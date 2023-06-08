<?php

/*
RSVPMaker API Endpoints
*/

class RSVPMaker_Listing_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'future';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}



	public function get_items( $request ) {

		$events = get_future_events();

		if ( empty( $events ) ) {

			return new WP_Error( 'empty_category', 'no future events listed', array( 'status' => 404 ) );

		}

		return new WP_REST_Response( $events, 200 );

	}



}

class RSVPMaker_Types_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'types';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}

	public function get_items( $request ) {

		$types = get_terms( array('taxonomy' =>'rsvpmaker-type','hide_empty' => false) );

		return new WP_REST_Response( $types, 200 );

	}



	// other functions to override

	// create_item(), update_item(), delete_item() and get_item()



}



class RSVPMaker_Authors_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'authors';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}



	public function get_items( $request ) {

		$authors = get_rsvpmaker_authors();

		return new WP_REST_Response( $authors, 200 );

	}



}



class RSVPMaker_By_Type_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'type/(?P<type>[A-Z0-9a-z_\-]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}



	public function get_items( $request ) {

		$wp_query = rsvpmaker_upcoming_query();
		$posts    = $wp_query->get_posts();
		if ( empty( $posts ) ) {
			return new WP_Error( 'empty_category', 'there is no post in this category ' . $querystring, array( 'status' => 404 ) );
		}
		return new WP_REST_Response( $posts, 200 );
	}



	// other functions to override

	// create_item(), update_item(), delete_item() and get_item()



}

class RSVPMaker_GuestList_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'guestlist/(?P<post_id>[0-9]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		global $rsvp_options;

		$meta = get_post_meta( $request['post_id'], '_rsvp_show_attendees', true );

		if ( $meta ) {

			return true;

		} elseif ( ( $meta == '' ) && $rsvp_options['show_attendees'] ) {

			return true; // if not explicitly set for event, default is positive value
		}

		return false;

	}



	public function get_items( $request ) {

		global $wpdb;

		$event = $request['post_id'];

		$sql = 'SELECT first,last,note FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$event AND yesno=1 ORDER BY id DESC";

		$attendees = $wpdb->get_results( $sql );

		return new WP_REST_Response( $attendees, 200 );

	}

}



class RSVPMaker_ClearDateCache extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'clearcache/(?P<post_id>[0-9]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}



	public function get_items( $request ) {

		delete_transient( 'rsvpmakerdates' );

		return new WP_REST_Response( (object) 'deleted rsvpmakerdates transient', 200 );

	}

}



class RSVPMaker_Sked_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'sked/(?P<post_id>[0-9]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return true;

	}



	public function get_items( $request ) {

		$sked = get_template_sked( intval($request['post_id']) );

		return new WP_REST_Response( $sked, 200 );

	}

}

class RSVPMaker_StripeSuccess_Controller extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'stripesuccess/(?P<txkey>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST,GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {

		return true;

	}

	public function get_items( $request ) {

		global $wpdb;

		$base = get_option( sanitize_text_field($request['txkey']), true );

		$key = 'conf:' . time();

		foreach ( $_POST as $name => $value ) {

			$vars[ $name ] = sanitize_text_field( $value );
		}

		if ( is_array( $base ) ) {

			foreach ( $base as $name => $value ) {

				if ( empty( $vars[ $name ] ) ) {
					$vars[ $name ] = sanitize_text_field($value);
				}
			}
		}

		if ( ! empty( $vars['rsvp_id'] ) ) {

			$rsvp_id = intval($vars['rsvp_id']);

			$rsvp_post_id = intval($vars['rsvp_post_id']);

			$paid = $vars['amount'];

			$invoice_id = get_post_meta( $rsvp_post_id, '_open_invoice_' . $rsvp_id, true );

			$charge = get_post_meta( $rsvp_post_id, '_invoice_' . $rsvp_id, true );

			$paid_amounts = get_post_meta( $rsvp_post_id, '_paid_' . $rsvp_id );

			if ( is_array( $paid_amounts ) ) {

				foreach ( $paid_amounts as $payment ) {

					$paid += $payment;
				}
			}

			$wpdb->query( 'UPDATE ' . $wpdb->prefix . "rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id " );

			add_post_meta( $rsvp_post_id, '_paid_' . $rsvp_id, $vars['amount'] );

			$vars['payment_confirmation_message'] = '';

			$message_id = get_post_meta( $rsvp_post_id, 'payment_confirmation_message', true );

			if ( $message_id ) {

				$message_post = get_post( $message_id );

				$vars['payment_confirmation_message'] = rsvpmaker_email_html( $message_post->post_content );

			}

			delete_post_meta( $rsvp_post_id, '_open_invoice_' . $rsvp_id );

			delete_post_meta( $rsvp_post_id, '_invoice_' . $rsvp_id );

		}

		rsvpmaker_stripe_payment_log( $vars, $key );

		delete_option( $request['txkey'] );
        wp_schedule_single_event( time() + 30, 'rsvpmaker_after_payment',array('stripe'));
		return new WP_REST_Response( $vars, 200 );

	}
}

class RSVP_Export extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'import/(?P<code>.+)/(?P<start>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'handle' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		$code = get_option( 'rsvptm_export_lock' );

		if ( empty( $code ) ) {

			return $false;
		}

		$parts = explode( ':', $code );

		$t = (int) $parts[1];

		if ( $t < time() ) {

			return false;
		}

		return ( $code == $request['code'] );

	}



	public function handle( $request ) {

		global $wpdb;

		$start = $request['start'];

		$sql = "SELECT * FROM $wpdb->posts WHERE ID > $start AND post_type='rsvpmaker' AND post_status='publish' ORDER BY ID LIMIT 0,50";

		$future = $wpdb->get_results( $sql );

		foreach ( $future as $index => $row ) {

			$sql = "select * from $wpdb->postmeta WHERE post_id=" . $row->ID;

			$metaresults = $wpdb->get_results( $sql );

			foreach ( $metaresults as $metarow ) {

				$future[ $index ]->meta[] = $metarow;

			}
		}

		return new WP_REST_Response( $future, 200 );

	}

}



class RSVP_RunImport extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'importnow';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'handle' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		// nonce check here

		return (current_user_can( 'manage_options' )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) );
	}

	public function handle( $request ) {

		global $wpdb;

		$error = '';

		$imported = 0;

		$top = 0;

		if ( isset( $_POST['importrsvp'] ) ) {

			$url  = sanitize_text_field( $_POST['importrsvp'] );
			$url .= '/' . (int) $_POST['start'];

			if ( rsvpmaker_is_url_local( $url ) ) {

				$error = 'You cannot import into the same site you are exporting from';

			} else {

				$remote = wp_remote_get( $url );

				if ( is_wp_error( $remote ) ) {

					$error = $remote->get_error_message();

				} else {

					$remote_events = $remote['body'];

					if ( strpos( $remote_events, 'rest_forbidden' ) ) {

						$error = 'forbidden';
					}
				}
			}

			if ( empty( $error ) ) {

				$events = json_decode( $remote_events );

				if ( ! empty( $events ) ) {

					foreach ( $events as $event ) {

						  $top = $event->ID;

						  $newpost['post_title'] = $event->post_title;

						  $newpost['post_content'] = $event->post_content;

						  $newpost['post_status'] = 'publish';

						  $newpost['post_type'] = 'rsvpmaker';

						  $post_id = wp_insert_post( $newpost );

						if ( $post_id ) {

							$imported++;

							if ( ! empty( $event->meta ) ) {

								foreach ( $event->meta as $metarow ) {

									  $sql = $wpdb->prepare( "INSERT INTO $wpdb->postmeta SET post_id=%s, meta_key=%s, meta_value=%s", $post_id, $metarow->meta_key, $metarow->meta_value );

									  $wpdb->query( $sql );

								}
							}//meta array

						}//post_id

					}//end for event loop
				}
			} //end empty error
		}//end post value

		return new WP_REST_Response(
			array(
				'error'    => $error,
				'imported' => $imported,
				'top'      => $top,
			),
			200
		);

	}//end handle()

}//end class



