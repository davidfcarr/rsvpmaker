const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { Component } = wp.element;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;

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
	const { attributes: { label, fieldnote }, setAttributes, isSelected } = props;
	const blockProps = useBlockProps();
			return (
			<Fragment>
			<TextAreaInspector {...props} />
			<div { ...blockProps }>
			<p><label>{label}:</label></p>
			{fieldnote && <p><em>{fieldnote}</em></p>}
			<div style={{ marginLeft: '1.25em' }}><textarea name="note" inert tabindex="-1"></textarea></div>
			<div><em>{__('Note for bottom of RSVP form. Only one allowed. Use RSVPField Text Area for any additional text fields. Set properties in sidebar. Intended for use within an RSVPMaker registration form.','rsvpmaker')}</em></div>
			</div>
			</Fragment>
			);
		}

class TextAreaInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	function setLabel(label) {
		setAttributes({ label: label.trim() });
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
				</PanelBody>
				</InspectorControls>
);	} }
