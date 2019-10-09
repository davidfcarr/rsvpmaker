<?php
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

//printf('<pre>%s</pre>duration %s',var_export($datevar),$duration_type);

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
		$t = strtotime($start_time.' +1 hour');
		$defaulthour = date('H',$t);
		$defaultmin = date('i',$t);
	}
else {
	if(empty($end_time))
	{
		$start_time = $rsvp_options['defaulthour'].':'.$rsvp_options['defaultmin'];
		$t = strtotime($start_time.' +1 hour');
		$defaulthour = date('H',$t);
		$defaultmin = date('i',$t);
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
	foreach($rsvpdates[$post_id] as $datetime)
	{
	$drow['datetime'] = $datetime;
	$drow["duration"] = get_post_meta($post_id,'_'.$datetime, true);
	$drow["end_time"] = get_post_meta($post_id,'_end'.$datetime, true);
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
foreach($results as $row)
	{
	$drow = array();
	$datetime = $row->meta_value;
	$drow["meta_id"] = $row->meta_id;
	$drow["datetime"] = $datetime;
	$rsvpdates[$post_id][] = $datetime;
	$drow["duration"] = get_post_meta($post_id,'_'.$datetime, true);
	$drow['end_time'] = '';
	if(!empty($drow['duration']))
	{
		if($drow['duration'] == 'set')
		{
			$drow['end_time'] = get_post_meta($post_id,'_end'.$datetime, true);
		}
		elseif(strpos($drow['duration'],'-'))
		{
			//old format
			$drow['end_time'] = date('H:i',strtotime($drow['duration']));
			update_post_meta($post_id,'_end'.$datetime,$drow['end_time']);
			update_post_meta($post_id,'_'.$datetime,'set');
		}
	}
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
		$where = ' a1.meta_value > CURDATE() ';
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
	 WHERE a1.meta_value > CURDATE() AND post_status='publish'
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
	 WHERE a1.meta_value > CURDATE() AND post_status='publish'
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
	 WHERE a1.meta_value > CURDATE() AND (post_status='publish' OR post_status='draft')
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
	 WHERE a1.meta_value > CURDATE() AND (post_status='publish' OR post_status='draft')
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
	 WHERE $wpdb->posts.post_author=$author AND a1.meta_value > NOW()".$status_sql;
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

function cache_rsvp_dates($limit) {
global $rsvpdates, $wpdb;
if(!empty($rsvpdates))
	return;//if some other process already retrieved the dates
$rsvpdates = get_transient('rsvpmakerdates');
if(!empty($rsvpdates))
	return;
$rsvpdates = array();
$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='_rsvp_dates' AND meta_value > NOW() ORDER BY meta_value LIMIT 0, $limit";
$results = $wpdb->get_results($sql);
if($results)
foreach($results as $row) {
	$rsvpdates[$row->post_id][] = $row->meta_value;
}
set_transient('rsvpmakerdates',$rsvpdates, HOUR_IN_SECONDS); 
}

function rsvpmaker_cleanup () {
	global $wpdb;
?>
<h1>RSVPMaker Cleanup</h1>
<?php
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
	 WHERE a1.meta_value < DATE_SUB(NOW(),INTERVAL $older DAY) AND (post_status='publish' OR post_status='draft') ";
	//echo $sql;
	$results = $wpdb->get_results($sql);
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
<p>Use this tool to clean up old events.</p>
<form method="post" action="<?php echo admin_url('tools.php?page=rsvpmaker_cleanup') ?>">
Delete events older than <input type="text" name="older_than" value="30" /> days 
<?php submit_button('Delete') ?>
</form>
<?php
}//end initial form
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
$active_options = array();
if(get_rsvpmaker_stripe_keys ())
	$active_options[] = 'Stripe';
if(get_option('rsvpmaker_paypal_rest_keys'))
	$active_options[] = 'PayPal REST API';
if(!empty($rsvp_options['paypal_config']))
	$active_options[] = 'PayPal (legacy)';
if(class_exists('Stripe_Checkout_Functions') && !empty($rsvp_options['stripe']))
	$active_options[] = 'Stripe via WP Simple Pay';
$active_options[] = 'Cash or Custom';
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
?>