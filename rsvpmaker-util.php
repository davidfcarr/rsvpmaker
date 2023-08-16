<?php
/*
utilities
*/

function get_rsvpmaker_event_table() {
	global $wpdb;
	return $wpdb->prefix . 'rsvpmaker_event';
}

function rsvpmaker_future_event_titles( $refresh = false ) {
	global $wpdb;
	$rsvpmaker_future_event_titles = get_transient('rsvpmaker_future_event_titles');	
	if(empty($rsvpmaker_future_event_titles) || $refresh) {
		$rsvpmaker_future_event_titles = array();
		$event_table = get_rsvpmaker_event_table();
		$sql = "SELECT $event_table.*, $wpdb->posts.post_title FROM $event_table JOIN $wpdb->posts ON $wpdb->posts.ID = $event_table.event WHERE enddate > NOW() ORDER BY date";
		$results = $wpdb->get_results($sql);
		foreach($results as $row)
		{
			$event = (int) $row->event;
			$rsvpmaker_future_event_titles[$event] = $row;
		}
		set_transient('rsvpmaker_future_event_titles',$rsvpmaker_future_event_titles);	
	}
	return $rsvpmaker_future_event_titles;
}

if(!function_exists('get_rsvpmaker_timestamp')) {
	function get_rsvpmaker_timestamp( $post_id ) {
		$event = get_rsvpmaker_event($post_id);
		return intval($event->ts_start);
	}	
}

function add_rsvpmaker_event($post_id,$date,$enddate='',$display_type='',$tz='') {
	global $rsvp_options, $wpdb;
	$ts_start = rsvpmaker_strtotime($date);
	if($enddate)
	{
		$ts_end = rsvpmaker_strtotime($enddate);
	}
	else {
		$ts_end = $time + HOUR_IN_SECONDS;
		$enddate = rsvpmaker_date('Y-m-d H:i:s',$ts_end);
	}
	if(!$tz)
		$tz = get_option('timezone_string');
	if($wpdb->get_var('SELECT event from '.$wpdb->prefix."rsvpmaker_event where event=$post_id"))
		$sql = $wpdb->prepare("update ".$wpdb->prefix."rsvpmaker_event SET ts_start=%d, ts_end=%d, date=%s, enddate=%s, display_type='', timezone=%s where event=%d",$ts_start,$ts_end,$date,$enddate,$tz,$post_id);
	else
		$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."rsvpmaker_event SET event=%d, ts_start=%d, ts_end=%d, date=%s, enddate=%s, display_type='', timezone=%s ",$post_id,$ts_start,$ts_end,$date,$enddate,$tz);
	$wpdb->query($sql);
}

add_action('save_post','add_rsvpmaker_new_event_defaults',12,3);
//from wp_insert_post action
function add_rsvpmaker_new_event_defaults($post_id,$post,$is_update) {
	global $rsvp_options,$wpdb;
	if(($post->post_status != 'auto-draft') || $is_update || ($post->post_type != 'rsvpmaker' && $post->post_type != 'rsvpmaker_template') )
		return;
	if($post->post_type == 'rsvpmaker_template')
	{
		rsvpmaker_defaults_for_post($post_id);
		return;
	}
	if(isset($_GET['t']))
	{
	$t = intval($_GET['t']);
	add_post_meta($post_id,'_meet_recur',$t);
	rsvpmaker_copy_metadata($t, $post_id);
	//alter this for template
	$mins = ($rsvp_options['defaultmin'] > 10) ? $rsvp_options['defaultmin'] : '00';
	$tstring = $rsvp_options['defaulthour'].':'.$mins.':00';
	$time = rsvpmaker_strtotime($tstring);
	$endtime = $time + HOUR_IN_SECONDS;
	$tz = get_option('timezone_string');
	$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."rsvpmaker_event SET event=%d, ts_start=%d, ts_end=%d, date=%s, enddate=%s, display_type='', timezone=%s ",$post_id,$time,$endtime,rsvpmaker_date('Y-m-d H:i:s',$time),rsvpmaker_date('Y-m-d H:i:s',$endtime),$tz);
	$wpdb->query($sql);
}
else
{
	$mins = ($rsvp_options['defaultmin'] > 10) ? $rsvp_options['defaultmin'] : '00';
	$tstring = $rsvp_options['defaulthour'].':'.$mins.':00';
	$time = rsvpmaker_strtotime($tstring);
	$endtime = $time + HOUR_IN_SECONDS;
	$tz = get_option('timezone_string');
	$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."rsvpmaker_event SET event=%d, ts_start=%d, ts_end=%d, date=%s, enddate=%s, display_type='', timezone=%s ",$post_id,$time,$endtime,rsvpmaker_date('Y-m-d H:i:s',$time),rsvpmaker_date('Y-m-d H:i:s',$endtime),$tz);
	
	$wpdb->query($sql);
	rsvpmaker_defaults_for_post($post_id);	
}
}

function rsvp_default_content_from_template($content) {
	if(isset($_GET['t'])) {
		$t = intval($_GET['t']);
		$tpl = get_post($t);
		$content = $tpl->post_content;
	}
	return $content;
} 
function rsvp_default_title_from_template($content) {
	if(isset($_GET['t'])) {
		$t = intval($_GET['t']);
		$tpl = get_post($t);
		$content = $tpl->post_title;
	}
	return $content;
} 

add_filter('default_title','rsvp_default_title_from_template');
add_filter('default_content','rsvp_default_content_from_template');

function rsvpmaker_add_event_row ($post_id, $date, $end, $type, $timezone = '', $post_title = '') {
	global $wpdb, $post;
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	if(empty($timezone))
		$timezone = wp_timezone_string();
	date_default_timezone_set( $timezone );
	$ts_start = strtotime($date);
	if(strpos($end,'-'))
		$enddate = $end;
	else
		$enddate = rsvpmaker_make_end_date ($date,$type,$end);
	printf('<p>%s to %s</p>',$end,$enddate);
	$ts_end = strtotime($enddate);
	$sql = $wpdb->prepare("INSERT INTO $event_table SET display_type=%s, date=%s, enddate=%s, ts_start=%d, ts_end=%d, timezone=%s, event=%s",$type,$date,$enddate,$ts_start,$ts_end,$timezone, $post_id);
	if(empty($post_title))
		$post_title = get_the_title($post_id);
	$sql .= ", post_title='".addslashes($post_title)."'";
	$wpdb->query($sql);
	return (object) array('event' => $post_id, 'display_type' => $type, 'date' => $date,'enddate' => $enddate, 'ts_start' => $ts_start, 'ts_end' => $ts_end, 'timezone' => $timezone,'justupdated' => true);
}

//legacy data update
function rsvpmaker_update_event_row ($post_id) {
	global $wpdb, $post, $rsvp_options;
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	$date = get_post_meta($post_id,'_rsvp_dates',true);
	if(empty($date))
		return;//don't mess with non dated events
	delete_transient('rsvp_meta_cache');
	$post = get_post($post_id);
	$timezone = rsvpmaker_get_timezone_string($post_id);
	$type = get_post_meta($post_id,'_firsttime',true);
	$date = get_post_meta($post_id,'_rsvp_dates',true);
	$enddate = get_post_meta($post_id,'_rsvp_end_date',true);
	if(empty($enddate)) {
	$end = get_post_meta($post_id,'_endfirsttime',true);
	$enddate = rsvpmaker_end_date($date,$type,$end);
	update_post_meta($post_id,'_rsvp_end_date',$enddate);
	}
	$ts_start = rsvpmaker_strtotime($date);
	$ts_end = rsvpmaker_strtotime($enddate);
	$event = $wpdb->get_row( "SELECT * FROM $event_table WHERE event=$post_id" );
	if($event)
		$sql = $wpdb->prepare("UPDATE $event_table SET post_title=%s, display_type=%s, date=%s, enddate=%s, ts_start=%d, ts_end=%d, timezone=%s WHERE event=%d ",$post->post_title,$type,$date,$enddate,$ts_start,$ts_end,$timezone,$post_id);
	else
		$sql = $wpdb->prepare("INSERT INTO $event_table SET post_title=%s, display_type=%s, date=%s, enddate=%s, ts_start=%d, ts_end=%d, timezone=%s, event=%d",$post->post_title,$type,$date,$enddate,$ts_start,$ts_end,$timezone, $post_id);
	$wpdb->query($sql);
	return (object) array('event' => $post_id, 'post_title'=> $post->post_title,'display_type' => $type, 'date' => $date,'enddate' => $enddate, 'ts_start' => $ts_start, 'ts_end' => $ts_end, 'timezone' => $timezone,'justupdated' => true);
}

function rsvpmaker_update_start_time ($post_id, $date) {
	global $wpdb, $post;
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	$event = get_rsvpmaker_event($post_id);
	$post = get_post($post_id);
	$diff = $event->ts_end - $event->ts_start;
	if($diff <= 0)
		$diff = HOUR_IN_SECONDS;
	$ts_start = rsvpmaker_strtotime($date);
	$ts_end = $ts_start + $diff;
	$enddate = rsvpmaker_date('Y-m-d H:i:s',$ts_end);
	$sql = $wpdb->prepare("update $event_table SET display_type=%s, date=%s, enddate=%s, ts_start=%d, ts_end=%d, timezone=%s WHERE event=%s",$type,$date,$enddate,$ts_start,$ts_end,$timezone, $post_id);
	$wpdb->query($sql);
}

function rsvpmaker_update_event_field ($event_post_id, $field, $value) {
	global $wpdb, $post, $rsvp_options;
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	if('date' == $field) {
		$date = $value;
		$ts_start = rsvpmaker_strtotime($date);
		$sql = $wpdb->prepare("UPDATE $event_table SET date=%s, ts_start=%d WHERE event=%d ",$date,$ts_start,$event_post_id);
	}
	elseif('enddate' == $field) {
		$date = $wpdb->get_var("SELECT date from $event_table WHERE event=$event_post_id");
		$enddate = $value;
		$ts_start = rsvpmaker_strtotime($date);
		$ts_end = rsvpmaker_strtotime($enddate);
		$sql = $wpdb->prepare("UPDATE $event_table SET enddate=%s, ts_end=%d WHERE event=%d ",$enddate,$ts_end,$event_post_id);
	}
	else {
		$sql = $wpdb->prepare("UPDATE $event_table SET $field=%s WHERE event=%d ",$value,$event_post_id);
	}
	$wpdb->query($sql);
}

function get_rsvpmaker_event( $post_id ) {
	if ( empty( $post_id ) ) {
		return;
	}

	global $wpdb, $rsvpdates;
	$wpdb->show_errors();
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker_event WHERE event=' . intval( $post_id );
	$row = $wpdb->get_row( $sql );
	if(!$row)
		return;
	$row->ts_start = intval($row->ts_start);
	$row->ts_end = intval($row->ts_end);
	//sanity check
	if($row->ts_start > $row->ts_end) {
		$row->ts_end = intval($row->ts_start) + HOUR_IN_SECONDS;
		if(empty($row->timezone))
			$row->timezone = wp_timezone_string();
		$row->enddate = rsvpmaker_date('Y-m-d H:i:s',$row->ts_end,$row->timezone );
		$sql = $wpdb->prepare('UPDATE '.$wpdb->prefix . "rsvpmaker_event SET ts_end=%d, enddate=%s, timezone=%s WHERE event=" . intval( $post_id ),$row->ts_end,$row->enddate,$row->timezone);
		$wpdb->query($sql);
	}
	if(is_single())
	{
		if(empty($row->ts_start) && !empty($row->date))
		{
			$row->ts_start = rsvpmaker_strtotime($row->date);
			$sql = 'UPDATE '.$wpdb->prefix . "rsvpmaker_event SET ts_start=$row->ts_start WHERE event=" . intval( $post_id );
			$wpdb->query($sql);
		}
	if(empty($row->ts_end) && !empty($row->enddate))
		{
			$row->ts_end = rsvpmaker_strtotime($row->enddate);
			$sql = 'UPDATE '.$wpdb->prefix . "rsvpmaker_event SET ts_end=$row->ts_end WHERE event=" . intval( $post_id );
			$wpdb->query($sql);
		}
	}
	return $row;
}

add_shortcode('rsvpmaker_future_event_titles','rsvpmaker_future_event_titles_test');

function rsvpmaker_future_event_titles_test () {
	$events = rsvpmaker_future_event_titles(true);
	$output = '';
	foreach($events as $event) {
		$output .= sprintf('<p>%s %s</p>',$event->post_title, $event->date);
	}
	return $output;
}

function get_rsvp_event_time( $post_id ) {
	$times = get_rsvp_event_times(  );
	if(isset($times[$post_id]))
		return $times[$post_id];
	$times = get_past_rsvp_event_times();
	if(isset($times[$post_id]))
		return $times[$post_id];
	$times = get_rsvp_event_times(true);
	if(isset($times[$post_id]))
		return $times[$post_id];
}

function get_rsvp_event_times( $refresh = false ) {
	global $rsvp_event_timestamps, $wpdb;
	if(empty($rsvp_event_timestamps) && !$refresh) {
		$rsvp_event_timestamps = get_transient('rsvp_event_timestamps');	
	}	
	if(empty($rsvp_event_timestamps) || $refresh) {
		$event_table = get_rsvpmaker_event_table();
		$sql = "SELECT event, ts_start FROM $event_table WHERE date > NOW() ORDER BY date";
		$results = $wpdb->get_results($sql);
		foreach($results as $row)
			$rsvp_event_timestamps[$row->event] = (int) $row->ts_start;
		set_transient('rsvp_event_timestamps',$rsvp_event_timestamps);	
	}	
	return $rsvp_event_timestamps;
}

function get_past_rsvp_event_times( ) {
	global $wpdb;
	$rsvp_past_event_timestamps = get_transient('rsvp_past_event_timestamps');	
	if(empty($rsvp_event_timestamps)) {
		$event_table = get_rsvpmaker_event_table();
		$sql = "SELECT * FROM $event_table WHERE date <= NOW() ORDER BY date";
		$results = $wpdb->get_results($sql);
		foreach($results as $row)
			$rsvp_past_event_timestamps[$row->event] = $row->ts_start;
		set_transient('rsvp_past_event_timestamps',$rsvp_past_event_timestamps);	
	}	
	return $rsvp_past_event_timestamps;
}

function rsvpmaker_create_nonce() {
	global $rsvpmaker_nonce;
	$rsvpmaker_nonce = array('field' => 'timelord','key' => 'galifrey','value' => wp_create_nonce('galifrey'));
}

function rsvpmaker_nonce($mode = 'echo'){
	global $rsvpmaker_nonce;
	if($mode == 'value')
		return $rsvpmaker_nonce['value'];
	if($mode == 'query')
		return $rsvpmaker_nonce['field'].'='.$rsvpmaker_nonce['value'];
	$output = sprintf('<input type="hidden" name="%s" class="%s" value="%s" />',esc_attr($rsvpmaker_nonce['field']),esc_attr($rsvpmaker_nonce['field']),esc_attr($rsvpmaker_nonce['value']));
	if($mode == 'echo')
		echo $output;
	else
		return $output;
}

function rsvpmaker_verify_nonce() {
	return wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'));
}

function rsvpmaker_nonce_data($mode = 'key'){
	global $rsvpmaker_nonce;
	if(($mode == 'key') && isset($rsvpmaker_nonce['key']))
		return $rsvpmaker_nonce['key'];
	if(empty($_REQUEST['timelord']))
		return false;
	return sanitize_text_field($_REQUEST['timelord']);
}

function rsvpmaker_get_timezone_string( $post_id = 0 ) {
	global $post;
	if ( ! $post_id && ! empty( $post->ID ) ) {
		$post_id = $post->ID;
	}
	$timezone = wp_timezone_string();
	if ( $post_id ) {
		$event = get_rsvpmaker_event($post_id);
		if ( ! empty( $event ) ) {
			$timezone = $event->timezone;
		}
	}
	if(strpos($timezone,'/') || strpos($timezone,'TC'))
		return $timezone;
	else
		return 'UTC';
}

add_action('rsvpmaker_add_timestamps','rsvpmaker_add_timestamps');
function rsvpmaker_add_timestamps() {
	global $default_tz,$wpdb;
	$last_tz = '';
	$sql     = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker_event WHERE ts_start=0 LIMIT 0,100';
	$list    = $wpdb->get_results( $sql );
	if ( $list ) {
		foreach ( $list as $event ) {
			$timezone = rsvpmaker_get_timezone_string( $event->event );
			if ( $timezone != $last_tz ) {
				date_default_timezone_set( $timezone );
				$last_tz = $timezone;
			}
			$t   = strtotime( $event->date );
			$end = strtotime( $event->enddate );
			$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET ts_start=%d, ts_end=%d, timezone=%s WHERE event=%d', $t, $end, $timezone, $event->event );
			$wpdb->query( $sql );
		}
	}
	if ( $last_tz != $default_tz ) {
		date_default_timezone_set( $default_tz );
	}
}