class RSVPMaker_Email_Lookup extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'email_lookup/(?P<nonce>.+)/(?P<event>[0-9]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return wp_verify_nonce( $request['nonce'], 'rsvp_email_lookup' );

	}

	public function get_items( $request ) {

		global $wpdb;

		$event = $request['event'];

		$email = sanitize_email( $_GET['email_search'] );

		$output = ajax_rsvp_email_lookup( $email, $event );

		return new WP_REST_Response( $output, 200 );

	}

}



class RSVPMaker_Signed_Up extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'signed_up';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {

		return wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'));

	}

	public function get_items( $request ) {

		global $wpdb;

		$event = (int) $_GET['event'];

		$output = signed_up_ajax( $event );

		return new WP_REST_Response( $output, 200 );

	}

}

class RSVPMaker_Shared_Template extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';

		$path = 'shared_template/(?P<post_id>[0-9]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}



	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		$post_id  = $request['post_id'];
		$template = get_post( $post_id );
		$shared   = get_post_meta( $post_id, 'rsvpmaker_shared_template', true );
		if ( empty( $template ) || empty( $shared ) ) {
			return new WP_REST_Response( false, 200 );
		}
		$export['post_title']   = $template->post_title;
		$export['post_content'] = $template->post_content;
		return new WP_REST_Response( $export, 200 );
	}
}

class RSVPMaker_Setup extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'setup';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return (current_user_can( 'edit_rsvpmakers' ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) );
	}

	public function get_items( $request ) {
		$editurl = rsvpmaker_setup_post( true );
		return new WP_REST_Response( $editurl, 200 );
	}
}

class RSVPMaker_Email_Templates extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'email_templates';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return (current_user_can( 'edit_others_rsvpemails' ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) );
	}

	public function get_items( $request ) {
		$templates = $_POST['rsvpmaker_email_template']; // array
		$output    = '<h2>' . __( 'Updated', 'rsvpmaker' ) . '</h2>';
		foreach ( $templates as $index => $template ) {
			$template['html']    = wp_kses_post(stripslashes( $template['html']) );
			$templates[ $index ] = $template;
			$output             .= sprintf( '<p><a target="_blank" href="%s">Preview %s</a></p>', admin_url( '?preview_broadcast_in_template=' . $index ), $template['slug'] );
		}
		update_option( 'rsvpmaker_email_template', $templates );
		$output .= sprintf( '<p><a href="%s">%s</a></p>', admin_url( 'edit.php?post_type=rsvpemail&page=rsvpmaker_email_template' ), __( 'Edit', 'rsvpmaker' ) );
		return new WP_REST_Response( $output, 200 );
	}
}

class RSVPMaker_Notification_Templates extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'notification_templates';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return rsvpmaker_verify_nonce();
	}

	public function get_items( $request ) {
		$output = '<h2>' . __( 'Updated', 'rsvpmaker' ) . '</h2>';
		if ( isset( $_POST['ntemp'] ) ) {
			$ntemp = $_POST['ntemp'];
			foreach($ntemp as $index => $data) {
				$ntemp[$index]['subject'] = sanitize_text_field($ntemp[$index]['subject']);
				$ntemp[$index]['body'] = wp_kses_post($ntemp[$index]['body']);
			}
			if ( ! empty( $_POST['newtemplate']['subject'] ) && ! empty( $_POST['newtemplate_label'] ) ) {
				$index = sanitize_text_field($_POST['newtemplate_label']);
				$ntemp[ $index ]['subject'] = sanitize_text_field( $_POST['newtemplate']['subject'] );
				$ntemp[ $index ]['body']    = wp_kses_post( $_POST['newtemplate']['body'] );
			}
			update_option( 'rsvpmaker_notification_templates', stripslashes_deep( $ntemp ) );
		}
		$output .= sprintf( '<p><a href="%s">%s</a></p>', admin_url( 'edit.php?post_type=rsvpemail&page=rsvpmaker_notification_templates' ), __( 'Edit', 'rsvpmaker' ) );
		return new WP_REST_Response( $output, 200 );
	}
}

class RSVPMaker_Details extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvpmaker_details';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return (current_user_can( 'edit_rsvpmakers' ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) );
	}

	public function get_items( $request ) {
		$output = rsvpmaker_details_post();
		return new WP_REST_Response( $output, 200 );
	}
}

class RSVPMaker_Time_And_Zone extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'time_and_zone/(?P<post_id>[0-9a-z]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		$date = '';
		if ( $request['post_id'] == 'nextrsvp' ) {
			$event = get_next_rsvp_on();
			if ( $event ) {
				$date = $event->datetime;
			}
		} elseif ( $request['post_id'] == 'next' ) {
			$event = get_next_rsvpmaker();
			if ( $event ) {
				$date = $event->datetime;
			}
		} elseif ( is_numeric( $request['post_id'] ) ) {
			$date = get_rsvp_date( $request['post_id'] );
		}

		if ( ! empty( $date ) ) {
			$t = rsvpmaker_strtotime( $date ) * 1000;
		}
		return new WP_REST_Response( $t, 200 );
	}
}

class RSVPMaker_Events_with_Timezone extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'events_with_timezone';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $default_tz;
		$last_tz = '';
		$events  = array();
		$list    = get_future_events( array( 'limit' => 10 ) );
		if ( $list ) {
			foreach ( $list as $event ) {
				$timezone = rsvpmaker_get_timezone_string( $event->ID );
				if ( $timezone != $last_tz ) {
					date_default_timezone_set( $timezone );
					$last_tz = $timezone;
				}
				$t        = strtotime( $event->datetime );
				$end      = strtotime( $event->enddate );
				$events[] = array(
					'ts'              => $t,
					'end'             => $end,
					'timezone_string' => $timezone,
					'site'            => get_option( 'blogname' ),
					'post_title'      => $event->post_title,
					'permalink'       => get_permalink( $event->ID ),
				);
			}
		}
		return new WP_REST_Response( $events, 200 );
	}
}

