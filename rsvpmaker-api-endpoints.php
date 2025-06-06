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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');

		$events = rsvpmaker_get_future_events(null,15);

		if ( empty( $events ) ) {

			$events = [];

		}
		foreach($events as $index => $post) {
			$events[$index]->permalink = get_permalink($post->ID);
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		$wp_query = rsvpmaker_upcoming_query(array('type'=>$request['type']));
		$posts    = $wp_query->get_posts();
		if ( empty( $posts ) ) {
			return new WP_Error( 'empty_category', 'there is no post in this category ' . $request['type'], array( 'status' => 404 ) );
		}
		foreach($posts as $index => $post) {
			$posts[$index]->permalink = get_permalink($post->ID);
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');

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
		$gift_certificate = '';
        if(!empty($vars['purchase_code'])) {
          $purchase = get_transient($vars['purchase_code']);
          if(!empty($purchase[2]))
            $details['rsvp_to'] = sanitize_text_field($purchase[2]);
          if(!empty($vars['is_gift_certificate'])) {
			$rsvptable = $wpdb->prefix.'rsvpmaker';
			$details = $wpdb->get_var("SELECT details FROM $rsvptable WHERE id=$rsvp_id");
			$details = unserialize($details); 
            $gift_certificate = 'GIFT'.wp_generate_password(12, false, false);
            $vars['gift_certificate'] = $details['gift_certficate'] = $gift_certificate;
            $sql = $wpdb->prepare("UPDATE $rsvptable set details=%s WHERE id=$rsvp_id",serialize($details));
			error_log($sql);
            $wpdb->query($sql);
            add_option($gift_certificate,trim(preg_replace('/[^0-9\.]/','',$purchase[1])));
          }
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');

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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $default_tz;
		$last_tz = '';
		$events  = array();
		$list    = rsvpmaker_get_future_events( array( 'limit' => 10 ) );
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $default_tz, $rsvp_options, $post;
		$originaltime = $time   = sanitize_text_field( $_POST['time'] );
		$end    = sanitize_text_field( $_POST['end'] );
		$tz     = sanitize_text_field( $_POST['tzstring'] );
		$format = sanitize_text_field( $_POST['format'] );
		$timezone_abbrev = sanitize_text_field($_POST['timezone_abbrev']);
		$post   = get_post( $_POST['post_id'] );
		$time   = rsvpmaker_strtotime( $time );
		$s3 = rsvpmaker_date( 'T', $time );
		if ( $end ) {
			$end = rsvpmaker_strtotime( $end );
		}
		date_default_timezone_set( $tz );
		// strip off year
		$times['new_time'] = date('Y-m-d H:i:s',$time);
		$times['original_time'] = $originaltime;
		if($times['new_time'] == $times['original_time'])
			$times['content'] = '';
		else {
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
		rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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

//todo more flexible blacklist system
$blacklist = ['tmtpres@gmail.com'];
if(in_array($data->From,$blacklist)) {
	return;
}

$origin = sprintf("<p>Forwarded message, originally <br />From <a href=\"mailto:%s\">%s</a><br />To: %s<br />Cc: %s<br /><a href=\"mailto:%s?cc=%s&subject=Re: %s\">Reply All</a></p>",$data->From,$data->From,htmlentities($data->To),htmlentities($data->Cc),$data->From,implode(',',$audience),$data->Subject);
$origin .= '<div class="postmark-origin" style="padding:10px; background-color:#efefef">'.$origin.'</div>';
$check = implode('|',$audience).$data->Subject;
$last = get_transient('postmark_last_incoming');
if($check == $last) {
	//rsvpmaker_debug_log($check,'incoming duplicate');
	return;
}
set_transient('postmark_last_incoming',$check,30);
if(empty($data->HtmlBody))
	$data->HtmlBody = '<html><body>'.nl2br($data->TextBody).$origin.'</body></html>';
else
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb;
		//patchstack fix, filter out anything other than email
		$email = rsvpmail_contains_email($request['email']);
		if($email)
		{
			$table = rsvpmaker_guest_list_table();
			$sql = "select id from $table where email LIKE '".esc_sql($email)."' ";
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb;
		$email = '';
		if(isset($_POST['em']))
			$email = trim($_POST['em']);
		elseif(isset($_POST['email']))
			$email = trim($_POST['email']);

		$origin = get_http_origin();

		if(!strpos($origin,$_SERVER['SERVER_NAME'])) //(check_ajax_referer('rsvp_mailing_list','rsvp_mailing_list', false))
			{
				$result['message'] = 'Security check failed '.$origin;
			}
		elseif(!empty($_POST['extra_special_discount_code']))
			$result['message'] = 'Something went wrong, sorry';
		elseif(is_email($email))
		{   
			$first = isset($_POST['first']) ? sanitize_text_field($_POST['first']) : '';
			$last = isset($_POST['last']) ? sanitize_text_field($_POST['last']) : '';
			$result['message'] = strip_tags(rsvpmaker_guest_list_add($email,$first,$last,'',0));
			$result['success'] = true;
			$result['code'] = urldecode($request['code']);
			$result['key'] = get_rsvpmail_signup_key();
		}
		else {
			$result['message'] = 'Please enter a valid email address. You entered: '.$email;
			$result['success'] = false;
		}
		$result['postwas'] = $_POST;
		add_post_meta(1,'rsvpmail_signup',var_export($_POST,true)."\n\n".var_export($_SERVER,true)."\n\n".var_export($result,true));
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb, $rsvp_options, $post, $current_user;
		$json = file_get_contents('php://input');
		$updated = array();
		$post_id = (empty($_GET['post_id']) || !is_numeric($_GET['post_id'])) ? 0 : intval($_GET['post_id']);
		$template_id = ($post_id) ? get_post_meta($post_id,'_meet_recur',true) : 0;
		$post = ($post_id) ? get_post($post_id) : null;
		$reusable = get_option('rsvpmaker_forms', array());
		if(isset($_GET['contact']) && 'undefined' != $_GET['contact']) {
			if(!in_array('Form:Contact',$reusable))
			{
				$updated['post_title'] = 'Form:Contact';
				$updated['post_type'] = 'rsvpmaker_form';
				$updated['post_author'] = $current_user->ID;
				$updated['post_content'] = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Phone","slug":"phone"} /-->

<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} /-->

<!-- wp:rsvpmaker/formnote /-->';
				$updated['post_status'] = 'publish';
				$form_id = wp_insert_post($updated);
				$reusable[$form_id] = 'Form:Contact';
				update_option('rsvpmaker_forms',$reusable);
		}
	}
		if(!empty($_GET['form_id'])) {
			if((strpos($_GET['form_id'],'clone') !== false) && (current_user_can('manage_options') || current_user_can('edit_post',$post_id)))
			{
				$reusable_name = '';
				$form_id = $_GET['form_id'];
				if(strpos($form_id,'|'))
				{
					$parts = explode('|',$form_id);
					$form_id = $parts[0];
					$reusable_name = sanitize_text_field($parts[1]);
				}
				$form_id = intval(str_replace('clone','',$form_id));
				$form = get_post($form_id);
				if($form) {
					$response['copied'] = $form->post_title;
					if($reusable_name)
						$title = 'Form:'.$reusable_name;
					elseif($post_id)
						$title = 'Form for Post '.$post_id;
					else {
						$reusable_name = date('r');
						$title = 'Form:'.$reusable_name;
					}
					$updated['post_title'] = $title;
					$updated['post_type'] = 'rsvpmaker_form';
					$updated['post_author'] = $current_user->ID;
					$updated['post_content'] = $form->post_content;
					$updated['post_status'] = 'publish';
					$updated['post_parent'] = $post_id;
					$form_id = wp_insert_post($updated);
					$updated['ID'] = $form_id;
					$form = (object) $updated;
					update_post_meta($post_id,'_rsvp_form',$form_id);
					$response['form_changed'] = $form_id;
					if($reusable_name) {
						$reusable[$form_id] = $updated['post_title'];
						update_option('rsvpmaker_forms',$reusable);
					}
				}
			}
			else {
				$form_id = intval($_GET['form_id']);
				if($post_id && current_user_can('edit_post',$post_id))
					update_post_meta($post_id,'_rsvp_form',$form_id);	
			}
		}
		elseif($post_id) {
			$form_id = get_post_meta($post_id,'_rsvp_form',true);
			$response['form_id_from_meta'] = $form_id;
		}
		if(empty($form_id)) {
			if(isset($_GET['contact']) && 'undefined' != $_GET['contact']) {
				$form_id = array_search('Form:Contact',$reusable);
			}
			else
				$form_id = $rsvp_options['rsvp_form'];
		}
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
			if(!empty($data->start) && $post_id)
				update_post_meta($post_id,'_rsvp_start',rsvpmaker_strtotime($data->start));
			if(!empty($data->deadline) && $post_id)
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
						$reusable = get_option('rsvpmaker_forms', array());
						$reusable[$form_id] = $updated['post_title'];
						$response['form_changed'] = $form_id;
						update_option('rsvpmaker_forms',$reusable);
				}
				else {
					$updated['ID'] = $form_id;
					$updated['post_content'] = $output;
					$upid = wp_update_post($updated);	
				}	
			}
		}
		$form = get_post($form_id);
		if(empty($form) || !strpos($form->post_content,'rsvpmaker/formfield')) {
			$form_id = upgrade_rsvpform(false,$form_id); // missing or corrupted form
			$form = get_post($form_id);
			$response['attempted_reset'] = true;
		}

		$response['form_id'] = $form_id;
		$response['post_status'] = $post->post_status;
		$response['form_title'] = $form->post_title;
		$response['form_parent'] = ($form->post_parent) ? $form->post_parent : 0;
		$response['default_form'] = $rsvp_options['rsvp_form'];
		$custom_form = ($post_id && ($post_id == $form->post_parent));
		$event_template = ($post_id) ? get_post_meta($post_id,'_rsvp_recur',true) : 0;
		$template_form = ($event_template) ? intval(get_post_meta($event_template,'_rsvp_form',true)) : 0;
		$response['default_form'] = $rsvp_options['rsvp_form'];
		$response['is_default'] = ($form_id == $rsvp_options['rsvp_form']);
		$response['is_inherited'] = ($form_id == $template_form);
		$response['form'] = parse_blocks($form->post_content);
		$response['form_options'][] = array('value'=>'','label'=>__('Select/Edit Form','rsvpmaker'));

		if($custom_form)
			$response['form_options'][] = array('value'=>$form_id,'label'=>__('Custom Form for This Event','rsvpmaker'));
		$includedform = array($rsvp_options['rsvp_form']);
		if($template_form)
		{
			$response['form_options'][] = array('value'=>$template_form,'label'=>__('Edit Form for Event Template','rsvpmaker'));
			if($post_id)
				$response['form_options'][] = array('value'=>'clone'.$template_form,'label'=>__('Clone Form for Event Template','rsvpmaker'));
			$includedform[] = $template_form;
		}
		$response['form_options'][] = array('value'=>$rsvp_options['rsvp_form'],'label'=>__('Edit Default Form','rsvpmaker'));
		$response['form_options'][] = array('value'=>'clone'.$rsvp_options['rsvp_form'],'label'=>__('Clone Default Form','rsvpmaker'));
		if(empty($reusable))
			$reusable = get_option('rsvpmaker_forms');
		$response['reusable'] = $reusable;
		if(is_array($reusable)) {
			$reusable_filtered = array();
			foreach($reusable as $value => $label) {
				if('publish' == get_post_status($value)) {
					$reusable_filtered[$value] = $label;
				if(!in_array($value,$includedform)) {
					$label = str_replace('Form:','',$label);
					$response['form_options'][] = array('value'=>$value,'label'=>'Edit '.$label);
					$response['form_options'][] = array('value'=>'clone'.$value,'label'=>'Clone '.$label);
					if($value == $form_id)
						$response['is_reusable'] = 'Reusable Form: '.$label;
				}
				$includedform[] = $value;
				}

			}
			if(sizeof($reusable) != sizeof($reusable_filtered))
				update_option('reusable_forms',$reusable_filtered);
		}
		if($response['is_default'])
			$response['current_form'] = 'Default';
		elseif($response['is_inherited'])
			$response['current_form'] = 'Inherited from Template';
		elseif(!empty($response['is_reusable']))
			$response['current_form'] = $response['is_reusable'];
		else
			$response['current_form'] = $form->post_title;
		$response['tweaked'] = 'test';
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
							$status[] = "rsvp option change $o->key"; // $o->value
							$rsvp_options[$o->key] = sanitize_rsvpopt($o->value);
							$changes++;
						}
						elseif('mergearray' == $o->type)
						{
							$p = get_option($o->key, array());
							if(empty($p) && (strpos($o->key,'stripe') || strpos($o->key,'paypal')))
								$p = array('pk'=>'','sk'=>'','sandbox_pk'=>'','sandbox_sk'=>'');
							$changes = (array) $o->value;
							$chkeys = [];
							foreach($changes as $chkey => $change) {
								if($change != 'set')
									$p[$chkey] = sanitize_text_field($change);
								if(('mode' == $chkey) && ('rsvpmaker_paypal_rest_keys' == $o->key))
									$p['sandbox'] = ('sandbox' == $change) ? '1' : '0';
							}
							$mergeresult = $p;
							update_option($o->key,$p);
						}
					}
				}
			}
			if($changes)
				update_option( 'RSVPMAKER_Options',$rsvp_options );
			$response = array('changes'=>$changes,'actions'=>$actions,'data'=>$data,'status'=>$status,'mergeresult'=>$mergeresult);
			return new WP_REST_Response( $response , 200 );	
		}

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
					$response['stripe']['webhook'] = 'set';	
				}
				else {
					$response['stripe']['sk'] = '';
					$response['stripe']['pk'] = '';	
					$response['stripe']['webhook'] = '';
				}		
				if(!empty($stripe['sandbox_pk']) && !empty($stripe['sandbox_sk'])) {
					$response['stripe']['sandbox_sk'] = 'set';
					$response['stripe']['sandbox_pk'] = 'set';	
					$response['stripe']['sandbox_webhook'] = 'set';	
				}
				else {
					$response['stripe']['sandbox_sk'] = '';
					$response['stripe']['sandbox_pk'] = '';	
					$response['stripe']['sandbox_webhook'] = '';	
				}		
				$response['stripe']['mode'] = (empty($stripe['mode'])) ? 'production' : $stripe['mode'];
				$response['stripe']['notify'] = (empty($stripe['notify'])) ? '' : $stripe['notify'];
			}
			$pp = get_option('rsvpmaker_paypal_rest_keys');
			if(!is_array($pp))
				{
					$response['paypal']['client_id'] = '';
					$response['paypal']['client_secret'] = '';
					$response['paypal']['sandbox_client_id'] = '';
					$response['paypal']['sandbox_client_secret'] = '';
					$response['paypal']['funding_sources'] = '';
					$response['paypal']['excluded_funding_sources'] = '';
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
				if (!empty($pp['sandbox_client_id']) && !empty($pp['sandbox_client_secret']))
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
				$response['paypal']['funding_sources'] = empty($pp['funding_sources']) ? '' : $pp['funding_sources'];
				$response['paypal']['excluded_funding_sources'] = empty($pp['excluded_funding_sources']) ? '' : $pp['excluded_funding_sources'];
			}

		$response['rsvp_options'] = $rsvp_options;
		$email = get_option('admin_email');
		$blogname = get_bloginfo('name');
		// default values
		$options = array(
		'email-from' => $email
		,'email-name' => $blogname
		,'reply-to' => $email
		,'chimp-key' => ''
		,'chimp-list' => ''
		,'mailing_address' => ''
		,'chimp_add_new_users' => ''
		,'company' => $blogname
		,"add_notify" => $email
		);
		$response['chimp'] = get_option('chimp',$options);
		if($response['chimp'] && !empty($response['chimp']['chimp-key']) )
			$response['chimp_lists'] = mailchimp_list_array($response['chimp']['chimp-key']);
		else
			$response['chimp_lists'] = [];
		$response['test'] = 'x';
		$response['smtp_test'] = admin_url('options-general.php?page=rsvpmaker-admin.php&smtptest=1');
		$response['mailing_list_settings'] = admin_url('options-general.php?page=rsvpmaker-admin.php');
		$response['current_user_id'] = $current_user->ID;
		$response['current_user_email'] = $current_user->user_email;
		$response['edit_url'] = admin_url('post.php?action=edit&post=');
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb, $rsvp_options, $current_user;
		$event_table = $wpdb->prefix.'rsvpmaker_event';
		$post_id = $event_id = intval($_GET['event_id']);
		$type = get_post_type($post_id);
		$rsvpmeta = array("_rsvp_to", "_rsvp_instructions", "simple_price", "simple_price_label", "venue", "_sked_minutes", "_sked_stop", "_sked_duration", "_payment_gateway", "_rsvp_currency", "_rsvp_end_display", "_sked_start_time", "_sked_end");
		$rsvpnumber = array("_rsvp_max", "_template_start_hour", "_template_start_minutes", "_sked_hour", "rsvp_tx_template", "_rsvp_deadline_daysbefore", "_rsvp_deadline_hours", "_rsvp_reg_daysbefore", "_rsvp_reg_hours","_rsvp_start", "_rsvp_deadline");
		$rsvpbool = array("_rsvp_on","_rsvp_show_attendees", "_rsvp_count_party", "_add_timezone", "_convert_timezone", "_calendar_icons", "_rsvp_rsvpmaker_send_confirmation_email", "_rsvp_confirmation_after_payment", "_rsvp_confirmation_include_event", "_rsvp_count", "_rsvp_yesno", "_rsvp_captcha", "_rsvp_login_required", "_rsvp_form_show_date","rsvpautorenew");
		$templatemeta = array("_sked_Varies", "_sked_First", "_sked_Second", "_sked_Third", "_sked_Fourth", "_sked_Last", "_sked_Every", "_sked_Sunday", "_sked_Monday", "_sked_Tuesday", "_sked_Wednesday", "_sked_Thursday", "_sked_Friday", "_sked_Saturday", "rsvpautorenew");

		$json = file_get_contents('php://input');
		if(!empty($_POST) || !empty($json))
		{
			if(!current_user_can('edit_post',$event_id))
			return new WP_REST_Response( 'user does not have editing rights for this event', 401 );
		}
		$status = $upsql = '';
		$event = get_rsvpmaker_event($event_id);// $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=$event_id");
		if(!empty($json)) {
			$data = json_decode(trim($json));
			if(isset($data->date)) //retry submission
			{
				$ts_start = rsvpmaker_strtotime($data->date);
				if(empty($data->enddate) || $data->enddate < $data->date) {
					$data->enddate = $data->date;
					$ts_end = $ts_start + 3600; // default to one hour
					$data->enddate = rsvpmaker_date('Y-m-d H:i:s',$ts_end);
				}
				else
					$ts_end = rsvpmaker_strtotime($data->enddate);
				$nv['date'] = $data->date;
				$nv['enddate'] = $data->enddate;
				$nv['ts_start'] = $ts_start;
				$nv['ts_end'] = $ts_end;
				if($event) {
					$event->date = $data->date;
					$event->enddate = $data->enddate;	
					$event->ts_start = $ts_start;
					$event->ts_end = $ts_end;
				}
				delete_transient('rsvpmakers');
			}
			elseif(isset($data->enddate)) // end date set independently
			{
				$ts_end = rsvpmaker_strtotime($data->enddate);
				if($event) {
					if(($event->ts_start > $ts_end)) {
						$event->ts_start = intval($event->ts_start);
						$ts_end = $event->ts_start + HOUR_IN_SECONDS; // default to one hour after start date
						$data->enddate = rsvpmaker_date('Y-m-d H:i:s',$ts_end);
					}
					$event->enddate = $data->enddate;
					$event->ts_end = $ts_end;
				}
				$nv['enddate'] = $data->enddate;
				$nv['ts_end'] = $ts_end;
			}
			if(isset($data->timezone))
			{
				if ('rsvpmaker_template' == $type) {
					update_post_meta($event_id,'timezone',$data->timezone);
				}
				else
					$nv['timezone'] = $data->timezone;
				if($event) {
					$event->timezone = $data->timezone;
				}
			}
			if(isset($data->display_type)) // end date set independently
			{
				$nv['display_type'] = $data->display_type;
				if($event) {
					$event->display_type = $data->display_type;
				}
			}
			if(isset($data->metaKey) && isset($data->metaValue)) {
				$status = 'updated '.$data->metaKey;
				if(in_array($data->metaKey,$rsvpmeta))
					update_post_meta($post_id,$data->metaKey,sanitize_text_field($data->metaValue));
				elseif(in_array($data->metaKey,$rsvpnumber))
					update_post_meta($post_id,$data->metaKey,intval($data->metaValue));
				elseif(in_array($data->metaKey,$rsvpbool) || in_array($data->metaKey,$templatemeta))
					update_post_meta($post_id,$data->metaKey,boolval($data->metaValue));
				else
					$status = $data->metaKey.' not found';
			}
			if(!empty($nv)) {
				$result = $wpdb->update($event_table,$nv,array('event' => $event_id));
				$upsql = $wpdb->last_query;
			}
		}

		if(!$event) {
			$type = get_post_type($event_id);
			if('rsvpmaker_template' != $type)
			{
				rsvpmaker_add_event_row($event_id,date('Y-m-d H:i:s',strtotime('tomorrow 12:00')),date('Y-m-d H:i:s',strtotime('tomorrow 13:00')),'');
				$event = get_rsvpmaker_event($event_id);
				if(!$event)
					return new WP_REST_Response( array('message'=>'error adding default dates', 'debug'=>var_export($event)), 200 );	
			}
			else
				$event = (object) array('event' => $event_id,'type' => $type);
		}
		$event->upsql = $upsql;
		$event->status = $status;
		$event->tzchoices = timezone_identifiers_list();
		$meta_all = get_post_meta($event_id);
		if(empty($meta_all)) {
			$meta = array();
			foreach($rsvp_options as $key => $values) {
				$meta['using_defaults'] = true;
				$key = '_'.$key;
				if(in_array($key,$rsvpmeta))
					$meta[$key] = $values[0];
				if(in_array($key,$rsvpnumber))
					$meta[$key] = intval($values[0]);
				if(in_array($key,$rsvpbool) || in_array($key,$templatemeta))
					$meta[$key] = boolval($values[0]);
			}			
		}
		else {
			$meta['using_defaults'] = false;
			foreach($meta_all as $key => $values) {
				if(in_array($key,$rsvpmeta))
					$meta[$key] = $values[0];
				if(in_array($key,$rsvpnumber))
					$meta[$key] = intval($values[0]);
				if(in_array($key,$rsvpbool) || in_array($key,$templatemeta))
					$meta[$key] = boolval($values[0]);
				if(!isset($meta['rsvp_on']))
					$meta['rsvp_on'] = boolval(get_post_meta($post_id,'_rsvp_on',true));
			}
		}
		foreach($rsvpmeta as $key) {
			if(empty($meta[$key]))
				$meta[$key] = '';
		}
		foreach($rsvpnumber as $key) {
			if(empty($meta[$key]))
				$meta[$key] = 0;
		}
		foreach($rsvpbool as $key) {
			if(empty($meta[$key]))
				$meta[$key] = false;
		}
		if ('rsvpmaker_template' == $type) {
			foreach($templatemeta as $key) {
				if(empty($meta[$key]))
					$meta[$key] = false;
			}
			if(empty($meta['_sked_start_time']))
			{
				if(empty($meta_all['_sked_hour'][0])) {
					$meta['_sked_start_time'] = $rsvp_options['defaulthour'].':'.$rsvp_options['defaultmin'];
					$t = strtotime('today '.$meta['_sked_start_time']);
					$meta['_sked_start_time'] = date('H:i:s',$t);
					$meta['_sked_end'] = date('H:i:s',$t + HOUR_IN_SECONDS);
				}
				else {
					$meta['_sked_start_time'] = $meta_all['_sked_hour'][0].':'.$meta_all['_sked_minutes'][0];
					$t = strtotime('today '.$meta['_sked_start_time']);
					$meta['_sked_start_time'] = date('H:i:s',$t);
					$end = empty($meta['_sked_end']) ? $t + HOUR_IN_SECONDS : strtotime($meta['_sked_end']);
					if($t > $end)
						$end = $t + HOUR_IN_SECONDS;
					$meta['_sked_end'] = date('H:i:s',$end);
				}
			}
			//sanity check
			$t = strtotime($meta['_sked_start_time']);
			$end = (empty($meta['_sked_end'])) ? 0 : strtotime($meta['_sked_end']);
			if($t > $end)
			{
			$end = $t + HOUR_IN_SECONDS;
			$meta['_sked_end'] = date('H:i:s',$end);
			update_post_meta($post_id,'_sked_end',$meta['_sked_end']);
			}
		}
		if ('rsvpmaker_template' == $type) {
			$event->timezone = get_post_meta($event_id,'_timezone',true);
			if(empty($event->timezone)) {
				$event->timezone = wp_timezone_string();
				update_post_meta($event_id,'_timezone',$event->timezone);
			}
		}
		$event->meta = $meta;
		$event->has_template = rsvpmaker_has_template($event_id);
		$event->template_edit = ($event->has_template) ? admin_url('post.php?action=edit&post='.$event->has_template) : '';
		$form_id = get_post_meta($event_id,'_rsvp_form',true);
		$event->form_id = ($form_id) ? $form_id : $rsvp_options['rsvp_form'];
		$event->default_form = empty($form_id) || $form_id == $rsvp_options['rsvp_form'];
		$conf_id = get_post_meta($event_id,'_rsvp_confirm',true);
		$event->default_confirmation = empty($conf_id) || $conf_id == $rsvp_options['rsvp_confirm'];

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb, $current_user, $post;
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
		$confirm_post = rsvp_get_confirm( $post_id, true );;
		if($post_id && $post_id != $confirm_post->post_parent) {
			$response['copied'] = $confirm_post->post_title;
			$updated['post_title'] = 'Confirmation:'.$post->post_title.' ('.$post_id.')';
			$updated['post_type'] = 'rsvpemail';
			$updated['post_author'] = $current_user->ID;
			$updated['post_content'] = $confirm_post->post_content;
			$updated['post_status'] = 'publish';
			$updated['post_parent'] = $post_id;
			$confirm_id = wp_insert_post($updated);
			$updated['ID'] = $confirm_id;
			$confirm_post = (object) $updated;
			update_post_meta($post_id,'_rsvp_confirm',$confirm_id);
		}

