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

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes: { label, slug, required, guestform }, setAttributes, isSelected } = props;
	var profilename = 'profile['+slug+']';
	const blockProps = useBlockProps();
			return (
			<Fragment>
			<FieldInspector {...props} />
			<div { ...blockProps }>
			<div className={ props.className }>
<p><label>{label}:</label> <span className={required}><input className={slug} inert tabindex="-1" type="text" name={profilename} id={slug} value="" /></span></p>
{isSelected && (<div><em>{__('Set form label and other properties in sidebar. For use within an RSVPMaker registration form.','rsvpmaker')}</em></div>) }
				</div>
				</div>
			</Fragment>
			);
}

class FieldInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	let toggleRequired = (attributes.required == 'required'); //make true/false
	function setLabel(label) {
		const slug = attributes.slug;
		if(attributes.sluglocked)
			{//don't change default required slugs
			setAttributes({label: label});
			return;
			}
		let simpleSlug = label.replaceAll(/[^A-Za-z0-9]+/g,'_');
		simpleSlug = simpleSlug.trim().toLowerCase();
		setAttributes({slug: simpleSlug});
		setAttributes({label: label});
		setAttributes({guestform: true});
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

