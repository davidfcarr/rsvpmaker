<?php

function date_slug($data) {
	
	if(!empty($_POST["override"]))
		return $data; // don't do this for template override
	
	if($data["post_status"] != 'publish')
		return $data;

	if(isset($_POST["edit_month"][0]) )
		{
		$y = (int) $_POST["edit_year"][0];
		$m = (int) $_POST["edit_month"][0];
		if($m < 10)
			$m = '0'.$m;
		$d = (int) $_POST["edit_day"][0];
		if($d < 10)
			$d = '0'.$d;			
		$date = $y.'-'.$m.'-'.$d;
	
		if (empty($data['post_name']) || !strpos($data['post_name'],$date) ) {
			$data['post_name'] = sanitize_title($data['post_title']);
			$data['post_name'] .= '-' .$date;
			}
		}
	elseif(isset($_POST["event_month"][0]) )
		{
		$y = (int) $_POST["event_year"][0];
		$m = (int) $_POST["event_month"][0];
		if($m < 10)
			$m = '0'.$m;
		$d = (int) $_POST["event_day"][0];
		if($d < 10)
			$d = '0'.$d;			
		$date = $y.'-'.$m.'-'.$d;
	
		if (empty($data['post_name']) || !strpos($data['post_name'],$date) ) {
			$data['post_name'] = sanitize_title($data['post_title']);
			$data['post_name'] .= '-' .$date;
			}
		}
	
	return $data;
}

add_filter('wp_insert_post_data', 'date_slug', 10);

function unique_date_slug($slug, $post_ID = 0, $post_status = '', $post_type = '', $post_parent = 0, $original_slug='' )
	{
	global $wpdb;
	if($post_type != 'rsvpmaker')
		return $slug;
	if($post_status != 'publish')
		return $slug;
	if(!empty($_POST["override"]))
		return $slug; // don't do this for template override
	
	$post = get_post($post_ID);
	if(empty($post->post_type)) return $slug;
	$date = str_replace(' ', '_',str_replace(':00','',get_rsvp_date($post_ID)));
	$newslug = sanitize_title($post->post_title.'-' .$date);
	return $newslug;
	}

add_filter('wp_unique_post_slug','unique_date_slug',10);

function save_calendar_data($postID) {

global $wpdb;

if($parent_id = wp_is_post_revision($postID))
	{
	$postID = $parent_id;
	}

if(isset($_POST["_require_webinar_passcode"]))
	{
	update_post_meta($postID,'_require_webinar_passcode',$_POST["_require_webinar_passcode"]);
	}
if(isset($_POST["event_month"]) )
	{
	foreach($_POST["event_year"] as $index => $year)
		{
		if(isset($_POST["event_day"][$index]) && $_POST["event_day"][$index])
			{
			$cddate = format_cddate($year,$_POST["event_month"][$index], $_POST["event_day"][$index],$_POST["event_hour"][$index],$_POST["event_minutes"][$index]);
			$dpart = explode(':',$_POST["event_duration"][$index]);
			if( is_numeric($dpart[0]) )
				{
				$hour = $_POST["event_hour"][$index] + $dpart[0];
				$minutes = (isset($dpart[1]) ) ? $_POST["event_minutes"][$index] + $dpart[1] : $_POST["event_minutes"][$index];
				$t = mktime( $hour, $minutes,0,$_POST["event_month"][$index],$_POST["event_day"][$index],$year);
				$duration = date('Y-m-d H:i:s',$t);
				}
			else
				$duration = $_POST["event_duration"][$index]; // empty or all day
				
			$dates_array[] = $cddate;
			$durations_array[] = $duration;
			}
		}
	}

if(isset($_POST["edit_month"]))
	{
	foreach($_POST["edit_year"] as $index => $year)
		{
			$cddate = format_cddate($year,$_POST["edit_month"][$index],  $_POST["edit_day"][$index], $_POST["edit_hour"][$index], $_POST["edit_minutes"][$index]);
			if(strpos( $_POST["edit_duration"][$index],':' ))
				{
				$dpart = explode(':',$_POST["edit_duration"][$index]);
				if( is_numeric($dpart[0]) )
					{
					$hour = $_POST["edit_hour"][$index] + $dpart[0];
					$minutes = (isset($dpart[1]) ) ? $_POST["edit_minutes"][$index] + $dpart[1] : $_POST["edit_minutes"][$index];
					//dchange
					$duration = date('Y-m-d H:i:s',mktime( $hour, $minutes,0,$_POST["edit_month"][$index],$_POST["edit_day"][$index],$year));
					}
				}
			elseif( is_numeric($_POST["edit_duration"][$index]) )
				{					
				$minutes = $_POST["edit_minutes"][$index] + (60*$_POST["edit_duration"][$index]);
				//dchange - can this be removed?
				$duration = date('Y-m-d H:i:s',mktime( $_POST["edit_hour"][$index], $minutes,0,$_POST["edit_month"][$index],$_POST["edit_day"][$index],$year));
				}
			else
				$duration = $_POST["edit_duration"][$index]; // empty or all day			
			$dates_array[] = $cddate;
			$durations_array[] = $duration;
			}
	} // end edit month

	if(!empty($dates_array) )
		update_rsvpmaker_dates($postID, $dates_array,$durations_array);

	if(isset($_POST["delete_date"]))
		{
		foreach($_POST["delete_date"] as $delete_date)
			{
			delete_rsvpmaker_date($postID,$delete_date);
			}
		}	
	
	if(isset($_POST["setrsvp"]) )
	{ // if rsvp parameters were set, was RSVP box checked?
	if(isset($_POST["setrsvp"]["on"]))
		save_rsvp_meta($postID);
	else
		delete_post_meta($postID, '_rsvp_on', '1');
	}
	
	if(isset($_POST["sked"]["week"]))
		{
		save_rsvp_template_meta($postID);
		}

	if(isset($_POST['add_timezone']) && $_POST['add_timezone'])
		update_post_meta($postID,'_add_timezone',1);
	else
		update_post_meta($postID,'_add_timezone',0);	
	if(isset($_POST['convert_timezone']) && $_POST['convert_timezone'])
		update_post_meta($postID,'_convert_timezone',1);
	else
		update_post_meta($postID,'_convert_timezone',0);	

	if(isset($_POST['calendar_icons']) && $_POST['calendar_icons'])
		update_post_meta($postID,'_calendar_icons',1);
	else
		update_post_meta($postID,'_calendar_icons',0);	

}

function rsvpmaker_date_option($datevar = NULL, $index = NULL, $jquery_date = NULL) {

global $rsvp_options;
$prefix = "event_";
fix_timezone();
if(is_array($datevar) )
{
	$datestring = $datevar["datetime"];
	//dchange - check this
	$duration = $datevar["duration"];
	if(strpos($duration,':'))
		$duration = strtotime($duration);
	$prefix = "edit_";
	if(isset($datevar["id"]))
		$index = $datevar["id"];
}
else
{
	$datestring = $datevar;
}

if(strpos($datestring,'-'))
	{
	$t = strtotime($datestring);
	$month =  (int) date('m',$t);
	$year =  (int) date('Y',$t);
	$day =  (int) date('d',$t);
	$hour =  (int) date('G',$t);
	$minutes =  (int) date('i',$t);
	}
elseif($datestring == 'today')
	{
	$month =  (int) date('m');
	$year =  (int) date('Y');
	$day =  (int) date('d');
	$hour = (isset($rsvp_options["defaulthour"])) ? ( (int) $rsvp_options["defaulthour"]) : 19;
	$minutes = (isset($rsvp_options["defaultmin"])) ? ( (int) $rsvp_options["defaultmin"]) : 0;
	}
else
	{
	$month = (int) date('m');
	$year =  (int) date('Y');
	$day = 0;
	$hour = (isset($rsvp_options["defaulthour"])) ? ( (int) $rsvp_options["defaulthour"]) : 19;
	$minutes = (isset($rsvp_options["defaultmin"])) ? ( (int) $rsvp_options["defaultmin"]) : 0;
	}

?>
<div id="<?php echo $prefix; ?>date<?php echo $index;?>" style="border-bottom: thin solid #888;">
<table width="100%">
<tr>
            <td width="*"><div class="date_block"><?php echo __('Month:','rsvpmaker');?> 
<select id="month<?php echo $index;?>" name="<?php echo $prefix; ?>month[<?php echo $index;?>]"> 
<?php
for($i = 1; $i <= 12; $i++)
{
echo "<option ";
	if($i == $month)
		echo ' selected="selected" ';
	echo 'value="'.$i.'">'.$i."</option>\n";
}
?>
</select> 
<?php echo __('Day:','rsvpmaker');?> 
<select  id="day<?php echo $index;?>"  name="<?php echo $prefix; ?>day[<?php echo $index;?>]"> 
<?php
if($day == 0)
	echo '<option value="0">Not Set</option>';
for($i = 1; $i <= 31; $i++)
{
echo "<option ";
	if($i == $day)
		echo ' selected="selected" ';
	echo 'value="'.$i.'">'.$i."</option>\n";
}
?>
</select> 
<?php echo __('Year','rsvpmaker');?>
<select  id="year<?php echo $index;?>" name="<?php echo $prefix; ?>year[<?php echo $index ;?>]"> 
<?php
$y = (int) date('Y');
$limit = $y + 3;
for($i = $y; $i < $limit; $i++)
{
echo "<option ";
	if($i == $year)
		echo ' selected="selected" ';
	echo 'value="'.$i.'">'.$i."</option>\n";
}
?>
</select> 
<input type="hidden" id="datepicker<?php echo $index;?>" value="<?php echo $jquery_date;?>">
</div> 
            </td> 
          </tr> 
<tr> 
<td><?php echo __('Hour:','rsvpmaker');?> <select name="<?php echo $prefix; ?>hour[<?php echo $index;?>]"> 
<?php
for($i=0; $i < 24; $i++)
	{
	$selected = ($i == $hour) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	if($i == 0)
		$twelvehour = "12 a.m.";
	elseif($i == 12)
		$twelvehour = "12 p.m.";
	elseif($i > 12)
		$twelvehour = ($i - 12) ." p.m.";
	else		
		$twelvehour = $i." a.m.";

	printf('<option  value="%s" %s>%s / %s:</option>',$padded,$selected,$twelvehour,$padded);
	}
?>
</select> 
 
<?php echo __('Minutes:','rsvpmaker');?> <select name="<?php echo $prefix; ?>minutes[<?php echo $index;?>]"> 
<?php
for($i=0; $i < 60; $i ++)
	{
	$selected = ($i == $minutes) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	printf('<option  value="%s" %s>%s</option>',$padded,$selected,$padded);
	}
?>
</select> -

<?php echo __('Duration','rsvpmaker');?> <select name="<?php echo $prefix; ?>duration[<?php echo $index;?>]">
<option value=""><?php echo __('Not set (optional)','rsvpmaker');?></option>
<option value="allday" <?php if(isset($duration) && ($duration == 'allday')) echo ' selected="selected" '; ?>><?php echo __("All day/don't show time in headline",'rsvpmaker');?></option>
<?php
if(isset($duration) && is_numeric($duration) )
	{
	$diff = (string) ( (((int) $duration) - $t) / 3600);
	$dparts = explode('.',$diff);
	$dh = (int) $dparts[0];
	$decimal = (isset($dparts[1]) ) ? (int) $dparts[1] : 0;
	}
else
	{
		$dh = $decimal = NULL;
	}
for($h = 0; $h < 24; $h++) {

if($h != 0)
{
?>
<option value="<?php echo $h;?>" <?php if(($h == $dh) && ($decimal == 0) ) echo ' selected="selected" '; ?> ><?php echo $h.' '.__('hours','rsvpmaker');?></option>
<?php
}
?>
<option value="<?php echo $h;?>:15" <?php if(($h == $dh) && ($decimal == 25) ) echo ' selected="selected" '; ?> ><?php echo $h;?>:15</option>
<option value="<?php echo $h;?>:30"  <?php if(($h == $dh) && ($decimal == 5) ) echo ' selected="selected" '; ?> ><?php echo $h;?>:30</option>

<option value="<?php echo $h;?>:45"  <?php if(($h == $dh) && ($decimal == 75) ) echo ' selected="selected" '; ?> ><?php echo $h;?>:45</option>
<?php } ;?>
</select>
<br /> 
</td> 
          </tr> 
</table>
</div>
<?php

}

function save_rsvp_meta($postID)
{
$setrsvp = $_POST["setrsvp"];

$checkboxes = array("show_attendees","count","captcha","login_required",'confirmation_include_event','yesno');
foreach($checkboxes as $check)
	{
		if(!isset($setrsvp[$check]))
			$setrsvp[$check] = 0;
	}

if(isset($_POST["deadyear"]) && isset($_POST["deadmonth"]) && isset($_POST["deadday"]))
	{
	if(empty($_POST["deadday"]))
		$setrsvp["deadline"] = '';
	else
		$setrsvp["deadline"] = strtotime($_POST["deadyear"].'-'.$_POST["deadmonth"].'-'.$_POST["deadday"].' '.$_POST["deadtime"]);
	}

if(isset($_POST["startyear"]) && isset($_POST["startmonth"]) && isset($_POST["startday"]))
	{
	if(empty($_POST["startday"]))
		$setrsvp["start"] = '';
	else
		$setrsvp["start"] = strtotime($_POST["startyear"].'-'.$_POST["startmonth"].'-'.$_POST["startday"].' '.$_POST["starttime"]);
	}
//legacy
if(isset($_POST["remindyear"]) && isset($_POST["remindmonth"]) && isset($_POST["remindday"]))
	$setrsvp["reminder"] = date('Y-m-d H:i:s',strtotime($_POST["remindyear"].'-'.$_POST["remindmonth"].'-'.$_POST["remindday"].' '.$_POST["remindtime"]));

foreach($setrsvp as $name => $value)
	{
	$field = '_rsvp_'.$name;
	$single = true;
	update_post_meta($postID, $field, $value);
	}

if(isset($_POST["unit"]))
	{
				
	foreach($_POST["unit"] as $index => $value)
		{
		if(empty($value))
			continue;
		if( empty($_POST["price"][$index]) && ($_POST["price"][$index] != 0) )
			continue;
		$per["unit"][$index] = $value;
		$per["price"][$index] = $_POST["price"][$index];
		if(!empty($_POST["price_deadline"][$index]))
			{
			fix_timezone();
			$per["price_deadline"][$index] = strtotime($_POST["price_deadline"][$index]);		
			}
		if(isset($_POST['showhide'][$index]))
			{
				foreach($_POST['showhide'][$index] as $showindex => $showhide)
					{
						if($showhide)
							$pricehide[$index][] = $showindex;
					}
			}
		}	
	
	if(!empty($pricehide))
		{
			update_post_meta($postID, '_hiddenrsvpfields', $pricehide);
		}
	
	$value = $per;
	$field = "_per";
	
	$current = get_post_meta($postID, $field, $single); 
	
	if($value && ($current == "") )
		add_post_meta($postID, $field, $value, true);
	
	elseif($value != $current)
		update_post_meta($postID, $field, $value);
	
	elseif($value == "")
		delete_post_meta($postID, $field, $current);
	
	}
	if(!empty($_POST["youtube_live"]) || !empty($_POST["webinar_other"]))
		{
		$ylive = $_POST["youtube_live"];
		unset($_POST);
		rsvpmaker_youtube_live($postID, $ylive);
		}
}

