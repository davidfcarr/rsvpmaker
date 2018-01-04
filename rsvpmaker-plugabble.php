<?php

// start customizable functions, can be overriden by adding a rsvpmaker-custom.php file to the plugins directory (one level up from rsvpmaker directory)

if(!function_exists('my_events_menu')) {
function my_events_menu() {
global $rsvp_options;
add_meta_box( 'EventDatesBox', __('Event Options','rsvpmaker'), 'draw_eventdates', 'rsvpmaker', 'normal', 'high' );
if(isset($rsvp_options["additional_editors"]) && $rsvp_options["additional_editors"])
	add_meta_box( 'ExtraEditorsBox', __('Additional Editors','rsvpmaker'), 'additional_editors', 'rsvpmaker', 'normal', 'high' );
}
}

if(!function_exists('draw_eventdates')) {
function draw_eventdates() {

global $post;
global $wpdb;
global $rsvp_options;
global $custom_fields;
if(isset($_GET["clone"]))
	{
		$id = (int) $_GET["clone"];
		$custom_fields = get_rsvpmaker_custom($id);
	}
else
	$custom_fields = get_rsvpmaker_custom($post->ID);

if(isset($custom_fields["_rsvpmaker_special"][0]))
	{
	$rsvpmaker_special = $custom_fields["_rsvpmaker_special"][0];
	if($rsvpmaker_special == 'Landing Page')
		{
?>
<p>This is a landing page for an RSVPMaker webinar.</p>
<p><input type="radio" name="_require_webinar_passcode" value="<?php echo $custom_fields["_webinar_passcode"][0]; ?>" <?php if(isset($custom_fields["_require_webinar_passcode"][0]) && $custom_fields["_require_webinar_passcode"][0]) echo 'checked="checked"'; ?> > Passcode required to view webinar</p>
<p><input type="radio" name="_require_webinar_passcode" value="0" <?php if(!isset($custom_fields["_require_webinar_passcode"][0]) || !$custom_fields["_require_webinar_passcode"][0]) echo 'checked="checked"'; ?>> No passcode required</p>
<?php
		}
	else
		do_action('rsvpmaker_special_metabox',$rsvpmaker_special);
	
	return;
	}
elseif(isset($custom_fields["_sked"][0]) || isset($_GET["new_template"]) )
	{
?>
<p><em><strong><?php _e('Event Template','rsvpmaker'); ?>:</strong> <?php _e('This form is for entering generic / boilerplate information, not specific details for an event on a specific date. Groups that meet on a monthly basis can post their standard meeting schedule, location, and contact details to make entering the individual events easier. You can also post multiple future meetings using the generic template and update those event listings as needed when the event date grows closer.','rsvpmaker'); ?></em></p>
<?php
		$template = get_post_meta($post->ID,'_sked',true);
		template_schedule($template);
		GetRSVPAdminForm($post->ID);
		return;
	}

if(isset($custom_fields["_meet_recur"][0]))
	{
		$t = (int) $custom_fields["_meet_recur"][0];

printf('<p><a href="%s">%s</a> | <a href="%s">%s</a></p>',admin_url('post.php?action=edit&post='.$t),__('Edit Template','rsvpmaker'),admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$t),__('See Related Events','rsvpmaker'));
	}
	
if(isset($post->ID) )
	$results = get_rsvp_dates($post->ID);
else
	$results = false;

$start = 0;
fix_timezone();
if($results)
{
foreach($results as $index => $row)
	{
	echo "\n<div class=\"event_dates\"> \n";
	$t = strtotime($row["datetime"]);
	if($rsvp_options["long_date"]) echo utf8_encode(strftime($rsvp_options["long_date"],$t));
	$dur = $row["duration"];
	if(strpos($dur,':'))
		$dur = strtotime($dur);
	if($dur != 'allday')
		echo strftime(' '.$rsvp_options["time_format"],$t);
	if(is_numeric($dur) )
		echo " to ".strftime ($rsvp_options["time_format"],$dur);
	echo sprintf(' <input type="checkbox" name="delete_date[]" value="%s" /> %s<br />',$row["datetime"],__('Delete','rsvpmaker'));
	rsvpmaker_date_option($row, $index, date('Y-m-d',$t));
	echo "</div>\n";
	$start = $index + 1;
	}
}
else
	{
	echo '<p><em>'.__('Enter one or more dates. For an event starting at 1:30 p.m., you would select 1 p.m. (or 13: for 24-hour format) and then 30 minutes. Specifying the duration is optional.','rsvpmaker').'</em> </p>';
	$t = time();
	}
if($start == 0)
	{
	$start = 1;
	$date = (isset($_GET["add_date"]) ) ? $_GET["add_date"] : 'today';
	rsvpmaker_date_option($date, 0, date('Y-m-d',$t));
	}
for($i=$start; $i < $start + 6; $i++)
{
if($i == $start)
	{
	do_action('rsvpmaker_datebox_message');
	$add_dates_div = true;
	echo "<p><a onclick=\"document.getElementById('additional_dates').style.display='block'\" >".__('Add More Dates','rsvpmaker')."</a> </p>
	<div id=\"additional_dates\" style=\"display: none;\">";
	$date = NULL;
	}
$t = $t + (60 * 60 * 24);
rsvpmaker_date_option($date, $i, date('Y-m-d',$t));
} // end for loop

if(isset($add_dates_div))
	echo "\n</div><!--add dates-->\n";

GetRSVPAdminForm($post->ID);

if(isset($_GET["debug"]))
{
echo '<pre>';
print_r($custom_fields);
echo '</pre>';

}

}
} // end draw event dates

if(!function_exists('template_schedule') )
{
function template_schedule($template) {

if(!is_array($template))
	$template = unserialize($template);

global $post;
global $wpdb;
global $rsvp_options;
//backward compatability
if(isset($template["week"]) && is_array($template["week"]))
	{
		$weeks = $template["week"];
		$dows = $template["dayofweek"];
	}
else
	{
		$weeks = array();
		$dows = array();
		$weeks[0] = (isset($template["week"])) ? $template["week"] : 0;
		$dows[0] = (isset($template["dayofweek"])) ? $template["dayofweek"] : 0;
	}

// default values
if(!isset($template["hour"])){
$template["hour"] = 19;
$template["minutes"] = '00';
}

if(isset($post->ID))
	printf('<p><a href="%s">%s</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post->ID),__('View/add/update events based on this template','rsvpmaker'));
global $wpdb;

$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));
$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));

echo '<p>'.__("Regular Schedule",'rsvpmaker').':</p><table id="skedtable"><tr><td>';

if($weeks[0] == 0)
	{
	$weeks = array(); // clear out any other values
	$dows = array();
	}
foreach($weekarray as $index => $label)
	{
		$class = ($index > 0) ? ' class="regular_sked" ' : '';
		$checked = (in_array($index,$weeks) || (($index == 0) && empty($weeks) ) ) ? ' checked="checked" ' : '';
		printf('<div><input type="checkbox" name="sked[week][]" value="%d" id="wkcheck%d" %s %s /> %s<div>',$index,$index, $checked, $class, $label);
	}

echo '</td><td id="daycolumn">';

foreach($dayarray as $index => $label)
	{
		$checked = (in_array($index,$dows)) ? ' checked="checked" ' : '';
		printf('<div><input type="checkbox" name="sked[dayofweek][]" value="%d" id="daycheck%d" %s class="days" /> %s<div>',$index,$index, $checked, $label);
	}

echo '</td><tr></table><div id="daymsg"></div>';

?>
<script>
jQuery(function () {
    jQuery('#wkcheck0').on('click', function () {
		if(this.checked){
        jQuery('#wkcheck1').prop('checked', false);
        jQuery('#wkcheck2').prop('checked', false);
        jQuery('#wkcheck3').prop('checked', false);
        jQuery('#wkcheck4').prop('checked', false);
        jQuery('#wkcheck5').prop('checked', false);
        jQuery('#wkcheck6').prop('checked', false);
        jQuery('#daycheck0').prop('checked', false);
        jQuery('#daycheck1').prop('checked', false);
        jQuery('#daycheck2').prop('checked', false);
        jQuery('#daycheck3').prop('checked', false);
        jQuery('#daycheck4').prop('checked', false);
        jQuery('#daycheck5').prop('checked', false);
        jQuery('#daycheck6').prop('checked', false);
        jQuery('#daycolumn').css('border', 'none');	
        jQuery('#daymsg').html('');
		}
    });
    jQuery('#wkcheck6').on('click', function () {
		if(this.checked){
        jQuery('#wkcheck0').prop('checked', false);
        jQuery('#wkcheck1').prop('checked', false);
        jQuery('#wkcheck2').prop('checked', false);
        jQuery('#wkcheck3').prop('checked', false);
        jQuery('#wkcheck4').prop('checked', false);
        jQuery('#wkcheck5').prop('checked', false);
		}
    });
    jQuery('.regular_sked').on('click', function () {
		if(this.checked){
        jQuery('#wkcheck0').prop('checked', false);
		if(!jQuery('#daycheck0').prop('checked') && !jQuery('#daycheck1').prop('checked') && !jQuery('#daycheck2').prop('checked') && !jQuery('#daycheck3').prop('checked') && !jQuery('#daycheck4').prop('checked') && !jQuery('#daycheck5').prop('checked') && !jQuery('#daycheck6').prop('checked'))
			{
				jQuery('#daycolumn').css('border', 'thin solid red');	
				jQuery('#skedtable td').css('padding', '5px');
				jQuery('#daymsg').html('<em><?php _e('choose one or more days of the week','rsvpmaker'); ?></em>');
			}
		}
    });
    jQuery('.days').on('click', function () {
		if(this.checked){
        jQuery('#daycolumn').css('border', 'none');	
        jQuery('#daymsg').html('');
		}
    });

});
</script>

<p><?php _e('Stop date (optional)','rsvpmaker');?>: <input type="text" name="sked[stop]" value="<?php if(isset($template["stop"])) echo $template["stop"];?>" placeholder="<?php _e('example','rsvpmaker'); echo ": ".date('Y').'-12-31' ?>" /> <em>(<?php _e('format','rsvpmaker'); ?>: "YYYY-mm-dd" or "+6 month" or "+1 year")</em></p>
<p><input type="checkbox" name="rsvpautorenew" id="rsvpautorenew" <?php if(get_post_meta($post->ID,'rsvpautorenew',true)) echo 'checked="checked"'?> /> <?php _e('Automatically add dates according to this schedule','rsvpmaker');?></em></p>
<?php

$h = (int) $template["hour"];
$minutes = $template["minutes"];
$duration = isset($template["duration"]) ? $template["duration"] : '';
$displayminutes = $displayhour = '';
?>
<table border="0">
<tr><td><?php _e("Time",'rsvpmaker'); ?>:</td>
<td><?php _e("Hour",'rsvpmaker'); ?>: <select name="sked[hour]" id="hour">
<?php
for($hour = 0; $hour < 24; $hour++)
{

if($hour == $h)
	$selected = ' selected = "selected" ';
else
	$selected = '';

	if($hour > 12)
		$displayhour .= "\n<option $selected " . 'value="' . $hour . '">' . ($hour - 12) . ' p.m.</option>';
	elseif($hour == 12)
		$displayhour .= "\n<option $selected " . 'value="' . $hour . '">12 p.m.</option>';
	elseif($hour == 0)
		$displayhour .= "\n<option $selected " . 'value="00">12 a.m.</option>';
	else
		$displayhour .= "\n<option $selected " . 'value="' . $hour . '">' . $hour . ' a.m.</option>';
}
echo $displayhour;
?>
</select>

<?php _e("Minutes",'rsvpmaker'); ?>: <select id="minutes" name="sked[minutes]">
<?php
echo '<option value="'.$minutes.'">'.$minutes.'</option>';
for($i = 0; $i < 60; $i++)
{
$zpad = ($i < 10) ? '0' : '';
	printf('<option value="%s%d">%s%d</option>',$zpad,$i,$zpad,$i);
}
?>
</select>
<?php echo __('Duration','rsvpmaker');?> <select name="sked[duration]">
<option value=""><?php echo __('Not set (optional)','rsvpmaker');?></option>
<option value="allday" <?php if(isset($duration) && ($duration == 'allday')) echo ' selected="selected" '; ?>><?php echo __("All day/don't show time in headline",'rsvpmaker');?></option>
<?php
if(!empty($duration) && ($duration != 'allday') && ($duration != 0))
	{
	if(strpos($duration,':'))
		$hlabel = '';
	else
		$hlabel = 'hours';	
	printf('<option value="%s" selected="selected">%s %s</option>',$duration, $duration, $hlabel);
	}
for($h = 0; $h < 24; $h++) {
	 $increment = ($h) ? 15 : 5;
	 if($h)
	 	echo '<option value="'.$h.'" >'.$h.' hours</option>';
	for($i = 0; $i < 60; $i += $increment)
		if($i >= 10)
			printf('<option value="%s:%s" >%s:%s</option>',$h,$i,$h,$i);
	} 
?>
</select>
<br />
<em><?php if(isset($debug)) echo $debug; 
_e("For an event starting at 12:30 p.m., you would select 12 p.m. and 30 minutes",'rsvpmaker'); ?>.</em>
</td>
          </tr>
</table>

<?php

	}
} // end template schedule

function save_rsvp_template_meta($postID) {

if(!isset($_POST["sked"]))
	return;
// we only care about saving template data

	global $wpdb;
	global $post;
	global $current_user;
	
	if($parent_id = wp_is_post_revision($postID))
		{
		$postID = $parent_id;
		}
	$sked = $_POST["sked"];
	if(empty($sked["dayofweek"]))
		$sked["dayofweek"][0] = 0;
	update_post_meta($postID, '_sked', $sked);
	if(isset($_POST["rsvpautorenew"]))
		update_post_meta($postID, 'rsvpautorenew', 1);
	else
		delete_post_meta($postID, 'rsvpautorenew');		
}

if(!function_exists('rsvpmaker_roles') )
{
function rsvpmaker_roles() {
// by default, capabilities for events are the same as for blog posts
global $wp_roles;

if(!isset($wp_roles) )
	$wp_roles = new WP_Roles();
// if roles persist from previous session, return
if($wp_roles->roles["administrator"]["capabilities"]["edit_rsvpmakers"])
	return;

if(isset($wp_roles->roles))
foreach ($wp_roles->roles as $role => $rolearray)
	{
	foreach($rolearray["capabilities"] as $cap => $flag)
		{
			if(strpos($cap,'post') )
				{
					$fbcap = str_replace('post','rsvpmaker',$cap);
					$wp_roles->add_cap( $role, $fbcap );
				}
		}
	}

}
}

if(! function_exists('GetRSVPAdminForm') )
{
function GetRSVPAdminForm($postID)
{
global $custom_fields;
global $post;
global $rsvp_options;

$rsvp_on = $custom_fields["_rsvp_on"][0];
$include_event = $custom_fields["_rsvp_confirmation_include_event"][0];
$login_required = $custom_fields["_rsvp_login_required"][0];
$rsvp_to = $custom_fields["_rsvp_to"][0];
$rsvp_instructions = $custom_fields["_rsvp_instructions"][0];
$rsvp_confirm = $custom_fields["_rsvp_confirm"][0];
$rsvp_form = $custom_fields["_rsvp_form"][0];
$rsvp_max = $custom_fields["_rsvp_max"][0];
$rsvp_count = $custom_fields["_rsvp_count"][0]; //else $rsvp_count = 1;
$rsvp_show_attendees = $custom_fields["_rsvp_show_attendees"][0];
$rsvp_captcha = $custom_fields["_rsvp_captcha"][0];
$rsvp_count_party = $custom_fields["_rsvp_count_party"][0];
$rsvp_yesno = $custom_fields["_rsvp_yesno"][0];

date_default_timezone_set('UTC');
if(isset($custom_fields["_rsvp_reminder"][0]) && $custom_fields["_rsvp_reminder"][0])
	{
	$t = strtotime($custom_fields["_rsvp_reminder"][0]);
	$remindyear = date('Y',$t);
	if($remindyear == 1970)
		$remindyear = '';
	else
		{
		$remindmonth = date('m',$t);
		$remindday = date('d',$t);
		$remindtime = date('H:i:s',$t);
		}
	}
	
if(isset($custom_fields["_rsvp_deadline"][0]) && $custom_fields["_rsvp_deadline"][0])
	{
	$t = (int) $custom_fields["_rsvp_deadline"][0];
	$deadyear = date('Y',$t);
	$deadmonth = date('m',$t);
	$deadday = date('d',$t);
	$deadtime = date('H:i:s',$t);
	}

if(isset($custom_fields["_rsvp_start"][0]) && $custom_fields["_rsvp_start"][0])
	{
	$t = (int) $custom_fields["_rsvp_start"][0];
	$startyear = date('Y',$t);
	$startmonth = date('m',$t);
	$startday = date('d',$t);
	$starttime = date('H:i:s',$t);
	}

?>
  <input type="checkbox" name="calendar_icons" value="1" <?php if($custom_fields["_calendar_icons"][0]) echo ' checked="checked" ';?> /> <?php _e('Show Add to Google / Download to Outlook (iCal) icons','rsvpmaker'); ?> 
	<br />
<?php

echo '<p id="timezone_options">';
if(!strpos($rsvp_options["time_format"],'T') )
{
?>
<input type="checkbox" name="add_timezone" value="1" <?php if($custom_fields["_add_timezone"][0]) echo ' checked="checked" '; ?> /><?php _e('Display timezone code as part of date/time','rsvpmaker'); echo ' '; ?>
<?php
}
?>
<input type="checkbox" name="convert_timezone" value="1" <?php if($custom_fields["_convert_timezone"][0]) echo ' checked="checked" '; ?> /><?php _e('Show timezone conversion button next to calendar icons','rsvpmaker'); ?>
<?php
echo '</p>';
if(empty($deadtime)) $deadtime = '23:59:59';
if(empty($starttime)) $starttime = '00:00:00';
if(empty($remindtime)) $remindtime = '00:00:00';
?>
<p>
  <input type="checkbox" name="setrsvp[on]" id="setrsvpon" value="1" <?php if( $rsvp_on ) echo 'checked="checked" ';?> />
<?php echo __('Collect RSVPs','rsvpmaker');?> <?php if( !$rsvp_on ) echo ' <strong style="color: red;">'.__('Check to activate','rsvpmaker').'</strong> ';?></p>
<div id="rsvpdetails">
  <input type="checkbox" name="setrsvp[login_required]" id="setrsvp[login_required]" value="1" <?php if( $login_required ) echo 'checked="checked" ';?> />
<?php echo __('Login required','rsvpmaker');?> <?php if( !$rsvp_on ) echo ' <strong style="color: red;">'.__('Check to activate','rsvpmaker').'</strong> ';?>
  <input type="checkbox" name="setrsvp[yesno]" id="setrsvp[yesno]" value="1" <?php if( $rsvp_yesno ) echo 'checked="checked" ';?> />
<?php echo __('Show Yes/No Radio Buttons','rsvpmaker');?> 
<br />  <input type="radio" name="setrsvp[show_attendees]" id="setrsvp[show_attendees]" value="1" <?php if( $rsvp_show_attendees == 1 ) echo 'checked="checked" ';?> />
<?php echo __(' Display attendee names and content of note field publicly','rsvpmaker');?>
 <input type="radio" name="setrsvp[show_attendees]" id="setrsvp[show_attendees]" value="2" <?php if( $rsvp_show_attendees == 2 ) echo 'checked="checked" ';?> />
<?php echo __(' Display attendees for logged in users','rsvpmaker');?>
 <input type="radio" name="setrsvp[show_attendees]" id="setrsvp[show_attendees]" value="0" <?php if( !$rsvp_show_attendees ) echo 'checked="checked" ';?> />
<?php echo __(' Do not display','rsvpmaker');?>
<?php
?>
<br />  <input type="checkbox" name="setrsvp[captcha]" id="setrsvp[captcha]" value="1" <?php if( $rsvp_captcha ) echo 'checked="checked" ';?> />
<?php echo __(' Include CAPTCHA challenge','rsvpmaker');?> <?php if( !$rsvp_captcha ) echo ' <strong style="color: red;">'.__('Check to activate','rsvpmaker').'</strong> ';?>

</p>

<div id="rsvpoptions">
<?php echo __('Email Address for Notifications','rsvpmaker');?>: <input id="setrsvp[to]" name="setrsvp[to]" type="text" value="<?php echo $rsvp_to;?>"><br />
<br /><?php echo __('Instructions for User','rsvpmaker');?>:<br />
<textarea id="setrsvp[instructions]" name="setrsvp[instructions]" cols="80" style="max-width: 95%;"><?php echo $rsvp_instructions;?></textarea>
<br /><?php echo __('Confirmation Message','rsvpmaker');?>:<br />
<textarea id="rsvp[confirm]" name="setrsvp[confirm]" cols="80" style="max-width: 95%;"><?php if(isset($rsvp_confirm)) echo $rsvp_confirm;?></textarea>
<br />
  <input type="checkbox" name="setrsvp[rsvpmaker_send_confirmation_email]" id="rsvpmaker_send_confirmation_email" <?php if(!isset($custom_fields['_rsvp_rsvpmaker_send_confirmation_email'][0]) || $custom_fields['_rsvp_rsvpmaker_send_confirmation_email'][0] ) echo ' checked="checked" ' ?> > <?php _e('Send confirmation emails','rsvpmaker'); ?>
  <input type="checkbox" name="setrsvp[confirmation_include_event]" id="rsvp_confirmation_include_event" <?php if( $include_event ) echo ' checked="checked" ' ?> > <?php _e('Include event listing with confirmation and reminders','rsvpmaker'); ?>
<?php
if(empty($custom_fields["_webinar_landing_page_id"][0]) || isset($_GET["youtube"]))
	echo '<br /><strong>'.__('Webinar Setup','rsvpmaker').'</strong><br />YouTube Live: <input type="text" name="youtube_live" /> <input type="checkbox" name="webinar_other" value="1" /> '.__('Other webinar','rsvpmaker').' <input type="checkbox" name="youtube_require_passcode" value="1" /> '.__('Require passcode to view','rsvpmaker').'<br /><em>'.__('If your event is a webinar, entering a YouTube Live url or checking &quot;Other webinar&quot; will create a landing page, plus suggested cofirmation and reminder messages to get you started. For YouTube Live, RSVPMaker adds the codes for the video player and chat.','rsvpmaker').'.</em>';
?>
<br /><?php 
if(isset($post->post_title) && !empty($post->post_title) )
	{
	printf('%s <a href="%s">%s</a>',__('More confirmation message and reminder message options available: ','rsvpmaker'),admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$post->ID,__('RSVP Reminders Editor','rsvpmaker'));
	$reminders = rsvpmaker_reminders_list($post->ID);
	if(!empty($reminders))
	echo $reminders;
	}
else
	_e('Additional Confirmation/Reminder Message options will appear after you save this event.','rsvpmaker');
?>
<br /><strong><?php echo __('Special Options','rsvpmaker'); ?></strong>

<table><tr><td><?php echo __('Deadline (optional)','rsvpmaker').'</td><td> '.__('Month','rsvpmaker');?>: <input type="text" name="deadmonth" id="deadmonth" value="<?php if(isset($deadmonth)) echo $deadmonth;?>" size="2" /> <?php echo __('Day','rsvpmaker');?>: <input type="text" name="deadday" id="deadday" value="<?php  if(isset($deadday)) echo $deadday;?>" size="2" /> <?php echo __('Year','rsvpmaker');?>: 
<input type="text" name="deadyear" id="deadyear" value="<?php  if(isset($deadyear)) echo $deadyear;?>" size="4" /> <?php rsvptimes ($deadtime,'deadtime'); ?> </td></tr>

<tr><td><?php echo __('Registration Starts (optional)','rsvpmaker').'</td><td>'.__('Month','rsvpmaker');?>: <input type="text" name="startmonth" id="startmonth" value="<?php  if(isset($startmonth)) echo $startmonth;?>" size="2" /> <?php echo __('Day','rsvpmaker');?>: <input type="text" name="startday" id="startday" value="<?php  if(isset($startday)) echo $startday;?>" size="2" /> <?php echo __('Year','rsvpmaker');?>: 
<input type="text" name="startyear" id="startyear" value="<?php  if(isset($startyear)) echo $startyear;?>" size="4" /> <?php rsvptimes($starttime,'starttime');?></td></tr>

<?php
if(!empty($remindday))
{ // only show if this was previously set
?>
<tr><td><?php echo __('Reminder (optional)','rsvpmaker').'</td><td>'.__('Month','rsvpmaker');?>: <input type="text" name="remindmonth" id="remindmonth" value="<?php  if(isset($remindmonth)) echo $remindmonth;?>" size="2" /> <?php echo __('Day','rsvpmaker');?>: <input type="text" name="remindday" id="remindday" value="<?php  if(isset($remindday)) echo $remindday;?>" size="2" /> <?php echo __('Year','rsvpmaker');?>: 
<input type="text" name="remindyear" id="remindyear" value="<?php  if(isset($remindyear)) echo $remindyear;?>" size="4" /> <?php rsvptimes($remindtime,'remindtime');?></td></tr>
<?php
}
?>

</table>

<br /><?php echo __('Show RSVP Count','rsvpmaker');?> <input type="checkbox" name="setrsvp[count]" id="setrsvp[count]" value="1" <?php if(isset($rsvp_count) && $rsvp_count) echo ' checked="checked" ';?> /> 

<br /><?php echo __('Maximum participants','rsvpmaker');?> <input type="text" name="setrsvp[max]" id="setrsvp[max]" value="<?php if(isset($rsvp_max)) echo $rsvp_max;?>" size="4" /> (<?php echo __('0 for none specified','rsvpmaker');?>)
<br /><?php echo __('Time Slots','rsvpmaker');?>:

<select name="setrsvp[timeslots]" id="setrsvp[timeslots]">
<option value="0">None</option>
<option value="0:30" <?php if(isset($custom_fields["_rsvp_timeslots"][0]) && ($custom_fields["_rsvp_timeslots"][0] == '0:30')) echo ' selected = "selected" ';?> >30 minutes</option>
<?php
$tslots = (int) $custom_fields["_rsvp_timeslots"][0];
for($i = 1; $i < 13; $i++)
	{
	$selected = ($i == $tslots) ? ' selected = "selected" ' : '';
	echo '<option value="'.$i.'" '.$selected.">$i-hour slots</option>";
	}
;?>
</select>
<br /><em><?php echo __('Used for volunteer shift signups. Duration must also be set.','rsvpmaker');?></em>

<br /><?php echo __('RSVP Form','rsvpmaker');?> (<a href="#" id="enlarge">Enlarge</a>):<br />
<textarea id="rsvpform" name="setrsvp[form]" cols="120" rows="5" style="max-width: 95%;"><?php if(isset($rsvp_form)) echo htmlentities($rsvp_form);?></textarea>
<?php rsvp_form_setup_form($rsvp_form); ?>
<div>
 <button id="create-form">Generate form</button>
</div>
<br />
<?php

if (class_exists('Stripe_Checkout_Functions') && empty($rsvp_options["stripe"]))
	{
	$s = ( !empty($custom_fields["_rsvp_stripe"][0]) ) ? 'checked="checked"' : '';
	echo '<h3>'.__('WP Simple Pay Lite for Stripe plugin detected','rsvpmaker').'</h3><p><input type="checkbox" name="setrsvp[stripe]" value="1" '.$s.' /> '.__('Use Stripe instead of PayPal','rsvpmaker').'</p>';
	}

if(!empty($rsvp_options["paypal_config"]) || !empty($rsvp_options["stripe"]) || !empty($custom_fields["_rsvp_stripe"][0]) || !empty($rsvp_options["cash_or_custom"]))
{
?>
<p><strong><?php echo __('Pricing','rsvpmaker');?></strong></p>
<p><?php echo __('You can set a different price for members vs. non-members, adults vs. children, etc.','rsvpmaker');?></p>
<p><input type="radio" name="setrsvp[count_party]" value="1" <?php if($rsvp_count_party) echo ' checked="checked" '; ?> > Multiply price times size of party
<br /><input type="radio" name="setrsvp[count_party]" value="0" <?php if(!$rsvp_count_party) echo ' checked="checked" '; ?> > Let user specify number of admissions per category
</p>
<?php

echo '<p>'.__('Optionally, you can add a time limit on specific prices, if for example you are offering "early bird" pricing on registration, after which the price goes up. Enter a full date and time. Example:','rsvpmaker').' '.date('Y-m-d').'  23:59:00 or '.date('F j, Y').' 11:59 pm '.__('for midnight tonight','rsvpmaker');

if($rsvp_count_party)
	{
		printf('<p>%s</p>',__('You can also specify fields that should not be displayed depending on price selections. Example: <em>The meal options at a conference should be disabled for attendees who choose "workshop only" pricing, or the dinner options should be disabled for those who select the lunch only.</em>','rsvptoast'));
	}

$hide = array();
if(isset($custom_fields['_hiddenrsvpfields'][0]))
	{
		$hide = unserialize($custom_fields['_hiddenrsvpfields'][0]);
	}

if(isset($custom_fields["_per"][0]))
	{
	$per = unserialize($custom_fields["_per"][0]);
	}

 if(empty($per["unit"][0]))
	{
	$per = array();
	$per["unit"][0] = __("Tickets",'rsvpmaker');
	}

	$defaultfields = array('first','last','email','phone','phone_type');
	preg_match_all('/(textfield|selectfield|radio|checkbox)="([^"]+)"/',$rsvp_form,$matches);
	$newfields = array_diff($matches[2],$defaultfields);

echo '<div id="priceper">';

$start = 1;

foreach($per["unit"] as $i => $value)
{
$start = $i + 1;
?>
<div class="priceblock" id="block_<?php echo $i;?>">
<div class="pricelabel"><?php _e('Units','rsvpmaker');?>:</div><div class="pricevalue"><input name="unit[<?php if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["unit"][$i])) echo $per["unit"][$i];?>" /></div>
<div class="pricelabel">@ <?php _e('Price','rsvpmaker');?>:</div><div class="pricevalue"><input name="price[<?php  if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["price"][$i])) echo $per["price"][$i];?>" /> <?php if(isset($rsvp_options["paypal_currency"])) echo $rsvp_options["paypal_currency"]; ?></div>
<div class="pricelabel"><?php _e('Deadline (optional)','rsvpmaker');?>:</div><div class="pricevalue"><input name="price_deadline[<?php  if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["price_deadline"][$i])) echo date("Y-m-d H:i:s", (int) $per["price_deadline"][$i]); ?>" placeholder="<?php echo date('Y-m-d 23:59:00'); ?>" /></div>
<?php
if($rsvp_count_party && !empty($newfields))
	{
		foreach($newfields as $field)
			{
				if(isset($hide[$i]) && is_array($hide[$i]) && in_array($field,$hide[$i]))
					{
						$showcheck = '';
						$hidecheck = ' checked="checked" ';
					}
				else
					{
						$showcheck = ' checked="checked" ';
						$hidecheck = '';
					}
				printf('<div class="pricelabel">%s:</div><div class="pricevalue"><input type="radio" name="showhide[%d][%s]" value="0" %s /> Show <input type="radio" name="showhide[%d][%s]" value="1" %s /> Hide</div>',$field,$i,$field,$showcheck,$i,$field,$hidecheck);
			}
	}
?>
</div>
<?php
}

$pad = ($start < 3) ? 5 : 1;

for($i = $start; $i < ($start + $pad); $i++)
{
$starterblanks = $i + 1;
?>
<div class="priceblock" id="block_<?php echo $i;?>">
<div class="pricelabel"><?php _e('Units','rsvpmaker');?>:</div><div class="pricevalue"><input name="unit[<?php if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["unit"][$i])) echo $per["unit"][$i];?>" /></div>
<div class="pricelabel">@ <?php _e('Price','rsvpmaker');?>:</div><div class="pricevalue"><input name="price[<?php  if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["price"][$i])) echo $per["price"][$i];?>" /> <?php if(isset($rsvp_options["paypal_currency"])) echo $rsvp_options["paypal_currency"]; ?></div>
<div class="pricelabel"><?php _e('Deadline (optional)','rsvpmaker');?>:</div><div class="pricevalue"><input name="price_deadline[<?php  if(isset($i)) echo $i;?>]" value="<?php  if(isset($per["price_deadline"][$i])) echo date("Y-m-d H:i:s", (int) $per["price_deadline"][$i]); ?>" placeholder="<?php echo date('Y-m-d 23:59:00'); ?>" /></div>
<?php
if($rsvp_count_party && !empty($newfields))
	{
		foreach($newfields as $field)
			{
				printf('<div class="pricelabel">%s:</div><div class="pricevalue"><input type="radio" name="showhide[%d][%s]" value="0" checked="checked" /> Show <input type="radio" name="showhide[%d][%s]" value="1" /> Hide</div>',$field,$i,$field,$i,$field);
			}
	}
?>
</div>
<?php
}
echo '</div>';
?>
<p><a id="add_blanks" href="#">+ More Prices</a></p>
<script type="text/javascript">
jQuery(document).ready(function($) {
var blankcount = <?php echo $starterblanks; ?>;
var lastblank = blankcount - 1;
var blank = $('#block_' + lastblank).html();
$('#add_blanks').click(function(event){
	event.preventDefault();
var newblank = '<' + 'div class="priceblock" id="blank_'+blankcount+'">' +
	blank.replace(/\[[0-9]+\]/g,'['+blankcount+']') +
	'<' + '/div>';
blankcount++;
$('#priceper').append(newblank);
});


});
</script>
<?php

if(isset($_GET["debug"]))
{
	$defaultfields = array('first','last','email','phone','phone_type');
	preg_match_all('/(textfield|selectfield|radio|checkbox)="([^"]+)"/',$rsvp_form,$matches);
	$newfields = array_diff($matches[2],$defaultfields);
	if(!empty($newfields))
	rsvpmaker_debug_log(var_export($newfields,true));
}

} // end paypal enabled section
?>
</div><!-- end rsvpdetails -->
<?php
if(!$rsvp_on)
{
?>
<script language="javascript">
jQuery(document).ready(function( $ ) {
$("#rsvpdetails").hide();
});
</script>
<?php
}
?>
</div>
<?php
} } // end rsvp admin ui

