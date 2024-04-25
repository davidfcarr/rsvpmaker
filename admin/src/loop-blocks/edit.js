/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal Dependencies
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
export default function Edit() {

	const RSVP_TEMPLATE = [
		['rsvpmaker/rsvpdateblock'],
		['rsvpmaker/excerpt'],
		['core/read-more',{"content":"Read More \u003e\u003e","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}}} ], 
		['rsvpmaker/button'],
	];

	return (
		<div { ...useBlockProps() }>
			<InnerBlocks template={ RSVP_TEMPLATE } />
		</div>
	);
}
