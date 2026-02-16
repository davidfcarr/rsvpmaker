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
	const { attributes, setAttributes, attributes: { limit, skipfirst } } = props;
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        apiFetch( {path: addQueryArgs('/rsvpmaker/v1/preview/future-rsvp-links', attributes) } ).then( ( p ) => {
            if(p)
            setPreview(p);
        } );
    }, [attributes]);

    return (
				<Fragment>
                <div { ...useBlockProps() }>
                    <InspectorControls key="futurersvpinspector">
<SelectControl
        label={__("Limit",'rsvpmaker')}
        value={ limit }
        options={ [{label:'3',value: '3'},{label:'5',value: '5'},{label:'7',value: '7'},{label:'10',value: '10'}] }
        onChange={ ( limit ) => { setAttributes( { limit } ) } }
    />
<ToggleControl
        label={__("Skip First Date",'rsvpmaker')}
        checked={ skipfirst }
		help={__('For example, to pick up after an embedded date block that features the first event in the series.')}
        onChange={ ( skipfirst ) => { setAttributes( { skipfirst } ) } }
    />
                    </InspectorControls>
                    {preview && <div dangerouslySetInnerHTML={{__html: preview}}></div>}
                    {!preview && <p>Future RSVP Links loading ...</p>}
                   </div>
                 </Fragment>
    );
}
