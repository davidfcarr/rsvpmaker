/**
 * BLOCK: rsvpmaker-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './style.scss';
import './editor.scss';
import './rsvpmaker-sidebar.js';		import './rsvpemail-sidebar.js';		
import './limited_time.js';		
import apiFetch from '@wordpress/api-fetch';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const { SelectControl, TextControl } = wp.components;

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */

registerBlockType( 'rsvpmaker/event', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'RSVPMaker Embed Event' ), // Block title.
	icon: 'clock', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'RSVPMaker' ),
		__( 'Event' ),
		__( 'Calendar' ),
	],
       attributes: {
            post_id: {
            type: 'string',
            default: '',
            },
            one_hideauthor: {
                type: 'boolean',
                default: true,
            },
            type: {
                type: 'string',
                default: '',
            },
            one_format: {
                type: 'string',
				default: '',
            },
            hide_past: {
                type: 'string',
                default: '',
            },
        },
	edit: function( props ) {
	const { attributes: { post_id, type, one_hideauthor, one_format, hide_past }, setAttributes, isSelected } = props;
		
	function setPostID( event ) {
		const selected = event.target.querySelector( '#post_id option:checked' );
		setAttributes( { post_id: selected.value } );		
		event.preventDefault();
	}	
	function setEventType( event ) {
		const selected = event.target.querySelector( '#type option:checked' );
		setAttributes( { type: selected.value } );
		event.preventDefault();
	}
	function setOneFormat( event ) {
		const selected = event.target.querySelector( '#one_format option:checked' );
		setAttributes( { one_format: selected.value } );
		event.preventDefault();
	}	
	function setHideAuthor( event ) {
		const selected = event.target.querySelector( '#one_hideauthor option:checked' );
		setAttributes( { one_hideauthor: selected.value } );
		event.preventDefault();
	}	
	function setHidePast( event ) {
		const selected = event.target.querySelector( '#hide_past option:checked' );
		setAttributes( { hide_past: selected.value } );
		event.preventDefault();
	}
	
		
	function showFormPrompt () {
		return <p><strong>Click here to set options.</strong></p>
	}

	function showForm() {
			return (
				<form onSubmit={ setPostID, setOneFormat, setHideAuthor, setEventType, setHidePast } >
					<p><label>Select Post</label> <select id="post_id"  value={ post_id } onChange={ setPostID }>
						{upcoming.map(function(opt, i){
                    return <option value={ opt.value }>{opt.text}</option>;
                })}
					</select></p>
					<p><label>Format</label> <select id="one_format"  value={ one_format } onChange={ setOneFormat }>
						<option value="">Event with Form</option>
						<option value="button">Event with Button</option>
						<option value="form">Form Only</option>
						<option value="button_only">Button Only</option>
						<option value="compact">Compact (Headline/Date/Button)</option>
						<option value="embed_dateblock">Dates Only</option>
					</select></p>
					<p id="rsvpcontrol-hide-after"><label>Hide After</label> <select id="hide_past"  value={ hide_past } onChange={ setHidePast }>
						<option value="">Not Set</option>
						<option value="1">1 hour</option>
						<option value="2">2 hours</option>
						<option value="3">3 hours</option>
						<option value="4">4 hours</option>
						<option value="5">5 hours</option>
						<option value="6">6 hours</option>
						<option value="7">7 hours</option>
						<option value="8">8 hours</option>
						<option value="12">12 hours</option>
						<option value="18">18 hours</option>
						<option value="24">24 hours</option>
						<option value="48">2 days</option>
						<option value="72">3 days</option>
					</select></p>
					<p id="rsvpcontrol-event-type"><label>Event Type</label> <select id="type" value={ type } onChange={ setEventType }>
					{rsvpmaker_types.map(function(opt, i){
                    return <option value={ opt.value }>{opt.text}</option>;
                })}</select></p>				
					<p><label>Show Author</label> <select id="one_hideauthor"  value={ one_hideauthor } onChange={ setHideAuthor }>
						<option value="1">No</option>
						<option value="0">Yes</option>
					</select></p>
				</form>
			);
		}

		return (
			<div className={ props.className }>
				<p class="dashicons-before dashicons-clock"><strong>RSVPMaker</strong>: Embed a single event.
				</p>
			{ isSelected && ( showForm() ) }
			{ !isSelected && ( showFormPrompt() ) }
			</div>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	save: function() {
		// server render
		return null;
	},
} );

//[rsvpmaker_one post_id="0" hideauthor="1" showbutton="0" one_format="compact"]
				  
