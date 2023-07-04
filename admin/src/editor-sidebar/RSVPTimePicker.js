import React, { useState } from 'react';
import {useRSVPDateMutation} from '../queries'

export default function RSVPTimePicker(props) {
    const {eventdata,metaKey,label} = props;
    const {meta} = eventdata;
    const {mutate:datemutate} = useRSVPDateMutation(eventdata.event);
    const [time,setTime] = useState(meta[metaKey]);
    const [status,setStatus] = useState('');
    return (
    <div>
    <p><label>{label}</label></p>
    <p>
      <input type="time" value={time} onChange={(e) => {
        setTime(e.target.value);
        datemutate({'metaKey':metaKey,'metaValue':e.target.value});
        setStatus('setting to '+e.target.value);
      }
    }
 /> <br /><small>{status}</small></p>
    </div>
    );
}