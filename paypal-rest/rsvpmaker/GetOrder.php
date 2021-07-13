<?php

// require_once '../vendor/autoload.php';
require_once 'PayPalClient.php';

use PayPalCheckoutSdk\Orders\OrdersGetRequest;
// use RSVPMaker\CaptureIntentExamples\CreateOrder;

class RSVPMakerGetOrder {


	/**
	 * This function can be used to retrieve an order by passing order Id as argument.
	 */
	public static function getOrder( $orderId ) {
		global $post, $current_user;
		$client   = RSVPMakerPayPalClient::client();
		$response = $client->execute( new OrdersGetRequest( $orderId ) );
		{
		if ( ! empty( $_GET['rsvp'] ) ) {
			$rsvp_id = (int) $_GET['rsvp'];
			$event   = (int) $_GET['event'];
			rsvpmaker_custom_payment( 'PayPal REST api', $response->result->purchase_units[0]->amount->value, $rsvp_id, $event, $response->result->id );
			//$log = sprintf( '%s %s %s %s', $response->result->purchase_units[0]->amount->value, $rsvp_id, $event, $response->result->id );
			// rsvpmaker_debug_log($log,'PayPal test');
			$payment_message_id = get_post_meta( $event, 'payment_confirmation_message', true );
		}
		if(isset($_GET))
		{
			$kv = array_map('sanitize_text_field',$_GET);
			if(!isset($kv['user_id']) && isset($current_user->ID))
				$kv['user_id'] = $current_user->ID;
			if(!isset($kv['post_id']) && isset($post->ID))
				$kv['post_id'] = $post->ID;	
			do_action('paypal_verify_kv',$kv, $response->result->purchase_units[0]->amount->value, $response->result->id);
		}

		$response->result->payment_confirmation_message = $payment_message_id;// (empty($payment_message_post) || empty($payment_message_post->post_content)) ? '' : do_blocks($payment_message_post->post_content);
		echo json_encode( $response ); // also log this?
		}
	}
}

add_action('paypal_verify_kv','paypal_verify_kv_log',10,3);

function paypal_verify_kv_log($kv,$amount,$result_id) {
	$message = $amount.' '.$result_id.' '.var_export($kv,true);
	mail('david@carrcommunications.com','verify kv log',$message);
}