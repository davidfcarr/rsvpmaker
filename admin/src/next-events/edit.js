const { __ } = wp.i18n;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { SelectControl } = wp.components;
import apiFetch from '@wordpress/api-fetch';
const { RawHTML } = wp.element;

import { useState, useEffect } from 'react';

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
	const blockProps = useBlockProps();
	const { attributes: { number_of_posts }, setAttributes, isSelected } = props;
	const [preview, setPreview] = useState([]);
	
	const fetchPreview = async () => {
		const path = '/rsvpmaker/v1/preview/next-events?number_of_posts='+number_of_posts;
		console.log(path);
		const preview = await apiFetch({path});
		setPreview(preview);
	}

	useEffect( () => { fetchPreview(); }, [number_of_posts]);
/*	if ( preview.length === 0 ) {
		return <div {...useBlockProps()}>Loading</div>;
	}
*/
		return (
			<div {...blockProps}>

	<InspectorControls key="eventinspector">
	<SelectControl
	label={__("Number of Event Links Shown",'rsvpmaker')}
	value={ number_of_posts }
	options={ [
	{label: '1 (next only)', value:1},
	{label: '2 (next +1)', value:2},
	{label: '3 (next +2)', value:3},
	{label: '4 (next +3)', value:4},
	{label: '5 (next +4)', value:5},
	{label: '6 (next +5)', value:6},
	{label: '7 (next +6)', value:7},
	{label: '8 (next +7)', value:8},
	{label: '9 (next +8)', value:9},
	{label: '10 (next +9)', value:10}] }
		onChange={ ( number_of_posts ) => { setAttributes( { number_of_posts } ) } }
/>
</InspectorControls>
<RawHTML>{preview}</RawHTML>
{!preview && <p>Loading...</p>}
			</div>
		);
}

