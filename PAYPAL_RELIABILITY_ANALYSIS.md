# PayPal Payment Reliability Issues & Solutions

## Problems Identified

### 1. **Race Condition in Payment Processing**
**Issue:** The payment verification endpoint (`rsvpmaker_paypal_verify_rest`) relies on transients that are stored at order creation time but can be lost or expire before capture completes.

**File:** `paypal-rest.php` lines 47-85

**Problem Code:**
```php
$saved = get_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);
if(empty($saved))
  return array('status' => 'Pending transaction not found');
delete_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);//one time use
```

**Risks:**
- Transients can expire (default 24 hours, but configurable)
- Server restarts can clear transients if using non-persistent storage
- Browser cache clearing or crashes prevent order completion
- Multiple payment attempts cause immediate failures on second try

---

### 2. **Conditional Receipt Email Send (No Error Handling)**
**Issue:** The receipt email only sends if `$rsvp_id` exists and is not in a multi-event scenario, but there's no verification it actually sent.

**File:** `paypal-rest.php` lines 144-147

**Problem Code:**
```php
if(!$already_processed) {
  do_action('rsvpmaker_paypal_confirmation_tracking',$data,$saved);
  // ... multi-event handling ...
  if($rsvp_id) {
    rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
  }
}
```

**Risks:**
- If `$rsvp_id` is 0 or empty, no receipt is sent at all
- No fallback if the email function fails silently
- No logging of email send attempts or failures
- Multi-event registrations have custom email logic that bypasses standard receipt

---

### 3. **RSVP Status Not Updated for Paid Registration**
**Issue:** The database update only changes `amountpaid`, `owed`, and `details`, but never updates a "paid" status field in the main registration record.

**File:** `paypal-rest.php` lines 92-107

**Problem Code:**
```php
if(!$already_processed) {
  $updatesql = $wpdb->prepare("UPDATE %i SET amountpaid=%s, owed=%s, details=%s WHERE id=%d",$rsvptable,$paid,$owed,maybe_serialize($details),$rsvp_id);
  $wpdb->query($updatesql);
}
```

**Risks:**
- RSVP Report queries may not correctly identify paid registrations
- Report might rely on `owed` field (which could have calculation errors with partial payments)
- No explicit status field to query when a registration is fully paid
- Difficult to filter/sort paid vs unpaid in reports

---

### 4. **No Payment Idempotency Key or Request Deduplication**
**Issue:** Uses PayPal transaction ID to detect duplicates, but this check happens at database level without atomic transaction protection.

**File:** `paypal-rest.php` lines 85-86

**Problem Code:**
```php
if(!empty($details['paypal_transaction_id']) && $details['paypal_transaction_id'] === $data->id) {
  $already_processed = true;
```

**Risks:**
- Two simultaneous requests with same transaction ID could both pass the check before either updates the database
- Webhook and direct fetch could both process the same payment
- No distributed lock mechanism
- Payment could be recorded twice in money transaction table

---

### 5. **No Retry Logic for Transient Failures**
**Issue:** The endpoint doesn't handle temporary network issues, timeouts, or WordPress database connection problems gracefully.

**File:** `paypal-rest.php` - entire function

**Risks:**
- If database is temporarily unavailable, payment records as failed
- No queue system for processing payments asynchronously
- PayPal receives error response but client doesn't know to retry
- User sees failure even though payment was actually captured

---

### 6. **Receipt Link Generation Issues**
**Issue:** Receipt code is stored as post meta per event, which fails for non-event payments and creates orphaned codes.

**File:** `paypal-rest.php` lines 158-169

**Problem Code:**
```php
if($rsvp_id) {
  $receipt_code = get_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,true);
  if(!$receipt_code) {
    $receipt_code = wp_generate_password(20,false,false);
    if($event_id)
      update_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
    elseif(!empty($saved['post_id']))
      update_post_meta($saved['post_id'],'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
  }
  if($event_id)
    $rsvp_receipt_link = add_query_arg(...,get_permalink($event_id));
```

