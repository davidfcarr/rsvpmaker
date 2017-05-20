<?php

function rsvpmaker_to_ical_email ($post_id = 0, $from_email, $rsvp_email) {
global $post;
global $rsvp_options;
if($post_id > 0)
	$post = get_post($post_id);
global $wpdb;
if(($post->post_type != 'rsvpmaker') )
	return;
$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND post_id=".$post->ID.' ORDER BY meta_value';
$datetime = $wpdb->get_var($sql);
$duration = get_post_meta($post_id,'_'.$datetime, true);
fix_timezone();
$start_ts = strtotime($datetime);
$duration_ts = (empty($duration)) ? strtotime($datetime . ' +1 hour') : strtotime($duration);
$hangout = get_post_meta($post->ID, '_hangout',true);
$description = '';
if(!empty($hangout))
	$description .= "Google Hangout: ".$hangout."\n";
$description .= "Event info: " . get_permalink($post->ID);
$summary = $post->post_title;
$venue = 'See: '. get_permalink($post->ID);

$start = gmdate('Ymd',$start_ts);
$start_time = gmdate('His',$start_ts);
$end = gmdate('Ymd',$duration_ts);
$end_time = gmdate('His',$duration_ts);
$event_id = $post->ID;
$sequence = 0;
$status = 'CONFIRMED';
$ical = "BEGIN:VCALENDAR\r\n";
$ical .= "VERSION:2.0\r\n";
$ical .= "PRODID:-//WordPress//RSVPMaker//EN\r\n";
$ical .= "METHOD:REQUEST\r\n";
$ical .= "BEGIN:VEVENT\r\n";
$ical .= "ORGANIZER;SENT-BY=\"MAILTO:".$from_email."\":MAILTO:".$from_email."\r\n";
$ical .= "ATTENDEE;CN=".$rsvp_email.";ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:".$from_email."\r\n";
$ical .= "UID:".strtoupper(md5($event_id))."-rsvpmaker.com\r\n";
$ical .= "SEQUENCE:".$sequence."\r\n";
$ical .= "STATUS:".$status."\r\n";
$ical .= "DTSTART:".$start."T".$start_time."Z\r\n";
$ical .= "DTEND:".$end."T".$end_time."Z\r\n";
$ical .= "LOCATION:".$venue."\r\n";
$ical .= "SUMMARY:".$summary."\r\n";
$ical .= "DESCRIPTION:".$description."\r\n";
$ical .= "BEGIN:VALARM\r\n";
$ical .= "TRIGGER:-PT15M\r\n";
$ical .= "ACTION:DISPLAY\r\n";
$ical .= "DESCRIPTION:Reminder\r\n";
$ical .= "END:VALARM\r\n";
$ical .= "END:VEVENT\r\n";
$ical .= "END:VCALENDAR\r\n";

return $ical;
}
?>