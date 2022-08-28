<?php

function upgrade_rsvpform( $future = true, $rsvp_form_post = 0 ) {

	global $rsvp_options;

	$newform = true;

	$form = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Phone","slug":"phone"} /-->

<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} /-->

<!-- wp:rsvpmaker/guests -->

<div class="wp-block-rsvpmaker-guests"><!-- wp:paragraph -->

<p></p>

<!-- /wp:paragraph --></div>

<!-- /wp:rsvpmaker/guests -->

<!-- wp:rsvpmaker/formnote /-->';

	if ( $rsvp_form_post ) {
		$post = get_post( $rsvp_form_post );
		if ( ! empty( $post ) && ( $post->post_status == 'publish' ) ) {
			$rsvp_options['rsvp_form'] = $rsvp_form_post;
			wp_update_post(
				array(
					'ID'           => $rsvp_form_post,
					'post_title'   => 'RSVP Form:Default',
					'post_content' => $form,
				)
			);
			$newform = false;
		}
	}

	if ( $newform ) {
		$rsvp_options['rsvp_form'] = wp_insert_post(
			array(
				'post_title'   => 'RSVP Form:Default',
				'post_content' => $form,
				'post_status'  => 'publish',
				'post_type'    => 'rsvpmaker',
				'post_parent'  => 0,
			)
		);
		update_option( 'RSVPMAKER_Options', $rsvp_options );
		update_post_meta( $rsvp_options['rsvp_form'], '_rsvpmaker_special', 'RSVP Form' );
	}

	if ( $future ) {
		$results = get_future_events();
		if ( $results ) {
			foreach ( $results as $post ) {
				update_post_meta( $post->ID, '_rsvp_form', $rsvp_options['rsvp_form'] );
			}
		}
	}

	return $rsvp_options['rsvp_form'];

}

