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
$check = implode('|',$audience).$data->Subject;
$last = get_transient('postmark_last_incoming');
if($check == $last) {
	rsvpmaker_debug_log($check,'incoming duplicate');
	return;
}
set_transient('postmark_last_incoming',$check,time()+30);
$origin = 'Message originally From: '.$data->From.', To: '.implode(', ',$tolist);
$origin .= ' (<a href="mailto:'.implode(',',$tolist).'?subject='.$data->Subject.'">Reply</a>)';
if(!empty($cclist))
	$origin .= ', CC: '.implode(', ',$cclist);
$origin = '<div style="background-color:#fff; color: black; margin-top: 20px; padding: 10px;">Forwarded by the <a href="https://rsvpmaker.com">RSVPMaker</a> Mailer. '.$origin.'</div>';
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
		//rsvpmaker_debug_log($form_id);
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
		global $wpdb, $rsvp_options;
		$json = file_get_contents('php://input');
		if(!empty($json)) {
			$data = json_decode(trim($json));
			if($data && !current_user_can('manage_options',$post_id))
				return new WP_REST_Response( array('status' => 'User does not have rights to edit this document'), 401 );	
		} 
		$form = get_post($rsvp_options['rsvp_form']);
		$response['blocksdata'] = parse_blocks($form->post_content);
		$response['button_style'] = get_post_meta($rsvp_options['rsvp_form'],'rsvp_button_style',true);
		if(!$response['button_style'])
			$response['button_style'] = array('color'=>'#ffffff','backgroundColor'=>'#000000','padding'=>'5px');
		$response['button_label'] = get_post_meta($rsvp_options['rsvp_form'],'rsvp_button_label',true);
		if(!$response['button_label'])
			$response['button_label'] = 'RSVP Now';
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
							$rsvp_options[$o->key] = sanitize_rsvpopt($o->value);
							$changes++;
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
			$response = array('changes'=>$changes,'actions'=>$actions,'data'=>$data);
			return new WP_REST_Response( $response , 200 );	
		} 
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
	}
);
