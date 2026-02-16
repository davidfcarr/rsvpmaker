const { __ } = wp.i18n;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { SelectControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

import { useState, useEffect } from 'react';

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
	const blockProps = useBlockProps();
	const { attributes: { post_id, type, one_hideauthor, one_format, hide_past }, attributes, setAttributes, isSelected } = props;
	const [eventHtml, setEventHtml] = useState(null);
	
	if(post_id == '')
		setAttributes( { post_id: 'next' } );
	apiFetch( {path: addQueryArgs( '/rsvpmaker/v1/preview/one', attributes)} ).then( ( x ) => {
		console.log('downloaded event html',x);
		setEventHtml(x);
	} );
const rsvpupcoming = [{label: __('Choose event'),value: ''},{label: __('Next event'),value: 'next'},{label: __('Next event - RSVP on'),value: 'nextrsvp'}];
apiFetch( {path: 'rsvpmaker/v1/future'} ).then( events => {
	if(Array.isArray(events)) {
		 events.map( function(event) { if(event.ID) { var title = (event.date) ? event.post_title+' - '+event.date : event.post_title; rsvpupcoming.push({value: event.ID, label: title }) } } );
	}
	 else {
		 var eventsarray = Object.values(events);
		 eventsarray.map( function(event) { if(event.ID) { var title = (event.date) ? event.post_title+' - '+event.date : event.post_title; rsvpupcoming.push({value: event.ID, label: title }) } } );
		}
}).catch(err => {
	console.log(err);
});

const rsvptypes = [{value: '', label: 'None selected (optional)'}];
apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
	if(Array.isArray(types))
			types.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
		else {
			var typesarray = Object.values(types);
			typesarray.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
		}
}).catch(err => {
	console.log(err);
});	

const rsvpauthors = [{value: '', label: 'Any'}];
apiFetch( {path: 'rsvpmaker/v1/authors'} ).then( authors => {
	if(Array.isArray(authors))
			authors.map( function(author) { if(author.ID && author.name) rsvpauthors.push({value: author.ID, label: author.name }) } );
		else {
			authors = Object.values(authors);
			authors.map( function(author) { if(author.ID && author.name) rsvpauthors.push({value: author.ID, label: author.name }) } );
		}
}).catch(err => {
	console.log(err);
});	

		return (
			<div {...blockProps}>
<InspectorControls key="eventinspector">
<SelectControl
        label={__("Select Post",'rsvpmaker')}
        value={ post_id }
        options={ rsvpupcoming }
        onChange={ ( post_id ) => { setAttributes( { post_id: post_id } ) } }
    />
<SelectControl
        label={__("Format",'rsvpmaker')}
        value={ one_format }
        options={ [
	{label: 'Event with Form', value:''},
	{label: 'Event with Button', value:'button'},
	{label: 'Event Excerpt with Button', value:'excerpt'},
	{label: 'Headline, Date and Icons, Button', value:'headline_date_button'},
	{label: 'Button Only', value:'button_only'},
	{label: 'Form Only', value:'form'},
	{label: 'Compact (Headline/Date/Button)', value:'compact'},
	{label: 'Dates Only', value:'embed_dateblock'}] }
        onChange={ ( one_format ) => { setAttributes( { one_format: one_format } ) } }
/>

<SelectControl
        label={__("Hide After",'rsvpmaker')}
        value={ hide_past }
        options={ [
	{label: 'Not Set', value:''},
	{label: '1 hour', value:'1'},
	{label: '2 hours', value:'2'},
	{label: '3 hours', value:'3'},
	{label: '4 hours', value:'4'},
	{label: '5 hours', value:'5'},
	{label: '6 hours', value:'6'},
	{label: '7 hours', value:'7'},
	{label: '8 hours', value:'8'},
	{label: '12 hours', value:'12'},
	{label: '18 hours', value:'18'},
	{label: '24 hours', value:'24'},
	{label: '2 days', value:'48'},
	{label: '3 days', value:'72'}] }
        onChange={ ( hide_past ) => { setAttributes( { hide_past: hide_past } ) } }
/>

<SelectControl
        label={__("Event Type",'rsvpmaker')}
        value={ type }
        options={ rsvptypes }
        onChange={ ( type ) => { setAttributes( { type: type } ) } }
    />

<SelectControl
        label={__("Show Author",'rsvpmaker')}
        value={ one_hideauthor }
        options={ [{label: 'No', value:'1'},{label: 'Yes', value:'0'}] }
        onChange={ ( one_hideauthor ) => { setAttributes( { one_hideauthor: one_hideauthor } ) } }
    />
</InspectorControls>
{eventHtml && (
<div dangerouslySetInnerHTML={{__html: eventHtml}} />
)}
{!eventHtml && (
<div>
<p>Loading ...</p>
</div>
)}
</div>
);

}

