import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'

export function useSaveControls() {
const [isSaving,setIsSaving] = useState(false);
const [notificationTimeout,setNotificationTimeout] = useState(null);
const [notification,setNotification] = useState(null);

function saveEffect() {
    setIsSaving(true);
    setTimeout(() => {
        setIsSaving(false);
    },500);
}

function makeNotification(message = '') {
    if(message == '')
        return notification;
    if(notificationTimeout)
        clearTimeout(notificationTimeout);
    setNotification({'message':message});
    let nt = setTimeout(() => {
        setNotification(null);
    },5000);
    setNotificationTimeout(nt);
}

function SaveControls(props) { 
    const {changes,setChanges} = props;    
    console.log('changes SaveControls',changes);
    const {mutate:setOption} = useOptionsMutation(setChanges,makeNotification);
    return (
    <div id="savecontrols">
    {notification && <div className="rsvp-notification rsvp-notification-success">{notification.message}</div>}
    <div id="savebuttonwrapper"><button onClick={() => {console.log('save changeset',changes);saveEffect();setOption(changes)}}>Save</button></div>
    </div>    
    );
}

return {SaveControls,notification,setNotification,isSaving,saveEffect,makeNotification};
}