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
import React, { useState, useEffect } from 'react';
const { Panel, PanelBody, ToggleControl, Toolbar, ToolbarButton, SelectControl, RadioControl, TextControl, ToolbarGroup } = wp.components;
import { formatBold, formatItalic } from '@wordpress/icons';
import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';

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
	const { attributes, attributes: {show, start_format, end_format, separator, timezone, italic, bold, align}, context: {postId}, setAttributes, isSelected } = props;
    const [dateobj, setDateObj] = useState({"start_formats":[],"end_formats":[]});

    const style={"display":"block"};
    if(bold)
        style.fontWeight = 'bold';
    if(italic)
        style.fontStyle = 'italic';
    if(align)
        style.textAlign = align;

    console.log('post id '+postId);
    attributes['post_id'] = postId;

    useEffect(() => {
        apiFetch( {path: addQueryArgs( '/rsvpmaker/v1/date-element', attributes)} ).then( ( x ) => {
            setDateObj(x);
        } );
    }, [show, start_format, end_format, separator, timezone, italic, bold, align]);

    const onChangeAlignment = ( newAlignment ) => {
        setAttributes( {
            align: newAlignment === undefined ? 'none' : newAlignment,
        } );
    };
    
    return (
                <div { ...useBlockProps() }>
                    <BlockControls>
                    <Toolbar label="Options">
                    <ToolbarGroup>
                        <AlignmentToolbar value={ align }
                        onChange={ onChangeAlignment } />
                    </ToolbarGroup>
                        <ToolbarGroup>
            <ToolbarButton
                icon={ formatBold }
                label="Bold"
                onClick={ () => setAttributes( {'bold': !bold} ) }
                isActive = {bold}
            />
            <ToolbarButton
                icon={ formatItalic }
                label="Italic"
                onClick={ () => setAttributes( {'italic': !italic} ) }
                isActive = {italic}
            />
            </ToolbarGroup>
        </Toolbar>
        </BlockControls>
                    <InspectorControls key="titledateinspector">
                    <PanelBody title={ __( 'Date Element', 'rsvpmaker' ) } >
            <SelectControl
            label="Show"
            value={ show }
            options={ [
                { label: 'Start and End Date', value: 'start_and_end' },
                { label: 'Start Date', value: 'start' },
                { label: 'End Date', value: 'end' },
                { label: 'Calendar Icons', value: 'icons' },
                { label: 'Timezone Conversion', value: 'tz_convert' },
            ] }
            onChange={ ( show ) => setAttributes( {show} ) }
            __nextHasNoMarginBottom
        />
        {
            show.includes('start') && <>
            <SelectControl
            label="Start Date Format"
            value={ start_format }
            options={ dateobj.start_formats }
            onChange={ ( start_format ) => setAttributes( {start_format} ) }
            __nextHasNoMarginBottom
        />
            <TextControl
            label="Start Date Format Code"
            value={ start_format }
            onChange={ ( start_format ) => setAttributes( {start_format} ) }
            __nextHasNoMarginBottom
        />            
            </>
        }
        {
            show.includes('end') && <>
            <SelectControl
            label="End Date Format"
            value={ end_format }
            options={ dateobj.end_formats }
            onChange={ ( end_format ) => setAttributes( {end_format} ) }
            __nextHasNoMarginBottom
        />
            <TextControl
            label="End Date Format Code"
            value={ start_format }
            onChange={ ( end_format ) => setAttributes( {end_format} ) }
            __nextHasNoMarginBottom
        />
        </>
        }
        { (show.includes('start') || show.includes('end')) && <>
            <p>See <a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">PHP date codes</a> for additional formatting options.</p>
            <ToggleControl
            label={__("Display Timezone",'rsvpmaker')}
            checked={ timezone }
            onChange={ ( timezone ) => { setAttributes( { timezone } ) } }
        />
        </>
        }
            </PanelBody>
            </InspectorControls>
                        {dateobj && dateobj.element && (
                        <>
                        {dateobj.element.includes('<') && <div  style={style} dangerouslySetInnerHTML={{'__html':dateobj.element}} />}
                        {!dateobj.element.includes('<') && <div style={style}>{dateobj.element}</div>}
                        </>
                        )}
                        {(!dateobj || !dateobj.element) && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
        </div>
    );
}