class RSVPMaker_Flux_Capacitor extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'flux_capacitor';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'POST',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $default_tz, $rsvp_options, $post;
		$time   = sanitize_text_field( $_POST['time'] );
		$end    = sanitize_text_field( $_POST['end'] );
		$tz     = sanitize_text_field( $_POST['tzstring'] );
		$format = sanitize_text_field( $_POST['format'] );
		$timezone_abbrev = sanitize_text_field($_POST['timezone_abbrev']);
		$post   = get_post( $_POST['post_id'] );
		$time   = rsvpmaker_strtotime( $time );
		$s3 = rsvpmaker_date( 'T', $time );
		if($timezone_abbrev == $s3)
			$times ['content'] = ''; // if city code is different but tz code is same
		else {
			if ( $end ) {
				$end = rsvpmaker_strtotime( $end );
			}
			date_default_timezone_set( $tz );
			// strip off year
			$rsvp_options['long_date'] = str_replace( ', %Y', '', $rsvp_options['long_date'] );
			$times['content']          = 'Or: ';
			if ( $format == 'time' ) {
				$times['content'] .= date( $rsvp_option['time_format'], $time );
				if ( $end ) {
					$times['content'] .= ' to ' . date( 'g:i A T', $end );
				}
			} else {
				$times['content'] .= $day1 = date( $rsvp_options['long_date'], $time );
				$times['content'] .= ' ' . date( 'g:i A T', $time );
				if ( $end ) {
					$times['content'] .= ' to ';
					$day2              = date( $rsvp_options['long_date'], $end );
					if ( $day2 != $day1 ) {
						$times['content'] .= $day2 . ' ';
					}
					$times['content'] .= date( 'g:i A T', $end );
				}
			}	
		}
		$times['tzoptions'] = wp_timezone_choice( $tz );
		return new WP_REST_Response( $times, 200 );
	}
}

class RSVPMaker_Daily extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'daily/(?P<event>[0-9a-z]+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb;
		$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=%d ORDER BY timestamp",$request['event']);
		$results = $wpdb->get_results($sql);
		$daily_count = [];
		$count = 0;
		$wasdate = '';
		$count = 0;
		foreach($results as $row) {
			$date = rsvpmaker_date('Y-m-d',rsvpmaker_strtotime($row->timestamp));
			if(isset($daily_count[$date]))
			$daily_count[$date]++;
			else
			$daily_count[$date] = 1;
		}
		$return_array = [];
		foreach($daily_count as $date => $count) {
			$return_array[] = array('date' => $date, 'count' => $count);
		}
		return new WP_REST_Response( $return_array, 200 );
	}
}

class RSVPMaker_Preview extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'preview/(?P<block>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		if('next-events' == $request['block'])
			return new WP_REST_Response( rsvpmaker_next_rsvps($_GET), 200 );
		if('schedule' == $request['block'])
			return new WP_REST_Response( rsvpmaker_daily_schedule($_GET), 200 );
		if('future-rsvp-links' == $request['block'])
			return new WP_REST_Response( future-rsvp-links($_GET), 200 );
		if('emailpostorposts' == $request['block'])
			return new WP_REST_Response( rsvpmaker_emailpostorposts($_GET), 200 );
	}
}

class RSVPMaker_PorC extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'postsorcategories';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => 'GET',

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		$pc[] = array('label' => 'Choose Post or Category', 'value' => '');
		$posts = get_posts('posts_per_page=20');
		foreach($posts as $p)
			$pc[] = array('label' => $p->post_title, 'value' => $p->ID);
		$categories = get_categories();
		foreach($categories as $category)
			$pc[] = array('label' => 'Category: '.$category->name, 'value' => $category->slug);
		return new WP_REST_Response($pc, 200 );
	}
}

class RSVPMaker_Confirmation_Code extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvpmaker_confirmation/(?P<code>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		$transient = get_transient('remote_nonce');
		$result = (empty($transient)) ? false : $request['code'] == $transient;
		return new WP_REST_Response( $result, 200 );
	}
}

class PostmarkIncoming extends WP_REST_Controller {

	public function register_routes() {
	  $namespace = 'rsvpmaker/v1';
	  $path = 'postmark_incoming/(?P<code>.+)';
  
	  register_rest_route( $namespace, '/' . $path, [
		array(
		  'methods'             => 'GET, POST, PUT, PATCH, DELETE',
		  'callback'            => array( $this, 'get_items' ),
		  'permission_callback' => array( $this, 'get_items_permissions_check' )
			  ),
		  ]);     
	  }
  
	public function get_items_permissions_check($request) {
		$postmark = get_rsvpmaker_postmark_options();
		return (!empty($postmark['handle_incoming']) && $request['code'] == $postmark['handle_incoming']);
	}
  
  public function get_items($request) {
$opbusiness = false;
$json = file_get_contents('php://input');
$data = json_decode(trim($json));

$toFull = $data->ToFull;
$ccFull = $data->CcFull;
$tolist = $cclist = $audience = array();
foreach($toFull as $tf) {
	$audience[] = $tf->Email;
	$tolist[] = $tf->Email;
	if(strpos($tf->Email,'p@') || strpos($tf->Email,'p-'))
		$opbusiness = true;
}
foreach($ccFull as $cf) {
	$audience[] = $cf->Email;
	$cclist[] = $tf->Email;
}

$origin = sprintf("<p>Forwarded message, originally <br />From <a href=\"mailto:%s\">%s</a><br />To: %s<br />Cc: %s<br /><a href=\"mailto:%s?cc=%s&subject=Re: %s\">Reply All</a></p>",$data->From,$data->From,htmlentities($data->To),htmlentities($data->Cc),$data->From,implode(',',$audience),$data->Subject);
$origin = '<div class="postmark-origin" style="padding:10px; background-color:#efefef">'.$origin.'</div>';
$check = implode('|',$audience).$data->Subject;
$last = get_transient('postmark_last_incoming');
if($check == $last) {
	//rsvpmaker_debug_log($check,'incoming duplicate');
	return;
}
set_transient('postmark_last_incoming',$check,time()+30);
$data->HtmlBody = (strpos($data->HtmlBody,'</body>')) ? str_replace('</body>',$origin.'</body>',$data->HtmlBody) : $data->HtmlBody.$origin;
$mail['subject'] = $qpost['post_title'] = $data->Subject;
$mail['html'] = $qpost['post_content'] = $data->HtmlBody;
if(strpos($qpost['post_content'],'</head>'))
{
	$parts = explode('</head>',$qpost['post_content']);
	$qpost['post_content'] = "<html>".$parts[1];
	$head = $parts[0].'</head>';
}

$qpost['post_status'] = 'rsvpmessage';
$qpost['post_type'] = 'rsvpemail';
$post_id = wp_insert_post($qpost);
$data->post_id = $post_id;
if(!empty($head))
	add_post_meta($post_id,'_rsvpmail_head',$head);
add_post_meta($post_id,'rsvprelay_from',$data->From);
add_post_meta($post_id,'rsvprelay_fromname',$data->FromName);
add_post_meta($post_id,'rsvprelay_postmark_to',$data->ToFull);
add_post_meta($post_id,'rsvprelay_postmark_cc',$data->CcFull);
add_post_meta($post_id,'rsvprelay_postmark_audience',$audience);
add_post_meta($post_id,'rsvprelay_postmark_data',$data);
rsvpmaker_postmark_incoming($audience,$data,$post_id);
do_action('postmark_incoming_email_object',$data,$json);
	return new WP_REST_Response($data, 200);
	}
}

