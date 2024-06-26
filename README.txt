=== RSVPMaker ===
Contributors: davidfcarr
Donate: http://www.rsvpmaker.com
Tags: event, calendar, rsvp, custom post type, paypal, stripe, email, mailchimp, gutenberg, email marketing, marketing
Donate link: http://rsvpmaker.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.6
Requires at least: 5.0
Tested up to: 6.5.4
Stable tag: 11.0.9

RSVPMaker is an event and email marketing and management platform. Schedule events, send invitations, track RSVPs, and collect payment via PayPal or Stripe. Send newsletters, including event and blog post roundups.

== Description ==

RSVPMaker is an event and email marketing tool. Use PayPal or Stripe to collect event payments. RSVPMaker handles scheduling, event marketing, and RSVP tracking. You can send email to small lists through your web server or take advantage of the integrations with Postmark and Mailchimp to scale up.

When implemented in combination with Postmark for reliable email delivery, RSVPMaker can function as an alternative to Mailchimp or MailPoet that allows you to format messages in the WordPress editor and easily incorporate events (for invitations) and blog posts or excerpts from posts (for email newsletters and promotions).

https://youtu.be/6zp_NaGb7qs

Use RSVPMaker to:
- Schedule and promote events of all sorts: conferences, classes, seminars, speaking events, parties and weddings are a few common uses.
- Register attendees, prompting them to enter whatever information you need, including the names of guests.
- Collect money using PayPal or Stripe.
- Create webinars and other online events leveraging free resources like the YouTube Live service.
- Create, format, and edit email newsletters within the WordPress block editor, rather than learning another content composer tool. Use dynamic blocks or shortcodes to incorporate dynamic content like lists of recent blog posts or upcoming events.
- Power membership-oriented websites with tools for emailing all your members or just those who have or have not registered for a specific event.
- Send email using your own web server, an SMTP plugin or the integrations with Mailchimp and Postmark.
- Postmark integration has the advantage of supporting both broadcast / mailing list and transactional messages (like RSVP Confirmations).

## Creating and Managing Events

RSVPMaker events are created and edited just like blog posts in the WordPress editor, with the addition of parameters like event date (so the items can be listed chronologically and displayed on a calendar grid).

You can use RSVPMaker for event announcements, or turn on the Collect RSVPs function and set additional options for sending email notifications, customizing confirmation and reminder messages, and setting a price or prices if you will be requesting online payments via PayPal.

RSVP reports can be viewed on the administrator's dashboard or downloaded as spreadsheets.

If you hold events on a recurring schedule, such as First Monday or Every Friday, you can define a template with the boilerplate details and quickly generate multiple entries that follow that schedule. Individual event posts can still be customized. For example, you might book a series of monthly events for the year and add the names of speakers or agenda details as you go along.

The RSVP Mailer tool allows you to use the familiar WordPress editor to format email newsletters and promotional messages, which can include embedded events and other dynamic content from your website. You use the same tools to format transactional messages such as confirmation and reminder messages.

## Hosting and Support

RSVPMaker is a free plugin that doesn't hold much back in terms of "premium" features.

The plugin author is available to consult on customizations, but most generally useful enhancements are folded back into the core plugin code. RSVPMaker also aims to be developer-friendly, allowing you to build event-centric applications on top of it.

