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
import {MetaTextControl, MetaSelectControl, MetaTextareaControl, MetaFormToggle} from './metadata_components.js';
import Setup from './Setup.js';
import DateOrTemplate from './DateOrTemplate.js';
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();


const PluginRSVPMaker2023 = () => {
    return(
		<Fragment>
		<PluginSidebarMoreMenuItem target="plugin-rsvpmaker-extra" icon="calendar-alt">RSVPMaker</PluginSidebarMoreMenuItem>
        <PluginSidebar
            name='plugin-rsvpmaker-extra-2023'
            title='RSVPMaker 2023'
            icon="calendar"
        >
<Panel header={__('RSVPMaker Event Options','rsvpmaker')}>
<PanelBody
            title={__("Set Basic Options",'rsvpmaker')}
            icon="calendar-alt"
            initialOpen={ true }
        >
<QueryClientProvider client={queryClient}>
<DateOrTemplate />
</QueryClientProvider>
<p><MetaFormToggle
label="Collect RSVPs" 
metaKey="_rsvp_on"/></p>
</PanelBody>
<PanelBody
            title="Related"
            icon="admin-links"
            initialOpen={ false }
        >
<ul>
<li><a href={wp?.data?.select('core/editor').getPermalink()}>{__('View Event','rsvpmaker')}</a></li>
{rsvpmaker_ajax.related_document_links.map( function (x) {return <li class={x.class}><a href={x.href}>{x.title}</a></li>} )}
</ul>
</PanelBody>

<PanelBody
            title="Display"
            icon="admin-settings"
            initialOpen={ false }
        >
<MetaFormToggle
label={__('"Show Add to Google/Outlook Calendar Icons" ','rsvpmaker')}
metaKey="_calendar_icons"/>

<MetaFormToggle
		label={__("Add Timezone to Date",'rsvpmaker')}
		metaKey="_add_timezone"
	/>
<MetaFormToggle
label={__("Show Timezone Conversion Button",'rsvpmaker')}
metaKey="_convert_timezone"/>

<MetaFormToggle
label={__("Show RSVP Count",'rsvpmaker')} 
metaKey="_rsvp_count"/>

<MetaSelectControl
		label={__("Display attendee names / RSVP note field",'rsvpmaker')}
		metaKey="_rsvp_show_attendees"
		options={ [
			{ label: 'No', value: '0' },
			{ label: 'Yes', value: '1' },
			{ label: 'Only for Logged In Users', value: '2' },
		] }
	/>

</PanelBody>
        <PanelBody
            title={__("Notifications / Reminders",'rsvpmaker')}
            icon="email"
            initialOpen={ false }
        >
			<MetaTextControl title={__("Send notifications to:",'rsvpmaker')} metaKey="_rsvp_to" />
		<MetaFormToggle
		label={__("Send Confirmation Email",'rsvpmaker')}
		metaKey="_rsvp_rsvpmaker_send_confirmation_email"
	/>
	<MetaFormToggle
		label={__("Confirm AFTER Payment",'rsvpmaker')}
		metaKey="_rsvp_confirmation_after_payment"
	/>
<MetaFormToggle
		label={__('"Include Event Content with Confirmation"','rsvpmaker')}
		metaKey="_rsvp_confirmation_include_event"
	/>
            <PanelRow>{__('Confirmation Message (exerpt)','rsvpmaker')}: {rsvpmaker_ajax.confirmation_excerpt}</PanelRow>
{rsvpmaker_ajax.confirmation_links.map( function(x) {return <PanelRow><a href={x.href}>{x.title}</a></PanelRow>} )}
<p><em>To add reminders, click the RSVP / Event Options button above and select Confirmation and Reminder Messages.</em></p>
<PanelRow>
<MetaSelectControl
		label={__("Email Template for Confirmations",'rsvpmaker')}
		metaKey="rsvp_tx_template"
		options={ rsvpmaker_ajax.rsvp_tx_template_choices }
	/>
</PanelRow>
<div>Venue:<br />
<MetaTextControl title={__("Venue",'rsvpmaker')} metaKey="venue" />
<br /><em>{__('A street address or web address to include on the calendar invite attachment included with confirmations. If not specifed, RSVPMaker includes a link to the event post.','rsvpmaker')}</em></div>
</PanelBody>
        <PanelBody
            title={__("RSVP Form",'rsvpmaker')}
            icon="yes-alt"
            initialOpen={ false }
        >
		<PanelRow>{rsvpmaker_ajax.form_fields}</PanelRow>
		<PanelRow><em>{rsvpmaker_ajax.form_type}</em></PanelRow>
		<MetaFormToggle
		label={__("Login required to RSVP",'rsvpmaker')}
		metaKey="_rsvp_login_required"
	/>

<MetaFormToggle
		label={__("Captcha security challenge",'rsvpmaker')}
		metaKey="_rsvp_captcha"
	/>

<MetaFormToggle
		label={__("Show Yes/No Options on Registration Form",'rsvpmaker')}
		metaKey="_rsvp_yesno"
	/>
<MetaFormToggle
		label={__("Show Date and Time on Form",'rsvpmaker')}
		metaKey='_rsvp_form_show_date'
	/>
<MetaTextControl
		label={__('Maximum number of participants (0 for no limit)','rsvpmaker')}
		metaKey="_rsvp_max"
	/>
<MetaTextareaControl
		label={__('Form Instructions for User','rsvpmaker')}
		metaKey="_rsvp_instructions"
/>

<p>Use the Show Setup Guide button, above - to edit the form without leaving this screen. Or use the link(s) below to open a form in the WordPress editor.</p>
{rsvpmaker_ajax.form_links.map( function(x) {return <PanelRow><a href={x.href}>{x.title}</a></PanelRow>} )}

		</PanelBody>
</Panel>
        </PluginSidebar>
		</Fragment>
    )
}

if (((rsvpmaker.post_type == 'rsvpmaker') || (rsvpmaker.post_type == 'rsvpmaker_template')) && !rsvpmaker_ajax.special) 
	registerPlugin( 'plugin-rsvpmaker-2023', { render: PluginRSVPMaker2023 } );
