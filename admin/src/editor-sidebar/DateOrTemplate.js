import React, {useState, useEffect} from "react";
import DateTimeMaker from '../DateTimeMaker.js';
import TemplateControl from './TemplateControl.js';
import Setup from './Setup.js';
import {useRSVPDate} from '../queries.js';
import { setupNonceInterceptor } from '../http-common.js';
import { useRsvpmakerRest } from '../useRsvpmakerRest.js';

export default function DateOrTemplate() {

    const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
    const postmeta = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );
    const tab = 'basics';
    const [openModal,setOpenModal] = useState(('draft' == initialPostStatus) || ('auto-draft' == initialPostStatus) || postmeta?._show_rsvpmaker_options);
    const event_id = wp?.data?.select("core/editor").getCurrentPostId();
    const rsvpmaker_rest = useRsvpmakerRest();
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
    
    return (
<div className="date-or-template">
{rsvpmaker_ajax.top_message}
<Setup open={openModal} setOpenModal={setOpenModal} tab={tab}  eventdata={eventdata} /> 
{(!rsvpmaker_ajax.special && (rsvpmaker.post_type == 'rsvpmaker') && 
<div>
{!openModal && (rsvpmaker.post_type == 'rsvpmaker') && <DateTimeMaker event_id={event_id} eventdata={eventdata} />}
</div>
)}
{((rsvpmaker.post_type == 'rsvpmaker_template') && <TemplateControl event_id={event_id} eventdata={eventdata} />
)}
</div>
);
}