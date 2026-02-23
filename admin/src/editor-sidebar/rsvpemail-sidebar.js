var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n

import { registerPlugin } from '@wordpress/plugins';
import { __experimentalMainDashboardButton as MainDashboardButton } from '@wordpress/edit-post';
import { Dashicon, Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
const { subscribe, useSelect } = wp.data;

const RSVPEmailSidebarPlugin = function() {
const type = wp.data.select( 'core/editor' ).getCurrentPostType();
const post_id = wp.data.select( 'core/editor' ).getCurrentPostId();
const rsvpmaker_rest = useSelect( ( select ) => {
	const rs = select( 'rsvpmaker' );
	if(!rs)
	{
		
		return {};
	}
	const rsvpmaker_rest = rs.getSettings();
	return rsvpmaker_rest;
	} );

	if(type != 'rsvpemail')
		return null;
	return	el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div><h3>{__('Email Editor','rsvpmaker')}</h3><p>{__('Use the WordPress editor to compose the body of your message, with the post title as your subject line. View post will display your content in an email template, with a user interface for addressing options.','rsvpmaker')}</p>
<p><a href="https://rsvpmaker.com/knowledge-base/using-rsvp-mailer/" target="_blank">Documentation</a></p>
<p><strong>Design Options</strong></p>
{post_id == rsvpmaker_rest.default_email_template && <p>You are editing the default email template</p>}
{post_id != rsvpmaker_rest.default_email_template && <p>To change the styling of messages or add branding, <a href={rsvpmaker_rest.default_email_template}>edit the default template</a>.</p>}
<p>Visit the <a href={rsvpmaker_rest.email_design_screen}>Email Design Templates screen</a> to create alternate templates or customize the email CSS.</p>

</div>
);
}

if(rsvpmaker.post_type == 'rsvpemail') {
	registerPlugin( 'rsvpmailer-sidebar-plugin', {
		render: RSVPEmailSidebarPlugin,
	} );

	let wasSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
	let wasAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
	let wasPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
	// determine whether to show notice
	subscribe( () => {
		const isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
		const isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
		const isPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
		const shouldTriggerTemplateNotice = (
				( wasSavingPost && ! isSavingPost && ! wasAutosavingPost ) ||
				( wasAutosavingPost && wasPreviewingPost && ! isPreviewingPost )
		);

		// Save current state for next inspection.
		wasSavingPost = isSavingPost;
		wasAutosavingPost = isAutosavingPost;
		wasPreviewingPost = isPreviewingPost;

		if ( shouldTriggerTemplateNotice ) {
	wp.data.dispatch('core/notices').createNotice(
	'success', // Can be one of: success, info, warning, error.
	__('Preview & Send Email'), // Text string to display.
	{
		id: 'rsvpemialnotice', //assigning an ID prevents the notice from being added repeatedly
		isDismissible: true, // Whether the user can dismiss the notice.
		// Any actions the user can perform.
		actions: [
			{
				url: wp.data.select("core/editor").getPermalink(),
				label: __('View in email template'),
			}
		]
	}
);
wp.data.dispatch('core/notices').createNotice(
	'success', // Can be one of: success, info, warning, error.
	__('Preview & Send Email'), // Text string to display.
	{
		id: 'rsvpemialnowsnack', //assigning an ID prevents the notice from being added repeatedly
		isDismissible: true, // Whether the user can dismiss the notice.
		// Any actions the user can perform.
		type: 'snackbar',
		actions: [
			{
				url: wp.data.select("core/editor").getPermalink(),
				label: __('View in email template'),
			}
		]
	}
);
		}
} );

wp.data.dispatch('core/notices').createNotice(
	'info', // Can be one of: success, info, warning, error.
	__('Compose your message with the post title as the subject line and post content as the email body. Once you save and publish your post, preview it in the email template, choose your recipients, and send it.'), // Text string to display.
	{
		id: 'rsvpemialnotice', //assigning an ID prevents the notice from being added repeatedly
		isDismissible: true, // Whether the user can dismiss the notice.
	}
);			

}
