<?php
function get_rsvp_date($post_id)
{
global $wpdb;
$wpdb->show_errors();
$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE post_id=".$post_id." AND meta_key='_rsvp_dates' ORDER BY meta_value";
return $wpdb->get_var($sql);
}

function get_rsvp_dates($post_id, $obj = false)
{
global $wpdb;
$wpdb->show_errors();
$sql = "SELECT * FROM ".$wpdb->postmeta." WHERE post_id=".$post_id." AND meta_key='_rsvp_dates' ORDER BY meta_value";
$results = $wpdb->get_results($sql);
$dates = array();
if($results)
foreach($results as $row)
	{
	$drow = array();
	$datetime = $row->meta_value;
	$drow["meta_id"] = $row->meta_id;
	$drow["datetime"] = $datetime;
	$drow["duration"] = get_post_meta($post_id,'_'.$datetime, true);
	if($obj)
		$drow = (object) $drow;
	$dates[] = $drow;
	}
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
		$where = ' a1.meta_value > CURDATE() ';
	else
		$where = str_replace('datetime','a1.meta_value',$where);
	$sql .= ' AND '.$where.' ';
	$sql .= ' ORDER BY a1.meta_value ';
return $wpdb->get_row($sql);
}

function get_events_by_template($template_id, $order = 'ASC', $output = OBJECT) {
global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, date_format(a1.meta_value,'%M %e, %Y') as date, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key='_meet_recur' AND a2.meta_value=".$template_id." 
	 WHERE a1.meta_value > CURDATE() AND post_status='publish'
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
	 WHERE a1.meta_value > CURDATE() AND post_status='publish'
	 ORDER BY a1.meta_value ".$order;
	$wpdb->show_errors();
	return $wpdb->get_row($sql, $output);
}

function rsvpmaker_get_templates() {
	global $wpdb;
	$sql = "SELECT DISTINCT $wpdb->posts.*, meta_value as sked FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE meta_key='_sked' AND post_status='publish' GROUP BY $wpdb->posts.ID ORDER BY post_title";
return $wpdb->get_results($sql);
}

function get_future_events ($where = '', $limit='', $output = OBJECT, $offset_hours = 0) {
global $wpdb;
$wpdb->show_errors();
$startfrom = ($offset_hours) ? ' DATE_SUB(NOW(), INTERVAL '.$offset_hours.' HOUR) ' : ' NOW() ';

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
	 WHERE a1.meta_value > NOW() AND post_status='publish' ";
	return $wpdb->get_var($sql);
}

function count_recent_posts($blog_weeks_ago = 1) {
global $wpdb;
	$week_ago_stamp = strtotime('-'.$blog_weeks_ago.' week');
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
	 WHERE a1.meta_value < NOW() AND (post_status='publish' OR post_status='draft') ";
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
foreach($future as $event)
	{
	if(get_post_meta($event->ID,'_rsvp_on',true))
	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',strtotime($event->datetime)));
	}
$options .= "<optiongroup>"."\n";

$options .= '<optgroup label="'.__('Recent Events','rsvpmaker').'">'."\n";
$past = get_past_events('',50);
foreach($past as $event)
	{
	if(get_post_meta($event->ID,'_rsvp_on',true))
	$options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',strtotime($event->datetime)));
	}
$options .= "<optiongroup>"."\n";
return $options;
}

function is_rsvpmaker_future($event_id, $offset_hours = 0) {
global $wpdb;
if($offset_hours)
	$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND meta_value + INTERVAL $offset_hours HOUR > NOW() AND post_id=".$event_id;
else
	$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND meta_value > NOW() AND post_id=".$event_id;
$date = $wpdb->get_var($sql);
return (!empty($date));
}

function rsvpmaker_is_template ($post_id = 0) {
	global $post;
	if(!$post_id)
	{
		if(isset($post->ID))
			$post_id = $post->ID;
		else
			return false;
	}
	return get_post_meta($post_id,'_sked',true);
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

?>