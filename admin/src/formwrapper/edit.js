const { __ } = wp.i18n;
const { InspectorControls, useBlockProps, InnerBlocks } = wp.blockEditor;
const { PanelBody, TextControl, ToggleControl } = wp.components;

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
	const { attributes, attributes: {appslug, button_label, recaptcha}, className, setAttributes, isSelected } = props;
	const RFORM_TEMPLATE = [
		[ 'rsvpmaker/formfield', { label: 'First Name', slug: 'first_name' } ],
		[ 'rsvpmaker/formfield', { label: 'Last Name', slug: 'last_name' } ],
		[ 'rsvpmaker/formfield', { label: 'Email', slug: 'email', "required":"required" } ],
	];
	const blockProps = useBlockProps({ className });
	return (
<div { ...blockProps }>
		<InspectorControls key="formwrapperinspector">
			<PanelBody title={ __( 'Form Options', 'rsvpmaker' ) } >
	<TextControl
		label={__("Button Label",'rsvpmaker')}
		value={ button_label }
		onChange={ ( button_label ) => { setAttributes( { button_label: button_label } ) } }
	/>
	<TextControl
		label={__("App Slug",'rsvpmaker')}
		value={ appslug }
		onChange={ ( appslug ) => { setAttributes( { appslug: appslug } ) } }
	/>
	<p><em>Leave the App Slug as "contact" for the built-in contact form. Change it to integrate with your own custom app (see <a href="https://rsvpmaker.com/knowledge-base/rsvpmaker-form-wrapper-block/">documentation</a>).</em></p>
	<ToggleControl
		label={__("Include ReCaptcha",'rsvpmaker')}
		checked={ recaptcha }
		onChange={ ( recaptcha ) => { setAttributes( { recaptcha } ) } }
	/>
	<p><em>ReCaptcha must be enabled first on the RSVPMaker Settings screen.</em></p>
			</PanelBody>
		</InspectorControls>
	{!isSelected && <p><em>Click here to set form options</em></p>}
	<InnerBlocks template={ RFORM_TEMPLATE } />
{recaptcha && <div><img src="/wp-content/plugins/rsvpmaker/images/recaptcha-preview.png" height="112" width="426" alt="ReCaptcha goes here" /></div>}
<p><button>{button_label}</button></p>
</div>
		);
 }
