<?php

function fix_timezone($timezone = '' ) {
	global $post;
	if(empty($timezone))
		$timezone = get_option('timezone_string');
	if(isset($post->ID))
	{
		$post_tz = get_post_meta($post->ID,'_rsvp_timezone_string',true);
		if(!empty($post_tz) && $post_tz != $timezone)
			$timezone = $post_tz;
	}
	if(!empty($timezone) )
		date_default_timezone_set($timezone);
}
	
function restore_timezone() {
	global $default_tz;
	date_default_timezone_set($default_tz);
}	

function rsvpmaker_strtotime($string) {
	$string = str_replace('::',':',$string);
	fix_timezone();
	$t = strtotime($string);
	restore_timezone();
	return $t;
}

function rsvpmaker_mktime($hour=NULL, $minute = NULL, $second = NULL,$month = NULL, $day = NULL, $year = NULL) {
	fix_timezone();
	$t = mktime((int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day,(int)$year);
	restore_timezone();
	return $t;
}

function rsvpmaker_strftime($date_format = '', $t = NULL) {
	fix_timezone();
	global $rsvp_options;
	if(empty($date_format))
		$date_format = $rsvp_options['long_date'];
	if(empty($t))
		$t = time();
	if(!is_int($t))
		$t = strtotime($t);
	$output = strftime($date_format, $t);
	restore_timezone();
	return $output;
}

function rsvpmaker_date($date_format = '', $t = NULL) {
	fix_timezone();
	global $rsvp_options;
	if(empty($date_format))
		$date_format = $rsvp_options['long_date'];
	if(empty($t))
		$t = time();
	if(!is_int($t))
		$t = strtotime($t);
	$output = date($date_format, $t);
	restore_timezone();
	return $output;
}

function get_sql_now() {
	
	$date = rsvpmaker_date('Y-m-d H:i:s');
	 
	return $date;
}

function get_sql_curdate() {
	
	$date = rsvpmaker_date('Y-m-d 00:00:00');
	 
	return $date;
}

function get_rsvp_date($post_id)
{
if(empty($post_id))
	return;
global $wpdb, $rsvpdates;
if(empty($rsvpdates))
	cache_rsvp_dates(50);
if(!empty($rsvpdates[$post_id]))
	return $rsvpdates[$post_id][0];
$wpdb->show_errors();
$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE post_id=".$post_id." AND meta_key='_rsvp_dates' ORDER BY meta_value";
return $wpdb->get_var($sql);
}

function rsvpmaker_duration_select ($slug, $datevar = array(), $start_time='', $index = 0) {
global $rsvp_options;
if(empty($datevar))
	$datevar = array('duration' => '');
if(!empty($datevar['duration']) && is_array($datevar['duration']))
	$duration_type = $datevar['duration'][$index];
elseif(!empty($datevar['duration']))
	$duration_type = $datevar['duration'];
else
	$duration_type = '';
$end_time = '';

if(!empty($datevar['end_time']))
	{
		$end_time = (is_array($datevar['end_time'])) ? $datevar['end_time'][$index] : $datevar['end_time'];
	}
elseif(!empty($datevar['end']))
{
	$end_time = (is_array($datevar['end'])) ? $datevar['end'][$index] : $datevar['end'];
}

echo __('End Time','rsvpmaker');
printf(' <select name="%s" class="end_time_type" > ',$slug);
?>
<option value=""><?php echo __('Not set (optional)','rsvpmaker');?></option>
<option value="set" <?php if($duration_type == 'set') echo ' selected="selected" '; ?> ><?php echo __("Set end time",'rsvpmaker');?></option>
<option value="allday" <?php if($duration_type == 'allday') echo ' selected="selected" '; ?>><?php echo __("All day/don't show time in headline",'rsvpmaker');?></option>
<?php
echo '</select>';

if(empty($end_time) && !empty($start_time))
	{
		$t = rsvpmaker_strtotime($start_time.' +1 hour');
		$defaulthour = rsvpmaker_date('H',$t);
		$defaultmin = rsvpmaker_date('i',$t);
	}
else {
	if(empty($end_time))
	{
		$start_time = $rsvp_options['defaulthour'].':'.$rsvp_options['defaultmin'];
		$t = rsvpmaker_strtotime($start_time.' +1 hour');
		$defaulthour = rsvpmaker_date('H',$t);
		$defaultmin = rsvpmaker_date('i',$t);
	}
	else {
		$p = explode(':',$end_time);
		$defaulthour = $p[0];
		$defaultmin = $p[1];
	}
}

$houropt = $minopt ="";

for($i=0; $i < 24; $i++)
	{
	$selected = ($i == $defaulthour) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	if($i == 0)
		$twelvehour = "12 a.m.";
	elseif($i == 12)
		$twelvehour = "12 p.m.";
	elseif($i > 12)
		$twelvehour = ($i - 12) ." p.m.";
	else		
		$twelvehour = $i." a.m.";

	$houropt .= sprintf('<option  value="%s" %s>%s / %s:</option>',$padded,$selected,$twelvehour,$padded);
	}

for($i=0; $i < 60; $i++)
	{
	$selected = ($i == $defaultmin) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	$minopt .= sprintf('<option  value="%s" %s>%s</option>',$padded,$selected,$padded);
	}
printf('<span class="end_time"> <select id="endhour%d" name="hour%s" >%s</select> <select id="endminutes%d" name="min%s" >%s</select> </span>',$index,$slug,$houropt,$index,$slug,$minopt);
}

function get_rsvp_dates($post_id, $obj = false)
{
global $wpdb, $rsvpdates;
if(empty($rsvpdates))
	cache_rsvp_dates(50);
if(!empty($rsvpdates[$post_id]))
{
	foreach($rsvpdates[$post_id] as $index => $datetime)
	{
	$drow['datetime'] = $datetime;
	$slug = ($index == 0) ? 'firsttime' : $datetime;
	$drow["duration"] = get_post_meta($post_id,'_'.$slug, true);
	$drow["end_time"] = get_post_meta($post_id,'_end'.$slug, true);
	if($obj)
		$drow = (object) $drow;
	$dates[] = $drow;		
	}
	return $dates;
}

if(empty($post_id))
	return array();

$wpdb->show_errors();
$sql = "SELECT * FROM ".$wpdb->postmeta." WHERE post_id=".$post_id." AND meta_key='_rsvp_dates' ORDER BY meta_value";
$results = $wpdb->get_results($sql);
$dates = array();
if($results)
foreach($results as $index => $row)
	{
	$drow = array();
	$datetime = $row->meta_value;
	$drow["meta_id"] = $row->meta_id;
	$drow["datetime"] = $datetime;
	$rsvpdates[$post_id][] = $datetime;
	$slug = ($index == 0) ? 'firsttime' : $datetime;
	$drow["duration"] = get_post_meta($post_id,'_'.$slug, true);
	$drow["end_time"] = get_post_meta($post_id,'_end'.$slug, true);
if($obj)
		$drow = (object) $drow;
	$dates[] = $drow;
	}
set_transient('rsvpmakerdates',$rsvpdates, HOUR_IN_SECONDS); 
return $dates;
}

function get_rsvp_event($where = '', $output = OBJECT)
{
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE (post_status='publish' OR post_status='draft') ";
	if(empty($where))
		$where = " a1.meta_value > '".get_sql_curdate()."' ";
	else
		$where = str_replace('datetime','a1.meta_value',$where);
	$sql .= ' AND '.$where.' ';
	$sql .= ' ORDER BY a1.meta_value ';
return $wpdb->get_row($sql);
}

function get_events_rsvp_on($limit = 0) {
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key='_rsvp_on' AND a2.meta_value=1 
	 WHERE a1.meta_value > '".get_sql_curdate()."' AND post_status='publish'
	 ORDER BY a1.meta_value ASC ";
	if($limit)
		$sql .= " LIMIT 0,".$limit;
	$wpdb->show_errors();
	return $wpdb->get_results($sql);
}

function get_next_rsvp_on() {
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key='_rsvp_on' AND a2.meta_value=1 
	 WHERE a1.meta_value > '".get_sql_curdate()."' AND post_status='publish'
	 ORDER BY a1.meta_value ASC ";
	$wpdb->show_errors();
	return $wpdb->get_row($sql);
}

function get_events_by_template($template_id, $order = 'ASC', $output = OBJECT) {
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key='_meet_recur' AND a2.meta_value=".$template_id." 
	 WHERE a1.meta_value > '".get_sql_curdate()."' AND (post_status='publish' OR post_status='draft')
	 ORDER BY a1.meta_value ".$order;
	$wpdb->show_errors();
	return $wpdb->get_results($sql, $output);
}

function rsvpmaker_next_by_template($template_id, $order = 'ASC', $output = OBJECT) {
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key='_meet_recur' AND a2.meta_value=".$template_id." 
	 WHERE a1.meta_value > '".get_sql_curdate()."' AND (post_status='publish' OR post_status='draft')
	 ORDER BY a1.meta_value ".$order;
	$wpdb->show_errors();
	return $wpdb->get_row($sql, $output);
}

function rsvpmaker_get_templates() {
	global $wpdb;
	$sql = "SELECT $wpdb->posts.*, meta_value as sked FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE meta_key='_sked' AND post_status='publish' GROUP BY $wpdb->posts.ID ORDER BY post_title";
return $wpdb->get_results($sql);
}

function get_next_rsvpmaker ($where = '', $offset_hours = 0) {
global $wpdb;
$wpdb->show_errors();
$startfrom = ($offset_hours) ? ' DATE_SUB("'.get_sql_now().'", INTERVAL '.$offset_hours.' HOUR) ' : "'".get_sql_now()."'";

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE a1.meta_value > ".$startfrom." AND post_status='publish' ";
	 if( !empty($where) )
	 	{
		$where = trim($where);
		$where = str_replace('datetime','a1.meta_value',$where);
		$sql .= ' AND '.$where.' ';
		}
	$sql .= ' ORDER BY a1.meta_value ';
	return $wpdb->get_row($sql);
}

function get_events_by_author ($author, $limit='', $status = "") {
global $wpdb;
$wpdb->show_errors();	
	if($status == 'publish')
		$status_sql = " AND post_status='publish' ";
	else
		$status_sql = " AND ($wpdb->posts.post_status='publish' OR $wpdb->posts.post_status='draft') ";
	
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE $wpdb->posts.post_author=$author AND a1.meta_value > '".get_sql_now()."' ".$status_sql;
	$sql .= ' ORDER BY a1.meta_value ';
	 if( !empty($limit) )
		$sql .= ' LIMIT 0,'.$limit.' ';
	if(!empty($_GET["debug_sql"]))
		echo $sql;
	return $wpdb->get_results($sql);
}

function get_future_events ($where = '', $limit='', $output = OBJECT, $offset_hours = 0) {
global $wpdb;
$wpdb->show_errors();
$startfrom = ($offset_hours) ? ' DATE_SUB("'.get_sql_now().'", INTERVAL '.$offset_hours.' HOUR) ' : '"'.get_sql_now().'"';

	$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE a1.meta_value > ".$startfrom." AND post_status='publish' ";
	 if( !empty($where) )
	 	{
		$where = trim($where);
		$where = str_replace('datetime','a1.meta_value',$where);
		$sql .= ' AND '.$where.' ';
		}
	$sql .= ' ORDER BY a1.meta_value ';
	 if( !empty($limit) )
		$sql .= ' LIMIT 0,'.$limit.' ';
	if(!empty($_GET["debug_sql"]))
		echo $sql;
	return $wpdb->get_results($sql, $output);
}

function count_future_events () {
global $wpdb;
$wpdb->show_errors();
	$sql = "SELECT COUNT(*)
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE a1.meta_value > '".get_sql_now()."' AND post_status='publish' ";
	return $wpdb->get_var($sql);
}

function count_recent_posts($blog_weeks_ago = 1) {
global $wpdb;
	$week_ago_stamp = rsvpmaker_strtotime('-'.$blog_weeks_ago.' week');
	$week_ago = date('Y-m-d H:i:s',$week_ago_stamp);
    $where = " AND post_date > '" . $week_ago . "'";
$wpdb->show_errors();
	$sql = "SELECT COUNT(*)
	 FROM ".$wpdb->posts."
	 WHERE post_type='post' AND post_status='publish' ".$where;
	return $wpdb->get_var($sql);
}

function get_past_events ($where = '', $limit='', $output = OBJECT) {
global $wpdb;
$wpdb->show_errors();
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime,date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE a1.meta_value < '".get_sql_now()."' AND (post_status='publish' OR post_status='draft') ";
	 if( !empty($where) )
	 	{
		$where = trim($where);
		$where = str_replace('datetime','a1.meta_value',$where);
		$sql .= ' AND '.$where.' ';
		}
	$sql .= ' ORDER BY a1.meta_value DESC';
	 if( !empty($limit) )
		$sql .= ' LIMIT 0,'.$limit.' ';
	return $wpdb->get_results($sql);
}

function get_events_dropdown () {
$options = '<optgroup label="'.__('Future Events','rsvpmaker').'">'."\n";
$future = get_future_events();
if(is_array($future))
foreach($future as $event)
	{
	if(get_post_meta($event->ID,'_rsvp_on',true))
	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',rsvpmaker_strtotime($event->datetime)));
	}
$options .= "<optiongroup>"."\n";

$options .= '<optgroup label="'.__('Recent Events','rsvpmaker').'">'."\n";
$past = get_past_events('',50);
if(is_array($past))
foreach($past as $event)
	{
	if(get_post_meta($event->ID,'_rsvp_on',true))
	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',rsvpmaker_strtotime($event->datetime)));
	}
$options .= "<optiongroup>"."\n";
return $options;
}

function is_rsvpmaker_future($event_id, $offset_hours = 0) {
global $wpdb;
if($offset_hours)
	$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND meta_value + INTERVAL $offset_hours HOUR > '".get_sql_now()."' AND post_id=".$event_id;
else
	$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND meta_value > '".get_sql_now()."' AND post_id=".$event_id;
$date = $wpdb->get_var($sql);
return (!empty($date));
}

function rsvpmaker_is_template ($post_id = 0) {
	global $post, $wpdb;
	if(!$post_id)
	{
		if(isset($post->ID))
			$post_id = $post->ID;
		else
			return false;
	}
	$dates = get_post_meta($post_id,'_rsvp_dates',true);
	if($dates) { // if there are dates, it's not a template
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_ske%' AND post_id=".$post_id);
		return false;
	}
	return get_template_sked($post_id);//get_post_meta($post_id,'_sked',true);
}

function has_template ($post_id = 0) {
	global $post;
	if(!$post_id)
	{
		if(isset($post->ID))
			$post_id = $post->ID;
		else
			return false;
	}
	return get_post_meta($post_id,'_meet_recur',true);
}

function cache_rsvp_dates($limit = 50) {
global $rsvpdates, $wpdb;
if(!empty($rsvpdates))
	return;//if some other process already retrieved the dates
$rsvpdates = get_transient('rsvpmakerdates');
if(!empty($rsvpdates))
	return;
$rsvpdates = array();
$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='_rsvp_dates' AND meta_value > '" . get_sql_now() . "' ORDER BY meta_value LIMIT 0, $limit";
$results = $wpdb->get_results($sql);
if($results)
foreach($results as $row) {
	$rsvpdates[$row->post_id][] = $row->meta_value;
}
set_transient('rsvpmakerdates',$rsvpdates, HOUR_IN_SECONDS); 
}

function get_rsvpmaker_payment_gateway () {
	global $post, $rsvp_options;
	$active_options = get_rsvpmaker_payment_options ();
	if(!empty($post->ID)) {
		$choice = get_post_meta($post->ID,'payment_gateway',true);
		if($choice)
			return $choice; // if specified for the event post
	}
	if(!empty($rsvp_options['payment_gateway']))
		{
			if($rsvp_options['payment_gateway'] == 'stripe')//legacy
				return 'Stripe via WP Simple Pay';
			return $rsvp_options['payment_gateway'];
		}
	//print_r($active_options);
	return $active_options[0]; // if no default specified, grab the first one on the list (Cash or Custom if no others set up)
}

function get_rsvpmaker_payment_options () {
global $rsvp_options;
$active_options = array('Cash or Custom','PayPal REST API','Stripe');
if(!empty($rsvp_options['paypal_config']))
	$active_options[] = 'PayPal (legacy)';
if(class_exists('Stripe_Checkout_Functions') && !empty($rsvp_options['stripe']))
	$active_options[] = 'Stripe via WP Simple Pay';
return $active_options;
}

function get_rsvpmaker_stripe_keys_all () {
	$keys = get_option('rsvpmaker_stripe_keys');
	if(empty($keys))
	{
		//older method of setting these options
		$pk = get_option('rsvpmaker_stripe_pk');
		if($pk) {
			if(strpos($pk,'test'))
			{
			$keys['sandbox_pk'] = $pk;
			$keys['sandbox_sk'] = get_option('rsvpmaker_stripe_sk');
			$keys['mode'] = 'sandbox';
			$keys['notify'] = get_option('rsvpmaker_stripe_notify');
			$keys['pk'] = '';
			$keys['sk'] = '';	
			}
			else
			{
			$keys['pk'] = $pk;
			$keys['sk'] = get_option('rsvpmaker_stripe_sk');
			$keys['mode'] = 'production';
			$keys['notify'] = get_option('rsvpmaker_stripe_notify');
			$keys['sandbox_pk'] = '';
			$keys['sandbox_sk'] = '';
			}
		update_option('rsvpmaker_stripe_keys',$keys);
		}
	}
	if(empty($keys))
		$keys = array('pk'=>'','sk'=>'','sandbox_pk'=>'','sandbox_sk'=>'','mode'=>'','notify' => '');
	return $keys;
}

function get_rsvpmaker_stripe_keys () {
	$keys = get_rsvpmaker_stripe_keys_all();
	if(!empty($keys['mode']) && ($keys['mode'] == 'production') && !empty($keys['sk']))
		return array('sk' => $keys['sk'], 'pk' => $keys['pk'], 'mode' => 'production', 'notify' => $keys['notify']);
	elseif(!empty($keys['mode']) && ($keys['mode'] == 'sandbox') && !empty($keys['sandbox_sk']))
		return array('sk' => $keys['sandbox_sk'], 'pk' => $keys['sandbox_pk'], 'mode' => 'sandbox', 'notify' => $keys['notify']);
	else
		return false;
}

function get_rspmaker_paypal_rest_keys () {
    $paypal_rest_keys = get_option('rsvpmaker_paypal_rest_keys');
    return $paypal_rest_keys;
}

if (version_compare(phpversion(), '7.1', '>='))
	include WP_PLUGIN_DIR."/rsvpmaker/inliner/init.php";
else
{
function rsvpmaker_inliner($content) {
		return $content;
}
	
}

function rsvpmaker_is_post_meta($post_id,$field) {
	global $wpdb;
	return $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE meta_key ='".$field."' AND post_id=".$post_id);
}

// a data integrity check run on wp_login. prevents null values from being passed to Gutenberg
function rsvpmaker_data_check() {
	global $rsvp_options, $wpdb;
	$last_data_check = (int) get_option('rsvpmaker_last_data_check2');
	$last_data_check = 0;
	if($last_data_check > time())
		{
			rsvpmaker_debug_log($last_data_check,'stopping rsvpmaker_data_check');
			return;
		}
	rsvpmaker_debug_log($last_data_check,'running rsvpmaker_data_check');
	update_option('rsvpmaker_last_data_check2',rsvpmaker_strtotime('+1 week'));
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value=1 WHERE meta_key='_rsvp_rsvpmaker_send_confirmation_email' AND meta_value='on' ");
	$wpdb->query("UPDATE $wpdb->posts SET post_type='rsvpmaker' WHERE post_title LIKE 'Form:%' AND post_type='post' ");
	$missing = 0;
	$found = 0;

	$defaults = array( 
		"calendar_icons" => "_calendar_icons",
		"rsvp_to" => "_rsvp_to",
		"rsvp_confirm" => "_rsvp_confirm", 
		"rsvpmaker_send_confirmation_email" => "_rsvp_rsvpmaker_send_confirmation_email",
		"confirmation_include_event" => "_rsvp_confirmation_include_event",
		"rsvp_instructions" => "_rsvp_instructions",
		"rsvp_count" => "_rsvp_count", 
		"rsvp_count_party" => "_rsvp_count_party", 
		"rsvp_yesno" => "_rsvp_yesno", 
		"rsvp_max" => "_rsvp_max",
		"login_required" => "_rsvp_login_required",
		"rsvp_captcha" => "_rsvp_captcha",
		"show_attendees" => "_rsvp_show_attendees",
		'convert_timezone' => '_convert_timezone',
		'add_timezone' => '_add_timezone',
		"rsvp_form" => "_rsvp_form"
		);
		$future = get_future_events();
		if(!empty($future) && is_array($future))
		foreach ($future as $post)
			$postlist[] = $post->ID;
		$templates = rsvpmaker_get_templates();
		if($templates)
		foreach ($templates as $post)
			$postlist[] = $post->ID;
		$allones = false;
		$missingfields = '';
	if(isset($postlist))
	foreach ($postlist as $post_id) {
		foreach($defaults as $index => $field) {
			$val = get_post_meta($post_id,$field,true);
			if(!rsvpmaker_is_post_meta($post_id,$field))
			{
				update_post_meta($post_id,$field,$rsvp_options[$index]);
				$missingfields .= $post_id.' missing: '.$field.', ';
				$missing++;
			}
			if(($field == '_rsvp_to') && is_numeric($val))
				$allones = true;
			else
				$found++;
			if(($val != 1) && ($val != '1'))
				$allones = false;
		}
		$form = get_post_meta($post_id,'_rsvp_form',true);
		if(!empty($form) && !is_numeric($form))
		{
			$data['post_title'] = 'Form:'.$post_id;
			$data['post_content'] = $form;
			$data['post_status'] = 'publish';
			$data['post_type'] = 'rsvpmaker';
			$data['post_author'] = $post->post_author;
			$form_id = wp_insert_post($data);
			update_post_meta($form_id,'_rsvpmaker_special','RSVP Form');
			update_post_meta($post_id,'_rsvp_form',$form_id);
			$missingfields .= ' fixed non numeric form';
		}
		if(!empty($missingfields))
			rsvpmaker_debug_log($missingfields,$missing.' missing fields');
	}
	if($allones)
		{
			rsvpmaker_debug_log($allones,'allones = rsvpmaker_set_defaults_all');
			rsvpmaker_set_defaults_all();
		}
}

function rsvpmaker_set_defaults_all($display = false) {
	global $rsvp_options, $wpdb;

	$defaults = array( 
		"calendar_icons" => "_calendar_icons",
		"rsvp_to" => "_rsvp_to",
		"rsvp_confirm" => "_rsvp_confirm", 
		"rsvpmaker_send_confirmation_email" => "_rsvp_rsvpmaker_send_confirmation_email",
		"confirmation_include_event" => "_rsvp_confirmation_include_event",
		"rsvp_instructions" => "_rsvp_instructions",
		"rsvp_count" => "_rsvp_count", 
		"rsvp_count_party" => "_rsvp_count_party", 
		"rsvp_yesno" => "_rsvp_yesno", 
		"rsvp_max" => "_rsvp_max",
		"login_required" => "_rsvp_login_required",
		"rsvp_captcha" => "_rsvp_captcha",
		"show_attendees" => "_rsvp_show_attendees",
		'convert_timezone' => '_convert_timezone',
		'add_timezone' => '_add_timezone',
		"rsvp_form" => "_rsvp_form"
		);
		$postlist = array();
		$future = get_future_events();
		if($future)
		foreach ($future as $post)
			$postlist[] = $post->ID;
		$templates = rsvpmaker_get_templates();
		if($templates)
		foreach ($templates as $post)
			$postlist[] = $post->ID;
	$output = '';
	if($postlist)
	foreach ($postlist as $post_index => $post_id) {
		foreach($defaults as $index => $field) {
			update_post_meta($post_id,$field,$rsvp_options[$index]);
			if($display && ($post_index == 0))
				$output .= '<div>'.$field.': '.$rsvp_options[$index].'</div>';
		}
	}
	return $output;
}

function rsvpmaker_set_default_field($index, $display = false) {
	global $rsvp_options, $wpdb;

	$defaults = array( 
		"calendar_icons" => "_calendar_icons",
		"rsvp_to" => "_rsvp_to",
		"rsvp_confirm" => "_rsvp_confirm", 
		"rsvpmaker_send_confirmation_email" => "_rsvp_rsvpmaker_send_confirmation_email",
		"confirmation_include_event" => "_rsvp_confirmation_include_event",
		"rsvp_instructions" => "_rsvp_instructions",
		"rsvp_count" => "_rsvp_count", 
		"rsvp_count_party" => "_rsvp_count_party", 
		"rsvp_yesno" => "_rsvp_yesno", 
		"rsvp_max" => "_rsvp_max",
		"login_required" => "_rsvp_login_required",
		"rsvp_captcha" => "_rsvp_captcha",
		"show_attendees" => "_rsvp_show_attendees",
		'convert_timezone' => '_convert_timezone',
		'add_timezone' => '_add_timezone',
		"rsvp_form" => "_rsvp_form"
		);
		$postlist = array();
		$future = get_future_events();
		if($future)
		foreach ($future as $post)
			$postlist[] = $post->ID;
		$templates = rsvpmaker_get_templates();
		if($templates)
		foreach ($templates as $post)
			$postlist[] = $post->ID;
	$output = '';
	$field = $defaults[$index];
	echo '<h3>Index/Field: '.$index.':'.$field.'</h3>';
	if($postlist)
	foreach ($postlist as $post_index => $post_id) {
		update_post_meta($post_id,$field,$rsvp_options[$index]);
		if($display && ($post_index == 0))
			$output .= '<div>'.$field.': '.$rsvp_options[$index].'</div>';
	}
	return $output;
}

function rsvpmaker_cleanup () {
	global $wpdb;
	$defaults = array( 
		"calendar_icons" => "_calendar_icons",
		"rsvp_to" => "_rsvp_to",
		"rsvp_confirm" => "_rsvp_confirm", 
		"rsvpmaker_send_confirmation_email" => "_rsvp_rsvpmaker_send_confirmation_email",
		"confirmation_include_event" => "_rsvp_confirmation_include_event",
		"rsvp_instructions" => "_rsvp_instructions",
		"rsvp_count" => "_rsvp_count", 
		"rsvp_count_party" => "_rsvp_count_party", 
		"rsvp_yesno" => "_rsvp_yesno", 
		"rsvp_max" => "_rsvp_max",
		"login_required" => "_rsvp_login_required",
		"rsvp_captcha" => "_rsvp_captcha",
		"show_attendees" => "_rsvp_show_attendees",
		'convert_timezone' => '_convert_timezone',
		'add_timezone' => '_add_timezone',
		"rsvp_form" => "_rsvp_form"
		);
?>
<h1>RSVPMaker Cleanup</h1>
<?php

if(isset($_POST['reset_defaults'])) {
	$result = rsvpmaker_set_defaults_all(true);
	echo '<div class="notice notice-success"><p>Defaults applied to all templates and future events</p></div>';
	echo $result;
}
if(isset($_POST['default_field'])) {
	$result = '';
	foreach($_POST['default_field'] as $field)
		{
			$result .= rsvpmaker_set_default_field($field,true);
		}
	echo '<div class="notice notice-success"><p>Defaults applied to all templates and future events for fields shown below.</p></div>';
	echo $result;
}

if(isset($_POST['older_than']))
{
$older = (int) $_POST['older_than'];
if(!isset($_POST['confirm']))
{
?>
<form method="post" action="<?php echo admin_url('tools.php?page=rsvpmaker_cleanup') ?>">
<input type="hidden" name="confirm" value="1" />
<input type="hidden" name="older_than" value="<?php echo $older; ?>" /> 
<?php submit_button('Confirm Delete') ?>
</form>
<div>Preview</div>
<?php	
}
	$sql = "SELECT DISTINCT ID as postID, $wpdb->posts.*, a1.meta_value as datetime,date_format(a1.meta_value,'%M %e, %Y') as date
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 WHERE a1.meta_value < DATE_SUB('".get_sql_now()."',INTERVAL $older DAY) AND (post_status='publish' OR post_status='draft') ";
	//echo $sql;
	$results = $wpdb->get_results($sql);
	if(is_array($results))
	foreach($results as $event)
	{
		$deleted = '';
		if(isset($_POST['confirm']))
		{
			wp_delete_post($event->ID,true);
			$deleted = '<span style="color:red">Deleted</span> ';
		}
		printf('<div>%s %s %s</div>',$deleted,$event->post_title,$event->date);
	}
}
if(!isset($_POST['older_than']))
{
?>
<h2><?php _e('Remove Past Events from Database','rsvpmaker'); ?></h2>
<form method="post" action="<?php echo admin_url('tools.php?page=rsvpmaker_cleanup') ?>">
<?php _e('Delete events older than','rsvpmaker'); ?> <input type="text" name="older_than" value="30" /> <?php _e('days','rsvpmaker'); ?> 
<?php submit_button('Delete') ?>
</form>

<h2><?php _e('Apply Defaults','rsvpmaker'); ?></h2>
<form method="post" action="<?php echo admin_url('tools.php?page=rsvpmaker_cleanup') ?>">
<p><?php _e('Apply default values from the RSVPMaker Settings screen to all templates and future events','rsvpmaker'); ?></p>
<div><input id="all" type="checkbox" name="reset_defaults" value="1" checked="checked" /> <?php _e('All fields','rsvpmaker'); ?></div>
<?php 
foreach($defaults as $index => $field)
printf('<div><input class="default_field" type="checkbox" name="default_field[]" value="%s" />%s</div>',$index,$field);
?>
<?php submit_button('Reset') ?>
</form>
<script>
jQuery(document).ready(function( $ ) {
$(document).on( 'click', '.default_field', function() {
	$("#all").prop("checked", false);
});

});
</script>


<?php
}//end initial form
}

function rsvp_simple_price($post_id) {
	$per=get_post_meta($post_id,"_per",true);
	$price = (empty($per['price'][0])) ? '' : $per['price'][0];
	return $price;
}
	
function rsvp_simple_price_label($post_id) {
	$per = get_post_meta($post_id,'_per',true);
	$label = (empty($per['unit'][0])) ? __('Tickets','rsvpmaker') : $per['unit'][0];
	return $label;
}

function rsvp_complex_price($post_id) {
	$per = get_post_meta($post_id,'_per',true);
	if(empty($per))
		return '';
	$complexity = '';
	$complex = false;
	$labels = $prices = 0;
	foreach($per as $index => $pricearray) {
		//$complexity .= $index.': '.var_export($pricearray, true).', ';
		if($index == 'unit')
			$labels = sizeof($pricearray);
		if($index == 'price')
			{
				$prices = sizeof($pricearray);
				foreach($pricearray as $index => $price)
					$complexity .= $per['unit'][$index].': '.$price.', ';
			}
	}
	if(isset($per['price_deadline']))
	{
		$complex = true;
		$complexity .= __('Pricing deadlines set in RSVP / Event Options','rsvpmaker').'. ';
	}
	if(isset($per['price_multiple']))
	{
		$complex = true;
		$complexity .= __('Multiple admissions specified in RSVP / Event Options','rsvpmaker').'. ';
	}

	if($prices > 1)
		$complex = true;
	if(!$complex)
		return '';
	return $complexity;
}

function get_rsvp_post_metadata($null, $post_id, $meta_key, $single) {
	global $wpdb, $current_user;
	$content = '';
	if($meta_key == 'simple_price')
		$content = rsvp_simple_price($post_id);
	if($meta_key == 'simple_price_label')
		$content = rsvp_simple_price_label($post_id);
	//fix for some older posts
	$date_fields = array('_firsttime','_endfirsttime','_day_of_week','_week_of_month','_template_start_hour','_template_start_minutes','complex_template');
	if(in_array($meta_key,$date_fields))
		{
			$datetime = get_rsvp_date($post_id);
			$sked = get_template_sked($post_id);//get_post_meta($post_id,'_sked',true);
			if($datetime) {
				if($meta_key == '_firsttime')
				{
					$content = get_post_meta($post_id, '_'.$datetime,true);
				}
				elseif(is_admin() && ($meta_key == '_endfirsttime'))
				{
					$end = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_endfirsttime' and post_id=$post_id ");
					if(empty($end))
					{
						//default value for editor
						$content = rsvpmaker_date('H:i',rsvpmaker_strtotime($datetime .' +1 hour'));
					}
				}
			} // end event with date
			elseif ($sked) {
				$week = (empty($sked["week"]) ) ? 0 : $sked["week"]; 
				$dayofweek = (empty($sked["dayofweek"]) ) ? '': $sked["dayofweek"];
				$hour = (empty($sked["hour"]) ) ? '00': $sked["hour"];
				$minutes = (empty($sked["minutes"]) ) ? '00': $sked["minutes"];
				$duration = (empty($sked["duration"]) ) ? '': $sked["duration"];
				if($meta_key == '_firsttime')
				{
					$content = $duration;
				}
				elseif($meta_key == '_endfirsttime')
				{
					if(empty($sked['end']))
					{
						$t = rsvpmaker_strtotime($sked['hour'].':'.$sked['minutes'].' +1 hour');
						$content = rsvpmaker_date('H:i',$t);
					}
					else {
						$content = $sked["end"];
					}
				}				
				elseif($meta_key == '_template_start_hour')
				{
					$content = $hour;
				}
				elseif($meta_key == '_template_start_minutes')
				{
					$content = $minutes;
				}
				elseif($meta_key == '_day_of_week')
				{
					$content = (empty($dayofweek[0])) ? 0 : $dayofweek[0];
				}
				elseif($meta_key == '_week_of_month')
				{
					$content = (empty($week[0])) ? '' : $week[0];
				}
				elseif($meta_key == 'complex_template')
				{
					$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));
					$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));
					if((empty($dayofweek)) || (empty($week)) || !is_array($dayofweek)  || !is_array($week) )
						return $null;
					if((sizeof($dayofweek) > 1) || (sizeof($week) > 1) )
					{
						foreach($week as $var)
							$content .= $weekarray[$var].' ';
						foreach($dayofweek as $var)
							$content .= $dayarray[$var].' ';
						$content .= $hour.':'.$minutes;
					}
				}
			}
		}
	if($content)
		return array($content);
	
	return $null; // don't alter
}

