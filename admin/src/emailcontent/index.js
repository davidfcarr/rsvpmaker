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
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

const deprecated = [
	{
		save: ( props ) => {
			const { className } = props;
			const legacyWhiteStyle = {
				backgroundColor: '#fff',
				color: '#000',
				padding: '5px',
				marginLeft: 'auto',
				marginRight: 'auto',
				maxWidth: '600px',
				border: 'thin solid gray',
				minHeight: '20px',
				marginBottom: '5px',
			};
			const blockProps = useBlockProps.save( { className, style: legacyWhiteStyle } );
			return <div { ...blockProps }><InnerBlocks.Content /></div>;
		},
	},
	{
		save: ( props ) => {
			const { className } = props;
			const legacyGrayStyle = {
				backgroundColor: '#efefef',
				color: '#000',
				padding: '5px',
				marginLeft: 'auto',
				marginRight: 'auto',
				maxWidth: '600px',
				border: 'thin solid gray',
				minHeight: '20px',
				marginBottom: '5px',
			};
			const blockProps = useBlockProps.save( { className, style: legacyGrayStyle } );
			return <div { ...blockProps }><InnerBlocks.Content /></div>;
		},
	},
	{
		save: ( props ) => {
			const { attributes, className } = props;
			const bodyStyle = {
				backgroundColor: attributes.backgroundColor,
				color: attributes.color,
				padding: attributes.padding,
				marginLeft: attributes.marginLeft,
				marginRight: attributes.marginRight,
				maxWidth: attributes.maxWidth,
				border: attributes.border,
				minHeight: '20px',
			};
			const blockProps = useBlockProps.save( { className, style: bodyStyle } );
			return <div { ...blockProps }><InnerBlocks.Content /></div>;
		},
	},
];

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
	deprecated,
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
							[ [ 'core/post-title',  {"isLink":true}  ], [ 'core/post-featured-image' ], [ 'rsvpmaker/loop-blocks' ], [ 'core/read-more', {"content":"Read More \u003e\u003e"} ] ],
						],
						[ 'core/query-pagination' ],
						[ 'core/query-no-results', {}, [['core/paragraph', {"content": "No events found."}] ]],
					] : [
						[
							'core/post-template',
							{"layout":{"type":"grid","columnCount":2}},
							[ [ 'core/post-title',  {"isLink":true}  ], [ 'core/post-featured-image' ], [ 'rsvpmaker/loop-blocks' ], [ 'core/read-more', {"content":"Read More \u003e\u003e"} ] ],
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
