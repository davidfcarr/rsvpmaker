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

function get_rsvpmaker_event($post_id)
{

	if(empty($post_id))
		return;
	
	global $wpdb, $rsvpdates;
	
	$wpdb->show_errors();
	
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=".$post_id;
	return $wpdb->get_row($sql);	
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

$sql = "SELECT date FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=".$post_id;
$date = $wpdb->get_var($sql);
if($date == '0000-00-00 00:00:00') {
	$wpdb->query("DELETE FROM ".$wpdb->prefix."rsvpmaker_event WHERE event=".$post_id );
	return;
}
return $date;

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

echo '<p><label>'.__('End Time','rsvpmaker').'</label> <select id="end_time_type" name="end_time_type" class="end_time_type" >';
?>
<option value=""><?php echo __('Not set (optional)','rsvpmaker');?></option>

<option value="set" <?php if($duration_type == 'set') echo ' selected="selected" '; ?> ><?php echo __("Set end time",'rsvpmaker');?></option>

<option value="allday" <?php if($duration_type == 'allday') echo ' selected="selected" '; ?>><?php echo __("All day/time not shown",'rsvpmaker');?></option>

<?php
for($i=2; $i < 8; $i++)
	{
		$multi = 'multi|'.$i;
		$s = ($duration_type == $multi) ? ' selected="selected" ' : '';
		printf('<option value="%s" %s>%s</option>',$multi,$s,$i.' '.__("days/time not shown",'rsvpmaker'));
	}

echo '</select>';
if(empty($end_time) && !empty($start_time))
		$t = rsvpmaker_strtotime($start_time.' +1 hour');
else {
	if(empty($end_time))
	{

		$start_time = $rsvp_options['defaulthour'].':'.$rsvp_options['defaultmin'];
		$t = rsvpmaker_strtotime($start_time.' +1 hour');
	}
	else {
		$t = rsvpmaker_strtotime('2020-01-01 '.$end_time);
	}
}

printf('<span id="end_time" class="end_time"> <input type="text" class="free-text-end" id="free-text-end" size="8" value="%s"> or <input name="rsvp_sql_end" type="text" class="sql-end" id="sql-end" size="5" value="%s">
<span id="end_time_error"></span> </span>',rsvpmaker_strftime('%l:%M %p',$t),rsvpmaker_date('H:i',$t));
echo '</p>';
}



function get_rsvp_dates($post_id, $obj = false)

{

global $wpdb, $rsvpdates;

if(empty($rsvpdates))

	cache_rsvp_dates(50);

if(!empty($rsvpdates[$post_id]) && (count($rsvpdates[$post_id]) == 1) )

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
	if(strpos($drow["duration"],'|')) //multidate
	{
		$parts = explode('|',$drow["duration"]);
		$limit = (int) $parts[1];
		$endtime = get_post_meta($post_id,'_endfirsttime',true);
		$endtime = empty($endtime) ? '00:00:00' : $endtime.':00';
		for($i=1; $i < $limit; $i++) {
			$drow["datetime"] = date('Y-m-d '.$endtime,strtotime($drow["datetime"].' +1 day'));
			$dates[] = $drow;
		}
		update_post_meta($post_id,'_rsvp_end_date',$drow['datetime']);
	}

	}

	return $dates;

}

if(empty($post_id))

	return array();

$wpdb->show_errors();

$sql = "SELECT * FROM ".$wpdb->postmeta." WHERE post_id=".$post_id." AND meta_key='_rsvp_dates' ORDER BY meta_value";

$results = $wpdb->get_results($sql);
if(empty($results))
	return array();