add_filter('get_post_metadata','get_rsvp_post_metadata',10,4);

function update_rsvp_post_metadata($check,$post_id,$meta_key,$meta_value) {
	if(($meta_key == 'simple_price') || ($meta_key == 'simple_price_label')) {
		$per = get_post_meta($post_id,'_per',true);
		if(empty($per))
			{
				$per = array();
				$per['unit'][0] = 'Tickets';
				$per['price'][0] = '';
			}
		elseif(empty($per['price']))
			$per['price'][0] = '';
		if($meta_key == 'simple_price') {
			$per['price'][0] = $meta_value;
		}
		if($meta_key == 'simple_price_label') {
			$per['unit'][0] = $meta_value;
		}
		update_post_meta($post_id,'_per',$per);
		return true; //no need to create a field for one of these values
	}


	$date_fields = array('_rsvp_dates','_firsttime','_endfirsttime','_day_of_week','_week_of_month','_template_start_hour','_template_start_minutes','complex_template');
	if(in_array($meta_key,$date_fields) && ($sked = get_template_sked($post_id) ) && is_array($sked) )
		{
			$week = $sked["week"];
			$dayofweek = $sked["dayofweek"];
			$hour = $sked["hour"];
			$minutes = $sked["minutes"];
			$duration = $sked["duration"];
			if($meta_key == '_firsttime')
			{
				rsvpmaker_debug_log($meta_value,$meta_key);
				$sked["duration"] = $meta_value;
			}
			elseif($meta_key == '_endfirsttime')
			{
				rsvpmaker_debug_log($meta_value,$meta_key);
				$sked['end'] = $meta_value;
			}				
			elseif($meta_key == '_template_start_hour')
			{
				$sked["hour"] = $meta_value;
				rsvpmaker_debug_log($meta_value,$meta_key);
			}
			elseif($meta_key == '_template_start_minutes')
			{
				$sked["minutes"] = $meta_value;
				rsvpmaker_debug_log($meta_value,$meta_key);
			}
			elseif($meta_key == '_day_of_week')
			{
				$sked["dayofweek"] = array($meta_value);
				rsvpmaker_debug_log($meta_value,$meta_key);
			}
			elseif($meta_key == '_week_of_month')
			{
				rsvpmaker_debug_log($meta_value,$meta_key);
				$sked["week"] = array($meta_value);
			}
		new_template_schedule($post_id,$sked);
		//update_post_meta($post_id,'_sked',$sked);
		rsvpmaker_debug_log($sked,'modified sked');
		return true; //short circuit regular meta update
		}
	rsvpmaker_debug_log($meta_value,$meta_key.' - update_rsvp_post_metadata');
	return $check;
}