$response["confirmation"] = $confirm_post;
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
			if('item_prices' == $data->update) {
				update_post_meta($post_id,'_rsvp_item_prices',$data->change);
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
		$form = get_post(get_post_meta($post_id,'_rsvp_form',true));
		if($form and isset($form->post_content))		
		$response['form_fields'] = rsvpmaker_data_from_document($form->post_content);	
		$response['item_prices'] = rsvpmaker_item_pricing($post_id);
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

class RSVP_Editor_Loop_Excerpt extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'excerpt/(?P<post_id>[A-Z0-9a-z_\-]+)';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	global $post;
	$backup = $post;
	$post_id = $request['post_id'];
	$post = get_post($post_id);
	$response['post_id'] = $post_id;
	$response['rsvp_on'] = (get_post_meta($post_id,'_rsvp_on',true)) ? '<div class="rsvp_button">'.get_rsvp_link( $post_id ).'</div>' : '';
	$terms = get_the_term_list( $post_id, 'rsvpmaker-type', '', ', ', ' ' );
	if ( $terms && is_string( $terms ) ) {
		$response['types'] = __( 'Event Types', 'rsvpmaker' ).': '.$terms;
	}
	else
		$response['types'] = '';
	$d = rsvp_date_block( $post_id, get_post_custom( $post_id ) );
	$response['dateblock'] = $d['dateblock'];
	$max = (isset($_GET['max'])) ? intval($_GET['max']) : 55;
	$response["excerpt"] = strip_tags(rsvpmaker_excerpt_body($post, $max));
	$post = $backup;
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_Title_Date extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'title-date/(?P<post_id>[A-Z0-9a-z_\-]+)';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	global $post, $rsvp_options;
	$backup = $post;
	$post_id = $request['post_id'];
	$post = get_post($post_id);
	$response['title'] = $post->post_title;
	$event = get_rsvpmaker_event($post_id);
	$response['date'] = rsvpmaker_date($rsvp_options['long_date'],$event->ts_start,$event->timezone);
	$response['time'] = rsvpmaker_date($rsvp_options['time_format'],$event->ts_start,$event->timezone);
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_Calendar extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'calendar';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	$atts = $_GET;
	$atts['calendar_block'] = true;
	$response['calendar'] = rsvpmaker_calendar($atts);
	$response['calendar'] = preg_replace('/href="[^"]+"/','href="#"',$response['calendar']); //disable links in editor preview
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_Date_Block extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'dateblock';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	global $wp_query, $post;
	$atts = $_GET;
	$response['dateblock'] = rsvpdateblock($atts);
	if(strpos($response['dateblock'],'tz_converter'))
		$response['dateblock'] = preg_replace('/<div class="tz_converter"[^<]+/','<div class="tz_converter"><a href="#">Show in my timezone</a>',$response['dateblock']);
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_Date_Element extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'date-element';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	global $wp_query, $post;
	$formats = ["l F j, Y g:i A", "l F j, g:i A", "M j g:i A","l g:i A","g:i A","H:i"];
	$atts = $_GET;
	if(isset($atts['start_format']) && !in_array($atts['start_format'],$formats)) {
		$formats[] = $atts['start_format'];
	}
	if(isset($atts['end_format']) && !in_array($atts['end_format'],$formats)) {
		$formats[] = $atts['end_format'];
	}
	$atts['editor'] = 1;
	$post = get_post(intval($atts['post_id']));
	$event = get_rsvpmaker_event(intval($atts['post_id']));
	if(empty($event) || empty($post)) {
		return;
	}
	$post->date = $event->date;
	$post->enddate = $event->enddate;
	$post->ts_start = $event->ts_start;
	$post->ts_end = $event->ts_end;
	$post->timezone = $event->timezone;
	foreach($formats as $format) {
		$response['start_formats'][] = array('value'=>$format,'label'=>rsvpmaker_date($format,$event->ts_start,$event->timezone));
		$response['end_formats'][] = array('value'=>$format,'label'=>rsvpmaker_date($format,$event->ts_end,$event->timezone));
	}		
	$response['element'] = rsvpmaker_date_element($atts);
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_Preview extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'upcoming_preview';

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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	global $wp_query;
	$atts = $_GET;
	$atts['calendar_block'] = true;
	$response['calendar'] = rsvpmaker_upcoming($atts);
	$response['calendar'] = preg_replace('/href="[^"]+"/','href="#"',$response['calendar']); //disable links in editor preview
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
	$row->price = number_format((float) $row->price,2,'.','');
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
		if(!empty($per) && !empty($per['unit'])) {
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
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
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
	$checkdates = array();
	foreach($sched_result as $event) {
		if($event->ts_start < $now)
			continue;
		$parts = explode(' ',$event->date);
		$exists[] = $event->date;
		$event->note = (isset($holidays[$parts[0]])) ? $holidays[$parts[0]]['name'] : '';
		$template_update = get_post_meta( $event->event, '_updated_from_template', true );
		$event->modified = ( ! empty( $template_update ) && ( $template_update != $event->post_modified ) ) ? __( 'Modified independently of template. Update could overwrite customizations.', 'rsvpmaker' ) : '';
		$event->dups = rsvpmaker_check_sametime($event->date,$event->event);
		$event->prettydate = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$event->ts_start,$event->timezone);
		$checkdates[] = rsvpmaker_date('Y-m-d',$event->ts_start,$event->timezone);
		$event->id = $event->event;
		$event->type = 'existing';
		$return['dates'][] = $event;
	}
	$projected = rsvpmaker_get_projected( get_template_sked( $t ) );
	if ( $projected && is_array( $projected ) ) {
		foreach ( $projected as $i => $ts ) {
			if($ts < $now)
				continue;
			$check = rsvpmaker_date('Y-m-d',$ts);
			if(in_array($check,$checkdates))
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

class RSVP_Report_API extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'rsvp_report';

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
		return ($_GET['code'] == get_option('rsvp_report_api_code'));// check for code
	}

	public function get_items( $request ) {
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	$response = rsvp_report_api();
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_PayPalPaid extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'paypal_paid';

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
		return true; //($_GET['code'] == get_option('rsvp_report_api_code'));// check for code
	}

	public function get_items( $request ) {
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
	$response = paypal_verify_rest ();
	return new WP_REST_Response( $response, 200 );
	}//end handle
}//end class

class RSVP_PayPalWebHook extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'paypal_webhook';

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
		return true; //($_GET['code'] == get_option('rsvp_report_api_code'));// check for code
	}

	public function get_items( $request ) {
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb;
		$params = $request->get_json_params();
		if(!empty($params)) {
			$type = $params['event_type'];
			if('PAYMENT.CAPTURE.COMPLETED' == $type) {
				$order_id = (empty($params['resource']['supplementary_data']['related_ids']['order_id'])) ? '' : sanitize_text_field($params['resource']['supplementary_data']['related_ids']['order_id']);
				$fee = (empty($params['resource']['seller_receivable_breakdown']['paypal_fee']['value'])) ? '' : sanitize_text_field($params['resource']['seller_receivable_breakdown']['paypal_fee']['value']);
				$gross = (empty($params['resource']['seller_receivable_breakdown']['gross_amount']['value'])) ? '' : sanitize_text_field($params['resource']['seller_receivable_breakdown']['gross_amount']['value']);
				$rsvpmaker_money = $wpdb->prefix.'rsvpmaker_money';
				$order_id = esc_sql($order_id);
				$fee = esc_sql($fee);
				$gross = esc_sql($gross);
				$sql_check = "SELECT * FROM $rsvpmaker_money WHERE transaction_id='$order_id'";
				$existing = $wpdb->get_row($sql_check);
				if($existing)
				$sql = "UPDATE $rsvpmaker_money SET fee=$fee WHERE transaction_id='$order_id'";
				else
				$sql = "INSERT INTO $rsvpmaker_money SET amount='$gross', fee='$fee', name='added from webhook', transaction_id='$order_id' ";
				$wpdb->query($sql);
			}
		} 
	return new WP_REST_Response( [], 200 );
	}//end handle
}//end class