function ajax_rsvp_email_lookup () {
if(!isset($_REQUEST['rsvp_email_lookup']))
	return;
$email = $_REQUEST['email_search'];
$event = $_REQUEST['post_id'];
$p = get_permalink($event);
global $wpdb;
$wpdb->show_errors();
$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix.'rsvpmaker WHERE email LIKE %s AND event=%d',$email.'%',$event);
$results = $wpdb->get_results($sql);
if($results)
{	
	echo '<div class="previous_rsvp_prompt">'.__('Did you RSVP previously?','rsvpmaker').'</div>';
	foreach($results as $row)
	{
	$out = 'RSVP ';
	$out .= ($row->yesno) ? __('YES','rsvpmaker') : __('NO','rsvpmaker');
	$out .= ' '.$row->first.' '.$row->last;
	$sql = $wpdb->prepare("SELECT count(*) FROM ".$wpdb->prefix.'rsvpmaker WHERE master_rsvp=%d',$row->id);
	$guests = $wpdb->get_var($sql);
	if($guests)
		$out .= ' + '.$guests.' '.__('guests','rsvpmaker');
	printf('<div><a href="%s">%s</a> %s</div>',add_query_arg(array('e' => $row->email,'update' => $row->id),$p),__('Update','rsvpmaker'),$out);
	}
}
	else echo '';
die();
}

add_action('init','ajax_rsvp_email_lookup');

function rsvp_form_setup_form($rsvp_form) {

$hidden = (strpos($rsvp_form,'hidden="email"'));
$email_list_ok = (strpos($rsvp_form,'checkbox="email_list_ok"'));
preg_match('/textfield="([^"]+)"/',$rsvp_form,$match);
$emailfirst = ($match[1] == 'email') ? ' checked="checked" ' : '';
?>
<div id="rsvp-dialog-form" title="Form setup">
  <p><?php _e('First Name, Last Name, Email (required)','rsvpmaker');?> Display options: <select id="name_email_hidden" name="name_email_hidden">
	  <option value="email_first" <?php if($emailfirst) echo 'selected="selected"'; ?> ><?php _e('email, then name','rsvpmaker');?></option>
	  <option value="name_first" <?php if(!$emailfirst && !$hidden) echo 'selected="selected"'; ?> ><?php _e('name, then email','rsvpmaker');?></option>
	  <option value="hidden" <?php if($hidden) echo 'selected="selected"'; ?> ><?php _e('hidden (use with login required)','rsvpmaker');?></option>
	  </select>
</p>
  <p><?php _e('For radio buttons or select fields, use the format Label:option 1, option 2','rsvpmaker');?> (<em><?php _e('Meal:Steak,Chicken,Vegitarian','rsvpmaker');?></em>)</p> 
    <fieldset>
<?php
	
preg_match_all('/(\[.+\])/',$rsvp_form,$matches);
$codes = implode($matches[1]);
$codes .= '[rsvpfield textfield=""][rsvpfield textfield=""][rsvpfield textfield=""]';
echo do_shortcode($codes);
global $extrafield;
printf('<input type="hidden" id="extrafields" value="%s" />',$extrafield);
?>
<p><input type="checkbox" name="guests" id="guests" value="1" <?php if(strpos($rsvp_form,'rsvpguests')) echo 'checked="checked"'; ?> /> <?php _e('Include guest form','rsvpmaker');?> <input type="checkbox" name="note" id="note" value="1" <?php if(strpos($rsvp_form,'rsvpnote')) echo 'checked="checked"'; ?>> <?php _e('Include notes field','rsvpmaker');?> <input type="checkbox" name="emailcheckbox" id="emailcheckbox" value="1" <?php if($email_list_ok) echo 'checked="checked"'; ?> > <?php _e('Include "Add me to email list" checkbox','rsvpmaker');?></p>
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
    </fieldset>
</div> 
<?php
}

if(!function_exists('capture_email') )
{
function capture_email($rsvp) {
//placeholder function, may be overriden to sign person up for email list

//or use this action, triggered by email_list_ok parameter in form
if(isset($rsvp["email_list_ok"]) && $rsvp["email_list_ok"])
	do_action('rsvpmaker_email_list_okay',$rsvp);

} } // end capture email

add_action('init','save_replay_rsvp');

if(!function_exists('save_replay_rsvp') )
{
function save_replay_rsvp() {

global $wpdb;
global $rsvp_options;
global $rsvp_id;

if(isset($_POST["replay_rsvp"]) && wp_verify_nonce($_POST['rsvp_replay_nonce'],'rsvp_replay') )
	{

if ( get_magic_quotes_gpc() )
    $_POST = array_map( 'stripslashes_deep', $_POST );
$req_uri = trim($_POST["replay_rsvp"]);
$req_uri .= (strpos($req_uri,'?')) ? '&' : '?';
//sanitize input
foreach($_POST["profile"] as $name => $value)
	$rsvp[$name] = esc_attr($value);
if(isset($_POST["note"]))
	$note = esc_attr($_POST["note"]);
else
	$note = "";

$answer = "YES";

$event = (!empty($_POST["event"])) ? (int) $_POST["event"] : 0;
if(!$event)
	die('Event ID not set');
// page hasn't loaded yet, so retrieve post variables based on event
$post = get_post($event);
//get rsvp_to
$custom_fields = get_post_custom($post->ID);
$rsvp_to = $custom_fields["_rsvp_to"][0];
$rsvp_confirm = (isset($custom_fields["_rsvp_confirm"][0])) ? $custom_fields["_rsvp_confirm"][0] : NULL;

//if permalinks are not turned on, we need to append to query string not add our own ?

if(empty($rsvp["email"]) || empty($rsvp["first"]) || empty($rsvp["last"]) )
	{
	header('Location: '.$req_uri.'&err='.urlencode(__('Required fields: first, last, email','rsvpmaker')));
	exit();
	}

if(isset($custom_fields["_rsvp_captcha"][0]) && $custom_fields["_rsvp_captcha"][0])
	{
	if(!isset($_SESSION["captcha_key"]))
		session_start();
	if($_SESSION["captcha_key"] != md5($_POST['captcha']) )	
		{
		header('Location: '.$req_uri.'&err='.urlencode('security code not entered correctly! Please try again.'));
		exit();
		}
	}

if(!empty($rsvp_options["rsvp_recaptcha_site_key"]) && !empty($rsvp_options["rsvp_recaptcha_secret"]))
	{
	if(!rsvpmaker_recaptcha_check ($rsvp_options["rsvp_recaptcha_site_key"],$rsvp_options["rsvp_recaptcha_secret"]))	{
		header('Location: '.$req_uri.'&err='.urlencode('failed recaptcha test'));
		exit();
		}	
	}
		
if(isset($_POST["required"]))
	{
		$required = explode(",",$_POST["required"]);
		$missing = "";
		foreach($required as $r)
			{
				if(empty($rsvp[$r]))
					$missing .= $r." ";
			}
		if($missing != '')
			{
			header('Location: '.$req_uri.'&err='.urlencode('missing required fields: '.$missing));
			exit();
			}
	}
if( preg_match_all('/http/',$_POST["note"],$matches) > 2 )
	{
	header('Location: '.$req_uri.'&err=Invalid input');
	exit();
	}

if( preg_match("|//|",implode(' ',$rsvp)) )
	{
	header('Location: '.$req_uri.'&err=Invalid input');
	exit();
	}

if(isset($rsvp["email"]))
	{
	// assuming the form includes email, test to make sure it's a valid one
	
	if( !apply_filters('rsvmpmaker_spam_check',$rsvp["email"]) )
		{
		header('Location: '.$req_uri.'&err='.urlencode('Invalid input.') );
		exit();
		}	
	if(!filter_var($rsvp["email"], FILTER_VALIDATE_EMAIL))
		{
		header('Location: '.$req_uri.'&err='.urlencode('Invalid email.') );
		exit();
		}
	}

if(isset($_POST["onfile"]))
	{
	$sql = $wpdb->prepare("SELECT details FROM ".$wpdb->prefix."rsvpmaker WHERE event='$event' AND email LIKE %s AND first LIKE %s AND last LIKE %s  ORDER BY id DESC",$rsvp["email"],$rsvp["first"],$rsvp["last"]);
	
	$details = $wpdb->get_var($sql);
	if($details)
		$contact = unserialize($details);
	else	
		$contact = rsvpmaker_profile_lookup($rsvp["email"]);
		
	if($contact)
		{
		foreach($contact as $name => $value)
			{
			if(!isset($rsvp[$name]))
				$rsvp[$name] = $value;
			}
		}
	}

global $current_user; // if logged in

$future = is_rsvpmaker_future($event, 1); // if start time in the future (or within one hour)
$yesno = ($future) ? 1 : 2;// 2 for replay
$rsvp_sql = $wpdb->prepare(" SET first=%s, last=%s, email=%s, yesno=%d, event=%d, note=%s, details=%s, participants=%d, user_id=%d ", $rsvp["first"], $rsvp["last"], $rsvp["email"],$yesno,$event, $note, serialize($rsvp), 1, $current_user->ID );

capture_email($rsvp);

$rsvp_id = (isset($_POST["rsvp_id"])) ? $_POST["rsvp_id"] : 0;

if($rsvp_id)
	{
	$rsvp_sql = "UPDATE ".$wpdb->prefix."rsvpmaker ".$rsvp_sql." WHERE id=$rsvp_id";
	$wpdb->show_errors();
	$wpdb->query($rsvp_sql);
	}
else
	{
	$rsvp_sql = "INSERT INTO ".$wpdb->prefix."rsvpmaker ".$rsvp_sql;
	$wpdb->show_errors();
	$wpdb->query($rsvp_sql);
	$rsvp_id = $wpdb->insert_id;
	}

setcookie ( 'rsvp_for_'.$event, $rsvp_id, time()+(60*60*24*90), "/" , $_SERVER['SERVER_NAME'] );

if($future)
{
$cleanmessage = '';
foreach($rsvp as $name => $value)
	$cleanmessage .= $name.": ".$value."\n";

$subject = __('You registered for ','rsvpmaker')." ".$post->post_title;
if(!empty($_POST["note"]))
	$cleanmessage .= 'Note: '.stripslashes($_POST["note"]);
	rsvp_notifications ($rsvp,$rsvp_to,$subject,$cleanmessage,$rsvp_confirm);
}
else
{
	// cron for follow up messages

$sql = "SELECT * 
FROM  `$wpdb->postmeta` 
WHERE meta_key REGEXP '_rsvp_reminder_msg_[0-9]{1,2}'
AND  `post_id` = " . $event;
	$results = $wpdb->get_results($sql);
	//$msg = var_export($results,true);
	if($results)
	foreach ($results as $row)
		{
			$parts = explode('_msg_',$row->meta_key);
			$hours = $parts[1];
			rsvpmaker_replay_cron($event, $rsvp_id, $hours);
			//$msg .= sprintf('event %s rsvp_id %s hours %s',$event, $rsvp_id, $hours);
		}
	//wp_mail('david@carrcommunications.com','replay_cron',$msg,'From: david@rsvpmaker.com');
}
	$landing_id = (int) $_POST['landing_id'];
	$passcode = get_post_meta($landing_id,'_webinar_passcode',true);
	$landing_permalink = $req_uri . '&webinar='.$passcode.'&e='.$rsvp["email"];
	header('Location: '.$landing_permalink);
	exit();
	}

} } // end save replay rsvp