class RSVPMaker_Confirm_Email_Membership extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvpmailer_member/(?P<email>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb;
		$email = $request['email'];
		if(rsvpmail_contains_email($email))
		{
			$table = rsvpmaker_guest_list_table();
			$sql = "select id from $table where email LIKE '$email' ";
			$result = ($wpdb->get_var($sql) > 0);
		}
		else
			$result = false;
		return new WP_REST_Response( $result, 200 );
	}
}

class RSVPMail_Remote_Signup extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvpmailer_signup/(?P<code>.+)';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
		$valid = (isset($_POST['em']) && empty($_POST['extra_special_discount_code']) && (urldecode($request['code']) == get_rsvpmail_signup_key()));
		if(!$valid)
			rsvpmaker_debug_log($_POST,'spam signup');
		return $valid;
	}

	public function get_items( $request ) {
		global $wpdb;
		$email = '';
		if(isset($_POST['em']))
			$email = trim($_POST['em']);
		elseif(isset($_POST['email']))
			$email = trim($_POST['email']);
		if(is_email($email))
		{   
			$first = isset($_POST['first']) ? sanitize_text_field($_POST['first']) : '';
			$last = isset($_POST['last']) ? sanitize_text_field($_POST['last']) : '';
			$result['message'] = rsvpmaker_guest_list_add($email,$first,$last,'',0);
			$result['success'] = true;
			$result['code'] = urldecode($request['code']);
			$result['key'] = get_rsvpmail_signup_key();
		}
		else {
			$result['message'] = 'Please enter a valid email address. You entered: '.$email;
			$result['success'] = false;
		}
		$result['postwas'] = $_POST;
		return new WP_REST_Response( $result, 200 );
	}
}

class RSVPMaker_Flex_Form extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'flexform';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		global $rsvp_options;
		rsvpmaker_create_nonce();
		$post_id = intval($_POST['post_id']);
		$valid = (empty($_POST['extra_special_discount_code']) && $post_id);
		if($valid && get_post_meta($post_id,'flexform_recaptcha',true) )
			$valid = rsvpmaker_recaptcha_check ($rsvp_options["rsvp_recaptcha_site_key"],$rsvp_options["rsvp_recaptcha_secret"]);
		return $valid;
	}

	public function get_items( $request ) {
		global $wpdb;
		ob_start();
		$formvars = array_map('sanitize_textarea_field',$_POST['profile']);
		$slug = sanitize_text_field($_POST['appslug']);
		if('contact' == $slug)
			$result = rsvpmaker_contact_form($formvars);
		else {
			$result = apply_filters('rsvpflexform_'.$slug,array('message' => 'Error: unrecognized app, '.$slug),$formvars);
		}
		$result['message'] .= trim(strip_tags(ob_get_clean()));
		return new WP_REST_Response( $result, 200 );
	}
}

class RSVPMaker_Json_Meta extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'json_meta';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb, $rsvp_options;
		$post_id = intval($_GET['post_id']);
		$changes = '';
			$json = file_get_contents('php://input');
			$data = json_decode(trim($json));
			if($data && !current_user_can('edit_post',$post_id))
				return new WP_REST_Response( array('status' => 'User does not have rights to edit this document'), 401 );

			$changes .= 'data:'.var_export($data,true).' ';	
			if(isset($data->kv)) {
				foreach($data->kv as $kv) {
					$changes .= $kv->key.'='.$kv->value .' ';
					update_post_meta($post_id,$kv->key,$kv->value);
				}
			}
		$post = get_post($post_id);
		$_rsvp_confirm = get_post_meta($post_id,'_rsvp_confirm',true);
		if(empty($_rsvp_confirm)) {
			$_rsvp_confirm = $rsvp_options['rsvp_confirm'];
			$confirm_type = 'Default';
			$cpost = get_post($_rsvp_confirm);
		}
		else {
			$cpost = get_post($_rsvp_confirm);
			$confirmation_type = ($cpost->post_parent == $post_id) ? '' : 'Inherited';
		}
		$excerpt = strip_tags($cpost->post_content);
		$excerpt = (strlen($excerpt) > 100) ? substr($excerpt, 0, 100).' ...' : $excerpt;	
		$_meet_recur = (int) get_post_meta($post->ID,'_meet_recur',true);	
		$form_id = get_post_meta($post->ID,'_rsvp_form',true);
		if(empty($form_id))
			$form_id = (int) $rsvp_options['rsvp_form'];
		$fpost = get_post($form_id);
		$form_edit = admin_url('post.php?action=edit&post='.$fpost->ID.'&back='.$post->ID);
		$form_customize = admin_url('?post_id='. $post->ID. '&customize_form='.$fpost->ID);
		$guest = (strpos($fpost->post_content,'rsvpmaker-guests')) ? 'Yes' : 'No';
		$note = (strpos($fpost->post_content,'name="note"') || strpos($fpost->post_content,'formnote')) ? 'Yes' : 'No';
		preg_match_all('/\[([A-Za-z0_9_]+)/',$fpost->post_content,$matches);
		if(!empty($matches[1]))
		foreach($matches[1] as $match)
			$fields[$match] = $match;
		preg_match_all('/"slug":"([^"]+)/',$fpost->post_content,$matches);
		if(!empty($matches[1]))
		foreach($matches[1] as $match)
			$fields[$match] = $match;	
		$merged_fields = (empty($fields)) ? '' : implode(', ',$fields);
		$form_fields = sprintf('Fields: %s, Guests: %s, Note field: %s',$merged_fields,$guest,$note);
		$form_type = '';
		$form_edit_post = (current_user_can('edit_post',$fpost->ID));
		$form_edit_post = true;
		if($fpost->post_parent == 0)
			$form_type = __('Default','rsvpmaker');//printf('<div id="editconfirmation"><a href="%s" target="_blank">Edit</a> (default from Settings)</div><div><a href="%s" target="_blank">Customize</a></div>',$edit,$customize);
		elseif($fpost->post_parent != $post->ID)
			$form_type = __('Inherited','rsvpmaker');//printf('<div id="editconfirmation"><a href="%s" target="_blank">Edit</a> (default from Settings)</div><div><a href="%s" target="_blank">Customize</a></div>',$edit,$customize);
		$form_customize = admin_url('?post_id='. $post_id. '&customize_form='.$fpost->ID);	
		$meta = array(
			'editor_base_url' => admin_url('post.php?action=edit&post='),
			'_rsvp_form' => $form_id,
			'_meet_recur' => $_meet_recur,//template
			'form_type' => $form_type,
			'_rsvp_dates' => get_post_meta($post_id,'_rsvp_dates',true),
			'_rsvp_end_date' => get_post_meta($post_id,'_rsvp_end_date',true),
			'_firsttime' => get_post_meta($post_id,'_firsttime',true),//display
			'_rsvp_on' => intval(get_post_meta($post->ID,'_rsvp_on',true)),
			'_rsvp_to' => get_post_meta($post->ID,'_rsvp_to',true),
			'_rsvp_confirm' => $_rsvp_confirm,
			'confirmation_excerpt' => $excerpt,
			'confirmation_customize' => admin_url('?post_id='. $post->ID. '&customize_rsvpconfirm='.$_rsvp_confirm.'#confirmation'),
			'reminders' => admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id='.$post->ID),
			'form_customize' => $form_customize,
			'form_type' => $form_type,
			'_rsvp_instructions' => get_post_meta($post_id,'_rsvp_instructions',true),
			'is12Hour' => strpos($rsvp_options['time_format'],'A') > 0
        );
		$rsvpdetails = ['_rsvp_rsvpmaker_send_confirmation_email','_rsvp_confirmation_include_event','_rsvp_count','_rsvp_count_party','_rsvp_yesno','_rsvp_max','_rsvp_login_required','_rsvp_show_attendees','_convert_timezone','_add_timezone'];
		foreach($rsvpdetails as $key) {
			$meta[$key] = intval(get_post_meta($post_id,$key,true));
		if(rsvpmaker_is_template($post_id)) {
			$tvars = ["Varies",
			"First",
			"Second",
			"Third",
			"Fourth",
			"Last",
			"Every",
			"Sunday",
			"Monday",
			"Tuesday",
			"Wednesday",
			"Thursday",
			"Friday",
			"Saturday",
			"hour",
			"minutes",
			"end"];
			foreach($tvars as $var) {
				$key = '_sked_'.$var;
				$meta[$key] = get_post_meta($post_id,$key,true);
			}
			$meta['rsvpautorenew'] = get_post_meta($post_id,'rsvpautorenew',true);	
		}
		$meta['form_links'] = get_form_links($post_id, $_meet_recur, 'rsvp_options');
		$meta['confirmation_links'] = get_conf_links($post_id, $_meet_recur, 'rsvp_options');
		$meta['related_document_links'] = get_related_documents($post_id);
		$meta['changes'] = $changes;
	}
		return new WP_REST_Response( $meta, 200 );
	}
}

