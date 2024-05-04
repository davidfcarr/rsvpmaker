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
import React, { useState, useEffect } from 'react';
const { Panel, PanelBody, ToggleControl, Toolbar, ToolbarButton, ToolbarGroup } = wp.components;
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
	const { attributes, attributes: {show_time, italic, bold, align}, context: {postId}, setAttributes, isSelected } = props;
    const [titledate, setTitleDate] = useState(null);

    const style={"display":"block"};
    if(bold)
        style.fontWeight = 'bold';
    if(italic)
        style.fontStyle = 'italic';
    if(align)
        style.textAlign = align;

    console.log('post id '+postId);

    useEffect(() => {
        apiFetch( {path: '/rsvpmaker/v1/title-date/'+postId} ).then( ( x ) => {
            setTitleDate(x);
        } );
    }, []);

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
        </Toolbar>
        </BlockControls>
                    <InspectorControls key="titledateinspector">
                    <PanelBody title={ __( 'Date or Date+Time', 'rsvpmaker' ) } >
            <ToggleControl
                label={__("Show Time",'rsvpmaker')}
                checked={ show_time }
                onChange={ ( show_time ) => { setAttributes( { show_time } ) } }
            />
            </PanelBody>
            </InspectorControls>
                        {titledate && (
                        <>
                        <p><a href="#" style={style}>{titledate.title} - {titledate.date} {show_time && <span>{titledate.time}</span>}</a></p>
                        </>
                        )}
                        {!titledate && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
        </div>
    );
}
