const { __ } = wp.i18n; // Import __() from wp.i18n
//const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const el = wp.element.createElement;
const { DateTimePicker, RadioControl, SelectControl, TextControl } = wp.components;
const { withSelect, withDispatch } = wp.data;

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
				console.log('dispatch value');
				console.log(metaValue);
				dispatch( 'core/editor' ).editPost(
					{ meta: { '_endfirsttime': metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		let metaValue = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_endfirsttime' ];
		//console.log(props);
		//console.log(metaValue);
		if((typeof metaValue === 'string') && (metaValue.indexOf(':') > 0))
			var parts = metaValue.split(':');
		else
			{
				var parts = ['12','00'];
			}
		return {
			parts: parts,
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
			console.log('newend '+newend);
			return newend;
		}

		function handleChange () {
			props.setMetaValue(getTimeValues());
		}

		return <div>End Time: <select id="endhour" value={props.parts[0]} onChange={ handleChange }>
		<option value='00'>12 midnight</option>
		<option value='01'>1 am / 01:</option>
		<option value='02'>2 am / 02:</option>
		<option value='03'>3 am / 03:</option>
		<option value='04'>4 am / 04:</option>
		<option value='05'>5 am / 05:</option>
		<option value='06'>6 am / 06:</option>
		<option value='07'>7 am / 07:</option>
		<option value='08'>8 am / 08:</option>
		<option value='09'>9 am / 09:</option>
		<option value='10'>10 am / 10:</option>
		<option value='11'>11 am / 11:</option>
		<option value='12'>12 pm / 12:</option>
		<option value='13'>1 pm / 13:</option>
		<option value='14'>2 pm / 14:</option>
		<option value='15'>3 pm / 15:</option>
		<option value='16'>4 pm / 16:</option>
		<option value='17'>5 pm / 17:</option>
		<option value='18'>6 pm / 18:</option>
		<option value='19'>7 pm / 19:</option>
		<option value='20'>8 pm / 20:</option>
		<option value='21'>9 pm / 21:</option>
		<option value='22'>10 pm / 22:</option>
		<option value='23'>11 pm / 23:</option>
		</select>	
		<select id="endminutes" value={props.parts[1]} onChange={ handleChange } >
		<option value='00'>00</option>
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
		</select>	
		</div>
	}
);

//  { ( minutes ) => { props.setMetaValue( document.querySelector( '#endhour option:checked' ).value+':'+{minutes} ) } } >

var MetaDateControl = wp.compose.compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				metaValue = metaValue.replace('T',' ');
				fetch('/wp-json/rsvpmaker/v1/clearcache/'+rsvpmaker_ajax.event_id);
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
		return el( DateTimePicker, {
			label: props.label,
			is12Hour: true,
			currentDate: props.metaValue,
			options: props.options,
			onChange: function( content ) {
				props.setMetaValue( content );
			},
		});
	}
);

function recordChange(metaKey, metaValue) {
	console.log(metaKey + ': ', metaValue);
}

export {MetaEndDateControl, MetaDateControl, MetaTextControl, MetaSelectControl, MetaRadioControl};