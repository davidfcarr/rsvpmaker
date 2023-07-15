import React, {useState} from 'react';
const { __ } = wp.i18n; // Import __() from wp.i18n
//const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const el = wp.element.createElement;
const { DateTimePicker, TimePicker, RadioControl, SelectControl, TextControl, TextareaControl,FormToggle } = wp.components;
const { withSelect, withDispatch } = wp.data;
const { Fragment } = wp.element;
import apiFetch from '@wordpress/api-fetch';
import { inputToDate } from '@wordpress/utils';
import {useRSVPDateMutation} from '../queries'

import { __experimentalGetSettings } from '@wordpress/date';

const settings = __experimentalGetSettings();
//experimental deprecated after WordPress 6.1 , switch to getSettings() : 

const is12HourTime = /a(?!\\)/i.test(
	settings.formats.time
		.toLowerCase() // Test only the lower case a
		.replace( /\\\\/g, '' ) // Replace "//" with empty strings
		.split( '' )
		.reverse()
		.join( '' ) // Reverse the string and test for "a" not followed by a slash
);

function HourOptions () {
	
	var hourarray = [];
	
	for(var i=0; i < 24; i++)
		hourarray.push(i);
	return 	hourarray.map(function(hour) {
		var displayhour = '';
		var valuehour = '';
		var ampm = '';
		if(hour < 10)
			valuehour = displayhour = '0'+hour.toString();
		else
			valuehour = displayhour = hour.toString();
		if(is12HourTime) {
			if(hour > 12) {
				displayhour = (hour - 12).toString();
				ampm = 'pm';
			}
			else if(hour == 12) {
				displayhour = hour.toString();
				ampm = 'pm';
			}
			else if(hour == 0) {
				displayhour = __('midnight','rsvpmaker');
			}
			else {
				displayhour = hour.toString();					
				ampm = 'am';	
			}
		}
		return <option value={valuehour}>{displayhour} {ampm}</option>;
	} );
}

function MinutesOptions() {
	return (
		<Fragment>
		<option value='00'>00</option>
		<option value='15'>15</option>
		<option value='30'>30</option>
		<option value='45'>45</option>
		<option value='01'>01</option>
		<option value='02'>02</option>
		<option value='03'>03</option>
		<option value='04'>04</option>
		<option value='05'>05</option>
		<option value='06'>06</option>
		<option value='07'>07</option>
		<option value='08'>08</option>
		<option value='09'>09</option>
		<option value='10'>10</option>
		<option value='11'>11</option>
		<option value='12'>12</option>
		<option value='13'>13</option>
		<option value='14'>14</option>
		<option value='15'>15</option>
		<option value='16'>16</option>
		<option value='17'>17</option>
		<option value='18'>18</option>
		<option value='19'>19</option>
		<option value='20'>20</option>
		<option value='21'>21</option>
		<option value='22'>22</option>
		<option value='23'>23</option>
		<option value='24'>24</option>
		<option value='25'>25</option>
		<option value='26'>26</option>
		<option value='27'>27</option>
		<option value='28'>28</option>
		<option value='29'>29</option>
		<option value='30'>30</option>
		<option value='31'>31</option>
		<option value='32'>32</option>
		<option value='33'>33</option>
		<option value='34'>34</option>
		<option value='35'>35</option>
		<option value='36'>36</option>
		<option value='37'>37</option>
		<option value='38'>38</option>
		<option value='39'>39</option>
		<option value='40'>40</option>
		<option value='41'>41</option>
		<option value='42'>42</option>
		<option value='43'>43</option>
		<option value='44'>44</option>
		<option value='45'>45</option>
		<option value='46'>46</option>
		<option value='47'>47</option>
		<option value='48'>48</option>
		<option value='49'>49</option>
		<option value='50'>50</option>
		<option value='51'>51</option>
		<option value='52'>52</option>
		<option value='53'>53</option>
		<option value='54'>54</option>
		<option value='55'>55</option>
		<option value='56'>56</option>
		<option value='57'>57</option>
		<option value='58'>58</option>
		<option value='59'>59</option>
		</Fragment>
	);
}

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
			label: props.label,
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
				console.log('onchange setMetaValue',metaValue);
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
			label: props.label,
			selected: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				console.log('onchange to',content);
				props.setMetaValue( content );
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
				props.setMetaValue( content );
			},
		});
	}
);

