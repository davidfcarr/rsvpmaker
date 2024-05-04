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
				'post_type'    => 'rsvpmaker_form',
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
	global $post, $rsvp_options;
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
			$data['post_type']    = 'rsvpmaker_form';
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
			$data['post_type']    = 'rsvpmaker_form';
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

function rsvpmaker_flexible_form_wrapper($atts,$content) {
	global $post;
	$slug = empty($atts['appslug']) ? 'contact' : sanitize_text_field($atts['appslug']);
	$recaptcha = !empty($atts['recaptcha']);
	update_post_meta($post->ID,'flexform_recaptcha',$recaptcha);
	$button_label = empty($atts['button_label']) ? 'Submit' : sanitize_text_field($atts['button_label']);
	$output = sprintf('<form class="rsvpmaker-flexible-form %s" id="flexible-form-%s"><input type="hidden" id="appslug" name="appslug" value="%s"><input type="hidden" name="post_id" value="%d">',$slug,$slug,$slug,$post->ID) . $content;
	$output .= rsvpmaker_nonce('field');
	if($recaptcha)
		{
			$output .= rsvpmaker_recaptcha_output(true);
		}
	$output .= sprintf('<p><button>%s</button></p></form>',$button_label);
	$output .= sprintf('<div id="flexform-result-%s"></div>',$slug);
	return $output;
}

function rsvpmaker_contact_form($postvars) {
if(!is_email($postvars['email']))
	return array('message' => 'Missing required field, email');
global $rsvp_options;
$mail['html'] = '';
foreach($postvars as $index => $value) {
	$value = stripslashes($value);
	$label = ucfirst(str_replace('_',' ',$index));
	$mail['html'] .= sprintf("<p><strong>%s</strong><br>%s</p>",$label,$value);
}
$mail['from'] = $postvars['email'];
$mail['subject'] = 'Contact form';
if(isset($postvars['subject']))
	$mail['subject'] .= ': '.$postvars['subject'];
$mail['subject'] .= ' ('.get_bloginfo('name').')';
$mail['to'] = $rsvp_options['rsvp_to'];
$result = rsvpmailer($mail);
$message = empty($result) ? '<span style="border: medium solid red;padding:10px;">Unknown error</span>' : '<span style="border: medium solid green;padding:10px;">Message sent</span>';
return array('message' => 'Submitted: '.$message);
}