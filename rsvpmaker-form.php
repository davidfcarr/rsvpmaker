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
		elseif($rsvp_form_post != $rsvp_options['rsvp_form']) {
			//if custom form is missing, return default form if possible
			$post = get_post($rsvp_options['rsvp_form']);
			if($post)
				return $rsvp_options['rsvp_form'];
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
	}
	error_log('upgrade form '.$rsvp_options['rsvp_form']);
	update_option( 'RSVPMAKER_Options', $rsvp_options );
	update_post_meta( $rsvp_options['rsvp_form'], '_rsvpmaker_special', 'RSVP Form' );

	if ( $future ) {
		$results = rsvpmaker_get_future_events();
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
	$required_marker = '';
	if ( !empty( $atts['required'] ) || !empty( $atts['require'] ) ) {
		$rsvp_required_field[ $slug ] = $slug;
		$required                     = 'required';
		$required_marker = ' <span class="rsvprequiredfield">*</span>';
	}

	if(isset($atts['type']))
		$type = $atts['type'];
	elseif ( strpos( $slug, 'email' ) !== false ) {	// if "email" is anywhere in the slug, use email type	
		$type = 'email';
		}
	elseif ( strpos( $slug, 'phone' ) !== false ) {	// if "phone" is anywhere in the slug, use tel type	
		$type = 'tel';
	}
	else {
		$type = 'text';
	}

	$content = sprintf( '<div class="wp-block-rsvpmaker-formfield %srsvpblock"><p><label>%s:%s</label> <span class="%s"><input class="%s" type="%s" name="profile[%s]" id="%s" value=""/></span></p></div>', esc_attr( $required ), esc_html( $label ), $required_marker, esc_attr( $required ), esc_attr( $slug ), esc_attr( $type ), esc_attr( $slug ), esc_attr( $slug ) );

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

	$pricing = rsvpmaker_item_pricing($post->ID);
	$currency = ( empty( $rsvp_options['paypal_currency'] ) ) ? 'usd' : strtolower( $rsvp_options['paypal_currency'] );
	if ( $currency == 'usd' ) {
		$currency = '$';
	} elseif ( $currency == 'eur' ) {
		$currency = '€';
	}
	else {
		$currency = strtoupper($currency).' ';
	}

	if ( isset( $atts['choicearray'] ) && ! empty( $atts['choicearray'] ) && is_array( $atts['choicearray'] ) ) {

		foreach ( $atts['choicearray'] as $i => $choice ) {
			$is_checked = (isset($atts['defaultToFirst']) && !empty($atts['defaultToFirst']) && 0 == $i) ? ' checked="checked" ' : ''; 
			$pricelabel = (!empty($pricing) && !empty($pricing->$slug) && !empty($pricing->$slug->$choice)) ? ' +'.$currency.$pricing->$slug->$choice : '';
			$choices .= sprintf( '<div class="rsvp-form-radio"><input type="radio" class="%s" name="profile[%s]" id="%s" value="%s" %s/> %s </div>', esc_attr( $slug ), esc_attr( $slug ), esc_attr( $slug ), esc_attr( $choice ), $is_checked, esc_html( $choice.$pricelabel ) );
		}
	}

	$required = '';

	$content = sprintf( '<div class="wp-block-rsvpmaker-formradio %s rsvpblock"><p><label>%s:</label> %s</p></div>', esc_attr( $required ), esc_html( $label ), $choices );

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

		return $content;
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

function rsvp_form_guests( $atts, $content = '' ) {
	global $wpdb, $blanks_allowed,$master_rsvp,$is_rsvp_report, $post;

	if ( is_admin() && empty($_GET['page']) ) {
		return $content;
	}

	$content = '';// ignore content

	global $guestfields;

	global $gprofile;

	$shared = '';

	$label = ( isset( $atts['label'] ) ) ? $atts['label'] : '#';
	$max_party = ( isset( $atts['max_party'] ) ) ? (int) $atts['max_party'] : 0;
	$count = ($master_rsvp) ? $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM %i WHERE master_rsvp=%d",$wpdb->prefix . 'rsvpmaker', $master_rsvp)) : 0;
	$max_guests = $blanks_allowed + $count;

	if ( $max_party ) {
		$max_guests = ( $max_party > $max_guests ) ? $max_guests : $max_party; // use the lower limit
	}

	if ( is_array( $guestfields ) ) {

		foreach ( $guestfields as $slug => $field ) {
			$shared .= $field;
		}
	}
	$output = '<input type="hidden" id="max_guests" value="' . $max_guests . '" />'."\n";

	//$template_content = preg_replace('/\[[^\]^a-zA-Z]*\]/', '[###]', $shared . $content);
	//echo "\n".htmlentities($template_content)."\n";
	$output .= '<div class="guest_blank" id="guest_blank_template" style="display:none"><p><strong># ###</strong></p>' .$shared . $content. '</div>';// fields shared from master form, plus added fields

	$count = 1; // reserve 0 for host

	$max_party = ( isset( $atts['max_party'] ) ) ? (int) $atts['max_party'] : 0;

	if ( isset( $master_rsvp ) && $master_rsvp ) {

		$guestsql = $wpdb->prepare('SELECT * FROM %i WHERE master_rsvp=%d ORDER BY id',$wpdb->prefix . 'rsvpmaker',$master_rsvp);

		if ( $results = $wpdb->get_results( $guestsql, ARRAY_A ) ) {

			foreach ( $results as $row ) {

				$count++;

				$output .= sprintf( '<div class="guest_blank" id="guest_blank_%d"><p><strong>%s %d</strong></p>', $count, $label, $count ) . "\n";

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

				$output .= '</div>';

				$output .= sprintf( '<input type="hidden" name="guest[id][%s]" value="%s">', $count, esc_attr( $row['id'] ) );

			}
		}
	}

	//$output .= $template;

	$max_guests = $blanks_allowed + $count;

	if ( $max_party ) {

		$max_guests = ( $max_party > $max_guests ) ? $max_guests : $max_party; // use the lower limit
	}

	// now the blank field
	if(isset($post->post_type) && strpos($post->post_type,'svpmaker') && !$is_rsvp_report) {
		if ( $max_guests && $blanks_allowed < 1 ) {

			return $output . '<p><em>' . esc_html( __( 'No room for additional guests', 'rsvpmaker' ) ) . '</em><p>'; // if event is full, no additional guests

		} elseif (  $blanks_allowed && $count > $max_guests ) {

			return $output . '<p><em>' . esc_html( __( 'No room for additional guests', 'rsvpmaker' ) ) . '</em><p>'; // limit by # of guests per person

		} elseif ( $blanks_allowed && $max_guests && ( $count >= $max_guests ) ) {

			return $output . '<p><em>' . esc_html( __( 'No room for additional guests (max per party)', 'rsvpmaker' ) ) . '</em><p>'; // limit by # of guests per person
		}	
	}
	if ( !strpos($post->post_type,'svpmaker') || $max_guests > ( $count + 1 ) || $is_rsvp_report ) {
		$output = '<h3>'.esc_html__('Add Guests','rsvpmaker').'</h3><p><input type="hidden" id="starting_count" value="'.esc_attr($count).'" /> <input type="number" id="people_in_party" name="people_in_party" min="1" value="'.esc_attr($count).'" style="width: 50px;" > '.__('People in party').'</p><p><strong id="rsvphost"># 1 (You)</strong></p>'."\n".$output;
	}

	$output = '<div id="guest_section" tabindex="-1">' . "\n" . $output . '</div>' . '<!-- end of guest section-->';

	$output .= sprintf('<input type="hidden" id="guestcount" value="%d" />',$count);//'<script type="text/javascript"> var guestcount =' . $count . '; </script>';

	return $output;

}

function rsvpmaker_stripe_form_wrapper( $atts, $content ) {

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

function rsvpmaker_remove_save_content_filters() {

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

add_action( 'set_current_user', 'rsvpmaker_remove_save_content_filters', 99 );

// not necessary, static block
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
			printf('<a href="%s">%s</a> &nbsp;',esc_attr(get_permalink($row->ID)),esc_html($row->post_title));
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
$purchase_link = null;
foreach($postvars as $index => $value) {
	if($index == 'post_id') {
		$post_id = $value;
		continue;
	}
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

function rsvpmaker_item_pricing($post_id) {
	$form = get_post(get_post_meta($post_id,'_rsvp_form',true));
	$exclude = ['phone_type'];
	$item_prices = get_post_meta($post_id,'_rsvp_item_prices',true);
	if(!$item_prices)
		$item_prices = (object) [];
	//testing
	//if(!sizeof($item_prices))
		//$item_prices->meal_choice->Steak = 15.00;
	if($form and isset($form->post_content)) {
		$fields = rsvpmaker_data_from_document($form->post_content);
		foreach($fields as $field) {
			if(empty($field->slug) || in_array($field->slug,$exclude))
				continue;
			$slug = $field->slug;
			if(isset($field->choicearray)) {
				$slugdata = null;
				foreach($field->choicearray as $choice) {
					if(empty($slugdata)) {	
						if(!isset($item_prices->$slug))
							$slugdata = array($choice => 0);
						else 
							$slugdata = (array) $item_prices->$slug;
					}

					if(!isset($item_prices->$slug->$choice)) {
						$slugdata[$choice] = 0;
					}
				}
				$item_prices->$slug = (object) $slugdata;
			}		
		}
	}
	return $item_prices;
}

function rsvpmaker_form_field_labels($post_id) {
	$form = get_post(get_post_meta($post_id,'_rsvp_form',true));
	$field_labels = [];
	if($form and isset($form->post_content)) {
		$fields = rsvpmaker_data_from_document($form->post_content);
		foreach($fields as $field) {
			if(isset($field->slug) && isset($field->label))
				$field_labels[$field->slug] = $field->label;
		}
	}
	return $field_labels;
}

add_shortcode('rsvpmaker_contact_form_output','rsvpmaker_contact_form_capture_output');
function rsvpmaker_contact_form_capture_output($attributes) {
	if(isset($_GET['check_coupon']))
		return '<p>'.get_option($_GET['check_coupon']).'</p>';
	ob_start();
	rsvpmaker_contact_form_output($attributes);
	return ob_get_clean();
}

function rsvpmaker_contact_form_order($lookup) {
	global $wpdb;
	$purchase = get_transient($lookup['purchase_code']);
	$rsvprow = (array) $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=".intval($lookup['rsvp_id']));
	$rsvprow = rsvp_row_to_profile($rsvprow);
	$currency = ( empty( $purchase['currency'] ) ) ? 'USD' : strtoupper( $purchase['currency'] );
      $currency_symbol = '';
      if ( $currency == 'USD' ) {
        $currency_symbol = '$';
      } elseif ( $currency == 'EUR' ) {
        $currency_symbol = '€';
      }
	$description=$purchase['item_name'].' '.$purchase['item_count'].' @ '.$currency_symbol.$purchase['item_amount'].' = '.$currency_symbol.$purchase['total'].' '.$currency;
	$rsvprow['amount']=number_format($purchase['total'],2);
	$rsvprow['description']=$description;
	$rsvprow['purchase_code']=$lookup['purchase_code'];
	$rsvprow['rsvp_id'] = $lookup['rsvp_id'];
	$rsvprow['name'] = $rsvprow['first'].' '.$rsvprow['last'];
	if('PayPal REST API' == $lookup['gateway'])
	return rsvpmaker_paypal_button ($purchase['total'], $purchase['currency'], $description, $rsvprow).'<p>'.$description.'</p>';
	elseif('Stripe' == $_GET['gateway']) {
		return rsvpmaker_stripe_form( $rsvprow, true );
	}
	else {
		return 'Invalid payment gateway';
	}
}

add_shortcode('rsvpmaker_contact_form_shortcode','rsvpmaker_contact_form_shortcode');

function rsvpmaker_contact_form_shortcode($attributes) {
	ob_start();
	rsvpmaker_contact_form_output($attributes);
	return ob_get_clean();
}

function rsvpmaker_contact_form_output($attributes) {
	if(isset($_GET['purchase_code'])) {
			echo rsvpmaker_contact_form_order($_GET);
		return;
	}

global $current_user, $rsvp_options, $post;
$user_id = (isset($current_user->ID)) ? $current_user->ID : 0;
?>
<form id="rsvpmaker_contact_form"><input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>" /><input type="hidden" name="form_id" value="<?php if(isset($attributes['unique_id'])) echo esc_attr($attributes['unique_id']); ?>" />
<?php
if(empty($attributes['order']) && empty($attributes['sale']))
{
	$button_label = __('Send','rsvpmaker');
	$subject_label = (empty($attributes['subject_label'])) ? 'Subject' : $attributes['subject_label'];
	printf('<div class="wp-block-rsvpmaker-formfield"><label>%s:</label><input type="text" name="contact_subject"></div>',esc_html($subject_label));	
}
else {
	$button_label = __('Submit','rsvpmaker');
	printf('<input type="hidden" name="is_order" value="%s" />',esc_attr($attributes['order']));
}
rsvphoney_ui();
echo rsvp_form_jquery();
if(!empty($attributes['sale'])) {
	$amount = floatval($attributes['sale']);
	$item_label = (empty($attributes['item_label'])) ? __('Item','rsvpmaker') : $attributes['item_label'];
	printf( '<p>%s<input type="hidden" name="sale_item_name" value="%s"> @ %s <input type="number" name="sale_item_count" value="1" />',esc_html($item_label),esc_attr($item_label),esc_html($amount) );
	echo '<input type="hidden" name="sale_item_amount" value="'.esc_attr($amount).'" />';
}
echo rsvpmaker_basic_form( $attributes['form_id'] );
wp_nonce_field('rsvpmaker_contact','contact_confidential');
printf('<input type="hidden" name="user_id" value="%d" >',esc_attr($user_id));
$payment_gateway = get_rsvpmaker_payment_gateway();
if(isset($attributes['gateway']) && 'Default' != $attributes['gateway'])
	$payment_gateway = sanitize_text_field($attributes['gateway']);
printf('<input type="hidden" name="gateway" value="%s" />',esc_attr($payment_gateway));
if(!empty($attributes['gift']))
	echo '<input type="hidden" name="profile[is_gift_certificate]" value="yes" />';

if (! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) )
{
	rsvpmaker_recaptcha_output();
}
?>
<p><button id="rsvpmaker_contact_send"><?php echo $button_label; ?></button></p>
</form>
<script>
const form = document.querySelector("#rsvpmaker_contact_form");
console.log(form);

async function sendData() {
  // Associate the FormData object with the form element
  const formData = new FormData(form);
  const contactUrl = "<?php echo esc_url( rest_url( 'rsvpmaker/v1/contact_form' ) ); ?>";

  try {
    const response = await fetch(contactUrl, {
      method: "POST",
      // Set the FormData instance as the request body
      body: formData,
    });
	const json = await response.json();
	console.log('senddata response',json);
	if(json.purchase_link) {
		console.log('attempt redirect to '+json.purchase_link);
		form.innerHTML += '<div style="color:green;border: thin solid red; padding: 15px; margin: 15px;"><a href="'+json.purchase_link+'">Pay Now</a></div>';
		location.assign(json.purchase_link);
	}
	else {
	if(json.no_note)
		form.innerHTML += '<div style="color:red;border: thin solid red; padding: 15px; margin: 15px;">Note field left blank</div>';
    if(json.sending)
		form.innerHTML = '<div style="color:green;border: thin solid green; padding: 15px; margin: 15px;">Sent</div>';
	if(json.error)
		form.innerHTML += '<div style="color:red;border: thin solid red; padding: 15px; margin: 15px;">'+json.error+'</div>';
	}
  } catch (e) {
    console.error(e);
  }
}

// Take over form submission
form.addEventListener("submit", (event) => {
  event.preventDefault();
  sendData();
});
</script>
<?php
}



// Moved from rsvpmaker-util.php during cleanup
function save_replay_rsvp() {

		global $wpdb;

		global $rsvp_options;

		global $rsvp_id;

		if ( isset( $_POST['replay_rsvp'] ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

			if ( get_magic_quotes_gpc() ) {

				$_POST = array_map( 'stripslashes_deep', $_POST );
			}

			$req_uri = trim( sanitize_text_field($_POST['replay_rsvp']) );

			$req_uri .= ( strpos( $req_uri, '?' ) ) ? '&' : '?';

			// sanitize input

			foreach ( $_POST['profile'] as $name => $value ) {

				$rsvp[ $name ] = sanitize_text_field( $value );
			}

			if ( isset( $_POST['note'] ) ) {

				$note = sanitize_text_field( $_POST['note'] );

			} else {
				$note = '';
			}

			$answer = 'YES';

			$event = ( ! empty( $_POST['event'] ) ) ? (int) $_POST['event'] : 0;

			if ( ! $event ) {

				die( 'Event ID not set' );
			}

			// page hasn't loaded yet, so retrieve post variables based on event

			$post = get_post( $event );

			// get rsvp_to

			$custom_fields = get_post_custom( $post->ID );

			$rsvp_to = $custom_fields['_rsvp_to'][0];

			$rsvp_confirm = rsvp_get_confirm( $post->ID );

			// if permalinks are not turned on, we need to append to query string not add our own ?

			if ( ! is_admin() && isset( $custom_fields['_rsvp_captcha'][0] ) && $custom_fields['_rsvp_captcha'][0] ) {

				if ( ! isset( $_SESSION['captcha_key'] ) ) {

					session_start();
				}

				if ( $_SESSION['captcha_key'] != md5( $_POST['captcha'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'security code not entered correctly! Please try again.' ) );

					exit();

				}
			}

			if ( ! is_admin() && ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) ) {

				if ( ! rsvpmaker_recaptcha_check( $rsvp_options['rsvp_recaptcha_site_key'], $rsvp_options['rsvp_recaptcha_secret'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'failed recaptcha test' ) );

					exit();

				}
			}

			if ( isset( $_POST['required'] ) || empty( $rsvp['email'] ) ) {

				$required = explode( ',', $_POST['required'] );

				if ( ! in_array( 'email', $required ) ) {

					$required[] = 'email';
				}

				$missing = '';

				foreach ( $required as $r ) {
					$r = sanitize_text_field($r);
					if ( empty( $rsvp[ $r ] ) ) {

						$missing .= $r . ' ';
					}
				}

				if ( $missing != '' ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'missing required fields: ' . esc_attr( $missing ) ) );

					exit();

				}
			}

			if ( preg_match_all( '/http/', $_POST['note'], $matches ) > 2 ) {

				header( 'Location: ' . $req_uri . '&err=Invalid input' );

				exit();

			}

			if ( preg_match( '|//|', implode( ' ', $rsvp ) ) ) {

				header( 'Location: ' . $req_uri . '&err=Invalid input' );

				exit();

			}

			if ( isset( $rsvp['email'] ) ) {

				// assuming the form includes email, test to make sure it's a valid one

				if ( ! apply_filters( 'rsvmpmaker_spam_check', $rsvp['email'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'Invalid input.' ) );

					exit();

				}

				if ( ! filter_var( $rsvp['email'], FILTER_VALIDATE_EMAIL ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'Invalid email.' ) );

					exit();

				}
			}

			if ( isset( $_POST['onfile'] ) ) {

				$sql = $wpdb->prepare( "SELECT details FROM %s WHERE event=%d AND email LIKE %s AND first LIKE %s AND last LIKE %s  ORDER BY id DESC", $wpdb->prefix . "rsvpmaker", $event, $rsvp['email'], $rsvp['first'], $rsvp['last'] );

				$details = $wpdb->get_var( $sql );

				if ( $details ) {

					$contact = unserialize( $details );

				} else {
					$contact = rsvpmaker_profile_lookup( $rsvp['email'] );
				}

				if ( $contact ) {

					foreach ( $contact as $name => $value ) {

						if ( ! isset( $rsvp[ $name ] ) ) {

							$rsvp[ $name ] = $value;
						}
					}
				}
			}

			global $current_user; // if logged in

			$future = is_rsvpmaker_future( $event, 1 ); // if start time in the future (or within one hour)

			$yesno = ( $future ) ? 1 : 2;// 2 for replay

			$nv = array('first'=>$rsvp['first'], 'last'=>$rsvp['last'], 'email'=>$rsvp['email'], 'yesno' => $yesno, 'event'=>$event, 'note' => $note, 'details'=>serialize( $rsvp ), 'participants'=>1, 'user_id'=>$current_user->ID);

			rsvpmaker_capture_email( $rsvp );

			$rsvp_id = ( isset( $_POST['rsvp_id'] ) ) ? (int) $_POST['rsvp_id'] : 0;

			if ( $rsvp_id ) {
				$wpdb->update($wpdb->prefix . 'rsvpmaker',$nv,array('id'=>$rsvp_id));
			} else {
				$wpdb->insert($wpdb->prefix . 'rsvpmaker',$nv);
				$rsvp_id = $wpdb->insert_id;

				$sql = $wpdb->prepare("SELECT date FROM %i WHERE event=%d ",$wpdb->prefix . "rsvpmaker_event",$event);

				if ( empty( $wpdb->get_var( $sql ) ) ) {
					$wpdb->insert($wpdb->prefix . 'rsvpmaker_event',array('event' => $event, 'post_title' => $post->post_title, 'date'=>get_rsvp_date( $event )));
				}
			}
			if(!is_admin())
			setcookie( 'rsvp_for_' . $event, $rsvp_id, time() + ( 60 * 60 * 24 * 90 ), '/', sanitize_text_field($_SERVER['SERVER_NAME']) );

			if ( $future ) {

				$cleanmessage = '';

				foreach ( $rsvp as $name => $value ) {

					$label = get_post_meta( $event, 'rsvpform' . $name, true );

					if ( $label ) {

						$name = $label;
					}

					$cleanmessage .= $name . ': ' . $value . "\n";// labels from form

				}

				$subject = __( 'You registered for ', 'rsvpmaker' ) . ' ' . esc_html( $post->post_title );

				if ( ! empty( $_POST['note'] ) ) {

					$cleanmessage .= ' Note: ' . sanitize_textarea_field( stripslashes( $_POST['note'] ) );
				}

				rsvp_notifications( $rsvp, $rsvp_to, $subject, $cleanmessage, $rsvp_confirm );

			} else {

				// cron for follow up messages

				$sql = $wpdb-prepare("SELECT * 

FROM %i 

WHERE meta_key REGEXP '_rsvp_reminder_msg_[0-9]{1,2}'

AND  `post_id` = %d",$wpdb->postmeta,$event);

				$results = $wpdb->get_results( $sql );

				if ( $results ) {

					foreach ( $results as $row ) {

						$parts = explode( '_msg_', $row->meta_key );

						$hours = $parts[1];

						rsvpmaker_replay_cron( $event, $rsvp_id, $hours );

					}
				}
			}

			$landing_id = (int) $_POST['landing_id'];

			$passcode = get_post_meta( $landing_id, '_webinar_passcode', true );

			$landing_permalink = $req_uri . '&webinar=' . $passcode . '&e=' . $rsvp['email'];

			header( 'Location: ' . $landing_permalink );

			exit();

		}

	}


// Moved from rsvpmaker-util.php during cleanup
function save_rsvp($postdata, $live = true) {

		global $wpdb;

		global $rsvp_options;

		global $post;

		global $rsvp_id;

		global $rsvpdata;
		global $current_user; // if logged in

		global $rsvpmaker_coupon_message;

		$payment_details = '';

		$currency = ( empty( $rsvp_options['paypal_currency'] ) ) ? 'usd' : strtolower( $rsvp_options['paypal_currency'] );
		if ( $currency == 'usd' ) {
			$currency = '$';
		} elseif ( $currency == 'eur' ) {
			$currency = '€';
		}
		else {
			$currency = strtoupper($currency).' ';
		}

		$rsvp['fee_total'] = 0;

		$rsvp_id = ( isset( $postdata['rsvp_id'] ) ) ? (int) $postdata['rsvp_id'] : 0;

		$cleanmessage = '';

		if ( isset( $postdata['withdraw'] ) ) {

			foreach ( $postdata['withdraw'] as $withdraw_id ) {

				$wpdb->query( 'UPDATE ' . $wpdb->prefix . "rsvpmaker SET yesno=0 WHERE id=".intval($withdraw_id) );

			}
		}
		$test = ($live) ? wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) : true;
		if ( $test) {

			$postdata = stripslashes_deep( $postdata );

			// sanitize input

			foreach ( $postdata['profile'] as $name => $value ) {
				$rsvp[ $name ] = sanitize_text_field( $value );
			}
			if ( isset( $postdata['note'] ) ) {

				$note = sanitize_textarea_field( $postdata['note'] );

			} else {
				$note = '';
			}

			$yesno = (int) $postdata['yesno'];

			$answer = ( $yesno ) ? __( 'YES', 'rsvpmaker' ) : __( 'NO', 'rsvpmaker' );

			$event = ( ! empty( $postdata['event'] ) ) ? (int) $postdata['event'] : 0;

			if ( ! $event ) {

				return 'Event ID not set';
			}
			if(!get_post_meta($event,'_rsvp_on'))
				return;

			// page hasn't loaded yet, so retrieve post variables based on event

			$post = get_post( $event );

			// get rsvp_to

			$custom_fields = get_post_custom( $post->ID );

			$rsvp_to = empty($custom_fields['_rsvp_to'][0]) ? $rsvp_options['rsvp_to'] : $custom_fields['_rsvp_to'][0];

			$rsvp_confirm = rsvp_get_confirm( $post->ID );

			$rsvp_max = empty($custom_fields['_rsvp_max'][0]) ? 0 : $custom_fields['_rsvp_max'][0];

			$count = $wpdb->get_var( $wpdb->prepare("SELECT count(*) FROM %i WHERE event=%d AND yesno=1",$wpdb->prefix . "rsvpmaker",$event) );

			// if permalinks are not turned on, we need to append to query string not add our own ?

			$guest_sql = array();

			$guest_text = array();

			if ( is_admin() ) {

				$req_uri = admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&event=' . $event );

			} else {

				$req_uri = add_query_arg('e',$rsvp['email'],get_permalink($event));

			}

			if ( ! is_admin() && isset( $custom_fields['_rsvp_captcha'][0] ) && $custom_fields['_rsvp_captcha'][0] ) {

				if ( ! isset( $_SESSION['captcha_key'] ) ) {

					session_start();
				}

				if ( $_SESSION['captcha_key'] != md5( $postdata['captcha'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'security code not entered correctly! Please try again.' ) );

					exit();

				}
			}

			if ( ! is_admin() && ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) ) {

				if ( ! rsvpmaker_recaptcha_check( $rsvp_options['rsvp_recaptcha_site_key'], $rsvp_options['rsvp_recaptcha_secret'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'failed recaptcha test' ) );

					exit();

				}
			}

			if ( isset( $postdata['required'] ) || empty( $rsvp['email'] ) ) {
				if(isset( $postdata['required'] ))
				$required = explode( ',', $postdata['required'] );

				if ( ! in_array( 'email', $required ) ) {
					$required[] = 'email';
				}

				$missing = '';

				if(!empty($required))
				foreach ( $required as $r ) {
					$r = sanitize_text_field($r);
					if ( empty( $rsvp[ $r ] ) ) {
						$missing .= $r . ' ';
					}
				}

				if ( $missing != '' ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'missing required fields: ' . $missing ) );

					exit();

				}
			}

			if ( ! isset( $rsvp['first'] ) ) {

				$rsvp['first'] = '';
			}

			if ( ! isset( $rsvp['last'] ) ) {

				$rsvp['last'] = '';
			}
			if(!empty($postdata['coupon_code']))
				$rsvp['coupon_code'] = $postdata['coupon_code'];

			if ( isset( $postdata['note'] ) && preg_match_all( '/http/', $postdata['note'], $matches ) > 2 ) {

				header( 'Location: ' . $req_uri . '&err=Invalid input' );

				exit();

			}

			if ( preg_match( '|//|', implode( ' ', $rsvp ) ) ) {

				header( 'Location: ' . $req_uri . '&err=Invalid input' );

				exit();

			}

			if ( isset( $rsvp['email'] ) ) {

				// assuming the form includes email, test to make sure it's a valid one

				if ( ! apply_filters( 'rsvmpmaker_spam_check', $rsvp['email'] ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'Invalid input.' ) );

					exit();

				}

				if ( ! filter_var( $rsvp['email'], FILTER_VALIDATE_EMAIL ) ) {

					header( 'Location: ' . $req_uri . '&err=' . urlencode( 'Invalid email.' ) );

					exit();

				}
			}
			if ( empty( $rsvp_id ) ) {
				//fix for patchstack report
				$sql = $wpdb->prepare('SELECT id FROM ' . $wpdb->prefix . "rsvpmaker WHERE email=%s AND first=%s AND last=%s AND event=%d ", $rsvp['email'], $rsvp['first'], $rsvp['last'], $post->ID);
				$duplicate_check = $wpdb->get_var( $sql );

				if ( $duplicate_check ) {

					$rsvp_id = $duplicate_check;

				}
			}

			$owed = 0;
			$paid = 0;

			if ( $rsvp_id ) {

				$sql = $wpdb->prepare("SELECT * FROM %i WHERE email !='' AND id=%d",$wpdb->prefix .'rsvpmaker',$rsvp_id);
				$rsvp_row = $wpdb->get_row( $sql );
				$details =  empty($rsvp_row) ? '' : $rsvp_row->details;
				$paid = empty($rsvp_row) ? 0 : $rsvp_row->amountpaid;

				if ( $details ) {
					//patchstack fix
					$contact = unserialize( $details, array('allowed_classes' => false) );

					if ( is_array( $contact ) ) {

						foreach ( $contact as $name => $value ) {

							if ( ! isset( $rsvp[ $name ] ) ) {

								$rsvp[ $name ] = $value;
							}
						}
					}
				} else {
					$rsvp_id = null;
				}
			}

			$rsvp['payingfor'] = '';

			if ( isset( $postdata['payingfor'] ) && is_array( $postdata['payingfor'] ) ) {

				$rsvp['fee_total'] = 0;

				$participants = 0;

				foreach ( $postdata['payingfor'] as $index => $value ) {

					$value = (int) $value;

					$unit = sanitize_text_field( $postdata['unit'][ $index ] );

					$price = (float) $postdata['price'][ $index ];
					$cost = $value * $price;
					$rsvp['payingfor'] .= "<div>$value $unit @ " .$currency. number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency']."</div>\n";

					$rsvp['fee_total'] += $cost;

					$participants += $value;

				}
			}

			if ( isset( $postdata['timeslot'] ) && is_array( $postdata['timeslot'] ) ) {

				$participants = $rsvp['participants'] = (int) $postdata['participants'];

				$rsvp['timeslots'] = ''; // ignore anything retrieved from prev rsvps

				foreach ( $postdata['timeslot'] as $slot ) {

					if ( ! empty( $rsvp['timeslots'] ) ) {

						$rsvp['timeslots'] .= ', ';
					}

					$rsvp['timeslots'] .= rsvpmaker_date( 'g:i A', sanitize_text_field($slot) );

				}
			}

			if ( ! isset( $participants ) && $yesno ) {

				// if they didn't specify # of participants (paid tickets or volunteers), count the host plus guests

				$participants = 1;

				if ( ! empty( $postdata['guest']['first'] ) ) {

					foreach ( $postdata['guest']['first'] as $first ) {

						if ( $first ) {
							$participants++;
						}
					}
				}
				if ( isset( $postdata['guestdelete'] ) ) {
					$participants -= sizeof( $postdata['guestdelete'] );
				}
			}

			if ( ! $yesno ) {

				$participants = 0; // if they said no, they don't count
			}

			if(isset($postdata['full_price'])) {
				$price = floatval($postdata['full_price']);
				$rsvp['full_price'] = $price;
				$unit = 'Multi-event discount';
				$index = 0;
			} 
			elseif(isset($postdata['guest_count_price'])) {
				$index = (int) $postdata['guest_count_price'];
				$per = rsvp_get_pricing($event);
				$price = $per[ $index ]->price;
				$unit = $per[ $index ]->unit;
			}
			if ( $participants && !empty($price) ) {
				$cleanmessage .= '<div>' . __( 'Participants', 'rsvpmaker' ) . ": $participants</div>\n";
				$multiple = (!empty( $per[ $index ]) && isset( $per[ $index ]->price_multiple ) ) ? (int) $per[ $index ]->price_multiple : 1;

				if ( $multiple > 1 ) {
					$rsvp['fee_total'] = $price;
					if ( $participants > $multiple ) {
						$rsvp['payingfor'] .= '<div>'.$per[$index]->unit.'</div>';
						if($per[$index]->extra_guest_price) {
							$extra_guests = $participants - $multiple;
							$extra_fee = $per[$index]->extra_guest_price * $extra_guests;
							$rsvp['payingfor'] .= '<div>'.$extra_guests.' additional guests @ '.$per[$index]->extra_guest_price.'</div>';
							$rsvp['fee_total'] += $per[$index]->extra_guest_price * ($participants - $multiple);
						}
						else {
							$multiple_warning = '<div style="color:red;">' . "Warning: party of $participants exceeds reservation size" . '</div>';
							$rsvp['payingfor'] .= $multiple_warning;
						}
					} else {
						$padguests = $multiple - $participants;
						$participants = $multiple;
					}
				} else {
					$rsvp['fee_total'] = $price * $participants;
				}

				$rsvp['payingfor'] .= "<div>$participants $unit @ " .$currency. number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] )."</div>\n";

				$rsvp['pricechoice'] = $index;

			}

			if(!empty($rsvp['fee_total'])) {
				$pricewas = $rsvp['fee_total'];
			}
			if ( ! empty( $rsvpmaker_coupon_message ) ) {
				$rsvp['coupon'] = $rsvpmaker_coupon_message;
			}

			$item_pricing = rsvpmaker_item_pricing($post->ID);
			$itemlog = '';
			if(sizeof(((array) $item_pricing))) {
				$field_labels = rsvpmaker_form_field_labels($post->ID);
				$pricing_vars = get_object_vars($item_pricing);
				$item_fees = [];
				$item_count = [];
				foreach($pricing_vars as $index => $slugv) {
					$slugv = (array) $slugv;
					if(sizeof($slugv))
					foreach($slugv as $slugindex => $v) {
						$item_count[$index.':'.$slugindex] = 0;
					}
				}
				foreach($rsvp as $rsvpkey => $rsvpvalue) {
					$itemlog .= $rsvpkey . ' ' . $rsvpvalue.' '; 
					if(!empty($item_pricing->$rsvpkey))
						$itemlog .= ' pricing key match for '.$rsvpkey.' ';
					if(!empty($item_pricing->$rsvpkey) && !empty($item_pricing->$rsvpkey->$rsvpvalue))
						{
							$item_count[$rsvpkey.':'.$rsvpvalue] = (empty($item_count[$rsvpkey.':'.$rsvpvalue])) ? 1 : $item_count[$rsvpkey.':'.$rsvpvalue] + 1;
							$item_fees[$rsvpkey.':'.$rsvpvalue] = floatval($item_pricing->$rsvpkey->$rsvpvalue);
							$rsvp['fee_total'] += floatval($item_pricing->$rsvpkey->$rsvpvalue);
						}
				}
			}

			if ( isset( $postdata['guest'] ) ) {
				foreach($postdata['guest'] as $key => $gv) {
					foreach($gv as $index => $value) {
						if(empty($gvs[$index]))
							$gvs[$index] = array();
						$gvs[$index][$key] = $value;
					}
				}
			}

			if ( !empty($gvs) && !empty(sizeof((array) $item_pricing)) ) {
				foreach($gvs as $key => $gv) {
					foreach($gv as $rsvpkey => $rsvpvalue) {
						if(!empty($item_pricing->$rsvpkey) && !empty($item_pricing->$rsvpkey->$rsvpvalue))
						{
						$item_count[$rsvpkey.':'.$rsvpvalue] = (empty($item_count[$rsvpkey.':'.$rsvpvalue])) ? 1 : $item_count[$rsvpkey.':'.$rsvpvalue] + 1;
						$item_fees[$rsvpkey.':'.$rsvpvalue] = floatval($item_pricing->$rsvpkey->$rsvpvalue);
						$price_before = $rsvp['fee_total'];
						$rsvp['fee_total'] += floatval($item_pricing->$rsvpkey->$rsvpvalue);
						}
					}
				}
			}
			if(!empty($item_count) && sizeof($item_count)) {
				foreach($item_count as $rsvpkey => $count) {
					if($count) {
						$parts = explode(':',$rsvpkey);
						$label = (empty($field_labels[$parts[0]])) ? $parts[0] : $field_labels[$parts[0]];
						$rsvp['payingfor'] .= '<div>'.$label.':'.$parts[1]." $count @ ".$currency.$item_fees[$rsvpkey]."</div>";
					}
				}
			}

			if(!empty($rsvp['fee_total'])) {
				$pricewas = $rsvp['fee_total'];
				$rsvp['fee_total'] = rsvpmaker_check_coupon_code( $rsvp['fee_total'], $postdata, $participants );
				$owedwas = $rsvp['fee_total'] - $paid;
				$rsvp['amountpaid'] = $paid;
				$rsvp = rsvpmaker_gift_certificate($rsvp);
				$paid = $rsvp['amountpaid'];
				$owed = $rsvp['fee_total'] - $paid;
				if($pricewas != $rsvp['fee_total'])
					$rsvp['payingfor'] .= sprintf("<div>Before coupon %s%s</div><div>Coupon discount - %s%s</div>",$currency,number_format($pricewas,2),$currency,number_format($pricewas - $rsvp["fee_total"],2));
				if($owedwas != $owed)
					$rsvp['payingfor'] .= sprintf("<div>Total %s%s</div><div>Gift certificate applied - %s%s</div>",$currency,number_format($rsvp["fee_total"],2),$currency,number_format($owedwas - $owed,2));
				$rsvp['payingfor'] .= '<div class="payment_details_total"><strong>= ' .$currency. number_format( $rsvp['fee_total'], 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands']) . "</strong></div>\n";
			}

			$nv = array('first'=>$rsvp['first'], 'last'=>$rsvp['last'], 'email'=>$rsvp['email'], 'yesno' => $yesno, 'event'=>$event, 'note' => $note, 'details'=>serialize( $rsvp ), 'participants'=>1, 'user_id'=>$current_user->ID,'owed'=>$owed,'fee_total'=>$rsvp['fee_total']);
			if(!empty($postdata['multi_event_price'])) {
				$nv['amountpaid'] = floatval($postdata['multi_event_price']);
				$nv['owed'] = 0;
			}

			rsvpmaker_capture_email( $rsvp );

			if ( $rsvp_id ) {
				if($owed) {
					$snapshot = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE id=%d OR master_rsvp=%d ORDER BY master_rsvp, id",$wpdb->prefix . "rsvpmaker",$rsvp_id,$rsvp_id)); //get host, followed by guests
					if($snapshot && !intval($snapshot[0]->owed)) {
						update_post_meta($snapshot[0]->event,'_rsvp_snapshot_'.$snapshot[0]->id.'_'.$snapshot[0]->amountpaid,$snapshot);
					}
				}
				$wpdb->update($wpdb->prefix . 'rsvpmaker',$nv,array('id'=>$rsvp_id));
				$wpdb->show_errors();
			} else {

				$count++;

				if ( $rsvp_max && ( $count > $rsvp_max ) ) {

					$cleanmessage .= '<div style="color:red;">' . __( 'Max RSVP count limit reached, entry not added for:', 'rsvpmaker' ) . "\n" . $rsvp['first'] . ' ' . $rsvp['last'] . '</div>';

					$rsvp_id = 0;

				} else {
					$wpdb->insert($wpdb->prefix . 'rsvpmaker',$nv);
					$rsvp_id = $wpdb->insert_id;

					$sql = $wpdb->prepare("SELECT date FROM %i WHERE event=%d ",$wpdb->prefix . "rsvpmaker_event",$event);

					if ( empty( $wpdb->get_var( $sql ) ) ) {
						$wpdb->insert($wpdb->prefix . 'rsvpmaker_event',array('event'=>$event, 'post_title'=>$post->post_title, 'date'=> get_rsvp_date( $event )));
					}
				}
			}

			if (!is_admin() && ! empty( $rsvp_options['send_payment_reminders'] ) && isset( $price ) && ( $price > 0 ) ) {
				rsvpmaker_payment_reminder_cron( $rsvp_id );
			}
			if(!is_admin())
			{
				setcookie( 'rsvp_for_' . $post->ID, $rsvp_id, time() + 60 * 60 * 24 * 90, '/', sanitize_text_field($_SERVER['SERVER_NAME']) );
				setcookie( 'rsvpmaker', $rsvp_id, time() + 60 * 60 * 24 * 90, '/', sanitize_text_field($_SERVER['SERVER_NAME']) );	
			}
			if ( isset( $postdata['timeslot'] ) ) {

				$participants = (int) $postdata['participants'];

				// clear previous response, if any

				$wpdb->query( $wpdb->prepare("DELETE FROM %i WHERE rsvp=",$wpdb->prefix . "rsvp_volunteer_time",$rsvp_id));

				foreach ( $postdata['timeslot'] as $slot ) {

					$slot = (int) $slot;

					$sql = $wpdb->prepare( 'INSERT INTO ' . $wpdb->prefix . 'rsvp_volunteer_time SET time=%d, event=%d, rsvp=%d, participants=%d', $slot, $post->ID, $rsvp_id, $participants );

					$wpdb->query( $sql );

				}
			}

			// get start date

			$rows = get_rsvp_dates( $event );

			$row = $rows[0];

			$date = rsvpdate_shortcode( array( 'format' => '%b %e' ) );

			foreach ( $rsvp as $name => $value ) {

				$label = get_post_meta( $post->ID, 'rsvpform' . $name, true );

				if ( $label ) {

					$name = $label;
				}

				if ( ! empty( $value ) ) {

					$cleanmessage .= $name . ': ' . $value . "\n";// labels from rsvp form
				}
			}

			$guestof = (empty($rsvp['first'])) ? '' : $rsvp['first'] . ' ';
			$guestof .=  (empty($rsvp['last'])) ? '' : $rsvp['last'];
			$guestnv = array();

			if ( isset( $postdata['guest']['first'] ) ) {

				foreach ( $postdata['guest']['first'] as $index => $first ) {

					$last = ( !empty( $postdata['guest']['last']) && !empty($postdata['guest']['last'][ $index ]) ) ? sanitize_text_field($postdata['guest']['last'][ $index ]) : '';
					if ( ! empty( $first ) ) {						
						$guestnv[$index] = array('event'=>$event, 'yesno'=>$yesno, 'master_rsvp'=>$rsvp_id, 'guestof'=>$guestof, 'first' => $first, 'last' => $last,'details'=>serialize($gvs[$index]));
						$guest_text[ $index ] = sprintf( "Guest: %s %s\n", $first, $last );
						$guest_list[ $index ] = sprintf( '%s %s', $first, $last );
						$lastguest = $index;
					}
				}
			}

			if ( ! empty( $padguests ) ) {

				for ( $i = 0; $i < $padguests; $i++ ) {

					$index = $i + 100;

					$tbd = $i + 1;
					$guestnv[$index] = array('event'=>$event, 'yesno'=>$yesno, 'master_rsvp'=>$rsvp_id, 'guestof'=>$guestof, 'first' => 'Placeholder', 'last' =>$tbd);
					$guest_text[ $index ] = sprintf( "Guest: %s %s\n", 'Placeholder', 'Guest TBD ' . $tbd );

					$guest_list[ $index ] = sprintf( '%s %s', 'Placeholder', 'Guest TBD ' . $tbd );

					$newrow[ $index ]['first'] = 'Placeholder';

					$newrow[ $index ]['last'] = 'Guest TBD ' . $tbd;

				}
			}

			if ( sizeof( $guestnv ) ) {
				foreach ( $postdata['guest'] as $field => $column ) {
					foreach ( $column as $index => $value ) {
						if ( empty( $guest_text[ $index ] ) ) {
							$guest_text[ $index ] = '';
						}
						if ( isset( $guest_sql[ $index ] ) ) {
							$newrow[ $index ][ $field ] = $value;
							if ( ( $field != 'first' ) && ( $field != 'last' ) && ( $field != 'id' ) ) {
								$guest_text[ $index ] .= sprintf( "%s: %s\n", $field, $value );
								$guestlast = (empty($postdata['guest']['last'][ $index ])) ? '' : sanitize_text_field($postdata['guest']['last'][ $index ]);
								$guest_list[ $index ] = sprintf( '%s %s', $first, $guestlast );
							}
						}
					}
				}
			}

			$keep_guests = '';

			if ( sizeof( $guestnv ) ) {

				foreach ( $guestnv as $index => $nv ) {

					$id = ( isset( $postdata['guest']) && isset( $postdata['guest']['id']) && isset( $postdata['guest']['id'][ $index ] ) ) ? (int) $postdata['guest']['id'][ $index ] : 0;

					if ( $id ) {
						$keep_guests .= " AND id != $id";
						$wpdb->update($wpdb->prefix . 'rsvpmaker', $nv,array('id'=>$id));// $sql = 'UPDATE ' . $wpdb->prefix . 'rsvpmaker ' . $sql . ' WHERE id=' . $id;
					} else {
						$count++;
						if ( $rsvp_max && ( $count > $rsvp_max ) ) {
							$guest_text[ $index ] = '<div style="color:red;">' . __( 'Max RSVP count limit reached, entry not added for:', 'rsvpmaker' ) . "\n" . $guest_text[ $index ] . '</div>';
							$guest_list[ $index ] = '<div style="color:red;">' . __( 'Max RSVP count limit reached, entry not added for:', 'rsvpmaker' ) . "\n" . $guest_text[ $index ] . '</div>';
						} else {
							$guests_to_add[] = $nv;
						}
					}
				}
			}
			if($rsvp_id) {
				$missing_guests = "delete from ".$wpdb->prefix."rsvpmaker WHERE master_rsvp= ".intval($rsvp_id).$keep_guests;
				$wpdb->query($missing_guests);
			}

			if(!empty($guests_to_add)) {
				foreach($guests_to_add as $nv)
					$wpdb->insert($wpdb->prefix . 'rsvpmaker', $nv);
			}

			$guestparty = rsvpmaker_guestparty($rsvp_id);
			//fix
			if ( $guestparty ) {
				$cleanmessage .= $guestparty;
			}

			if ( ! empty( $multiple_warning ) ) {

				$cleanmessage .= $multiple_warning;
			}

			if ( ! is_admin() ) {

				if ( ! empty( $postdata['note'] ) ) {

					$cleanmessage .= ' Note: ' . stripslashes( $postdata['note'] );
				}

				$include_event = get_post_meta( $post->ID, '_rsvp_confirmation_include_event', true );

				if ( $include_event ) {

					$embed = rsvpmaker_event_to_embed( $post->ID, $post, 'confirmation' );

					$cleanmessage .= "\n\n" . $embed['content'];

				}

				$receipt_code = get_post_meta($post->ID,'rsvpmaker_receipt_'.$rsvp_id,true);
				if(!$receipt_code) {
					$receipt_code = wp_generate_password(20,false,false);
					update_post_meta($post->ID,'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
				}	
				$cleanmessage = sprintf('<p><a href="%s">%s</a></p>',add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($post->ID)),__('Print Receipt','rsvpmaker'))."\n".$cleanmessage;
				if($rsvp['fee_total']) {
					$url = get_permalink($post->ID);
					$url = add_query_arg('rsvp',$rsvp_id,$url);
					$url = add_query_arg('e',$rsvp['email'],$url);
					$cleanmessage = sprintf('<p>Payment link: <a href="%s" target="_blank">%s</a></p>'."\n",$url,$url).$cleanmessage;	
				}

				$rsvpdata['rsvpdetails'] = rsvpmaker_guestparty($rsvp_id,true);
				$rsvpdata['rsvpmessage'] = $rsvp_confirm; // confirmation message from editor

				$rsvpdata['rsvpyesno'] = $answer;
				$rsvpdata['yesno'] = $yesno;
				$rsvpdata['rsvpdate'] = $date;

				$rsvp_options['rsvplink'] = get_rsvp_link( $post->ID, false, $rsvp['email'], $rsvp_id );
				$rsvpdata['rsvpupdate'] = preg_replace( '/#rsvpnow">[^<]+/', '#rsvpnow">' . $rsvp_options['update_rsvp'],$rsvp_options['rsvplink']);

				if($live)
				rsvp_notifications_via_template( $rsvp, $rsvp_to, $rsvpdata );

			}

			do_action( 'rsvp_recorded', $rsvp );
			if($live) {
				header( 'Location: ' . $req_uri . '&rsvp=' . $rsvp_id .'&timelord='.rsvpmaker_nonce('value').'&ts='.time().'#rsvpmaker_top' );
				exit();	
			}
			else {
				return sprintf('<p><a href="%s">%s</a></p>',$req_uri . '&rsvp=' . $rsvp_id .'&ts='.time().'#rsvpmaker_top',$post->post_title);
			}
		}

	}