function customize_rsvp_form() {
	global $current_user, $wpdb, $rsvp_options;
	if ( ! empty( $_GET['rsvp_form_new'] ) ) {
		$id = rsvpmaker_get_form_id( $_GET['rsvp_form_new'] );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		exit;
	}

	if ( ! empty( $_GET['rsvp_form_switch'] ) && ! empty( $_GET['post_id'] ) ) {
		$id      = (int) $_GET['rsvp_form_switch'];
		$post_id = (int) $_GET['post_id'];
		update_post_meta( $post_id, '_rsvp_form', $id );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		exit;
	}

	if ( current_user_can( 'manage_options' ) && isset( $_GET['upgrade_rsvpform'] ) ) {
		$id = upgrade_rsvpform();
	}

	if ( isset( $_GET['rsvpcz_default'] ) && isset( $_GET['post_id'] ) ) {
		$meta_key = sanitize_text_field($_GET['rsvpcz_default']);
		$post_id  = (int) $_GET['post_id'];
		$id       = $rsvp_options[ $meta_key ];
		update_post_meta( $post_id, '_' . $meta_key, $id );
	}

	if ( isset( $_GET['rsvpcz'] ) && isset( $_GET['post_id'] ) ) {
		$meta_key = sanitize_text_field($_GET['rsvpcz']);
		$parent   = (int) $_GET['post_id'];
		$title    = sanitize_text_field(stripslashes($_GET['title'])) . ':' . $parent;
		$content  = '';
		if ( isset( $_GET['source'] ) ) {
			$source = (int) $_GET['source'];
			if ( $source ) {
				$old     = get_post( $source );
				$content = ( empty( $old->post_content ) ) ? '' : $old->post_content;
			}
		}

		$new['post_title'] = $title;

		$new['post_parent'] = $parent;

		$new['post_status'] = 'publish';

		$new['post_type'] = ( $meta_key == '_rsvp_form' ) ? 'rsvpmaker' : 'rsvpemail';

		$new['post_author'] = $current_user->ID;

		$new['post_content'] = $content;

		$id = wp_insert_post( $new );
		if ( ! $id ) {
			return;
		}

		if ( $source ) {

			rsvpmaker_copy_metadata( $source, $id );
		}

		update_post_meta( $parent, $meta_key, $id );

		if ( $meta_key == '_rsvp_form' ) {

			update_post_meta( $id, '_rsvpmaker_special', 'RSVP Form' );// important to make form blocks available

		} else {
			update_post_meta( $id, '_rsvpmaker_special', $title );
		}
	}

	if ( isset( $_GET['customize_rsvpconfirm'] ) ) {

		$parent = (int) $_GET['post_id'];

		$source = (int) get_post_meta( $parent, '_rsvp_confirm', true );

		$old = get_post( $source );

		if ( $old->post_parent ) { // false for default message

			$id = $old->ID; // if link called after custom post already created

		} elseif ( $old ) {

			$new['post_title'] = 'Confirmation:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $old->post_content;

			$id = wp_insert_post( $new );

			if ( $id ) {

				update_post_meta( $parent, '_rsvp_confirm', $id );
			}

			update_post_meta( $id, '_rsvpmaker_special', 'Confirmation Message' );

		}
	}

	if ( isset( $_POST['create_reminder_for'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

		$parent  = $post_id = (int) $_POST['create_reminder_for'];
		$event   = get_post( $post_id );
		$subject = $event->post_title;

		$hours = (int) $_REQUEST['hours'];

		$key = '_rsvp_reminder_msg_' . $hours;

		$copy_from = (int) $_POST['copy_from'];

		$content = '';

		if ( $copy_from ) {

			$copy = get_post( $copy_from );

			$content = $copy->post_content;

		}

		$id = get_post_meta( $parent, $key, true );

		if ( ! $id ) {

			$label = ( $hours > 0 ) ? __( 'Follow Up', 'rsvpmaker' ) : __( 'Reminder', 'rsvpmaker' );

			$title = $label . ': ' . get_the_title( $post_id ) . ' [datetime]';

			$new['post_title'] = $title;

			$new['post_parent'] = $post_id;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $content;

			$id = wp_insert_post( $new );

		}

		if ( $id ) {

			update_post_meta( $parent, $key, $id );

			update_post_meta( $id, '_rsvpmaker_special', 'Reminder (' . $hours . ' hours) ' . $subject );

			if ( isset( $_POST['paid_only'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

				update_post_meta( $id, 'paid_only_confirmation', 1 );
			}

			if ( rsvpmaker_is_template( $post_id ) ) {

				rsvpmaker_template_reminder_add( $hours, $post_id );

				rsvpautorenew_test(); // will add to the next scheduled event associated with template

				// header('Location: '.admin_url('edit.php?page=rsvp_reminders&post_type=rsvpmaker&template_reminder=1&post_id=').$post_id);

				// exit();

			} else {

				$start_time = get_rsvpmaker_timestamp($post_id);
				rsvpmaker_reminder_cron( $hours, $start_time, $post_id );
			}
		}
	}

	if ( isset( $_GET['payment_confirmation'] ) ) {

		$parent = (int) $_GET['post_id'];

		$id = get_post_meta( $parent, 'payment_confirmation_message', true );

		$source = ( isset( $_GET['source'] ) ) ? (int) $_GET['source'] : 0;

		if ( empty( $id ) || $source ) {

			$new['post_title'] = 'Payment Confirmation:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'draft';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			if ( $source ) {

				$source_post = get_post( $source );

				$new['post_content'] = $source_post->post_content;

			} else {
				$new['post_content'] = '';
			}

			$id = wp_insert_post( $new );

			if ( $id ) {

				update_post_meta( $parent, 'payment_confirmation_message', $id );
			}

			update_post_meta( $id, '_rsvpmaker_special', 'Payment Confirmation Message' );

		}
	}

	if ( isset( $_GET['customize_form'] ) ) {

		$parent = (int) $_GET['post_id'];

		$source = (int) get_post_meta( $parent, '_rsvp_form', true );

		$old = get_post( $source );

		if ( $old->post_parent ) { // false for default form

			$id = $old->ID; // if link called after custom post already created

		} elseif ( $old ) {

			$new['post_title'] = 'RSVP Form:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpmaker';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $old->post_content;

			remove_all_filters( 'content_save_pre' ); // don't allow form fields to be filtered out

			$id = wp_insert_post( $new );

			if ( $id ) {
				update_post_meta( $parent, '_rsvp_form', $id );
				update_post_meta( $id, '_rsvpmaker_special', 'RSVP Form' );
			}
		}
	}
}

function rsvp_field_apply_default( $content, $slug, $default ) {

	if ( strpos( $content, 'type="text"' ) || strpos( $content, 'type="email"' ) ) {

		$content = str_replace( 'value=""', 'value="' . $default . '"', $content );

	} elseif ( strpos( $content, '</textarea>' ) ) {

		$content = str_replace( '</textarea>', $default . '</textarea>', $content );
	}

	$find = 'value="' . $default . '"';

	if ( strpos( $content, '</select>' ) ) {

		$content = str_replace( $find, $find . ' selected="selected"', $content );

	} elseif ( strpos( $content, 'type="radio"' ) ) {

		$content = str_replace( $find, $find . ' checked="checked"', $content );
	}

	return $content;

}

function rsvp_form_text( $atts, $content ) {

	global $post;

	global $rsvp_required_field;

	if ( empty( $atts['slug'] ) || empty( $atts['label'] ) ) {

		return;
	}

	$slug = $atts['slug'];
	if ( strpos( $slug, ' ' ) ) {
		$slug = preg_replace( '/[^a-zA-Z0-9_]/', '_', $slug );
	}

	$label = $atts['label'];

	$required = '';

	if ( isset( $atts['required'] ) || isset( $atts['require'] ) ) {
		$rsvp_required_field[ $slug ] = $slug;
		$required                     = 'required';
	}

	$content = sprintf( '<div class="wp-block-rsvpmaker-formfield %srsvpblock"><p><label>%s:</label> <span class="%s"><input class="%s" type="text" name="profile[%s]" id="%s" value=""/></span></p></div>', esc_attr( $required ), esc_html( $label ), esc_attr( $required ), esc_attr( $slug ), esc_attr( $slug ), esc_attr( $slug ) );

	if ( $slug == 'email' ) {

		$content .= '<div id="rsvp_email_lookup"></div>';
	}

	return rsvp_form_field( $atts, $content );

}

function rsvp_form_textarea( $atts, $content = '' ) {

	global $post;

	global $rsvp_required_field;

	if ( empty( $atts['slug'] ) || empty( $atts['label'] ) ) {

		return;
	}

	$slug = $atts['slug'];

	$label = $atts['label'];

	$rows = ( empty( $atts['rows'] ) ) ? '3' : $atts['rows'];

	$required = '';

	$content = sprintf( '<div class="wp-block-rsvpmaker-formtextarea %srsvpblock"><p><label>%s:</label></p><p><textarea rows="%d" class="%s" type="text" name="profile[%s]" id="%s"></textarea></p></div>', esc_attr( $required ), esc_html( $label ), esc_attr( $required ), esc_attr( $rows ), esc_attr( $slug ), esc_attr( $slug ), esc_attr( $slug ) );

	return rsvp_form_field( $atts, $content );

}

function rsvp_form_select( $atts, $content = '' ) {

	global $post;

	global $rsvp_required_field;

	if ( empty( $atts['slug'] ) || empty( $atts['label'] ) ) {

		return;
	}

	$slug = $atts['slug'];

	$label = $atts['label'];

	$required = '';

	$choices = '';

	if ( isset( $atts['choicearray'] ) && ! empty( $atts['choicearray'] ) && is_array( $atts['choicearray'] ) ) {

		foreach ( $atts['choicearray'] as $choice ) {

			$choices .= sprintf( '<option value="%s">%s</option>', esc_attr( $choice ), esc_attr( $choice ) );
		}
	}

	$content = sprintf( '<div class="wp-block-rsvpmaker-formselect %srsvpblock"><p><label>%s:</label> <span><select class="%s" name="profile[%s]" id="%s">%s</select></span></p></div>', esc_attr( $required ), esc_html( $label ), esc_attr( $slug ), esc_attr( $slug ), esc_attr( $slug ), $choices );

	return rsvp_form_field( $atts, $content );

}

function rsvp_form_radio( $atts, $content = '' ) {

	global $post;

	global $rsvp_required_field;

	if ( empty( $atts['slug'] ) || empty( $atts['label'] ) ) {

		return;
	}

	$slug = $atts['slug'];

	$label   = $atts['label'];
	$choices = '';

	if ( isset( $atts['choicearray'] ) && ! empty( $atts['choicearray'] ) && is_array( $atts['choicearray'] ) ) {

		foreach ( $atts['choicearray'] as $choice ) {

			$choices .= sprintf( '<span class="rsvp-form-radio"><input type="radio" class="%s" name="profile[%s]" id="%s" value="%s"/> %s </span>', esc_attr( $slug ), esc_attr( $slug ), esc_attr( $slug ), esc_attr( $choice ), esc_html( $choice ) );
		}
	}

	$required = '';

	$content = sprintf( '<div class="wp-block-rsvpmaker-formradio %srsvpblock"><p><label>%s:</label> %s</p></div>', esc_attr( $required ), esc_html( $label ), $choices );

	return rsvp_form_field( $atts, $content );

}

function rsvp_form_field( $atts, $content = '' ) {

	// same for all field types

	global $post;

	global $rsvp_required_field;

	if ( empty( $atts['slug'] ) || empty( $atts['label'] ) ) {

		return;
	}

	$slug = $atts['slug'];

	$label = $atts['label'];

	update_post_meta( $post->ID, 'rsvpform' . $slug, $label );

	global $profile;

	if ( ! empty( $atts['guestform'] ) ) { // if not set, default is true
		rsvp_add_guest_field( $content, $slug );
	}

	if ( empty( $profile[ $slug ] ) ) {

		return $content;// .$slug.': no default'.var_export($profile,true);
	}

	$default = $profile[ $slug ];

	return rsvp_field_apply_default( $content, $slug, $default );

}

function rsvp_form_note( $atts = array() ) {

	$label = ( empty( $atts['label'] ) ) ? 'Note' : esc_html( $atts['label'] );

	return sprintf( '<p>%s:<br><textarea name="note"></textarea></p>', $label );

}

function rsvp_guest_content( $content ) {
	$content = str_replace( ']"', '][]"', $content );
	$content = str_replace( '"profile', '"guest', $content );
	$content = preg_replace( '/id="[^"]+"/', '', $content );// no ids on guest fields
	$content = str_replace( 'class="required"', '', $content );// no required fields
	return $content;
}

function rsvp_add_guest_field( $content, $slug ) {
	global $guestfields;
	$guestfields[ $slug ] = rsvp_guest_content( $content );
}

function rsvp_form_guests( $atts, $content ) {

	if ( is_admin() ) {
		return $content;
	}

	$content = '';// ignore content

	global $guestfields;

	global $gprofile;

	$shared = '';

	$label = ( isset( $atts['label'] ) ) ? $atts['label'] : __( 'Guest', 'rsvpmaker' );

	if ( is_array( $guestfields ) ) {

		foreach ( $guestfields as $slug => $field ) {

			$shared .= $field;
		}
	}

	$template = '<div class="guest_blank" id="first_blank"><p><strong>' . __( 'Guest', 'rsvpmaker' ) . ' ###</strong></p>' . $shared . $content . '</div>';// fields shared from master form, plus added fields

	$addmore = ( isset( $atts['addmore'] ) ) ? $atts['addmore'] : __( 'Add more guests', 'rsvpmaker' );

	global $wpdb;

	global $blanks_allowed;

	global $master_rsvp;

	// $master_rsvp = 4;//test data

	$wpdb->show_errors();

	$output = '';

	$count = 1; // reserve 0 for host

	$max_party = ( isset( $atts['max_party'] ) ) ? (int) $atts['max_party'] : 0;

	if ( isset( $master_rsvp ) && $master_rsvp ) {

		$guestsql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE master_rsvp=' . $master_rsvp . ' ORDER BY id';

		if ( $results = $wpdb->get_results( $guestsql, ARRAY_A ) ) {

			foreach ( $results as $row ) {

				$output .= sprintf( '<div class="guest_blank"><p><strong>%s %d</strong></p>', $label, $count ) . "\n";

				$gprofile = rsvp_row_to_profile( $row );

				$shared = '';

				if ( is_array( $guestfields ) ) {

					foreach ( $guestfields as $slug => $field ) {

						if ( ! empty( $gprofile[ $slug ] ) ) {

							$shared .= rsvp_field_apply_default( $field, $slug, $gprofile[ $slug ] );

						} else {
							$shared .= $field;
						}
					}
				}

				$output .= $shared . do_blocks( $content );

				$output = str_replace( '[]', '[' . $count . ']', $output );

				$output .= sprintf( '<div><input type="checkbox" name="guestdelete[%s]" value="%s" /> ' . __( 'Delete Guest', 'rsvpmaker' ) . ' %d</div><input type="hidden" name="guest[id][%s]" value="%s">', esc_attr( $row['id'] ), esc_attr( $row['id'] ), $count, $count, esc_attr( $row['id'] ) );

				$count++;

			}
		}
	}

	$output .= $template;

	// $output .= '<script type="text/javascript"> var guestcount ='.$count.'; </script>';

	$max_guests = $blanks_allowed + $count;

	if ( $max_party ) {

		$max_guests = ( $max_party > $max_guests ) ? $max_guests : $max_party; // use the lower limit
	}

	// now the blank field

	if ( $blanks_allowed < 1 ) {

		return $output . '<p><em>' . esc_html( __( 'No room for additional guests', 'rsvpmaker' ) ) . '</em><p>'; // if event is full, no additional guests

	} elseif ( $count > $max_guests ) {

		return $output . '<p><em>' . esc_html( __( 'No room for additional guests', 'rsvpmaker' ) ) . '</em><p>'; // limit by # of guests per person

	} elseif ( $max_guests && ( $count >= $max_guests ) ) {

		return $output . '<p><em>' . esc_html( __( 'No room for additional guests (max per party)', 'rsvpmaker' ) ) . '</em><p>'; // limit by # of guests per person
	}

	$output = '<div id="guest_section" tabindex="-1">' . "\n" . $output . '</div>' . '<!-- end of guest section-->';

	if ( $max_guests > ( $count + 1 ) ) {

		$output .= '<p><a href="#guest_section" id="add_guests" name="add_guests">(+) ' . $addmore . "</a><!-- end of guest section--></p>\n";
	}

	$output .= '<script type="text/javascript"> var guestcount =' . $count . '; </script>';

	return $output;

}

function stripe_form_wrapper( $atts, $content ) {

	global $post;

	$permalink = get_permalink( $post->ID );

	$amount = ( isset( $atts['amount'] ) ) ? $atts['amount'] : '';

	$vars['paymentType'] = ( isset( $atts['paymentType'] ) ) ? $atts['paymentType'] : '';

	$vars['description'] = ( isset( $atts['description'] ) ) ? $atts['description'] : 'Online Payment ' . get_bloginfo( 'name' );

	if ( ! empty( $_POST )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  ) {

		$output = '';

		if ( ! empty( $_POST['profile'] ) ) {

			foreach ( $_POST['profile'] as $slug => $value ) {

				$value = sanitize_text_field( $value );

				$output .= sprintf( '<p>%s: %s</p>' . "\n", esc_html( $slug ), esc_html( $value ) );

				$vars[ $slug ] = $value;

			}
		}

		foreach ( $_POST as $slug => $value ) {

			$value = sanitize_text_field( $value );

			if ( $slug != 'profile' ) {

				$output .= sprintf( '<p>%s: %s</p>' . "\n", esc_html( $slug ), esc_html( $value ) );

				$vars[ $slug ] = $value;

			}
		}

		preg_match_all( '/<p.+\/p>/', $content, $matches );

		$content = $output;

		$paragraphs = '';

		if ( ! empty( $matches ) ) {

			foreach ( $matches[0] as $paragraph ) {

				if ( ! strpos( $paragraph, '<input' ) && ! strpos( $paragraph, '<textarea' ) && ! strpos( $paragraph, '<select' ) ) {

					$paragraphs .= $paragraph . "\n";
				}
			}
		}

		$content .= wp_kses_post( $paragraphs );

		if ( ! empty( $vars['paymentType'] ) ) {

			$content .= sprintf( '<p>Payment type: %s</p>', $vars['paymentType'] );

		}

		$vars['contract'] = $paragraphs;

		$content .= rsvpmaker_stripe_form( $vars );

		return $content;

	}

	$content = sprintf( '<form method="post" action="%s">', $permalink ) . $content;
	$content .= rsvpmaker_nonce('return');
	$content .= sprintf( '<input type="hidden" name="amount" value="%s" /><button>Submit</button></form>', $amount );
	return $content;
}

function remove_save_content_filters() {

	if ( isset( $_REQUEST['_locale'] ) && ( $_REQUEST['_locale'] == 'user' ) ) {

		$request_body = file_get_contents( 'php://input' );

		if ( strpos( $request_body, 'wp:rsvpmaker/formfield' ) ) {
			// prevent html filtering on form for non-administrators

			remove_all_filters( 'content_save_pre' ); // don't allow form fields to be filtered out

			remove_all_filters( 'content_filtered_save_pre' );// 'content_filtered_save_pre', 'wp_filter_post_kses'

		}
	}

}

function rsvpmaker_get_forms() {
	global $post;
	$post_id = empty( $post->ID ) ? 0 : $post->ID;
	$forms   = get_option( 'rsvpmaker_forms' );
	if ( empty( $forms['webinar'] ) || empty( get_post( $forms['webinar'] ) ) || empty( $forms['simple'] ) || empty( get_post( $forms['simple'] ) ) ) {
		if ( empty( $forms['webinar'] ) ) {
			$form                 = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formnote /-->
			<!-- wp:rsvpmaker/formchimp -->
			<div class="wp-block-rsvpmaker-formchimp"><p><input class="email_list_ok" type="checkbox" name="profile[email_list_ok]" id="email_list_ok" value="1"/> Add me to your email list</p></div>
			<!-- /wp:rsvpmaker/formchimp -->';
			$data['post_title']   = 'Form:Default for Webinars';
			$data['post_content'] = $form;
			$data['post_status']  = 'publish';
			$data['post_author']  = 1;
			$data['post_type']    = 'rsvpmaker';
			$forms['webinar']     = wp_insert_post( $data );
			update_post_meta( $forms['webinar'], '_rsvpmaker_special', 'RSVP Form' );
		}
		if ( empty( $forms['simple'] ) ) {
			$form                 = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->
			<!-- wp:rsvpmaker/formfield {"label":"Phone","slug":"phone"} /-->
			<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} /-->
			<!-- wp:rsvpmaker/formnote /-->
			<!-- wp:rsvpmaker/formchimp -->
			<div class="wp-block-rsvpmaker-formchimp"><p><input class="email_list_ok" type="checkbox" name="profile[email_list_ok]" id="email_list_ok" value="1"/> Add me to your email list</p></div>
			<!-- /wp:rsvpmaker/formchimp -->';
			$data['post_title']   = 'Form:Simple';
			$data['post_content'] = $form;
			$data['post_status']  = 'publish';
			$data['post_author']  = 1;
			$data['post_type']    = 'rsvpmaker';
			$forms['simple']      = wp_insert_post( $data );
			update_post_meta( $forms['simple'], '_rsvpmaker_special', 'RSVP Form' );
		}
		update_option( 'rsvpmaker_forms', $forms );
	}
	return $forms;
}

function rsvpmaker_get_form_id( $slug ) {
	$title   = $slug;
	$slug    = preg_replace( '/[^a-zA-Z0-9]/', '_', $slug );
	$forms   = rsvpmaker_get_forms();
	$post_id = empty( $post->ID ) ? 0 : $post->ID;
	if ( empty( $forms[ $slug ] ) ) {
			$form                 = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->
<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->
<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->
<!-- wp:rsvpmaker/formfield {"label":"Phone","slug":"phone"} /-->
<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} /-->
<!-- wp:rsvpmaker/formradio {"label":"Radio Buttons","slug":"radio_buttons","choicearray":["Choice A"," Choice B"," Choice C"],"guestform":true} /-->
<!-- wp:rsvpmaker/formselect {"label":"Dropdown List","slug":"dropdown_list","choicearray":["Choice A"," Choice B"," Choice C"],"guestform":true} /-->
<!-- wp:rsvpmaker/formchimp -->
<div class="wp-block-rsvpmaker-formchimp"><p><input class="email_list_ok" type="checkbox" name="profile[email_list_ok]" id="email_list_ok" value="1"/> Add me to your email list</p></div>
<!-- /wp:rsvpmaker/formchimp -->
<!-- wp:rsvpmaker/formtextarea {"label":"Text Area","slug":"text_area","guestform":true} /-->
<!-- wp:rsvpmaker/guests -->
<div class="wp-block-rsvpmaker-guests"><!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph --></div>
<!-- /wp:rsvpmaker/guests -->
<!-- wp:rsvpmaker/formnote /-->
<!-- /wp:rsvpmaker/formchimp -->
';
			$data['post_title']   = 'Form:' . $title;
			$data['post_content'] = $form;
			$data['post_status']  = 'publish';
			$data['post_author']  = 1;
			$data['post_type']    = 'rsvpmaker';
			$forms[ $slug ]       = wp_insert_post( $data );
			update_post_meta( $forms[ $slug ], '_rsvpmaker_special', 'RSVP Form' );
			// if($post_id)
				// get_post_meta($post_id,'_rsvp_form',$forms['webinar']);
			update_option( 'rsvpmaker_forms', $forms );
	}
		return $forms[ $slug ];
}

add_action( 'init', 'remove_save_content_filters', 99 );
add_action( 'set_current_user', 'remove_save_content_filters', 99 );

function rsvpmaker_formchimp( $atts, $content ) {
	$checked = empty( $atts['checked'] ) ? '' : ' checked="checked" ';
	return '<p><input class="email_list_ok" type="checkbox" name="profile[email_list_ok]" id="email_list_ok" value="1" ' . $checked . ' /> ' . __( 'Add me to your email list', 'rsvpmaker' ) . '</p>';
}

function rsvpmaker_add_to_list_on_rsvp_form() {
	global $rsvp_options;
	if(empty($rsvp_options['rsvp_form']))
		return;
	$fpost = get_post($rsvp_options['rsvp_form']);
	if(isset($_POST['add_add_checkbox']) && !strpos($fpost->post_content,'formchimp')) {
		$update['post_content'] = $fpost->post_content . "\n\n".'<!-- wp:rsvpmaker/formchimp {"checked":true} -->
<div class="wp-block-rsvpmaker-formchimp"><p><input class="email_list_ok" type="checkbox" name="profile[email_list_ok]" id="email_list_ok" value="1" checked/> Add me to your email list</p></div>
<!-- /wp:rsvpmaker/formchimp -->';
		$update['ID'] = $fpost->ID;
		wp_update_post($update);
	}
	elseif(!strpos($fpost->post_content,'wp-block-rsvpmaker-formchimp'))
	{
		echo '<p><input type="checkbox" name="add_add_checkbox"> Include "Add me to your email list" checkbox on your default event signup form.</p>';
	}
}

function rsvpmail_signup_page_add() {
	global $wpdb, $current_user;
	if(isset($_POST['add_email_signup_page'])){
		$new['post_title'] = 'Join Our Email List';
		$new['post_content'] = '<!-- wp:rsvpmaker/emailguestsignup /-->';
		$new['post_author'] = $current_user->ID;
		$new['post_type'] = 'page';
		$new['post_status'] = 'publish';
		wp_insert_post($new);
	}

	$sql = "SELECT * from $wpdb->posts WHERE post_content LIKE '%wp:rsvpmaker/emailguestsignup%' and post_status='publish' ";
	$results = $wpdb->get_results($sql);
	if($results) {
		echo '<p><strong>Email Signup Form is Published Here:</strong> ';
		foreach($results as $row) 
			printf('<a href="%s">%s</a> &nbsp;',get_permalink($row->ID),$row->post_title);
		echo '</p>';
	}
	else {
		echo '<p><input type="checkbox" name="add_email_signup_page" value="1"> Add Email Signup Page</p>';
	}
}