add_filter('update_post_metadata','update_rsvp_post_metadata',10,4);

function rsvpmaker_check_privacy_page() {
	$privacy_page = get_option('wp_page_for_privacy_policy');
	if($privacy_page) {
		$privacy_post = get_post($privacy_page);
		if(empty($privacy_post) || ($privacy_post->post_status != 'publish') )
			$privacy_page = 0;
	}
	return $privacy_page;
}

function get_day_array() {
	return Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
}
function get_week_array() {
return Array("Varies","First","Second","Third","Fourth","Last","Every");
}
	
function get_template_sked($post_id) {
	global $wpdb;
	$week_array = get_week_array();
	$day_array = get_day_array();
	$singles = array('hour','minutes','duration','stop');
	$newsked = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_sked_%' ");
	if($newsked) {
		//retrieved new format
		$dayofweek = array();
		$week = array();
		foreach($newsked as $row) {
			$key = str_replace('_sked_','',$row->meta_key);
			if(in_array($key,$day_array) && $row->meta_value)
				$dayofweek[] = array_search($key,$day_array);
			elseif(in_array($key,$week_array) && $row->meta_value)
				$week[] = array_search($key,$week_array);
			elseif(($row->meta_key == '_sked_minutes') && ($row->meta_value == ''))
				{	//fix for corrupted record
					$sked = get_post_meta($post_id,'_sked',true);
					$row->meta_value = (empty($sked['minutes'])) ? '00' : $sked['minutes'];
					update_post_meta($post_id,'_sked_minutes',$row->meta_value);
				}
			$sked[$key] = $row->meta_value;
		}
		sort($week);
		sort($dayofweek);
		$sked['dayofweek'] = $dayofweek;
		//if every is checked, ignore other checks
		$sked['week'] = $week;
		if(sizeof($week) > 1)
		{
			//if every week, other weeks don't count
			if(in_array(6,$week))
				{
				$sked['week'] = array(6);
				foreach($week_array as $index => $value)
					{
						if($index != 6)
							update_post_meta($post_id,'_sked_'.$value,false);
					}
				}
			if(in_array(0,$week)) //if any other value is set, Varies doesn't make sense
				update_post_meta($post_id,'_sked_Varies',false);
		} 
		return $sked;
	}
	else {
		$sked = get_post_meta($post_id,'_sked',true);
		if($sked) {
			//upgrade it
			$sked = new_template_schedule($post_id,$sked);
			return $sked;
		}
		else
			return false;//not a template
	}
}