if(!function_exists('save_rsvp') )
{
function save_rsvp() {

global $wpdb;
global $rsvp_options;
global $post;
global $rsvp_id;
global $rsvpdata;
$rsvp_id = (isset($_POST["rsvp_id"])) ? $_POST["rsvp_id"] : 0;

if(isset($_POST["withdraw"]) )
	{
		 if( !wp_verify_nonce($_POST['withdraw_nonce'],'withdraw_nonce'))
		 die('nonce check failed');
		foreach($_POST["withdraw"] as $withdraw_id)
			{
			$wpdb->query("UPDATE ".$wpdb->prefix."rsvpmaker SET yesno=0 WHERE id=$withdraw_id " );
			}
	}

if(isset($_POST["yesno"]) && wp_verify_nonce($_POST['rsvp_nonce'],'rsvp') )
	{

$_POST = stripslashes_deep ($_POST);

//sanitize input
foreach($_POST["profile"] as $name => $value)
	$rsvp[$name] = esc_attr($value);
if(isset($_POST["note"]))
	$note = esc_attr($_POST["note"]);
else
	$note = "";

$yesno = (int) $_POST["yesno"];
$answer = ($yesno) ? __("YES",'rsvpmaker') : __("NO",'rsvpmaker');

$event = (!empty($_POST["event"])) ? (int) $_POST["event"] : 0;
if(!$event)
	die('Event ID not set');
// page hasn't loaded yet, so retrieve post variables based on event
$post = get_post($event);
//get rsvp_to
$custom_fields = get_post_custom($post->ID);
$rsvp_to = $custom_fields["_rsvp_to"][0];
$rsvp_confirm = (isset($custom_fields["_rsvp_confirm"][0])) ? $custom_fields["_rsvp_confirm"][0] : NULL;

//if permalinks are not turned on, we need to append to query string not add our own ?

if(empty($rsvp["email"]) || empty($rsvp["first"]) || empty($rsvp["last"]) )
	{
	$req_uri = site_url('?post_type=rsvpmaker&p='.$event);
	header('Location: '.$req_uri.'&err='.urlencode(__('Required fields: first, last, email','rsvpmaker')));
	exit();
	}
if(is_admin())
{
	$req_uri = admin_url('edit.php?page=rsvp&post_type=rsvpmaker&event='.$event);
}
else
{
$req_uri = site_url('?post_type=rsvpmaker&p='.$event.'&e='.$rsvp["email"]);
}

if(isset($custom_fields["_rsvp_captcha"][0]) && $custom_fields["_rsvp_captcha"][0])
	{
	if(!isset($_SESSION["captcha_key"]))
		session_start();
	if($_SESSION["captcha_key"] != md5($_POST['captcha']) )	
		{
		header('Location: '.$req_uri.'&err='.urlencode('security code not entered correctly! Please try again.'));
		exit();
		}
	}

if(!empty($rsvp_options["rsvp_recaptcha_site_key"]) && !empty($rsvp_options["rsvp_recaptcha_secret"]))
	{
	if(!rsvpmaker_recaptcha_check ($rsvp_options["rsvp_recaptcha_site_key"],$rsvp_options["rsvp_recaptcha_secret"]))	{
		header('Location: '.$req_uri.'&err='.urlencode('failed recaptcha test'));
		exit();
		}	
	}

if(isset($_POST["required"]))
	{
		$required = explode(",",$_POST["required"]);
		$missing = "";
		foreach($required as $r)
			{
				if(empty($rsvp[$r]))
					$missing .= $r." ";
			}
		if($missing != '')
			{
			header('Location: '.$req_uri.'&err='.urlencode('missing required fields: '.$missing));
			exit();
			}
	}
if( isset($_POST["note"]) && preg_match_all('/http/',$_POST["note"],$matches) > 2 )
	{
	header('Location: '.$req_uri.'&err=Invalid input');
	exit();
	}

if( preg_match("|//|",implode(' ',$rsvp)) )
	{
	header('Location: '.$req_uri.'&err=Invalid input');
	exit();
	}

if(isset($rsvp["email"]))
	{
	// assuming the form includes email, test to make sure it's a valid one
	
	if( !apply_filters('rsvmpmaker_spam_check',$rsvp["email"]) )
		{
		header('Location: '.$req_uri.'&err='.urlencode('Invalid input.') );
		exit();
		}	
	if(!filter_var($rsvp["email"], FILTER_VALIDATE_EMAIL))
		{
		header('Location: '.$req_uri.'&err='.urlencode('Invalid email.') );
		exit();
		}
	}

if($rsvp_id)
	{
	$sql = "SELECT details FROM ".$wpdb->prefix."rsvpmaker WHERE email !='' AND id=".$rsvp_id;
	$details = $wpdb->get_var($sql);
	if($details)
	{
	$contact = unserialize($details);
	if(is_array($contact))
		{
		foreach($contact as $name => $value)
			{
			if(!isset($rsvp[$name]))
				$rsvp[$name] = $value;
			}
		}
		
	}
	else
		$rsvp_id = NULL;
	}

if(isset($_POST["payingfor"]) && is_array($_POST["payingfor"]) )
	{
	$rsvp["total"] = 0;
	$participants = 0;
	$rsvp["payingfor"] = "";
	foreach($_POST["payingfor"] as $index => $value)
		{
		$value = (int) $value;
		$unit = esc_attr($_POST["unit"][$index]);
		$price = (float) $_POST["price"][$index];
		$cost = $value * $price;
		if(isset($rsvp["payingfor"]) && $rsvp["payingfor"])
			$rsvp["payingfor"] .= ", ";
		$rsvp["payingfor"] .= "$value $unit @ ".number_format($price,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]) . ' '.$rsvp_options["paypal_currency"];
		$rsvp["total"] += $cost;
		$participants += $value;
		}
	}

if( isset($_POST["timeslot"]) && is_array($_POST["timeslot"]) )
	{
	fix_timezone();
	$participants = $rsvp["participants"] = (int) $_POST["participants"];
	$rsvp["timeslots"] = ""; // ignore anything retrieved from prev rsvps
	foreach($_POST["timeslot"] as $slot)
		{
		if(!empty($rsvp["timeslots"]))
			$rsvp["timeslots"] .=  ", ";
		$rsvp["timeslots"] .= date('g:i A',$slot);
		}
	}

if(!isset($participants) && $yesno)
	{
	// if they didn't specify # of participants (paid tickets or volunteers), count the host plus guests
	$participants = 1;
	if(!empty($_POST["guest"]["first"]))
	{
	foreach($_POST["guest"]["first"] as $first)
		if($first)
			$participants++;
	}
	
	if(isset($_POST["guestdelete"]))
		$participants -= sizeof($_POST["guestdelete"]);
	}
if(!$yesno)
	$participants = 0; // if they said no, they don't count

$rsvp["payingfor"] = "";

if($participants && isset($_POST["guest_count_price"]))
	{
		$index = (int) $_POST["guest_count_price"];
		$per = unserialize($custom_fields["_per"][0]);
		$price = $per["price"][$index];
		$unit = $per["unit"][$index];
		$rsvp["total"] = $price * $participants;
		$rsvp["payingfor"] .= "$participants $unit @ ".number_format($price,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]);		
		$rsvp["pricechoice"] = $index;
	}

global $current_user; // if logged in

$rsvp_sql = $wpdb->prepare(" SET first=%s, last=%s, email=%s, yesno=%d, event=%d, note=%s, details=%s, participants=%d, user_id=%d ", $rsvp["first"], $rsvp["last"], $rsvp["email"],$yesno,$event, $note, serialize($rsvp), $participants, $current_user->ID );

capture_email($rsvp);

if($rsvp_id)
	{
	$rsvp_sql = "UPDATE ".$wpdb->prefix."rsvpmaker ".$rsvp_sql." WHERE id=$rsvp_id";
	$wpdb->show_errors();
	$wpdb->query($rsvp_sql);
	}
else
	{
	$rsvp_sql = "INSERT INTO ".$wpdb->prefix."rsvpmaker ".$rsvp_sql;
	$wpdb->show_errors();
	$wpdb->query($rsvp_sql);
	$rsvp_id = $wpdb->insert_id;
	}

setcookie ( 'rsvp_for_'.$post->ID, $rsvp_id, time()+60*60*24*90, "/" , $_SERVER['SERVER_NAME'] );
setcookie ( 'rsvpmaker', $rsvp_id, time()+60*60*24*90, "/" , $_SERVER['SERVER_NAME'] );

if(isset($_POST["timeslot"]))
	{
	$participants = (int) $_POST["participants"];
	// clear previous response, if any
	$wpdb->query("DELETE FROM ".$wpdb->prefix."rsvp_volunteer_time WHERE rsvp=$rsvp_id");
	foreach($_POST["timeslot"] as $slot)
		{
		$slot = (int) $slot;
		$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."rsvp_volunteer_time SET time=%d, event=%d, rsvp=%d, participants=%d",$slot,$post->ID,$rsvp_id,$participants); 
		$wpdb->query($sql);
		}
	}

//get start date
$rows = get_rsvp_dates($event);
$row = $rows[0];
$t = strtotime($row["datetime"]);
$date = date('M j',$t);

$cleanmessage = '';
foreach($rsvp as $name => $value)
	{
	if(!empty($value))
		$cleanmessage .= $name.": ".$value."\n";
	}
$guestof = $rsvp["first"]." ".$rsvp["last"];
$guest_sql = array();
$guest_text = array();

if(isset($_POST["guest"]["first"]) )
foreach ($_POST["guest"]["first"] as $index => $first)
	{
		if(!empty($first) || !empty($_POST["guest"]["last"][$index]) )
			{
			$guest_sql[$index] = $wpdb->prepare(" SET event=%d, yesno=%d, `master_rsvp`=%d, `guestof`=%s, `first` = %s, `last` = %s",$event, $yesno, $rsvp_id, $guestof, $first, $_POST["guest"]["last"][$index]);
			$guest_text[$index] = sprintf("Guest: %s %s\n",$first,$_POST["guest"]["last"][$index]);
			}
	}

if(sizeof($guest_sql))
foreach($_POST["guest"] as $field => $column)
	{
		foreach ($column as $index => $value)	
			{
				if(empty($guest_text[$index])) $guest_text[$index] = '';
				if(isset($guest_sql[$index]))
					{
					$newrow[$index][$field] = $value;
					if(($field != 'first') && ($field != 'last') && ($field != 'id'))
						$guest_text[$index] .= sprintf("%s: %s\n",$field,$value);
					}
			}
	}
if(sizeof($guest_sql))
	{
		foreach($guest_sql as $index => $sql)
			{
				$sql .= $wpdb->prepare(", `details`=%s ", serialize( $newrow[$index]) );
				$id = (isset($_POST["guest"]["id"][$index])) ? (int) $_POST["guest"]["id"][$index] : 0;
				if(isset($_POST["guestdelete"][$id]))
					{
					$gd = (int) $_POST["guestdelete"][$id];
					$sql = "DELETE FROM ".$wpdb->prefix."rsvpmaker WHERE id=". $gd;
					$guest_text[$index] = __('Deleted:','rsvpmaker')."\n".$guest_text[$index];				
					}
				elseif($id)
					$sql = "UPDATE ".$wpdb->prefix."rsvpmaker ".$sql.' WHERE id='.$id;
				else
					$sql = "INSERT INTO ".$wpdb->prefix."rsvpmaker ".$sql;
				$wpdb->query($sql);
			}
	}

if(!empty($guest_text))
	$cleanmessage .= implode("\n",$guest_text);

if(!is_admin() )
{
if(!empty($_POST["note"]))
	$cleanmessage .= 'Note: '.stripslashes($_POST["note"]);
update_post_meta($post->ID,'_rsvp_'.$rsvp["email"],$cleanmessage);

$include_event = get_post_meta($post->ID, '_rsvp_confirmation_include_event', true);
if($include_event)
	{
	$embed = event_to_embed($post->ID);
	$cleanmessage .= "\n\n".$embed["content"];
	}
$rsvpdata["rsvpdetails"] = $cleanmessage;
$rsvpdata["rsvpmessage"] = $rsvp_confirm; // confirmation message from editor
$rsvpdata["rsvptitle"] = $post->post_title;
$rsvpdata["rsvpyesno"] = $answer;
$rsvpdata["rsvpdate"] = $date;
$login_required = get_post_meta($post->ID, '_rsvp_login_required', true);
$rsvplink = ($login_required) ? wp_login_url( $req_uri ) : $req_uri;
if(strpos($rsvplink,'?') )
	$rsvp_options["rsvplink"] = str_replace('?','&',$rsvp_options["rsvplink"]);
$rsvp_options["rsvplink"] = sprintf($rsvp_options["rsvplink"],$rsvplink);
$rsvpdata["rsvpupdate"] = preg_replace('/#rsvpnow">[^<]+/','#rsvpnow">'.__('Update RSVP','rsvptoast'),str_replace('*|EMAIL|*',$rsvp["email"].'&update='.$rsvp_id, $rsvp_options["rsvplink"]));

rsvp_notifications_via_template ($rsvp,$rsvp_to,$rsvpdata);
//rsvp_notifications ($rsvp,$rsvp_to,$subject,$cleanmessage,$rsvp_confirm);
}
	do_action('rsvp_recorded',$rsvp);
	header('Location: '.$req_uri.'&rsvp='.$rsvp_id.'#rsvpmaker_top');
	exit();
	}

} } // end save rsvp

if(!function_exists('rsvp_notifications') )
{
function rsvp_notifications ($rsvp,$rsvp_to,$subject,$message, $rsvp_confirm = '') {

include 'rsvpmaker-ical.php';

global $post;

$message = wpautop($message);
if(!empty($rsvp_confirm))
	$rsvp_confirm = wpautop($rsvp_confirm);

global $rsvp_options;

	$mail["to"] = $rsvp_to;
	$mail["from"] = $rsvp["email"];
	$mail["fromname"] = $rsvp["first"].' '.$rsvp["last"];
	$mail["subject"] = $subject;
	$mail["html"] = $message;
	rsvpmaker_tx_email($post, $mail);

	if(!empty($rsvp_confirm))
	{
	$mail["html"] = $rsvp_confirm . "\n\n".$message;
	}
	if(isset($post->ID)) // not for replay
	$mail["ical"] = rsvpmaker_to_ical_email ($post->ID, $rsvp_to, $rsvp["email"]);
	$mail["to"] = $rsvp["email"];
	$mail["from"] = $rsvp_to;
	$mail["fromname"] = get_bloginfo('name');
	$mail["subject"] = "Confirming ".$subject;
	rsvpmaker_tx_email($post, $mail);
	
} } // end rsvp notifications

if(!function_exists('paypal_start') )
{
function paypal_start() {

global $rsvp_options;

//sets up session to display errors or initializes paypal transactions prior to page display
if( isset($_REQUEST["paypal"]) && ( $_REQUEST["paypal"] == 'error' ) )
	{
	session_start();
	return;
	}
elseif( ! isset($_REQUEST['paymentAmount']) )
	return;

session_start();

require_once $rsvp_options["paypal_config"];
require_once WP_CONTENT_DIR.'/plugins/rsvpmaker/paypal/CallerService.php';
$token = $_REQUEST['token'];
if(! isset($token)) {

// remove any session data from previous transactions
if(isset($_SESSION['reshash_checkout']))
	unset($_SESSION['reshash_checkout']);
if(isset($_SESSION['reshash_details']))
	unset($_SESSION['reshash_details']);

// ignore if it fails security test
if(empty($_POST["rsvp-pp-nonce"]) || ! wp_verify_nonce($_POST["rsvp-pp-nonce"],'pp-nonce') )
	return;

		/* The servername and serverport tells PayPal where the buyer
		   should be directed back to after authorizing payment.
		   In this case, its the local webserver that is running this script
		   Using the servername and serverport, the return URL is the first
		   portion of the URL that buyers will return to after authorizing payment
		   */
		   $url = $_POST["permalink"];
		   $url .= ( strpos($url,'?') ) ? '&' : '?';
		   $_SESSION['rsvp_permalink'] = $url;
		if(!empty($_REQUEST['paymentAmount']))
			$paymentAmount=$_REQUEST['paymentAmount'];
		else
			$paymentAmount = $_POST["price"]*$_POST["unit"];
		   $_SESSION["paymentAmount"] = $paymentAmount;//=$_REQUEST['paymentAmount'];
		   $_SESSION["currencyCodeType"] = $currencyCodeType=$rsvp_options["paypal_currency"];
		   $_SESSION["paymentType"] = $paymentType='Sale'; //$_REQUEST['paymentType'];
		   $desc=$_REQUEST['desc'];
			$_SESSION["payer_email"] = $email = $_REQUEST['email'];
			$_SESSION["rsvp_id"] = $_REQUEST['rsvp_id'];

		 /* The returnURL is the location where buyers return when a
			payment has been succesfully authorized.
			The cancelURL is the location buyers are sent to when they hit the
			cancel button during authorization of payment during the PayPal flow
			*/
		   $returnURL =urlencode($url.'currencyCodeType='.$currencyCodeType.'&paymentType='.$paymentType.'&paymentAmount='.$paymentAmount);
		   
		   $cancelURL =urlencode("$url");

		 /* Construct the parameter string that describes the PayPal payment
			the varialbes were set in the web form, and the resulting string
			is stored in $nvpstr
			*/
		  
		   $nvpstr="&Amt=".$paymentAmount."&PAYMENTACTION=".$paymentType."&RETURNURL=".$returnURL."&CANCELURL=".$cancelURL ."&CURRENCYCODE=".$currencyCodeType.'&EMAIL='.$email;
		  
		  	if(!empty($_REQUEST["invoice"]))
				{
				$_SESSION["invoice"] = $_REQUEST["invoice"];
				$nvpstr.="&INVNUM=" . $_REQUEST["invoice"];
				}
		   $nvpstr.= "&SOLUTIONTYPE=Sole&LANDING=Billing&DESC=" . urlencode($desc);
			
		   $resArray=hash_call("SetExpressCheckout",$nvpstr);

		   $_SESSION['reshash']=$resArray;

		   $ack = strtoupper($resArray["ACK"]);

		   if($ack=="SUCCESS"){
					// Redirect to paypal.com here
					$token = urldecode($resArray["TOKEN"]);
					$payPalURL = PAYPAL_URL.$token;
					header("Location: ".$payPalURL);
					exit();
				  } else  {
					 //Redirecting to APIError.php to display errors. 
						$location = $url . "paypal=error&function=firstpass";
						header("Location: $location");
						exit();
					}
} else {
		 /* At this point, the buyer has completed in authorizing payment
			at PayPal.  The script will now call PayPal with the details
			of the authorization, incuding any shipping information of the
			buyer.  Remember, the authorization is not a completed transaction
			at this state - the buyer still needs an additional step to finalize
			the transaction
			*/
			if(!isset($_SESSION['reshash_details']))
			{
		   $token =urlencode( $_REQUEST['token']);

		 /* Build a second API request to PayPal, using the token as the
			ID to get the details on the payment authorization
			*/
		   $nvpstr="&TOKEN=".$token;

		 /* Make the API call and store the results in an array.  If the
			call was a success, show the authorization details, and provide
			an action to complete the payment.  If failed, show the error
			*/
		   $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
		   $_SESSION['reshash_details']=$resArray;
			}
			
		   $ack = strtoupper($_SESSION['reshash_details']["ACK"]);

		   if($ack == "SUCCESS"){
$paymentAmount =urlencode ($_SESSION['paymentAmount']);
$paymentType = urlencode($_SESSION['paymentType']);
$currencyCodeType = urlencode($_SESSION["currencyCodeType"]);
$payerID = urlencode($_REQUEST['PayerID']);
$serverName = urlencode($_SERVER['SERVER_NAME']);

$nvpstr='&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION='.$paymentType.'&AMT='.$paymentAmount.'&CURRENCYCODE='.$currencyCodeType.'&IPADDRESS='.$serverName ;

 /* Make the call to PayPal to finalize payment
    If an error occured, show the resulting errors
    */
//avoid double transactions
if(!isset($_SESSION['reshash_checkout']))
	$_SESSION['reshash_checkout'] = $resArray = hash_call("DoExpressCheckoutPayment",$nvpstr);

/* Display the API response back to the browser.
   If the response from PayPal was a success, display the response parameters'
   If the response was an error, display the errors received using APIError.php.
   */
$ack = strtoupper($_SESSION['reshash_checkout']["ACK"]);
if($ack != "SUCCESS")
 {
// second test fails
	$showerror = true;
  }		   
		   }
		   else
		   	{
				//first test fails
				$showerror = true;
			  }

if($showerror)
		   	{
				//Redirecting to display errors. 
				$location = $_SESSION['rsvp_permalink'] . "paypal=error";
				header("Location: $location");
				exit();
			  }

// otherwise, processing will pick up with the display of the confirmation page  
			  
	}// end second pass

}
} // end paypal start

add_action("init","paypal_start");

if(!function_exists('paypal_payment') )
{
function paypal_payment() {

ob_start();
	global $post;
	global $wpdb;
	
if(isset($_SESSION['reshash_checkout']))
	$resArray = $_SESSION['reshash_checkout'];
elseif(isset($_SESSION['reshash_details']))
	$resArray=$_SESSION['reshash_details'];
else
	$resArray=array('TRANSACTIONID' => 'session data not set', 'CURRENCYCODE' => '', 'AMT' => '','PAYMENTSTATUS' => '');
	
	$rsvp_id = $_SESSION["rsvp_id"];
	
	$paid = $resArray['AMT'];
	// check for previous payments
	
	$message = '<div id="paypal_thank_you">
	<h1>Thank you for your payment!!</h1>
    <table>
        <tr>
            <td>
               '.__('Transaction ID','rsvpmaker').':</td>
            <td>'.$resArray['TRANSACTIONID'].'</td>
        </tr>
        <tr>
            <td>
                '.__('Amount','rsvpmaker').':</td>
            <td>'.$resArray['CURRENCYCODE'].' '.$resArray['AMT'] . '</td>
        </tr>
        <tr>
            <td>
                '.__('Payment Status','rsvpmaker').':</td>
            <td>'.$resArray['PAYMENTSTATUS'] . '</td>
        </tr>
    </table>
	</div>
';
	$invoice_id = get_post_meta($post->ID,'_open_invoice_'.$rsvp_id, true);
	if($invoice_id)
	{
	$charge = get_post_meta($post->ID,'_invoice_'.$rsvp_id, true);
	$paid_amounts = get_post_meta($post->ID,'_paid_'.$rsvp_id);
	if(!empty($paid_amounts))
	foreach($paid_amounts as $payment)
		$paid += $payment;
	$wpdb->query("UPDATE ".$wpdb->prefix."rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id ");
	
	add_post_meta($post->ID,'_paid_'.$rsvp_id,$resArray['AMT']);
	delete_post_meta($post->ID,'_open_invoice_'.$rsvp_id);
	delete_post_meta($post->ID,'_invoice_'.$rsvp_id);
	}

do_action('log_paypal',$message);
return $message;
} } // end paypal payment

if(!function_exists('admin_payment') )
{
function admin_payment($rsvp_id,$charge) {

	global $wpdb;
	global $current_user;
	$event = $_GET['event'];
	$paid = $charge;
	$paid_amounts = get_post_meta($event,'_paid_'.$rsvp_id);
	if(!empty($paid_amounts))
	foreach($paid_amounts as $payment)
		$paid += $payment;
	$wpdb->query("UPDATE ".$wpdb->prefix."rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id ");
	
	add_post_meta($event,'_paid_'.$rsvp_id,$charge);
	delete_post_meta($event,'_open_invoice_'.$rsvp_id);
	delete_post_meta($event,'_invoice_'.$rsvp_id);
	
	$row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=$rsvp_id ",ARRAY_A);
	
	$message = sprintf('<p>%s '.__('payment for','rsvpmaker').' %s %s '.__(' manually recorded by','rsvpmaker').' %s<br />'.__('Post ID','rsvpmaker').': %s<br />'.__('Time','rsvpmaker').': %s</p>',$charge,$row["first"],$row["last"],$current_user->display_name,$event,date('r'));
add_post_meta($event, '_paypal_log', $message);

echo $message;

} } // end admin payment


if(!function_exists('paypal_error'))
{
function paypal_error() {

if(isset($_SESSION['reshash_checkout']))
	$resArray = $_SESSION['reshash_checkout'];
elseif(isset($_SESSION['reshash_details']))
	$resArray=$_SESSION['reshash_details'];
else
	$resArray=array('TRANSACTIONID' => 'session data not set', 'CURRENCYCODE' => '', 'AMT' => '','PAYMENTSTATUS' => '');

ob_start();
?>

<h1><?php _e('PayPal Error','rsvpmaker'); ?></h1>
<p>
<?php

	if(!empty($_SESSION["rsvp_id"]) && ($id = $_SESSION["rsvp_id"]))
	{
	global $wpdb;
	$sql = $wpdb->prepare("select * FROM ".$wpdb->prefix."rsvpmaker where id=%d",$id);
	$row = $wpdb->get_row($sql);
	$paid = (int) $row->amountpaid;
	if($paid)
		{
		_e('Confirmed paid','rsvpmaker');
		?>: <?php echo  $paid ;?><br />
		<?php	
		_e('Note: You may see this error message after a transaction has already gone through (Paypal is trying to avoid charging you twice).','rsvpmaker');
		echo "<br /><br />\n";
		}
	}

  //it will print if any URL errors 
	if(isset($_SESSION['curl_error_no'])) { 
			$errorCode= $_SESSION['curl_error_no'] ;
			$errorMessage=$_SESSION['curl_error_msg'] ;	
			session_unset();	
;?>
   
<?php _e('Error Message','rsvpmaker'); ?>: <?php echo  $errorMessage ;?>
	<br />
	
<?php } else {

/* If there is no URL Errors, Construct the HTML page with 
   Response Error parameters.   
   */
;?>

		<?php _e('Ack Code','rsvpmaker'); ?>: <?php echo  $resArray['ACK'] ;?>
	<br />
	
		<?php _e('Correlation ID','rsvpmaker'); ?>: <?php echo  $resArray['CORRELATIONID'] ;?>
	<br />
	
		<?php _e('Version','rsvpmaker'); ?>: <?php echo  $resArray['VERSION'];?>
	<br />
<?php
	$count=0;
	while (isset($resArray["L_SHORTMESSAGE".$count])) {		
		  $errorCode    = $resArray["L_ERRORCODE".$count];
		  $shortMessage = $resArray["L_SHORTMESSAGE".$count];
		  $longMessage  = $resArray["L_LONGMESSAGE".$count]; 
		  $count=$count+1; 
?>
	
		<?php _e('Error Number','rsvpmaker'); ?>: <?php echo  $errorCode ;?>
	<br />
	
		<?php _e('Short Message','rsvpmaker'); ?>: <?php echo  $shortMessage ;?>
	<br />
	
		<?php _e('Long Message','rsvpmaker'); ?>: <?php echo  $longMessage ;?>
	<br />
	
<?php }//end while
}// end else

$message = ob_get_clean();
do_action('log_paypal',$message);
return $message;
} } // end paypal error

if(!function_exists('rsvpmaker_event_scripts'))
{
function rsvpmaker_event_scripts() {
global $post;
global $rsvp_options;
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-tooltip');
	$myStyleUrl = (isset($rsvp_options["custom_css"]) && $rsvp_options["custom_css"]) ? $rsvp_options["custom_css"] : WP_PLUGIN_URL . '/rsvpmaker/style.css';
	wp_register_style('rsvp_style', $myStyleUrl, array(), '4.4.7');
	wp_enqueue_style( 'rsvp_style');
	wp_localize_script( 'rsvpmaker_ajaxurl', 'ajaxurl', admin_url('admin-ajax.php') );
	wp_enqueue_script('rsvpmaker_js',plugins_url('rsvpmaker/rsvpmaker.js'), array(), 0.8);
} } // end event scripts

add_action('wp_enqueue_scripts','rsvpmaker_event_scripts',10000);

add_action('init','rsvpmaker_localdate');

function rsvpmaker_localdate() {
	if(empty($_REQUEST['action']) || $_REQUEST['action'] != 'rsvpmaker_localstring')
		return;
	$output = '';
	global $rsvp_options;
	if(!empty($_REQUEST['localstring']))
	{
		preg_match('/(.+:00 ).+\(([^)]+)/',$_REQUEST['localstring'],$matches);
		$tf = str_replace('%Z','',$rsvp_options["time_format"]);
		$t = strtotime($matches[1]);
		$output = strftime($rsvp_options["long_date"],$t).' '.strftime($tf,$t).' '.$matches[2];
	}
echo $output;
wp_die();
}

if(!function_exists('basic_form') ) {
function basic_form( $form = '') {
global $rsvp_options;
global $custom_fields;
if(!empty($form))
	echo do_shortcode($form);
if(isset($custom_fields["_rsvp_form"][0]))
	echo do_shortcode($custom_fields["_rsvp_form"][0]);
else
	echo do_shortcode($rsvp_options["rsvp_form"]);
}
}

