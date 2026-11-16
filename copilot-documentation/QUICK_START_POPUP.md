# Quick Start: Newsletter Signup Popup

## ⚡ TL;DR
The newsletter signup popup settings are in **Settings → RSVPMaker Settings → Email List tab**, NOT in Email Basics.

---

## 📍 How to Find It

### Step 1: Go to WordPress Settings
1. Log in to WordPress admin
2. Click **Settings** in the left menu
3. Click **RSVPMaker Settings**

### Step 2: Find Email List Tab
You'll see several tabs at the top of the page. Click on the **Email List** tab.

### Step 3: Look for "Built-in Mailing List"
Scroll down to the section labeled **"Built-in Mailing List"**

You'll see:
```
☑ Popup On    [Show Popup Options]
```

---

## 🎛️ Turning It On/Off

### Enable the Popup
- Check the **"Popup On"** checkbox
- Click **Save Changes** at the bottom

### Customize It
- Click the **"Show Popup Options"** checkbox to expand settings
- Customize:
  - Popup title
  - Footer text  
  - Width and distance from top
  - Custom CSS for styling
- Click **Save Changes**

### Disable It
- Uncheck the **"Popup On"** checkbox
- Click **Save Changes**

---

## 👀 Testing the Popup

### Method 1: Normal Display (After Delay)
1. Open your website in a fresh browser or private/incognito mode
2. The popup will appear after 5 seconds (configurable)
3. Close it by clicking the X button

### Method 2: Force Display (Immediate)
1. Add `?show_rsvp_popup=1` to your site URL
2. Example: `https://yoursite.com/?show_rsvp_popup=1`
3. The popup appears immediately (ignores cookies/delay)
4. Great for testing and previews

### Method 3: Preview Link
1. Go to Email List Settings
2. Click the **"See preview"** link
3. Opens popup in a new tab (displays after 5 seconds)

---

## 🎨 Styling the Popup

In the "Show Popup Options" section, you can add custom CSS.

### Common Customizations

**Increase title size:**
```css
.rsvpmodal-header h3 { font-size: 24px; }
```

**Change header background color:**
```css
.rsvpmodal-header { background-color: #2c5aa0; }
```

**Adjust text color:**
```css
.rsvpmodal-body p { color: #333; font-size: 16px; }
```

**Style the button:**
```css
.rsvpsubscribe { background-color: #4CAF50; color: white; padding: 10px 20px; }
```

### View Available Colors
At the bottom of the Email List Settings, you'll see:
- **Colors used in your theme** - view exact hex codes
- **Gradients used in your theme** - for advanced styling

---

## 📱 Mobile Display

The popup is automatically responsive:
- **Desktop**: Full configured size
- **Tablet**: ~95% width, adjusted spacing
- **Mobile Phone**: ~90% width, optimized font sizes
- **Small Phone**: ~98% width, minimal padding

No configuration needed - it just works!

---

## 🍪 How It Works

The popup uses cookies to avoid annoying users:

1. **First Visit**: Popup displays after 5 seconds
2. **User Closes Popup**: Cookie is set
3. **Next Visit**: User doesn't see popup for 1 year (unless cookies cleared)
4. **Force Display**: Add `?show_rsvp_popup=1` to always show it (for testing)

---

## ❓ Troubleshooting

### Popup Not Showing
**Check these:**
1. Is **"Popup On"** checkbox checked?
2. Did you click **Save Changes**?
3. Clear your browser cookies (or use private/incognito mode)
4. Try the force-display method: add `?show_rsvp_popup=1` to URL

### Popup Showing to Logged-In Users
This is by design - the popup only shows to:
- Anonymous (not logged-in) visitors
- Without the tracking cookie
- On first visit or after cookie expires

### Styling Not Applying
- Make sure CSS is in the correct text area
- Use `!important` if styles don't override:
  ```css
  .rsvpmodal-header h3 { font-size: 24px !important; }
  ```
- Check browser DevTools (F12) to see what CSS is actually applied

### Not Seeing Settings Tab
If you can't find the Email List tab:
1. Make sure you're on: **Settings → RSVPMaker Settings**
2. Look for tabs across the top of the page
3. If only one tab shows, you may need to save other settings first

---

## 📚 Learn More

For detailed technical information, see:
- `SETTINGS_CLARIFICATION.md` - Settings location and features
- `REFACTORING_SUMMARY.md` - How the system works technically
- `assets/README.md` - JavaScript implementation details

---

**Last Updated**: 2026-11-07  
**Question?** Check the documentation or review the GitHub commit history for this feature.
