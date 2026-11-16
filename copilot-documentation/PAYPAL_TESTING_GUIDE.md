# PayPal Payment Testing Guide

## Quick Start Tests

### Test 1: Verify PayPal Button Displays on RSVP Confirmation

**Steps**:
1. Go to WordPress admin → RSVP Maker Events
2. Open or create an event with a fee (e.g., $10)
3. Set Payment Gateway: "PayPal REST API"
4. Save event
5. Create RSVP as guest or logged-in user
6. Set quantities to trigger a balance due
7. Click "Submit RSVP"
8. On confirmation page, look for PayPal button

**Expected Result**: 
- PayPal button with PayPal SDK styling should appear
- NOT a generic "Pay Online" link
- Button should be interactive

**If Failed**:
- Check WordPress error log (`wp-content/debug.log`)
- Look for entries starting with "PayPal Button:"
- See "Troubleshooting" section below

---

### Test 2: Verify Standalone PayPal Payment Block

**Steps**:
1. Go to WordPress admin → Pages
2. Create new page or edit existing page
3. Add block → Search "PayPal"
4. Select "PayPal Payment" block
5. In block settings:
   - Set Amount: 1.00
   - Set Currency: USD
   - Set Description: "Test Payment"
6. Publish/Update page
7. View page on frontend

**Expected Result**:
- PayPal button should appear
- Should be clickable and interactive
- Should not vary based on page type

**If Failed on Some Pages**:
- Test on different page types (post, page, archive)
- Check if page has custom template
- See "Testing on Different Page Types" below

---

### Test 3: Complete Payment Flow

**Prerequisites**:
- Have a PayPal sandbox account set up
- RSVP Maker configured for sandbox mode
- $1.00 minimum test payment

**Steps**:
1. Create RSVP with fee
2. On confirmation page, see PayPal button
3. Click "Pay with PayPal"
4. Log in with PayPal sandbox account
5. Approve payment
6. Return to RSVP site
7. Verify confirmation message
8. Check RSVP record in admin

**Expected Result**:
- Payment approved in PayPal
- Return to confirmation page
- RSVP marked as "Paid"
- Admin shows payment recorded

**If Not Marked Paid**:
- Check error log for "PayPal Paid:" entries
- Check for retry attempts logged
- Wait a few minutes for webhook to process
- Refresh RSVP record page

---

## Diagnostic Tests

### Test 4: Check PayPal Configuration

**Steps**:
1. WordPress admin → RSVP Maker → Settings
2. Scroll to "PayPal" section
3. Verify:
   - "Enable PayPal REST API" is checked
   - Correct Client ID is filled in
   - Correct Secret is filled in
   - Sandbox toggle matches your credentials

**What to Check**:
- Client ID should start with "AY" (production) or "AZ" (sandbox)
- Should not be empty or placeholder text
- Secret should be a long string of characters
- Sandbox toggle should match whether you're using sandbox or live credentials

**If Not Configured**:
- Get credentials from PayPal app settings
- Copy-paste carefully (no extra spaces)
- Save settings
- Re-test

---

### Test 5: Check Error Logs

**Steps**:
1. Connect to your server via SFTP or file manager
2. Navigate to: `/wp-content/`
3. Look for `debug.log` file
4. Open and search for "PayPal"

**What to Look For**:
- `PayPal Button: PayPal keys not configured` → Check settings
- `PayPal Button: Sandbox mode enabled but sandbox_client_id is empty` → Configure sandbox ID
- `PayPal Button: Live mode but client_id is empty` → Configure live ID
- `PayPal Button: ob_get_clean() returned empty` → Server/output buffering issue
- `PayPal Paid: Order not found in PayPal` → PayPal verification failed
- `PayPal Paid: Amount mismatch` → Configured currency issue

**Action**:
- Each error has specific fix (see error message)
- Implement fix
- Clear log file
- Re-test

---

### Test 6: Verify Webhook Configuration

**Steps**:
1. Log in to PayPal Developer Dashboard
2. Go to your app settings
3. Look for "Webhooks" section
4. Verify URL is set to your site's API endpoint

**Expected Webhook URL Format**:
```
https://yoursite.com/wp-json/rsvpmaker/v1/paypal_webhook
```

**To Find Your Correct Webhook URL**:
1. In WordPress, go to WordPress admin
2. Paste in browser console: `wp.apiSettings.root + 'rsvpmaker/v1/paypal_webhook'`
3. Copy the URL
4. Add to PayPal app webhook settings

**If Not Configured**:
- Copy correct URL
- Add to PayPal app settings
- Select event: "payment.capture.completed"
- Save

**Testing Webhook**:
- PayPal provides "Send a test event" button
- Send test payment.capture.completed event
- Check WordPress error log for webhook processing

---

## Specific Scenarios

### Scenario: Button Shows on One Page But Not Another

**Diagnosis Steps**:
1. Note which pages show button, which don't
2. Check common differences:
   - Page type (post vs page vs archive)
   - Custom template used
   - Plugin conflicts
3. Check error log for differences

**Common Causes**:
- Archive page with no post context
  - **Fix**: Should now work after post-context fix
- Custom template not initializing post
  - **Fix**: Add event ID to block attributes
- Plugin conflict with output buffering
  - **Fix**: Disable plugins one by one to test

**Test Fix**:
1. Apply fix
2. Clear browser cache (Ctrl+Shift+Delete)
3. Reload page
4. Verify button appears

---

### Scenario: "Payment not marked as paid" Despite PayPal Confirmation

