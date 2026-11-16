# Debug PayPal Button Display Issue

## What to Do

1. Upload the updated `paypal-rest.php` to your live server
2. Clear browser cache (Ctrl+Shift+Delete)
3. Go to an RSVP confirmation page with PayPal payment
4. Right-click on the page → "View Page Source"
5. Search for "PayPal Button Error" in the HTML

## What the Comments Mean

If you see one of these HTML comments, it tells you exactly what's wrong:

### Comment Found:
```html
<!-- PayPal Button Error: Keys not configured -->
```
**Means**: PayPal credentials are NOT configured  
**Fix**: Go to WP Admin → RSVP Maker → Settings → PayPal  
**Action**: Add Client ID and Secret, then save

### Comment Found:
```html
<!-- PayPal Button Error: Sandbox enabled but no sandbox client ID -->
```
**Means**: Sandbox mode is ON, but Sandbox Client ID is missing  
**Fix**: Either:
- Add the Sandbox Client ID in settings, OR
- Turn OFF Sandbox mode if using live keys

### Comment Found:
```html
<!-- PayPal Button Error: Live mode but no live client ID -->
```
**Means**: Sandbox mode is OFF (live mode), but live Client ID is missing  
**Fix**: Either:
- Add the live Client ID in settings, OR
- Turn ON Sandbox mode if using sandbox keys

### No PayPal Comment Found
**Means**: The function is running but output buffering failed  
**Likely Cause**: PHP error during script generation  
**Action**: Check WordPress error log for PHP fatal errors

## Quick Test Steps

1. **Upload File**
   ```
   paypal-rest.php → /wp-content/plugins/rsvpmaker/
   ```

2. **Clear Cache**
   ```
   Browser: Ctrl+Shift+Delete
   WordPress: Go to settings and enable "cache busting" or just wait 5 min
   ```

3. **Test Page**
   - Create RSVP with fee
   - Check confirmation page
   - View page source (Right-click → View Page Source)
   - Search for "PayPal Button Error"

4. **Interpret Result**
   - If error comment found → See "What the Comments Mean" above
   - If no error comment → Check WordPress error log for PHP errors
   - If PayPal SDK button appears → Success! ✓

## How to Check Credentials in WordPress Admin

1. Go to WP Admin
2. RSVP Maker menu → Settings
3. Scroll to "PayPal" section
4. Look for:
   - "Enable PayPal REST API" checkbox
   - "Client ID" field
   - "Secret" field
   - "Sandbox Mode" toggle

## What Credentials Should Look Like

**Sandbox**:
- Client ID: starts with "AZ"
- Should be a long string of characters

**Live/Production**:
- Client ID: starts with "AY"
- Should be a long string of characters

**Both should have**:
- Secret: Very long string
- Not empty or placeholder text

## Troubleshooting Flowchart

```
PayPal button not showing?
├─ Check page source for "PayPal Button Error" comment?
│  ├─ YES → See "What the Comments Mean" above
│  └─ NO → Check WP error log for PHP fatal errors
├─ Credentials configured in WP Admin?
│  ├─ NO → Add them
│  └─ YES → Is Sandbox mode matching your credentials?
│     ├─ NO → Toggle sandbox mode or add correct credentials
│     └─ YES → Check error log or reload page
└─ Still broken? → Upload file again & clear all caches
```

## Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| Error: Keys not configured | PayPal credentials missing | Add to WP Admin settings |
| Error: Sandbox enabled but no ID | Sandbox ON, but no sandbox credential | Add sandbox credential or turn OFF sandbox |
| Error: Live mode but no ID | Sandbox OFF, but no live credential | Add live credential or turn ON sandbox |
| No error comment, no button | PHP error during rendering | Check WP error log for fatal errors |
| Button shows but fails on payment | Different issue (not this one) | Check payment verification logs |

## Next Steps

1. Upload paypal-rest.php
2. Check page source for error comment
3. Tell me what error message (if any) you see
4. I'll help fix the specific issue

The debug comments should tell us exactly what's wrong!
