# PayPal Confirmation Message Fix

## Issue
When processing a single-event PayPal payment, users saw an inappropriate error message:
```
"COMPLETED payment of 1.00 USD from David Carr ERROR processing multi-event registration"
```

This error message appeared even though the registration was NOT multi-event.

## Root Cause
The code had flawed logic in the payment confirmation flow:

```php
if(isset($saved['rsvpmulti'])) {
  // Handle multi-event registration
  if($postdata && is_array($postdata)) {
    // Process multi-event...
  }
}
else {
  // ERROR: This executes for SINGLE-EVENT registrations too!
  $status .= " ERROR processing multi-event registration";
}
```

The `else` clause was incorrectly triggered for **all non-multi-event cases**, including the normal single-event registrations where `$saved['rsvpmulti']` is never set.

## Solution
Restructured the logic so that:

1. **Multi-event registrations** - Process if `$saved['rsvpmulti']` is set
2. **Single-event registrations** - Skip the entire multi-event block (no error)
3. **Multi-event error** - Only show error if multi-event WAS expected but data is missing

### Code Change

**Before (Incorrect):**
```php
if(isset($saved['rsvpmulti'])) {
  // Multi-event handling
  if($postdata && is_array($postdata)) {
    // Process events
  }
}
else {
  // BUG: Triggers for single-event registrations
  $status .= " ERROR processing multi-event registration";
}
```

**After (Correct):**
```php
if(isset($saved['rsvpmulti'])) {
  // Multi-event registration expected
  $postdata = get_transient($saved['rsvpmulti']);
  $postdata['yesno'] = 1;
  if($postdata && is_array($postdata)) {
    // Process multi-event successfully
  }
  else {
    // Only error if multi-event was expected but data is missing
    error_log('PayPal: Multi-event data not found...');
    $status .= " ERROR processing multi-event registration";
  }
}
// Single-event registrations skip this entire block - no error shown

// Receipt handling continues normally for all registration types
if($rsvp_id) {
  $receipt_sent = rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
  // ...
}
```

## Impact

✅ **Single-event registrations** now show clean confirmation:
```
"COMPLETED payment of 1.00 USD from David Carr"
```

✅ **Multi-event registrations** still work correctly with appropriate messages

✅ **Actual multi-event errors** are now logged properly when data is missing

## File Modified
- `paypal-rest.php` (lines 145-195)

## Testing
1. Process a single-event PayPal payment
2. Verify confirmation shows only: `"COMPLETED payment of [amount] [currency] from [name]"`
3. No "ERROR processing multi-event" message should appear
4. Receipt email should arrive normally
5. Registration should show as paid in RSVP Report

## Verification

Check error logs for multi-event payments to confirm proper handling:
```bash
grep "Multi-event" /path/to/wp-content/debug.log
```

---

**Status:** ✅ Fixed  
**Date:** 2026-11-07  
**Backward Compatible:** Yes  
**Database Changes:** None  
**Plugin Deactivation Required:** No