**Diagnosis Steps**:
1. Check error log for "PayPal Paid:" entries
2. Look for verification steps:
   - ✓ Order captured
   - ✓ Order found in PayPal
   - ✓ Amount matches
   - ✓ Currency matches
   - ✓ Not already paid
3. Check for retry attempts logged

**If No Verification Entries**:
- Client-side verification never ran
- Check for JavaScript errors in browser console
- Try payment again

**If Verification Entries Exist**:
- Check which step failed
- Implement specific fix for that step
- Document fix

**If Payment Marked as Paid Later**:
- Webhook processing worked
- This is expected behavior (confirms reliability improvements)
- Payment now correctly marked in system

---

### Scenario: Transient Recovery Needed

**Diagnosis**:
- Error log shows "rsvpmaker_paypal_recover_from_database"
- Means 15+ minute gap occurred
- Recovery function activated

**What This Means**:
- System automatically recovered from cache expiration
- No user intervention needed
- One of the reliability improvements working as designed

**Testing Transient Recovery** (advanced):
1. Complete payment successfully
2. Manually clear transient from database:
   ```sql
   DELETE FROM wp_options 
   WHERE option_name LIKE '%rsvpmaker_paypal_payment_%'
   ```
3. Before 15 minutes: Update another RSVP with same event
4. Verify recovery function is logged
5. Verify payment still works

---

## Browser Console Testing

### Test 7: Verify JavaScript is Working

**Steps**:
1. Open RSVP confirmation page
2. Open browser Developer Tools (F12)
3. Go to Console tab
4. Look for any red error messages
5. Look for messages starting with "PayPal:"

**Expected**: No red errors related to PayPal

**If Errors Appear**:
- Screenshot the error
- Note exact error message
- Disable other plugins to test
- Contact support with screenshot

---

### Test 8: Monitor Payment Flow

**Steps**:
1. Open RSVP confirmation page
2. Open browser Developer Tools (Console tab)
3. Look for messages during payment:
   - User approves in PayPal popup
   - Return to site
   - Message: "Recording transaction by [PayerName]..."
   - Message: "Payment recorded successfully" or error

**If Stuck at "Recording transaction..."**:
- Payment was approved
- Verification taking time or failing
- Wait 30 seconds for retry logic
- Check console for error messages

---

## Network Testing

### Test 9: Monitor API Requests

**Steps**:
1. Open RSVP confirmation page
2. Open browser Developer Tools → Network tab
3. Complete PayPal payment
4. Look for POST request to `/wp-json/rsvpmaker/v1/paypal_paid`
5. Click on request → Response tab
6. Should see JSON success response

**Expected Response**:
```json
{
  "success": true,
  "rsvp_id": 123,
  "message": "Payment recorded"
}
```

**If Error Response**:
- Note error message
- Screenshot it
- Check error log for corresponding entry
- Implement suggested fix

---

## Comparison Testing

### Test 10: Compare With Previous Behavior

**Before Recent Changes**:
- Only single verification attempt
- No retry logic
- Only marked paid on initial success
- Webhook could not catch missed payments

**After Recent Changes**:
- 3 retry attempts with delays
- Webhook backup confirmation
- Transient recovery mechanism
- Better error logging

**To Verify Improvements**:
1. Simulate network issue (browser DevTools → Network tab → set to "Slow 3G")
2. Try to complete payment
3. Verify retry logic attempts payment multiple times
4. Note improved success rate

---

## Troubleshooting Decision Tree

```
Button not showing?
├─ Check error log
│  ├─ "PayPal keys not configured" → Configure credentials
│  ├─ "Sandbox mode but sandbox_client_id empty" → Add sandbox ID
│  ├─ "Live mode but client_id empty" → Add live ID
│  └─ "ob_get_clean() returned empty" → Server issue, check PHP errors
├─ On archive/template page?
│  └─ Should work now, test after clearing cache
└─ Still failing? → See next section

Payment not marked paid despite PayPal approval?
├─ Check error log for "PayPal Paid:" entries
├─ If no entries → Verification never ran (check JS console)
├─ If entries → Which step failed?
│  ├─ "Order not found in PayPal" → PayPal system issue
│  ├─ "Amount mismatch" → Currency/amount issue
│  └─ Other error → See log entry for specific fix
└─ Wait 5 minutes for webhook → Often resolves automatically

General issues?
├─ Clear browser cache (Ctrl+Shift+Delete)
├─ Check multiple browsers
├─ Test on different event/page
├─ Check if isolated to one site/user
└─ If still broken → Escalate to support with error logs
```

---

## Support Information to Gather

If you need to contact support about PayPal issues, have this ready:

1. **Error Log Entries**
   - All entries from past hour containing "PayPal"
   - Any relevant PHP errors

2. **Configuration**
   - Sandbox or Live mode?
   - Which Client ID configured?
   - Which page types affected?

3. **Reproduction Steps**
   - Exact steps to reproduce issue
   - Which events, pages are affected
   - Consistent or intermittent?

4. **Screenshots**
   - PayPal settings page
   - Error message or missing button
   - Browser console errors (if any)

5. **PayPal Confirmation**
   - Order ID from PayPal
   - Timestamp of payment
   - Payment amount and status in PayPal

## Additional Resources

- **Technical Architecture**: See `PAYPAL_IMPROVEMENTS.md`
- **Diagnostic Guide**: See `PAYPAL_BUTTON_DIAGNOSTICS.md`
- **Summary of Changes**: See `PAYPAL_IMPROVEMENTS_SUMMARY.md`
- **PayPal Webhook Docs**: https://developer.paypal.com/api/webhooks/
- **PayPal REST API Docs**: https://developer.paypal.com/docs/api/rest/
