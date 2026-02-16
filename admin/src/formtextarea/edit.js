const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { Component } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl, RadioControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';

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
	const { attributes: { label, slug, rows, guestform }, setAttributes, isSelected } = props;
	var profilename = 'profile['+slug+']';
			return (
			<Fragment>
			<TextAreaInspector {...props} />
			<div className={ props.className }>
<p><label>{label}:</label></p> <p><textarea inert tabindex="-1" rows={rows} className={slug} type="text" name={profilename} id={slug}></textarea></p>
<div><em>{__('Set properties in sidebar. Intended for use within an RSVPMaker registration form.','rsvpmaker')}</em></div>
			</div>
			</Fragment>
			);
}

class TextAreaInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	function setLabel(label) {
		const slug = attributes.slug;
		let simpleSlug = label.replaceAll(/[^A-Za-z0-9]+/g,'_');
		simpleSlug = simpleSlug.trim().toLowerCase();
		setAttributes({slug: simpleSlug});
		setAttributes({label: label});
		setAttributes({guestform: true});
	}
		return (
			<InspectorControls key="fieldinspector">
			<PanelBody title={ __( 'Field Properties', 'rsvpmaker' ) } >
			<TextControl
				label={ __( 'Label', 'rsvpmaker' ) }
				value={ attributes.label }
				onChange={ ( label ) => setLabel( label  ) }
			/>
    <SelectControl
        label="Rows"
        value={ attributes.rows }
        options={ [
            { label: '2', value: '2' },
            { label: '3', value: '3' },
            { label: '4', value: '4' },
            { label: '5', value: '5' },
            { label: '6', value: '6' },
            { label: '7', value: '7' },
            { label: '8', value: '8' },
            { label: '9', value: '9' },
            { label: '10', value: '10' },
        ] }
        onChange={ ( rows ) => { setAttributes( { rows: rows } ) } }
    />
			<ToggleControl
				label={ __( 'Include on Guest Form', 'rsvpmaker' ) }
				checked={ attributes.guestform }
				help={ attributes.required ? 'Included' : 'Not included' } 
				onChange={ ( guestform ) => {setAttributes( {guestform: guestform} ) }}
				 
			/>
				</PanelBody>
				</InspectorControls>
);	} }