class RSVPMaker_Form extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvp_form';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb, $rsvp_options, $post, $current_user;
		$json = file_get_contents('php://input');
		$updated = array();
		$form_id = (empty($_GET['form_id'])) ? $rsvp_options['rsvp_form'] : intval($_GET['form_id']); 
		$post_id = (empty($_GET['post_id']) || !is_numeric($_GET['post_id'])) ? 0 : intval($_GET['post_id']);
		$post = ($post_id) ? get_post($post_id) : null;
		if(!empty($json)) {
			if($post_id)
			{
				if(!current_user_can('edit_post',$post_id))
				return new WP_REST_Response( array('status' => 'User does not have rights to edit this document'), 401 );
			}
			else {
				if(!current_user_can('edit_post',$form_id))
				return new WP_REST_Response( array('status' => 'User lacks administrative rights'), 401 );
			}
			$data = json_decode(trim($json));
			if($data->start && $post_id)
				update_post_meta($post_id,'_rsvp_start',rsvpmaker_strtotime($data->start));
			if($data->deadline && $post_id)
				update_post_meta($post_id,'_rsvp_start',rsvpmaker_strtotime($data->deadline));
			if(isset($data->form) && is_array($data->form)) {
				$output = '';
				foreach($data->form as $index => $block) {
					if($block->blockName)
						$output .= rsvpBlockDataOutput($block, $post_id);
				}
				if($data->newForm) {
						$updated['post_title'] = 'Form:'.$data->newForm;
						$updated['post_type'] = 'rsvpmaker_form';
						$updated['post_author'] = $current_user->ID;
						$updated['post_content'] = $output;
						$updated['post_status'] = 'publish';
						$form_id = wp_insert_post($updated);
						if($data->event_id)
							update_post_meta($data->event_id,'_rsvp_form',$form_id);
				}
				else {
					$updated['ID'] = $form_id;
					$updated['post_content'] = $output;
					$upid = wp_update_post($updated);	
				}	
			}
		}
		$form = get_post($form_id);
		$response['form_id'] = $form_id;
		$response['form'] = parse_blocks($form->post_content);
		$response['form_options'] = [];
		$response['form_options'][] = array('value'=>$rsvp_options['rsvp_form'],'label'=>'Default');
		$includedform = array($rsvp_options['rsvp_form']);
		$reusable = get_option('rsvpmaker_forms');
		if(is_array($reusable))
		foreach($reusable as $label => $value) {
			$response['form_options'][] = array('value'=>$value,'label'=>$label);
			$includedform[] = $value;
		}
		$allforms = $wpdb->get_results("select ID, post_title, post_parent from $wpdb->posts WHERE post_type='rsvpmaker_form' ORDER BY ID DESC LIMIT 0, 50");
		foreach($allforms as $form)
		{
			if(!in_array($form->ID,$includedform)) {
				$label = '';
				if($form->post_parent) {
					$parent = get_post($form->post_parent);
					if($parent && $parent->post_type == 'rsvpmaker_template');
						$label = ' (from template: '.$parent->post_title.')';
				}
				$response['form_options'][] = array('value'=>$form->ID,'label'=>$form->post_title.$label);
			}
		}
		$response['updated'] = $updated;
		return new WP_REST_Response( $response, 200 );
	}
}

