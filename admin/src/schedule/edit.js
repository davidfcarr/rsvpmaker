const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { RichText } = wp.blockEditor;
const { Fragment } = wp.element;
const { Component } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, DateTimePicker, SelectControl, ToggleControl, TextControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';
const rsvptypes = [{value: '', label: 'None selected (optional)'}];

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
	const { attributes, className, setAttributes, isSelected, clientId } = props;

	const blockProps = useBlockProps({ className });

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

	return (
        <Fragment>
        <TimeInspector { ...props } />
		<div className="schedule-placeholder">{__('Daily schedule of events')}</div>
        </Fragment>
		);

}

class TimeInspector extends Component {

	render() {
		const { attributes, setAttributes, className } = this.props;
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Start Time', 'rsvpmaker' ) } >
					<SelectControl
							label={ __( 'Set Start Time', 'rsvpmaker' ) }
							value={ attributes.start_on }
							onChange={ ( start_on ) => setAttributes( { start_on } ) }
							options={ [
								{ value: 0, label: __( 'No', 'rsvpmaker' ) },
								{ value: 1, label: __( 'Yes', 'rsvpmaker' ) },
							] }
						/>
		
			{(attributes.start_on > 0) && (
			<DateTimePicker
		    is12Hour={ true }
		    currentDate={ attributes.start }
			onChange={ ( start ) => setAttributes( { start })}
		    />									 
			 )}
				</PanelBody>
			<PanelBody title={ __( 'End Time', 'rsvpmaker' ) } >
					<SelectControl
							label={ __( 'Set End Time', 'rsvpmaker' ) }
							value={ attributes.end_on }
							onChange={ ( end_on ) => setAttributes( { end_on } ) }
							options={ [
								{ value: 0, label: __( 'No', 'rsvpmaker' ) },
								{ value: 1, label: __( 'Yes', 'rsvpmaker' ) },
							] }
						/>
			{(attributes.end_on > 0) && (
			 <div id="endtime">
		<DateTimePicker
		    is12Hour={ true }
		    currentDate={ attributes.end }
			onChange={ ( end ) => setAttributes( { end })}
		    />
			</div>
			 )}
	</PanelBody>
	<PanelBody title={ __( 'Display Options', 'rsvpmaker' ) } >
	<ToggleControl
        label={__('Display "Show in my timezone" button','rsvpmaker')}
        checked={ attributes.convert_tz }
        onChange={ ( convert_tz ) => { setAttributes( { convert_tz } ) } }
    />

     <SelectControl
        label={__("Event Type",'rsvpmaker')}
        value={ attributes.type }
        options={ rsvptypes }
        onChange={ ( type ) => { setAttributes( { type: type } ) } }
    />
	<TextControl
		label={__('Max Events Displayed')}
		value={attributes.limit}
        onChange={ ( limit ) => { setAttributes( { limit: limit } ) } }
	/>		
    </PanelBody>
			</InspectorControls>
		);
	}
}