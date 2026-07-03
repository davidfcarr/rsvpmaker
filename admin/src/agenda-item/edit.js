/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';

const { SelectControl, ToggleControl, TextControl, DateTimePicker } = wp.components;
import { useSelect, useDispatch } from '@wordpress/data';
import { __experimentalNumberControl as NumberControl } from '@wordpress/components';
import TimeBlock from '../TimeBlock.js';
import { useRsvpmakerRest } from '../useRsvpmakerRest.js';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

function toNumber(value, fallback = 0) {
	const parsed = Number(value);
	return Number.isFinite(parsed) ? parsed : fallback;
}

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */

export default function Edit({ attributes, attributes: { title, itemType, setDateTime, duration, displayTime, blockId, allowNotes }, setAttributes, isSelected, className, clientId }) {
const [manualTime, setManualTime] = useState(setDateTime != '' ? true : false);
const rsvpmaker_rest = useRsvpmakerRest();
	useEffect(() => {
        // Only set it if it hasn't been set before (prevents overwriting on reload)
        if ( ! blockId ) {
            setAttributes({ blockId: `b-${clientId}` });
        }
    }, [ blockId, clientId, setAttributes ]);

return (			
<div {...useBlockProps()}>
<TimeBlock 
	clientId={clientId} 
	onTimeCalculated={(timeString) => {
		if (displayTime !== timeString) {
			setAttributes({ displayTime: timeString });
		}
	}} 
/>
<strong>{itemType ? itemType + ': ' : ''}{title}</strong>
<InnerBlocks />
<InspectorControls>
	<TextControl
	
					label={ __( 'Title', 'rsvpmaker-for-toastmasters' ) }
	
					value={ title }
	
					onChange={ ( title ) => {
						setAttributes({ title });
					} }
	
	/>
		
	<div>	<NumberControl
	
			label={ __( 'Duration in Minutes', 'rsvpmaker' ) }
	
			value={ duration }
			min = {1}
	
			onChange={ ( duration ) => {
				const numericDuration = toNumber(duration, 1);
				setAttributes( { duration: numericDuration } );
			} }
	
		/>
	
		</div>
	
	
	<div>
		
	</div>
		
	<SelectControl
	
					label={ __( 'ItemType', 'rsvpmaker-for-toastmasters' ) }
	
					value={ itemType }
	
					onChange={ ( itemType ) => setAttributes( { itemType } ) }
	
					options={ [{value: '', label: ''},{value: 'Presentation', label: 'Presentation'},{value: 'Tour', label: 'Tour'},{value: 'Meeting', label: 'Meeting'},{value: 'Breakfast', label: 'Breakfast'},{value: 'Lunch', label: 'Lunch'},{value: 'Dinner', label: 'Dinner'},{value: 'Meal', label: 'Meal'},{value: 'Break', label: 'Break'}] }
	
				/>
	<ToggleControl
		label={ __( 'Allow Notes', 'rsvpmaker' ) }
		help={ __( 'Turn on if you want to allow users to enter notes for this agenda item.', 'rsvpmaker' ) }
		checked={ allowNotes }
		onChange={ ( value ) => setAttributes( { allowNotes: value } ) }
	/>
	<ToggleControl
		label={ __( 'Set Start Time', 'rsvpmaker' ) }
		help={ __( 'Turn on if you want to set a specific start time, for example for the second day of an event.', 'rsvpmaker' ) }
		checked={ manualTime }
		onChange={ ( value ) => {setManualTime( value ); if (!value) { setAttributes({ setDateTime: '' }); } } }
	/>

	{ manualTime && (
        <p><input type="datetime-local" value={ setDateTime ? setDateTime : rsvpmaker_rest.date } onChange={ ( e ) => setAttributes( { setDateTime: e.target.value })} /></p>
	)}								 

</InspectorControls>

</div>
		);
}
