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
const { Panel, PanelBody, ToggleControl, RangeControl } = wp.components;
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
	const { attributes: { hide_type,max }, setAttributes, context, isSelected } = props;
    const {postId} = context;
    const [excerptobj, setExcerptobj] = useState({});
    console.log(props);

    useEffect(() => {
        apiFetch( {path: '/rsvpmaker/v1/excerpt/'+postId+'?max='+max} ).then( ( excerpt ) => {
            setExcerptobj(excerpt);
        } );
    }, [max]);

    class ExcerptInspector extends Component {
	
            render() {
                const { attributes: { hide_type, max }, setAttributes, isSelected } = this.props;
                if (typeof hide_type == 'undefined')
                    hide_type = false;
                
                return (
                        <div>
                    <InspectorControls key="excerptinspector">
                    <PanelBody title={ __( 'RSVPMaker Excerpt', 'rsvpmaker' ) } >
                    <RangeControl
            label="Max Number of Words"
            value={ max }
            onChange={ ( value ) => setAttributes( {'max':value} ) }
            min={ 10 }
            max={ 110 }
        />
            <ToggleControl
                label={__("Hide Event Type",'rsvpmaker')}
                checked={ hide_type }
                onChange={ ( hide_type ) => { setAttributes( { hide_type } ) } }
            />
            <p><em>By default, any RSVPMaker Event Types that have been set will be shown beneath the excerpt. An Event Type is similar to a blog post category.</em></p>
            </PanelBody>
            </InspectorControls>
            </div>
        );	} }
    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <ExcerptInspector {...props}/>
                        {excerptobj.excerpt && (
                        <>
                        <p>{excerptobj.excerpt}</p>
                        {!hide_type && excerptobj.types && <p className="rsvpmeta" dangerouslySetInnerHTML={{__html: excerptobj.types}} />}                       
                        </>
                        )}
                        {!excerptobj.excerpt && (
                        <>
                        <p>Loading ...</p>
                        </>
                        )}
                   </div>
                 </Fragment>
    );
}