add_action('admin_menu', 'my_events_menu');

add_action('save_post','save_calendar_data');

function rsvpmaker_menu_security($label, $slug,$options) {

echo $label;
?>
 <select name="option[<?php echo $slug; ?>]" id="<?php echo $slug; ?>">
  <option value="manage_options" <?php if(isset($options[$slug]) && ($options[$slug] == 'manage_options')) echo ' selected="selected" ';?> ><?php _e('Administrator','rsvpmaker');?> (manage_options)</option>
  <option value="edit_others_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'edit_others_rsvpmakers')) echo ' selected="selected" ';?><?php _e('Editor','rsvpmaker');?> (edit_others_rsvpmakers)</option>
  <option value="publish_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'publish_rsvpmakers')) echo ' selected="selected" ';?> ><?php _e('Author','rsvpmaker');?> (publish_rsvpmakers)</option>
  <option value="edit_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'edit_rsvpmakers')) echo ' selected="selected" ';?> ><?php _e('Contributor','rsvpmaker');?> (edit_rsvpmakers)</option>
  </select><br />
<?php
}

  
  // Avoid name collisions.
  if (!class_exists('RSVPMAKER_Options'))
      : class RSVPMAKER_Options
      {
          // this variable will hold url to the plugin  
          var $plugin_url;
          
          // name for our options in the DB
          var $db_option = 'RSVPMAKER_Options';
          
          // Initialize the plugin
          function RSVPMAKER_Options()
          {
              $this->plugin_url = plugins_url('',__FILE__).'/';

              // add options Page
              add_action('admin_menu', array(&$this, 'admin_menu'));
              
          }
          
          // hook the options page
          function admin_menu()
          {
              add_options_page('RSVPMaker', 'RSVPMaker', 'manage_options', basename(__FILE__), array(&$this, 'handle_options'));
          }
          
          
          // handle plugin options
          function get_options()
          {
              global $rsvp_options;
              return $rsvp_options;
          }
          
          // Set up everything
          function install()
          {
              // set default options
              $this->get_options();
          }
          
          // handle the options page
          function handle_options()
          {
              $options = $this->get_options();
              
              if (isset($_POST['submitted'])) {
              		
              		//check security
              		check_admin_referer('calendar-nonce');
              		
                  $newoptions = stripslashes_deep($_POST["option"]);
                  $newoptions["rsvp_on"] = (isset($_POST["option"]["rsvp_on"]) && $_POST["option"]["rsvp_on"]) ? 1 : 0;
                  $newoptions["confirmation_include_event"] = (isset($_POST["option"]["confirmation_include_event"]) && $_POST["option"]["confirmation_include_event"]) ? 1 : 0;
                  $newoptions["login_required"] = (isset($_POST["option"]["login_required"]) && $_POST["option"]["login_required"]) ? 1 : 0;
                  $newoptions["rsvp_captcha"] = (isset($_POST["option"]["rsvp_captcha"]) && $_POST["option"]["rsvp_captcha"]) ? 1 : 0;
                  $newoptions["rsvp_yesno"] = (isset($_POST["option"]["rsvp_yesno"]) && $_POST["option"]["rsvp_yesno"]) ? 1 : 0;
                  $newoptions["calendar_icons"] = (isset($_POST["option"]["calendar_icons"]) && $_POST["option"]["calendar_icons"]) ? 1 : 0;
                  $newoptions["convert_timezone"] = (isset($_POST["option"]["convert_timezone"]) && $_POST["option"]["convert_timezone"]) ? 1 : 0;
                  $newoptions["social_title_date"] = (isset($_POST["option"]["social_title_date"]) && $_POST["option"]["social_title_date"]) ? 1 : 0;
                  $newoptions["rsvp_count"] = (isset($_POST["option"]["rsvp_count"]) && $_POST["option"]["rsvp_count"]) ? 1 : 0;
                  $newoptions["show_attendees"] = (isset($_POST["option"]["show_attendees"]) && $_POST["option"]["show_attendees"]) ? 1 : 0;
                  $newoptions["missing_members"] = (isset($_POST["option"]["missing_members"]) && $_POST["option"]["missing_members"]) ? 1 : 0;
                  $newoptions["additional_editors"] = (isset($_POST["option"]["additional_editors"]) && $_POST["option"]["additional_editors"]) ? 1 : 0;
				  $newoptions["dbversion"] = $options["dbversion"]; // gets set by db upgrade routine
				$nfparts = explode('|',$_POST["currency_format"]);
				$newoptions["eventpage"] = $_POST["option"]["eventpage"];
				$newoptions["currency_decimal"] = $nfparts[0];
				$newoptions["currency_thousands"] = $nfparts[1];
				
				  $options = $newoptions;
				  
                  update_option($this->db_option, $options);
                  
                  echo '<div class="updated fade"><p>Plugin settings saved.</p></div>';
              }
              
              // URL for form submit, equals our current page
              $action_url = admin_url('options-general.php?page=rsvpmaker-admin.php');

$defaulthour = (isset($options["defaulthour"])) ? ( (int) $options["defaulthour"]) : 19;
$defaultmin = (isset($options["defaultmin"])) ? ( (int) $options["defaultmin"]) : 0;
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

for($i=0; $i < 60; $i += 5)
	{
	$selected = ($i == $defaultmin) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	$minopt .= sprintf('<option  value="%s" %s>%s</option>',$padded,$selected,$padded);
	}

if(isset($_GET["test"]))
	print_r($options);

if(isset($_GET["reminder_reset"]))
	rsvp_reminder_reset($_GET["reminder_reset"]);

?>

<div class="wrap" style="max-width:950px !important;">

    <h2 class="nav-tab-wrapper">
      <a class="nav-tab nav-tab-active" href="#calendar">Calendar Settings</a>
      <a class="nav-tab" href="#email">Email List</a>
    </h2>

    <div id='sections'>
    <section id="calendar">

<div style="float: right;">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="N6ZRF6V6H39Q8">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>

	<h2><?php _e('Calendar Options','rsvpmaker');?></h2>
    
    <?php
if(file_exists(WP_PLUGIN_DIR."/rsvpmaker-custom.php") )
	echo "<p><em>".__('Note: This site also implements custom code in','rsvpmaker').' '.WP_PLUGIN_DIR."/rsvpmaker-custom.php.</em></p>";
	?>
    
	<div id="poststuff" style="margin-top:10px;">

	 <div id="mainblock" style="width:710px">
	 
		<div class="dbx-content">
		 	<form name="caldendar_options" action="<?php echo $action_url ;?>" method="post">
					
                    <input type="hidden" name="submitted" value="1" /> 
					<?php wp_nonce_field('calendar-nonce');?>

					<h3><?php _e('Default Content for Events (such as standard meeting location)','rsvpmaker'); ?>:</h3>
  <textarea name="option[default_content]"  rows="5" cols="80" id="default_content"><?php if(isset($options["default_content"])) echo $options["default_content"];?></textarea>
	<br />
<?php _e('Hour','rsvpmaker'); ?>: <select name="option[defaulthour]"> 
<?php echo $houropt;?>
</select> 
 
<?php _e('Minutes','rsvpmaker'); ?>: <select name="option[defaultmin]"> 
<?php echo $minopt;?>
</select>
<br />
<?php echo __('See also','rsvpmaker') . ' <a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list').'">'.__('Event Templates','rsvpmaker').'</a> '.__('for events held an a recurring schedule.','rsvpmaker'); ?><br />
<strong><?php _e('RSVP TO','rsvpmaker'); ?>:</strong><br />
<input type="radio" name="option[rsvp_to_current]" value="0" <?php if(!isset($options["rsvp_to_current"]) || ! $options["rsvp_to_current"] ) echo ' checked="checked" ';?> /> <strong><?php _e('Use this address','rsvpmaker'); ?></strong>: 
<input type="text" name="option[rsvp_to]" id="rsvp_to" value="<?php if(isset($options["rsvp_to"])) echo $options["rsvp_to"];?>" /><br />
<input type="radio" name="option[rsvp_to_current]" value="1" <?php if(isset($options["rsvp_to_current"]) && $options["rsvp_to_current"]) echo ' checked="checked" ';?> /> <strong><?php _e('Use email of current user (event author)','rsvpmaker'); ?></strong>
<br />
<br />
<input type="checkbox" name="option[rsvp_on]" value="1" <?php if(isset($options["rsvp_on"]) && $options["rsvp_on"]) echo ' checked="checked" ';?> /> <strong><?php _e('RSVP On','rsvpmaker'); ?></strong>
<?php _e('check to turn on by default','rsvpmaker'); ?>	<br />    

<input type="checkbox" name="option[rsvp_captcha]" value="1" <?php if(isset($options["rsvp_captcha"]) && $options["rsvp_captcha"]) echo ' checked="checked" ';?> /> <strong><?php _e('RSVP CAPTCHA On','rsvpmaker'); ?></strong> <?php _e('check to turn on by default','rsvpmaker'); ?><br />

<input type="checkbox" name="option[login_required]" value="1" <?php if(isset($options["login_required"]) && $options["login_required"]) echo ' checked="checked" ';?> /> <strong><?php _e('Login Required to RSVP','rsvpmaker'); ?></strong> <?php _e('check to turn on by default','rsvpmaker'); ?>
<br />

  <input type="checkbox" name="option[show_attendees]" value="1" <?php if(isset($options["show_attendees"]) && $options["show_attendees"]) echo ' checked="checked" ';?> /> <strong><?php _e('RSVPs Attendees List Public','rsvpmaker'); ?></strong> <?php _e('check to turn on by default','rsvpmaker'); ?>
	<br />

  <input type="checkbox" name="option[rsvp_count]" value="1" <?php if(isset($options["rsvp_count"]) && $options["rsvp_count"]) echo ' checked="checked" ';?> /> <strong><?php _e('Show RSVP Count','rsvpmaker'); ?></strong> <?php _e('check to turn on by default','rsvpmaker'); ?>
	<br />

  <input type="checkbox" name="option[rsvp_yesno]" value="1" <?php if(isset($options["rsvp_yesno"]) && $options["rsvp_yesno"]) echo ' checked="checked" ';?> /> <strong><?php _e('Show RSVP Yes/No Radio Buttons','rsvpmaker'); ?></strong> <?php _e('check to turn on by default','rsvpmaker'); ?>
	<br />

  <input type="checkbox" name="option[calendar_icons]" value="1" <?php if(isset($options["calendar_icons"]) && $options["calendar_icons"]) echo ' checked="checked" ';?> /> <strong><?php _e('Show Add to Google / Download to Outlook (iCal) icons','rsvpmaker'); ?></strong> 
	<br />

  <input type="checkbox" name="option[convert_timezone]" value="1" <?php if(isset($options["convert_timezone"]) && $options["convert_timezone"]) echo ' checked="checked" ';?> /> <strong><?php _e('Show timezone conversion button next to calendar icons','rsvpmaker'); ?></strong> 
	<br />

  <input type="checkbox" name="option[social_title_date]" value="1" <?php if(isset($options["social_title_date"]) && $options["social_title_date"]) echo ' checked="checked" ';?> /> <strong><?php _e('Include date with title shown on Facebook/Twitter previews (og:title and twitter:title metatags)','rsvpmaker'); ?></strong> 
	<br />

  <input type="checkbox" name="option[missing_members]" value="1" <?php if(isset($options["missing_members"]) && $options["missing_members"]) echo ' checked="checked" ';?> /> <strong><?php _e('RSVP Form Shows Members Not Responding','rsvpmaker'); ?></strong><br /><em><?php _e('if members log in to RSVP, this shows user accounts NOT associated with an RSVP (tracking WordPress user IDs)','rsvpmaker'); ?>.</em>
	<br />

					<h3><?php _e('Instructions for Form','rsvpmaker'); ?>:</h3>
  <textarea name="option[rsvp_instructions]"  rows="5" cols="80" id="rsvp_instructions"><?php if(isset($options["rsvp_instructions"]) ) echo $options["rsvp_instructions"];?></textarea>
	<br />
					<h3><?php _e('Confirmation Message','rsvpmaker'); ?>:</h3>
  <textarea name="option[rsvp_confirm]"  rows="5" cols="80" id="rsvp_confirm"><?php if( isset($options["rsvp_confirm"]) ) echo $options["rsvp_confirm"];?></textarea><br />
  <input type="checkbox" name="option[confirmation_include_event]" id="rsvp_confirmation_include_event" <?php if( isset($options["confirmation_include_event"]) && $options["confirmation_include_event"] ) echo ' checked="checked" ' ?> > <?php _e('Include event listing with confirmation and reminders','rsvpmaker'); ?>
	<br />
					<h3><?php _e('RSVP Form','rsvpmaker'); ?> (<a href="#" id="enlarge"><?php _e('Enlarge','rsvpmaker'); ?></a>):</h3>
  <textarea name="option[rsvp_form]"  rows="5" cols="80" id="rsvpform"><?php if( isset($options["rsvp_form"]) ) echo htmlentities($options["rsvp_form"]);?></textarea>
  
<?php rsvp_form_setup_form($options["rsvp_form"]); ?>
  
<br /><button id="create-form">Generate Form</button> or <a href="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php&reset_form=1'); ?>"><?php _e('Reset to default','rsvpmaker'); ?></a>
<br /><?php _e("This is a customizable template for the RSVP form, introduced as part of the Aug. 2012 update. With the exception of the yes/no radio buttons and the notes textarea, fields are represented by the shortcodes [rsvpfield textfield=&quot;fieldname&quot;] or [rsvpfield selectfield=&quot;fieldname&quot; options=&quot;option1,option2&quot;]. There is also a [rsvpprofiletable show_if_empty=&quot;phone&quot;] shortcode which is an optional block that will not be displayed if the required details (such as a phone number) are already &quot;on file&quot; from a prior RSVP. For this to work, there must also be a [/rsvpprofiletable] closing tag. The guest section of the form is represented by [rsvpguests] (no parameters). If you don't want the guest blanks to show up, you can remove this. The form code you supply will be wrapped in a form tag with the CSS ID of",'rsvpmaker'); ?> &quot;rsvpform&quot;.
<script>
jQuery('#enlarge').click(function() {
  jQuery('#rsvpform').attr('rows','40');
  return false;
});
</script>
	<br />
					<h3><?php _e('RSVP Link','rsvpmaker'); ?>:</h3>
  <textarea name="option[rsvplink]"  rows="5" cols="80" id="rsvplink"><?php if(isset($options["rsvplink"]) ) echo $options["rsvplink"];?></textarea>
	<br />
					<h3><?php _e('Date Format (long)','rsvpmaker'); ?>:</h3>
  <input type="text" name="option[long_date]"  id="long_date" value="<?php if(isset($options["long_date"]) ) echo $options["long_date"];?>" /> (used in event display, PHP <a target="_blank" href="http://php.net/manual/en/function.strftime.php">date format string</a>)
	<br />
					<h3><?php _e('Date Format (short)','rsvpmaker'); ?>:</h3>
  <input type="text" name="option[short_date]"  id="short_date" value="<?php if(isset($options["short_date"]) ) echo $options["short_date"];?>" /> (used in headlines for event_listing shortcode)
	<br />
<h3><?php _e('Time Format','rsvpmaker'); ?>:</h3>
<p>
<input type="radio" name="option[time_format]" value="%l:%M %p" <?php if( isset($options["time_format"]) && ($options["time_format"] == "%l:%M %p")) echo ' checked="checked"';?> /> 12 hour AM/PM 
<input type="radio" name="option[time_format]" value="%H:%M" <?php if( isset($options["time_format"]) && ($options["time_format"] == "%H:%M")) echo ' checked="checked"';?> /> 24 hour 

<input type="radio" name="option[time_format]" value="%l:%M %p %Z" <?php if( isset($options["time_format"]) && ($options["time_format"] == "%l:%M %p %Z")) echo ' checked="checked"';?> /> 12 hour AM/PM (include timezone)
<input type="radio" name="option[time_format]" value="%H:%M %Z" <?php if( isset($options["time_format"]) && ($options["time_format"] == "%H:%M %Z")) echo ' checked="checked"';?> /> 24 hour (include timezone)

<br />
					<h3><?php _e('Event Page','rsvpmaker'); ?>:</h3>
  <input type="text" name="option[eventpage]" value="<?php if(isset($options["eventpage"]))  echo $options["eventpage"];?>" size="80" />

<br /><h3><?php _e('Custom CSS','rsvpmaker'); ?>:</h3>
  <input type="text" name="option[custom_css]" value="<?php if(isset($options["custom_css"]) ) echo $options["custom_css"];?>" size="80" />
<?php
if(isset($options["custom_css"]) && $options["custom_css"])
	{

		$file_headers = @get_headers($options["custom_css"]);
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
			echo ' <span style="color: red;">'.__('Error: CSS not found','rsvpmaker').'</span>';
		}
		else {
			echo ' <span style="color: green;">'.__('OK','rsvpmaker').'</span>';
		}

	}
