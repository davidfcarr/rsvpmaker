<?php

namespace Test;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class TestHarness
{
    public static function client()
    {
        return new PayPalHttpClient(self::environment());
    }
    public static function environment()
    {
        $clientId = getenv("CLIENT_ID") ?: "<<PAYPAL-CLIENT-ID>>";
        $clientSecret = getenv("CLIENT_SECRET") ?: "<<PAYPAL-CLIENT-SECRET>>";
        return new SandboxEnvironment($clientId, $clientSecret);
    }
}