$row = $results[0];
$index = 0;
$count = sizeof($results);
if($count > 1)
{ //upgrade data model
	$datetime = $row->meta_value;
	delete_post_meta($post_id,'_rsvp_dates');
	update_post_meta($post_id,'_rsvp_dates',$datetime);
	update_post_meta($post_id,'_firsttime','multi|'.$count);
}
$dates = array();

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

	if(strpos($drow["duration"],'|')) //multidate
	{
		$parts = explode('|',$drow["duration"]);
		$limit = (int) $parts[1];
		for($i=1; $i < $limit; $i++) {
			$drow["datetime"] = date('Y-m-d H:i:s',strtotime($drow["datetime"].' +1 day'));
			$dates[] = $drow;
		}
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

function is_rsvpmaker_deadline_future($post_id) {
	$deadline = (int) get_post_meta($post_id,"_rsvp_deadline",true);
	if(!$deadline)
	{
		$event = get_rsvpmaker_event($post_id);
		$deadline = rsvpmaker_strtotime($event->enddate);
	}
	return $deadline > time();
}

function get_next_rsvp_on() {
	$events = get_future_events_by_meta (array('meta_key' => '_rsvp_on','meta_value' => 1), 1);
	return $events[0];
}

function get_events_by_template($template_id, $order = 'ASC', $output = OBJECT) {

//return rsvpmaker_upcoming_data(array('meta_key' => '_meet_recur', 'meta_value' => $template_id));

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

function rsvpmaker_set_template_defaults($post_id) {
	global $rsvp_options;
	update_post_meta($post_id,'_sked_Varies',1);
	update_post_meta($post_id,'_sked_First','');
	update_post_meta($post_id,'_sked_Second','');
	update_post_meta($post_id,'_sked_Third','');
	update_post_meta($post_id,'_sked_Fourth','');
	update_post_meta($post_id,'_sked_Last','');
	update_post_meta($post_id,'_sked_Every','');
	update_post_meta($post_id,'_sked_Sunday','');
	update_post_meta($post_id,'_sked_Monday','');
	update_post_meta($post_id,'_sked_Tuesday','');
	update_post_meta($post_id,'_sked_Wednesday','');
	update_post_meta($post_id,'_sked_Thursday','');
	update_post_meta($post_id,'_sked_Friday','');
	update_post_meta($post_id,'_sked_Saturday','');
	update_post_meta($post_id,'_sked_hour',str_pad($rsvp_options['defaulthour'],2,'0',STR_PAD_LEFT));
	update_post_meta($post_id,'_sked_minutes',str_pad($rsvp_options['defaultmin'],2,'0',STR_PAD_LEFT));
	update_post_meta($post_id,'_sked_stop','');
	update_post_meta($post_id,'_sked_duration','');
}

function rsvpmaker_get_templates($criteria = '') {
	global $wpdb;
	$templates = array();
	$sql = "SELECT $wpdb->posts.*, meta_value as sked FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE BINARY `meta_key` REGEXP '_sked_[A-Z].+' and meta_value AND post_status='publish' GROUP BY $wpdb->posts.ID ORDER BY post_title".$criteria;
	$results = $wpdb->get_results($sql);
	foreach($results as $template) {
		$templates[$template->ID] = $template;
		delete_post_meta($template->ID,'_rsvp_dates');
		delete_post_meta($template->ID,'_meet_recur');
	}
	delete_transient('rsvpmakerdates');//clear date cache
return $templates;
}

function get_next_rsvpmaker () {
$events = get_future_events('',1);
return $events[0];
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



function rsvpmaker_week_of_events () {

	global $wpdb;

	$wpdb->show_errors();

	$startfrom = '"'.get_sql_now().'"';

	$enddate = ' DATE_ADD(NOW(),INTERVAL 1 WEEK) ';

		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date

		 FROM ".$wpdb->posts."

		 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

		 WHERE a1.meta_value > ".$startfrom." AND a1.meta_value < $enddate AND post_status='publish' ";

		$sql .= ' ORDER BY a1.meta_value ';

		return $wpdb->get_results($sql);

}



function rsvpmaker_week_reminders () {

	global $wpdb;

	$wpdb->show_errors();

	$startfrom = '"'.get_sql_now().'"';

	$enddate = ' DATE_ADD(NOW(),INTERVAL 1 WEEK) ';

		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, a1.meta_value as datetime,

		a2.meta_key as slug, a2.meta_value as reminder_post_id

		 FROM ".$wpdb->posts."

		 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'

		 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key LIKE '_rsvp_reminder_msg_%'

		 WHERE a1.meta_value > ".$startfrom." AND a1.meta_value < $enddate AND post_status='publish' ";

		$sql .= ' ORDER BY a1.meta_value ';

		return $wpdb->get_results($sql);

}

function rsvpmaker_reminders_nudge () {

	$posts_with_reminders = rsvpmaker_week_reminders();

	if($posts_with_reminders)

	{

		foreach($posts_with_reminders as $post_with_reminder) {

			$parts = explode('_',$post_with_reminder->slug);

			$hours = $parts[4];

			if(isset($_GET['debug']))

				printf('<div>%s - %s - %s</div>',$hours, $post_with_reminder->datetime, $post_with_reminder->ID);

			rsvpmaker_reminder_cron($hours, $post_with_reminder->datetime, $post_with_reminder->ID, $hours, $post_with_reminder->datetime);

			}

	}

}

function rsvpmaker_event_dates_table_update($new = false) {
	global $wpdb;
	if(!$new) {
		$haserror = $wpdb->get_var("SELECT event FROM ".$wpdb->prefix."rsvpmaker_event WHERE enddate IS NULL OR enddate='0000-00-00 00:00:00' ");
		if(!$haserror)
			return;
	}
	$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates' AND post_status='publish' ";
	$events = $wpdb->get_results($sql);
	$log = '';
	foreach($events as $event) {
		$date = $event->datetime;
		$type = get_post_meta($event->ID,'_firsttime',true);
		$end =  get_post_meta($event->ID,'_endfirsttime',true);
		if(empty($end))
			$enddatetime = date('Y-m-d H:i:s',strtotime($date.' +1 hour'));		
		elseif(strpos($type,'|')) {
			$p = explode('|',$type);
			$days = $p[1] - 1;
			$enddatetime = date('Y-m-d ',strtotime($date.' +'.$days.' days')).$end.':00';		
		}
		elseif($type == 'set')
			$enddatetime = date('Y-m-d',strtotime($date)).' '.$end.':00';
		else
			$enddatetime = date('Y-m-d H:i:s',strtotime($date.' +1 hour'));
		$enddatetime = fix_enddatetime($enddatetime,$date,$event->ID);
		$title = get_the_title($event->ID);
		$sql = $wpdb->prepare('REPLACE INTO '.$wpdb->prefix.'rsvpmaker_event SET event=%d, post_title=%s, date=%s, enddate=%s, display_type=%s',$event->ID,$title,$date,$enddatetime,$type);
		$wpdb->query($sql);
		$log .= $sql."\n";
	}
	rsvpmaker_debug_log($log,'table update sql');
	update_option('rsvpmaker_event_table',time());
}

function sync_rsvpmaker_event_dates ($meta_id, $object_id, $meta_key, $meta_value) {
	global $wpdb, $post;
	if($meta_key == '_endfirsttime') {
	$end = $meta_value;
	$type = get_post_meta($object_id,'_firsttime',true);
	$date = get_post_meta($object_id,'_rsvp_dates',true);
	if(strpos($type,'|')) {
		$p = explode('|',$type);
		$enddatetime = date('Y-m-d ',strtotime($date.' +'.$p[1].' days'));		
	}
	else
		$enddatetime = preg_replace('/\d{2}:\d{2}:\d{2}/','',$date);
	$enddatetime .= $end.':00';
	$enddatetime = fix_enddatetime($enddatetime,$date, $object_id);
	$title = empty($post->post_title) ? get_the_title($object_id) : $post->post_title;
	$sql = $wpdb->prepare('REPLACE INTO '.$wpdb->prefix.'rsvpmaker_event SET event=%d, post_title=%s, date=%s, enddate=%s, display_type=%s',$object_id,$title,$date,$enddatetime,$type);
	//rsvpmaker_debug_log($sql,'sync sql2 endfirsttime');
	$wpdb->query($sql);
	}
	elseif($meta_key == '_rsvp_dates') {
		$date = $meta_value;
		$end = get_post_meta($object_id,'_endfirsttime',true);
		rsvpmaker_debug_log($end,'end from meta');
		$type = get_post_meta($object_id,'_firsttime',true);
		if(empty($end)) 
			$end = date('H:i',strtotime($date.' +1 hour'));
		if(strpos($type,'|')) {
			$p = explode('|',$type);
			$days = $p[1] - 1;
			$enddatetime = date('Y-m-d ',strtotime($date.' +'.$days.' days'));
		}
		else
			$enddatetime = preg_replace('/\d{2}:\d{2}:\d{2}/','',$date);
		$enddatetime .= $end.':00';
		$enddatetime = fix_enddatetime($enddatetime, $date, $object_id);
		$title = empty($post->post_title) ? get_the_title($object_id) : $post->post_title;
		$sql = $wpdb->prepare('REPLACE INTO '.$wpdb->prefix.'rsvpmaker_event SET post_title=%s, date=%s, enddate=%s, display_type=%s, event=%d',$title,$date,$enddatetime,$type,$object_id);
		//rsvpmaker_debug_log($sql,'sync sql2 date');
		$wpdb->query($sql);
	}
	elseif($meta_key == '_firsttime') {
		$type = $meta_value;
		$date = get_post_meta($object_id,'_rsvp_dates',true);
		$end = get_post_meta($object_id,'_endfirsttime',true);
		if(empty($end)) 
			$end = date('H:i',strtotime($date.' +1 hour'));
		if(strpos($type,'|')) {
			$p = explode('|',$type);
			$days = $p[1] - 1;
			$enddatetime = date('Y-m-d ',strtotime($date.' +'.$days.' days'));
		}
		else
			$enddatetime = preg_replace('/\d{2}:\d{2}:\d{2}/','',$date);
		$enddatetime .= $end.':00';
		$enddatetime = fix_enddatetime($enddatetime, $date, $object_id);
		$title = empty($post->post_title) ? get_the_title($object_id) : $post->post_title;
		$sql = $wpdb->prepare('REPLACE INTO '.$wpdb->prefix.'rsvpmaker_event SET post_title=%s, date=%s, enddate=%s, display_type=%s, event=%d',$title, $date,$enddatetime,$type,$object_id);
		//rsvpmaker_debug_log($sql,'sync sql2 firstime');
		$wpdb->query($sql);
	}
}
add_action('updated_postmeta','sync_rsvpmaker_event_dates',10,4);
add_action('added_post_meta','sync_rsvpmaker_event_dates',10,4);

function fix_enddatetime($enddatetime, $date, $post_id) {
	global $wpdb;
	if($enddatetime < $date) {
		$t = strtotime($date.' +1 hour');
		$enddatetime = date('Y-m-d H:i:s',$t);
		update_post_meta($post_id,'_endfirsttime',date('H:i',$t));
	}
	return $enddatetime;
}

function rsvpmaker_excerpt($post) {
	global $rsvp_options, $post;
	$rsvp_on = get_post_meta($post->ID,'_rsvp_on',true);
	$excerpt = rsvpdateblock();
	if(strpos($post->post_content,'<!--more-->')) {
		$morelink = true;
		$p = explode('<!--more-->',$post->post_content);
		$excerpt = do_blocks($p[0]);
	}
	else {
		$content = do_blocks($post->post_content);
		$blocks = explode("<p",$content);
		$fullsize = sizeof($blocks);
		$blocks = array_slice($blocks,0,3);
		$excerpt .= do_blocks(implode("<p",$blocks));
		$shortened = sizeof($blocks);
		$morelink = ($fullsize > $shortened);
	}
	$permalink = get_permalink($post->ID);
	$rsvplink = add_query_arg('e','*|EMAIL|*',$permalink).'#rsvpnow';
	if($morelink)
	$excerpt .= sprintf('<p style="text-align: right;"><a href="%s">%s</a></p>',$permalink,__('Read More','rsvpmaker'));
	if($rsvp_on)
		$excerpt .= sprintf($rsvp_options['rsvplink'],$rsvplink);
	return $excerpt;
}

add_shortcode('future_events_test','future_events_test');

function get_future_events ($where_or_atts = '', $limit=0, $output = OBJECT, $offset_hours = 0) {
global $offset_hours;
if(is_array($where_or_atts)) {
	$atts = $where_or_atts;
	$atts['is_array'] = 1;
	$offset_hours = (isset($where_or_atts['offset_hours'])) ? $where_or_atts['offset_hours'] : 0;
}
else {
	$atts = array('where' => $where_or_atts, 'limit' => $limit, 'offset_hours' => $offset_hours);
}
	$atts['afternow'] = 1;
	$data = rsvpmaker_upcoming_data($atts);
	return $data;
}

function get_future_events_by_meta ($kv, $limit='', $output = OBJECT, $offset_hours = 0) {

	global $wpdb;
	$wpdb->show_errors();
	
	$startfrom = ($offset_hours) ? ' DATE_SUB("'.get_sql_now().'", INTERVAL '.$offset_hours.' HOUR) ' : '"'.get_sql_now().'"';
	
		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.date as datetime, date_format(a1.date,'%M %e, %Y') as date, a1.enddate, a1.display_type, meta.meta_value
		 FROM ".$wpdb->posts."
		 JOIN ".$wpdb->prefix."rsvpmaker_event"." a1 ON ".$wpdb->posts.".ID =a1.event
		 JOIN ".$wpdb->postmeta." meta ON ".$wpdb->posts.".ID =meta.post_id
		 WHERE (a1.date > ".$startfrom." OR a1.enddate > ".$startfrom.") AND post_status='publish' 
		 AND meta_key='".$kv['meta_key']."' ";
		 if(isset($kv['meta_value'])) {
			 $comparison = '=';
			 if(isset($kv['comparison']))
			 	$comparison = $kv['comparison'];
			 $sql .= " AND meta_value $comparison '".$kv['meta_value']."'";
		 }
		$sql .= ' ORDER BY a1.date ';
	
		 if( !empty($limit) )
	
			$sql .= ' LIMIT 0,'.$limit.' ';
	
		if(!empty($_GET["debug_sql"]))
	
			echo $sql;

		rsvpmaker_debug_log($sql,'meta lookup test');
	
		return $wpdb->get_results($sql, $output);	
}

function future_events_test() {
	$events = get_future_events();
	foreach($events as $event) 
		echo $event->post_title;
	return var_export($events,true);
}

function get_future_dates ($limit) {

	global $wpdb;
	
	$wpdb->show_errors();
	
	$startfrom = '"'.get_sql_curdate().'"';
	
		$sql = "SELECT DISTINCT ID, $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date
	
		 FROM ".$wpdb->posts."
	
		 JOIN ".$wpdb->prefix." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	
		 WHERE a1.meta_value > ".$startfrom." AND post_status='publish' ";
		$sql .= ' ORDER BY a1.meta_value ';
	


		 if( !empty($limit) )
	
			$sql .= ' LIMIT 0,'.$limit.' ';
	
		if(!empty($_GET["debug_sql"]))
	
			echo $sql;

		return $wpdb->get_results($sql);
	
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

	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,rsvpmaker_date('F j, Y',rsvpmaker_strtotime($event->datetime)));

	}

$options .= "<optiongroup>"."\n";



$options .= '<optgroup label="'.__('Recent Events','rsvpmaker').'">'."\n";

$past = get_past_events('',50);

if(is_array($past))

foreach($past as $event)

	{

	if(get_post_meta($event->ID,'_rsvp_on',true))

	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,rsvpmaker_date('F j, Y',rsvpmaker_strtotime($event->datetime)));

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

	return get_template_sked($post_id);

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

	////print_r($active_options);

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

			$field = sanitize_text($field);

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
	//'_firsttime','_endfirsttime',
	$date_fields = array('_day_of_week','_week_of_month','_template_start_hour','_template_start_minutes','complex_template');

	if(in_array($meta_key,$date_fields))

		{

			$datetime = get_rsvp_date($post_id);

			$sked = get_template_sked($post_id);

			if($datetime) {

				if($meta_key == '_firsttime')

				{

					if(rsvpmaker_is_template($post_id))

						$content = get_post_meta($post_id, '_sked_duration',true);

					else

						$content = get_post_meta($post_id, '_'.$datetime,true);

				}

				elseif(is_admin() && ($meta_key == '_endfirsttime'))

				{

					if(rsvpmaker_is_template())

						$content = get_post_meta($post_id,'_sked_end');

					else {

						$end = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_endfirsttime' and post_id=$post_id ");

						if(empty($end))

						{

							//default value for editor

							$content = rsvpmaker_date('H:i',rsvpmaker_strtotime($datetime .' +1 hour'));

						}	

					}

				}

			} 

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

				elseif(($meta_key == '_endfirsttime'))

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

	if($content) {

		update_post_meta_unfiltered($post_id,$meta_key,$content);

		return array($content);

	}

	return $null; // don't alter

}



function update_post_meta_unfiltered($post_id,$meta_key,$meta_value) {

	global $wpdb;

	if(is_array($meta_value))
		$meta_value = serialize($meta_value);

	$meta_id = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=$post_id and meta_key='$meta_key' ");

	if($meta_id)

		$wpdb->query("UPDATE $wpdb->postmeta SET meta_value='$meta_value' WHERE meta_id=$meta_id ");

	else

		$wpdb->query("INSERT INTO $wpdb->postmeta SET meta_value='$meta_value', post_id=$post_id, meta_key='$meta_key' ");

}

//handles conversions, for example "simple price"
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

	$date_fields = array('_firsttime','_endfirsttime','_day_of_week','_week_of_month','_template_start_hour','_template_start_minutes','complex_template');

	if(in_array($meta_key,$date_fields) && ($sked = get_template_sked($post_id) ) && is_array($sked) )

		{

			$week = $sked["week"];

			$dayofweek = $sked["dayofweek"];

			$hour = $sked["hour"];

			$minutes = $sked["minutes"];

			$duration = $sked["duration"];

			if($meta_key == '_firsttime')

			{

				rsvpmaker_debug_log('firsttime_test'.$meta_value,$meta_key);

				$sked["duration"] = $meta_value;

			}

			elseif(($meta_key == '_endfirsttime') || ($meta_key == '_endfirsttime'))

			{

				rsvpmaker_debug_log($meta_value,$meta_key);

				$sked['end'] = $meta_value;

			}

			if($meta_key == '_template_start_hour')

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

		new_template_schedule($post_id,$sked,'update_rsvp_post_metadata');

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

	global $wpdb, $rsvp_options;

	$week_array = get_week_array();

	$day_array = get_day_array();

	$singles = array('hour','minutes','duration','end','stop');

	$newsked = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_sked_%' ");

	if($newsked) {


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
					update_post_meta($post_id,'_sked_minutes',$rsvp_options['defaultmin']);
				}

			$sked[$key] = $row->meta_value;

		}

		if(empty($week) && empty($dayofweek))
			return false; //not a valid template
		update_post_meta($post_id,'_sked_template',true);

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

		if(empty($sked['hour']))

			$sked['hour'] = $rsvp_options['defaulthour'];

		if(empty($sked['minutes']))

			$sked['minutes'] = $rsvp_options['defaultmin'];
		return $sked;

	}
	return false;

}