$dstyle = plugins_url('/style.css',__FILE__);
?>

    <br /><em><?php _e('Allows you to override the standard styles from','rsvpmaker'); ?> <br /><a href="<?php echo $dstyle;?>"><?php echo $dstyle;?></a></em>
<h3><?php _e('Theme Template for Events'); ?></h3>
<br /><select name="option[rsvp_template]"><?php
$current_template = (empty($options["rsvp_template"])) ? 'page.php' : $options["rsvp_template"];
$templates = get_page_templates();
$templates['Page'] = 'page.php';
$templates['Single'] = 'single.php';
foreach($templates as $tname => $tfile)
	{
	$s = ($tfile == $current_template) ? ' selected="selected" ' : '';
	printf('<option value="%s" %s>%s</option>',$tfile,$s,$tname);
	}
?></select> <br /><em><?php _e('Template from your theme to be used in the absence of a single-rsvpmaker.php file.','rsvpmaker'); ?></em>

<br />					<h3><?php _e('PayPal Configuration File','rsvpmaker'); ?>:</h3>
  <input type="text" name="option[paypal_config]" id="paypal_config" value="<?php if(isset($options["paypal_config"]) ) echo $options["paypal_config"];?>" size="80" /><button id="paypal_setup"><?php _e('PayPal Setup','rsvpmaker'); ?></button>
<?php
if( !empty($options["paypal_config"]) )
{
$config = $options["paypal_config"];

if(isset($config) && file_exists($config) )
	echo ' <span style="color: green;">'.__('OK','rsvpmaker').'</span>';
else
	echo ' <span style="color: red;">'.__('error: file not found','rsvpmaker').'</span>';
}
?>	
    <br /><em><?php _e('The PayPal setup button will help you create a configuration file containing your API credentials. See documentation.','rsvpmaker'); echo ': <a href="http://rsvpmaker.com/blog/category/paypal/">http://rsvpmaker.com/blog/category/paypal/</a>'; ?>
</em>
<div id="pp-dialog-form">
<?php _e('User','rsvpmaker');?>:<br /><input type="text" id="pp_user" name="user">
<br /><?php _e('Password','rsvpmaker')?>:<br /><input type="text" id="pp_password" name="password">
<br /><?php _e('Signature','rsvpmaker');?>:<br /><input type="text" id="pp_signature" name="signature">
</div>

<br /><h3><?php _e('Track RSVP as &quot;invoice&quot; number','rsvpmaker'); ?>:</h3>
<br />
<input type="radio" name="option[paypal_invoiceno]" value ="1" <?php if($options["paypal_invoiceno"]) echo ' checked="checked" ' ?> /> Yes
<input type="radio" name="option[paypal_invoiceno]" value ="0" <?php if(!$options["paypal_invoiceno"]) echo ' checked="checked" ' ?> /> No
<br /><em>Must be enabled for RSVPMaker to track payments</em>
<br /><h3><?php _e('Payment Currency','rsvpmaker'); ?>:</h3>
<input type="text" name="option[paypal_currency]" value="<?php if(isset($options["paypal_currency"])) echo $options["paypal_currency"];?>" size="5" /> <a href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes">(list of codes)</a>

<select name="currency_format">
<option value="<?php if(isset($options["currency_decimal"]) ) echo $options["currency_decimal"];?>|<?php if(isset($options["currency_thousands"])) echo $options["currency_thousands"];?>"><?php echo number_format(1000.00, 2, $options["currency_decimal"],  $options["currency_thousands"]); ?></option>
<option value=".|,"><?php echo number_format(1000.00, 2, '.',  ','); ?></option>
<option value=",|."><?php echo number_format(1000.00, 2, ',',  '.'); ?></option>
<option value=",| "><?php echo number_format(1000.00, 2, ',',  ' '); ?></option>
</select>    
<br />

<?php
if (class_exists('Stripe_Checkout_Functions'))
	{
	$s = (!empty($options["stripe"])) ? 'checked="checked"' : '';
	echo '<h3>'.__('WP Simple Pay Lite for Stripe plugin detected','rsvpmaker').'</h3><p><input type="checkbox" name="option[stripe]" value="1" '.$s.' /> '.__('Use Stripe instead of PayPal','rsvpmaker').'</p>';
	}
else
	echo '<h3>'.__('WP Simple Pay Lite for Stripe','rsvpmaker').'</h3><p>'.__('To use Stripe instead of PayPal, enable the <a href="https://wordpress.org/plugins/stripe/" target="_blank">WP Simple Pay Lite for Stripe plugin</a>','rsvpmaker').'</p>';
?>

<h3><?php _e('Menu Security','rsvpmaker'); ?>:</h3>
<?php
rsvpmaker_menu_security( __("RSVP Report",'rsvpmaker'),  "menu_security", $options );
rsvpmaker_menu_security(__("Event Templates",'rsvpmaker'),"rsvpmaker_template",$options );
rsvpmaker_menu_security( __("Recurring Event",'rsvpmaker'), "recurring_event", $options );
rsvpmaker_menu_security( __("Multiple Events",'rsvpmaker'), "multiple_events",$options );
rsvpmaker_menu_security( __("Documentation",'rsvpmaker'), "documentation",$options );
?>
<p><em><?php _e('Security level required to access custom menus (RSVP Report, Documentation)','rsvpmaker'); ?></em></p>

<h3><?php _e('Dashboard','rsvpmaker');?></h3>
<select name="option[dashboard]">
<option value=""><?php _e('No Widget','rsvpmaker');?></option>
<option value="show" <?php if(isset($options["dashboard"]) && ($options["dashboard"] == 'show')) echo ' selected="selected" '; ?> ><?php _e('Show Widget','rsvpmaker');?></option>
<option value="top" <?php if(isset($options["dashboard"]) && ($options["dashboard"] == 'top')) echo ' selected="selected" '; ?> ><?php _e('Show Widget on Top','rsvpmaker');?></option>
</select>
<br /><?php _e('Note','rsvpmaker'); ?>
<br />
<textarea name="option[dashboard_message]" style="width:90%;"><?php echo $options["dashboard_message"]; ?></textarea>

<h3 id="smtp"><?php _e('SMTP for Notifications','rsvpmaker'); ?></h3>
<p><?php _e('For more reliable delivery of email notifications, enable delivery through the SMTP email protocol. Standard server parameters will be used for Gmail and the SendGrid service, or specify the server port number and security protocol','rsvpmaker'); ?>.</p>
<p><?php _e('If you are using another plugin that improves the delivery of email notifications, such one of the <a href="https://wordpress.org/plugins/sendgrid-email-delivery-simplified/">SendGrid plugin</a> (which uses the SendGrid API rather than SMTP), leave this set to "None - use wp_mail()."','rsvpmaker'); ?>.</p>
  <select name="option[smtp]" id="smtp">
  <option value="" <?php if(isset($options["smtp"]) && ($options["smtp"] == '' )) {echo ' selected="selected" ';}?> ><?php _e('None - use wp_mail()','rsvpmaker'); ?></option>
  <option value="gmail" <?php if(isset($options["smtp"]) && ($options["smtp"] == 'gmail')) {echo ' selected="selected" ';}?> >Gmail</option>
  <option value="sendgrid" <?php if(isset($options["smtp"]) && ($options["smtp"] == 'sendgrid')) {echo ' selected="selected" ';}?> >SendGrid (SMTP)</option>
  <option value="other" <?php if(isset($options["smtp"]) && ($options["smtp"] == 'other')) {echo ' selected="selected" ';}?> ><?php _e('Other SMTP (specified below)','rsvpmaker'); ?></option>
  </select>
<br />
<?php _e('Email Account for Notifications','rsvpmaker'); ?>
<br />
<input type="text" name="option[smtp_useremail]" value="<?php if(isset($options["smtp_useremail"])) echo $options["smtp_useremail"];?>" size="15" />
<br />
<?php _e('Email Username','rsvpmaker'); ?>
<br />
<input type="text" name="option[smtp_username]" value="<?php if(isset($options["smtp_username"])) echo $options["smtp_username"];?>" size="15" />
<br />
<?php _e('Email Password','rsvpmaker'); ?>
<br />
<input type="text" name="option[smtp_password]" value="<?php if(isset($options["smtp_password"])) echo $options["smtp_password"];?>" size="15" />
<br />
<?php _e('Server (parameters below not necessary if you specified Gmail or SendGrid)','rsvpmaker'); ?><br />
<input type="text" name="option[smtp_server]" value="<?php if(isset($options["smtp_server"])) echo $options["smtp_server"];?>" size="15" />
<br />
<?php _e('SMTP Security Prefix (ssl or tls, leave blank for non-encrypted connections)','rsvpmaker'); ?> 
<br />
<input type="text" name="option[smtp_prefix]" value="<?php if(isset($options["smtp_prefix"])) echo $options["smtp_prefix"];?>" size="15" />
<br />
<?php _e('SMTP Port','rsvpmaker'); ?>
<br />
<input type="text" name="option[smtp_port]" value="<?php if(isset($options["smtp_port"])) echo $options["smtp_port"];?>" size="15" />
<br />

<p><?php _e('See <a href="http://www.wpsitecare.com/gmail-smtp-settings/">this article</a> for additional guidance on using Gmail (requires a tweak to security settings in your Google account). If you have trouble getting Gmail or ssl or tls connections to work, an unencrypted port 25 connection to an email account on the same server that hosts your website should be reasonably secure since no data will be passed over the network.','rsvpmaker');?></p>

<?php 
if(!empty($options["smtp"]))
	{
?>
<a href="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php&smtptest=1'); ?>"><?php _e('Send SMTP Test to RSVP To address','rsvpmaker'); ?></a>
<?php
	}

?>
<h3><?php _e('Event Templates','rsvpmaker'); ?></h3>
  <input type="checkbox" name="option[additional_editors]" value="1" <?php if(isset($options["additional_editors"]) && $options["additional_editors"]) echo ' checked="checked" ';?> /> <strong><?php _e('Additional Editors','rsvpmaker'); ?></strong> <em><?php _e('Allow users to share editing rights for event templates and related events.','rsvpmaker'); ?></em> 
	<br />

<h3><?php _e('Troubleshooting','rsvpmaker'); ?></h3>
  <input type="checkbox" name="option[flush]" value="1" <?php if(isset($options["flush"]) && $options["flush"]) echo ' checked="checked" ';?> /> <strong><?php _e('Tweak Permalinks','rsvpmaker'); ?></strong> <?php _e('Check here if you are getting &quot;page not found&quot; errors for event content (should not be necessary for most users).','rsvpmaker'); ?> 
	<br />
  <input type="checkbox" name="option[debug]" value="1" <?php if(isset($options["debug"]) && $options["debug"]) echo ' checked="checked" ';?> /> <strong><?php _e('Debug','rsvpmaker'); ?>:</strong>
	<br />

					<div class="submit"><input type="submit" name="Submit" value="<?php _e('Update','rsvpmaker'); ?>" /></div>
			</form>

<form action="<?php echo admin_url('options-general.php'); ?>" method="get"><input type="hidden" name="page" value="rsvpmaker-admin.php" /><?php _e('RSVP Reminders scheduled for','rsvpmaker'); ?>: <?php echo date('F jS, g:i A / H:i',wp_next_scheduled( 'rsvp_daily_reminder_event' )).' GMT offset '.get_option('gmt_offset').' hours'; // ?><br />
<?php _e('Set new time','rsvpmaker'); ?>: <select name="reminder_reset">
<?php echo $houropt;?>
</select><input type="submit" name="submit" value="<?php _e('Set','rsvpmaker'); ?>" /></form>

	    </div>
		
	 </div>

	</div>

</section>
    <section id="email">

<?php
global $RSVPMaker_Email_Options;
$RSVPMaker_Email_Options->handle_options();
?>

    </section>
</sections>

</div>

<?php              

          }
      }
  
  else
      : exit("Class already declared!");
  endif;
  

  // create new instance of the class
  $RSVPMAKER_Options = new RSVPMAKER_Options();
  //print_r($RSVPMAKER_Options);
  if (isset($RSVPMAKER_Options)) {
      // register the activation function by passing the reference to our instance
      register_activation_hook(__FILE__, array(&$RSVPMAKER_Options, 'install'));
  }

add_action('init','save_rsvp');


function admin_event_listing() {
global $wpdb;

$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates' 
WHERE meta_value > CURDATE( ) AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value";
$listings = '';
if(empty($_GET["events"]) || ($_GET["events"] != 'all') )
	$sql .= " LIMIT 0, 20";

$results = $wpdb->get_results($sql,ARRAY_A);
if($results)
foreach($results as $row)
	{
	$t = strtotime($row["datetime"]);
	if(empty($dateline[$row["postID"]])) $dateline[$row["postID"]] = '';
	$dateline[$row["postID"]] .= date('F jS',$t)." ";
	if(empty($eventlist[$row["postID"]]))
		$eventlist[$row["postID"]] = $row;
	}

if(!empty($eventlist))
foreach($eventlist as $event)
	{
		$listings .= sprintf('<li><a href="'.admin_url().'post.php?post=%d&action=edit">%s</a> %s</li>'."\n",$event["postID"],$event["post_title"],$dateline[$event["postID"]]);
	}	

	$listings = "<p><strong>".__('Events (click to edit)','rsvpmaker')."</strong></p>\n<ul id=\"eventheadlines\">\n$listings</ul>\n".'<p><a href="?events=all">'.__('Show All','rsvpmaker').'</a></p>';
	return $listings;
}

function default_event_content($content) {
global $post;
global $rsvp_options;
global $rsvp_template;
if(empty($post->post_type))
	return $content;
if(($post->post_type == 'rsvpmaker') && ($content == ''))
{
if(isset($rsvp_template->post_content))
	return $rsvp_template->post_content;
return $rsvp_options['default_content'];
}
else
return $content;
}

