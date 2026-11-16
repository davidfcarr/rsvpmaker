# Newsletter Signup Popup Settings Location

## Summary
The newsletter signup popup modal on/off toggle and configuration options are located in the **Email List Settings** tab, not the "Email Basics" tab.

## Location Details

### Where to Find Popup Settings
1. Go to **Settings → RSVPMaker Settings**
2. Click the **"Email List"** tab
3. Look for the section titled **"Built-in Mailing List"**
4. You'll see: `☑ Popup On` checkbox with a secondary checkbox to show/hide advanced options

### Popup Configuration Options
Once you enable "Popup On", you can:
- Click the secondary checkbox to show/hide popup configuration options
- Configure popup title
- Configure footer text
- Adjust width and distance from top
- Add custom CSS for different elements
- View your theme's colors and gradients for styling reference

### Preview
- Link at top: "See preview" opens a preview of the popup (displays after 5 seconds)
- Force display: Append `?show_rsvp_popup=1` to your site URL to force immediate display

## Why This Location?
The modal popup is an email list engagement feature, not a general email setting, so it's grouped with other email list management features in the "Email List" settings tab rather than general "Email Basics" (which handles sender name, address, etc.).

## Mobile Responsiveness
The popup is now fully responsive and automatically adjusts its size and styling on:
- Tablets (≤768px width): 95% width, adjusted padding
- Phones (≤480px width): 90% width, reduced font sizes
- Small phones (≤360px width): 98% width, minimal padding

## How It Works

### When Popup Displays
The popup displays to:
- Anonymous (not logged-in) visitors
- Visitors without "rsvpmail" or "rsvpmail_subscriber" cookies
- Visitors who haven't seen the popup before (checked via "rsvpmail_shown_popup" cookie)

### Cookie Management
- **rsvpmail_shown_popup**: Set to expire after 1 year (31,536,000 seconds)
- Users can close the popup and won't see it again for one year
- The `?show_rsvp_popup=1` parameter forces display regardless of cookies (useful for testing)

### JavaScript Implementation
The popup is handled by a static JavaScript file (`assets/modal-popup.js`) that:
- Loads via `wp_enqueue_script()` to avoid WordPress content filter corruption
- Detects the modal HTML on the page using `[data-rsvp-modal]` attribute
- Manages display timing, cookies, and user interactions
- Reads configuration from HTML `data-*` attributes

## Status
✅ **Fully implemented and tested on production**
- Static JS file avoids content filter issues that corrupted embedded JavaScript
- Mobile responsive CSS media queries included
- Force-display query parameter works for testing
- All configuration options accessible in Email List Settings tab
- Cookies properly manage display frequency
