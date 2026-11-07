# PayPal Payment Processing - Quick Fixes

## Immediate Actions (Can implement today)

### Fix 1: Always Send Receipt Email
**File:** `rsvpmaker-util.php` - `rsvpmaker_confirm_payment()` function

**Current Issue:** Receipt only sends for single-event RSVP, not for multi-event or gift certificates

**Change needed:** Move receipt sending outside the `if($rsvp_id)` conditional and add error handling

```php
// BEFORE (current behavior - receipt may not send)
if($rsvp_id) {
  rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
}

// AFTER (always attempt receipt)
if($rsvp_id) {
  $result = rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
  if(!$result) {
    error_log('PayPal: Receipt email failed to send for RSVP ID: ' . $rsvp_id);
  }
}
// For non-RSVP payments (gift certificates), also send receipt
else if($atts['email']) {
  $gift_data = array(
    'email' => $atts['email'],
    'name' => $atts['name'],
    'amount' => $atts['amount'],
    'transaction_id' => $atts['transaction_id']
  );
  do_action('rsvpmaker_send_gift_certificate_email', $gift_data);
}
```

**Why it helps:** Ensures receipt is always sent, not just for some payment types

---

### Fix 2: Add Database Transaction Recording
**File:** `paypal-rest.php` - in `rsvpmaker_paypal_verify_rest()` function

**Current Issue:** Payment recorded in RSVP but not in money transaction table for some cases

**Change needed:** Always log to money_tx table after successful RSVP update

```php
// AFTER the RSVP update is successful, ensure money_tx is recorded
if(!$already_processed) {
  $updatesql = $wpdb->prepare("UPDATE %i SET amountpaid=%s, owed=%s, details=%s WHERE id=%d",$rsvptable,$paid,$owed,maybe_serialize($details),$rsvp_id);
  $result = $wpdb->query($updatesql);
  
  if($result === false) {
    error_log('PayPal: Failed to update RSVP record for ID: ' . $rsvp_id . ', Error: ' . $wpdb->last_error);
    return array('status' => 'Database update failed: ' . $wpdb->last_error);
  }
  
  // ALWAYS record to money transaction table as audit trail
  if(empty($existing)) {
    $existing = rsvpmaker_money_tx($atts);
    if(!$existing) {
      error_log('PayPal: Failed to create money transaction for RSVP ID: ' . $rsvp_id);
    }
  }
}
```

**Why it helps:** Creates an audit trail and ensures financial records match RSVP records

---

### Fix 3: Improve Duplicate Detection
**File:** `paypal-rest.php` - around line 85

**Current Issue:** Duplicate check only looks at details field which could be out of sync

**Change needed:** Add secondary check in money transaction table

```php
// BEFORE
if(!empty($details['paypal_transaction_id']) && $details['paypal_transaction_id'] === $data->id) {
  $already_processed = true;

// AFTER (add secondary check)
$money_table = rsvpmaker_money_table();
$existing_money_tx = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM %i WHERE transaction_id=%s",
  $money_table,
  $data->id
));

if(!empty($details['paypal_transaction_id']) && $details['paypal_transaction_id'] === $data->id) {
  $already_processed = true;
}
elseif(!empty($existing_money_tx)) {
  // Transaction was already recorded even if RSVP details don't show it
  error_log('PayPal: Transaction ' . $data->id . ' already in money table');
  $already_processed = true;
  $paid = $paidbefore; // Don't double-charge
}
```

**Why it helps:** Catches duplicate payments through multiple data sources, prevents double-charging

---

### Fix 4: Handle Missing Event ID Gracefully
**File:** `paypal-rest.php` - around lines 158-169

**Current Issue:** Receipt link generation fails silently when event_id is 0

**Change needed:** Generate receipt link even for non-event payments

```php
// BEFORE
if($event_id)
  $rsvp_receipt_link = add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($event_id));
elseif(!empty($saved['post_id']))
  $rsvp_receipt_link = add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($saved['post_id']));

// AFTER (always generate receipt link)
$receipt_page = $event_id ?: (!empty($saved['post_id']) ? $saved['post_id'] : 0);
if($receipt_page) {
  $rsvp_receipt_link = add_query_arg(
    array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),
    get_permalink($receipt_page)
  );
} else {
  // Fallback: use home page for payment-only (no event) receipts
  $rsvp_receipt_link = add_query_arg(
    array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()},
    home_url()
  );
}
```