function title_from_template($title) {
global $rsvp_template;
global $post;
global $wpdb;
if(isset($_GET["from_template"]) ) 
	{
	$t = (int) $_GET["from_template"];
	$sql = "SELECT post_title, post_content FROM $wpdb->posts WHERE ID=$t";
	$rsvp_template = $wpdb->get_row($sql);
	return $rsvp_template->post_title;
	}
return $title;
}

add_filter('the_editor_content','default_event_content');
add_filter('default_title','title_from_template');


function multiple() {

global $wpdb;
global $current_user;

if(isset($_POST))
{

	$my_post['post_status'] = current_user_can('publish_rsvpmakers') ? 'publish' : 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';
	if(!empty($_POST["recur_year"]))
	foreach($_POST["recur_year"] as $index => $year)
		{
		if($_POST["recur_day"][$index] )
			{
			$my_post['post_title'] = $_POST["title"][$index];
			$my_post['post_content'] = $_POST["body"][$index];
			$cddate = format_cddate($year, $_POST["recur_month"][$index], $_POST["recur_day"][$index], $_POST["recur_hour"][$index], $_POST["recur_minutes"][$index]);// Insert the post into the database
  			if($postID = wp_insert_post( $my_post ) )
				{
				add_post_meta($postID,'_rsvp_dates',$cddate);
				echo '<div class="updated">'."Added post # $postID for $cddate.</div>\n";	
				}
			}		
		}
}

global $rsvp_options;

;?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div> 
<h2><?php _e('Multiple Events','rsvpmaker'); ?></h2> 

<p><?php _e('Use this form to enter multiple events quickly with basic formatting','rsvpmaker'); ?>.</p>

<form id="form1" name="form1" method="post" action="<?php echo admin_url('edit.php?post_type=rsvpmaker&page=multiple');?>">
<?php
$today = '<option value="0">None</option>';
for($i=0; $i < 10; $i++)
{

$m = date('n');
$y = date('Y');
$y2 = $y+1;

wp_nonce_field(-1,'add_date'.$i);
?>
<p><?php _e('Title','rsvpmaker'); ?>: <input type="text" name="title[<?php echo $i;?>]" /></p>
<p><textarea name="body[<?php echo $i;?>]" rows="5" cols="80"><?php echo $rsvp_options["default_content"];?></textarea></p>

<div id="recur_date<?php echo $i;?>" style="border-bottom: thin solid #888;">

<?php _e('Month','rsvpmaker'); ?>: 
              <select name="recur_month[<?php echo $i;?>]"> 
              <option value="<?php echo $m;?>"><?php echo $m;?></option> 
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
              <?php echo $today;?> 
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

<?php _e('Hour','rsvpmaker'); ?>: <select name="recur_hour[<?php echo $i;?>]"> 
 
<option  value="00">12 a.m.</option> 
<option  value="1">1 a.m.</option> 
<option  value="2">2 a.m.</option> 
<option  value="3">3 a.m.</option> 
<option  value="4">4 a.m.</option> 
<option  value="5">5 a.m.</option> 
<option  value="6">6 a.m.</option> 
<option  value="7">7 a.m.</option> 
<option  value="8">8 a.m.</option> 
<option  value="9">9 a.m.</option> 
<option  value="10">10 a.m.</option> 
<option  value="11">11 a.m.</option> 
<option  value="12">12 p.m.</option> 
<option  value="13">1 p.m.</option> 
<option  value="14">2 p.m.</option> 
<option  value="15">3 p.m.</option> 
<option  value="16">4 p.m.</option> 
<option  value="17">5 p.m.</option> 
<option  value="18">6 p.m.</option> 
<option  selected = "selected"  value="19">7 p.m.</option> 
<option  value="20">8 p.m.</option> 
<option  value="21">9 p.m.</option> 
<option  value="22">10 p.m.</option> 
<option  value="23">11 p.m.</option></select> 
 
<?php _e('Minutes','rsvpmaker'); ?>: <select name="recur_minutes[<?php echo $i;?>]"> 
<option value="00">00</option> 
<option value="00">00</option> 
<option value="15">15</option> 
<option value="30">30</option> 
<option value="45">45</option> 
</select>

</div>
<?php
} // end for loop
;?>

<input type="submit" name="button" id="button" value="<?php _e('Submit','rsvpmaker'); ?>" />
</form>
</div>
<?php
}



function add_dates() {

global $wpdb;
global $current_user;

if(isset($_POST))
{

if(empty($_POST['add_recur']) || !wp_verify_nonce($_POST['add_recur'],'recur'))
	die("Security error");

if(!empty($_POST["recur-title"]))
	{
	$my_post['post_title'] = $_POST["recur-title"];
	$my_post['post_content'] = $_POST["recur-body"];
	$my_post['post_status'] = current_user_can('publish_rsvpmakers') ? 'publish' : 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';

	foreach($_POST["recur_checked"] as $index => $on)
		{
		$year = $_POST["recur_year"][$index];
		if(!empty($_POST["recur_day"][$index]) )
			{
			$cddate = format_cddate($year,$_POST["recur_month"][$index], $_POST["recur_day"][$index], $_POST["event_hour"], $_POST["event_minutes"]);

			$dpart = explode(':',$_POST["event_duration"]);			
			
			if( is_numeric($dpart[0]) )
				{
				$dtext = $cddate.' +'.$dpart[0].' hours';
				if(!empty($dpart[1]))
					$dtext .= ' +'.$dpart[1].' minutes';
				$dt = strtotime($dtext);
				$duration = date('Y-m-d H:i:s',$dt);
				//printf('<p>%s %s</p>',$dtext,$duration);
				}
			else
				$duration = $_POST["event_duration"]; // empty or all day

// Insert the post into the database
  			if($postID = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($postID,$cddate,$duration);
				echo '<div class="updated">Posted: event for '.$cddate.' <a href="post.php?action=edit&post='.$postID.'">Edit</a> / <a href="'.site_url().'/?p='.$postID.'">View</a></div>';	

				if(!empty($_POST["setrsvp"]["on"]))
					save_rsvp_meta($postID);

				}
			}		
		}

	}

}

global $rsvp_options;

;?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div> 
<h2>Recurring Event</h2> 

<?php

$defaulthour = (isset($_GET["hour"])) ? ( (int) $_GET["hour"]) : 19;
$defaultmin = (isset($_GET["minutes"])) ? ( (int) $_GET["minutes"]) : 0;
$houropt = $minopt = '';
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

for($i=0; $i < 60; $i += 5)
	{
	$selected = ($i == $defaultmin) ? ' selected="selected" ' : '';
	$padded = ($i < 10) ? '0'.$i : $i;
	$minopt .= sprintf('<option  value="%s" %s>%s</option>',$padded,$selected,$padded);
	}

$cm = date('n');
$y = date('Y');
$y2 = $y+1;

if(!isset($_GET["week"]))
{
;?>

<p><?php _e('Use this form to create multiple events with the same headline, description, and RSVP paramaters. You can have the program automatically calculate dates for a regular montly schedule.','rsvpmaker');?></p>

<p><em><?php _e('Optional: Calculate dates for a recurring schedule','rsvpmaker');?> ...</em></p>

<form method="get" action="<?php echo admin_url("edit.php");?>" id="recursked">

<p><?php _e('Regular schedule','rsvpmaker');?>: 

<select name="week" id="week">

<option value="+0 week"><?php _e('First','rsvpmaker');?></option> 
<option value="+1 week"><?php _e('Second','rsvpmaker');?></option> 
<option value="+2 week"><?php _e('Third','rsvpmaker');?></option> 
<option value="+3 week"><?php _e('Fourth','rsvpmaker');?></option> 
<option value="Last"><?php _e('Last','rsvpmaker');?></option> 
</select>

<select name="dayofweek" id="dayofweek">

<option value="Sunday"><?php _e('Sunday','rsvpmaker');?></option> 
<option value="Monday"><?php _e('Monday','rsvpmaker');?></option> 
<option value="Tuesday"><?php _e('Tuesday','rsvpmaker');?></option> 
<option value="Wednesday"><?php _e('Wednesday','rsvpmaker');?></option> 
<option value="Thursday"><?php _e('Thursday','rsvpmaker');?></option> 
<option value="Friday"><?php _e('Friday','rsvpmaker');?></option> 
<option value="Saturday"><?php _e('Saturday','rsvpmaker');?></option> 
</select>

</p>
        <table border="0">

<tr><td><?php _e('Time','rsvpmaker');?>:</td>
<td><?php _e('Hour','rsvpmaker');?>: <select name="hour" id="hour">
<?php echo $houropt;?>
</select>

<?php _e('Minutes','rsvpmaker');?>: <select id="minutes" name="minutes">
<?php echo $minopt;?>
</select> 

<em><?php _e('For an event starting at 12:30 p.m., you would select 12 p.m. and 30 minutes.','rsvpmaker');?></em>

</td>

          </tr>
</table>

<input type="hidden" name="post_type" value="rsvpmaker" />
<input type="hidden" name="page" value="add_dates" />
<input type="submit" value="Get Dates" />
</form>

<p><em>... <?php _e('or enter dates individually.','rsvpmaker');?></em></p>

<?php
$futuremonths = 12;
for($i =0; $i < $futuremonths; $i++)
	$projected[$i] = mktime(0,0,0,$cm+$i,1); // first day of month
}
else
{
	$week = $_GET["week"];
	$dow = $_GET["dayofweek"];
	$futuremonths = 12;
	for($i =0; $i < $futuremonths; $i++)
		{
		$thisdate = mktime(0,0,0,$cm+$i,1); // first day of month
		$datetext =  "$week $dow ".date("F Y",$thisdate);
		$projected[$i] = strtotime($datetext);
		$datetexts[$i] = $datetext;
		}//end for loop

echo "<p>".__('Loading recurring series of dates for','rsvpmaker'). " $week $dow. ".__("To omit a date in the series, change the day field to &quot;Not Set&quot;",'rsvpmaker')."</p>\n";
}

;?>

<h3><?php _e('Enter Recurring Events','rsvpmaker'); ?></h3>

<form id="form1" name="form1" method="post" action="<?php echo admin_url("edit.php?post_type=rsvpmaker&page=add_dates");?>">
<p>Headline: <input type="text" name="recur-title" size="60" value="<?php if(isset($_POST["recur-title"])) echo stripslashes($_POST["recur-title"]);?>" /></p>
<p><textarea name="recur-body" rows="5" cols="80"><?php echo (isset($_POST["recur-body"]) && $_POST["recur-body"]) ? stripslashes($_POST["recur-body"]) : $rsvp_options["default_content"];?></textarea></p>
<?php
wp_nonce_field('recur','add_recur');

foreach($projected as $i => $ts)
{

$today = date('d',$ts);
$cm = date('n',$ts);
$y = date('Y',$ts);

$y2 = $y+1;

;?>
<div id="recur_date<?php echo $i;?>" style="margin-bottom: 5px;">

<input type="checkbox" name="recur_checked[<?php echo $i;?>]" value="<?php echo $i;?>" />

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
if($week)
	echo sprintf('<option value="%s">%s</option>',$today,$today);
?>
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
} // end for loop

?>
<p><?php echo __('Hour:','rsvpmaker');?> <select name="event_hour"> 
<?php echo $houropt;?>
</select> 
 
<?php echo __('Minutes:','rsvpmaker');?> <select name="event_minutes"> 
<?php echo $minopt;?>
</select> -

<?php echo __('Duration','rsvpmaker');?> <select name="event_duration">
<option value=""><?php echo __('Not set (optional)','rsvpmaker');?></option>
<option value="allday"><?php echo __("All day/don't show time in headline",'rsvpmaker');?></option>
<?php for($h = 1; $h < 24; $h++) { ;?>
<option value="<?php echo $h;?>"><?php echo $h;?> hours</option>
<option value="<?php echo $h;?>:15"><?php echo $h;?>:15</option>
<option value="<?php echo $h;?>:30"><?php echo $h;?>:30</option>
<option value="<?php echo $h;?>:45"><?php echo $h;?>:45</option>
<?php 
}
;?>
</select>
</p>
<?php

echo GetRSVPAdminForm(0);

;?>

<input type="submit" name="button" id="button" value="Submit" />
</form>

</div><!-- wrap -->

<?php
}


function rsvpmaker_doc () {
global $rsvp_options;
?>
<style>
#docpage ul {
margin-left: 10px;
}
#docpage li {
margin-left: 10px;
list-style-type: circle;
}
</style>
<div id="docpage">
<h2>Documentation</h2><p>More detailed documentation at <a href="http://www.rsvpmaker.com/documentation/">http://www.rsvpmaker.com/documentation/</a></p><h3>Shortcodes and Event Listing / Calendar Views</strong></h3><p>RSVPMaker provides the following shortcodes for listing events, listing event headlines, and displaying a calendar with links to events.</p><p><strong>[rsvpmaker_upcoming]</strong> displays the index of upcoming events. If an RSVP is requested, the event includes the RSVP button link to the single post view, which will include your RSVP form. The calendar icon in the WordPress visual editor simplifies adding the rsvpmaker_upcoming code to any page or post.</p><p>[rsvpmaker_upcoming calendar=&quot;1&quot;] displays the calendar, followed by the index of upcoming events.</p><p>Attributes can be added in the format [rsvpmaker_upcoming attribute_name="attribute_value"]<p><ul><li>type="type_name" displays only the events with the matching event type, as set in the editor (example: type="featured") </li><li>no_event="message" message to display if no events are in the database (example="We are workin on scheduling new events. Check back soon")</li><li>one="ID|slug|next" embed a single message, identified by either post ID number, slug, or "next" to display the next upcoming event. (examples one="123" or one="special-event" or one="next")</li><li>limit="posts_per_page" limits the number of posts to display. If not specified, this will be the same as the number of posts displayed on your blog index page. (example: limit="30")</li><li>add_to_query="querystring" adds an arbitrary command to the WordPress query (example: add_to_query="posts_per_page=30&amp;post_status=draft" would retrieve 30 draft posts)</li><li>hideauthor="1" set this to prevent the author displayname from being shown at the bottom of an event post.</li>
</ul>
  
            <div style="background-color: #FFFFFF; padding: 15px; text-align: center;">
            <img src="<?php echo plugins_url('/shortcode.png',__FILE__);?>" width="535" height="412" />
<br /><em><?php _e('Contents for an events page.','rsvpmaker');?></em>
            </div>

<p><strong>[rsvpmaker_calendar]</strong> displays the calendar by itself.</p><p><strong>[rsvpmaker_calendar nav="top"]</strong> displays the calendar with the next / previous month navigation on the top rather than the bottom. By default, navigation is displayed on the bottom.</p><p>Attributes: type="type_name" and add_to_query="querystring" also work with rsvpmaker_calendar.</p><p><strong>[event_listing format=&quot;headlines&quot;]</strong> displays a list of headlines</p><p>[event_listing format=&quot;calendar&quot;] OR [event_listing calendar=&quot;1&quot;] displays the calendar (recommend using [rsvpmaker_calendar] instead)</p><p>Other attributes:</p><ul><li>limit=&quot;posts_per_page&quot; limits the number of posts to display. If not specified, this will be the same as the number of posts displayed on your blog index page. (example: limit=&quot;30&quot;)</li><li>past=&quot;1&quot; will show a listing of past events, most recent first, rather than upcoming events.</li><li>title=&quot;Title Goes Here&quot; Specifies a title to be displayed in bold at the top of the listing.</li></ul>

<h3>To Embed a Single Event</h3>