function new_template_schedule($post_id,$template) {
	if(is_array($template["week"]))
	{
		$weeks = $template["week"];
		$dows = $template["dayofweek"];
	}
	else
	{
		$weeks[0] = $template["week"];
		$dows[0] = (isset($template["dayofweek"])) ? $template["dayofweek"] : 0;
	}
	$hour = (isset($template['hour'])) ? $template['hour'] : '00';	
	$minutes = (isset($template['minutes'])) ? $template['minutes'] : '00';	
	$duration = (isset($template['duration'])) ? $template['duration'] : '';	
	$stop = (isset($template['stop'])) ? $template['stop'] : '';	
	$new_template_schedule = build_template_schedule($post_id,$dows,$weeks,$hour,$minutes,$duration,$stop);
	foreach($new_template_schedule as $label => $value) {
		$label = '_sked_'.$label;
		$value = $value;
		update_post_meta($post_id,$label,$value);
	}
	$new_template_schedule['week'] = $weeks;
	$new_template_schedule['dayofweek'] = $dows;
	update_post_meta($post_id,'_sked',$new_template_schedule);
	return $new_template_schedule;
}

function build_template_schedule($post_id,$dows,$weeks,$hour,$minutes,$duration,$stop) {
		$weekarray = get_week_array();
		foreach($weekarray as $index => $label)
		{
			//printf('<p>check %s %s</p>',$index,$label);
			$atomic_sked[$label] = in_array($index,$weeks);
		}
		$dayarray = get_day_array();
		foreach($dayarray as $index => $label)
		{
			//printf('<p>check %s %s</p>',$index,$label);
			$atomic_sked[$label] = in_array($index,$dows);
		}
	$atomic_sked['hour'] = (empty($hour)) ? '00' : $hour;
	$atomic_sked['minutes'] = (empty($minutes)) ? '00' : $minutes;
	$atomic_sked['stop'] = $stop;
	$atomic_sked['duration'] = $duration;
	return $atomic_sked;
}

