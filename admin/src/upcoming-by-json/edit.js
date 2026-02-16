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
const { Panel, PanelBody, SelectControl, RadioControl, TextControl, ColorPalette, FontSizePicker } = wp.components;
import apiFetch from '@wordpress/api-fetch';
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
	const { attributes: { limit, url, morelink }, setAttributes, isSelected } = props;
	let typelist = '';
    const rsvpupcoming = [{label: __('Choose event'),value: ''},{label: __('Next event'),value: 'next'},{label: __('Next event - RSVP on'),value: 'nextrsvp'}];
    apiFetch( {path: 'rsvpmaker/v1/future'} ).then( events => {
        if(Array.isArray(events)) {
             events.map( function(event) { if(event.ID) { var title = (event.date) ? event.post_title+' - '+event.date : event.post_title; rsvpupcoming.push({value: event.ID, label: title }) } } );
        }
         else {
             var eventsarray = Object.values(events);
             eventsarray.map( function(event) { if(event.ID) { var title = (event.date) ? event.post_title+' - '+event.date : event.post_title; rsvpupcoming.push({value: event.ID, label: title }) } } );
            }
    }).catch(err => {
        console.log(err);
    });
    const rsvptypes = [{value: '', label: 'None selected (optional)'}];
    apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
        if(Array.isArray(types))
                types.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
            else {
                var typesarray = Object.values(types);
                typesarray.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
            }
    }).catch(err => {
        console.log(err);
    });	
    
	if(rsvpupcoming && (rsvpupcoming.length > 2))
	{
		typelist = 'API urls for  this site:\n'+window.location.protocol+'//'+window.location.hostname+'/wp-json/rsvpmaker/v1/future\n';
		rsvptypes.forEach(showTypes);	
	}

function showTypes (data, index) {
	if(index > 0)
		typelist = typelist.concat(rsvpmaker.json_url+'type/'+data.value + '\n'); 
}

		return (
			<div { ...useBlockProps({ className: props.className }) }>
				<p  class="dashicons-before dashicons-calendar-alt"><strong>RSVPMaker </strong>: Add an Events Listing that dynamically loads via JSON API endpoint
				</p>
                <InspectorControls key="eventlistinginspector">
                    <Panel>
                        <PanelBody>
	<TextControl
        label={ __( 'JSON API url', 'rsvpmaker' ) }
        value={ url }
        onChange={ ( url ) => setAttributes( { url } ) }
    />
	<TextControl
        label={ __( 'Limit', 'rsvpmaker' ) }
        value={ limit }
		help={__('For no limit, enter 0')}
        onChange={ ( limit ) => setAttributes( { limit } ) }
    />	
	<TextControl
        label={ __( 'Link URL for more results (optional)', 'rsvpmaker' ) }
        value={ morelink }
        onChange={ ( morelink ) => setAttributes( { morelink } ) }
    />	
	<p><em>Enter JSON API url for this site or another in the format:
	<br />https://rsvpmaker.com/wp-json/rsvpmaker/v1/future
	<br />or
	<br />https://rsvpmaker.com/wp-json/rsvpmaker/v1/type/featured</em></p>
<pre>{typelist}</pre>
                        </PanelBody>
                    </Panel>
                </InspectorControls>
            </div>
        );
}
