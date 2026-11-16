# Quick Reference: Newsletter Popup Toggle in React UI

## Location
**Settings → RSVPMaker Settings → Email Basic Settings → Newsletter Signup Popup** (collapsible section)

## What's New
The **"Enable Newsletter Signup Popup"** checkbox now appears in the modern React-based admin UI, no longer requiring navigation to the old PHP settings form.

## User Instructions

### Enable the Popup
1. Go to Settings → RSVPMaker Settings
2. Make sure you're in the "Email Basic Settings" tab  
3. Scroll down to find "Newsletter Signup Popup" section
4. Click the section header to expand it
5. Check the "Enable Newsletter Signup Popup" checkbox
6. Click Save

### Test the Popup
- **Normal display**: Visit homepage in private/incognito mode - popup appears after 5 seconds
- **Force display**: Add `?show_rsvp_popup=1` to URL - popup appears immediately
- **Mobile**: Check on phone/tablet - popup is fully responsive

### Disable the Popup
1. Go to Settings → RSVPMaker Settings → Email Basic Settings
2. Expand "Newsletter Signup Popup" section
3. Uncheck the checkbox
4. Click Save

## Technical Details (for Developers)

### Data Storage
- **Option**: `rsvpemail_from_settings`
- **Field**: `rsvpmail_list_rsvpmodal_on`
- **Type**: Boolean (true/false)
- **Default**: false

### REST API
- **Endpoint**: `/wp/v2/settings`
- **Property**: `rsvpemail_from_settings.rsvpmail_list_rsvpmodal_on`

### Frontend Check
```php
$from_options = get_rsvpemail_from_settings();
$modal_enabled = $from_options['rsvpmail_list_rsvpmodal_on'] ?? false;
```

### Browser Console Check
```javascript
wp.apiFetch({ path: '/wp/v2/settings' }).then(s => {
  console.log('Modal enabled:', s.rsvpemail_from_settings.rsvpmail_list_rsvpmodal_on);
});
```

## What Changed

### Files Modified
1. **admin/src/settings/components/mailing-list-settings.jsx**
   - Added field to React form
   - Added new collapsible section

2. **rsvpmaker.php**
   - Added field to REST API schema
   - Updated default values

3. **rsvpmaker-email.php**
   - Updated settings handler for boolean sanitization
   - Added helper function for safe retrieval
   - Updated controller to check both old and new locations

### Backward Compatibility
✅ **Fully backward compatible** - Old PHP forms still work, new React UI uses same data

## Common Questions

**Q: Does this replace the old settings form?**  
A: The old settings form still exists and works, but the React UI is now the preferred way to manage email settings.

**Q: Will my existing popup setting be lost?**  
A: No, the system checks both the old and new locations. Your existing setting continues to work.

**Q: Can I still use the old settings form?**  
A: Yes, but changes made via the React UI will be visible in both places.

**Q: How do I customize the popup further?**  
A: Additional customization options (title, footer, CSS) are still in the Email List tab. The React UI currently only has the on/off toggle.

**Q: Why does the section start collapsed?**  
A: This keeps the Email Basic Settings tab clean and focused. Expand it when you need to change the setting.

## Support Documentation

- **Full User Guide**: See `QUICK_START_POPUP.md`
- **Technical Details**: See `REACT_SETTINGS_INTEGRATION.md`
- **Troubleshooting**: See `SETTINGS_CLARIFICATION.md`

## Version Info
- Integrated: 2026-11-12
- Component: Email Basic Settings
- Framework: React with WordPress DataViews
- Compatibility: WordPress 5.2+
