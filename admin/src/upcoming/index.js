/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType, createBlock, createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ 'core/query' ],
				transform: ( atts ) => {
					const qatts = {"queryId":0,"query":{"perPage":20,"pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"rsvpmaker/rsvpmaker-loop"};
					const template = (atts.calendar && parseInt(atts.calendar)) ? [
						[ 'rsvpmaker/calendar', atts ],
						[
							'core/post-template',
							{"layout":{"type":"grid","columnCount":2}},
							[ [ 'core/post-title',  {"isLink":true}  ], [ 'core/post-featured-image' ], [ 'rsvpmaker/loop-excerpt', {"show_rsvp_button":true} ], [ 'core/read-more', {"content":"Read More \u003e\u003e"} ] ],
						],
						[ 'core/query-pagination' ],
						[ 'core/query-no-results', {}, [['core/paragraph', {"content": "No events found."}] ]],
					] : [
						[
							'core/post-template',
							{"layout":{"type":"grid","columnCount":2}},
							[ [ 'core/post-title',  {"isLink":true}  ], [ 'core/post-featured-image' ], [ 'rsvpmaker/loop-excerpt', {"show_rsvp_button":true} ], [ 'core/read-more', {"content":"Read More \u003e\u003e"} ] ],
						],
						[ 'core/query-pagination' ],
						[ 'core/query-no-results', {}, [['core/paragraph', {"content": "No events found."}] ]],
					];
					const innerblocks = createBlocksFromInnerBlocksTemplate( template );
					return createBlock('core/query',qatts, innerblocks);
				},
			},
		]
	},

} );
