const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { Component } = wp.element;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl, RadioControl } = wp.components;

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import { applyFieldLabelChange } from '../form-field-label';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes: { label, fieldnote, slug, required, guestform, labelPosition, fieldType }, setAttributes, isSelected } = props;
	var profilename = 'profile['+slug+']';
	const isInlineLabel = labelPosition === 'inline';
	const wrapperClassName = isInlineLabel ? 'rsvp-label-inline' : '';
	const blockProps = useBlockProps({ className: wrapperClassName });
			return (
			<Fragment>
			<FieldInspector {...props} />
			<div { ...blockProps }>
<label>{label}:</label>
<div className="rsvp-input-line"><span className={required}><input className={slug} inert tabIndex="-1" type={fieldType || 'text'} name={profilename} id={slug} value="" /></span></div>
				</div>
{fieldnote && <div className="rsvp-fieldnote">{fieldnote}</div>}
{isSelected && (<div><em>{__('Set form label and other properties in sidebar. For use within an RSVPMaker registration form.','rsvpmaker')}</em></div>) }
			</Fragment>
			);
}

class FieldInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	let toggleRequired = (attributes.required == 'required'); //make true/false
	function setLabel(label) {
		applyFieldLabelChange({
			label,
			attributes,
			setAttributes,
			setGuestform: true,
		});
	}
	function setRequired(toggleRequired) {
		let required = (toggleRequired) ? 'required' : '';
		setAttributes({required: required});
	}
		return (
			<InspectorControls key="fieldinspector">
			<PanelBody title={ __( 'Field Properties', 'rsvpmaker' ) } >
			<TextControl
				label={ __( 'Label', 'rsvpmaker' ) }
				value={ attributes.label }
				onChange={ ( label ) => setLabel( label  ) }
			/>
			<TextControl
				label={ __( 'Field Note (optional additional information)', 'rsvpmaker' ) }
				value={ attributes.fieldnote || '' }
				onChange={ ( fieldnote ) => setAttributes( { fieldnote } ) }
			/>
			<SelectControl
				label={ __( 'Field Label Position', 'rsvpmaker' ) }
				value={ attributes.labelPosition || 'stacked' }
				options={ [
					{ label: __( 'On separate line', 'rsvpmaker' ), value: 'stacked' },
					{ label: __( 'Inline (left of field)', 'rsvpmaker' ), value: 'inline' },
				] }
				onChange={ ( labelPosition ) => setAttributes( { labelPosition } ) }
			/>
			<SelectControl
				label={ __( 'Input Type', 'rsvpmaker' ) }
				value={ attributes.fieldType || 'text' }
				options={ [
					{ label: __( 'Text', 'rsvpmaker' ), value: 'text' },
					{ label: __( 'Email', 'rsvpmaker' ), value: 'email' },
					{ label: __( 'Telephone', 'rsvpmaker' ), value: 'tel' },
					{ label: __( 'Number', 'rsvpmaker' ), value: 'number' },
					{ label: __( 'Date/Time (local)', 'rsvpmaker' ), value: 'datetime-local' },
					{ label: __( 'Date', 'rsvpmaker' ), value: 'date' },
					{ label: __( 'Time', 'rsvpmaker' ), value: 'time' },
					{ label: __( 'URL', 'rsvpmaker' ), value: 'url' },
					{ label: __( 'Password', 'rsvpmaker' ), value: 'password' },
					{ label: __( 'Color', 'rsvpmaker' ), value: 'color' },
				] }
				onChange={ ( fieldType ) => setAttributes( { fieldType } ) }
			/>
			<ToggleControl
				label={ __( 'Required', 'rsvpmaker' ) }
				checked={ toggleRequired }
				help={ attributes.required ? 'Required' : 'Not required' } 
				onChange={ ( toggleRequired ) => {setRequired( toggleRequired ) }}
			/>
			<ToggleControl
				label={ __( 'Include on Guest Form', 'rsvpmaker' ) }
				checked={ attributes.guestform }
				help={ attributes.guestform ? 'Included' : 'Not included' } 
				onChange={ ( guestform ) => {setAttributes( {guestform: guestform} ) }}
			/>
				</PanelBody>
				</InspectorControls>
);	} }

