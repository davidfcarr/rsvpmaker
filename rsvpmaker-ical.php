<?php
include $rsvpmaker_dir . 'icalcreator/autoload.php';
use Kigkonsult\Icalcreator\Vcalendar;
use DateTime;
use DateTimezone;

function rsvpmaker_to_ical_email( $post_id = 0, $from_email ='', $rsvp_email = '' ) {
	global $post;
	global $rsvp_options;
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
	}
	/*
	$tz = rsvpmaker_get_timezone_string();
	$timeZone = new DateTimeZone($tz);
	$event = get_rsvpmaker_event($post->ID);
	$ts_end = intval($event->ts_end);
	//gmdate('Ymd',$ts_start).'T'.gmdate('His',$ts_start);
	$start = new DateTime();
	$start->setTimestamp(intval($event->ts_start))->setTimezone($timeZone);
	
	$end = new DateTime();
	$end->setTimestamp(intval($event->ts_end))->setTimezone($timeZone);
	
	$confirm = rsvp_get_confirm( $post_id, true );
	$confirm_text = strip_tags($confirm->post_content);
	
	// create a new calendar
	$vcalendar = Vcalendar::factory( [ Vcalendar::UNIQUE_ID => "kigkonsult.se", ] )
	
		// with calendaring info
					 ->setMethod( Vcalendar::REQUEST )
					 ->setXprop(
						  Vcalendar::X_WR_CALNAME,
						  get_bloginfo('name')
					 )
					 ->setXprop(
						  Vcalendar::X_WR_RELCALID,
						  "3E26604A-50F4-4449-8B3E-E4F4932D05B5"
					 )
					 ->setXprop(
						  Vcalendar::X_WR_TIMEZONE,
						  $tz
					 );
	
		// create a new event
		$event1 = $vcalendar->newVevent()
		->setClass( Vcalendar::P_BLIC )
	// describe the event
		->setSummary( $post->post_title )
		->setDescription(
			'Event info: ' . get_permalink( $post->ID )."\n\n".$confirm_text
		)
	// set the time
		->setDtstart($start)
		->setDtend($end);
		//->setStatsus('CONFIRMED');
	if(empty($rsvp_email))
		$rsvp_email = get_post_meta($post->ID,'_rsvp_to',true);
	if(empty($rsvp_email))
		$rsvp_email = $rsvp_options['rsvp_to'];
	if(!empty($rsvp_email))
		$event1->setAttendee($rsvp_email);
	if(!empty($from_email))
		$event1->setOrganizer(
			$from_email,
			[ Vcalendar::CN => 'Organizer' ]
		);
rsvpmaker_debug_log($from_email,'from email');
rsvpmaker_debug_log($event1,'event1');

		//->setAttendee($rsvp_email);
	// with recurrence rule
	/*	->setRrule(
			[
				Vcalendar::FREQ  => Vcalendar::WEEKLY,
				Vcalendar::COUNT => 5,
			]
		)
	// and set another on a specific date
		->setRdate(
			[
				new DateTime(
					'20190609T090000',
					new DateTimezone( 'Europe/Stockholm' )
				),
				new DateTime(
					'20190609T110000',
					new DateTimezone( 'Europe/Stockholm' )
				),
			],
			[ Vcalendar::VALUE => Vcalendar::PERIOD ]
		)
	// and revoke a recurrence date
		->setExdate(
			new DateTime(
				'2019-05-12 09:00:00',
				new DateTimezone( 'Europe/Stockholm' )
			)
		)
	// organizer, chair and some participants
		->setOrganizer(
			$from_email,
			[ Vcalendar::CN => 'Organizer' ]
		)
		->setAttendee(
			'president@coffeebean.com',
			[
				Vcalendar::ROLE     => Vcalendar::CHAIR,
				Vcalendar::PARTSTAT => Vcalendar::ACCEPTED,
				Vcalendar::RSVP     => Vcalendar::FALSE,
				Vcalendar::CN       => 'President CoffeeBean',
			]
		)
		->setAttendee(
			'participant1@coffeebean.com',
			[
				Vcalendar::ROLE     => Vcalendar::REQ_PARTICIPANT,
				Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
				Vcalendar::RSVP     => Vcalendar::TRUE,
				Vcalendar::CN       => 'Participant1 CoffeeBean',
			]
		)
		->setAttendee(
			'participant2@coffeebean.com',
			[
				Vcalendar::ROLE     => Vcalendar::REQ_PARTICIPANT,
				Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
				Vcalendar::RSVP     => Vcalendar::TRUE,
				Vcalendar::CN       => 'Participant2 CoffeeBean',
			]
		);
	
		// add alarm for the event
	$alarm = $event1->newValarm()
				 ->setAction( Vcalendar::DISPLAY )
		// copy description from event
				 ->setDescription( $event1->getDescription())
		// fire off the alarm one day before
				 ->setTrigger( '-P1D' );
	$vcalendarString =
		// apply appropriate Vtimezone with Standard/DayLight components
		$vcalendar->vtimezonePopulate()
		// and create the (string) calendar
		->createCalendar();
return $vcalendarString;
*/
	global $wpdb;

	if ( ( $post->post_type != 'rsvpmaker' ) ) {

		return;
	}

	$dates    = get_rsvp_dates( $post_id );
	$date     = $dates[0];
	$datetime = $date['datetime'];
	$end_time = $date['end_time'];

	if ( sizeof( $dates ) > 1 ) {
		$lastdate    = array_pop( $dates );
		$duration_ts = rsvpmaker_strtotime( $lastdate['datetime'] );
	} elseif ( empty( $end_time ) ) {
		$duration_ts = rsvpmaker_strtotime( $datetime . ' +1 hour' );
	} else {
		$p           = explode( ' ', $datetime );
		$duration_ts = rsvpmaker_strtotime( $p[0] . ' ' . $end_time );
	}

	$start_ts = rsvpmaker_strtotime( $datetime );

	$hangout = get_post_meta( $post->ID, '_hangout', true );

	$description = '';

	if ( ! empty( $hangout ) ) {

		$description .= 'Google Hangout: ' . $hangout . "\n";
	}

	$description .= 'Event info: ' . get_permalink( $post->ID );

	$summary = $post->post_title;

	$venue_meta = get_post_meta( $post->ID, 'venue', true );

	$venue = ( empty( $venue_meta ) ) ? 'See: ' . get_permalink( $post->ID ) : $venue_meta;

	$dtstamp = gmdate( 'Ymd' ) . 'T' . gmdate( 'His' ) . 'Z';

	$start = gmdate( 'Ymd', $start_ts );

	$start_time = gmdate( 'His', $start_ts );

	$end = gmdate( 'Ymd', $duration_ts );

	$end_time = gmdate( 'His', $duration_ts );

	$event_id = $post->ID;

	$sequence = 0;

	$status = 'CONFIRMED';

	$ical[] = 'BEGIN:VCALENDAR';

	$ical[] = 'VERSION:2.0';

	$ical[] = 'PRODID:-//WordPress//RSVPMaker//EN';

	$ical[] = 'METHOD:REQUEST';

	$ical[] = 'BEGIN:VEVENT';

	$ical[] = 'DTSTAMP:' . $dtstamp;

	$ical[] = 'ORGANIZER;SENT-BY="MAILTO:' . $from_email . '":MAILTO:' . $from_email;

	$ical[] = 'ATTENDEE;CN=' . $rsvp_email . ';ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;' . 'RSVP=TRUE:mailto:' . $from_email;

	$ical[] = 'UID:' . get_permalink( $post->ID ); //strtoupper( md5( $event_id ) ) . '-rsvpmaker.com';

	//$ical[] = 'SEQUENCE:' . $sequence;

	$ical[] = 'STATUS:' . $status;

	$ical[] = 'DTSTART:' . $start . 'T' . $start_time . 'Z';

	$ical[] = 'DTEND:' . $end . 'T' . $end_time . 'Z';

	$ical[] = 'LOCATION:' . $venue;

	$ical[] = 'SUMMARY:' . $summary;

	$ical[] = 'DESCRIPTION:' . $description;

	//$ical[] = 'BEGIN:VALARM';

	//$ical[] = 'TRIGGER:-PT15M';

	//$ical[] = 'ACTION:DISPLAY';

	//$ical[] = 'DESCRIPTION:Reminder';

	//$ical[] = 'END:VALARM';

	$ical[] = 'END:VEVENT';

	$ical[] = 'END:VCALENDAR';

	$icalstring = '';

	foreach ( $ical as $line ) {

		if ( strlen( $line ) >= 70 ) {

			$line = trim( chunk_split( $line, 70, "\r\n " ) );

		}

		$icalstring .= $line . "\r\n";

	}

	return trim( $icalstring );

}

