import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import {SaveControls,useSaveControls,makeNotification} from './SaveControls';

export default function Security (props) {
    const {notification,setNotification,isSaving,setIsSaving} = useSaveControls();

    return <div className="rsvptab">
    {isSaving && <h1>Saving ...</h1>}
    <div className={(isSaving) ? "rsvptab-saving": ""}>
    
    <p>Security stuff goes here</p>
        
    </div>
    <SaveControls />
    </div>
}