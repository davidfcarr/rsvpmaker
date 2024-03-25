import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import {useSaveControls} from './SaveControls';
import { OptionsToggle,OptRadio,OptSelect,OptText,OptTextArea } from "./OptionControls.js";

export default function Email (props) {
    const {changes,addChange,setChanges} = props;
    const {data,isLoading,isError} = useOptions('general');
    if(isError)
        return <p>Error loading options</p>
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();
    const [chimp,setChimp] = useState(data.data.chimp);
    const [rsvp_options,setRSVP] = useState(data.data.rsvp_options);
    const [showChimp,setShowChimp] = useState(data.data.chimp['chimp-key'] != '');
    const [showSMTP,setShowSMTP] = useState(data.data.rsvp_options['smtp'] != '');
    const [chimpAdd,setChimpAdd] = useState(data.data.chimp['chimp_add_new_users']);
    const [chimpList,setChimpList] = useState(data.data.chimp['chimp-list']);
    const chimp_lists = [{'label':'None','value':''},{'label':'Test','value':'test'}];
    const smtp_options = [{'label':'None - use wp_mail()','value':''},{'label':'Custom','value':'other'},{'label':'Gmail','value':'gmail'},{'label':'Sendgrid','value':'sendgrid'}];

    if(isLoading)
        return <p>Loading ...</p>
    //const rsvp_options = data.data.rsvp_options;

    return <div className="rsvptab">
    <p>See <a href={data.data.mailing_list_settings} target="_blank">Mailing List Settings</a> for activation of the Postmark integration recommended for sending newsletters, promotional emails, and event invitations. Alternatively, you can use RSVPMaker's integration with Mailchimp. You can improve delivery of email messages using RSVPMaker's own SMTP servers support or an external SMTP plugin (not needed if Postmark is active).</p>

    <h3>Email Footer</h3>
    <TextControl label="Company/Organization" value={chimp['company']} onChange={(value) => {let prev = {...chimp}; prev['company']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    <TextControl label="Mailing Address" value={chimp['mailing_address']} onChange={(value) => {let prev = {...chimp}; prev['mailing_address']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    <p><em>Including a physical mailing address helps distinguish your email from spam.</em></p>
    {showChimp && (
    <>
    <h3>Mailchimp Configuration</h3>
    <TextControl label="Mailchimp API Key" value={chimp['chimp-key']} onChange={(value) => {let prev = {...chimp}; prev['chimp-key']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    <TextControl label="Email From Text" value={chimp['email-name']} onChange={(value) => {let prev = {...chimp}; prev['email-name']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    <TextControl label="Email From Address" value={chimp['email-from']} onChange={(value) => {let prev = {...chimp}; prev['email-from']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    <SelectControl className="chimplist" label="Default Maling List" value={chimpList} options={chimp_lists} onChange={(value) => {setChimpList(value); let prev = {...chimp}; prev['chimp-list']=value; setChimp(prev); addChange('chimp',prev,'mergearray'); } } />    
    <ToggleControl label="Add new WordPress user emails to mailing list" checked={chimpAdd} onChange={() => {setChimpAdd(!showChimp); let prev = {...chimp}; prev['chimp_add_new_users']=value; setChimp(prev); addChange('chimp',prev,'mergearray');} } />
    </>
    )}
    {!showChimp && <ToggleControl label="Show Mailchimp Integration Settings" checked={showChimp} onChange={() => {setShowChimp(!showChimp)} } />}

    {showSMTP && (
    <>
    <h3>SMTP Configuration</h3>
    <OptSelect addChange={addChange} rsvp_options={rsvp_options} label="SMTP Choice" slug="smtp" options={smtp_options} />
    <p>Email<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Email" slug="smtp_useremail" /></p>
    <p>Username<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Username" slug="smtp_username" /></p>
    <p>Password<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Password" slug="smtp_password" /></p>
    <p>Server<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Server" slug="smtp_server" /></p>
    <p>Prefix (ssl,tls)<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Prefix (ssl, tls)" slug="smtp_prefix" /></p>
    <p>Port<br /><OptText addChange={addChange} rsvp_options={rsvp_options}  label="Port" slug="smtp_port" /></p>
    <p><a href={data.data.smtp_test} target="_blank">Test SMTP Connection</a></p>
    <p>See <a href="http://www.wpsitecare.com/gmail-smtp-settings/">this article</a> for additional guidance on sending using Gmail (requires a tweak to security settings in your Google account).</p>        
    </>
    )}
    {!showSMTP && <ToggleControl label="Show SMTP Settings" checked={showSMTP} onChange={() => {setShowSMTP(!showSMTP)} } />}
    <SaveControls changes={changes} setChanges={setChanges} />

    </div>
}