function default_gateway_check($chosen_gateway) {

	if(empty($chosen_gateway) || ($chosen_gateway == 'Cash or Custom')) {
		$paypal_rest_keys = get_option('rsvpmaker_paypal_rest_keys');
		$stripe_keys = get_rsvpmaker_stripe_keys_all ();
		$gateway_set = '';
		if(!empty($paypal_rest_keys))
			{
			foreach($paypal_rest_keys as $index => $value) {
				if($index == 'sandbox')
					continue;
				if(!empty($value)) {
					$gateway_set = 'PayPal';
					break;
				}
			}
			}
		if(!empty($stripe_keys))
		{
			foreach($stripe_keys as $index => $value) {
				if($index == 'mode')
					continue;
				if(!empty($value)) {
					if($gateway_set == 'PayPal')
						{	
							$gateway_set = 'PayPal or Stripe';
							break;
						}
					else {
						$gateway_set = 'Stripe';
						break;
					}
				}
			}
		}
	}
	if(!empty($gateway_set))
	return sprintf('<p style="color: red; font-weight: bold;">%s %s?</p>',__('Do you want to set the Preferred Payment Gateway to','rsvpmaker'),$gateway_set);
}

function get_rsvp_id ($email = '') {
global $post, $wpdb;
$rsvp_id = 0;
if(isset($_GET['rsvp']))
	$rsvp_id = (int) $_GET['rsvp'];
elseif(isset($_GET['update']))
	$rsvp_id = (int) $_GET['update'];
elseif(isset($_COOKIE['rsvp_for_'.$post->ID]) && !$email_context)
	$rsvp_id = (int) $_COOKIE['rsvp_for_'.$post->ID];
elseif(is_user_logged_in() && !empty($email)) {
	$sql = 'SELECT id FROM '.$wpdb->prefix.'rsvpmaker WHERE email LIKE "'.$email.'" AND event='.$post->ID.' ORDER BY id DESC';
	$rsvp_id = (int) $wpdb->get_var($sql);	
	}
return $rsvp_id;
}

function get_rsvp_email() {
	global $post, $wpdb;
	$email = '';
	global $current_user;
	if($_GET['e'])
		{
			$email = $_GET['e'];
		}
	elseif(isset($_COOKIE['rsvp_for_'.$post->ID]) && !$email_context)
		{
			$rsvp_id = (int) $_COOKIE['rsvp_for_'.$post->ID];
			$sql = 'SELECT email FROM '.$wpdb->prefix.'rsvpmaker WHERE id='.$rsvp_id;
			$email = $wpdb->get_var($sql);	
		}
	elseif(is_user_logged_in()) {
		$email = $current_user->user_email;
	}
	if ( $email && !filter_var($email, FILTER_VALIDATE_EMAIL) )
		$email = '';
	return $email;		
}

?>