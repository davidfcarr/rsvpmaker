# Newsletter Popup Toggle in React-Based Settings UI

## Overview
Successfully integrated the Newsletter Signup Popup on/off toggle into the modern React-based Email Basic Settings interface.

## What Changed

### 1. React Component (admin/src/settings/components/mailing-list-settings.jsx)
Added the newsletter popup toggle to the Email Basic Settings form:

```jsx
// Added field to the form
{ id: 'rsvpmail_list_rsvpmodal_on', label: __( 'Enable Newsletter Signup Popup', 'rsvpmaker' ), type: 'boolean', Edit: 'checkbox' }

// Added collapsible section to display it
{
    id: 'modal_settings',
    label: __( 'Newsletter Signup Popup', 'rsvpmaker' ),
    children: [ 'rsvpmail_list_rsvpmodal_on' ],
    layout: { type: 'card', isOpened: false, withHeader: true },
}
```

**Result**: Checkbox appears in a collapsible "Newsletter Signup Popup" card section within Email Basic Settings

### 2. REST API Schema (rsvpmaker.php, lines 541-570)
Updated the `rsvpemail_from_settings` REST API registration to include the new boolean field:

```php
// Added to $email_from_properties
'rsvpmail_list_rsvpmodal_on' => array( 'type' => 'boolean' ),

// Added to default values
'rsvpmail_list_rsvpmodal_on' => false,
```

**Result**: The toggle value is properly serialized/deserialized via the REST API

### 3. PHP Settings Handler (rsvpmaker-email.php)
- Updated `update_rsvpemail_from_settings()` to handle boolean sanitization (line 6996-7004)
- Added `get_rsvpemail_from_settings_option()` helper function (line 7006-7008)
- Updated `rsvpmail_list_rsvpmodal_controller()` to check both old and new option locations (line 6708)

**Result**: Setting is properly saved and retrieved with full backward compatibility

## How It Works

### User Workflow
1. Go to **Settings → RSVPMaker Settings → Email Basic Settings**
2. Find the **"Newsletter Signup Popup"** section (collapsed by default)
3. Check/uncheck the **"Enable Newsletter Signup Popup"** checkbox
4. Click **Save**
5. Setting is saved via REST API to WordPress options

### Data Flow
```
React Component
    ↓
useFromOptions Hook (fetches from REST API)
    ↓
/wp/v2/settings endpoint
    ↓
rsvpemail_from_settings option
    ↓
Database
```

### Save Flow
```
User toggles checkbox
    ↓
React state updated
    ↓
Click Save button
    ↓
saveFromOptions() via REST API
    ↓
update_rsvpemail_from_settings()
    ↓
Database updated
```

### Frontend Display
When the setting is enabled, `rsvpmail_list_rsvpmodal_controller()` will:
1. Check if user is logged out
2. Check if modal hasn't been displayed before (cookie)
3. Check if `rsvpmail_list_rsvpmodal_on` is enabled (new location)
4. OR check `rsvpmail_list_rsvpmodal_on` legacy option (backward compatibility)
5. If conditions met, load modal and display after 5-second delay

## Backward Compatibility

The implementation supports both:
- **Old location**: `rsvpmail_list_rsvpmodal_on` as separate WordPress option
- **New location**: `rsvpmail_list_rsvpmodal_on` within `rsvpemail_from_settings` object

The controller function checks both with OR logic:
```php
(get_option('rsvpmail_list_rsvpmodal_on') || get_rsvpemail_from_settings_option('rsvpmail_list_rsvpmodal_on'))
```

This ensures:
- Existing installations continue working without migration
- New installations use the React-based setting
- Either setting enables the feature
- Updating via React updates the new location

## Integration Points

### Frontend Display
- **File**: rsvpmaker-email.php
- **Function**: `rsvpmail_list_rsvpmodal_controller()` (line 6708)
- **Behavior**: Checks if popup should display

### Backend Storage
- **Option Name**: `rsvpemail_from_settings`
- **Field Name**: `rsvpmail_list_rsvpmodal_on`
- **Type**: Boolean
- **Default**: false (popup disabled)

### REST API
- **Endpoint**: `/wp/v2/settings`
- **Setting**: `rsvpemail_from_settings`
- **Schema**: Registered in rsvpmaker.php (line 551-570)

### React Hook
- **File**: admin/src/settings/hooks/use-from-options.js
- **Behavior**: Fetches and saves rsvpemail_from_settings via REST API

## Testing

### Manual Testing
1. **Enable the popup**:
   - Go to Email Basic Settings
   - Expand "Newsletter Signup Popup"
   - Check "Enable Newsletter Signup Popup"
   - Click Save
   - Verify: Setting saved successfully

2. **Test popup display**:
   - Log out (or use private/incognito mode)
   - Visit your website homepage
   - Wait 5 seconds - modal should appear
   - Or add `?show_rsvp_popup=1` to force immediate display

3. **Disable the popup**:
   - Go to Email Basic Settings
   - Expand "Newsletter Signup Popup"
   - Uncheck "Enable Newsletter Signup Popup"
   - Click Save
   - Verify: Popup no longer displays on homepage

### Browser DevTools
```javascript
// Check if setting is enabled
wp.apiFetch({ path: '/wp/v2/settings' }).then(settings => {
  console.log(settings.rsvpemail_from_settings.rsvpmail_list_rsvpmodal_on);
});
```

## Database Storage

### WordPress Options Table
```sql
SELECT * FROM wp_options 
WHERE option_name = 'rsvpemail_from_settings';
```

Example value:
```json
{
  "email-from": "info@example.com",
  "email-name": "My Website",
  "company": "My Company",
  "mailing_address": "123 Main St, City, State 12345",
  "rsvpmail_list_rsvpmodal_on": true
}
```

## Files Modified

1. **admin/src/settings/components/mailing-list-settings.jsx**
   - Added field definition for rsvpmail_list_rsvpmodal_on
   - Added form section for Newsletter Signup Popup
   - Status: ✅ Complete

2. **rsvpmaker.php** (lines 541-570)
   - Added boolean property to REST schema
   - Added default value initialization
   - Status: ✅ Complete

3. **rsvpmaker-email.php**
   - Updated update_rsvpemail_from_settings() (line 6996-7004)
   - Added get_rsvpemail_from_settings_option() (line 7006-7008)
   - Updated rsvpmail_list_rsvpmodal_controller() (line 6708)
   - Status: ✅ Complete

## Deployment

✅ **Ready for deployment**
- No database migration required
- Backward compatible with existing installations
- No breaking changes
- All code follows WordPress standards
- REST API properly registered

## Related Documentation

- `QUICK_START_POPUP.md` - User guide for using the popup
- `SETTINGS_CLARIFICATION.md` - Original settings location documentation
- `assets/modal-popup.js` - Frontend popup implementation
- `REFACTORING_SUMMARY.md` - Technical overview of all refactoring

## Future Enhancements

Potential improvements:
- Add delay/timing configuration to React UI
- Add title/footer text customization in React UI
- Add CSS customization options in React UI
- Add preview/test button in React UI
- Add animation options

These would require extending the `rsvpemail_from_settings` schema in both React and PHP registration.