// temporary shim for name change
function fix_timezone () {
	rsvpmaker_fix_timezone();
}

function rsvpmaker_fix_timezone( $timezone = '' ) {

	global $post;

	if ( empty( $timezone ) ) {

		$timezone = get_option( 'timezone_string' );
	}

	if ( isset( $post->ID ) ) {

		$post_tz = get_post_meta( $post->ID, '_rsvp_timezone_string', true );

		if ( ! empty( $post_tz ) && $post_tz != $timezone ) {

			$timezone = $post_tz;
		}
	}

	if ( ! empty( $timezone ) ) {
		date_default_timezone_set( $timezone );
	}
}

function rsvpmaker_restore_timezone() {

	global $default_tz;

	date_default_timezone_set( $default_tz );

}

function rsvpmaker_strtotime( $string ) {

	$string = str_replace( '::', ':', $string );

	rsvpmaker_fix_timezone();

	$t = strtotime( $string );

	rsvpmaker_restore_timezone();

	return $t;

}

function rsvpmaker_mktime( $hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null ) {
	rsvpmaker_fix_timezone();
	$t = mktime( (int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day, (int) $year );
	rsvpmaker_restore_timezone();
	return $t;
}

function rsvpmaker_strftime( $date_format = '', $t = null ) {
	return rsvpmaker_date( $date_format, $t );
}

function rsvpmaker_long_date( $post_id, $and_time = false, $end_time = false ) {
	global $rsvp_options, $wpdb;
	if ( ! strpos( $rsvp_options['time_format'], 'T' ) && get_post_meta( $post_id, '_add_timezone', true ) ) {
		$rsvp_options['time_format'] .= ' T';
	}
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	$sql         = "SELECT * FROM $event_table where event=$post_id";
	$timerow     = $wpdb->get_row( $sql );
	if ( empty( $timerow ) ) {
		return;
	}
	if ( ! $timerow->display_type || ( $timerow->display_type == 'allday' ) ) {
		$end_time = false;
	}
	if ( $timerow->display_type == 'allday' ) {
		$and_time = false;
	}

	$output          = $start_date = rsvpmaker_date( $rsvp_options['long_date'], (int) $timerow->ts_start );
	if($and_time) {
		$time_format = $rsvp_options['time_format'];
		$time        = rsvpmaker_date( $time_format, (int) $timerow->ts_start );
		$output     .= ' ' . $time;
	}
	if ( $end_time && ! empty( $timerow->ts_end ) ) {
		$end_date = rsvpmaker_date( $rsvp_options['long_date'], (int) $timerow->ts_end );
		if ( ( $end_date != $start_date ) || $and_time ) {
			$output .= ' ' . __( 'to', 'rsvpmaker' ) . ' ';
		}
		if ( $end_date != $start_date ) {
			$output .= $end_date . ' ';
		}
		if ( $and_time ) {
			$time    = rsvpmaker_date( $time_format, (int) $timerow->ts_end );
			$output .= ' ' . $time;
		}
	}
	return $output;
}

function rsvpmaker_short_date( $post_id, $and_time = false, $end_time = false ) {
	global $rsvp_options, $wpdb;
	if ( ! strpos( $rsvp_options['time_format'], 'T' ) && get_post_meta( $post_id, '_add_timezone', true ) ) {
		$rsvp_options['time_format'] .= ' T';
	}
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	$sql         = "SELECT * FROM $event_table where event=$post_id";
	$timerow     = $wpdb->get_row( $sql );
	if ( ! $timerow->display_type || ( $timerow->display_type == 'allday' ) ) {
		$end_time = false;
	}
	if ( $timerow->display_type == 'allday' ) {
		$and_time = false;
	}
	if ( empty( $timerow ) ) {
		return;
	}
	rsvpmaker_fix_timezone();
	$output = $start_date = wp_date( $rsvp_options['short_date'], $timerow->ts_start );
	if ( $and_time ) {
		$time_format = $rsvp_options['time_format'];
		$time        = wp_date( $time_format, $timerow->ts_start );
		$output     .= ' ' . $time;
	}
	if ( $end_time ) {
		$end_date = wp_date( $rsvp_options['short_date'], $timerow->ts_end );
		if ( ( $end_date != $start_date ) || $and_time ) {
			$output .= ' ' . __( 'to', 'rsvpmaker' ) . ' ';
		}
		if ( $end_date != $start_date ) {
			$output .= $end_date . ' ';
		}
		if ( $and_time ) {
			$time    = wp_date( $time_format, $timerow->ts_end );
			$output .= ' ' . $time;
		}
	}
	rsvpmaker_restore_timezone();
	return $output;
}

function rsvpmaker_end_date( $datetime, $type, $end_time ) {
	global $rsvp_options, $wpdb;
	$p = explode(' ',$datetime);
	$date = $p[0];
	if(strpos($type,'|')) {
		$multi = explode('|',$type);
		$add = $multi[1] - 1;
		$date = rsvpmaker_date('Y-m-d',rsvpmaker_strtotime("$date +$add days"));
	}
	return $date . ' '.$end_time; 
}

function rsvpmaker_time_format( $post_id, $end_time = false ) {
	global $rsvp_options, $wpdb;
	if ( ! strpos( $rsvp_options['time_format'], 'T' ) && get_post_meta( $post_id, '_add_timezone', true ) ) {
		$rsvp_options['time_format'] .= ' T';
	}
	$event_table = $wpdb->prefix . 'rsvpmaker_event';
	$sql         = "SELECT * FROM $event_table where event=$post_id";
	$timerow     = $wpdb->get_row( $sql );
	if ( empty( $timerow ) ) {
		return;
	}
	rsvpmaker_fix_timezone();
	$t           = ( $end_time ) ? $timerow->ts_end : $timerow->ts_start;
	$time_format = $rsvp_options['time_format'];
	$time        = wp_date( $time_format, $timerow->ts_start );
	rsvpmaker_restore_timezone();
	return $time;
}

function rsvpmaker_timestamp_to_time( $t, $add_tz = false ) {
	global $rsvp_options, $wpdb, $post;
	if ( ! strpos( $rsvp_options['time_format'], 'T' ) && ( $add_tz || get_post_meta( $post->ID, '_add_timezone', true ) ) ) {
		$rsvp_options['time_format'] .= ' T';
	}
	return rsvpmaker_date( $rsvp_options['time_format'], $t );
}

function rsvpmaker_date( $date_format = '', $t = 0, $tzstring = '') {
	global $post;
	$t = intval($t);
	if ( strpos( $date_format, '%' ) !== false ) {
		$date_format = strftime_format_to_date_format( $date_format );
	}

	$post_id  = ( empty( $post->ID ) ) ? 0 : $post->ID;
	if(empty($tzstring))
		$tzstring = rsvpmaker_get_timezone_string( $post_id );
	$tz       = new DateTimeZone( $tzstring );

	if ( empty( $date_format ) ) {
		$date_format = 'F jS, Y g:i A T';
	}
	if ( empty( $t ) ) {
		$t = time();
	}
	if ( ! is_int( $t ) ) {
		$t = rsvpmaker_strtotime( $t );
	}

	$output = wp_date( $date_format, $t, $tz );
	return $output;
}

function rsvpmaker_prettydate($t, $type = 'long_date') {
	global $rsvp_options;
	$format = (isset($rsvp_options[$type])) ? $rsvp_options[$type] : $rsvp_options['long_date'];
	return rsvpmaker_date($format,intval($t));
}

function rsvpmaker_date_test() {
	return rsvpmaker_date();
}

add_shortcode( 'rsvpmaker_date_test', 'rsvpmaker_date_test' );

function get_sql_now() {

	$date = rsvpmaker_date( 'Y-m-d H:i:s' );

	return $date;

}

function get_sql_curdate() {

	$date = rsvpmaker_date( 'Y-m-d 00:00:00' );

	return $date;

}

function get_rsvp_date( $post_id, $format = '' ) {
	global $wpdb, $rsvpdates, $rsvp_options;
	if ( empty( $post_id ) ) {
		return;
	}
	if ( empty( $rsvpdates ) ) {
		cache_rsvp_dates( 50 );
	}

	if ( ! empty( $rsvpdates[ $post_id ] ) ) {
		if(empty($format))
			return $rsvpdates[ $post_id ][0];
		else
			return rsvpmaker_date($rsvp_options[$format],rsvpmaker_strtotime($rsvpdates[ $post_id ][0]));
	}

	$wpdb->show_errors();

	$sql  = 'SELECT date FROM ' . $wpdb->prefix . 'rsvpmaker_event WHERE event=' . intval( $post_id );
	$date = $wpdb->get_var( $sql );
	if ( $date == '0000-00-00 00:00:00' ) {
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'rsvpmaker_event WHERE event=' . intval( $post_id ) );
		return;
	}
	if(empty($format))
		return $date;
	else
		return rsvpmaker_date($rsvp_options[$format],rsvpmaker_strtotime($date));
}

function rsvpmaker_duration_select( $slug, $datevar = array(), $start_time = '', $index = 0 ) {

	global $rsvp_options;

	if ( empty( $datevar ) ) {

		$datevar = array( 'duration' => '' );
	}

	if ( ! empty( $datevar['duration'] ) && is_array( $datevar['duration'] ) ) {

		$duration_type = $datevar['duration'][ $index ];

	} elseif ( ! empty( $datevar['duration'] ) ) {

		$duration_type = $datevar['duration'];

	} else {
		$duration_type = '';
	}

	$end_time = '';

	if ( ! empty( $datevar['end_time'] ) ) {

		$end_time = ( is_array( $datevar['end_time'] ) ) ? $datevar['end_time'][ $index ] : $datevar['end_time'];
	} elseif ( ! empty( $datevar['end'] ) ) {

		$end_time = ( is_array( $datevar['end'] ) ) ? $datevar['end'][ $index ] : $datevar['end'];

	}

	echo '<p><label>' . __( 'End Time', 'rsvpmaker' ) . '</label> <select id="end_time_type" name="end_time_type" class="end_time_type" >';
	?>
<option value=""><?php echo __( 'Not set (optional)', 'rsvpmaker' ); ?></option>

<option value="set" 
	<?php
	if ( $duration_type == 'set' ) {
		echo ' selected="selected" ';}
	?>
 ><?php echo __( 'Set end time', 'rsvpmaker' ); ?></option>

<option value="allday" 
	<?php
	if ( $duration_type == 'allday' ) {
		echo ' selected="selected" ';}
	?>
><?php echo __( 'All day/time not shown', 'rsvpmaker' ); ?></option>
<?php
	echo '</select>';
	echo '</p>';
}

function rsvpmaker_duration_select_2021( $duration_type ) {

	echo '<p><label>' . __( 'End Time', 'rsvpmaker' ) . '</label> <select id="end_time_type" name="end_time_type" class="end_time_type" >';
	?>
<option value=""><?php echo __( 'Not set (optional)', 'rsvpmaker' ); ?></option>

<option value="set" 
	<?php
	if ( $duration_type == 'set' ) {
		echo ' selected="selected" ';}
	?>
 ><?php echo __( 'Set end time', 'rsvpmaker' ); ?></option>

<option value="allday" 
	<?php
	if ( $duration_type == 'allday' ) {
		echo ' selected="selected" ';}
	?>
><?php echo __( 'All day/time not shown', 'rsvpmaker' ); ?></option>

	<?php
	for ( $i = 2; $i < 8; $i++ ) {
		$multi = 'multi|' . $i;
		$s     = ( $duration_type == $multi ) ? ' selected="selected" ' : '';
		printf( '<option value="%s" %s>%s</option>', $multi, $s, $i . ' ' . __( 'days/time not shown', 'rsvpmaker' ) );
	}

	echo '</select>';
	echo '</p>';
}

//override legacy function
function get_rsvp_dates( $post_id, $obj = false ) {
	$event = get_rsvpmaker_event($post_id);
	if(!$event)
		return [];
	return array(array('datetime'=>$event->date,'end_time'=>$event->enddate,'dur'=>$event->display_type,'duration'=>$event->display_type));
}

function get_rsvp_event( $where = '', $output = OBJECT ) {
	global $wpdb;

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date

	 FROM " . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 WHERE (post_status='publish' OR post_status='draft') ";

	if ( empty( $where ) ) {

		$where = " a1.meta_value > '" . get_sql_curdate() . "' ";

	} else {
		$where = str_replace( 'datetime', 'a1.meta_value', $where );
	}

	$sql .= ' AND ' . $where . ' ';

	$sql .= ' ORDER BY a1.meta_value ';

	return $wpdb->get_row( $sql );
}



function get_events_rsvp_on( $limit = 0 ) {

	global $wpdb;

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template

	 FROM " . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 JOIN " . $wpdb->postmeta . ' a2 ON ' . $wpdb->posts . ".ID =a2.post_id AND a2.meta_key='_rsvp_on' AND a2.meta_value=1 

	 WHERE a1.meta_value > '" . get_sql_curdate() . "' AND post_status='publish'

	 ORDER BY a1.meta_value ASC ";

	if ( $limit ) {

		$sql .= ' LIMIT 0,' . $limit;
	}

	$wpdb->show_errors();

	return $wpdb->get_results( $sql );

}

function is_rsvpmaker_deadline_future( $post_id ) {
	global $post;
	if('rsvpmaker_template' == $post->post_type)
		return true;
	if('rsvpmaker' != $post->post_type)
		return false;
	$deadline = (int) get_post_meta( $post_id, '_rsvp_deadline', true );
	$event = get_rsvpmaker_event( $post_id );
	if(!is_object($event))
		return false;
	$start = (int) $event->ts_start;
	$end = (int) $event->ts_end;
	if ( ! $deadline  ) {
		$deadline = $end;
	}
	elseif($deadline < $start) {
		$diff = $start - $deadline;
		if($diff > YEAR_IN_SECONDS){
			//must to be an error
			$deadline = (int) $event->ts_end;
		} 
	}
	//rsvpmaker_debug_log($deadline .':'. rsvpmaker_date('r',$deadline),'deadline');
	return $deadline > time();
}

function get_next_rsvp_on() {
	$events = get_future_events_by_meta(
		array(
			'meta_key'   => '_rsvp_on',
			'meta_value' => 1,
		),
		1
	);
	return $events[0];
}

function get_events_by_template( $template_id, $order = 'ASC', $output = OBJECT ) {

	// return rsvpmaker_upcoming_data(array('meta_key' => '_meet_recur', 'meta_value' => $template_id));

	global $wpdb;

	$event_table = get_rsvpmaker_event_table();
	$sql = "SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_status, $wpdb->posts.post_author, $wpdb->posts.post_modified, $wpdb->posts.ID as postID, $event_table.*, $event_table.date as datetime FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id JOIN $event_table ON $event_table.event = $wpdb->posts.ID WHERE date > '" . get_sql_curdate() . "' AND post_status='publish' AND meta_key='_meet_recur' AND meta_value=$template_id ORDER BY date " . $order.", post_modified DESC";
	$wpdb->show_errors();

	return $wpdb->get_results( $sql, $output );

}

function rsvpmaker_next_by_template( $template_id, $order = 'ASC', $output = OBJECT ) {

	global $wpdb;

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template

	 FROM " . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 JOIN " . $wpdb->postmeta . ' a2 ON ' . $wpdb->posts . ".ID =a2.post_id AND a2.meta_key='_meet_recur' AND a2.meta_value=" . $template_id . " 

	 WHERE a1.meta_value > '" . get_sql_curdate() . "' AND (post_status='publish' OR post_status='draft')

	 ORDER BY a1.meta_value " . $order;

	$wpdb->show_errors();

	return $wpdb->get_row( $sql, $output );

}

function rsvpmaker_set_template_defaults( $post_id ) {
	global $rsvp_options;
	update_post_meta( $post_id, '_sked_Varies', 1 );
	update_post_meta( $post_id, '_sked_First', '' );
	update_post_meta( $post_id, '_sked_Second', '' );
	update_post_meta( $post_id, '_sked_Third', '' );
	update_post_meta( $post_id, '_sked_Fourth', '' );
	update_post_meta( $post_id, '_sked_Last', '' );
	update_post_meta( $post_id, '_sked_Every', '' );
	update_post_meta( $post_id, '_sked_Sunday', '' );
	update_post_meta( $post_id, '_sked_Monday', '' );
	update_post_meta( $post_id, '_sked_Tuesday', '' );
	update_post_meta( $post_id, '_sked_Wednesday', '' );
	update_post_meta( $post_id, '_sked_Thursday', '' );
	update_post_meta( $post_id, '_sked_Friday', '' );
	update_post_meta( $post_id, '_sked_Saturday', '' );
	update_post_meta( $post_id, '_sked_hour', str_pad( $rsvp_options['defaulthour'], 2, '0', STR_PAD_LEFT ) );
	update_post_meta( $post_id, '_sked_minutes', str_pad( $rsvp_options['defaultmin'], 2, '0', STR_PAD_LEFT ) );
	update_post_meta( $post_id, '_sked_stop', '' );
	update_post_meta( $post_id, '_sked_duration', '' );
}

