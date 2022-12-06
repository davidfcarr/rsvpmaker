<?php

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class RSVPMakerPayPalClient
{
    /**
     * Returns PayPal HTTP client instance with environment which has access
     * credentials context. This can be used invoke PayPal API's provided the
     * credentials have the access to do so.
     */
    public static function client()
    {
        return new PayPalHttpClient(self::environment());
    }
    
    /**
     * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
     * For demo purpose, we are using SandboxEnvironment. In production this will be
     * LiveEnvironment.
     */
    public static function environment()
    {
        global $paypal_rest_keys;
        if($paypal_rest_keys['sandbox']) {
            $clientId = getenv("CLIENT_ID") ?: $paypal_rest_keys['sandbox_client_id'];
            $clientSecret = getenv("CLIENT_SECRET") ?: $paypal_rest_keys['sandbox_client_secret'];
            return new SandboxEnvironment($clientId, $clientSecret);    
        }
        else {
            $clientId = getenv("CLIENT_ID") ?: $paypal_rest_keys['client_id'];
            $clientSecret = getenv("CLIENT_SECRET") ?: $paypal_rest_keys['client_secret'];
            return new ProductionEnvironment($clientId, $clientSecret);    
        }
    }
}