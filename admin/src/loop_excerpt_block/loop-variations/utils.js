/**
 * WordPress dependencies - from https://github.com/WordPress/gutenberg/blob/e9bccc865ced643bdc1e262aa7efac16253dda94/packages/block-library/src/query/utils.js
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { decodeEntities } from '@wordpress/html-entities';
import {
	cloneBlock,
	getBlockSupport,
	store as blocksStore,
} from '@wordpress/blocks';

/** @typedef {import('@wordpress/blocks').WPBlockVariation} WPBlockVariation */

/**
 * @typedef IHasNameAndId
 * @property {string|number} id   The entity's id.
 * @property {string}        name The entity's name.
 */

export const useTaxonomies = ( postType ) => {
	const taxonomies = useSelect(
		( select ) => {
			const { getTaxonomies } = select( coreStore );
			return getTaxonomies( {
				type: postType,
				per_page: -1,
			} );
		},
		[ postType ]
	);
	return useMemo( () => {
		return taxonomies?.filter(
			( { visibility } ) => !! visibility?.publicly_queryable
		);
	}, [ taxonomies ] );
};
