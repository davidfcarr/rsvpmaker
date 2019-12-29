/**
 * BLOCK: blocknewrsvp
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */


const { __ } = wp.i18n; // Import __() from wp.i18n
const el = wp.element.createElement;
const {Fragment} = wp.element;
const { registerPlugin } = wp.plugins;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const { Panel, PanelBody, PanelRow } = wp.components;

import {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl} from './metadata_components.js';

function recordChange(metaKey, metaValue) {
	console.log(metaKey + ': ', metaValue);
}

function related_link() {
	if(rsvpmaker_ajax.special)
		{
		return <div class="rsvp_related_links"></div>;
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
<Panel header="RSVPMaker Event Options">
<PanelBody
            title="Set Basic Options"
            icon="calendar-alt"
            initialOpen={ true }
        >
{(!rsvpmaker_ajax.special && !rsvpmaker_ajax.template_msg && (rsvpmaker_ajax._rsvp_count == '1') && <div><MetaDateControl metaKey='_rsvp_dates' />
<MetaSelectControl
		label="Time Display"
		metaKey="_firsttime"
		options={ [
			{ label: 'End Time Not Displayed', value: '' },
			{ label: 'Show End Time', value: 'set' },
			{ label: 'Add Day / Do Not Show Time', value: 'allday' },
		] }
	/>
	<MetaEndDateControl /></div>
)}
{(rsvpmaker_ajax._rsvp_count > '1') && <PanelRow><a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Multiple Dates')}</a></PanelRow>}
{(rsvpmaker_json.projected_url && <div><PanelRow>
<a href={rsvpmaker_ajax.rsvpmaker_details} target="_blank">{__('Edit Template Schedule')}</a></PanelRow>
<PanelRow><a href={rsvpmaker_json.projected_url} target="_blank">{__('Create/Update Events from Template')}</a><br />
</PanelRow>
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
</PanelBody>
<PanelBody
            title="Display"
            icon="admin-settings"
            initialOpen={ false }
        >
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

</PanelBody>
        <PanelBody
            title="Notifications / Reminders"
            icon="email"
            initialOpen={ false }
        >
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
            <PanelRow>Confirmation Message (exerpt): {rsvpmaker_ajax.confirmation_excerpt}</PanelRow>
			<PanelRow><em>{rsvpmaker_ajax.confirmation_type}</em></PanelRow>
<PanelRow><a href={rsvpmaker_ajax.confirmation_edit} target="_blank">{__('Edit Confirmation Message')}</a></PanelRow>
{(rsvpmaker_ajax.confirmation_type != '') && <PanelRow><a href={rsvpmaker_ajax.confirmation_customize} target="_blank">{__('Customize Confirmation Message')}</a></PanelRow>}
<PanelRow><a href={rsvpmaker_ajax.reminders} target="_blank">{__('Create / Edit Reminders')}</a></PanelRow>
        </PanelBody>
        <PanelBody
            title="RSVP Form"
            icon="yes-alt"
            initialOpen={ false }
        >
		<PanelRow>{rsvpmaker_ajax.form_fields}</PanelRow>
		<PanelRow><em>{rsvpmaker_ajax.form_type}</em></PanelRow>
		<PanelRow><a href={rsvpmaker_ajax.form_edit} target="_blank">{__('Edit Form')}</a></PanelRow>
		{((rsvpmaker_ajax.form_type != '') || !rsvpmaker_ajax.form_edit_post) && <PanelRow><a href={rsvpmaker_ajax.form_customize} target="_blank">{__('Customize Form')}</a></PanelRow>}
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

<MetaTextControl
		title="Maximum number of participants (0 for no limit)"
		metaKey="_rsvp_max"
	/>
		</PanelBody>
</Panel>

<div>For additional options, including multiple dates and event pricing see: {related_link()}</div>
        </PluginSidebar>
		</Fragment>
    )
}
if ((typeof rsvpmaker_ajax !== 'undefined') && !rsvpmaker_ajax.special) 
	registerPlugin( 'plugin-rsvpmaker', { render: PluginRSVPMaker } );
