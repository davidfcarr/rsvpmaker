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
import React, { useState, useEffect, useCallback } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
const { Fragment } = wp.element;
const { SelectControl, TextControl, ToggleControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { debounce } from 'lodash'; // Lodash comes standard with WordPress

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
	const { attributes, setAttributes, attributes: { pixel, url, rsvpnow, queryString } } = props;
    const [qr, setQr] = useState(null);
    const post_id = wp.data.select('core/editor').getCurrentPostId();
// 1. Create a debounced version of your API fetch function
    const debouncedFetchQr = useCallback(
        debounce((currentUrl, currentPixel, currentRsvpNow, currentQueryString, currentPostId) => {
            const query = { url: currentUrl, pixel: currentPixel, rsvpnow: currentRsvpNow, queryString: currentQueryString, post_id: currentPostId };
            const qrUrl = addQueryArgs('/rsvpmaker/v1/qr', query);
            
            apiFetch({ path: qrUrl }).then((qr) => {
                setQr(qr);
            });
        }, 500), // 500ms delay
        [] // Empty dependency array ensures the debounced function is created only once
    );

    // 2. Trigger the debounced function inside useEffect when dependencies change
    useEffect(() => {
        if (url) {
            debouncedFetchQr(url, pixel, rsvpnow, queryString, post_id);
        }
    }, [url, pixel, rsvpnow, queryString, post_id, debouncedFetchQr]);
    return (
        <div { ...useBlockProps() }>
        <InspectorControls key="qrinspector">

<TextControl
        label={__("Enter URL or 'permalink' to for current post",'rsvpmaker')}
        value={ url }
        onChange={ ( url ) => { setAttributes( { url } ) } }
    />
<TextControl
        label={__("Query String (optional)",'rsvpmaker')}
        value={ queryString }
        onChange={ ( queryString ) => { setAttributes( { queryString } ) } }
    />
    <p><em>Must begin with '?'. Example: ?ref=newsletter</em></p>
<ToggleControl
        label={__("Add #rsvpnow to event URLs",'rsvpmaker')}
        checked={ rsvpnow }
        onChange={ ( rsvpnow ) => { setAttributes( { rsvpnow } ) } }
    />
    <p><em>{__("If set, the link takes user to form, rather than top of post.",'rsvpmaker')}</em></p>
<SelectControl
        label={__("Pixel Size",'rsvpmaker')}
        value={ pixel }
        options={ [{value: 5, label: 5},
			{value: 3, label: 3},
			{value: 4, label: 4},
			{value: 8, label: 8},
			{value: 10, label: 10},
			{value: 15, label: 15},
        ] }
        onChange={ ( pixel ) => { setAttributes( { pixel } ) } }
    />
                    </InspectorControls>
                    {qr && <div className="rsvpmaker_qr"><img src={qr.image} alt="QR Code" /></div>}
                    {!qr && <p>QR Code Loading...</p>}
                   </div>
    );
}
