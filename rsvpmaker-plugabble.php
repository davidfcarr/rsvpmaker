<?php

// start customizable functions, can be overriden by adding a rsvpmaker-custom.php file to the plugins directory (one level up from rsvpmaker directory)

function my_events_menu() {

	global $rsvp_options;

	if ( function_exists( 'do_blocks' ) ) {

		return;
	}

	add_meta_box( 'EventDatesBox', __( 'Event Options', 'rsvpmaker' ), 'draw_eventdates', 'rsvpmaker', 'normal', 'high' );

	if ( isset( $rsvp_options['additional_editors'] ) && $rsvp_options['additional_editors'] ) {

		add_meta_box( 'ExtraEditorsBox', __( 'Additional Editors', 'rsvpmaker' ), 'additional_editors', 'rsvpmaker', 'normal', 'high' );
	}

}

	function draw_eventdates() {

		global $post;

		$post_id = ( isset( $_GET['post_id'] ) ) ? (int) $_GET['post_id'] : 0;
		$post    = ( $post_id ) ? get_post( $post_id ) : null;

		global $wpdb;

		global $rsvp_options;

		global $custom_fields;

		if ( isset( $_GET['debug'] ) ) {
			echo '<pre>';
			print_r( $post );
			echo '</pre>';
		}

		if ( isset( $_GET['clone'] ) ) {
			$id            = (int) $_GET['clone'];
			$custom_fields = get_rsvpmaker_custom( $id );
		} elseif ( isset( $post->ID ) ) {

			$custom_fields = get_rsvpmaker_custom( $post->ID );
		}

		if ( isset( $custom_fields['_rsvpmaker_special'][0] ) ) {

			$rsvpmaker_special = $custom_fields['_rsvpmaker_special'][0];

			if ( $rsvpmaker_special == 'Landing Page' ) {

				?>

<p>This is a landing page for an RSVPMaker webinar.</p>

<p><input type="radio" name="_require_webinar_passcode" value="<?php echo esc_attr( $custom_fields['_webinar_passcode'][0] ); ?>" 
																		  <?php
																			if ( isset( $custom_fields['_require_webinar_passcode'][0] ) && $custom_fields['_require_webinar_passcode'][0] ) {
																				echo 'checked="checked"';}
																			?>
	 > Passcode required to view webinar</p>

<p><input type="radio" name="_require_webinar_passcode" value="0" 
				<?php
				if ( ! isset( $custom_fields['_require_webinar_passcode'][0] ) || ! $custom_fields['_require_webinar_passcode'][0] ) {
					echo 'checked="checked"';}
				?>
	> No passcode required</p>

				<?php

			} else {
				do_action( 'rsvpmaker_special_metabox', $rsvpmaker_special );
			}

			return 'special';

		} elseif ( ( isset( $post->ID ) && rsvpmaker_is_template( $post->ID ) ) || isset( $_GET['new_template'] ) ) {

			?>

<p><em><strong><?php esc_html_e( 'Event Template', 'rsvpmaker' ); ?>:</strong> <?php esc_html_e( 'This form is for entering generic / boilerplate information, not specific details for an event on a specific date. Groups that meet on a monthly basis can post their standard meeting schedule, location, and contact details to make entering the individual events easier. You can also post multiple future meetings using the generic template and update those event listings as needed when the event date grows closer.', 'rsvpmaker' ); ?></em></p>

			<?php

			$template = get_template_sked( $post_id );

			template_schedule( $template );

			 rsvp_time_options( $post->ID );

			return;

		}

		if ( isset( $custom_fields['_meet_recur'][0] ) ) {

			$t = (int) $custom_fields['_meet_recur'][0];

			if ( $post_id ) {

				printf(
					'<p><a href="%s">%s</a> | <a href="%s">%s</a> | <a href="%s">%s</a> | <a href="%s">%s</a></p>',
					admin_url( 'post.php?action=edit&post=' . $t ),
					__( 'Edit Template Content', 'rsvpmaker' ),
					admin_url( 'post.php?action=edit&tab=basics&post=' . $t ),
					__( 'Edit Template Options', 'rsvpmaker' ),
					admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $t ),
					__( 'See Related Events', 'rsvpmaker' ),
					admin_url(
						'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&apply_target=' . intval( $post->ID ) . '&apply_current=' . $t . '#applytemplate

'
					),
					__( 'Switch Template', 'rsvpmaker' )
				);
			}
		} elseif ( isset( $post->ID ) ) {

			printf(
				'<p><a href="%s">%s</a></p>',
				admin_url(
					'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&apply_target=' . intval( $post->ID ) . '#applytemplate

'
				),
				__( 'Apply Template', 'rsvpmaker' )
			);
		}

		if(isset($post->ID))
		$event = get_rsvpmaker_event( $post->ID );
		$start = 0;

		if ( !empty($event) ) {
			$t = intval($event->ts_start);
			$end = intval($event->ts_end);
			echo "\n<div class=\"event_dates\"> \n";

			if ( $rsvp_options['long_date'] ) {
				echo mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );
			}

			$dur = $event->display_type;

			if ( ( $dur != 'allday' ) && ! strpos( $dur, '|' ) ) {

				echo rsvpmaker_date( ' ' . $rsvp_options['time_format'], $t );

			} elseif ( ( $dur == 'set' ) && $row['end_time'] ) {

				echo ' to ' . rsvpmaker_date( $rsvp_options['time_format'], $end );
			}
			rsvpmaker_date_option_event( $t, $end, $dur );

			echo "</div>\n";
			return;
		} else {

			echo '<p><em>' . __( 'You can enter dates and times in either text format or the numeric/database format.', 'rsvpmaker' ) . '</em> </p>';

			$t = time();

		}

		if ( isset( $_GET['t'] ) ) {

			$t = (int) $_GET['t'];

			$sked = get_template_sked( $t );

			$times = rsvpmaker_get_projected( $sked );

			foreach ( $times as $ts ) {

				if ( $ts > time() ) {

					break;
				}
			}

			rsvpmaker_date_option( $ts, 0, rsvpmaker_date( 'Y-m-d H:i:s', $ts ), $sked );

			$start = 1;

		} elseif ( $start == 0 ) {

			$start = 1;

			$date = ( isset( $_GET['add_date'] ) ) ? sanitize_text_field($_GET['add_date']) : 'today ' . $rsvp_options['defaulthour'] . ':' . $rsvp_options['defaultmin'] . ':00';

			rsvpmaker_date_option( $date, 0, rsvpmaker_date( 'Y-m-d H:i:s', $t ) );

		}

		if ( ! isset( $_GET['t'] ) ) { // if this is based on a template, use the template defaults

			rsvp_time_options( $post_id );
		}

		if ( isset( $_GET['debug'] ) ) {

			echo '<pre>';

			// print_r($custom_fields);

			echo '</pre>';

		}

	}
// end draw event dates

function template_schedule( $template ) {
		global $post;
		$sked = get_template_sked( $post->ID );
		$occur = array('Varies','First','Second','Third','Fourth','Last','Every');
		$schedule = '';
		$day = 'tomorrow';
		foreach($sked as $index => $value) {
			if(in_array($index,$occur) && $value)
				$schedule .= ' '.$index;
			if(strpos($index,'day') && $value) {
				if($day == 'tomorrow')
					$day = $index;
				$schedule .= ' '.$index;
			}
		}
		echo '<p><em>Template: '.$schedule.' (set in editor)</em></p>';
?>
<p><?php esc_html_e( 'Stop date (optional)', 'rsvpmaker' ); ?>: <input type="text" name="sked[stop]" value="<?php
				if ( isset( $template['stop'] ) ) {
					echo esc_attr($template['stop']);
				}
				?>" placeholder="<?php
		esc_html_e( 'example', 'rsvpmaker' );
		echo ': ' . date( 'Y' ) . '-12-31';
		?>" /> <em>(<?php esc_html_e( 'format', 'rsvpmaker' ); ?>: "YYYY-mm-dd" or "+6 month" or "+1 year")</em></p>
		<?php
		$auto = ( ( isset( $_GET['new_template'] ) && ! empty( $rsvp_options['autorenew'] ) ) || get_post_meta( $post->ID, 'rsvpautorenew', true ) );
		?>
<p><input type="checkbox" name="rsvpautorenew" id="rsvpautorenew" 
		<?php
		if ( $auto ) {
			echo 'checked="checked"';}
		?>
 /> <?php esc_html_e( 'Automatically add dates according to schedule', 'rsvpmaker' ); ?></em></p>
<?php
}
// end template schedule

function rsvpmaker_sanitize_array_vars($array) {
	if(!is_array($array))
		return false;
	foreach($array as $index => $var) {
		if(is_array($var))
			$var = array_map('sanitize_text_field',$var);
		else
			$var = sanitize_text_field($var);
		$array[$index] = $var;
	}
	return $array;
}