registerBlockType( 'rsvpmaker/upcoming', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'RSVPMaker Upcoming Events' ), // Block title.
	icon: 'calendar-alt', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'RSVPMaker' ),
		__( 'Events' ),
		__( 'Calendar' ),
	],
       attributes: {
            calendar: {
                type: 'int',
                default: 0,
            },
            nav: {
                type: 'string',
                default: 'bottom',
            },
            days: {
                type: 'int',
				default: 180,
            },
            posts_per_page: {
                type: 'int',
				default: 10,
            },
            type: {
                type: 'string',
                default: '',
            },
            no_events: {
                type: 'string',
                default: 'No events listed',
            },
            hideauthor: {
                type: 'boolean',
                default: true,
            },
        },
	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	edit: function( props ) {
		// Creates a <p class='wp-block-cgb-block-toast-block'></p>.
	const { attributes: { calendar, days, posts_per_page, hideauthor, no_events, nav, type }, setAttributes, isSelected } = props;
/*
apiFetch( { path: '/wp-json/rsvpmaker/v1/types' } ).then( rsvpmaker_types => {
	console.log(rsvpmaker_types);
} );
*/		
	function setCalendarDisplay( event ) {
		const selected = event.target.querySelector( '#calendar option:checked' );
		setAttributes( { calendar: selected.value } );
		event.preventDefault();
	}	
	function setNav( event ) {
		const selected = event.target.querySelector( '#nav option:checked' );
		setAttributes( { nav: selected.value } );
		event.preventDefault();
	}	
	function setPostsPerPage( event ) {
		const selected = event.target.querySelector( '#posts_per_page option:checked' );
		setAttributes( { posts_per_page: selected.value } );
		event.preventDefault();
	}	
	function setDays( event ) {
		const selected = event.target.querySelector( '#days option:checked' );
		setAttributes( { days: selected.value } );
		event.preventDefault();
	}	
	function setNoEvents( event ) {
		var no_events = document.getElementById('no_events').value;
		setAttributes( { agenda_note: no_events } );
		event.preventDefault();
	}
	function setEventType( event ) {
		const selected = event.target.querySelector( '#type option:checked' );
		setAttributes( { type: selected.value } );
		event.preventDefault();
	}	

	function showFormPrompt () {
		return <p><strong>Click here to set options.</strong></p>
	}
		
	function showForm() {
			return (
				<form onSubmit={ setCalendarDisplay, setNav, setNoEvents, setEventType } >
					<p><label>Display Calendar</label> <select id="calendar"  value={ calendar } onChange={ setCalendarDisplay }>
						<option value="1">Yes - Calendar plus events listing</option>
						<option value="0">No - Events listing only</option>
						<option value="2">Calendar Only</option>
					</select></p>
					<p><label>Events Per Page</label> <select id="posts_per_page"  value={ posts_per_page } onChange={ setPostsPerPage }>
						<option value="5">5</option>
						<option value="10">10</option>
						<option value="15">15</option>
						<option value="20">15</option>
						<option value="30">15</option>
						<option value="-1">No limit</option>
					</select></p>
					<p><label>Date Range</label> <select id="days" value={ days } onChange={ setDays }>
						<option value="30">30 days</option>
						<option value="60">60 days</option>
						<option value="90">90 days</option>
						<option value="180">180 days</option>
						<option valu="365">1 Year</option>
					</select></p>
				<p id="rsvpcontrol-event-type"><label>Event Type</label> <select id="type" value={ type } onChange={ setEventType }>
				<option value=""></option>
					{rsvpmaker_types.map(function(opt, i){
                    return <option value={ opt.value }>{opt.text}</option>;
                })}</select></p>				
					<p><label>Calendar Navigation</label> <select id="nav"  value={ nav } onChange={ setNav }>
						<option value="top">Top</option>
						<option value="bottom">Bottom</option>
						<option value="both">Both</option>
					</select></p>
					<p>Text to show for no events listed<br />
				<input type="text" id="no_events" onChange={setNoEvents} defaultValue={no_events} /> 
				</p>
				
				</form>
			);
		}

		return (
			<div className={ props.className }>
				<p  class="dashicons-before dashicons-calendar-alt"><strong>RSVPMaker</strong>: Add an Events Listing and/or Calendar Display
				</p>
			{ isSelected && ( showForm() ) }
			{ !isSelected && ( showFormPrompt() ) }
			</div>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	save: function( props ) {
		return null;
	},
} );