function rsvpmaker_upgrade_templates() {
	global $wpdb, $rsvp_options;

	$oldtemplates = $wpdb->get_results("select post_id FROM $wpdb->postmeta WHERE meta_key='_sked'");
	foreach($oldtemplates as $oldtemplate) {
		if(!get_post_meta($oldtemplate->post_id,'_sked_Varies')) {
			$sked = get_post_meta($oldtemplate->post_id,'_sked',true);
			if(empty($sked['hour']))
				$sked['hour'] = $rsvp_options['defaulthour'];
			if(empty($sked['minutes']))
				$sked['minutes'] = $rsvp_options['defaultmin'];
			new_template_schedule($oldtemplate->post_id,$sked);
		}
		delete_post_meta($oldtemplate->post_id,'_sked');
	}
}

function new_template_schedule($post_id,$template,$source = '') {

	if(is_array($template["week"]))
	{
		$weeks = $template["week"];
		$dows = (empty($template["dayofweek"])) ? array() : $template["dayofweek"];
	}
	else
	{
		$weeks[0] = $template["week"];
		$dows[0] = (isset($template["dayofweek"])) ? $template["dayofweek"] : 9; // no day for varies
	}
	$hour = (isset($template['hour'])) ? $template['hour'] : '00';	
	$minutes = (isset($template['minutes'])) ? $template['minutes'] : '00';	
	$duration = (isset($template['duration'])) ? $template['duration'] : '';

	$end = (isset($template['end'])) ? $template['end'] : '';

	$stop = (isset($template['stop'])) ? $template['stop'] : '';	

	$new_template_schedule = build_template_schedule($post_id,$dows,$weeks,$hour,$minutes,$duration,$end,$stop);
	foreach($new_template_schedule as $label => $value) {
		$label = '_sked_'.$label;
		update_post_meta_unfiltered($post_id,$label,$value);
	}
	$new_template_schedule['week'] = $weeks;
	$new_template_schedule['dayofweek'] = $dows;
	return $new_template_schedule;
}



