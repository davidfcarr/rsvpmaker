const { __ } = wp.i18n;
const { TextControl } = wp.components;

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
	const blockProps = useBlockProps({ className: props.className });
	const { attributes: { text }, setAttributes, isSelected } = props;
			
		if(isSelected)
		return (
			<div {...blockProps}>
	<TextControl
        label={ __( 'Text', 'rsvpmaker' ) }
        value={ text }
        onChange={ ( text ) => setAttributes( { text } ) }
    />	
	<p class="dashicons-before dashicons-welcome-write-blog"><em>(Not shown on front end. Delete from finished post)</em></p>
				</div>
			);
		
		return (
			<div {...blockProps}>
				<p class="dashicons-before dashicons-welcome-write-blog">{text} <em>(Placeholder: Not shown on front end)</em></p>
				</div>
			);

}
