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
	const { attributes: { label, fieldnote, slug, choicearray, guestform, labelPosition }, setAttributes, isSelected } = props;
	var profilename = 'profile['+slug+']';
	const isInlineLabel = labelPosition === 'inline';
	const wrapperClassName = isInlineLabel ? 'rsvp-label-inline' : '';
	const blockProps = useBlockProps({ className: wrapperClassName });
	return (
	<Fragment>
	<ChoiceInspector {...props} />
	<div { ...blockProps }>
	<p><label>{label}:</label></p>
	<div className="rsvp-input-line"><span><select className={slug} name={profilename} id={slug} >{choicearray.map(function(opt, i){
			return <option value={ opt }>{opt}</option>;
		})}</select></span></div>
	</div>
{fieldnote && <div className="rsvp-fieldnote">{fieldnote}</div>}
{isSelected && (<div><em>{__('Set form label and other properties in sidebar. For use within an RSVPMaker registration form.','rsvpmaker')}</em></div>) }
	</Fragment>
	);
}

class ChoiceInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	const choices =attributes.choicearray.join('\n');
	function setLabel(label) {
		applyFieldLabelChange({
			label,
			attributes,
			setAttributes,
			setGuestform: true,
		});
	}
		
	function setChoices(choices) {
		setAttributes({choicearray: choices.split('\n')});
	}
		return (
			<InspectorControls key="choiceinspector">
			<PanelBody title={ __( 'Field Properties', 'rsvpmaker' ) } >
			<TextControl
				label={ __( 'Label', 'rsvpmaker' ) }
				value={ attributes.label }
				onChange={ ( label ) => setLabel(label) }
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
			<TextareaControl
				label={ __( 'Choices', 'rsvpmaker' ) }
				value={ choices }
				onChange={ ( choices ) => setChoices( choices  ) }
			/>
				<div><em>Enter each choice on a separate line</em></div>
			<ToggleControl
				label={ __( 'Include on Guest Form', 'rsvpmaker' ) }
				checked={ attributes.guestform }
				help={ attributes.guestform ? 'Included' : 'Not included' } 
				onChange={ ( guestform ) => {setAttributes( {guestform: guestform} ) }}
			/>
			<ToggleControl
				label={ __( 'Check first choice by default', 'rsvpmaker' ) }
				checked={ attributes.defaultToFirst }
				help={ attributes.defaultToFirst ? 'First item selected by default' : 'No default' } 
				onChange={ ( defaultToFirst ) => {setAttributes( {defaultToFirst: defaultToFirst} ) }}
			/>
	</PanelBody>
	</InspectorControls>
);	} }
