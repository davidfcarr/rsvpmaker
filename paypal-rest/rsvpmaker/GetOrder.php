<?php

//require_once '../vendor/autoload.php';
require_once 'PayPalClient.php';

use PayPalCheckoutSdk\Orders\OrdersGetRequest;
//use RSVPMaker\CaptureIntentExamples\CreateOrder;

class RSVPMakerGetOrder
{

    /**
     * This function can be used to retrieve an order by passing order Id as argument.
     */
    public static function getOrder($orderId)
    {   
        global $current_user;
        $client = RSVPMakerPayPalClient::client();
        $response = $client->execute(new OrdersGetRequest($orderId));
        if($response)
        {
            $rsvp_id = (empty($response->result->purchase_units[0]->custom_id)) ? 0 : intval($response->result->purchase_units[0]->custom_id);
            $atts['invoice_id'] = (empty($response->result->purchase_units[0]->invoice_id)) ? 0 : sanitize_text_field($response->result->purchase_units[0]->invoice_id);
            $event = (empty($_GET['event'])) ? 0 : intval($_GET['event']);
            if(isset($_GET['tracking_key']) && isset($_GET['tracking_value']))
            {
                $atts['tracking_key'] = sanitize_text_field($_GET['tracking_key']);
                $atts['tracking_value'] = sanitize_text_field($_GET['tracking_value']);
            }
            if($rsvp_id)
            {
                $atts['tracking_key'] = 'rsvp';
                $atts['tracking_value'] = $rsvp_id;
            }
            if(isset($current_user->ID)) {
                $atts['user_id'] = $current_user->ID;
                $user = get_userdata($current_user->ID);             
                if(!empty($user->first_name) && (!empty($user->last_name)))
                    $atts['name'] = $user->first_name.' '.$user->last_name;
                elseif(isset($user->display_name))
                    $atts['name'] = $user->display_name;
            }
            $atts['description'] = $response->result->purchase_units[0]->description;
            $atts['amount'] = $response->result->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->gross_amount->value;
            $atts['fee'] = $response->result->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value;
            //$net = $response->result->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->net_amount->value;
            $atts['email'] = $response->result->payer->email_address;
            $atts['transaction_id'] = $response->result->id;
            $atts['status'] = 'PayPal';
            rsvpmaker_custom_payment('PayPal REST api',$response->result->purchase_units[0]->amount->value,$rsvp_id,$event, $response->result->id);
            //$payment_message_id = get_post_meta($event,'payment_confirmation_message',true);
        if($response->statusCode == 200)
            {
                if($rsvp_id) {
                    rsvp_confirmation_after_payment( $rsvp_id );
                }
                $atts['paypal_response'] = $response;
                rsvpmaker_money_tx($atts);
                rsvpmaker_debug_log($atts,'rsvpmaker paypal confirmed');
                do_action('rsvpmaker_paypal_verification_response',$atts);
            }
        //$response->result->payment_confirmation_message = $payment_message_id;//(empty($payment_message_post) || eseller_receivable_breakdownmpty($payment_message_post->post_content)) ? '' : do_blocks($payment_message_post->post_content);
        echo json_encode($response); // also log this?
        //wp_schedule_single_event( time() + 30, 'rsvpmaker_after_payment',array('paypal',$atts['amount'],$atts['description']));
        }
    }
}

/*
sample response
BraintreeHttp\HttpResponse::__set_state(array(
   'statusCode' => 200,
   'result' => 
  (object) array(
     'id' => '00805278L17884104',
     'intent' => 'CAPTURE',
     'status' => 'COMPLETED',
     'payment_source' => 
    (object) array(
       'paypal' => 
      (object) array(
         'email_address' => 'david-buyer@carrcommunications.com',
         'account_id' => 'NJ8X44JC2U7W8',
         'name' => 
        (object) array(
           'given_name' => 'test',
           'surname' => 'buyer',
        ),
         'address' => 
        (object) array(
           'country_code' => 'US',
        ),
      ),
    ),
     'purchase_units' => 
    array (
      0 => 
      (object) array(
         'reference_id' => 'default',
         'amount' => 
        (object) array(
           'currency_code' => 'USD',
           'value' => '50.00',
        ),
         'payee' => 
        (object) array(
           'email_address' => 'davecarr-facilitator@carrcommunications.com',
           'merchant_id' => 'FXP8AT335JR9J',
        ),
         'description' => 'Confirmation After Payment',
         'custom_id' => '90513',
         'shipping' => 
        (object) array(
           'name' => 
          (object) array(
             'full_name' => 'test buyer',
          ),
           'address' => 
          (object) array(
             'address_line_1' => '1 Main St',
             'admin_area_2' => 'San Jose',
             'admin_area_1' => 'CA',
             'postal_code' => '95131',
             'country_code' => 'US',
          ),
        ),
         'payments' => 
        (object) array(
           'captures' => 
          array (
            0 => 
            (object) array(
               'id' => '1B358687PD9296944',
               'status' => 'COMPLETED',
               'amount' => 
              (object) array(
                 'currency_code' => 'USD',
                 'value' => '50.00',
              ),
               'final_capture' => true,
               'seller_protection' => 
              (object) array(
                 'status' => 'ELIGIBLE',
                 'dispute_categories' => 
                array (
                  0 => 'ITEM_NOT_RECEIVED',
                  1 => 'UNAUTHORIZED_TRANSACTION',
                ),
              ),
               'seller_receivable_breakdown' => 
              (object) array(
                 'gross_amount' => 
                (object) array(
                   'currency_code' => 'USD',
                   'value' => '50.00',
                ),
                 'paypal_fee' => 
                (object) array(
                   'currency_code' => 'USD',
                   'value' => '2.24',
                ),
                 'net_amount' => 
                (object) array(
                   'currency_code' => 'USD',
                   'value' => '47.76',
                ),
              ),
               'custom_id' => '90513',
               'links' => 
              array (
                0 => 
                (object) array(
                   'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/1B358687PD9296944',
                   'rel' => 'self',
                   'method' => 'GET',
                ),
                1 => 
                (object) array(
                   'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/1B358687PD9296944/refund',
                   'rel' => 'refund',
                   'method' => 'POST',
                ),
                2 => 
                (object) array(
                   'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/00805278L17884104',
                   'rel' => 'up',
                   'method' => 'GET',
                ),
              ),
               'create_time' => '2022-09-17T19:02:46Z',
               'update_time' => '2022-09-17T19:02:46Z',
            ),
          ),
        ),
      ),
    ),
     'payer' => 
    (object) array(
       'name' => 
      (object) array(
         'given_name' => 'test',
         'surname' => 'buyer',
      ),
       'email_address' => 'david-buyer@carrcommunications.com',
       'payer_id' => 'NJ8X44JC2U7W8',
       'address' => 
      (object) array(
         'country_code' => 'US',
      ),
    ),
     'create_time' => '2022-09-17T19:02:30Z',
     'update_time' => '2022-09-17T19:02:46Z',
     'links' => 
    array (
      0 => 
      (object) array(
         'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/00805278L17884104',
         'rel' => 'self',
         'method' => 'GET',
      ),
    ),
  ),
   'headers' => 
  array (
    '' => '',
    'Content-Type' => 'application/json',
    'Content-Length' => '1938',
    'Connection' => 'keep-alive',
    'Date' => 'Sat, 17 Sep 2022 19',
    'Application_id' => 'APP-80W284485P519543T',
    'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
    'Caller_acct_num' => 'FXP8AT335JR9J',
    'Paypal-Debug-Id' => 'acbd4831d05c0',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
  ),
))
*/