function rsvpmaker_get_templates( $criteria = '', $include_drafts = false ) {
	global $wpdb;
	$templates = array();
	$status_sql = ($include_drafts) ? "(post_status='publish' OR post_status='draft')" : "post_status='publish'";
	$sql       = "SELECT $wpdb->posts.*, meta_value as sked FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE post_type='rsvpmaker_template' AND `meta_key` REGEXP '_sked_[A-Z].+' and meta_value AND $status_sql $criteria GROUP BY $wpdb->posts.ID ORDER BY post_title";
	$results   = $wpdb->get_results( $sql );
	foreach ( $results as $template ) {
		$templates[ $template->ID ] = $template;
		delete_post_meta( $template->ID, '_rsvp_dates' );
		delete_post_meta( $template->ID, '_meet_recur' );
	}
	delete_transient( 'rsvpmakerdates' );// clear date cache
	return $templates;
}

function get_next_rsvpmaker() {
	$events = get_future_events( '', 1 );
	return $events[0];
}

function get_events_by_author( $author, $limit = '', $status = '' ) {

	global $wpdb;

	$wpdb->show_errors();

	if ( $status == 'publish' ) {

		$status_sql = " AND post_status='publish' ";

	} else {
		$status_sql = " AND ($wpdb->posts.post_status='publish' OR $wpdb->posts.post_status='draft') ";
	}

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date

	 FROM " . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 WHERE $wpdb->posts.post_author=$author AND a1.meta_value > '" . get_sql_now() . "' " . $status_sql;

	$sql .= ' ORDER BY a1.meta_value ';

	if ( ! empty( $limit ) ) {

		$sql .= ' LIMIT 0,' . $limit . ' ';
	}

	return $wpdb->get_results( $sql );

}



function rsvpmaker_week_of_events() {

	global $wpdb;

	$wpdb->show_errors();

	$startfrom = '"' . get_sql_now() . '"';

	$enddate = ' DATE_ADD(NOW(),INTERVAL 1 WEEK) ';

		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date

		 FROM " . $wpdb->posts . '

		 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

		 WHERE a1.meta_value > " . $startfrom . " AND a1.meta_value < $enddate AND post_status='publish' ";

		$sql .= ' ORDER BY a1.meta_value ';

		return $wpdb->get_results( $sql );

}



function rsvpmaker_week_reminders() {

	global $wpdb;

	$wpdb->show_errors();

	$startfrom = '"' . get_sql_now() . '"';

	$enddate = ' DATE_ADD(NOW(),INTERVAL 1 WEEK) ';

		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, a1.meta_value as datetime,

		a2.meta_key as slug, a2.meta_value as reminder_post_id

		 FROM " . $wpdb->posts . '

		 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

		 JOIN " . $wpdb->postmeta . ' a2 ON ' . $wpdb->posts . ".ID =a2.post_id AND a2.meta_key LIKE '_rsvp_reminder_msg_%'

		 WHERE a1.meta_value > " . $startfrom . " AND a1.meta_value < $enddate AND post_status='publish' ";

		$sql .= ' ORDER BY a1.meta_value ';

		return $wpdb->get_results( $sql );

}

function rsvpmaker_reminders_nudge() {

	$posts_with_reminders = rsvpmaker_week_reminders();

	if ( $posts_with_reminders ) {

		foreach ( $posts_with_reminders as $post_with_reminder ) {

			$parts = explode( '_', $post_with_reminder->slug );

			$hours = $parts[4];

			if ( isset( $_GET['debug'] ) ) {

				printf( '<div>%s - %s - %s</div>', $hours, $post_with_reminder->datetime, $post_with_reminder->ID );
			}

			rsvpmaker_reminder_cron( $hours, $post_with_reminder->datetime, $post_with_reminder->ID, $hours, $post_with_reminder->datetime );

		}
	}

}

add_action( 'rsvpmaker_update_table_continue', 'rsvpmaker_update_table_continue' );

function rsvpmaker_update_table_continue() {
	rsvpmaker_event_dates_table_update( );
}

function rsvpmaker_event_dates_table_update( $new = false ) {
	global $wpdb;
	if($new)
		{
		$last_date = '';
		delete_option('rsvpmaker_update_last_date');
		}
	else
		$last_date = get_option( 'rsvpmaker_update_last_date' );
	$where     = ( $last_date ) ? " AND a1.meta_value <= '" . $last_date . "'" : '';
	$sql       = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime
	 FROM " . $wpdb->posts . '
	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates' AND post_status='publish' $where ORDER BY a1.meta_value DESC LIMIT 0, 100";
	 //rsvpmaker_debug_log( $sql, 'event lookup' );
	$events    = $wpdb->get_results( $sql );
	$log       = '';
	$last_date = '';// remove for now
	if ( $events ) {
		foreach ( $events as $event ) {
			$date = $event->datetime;
			$type = get_post_meta( $event->ID, '_firsttime', true );
			$end  = get_post_meta( $event->ID, '_endfirsttime', true );
			if ( empty( $end ) ) {
				$enddatetime = date( 'Y-m-d H:i:s', strtotime( $date . ' +1 hour' ) );
			} elseif ( strpos( $type, '|' ) ) {
				$p           = explode( '|', $type );
				$days        = $p[1] - 1;
				$enddatetime = date( 'Y-m-d ', strtotime( $date . ' +' . $days . ' days' ) ) . $end . ':00';
			} elseif ( $type == 'set' ) {
				$enddatetime = date( 'Y-m-d', strtotime( $date ) ) . ' ' . $end . ':00';
			} else {
				$enddatetime = date( 'Y-m-d H:i:s', strtotime( $date . ' +1 hour' ) );
			}
			$enddatetime = fix_enddatetime( $enddatetime, $date, $event->ID );
			$title       = get_the_title( $event->ID );
			$sql         = $wpdb->prepare( 'REPLACE INTO ' . $wpdb->prefix . 'rsvpmaker_event SET event=%d, post_title=%s, date=%s, enddate=%s, display_type=%s', $event->ID, $title, $date, $enddatetime, $type );
			$wpdb->query( $sql );
			$last_date = $event->datetime;
			$log      .= $sql . "\n";
		}
	}
	//rsvpmaker_debug_log( $log, 'log of updated events' );
	$where = " AND a1.meta_value < '" . $last_date . "'";
	$sql   = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime
	 FROM " . $wpdb->posts . '
	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates' AND post_status='publish' $where ORDER BY a1.meta_key DESC ";
	//rsvpmaker_debug_log( $sql, 'more events lookup' );
	$events = $wpdb->get_results( $sql );
	$more   = ( is_array( $events ) ) ? sizeof( $events ) : 0;
	//rsvpmaker_debug_log( $more, 'more events to convert' );
	update_option( 'rsvpmaker_update_last_date', $last_date );
	//rsvpmaker_debug_log( $last_date, 'recording last date' );
	if ( empty( $events ) ) {
		update_option( 'rsvpmaker_event_table', time() );
		wp_clear_scheduled_hook( 'rsvpmaker_update_table_continue' );
	} else {
		$timestamp = time() + 300;
		wp_schedule_single_event( $timestamp, 'rsvpmaker_update_table_continue' );
	}
}

add_action( 'updated_option', 'timezone_change_check' );
function timezone_change_check( $option_name ) {
	if ( $option_name == 'timezone_string' ) {
		rsvpmaker_consistency_check();
	}
}

function rsvpmaker_consistency_check( $post_id = 0 ) {
	global $default_tz,$wpdb;
	$last_tz = '';
	if ( $post_id ) {
		$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker_event WHERE event=$post_id LIMIT 0,100";
	} else {
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker_event WHERE date > CURDATE() ORDER BY date LIMIT 0,100';
	}
	$list = $wpdb->get_results( $sql );
	if ( $list ) {
		foreach ( $list as $event ) {
			$timezone = rsvpmaker_get_timezone_string( $event->event );
			if (!empty($timezone) && (( $timezone != $last_tz ) || ($timezone != $event->timezone) ) ) {
				//rsvpmaker_debug_log($event->event.':'.$timezone.':'.$last_tz,'ID timezone:last tz');
				date_default_timezone_set( $timezone );
			}
			$last_tz = $timezone;
			$t   = strtotime( $event->date );
			$end = strtotime( $event->enddate );
			//end should not be before the beginning
			if(($end < $t) || ($event->ts_end < $event->ts_start)) {
				$end = $event->ts_end = $t + HOUR_IN_SECONDS;
				$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET ts_end=%d, enddate=%s WHERE event=%d', $end, rsvpmaker_date('Y-m-d H:i:s',$end), $event->event );
				$wpdb->query($sql);
			}
			if ( ( $t != (int) $event->ts_start ) || ( $end != (int) $event->ts_end ) ) {
				$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET ts_start=%d, ts_end=%d, timezone=%s WHERE event=%d', $t, $end, $timezone, $event->event );
				//rsvpmaker_debug_log( $sql, 'consistency set timestamps' );
				$wpdb->query( $sql );
			}
			elseif(($timezone != $event->timezone) || strpos($event->timezone,':') ) {
				$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET timezone=%s WHERE event=%d', $timezone, $event->event );
				//rsvpmaker_debug_log( $sql, 'consistency set timezone' );
				$wpdb->query( $sql );
			}
			$ymd = date( 'Y-m-d', $t );
			// rsvpmaker_debug_log($ymd,'consistency check ymd');
			if ( $ymd != date( 'Y-m-d', $end ) ) {
				$endtime = date( 'H:i:s', $end );
				// rsvpmaker_debug_log($endtime,'end time');
				if ( strpos( $event->display_type, '|' ) ) {
					$dtparts   = explode( '|', $event->display_type );
					$increment = ( (int) $dtparts[1] ) - 1;
					$enddate   = date( 'Y-m-d', strtotime( $ymd . '+ ' . $increment . ' days' ) ) . ' ' . $endtime;
				} else {
					$enddate = $ymd . ' ' . $endtime;
				}
				// rsvpmaker_debug_log($enddate,'new end date');
				$end = strtotime( $enddate );
				$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET enddate=%s, ts_end=%d WHERE event=%d', $enddate, $end, $event->event );
				//rsvpmaker_debug_log( $sql, 'consistency check enddate' );
				$wpdb->query( $sql );
			}
			if(empty($event->post_title)) 
			{
				$post = get_post($event->event);
				$sql = $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'rsvpmaker_event SET post_title=%s WHERE event=%d', $post->post_title, $event->event );
				$wpdb->query($sql);
			}
		}
	}
	if ( $last_tz != $default_tz ) {
		date_default_timezone_set( $default_tz );
	}

	$event_table = get_rsvpmaker_event_table();
	$sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE post_type='rsvpmaker' AND meta_key='_sked_Monday' ";
	$results = $wpdb->get_results($sql);
	foreach($results as $p) {
		if(get_post_meta($p->ID,'_rsvp_dates',true))
			$wpdb->query("delete from $wpdb->postmeta WHERE post_id=$p->ID AND meta_key LIKE '_sked%' ");
		else {
			$wpdb->query("update $wpdb->posts set post_type='rsvpmaker_template' WHERE ID=$p->ID ");
		}		
	}
}

function fix_enddatetime( $enddatetime, $date, $post_id ) {
	global $wpdb;
	if ( $enddatetime < $date ) {
		$t           = strtotime( $date . ' +1 hour' );
		$enddatetime = date( 'Y-m-d H:i:s', $t );
		update_post_meta( $post_id, '_endfirsttime', date( 'H:i', $t ) );
	}
	return $enddatetime;
}

function rsvpmaker_excerpt_filter($excerpt) {
	global $post;
	if(isset($post->post_type) && (('rsvpmaker' == $post->post_type) || ('rsvpmaker_template' == $post->post_type))) {
		$block = rsvp_date_block($post->ID);
		$excerpt = !empty($block['dateblock']) ? $block['dateblock'] : '';// htmlentities(var_export($block,true)); //get_rsvp_date($post->ID);
		$excerpt .= ' '.substr(strip_tags($post->post_content),0,200) . ' ....';	
	}
	return $excerpt;
}

function rsvpmaker_excerpt( $post ) {
	global $rsvp_options, $post;
	$rsvp_on = get_post_meta( $post->ID, '_rsvp_on', true );
	$excerpt = rsvpdateblock();
	if ( strpos( $post->post_content, '<!--more-->' ) ) {
		$morelink = true;
		$p        = explode( '<!--more-->', $post->post_content );
		$excerpt  = do_blocks( $p[0] );
	} else {
		$content   = rsvpmaker_email_html( $post->post_content );
		$blocks    = explode( '<p', $content );
		$fullsize  = sizeof( $blocks );
		$blocks    = array_slice( $blocks, 0, 3 );
		$excerpt  .= implode( '<p', $blocks );
		$shortened = sizeof( $blocks );
		$morelink  = ( $fullsize > $shortened );
	}
	$permalink = get_permalink( $post->ID );
	$rsvplink  = add_query_arg( 'e', '*|EMAIL|*', $permalink ) . '#rsvpnow';
	if ( $morelink ) {
		$excerpt .= sprintf( '<p style="text-align: right;"><a href="%s">%s</a></p>', $permalink, __( 'Read More', 'rsvpmaker' ) );
	}
	if ( $rsvp_on ) {
		$excerpt .= sprintf( $rsvp_options['rsvplink'], $rsvplink );
	}
	return $excerpt;
}

add_shortcode( 'future_events_test', 'future_events_test' );

function get_future_events( $where_or_atts = '', $limit = 0, $output = OBJECT, $offset_hours = 0 ) {
	global $offset_hours;
	if ( is_array( $where_or_atts ) ) {
		$atts             = $where_or_atts;
		$atts['is_array'] = 1;
		$offset_hours     = ( isset( $where_or_atts['offset_hours'] ) ) ? $where_or_atts['offset_hours'] : 0;
	} else {
		$atts = array(
			'where'        => $where_or_atts,
			'limit'        => $limit,
			'offset_hours' => $offset_hours,
		);
	}
	$atts['afternow'] = 1;
	$data = rsvpmaker_upcoming_data( $atts );
	return $data;
}

