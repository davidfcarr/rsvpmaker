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

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes, attributes: {alignment}, context: {postId}, setAttributes, isSelected } = props;
    const [dateblock, setDateBlock] = useState(null);

    console.log('post id '+postId);

    useEffect(() => {
        apiFetch( {path: '/rsvpmaker/v1/dateblock?post_id='+postId+'&alignment='+alignment} ).then( ( x ) => {
            setDateBlock(x.dateblock);
        } );
    }, [alignment]);
    
    return (
                <div { ...useBlockProps() }>
        <BlockControls>
          <AlignmentToolbar
            value={alignment}
            onChange={(newVal) => setAttributes({alignment: newVal})} />
            </BlockControls>
                        {dateblock && (
                        <>
                        <div dangerouslySetInnerHTML={{__html: dateblock}} />
                        </>
                        )}
                        {!dateblock && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
        </div>
    );
}
