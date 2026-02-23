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
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';
const { TextControl, SelectControl } = wp.components;
import { useFutureDateOptions } from '../queries';

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
    //const [rsvpupcoming, setRsvpupcoming] = useState([{label: __('Choose event'),value: ''},{label: __('Next event'),value: 'next'},{label: __('Next event - RSVP on'),value: 'nextrsvp'}]);
	const { attributes: { event_id, countdown_id, expiration_display, expiration_message }, setAttributes, className, isSelected } = props;
    const blockProps = useBlockProps({className});
    const rsvpupcoming = useFutureDateOptions();
	let	current_id = wp.data.select("core/editor").getCurrentPostId();
    const postType = useSelect((select) =>
        select('core/editor').getCurrentPostType(),
        []
    );
    let isEvent = ((postType == 'rsvpmaker'));
	setAttributes( { countdown_id : 'countdown-'+current_id } );
    /*
    useEffect(() => {
        apiFetch( {path: 'rsvpmaker/v1/future'} ).then( events => {
            const upcoming = [...rsvpupcoming];
            if(Array.isArray(events)) {
                events.map( function(event) { if(event.ID) { var title = (event.neatdate) ? event.post_title+' - '+event.neatdate : event.post_title; upcoming.push({value: event.ID, label: title }) } } );
            }
            else {
                var eventsarray = Object.values(events);
                eventsarray.map( function(event) { if(event.ID) { var title = (event.date) ? event.post_title+' - '+event.date : event.post_title; upcoming.push({value: event.ID, label: title }) } } );
                }
            setRsvpupcoming(upcoming);
        }).catch(err => {
            console.log(err);
        });
    },[])
    */
		return (
			<div {...blockProps}>
				<p class="dashicons-before dashicons-clock"><strong>RSVPMaker</strong>: Embed a countdown clock.
				</p>
                <InspectorControls key="countdowninspector">
 {(!isEvent && <SelectControl
        label={__("Select Event",'rsvpmaker')}
        value={ event_id }
        options={ rsvpupcoming }
        onChange={ ( event_id ) => { setAttributes( { event_id: event_id} ) } }
    />)}
<SelectControl
        label={__("Show When Time Expires",'rsvpmaker')}
        value={ expiration_display }
        options={ [{label: __('Stopped Clock 00:00:00'),value: 'stoppedclock'},{label: __('Stopped Clock Plus Message'),value: 'clockmessage'},{label: __('Message Only'),value: 'message'},{label: __('Nothing, Clear Content'),value: 'nothing'}] }
        onChange={ ( expiration_display ) => { setAttributes( { expiration_display: expiration_display} ) } }
    />
<TextControl label={__('Expiration Message','rsvpmaker')} value={expiration_message} onChange={ ( expiration_message ) => { setAttributes( { expiration_message: expiration_message} ) } } />                   
                </InspectorControls>
			</div>
		);
    }
