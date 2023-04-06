import React, {useState} from "react"
import { DateTimePicker } from '@wordpress/components';
import { SelectCtrl } from "./Ctrl";
import {useRSVPDateMutation} from './queries.js'

export default function DateTimeMaker(props) {
    const {event_id, eventdata, isLoadingDates} = props;
    const [error,setError] = useState('');
    const {mutate:datemutate} = useRSVPDateMutation(event_id,setError);
    if(!eventdata.tzchoices)
        eventdata.tzchoices = [];
    console.log('props DateTimeMaker',props);
    console.log('eventdata DateTimeMaker',eventdata);
    const d = new Date();
    const is12Hour = true;
    const date = new Date(eventdata.date);
    const endDate = new Date(eventdata.enddate);
    const elapsed = endDate.getTime() - date.getTime();
    console.log('elapsed',elapsed);

    function sqlDate(date) {
        console.log(typeof date);
        if(typeof date == 'string')
            date = new Date(date);
        console.log('date for sqlDate',date);
        var pad = function(num) { return ('00'+num).slice(-2) };
        return date.getFullYear()         + '-' +
        pad(date.getMonth() + 1)  + '-' +
        pad(date.getDate())       + ' ' +
        pad(date.getHours())      + ':' +
        pad(date.getMinutes())    + ':' +
        pad(date.getSeconds());
    } 

    function setDate(datestring) {
        const dateobj = new Date(datestring);
        const m = dateobj.getTime();
        const m_end = m+elapsed;
        //console.log('setDate value',dateobj);
        const newendDate = new Date();
        newendDate.setTime(m_end);
        console.log(m);
        console.log(m_end);
        const sqldate = sqlDate(dateobj);
        const endsqldate = sqlDate(newendDate);
        datemutate({'date':sqldate,'enddate':endsqldate});
    }

    function setEndDate(datestring) {
        const newendDate = new Date(datestring);
        if(newendDate.getDate() < date.getDate()) {
            alert('end date cannot be before start date');
            return;
        }
        const endsqldate = sqlDate(newendDate);
        datemutate({'enddate':sqlDate(newendDate)});
    }

    function setOther(key,value) {
        const change = {...eventdata};
        change[key] = value;
        datemutate(change);
    }

    return (
        <div className="rsvpmaker-date-time">
        <h3>Start Date and Time</h3>
        {isLoadingDates && <p><em>Loading fresh data ...</em></p>}
            <DateTimePicker
            currentDate={ date }
            onChange={ ( newDate ) => {setDate( newDate ); console.log('new date', newDate);} }
            is12Hour={ is12Hour }
            __nextRemoveHelpButton
            __nextRemoveResetButton
        />
        {error && <p style={{'color':'red'}}>{error}</p>}
        <SelectCtrl label="Date Display Options" value={eventdata.display_type} options={[{'label':'Show Start Time Only','value':''},{'label':'Show Both Start and End Time','value':'set'},{'label':'Show Date Only, No Times','value':'allday'}]} onChange={(value) => { setOther('display_type',value); } } />
        {eventdata.display_type != '' && (
        <div className="rsvp-end-date"><h3>End Date and Time</h3>
        <DateTimePicker
            currentDate={ endDate }
            onChange={ ( newDate ) => {setEndDate( newDate ); console.log('new enddate', newDate)} }
            is12Hour={ is12Hour }
            __nextRemoveHelpButton
            __nextRemoveResetButton
        />
        </div>
        )}
        <SelectCtrl label="Time Zone" value={eventdata.timezone} options={eventdata.tzchoices.map((choice) => {return {'label':choice,'value':choice}})} onChange={(value) => { setOther('timezone',value); } } />
   </div>
    )
}