function build_template_schedule($post_id,$dows,$weeks,$hour,$minutes,$duration,$end,$stop) {

		$weekarray = get_week_array();
		foreach($weekarray as $index => $label)
		{
			$atomic_sked[$label] = in_array($index,$weeks);
		}
		$dayarray = get_day_array();
		foreach($dayarray as $index => $label)
		{
			$atomic_sked[$label] = in_array($index,$dows);
		}

	$atomic_sked['hour'] = (empty($hour)) ? '00' : $hour;

	$atomic_sked['minutes'] = (empty($minutes)) ? '00' : $minutes;

	$atomic_sked['stop'] = $stop;

	$atomic_sked['duration'] = $duration;

	$atomic_sked['end'] = $end;

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

global $post, $wpdb, $email_context;

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

	global $post, $wpdb, $email_context;

	$email = '';

	global $current_user;

	if(isset($_GET['e']))

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



function rsvpmaker_parent ($post_id) {

global $wpdb;

return $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID=$post_id");

}



function get_form_links($post_id, $t, $parent_tag) {

	global $rsvp_options;

	$label = '';

	$form_id = get_post_meta($post_id,'_rsvp_form',true);

	$forms = rsvpmaker_get_forms();

	if($form_id) {

		$parent_id = rsvpmaker_parent ($form_id);
		$formtype = array_search($form_id,$forms);
		if(empty($parent_id))
			$label = empty($formtype) ? ' (Default)' : '(Default: '.$formtype.')';
		elseif($parent_id == $t)
			$label = ' (From Template)';
	}

	else {
		$form_id = $rsvp_options['rsvp_form'];
		$label = ' (Default)';
	}

	$args[] = array(

		'parent'    => $parent_tag,

		'id' => 'edit_form',

		'title' => 'RSVP Form'.$label,

		'href'  => admin_url('post.php?action=edit&post='.$form_id.'&back='.$post_id),

		'meta'  => array( 'class' => 'rsvpmenu')

	);
	if(empty($label) || ($label == ' (From Template)') || (strpos($label, ':') ) ) {
		$args[] = array(

			'parent'    => 'edit_form',

			'id' => 'customize_form',

			'title' => 'RSVP Form -> Default',

			'href'  => admin_url("edit.php?title=Form&rsvpcz_default=rsvp_form&post_id=$post_id"),

			'meta'  => array( 'class' => 'rsvpmenu-custom')

		);
	}
	if(!empty($label)) {

		//if inherited, provide option to customize

		$args[] = array(

			'parent'    => 'edit_form',

			'id' => 'customize_form',

			'title' => 'RSVP Form -> Customize',

			'href'  => admin_url("edit.php?title=Form&rsvpcz=_rsvp_form&post_id=$post_id&source=".$form_id),//$formurl,

			'meta'  => array( 'class' => 'rsvpmenu-custom')

		);

	}
	foreach($forms as $label => $named_form_id) {
		if($named_form_id == $form_id)
			continue;
			$args[] = array(

				'parent'    => 'edit_form',
	
				'id' => 'switch_form_'.$label,
	
				'title' => 'RSVP Form, '.$label.' -> Switch',
	
				'href'  => admin_url("edit.php?post_id=$post_id&rsvp_form_switch=".$named_form_id),
				'meta'  => array( 'class' => 'rsvpmenu-custom')
			);		
			$args[] = array(

			'parent'    => 'edit_form',

			'id' => 'customize_form_'.$label,

			'title' => 'RSVP Form, '.$label.' -> Customize',

			'href'  => admin_url("edit.php?title=Form&rsvpcz=_rsvp_form&post_id=$post_id&source=".$named_form_id),
			'meta'  => array( 'class' => 'rsvpmenu-custom')
		);		
	}

return $args;

}

function get_conf_links ($post_id, $t, $parent_tag) {

global $rsvp_options, $wpdb;

$label = '';

$confirm_id = get_post_meta($post_id,'_rsvp_confirm',true);

if($confirm_id == $rsvp_options['rsvp_confirm'])

	$label = ' (Default)';

elseif($confirm_id) {

	$cpost = get_post($confirm_id);

	if(empty($cpost->ID))

	{

		$confirm_id = $rsvp_options['rsvp_confirm'];
		$label = ' (Default)';	

	}

	else {

		$parent_id = $cpost->post_parent;

		if($parent_id == $t)

			$label = ' (From Template)';

		elseif($parent_id != $post_id)

			$label = ' (Inherited)';

	}

}

else {

	$confirm_id = $rsvp_options['rsvp_confirm'];

	$label = ' (Default)';

}

$args[] = array(

	'parent'    => $parent_tag,

	'id' => 'edit_confirm',

	'title' => 'Confirmation Message'.$label,

	'href'  => admin_url('post.php?action=edit&post='.$confirm_id.'&back='.$post_id),

	'meta'  => array( 'class' => 'rsvpmenu')

);
if(empty($label) || $label == ' (From Template)') {
	$args[] = array(

		'parent'    => 'edit_confirm',

		'id' => 'default_form',

		'title' => 'RSVP confirmation -> Default',

		'href'  => admin_url("edit.php?title=Form&rsvpcz_default=rsvp_confirm&post_id=$post_id"),//$formurl,

		'meta'  => array( 'class' => 'rsvpmenu-custom')

	);
}

if(!empty($label)) {

	//if inherited, provide option to customize

	$confurl = rsvp_confirm_url($post_id);

	$args[] = array(

		'parent' => 'edit_confirm', //$parent_tag,

		'id' => 'customize_confirmation',

		'title' => 'Confirmation -> Customize',

		'href'  => admin_url("edit.php?title=Confirmation&rsvpcz=_rsvp_confirm&post_id=$post_id&source=".$confirm_id),

		'meta'  => array( 'class' => 'rsvpmenu-custom')

	);

}



$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_rsvp_reminder_msg_%' ORDER BY meta_key";



$results = $wpdb->get_results($sql);

if($results)

foreach($results as $row)

{

	$hours = str_replace('_rsvp_reminder_msg_','',$row->meta_key);

	$meta_key = $row->meta_key;

	$type = ($hours > 0) ? 'FOLLOW UP' : 'REMINDER';

	$reminder = rsvp_get_reminder($post_id,$hours);

	$parent = $reminder->post_parent;//get_post_meta($reminder->ID,'_rsvpmaker_parent',true);

	$label = ($parent != $post_id) ? ' (from Template)' : '';

	$identifier = 'reminder'.$hours;

	$args[] = array(

		'parent'    => $parent_tag,

		'id' => $identifier,

		'title' => $type.' '.$hours.' hours'.$label,

		'href'  => admin_url('post.php?action=edit&post='.$reminder->ID),

		'meta'  => array( 'class' => 'rsvpmenu'),

	);

	if(!empty($label))

	{

		$args[] = array(

			'parent'    => $identifier,

			'id' => $identifier.'custom',

			'title' => $type.' '.$hours.' hours -> Customize',

			'href'  => admin_url("edit.php?title=Reminder $hours&rsvpcz=$meta_key&post_id=$post_id&source=".$reminder->ID),

			'meta'  => array( 'class' => 'rsvpmenu-custom'),

		);

	}

}



$payconf = get_post_meta($post_id,'payment_confirmation_message',true);

$meta_key = 'payment_confirmation_message';

if(empty($payconf) )

{

	$args[] = array(

		'parent' => $parent_tag,

		'id' => 'edit_payment_confirmation',

		'title' => 'Payment Confirmation',

		'href'  => admin_url("edit.php?title=Payment Confirmation&rsvpcz=$meta_key&post_id=$post_id"),

		'meta'  => array( 'class' => 'rsvpmenu')

	);	

}

else {

	$payparent = rsvpmaker_parent($payconf);

	$label = ($payparent == $t) ? ' (from Template)' : '';

	$args[] = array(

		'parent' => $parent_tag,

		'id' => 'edit_payment_confirmation',

		'title' => 'Payment Confirmation'.$label,

		'href'  => admin_url('post.php?action=edit&post='.$payconf),

		'meta'  => array( 'class' => 'rsvpmenu')

	);

	if(!empty($label))	

	$args[] = array(

		'parent' => 'edit_payment_confirmation',

		'id' => 'edit_payment_confirmation_custom',

		'title' => 'Payment Confirmation -> Customize',

		'href'  => admin_url("edit.php?title=Payment Confirmation&rsvpcz=$meta_key&post_id=$post_id&source=$payconf"),

		'meta'  => array( 'class' => 'rsvpmenu-custom')

	);

}



return $args;

}



function get_more_related($post, $post_id, $t, $parent_tag) {

global $wpdb, $rsvp_options;

$args[] = array(

	'parent'    => $parent_tag,

	'id' => 'confirmation_reminders',

	'title' => 'Confirmations + Reminders',

	'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id='.$post_id),

	'meta'  => array( 'class' => 'confirmation_reminders')

);

$confs = get_conf_links($post_id, $t, $parent_tag);

foreach($confs as $arg)
	$args[] = $arg;

		$forms = get_form_links($post_id, $t, $parent_tag);

		foreach($forms as $arg)

			$args[] = $arg;



		$args[] = array(

			'parent'    => $parent_tag,

			'id' => 'rsvp_report',

			'title' => 'RSVP Report',

			'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvp&event='.$post_id),

			'meta'  => array( 'class' => 'edit_form')

		);



		if($t)

		{

		$args [] = array('parent' => $parent_tag, 'id' => 'rsvpmaker-edit-template', 'href' => admin_url('post.php?action=edit&post=').$t, 'title' => __('Edit Template','rsvpmaker'), 'meta' => array('class' => 'rsvpmaker-edit-template'));

	

		$args[] = array(

			'parent' => $parent_tag,

			'id' => 'template-options',

			'title' => 'Template Options',

			'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$t),

			'meta'  => array( 'class' => 'template-options')

		);

		$args[] = array('parent' => $parent_tag, 'title' => __('Update Template Based On Event'), 'id' => 'rsvpmaker-overwrite-template', 'href' => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&override_template=').$t.'&event='.$post->ID, 'meta' => array('class' => 'rsvpmaker-overwrite-template'));

		}



		if(rsvpmaker_is_template($post_id))

		{

		$args[] = array(

			'id'    => 'rsvpmaker_create_update',

			'parent'    => $parent_tag,

			'title' => 'Create / Update',

			'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post_id),

			'meta'  => array( 'class' => 'rsvpmaker-create-update')

		);

		}





		//rsvpmaker_debug_log($args,'more menu array');

	

		return $args;

}



