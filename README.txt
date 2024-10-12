=== RSVPMaker ===
Contributors: davidfcarr
Donate: http://www.rsvpmaker.com
Tags: event, calendar, rsvp, marketing, email
Donate link: http://rsvpmaker.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.6
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 11.4.3

Event and email marketing. Register guests and collect payment by PayPal or Stripe. Send invitations and newsletters.

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
    Stripe and MailChimp under the license of their creators.

== Changelog ==

= 11.4.2 =

* Eliminate one stray use of PHP short tags not supported in some configs of PHP 8

= 11.2.6 =

* PayPal support improvements
* Multiple sender addresses for Postmark forwarding
* RSVP Report format update, fix for deleting registrations

= 11.2.4 =

* Updates to PayPal payments support. Option to add payment services like Venmo or exclude services like PayLater from buttons displayed.
* Button on Settings -> RSVPMaker screen for copying current defaults to existing events and templates.

= 11.2.3 =

* More form and pricing UI improvements

= 11.2.2 =

* Clarified UI for modifying the RSVP form, creating reusable forms

= 11.2 =

* API access to the RSVP Report

= 11.1.9 =

* Better control over editing the default form (or an inherited form) versus a custom form for a single event.

= 11.1.8 =

* Updates to radio button controls on RSVP form.

= 11.1.7 =

* Neater display of pricing information in confirmation message
* Option to manually send a scheduled post promo when automation glitches. (Checks first to make sure it didn't already send).

= 11.1.5 =

* Option to add a charge based on an RSVP form selection such as meal choice (+$15 for Steak), either in addition to the base charge or as the primary way of charging attendees. Designed to work with radio button options.
* Radio button form widgets can now be set to default to the first item.

= 11.1.2 =

* Fix is_rsvpmaker_query()

= 11.1 =

* Bug fix for registration system, tweak for RSVP Report

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
