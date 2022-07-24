<?php

function rsvpmaker_to_ical_email( $post_id = 0, $from_email, $rsvp_email, $description = '' ) {
	global $post;
	$backslash = '\\';

	global $rsvp_options;

	if ( $post_id > 0 ) {

		$post = get_post( $post_id );
	}

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

	if ( ! empty( $hangout ) ) {

		$description .= 'Google Hangout: ' . $hangout . "\n";
	}

	if(empty($description))
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

	$ical[] = 'CALSCALE:GREGORIAN';

	$ical[] = 'PRODID:-//WordPress//RSVPMaker//EN';

	$ical[] = 'METHOD:REQUEST';

	$ical[] = 'BEGIN:VEVENT';

	$ical[] = 'DTSTAMP:' . $dtstamp;

	$ical[] = 'ORGANIZER;SENT-BY="MAILTO:' . $from_email . '":MAILTO:' . $from_email;
	//$ical[] = 'ORGANIZER;CN=' . get_bloginfo('name') . '":MAILTO:' . $from_email;

	$ical[] = 'ATTENDEE;CN=' . $rsvp_email . ';ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;' . 'RSVP=TRUE:mailto:' . $from_email;

	$ical[] = 'UID:' . strtoupper( md5( $event_id ) ) . '@rsvpmaker.com';

	$ical[] = 'SEQUENCE:' . $sequence;

	$ical[] = 'STATUS:' . $status;

	$ical[] = 'DTSTART:' . $start . 'T' . $start_time . 'Z';

	$ical[] = 'DTEND:' . $end . 'T' . $end_time . 'Z';

	$ical[] = 'LOCATION:' . rsvpmaker_ical_escape($venue);

	$ical[] = 'SUMMARY:' . $summary;

	$ical[] = 'DESCRIPTION:' . rsvpmaker_ical_escape($description);

	$ical[] = 'BEGIN:VALARM';

	$ical[] = 'TRIGGER:-PT15M';

	$ical[] = 'ACTION:DISPLAY';

	$ical[] = 'END:VALARM';

	$ical[] = 'END:VEVENT';

	$ical[] = 'END:VCALENDAR';

	$icalstring = '';

	foreach ( $ical as $line ) {

		if ( strlen( $line ) >= 70 ) {
			$line = trim( chunk_split( $line, 70, "\r\n" ) );
			$line = str_replace("\n","\n ",$line);
		}
		$icalstring .= $line . "\r\n";
	}

	return trim( $icalstring );

}

function rsvpmaker_ical_escape($text) {
	$text = addslashes($text);
	$text = str_replace(':','\:',$text);
	$text = str_replace(';','\;',$text);
	$text = str_replace(',','\,',$text);
	$text = str_replace("\n","\\n",$text);
	return $text;
}