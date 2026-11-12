# PayPal Button Display Diagnostics

## Issue
PayPal button is not displaying on RSVP confirmations and standalone PayPal Payment blocks. Instead, a generic "Pay Online" button appears as fallback.

## Diagnostic Changes
Enhanced error logging has been added to the `rsvpmaker_paypal_button()` function to identify which validation is failing.

### What to Look For in Error Logs
Check your WordPress error log (usually `wp-content/debug.log`) for messages starting with `PayPal Button:`.

#### Possible Error Messages:

1. **"PayPal keys not configured"**
   - Indicates: PayPal REST API credentials are not set in WordPress admin
   - Action: Go to RSVP Maker settings and configure PayPal API keys (Client ID and Secret)

2. **"Global $post is not available or invalid"**
   - Indicates: The function is being called in a context where the global `$post` object is not available
   - Action: This might happen on certain page types; may need to pass event ID explicitly

3. **"Sandbox mode enabled but sandbox_client_id is empty"**
   - Indicates: Plugin is set to sandbox mode but Sandbox Client ID is not configured
   - Action: Configure Sandbox Client ID in RSVP Maker settings, or switch to Live mode if you have live keys

4. **"Live mode but client_id is empty"**
   - Indicates: Plugin is set to live mode but Live Client ID is not configured
   - Action: Configure Live Client ID in RSVP Maker settings, or switch to Sandbox mode for testing

5. **"Failed to set transient for transaction code"**
   - Indicates: WordPress transient storage failed (temporary data cache)
   - Action: Check WordPress transient handling; may indicate database issues

6. **"ob_get_clean() returned empty/false"**
   - Indicates: PHP output buffering returned empty HTML
   - Action: Check for PHP errors during script generation; check PHP error logs for more details

## Testing Steps

### 1. Check PayPal Settings
- Go to WP Admin → RSVP Maker → Settings
- Look for "PayPal" section
- Verify:
  - "Enable PayPal REST API" is checked
  - Client ID is filled in (for sandbox or live mode)
  - Secret is filled in
  - Sandbox/Live toggle matches your configuration

### 2. Create Test Event
- Create a new event with a fee
- Set payment gateway to "PayPal REST API"
- Create an RSVP and go to confirmation page
- Check if PayPal button appears

### 3. Standalone PayPal Block
- Create a new page
- Add "PayPal Payment" block
- Configure with:
  - Amount: 1.00
  - Currency: USD
- Check if button appears

### 4. Check WordPress Error Log
```
wp-content/debug.log
```

Look for entries with `PayPal Button:` prefix. They should tell you exactly which validation failed.

## Specific Issues and Solutions

### Issue: Button shows on one page but not another
- Check if both pages have posts/custom post types in proper context
- Check if PayPal settings differ between pages
- The standalone block works independently of post context, so if it fails everywhere, it's a settings issue
- If it fails on some pages but not others, it may be a context/post availability issue

### Issue: "client ID not set" appears as text
This might appear instead of the button if:
- You're viewing in edit mode (Gutenberg editor)
- PayPal keys are truly not configured
- Check that the text "client ID not set" or "amount not set" appears as error message

### Issue: Nothing appears (no button, no error text)
- Function is returning empty string
- Check error logs for `PayPal Button:` messages
- One of the validation checks is failing silently

## Debug Mode
To add more detailed logging, you can temporarily modify line numbers in `paypal-rest.php`:

### After line 401 (before ob_start), add:
```php
error_log('PayPal Button Debug: about to render button. vars=' . json_encode($vars));
error_log('PayPal Button Debug: paypal_client_id=' . $paypal_client_id);
error_log('PayPal Button Debug: currency_code=' . $currency_code);
error_log('PayPal Button Debug: amount=' . $amount);
```

This will show all input values to the button rendering.

## Common Configuration Issues

### Sandbox vs Live Mode Mismatch
- If sandbox_client_id is set but sandbox mode is OFF, the live client_id will be used
- If you have sandbox configured but sandbox_client_id is empty, error occurs
- Solution: Make sure enabled mode has corresponding non-empty credentials

### Missing Currency Settings
- If currency code is not set properly, it might cause issues
- Check `_rsvp_currency` post meta on event posts
- Check default currency in RSVP settings

### Output Buffering Issues
- If other plugins use output buffering, it might interfere
- If PHP errors occur during script generation, ob_get_clean() may return empty
- Last resort: Check raw PHP error log for fatal errors

## PayPal Button Block Attributes
The standalone PayPal Payment block accepts these attributes:
- `amount`: Payment amount (required, must be numeric)
- `currencyCode`: Currency code (optional, defaults to USD)
- `description`: Payment description (optional)
- `showdescription`: Show price/currency to user (optional, 'yes' to enable)

If you see "amount not set" error, make sure the block is configured with an amount.

## Next Steps if Diagnostics Don't Help
1. Take a screenshot of the error log showing the `PayPal Button:` error messages
2. Note which page/context the error occurs in (RSVP confirmation, standalone block, etc.)
3. Provide:
   - The exact error message from the log
   - Screenshot of PayPal settings in WordPress admin
   - Whether sandbox or live mode is enabled
   - Currency code being used
