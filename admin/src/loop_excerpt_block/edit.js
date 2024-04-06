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
const { Component, Fragment } = wp.element;
const { Panel, PanelBody, ToggleControl } = wp.components;
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
	const { attributes: { show_rsvp_button, hide_excerpt }, setAttributes, context, isSelected } = props;
    const {postId} = context;
    const [excerptobj, setExcerptobj] = useState({});

    useEffect(() => {
        apiFetch( {path: '/rsvpmaker/v1/excerpt/'+postId+'?show_button=='+show_rsvp_button} ).then( ( excerpt ) => {
            setExcerptobj(excerpt);
        } );
    }, []);

    class ExcerptInspector extends Component {
	
            render() {
                const { attributes: { show_rsvp_button, hide_excerpt }, setAttributes, isSelected } = this.props;
                    return (
                        <div>
                    <InspectorControls key="excerptinspector">
                    <PanelBody title={ __( 'RSVPMaker Excerpt', 'rsvpmaker' ) } >
                    <ToggleControl
                label={__("Show RSVP Button",'rsvpmaker')}
                checked={ show_rsvp_button }
                onChange={ ( show_rsvp_button ) => { setAttributes( { show_rsvp_button } ) } }
            />
                    <ToggleControl
                label={__("Hide Excerpt (date will still be displayed)",'rsvpmaker')}
                checked={ hide_excerpt }
                onChange={ ( hide_excerpt ) => { setAttributes( { hide_excerpt } ) } }
            />
            </PanelBody>
            </InspectorControls>
            </div>
        );	} }
    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <ExcerptInspector {...props}/>
                        {excerptobj.dateblock && (
                        <>
                        <div dangerouslySetInnerHTML={{__html: excerptobj.dateblock}} />
                        {!hide_excerpt && <p>{excerptobj.excerpt}</p>}
                        {show_rsvp_button && excerptobj.rsvp_on && <div dangerouslySetInnerHTML={{__html: excerptobj.rsvp_on}} />}
                        {excerptobj.types && <p className="rsvpmeta" dangerouslySetInnerHTML={{__html: excerptobj.types}} />}
                        </>
                        )}
                        {!excerptobj.dateblock && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
                   </div>
                 </Fragment>
    );
}
