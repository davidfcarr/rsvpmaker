import React, {useState, useEffect, Suspense} from "react"

export function SaveControls(props) {   
    const {notification,setIsSaving} = useSaveControls();
    return (
    <div id="savecontrols">
    {notification && <div className="rsvp-notification rsvp-notification-success">{notification.message}</div>}
    <div id="savebuttonwrapper"><button onClick={() => {setIsSaving(true);setOption(changes)}}>Save</button></div>
    </div>
    
    );
}

export function makeNotification(message) {
    const [notificationTimeout,setNotificationTimeout] = useState(null);
    const {notification,setNotification,isSaving,setIsSaving} = useSaveControls();
    if(notificationTimeout)
        clearTimeout(notificationTimeout);
    setNotification({'message':message});
    let nt = setTimeout(() => {
        setNotification(null);
        setIsSaving(false);
    },5000);
    setNotificationTimeout(nt);
}

export function useSaveControls() {
const [notification,setNotification] = useState(null);
const [isSaving,setIsSaving] = useState(false);
return {notification,setNotification,isSaving,setIsSaving};    
}