// Moved from rsvpmaker-util.php during cleanup
function rsvpmaker_basic_form( $form = '' ) {

		global $rsvp_options;

		global $post;

		if ( empty( $form ) ) {

			$form = get_post_meta( $post->ID, '_rsvp_form', true );
		}

		if ( empty( $form ) ) {
			$form = $rsvp_options['rsvp_form'];
		}

		if ( is_numeric( $form ) ) {
			$fpost = get_post( $form );
			if(empty($fpost) || empty($fpost->post_content) || !strpos($fpost->post_content,'rsvpmaker/'))
			{
				$form = upgrade_rsvpform(false,$form);
				$fpost = get_post($form);
			}
			echo do_blocks( $fpost->post_content );

		} else {
			echo do_shortcode( $form );
		}

	}


// Moved from rsvpmaker-util.php during cleanup
function rsvpguests( $atts ) {

		if ( is_admin() || wp_is_json_request() ) {

			return;
		}
		return rsvp_form_guests($atts);
	}


// Moved from rsvpmaker-util.php during cleanup
function rsvpprofiletable( $atts, $content = null ) {

		global $profile;

		if ( ! isset( $atts['show_if_empty'] ) || ! ( isset( $profile[ $atts['show_if_empty'] ] ) && $profile[ $atts['show_if_empty'] ] ) ) {

			return do_shortcode( $content );

		} else {

			$p = get_post_permalink();

			$p .= ( strpos( $p, '?' ) ) ? '&blank=1' : '?blank=1';

			return '

<p id="profiledetails">' . __( 'Profile details on file. To update profile, or RSVP for someone else', 'rsvpmaker' ) . ' <a href="' . $p . '">' . __( 'fetch a blank form', 'rsvpmaker' ) . '</a></p>

<input type="hidden" name="onfile" value="1" />';

		}

	}


