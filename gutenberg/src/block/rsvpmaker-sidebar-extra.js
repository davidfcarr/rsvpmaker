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
//import {TemplateTextControl} from './template-settings.js';  

import {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaTextareaControl, MetaFormToggle, MetaTemplateStartTimeControl} from './metadata_components.js';

function recordChange(metaKey, metaValue) {
	console.log(metaKey + ': ', metaValue);
}

//<!-- RSVPTemplate / -->
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
	<MetaEndDateControl /></div>
)}
{(rsvpmaker_ajax._rsvp_count > '1') && <PanelRow><a href={rsvpmaker_ajax.rsvpmaker_details} >{__('Edit Multiple Dates')}</a></PanelRow>}
{(rsvpmaker_ajax.projected_url && (rsvpmaker_ajax.complex_template == '') &&
<div>
<MetaSelectControl
		label="Week"
		metaKey="_week_of_month"
		options={ [
			{ label: 'Varies', value: '0' },
			{ label: 'First', value: '1' },
			{ label: 'Second', value: '2' },
			{ label: 'Third', value: '3' },
			{ label: 'Fourth', value: '4' },
			{ label: 'Last', value: '5' },
			{ label: 'Every', value: '6' },
		] }
	/>
<MetaSelectControl
		label="Day of Week"
		metaKey="_day_of_week"
		options={ [
			{ label: 'Not Set', value: '' },
			{ label: 'Sunday', value: '0' },
			{ label: 'Monday', value: '1' },
			{ label: 'Tuesday', value: '2' },
			{ label: 'Wednesday', value: '3' },
			{ label: 'Thursday', value: '4' },
			{ label: 'Friday', value: '5' },
			{ label: 'Saturday', value: '6' },
		] }
	/>
<MetaSelectControl
		label="Start Time (hour)"
		metaKey="_template_start_hour"
		options={ [
			{ label: '12 midnight', value: '00' },
			{ label: '1 am / 01:', value: '01' },
			{ label: '2 am / 02:', value: '02' },
			{ label: '3 am / 03:', value: '03' },
			{ label: '4 am / 04:', value: '04' },
			{ label: '5 am / 05:', value: '05' },
			{ label: '6 am / 06:', value: '06' },
			{ label: '7 am / 07:', value: '07' },
			{ label: '8 am / 08:', value: '08' },
			{ label: '9 am / 09:', value: '09' },
			{ label: '10 am / 10:', value: '10' },
			{ label: '11 am / 11:', value: '11' },
			{ label: '12 noon / 12:', value: '12' },
			{ label: '1 pm / 13:', value: '13' },
			{ label: '2 pm / 14:', value: '14' },
			{ label: '3 pm / 15:', value: '15' },
			{ label: '4 pm / 16:', value: '16' },
			{ label: '5 pm / 17:', value: '17' },
			{ label: '6 pm / 18:', value: '18' },
			{ label: '7 pm / 19:', value: '19' },
			{ label: '8 pm / 20:', value: '20' },
			{ label: '9 pm / 21:', value: '21' },
			{ label: '10 pm / 22:', value: '22' },
			{ label: '11 pm / 23:', value: '23' },
		] }
	/>
<MetaSelectControl
		label="Start Time (minutes)"
		metaKey="_template_start_minutes"
		options={ [
			{ label: '00', value: '00' },
			{ label: '01', value: '01' },
			{ label: '02', value: '02' },
			{ label: '03', value: '03' },
			{ label: '04', value: '04' },
			{ label: '05', value: '05' },
			{ label: '06', value: '06' },
			{ label: '07', value: '07' },
			{ label: '08', value: '08' },
			{ label: '09', value: '09' },
			{ label: '10', value: '10' },
			{ label: '11', value: '11' },
			{ label: '12', value: '12' },
			{ label: '13', value: '13' },
			{ label: '14', value: '14' },
			{ label: '15', value: '15' },
			{ label: '16', value: '16' },
			{ label: '17', value: '17' },
			{ label: '18', value: '18' },
			{ label: '19', value: '19' },
			{ label: '20', value: '20' },
			{ label: '21', value: '21' },
			{ label: '22', value: '22' },
			{ label: '23', value: '23' },
			{ label: '24', value: '24' },
			{ label: '25', value: '25' },
			{ label: '26', value: '26' },
			{ label: '27', value: '27' },
			{ label: '28', value: '28' },
			{ label: '29', value: '29' },
			{ label: '30', value: '30' },
			{ label: '31', value: '31' },
			{ label: '32', value: '32' },
			{ label: '33', value: '33' },
			{ label: '34', value: '34' },
			{ label: '35', value: '35' },
			{ label: '36', value: '36' },
			{ label: '37', value: '37' },
			{ label: '38', value: '38' },
			{ label: '39', value: '39' },
			{ label: '40', value: '40' },
			{ label: '41', value: '41' },
			{ label: '42', value: '42' },
			{ label: '43', value: '43' },
			{ label: '44', value: '44' },
			{ label: '45', value: '45' },
			{ label: '46', value: '46' },
			{ label: '47', value: '47' },
			{ label: '48', value: '48' },
			{ label: '49', value: '49' },
			{ label: '50', value: '50' },
			{ label: '51', value: '51' },
			{ label: '52', value: '52' },
			{ label: '53', value: '53' },
			{ label: '54', value: '54' },
			{ label: '55', value: '55' },
			{ label: '56', value: '56' },
			{ label: '57', value: '57' },
			{ label: '58', value: '58' },
			{ label: '59', value: '59' },
		] }
	/>
<MetaEndDateControl />
</div>
)}
{(rsvpmaker_ajax.projected_url && rsvpmaker_ajax.complex_template &&
<PanelRow>{rsvpmaker_ajax.complex_template}</PanelRow>
)}
{(rsvpmaker_ajax.projected_url && <div><PanelRow>
<a href={rsvpmaker_ajax.rsvpmaker_details}>{__('Edit Template Schedule')}</a></PanelRow>
<PanelRow><a href={rsvpmaker_ajax.projected_url}>{__('Create/Update Events from Template')}</a><br />
</PanelRow>
</div>
			)}

