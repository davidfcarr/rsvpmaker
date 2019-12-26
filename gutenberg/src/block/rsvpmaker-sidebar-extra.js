/**
 * BLOCK: blocknewrsvp
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
//import './editor.scss';
//import './style.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
//const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const el = wp.element.createElement;
const {Fragment} = wp.element;
const { registerPlugin } = wp.plugins;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
//const { withState } = wp.compose;
//const { DateTimePicker, RadioControl, SelectControl, TextControl } = wp.components;
//const { withSelect, withDispatch } = wp.data;
//const {RSVPMakerDateTimePicker, RSVPMakerOn} = './rsvpmaker-sidebar.js';

import {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl} from './metadata_components.js';

function recordChange(metaKey, metaValue) {
	console.log(metaKey + ': ', metaValue);
}

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

const PluginRSVPMaker = () => {
    return(
		<Fragment>
		<PluginSidebarMoreMenuItem target="plugin-rsvpmaker-extra" icon="calendar-alt">RSVPMaker</PluginSidebarMoreMenuItem>
        <PluginSidebar
            name='plugin-rsvpmaker-extra'
            title='RSVPMaker'
            icon="calendar-alt"
        >

<p>For additional options, events spanning multiple dates, and event pricing see: {related_link()}</p>
{(!rsvpmaker_ajax.template_msg && !rsvpmaker_ajax.special && (rsvpmaker_ajax._rsvp_count == '1')) && <div><MetaDateControl metaKey='_rsvp_dates' /><MetaSelectControl
		label="End Time Display"
		metaKey="_firsttime"
		options={ [
			{ label: 'Not Set', value: '' },
			{ label: 'Set End Time', value: 'set' },
			{ label: 'Add Day / Do Not Show Time', value: 'allday' },
		] }
	/><MetaEndDateControl /></div>}
<div>{(rsvpmaker_ajax._rsvp_count != '1') && <p>{__('Event has multiple dates set. Edit on RSVP / Event Options screen.')}</p>}</div>

<MetaSelectControl
		label="Collect RSVPs"
		metaKey="_rsvp_on"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Add to Google/Outlook Calendar Icons"
		metaKey="_calendar_icons"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Add Timezone to Date"
		metaKey="_add_timezone"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Timezone Conversion Button"
		metaKey="_convert_timezone"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>
			<MetaTextControl title="Send notifications to:" metaKey="_rsvp_to" />
		<MetaSelectControl
		label="Send Confirmation Email"
		metaKey="_rsvp_rsvpmaker_send_confirmation_email"
		options={ [
			{ label: 'Yes', value: 'on' },//past implementation used both 'on' and '1'
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Include Event Content with Confirmation"
		metaKey="_rsvp_confirmation_include_event"
		options={ [
			{ label: 'Yes', value: 'on' },//past implementation used both 'on' and '1'
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Login required to RSVP"
		metaKey="_rsvp_login_required"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Captcha security challenge"
		metaKey="_rsvp_captcha"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Yes/No Options on Registration Form"
		metaKey="_rsvp_yesno"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No (Yes is assumed)', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show RSVP Count"
		metaKey="_rsvp_count"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Display attendee names / RSVP note field"
		metaKey="_rsvp_show_attendees"
		options={ [
			{ label: 'No', value: '0' },
			{ label: 'Yes', value: '1' },
			{ label: 'Only for Logged In Users', value: '2' },
		] }
	/>

<MetaTextControl
		title="Maximum number of participants (0 for no limit)"
		metaKey="_rsvp_max"
	/>
<div>For additional options, including event end time, multiple dates, and event pricing see: {related_link()}</div>
        </PluginSidebar>
		</Fragment>
    )
}
if (typeof rsvpmaker_ajax !== 'undefined') 
	registerPlugin( 'plugin-rsvpmaker', { render: PluginRSVPMaker } );
