import React, {useState} from "react"
import { DateTimePicker,ToggleControl } from '@wordpress/components';
import { SelectCtrl } from "./Ctrl";
import {useRSVPDate, useRSVPDateMutation} from './queries.js'

export default function DateTimeMakerInner(props) {
    const {event_id} = props;
    const [local,setLocal] = useState({});
    const [error,setError] = useState('');
    const {data:bigdata,isLoading,isError} = useRSVPDate(event_id);
    if(isError)
        return <p>Error loading event date</p>
    const {mutate:datemutate} = useRSVPDateMutation(event_id,setError);
    if(isLoading)
        return <p>Loading date and time ...</p>
    const d = new Date();
    const eventdata = (local.date) ? local : bigdata.data;
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
        setLocal((prev) => { return (prev.date) ? {...prev,'date':sqldate,'enddate':endsqldate} : {...eventdata,'date':sqldate,'enddate':endsqldate} } );
        datemutate({'date':sqldate,'enddate':endsqldate});
        console.log('local after set date',local);
        //setEndDate(newendDate);
    }

    function setEndDate(datestring) {
        console.log('end date '+datestring);
        const newendDate = new Date(datestring);
        if(newendDate.getDate() < date.getDate()) {
            console.log('new end date error end',newendDate);
            console.log('new end date error date',date);
            alert('end date cannot be before start date');
            newendDate.setTime(date.getTime()+60000);
            return;
        }
        const endsqldate = sqlDate(newendDate);
        setLocal((prev) => { return (prev.date) ? {...prev,'enddate':endsqldate} : {...eventdata,'enddate':endsqldate} } );
        datemutate({'enddate':sqlDate(newendDate)});
    }

    function setOther(key,value) {
        console.log('set other key',key);
        console.log('set other value',value);
        setLocal((prev) => { let change = (prev.date) ? {...prev} : {...eventdata}; change[key] = value; datemutate(change); return change; } );
    }

    function retry() {
        datemutate(...local);
    }

    return (
        <div className="rsvpmaker-date-time">
        <h3>Start Date and Time</h3>
            <DateTimePicker
            currentDate={ date }
            onChange={ ( newDate ) => {setDate( newDate ); console.log('new date', newDate);} }
            is12Hour={ is12Hour }
            __nextRemoveHelpButton
            __nextRemoveResetButton
        />
        {error && <p style={{'color':'red'}}>{error} <br /><button onClick={retry}>Retry</button></p>}
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