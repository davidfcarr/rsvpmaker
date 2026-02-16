/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import React, { useState, useEffect } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
const { Fragment } = wp.element;
const { SelectControl, ToggleControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes, setAttributes, attributes: { posts_per_page, days, type, time, date_format } } = props;
    const [rsvptypes, setTypes] = useState([]);
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        const t = [{value: '', label: 'None selected (optional)'}];
        apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
            if(Array.isArray(types))
                    types.map( function(type) { if(type.slug && type.name) t.push({value: type.slug, label: type.name }) } );
                else {
                    var typesarray = Object.values(types);
                    typesarray.map( function(type) { if(type.slug && type.name) t.push({value: type.slug, label: type.name }) } );
                    console.log(type.slug);
                    console.log(typeof type.slug);
                    console.log(type.name);
                    console.log(typeof type.name);
                }
        }).catch(err => {
            console.log(err);
        });
        setTypes(t);

    }, []);

    useEffect(() => {
        apiFetch( {path: addQueryArgs('/rsvpmaker/v1/preview/eventslisting', attributes) } ).then( ( p ) => {
            if(p)
            setPreview(p);
        } );
    }, [attributes]);


    return (
				<Fragment>
                <div { ...useBlockProps() }>
                    <InspectorControls key="eventlistinginspector">
                        					<SelectControl
        label={__("Events Per Page",'rsvpmaker')}
        value={ posts_per_page }
        options={ [{value: 5, label: 5},
			{value: 10, label: 10},
			{value: 15, label: 15},
			{value: 20, label: 20},
			{value: 25, label: 25},
			{value: 30, label: 30},
			{value: 35, label: 35},
			{value: 40, label: 40},
			{value: 45, label: 45},
			{value: 50, label: 50},
			{value: '-1', label: 'No limit'}]}
        onChange={ ( posts_per_page ) => { setAttributes( { posts_per_page: posts_per_page } ) } }
    />
					<SelectControl
        label={__("Date Range",'rsvpmaker')}
        value={ days }
        options={ [{value: 5, label: 5},
			{value: 30, label: '30 Days'},
			{value: 60, label: '60 Days'},
			{value: 90, label: '90 Days'},
			{value: 180, label: '180 Days'},
			{value: 366, label: '1 Year'}] }
        onChange={ ( days ) => { setAttributes( { days: days } ) } }
    />
					<SelectControl
        label={__("Event Type",'rsvpmaker')}
        value={ type }
        options={ rsvptypes }
        onChange={ ( type ) => { setAttributes( { type: type } ) } }
    />
				<SelectControl
        label={__("Date Format",'rsvpmaker')}
        value={ date_format }
        options={ [
            { label: 'Thursday August 8, 2019', value: '%A %B %e, %Y' },
            { label: 'August 8, 2019', value: '%B %e, %Y' },
            { label: 'August 8', value: '%B %e' },
            { label: 'Aug. 8', value: '%h. %e' },
            { label: '8 August 2019', value: '%e %B %Y' },
        ] }
        onChange={ ( date_format ) => { setAttributes( { date_format: date_format } ) } }
    />
				<ToggleControl
        label={__("Include Time",'rsvpmaker')}
        checked={ time }
        onChange={ ( time ) => { setAttributes( { time: time } ) } }
    />
                    </InspectorControls>
                    {preview && <div dangerouslySetInnerHTML={{__html: preview}}></div>}
                    {!preview && <p>RSVPMaker Events Listing loading ...</p>}
                   </div>
                 </Fragment>
    );
}