function get_future_events_by_meta( $kv, $limit = '', $output = OBJECT, $offset_hours = 0 ) {

	global $wpdb;
	$wpdb->show_errors();

	$startfrom = ( $offset_hours ) ? ' DATE_SUB("' . get_sql_now() . '", INTERVAL ' . $offset_hours . ' HOUR) ' : '"' . get_sql_now() . '"';

		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.date as datetime, date_format(a1.date,'%M %e, %Y') as date, a1.enddate, a1.display_type, meta.meta_value
		 FROM " . $wpdb->posts . '
		 JOIN ' . $wpdb->prefix . 'rsvpmaker_event' . ' a1 ON ' . $wpdb->posts . '.ID =a1.event
		 JOIN ' . $wpdb->postmeta . ' meta ON ' . $wpdb->posts . '.ID =meta.post_id
		 WHERE (a1.date > ' . $startfrom . ' OR a1.enddate > ' . $startfrom . ") AND post_status='publish' 
		 AND meta_key='" . $kv['meta_key'] . "' ";
	if ( isset( $kv['meta_value'] ) ) {
		$comparison = '=';
		if ( isset( $kv['comparison'] ) ) {
			$comparison = $kv['comparison'];
		}
		$sql .= " AND meta_value $comparison '" . $kv['meta_value'] . "'";
	}
		$sql .= ' ORDER BY a1.date ';

	if ( ! empty( $limit ) ) {

		$sql .= ' LIMIT 0,' . $limit . ' ';
	}

		//rsvpmaker_debug_log( $sql, 'meta lookup test' );

		return $wpdb->get_results( $sql, $output );
}

function future_events_test() {
	$events = get_future_events();
	foreach ( $events as $event ) {
		echo esc_html($event->post_title);
	}
	return var_export( $events, true );
}

function get_future_dates( $limit ) {

	global $wpdb;

	$wpdb->show_errors();

	$startfrom = '"' . get_sql_curdate() . '"';

		$sql  = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	
		 FROM " . $wpdb->posts . '
	
		 JOIN ' . $wpdb->prefix . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	
		 WHERE a1.meta_value > " . $startfrom . " AND post_status='publish' ";
		$sql .= ' ORDER BY a1.meta_value ';

	if ( ! empty( $limit ) ) {

		$sql .= ' LIMIT 0,' . $limit . ' ';
	}

		return $wpdb->get_results( $sql );

}


function count_future_events() {

	global $wpdb;

	$wpdb->show_errors();

	$sql = 'SELECT COUNT(*)

	 FROM ' . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 WHERE a1.meta_value > '" . get_sql_now() . "' AND post_status='publish' ";

	return $wpdb->get_var( $sql );

}



function count_recent_posts( $blog_weeks_ago = 1 ) {

	global $wpdb;

	$week_ago_stamp = rsvpmaker_strtotime( '-' . $blog_weeks_ago . ' week' );

	$week_ago = date( 'Y-m-d H:i:s', $week_ago_stamp );

	$where = " AND post_date > '" . $week_ago . "'";

	$wpdb->show_errors();

	$sql = 'SELECT COUNT(*)

	 FROM ' . $wpdb->posts . "

	 WHERE post_type='post' AND post_status='publish' " . $where;

	return $wpdb->get_var( $sql );

}

function get_past_events( $where = '', $limit = '', $output = OBJECT ) {

	global $wpdb;
	$table = $wpdb->prefix.'rsvpmaker_event';

	$wpdb->show_errors();

	$sql = "SELECT DISTINCT *

	 FROM " . $wpdb->posts . '

	 JOIN ' . $table . ' ON ' . $wpdb->posts . ".ID = event 

	 WHERE date < '" . get_sql_now() . "' AND post_status='publish' ";

	if ( ! empty( $where ) ) {

		$where = trim( $where );

		$sql .= ' AND ' . $where . ' ';

	}

	$sql .= ' ORDER BY date DESC';

	if ( ! empty( $limit ) ) {

		$sql .= ' LIMIT 0,' . $limit . ' ';
	}

	return $wpdb->get_results( $sql );

}

function get_events_dropdown() {

	$options = '<optgroup label="' . __( 'Future Events', 'rsvpmaker' ) . '">' . "\n";

	$future = get_future_events();

	if ( is_array( $future ) ) {

		foreach ( $future as $event ) {

			if ( get_post_meta( $event->ID, '_rsvp_on', true ) ) {
				$options .= sprintf( '<option value="%s">%s - %s</option>' . "\n", esc_attr( $event->ID ), esc_html( $event->post_title ), rsvpmaker_date( 'F j, Y', rsvpmaker_strtotime( $event->datetime ) ) );
			}
		}
	}

	$options .= '<optiongroup>' . "\n";

	$options .= '<optgroup label="' . __( 'Recent Events', 'rsvpmaker' ) . '">' . "\n";

	$past = get_past_events( '', 50 );
	if ( is_array( $past ) ) {

		foreach ( $past as $event ) {
			if ( get_post_meta( $event->ID, '_rsvp_on', true ) ) {
				$options .= sprintf( '<option value="%s">%s - %s</option>' . "\n", $event->ID, esc_html( $event->post_title ), rsvpmaker_date( 'F j, Y', intval($event->ts_start) ) );
			}
		}
	}

	$options .= '<optiongroup>' . "\n";

	return $options;

}



function is_rsvpmaker_future( $event_id, $offset_hours = 0 ) {

	global $wpdb;

	if ( $offset_hours ) {

		$sql = 'SELECT meta_value FROM ' . $wpdb->postmeta . " WHERE meta_key='_rsvp_dates' AND meta_value + INTERVAL $offset_hours HOUR > '" . get_sql_now() . "' AND post_id=" . $event_id;

	} else {
		$sql = 'SELECT meta_value FROM ' . $wpdb->postmeta . " WHERE meta_key='_rsvp_dates' AND meta_value > '" . get_sql_now() . "' AND post_id=" . $event_id;
	}

	$date = $wpdb->get_var( $sql );

	return ( ! empty( $date ) );

}



function rsvpmaker_is_template( $post_id = 0 ) {
	global $post, $wpdb;
	if ( ! $post_id ) {
		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;

		} else {
			return false;
		}
	}
	return get_template_sked( $post_id );
}

function rsvpmaker_has_template( $post_id = 0 ) {

	global $post;

	if ( ! $post_id ) {

		if ( isset( $post->ID ) ) {

			$post_id = $post->ID;

		} else {
			return false;
		}
	}

	return get_post_meta( $post_id, '_meet_recur', true );
}


//replace with get_rsvp_event_times
function cache_rsvp_dates( $limit = 50 ) {

	global $rsvpdates, $wpdb;

	if ( ! empty( $rsvpdates ) ) {

		return;// if some other process already retrieved the dates
	}

	$rsvpdates = get_transient( 'rsvpmakerdates' );

	if ( ! empty( $rsvpdates ) ) {

		return;
	}

	$rsvpdates = array();

	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='_rsvp_dates' AND meta_value > '" . get_sql_now() . "' ORDER BY meta_value LIMIT 0, $limit";

	$results = $wpdb->get_results( $sql );

	if ( $results ) {

		foreach ( $results as $row ) {

			$rsvpdates[ $row->post_id ][] = $row->meta_value;

		}
	}

	set_transient( 'rsvpmakerdates', $rsvpdates, HOUR_IN_SECONDS );

}

function get_rsvpmaker_payment_gateway() {
	global $post, $rsvp_options;
	$active_options = get_rsvpmaker_payment_options();
	if ( ! empty( $post->ID ) ) {
		$choice = get_post_meta( $post->ID, '_payment_gateway', true );
		if ( $choice ) {
			return $choice; // if specified for the event post
		}
	}

	if ( ! empty( $rsvp_options['payment_gateway'] ) ) {

		if ( $rsvp_options['payment_gateway'] == 'stripe' ) { // legacy

			return 'Stripe via WP Simple Pay';
		}

		return $rsvp_options['payment_gateway'];
	}
	// print_r($active_options);
	return $active_options[0]; // if no default specified, grab the first one on the list (Cash or Custom if no others set up)
}

function get_rsvpmaker_payment_options() {

	global $rsvp_options;

	$active_options = array( 'Cash or Custom', 'PayPal REST API', 'Stripe','Both Stripe and PayPal' );

	if ( class_exists( 'Stripe_Checkout_Functions' ) && ! empty( $rsvp_options['stripe'] ) ) {

		$active_options[] = 'Stripe via WP Simple Pay';
	}

	return $active_options;

}


function get_rsvpmaker_stripe_keys_all() {

	$keys = get_option( 'rsvpmaker_stripe_keys' );

	if ( empty( $keys ) ) {

		// older method of setting these options

		$pk = get_option( 'rsvpmaker_stripe_pk' );

		if ( $pk ) {

			if ( strpos( $pk, 'test' ) ) {

				$keys['sandbox_pk'] = $pk;

				$keys['sandbox_sk'] = get_option( 'rsvpmaker_stripe_sk' );

				$keys['mode'] = 'sandbox';

				$keys['notify'] = get_option( 'rsvpmaker_stripe_notify' );

				$keys['pk'] = '';

				$keys['sk'] = '';

			} else {

				$keys['pk'] = $pk;

				$keys['sk'] = get_option( 'rsvpmaker_stripe_sk' );

				$keys['mode'] = 'production';

				$keys['notify'] = get_option( 'rsvpmaker_stripe_notify' );

				$keys['sandbox_pk'] = '';

				$keys['sandbox_sk'] = '';

			}

			update_option( 'rsvpmaker_stripe_keys', $keys );

		}
	}

	if ( empty( $keys ) ) {

		$keys = array(
			'pk'         => '',
			'sk'         => '',
			'sandbox_pk' => '',
			'sandbox_sk' => '',
			'mode'       => '',
			'notify'     => '',
		);
	}
	if(!isset($keys['webhook']))
		$keys['webhook'] = '';
	if(!isset($keys['sandbox_webhook']))
		$keys['sandbox_webhook'] = '';
	return $keys;
}

function get_rsvpmaker_stripe_keys($sandbox = false) {

	$keys = get_rsvpmaker_stripe_keys_all();
	//if set for this specific block
	if($sandbox || (!empty($_GET['sb']) && current_user_can('manage_options'))) {
		$_SESSION['sandbox_override'] = true;
		return array(
			'sk'     => $keys['sandbox_sk'],
			'pk'     => $keys['sandbox_pk'],
			'mode'   => 'sandbox',
			'notify' => $keys['notify'],
		);
	}

	if ( ! empty( $keys['mode'] ) && ( $keys['mode'] == 'production' ) && ! empty( $keys['sk'] ) ) {

		return array(
			'sk'     => $keys['sk'],
			'pk'     => $keys['pk'],
			'mode'   => 'production',
			'notify' => $keys['notify'],
		);

	} elseif ( ! empty( $keys['mode'] ) && ( $keys['mode'] == 'sandbox' ) && ! empty( $keys['sandbox_sk'] ) ) {

		return array(
			'sk'     => $keys['sandbox_sk'],
			'pk'     => $keys['sandbox_pk'],
			'mode'   => 'sandbox',
			'notify' => $keys['notify'],
		);

	} else {
		return false;
	}
}

function get_rspmaker_paypal_rest_keys() {

	$paypal_rest_keys = get_option( 'rsvpmaker_paypal_rest_keys' );

	return $paypal_rest_keys;

}

