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
const { Panel, PanelBody, SelectControl, RadioControl, TextControl, ColorPalette, FontSizePicker } = wp.components;
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
	const { attributes: { post_id, type, one_hideauthor, one_format, hide_past }, setAttributes, isSelected } = props;
    const [rsvpupcoming, setRsvpupcoming] = useState([
		{label: __('Choose event'),value: ''},
		{label: __('Next event'),value: 'next'},
		{label: __('Next event - RSVP on'),value: 'nextrsvp'}
	]);

	useEffect( () => {
		apiFetch( {path: 'rsvpmaker/v1/future'} ).then( events => {
			let options = [
				{label: __('Choose event, please'),value: ''},
				{label: __('Next event'),value: 'next'},
				{label: __('Next event - RSVP on'),value: 'nextrsvp'}
			];
			if(Array.isArray(events)) {
				events.map( function(event) { 
					if(event.ID) { 
						var title = (event.neatdate) ? event.post_title+' - '+event.neatdate : event.post_title; 
						options.push({value: event.ID, label: title });
					}
				});
			} else {
				var eventsarray = Object.values(events);
				eventsarray.map( function(event) { 
					if(event.ID) { 
						var title = (event.neatdate) ? event.post_title+' - '+event.neatdate : event.post_title; 
						options.push({value: event.ID, label: title });
					}
				});
			}
			setRsvpupcoming(options);
		}).catch(err => {
			console.log(err);
		});
	}, []);

	if(post_id == '')
		setAttributes( { post_id: 'next' } );

		return (
			<div { ...useBlockProps({ className: props.className }) }>
				<p class="dashicons-before dashicons-clock"><strong>RSVPMaker</strong>: Embed just the form for a single event.
				</p>
<InspectorControls key="eventlistinginspector">
    <Panel>
        <PanelBody>
<SelectControl
        label={__("Select Post",'rsvpmaker')}
        value={ post_id }
        options={ rsvpupcoming }
        onChange={ ( post_id ) => { setAttributes( { post_id: post_id } ) } }
    />
</PanelBody>
</Panel>
</InspectorControls>
			</div>
		);
}
