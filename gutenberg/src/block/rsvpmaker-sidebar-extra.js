/**
 * BLOCK: blocknewrsvp
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
//import './editor.scss';
//import './style.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
//const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const el = wp.element.createElement;
const {Fragment} = wp.element;
const { registerPlugin } = wp.plugins;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
//const { withState } = wp.compose;
const { DateTimePicker, RadioControl, SelectControl, TextControl } = wp.components;
const { withSelect, withDispatch } = wp.data;
//const {RSVPMakerDateTimePicker, RSVPMakerOn} = './rsvpmaker-sidebar.js';

var MetaTextControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		return el( TextControl, {
			label: props.title,
			value: props.metaValue,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

var MetaRadioControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		return el( RadioControl, {
			label: props.title,
			selected: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content ), recordChange(props.metaKey, content);
			},
		});
	}
);

var MetaSelectControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		return el( SelectControl, {
			label: props.label,
			value: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content ), recordChange(props.metaKey, content);
			},
		});
	}
);

var MetaDateControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				metaValue = metaValue.replace('T',' ');
				fetch('/wp-json/rsvpmaker/v1/clearcache/'+rsvpmaker_ajax.event_id);
				console.log(metaValue);
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		console.log(props);
		return el( DateTimePicker, {
			label: props.label,
			currentDate: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

/*
var EndTimeControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setEndTime: function( endtime ) {
				dispatch( 'rsvpevent' ).setEndTime(endtime);
			}
		}
	} ),
	withSelect( function( select, props ) {
		return {
			endtime: select( 'rsvpevent' ).getEndTime(),
		}
	} ) )( function( {endtime, setEndTime} ) {

		function handleEndChange(newobj) {
			endtime.end = newobj.time;
			console.log(newobj.time);
			console.log(endtime);
			setEndTime(endtime);
		}
		endtime =wp.data.select( 'rsvpevent' ).getEndTime();
		return <TextControl title="End Time" value={endtime.end} onChange={ ( time ) => handleEndChange({time})} /> //handleEndChange({time})

		//var parts = endtime.end.split(':');
		return <p><input type="text" id="endhour" value={endtime.end} onChange={ ( newhour ) => handleEndChange('hour',newhour) } /> {endtime.end} / {endtime.display}</p>

		return el( RadioControl, {
			label: props.title,
			selected: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content ), recordChange(props.metaKey, content);
			},
		});
	}
);


function getEndTime(post_id) {
let url = '/wp-json/rsvpmaker/v1/endtime/'+post_id;
console.log('fetch from '+url);
fetch(url)
  .then(function(data) {
	console.log(data);
	return data;
    // Here you get the data to modify as you please
    })
  .catch(function(error) {
  	console.log(error);  // If there is any error you will catch them here
  });
}

//wp.data.select("core/editor").getCurrentPostId()

const EndTimePicker = withState( {
	endtime: {},
} )( ( { endtime, setState } ) => {

	endtime = getEndTime(rsvpmaker_ajax.event_id);

	console.log(endtime);

	return (
		<TextControl title="End Time: " value={endttime.end} onChange={ ( newtime ) => setState({newtime}), recordChange('end',{endtime}) } />
	);
} );



/*
paramaters removed from datetime picker
settings object removed
		    locale={ settings.l10n.locale }
*/

function recordChange(metaKey, metaValue) {
	console.log(metaKey + ': ', metaValue);
}

function related_link() {
	if(rsvpmaker_ajax.special)
		{
		return <div class="rsvp_related_links"><p></p></div>;
		}
	if(rsvpmaker_json.projected_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.projected_url}>{rsvpmaker_json.projected_label}</a></p></div>;	
		}
	if(rsvpmaker_json.template_url)
		{
		return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p><p><a href={rsvpmaker_json.template_url}>{rsvpmaker_json.template_label}</a></p></div>;	
		}
	return <div class="rsvp_related_links"><p><a href={rsvpmaker_ajax.rsvpmaker_details}>RSVP / Event Options</a></p></div>;	
	}

const PluginRSVPMaker = () => {
    return(
		<Fragment>
		<PluginSidebarMoreMenuItem target="plugin-rsvpmaker-extra" icon="calendar-alt">RSVPMaker</PluginSidebarMoreMenuItem>
        <PluginSidebar
            name='plugin-rsvpmaker-extra'
            title='RSVPMaker'
            icon="calendar-alt"
        >

<p>For additional options, including event end time, multiple dates, and event pricing see: {related_link()}</p>
<div>{(rsvpmaker_ajax._rsvp_count == '1') && <MetaDateControl metaKey='_rsvp_dates' />}</div>
<div>{(rsvpmaker_ajax._rsvp_count != '1') && <p>{__('Event has multiple dates set. Edit on RSVP Event / Options screen.')}</p>}</div>
<MetaSelectControl
		label="Collect RSVPs"
		metaKey="_rsvp_on"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Add to Google/Outlook Calendar Icons"
		metaKey="_calendar_icons"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Add Timezone to Date"
		metaKey="_add_timezone"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Timezone Conversion Button"
		metaKey="_convert_timezone"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>
			<MetaTextControl title="Send notifications to:" metaKey="_rsvp_to" />
		<MetaSelectControl
		label="Send Confirmation Email"
		metaKey="_rsvp_rsvpmaker_send_confirmation_email"
		options={ [
			{ label: 'Yes', value: 'on' },//past implementation used both 'on' and '1'
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Include Event Content with Confirmation"
		metaKey="_rsvp_confirmation_include_event"
		options={ [
			{ label: 'Yes', value: 'on' },//past implementation used both 'on' and '1'
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Login required to RSVP"
		metaKey="_rsvp_login_required"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Captcha security challenge"
		metaKey="_rsvp_captcha"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show Yes/No Options on Registration Form"
		metaKey="_rsvp_yesno"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No (Yes is assumed)', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Show RSVP Count"
		metaKey="_rsvp_count"
		options={ [
			{ label: 'Yes', value: '1' },
			{ label: 'No', value: '0' },
		] }
	/>

<MetaSelectControl
		label="Display attendee names / RSVP note field"
		metaKey="_rsvp_show_attendees"
		options={ [
			{ label: 'No', value: '0' },
			{ label: 'Yes', value: '1' },
			{ label: 'Only for Logged In Users', value: '2' },
		] }
	/>

<MetaTextControl
		title="Maximum number of participants (0 for no limit)"
		metaKey="_rsvp_max"
	/>
<div>For additional options, including event end time, multiple dates, and event pricing see: {related_link()}</div>
        </PluginSidebar>
		</Fragment>
    )
}
registerPlugin( 'plugin-rsvpmaker', { render: PluginRSVPMaker } );
