<?php
function rsvpmaker_to_ical_email( $post_id = 0, $from_email = '', $rsvp_email = '', $description = '', $rsvp_id = 0 ) {
    if ( ! $post_id ) {
        return '';
    }

	if(strpos($description,'>') !== false) {
		$html = $description;
		$description = wp_strip_all_tags($html);
	}

    $event_post = get_post( $post_id );
    if ( ! $event_post || 'rsvpmaker' !== $event_post->post_type ) {
        return '';
    }

    $event = get_rsvpmaker_event( $post_id );
    if ( ! $event ) {
        return '';
    }

    if ( $rsvp_id ) {
        $receipt_code = get_post_meta( $event_post->ID, 'rsvpmaker_receipt_' . $rsvp_id, true );
        if ( ! $receipt_code ) {
            $receipt_code = wp_generate_password( 20, false, false );
            update_post_meta( $event_post->ID, 'rsvpmaker_receipt_' . $rsvp_id, $receipt_code );
        } 
        
        $rsvp_receipt_link = add_query_arg( array(
            'rsvp_receipt' => $rsvp_id,
            'receipt'      => $receipt_code,
            't'            => time()
        ), get_permalink( $event_post->ID ) );

        $description = "See receipt: " . $rsvp_receipt_link;
    }

    if ( empty( $description ) ) {
        $description = 'Event info: ' . get_permalink( $event_post->ID );
    }

    $venue_meta = get_post_meta( $event_post->ID, 'venue', true );
    $venue      = empty( $venue_meta ) ? 'See: ' . get_permalink( $event_post->ID ) : $venue_meta;

    $dtstamp    = gmdate( 'Ymd\THis\Z' );
    $start_time = gmdate( 'Ymd\THis\Z', $event->ts_start );
    $end_time   = gmdate( 'Ymd\THis\Z', $event->ts_end );

    $domain = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'rsvpmaker.com';
    $uid    = strtoupper( md5( $event_post->ID . $start_time ) ) . '@' . $domain;

    $ical = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'CALSCALE:GREGORIAN',
        'PRODID:-//WordPress//RSVPMaker//EN',
        'METHOD:REQUEST',
        'BEGIN:VEVENT',
        'DTSTAMP:' . $dtstamp,
        'UID:' . $uid,
        'SEQUENCE:0',
        'STATUS:CONFIRMED',
        'DTSTART:' . $start_time,
        'DTEND:' . $end_time,
        'LOCATION:' . rsvpmaker_ical_escape( $venue ),
        'SUMMARY:' . rsvpmaker_ical_escape( $event_post->post_title ),
        'DESCRIPTION:' . rsvpmaker_ical_escape( $description ),
    );

	if(!empty($html)) {
		$ical[] = 'X-ALT-DESC;FMTTYPE=text/html:' . rsvpmaker_ical_escape( $html );
	}

    if ( $from_email ) {
        $ical[] = 'ORGANIZER;SENT-BY="MAILTO:' . $from_email . '":MAILTO:' . $from_email;
    }

    if ( $rsvp_email && $from_email ) {
        $ical[] = 'ATTENDEE;CN=' . $rsvp_email . ';ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:' . $from_email;
    }

    $ical[] = 'END:VEVENT';
    $ical[] = 'END:VCALENDAR';

    $ical_string = '';
    foreach ( $ical as $line ) {
        // Multi-byte safe folding should happen AFTER escaping
        $ical_string .= rsvpmaker_fold_ical_line( $line ) . "\r\n";
    }

    return trim( $ical_string );
}

/**
 * Helper function to properly fold lines according to RFC 5545
 * Uses mb_ substr techniques to prevent cutting multi-byte UTF-8 characters in half.
 */
function rsvpmaker_fold_ical_line( $line ) {
    if ( mb_strlen( $line, 'UTF-8' ) <= 75 ) {
        return $line;
    }

    $folded = '';
    while ( mb_strlen( $line, 'UTF-8' ) > 75 ) {
        $folded .= mb_substr( $line, 0, 75, 'UTF-8' ) . "\r\n ";
        $line    = mb_substr( $line, 75, null, 'UTF-8' );
    }
    $folded .= $line;

    return $folded;
}

/**
 * RFC 5545 compliant Text Value escaping helper
 */
function rsvpmaker_ical_escape( $text ) {
    // 1. Strip any existing accidental slash-escapes to avoid double-escaping
    $text = stripslashes( $text );

    // 2. Escape literal Backslashes first
    $text = str_replace( '\\', '\\\\', $text );

    // 3. Escape Newlines (iCal requires a literal "\n" or "\N" string sequence)
    $text = str_replace( array( "\r\n", "\n", "\r" ), '\\n', $text );

    // 4. Escape Commas (Required for text lists/values)
    $text = str_replace( ',', '\,', $text );

    // 5. Escape Double Quotes
    $text = str_replace( '"', '\"', $text );

    return $text;
}