function get_related_documents ( $post_id = 0, $query = '') {

	global $post, $rsvp_options;

	$backup = $post;

	if(isset($_GET['page']) && isset($_GET['post_id']))

		$post_id = (int) $_GET['post_id'];

	if($post_id)

		$post = get_post($post_id);

	elseif(isset($post->ID))

		$post_id = $post->ID;

	else

		return;

	if(strpos($_SERVER['REQUEST_URI'],'edit.php') && empty($_GET['page']))

		return;

	if(($post->post_type != 'rsvpmaker') && ($post->post_type != 'rsvpemail'))

		return;

	$t = has_template($post->ID);

	$parent_tag = 'edit'; // front end

	if(isset($_GET['page']) || isset($_GET['action']))

		$parent_tag = 'rsvpmaker_options';



	$rsvp_id = rsvpmaker_parent($post_id);

	if(($query == 'rsvpemail') && !$rsvp_id && !(strpos($post->post_title,'Default')))

		return array();



	//$parent_tag = (is_admin() && !isset($_GET['page'])) ? 'rsvpmaker_options' : 'edit';

	if(isset($post->post_title) && (strpos($post->post_title,'Default')) )

	{

		$args[] = array(

			'id'    => 'rsvpmaker_settings',

			'title' => __('RSVPMaker Settings','rsvpmaker'),

			'href'  => admin_url('options-general.php?page=rsvpmaker-admin.php'),

			'meta'  => array( 'class' => 'edit-rsvpmaker-options'));

			if(!empty($_GET['back']))

			{

			$rsvp_parent = (int) $_GET['back'];//get_post_meta($post->ID,'_rsvpmaker_parent',true);

			$args[] = array(

				'id'    => 'rsvpmaker_parent',

				'title' => __('Edit Event','rsvpmaker'),

				'href'  => admin_url('post.php?action=edit&post='.$rsvp_parent),

				'meta'  => array( 'class' => 'edit-rsvpmaker')

			);

			$args[] = array(

				'id'    => 'view-event',

				'title' => __('View Event','rsvpmaker'),

				'href'  => get_permalink($rsvp_parent),

				'meta'  => array( 'class' => 'view')

			);		

			$args[] = array(

				'parent'    => 'rsvpmaker_parent',

				'id'    => 'rsvpmaker_options',

				'title' => 'RSVP / Event Options',

				'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$rsvp_parent),

				'meta'  => array( 'class' => 'edit-rsvpmaker-options')

			);

			$parent_tag = 'rsvpmaker_parent';

			$more = get_more_related(get_post($rsvp_parent), $rsvp_parent, has_template($rsvp_parent), $parent_tag);	

			foreach($more as $add)

				$args[] = $add;

			}// default used in a post identified by "back"

	return $args;		

	} // end this is a default message

	$rsvp_parent = $post->post_parent;//get_post_meta($post->ID,'_rsvpmaker_parent',true);

	if(!empty($_GET['back']))

		$rsvp_parent = (int) $_GET['back'];

	if($rsvp_parent)

	{

	$args[] = array(

		'id'    => 'rsvpmaker_parent',

		'title' => __('Edit Event','rsvpmaker'),

		'href'  => admin_url('post.php?action=edit&post='.$rsvp_parent),

		'meta'  => array( 'class' => 'edit-rsvpmaker')

	);

	$parent_tag = 'rsvpmaker_parent';

	$args[] = array(

		'id'    => 'view-event',

		'title' => __('View Event ','rsvpmaker'),

		'href'  => get_permalink($rsvp_parent),

		'meta'  => array( 'class' => 'view-event')

	);

	$args[] = array(

	'parent' => $parent_tag,

	'id'    => 'rsvpmaker_options',

	'title' => 'RSVP / Event Options',

	'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$rsvp_parent),

	'meta'  => array( 'class' => 'rsvpmenu')

	);

	$more = get_more_related(get_post($rsvp_parent), $rsvp_parent, has_template($rsvp_parent), $parent_tag);

	foreach($more as $add)

		$args[] = $add;

	return $args;

	}



	if($post->post_type != 'rsvpmaker')

		return array();//no rsvpemail documents unless they have a post parent



	if(is_admin() && isset($_GET['page']) && ($_GET['page'] == 'rsvpmaker_details')) {

	$args[] = array(

		'id'    => 'rsvpmaker_options',

		'title' => __('Edit Event','rsvpmaker'),

		'href'  => admin_url('post.php?action=edit&post='.$post_id),

		'meta'  => array( 'class' => 'edit-rsvpmaker')

	);

	$args[] = array(

		'id'    => 'view-event',

		'title' => __('View Event','rsvpmaker'),

		'href'  => get_permalink($post_id),

		'meta'  => array( 'class' => 'view')

	);

}

	elseif(is_admin() && isset($_GET['page'])) {

	//a different page

	$args[] = array(

		'id'    => 'rsvpmaker_options',

		'title' => __('Edit Event','rsvpmaker'),

		'href'  => admin_url('post.php?action=edit&post='.$post_id),

		'meta'  => array( 'class' => 'edit-rsvpmaker')

	);

	$args[] = array(

		'id'    => 'view-event',

		'title' => __('View Event','rsvpmaker'),

		'href'  => get_permalink($post_id),

		'meta'  => array( 'class' => 'view')

	);

	$args[] = array(

		'parent' => $parent_tag,

		'id'    => 'rsvpmaker_options_screen',

		'title' => 'RSVP / Event Options',

		'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),

		'meta'  => array( 'class' => 'edit-rsvpmaker-options')

		);

	}

	elseif(is_admin()) {

	//edit or other admin page

	$args[] = array(

		'id'    => 'rsvpmaker_options',

		'title' => 'RSVP / Event Options',

		'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),

		'meta'  => array( 'class' => 'edit-rsvpmaker-options')

		);

	}

	else //front end

	$args[] = array(

		'parent' => $parent_tag,

		'id'    => 'rsvpmaker_options',

		'title' => 'RSVP / Event Options',

		'href'  => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),

		'meta'  => array( 'class' => 'edit-rsvpmaker-options')

	);



	$more = get_more_related($post, $post_id, $t, $parent_tag);

	foreach($more as $add)

		$args[] = $add;