registerBlockType( 'rsvpmaker/stripecharge', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Stripe Charge (RSVPMaker)' ), // Block title.
	icon: 'products', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'RSVPMaker' ),
		__( 'Payment' ),
		__( 'Charge' ),
	],
       attributes: {
            description: {
            type: 'string',
            default: '',
            },
            showdescription: {
            type: 'string',
            default: 'no',
            },
            amount: {
            type: 'string',
            default: '',
            },
            paymentType: {
            type: 'string',
            default: 'once',
            },
            amount: {
            type: 'string',
            default: '',
            },
            january: {
            type: 'string',
            default: '',
            },
            february: {
            type: 'string',
            default: '',
            },
            march: {
            type: 'string',
            default: '',
            },
            april: {
            type: 'string',
            default: '',
            },
            may: {
            type: 'string',
            default: '',
            },
            june: {
            type: 'string',
            default: '',
            },
            july: {
            type: 'string',
            default: '',
            },
            august: {
            type: 'string',
            default: '',
            },
            september: {
            type: 'string',
            default: '',
            },
            october: {
            type: 'string',
            default: '',
            },
            november: {
            type: 'string',
            default: '',
            },
            december: {
            type: 'string',
            default: '',
            },
        },
	edit: function( props ) {
		// Creates a <p class='wp-block-cgb-block-toast-block'></p>.
	const { attributes: { description, showdescription, amount, paymentType, january, february, march, april, may, june, july, august, september, october, november, december }, setAttributes, isSelected } = props;
		var show = (paymentType.toString() == 'schedule') ? true : false;
		//alert(show);
		
		if(!isSelected)
			return (
			<div className={ props.className }>
				<p class="dashicons-before dashicons-products"><strong>Payment Button</strong>: Embed in any post or page (not meant to be included in events). Clicke to set price and options.
				</p>
				</div>
			);
		
		return (
			<div className={ props.className }>
				<p class="dashicons-before dashicons-products"><strong>Payment Button</strong>: Embed in any post or page (not meant to be included in events).
				</p>
	<TextControl
        label={ __( 'Description', 'rsvpmaker' ) }
        value={ description }
        onChange={ ( description ) => setAttributes( { description } ) }
    />	
<div>		<SelectControl
			label={ __( 'Show Amount/Description Under Button', 'rsvpmaker' ) }
			value={ showdescription }
			onChange={ ( showdescription ) => setAttributes( { showdescription } ) }
			options={ [
				{ value: 'yes', label: __( 'Yes', 'rsvpmaker' ) },
				{ value: 'no', label: __( 'No', 'rsvpmaker' ) },
			] }
		/>

		<SelectControl
			label={ __( 'Payment Type', 'rsvpmaker' ) }
			value={ paymentType }
			onChange={ ( paymentType ) => setAttributes( { paymentType } ) }
			options={ [
				{ value: 'one-time', label: __( 'One time, fixed fee', 'rsvpmaker' ) },
				{ value: 'schedule', label: __( 'Dues schedule', 'rsvpmaker' ) },
				{ value: 'subscription:1 year', label: __( 'Subscription, yearly', 'rsvpmaker' ) },
				{ value: 'subscription:6 months', label: __( 'Subscription, every 6 months', 'rsvpmaker' ) },
				{ value: 'subscription:monthly', label: __( 'Subscription, monthly', 'rsvpmaker' ) },
				{ value: 'donation', label: __( 'Donation', 'rsvpmaker' ) },
			] }
		/>
				</div>
{
!show &&	<TextControl
        label={ __( 'Fee', 'rsvpmaker' ) }
        value={ amount }
		placeholder="$0.00"
        onChange={ ( amount ) => setAttributes( { amount } ) }
    />			
}
			{
show &&	
<div>    <TextControl
        label={ __( 'January', 'rsvpmaker' ) }
        value={ january }
        onChange={ ( january ) => setAttributes( { january } ) }
    />
    <TextControl
        label={ __( 'February', 'rsvpmaker' ) }
        value={ february }
        onChange={ ( february ) => setAttributes( { february } ) }
    />
    <TextControl
        label={ __( 'March', 'rsvpmaker' ) }
        value={ march }
        onChange={ ( march ) => setAttributes( { march } ) }
    />
    <TextControl
        label={ __( 'April', 'rsvpmaker' ) }
        value={ april }
        onChange={ ( april ) => setAttributes( { april } ) }
    />
    <TextControl
        label={ __( 'May', 'rsvpmaker' ) }
        value={ may }
        onChange={ ( may ) => setAttributes( { may } ) }
    />
    <TextControl
        label={ __( 'June', 'rsvpmaker' ) }
        value={ june }
        onChange={ ( june ) => setAttributes( { june } ) }
    />
    <TextControl
        label={ __( 'July', 'rsvpmaker' ) }
        value={ july }
        onChange={ ( july ) => setAttributes( { july } ) }
    />
    <TextControl
        label={ __( 'August', 'rsvpmaker' ) }
        value={ august }
        onChange={ ( august ) => setAttributes( { august } ) }
    />
    <TextControl
        label={ __( 'September', 'rsvpmaker' ) }
        value={ september }
        onChange={ ( september ) => setAttributes( { september } ) }
    />
    <TextControl
        label={ __( 'October', 'rsvpmaker' ) }
        value={ october }
        onChange={ ( october ) => setAttributes( { october } ) }
    />
    <TextControl
        label={ __( 'November', 'rsvpmaker' ) }
        value={ november }
        onChange={ ( november ) => setAttributes( { november } ) }
    />
    <TextControl
        label={ __( 'December', 'rsvpmaker' ) }
        value={ december }
        onChange={ ( december ) => setAttributes( { december } ) }
    />
</div>
 }
			</div>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	save: function() {
		// server render
		return null;
	},
} );