class RSVP_Options_Json extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvp_options';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return current_user_can('manage_options');
	}

	public function get_items( $request ) {
		global $wpdb, $rsvp_options, $current_user;
		$json = file_get_contents('php://input');
		$actions = [];
		$changes = 0;
		$status = [];
		if(!empty($json)) {
			$data = json_decode(trim($json));
			if(is_array($data)) {
				foreach($data as $o) {
					if(isset($o->type)) {
						if('option' == $o->type) {
							update_option($o->key,sanitize_rsvpopt($o->value));
						}
						elseif('meta' == $o->type)
						{
							update_post_meta($o->post_id,$o->key,sanitize_rsvpopt($o->value));
						}
						elseif('action' == $o->type)
						{
							$actions[] = $o;//process these at the end
						}
						elseif('rsvp_options' == $o->type)
						{
							$status[] = "rsvp option change $o->key $o->value";
							$rsvp_options[$o->key] = sanitize_rsvpopt($o->value);
							$changes++;
						}
						elseif('mergearray' == $o->type)
						{
							$p = get_option($o->key);
							if(!$p)
								$p = array();
							$changes = (array) $o->value;
							foreach($changes as $chkey => $change) {
								if($change && $change != 'set')
									$p[$chkey] = sanitize_text_field($change);
								if(('mode' == $chkey) && ('rsvpmaker_paypal_rest_keys' == $o->key))
									$p['sandbox'] = ('sandbox' == $change) ? '1' : '0';
							}
							update_option($o->key,$p);
						}
					}
					//else
						//$rsvp_options[$o->key] = $o->value;
				}
			}
			//else
				//$rsvp_options[$data->key] = $data->value;
			if($changes)
				update_option( 'RSVPMAKER_Options',$rsvp_options );
			$response = array('changes'=>$changes,'actions'=>$actions,'data'=>$data,'status'=>$status);
			return new WP_REST_Response( $response , 200 );	
		}

		//if(isset($_GET['tab']) && 'payment' == $_GET['tab'])
		//{
			$response['gateways'] = array();
			$gateways = get_rsvpmaker_payment_options ();
			foreach($gateways as $gateway)
			$response['gateways'][] = array('value'=>$gateway,'label'=>$gateway);
			$response['chosen_gateway'] = get_rsvpmaker_payment_gateway ();
			$stripe = get_option('rsvpmaker_stripe_keys');
			if(!is_array($stripe))
				{
					$response['stripe']['sk'] = '';
					$response['stripe']['pk'] = '';
					$response['stripe']['sandbox_sk'] = '';
					$response['stripe']['sandbox_pk'] = '';
					$response['stripe']['mode'] = 'production';
				}
			else {
				if(!empty($stripe['pk']) && !empty($stripe['sk'])) {
					$response['stripe']['sk'] = 'set';
					$response['stripe']['pk'] = 'set';	
				}
				else {
					$response['stripe']['sk'] = '';
					$response['stripe']['pk'] = '';	
				}		
				if(!empty($stripe['pk']) && !empty($stripe['sk'])) {
					$response['stripe']['sandbox_sk'] = 'set';
					$response['stripe']['sandbox_pk'] = 'set';	
				}
				else {
					$response['stripe']['sandbox_sk'] = '';
					$response['stripe']['sandbox_pk'] = '';	
				}		
				$response['stripe']['mode'] = (empty($stripe['mode'])) ? 'production' : $stripe['mode'];
			}
			$pp = get_option('rsvpmaker_paypal_rest_keys');
			if(!is_array($pp))
				{
					$response['paypal']['client_id'] = '';
					$response['paypal']['client_secret'] = '';
					$response['paypal']['sandbox_client_id'] = '';
					$response['paypal']['sandbox_client_secret'] = '';
					$response['paypal']['sandbox'] = 0;//0 for production
					$response['paypal']['mode'] = 'production';
				}
			else {
				if (!empty($pp['client_id']) && !empty($pp['client_secret']))
				{
					$response['paypal']['client_id'] = 'set';
					$response['paypal']['client_secret'] = 'set';
				}
				else
				{
					$response['paypal']['client_id'] = '';
					$response['paypal']['client_secret'] = '';
				}
				if (!empty($pp['sandbox_client_id']) && !empty($pp['client_secret']))
				{
					$response['paypal']['sandbox_client_id'] = 'set';
					$response['paypal']['sandbox_client_secret'] = 'set';
				}
				else
				{
					$response['paypal']['sandbox_client_id'] = '';
					$response['paypal']['sandbox_client_secret'] = '';
				}
				$response['paypal']['mode'] = ($pp['sandbox']) ? 'sandbox' : 'production';
			}	
		//}

		$response['rsvp_options'] = $rsvp_options;
		$response['current_user_id'] = $current_user->ID;
		$response['current_user_email'] = $current_user->user_email;
		$response['edit_url'] = admin_url('https://delta.local/wp-admin/post.php?action=edit&post=');
		$c = get_post($rsvp_options['rsvp_confirm']);
		$c = ($c && !empty($c->post_content)) ? do_blocks($c->post_content) : '<p>Error retrieving message.</p>';
		$response['confirmation_message'] = $c;
		$response['stylesheet_url'] = plugins_url('rsvpmaker/style.css');
		return new WP_REST_Response( $response, 200 );
	}
}

function sanitize_rsvpopt($value) {
	if(strpos($value,'</'))
		$value = wp_kses_post($value);
	elseif(strpos($value,"\n"))
		$value = sanitize_textarea_field($value);
	else
		$value = sanitize_text_field($value);
	return $value;
}

class RSVP_Event_Date extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvp_event_date';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb, $rsvp_options, $current_user;
		$event_id = intval($_GET['event_id']);
		$json = file_get_contents('php://input');
		if(!empty($_POST) || !empty($json))
		{
			if(!current_user_can('edit_post',$event_id))
			return new WP_REST_Response( 'user does not have editing rights for this event', 401 );
		}
		$upsql = '';
		if(!empty($json)) {
			$data = json_decode(trim($json));
			if(isset($data->date) && isset($data->timezone)) //retry submission
			{
				$ts_start = rsvpmaker_strtotime($data->date);
				$ts_end = rsvpmaker_strtotime($data->enddate);
				$upsql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET date=%s, enddate=%s, ts_start=%d, ts_end=%d, display_type=%s, timezone=%s WHERE event=%d",$data->date,$data->enddate,$ts_start, $ts_end, $data->display_type, $data->timezone, $event_id);
			}
			elseif(isset($data->date))
			{
				$ts_start = rsvpmaker_strtotime($data->date);
				$ts_end = rsvpmaker_strtotime($data->enddate);
				$upsql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET date=%s, enddate=%s, ts_start=%d, ts_end=%d WHERE event=%d",$data->date,$data->enddate,$ts_start, $ts_end, $event_id);
			}
			elseif(isset($data->enddate)) // end date set independently
			{
				$ts_end = rsvpmaker_strtotime($data->enddate);
				$upsql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET enddate=%s, ts_end=%d WHERE event=%d",$data->enddate,$ts_end,$event_id);
			}
			elseif(isset($data->display_type)) // end date set independently
			{
				$upsql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET display_type=%s WHERE event=%d",$data->display_type,$event_id);
			}
			elseif(isset($data->timezone)) // end date set independently
			{
				$upsql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET timezone=%s WHERE event=%d",$data->timezone,$event_id);
			}
			if(!empty($upsql))
				$wpdb->query($upsql);
		}
		$event = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=$event_id");
		if(!$event) {
			$type = get_post_type($event_id);
			if('rsvpmaker_template' == $type)
				return new WP_REST_Response( array('message'=>'not an event', 'is_template'=>true), 200 );	
			elseif('rsvpmaker' == $type)
			{
				rsvpmaker_add_event_row($event_id,date('Y-m-d H:i:s',strtotime('tomorrow 12:00')),date('Y-m-d H:i:s',strtotime('tomorrow 13:00')),'');//add_rsvpmaker_new_event_defaults($event_id,get_post($event_id),false);
				$event = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=$event_id");
				if(!$event)
					return new WP_REST_Response( array('message'=>'error adding default dates', 'debug'=>var_export($event)), 200 );	
			}
			else
				return new WP_REST_Response( array('message'=>'not an event'), 200 );	
		}
		$event->upsql = $upsql;
		$event->tzchoices = timezone_identifiers_list();
		return new WP_REST_Response( $event , 200 );	
	}
}

