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
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
const { Component, Fragment } = wp.element;
const { Panel, PanelBody, TextControl, RadioControl, ColorPicker, SelectControl } = wp.components;
import React, { useState, useEffect } from 'react';

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
	const { attributes, setAttributes, isSelected } = props;
    const [cal, setCal] = useState(null);
    const [rsvptypes, setRSVPTypes] = useState([]);

    useEffect(() => {
        apiFetch( {path: addQueryArgs( '/rsvpmaker/v1/calendar', attributes)} ).then( ( x ) => {
            setCal(x.calendar);
        } );
        rsvptypes.push({value: '', label: 'Any'});
        apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
            if(Array.isArray(types))
                    types.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
                else {
                    var typesarray = Object.values(types);
                    typesarray.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
                }
            setRSVPTypes(rsvptypes);
        }).catch(err => {
            console.log(err);
        });	    
    }, [attributes]);

    /* <RadioControl label="Calendar Item Font Size" selected={itemfontsize} options={[{'label':'x-small','value':'x-small'},{'label':'xx-small','value':'xx-small'},{'label':'small','value':'small'},{'label':'medium','value':'medium'}]} onChange={ (change) => setAttributes({'itemfontsize':change}) }	/> */
    
    class ExcerptInspector extends Component {
	
            render() {
                const { attributes: {itembg, itemcolor, itemfontsize, nav, date_format, type}, setAttributes, isSelected } = this.props;
                    return (
                        <div>
                    <InspectorControls key="calendarinspector">
                    <PanelBody title={ __( 'RSVPMaker Calendar', 'rsvpmaker' ) } >
                    <RadioControl label="Position of Navigation Links" selected={nav} options={[{'label':'bottom','value':'bottom'},{'label':'top','value':'top'},{'label':'both','value':'both'}]} onChange={ function( change ) { setAttributes( {'nav':change} ); } }	/>
                    <SelectControl
                label={__("Event Type",'rsvpmaker')}
                value={ type }
                options={ rsvptypes }
                onChange={ ( type ) => { setAttributes( { type: type } ) } } />
                    <p>Calendar Item Text Color</p>
                    <ColorPicker color={itemcolor} onChange={ (change) => setAttributes ({'itemcolor':change}) } />
                    <p>Calendar Item Background Color</p>
                    <ColorPicker color={itembg} onChange={(change) => setAttributes({'itembg':change}) } />
                    </PanelBody>
            </InspectorControls>
            </div>
        );	} }
    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <ExcerptInspector {...props}/>
                        {cal && (
                        <>
                        <div dangerouslySetInnerHTML={{__html: cal}} />
                        </>
                        )}
                        {!cal && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
                   </div>
                 </Fragment>
    );
}
