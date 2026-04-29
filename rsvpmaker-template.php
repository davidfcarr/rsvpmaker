<?php


// Migrated from rsvpmaker-plugabble.php
function rsvpmaker_template_schedule( $template ) {
		global $post;
		$sked = rsvpmaker_get_template_sked( $post->ID );
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

	rsvpmaker_new_template_schedule( $post_id, $sked );

	if ( isset( $_POST['rsvpautorenew'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		update_post_meta( $post_id, 'rsvpautorenew', 1 );
	} else {
		delete_post_meta( $post_id, 'rsvpautorenew' );
	}
}

function rsvpmaker_template_list() {

		global $rsvp_options, $wpdb, $current_user, $post;

		?>

<div class="wrap"> 

		<?php

		$heading = __( 'Create / Update from Template', 'rsvpmaker' );

		rsvpmaker_admin_heading($heading, __FUNCTION__);

        if(isset($_POST['add_template_to'])) {
            if(empty($_POST['rsvpnonce']) || !wp_nonce_verify(sanitize_text_field($_POST['rsvpnonce']),'rsvpmaker'))
            {
                echo '<div class="notice notice-error"><p>Security error</p></div>';
                return;
            }
            $target = intval($_POST['template_target']);
            foreach($_POST['add_template_to'] as $to) {
                $to = intval($to);
                printf('add post meta %d to %d',$target,$to);
                add_post_meta($to,'_meet_recur',$target);
            }
        }

		if ( ! empty( $_POST['import_shared_template'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  ) {

			$url = sanitize_text_field( $_POST['import_shared_template'] );

			printf( '<p>Importing %s</p>', $url );

			$duplicate = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM %i posts JOIN %i meta ON posts.ID = meta.post_id  WHERE meta_key='template_imported_from' AND meta_value=%s ", $wpdb->posts, $wpdb->postmeta, $url) );

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



			$sk = rsvpmaker_get_template_sked( $overridden );



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

			$sql = $wpdb->prepare( "UPDATE %i SET meta_key='_detached_from_template' WHERE meta_key='_meet_recur' AND post_id=%d",$wpdb->postmeta, $overridden );

			$wpdb->query( $sql );

			update_post_meta( $overridden, '_meet_recur', $override );



			printf( '<div class="updated notice notice-success">Applied "%s" template: <a href="%s">View</a> | <a href="%s">Edit</a></div>', $opost->post_title, get_permalink( $overridden ), admin_url( 'post.php?action=edit&post=' . $overridden ) );



			$params = [$wpdb->postmeta,$override];



			if(isset($_POST['copymeta']))

			{

				$sql = "select * from %i WHERE post_id=%d ";

				foreach($_POST['copymeta'] as $key)

				{

					$key = sanitize_text_field($key);

					if(empty($keysql))

						$keysql = '';

					else

						$keysql .= ' OR ';

					$keysql .= "  meta_key LIKE %s ";//match multiple reminder messages

					$params[] = $wpdb->esc_like($key).'%';

				}

				if(!empty($keysql))

					$sql .= '( '.$keysql.' )';



				

				$results = $wpdb->get_results( $wpdb->prepare($sql,$params) );



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

				$results = $wpdb->get_results( $wpdb->prepare("select * from %i WHERE post_id=%d",$wpdb->postmeta,$e) );



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



			rsvpmaker_new_template_schedule( $t, $template );



			printf( '<h1>Template updated based on contents of event for %s</h1>', rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $ts ) ) );



			$sql = $wpdb->prepare("select * from %i WHERE post_id=%d", $wpdb->postmeta, $e);



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


		if ( isset( $_GET['new_template'] ) && isset( $_GET['event'] ) ) {


			$e = (int) $_GET['event'];
			$event = get_post( $e );
			$newpost = array(

				'post_title'   => $event->post_title,

				'post_content' => $event->post_content,

				'post_type'    => 'rsvpmaker_template',

				'post_author'  => $current_user->ID,

				'post_status'  => 'publish',

			);

			$t = wp_insert_post( $newpost );

				printf( '<div class="notice success notice-sodium_crypto_core_ristretto255_scalar_add( $x:string, $y:string )"><h1>Template created for %s</h1><p><a href="%s">Edit</a></p></div>', $event->post_title, get_edit_post_link( $t ) );

				$results = $wpdb->get_results( $wpdb->prepare("select * from %i WHERE post_id=%d",$wpdb->postmeta,$e) );
				$docopy = array( '_add_timezone', '_convert_timezone', '_calendar_icons', 'tm_sidebar', 'sidebar_officers' );

				if ( is_array( $results ) ) {

					foreach ( $results as $row ) {

						if ( ( strpos( $row->meta_key, 'rsvp' ) && ( $row->meta_key != '_rsvp_dates' ) ) || ( in_array( $row->meta_key, $docopy ) ) ) {

							update_post_meta( $t, $row->meta_key, $row->meta_value );

							$copied[] = $row->meta_key;

						}

					}
				}
			$ts = get_rsvp_date( $e );

			$tsexplode = preg_split( '/[\s:]/', $ts );
			$template = array(

				'week'      => array( 0 ),

				'dayofweek' => array( 0 ),

				'hour'      => $tsexplode[1],

				'minutes'   => $tsexplode[2],

			);

			rsvpmaker_new_template_schedule( $t, $template );

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



			rsvpmaker_new_template_schedule( $t, $template );



			printf( '<h1>Template updated based on contents of event for %s</h1>', rsvpmaker_date( $rsvp_options['long_date'], rsvpmaker_strtotime( $ts ) ) );



			$sql = $wpdb->prepare("select * from %i WHERE post_id=%d", $wpdb->postmeta, $e);



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



			rsvpmaker_new_template_schedule( $r, $sked );



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

				$sked = rsvpmaker_get_template_sked( $post->ID );



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

				$eds = rsvpmaker_get_additional_editors( $post->ID );



				if ( ( $post->post_author == $current_user->ID ) || in_array( $current_user->ID, $eds ) || current_user_can( 'edit_post', $post->ID ) ) {



					$template_edit_url = admin_url( 'post.php?action=edit&post=' . intval( $post->ID ) );



					$title = sprintf( '<a href="%s">%s</a>', esc_attr( $template_edit_url ), esc_html( $post->post_title ) );



					if ( strpos( $post->post_content, '[toastmaster' ) && function_exists( 'agenda_setup_url' ) ) { // rsvpmaker for toastmasters



						$title .= sprintf( ' (<a href="%s">Toastmasters %s</a>)', agenda_setup_url( $post->ID ), __( 'Agenda Setup', 'rsvptoast' ) );

					}


					$template_options .= sprintf( '<option value="%d" %s>%s</option>', $post->ID, (!empty($t) && $t == $post->ID) ? 'selected="selected"' : '', esc_html( $post->post_title ) );



					$template_override .= sprintf( '<option value="%d">APPLY TO TEMPLATE: %s</option>', $post->ID, esc_html( $post->post_title ) );



					$template_recur_url = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . intval( $post->ID ) );



					$schedoptions = sprintf( ' (<a href="%s">Options</a>)', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=' ) . intval( $post->ID ) );



					printf( '<tr><td>%s</td><td>%s</td><td><a href="%s">' . __( 'Create/Update', 'rsvpmaker' ) . '</a></td><td>%s</td></tr>' . "\n", wp_kses_post( $title ) . wp_kses_post( $schedoptions ), $s, esc_attr( $template_recur_url ), rsvpmaker_next_or_recent( $post->ID ) );



				} else {



					$title = $post->post_title;



					printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n", esc_html( $title ), $s, __( 'Not an editor', 'rsvpmaker' ), rsvpmaker_next_or_recent( $post->ID ) );



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



						$event_options .= sprintf( '<option value="%d" selected="selected">%s %s</option>', $event->event, esc_html( $event->post_title ), esc_html( rsvpmaker_date($rsvp_options['long_date'],$event->ts_start) ) );

					}

				}



				$current_template .= '<option value="0">Choose Template</option>';



				$results = rsvpmaker_get_future_events();



				if ( is_array( $results ) ) {



					foreach ( $results as $r ) {



						$event_options .= sprintf( '<option value="%d">%s %s</option>', $r->postID, esc_html( $r->post_title ), esc_html( rsvpmaker_date($rsvp_options['long_date'],$r->ts_start) ) );



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

		$results = rsvpmaker_get_proximate_events();

		if ( is_array( $results ) ) {

			foreach ( $results as $r ) {

				$event_options .= sprintf( '<option value="%d">%s %s</option>', $r->ID, esc_html( $r->post_title ), esc_html( rsvpmaker_date($rsvp_options['long_date'],$r->ts_start) ) );

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

		$results          = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i posts JOIN %i meta ON posts.ID= meta.post_id WHERE meta_key='rsvpmaker_shared_template'", $wpdb->posts, $wpdb->postmeta) );

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



		$sql = $wpdb->prepare("select count(*) as copies, meta_value as t FROM %i WHERE `meta_key` = '_meet_recur' group by meta_value",$wpdb->postmeta);



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



		$holidays = rsvpmaker_commonHolidays();

		echo '<p><strong>Current list</strong> ';

		foreach($holidays as $t => $s)

			printf('%s %s / ',$s['name'],rsvpmaker_date('l F jS, Y',$t));

		echo '<p>';



		if(isset($_GET['autorenew']))

			rsvpmaker_auto_renew_project ($_GET['autorenew'], true);

		else {

			foreach ( $templates as $template ) {

				$url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&autorenew='.$template->ID);

				printf('<p><a href="%s">Auto renew %s</a></p>',$url,$template->post_title);

			}

		}

		?>



</div>



		<?php

$sql = $wpdb->prepare("SELECT * FROM %i events JOIN %i posts ON events.event=posts.ID left join %i meta on posts.ID=meta.post_id and meta.meta_key='_meet_recur' WHERE meta_key IS NULL AND date>curdate() order by date",
$wpdb->prefix.'rsvpmaker_event',$wpdb->posts,$wpdb->postmeta);
$notemplate = $wpdb->get_results($sql);
if(!empty($totemplate)) {
printf('<h2>No Template</h2><form action="" method="post">',admin_url('https://op.toastmost.org/wp-admin/edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list'));
echo '<p><select name="template_target">'.$template_options.'</select></p>';
foreach($notemplate as $event) {
	printf('<p><input type="checkbox" name="add_template_to[]" value="%d"> %s %s</p>',$event->event, $event->date,$event->post_title);
}
submit_button();
wp_nonce_field('rsvpmaker','rsvpnonce');
echo '</form>';
}

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
		$donotproject = false;

		$template = rsvpmaker_get_template_sked( $t );

		$post = get_post( $t );

		$template_editor = false;

		if ( current_user_can( 'edit_others_rsvpmakers' ) ) {

			$template_editor = true;

		} else {

			$eds = get_post_meta( $t, '_additional_editors', false );

			$eds[] = $wpdb->get_var( $wpdb->prepare("SELECT post_author FROM %i WHERE ID = %d",$wpdb->posts,$t) );

			$template_editor = in_array( $current_user->ID, $eds );

		}

		$template = rsvpmaker_get_template_sked( $t );

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

		printf( '<p id="template_ck">%s:</p><h2>%s</h2><h3>%s</h3><blockquote><p><a href="%s">%s</a></p><p><a href="#applytemplate">%s</a></p></blockquote>', __( 'Template', 'rsvpmaker' ), esc_html( $post->post_title ), $schedule, admin_url( 'post.php?action=edit&post=' . $t ), __( 'Edit Template', 'rsvpmaker' ), __( 'Apply to Existing Event', 'rsvpmaker' ) );

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

			$eds[] = $wpdb->get_var( $wpdb->prepare("SELECT post_author FROM %i WHERE ID = %d",$wpdb->posts,$t) );

			$template_editor = in_array( $current_user->ID, $eds );

		}

		$cm = rsvpmaker_date( 'm' );

		$cd = rsvpmaker_date( 'j' );

		global $current_user;

		$sched_result = rsvpmaker_get_events_by_template( $t );
		$holidays = rsvpmaker_commonHolidays();
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

		$projected = rsvpmaker_get_projected( $template );
		if(isset($_GET['debug']))
			print_r($projected);
		if ( $projected && is_array( $projected ) ) {
			foreach ( $projected as $i => $ts ) {
				$add_date_checkbox .= rsvpmaker_add_date_checkbox($i,$ts,$donotproject);
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

		if ( current_user_can( 'edit_rsvpmakers' )) {
			if(empty($add_one))
				$add_one = str_replace('type="checkbox"','type="hidden"',rsvpmaker_add_date_checkbox(0,time(),$donotproject));
			do_action( 'add_from_template_prompt' );

			printf(
				'<div class="group_add_date"><br />

<form method="post" action="%s">

<strong>' . __( 'Add One', 'rsvpmaker' ) . ':</strong><br />

%s

<input type="hidden" name="rsvpmaker_add_one" value="1" />

<input type="hidden" name="template" value="%s" />

<br />' . __( 'New Post Status', 'rsvpmaker' ) . ': <input name="newstatus" type="radio" value="publish" checked="checked" /> publish <input name="newstatus" type="radio" value="draft" /> draft
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

function rsvpmaker_template_admin_title() {

		global $title;

		global $post;

		global $post_new_file;

		if ( ! isset( $post ) || ( $post->post_type != 'rsvpmaker' ) ) {

			return;
		}

		if ( ! empty( $_GET['new_template'] ) || rsvpmaker_get_template_sked( $post->ID ) ) {

			$title .= ' ' . __( 'Template', 'rsvpmaker' );

			if ( isset( $post_new_file ) ) {

				$post_new_file = 'post-new.php?post_type=rsvpmaker&new_template=1';
			}
		}

}


// Moved from rsvpmaker-util.php during cleanup
function rsvpmaker_next_or_recent( $template_id ) {

		global $wpdb;

		global $rsvp_options;
		$event_table = get_rsvpmaker_event_table();

		$event = '';

		$sql = $wpdb->prepare("SELECT DISTINCT posts.ID as postID, posts.*, event_table.*, meta.meta_value as template

	 FROM %i posts

	 JOIN %i meta ON posts.ID =meta.post_id 

	 JOIN %i event_table ON posts.ID = event_table.event

	 WHERE event_table.date > %s AND meta.meta_key='_meet_recur' AND meta.meta_value=%d AND post_status='publish'

	 ORDER BY event_table.date LIMIT 0,1",$wpdb->posts, $wpdb->postmeta, $event_table, rsvpmaker_get_sql_curdate(), $template_id );
		if ( $row = $wpdb->get_row( $sql ) ) {
			$t = $row->ts_start;

			$neatdate = mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );

			$event = sprintf( '<a href="%s">%s: %s</a>', get_post_permalink( $row->postID ), __( 'Next Event', 'rsvpmaker' ), $neatdate );

		} else {

		$sql = $wpdb->prepare("SELECT DISTINCT posts.ID as postID, posts.*, event_table.*, meta.meta_value as template

	 FROM %i posts

	 JOIN %i meta ON posts.ID =meta.post_id 

	 JOIN %i event_table ON posts.ID = event_table.event

	 WHERE event_table.date < %s AND meta.meta_key='_meet_recur' AND meta.meta_value=%d AND post_status='publish'

	 ORDER BY event_table.date LIMIT 0,1",$wpdb->posts, $wpdb->postmeta, $event_table, rsvpmaker_get_sql_curdate(), $template_id );
			if ( $row = $wpdb->get_row( $sql ) ) {
				$t = rsvpmaker_strtotime( $row->date );

				$neatdate = mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );

				$event = sprintf( '<a style="color:#333;" href="%s">%s: %s</a>', get_post_permalink( $row->postID ), __( 'Most Recent', 'rsvpmaker' ), $neatdate );

			}
	}

		return $event;

}


// Moved from rsvpmaker-util.php during cleanup
function rsvpmaker_add_date_checkbox($i,$ts,$donotproject = [],$holidays=[]) {
$post = get_post(intval($_GET['t']));
	ob_start();

				$today = rsvpmaker_date( 'd', $ts );

				$cm = rsvpmaker_date( 'n', $ts );

				$y = date( 'Y', $ts );

				$y0 = $y - 1;

				$y2 = $y + 1;

				if ( ( $ts < current_time( 'timestamp' ) ) && ! isset( $_GET['start'] ) ) {

					return; // omit dates past
				}

				if ( isset( $donotproject ) && is_array( $donotproject ) && in_array( rsvpmaker_date( 'Y-m-j', $ts ), $donotproject ) ) {
					return;
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
return ob_get_clean();
}