function rsvpmaker_is_post_meta( $post_id, $field ) {

	global $wpdb;

	return $wpdb->get_var( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key ='" . $field . "' AND post_id=" . intval( $post_id ) );

}



// a data integrity check run on wp_login. prevents null values from being passed to Gutenberg

function rsvpmaker_data_check() {

	global $rsvp_options, $wpdb;

	$last_data_check = (int) get_option( 'rsvpmaker_last_data_check2' );

	$last_data_check = 0;

	if ( $last_data_check > time() ) {

			return;

	}

	update_option( 'rsvpmaker_last_data_check2', rsvpmaker_strtotime( '+1 week' ) );

	$wpdb->query( "UPDATE $wpdb->postmeta SET meta_value=1 WHERE meta_key='_rsvp_rsvpmaker_send_confirmation_email' AND meta_value='on' " );

	$wpdb->query( "UPDATE $wpdb->posts SET post_type='rsvpmaker' WHERE post_title LIKE 'Form:%' AND post_type='post' " );

	$missing = 0;

	$found = 0;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

		$future = get_future_events();

	if ( ! empty( $future ) && is_array( $future ) ) {

		foreach ( $future as $post ) {

			$postlist[] = $post->ID;
		}
	}

		$templates = rsvpmaker_get_templates();

	if ( $templates ) {

		foreach ( $templates as $post ) {

			$postlist[] = $post->ID;
		}
	}

		$allones = false;

		$missingfields = '';

	if ( isset( $postlist ) ) {

		foreach ( $postlist as $post_id ) {

			foreach ( $defaults as $index => $field ) {

				$val = get_post_meta( $post_id, $field, true );

				if ( ! rsvpmaker_is_post_meta( $post_id, $field ) ) {

					update_post_meta( $post_id, $field, $rsvp_options[ $index ] );

					$missingfields .= $post_id . ' missing: ' . $field . ', ';

					$missing++;

				}

				if ( ( $field == '_rsvp_to' ) && is_numeric( $val ) ) {

					$allones = true;

				} else {
					$found++;
				}

				if ( ( $val != 1 ) && ( $val != '1' ) ) {

					$allones = false;
				}
			}

			$form = get_post_meta( $post_id, '_rsvp_form', true );

			if ( ! empty( $form ) && ! is_numeric( $form ) ) {

				$data['post_title'] = 'Form:' . $post_id;

				$data['post_content'] = $form;

				$data['post_status'] = 'publish';

				$data['post_type'] = 'rsvpmaker';

				$data['post_author'] = $post->post_author;

				$form_id = wp_insert_post( $data );

				update_post_meta( $form_id, '_rsvpmaker_special', 'RSVP Form' );

				update_post_meta( $post_id, '_rsvp_form', $form_id );

				$missingfields .= ' fixed non numeric form';

			}
		}
	}

	if ( $allones ) {
			rsvpmaker_set_defaults_all();
	}

}



function rsvpmaker_set_defaults_all( $display = false ) {

	global $rsvp_options, $wpdb;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

		$postlist = array();

		$future = get_future_events();

	if ( $future ) {

		foreach ( $future as $post ) {

			$postlist[] = $post->ID;
		}
	}

		$templates = rsvpmaker_get_templates();

	if ( $templates ) {

		foreach ( $templates as $post ) {

			$postlist[] = $post->ID;
		}
	}

	$output = '';

	if ( $postlist ) {

		foreach ( $postlist as $post_index => $post_id ) {

			foreach ( $defaults as $index => $field ) {

				update_post_meta( $post_id, $field, $rsvp_options[ $index ] );

				if ( $display && ( $post_index == 0 ) ) {

					$output .= '<div>' . $field . ': ' . $rsvp_options[ $index ] . '</div>';
				}
			}
		}
	}

	return $output;

}



function rsvpmaker_set_default_field( $index, $display = false ) {

	global $rsvp_options, $wpdb;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

		$postlist = array();

		$future = get_future_events();

	if ( $future ) {

		foreach ( $future as $post ) {

			$postlist[] = $post->ID;
		}
	}

		$templates = rsvpmaker_get_templates();

	if ( $templates ) {

		foreach ( $templates as $post ) {

			$postlist[] = $post->ID;
		}
	}

	$output = '';

	$field = $defaults[ $index ];

	echo '<h3>Index/Field: ' . $index . ':' . $field . '</h3>';

	if ( $postlist ) {

		foreach ( $postlist as $post_index => $post_id ) {

			update_post_meta( $post_id, $field, $rsvp_options[ $index ] );

			if ( $display && ( $post_index == 0 ) ) {

				$output .= '<div>' . $field . ': ' . $rsvp_options[ $index ] . '</div>';
			}
		}
	}

	return $output;

}

function rsvpmaker_cleanup() {

	global $wpdb;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

	?>

<h1>RSVPMaker Cleanup</h1>

	<?php


if ( isset( $_POST['rsvpmaker_database_check'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	cpevent_activate();
	echo '<div class="notice notice-success"><p>Checking that RSVPMaker database tables are properly initialized</p></div>';
	rsvpmaker_event_dates_table_update(true);
	echo '<div class="notice notice-success"><p>Checking that '.$wpdb->prefix.'resvpmaker_event table is complete</p></div>';
}

if ( isset( $_POST['rsvpmaker_template_duplicates'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	echo '<div class="notice notice-success"><p>Checking for duplicates by template</p></div>';
	$templates = rsvpmaker_get_templates(); 
	if($templates)
	{
		foreach($templates as $template) {
			printf('<p>Checking %s</p>',$template->post_title);
			$sofar = get_events_by_template($template->ID);
			if($sofar) {
				$dupcheck = [];
				foreach($sofar as $event) {
					$dupcheck[$event->date][] = $event;
				}
				foreach($dupcheck as $date => $check) {
					if(sizeof($check) > 1) {
						$keep = array_pop($check);
						foreach($check as $ch) {
							$meta = get_post_meta($ch->ID);
							foreach($meta as $key => $values) {
								if(preg_match('/_[A-Z]/',$key) && !empty($values[0])) {
									printf('<p><strong>%s %s</strong></p>',$key,$values[0]);
									continue;
								}
							}
							echo "<p>delete $ch->ID</p>";
							wp_delete_post($ch->ID,true);
						}
					}
				}
			}
		}
	}
}

	if ( isset( $_POST['reset_defaults'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

		$result = rsvpmaker_set_defaults_all( true );

		echo '<div class="notice notice-success"><p>Defaults applied to all templates and future events</p></div>';

		echo esc_html($result);

	}

	if ( isset( $_POST['default_field'] ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

		$result = '';

		foreach ( $_POST['default_field'] as $field ) {

			$field = sanitize_text_field( $field );

			$result .= rsvpmaker_set_default_field( $field, true );

		}

		echo '<div class="notice notice-success"><p>Defaults applied to all templates and future events for fields shown below.</p></div>';

		echo esc_attr($result);

	}

	if ( isset( $_POST['older_than'] ) ) {

		$older = sanitize_text_field($_POST['older_than']);
		$regex = '/^\d{4}-\d{2}-\d{2}$/';
		if ( ! preg_match( $regex, $older ) ) {
			die( 'invalid date' );
		}

		if ( ! isset( $_POST['confirm'] ) ) {

			?>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">

<input type="hidden" name="confirm" value="1" />

<input type="hidden" name="older_than" value="<?php echo esc_attr( $older ); ?>" /> 
rsvpmaker_nonce();
			<?php submit_button( 'Confirm Delete' ); ?>

</form>

<div>Preview</div>

			<?php

		}

		$sql = "SELECT DISTINCT ID as postID, $wpdb->posts.*, a1.meta_value as datetime,date_format(a1.meta_value,'%M %e, %Y') as date

	 FROM " . $wpdb->posts . '

	 JOIN ' . $wpdb->postmeta . ' a1 ON ' . $wpdb->posts . ".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

	 WHERE a1.meta_value < '$older' ";


		$results = $wpdb->get_results( $sql );

		if ( is_array( $results ) ) {

			foreach ( $results as $event ) {

				$deleted = '';

				if ( isset( $_POST['confirm'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

					wp_delete_post( $event->ID, true );

					$deleted = '<span style="color:red">Deleted</span> ';

				}

				printf( '<div>%s %s %s</div>', esc_html( $deleted ), esc_html( $event->post_title ), esc_html( $event->date ) );

			}
		}
	}

	if ( isset( $_POST['rsvps_older_than'] ) ) {

		$older = sanitize_text_field($_POST['rsvps_older_than']);
		$regex = '/^\d{4}-\d{2}-\d{2}$/';
		if ( ! preg_match( $regex, $older ) ) {
			die( 'invalid date' );
		}

		if ( ! isset( $_POST['confirm'] ) ) {

			?>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
rsvpmaker_nonce();
<input type="hidden" name="confirm" value="1" />

RSVPs older than <input type="hidden" name="rsvps_older_than" value="<?php echo esc_attr( $older ); ?>" /> 

			<?php submit_button( 'Confirm Delete' ); ?>

</form>

			<?php

		}

		if ( isset( $_POST['confirm'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "rsvpmaker WHERE timestamp < '$older' " );
			printf( '<p style="color: red;">Deleting RSVPs older than %s </p>', $older );
		} else {
			$sql   = 'SELECT count(*) FROM ' . $wpdb->prefix . "rsvpmaker WHERE timestamp < '$older' ";
			$count = $wpdb->get_var( $sql );
			printf( '<p style="color: red;">%d RSVPs older than %s </p>', $count, esc_html( $older ) );
		}
	}

	if ( ! empty( $_POST ) ) {
		printf( '<p><a href="%s">Reload form</a></p>', admin_url( 'tools.php?page=rsvpmaker_cleanup' ) );
	} else {
		$minus30 = strtotime( '30 days ago' );
		?>

<h2><?php esc_html_e( 'Remove Past Events from Database', 'rsvpmaker' ); ?></h2>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
<?php rsvpmaker_nonce();
esc_html_e( 'Delete events older than', 'rsvpmaker' ); ?> <input type="date" name="older_than" value="<?php echo date( 'Y-m-d', $minus30 ); ?>" /> 

		<?php submit_button( 'Delete' ); ?>

</form>

<h2><?php esc_html_e( 'Remove RSVP Event Registrations from Database', 'rsvpmaker' ); ?></h2>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
<?php rsvpmaker_nonce(); ?>
		<?php esc_html_e( 'Delete RSVP event registrations older than', 'rsvpmaker' ); ?> <input type="date" name="rsvps_older_than" value="<?php echo date( 'Y-m-d', $minus30 ); ?>" /> 

		<?php submit_button( 'Delete' ); ?>

</form>

<h2><?php esc_html_e( 'Apply Defaults', 'rsvpmaker' ); ?></h2>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
<?php rsvpmaker_nonce(); ?>

<p><?php esc_html_e( 'Apply default values from the RSVPMaker Settings screen to all templates and future events', 'rsvpmaker' ); ?></p>

<div><input id="all" type="checkbox" name="reset_defaults" value="1" checked="checked" /> <?php esc_html_e( 'All fields', 'rsvpmaker' ); ?></div>

		<?php

		foreach ( $defaults as $index => $field ) {

			printf( '<div><input class="default_field" type="checkbox" name="default_field[]" value="%s" />%s</div>', esc_attr( $index ), esc_html( $field ) );
		}

		?>

		<?php submit_button( 'Reset' ); ?>

</form>


<h2>Check RSVPMaker Templates for Duplicates</h2>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
<?php rsvpmaker_nonce();
?> <input type="hidden" name="rsvpmaker_template_duplicates" value="1" /> 
		<?php submit_button( 'Check Now' ); ?>
</form>

<h2>Check RSVPMaker Database Tables</h2>

<form method="post" action="<?php echo admin_url( 'tools.php?page=rsvpmaker_cleanup' ); ?>">
<?php rsvpmaker_nonce();
?> <input type="hidden" name="rsvpmaker_database_check" value="1" /> 
		<?php submit_button( 'Check Now' ); ?>
</form>

<?php
	$tables = $wpdb->get_results('SHOW TABLES');
	foreach ($tables as $mytable)
	{
		foreach ($mytable as $t) 
		{       
			if(strpos($t,$wpdb->prefix.'rsvp') !== false)
			echo $t . "<br>";
		}
	}
?>

<script>

jQuery(document).ready(function( $ ) {

$(document).on( 'click', '.default_field', function() {

	$("#all").prop("checked", false);

});



});

</script>

		<?php
	}
	// end initial form
}



function rsvp_simple_price( $post_id ) {

	$per = get_post_meta( $post_id, '_per', true );

	$price = ( empty( $per['price'][0] ) ) ? '' : $per['price'][0];

	return $price;

}



function rsvp_simple_price_label( $post_id ) {

	$per = get_post_meta( $post_id, '_per', true );

	$label = ( empty( $per['unit'][0] ) ) ? __( 'Tickets', 'rsvpmaker' ) : $per['unit'][0];

	return $label;

}



function rsvp_complex_price( $post_id ) {

	$per = get_post_meta( $post_id, '_per', true );

	if ( empty( $per ) ) {

		return '';
	}

	$complexity = '';

	$complex = false;

	$labels = $prices = 0;

	foreach ( $per as $index => $pricearray ) {

		// $complexity .= $index.': '.var_export($pricearray, true).', ';

		if ( $index == 'unit' ) {

			$labels = sizeof( $pricearray );
		}

		if ( $index == 'price' ) {

				$prices = sizeof( $pricearray );

			foreach ( $pricearray as $index => $price ) {

				$complexity .= $per['unit'][ $index ] . ': ' . $price . ', ';
			}
		}
	}

	if ( isset( $per['price_deadline'] ) ) {

		$complex = true;

		$complexity .= __( 'Pricing deadlines set in RSVP / Event Options', 'rsvpmaker' ) . '. ';

	}

	if ( isset( $per['price_multiple'] ) ) {

		$complex = true;

		$complexity .= __( 'Multiple admissions specified in RSVP / Event Options', 'rsvpmaker' ) . '. ';

	}

	if ( $prices > 1 ) {

		$complex = true;
	}

	if ( ! $complex ) {

		return '';
	}

	return $complexity;

}

function update_post_meta_unfiltered( $post_id, $meta_key, $meta_value ) {

	global $wpdb;

	if ( is_array( $meta_value ) ) {
		$meta_value = serialize( $meta_value );
	}

	$meta_id = $wpdb->get_var( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id=$post_id and meta_key='$meta_key' " );

	if ( $meta_id ) {

		$wpdb->query( "UPDATE $wpdb->postmeta SET meta_value='$meta_value' WHERE meta_id=$meta_id " );

	} else {
		$wpdb->query( "INSERT INTO $wpdb->postmeta SET meta_value='$meta_value', post_id=$post_id, meta_key='$meta_key' " );
	}

}

function update_rsvp_post_metadata( $check, $post_id, $meta_key, $meta_value ) {

	if ( ( $meta_key == 'simple_price' ) || ( $meta_key == 'simple_price_label' ) ) {

		$per = get_post_meta( $post_id, '_per', true );

		if ( empty( $per ) ) {

				$per = array();

				$per['unit'][0] = 'Tickets';

				$per['price'][0] = '';

		} elseif ( empty( $per['price'] ) ) {

			$per['price'][0] = '';
		}

		if ( $meta_key == 'simple_price' ) {

			$per['price'][0] = $meta_value;

		}

		if ( $meta_key == 'simple_price_label' ) {

			$per['unit'][0] = $meta_value;

		}

		update_post_meta( $post_id, '_per', $per );

		return $check; //also record Gutenberg value

	}

	$date_fields = array( '_firsttime', '_endfirsttime', '_day_of_week', '_week_of_month', '_template_start_hour', '_template_start_minutes', 'complex_template' );

	if ( in_array( $meta_key, $date_fields ) && ( $sked = get_template_sked( $post_id ) ) && is_array( $sked ) ) {

			$week = $sked['week'];

			$dayofweek = $sked['dayofweek'];

			$hour = $sked['hour'];

			$minutes = $sked['minutes'];

			$duration = $sked['duration'];

		if ( $meta_key == '_firsttime' ) {

			//rsvpmaker_debug_log( 'firsttime_test' . $meta_value, $meta_key );

			$sked['duration'] = $meta_value;

		} elseif ( ( $meta_key == '_endfirsttime' ) || ( $meta_key == '_endfirsttime' ) ) {

			//rsvpmaker_debug_log( $meta_value, $meta_key );

			$sked['end'] = $meta_value;

		}

		if ( $meta_key == '_template_start_hour' ) {

			$sked['hour'] = $meta_value;

			//rsvpmaker_debug_log( $meta_value, $meta_key );

		} elseif ( $meta_key == '_template_start_minutes' ) {

			$sked['minutes'] = $meta_value;

			//rsvpmaker_debug_log( $meta_value, $meta_key );

		} elseif ( $meta_key == '_day_of_week' ) {

			$sked['dayofweek'] = array( $meta_value );

			//rsvpmaker_debug_log( $meta_value, $meta_key );

		} elseif ( $meta_key == '_week_of_month' ) {

			//rsvpmaker_debug_log( $meta_value, $meta_key );

			$sked['week'] = array( $meta_value );

		}

		new_template_schedule( $post_id, $sked, 'update_rsvp_post_metadata' );

		return $check; //also record Gutenberg value

	}

	//rsvpmaker_debug_log( $meta_value, $meta_key . ' - update_rsvp_post_metadata' );

	return $check;

}

add_filter( 'update_post_metadata', 'update_rsvp_post_metadata', 10, 4 );

function rsvpmaker_check_privacy_page() {

	$privacy_page = get_option( 'wp_page_for_privacy_policy' );

	if ( $privacy_page ) {

		$privacy_post = get_post( $privacy_page );

		if ( empty( $privacy_post ) || ( $privacy_post->post_status != 'publish' ) ) {

			$privacy_page = 0;
		}
	}

	return $privacy_page;

}



function get_day_array() {

	return array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

}

function get_week_array() {

	return array( 'Varies', 'First', 'Second', 'Third', 'Fourth', 'Last', 'Every' );

}

function get_template_sked( $post_id ) {

	global $wpdb, $rsvp_options;

	$week_array = get_week_array();

	$day_array = get_day_array();

	$newsked = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_sked_%' " );
	if ( $newsked ) {

		$dayofweek = array();

		$week = array();

		foreach ( $newsked as $row ) {

			$key = str_replace( '_sked_', '', $row->meta_key );

			if ( in_array( $key, $day_array ) && $row->meta_value ) {

				$dayofweek[] = array_search( $key, $day_array );

			} elseif ( in_array( $key, $week_array ) && $row->meta_value ) {

				$week[] = array_search( $key, $week_array );

			} elseif ( ( $row->meta_key == '_sked_minutes' ) && ( $row->meta_value == '' ) ) {   // fix for corrupted record
					update_post_meta( $post_id, '_sked_minutes', $rsvp_options['defaultmin'] );
			}

			$sked[ $key ] = $row->meta_value;

		}
		if ( empty( $week ) && empty( $dayofweek ) ) {
			return false; // not a valid template
		}
		update_post_meta( $post_id, '_sked_template', true );

		sort( $week );

		sort( $dayofweek );

		$sked['dayofweek'] = $dayofweek;

		// if every is checked, ignore other checks

		$sked['week'] = $week;

		if ( sizeof( $week ) > 1 ) {

			// if every week, other weeks don't count

			if ( in_array( 6, $week ) ) {

				$sked['week'] = array( 6 );

				foreach ( $week_array as $index => $value ) {

					if ( $index != 6 ) {

						update_post_meta( $post_id, '_sked_' . $value, false );
					}
				}
			}

			if ( in_array( 0, $week ) ) { // if any other value is set, Varies doesn't make sense

				update_post_meta( $post_id, '_sked_Varies', false );
			}
		}

		if(empty($sked['start_time']))
		{
			if(empty($sked['hour'])) {
				$sked['start_time'] = $rsvp_options['defaulthour'].':'.$rsvp_options['defaultmin'];
				$t = strtotime('today '.$sked['_sked_start_time']);
				$sked['start_time'] = date('H:i:s',$t);
				$sked['end'] = date('H:i:s',$t + HOUR_IN_SECONDS);
			}
			else {
				$sked['start_time'] = $sked['hour'].':'.$sked['minutes'];
				$t = strtotime('today '.$sked['start_time']);
				$sked['start_time'] = date('H:i:s',$t);
			}
		}
		//sanity check
		$t = strtotime($sked['start_time']);
		$end = (empty($sked['end'])) ? 0 : strtotime($sked['end']);
		if($t > $end)
		{
		$end = $t + HOUR_IN_SECONDS;
		$sked['end'] = date('H:i:s',$end);
		update_post_meta($post_id,'_sked_end',$sked['_sked_end']);
		}
		//backward compatability
		$parts = explode(':',$sked['start_time']);
		$sked['hour'] = $parts[0];
		$sked['minutes'] = $parts[1];
	
		return $sked;

	}
	return false;

}

function new_template_schedule( $post_id, $template, $source = '' ) {

	if ( is_array( $template['week'] ) ) {
		$weeks = $template['week'];
		$dows  = ( empty( $template['dayofweek'] ) ) ? array() : $template['dayofweek'];
	} else {
		$weeks[0] = $template['week'];
		$dows[0]  = ( isset( $template['dayofweek'] ) ) ? $template['dayofweek'] : 9; // no day for varies
	}
	$hour     = ( isset( $template['hour'] ) ) ? $template['hour'] : '00';
	$minutes  = ( isset( $template['minutes'] ) ) ? $template['minutes'] : '00';
	$duration = ( isset( $template['duration'] ) ) ? $template['duration'] : '';

	$end = ( isset( $template['end'] ) ) ? $template['end'] : '';

	$stop = ( isset( $template['stop'] ) ) ? $template['stop'] : '';

	$new_template_schedule = build_template_schedule( $post_id, $dows, $weeks, $hour, $minutes, $duration, $end, $stop );
	foreach ( $new_template_schedule as $label => $value ) {
		$label = '_sked_' . $label;
		update_post_meta_unfiltered( $post_id, $label, $value );
	}
	$new_template_schedule['week']      = $weeks;
	$new_template_schedule['dayofweek'] = $dows;
	return $new_template_schedule;
}

function build_template_schedule( $post_id, $dows, $weeks, $hour, $minutes, $duration, $end, $stop ) {

	$weekarray = get_week_array();
	foreach ( $weekarray as $index => $label ) {
		$atomic_sked[ $label ] = in_array( $index, $weeks );
	}
		$dayarray = get_day_array();
	foreach ( $dayarray as $index => $label ) {
		$atomic_sked[ $label ] = in_array( $index, $dows );
	}

	$atomic_sked['hour'] = ( empty( $hour ) ) ? '00' : $hour;

	$atomic_sked['minutes'] = ( empty( $minutes ) ) ? '00' : $minutes;

	$atomic_sked['stop'] = $stop;

	$atomic_sked['duration'] = $duration;

	$atomic_sked['end'] = $end;

	return $atomic_sked;

}



function default_gateway_check( $chosen_gateway ) {

	if ( empty( $chosen_gateway ) || ( $chosen_gateway == 'Cash or Custom' ) ) {

		$paypal_rest_keys = get_option( 'rsvpmaker_paypal_rest_keys' );

		$stripe_keys = get_rsvpmaker_stripe_keys_all();

		$gateway_set = '';

		if ( ! empty( $paypal_rest_keys ) ) {

			foreach ( $paypal_rest_keys as $index => $value ) {

				if ( $index == 'sandbox' ) {

					continue;
				}

				if ( ! empty( $value ) ) {

					$gateway_set = 'PayPal';

					break;

				}
			}
		}

		if ( ! empty( $stripe_keys ) ) {

			foreach ( $stripe_keys as $index => $value ) {

				if ( $index == 'mode' ) {

					continue;
				}

				if ( ! empty( $value ) ) {

					if ( $gateway_set == 'PayPal' ) {

							$gateway_set = 'PayPal or Stripe';

							break;

					} else {

						$gateway_set = 'Stripe';

						break;

					}
				}
			}
		}
	}

	if ( ! empty( $gateway_set ) ) {

		return sprintf( '<p style="color: red; font-weight: bold;">%s %s?</p>', __( 'Do you want to set the Preferred Payment Gateway to', 'rsvpmaker' ), $gateway_set );
	}

}



function get_rsvp_id( $email = '' ) {

	global $post, $wpdb, $email_context;

	$rsvp_id = 0;

	if ( isset( $_GET['rsvp'] ) ) {

		$rsvp_id = (int) $_GET['rsvp'];

	} elseif ( isset( $_GET['update'] ) ) {

		$rsvp_id = (int) $_GET['update'];

	} elseif ( isset( $_COOKIE[ 'rsvp_for_' . $post->ID ] ) && ! $email_context ) {

		$rsvp_id = (int) $_COOKIE[ 'rsvp_for_' . $post->ID ];

	} elseif ( is_user_logged_in() && ! empty( $email ) ) {

		$sql = 'SELECT id FROM ' . $wpdb->prefix . 'rsvpmaker WHERE email LIKE "' . $email . '" AND event=' . intval( $post->ID ) . ' ORDER BY id DESC';

		$rsvp_id = (int) $wpdb->get_var( $sql );

	}

	return $rsvp_id;

}



function get_rsvp_email() {

	global $post, $wpdb, $email_context;

	$email = '';

	global $current_user;

	if ( isset( $_GET['e'] ) ) {

			$email = sanitize_text_field($_GET['e']);

	} elseif ( isset( $_COOKIE[ 'rsvp_for_' . $post->ID ] ) && ! $email_context ) {

			$rsvp_id = (int) $_COOKIE[ 'rsvp_for_' . $post->ID ];

			$sql = 'SELECT email FROM ' . $wpdb->prefix . 'rsvpmaker WHERE id=' . $rsvp_id;

			$email = $wpdb->get_var( $sql );

	} elseif ( is_user_logged_in() ) {

		$email = $current_user->user_email;

	}

	if ( $email && ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {

		$email = '';
	}

	return $email;

}



function rsvpmaker_parent( $post_id ) {

	global $wpdb;

	return $wpdb->get_var( "SELECT post_parent FROM $wpdb->posts WHERE ID=$post_id" );

}



function get_form_links( $post_id, $t, $parent_tag ) {

	global $rsvp_options;
	$args[] = array(

		'parent' => $parent_tag,

		'id'     => 'edit_form',

		'title'  => 'RSVP Form',

		'href'   => admin_url( 'post.php?action=edit&tab=form&post=' . $post_id ),

		'meta'   => array( 'class' => 'rsvpmenu' ),

	);

	return $args;

}

function get_conf_links( $post_id, $t, $parent_tag ) {

	global $rsvp_options, $wpdb;

	$label = '';

	$confirm_id = get_post_meta( $post_id, '_rsvp_confirm', true );

	if ( $confirm_id == $rsvp_options['rsvp_confirm'] ) {

		$label = ' (Default)';

	} elseif ( $confirm_id ) {

		$cpost = get_post( $confirm_id );

		if ( empty( $cpost->ID ) ) {

			$confirm_id = $rsvp_options['rsvp_confirm'];
			$label      = ' (Default)';

		} else {

			$parent_id = $cpost->post_parent;

			if ( $parent_id == $t ) {

				$label = ' (From Template)';

			} elseif ( $parent_id != $post_id ) {

				$label = ' (Inherited)';
			}
		}
	} else {

		$confirm_id = $rsvp_options['rsvp_confirm'];

		$label = ' (Default)';

	}

	$args[] = array(

		'parent' => $parent_tag,

		'id'     => 'edit_confirm',

		'title'  => 'Confirmation Message' . $label,

		'href'   => admin_url( 'post.php?action=edit&post=' . $confirm_id . '&back=' . $post_id ),

		'meta'   => array( 'class' => 'rsvpmenu' ),

	);

	$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_rsvp_reminder_msg_%' ORDER BY meta_key";

	$results = $wpdb->get_results( $sql );

	if ( $results ) {

		foreach ( $results as $row ) {

			$hours = str_replace( '_rsvp_reminder_msg_', '', $row->meta_key );

			$meta_key = $row->meta_key;

			$type = ( $hours > 0 ) ? 'FOLLOW UP' : 'REMINDER';

			$reminder = rsvp_get_reminder( $post_id, $hours );
			if(empty($reminder->ID))
				continue;

			$parent = (isset($reminder->post_parent)) ? $reminder->post_parent : 0;

			$label = ( $parent != $post_id ) ? ' (from Template)' : '';

			$identifier = 'reminder' . $hours;

			$args[] = array(

				'parent' => $parent_tag,

				'id'     => $identifier,

				'title'  => $type . ' ' . $hours . ' hours' . $label,

				'href'   => admin_url( 'post.php?action=edit&post=' . $reminder->ID ),

				'meta'   => array( 'class' => 'rsvpmenu' ),

			);

		}
	}

	$payconf = get_post_meta( $post_id, 'payment_confirmation_message', true );

	$meta_key = 'payment_confirmation_message';

	if ( !empty( $payconf ) ) {

		$payparent = rsvpmaker_parent( $payconf );

		$label = ( $payparent == $t ) ? ' (from Template)' : '';

		$args[] = array(

			'parent' => $parent_tag,

			'id'     => 'edit_payment_confirmation',

			'title'  => 'Payment Confirmation' . $label,

			'href'   => admin_url( 'post.php?action=edit&post=' . $payconf ),

			'meta'   => array( 'class' => 'rsvpmenu' ),

		);
	}
	return $args;
}

function get_more_related( $post, $post_id, $t, $parent_tag ) {

	global $wpdb, $rsvp_options;

	$args[] = array(

		'parent' => $parent_tag,

		'id'     => 'confirmation_reminders',

		'title'  => 'Confirmations + Reminders',

		'href'   => admin_url( 'post.php?action=edit&tab=confirmation&post=' . $post_id ),

		'meta'   => array( 'class' => 'confirmation_reminders' ),

	);

	$forms = get_form_links( $post_id, $t, $parent_tag );

	foreach ( $forms as $arg ) {

		$args[] = $arg;
	}

		if('rsvpmaker' == $post->post_type) {
			$args[] = array(

				'parent' => $parent_tag,
	
				'id'     => 'rsvp_report',
	
				'title'  => 'RSVP Report',
	
				'href'   => admin_url( 'edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . $post_id ),
	
				'meta'   => array( 'class' => 'edit_form' ),
	
			);	
		}

		if ( $t ) {

			$args [] = array(
				'parent' => $parent_tag,
				'id'     => 'rsvpmaker-edit-template',
				'href'   => admin_url( 'post.php?action=edit&post=' ) . $t,
				'title'  => __( 'Edit Template', 'rsvpmaker' ),
				'meta'   => array( 'class' => 'rsvpmaker-edit-template' ),
			);

			$args [] = array(
				'parent' => $parent_tag,
				'id'     => 'rsvpmaker-switch-template',
				'href'   => admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&apply_target='.$post->ID.'#applytemplate' ),
				'title'  => __( 'Switch Template', 'rsvpmaker' ),
				'meta'   => array( 'class' => 'rsvpmaker-switch-template' ),
			);

			$args[] = array(

				'parent' => $parent_tag,

				'id'     => 'template-options',

				'title'  => 'Template Options',

				'href'   => admin_url( 'post.php?action=edit&tab=basics&post=' . $t ),

				'meta'   => array( 'class' => 'template-options' ),

			);

			$args[] = array(
				'parent' => $parent_tag,
				'title'  => __( 'Update Template Based On Event' ),
				'id'     => 'rsvpmaker-overwrite-template',
				'href'   => admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&override_template=' ) . $t . '&event=' . intval( $post->ID ),
				'meta'   => array( 'class' => 'rsvpmaker-overwrite-template' ),
			);

		}

		if ( 'rsvpmaker_template' == $post->post_type ) {

			$args[] = array(

				'id'     => 'rsvpmaker_create_update',

				'parent' => $parent_tag,

				'title'  => 'Create / Update',

				'href'   => admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $post_id ),

				'meta'   => array( 'class' => 'rsvpmaker-create-update' ),

			);

		}

		// rsvpmaker_debug_log($args,'more menu array');

		return $args;

}



function get_related_documents( $post_id = 0, $query = '' ) {

	global $post, $rsvp_options;
	$backup = $post;
	$args = array();

	if ( isset( $_GET['page'] ) && isset( $_GET['post_id'] ) ) {

		$post_id = (int) $_GET['post_id'];
	}

	if ( $post_id ) {

		$post = get_post( $post_id );

	} elseif ( isset( $post->ID ) ) {

		$post_id = $post->ID;

	} else {
		return array();
	}

	if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) && empty( $_GET['page'] ) ) {

		return array();
	}

	if ( ( $post->post_type != 'rsvpmaker' ) && ( $post->post_type != 'rsvpemail' )  && ( $post->post_type != 'rsvpmaker_template' ) ) {

		return array();
	}

	$t = rsvpmaker_has_template( $post->ID );

	$parent_tag = 'edit'; // front end

	if ( isset( $_GET['page'] ) || isset( $_GET['action'] ) ) {

		$parent_tag = 'rsvpmaker_options';
	}

	$rsvp_id = rsvpmaker_parent( $post_id );

	if ( ( $query == 'rsvpemail' ) && ! $rsvp_id && ! ( strpos( $post->post_title, 'Default' ) ) ) {
		return array();
	}

	// $parent_tag = (is_admin() && !isset($_GET['page'])) ? 'rsvpmaker_options' : 'edit';

	if ( isset( $post->post_title ) && ( strpos( $post->post_title, 'Default' ) ) ) {

		$args[] = array(

			'id'    => 'rsvpmaker_settings',

			'title' => __( 'RSVPMaker Settings', 'rsvpmaker' ),

			'href'  => admin_url( 'options-general.php?page=rsvpmaker-admin.php' ),

			'meta'  => array( 'class' => 'edit-rsvpmaker-options' ),
		);

		if ( ! empty( $_GET['back'] ) ) {

			$rsvp_parent = (int) $_GET['back'];

			$args[] = array(

				'id'    => 'rsvpmaker_parent',

				'title' => __( 'Edit Event', 'rsvpmaker' ),

				'href'  => admin_url( 'post.php?action=edit&post=' . $rsvp_parent ),

				'meta'  => array( 'class' => 'edit-rsvpmaker' ),

			);

			$args[] = array(

				'id'    => 'view-event',

				'title' => __( 'View Event', 'rsvpmaker' ),

				'href'  => get_permalink( $rsvp_parent ),

				'meta'  => array( 'class' => 'view' ),

			);

			$args[] = array(

				'parent' => 'rsvpmaker_parent',

				'id'     => 'rsvpmaker_options',

				'title'  => 'RSVP / Event Options',

				'href'   => admin_url( 'post.php?action=edit&tab=basics&post=' . $rsvp_parent ),

				'meta'   => array( 'class' => 'edit-rsvpmaker-options' ),

			);

			$parent_tag = 'rsvpmaker_parent';

			$more = get_more_related( get_post( $rsvp_parent ), $rsvp_parent, rsvpmaker_has_template( $rsvp_parent ), $parent_tag );

			foreach ( $more as $add ) {

				$args[] = $add;
			}
		}// default used in a post identified by "back"

		return $args;

	} // end this is a default message

	$rsvp_parent = $post->post_parent;

	if ( ! empty( $_GET['back'] ) ) {

		$rsvp_parent = (int) $_GET['back'];
	}

	if ( $rsvp_parent ) {

		$args[] = array(

			'id'    => 'rsvpmaker_parent',

			'title' => __( 'Edit Event', 'rsvpmaker' ),

			'href'  => admin_url( 'post.php?action=edit&post=' . $rsvp_parent ),

			'meta'  => array( 'class' => 'edit-rsvpmaker' ),

		);

		$parent_tag = 'rsvpmaker_parent';

		$args[] = array(

			'id'    => 'view-event',

			'title' => __( 'View Event ', 'rsvpmaker' ),

			'href'  => get_permalink( $rsvp_parent ),

			'meta'  => array( 'class' => 'view-event' ),

		);

		$args[] = array(

			'parent' => $parent_tag,

			'id'     => 'rsvpmaker_options',

			'title'  => 'RSVP / Event Options',

			'href'   => admin_url( 'post.php?action=edit&tab=basics&post=' . $rsvp_parent ),

			'meta'   => array( 'class' => 'rsvpmenu' ),

		);

		$more = get_more_related( get_post( $rsvp_parent ), $rsvp_parent, rsvpmaker_has_template( $rsvp_parent ), $parent_tag );

		foreach ( $more as $add ) {

			$args[] = $add;
		}

		return $args;

	}

	if ( ( $post->post_type != 'rsvpmaker' ) && ( $post->post_type != 'rsvpmaker_template' ) ) {

		return array();// no rsvpemail documents unless they have a post parent
	}

	if ( is_admin() && isset( $_GET['page'] ) && ( $_GET['page'] == 'rsvpmaker_details' ) ) {

		$args[] = array(

			'id'    => 'rsvpmaker_options',

			'title' => __( 'Edit Event', 'rsvpmaker' ),

			'href'  => admin_url( 'post.php?action=edit&post=' . $post_id ),

			'meta'  => array( 'class' => 'edit-rsvpmaker' ),

		);

		$args[] = array(

			'id'    => 'view-event',

			'title' => __( 'View Event', 'rsvpmaker' ),

			'href'  => get_permalink( $post_id ),

			'meta'  => array( 'class' => 'view' ),

		);

	} elseif ( is_admin() && isset( $_GET['page'] ) ) {

		// a different page

		$args[] = array(

			'id'    => 'rsvpmaker_options',

			'title' => __( 'Edit Event', 'rsvpmaker' ),

			'href'  => admin_url( 'post.php?action=edit&post=' . $post_id ),

			'meta'  => array( 'class' => 'edit-rsvpmaker' ),

		);

		$args[] = array(

			'id'    => 'view-event',

			'title' => __( 'View Event', 'rsvpmaker' ),

			'href'  => get_permalink( $post_id ),

			'meta'  => array( 'class' => 'view' ),

		);

		$args[] = array(

			'parent' => $parent_tag,

			'id'     => 'rsvpmaker_options_screen',

			'title'  => 'RSVP / Event Options',

			'href'   => admin_url( 'post.php?action=edit&tab=basics&post=' . $post_id ),

			'meta'   => array( 'class' => 'edit-rsvpmaker-options' ),

		);

	} elseif ( is_admin() ) {

		// edit or other admin page

		$args[] = array(

			'id'    => 'rsvpmaker_options',

			'title' => 'RSVP / Event Options',

			'href'  => admin_url( 'post.php?action=edit&tab=basics&post=' . $post_id ),

			'meta'  => array( 'class' => 'edit-rsvpmaker-options' ),

		);

	} else { // front end

		$args[] = array(

			'parent' => $parent_tag,

			'id'     => 'rsvpmaker_options',

			'title'  => 'RSVP / Event Options',

			'href'   => admin_url( 'post.php?action=edit&tab=basics&post=' . $post_id ),

			'meta'   => array( 'class' => 'edit-rsvpmaker-options' ),

		);
	}

	$more = get_more_related( $post, $post_id, $t, $parent_tag );

	foreach ( $more as $add ) {

		$args[] = $add;
	}

	return $args;

}

function get_rsvpmaker_authors() {

	$entire_user_list = get_users( 'orderby=display_name' );

	$rsvp_users = array();

	foreach ( $entire_user_list as $user ) {

		if ( $user->has_cap( 'edit_rsvpmakers' ) ) {

			$rsvp_users[] = array(
				'ID'   => $user->ID,
				'name' => $user->display_name,
			);

		}
	}

	return $rsvp_users;

}

function cleanup_rsvpmaker_child_documents() {
	global $wpdb;
	// forms and messages with no event document
	$sql     = "SELECT parent_post.ID, child_post.ID as child_ID, child_post.post_parent
FROM $wpdb->posts as parent_post right join $wpdb->posts as child_post ON parent_post.ID=child_post.post_parent where parent_post.ID IS NULL AND child_post.post_parent";
	$results = $wpdb->get_results( $sql );
	foreach ( $results as $row ) {
		wp_delete_post( $row->child_ID, true );
	}
}

add_filter( 'mailpoet_newsletter_shortcode', 'mailpoet_rsvpmaker_shortcode', 10, 5 );

function mailpoet_rsvpmaker_shortcode( $shortcode, $newsletter, $subscriber, $queue, $newsletter_body ) {
	// always return the shortcode if it doesn't match your own!
	if ( ! strpos( $shortcode, 'rsvpmaker' ) && ! strpos( $shortcode, 'event_listing' ) ) {
		return $shortcode;
	}
	global $email_context;
	$email_context = true;
	$shortcode     = str_replace( 'custom:', '', $shortcode );
	$atts          = shortcode_parse_atts( str_replace( ']', '', str_replace( '[', '', $shortcode ) ) );
	if ( strpos( $shortcode, 'upcoming' ) ) {
		$content = rsvpmaker_upcoming( $atts );
	} elseif ( strpos( $shortcode, 'one' ) ) {
		$content = rsvpmaker_one( $atts );
	}
	if ( strpos( $shortcode, 'next' ) ) {
		$content = rsvpmaker_next( $atts );
	} elseif ( strpos( $shortcode, 'listing' ) ) {
		$content = rsvpmaker_event_listing( $atts );
	} elseif ( strpos( $shortcode, 'youtube' ) ) {
		preg_match( '/(?<!")(https:\/\/www.youtube.com\/watch\?v=|https:\/\/youtu.be\/)([a-zA-Z0-9_\-]+)/', $atts['url'], $match );
		$image   = 'https://img.youtube.com/vi/' . $match[2] . '/mqdefault.jpg';
		$link    = ( empty( $atts['link'] ) ) ? $match[0] : $atts['link'];		
		//$content = sprintf( '<p><a href="%s">Watch on YouTube: %s<br /><img src="%s" width="320" height="180" /></a></p>', $link, $link, $image );
		$content = sprintf( '<p><a href="%s">Watch on YouTube: %s</p><a href="%s" style="text-align: center; padding-top: 130px; height: 640px; width:360px; background-image: url(%s); background-size: contain; background-repeat: no-repeat; margin-left: auto; margin-right: auto; %s"><div><img src="%s" ></div></a>', $link, $link, 'display: block; width: 100%; height: 300px;', $image, $link, plugins_url('rsvpmaker/images/youtube-button-100px.png') );
	}
	$content = str_replace( '<h1', '<h1 style="line-height: 1.3" ', $content );
	$content = str_replace( 'class="rsvpmaker-entry-title-link"', 'style="text-decoration: none" ', $content );
	return $content;
}

add_shortcode('rsvpmaker_youtube_email_test','rsvpmaker_youtube_email_test');

function rsvpmaker_youtube_email_test() {
	$content = '<p>The Postmark service for reliable email delivery is active.</p></div>
	<article>
	<div class="entry-content" style="">
	<div id="email-content">

	<!-- editors note goes here -->

		
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio" style=""><div class="wp-block-embed__wrapper" style="">
<iframe title="5th Anniversary Celebration - Online Presenters Toastmasters"   src="https://www.youtube.com/embed/7H5-oRolU_I?feature=oembed" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
</div></figure>';
preg_match_all('|<iframe.+src="https://www.youtube.com/embed/([^\?"]+)|is',$content,$matches);
return rsvpmaker_youtube_email($content);// htmlentities(var_export($matches,true));
}

function rsvpmaker_youtube_email($content) {
	//return $content;
	$iframe = '|<iframe.+src="https://www.youtube.com/embed/([^\?"]+).+</iframe>|';
	$content = preg_replace_callback($iframe,function ($match) {
		return empty($match[1]) ? '' : "\n\n".YouTubeEmailFormat('https://www.youtube.com/watch?v='.$match[1])."\n\n";
	},$content);
	$pattern = '/(?<!")(https:\/\/www.youtube.com\/watch\?v=|https:\/\/www.youtube.com\/embed\/|https:\/\/youtu.be\/)([^\s<]+)/';
		$content = preg_replace_callback($pattern,function ($match) {
			return empty($match[2]) ? '' : "\n\n".YouTubeEmailFormat('https://www.youtube.com/watch?v='.$match[2])."\n\n";
		},$content);
	return $content;
}

add_shortcode(
	'custom:rsvpmaker_youtube',
	function() {
		return '';
	}
);

function mailpoet_email_list_okay( $rsvp ) {
	if ( class_exists( \MailPoet\API\API::class ) ) {
		$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		$list         = get_option( 'rsvpmaker_mailpoet_list' );
		if ( ! $list ) {
			return;
		}
		$list_ids     = array( $list );
		$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		$first        = ( empty( $rsvp['first'] ) ) ? '' : $rsvp['first'];
		$last         = ( empty( $rsvp['last'] ) ) ? '' : $rsvp['last'];
		$subscriber   = array(
			'email'      => $rsvp['email'],
			'first_name' => $first,
			'last_name'  => $last,
		);
		try {
			$get_subscriber = $mailpoet_api->getSubscriber( $subscriber['email'] );
		} catch ( \Exception $e ) {
		}
		try {
			if ( ! $get_subscriber ) {
				  // Subscriber doesn't exist let's create one
				  $mailpoet_api->addSubscriber( $subscriber, $list_ids );
			} else {
				// In case subscriber exists just add him to new lists
				$mailpoet_api->subscribeToLists( $subscriber['email'], $list_ids );
			}
		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();
		}
	}
}

function rsvpmaker_sametime( $datetime, $post_id = 0 ) {

	global $wpdb;
	$sql             = sprintf( "SELECT * FROM $wpdb->posts JOIN %s ON $wpdb->posts.ID=%s.event WHERE post_status='publish' AND ID != %d AND date='%s' ", $wpdb->prefix . 'rsvpmaker_event', $wpdb->prefix . 'rsvpmaker_event', $post_id, $datetime );
	$sametime_events = $wpdb->get_results( $sql );
	$mod             = '';
	if ( $sametime_events ) {
		$label = ( sizeof( $sametime_events ) > 1 ) ? __( 'Events', 'rsvpmaker' ) : __( 'Event', 'rsvpmaker' );
		$mod  .= ' <span style="color:red;">* ' . $label . ' ' . __( ' at same time', 'rsvpmaker' ) . '</span>: ';
		$same  = array();
		foreach ( $sametime_events as $sametime ) {
			$title  = ( empty( $sametime->post_title ) ) ? '?' : $sametime->post_title;
			$same[] = sprintf( '<a href="%s">%s</a> ', admin_url( 'post.php?action=edit&post=' . $sametime->ID ), $title );
		}
		$mod .= implode( ', ', $same );
		$d    = get_post_meta( $sametime->ID, '_detached_from_template', true );
		$mod .= ( $d ) ? ' - detached from template ' . $d : '';
	}

	return $mod;
}

function rsvpmaker_edit_link( $post_id, $label = '', $new = false ) {
	if ( empty( $post_id ) ) {
		return 'not set';
	}
	$title = get_the_title( $post_id );
	if ( empty( $label ) ) {
		$label = $title;
	}
	if ( ! $title ) {
		return $label . ' not found';
	}
	if ( current_user_can( 'edit_post', $post_id ) ) {
		$blank = ( $new ) ? ' target="_blank" ' : '';
		return '<a href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '" ' . $blank . '>' . $label . '</a>';
	} else {
		return $label . ' (' . __( 'You cannot edit', 'rsvpmaker' );
	}
}

function strftime_format_to_date_format( $strftimeformat ) {
	// It is important to note that some do not translate accurately ie. lowercase L is supposed to convert to number with a preceding space if it is under 10, there is no accurate conversion so we just use 'g'
	$phpdateformat = str_replace(
		array(
			'%a',
			'%A',
			'%d',
			'%e',
			'%u',
			'%w',
			'%W',
			'%b',
			'%h',
			'%B',
			'%m',
			'%y',
			'%Y',
			'%D',
			'%F',
			'%x',
			'%n',
			'%t',
			'%H',
			'%k',
			'%I',
			'%l',
			'%M',
			'%p',
			'%P',
			'%r', /* %I:%M:%S %p */
			'%R', /* %H:%M */
			'%S',
			'%T', /* %H:%M:%S */
			'%X',
			'%z',
			'%Z',
			'%c',
			'%s',
			'%%',
		),
		array(
			'D',
			'l',
			'd',
			'j',
			'N',
			'w',
			'W',
			'M',
			'M',
			'F',
			'm',
			'y',
			'Y',
			'm/d/y',
			'Y-m-d',
			'm/d/y',
			"\n",
			"\t",
			'H',
			'G',
			'h',
			'g',
			'i',
			'A',
			'a',
			'h:i:s A',
			'H:i',
			's',
			'H:i:s',
			'H:i:s',
			'O',
			'T',
			'D M j H:i:s Y', /*Tue Feb 5 00:45:10 2009*/
			'U',
			'%',
		),
		$strftimeformat
	);
	return $phpdateformat;
}

function rsvpmaker_is_url_local( $url ) {
	$host = parse_url( $url, PHP_URL_HOST );

	// Case of an url passed w/o protocol
	if ( $host === null ) {
		$host = $url;
	}

	$ip = gethostbyname( $host );

	return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
}

//add_action('after_setup_theme','rsvpmail_editor_style',99999);
/* deprecated */
function rsvpmail_editor_style() {
	return;
global $editor_styles;
global $post;
if(isset($_GET['post']))
	$post = get_post($_GET['post']);

if((isset($post->post_type) && ($post->post_type == 'rsvpmailer')) || strpos($_SERVER['REQUEST_URI'],'post-new.php?post_type=rsvpemail') )
{
	//rsvpmaker_included_styles();
	$rsvpmailer_css = 'rsvpemail-editor-style.css';
	$editor_styles = array($rsvpmailer_css);
}

}

function get_site_members( $blog_id = 0 ) {

	if ( empty( $blog_id ) ) {

		$blog_id = get_current_blog_id();
	}

	return get_users(
		array(
			'blog_id' => $blog_id,
			'orderby' => 'display_name',
		)
	);

}

function rsvpmail_add_problem($email,$code) {
	global $wpdb;
	$table = $wpdb->prefix . "rsvpmailer_blocked";
	$email = trim(strtolower($email));
	$sql = $wpdb->prepare("SELECT code from $table where email=%s",$email);
	if(! $wpdb->get_var($sql) )
		$wpdb->query("INSERT INTO $table SET email='".$email."',code='".$code."' ");
	do_action('rsvpmail_add_problme',$email,$code);
}

function rsvpmail_remove_problem($email) {
	global $wpdb;
	$table = $wpdb->prefix . "rsvpmailer_blocked";
	$email = trim(strtolower($email));
	$wpdb->query("DELETE from $table where email='".$email."' ");
	do_action('rsvpmail_remove_problme',$email);
}

function rsvpmail_problem_init() {
	global $wpdb;
	$unsubscribed = get_option('rsvpmail_unsubscribed');
	if(!empty($unsubscribed) && is_array($unsubscribed)) {
		foreach($unsubscribed as $email)
			rsvpmail_add_problem($email,'unsubscribed');
		delete_option('rsvpmail_unsubscribed');
	}
}

function rsvpmail_is_problem($email) {
	if(strpos($email,'example.com'))
		return $email.' : example.com blocked';
	if(!is_email($email))
		return 'not a valid email';
	$email = sanitize_text_field($email);
	global $wpdb;
	$table = $wpdb->prefix . "rsvpmailer_blocked";
	$email = trim(strtolower($email));
	$sql = $wpdb->prepare("SELECT code from $table where email=%s AND (code='unsubscribed' OR code LIKE 'blocke%')",$email);
	$code = $wpdb->get_var($sql);
	if(empty($code))
		$code = apply_filters('rsvpmail_is_problem',$code,$email);
	if($code) {
		return $email.': '.$code;
	}
}

function rsvpmaker_atts($post_id, $event = NULL) {
	global $wpdb;
	$atts = array('_is_template' => $wpdb->get_var("select meta_value from $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_sked_Varies' "),
'_rsvp_on'=>'','_convert_timezone'=>'','_add_timezone'=>'','_calendar_icons'=>'',"_rsvp_timezone_string"=>'','_firsttime'=>'','_endfirsttime'=>'','_meet_recur'=>'','_rsvpmaker_special'=>'','event'=>NULL,'_rsvp_dates'=>'');
	$sql = "SELECT * FROM $wpdb->postmeta where post_id=$post_id AND (meta_key='_rsvp_on' ";
	foreach($atts as $index => $name) {
		if($index != '_rsvp_on')
			$sql .= " OR meta_key='$index' ";
	}
	$sql .= ")";
	$results = $wpdb->get_results($sql);
	foreach($results as $row) {
		$atts[$row->meta_key] = $row->meta_value;
	}
	return $atts;
}

function rsvpmaker_make_end_date ($date,$type='',$end='') {
	if ( strpos( $type, '|' ) ) {
		$p           = explode( '|', $type );
		$enddate = date( 'Y-m-d ', strtotime( $date . ' +' . ($p[1] - 1) . ' days' ) );
	} else {
		$enddate = preg_replace( '/\d{2}:\d{2}:\d{2}/', '', $date );
	}
	if(empty($end)) {
		$ts_start = strtotime($date);
		$end = date('H:i', $ts_start+3600);
	}
	$enddate = $enddate .' '.$end;
	//rsvpmaker_debug_log("$date / $type / $end / $enddate","date / type / end / enddate");
	return $enddate;
}

add_filter( 'wp_nav_menu', 'wp_nav_menu_rsvpmaker', 10, 2 );

function wp_nav_menu_rsvpmaker( $menu_html, $menu_args ) {
global $rsvp_options;
	if ( strpos( $menu_html, '#rsvp-' )) {
		preg_match_all('/<li.+#rsvp-([^"]+).+<\/li>/',$menu_html,$match);
		foreach($match[1] as $index => $type) {
			global $rsvp_options;
			if('all' != $type)
				$atts = array('type' => $type);
			else
				$atts = array();
			$events = rsvpmaker_upcoming_data( $atts );
		
			$date_format = $rsvp_options['short_date'];
		
			$listings = '';
		
			if ( is_array( $events ) ) {
		
				foreach ( $events as $event ) {
					$t = ( $event->ts_start ) ? (int) $event->ts_start : rsvpmaker_strtotime( $event->datetime );
		
					$dateline = rsvpmaker_date( $date_format, $t ); // rsvpmaker_long_date($event->ID, isset($atts['time']), false);
		
					$listings .= sprintf( '<li><a href="%s">%s - %s</a></li>' . "\n", esc_url_raw( get_permalink( $event->ID ) ), esc_html( $event->post_title ), $dateline );
			
				}
			$menu_html = str_replace($match[0][$index],$listings,$menu_html);
			}	
		}
	}
	return $menu_html;
}

function rsvphoney_ui($return = false) {
	$html = '<div class="rsvploginrequired" aria-hidden="true"><p><label>Extra Discount Code</label> <input name="extra_special_discount_code" /></p><p></p></div>';
	if($return)
		return $html;
	echo $html;
}

function rsvphoney_login() {
	if(!empty($_POST['extra_special_discount_code']))
		rsvphoney_login_now();
}

add_action('admin_init','rsvpmaker_number_events_post',1);
function rsvpmaker_number_events_post() {
	if(!isset($_POST['start_number']))
		{
			//echo 'start number not set';
			return;
		}
	$t = intval($_GET['t']);
	$start_number = intval($_POST['start_number']);
	$starting_with = intval($_POST['starting_with']);
	$on = ($starting_with == 0);
	$events = get_events_by_template($t);
	if($events) {
		foreach($events as $event) {
			if($event->ID == $starting_with)
				$on = true;
			if($on) {
				update_post_meta($event->ID,'rsvpeventnumber',$start_number);
				$start_number++;
				}
			}
		}
	update_post_meta($t,'rsvpeventnumber_top',$start_number);
}

function rsvpmaker_number_events_ui($t) {
	$top_number = 0;
	$events = get_events_by_template($t);
	$defaultoption = '<option value="0">Next Event</option>';
	$options = '';
	$isset = 1;
	if($events) {
		$isset = get_post_meta($events[0]->ID,'rsvpeventnumber',true);
		if(!$isset)
			$isset = 1;
		foreach($events as $event) {
			$top_number = (int) get_post_meta($event->ID,'rsvpeventnumber',true);
			$current = ($top_number) ? '(currently #'.$top_number.')' : '';
			$options .= sprintf('<option value="%d">%s %s %s</option>', $event->ID, $event->post_title, $event->datetime, $current);
			/*
			if($isset && $top_number) {
				$defaultoption = sprintf('<option value="%d" selected="selected">%s (currently #%d)</option>',$event->ID, $event->datetime,$top_number);
			}
			*/
		}
	}
	?>
	<h3>Number Events</h3>
	<p><em>This feature is for sequential numbering of events, for example class sessions or meetings since a Toastmasters club was chartered.</em></p>
	<p>To use it, use the form below to set the number of the next event or a selected upcoming event.</p>
	<p>Include the shortcode (placeholder) <code>[rsvpmaker_numbered]</code> with the square brackets included in the text of your event template document, as part of a paragraph or heading (but not the main post title). When you go through the Create / Update routine, that code will then be copied to the event posts for specific dates, each of which will have an event number set. When displayed on the website, that code will be replaced with the event number.</p>
	<?php	
	printf('<form method="post" action="%s"><p>Starting number <input type="number" name="start_number" value="%d"><p>Starting with <select name="starting_with">%s</select></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$t), $isset, $defaultoption.$options);
	submit_button('Add Numbering');
	echo '</form>';
}

function rsvpmaker_numbered () {
	global $post;
	return get_post_meta($post->ID,'rsvpeventnumber',true);
}

add_shortcode('rsvpmaker_numbered','rsvpmaker_numbered');

function theme_features_test() {
	$output = '';
	$json = new WP_Theme_JSON_Resolver();
	$jsondata = (array) $json->get_merged_data('theme');
	$p = array_pop($jsondata);
	return '<pre>'.var_export($p['settings'],true).'</p>';
	$p = $p['settings']['color'];
	$palette = $p['palette']['default'];
	if(isset($p['palette']['theme'])) {
		$theme = $p['palette']['theme'];
	}
	else {
		$theme = array();
	}
	foreach($palette as $index => $item) {
		$output .= sprintf('<p>%s %s</p>',$index, var_export($item,true));
		if(isset($item['slug'])) {
			$theme_colors[$item['slug']] = $item['color'];
		}
			$theme_colors[$item['slug']] = $item['color'];
	}
	foreach($theme as $index => $item) {
		$output .= sprintf('<p>%s %s</p>',$index, var_export($item,true));
		if(isset($item['slug']))
			$theme_colors[$item['slug']] = $item['color'];
	}

	//$output .= '<p> palette: </p><pre>'.var_export($palette,true).'</pre>';
	//$output .= '<p> theme: </p><pre>'.var_export($theme,true).'</pre>';
	$output .= '<p> colors </p><pre>'.var_export($theme_colors,true).'</pre>';

	return $output;


	$p = array_shift($jsondata);
	//rsvpmaker_debug_log($p['settings']['color'],'theme colors');
	$p = $p['settings']['color']['palette'];

	foreach($p as $index => $value_array) {
		foreach($value_array as $index => $item) {
			if($index == 'theme') {
				foreach($item as $subitem) {
					$output .= sprintf('<p>theme item %s</p>',var_export($subitem));
					if(!empty($subitem['slug']))
					$theme_colors[$subitem['slug']] = $subitem['color'];		
				}
			}
			elseif(!empty($item['slug']))
			$theme_colors[$item['slug']] = $item['color'];
		}
	}

	return '<pre>'.var_export($jsondata,true).'</pre>'.$output;
}
add_shortcode('theme_features_test','theme_features_test');

//placeholder for deprecated function
function rsvpmaker_inliner($content) {
return preg_replace('/<style.+</style>/','',$content);
}

function rsvpmaker_admin_heading_help($content, $function) {
	$helplinks = get_option('rsvpmaker_help');
	$missing = get_option('rsvpmaker_missing_help');
	if(empty($missing))
		$missing = array();
	if(!is_array($helplinks)) {
		$helplinks = array();
		$helplinks['email_get_content'] = sprintf('<p><a href="%s" target="_blank">%s</a></p>','https://rsvpmaker.com/blog/2022/06/02/updated-rsvp-mailer-for-event-invitations-and-newsletters/','Blog Post: Updated RSVP Mailer for Event Invitations and Newsletters');
	}
	$help = '';
	if(!empty($helplinks[$function]))
	{
		$help .= $helplinks[$function];
		$index = array_search($function,$missing);
		if($index !== false)
			{
				unset($missing[$index]);
				update_option('rsvpmaker_missing_help',$missing);
			}
	}
	else {
		if(!is_array($missing))
			$missing = array();
		if(!in_array($function,$missing))
			{
				$missing[$function] = $function;
				update_option('rsvpmaker_missing_help',$missing);
				unset($missing[$function]);//before display
			}
		$show = array('delta.local','rsvpmaker.com','www.wp4toastmasters.com');
		if(in_array($_SERVER['SERVER_NAME'],$show))
			$help = '<p style="font-size: small;">No help link yet for '.$function.'</p>';
	}
	return $content . $help;
}

add_filter('rsvpmaker-admin-heading-help','rsvpmaker_admin_heading_help',1,2);

//rsvpmaker_admin_heading(__('Headline','rsvpmaker'),__FUNCTION__,'tag');
function rsvpmaker_admin_heading($headline, $function, $tag='', $sidebar = '') {
if(!empty($tag))
	$function .= '_'.$tag;
?>
<div class="rsvpmaker-admin-heading">
<?php
$help = apply_filters('rsvpmaker-admin-heading-help','',$function,$tag);
$help .= $sidebar;
if(!empty($help)) 
	echo '<div class="rsvpmaker-admin-heading-help" style="float: right; margin-left: 20px; width: 300px; background-color: #FFFFE0; padding-left: 10px; border: thin dotted #000;"><p><strong>Help Resources</strong></p>'.$help.'</div>';
?>
<div class="rsvpmaker-admin-heading-headline" ><h1><?php echo $headline; ?></h1></div>
</div>
<?php
}

add_action('admin_footer','rsvpmaker_update_help');

function rsvpmaker_update_help() {
	//print_r($_SERVER);
	if(strpos($_SERVER['SCRIPT_NAME'],'index.php')) {
		$helpjson = file_get_contents('https://rsvpmaker.com/wp-json/rsvpmaker/v1/help');
		if(!empty($helpjson)) {
			$data = json_decode($helpjson);
			$data = (array) $data;
			update_option('rsvpmaker_help',$data);
			printf('<p style="text-align: center; margin: 20px; padding: 20px;">Downloaded %d help entries</p>',sizeof($data));
		}	
	}
}

function rsvpmaker_print_word() {
	$printlink = admin_url( str_replace( '/wp-admin/', '', $_SERVER['REQUEST_URI'] ) ) . '&rsvp_print=1&'.rsvpmaker_nonce('query');
	$wordlink = admin_url( str_replace( '/wp-admin/', '', $_SERVER['REQUEST_URI'] ) ) . '&rsvp_print=word&'.rsvpmaker_nonce('query');
	return '<p><a target="_blank" href="' . $printlink .'">'.__('Print','rsvpmaker-for-toastmasters').'</a></p><p><a target="_blank" href="' . $wordlink .'">'.__('Export to Word','rsvpmaker-for-toastmasters').'</a></p>';
}

function rsvpmaker_notice($message, $status='success', $is_dismissible = true) {
printf('
<div class="notice notice-%s %s">
<p>%s</p>
</div>
',$status,($is_dismissible) ? 'is-dismissible' : '',$message);
}

add_shortcode('rsvpmailer_bot_shortcode','rsvpmailer_bot_shortcode');
function rsvpmailer_bot_shortcode() {
	$result = rsvpmaker_relay_queue();
	$result .= rsvpmaker_relay_get_pop( 'bot' );
	return $result;
}

function rsvpmaker_guestparty($rsvp_id, $master = false) {
	global $wpdb;
	$guestparty = '';
	$exclude = array('first','last','id','email','yesno','event','owed','amountpaid','master_rsvp','guestof','note','participants','user_id','timestamp','payingfor');
	if($master) {
		$guestsql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE id=' . $rsvp_id . ' ORDER BY id';
		$row = $wpdb->get_row($guestsql, ARRAY_A);
		if(!$row) {
			//rsvpmaker_guestparty master row not found
			return;
		}
		$row = rsvp_row_to_profile( $row );
		$guestparty .= '<p>'.$row['first'].' '.$row['last'];
		foreach($row as $key => $value) {
			if(in_array($key,$exclude))
				continue;
			$guestparty .= '<br>'.ucwords(str_replace('_',' ',$key)).': '.$value;
		}
		$guestparty .= '</p>';
}

	$guestsql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE master_rsvp=' . $rsvp_id . ' ORDER BY id';

	if ( $results = $wpdb->get_results( $guestsql, ARRAY_A ) ) {
		$guestparty .= "<h3>Guests</h3>\n";
		foreach($results as $row) {
			$row = rsvp_row_to_profile( $row );
			$guestparty .= '<p>'.$row['first'].' '.$row['last'];
			foreach($row as $key => $value) {
				if(in_array($key,$exclude))
					continue;
				$guestparty .= '<br>'.ucwords(str_replace('_',' ',$key)).': '.$value;
			}
			$guestparty .= '</p>';
		}
		return $guestparty;
	}
	return false;
}

function customize_forms_and_messages() {
	global $current_user, $wpdb, $rsvp_options;
	if ( ! empty( $_GET['rsvp_form_new'] ) ) {
		$id = rsvpmaker_get_form_id( $_GET['rsvp_form_new'] );
		printf('<div class="notice notice-successs"><p>New form: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( ! empty( $_GET['rsvp_form_switch'] ) && ! empty( $_GET['post_id'] ) ) {
		$id      = (int) $_GET['rsvp_form_switch'];
		$post_id = (int) $_GET['post_id'];
		update_post_meta( $post_id, '_rsvp_form', $id );
		printf('<div class="notice notice-successs"><p>Switched form: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( current_user_can( 'manage_options' ) && isset( $_GET['upgrade_rsvpform'] ) ) {
		$id = upgrade_rsvpform();
		printf('<div class="notice notice-successs"><p>Upgraded form: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( isset( $_GET['rsvpcz_default'] ) && isset( $_GET['post_id'] ) ) {
		$meta_key = sanitize_text_field($_GET['rsvpcz_default']);
		$post_id  = (int) $_GET['post_id'];
		$id       = $rsvp_options[ $meta_key ];
		update_post_meta( $post_id, '_' . $meta_key, $id );
		printf('<div class="notice notice-successs"><p>Switched to default form: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( isset( $_GET['rsvpcz'] ) && isset( $_GET['post_id'] ) ) {
		$meta_key = sanitize_text_field($_GET['rsvpcz']);
		$parent   = (int) $_GET['post_id'];
		$title    = sanitize_text_field(stripslashes($_GET['title'])) . ':' . $parent;
		$content  = '';
		if ( isset( $_GET['source'] ) ) {
			$source = (int) $_GET['source'];
			if ( $source ) {
				$old     = get_post( $source );
				$content = ( empty( $old->post_content ) ) ? '' : $old->post_content;
			}
		}

		$new['post_title'] = $title;

		$new['post_parent'] = $parent;

		$new['post_status'] = 'publish';

		$new['post_type'] = ( $meta_key == '_rsvp_form' ) ? 'rsvpmaker' : 'rsvpemail';

		$new['post_author'] = $current_user->ID;

		$new['post_content'] = $content;

		$id = wp_insert_post( $new );
		if ( ! $id ) {
			return;
		}

		if ( !empty($source) ) {

			rsvpmaker_copy_metadata( $source, $id );
		}

		update_post_meta( $parent, $meta_key, $id );

		if ( $meta_key == '_rsvp_form' ) {

			update_post_meta( $id, '_rsvpmaker_special', 'RSVP Form' );// important to make form blocks available
			printf('<div class="notice notice-successs"><p>Custom form created: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		} else {
			update_post_meta( $id, '_rsvpmaker_special', $title );
			printf('<div class="notice notice-successs"><p>%s created: <a href="%s">Edit</a></p></div>',$title,admin_url( 'post.php?post=' . $id . '&action=edit' ));
		}
		return;
	}

	if ( isset( $_GET['customize_rsvpconfirm'] ) ) {

		$parent = (int) $_GET['post_id'];

		$source = (int) get_post_meta( $parent, '_rsvp_confirm', true );

		$old = get_post( $source );

		if ( $old->post_parent ) { // false for default message

			$id = $old->ID; // if link called after custom post already created

		} elseif ( $old ) {

			$new['post_title'] = 'Confirmation:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $old->post_content;

			$id = wp_insert_post( $new );

			if ( $id ) {

				update_post_meta( $parent, '_rsvp_confirm', $id );
			}

			update_post_meta( $id, '_rsvpmaker_special', 'Confirmation Message' );

		}
		printf('<div class="notice notice-successs"><p>Custom confirmation message created: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( isset( $_POST['create_reminder_for'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

		$parent  = $post_id = (int) $_POST['create_reminder_for'];
		$event   = get_post( $post_id );
		$subject = $event->post_title;

		$hours = (int) $_REQUEST['hours'];

		$key = '_rsvp_reminder_msg_' . $hours;

		$copy_from = (int) $_POST['copy_from'];

		$content = '';

		if ( $copy_from ) {

			$copy = get_post( $copy_from );

			$content = $copy->post_content;

		}

		$id = get_post_meta( $parent, $key, true );

		if ( ! $id ) {

			$label = ( $hours > 0 ) ? __( 'Follow Up', 'rsvpmaker' ) : __( 'Reminder', 'rsvpmaker' );

			$title = $label . ': ' . get_the_title( $post_id ) . ' [datetime]';

			$new['post_title'] = $title;

			$new['post_parent'] = $post_id;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $content;

			$id = wp_insert_post( $new );

		}

		if ( $id ) {

			update_post_meta( $parent, $key, $id );

			update_post_meta( $id, '_rsvpmaker_special', 'Reminder (' . $hours . ' hours) ' . $subject );

			if ( isset( $_POST['paid_only'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

				update_post_meta( $id, 'paid_only_confirmation', 1 );
			}

			if ( rsvpmaker_is_template( $post_id ) ) {

				rsvpmaker_template_reminder_add( $hours, $post_id );

				rsvpautorenew_test(); // will add to the next scheduled event associated with template

			} else {
				$start_time = get_rsvpmaker_timestamp($post_id);
				rsvpmaker_reminder_cron( $hours, $start_time, $post_id );
			}
		}

		printf('<div class="notice notice-successs"><p>Reminder created: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
		return;
	}

	if ( isset( $_GET['payment_confirmation'] ) ) {

		$parent = (int) $_GET['post_id'];

		$id = get_post_meta( $parent, 'payment_confirmation_message', true );

		$source = ( isset( $_GET['source'] ) ) ? (int) $_GET['source'] : 0;

		if ( empty( $id ) || $source ) {

			$new['post_title'] = 'Payment Confirmation:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'draft';

			$new['post_type'] = 'rsvpemail';

			$new['post_author'] = $current_user->ID;

			if ( $source ) {

				$source_post = get_post( $source );

				$new['post_content'] = $source_post->post_content;

			} else {
				$new['post_content'] = '';
			}

			$id = wp_insert_post( $new );

			if ( $id ) {
				update_post_meta( $parent, 'payment_confirmation_message', $id );
				update_post_meta( $id, '_rsvpmaker_special', 'Payment Confirmation Message' );			
				printf('<div class="notice notice-successs"><p>Payment confirmation message created: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
				return;
			}
		}
	}

	if ( isset( $_GET['customize_form'] ) ) {

		$parent = (int) $_GET['post_id'];

		$source = (int) get_post_meta( $parent, '_rsvp_form', true );

		$old = get_post( $source );

		if ( $old->post_parent ) { // false for default form

			$id = $old->ID; // if link called after custom post already created

		} elseif ( $old ) {

			$new['post_title'] = 'RSVP Form:' . $parent;

			$new['post_parent'] = $parent;

			$new['post_status'] = 'publish';

			$new['post_type'] = 'rsvpmaker';

			$new['post_author'] = $current_user->ID;

			$new['post_content'] = $old->post_content;

			remove_all_filters( 'content_save_pre' ); // don't allow form fields to be filtered out

			$id = wp_insert_post( $new );

			if ( $id ) {
				update_post_meta( $parent, '_rsvp_form', $id );
				update_post_meta( $id, '_rsvpmaker_special', 'RSVP Form' );
				printf('<div class="notice notice-successs"><p>Custom form: <a href="%s">Edit</a></p></div>',admin_url( 'post.php?post=' . $id . '&action=edit' ));
				return;
			}
		}
	}
}

function rsvpBlockDataOutput($block, $post_id) {
    if(empty($block))
        return;
    $attrs = ($block->attrs) ? json_encode($block->attrs) : '';
	if(!empty($block->innerHTML) || (!empty($block->innerBlocks) && sizeof($block->innerBlocks)) ) {
        $output = sprintf('<!-- wp:%s %s -->',$block->blockName,$attrs)."\n";
        if(!empty($block->innerHTML))
			$output .= $block->innerHTML."\n";
        if(!empty($block->innerBlocks) && is_array($block->innerBlocks) && sizeof($block->innerBlocks)) {
            foreach($block->innerBlocks as $innerblock) {
                $output .= rsvpBlockDataOutput($innerblock,$post_id);
            }
        }
        $output .= sprintf('<!-- /wp:%s -->',$block->blockName)."\n\n";    
    }
    else 
        $output = sprintf('<!-- wp:%s %s /-->',$block->blockName,$attrs)."\n\n";
    return $output;
}

add_filter('the_content','rsvpmaker_show_meta',999,2);

function rsvpmaker_show_meta($content) {
	global $post;
	if(isset($_GET['showmeta']) && current_user_can('manage_options'))
		$content .= '<hr>metadata<hr><pre>'.var_export(get_post_meta($post->ID),true).'</pre>';
	return $content;
}

function rsvpmaker_check_sametime($datetime,$post_id=0) {
	global $wpdb, $rsvp_options;
	$parts = explode(' ',$datetime);
	$dups = array('sametime' => [], 'sameday' => []);
	$event_table = get_rsvpmaker_event_table();
	$sql = $wpdb->prepare("select * from $event_table JOIN $wpdb->posts ON $wpdb->posts.ID = $event_table.event WHERE date=%s AND event != %d AND post_status='publish' ",$datetime,$post_id);
	$results = $wpdb->get_results($sql);
	if($results) {
		foreach($results as $row) {
			$row->prettydate = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$row->ts_start);
			$row->permalink = get_permalink($row->event);
			$row->edit = admin_url('post.php?action=edit&post='.$row->event);
			$dups['sametime'][] = $row;
		}
	}
	$sql = $wpdb->prepare("select * from $event_table WHERE date!=%s AND event != %d AND date LIKE '".$parts[0]."%' ",$datetime,$post_id);
	$results = $wpdb->get_results($sql);
	if($results) {
		foreach($results as $row) {
			$row->prettydate = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$row->ts_start);
			$row->permalink = get_permalink($row->event);
			$row->edit = admin_url('post.php?action=edit&post='.$row->event);
			$dups['sameday'][] = $row;
		}
	}
	return $dups;
}

function rsvpmaker_testlog($key,$data) {
	if(function_exists('rsvpmaker_testing'))
		set_transient($key,$data,DAY_IN_SECONDS);
}