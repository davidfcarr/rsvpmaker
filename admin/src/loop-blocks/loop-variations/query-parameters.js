import { InspectorControls } from '@wordpress/block-editor';
import {PanelBody, Panel, PanelRow, SelectControl}  from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function RSVPQuery (props) {
	const  { attributes,setAttributes,attributes: {query} }  = props;
	const {perPage, include} = query;
	const opt = [];
	for(let i=1; i <= 50; i++)
		opt.push({value: i, label: i});
	return (<div>
		<p><SelectControl label="Max Number of Events" value={ perPage } options={ opt } onChange={ ( value ) => { setAttributes( {query: {...query,perPage: value}} );} } /></p>
	</div>)
}
/*		
import { useFutureDateOptions } from '../../queries';
const rsvpoptions = useFutureDateOptions([{label: __('Choose event'),value: ''}]);
<p><SelectControl label="Single Event" value={ include } options={ rsvpoptions } onChange={ ( value ) => { setAttributes( {query: {...query,include: value}} );} } /></p>
*/