return $args;

}

function get_rsvpmaker_authors() {

$entire_user_list = get_users('orderby=display_name');

$rsvp_users= array();

    

    foreach($entire_user_list as $user){

    

        if($user->has_cap('edit_rsvpmakers')){

            $rsvp_users[] = array('ID' => $user->ID, 'name' => $user->display_name);

        }

    }

return $rsvp_users;

}

function cleanup_rsvpmaker_child_documents () {
	global $wpdb;
	//forms and messages with no event document
	$sql = "SELECT parent_post.ID, child_post.ID as child_ID, child_post.post_parent
FROM $wpdb->posts as parent_post right join $wpdb->posts as child_post ON parent_post.ID=child_post.post_parent where parent_post.ID IS NULL AND child_post.post_parent";
	$results = $wpdb->get_results($sql);
	foreach($results as $row)
		wp_delete_post($row->child_ID, true);
}

add_filter('mailpoet_newsletter_shortcode', 'mailpoet_rsvpmaker_shortcode', 10, 5);

function mailpoet_rsvpmaker_shortcode($shortcode, $newsletter, $subscriber, $queue, $newsletter_body) {
  // always return the shortcode if it doesn't match your own!
  if (!strpos($shortcode,'rsvpmaker') && !strpos($shortcode,'event_listing') ) return $shortcode;
  global $email_context;
  $email_context = true;
  $shortcode = str_replace('custom:','',$shortcode);
  $atts = shortcode_parse_atts(str_replace(']','',str_replace('[','',$shortcode)));
if(strpos($shortcode,'upcoming'))
	$content = rsvpmaker_upcoming($atts);
elseif(strpos($shortcode,'one'))
	$content = rsvpmaker_one($atts);
if(strpos($shortcode,'next'))
	$content = rsvpmaker_next($atts);
elseif(strpos($shortcode,'listing'))
	$content = event_listing($atts);
$content = str_replace('<h1','<h1 style="line-height: 1.3" ',$content);
$content = str_replace('class="rsvpmaker-entry-title-link"','style="text-decoration: none" ',$content);
return $content;
}

