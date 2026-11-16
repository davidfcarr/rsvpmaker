# PayPal Button Display Troubleshooting

## Issue
PayPal payment UI is not displayed consistently after making a reservation:
- Shows up on one page
- Not showing up on another page
- Standalone PayPal Payment block displays inconsistently

## Diagnostic Steps

### 1. Check if Button is Suppressed
The PayPal button will NOT display if any of these conditions are true:

- **No balance due**: `$charge == 0` or balance paid in full
- **Gateway not configured**: Payment gateway is not set for the event
- **PayPal keys missing**: Client ID or Sandbox Client ID not configured in settings
- **Wrong gateway**: Gateway is set to "Stripe" or "Cash or Custom" instead of "PayPal REST API" or "Both Stripe and PayPal"

### 2. Check Event Configuration
For each event where the button is NOT showing:

```
Event Edit → Event Settings → Payment
```

Verify:
- [ ] "Payment processor" is set to "PayPal REST API" OR "Both Stripe and PayPal"
- [ ] Event has a fee amount set (charge > 0)
- [ ] PayPal is enabled in plugin settings

### 3. Check Plugin Configuration
```
WordPress Admin → Settings → RSVPMaker → Payment
```

Verify:
- [ ] PayPal Client ID is set (for either Live or Sandbox)
- [ ] If Sandbox, "Use PayPal Sandbox" is checked

### 4. Browser Console Debugging
When on a page where the button SHOULD show but doesn't:

1. Open Developer Tools: Press `F12` (Windows/Linux) or `Cmd+Option+I` (Mac)
2. Click "Console" tab
3. Look for any red error messages
4. Common issues:
   - "Uncaught ReferenceError: paypal is not defined"
   - "Failed to load PayPal SDK"
   - "Refused to frame because..."

### 5. Check Page Source
Right-click on page → "View Page Source" and search for:

#### Should find these:
```html
<div id="paypal-button-container"></div>
<script src="https://www.paypal.com/sdk/js?client-id=..."></script>
<script>
  var purchase = {
    custom_id: ...
```

#### If NOT found:
- `rsvpmaker_paypal_button()` function returned nothing
- Likely causes:
  - PayPal keys not configured
  - `$post` object is null (shouldn't happen in normal context)
  - Function exited early due to validation

## Changes Made in This Update

I removed two debug `mail()` calls that were sending emails to `david@carrcommunications`:
- `mail('david@carrcommunications','paypal button vars',...)`
- `mail('david@carrcommunications','paypal button details',...)`

These were harmless for display but were unnecessary. Their removal should not affect button display.

### JavaScript Changes
Added automatic retry logic for failed payment verification (3 attempts with 1s, 2s, 4s delays). This is AFTER the button displays, so it shouldn't prevent the button from showing.

## How Button Display Works

```
1. User completes RSVP form
2. System checks if charge > 0
3. System gets payment gateway setting
4. If gateway == 'PayPal REST API' or 'Both Stripe and PayPal':
   5. rsvpmaker_paypal_button() is called
   6. Checks if PayPal keys are configured
   7. Outputs PayPal SDK script and button HTML
8. If gateway is something else:
   - Button is NOT displayed
```

## Testing Steps

### Test Case 1: Same Event, Different Pages
If button shows on one page but not another for the same event:

- Check if both pages use the same shortcode/block
- Check if both pages are showing the confirmation screen (where button appears)
- Check browser console for errors

### Test Case 2: Standalone PayPal Block
If using `[rsvpmaker_paypal_button_invoke]` shortcode:

```
[rsvpmaker_paypal_button_invoke amount="50.00" description="Test Payment"]
```

Verify:
- [ ] Both `amount` and `description` attributes are provided
- [ ] Amount is a positive number
- [ ] PayPal keys are configured

### Test Case 3: After Reservation
The button should appear in the "Registration Saved" confirmation section:

Check for:
- [ ] "To complete your registration, please pay now." message
- [ ] Payment amount and details
- [ ] PayPal button div and script

If confirmation appears but button doesn't, check PayPal configuration.

## Rollback if Needed

The changes made should not cause display issues. However, if you want to verify, the only modifications to `paypal-rest.php` display logic were:

1. **Removed**: Two debug email() calls (lines 383-387 in original)
2. **Added**: Client-side retry logic in JavaScript (runs AFTER button displays)

Both changes should have no impact on whether the button appears.

## Support Information

If button still doesn't display after checking above:

Provide:
1. Event ID where button is missing
2. Browser console output (screenshot)
3. Page source snippet showing payment section
4. Whether button works on other events
5. Payment gateway configuration for that event