class RSVP_Confirm_Remind extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'confirm_remind';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		global $wpdb, $current_user;
		$status = '';
		$post_id= intval($_GET['event_id']);
		$json = file_get_contents('php://input');
		if(!empty($_POST) || !empty($json))
		{
			if(!current_user_can('edit_post',$post_id))
			return new WP_REST_Response( 'user does not have editing rights for this event', 401 );
		}
		if(!empty($json)) {
			$data = json_decode($json);
			if('customize' == $data->action) {
				$old = get_post($data->source);
				$new['post_content'] = $old->post_content;
				$new['post_type'] = 'rsvpemail';
				$new['post_author'] = $current_user->ID;
				$new['post_status'] = 'publish';
				$new['post_parent'] = $data->event_id;
				$new['post_title'] = $data->type.' for '.$post_id;
				$id = wp_insert_post($new);
				update_post_meta($post_id,'_rsvp_confirm',$id);
			}
			if('add_payment_confirmation' == $data->action) {
				$old = get_post($data->source);
				$new['post_content'] = $old->post_content;
				$new['post_type'] = 'rsvpemail';
				$new['post_author'] = $current_user->ID;
				$new['post_status'] = 'publish';
				$new['post_parent'] = $data->event_id;
				$new['post_title'] = $data->type.' for '.$post_id;
				$id = wp_insert_post($new);
				$status .= ' added payment confirmation ' .$id;
				update_post_meta($post_id,'payment_confirmation_message',$id);
			}
			elseif('add_reminder' == $data->action) {
				$hours = $data->hours;
				$event_title = get_the_title($post_id);
				if('before' == $data->beforeafter)
				{
					$hours = 0 - $hours;
					$new['post_title'] = 'Reminder: '. $event_title;
				}
				else {
					$new['post_title'] = 'Follow up: '. $event_title;
				}
				$old = get_post($data->source);
				$new['post_content'] = $old->post_content;
				$new['post_type'] = 'rsvpemail';
				$new['post_author'] = $current_user->ID;
				$new['post_status'] = 'publish';
				$new['post_parent'] = $data->event_id;
				$id = wp_insert_post($new);
				$status .= ' added reminder ' .$id.' hours: '.$hours;
				update_post_meta($post_id,'_rsvp_reminder_msg_'.$hours,$id);
				rsvpmaker_reminder_cron($hours, get_rsvp_date($post_id), $post_id);
			}
		}
		
$response["confirmation"] = rsvp_get_confirm( $post_id, true );
$response['confirmation']->html = do_blocks($response["confirmation"]->post_content);
$response['reminder'] = [];
$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_rsvp_reminder_msg_%' ORDER BY meta_key";
$results = $wpdb->get_results($sql);
if($results)
{
	foreach($results as $row) {
		//$hour = 
		$rpost = get_post($row->meta_value);
		if($rpost) {
			$rpost->hour = str_replace('_rsvp_reminder_msg_','',$row->meta_key);
			$rpost->html = do_blocks($rpost->post_content);
			$response['reminder'][] = $rpost;
		}
	}
}

$payment_confirmation = (int) get_post_meta($post_id,'payment_confirmation_message',true);
$response['payment_confirmation'] = null;
if($payment_confirmation)
{
	$pconf = get_post($payment_confirmation);
	if($pconf) {
	$response['payment_confirmation'] = $pconf;
	$response['payment_confirmation']->html = do_blocks($pconf->post_content);
	}
}
$response['edit_url'] = admin_url('post.php?action=edit&post=');
$response['status'] = $status;
return new WP_REST_Response( $response, 200 );	

	}//end handle
}//end class

class RSVP_Pricing extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'pricing';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('POST','GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;// current_user_can('edit_posts');
	}

	public function get_items( $request ) {
		global $wpdb, $current_user;
		$status = '';
		$post_id= intval($_GET['event_id']);
		$json = file_get_contents('php://input');
		if(!empty($_POST) || !empty($json))
		{
			if(!current_user_can('edit_post',$post_id))
			return new WP_REST_Response( 'user does not have editing rights for this event', 401 );
		}
		if(!empty($json)) {
			$data = json_decode($json);
			if('pricing' == $data->update) {
				$change = array_filter($data->change,'rsvp_pricing_deleted');
				$change = array_map('rsvp_pricing_ts', $change);
				$status .= 'change: '.var_export($change, true);
				update_post_meta($post_id,'pricing',$change);
			}
			if('coupon_codes' == $data->update) {
				$status .= 'change: '.var_export($data->change, true);
				delete_post_meta( $post_id, '_rsvp_coupon_code' );
				delete_post_meta( $post_id, '_rsvp_coupon_method' );
				delete_post_meta( $post_id, '_rsvp_coupon_discount' );	
				foreach($data->change->coupon_codes as $index => $code) {
					add_post_meta( $post_id, '_rsvp_coupon_code', $code );
					add_post_meta( $post_id, '_rsvp_coupon_method', $data->change->coupon_methods[$index] );
					add_post_meta( $post_id, '_rsvp_coupon_discount', $data->change->coupon_discounts[$index] );	
				}
		
				update_post_meta($post_id,'_rsvp_coupons',(array) $data->change);
			}
		}
		$pricing = rsvp_get_pricing($post_id);
		//if(sizeof($pricing) && $pricing[0]->price)
		$response['pricing'] = $pricing;
		$coupon_codes = get_post_meta( $post_id, '_rsvp_coupon_code' );
		$coupon_methods = get_post_meta( $post_id, '_rsvp_coupon_method' );
		$coupon_discounts = get_post_meta( $post_id, '_rsvp_coupon_discount' );		
		$response['coupon_codes'] = is_array($coupon_codes) ? $coupon_codes : [];
		$response['coupon_methods'] = is_array($coupon_methods) ? $coupon_methods : [];
		$response['coupon_discounts'] = is_array($coupon_discounts) ? $coupon_discounts : [];
		$response['status'] = $status;
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

function rsvp_pricing_deleted($row) {
	if(!is_object($row) || empty($row->price))
		return false;
	return true;
}

function rsvp_pricing_ts ($row) {
	if(!is_object($row) || empty($row->price))
		$row = null;
	elseif(!empty($row->deadlineDate)) {
		$string = $row->deadlineDate.' ';
		$string .= ($row->deadlineTime) ? $row->deadlineTime : '23:59';
		$row->price_deadline = rsvpmaker_strtotime($string);
	}
	else
		$row->price_deadline = null;
	return $row;
}

function rsvp_pricing_date_time($row) {
	global $rsvp_options;
	if($row->price_deadline)
	{
		$row->deadlineDate = rsvpmaker_date('Y-m-d',$row->price_deadline);
		$row->deadlineTime = rsvpmaker_date('H:i:s',$row->price_deadline);
		$row->niceDeadline = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$row->price_deadline);
	}
	else
		$row->deadlineDate = $row->deadlineTime = '';
	$row->price = number_format((float) $row->price,2);
	$row->filtered= true;
	return $row;
}

