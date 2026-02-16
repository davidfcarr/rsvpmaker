const { __ } = wp.i18n;
const { useBlockProps, InnerBlocks } = wp.blockEditor;
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

	const { attributes, className, setAttributes, isSelected } = props;
	const blockProps = useBlockProps();
	return (
	<div {...blockProps}>
<h3>{__("Guest Fields",'rsvpmaker')}</h3>
    <TextControl
        label={__("Limit (if any). Enter a maximum number of guests or leave blank for no limit.")}
        value={ attributes.limit }
        onChange={ ( limit ) => { setAttributes( { limit: (limit) ? parseInt(limit) : 0 } ) } }
    />
<div className="guestnote">{__('Guests section will include fields you checked off above (such as First Name, Last Name), plus any others you embed below (information to be collected about guests ONLY).','rsvpmaker')}<ul><li>{__('You MUST check "Include on Guest Form"','rsvpmaker')}</li><li>{__('"Required" checkbox does not work in guest fields','rsvpmaker')}</li><li>{__('This block is not intended for use outside of an RSVPMaker RSVP Form document','rsvpmaker')}</li></ul></div>
	<InnerBlocks />
	</div>
	);
}
