# Resend Receipt Feature - RSVP Report

## Feature Overview

Added a "Resend Receipt" link to the RSVP Report screen that allows administrators to resend the receipt/confirmation email to any RSVP entry.

### User Experience

1. **Admin views RSVP Report** - Navigate to WordPress Admin > RSVP > RSVP Report > select an event
2. **Sees new "Resend Receipt" link** - Between "Edit" and "Delete" links for each RSVP entry
3. **Clicks "Resend Receipt"** - JavaScript confirmation dialog appears asking to confirm resend
4. **Confirms action** - Confirmation email is sent to the attendee's email address
5. **Sees success message** - Admin receives confirmation showing receipt was sent

## Implementation Details

### Files Modified

#### 1. rsvpmaker-report.php

**Added action handler (top of rsvp_report function):**
```php
// Handle resend receipt action
if ( isset( $_GET['resend_receipt'] ) && wp_verify_nonce( rsvpmaker_nonce_data('data'), rsvpmaker_nonce_data('key') ) ) {
    $rsvp_id = intval( $_GET['resend_receipt'] );
    rsvpmaker_admin_resend_receipt( $rsvp_id );
}
```

**Modified action links (around line 807):**
- Added "Resend Receipt" link with JavaScript confirmation prompt
- Link includes nonce for security
- Confirmation message personalizes with attendee name

Before:
```php
$editlink = (...);
echo sprintf( '<p>%s <a href="%s&delete=%d">Delete...</a></p>', 
    $editlink, admin_url(...), $row['id'] );
```

After:
```php
$editlink = (...);
$resend_link = sprintf( '<a href="%s&resend_receipt=%d&%s" onclick="return confirm(\'%s\');">
    Resend Receipt</a> | ', 
    admin_url(...), $row['id'], rsvpmaker_nonce('query'), 
    esc_attr(__('Send receipt email to '.$row['first'].' '.$row['last'].'?','rsvpmaker')) );
echo sprintf( '<p>%s %s <a href="%s&delete=%d">Delete...</a></p>', 
    $editlink, $resend_link, admin_url(...), $row['id'] );
```

#### 2. rsvpmaker-email.php

**Added function: `rsvpmaker_admin_resend_receipt( $rsvp_id )`**

This function:
1. Validates the RSVP entry exists
2. Validates the event exists
3. Checks if "Send Confirmation Email" is enabled for the event
4. Loads confirmation email templates
5. Builds the confirmation message with:
   - Receipt code and print receipt link
   - Event title, date, and details
   - Guest information (party members)
   - RSVP update link
   - iCal attachment (if available)
6. Sends the email to the attendee
7. Displays success message to admin

**Key aspects:**
- Uses existing confirmation template from event settings
- Respects "Send Confirmation Email on/off" setting
- Generates unique receipt code if one doesn't exist
- Includes iCal attachment for calendar integration
- Returns appropriate error messages if event/RSVP not found or emails disabled

## Security Features

✓ **Nonce Verification** - All actions protected with WordPress nonce
✓ **Permission Check** - Only admins with 'edit_others_posts' capability can resend
✓ **Input Validation** - RSVP ID converted to integer to prevent injection
✓ **Confirmation Dialog** - JavaScript confirms action before sending
✓ **Event Check** - Verifies RSVP belongs to valid event

## Email Content

The resent receipt includes:

1. **Receipt Code** - Provides link to print receipt with event details
2. **Receipt Link** - `print_receipt_at_[event_url]`
3. **Event Information** - Title, date, and time
4. **RSVP Details** - Guest information, party members, fees
5. **Confirmation Message** - Custom message from event settings
6. **Update Link** - Link to update RSVP information
7. **iCal Attachment** - Calendar event for email clients

## Workflow Example

```
Admin Report View:
┌─────────────────────────────────────────┐
│ John Smith (john@example.com)            │
│ Edit John Smith | Resend Receipt | Delete│
└─────────────────────────────────────────┘
                  ↓
          Click "Resend Receipt"
                  ↓
        ┌─────────────────────┐
        │ Send receipt email  │
        │ to John Smith?      │
        │ [OK] [Cancel]       │
        └─────────────────────┘
                  ↓
        Email sent successfully
    "Receipt resent to John Smith
         (john@example.com)"
                  ↓
        Email appears in attendee's inbox
```

## Features & Benefits

| Feature | Benefit |
|---------|---------|
| **No re-registration needed** | Admin can resend without attendee re-doing form |
| **Uses existing templates** | Consistent with original confirmation email |
| **Event settings respected** | Only works if emails enabled for that event |
| **Confirmation dialog** | Prevents accidental sending |
| **Success feedback** | Admin knows email was sent |
| **Receipt code included** | Attendee can print event receipt |
| **iCal support** | Works with calendar integrations |

## Testing Checklist

- [ ] Click "Resend Receipt" link displays confirmation dialog
- [ ] Canceling confirmation does not send email
- [ ] Confirming sends email to attendee
- [ ] Email includes receipt code and print link
- [ ] Email includes event details and date
- [ ] Email includes guest party information
- [ ] Email includes RSVP update link
- [ ] Email includes custom confirmation message
- [ ] Success message displays after sending
- [ ] Only works if "Send Confirmation Email" is enabled
- [ ] Non-admin users cannot access feature
- [ ] Nonce verification prevents CSRF

## Database Impact

No database schema changes needed. Uses existing:
- `wp_rsvpmaker` table for RSVP entries
- `wp_postmeta` for receipt codes and event settings
- Existing email template system

## Backward Compatibility

✓ Fully backward compatible
✓ No breaking changes to existing functionality
✓ New link added alongside existing Edit/Delete links
✓ Works with all existing event configurations

## Code Quality

- ✓ PHP syntax validated
- ✓ Follows WordPress coding standards
- ✓ Proper escaping and sanitization
- ✓ Security nonce verification
- ✓ Comprehensive error handling
- ✓ Well-documented with comments

## Commit Information

**Commit Hash:** 5abe01a
**Message:** Add 'Resend Receipt' functionality to RSVP Report screen
**Files Changed:** 
- rsvpmaker-report.php (+36 lines)
- rsvpmaker-email.php (+86 lines)
