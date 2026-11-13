# Static JavaScript Files for RSVPMaker

This directory contains static JavaScript files that handle client-side functionality for RSVPMaker. These files are intentionally kept separate from PHP to avoid WordPress content filtering issues that would break embedded JavaScript code.

## Why Static Files?

WordPress applies security filters (like `wp_kses_post`) to post content as it's returned from shortcodes and hooks. These filters convert JavaScript operators like `&&` to `&#038;&#038;`, which breaks JavaScript syntax. By using static JS files loaded via `wp_enqueue_script()`, we bypass all content filtering while maintaining clean separation of concerns.

## Files

### paypal-button.js
Handles PayPal payment button initialization and processing.

**Configuration attributes:**
- `data-paypal-button`: Marker attribute (value can be empty or "true")
- `data-paypal-client-id`: PayPal Client ID
- `data-paypal-currency`: Currency code (e.g., "USD")
- `data-paypal-amount`: Payment amount
- `data-paypal-description`: Payment description
- `data-paypal-custom-id`: Custom transaction identifier
- `data-paypal-verify-url`: REST endpoint for payment verification
- `data-paypal-rsvp-tx`: RSVP transaction token
- `data-paypal-user-id`: WordPress user ID

**Features:**
- Initializes PayPal SDK dynamically
- Polls for SDK availability before rendering button
- Implements retry logic with exponential backoff
- Handles payment capture and verification
- Displays success/error messages
- Supports gift certificates and receipts
- 30-minute session timeout

**Used by:** `rsvpmaker_paypal_button()` in paypal-rest.php

---

### stripe-checkout.js
Handles Stripe payment form initialization and card processing.

**Configuration attributes:**
- `data-stripe-checkout`: Marker attribute (value can be empty or "true")
- `data-stripe-public-key`: Stripe publishable key
- `data-stripe-client-secret`: PaymentIntent client secret
- `data-stripe-success-url`: REST endpoint for payment recording
- `data-stripe-form-id`: ID of the form element
- `data-stripe-card-element`: ID of the card element container
- `data-stripe-result-element`: ID of the result message element
- `data-stripe-button-id`: ID of the submit button
- `data-stripe-name-input`: ID of the name input field
- `data-stripe-email-input`: ID of the email input field

**Features:**
- Creates Stripe elements for card input
- Real-time card validation
- Payment intent confirmation
- Form submission handling via Fetch API
- Error and success messaging
- Hides form after successful payment

**Used by:** `rsvpmaker_stripe_checkout()` in rsvpmaker-stripe.php

---

### signup-form.js
Handles mailing list signup form submission and response handling.

**Configuration attributes:**
- `data-signup-form`: Form ID (used as identifier)
- `data-signup-url`: REST endpoint for form submission
- `data-signup-message`: ID of the message element for feedback

**Features:**
- Form submission via Fetch API
- Support for both JSON and text responses
- Success/error message display with styling
- Form reset on success
- Network error handling
- XSS protection via HTML escaping

**Used by:** `rsvpmail_signup_form()` in rsvpmaker-email.php

---

### modal-popup.js
Handles modal popup display, timing, and user interactions.

**Configuration attributes:**
- `data-rsvp-modal`: Marker attribute (value can be empty or "true")
- `data-rsvp-modal-delay`: Delay in milliseconds before showing modal (default: 5000)
- `data-rsvp-modal-cookie`: Cookie name for tracking if user has seen modal (default: "rsvpmail")
- `data-rsvp-modal-close-selector`: CSS selector for close button (default: ".close")

**Features:**
- Displays modal after configurable delay
- Cookie-based tracking to show modal only once per user
- Close button handling
- Click-outside-modal to close
- Respects user preferences (won't show if cookie exists)

**Used by:** `rsvpmail_list_rsvpmodal()` in rsvpmaker-email.php

---

## Architecture Pattern

All static files follow the same pattern:

1. **Initialization function** runs on DOM load (checks `document.readyState`)
2. **Query DOM** for elements with target data attributes
3. **Validate configuration** before proceeding
4. **Initialize each instance** with proper error handling
5. **Attach event listeners** for user interactions

Example:
```javascript
function initializeFeature() {
  const elements = document.querySelectorAll('[data-feature-marker]');
  elements.forEach(el => {
    const config = {
      option1: el.dataset.featureOption1,
      option2: el.dataset.featureOption2
    };
    initializeElement(el, config);
  });
}

// Run on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeFeature);
} else {
  initializeFeature();
}
```

This pattern ensures:
- No global namespace pollution
- Graceful handling of missing elements
- Support for multiple instances on one page
- Proper error reporting to console

## Integration Points

### From PHP
1. Call `wp_enqueue_script()` with the script handle and correct path
2. Output clean HTML with configuration in `data-*` attributes
3. Include required form fields or DOM elements
4. Use `esc_attr()` for all attribute values and `esc_url()` for URLs

Example:
```php
wp_enqueue_script('rsvpmaker-paypal-button', 
  plugins_url('assets/paypal-button.js', __FILE__), 
  array(), '1.0.0', true);

echo '<div data-paypal-button="true" 
           data-paypal-client-id="' . esc_attr($client_id) . '"
           data-paypal-amount="' . esc_attr($amount) . '">
      </div>';
```

### REST Endpoints
These files make HTTP requests to REST endpoints that should:
- Accept POST requests
- Return JSON responses
- Include appropriate nonce verification
- Handle validation and error cases
- Return success/failure status

## Testing

When testing these files:

1. **Check browser console** for any errors
2. **Verify elements exist** before functionality is needed
3. **Test with missing SDK libraries** (e.g., Stripe not loaded)
4. **Test form validation** (empty fields, invalid input)
5. **Test error handling** (network errors, API failures)
6. **Check for XSS vulnerabilities** in dynamic content
7. **Verify cookies work correctly** (modal should only show once)

## Debugging

- Open browser DevTools (F12)
- Check Console for errors and log messages
- Check Network tab to verify API calls
- Check Application > Cookies to verify cookie handling
- Use `console.log()` statements in JavaScript files
- Check WordPress error logs for PHP errors

## Future Enhancements

Potential improvements:
- Add loading state indicators
- Implement progressive enhancement for JavaScript-disabled browsers
- Add analytics tracking
- Improve accessibility (ARIA labels, keyboard navigation)
- Add internationalization support for messages
- Create TypeScript versions for type safety