function save_rsvp_template_meta( $post_id ) {

	if ( ! isset( $_POST['sked'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

		return;
	}

	// we only care about saving template data

	global $wpdb;

	global $post;

	global $current_user;

	if ( $parent_id = wp_is_post_revision( $post_id ) ) {

		$post_id = $parent_id;

	}

	$sked = array_map('rsvpmaker_sanitize_array_vars',$_POST['sked']);

	if ( $sked['time'] ) {
			$p               = explode( ':', $sked['time'] );
			$sked['hour']    = $p[0];
			$sked['minutes'] = $p[1];
	}

	if ( empty( $sked['dayofweek'] ) ) {
		$sked['dayofweek'][0] = 9;
	}

	$sked['duration'] = $sked['end_time_type'] = sanitize_text_field( $_POST['end_time_type'] );
	if(isset($_POST['rsvp_sql_end']))
	$sked['end']      = $sked['rsvp_sql_end'] = sanitize_text_field( $_POST['rsvp_sql_end'] );

	new_template_schedule( $post_id, $sked );

	if ( isset( $_POST['rsvpautorenew'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		update_post_meta( $post_id, 'rsvpautorenew', 1 );
	} else {
		delete_post_meta( $post_id, 'rsvpautorenew' );
	}
}

function rsvpmaker_roles() {

		// by default, capabilities for events are the same as for blog posts

		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {

			$wp_roles = new WP_Roles();
		}

		// subscribers should not be able to edit
		$wp_roles->remove_cap( 'subscriber', 'edit_rsvpmakers' );

		// if roles persist from previous session, return
		if ( ! empty( $wp_roles->roles['administrator']['capabilities']['edit_rsvpmakers'] ) ) {
			return;
		}

		if ( is_array( $wp_roles->roles ) ) {

			foreach ( $wp_roles->roles as $role => $rolearray ) {

				foreach ( $rolearray['capabilities'] as $cap => $flag ) {

					if ( strpos( $cap, 'post' ) ) {
						$fbcap = str_replace( 'post', 'rsvpmaker', $cap );
						$wp_roles->add_cap( $role, $fbcap );
					}
				}
			}
		}

	}

function get_confirmation_options( $post_id = 0, $documents = array() ) {

	global $post;

	if ( isset( $post->ID ) ) {

		$post_id = $post->ID;
	}

	$output = '';

	$confirm = rsvp_get_confirm( $post_id, true );

	$output = sprintf( '<h3 id="confirmation">%s</h3>', __( 'Confirmation Message', 'rsvpmaker' ) );

	$output .= $confirm->post_content;

	foreach ( $documents as $d ) {

		$id = $d['id'];

		if ( ( $id == 'edit_confirm' ) || ( $id == 'customize_confirmation' ) ) {

			$output .= sprintf( '<p><a href="%s">Edit: %s</a></p>', esc_attr( $d['href'] ), esc_html( $d['title'] ) );
		}
	}

	if ( ( empty( $_GET['page'] ) || $_GET['page'] != 'rsvp_reminders' ) ) {
		$output              .= sprintf( '<p><a href="%s" target="_blank">Create / Edit Reminders</a></p>', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=' . $post_id ) );
		$payment_confirmation = (int) get_post_meta( $post_id, 'payment_confirmation_message', true );
		if ( empty( $payment_confirmation ) || empty( get_post( $payment_confirmation ) ) ) {
				$add_payment_conf = admin_url( 'edit.php?title=Payment%20Confirmation&post_type=rsvpmaker&page=rsvpmaker_details&rsvpcz=payment_confirmation_message&post_id=' . $post_id );
				$output          .= sprintf( '<p><a href="%s">%s</a></p>', $add_payment_conf, __( 'Add Payment Confirmation Message' ) );
		} else {
			$output .= sprintf( '<p><a href="%s">%s</a></p>', admin_url( 'post.php?post=' . $payment_confirmation . '&action=edit' ), __( 'Edit Payment Confirmation Message' ) );
		}
	}

	$output = '<div style="max-width: 800px">' . $output . '</div>';

	return $output;

}

function ajax_rsvp_email_lookup( $email, $event ) {

	$p = get_permalink( $event );

	if ( ! rsvpmail_contains_email( $email ) ) {

		return;
	}

	global $wpdb;

	$wpdb->show_errors();

	$sql = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE email LIKE %s AND event=%d', $email, $event );

	$results = $wpdb->get_results( $sql );

	if ( $results ) {

		$out = '<div class="previous_rsvp_prompt">' . __( 'Did you RSVP previously?', 'rsvpmaker' ) . '</div>';

		foreach ( $results as $row ) {

			$out .= 'RSVP ';

			$out .= ( $row->yesno ) ? __( 'YES', 'rsvpmaker' ) : __( 'NO', 'rsvpmaker' );

			$out .= esc_html( ' ' . $row->first . ' ' . $row->last );

			$sql = $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'rsvpmaker WHERE master_rsvp=%d', intval( $row->id ) );

			$guests = $wpdb->get_var( $sql );

			if ( $guests ) {
				$out .= ' + ' . esc_html( $guests ) . ' ' . __( 'guests', 'rsvpmaker' );
			}

			return sprintf(
				'<div><a href="%s">%s</a> %s</div>',
				add_query_arg(
					array(
						'e'      => $row->email,
						'update' => $row->id,
					),
					$p
				).'#rsvpnow',
				__( 'Click to Update', 'rsvpmaker' ),
				$out
			);

		}
	} else {
		return;
	}

}

function rsvp_form_setup_form( $rsvp_form ) {

	$hidden = ( strpos( $rsvp_form, 'hidden="email"' ) );

	$email_list_ok = ( strpos( $rsvp_form, 'checkbox="email_list_ok"' ) );

	preg_match( '/textfield="([^"]+)"/', $rsvp_form, $match );

	$emailfirst = ( $match[1] == 'email' ) ? ' checked="checked" ' : '';

	?>

<div id="rsvp-dialog-form" title="Form setup">

  <p><?php esc_html_e( 'First Name, Last Name, Email (required)', 'rsvpmaker' ); ?> Display options: <select id="name_email_hidden" name="name_email_hidden">

	  <option value="email_first" 
	  <?php
		if ( $emailfirst ) {
			echo 'selected="selected"';}
		?>
			 ><?php esc_html_e( 'email, then name', 'rsvpmaker' ); ?></option>

	  <option value="name_first" 
	  <?php
		if ( ! $emailfirst && ! $hidden ) {
			echo 'selected="selected"';}
		?>
			 ><?php esc_html_e( 'name, then email', 'rsvpmaker' ); ?></option>

	  <option value="hidden" 
	  <?php
		if ( $hidden ) {
			echo 'selected="selected"';}
		?>
			 ><?php esc_html_e( 'hidden (use with login required)', 'rsvpmaker' ); ?></option>

	  </select>

<br /><?php esc_html_e( 'For radio buttons or select fields, use the format Label:option 1, option 2', 'rsvpmaker' ); ?> (<em><?php esc_html_e( 'Meal:Steak,Chicken,Vegitarian', 'rsvpmaker' ); ?></em>)</p> 

	<fieldset>

	<?php

	preg_match_all( '/(\[.+\])/', $rsvp_form, $matches );

	preg_match( '/max_party="(\d+")/', $rsvp_form, $maxparty );

	$codes = implode( $matches[1] );

	$codes .= '[rsvpfield textfield=""][rsvpfield textfield=""][rsvpfield textfield=""]';

	echo do_shortcode( $codes );

	global $extrafield;

	printf( '<input type="hidden" id="extrafields" value="%s" />', $extrafield );

	$mp = ( empty( $maxparty[1] ) ) ? '' : $maxparty[1] - 1;

	?>

<p><input type="checkbox" name="guests" id="guests" value="1" 
	<?php
	if ( strpos( $rsvp_form, 'rsvpguests' ) ) {
		echo 'checked="checked"';}
	?>
	 /> <?php esc_html_e( 'Include guest form', 'rsvpmaker' ); ?> - <?php esc_html_e( 'up to', 'rsvpmaker' ); ?> <input type="text" name="maxguests" id="maxguests" value="<?php echo esc_attr($mp); ?>" size="2" /> <?php esc_html_e( ' guests (enter # or leave blank for no limit)', 'rsvpmaker' ); ?><br /> <input type="checkbox" name="note" id="note" value="1" 
	<?php
	if ( strpos( $rsvp_form, 'rsvpnote' ) ) {
		echo 'checked="checked"';}
	?>
	> <?php esc_html_e( 'Include notes field', 'rsvpmaker' ); ?> <input type="checkbox" name="emailcheckbox" id="emailcheckbox" value="1" 
	<?php
	if ( $email_list_ok ) {
		echo 'checked="checked"';}
	?>
	 > <?php esc_html_e( 'Include "Add me to email list" checkbox', 'rsvpmaker' ); ?></p>

<p><input type="checkbox" name="guests" id="guests" value="1" 
	<?php
	if ( strpos( $rsvp_form, 'rsvpguests' ) ) {
		echo 'checked="checked"';}
	?>
	 /> <?php esc_html_e( 'Include guest form', 'rsvpmaker' ); ?> - <?php esc_html_e( 'up to', 'rsvpmaker' ); ?> <input type="text" name="maxguests" id="maxguests" value="<?php echo esc_attr($mp); ?>" size="2" /> <?php esc_html_e( ' guests (enter # or leave blank for no limit)', 'rsvpmaker' ); ?><br /> <input type="checkbox" name="note" id="note" value="1" 
	<?php
	if ( strpos( $rsvp_form, 'rsvpnote' ) ) {
		echo 'checked="checked"';}
	?>
	> <?php esc_html_e( 'Include notes field', 'rsvpmaker' ); ?> <input type="checkbox" name="emailcheckbox" id="emailcheckbox" value="1" 
	<?php
	if ( $email_list_ok ) {
		echo 'checked="checked"';}
	?>
	 > <?php esc_html_e( 'Include "Add me to email list" checkbox', 'rsvpmaker' ); ?></p>

	  <!-- Allow form submission with keyboard without duplicating the dialog button -->

	  <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">

	</fieldset>

</div> 

	<?php

}
function rsvpmaker_capture_email( $rsvp ) {
	// placeholder function, may be overriden to sign person up for email list
	// or use this action, triggered by email_list_ok parameter in form
	if ( isset( $rsvp['email_list_ok'] ) && $rsvp['email_list_ok'] ) {
		do_action( 'rsvpmaker_email_list_okay', $rsvp );
	}
}

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

				$sql = $wpdb->prepare( 'SELECT details FROM ' . $wpdb->prefix . "rsvpmaker WHERE event='$event' AND email LIKE %s AND first LIKE %s AND last LIKE %s  ORDER BY id DESC", $rsvp['email'], $rsvp['first'], $rsvp['last'] );

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

				$sql = 'SELECT date FROM ' . $wpdb->prefix . "rsvpmaker_event WHERE event=$event ";

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

				$sql = "SELECT * 

FROM  `$wpdb->postmeta` 

WHERE meta_key REGEXP '_rsvp_reminder_msg_[0-9]{1,2}'

AND  `post_id` = " . $event;

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
// end save replay rsvp

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

			$count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$event AND yesno=1" );

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

				$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE email !='' AND id=" . $rsvp_id;
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
					$snapshot = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix . "rsvpmaker WHERE id=$rsvp_id OR master_rsvp=$rsvp_id ORDER BY master_rsvp, id"); //get host, followed by guests
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

					$sql = 'SELECT date FROM ' . $wpdb->prefix . "rsvpmaker_event WHERE event=$event ";

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

				$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "rsvp_volunteer_time WHERE rsvp=$rsvp_id" );

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

			$missing_guests = '';

			if ( sizeof( $guestnv ) ) {

				foreach ( $guestnv as $index => $nv ) {

					$id = ( isset( $postdata['guest']) && isset( $postdata['guest']['id']) && isset( $postdata['guest']['id'][ $index ] ) ) ? (int) $postdata['guest']['id'][ $index ] : 0;

					if ( isset( $postdata['guestdelete'][ $id ] ) ) {

						$gd = (int) $postdata['guestdelete'][ $id ];

						$sql = 'DELETE FROM ' . $wpdb->prefix . 'rsvpmaker WHERE id=' . $gd;

						$guest_text[ $index ] = __( 'Deleted:', 'rsvpmaker' ) . "\n" . $guest_text[ $index ];

						$guest_list[ $index ] = __( 'Deleted:', 'rsvpmaker' ) . ' ' . $guest_list[ $index ];

						$wpdb->query( $sql );

					} elseif ( $id ) {
						$missing_guests .= " AND id != $id";
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

			$missing_guests = "delete from ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=$rsvp_id ".$missing_guests;
			$wpdb->query($missing_guests);

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

					$embed = event_to_embed( $post->ID, $post, 'confirmation' );

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
// end save rsvp

	function rsvp_notifications( $rsvp, $rsvp_to, $subject, $message, $rsvp_confirm = '' ) {

		include_once 'rsvpmaker-ical.php';

		global $post;

		$message = wpautop( $message );

		$mail['html'] = $rsvp_confirm . "\n\n" . $message;

		global $rsvp_options;

		$mail['to'] = $rsvp_to;

		$mail['from'] = $rsvp['email'];

		$mail['fromname'] = $rsvp['first'] . ' ' . $rsvp['last'];

		$mail['subject'] = $subject;

		rsvpmaker_tx_email( $post, $mail );

		if ( isset( $post->ID ) ) { // not for replay
			$mail['ical'] = rsvpmaker_to_ical_email( $post->ID, $rsvp_to, $rsvp['email'] );
			$event_title = get_the_title($post->ID);
			$dateblock = rsvp_date_block_email( $post->ID );
			$mail['html'] = '<h1>'.esc_html($event_title).'</h1>'."\n".$dateblock."\n".$mail['html'];	
		}

		$mail['to'] = $rsvp['email'];

		$mail['from'] = $rsvp_to;

		$mail['fromname'] = get_bloginfo( 'name' );

		$mail['subject'] = 'Confirming ' . $subject;

		rsvpmaker_tx_email( $post, $mail );

	}
// end rsvp notifications

function rsvp_admin_payment( $rsvp_id, $amount_paid = 0 ) {
	global $wpdb;
	global $current_user;
	$row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix . "rsvpmaker WHERE id=$rsvp_id ");
	$owed = $row->owed - $amount_paid;
	$amount_paid += $row->amountpaid;
	$sql = 'UPDATE ' . $wpdb->prefix . "rsvpmaker SET owed=$owed, amountpaid='".$amount_paid."' WHERE id=$rsvp_id ";
	$wpdb->query( $sql );
	$row = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE id=$rsvp_id ", ARRAY_A );
}

function rsvpmaker_localdate() {

	if ( empty( $_REQUEST['action'] ) || $_REQUEST['action'] != 'rsvpmaker_localstring' ) {

		return;
	}
	if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
		return;

	$output = '';

	global $rsvp_options;

	if ( ! empty( $_REQUEST['localstring'] ) ) {

		preg_match( '/(.+:00 ).+\(([^)]+)/', sanitize_text_field($_REQUEST['localstring']), $matches );

		$tf = str_replace( 'T', '', $rsvp_options['time_format'] );

		$t = rsvpmaker_strtotime( $matches[1] );

		$output = rsvpmaker_date( $rsvp_options['long_date'], $t ) . ' ' . rsvpmaker_date( $tf, $t ) . ' ' . $matches[2];

	}

	echo wp_kses_post($output);

	wp_die();

}
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

// global variable for content
$confirmed_content = '';

function event_content( $content, $formonly = false, $form = '' ) {
	global $wpdb, $post, $rsvp_options, $profile, $master_rsvp, $showbutton, $blanks_allowed, $email_context, $confirmed_content;
	$payment_details = '';
	if ( is_admin() && !isset($_GET['page']) ) { // || !in_the_loop()

		return $content;
	}

	// If the post is not an event, leave it alone

	if (empty($post->post_type) || ( $post->post_type != 'rsvpmaker' ) && ( $post->post_type != 'rsvpmaker_template' )) {
		return $content;
	}
	$currency = ( empty( $rsvp_options['paypal_currency'] ) ) ? 'usd' : strtolower( $rsvp_options['paypal_currency'] );
	if ( $currency == 'usd' ) {
		$currency = '$';
	} elseif ( $currency == 'eur' ) {
		$currency = '€';
	}
	else {
		$currency = strtoupper($currency).' ';
	}

	$rsvpconfirm = $rsvp_confirm = '';

	$display = array();

	if ( post_password_required( $post ) ) {

		return $content;

	}

	global $custom_fields; // make this globally accessible

	$custom_fields = get_rsvpmaker_custom( $post->ID );

	$content = apply_filters( 'rsvpmaker_event_content_top', $content, $custom_fields );

	// if requiring passcode, check code (unless RSVP cookie is set)

	if ( isset( $custom_fields['_require_webinar_passcode'][0] ) && $custom_fields['_require_webinar_passcode'][0] && ! isset( $_COOKIE[ 'rsvp_for_' . $post->ID ] ) ) {

		$event_id = $custom_fields['_require_webinar_passcode'][0];

		if ( ! isset( $_GET['webinar'] ) ) {

			return rsvpmaker_replay_form( $custom_fields['_webinar_event_id'][0] );
		}

		$code = sanitize_text_field($_GET['webinar']);

		$required = $custom_fields['_require_webinar_passcode'][0];

		if ( $required != trim( $code ) ) {

			return rsvpmaker_replay_form( $custom_fields['_webinar_event_id'][0] );
		}
	}

	$permalink = get_permalink( $post->ID );

	if ( isset( $custom_fields['_rsvp_on'][0] ) ) {

		$rsvp_on = $custom_fields['_rsvp_on'][0];
	}

	if ( isset( $custom_fields['_rsvp_login_required'][0] ) ) {
		$login_required = $custom_fields['_rsvp_login_required'][0];
	}

	if ( isset( $custom_fields['_rsvp_to'][0] ) ) {

		$rsvp_to = $custom_fields['_rsvp_to'][0];
	}

	if ( isset( $custom_fields['_rsvp_max'][0] ) ) {

		$rsvp_max = $custom_fields['_rsvp_max'][0];
	}

	$rsvp_count = ( isset( $custom_fields['_rsvp_count'][0] ) && $custom_fields['_rsvp_count'][0] ) ? 1 : 0;

	$rsvp_show_attendees = ( isset( $custom_fields['_rsvp_show_attendees'][0] ) && $custom_fields['_rsvp_show_attendees'][0] ) ? $custom_fields['_rsvp_show_attendees'][0] : 0;

	if ( isset( $custom_fields['_rsvp_deadline'][0] ) && $custom_fields['_rsvp_deadline'][0] ) {

		$deadline = (int) $custom_fields['_rsvp_deadline'][0];
	}

	$rsvpstart = ( isset( $custom_fields['_rsvp_start'][0] ) && $custom_fields['_rsvp_start'][0] ) ? (int) $custom_fields['_rsvp_start'][0] : 0;
	$rsvpstart = apply_filters('rsvpstart',$rsvpstart);

	$rsvp_instructions = ( isset( $custom_fields['_rsvp_instructions'][0] ) ) ? $custom_fields['_rsvp_instructions'][0] : null;

	$rsvp_yesno = ( isset( $custom_fields['_rsvp_yesno'][0] ) ) ? $custom_fields['_rsvp_yesno'][0] : 1;

	$replay = ( isset( $custom_fields['_replay'][0] ) ) ? $custom_fields['_replay'][0] : null;

	$first = ( isset( $_GET['first'] ) ) ? sanitize_text_field($_GET['first']) : null;

	$last = ( isset( $_GET['last'] ) ) ? sanitize_text_field($_GET['last']) : null;

	$rsvprow = null;

	$e = get_rsvp_email();

	$rsvp_id = get_rsvp_id( $e );
	if ( $rsvp_id && $e ) {
		$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE id=$rsvp_id and email='$e'";
		$rsvprow = $wpdb->get_row( $sql, ARRAY_A );

		if(!$rsvprow) {
			$unp = rsvpmaker_check_unpaid($post->ID,$rsvp_id);
			$party_size = (is_array($unp)) ? sizeof($unp) : 0;
			//restore unpaid record as long as there is still room
			if($party_size && rsvpmaker_check_openings($post->ID, $party_size)) {
				foreach($unp as $restore) {
					$restore = (array) $restore;
					$wpdb->replace($wpdb->prefix.'rsvpmaker',$restore);
				}
				$rsvprow = (array) $unp[0];
				$time = rsvpmaker_strtotime('+10 minutes');
				wp_clear_scheduled_hook( 'rsvp_payment_reminder',array($rsvp_id) );
				wp_schedule_single_event($time,'rsvp_payment_reminder',array($rsvp_id));			
			}	
		}

		if($rsvprow)
			$profile = rsvp_row_to_profile($rsvprow);
	}
	else {
		$profile = rsvpmaker_profile_lookup( $e );
	}

	if ( $profile ) {

		$first = $profile['first'];

		$last = $profile['last'];

	}

	if ( isset( $_GET['rsvp'] ) && rsvpmaker_verify_nonce()) {

		$rsvp_confirm = rsvp_get_confirm( $post->ID );

		$rsvp_confirm .= "\n\n" . wpautop( get_post_meta( $post->ID, '_rsvp_' . $e, true ) );

		$rsvpconfirm = $rsvp_confirm;
	$rsvp_id = intval($_GET['rsvp']);
	$rsvpconfirm .= rsvpmaker_guestparty($rsvp_id,true);

	} elseif ( isset( $_COOKIE[ 'rsvp_for_' . $post->ID ] ) && ! $email_context && is_single() ) {

		$rsvp_confirm = rsvp_get_confirm( $post->ID );

		if ( $rsvprow ) {

			$permalink .= ( strpos( $permalink, '?' ) ) ? '&' : '?';

			$rsvpconfirm = '

<h4>' . $rsvp_options['update_rsvp'] . '?</h4>	

<p><a href="' . esc_url_raw( $permalink . 'update=' . $rsvp_id . '&e=' . $rsvprow['email'] ) .'&t='.time(). '#rsvpnow">' . __( 'Yes', 'rsvpmaker' ) . '</a>, ' . __( 'I want to update this record for ', 'rsvpmaker' ) . esc_html( $rsvprow['first'] . ' ' . $rsvprow['last'] ) . '</p>
<p>Or <a href="' . esc_url_raw( $permalink . 'new='.$post->ID.'&t='.time()). '#rsvpnow">' . __( 'New Entry', 'rsvpmaker' ) . '</a></p>

';

		}
	}

	$nomatch = false;

	if ( ( ( $e && isset( $_GET['rsvp'] ) ) || ( is_user_logged_in() && ! $email_context ) ) ) {

		if ( $rsvprow && is_single() ) {
			$master_rsvp = $rsvprow['id'];

			$rsvpwithdraw = sprintf( '<div><input type="checkbox" checked="checked" name="withdraw[]" value="%d"> %s %s</div>', esc_attr( $rsvprow['id'] ), esc_html( $rsvprow['first'] ), esc_html( $rsvprow['last'] ) );

			$answer = ( $rsvprow['yesno'] ) ? __( 'Yes', 'rsvpmaker' ) : __( 'No', 'rsvpmaker' );

			$rsvpconfirm .= '<div class="rsvpdetails"><p>' . __( 'Your RSVP', 'rsvpmaker' ) . ": $answer</p>\n";

			$profile = $details = rsvp_row_to_profile( $rsvprow );
			if ( isset( $details['fee_total'] ) && $details['fee_total'] ) {

				$payment_details = '';

				$invoice_id = (int) get_post_meta( $post->ID, '_open_invoice_' . $rsvp_id, true );

				$paid = floatval($details['amountpaid']);

				$charge = floatval($details['owed']);

				$price_display = ( $charge == $details['fee_total'] ) ? $details['fee_total'] : $details['fee_total'] . ' - ' . $paid . ' = ' . $charge;

				if ( $invoice_id ) {

					update_post_meta( $post->ID, '_invoice_' . $rsvp_id, $charge );

				} else {

					$invoice_id = 'rsvp' . add_post_meta( $post->ID, '_invoice_' . $rsvp_id, $charge );

					add_post_meta( $post->ID, '_open_invoice_' . $rsvp_id, $invoice_id );

				}

				$payment_details .= '<div class="payment_details"><strong>' . __( 'Event Fees', 'rsvpmaker' ) . '</strong> <div class="payment_details_itemized">' . wp_kses_post( $details['payingfor'] ) . '</div></div>';

				if ( $charge != $details['fee_total'] ) {

					$payment_details .= '<p><strong>' . __( 'Previously Paid', 'rsvpmaker' ) . ' ' . number_format( $paid, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'] . '</strong></p>';

					$payment_details .= '<p><strong>' . __( 'Balance Owed', 'rsvpmaker' ) . ' ' . number_format( $charge, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'] . '</strong></p>';

				}

				if ( $charge > 0 ) {

					$gateway = get_rsvpmaker_payment_gateway();
					if($gateway && $gateway != 'Cash or Custom')
						$payment_details = '<p>To complete your registration, please pay now.</p><div id="rsvp_payment_details_prompt">'.$payment_details.'</div>';

					$currency = get_post_meta($post->ID,'_rsvp_currency',true);
					if(empty($currency))
						$currency = $rsvp_options['paypal_currency'];

					if ( $gateway == 'Stripe' ) {
						$rsvprow['amount'] = $charge;
						$rsvprow['rsvp_id'] = $rsvp_id;
						$rsvprow['currency'] = strtolower($currency);
						$payment_details .= rsvpmaker_to_stripe( $rsvprow );
					} 
					elseif ( $gateway == 'Both Stripe and PayPal' ) {
						$rsvprow['amount'] = $charge;
						$rsvprow['rsvp_id'] = $rsvp_id;
						$rsvprow['currency'] = strtolower($currency);
						$payment_details .= rsvpmaker_to_stripe( $rsvprow );
						$payment_details .= '<p>'. __('Credit card processing by Stripe','rsvpmaker').'</p>';
						$payment_details .= '<p>'. __('Or pay with PayPal','rsvpmaker').'</p>';
						$payment_details .= rsvpmaker_paypal_button( $charge, $currency, $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID,'is_gift_certificate' => !empty($profile['is_gift_certificate'])) );
					} 
					elseif ( $gateway == 'Stripe via WP Simple Pay' ) {

						$payment_details .= '<p>' . do_shortcode( '[stripe amount="' . ( $charge * 100 ) . '" description="' . htmlentities( $post->post_title ) . ' ' . esc_html( $details['payingfor'] ) . '" ]' ) . '</p>';

					} elseif ( $gateway == 'Cash or Custom' ) {

						ob_start();

						do_action( 'rsvpmaker_cash_or_custom', $charge, $invoice_id, $rsvp_id, $details, $profile, $post );

						$payment_details .= ob_get_clean();

					} elseif ( $gateway == 'PayPal REST API' ) {
						$payment_details .= rsvpmaker_paypal_button( $charge, $currency, $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID, 'is_gift_certificate' => !empty($profile['is_gift_certificate'])) );

					}					
				}
			}

			if ( ! isset( $_GET['rsvp'] ) ) {

				$guestsql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE master_rsvp=' . $rsvprow['id'];

				if ( $results = $wpdb->get_results( $guestsql, ARRAY_A ) ) {

					$rsvpconfirm .= '<p>' . __( 'Guests', 'rsvpmaker' ) . ':</p>';

					foreach ( $results as $row ) {

						$rsvpconfirm .= $row['first'] . ' ' . $row['last'] . '<br />';

						$rsvpwithdraw .= sprintf( '<div><input type="checkbox" checked="checked" name="withdraw[]" value="%d"> %s %s</div>', $row['id'], esc_html( $row['first'] ), esc_html( $row['last'] ) );

					}
				}
			}

			$rsvpconfirm .= "</p></div>\n";

		}
		else
			$nomatch = true;
	} elseif ( $e && isset( $_GET['update'] ) ) {

		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE ' . $wpdb->prepare( 'event=%d AND email=%s AND id=%d', $post->ID, $e, intval($_GET['update']) );

		$rsvprow = $wpdb->get_row( $sql, ARRAY_A );

		if ( $rsvprow ) {
			$master_rsvp = $rsvprow['id'];
			$answer = ( $rsvprow['yesno'] ) ? __( 'Yes', 'rsvpmaker' ) : __( 'No', 'rsvpmaker' );
			$guestprofile = $details = rsvp_row_to_profile( $rsvprow );
		}
		else
			$nomatch = true;
	}

	if('rsvpmaker_template' == $post->post_type)
		$dateblock = rsvpmaker_format_event_dates( $post->ID, true ); //'<p><em>Template Preview</em></p>';
	else {
	$dateblock = ( strpos( $post->post_content, 'rsvpdateblock]' ) || strpos( $post->post_content, 'wp:rsvpmaker/rsvpdateblock' ) ) ? '' : rsvpmaker_format_event_dates( $post->ID );
	$event     = get_rsvpmaker_event( $post->ID );
	if ( $event ) {
		$dur       = isset($event->display_type) ? $event->display_type : '';
		$last_time = (int) $event->ts_end;
		$firstrow  = $event->date;
	} else {
		$dur = $last_time = $firstrow = '';
	}
	}

	if($nomatch && (!empty($_GET['rsvp']) || !empty($_GET['update'])) ) {
		$rsvpconfirm = '<div style="border: thin solid #000; padding: 20px;font-size: large;">'.__('RSVP Record expired or not found.','rsvpmaker').'</div>';
	}
	elseif ( ! empty( $rsvpconfirm ) || !empty($payment_details) ) {
		$rsvpconfirm = '<div id="rsvpconfirm"><h3>' . esc_html( __( 'Registration Saved', 'rsvpmaker' ) ) . "</h3>\n" . $payment_details . $rsvpconfirm . '</div>';
	}
	// $content = '<div>'.$content;

	if ( ! $formonly && ! empty( $dateblock ) ) {
		$content = $dateblock . $content;
	}

	if ( ! empty( $rsvpconfirm ) ) {

		$content = $rsvpconfirm . $content;
	}

	if ( isset( $_GET['rsvp'] ) ) {

		// don't repeat form

		$link = get_permalink();
		//fix for patchstack report
		$args = array(
			'e'      => sanitize_text_field($_GET['e']),
			'update' => sanitize_text_field($_GET['rsvp']),
		);

		$link = add_query_arg( $args, $link );

		$content .= sprintf( '<p><a href="%s#rsvpnow">%s</a>', esc_url_raw( $link ), esc_html( $rsvp_options['update_rsvp'] ) );

		$confirmed_content[ $post->ID ] = $content;

		return $content;

	}

	$showbutton = apply_filters( 'rsvpmaker_showbutton', $showbutton );

	if ( isset( $rsvp_on ) && $rsvp_on ) {

		// check for responses so far

		$sql = 'SELECT first,last,note FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$post->ID AND yesno=1 ORDER BY id DESC";

		$attendees = $wpdb->get_results( $sql );

		$total = sizeof( $attendees ); // (int) $wpdb->get_var($sql);

		if ( isset( $rsvp_max ) && $rsvp_max ) {

			$blanks_allowed = ( $total + 1 ) - $rsvp_max;

			if ( $total >= $rsvp_max ) {

				$too_many = true;
			}

			$blanks_allowed = $rsvp_max - ( $total );

			if ( ! isset( $answer ) ) {

				$blanks_allowed--;
			}
		} else {
			$blanks_allowed = 10000000;
		}

		if ( $rsvp_count ) {

			$content .= '<div class="signed_up_ajax" id="signed_up_' . esc_attr( $post->ID ) . '" post="' . esc_attr( $post->ID ) . '"></div>';

		}

		$now = time();

		$rsvplink = get_rsvp_link( $post->ID, true );
		if ( !is_rsvpmaker_deadline_future( $post->ID ) ) {
			$content .= '<p class="rsvp_status">' . __( 'RSVP deadline is past', 'rsvpmaker' ) . '</p>';
		} 
		elseif ( isset( $rsvpstart ) && ( $now < $rsvpstart ) ) {

			$content .= '<p class="rsvp_status">' . esc_html( __( 'RSVPs accepted starting: ', 'rsvpmaker' ) . mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $rsvpstart  ), 'UTF-8' ) ) . '</p>';

		} elseif ( isset( $too_many ) ) {

			$content .= '<p class="rsvp_status">' . esc_html( __( 'RSVPs are closed', 'rsvpmaker' ) ) . '</p>';
			if ( isset( $rsvpwithdraw ) ) {

				$content .= sprintf( '<h3>%s</h3><form method="post" action="%s">%s<p><button>%s</button></p>%s</form>', esc_html( __( 'To cancel, check the attendee names to be removed', 'rsvpmaker' ) ), esc_url_raw( $rsvplink ), esc_url_raw( $rsvpwithdraw ), __( 'Cancel RSVP', 'rsvpmaker' ), rsvpmaker_nonce('return') );

			}
		}
		elseif ( ( $rsvp_on && is_admin() && isset( $_GET['page'] ) && ( $_GET['page'] != 'rsvp' ) ) || ( $rsvp_on && is_email_context() ) || ( $rsvp_on && isset( $_GET['load'] ) ) ) { // when loaded into editor
			if(!strpos($post->post_content,'rsvplink') && !strpos($post->post_content,'rsvpmaker/button'))//if button not already displayed
				$content .= sprintf( $rsvp_options['rsvplink'], $rsvplink );
		} elseif ( $rsvp_on && $login_required && ! is_user_logged_in() ) { // show button, coded to require login
			if(!strpos($post->post_content,'rsvplink') && !strpos($post->post_content,'rsvpmaker/button'))//if button not already displayed
				$content .= sprintf( $rsvp_options['rsvplink'], wp_login_url($rsvplink) );
		} elseif ( $rsvp_on && ! is_admin() && ! $formonly && ( ! is_single() || $showbutton ) ) { // show button
			if(!strpos($post->post_content,'rsvplink') && !strpos($post->post_content,'rsvpmaker/button'))//if button not already displayed
				$content .= sprintf( $rsvp_options['rsvplink'], $rsvplink );
		} elseif ( $rsvp_on && ( is_single() || is_admin() || $formonly ) ) {

			ob_start();
			echo '<div id="rsvpsection">';
			?>

<form id="rsvpform" action="<?php echo esc_url_raw( $permalink ); ?>" method="post">
<h3 id="rsvpnow"><?php echo esc_html( $rsvp_options['rsvp_form_title'] ); ?></h3> 

			<?php

			if ( get_post_meta( $post->ID, '_rsvp_form_show_date', true ) ) {
				echo '<div class="date_on_form">'.rsvpmaker_format_event_dates( $post->ID ).'</div>';
			}

			if ( $rsvp_instructions ) {
				echo '<p>' . wp_kses_post( nl2br( $rsvp_instructions ) ) . '</p>';
			}

			if ( $rsvp_show_attendees ) {

				  echo '<p class="rsvp_status">' . __( 'Names of attendees will be displayed publicly, along with the contents of the notes field.', 'rsvpmaker' ) . '</p>';

				if ( $rsvp_show_attendees == 2 ) {

					echo ' (' . __( 'only for logged in users', 'rsvpmaker' ) . ')';
				}

				echo '</p>';

			}

			if ( $rsvp_yesno ) {
				echo '<p>' . __( 'Your Answer', 'rsvpmaker' );
				?>
				: <input name="yesno" class="radio_buttons" type="radio" value="1" 
				<?php
				if ( ! isset( $rsvprow ) || $rsvprow['yesno'] ) {
					echo 'checked="checked"';
				}
				?>
/> <?php echo __( 'Yes', 'rsvpmaker' ); ?> <input name="yesno" type="radio"  class="radio_buttons" value="0" 
				<?php
				if ( isset( $rsvprow['yesno'] ) && ( $rsvprow['yesno'] == 0 ) ) {
					echo 'checked="checked"';
				}
				?>
/> 
				<?php
				echo __( 'No', 'rsvpmaker' ) . '</p>';
			} else {
				echo '<input name="yesno" type="hidden" value="1" />';
			}

			rsvphoney_ui();

			if ( !empty($dur) && ( $slotlength = ! empty( $custom_fields['_rsvp_timeslots'][0] ) ) ) {

				?>

<div><?php echo __( 'Number of Participants', 'rsvpmaker' ); ?>: <select name="participants">

<option value="1">1</option>

<option value="2">2</option>

<option value="3">3</option>

<option value="4">4</option>

<option value="5">5</option>

<option value="6">6</option>

<option value="7">7</option>

<option value="8">8</option>

<option value="9">9</option>

<option value="10">10</option>

</select></div>
<div><?php echo __( 'Choose timeslots', 'rsvpmaker' ); ?></div>

				<?php

				$t = rsvpmaker_strtotime( $firstrow['datetime'] );

				$dur = $firstrow['duration'];

				if ( strpos( $dur, ':' ) ) {

					$dur = rsvpmaker_strtotime( $dur );
				}

				$day = rsvpmaker_date( 'j', $t );

				$month = date( 'n', $t );

				$year = date( 'Y', $t );

				$hour = rsvpmaker_date( 'G', $t );

				$minutes = rsvpmaker_date( 'i', $t );

				$slotlength = explode( ':', $slotlength );

				$min_add = $slotlength[0] * 60;

				$min_add = ( empty( $slotlength[1] ) ) ? $min_add : ( $min_add + $slotlength[1] );

				for ( $i = 0; ( $slot = rsvpmaker_mktime( $hour, $minutes + ( $i * $min_add ), 0, $month, $day, $year ) ) < $dur; $i++ ) {

					$sql = 'SELECT SUM(participants) FROM ' . $wpdb->prefix . "rsvp_volunteer_time WHERE time=$slot AND event = $post->ID";

					$signups = ( $signups = $wpdb->get_var( $sql ) ) ? $signups : 0;

					echo '<div><input type="checkbox" name="timeslot[]" value="' . $slot . '" /> ' . rsvpmaker_date( ' ' . $rsvp_options['time_format'], $slot ) . " $signups participants signed up</div>";

				}
			}

			$pricing = rsvp_get_pricing($post->ID);
			if ($pricing && ($pricing[0]->price != '0.00')) {

				$pf = '';

				$options = '';

				foreach ( $pricing as $index => $price_row ) {

					if ( empty($price_row->price ) ) { // no price = $0 where no other price is specified
						continue;
					}

					$price = (float) $price_row->price;

					$deadstring = '';

					if ( ! empty( $price_row->price_deadline ) ) {

						$deadline = (int) $price_row->price_deadline;

						if ( time() > $deadline ) {

							continue;

						} else {
							if(!empty($price_row->niceDeadline))
								$deadstring = ' (' . __( 'until', 'rsvpmaker' ) . ' ' . $price_row->niceDeadline . ')';
						}
					}

					$display[ $index ] = $price_row->unit . ' @ ' . ( ( $rsvp_options['paypal_currency'] == 'USD' ) ? '$' : $rsvp_options['paypal_currency'] ) . ' ' . number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . $deadstring;

				}

				if ( isset( $custom_fields['_rsvp_count_party'][0] ) && $custom_fields['_rsvp_count_party'][0] ) {

					$number_prices = sizeof( $display );

					if ( $number_prices ) {

						if ( $number_prices == 1 ) { // don't show options, just one choice

							foreach ( $display as $index => $value ) {
								printf( '<h3 id="guest_count_pricing"><input type="hidden" name="guest_count_price" value="%s">%s</h3>', $index, esc_html( $value ) );
							}
						} else {

							foreach ( $display as $index => $value ) {

								$s = ( isset( $profile['pricechoice'] ) && ( $index == $profile['pricechoice'] ) ) ? ' selected="selected" ' : '';

								$options .= sprintf( '<option value="%d" %s>%s</option>', $index, $s, esc_html( $value ) );

							}

							printf( '<div id="guest_count_pricing"><label>' . __( 'Pricing Options', 'rsvpmaker' ) . ':</label> <select name="guest_count_price"  id="guest_count_price">%s</select></div>', $options );

						}
					}
				} else {

					if ( sizeof( $display ) ) {

						foreach ( $display as $index => $value ) {

							if ( empty( $pricing ) ) {

								continue;
							}

							if(!empty($pricing[$index])) {
								$price = (float) $pricing[ $index ]->price;
								$unit = $pricing[ $index ]->unit;
								$pf .= '<div class="paying_for_tickets"><select name="payingfor[' . $index . ']" class="tickets"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="unit[' . $index . ']" value="' . esc_attr( $unit ) . '" />' . esc_html( $value ) . '<input type="hidden" name="price[' . $index . ']" value="' . esc_attr( $price ) . '" /></div>' . "\n";	
							} 

						}
					}

					if ( ! empty( $pf ) ) {

						echo '<h3>' . __( 'Paying For', 'rsvpmaker' ) . '</h3><p>' . $pf . "</p>\n";
					}
				}

			}

			rsvpmaker_basic_form( $form );
				// coupon code

			if ( ! empty( $custom_fields['_rsvp_coupon_code'][0] ) ) {
				$code = (empty($profile['coupon_code'])) ? '' : esc_attr($profile['coupon_code']);
				echo '<p id="coupon_field"><label>Coupon Code:</label> <input type="text" name="coupon_code" size="10" value="'.$code.'" /></p><p id="coupon_field_prompt"><a href="#coupon_field" id="coupon_field_add">'.__('Add coupon code','rsvpmaker').'</a></p>';
			}

			if ( isset( $custom_fields['_rsvp_captcha'][0] ) && $custom_fields['_rsvp_captcha'][0] ) {

				?>

<p>          <img src="<?php echo plugins_url( '/captcha/captcha_ttf.php', __FILE__ ); ?>" alt="CAPTCHA image">

<br />

				<?php esc_html_e( 'Type the hidden security message', 'rsvpmaker' ); ?>:<br />                    

<input maxlength="10" size="10" name="captcha" type="text" autocomplete="off" />

</p>

				<?php

				do_action( 'rsvpmaker_after_captcha' );

			}
			global $rsvp_required_field;
			$rsvp_required_field['email'] = 'email';// at a minimum			

			rsvpmaker_recaptcha_output();
			if ( isset( $rsvp_options['privacy_confirmation'] ) && ( $rsvp_options['privacy_confirmation'] == '1' ) ) {

				echo '<p><input type="checkbox" name="profile[privacy_consent]" id="privacy_consent" value="1" /> ' . wp_kses_post( $rsvp_options['privacy_confirmation_message'] ) . '</p>';

				if ( ! in_array( 'privacy_consent', $rsvp_required_field ) ) {

					$rsvp_required_field[] = 'privacy_consent';
				}
			}

			if ( isset( $rsvp_required_field ) ) {

				echo '<div id="jqerror"></div><input type="hidden" name="required" id="required" value="' . esc_attr( implode( ',', $rsvp_required_field ) ) . '" />';
			}

			?>

	<p> 

	  <input type="submit" id="rsvpsubmit" style="<?php echo esc_attr(get_rsvp_submit_button_css()); ?>" name="Submit" value="<?php esc_html_e( 'Submit', 'rsvpmaker' ); ?>" /> 

	</p> 

<input type="hidden" name="rsvp_id" id="rsvp_id" value="
			<?php
			if ( isset( $profile['id'] ) ) {
				echo esc_attr( $profile['id'] );}
			?>
" /><input type="hidden" name="event" id="event" value="<?php echo esc_attr( $post->ID ); ?>" /><?php rsvpmaker_nonce(); ?>

</form>	

</div>

			<?php
			$content .= ob_get_clean();
		}

		if ( isset( $_GET['err'] ) ) {

			$error = sanitize_text_field($_GET['err']);

			$content = '<div id="rsvpconfirm" >

<h3 class="rsvperror">' . __( 'Error', 'rsvpmaker' ) . '<br />' . esc_attr( $error ) . '</h3>

<p>' . __( 'Please correct your submission.', 'rsvpmaker' ) . '</p>

</div>

' . $content;

		}

		if ( ( ( $rsvp_show_attendees == 1 ) || ( ( $rsvp_show_attendees == 2 ) && is_user_logged_in() ) ) && $total && ! isset( $_GET['load'] ) && ! isset( $_POST['profile'] ) ) {

			// use api

			$content .= '<p><button class="rsvpmaker_show_attendees" post_id="' . esc_attr( $post->ID ) . '" >' . __( 'Show Attendees', 'rsvpmaker' ) . '</button></p>

<div id="attendees-' . esc_attr( $post->ID ) . '"></div>';

		}
	} // end if($rsvp_on)

	$terms = get_the_term_list( $post->ID, 'rsvpmaker-type', '', ', ', ' ' );

	if ( $terms && is_string( $terms ) ) {

		$content .= '<p class="rsvpmeta">' . __( 'Event Types', 'rsvpmaker' ) . ': ' . $terms . '</p>';
	}

	$content = apply_filters( 'rsvpmaker_event_content_bottom', $content, $custom_fields );

	return $content;

}

function rsvp_report_shortcode( $atts ) {

	if ( ! isset( $atts['public'] ) || ( $atts['public'] == '0' ) ) {

		if ( ! is_user_logged_in() ) {

			return sprintf( /* translators: login link */    __( 'You must <a href="%s">login</a> to view this.', 'rsvpmaker' ), login_redirect( $_SERVER['REQUEST_URI'] ) );
		}
	}

	global $post;

	$permalink = get_permalink( $post->ID );

	$permalink .= ( strpos( $permalink, '?' ) ) ? '&rsvp_print=1&' . rsvpmaker_nonce('query') : '?rsvp_print=1&' . rsvpmaker_nonce('query');

	ob_start();

	rsvp_report();

	$report = ob_get_clean();

	return str_replace( admin_url( 'edit.php?post_type=rsvpmaker&page=rsvp' ), $permalink, $report );

}


	function rsvp_csv() {

		if ( ! isset( $_GET['rsvp_csv'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) { 
			die( 'Security error' );
		}

		global $wpdb;
		$fields  = array_map('sanitize_text_field',$_GET['fields']);
		if(isset($_GET['allcontacts'])) {
			$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE master_rsvp=0 ORDER BY timestamp DESC";
			$name = 'all-contacts';
		}
		else {
			$eventid = (int) $_GET['event'];
			$post    = get_post( $eventid );	
			$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, master_rsvp, last, first";
			$name = $post->post_name;
		}

		header( 'Content-Type: text/csv' );

		header( 'Content-Disposition: attachment;filename="' . $name . '-' . date( 'Y-m-d-H-i' ) . '.csv"' );

		header( 'Cache-Control: max-age=0' );

		$out = fopen( 'php://output', 'w' );

		fputcsv( $out, $fields );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$rows = sizeof( $results );
		if(!empty($phonecol))
		$phonecells = $phonecol . '1:' . $phonecol . ( $rows + 1 );

		if ( is_array( $results ) ) {

			foreach ( $results as $row ) {

				//$index++;

				$row['yesno'] = ( $row['yesno'] ) ? 'YES' : 'NO';

				if ( $row['details'] ) {

					$details = unserialize( $row['details'] );
					$row = array_merge( $row, $details );

				}
				$newrow = array();

				if ( is_array( $fields ) ) {

					foreach ( $fields as $column => $name ) {
						if ( isset( $row[ $name ] ) ) {
							$newrow[] = strip_tags($row[ $name ]);

						} else {
							$newrow[] = '';
						}
					}
				}

				fputcsv( $out, $newrow );

			}
		}

		fclose( $out );

		exit();

	} // end rsvp_csv

function rsvp_report_api () {
	global $wpdb;
	$events_table = $wpdb->prefix.'rsvpmaker_event';
	$rsvp_table = $wpdb->prefix.'rsvpmaker';
	$sql = "select $events_table.post_title, $events_table.date, $rsvp_table.first, $rsvp_table.last, $rsvp_table.email, $rsvp_table.fee_total, $rsvp_table.amountpaid, $rsvp_table.owed, $rsvp_table.details, $rsvp_table.note, $rsvp_table.guestof, $rsvp_table.master_rsvp from $events_table JOIN $rsvp_table ON $events_table.event = $rsvp_table.event WHERE yesno && enddate > NOW() order by ts_start, master_rsvp, first, last";
	$results = $wpdb->get_results($sql, ARRAY_A);
	$eventrows = [];
	$fields = ['first','last','email','fee_total','amountpaid','owed'];
	foreach($results as $row) {
		if ( $row['details'] ) {
			$details = unserialize( $row['details'] );
			$row = array_merge( $row, $details );
		}
		unset($row['id']);
		unset($row['yesno']);
		unset($row['user_id']);
		unset($row['details']);
		unset($row['ts_start']);
		unset($row['participants']);
		$row['payingfor'] = str_replace("\n",'',str_replace('</',' </',$row['payingfor']));
		$row['payingfor'] = strip_tags($row['payingfor']);
		$parts = explode(' ',$row['date']);
		$row['date'] = $parts[0];
		foreach($row as $field => $value) {
			if(!in_array($field, $fields) && !in_array($field,['post_title','date']))
				$fields[] = $field;
		}
		$eventrows[] = $row;
	}

	return array('rsvp'=>$eventrows,'fields'=>$fields);
}
function rsvp_report_table() {

	?>

<style>

table#rsvptable {

	border-collapse: collapse;

}

table#rsvptable td, table#rsvptable td {

border: thin solid #555;

padding: 3px;

text-align: left;

}

</style>

	<?php

	global $wpdb, $rsvp_options;

	$fields = array_map('sanitize_text_field',$_GET['fields']);

	$eventid   = (int) $_GET['event'];
	$event_row = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker_event WHERE event=$eventid" );

	$date = $event_row->date;

	$t = rsvpmaker_strtotime( $date );

	$title = esc_html( $event_row->post_title ) . ' ' . rsvpmaker_date( $rsvp_options['long_date'], $t );

	echo "<h2>$title</h2>\n<table id=\"rsvptable\"><tr>\n";

	// Create new PHPExcel object

	if ( is_array( $fields ) ) {

		foreach ( $fields as $column => $name ) {

			echo "<th>$name</th>";

		}
	}

	echo '</tr>';

	$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, master_rsvp, last, first";

	$results = $wpdb->get_results( $sql, ARRAY_A );

	$rows = sizeof( $results );
	if(!empty($phonecol))
	$phonecells = $phonecol . '1:' . $phonecol . ( $rows + 1 );

	if ( is_array( $results ) ) {

		foreach ( $results as $row ) {

			//$index++;

			$row['yesno'] = ( $row['yesno'] ) ? 'YES' : 'NO';

			if ( $row['details'] ) {

				$details = unserialize( $row['details'] );

				$row = array_merge( $row, $details );

			}

			echo '<tr>';

			if ( is_array( $fields ) ) {

				foreach ( $fields as $column => $name ) {

					if ( isset( $row[ $name ] ) ) {

						printf( '<td>%s</td>', strip_tags( $row[ $name ] ) );

					} else {
						echo '<td></td>';
					}
				}
			}

			echo '</tr>';

		}
	}

		echo '</table>';

}

	function get_spreadsheet_data( $eventid ) {

		global $wpdb;

		$sql = 'SELECT yesno,first,last,email, details, note, guestof FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, last, first";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		foreach ( $results as $index => $row ) {

			$srow['answer'] = ( $row['yesno'] ) ? 'YES' : 'NO';

			$srow['name'] = $row['first'] . ' ' . $row['last'];

			$details = unserialize( $srow['details'] );

			$srow['address'] = $details['address'] . ' ' . $details['city'] . ' ' . $details['state'] . ' ' . $details['zip'];

			$srow['employment'] = $details['occupation'] . ' ' . $details['company'];

			$srow['email'] = $row['email'];

			$srow['guestof'] = $row['guestof'];

			$srow['note'] = $row['note'];

			$spreadsheet[] = $srow;

		}

		return $spreadsheet;

	}
// end get spreadsheet data

function widgetlink( $evdates, $plink, $evtitle ) {

	return sprintf( '<a href="%s">%s</a> %s', esc_attr( $plink ), esc_html( $evtitle ), esc_html( $evdates ) );

}

function rsvpmaker_profile_lookup( $email = '' ) {

		global $wpdb;

		$profile = array();

		if ( isset( $_GET['blank'] ) ) {

			return null;
		}

		if ( ! empty( $email ) ) {

			if(isset($_GET['rmail'])) {
				$sql = 'SELECT email, first_name as first, last_name as last FROM ' . $wpdb->prefix . 'rsvpmaker_guest_email WHERE email LIKE "' . $email . '" ORDER BY id DESC';
				$profile = $wpdb->get_row($sql,ARRAY_A);
				if($profile) {
					return $profile;
				}
			}

			$sql = 'SELECT details FROM ' . $wpdb->prefix . 'rsvpmaker WHERE email LIKE "' . $email . '" ORDER BY id DESC';

			$details = $wpdb->get_var( $sql );

			if ( ! empty( $details ) ) {

				$details = unserialize( $details );

				$profile['email'] = $details['email'];

				$profile['first'] = $details['first'];

				$profile['last'] = $details['last'];

				foreach ( $details as $name => $value ) {

					if ( strpos( $name, 'phone' ) !== false ) {

						$profile[ $name ] = $value;
					}
				}
			}
		} else {

			// if members are registered and logged in, retrieve basic info for profile

			if ( is_user_logged_in() ) {

				global $current_user;

				$profile['email'] = $current_user->user_email;

				$profile['first'] = $current_user->first_name;

				$profile['last'] = $current_user->last_name;

			}
		}

		return $profile;

}

function ajax_guest_lookup() {

		$event = (int) $_GET['ajax_guest_lookup'];

		global $wpdb;

		$sql = 'SELECT first,last,note FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$event AND yesno=1 ORDER BY id DESC";

		$attendees = $wpdb->get_results( $sql );

		echo '<div class="attendee_list">';

		if ( is_array( $attendees ) ) {

			foreach ( $attendees as $row ) {

				;
				?>

<h3 class="attendee"><?php echo esc_html( $row->first ); ?> <?php echo esc_html( $row->last ); ?></h3>

				<?php

				if ( $row->note ) {
				}

				echo wpautop( $row->note );

			}
		}

		echo '</div>';

		exit();

}

function rsvp_reminder_activation() {

	if ( isset( $_GET['autorenew'] ) ) {

		rsvpautorenew_test();
	}

	if ( ! wp_next_scheduled( 'rsvp_daily_reminder_event' ) ) {

		$hour = 12 - get_option( 'gmt_offset' );

		$t = rsvpmaker_mktime( $hour, 0, 0, date( 'n' ), date( 'j' ), date( 'Y' ) );

		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rsvp_daily_reminder_event' );

	}

	if(rsvpmaker_postmark_is_active())
		return; //we don't want to start rsvpmaker_relay_init_hook

	$active = get_option( 'rsvpmaker_discussion_active' );

	// if stalled, restart email queue process

	if ( $active && ! wp_next_scheduled( 'rsvpmaker_relay_init_hook' ) && !rsvpmaker_postmark_is_live() ) {
		wp_schedule_event( time(), 'doubleminute', 'rsvpmaker_relay_init_hook' );
	}

}

function rsvp_daily_reminder() {
		delete_transient('rsvpmakerdates');//no longer used
		rsvpautorenew_test(); // also check for templates that autorenew
		cleanup_rsvpmaker_child_documents(); // delete form and confirmation messages
		rsvpmaker_reminders_nudge(); // make sure events with reminders set are in cron
		rsvpmaker_consistency_check();

		global $wpdb;

		global $rsvp_options;

		$today = rsvpmaker_date( 'Y-m-d' );

		$sql = "SELECT * FROM `$wpdb->postmeta` WHERE `meta_key` LIKE '_rsvp_reminder' AND `meta_value`='$today'";

		if ( $reminders = $wpdb->get_results( $sql ) ) {

			foreach ( $reminders as $reminder ) {

				$post_id = $reminder->post_id;

				$q = "p=$post_id&post_type=rsvpmaker";

				echo "Post $post_id is scheduled for a reminder $q<br />";

				global $post;

				query_posts( $q );

				global $wp_query;

				// treat as single, display rsvp button, not form

				$wp_query->is_single = false;

				the_post();

				if ( $post->post_title ) {

					$event_title = $post->post_title;

					ob_start();

					echo '<h1>';

					the_title();

					echo "</h1>\n<div>\n";

					the_content();

					echo "\n</div>\n";

					$event = ob_get_clean();

					$rsvpto = get_post_meta( $post_id, '_rsvp_to', true );

					$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$post_id AND yesno=1";

					$rsvps = $wpdb->get_results( $sql, ARRAY_A );

					if ( $rsvps ) {

						foreach ( $rsvps as $row ) {

							$notify = $row['email'];

							$row['yesno'] = ( $row['yesno'] ) ? 'YES' : 'NO';

							$notification = '<p>' . __( 'This is an automated reminder that we have you on the RSVP list for the event shown below. If your plans have changed, you can update your response by clicking on the RSVP button again.', 'rsvpmaker' ) . '</p>';

							$notification .= '<h3>' . esc_html( $row['yesno'] . ' ' . $row['first'] . ' ' . $row['last'] . ' ' . $row['email'] );

							if ( $row['guestof'] ) {

								$notification .= esc_html( ' (' . __( 'guest of', 'rsvpmaker' ) . ' ' . $row['guestof'] . ')' );
							}

							$notification .= "</h3>\n";

							$notification .= '<p>';

							if ( $row['details'] ) {

								$details = unserialize( $row['details'] );

								if ( is_array( $details ) ) {

									foreach ( $details as $name => $value ) {

										if ( $value ) {

											$notification .= esc_html( "$name: $value" ) . '<br />';

										}
									}
								}
							}

							if ( $row['note'] ) {

								$notification .= ' note: ' . wp_kses_post( nl2br( $row['note'] ) ) . '<br />';
							}

							$t = rsvpmaker_strtotime( $row['timestamp'] );

							$notification .= 'posted: ' . rsvpmaker_date( $rsvp_options['short_date'], $t );

							$notification .= '</p>';

							$notification .= "<h3>Event Details</h3>\n" . rsvpmail_replace_email_placeholder( $event, $notify);

							$notification = wp_kses_post( $notification );

							echo "Notification for $notify<br />$notification";

							$subject = '=?UTF-8?B?' . base64_encode( __( 'Event Reminder for', 'rsvpmaker' ) . ' ' . $event_title ) . '?=';

							if ( isset( $rsvp_options['smtp'] ) && ! empty( $rsvp_options['smtp'] ) ) {

								$mail['subject'] = __( 'Event Reminder for', 'rsvpmaker' ) . ' ' . $event_title;

								$mail['html'] = $notification;

								$mail['to'] = $notify;

								$mail['from'] = $rsvp_to;

								$mail['fromname'] = get_bloginfo( 'name' );

								rsvpmailer( $mail );

							} else {

								$subject = '=?UTF-8?B?' . base64_encode( __( 'Event Reminder for', 'rsvpmaker' ) . ' ' . $event_title ) . '?=';

								mail( $notify, $subject, $notification, "From: $rsvpto\nContent-Type: text/html; charset=UTF-8" );

							}
						}
					}
				}
			}
		} else {
			echo 'none found';
		}

}

//legacy
	function rsvpguests( $atts ) {

		if ( is_admin() || wp_is_json_request() ) {

			return;
		}
		return rsvp_form_guests($atts);
	}

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

		if ( isset( $atts['required'] ) || isset( $atts['require'] ) ) {

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

function guestfield( $atts, $profile, $count ) {

		global $fieldcount;

		if ( ! $fieldcount ) {

			$fieldcount = 1;
		}

		// synonyms

		if ( isset( $atts['text'] ) && ! isset( $atts['textfield'] ) ) {
			$atts['textfield'] = $atts['text'];
		}

		if ( isset( $atts['select'] ) && ! isset( $atts['selectfield'] ) ) {
			$atts['selectfield'] = $atts['select'];
		}

		if ( isset( $atts['textfield'] ) ) {

			$field = $atts['textfield'];

			$label = ( isset( $atts['label'] ) ) ? $atts['label'] : ucfirst( str_replace( '_', ' ', $field ) );

			$firstlabel = __( 'First', 'rsvpmaker' );

			$lastlabel = __( 'Last', 'rsvpmaker' );

			if ( ( $label == 'First' ) && ( $label != $firstlabel ) ) {

				$label = str_replace( 'First', $firstlabel, $label );
			}

			if ( ( $label == 'Last' ) && ( $label != $lastlabel ) ) {

				$label = str_replace( 'Last', $lastlabel, $label );
			}

			$size = ( isset( $atts['size'] ) ) ? ' size="' . $atts['size'] . '" ' : '';

			$data = ( isset( $profile[ $field ] ) ) ? ' value="' . $profile[ $field ] . '" ' : '';

			$output = '<div class="' . $field . '"><label>' . $label . ':</label> <input type="text" name="guest[' . $field . '][' . $count . ']" id="' . $field . $fieldcount++ . '" ' . $size . $data . '  class="' . $field . '" /></div>';

		} elseif ( isset( $atts['selectfield'] ) ) {

			$field = $atts['selectfield'];

			$label = ( isset( $atts['label'] ) ) ? $atts['label'] : ucfirst( str_replace( '_', ' ', $field ) );

			$selected = ( isset( $atts['selected'] ) ) ? trim( $atts['selected'] ) : '';

			if ( isset( $profile[ $field ] ) ) {

				$selected = $profile[ $field ];
			}

			$output = '<div class="' . $field . '"><label>' . $label . ':</label> <select  class="' . $field . '" name="guest[' . $field . '][' . $count . ']" id="' . $field . $fieldcount++ . '" >' . "\n";

			if ( isset( $atts['options'] ) ) {

				$o = explode( ',', $atts['options'] );

				foreach ( $o as $i ) {

					$i = trim( $i );

					$s = ( $selected == $i ) ? ' selected="selected" ' : '';

					$output .= '<option value="' . $i . '" ' . $s . '>' . $i . '</option>' . "\n";

				}
			}

			$output .= '</select></div>' . "\n";

		} elseif ( isset( $atts['radio'] ) ) {

			$field = $atts['radio'];

			$label = ( isset( $atts['label'] ) ) ? $atts['label'] : ucfirst( str_replace( '_', ' ', $field ) );

			$sep = ( isset( $atts['sep'] ) ) ? $atts['sep'] : ' ';

			$checked = ( isset( $atts['checked'] ) ) ? trim( $atts['checked'] ) : '';

			if ( isset( $profile[ $field ] ) ) {

				$checked = $profile[ $field ];
			}

			if ( isset( $atts['options'] ) ) {

				$o = explode( ',', $atts['options'] );

				foreach ( $o as $i ) {

					$i = trim( $i );

					$ischecked = ( $checked == $i ) ? ' checked="checked" ' : '';

					$radio[] = '<input  class="' . $field . '" type="radio" name="guest[' . $field . '][' . $count . ']" id="' . $field . $i . $fieldcount++ . '" class="' . $field . '"  value="' . $i . '"  ' . $ischecked . '/> ' . $i . ' ';

				}
			}

			$output = '<div  class="' . $field . '"><label>' . $label . ':</label> ' . implode( $sep, $radio ) . '</div>';

		}

		return $output;

	}

function rsvpnote() {

	global $rsvp_row;

	return ( isset( $rsvp_row->note ) ) ? $rsvp_row->note : '';

}

	function date_title( $title, $sep = '&raquo;', $seplocation = 'left' ) {

		global $post;

		global $wpdb;

		if ( empty( $post->post_type ) ) {
			return $title;
		}

		if(empty($title))
			$title = $post->post_title;

		if ( $post->post_type == 'rsvpmaker' ) {

			// get first date associated with event

			$event = get_rsvpmaker_event($post->ID);

			$title .= rsvpmaker_date( 'F jS', rsvpmaker_strtotime( $event->ts_start ) );

			if ( $seplocation == 'right' ) {

				$title .= " $sep ";

			} else {
				$title = " $sep $title ";
			}
		}

		return $title;

}

add_filter( 'wp_title', 'date_title', 1, 3 );

function rsvpmaker_template_list() {
		global $rsvp_options, $wpdb, $current_user;
		?>
<div class="wrap"> 
		<?php
		$heading = __( 'Create / Update from Template', 'rsvpmaker' );
		rsvpmaker_admin_heading($heading, __FUNCTION__);

		if ( ! empty( $_POST['import_shared_template'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  ) {
			$url = sanitize_text_field( $_POST['import_shared_template'] );
			printf( '<p>Importing %s</p>', $url );
			$duplicate = $wpdb->get_var( "SELECT ID FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id  WHERE meta_key='template_imported_from' AND meta_value='$url' " );
			$response  = wp_remote_get( $url );

			if ( empty( $duplicate ) && is_array( $response ) && ! is_wp_error( $response ) ) {
				$headers = $response['headers']; // array of http header lines
				$body    = $response['body']; // use the content
				$data    = json_decode( $body );
				// print_r($data);
				$newpost['post_type']    = 'rsvpmaker';
				$newpost['post_status']  = 'publish';
				$newpost['post_author']  = $current_user->ID;
				$newpost['post_title']   = $data->post_title;
				$newpost['post_content'] = $data->post_content;
				$id                      = wp_insert_post( $newpost );
				rsvpmaker_set_template_defaults( $id );
				update_post_meta( $id, 'template_imported_from', $url );
			}
		}

		if ( ! empty( $_POST['share_template'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
			update_post_meta( $_POST['share_template'], 'rsvpmaker_shared_template', true );
		}

		if ( ! empty( $_POST['override'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

			$override = (int) $_POST['override'];

			$overridden = (int) $_POST['overridden'];

			$opost = get_post( $override );

			$target = get_post( $overridden );

			$sk = get_template_sked( $overridden );

			if ( $sk ) {

				wp_update_post(
					array(
						'ID'         => $override,
						'post_title' => $opost->post_title . ' (backup)',
					)
				);
			}

			$newpost = array(
				'ID'           => $overridden,
				'post_title'   => $opost->post_title,
				'post_content' => $opost->post_content,
				'post_name'    => $target->post_name,
			);

			wp_update_post( $newpost );
			$sql = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_key='_detached_from_template' WHERE meta_key='_meet_recur' AND post_id=%d", $overridden );
			$wpdb->query( $sql );
			update_post_meta( $overridden, '_meet_recur', $override );

			printf( '<div class="updated notice notice-success">Applied "%s" template: <a href="%s">View</a> | <a href="%s">Edit</a></div>', $opost->post_title, get_permalink( $overridden ), admin_url( 'post.php?action=edit&post=' . $overridden ) );

			if(isset($_POST['copymeta']))
			{
				foreach($_POST['copymeta'] as $key)
				{
					$key = sanitize_text_field($key);
					if(empty($keysql))
						$keysql = '';
					else
						$keysql .= ' OR ';
					$keysql .= "  meta_key LIKE '$key%' ";//match multiple reminder messages
				}

				$sql = "select * from $wpdb->postmeta WHERE post_id=" . $override ." AND ($keysql) ";
				$results = $wpdb->get_results( $sql );

				if ( is_array( $results ) ) {

					foreach ( $results as $row ) {	
							update_post_meta( $overridden, $row->meta_key, $row->meta_value );
							$copied[] = $row->meta_key;
					}
				}

			}
			if ( ! empty( $copied ) ) {

				printf( '<p>Settings copied: %s</p>', implode( ', ', $copied ) );
			}
		}

		if ( isset( $_GET['override_template'] ) || ( isset( $_GET['t'] ) && isset( $_GET['overconfirm'] ) ) ) {

			$t = ( isset( $_GET['override_template'] ) ) ? (int) $_GET['override_template'] : (int) $_GET['t'];

			$e = (int) $_GET['event'];

			$ts = get_rsvp_date( $e );

			if ( isset( $_GET['overconfirm'] ) ) {

				$event = get_post( $e );

				$newpost = array(
					'ID'           => $t,
					'post_title'   => $event->post_title,
					'post_content' => $event->post_content,
				);

				wp_update_post( $newpost );

				printf( '<h1>Template updated based on contents of event for %s</h1>', rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $ts ) ) );

				$sql = "select * from $wpdb->postmeta WHERE post_id=" . $e;

				$results = $wpdb->get_results( $sql );

				$docopy = array( '_add_timezone', '_convert_timezone', '_calendar_icons', 'tm_sidebar', 'sidebar_officers' );

				if ( is_array( $results ) ) {

					foreach ( $results as $row ) {

						if ( ( strpos( $row->meta_key, 'rsvp' ) && ( $row->meta_key != '_rsvp_dates' ) ) || ( in_array( $row->meta_key, $docopy ) ) ) {

							update_post_meta( $t, $row->meta_key, $row->meta_value );

							$copied[] = $row->meta_key;

						}
					}
				}

				if ( ! empty( $copied ) ) {

					printf( '<p>Settings copied: %s</p>', implode( ', ', $copied ) );
				}
			} else {

				printf( '<h1 style="color: red;">Update Template?</h1><p>Click &quot;Confirm&quot; to override template with the contents of your %s event<p><form method="get" action="%s"><input type="hidden" name="post_type" value="rsvpmaker" /><input type="hidden" name="page" value="rsvpmaker_template_list" /><input type="hidden" name="t" value="%d" /><input type="hidden" name="event" value="%d" /><input type="hidden" name="overconfirm" value="1" /><button>Confirm</button>%s</form> ', rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $ts ) ), admin_url( 'edit.php' ), $t, $e, rsvpmaker_nonce('return') );

			}
		}

		if ( isset( $_POST['event_to_template'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

			$e = (int) $_POST['event_to_template'];

			$ts = get_rsvp_date( $e );

			$tsexplode = preg_split( '/[\s:]/', $ts );

			$event = get_post( $e );

			$newpost = array(
				'post_title'   => $event->post_title,
				'post_content' => $event->post_content,
				'post_type'    => 'rsvpmaker_template',
				'post_author'  => $current_user->ID,
				'post_status'  => 'publish',
			);

			$t = wp_insert_post( $newpost );

			$template = array(
				'week'      => array( 0 ),
				'dayofweek' => array( 0 ),
				'hour'      => $tsexplode[1],
				'minutes'   => $tsexplode[2],
			);

			new_template_schedule( $t, $template );

			printf( '<h1>Template updated based on contents of event for %s</h1>', rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $ts ) ) );

			$sql = "select * from $wpdb->postmeta WHERE post_id=" . $e;

			$results = $wpdb->get_results( $sql );

			$docopy = array( '_add_timezone', '_convert_timezone', '_calendar_icons', 'tm_sidebar', 'sidebar_officers' );

			if ( is_array( $results ) ) {

				foreach ( $results as $row ) {

					if ( ( strpos( $row->meta_key, 'rsvp' ) && ( $row->meta_key != '_rsvp_dates' ) ) || ( in_array( $row->meta_key, $docopy ) ) ) {

						update_post_meta( $t, $row->meta_key, $row->meta_value );

						$copied[] = $row->meta_key;

					}
				}
			}

			if ( ! empty( $copied ) ) {

				printf( '<p>Settings copied: %s</p>', implode( ', ', $copied ) );
			}
		}

		if ( empty( $_REQUEST['t'] ) ) {

			printf( '<h3>Add One or More Events Based on a Template</h3><form method="get" action="%s"><input type="hidden" name="post_type" value="rsvpmaker" />%s <select name="page"><option value="rsvpmaker_setup">%s</option><option value="rsvpmaker_template_list">%s</option></select><br /><br />%s %s<br >%s %s</form>', admin_url( 'edit.php' ), __( 'Add', 'rsvpmaker' ), __( 'One event', 'rsvpmaker' ), __( 'Multiple events', 'rsvpmaker' ), __( 'based on', 'rsvpmaker' ), rsvpmaker_templates_dropdown( 't' ), get_submit_button( 'Submit' ), rsvpmaker_nonce('return') );
		}

		do_action( 'rsvpmaker_template_list_top' );

		if ( isset( $_GET['t'] ) ) {

			$t = (int) $_GET['t'];

			rsvp_template_checkboxes( $t );

		}

		$dayarray = array( __( 'Sunday', 'rsvpmaker' ), __( 'Monday', 'rsvpmaker' ), __( 'Tuesday', 'rsvpmaker' ), __( 'Wednesday', 'rsvpmaker' ), __( 'Thursday', 'rsvpmaker' ), __( 'Friday', 'rsvpmaker' ), __( 'Saturday', 'rsvpmaker' ) );

		$weekarray = array( __( 'Varies', 'rsvpmaker' ), __( 'First', 'rsvpmaker' ), __( 'Second', 'rsvpmaker' ), __( 'Third', 'rsvpmaker' ), __( 'Fourth', 'rsvpmaker' ), __( 'Last', 'rsvpmaker' ), __( 'Every', 'rsvpmaker' ) );

		global $wpdb;

		$wpdb->show_errors();

		global $current_user;

		global $rsvp_options;

		$current_template = $event_options = $template_options = '';

		$template_override = '';

		if ( isset( $_GET['restore'] ) ) {

			echo '<div class="notice notice-info">';

			$r = (int) $_GET['restore'];

			$sked['week'] = array( 6 );

			$sked['dayofweek'] = array();

			$sked['hour'] = $rsvp_options['defaulthour'];

			$sked['minutes'] = $rsvp_options['defaultmin'];

			if ( $_GET['specimen'] ) {

				$date = get_rsvp_date( $_GET['specimen'] );

				if ( $date ) {

					$t = strtotime( $date );

					$sked['dayofweek'] = array( date( 'w', $t ) );

					$sked['hour'] = date( 'h', $t );

					$sked['minutes'] = date( 'i', $t );

				}
			}

			$sked['duration'] = '';

			$sked['stop'] = '';

			new_template_schedule( $r, $sked );

			echo '<p>Restoring template. Edit to fix schedule parameters.</p></div>';

		}

		$templates = $results = rsvpmaker_get_templates('',true);

		if ( $results ) {

			printf( '<h3>Templates</h3><table  class="wp-list-table widefat fixed posts" cellspacing="0"><thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></thead><tbody>', __( 'Title', 'rsvpmaker' ), __( 'Schedule', 'rsvpmaker' ), __( 'Projected Dates', 'rsvpmaker' ), __( 'Event', 'rsvpmaker' ) );

			foreach ( $results as $post ) {

				if($post->post_status == 'draft')
					$post->post_title .= ' (draft)';

				if ( isset( $_GET['apply_current'] ) && ( $post->ID == $_GET['apply_current'] ) ) {

					$current_template = '<option value="' . $post->ID . '">Current Template: ' . $post->post_title . '</option>';
				}

				$sked = get_template_sked( $post->ID );

				// backward compatability

				if ( is_array( $sked['week'] ) ) {

					$weeks = $sked['week'];

					$dows = ( empty( $sked['dayofweek'] ) ) ? 0 : $sked['dayofweek'];

				} else {

					$weeks = array();

					$dows = array();

					$weeks[0] = ( isset( $sked['week'] ) ) ? $sked['week'] : 0;

					$dows[0] = ( isset( $sked['dayofweek'] ) ) ? $sked['dayofweek'] : 0;

				}

				$dayarray = array( __( 'Sunday', 'rsvpmaker' ), __( 'Monday', 'rsvpmaker' ), __( 'Tuesday', 'rsvpmaker' ), __( 'Wednesday', 'rsvpmaker' ), __( 'Thursday', 'rsvpmaker' ), __( 'Friday', 'rsvpmaker' ), __( 'Saturday', 'rsvpmaker' ) );

				$weekarray = array( __( 'Varies', 'rsvpmaker' ), __( 'First', 'rsvpmaker' ), __( 'Second', 'rsvpmaker' ), __( 'Third', 'rsvpmaker' ), __( 'Fourth', 'rsvpmaker' ), __( 'Last', 'rsvpmaker' ), __( 'Every', 'rsvpmaker' ) );

				if ( empty( $sked['week'] ) || ( (int) $sked['week'][0] == 0 ) ) {

					$s = __( 'Schedule Varies', 'rsvpmaker' );

				} else {

					foreach ( $weeks as $week ) {

						if ( empty( $s ) ) {

							$s = '';

						} else {
							$s .= '/ ';
						}

						$s .= $weekarray[ (int) $week ] . ' ';

					}

					if ( ! empty( $dows ) && is_array( $dows ) ) {

						foreach ( $dows as $dow ) {

							if ( $dow > -1 ) {

								$s .= $dayarray[ (int) $dow ] . ' ';
							}
						}
					}

					if ( empty( $sked['hour'] ) || empty( $sked['minutes'] ) ) {

						$time = '';

					} else {

						$time = rsvpmaker_strtotime( $sked['hour'] . ':' . $sked['minutes'] );

						$s .= ' ' . rsvpmaker_date( $rsvp_options['time_format'], $time );

					}
				}

				if(get_post_meta( $post->ID, 'rsvpautorenew', true ) )
					$s .= '<br><strong>'.__('Set to automatically add dates','rsvpmaker').'</strong>';

				$eds = get_additional_editors( $post->ID );

				if ( ( $post->post_author == $current_user->ID ) || in_array( $current_user->ID, $eds ) || current_user_can( 'edit_post', $post->ID ) ) {

					$template_edit_url = admin_url( 'post.php?action=edit&post=' . intval( $post->ID ) );

					$title = sprintf( '<a href="%s">%s</a>', esc_attr( $template_edit_url ), esc_html( $post->post_title ) );

					if ( strpos( $post->post_content, '[toastmaster' ) && function_exists( 'agenda_setup_url' ) ) { // rsvpmaker for toastmasters

						$title .= sprintf( ' (<a href="%s">Toastmasters %s</a>)', agenda_setup_url( $post->ID ), __( 'Agenda Setup', 'rsvptoast' ) );
					}

					$template_options .= sprintf( '<option value="%d">%s</option>', $post->ID, esc_html( $post->post_title ) );

					$template_override .= sprintf( '<option value="%d">APPLY TO TEMPLATE: %s</option>', $post->ID, esc_html( $post->post_title ) );

					$template_recur_url = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . intval( $post->ID ) );

					$schedoptions = sprintf( ' (<a href="%s">Options</a>)', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=' ) . intval( $post->ID ) );

					printf( '<tr><td>%s</td><td>%s</td><td><a href="%s">' . __( 'Create/Update', 'rsvpmaker' ) . '</a></td><td>%s</td></tr>' . "\n", wp_kses_post( $title ) . wp_kses_post( $schedoptions ), $s, esc_attr( $template_recur_url ), next_or_recent( $post->ID ) );

				} else {

					$title = $post->post_title;

					printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n", esc_html( $title ), $s, __( 'Not an editor', 'rsvpmaker' ), next_or_recent( $post->ID ) );

				}

				$s = '';

			}

			echo '</tbody></table>';

			printf( '<p><a href="%s">See all templates (including drafts)</a></p>', admin_url( 'edit.php?post_type=rsvpmaker&rsvpsort=templates' ) );

			if ( isset( $template_options ) ) {

				echo '<div id="applytemplate"></div><h3>Apply Template to Existing Event</h3>';

				$target_id = isset( $_GET['apply_target'] ) ? (int) $_GET['apply_target'] : 0;

				if ( $target_id ) {

					$event = get_rsvpmaker_event( $target_id );

					if ( ! empty( $event ) ) {

						$event_options .= sprintf( '<option value="%d" selected="selected">%s %s</option>', $event->event, esc_html( $event->post_title ), esc_html( $event->date ) );
					}
				}

				$current_template .= '<option value="0">Choose Template</option>';

				$results = rsvpmaker_get_future_events();

				if ( is_array( $results ) ) {

					foreach ( $results as $r ) {

						$event_options .= sprintf( '<option value="%d">%s %s</option>', $r->postID, esc_html( $r->post_title ), $r->datetime );

					}
				}

				$action = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list' );

				printf( '<form method="post" action="%s"><p>Apply <select name="override">%s</select> to <select name="overridden">%s</select></p><p>Copy settings:</p>', $action, $current_template . $template_options, $event_options . $template_override );
				printf('<div><input type="checkbox" value="_add_timezone" name="copymeta[]">  %s</div>',__('Show Timezone','rsvpmaker'));
				printf('<div><input type="checkbox" value="_convert_timezone" name="copymeta[]">  %s</div>',__('Convert Timezone','rsvpmaker'));
				printf('<div><input type="checkbox" value="_calendar_icons" name="copymeta[]">  %s</div>',__('Show Calendar Icons','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_on" name="copymeta[]">  %s</div>',__('Collect RSVPs on/off','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_instructions" name="copymeta[]">  %s</div>',__('RSVP Instructions','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_to" name="copymeta[]">  %s</div>',__('Email for RSVP Notifications','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_rsvpmaker_send_confirmation_email" name="copymeta[]">  %s</div>',__('Send Confirmation Email on/off','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_confirm" name="copymeta[]">  %s</div>',__('Confirmation Email Message','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_confirmation_include_event" name="copymeta[]">  %s</div>',__('Include Event Details in Confirmation','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_confirmation_after_payment" name="copymeta[]">  %s</div>',__('Send Confirmation After Payment on/off','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_reminder_" name="copymeta[]">  %s</div>',__('Reminder Email Messages','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_timezone_string" name="copymeta[]">  %s</div>',__('Timezone','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_count" name="copymeta[]">  %s</div>',__('Show RSVP Count Publicly','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_show_attendees" name="copymeta[]">  %s</div>',__('Show Attendees Publicly','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_login_required" name="copymeta[]">  %s</div>',__('Login Required','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_max" name="copymeta[]">  %s</div>',__('Maximum Number of Attendees','rsvpmaker'));
				printf('<div><input type="checkbox" value="_per" name="copymeta[]">  %s</div>',__('Pricing','rsvpmaker'));
				printf('<div><input type="checkbox" value="_rsvp_count_party" name="copymeta[]">  %s</div>',__('Count Members of Party for Event Pricing','rsvpmaker'));
				submit_button();
				rsvpmaker_nonce();
				echo '</form>';

			}
		}

		$event_options = '';

		$results = rsvpmaker_get_future_events();

		if ( is_array( $results ) ) {

			foreach ( $results as $r ) {

				$event_options .= sprintf( '<option value="%d">%s %s</option>', $r->ID, esc_html( $r->post_title ), $r->datetime );

			}
		}

		$action = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list' );

		echo '<h3 id="fromexisting">Create Template Based on Existing Event</h3>';

		printf(
			'<form method="post" action="%s"><p>%s <select name="event_to_template">%s</select>

		</p>',
			$action,
			__( 'Copy', 'rsvpmaker' ),
			$event_options
		);

		submit_button( __( 'Copy Event', 'rsvpmaker' ) );
		rsvpmaker_nonce();
		echo '</form>';

		printf( '<h2>%s</h2><p>%s</p>', __( 'Shared Templates', 'rsvpmaker' ), __( 'RSVPMaker users can share the content of templates between websites', 'rsvpmaker' ) );

		$shared_templates = '';
		$results          = $wpdb->get_results( "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID= $wpdb->postmeta.post_id WHERE meta_key='rsvpmaker_shared_template'" );
		if ( $results ) {
			echo '<h3>My Shared Templates</h3>';
			foreach ( $results as $row ) {
				printf( '<p>%s<br />%s</p>', $row->post_title, rest_url( 'rsvpmaker/v1/shared_template/' . intval( $row->ID ) ) );
			}
		}

		do_action( 'import_shared_template_prompt' );

		printf( '<h3>%s</h3><form action="%s" method="post"><input name="import_shared_template" />', __( 'Import Shared Template', 'rsvpmaker' ), admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list' ) );
		submit_button( __( 'Import', 'rsvpmaker' ) );
		rsvpmaker_nonce();
		echo '</form>';

		if ( $template_options ) {
			printf( '<h3>%s</h3><form action="%s" method="post"><select name="share_template">%s</select>', __( 'Share Template', 'rsvpmaker' ), admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list' ), $template_options );
			submit_button( __( 'Share Template', 'rsvpmaker' ) );
			rsvpmaker_nonce();
			echo '</form>';
		}

		$restore = '';

		$sql = "select count(*) as copies, meta_value as t FROM $wpdb->postmeta WHERE `meta_key` = '_meet_recur' group by meta_value";

		$results = $wpdb->get_results( $sql );

		foreach ( $results as $index => $row ) {

			if ( ! rsvpmaker_is_template( $row->t ) ) {

				$corrupted = get_post( $row->t );

				if ( $corrupted ) {

					$future = future_rsvpmakers_by_template( $row->t );

					$futurecount = ( $future ) ? sizeof( $future ) : 0;

					$specimen = ( $futurecount ) ? $future[0] : 0;

					$restore .= sprintf( '<p><a href="%s">Restore</a> - This template appears to have been corrupted: <strong>%s</strong> (%d) last modified: %s, used for %d future events.</p>', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&restore=' . intval( $corrupted->ID ) . '&specimen=' . intval( $specimen ) ), esc_html( $corrupted->post_title ), esc_attr( $corrupted->ID ), esc_attr( $corrupted->post_modified ), esc_attr( $futurecount ) );

				}
			}
		}

		if ( ! empty( $restore ) ) {

			echo '<h3>Restore Templates</h3>' . $restore;
		}

		foreach ( $templates as $template ) {
			$date = get_rsvp_date( $template->ID );
			if ( $date ) {
				printf( '<p>Template %d also shows date %s</p>', $template->ID, esc_html( $date ) );
			}
		}

		if(isset($_POST['holiday_include'])) {
			foreach($_POST['holiday_include'] as $index) {
				if($_POST['name'][$index] && $_POST['schedule'][$index]) {
					$h[] = array('name' => sanitize_text_field(stripslashes($_POST['name'][$index])),'schedule' => sanitize_text_field($_POST['schedule'][$index]),'default' => isset($_POST['default'][$index]),'overflow' => sanitize_text_field($_POST['overflow'][$index]));
				}
			}
			update_option('rsvpmaker_holidays',$h);
		}
		printf('<h3 id="holidays">Holidays and Blackout Dates (<a href="%s">Customize</a>)</h3>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&setholidays=1#holidays'));
		if(isset($_GET['setholidays'])) {
		echo '<p>Schedule field can be a specific date like "December 25", a description like "Third Thursday of November" or a date range like "July 1 to August 31"</p>';
		echo '<p>Set the Adjacent day rule if (a) the holiday sometimes falls on a weekend and is observed the Friday before or Monday after or (b) the day after the holiday is also a day off for your organization (example: the day after Thanksgiving).</p>';
		echo '<p>The defaults are based on U.S. federal holidays but can be customized for other regions and traditions.</p>';
		printf('<form method="post" action="%s">',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list#holidays'));
		$schedule = rsvpmakerDefaultHolidays();
		$schedule[] = array('name' => '','schedule' => '', 'default' => false, 'overflow' => '');
		$schedule[] = array('name' => '','schedule' => '', 'default' => false, 'overflow' => '');
		$schedule[] = array('name' => '','schedule' => '', 'default' => false, 'overflow' => '');
		$schedule[] = array('name' => '','schedule' => '', 'default' => false, 'overflow' => '');
		foreach($schedule as $index => $s) {
			$isdefault = ($s['default']) ? ' checked="checked" ' : '';
			printf('<p>Name<br><input type="text" name="name[%d]" value="%s"><br>Schedule<br><input type="text" name="schedule[%d]" value="%s"> 
			<br>Adjacent day rule: <select name="overflow[%d]"><option value="%s">%s</option><option value="weekend">Monday/Friday if falls on weekend</option><option value="nextday">Next day</option></select>
			<br><input type="checkbox" name="holiday_include[]" value="%d" checked="checked"> include <input type="checkbox" name="default[%d]" value="1" %s> block by default</p>',
			$index,$s['name'],$index,$s['schedule'],$index,$s['overflow'],$s['overflow'],$index,$index,$isdefault);
		}
		submit_button( __( 'Set Holidays and Blackout Dates', 'rsvpmaker' ) );
		rsvpmaker_nonce();
		echo '</form>';		
		}

		$holidays = commonHolidays();
		echo '<p><strong>Current list</strong> ';
		foreach($holidays as $t => $s)
			printf('%s %s / ',$s['name'],rsvpmaker_date('l F jS, Y',$t));
		echo '<p>';

		if(isset($_GET['autorenew']))
			auto_renew_project ($_GET['autorenew'], true);
		else {
			foreach ( $templates as $template ) {
				$url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&autorenew='.$template->ID);
				printf('<p><a href="%s">Auto renew %s</a></p>',$url,$template->post_title);
			}
		}
		?>

</div>

		<?php

}

function rsvpmaker_week( $index = 0, $context = '' ) {

	if ( $context == 'rsvpmaker_strtotime' ) {

		$weekarray = array( 'Varies', 'First', 'Second', 'Third', 'Fourth', 'Last', 'Every' );

	} else {

		$weekarray = array( __( 'Varies', 'rsvpmaker' ), __( 'First', 'rsvpmaker' ), __( 'Second', 'rsvpmaker' ), __( 'Third', 'rsvpmaker' ), __( 'Fourth', 'rsvpmaker' ), __( 'Last', 'rsvpmaker' ), __( 'Every', 'rsvpmaker' ) );

	}

	return $weekarray[ $index ];

}
function rsvpmaker_day( $index = 0, $context = '' ) {

	if ( $context == 'rsvpmaker_strtotime' ) {

		$dayarray = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

	} else {

		$dayarray = array( __( 'Sunday', 'rsvpmaker' ), __( 'Monday', 'rsvpmaker' ), __( 'Tuesday', 'rsvpmaker' ), __( 'Wednesday', 'rsvpmaker' ), __( 'Thursday', 'rsvpmaker' ), __( 'Friday', 'rsvpmaker' ), __( 'Saturday', 'rsvpmaker' ), '' );

	}

	return $dayarray[ $index ];

}
function rsvp_template_checkboxes( $t ) {
		$templates = rsvpmaker_get_templates();

		global $wpdb;

		global $current_user,$rsvp_options;

		$nomeeting = $editlist = $add_one = $add_date_checkbox = $event_options = $updatelist = '';

		$template = get_template_sked( $t );

		$post = get_post( $t );

		$template_editor = false;

		if ( current_user_can( 'edit_others_rsvpmakers' ) ) {

			$template_editor = true;

		} else {

			$eds = get_post_meta( $t, '_additional_editors', false );

			$eds[] = $wpdb->get_var( "SELECT post_author FROM $wpdb->posts WHERE ID = $t" );

			$template_editor = in_array( $current_user->ID, $eds );

		}

		$template = get_template_sked( $t );

		$weeks = empty($template['week']) ? 0 : $template['week'];

		$dows = empty($template['dayofweek']) ? 0 : $template['dayofweek'];

		$hour = ( isset( $template['hour'] ) ) ? (int) $template['hour'] : 17;

		$minutes = isset( $template['minutes'] ) ? $template['minutes'] : '00';

		$terms = get_the_terms( $t, 'rsvpmaker-type' );

		if ( $terms && ! is_wp_error( $terms ) ) {

			$rsvptypes = array();

			foreach ( $terms as $term ) {

				$rsvptypes[] = $term->term_id;

			}
		}

		$cy = date( 'Y' );

		$cm = rsvpmaker_date( 'm' );

		$cd = rsvpmaker_date( 'j' );

		$schedule = '';

		if ( empty($weeks) || $weeks[0] == 0 ) {

			$schedule = __( 'Schedule Varies', 'rsvpmaker' );

		} else {

			foreach ( $weeks as $week ) {

				$schedule .= rsvpmaker_week( $week ) . ' ';
			}

			$schedule .= ' / ';

			if ( ! empty( $dows ) && is_array( $dows ) ) {

				foreach ( $dows as $dow ) {

					$schedule .= rsvpmaker_day( $dow ) . ' ';
				}
			}
		}

		if(!empty($template['stop']))
		{
			$schedule .= ' - stop date set for '.$template['stop'] .' (<a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$t).'">Options</a>)';
		}

		printf( '<p id="template_ck">%s:</p><h2>%s</h2><h3>%s</h3><blockquote><a href="%s">%s</a></blockquote>', __( 'Template', 'rsvpmaker' ), esc_html( $post->post_title ), $schedule, admin_url( 'post.php?action=edit&post=' . $t ), __( 'Edit Template', 'rsvpmaker' ) );

		$hour = (empty($template['hour'])) ? 12 : (int) $template['hour'];

		$minutes = empty($template['minutes']) ? '00' : $template['minutes'];

		$his = ( $hour < 10 ) ? '0' . $hour : $hour;

		$his .= ':' . $minutes . ':00';

		$cy              = date( 'Y' );
		$template_editor = false;

		if ( current_user_can( 'edit_others_rsvpmakers' ) ) {

			$template_editor = true;

		} else {

			$eds = get_post_meta( $t, '_additional_editors', false );

			$eds[] = $wpdb->get_var( "SELECT post_author FROM $wpdb->posts WHERE ID = $t" );

			$template_editor = in_array( $current_user->ID, $eds );

		}

		$cm = rsvpmaker_date( 'm' );

		$cd = rsvpmaker_date( 'j' );

		global $current_user;

		$sched_result = get_events_by_template( $t );
		$holidays = commonHolidays();
		$add_date_checkbox = $updatelist = $editlist = $nomeeting = '';

		if ( $sched_result ) {

			foreach ( $sched_result as $index => $sched ) {

				$thistime = rsvpmaker_strtotime( $sched->datetime );
				$holiday_check = rsvpmaker_holiday_check($thistime,$holidays);
				$fulldate = rsvpmaker_date( $rsvp_options['long_date'] . ' ' . $rsvp_options['time_format'], $thistime );

				$a = ( $index % 2 ) ? '' : 'alternate';

				$tparts = preg_split( '/\s+/', $sched->datetime );

				if ( $his != $tparts[1] ) {

					$newtime = str_replace( $tparts[1], $his, $sched->datetime );

					$timechange = sprintf( '<input type="hidden" name="timechange[%d]" value="%s" />', $sched->ID, $newtime );

					if ( empty( $timechangemessage ) ) {

						$timechangemessage = '<p>' . __( 'Start time for updated events will be changed to', 'rsvpmaker' ) . ' ' . rsvpmaker_date( $rsvp_options['time_format'], rsvpmaker_strtotime( $newtime ) );
						echo wp_kses_post($timechangemessage);
					}
				} else {
					$timechange = '';
				}

				$donotproject[] = rsvpmaker_date( 'Y-m-j', $thistime );

				$nomeeting .= sprintf( '<option value="%s">%s (%s)</option>', $sched->postID, date( 'F j, Y', $thistime ), __( 'Already Scheduled', 'rsvpmaker' ) );

				$cy = date( 'Y', $thistime ); // advance starting time

				$cm = rsvpmaker_date( 'm', $thistime );

				$cd = rsvpmaker_date( 'j', $thistime );

				if ( strpos( $sched->post_title, 'o Meeting:' ) ) {

					$sched->post_title = '<span style="color:red;">' . $sched->post_title . '</span>';
				}

				if ( current_user_can( 'delete_post', $sched->postID ) ) {

					$delete_text = __( 'Move to Trash' );

					$d = '<input type="checkbox" name="trash_template[]" value="' . $sched->postID . '" class="trash_template" /> <a class="submitdelete deletion" href="' . get_delete_post_link( $sched->postID ) . '">' . esc_html( $delete_text ) . '</a>';

				} else {
					$d = '-';
				}

				$ifdraft = ( $sched->post_status == 'draft' ) ? ' (draft) ' : '';

				$edit = ( ( $sched->post_author == $current_user->ID ) || $template_editor ) ? sprintf( '<a href="%s?post=%d&action=edit">' . __( 'Edit', 'rsvpmaker' ) . '</a>', admin_url( 'post.php' ), $sched->postID ) : '-';

				$schedoptions = sprintf( ' (<a href="%s">Options</a>)', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=' ) . $sched->ID );

				$editlist .= sprintf( '<tr class="%s"><td><input type="checkbox" name="update_from_template[]" value="%s" class="update_from_template" /> %s </td><td>%s</td><td><input type="checkbox" name="detach_from_template[]" value="%d" /> </td><td>%s</td><td>%s</td><td><a href="%s">%s</a></td></tr>', $a, $sched->postID, $timechange, $edit, $sched->postID, $d, rsvp_x_day_month($thistime), get_post_permalink( $sched->postID ), $sched->post_title . $ifdraft . $schedoptions );
				$template_update = get_post_meta( $sched->postID, '_updated_from_template', true );

				if ( ! empty( $template_update ) && ( $template_update != $sched->post_modified ) ) {
					$mod = ' <span style="color:red;">* ' . __( 'Modified independently of template. Update could overwrite customizations.', 'rsvpmaker' ) . '</span> ' . sprintf( '<input type="checkbox" name="detach_from_template[]" value="%d" /> ', $sched->postID ) . __( 'Detach', 'rsvpmaker' );
				} else {
					$mod = '';
				}

				// $sametime_events
				$mod        .= rsvpmaker_sametime( $sched->datetime, $sched->ID );
				$hwarn = ($holiday_check) ? $holiday_check['hwarn'] : '';
				$updatelist .= sprintf( '<p class="%s"><input type="checkbox" name="update_from_template[]" value="%s"  class="update_from_template" /><em>%s</em> %s <span class="updatedate">%s</span> %s %s %s</p>', $a, $sched->postID, __( 'Update', 'rsvpmaker' ), $sched->post_title . $ifdraft, $fulldate, $hwarn, $mod, $timechange );
			}
		}

		if ( ! empty( $updatelist ) ) {

			$updatelist = '<p>' . __( 'Already Scheduled' ) . "</p>\n" . '<fieldset>

<div><input type="checkbox" class="checkall"> ' . __( 'Check all', 'rsvpmaker' ) . '</div>' . "\n"
.'<div><input type="checkbox" name="metadata_only" value="1" />' . __( 'Update Metadata Only', 'rsvpmaker' ) . '</div>'

			. $updatelist . "\n</fieldset>\n";
		}

		// missing template variable

		// problem call

		// print_r($template);

		$projected = rsvpmaker_get_projected( $template );
		if(isset($_GET['debug']))
		print_r($projected);
		if ( $projected && is_array( $projected ) ) {
			foreach ( $projected as $i => $ts ) {
				ob_start();

				$today = rsvpmaker_date( 'd', $ts );

				$cm = rsvpmaker_date( 'n', $ts );

				$y = date( 'Y', $ts );

				$y0 = $y - 1;

				$y2 = $y + 1;

				if ( ( $ts < current_time( 'timestamp' ) ) && ! isset( $_GET['start'] ) ) {

					continue; // omit dates past
				}

				if ( isset( $donotproject ) && is_array( $donotproject ) && in_array( rsvpmaker_date( 'Y-m-j', $ts ), $donotproject ) ) {

					continue;
				}

				$holiday_check = rsvpmaker_holiday_check($ts,$holidays);
				$hwarn = ($holiday_check) ? $holiday_check['hwarn'] : '';

				if ( empty( $nomeeting ) ) {
					$nomeeting = '';
				}

				$nomeeting .= sprintf( '<option value="%s">%s</option>', date( 'Y-m-d', $ts ), date( 'F j, Y', $ts ) );

				?>

<div style="font-family:Courier, monospace"><input name="recur_check[<?php echo esc_attr($i); ?>]" type="checkbox" class="update_from_template" value="1">

				<?php esc_html_e( 'Month', 'rsvpmaker' ); ?>: 

			  <select name="recur_month[<?php echo esc_attr($i); ?>]"> 

			  <option value="<?php echo esc_attr($cm); ?>"><?php echo esc_attr($cm); ?></option> 

			  <option value="1">1</option> 

			  <option value="2">2</option> 

			  <option value="3">3</option> 

			  <option value="4">4</option> 

			  <option value="5">5</option> 

			  <option value="6">6</option> 

			  <option value="7">7</option> 

			  <option value="8">8</option> 

			  <option value="9">9</option> 

			  <option value="10">10</option> 

			  <option value="11">11</option> 

			  <option value="12">12</option> 

			  </select> 

				<?php esc_html_e( 'Day', 'rsvpmaker' ); ?> 

			<select name="recur_day[<?php echo esc_attr($i); ?>]"> 

				<?php

				echo sprintf( '<option value="%s">%s</option>', $today, $today );

				?>

			  <option value="">Not Set</option>

			  <option value="1">1</option> 

			  <option value="2">2</option> 

			  <option value="3">3</option> 

			  <option value="4">4</option> 

			  <option value="5">5</option> 

			  <option value="6">6</option> 

			  <option value="7">7</option> 

			  <option value="8">8</option> 

			  <option value="9">9</option> 

			  <option value="10">10</option> 

			  <option value="11">11</option> 

			  <option value="12">12</option> 

			  <option value="13">13</option> 

			  <option value="14">14</option> 

			  <option value="15">15</option> 

			  <option value="16">16</option> 

			  <option value="17">17</option> 

			  <option value="18">18</option> 

			  <option value="19">19</option> 

			  <option value="20">20</option> 

			  <option value="21">21</option> 

			  <option value="22">22</option> 

			  <option value="23">23</option> 

			  <option value="24">24</option> 

			  <option value="25">25</option> 

			  <option value="26">26</option> 

			  <option value="27">27</option> 

			  <option value="28">28</option> 

			  <option value="29">29</option> 

			  <option value="30">30</option> 

			  <option value="31">31</option> 

			</select> 

				<?php esc_html_e( 'Year', 'rsvpmaker' ); ?>

			<select name="recur_year[<?php echo esc_attr($i); ?>]"> 

			<option value="<?php echo esc_attr($y0); ?>"><?php echo esc_attr($y0); ?></option> 

			  <option value="<?php echo esc_attr($y); ?>" selected="selected"><?php echo esc_attr($y); ?></option> 

			  <option value="<?php echo esc_attr($y2); ?>"><?php echo esc_attr($y2); ?></option> 

			</select>

			<input type="text" name="recur_title[<?php echo esc_attr($i); ?>]" value="<?php echo esc_html( $post->post_title ); ?>" >
				<?php echo rsvpmaker_sametime( rsvpmaker_date( 'Y-m-d H:i:s', $ts ) );
				echo $hwarn;
				?>
</div>

				<?php

				$add_date_checkbox .= ob_get_clean();

				if ( empty( $add_one ) ) {

					$add_one = str_replace( 'type="checkbox"', 'type="hidden"', $add_date_checkbox );
				}
			} // end for loop
		}

		$action = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $t );

		if ( empty( $updatelist ) ) {
			$updatelist = '';
		}

		if(isset($_GET['other'])) {
			$future = rsvpmaker_get_future_events(50);
			$updatelist .= '<h3>Other Events</h3>';
			foreach($future as $f) {
				$otherrecur = get_post_meta($f->ID,'_meet_recur',true);
				$different = '';
				if($otherrecur && ($otherrecur != $t))
				{
					$title = get_the_title($otherrecur);
					if($title) {
						$different = __('Template','rsvpmaker-for-toastmasters').': '.$title;
					}
					$updatelist .= sprintf('<p><input type="checkbox" name="update_from_template[]" value="%d" /> %s %s %s</p>',$f->ID,$f->post_title,$f->date,$different);
				}
			}
		}
		else {
			$updatelist .= sprintf('<div><a href="%s&other=1">%s</a></div>',$action,__('Show other events (not based on this template)','rsvpmaker'));	
		}

		if ( current_user_can( 'edit_rsvpmakers' ) ) {

			printf(
				'<div class="group_add_date"><br />

<form method="post" action="%s">

%s

<div><strong>' . __( 'Projected Dates', 'rsvpmaker' ) . ':</strong></div>

<fieldset>

<div><input type="checkbox" class="checkall"> ' . __( 'Check all', 'rsvpmaker' ) . '</div>

%s

</fieldset>

<br />' . __( 'New Post Status', 'rsvpmaker' ) . ': <input name="newstatus" type="radio" value="publish" checked="checked" /> publish <input name="newstatus" type="radio" value="draft" /> draft<br />
<br /><input type="submit" value="' . __( 'Add/Update From Template', 'rsvpmaker' ) . '" />

<input type="hidden" name="template" value="%s" />
%s
</form>

</div><br />',
				$action,
				$updatelist,
				$add_date_checkbox,
				$t,
				rsvpmaker_nonce('return')
			);
		}

		if ( isset( $_GET['trashed'] ) ) {

				$ids = (int) $_GET['ids'];

				$message = '<a href="' . esc_url( wp_nonce_url( "edit.php?post_type=rsvpmaker&doaction=undo&action=untrash&ids=$ids", 'bulk-posts' ) ) . '">' . __( 'Undo' ) . '</a>';

				echo '<div id="message" class="updated"><p>' . __( 'Moved to trash', 'rsvpmaker' ) . ' ' . $message . '</p></div>';

		}

		$projected = rsvpmaker_get_projected( $template );

		if ( $projected && is_array( $projected ) ) {
			foreach ( $projected as $i => $ts ) {

				$today = rsvpmaker_date( 'd', $ts );

				$cm = rsvpmaker_date( 'n', $ts );

				$y = date( 'Y', $ts );

				$y2 = $y + 1;

				ob_start();

				if ( ( $ts < current_time( 'timestamp' ) ) && ! isset( $_GET['start'] ) ) {

					continue; // omit dates past
				}

				if ( isset( $donotproject ) && is_array( $donotproject ) && in_array( date( 'Y-m-j', $ts ), $donotproject ) ) {

					continue;
				}

				$nomeeting .= sprintf( '<option value="%s">%s</option>', date( 'Y-m-d', $ts ), date( 'F j, Y', $ts ) );

				?>

<div style="font-family:Courier, monospace"><input name="recur_check[<?php echo esc_attr($i); ?>]" type="checkbox" value="1">

				<?php esc_html_e( 'Month', 'rsvpmaker' ); ?>: 

			  <select name="recur_month[<?php echo esc_attr($i); ?>]"> 

			  <option value="<?php echo esc_attr($cm); ?>"><?php echo esc_attr($cm); ?></option> 

			  <option value="1">1</option> 

			  <option value="2">2</option> 

			  <option value="3">3</option> 

			  <option value="4">4</option> 

			  <option value="5">5</option> 

			  <option value="6">6</option> 

			  <option value="7">7</option> 

			  <option value="8">8</option> 

			  <option value="9">9</option> 

			  <option value="10">10</option> 

			  <option value="11">11</option> 

			  <option value="12">12</option> 

			  </select> 

				<?php esc_html_e( 'Day', 'rsvpmaker' ); ?> 

			<select name="recur_day[<?php echo esc_attr($i); ?>]"> 

				<?php

				printf( '<option value="%s">%s</option>', $today, $today );

				?>

			  <option value=""><?php esc_html_e( 'Not Set', 'rsvpmaker' ); ?></option>

			  <option value="1">1</option> 

			  <option value="2">2</option> 

			  <option value="3">3</option> 

			  <option value="4">4</option> 

			  <option value="5">5</option> 

			  <option value="6">6</option> 

			  <option value="7">7</option> 

			  <option value="8">8</option> 

			  <option value="9">9</option> 

			  <option value="10">10</option> 

			  <option value="11">11</option> 

			  <option value="12">12</option> 

			  <option value="13">13</option> 

			  <option value="14">14</option> 

			  <option value="15">15</option> 

			  <option value="16">16</option> 

			  <option value="17">17</option> 

			  <option value="18">18</option> 

			  <option value="19">19</option> 

			  <option value="20">20</option> 

			  <option value="21">21</option> 

			  <option value="22">22</option> 

			  <option value="23">23</option> 

			  <option value="24">24</option> 

			  <option value="25">25</option> 

			  <option value="26">26</option> 

			  <option value="27">27</option> 

			  <option value="28">28</option> 

			  <option value="29">29</option> 

			  <option value="30">30</option> 

			  <option value="31">31</option> 

			</select> 

				<?php esc_html_e( 'Year', 'rsvpmaker' ); ?>

			<select name="recur_year[<?php echo esc_attr($i); ?>]"> 

			  <option value="<?php echo esc_attr($y); ?>"><?php echo esc_attr($y); ?></option> 

			  <option value="<?php echo esc_attr($y2); ?>"><?php echo esc_attr($y2); ?></option> 

			</select>

</div>
				<?php

				$add_date_checkbox .= ob_get_clean();

				if ( empty( $add_one ) ) {

					$add_one = str_replace( 'type="checkbox"', 'type="hidden"', $add_date_checkbox );
				}
			} // end for loop
		}

		$action = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $t );

		if ( $editlist ) {

			do_action( 'update_from_template_prompt' );

			echo '<strong>' . __( 'Already Scheduled', 'rsvpmaker' ) . ':</strong><br /><br /><form method="post" action="' . $action . '">

<fieldset>

<table  class="wp-list-table widefat fixed posts" cellspacing="0">

<thead>

<tr><th class="manage-column column-cb check-column" scope="col" ><input type="checkbox" class="checkall" title="Check all"></th><th>' . __( 'Edit' ) . '</th><th>' . __( 'Detach' ) . '</th><th><input type="checkbox" class="trashall" title="Trash all"> ' . __( 'Move to Trash' ) . '<th>' . __( 'Date' ) . '</th><th>' . __( 'Title' ) . '</th></tr>

</thead>

<tbody>

' . $editlist . '

</tbody></table>

</fieldset>

<input type="submit" value="' . __( 'Update Checked', 'rsvpmaker' ) .'" />'.rsvpmaker_nonce('return').'</form>' . '<p>' . __( 'Update function copies title and content of current template, replacing the existing content of checked posts.', 'rsvpmaker' ) . '</p>';

		}

		if ( current_user_can( 'edit_rsvpmakers' ) && ! empty( $add_one ) ) {

			do_action( 'add_from_template_prompt' );

			printf(
				'<div class="group_add_date"><br />

<form method="post" action="%s">

<strong>' . __( 'Add One', 'rsvpmaker' ) . ':</strong><br />

%s

<input type="hidden" name="rsvpmaker_add_one" value="1" />

<input type="hidden" name="template" value="%s" />

<br /><input type="submit" value="' . __( 'Add From Template', 'rsvpmaker' ) . '" />
%s
</form>

</div><br />',
				$action,
				$add_one,
				$t,
				rsvpmaker_nonce('return')
			);

		}

		if ( current_user_can( 'edit_rsvpmakers' ) ) {

			printf(
				'<div class="group_add_date"><br />

<form method="post" action="%s">

<strong>%s:</strong><br />

%s: <select name="nomeeting">%s</select>

<br />%s:<br /><textarea name="nomeeting_note" cols="60" %s></textarea>

<input type="hidden" name="template" value="%s" />

<br /><input type="submit" value="%s" />
%s
</form>

</div><br />

',
				$action,
				__( 'No Meeting', 'rsvpmaker' ),
				__( 'Regularly Scheduled Date', 'rsvpmaker' ),
				$nomeeting,
				__( 'Note (optional)', 'rsvpmaker' ),
				'style="max-width: 95%;"',
				$t,
				__( 'Submit', 'rsvpmaker' ),
				rsvpmaker_nonce('return')
			);
		}

		echo "<script>

jQuery(function () {

    jQuery('.checkall').on('click', function () {

	jQuery(this).closest('fieldset').find('.update_from_template:checkbox').prop('checked', this.checked);

    });

    jQuery('.trashall').on('click', function () {

	jQuery(this).closest('fieldset').find('.trash_template:checkbox').prop('checked', this.checked);

    });

});

</script>

";
	rsvpmaker_number_events_ui($t);

}

function rsvpmaker_updated_messages( $messages ) {

		if ( empty( $messages ) ) {

			return;
		}

		global $post;

		$post_ID = $post->ID;

		if ( $post->post_type != 'rsvpmaker' ) {
			return; // only for RSVPMaker
		}

		$singular = __( 'Event', 'rsvpmaker' );

		$link = sprintf( ' <a href="%s">%s %s</a>', esc_url( get_post_permalink( $post_ID ) ), __( 'View', 'rsvpmaker' ), $singular );

		$sked = get_template_sked( $post_ID );

		if ( ! empty( $sked ) ) {

			$singular = __( 'Event Template', 'rsvpmaker' );

			$link = sprintf( ' -> <a class="update_from_template" href="%s">%s</a>', esc_url( admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $post_ID ) ), __( 'Create/update events from template', 'rsvpmaker' ) );

		}

		$messages['rsvpmaker'] = array(

			0  => '', // Unused. Messages start at index 1.

			1  => $singular . ' ' . __( 'updated', 'rsvpmaker' ) . $link,

			2  => __( 'Custom field updated.' ),

			3  => __( 'Custom field deleted.' ),

			4  => $singular . ' ' . __( 'updated', 'rsvpmaker' ) . $link,
			/* translators: placeholder for singular name, title */
			5  => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s' ), $singular, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,

			6  => $singular . ' ' . __( 'published', 'rsvpmaker' ) . $link,

			7  => __( 'Page saved.' ),
			/* translators: placeholders for permalink, singular name */
			8  => $singular . sprintf( __( ' submitted. <a target="_blank" href="%1$s">Preview %2$s</a>' ), esc_url( add_query_arg( 'preview', 'true', get_post_permalink( $post_ID ) ) ), strtolower( $singular ) ),
			/* translators: placeholders for date, preview link, singular name */
			9  =>  $singular . sprintf( __(' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %3$s</a>' ), date_i18n( __( 'M j, Y @ G:i' ), rsvpmaker_strtotime( $post->post_date ) ), esc_url( get_post_permalink( $post_ID ) ), strtolower( $singular ) ),
			/* translators: placeholders for preview link, singular name */
			10 => $singular . sprintf( __( ' draft updated. <a target="_blank" href="%1$s">Preview %2$s</a>' ), esc_url( add_query_arg( 'preview', 'true', get_post_permalink( $post_ID ) ) ), strtolower( $singular ) ),

		);

		return $messages;

	}

function rsvpmaker_template_admin_title() {

		global $title;

		global $post;

		global $post_new_file;

		if ( ! isset( $post ) || ( $post->post_type != 'rsvpmaker' ) ) {

			return;
		}

		if ( ! empty( $_GET['new_template'] ) || get_template_sked( $post->ID ) ) {

			$title .= ' ' . __( 'Template', 'rsvpmaker' );

			if ( isset( $post_new_file ) ) {

				$post_new_file = 'post-new.php?post_type=rsvpmaker&new_template=1';
			}
		}

}

function next_or_recent( $template_id ) {

		global $wpdb;

		global $rsvp_options;
		$event_table = get_rsvpmaker_event_table();

		$event = '';

		$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, $event_table.*, a2.meta_value as template

	 FROM " . $wpdb->posts . '

	 JOIN ' . $event_table . ' ON ' . $wpdb->posts . '.ID ='.$event_table.'.event

	 JOIN ' . $wpdb->postmeta . ' a2 ON ' . $wpdb->posts . ".ID =a2.post_id 

	 WHERE date > '" . get_sql_curdate() . "' AND a2.meta_key='_meet_recur' AND a2.meta_value=" . $template_id . " AND post_status='publish'

	 ORDER BY date LIMIT 0,1";

		if ( $row = $wpdb->get_row( $sql ) ) {

			$t = rsvpmaker_strtotime( $row->datetime );

			$neatdate = mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );

			$event = sprintf( '<a href="%s">%s: %s</a>', get_post_permalink( $row->postID ), __( 'Next Event', 'rsvpmaker' ), $neatdate );

		} else {

			$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, $event_table.*, a2.meta_value as template

			FROM " . $wpdb->posts . '

			JOIN ' . $event_talbe . ' ON ' . $wpdb->posts . '.ID ='.$event_table.'.event

			JOIN ' . $wpdb->postmeta . ' a2 ON ' . $wpdb->posts . ".ID =a2.post_id 

			WHERE date < '" . get_sql_curdate() . "' AND a2.meta_key='_meet_recur' AND a2.meta_value=" . $template_id . " AND post_status='publish'

			ORDER BY date LIMIT 0,1";

			if ( $row = $wpdb->get_row( $sql ) ) {

				$t = rsvpmaker_strtotime( $row->datetime );

				$neatdate = mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );

				$event = sprintf( '<a style="color:#333;" href="%s">%s: %s</a>', get_post_permalink( $row->postID ), __( 'Most Recent', 'rsvpmaker' ), $neatdate );

			}
		}

		return $event;

}

if ( isset( $_GET['message'] ) ) {
	add_filter( 'post_updated_messages', 'rsvpmaker_updated_messages' );
}

function additional_editors_setup() {

	global $rsvp_options;

	if ( isset( $rsvp_options['additional_editors'] ) && $rsvp_options['additional_editors'] ) {

		add_action( 'save_post', 'save_additional_editor' );

		// add_filter( 'user_has_cap', 'rsvpmaker_cap_filter', 99, 3 );

		add_filter( 'map_meta_cap', 'rsvpmaker_map_meta_cap', 10, 4 );

	}

}

function rsvpmaker_cap_filter_test( $cap, $post_id ) {

		if ( strpos( $cap, 'rsvpmaker' ) ) {

			return true;

		} elseif ( $post = get_post( $post_id ) ) {

			if ( isset( $post->post_type ) && ( $post->post_type == 'rsvpmaker' ) ) {

				return true;

			} else {
				return false;
			}
		} else {
			return false;
		}

}

function get_additional_editors( $post_id ) {

		global $wpdb;

		$eds = array();

		$recurid = get_post_meta( $post_id, '_meet_recur', true );

		if ( $recurid ) {

			$eds = get_post_meta( $recurid, '_additional_editors', false );

		}

		$post_eds = get_post_meta( $post_id, '_additional_editors', false );

		if ( is_array( $post_eds ) ) {

			foreach ( $post_eds as $this_eds ) {

				if ( ! in_array( $this_eds, $eds ) ) {

					$eds[] = $this_eds;
				}
			}
		}

		return $eds;

}

function save_additional_editor( $post_id ) {

		if ( ! empty( $_POST['additional_editor'] ) || ! empty( $_POST['remove_editor'] ) ) {

			if ( $parent_id = wp_is_post_revision( $post_id ) ) {

				$post_id = $parent_id;

			}
		}

		if ( ! empty( $_POST['additional_editor'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

			$ed = (int) $_POST['additional_editor'];

			if ( $ed ) {

				add_post_meta( $post_id, '_additional_editors', $ed, false );
			}
		}

		if ( ! empty( $_POST['remove_editor'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

			foreach ( $_POST['remove_editor'] as $remove ) {

				$remove = (int) $remove;

				if ( $remove ) {

					delete_post_meta( $post_id, '_additional_editors', $remove );
				}
			}
		}

}

function rsvpmaker_editor_dropdown( $eds ) {

		global $wpdb;

		$options = '';

		$sql = "SELECT * FROM $wpdb->users ORDER BY user_login";

		$results = $wpdb->get_results( $sql );

		if ( is_array( $results ) ) {

			foreach ( $results as $row ) {

				if ( in_array( $row->ID, $eds ) ) {

					continue;
				}

				$member = get_userdata( $row->ID );

				$index = preg_replace( '/[^a-zA-Z]/', '', $member->last_name . $member->first_name . $row->user_login );

				$sortmember[ $index ] = $member;

			}
		}

		ksort( $sortmember );

		foreach ( $sortmember as $index => $member ) {

			if ( isset( $member->last_name ) && ! empty( $member->last_name ) ) {

				$label = $member->first_name . ' ' . $member->last_name;

			} else {
				$label = $index;
			}

			$label = esc_html( $label );

			if ( $member->ID == $assigned ) {

				$s = ' selected="selected" ';

			} else {
				$s = '';
			}

			$options .= sprintf( '<option %s value="%d">%s</option>', $s, $member->ID, esc_html( $label ) );

		}

		return $options;

}

function additional_editors() {

		global $post;

		global $custom_fields;

		if ( $post->ID ) {

			$eds = get_post_meta( $post->ID, '_additional_editors', false );
		}

		if ( $eds ) {

			echo '<strong>' . __( 'Editors', 'rsvpmaker' ) . ':</strong><br />';

			foreach ( $eds as $user_id ) {

				$member = get_userdata( $user_id );

				if ( isset( $member->last_name ) && ! empty( $member->last_name ) ) {

					$label = $member->first_name . ' ' . $member->last_name;

				} else {
					$label = $member->user_login;
				}

				$label .= ' ' . $member->user_email;

				echo esc_html( $label ) . sprintf( ' <strong>( <input type="checkbox" name="remove_editor[]" value="%d"> %s)</strong><br />', $user_id, __( 'Remove', 'rsvpmaker' ) );

			}
		}

		?>

<p><?php esc_html_e( 'Add Editor', 'rsvpmaker' ); ?>: <select name="additional_editor" ><option value=""><?php esc_html_e( 'Select' ); ?></option><?php echo rsvpmaker_editor_dropdown( $eds ); ?></select></p>

		<?php

		if ( isset( $custom_fields['_meet_recur'][0] ) ) {
		}

		{

		echo '<strong>' . __( 'Template', 'rsvpmaker' ) . ' ' . __( 'Editors', 'rsvpmaker' ) . ':</strong><br />';

		$t = isset( $custom_fields['_meet_recur'][0] ) ? $custom_fields['_meet_recur'][0] : 0;

		$eds = get_post_meta( $t, '_additional_editors', false );

		if ( $eds ) {

			foreach ( $eds as $user_id ) {

				$member = get_userdata( $user_id );

				if ( isset( $member->last_name ) && ! empty( $member->last_name ) ) {

					$label = $member->first_name . ' ' . $member->last_name;

				} else {
					$label = $member->user_login;
				}

				echo esc_html( $label ) . '<br />';

			}
		} else {
			esc_html_e( 'None', 'rsvpmaker' );
		}

		printf( '<p><a href="%s">' . __( 'Edit Template', 'rsvpmaker' ) . '</a></p>', admin_url( 'post.php?action=edit&post=' . $t ) );

		}

		do_action( 'rsvpmaker_additional_editors' );

}

function rsvpmaker_dashboard_widget_function() {

		global $wpdb;

		global $rsvp_options;

		global $current_user;

		// $wpdb->show_errors();

		do_action( 'rsvpmaker_dashboard_action' );

		if ( isset( $rsvp_options['dashboard_message'] ) && ! empty( $rsvp_options['dashboard_message'] ) ) {

			echo '<div>' . wp_kses_post( $rsvp_options['dashboard_message'] ) . '</div>';
		}

		echo '<p><strong>' . __( 'My Events', 'rsvpmaker' ) . '</strong><br /></p>';

		$results = rsvpmaker_get_future_events( 'post_author=' . $current_user->ID );

		if ( $results ) {

			foreach ( $results as $index => $row ) {

				$draft = ( $row->post_status == 'draft' ) ? ' (draft)' : '';

				printf( '<p><a href="%s">(' . __( 'Edit', 'rsvpmaker' ) . ')</a> <a href="%s">%s %s%s</a></p>', admin_url( 'post.php?action=edit&post=' . $row->ID ), get_post_permalink( $row->ID ), esc_html( $row->post_title ), mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $row->datetime ) ), 'UTF-8' ), $draft );

				if ( $index == 10 ) {

					printf( '<p><a href="%s">&gt; &gt; ' . __( 'More', 'rsvpmaker' ) . '</a></p>', admin_url( 'edit.php?post_type=rsvpmaker&rsvpsort=chronological&author=' . $current_user->ID ) );

					break;

				}
			}
		} else {

			'<p>' . __( 'None', 'rsvpmaker' ) . '</p>';

		}

		printf( '<p><a href="%s">' . __( 'Add Event', 'rsvpmaker' ) . '</a></p>', admin_url( 'post-new.php?post_type=rsvpmaker' ) );

		$sql = "SELECT $wpdb->posts.ID as editid FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id 

WHERE $wpdb->posts.post_type = 'rsvpmaker' AND $wpdb->postmeta.meta_key = '_additional_editors' AND $wpdb->postmeta.meta_value = $current_user->ID";

		$wpdb->show_errors();

		$result = $wpdb->get_results( $sql );

		$r2 = rsvpmaker_get_templates( 'AND post_author=$current_user->ID' );// $wpdb->get_results($sql);

		if ( $result && $r2 ) {

			$result = array_merge( $r2, $result );

		} elseif ( $r2 ) {

			$result = $r2;
		}

		if ( $result ) {

			foreach ( $result as $row ) {

				rsvp_template_checkboxes( $row->editid );

			}
		}

}

function rsvpmaker_add_dashboard_widgets() {

	global $rsvp_options;

	wp_add_dashboard_widget( 'rsvpmaker_dashboard_widget', __( 'Events', 'rsvpmaker' ), 'rsvpmaker_dashboard_widget_function' );

	if ( empty( $rsvp_options['dashboard'] ) || ( $rsvp_options['dashboard'] != 'top' ) ) {

		return;
	}

	// Globalize the metaboxes array, this holds all the widgets for wp-admin

	global $wp_meta_boxes;

	// Get the regular dashboard widgets array

	// (which has our new widget already but at the end)

	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

	// Backup and delete our new dashbaord widget from the end of the array

	$rsvpmaker_widget_backup = array(
		'rsvpmaker_dashboard_widget' =>

				$normal_dashboard['rsvpmaker_dashboard_widget'],
	);

	unset( $normal_dashboard['rsvpmaker_dashboard_widget'] );

	// Merge the two arrays together so our widget is at the beginning

	$sorted_dashboard = array_merge( $rsvpmaker_widget_backup, $normal_dashboard );

	// Save the sorted array back into the original metaboxes

	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;

}

function get_rsvpmaker_multievent_discount_code() {
	$code = get_option('rsvpmaker_multievent_discount_code');
	if(!$code)
	{
		$code = 'multievent_discount_code'.wp_generate_password(20,false,false);
		update_option('rsvpmaker_multievent_discount_code',$code);
	}
	return $code;
}

function rsvpmaker_check_coupon_code( $price, $postdata, $participants ) {

	global $post;

	global $rsvpmaker_coupon_message, $rsvpmaker_codes;

	$rsvpmaker_codes = get_post_meta( $post->ID, '_rsvp_coupon_code' ); // array of codes
	$user_code = (empty($postdata['coupon_code'])) ? '' : trim($postdata['coupon_code']);
	$multievent_code = get_rsvpmaker_multievent_discount_code();
	$log = '';

	if($multievent_code == $user_code) {
		$log .= ' multievent_discount_code check';
		if(!in_array($user_code, $rsvpmaker_codes)){
			$rsvpmaker_codes[] = $user_code;
			update_post_meta($post->ID,'_rsvp_coupon_code',$rsvpmaker_codes);
		}
		if(empty($postdata['multievent_discount_amount'])) {
			$discount = get_post_meta($post->ID,'_multievent_discount_amount',true);
			$log .= ' get discount '.$discount;
		}
		else
		{
			$discount = floatval($postdata['multievent_discount_amount']);
			update_post_meta($post->ID,'_multievent_discount_amount',$discount);
		}
		if(empty($postdata['multievent_discount_method']))
			{
			$method = get_post_meta($post->ID,'_multievent_discount_method',true);
			$log .= ' get method '.$method;
			}
		else
		{
			$method = sanitize_text_field($postdata['multievent_discount_method']);
			update_post_meta($post->ID,'_multievent_discount_method',$method);
		}
	}
	elseif ( ! empty( $rsvpmaker_codes ) && ! empty( $user_code )) {
		$log .= ' rsvpmaker codes check';

		if ( ( in_array( $user_code, $rsvpmaker_codes ) ) ) {

			$index = array_search( $user_code, $rsvpmaker_codes );

			if(empty($discounts))
			$discounts = get_post_meta( $post->ID, '_rsvp_coupon_discount' );

			if(empty($methods))
			$methods = get_post_meta( $post->ID, '_rsvp_coupon_method' );

			$discount = (float) $discounts[ $index ];

			$method = $methods[ $index ];
		}
	}

	if(!empty($discount) && !empty($method)) {
		$rsvpmaker_coupon_message = 'Coupon code applied: ' . $user_code;
		$log .= ' price before coupon '.$price;
		if( $method == 'totalamount') {
			$log .= ' amount total adjustment '.$discount;
			$price = $price - $discount;
			if($price < 0)
				$price = 0;//gift certificate larger than purchase price
		} else {
			if ( $method == 'percent' ) {
				$price = $price - ( $price * $discount );

			} elseif( $method == 'amount' ) {
				$log .= ' amount per adjustment '.$discount.' on '.$price;
				$price = $price - ($discount * $participants);
			}	
		}
		$log .= ' coupon adjusted price '.$price;
	} elseif(empty($rsvpmaker_coupon_message)) {
		$rsvpmaker_coupon_message = 'Coupon code not recognized';
	}

	return $price;

}

function rsvpmaker_check_unpaid($post_id, $rsvp_id) {
	global $wpdb;
	$unpaid = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key='rsvpmaker_unpaid' ORDER BY meta_id DESC ");
	foreach($unpaid as $unp) {
		$unpaid_record = unserialize($unp->meta_value);
		if($unpaid_record[0]->id == $rsvp_id)
			return $unpaid_record;
	}
}

function rsvpmaker_check_openings( $post_id, $party_size ) {
	global $rsvp_options, $wpdb;
	if(!get_post_meta($post_id,'_rsvp_on',true)) {
		return false;
	}
	$rsvp_max = get_post_meta($post_id,'_rsvp_max',true);
	$rsvp_start = (int) get_post_meta($post_id,'_rsvp_start',true);
	$now = time();
	if($rsvp_start && ($now < $rsvp_start)) {
		return false;
	}
	if ( !is_rsvpmaker_deadline_future( $post_id) ) {
		return false;
	} 	
	if($rsvp_max) {
		$sql = 'SELECT count(*) FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$post_id AND yesno=1";
		$total = $wpdb->get_var( $sql );
		if( $total + $party_size >= $rsvp_max ) {
			return false;
		}
	}
	return true;
}