var MetaEndDateControl = wp.compose.compose(

	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [props.timeKey]: metaValue } } //'_endfirsttime'
				);
			},
			setDisplay: function( value ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [props.statusKey]: value } } //'_firsttime'
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		let metaValue = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[props.timeKey];
		var hour = '';
		var minutes = '';
		var parts;
		if(metaValue == 'Array')
			metaValue = '12:00';
		if((typeof metaValue === 'string') && (metaValue.indexOf(':') > 0))
			parts = metaValue.split(':');
		else
			{	
				parts = ['12','00'];
				if(props.type == 'date') {
					var time = select( 'core/editor' ).getEditedPostAttribute( 'meta' )['_rsvp_date'];
					var p = time.split('/ :/');
					var h = parseInt(p[1])+1;
					if(h < 10)
					hour = '0'+h.toString();
					hour = h.toString();
					parts = [hour,p[2]];
				}
				else {
					hour = select( 'core/editor' ).getEditedPostAttribute( 'meta' )['_sked_hour'];
					minutes = select( 'core/editor' ).getEditedPostAttribute( 'meta' )['_sked_minutes'];
					var h = parseInt(hour)+1;
					if(h < 10)
					hour = '0'+h.toString();
					hour = h.toString();
					parts = [hour,minutes];
				}

				}
		let display = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[props.statusKey];
		return {
			parts: parts,
			display: display,
			//metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ],
		}
	} ) )( function( props ) {
		//inner function to handle change
		function getTimeValues(){
			var hour = document.querySelector( '#endhour option:checked' );
			var minutes = document.querySelector( '#endminutes option:checked' );
			if((typeof hour === 'undefined') || !hour )
				hour = '12';
			if((typeof minutes === 'undefined') || !minutes)
				minutes = '00';
			var newend = hour.value+':'+minutes.value;
			return newend;
		}

		function handleChange () {
			props.setMetaValue(getTimeValues());
		}

		if((typeof props.display != 'undefined') && (props.display != 'set') && (props.display.search('ulti') < 0) )
		return <SelectControl
			label="Time Display"
			value={props.display}
			options={ [
				{ label: 'End Time Not Displayed', value: '' },
				{ label: 'Show End Time', value: 'set' },
				{ label: 'All Day / Time Not Shown', value: 'allday' },
				{ label: '2 Days / Time Not Shown', value: 'multi|2' },
            { label: '3 Days / Time Not Shown', value: 'multi|3' },
            { label: '4 Days / Time Not Shown', value: 'multi|4' },
            { label: '5 Days / Time Not Shown', value: 'multi|5' },
            { label: '6 Days / Time Not Shown', value: 'multi|6' },
            { label: '7 Days / Time Not Shown', value: 'multi|7' },
			] }
			onChange={function( content ) {
				props.setDisplay( content );
			}}
		/> 

		return <div>
		<SelectControl
			label="Time Display"
			value={props.display}
			options={ [
				{ label: 'End Time Not Displayed', value: '' },
				{ label: 'Show End Time', value: 'set' },
				{ label: 'All Day / Time Not Shown', value: 'allday' },
				{ label: '2 Days / Time Not Shown', value: 'multi|2' },
            { label: '3 Days / Time Not Shown', value: 'multi|3' },
            { label: '4 Days / Time Not Shown', value: 'multi|4' },
            { label: '5 Days / Time Not Shown', value: 'multi|5' },
            { label: '6 Days / Time Not Shown', value: 'multi|6' },
            { label: '7 Days / Time Not Shown', value: 'multi|7' },
			] }
			onChange={function( content ) {
				props.setDisplay( content );
			}}
		/> 
		End Time<br /><select id="endhour" value={props.parts[0]} onChange={ handleChange }>
		<HourOptions />
		</select>	
		<select id="endminutes" value={props.parts[1]} onChange={ handleChange } >
		<MinutesOptions />
		</select>	
		</div>
	}
);

var MetaTemplateStartTimeControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setHour: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { '_sked_hour': metaValue } }
				);
			},
			setMinutes: function( value ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { '_sked_minutes': value } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		let hour = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_sked_hour' ];
		let minutes = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_sked_minutes' ];
		return {
			hour: hour,
			minutes: minutes,
		}
	} ) )( function( props ) {
		//inner function to handle change

		return <div>
		Start Time:<br /><select id="starthour" value={props.hour} onChange={ (hour) => {setHour(hour)} }>
		<HourOptions />
		</select>
		<select id="startminutes" value={props.minutes} onChange={ (minutes) => {setMinutes(minutes)} } >
		<MinutesOptions />
		</select>	
		</div>
	}
);

var MetaDateControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				metaValue = metaValue.replace('T',' ');
				apiFetch({path: 'rsvpmaker/v1/clearcache/'+rsvpmaker_ajax.event_id});
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

		const settings = __experimentalGetSettings();
		// To know if the current timezone is a 12 hour time with look for "a" in the time format
		// We also make sure this a is not escaped by a "/"
		const is12HourTime = /a(?!\\)/i.test(
			settings.formats.time
				.toLowerCase() // Test only the lower case a
				.replace( /\\\\/g, '' ) // Replace "//" with empty strings
				.split( '' )
				.reverse()
				.join( '' ) // Reverse the string and test for "a" not followed by a slash
		);	

		return el( DateTimePicker, {
			label: props.label,
			is12Hour: is12HourTime,
			currentDate: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

var MetaTextareaControl = wp.compose.compose(
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
		return el( TextareaControl, {
			label: props.label,
			value: props.metaValue,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

var MetaFormToggle = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				if(metaValue == null)
						{
						metaValue = false; //never submit a null value
						}
					dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
				//todo trigger change in week components for template
			}
		}
	} ),
	withSelect( function( select, props ) {
		let value = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ];//boolvalue,
		if(value == null)
			value = false;
		return {
			metaValue: value,
		}
	} ) )( function( props ) {
		return <div class="rsvpmaker_toggles"><FormToggle checked={props.metaValue} 
		onChange={ function(  ) {
				props.setMetaValue( !props.metaValue );
			} }	
		/>&nbsp;{props.label} </div>
	}
);

export function RSVPMetaToggle(props) {
	if(!props)
		return <p>Reloading ...</p>
	const {eventdata, metaKey, label} = props;
	const {event,meta} = eventdata;
	if(!meta)
		return <p><em>Saving ...</em></p>
	console.log('meta in RSVPMetaToggle',meta);
	const value = (meta.hasOwnProperty(metaKey)) ? meta[metaKey] : false;
    const {mutate:datemutate} = useRSVPDateMutation(event);

	console.log('Toggle value',value);

	return <div class="rsvpmaker_toggles"><FormToggle checked={value} 
	onChange={ function(  ) {
		const change = {'metaKey':metaKey,'metaValue':!value};
		datemutate(change);
			console.log( 'update toggle to', change );
		} }	
	/>&nbsp;{label} </div>
}

var MetaPrices = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue, index ) {
					dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey[index] ]: metaValue } }
				);
				//todo trigger change in week components for template
			}
		}
	} ),
	withSelect( function( select, props ) {
		let value = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ];//boolvalue,
		return {
			metaValue: value,
		}
	} ) )( function( props ) {
		return props.metaValue.forEach( function(value, index) {
		<TextControl value={value} 
		onChange={ function( value ) {
				props.setMetaValue( value, index );
			} }	
		/>
} );				
	}
);