function embed_dateblock ($atts) {
	$d = rsvp_date_block($atts["post_id"],get_post_custom($atts["post_id"]));
	return $d["dateblock"];
}

add_shortcode('embed_dateblock','embed_dateblock');

function rsvp_date_block($post_id, $custom_fields = array()) {
global $rsvp_options;
global $last_time;
global $post;
$time_format = $rsvp_options["time_format"];
$dur = $tzbutton = '';
$firstrow = array();

if(!strpos($time_format,'%Z') && isset($custom_fields['_add_timezone'][0]) && $custom_fields['_add_timezone'][0] )
	{
	$time_format .= ' %Z';
	}
$permalink = get_permalink($post_id);
$results = get_rsvp_dates($post_id);
if($results)
{
fix_timezone();
$start = 2;
$dateblock = '';
global $last_time;
foreach($results as $row)
	{
	if(empty($firstrow))
		$firstrow = $row;
	$last_time = $t = strtotime($row["datetime"]);
	$dateblock .= '<div itemprop="startDate" datetime="'.date('c',$t).'">';
	$dateblock .= utf8_encode(strftime($rsvp_options["long_date"],$t));
	$dur = $row["duration"];
	if($dur != 'allday')
		{
		$dateblock .= strftime(' '.$time_format,$t);
		}
	// dchange
	if(strpos($dur,':'))
		$dur = strtotime($dur);
	if(is_numeric($dur) )
		$dateblock .= " ".__('to','rsvpmaker')." ".strftime($time_format,$dur);
	$dateblock .= '<span class="timezone_hint" utc="'.gmdate('c',$t). '"  target="timezone_converted'.$post->ID.'">'."\n";
	if(isset($custom_fields['_convert_timezone'][0]) && $custom_fields['_convert_timezone'][0])
	$tzbutton = '<button class="timezone_on">Show in my timezone</button>';
	$dateblock .= '</span><span id="timezone_converted'.$post->ID.'"></span></div>';
	}

//gcal link
if( ( (!empty($rsvp_options["calendar_icons"]) && !isset($custom_fields["_calendar_icons"][0])) || !empty($custom_fields["_calendar_icons"][0]) ) && !is_email_context ())
	{
	$duration = (strpos($firstrow["duration"],'-') ) ? $row["duration"] : $firstrow["datetime"] . ' +1 hour';
	$j = (strpos($permalink,'?')) ? '&' : '?';
	$dateblock .= sprintf('<div class="rsvpcalendar_buttons"><a href="%s" target="_blank" title="%s"><img src="%s" border="0" width="25" height="25" /></a>&nbsp;<a href="%s" title="%s"><img src="%s"  border="0" width="28" height="25" /></a> %s</div>',rsvpmaker_to_gcal($post,$firstrow["datetime"],$duration), __('Add to Google Calendar','rsvpmaker'), plugins_url('rsvpmaker/button_gc.gif'),$permalink.$j.'ical=1', __('Add to Outlook/iCal','rsvpmaker'), plugins_url('rsvpmaker/button_ical.gif'), $tzbutton );
	}
}
elseif(isset($custom_fields["_sked"][0]))
	{
		$sked = unserialize($custom_fields["_sked"][0]);

		//backward compatability
		if(is_array($sked["week"]))
			{
				$weeks = $sked["week"];
				$dows = $sked["dayofweek"];
			}
		else
			{
				$weeks = array();
				$dows = array();
				$weeks[0] = $sked["week"];
				$dows[0] = $sked["dayofweek"];
			}

		$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));
		$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));
		if((int)$weeks[0] == 0)
			$s = __('Schedule Varies','rsvpmaker');
		else
			{
			foreach($weeks as $week)
				{
				if(empty($s))
					$s = '';
				else
					$s .= '/ ';
				$s .= $weekarray[(int) $week].' ';
				}
			foreach($dows as $dow)
				$s .= $dayarray[(int) $dow] . ' ';	
			}
		$t = mktime($sked["hour"],$sked["minutes"]);
		fix_timezone();
		$dateblock = $s.' '.strftime($rsvp_options["time_format"],$t);
	}
	else // no dates, no sked, maybe this is an agenda or a landing page
		return array('dateblock' => '','dur' => NULL, 'last_time' => NULL);	
	
return array('dateblock' => $dateblock,'dur' => $dur, 'last_time' => $last_time, 'firstrow' => $firstrow);
}

if(!function_exists('event_content') )
{
function event_content($content, $formonly = false, $form ='') {
global $wpdb;
global $post;
global $rsvp_options;
global $profile;
global $master_rsvp;
global $showbutton;
global $blanks_allowed;
$rsvpconfirm = '';
$display = array();
$rsvp_id = 0;

//On return from paypal payment process, show confirmation
if(isset($_GET["PayerID"]))
	return paypal_payment();

//Show paypal error for payment gone wrong
if(isset($_GET["paypal"]) && ($_GET["paypal"] == 'error'))
	return paypal_error();

//If the post is not an event, leave it alone
if($post->post_type != 'rsvpmaker' )
	return $content;

if ( post_password_required( $post ) ) {
    return $content;
  }

global $custom_fields; // make this globally accessible
$custom_fields = get_rsvpmaker_custom($post->ID);

$content = apply_filters('rsvpmaker_event_content_top',$content, $custom_fields);

// if requiring passcode, check code (unless RSVP cookie is set)
if(isset($custom_fields['_require_webinar_passcode'][0]) && $custom_fields['_require_webinar_passcode'][0] && !isset($_COOKIE["rsvp_for_".$post->ID]))
{
	$event_id = $custom_fields['_require_webinar_passcode'][0];
	if(!isset($_GET["webinar"]))
		return rsvpmaker_replay_form($custom_fields['_webinar_event_id'][0]);
	$code = $_GET["webinar"];
	$required = $custom_fields['_require_webinar_passcode'][0];
	if($required != trim($code))
		return rsvpmaker_replay_form($custom_fields['_webinar_event_id'][0]);
}

$permalink = site_url('?post_type=rsvpmaker&p='.$post->ID);

if(isset($custom_fields["_rsvp_on"][0]))
$rsvp_on = $custom_fields["_rsvp_on"][0];
if(isset($custom_fields["_rsvp_login_required"][0]))
$login_required = $custom_fields["_rsvp_login_required"][0];
if(isset($custom_fields["_rsvp_to"][0]))
$rsvp_to = $custom_fields["_rsvp_to"][0];
if(isset($custom_fields["_rsvp_max"][0]))
$rsvp_max = $custom_fields["_rsvp_max"][0];
$rsvp_count = (isset($custom_fields["_rsvp_count"][0]) && $custom_fields["_rsvp_count"][0]) ? 1 : 0;
$rsvp_show_attendees = (isset($custom_fields["_rsvp_show_attendees"][0]) && $custom_fields["_rsvp_show_attendees"][0]) ? $custom_fields["_rsvp_show_attendees"][0] : 0;
if(isset($custom_fields["_rsvp_deadline"][0]) && $custom_fields["_rsvp_deadline"][0])
	$deadline = (int) $custom_fields["_rsvp_deadline"][0];
if(isset($custom_fields["_rsvp_start"][0]) && $custom_fields["_rsvp_start"][0])
	$rsvpstart = (int) $custom_fields["_rsvp_start"][0];
$rsvp_instructions = (isset($custom_fields["_rsvp_instructions"][0])) ? $custom_fields["_rsvp_instructions"][0] : NULL;
$rsvp_confirm = (isset($custom_fields["_rsvp_confirm"][0])) ? $custom_fields["_rsvp_confirm"][0] : NULL;
$rsvp_yesno = (isset($custom_fields["_rsvp_yesno"][0])) ? $custom_fields["_rsvp_yesno"][0] : 1;
$replay = (isset($custom_fields["_replay"][0])) ? $custom_fields["_replay"][0] : NULL;

$e = (isset($_GET["e"]) ) ? $_GET["e"] : NULL;
$first = (isset($_GET["first"]) ) ? $_GET["first"] : NULL;
$last = (isset($_GET["last"]) ) ? $_GET["last"] : NULL;
if ( $e && !filter_var($e, FILTER_VALIDATE_EMAIL) )
	$e = '';
//returns null if email ($e) is empty
$rsvprow = NULL;
$profile = rsvpmaker_profile_lookup($e);
if($profile)
	{
	$first = $profile["first"];
	$last = $profile["last"];
	$sql = 'SELECT id FROM '.$wpdb->prefix.'rsvpmaker WHERE email LIKE "'.$e.'" AND event='.$post->ID.' ORDER BY id DESC';
	$rsvp_id = $wpdb->get_var($sql);	
	}

if(isset($_GET["rsvp"]))
	{
	$rsvp_confirm .= "\n\n".get_post_meta($post->ID, '_rsvp_'.$e, true);
	$rsvpconfirm = '<h3>'.__('RSVP Recorded','rsvpmaker').'</h3>	
<p>'.nl2br($rsvp_confirm).'</p>
';
	}
elseif(isset($_COOKIE['rsvp_for_'.$post->ID]))
	{
	$rsvp_id = (int) $_COOKIE['rsvp_for_'.$post->ID];
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post->ID AND id=".$rsvp_id;
	$rsvprow = $wpdb->get_row($sql, ARRAY_A);
	
	if($rsvprow)
	{
	$permalink .= (strpos($permalink,'?')) ? '&' : '?';
	$rsvpconfirm = '
<h4>'.__('Update RSVP?','rsvpmaker').'</h4>	
<p><a href="'.$permalink.'rsvp='.$rsvp_id.'&e='.$rsvprow["email"].'#rsvpnow">'.__('Yes','rsvpmaker').'</a>, '.__('I want to update a previous RSVP for ').$rsvprow["first"].' '.$rsvprow["last"].'</p>
';
	rsvpmaker_debug_log('Yes I want to update RSVP '.$sql.' cookie '.var_export($_COOKIE,true));
	}
	}
	
if(($e && isset($_GET["rsvp"]) ) || is_user_logged_in() )
	{
	if(is_user_logged_in())
		{
		global $current_user;
		$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE ".$wpdb->prepare("event=%d AND email=%s",$post->ID,$current_user->user_email);
		}
	else
		$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE ".$wpdb->prepare("event=%d AND email=%s AND id=%d",$post->ID,$e,$_GET["rsvp"]);
	$rsvprow = $wpdb->get_row($sql, ARRAY_A);
	$e = (isset($rsvprow["email"])) ? $rsvprow["email"] : '';
	if($rsvprow && is_single() ) // don't display in an events listing
		{
		$master_rsvp = $rsvprow["id"];
		$rsvpwithdraw = sprintf('<div><input type="checkbox" checked="checked" name="withdraw[]" value="%d"> %s %s</div>',$rsvprow["id"],$rsvprow["first"],$rsvprow["last"]);
		$answer = ($rsvprow["yesno"]) ? __("Yes",'rsvpmaker') : __("No",'rsvpmaker');		
		$rsvpconfirm .= "<div class=\"rsvpdetails\"><p>".__('Your RSVP','rsvpmaker').": $answer</p>\n";
		$profile = $details = rsvp_row_to_profile($rsvprow);
		if(isset($details["total"]) && $details["total"])
			{
			$nonce= wp_create_nonce('pp-nonce');
			$rsvp_id = (isset($_GET["rsvp"])) ? (int) $_GET["rsvp"]: 0;
			
			$invoice_id = (int) get_post_meta($post->ID,'_open_invoice_'.$rsvp_id,true);
			$paid = 0;
			$paid_amounts = get_post_meta($post->ID,'_paid_'.$rsvp_id);
			if(!empty($paid_amounts))
			foreach($paid_amounts as $payment)
				$paid += $payment;
			$charge = $details["total"] - $paid;
			
			$price_display = ($charge == $details["total"]) ? $details["total"] : $details["total"] . ' - '.$paid.' = '.$charge;
			
			if($invoice_id)
				{
				update_post_meta($post->ID,'_invoice_'.$rsvp_id,$charge);
				}
			else
				{
				$invoice_id = 'rsvp' . add_post_meta($post->ID,'_invoice_'.$rsvp_id,$charge);
				add_post_meta($post->ID,'_open_invoice_'.$rsvp_id,$invoice_id);
				}

			$rsvpconfirm .= "<p><strong>".__('Pay for ','rsvpmaker')." ".$details["payingfor"].' = '.number_format($details["total"],2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).' ' . $rsvp_options["paypal_currency"]."</strong></p>";
			if($charge != $details["total"])
			{
			$rsvpconfirm .= "<p><strong>".__('Previously Paid','rsvpmaker')." ".number_format($paid,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).' ' . $rsvp_options["paypal_currency"]."</strong></p>";
			$rsvpconfirm .= "<p><strong>".__('Balance Owed','rsvpmaker')." ".number_format($charge,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).' ' . $rsvp_options["paypal_currency"]."</strong></p>";
			}
			if($charge > 0)
			{
			if(class_exists('Stripe_Checkout_Functions') && (!empty($rsvp_options["stripe"]) || !empty($custom_fields['_rsvp_stripe'][0]) ))
			$rsvpconfirm .= '<p>'.do_shortcode('[stripe amount="'.($charge*100).'" description="'.htmlentities($post->post_title).' '.$details["payingfor"].'" ]').'</p>';
			elseif(!empty($rsvp_options["cash_or_custom"]) || !empty($custom_fields['_cash_or_custom'][0]) )
			{
				ob_start();
				do_action('rsvpmaker_cash_or_custom',$charge,$invoice_id,$rsvp_id,$details,$profile,$post);
				$rsvpconfirm .= ob_get_clean();
			}
			else
			$rsvpconfirm .= '<form method="post" name="donationform" id="donationform" action="'.$permalink.'">
<input type="hidden" name="paypal" value="payment" /> 
<p><input name="paymentAmount" type="hidden" id="paymentAmount" size="10" value="'.$charge.'"> '.$rsvp_options["paypal_currency"].'
    </p>
  <p>Email: <input name="email" type="text" id="paypal_email" size="40"  value="'.$e.'" >
    </p>
<p><input name="desc" type="hidden" id="desc" value="'.htmlentities($post->post_title).'" ><input name="invoice" type="hidden" id="invoice" value="'.$invoice_id.'" ><input name="permalink" type="hidden" id="permalink" value="'.$permalink.'" ><input name="rsvp_id" type="hidden" id="permalink" value="'.$rsvp_id.'" ><input name="rsvp-pp-nonce" type="hidden" id="rsvp-pp-nonce" value="'.$nonce.'" ><input type="submit" name="Submit" value="'. __('Next','rsvpmaker').' &gt;&gt;"></p>
</form>
<p>'.__('Secure payment processing is provided by <strong>PayPal</strong>. After you click &quot;Next,&quot; we will transfer you to the PayPal website, where you can pay by credit card or with a PayPal account.','rsvpmaker').' </p>';
			}
			
			}
		
		$guestsql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=".$rsvprow["id"];
		if($results = $wpdb->get_results($guestsql, ARRAY_A) )
			{
			$rsvpconfirm .=  "<p>". __('Guests','rsvpmaker').":</p>";
			foreach($results as $row)
				{
				$rsvpconfirm .= $row["first"]." ".$row["last"]."<br />";
				$rsvpwithdraw .= sprintf('<div><input type="checkbox" checked="checked" name="withdraw[]" value="%d"> %s %s</div>',$row["id"],$row["first"],$row["last"]);
				}
			}

		$rsvpconfirm .= "</p></div>\n";
		}
	}
elseif($e && isset($_GET["update"]))
	{
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE ".$wpdb->prepare("event=%d AND email=%s AND id=%d",$post->ID,$e,$_GET["update"]);
	$rsvprow = $wpdb->get_row($sql, ARRAY_A);
	if($rsvprow)
		{
		$master_rsvp = $rsvprow["id"];
		$answer = ($rsvprow["yesno"]) ? __("Yes",'rsvpmaker') : __("No",'rsvpmaker');		
		$profile = $details = rsvp_row_to_profile($rsvprow);
		}
	}

$date_array = rsvp_date_block($post->ID, $custom_fields);
$dateblock = $date_array["dateblock"];
$dur = $date_array["dur"];
$last_time = $date_array["last_time"];
$firstrow = $date_array["firstrow"];

if(!empty($rsvpconfirm))
$rsvpconfirm = '<div id="rsvpconfirm">'.$rsvpconfirm.'</div>'; 

if(!$formonly && !empty($dateblock))
	$content = '<div class="dateblock">'.$dateblock."\n</div>\n".$rsvpconfirm.$content;

if(isset($rsvp_on) && $rsvp_on)
{
//check for responses so far
$sql = "SELECT first,last,note FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post->ID AND yesno=1 ORDER BY id DESC";
$attendees = $wpdb->get_results($sql);
	$total = sizeof($attendees); //(int) $wpdb->get_var($sql);

if(isset($rsvp_max) && $rsvp_max)
	{
	$blanks_allowed = ($total + 1) - $rsvp_max;
	if($total >= $rsvp_max)
		$too_many = true;
	$blanks_allowed = $rsvp_max - ($total);
	if(!isset($answer) )
		$blanks_allowed--;
	}
else
	$blanks_allowed = 1000;

// never show count of 0
if($total && isset($rsvp_max) && $rsvp_max && (isset($rsvp_count) && $rsvp_count))
	{
	$content .= '<p class="signed_up">'.$total.' '.__('signed up so far. Limit: ','rsvpmaker'). "$rsvp_max.</p>\n";
	}
elseif($total && (!isset($rsvp_count) || (isset($rsvp_count) && $rsvp_count)  ))
	$content .= '<p class="signed_up">'.$total.' '. __('signed up so far.','rsvpmaker').'</p>';

$now = current_time('timestamp');
$rsvplink = ($login_required) ? wp_login_url( get_post_permalink( $post->ID ) ) : get_post_permalink( $post->ID );
if(strpos($rsvplink,'?') )
	$rsvp_options["rsvplink"] = str_replace('?','&',$rsvp_options["rsvplink"]);

if(isset($deadline) && ($now  > $deadline  ) )
	$content .= '<p class="rsvp_status">'.__('RSVP deadline is past','rsvpmaker').'</p>';
elseif( ( $now > $last_time  ) )
	{
	if(!empty($custom_fields["_webinar_landing_page_id"][0]))
		{
		$content .= '<p class="rsvp_status">'.'<a href="'.get_permalink($custom_fields["_webinar_landing_page_id"][0]).'">'.__('Watch the replay','rsvpmaker').'</a></p>';
		}
	else
		$content .= '<p class="rsvp_status">'.__('Event date is past','rsvpmaker').'</p>';
	}
elseif(isset($rsvpstart) && ( $now < $rsvpstart  ) )
	$content .= '<p class="rsvp_status">'.__('RSVPs accepted starting: ','rsvpmaker').utf8_encode(strftime($rsvp_options["long_date"],$rsvpstart)).'</p>';
elseif(isset($too_many))
	{
	$content .= '<p class="rsvp_status">'.__('RSVPs are closed','rsvpmaker').'</p>';
	if(isset($rsvpwithdraw) )
		{
		$content .= sprintf('<h3>%s</h3><form method="post" action="%s">%s<p><button>%s</button></p><input type="hidden" name="withdraw_nonce" value="%s" /></form>',__('To cancel, check the attendee names to be removed','rsvpmaker'), $rsvplink, $rsvpwithdraw, __('Cancel RSVP','rsvpmaker'), wp_create_nonce('withdraw_nonce'));
		}
	}
elseif(($rsvp_on && is_admin() && ( $_GET["page"] != 'rsvp' )) || ($rsvp_on && is_email_context ()) || ($rsvp_on && isset($_GET["load"]))) // when loaded into editor
	$content .= sprintf($rsvp_options["rsvplink"],$rsvplink );
elseif($rsvp_on && $login_required && !is_user_logged_in()) // show button, coded to require login
	$content .= sprintf($rsvp_options["rsvplink"],$rsvplink );
elseif($rsvp_on && !is_admin() && (!is_single() || $showbutton ) ) // show button
	$content .= sprintf($rsvp_options["rsvplink"],$rsvplink );
elseif($rsvp_on && (is_single() || is_admin() ) ) // 
	{
	ob_start();
	echo '<div id="rsvpsection">';

;?>

<form id="rsvpform" action="<?php echo $permalink;?>" method="post">

<h3 id="rsvpnow"><?php echo __('RSVP Now!','rsvpmaker');?></h3> 

  <?php if($rsvp_instructions) echo '<p>'.nl2br($rsvp_instructions).'</p>';?>

  <?php if($rsvp_show_attendees) {
	  echo '<p class="rsvp_status">'.__('Names of attendees will be displayed publicly, along with the contents of the notes field.','rsvpmaker').'</p>';
  if($rsvp_show_attendees == 2)
 	echo ' ('.__('only for logged in users','rsvpmaker').')';	
  echo '</p>';
  }
  ?>
   
<?php if ($rsvp_yesno) { echo '<p>'.__('Your Answer','rsvpmaker');?>: <input name="yesno" type="radio" value="1" <?php if(!isset($rsvprow) || $rsvprow["yesno"]) echo 'checked="checked"';?> /> <?php echo __('Yes','rsvpmaker');?> <input name="yesno" type="radio" value="0" <?php if(isset($rsvprow["yesno"]) && ($rsvprow["yesno"] == 0)) echo 'checked="checked"';?> /> <?php echo __('No','rsvpmaker').'</p>'; } else echo '<input name="yesno" type="hidden" value="1" />'; ?> 
<?php

if($dur && ( $slotlength = $custom_fields["_rsvp_timeslots"][0] ))
{
?>
<div><?php echo __('Number of Participants','rsvpmaker');?>: <select name="participants">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
  </select></div>

<div><?php echo __('Choose timeslots','rsvpmaker');?></div>
<?php
fix_timezone();
$t = strtotime($firstrow["datetime"]);
$dur = $firstrow["duration"];
if(strpos($dur,':'))
	$dur = strtotime($dur);
$day = date('j',$t);
$month = date('n',$t);
$year = date('Y',$t);
$hour = date('G',$t);
$minutes = date('i',$t);
$slotlength = explode(":",$slotlength);
$min_add = $slotlength[0]*60;
$min_add = (empty($slotlength[1])) ? $min_add : ($min_add + $slotlength[1]);

for($i=0; ($slot = mktime($hour ,$minutes + ($i * $min_add),0,$month,$day,$year)) < $dur; $i++)
	{
	$sql = "SELECT SUM(participants) FROM ".$wpdb->prefix."rsvp_volunteer_time WHERE time=$slot AND event = $post->ID";
	$signups = ($signups = $wpdb->get_var($sql)) ? $signups : 0;
	echo '<div><input type="checkbox" name="timeslot[]" value="'.$slot.'" /> '.strftime(' '.$rsvp_options["time_format"],$slot)." $signups participants signed up</div>";
	}
}

if(isset($custom_fields["_per"][0]) && $custom_fields["_per"][0])
{
$pf = "";
$options = "";
$per = unserialize($custom_fields["_per"][0]);

	foreach($per["unit"] as $index => $value)
		{
		if(($index == 0) && empty($per["price"][$index]) ) // no price = $0 where no other price is specified
			continue;
		if(empty($per["price"][$index]) && ($per["price"][$index] != 0 ) )
			continue;
		$price = (float) $per["price"][$index];
		
		$deadstring = '';
		if(!empty($per["price_deadline"][$index]))
			{
			$deadline = (int) $per["price_deadline"][$index];
			if(current_time('timestamp') > $deadline)
				continue;
			else
				$deadstring = ' ('.__('until','rsvpmaker').' '.strftime($rsvp_options["short_date"].' '.$rsvp_options["time_format"],$deadline).')';
			}
		
		$display[$index] = $value.' @ '.(($rsvp_options["paypal_currency"] == 'USD') ? '$' : $rsvp_options["paypal_currency"]).' '.number_format($price,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).$deadstring;
		}

if(isset($custom_fields["_rsvp_count_party"][0]) && $custom_fields["_rsvp_count_party"][0])
	{
	$number_prices = sizeof($display);
	if($number_prices)
		{
			if($number_prices == 1)
				{ // don't show options, just one choice
				printf('<h3 id="guest_count_pricing"><input type="hidden" name="guest_count_price" value="%s">%s</h3>',0,$display[0]);
				}
			else
				{
					foreach($display as $index => $value)
						{
						
						$s = (isset($profile["pricechoice"]) && ($index == $profile["pricechoice"])) ? ' selected="selected" ' : '';
						$options .= sprintf('<option value="%d" %s>%s</option>',$index, $s, $value);
						}
					printf('<div id="guest_count_pricing">'.__('Options','rsvpmaker').': <select name="guest_count_price"  id="guest_count_price">%s</select></div>',$options);
				}
		}
	}
else
	{
	if(sizeof($display))
	foreach($display as $index => $value)
		{
		if(empty($per["price"][$index]) && ($per["price"][$index] != 0 ) )
			continue;
		
		$price = (float) $per["price"][$index];
		$unit = $per["unit"][$index];
		$pf .= '<div class="paying_for_tickets"><select name="payingfor['.$index.']" class="tickets"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="unit['.$index.']" value="'.$unit.'" />'.$value.'<input type="hidden" name="price['.$index.']" value="'.$price.'" /></div>'."\n";
		}
	if(!empty($pf))
		echo  "<h3>".__('Paying For','rsvpmaker')."</h3><p>".$pf."</p>\n";
	}
}

basic_form($form);

if(isset($custom_fields["_rsvp_captcha"][0]) && $custom_fields["_rsvp_captcha"][0])
{
?>
<p>          <img src="<?php echo plugins_url('/captcha/captcha_ttf.php',__FILE__);  ?>" alt="CAPTCHA image">
<br />
<?php _e('Type the hidden security message','rsvpmaker'); ?>:<br />                    
<input maxlength="10" size="10" name="captcha" type="text" />
</p>
<?php
do_action('rsvpmaker_after_captcha');
}
rsvpmaker_recaptcha_output();
global $rsvp_required_field;
if(isset($rsvp_required_field) )
	echo '<div id="jqerror"></div><input type="hidden" name="required" value="'.implode(",",$rsvp_required_field).'" />';
?>
        <p> 
          <input type="submit" id="rsvpsubmit" name="Submit" value="<?php  _e('Submit','rsvpmaker');?>" /> 
        </p> 
<input type="hidden" name="rsvp_id" id="rsvp_id" value="<?php if(isset($profile["id"])) echo $profile["id"];?>" /><input type="hidden" name="event" id="event" value="<?php echo $post->ID;?>" /><?php wp_nonce_field('rsvp','rsvp_nonce'); ?>
</form>	
</div>
<?php

	$content .= ob_get_clean();
	}

if(isset($_GET["err"]))
	{
	$error = $_GET["err"];
		$content = '<div id="rsvpconfirm" >
<h3 class="rsvperror">'.__('Error','rsvpmaker').'<br />'.esc_attr($error).'</h3>
<p>'.__('Please correct your submission.','rsvpmaker').'</p>
</div>
'.$content;
	}

if((($rsvp_show_attendees == 1) || (($rsvp_show_attendees == 2) && is_user_logged_in() ) ) && $total && !isset($_GET["load"]) && !isset($_POST["profile"]) )
	{
$content .= '<p><button class="rsvpmaker_show_attendees" onclick="'."jQuery.get('".site_url()."/?ajax_guest_lookup=".$post->ID."', function(data) { jQuery('#attendees-".$post->ID."').html(data); } );". '">'. __('Show Attendees','rsvpmaker') .'</button></p>
<div id="attendees-'.$post->ID.'"></div>';
	}
} // end if($rsvp_on)

$terms = get_the_term_list($post->ID,'rsvpmaker-type','',', ',' ');

if($terms && is_string($terms))
	$content .= '<p class="rsvpmeta">'.__('Event Types','rsvpmaker').': '.$terms.'</p>';

$content = apply_filters('rsvpmaker_event_content_bottom',$content, $custom_fields);
return $content;
} } // end event content

