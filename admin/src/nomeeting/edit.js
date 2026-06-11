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
const { SelectControl, TextControl } = wp.components;

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
	const { attributes, setAttributes, attributes: { weeks, headline } } = props;

    return (
				<Fragment>
                <div { ...useBlockProps() }>
                    <InspectorControls key="nomeetinginspector">
<SelectControl
        label={__("Weeks",'rsvpmaker')}
        value={ weeks }
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
			{value: 60, label: 60}]}
        onChange={ ( weeks ) => { setAttributes( { weeks: weeks } ) } }
    />
					<TextControl
        label={__("Headline (optional)",'rsvpmaker')}
        value={ headline }
        onChange={ ( headline ) => { setAttributes( { headline: headline } ) } }
    />
    <p>{__("If specified, this headline will be displayed above the list of no meeting dates.",'rsvpmaker')}</p>
                    </InspectorControls>
                    {headline && <h2>{headline}</h2>}
                    <p>Events specified as no meeting dates, if any, will be displayed here.</p>
                   </div>
                 </Fragment>
    );
}
