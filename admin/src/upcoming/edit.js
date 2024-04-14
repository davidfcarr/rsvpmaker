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
const { Component, Fragment } = wp.element;
const { Panel, PanelBody, SelectControl, RadioControl, TextControl, ColorPalette, FontSizePicker } = wp.components;
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
	const { attributes } = props;
    const [rsvptypes, setTypes] = useState([]);
    const [rsvpauthors, setAuthors] = useState([]);
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

        const a = [{value: '', label: 'Any'}];
        apiFetch( {path: 'rsvpmaker/v1/authors'} ).then( authors => {
            if(Array.isArray(authors))
                    authors.map( function(author) { if(author.ID && author.name) a.push({value: author.ID, label: author.name }) } );
                else {
                    authors = Object.values(authors);
                    authors.map( function(author) { if(author.ID && author.name) a.push({value: author.ID, label: author.name }) } );
                }
        }).catch(err => {
            console.log(err);
        });	
        setAuthors(a);
    }, []);

    useEffect(() => {
        apiFetch( {path: addQueryArgs('/rsvpmaker/v1/upcoming_preview/', attributes) } ).then( ( p ) => {
            if(p.calendar)
            setPreview(p.calendar);
        } );
    }, [attributes]);


    
        class UpcomingInspector extends Component {
	
            render() {
                const { attributes: { calendar, excerpt, days, posts_per_page, hideauthor, no_events, nav, type, exclude_type, author, itemcolor, itembg, itemfontsize }, setAttributes, isSelected } = this.props;
                const fontSizes = [
                    {
                        name: __( 'Small' ),
                        slug: 'small',
                        size: 10,
                    },
                    {
                        name: __( 'Medium' ),
                        slug: 'medium',
                        size: 12,
                    },
                    {
                        name: __( 'Large' ),
                        slug: 'large',
                        size: 13,
                    },
                    {
                        name: __( 'Extra Large' ),
                        slug: 'xlarge',
                        size: 14,
                    }
                ];
                const fallbackFontSize = 10;
                console.log('type',type);
                console.log('types',rsvptypes);
                    return (
                        <div>
                    <InspectorControls key="upcominginspector">
                    <PanelBody title={ __( 'RSVPMaker Upcoming Options', 'rsvpmaker' ) } >
                    <form  >
                            <SelectControl
                label={__("Display Calendar",'rsvpmaker')}
                value={ calendar }
                options={ [{value: 1, label: __('Yes - Calendar plus events listing')},{value: 0, label:  __('No - Events listing only')},{value: 2, label: __('Calendar only')}] }
                onChange={ ( calendar ) => { console.log('calendar choice '+typeof calendar); console.log(calendar); setAttributes( { calendar: calendar } ) } }
            />
                            <SelectControl
                label={__("Format",'rsvpmaker')}
                value={ excerpt }
                options={ [{value: 0, label: __('Full Text')},{value: 1, label:  __('Excerpt')}] }
                onChange={ ( excerpt ) => { setAttributes( { excerpt: excerpt } ) } }
            />
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
                selected={ type }
                value={ type }
                options={ rsvptypes }
                onChange={ ( type ) => { setAttributes( { type: type } ) } }
            />
                            <SelectControl
                label={__("EXCLUDE Event Type",'rsvpmaker')}
                selected={ exclude_type }
                value={ exclude_type }
                options={ rsvptypes }
                onChange={ ( exclude_type ) => { setAttributes( { exclude_type: exclude_type } ) } }
            />
                            <SelectControl
                label={__("Author",'rsvpmaker')}
                value={ author }
                options={ rsvpauthors }
                onChange={ ( author ) => { setAttributes( { author: author } ) } }
            />
                            <SelectControl
                label={__("Calendar Navigation",'rsvpmaker')}
                value={ nav }
                options={ [{value: 'top', label: __('Top')},{value: 'bottom', label: __('Bottom')},{value: 'both', label: __('Both')}] }
                onChange={ ( nav ) => { setAttributes( { nav: nav } ) } }
            />
                        <SelectControl
                label={__("Show Event Author",'rsvpmaker')}
                value={ hideauthor }
                options={ [
                    { label: 'No', value: true },
                    { label: 'Yes', value: false },
                ] }
                onChange={ ( hideauthor ) => { setAttributes( { hideauthor: hideauthor } ) } }
            />
                        <TextControl
                label={__("Text to show for no events listed",'rsvpmaker')}
                value={ no_events }
                onChange={ ( no_events ) => { setAttributes( { no_events: no_events } ) } }
            />
        
                        </form>
            </PanelBody>
            <Panel header="Calendar Colors">
            <PanelBody title={ __( 'Calendar Item Text Color', 'rsvpmaker' ) } >
            <ColorPalette 
                label={__("Calendar item text color",'rsvpmaker')}
                colors = {wp.data.select ("core/editor").getEditorSettings ().colors}
                value={ itemcolor }
                defaultValue={ itemcolor }
                onChange={ ( itemcolor ) => { setAttributes( { itemcolor } ) } }	
            />
            </PanelBody>
            <PanelBody title={ __( 'Calendar Item Background Color', 'rsvpmaker' ) } >
            <ColorPalette 
                colors = {wp.data.select ("core/editor").getEditorSettings ().colors}
                label={__("Calendar item background color",'rsvpmaker')}
                value={ itembg }
                defaultValue={ itembg }
                onChange={ ( itembg ) => { setAttributes( { itembg } ) } }	
            />
            <div><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"></path></svg> <em>See the styles tab for the overall text and background color settings.</em></div>
            </PanelBody>
            </Panel>
            <Panel header="Calendar Fonts">
            <PanelBody title={ __( 'Calendar Item Font Size', 'rsvpmaker' ) }  >             
            <FontSizePicker 
                label={__("Calendar item text size",'rsvpmaker')}
                value={ itemfontsize }
                fontSizes={ fontSizes }
                fallbackFontSize={ fallbackFontSize }
                onChange={ ( itemfontsize ) => { setAttributes( { itemfontsize: itemfontsize } ) } }		
            />
            </PanelBody>
            </Panel>
            </InspectorControls>
            </div>
        );	} }
        

    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <UpcomingInspector {...props}/>
                    {preview && <div dangerouslySetInnerHTML={{__html: preview}}></div>}
                    {!preview && <p>RSVPMaker Upcoming loading ...</p>}
                   </div>
                 </Fragment>
    );
}