add_shortcode('ical_test','ical_test');

function ical_test() {
global $post;
$ical = rsvpmaker_to_ical_email();
	return '<pre>'.$ical.'</pre>';
$tz = rsvpmaker_get_timezone_string();
$timeZone = new DateTimeZone($tz);
$event = get_rsvpmaker_event($post->ID);
$ts_end = intval($event->ts_end);
//gmdate('Ymd',$ts_start).'T'.gmdate('His',$ts_start);
$start = new DateTime();
$start->setTimestamp(intval($event->ts_start))->setTimezone($timeZone);

$end = new DateTime();
$end->setTimestamp(intval($event->ts_end))->setTimezone($timeZone);

$confirm = rsvp_get_confirm( $post_id, true );
$confirm_text = strip_tags($confirm->post_content);

// create a new calendar
$vcalendar = Vcalendar::factory( [ Vcalendar::UNIQUE_ID => "kigkonsult.se", ] )

    // with calendaring info
                 ->setMethod( Vcalendar::REQUEST )
                 ->setXprop(
                      Vcalendar::X_WR_CALNAME,
                      get_bloginfo('name')
                 )
                 ->setXprop(
                      Vcalendar::X_WR_RELCALID,
                      "3E26604A-50F4-4449-8B3E-E4F4932D05B5"
                 )
                 ->setXprop(
                      Vcalendar::X_WR_TIMEZONE,
                      $tz
                 );

    // create a new event
	$event1 = $vcalendar->newVevent()
	->setClass( Vcalendar::P_BLIC )
// describe the event
	->setSummary( $post->post_title )
	->setDescription(
		quoted_printable_encode('Event info: ' . get_permalink( $post->ID )."\n\n".$confirm_text)
		)
// set the time
	->setDtstart($start)
	->setDtend($end);
// with recurrence rule
/*	->setRrule(
		[
			Vcalendar::FREQ  => Vcalendar::WEEKLY,
			Vcalendar::COUNT => 5,
		]
	)
// and set another on a specific date
	->setRdate(
		[
			new DateTime(
				'20190609T090000',
				new DateTimezone( 'Europe/Stockholm' )
			),
			new DateTime(
				'20190609T110000',
				new DateTimezone( 'Europe/Stockholm' )
			),
		],
		[ Vcalendar::VALUE => Vcalendar::PERIOD ]
	)
// and revoke a recurrence date
	->setExdate(
		new DateTime(
			'2019-05-12 09:00:00',
			new DateTimezone( 'Europe/Stockholm' )
		)
	)
// organizer, chair and some participants
	->setOrganizer(
		'secretary@coffeebean.com',
		[ Vcalendar::CN => 'Secretary CoffeeBean' ]
	)
	->setAttendee(
		'president@coffeebean.com',
		[
			Vcalendar::ROLE     => Vcalendar::CHAIR,
			Vcalendar::PARTSTAT => Vcalendar::ACCEPTED,
			Vcalendar::RSVP     => Vcalendar::FALSE,
			Vcalendar::CN       => 'President CoffeeBean',
		]
	)
	->setAttendee(
		'participant1@coffeebean.com',
		[
			Vcalendar::ROLE     => Vcalendar::REQ_PARTICIPANT,
			Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
			Vcalendar::RSVP     => Vcalendar::TRUE,
			Vcalendar::CN       => 'Participant1 CoffeeBean',
		]
	)
	->setAttendee(
		'participant2@coffeebean.com',
		[
			Vcalendar::ROLE     => Vcalendar::REQ_PARTICIPANT,
			Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
			Vcalendar::RSVP     => Vcalendar::TRUE,
			Vcalendar::CN       => 'Participant2 CoffeeBean',
		]
	);

    // add alarm for the event
$alarm = $event1->newValarm()
             ->setAction( Vcalendar::DISPLAY )
    // copy description from event
             ->setDescription( $event1->getDescription())
    // fire off the alarm one day before
             ->setTrigger( '-P1D' );
*/
$vcalendarString =
    // apply appropriate Vtimezone with Standard/DayLight components
    $vcalendar->vtimezonePopulate()
    // and create the (string) calendar
    ->createCalendar();
return '<pre>'.$vcalendarString.'</pre>';
}