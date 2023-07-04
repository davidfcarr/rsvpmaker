import {useState} from 'react';

import {RSVPMetaToggle, MetaEndDateControl, MetaSelectControl} from './metadata_components.js';
const { __ } = wp.i18n; // Import __() from wp.i18n
import RSVPTimePicker from './RSVPTimePicker.js';

export default function TemplateControl (props) {
	if(!props)
		return <p>Reloading ...</p>
	console.log('props for TemplateControl',props);
	const {eventdata, event_id} = props;

    return (<div>
        <h3>RSVPMaker Template {rsvpmaker_ajax.projected_url && <a href={rsvpmaker_ajax.projected_url}>(Create/Update)</a>}</h3>
			<div class="sked_frequency">
			<p class="varies"><RSVPMetaToggle
			label="Varies" 
			metaKey="_sked_Varies" eventdata={eventdata} /></p>
			<p class="weeknumber"><RSVPMetaToggle
			label="First" 
			metaKey="_sked_First"  eventdata={eventdata} /></p>
			<p class="weeknumber"><RSVPMetaToggle
			label="Second" 
			metaKey="_sked_Second" eventdata={eventdata} /></p>
			<p class="weeknumber"><RSVPMetaToggle
			label="Third" 
			metaKey="_sked_Third" eventdata={eventdata} /></p>
			<p class="weeknumber"><RSVPMetaToggle
			label="Fourth" 
			metaKey="_sked_Fourth"  eventdata={eventdata} /></p>
			<p class="weeknumber"><RSVPMetaToggle
			label="Last" 
			metaKey="_sked_Last" eventdata={eventdata} /></p>
			<p class="every"><RSVPMetaToggle
			label="Every" 
			metaKey="_sked_Every" eventdata={eventdata} /></p>
			</div>
			<p><RSVPMetaToggle
			label="Sunday" 
			metaKey="_sked_Sunday" eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Monday" 
			metaKey="_sked_Monday" eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Tuesday" 
			metaKey="_sked_Tuesday" eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Wednesday" 
			metaKey="_sked_Wednesday" eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Thursday" 
			metaKey="_sked_Thursday" eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Friday" 
			metaKey="_sked_Friday"  eventdata={eventdata} /></p>
			<p><RSVPMetaToggle
			label="Saturday" 
			metaKey="_sked_Saturday" eventdata={eventdata} /></p>
			
			<br />
			<RSVPTimePicker eventdata={eventdata} metaKey="_sked_start_time" label="Start Time" />
			<RSVPTimePicker eventdata={eventdata} metaKey="_sked_end" label="End Time" />
			<MetaEndDateControl type="template" statusKey="_sked_duration" timeKey="_sked_end" eventdata={eventdata} />
			<p><RSVPMetaToggle
		label="Auto Add Dates" 
		metaKey="rsvpautorenew"
		help="Automatically add dates according to this schedule"
		eventdata={eventdata}
		/></p>
        <RSVPMetaToggle
label={__('Collect RSVPs','rsvpmaker')} 
metaKey="_rsvp_on" eventdata={eventdata} event_id={event_id} />
    </div>)
}