function rsvp_get_pricing($post_id) {
	$p = (isset($_GET['reset'])) ? null : get_post_meta($post_id,'pricing',true);
	if($p && is_array($p))
	{
		$pricing = $p;
	}
	else {
		$pricing = [];
		$per = get_post_meta($post_id,'_per',true);
		if($per) {
			foreach($per['unit'] as $index => $unit)
			{
				$price = $per['price'][$index];
				$price_deadline = empty($per['price_deadline'][$index]) ? '' : $per['price_deadline'][$index];
				$price_multiple = empty($per['price_multiple'][$index]) ? 1 : $per['price_multiple'][$index];
				$pricing[] = (object) array('unit'=>$unit,'price'=>$price,'price_deadline'=>$price_deadline,'price_multiple'=>$price_multiple);
			}
	}	
	}
$pricing = array_map('rsvp_pricing_date_time',$pricing);
usort($pricing,'rsvp_price_compare');
if(!empty($per)) {//update from old format
	update_post_meta($post_id,'pricing',$pricing);
}
$pricing = array_filter($pricing,'rsvp_pricing_no_zeroes');
return $pricing;
}

function rsvp_pricing_no_zeroes($pricing) {
	return intval($pricing->price);
}

function rsvp_price_compare ($a,$b) {
	if(empty($a->price_deadline))
		$a->price_deadline = time()+YEAR_IN_SECONDS;
	if(empty($b->price_deadline))
		$b->price_deadline = time()+YEAR_IN_SECONDS;
	return $a->price_deadline - $b->price_deadline;
}

class RSVP_Template_Projected extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'template_projected';

		register_rest_route(
			$namespace,
			'/' . $path,
			array(

				array(

					'methods'             => array('GET'),

					'callback'            => array( $this, 'get_items' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)
		);

	}

	public function get_items_permissions_check( $request ) {
		return true;// current_user_can('edit_posts');
	}

	public function get_items( $request ) {
	$response = get_rsvpmaker_projected_api(intval($_GET['post']));
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

function get_rsvpmaker_projected_api($t) {
	global $rsvp_options,$post;
	$title = get_the_title($t);
	$return = array('title'=>$title,'dates'=>[]);
	$sched_result = get_events_by_template( $t );
	$holidays = commonHolidays();
	$return['action'] = admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $t );
	$exists = [];
	$now = time();
	foreach($sched_result as $event) {
		if($event->ts_start < $now)
			continue;
		$parts = explode(' ',$event->date);
		$exists[] = $event->date;
		$event->note = (isset($holidays[$parts[0]])) ? $holidays[$parts[0]]['name'] : '';
		$template_update = get_post_meta( $event->event, '_updated_from_template', true );
		$event->modified = ( ! empty( $template_update ) && ( $template_update != $event->post_modified ) ) ? __( 'Modified independently of template. Update could overwrite customizations.', 'rsvpmaker' ) : '';
		$event->dups = rsvpmaker_check_sametime($event->date,$event->event);
		$event->prettydate = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$event->ts_start);
		$event->id = $event->event;
		$event->type = 'existing';
		$return['dates'][] = $event;
	}
	$projected = rsvpmaker_get_projected( get_template_sked( $t ) );
	if ( $projected && is_array( $projected ) ) {
		foreach ( $projected as $i => $ts ) {
			if($ts < $now)
				continue;
			$year = rsvpmaker_date('Y',$ts);
			$month = rsvpmaker_date('m',$ts);
			$day = rsvpmaker_date('d',$ts);
			$datetime = rsvpmaker_date('Y-m-d H:i:s',$ts);
			$date = rsvpmaker_date('Y-m-d',$ts);
			$prettydate = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$ts);
			$note = (isset($holidays[$date])) ? $holidays[$date]['name'] : '';
			$dups = rsvpmaker_check_sametime($datetime);
			if(!in_array($datetime,$exists))
				$return['dates'][] = array('id'=>'p'.$ts,'type'=>'projected','datetime'=>$datetime,'year'=>$year,'month'=>$month,'day'=>$day,'note'=>$note,'dups'=>$dups);
		} // end for loop
	}
	return $return;
}


add_action('rest_api_init',
	function () {
		$rsvpmaker_sked_controller = new RSVPMaker_Sked_Controller();
		$rsvpmaker_sked_controller->register_routes();
		$rsvpmaker_by_type_controller = new RSVPMaker_By_Type_Controller();
		$rsvpmaker_by_type_controller->register_routes();
		$rsvpmaker_listing_controller = new RSVPMaker_Listing_Controller();
		$rsvpmaker_listing_controller->register_routes();
		$rsvpmaker_types_controller = new RSVPMaker_Types_Controller();
		$rsvpmaker_types_controller->register_routes();
		$rsvpmaker_authors_controller = new RSVPMaker_Authors_Controller();
		$rsvpmaker_authors_controller->register_routes();
		$rsvpmaker_guestlist_controller = new RSVPMaker_GuestList_Controller();
		$rsvpmaker_guestlist_controller->register_routes();
		$rsvpmaker_meta_controller = new RSVPMaker_ClearDateCache();
		$rsvpmaker_meta_controller->register_routes();
		$stripesuccess = new RSVPMaker_StripeSuccess_Controller();
		$stripesuccess->register_routes();
		$rsvpexp = new RSVP_Export();
		$rsvpexp->register_routes();
		$rsvpimp = new RSVP_RunImport();
		$rsvpimp->register_routes();
		$signed_up = new RSVPMaker_Signed_Up();
		$signed_up->register_routes();
		$email_lookup = new RSVPMaker_Email_Lookup();
		$email_lookup->register_routes();
		$sharedt = new RSVPMaker_Shared_Template();
		$sharedt->register_routes();
		$setup = new RSVPMaker_Setup();
		$setup->register_routes();
		$et = new RSVPMaker_Email_Templates();
		$et->register_routes();
		$nt = new RSVPMaker_Notification_Templates();
		$nt->register_routes();
		$deet = new RSVPMaker_Details();
		$deet->register_routes();
		$tz = new RSVPMaker_Time_And_Zone();
		$tz->register_routes();
		$tzevents = new RSVPMaker_Events_with_Timezone();
		$tzevents->register_routes();
		$flux = new RSVPMaker_Flux_Capacitor();
		$flux->register_routes();
		$daily = new RSVPMaker_Daily();
		$daily->register_routes();
		$preview = new RSVPMaker_Preview();
		$preview->register_routes();
		$pc = new RSVPMaker_PorC();
		$pc->register_routes();
		$conf = new RSVPMaker_Confirmation_Code();
		$conf->register_routes();
		$pi = new PostmarkIncoming();
		$pi->register_routes();
		$confmemb = new RSVPMaker_Confirm_Email_Membership();
		$confmemb->register_routes();
		$rsignup = new RSVPMail_Remote_Signup();
		$rsignup->register_routes();
		$flex = new RSVPMaker_Flex_Form();
		$flex->register_routes();
		$jm = new RSVPMaker_Json_Meta();
		$jm->register_routes();
		$form = new RSVPMaker_Form();
		$form->register_routes();
		$ropts = new RSVP_Options_Json();
		$ropts->register_routes();
		$d = new RSVP_Event_Date();
		$d->register_routes();
		$rconf = new RSVP_Confirm_Remind();
		$rconf->register_routes();
		$pricing = new RSVP_Pricing();
		$pricing->register_routes();
		$proj = new RSVP_Template_Projected();
		$proj->register_routes();
	}
);
