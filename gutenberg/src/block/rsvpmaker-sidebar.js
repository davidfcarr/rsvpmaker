//import './state.js';
//const { withState } = wp.compose;
const { subscribe } = wp.data;
const { DateTimePicker } = wp.components;
//const { RadioControl, SelectControl, TextControl } = wp.components;
//const { withSelect, withDispatch } = wp.data;
import {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl} from './metadata_components.js';

//const { getSettings } = wp.date; // removed from Gutenberg
var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n
//var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
const rsvpdates = Array();
const rsvpdateobj = {"date":'',"end":'',"display":''};

function RsvpMeta(key,value) {
var xhr = new XMLHttpRequest();
xhr.open("POST", ajaxurl, true);

//Send the proper header information along with the request
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

xhr.onreadystatechange = function() {//Call a function when the state changes.
    if(this.readyState == XMLHttpRequest.DONE && this.status == 200) {
        // Request finished. Do processing here.
    }
}
wp.data.dispatch('rsvpevent').setRsvpMeta(key,value);
var dateaction = "action=rsvpmaker_meta&nonce="+rsvpmaker_ajax.ajax_nonce+"&post_id="+rsvpmaker_ajax.event_id+ "&key="+key+"&value="+value;
xhr.send(dateaction);
	}

if(rsvpmaker_type == 'rsvpmaker')
	{
//wp.data.dispatch('rsvpevent').setRSVPdate(rsvpmaker_ajax._rsvp_first_date);
//wp.data.dispatch('rsvpevent').setRsvpMeta('_rsvp_on',rsvpmaker_ajax._rsvp_on);
var datestring = '';
var dateaction = "action=rsvpmaker_date&nonce="+rsvpmaker_ajax.ajax_nonce+"&post_id="+rsvpmaker_ajax.event_id;

function related_link() {
	if(rsvpmaker_ajax.special)
		{
		return <div class="rsvp_related_links"></div>;
		}
/*	if(rsvpmaker_json.projected_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.projected_url}>{rsvpmaker_json.projected_label}</a></p></div>;	
		}
	if(rsvpmaker_json.template_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.template_url}>{rsvpmaker_json.template_label}</a></p></div>;	
		}
*/
	return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p></div>;	
	}

function get_template_prompt () {
	var post_id = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'id' );
	let parts = window.location.href.split('wp-admin/');
	let template_url = parts[0] + 'wp-admin/edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' + post_id;

	var template_prompt='';
	if(post_id)
		return <p id="template_prompt"><a href={template_url}>Create/update events from template</a></p>;
	return;
}

const RSVPMakerSidebarPlugin = function() {
if(!rsvpmaker_ajax)
	return; //not an rsvpmaker post
const end_times = Array();
const end_times_display = Array();
var datecount = '';
var first_date = '';
var additional_dates = '';
var time_display = '';
const rsvpdates = Array();
if(rsvpmaker_ajax && (rsvpmaker_ajax._rsvp_end_display != ''))
	{
		if(rsvpmaker_ajax._rsvp_end_display == 'all')
			time_display = __('Time not displayed');
		else {
			var timeparts = rsvpmaker_ajax._rsvp_end.split(':');
			var endhour = parseFloat(timeparts[0]);
			if(endhour > 11)
			{
				var pm = endhour - 12;
				if(pm) {
					time_display = __('End time set to ')+rsvpmaker_ajax._rsvp_end+' (' + pm +':'+timeparts[1]+ ' pm)';
				}
				else {
					time_display = __('End time set to ')+rsvpmaker_ajax._rsvp_end+' (12:'+timeparts[1]+ ' pm)';
				}

			}
			else if (endhour == 0) {
				time_display = __('End time set to ')+rsvpmaker_ajax._rsvp_end+' (12:'+timeparts[1]+ ' am)';
			}
			else {
				time_display = __('End time set to ')+rsvpmaker_ajax._rsvp_end+' (' + endhour + ':'+timeparts[1]+ ' am)';
			}
		}
	}
	else
		time_display = __('No end time set');

if(rsvpmaker_ajax._rsvp_count && (rsvpmaker_ajax._rsvp_count != '1'))
	additional_dates = 'This event spans multiple dates ('+rsvpmaker_ajax._rsvp_count+' total)';
var post_id = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'id' );
/*
if(rsvpmaker_ajax.template_msg)
	{//if this is a template
		
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<h3>RSVPMaker Template</h3>
{rsvpmaker_ajax.top_message}
<MetaSelectControl
		label="Collect RSVPs"
		metaKey="_rsvp_on"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>
<p>{rsvpmaker_ajax.template_msg}</p>
<p>{__('To change the schedule, follow the link below.')}</p>
<div class="rsvpmaker_related">
{related_link()}
<p>{__('Some options can be edited through the RSVPMaker sidebar (calendar icon above).')}</p>
</div>
{rsvpmaker_ajax.bottom_message}
</div>
		)
	);

	}
	else 
*/

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
<MetaSelectControl
		label="End Time Display"
		metaKey="_firsttime"
		options={ [
			{ label: 'Not Set', value: '' },
			{ label: 'Set End Time', value: 'set' },
			{ label: 'Add Day / Do Not Show Time', value: 'allday' },
		] }
	/>
	<MetaEndDateControl /></div>
)}
{(rsvpmaker_ajax._rsvp_count > '1') && <p><a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Multiple Dates')}</a></p>}
{(rsvpmaker_json.projected_url && <div><p>
<a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Template Schedule')}</a></p>
<p><a href={rsvpmaker_json.projected_url} target="_blank">{__('Create/Update Events from Template')}</a><br />
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

if(rsvpmaker_ajax.template_msg)
wp.plugins.registerPlugin( 'rsvpmaker-template-sidebar-prepublish', {
	render: RSVPTemplatePluginPrePublishPanel,
} );
else
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

wp.plugins.registerPlugin( 'rsvpmaker-sidebar-postpublish', {
	render: RSVPPluginPostPublishPanel,
} );

}// end initial test that rsvpmaker is set

if((typeof rsvpmaker_json !== 'undefined' ) && rsvpmaker_json.projected_url) {

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
				var newurl = rsvpmaker_json.projected_url.replace('template_list','setup');
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
					url: rsvpmaker_json.projected_url,
					label: __('Create / Update events'),
				},
			]
		}
	);
			}
} );
	
}