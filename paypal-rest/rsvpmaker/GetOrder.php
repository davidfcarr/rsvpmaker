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
        $client = RSVPMakerPayPalClient::client();
        $response = $client->execute(new OrdersGetRequest($orderId));
        {
            if(!empty($_GET['rsvp'])) {
                $rsvp_id = $_GET['rsvp'];
                $event = $_GET['event'];
                rsvpmaker_custom_payment('PayPal REST api',$response->result->purchase_units[0]->amount->value,$rsvp_id,$event, $response->result->id);
            $log = sprintf('%s %s %s %s',$response->result->purchase_units[0]->amount->value,$rsvp_id,$event, $response->result->id);
            rsvpmaker_debug_log($log,'PayPal test');
            }
        //print_r($_GET);
        echo json_encode($response); // also log this?
        }
    }
}