// Moved from rsvpmaker-util.php during cleanup
function rsvpfield( $atts ) {

		global $profile;

		global $rsvp_required_field;

		global $guestextra;

		global $current_user;

		// synonyms

		if ( isset( $atts['text'] ) && ! isset( $atts['textfield'] ) ) {
			$atts['textfield'] = $atts['text'];
		}

		if ( isset( $atts['select'] ) && ! isset( $atts['selectfield'] ) ) {
			$atts['selectfield'] = $atts['select'];
		}

		if ( is_admin() && ! isset( $_REQUEST['edit_rsvp'] ) ) {

			$output = '';

			$guestfield = ( isset( $atts['guestfield'] ) ) ? (int) $atts['guestfield'] : 0;

			$guestoptions = array( __( 'main form', 'rsvpmaker' ), __( 'main+guest', 'rsvpmaker' ), __( 'guest form only', 'rsvpmaker' ) );

			$goptions = '';

			foreach ( $guestoptions as $index => $option ) {

				$s = ( $index == $guestfield ) ? ' selected="selected" ' : '';

				$goptions .= '<option value="' . $index . '" ' . $s . '>' . $option . '</option>';

			}

			$private = ( isset( $atts['private'] ) && $atts['private'] ) ? ' checked="checked" ' : '';

			if ( isset( $atts['textfield'] ) ) {

				$field = $atts['textfield'];

				if ( ( $field == 'email' ) || ( $field == 'first' ) || ( $field == 'last' ) ) {

					return;
				}

				if ( strpos( $field, 'hone' ) && empty( $atts['private'] ) ) {

					$private = ' checked="checked" ';
				}

				$label = ucfirst( str_replace( '_', ' ', $field ) );

				global $extrafield;

				$extrafield++;

				$output = '<select name="type' . $extrafield . '" id="type' . $extrafield . '"><option value="text" selected="selected">text</option><option value="hidden">hidden</option><option value="radio">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select> ' . __( 'Show', 'rsvpmaker' ) . ': <select id="guest' . $extrafield . '" name="guest' . $extrafield . '">' . $goptions . '</select>

<input type="checkbox" id="private' . $extrafield . '" name="private' . $extrafield . '" value="1" ' . $private . ' /> ' . __( 'private', 'rsvpmaker' ) . '

<br /><input type="text" name="extra' . $extrafield . '" id="extra' . $extrafield . '" value="' . esc_attr( $label ) . '"  class="text ui-widget-content ui-corner-all" />';

			}

			if ( isset( $atts['hidden'] ) ) {

				$field = $atts['hidden'];

				if ( ( $field == 'email' ) || ( $field == 'email' ) || ( $field == 'email' ) ) {

					return;
				}

				$label = ucfirst( str_replace( '_', ' ', $field ) );

				global $extrafield;

				$extrafield++;

				$output = '<select id="type' . $extrafield . '"><option value="text">text</option><option value="hidden" selected="selected">hidden</option><option value="radio">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select><input type="hidden" id="guest' . $extrafield . '" />

<input type="hidden" id="private' . $extrafield . '" name="private' . $extrafield . '" /> 

<br /><input type="text" id="extra' . $extrafield . '" value="' . esc_attr( $label ) . '"  class="text ui-widget-content ui-corner-all" />';

			}

			if ( isset( $atts['radio'] ) ) {

				$field = $atts['radio'];

				if ( ( $field == 'email' ) || ( $field == 'email' ) || ( $field == 'email' ) ) {

					return;
				}

				$label = ucfirst( str_replace( '_', ' ', $field ) );

				global $extrafield;

				$extrafield++;

				$output = '<select id="type' . $extrafield . '"><option value="text">text</option><option value="hidden">hidden</option><option value="radio"  selected="selected">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select> ' . __( 'Show', 'rsvpmaker' ) . ': <select id="guest' . $extrafield . '" name="guest' . $extrafield . '">' . $goptions . '</select>

<input type="checkbox" id="private' . $extrafield . '" name="private' . $extrafield . '" value="1" ' . $private . ' /> ' . __( 'private', 'rsvpmaker' ) . '

<br /><input type="text" id="extra' . $extrafield . '" value="' . esc_attr( $label ) . ':' . esc_attr( $atts['options'] ) . '"  class="text ui-widget-content ui-corner-all" />';

			}

			if ( isset( $atts['selectfield'] ) ) {

				$field = $atts['selectfield'];

				if ( ( $field == 'email' ) || ( $field == 'email' ) || ( $field == 'email' ) ) {

					return;
				}

				if ( strpos( $field, 'hone' ) && empty( $atts['private'] ) ) {

					$private = ' checked="checked" ';
				}

				$label = ucfirst( str_replace( '_', ' ', $field ) );

				global $extrafield;

				$extrafield++;

				$output = '<select id="type' . $extrafield . '"><option value="text">text</option><option value="hidden">hidden</option><option value="radio">radio</option><option value="select" selected="selected">select</option><option value="checkbox">checkbox</option></select> 

' . __( 'Show', 'rsvpmaker' ) . ': <select id="guest' . $extrafield . '" name="guest' . $extrafield . '">' . $goptions . '</select> <input type="checkbox" id="private' . $extrafield . '" name="private' . $extrafield . '" value="1" ' . $private . ' /> ' . __( 'private', 'rsvpmaker' ) . '		

<br /><input type="text" id="extra' . $extrafield . '" value="' . esc_attr( $label . ':' . $atts['options'] ) . '"  class="text ui-widget-content ui-corner-all" />';

			}

			return $output;

		}

		// front end behavior

		if ( isset( $atts['textfield'] ) ) {

			$field = str_replace( ' ', '_', $atts['textfield'] );// preg_replace('/[^a-zA-Z0-9_]/','_',$atts["textfield"]);

			$meta = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, $field, true ) : '';

			$profile[ $field ] = ( isset( $profile[ $field ] ) ) ? $profile[ $field ] : $meta;

			if ( ! is_admin() && ! empty( $profile[ $field ] ) && isset( $atts['private'] ) && $atts['private'] ) {

				$output = '<span  class="onfile ' . $field . '" >' . __( 'private data on file', 'rsvpmaker' ) . '</span>';

			} else {

				$size = ( isset( $atts['size'] ) ) ? ' size="' . $atts['size'] . '" ' : '';

				$data = ( isset( $profile[ $field ] ) ) ? ' value="' . $profile[ $field ] . '" ' : '';

				$output = '<input  class="' . $field . '" type="text" name="profile[' . $field . ']" id="' . $field . '" ' . $size . $data . ' />';

			}
		}

		if ( isset( $atts['hidden'] ) ) {

			$field = $atts['hidden'];

			$meta = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, $field, true ) : '';

			$profile[ $field ] = ( isset( $profile[ $field ] ) ) ? $profile[ $field ] : $meta;

			$size = ( isset( $atts['size'] ) ) ? ' size="' . $atts['size'] . '" ' : '';

			$data = ( isset( $profile[ $field ] ) ) ? ' value="' . $profile[ $field ] . '" ' : '';

			$output = '<input  class="' . $field . '" type="hidden" name="profile[' . $field . ']" id="' . $field . '" ' . $size . $data . ' />';

		} elseif ( isset( $atts['selectfield'] ) ) {

			$field = $atts['selectfield'];

			$meta = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, $field, true ) : '';

			$profile[ $field ] = ( isset( $profile[ $field ] ) ) ? $profile[ $field ] : $meta;

			if ( ! is_admin() && ! empty( $profile[ $field ] ) && isset( $atts['private'] ) && $atts['private'] ) {

				return '<span  class="onfile ' . $field . '" >' . __( 'private data on file', 'rsvpmaker' ) . '</span>';
			}

			$selected = ( isset( $atts['selected'] ) ) ? trim( $atts['selected'] ) : '';

			if ( ! empty( $profile[ $field ] ) ) {

				$selected = $profile[ $field ];
			}

			$output = '<span  class="' . $field . '"><select class="' . $field . '" name="profile[' . $field . ']" id="' . $field . '" >' . "\n";

			if ( isset( $atts['options'] ) ) {

				$o = explode( ',', $atts['options'] );

				foreach ( $o as $i ) {

					$i = trim( $i );

					$s = ( $selected == $i ) ? ' selected="selected" ' : '';

					$output .= '<option value="' . $i . '" ' . $s . '>' . $i . '</option>' . "\n";

				}
			}

			$output .= '</select></span>' . "\n";

		} elseif ( isset( $atts['checkbox'] ) ) {

			$field = $atts['checkbox'];

			$value = $atts['value'];

			$ischecked = ( isset( $atts['checked'] ) ) ? ' checked="checked" ' : '';

			$meta = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, $field, true ) : '';

			$profile[ $field ] = ( isset( $profile[ $field ] ) ) ? $profile[ $field ] : $meta;

			if ( ! empty( $profile[ $field ] ) && isset( $atts['private'] ) && $atts['private'] ) {

				return '<span  class="onfile ' . $field . '" >' . __( 'private data on file', 'rsvpmaker' ) . '</span>';
			}

			if ( isset( $profile[ $field ] ) ) {

				$ischecked = ' checked="checked" ';
			}

			$output = '<input class="' . $field . '" type="checkbox" name="profile[' . $field . ']" id="' . $field . '" value="' . $value . '" ' . $ischecked . '/>';

		} elseif ( isset( $atts['radio'] ) ) {

			$field = $atts['radio'];

			$meta = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, $field, true ) : '';

			$profile[ $field ] = ( isset( $profile[ $field ] ) ) ? $profile[ $field ] : $meta;

			if ( ! empty( $profile[ $field ] ) && isset( $atts['private'] ) && $atts['private'] ) {

				return '<span  class="onfile ' . $field . '" >' . __( 'private data on file', 'rsvpmaker' ) . '</span>';
			}

			$sep = ( isset( $atts['sep'] ) ) ? $atts['sep'] : ' ';

			$checked = ( isset( $atts['checked'] ) ) ? trim( $atts['checked'] ) : '';

			if ( isset( $profile[ $field ] ) ) {

				$checked = $profile[ $field ];
			}

			if ( isset( $atts['options'] ) ) {

				$o = explode( ',', $atts['options'] );

				$radio = array();

				foreach ( $o as $i ) {

					$i = trim( $i );

					$ischecked = ( $checked == $i ) ? ' checked="checked" ' : '';

					$radio[] = '<span  class="' . $field . '"><input class="' . $field . '" type="radio" name="profile[' . $field . ']" id="' . $field . $i . '" class="' . $field . '"  value="' . $i . '"  ' . $ischecked . '/> ' . $i . '</span> ';

				}
			}

			$output = implode( $sep, $radio );

		}

		if ( !empty( $atts['required'] ) || !empty( $atts['require'] ) ) {

			$output = '<span class="required">' . $output . '</span>';

			$rsvp_required_field[ $field ] = $field;

		}

		if ( isset( $atts['demo'] ) ) {

			$demo = "<div>Shortcode:</div>\n<p><strong>[</strong>rsvpfield";

			foreach ( $atts as $name => $value ) {

				if ( $name == 'demo' ) {

					continue;
				}

				$demo .= ' ' . $name . '="' . $value . '"';

			}

			$demo .= "<strong>]</strong></p>\n";

			$demo .= "<div>HTML:</div>\n<pre>" . htmlentities( $output ) . "</pre>\n";

			$demo .= "<div>Profile:</div>\n<pre>" . var_export( $profile, true ) . "</pre>\n";

			$demo .= "<div>Display:</div>\n<p>";

			$output = $demo . $output . '</p>';

		}

		if ( isset( $atts['guestfield'] ) && $atts['guestfield'] ) {

			$guestextra[ $field ] = $atts;

			if ( $atts['guestfield'] == 2 ) {

				return; // guest only don't display on main form
			}
		}

		if ( $field == 'email' ) {

			$output .= '<div id="rsvp_email_lookup"></div>';
		}

		return $output;

	}


// Moved from rsvpmaker-util.php during cleanup
function rsvpnote() {

	global $rsvp_row;

	return ( isset( $rsvp_row->note ) ) ? $rsvp_row->note : '';

}