<p><strong>[rsvpmaker_next]</strong>, displays just the next scheduled event. If the type attribute is set, that becomes the next event of that type. Example: [rsvpmaker_next type="webinar"]. Also, this displays the complete form rather than the RSVP Now! button unless showbutton="1" is set.</p>
<p><strong>[rsvpmaker_one post_id="10"]</strong> displays a single event post with ID 10. Shows the complete form unless the attribute showbutton="1" is set</p>
<p><strong>[rsvpmaker_form post_id="10"]</strong> displays just the form associated with an event (ID 10 in this example. Could be useful for embedding the form in a landing page that describes the event but where you do not want to include the full event content.</p>

<p>The rsvpmaker_one and rsvpmaker_form shortcodes also accept one="10" as equivalent to post_id="10"</p>

<?php _e('<h3>RSVP Form</h3><p>The RSVP from is also now formatted using shortcodes, which you can edit in the RSVP Form section of the Settings screen. You can also vary the form on a per-event basis, which can be handy for capturing an extra field. This is your current default form:</p>','rsvpmaker');?>
<pre>
<?php echo(htmlentities($rsvp_options["rsvp_form"])); ?>
</pre>
<?php _e('<p>Explanation:</p><p>[rsvpfield textfield=&quot;myfield&quot;] outputs a text field coded to capture data for &quot;myfield&quot;</p><p>[rsvpfield textfield=&quot;myfield&quot; required=&quot;1&quot;] treats &quot;myfield&quot; as a required field.</p><p>[rsvpfield selectfield=&quot;phone_type&quot; options=&quot;Work Phone,Mobile Phone,Home Phone&quot;] HTML select field with specified options</p><p>[rsvpfield checkbox=&quot;checkboxtest&quot; value=&quot;1&quot;] Checkbox named checkboxtext with a value of 1 when checked.</p><p>[rsvpfield checkbox=&quot;checkboxtest&quot; value=&quot;1&quot; checked=&quot;1&quot;] Checkbox checked by default.</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot;] When checked, records one of the 4 values for the field &quot;radiotest&quot;</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot; checked=&quot;two&quot;] choice &quot;two&quot; is checked by default</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot; checked=&quot;two&quot; sep=&quot; &quot;] separate choices with a space (by default, each appears on a separate line)</p><p>[rsvpprofiletable show_if_empty=&quot;phone&quot;]CONDITIONAL CONTENT GOES HERE[/rsvpprofiletable] This section only shown if the required field is empty; otherwise displays a message that the info is &quot;on file&quot;. Because RSVPMaker is capable of looking up profile data based on an email address, you may want some data to be hidden for privacy reasons.</p><p>[rsvpguests] Outputs the guest blanks.</p>','rsvpmaker'); ?>

<p><?php _e("If you're having trouble with the form fields not being formatted correctly",'rsvpmaker')?>, <a href="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php&amp;reset_form=1');?>"><?php _e('Reset default RSVP Form','rsvpmaker');?></a></p>

<h3>YouTube Live webinars</h3>
<p>When embedding a YouTube Live stream in a page or post of your WordPress site, you can use the shortcode [ylchat] to embed the associated comment stream (which can be used to take questions from the audience). This extracts the video ID from the YouTube link included in the page and constructs the iframe for the chat window, according to Google's specifications. You can add attributes for width and height to override the default values (100% wide x 200 pixels tall). You can add a note to be displayed above the comments field using the note parameter, as in [ylchat note=&quot;During the program, please post questions and comments in the chat box below.&quot;]</p>

<p>RSVPMaker will stop displaying the chat field once the live event is over and the live chat is no longer active. Because this requires RSVPMaker to keep polling YouTube to see if the chat is live, you may wish to remove the shortcode from the page for replay views.</p>

<?php

}

function rsvpmaker_debug () {
global $wpdb;
global $rsvp_options;

ob_start();
if(isset($_GET["rsvp"]))
	{
	
$sql = "SELECT ".$wpdb->prefix."rsvpmaker.*, ".$wpdb->prefix."posts.post_title FROM ".$wpdb->prefix."rsvpmaker JOIN ".$wpdb->prefix."posts ON ".$wpdb->prefix."rsvpmaker.event = ".$wpdb->prefix."posts.ID ORDER BY ".$wpdb->prefix."rsvpmaker.id DESC LIMIT 0, 10";

$wpdb->show_errors();
$results = $wpdb->get_results($sql);
echo "RSVP RECORDS\n";
echo $sql . "\n";
print_r($results);

	}
if(isset($_GET["options"]))
	{
echo "\n\nOPTIONS\n";
print_r($rsvp_options);	
	}
if(isset($_GET["rewrite"]))
	{
	global $wp_rewrite;
	echo "\n\nREWRITE\n";
	print_r($wp_rewrite);
	}
if(isset($_GET["globals"]))
	{
	echo "\n\nGLOBALS\n";
	print_r($GLOBALS);
	}
$output = ob_get_clean();

$output = "Version: ".get_bloginfo('version')."\n".$output;

if(MULTISITE)
	$output .= "Multisite: YES\n";
else
	$output .= "Multisite: NO\n";

if(isset($_GET["author"]))
	{
	$url = get_bloginfo('url');
	$email = get_bloginfo('admin_email');
	mail("david@carrcommunications.com","RSVPMAKER DEBUG: $url", $output);
	}

;?>
<h2><?php _e('Debug','rsvpmaker');?></h2>
<p><?php _e('Use this screen to verify that RSVPMaker is recording data correctly or to share debugging information with the plugin author. If you send debugging info, follow up with a note to <a href="mailto:david@carrcommunications.com">david@carrcommunications.com</a> and explain what you need help with.','rsvpmaker');?></p>
<form action="<?php echo admin_url("edit.php");?>" method="get">
<input type="hidden" name="post_type" value="rsvpmaker" />
<input name="page" type="hidden" value="rsvpmaker_debug" />
  <label>
  <input type="checkbox" name="rsvp" id="rsvp"  value="1" />
  <?php _e('RSVP Records','rsvpmaker');?></label>
 <label>
 <input type="checkbox" name="options" id="options"  value="1" />
 <?php _e('Options','rsvpmaker');?></label>
    <label>
    <input type="checkbox" name="rewrite" id="rewrite"  value="1" />
    <?php _e('Rewrite Rules','rsvpmaker');?>
</label>
<label>
<input type="checkbox" name="globals" id="globals" value="1" />
<?php _e('Globals','rsvpmaker');?></label>
<label>
    <input type="checkbox" name="author" id="author"  value="1"  />
   <?php _e('Send to Plugin Author','rsvpmaker');?></label>
   <input type="submit" value="Show" />
</form>
<pre>
<?php echo $output;?>
</pre>
<?php
}

//my_events_rsvp function in rsvpmaker-pluggable.php
add_action('admin_menu', 'my_rsvp_menu');

add_filter('manage_posts_columns', 'rsvpmaker_columns');
function rsvpmaker_columns($defaults) {
	if(!empty($_GET["post_type"]) && ($_GET["post_type"] == 'rsvpmaker'))
    	$defaults['event_dates'] = __('Event Dates','rsvpmaker');
	if(!empty($_GET["post_type"]) && ($_GET["post_type"] == 'rsvpemail'))
    	$defaults['rsvpmaker_cron'] = __('Scheduled','rsvpmaker');
    return $defaults;
}

add_action('manage_posts_custom_column', 'rsvpmaker_custom_column', 10, 2);

function rsvpmaker_custom_column($column_name, $post_id) {
    global $wpdb;
    if( $column_name == 'event_dates' ) {

$results = get_rsvp_dates($post_id);
$template = get_post_meta($post_id,'_sked',true);
$rsvpmaker_special = get_post_meta($post_id,'_rsvpmaker_special',true);

$s = $dateline = '';

if($results)
{
foreach($results as $row)
		{
		$t = strtotime($row["datetime"]);
		if($dateline)
			$dateline .= ", ";
		$dateline .= date('F jS, Y',$t);
		}
if(isset($dateline)) echo $dateline;

}
elseif($template)
	{
echo __("Template",'rsvpmaker').": ";
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

$weekarray = Array(__("Varies",'rsvpmaker'),__("First",'rsvpmaker'),__("Second",'rsvpmaker'),__("Third",'rsvpmaker'),__("Fourth",'rsvpmaker'),__("Last",'rsvpmaker'),__("Every",'rsvpmaker'));
$dayarray = Array(__("Sunday",'rsvpmaker'),__("Monday",'rsvpmaker'),__("Tuesday",'rsvpmaker'),__("Wednesday",'rsvpmaker'),__("Thursday",'rsvpmaker'),__("Friday",'rsvpmaker'),__("Saturday",'rsvpmaker'));

if($weeks[0] == 0)
	echo __('Schedule varies','rsvpmaker');
else
	{
	foreach($weeks as $week)
		{
		if(!empty($s))
			$s .= '/ ';
		$s .= $weekarray[(int) $week].' ';
		}
	foreach($dows as $dow)
		$s .= $dayarray[(int) $dow] . ' ';	
	echo $s;
		
	}

	} // end sked
	elseif($rsvpmaker_special)
		{
			echo __('Special Page','rsvpmaker').': '.$rsvpmaker_special;
		}
	} // end event dates column
	elseif($column_name == 'rsvpmaker_cron') {
		echo rsvpmaker_next_scheduled($post_id);	
	}
}

function rsvpmaker_reminders_list($post_id)
{
global $wpdb;
$sql = "SELECT  `meta_key` 
FROM  `$wpdb->postmeta` 
WHERE  `meta_key` LIKE  '_rsvp_reminder_msg%' AND post_id = $post_id
ORDER BY  `meta_key` ASC ";
$results = $wpdb->get_results($sql);
$txt = '';
if($results)
	{
		foreach ($results as $row)
			{
				$p = explode('_msg_',$row->meta_key);
				$hours[] = (int) $p[1];
			}
	sort($hours);
	foreach($hours as $hour)
		{
			if($hour > 0)
				$label = __('Follow up','rsvpmaker').': '.$hour.' '.__('hours after','rsvpmaker');
			else
				$label = __('Reminder','rsvpmaker').': '.abs($hour).' '.__('hours before','rsvpmaker');
		$txt .= sprintf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours='.$hour.'&page=rsvp_reminders&message_type=reminder&post_id=').$post_id,$label);
		}
	}
return $txt;
}

