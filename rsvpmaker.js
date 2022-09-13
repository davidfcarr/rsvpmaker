jQuery( document ).ready(
	function($) {

		$.ajaxSetup(
			{
				headers: {
					'X-WP-Nonce': rsvpmaker_rest.nonce,
				}
			}
		);

		$('.wp-block-rsvpmaker-formfield input').change( function () {
			let v = $(this).val();
			let h = v.includes('//');
			console.log(v+' '+h);
			if(h) {
				v = v.replace(/[a-z]{0,8}:{0,1}\/\//,'');	
				console.log('strip prefix');
				$(this).val(v);	
			}
		});

		$( '.rsvpmaker-schedule-detail' ).hide();
		$( '.rsvpmaker-schedule-button' ).click(
			function( event ) {
				var button_id = $( this ).attr( 'id' );
				var more_id   = button_id.replace( 'button','detail' );
				$( '#' + button_id ).hide();
				$( '#' + more_id ).show();
			}
		);
		$( '.wp-block-rsvpmaker-countdown' ).each(
			function () {
				var event_id     = $( this ).attr( 'event_id' );
				var countdown_id = $( this ).attr( 'id' );
				if (event_id == '') {
					var parts = countdown_id.split( '-' );
					if (parts[1]) {
						event_id = parts[1];
					}
				}
				if (event_id == '') {
					return;
				}
				let apiurl = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/time_and_zone/' + event_id;
				jQuery.get(
					apiurl,
					null,
					function(response) {
						let t = parseInt( response );
						if (Number.isNaN( t )) {
							$( '#' + countdown_id ).html( 'Event not found' );
							return;
						}
						let interval = setInterval(
							function() {
								var now      = new Date().getTime();
								var distance = t - now;
								var days     = Math.floor( distance / (1000 * 60 * 60 * 24) );
								var hours    = Math.floor( (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60) );
								var hpad     = (hours < 10) ? '0' : '';
								var minutes  = Math.floor( (distance % (1000 * 60 * 60)) / (1000 * 60) );
								var mpad     = (minutes < 10) ? '0' : '';
								var seconds  = Math.floor( (distance % (1000 * 60)) / 1000 );
								var spad     = (seconds < 10) ? '0' : '';
								if (distance < 0) {
									clearInterval( interval );
									days           = hours = minutes = seconds = '00';
									let display    = $( '#' + countdown_id ).attr( 'expiration_display' );
									let message    = $( '#' + countdown_id ).attr( 'expiration_message' );
									let expiration = '';
									if ((display == 'stoppedclock') || display == 'clockmessage') {
										expiration = '<div class="countdowndigits-line"><div class="countdowndigits countdowndays">' + days + '</div> <span class="countdowndayslabel">days</span> <div class="countdowndigits countdownhours">' + hours + '</div><span class="countdownspacer">:</span><div class="countdowndigits countdownminutes">' + minutes + '</div><span class="countdownspacer">:</span><div class="countdowndigits countdownseconds">' + seconds + '</div></div>';
									}
									if ((display == 'message') || display == 'clockmessage') {
										expiration = expiration + '<p class="countdown_expiration_message">' + message + '</p>';
									}
									$( '#' + countdown_id ).html( expiration );
								} else {
									$( '#' + countdown_id ).html( '<div class="countdowndigits-line"><div class="countdowndigits countdowndays">' + days + '</div> <span class="countdowndayslabel">days</span> <div class="countdowndigits countdownhours">' + hpad + hours + '</div><span class="countdownspacer">:</span><div class="countdowndigits countdownminutes">' + mpad + minutes + '</div><span class="countdownspacer">:</span><div class="countdowndigits countdownseconds">' + spad + seconds + '</div></div>' );
								}
							},
							1000
						);
					}
				);
			}
		);
		$( '.timezone_on' ).click(
			function () {
				var utc      = $( this ).attr( 'utc' );
				var target   = $( this ).attr( 'target' );
				var newtz    = target.replace( 'timezone_converted','tz_convert_to' );
				var event_tz = $( this ).attr( 'event_tz' );
				if (event_tz == '') {
					  return;
				}
				var localdate = new Date( utc );

				localstring = localdate.toString();

				$( '#' + target ).html( localstring );
				var match = localstring.match( /\(([^)]+)/ );
				$( this ).attr( 'event_tz','' );// so it won't run twice
				$( '#' + newtz ).html( 'Converting to ' + match[1] );
				var timeparts = utc.split( /T/ );
				var newtime;
				var timecount = 0;
				$( '.tz-convert, .tz-convert table tr td, .tz-table1 table tr td:first-child, .tz-table2 table tr td:nth-child(2), .tz-table3 table tr td:nth-child(3)' ).each(
					function () {
						celltime = this.innerHTML.replace( '&nbsp;',' ' );
						// if contains time but not more html
						if ((celltime.search( /\d:\d\d/ ) >= 0) && (celltime.search( '<' ) < 0)) {
							  timecount++;
							  newtime = timeparts[0] + ' ' + celltime + ' ' + event_tz;
							  ts      = Date.parse( newtime );
							if ( ! Number.isNaN( ts )) {
								localdate.setTime( ts );
								newtime        = localdate.toLocaleTimeString().replace( ':00 ',' ' );
								this.innerHTML = newtime;
								$( this ).css( 'font-weight','bold' );
							}
						}

					}
				);

				var checkrow = true;
				$( '.tz-table1 table tr td:first-child, .tz-table2 table tr td:nth-child(2), .tz-table3 table tr td:nth-child(3)' ).each(
					function() {
						if (checkrow && (this.innerHTML != '') && (this.innerHTML.search( ':' ) < 0) ) { // if this looks like a column header
							this.innerHTML = '<strong>Your TZ</strong>';
						}
						checkrow = false;
					}
				);

				var data = {

					'action': 'rsvpmaker_localstring',

					'localstring': localstring,

					'timelord': rsvpmaker_rest.timelord,

				};

				jQuery.post(
					rsvpmaker_rest.ajaxurl,
					data,
					function(response) {

						$( '#' + target ).html( response );

					}
				);

			}
		);
		$( '.signed_up_ajax' ).each(
			function () {

				var post = $( this ).attr( 'post' );

				var data = {

					'event': post,
					'timelord': rsvpmaker_rest.timelord,

				};

				jQuery.get(
					rsvpmaker_rest.rest_url + 'rsvpmaker/v1/signed_up',
					data,
					function(response) {

						$( '#signed_up_' + post ).html( response );

					}
				);

			}
		);
		function flux_capacitor(tzstring = '', check = true) {
			$( '.tz_converter' ).each(
				function () {
					var id              = $( this ).attr( 'id' );
					var time            = $( this ).attr( 'time' );
					var end             = $( this ).attr( 'end' );
					var format          = $( this ).attr( 'format' );
					var post_id         = $( this ).attr( 'post_id' );
					var server_timezone = $( this ).attr( 'server_timezone' );
					var timezone_abbrev = $( this ).attr( 'timezone_abbrev' );
					var nofluxbutton = $( this ).attr( 'nofluxbutton' );
					console.log('post '+id+' noflux '+nofluxbutton);
					console.log(timezone_abbrev);
					var select          = {};
					var fluxbutton      = {};
					if (check && (tzstring == server_timezone)) {
								if(nofluxbutton)
									return;
								 $( this ).css( 'display','inline-block' );
								 fluxbutton[id]                = document.createElement( "A" );
								 fluxbutton[id].innerHTML      = 'Show in My Timezone';
								 fluxbutton[id].className      = 'tzbutton';
								 fluxbutton[id].style.fontSize = 'small';
								 document.getElementById( id ).appendChild( fluxbutton[id] );
								fluxbutton[id].addEventListener(
									'click',
									(event) => {
										fluxbutton[id].style.display = 'none';
										var tz                       = jstz.determine();
										var tzstring                 = tz.name();
										flux_capacitor( tzstring,false );
									}
								);
								 return;
					}
					var data = {
						'time' : time,
						'end' : end,
						'tzstring' : tzstring,
						'format' : format,
						'post_id' : post_id,
						'timezone_abbrev' : timezone_abbrev,
					};
					console.log( data );
					jQuery.post(
						rsvpmaker_rest.rest_url + 'rsvpmaker/v1/flux_capacitor',
						data,
						function(response) {
							console.log( response );
							$( '#' + id ).html( response.content + ' ' );// + '<select class="timezone_options">'+response.tzoptions+'</select>');
							select[id]               = document.createElement( "SELECT" );
							select[id].innerHTML     = response.tzoptions;
							select[id].className     = 'tzselect';
							select[id].style.display = 'none';
							document.getElementById( id ).appendChild( select[id] );
							select[id].addEventListener(
								'change',
								(event) => {
									var tzstring = event.target.value;
									flux_capacitor( tzstring, false );
								}
							);
							fluxbutton[id]                = document.createElement( "A" );
							fluxbutton[id].innerHTML      = 'Switch Timzeone?';
							fluxbutton[id].className      = 'tzswitch';
							fluxbutton[id].style.fontSize = 'small';
							document.getElementById( id ).appendChild( fluxbutton[id] );
							fluxbutton[id].addEventListener(
								'click',
								(event) => {
									select[id].style.display     = 'block';
									fluxbutton[id].style.display = 'none';
								}
							);
						}
					);
				}
			);
		}
		var tz       = jstz.determine();
		var tzstring = tz.name();
		flux_capacitor( tzstring );

		var guestlist = '';
		function format_guestlist(guest) {

			if ( ! guest.first) {

				return;
			}

			guestlist = guestlist.concat( '<h3>' + guest.first );

			if (guest.last) {

				guestlist = guestlist.concat( ' ' + guest.last );
			}

			guestlist = guestlist.concat( '</h3>\n' );

			if (guest.note) {

				guestlist = guestlist.concat( '<p>' + guest.note + '</p>' );
			}

		}

		function display_guestlist (post_id) {

			var url = rsvpmaker_rest.rsvpmaker_json_url + 'guestlist/' + post_id;

			fetch( url )

			.then(
				response => {
					return response.json()

				}
			)

			.then(
				data => {
                if (Array.isArray( data ))

                {

                    data.forEach( format_guestlist );

                    if (guestlist == '') {

                        guestlist = '<div>?</div>';
                    }

                    $( '#attendees-' + post_id ).html( guestlist );

                }
				}
			)

			.catch(
				err => {
                console.log( err );
                $( '#attendees-' + post_id ).html( 'Error fetching guestlist from ' + url );
				}
			);

		}
		
		$( ".rsvpmaker_show_attendees" ).click(
			function( event ) {

				var post_id = $( this ).attr( 'post_id' );

				guestlist = '';

				display_guestlist( post_id );

			}
		);
		
	}
);

// end jquery

class RSVPJsonWidget {

	constructor(divid, url, limit, morelink = '') {

		this.el = document.getElementById( divid );

		this.url = url;

		this.limit = limit;

		this.morelink = morelink;

		let eventslist = '';

		fetch( url ).then(
			response => {
            return response.json()

			}
		)
		.then(
			data => {
            var showmorelink = false;
            if (Array.isArray( data )) {

                if (limit && (data.length >= limit)) {

                    data = data.slice( 0,limit );

                    showmorelink = true;

                }

                data.forEach(
                function (value, index, data) {

                    if ( ! value.datetime) {

                        return '';
                    }

                    var d = new Date( value.datetime );

                    eventslist = eventslist.concat( '<li class="rsvpmaker-widget-li"><span class="rsvpmaker-widget-title"><a href="' + value.guid + '">' + value.post_title + '</a></span> - <span class="rsvpmaker-widget-date">' + value.date + '</span></li>' );

                }
                );

            } else {
					this.el.innerHTML = 'None found: ' + data.code;
            }
				if (eventslist == '') {

					this.el.innerHTML = 'No event listings found';
				} else {

					if (showmorelink && (morelink != '')) {

						eventslist = eventslist.concat( '<li><a href="' + morelink + '">More events</a></li>' );
					}

					this.el.innerHTML = '<ul class="eventslist rsvpmakerjson">' + eventslist + '</ul>';

				}
			}
		)

		.catch(
			err => {
            this.el.innerHTML = 'Error fetching events from ' + this.url;
            console.log( err );
			}
		);

	}

}

const flexforms = document.querySelectorAll('.rsvpmaker-flexible-form');
flexforms.forEach(flexform => {
  flexform.addEventListener('submit', function handleClick(e) {
    e.preventDefault();
    // Create payload as new FormData object:
    const payload = new FormData(this);
	console.log(payload);
	fetch(rsvpmaker_rest.rest_url+'rsvpmaker/v1/flexform', {
		method: 'POST',
		body: payload,
		})
		.then(res => res.json())
		.then(data => showMessage(data))
	})	
});

function showMessage(data) {
	appslug = document.getElementById( 'appslug' ).value;
	console.log('appslug for message: '+appslug);
	document.getElementById( 'flexible-form-'+appslug ).innerHTML = '';
	document.getElementById( 'flexform-result-'+appslug ).innerHTML = data.message;
}