/* JavaScript Document */
jQuery( document ).ready(
	function( $ ) {
		$.ajaxSetup(
			{
				headers: {
					'X-WP-Nonce': rsvpmaker_rest.nonce,
				}
			}
		);

		$( '#quick_start_date' ).change(
			function () {
				var startdate = $( this ).val();
				var dt        = new Date( startdate );
				var sqldate   = rsvpsql_date( dt );
				$( '#weekday' ).html( rsvpmaker_weekday( dt ) );
				$( '.free-text-date' ).each(
					function() {
						$( this ).val( startdate );
					}
				);
				$( '.sql-date' ).each(
					function() {
						$( this ).val( sqldate );
					}
				);
			}
		);

		$( '.free-text-date' ).change(
			function() {
				var datetext = $( this ).val();
				datetext     = datetext.replace( / (\d{1,2}) ([aApP])/,' $1:00 $2' );
				var error_id = $( this ).attr( 'id' ).replace( 'free-text-date','date_error' );
				var t        = Date.parse( datetext );
				if (Number.isNaN( t )) {
					$( '#' + error_id ).html( '<span style="color:red">Error:</span> free text date is not valid' );
				} else {
					$( '#' + error_id ).html( '' );
					var dt         = new Date( t );
					var target     = $( this ).attr( 'id' ).replace( 'free-text','sql' );
					var weekday_id = target.replace( 'sql-date','date-weekday' );
					$( '#' + target ).val( rsvpsql_date( dt ) );
					$( '#' + weekday_id ).html( rsvpmaker_weekday( dt ) );
				}
			}
		);

		$( '.quickdate, .quicktime' ).change(
			function () {
				var id    = $( this ).attr( 'id' );
				var parts = id.split( '-' );
				var date  = $( '#quickdate-' + parts[1] ).val();
				var time  = $( '#quicktime-' + parts[1] ).val();
				if ((date == '') || (time == '')) {
					$( '#quicktime-text-' + parts[1] ).html( 'Date format error' );
				} else {
					var t = Date.parse( date + ' ' + time );
					if (Number.isNaN( t )) {
						$( '#quicktime-text-' + parts[1] ).html( 'Date format error' );
					} else {
						var dt           = new Date( t );
						const options    = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
						var localestring = dt.toLocaleDateString( undefined, options ) + ' ' + dt.toLocaleTimeString().replace( ':00 ',' ' );
						$( '#quicktime-text-' + parts[1] ).html( localestring );
					}
				}
			}
		);

		$( 'select.rsvpsort' ).change(
			function() {
				var sort   = $( this ).val();
				var parts  = window.location.href.split( '?' );
				var url    = parts[0] + '?post_type=rsvpmaker&rsvpsort=' + sort;
				var top    = $( '#bulk-action-selector-top' ).val();
				var bottom = $( '#bulk-action-selector-bottom' ).val();
				if ((top == '-1') && (bottom == '-1')) {
					window.location.replace( url );
				}
				$( '.rsvpsortwrap' ).html( '<a style="font-size: large;" href="' + url + '">View: ' + sort + '</a>' );
			}
		);

		$( document ).on(
			'click',
			'.rsvpmaker-nav-tab-wrapper a',
			function() {
				$( '.rsvpmaker-nav-tab' ).removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' );
				$( 'section.rsvpmaker' ).hide();
				$( 'section.rsvpmaker' ).eq( $( this ).index() ).show();
				return false;
			}
		);

		if($('#activetab')) {
			var activetab = '#'+$('#activetab').val();
			$( 'section.rsvpmaker' ).hide();
			$( 'section' + activetab ).show();
		}
		else {
			var active = $( '.nav-tab-active' );
			if (active) {
			var activetab = active.attr('href');
			$( 'section.rsvpmaker' ).hide();
			$( 'section' + activetab ).show();
			}
		}

		$(
			function() {
				var dialog;

				function setRsvpForm() {

					var extras = '';
					var label  = '';
					var radiosplit;
					var radiovalues;
					var guestfield        = '';
					var checked           = '';
					var field             = '';
					var name_email_hidden = $( '#name_email_hidden' ).val();

					if (name_email_hidden == 'hidden') {
						  var newform = '[rsvpfield hidden="first" ][rsvpfield hidden="last"][rsvpfield hidden="email"]';
					} else if (name_email_hidden == 'email_first') {
						  var newform = '<p><label>Email:</label> [rsvpfield textfield="email" required="1"]</p>' + "\n" + '<p><label>First Name:</label> [rsvpfield textfield="first" required="1"]</p>' + "\n" +
						'<p><label>Last Name:</label> [rsvpfield textfield="last" required="1"]</p>' + "\n";
					} else {
						var newform = '<p><label>First Name:</label> [rsvpfield textfield="first" required="1"]</p>' + "\n" +
						'<p><label>Last Name:</label> [rsvpfield textfield="last" required="1"]</p>' + "\n" +
						'<p><label>Email:</label> [rsvpfield textfield="email" required="1"]</p>' + "\n";
					}

					guestfield = ' guestfield="' + $( '#guest_phoneandtype' ).val() + '" ';

					var note        = '<p>Note:<br />' + "\n" + '<textarea name="note" cols="60" rows="2" id="note">[rsvpnote]</textarea></p>';
					var extrafields = $( "#extrafields" ).val();

					for (i = 1; i <= extrafields; i++) {
							  var extra = $( "#extra" + i ).val().trim();
							  var type  = $( "#type" + i + ' option:selected' ).text();
						if (type == 'text') {
							type = 'textfield';
						}
						if (extra != '') {
							guestfield = ' guestfield="' + $( '#guest' + i ).val() + '" ';
							if ( $( '#private' + i ).is( ':checked' ) ) {
									private = ' private="1" ';
							} else {
								private = ' private="0" ';
							}
							if (extra.indexOf( ':' ) != -1 ) {
									  radiosplit  = extra.split( ':' );
									  label       = radiosplit[0];
									  radiovalues = radiosplit[1].split( ',' );
									  checked     = radiovalues[0].trim();
									  field       = label.toLowerCase().replace( /[^a-z0-9]+/g,'_' );
									  extras      = extras.concat( '<p class="' + field + '"><label>' + label + '</label> [rsvpfield ' + type + '="' + field + '" options="' + radiosplit[1] + '" checked="' + checked + '" ' + guestfield + ' ' + private + ']</p>' + "\n" );
							} else {
								  field = extra.toLowerCase().replace( /[^a-z0-9]+/g,'_' );
								extras  = extras.concat( '<p class="' + field + '"><label>' + extra + '</label> [rsvpfield textfield="' + field + '" ' + guestfield + ' ' + private + ']</p>' + "\n" );
							}
						}
					}

					if (extras != '') {
						newform = newform.concat( extras );
					}
					var maxguests = parseInt( $( '#maxguests' ).val() );
					if ( $( '#guests' ).is( ':checked' ) && maxguests) {
						newform = newform.concat( '[rsvpguests max_party="' + (maxguests + 1) + '"]' + "\n" );
					} else if ( $( '#guests' ).is( ':checked' ) ) {
						newform = newform.concat( '[rsvpguests]' + "\n" );
					}
					if ( $( '#note' ).is( ':checked' ) ) {
						newform = newform.concat( note );
					}
					if ( $( '#emailcheckbox' ).is( ':checked' ) ) {
						newform = newform.concat( '<p>[rsvpfield checkbox="email_list_ok" value="yes" checked="1"] Add me to your email list</p>' );
					}

					$( "#rsvpform" ).val( newform );
					dialog.dialog( "close" );
					return;
				}

				dialog = $( "#rsvp-dialog-form" ).dialog(
					{
						autoOpen: false,
						height: 600,
						width: 800,
						modal: true,
						buttons: {
							"Create form": setRsvpForm,
							Cancel: function() {
								dialog.dialog( "close" );
							}
						},
					}
				);

				form = dialog.find( "form" ).on(
					"submit",
					function( event ) {
						event.preventDefault();
						setRsvpForm();
					}
				);

				$( "#create-form" ).button().on(
					"click",
					function(event) {
						event.preventDefault();
						dialog.dialog( "open" );
					}
				);
			}
		);

		$( '#enlarge' ).click(
			function() {
				jQuery( '#rsvpform' ).attr( 'rows','40' );
				return false;
			}
		);

		$( "#multireminder #checkall" ).click(
			function(){
				$( '#multireminder input:checkbox' ).not( this ).prop( 'checked', this.checked );
			}
		);

		$( '.end_time_type' ).change(
			function() {
				var type   = $( this ).val();
				if ((type == 'set') || (type.search( 'ulti' ) > 0)) {
					$( '#endtimespan').show();
				} else {
					$( '#endtimespan').hide();
				}
			}
		);

		$('#newrsvptime').change(function() {
			var time = $( this ).val();
			var date = new Date('2000-01-01T'+time);
			date.setTime(date.getTime()+(60*60*1000));
			$('#rsvpendtime').val(date.toLocaleTimeString('en-GB'));
		});
		
		$('.quick-rsvp-date').change( function() {
			let datetext = $(this).val();
			let count = parseInt($(this).attr('count'));
			console.log(datetext);
			console.log(count);
			$('.quick-rsvp-date').each(
				function (i) {
					if(i > count)
					$(this).val(datetext);
				}
			);
		});

		$('.quick-rsvp-time').change( function() {
			let timetext = $(this).val();
			let count = parseInt($(this).attr('count'));
			console.log('start:'+timetext);
			console.log(count);
			var date = new Date('2000-01-01T'+timetext);
			date.setTime(date.getTime()+(60*60*1000));
			console.log(date);
			$('#quick-rsvp-time-end-'+count).val(date.toLocaleTimeString('en-GB'));
			$('.quick-rsvp-time').each(
				function(i) {
					if(i > count)
					{
						$('#quick-rsvp-time-'+i).val(date.toLocaleTimeString('en-GB'));
						date.setTime(date.getTime()+(60*60*1000));
						$('#quick-rsvp-time-end-'+i).val(date.toLocaleTimeString('en-GB'));
					}
				}
			);
		});

		$('.quick-rsvp-time-end').change( function() {
			let timetext = $(this).val();
			let count = parseInt($(this).attr('count'));
			console.log(timetext);
			console.log(count);
			count++;
			$('#quick-rsvp-time-'+count).val(timetext);
		});

		function default_end_time(target) {
			var end_id         = target.replace( 'end_time','sql-end' );
			var start_id       = target.replace( 'end_time','sql-date' );
			var start_date_sql = $( '#' + start_id ).val();
			if (typeof start_date_sql == 'undefined') {
				/*try template*/
				start_date_sql = '2001-01-01 ' + $( '#sql-time' ).val();
			}
			console.log( 'start date sql' );
			console.log( start_date_sql );
			var t  = Date.parse( start_date_sql ) + (60 * 60 * 1000);
			var dt = new Date( t );
			$( '#' + end_id ).val( rsvpsql_end_time( dt ) );
		}

		$( '.end_time select' ).change(
			function() {
				$( '.end_time_type' ).val( 'set' );
			}
		);

		$( '#reset_stripe_production' ).click(
			function(event) {
				event.preventDefault();
				$( '#stripe_production' ).html( '<p>Publishable Key (Production):<br /><input name="_keys[pk]" value=""></p><p>Secret Key (Production):<br /><input name="rsvpmaker_stripe_keys[sk]" value=""></p>' );
			}
		);

		$( '#reset_stripe_sandbox' ).click(
			function(event) {
				event.preventDefault();
				$( '#stripe_sandbox' ).html( '<p>Publishable Key (Sandbox):<br /><input name="rsvpmaker_stripe_keys[sandbox_pk]" value=""></p><p>Secret Key (Sandbox):<br /><input name="rsvpmaker_stripe_keys[sandbox_sk]" value=""></p>' );
			}
		);

		$( '#reset_paypal_production' ).click(
			function(event) {
				event.preventDefault();
				$( '#paypal_production' ).html( '<p>Client ID (Production):<br /><input name="rsvpmaker_paypal_rest_keys[client_id]" value=""></p><p>Client Secret (Production):<br /><input name="rsvpmaker_paypal_rest_keys[client_secret]" value=""></p>' );
			}
		);
		$( '#reset_paypal_sandbox' ).click(
			function(event) {
				event.preventDefault();
				$( '#paypal_sandbox' ).html( '<p>Client ID (Sandbox):<br /><input name="rsvpmaker_paypal_rest_keys[sandbox_client_id]" value=""></p><p>Client Secret (Sandbox):<br /><input name="rsvpmaker_paypal_rest_keys[sandbox_client_secret]" value=""></p>' );
			}
		);

		$( "form#rsvpmaker_setup" ).submit(
			function( event ) {
				var error = false;
				var date  = $( '#sql-date' ).val();
				var end   = $( '#sql-end' ).val();
				if ( ! date.match( /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/ )) {
					error = true;
				} else if ((end != '') && ! end.match( /^\d{2}:\d{2}$/ )) {
					error = true;
				} else if (Number.isNaN( Date.parse( date ) ) || Number.isNaN( Date.parse( '2001-01-01 ' + end ) )) {
					error = true;
				} else {
					error = false;
				}
				if (error) {
					alert( "Fix date format errors" );
					event.preventDefault();
					return;
				}

				var data = $( this ).serializeArray();
				var url  = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/setup';
				$( "form#rsvpmaker_setup" ).html( '<h1>Creating Draft ...</h1>' );
				jQuery.post(
					url,
					data,
					function(editurl) {
						window.location.href = editurl;
						$( "form#rsvpmaker_setup" ).html( '<h1>Draft Created: <a href="' + editurl + '">Edit</a></h1><p>Draft should load automatically.</p>' );
					}
				);
				event.preventDefault();
			}
		);

		$( "form#email_templates" ).submit(
			function( event ) {
				var data = $( this ).serializeArray();
				var url  = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/email_templates';
				$( "form#email_templates" ).html( '<h1>Saving ...</h1>' );
				jQuery.post(
					url,
					data,
					function(response) {
						$( "form#email_templates" ).html( response );
					}
				);
				event.preventDefault();
			}
		);

		$( "form#rsvpmaker_notification_templates" ).submit(
			function( event ) {
				var data = $( this ).serializeArray();
				var url  = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/notification_templates';
				$( "form#rsvpmaker_notification_templates" ).html( '<h1>Saving ...</h1>' );
				jQuery.post(
					url,
					data,
					function(response) {
						document.getElementById( "rsvpmaker_notification_templates" ).scrollIntoView( true );
						$( "form#rsvpmaker_notification_templates" ).html( response );
					}
				);
				event.preventDefault();
			}
		);

		$( "form#rsvpmaker_details" ).submit(
			function( event ) {
				var data = $( this ).serializeArray();
				var url  = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/rsvpmaker_details';
				$( "#rsvpmaker_details_status" ).html( '<h1>Saving ...</h1>' );
				document.getElementById( "headline" ).scrollIntoView( true );
				jQuery.post(
					url,
					data,
					function(response) {
						$( "#rsvpmaker_details_status" ).html( response );
					}
				);
				event.preventDefault();
			}
		);

		$( '#month0' ).change(
			function () {
				var datestring = $( '#year0' ).val() + '-' + $( '#month0' ).val() + '-' + $( '#day0' ).val();
				var target     = new Date( datestring );
				var now        = new Date();
				var nextyear   = parseInt( $( '#year0' ).val() ) + 1;
				if (target < now) {
					  var nextyeartext = nextyear.toString();
				}
				$( '#year0' ).val( nextyeartext );
			}
		);

		$( '.quick_event_date' ).change(
			function () {
				const options  = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
				var datestring = $( this ).val();
				var id         = $( this ).attr( 'id' );
				var post_id    = $( this ).attr( 'post_id' );
				var target     = id.replace( 'quick_event_date','quick_event_date_text' );
				var illegal    = datestring.match( /[^\d: -]/ );
				if (illegal) {
					$( '#' + target ).html( 'Illegal characters in datetime string' );
					return;
				}
				var dt           = new Date( datestring );
				var localestring = dt.toLocaleDateString( undefined, options ) + ' ' + dt.toLocaleTimeString().replace( ':00 ',' ' );
				if (localestring == 'Invalid Date Invalid Date') {
					$( '#' + target ).html( 'Date string not valid' );
				} else {
					$( '#' + target ).html( localestring );
					$( this ).val( dt.getFullYear() + '-' + pad2( dt.getMonth() + 1 ) + '-' + pad2( dt.getDate() ) + ' ' + pad2( dt.getHours() ) + ':' + pad2( dt.getMinutes() ) + ':' + pad2( dt.getSeconds() ) );
				}
				$( 'span #rsvpmaker-date-' + post_id + ' .rsvpmaker-date' ).text( datestring );
			}
		);

		$( '.quick_end_time' ).change(
			function () {
				var timestring = $( this ).val();
				var id         = $( this ).attr( 'id' );
				var target     = id.replace( 'quick_end_time','quick_end_time_text' );
				var illegal    = timestring.match( /[^\d:]/ );
				if (illegal) {
					$( '#' + target ).html( 'Illegal characters in time string' );
					return;
				}
				var post_id = $( this ).attr( 'post_id' );
				// var datestr = $('#quick_event_date-'+post_id).val();
				var datestr      = $( '#post-' + post_id + ' .rsvpmaker_end' ).text();
				var dateparts    = datestr.split( ' ' );
				var newendstr    = dateparts[0] + ' ' + timestring;
				var datestr      = $( '#post-' + post_id + ' .rsvpmaker_end' ).text( newendstr );
				var dt           = new Date( newendstr );
				var localestring = dt.toLocaleTimeString().replace( ':00 ',' ' );
				if (localestring == 'Invalid Date') {
					$( '#' + target ).html( 'Time string not valid' );
				} else {
					$( '#' + target ).html( localestring );
					$( this ).val( pad2( dt.getHours() ) + ':' + pad2( dt.getMinutes() ) );
				}
			}
		);

		/* update quick edit ui based on https://rudrastyh.com/wordpress/quick-edit-tutorial.html
		it is a copy of the inline edit function */
		if (typeof inlineEditPost !== 'undefined') {
			var wp_inline_edit_function = inlineEditPost.edit;

			/* we overwrite the it with our own */
			inlineEditPost.edit = function( post_id ) {

				/* let's merge arguments of the original function */
				wp_inline_edit_function.apply( this, arguments );

				/* get the post ID from the argument */
				var id = 0;
				if ( typeof( post_id ) == 'object' ) {
					id = parseInt( this.getId( post_id ) );
				}

				/* if post id exists */
				if ( id > 0 ) {
					var specific_post_edit_row = $( '#edit-' + id ),
					specific_post_row          = $( '#post-' + id ),
					datetext                   = $( '.rsvpmaker-date', specific_post_row ).text();
					var timestamp              = Date.parse( datetext );
					if (Number.isNaN( timestamp )) {
						return;
					}
					var dt       = new Date( timestamp );
					var dtstring = dt.getFullYear() + '-' + pad2( dt.getMonth() + 1 ) + '-' + pad2( dt.getDate() ) + ' ' + pad2( dt.getHours() ) + ':' + pad2( dt.getMinutes() ) + ':' + pad2( dt.getSeconds() );
					var justdate = dt.getFullYear() + '-' + pad2( dt.getMonth() + 1 ) + '-' + pad2( dt.getDate() );

					var endtext = $( '.rsvpmaker_end', specific_post_row ).text();
					if (endtext == '') {
						var hourplus      = pad2( dt.getHours() + 1 ) + ':' + pad2( dt.getMinutes() ) + ':' + pad2( dt.getSeconds() );
						var end_date_time = new Date( justdate + ' ' + hourplus );
					} else {
						var end_date_time = new Date( endtext );
					}
					var end_tstring = pad2( end_date_time.getHours() ) + ':' + pad2( end_date_time.getMinutes() );

					var end_display_code = $( '.end_display_code', specific_post_row ).val();

					/* populate the inputs with column data */
					$( ':input[name="event_dates"]', specific_post_edit_row ).val( dtstring );
					$( ':input[name="end_time"]', specific_post_edit_row ).val( end_tstring );
					if (end_display_code) {
						$( '.quick_time_display', specific_post_edit_row ).val( end_display_code );
					}
				}
			}
		}

		function pad2(number) {
			return (number < 10 ? '0' : '') + number
		}

		function rsvpmaker_weekday(dt) {
			var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
			return days[dt.getDay()];
		}

		function rsvpsql_date(dt) {
			return dt.getFullYear() + '-' + pad2( dt.getMonth() + 1 ) + '-' + pad2( dt.getDate() ) + ' ' + pad2( dt.getHours() ) + ':' + pad2( dt.getMinutes() ) + ':' + pad2( dt.getSeconds() );
		}

		function rsvpsql_end_time(dt) {
			return pad2( dt.getHours() ) + ':' + pad2( dt.getMinutes() );
		}

		$(
			function() {
				$( "#sql-date" ).datepicker(
					{
						showOn: "button",
						buttonImage: "../wp-content/plugins/rsvpmaker/datepicker.gif",
						buttonImageOnly: true,
						buttonText: "Select date",
						onSelect: function()
					{
									  var dt      = $( this ).datepicker( 'getDate' );
									  var sql     = $( '#sql-date' ).val();
									  var hour    = parseInt( $( '#defaulthour' ).val() );
									  var minutes = parseInt( $( '#defaultmin' ).val() );
									  dt.setHours( hour );
									  dt.setMinutes( minutes );
									  $( '#sql-date' ).val( rsvpsql_date( dt ) );
									  set_free_text_date( dt );
									  $( '#date-weekday' ).html( rsvpmaker_weekday( dt ) );
									  return false;
						}
					}
				);
			}
		);

		function set_free_text_date(dt) {
			const options    = { year: 'numeric', month: 'long', day: 'numeric' };
			var localestring = dt.toLocaleDateString( undefined, options ) + ' ' + dt.toLocaleTimeString().replace( ':00 ',' ' );
			$( '#free-text-date' ).val( localestring );
		}

		$( '.sql-date' ).change(
			function() {
				var datetext = $( this ).val();
				var t        = Date.parse( datetext );
				var error_id = $( this ).attr( 'id' ).replace( 'sql-date','date_error' );
				if (Number.isNaN( t )) {
					$( '#' + error_id ).html( '<span style="color:red">Error:</span> sql date is not valid' );
				} else {
					const options = { year: 'numeric', month: 'long', day: 'numeric' };
					$( '#' + error_id ).html( '' );
					var dt           = new Date( t );
					var localestring = dt.toLocaleDateString( undefined, options ) + ' ' + dt.toLocaleTimeString().replace( ':00 ',' ' );
					var target       = $( this ).attr( 'id' ).replace( 'sql','free-text' );
					$( '#' + target ).val( localestring );
					$( this ).val( rsvpsql_date( dt ) );/* standardize format */
					$( '#date-weekday' ).html( rsvpmaker_weekday( dt ) );
				}
			}
		);
		/*simulate a change on load*/
		$( '.sql-date' ).change();

		$( '.quick-extra-blank' ).hide();
		var quickeditcount = 0;

		$( '#add-quick-blank' ).click(
			function (e) {
				if (quickeditcount == 0) {
					quickeditcount = parseInt( $( this ).attr( 'start' ) );
				}
				e.preventDefault();
				$( '#quick-extra-blank-' + quickeditcount ).show();
				quickeditcount++;
			}
		);

		$( '#skedtimetext' ).change(
			function() {
				let datetext = 'January 1, 2000 ' + $( '#skedtimetext' ).val();
				let t        = Date.parse( datetext );
				if (Number.isNaN( t )) {
					$( '#template-time-error' ).html( '<p style="color: red;">Invalid time</p>' );
				} else {
					let date = new Date( t );
					$( '#hour0' ).val( pad2( date.getHours() ) );
					$( '#minutes0' ).val( pad2( date.getMinutes() ) );
					$( '#template-time-error' ).html( '' );
					let localestring = date.toLocaleTimeString().replace( ':00 ',' ' );
					$( '#skedtimetext' ).val( localestring );
				}
			}
		);

		function quick_template_time() {
			let hour = $( '#hour0' ).val();
			if (typeof hour == 'undefined') {
				return;
			}
			hour        = hour.replace( /[^0-9]/,'' );
			hour        = pad2( parseInt( hour ) );
			let minutes = $( '#minutes0' ).val().replace( /[^0-9]/,'' );
			minutes     = pad2( parseInt( minutes ) );
			let t       = Date.parse( 'January 1, 2000 ' + hour + ':' + minutes );
			if (Number.isNaN( t )) {
				/* set to a legal default value */
				hour    = '12';
				minutes = '00';
				t       = Date.parse( 'January 1, 2000 ' + hour + ':' + minutes );
			}
			$( '#hour0' ).val( hour );
			$( '#minutes0' ).val( minutes );
			let dt           = new Date( t );
			let localestring = dt.toLocaleTimeString().replace( ':00 ',' ' );
			$( '#skedtimetext' ).val( localestring );
			$( '#template-time-error' ).html( '' );
		}

		$( '#hour0, #minutes0' ).change(
			function() {
				quick_template_time();
			}
		);

		quick_template_time();

if($('#stripe_on') !== undefined) {
var stripe_on = ('1' == $('#stripe_on').val());
var paypal_on = ('1' == $('#spaypal_on').val());
console.log('stripe '+ stripe_on);
console.log('paypal '+ paypal_on);
}
$('.paypal_keys').change( function() {
	paypal_on = true;
	if(stripe_on && paypal_on)
		$('#payment_gateway').val('Both Stripe and PayPal');
	else {
		$('#payment_gateway').val('PayPal REST API');
	}
});


$('.stripe_keys').change( function() {
	stripe_on = true;
	if(stripe_on && paypal_on)
		$('#payment_gateway').val('Both Stripe and PayPal');
	else {
		$('#payment_gateway').val('Stripe');
	}
});
	
	
});