**Risks:**
- Post meta queries are slow and can fail silently
- No receipt link if both `$event_id` and `$saved['post_id']` are empty
- Receipt link not included in email response if generation fails
- Orphaned receipt codes accumulate with no cleanup

---

## Recommended Solutions

### Solution 1: Use Database-Backed Order State Instead of Transients
- Create a dedicated `rsvpmaker_paypal_orders` table to store order details
- Store: `order_id`, `rsvp_id`, `event_id`, `amount`, `currency`, `created_at`, `expires_at`, `status`, `payer_email`
- Check database instead of transients on capture
- Update status to 'captured', 'processed', 'failed' through transaction
- Automatically cleanup expired orders via scheduled action

**File to modify:** `paypal-rest.php` and potentially `rsvpmaker-money.php`

---

### Solution 2: Ensure Receipt Email Delivery with Fallback
- **Always** send receipt email after successful payment update
- Add email log/confirmation step
- If multi-event, still send receipt (just with combined event details)
- Add error handling and retry logic
- Log all email attempts (success/failure) for debugging

**File to modify:** `rsvpmaker-util.php` (rsvpmaker_confirm_payment function)

---

### Solution 3: Add Explicit "Paid" Status Field
- Add a `paid_status` column to `wp_rsvpmaker` table (values: 'unpaid', 'partial', 'paid')
- Update this status whenever payment is recorded
- Use this field in RSVP Report queries
- Simplify report filtering logic

**Files to modify:** 
- Database upgrade/migration
- `paypal-rest.php` - update status on payment
- `rsvpmaker-report.php` - query using new field

---

### Solution 4: Implement Idempotent Payment Processing
- Use database transactions (if supported) to atomically check + update
- Add unique constraint on `paypal_transaction_id` in `details` serialized field (or separate column)
- Implement request ID tracking from initial order creation through capture
- Use the `rsvp_tx` code as idempotency key

**File to modify:** `paypal-rest.php` - wrap verification in transaction, add idempotency checks

---

### Solution 5: Add Async Payment Processing with Retry Queue
- Instead of direct update, insert payment intent into queue table
- Process asynchronously with background job (WP-Cron or third-party)
- Implement exponential backoff retry (3-5 attempts)
- Log all attempts for admin debugging
- Send user confirmation immediately, receipt after successful processing

**Files to modify:**
- Create new file: `paypal-payment-queue.php`
- Modify: `paypal-rest.php` to queue instead of direct process

---

### Solution 6: Move Receipt Link Generation to RSVP Record
- Store receipt code directly in RSVP record's `details` field (or new column)
- Generate code on first successful payment verification
- Include generated code in JSON response to JavaScript
- Display link immediately in confirmation without post meta lookup

**File to modify:** `paypal-rest.php` - simplify receipt code generation

---

## Implementation Priority

**High Priority (Block payments being missed):**
1. Solution 1: Database-backed order state
2. Solution 4: Idempotent payment processing
3. Solution 3: Add paid_status field

**Medium Priority (Improve reliability & debugging):**
4. Solution 5: Async payment queue
5. Solution 6: Receipt link generation

**Low Priority (UX & logging):**
6. Solution 2: Ensure receipt email delivery (complement to above)

---

## Testing Checklist

After implementing fixes:
- [ ] Simulate transient expiration - payment still processes
- [ ] Test duplicate capture attempts - only recorded once
- [ ] Test multi-event registration - receipt sent and status updated
- [ ] Test non-event payments (gift certificates) - receipt works
- [ ] Test database connection failure during payment - graceful retry
- [ ] Test concurrent payments for same user
- [ ] Verify RSVP Report shows correct paid status
- [ ] Check error logs for payment failures
- [ ] Test receipt link from email - code is valid
- [ ] Test partial vs full payment scenarios