function rsvpmaker_admin_notice() {
global $wpdb;
global $rsvp_options;
global $current_user;
global $post;
$timezone_string = get_option('timezone_string');

if(isset($post->post_type) && ($post->post_type == 'rsvpmaker') ) {
if($landing = get_post_meta($post->ID,'_webinar_landing_page_id',true))
	{
	echo '<div class="notice notice-info"><p>'.__('Edit the','rsvpmaker').' <a href="'.admin_url('post.php?action=edit&post='.$landing).'">'.__("webinar landing page",'rsvpmaker').'</a> '.__('associated with this event').'.</p>';
	echo '<p>';
	_e('Related messages:','rsvpmaker');
	printf(' <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$post->ID,__('Confirmation','rsvpmaker'));
	echo rsvpmaker_reminders_list($post->ID);
/*	printf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours=-2&page=rsvp_reminders&message_type=reminder&post_id=').$post->ID,__('Reminder','rsvpmaker'));	
	printf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours=2&page=rsvp_reminders&message_type=reminder&post_id=').$post->ID,__('Follow Up','rsvpmaker'));	
*/
	echo '</p></div>';
	}
if($event = get_post_meta($post->ID,'_webinar_event_id',true))
	{
	echo '<div class="notice notice-info"><p>'.__('Edit the','rsvpmaker').' <a href="'.admin_url('post.php?action=edit&post='.$event).'">'.__("webinar event post",'rsvpmaker').'</a> '.__('associated with this landing page').'.</p>';
	echo '<p>';
	_e('Related messages:','rsvpmaker');
	printf(' <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$event,__('Confirmation','rsvpmaker'));	
	echo rsvpmaker_reminders_list($event);
/*
	printf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours=-2&page=rsvp_reminders&message_type=reminder&post_id=').$event,__('Reminder','rsvpmaker'));	
	printf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours=2&page=rsvp_reminders&message_type=reminder&post_id=').$event,__('Follow Up','rsvpmaker'));	
*/
	echo '</p></div>';
	}
}

if(current_user_can('manage_options') && function_exists('my_chimpblasts_menu'))
	echo '<div class="notice notice-warning"><p>'.__('ChimpBlast has been replaced by the RSVP Mailer function of RSVPMaker and should be uninstalled','rsvpmaker').'</p></div>';

if(empty($timezone_string))
	printf('<div class="notice notice-warning is-dismissible">
    <p>%s <a href="%s">%s</a> %s</p>
</div>',__('RSVPMaker needs you to','rsvpmaker'),admin_url('options-general.php'),__('set the timezone for your website','rsvpmaker'), __('using a region/city string like America/New York','rsvpmaker') );

if(isset($_GET["update"]) && ($_GET["update"] == "eventslug"))
	{
	$wpdb->query("UPDATE $wpdb->posts SET post_type='rsvpmaker' WHERE post_type='event' OR post_type='rsvp-event' ");
	}
if(isset($_GET["create_calendar_page"]))
	{
	$post = array(
	  'post_content'   => '[rsvpmaker_upcoming calendar="1" comment="This placeholder code displays the calendar of events."]',
	  'post_name'      => 'calendar',
	  'post_title'     => 'Calendar',
	  'post_status'    => 'publish',
	  'post_type'      => 'page',
	  'post_author'    => $current_user->ID,
	  'ping_status'    => 'closed'
	);
	wp_insert_post($post);		
	}
if(isset($_GET["noeventpageok"]) && $_GET["noeventpageok"])
	{
	update_option('noeventpageok',1);
	}
elseif( (!isset($rsvp_options["eventpage"]) || empty($rsvp_options["eventpage"]) ) && !get_option('noeventpageok') && !is_plugin_active('rsvpmaker-for-toastmasters/rsvpmaker-for-toastmasters.php') )
	{
	$sql = "SELECT ID from $wpdb->posts WHERE post_type='page' AND post_status='publish' AND post_content LIKE '%[rsvpmaker_upcoming%' ";
	$front = get_option('page_on_front');
	if($front)
		$sql .= " AND ID != $front ";
	if($id =$wpdb->get_var($sql))
		{
		$rsvp_options["eventpage"] = get_permalink($id);
		update_option('RSVPMAKER_Options',$rsvp_options);
		}
	else
		echo '<div class="notice notice-warning"><p>'.__('RSVPMaker needs you to create a page with the [rsvpmaker_upcoming] shortcode to display event listings','rsvpmaker').'. (<a href="'.admin_url('options-general.php?page=rsvpmaker-admin.php&create_calendar_page=1').'">'.__('Create page','rsvpmaker').'</a>: &quot;'.__('Calendar','rsvpmaker').'&quot; | <a href="'.admin_url('/?noeventpageok=1').'">'.__('Turn off this warning','rsvpmaker').'</a>)</p></div>';
	}
	
	if(isset($_GET["smtptest"]))
		{
		$mail["to"] = $rsvp_options["rsvp_to"];
	$mail["from"] = "david@carrcommunications.com";
	$mail["fromname"] = "RSVPMaker";
	$mail["subject"] = __("Testing SMTP email notification",'rsvpmaker');
	$mail["html"] = '<p>'. __('Test from RSVPMaker.','rsvpmaker').'</p>';
	$result = rsvpmailer($mail);
	echo '<div class="updated" style="background-color:#fee;">'."<strong>".__('Sending test email','rsvpmaker').' '.$result ."</strong></div>";
		}
}

add_action('admin_notices', 'rsvpmaker_admin_notice');

function rsvpmailer($mail) {
	global $rsvp_options;
	
	$rsvp_options = apply_filters('rsvp_email_options',$rsvp_options);

	if(!isset($rsvp_options["smtp"]) || empty($rsvp_options["smtp"]))
		{
		$to = $mail["to"];
		$subject = $mail["subject"];
		if(!empty($mail["html"]))
			{
				$body = $mail["html"];
				if(function_exists('set_html_content_type') ) // if using sendgrid plugin
					add_filter('wp_mail_content_type', 'set_html_content_type');
				else
					$headers[] = 'Content-Type: text/html; charset=UTF-8';
			}
		else
				$body = $mail["text"];
		$headers[] = 'From: '.$mail["fromname"]. ' <'.$mail["from"].'>'."\r\n";
		if(!empty($mail["replyto"]))
			$headers[] = 'Reply-To: '.$mail["replyto"] ."\r\n";
		$attachments = NULL;
		if(isset($mail["ical"]))
			{
			$temp = tmpfile();
			fwrite($temp, $mail["ical"]);
			$metaDatas = stream_get_meta_data($temp);
			$tmpFilename = $metaDatas['uri'];
			$icalname = $tmpFilename .'.ics';
			rename($tmpFilename,$icalname);
			$attachments[] = $icalname;
			}
			
		wp_mail( $to, $subject, $body, $headers, $attachments );
		if(function_exists('set_html_content_type') )
			remove_filter('wp_mail_content_type', 'set_html_content_type');
		return;
		}
	
	require_once ABSPATH . WPINC . '/class-phpmailer.php';
	require_once ABSPATH . WPINC . '/class-smtp.php';
	$rsvpmail = new PHPMailer();
	
	if(!empty($rsvp_options["smtp"]))
	{
		$rsvpmail->IsSMTP(); // telling the class to use SMTP
	
	if($rsvp_options["smtp"] == "gmail") {
		$rsvpmail->SMTPAuth   = true;                  // enable SMTP authentication
		$rsvpmail->SMTPSecure = "tls";                 // sets the prefix to the servier
		$rsvpmail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		$rsvpmail->Port       = 587;                   // set the SMTP port for the GMAIL server
	}
	elseif($rsvp_options["smtp"] == "sendgrid") {
	$rsvpmail->SMTPAuth   = true;                  // enable SMTP authentication
	$rsvpmail->Host = 'smtp.sendgrid.net';
	$rsvpmail->Port = 587; 
	}
	elseif(!empty($rsvp_options["smtp"]) ) {
	$rsvpmail->Host = $rsvp_options["smtp_server"]; // SMTP server
	$rsvpmail->SMTPAuth=true;
	if(isset($rsvp_options["smtp_prefix"]) && $rsvp_options["smtp_prefix"] )
		$rsvpmail->SMTPSecure = $rsvp_options["smtp_prefix"];                 // sets the prefix to the servier
	$rsvpmail->Port=$rsvp_options["smtp_port"];
	}
 	
	}
	
 $rsvpmail->Username= (!empty($rsvp_options["smtp_username"]) ) ? $rsvp_options["smtp_username"] : '';
 $rsvpmail->Password= (!empty($rsvp_options["smtp_password"]) ) ? $rsvp_options["smtp_password"] : '';
 $rsvpmail->AddAddress($mail["to"]);
 if(isset($mail["cc"]) )
 	$rsvpmail->AddCC($mail["cc"]);
$via = (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) ? ' (via '.$_SERVER['SERVER_NAME'].')' : '';
if(is_admin() && isset($_GET["debug"]))
	$rsvpmail->SMTPDebug = 2;
 if(!empty($rsvp_options["smtp_useremail"]))
 	{
	 $rsvpmail->SetFrom($rsvp_options["smtp_useremail"], $mail["fromname"]. $via);
	 $rsvpmail->AddReplyTo($mail["from"], $mail["fromname"]);
	}
 else
	 $rsvpmail->SetFrom($mail["from"], $mail["fromname"]. $via); 
 $rsvpmail->ClearReplyTos();
 $rsvpmail->AddReplyTo($mail["from"], $mail["fromname"]);
if(!empty($mail["replyto"]))
 $rsvpmail->AddReplyTo($mail["replyto"]);

 $rsvpmail->Subject = $mail["subject"];
if($mail["html"])
	{
	if($mail["text"])
		$rsvpmail->AltBody = $mail["text"];
	else
		$rsvpmail->AltBody = trim(strip_tags($mail["html"]) );
	$rsvpmail->MsgHTML($mail["html"]);
	}
	else
		{
			$rsvpmail->Body = $mail["text"];
			$rsvpmail->WordWrap = 150;
		}

	if(isset($mail["ical"]))
		$rsvpmail->Ical = $mail["ical"];
	
	try {
		$rsvpmail->Send();
	} catch (phpmailerException $e) {
		echo $e->errorMessage();
	} catch (Exception $e) {
		echo $e->getMessage(); //Boring error messages from anything else!
	}
	
	return $rsvpmail->ErrorInfo;
}

function set_rsvpmaker_order_in_admin( $wp_query ) {
  if ( is_admin() && isset($_GET["rsvpsort"]) && ($_GET["rsvpsort"]=="chronological") ) {
add_filter('posts_join', 'rsvpmaker_join',99 );
add_filter('posts_where', 'rsvpmaker_where',99 );
add_filter('posts_groupby', 'rsvpmaker_groupby',99 );
add_filter('posts_orderby', 'rsvpmaker_orderby',99 );
add_filter('posts_distinct', 'rsvpmaker_distinct',99 );
  }
return $wp_query;
}
add_filter('pre_get_posts', 'set_rsvpmaker_order_in_admin',1 );

function rsvpmaker_sort_message() {
	if((basename($_SERVER['SCRIPT_NAME']) == 'edit.php') && ($_GET["post_type"]=="rsvpmaker") && !isset($_GET["page"]))
	{
		echo '<div style="padding: 10px; ">';
		if(isset($_GET["rsvpsort"]) && ($_GET["rsvpsort"] == 'chronological'))
			echo '<a class="add-new-h2" href="'.admin_url('edit.php?post_type=rsvpmaker&rsvpsort=newest').'">'.__('Sort By Newest','rsvpmaker').'</a>';
		else
			echo '<a class="add-new-h2" href="'.admin_url('edit.php?post_type=rsvpmaker&rsvpsort=chronological').'">'.__('Sort By Event Date','rsvpmaker').'</a>';
		echo '</div>';
	}
}
add_action('manage_posts_extra_tablenav','rsvpmaker_sort_message');

function rsvpmaker_get_projected($template) {

//backward compatability
if(is_array($template["week"]))
	{
		$weeks = $template["week"];
		$dows = $template["dayofweek"];
	}
else
	{
		$weeks[0] = $template["week"];
		$dows[0] = $template["dayofweek"];
	}

$cy = date("Y");
$cm = date("m");

if(!empty($template["stop"]))
	{
	$stopdate = strtotime($template["stop"].' 23:59:59');
	}

foreach($weeks as $week)
foreach($dows as $dow) {

if($week == 6)
	{
	if(empty($stopdate))
		$stopdate = strtotime('+6 months');
	$ts = strtotime(rsvpmaker_day($dow,'strtotime'));
	if(isset($_GET["start"]))
		$ts = strtotime($_GET["start"]);
	$i = 0;
	while($ts < $stopdate)
		{
		$projected[$ts] = $ts; // add numeric value for 1 week
		$i++;
		$text = rsvpmaker_day($dow,'strtotime') ." +".$i." week";
		$ts = strtotime($text);
		}
	}
else {
	//monthly
	$ts = mktime(0,0,0,$cm,1,$cy); // first day of month
	if(isset($_GET["start"]))
		$ts = strtotime($_GET["start"]);
	$i = 0;
	if(empty($stopdate))
		$stopdate = strtotime('+1 year');
	while($ts < $stopdate)
		{
		$firstdays[$ts] = $ts;
		$i++;
		$ts = mktime(0,0,0,$cm+$i,1,$cy); // first day of month
		if($week == 0)
			$projected[$ts] = $ts;
		}
	if($week > 0)
		{
			if($week == 5)
				$wtext = 'Last';
			else
				$wtext = '+'. ($week - 1) .' week';
			foreach($firstdays as $i => $firstday)
				{
				$datetext =  "$wtext ".rsvpmaker_day($dow,'strtotime')." ".date("F Y",$firstday);
				$ts = strtotime($datetext);
				if(isset($stopdate) && $ts > $stopdate)
					break;
				$projected[$ts] = $ts;
				}
		}
	}
}

//order by timestamp
ksort($projected);

return $projected; 
}

// RSVPMaker Replay Follow up

function rsvpmaker_replay_cron($post_id, $rsvp_id, $hours) {
//Convert start time from local time to GMT since WP Cron sends based on GMT
$start_time_gmt = time();
$time_difference = $hours * 60 * 60; 
$reminder_time = $start_time_gmt + $time_difference;

wp_clear_scheduled_hook( 'rsvpmaker_replay_email', array( $post_id, $rsvp_id, $hours ) );

//Schedule the reminder
wp_schedule_single_event( $reminder_time, 'rsvpmaker_replay_email', array( $post_id, $rsvp_id, $hours ) );
}

add_action('rsvpmaker_replay_email','rsvpmaker_replay_email',10,3);

function rsvpmaker_replay_email ( $post_id, $rsvp_id, $hours ) {
global $wpdb;
global $rsvp_options;
$wpdb->show_errors();
	$confirm_slug = '_rsvp_reminder_msg_'.$hours;
	$confirm = get_post_meta($post_id, $confirm_slug, true);
	$subject = get_post_meta($post_id, '_rsvp_reminder_subject_'.$hours, true);

	if(!empty($confirm))
	{
	$confirm = wpautop($confirm);				
	}

	$rsvpto = get_post_meta($post_id,'_rsvp_to',true);			
	
$sql = "SELECT email FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post_id AND id=".$rsvp_id;
	$notify = $wpdb->get_var($sql);							
	$mail["subject"] = $subject;
	$mail["html"] = $confirm;
	$mail["to"] = $notify;
	$mail["from"] = $rsvp_to;
	$mail["fromname"] = get_bloginfo('name');
	rsvpmaker_tx_email(get_post($post_id), $mail);
	/*
		if(isset($rsvp_options["smtp"]) && !empty($rsvp_options["smtp"]) )
			{
			$mail["subject"] = $subject;
			$mail["html"] = $confirm;
			$mail["to"] = $notify;
			$mail["from"] = $rsvp_to;
			$mail["fromname"] = get_bloginfo('name');
			$result = rsvpmailer($mail);
			}
		else
			{
			echo wpautop("Notification to $notify");
			wp_mail($notify,$subject,$notification,"From: $rsvpto\nContent-Type: text/html; charset=UTF-8");
			}
	*/
}

// RSVPMaker Reminders

function rsvpmaker_reminder_cron($hours, $start_time, $post_id) {
$hours = (int) $hours;
$post_id = (int) $post_id;
//Convert start time from local time to GMT since WP Cron sends based on GMT
$start_time_gmt = strtotime( get_gmt_from_date( $start_time ) . ' GMT' );

$time_difference = $hours * 60 * 60; 
$reminder_time = $start_time_gmt + $time_difference;

//Remove existing cron event for this post if one exists
//We pass $post_id because cron event arguments are required to remove the scheduled event
wp_clear_scheduled_hook( 'rsvpmaker_send_reminder_email', array( $post_id, $hours ) );

//Schedule the reminder
wp_schedule_single_event( $reminder_time, 'rsvpmaker_send_reminder_email', array( $post_id, $hours ) );
}

add_action('rsvpmaker_send_reminder_email','rsvpmaker_send_reminder_email',10,2);

function rsvpmaker_send_reminder_email ( $post_id, $hours ) {
global $wpdb;
global $rsvp_options;
$wpdb->show_errors();
	$confirm = get_post_meta($post_id, '_rsvp_reminder_msg_'.$hours, true);
	$subject = get_post_meta($post_id, '_rsvp_reminder_subject_'.$hours, true);
	$include_event = get_post_meta($post_id, '_rsvp_confirmation_include_event', true);

	if(!empty($confirm))
	{
	$confirm = wpautop($confirm);				
	}

	if($hours < 0)
	{	
	$confirm .= "<p>".__("This is an automated reminder that we have you on the RSVP list for the event shown below. If your plans have changed, you can update your response by clicking on the RSVP button again.",'rsvpmaker')."</p>";
	$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
		if($include_event)
		{
			$event_content = event_to_embed($post_id);
		}
		else
			$event_content = $rsvp_options['rsvplink'];
	}
			$rsvpto = get_post_meta($post_id,'_rsvp_to',true);			
			
			$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post_id AND yesno=1";
			$rsvps = $wpdb->get_results($sql,ARRAY_A);
			if($rsvps)
			foreach($rsvps as $row)
				{
				$notify = $row["email"];
				$notification = $confirm;
				$notification .= '<h3>'.$row["first"]." ".$row["last"]." ".$row["email"];
				if(!empty($row["guestof"]))
					$notification .=  " (". __('guest of','rsvpmaker')." ".$row["guestof"].")";
				$notification .=  "</h3>\n";
				$notification .=   "<p>";
				if(!empty($row["details"]))
					{
					$details = unserialize($row["details"]);
					foreach($details as $name => $value)
						if($value) {
							$notification .=  "$name: $value<br />";
							}
					}
				if(!empty($row["note"]))
					$notification .= "note: " . nl2br($row["note"])."<br />";
				$t = strtotime($row["timestamp"]);
				$notification .= 'posted: '.strftime($rsvp_options["short_date"],$t);
				$notification .=  "</p>";
				$notification .=  "<h3>Event Details</h3>\n".str_replace('#rsvpnow">','#rsvpnow">'.__('Update','rsvptoast').' ',str_replace('*|EMAIL|*',$notify, $event_content));
				
				echo "Notification for $notify<br />$notification";

			//if this is a follow up, we don't need all the RSVP data
			if($hours > 0)
				$notification = $confirm;

				$mail["subject"] = $subject;
				$mail["html"] = $notification;
				$mail["to"] = $notify;
				$mail["from"] = $rsvp_to;
				$mail["fromname"] = get_bloginfo('name');
				rsvpmaker_tx_email(get_post($post_id), $mail);
				}
}

function reminder_events_menu () {
add_submenu_page('edit.php?post_type=rsvpmaker', __("Scheduled Reminders",'rsvpmaker'), __("RSVP Reminders",'rsvpmaker'), 'manage_options', "rsvp_reminders", "rsvp_reminders" );
}

function rsvp_reminder_options($hours = -2) {
$ho = array(-1,-2,-3,-4,-5,-6,-7,-8,-12,-16,-20,-24,-48,-72,1,2,3,4,5,6,7,8,12,16,20,24,28,32,36,40,44,48,72);
$o = "";
foreach($ho as $h)
	{
	$s = ($h == $hours) ? ' selected="selected" ' : '';
	if($h < 0)
		$o .= sprintf('<option value="%s" %s>%s ',$h,$s, abs($h) ).__('hours before','rsvpmaker').'</option>';
	else
		$o .= sprintf('<option value="%s" %s>%s ',$h,$s,$h).__('hours after event starts','rsvpmaker').'</option>';
	}
	return $o;
}

function rsvpmaker_youtube_live($post_id, $ylive, $show = false) {
global $rsvp_options;
global $current_user;
		fix_timezone();
		$event = get_post($post_id);
		$start_time = $date = get_rsvp_date($post_id);
		$date = utf8_encode(strftime($rsvp_options["long_date"].' %l:%M %p %Z',strtotime($date)));
		$landing["post_type"] = 'rsvpmaker';
		$landing["post_title"] = __('Live','rsvpmaker').': '.$event->post_title;
		$landing["post_content"] = __('The event starts','rsvpmaker').' '.$date."\n\n".$ylive;
		if(!empty($ylive))
			$landing["post_content"] .= "\n\n[ylchat note=\"During the program, please post questions and comments in the chat box below.\"]";
		$landing["post_author"] = $current_user->ID;
		$landing["post_status"] = 'publish';
		$landing_id = get_post_meta($post_id,'_webinar_landing_page_id',true);
		if($landing_id)
			{
			$landing["ID"] = $landing_id;
			wp_update_post( $landing );
			}
		else
			{
			$landing_id = wp_insert_post( $landing );
			}
		update_post_meta($post_id,'_webinar_landing_page_id',$landing_id);
		$landing_permalink = get_permalink($landing_id);
		$landing_permalink .= (strpos($landing_permalink,'?')) ? '&webinar=' : '?webinar=';
		$landing_permalink .= $passcode = wp_generate_password(14, false); // 14 characters, no special characters
		update_post_meta($landing_id,'_rsvpmaker_special','Landing Page');
		update_post_meta($landing_id,'_webinar_event_id',$post_id);
		update_post_meta($landing_id,'_webinar_passcode',$passcode);
		if(isset($_REQUEST["youtube_require_passcode"]))
			update_post_meta($landing_id,'_require_webinar_passcode',$passcode);
	$subject = 'Reminder: '.$event->post_title;
	$message = __('Thanks for registering for','rsvpmaker').' '.$event->post_title."\n\n".__('The event will start at','rsvpmaker').' '.$date."\n\n".__('Tune in here','rsvpmaker').":\n".'<a href="'.$landing_permalink.'">'.$landing_permalink."</a>\n\n".__('You will be able to post questions or comments to the live chat on the event page').'.';
	$hours = -2;
	update_post_meta($post_id, '_rsvp_confirm',$message);
	update_post_meta($post_id, '_rsvp_reminder_msg_'.$hours,$message);
	update_post_meta($post_id, '_rsvp_reminder_subject_'.$hours,$subject);
	rsvpmaker_reminder_cron($hours, $start_time, $post_id);

	$hours = 2;
	$subject = 'Follow up: '.$event->post_title;
	$message = __('Thanks for your interest in ','rsvpmaker').' '.$event->post_title."\n\n".__('If you missed all or part of the program, a replay is waiting for you here','rsvpmaker').":\n".'<a href="'.$landing_permalink.'">'.$landing_permalink."</a>\n\n";
	update_post_meta($post_id, '_rsvp_reminder_msg_'.$hours,$message);
	update_post_meta($post_id, '_rsvp_reminder_subject_'.$hours,$subject);
	rsvpmaker_reminder_cron($hours, $start_time, $post_id);

	if($show)
		printf('<p>%s <a href="%s">%s</a> (<a href="%s">%s</a>)</p>',__('YouTube Live landing page created at'),$landing_permalink,$landing_permalink, admin_url('post.php?action=edit&post='.$landing_id), __('Edit','rsvpmaker'));
	
}

function rsvp_reminders () {
global $wpdb;
global $rsvp_options;
global $current_user;
$existing = $options = '';
fix_timezone();
?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div>
<h1><?php _e('RSVP Reminders','rsvpmaker'); ?></h1> 
<?php

if(isset($_GET["webinar"]))
	{
		$post_id = $_GET["post_id"];
		$ylive = $_GET["youtube_live"];	
		rsvpmaker_youtube_live($post_id, $ylive, true);
	}

if(isset($_POST["hours"]))
{
	$hours = (int) $_POST["hours"];
	$post_id = (int) $_POST["post_id"];
	$start_time = $_POST["start_time"];
	$message = $_POST["message"];
	$subject = $_POST["subject"];
	update_post_meta($post_id, '_rsvp_reminder_msg_'.$hours,$message);
	update_post_meta($post_id, '_rsvp_reminder_subject_'.$hours,$subject);
	if($hours > 0)
		{
		printf('<div class="updated">Setting follow up for %s hours after %s</div>',$hours, $start_time);
		}
	else
		printf('<div class="updated">Setting reminder for %s hours before %s</div>',abs($hours), $start_time);	
	rsvpmaker_reminder_cron($hours, $start_time, $post_id);
}

if(isset($_POST["_rsvp_confirm"])) {
	$post_id = (int) $_POST['post_id'];
	update_post_meta($post_id, '_rsvp_confirm',$_POST["_rsvp_confirm"]);
		printf('<div class="updated">Confirmation message updated. <a href="%s">Add a reminder</a>?</div>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=reminder&hours=-2&post_id=').$post_id );
}

if(isset($_GET["delete_reminder"])) {
	$post_id = (int) $_GET["post_id"];
	$hours = (int) $_GET["hours"];
	delete_post_meta($post_id, '_rsvp_reminder_msg_'.$hours);
	delete_post_meta($post_id, '_rsvp_reminder_subject_'.$hours);
	wp_clear_scheduled_hook( 'rsvpmaker_send_reminder_email', array( $post_id, $hours ) );
}

if(isset($_GET["message_type"])) {
	$type = $_GET["message_type"];
	$post_id = (int) $_GET["post_id"];
	$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
	FROM `".$wpdb->postmeta."`
	JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates' 
	WHERE $wpdb->posts.ID =" . $post_id;
	$event = $wpdb->get_row($sql);
	$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
	$titledate = $event->post_title.' '.$prettydate;

	$confirm = "";
	if($type == 'reminder')
	{
		$hours = (int) $_GET["hours"];
		$content = get_post_meta($post_id,'_rsvp_reminder_msg_'.$hours,true);
		$subject = get_post_meta($post_id,'_rsvp_reminder_subject_'.$hours,true);
		if(empty($subject))
			{
			if($hours < 0)
				{
				$subject = "REMINDER: ".$event->post_title.' '.$prettydate;
				$sql = "SELECT * FROM  `wp_postmeta` WHERE meta_key REGEXP '_rsvp_reminder_msg_-[0-9]{1,2}' AND  `post_id` = " . $post_id. ' ORDER BY meta_key';
				}
			else
				{
				$subject = "Follow up from  ".$event->post_title;
				$sql = "SELECT * FROM  `wp_postmeta` WHERE meta_key REGEXP '_rsvp_reminder_msg_[0-9]{1,2}' AND  `post_id` = " . $post_id. ' ORDER BY meta_key';
				}					
			$row = $wpdb->get_row($sql);
			if($row)
				{ // if no message for same hour, model on another reminder / follow up message
				$content = $row->meta_value;
				$subject = get_post_meta($post_id,str_replace('msg','subject',$row->meta_key),true);
				}
			}
		
		echo '<h2>'.__('Edit Reminder Message','rsvpmaker')."</h2>";
		$editor_id = "message";
?>
<form method="post" action = "<?php echo admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders'); ?>">
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" >
<input type="hidden" name="start_time" value="<?php echo $event->datetime; ?>" >
<p>Timing: <select name="hours"><?php echo rsvp_reminder_options($hours); ?></select></p>

<p>Subject: <input type="text" name="subject" value="<?php echo $subject; ?>" size="100"></p>
<?php
echo "<p>".__( 'Edit and save this text to create an email reminder.','rsvpmaker' )."</p>";
	}
	else
	{
		echo '<h2>'.__('Edit Confirmation Message','rsvpmaker')."</h2>";
		$editor_id = "_rsvp_confirm";
?>
<h3><?php echo $titledate; ?></h3>
<form method="post" action = "<?php echo admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders'); ?>">
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" >
<?php
	}

if(empty($content) )
	$content = get_post_meta($post_id,'_rsvp_confirm',true);
$settings = array();
wp_editor( $content, $editor_id, $settings );
?>
<p><button>Save</button></p>
</form>
<?php
}

if(isset($_REQUEST["post_id"]))
	{
	$id = (int) $_REQUEST["post_id"];
	$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE (meta_value > CURDATE( ) OR ID=$id ) AND $wpdb->posts.post_status = 'publish'
ORDER BY datetime LIMIT 0, 50";

$event = $wpdb->get_row($sql);
$confirm = get_post_meta($event->postID,'_rsvp_confirm',true);
$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
$titledate = $event->post_title.' '.$prettydate;

$s = (isset($_REQUEST["post_id"]) && $_REQUEST["post_id"] == $event->postID ) ? ' selected="selected" ' : '';

$options .= sprintf('<option value="%d" %s>%s</option>',$event->postID, $s, $titledate);
$confirm = get_post_meta($event->postID,'_rsvp_confirm',true);
if(!empty($confirm))
	{
	printf('<h3>Confirmation Message: %s</h3>%s',$titledate,wpautop($confirm));
	printf('<p><a href="%s">Edit</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation').'&post_id='.$event->postID);
	}

$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$event->ID AND meta_key LIKE '_rsvp_reminder_msg_%' ORDER BY meta_key";
$reminders = $wpdb->get_results($sql);
foreach ($reminders as $reminder)
	{
	$hour = (int) str_replace('_rsvp_reminder_msg_','',$reminder->meta_key);
	$subject = get_post_meta($event->postID,'_rsvp_reminder_subject_'.$hour,true);
	//echo $hour;
	if($hour < 0)
		$existing .= '<p><em>'.sprintf(__("Set for %s hours before the start of the event",'rsvpmaker'),abs($hour)).'</em></p>';
	else	
		$existing .= '<p><em>'.sprintf(__("Set for %s hours after the start of the event",'rsvpmaker'),$hour).'</em></p>';
	$existing .= sprintf('<h3>Subject: %s</h3>',$subject);	
	$existing .= wpautop($reminder->meta_value);
	$existing .= sprintf('<p><a href="%s">Edit</a> | <a href="%s">Delete</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=reminder').'&hours='.$hour.'&post_id='.$reminder->post_id,admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&delete_reminder=1').'&hours='.$hour.'&post_id='.$reminder->post_id );
	}
	
	}

$cron_jobs = _get_cron_array();
$now = time();
foreach ($cron_jobs as $key => $job)
	{
		if(($key > $now) && isset($job["rsvpmaker_send_reminder_email"]) )
			{
			$onejob = array_shift($job["rsvpmaker_send_reminder_email"]);
			$reminder_event = (int) $onejob['args'][0];
			$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
			FROM `".$wpdb->postmeta."`
			JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates' 
			WHERE $wpdb->posts.ID =" . $reminder_event;
			$event = $wpdb->get_row($sql);
			$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
			$label = $event->post_title.' '.$prettydate.' ';
			$hour = (int) $onejob['args'][1];
			if($hour > 0)
				$label .= __('Follow up','rsvpmaker').': '.$hour.' '.__('hours after','rsvpmaker');
			else
				$label .= __('Reminder','rsvpmaker').': '.abs($hour).' '.__('hours before','rsvpmaker');
			$existing .= sprintf('<p><em><a href="%s">%s</a></em></p>',admin_url('edit.php?hours='.$hour.'&post_type=rsvpmaker&page=rsvp_reminders&message_type=reminder&post_id='.$reminder_event),$label );
	$subject = get_post_meta($event->postID,'_rsvp_reminder_subject_'.$hour,true);
	$message = get_post_meta($event->postID,'_rsvp_reminder_msg_'.$hour,true);
	$existing .= sprintf('<h3>Subject: %s</h3>',$subject);	
	$existing .= wpautop($message);
	$existing .= sprintf('<p><a href="%s">Edit</a> | <a href="%s">Delete</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=reminder').'&hours='.$hour.'&post_id='.$reminder_event,admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&delete_reminder=1').'&hours='.$hour.'&post_id='.$reminder_event );

			}
	}
if(!empty($existing))
	echo '<h3>Previously Set Reminders</h3>'.$existing;	

// future events
$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value > CURDATE( ) AND $wpdb->posts.post_status = 'publish'
ORDER BY datetime LIMIT 0, 50";
$events = $wpdb->get_results($sql);

foreach($events as $event) {
$confirm = get_post_meta($event->postID,'_rsvp_confirm',true);
$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
$titledate = $event->post_title.' '.$prettydate;

$s = (isset($_REQUEST["post_id"]) && $_REQUEST["post_id"] == $event->postID ) ? ' selected="selected" ' : '';

$options .= sprintf('<option value="%d" %s>%s</option>',$event->postID, $s, $titledate);
}

// past events
$past_options = '<optgroup label="' .__('Recent Events','rsvpmaker').'">';
$sql = "SELECT *, $wpdb->posts.ID as postID, meta_value as datetime
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE meta_value > DATE_SUB(CURDATE(),INTERVAL 1 WEEK) && meta_value < CURDATE( ) AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value DESC LIMIT 0, 50";
$events = $wpdb->get_results($sql);
foreach ($events as $event)
{
	$prettydate = date('l F jS g:i A T',strtotime($event->datetime));
$titledate = $event->post_title.' '.$prettydate;

$s = (isset($_REQUEST["post_id"]) && $_REQUEST["post_id"] == $event->postID ) ? ' selected="selected" ' : '';

$past_options .= sprintf('<option value="%d" %s>%s</option>',$event->postID, $s, $titledate);
}
$past_options .= '</optgroup>';

?>
<h3><?php _e('Edit Confirmation Message','rsvpmaker'); ?></h3>
<form method="get" action = "<?php echo admin_url('edit.php'); ?>">
<input type="hidden" name="post_type" value="rsvpmaker" >
<input type="hidden" name="page" value="rsvp_reminders" >
<input type="hidden" name="message_type" value="confirmation" >
<p><select name="post_id"><?php echo $options; ?></select></p>
<p><button><?php _e('Load','rsvpmaker');?></button></p>
</form>

<h3><?php _e('Create Reminder Message','rsvpmaker'); ?></h3>
<form method="get" action = "<?php echo admin_url('edit.php'); ?>">
<p><?php _e('Timing','rsvpmaker');?>: <select name="hours"><?php echo rsvp_reminder_options(); ?></select></p>
<input type="hidden" name="post_type" value="rsvpmaker" >
<input type="hidden" name="page" value="rsvp_reminders" >
<input type="hidden" name="message_type" value="reminder" >
<p><select name="post_id"><?php echo $options; echo $past_options; ?></select></p>
<p><button><?php _e('Load','rsvpmaker');?></button></p>
</form>

<h3><?php _e('Webinar Setup','rsvpmaker'); ?></h3>
<form method="get" action = "<?php echo admin_url('edit.php'); ?>">
<p><?php _e('This utility sets up a landing page and suggested confirmation and reminder messages, linked to that page. RSVPMaker explicitly supports webinars based on YouTube Live, but you can also embed the coding required for another webinar of your choice.','rsvpmaker'); ?></p>
<input type="hidden" name="post_type" value="rsvpmaker" >
<input type="hidden" name="page" value="rsvp_reminders" >
<input type="hidden" name="webinar" value="1" >
<p><select name="post_id"><?php echo $options; ?></select></p>
<p>YouTube Live url: <input type="text" name="youtube_live" value=""> <input type="checkbox" name="youtube_require_passcode" value="1" /> <?php _e('Require passcode to view','rsvpmaker');?></p>
<p><button><?php _e('Create','rsvpmaker');?></button></p>
</form>

<h3><?php _e('A Note on More Reliable Scheduling','rsvpmaker');?></h3>
<p><?php _e('RSVPMaker takes advantage of WP Cron, a standard WordPress scheduling mechanism. Because it only checks for scheduled tasks to be run when someone visits your website, WP Cron can be imprecise -- which could be a problem if you want to make sure a reminder will go out an hour before your event, if that happens to be a low traffic site. Caching plugins can also get in the way of regular WP Cron execution. Consider following <a href="http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119">these directions</a> to make sure your server checks for scheduled tasks to run on a more regular schedule, like once every 5 or 15 minutes.','rsvpmaker');?></p>

<p><?php _e('Using Unix cron, the command you would set to execute would be','rsvpmaker');?>:</p>
<code>
curl <?php echo site_url('/wp-cron.php?doing_wp_cron=1');?> > /dev/null 2>&1
</code>
<p><?php _e('If curl does not work, you can also try this variation (seems to work better on some systems)','rsvpmaker');?>:</p>
<code>
wget -qO- <?php echo site_url('/wp-cron.php?doing_wp_cron=1');?>  &> /dev/null
</code>
</div>
<?php
}

add_action('admin_menu', 'reminder_events_menu');

function rsvpmaker_placeholder_image () {
if(!isset($_GET["rsvpmaker_placeholder"]))
	return;
$impath = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'placeholder.png';
$im = imagecreatefrompng($impath);
if(!$im)
{
$im = imagecreate(800, 50);
imagefilledrectangle($im,5,5,790,45, imagecolorallocate($im, 50, 50, 255));
}

// White background and blue text
$bg = imagecolorallocate($im, 200, 200, 255);
$border = imagecolorallocate($im, 0, 0, 0);
$textcolor = imagecolorallocate($im, 255, 255, 255);

$text = __('Events','rsvpmaker').': ';
$tip = '('.__('double-click for popup editor','rsvpmaker').')';

foreach ($_GET as $name => $value)
	{
	if($name == 'rsvpmaker_placeholder')
		continue;
	$text .= $name.'='.$value.' '; 
	}

// Write the string at the top left
imagestring($im, 5, 10, 10, $text, $textcolor);
imagestring($im, 5, 10, 25, $tip, $textcolor);

// Output the image
header('Content-type: image/png');

imagepng($im);
imagedestroy($im);
exit();
}

add_action('init','rsvpmaker_placeholder_image');

function rsvpmaker_admin_enqueue($hook) {
global $post;
$rsvppost = array('post.php','post-new.php','options-general.php');
	if(in_array($hook,$rsvppost) || (isset($_GET["page"]) && ($_GET["page"] == 'rsvpmaker-admin.php') ) )
		{
		wp_enqueue_script( 'rsvpmaker_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js',array(),'4.1' );
		wp_enqueue_style( 'rsvpmaker_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css',array(),'4.1' );
		wp_enqueue_script('jquery-ui-dialog');
		//wp_enqueue_style('jquery-ui-dialog-css',includes_url('/css/jquery-ui-dialog.min.css'));
		wp_enqueue_style( 'rsvpmaker_jquery_ui', plugin_dir_url( __FILE__ ) . 'jquery-ui.css',array(),'4.1' );
		wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
		}
}
add_action( 'admin_enqueue_scripts', 'rsvpmaker_admin_enqueue' );

function rsvp_mce_buttons( $buttons ) {
	global $post;
	if(empty($post)) return $buttons;
	if(($post->post_type=='rsvpmaker') || (isset($_GET["post_type"]) && ($_GET["post_type"] == 'rsvpmaker')) )
		return $buttons;
    array_push( $buttons, 'rsvpmaker_upcoming' );
    return $buttons;
}
add_filter( 'mce_buttons', 'rsvp_mce_buttons' );

function rsvp_mce_plugins ( $plugin_array ) {
	global $post;
	if(empty($post)) return $plugin_array;
	if(($post->post_type=='rsvpmaker') || (isset($_GET["post_type"]) && ($_GET["post_type"] == 'rsvpmaker')) )
		return $plugin_array;
	
    $plugin_array['rsvpmaker_upcoming'] = plugins_url( 'mce.js' , __FILE__ );
    return $plugin_array;
}
add_filter( 'mce_external_plugins', 'rsvp_mce_plugins');

add_action('admin_head','rsvpmaker_upcoming_admin_js');
function rsvpmaker_upcoming_admin_js() {

    global $current_screen;
	global $post;
	global $wp_query;
	global $wpdb;
	global $showbutton;
	global $startday;
	global $rsvp_options;
	
	$showbutton = true;
	
	$backup = $wp_query;

    $type = $current_screen->post_type;

    if (is_admin() && $type != 'rsvpmaker') {
     
	 	$sql = "SELECT *, $wpdb->postmeta.meta_value as datetime, $wpdb->posts.ID as postID, 1 as current
FROM `".$wpdb->postmeta."`
JOIN $wpdb->posts ON ".$wpdb->postmeta.".post_id = $wpdb->posts.ID AND meta_key='_rsvp_dates'
WHERE  meta_value >= NOW() AND $wpdb->posts.post_status = 'publish'
ORDER BY meta_value";
	 
	 $results = $wpdb->get_results($sql);
	 $row[] = "{text: 'Pick One?', value: '0'}";
	 foreach ($results as $r)
	 	$row[] = sprintf("{text: '%s', value: '%d'}",addslashes($r->post_title).' '.date('r',strtotime($r->datetime)),$r->ID);   

$terms = get_terms('rsvpmaker-type');
$t[] = "{text: 'None', value: ''}";
if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
     foreach ( $terms as $term ) {
       $t[] = sprintf("{text: '%s', value: '%s'}",$term->name,$term->slug);
     }
}
		?>
        <script type="text/javascript">
        var upcoming = [<?php echo implode(",\n",$row); ?>];
        var rsvpmaker_types = [<?php echo implode(",\n",$t); ?>];
        </script>
        <?php
    }
}

