import React, {useState} from "react"
const { Modal, TabPanel, Guide,GuidePage,ToggleControl,Panel, PanelBody, PanelRow, Flex, FlexBlock, FlexItem } = wp.components;
import {RSVPTimestampControl, RSVPMetaToggle, MetaDateControl, MetaEndDateControl, MetaTextControl, MetaSelectControl, MetaTextareaControl, MetaRadioControl, MetaFormToggle, MetaTimeLord, MetaTimestampControl} from './metadata_components.js';
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

    if(eventdata.status) {
        wp.data.dispatch('core/notices').createNotice(
            'success', // Can be one of: success, info, warning, error.
            eventdata.status, // Text string to display.
            {
                id: 'rsvpemialnowsnack', //assigning an ID prevents the notice from being added repeatedly
                isDismissible: true, // Whether the user can dismiss the notice.
                // Any actions the user can perform.
                type: 'snackbar',
            }
        );    
    }
    
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
    <div>{eventdata.status}</div>

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
                    return <div className="rsvpsettings-tab-contents"><div className="modal-save"><button onClick={close}>Done</button></div><p><em>{__('Set the date, time, and RSVP parameters for your event, then click Done. Access these options at any time from the RSVP / Event Options button in the sidebar.','rsvpmaker')}</em></p><Basics eventdata={eventdata} /><div></div></div>
                else if('form' == tab.name)
                    return <div className="rsvpsettings-tab-contents"><div className="modal-save"><button onClick={close}>Done</button></div><Form form_id={eventdata.form_id} event_id={rsvpmaker_ajax.event_id} eventdata={eventdata} /></div>
                else if('confirmation' == tab.name)
                    return (
                        <div className="rsvpsettings-tab-contents"><div className="modal-save"><button onClick={close}>Done</button></div><Confirmation eventdata={eventdata} /></div>
                    )
                    else if('pricing' == tab.name)
                    return (
                        <div className="rsvpsettings-tab-contents"><div className="modal-save"><button onClick={close}>Done</button></div><Pricing eventdata={eventdata} /></div>
                    )
        } }
        </TabPanel>
        <div><button onClick={close}>Done</button></div>
        </Modal>}
    </div>
)

}

function Form(props) {
    const {eventdata} = props;
    return (<div>
                <div>{rsvpmaker_ajax.form_fields}</div>
<div><em>{rsvpmaker_ajax.form_type}</em></div>
<div>
<RSVPMetaToggle
label={__("Login required to RSVP",'rsvpmaker')}
metaKey="_rsvp_login_required"
eventdata={eventdata}
/>
<RSVPMetaToggle
 eventdata={eventdata}
label={__("Captcha security challenge",'rsvpmaker')}
metaKey="_rsvp_captcha"
/>
<RSVPMetaToggle
 eventdata={eventdata}
label={__("Show Yes/No Options on Registration Form",'rsvpmaker')}
metaKey="_rsvp_yesno"
/>
<RSVPMetaToggle
 eventdata={eventdata}
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
{(rsvpmaker.post_type == 'rsvpmaker') && <RSVPTimestampControl label="Registration Start Date (optional)" metaKey="_rsvp_start" eventdata={eventdata} />}
{(rsvpmaker.post_type == 'rsvpmaker') && <RSVPTimestampControl label="Registration Deadline (optional)" metaKey="_rsvp_deadline" eventdata={eventdata} />}
{(rsvpmaker.post_type == 'rsvpmaker_template') && <div>
<h3>Registration Start Date (optional)</h3>
<MetaTextControl
label={__('Start Date, Days Before','rsvpmaker')}
metaKey="_rsvp_reg_daysbefore"
/>
<MetaTextControl
label={__('Start Date, Hours Before','rsvpmaker')}
metaKey="_rsvp_reg_hours"
/>
<h3>Registration Deadline (optional)</h3>
<MetaTextControl
label={__('Deadline, Days Before','rsvpmaker')}
metaKey="_rsvp_deadline_daysbefore"
/>
<MetaTextControl
label={__('Deadline, Hours Before','rsvpmaker')}
metaKey="_rsvp_deadline_hours"
/>
</div>}

</div>
<FormSetup />
    </div>
    );
}

function Reminders() {
    return <div>Form controls go here</div>
}

function Basics(props) {
    const {eventdata} = props;
    return (
        <div className="guide-page-1-columns">
        <div className="rsvpguide-datetime">
        {(rsvpmaker.post_type == 'rsvpmaker') && <DateTimeMaker event_id={rsvpmaker_rest.post_id} eventdata={props.eventdata} />}
        {(rsvpmaker.post_type == 'rsvpmaker_template') && <TemplateControl  event_id={rsvpmaker_rest.post_id} eventdata={props.eventdata} />}
        </div>
        <div className="guide-options-column">
        <RSVPMetaToggle
label={__('Collect RSVPs','rsvpmaker')} 
metaKey="_rsvp_on" eventdata={props.eventdata} />
<p>RSVP Form: {eventdata.default_form && <span>Default</span>} {!eventdata.default_form && <span>Custom for event or template</span>}</p>
<p>Confirmation Message: {eventdata.default_confirmation && <span>Default</span>} {!eventdata.default_confirmation && <span>Custom for event or template</span>}</p>

<Panel>
<PanelBody
title="Display"
icon="admin-settings"
initialOpen={ true }
>
<RSVPMetaToggle
label={__('"Show Add to Google/Outlook Calendar Icons" ','rsvpmaker')}
metaKey="_calendar_icons" eventdata={eventdata}/>

<RSVPMetaToggle
label={__("Add Timezone to Date",'rsvpmaker')}
metaKey="_add_timezone"
eventdata={eventdata}
/>
<RSVPMetaToggle
label={__("Show Timezone Conversion Button",'rsvpmaker')}
metaKey="_convert_timezone" eventdata={eventdata}/>

<RSVPMetaToggle
label={__("Show RSVP Count",'rsvpmaker')} 
metaKey="_rsvp_count" eventdata={eventdata}/>

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
<RSVPMetaToggle
label={__("Send Confirmation Email",'rsvpmaker')}
metaKey="_rsvp_rsvpmaker_send_confirmation_email"
eventdata={eventdata}
/>
<RSVPMetaToggle
label={__("Confirm AFTER Payment",'rsvpmaker')}
metaKey="_rsvp_confirmation_after_payment"
eventdata={eventdata}
/>
<RSVPMetaToggle
label={__('"Include Event Content with Confirmation"','rsvpmaker')}
metaKey="_rsvp_confirmation_include_event"
eventdata={eventdata} />
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
</Panel>
</div>
</div>
    )
}