class RSVP_CopyDefaults extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'copy_defaults';

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
		return current_user_can('manage_options');
	}

	public function get_items( $request ) {
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		$found = '';
		$templates = rsvpmaker_get_templates();
		$events = rsvpmaker_get_future_events();
		foreach($templates as $post) {
			rsvpmaker_defaults_for_post($post->ID);
			if(!empty($found))
				$found .= ', ';
			$found .= 'template: '.$post->post_title;
		}
		foreach($events as $post) {
			rsvpmaker_defaults_for_post($post->ID);
			if(!empty($found))
				$found .= ', ';
			$found .= $post->post_title.' '.$post->date;
		}
		$response = array('updated'=>'Updated: '.$found);
	return new WP_REST_Response($response, 200 );
	}//end handle
}//end class

class RSVP_Contact_Form extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'rsvpmaker/v1';
		$path      = 'contact_form';

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
		global $current_user;
		$current_user = get_userdata(intval($_POST['user_id']));
		return (empty($_POST['extra_special_discount_code']) && wp_verify_nonce( $_POST['contact_confidential'], 'rsvpmaker_contact' ));
	}

	public function get_items( $request ) {
	rsvpmaker_debug_log($_SERVER['SERVER_NAME'].' '.$_SERVER['REQUEST_URI'],'rsvpmaker_api');
		global $wpdb, $current_user, $rsvp_options;
		$postdata = $_POST;
		if(!empty($postdata['contact_subject']))
			$rsvp['subject'] = sanitize_text_field($postdata['contact_subject']);
		$post_id = intval($postdata['post_id']);
		$post = get_post($post_id);
		$unique_id = (empty($postdata['form_id'])) ? '' : sanitize_text_field($postdata['form_id']);
		$is_order = empty($postdata['is_order']) ? false : sanitize_text_field($postdata['is_order']);
		$gateway = (empty($postdata['gateway'])) ? '' : sanitize_text_field($postdata['gateway']);
		$email_to = $rsvp_options['rsvp_to'];
		$prefix = 'Contact';
		$attributes = [];
		if($unique_id) {
			$regex = '/{[^}]+'.$unique_id.'[^}]+}/';
			preg_match($regex,$post->post_content,$match);
			if($match) {
				$attributes = (array) json_decode($match[0]);
				if(!empty($attributes['email']))
					$email_to = sanitize_text_field($attributes['email']);
				if(!empty($attributes['subject_prefix']))
					$prefix = sanitize_text_field($attributes['subject_prefix']);
			}
		}
		foreach ( $postdata['profile'] as $name => $value ) {
			$rsvp[ $name ] = sanitize_text_field( $value );
		}
		$note = (empty($postdata['note'])) ? '' : sanitize_textarea_field($postdata['note']);
		if(!$note && !$is_order)
			return new WP_REST_Response(array('no_note'=>1), 200 );
		if ( ! is_admin() && ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) ) {
			if ( ! rsvpmaker_recaptcha_check( $rsvp_options['rsvp_recaptcha_site_key'], $rsvp_options['rsvp_recaptcha_secret'] ) ) {
				return new WP_REST_Response(array('error'=>'Failed security check'), 200 );
			}
		}
		if ( isset( $postdata['required'] ) || empty( $rsvp['email'] ) ) {
			if(isset( $postdata['required'] ))
			$required = explode( ',', $postdata['required'] );
			else
			$required = array();

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
				return new WP_REST_Response(array('error'=>'missing fields: '.$missing), 200 );
			}
		}
		if ( ! isset( $rsvp['first'] ) ) {
			$rsvp['first'] = '';
		}

		if ( ! isset( $rsvp['last'] ) ) {
			$rsvp['last'] = '';
		}
		if ( !is_email($rsvp['email']) ) {
			return new WP_REST_Response(array('error'=>'not a valid email: '.$rsvp['email']), 200 );
		}
		$nv = array('first'=>$rsvp['first'], 'last'=>$rsvp['last'], 'email'=>$rsvp['email'], 'event'=>0, 'note' => $note, 'details'=>serialize( $rsvp ),'user_id'=>$current_user->ID,'note'=>$note);
		$wpdb->insert($wpdb->prefix.'rsvpmaker',$nv);
		$id = $wpdb->insert_id;
		$sitename = get_option('sitename');
		rsvpmaker_capture_email( $rsvp );
		$mail['html'] = '<p>Contact form submission from '.$sitename.'</p>';
		foreach($rsvp as $key => $item) {
			if($is_order && (strpos($key,$is_order) !== false) && (strpos($item,':') !== false)) {
				$purchase = explode(':',$item);
				$purchase[1] = trim($purchase[1]);
				$purchase[2] = $email_to;
				$purchase_code = 'rsvp_purchase_'.time();
				set_transient($purchase_code,$purchase,HOUR_IN_SECONDS);
			}
			$label = ucfirst(str_replace('_',' ',$key));		
			$mail['html'] .= sprintf("\n<p><label>%s:</label> %s</p>",$label,sanitize_text_field($item));
		}
		if($post_id && !empty($purchase_code)) {
			$purchase_link = add_query_arg(array('purchase_code'=>$purchase_code,'rsvp_id'=>$id,'gateway'=>$gateway),get_permalink($post_id));
			return new WP_REST_Response(array('sending'=>$id,'purchase_link'=>$purchase_link,'gateway'=>$gateway), 200 );
		}

		$mail['html'] .= '<p>'.nl2br($note).'</p>';
		$mail['from'] = $rsvp['email'];
		$mail['fromname'] = $rsvp['first'].' '.$rsvp['last'].' (Contact Form)';
		$mail['subject'] = '['.$prefix.'] '.$rsvp['subject'].' ('.$_SERVER['SERVER_NAME'].')';
		$recipients = explode(",",$email_to);
		foreach($recipients as $recipient) {
			$mail['to'] = trim($recipient);
			rsvpmailer($mail);
		}
		$response = $postdata;
	return new WP_REST_Response(array('sending'=>$id,'mail'=>$mail,'attributes'=>$attributes,'unique_id'=>$unique_id), 200 );
	}//end handle
}//end class

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
		$excerpt = new RSVP_Editor_Loop_Excerpt();
		$excerpt->register_routes();
		$calendar = new RSVP_Calendar();
		$calendar->register_routes();
		$preview = new RSVP_Preview();
		$preview->register_routes();
		$dateblock = new RSVP_Date_Block();
		$dateblock->register_routes();
		$date_element = new RSVP_Date_Element();
		$date_element->register_routes();
		$titledate = new RSVP_Title_Date();
		$titledate->register_routes();
		$report = new RSVP_Report_API();
		$report->register_routes();
		$pppaid = new RSVP_PayPalPaid();
		$pppaid->register_routes();
		$pphook = new RSVP_PayPalWebHook();
		$pphook->register_routes();
		$copy = new RSVP_CopyDefaults();
		$copy->register_routes();
		$contact = new RSVP_Contact_Form();
		$contact->register_routes();
	}
);