function rsvpmaker_clone_title($title) {
	if(isset($_GET["clone"]))
		{
			$id = (int) $_GET["clone"];
			$clone = get_post($id);
			$title = $clone->post_title;
		}
	return $title;
}
add_filter('default_title','rsvpmaker_clone_title');

function rsvpmaker_clone_content ($content) {
	if(isset($_GET["clone"]))
		{
			$id = (int) $_GET["clone"];
			$clone = get_post($id);
			$content = $clone->post_content;
		}
	return $content;
}
add_filter('default_content','rsvpmaker_clone_content');

function export_rsvpmaker () {
//pack data from custom tables into wordpress metadata
global $wpdb;
$results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'rsvpmaker ORDER BY event',ARRAY_A);
if($results)
	{
	foreach($results as $row)
		{
			array_shift($row); // id becomes irrelevant
			$events[$row['event']][] = $row; 		
		}
	foreach($events as $event => $meta)
		update_post_meta($event,'_export_rsvpmaker',$meta);
	}
$results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'rsvp_volunteer_time ORDER BY event',ARRAY_A);
if($results)
	{
	foreach($results as $row)
		{
			array_shift($row); // id becomes irrelevant
			$v[$row['event']][] = $row; 		
		}
	foreach($v as $event => $meta)
		update_post_meta($event,'_export_rsvp_volunteer_time',$meta);
	}

}
add_action('export_wp','export_rsvpmaker');