function mailpoet_email_list_okay($rsvp) {
	if (class_exists(\MailPoet\API\API::class)) {
		$mailpoet_api = \MailPoet\API\API::MP('v1');
		$list = get_option('rsvpmaker_mailpoet_list');
		if(!$list)
			return;
		$list_ids = array($list);
		$mailpoet_api = \MailPoet\API\API::MP('v1');
		$first = (empty($rsvp['first'])) ? '' : $rsvp['first'];
		$last = (empty($rsvp['last'])) ? '' : $rsvp['last'];
		$subscriber = array('email' => $rsvp['email'], 'first_name' => $first,'last_name' => $last);
			try {
				$get_subscriber = $mailpoet_api->getSubscriber($subscriber['email']);
			  } catch (\Exception $e) {}			
			  try {
				if (!$get_subscriber) {
				  // Subscriber doesn't exist let's create one
				  $mailpoet_api->addSubscriber($subscriber, $list_ids);
				} else {
				  // In case subscriber exists just add him to new lists
				  $mailpoet_api->subscribeToLists($subscriber['email'], $list_ids);
				}
			  } catch (\Exception $e) {
				$error_message = $e->getMessage();
			  }
	}
}

function rsvpmaker_sametime($datetime, $post_id=0) {

global $wpdb;
$sql = sprintf("SELECT * FROM $wpdb->posts JOIN %s ON $wpdb->posts.ID=%s.event WHERE post_status='publish' AND ID != %d AND date='%s' ",$wpdb->prefix.'rsvpmaker_event',$wpdb->prefix.'rsvpmaker_event',$post_id,$datetime);
$sametime_events = $wpdb->get_results($sql);
$mod = '';
if($sametime_events)
{
	$label = (sizeof($sametime_events) > 1) ? __('Events','rsvpmaker') : __('Event','rsvpmaker');
	$mod .= ' <span style="color:red;">* '.$label.' '.__(' at same time','rsvpmaker')."</span>: ";
	$same = array();
	foreach($sametime_events as $sametime)
		$same[] = sprintf('<a href="%s">%s</a> ',admin_url('post.php?action=edit&post='.$sametime->ID),$sametime->post_title );
	$mod .= implode(', ',$same);
	$d = get_post_meta($sametime->ID,'_detached_from_template',true);
	$mod .= ($d) ? ' - detached from template '.$d : '';
}

return $mod;
}

?>