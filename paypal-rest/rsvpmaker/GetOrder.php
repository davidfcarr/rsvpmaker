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
        {
            $rsvp_id = (empty($_GET['rsvp'])) ? 0 : intval($_GET['rsvp']);
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
            rsvpmaker_money_tx($atts);
            rsvpmaker_custom_payment('PayPal REST api',$response->result->purchase_units[0]->amount->value,$rsvp_id,$event, $response->result->id);
            $payment_message_id = get_post_meta($event,'payment_confirmation_message',true);
        $response->result->payment_confirmation_message = $payment_message_id;//(empty($payment_message_post) || eseller_receivable_breakdownmpty($payment_message_post->post_content)) ? '' : do_blocks($payment_message_post->post_content);
        echo json_encode($response); // also log this?
        wp_schedule_single_event( time() + 30, 'rsvpmaker_after_payment',array('paypal'));
        }
    }
}
