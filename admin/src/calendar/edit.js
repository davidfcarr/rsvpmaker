/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, PanelColorSettings, useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
const { Fragment } = wp.element;
const { PanelBody, RadioControl, SelectControl } = wp.components;
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
    const { attributes, setAttributes } = props;
    const {
        itembg,
        itemcolor,
        nav,
        type,
        event_type_colors = {},
    } = attributes;
    const [cal, setCal] = useState(null);
    const [rsvptypes, setRSVPTypes] = useState([
        { value: '', label: __( 'Any', 'rsvpmaker' ) },
    ]);

    useEffect(() => {
        const requestAttributes = {
            ...attributes,
            event_type_colors: JSON.stringify( attributes.event_type_colors || {} ),
        };

        apiFetch( { path: addQueryArgs( '/rsvpmaker/v1/calendar', requestAttributes ) } ).then( ( x ) => {
            setCal( x.calendar );
        } );
    }, [attributes]);

    useEffect(() => {
        apiFetch( { path: 'rsvpmaker/v1/types' } )
            .then( ( types ) => {
                const typeList = Array.isArray( types ) ? types : Object.values( types || {} );
                const options = [
                    { value: '', label: __( 'Any', 'rsvpmaker' ) },
                ];

                typeList.forEach( ( typeItem ) => {
                    if ( typeItem?.slug && typeItem?.name ) {
                        options.push( { value: typeItem.slug, label: typeItem.name } );
                    }
                } );

                setRSVPTypes( options );
            } )
            .catch( ( err ) => {
                console.log( err );
            } );
    }, []);

    const setTypeColorMode = ( slug, mode ) => {
        const nextColors = { ...event_type_colors };

        if ( mode === 'default' ) {
            delete nextColors[slug];
        } else if ( ! nextColors[slug] ) {
            nextColors[slug] = itembg;
        }

        setAttributes( { event_type_colors: nextColors } );
    };

    const setTypeColor = ( slug, color ) => {
        const nextColors = { ...event_type_colors };

        if ( color ) {
            nextColors[slug] = color;
        } else {
            delete nextColors[slug];
        }

        setAttributes( { event_type_colors: nextColors } );
    };

    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <InspectorControls key="calendarinspector">
                            <PanelBody title={ __( 'RSVPMaker Calendar', 'rsvpmaker' ) }>
                                <RadioControl
                                    label={ __( 'Position of Navigation Links', 'rsvpmaker' ) }
                                    selected={ nav }
                                    options={ [
                                        { label: 'bottom', value: 'bottom' },
                                        { label: 'top', value: 'top' },
                                        { label: 'both', value: 'both' },
                                    ] }
                                    onChange={ ( change ) => setAttributes( { nav: change } ) }
                                />
                                <SelectControl
                                    label={ __( 'Event Type', 'rsvpmaker' ) }
                                    value={ type }
                                    options={ rsvptypes }
                                    onChange={ ( selectedType ) => setAttributes( { type: selectedType } ) }
                                />
                            </PanelBody>
                            <PanelColorSettings
                                title={ __( 'Default Calendar Item Colors', 'rsvpmaker' ) }
                                colorSettings={ [
                                    {
                                        label: __( 'Text color', 'rsvpmaker' ),
                                        onChange: ( color ) => setAttributes( { itemcolor: color } ),
                                        value: itemcolor,
                                    },
                                    {
                                        label: __( 'Background color', 'rsvpmaker' ),
                                        onChange: ( color ) => setAttributes( { itembg: color } ),
                                        value: itembg,
                                    },
                                ] }
                            />
                            <PanelBody title={ __( 'Event Type Background Colors', 'rsvpmaker' ) } initialOpen={ false }>
                                { rsvptypes
                                    .filter( ( typeOption ) => typeOption.value )
                                    .map( ( typeOption ) => {
                                        const hasCustom = !! event_type_colors[typeOption.value];

                                        return (
                                            <div key={ typeOption.value } style={ { marginBottom: '16px' } }>
                                                <SelectControl
                                                    label={ sprintf( __( '%s background', 'rsvpmaker' ), typeOption.label ) }
                                                    value={ hasCustom ? 'custom' : 'default' }
                                                    options={ [
                                                        { label: __( 'Use default', 'rsvpmaker' ), value: 'default' },
                                                        { label: __( 'Use custom color', 'rsvpmaker' ), value: 'custom' },
                                                    ] }
                                                    onChange={ ( mode ) => setTypeColorMode( typeOption.value, mode ) }
                                                />
                                                { hasCustom && (
                                                    <PanelColorSettings
                                                        title={ sprintf( __( '%s custom color', 'rsvpmaker' ), typeOption.label ) }
                                                        colorSettings={ [
                                                            {
                                                                label: __( 'Background color', 'rsvpmaker' ),
                                                                onChange: ( color ) => setTypeColor( typeOption.value, color ),
                                                                value: event_type_colors[typeOption.value],
                                                            },
                                                        ] }
                                                    />
                                                ) }
                                            </div>
                                        );
                                    } ) }
                            </PanelBody>
                        </InspectorControls>
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
