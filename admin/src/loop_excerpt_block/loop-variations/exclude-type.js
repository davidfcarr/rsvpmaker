import { InspectorControls } from '@wordpress/block-editor';
import {SelectControl}  from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {useState, useEffect} from 'react';
import apiFetch from '@wordpress/api-fetch';

export function RSVPExclude (props) {
	const  { attributes,setAttributes }  = props;
    const { query } = attributes;
    const exclude_type = query.excludeType ? query.excludeType : '';
    const [rsvptypes, setTypes] = useState([]);
    useEffect(() => {
        const t = [];
        if(exclude_type)
            t.push({value: exclude_type, label: 'Selected: '+exclude_type});
        t.push({value: '', label: 'None selected (optional)'});
        apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
            if(Array.isArray(types))
                    types.map( function(type) { if(type.slug && type.name) t.push({value: type.slug, label: type.name }) } );
                else {
                    var typesarray = Object.values(types);
                    typesarray.map( function(type) { if(type.slug && type.name) t.push({value: type.slug, label: type.name }) } );
                }
        }).catch(err => {
            console.log(err);
        });
        setTypes(t);
    }, []);

    return (
        <>
<SelectControl
    label={__("EXCLUDE Event Type",'rsvpmaker')}
    selected={ exclude_type }
    value={ exclude_type }
    options={ rsvptypes }
    onChange={ ( exclude_type ) => { setAttributes( { query: {...query, excludeType: exclude_type } }) } }
/>
</>
);
} 