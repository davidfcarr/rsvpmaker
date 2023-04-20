import React, {useState} from "react"
const { Modal, TabPanel, Guide,GuidePage,ToggleControl,Panel, PanelBody, PanelRow, Flex, FlexBlock, FlexItem } = wp.components;
import {MetaDateControl, MetaEndDateControl, MetaTextControl, MetaSelectControl, MetaTextareaControl, MetaRadioControl, MetaFormToggle, MetaTimeLord, MetaTimestampControl} from './metadata_components.js';
import DateTimeMaker from "../DateTimeMaker.js";
import TemplateControl from './TemplateControl.js';
import FormSetup from './FormSetup.js'
import Confirmation from './Confirmation.js';
import Pricing from './Pricing.js';
const { __ } = wp.i18n; // Import __() from wp.i18n

const onSelect = ( tabName ) => {
    console.log( 'Selecting tab', tabName );
};

export default function Setup (props) {
    const [ isOpen, setOpen ] = useState( props.open );
    const {tab,eventdata} = props;
    const start = (tab) ? tab : 'basics';
    console.log('setup start',start);

    function close() {
        setOpen(false);
        if(props.setOpenModal)
            props.setOpenModal(false);
    }
    function open() {
        setOpen(true);
        if(props.setOpenModal)
            props.setOpenModal(true);
    }

return (
    <div>
    {!isOpen && <p><button onClick={open} >RSVP / Event Options</button><br /><em>Click to see more event options</em></p>}
    {isOpen && <Modal className="rsvpmaker-setup-modal" title="RSVP / Event Options" onRequestClose={ close } >

        <TabPanel
            className="rsvpmaker-tab-panel"
            activeClass="is-active"
            onSelect={ onSelect }
            initialTabName={start}
            tabs={ [
                {
                    name: 'basics',
                    title: 'Basic Settings',
                    className: 'rsvpmaker-modal-tab',
                },
                {
                    name: 'form',
                    title: 'RSVP Form',
                    className: 'rsvpmaker-modal-tab',
                },
                {
                    name: 'confirmation',
                    title: 'Confirmation and Reminder Messages',
                    className: 'rsvpmaker-modal-tab',
                },
                {
                    name: 'pricing',
                    title: 'Pricing',
                    className: 'rsvpmaker-modal-tab',
                },
            ] }
        >
            { ( tab ) => {
                if('basics' == tab.name)
                    return <div className="rsvpsettings-tab-contents"><Basics eventdata={eventdata} /></div>
                else if('form' == tab.name)
                    return <div className="rsvpsettings-tab-contents"><Form form_id={rsvpmaker_ajax.form_id} event_id={rsvpmaker_ajax.event_id} /></div>
                else if('confirmation' == tab.name)
                    return (
                        <div className="rsvpsettings-tab-contents"><Confirmation /></div>
                    )
                    else if('pricing' == tab.name)
                    return (
                        <div className="rsvpsettings-tab-contents"><Pricing /></div>
                    )
        } }
        </TabPanel>
        <div><button onClick={close}>Close</button></div>
        </Modal>}
    </div>
)

}

function Form() {
    return (<div>
                <div>{rsvpmaker_ajax.form_fields}</div>
<div><em>{rsvpmaker_ajax.form_type}</em></div>
<div>
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
<MetaTimestampControl label="Registration Start Date (optional)" metaKey="_rsvp_start" />
<MetaTimestampControl label="Registration Deadline (optional)" metaKey="_rsvp_deadline" />
</div>
<FormSetup />
    </div>
    );
}

function Reminders() {
    return <div>Form controls go here</div>
}

function Basics(props) {
    return (
        <div className="guide-page-1-columns">
        <div className="rsvpguide-datetime">
        {(rsvpmaker.post_type == 'rsvpmaker') && <DateTimeMaker event_id={rsvpmaker_rest.post_id} eventdata={props.eventdata} />}
        {(rsvpmaker.post_type == 'rsvpmaker_template') && <TemplateControl />}
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
<PanelRow><a href={rsvpmaker_ajax.reminders} >{__('Create / Edit Reminders')}</a></PanelRow>

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
<li><a href={wp.data.select('core/editor').getPermalink()}>{__('View Event','rsvpmaker')}</a></li>
{rsvpmaker_ajax.related_document_links.map( function (x) {return <li class={x.class}><a href={x.href}>{x.title}</a></li>} )}
</ul>
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
    )
}