add_shortcode('rsvp_report_shortcode','rsvp_report_shortcode');

function rsvp_report_shortcode ($atts) {
if(!isset($atts["public"]) || ($atts["public"] == '0'))
	{
		if(!is_user_logged_in())
			return sprintf(__('You must <a href="%s">login</a> to view this.','rsvpmaker'),login_redirect($_SERVER['REQUEST_URI']));
	}
global $post;
$permalink = get_permalink($post->ID);
$print_nonce = wp_create_nonce('rsvp_print');
$permalink .= (strpos($permalink,'?')) ? '&rsvp_print='.$print_nonce : '?rsvp_print='.$print_nonce;
ob_start();
rsvp_report();
$report = ob_get_clean();
return str_replace(admin_url('edit.php?post_type=rsvpmaker&page=rsvp'),$permalink,$report);
}

if(!function_exists('rsvp_report') )
{
function rsvp_report() {

global $wpdb;
global $rsvp_options;
$guest_check = '';
$print_nonce = wp_create_nonce('rsvp_print');

$wpdb->show_errors();
?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div>
<h2><?php _e('RSVP Report','rsvpmaker'); ?></h2> 
<?php

if(!empty($_GET["fields"]))
	{
		rsvp_report_table();
		echo "</div>";
		return;
	}

if(isset($_POST["deletenow"]) && current_user_can('edit_others_posts'))
	{
	
	if(empty($_POST["deletenonce"]) || !wp_verify_nonce($_POST["deletenonce"],'rsvpdelete') )
		die("failed security check");
	
	foreach($_POST["deletenow"] as $d)
		$wpdb->query("DELETE FROM ".$wpdb->prefix."rsvpmaker where id=$d");
	}

if(isset($_GET["delete"]) && current_user_can('edit_others_posts'))
	{
	$delete = $_GET["delete"];
	$row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=$delete");

	$guests = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=$delete");
	foreach($guests as $guest)
		$guestcheck .= sprintf('<input type="checkbox" name="deletenow[]" value="%s" checked="checked" /> Delete guest: %s %s<br />',$guest->id,$guest->first,$guest->last);

	echo sprintf('<form action="%s" method="post">
<h2 style="color: red;">'.__('Confirm Delete for','rsvpmaker').' %s %s</h2>
<input type="hidden" name="deletenow[]" value="%s"  />
%s
<input type="hidden" name="deletenonce" value="%s"  />
<input type="submit" style="color: red;" value="'.__('Delete Now','rsvpmaker').'"  />
</form>
',admin_url().'edit.php?post_type=rsvpmaker&page=rsvp',$row->first,$row->last,$delete,$guestcheck,wp_create_nonce('rsvpdelete') );
	}

if(isset($_GET["event"]))
	{
	$eventid = (int) $_GET["event"];
	$date = get_rsvp_date($eventid);
	$post = get_post($eventid);
	$t = strtotime($date);
	$title = $post->post_title ." ".date('F jS',$t);
	
	echo "<h2>".__("RSVPs for",'rsvpmaker')." ".$title."</h2>\n";
	if(!isset($_GET["rsvp_print"]))
		{
		echo '<div style="float: right; margin-left: 15px; margin-bottom: 15px;"><a href="edit.php?post_type=rsvpmaker&page=rsvp">'.__('Show Events List','rsvpmaker').'</a> |
<a href="edit.php?post_type=rsvpmaker&page=rsvp&event='.$eventid.'&rsvp_order=alpha">Alpha Order</a> <a href="edit.php?post_type=rsvpmaker&page=rsvp&event='.$eventid.'&rsvp_order=timestamp">Most Recent First</a> | <a href="edit.php?post_type=rsvpmaker&page=rsvp&event='.$eventid.'&rsvp_order=alpha">Alpha Order</a>
		</div>';
		echo '<p><a href="'.$_SERVER['REQUEST_URI'].'&print_rsvp_report=1&rsvp_print='.$print_nonce.'" target="_blank" >Format for printing</a></p>';	
		echo '<p><a href="edit.php?post_type=rsvpmaker&page=rsvp&event='.$eventid.'&paypal_log=1">Show PayPal Log</a></p>';
		if(isset($phpexcel_enabled))
			echo '<p><a href="#excel">Download to Excel</a></p>';
		}

	if(!empty($_GET["paypal_log"]))
	{
		$log = get_post_meta($eventid,"_paypal_log");
		if($log)
		{
		echo '<div style="border: thin solid red; padding: 5px;"><strong>PayPal</strong><br />';
		echo implode('',$log);
		echo '</div>';
		}
	}

if(!empty($_POST['paymentAmount']))
	{
	$rsvp_id = (int) $_POST["rsvp_id"];
	$paid = (float) $_POST["paymentAmount"];
	admin_payment($rsvp_id,$paid);
	}

if(!empty($_POST["markpaid"]))
	{
		foreach($_POST["markpaid"] as $value)
			{
				$parts = explode(":",$value);
				admin_payment($parts[0],$parts[1]);		
			}
	}

if(isset($_GET["rsvp"]))
{
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE ".$wpdb->prepare("id=%d",$_GET["rsvp"]);
	$rsvprow = $wpdb->get_row($sql, ARRAY_A);
	if($rsvprow)
		{
		$master_rsvp = $rsvprow["id"];
		$answer = ($rsvprow["yesno"]) ? __("Yes",'rsvpmaker') : __("No",'rsvpmaker');		
		$rsvpconfirm .= "<div style=\"border: medium solid #555; padding: 10px;\"><p>".$rsvprow["first"].' '.$rsvprow["last"].": $answer</p>\n";
		$profile = $details = rsvp_row_to_profile($rsvprow);
		if(isset($details["total"]) && $details["total"])
			{
			$nonce= wp_create_nonce('pp-nonce');
			$rsvp_id = (int) $_GET["rsvp"];
			
			$invoice_id = (int) get_post_meta($eventid,'_open_invoice_'.$rsvp_id,true);
			$paid = $rsvprow["amountpaid"];
			$charge = $details["total"] - $paid;
			
			$price_display = ($charge == $details["total"]) ? $details["total"] : $details["total"] . ' - '.$paid.' = '.$charge;
			
			if($invoice_id)
				{
				update_post_meta($eventid,'_invoice_'.$rsvp_id,$charge);
				}
			else
				{
				$invoice_id = 'rsvp' . add_post_meta($eventid,'_invoice_'.$rsvp_id,$charge);
				add_post_meta($eventid,'_open_invoice_'.$rsvp_id,$invoice_id);
				}

			$rsvpconfirm .= "<p><strong>".__('Record Payment','rsvpmaker')." ".$details["payingfor"].' = '.number_format($details["total"],2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).' ' . $rsvp_options["paypal_currency"]."</strong></p>";
			if($charge != $details["total"])
			$rsvpconfirm .= "<p><strong>".__('Previously Paid','rsvpmaker')." ".number_format($paid,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).' ' . $rsvp_options["paypal_currency"]."</strong></p>";
			if($charge > 0)
			{
			$rsvpconfirm .= '<form method="post" name="donationform" id="donationform" action="'.admin_url('edit.php?page=rsvp&post_type=rsvpmaker&event='.$eventid).'">
<p>'. __('Amount','rsvpmaker').': '.$charge.'<input name="paymentAmount" type="hidden" id="paymentAmount" size="10" value="'.$charge.'"> '.$rsvp_options["paypal_currency"].'</p><input name="rsvp_id" type="hidden" id="rsvp_id" value="'.$rsvp_id.'" ><input type="submit" name="Submit" value="'. __('Mark Paid','rsvpmaker').'"></p>
</form>';
			}
			
			}
		$rsvpconfirm .= '</div>';
		echo $rsvpconfirm;
		}
}

if(isset($_GET["edit_rsvp"]) && current_user_can('edit_rsvpmakers'))
	admin_edit_rsvp($_GET["edit_rsvp"],$eventid);
	
	$rsvp_order = (isset($_GET["rsvp_order"]) && ($_GET["rsvp_order"] == 'alpha')) ? ' ORDER BY yesno DESC, last, first' : ' ORDER BY yesno DESC, timestamp DESC';
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$eventid $rsvp_order";
	$wpdb->show_errors();
	$results = $wpdb->get_results($sql, ARRAY_A);

	format_rsvp_details($results);
		
	rsvpmaker_debug_log($sql,'rsvp sql');
	rsvpmaker_debug_log($results,'rsvp results');

	}
elseif(isset($_GET["detail"]))
{
if(!isset($_GET["rsvp_print"]))
	echo '<p><a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvp').'">'.__('Show Events List','rsvpmaker').'</a> | <a href="'.$_SERVER['REQUEST_URI'].'&print_rsvp_report=1&rsvp_print='.$print_nonce.'" target="_blank" >'.__('Format for printing','rsvpmaker').'</a></p>';	

	$limit = (int) $_GET["limit"];
	if($_GET["detail"] == 'future')
		$future = get_future_events('',$limit);
	else
		$future = get_past_events('',$limit);
	$all_emails = array();
	foreach($future as $f)
	{
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=".$f->ID." ORDER BY yesno DESC, timestamp DESC";
	$wpdb->show_errors();
	$rsvps = $wpdb->get_results($sql, ARRAY_A);
	if(!empty($rsvps))
		{
			printf('<h1>%s %s</h1>',$f->post_title,$f->date);
			$emails = format_rsvp_details($rsvps);
			$all_emails = $all_emails + $emails;
		}	
	}
if(!empty($all_emails))
{
$attendees = implode(', ',$all_emails);
$label = __('Email Attendees (all)','rsvpmaker');
printf('<p><a href="mailto:%s">%s: %s</a>',$attendees,$label,$attendees);
}

}
else
{// show events list

$eventlist = "";

$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN ".$wpdb->posts." ON ".$wpdb->postmeta.".post_id = ".$wpdb->posts.".ID and meta_key='_rsvp_dates' ";

if(!isset($_GET["show"]))
	{
	$sql .= " AND meta_value > CURDATE( ) ";
	$eventlist .= '<p>'.__('Showing future events only','rsvpmaker').' (<a href="'.$_SERVER['REQUEST_URI'].'&show=all">show all</a>)<p>';
?>
<form action="edit.php" method="get">
<?php _e('Show details for','rsvpmaker');?>
<input type="hidden" name="page" value="rsvp">
<input type="hidden" name="post_type" value="rsvpmaker">
<select name="limit">
<option value="5">5</option>
<option value="10">10</option>
<option value="25">25</option>
<option value="50">50</option>
<option value="100">100</option>
</select>
<select name="detail">
<option value="past">past</option>
<option value="future">future</option>
</select> events 
<button><?php _e('Show','rsvpmaker');?></button>
</form>
<?php
	}
else
	$eventlist .= '<p>'.__('Showing past events (for which RSVPs were collected) as well as upcoming events.','rsvpmaker').'<p>';

$sql .= " ORDER BY meta_value";


$wpdb->show_errors();
$results = $wpdb->get_results($sql);

	if(isset($rsvp_options["debug"]))
		{
		rsvpmaker_debug_log('rsvp_report sql '.$sql);
		rsvpmaker_debug_log('rsvp_report sql '.var_export($results,true));
		}

if($results)
{

foreach($results as $row)
	{
	if(!get_post_meta($row->postID,'_rsvp_on',true))
		continue;
	if(!isset($events[$row->postID]))
		$events[$row->postID] = $row->post_title;
	$t = strtotime($row->datetime);
	$events[$row->postID] .= " ".date('F jS',$t);
	}
}

if($events)
foreach($events as $postID => $event)
	{
	$eventlist .= "<h3>$event</h3>";
	$sql = "SELECT count(*) FROM ".$wpdb->prefix."rsvpmaker WHERE yesno=1 AND event=".$postID;
	if($rsvpcount = $wpdb->get_var($sql) )
		$eventlist .= '<p><a href="'.admin_url().'edit.php?post_type=rsvpmaker&page=rsvp&event='.$postID.'">'. __('RSVP','rsvpmaker'). ' '.__('Yes','rsvpmaker').': '.$rsvpcount."</a></p>";
	}

if($eventlist && !isset($_GET["rsvp_print"]))
	echo "<h2>".__('Events','rsvpmaker')."</h2>\n".$eventlist;
}

} } // end rsvp report

if(!function_exists('format_rsvp_details') )
{
function format_rsvp_details($results, $editor_options = true) {
	
	global $rsvp_options;
	$print_nonce = wp_create_nonce('rsvp_print');
	$missing = $owed_list = '';
	$members = $nonmembers = 0;
	if($results)
	$fields = array('yesno','first','last','email','guestof','amountpaid');
	foreach($results as $index => $row)
		{
		$row["yesno"] = ($row["yesno"]) ? "YES" : "NO";
		if($row["yesno"])
			$emails[$row["email"]] = $row["email"];

		if(get_user_by('email',$row["email"]))
			$members++;
		else
			$nonmembers++;
		echo '<h3>'.$row["yesno"]." ".esc_attr($row["first"])." ".esc_attr($row["last"])." ".$row["email"];
		if($row["guestof"])
			echo " (". __('guest of','rsvpmaker')." ".esc_attr($row["guestof"]).")";
		echo "</h3>";
		
		if($row["master_rsvp"])
			{
			if(isset($guestcount[$row["master_rsvp"]]))
				$guestcount[$row["master_rsvp"]]++;
			else
				$guestcount[$row["master_rsvp"]] = 1;
			}
		else
			$master_row[$row["id"]] = $row["first"].' '.$row["last"];
		
		if($row["details"])
			$details = unserialize($row["details"]);

		if(isset($details["total"]))
			echo '<div style="font-weight: bold;">'.__('Total','rsvpmaker').': '.$details["total"]."</div>";		
		if($row["amountpaid"] > 0)		
			echo '<div style="color: #006400;font-weight: bold;">'.__('Paid','rsvpmaker').': '.$row["amountpaid"]."</div>";
		if(isset($details["total"]))
			{
			$owed = $details["total"] - $row["amountpaid"];
			if($owed)
				{
				echo '<div style="color: red;font-weight: bold;">'.__('Owed','rsvpmaker').': '.$owed."</div>";
				if($owed > 0)
				$owed_list .= sprintf('<p><input type="checkbox" name="markpaid[]" value="%s:%s">%s %s %s %s</p>',$row["id"],$owed,$row["first"],$row["last"],$owed,__('Owed','rsvpmaker'));
				}
			}

		echo "<p>";
		if($row["details"])
			{
			$details = unserialize($row["details"]);
			foreach($details as $name => $value)
				if($value) {
					echo $name.': '.esc_attr($value)."<br />";
					if(!in_array($name,$fields) )
						$fields[] = $name;
					}
			}
		if($row["note"])
			echo "note: " . nl2br(esc_attr($row["note"]))."<br />";
		$t = strtotime($row["timestamp"]);
		echo 'posted: '.strftime($rsvp_options["short_date"],$t);
		echo "</p>";
		
		if(!isset($_GET["rsvp_print"]) && current_user_can('edit_others_posts') && $editor_options)
			echo sprintf('<p><a href="%s&delete=%d">Delete record for: %s %s</a></p>',admin_url().'edit.php?post_type=rsvpmaker&page=rsvp',$row["id"],esc_attr($row["first"]),esc_attr($row["last"]) );
		$userrsvps[] = $row["user_id"];
		}

	if(!empty($rsvp_options["missing_members"]))
		{
		$blogusers = get_users('blog_id=1&orderby=nicename');
			foreach ($blogusers as $user) {
				if(in_array($user->ID,$userrsvps) )
					continue;		
			$userdata = get_userdata($user->ID);
			$missing .= "<p>$userdata->display_name $userdata->user_email</p>\n";
			}
		}
	if(!empty($missing))
		{
			echo "<hr /><h3>".__('Members Who Have Not Responded','rsvpmaker')."</h3>".$missing;
		}

	$emails = apply_filters('rsvp_yes_emails',$emails);
	if(isset($emails) && is_array($emails))
		{
			$emails = array_filter($emails); // removes empty elements
			$attendees = implode(', ',$emails);
			$label = __('Email Attendees','rsvpmaker');
			printf('<p><a href="mailto:%s">%s: %s</a>',$attendees,$label,$attendees);
		}

	if($members && $nonmembers)
		printf('<p>Responses from %d members with user accounts and %d nonmembers.</p>',$members, $nonmembers);
	
global $phpexcel_enabled; // set if excel extension is active
if($fields && !isset($_GET["rsvp_print"]) && !isset($_GET["limit"]))
	{
	$fields[]='note';
	$fields[]='timestamp';	 
;?>
<div id="excel" name="excel" style="padding: 10px; border: thin dotted #333; width: 300px;margin-top: 30px;">
<h3><?php _e('Data Table / Spreadsheet','rsvpmaker'); ?></h3>
<form method="get" action="edit.php" target="_blank">
<?php
foreach($_GET as $name => $value)
	echo sprintf('<input type="hidden" name="%s" value="%s" />',$name,$value);

foreach($fields as $field)
	echo '<input type="checkbox" name="fields[]" value="'.$field.'" checked="checked" /> '.$field . "<br />\n";

printf('<input type="hidden" name="rsvp_print" value="%s" />',$print_nonce);

?>
<p><button name="print_rsvp_report" value="1" ><?php _e('Print Report','rsvpmaker');?></button> <button name="rsvp_csv" value="1" ><?php _e('Download CSV','rsvpmaker');?></button></p>
<?php
if(isset($phpexcel_enabled))
{
$rsvpexcel = wp_create_nonce('rsvpexcel');
printf('<p><button name="rsvpexcel" value="%s" />%s</button></p>',$rsvpexcel,__('Download to Excel','rsvpmaker'));
}
else
	{
	echo "<br />";
	_e("Additional RSVPMaker Excel plugin required for download to Excel function.",'rsvpmaker');
	echo '<a href="https://wordpress.org/plugins/rsvpmaker-excel/">https://wordpress.org/plugins/rsvpmaker-excel/</a>';
	}
?>
</form>
</div>
<?php

	}
$options = $name = '';
if(is_admin() && !isset($_GET["rsvp_print"]))
{
$options .= sprintf('<option value="%d">%s</option>',0,__('Add New','rsvpmaker') );
if(!empty($master_row) )
foreach($master_row as $id => $name)
	{
		if(isset($guestcount[$id]))
			$name .= sprintf(' + %d guests',$guestcount[$id]);
		$options .= sprintf('<option value="%d">%s</option>',$id,$name);
	}
?>
<h3><?php _e('Edit Entries','rsvpmaker');?></h3>
<form action="edit.php" method="get">
<select name="edit_rsvp"><?php echo $options; ?></select>
<input type="hidden" name="page" value="rsvp">
<input type="hidden" name="post_type" value="rsvpmaker">
<input type="hidden" name="event" value="<?php echo $_GET["event"]; ?>">
<button><?php _e('Edit','rsvpmaker');?></button>
</form>
<?php

if(!empty($owed_list) )
{
printf('<h3>Record Payments</h3><form action="%s" method="post">',admin_url('edit.php?page=rsvp&post_type=rsvpmaker&event='.$_GET["event"]));
echo $owed_list;
?>
<button><?php _e('Mark Paid','rsvpmaker');?></button>
</form>
<?php
} // end is admin

}

return $emails;
} } // end format_rsvp_details

