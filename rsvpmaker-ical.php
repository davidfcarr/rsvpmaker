<?php

function rsvpmaker_to_ical_email( $post_id = 0, $from_email = '', $rsvp_email ='', $description = '', $rsvp_id=0 ) {
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

	$event = get_rsvpmaker_event( $post_id );
	if($rsvp_id) {
		$receipt_code = get_post_meta($event->ID,'rsvpmaker_receipt_'.$rsvp_id,true);
		if(!$receipt_code) {
		  $receipt_code = wp_generate_password(20,false,false);
		  update_post_meta($event->ID,'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
		}	
		$rsvp_receipt_link = add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($event->ID));
		$description = "See receipt ".$rsvp_receipt_link;
	}

	if ( ! empty( $hangout ) ) {

		$description .= 'Google Hangout: ' . $hangout . "\n";
	}

	if(empty($description))
		$description .= 'Event info: ' . get_permalink( $post->ID );

	$summary = $post->post_title;

	$venue_meta = get_post_meta( $post->ID, 'venue', true );

	$venue = ( empty( $venue_meta ) ) ? 'See: ' . get_permalink( $post->ID ) : $venue_meta;

	$dtstamp = gmdate( 'Ymd' ) . 'T' . gmdate( 'His' ) . 'Z';

	$start = gmdate( 'Ymd', $event->ts_start );

	$start_time = gmdate( 'His', $event->ts_start );

	$end = gmdate( 'Ymd', $event->ts_end );

	$end_time = gmdate( 'His', $event->ts_end );

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
	if($from_email)
	$ical[] = 'ORGANIZER;SENT-BY="MAILTO:' . $from_email . '":MAILTO:' . $from_email;
	//$ical[] = 'ORGANIZER;CN=' . get_bloginfo('name') . '":MAILTO:' . $from_email;

	if($rsvp_email)
	$ical[] = 'ATTENDEE;CN=' . $rsvp_email . ';ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;' . 'RSVP=TRUE:mailto:' . $from_email;

	$ical[] = 'UID:' . strtoupper( md5( $event_id ) ) . '@rsvpmaker.com';

	$ical[] = 'SEQUENCE:' . $sequence;

	$ical[] = 'STATUS:' . $status;

	$ical[] = 'DTSTART:' . $start . 'T' . $start_time . 'Z';

	$ical[] = 'DTEND:' . $end . 'T' . $end_time . 'Z';

	$ical[] = 'LOCATION:' . rsvpmaker_ical_escape($venue);

	$ical[] = 'SUMMARY:' . $summary;

	$ical[] = 'DESCRIPTION:' . rsvpmaker_ical_escape($description);

	/*
	$ical[] = 'BEGIN:VALARM';

	$ical[] = 'TRIGGER:-PT15M';

	$ical[] = 'ACTION:DISPLAY';

	$ical[] = 'END:VALARM';
	*/
	
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