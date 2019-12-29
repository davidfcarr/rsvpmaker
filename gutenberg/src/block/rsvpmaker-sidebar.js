//import './state.js';
//const { withState } = wp.compose;
const { subscribe } = wp.data;
const { DateTimePicker } = wp.components;
import {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl} from './metadata_components.js';

var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n

function related_link() {
	if(rsvpmaker_ajax.special)
		{
		return <div class="rsvp_related_links"></div>;
		}
	return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p></div>;	
	}

const RSVPMakerSidebarPlugin = function() {
if(typeof rsvpmaker_ajax === 'undefined')
		return null; //not an rsvpmaker post
const end_times = Array();
const end_times_display = Array();
var datecount = '';
var first_date = '';
var additional_dates = '';
var time_display = '';
const rsvpdates = Array();

if(rsvpmaker_ajax._rsvp_count && (rsvpmaker_ajax._rsvp_count != '1'))
	additional_dates = 'This event spans multiple dates ('+rsvpmaker_ajax._rsvp_count+' total)';
var post_id = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'id' );
if(rsvpmaker_ajax.special)
	{
		
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<h3>RSVPMaker Special Document</h3>
{rsvpmaker_ajax.top_message}
																																	<div class="rsvpmaker_related">
{related_link()}
</div>
{rsvpmaker_ajax.bottom_message}
</div>
		)
	);

	}
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<h3>RSVPMaker Event Date</h3>
{rsvpmaker_ajax.top_message}
{(!rsvpmaker_ajax.special && !rsvpmaker_ajax.template_msg && (rsvpmaker_ajax._rsvp_count == '1') && <div><MetaDateControl metaKey='_rsvp_dates' />
	<MetaEndDateControl /></div>
)}
{(rsvpmaker_ajax._rsvp_count > '1') && <p><a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Multiple Dates')}</a></p>}
{(rsvpmaker_ajax.projected_url && <div><p>
<a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Template Schedule')}</a></p>
<p><a href={rsvpmaker_ajax.projected_url} target="_blank">{__('Create/Update Events from Template')}</a><br />
</p>
</div>
)}
<MetaSelectControl
		label="Collect RSVPs"
		metaKey="_rsvp_on"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>
<div class="rsvpmaker_related">
<p>{related_link()}</p>
<p>{__('Basic options can be edited through the RSVPMaker sidebar (calendar icon above).')}</p>
</div>
{rsvpmaker_ajax.bottom_message}
</div>
		)
	);
}

if(typeof rsvpmaker_ajax !== 'undefined')
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-plugin', {
	render: RSVPMakerSidebarPlugin,
} );	

var PluginPrePublishPanel = wp.editPost.PluginPrePublishPanel;

function RSVPTemplatePluginPrePublishPanel() {

	return el(
        PluginPrePublishPanel,
        {
            className: 'rsvpmakertemplate-pre-publish-panel',
            title: __( 'RSVPMaker Template' ),
            initialOpen: true,
        },
<div>This is a template you can use to create or update multiple events.</div>
	);
}

function RSVPPluginPrePublishPanel() {
	return el(
        PluginPrePublishPanel,
        {
            className: 'rsvpmaker-pre-publish-panel',
            title: __( 'RSVPMaker Event Date' ),
            initialOpen: true,
        },
        <div><MetaDateControl /></div>
    );
}

if((typeof rsvpmaker_ajax !== 'undefined') && rsvpmaker_ajax.template_msg)
wp.plugins.registerPlugin( 'rsvpmaker-template-sidebar-prepublish', {
	render: RSVPTemplatePluginPrePublishPanel,
} );
else if (typeof rsvpmaker_ajax !== 'undefined')
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-prepublish', {
	render: RSVPPluginPrePublishPanel,
} );	

var PluginPostPublishPanel = wp.editPost.PluginPostPublishPanel;

function RSVPPluginPostPublishPanel() {
    return el(
        PluginPostPublishPanel,
        {
            className: 'rsvpmaker-post-publish-panel',
            title: __( 'RSVPMaker Post Published' ),
            initialOpen: true,
        },
        <div>{related_link()}</div>
    );
}

if(typeof rsvpmaker_ajax !== 'undefined')
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-postpublish', {
	render: RSVPPluginPostPublishPanel,
} );

if((typeof rsvpmaker_ajax !== 'undefined' ) && rsvpmaker_ajax.projected_url) {

		let wasSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
		let wasAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
		let wasPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
		// determine whether to show notice
		subscribe( () => {
			const isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
			const isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
			const isPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
			const hasActiveMetaBoxes = wp.data.select( 'core/edit-post' ).hasMetaBoxes();
			
			// Save metaboxes on save completion, except for autosaves that are not a post preview.
			const shouldTriggerTemplateNotice = (
					( wasSavingPost && ! isSavingPost && ! wasAutosavingPost ) ||
					( wasAutosavingPost && wasPreviewingPost && ! isPreviewingPost )
				);

			// Save current state for next inspection.
			wasSavingPost = isSavingPost;
			wasAutosavingPost = isAutosavingPost;
			wasPreviewingPost = isPreviewingPost;
	
			if ( shouldTriggerTemplateNotice ) {
				var newurl = rsvpmaker_ajax.projected_url.replace('template_list','setup');
	wp.data.dispatch('core/notices').createNotice(
		'info', // Can be one of: success, info, warning, error.
		__('After updating this template, click'), // Text string to display.
		{
			id: 'rsvptemplateupdate', //assigning an ID prevents the notice from being added repeatedly
			isDismissible: true, // Whether the user can dismiss the notice.
			// Any actions the user can perform.
			actions: [
				{
					url: newurl,
					label: __('New Event based on template'),
				},
				{
					label: ' or ',
				},
				{
					url: rsvpmaker_ajax.projected_url,
					label: __('Create / Update events'),
				},
			]
		}
	);
			}
} );
	
}