function admin_edit_rsvp($id,$event) {
global $wpdb;
global $profile;
global $master_rsvp;
global $post;
if($id == 0)
	$profile = array('yesno' => 1);
else
	{
	$row = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=".$id, ARRAY_A);
	$profile = rsvp_row_to_profile($row);
	}
$master_rsvp = $id;
$custom_fields = get_rsvpmaker_custom($event);

global $rsvp_options;
$form = $custom_fields['_rsvp_form'][0];
printf('<form action="%s" method="post">',admin_url('edit.php?page=rsvp&post_type=rsvpmaker&event='.$event));

echo '<p>'; ?><input name="yesno" type="radio" value="1" <?php echo ($profile["yesno"]) ? 'checked="checked"' : '';?> /> <?php echo __('Yes','rsvpmaker');?> <input name="yesno" type="radio" value="0" <?php echo (!$profile["yesno"]) ? 'checked="checked"' : '';?> /> <?php echo __('No','rsvpmaker').'</p>';

$results = get_rsvp_dates($event);
if($results)
{
fix_timezone();
$start = 2;
$firstrow = NULL;
$dateblock = '';
global $last_time;
foreach($results as $row)
	{
	if(!$firstrow)
		$firstrow = $row;
	$last_time = $t = strtotime($row["datetime"]);
	$dateblock .= '<div itemprop="startDate" datetime="'.date('c',$t).'">';
	$dateblock .= utf8_encode(strftime($rsvp_options["long_date"],$t));
	$dur = $row["duration"];
	if($dur != 'allday')
		$dateblock .= strftime(' '.$rsvp_options["time_format"],$t);
	// dchange
	if(strpos($dur,':'))
		$dur = strtotime($dur);
	if(is_numeric($dur) )
		$dateblock .= " ".__('to','rsvpmaker')." ".strftime($rsvp_options["time_format"],$dur);
	$dateblock .= "</div>\n";
	}

}

echo '<div class="dateblock">'.$dateblock."\n</div>\n";

if($dur && ( $slotlength = $custom_fields["_rsvp_timeslots"][0] ))
{
?>
<div><?php echo __('Number of Participants','rsvpmaker');?>: <select name="participants">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
  </select></div>

<div><?php echo __('Choose timeslots','rsvpmaker');?></div>
<?php
fix_timezone();
$t = strtotime($firstrow["datetime"]);
$dur = $firstrow["duration"];
if(strpos($dur,':'))
	$dur = strtotime($dur);
$day = date('j',$t);
$month = date('n',$t);
$year = date('Y',$t);
$hour = date('G',$t);
$minutes = date('i',$t);
$slotlength = explode(":",$slotlength);
$min_add = $slotlength[0]*60;
$min_add = $min_add + $slotlength[1];

for($i=0; ($slot = mktime($hour ,$minutes + ($i * $min_add),0,$month,$day,$year)) < $dur; $i++)
	{
	$sql = "SELECT SUM(participants) FROM ".$wpdb->prefix."rsvp_volunteer_time WHERE time=$slot AND event = $post->ID";
	$signups = ($signups = $wpdb->get_var($sql)) ? $signups : 0;
	echo '<div><input type="checkbox" name="timeslot[]" value="'.$slot.'" /> '.strftime(' '.$rsvp_options["time_format"],$slot)." $signups participants signed up</div>";
	}
}

if(isset($custom_fields["_per"][0]) && $custom_fields["_per"][0])
{
$pf = "";
$options = "";
$per = unserialize($custom_fields["_per"][0]);

if(isset($custom_fields["_rsvp_count_party"][0]) && $custom_fields["_rsvp_count_party"][0])
	{
	foreach($per["unit"] as $index => $value)
		{
		$price = (float) $per["price"][$index];
		if(!$price)
			break;
		$display[] = $value.' @ '.(($rsvp_options["paypal_currency"] == 'USD') ? '$' : $rsvp_options["paypal_currency"]).' '.number_format($price,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]);
		}
	$number_prices = sizeof($display);
	if($number_prices)
		{
			if($number_prices == 1)
				{ // don't show options, just one choice
				printf('<h3 id="guest_count_pricing"><input type="hidden" name="guest_count_price" value="%s">%s '.__('per person','rsvpmaker').'</h3>',0,$display[0]);
				}
			else
				{
					foreach($display as $index => $value)
						{
						$s = ($index == $profile["pricechoice"]) ? ' selected="selected" ' : '';
						$options .= sprintf('<option value="%d" %s>%s</option>',$index, $s, $value);
						}
					printf('<div id="guest_count_pricing">'.__('Options','rsvpmaker').': <select name="guest_count_price">%s</select></div>',$options);
				}
		}
	}
else
	{
	foreach($per["unit"] as $index => $value)
		{
		$price = (float) $per["price"][$index];
		if(!$price)
			break;
		$pf .= '<div><select name="payingfor['.$index.']" class="tickets"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="unit['.$index.']" value="'.$value.'" />'.$value.' @ <input type="hidden" name="price['.$index.']" value="'.$price.'" />'.(($rsvp_options["paypal_currency"] == 'USD') ? '$' : $rsvp_options["paypal_currency"]).' '.number_format($price,2,$rsvp_options["currency_decimal"],$rsvp_options["currency_thousands"]).'</div>'."\n";
		}
	if(!empty($pf))
		echo  "<h3>".__('Paying For','rsvpmaker')."</h3><p>".$pf."</p>\n";
	}
}

echo do_shortcode($form);
printf('<input type="hidden" name="rsvp_id" id="rsvp_id" value="%s" /><input type="hidden" id="event" name="event" value="%s" /><input type="hidden" name="rsvp_nonce" value="%s" /><p><button>Submit</button></p></form>',$id,$event,wp_create_nonce('rsvp'));
echo '<p>'.__('Tip: If you do not have an email address for someone you registered offline, you can use the format firstnamelastname@example.com (example.com is an Internet domain reserved for examples and testing). You will get an error message if you try to leave it blank').'</p>';

echo rsvp_form_jquery();

}

if(!function_exists('rsvp_print') ) {
function rsvp_print() {
	

if(isset($_GET["rsvp_print"]) && isset($_GET["page"])  && is_admin() )
{
//if(!wp_verify_nonce($_GET["rsvp_print"],'rsvp_print') )
	//die("Security error");

$slug = $_GET["page"];
$hookname = get_plugin_page_hookname( $slug, '' );
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.get_admin_page_title().'</title>
</head>

<body>
';

do_action($hookname);

echo "</body></html>";
exit();
}
}// end function
}// if exists

if(!function_exists('rsvp_csv') ) {
function rsvp_csv() {

if(!isset($_GET["rsvp_csv"]) )
	return;

if(empty($_GET["rsvp_print"]) || !wp_verify_nonce($_GET["rsvp_print"],'rsvp_print') ) // use the same nonce as print
	die("Security error");

global $wpdb;
$fields = $_GET["fields"];
$eventid = (int) $_GET["event"];
$post = get_post($eventid);

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="'.$post->post_name.'-'.date('Y-m-d-H-i').'.csv"');
header('Cache-Control: max-age=0');
$out = fopen('php://output', 'w');
	fputcsv($out, $fields);

	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, last, first";
	$results = $wpdb->get_results($sql, ARRAY_A);
	$rows = sizeof($results);
	//$maxcol = col2chr(sizeof($fields));
	$phonecells = $phonecol.'1:'.$phonecol.($rows+1);
	
	foreach($results as $row)
		{
		$index++;
		$row["yesno"] = ($row["yesno"]) ? "YES" : "NO";
		if($row["details"])
			{
			$details = unserialize($row["details"]);
			$row = array_merge($row,$details);
			}
		$newrow = array();
		foreach($fields as $column => $name )
			{
				if(isset($row[$name]) )
					$newrow[] = $row[$name];
				else
					$newrow[] = '';
			}
		fputcsv($out, $newrow);
		}
fclose($out);

exit();
}
} // end rsvp_csv

add_action('admin_init','rsvp_csv');

function rsvp_report_table () {
?>
<style>
table#rsvptable {
    border-collapse: collapse;
}
table#rsvptable td, table#rsvptable td {
border: thin solid #555;
padding: 3px;
text-align: left;
}
</style>
<?php

global $wpdb;
$fields = $_GET["fields"];
$eventid = (int) $_GET["event"];
	
	$sql = "SELECT post_title FROM ".$wpdb->posts." WHERE ID = $eventid";
	$title = $wpdb->get_var($sql);

echo "<h2>$title</h2>\n<table id=\"rsvptable\"><tr>\n";
// Create new PHPExcel object

foreach($fields as $column => $name )
{
echo "<th>$name</th>";
}
echo "</tr>";

	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, last, first";
	$results = $wpdb->get_results($sql, ARRAY_A);
	$rows = sizeof($results);
	//$maxcol = col2chr(sizeof($fields));
	$phonecells = $phonecol.'1:'.$phonecol.($rows+1);
		
	foreach($results as $row)
		{
		$index++;
		$row["yesno"] = ($row["yesno"]) ? "YES" : "NO";
		if($row["details"])
			{
			$details = unserialize($row["details"]);
			$row = array_merge($row,$details);
			}
		echo "<tr>";
		foreach($fields as $column => $name )
			{
				if(isset($row[$name]) )
					printf('<td>%s</td>',$row[$name]);
				else
					echo "<td></td>";
			}
			 //$worksheet->write($index, $column, $row[$name], $format_wrap);
		echo "</tr>";
		}
		echo "</table>";
}

add_action('current_screen','rsvp_print',999);

if(!function_exists('get_spreadsheet_data') )
{
function get_spreadsheet_data($eventid) {
global $wpdb;

	$sql = "SELECT yesno,first,last,email, details, note, guestof FROM ".$wpdb->prefix."rsvpmaker WHERE event=$eventid ORDER BY yesno DESC, last, first";
	$results = $wpdb->get_results($sql, ARRAY_A);
	
	foreach($results as $index => $row)
		{
		$srow["answer"] = ($row["yesno"]) ? "YES" : "NO";
		$srow["name"] = $row["first"]." ".$row["last"];
		
		$details = unserialize($srow["details"]);
		
		$srow["address"] = $details["address"]." ".$details["city"]." ".$details["state"]." ".$details["zip"];
		$srow["employment"] = $details["occupation"]." ".$details["company"];
		$srow["email"] = $row["email"];
		$srow["guestof"] = $row["guestof"];
		$srow["note"] = $row["note"];
		$spreadsheet[] = $srow;
		}
return $spreadsheet;
} } // end get spreadsheet data

if(!function_exists('widgetlink') ) {
function widgetlink($evdates,$plink,$evtitle) {
	return sprintf('<a href="%s">%s</a> %s',$plink,$evtitle,$evdates);
} } // end widgetlink

if(!function_exists('rsvpmaker_profile_lookup') ) {
function rsvpmaker_profile_lookup($email = '') {
global $wpdb;
$profile = array();
if(isset($_GET["blank"]))
	return NULL;

if(!empty($email))
{
$sql = 'SELECT details FROM '.$wpdb->prefix.'rsvpmaker WHERE email LIKE "'.$email.'" ORDER BY id DESC';
$details = $wpdb->get_var($sql);
if(!empty($details))
{
	$details = unserialize($details);
	$profile["email"] = $details["email"];
	$profile["first"] = $details["first"];
	$profile["last"] = $details["last"];
	foreach($details as $name => $value)
	{
		if(strpos($name,'phone') !== false)
			$profile[$name] = $value;
	}
}	
}
else
	{
	// if members are registered and logged in, retrieve basic info for profile
	if(is_user_logged_in() )
		{
		global $current_user;
		$profile["email"] = $current_user->user_email;
		$profile["first"] = $current_user->first_name;
		$profile["last"] = $current_user->last_name;
		}
	}
return $profile;
} }

if(!function_exists('ajax_guest_lookup') )
{
function ajax_guest_lookup() {
if(!isset($_GET["ajax_guest_lookup"]))
	return;
$event = $_GET["ajax_guest_lookup"];
global $wpdb;

$sql = "SELECT first,last,note FROM ".$wpdb->prefix."rsvpmaker WHERE event=$event AND yesno=1 ORDER BY id DESC";
$attendees = $wpdb->get_results($sql);
echo '<div class="attendee_list">';
foreach($attendees as $row)
	{
;?>
<h3 class="attendee"><?php echo $row->first;?> <?php echo $row->last;?></h3>
<?php	
if($row->note);
echo wpautop($row->note);
	}
echo '</div>';
exit();
} }

add_action('init','ajax_guest_lookup');

add_action('rsvp_daily_reminder_event', 'rsvp_daily_reminder');

function rsvp_reminder_activation() {
	if(isset($_GET['autorenew']))
		rsvpautorenew_test();
	
	if ( !wp_next_scheduled( 'rsvp_daily_reminder_event' ) ) {
		$hour = 12 - get_option('gmt_offset');
		$t = mktime($hour,0,0);
		wp_schedule_event(current_time('timestamp'), 'daily', 'rsvp_daily_reminder_event');
	}
}

function rsvp_reminder_reset($basehour) {
	wp_clear_scheduled_hook('rsvp_daily_reminder_event'); //
	$hour = $basehour - get_option('gmt_offset');
	$t = mktime($hour,0,0);
	wp_schedule_event($t, 'daily', 'rsvp_daily_reminder_event');
}

add_action('wp', 'rsvp_reminder_activation');

if(!function_exists('rsvp_daily_reminder') )
{
function rsvp_daily_reminder() {
rsvpautorenew_test(); //also check for templates that autorenew
	
global $wpdb;
global $rsvp_options;

$today = date('Y-m-d');
$sql = "SELECT * FROM `$wpdb->postmeta` WHERE `meta_key` LIKE '_rsvp_reminder' AND `meta_value`='$today'";
if( $reminders = $wpdb->get_results($sql) )
	{
	foreach($reminders as $reminder)
		{
		$postID = $reminder->post_id;
		$q = "p=$postID&post_type=rsvpmaker";
		echo "Post $postID is scheduled for a reminder $q<br />";
		global $post;
		query_posts($q);
		global $wp_query;
		// treat as single, display rsvp button, not form
		$wp_query->is_single = false;
		the_post();

		if($post->post_title)
			{
			$event_title = $post->post_title;
			ob_start();
			echo "<h1>";
			the_title();
			echo "</h1>\n<div>\n";	
			the_content();
			echo "\n</div>\n";
			$event = ob_get_clean();
			
			$rsvpto = get_post_meta($postID,'_rsvp_to',true);
			
			$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$postID AND yesno=1";
			$rsvps = $wpdb->get_results($sql,ARRAY_A);
			if($rsvps)
			foreach($rsvps as $row)
				{
				$notify = $row["email"];

				$row["yesno"] = ($row["yesno"]) ? "YES" : "NO";
				
				$notification = "<p>".__("This is an automated reminder that we have you on the RSVP list for the event shown below. If your plans have changed, you can update your response by clicking on the RSVP button again.",'rsvpmaker')."</p>";
				$notification .= '<h3>'.$row["yesno"]." ".$row["first"]." ".$row["last"]." ".$row["email"];
				if($row["guestof"])
					$notification .=  " (". __('guest of','rsvpmaker')." ".$row["guestof"].")";
				$notification .=  "</h3>\n";
				$notification .=   "<p>";
				if($row["details"])
					{
					$details = unserialize($row["details"]);
					foreach($details as $name => $value)
						if($value) {
							$notification .=  "$name: $value<br />";
							}
					}
				if($row["note"])
					$notification .= "note: " . nl2br($row["note"])."<br />";
				$t = strtotime($row["timestamp"]);
				$notification .= 'posted: '.strftime($rsvp_options["short_date"],$t);
				$notification .=  "</p>";
				$notification .=  "<h3>Event Details</h3>\n".str_replace('*|EMAIL|*',$notify,$event);
				
				echo "Notification for $notify<br />$notification";
				$subject = '=?UTF-8?B?'.base64_encode( __("Event Reminder for",'rsvpmaker').' '.$event_title ).'?=';
				if(isset($rsvp_options["smtp"]) && !empty($rsvp_options["smtp"]) )
					{
					$mail["subject"] = __("Event Reminder for",'rsvpmaker').' '.$event_title;
					$mail["html"] = $notification;
					$mail["to"] = $notify;
					$mail["from"] = $rsvp_to;
					$mail["fromname"] = get_bloginfo('name');
					rsvpmailer($mail);
					}
				else
					{
					$subject = '=?UTF-8?B?'.base64_encode( __("Event Reminder for",'rsvpmaker').' '.$event_title ).'?=';
					mail($notify,$subject,$notification,"From: $rsvpto\nContent-Type: text/html; charset=UTF-8");
					}

				}
			}
		}
	}
	else
		echo "none found";
}
}// end

if(!function_exists('rsvpguests') )
{
function rsvpguests($atts) {
if(is_admin())
	return;
global $guestextra;
global $wpdb;
global $blanks_allowed;
global $master_rsvp;
$wpdb->show_errors();
$output = '';
$count = 1; // reserve 0 for host
$max_party = (isset($atts["max_party"])) ? (int) $atts["max_party"] : 0;

if(isset($master_rsvp) && $master_rsvp)
{
$guestsql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=".$master_rsvp;
if($results = $wpdb->get_results($guestsql, ARRAY_A) )
	{
	foreach($results as $row)
		{
			$guestprofile = rsvp_row_to_profile($row);
			$output .= sprintf('<div class="guest_blank"><p><strong>'.__('Guest','rsvpmaker').' %d</strong></p>',$count)."\n";
			$output .= guestfield(array('textfield' => 'first'), $guestprofile, $count);
			$output .= guestfield(array('textfield' => 'last'), $guestprofile, $count);
			if(is_array($guestextra))
			foreach ($guestextra as $atts)
				$output .= guestfield($atts, $guestprofile, $count);
			$output .= sprintf('<div><input type="checkbox" name="guestdelete[%s]" value="%s" /> '.__('Delete Guest','rsvpmaker').' %d</div><input type="hidden" name="guest[id][%s]" value="%s">',$row["id"],$row["id"], $count,$count,$row["id"]);
			$output .= '</div>'."\n";
			$count++;
		}
	}
}

$max_guests = $blanks_allowed + $count;

if($max_party)
	$max_guests = ($max_party > $max_guests) ? $max_guests : $max_party; // use the lower limit

// now the blank field
if($blanks_allowed < 1)
	return $output.'<p><em>'.__('No room for additional guests','rsvpmaker').'</em><p>'; // if event is full, no additional guests
elseif($count > $max_guests)
	return $output.'<p><em>'.__('No room for additional guests','rsvpmaker').'</em><p>'; // limit by # of guests per person
elseif($max_guests && ($count >= $max_guests))
	return $output.'<p><em>'.__('No room for additional guests (max per party)','rsvpmaker').'</em><p>'; // limit by # of guests per person

			$output .= '<input type="hidden" id="max_guests" value="'.$max_guests.'" />';
			$output .= '<div class="guest_blank" id="first_blank"><p><strong>Guest ###</strong></p>'."\n";
			$output .= guestfield(array('textfield' => 'first'), array(), '');
			$output .= guestfield(array('textfield' => 'last'), array(), '');
			if(is_array($guestextra))
			foreach ($guestextra as $atts)
				$output .= guestfield($atts, array(), '');
			$output .= '</div>'."\n";

$output = '<div id="guest_section" tabindex="-1">'."\n".$output.'</div>'."<!-- end of guest section-->";
if($max_guests > ($count + 1))
	$output .= "<p><a href=\"#guest_section\" id=\"add_guests\" name=\"add_guests\">(+) ". __('Add more guests','rsvpmaker')."</a><!-- end of guest section--></p>\n";

$output .= '<script type="text/javascript"> var guestcount ='.$count.'; </script>';

return $output;
}
}

add_shortcode('rsvpguests','rsvpguests');

if(!function_exists('rsvpprofiletable') )
{
function rsvpprofiletable( $atts, $content = null ) {
global $profile;
if(!isset($atts["show_if_empty"]) || !(isset($profile[$atts["show_if_empty"]]) && $profile[$atts["show_if_empty"]]) )
	return do_shortcode($content);
else
	{
	$p = get_post_permalink();
	$p .= (strpos($p,'?')) ? '&blank=1' : '?blank=1';
return '
<p id="profiledetails">'. __('Profile details on file. To update profile, or RSVP for someone else','rsvpmaker').' <a href="'.$p.'">'. __('fetch a blank form','rsvpmaker').'</a></p>
<input type="hidden" name="onfile" value="1" />';
	}

}
}
add_shortcode('rsvpprofiletable','rsvpprofiletable');

if(!function_exists('rsvpfield') )
{
function rsvpfield($atts) {
global $profile;
global $rsvp_required_field;
global $guestextra;
global $current_user;

//synonyms
if( isset($atts["text"]) && !isset($atts["textfield"])  ) $atts["textfield"] = $atts["text"];
if( isset($atts["select"]) && !isset($atts["selectfield"])  ) $atts["selectfield"] = $atts["select"];

if(is_admin() && !isset($_REQUEST["edit_rsvp"]))
	{
	$output = '';
	$guestfield = (isset($atts["guestfield"])) ? (int) $atts["guestfield"] : 0;
	$guestoptions = array(__('main form','rsvpmaker'),__('main+guest','rsvpmaker'),__('guest form only','rsvpmaker'));
	$goptions = '';
	foreach($guestoptions as $index => $option)
		{
			$s = ($index == $guestfield) ? ' selected="selected" ' : '';
			$goptions .= '<option value="'.$index.'" '.$s.'>'.$option.'</option>';
		}	
	$private = (isset($atts["private"]) && $atts["private"]) ? ' checked="checked" ' : '';
	if(isset($atts["textfield"])) {
		$field = $atts["textfield"];
		if(($field == 'email') || ($field == 'first') || ($field == 'last'))
			return;
		if(strpos($field,'hone') && empty($atts["private"]))
			$private = ' checked="checked" ';
		$label = ucfirst(str_replace('_',' ',$field));
		global $extrafield;
		$extrafield++;
		$output = '<select name="type'.$extrafield.'" id="type'.$extrafield.'"><option value="text" selected="selected">text</option><option value="hidden">hidden</option><option value="radio">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select> '.__('Show','rsvpmaker').': <select id="guest'.$extrafield.'" name="guest'.$extrafield.'">'.$goptions.'</select>
<input type="checkbox" id="private'.$extrafield.'" name="private'.$extrafield.'" value="1" '.$private.' /> '.__('private','rsvpmaker').'
<br /><input type="text" name="extra'.$extrafield.'" id="extra'.$extrafield.'" value="'.$label.'"  class="text ui-widget-content ui-corner-all" />';
		}

	if(isset($atts["hidden"])) {
		$field = $atts["hidden"];
		if(($field == 'email') || ($field == 'email') || ($field == 'email'))
			return;
		$label = ucfirst(str_replace('_',' ',$field));
		global $extrafield;
		$extrafield++;
		$output = '<select id="type'.$extrafield.'"><option value="text">text</option><option value="hidden" selected="selected">hidden</option><option value="radio">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select><input type="hidden" id="guest'.$extrafield.'" />
<input type="hidden" id="private'.$extrafield.'" name="private'.$extrafield.'" /> 
<br /><input type="text" id="extra'.$extrafield.'" value="'.$label.'"  class="text ui-widget-content ui-corner-all" />';
		}

	if(isset($atts["radio"])) {
		$field = $atts["radio"];
		if(($field == 'email') || ($field == 'email') || ($field == 'email'))
			return;
		$label = ucfirst(str_replace('_',' ',$field));
		global $extrafield;
		$extrafield++;
		$output = '<select id="type'.$extrafield.'"><option value="text">text</option><option value="hidden">hidden</option><option value="radio"  selected="selected">radio</option><option value="select">select</option><option value="checkbox">checkbox</option></select> '.__('Show','rsvpmaker').': <select id="guest'.$extrafield.'" name="guest'.$extrafield.'">'.$goptions.'</select>
<input type="checkbox" id="private'.$extrafield.'" name="private'.$extrafield.'" value="1" '.$private.' /> '.__('private','rsvpmaker').'
<br /><input type="text" id="extra'.$extrafield.'" value="'.$label.':'.$atts["options"].'"  class="text ui-widget-content ui-corner-all" />';
		}

	if(isset($atts["selectfield"])) {
		$field = $atts["selectfield"];
		if(($field == 'email') || ($field == 'email') || ($field == 'email'))
			return;
		if(strpos($field,'hone') && empty($atts["private"]))
			$private = ' checked="checked" ';
		$label = ucfirst(str_replace('_',' ',$field));
		global $extrafield;
		$extrafield++;
		$output = '<select id="type'.$extrafield.'"><option value="text">text</option><option value="hidden">hidden</option><option value="radio">radio</option><option value="select" selected="selected">select</option><option value="checkbox">checkbox</option></select> 
'.__('Show','rsvpmaker').': <select id="guest'.$extrafield.'" name="guest'.$extrafield.'">'.$goptions.'</select> <input type="checkbox" id="private'.$extrafield.'" name="private'.$extrafield.'" value="1" '.$private.' /> '.__('private','rsvpmaker').'		
<br /><input type="text" id="extra'.$extrafield.'" value="'.$label.':'.$atts["options"].'"  class="text ui-widget-content ui-corner-all" />';
		}
				
		return $output;
	}

//front end behavior

if(isset($atts["textfield"])) {
	$field = $atts["textfield"];
	$meta = (is_user_logged_in()) ? get_user_meta($current_user->ID,$field,true) : '';
	$profile[$field] = (isset($profile[$field])) ? $profile[$field] : $meta;
	if(!is_admin() && !empty($profile[$field]) && isset($atts["private"]) && $atts["private"])
		$output = '<span  class="onfile '.$field.'" >'.__('private data on file','rsvpmaker').'</span>';
	else
		{
		$size = ( isset($atts["size"]) ) ? ' size="'.$atts["size"].'" ' : '';
		$data = ( isset($profile[$field]) ) ? ' value="'.$profile[$field].'" ' : '';
		$output = '<input  class="'.$field.'" type="text" name="profile['.$field.']" id="'.$field.'" '.$size.$data.' />';
		}
	}
if(isset($atts["hidden"])) {
	$field = $atts["hidden"];
	$meta = (is_user_logged_in()) ? get_user_meta($current_user->ID,$field,true) : '';
	$profile[$field] = (isset($profile[$field])) ? $profile[$field] : $meta;
	$size = ( isset($atts["size"]) ) ? ' size="'.$atts["size"].'" ' : '';
	$data = ( isset($profile[$field]) ) ? ' value="'.$profile[$field].'" ' : '';
	$output = '<input  class="'.$field.'" type="hidden" name="profile['.$field.']" id="'.$field.'" '.$size.$data.' />';
	}
elseif(isset($atts["selectfield"])) {
	$field = $atts["selectfield"];
	$meta = (is_user_logged_in()) ? get_user_meta($current_user->ID,$field,true) : '';
	$profile[$field] = (isset($profile[$field])) ? $profile[$field] : $meta;
	if(!is_admin() && !empty($profile[$field]) && isset($atts["private"]) && $atts["private"])
		return '<span  class="onfile '.$field.'" >'.__('private data on file','rsvpmaker').'</span>';
	$selected = (isset($atts["selected"])) ? trim($atts["selected"]) : '';
	if( !empty($profile[$field]) ) 
		$selected = $profile[$field];
	$output = '<span  class="'.$field.'"><select class="'.$field.'" name="profile['.$field.']" id="'.$field.'" >'."\n";
	if(isset($atts["options"]))
		{
			$o = explode(',',$atts["options"]);
			foreach($o as $i)
				{
					$i = trim($i);
					$s = ($selected == $i) ? ' selected="selected" ' : '';
					$output .= '<option value="'.$i.'" '.$s.'>'.$i.'</option>'."\n";
				}
		}
		$output .= '</select></span>'."\n";
	}
elseif(isset($atts["checkbox"]))
	{
		$field = $atts["checkbox"];
		$value = $atts["value"];
		$ischecked = (isset($atts["checked"])) ? ' checked="checked" ' : '';

		$meta = (is_user_logged_in()) ? get_user_meta($current_user->ID,$field,true) : '';
		$profile[$field] = (isset($profile[$field])) ? $profile[$field] : $meta;
		if(!empty($profile[$field]) && isset($atts["private"]) && $atts["private"])
			return '<span  class="onfile '.$field.'" >'.__('private data on file','rsvpmaker').'</span>';

		if( isset($profile[$field]) ) 
			$ischecked = ' checked="checked" ';
		$output = '<input class="'.$field.'" type="checkbox" name="profile['.$field.']" id="'.$field.'" value="'.$value.'" '.$ischecked.'/>';
	}
elseif(isset($atts["radio"]))
	{
	$field = $atts["radio"];
	$meta = (is_user_logged_in()) ? get_user_meta($current_user->ID,$field,true) : '';
	$profile[$field] = (isset($profile[$field])) ? $profile[$field] : $meta;
	if(!empty($profile[$field]) && isset($atts["private"]) && $atts["private"])
		return '<span  class="onfile '.$field.'" >'.__('private data on file','rsvpmaker').'</span>';
	$sep = (isset($atts["sep"])) ? $atts["sep"] : ' ';
	$checked = (isset($atts["checked"])) ? trim($atts["checked"]) : '';
	if( isset($profile[$field]) ) 
		$checked = $profile[$field];
	if(isset($atts["options"]))
		{
			$o = explode(',',$atts["options"]);
			$radio = array();
			foreach($o as $i)
				{
					$i = trim($i);
					$ischecked = ($checked == $i) ? ' checked="checked" ' : '';					
					$radio[] = '<span  class="'.$field.'"><input class="'.$field.'" type="radio" name="profile['.$field.']" id="'.$field.$i.'" class="'.$field.'"  value="'.$i.'"  '.$ischecked.'/> '.$i.'</span> ';
				}
		}
		$output = implode($sep,$radio);
	}

if(isset($atts["required"]) || isset($atts["require"]))
	{
		$output = '<span class="required">'.$output.'</span>';
		$rsvp_required_field[$field] = $field;
	}

if(isset($atts["demo"]))
	{
		$demo = "<div>Shortcode:</div>\n<p><strong>[</strong>rsvpfield";
		foreach($atts as $name => $value)
			{
			if($name == "demo")
				continue;
			$demo .= ' '.$name.'="'.$value.'"';
			}
		$demo .= "<strong>]</strong></p>\n";
		$demo .= "<div>HTML:</div>\n<pre>".htmlentities($output)."</pre>\n";
		$demo .= "<div>Profile:</div>\n<pre>".var_export($profile,true)."</pre>\n";
		$demo .= "<div>Display:</div>\n<p>";
		$output = $demo . $output."</p>";
	}

if(isset($atts["guestfield"]) && $atts["guestfield"])
	{
	$guestextra[$field] = $atts;
	if($atts["guestfield"] == 2)
		return; // guest only don't display on main form
	}

if($field == 'email')
	$output .= '<div id="rsvp_email_lookup"></div>';
return $output;

}
}

