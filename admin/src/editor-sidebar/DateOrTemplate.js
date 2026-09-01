import React, {useState, useEffect} from "react";
import DateTimeMaker from '../DateTimeMaker.js';
import TemplateControl from './TemplateControl.js';
import Setup from './Setup.js';
import {useRSVPDate} from '../queries.js';
import { setupNonceInterceptor } from '../http-common.js';
import { useRsvpmakerRest } from '../useRsvpmakerRest.js';

export default function DateOrTemplate(props) {

    const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
    const postmeta = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );
    const tab = 'basics';
    const event_id = wp?.data?.select("core/editor").getCurrentPostId();
    const rsvpmaker_rest = props.rsvpmaker_rest;
    useEffect(() => {
        if (rsvpmaker_rest?.nonce) {
        setupNonceInterceptor(rsvpmaker_rest.nonce);
        }
    }, [rsvpmaker_rest?.nonce]);

    const {data,isLoading,isError} = useRSVPDate(event_id);
    if(isError)
        return <p>Error loading event date</p>

    if(isLoading) 
        return <p><em>Loading event data</em></p>
    const eventdata = data.data;
    if(typeof eventdata === 'string')
        return;
    if(!eventdata.tzchoices || !Array.isArray(eventdata.tzchoices))
        eventdata.tzchoices = [];
    const hasValidEventDate = Number.isFinite(Date.parse(eventdata?.date));
    return (
<div className="date-or-template">
{rsvpmaker_rest.top_message}
<Setup rsvpmaker_rest={rsvpmaker_rest} tab={tab}  eventdata={eventdata} /> 
{( (rsvpmaker_rest.post_type == 'rsvpmaker') && 
<div>
{hasValidEventDate && <DateTimeMaker rsvpmaker_rest={rsvpmaker_rest} event_id={event_id} eventdata={eventdata} />}
{!hasValidEventDate && <p><em>not a dated event</em></p>}
</div>
)}
{(rsvpmaker_rest.post_type == 'rsvpmaker_template') && <TemplateControl rsvpmaker_rest={rsvpmaker_rest} event_id={event_id} eventdata={eventdata} />}
</div>
);
}