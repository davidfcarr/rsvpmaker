import { InspectorControls } from '@wordpress/block-editor';
import {PanelBody, Panel, PanelRow, SelectControl, ToggleControl}  from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function RSVPQuery (props) {
	const  { attributes,setAttributes,attributes: {query} }  = props;
	const {perPage, include, rsvp_only, offset} = query;
	const rsvpOnlyEnabled = (typeof attributes.rsvp_only === 'boolean') ? attributes.rsvp_only : !!rsvp_only;
	const skipFirstEnabled = offset === 1;
	const opt = [];
	for(let i=1; i <= 50; i++)
		opt.push({value: i, label: i});
	return (<div>
		<p><SelectControl label="Max Number of Events" value={ perPage } options={ opt } onChange={ ( value ) => { setAttributes( {query: {...query,perPage: value}} );} } /></p>
		<div style={{marginTop: '15px'}}>
		<ToggleControl
			label={__("RSVP On Only", 'rsvpmaker')}
			checked={ rsvpOnlyEnabled }
			help={__('Only include events where the _rsvp_on postmeta flag is enabled.', 'rsvpmaker')}
			onChange={ ( value ) => { setAttributes( {query: {...query,rsvp_only: value}, rsvp_only: value} );} }
		/>
		<ToggleControl
			label={__("Skip First Entry", 'rsvpmaker')}
			checked={ skipFirstEnabled }
			help={__('Skip the first event in the results (useful for displaying the featured event separately).', 'rsvpmaker')}
			onChange={ ( value ) => { setAttributes( {query: {...query,offset: value ? 1 : 0}} );} }
		/>
		</div>
	</div>)
}
/*		
import { useFutureDateOptions } from '../../queries';
const rsvpoptions = useFutureDateOptions([{label: __('Choose event'),value: ''}]);
<p><SelectControl label="Single Event" value={ include } options={ rsvpoptions } onChange={ ( value ) => { setAttributes( {query: {...query,include: value}} );} } /></p>
*/