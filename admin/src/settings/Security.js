import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import { SanitizedHTML } from "./SanitizedHTML.js";
import {useSaveControls} from './SaveControls';
import { OptionsToggle,OptSelect} from "./OptionControls.js";

export default function Security (props) {
    const {data,isLoading,isError} = useOptions('security');
    if(isError)
        return <p>Error loading security options</p>
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();
    const {changes,addChange,setChanges} = props;
    const {mutate:setOption} = useOptionsMutation(setChanges,makeNotification);
    const secoptions = [{'value':'manage_options','label':'Administrator (manage_options)'},{'value':'edit_others_rsvpmakers','label':'Editor (edit_others_rsvpmakers)'},{'value':'publish_rsvpmakers','label':'Author (publish_rsvpmakers)'},{'value':'edit_rsvpmakers','label':'Contributor (edit_rsvpmakers)'}];

    if(isLoading)
        return <p>Loading ...</p>
    const rsvp_options = data.data.rsvp_options;
    const current_user_id = data.data.current_user_id;
    const current_user_email = data.data.current_user_email;
    //const edit_url = data.data.edit_url;
    //const confirmation_message = data.data.confirmation_message;
    //const stylesheet_url = data.data.stylesheet_url;

    return <div className="rsvptab">
    {isSaving && <h1>Saving ...</h1>}
    <div className={(isSaving) ? "rsvptab-saving": ""}>
    <div className="security-select">
    <OptSelect addChange={addChange} rsvp_options={rsvp_options} label="RSVP Report" slug="menu_security" options={secoptions} />
    <OptSelect addChange={addChange} rsvp_options={rsvp_options} label="Event Templates" slug="rsvpmaker_template" options={secoptions} />
    <OptSelect addChange={addChange} rsvp_options={rsvp_options} label="Recurring Event" slug="recurring_event" options={secoptions} />
    <OptSelect addChange={addChange} rsvp_options={rsvp_options} label="Documentation" slug="documentation" options={secoptions} />
    </div>
    <div>
    <OptionsToggle addChange={addChange} rsvp_options={rsvp_options} label="Additional Editors" slug="additional_editors" />
    <p>Enabling Additional Editors/Co-Authors allows users to share editing rights for event templates and related events.</p>
	<p><strong>How this works: </strong> When this function is enabled, event authors have the option of allowing other users to be additional editors or co-authors for an event or a series of events based  on a template. This is useful for community websites where multiple organizations post their events. The organization can appoint multiple officers or representatives to have equal rights to update the events template for their meetings and all the individual events based on that template.</p>
	<p>Note that to unlock events for editing, RSVPMaker changes the author ID for a post to the ID of the authorized user editing the post.</p>		
    </div>
    </div>
    <SaveControls changes={changes} setChanges={setChanges} />
   </div>
}