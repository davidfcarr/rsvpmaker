import './state.js';
//const { withState } = wp.compose;
const { subscribe } = wp.data;
const { DateTimePicker } = wp.components;
const { RadioControl, SelectControl, TextControl } = wp.components;
const { withSelect, withDispatch } = wp.data;

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
wp.data.dispatch('rsvpevent').setRSVPdate(rsvpmaker_ajax._rsvp_first_date);
/*
console.log('first '+rsvpmaker_ajax._rsvp_first_date);
console.log('end '+rsvpmaker_ajax._rsvp_end);
console.log('end display '+rsvpmaker_ajax._rsvp_end_display);
wp.data.dispatch('rsvpevent').setRSVPEnd(rsvpmaker_ajax._rsvp_end);
wp.data.dispatch('rsvpevent').setRSVPEndDisplay(rsvpmaker_ajax._rsvp_end_display);
*/
wp.data.dispatch('rsvpevent').setRsvpMeta('_rsvp_on',rsvpmaker_ajax._rsvp_on);
var datestring = '';
var dateaction = "action=rsvpmaker_date&nonce="+rsvpmaker_ajax.ajax_nonce+"&post_id="+rsvpmaker_ajax.event_id;

function related_link() {
	if(rsvpmaker_ajax.special)
		{
		return <div class="rsvp_related_links"><p></p></div>;
		}
	if(rsvpmaker_json.projected_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.projected_url}>{rsvpmaker_json.projected_label}</a></p></div>;	
		}
	if(rsvpmaker_json.template_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.template_url}>{rsvpmaker_json.template_label}</a></p></div>;	
		}
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
const end_times = Array();
const end_times_display = Array();
var datecount = '';
var first_date = '';
var additional_dates = '';
var time_display = '';
const rsvpdates = Array();
var meta = wp.data.select('core/editor').getEditedPostAttribute('meta');
console.log('select metadata');
console.log(meta);
if(rsvpmaker_ajax._rsvp_end_display != '')
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
console.log(post_id + ' post_id');
var rsvpmeta;
/*if(post_id)
	rsvpmeta = apiFetch( {path: '/wp-json/rsvpmaker/v1/meta/'+post_id} );
*/
/*then( meta => {
		console.log('meta_date');
		console.log(meta.first_date);
		rsvpdates.push(meta.first_date);
		Object.defineProperty(rsvpdateobj, 'date', {'value': meta.first_date});
		Object.defineProperty(rsvpdateobj, 'display', {'value': meta.first_display});
		Object.defineProperty(rsvpdateobj, 'end', {'value': meta.first_end});
		//console.log(meta);
 if(Array.isArray(meta.end_times))
			meta.end_times.map( function(key,value) { end_times.push(value) } );
		if(Array.isArray(meta.end_times_display))
			meta.end_times_display.map( function(key,value) { end_times_display.push(value) } );
		if(Array.isArray(meta.dates))
			meta.dates.map( function(key,value) { console.log('dates iterator'); console.log(value); rsvpdates.push(value) } );
	}).catch(err => {
		console.log(err);
	});
}
*/

//console.log('end times '+ typeof end_times);
//console.log(end_times);
/*
if(Array.isArray(rsvpdates))
	{
		console.log('rsvpdates is array: yes');
		console.log(rsvpdates);
		datecount = rsvpdates.length;
		first_date = rsvpdates.shift();
		console.log('datecount: '+datecount);
		console.log('first_date:' + first_date);
		console.log('rsvpdateobject');
		console.log(rsvpdateobj);
	}
else
	console.log('rsvpdates is array: NO');
if(Array.isArray(end_times))
	console.log('end times is array: yes');
else
	console.log('end times is array: NO');
*/
	//console.log('rsvpdateobj');
//console.log(rsvpdateobj);
/*
console.log('first end time');
console.log(first_end);
console.log('first end time display');
console.log(first_display);
*/

if(rsvpmaker_ajax.template_msg)
	{//if this is a template
		
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<h3>RSVPMaker Template</h3>
{rsvpmaker_ajax.top_message}
<p><RSVPMakerOn /></p>
<p>{rsvpmaker_ajax.template_msg}</p>
<p>{__('To change the schedule, follow the link below.')}</p>
<div class="rsvpmaker_related">
{related_link()}
<p>{__('Some options can be edited through the RSVPMaker sidebar (<span class="dashicons dashicons-calendar-alt"></span> icon above).')}</p>
</div>
{rsvpmaker_ajax.bottom_message}
</div>
		)
	);

	}
else if(rsvpmaker_ajax.special)
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
<div>{(rsvpmaker_ajax._rsvp_count == '1') && <MetaDateControl metaKey='_rsvp_dates' />}</div>
<div>{(rsvpmaker_ajax._rsvp_count != '1') && <p>{__('Event has multiple dates set. Edit on RSVP Event / Options screen.')}</p>}</div>
<MetaSelectControl
		label="Collect RSVPs"
		metaKey="_rsvp_on"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>
<div class="rsvpmaker_related">
<p>{time_display} {related_link()}</p>
<p>{__('Some options can be edited through the RSVPMaker sidebar (calendar icon above).')}</p>
</div>
{rsvpmaker_ajax.bottom_message}
</div>
		)
	);
}

var MetaSelectControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		return el( SelectControl, {
			label: props.label,
			value: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content ), recordChange(props.metaKey, content);
			},
		});
	}
);

var MetaDateControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				metaValue = metaValue.replace('T',' ');
				fetch('/wp-json/rsvpmaker/v1/clearcache/'+rsvpmaker_ajax.event_id);
				console.log(metaValue);
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		console.log(props);
		return el( DateTimePicker, {
			label: props.label,
			currentDate: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

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