if(!function_exists('guestfield') )
{
function guestfield($atts, $profile, $count) {

global $fieldcount;
if(!$fieldcount)
	$fieldcount = 1;

//synonyms
if( isset($atts["text"]) && !isset($atts["textfield"])  ) $atts["textfield"] = $atts["text"];
if( isset($atts["select"]) && !isset($atts["selectfield"])  ) $atts["selectfield"] = $atts["select"];

if(isset($atts["textfield"])) {
	$field = $atts["textfield"];
	$label = ucfirst(str_replace('_',' ',$field));
	$size = ( isset($atts["size"]) ) ? ' size="'.$atts["size"].'" ' : '';
	$data = ( isset($profile[$field]) ) ? ' value="'.$profile[$field].'" ' : '';
	$output = '<div class="'.$field.'"><label>' . $label.':</label> <input type="text" name="guest['.$field.']['.$count.']" id="'.$field.$fieldcount++.'" '.$size.$data.'  class="'.$field.'" /></div>';
	}
elseif(isset($atts["selectfield"])) {
	$field = $atts["selectfield"];
	$label = ucfirst(str_replace('_',' ',$field));
	$selected = (isset($atts["selected"])) ? trim($atts["selected"]) : '';
	if( isset($profile[$field]) ) 
		$selected = $profile[$field];
	$output = '<div class="'.$field.'"><label>' . $label.':</label> <select  class="'.$field.'" name="guest['.$field.']['.$count.']" id="'.$field.$fieldcount++.'" >'."\n";
	if(isset($atts["options"]))
		{
			$o = explode(',',$atts["options"]);
			foreach($o as $i)
				{
					$i = trim($i);
					$s = ($selected == $i) ? ' selected="selected" ' : '';
					$output .= '<option value="'.$i.'" '.$s.'>'.$i.'</option>'."\n";
				}
		}
		$output .= '</select></div>'."\n";
	}
elseif(isset($atts["radio"]))
	{
	$field = $atts["radio"];
	$label = ucfirst(str_replace('_',' ',$field));
	$sep = (isset($atts["sep"])) ? $atts["sep"] : ' ';
	$checked = (isset($atts["checked"])) ? trim($atts["checked"]) : '';
	if( isset($profile[$field]) ) 
		$checked = $profile[$field];
	if(isset($atts["options"]))
		{
			$o = explode(',',$atts["options"]);
			foreach($o as $i)
				{
					$i = trim($i);
					$ischecked = ($checked == $i) ? ' checked="checked" ' : '';					
					$radio[] = '<input  class="'.$field.'" type="radio" name="guest['.$field.']['.$count.']" id="'.$field.$i.$fieldcount++.'" class="'.$field.'"  value="'.$i.'"  '.$ischecked.'/> '.$i.' ';
				}
		}
		$output = '<div  class="'.$field.'"><label>'.$label.':</label> '.implode($sep,$radio).'</div>';
	}
return $output;

}

}

if(!function_exists('rsvpnote')) {
	function rsvpnote() {
	global $rsvp_row;
	return (isset($rsvp_row->note)) ? $rsvp_row->note : '';
	}
}

add_shortcode('rsvpnote','rsvpnote');

add_shortcode('rsvpfield','rsvpfield');

if(!function_exists('my_rsvp_menu'))
{
function my_rsvp_menu() {
global $rsvp_options;

add_submenu_page('edit.php?post_type=rsvpmaker', __("RSVP Report",'rsvpmaker'), __("RSVP Report",'rsvpmaker'), $rsvp_options["menu_security"], "rsvp", "rsvp_report" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("Event Templates",'rsvpmaker'), __("Event Templates",'rsvpmaker'), $rsvp_options["rsvpmaker_template"], "rsvpmaker_template_list", "rsvpmaker_template_list" );
if($rsvp_options["show_screen_recurring"])
	add_submenu_page('edit.php?post_type=rsvpmaker', __("Recurring Event",'rsvpmaker'), __("Recurring Event",'rsvpmaker'), $rsvp_options["recurring_event"], "add_dates", "add_dates" );
if(!empty($rsvp_options["show_screen_multiple"]))
	add_submenu_page('edit.php?post_type=rsvpmaker', __("Multiple Events","rsvpmaker"), __("Multiple Events",'rsvpmaker'), $rsvp_options["multiple_events"], "multiple", "multiple" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("Documentation",'rsvpmaker'), __("Documentation",'rsvpmaker'), $rsvp_options["documentation"], "rsvpmaker_doc", "rsvpmaker_doc" );
if(isset($rsvp_options["debug"]) && $rsvp_options["debug"])
	add_submenu_page('edit.php?post_type=rsvpmaker', "Debug", "Debug", 'manage_options', "rsvpmaker_debug", "rsvpmaker_debug");
}
}//end my_rsvp_menu

if(!function_exists('date_title') )
{
function date_title( $title, $sep = '&raquo;', $seplocation = 'left' ) {
global $post;
global $wpdb;
if($post->post_type == 'rsvpmaker')
	{
	// get first date associated with event
	$sql = "SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_rsvp_dates' AND post_id = $post->ID ORDER BY meta_value";
	$dt = $wpdb->get_var($sql);
	$title .= date('F jS',strtotime($dt) );
	if($seplocation == "right")
		$title .= " $sep ";
	else
		$title = " $sep $title ";
	}
return $title;
}
}

add_filter('wp_title','date_title', 1, 3);

if(!function_exists('rsvpmaker_template_list'))
{
function rsvpmaker_template_list () {

?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div>
<h2><?php _e('Event Templates','rsvpmaker'); 
printf(' <a href="%s"  class="add-new-h2">%s</a>',admin_url('post-new.php?post_type=rsvpmaker&new_template=1'),__('New Template','rsvpmaker'));
?>  </h2> 
<?php

if(isset($_GET["t"]))
	{
		$t = (int) $_GET["t"];
		rsvp_template_checkboxes($t);
	}

$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));
$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));

global $wpdb;
$wpdb->show_errors();
global $current_user;
global $rsvp_options;
$event_options = $template_options = '';

$sql = "SELECT DISTINCT $wpdb->posts.*, meta_value as sked FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE meta_key='_sked' GROUP BY $wpdb->posts.ID ORDER BY post_title";

$results = $wpdb->get_results($sql);
if ( $results ) {

do_action('rsvpmaker_template_list_top');

printf('<table  class="wp-list-table widefat fixed posts" cellspacing="0"><thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></thead><tbody>',__('Title','rsvpmaker'),__('Schedule','rsvpmaker'),__('Projected Dates','rsvpmaker'),__('Event','rsvpmaker'));
foreach ( $results as $post )
	{
		$sked = unserialize($post->sked);

		//backward compatability
		if(is_array($sked["week"]))
			{
				$weeks = $sked["week"];
				$dows = $sked["dayofweek"];
			}
		else
			{
				$weeks = array();
				$dows = array();
				$weeks[0] = (isset($sked["week"])) ? $sked["week"] : 0;
				$dows[0] = (isset($sked["dayofweek"]))? $sked["dayofweek"] : 0;
			}

		$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));
		$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));
		if((int)$sked["week"][0] == 0)
			$s = __('Schedule Varies','rsvpmaker');
		else
			{
			foreach($weeks as $week)
				{
				if(empty($s))
					$s = '';
				else
					$s .= '/ ';
				$s .= $weekarray[(int) $week].' ';
				}
			foreach($dows as $dow)
				$s .= $dayarray[(int) $dow] . ' ';
			fix_timezone();
			$time = strtotime($sked["hour"].':'.$sked["minutes"]);
			$s .= ' '.strftime($rsvp_options["time_format"],$time);
			}

		$eds = get_additional_editors($post->ID); 
		if(($post->post_author == $current_user->ID) || in_array($current_user->ID,$eds) || current_user_can('edit_rsvpmaker',$post->ID) )
			{
			$template_edit_url = admin_url('post.php?action=edit&post='.$post->ID);
			$title = sprintf('<a href="%s">%s</a>',$template_edit_url,$post->post_title);
			if(strpos($post->post_content,'[toastmaster') && function_exists('agenda_setup_url')) // rsvpmaker for toastmasters
				$title .= sprintf(' (<a href="%s">Toastmasters %s</a>)',agenda_setup_url($post->ID),__('Agenda Setup','rsvptoast'));
			$template_options .= sprintf('<option value="%d">%s</option>',$post->ID,$post->post_title);
			$template_recur_url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post->ID);
			printf('<tr><td>%s</td><td>%s</td><td><a href="%s">'.__('Projected Dates','rsvpmaker').'</a></td><td>%s</td></tr>'."\n",$title,$s,$template_recur_url,next_or_recent($post->ID));
			}
		else
			{
			$title = $post->post_title;
			printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>'."\n",$title,$s,__('Not an editor','rsvpmaker'),next_or_recent($post->ID));
			}
		$s = '';
		
	}
echo "</tbody></table>";

if(isset($template_options))
	{
if(!empty($_POST["override"]))
{
	$override = (int) $_POST["override"];
	$overridden = (int) $_POST["overridden"];
	$opost = get_post($override);
	$target = get_post($overridden);
	$newpost = array('ID' => $overridden, 'post_title' => $opost->post_title, 'post_content' => $opost->post_content, 'post_name' => $target->post_name);
	wp_update_post($newpost);
	update_post_meta($overridden, '_meet_recur', $override );
	printf('<p>View <a href="%s">updated post</a></p>',get_permalink($overridden));
}

		echo "<h3>Apply Template to Existing Event</h3>";
		
		$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value >= CURDATE() AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value LIMIT 0,100";
		$results = $wpdb->get_results($sql);
		foreach ($results as $r)
			{
			$event_options .= sprintf('<option value="%d">%s %s</option>',$r->postID,$r->post_title,$r->datetime);
			}
			
		$action = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list');
		
		printf('<form method="post" action="%s"><p>Apply <select name="override">%s</select> to <select name="overridden">%s</select></p>',$action, $template_options, $event_options);
		submit_button();
		echo '</form>';

	}

}

		$event_options = '';
		$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value >= DATE_SUB(CURDATE(),INTERVAL 3 MONTH) AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value LIMIT 0,100";
		$results = $wpdb->get_results($sql);
		foreach ($results as $r)
			{
			$event_options .= sprintf('<option value="%d">%s %s</option>',$r->postID,$r->post_title,$r->datetime);
			}			
		$action = admin_url('post-new.php');

		echo "<h3>Create Template Based on Existing Event</h3>";
		printf('<form method="get" action="%s"><p>%s <select name="clone">%s</select>
		<input type="hidden" name="post_type" value="rsvpmaker"><input type="hidden" name="new_template" value="1" />
		</p>',$action,__("Copy",'rsvpmaker'), $event_options);
		submit_button(__("Copy Event",'rsvpmaker'));
		echo '</form>';

		echo "<h3>Clone Event</h3>";
		printf('<form method="get" action="%s"><p>%s <select name="clone">%s</select>
		<input type="hidden" name="post_type" value="rsvpmaker">
		</p>',$action,__("Copy",'rsvpmaker'), $event_options);
		submit_button(__("Copy Event",'rsvpmaker'));
		echo '</form>';

?>

</div>
<?php
}
}// end if pluggable

function rsvpmaker_week($index = 0, $context = '') {
if($context == 'strtotime'){
	$weekarray = Array("Varies","First","Second","Third","Fourth","Last","Every");
	}
else {
	$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));
	}
return $weekarray[$index];
}

function rsvpmaker_day($index = 0, $context = '') {
if($context == 'strtotime'){
	$dayarray = Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
	}
else {
	$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'),'');
	}
return $dayarray[$index];
}

