import { InspectorControls } from '@wordpress/block-editor';
import {PanelBody, Panel, PanelRow, SelectControl}  from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function RSVPEventOrder (props) {
	const  { attributes,setAttributes }  = props;
    const { query } = attributes;
	console.log('rsvpcontrols query',query);
	console.log('rsvpcontrols query namespace',attributes.namespace);
	const eventorder = query.eventOrder ? query.eventOrder : 'future';
	console.log('eventorder',eventorder);
	return <p><SelectControl label="Event Order" value={ eventorder } options={ [{ value: 'future', label: 'Future' },{ value: 'past',  label: "Past" },] } onChange={ ( value ) => { setAttributes( {query: {...query,eventOrder: value}} );} } /></p>
}