Hosting and support of pre-configured websites is available through [RSVPMaker.com](https://rsvpmaker.com/). RSVPMaker.com uses Siteground hosting behind the scenes and Postmark for reliable delivery of email newsletters and transactional messages.

<a href="mailto:david@rsvpmaker.com?subject=RSVPMaker Postmark customizations">Contact the plugin author</a> for details about additional customizations for reselling Postmark services across WordPress multisite networks.

[__RSVPMaker.com__](https://rsvpmaker.com/)
[RSVPMaker on GitHub](https://github.com/davidfcarr/rsvpmaker)

Free Extensions:

[RSVPMaker for Toastmasters](http://wordpress.org/plugins/rsvpmaker-for-toastmasters) provides meeting management for public speaking and leadership development clubs that are part of Toastmasters International.

[RSVPMaker Volunteer Roles](https://wordpress.org/plugins/rsvpmaker-volunteer-roles/) Sign up people to fill specific roles at an event.

Translations (some may be out of date):

German: Markus König, Björn Wilkens

Dutch: Els van der Zalm

Spanish: Andrew Kurtis, [__WebHostingHub__](http://www.webhostinghub.com/)

Polish: Jarosław Żeliński

Norwegian: Thomas Nybø

Turkish: Göksel UÇAK

Thank you!

Translators please reach out to me if you want an updated POT source file

== Installation ==

1. Upload the entire `rsvpmaker` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Visit the RSVPMaker options page to configure default values for RSVP email notifications, etc.
1. Check that you have set the timezone for your site on the Settings -> General WordPress admin screen
1. Create and customize your events listing page. Embed the RSVPMaker Upcoming block or the RSVPMaker Query Loop block (a variation on WordPress's standard Query Loop). The RSVPMaker Calendar block displays a calendar grid. These blocks can also be used to customize the standard archive page displayed at /rsvpmaker/ on your site.
1. OPTIONAL: Depending on your theme, you may want to create a single-rsvpmaker template to prevent confusion between the the event date and the post publication date (move the post publication date to the bottom or just remove it).
1. OPTIONAL: To enable online payments for events, obtain the necessary credentials from PayPal or Stripe to enter into RSVPMaker settings.

For basic usage, you can also have a look at the [plugin homepage](http://www.rsvpmaker.com/).

== Frequently Asked Questions ==

= Where can I get more information about using RSVPMaker? =

For basic usage, you can also have a look at the [plugin homepage](http://www.rsvpmaker.com/).

== Screenshots ==

1. Example of an event listing with an RSVP Now! button.
2. Customizable RSVPMaker registration form.
3. Prompt to pay by credit card (PayPal also supported).
4. Email confirmation message.
5. Event options displayed within the WordPress editor. This is where you turn on registration, customize the form, define confirmation and reminder messages, and set pricing (if any).
6. Timezone conversions displayed automatically, which is handy for webinars and online meetings with a global audience.
7. Built-in email template with options for which list the message should be distributed to.

== Credits ==

    RSVPMaker
    Copyright (C) 2010-2023 David F. Carr

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    See the GNU General Public License at <http://www.gnu.org/licenses/gpl-2.0.html>.
	
	RSVPMaker also includes code derived from the software development kits for PayPal, 
    Stripe and MailChimp under the license of their creators. Inlining of CSS code in
    the HTML of email messages provided by the [Emogrifier](https://github.com/MyIntervals/emogrifier) library.

== Changelog ==

= 11.0.9 =

* Overhaul of event pricing and coupon discounts
* Improvements to the RSVP Report, including grouping of registrations by party (host + guests)
* Better support for adding / editing records from the RSVP Report screen

= 11.0.8 =

* Improved support for the WordPress native import / export tools (WXR file method)
* Prevent event templates, forms, and other internal post types from being displayed in search results
* Support for dynamic menu items. In the site editor, you can add class name "rsvpmaker_menu_dropdown" to menu item with a submenu specified to have a future events listing added to the submenu. Or use "rsvpmaker_menu_dropdown rsvmpaker_menu_type_featured-event" to get a listing of events tagged with the featured-event event type (substitute any other event type for 'featured-event')

= 11.0.7 =

* Fixed bug with regenerating default email template
* Option for sending Postmark heavy message volume alerts to a different email (other than admin)

= 11.0.5 =

* More responsive email CSS for columns (fluid 2 columns grid template)
* Option to set timezone in event template
* Removed filters wp_theme_json_data_user and wp_theme_json_data_theme

= 11.0.4 =

* Fixed Settings screen for Mailchimp Default List
* Changed name of options page that includes Postmark Settings

= 11.0.3 =

* RSVPMaker Date Element block for fine-grained control over placement / formatting of date, calendar icons, timezone conversion button
* More consistent use of the RSVPMaker Loop Blocks collection across templates and loop variations

= 11.0.2 =

* Fix for issue with html entities in email

= 10.9.7 =

* Block transform from excerpt to RSVPMaker Loop Blocks block (contains templated elements as InnerBlocks)

= 10.9.5 =

* Block transform from RSVPMaker Upcoming to Query Loop variation.

= 10.9.4 =

* Refinements to Query Loop block support
* Remove deprecated PHP code for UTF-8 encoding

= 10.9.1 =

* Custom past / future and exclude post type controls for the Query Block variations

= 10.9 =

* Rendering of RSVPMaker Upcoming block within the editor

= 10.8.8 =

* Tweaks to RSVPMaker Query block variation
* Prevent rsvpmaker_where filter on single event posts to allow display of past events

= 10.8.7 =

* RSVPMaker Query Loop block variation added: sets default formatting for event listings displayed using the Query Loop block
* New RSVPMaker Calendar block for displaying the calendar grid
* Event sort order corrected to work with the standard Query Loop block, not just custom RSVPMaker Upcoming blocknpm

= 10.8.6 =

* Update Stripe library, fix conflict with PHP 8.x
* Fixes to settings screen for Stripe
* Fixes to the settings screens for email and mailing list functions

= 10.8.4 =

* Fix for encoding the event venue in ical link / email attachment and Google Calendar links

= 10.8.1 to 10.8.3 =

* Updated database routines for events. Better compatability with wpdb apis.

= 10.8 =

* Disabling patterns intended for use in email, which were causing react validation errors in editor / site editor

= 10.7.9 =

* Fixes to scheduled email, newsletter popup functions

= 10.7.8 =

* Misc cleanup. Removal of feature for hiding page menus.

= 10.7.7 =

* Clearer event date display including weekday count (1st Monday, 2nd Monday ...) on dashboard event listing and template page.

= 10.7.6 =

* Always create a new copy of the RSVP form and confirmation before allowing it to be customized per event, whether inherited from the default settings or from a template. The goal is to reduce opportunities for confusion, such as altering the default form or message by mistake.

= 10.7.5 =

* Remove dependency on dbDelta utility

= 10.7 =

* rsvpmaker/upcoming block uses inline styles to avoid theme conflicts. Item colors can be specified within editor.
* Began migrating blocks to use Create Block vs Create Guten Block

= 10.6.9 =

* Better management of default versus custom forms

= 10.6.6 and 10.6.7 =

* Responding to Patchstack alerts

= 10.6.5 =

* Email scheduling / cancel emails

= 10.6.3 =

* Links to email design templates in the email editor sidebar.

= 10.6.2 =

* Postmark Email Log now shows monthly breakdown of emails sent, sorted by date and site (for multisite) or sorted by volume

= 10.6 =

* Fix calendar display issues (style conflicts with themes)

= 10.5.8 =

* Fix for setting event deadlines
* Fix Create / Update modal and API code glitch producing duplicates
* Display template for an event on post listing screen and DateTime UI in editor, with edit link

= 10.5.7 =

* Event title and date/time included in confirmation / reminder emails by default
* Confirmation emails on by default unless you turn off per-event

= 10.5.5 =

* Addresses reported SQL injection vulnerability within admin functions

= 10.5.3 =

* Improved saving of event variables from editor screen using React Query

= 10.5.1 =

* RSVP Button can now be customized in the editor

= 10.4.9 =

* Better formatting of headline and Read More button for posts and events imported into an email.

= 10.4.5 =

* Correct when end time set earlier than start.

= 10.4.4 =

* Enable Patterns inserter to add email newsletter _rsvp_confirmation_include_event
* Improvements to newsletter builder

= 10.4.3 =

* Remove duplicates on Cleanup screen
* Fix for get_events_by_template

= 10.4 =

* Correct end time in projected dates based on a template.

= 10.3.9 =

* Remove obsolete confirmation / reminder links

= 10.3.7 =

* Fix timezone display bug

= 10.3.6 =

* Newsletter Builder screen - pull content from multiple blog posts and events

= 10.3.5 =

* Mailer creates static version of email image.

= 10.3.4 =

* Improved copy to email for events

= 10.3.3 =

* Prevent theme styles and global styles from being added to the editor for email content.

= 10.3.2 =

* Fix "show in my timezone" feature

= 10.3.1 =

* Make RSVP Email display within the editor more consistent with email template (as distinct from web theme)

= 10.2.8 =

* Improved YouTube embed to email preview
* Admin bar option to turn rsvpemail post into a blog post

= 10.2.5 =

* Cleaner implementation of the email forwarding system for Postmark

= 10.2.4 =

* Fix date display and quick edit issues

= 10.2.3 =

* Better error checking for React Query
* Fix glitch with event submission

= 10.2.2 =

* Create/Update dialog appears inside the editor when a template is saved 
* Improvements to pricing screen

= 10.2.1 =

* Fix price display PHP 8.1

= 10.1.8 =

* Fix quick edit

= 10.1.7 =

* Fix price display glitch

= 10.1.4 =

* Updated uninstall script

= 10.1.2 =

* Correct defaults for new events

= 10.1.1 =

* Fix Javascript bugs

= 10.1 =

* Replaces the old RSVP / Event Options page in favor of a dialog box that displays within the editor to show options that don't fit within a sidebar. Options such as pricing can now be managed within the editor.
* Initial setup page for new event posts also goes away.
* Fixes a bug with PayPal credential setup on the admin screen.
* Removes vestigages with legacy data model for event dates, timezones, and date / time display

= 10.0.1 =

* Tweaks to form editor

= 10.0 =

* New RSVP form editor

= 9.9.9 =

* Postmark settings fix
* rsvpmaker_form post type

= 9.9.8 =

* Better compatibility with PHP 8.0 -- 8.1 seems to cause problems with core WP functions, not just RSVPMaker. Tested with 8.0.22

= 9.9.4 =

* Fixes for email functions

= 9.9.3 =

* Allow email recipients to change email as alternative to unsubscribe (built in list)
* Fix ?rmail=1 query parameter output on email 
* Clean up unnecessary debug logging

= 9.9.2 =

* Added a check for holidays that may overlap with a recurring event schedule. Administrators can customize the list of holidays observed.
* Fixed an issue with HTML email centering not working on Outlook.
* Also corrected an issue with email forwarding functions (more specific pattern matching).

= 9.9.1 =

* Removed function for automatically addding single-rsvpmaker block template (causing more problems than it solved)

= 9.8.9 =

* Better event display on archive pages
* PayPal code fix

= 9.8.8 =

* Option to set pricing on the event setup screen.
* Screenshot showing where to find configuration options from within the editor.

= 9.8.6 =

* Use standard DateTime component for setting event end time.
* Better navigation to event payment options.
* Keep admin bar visible in editor for RSVP documents - based on [Admin Bar in Fullscreen Mode by srikat](https://wordpress.org/plugins/admin-bar-in-fullscreen-mode/)

= 9.8.5 =

* Improved settings screen for RSVPMaker Mailer, separating some settings that used to be tied to MailChimp.
* Added a link from email preview screen to settings screen (displayed for admins)
* Improved email preview, showing substitution of template codes.

= 9.8.2 =

* Changed email type label to RSVP Email Newsletters and Notifications
* Added more instructive notification to be the editor screen for emails

= 9.8 =

* Improved notifications in editor for event template and email documents

= 9.7.7 =

* Corrected handling of currencies other than USD for PayPal payments.
* Added currency as an option that can be set for individual events, or individual Stripe or PayPal payment blocks, separate from the choice on the RSVPMaker Settings screen.
* Per-event currency code choice can be set from the RSVP Options screen (not available within the editor sidebar yet)

= 9.7.6 =

* Removed a caching function that was causing more problems than it solved.

= 9.7.5 =

* Fix bugs associated with memory problems, other mysterious issues

= 9.7.3 =

* Removed get_post_metadata filter that was causing memory problems / editor errors

= 9.7.2 =

* Performance tuning
* Changes to Postmark log screen

= 9.7 =

* Corrected an issue with duplicate email sends.

= 9.6.7 =

* Integrated Postmark options from beta test into main plugin.
* Integrated "Join mailing list" popup with configuration options for text, colors, and CSS properties.

= 9.6.6 =

* RSVP Mailer refinements

= 9.6.5 =

* Refined functions for creating RSVP Mailer emails based on post excerpts
* Tweaked some anti-form spam code.

= 9.6.4 =

* Fixing output of registrant and guest details onscreen and in notification / confirmation emails

= 9.6.1 =

* Improvements to email scheduling

= 9.6 =

* Updated RSVP Email Embed Post or Post List block for including one or more blog post excerpts in a message.

= 9.5.9 =

* Fix for YouTube embeds in email

= 9.5.8 =

* Neater reporting of opens and links for Postmark sends
* Checkbox option to add an email signup page

= 9.5.6 =

* Improvements to email list signup form, admin utility
* Confirmation for email list signup
* Prompt to confirm sending to more than 100 recipients
* For multisite, option to restrict sending to more than 100 recipients to specific sites
* Better detection / prevention of duplicate messages
* Refinements to Postmark integration

= 9.5.5 =

* Formatting options for email background images.

= 9.5.2 =

* Add background image picker along with color pickers to email body block.

= 9.5.1 =

* RSVP Mail improvements
* Core features to support [RSVPMaker Mailer for Postmark](https://rsvpmaker/rsvpmaker-for-postmark) extension for high volume email sending

= 9.4.9 =

* Another tweak to mail queue

= 9.4.8 =

* Rewrote sql for mail queue, avoid get_post_meta glitches.

= 9.4.7 =

* Added rsvpmailer_mail filter of $mail array within rsvpmailer function

= 9.4.5 =

* Fix check for user email notification rules

= 9.4.4 =

* Separated out event templates as a separate rsvpmaker_template post type

= 9.4.2 =

* More consistent presentation of knowledge base articles within admin screens
* Selective inclusion of the code for displaying tabbed UI

= 9.4.1 =

* More mailer tweaks

= 9.4 =

* Cleanup of RSVP Mailer screens.

= 9.3.9 =

* ColorPalette component added to email wrapper blocks

= 9.3.8 =

* Multiple improvements to the RSVP Mailer utility.
* Improvements to inline CSS for email, including substitutions for classes like .has-color-black and .has-background-color-black. For themes with a themes.json file, the theme color palette is imported.
* Option for users to register their own simple CSS substitution like .my-custom-class{background-image:linear-gradient(red,yellow);padding-bottom:5px} - limited to single class / rule pairs
* Partial support in email for the core/columns block - 2 columns only, 3rd or 4th column will wrap to below columns 1 and 2
* Better YouTube preview image with play button overlay for email
* Email List Signup block for use as a standalone mailing list utility. Built in email functions have the advantage of being able to send to just those who have or have not RSVP'ed for event. 
* For large lists, use the integration with MailChimp or use in combination with plugins for integration with Sendgrid and other high-capacity email service with better handling of spam and email bounce issues.

= 9.3.7 =

* Decoding of UTF-8 encoded subject lines in incoming email for group email discussion list functions
* Refinements to email content wrapper blocks
* Latest post block designed for use in email

= 9.3.6 =

* Addition of ability to manage a guest email list
* Filtering for RSVP / no RSVP now works with all local email broadcasts (but not with Mailchimp)
* Add recipient name when sending email (supposed to improve deliverability) if available from RSVP list, member list, or guest list

= 9.3.5 =

* Updated email templates that can be formatted in the editor.
* When emailing website members (with user accounts), you can selectively email those who either have or have not RSVP'ed (registered) for a specified event. This does not work with the MailChimp interation.

= 9.3.2 =

* Security updates
* Added rsvp_to_array filter

= 9.3.1 =

* Updates related to email functions.

= 9.3 =

* Bug fix. Improper output of email stylesheet

= 9.2.9 =

* Tested with WordPress 6.0.
* Improvements to PayPal integration
* First guest blank no longer displayed until user clicks "Add more guests." Seems to avoid some confusion where people were adding their own name twice.

= 9.2.8 =

* Fix for adding date, other variables to email footer on confirmation messages
* Option to number events in a series defined by an event template

= 9.2.6 and 9.2.7 =

* Code security fixes

= 9.2.5 =

* Fix for issue where transients used to prevent duplicate emails accumulated in the options table

= 9.2.4 =

* Improvement to the convert timezone function, in the context of schedule display.

= 9.2.2 =

* Bug fix, projected time for templates

= 9.2.1 =

* When you activate a full-site editing block theme (or activate RSVPMaker on a site that has it active), the plugin will now create a template based on the single post template tweaked to remove the post date (which can be confusing when emphasized over the event date) and post author fields.

= 9.2 =

* Fixed issues with RSVP Mail utility conflicts with lazy loading of images (not useful for email)
* Cleaned up email preview display
* Tweak for compatibility with block-based themes introduced with WordPress 5.9.

= 9.1.9 =

* Fix to CSS for Stripe widgets
* Ensuring initiation of table that tracks payments

= 9.1.8 =

* Fix problem with templates set to automatically add dates.

= 9.1.7 =

* Making it easier to see if "Auto add dates" is active for a template.

= 9.1.6 =

* Added get_rsvpmaker_timestamp utility function
* Tweak to rsvpmaker_where filter, correcting for timezone

= 9.1.4 =

* Update event row whenever event post is updated

= 9.1.3 =

* Auto add events checkbox for templates in Gutenberg editor
* Email notification to admin when events are automatically added

= 9.1.1 =

* Form spam countermeasures 

= 9.1 =

* Fix for autornew templates

= 9.0.7 =

* Additional checks to sync rsvp_dates meta tag with rsvpmaker_event table

= 9.0.6 =

* Fix RSVPMaker Submission` block for front end (allow anonymous users to submit event draft event posts for approval)

= 9.0.5 =

* Fix to agenda notification templates setup screen.

= 9.0.4 =

* Tweaks to some payment functions
* rsvpmaker_verify_nonce() for simplified verification of form submissions

= 9.0.3 =

* Simplified date entry for new events, quick event setup screen, front end submission

= 9.0.2 =

* bug fix, consistency check function post titles

= 9.0.1 =

* Selectively copy metadata when applying a template to an existing event

= 8.9.8 =

* More consistency between PayPal and Stripe integrations

= 8.9.4 =

* Fine tune date table routines

= 8.9.3 =

* Check that event timestamps, deadlines are set properly.

= 8.9.2 =

* Test whether PayPal and Stripe keys are valid

= 8.9.1 =

* Updates to PayPal integration
* Option to show both Stripe and PayPal buttons - toggle on in Stripe block
* Separate PayPal payment block

= 8.9 =

* Updated translation POT file
* Updated French translation with content from https://translate.wordpress.org/projects/wp-plugins/rsvpmaker

= 8.8.9 =

* Caching of event attributes using transient

= 8.8.6 =

* Updates to email queue functions

= 8.8.5 =

* Utility for checking database tables added to Cleanup RSVPMaker (under Tools)

= 8.8.3 =

* Simplified email template for group email function.

= 8.8.1 =

* Fix for MailChimp integration

= 8.7.9 =

* Delayed send option for RSVP Mail Utility

* Turkish translation care of Göksel UÇAK

= 8.7.4 =

* Security fix

= 8.7.2 =

* Fix to calendar navigation 

= 8.7.1 =

* Switched from strftime to wp_date to take advantage of WordPress internationalization functions for dates

= 8.7 =

* Consistency check after event dates are updated
* Timezone conversion API tweaked to prevent inappropriate display when two region/city zones are effectively same timezone

= 8.6.9 =

* Email reminder to do Create/Update after template update

= 8.6.8 =

* If timezone conversions are active, they're now displayed automatically if the user's timezone is diff from event local time
* User can switch to display timezones other than ones reported by JavaScript

= 8.6.7 =

* Refinements to timezone adjustment functions
* Fine tuning rsvpmaker/next-events

= 8.6.5 =

* rsvpmaker/next-events block
* Tweaks to projected events function (timezone adjustment)

= 8.6.3 =

* When viewing a specific post status like draft or trashed, show all events, not just future.

= 8.6.1 =

* Fix csv export issue
* Prompt in editor for individual event in series (edit template instead?)

= 8.6 =

* Check post status on autorenew templates
* Updates to RSVPMaker cleanup tool

= 8.5.8 =

* Using input type="time" on setup form

= 8.5.7 =

* Correct issue with rsvpmaker/schedule block

= 8.5.6 =

* More efficient date and time formatting

= 8.5.5 =

* Better handling of the events database upgrade, particularly for large databases
* Better fixing of dates with missing enddate.

= 8.5.2 =

* For MailPoet integration, custom:rsvpmaker_youtube shortcode for embedding preview image for a video

= 8.5.1 =

* New API endpoint wp-json/rsvpmaker/v1/events_with_timezone for future events with timezone information

= 8.4.8 =

* Updates to MailPoet and Stripe integration features

= 8.4.5 =

* Improved shortcodes display for MailPoet

= 8.4.4 =

* MailPoet shortcodes widget displayed in the notices header with show/hide toggle

= 8.4.3 =

* Change name space on included MailChimp library to avoid conflicts with other plugins

= 8.4.2 =

* MailPoet integration

= 8.4 =

* Another adjustment to HTML output, specifically for dates and calendar icons at the top of each block.
* Better way of checking whether rsvpmaker_event table needs to be updated

= 8.3.8 =

* Fix to group email functions.

= 8.3.7 =

* Fix for HTML output error

= 8.3.5 =

* Fixes related to updated custom table for event dates

= 8.3.2 =

* Bot account added to RSVPMailer Utilities
* Bug fix SQL reference missing $wpdb

= 8.3.1 =

* Tweaks for Toastmasters integration
* Fixed bug in email template setup
* Moved server side block registration to rsvpmaker.php (was in gutenberg source folder)

= 8.2.8 =

* Alternative RSVP Forms, such as variant for webinars
* Restored calendar button on event setup form
* CSS fix for forms to display correctly with the Twenty Twenty One theme

= 8.2.7 =

* Better cross-browser support for countdown block

= 8.2.6 =

* Tweaked countdown timer block to allow you to use next event (or next event with rsvp on) instead of specifying event ID. Added options for what to display after the countdown expires.
* Fix for glitch in Quick Edit options for setting event dates.

= 8.2.4 =

* New Countdown Timer block. Can be used within an event or on another page or post where you specify the event you want to count down to.
* Fix issue with Stripe code and minification

= 8.2.1 =

* Fixing confirmation / reminder links on RSVP Options page

= 8.1.9 =

* New template UI more consistent with datetime UI for new events
* Fix timezone convert link for email
* Limit quick edit UI for setting dates to events (not templates or special documents)

= 8.1.8 =

* Multiple event entry screen lets you add blanks for additional events (up to 50).
* Updated video demo included in readme.txt

= 8.1.7 =

* Further refinements to timezone conversion and new date/time editing UI

= 8.1.6 =

* Bug fix, new template creation

= 8.1.5 =

* Additional validation for dates. Removed reference to JQuery UI datepicker

= 8.1.4 =

* New date setup UI allows you to enter dates in either free text or SQL format
* Quickly Setup Multiple Event Posts screen also uses this format. Good for setting up a series of events on the same day (for example, sessions within a conference) or on subsequent days.

= 8.1.1 =

* Timezone conversion now applied to blocks and cells in tables with tz-convert class (hyphen not underscore)
* For tables, use tz-table1, tz-table2, tz-table3 class on table to target 1st, 2nd or 3rd column
* Gutenberg fix to make rsvpmaker-type taxonomy display properly
* RSVPMaker Schedule block now allows you to specify that Show in my timezone button should be displayed
* RSVPMaker Setup screen now allows you to quickly specify a series of events without a template. Enter a few paragraphs of text and edit further later. Event Type and basic parameters can be specified.

= 8.1.0 =

* Show in my timezone button will now convert times in paragaphs, headings, or other blocks tagged with "tz_convert"
* Also applied to cells of a table where the table is tagged with the class "tz_convert"

= 8.0.9 =

* Updated way of handling multi-date events, works better with Gutenberg editor and other updated code
* Updates to iCal export and add to Google for multi-date events

= 8.0.8 =

* Don't run inliner on emails with no html

= 8.0.7 =

* AJAX fix for RSVP Options page
* Correct end time for iCal calendar items

= 8.0.6 =

* Added timezone / calendar icons back to setup page
* Option to revert to default confirmation message or default form

= 8.0.5 =

* Fix for white screen issue when creating new events or templates (theme or plugin conflict?)

= 8.0.2 =

* Dropping support for multi-date events. Use templates instead.

= 8.0.1 =

* Workaround for problem with event end time gutenberg component
* Bug fix for template listings

= 7.9.9 =

* Adding defaults to register_meta
* Check for empty templates (no schedule set)

= 7.9.7 =

* Bug fix, rsvp link

= 7.9.6 =

* More consistent handling of email placeholder codes, whether in a notification template or the body of the confirmation message.

= 7.9.5 =

* Cleanup of data model for templates
* Import templates between websites

= 7.9.4 =

* Allow RSVP deadline to extend past start time (if you want to welcome people to register after event starts)
* Fix error in event pricing code (incorrect sanitization)

= 7.9.3 =

* Fix inappropriate data sanitization on Settings screen

= 7.9.2 =

* Tweaks to Group Email mailing list functions

= 7.9.1 =

* Tweaks to RSVP Report, Stipe payment page, Stripe Report

= 7.9 =

* Correcting error in email template setup

= 7.8.9 =

* Fix error with $email_context global.

= 7.8.8 =

* Fix error in save_post action

= 7.8.4 =

* Fixing plugins_url() syntax to use __FILE__

= 7.8.3 =

* Additional security fixes required by wordpress.org plugin team
* MailChimp API functions more clearly bundled as an external library https://github.com/DrewM/mailchimp-api
* Stripe report screen now allows you to fetch records of past transactions, including fees and refunds

= 7.8.2 =

* Fixed issue with email queue initialization
* Addressed potential SQL injection vulnerability flagged by GitHub

= 7.8.1 =

* Fixed issue with auto-renew from templates formatting dates incorrectly
* Improved routine for detecting and fixing garbled dates

= 7.8 =

= 7.7.9 =

* Filter events listing by author

= 7.7.8 =

* New import/export function for transferring events between sites.
* Updated PHPMailer init code in anticipation of WordPress 5.5 change

= 7.7.7 =

* Copy to Email option under New RSVP Email, plus Embed in Email for rsvpmaker events

= 7.7.5 =

* ReCaptcha anti-spam enabled on RSVPMaker Event Submission form (if configured in Settings)

= 7.7.4 =

* Added support for limited rich text editing (bold, italic, and link) with RSVPMaker Event Submission block.

= 7.7.3 =

* RSVPMaker Event Submission block. Adds a form to allow site visitors to submit an event for approval by an editor.

= 7.6.9 =

* Fix for event end times in templates.

= 7.6.8 =

* Tweaks to admin menu options
* Full screen editing off by default for RSVPMaker and related documents.
* Option to reset confirmation and reminder messages to default

= 7.6.7 =

* Reminder and follow up messages can be set to go only to those who have paid.
* Clearer admin bar menu structure for related documents, including confirmations, reminders, and forms, as well as showing those inherited from default and template.
* Admin bar links are also shown in the Related panel of the RSVPMaker editor sidebar. Links in the Confirmation / Reminder and RSVP Form panels are also more consistent.

= 7.6.6 =

* Fixing a glitch with the auto-renew function for automatically adding event dates based on a template schedule.

= 7.6.5 =

* Refinements for payment confirmation message, RSVPMaker Schedule block for displaying a series of related events.

= 7.6.1 =

* Utility for fixing corrupted dates

= 7.6 =

* Refinements to payment setup screens
* Created separate payment confirmation message document for details that should only be disclosed after payment, such as the link to a webinar you're charging for.

= 7.5.9 =

* Better detection of duplicate entries
* Group email uses transactional template
* Date and time displayed in RSVP form.
* Future RSVP Links block

= 7.5.8 =

* Tweaks to javascript for changing views on the RSVP Events post listing
* When other plugins, including my Toastmasters extension, send email, the filter 'rsvpmailer_rule' is called with email address and type of message as arguments. Return 'permit' or 'deny' to determine whether the message will be sent. Supersedes the unsubscribed list. Meant to be a user perference for membership applications.

= 7.5.7 =

* Fix for Future Events / Past Events dropdown on posts listing screen. Previous implementation interfered with standard "Bulk Actions" behavior.

= 7.5.4 =

* Fixed error in event template code

= 7.5.3 =

* Fixed error in Gutenberg code.

= 7.5.2 =

* Bug fixes for copying template metadata, rsvpmaker_is_template function

= 7.5.1 =

* Updating POT source file for translators.

= 7.4.9 =

* Complex template schedules like first and third Monday now display properly in Gutenberg editor.

= 7.4.8 =

* Tweaks to the new event setup screen.

= 7.4.5 =

* Limit debug log (if activated) to current day and previous days (delete older files)
* Clean up WP Cron jobs on plugin deactivate

= 7.4.3 =

* Better support for attachments and embedded images in email shared through the Group Email feature. Still has an issue with messages that have both embedded images and attachments.
* Polling mailbox and sending messages now handled via WP Cron actions, interval 1 minute (may take longer) 

= 7.4.2 =

* Limited support for attachments in Group Email discussion lists.

= 7.4 =

* Email distribution lists for member-oriented sites. Any member with a user can send a message from the email address on their profile (or another whietlisted address) for distribution to other list members. See the Group Email tab under settings.

= 7.3.9 =

* Corrects a couple of issues with copying metadata from templates to individual events (when it's not appropriate).

= 7.3.8 =

* Fix for Stripe payment confirmation message

= 7.3.7 =

* Fix to calendar display (timezone issue)
* Function to lookup Stripe payments by email

= 7.3.4 =

* Fix to make REST API calls to correct site url

= 7.3.3 =

* Better control over specialized blocks like those for use in the form not being shown in the editor outside of that context.
* Fix to make the RSVP Mailer module distinguish between sending to all users and users who are members of a specific website in a multisite setup.

= 7.3.2 =

* Wrapping shortcodes and dynamic blocks in test that wp_is_json_request returns false. Was seeing unintended output on document save confusing JSON confirmation message.

= 7.2.6 =

* RSVPMaker Schedule block (also rsvpmaker_daily_schedule shortcode) for displaying an hour by hour schedule of events (for example, for a conference)

= 7.2.5 =

* Make sure Stripe submissions include name and email

= 7.2.2 =

* Fix to calendar display

= 7.2.1 =

* Stripe integration fix, better styling of form.

= 7.1.9 =

* Fixes/updates to blocks for embedding single events or elements of single events (just the button, just the form)
* Fix to confirmation message option for including the event content with the confirmation.

= 7.1.3 =

* Better compatability with Classic Editor
* Better guidance on Privacy Page setup.

= 7.1.1 =

* Correcting an error in data validation routine.

= 7.1.0 =

* Neater presentation of the essential options to be set including timezone and the creation of a calendar page.
* Tweaks to Gutenberg code.

= 7.0.6 =

* Most template schedules can now be set from the Gutenberg editor sidebar. For example, First Monday at 7 pm or Every Wednesday at noon. Exception: More complex schedules (First and Third Monday) still require exiting to the RSVP / Event Options screen.  

= 7.0.5 =

* Single event prices can now be set from Gutenberg editor sidebar. Information about additional options that can be edited in RSVP / Event Options also displayed.
* Improved display on some themes where RSVPMaker posts are displayed in the default index.php (Archive) template, including Twenty Twenty 

= 7.0.1 =

* Updated Stripe support to reflect API that supports newer European regulations for credit card processing (SCA and PSD2).
* Dropped support, at least for now, for recurring payments (subscriptions) when Stripe is used as a standalone payment button.

= 7.0.0 =

* Fixed error in register_meta function. Updated POT file for translations.

= 6.6.4 =

* Added PHP version check before loading Emogrifier CSS inliner library, which relies on features of PHP 7.1

= 6.6.2 =

* Fix to the option to set relative "days before" times for deadline or registration start in the template and have them calculated and copied to individual event posts.
* Yet more email template tweaks.

= 6.6.1 =

* More email template tweaks

= 6.6.0 =

* Tweaks to email message templating / styling.

= 6.5.8 =

* Improved Email Template editor. New default template for transactional messages.
* Inlining of CSS code in HTML email documents, based on the [Emogrifier](https://github.com/MyIntervals/emogrifier) library.

= 6.5.6 =

* Tweaks to timing functions.

= 6.5.5 =

* More sidebar improvements. Now divided into panels, with links to confirmation message and RSVP form posts

= 6.5.4 =

* Better Gutenberg sidebar access to setting start and end time and other basic variables. Multi-date events and event pricing still must be handled through RSVP / Event Options screen.

= 6.5.2 =

* More variables can now be edited through the Gutenberg sidebar, rather than the RSVP / Event Options screen.

= 6.5 =

* Event end time now displayed in Gutenberg interface (still have to click through to RSVP / Event Options screen to change)
* More compact display of admin bar options on front end (shown as submenu under Edit RSVP Event)
* Portugese translation (thanks to Letrajato)

= 6.4.4 =

* Fix for email unsubscribes

= 6.4.3 =

* Eliminating the use of create_function (deprecated as of PHP 7.2+) in widget definitions

= 6.4.2 =

* Introducing widget and editor block for dynamically loading lists of events from RSVPMaker API endpoints, using JavaScript and JSON. Can be used to fetch event listings from other sites or to perform better in combination with caching solutions.

= 6.4.1 =

* Fix for issue where RSVP Form did not reset properly if the document was deleted.

= 6.4 =

* Fix for scheduled email (featured message)
* Fix for default options not being applied correctly on event setup (Add New) screen
* Option to change event post title when adding events from a template

= 6.3.9 =

* Updated support for PayPal (newer REST API) [blog post](https://rsvpmaker.com/blog/2019/09/09/paypal-rest/)

= 6.3.8 =

* UI tweaks to make it more obvious how to create an event based on a template.
* Fix for an intermittent glitch that prevented Gutenberg from displaying the proper error message after saving an event post.

= 6.3.7 =

* Adding RSVPMaker Dateblock block - changes placement of date/time block. Same as rsvpdateblock shortcode
* Adding Placeholder block. Add a note that only appears in the editor, not on the front end. Example: "event description goes here" text for an event template.

= 6.3.6 =

* In a template, option to set deadline and registration start as x days before rather than a specific date
* When adding event posts from a template, you now have the option to add them as drafts rather than published posts
* Tweak to function that allows you to jump ahead to a specific date

= 6.3.4 =

* rsvpdateblock shortcode can be inserted in the body of a post, altering where the date and time information appears.
* Fix to preserve event end time from template to screen for creating an individual event.
* Additional span elements wrapped around the time display within datetime block. CSS can be used to change display of span.time or span.end_time

= 6.3.4 =

* Fixes to event_listing shortcode
* Addition of an RSVPMaker event listing Gutenberg block. For showing a listing of just headlines, dates and links to upcoming events.

= 6.3.1 =

* Corrected an issue with editing of the RSVP form. Under some circumstances, WordPress would strip HTML form field tags from the Gutenberg blocks, so now those are formatted on the server.
* Added Stripe Form Wrapper Gutenberg component. Can be used for simple order forms for payments independent of the RSVP Form.

= 6.2.6 =

* Changed how event end times are set, more consistent with how other calendaring programs do it.

= 6.2.5 =

* If timezone is not set, suggest one based on JavaScript detection of the user's timezone. Uses https://github.com/iansinnott/jstz
* Bug fix related to multi-day events.

= 6.2.4 =

* Updated German translation files, thanks to Markus König

= 6.2.3 =

* Fixing a glitch that caused duplicate posts to be created when a template is set to automatically generate new events according to the template schedule.
* Fixed the function for loading blog posts into the RSVP Email editor.

= 6.2.2 =

* Tweaks to the scheduled email function. You can now schedule broadcasts relative to the date of the event or for an arbitrary date and time.

= 6.2.1 =

* Fixes for broken features: links to edit confirmation message, adding RSVP records on the back end (from RSVP Report)

= 6.1.9 =

* Change start time based on template
* Series of updates in 6.1.6-8 related to confirmation / reminder messages
* Bug fix for stripe charges in currencies other than usd

= 6.1.5 =

* Prevent WordPress from filtering out form tags in and RSVPMaker form posts (was happening for non-admins in Gutenberg version)
* Make standard RSVPMaker Upcoming block / shortcode work in email.

= 6.1.4 =

* UX changes. Tweaks to admin bar menu, other navigation labels / methods.
* Fix for date / time display on new events (using defaults from settings screen, not defaulting to midnight)
* Fix to the mailer utility, import of an event listing or blog post (body content wasn't being imported correctly)

= 6.1.1 =

* RSVPMaker Setup screen now gives more context on previously published events and works for adding a single event based on a template.
* Notice after template is saved (in Gutenberg) now gives 2 options: New Event from template or Create / Update events.

= 6.1.0 =

* Trying to fix an issue with confirmation messages / payment buttons not showing up for logged in users.
* Assigning slug to new draft posts.

= 6.0.7 =

Backwards compatibility for sites not running Gutenberg, specifically for confirmation messages.

= 6.0.6 =

* Important bug fix. Options screen was not working properly

= 6.0.5 =

* Added Edit / Customize RSVP Form to admin bar, where relevant
* More bug fixes (also 6.0.1 to 6.0.4)

= 6.0 =

* New RSVP form, customizable using the Gutenberg editor.
* Further refinements to setup for confirmation / reminder messages and navigation back to parent post.
* RSVP reports can now be run on past events that have been deleted as posts. Event titles and dates are tracked in a separate custom DB table.
* RSVPMaker Cleanup utility added under tools to make it easier to delete old events.

= 5.9.9 =

* Caching of date lookups to improve performance
* Fix to author archive lookup query setting (to show events as well as blog posts)

= 5.9.7 =

* Tweaked data model for relationship between confirmation / reminder messages and their parent posts.
* Updated the Event Templates screen, particularly the features for applying a template to an existing event and for creating a template based on an existing event.

= 5.9.6 =

* Fix for DB performance issues
* Updates to event reminder messages functions

= 5.9.5 =

* More template options shown on Event Setup screen. Trying to make the choice between creating an individual event and using a template clearer from the user interface.
* Excluding templates and special documents (such as confirmation messages) from search.

= 5.9.4 =

* Yet a better implementation of the custom Gutenberg notification for RSVPMaker templates (no longer displayed more than once if user saves template again)
* Bug fix related to date display in email newsletter output.

= 5.9.2 =

* Better implementation of the custom Gutenberg notification for RSVPMaker templates.

= 5.9.1 =

* Notice on Gutenberg editing screen prompting user editing template to create / update individual events
* Tweak to shortcodes such as rsvpmaker_next

= 5.8.9 =

* Tweaks to Event Options screen, routine for automatically adding a Calendar page (uses block format for WordPress 5.0)

= 5.8.8 =

* After registration, form is no longer displayed on the confirmation page unless the user clicks Update RSVP link (removes a source of confusion)
* Adding API endpoints /wp-json/rsvpmaker/v1/future /wp-json/rsvpmaker/v1/types /wp-json/rsvpmaker/vi/type/slug-goes-here
* Fixed display of event types in RSVPMaker Upcoming Events block

= 5.8.6 =

* Fix to coupon codes
* Multiple Admissions option for pricing (Example: reserving a table of 6, rather than counting host/guests individually)

= 5.8.5 =

* Limited Time Content block now allows you to set expired content to automatically be deleted from a post. By default, expired content is hidden (you can go back and change it or remove it manually). If you have WordPress set to save revisions, deleted content will be preserved as part of a past revision.

= 5.8.4 =

* Support for coupon codes (discount pricing for events)
* Option to send a payment reminders. If someone registers but does not pay, they will get an email reminder after 30 minutes.

= 5.8.3 =

* Added the Limited Time Content block (a wrapper for content with a start time and/or end time specified for how long it should be displayed)

= 5.8.2 =

Improvements to the Stripe payment functions

= 5.8 =

* Update Template Based on Event option added to admin bar - adjust template based on changes to the latest event in a series, rather than the other way around.
* If an event is based on a template, Edit Template appears under the main Edit link on the admin bar.
* Added action - do_action('rsvpmaker_stripe_payment',$vars) - other plugins can detect when a successful Stripe online payment transaction is logged.

= 5.7.9 =

* Native support for Stripe online payments (previously supported through an integration with WP Simple Pay)
* Stripe Charge block for Gutenberg for processing other sorts of charges, such as dues payments or consulting fees, in addition to event payments.

= 5.7.6 =

* Fix for some issues with items being added twice.
* RSVP On radio buttons now displayed in Gutenberg sidebar.

= 5.7.5 =

* Bug fix, positioning of navigation links at top and/or bottom of calendar.

= 5.7.4 =

* The guest blanks at the bottom of the RSVP form can now be changed to use another label other than "Guest" and alter the text of the "Add to guests" link. This is not supported by the visual form builder, but you can alter the rsvpguests shortcode with attributes such as [rsvpguests label="Athlete" addmore="Add more athletes"]
* Send RSVP Email link in admin bar and editor listing for posts and RSVPMaker events.

= 5.7.2 =

* Option to make all notification emails come from the same address (reply to header can be different) to avoid issues with spam filters flagging messages as "spoofed."
* Code cleanup. Most action calls moved to a separate file. Utilities such as lookups of past and future events moved to rsvpmaker-util.php.

= 5.7.0 =

* WordPress 5.0 / Gutenberg tweaks

= 5.6.8 =

* If you set up reminders associated with an event template, as opposed to an individual event, RSVPMaker will now automatically create a reminders for the next scheduled event in the series and add WordPress cron events for the specified number of hours ahead.

= 5.6.6 =

* Fix for recording units and price when multiple prices offered (for example, adult versus child tickets)

= 5.6.5 =

* Adjusting for a breaking change in the latest Gutenberg release (getSettings function removed from the date component)

= 5.6.4 =

* Fix for potential SQL injection security issue

= 5.6.3 =

* Tested for WordPress 5.0 / Gutenberg
* Fixed bug with scheduled email broadcasts (featured post for blog roundups)
* Fixed bug with calendar navigation display of current month
* Option to set different timezone for individual event

= 5.6.2 =

* Option to limit the size of the export file. You can now also include pages, posts and other WordPress content, in addition to RSVPMaker events.

= 5.6.1 =

* Export RSVPMaker screen added under Tools, plus action hook to clean up imported RSVPMaker event posts.

= 5.5.7 =

* Add Email Options / fix Send Invites links to RSVP Events listing
* Tweaked the basic form builder for the RSVP form to allow you to set max guests per party

= 5.5.6 =

Fix to feature for adding an editor's note to a scheduled email.

= 5.5.5 =

Fix for RSVP count display.

= 5.5.1 =

* Fixes to make RSVPMaker "special" pages (landing pages, locations) work with Gutenberg turned on.
* RSVPMaker count of people signed up loaded into event listing via AJAX (prevents issues with caching)

= 5.5 =

Restoring saved locations feature for use with the Classic Editor. Gutenberg version to follow.

= 5.4.9 =

* Eliminating reload of page when event date/time is edited in the Gutenberg sidebar. Most other date / RSVP options set on a separate page ... at least until I can get features working more reliably on Javascript powered screens.

= 5.4.7 =

* Fix for proper multipart alternative coding on HTML email.

= 5.4.6 =

* Limiting output of JS/CSS for admin screens to screens specific to RSVPMaker as a way of avoiding conflicts with other plugins.

= 5.4.2 =

* Further improvements to Gutenberg support. Event Options and RSVP Mailer scheduled email options broken out onto separate screens. Only essential event setting options shown in the Gutenberg editor.

= 5.4.1 =

* Refinements to RSVPMaker support for the Gutenberg editor.
* RSVP Mailer tool now also works with Gutenberg.

= 5.4 =

* With Gutenberg enabled for RSVPMaker event posts, the meta box at the bottom of the page goes away (because it tends to cause compatability issues) and basic date setting options are instead shown on the sidebar. You can click through to a separate screen for more detailed options, such as customizing the RSVP form or setting event prices. Option to use Gutenberg with RSVPMaker posts is still turned off by default, even if you have the Gutenberg plugin installed or are on WordPress 5.0.

= 5.3.9 =

* To improve compatability with Gutenberg, moved RSVPMaker event registration options from the meta box at the bottom of each event post to a separate screen.
* Improved sort options for the RSVP Events listing in admin.

= 5.3.7 =

* Adding a Gutenberg-compatible prompt to "Create/update events from template" to be displayed when a post is updated, linking to the template projected dates page. The old code for immediately displaying checkboxes for updating future events no longer works because it relied on features of the classic WordPress editor.
* Added links to the Gutenberg document status section of the sidebar that make it easier to navigate to the Event Options panel (for setting dates, RSVP options), from an event to the template it is based on, or from a template to the projected dates screen.
* Fixed Event Types (RSVPMaker equivalent of post Categories taxonomy) to be displayed in Gutenberg

= 5.3.4 =

* Preliminary support for Gutenberg, the new WordPress editor, with blocks for inserting events listings: RSVPMaker Upcoming Events for display of an events listing and/or calendar and RSVPMaker Event Embed for inserting a single event (for example in a blog post or a landing page).
* Checkbox on the RSVPMaker settings page for enabling the Gutenberg editor for composing new RSVPMaker posts.

= 5.3.1 =

* Option to add a privacy consent checkbox to your RSVP forms (recommended for GDPR compliance)

= 5.3 =

* Integration with the new Export Personal Data and Erase Personal Data tools WordPress 4.9.6 added to simplify compliance with privacy regulations such as the EU's General Data Protection Regulation (GDPR)

= 5.2.1 =

* Calendar view now shows events from earlier in the month. In the default styling, past dates are shown with a gray background - to override, change styling for #cpcalendar td.past
* Unsubscribed emails list now allows you to remove email addresses from the unsubscribed list.

= 5.1.8 =

* Added a server-side check on RSVPs exceeding the maximum count allowed (helps counter some form spam issues)

= 5.1.7 =

* Added setting for RSVP form title
* Bug fix related to limited time pricing ("early bird" scenario)

= 5.1.6 =

* Tweak to tabbed js and css for settings screen.

= 5.1.4 =

* Apply Template / Switch Template options added on editing screen. Makes it easier to apply a new template to the current post.

= 5.1 =

* Save locations and add saved locations to an event post
* Duplicate reminders for all events associated with a given template

= 5.0 =

* Better / more consistent formatting for Show in My Timezone
* Tested for WordPress 4.9

= 4.9.8 =

* Adding support for post_type attribute in rsvpmaker_timed shortcode

= 4.9.7 =

* bug fix for rsvpmaker_next shortcode
* check to make sure function exists before calling rsvpmaker_recaptcha_output (not loaded for older PHP versions)

= 4.9.5 =

* Updates to the rsvpmaker_timed shortcode, including the addition of a post_id attribute (post_id="123" will insert headline and body of the post with the ID 123). You can also include a style attribute for CSS wrapper code such as a border, padding, and background color for the block. If no post_id is specified, the output will be all the content from the start tag to the end tag. With a post_id, no end tag is required. Include the attributes start and/or end to specify a start or end time for the display of a post, for example start="2017-11-01 07:00" end="2017-12-31 23:00" or start="November 1, 2017" end="December 31, 2017 11 pm"
* The style attribute now also works with the rsvpmaker_one shortcode. Fixed it so you can specify something like [rsvpmaker_one post_id="next" one_format="compact"] and the one_format attribute will be respected. (In the last release, specifying "next" instead of a number would always output the full post).

= 4.9.4 =

* Fixes to RSVP Mailer functions for setting up email design templates, pulling content from the blog or calendar.

= 4.9.3 =

* Better organization of the RSVPMaker settings screen, divided into tabs

= 4.9.2 =

* Option to have RSVPMaker calculate event fees, even if you haven't set up an online payment service. This "Cash or Custom" option also allows you to add a custom payment gateway.
* Added hook for custom payment methods, 'rsvpmaker_cash_or_custom' action [documentation](https://rsvpmaker.com/blog/2017/10/18/custom-payment-gateway/)

= 4.9.1 =

* Changed the way the "additional editors" function works, if activated in settings. This allows users with authoring but not editing rights to share the right to edit an RSVPMaker event or all the events based on a particular template. To make this work more reliably, the author ID # on the post is now changed when another authorized user (designated by the original author) updates the post.
* Events embedded in a post or page using the rsvpmaker_one shortcode can now be set to automatically stop displaying when the event date is past.
* Added new RSVP Mailer options for sending to all past event attendees or all who registered within a specified timeframe.

= 4.9 =

* Test for PHP version before loading code that requires namespace support (introduced in PHP 5.3). Required for ReCaptcha library.

= 4.8.9 =

* Added embed_dateblock shortcode
* Bug fix, properly removing calendar sql filter
* Bug fix, css for admin screens

= 4.8.8 =

* Event templates can now be set to let RSVPMaker automatically add dates to the end of the specified schedule
* Fixed a bug with the recording of timeslot signups (often used to enroll volunteers for specific shifts)

= 4.8.7 =

* Adding support for Google ReCaptcha by incorporating Google's PHP library

= 4.8 =

* Never show "0 signed up so far" (start at 1)
* Checkbox for pages to supress the display of page menu on the front end. Intended as a simple way of turning a full width page template into a landing page, reducing temptation for visitors to click away rather than completing a call to action such as registering for an event embedded in the page.

= 4.7.9 =

* Changed defaults, such as number of days listed on calendar page, to match most common usage.
* Added a button for inserting a single event on a page, covering variations to show full event with button or form, form only, or button only. Useful for building landing pages promoting one or more events.

= 4.7.8 =

* Fix for email lookup on multisite

= 4.7.7 =

* Fixed lookup of contact info based on email. Also checks for prior rsvps for the current event.

= 4.7.6 =

* Fixing bug in the tracking of "private data on file"

= 4.7.5 =

* Try to minimize duplicate RSVPs by searching RSVP list by email address, as the user is typing their email address into the form. On match, they are prompted to update their existing registration rather than adding a new one.
* Option to turn off email confirmations

= 4.7.4 =

* Factoring out anonymous function call (fails on older versions of PHP)

= 4.7.3 =

* Fix for bug overwriting $_GET["page"] on admin screens

= 4.7.2 =

* Improvements to clone event / create template from event functions
* Fix to prevent other plugins from modifying the mce editor on reminders editor page

= 4.7.1 =

* Fix to rsvpmaker_upcoming display so doesn't show "No events listed" when future events out of date range
* Meeting durations of 10-55 minutes now supported in dropdown; 15 minute increments after 1 hour

= 4.7 =

* Fix conflict with Jetpack

= 4.6.9 =

* Cleanup of utility functions.

= 4.6.8 =

* RSVP form fields can now be set to appear only on the guest form, not on the main RSVP form. Previously, all fields appeared on the main form and only displaying them on the guest form was optional.

= 4.6.7 =

Tweaks for compatibility with PHP 7

= 4.6.4 =

* Notification Templates screen, which appears under RSVP Mailer on the admin dashboard, lets you customize notification and confirmation messages and the information to be included in them. For example, if you want to use the word Registration rather than RSVP in your subject line, or remove the Update RSVP button from confirmation messages, you can now do so.
* Tagged a few front-end translation strings that had been missed previously.

= 4.6.3 =

* RSVP Report now lets you see details for multiple upcoming or recent events

= 4.6 =

* Tweak to More Events link

= 4.5.9 =

* Improved navigation for archive pages.

= 4.5.6 =

* Fix for volunteer slots signups.

= 4.5.5 =

* New action 'rsvp_recorded' passes and array that you can log or process with your own add_action hook
* Bug fix for changing time format

= 4.5.2 =

* Support for creating a webinar landing page for something other than YouTube Live.
* Added filter for data copied from template to individual posts during updates.

= 4.5.1 =

* Fix for copying metadata from template, plus another random bug

= 4.4.9 =

* Fix for UTF-8 encoding of non-English characters in strftime output

= 4.4.8 =

* Fix for event_listing shortcode
* Tweaks to get_future_events and get_past_events functions

= 4.4.7 =

* Change coding for date display to work in other locales (PHP strftime instead of date)

= 4.4.6 =

* Integration with WP Simple Pay Lite for Stripe (should also work with Pro version), making Stripe payment service an alternative to PayPal. Requires that your site (or at least the event page) be secured via SSL
* Fix to numbering for guest blanks

= 4.4.4 =

Tweaks to the system for scheduled reminder / follow up messages. Plus some overdue code cleanup.

= 4.4.1 =

Added option to show timezone conversion. Uses JavaScript to convert from UTC time to local time, according to the settings on the user's computer. Useful for online events such as webinars with a worldwide audience.

= 4.3.9 =

* Updated system for managing YouTube Live webinars through RSVPMaker, now automatically generates model landing page plus confirmation, reminder, and follow up messages that you can use as a starting point. When you view an event or a landing page in the editor, navigation links make it easier to navigate between the two, or to the related confirmation and reminder messages.
* Confirmation and reminder messages are now displayed in the RSVPMailer email template. If you have several templates, you can designate which one should be used for these transactional messages.
* Registration can be required to view the landing page containing the YouTube Live player. If people register to watch a replay, they will get whatever series of follow up messages you created for the original event. So if a follow up message was supposed to go out two hours after the live event, replay viewers will get that message two hours after watching the replay.
* \[ylchat\] shortcode now automatically stops outputting the iframe for the YouTube Live chat associated with the video feed when it is no longer available after the program. This prevents people from seeing what looks like an error if they view the replay. Now instead of supporting an attribute asking for the time when the feed should be deactivated, the only supported attribute is a note field for a message to be displayed over the chat box. Example: \[ylchat note="Enter your questions below"\]

= 4.3.8 =

* Styling tweaks to avoid conflicts with Twentyseventeen (and probably other themes)
* New shortcode: \[rsvpmaker_one post_id="10"\] displays a single event post with ID 10. Shows the complete form unless the attribute showbutton="1" is set
* New shortcode: \[rsvpmaker_form post_id="10"\] displays just the form associated with an event (ID 10 in this example. Could be useful for embedding the form in a landing page that describes the event but where you do not want to include the full event content.

= 4.3.7 =

YouTube Live webinar setup help on reminders page

= 4.3.5 =

Tweak to PayPal code

= 4.3.4 =

Tweak to localization/translation code.

= 4.3.3 =

Notification to make sure timezone is set properly.

= 4.3.2 =

* Improved event post previews on Facebook by adding event date to the end of the title. RSPMaker now outputs its own og:title Facebook Open Graph metadata (on by default, but can be turned off in settings if this interferes with other SEO plugins).
* New shortcode, \[rsvpmaker_next\], displays just the next scheduled event. If the type attribute is set, that becomes the next event of that type. Example: \[rsvpmaker_next type="webinar"\]. Also, this displays the complete form rather than the RSVP Now! button unless showbutton="1" is set.
* When embedding a YouTube Live stream in a page or post of your WordPress site, you can use the shortcode \[ylchat\] to embed the associated comment stream (which can be used to take questions from the audience). This extracts the video ID from the YouTube link included in the page and constructs the iframe for the chat window, according to Google's specifications. You can add attributes for width and height to override the default values (100% wide x 200 pixels tall). To make the comments box stop displaying after the end time for the webinar, use the attribute until as in \[ylchat until="January 30, 2017 8 pm"\] or \[ylchat until="2017-01-30 20:00:00"\]

= 4.3 =

Made sure all metadata, including pricing, is copied from event templates to individual events.

= 4.2.8 =

Fixes to captcha, date encoding in post slug

= 4.2.7 =

* Option for RSVP attendees to cancel when RSVPs are closed (max attendees threshhold met)
* Number of guests added limited according to max attendees setting

= 4.2.5 =

* Tracking of email unsubscribes for local email broadcasts (to members and event attendees). Will also unsubscribe email address from the default MailChimp list, if set.

= 4.2.4 =

* Option to have calendar grid display week starting on Monday rather than Sunday (add attribute weekstart="Monday" to rsvpmaker_upcoming or rsvpmaker_calendar shortcodes)
* Tweaks to mailer. For email to website members or event attendees, default is now to use from email of logged in user rather than email address specified for use with MailChimp.

= 4.2.3 =

* Improvements to RSVP form builder. Better round trip handling of additional fields added.
* Bug fix to code the encodes date into permalink.

= 4.2 =

* RSVPMaker Events widget can now be set to only show events of a specific type (event types are a taxonomy similar to post categories). Example: only show events of the type Featured, rather than all upcoming events.
* New RSVPMaker Events By Type widget shows a listing of the types you have established, with a count of the upcoming events for each.

= 4.1.8 =

Update for better compatibility with SMTP plugins and the SendGrid plugin.

= 4.1.7 =

Adding JQuery datepicker calendar widget (longtime wishlist item, finally got it working)

= 4.1.6 =

* Added rich text editor for Editor's Note on scheduled email broadcasts.
* Fixes to content import for email broadcasts and iCal appointment reminder.

= 4.1.5 =

* Code updated for translation
* Mailer now provides an equivalent of the WordPress YouTube embed (displays the YouTube preview image for a video with a link to the YouTube address for that content)
* Bug fixes to mailer function

= 4.1.4 =

* Additional options for scheduled email.
* Set a condition that has to be met for the message to be sent (example: roundup of events shouldn't be sent if there are currently no future events listed).
* Making it easier to see which posts have scheduled broadcasts and for what time.

= 4.1.3 =

* More options for adding an Editors Note to a scheduled email newsletter (can be based on an excerpt from a sselected blog post).
* Option to send a preview version of a scheduled email 1 to 24 hours prior to the scheduled broadcast time.

= 4.0 =

* Addition of mailer for invitations and other messages, with support for MailChimp lists.
* Mailer includes the ability to schedule emails or establish newsletters that will be sent on a recurring schedule, such as a roundup of events from the calendar to be sent weekly.

= 3.9.9 =

Fix for duplicate dates appearing on posts after upgrading.

= 3.9.8 =

* Fixed the archive feed for the rsvpmaker post type to sort by date order. So a url like rsvpmaker.com/rsvpmaker/ now works for showing an event listing. You can also get an RSS feed of your events at rsvpmaker.com/rsvpmaker/feed/
* Event Types set in the editor are now displayed like categories on a blog post, and clicking on them will take you to a feed for that event type. Example: http://rsvpmaker.com/blog/rsvpmaker-type/featured/
* Added shortcode for displaying the RSVP Report publicly on the website. Tag: rsvp_report_shortcode, accepts one attribute, public="1" or public="0" with the default being public="0" (login required)
* Settings page for RSVPMaker now allows you to specify one of the page templates included with your theme that should be used for single event posts. If you have a custom theme, you can create a single-rsvpmaker.php template specifically for events. But if you're working with a free or purchased theme, you may find that it includes a full-width page template or other variation that works better than the one that would be used by default.

= 3.9.7 =

Better functionality for membership websites, where the people entering RSVPs have user accounts and log in prior to responding.
* The system will now automatically look up any previous RSVPs associated with the same account (which for unauthenticated users only happens if they click the update link in a confirmation email).
* Form fields will be filled in based on user metadata, provided that the form field names match the usermeta fields. Example: a membership site records the user's mobile number as mobile_phone, so the form field for Mobile Phone will be filled in automatically on the RSVP form - [See blog post](http://rsvpmaker.com/blog/2016/06/20/using-rsvpmaker-on-membership-websites/)

= 3.9.6 =

Bug fix - delete guests

= 3.9.4 =

* Restoring iCal attachments on RSVP confirmation emails. This was included in a previous release but removed because of a problem with Microsoft Outlook compatibility - now fixed.
* Updated coding for translation - all display strings should now be coded to allow for translation.

= 3.9 =

Significant change to the event data model. By storing dates as post metadata, this release eliminates the dependency on the custom database table previously used for event dates. One advantage: content will be easier to move between websites using standard WordPress import/export routines.

= 3.8.1 =

Bug fix: timezone display

= 3.8 =

Conditional display of form options depending on pricing (whether the user is paying for the lunch as well as the workshop)

= 3.7.5 =

Time limits on prices for online payments. Example: "early bird" pricing for conference registration, where after the deadline pricing goes up.

= 3.7.4 =

* Attendees who update an RSVP for which they previously recorded a payment are now prompted to pay any difference (for additional guests or a change in pricing options)
* The RSVP Report screen now lets an administrator update attendee records or record a payment (for example, if a payment was received offline rather than through PayPal). Payments recorded by an administrator are logged on the 'PayPal log' with the username of the user who recorded them.

= 3.7 =

* Overhaul of PayPal payment functionality, including easier setup
* Better handling of guest registration, including the ability to prompt guests for data such as meal choices

= 3.6.4 =

* German translation, courtesy of Björn Wilkens
* Update of PO Edit POT catalog for translations
* Warning message to make it easier to see when updating events based on a new event template might overwrite customizations

= 3.6.3 =

Added checkbox option for whether to include the content of an event listing in the RSVP confirmation and reminder messages.

= 3.6.2 =

Adds the rsvpmaker_timed shortcode, which can be wrapped around any bit of content in a page or a post that should only be displayed after a given time, until a given time, or between a start and end time. The shortcode attributes are start, end, too_early, and too_late. Put a plain language date like 'January 1, 2016 7 pm' in the start and/or end fields, or use a database style date like '2016-01-20 19:00' and RSVPMaker will test the current time against those rules. If the viewer is coming too the site too early or too late, according to those rules, the shortcode will return either an empty string or the contents of the too_early / too_late parameters, if set. Otherwise, the content will be returned as it normally would be.

Example:

[rsvpmaker_timed start="January 1, 2016" end="January 30, 2016 11:59 pm" too_early="sorry, too early" too_late="sorry, too late" ]

Special offer details here

[/rsvpmaker_timed]

= 3.6 =

* CSS changes aimed at more consistent formatting of the form across themes.
* RSVP Reminders function will now let you create and edit follow up email messages to attendees for events from the past week.

= 3.5.9 =

Create a template based on an existing event, or clone an event (copy content to a new title for a new date).

= 3.5.8 =

Got drop-down lists for future events, event types working in calendar popup

= 3.5.7 =

* Placeholder image and popup user interface for the calendar now provided for the WordPress rich text editor. This allows a site editor to insert or update an events listing with optional calendar display, without the need to work directly with the rsvpmaker_upcoming shortcode and its parameters.
* Popup editor also provided for setup of the RSVPMaker form. You can still edit or enhance the HTML/shortcodes directly, but this should make it easier to get the coding right (particularly when you don't want to do anything more elaborate than add an additional field.

= 3.5.6 =

* You can now download to CSV for basic spreadsheets without the need to install the additional RSVPMaker Excel plugin.
* Removing iCal attachments from confirmation and reminder messages for now. Ran into some problems with Microsoft Outlook that will take time to debug.

= 3.5.4 =

Confirmation and reminder messages now include an iCal attachment, making it easier for recipients to add the event to their own appointment calendars.

= 3.5 =

* [Support for Google Hangouts on Air](http://rsvpmaker.com/hangouts), the free video broadcast service, makes it possible to organize webinars on a budget using RSVPMaker.
* Overhaul of the system for creating and sending email confirmation messages and scheduled reminders.

= 3.4.4 =

Fixing bug in RSVP Report.

= 3.3.8, 3.3.9 =

Revisiting fix to widget code. Not as fixed as I thought.

= 3.3.7 =

Updated widget code to use the newer style of PHP object constructor, replacing code deprecated under WordPress 4.3.

= 3.3.1 =

Bug fix: display of multiday events in calendar view

= 3.3 =

* Event templates modified to support multiple choices on frequency and day of the week. For example, "Every week on Monday and Wednesday" or "First and Third Monday"
* Add to Google Calendar / Download to Outlook (iCal) icons now shown by default (can be disabled on settings screen)
* Added rsvpmaker_calendar shortcode for displaying the calendar independently of the rsvpmaker_upcoming event listing.

= 3.2.8 =

* Updated for WordPress 4.2.2.
* Translation files updated.
* Duration and category set in event templates now copied to events based on that template.
* Fix to date display when duration is set (timezone adjustment)

= 3.2.6 =

Fixed next post / previous post links to reflect chronological order of events, rather than post_date

= 3.2.5 =

Added option to include timestamp when RSVP was recorded in RSVP Report or download to Excel.

= 3.2.4 =

* Bug fix - projected dates for event templates
* Email attendees option added to RSVP Report (a mailto: link with the addresses of all attendees)

= 3.2.3 =

Fixes a CSS bug with the display of multiple prices

= 3.2.2 =

* Bug fixe: wp_title filter was missing defaults for optional values
* Print format option for RSVP Report when displayed as a table

= 3.2 =

* More responsive calendar display, works better in themes with a narrow content area (including Twenty Fifteen) or on mobile devices.
* Option to display RSVP Report in a table, similar to the excel export format
* Fixed a bug in event template projected dates

= 3.1.1 =

* Adjusts for timezones if set in the WordPress Settings > General screen
* Invoice tracking can be disabled for PayPal payments

= 3.1 =

* Updating for WordPress 4.0
* Fixed a bug with form templates (support for hidden fields)
* Event Template screen now includes an option to apply a template to an existing event.

= 3.0.9 =

* Clearer prompt to update existing events based on a template, or add new events on the schedule set in the template, after a template is created or updated.
* Fix for bug with setting to display or hide the count of people who have RSVP'ed for an event.

= 3.0.8 =

* Tweak to PayPal success/error messages, logging of messages
* Fixing bug related to rsvpmaker_upcoming shortcode display (unset variable)

= 3.0.7 =

Fix to calendar display

= 3.0.6 =

Bug fix: correct handing of "More Events" link. The "events page" field on the RSVPMaker settings screen should be set to a full url like http://rsvpmaker.com/events/

= 3.0.5 = 

Additional rsvpmaker_upcoming attribute of one="next" or one="slug-here" or one="123" (post id) to [highlight a single event in a page or blog post](http://rsvpmaker.com/2014/01/embedding-a-single-event-in-a-page-or-post/).

= 3.0.4 =

Fix to handle password protected posts properly (previously was showing RSVP form even if content was supposed to be protected).

= 3.0.3 =

* Updated Spanish translation
* Fix to dashboard widget

= 3.0.2 =

* Tweaked code to avoid overwriting event post slugs that have been set manually.
* Updated translation for Norwegian

= 3.0.1 =

* Optional dashboard widget
* Updated admin screen for better control of custom menus (display for only authors, only editors, or only admins)
* Updated Norwegian translation (thank you Thomas Nybø)

= 3.0 =

Bug fixes for additional editors function (very tricky)

= 2.8.9 =

Bug fixes, primarily in the event template functions.

= 2.8.8 =

Bug fixes. Checkbox settings on editing screen weren't being recorded properly.

= 2.8.7 =

Bug fix for incorrect rounding of ticket prices.

= 2.8.6 =

More complete Spanish translation

= 2.8.5 =

* Spanish language translation
* Option to allow event authors to designate other users who can edit an event or, more importantly, an event template -- and all events derived from that template. This allows users who do not have full editing rights to be granted rights to edit specific events or series of events. Useful on community websites where several representatives of a group or club may wish to share editing rights, without the site owner having to make them editors of the entire site or of all events.

= 2.8.4 =

Additional form customization shortcodes for checkbox and radio buttons. See [form customization](http://rsvpmaker.com/2012/07/rsvpmaker2-5/)

= 2.8.3 =

Bug fix - trying to address issue some users report with permalinks. Switched to get_post_permalink() instead of get_permalink() (according to Codex, may be better at handling custom post types)

= 2.8.1 and 2.8.2 =

Improvements to template function.

= 2.8 =

* Event template function - more flexible way of handling recurring events
* Update of translation files, more admin functions included

= 2.7.8 =

* Bug fix: recurring events utility was broken, now it's not
* Bug fix: calendar navigation from month to month fixed for sites without pretty permalinks (?page_id=123 format)

= 2.7.7 =

Removed a spam check that created more problems than it solved.

= 2.7.6 =

* Fixes to paypal code
* Better handling of query string post addresses (question mark format rather than pretty permalinks)
* Sort by chronological option for RSVPMaker posts in admin screen
* RSVP Report option to show members who have not responded (for membership sites where users log in to a WordPress account before responding, tracks user IDs). Must be activated on settings screen.

= 2.7.5 =

* Fixed a glitch with display of CAPTCHA image
* Added option to hide yes/no radio boxes (assume the answer is yes)

= 2.7.4.1 and 2.7.4.2 =

Bug fixes

= 2.7.4 =

You can now specify an SMTP account to be used for more reliable delivery of notification emails (less likely to be flagged as spam if they're coming from a real account).

= 2.7.3 =

Another bug fix related to JavaScript output.

= 2.7.2 =

Bug fix. RSVPMaker-specific JavaScript was being output on other post types. Oops.

= 2.7.1 =

* Improved functionality for attendees updating their RSVPs. Previous data loaded from email address coded in link (from confirmation message) or from profile of a logged in user.
* Fixed JavaScript error that was interfering with display attendees function.
* Fixed error in More Events link for an event listing.

= 2.7 =

* Added the option to require a login prior to RSVP for membership-oriented sites where event attendees have a user name and password in WordPress. Name and email can automatically be filled in on the form. It's possible to read in other profile data by customizing the rsvpmaker_profile_lookup function (see the documentation on RSVPMaker customization at rsvpmaker.com).

= 2.6 =

Incremental update to translation files.

= 2.5.9 =

* Norwegian translation (thank you: Thomas Nybø) and update of translation source file.

* Added checkbox to let you specify whether the count of people who have RSVPed should always be shown (or only when a maximum number of participants is specified).

= 2.5.8 =

Bugfix

= 2.5.7 =

* Form customization now includes the ability to set fields as required, with both client and server-side validation. This works with the new shortcode-style method of specifying form fields and form layout. Example: `[rsvpfield textfield="phone" required="1"]`. By default, the required fields are first, last, and email.

* The filter used to add RSVP form fields has also been updated with a lower priority index to make it execute before other filters on the_content. This is in response to a user complaint about interaction with a related posts plugin that also operates on the_content, where the related posts widget was appearing above rather than below the form. New call is `add_filter('the_content','event_content',5)`

= 2.5.6 =

Fixes bug fix with some checkbox options not being set / cleared correctly in the event editor.

= 2.5.5 =

* The date editing section of the event editor now uses drop-downs controls for both adding dates and editing dates.

* rsvpmaker_upcoming shortcode now accepts limit="x" (show x number of events) as an attribute. Example `[rsvpmaker_upcoming limit="3"]` would retrieve a maximum of 3 posts. You can also use add_to_query="myquerystring" to modify the query using query_posts() syntax. Example: `[rsvpmaker_upcoming add_to_query="p=99"]` would retrieve a single rsvpmaker post with the ID of 99. 

* Code changes to prevent a potential security risk with user submitted data in RSVP Reports, use of esc_attr() on variables to prevent script injection.

= 2.5.4 =

* Moved functions for downloading RSVP results to Excel to a separate plugin, RSVPMaker Excel.

* Several bugfixes were released following version 2.5, and a few more are included in this release.

= 2.5 =

Introduced a new method for customizing the RSVP form, either on the settings screen or on a per-event basis. NOTE THAT PREVIOUS CUSTOMIZATIONS WILL NOT BE AUTOMATICALLY BE PRESERVED. The new method provides greater design freedom, allowing you to change the form layout, the order in which fields appear, and whether you want to include the guest section or a note field. A series of shortcodes are provide to generate the fields in the correct format for RSVPMaker.

This release also includes some code cleanup and a fix to the JavaScript function for adding guest fields (thanks to soaringthor for the code shared on the support forum).

= 2.4.2 =

Fix to PayPal code for handling currency other than USD.

= 2.4.1 =

Fix to calendar grid display, navigation between months.

= 2.4 =

Number format options on settings screen for non-U.S. currencies. For example, PLN 1.000,00 (Polish currency, European separation for thousands and decimal) instead of $1,000.00

= 2.3.9 =

* Updates to Polish translation by Jaroslaw Zelinski
* Fix for multi-currency support (display of currency code rather than $ for currencies other than USD)

= 2.3.6 =

* Introducing Polish translation by Jaroslaw Zelinski
* Corrections to translation file setup

= 2.3.5 =

* Improvements to automated reminders. Ability to set timing for reminders cron job
* Even more tweaks for UTF-8 email (coding for From and Subject headers)

= 2.3.4 =

* Automated event reminders to people on RSVP list for an event (experimental)
* Email and confirmation messages set to UTF-8

= 2.3.3 =

Bug fix - rsvp report

= 2.3.2 =

* Fixing character encoding issue with database table for RSVP responses (setting to utf-8 for better multi-language compatibility).
* Fixed typographical error on calendar display (comma between month and year)

= 2.3.1 =

More changes for use with ChimpBlast

= 2.3 =

* Currency for use with PayPal payments can now be customized on Settings screen
* Minor changes for use with ChimpBlast

= 2.2 =

Added option to require people to decode the secret message in a CAPTCHA image when completing the RSVP form. Useful if you're getting spam bot submissions.

= 2.1 =

* Fields for RSVP form can now be edited from the settings panel. Previously modifying the form required some PHP hacking.
* You can now get a listing of past events with some attributes for the event_listings shortcode. Suggesting past="1" format="headline" date_format="F jS, Y"

= 2.0 =

Fixed code for downloading reports to Excel (again), this time based on the [PHPExcel](http://www.phpexcel.net/) library

= 1.9.3 =

* Fix to code for downloading reports to Excel (bundling of PEAR libraries)
* Changed loading of translation domain.

= 1.9.1 =

* Tweak to handing of the loop within rsvpmaker_upcoming shortcode
* Update to plugin url references using plugins_url() instead of constant

= 1.9 =

* Integrated ability to download reports to Excel (still based on PEAR Spreadsheet Writer, but you no longer have to download it separately).
* Bug fixes and code cleanup.

= 1.8 =

Fixing translation files that were missing from svn

= 1.7 =

Bug fixes: display glitch, form spam filtering

= 1.6 =

Added by request: support for custom-fields and post_tag in the rsvpmaker content type. I understand this helps with WooThemes integratiton?

= 1.3, 1.4, 1.5 =

Bug fixes. Sorry

= 1.2 =

* Update to pluggable function rsvpmaker_profile_lookup - will now look up profile details of users who are logged in. Override to retrieve profile details from a member database or any other source.
* Customizable security settings for RSVP Report.

= 1.1 =

* Bug fix for uninstall.php file.
* Fixed display of events with no RSVP set.

= 1.0 =

* Added a `basic_form` function that you can override to change the basic fields of the RSVP form. For example, you can change it to omit the section that asks for the names of guests. This is in addition to the `rsvp_profile` function, which is used to collect additional contact details such as phone # or mailing address. See the instructions for [__adding custom functions__](http://www.rsvpmaker.com/2010/12/changing-the-rsvp-form-other-customizations/).
* You have the option of allowing the names of attendees and the contents of the notes field to be displayed publicly. To avoid encouraging spam entries, this content is loaded via AJAX and only when the user clicks the Show Attendees button
* Moved most of the default formatting into a CSS file that is queued up on pages that show event content. There is in option on the settings page for specifying your own css file to use instead.  Most inline styles have been replaced by class selectors. However, the styling of the RSVP Now button is still set on the RSVPMaker settings screen. Works better for email distribution of events.
* RSVP Report now lists timestamp on reply and lets you sort by either alphabetical order or most recent.
* If you're signing up employees or workers for specific timeslots, you can now set that to half-hour increments
* Tweaked redirection code to handle confirmation and error messages on sites that don't have permalinks enabled
* Changed label for RSVPMaker widget as it shows up on the administrator's screen under Appearance.
* Added an uninstall script for removing custom tables and database entries.

= 0.9.2 =

Bug fix

= 0.9.1 =

Added debug checkbox in options. When this is turned on, it creates an additional admin screen for checking that RSVPs are recorded properly, displaying system variables.

= 0.9 =

* Made it easier to edit dates for events previously entered in system.
* Widget and headlines listing shortcode output now include a link to your event listing page.
* Cleanup on options handling.

= 0.8 =

* Added type parameter for shortcode so you can display only events tagged with "featured" or another event type using `[rsvpmaker_upcoming type="featured"]`
* Added ability to set RSVP start date as well as deadline for RSVPs
* If signing up workers or volunteers for specific timeslots, you can now specify the duration of the timeslots in one-hour increments
* Cleaned up Event Dates, RSVP Options box in editor, moving less commonly used parameters to the bottom.
* Added a Tweak Permalinks setting (a hack for a few users who have reported "page not found" errors, possibly because some other plugin is overwriting the RSVPMaker rewrite rules).
* Tested with WP 3.1 release candidate

= 0.7.6 =

Fixed issue with setting default options.

= 0.7.5 =

Improved ability to add a series of recuring events, including ability for software to calculate the dates based on a schedule like "Second Tuesday of the month"

= 0.7.4 =

Bug fix to prevent customizations from being overwritten. Custom functions should be placed in rsvpmaker-custom.php and the file must be installed in the plugins directory above the rsvpmaker folder: wp-content/plugins/ instead of wp-content/plugins/rsvpmaker/

= 0.7.3 =

* Updated code for displaying RSVP Reports. Added functionality for deleting entries.
* Beginning to introduce translation support. See translations directory for rsvp.pot file to be used by translators.

= 0.7.2 =

Bug fix, RSVP Reports

= 0.7.1 =

Bug fix, tweak to register post type configuration

= 0.7 =

* Custom post type slug changed from 'event' to 'rsvpmaker' in an attempt to avoid name conflicts, permalink issues.
* Widget now lets you set the # of posts to display and date format string

= 0.6.2 =

* Updated to WP 3.03
* Addition of event type taxonomy

= 0.6.1 =

* Fixed errors in database code for recording guests and payments
* Added option to switch between 12-hour and 24-hour time formats
* Added ability to set maximum participants per event.

= 0.6 =

* First public release November 2010.

== Upgrade Notice ==

= 3.9 =

Significant change to the event data model. By storing dates as post metadata, this release eliminates the dependency on the custom database table previously used for event dates. One advantage: content will be easier to move between websites using standard WordPress import/export routines.

= 3.0 =

Important fixes if you are using the event templates or additional editors functions

= 2.5.4 =

Export to Excel function moved to a separate plugin.

= 2.5 =

New method for customizing the RSVP form introduced.