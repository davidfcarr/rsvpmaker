import {useState} from 'react';
const { subscribe } = wp.data;
const { DateTimePicker } = wp.components;
const { Panel, PanelBody, PanelRow } = wp.components;
import DateOrTemplate from './DateOrTemplate.js';
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();
import { useSelect } from '@wordpress/data';
import TemplateProjected from './TemplateProjected.js';

function getRsvpmakerRestSettings() {
	const fromStore = wp?.data?.select?.('rsvpmaker')?.getSettings?.();
	// The store returns {} as default state, so only trust it if it has data
	if(fromStore?.post_type) return fromStore;
	// PHP localizes as rsvpmakerSettings (block bundle) or rsvpmaker_rest (sidebar script)
	return window?.rsvpmakerSettings?.post_type ? window.rsvpmakerSettings
		: window?.rsvpmaker_rest?.post_type ? window.rsvpmaker_rest
		: null;
}

var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n
	
const RSVPMakerSidebarPlugin = function() {
const tab = 'basics';
const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
const [openModal,setOpenModal] = useState(('draft' == initialPostStatus) || ('auto-draft' == initialPostStatus));

const end_times = Array();
const end_times_display = Array();
var datecount = '';
var first_date = '';
var additional_dates = '';
var time_display = '';
const rsvpdates = Array();

var post_id = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'id' );
const rsvpmaker_rest = getRsvpmakerRestSettings();
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<QueryClientProvider client={queryClient}>
<DateOrTemplate rsvpmaker_rest={rsvpmaker_rest} />
{rsvpmaker_rest.bottom_message}
</QueryClientProvider>
</div>
		)
	);
}

let rsvpSidebarNoticesInitialized = false;
initRsvpSidebarNotices();

function initRsvpSidebarNotices() {
	if(rsvpSidebarNoticesInitialized) return;

	const tryInit = () => {
		if(rsvpSidebarNoticesInitialized) return true;
		const rsvpmaker_rest = getRsvpmakerRestSettings();
		if(!rsvpmaker_rest?.post_type) {
			return false;
		}
		/*
		// Only initialize for rsvpmaker and rsvpmaker_template post types—rsvpemail has its own
		if(rsvpmaker_rest.post_type != 'rsvpmaker_template' && rsvpmaker_rest.post_type != 'rsvpmaker') {
			rsvpSidebarNoticesInitialized = true;
			return true;
		}
		*/
		rsvpSidebarNoticesInitialized = true;
		runRsvpSidebarNotices(rsvpmaker_rest);
		return true;
	};

	if(tryInit()) return;

	// Wait for store/localized settings to be available, then run once.
	const unsubscribeInit = subscribe(() => {
		if(tryInit()) {
			unsubscribeInit();
		}
	});
}

function runRsvpSidebarNotices(rsvpmaker_rest) {
console.log('initRsvpSidebarNotices rsvpmaker_rest now',rsvpmaker_rest);

if(rsvpmaker_rest.post_type == 'rsvpmaker_template' || rsvpmaker_rest.post_type == 'rsvpmaker') {
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-plugin', {
	render: RSVPMakerSidebarPlugin,
} );	
}

console.log('post type check',rsvpmaker_rest.post_type);
if(rsvpmaker_rest.post_type == 'rsvpemail') {
	wp.plugins.registerPlugin( 'rsvpmailer-sidebar-plugin', {
		render: RSVPEmailSidebarPlugin,
	} );
	console.log('registered email sidebar plugin');

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

var PluginPostPublishPanel = wp.editPost.PluginPostPublishPanel;

function RSVPPluginPostPublishPanel() {
    return el(
        PluginPostPublishPanel,
        {
            className: 'rsvpmaker-post-publish-panel',
            title: __( 'RSVPMaker Post Published' ),
            initialOpen: true,
        },
    );
}

wp.plugins.registerPlugin( 'rsvpmaker-sidebar-postpublish', {
	render: RSVPPluginPostPublishPanel,
} );

if(rsvpmaker_rest.template_url != '') {
	wp.data.dispatch('core/notices').createNotice(
		'info', // Can be one of: success, info, warning, error.
			__('You are editing one event in a series defined by a template. To make changes you can apply to the whole series of events, switch to editing the template.'), // Text string to display.
			{
				id: 'rsvptemplateedit', //assigning an ID prevents the notice from being added repeatedly
				isDismissible: true, // Whether the user can dismiss the notice.
				// Any actions the user can perform.
				actions: [
					{
						url: rsvpmaker_rest.template_url,
						label: rsvpmaker_rest.template_label,
					},
				]
			}
	);
}

const isEditorSidebarOpened = wp.data.select( 'core/edit-post' ).isEditorSidebarOpened();
if ( ! isEditorSidebarOpened ) {
	wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar('edit-post/document');
}
if(rsvpmaker_rest.post_type == 'rsvpmaker_template') {
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
	__('Create/Update events based on template'), // Text string to display.
	{
		id: 'rsvpcreateupdatenotice', //assigning an ID prevents the notice from being added repeatedly
		isDismissible: true, // Whether the user can dismiss the notice.
		// Any actions the user can perform.
		actions: [
			{
				url: rsvpmaker_rest.projected_url,
				label: __('Go to Create/Update screen'),
			}
		]
	}
);
wp.data.dispatch('core/notices').createNotice(
	'success', // Can be one of: success, info, warning, error.
	__('Create/Update events based on template'), // Text string to display.
	{
		id: 'rsvptemplatesnack', //assigning an ID prevents the notice from being added repeatedly
		isDismissible: true, // Whether the user can dismiss the notice.
		// Any actions the user can perform.
		type: 'snackbar',
		actions: [
			{
				url: rsvpmaker_rest.projected_url,
				label: __('Go to Create/Update screen'),
			}
		]
	}
);
		}
} );
}

}

const RSVPEmailSidebarPlugin = function() {
	const rsvpmaker_rest = getRsvpmakerRestSettings();
	return	el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div><h3>{__('Email Editor','rsvpmaker')}</h3><p>{__('Use the WordPress editor to compose the body of your message, with the post title as your subject line. View post will display your content in an email template, with a user interface for addressing options.','rsvpmaker')}</p>
<p><a href="https://rsvpmaker.com/knowledge-base/using-rsvp-mailer/" target="_blank">Documentation</a></p>
<p><strong>Design Options</strong></p>
{rsvpmaker_rest.post_id == rsvpmaker_rest.default_email_template && <p>You are editing the default email template</p>}
{rsvpmaker_rest.post_id != rsvpmaker_rest.default_email_template && <p>To change the styling of messages or add branding, <a href={rsvpmaker_rest.default_email_template}>edit the default template</a>.</p>}
<p>Visit the <a href={rsvpmaker_rest.email_design_screen}>Email Design Templates screen</a> to create alternate templates or customize the email CSS.</p>

</div>
);
}
