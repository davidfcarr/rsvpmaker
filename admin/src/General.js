import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import { SanitizedHTML } from "./SanitizedHTML.js";
import {useSaveControls} from './SaveControls';
import { OptionsToggle,OptRadio,OptSelect,OptText,OptTextArea } from "./OptionControls.js";

export default function General (props) {
    const {changes,addChange,setChanges} = props;
    const {data,isLoading,isError} = useOptions('general');
    if(isError)
        return <p>Error loading general options</p>
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();

    if(isLoading)
        return <p>Loading ...</p>
    const rsvp_options = data.data.rsvp_options;
    const current_user_id = data.data.current_user_id;
    const current_user_email = data.data.current_user_email;
    const edit_url = data.data.edit_url;
    const confirmation_message = data.data.confirmation_message;
    const stylesheet_url = data.data.stylesheet_url;

    function Time (props) {
        const {addChange} = props;
        const [time,setTime] = useState(props.time);
        // console.log('newtime',e.target.value); console.log('newtime state',time)
        return (
        <>
        <input type="time" value={time} onChange={(e) => {setTime(e.target.value);}} onBlur={() => {let split = time.split(':'); saveEffect(); addChange('defaulthour',split[0]); addChange('defaultmin',split[1]); } } />
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
    <p>Start Time: <Time addChange={addChange} time={rsvp_options.defaulthour+':'+rsvp_options.defaultmin} /> </p>
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Collect RSVPs" slug="rsvp_on" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Display Add to Calendar Icons" slug="calendar_icons" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Add Timezone" slug="add_timezone" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Show Timezone Conversions" slug="convert_timezone" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Include Time in Title for Facebook and Twitter" slug="social_title_date" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Show Yes/No Radio Buttons on Form" slug="rsvp_yesno" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Show Members Not Registered (for membership sites where members have an account)" slug="missing_members" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Show Attendee Names Publicly" slug="show_attendees" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Show RSVP Count Publicly" slug="rsvp_count" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Login Required to Register" slug="login_required" />
    <p>Default Email for RSVP Notifications <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="rsvp_to" value={rsvp_options.rsvp_to} /></p>
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Email RSVPs To Event Author Instead" slug="rsvp_to_current" />
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Simple Captcha on RSVP Form" slug="rsvp_captcha" />
    <p><strong>Or use Google ReCaptcha (v2) </strong> <a href="https://www.google.com/recaptcha/admin" target="_blank">register</a> for API key and secret
    <br />Key <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="rsvp_recaptcha_site_key" /> Secret <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="rsvp_recaptcha_secret" /></p>    

    <h3>Instructions for RSVP Form</h3>
    <p>
    <OptTextArea addChange={addChange} rsvp_options={rsvp_options} slug="rsvp_instructions" />
    </p>
    <h3>Confirmation Message</h3>
    <SanitizedHTML innerHTML={confirmation_message} />
    <p><a target="_blank" href={edit_url + rsvp_options.rsvp_confirm}>Edit Message</a></p>

    <h3>RSVP Button</h3>
    <p><a target="_blank" href={rsvp_options.rsvplink_edit}>Edit Button Text and Colors</a></p>

    <p><label className="textlabel">Update Button Title</label>
    <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="update_rsvp" />
    </p>

    <p><label className="textlabel">RSVP Form Title</label>
    <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="rsvp_form_title" /></p>
    <h3>Date and Time Formats</h3>
    <p><a target="_blank" href="https://www.php.net/manual/en/datetime.format.php">find codes here</a> <br />Examples:<br />
l F j, Y = Thursday March 9, 2023<br />j F Y = 9 March 2023<br />m-d-Y = 03-09-2023</p>
    <p><label className="textlabel">Date (long)</label>
    <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="long_date" /></p>
    <p><label className="textlabel">Date (short)</label>
    <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="short_date" /></p>
    <OptRadio addChange={addChange} rsvp_options={rsvp_options}  label="Time Format" slug="time_format" options={[{'label':'12 hour AM/PM','value':'g:i A'},{'label':'24 hour','value':'H:i'},{'label':'12 hour AM/PM with timezone','value':'g:i A T'},{'label':'24 hour with timezone','value':'H:i T'}]} />

    <p><label  className="textlabel">Custom CSS</label> <OptText addChange={addChange} rsvp_options={rsvp_options}  slug="custom_css" />
	<br /><em>Option to provide the url to a stylesheet that will override the standard styles from <br /><a href={stylesheet_url} target="_blank">{stylesheet_url}</a></em></p>

    <OptRadio addChange={addChange} rsvp_options={rsvp_options}  slug="dashboard" label="Dashboard Widget" options={[{'label':'None','value':''},{'label':'Show on Dashboard','value':'show'},{'label':'Show on Top','value':'top'}]} />
    <p><label>Message for Dashboard</label><br />
    <OptTextArea addChange={addChange} rsvp_options={rsvp_options} slug="dashboard_message" />
    </p>
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Troubleshooting and Logging" slug="debug" />
    <p>Turning this on may be helpful to identify bugs in the software.</p>

    <h3>Template Behavior</h3>
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options}  label="Auto-Renew Events" slug="autorenew" />
    <p><em>If this is active, events based on a template will automatically be added according to the defined schedule.</em></p>
    </div>

<SaveControls changes={changes} setChanges={setChanges} />
    
    </div>)
}