// obsolete?
if(!function_exists('rsvp_template_update_checkboxes') )
{
function rsvp_template_update_checkboxes($t) {

global $wpdb;
global $current_user;
global $post;
$add_date_checkbox = $updatelist = $editlist = $nomeeting = '';
$template = get_post_meta($t,'_sked',true);
$hour = (int) $template["hour"];
$minutes = $template["minutes"];
$cy = date("Y");$template_editor = false;
if(current_user_can('edit_others_rsvpmakers'))
	$template_editor = true;
else
	{
	$eds = get_post_meta($t,'_additional_editors',false);
	$eds[] = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID = $t");
	$template_editor = in_array($current_user->ID,$eds);		
	}

$cm = date("m");
$cd = date("j");	
	
	global $current_user;
	
	$sched_result = get_events_by_template($t);
	$add_date_checkbox = $updatelist = $editlist = $nomeeting = '';	
	if($sched_result)
	foreach($sched_result as $index => $sched)
		{
		$a = ($index % 2) ? "" : "alternate";
		$thistime = strtotime($sched->datetime);
		$donotproject[] = date('Y-m-j',$thistime);
		$nomeeting .= sprintf('<option value="%s">%s (%s)</option>',$sched->postID,date('F j, Y',$thistime), __('Already Scheduled','rsvpmaker'));
		$cy = date("Y",$thistime); // advance starting time
		$cm = date("m",$thistime);
		$cd = date("j",$thistime);
		if ( current_user_can( "delete_post", $sched->postID ) ) {
				$delete_text = __('Move to Trash');
			$d = '<a class="submitdelete deletion" href="'. get_delete_post_link($sched->postID) . '">'. $delete_text . '</a>';
		}
		else
			$d = '-';
		$edit = (($sched->post_author == $current_user->ID) || $template_editor) ? sprintf('<a href="%s?post=%d&action=edit">'.__('Edit','rsvpmaker').'</a>',admin_url("post.php"),$sched->postID) : '-';
		$editlist .= sprintf('<tr class="%s"><td><input type="checkbox" name="update_from_template[]" value="%s" /></td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s">%s</a></td></tr>',$a,$sched->postID,$edit, $d,date('F d, Y',$thistime),get_post_permalink($sched->postID),$sched->post_title);

		$template_update = get_post_meta($sched->postID,"_updated_from_template",true);
		if(!empty($template_update) && ($template_update != $sched->post_modified))
			$mod = ' <span style="color:red;">* '.__('Modified independently of template. Update could overwrite customizations.','rsvpmaker').'</span>';
		else
			$mod = '';
		$updatelist .= sprintf('<p class="%s"><input type="checkbox" name="update_from_template[]" value="%s" /><em>%s</em> %s %s %s</p>',$a,$sched->postID,__('Update','rsvpmaker'),$sched->post_title,date('F d, Y',$thistime), $mod );
		
		}

if(!empty($updatelist))
	$updatelist = "<p>".__('Already Scheduled')."</p>\n".'<fieldset>
<div><input type="checkbox" class="checkall"> '.__('Check all','rsvpmaker').'</div>'."\n"
.$updatelist."\n</fieldset>\n";

// missing template variable

//problem call
$projected = rsvpmaker_get_projected($template);

foreach($projected as $i => $ts)
{
ob_start();

$today = date('d',$ts);
$cm = date('n',$ts);
$y = date('Y',$ts);

$y2 = $y+1;

if(($ts < current_time('timestamp')) && !isset($_GET["start"]) )
	continue; // omit dates past
if(isset($donotproject) && is_array($donotproject) && in_array(date('Y-m-j',$ts), $donotproject) )
	continue;
if(empty($nomeeting)) $nomeeting = '';
$nomeeting .= sprintf('<option value="%s">%s</option>',date('Y-m-d',$ts),date('F j, Y',$ts));

?>
<div style="font-family:Courier, monospace"><input name="recur_check[<?php echo $i; ?>]" type="checkbox" value="1">
<?php _e('Month','rsvpmaker'); ?>: 
              <select name="recur_month[<?php echo $i;?>]"> 
              <option value="<?php echo $cm;?>"><?php echo $cm;?></option> 
              <option value="1">1</option> 
              <option value="2">2</option> 
              <option value="3">3</option> 
              <option value="4">4</option> 
              <option value="5">5</option> 
              <option value="6">6</option> 
              <option value="7">7</option> 
              <option value="8">8</option> 
              <option value="9">9</option> 
              <option value="10">10</option> 
              <option value="11">11</option> 
              <option value="12">12</option> 
              </select> 
            <?php _e('Day','rsvpmaker'); ?> 
            <select name="recur_day[<?php echo $i;?>]"> 
<?php
	echo sprintf('<option value="%s">%s</option>',$today,$today);
?>
              <option value="">Not Set</option>
              <option value="1">1</option> 
              <option value="2">2</option> 
              <option value="3">3</option> 
              <option value="4">4</option> 
              <option value="5">5</option> 
              <option value="6">6</option> 
              <option value="7">7</option> 
              <option value="8">8</option> 
              <option value="9">9</option> 
              <option value="10">10</option> 
              <option value="11">11</option> 
              <option value="12">12</option> 
              <option value="13">13</option> 
              <option value="14">14</option> 
              <option value="15">15</option> 
              <option value="16">16</option> 
              <option value="17">17</option> 
              <option value="18">18</option> 
              <option value="19">19</option> 
              <option value="20">20</option> 
              <option value="21">21</option> 
              <option value="22">22</option> 
              <option value="23">23</option> 
              <option value="24">24</option> 
              <option value="25">25</option> 
              <option value="26">26</option> 
              <option value="27">27</option> 
              <option value="28">28</option> 
              <option value="29">29</option> 
              <option value="30">30</option> 
              <option value="31">31</option> 
            </select> 
            <?php _e('Year','rsvpmaker'); ?>
            <select name="recur_year[<?php echo $i;?>]"> 
              <option value="<?php echo $y;?>"><?php echo $y;?></option> 
              <option value="<?php echo $y2;?>"><?php echo $y2;?></option> 
            </select>
</div>

<?php
$add_date_checkbox .= ob_get_clean();
if(empty($add_one))
	$add_one = str_replace('type="checkbox"','type="hidden"',$add_date_checkbox);
} // end for loop

$checkallscript = "<script>
jQuery(function () {
    jQuery('.checkall').on('click', function () {
        jQuery(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
    });
});
</script>
";

$action = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$t);
if(empty($updatelist)) $updatelist = '';
if(current_user_can('edit_rsvpmakers'))
return sprintf('<div class="group_add_date"><br />
<form method="post" action="%s">
%s
<div><strong>'.__('Projected Dates','rsvpmaker').':</strong></div>
<fieldset>
<div><input type="checkbox" class="checkall"> '.__('Check all','rsvpmaker').'</div>
%s
</fieldset>
<br /><input type="submit" value="'.__('Add/Update From Template','rsvpmaker').'" />
<input type="hidden" name="template" value="%s" />
</form>
</div><br />
%s',$action,$updatelist,$add_date_checkbox,$t,$checkallscript);

return ob_get_clean();
}
}

if(!function_exists('rsvp_template_checkboxes') )
{
function rsvp_template_checkboxes($t) {
global $wpdb;
global $current_user;
$nomeeting = $editlist = $add_one = $add_date_checkbox = $event_options = '';

$post = get_post($t);
$template_editor = false;
if(current_user_can('edit_others_rsvpmakers'))
	$template_editor = true;
else
	{
	$eds = get_post_meta($t,'_additional_editors',false);
	$eds[] = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID = $t");
	$template_editor = in_array($current_user->ID,$eds);		
	}

$template = get_post_meta($t,'_sked',true);
$hour = (isset($template["hour"]) ) ? (int) $template["hour"] : 17;
$minutes = isset($template["minutes"]) ? $template["minutes"] : '00';

$terms = get_the_terms( $t, 'rsvpmaker-type' );						
if ( $terms && ! is_wp_error( $terms ) ) { 
	$rsvptypes = array();

	foreach ( $terms as $term ) {
		$rsvptypes[] = $term->term_id;
	}
}

$cy = date("Y");
$cm = date("m");
$cd = date("j");

//backward compatability
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
$schedule = '';
if($weeks[0] == 0)
	$schedule = __('Schedule Varies','rsvpmaker');
foreach($weeks as $week)
	$schedule .= rsvpmaker_week($week).' ';
$schedule .= ' / ';
foreach($dows as $dow)
	$schedule .= rsvpmaker_day($dow).' ';

printf('<p id="template_ck">%s:</p><h2>%s</h2><h3>%s</h3><blockquote><a href="%s">%s</a></blockquote>',__('Template','rsvpmaker'),$post->post_title,$schedule,admin_url('post.php?action=edit&post='.$t),__('Edit Template','rsvpmaker'));

if(isset($_GET["trashed"]))
	{
		$ids = (int) $_GET["ids"];
		$message = '<a href="' . esc_url( wp_nonce_url( "edit.php?post_type=rsvpmaker&doaction=undo&action=untrash&ids=$ids", "bulk-posts" ) ) . '">' . __('Undo') . '</a>';
		echo '<div id="message" class="updated"><p>' .__('Moved to trash','rsvpmaker'). ' '.$message . '</p></div>';
	}

if(isset($_POST["update_from_template"]))
	{
		foreach($_POST["update_from_template"] as $target_id)
			{
				if(!current_user_can('publish_rsvpmakers'))
					{
						echo '<div class="updated">Error</div>';
						break;
					}
				
				$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_title=%s, post_content=%s WHERE ID=%d",$post->post_title,$post->post_content,$target_id);
				$wpdb->query($sql);
		rsvpmaker_copy_metadata($t, $target_id);

				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$target_id);
				update_post_meta($target_id,"_updated_from_template",$ts);
				if(empty($template["duration"])) $template["duration"] = '';
				$dpart = explode(':',$template["duration"]);			
				if( is_numeric($dpart[0]) )
					{
					$cddate = get_post_meta($target_id,'_rsvp_dates',true);
					$dtext = $cddate.' +'.$dpart[0].' hours';
					if(!empty($dpart[1]))
						$dtext .= ' +'.$dpart[1].' minutes';
					$dt = strtotime($dtext);
					$duration = date('Y-m-d H:i:s',$dt);
					}
				else
					$duration = $template["duration"];
				if(!empty($cddate))
					update_post_meta($target_id,'_'.$cddate,$duration);				
				if(isset($rsvptypes))
					wp_set_object_terms( $target_id, $rsvptypes, 'rsvpmaker-type', true );

				echo '<div class="updated">Updated: event #'.$target_id.' <a href="post.php?action=edit&post='.$target_id.'">Edit</a> / <a href="'.get_post_permalink($target_id).'">View</a></div>';	
			}
	}


if(isset($_POST["recur_check"]) )
{

	$my_post['post_title'] = $post->post_title;
	$my_post['post_content'] = $post->post_content;
	$my_post['post_status'] = current_user_can('publish_rsvpmakers') ? 'publish' : 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';

	foreach($_POST["recur_check"] as $index => $on)
		{
			$year = $_POST["recur_year"][$index];
			$cddate = format_cddate($year, $_POST["recur_month"][$index], $_POST["recur_day"][$index], $hour, $minutes);
			$y = (int) $_POST["recur_year"][$index];
			$m = (int) $_POST["recur_month"][$index];
			$d = (int) $_POST["recur_day"][$index];
			if($m < 10) $m = '0'.$m;
			$d = (int) $_POST["recur_day"][$index];
			if($d < 10) $d = '0'.$d;
			$date = $y.'-'.$m.'-'.$d;
			if(empty($template["duration"]))
				$template["duration"] = '';			
			$dpart = explode(':',$template["duration"]);
			
			if( is_numeric($dpart[0]) )
				{
				$dtext = $cddate.' +'.$dpart[0].' hours';
				if(!empty($dpart[1]))
					$dtext .= ' +'.$dpart[1].' minutes';
				$dt = strtotime($dtext);
				$duration = date('Y-m-d H:i:s',$dt);
				}
			else
				$duration = (isset($template["duration"])) ? $template["duration"] : '';
			
			$my_post['post_name'] = sanitize_title($my_post['post_title'] . '-' .$date );
			$singular = __('Event','rsvpmaker');
// Insert the post into the database
  			if($postID = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($postID,$cddate,$duration);
				if($my_post["post_status"] == 'publish')
					echo '<div class="updated">Posted: event for '.$cddate.' <a href="post.php?action=edit&post='.$postID.'">Edit</a> / <a href="'.get_post_permalink($postID).'">View</a></div>';
				else
					echo '<div class="updated">Draft for '.$cddate.' <a href="post.php?action=edit&post='.$postID.'">Edit</a> / <a href="'.get_post_permalink($postID).'">Preview</a></div>';
				
				add_post_meta($postID,'_meet_recur',$t,true);
				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$postID);
				update_post_meta($postID,"_updated_from_template",$ts);
				
				rsvpmaker_copy_metadata($t, $postID);
				
				}
		
		}
}

if(isset($_POST["nomeeting"]) )
{
	$my_post['post_title'] = __('No Meeting','rsvpmaker').': '.$post->post_title;
	$my_post['post_content'] = $_POST["nomeeting_note"];
	$my_post['post_status'] = current_user_can('publish_rsvpmakers') ? 'publish' : 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';
	
	if(!strpos($_POST["nomeeting"],'-'))
		{ //update vs new post
			$id = (int) $_POST["nomeeting"];
			$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_title=%s, post_content=%s WHERE ID=%d",$my_post['post_title'],$my_post['post_content'],$id);
			$wpdb->show_errors();
			$return = $wpdb->query($sql);
			if($return == false)
				echo '<div class="updated">'."Error: $sql.</div>\n";
			else
				echo '<div class="updated">Updated: no meeting <a href="post.php?action=edit&post='.$postID.'">Edit</a> / <a href="'.get_post_permalink($id).'">View</a></div>';	
		}
	else
		{
			$cddate = $_POST["nomeeting"];
			$my_post['post_name'] = sanitize_title($my_post['post_title'] . '-' .$cddate );

// Insert the post into the database
  			if($postID = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($postID,$cddate,'allday');
				echo '<div class="updated">Posted: event for '.$cddate.' <a href="post.php?action=edit&post='.$postID.'">Edit</a> / <a href="'.get_post_permalink($postID).'">View</a></div>';	
				add_post_meta($postID,'_meet_recur',$t,true);
				}
		}		
}

	$sched_result = get_events_by_template($t);
	if($sched_result)
	foreach($sched_result as $index => $sched)
		{
		$a = ($index % 2) ? "" : "alternate";
		$thistime = strtotime($sched->datetime);
		$nomeeting .= sprintf('<option value="%s">%s (%s)</option>',$sched->postID,date('F j, Y',$thistime), __('Already Scheduled','rsvpmaker'));
		$donotproject[] = date('Y-m-j',$thistime);
		if ( current_user_can( "delete_post", $sched->postID ) ) {
				$delete_text = __('Move to Trash');
			$d = '<a class="submitdelete deletion" href="'. get_delete_post_link($sched->postID) . '">'. $delete_text . '</a>';
		}
		else
			$d = '-';
		$edit = (($sched->post_author == $current_user->ID) || $template_editor) ? sprintf('<a href="%s?post=%d&action=edit">'.__('Edit','rsvpmaker').'</a>',admin_url("post.php"),$sched->postID) : '-';
		$editlist .= sprintf('<tr class="%s"><td><input type="checkbox" name="update_from_template[]" value="%s" /></td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s">%s</a></td></tr>',$a,$sched->postID,$edit, $d,date('F d, Y',$thistime),get_post_permalink($sched->postID),$sched->post_title);
		}

$projected = rsvpmaker_get_projected($template);

foreach($projected as $i => $ts)
{
$today = date('d',$ts);
$cm = date('n',$ts);
$y = date('Y',$ts);

$y2 = $y+1;

ob_start();

if(($ts < current_time('timestamp')) && !isset($_GET["start"]) )
	continue; // omit dates past
if(isset($donotproject) && is_array($donotproject) && in_array(date('Y-m-j',$ts), $donotproject) )
	continue;

$nomeeting .= sprintf('<option value="%s">%s</option>',date('Y-m-d',$ts),date('F j, Y',$ts));

?>
<div style="font-family:Courier, monospace"><input name="recur_check[<?php echo $i; ?>]" type="checkbox" value="1">
<?php _e('Month','rsvpmaker'); ?>: 
              <select name="recur_month[<?php echo $i;?>]"> 
              <option value="<?php echo $cm;?>"><?php echo $cm;?></option> 
              <option value="1">1</option> 
              <option value="2">2</option> 
              <option value="3">3</option> 
              <option value="4">4</option> 
              <option value="5">5</option> 
              <option value="6">6</option> 
              <option value="7">7</option> 
              <option value="8">8</option> 
              <option value="9">9</option> 
              <option value="10">10</option> 
              <option value="11">11</option> 
              <option value="12">12</option> 
              </select> 
            <?php _e('Day','rsvpmaker'); ?> 
            <select name="recur_day[<?php echo $i;?>]"> 
<?php
	echo sprintf('<option value="%s">%s</option>',$today,$today);
?>
              <option value="">Not Set</option>
              <option value="1">1</option> 
              <option value="2">2</option> 
              <option value="3">3</option> 
              <option value="4">4</option> 
              <option value="5">5</option> 
              <option value="6">6</option> 
              <option value="7">7</option> 
              <option value="8">8</option> 
              <option value="9">9</option> 
              <option value="10">10</option> 
              <option value="11">11</option> 
              <option value="12">12</option> 
              <option value="13">13</option> 
              <option value="14">14</option> 
              <option value="15">15</option> 
              <option value="16">16</option> 
              <option value="17">17</option> 
              <option value="18">18</option> 
              <option value="19">19</option> 
              <option value="20">20</option> 
              <option value="21">21</option> 
              <option value="22">22</option> 
              <option value="23">23</option> 
              <option value="24">24</option> 
              <option value="25">25</option> 
              <option value="26">26</option> 
              <option value="27">27</option> 
              <option value="28">28</option> 
              <option value="29">29</option> 
              <option value="30">30</option> 
              <option value="31">31</option> 
            </select> 
            <?php _e('Year','rsvpmaker'); ?>
            <select name="recur_year[<?php echo $i;?>]"> 
              <option value="<?php echo $y;?>"><?php echo $y;?></option> 
              <option value="<?php echo $y2;?>"><?php echo $y2;?></option> 
            </select>
</div>

<?php
$add_date_checkbox .= ob_get_clean();
if(empty($add_one))
	$add_one = str_replace('type="checkbox"','type="hidden"',$add_date_checkbox);
} // end for loop

$checkallscript = "<script>
jQuery(function () {
    jQuery('.checkall').on('click', function () {
        jQuery(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
    });
});
</script>
";



$action = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$t);


if($editlist)
{
do_action("update_from_template_prompt");
	echo '<strong>'.__('Already Scheduled','rsvpmaker').':</strong><br /><br /><form method="post" action="'.$action.'">
<fieldset>
<table  class="wp-list-table widefat fixed posts" cellspacing="0">
<thead>
<tr><th class="manage-column column-cb check-column" scope="col" ><input type="checkbox" class="checkall" title="Check all"></th><th>'.__('Edit').'</th><th>'.__('Move to Trash').'<th>'.__('Date').'</th><th>'.__('Title').'</th></tr>
</thead>
<tbody>
'.$editlist.'
</tbody></table>
</fieldset>
<input type="submit" value="'.__('Update Checked','rsvpmaker').'" /></form>'.'<p>'.__('Update function copies title and content of current template, replacing the existing content of checked posts.','rsvpmaker').'</p>';
}

if(current_user_can('edit_rsvpmakers'))
{
do_action("add_from_template_prompt");
printf('<div class="group_add_date"><br />
<form method="post" action="%s">
<strong>'.__('Add One','rsvpmaker').':</strong><br />
%s
<input type="hidden" name="rsvpmaker_add_one" value="1" />
<input type="hidden" name="template" value="%s" />
<br /><input type="submit" value="'.__('Add From Template','rsvpmaker').'" />
</form>
<form method="post" action="%s">
<br /><strong>'.__('Projected Dates','rsvpmaker').':</strong>
<fieldset>
<div><input type="checkbox" class="checkall"> '.__('Check all','rsvpmaker').'</div>
%s
</fieldset>
<br /><input type="submit" value="'.__('Add From Template','rsvpmaker').'" />
<input type="hidden" name="template" value="%s" />
</form>
</div><br />
%s',$action,$add_one,$t,$action,$add_date_checkbox,$t,$checkallscript);
}

if(current_user_can('edit_rsvpmakers'))
printf('<div class="group_add_date"><br />
<form method="post" action="%s">
<strong>%s:</strong><br />
%s: <select name="nomeeting">%s</select>
<br />%s:<br /><textarea name="nomeeting_note" cols="60" %s></textarea>
<input type="hidden" name="template" value="%s" />
<br /><input type="submit" value="%s" />
</form>
</div><br />
',$action,__('No Meeting','rsvpmaker'),__('Regularly Scheduled Date','rsvpmaker'),$nomeeting,__('Note (optional)','rsvpmaker'),'style="max-width: 95%;"',$t,__('Submit','rsvpmaker'));

}
} // end function_exists

if(!function_exists('rsvpmaker_updated_messages'))
{
function rsvpmaker_updated_messages($messages) {
if(empty($messages) )
	return;

global $post, $post_ID;

if($post->post_type != 'rsvpmaker') return; // only for RSVPMaker

$singular = __('Event','rsvpmaker');
$link = sprintf(' <a href="%s">%s %s</a>',esc_url( get_post_permalink($post_ID)),__('View','rsvpmaker'), $singular );

$sked = get_post_meta($post_ID,'_sked',true);
if(!empty($sked) )
	{
		$singular = __('Event Template','rsvpmaker');
		$link = "<br />".rsvp_template_update_checkboxes($post_ID);
		//$link = sprintf(' <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post_ID),__('View/add/update events based on this template','rsvpmaker'));
	}

$messages['rsvpmaker'] = array(
0 => '', // Unused. Messages start at index 1.
1 => $singular.' '.__('updated','rsvpmaker').$link,
2 => __('Custom field updated.'),
3 => __('Custom field deleted.'),
4 => $singular.' '.__('updated','rsvpmaker').$link,
5 => isset($_GET['revision']) ? sprintf( __($singular.' restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
6 => $singular.' '.__('published','rsvpmaker').$link,
7 => __('Page saved.'),
8 => sprintf( __($singular.' submitted. <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>'), esc_url( add_query_arg( 'preview', 'true', get_post_permalink($post_ID) ) ) ),
9 => sprintf( __($singular.' scheduled for: <strong>%s</strong>. <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_post_permalink($post_ID) ) ),
10 => sprintf( __($singular.' draft updated. <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>'), esc_url( add_query_arg( 'preview', 'true', get_post_permalink($post_ID) ) ) ),
);

return $messages;
}
} // end if function

if( !function_exists('rsvpmaker_template_admin_title') )
{
function rsvpmaker_template_admin_title() {
global $title;
global $post;
global $post_new_file;
if(!isset($post) || ($post->post_type != 'rsvpmaker'))
	return;
if(!empty($_GET["new_template"]) || get_post_meta($post->ID,'_sked',true))
	{
	$title .= ' '.__('Template','rsvpmaker');
	if(isset($post_new_file))
		$post_new_file = 'post-new.php?post_type=rsvpmaker&new_template=1';
	}
}
}

add_action('admin_head','rsvpmaker_template_admin_title');

if(!function_exists('next_or_recent')) {
function next_or_recent($template_id) {
global $wpdb;
global $rsvp_options;
$event = '';
$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, a1.meta_value as datetime, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id 
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id 
	 WHERE a1.meta_key='_rsvp_dates' AND a1.meta_value > CURDATE() AND a2.meta_key='_meet_recur' AND a2.meta_value=".$template_id." AND post_status='publish'
	 ORDER BY a1.meta_value LIMIT 0,1";
if($row = $wpdb->get_row($sql) )
{
	$t = strtotime($row->datetime);
	$neatdate = utf8_encode(strftime($rsvp_options["long_date"],$t));
	$event = sprintf('<a href="%s">%s: %s</a>',get_post_permalink($row->postID),__('Next Event','rsvpmaker'),$neatdate );
}
else {
$sql ="SELECT DISTINCT $wpdb->posts.ID as postID, a1.meta_value as datetime, a2.meta_value as template
	 FROM ".$wpdb->posts."
	 LEFT JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id
	 LEFT JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id 
	 WHERE a1.meta_key='_rsvp_dates' AND a1.meta_value < CURDATE() AND a2.meta_key='_meet_recur' AND a2.meta_value=".$template_id." AND post_status='publish'
	 ORDER BY a1.meta_value DESC LIMIT 0,1";
	if($row = $wpdb->get_row($sql) )
	{
	$t = strtotime($row->datetime);
	$neatdate = utf8_encode(strftime($rsvp_options["long_date"],$t));
	$event = sprintf('<a style="color:#333;" href="%s">%s: %s</a>',get_post_permalink($row->postID),__('Most Recent','rsvpmaker'),$neatdate );
	}
}
return $event;
}
} // end if funnction

if(isset($_GET["message"]))
	add_filter('post_updated_messages', 'rsvpmaker_updated_messages' );

if(!function_exists('additional_editors_setup') )
{
function additional_editors_setup() {
global $rsvp_options;
if(isset($rsvp_options["additional_editors"]) && $rsvp_options["additional_editors"])
	{
		add_action('save_post','save_additional_editor');
		//add_filter( 'user_has_cap', 'rsvpmaker_cap_filter', 99, 3 );
		add_filter( 'map_meta_cap', 'rsvpmaker_map_meta_cap', 10, 4 );
	}
}
}

add_action('admin_init','additional_editors_setup');

if(!function_exists('rsvpmaker_cap_filter_test') )
{
function rsvpmaker_cap_filter_test( $cap, $post_id ) {
	
	if(strpos($cap,'rsvpmaker') )
		return true;
	elseif($post = get_post($post_id))
	{
		if(isset($post->post_type) && ($post->post_type =='rsvpmaker'))
			return true;
		else
			return false;
	}
	else
		return false;
}
}

if(!function_exists('get_additional_editors') )
{
function get_additional_editors($post_id) {
global $wpdb;
$eds = array();
	$recurid = get_post_meta($post_id,'_meet_recur',true);
	if($recurid)
	{
		$eds = get_post_meta($recurid,'_additional_editors',false);
		$author = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID = $recurid");
		if(!in_array($author, $eds))
		{
			$eds[] = $author;
		}
	}
	$post_eds = get_post_meta($post_id,'_additional_editors',false);
	$author = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID = $post_id");		
	if(!in_array($author, $post_eds))
	{
		$post_eds[] = $author;
	}

	foreach($post_eds as $this_eds)
	{
		if(!in_array($this_eds, $eds))
			$eds[] = $this_eds;
	}

return $eds;
}
}// end if exists

if(!function_exists('save_additional_editor') )
{
function save_additional_editor($postID) {

if(!empty($_POST["additional_editor"]) || !empty($_POST["remove_editor"]))
	{
	if($parent_id = wp_is_post_revision($postID))
		{
		$postID = $parent_id;
		}
	}
if(!empty($_POST["additional_editor"]))
	{		
	$ed = (int) $_POST["additional_editor"];
	if($ed)
		add_post_meta($postID,'_additional_editors',$ed,false);
	}
if(!empty($_POST["remove_editor"]))
	{		
	foreach($_POST["remove_editor"] as $remove)
		{
			$remove = (int) $remove;
			if($remove)
				delete_post_meta($postID,'_additional_editors',$remove);
		}
	}
}
} // end function exists

if(!function_exists('rsvpmaker_editor_dropdown') )
{
function rsvpmaker_editor_dropdown ($eds) {
global $wpdb;
$options = '';
$sql = "SELECT * FROM $wpdb->users ORDER BY user_login";
$results = $wpdb->get_results($sql);
	foreach($results as $row)
		{
			if(in_array($row->ID,$eds) )
				continue;
			$member = get_userdata($row->ID);
			$index = preg_replace('/[^a-zA-Z]/','',$member->last_name.$member->first_name.$row->user_login);
			$sortmember[$index] = $member;
		}
	ksort($sortmember);
	
	foreach($sortmember as $index => $member)
		{
			if(isset($member->last_name) && !empty($member->last_name) )
				$label = $member->first_name.' '.$member->last_name;
			else
				$label = $index;
			if($member->ID == $assigned)
				$s = ' selected="selected" ';
			else
				$s = '';
			$options .= sprintf('<option %s value="%d">%s</option>',$s, $member->ID,$label);
		}
	return $options;
}
} // end function exists

if(!function_exists('additional_editors') )
{
function additional_editors() {
global $post;
global $custom_fields;

if($post->ID)
$eds = get_post_meta($post->ID,'_additional_editors',false);
if($eds)
{
echo "<strong>".__("Editors",'rsvpmaker').":</strong><br />";
foreach($eds as $user_id)
	{
	$member = get_userdata($user_id);
	if(isset($member->last_name) && !empty($member->last_name) )
		$label = $member->first_name.' '.$member->last_name;
	else
		$label = $member->user_login;
	$label .= ' '.$member->user_email;
	echo $label.sprintf(' <strong>( <input type="checkbox" name="remove_editor[]" value="%d"> %s)</strong><br />',$user_id,__('Remove','rsvpmaker'));
	}
}
?>
<p><?php _e('Add Editor','rsvpmaker'); ?>: <select name="additional_editor" ><option value=""><?php _e('Select'); ?></option><?php echo rsvpmaker_editor_dropdown($eds); ?></select></p>
<?php

if(isset($custom_fields["_meet_recur"][0]));
	{
	echo "<strong>".__("Template",'rsvpmaker').' '.__("Editors",'rsvpmaker').":</strong><br />";
	$t = isset($custom_fields["_meet_recur"][0]) ? $custom_fields["_meet_recur"][0] : 0;	

	$eds = get_post_meta($t,'_additional_editors',false);
	if($eds)
	{
	foreach($eds as $user_id)
		{
		$member = get_userdata($user_id);
		if(isset($member->last_name) && !empty($member->last_name) )
			$label = $member->first_name.' '.$member->last_name;
		else
			$label = $member->user_login;
		echo $label.'<br />';
		}
	}
	else
		_e('None','rsvpmaker');
	printf('<p><a href="%s">'.__('Edit Template','rsvpmaker').'</a></p>', admin_url('post.php?action=edit&post='.$t));
	}
do_action('rsvpmaker_additional_editors');
}
} // function exists

if( !function_exists('rsvpmaker_dashboard_widget_function') )
{ 
function rsvpmaker_dashboard_widget_function () {
global $wpdb;
global $rsvp_options;
global $current_user;
//$wpdb->show_errors();

do_action('rsvpmaker_dashboard_action');

if(isset($rsvp_options["dashboard_message"]) && !empty($rsvp_options["dashboard_message"]) )
	echo '<div>'.$rsvp_options["dashboard_message"].'</div>';

echo '<p><strong>'.__('My Events','rsvpmaker').'</strong><br /></p>';
$results = get_future_events('post_author='.$current_user->ID);
if($results)
	{
		foreach ($results as $index => $row)
		{
			$draft = ($row->post_status == 'draft') ? ' (draft)' : '';
			printf('<p><a href="%s">('.__('Edit','rsvpmaker').')</a> <a href="%s">%s %s%s</a></p>',admin_url('post.php?action=edit&post='.$row->ID),get_post_permalink($row->ID), $row->post_title, utf8_encode(strftime($rsvp_options["long_date"],strtotime($row->datetime))), $draft );
			if($index == 10)
				{
				printf('<p><a href="%s">&gt; &gt; '.__('More','rsvpmaker').'</a></p>',admin_url('edit.php?post_type=rsvpmaker&rsvpsort=chronological&author='.$current_user->ID) );
				break;
				}
		}
	}
else {
	'<p>'.__('None','rsvpmaker').'</p>';
}

printf('<p><a href="%s">'.__('Add Event','rsvpmaker').'</a></p>',admin_url('post-new.php?post_type=rsvpmaker'));

$sql = "SELECT $wpdb->posts.ID as editid FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
WHERE $wpdb->posts.post_type = 'rsvpmaker' AND $wpdb->postmeta.meta_key = '_additional_editors' AND $wpdb->postmeta.meta_value = $current_user->ID";
$wpdb->show_errors();
$result = $wpdb->get_results($sql);
$sql = "SELECT $wpdb->posts.ID as editid FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE post_type='rsvpmaker' AND post_status='publish' AND meta_key='_sked' AND post_author=$current_user->ID";
$r2 = $wpdb->get_results($sql);

if($result && $r2)
	$result = array_merge($r2,$result);
elseif($r2)
	$result = $r2;

if( $result )
{
foreach($result as $row)
	{
	rsvp_template_checkboxes($row->editid);
	}
}

}
} // end function exists

function rsvpmaker_add_dashboard_widgets() {

global $rsvp_options;

wp_add_dashboard_widget('rsvpmaker_dashboard_widget', __( 'Events','rsvpmaker' ), 'rsvpmaker_dashboard_widget_function');

if(empty($rsvp_options["dashboard"]) || ($rsvp_options["dashboard"] != 'top'))
	return;

// Globalize the metaboxes array, this holds all the widgets for wp-admin

global $wp_meta_boxes;

// Get the regular dashboard widgets array
// (which has our new widget already but at the end)

$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
/*
foreach($normal_dashboard as $name => $value)
	echo $name . "<br />";
*/
// Backup and delete our new dashbaord widget from the end of the array

$rsvpmaker_widget_backup = array('rsvpmaker_dashboard_widget' =>
$normal_dashboard['rsvpmaker_dashboard_widget']);

unset($normal_dashboard['rsvpmaker_dashboard_widget']);

// Merge the two arrays together so our widget is at the beginning

$sorted_dashboard = array_merge($rsvpmaker_widget_backup, $normal_dashboard);

// Save the sorted array back into the original metaboxes

$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;

}

// Hook into the 'wp_dashboard_setup' action to register our other functions

if(isset($rsvp_options["dashboard"]) && !empty($rsvp_options["dashboard"]) )
	add_action('wp_dashboard_setup', 'rsvpmaker_add_dashboard_widgets' );

?>