function import_rsvpmaker() {
global $wpdb;
// import routine (transfer from another site)

global $wpdb;
$wpdb->show_errors();

$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key='_export_rsvpmaker' ");
if($results)
{
foreach($results as $row)
	{
	$data = unserialize($row->meta_value);
	foreach($data as $newrow)
	{
	$sql = "INSERT INTO ".$wpdb->prefix.'rsvpmaker SET ';
	$count = 0;
	foreach($newrow as $key => $value)
		{
		if($count)
			$sql .= ', ';
		$sql .= $wpdb->prepare("`$key` = %s",$value);
		$count++;
		}
	$wpdb->query($sql);
	}
	
	}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='_export_rsvpmaker' ");
}

$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key='_export_rsvp_volunteer_time' ");
if($results)
{
foreach($results as $row)
	{
	$data = unserialize($row->meta_value);
	foreach($data as $newrow)
	{
	$sql = "INSERT INTO ".$wpdb->prefix.'rsvp_volunteer_time SET ';
	$count = 0;
	foreach($newrow as $key => $value)
		{
		if($count)
			$sql .= ', ';
		$sql .= $wpdb->prepare("`$key` = %s",$value);
		$count++;
		}
	$wpdb->query($sql);
	}
	
	}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='_export_rsvp_volunteer_time' ");
}

}

add_action('import_end','import_rsvpmaker');

add_action('wp_ajax_rsvpmaker_paypal_config','rsvpmaker_paypal_config_ajax');

function rsvpmaker_paypal_config_ajax () {
$filename = rsvpmaker_paypal_config_write($_POST["user"],$_POST["password"],$_POST["signature"]);
die($filename);
}

function rsvpmaker_paypal_config_write($user,$password,$signature) {
$up = wp_upload_dir();
$filename = trailingslashit($up['path']);
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    for ($i = 0; $i < 20; $i++) {
        $filename .= $characters[rand(0, $charactersLength - 1)];
    }
$filename .= '.php';

$paypal_config_template = sprintf("<?php
if( !defined( 'ABSPATH' ) )
	die( 'Fatal error: Call to undefined function paypal_setup() in %s on line 5' );
define('API_USERNAME', '%s');
define('API_PASSWORD', '%s');
define('API_SIGNATURE', '%s');
define('API_ENDPOINT', 'https://api-3t.paypal.com/nvp');
define('USE_PROXY',FALSE);
define('PROXY_HOST', '127.0.0.1');
define('PROXY_PORT', '808');
define('PAYPAL_URL', 'https://www.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=');
define('VERSION', '3.0');
?>",$filename,$user,$password,$signature);
$myfile = fopen($filename, "w") or die("Unable to open file!");
fwrite($myfile, $paypal_config_template);
fclose($myfile);
//echo $filename . "<br />";
//echo '<p><textarea rows="10" cols="80">'.$paypal_config_template.'</textarea></p>';
update_option('paypal_config',$filename);
return $filename;
}

function future_rsvpmakers_by_template($template_id) {
	$ids = array();
	$sched_result = get_events_by_template($template_id);
	if($sched_result)
	foreach($sched_result as $row)
		$ids[] = $row->ID;
	return $ids;
}

function rsvptimes ($time,$fieldname) {
$timearray = array(
'00:00:00' => __('12 am','rsvpmaker'),
'01:00:00' => __('1 am','rsvpmaker'),
'02:00:00' => __('2 am','rsvpmaker'),
'03:00:00' => __('3 am','rsvpmaker'),
'04:00:00' => __('4 am','rsvpmaker'),
'05:00:00' => __('5 am','rsvpmaker'),
'06:00:00' => __('6 am','rsvpmaker'),
'07:00:00' => __('7 am','rsvpmaker'),
'08:00:00' => __('8 am','rsvpmaker'),
'09:00:00' => __('9 am','rsvpmaker'),
'10:00:00' => __('10 am','rsvpmaker'),
'11:00:00' => __('11 am','rsvpmaker'),
'12:00:00' => __('12 pm','rsvpmaker'),
'13:00:00' => __('1 pm','rsvpmaker'),
'14:00:00' => __('2 pm','rsvpmaker'),
'15:00:00' => __('3 pm','rsvpmaker'),
'16:00:00' => __('4 pm','rsvpmaker'),
'17:00:00' => __('5 pm','rsvpmaker'),
'18:00:00' => __('6 pm','rsvpmaker'),
'19:00:00' => __('7 pm','rsvpmaker'),
'20:00:00' => __('8 pm','rsvpmaker'),
'21:00:00' => __('9 pm','rsvpmaker'),
'22:00:00' => __('10 pm','rsvpmaker'),
'23:00:00' => __('11 pm','rsvpmaker'),
'23:59:59' => __('midnight','rsvpmaker')  );

printf('<select name="%s">',$fieldname);
foreach($timearray as $index => $value)
	{
	$s = ($index == $time) ? ' selected="selected" ' : '';
	printf('<option value="%s" %s>%s</option>',$index,$s,$value);
	}
echo '</select>';
}

function rsvpmaker_add_one () {

if(!empty($_POST["rsvpmaker_add_one"]))
{
global $wpdb;

$t = (int) $_POST["template"];
$post = get_post($t);
$template = get_post_meta($t,'_sked',true);
$hour = (isset($template["hour"]) ) ? (int) $template["hour"] : 17;
$minutes = isset($template["minutes"]) ? $template["minutes"] : '00';

	$my_post['post_title'] = $post->post_title;
	$my_post['post_content'] = $post->post_content;
	$my_post['post_status'] = 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';
	foreach($_POST["recur_check"] as $index => $on)
		{
			$year = $_POST["recur_year"][$index];
			$cddate = format_cddate($year, $_POST["recur_month"][$index], $_POST["recur_day"][$index], $hour, $minutes);
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
				$duration = $template["duration"];
			
			$my_post['post_name'] = sanitize_title($my_post['post_title'] . '-' .$date );
				
			$singular = __('Event','rsvpmaker');
// Insert the post into the database
  			if($postID = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($postID,$cddate,$duration);				
				add_post_meta($postID,'_meet_recur',$t,true);
				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$postID);
				update_post_meta($postID,"_updated_from_template",$ts);

				wp_set_object_terms( $postID, $rsvptypes, 'rsvpmaker-type', true );

				$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_rsvp%' AND post_id=".$t);
				if($results)
				foreach($results as $row)
					{
					if($row->meta_key == '_rsvp_reminder')
						continue;
					$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta SET meta_key=%s,meta_value=%s,post_id=%d",$row->meta_key,$row->meta_value,$postID));
					}
				//copy rsvp options
				$editurl = admin_url('post.php?action=edit&post='.$postID);
				wp_redirect($editurl);
				}		
		break;
		}
	}
}//end rsvpmaker_add_one
add_action('admin_init','rsvpmaker_add_one');

function rsvpmaker_admin_page_top($headline) {

/*
$hook = rsvpmaker_admin_page_top(__('Headline','rsvpmaker'));
rsvpmaker_admin_page_bottom($hook);
*/
$hook = '';
if(is_admin()) { // if not full screen view
	$screen = get_current_screen();
	$hook = $screen->id;
}

$print = (isset($_GET["page"]) && !isset($_GET["rsvp_print"])) ? '<div style="width: 200px; text-align: right; float: right;"><a target="_blank" href="'.admin_url(str_replace('/wp-admin/','',$_SERVER['REQUEST_URI'])).'&rsvp_print=1">Print</a></div>' : '';
printf('<div id="wrap" class="%s toastmasters">%s<h1>%s</h1>',$hook,$print,$headline);
return $hook;
}

function rsvpmaker_admin_page_bottom($hook = '') {
if(is_admin() && empty($hook))
	{
	$screen = get_current_screen();
	$hook = $screen->id;
	}
printf("\n".'<hr /><p><small>%s</small></p></div>',$hook);
}

?>