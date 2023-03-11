import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import { SanitizedHTML } from "./SanitizedHTML.js";

export default function General (props) {
    const {data,isLoading} = useOptions();
    const [changes,setChanges] = useState([]);
    const {mutate:setOption} = useOptionsMutation(() => {setChanges([]); makeNotification('Updated');});
    const [notification,setNotification] = useState(null);
    const [notificationTimeout,setNotificationTimeout] = useState(null);
    const [isSaving,setIsSaving] = useState(false);

    function makeNotification(message) {
        if(notificationTimeout)
            clearTimeout(notificationTimeout);
        setNotification({'message':message});
        let nt = setTimeout(() => {
            setNotification(null);
            setIsSaving(false);
        },5000);
        setNotificationTimeout(nt);
    }

    if(isLoading)
        return <p>Loading ...</p>

    function addChange(key,value, type='rsvp_options') {
        setChanges((ch) => {
            const exists = ch.findIndex( (item) => item.key==key );
            if(exists > -1)
                ch[exists].value = value;
            else
                ch.push({'key':key,'value':value,'type':type});      
            setChanges(ch)});
    }
    
    const rsvp_options = data.data.rsvp_options;
    const current_user_id = data.data.current_user_id;
    const current_user_email = data.data.current_user_email;
    const edit_url = data.data.edit_url;
    const confirmation_message = data.data.confirmation_message;
    const stylesheet_url = data.data.stylesheet_url;

    function OptionsToggle (props) {
        const [on,setOn] = useState(rsvp_options[props.slug] == 1);
        return (
        <ToggleControl label={props.label} 
        checked={on} onChange={() => { let value = !on; setOn(value); addChange(props.slug,(value) ? 1 : 0); }}
        />)
    }

    function OptRadio (props) {
        const [choice,setChoice] = useState(rsvp_options[props.slug]);
        console.log('choice',choice);
        return (
        <RadioControl label={props.label} 
        selected={choice} options={props.options} onChange={(value) => { setChoice(value); addChange(props.slug,value);}}
        />)
    }

    function OptText (props) {
        const [text,setText] = useState(rsvp_options[props.slug]);
        return (
        <>
        <input type="text" value={text} onChange={(e) => {setText(e.target.value); console.log(e.target.value)}} onBlur={() => {addChange(props.slug,text); } } />
        </>)
    }

    function OptTextArea (props) {
        const [text,setText] = useState(rsvp_options[props.slug]);
        return (
        <>
        <textarea type="text" value={text} onChange={(e) => {setText(e.target.value); console.log(e.target.value)}} onBlur={() => { addChange(props.slug,text); } } />
        </>)
    }

    function Time (props) {
        const [time,setTime] = useState(props.time);
        // console.log('newtime',e.target.value); console.log('newtime state',time)
        return (
        <>
        <input type="time" value={time} onChange={(e) => {setTime(e.target.value);}} onBlur={() => {let split = time.split(':'); setIsSaving(true); setOption([{'key':'defaulthour','value':split[0],'type':'rsvp_options'},{'key':'defaultmin','value':split[1],'type':'rsvp_options'}]); } } />
        </>)
    }
// onBlur={(e) => {let value = e.target.value; let split = value.split(':'); console.log('value',value); console.log('split',split); setOption([{'key':'defaulthour','value':split[0]},{'key':'defaultmin','value':split[1]}])} }
    console.log('rsvp options',rsvp_options);
    const optionsArray = Object.entries(rsvp_options);
    if(rsvp_options.defaultmin.toString().length < 2)
        rsvp_options.defaultmin = rsvp_options.defaultmin+'0';
    return (<div className="rsvptab">
    {isSaving && <h1>Saving ...</h1>}
    <div className={(isSaving) ? "rsvptab-saving": ""}>
    <h3>Defaults for New Events</h3>
    <p>Start Time: <Time time={rsvp_options.defaulthour+':'+rsvp_options.defaultmin} /> </p>
    <OptionsToggle label="Collect RSVPs" slug="rsvp_on" />
    <OptionsToggle label="Display Add to Calendar Icons" slug="calendar_icons" />
    <OptionsToggle label="Add Timezone" slug="add_timezone" />
    <OptionsToggle label="Show Timezone Conversions" slug="convert_timezone" />
    <OptionsToggle label="Include Time in Title for Facebook and Twitter" slug="social_title_date" />
    <OptionsToggle label="Show Yes/No Radio Buttons on Form" slug="rsvp_yesno" />
    <OptionsToggle label="Show Members Not Registered (for membership sites where members have an account)" slug="missing_members" />
    <OptionsToggle label="Show Attendee Names Publicly" slug="show_attendees" />
    <OptionsToggle label="Show RSVP Count Publicly" slug="rsvp_count" />
    <OptionsToggle label="Login Required to Register" slug="login_required" />
    <p>Default Email for RSVP Notifications <OptText slug="rsvp_to" value={rsvp_options.rsvp_to} /></p>
    <OptionsToggle label="Email RSVPs To Event Author Instead" slug="rsvp_to_current" />
    <OptionsToggle label="Simple Captcha on RSVP Form" slug="rsvp_captcha" />
    <p><strong>Or use Google ReCaptcha (v2) </strong> <a href="https://www.google.com/recaptcha/admin" target="_blank">register</a> for API key and secret
    <br />Key <OptText slug="rsvp_recaptcha_site_key" /> Secret <OptText slug="rsvp_recaptcha_secret" /></p>    

    <h3>Instructions for RSVP Form</h3>
    <p>
    <OptTextArea slug="rsvp_instructions" />
    </p>
    <h3>Confirmation Message</h3>
    <SanitizedHTML innerHTML={confirmation_message} />
    <p><a target="_blank" href={edit_url + rsvp_options.rsvp_confirm}>Edit Message</a></p>

    <p><label>RSVP Button</label><br />
    <OptTextArea slug="rsvplink" />
    </p>

    <p><label>Update Button Title</label>
    <OptText slug="update_rsvp" />
    </p>

    <p><label>RSVP Form Title</label>
    <OptText slug="rsvp_form_title" /></p>
    <h3>Date and Time Formats</h3>
    <p><a target="_blank" href="https://www.php.net/manual/en/datetime.format.php">find codes here</a> <br />Examples:<br />
l F j, Y = Thursday March 9, 2023<br />j F Y = 9 March 2023<br />m-d-Y = 03-09-2023</p>
    <p><label>Date (long)</label>
    <OptText slug="long_date" /></p>
    <p><label>Date (short)</label>
    <OptText slug="short_date" /></p>
    <OptRadio label="Time Format" slug="time_format" options={[{'label':'12 hour AM/PM','value':'g:i A'},{'label':'24 hour','value':'H:i'},{'label':'12 hour AM/PM with timezone','value':'g:i A T'},{'label':'24 hour with timezone','value':'H:i T'}]} />

    <p><label>Custom CSS</label> <OptText slug="custom_css" />
	<br /><em>Option to provide the url to a stylesheet that will override the standard styles from <br /><a href={stylesheet_url} target="_blank">{stylesheet_url}</a></em></p>

    <OptRadio slug="dashboard" label="Dashboard Widget" options={[{'label':'None','value':''},{'label':'Show on Dashboard','value':'show'},{'label':'Show on Top','value':'top'}]} />
    <p><label>Message for Dashboard</label><br />
    <OptTextArea slug="dashboard_message" />
    </p>
    <OptionsToggle label="Troubleshooting and Logging" slug="debug" />
    <p>Turning this on may be helpful to identify bugs in the software.</p>

    <h3>Template Behavior</h3>
    <OptionsToggle label="Auto-Renew Events" slug="autorenew" />
    <p><em>If this is active, events based on a template will automatically be added according to the defined schedule.</em></p>
    
    </div>

<div id="savecontrols">
{notification && <div className="rsvp-notification rsvp-notification-success">{notification.message}</div>}
<div id="savebuttonwrapper"><button onClick={() => {setIsSaving(true);setOption(changes)}}>Save</button></div>
</div>
    
    </div>)
}
