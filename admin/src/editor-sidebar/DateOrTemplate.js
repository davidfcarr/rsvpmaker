import React, {useState} from "react";
import DateTimeMaker from '../DateTimeMaker.js';
import TemplateControl from './TemplateControl.js';
import Setup from './Setup.js';
import {useRSVPDate} from '../queries.js'

export default function DateOrTemplate() {
    const initialPostStatus = wp?.data?.select( 'core/editor' ).getEditedPostAttribute( 'status' );
    const url = window.location.href;
    const tabarg = url.match(/tab=([^&]+)/);
    const tab = (tabarg) ? tabarg[1] : 'basics';
    const [openModal,setOpenModal] = useState(('draft' == initialPostStatus) || ('auto-draft' == initialPostStatus) || ((tabarg) && tabarg[1]));
    const event_id = wp?.data?.select("core/editor").getCurrentPostId();
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