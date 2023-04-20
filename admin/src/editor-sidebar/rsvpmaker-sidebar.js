import {useState} from 'react';
//import './state.js';
//const { withState } = wp.compose;
const { subscribe } = wp.data;
const { DateTimePicker } = wp.components;
const { Panel, PanelBody, PanelRow } = wp.components;
import {MetaFormToggle, MetaTimeLord, MetaEndDateTimeControl} from './metadata_components.js';
import DateOrTemplate from './DateOrTemplate.js';
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();
import TemplateProjected from './TemplateProjected.js';

var el = wp.element.createElement;
const { __ } = wp.i18n; // Import __() from wp.i18n
	
const RSVPMakerSidebarPlugin = function() {
if(typeof rsvpmaker_ajax === 'undefined')
	return null; //not an rsvpmaker post
const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
const url = window.location.href;
const tabarg = url.match(/tab=([^&]+)/);
const tab = (tabarg) ? tabarg[1] : 'basics';

const [openModal,setOpenModal] = useState(('draft' == initialPostStatus) || ('auto-draft' == initialPostStatus) || ((tabarg) && tabarg[1]));

const end_times = Array();
const end_times_display = Array();
var datecount = '';
var first_date = '';
var additional_dates = '';
var time_display = '';
const rsvpdates = Array();

var post_id = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'id' );
if(rsvpmaker_ajax.special)
	{
	return (
		el(
			wp.editPost.PluginPostStatusInfo,
			{},
<div>
<h3>RSVPMaker {__('Special Document','rsvpmaker')}</h3>
{rsvpmaker_ajax.top_message}
{(rsvpmaker_ajax.special == 'RSVP Form') && <p><a href="https://rsvpmaker.com/knowledge-base/customize-the-rsvp-form/" target="_blank">{__('Documentation','rsvpmaker')}</a></p>}
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
<QueryClientProvider client={queryClient}>
<DateOrTemplate />
<MetaFormToggle
label={__('Collect RSVPs','rsvpmaker')} 
metaKey="_rsvp_on"/>

{rsvpmaker_ajax.bottom_message}
</QueryClientProvider>
</div>
		)
	);
}

if(typeof rsvpmaker_ajax !== 'undefined')
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-plugin', {
	render: RSVPMakerSidebarPlugin,
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
    );
}

if((rsvpmaker.post_type == 'rsvpmaker') || (rsvpmaker.post_type == 'rsvpmaker_template'))
wp.plugins.registerPlugin( 'rsvpmaker-sidebar-postpublish', {
	render: RSVPPluginPostPublishPanel,
} );

if(("undefined" !== typeof rsvpmaker_ajax) && rsvpmaker_ajax.template_url) {
	wp.data.dispatch('core/notices').createNotice(
		'info', // Can be one of: success, info, warning, error.
		__('You are editing one event in a series defined by a template. To make changes you can apply to the whole series of events, switch to editing the template.'), // Text string to display.
		{
			id: 'rsvptemplateedit', //assigning an ID prevents the notice from being added repeatedly
			isDismissible: true, // Whether the user can dismiss the notice.
			// Any actions the user can perform.
			actions: [
				{
					url: rsvpmaker_ajax.template_url,
					label: rsvpmaker_ajax.template_label,
				},
			]
		}
	);	
}

if((rsvpmaker.post_type == 'rsvpmaker') || (rsvpmaker.post_type == 'rsvpmaker_template')) {
	const isEditorSidebarOpened = wp.data.select( 'core/edit-post' ).isEditorSidebarOpened();
	if ( ! isEditorSidebarOpened ) {
	  wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar('edit-post/document');
	}
}
