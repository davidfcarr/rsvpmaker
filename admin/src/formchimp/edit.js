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
	const { attributes: { label, checked }, setAttributes, isSelected } = props;
	let slug = 'email_list_ok';
	let profilename = 'profile['+slug+']';
	const blockProps = useBlockProps({ className: props.className });
	return (
	<div { ...blockProps }>
			<InspectorControls key="choiceinspector">
			<PanelBody title={ __( 'Field Properties', 'rsvpmaker' ) } >
			<TextControl
				label={ __( 'Label', 'rsvpmaker' ) }
				value={ label }
				onChange={ ( label ) => setAttributes( {label: label} ) }
			/>
			<ToggleControl
				label={ __( 'Checked by Default', 'rsvpmaker' ) }
				checked={ checked }
				help={ checked ? 'Included' : 'Not included' } 
				onChange={ ( checked ) => {setAttributes( {checked: checked} ) }}
			/>
			</PanelBody>
			</InspectorControls>
<p><input className={slug} type="checkbox" name={profilename} id={slug} value="1" checked={checked} /> {label}</p>
	</div>
	);
}
