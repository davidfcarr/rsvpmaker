import React, {useState} from "react"
const { Guide,GuidePage,ToggleControl,Panel, PanelBody, PanelRow, Flex, FlexBlock, FlexItem } = wp.components;
import {MetaDateControl, MetaEndDateControl, MetaTextControl, MetaSelectControl, MetaTextareaControl, MetaRadioControl, MetaFormToggle, MetaTimeLord, MetaEndDateTimeControl} from './metadata_components.js';
import DateTimeMaker from "../DateTimeMaker.js";
const { __ } = wp.i18n; // Import __() from wp.i18n

export default function RSVPGuide (props) {
    const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
    const [ isOpen, setOpen ] = useState( initialPostStatus == 'draft' );
return (
    <div>
    <ToggleControl label="Show RSVPMaker Guide" checked={isOpen} onClick={() => {setOpen((prev) => !prev)} } />
    {isOpen && <Guide className="rsvpmaker-setup-modal" onFinish={() => {setOpen(false)} }>
            <GuidePage className="rsvpguide-page">
                <h1>Event Setup Guide</h1>
                <p>This event guide will walk you through the basics of scheduling your event and setting RSVP options.</p>
                <div className="guide-page-1-columns">
                    <div className="rsvpguide-datetime">
                    <DateTimeMaker event_id={rsvpmaker_rest.post_id} />
                    </div>
                    <div className="guide-options-column">
                    <MetaFormToggle
label={__('Collect RSVPs','rsvpmaker')} 
metaKey="_rsvp_on"/>

<Panel>
<PanelBody
            title="Display"
            icon="admin-settings"
            initialOpen={ true }
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
            title={__("RSVP Form",'rsvpmaker')}
            icon="yes-alt"
            initialOpen={ false }
        >
		<PanelRow>{rsvpmaker_ajax.form_fields}</PanelRow>
		<PanelRow><em>{rsvpmaker_ajax.form_type}</em></PanelRow>
		{rsvpmaker_ajax.form_links.map( function(x) {return <PanelRow><a href={x.href}>{x.title}</a></PanelRow>} )}
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
		</PanelBody>
<PanelBody
	title={__("Pricing",'rsvpmaker')}
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
{
	(rsvpmaker_ajax.edit_payment_confirmation != '') && <p>See <strong>Confirmation/Notifications</strong> for paymment confirmation message.</p> 
}
{
	(rsvpmaker_ajax.edit_payment_confirmation == '') && <p>{__('Neither PayPal nor Stripe is active','rsvpmaker')}</p> 
}
</PanelBody>
</Panel>
</div>
</div>
            </GuidePage>
            <GuidePage>
            <h1>The RSVP Form</h1>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
            </GuidePage>
            <GuidePage>
            <h1>Confirmation and Reminders</h1>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
                <p>Page 2 content</p>
            </GuidePage>
        </Guide>}
        </div>
)

}