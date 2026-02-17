
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { Component, Fragment, useState, useEffect, RawHTML } = wp.element;
const { Panel, PanelBody, SelectControl, TextControl, ColorPicker, ColorPalette, Button, ResponsiveWrapper } = wp.components;
import { useBlockProps } from '@wordpress/block-editor';
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
	const CTEMPLATE = [
	[ 'core/paragraph', { placeholder: __('Email content','rsvpmaker')}],
	];
	const { attributes, className, setAttributes, isSelected, attributes: {backgroundColor, color, padding, marginLeft, marginRight, maxWidth, border,minHeight} } = props;
    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
        marginLeft: attributes.marginLeft,
        marginRight: attributes.marginRight,
        maxWidth: attributes.maxWidth,
        border: attributes.border,
        minHeight: '20px',
    };
    const blockProps = useBlockProps( { style: bodyStyle } );
	const colors = wp.data.select('core/block-editor').getSettings().colors;

	return (
<div className={className}  { ...blockProps } >
	<InspectorControls key="emailcontentinspector" >
		<PanelBody title={ __( 'Style', 'rsvpmaker' ) } >
            <h3>Background Color</h3>
            <ColorPalette 
            colors={ colors }
            value={ backgroundColor }
            onChange={ ( backgroundColor ) => setAttributes( {backgroundColor: backgroundColor} ) }
            />
            <ColorPicker
            label="Background Color"
            color={backgroundColor}
            onChange={ ( backgroundColor ) => { setAttributes( { backgroundColor: backgroundColor } ) } }
            enableAlpha
            />
            <h3>Text Color</h3>
            <ColorPalette 
            colors={ colors }
            value={ color }
            onChange={ ( color ) => setAttributes( {color: color} ) }
            />
            <ColorPicker
            color={color}
            onChange={ ( color ) => { setAttributes( { color: color } ) } }
            enableAlpha
        />  
            <TextControl
            label="Border"
            value={ border }
            onChange={ ( border ) => { setAttributes( { border: border } ) } }
            />
            <TextControl
            label="Margin Left"
            value={ marginLeft }
            onChange={ ( marginLeft ) => { setAttributes( { marginLeft: marginLeft } ) } }
            />
            <TextControl
            label="Margin Right"
            value={ marginRight }
            onChange={ ( marginRight ) => { setAttributes( { marginRight: marginRight } ) } }
            />
            <TextControl
            label="Max Width"
            value={ maxWidth }
            onChange={ ( maxWidth ) => { setAttributes( { maxWidth: maxWidth } ) } }
            />
            <TextControl
            label="Padding"
            value={ padding }
            onChange={ ( padding ) => { setAttributes( { padding: padding } ) } }
            />
		</PanelBody>

	</InspectorControls>
    <InnerBlocks template={CTEMPLATE} />
</div>
		);
}