**Why it helps:** Receipt link available in all cases, including gift certificate purchases

---

### Fix 5: Add Debug Logging
**File:** `paypal-rest.php` - at the start of `rsvpmaker_paypal_verify_rest()`

**Change needed:** Log all payment attempts for troubleshooting

```php
function rsvpmaker_paypal_verify_rest () {
    global $wpdb, $rsvp_options, $paypal_rest_keys;
    
    $request_body = file_get_contents('php://input');        
    $data = json_decode($request_body);
    
    // ADD LOGGING
    $log_entry = array(
      'timestamp' => current_time('mysql'),
      'transaction_id' => !empty($data->id) ? $data->id : 'unknown',
      'rsvp_id' => !empty($data->rsvp_tx) ? $data->rsvp_tx : 'unknown',
      'status' => !empty($data->status) ? $data->status : 'no_status',
      'amount' => !empty($data->purchase_units[0]->amount->value) ? $data->purchase_units[0]->amount->value : 0
    );
    error_log('PayPal Payment Attempt: ' . json_encode($log_entry));
    
    // ... rest of function
```

**Why it helps:** Creates audit trail for debugging payment issues in WP error logs

---

## Medium-Term Fixes (1-2 weeks)

### Database Enhancement: Add Payment Status Column
```sql
ALTER TABLE wp_rsvpmaker ADD COLUMN payment_status VARCHAR(20) DEFAULT 'unpaid' AFTER owed;
ALTER TABLE wp_rsvpmaker ADD INDEX idx_payment_status (payment_status);
```

After adding this:
- Update `paypal-rest.php` to set `payment_status = 'paid'` when payment received
- Update RSVP Report queries to use this column for filtering
- Set `payment_status = 'partial'` when `owed > 0` but `amountpaid > 0`

### Create Payment Processing Log Table
```sql
CREATE TABLE wp_rsvpmaker_payment_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rsvp_id BIGINT,
  transaction_id VARCHAR(255) UNIQUE,
  status VARCHAR(50),
  amount DECIMAL(10,2),
  payer_email VARCHAR(255),
  error_message TEXT,
  created_at DATETIME,
  processed_at DATETIME,
  INDEX idx_rsvp_id (rsvp_id),
  INDEX idx_transaction_id (transaction_id),
  INDEX idx_created_at (created_at)
);
```

After creating:
- Log every payment attempt (success and failure)
- Query this for admin payment history view
- Use for audit trail and duplicate detection

---

## Testing Immediate Fixes

1. **Test receipt email:**
   - Process a test payment through PayPal
   - Confirm receipt email arrives even for multi-event registrations
   - Check error logs: `tail -f /path/to/wp-content/debug.log`

2. **Test duplicate detection:**
   - Simulate webhook retry by sending same payment twice
   - Verify only recorded once
   - Check money transaction table has no duplicate entries

3. **Test logging:**
   - Make test payment
   - Check WordPress error log for payment attempt entry
   - Verify transaction_id and amount are logged

4. **Test receipt link:**
   - Verify receipt link appears in PayPal response
   - Test for both event registrations and gift certificates
   - Confirm link is clickable and shows receipt

---

## Commands to Monitor PayPal Issues

### Check payment logs (Linux):
```bash
tail -f /path/to/wp-content/debug.log | grep "PayPal"
```

### Database query - find unmatched payments:
```sql
SELECT rsvp.id, rsvp.email, rsvp.amountpaid, rsvp.owed, rsvp.fee_total
FROM wp_rsvpmaker rsvp
WHERE rsvp.owed > 0 
AND rsvp.amountpaid > 0
ORDER BY rsvp.id DESC
LIMIT 20;
```

### Check for duplicate transaction IDs:
```sql
SELECT transaction_id, COUNT(*) as count
FROM wp_rsvpmaker_money
WHERE status = 'PayPal'
GROUP BY transaction_id
HAVING count > 1;
```

### Find orphaned receipt codes:
```sql
SELECT meta_key, COUNT(*) as count
FROM wp_postmeta
WHERE meta_key LIKE 'rsvpmaker_receipt_%'
GROUP BY post_id
ORDER BY count DESC;
```

