import React, {useState} from 'react';
import { ToggleControl, TextControl, RadioControl, TextareaControl } from '@wordpress/components';
import {SelectCtrl} from '../Ctrl.js'
import { SanitizedHTML } from "../SanitizedHTML.js";
import {Up,Down,Delete} from '../icons.js';
import apiClient from '../http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';

export default function Confirmation() {
    const [status,setStatus] = useState('');
    const [reminderHours,setReminderHours] = useState(1);
    const [addBeforeAfter,setBeforeAfter] = useState('before');
    
    function fetchMessages() {
        return apiClient.get('confirm_remind?event_id='+rsvpmaker_ajax.event_id);
    }
    const {data,isLoading} = useQuery(['confirm_remind'], fetchMessages, { enabled: true, retry: 2, 
    onSuccess: (data, error, variables, context) => {
        console.log('rsvp confirmation query',data);
    }, onError: (err, variables, context) => {
        console.log('error retrieving messages',err);
    }, refetchInterval: false });
        
    const queryClient = useQueryClient();
    async function updateMessage (command) {
        return await apiClient.post('confirm_remind?event_id='+rsvpmaker_ajax.event_id, command);
    }
    
    const {mutate:messageMutate} = useMutation(updateMessage, {
        /*
        onMutate: async (form) => {
            const previousValue = queryClient.getQueryData(['rsvp_form',formId]);
            console.log('optimistic update form',form);
            await queryClient.cancelQueries(['rsvp_form',formId]);
            queryClient.setQueryData(['rsvp_form',formId],(oldQueryData) => {
                //function passed to setQueryData
                const {data} = oldQueryData;
                data.form = form;
                const newdata = {
                    ...oldQueryData, data: data
                };
                console.log('newdata optimistic form update',newdata);
                return newdata;
            }) 
            //makeNotification('Updating ...');
            console.log('updating options');
            return {previousValue}
        },

        onSettled: (data, error, variables, context) => {
            queryClient.invalidateQueries(['rsvp_form',formId]);
        },
        */
        onSuccess: (data, error, variables, context) => {
            console.log('updated',data);
            setStatus('');
            queryClient.setQueryData(['confirm_remind'], data);
        },
        onError: (err, variables, context) => {
            //makeNotification('Error '+err.message);
            console.log('update message error',err);
            //queryClient.setQueryData(['rsvp_form',formId], context.previousValue);
        },    
    });
     
        if(isLoading)
            return <p>Loading ...</p>
        const messagedata = data.data;
        const {confirmation, reminder, edit_url} = messagedata;

        const ropt = [];
    
    return <div>
    <h2>Confirmation Message: {messagedata.confirmation.post_title}</h2>
    <SanitizedHTML innerHTML={confirmation.html} />
    <p><a target="_blank" href={edit_url+confirmation.ID}>Edit</a></p>
    {confirmation.post_parent != rsvpmaker_ajax.event_id && <p><button onClick={() => {messageMutate({'action':'customize','type':'confirmation','source':confirmation.ID,'event_id':rsvpmaker_ajax.event_id} ),setStatus('working ...');} }>Customize for this {rsvpmaker.post_type == 'rsvpmaker' ? 'event' : 'template'}</button> {status}</p> }

    <h2>Payment Confirmation {!messagedata.payment_confirmation && <span>:Not Set</span>}</h2>
    {!messagedata.payment_confirmation &&  <p><button onClick={() => {messageMutate({'action':'add_payment_confirmation','type':'payment confirmation','source':confirmation.ID,'event_id':rsvpmaker_ajax.event_id} ),setStatus('working ...');} }>Customize for this {rsvpmaker.post_type == 'rsvpmaker' ? 'event' : 'template'}</button> {status}<br />If you are charging for an event, you can add a separate payment confiration message that is only sent after payment is received.</p>}
    {messagedata.payment_confirmation &&  <div><SanitizedHTML innerHTML={messagedata.payment_confirmation.html} /><p><a  target="_blank" href={edit_url+messagedata.payment_confirmation.ID}>Edit</a></p></div>}

    <h2>Reminder and Follow Up Messages</h2>
    {reminder.map( (rem) => {
        const hour = parseInt(rem.hour);
        const beforeafter = (hour < 0) ? 'before' : 'after';
        return (
            <div>
            <h2>{Math.abs(hour)} hours {beforeafter}</h2>
            <p><strong><SanitizedHTML innerHTML={rem.post_title} /></strong></p>
            <SanitizedHTML innerHTML={rem.html} />
            <p><a target="_blank" href={edit_url+rem.ID}>Edit</a></p>
            </div>
        );
    } )}
    <h3>Add a Message</h3>
    <p>Number of Hours<br /> <input type="number" min="1" value={reminderHours} onChange={(e) => {if(e.target.value > 0) setReminderHours(e.target.value)} } />
    <SelectCtrl value={addBeforeAfter} onChange={(value) => {setBeforeAfter(value);console.log(value);} } options={[{'label': 'before event start time','value': 'before'},{'label': 'after event start time','value': 'after'}]} /></p>
    <p><button onClick={() => {messageMutate({'action':'add_reminder','type':'reminder '+reminderHours+' '+addBeforeAfter,'source':confirmation.ID,'event_id':rsvpmaker_ajax.event_id,'hours':reminderHours,'beforeafter':addBeforeAfter} ),setStatus('working ...');} }>Add</button></p>
    </div>
}
