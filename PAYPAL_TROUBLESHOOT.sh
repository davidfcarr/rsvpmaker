#!/bin/bash
# PayPal Payment Processing - Troubleshooting Commands
# Copy these commands to quickly diagnose payment issues

echo "=================================="
echo "PayPal Payment Troubleshooting Kit"
echo "=================================="

# Path to WordPress installation (adjust as needed)
WP_PATH="/path/to/wordpress"
DEBUG_LOG="$WP_PATH/wp-content/debug.log"

# Path to database (WordPress uses wp-cli typically)
# Adjust the database name as needed
DB_NAME="wordpress_db"
DB_USER="wordpress_user"
DB_PASS="wordpress_pass"

echo ""
echo "--- QUICK CHECKS ---"
echo ""

# 1. Check if debug logging is enabled
echo "1. Checking if debug logging is enabled..."
grep -c "define.*WP_DEBUG.*true" "$WP_PATH/wp-config.php"
if [ $? -eq 0 ]; then
    echo "   ✓ Debug logging is ENABLED"
else
    echo "   ✗ Debug logging is DISABLED - enable it to see payment logs"
    echo "   Add to wp-config.php: define('WP_DEBUG', true);"
fi

echo ""

# 2. Check if payment logs exist
echo "2. Checking for recent payment logs..."
if [ -f "$DEBUG_LOG" ]; then
    COUNT=$(grep -c "PayPal" "$DEBUG_LOG" 2>/dev/null || echo 0)
    echo "   Found $COUNT PayPal-related log entries"
    if [ $COUNT -gt 0 ]; then
        echo "   Last few entries:"
        grep "PayPal" "$DEBUG_LOG" | tail -5
    fi
else
    echo "   ✗ Debug log file not found at: $DEBUG_LOG"
fi

echo ""
echo "--- DATABASE QUERIES ---"
echo ""

# 3. Query recent RSVP payments
echo "3. Recent RSVP payments (last 10):"
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
  id, 
  email, 
  amountpaid, 
  owed, 
  fee_total,
  DATE_FORMAT(COALESCE(updated, created), '%Y-%m-%d %H:%i:%s') as date
FROM wp_rsvpmaker 
WHERE amountpaid > 0 
ORDER BY id DESC 
LIMIT 10;
"

echo ""

# 4. Query payment transaction log
echo "4. Recent payment transactions (last 10):"
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
  id,
  name,
  email,
  amount,
  status,
  transaction_id,
  DATE_FORMAT(date, '%Y-%m-%d %H:%i:%s') as date
FROM wp_rsvpmaker_money 
WHERE status = 'PayPal'
ORDER BY id DESC 
LIMIT 10;
"

echo ""

# 5. Check for duplicate transactions
echo "5. Checking for duplicate transaction IDs..."
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
  transaction_id, 
  COUNT(*) as count,
  SUM(amount) as total_amount
FROM wp_rsvpmaker_money 
WHERE status = 'PayPal'
GROUP BY transaction_id 
HAVING count > 1;
"

echo ""

# 6. Find unpaid registrations with online payment option
echo "6. Registrations with online payment selected but unpaid:"
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
  id,
  email,
  fee_total,
  amountpaid,
  owed,
  details
FROM wp_rsvpmaker 
WHERE fee_total > 0 
AND amountpaid = 0 
ORDER BY id DESC 
LIMIT 10;
"

echo ""
echo "--- ERROR LOG SEARCHES ---"
echo ""

# 7. Look for PayPal errors
echo "7. PayPal errors in debug log:"
grep -i "paypal.*error" "$DEBUG_LOG" 2>/dev/null | tail -10

echo ""

# 8. Look for payment failures
echo "8. Failed payments:"
grep -i "payment.*fail\|failed.*payment" "$DEBUG_LOG" 2>/dev/null | tail -10

echo ""

# 9. Look for duplicate detection
echo "9. Duplicate transactions detected:"
grep "Duplicate transaction" "$DEBUG_LOG" 2>/dev/null | tail -10

echo ""

# 10. Look for email failures  
echo "10. Email send failures:"
grep "email.*fail\|send failed" "$DEBUG_LOG" 2>/dev/null | tail -10

echo ""
echo "--- MANUAL CHECKS ---"
echo ""

echo "11. Check PayPal plugin settings:"
echo "    - Go to WordPress Admin > RSVP Maker > PayPal Settings"
echo "    - Verify Client ID and Client Secret are set"
echo "    - Verify Currency is correct"
echo "    - Click 'Test Connection' button"

echo ""

echo "12. Check event registration settings:"
echo "    - Go to event post"
echo "    - Check 'RSVP Settings' tab"
echo "    - Verify 'Fee/Cost' is set correctly"
echo "    - Verify 'Online payment method' includes PayPal"

echo ""

echo "13. Check for email delivery:"
echo "    - Search email for: 'receipt', 'payment', 'RSVP'  "
echo "    - Check spam/junk folders"
echo "    - If not found, check WordPress email settings"

echo ""
echo "=================================="
echo "End of Troubleshooting Kit"
echo "=================================="