var MetaEndDateTimeControl = wp.compose.compose(

	withDispatch( function( dispatch, props ) {
		return {
			setDisplay: function( value ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { ['_firsttime']: value } } //'_firsttime'
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		let display = select( 'core/editor' ).getEditedPostAttribute( 'meta' )['_firsttime'];
		return {
			display: display,
			//endtime: metaValue,
			//metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ],
		}
	} ) )( function( props ) {
		//inner function to handle change

		if((typeof props.display != 'undefined') && (props.display != 'set') && (props.display.search('ulti') < 0) )
		return <SelectControl
			label="Time Display"
			value={props.display}
			options={ [
				{ label: 'End Time Not Displayed', value: '' },
				{ label: 'Show End Time', value: 'set' },
				{ label: 'All Day / Time Not Shown', value: 'allday' },
			] }
			onChange={function( content ) {
				props.setDisplay( content );
			}}
		/> 

		return <div>
		<SelectControl
			label="Time Display"
			value={props.display}
			options={ [
				{ label: 'End Time Not Displayed', value: '' },
				{ label: 'Show End Time', value: 'set' },
				{ label: 'All Day / Time Not Shown', value: 'allday' },
			] }
			onChange={function( content ) {
				props.setDisplay( content );
			}}
		/>
		<div class="endtime" style={{backgroundColor: 'lightgray', padding: 5}}><h3>End Time</h3>
		<RSVPEndDateControl metaKey='_rsvp_end_date' />
		</div>
		</div>
	}
);

var RSVPEndDateControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				metaValue = metaValue.replace('T',' ');
				apiFetch({path: 'rsvpmaker/v1/clearcache/'+rsvpmaker_ajax.event_id});
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		let current = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ];
		
		if(!current)
		{
			let datestring = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_rsvp_dates' ];
			let timestring = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ];
			if(timestring) {
				let parts = datestring.split(' ');
				current = parts[0] + ' ' + timestring;
			}
			else {
				let startdate = new Date(datestring);
				startdate.setTime(startdate.getTime() + 60 * 1000);
				current = startdate.getHours();
				if(current < 10)
				current = '0'+current;
				current = current+':'+startdate.getMinutes()+':00';
			}
		}		
		return {
			metaValue: current,
		}
	} ) )( function( props ) {

		const settings = __experimentalGetSettings();
		// To know if the current timezone is a 12 hour time with look for "a" in the time format
		// We also make sure this a is not escaped by a "/"
		const is12HourTime = /a(?!\\)/i.test(
			settings.formats.time
				.toLowerCase() // Test only the lower case a
				.replace( /\\\\/g, '' ) // Replace "//" with empty strings
				.split( '' )
				.reverse()
				.join( '' ) // Reverse the string and test for "a" not followed by a slash
		);	

		return el( DateTimePicker, {
			label: props.label,
			is12Hour: is12HourTime,
			currentDate: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

export function RSVPTimestampControl (props) {
	const {metaKey, eventdata} = props;
	const {meta} = eventdata;
	console.log('RSVPTimestampControl metaKey',metaKey);
	console.log('RSVPTimestampControl meta',meta);
	const value = meta[metaKey];
    console.log('RSVPTimestampControl value',value);
	const {mutate:datemutate} = useRSVPDateMutation(eventdata.event);

	function pad(n) {
		if(n < 10)
			return '0'+n;
		else
			return n;
	}

	const sdate = new Date(eventdata.date);
	//subtract from js calculated dates / 1000 to get server timestamp
	const correction = sdate.getTime() - (eventdata.ts_start * 1000);
	const metadate = new Date();
	const set = (value * 1000)+correction;
	metadate.setTime(set);
	const [date,setDate] = useState((value) ? metadate.getFullYear()+'-'+(pad(metadate.getMonth()+1))+'-'+pad(metadate.getDate()): '');
	const [time,setTime] = useState((value) ? pad(metadate.getHours())+':'+pad(metadate.getMinutes()) : '');

	function save() {
		const sdate = new Date(date+' '+time);
		datemutate({'metaKey':metaKey,'metaValue':(sdate.getTime()-correction)/1000});
	}

	return (
		<div>
			<label>{props.label}</label>
			<p><input type="date" value={date} onChange={(e) => {setDate(e.target.value)}} /> <input type="time" value={time} onChange={(e) => {setTime(e.target.value)}} /> {date && time && <button onClick={save}>Set</button>}</p>
			{((date && !time) || (time && !date)) && <p><em>Enter both date and time</em></p>}
		</div>
	);
} 

const MetaTimestampControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				console.log('metats dispatch',metaValue);
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		console.log('withselect props',props);
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		function pad(n) {
			if(n < 10)
				return '0'+n;
			else
				return n;
		}

		const sdate = new Date(rsvpmaker_ajax.eventdata.date);
		//subtract from js calculated dates / 1000 to get server timestamp
		console.log('meta ts props',props);
		const correction = sdate.getTime() - (rsvpmaker_ajax.eventdata.ts_start * 1000);
		const metadate = new Date();
		if(props.metaValue)
			metadate.setTime((props.metaValue * 1000)+correction);
		const [date,setDate] = useState((props.metaValue) ? metadate.getFullYear()+'-'+(pad(metadate.getMonth()+1))+'-'+pad(metadate.getDate()): '');
		const [time,setTime] = useState((props.metaValue) ? pad(metadate.getHours())+':'+pad(metadate.getMinutes()) : '');
		const [message,setMessage] = useState('');
	
		function save() {
			const sdate = new Date(date+' '+time);
			props.setMetaValue((sdate.getTime()-correction)/1000);
			setMessage('New date will be recorded when you save/publish/update');
		}

		return (
			<div>
				<label>{props.label}</label>
				<p><input type="date" value={date} onChange={(e) => {setDate(e.target.value)}} /> <input type="time" value={time} onChange={(e) => {setTime(e.target.value)}} /> {date && time && <button onClick={save}>Set</button>}</p>
				{((date && !time) || (time && !date)) && <p><em>Enter both date and time</em></p>}
				{message}
			</div>
		);
	}
);

var MetaPrices = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue, index ) {
					dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey[index] ]: metaValue } }
				);
				//todo trigger change in week components for template
			}
		}
	} ),
	withSelect( function( select, props ) {
		let value = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ];//boolvalue,
		return {
			metaValue: value,
		}
	} ) )( function( props ) {
		return props.metaValue.forEach( function(value, index) {
		<TextControl value={value} 
		onChange={ function( value ) {
				props.setMetaValue( value, index );
			} }	
		/>
} );				
	}
);

export {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl, MetaFormToggle, MetaTextareaControl, MetaEndDateTimeControl, MetaTimestampControl};