<p><MetaFormToggle
label="Collect RSVPs" 
metaKey="_rsvp_on"/></p>
</PanelBody>
<PanelBody
            title="Display"
            icon="admin-settings"
            initialOpen={ false }
        >
<MetaFormToggle
label="Show Add to Google/Outlook Calendar Icons" 
metaKey="_calendar_icons"/>

<MetaFormToggle
		label="Add Timezone to Date"
		metaKey="_add_timezone"
	/>
<MetaFormToggle
label="Show Timezone Conversion Button" 
metaKey="_convert_timezone"/>

<MetaFormToggle
label="Show RSVP Count" 
metaKey="_rsvp_count"/>

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
		<MetaFormToggle
		label="Send Confirmation Email"
		metaKey="_rsvp_rsvpmaker_send_confirmation_email"
	/>
<MetaFormToggle
		label="Include Event Content with Confirmation"
		metaKey="_rsvp_confirmation_include_event"
	/>
            <PanelRow>Confirmation Message (exerpt): {rsvpmaker_ajax.confirmation_excerpt}</PanelRow>
			<PanelRow><em>{rsvpmaker_ajax.confirmation_type}</em></PanelRow>
<PanelRow><a href={rsvpmaker_ajax.confirmation_edit}>{__('Edit Confirmation Message')}</a></PanelRow>
{(rsvpmaker_ajax.confirmation_type != '') && <PanelRow><a href={rsvpmaker_ajax.confirmation_customize} >{__('Customize Confirmation Message')}</a></PanelRow>}
<PanelRow><a href={rsvpmaker_ajax.reminders} >{__('Create / Edit Reminders')}</a></PanelRow>

<PanelRow>
<MetaSelectControl
		label="Email Template for Confirmations"
		metaKey="rsvp_tx_template"
		options={ rsvpmaker_ajax.rsvp_tx_template_choices }
	/>
</PanelRow>

        </PanelBody>
        <PanelBody
            title="RSVP Form"
            icon="yes-alt"
            initialOpen={ false }
        >
		<PanelRow>{rsvpmaker_ajax.form_fields}</PanelRow>
		<PanelRow><em>{rsvpmaker_ajax.form_type}</em></PanelRow>
		<PanelRow><a href={rsvpmaker_ajax.form_edit} >{__('Edit Form')}</a></PanelRow>
		{((rsvpmaker_ajax.form_type != '') || !rsvpmaker_ajax.form_edit_post) && <PanelRow><a href={rsvpmaker_ajax.form_customize} >{__('Customize Form')}</a></PanelRow>}
		<MetaFormToggle
		label="Login required to RSVP"
		metaKey="_rsvp_login_required"
	/>

<MetaFormToggle
		label="Captcha security challenge"
		metaKey="_rsvp_captcha"
	/>

<MetaFormToggle
		label="Show Yes/No Options on Registration Form"
		metaKey="_rsvp_yesno"
	/>

<MetaTextControl
		label="Maximum number of participants (0 for no limit)"
		metaKey="_rsvp_max"
	/>
<MetaTextareaControl
		label="Form Instructions for User"
		metaKey="_rsvp_instructions"
/>
		</PanelBody>
<PanelBody
	title="Pricing"
	icon="smiley"
	initialOpen={ false }
>
{(rsvpmaker_ajax.complex_pricing != '') && 
<PanelRow>{rsvpmaker_ajax.complex_pricing}</PanelRow>
}
{(rsvpmaker_ajax.complex_pricing == '') && 
<div>
<MetaTextControl
		label={__("Label for Payments")}
		metaKey="simple_price_label"
	/>
<MetaTextControl
		label={__("Price")}
		metaKey="simple_price"
/>
</div>
}
</PanelBody>
</Panel>

<div>For additional options, including multiple dates and complex event pricing see: {related_link()}</div>
        </PluginSidebar>
		</Fragment>
    )
}
if ((typeof rsvpmaker_ajax !== 'undefined') && !rsvpmaker_ajax.special) 
	registerPlugin( 'plugin-rsvpmaker', { render: PluginRSVPMaker } );
