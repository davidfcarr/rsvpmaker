<?php
/*
* Backend functions
*/
add_filter('default_title','rsvpmaker_title_from_template');
add_filter('manage_posts_columns', 'rsvpmaker_columns');

function rsvpmaker_date_slug($data) {
	if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
		return $data;

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

add_filter('wp_insert_post_data', 'rsvpmaker_date_slug', 10);

function rsvpmaker_unique_date_slug($slug, $post_ID = 0, $post_status = '', $post_type = '', $post_parent = 0, $original_slug='' )
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
	$newslug = sanitize_text_field($post->post_title.'-' .$date);
	return $newslug;
	}

add_filter('wp_unique_post_slug','rsvpmaker_unique_date_slug',10);

function rsvpmaker_save_calendar_data($post_id) {
	if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
		return;

	global $wpdb, $current_user;
	$end_array = array();

	if($parent_id = wp_is_post_revision($post_id))
		{
		$post_id = $parent_id;
		}
	if(rsvpmaker_is_template($post_id))
	{
		$args = array($post_id,$current_user->user_email);
		wp_clear_scheduled_hook( 'rsvpmaker_create_update_reminder', $args );
		wp_schedule_single_event( strtotime('+2 hours'), 'rsvpmaker_create_update_reminder', $args);
	}
	if(isset($_POST["_require_webinar_passcode"]))
		{
		update_post_meta($post_id,'_require_webinar_passcode',sanitize_text_field($_POST["_require_webinar_passcode"]));
		}
	if(isset($_POST["event_month"]) )
		{
		foreach($_POST["event_year"] as $index => $year)
			{
			$year = (int) $year;
			if(isset($_POST["event_day"][$index]) && $_POST["event_day"][$index])
				{
				$cddate = format_cddate($year,(int) $_POST["event_month"][$index], (int) $_POST["event_day"][$index], (int) $_POST["event_hour"][$index], (int) $_POST["event_minutes"][$index]);
				$dpart = explode(':',$_POST["event_duration"][$index]);
				if( is_numeric($dpart[0]) )
					{
					$hour = intval($_POST["event_hour"][$index]) + intval($dpart[0]);
					$minutes = (isset($dpart[1]) ) ? intval($_POST["event_minutes"][$index]) + intval($dpart[1]) : sanitize_text_field($_POST["event_minutes"][$index]);
					$t = rsvpmaker_mktime( $hour, $minutes,0,intval($_POST["event_month"][$index]),intval($_POST["event_day"][$index]),$year);
					$duration = rsvpmaker_date('Y-m-d H:i:s',$t);
					}
				else
					$duration = sanitize_text_field($_POST["event_duration"][$index]); // empty or all day
				if($duration == 'set')
					$end_array[] = sanitize_text_field($_POST["hourevent_duration"][$index]).':'.sanitize_text_field($_POST["minevent_duration"][$index]);
				$dates_array[] = $cddate;
				$durations_array[] = $duration;
				}
			}
		}

	if(isset($_POST["edit_month"]))
		{
		delete_transient('rsvpmakerdates');//invalidate cached values
		foreach($_POST["edit_year"] as $index => $year)
			{
				$year = (int) $year;
				$cddate = format_cddate(intval($year),intval($_POST["edit_month"][$index]), intval($_POST["edit_day"][$index]), intval($_POST["edit_hour"][$index]), intval($_POST["edit_minutes"][$index]));
				if(strpos( $_POST["edit_duration"][$index],':' ))
					{
					$dpart = explode(':',sanitize_text_field($_POST["edit_duration"][$index]));
					if( is_numeric($dpart[0]) )
						{
						$hour = intval($_POST["edit_hour"][$index]) + intval($dpart[0]);
						$minutes = (isset($dpart[1]) ) ? intval($_POST["edit_minutes"][$index]) + intval($dpart[1]) : intval($_POST["edit_minutes"][$index]);
						//dchange
						$duration = rsvpmaker_date('Y-m-d H:i:s',rsvpmaker_mktime( $hour, $minutes,0,intval($_POST["edit_month"][$index]),intval($_POST["edit_day"][$index]),$year));
						}
					}
				elseif( is_numeric($_POST["edit_duration"][$index]) )
					{
					$d_duration = (int) $_POST["edit_duration"][$index];
					$minutes = (int) $_POST["edit_minutes"][$index];				
					$minutes = $minutes + (60*$d_duration);
					//dchange - can this be removed?
					$duration = rsvpmaker_date('Y-m-d H:i:s',rsvpmaker_mktime( sanitize_text_field($_POST["edit_hour"][$index]), $minutes,0,sanitize_text_field($_POST["edit_month"][$index]),sanitize_text_field($_POST["edit_day"][$index]),$year));
					}
				else
					$duration = sanitize_text_field($_POST["edit_duration"][$index]); // empty or all day			
				if(($duration == 'set') || strpos($duration,'|') )
					$end_array[] = sanitize_text_field($_POST["houredit_duration"][$index]).':'.sanitize_text_field($_POST["minedit_duration"][$index]);
				$dates_array[] = $cddate;
				$durations_array[] = $duration;
				}
		} // end edit month

		if(isset($_POST["setrsvp"]) )
		{ // if rsvp parameters were set, was RSVP box checked?
		if(isset($_POST["setrsvp"]["on"]))
			update_post_meta($post_id, '_rsvp_on', (int) $_POST["setrsvp"]["on"]);
		}

		if(isset($_POST['payment_gateway']))
			update_post_meta($post_id, 'payment_gateway', sanitize_text_field($_POST["payment_gateway"]));

		if(isset($_POST["sked"]["week"]))
			{
			save_rsvp_template_meta($post_id);
			}
		if(!isset($_POST["sked"]) && !isset($_POST["setrsvp"]))
			return;
		if(isset($_POST['add_timezone']) && $_POST['add_timezone'])
			update_post_meta($post_id,'_add_timezone',1);
		else
			update_post_meta($post_id,'_add_timezone',0);	
		if(isset($_POST['convert_timezone']) && $_POST['convert_timezone'])
			update_post_meta($post_id,'_convert_timezone',1);
		else
			update_post_meta($post_id,'_convert_timezone',0);	

		if(isset($_POST['calendar_icons']) && $_POST['calendar_icons'])
			update_post_meta($post_id,'_calendar_icons',1);
		else
			update_post_meta($post_id,'_calendar_icons',0);
}

add_action('rsvpmaker_create_update_reminder','rsvpmaker_create_update_reminder',10,3);
function rsvpmaker_create_update_reminder($t, $author_email = '') {
	$template = get_post($t);
	$output = '';
	$sched_result = get_events_by_template($t);
	if(empty($sched_result))
		return;//no events to update
	$nag = true;
	foreach($sched_result as $event) {
		$updated_from_template = get_post_meta($event->ID,"_updated_from_template",true);
		if($updated_from_template >= $template->post_modified)
			$nag = false; // at least one event has been updated
		$up = ($updated_from_template < $template->post_modified) ? 'not updated'." $updated_from_template < $template->post_modified " : 'updated';
		$output .= sprintf('%d %s ',$event->ID, $up);
	}
	if($nag) {
		$mail['html'] = sprintf('<p>You updated the <strong>%s</strong> template but not the events based on that template.</p>'."\n".'<p>To update the whole series, use <a href="%s">Create/Update<a></p>',$template->post_title,admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$t));
		$mail['to'] = get_option('admin_email');
		$mail['from'] = get_option('admin_email');
		$mail['subject'] = __('Event template not applied to existing events:','rsvpmaker').' '.$template->post_title;
		rsvpmailer($mail);
		if($author_email != $mail['to'])
		{
			$mail['to'] = $author_email;
			rsvpmailer($mail);
		}
	}
}

function rsvpmaker_date_option($datevar = NULL, $index = NULL, $jquery_date = NULL, $sked = NULL) {

global $rsvp_options;
$prefix = "event_";

if(is_int($datevar))
	{
	$t = $datevar;
	$datevar = array();
	}
elseif(is_array($datevar) )
{
	$datestring = $datevar["datetime"];
	//dchange - check this
	$duration = $datevar["duration"];
	if(strpos($duration,':'))
		{
		$datevar['end_time'] = rsvpmaker_date('H:i',rsvpmaker_strtotime($duration));
		$datevar['duration'] = 'set';
		}
	$prefix = "edit_";
	if(isset($datevar["id"]))
		$index = $datevar["id"];
}
else
{
	$datestring = $datevar;
	$datevar = array();
}
if(!empty($datestring))
	{
	$datestring = str_replace('Every','Next',$datestring);
	$t = rsvpmaker_strtotime($datestring);
	}

$endtime = (is_array($sked) && isset($sked['end'])) ? rsvpmaker_strtotime('2000-01-01 '. $sked['end']) : $t+HOUR_IN_SECONDS;

?>
<div id="<?php echo esc_attr($prefix); ?>date<?php echo esc_attr($index);?>" ><input type="hidden" id="defaulthour" value="<?php echo esc_attr($rsvp_options["defaulthour"]); ?>" /><input type="hidden" id="defaultmin" value="<?php echo esc_attr($rsvp_options["defaultmin"]); ?>" />
<p><label>Date</label> <input type="date" name="newrsvpdate" id="newrsvpdate" value="<?php echo date('Y-m-d',$t); ?>"> 
</p>
<p><label>Time</label> <input name="newrsvptime" type="time" class="newrsvptime" id="newrsvptime" value="<?php echo rsvpmaker_date('H:i:s',$t) ?>"><span id="endtimespan"> to <input name="rsvpendtime" type="time" class="rsvpendtime" id="rsvpendtime" value="<?php echo rsvpmaker_date('H:i:s',$endtime) ?>"> </span>
</p>

<?php
if(!empty($sked['duration']))
	$datevar['duration'] = $sked['duration'];
if(empty($datestring))
	$datestring ='';
rsvpmaker_duration_select ($prefix.'duration['.$index.']', $datevar, $datestring, $index );

?>
</div>
<?php

}

function rsvpmaker_date_option_event($t, $endtime, $type) {

	global $rsvp_options;
	$prefix = "edit_";
	$index = 0;
	?>
	<div id="<?php echo esc_attr($prefix); ?>date<?php echo esc_attr($index);?>" ><input type="hidden" id="defaulthour" value="<?php echo esc_attr($rsvp_options["defaulthour"]); ?>" /><input type="hidden" id="defaultmin" value="<?php echo esc_attr($rsvp_options["defaultmin"]); ?>" />
	<p><label>Date</label> <input type="date" name="newrsvpdate" id="newrsvpdate" value="<?php echo date('Y-m-d',$t); ?>"> 
	</p>
	<p><label>Time</label> <input name="newrsvptime" type="time" class="newrsvptime" id="newrsvptime" value="<?php echo rsvpmaker_date('H:i:s',$t) ?>"><span id="endtimespan"> to <input name="rsvpendtime" type="time" class="rsvpendtime" id="rsvpendtime" value="<?php echo rsvpmaker_date('H:i:s',$endtime) ?>"> </span>
	</p>

	<?php
	rsvpmaker_duration_select_2021 ($type);
	?>
	</div>
	<?php
}

function save_rsvp_meta($post_id, $new = false)
{
if( !wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	return;
$setrsvp = array_map('sanitize_text_field',$_POST["setrsvp"]);
if($new)
{
rsvpmaker_defaults_for_post($post_id); 
}
else
{
	$checkboxes = array("show_attendees","count","captcha","login_required",'confirmation_include_event','rsvpmaker_send_confirmation_email','yesno','confirmation_after_payment');
	foreach($checkboxes as $check)
		{
			if(!isset($setrsvp[$check]))
				$setrsvp[$check] = 0;
		}	
}

if(isset($_POST["deadyear"]) && isset($_POST["deadmonth"]) && isset($_POST["deadday"]))
	{
	if(empty(trim($_POST["deadday"])))
		$setrsvp["deadline"] = '';
	else
		$setrsvp["deadline"] = rsvpmaker_strtotime(sanitize_text_field($_POST["deadyear"]).'-'.sanitize_text_field($_POST["deadmonth"]).'-'.sanitize_text_field($_POST["deadday"]).' '.sanitize_text_field($_POST["deadtime"]));
	}

if(isset($_POST["startyear"]) && isset($_POST["startmonth"]) && isset($_POST["startday"]))
	{
	if(empty(trim($_POST["startday"])))
		$setrsvp["start"] = '';
	else
		$setrsvp["start"] = rsvpmaker_strtotime(sanitize_text_field($_POST["startyear"].'-'.$_POST["startmonth"].'-'.$_POST["startday"].' '.$_POST["starttime"]));
	}

foreach($setrsvp as $name => $value)
	{
	$field = '_rsvp_'.$name;
	$single = true;
	update_post_meta($post_id, $field, sanitize_text_field($value));
	}

if(isset($_POST["unit"]))
	{

	foreach($_POST["unit"] as $index => $value)
		{
		$value = sanitize_text_field($value);
		if(empty($value))
			continue;
		if( empty($_POST["price"][$index]) && ($_POST["price"][$index] != 0) )
			continue;
		$per["unit"][$index] = $value;
		$per["price"][$index] = sanitize_text_field($_POST["price"][$index]);
		if(isset($_POST["price_multiple"][$index]) && ($_POST["price_multiple"][$index] > 1))
			$per["price_multiple"][$index] = sanitize_text_field($_POST["price_multiple"][$index]);
		if(!empty($_POST["price_deadline"][$index]))
			{
			$per["price_deadline"][$index] = rsvpmaker_strtotime(sanitize_text_field($_POST["price_deadline"][$index]));			
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
			update_post_meta($post_id, '_hiddenrsvpfields', $pricehide);
		}

	$value = $per;
	$field = "_per";

	$current = get_post_meta($post_id, $field, $single); 

	if($value && ($current == "") )
		add_post_meta($post_id, $field, $value, true);

	elseif($value != $current)
		update_post_meta($post_id, $field, $value);

	elseif($value == "")
		delete_post_meta($post_id, $field, $current);

	}
	if(!empty($_POST["youtube_live"]) || !empty($_POST["webinar_other"]))
		{
		$ylive = sanitize_text_field($_POST["youtube_live"]);
		unset($_POST);
		rsvpmaker_youtube_live($post_id, $ylive);
		}

		if(isset($_POST['coupon_code']))

		{

		delete_post_meta($post_id,'_rsvp_coupon_code');

		delete_post_meta($post_id,'_rsvp_coupon_discount');

		delete_post_meta($post_id,'_rsvp_coupon_method');

		foreach($_POST['coupon_code'] as $index => $value)

		{

			$value = sanitize_text_field($value);

			$discount = sanitize_text_field($_POST['coupon_discount'][$index]);

			if(!empty($value) && is_numeric($discount))

			{

				$method = sanitize_text_field($_POST['coupon_method'][$index]);

				add_post_meta($post_id,'_rsvp_coupon_code',$value);

				add_post_meta($post_id,'_rsvp_coupon_discount',$discount);

				add_post_meta($post_id,'_rsvp_coupon_method',$method);		

			}
		}

		}
}

function rsvpmaker_menu_security($label, $slug,$options) {
echo esc_html($label);
?>
 <select name="security_option[<?php echo esc_attr($slug); ?>]" id="<?php echo esc_attr($slug); ?>">
  <option value="manage_options" <?php if(isset($options[$slug]) && ($options[$slug] == 'manage_options')) echo ' selected="selected" ';?> ><?php esc_html_e('Administrator','rsvpmaker');?> (manage_options)</option>
  <option value="edit_others_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'edit_others_rsvpmakers')) echo ' selected="selected" ';?>><?php esc_html_e('Editor','rsvpmaker');?> (edit_others_rsvpmakers)</option>
  <option value="publish_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'publish_rsvpmakers')) echo ' selected="selected" ';?> ><?php esc_html_e('Author','rsvpmaker');?> (publish_rsvpmakers)</option>
  <option value="edit_rsvpmakers" <?php if(isset($options[$slug]) && ($options[$slug] == 'edit_rsvpmakers')) echo ' selected="selected" ';?> ><?php esc_html_e('Contributor','rsvpmaker');?> (edit_rsvpmakers)</option>
  </select><br />
<?php
}

  // Avoid name collisions.
class RSVPMAKER_Options
      {
          // this variable will hold url to the plugin  
          var $plugin_url;

          // name for our options in the DB
          var $db_option = 'RSVPMAKER_Options';

          // Initialize the plugin
          function __construct()
          {
              $this->plugin_url = plugins_url('',__FILE__).'/';

              // add options Page
              add_action('admin_menu', array(&$this, 'admin_menu'));

          }

          // hook the options page
          function admin_menu()
          {
			add_options_page('RSVPMaker', 'RSVPMaker', 'manage_options', 'rsvpmaker_settings', 'rsvpmaker_react_admin', 17);
			add_options_page('RSVPMaker Postmark + Advanced Email', 'RSVPMaker Postmark + Advanced Email', 'manage_options', basename(__FILE__), array(&$this, 'handle_options'), 18);
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
			if(!empty($_POST) && !isset($_POST['rsvpelist']) && !wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
				wp_die('nonce security error');
			if(!empty($_POST['rsvpmaker_save_form']) && !empty($_POST['form_id']))
			{
				$forms = rsvpmaker_get_forms();
				$forms[sanitize_text_field($_POST['rsvpmaker_save_form'])] = (int) $_POST['form_id'];
				update_option('rsvpmaker_forms',$forms);
			}

			$options = $this->get_options();
			  if(isset($_POST["payment_option"])) {
              $newoptions = array_map('sanitize_text_field',stripslashes_deep($_POST["payment_option"]));
				$newoptions["stripe"] = (isset($_POST['payment_gateway']) && ($_POST['payment_gateway'] == 'stripe')) ? 1 : 0;
				$newoptions["cash_or_custom"] = (isset($_POST['payment_gateway']) && ($_POST['payment_gateway'] == 'cash_or_custom')) ? 1 : 0;
				$nfparts = explode('|',$_POST["currency_format"]);
				$newoptions["currency_decimal"] = sanitize_text_field($nfparts[0]);
				$newoptions["currency_thousands"] = sanitize_text_field($nfparts[1]);

				foreach($newoptions as $name => $value)
				  $options[$name] = sanitize_text_field($value);

                  update_option($this->db_option, $options);

				  if(isset($_POST['rsvpmaker_stripe_keys']))
				  {
					//don't overwrite keys that are not displayed
					$stripe_keys = array_map( 'sanitize_text_field', $_POST['rsvpmaker_stripe_keys']);

					if(!isset($stripe_keys['sk']) || !isset($stripe_keys['sandbox_pk']))
					{
						$prev = get_option('rsvpmaker_stripe_keys');
						if(!isset($stripe_keys['sk']))
							{
								$stripe_keys['sk'] = $prev['sk'];
								$stripe_keys['pk'] = $prev['pk'];
							}
						if(!isset($stripe_keys['sandbox_sk']))
						{
							$stripe_keys['sandbox_sk'] = $prev['sandbox_sk'];
							$stripe_keys['sandbox_pk'] = $prev['sandbox_pk'];
						}
					}
					update_option('rsvpmaker_stripe_keys',$stripe_keys);
				  }
				if(isset($_POST['rsvpmaker_paypal_rest_keys']))
				{
					$pkeys = array_map( 'sanitize_text_field', $_POST['rsvpmaker_paypal_rest_keys']);

					if(!isset($pkeys['client_id']) || !isset($keys['sandbox_client_id']))
					{
						$prev = get_option('rsvpmaker_paypal_rest_keys');
						if(!isset($pkeys['client_id']))
							{
								$pkeys['client_id'] = $prev['client_id'];
								$pkeys['client_secret'] = $prev['client_secret'];
							}
						if(!isset($pkeys['sandbox_client_id']))
						{
							$pkeys['sandbox_client_id'] = $prev['sandbox_client_id'];
							$pkeys['sandbox_client_secret'] = $prev['sandbox_client_secret'];
					}
					}
				update_option('rsvpmaker_paypal_rest_keys',$pkeys);
				}

				  $paypal_rest_keys = get_option('rsvpmaker_paypal_rest_keys');

                  echo '<div class="updated fade"><p>'.__('Plugin settings saved - payments.','rsvpmaker').' <a href="'.admin_url('options-general.php?page=rsvpmaker-admin.php&payment_key_test=1').'">',__('Test Keys','rsvpmaker').'</a></p>'.default_gateway_check( get_rsvpmaker_payment_gateway () ).'</div>';
			  }	

			  if(isset($_POST["enotify_option"])) {
				  //print_r($_POST["enotify_option"]);
				$newoptions = array_map( 'sanitize_text_field', stripslashes_deep($_POST["enotify_option"] ) );
				foreach($newoptions as $name => $value)
				  $options[$name] = sanitize_text_field($value);

                  update_option($this->db_option, $options);
                  echo '<div class="updated fade"><p>'.__('Plugin settings saved - email server.','rsvpmaker').'</p></div>';
			  }	

			  if(isset($_POST["security_option"])) {
				foreach($_POST["security_option"] as $index => $value) {
					$newoptions[sanitize_text_field($index)] = sanitize_text_field($value);
				}
				$newoptions["additional_editors"] = (isset($_POST["security_option"]["additional_editors"]) && $_POST["security_option"]["additional_editors"]) ? 1 : 0;
				foreach($newoptions as $name => $value)
				  $options[$name] = sanitize_text_field($value);
                update_option($this->db_option, $options);
                  echo '<div class="updated fade"><p>'.__('Plugin settings saved - security.','rsvpmaker').'</p></div>';
				  //print_r($newoptions);
			  }	

			  if (isset($_POST['submitted'])) {

                  $newoptions = stripslashes_deep($_POST["option"]);
                  $newoptions["rsvp_on"] = (isset($_POST["option"]["rsvp_on"]) && $_POST["option"]["rsvp_on"]) ? 1 : 0;
                  $newoptions["confirmation_include_event"] = (isset($_POST["option"]["confirmation_include_event"]) && $_POST["option"]["confirmation_include_event"]) ? 1 : 0;
                  $newoptions['rsvpmaker_send_confirmation_email'] = (isset($_POST["option"]['rsvpmaker_send_confirmation_email']) && $_POST["option"]['rsvpmaker_send_confirmation_email']) ? 1 : 0;
                  $newoptions["login_required"] = (isset($_POST["option"]["login_required"]) && $_POST["option"]["login_required"]) ? 1 : 0;
                  $newoptions["rsvp_captcha"] = (isset($_POST["option"]["rsvp_captcha"]) && $_POST["option"]["rsvp_captcha"]) ? 1 : 0;
				  if(isset($_POST["option"]["rsvp_recaptcha_site_key"])) {
                  $newoptions["rsvp_recaptcha_site_key"] = sanitize_text_field($_POST["option"]["rsvp_recaptcha_site_key"]);
                  $newoptions["rsvp_recaptcha_secret"] = sanitize_text_field($_POST["option"]["rsvp_recaptcha_secret"]);		  
				  }
                  $newoptions["rsvp_yesno"] = (isset($_POST["option"]["rsvp_yesno"]) && $_POST["option"]["rsvp_yesno"]) ? 1 : 0;
                  $newoptions["calendar_icons"] = (isset($_POST["option"]["calendar_icons"]) && $_POST["option"]["calendar_icons"]) ? 1 : 0;
                  $newoptions["convert_timezone"] = (isset($_POST["option"]["convert_timezone"]) && $_POST["option"]["convert_timezone"]) ? 1 : 0;
                  $newoptions["social_title_date"] = (isset($_POST["option"]["social_title_date"]) && $_POST["option"]["social_title_date"]) ? 1 : 0;
                  $newoptions["rsvp_count"] = (isset($_POST["option"]["rsvp_count"]) && $_POST["option"]["rsvp_count"]) ? 1 : 0;
                  $newoptions["show_attendees"] = (isset($_POST["option"]["show_attendees"]) && $_POST["option"]["show_attendees"]) ? 1 : 0;
                  $newoptions["debug"] = (isset($_POST["option"]["debug"]) && $_POST["option"]["debug"]) ? 1 : 0;

				  $newoptions["dbversion"] = $options["dbversion"]; // gets set by db upgrade routine

				$newoptions["eventpage"] = sanitize_text_field($_POST["option"]["eventpage"]);
                  $newoptions["log_email"] = (isset($_POST["option"]["log_email"]) && $_POST["option"]["log_email"]) ? 1 : 0;

				foreach($newoptions as $name => $value) {
					if($name == 'rsvplink')
						$options[$name] = $value;
					else
						$options[$name] = sanitize_text_field($value);
				}

                  update_option($this->db_option, $options);

				  echo '<div class="updated fade"><p>Plugin settings saved.</p></div>';
				  if(isset($_POST['defaultoverride'])) {
					$future = rsvpmaker_get_future_events();
					$fcount = sizeof($future);
					$templates = rsvpmaker_get_templates();
					$tcount = sizeof($templates);
					$future = array_merge($future,$templates);
					foreach($future as $event) {
						foreach($_POST['defaultoverride'] as $slug) {
							$dbslug = '_'.sanitize_text_field($slug);
							update_post_meta($event->ID, $dbslug, $options[$slug]);
							//printf('<p>updating %s %s %s</p>',$event->ID, $dbslug, $options[$slug]);
						}						  
					}
				printf('<p>Updating %s for %s events and %s templates',esc_html(implode(', ',$_POST['defaultoverride']), $fcount, $tcount ));  
				}
			  }

              // URL for form submit, equals our current page
$action_url = admin_url('options-general.php?page=rsvpmaker-admin.php');
global $wpdb;
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

if(isset($_POST['timezone_string']))
{
	$tz = sanitize_text_field($_POST['timezone_string']);
	update_option('timezone_string',$tz);
	echo '<div class="notice notice-info"><p>'. __('Timezone set to','rsvpmaker').' '.$tz.'</p></div>';
}

?>

<div class="wrap" style="max-width:950px !important;">

    <h2 class="rsvpmaker-nav-tab-wrapper nav-tab-wrapper">
      <a class="rsvpmaker-nav-tab nav-tab <?php if(empty($_REQUEST['legacy']) ) echo 'nav-tab-active'; ?>" href="#email"><?php esc_html_e('Mailing List &amp; Postmark','rsvpmaker');?></a>
      <a class="rsvpmaker-nav-tab nav-tab <?php if(!empty($_REQUEST['tab']) && 'group_email' == $_REQUEST['tab'] ) echo 'nav-tab-active'; ?>" href="#groupemail"><?php esc_html_e('Group Email','rsvpmaker');?></a>
      <a class="rsvpmaker-nav-tab nav-tab <?php if(!empty($_REQUEST['tab']) && 'mailpoet' == $_REQUEST['tab'] ) echo 'nav-tab-active'; ?>" href="#mailpoet"><?php esc_html_e('MailPoet','rsvpmaker');?></a>
      <a class="rsvpmaker-nav-tab nav-tab <?php if(!empty($_REQUEST['tab']) && 'security' == $_REQUEST['tab'] ) echo 'nav-tab-active'; ?>" href="#security"><?php esc_html_e('Editing/Sending Rights','rsvpmaker');?></a>
    </h2>

    <div id='sections' class="rsvpmaker">
<section id="email" class="rsvpmaker">
<?php
global $RSVPMaker_Email_Options;
if(empty($RSVPMaker_Email_Options))
$RSVPMaker_Email_Options = new RSVPMaker_Email_Options();
$RSVPMaker_Email_Options->handle_options();
?>
</section>
<section id="groupemail" class="rsvpmaker">
<form action="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php'); ?>" method="post">
<?php rsvpmaker_nonce(); 
rsvpmaker_admin_heading(__('Group Email','rsvpmaker'),__FUNCTION__,'group_email');
?>
<?php
do_action('group_email_admin_notice');

echo '<p>'.__('Membership oriented websites can use this feature to relay messages from any member with a user account to all other members. Members can unsubscribe.','rsvpmaker').'</p>';

$hooksays = wp_get_schedule('rsvpmaker_relay_init_hook');

if(isset($_POST['rsvpmaker_discussion_server']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
	update_option('rsvpmaker_discussion_server',sanitize_text_field($_POST['rsvpmaker_discussion_server']));
if(isset($_POST['rsvpmaker_discussion_member']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	$newarray = array();
	foreach($_POST['rsvpmaker_discussion_member'] as $index => $value)
		$newarray[$index] = sanitize_textarea_field($value);
	update_option('rsvpmaker_discussion_member',$newarray);	
}
if(isset($_POST['rsvpmaker_discussion_officer']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	$newarray = array();
	foreach($_POST['rsvpmaker_discussion_officer'] as $index => $value)
		$newarray[$index] = sanitize_textarea_field($value);
	update_option('rsvpmaker_discussion_officer',$newarray);
}
if(isset($_POST['rsvpmaker_discussion_extra']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	$newarray = array();
	foreach($_POST['rsvpmaker_discussion_extra'] as $index => $value)
		$newarray[$index] = sanitize_textarea_field($value);
	update_option('rsvpmaker_discussion_extra',$newarray);
}
if(isset($_POST['rsvpmaker_discussion_bot']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	$newarray = array();
	foreach($_POST['rsvpmaker_discussion_bot'] as $index => $value)
		$newarray[$index] = sanitize_textarea_field($value);
	update_option('rsvpmaker_discussion_bot',$newarray);
}
if(isset($_POST['rsvpmaker_discussion_active']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	update_option('rsvpmaker_discussion_active',(int) $_POST['rsvpmaker_discussion_active']);
	deactivate_plugins('wp-mailster/wp-mailster.php',false,false);
	if(!wp_get_schedule('rsvpmaker_relay_init_hook')) {
		if(rsvpmaker_postmark_is_live()) {
			echo '<p>Postmark integration is active</p>';
		}
		else {
			wp_schedule_event( strtotime('+2 minutes'), 'doubleminute', 'rsvpmaker_relay_init_hook' );
			echo '<p>Activating rsvpmaker_relay_init_hook</p>';	
		}
	}
	update_option('rsvpmaker_email_queue_limit',intval($_POST['rsvpmaker_email_queue_limit']));
}
elseif(isset($_POST))
	wp_unschedule_hook( 'rsvpmaker_relay_init_hook' );

$active = (int) get_option('rsvpmaker_discussion_active');

$limit = (int) get_option('rsvpmaker_email_queue_limit');
if(empty($limit))
	$limit = 10;

$server = get_option('rsvpmaker_discussion_server');
if(empty($server))
	{
	$server = '{localhost:995/pop3/ssl/novalidate-cert}';
	update_option('rsvpmaker_discussion_server',$server);
	}
$member = get_option('rsvpmaker_discussion_member',array());
$officer = get_option('rsvpmaker_discussion_officer',array());

if(is_plugin_active( 'wp-mailster/wp-mailster.php' ) )
	{
	echo '<div style="border: thin dotted red; padding: 10px; margin: 5px;">';
		$sql = "SELECT * FROM ".$wpdb->prefix."mailster_lists WHERE name LIKE 'Member%' ";
		$row = $wpdb->get_row($sql);
		if(!empty($row->list_mail) && empty($member) ){
			$member = array('user' => $row->list_mail,'password' => $row->mail_in_pw, 'subject_prefix' => 'Members:'.get_option('blogname'), 'whitelist' => '','additional_recipients' => '', 'blocked' => '');
			update_option('rsvpmaker_discussion_member',$member);
			echo '<p>'.__('Importing Member List settings from WP Mailster','rsvpmaker').'</p>';
		}
		$sql = "SELECT * FROM ".$wpdb->prefix."mailster_lists WHERE name LIKE 'Officer%' ";
		$row = $wpdb->get_row($sql);
		if(!empty($row->list_mail) && empty($officer) ){
			$officer = array('user' => $row->list_mail,'password' => $row->mail_in_pw, 'subject_prefix' => 'Officers:'.get_option('blogname'), 'whitelist' => '','additional_recipients' => '', 'blocked' => '');
			update_option('rsvpmaker_discussion_officer',$officer);
			echo '<p>'.__('Importing Officer List settings from WP Mailster','rsvpmaker').'</p>';
		}
	echo '<p>'.__('If you activate this feature, WP Mailster will be deactivated','rsvpmaker').'</p>';
	echo '</div>';
	}

$postmark = get_rsvpmaker_postmark_options();
if(!empty($postmark['handle_incoming'])) {
echo '<p>Incoming messages are being handled through the Postmark integration.</a>';
}
else {
	echo '<p>Configured to use POP3 servers for incoming messages.</p>';
	echo rsvpmaker_relay_get_pop('member');
	echo rsvpmaker_relay_get_pop('officer');
	echo rsvpmaker_relay_get_pop('extra');
	echo rsvpmaker_relay_get_pop('bot');
}

printf('<p><label>Activate</label> <input type="radio" name="rsvpmaker_discussion_active" value="1" %s /> Yes <input type="radio" name="rsvpmaker_discussion_active" value="0" %s /> No</p>',($active) ? ' checked="checked" ' : '',(!$active) ? ' checked="checked" ' : '');

if(empty($postmark['handle_incoming'])) {
printf('<p><label>Server</label> <input type="text" name="rsvpmaker_discussion_server" value="%s" /></p>',esc_attr($server));
printf('<p><label>Queue Limit</label> <input type="text" name="rsvpmaker_email_queue_limit" value="%s" /></p>', $limit);
}
$member = get_option('rsvpmaker_discussion_member');
if(empty($member))
	$member = array('user' => '','password' => '','subject_prefix' => 'Members:'.get_option('blogname'), 'whitelist' => '', 'blocked' => '','additional_recipients' => '');

print_group_list_options('member', $member);

if(is_plugin_active( 'rsvpmaker-for-toastmasters/rsvpmaker-for-toastmasters.php' ))
{

	//officers section
	$officer = get_option('rsvpmaker_discussion_officer');
	if(empty($officer))
		$officer = array('user' => '','password' => '', 'subject_prefix' => 'Officer:'.get_option('blogname'),  'whitelist' => '', 'blocked' => '','additional_recipients' => '');
	print_group_list_options('officer', $officer);
}

$extra = get_option('rsvpmaker_discussion_extra');
if(empty($extra))
	$extra = array('user' => '','password' => '', 'subject_prefix' => '', 'whitelist' => get_option('admin_email'), 'blocked' => '','additional_recipients' => '');
print_group_list_options('extra', $extra);
echo '<p><em>'.__('Use for small custom distribution lists. Or use to forward an email you want to share to WordPress, then edit further with RSVP Mailer before sending.','rsvpmaker').'</em></p>';

if($postmark['handle_incoming']) {
	echo '<p>Postmark integration takes over the "bot" account function</p>';
}
else {
$bot = get_option('rsvpmaker_discussion_bot');
if(empty($bot))
	$bot = array('user' => '','password' => '', 'subject_prefix' => '', 'whitelist' => get_option('admin_email'), 'blocked' => '','additional_recipients' => '');
print_group_list_options('bot', $bot);
echo '<p><em>'.__('Use for automations triggered by an email.','rsvpmaker').'</em></p>';
}
if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'groupemail')
{
?>
<input type="hidden" id="activetab" value="groupemail" />
<?php	
}
?>
<input type="hidden" name="tab" value="groupemail">
<button>Submit</button>
</form>

<h3>Using the Bot Account</h3>
<p>Email sent to the bot account can be processed using a WordPress action where the email content is past in the form of post variables (subject as post_title, content as post_content). Example:</p>
<pre>
add_action('rsvpmaker_autoreply','my_post_to_email',10,5);
function my_post_to_email($email_as_post, $email_user, $from, $to, $fromname = '') {
	$myemail = 'mytrustedemail@gmail.com';
	if($from == $myemail) {
	$email_as_post['post_type'] = 'post';
	$email_as_post['post_status'] = 'draft';
	$id = wp_insert_post($email_as_post);
	wp_mail($myemail,'Draft '.$id.' '.$email_as_post['post_title'],'Edit draft '.$id);
	}
}
</pre>

</section>
<section id="email" class="mailpoet">
<div id="mailpoet">
<?php rsvpmaker_admin_heading('MailPoet Integration','mailpoet'); ?>
<h2>MailPoet Integration</h2>
<p>MailPoet is a WordPress plugin and web service for sending email newsletters and other mass email, with the permission of the recipients.</p>
<p>You can add RSVPMaker events or event listings to the content of a MailPoet newsletter using a modified versions of the RSVPMaker Shortcodes (see the <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=email_get_content'); ?>">Content for Email</a> screen and the <a href="https://rsvpmaker.com/knowledge-base/shortcodes/" target="_blank">RSVPMaker Shortcodes Documentation</a>).</p>
<?php
	if (class_exists(\MailPoet\API\API::class)) {
		$mailpoet_api = \MailPoet\API\API::MP('v1');
		$lists = $mailpoet_api->getLists();
		if(isset($_POST['rsvpmaker_mailpoet_list'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		{
			$listok = (int) $_POST['rsvpmaker_mailpoet_list'];
			update_option('rsvpmaker_mailpoet_list',$listok);
			echo '<div class="notice notice-success"><p>MailPoet List Set</p></div>';
		}
		else
			$listok = get_option('rsvpmaker_mailpoet_list');
		$o = '<option value="">Choose List</option>';
		foreach($lists as $list) {
			$s = ($list['id'] == $listok) ? ' selected="selected" ' : '';
			$o .= sprintf('<option value="%d" %s>%s</option>',$list['id'], $s, $list['name']);
		}
	printf('<form method="post" action="%s"><p>List to use with "Add me to your email list" checkbox <select name="rsvpmaker_mailpoet_list">%s</select><button>Update</button></p>%s</form>',site_url(sanitize_text_field($_SERVER['REQUEST_URI'])),$o,rsvpmaker_nonce());
	}
	else
		echo '<p>MailPoet not enabled</p>';
?>
</section>
<section id="security" class="rsvpmaker">
<h3><?php esc_html_e('Who Can Publish and Send Email?','rsvpmaker');?></h3>
<form method="post" action="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php'); ?>">
<p><?php esc_html_e('By default, only the administrator has this right, but you can add it to other roles.','rsvpmaker');?></p>
<?php $allroles = get_editable_roles(  ); 
foreach($allroles as $slug => $properties)
{
if($slug == 'administrator')
	continue;
	echo esc_html($properties["name"]);
	if(isset($properties["capabilities"]['publish_rsvpemails']))
		printf(' %s <input type="checkbox" name="remove_cap[%s]" value="1" /> %s <br />',__('can publish and send broadcasts','rsvpmaker'),$slug,__('Remove','rsvpmaker'));
	elseif(isset($properties["capabilities"]['edit_rsvpemails']))
		printf(' %s <input type="checkbox" name="remove_cap[%s]" value="1" /> %s <br />',__('can edit draft emails','rsvpmaker'),$slug,__('Remove','rsvpmaker'));
	else
		printf(' %s <input type="radio" name="add_cap[%s]" value="edit" /> %s <input type="radio" name="add_cap[%s]" value="publish" /> %s <br />',__('grant right to','rsvpmaker'),$slug,__('Edit','rsvpmaker'),$slug,__('Publish and Send','rsvpmaker'));
}
rsvpmaker_nonce();
submit_button();
?>
</form>
</section>
</div><!-- sections -->

<?php
	}
}
// create new instance of the class
$RSVPMAKER_Options = new RSVPMAKER_Options();
////print_r($RSVPMAKER_Options);
if (isset($RSVPMAKER_Options)) {
// register the activation function by passing the reference to our instance
register_activation_hook(__FILE__, array(&$RSVPMAKER_Options, 'install'));             
}

function rsvpmaker_form_summary($fpost) {
	$guest = (strpos($fpost->post_content,'rsvpmaker-guests')) ? __('Yes','rsvpmaker') : __('No','rsvpmaker');
	$note = (strpos($fpost->post_content,'name="note"') || strpos($fpost->post_content,'formnote')) ?  __('Yes','rsvpmaker') : __('No','rsvpmaker');
	preg_match_all('/"slug":"([^"]+)/',$fpost->post_content,$matches);
	if(!empty($matches[1]))
	foreach($matches[1] as $match)
		$fields[$match] = $match;
	if(empty($fields))
		return;
	return sprintf('<div>'.__('Fields','rsvpmaker').': %s<br />'.__('Guests','rsvpmaker').': %s<br />'.__('Note field','rsvpmaker').': %s</div>',implode(', ',$fields),$guest,$note);
}

function print_group_list_options($list_type, $vars) {
	printf('<h3>%s List</h3>',ucfirst($list_type));
	$postmark = get_rsvpmaker_postmark_options();
	$fields = array('user','password','subject_prefix','whitelist','blocked','additional_recipients');

	foreach($fields as $field)
		{
			if(empty($vars[$field]))
				$vars[$field] = '';
		}
	if(empty($postmark['handle_incoming'])) {
		printf('<p><label>%s</label><br /><input type="text" name="rsvpmaker_discussion_'.esc_attr($list_type).'[user]" value="%s" /> </p>',__('Email/User','rsvpmaker'),esc_attr($vars["user"]));	
		printf('<p><label>%s</label><br /><input type="text" name="rsvpmaker_discussion_'.esc_attr($list_type).'[password]" value="%s" /> </p>',__('Password','rsvpmaker'),esc_attr($vars["password"]));	
	}
	if($list_type != 'bot') {
		printf('<p><label>%s</label><br /><input type="text" name="rsvpmaker_discussion_'.esc_attr($list_type).'[subject_prefix]" value="%s" /> </p>',__('Subject Prefix','rsvpmaker'),esc_attr($vars["subject_prefix"]));
		printf('<p><label>%s</label> <br /><textarea rows="3" cols="80" name="rsvpmaker_discussion_'.esc_attr($list_type).'[whitelist]">%s</textarea> </p>',__('Whitelist - additional allowed sender emails','rsvpmaker'),esc_attr($vars["whitelist"]));	
		printf('<p><label>%s</label> <br /><textarea rows="3" cols="80" name="rsvpmaker_discussion_'.esc_attr($list_type).'[blocked]">%s</textarea> </p>',__('Blocked - not allowed to send to list','rsvpmaker'),esc_attr($vars["blocked"]));	
		printf('<p><label>%s</label> <br /><textarea  rows="3" cols="80" name="rsvpmaker_discussion_'.esc_attr($list_type).'[additional_recipients]">%s</textarea> </p>',__('Additional Recipients','rsvpmaker'),esc_attr($vars["additional_recipients"]));
	}	
}

function rsvpmaker_title_from_template($title) {
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
<br /><em><?php esc_html_e('Contents for an events page.','rsvpmaker');?></em>
            </div>

<p><strong>[rsvpmaker_calendar]</strong> displays the calendar by itself.</p><p><strong>[rsvpmaker_calendar nav="top"]</strong> displays the calendar with the next / previous month navigation on the top rather than the bottom. By default, navigation is displayed on the bottom.</p><p>Attributes: type="type_name" and add_to_query="querystring" also work with rsvpmaker_calendar.</p><p><strong>[event_listing format=&quot;headlines&quot;]</strong> displays a list of headlines</p><p>[event_listing format=&quot;calendar&quot;] OR [event_listing calendar=&quot;1&quot;] displays the calendar (recommend using [rsvpmaker_calendar] instead)</p><p>Other attributes:</p><ul><li>limit=&quot;posts_per_page&quot; limits the number of posts to display. If not specified, this will be the same as the number of posts displayed on your blog index page. (example: limit=&quot;30&quot;)</li><li>past=&quot;1&quot; will show a listing of past events, most recent first, rather than upcoming events.</li><li>title=&quot;Title Goes Here&quot; Specifies a title to be displayed in bold at the top of the listing.</li></ul>

<h3>To Embed a Single Event</h3>

<p><strong>[rsvpmaker_next]</strong>, displays just the next scheduled event. If the type attribute is set, that becomes the next event of that type. Example: [rsvpmaker_next type="webinar"]. Also, this displays the complete form rather than the RSVP Now! button unless showbutton="1" is set.</p>
<p><strong>[rsvpmaker_one post_id="10"]</strong> displays a single event post with ID 10. Shows the complete form unless the attribute showbutton="1" is set</p>
<p><strong>[rsvpmaker_form post_id="10"]</strong> displays just the form associated with an event (ID 10 in this example. Could be useful for embedding the form in a landing page that describes the event but where you do not want to include the full event content.</p>

<p>The rsvpmaker_one and rsvpmaker_form shortcodes also accept one="10" as equivalent to post_id="10"</p>

<?php esc_html_e('<h3>RSVP Form</h3><p>The RSVP from is also now formatted using shortcodes, which you can edit in the RSVP Form section of the Settings screen. You can also vary the form on a per-event basis, which can be handy for capturing an extra field. This is your current default form:</p>','rsvpmaker');?>
<pre>
<?php echo(htmlentities($rsvp_options["rsvp_form"])); ?>
</pre>
<?php esc_html_e('<p>Explanation:</p><p>[rsvpfield textfield=&quot;myfield&quot;] outputs a text field coded to capture data for &quot;myfield&quot;</p><p>[rsvpfield textfield=&quot;myfield&quot; required=&quot;1&quot;] treats &quot;myfield&quot; as a required field.</p><p>[rsvpfield selectfield=&quot;phone_type&quot; options=&quot;Work Phone,Mobile Phone,Home Phone&quot;] HTML select field with specified options</p><p>[rsvpfield checkbox=&quot;checkboxtest&quot; value=&quot;1&quot;] Checkbox named checkboxtext with a value of 1 when checked.</p><p>[rsvpfield checkbox=&quot;checkboxtest&quot; value=&quot;1&quot; checked=&quot;1&quot;] Checkbox checked by default.</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot;] When checked, records one of the 4 values for the field &quot;radiotest&quot;</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot; checked=&quot;two&quot;] choice &quot;two&quot; is checked by default</p><p>[rsvpfield radio=&quot;radiotest&quot; options=&quot;one,two,three,four&quot; checked=&quot;two&quot; sep=&quot; &quot;] separate choices with a space (by default, each appears on a separate line)</p><p>[rsvpprofiletable show_if_empty=&quot;phone&quot;]CONDITIONAL CONTENT GOES HERE[/rsvpprofiletable] This section only shown if the required field is empty; otherwise displays a message that the info is &quot;on file&quot;. Because RSVPMaker is capable of looking up profile data based on an email address, you may want some data to be hidden for privacy reasons.</p><p>[rsvpguests] Outputs the guest blanks.</p>','rsvpmaker'); ?>

<p><?php esc_html_e("If you're having trouble with the form fields not being formatted correctly",'rsvpmaker')?>, <a href="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php&amp;reset_form=1');?>"><?php esc_html_e('Reset default RSVP Form','rsvpmaker');?></a></p>

<h3>Timed Content</h3>

<p>To make a set a start or end time for the display of a block of content, wrap it in the rsvpmaker_timed shortcode.</p>

<p>Example:</p>

<p>[rsvpmaker_timed start="June 1, 2018" end="June 15, 2018" too_soon="Sorry, too soon" too_late="Sorry, too late"]</p>

<p>Timed Content goes here ...<br />continues until close tag.</p>

<p>[/rsvpmaker_timed]</p>

<p>Include either a start or end attribute, or both. For more precision, use a database style YYYY-MM-DD format for the date. Example: start="2018-06-01 13:00" for the content to start displaying June 1 after 1 pm local time.</p>
<p>The too_soon and too_late attributes are optional, for the output of messages before and after the specified time time period. Leave them out or leave them blank, and no content will be displayed outside the specified time period. </p>

<h3>YouTube Live webinars</h3>
<p>When embedding a YouTube Live stream in a page or post of your WordPress site, you can use the shortcode [ylchat] to embed the associated comment stream (which can be used to take questions from the audience). This extracts the video ID from the YouTube link included in the page and constructs the iframe for the chat window, according to Google's specifications. You can add attributes for width and height to override the default values (100% wide x 200 pixels tall). You can add a note to be displayed above the comments field using the note parameter, as in [ylchat note=&quot;During the program, please post questions and comments in the chat box below.&quot;]</p>

<p>RSVPMaker will stop displaying the chat field once the live event is over and the live chat is no longer active. Because this requires RSVPMaker to keep polling YouTube to see if the chat is live, you may wish to remove the shortcode from the page for replay views.</p>

<?php

}

function rsvpmaker_admin_menu() {

global $rsvp_options;
//do_action('rsvpmaker_admin_menu_top');
add_submenu_page('edit.php?post_type=rsvpmaker', __("Create / Update from Template",'rsvpmaker'), __("Create / Update",'rsvpmaker'), $rsvp_options["rsvpmaker_template"], "rsvpmaker_template_list", "rsvpmaker_template_list" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("Event Setup",'rsvpmaker'), __("Event Setup",'rsvpmaker'), 'edit_rsvpmakers', "rsvpmaker_setup", "rsvpmaker_setup" );
if(!empty($rsvp_options['additional_editors']))
	add_submenu_page('edit.php?post_type=rsvpmaker', __("Share Templates",'rsvpmaker'), __("Share Templates",'rsvpmaker'), 'edit_rsvpmakers', "rsvpmaker_share", "rsvpmaker_share" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("Multiple Events (without a template)",'rsvpmaker'), __("Multiple Events (without a template)",'rsvpmaker'), 'edit_rsvpmakers', "rsvpmaker_setup&quick=5", "rsvpmaker_setup" );
//add_submenu_page('edit.php?post_type=rsvpmaker', __("Event Options",'rsvpmaker'), __("Event Options",'rsvpmaker'), 'edit_rsvpmakers', "rsvpmaker_details", "rsvpmaker_details" );
//add_submenu_page('edit.php?post_type=rsvpmaker', __("Confirmation / Reminders",'rsvpmaker'), __("Confirmation / Reminders",'rsvpmaker'), 'edit_rsvpmakers', "rsvp_reminders", "rsvp_reminders" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("RSVP Report",'rsvpmaker'), __("RSVP Report",'rsvpmaker'), $rsvp_options["menu_security"], "rsvp_report", "rsvp_report" );
add_submenu_page( 'edit.php?post_type=rsvpmaker', __( 'RSVPMaker Payments', 'rsvpmaker' ), __( 'RSVPMaker Payments', 'rsvpmaker' ), 'edit_rsvpmakers', 'rsvpmaker_stripe_report', 'rsvpmaker_stripe_report' );
add_submenu_page('tools.php',__('Import/Export RSVPMaker'),__('Import/Export RSVPMaker'),'manage_options','rsvpmaker_export_screen','rsvpmaker_export_screen');
add_submenu_page('tools.php',__('Cleanup RSVPMaker'),__('Cleanup RSVPMaker'),'manage_options','rsvpmaker_cleanup','rsvpmaker_cleanup');
add_submenu_page('edit.php','Email Promos','Email Promos','edit_others_posts','rsvpmail_latest_posts_notification_setup','rsvpmail_latest_posts_notification_setup');
if(!empty($rsvp_options['debug']))
	add_submenu_page('tools.php',__('RSVPMaker Debug Log'),__('RSVPMaker Debug Log'),'manage_options','rsvpmaker_show_debug_log','rsvpmaker_show_debug_log');
}

function rsvpmaker_columns($defaults) {
	if(!empty($_GET["post_type"]) && ($_GET["post_type"] == 'rsvpemail'))
    	$defaults['rsvpmaker_cron'] = __('Scheduled','rsvpmaker');
    return $defaults;
}

function rsvpmaker_template_custom_column($column_name, $post_id) {
	if('template_schedule' == $column_name) {
		error_log('custom column for template schedule '.$post_id);
		$sked = get_template_sked($post_id);
		error_log('sked for custom colum '.var_export($sked,true));
		$output = '';
		if(is_array($sked))
		foreach($sked as $key => $value) {
			if(!preg_match('/[A-Z]/',$key))
				break;
			if($value)
				$output .= $key.' ';
		}
		echo esc_html($output);
	}
}

function rsvpmaker_custom_column($column_name, $post_id) {
	global $wpdb, $rsvp_options, $event, $post;
	$event = get_rsvpmaker_event($post_id);
	if(!$event && ('rsvpmaker_template' != $post->post_type) && ('rsvpemail' !=  $post->post_type)) {
			return;
	}
		//return;

    if( $column_name == 'rsvpmaker_end' ) {
		if($event)
		echo rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$event->ts_end);
	}
    elseif( $column_name == 'rsvpmaker_display' ) {
		if(empty($event))
			$event = get_rsvpmaker_event($post_id);
		$end_type = $event->display_type;
		if(empty($end_type))
			echo 'End Time Not Shown';
		else {
			$options = array('set' => 'Show End Time','allday' => 'All Day/Times Not Shown','multi|2' => '2 Days','multi|3' => '3 Days','multi|4' => '4 Days','multi|5' => '5 Days','multi|6' => '6 Days','multi|7' => '7 Days');
			if(!empty($options[$end_type]))
				echo esc_html($options[$end_type]);
		}
		printf('<input type="hidden" class="end_display_code" value="%s" />',$end_type);
		$rsvp_on = get_post_meta($post_id,'_rsvp_on',true);
		$convert_timezone = get_post_meta($post_id,'_convert_timezone',true);
		$add_timezone = get_post_meta($post_id,'_add_timezone',true);
		$template = get_post_meta($post_id,'_meet_recur',true);
		if(!empty($rsvp_on))
			echo '<br />RSVP On';
		if(!empty($add_timezone))
			echo '<br />Timezone code added to time';
		if(!empty($convert_timezone))
			echo '<br /><em>Show in my timezone</em> button displayed';
		if($template)
			printf('<br /><a href="%s">Template: %d</a>',admin_url('post.php?action=edit&post='.$template),$template);
	}
    elseif( $column_name == 'event_dates' ) {

$datetime = (isset($event->date)) ? $event->date : '';
$datetime = get_post_meta($post_id,'_rsvp_email_date',true);
$template = ($post->post_type == 'rsvpmaker_template') ? get_template_sked($post_id) : null;
$rsvpmaker_special = get_post_meta($post_id,'_rsvpmaker_special',true);

$s = $dateline = '';

if($datetime)
{
	echo rsvp_x_day_month($event->ts_start);
	printf('<span class="rsvpmaker-date" style="display:none" id="rsvpmaker-date-%d">%s</span>',esc_attr($post_id),esc_attr($datetime));
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
	echo esc_html($s);

	}

	} // end sked
	elseif($rsvpmaker_special)
		{
			echo __('Special Page','rsvpmaker').': '.$rsvpmaker_special;
		}
	} // end event dates column
	elseif($column_name == 'rsvpmaker_cron') {
		//echo rsvpmaker_next_scheduled($post_id);
		//echo 'test for chron';
		$signatures = get_post_meta($post->ID,'signatures');
		foreach($signatures as $signature) {
			$cancel = add_query_arg('cancel',implode('-',$signature),get_permalink()).'&timelord='.rsvpmaker_nonce('value');
			$next = wp_next_scheduled('rsvpmailer_delayed_send',$signature);
			if($next) {
				printf('<p>Scheduled send: %s | <a href="%s">cancel</a></p>',rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$next),$cancel);
			}
			$next = wp_next_scheduled('rsvpmaker_cron_email',$signature);
			if($next) {
				$recurrence = wp_get_schedule( 'rsvpmaker_cron_email',$signature );
				printf('<p>Scheduled send: %s %s | <a href="%s">cancel</a></p>',rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$next),$recurrence,$cancel);
			}	
		}		
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
			$hour = (int) $hour;
			if($hour > 0)
				$label = __('Follow up','rsvpmaker').': '.$hour.' '.__('hours after','rsvpmaker');
			else
				$label = __('Reminder','rsvpmaker').': '.abs($hour).' '.__('hours before','rsvpmaker');
		$txt .= sprintf(' | <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&hours='.$hour.'&page=rsvp_reminders&message_type=reminder&post_id=').$post_id,$label);
		}
	}
return $txt;
}

//add_action('admin_init','rsvpmaker_create_calendar_page');

function rsvpmaker_create_calendar_page() {
global $current_user;
if(isset($_GET["create_calendar_page"]))
	{
	$content = (function_exists('register_block_type')) ? '<!-- wp:rsvpmaker/upcoming {"calendar":"1","nav":"both"} /-->' : '[rsvpmaker_upcoming calendar="1" days="180" posts_per_page="10" type="" one="0" hideauthor="1" past="0" no_events="No events currently listed" nav="bottom"]';
	$post = array(
	  'post_content'   => $content,
	  'post_name'      => 'calendar',
	  'post_title'     => 'Calendar',
	  'post_status'    => 'publish',
	  'post_type'      => 'page',
	  'post_author'    => $current_user->ID,
	  'ping_status'    => 'closed'
	);
	$id = wp_insert_post($post);
	wp_redirect(admin_url('post.php?action=edit&post=').$id);
	exit();
	}
}

function rsvpmaker_essentials () {
	global $rsvp_options, $current_user;
	$cleared = get_option('cleared_rsvpmaker_notices');
	$cleared = is_array($cleared) ? $cleared : array();
	$message = '';
	if(isset($_POST["create_calendar_page"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		$content = (function_exists('register_block_type')) ? '<!-- wp:rsvpmaker/upcoming {"calendar":"1","nav":"both"} /-->' : '[rsvpmaker_upcoming calendar="1" days="180" posts_per_page="10" type="" one="0" hideauthor="1" past="0" no_events="No events currently listed" nav="bottom"]';
		$post = array(
		  'post_content'   => $content,
		  'post_name'      => 'calendar',
		  'post_title'     => 'Calendar',
		  'post_status'    => 'publish',
		  'post_type'      => 'page',
		  'post_author'    => $current_user->ID,
		  'ping_status'    => 'closed'
		);
		$id = wp_insert_post($post);
		$link = get_permalink($id);
		$message .= '<p>'.__('Calendar page created: ','rsvpmaker').sprintf('<a href="%s">%s</a>',$link,$link).'</p>';
	}
	if(isset($_POST["clear_calendar_page_notice"]) && !isset($_POST["create_calendar_page"])) {
		update_option('noeventpageok',1);
		$message .= '<p>Calendar notice cleared.</p>';		
	}
	if(isset($_POST["timezone_string"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		$tz = sanitize_text_field($_POST["timezone_string"]);
		update_option('timezone_string',$tz);
		$message .= '<p>Timezone set: '.$tz.'.</p>';		
	}
	if(isset($_POST["privacy_confirmation"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
		$rsvp_options["privacy_confirmation"] = (int) $_POST["privacy_confirmation"];
		$message .= '<p>Privacy confirmation option set.</p>';
		$privacy_page = get_option('wp_page_for_privacy_policy');
		if($privacy_page)
		{
			$privacy_url = get_permalink($privacy_page);
			$conf_message = sprintf('I consent to the <a target="_blank" href="%s">privacy policy</a> site of this site for purposes of follow up to this registration.',$privacy_url);
			$rsvp_options['privacy_confirmation_message'] = $conf_message;
			$message .= '<p>Confirmation message (can be edited in RSVPMaker Settings): '.$conf_message.'</p>';
		}
		else
			$message .= printf('<p><a href="%s">%s</a></p>',admin_url('options-privacy.php'),__('Set up your privacy page','rsvpmaker'));
		update_option('RSVPMAKER_Options',$rsvp_options);
	}
	$message .= '<p>'.__('You can set additional options, including default settings for RSVPMaker events, on the','rsvpmaker').' <a href="'.admin_url('options-general.php?page=rsvpmaker-admin.php').'">'.__('RSVPMaker settings page','rsvpmaker').'</a>.</p>';
	echo '<div class="notice notice-success is-dismissible">'.$message.'</div>';
}

function rsvpmaker_admin_notice() {

if(isset($_GET['action']) && ($_GET['action'] == 'edit'))
	return; //don't clutter edit page with admin notices. Gutenberg hides them anyway.
if(isset($_GET['post_type']) && ($_GET['post_type'] == 'rsvpmaker') && !isset($_GET['page']))
	return; //don't clutter post listing page with admin notices
if(isset($_POST["rsvpmaker_essentials"]))
	rsvpmaker_essentials();

if(isset($_GET['payment_key_test'])) {
	echo '<div class="notice notice-info"><p><div>Checking payment API connections</div>';
	$paypal_rest_keys = get_option('rsvpmaker_paypal_rest_keys');
	if(!empty($paypal_rest_keys['client_secret']) && !empty($paypal_rest_keys['client_id']))
		echo '<div>PayPal production key: '.rsvpmaker_paypal_test_connection().'</div>';
	if(!empty($paypal_rest_keys['sandbox_client_secret']) && !empty($paypal_rest_keys['sandbox_client_id']) )
		echo '<div>PayPal sandbox key: '.rsvpmaker_paypal_test_connection('sandbox').'</div>';
	$stripe_keys = get_option('rsvpmaker_stripe_keys');
	if(!empty($stripe_keys['pk']) && !empty($stripe_keys['sk']))
		echo '<div>Stripe production key :'.rsvpmaker_stripe_validate($stripe_keys['pk'],$stripe_keys['sk']).'</div>';
	if(!empty($stripe_keys['sandbox_sk']) && !empty($stripe_keys['sandbox_pk']) )
		echo '<div>Stripe sandbox key: '.rsvpmaker_stripe_validate($stripe_keys['sandbox_pk'],$stripe_keys['sandbox_sk']).'</div>';
	$chosen_gateway = get_rsvpmaker_payment_gateway ();
	if($chosen_gateway)
		printf('<p>Payment gateway defaults to: %s</p>',$chosen_gateway);
	echo '</p></div>';
}

global $wpdb;
global $rsvp_options;
global $current_user;
global $post;
$timezone_string = get_option('timezone_string');
$cleared = get_option('cleared_rsvpmaker_notices');
$cleared = is_array($cleared) ? $cleared : array();
$basic_options = '';

if( empty($rsvp_options["eventpage"]) && !get_option('noeventpageok') && !is_plugin_active('rsvpmaker-for-toastmasters/rsvpmaker-for-toastmasters.php') )
	{
	$sql = "SELECT ID from $wpdb->posts WHERE post_type='page' AND post_status='publish' AND post_content LIKE '%rsvpmaker_upcoming%' ";
	$front = get_option('page_on_front');
	if($front)
		$sql .= " AND ID != $front ";
	if($id =$wpdb->get_var($sql))
		{
		$rsvp_options["eventpage"] = get_permalink($id);
		update_option('RSVPMAKER_Options',$rsvp_options);
		}
	else {
		$message = __('RSVPMaker can add a calendar or events listing page to your site automatically, which you can then add to your website menu.','rsvpmaker');
		$message2 = __('Create page','rsvpmaker');
		$message3 = __('Turn off this warning','rsvpmaker');
		$basic_options = sprintf('<p>%s</p>
		<p><input type="checkbox" id="create_calendar" name="create_calendar_page" value="1" checked="checked"> %s</p>
		<p id="create_calendar_clear"><input type="checkbox" name="clear_calendar_page_notice" value="1" checked="checked"> %s<p>',$message,$message2,$message3);
		$basic_options .= "<script>
		jQuery(document).ready(function( $ ) {
		$('#create_calendar_clear').hide();
		$('#create_calendar').click(function(event) {
			$('#create_calendar_clear').show();
		});		
	});
		</script>";
		}	
}

if((empty($timezone_string) || isset($_GET['timezone'])) && !isset($_POST['timezone_string']) ) {
$choices = wp_timezone_choice('');
$choices = str_replace('selected="selected"','',$choices);
$message = sprintf('<p>%s %s. %s</p>',__('RSVPMaker needs you to','rsvpmaker'),__('set the timezone for your website','rsvpmaker'), __('Confirm if the choice below is correct or make another choice by region/city.','rsvpmaker') );
$basic_options .= sprintf('<p>%s</p>
<p>
<select id="timezone_string" name="timezone_string">
<script>'."
var tz = jstz.determine();
var tzstring = tz.name();
document.write('<option selected=\"selected\" value=\"' + tzstring + '\">' + tzstring + '</option>');
</script>
%s
</select>
",$message,$choices);
}

if(!isset($rsvp_options["privacy_confirmation"]) || isset($_GET['test']) )
	{
		$privacy_page = rsvpmaker_check_privacy_page();
		if($privacy_page)
			{
				$message = __('Please decide whether your RSVPMaker forms should include a privacy policy confirmation checkbox. This may be important if some of your website visitors may be covered by the European Union\'s GDPR privacy regulation','rsvpmaker').' <a href="'.admin_url('options-general.php?page=rsvpmaker-admin.php#privacy_consent').'">('.__('more details','rsvpmaker').')</a>';
				$basic_options .= sprintf('<p>%s</p><input type="radio" name="privacy_confirmation" value="1" checked="checked" /> %s <input type="radio" name="privacy_confirmation" value="no" /> %s - %s</p>',$message,__('Yes','rsvpmaker'),__('No','rsvpmaker'),__('Add checkbox?','rsvpmaker'));
			}
		else
			$basic_options .= '<p>'.__('You may want for your RSVPMaker forms to include a privacy policy confirmation checkbox, particularly if any site visitors are covered by the European Union\'s GDPR or similar privacy regulations.','rsvpmaker').' '.__('First, you must register a privacy page with WordPress','rsvpmaker').': <a href="'.admin_url('options-privacy.php').'">'.admin_url('options-privacy.php').'</a></p>';
	}

if(!empty($basic_options)) {
	$message = sprintf('<h3>%s</h3><form method="post" action="%s">
	%s
	<p><input type="submit" name="submit" id="submit" class="button button-primary" value="%s"></p>
	<input type="hidden" name="rsvpmaker_essentials" value="1">
	%s</form>',__('RSVPMaker Essential Settings','rsvpmaker'),site_url(sanitize_text_field($_SERVER['REQUEST_URI'])),$basic_options,__('Save Changes','rsvpmaker'),rsvpmaker_nonce('return'));
	$notice[] = rsvpmaker_admin_notice_format($message, 'rsvp_timezone', $cleared, $type='warning');
}

$ver = phpversion();

if(isset($_GET['update_messages']) && isset($_GET['t']))
{
echo get_post_meta((int) $_GET['t'],'update_messages',true);
delete_post_meta((int) $_GET['t'],'update_messages');
}

if(isset($post->post_type) && ($post->post_type == 'rsvpmaker') ) {
if($landing = get_post_meta($post->ID,'_webinar_landing_page_id',true))
	{
	$message = '<div class="notice notice-info"><p>'.__('Edit the','rsvpmaker').' <a href="'.admin_url('post.php?action=edit&post='.$landing).'">'.__("webinar landing page",'rsvpmaker').'</a> '.__('associated with this event').'.</p>';
	$message .= '<p>';
	$message .= __('Related messages:','rsvpmaker');
	$message .= sprintf(' <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$post->ID,__('Confirmation','rsvpmaker'));
	$message .= rsvpmaker_reminders_list($post->ID);
	$message .=  '</p></div>';
	$notice[] = rsvpmaker_admin_notice_format($message, 'Landing page', $cleared, $type='notice');
	}
if($event = get_post_meta($post->ID,'_webinar_event_id',true))
	{
	$message = '<div class="notice notice-info"><p>'.__('Edit the','rsvpmaker').' <a href="'.admin_url('post.php?action=edit&post='.$event).'">'.__("webinar event post",'rsvpmaker').'</a> '.__('associated with this landing page').'.</p>';
	$message .=  '<p>';
	$message .=  __('Related messages:','rsvpmaker');
	$message .=  sprintf(' <a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$event,__('Confirmation','rsvpmaker'));	
	$message .=  rsvpmaker_reminders_list($event);
	$message .=  '</p></div>';
	$notice[] = rsvpmaker_admin_notice_format($message, 'Webinar event', $cleared, $type='notice');
	}
}

	if(isset($_GET["smtptest"]))
		{
		$mail["to"] = $rsvp_options["rsvp_to"];
	$mail["from"] = get_bloginfo('admin_email');
	$mail["fromname"] = "RSVPMaker";
	$mail["subject"] = __("Testing SMTP email notification",'rsvpmaker');
	$mail["html"] = '<p>'. __('Test from RSVPMaker.','rsvpmaker').'</p>';
	$result = rsvpmailer($mail);
	echo '<div class="updated" style="background-color:#fee;">'."<strong>".__('Sending test email','rsvpmaker').' '.$result .'</strong> <a href="'.admin_url('index.php?smtptest=1&debug=1').'">(debug)</a></div>';
		}

	if(!empty($_GET["rsvp_form_reset"]))
		{
		$target = (int) $_GET["rsvp_form_reset"];
		upgrade_rsvpform (true, $target);
		echo '<div class="updated" ><p>'."<strong>".__('RSVP Form Updated (default and future events)','rsvpmaker').'</strong></p></div>';
		}
	if(!empty($notice))
	{
		if(isset($_GET['show_rsvpmaker_notices']))
			echo implode("\n",$notice);
		else {
			$size = sizeof($notice);
			$message = __('RSVPMaker notices for administrator','rsvpmaker').': '.$size;
			$message .= sprintf(' - <a href="%s">%s</a>',admin_url('?show_rsvpmaker_notices=1'),__('Display','rsvpmaker'));
			echo rsvpmaker_admin_notice_format($message, 'RSVPMaker', $cleared, $type='info');	
		}
	}
?>
<script>
jQuery(document).ready(function( $ ) {
$( document ).on( 'click', '.rsvpmaker-notice .notice-dismiss', function () {
	// Read the "data-notice" information to track which notice
	// is being dismissed and send it via AJAX
	var type = $( this ).closest( '.rsvpmaker-notice' ).data( 'notice' );
	$.ajax( rsvpmaker_rest.ajaxurl,
	  {
		type: 'POST',
		data: {
		  action: 'rsvpmaker_dismissed_notice_handler',
		  type: type,
		}
	  } );
  } );
});
</script>
<?php

}

function set_rsvpmaker_order_in_admin( $wp_query ) {
global $current_user, $rsvpmaker_upcoming_loop;
if(strpos($_SERVER["REQUEST_URI"],'wp-json/') && strpos($_SERVER["REQUEST_URI"],'/rsvpmaker') && !$rsvpmaker_upcoming_loop) {
	//editor behavior, for example query loop block
	add_filter('posts_join', 'rsvpmaker_join',99, 2 );
	add_filter('posts_groupby', 'rsvpmaker_groupby',99, 2 );	
	add_filter('posts_where', 'rsvpmaker_where',99, 2 );
	add_filter('posts_orderby', 'rsvpmaker_orderby',99, 2 );						
}

if(!is_admin() || empty($_GET['post_type']) || ($_GET['post_type'] != 'rsvpmaker') ) // don't mess with front end or other post types
	{
		return $wp_query;
	}

if(isset($_GET["rsvpsort"])) {
	$sort = sanitize_text_field($_GET["rsvpsort"]);
update_user_meta($current_user->ID,'rsvpsort',$sort);
}
elseif(isset($_GET['all_posts']) || isset($_GET['post_status'])) {
	$sort = 'all';
	update_user_meta($current_user->ID,'rsvpsort',$sort);
}
else
	$sort = get_user_meta($current_user->ID,'rsvpsort',true);
if(empty($sort))
	$sort = 'future';
if(isset($_GET['s']))
	return;
if($sort == 'all')
	return;

if(($sort == "past") || ($sort == "future")) {
	add_filter('posts_join', 'rsvpmaker_join',99, 2 );
	add_filter('posts_groupby', 'rsvpmaker_groupby',99, 2 );
	}
if($sort == 'past')
	{
	add_filter('posts_where', 'rsvpmaker_where_past',99, 2 );
	add_filter('posts_orderby', 'rsvpmaker_orderby_past',99, 2 );
	}
elseif($sort == 'special')
	{
	add_filter('posts_join', 'rsvpmaker_join_special',99, 2 );
	add_filter('posts_where', function($content) {return ' AND post_type="rsvpmaker" ';}, 99, 2 );
	add_filter('posts_orderby', function($content) {return ' ID DESC ';}, 99, 2 );
	}
elseif($sort == 'all')
	{
	add_filter('posts_where', function($content) {return " AND post_type='rsvpmaker' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'future' OR wp_posts.post_status = 'draft' OR wp_posts.post_status = 'pending' OR wp_posts.post_status = 'private')";}, 99 );
	add_filter('posts_orderby', function($content) {return ' ID DESC ';}, 99, 2  );
	}
else
	{
	add_filter('posts_where', 'rsvpmaker_where',99, 2 );
	add_filter('posts_orderby', 'rsvpmaker_orderby',99, 2 );
	}
}
add_filter('pre_get_posts', 'set_rsvpmaker_order_in_admin',1 );
add_filter('posts_orderby', function($content) { if(isset($_GET['post_type']) && 'rsvpmaker_template' == $_GET['post_type']) return ' post_title ASC '; return $content;}, 99, 2  );

function rsvpmaker_admin_months_dropdown($bool, $post_type) {
return ($post_type == 'rsvpmaker');
}

add_filter( 'disable_months_dropdown', 'rsvpmaker_admin_months_dropdown',10,2 );

function rsvpmaker_join_special($join) {
  global $wpdb;
return $join." JOIN ".$wpdb->postmeta." rsvpdates ON rsvpdates.post_id = $wpdb->posts.ID AND rsvpdates.meta_key='_rsvpmaker_special'";
}

function rsvpmaker_sort_message() {
	if((basename($_SERVER['SCRIPT_NAME']) == 'edit.php') && isset($_GET["post_type"]) &&  ($_GET["post_type"]=="rsvpmaker") && !isset($_GET["page"]))
	{
	global $current_user;
	$sort = get_user_meta($current_user->ID,'rsvpsort',true);
	if(empty($sort))
		$sort = 'future';
		$current_sort = $o = $opt = '';
		$sortoptions = array('future' => __('Future Events','rsvpmaker'),'past' => __('Past Events','rsvpmaker'),'all' => __('All RSVPMaker Posts','rsvpamker'));
		foreach($sortoptions as $key => $option)
		{
			if(isset($_GET['s']))
			{
				$current_sort = __('Showing','rsvpmaker').': '.__('Search Results','rsvpmaker');
			}
			if($key == $sort)
			{
				$opt .= sprintf('<option value="%s" selected="selected">%s</option>',$key,$option);
				$current_sort = __('Showing','rsvpmaker').': '.$sortoptions[$key];
			}
			else
			{	
				$opt .= sprintf('<option value="%s">%s</option>',$key,$option);
				$o .= '<a class="add-new-h2" href="'.admin_url('edit.php?post_type=rsvpmaker&rsvpsort='.$key).'">'.$option.'</a> ';
			}
		}
		$opt = '<div class="alignleft actions rsvpsortwrap" style="margin-top: -10px;" ><select name="rsvpsort" class="rsvpsort">'.$opt.'</select> </div>';
		echo '<div style="padding: 10px; ">'.$opt;//.$current_sort.$o;
		echo '</div>';
	}
}

function rsvpmaker_projected_datestring($dow,$week,$template,$t = 0) {
	if(!$t) 
		$t = time();
	$weektext = rsvpmaker_week($week);
	if($week == '0')
		return rsvpmaker_date('Y-m',$t).'-01 '.$template['hour'].':'.$template['minutes'].':00';
	elseif($week == '6')
		return rsvpmaker_day($dow,'rsvpmaker_strtotime').' '.rsvpmaker_date('Y-m',$t).' '.$template['hour'].':'.$template['minutes'].':00';
	else
		return $weektext.' '.rsvpmaker_day($dow,'rsvpmaker_strtotime').' of '.rsvpmaker_date('F',$t).' '.rsvpmaker_date('Y',$t).' '.$template['start_time'];
}

function rsvpmaker_get_projected($template) {

if(!isset($template["week"]))
	return;

$th = strtotime($template['hour']);

//backward compatability
if(is_array($template["week"]))
	{
		$weeks = $template["week"];
		$dows = (empty($template["dayofweek"])) ? 0 : $template["dayofweek"];
	}
else
	{
		$weeks[0] = $template["week"];
		$dows[0] = isset($template["dayofweek"]) ? $template["dayofweek"] : 0;
	}

$cy = date("Y");
$cm = date("m");

if(!empty($template["stop"]))
	{
	$stopdate = rsvpmaker_strtotime($template["stop"].' 23:59:59');
	}

if(empty($dows))
	$dows = array(0 => 0);
foreach($weeks as $week)
foreach($dows as $dow) {
$i = 0;
$startdaytxt = rsvpmaker_projected_datestring($dow,$week,$template);
$ts = rsvpmaker_strtotime($startdaytxt);
if(!$ts) {
	echo 'Error parsing '.$startdaytxt;
	return;
}
if($week == 6)
	{
	$i = 0;
	if(empty($stopdate))
		$stopdate = rsvpmaker_strtotime('+6 months');
	else
		echo 'stopdate set';
	if(isset($_GET["start"]))
		$ts = rsvpmaker_strtotime($_GET["start"]);
	while($ts < $stopdate)
		{
		$projected[$ts] = $ts; // add numeric value for 1 week
		$ts = $ts + WEEK_IN_SECONDS;
		$i++;
		if($i > 52)
			break;
		}
	}
else {
	if(isset($_GET["start"]))
		$ts = rsvpmaker_strtotime($_GET["start"]);
	if(empty($stopdate))
		$stopdate = rsvpmaker_strtotime('+12 months');
	if($week == 0) {
		$i = 0;
		$firstdaytxt = rsvpmaker_projected_datestring($dow,0,$template);//rsvpmaker_day($dow,'rsvpmaker_strtotime').' '.$template['hour'].':'.$template['minutes'];
		$tcount = rsvpmaker_strtotime($firstdaytxt);
		for($tcount; $tcount < $stopdate; $tcount+= MONTH_IN_SECONDS )
		{
		$firstdaytxt = rsvpmaker_projected_datestring($dow,0,$template,$tcount);//rsvpmaker_day($dow,'rsvpmaker_strtotime').' '.$template['hour'].':'.$template['minutes'];
		$ts = rsvpmaker_strtotime($firstdaytxt);
		$projected[$ts] = $ts;
		$i++;
		if($i > 10)
			break;
		}	
	}
	else
		{
			$i = 0;
			$ts = time();
			$startmonth = rsvpmaker_date('F Y',$ts);
			for($i = 0; $i < 50; $i++) //($ts; $ts < $stopdate; $ts+= MONTH_IN_SECONDS )
			{
				$ts = strtotime($startmonth." + $i month");
				$datetext = rsvpmaker_projected_datestring($dow,$week,$template,$ts);//rsvpmaker_day($dow,'rsvpmaker_strtotime').' '.$template['hour'].':'.$template['minutes'];

				$ts = rsvpmaker_strtotime($datetext);
				if(!$ts)
				{
					echo 'Error parsing date string '.$datetext;
					return;
				}
				if(isset($stopdate) && $ts > $stopdate) {
					break;
				}
				$projected[$ts] = $ts;
				}
		}
	}
}

//order by timestamp
if(empty($projected))
	return array();
//timezone correction
foreach($projected as $index => $value) {
	$checkhour = (int) rsvpmaker_date('G',$value);
	$diff =  $th - $checkhour; 
	if($diff) {
		$value+= ($diff * HOUR_IN_SECONDS);
		unset($projected[$index]);
		$projected[$value] = $value;
	}
}
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
}

// RSVPMaker Reminders

function rsvpmaker_reminder_cron($hours, $start_time, $post_id) {

$hours = (int) $hours;
$post_id = (int) $post_id;
//Convert start time from local time to GMT since WP Cron sends based on GMT
if(is_int($start_time))
	$start_time_gmt = $start_time;
else
	$start_time_gmt = rsvpmaker_strtotime( get_gmt_from_date( $start_time ) . ' GMT' );

$time_difference = $hours * 60 * 60; 
$reminder_time = $start_time_gmt + $time_difference;

//Remove existing cron event for this post if one exists
//We pass $post_id because cron event arguments are required to remove the scheduled event
wp_clear_scheduled_hook( 'rsvpmaker_send_reminder_email', array( $post_id, $hours ) );
//Schedule the reminder
wp_schedule_single_event( $reminder_time, 'rsvpmaker_send_reminder_email', array( $post_id, $hours ) );
}

function rsvpmaker_send_reminder_email ( $post_id, $hours ) {
global $wpdb, $post;
global $rsvp_options;
$marker = $post_id.':'.$hours;
$check = get_option('rsvpmaker_last_reminder');

if($check == $marker)
	return; //this already ran
$success = update_option('rsvpmaker_last_reminder',$marker);
if(!$success)
	return;
$wpdb->show_errors();
	$post = get_post($post_id);
	$reminder = rsvp_get_reminder($post_id,$hours);
	$confirm = $reminder->post_content;
	$subject = $reminder->post_title;
	$include_event = get_post_meta($post_id, '_rsvp_confirmation_include_event', true);
	$rsvpto = get_post_meta($post_id,'_rsvp_to',true);
	//handle codes like [datetime] and [rsvpdate]
	$subject = do_shortcode($subject);
	if(!empty($confirm))
	{
	$confirm = wpautop($confirm);				
	}

	if($hours < 0)
	{	
	$confirm .= "<p>".__("This is an automated reminder that we have you on the RSVP list for the event shown below. If your plans have changed, you can update your response by clicking on the RSVP button again.",'rsvpmaker')."</p>";
		if($include_event)
		{
			$event_content = event_to_embed($post_id);
		}
		else
			$event_content = get_rsvp_link($post_id);
	}

			$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post_id AND yesno=1";
			if(get_post_meta($post_id,'paid_only_confirmation',true))
				$sql .= " AND amountpaid";

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
				$t = rsvpmaker_strtotime($row["timestamp"]);
				$notification .= 'posted: '.rsvpmaker_date($rsvp_options["short_date"],$t);
				$notification .=  "</p>";
				$notification .=  "<h3>Event Details</h3>\n".str_replace('#rsvpnow">','#rsvpnow">'.__('Update','rsvptoast').' ',preg_replace('/(\*|){0,1}EMAIL(|\*){0,1}/',$notify, $event_content));

				echo "Notification for $notify<br />$notification";

			//if this is a follow up, we don't need all the RSVP data
			if($hours > 0)
				$notification = $confirm;

				$mail["subject"] = $subject;
				$mail["html"] = $notification;
				$mail["to"] = $notify;
				$mail["from"] = $rsvpto;
				$mail["fromname"] = get_bloginfo('name');

				rsvpmaker_tx_email(get_post($post_id), $mail);
				}
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

		$event = get_post($post_id);
		$start_time = $date = get_rsvp_date($post_id);
		$date = mb_convert_encoding(rsvpmaker_date($rsvp_options["long_date"].' '.$rsvp_options['time_format'],rsvpmaker_strtotime($date)),'UTF-8');
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

function no_mce_plugins( $p ) { return array(); }

function rsvpmaker_template_reminder_add($hours,$post_id) {
	$cron = get_post_meta($post_id,'rsvpmaker_template_reminder',true);
	if(!is_array($cron))
		$cron = array();
	if(!in_array($hours,$cron))
		$cron[] = $hours;
	update_post_meta($post_id, 'rsvpmaker_template_reminder', $cron);
}

function rsvp_get_confirm($post_id, $return_post = false) {
	global $rsvp_options, $post, $wpdb, $wp_styles;
	$content = ($post_id) ? get_post_meta($post_id,'_rsvp_confirm',true) : $rsvp_options['rsvp_confirm'];
	if(empty($content))
		$content = $rsvp_options['rsvp_confirm'];
	if(is_numeric($content))
	{
		$id = $content;
		$conf_post=get_post($id);
		if(empty($conf_post))
		{
			//maybe got deleted or something?
			if(is_numeric($rsvp_options['rsvp_confirm']))
				$conf_post= get_post($rsvp_options['rsvp_confirm']);
		}
		if(empty($conf_post->post_content)) {
			//if the default confirmation post is missing, recreate it
			$conf_array = array('post_title'=>'Confirmation:Default', 'post_content'=>'Thank you!','post_type' => 'rsvpemail','post_status' => 'publish');
			$conf_array['ID'] = $id = wp_insert_post($conf_array);
			$rsvp_options['rsvp_confirm'] = $id;
			update_option('RSVPMAKER_Options',$rsvp_options);
			$conf_post = (object) $conf_array;
		}			
		if(!strpos($conf_post->post_content,'!-- /wp'))//if it's not Gutenberg content
			$conf_post->post_content = wpautop($conf_post->post_content);
		$conf_post->post_content = do_blocks($conf_post->post_content);
		$title = (!empty($post->post_title)) ? $post->post_title : 'not set';
		$context = (is_admin()) ? 'admin' : 'not admin';
		$log = sprintf('retrieve conf post ID %s for %s %s',$id,$title,$context);
	}
	else {
		$content = wp_kses_post(rsvpautog($content));
		$conf_post = array('post_title'=>'Confirmation:'.$post_id, 'post_content'=>$content,'post_type' => 'rsvpemail','post_status' => 'publish','post_parent' => $post_id);
		$conf_post['ID'] = $id = wp_insert_post($conf_post);
		$conf_post = (object) $conf_post;
		update_post_meta($post_id,'_rsvp_confirm',$id);
		$title = (!empty($post->post_title)) ? $post->post_title : 'not set';
		$context = (is_admin()) ? 'admin' : 'not admin';
		$log = sprintf('adding conf post ID %s for %s %s',$id,$title,$context);
	}
	if($return_post)
		return $conf_post;
	return $conf_post->post_content;
}

function rsvp_list_reminders_all_events() {
	global $wpdb;
	$events = rsvpmaker_get_future_events();
	if(empty($events))
		return;
	$output = '';
	foreach($events as $event) {
		$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_rsvp_reminder_msg_%' AND post_id=".$event->ID." ORDER BY meta_key ";
		$results = $wpdb->get_results($sql);
		foreach($results as $row) {
			$time = str_replace('_rsvp_reminder_msg_','',$row->meta_key);
			$output .= sprintf('<p><input type="checkbox" name="delete_reminder[]" value="%d:%s"> <a href="%s">Edit Reminders</a> %s %s %s hours</p>',$event->ID, $row->meta_key, admin_url('edit.php?page=rsvp_reminders&post_type=rsvpmaker&post_id='.$event->ID),$event->post_title,$event->datetime,$time);
		}
	}
	if(!empty($output))
		return 	sprintf('<form method="post" action="%s">',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders')).$output.'<p><button>Delete Checked</button>'.rsvpmaker_nonce('return').'</p></form>';
}

function rsvp_get_reminder($post_id,$hours) {
	global $rsvp_options, $wpdb;
	$key = '_rsvp_reminder_msg_'.$hours;
	$reminder_id = get_post_meta($post_id, $key,true);
	if(empty($reminder_id) && ($t = rsvpmaker_has_template($post_id)) &&!isset($_GET['was']) )
		$reminder_id = get_post_meta($t, $key,true);
	if(empty($reminder_id) || !is_numeric($reminder_id))
		return;
	$conf_post = get_post($reminder_id);
	if(empty($conf_post))
		return;
	$event_title = get_the_title($post_id);
	$dateblock = rsvp_date_block_email( $post_id );

	$conf_post->post_content = rsvpmaker_email_html($conf_post->post_content);
	$conf_post->post_content = '<h1>'.esc_html($event_title).'</h1>'."\n".$dateblock."\n".$conf_post->post_content;
	return $conf_post;
}

function rsvp_reminders () {
global $wpdb;
global $rsvp_options;
global $current_user;
$existing = $options = '';
$templates = rsvpmaker_get_templates();
$post_id = (isset($_REQUEST["post_id"])) ? (int) $_REQUEST["post_id"] : false;

if(isset($_POST['defaults']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
	foreach($_POST['defaults'] as $index => $value) {
		if($index == 'confirmation') {
			delete_post_meta($post_id,'_rsvp_confirm');
		}
		if($index == 'payment_confirmation') {
			delete_post_meta($post_id,'payment_confirmation_message');
		}
		if($index == 'reminders')
		{
			$sql = "DELETE FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_rsvp_reminder_msg_%'";
			$wpdb->query($sql);
		}
	}
}

$documents = get_related_documents();
?>
<style>
<?php 
$styles = rsvpmaker_included_styles();
echo $styles; ?>
</style>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div>
<?php
$title = __('Confirmation / Reminder Messages','rsvpmaker');
if($post_id) $title .= ': '.get_the_title($post_id); 
rsvpmaker_admin_heading($title,__FUNCTION__);
?> 
<?php

if($post_id)
	$start_time = get_rsvp_date($post_id);

$hours = (isset($_REQUEST["hours"])) ? (int) $_REQUEST["hours"] : false;

if(isset($_GET["webinar"]))
	{
		$post_id = (int) $_GET["post_id"];
		$ylive = sanitize_text_field($_GET["youtube_live"]);	
		rsvpmaker_youtube_live($post_id, $ylive, true);
	}	

if(isset($_GET['delete']))
{
	$key = '_rsvp_reminder_msg_'. (int) $_GET['delete'];
	printf('<p>Deleting %s</p>',$key);
	delete_post_meta($post_id,$key);
}

if(isset($_POST['delete_reminder']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	foreach($_POST['delete_reminder'] as $delete_reminder) {
		$delete_reminder = sanitize_text_field($delete_reminder);
		$p = explode(':',$delete_reminder);
		delete_post_meta($p[0],$p[1]);
	}
}

if(isset($_POST['paid_only_confirmation'])) {
	$reminder_id = (int) $_POST['reminder_post_id'];
	update_post_meta($reminder_id, 'paid_only_confirmation', (int) $_GET['paid_only_confirmation']);
	printf('<div class="notice notice-success"><p>%s, post_id: %d</p></div>',__('Reminder updated','rsvpmaker'),$reminder_id);
}

if($post_id && $hours)
{
	$reminder = rsvp_get_reminder($post_id,$hours);
	if(!empty($reminder))
	{
		printf('<p>%s %s %s</p><h2>%s</h2>%s<p><a href="%s">%s</a></p>',__('Added reminder ','rsvpmaker'), (int) $_GET['hours'],__('hours','rsvpmaker'),esc_html($reminder->post_title),wp_kses_post($reminder->post_content),admin_url('post.php?action=edit&post='.intval($reminder->ID)),__('Edit','rsvpmaker'));	
	if(rsvpmaker_is_template($post_id))
	{
		echo 'This is a template';
		rsvpmaker_template_reminder_add($hours,$post_id);
		rsvpautorenew_test (); // will add to the next scheduled event associated with template
	}
	else
	{
		$start_time = get_rsvp_event_time($post_id);
		rsvpmaker_reminder_cron($hours, $start_time, $post_id);
	}

	}
	else '<h2>Error Adding Reminder</h2>';
}

if($post_id)
{
if(rsvpmaker_is_template($post_id))
	printf('<p><em>%s</em></p>',__('This is an event template: The confirmation and reminder messages you set here will become the defaults for future events based on this template. The [datetime] placeholder in subject lines will be replaced with the specific event date.','rsvpmaker'));

//$confirm = rsvp_get_confirm($post_id, true);
printf('<form action="%s" method="post">',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id=').$post_id);
rsvpmaker_nonce();
echo get_confirmation_options($post_id, $documents);
echo '<button>Save</button></form>';

$reminder_copy = sprintf('<option value="%d">%s</option>',get_post_meta($post_id,'_rsvp_confirm',true),__('Confirmation Message'));

printf('<h3>%s</h3>',__('Payment Confirmation','rsvpmaker'));
$payment_confirmation = (int) get_post_meta($post_id,'payment_confirmation_message',true);
if($payment_confirmation)
{
	$pconf = get_post($payment_confirmation);
	echo (empty($pconf->post_content)) ? '<p>[not set]</p>' : $pconf->post_content;
}

foreach($documents as $d) {
	$id = $d['id'];
	if(($id == 'edit_payment_confirmation') || ($id == 'edit_payment_confirmation_custom'))
	printf('<p><a href="%s">Edit: %s</a></p>',$d['href'],$d['title']);
}

if(!empty($pconf->post_content))
	$reminder_copy .= sprintf('<option value="%d">%s</option>',$pconf->ID,__('Payment Confirmation','rsvpmaker'));

echo '<div style="border: thin solid #555; padding: 10px;"><h2>Reminders</h2>';

$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE '_rsvp_reminder_msg_%' ORDER BY meta_key";

$results = $wpdb->get_results($sql);
$delete_reminder_options = '';
if(!$results)
	echo '<p>No reminders active</p>';
else {
	foreach($results as $row)
	{
		$hours = str_replace('_rsvp_reminder_msg_','',$row->meta_key);
		$type = ($hours > 0) ? 'FOLLOW UP' : 'REMINDER';
		$reminder = rsvp_get_reminder($post_id,$hours);
		$reminder_copy .= sprintf('<option value="%d">%s %s</option>',$reminder->ID,$type,$hours);
		$delete_reminder_options .= sprintf('<option value="%s">%s %s</option>',esc_attr($post_id.':'.$row->meta_key),esc_html($type),esc_html($hours));
		printf('<h2>%s (%s hours)</h2><h3>%s</h3>%s',esc_html($type),esc_html($hours),esc_html($reminder->post_title),wp_kses_post($reminder->post_content));
		$parent = $reminder->post_parent;
		if($parent != $post_id)
			printf('<p>%s<br /><a href="%s">%s</a></p>',__('This is the standard reminder from the event template','rsvpmaker'), admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&post_id='.$post_id.'&hours='.$hours.'&was='. $reminder->ID),__('Customize for this event','rsvpmaker'));
		foreach($documents as $d) {
			$id = $d['id'];
			if(($id == 'reminder'.$hours) || ($id == 'reminder'.$hours.'custom'))
			printf('<p><a href="%s">Edit: %s</a></p>',esc_attr($d['href']),esc_html($d['title']));
		}
		$paid_only = get_post_meta($reminder->ID,'paid_only_confirmation',true);
		if($paid_only)
			$radio = sprintf('<input type="radio" name="paid_only_confirmation" value="1" checked="checked" /> Yes <input type="radio" name="paid_only_confirmation" value="0" /> No ');
		else
			$radio = sprintf('<input type="radio" name="paid_only_confirmation" value="1" /> Yes <input type="radio" name="paid_only_confirmation" value="0"  checked="checked"  /> No ');
		printf('<form action="%s" method="post">
		<input type="hidden" name="post_type" value="rsvpmaker" />
		<input type="hidden" name="page" value="rsvp_reminders" />
		<input type="hidden" name="message_type" value="confirmation" />
		<input type="hidden" name="post_id" value="%d" />
		<input type="hidden" name="reminder_post_id" value="%d" />
		<p>%s %s %s
		<button>Update</button></p>
		</form>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&post_id='.$post_id),esc_attr($post_id),esc_attr($reminder->ID),__('Send only after payment','rsvpmaker'),$radio,rsvpmaker_nonce('return'));
	}
printf('<h3>Delete Reminder</h3><form method="post" action="%s"><select name="delete_reminder[]">%s</select><br /><button>Delete</button>%s</form>',admin_url('edit.php?page=rsvp_reminders&post_type=rsvpmaker&post_id='.$post_id),$delete_reminder_options,rsvpmaker_nonce('return'));
}

$reminder_copy .= '<option value="">'.__('Blank message','rsvpmaker').'</option>';

$hour_options = rsvp_reminder_options();
printf('<h3>Add Reminders and Follow Up Messages</h3>
<form method="post" action="%s"><input type="hidden" name="create_reminder_for" value="%s">
<p><select name="hours">%s</select>
%s
<select name="copy_from">%s</select></p>
<p><input type="checkbox" name="paid_only" value="1"> Send for PAID registrations only</p>
<p><button>Submit</button></p>%s</form>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&post_id='.$post_id),esc_attr($post_id),$hour_options,__('Based on','rsvpmaker'),$reminder_copy,rsvpmaker_nonce('return'));

echo '</div>';//end reminders section

printf('<h3>Reset to Defaults</h3>
<form method="post" action="%s">
<p><input type="checkbox" name="defaults[confirmation]" value="1" /> Confirmation</p>
<p><input type="checkbox" name="defaults[payment_confirmation]" value="1"> Payment Confirmation</p>
<p><input type="checkbox" name="defaults[reminders]" value="1"> Remove Reminders</p>
<p><button>Submit</button></p>%s</form>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id='.$post_id),rsvpmaker_nonce('return'));

?>
<h3><?php esc_html_e('Webinar Setup','rsvpmaker'); ?></h3>
<form method="get" action = "<?php echo admin_url('edit.php'); ?>">
<p><?php esc_html_e('This utility sets up a landing page and suggested confirmation and reminder messages, linked to that page. RSVPMaker explicitly supports webinars based on YouTube Live, but you can also embed the coding required for another webinar of your choice.','rsvpmaker'); ?></p>
<input type="hidden" name="post_type" value="rsvpmaker" >
<input type="hidden" name="page" value="rsvp_reminders" >
<input type="hidden" name="webinar" value="1" >
<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
<p>YouTube Live url: <input type="text" name="youtube_live" value=""> <input type="checkbox" name="youtube_require_passcode" value="1" /> <?php esc_html_e('Require passcode to view','rsvpmaker');?></p>
<?php rsvpmaker_nonce(); ?>
<p><button><?php esc_html_e('Create','rsvpmaker');?></button></p>
</form>
<?php

}
else {
	$o = '<option value="">Select Event or Event Template</option>';
	$templates = rsvpmaker_get_templates();
	if($templates)
	foreach($templates as $event)
	{
		if(current_user_can('edit_post',$event->ID))
		$o .= sprintf('<option value="%s">TEMPLATE: %s</option>',esc_attr($event->ID),esc_html($event->post_title));
	}
	$future = rsvpmaker_get_future_events();
	if($future)
	foreach($future as $event)
	{
		if(current_user_can('edit_post',$event->ID))
		$o .= sprintf('<option value="%s">%s - %s</option>',esc_attr($event->ID),esc_html($event->post_title),esc_html($event->date));
	}	
	printf('<form method="get" action="%s"><input type="hidden" name="page" value="rsvp_reminders"><input type="hidden" name="post_type" value="rsvpmaker"><select name="post_id">%s</select><button>Get</button>%s</form>',admin_url('edit.php'),$o,rsvpmaker_nonce('return'));
}

rsvpmaker_reminders_nudge ();

$list = rsvp_list_reminders_all_events();
if($list)
	echo '<h2>Reminders for All Upcoming Events</h2>'.$list;

?>
<h3><?php esc_html_e('A Note on More Reliable Scheduling','rsvpmaker');?></h3>
<p><?php esc_html_e('RSVPMaker takes advantage of WP Cron, a standard WordPress scheduling mechanism. Because it only checks for scheduled tasks to be run when someone visits your website, WP Cron can be imprecise -- which could be a problem if you want to make sure a reminder will go out an hour before your event, if that happens to be a low traffic site. Caching plugins can also get in the way of regular WP Cron execution. Consider following <a href="http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119">these directions</a> to make sure your server checks for scheduled tasks to run on a more regular schedule, like once every 5 or 15 minutes.','rsvpmaker');?></p>

<p><?php esc_html_e('Using Unix cron, the command you would set to execute would be','rsvpmaker');?>:</p>
<code>
curl <?php echo site_url('/wp-cron.php?doing_wp_cron=1');?> > /dev/null 2>&1
</code>
<p><?php esc_html_e('If curl does not work, you can also try this variation (seems to work better on some systems)','rsvpmaker');?>:</p>
<code>
wget -qO- <?php echo site_url('/wp-cron.php?doing_wp_cron=1');?>  &> /dev/null
</code>
</div>
<?php

}

function rsvpmaker_placeholder_image () {
$impath = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'placeholder.png';
$im = imagecreatefrompng($impath);
if(!$im)
{
$im = imagecreate(800, 50);
imagefilledrectangle($im,5,5,790,45, imagecolorallocate($im, 50, 50, 255));
}

$bg = imagecolorallocate($im, 200, 200, 255);
$border = imagecolorallocate($im, 0, 0, 0);
$textcolor = imagecolorallocate($im, 255, 255, 255);

$text = (isset($_GET['post_id'])) ? __('Event','rsvpmaker').': ' : __('Events','rsvpmaker').': ';
$tip = '('.__('double-click for popup editor','rsvpmaker').')';

foreach ($_GET as $name => $value)
	{
	if($name == 'rsvpmaker_placeholder')
		continue;
	if(empty($value))
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

function rsvpmaker_default_content ($content) {
	global $rsvp_options;
	global $rsvp_template;
	if(isset($_GET["clone"]))
		{
			$id = (int) $_GET["clone"];
			$clone = get_post($id);
			$content = $clone->post_content;
		}
	elseif(isset($_GET['post_type']) && ('rsvpemail' == $_GET['post_type']) ) {
		$content = get_rsvpmailer_default_block_template();
	}
	if(isset($rsvp_template->post_content))
		$content = $rsvp_template->post_content;
	if(isset($_GET['post_type']) && ('rsvpemail' == $_GET['post_type']) && !empty($rsvp_options['default_content']))
		$content = wp_kses_post($rsvp_options['default_content']);		
	return $content;
}

add_filter('default_content','rsvpmaker_default_content');

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
	if($events && is_array($events))
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
	$nv = array();
	if(is_array($data))
	foreach($data as $newrow)
	{
	foreach($newrow as $key => $value)
		{
		$nv[$key] = $value;
		}
	$wpdb->insert($wpdb->prefix.'rsvpmaker',$nv);
	}

	}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='_export_rsvpmaker' ");
}

$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key='_export_rsvp_volunteer_time' ");
if($results)
{
$table = $wpdb->prefix.'rsvp_volunteer';
foreach($results as $row)
	{
	$data = unserialize($row->meta_value);
	foreach($data as $newrow)
	{
	$nv = array();
	foreach($newrow as $key => $value)
		{
		$nv[$key] = $value;
		}
	$wpdb->insert($table,$nv);
	}	
	}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='_export_rsvp_volunteer_time' ");
}

}

function rsvpmaker_paypal_config_ajax () {
$filename = rsvpmaker_paypal_config_write(sanitize_text_field($_POST["user"]),sanitize_text_field($_POST["password"]),sanitize_text_field($_POST["signature"]));
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
update_option('paypal_config',$filename);
return $filename;
}

function future_rsvpmakers_by_template($template_id) {
	if(!$template_id)
		return false;
	$ids = array();
	$sched_result = get_events_by_template($template_id);
	if($sched_result)
	foreach($sched_result as $row)
		$ids[] = $row->ID;
	return $ids;
}

function rsvptimes ($time,$fieldname) {
if(empty($time))
	$time = '01:00:00';
printf('%s <input type="time" name="%s" value="%s">',__('Time'),$fieldname,$time);
}

function rsvpmaker_add_one () {
if(!empty($_POST["rsvpmaker_add_one"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
global $wpdb;
global $current_user;

$t = (int) $_POST["template"];
$post = get_post($t);
$template = get_template_sked($t);

$timezone = get_post_meta($t,'_timezone',true);
if(!$timezone)
	$timezone = wp_timezone_string();

$hour = (isset($template["hour"]) ) ? (int) $template["hour"] : 17;
$minutes = isset($template["minutes"]) ? $template["minutes"] : '00';

	$my_post['post_title'] = $post->post_title;
	$my_post['post_content'] = $post->post_content;
	$my_post['post_excerpt'] = $post->post_excerpt;
	$my_post['post_status'] = 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';
	foreach($_POST["recur_check"] as $index => $on)
		{
			if(!empty($_POST["recur_title"][$index]))
				$my_post['post_title'] = sanitize_text_field($_POST["recur_title"][$index]);
			$year = sanitize_text_field($_POST["recur_year"][$index]);
			$cddate = format_cddate($year, sanitize_text_field($_POST["recur_month"][$index]), sanitize_text_field($_POST["recur_day"][$index]), $hour, $minutes);
			$dpart = explode(':',$template["duration"]);

			if( is_numeric($dpart[0]) )
				{
				$dtext = $cddate.' +'.$dpart[0].' hours';
				if(!empty($dpart[1]))
					$dtext .= ' +'.$dpart[1].' minutes';
				$dt = rsvpmaker_strtotime($dtext);
				$duration = rsvpmaker_date('Y-m-d H:i:s',$dt);
				}
			else
				$duration = $template["duration"];
			$y = (int) $_POST["recur_year"][$index];
			$m = (int) $_POST["recur_month"][$index];
			if($m < 10) $m = '0'.$m;
			$d = (int) $_POST["recur_day"][$index];
			if($d < 10) $d = '0'.$d;
			$date = $y.'-'.$m.'-'.$d;

			$my_post['post_name'] = sanitize_text_field($my_post['post_title'] . '-' .$date );
			$singular = __('Event','rsvpmaker');
// Insert the post into the database
  			if($post_id = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($post_id,$cddate,$duration, $timezone);				
				add_post_meta($post_id,'_meet_recur',$t,true);
				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$post_id);
				update_post_meta($post_id,"_updated_from_template",$ts);

				wp_set_object_terms( $post_id, $rsvptypes, 'rsvpmaker-type', true );

				$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_rsvp%' AND post_id=".$t);
				if($results)
				foreach($results as $row)
					{
					if($row->meta_key == '_rsvp_reminder')
						continue;
					$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta SET meta_key=%s,meta_value=%s,post_id=%d",$row->meta_key,$row->meta_value,$post_id));
					}
				//copy rsvp options
				$editurl = admin_url('post.php?action=edit&post='.$post_id);
				wp_redirect($editurl);
				exit;
				}		
		break;
		}
	}
}//end rsvpmaker_add_one

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

$print = (isset($_GET["page"]) && !isset($_GET["rsvp_print"])) ? '<div style="width: 200px; text-align: right; float: right;"><a target="_blank" href="'.admin_url(str_replace('/wp-admin/','',sanitize_text_field($_SERVER['REQUEST_URI']))).'&rsvp_print=1">Print</a></div>' : '';
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

function rsvpmaker_editors() {
if(isset($_GET['page']) && ($_GET['page'] == 'rsvp_reminders'))
	wp_enqueue_editor();
}

function rsvpmaker_admin_notice_format($message, $slug, $cleared, $type='info')
{
if(in_array($slug,$cleared))
	return;
return sprintf('<div class="notice notice-%s rsvpmaker-notice is-dismissible" data-notice="%s">
<p>%s</p>
</div>',esc_attr($type),esc_attr($slug),$message);
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
function rsvpmaker_ajax_notice_handler() {
$cleared = get_option('cleared_rsvpmaker_notices');
$cleared = is_array($cleared) ? $cleared : array();
    // Pick up the notice "type" - passed via jQuery (the "data-notice" attribute on the notice)
    $cleared[] = sanitize_text_field($_REQUEST['type']);
    update_option('cleared_rsvpmaker_notices',$cleared);
}

function rsvpmaker_debug_log($msg, $context = '') {
	if(!defined('RSVPMAKER_DEBUG'))
		return;
	if(!is_string($msg))
		$msg = var_export($msg,true);
	if($context)
		error_log($context.' '.$msg);
	else
		error_log($msg);
}

function rsvpmaker_show_debug_log() {
	echo '<h2>RSVPMaker Debug Log</h2><p>Captures events from the plugin\'s operation</p>';
	if(isset($_POST['wpclear'])) {
		file_put_contents(WP_DEBUG_LOG, '');	
	}
	if(isset($_GET['wp'])) {
		$log = file_get_contents(WP_DEBUG_LOG);
		printf('<p><a href="%s">Show RSVPMaker Debug Log</a></p>', admin_url('tools.php?page=rsvpmaker_show_debug_log'));
		printf('<form method="post" action=""><input type="hidden" name="wpclear" value="1"><button>Clear WP Log</button><form>');
		echo '<textarea rows="20" style="width: 100%;">'.htmlentities($log).'</textarea>';		
		return;
	}
	printf('<p><a href="%s">Show WP DEBUG LOG</a></p>', admin_url('tools.php?page=rsvpmaker_show_debug_log&wp=1'));
	global $rsvp_options;
	$filename_base = 'rsvpmaker';
	$upload_dir   = wp_upload_dir();

	if ( ! empty( $upload_dir['basedir'] ) ) {
		$fname = $upload_dir['basedir'].'/'.$filename_base.'_log_'.date('Y-m-d').'.txt';
		$content = file_get_contents($fname);
		printf('<form method="post" action="%s"><input type="hidden" name="clear" value="1"><button>Clear Log</button></form>',admin_url('tools.php?page=rsvpmaker_show_debug_log'));
		echo '<textarea rows="20" style="width: 100%;">'.htmlentities($content).'</textarea>';		
	}

}

function rsvpmaker_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if (!empty($args[0]) && ( 'edit_post' == $cap || strpos($cap,'rsvpmaker') ) )
    {
        global $wpdb;
		$post_id = $args[0];
		$author = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID=".intval($post_id));
		$eds = get_additional_editors($post_id);
		if(!current_user_can($cap[0]) && ($author != $user_id) && in_array($user_id, $eds) )
        {
            /* Set an empty array for the caps. */
            $caps = array(); 
            $caps[] = 'edit_rsvpmakers';
			if(isset($_GET['action']) && ($_GET['action'] == 'edit'))
			{
			//if the current author is not already on the editors list, add them
			if(!$wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key='_additional_editors' AND meta_value=$author"))
				add_post_meta($post_id, '_additional_editors',$author);				
			wp_update_post(array('ID' => $post_id, 'post_author' => $user_id));
			}
        }
    }
    /* Return the capabilities required by the user. */
    return $caps;
}

function auto_renew_project ($template_id, $notify = true) {
global $rsvp_options;

$sofar = get_events_by_template($template_id);
$fts = 0;
if(!empty($sofar))
{
	$farthest = array_pop($sofar);
	echo "farthest $farthest->datetime<br>";
	$fts = rsvpmaker_strtotime($farthest->datetime);
}
if($fts > (time() + (2 * MONTH_IN_SECONDS)) )
	return; // cancel if more than 2 months worth of events in system
$sked = get_template_sked($template_id);
if(!isset($sked["week"]))
	return;
$hour = str_pad($sked['hour'],2,'0',STR_PAD_LEFT);
$minutes = str_pad($sked['minutes'],2,'0',STR_PAD_LEFT);
$added = ($fts) ? sprintf('<p>In addition to previously published dates ending %s</p>',rsvpmaker_date($rsvp_options['long_date'],$fts))."\n" : '';
$projected = rsvpmaker_get_projected($sked);
$htext = '';
$holidays = commonHolidays();
if($projected)
foreach($projected as $i => $ts)
{
if(($ts < current_time('timestamp')))
	continue; // omit dates past
if(isset($fts) && $ts <= $fts)
	continue;
$holiday_check = rsvpmaker_holiday_check($ts,$holidays);
$hthis = '';
if($holiday_check) {
	if($holiday_check['default']) {
		$htext .= '<p>'.$holiday_check['hwarn']."</p>\n";
		continue;
	}
	$htext .= '<p>'.$holiday_check['hwarn']."</p>\n";
	$hthis = $holiday_check['hwarn'];
}
$post = get_post($template_id);
$date = rsvpmaker_date('Y-m-d',$ts).' '.$sked['start_time'];
$added .= add_rsvpmaker_from_template($post, $sked, $date, $ts,$hthis);
} // end for loop

if($notify && !empty($added))
	{
		$admin = get_option('admin_email');
		$mail['subject'] = __('Dates added for ','rsvpmaker').$post->post_title;
		if(!empty($htext)) 
			$mail['subject'] .= ' - check overlap with holidays';
		$mail['html'] = "<p>Dates added according to recurring event schedule.</p>\n".$added.$htext;
		$mail['to'] = $admin;
		$mail['from'] = $admin;
		$mail['fromname'] = get_bloginfo('name');
		echo $mail['html'];
		rsvpmailer($mail);
	}
}

function add_rsvpmaker_from_template($post, $template, $date, $ts, $hthis = '') {
	global $wpdb, $rsvp_options;
	if($post->post_status != 'publish')
		return;
	$t = $post->ID;
	$timezone = get_post_meta($t,'_timezone',true);
	if(!$timezone)
		$timezone = wp_timezone_string();
	$my_post['post_title'] = $post->post_title;
	$my_post['post_content'] = $post->post_content;
	$my_post['post_status'] = 'publish';
	$my_post['post_author'] = $post->post_author;
	$my_post['post_type'] = 'rsvpmaker';
			if(empty($template["duration"]))
				$template["duration"] = '';			
			$dpart = explode(':',$template["duration"]);

			if( is_numeric($dpart[0]) )
				{
				$dtext = $cddate.' +'.$dpart[0].' hours';
				if(!empty($dpart[1]))
					$dtext .= ' +'.$dpart[1].' minutes';
				$dt = rsvpmaker_strtotime($dtext);
				$duration = rsvpmaker_date('Y-m-d H:i:s',$dt);
				}
			else
				$duration = (isset($template["duration"])) ? $template["duration"] : '';
			$my_post['post_name'] = sanitize_title($my_post['post_title'] . '-' .$date );
  			$added = '';
			if($post_id = wp_insert_post( $my_post ) )
				{
				$prettydate = rsvpmaker_date($rsvp_options['long_date'],$ts);
				$added = sprintf('<p>%s <a href="%s">%s</a> / <a href="%s">%s</a> / <a href="%s">%s</a> %s</p>',$prettydate,get_permalink($post_id),__('View','rsvpmaker'),admin_url("post.php?post=$post_id&action=edit"),__('Edit','rsvpmaker'),admin_url("edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=$post_id&trash=1"),__('Trash','rsvpmaker'),$hthis);
				add_rsvpmaker_date($post_id,$date,$duration,$timezone);
				add_post_meta($post_id,'_meet_recur',$t,true);
				$upts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$post_id);
				update_post_meta($post_id,"_updated_from_template",$upts);
				rsvpmaker_copy_metadata($t, $post_id);
			}
	return $added;
}

function rsvpautorenew_test () {
global $rsvp_options;

	global $wpdb;
	$wpdb->show_errors();

	$sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE meta_key='rsvpautorenew' AND meta_value=1 AND $wpdb->posts.post_status='publish' ";
	$results = $wpdb->get_results($sql);
	if(is_array($results))
	foreach($results as $row)
	{
		auto_renew_project ($row->ID);
	}
	$sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE meta_key='rsvpmaker_template_reminder' ";
	$results = $wpdb->get_results($sql);
	if(is_array($results))
	foreach($results as $row)
	{		
		$thours = unserialize($row->meta_value);
		$next = rsvpmaker_next_by_template($row->ID);
		if(empty($next))
			return;
		$message = get_post_meta($next->ID, '_rsvp_reminder_msg_'.$thours[0], true);
		if(!empty($message))
			continue; // already set
		$start_time = rsvpmaker_strtotime($next->datetime);
		$prettydate = rsvpmaker_date('l F jS g:i A T',rsvpmaker_strtotime($next->datetime));
		$include_event = get_post_meta($row->ID, '_rsvp_confirmation_include_event', true);
		update_post_meta($next->ID, '_rsvp_confirmation_include_event',$include_event);
		foreach($thours as $hours) {
			$message = get_post_meta($row->ID, '_rsvp_reminder_msg_'.$hours, true);
			$subject = get_post_meta($row->ID, '_rsvp_reminder_subject_'.$hours, true);
			$subject = str_replace('[datetime]',$prettydate,$subject);
			update_post_meta($next->ID, '_rsvp_reminder_msg_'.$hours,$message);
			update_post_meta($next->ID, '_rsvp_reminder_subject_'.$hours,$subject);
			rsvpmaker_reminder_cron($hours, $start_time, $next->ID);
		}
	}
}

function rsvpmaker_template_checkbox_post () {
if(empty($_POST) || empty($_REQUEST['t']) || empty($_REQUEST['page']) || ($_REQUEST['page'] != 'rsvpmaker_template_list'))
	return;
global $wpdb, $current_user;
$t = (int) $_REQUEST['t'];
$post = get_post($t);
$template = $sked = get_template_sked($t);
$template['hour'] = (int) $template['hour'];
if($template['hour'] < 10)
	$template['hour'] = $sked['hour'] = '0'.$template['hour']; // make sure of zero padding
$hour = $sked['hour'];
$minutes = $sked['minutes'];
$update_messages = '';
$timezone = get_post_meta($t,'_timezone',true);
if(!$timezone)
	$timezone = wp_timezone_string();

if(!empty($_POST['trash_template']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	foreach($_POST['trash_template'] as $id)
		wp_trash_post((int) $id);
	$count = sizeof($_POST['trash_template']);
	$update_messages = '<div class="updated">'.$count.' '.__('event posts moved to trash','rsvpmaker').'</div>';
}

if(isset($_POST["update_from_template"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		foreach($_POST["update_from_template"] as $target_id)
			{
				$target_id = (int) $target_id;
				if(!current_user_can('publish_rsvpmakers'))
					{
						$update_messages .= '<div class="updated">Error</div>';
						break;
					}
				if(!empty($_POST['metadata_only'])) {
					rsvpmaker_copy_metadata($t, $target_id);
					$update_messages .= '<div class="updated">Updated: metadata for event #'.$target_id.' <a href="post.php?action=edit&post='.$target_id.'">Edit</a> / <a href="'.get_post_permalink($target_id).'">View</a></div>';	
					continue;
				}
				$update_post['ID'] = $target_id;
				$update_post['post_title'] = $post->post_title;
				$update_post['post_content'] = $post->post_content;
				$update_post['post_excerpt'] = $post->post_excerpt;
				wp_update_post($update_post);
				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$target_id);
				update_post_meta($target_id,"_updated_from_template",$ts);
				update_post_meta($target_id,"_meet_recur",$t);
				$duration = (empty($template["duration"])) ? '' : $template["duration"];
				$end_time = (empty($template['end'])) ? '' : $template['end'];
				$event = get_rsvpmaker_event($target_id);
				$cddate = isset($event->date) ? $event->date : '';
				if(!empty($cddate))
					{
					$parts = explode(' ',$cddate);
					$cddate = $parts[0].' '.$template['hour'].':'.$template['minutes'].':00';
					update_rsvpmaker_date($target_id,$cddate,$duration,$end_time);
					}
				if(isset($rsvptypes))
					wp_set_object_terms( $target_id, $rsvptypes, 'rsvpmaker-type', true );

				$update_messages .= '<div class="updated">Updated: event #'.$target_id.' <a href="post.php?action=edit&post='.$target_id.'">Edit</a> / <a href="'.get_post_permalink($target_id).'">View</a></div>';	
			}
	}

if(isset($_POST["detach_from_template"])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
	if(!current_user_can('publish_rsvpmakers'))
	{
		$update_messages .= '<div class="updated">Error</div>';
	}
	else
	foreach($_POST["detach_from_template"] as $target_id)
		{
			$target_id = (int) $target_id;
			$sql = $wpdb->prepare("UPDATE $wpdb->postmeta SET meta_key='_detached_from_template' WHERE meta_key='_meet_recur' AND post_id=%d", $target_id);
			$result = $wpdb->query($sql);
			$update_messages .= '<div class="updated">Detached from Template: event #'.$target_id.' <a href="post.php?action=edit&post='.$target_id.'">Edit</a> / <a href="'.get_post_permalink($target_id).'">View</a></div>';	
		}
}
if(isset($_POST["recur_check"])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
	$my_post['post_title'] = $post->post_title;
	$my_post['post_content'] = $post->post_content;
	$my_post['post_excerpt'] = $post->post_excerpt;
	$my_post['post_status'] = (($_POST['newstatus'] == 'publish') && current_user_can('publish_rsvpmakers')) ? 'publish' : 'draft';
	$my_post['post_author'] = $current_user->ID;
	$my_post['post_type'] = 'rsvpmaker';
	$topnumber = (int) get_post_meta($post->ID,'rsvpeventnumber_top',true);
	$update_messages .= sprintf('<p>Top # from template %d</p>',$topnumber);

	foreach($_POST["recur_check"] as $index => $on)
		{
			$year = $y = (int) $_POST["recur_year"][$index];
			$cddate = format_cddate($year, sanitize_text_field($_POST["recur_month"][$index]), sanitize_text_field($_POST["recur_day"][$index]), $hour, $minutes);
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
				$dt = rsvpmaker_strtotime($dtext);
				$duration = rsvpmaker_date('Y-m-d H:i:s',$dt);
				}
			else{
				$duration = (isset($template["duration"])) ? $template["duration"] : '';
			}

			if(!empty($_POST["recur_title"][$index])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
				$my_post['post_title'] = sanitize_text_field($_POST["recur_title"][$index]);

			$my_post['post_name'] = $my_post['post_title'] . '-' .$date;
			$singular = __('Event','rsvpmaker');
// Insert the post into the database
  			if($post_id = wp_insert_post( $my_post ) )
				{
				$end_time = (empty($template['end'])) ? '' : $template['end'];	
				//$timezone = rsvpmaker_get_timezone_string($t);
				rsvpmaker_add_event_row ($post_id, $cddate, $end_time, $duration,$timezone,$my_post['post_title']);
				if($my_post["post_status"] == 'publish')
					$update_messages .=  '<div class="updated">Posted: event for '.$cddate.' <a href="post.php?action=edit&post='.$post_id.'">Edit</a> / <a href="'.get_post_permalink($post_id).'">View</a></div>';
				else
					$update_messages .= '<div class="updated">Draft for '.$cddate.' <a href="post.php?action=edit&post='.$post_id.'">Edit</a> / <a href="'.get_post_permalink($post_id).'">Preview</a></div>';

				add_post_meta($post_id,'_meet_recur',$t,true);
				$ts = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID=".$post_id);
				update_post_meta($post_id,"_updated_from_template",$ts);
				rsvpmaker_copy_metadata($t, $post_id);
				if($topnumber) {
					update_post_meta($post_id,'rsvpeventnumber',$topnumber);
					$update_messages .= sprintf('<div>post_id %s top %d</div>',$post_id,$topnumber);
					$topnumber++;
				}

				}

		}
		update_post_meta($post->ID,'rsvpeventnumber_top',$topnumber);
}

if(isset($_POST["nomeeting"])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  )
{
	$my_post['post_title'] = __('No Meeting','rsvpmaker').': '.$post->post_title;
	$my_post['post_content'] = sanitize_textarea_field($_POST["nomeeting_note"]);
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
				$update_messages .= '<div class="updated">'."Error: $sql.</div>\n";
			else
				$update_messages .=  '<div class="updated">Updated: no meeting <a href="post.php?action=edit&post='.$post_id.'">Edit</a> / <a href="'.get_post_permalink($id).'">View</a></div>';	
		}
	else
		{
			$cddate = sanitize_text_field($_POST["nomeeting"]).' 00:00:00';
			$my_post['post_name'] = $my_post['post_title'] . '-' .$cddate;

// Insert the post into the database
  			if($post_id = wp_insert_post( $my_post ) )
				{
				add_rsvpmaker_date($post_id,$cddate,'allday','',0,$timezone);
				$update_messages .=  '<div class="updated">Posted: event for '.$cddate.' <a href="post.php?action=edit&post='.$post_id.'">Edit</a> / <a href="'.get_post_permalink($post_id).'">View</a></div>';	
				add_post_meta($post_id,'_meet_recur',$t,true);
				}
		}		
}
	update_post_meta($t,'update_messages',$update_messages);
	header('Location: ' . admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&update_messages=1&t='.$t));
	die();
}

function rsvpmaker_copy_metadata($source_id, $target_id) {
global $wpdb;
$log = '';
//copy metadata
$meta_keys = array();
$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$source_id");
$post_meta_infos = apply_filters('rsvpmaker_meta_update_from_template',$post_meta_infos);
$deadlinedays = $deadlinehours = $regdays = $reghours = 0;
$meta_protect = array('_rsvp_reminder', '_sked', '_edit_lock','_additional_editors','rsvpautorenew','_meet_recur');
if(isset($_POST['metadata_only']))
	$meta_protect[] = '_thumbnail_id';

		if (count($post_meta_infos)!=0) {
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;
				if(in_array($meta_key,$meta_keys))
					continue;
				$meta_keys[] = $meta_key;
				if(in_array($meta_key, $meta_protect) || strpos($meta_key,'sked') ) // should filter out _sked_Monday etc
				{
					$log .= 'Skip '.$meta_key.'<br />';
					continue;					
				}
				elseif(strpos($meta_key,'_note') || preg_match('/^_[A-Z]/',$meta_key) ) //agenda note or any other note
					{
						$log .= 'Skip '.$meta_key.'<br />';
						continue;	
					}
				else
				{
					$log .= 'Copy '.$meta_key.': '.$meta_info->meta_value.'<br />';			
				}
				if('_rsvp_coupons' == $meta_key && !empty($meta_info->meta_value) ) {
					$coupons = unserialize($meta_info->meta_value);
					continue;
				}
				elseif(strpos($meta_key,'coupon'))
					continue;
				if(is_serialized($meta_info->meta_value))
					update_post_meta($target_id,$meta_key,unserialize($meta_info->meta_value));
				else
					update_post_meta($target_id,$meta_key,$meta_info->meta_value);
				if($meta_key == '_rsvp_deadline_daysbefore')
					$deadlinedays = $meta_info->meta_value;		
				if($meta_key == '_rsvp_deadline_hours')
					$deadlinehours = $meta_info->meta_value;		
				if($meta_key == '_rsvp_reg_daysbefore')
					$regdays = $meta_info->meta_value;		
				if($meta_key == '_rsvp_reg_hours')
					$reghours = $meta_info->meta_value;		
			}
		}

if(!empty($coupons)) {
	$new_coupons = array('coupon_codes'=>array(),'coupon_discounts'=>array(),'coupon_methods'=>array());
	delete_post_meta($target_id,'_rsvp_coupon_code');
	delete_post_meta($target_id,'_rsvp_coupon_discount');
	delete_post_meta($target_id,'_rsvp_coupon_method');
	foreach($coupons['coupon_codes'] as $code) {
		$code = str_replace('post_id',$target_id,$code);
		$new_coupons['coupon_codes'][] = $code;
		add_post_meta($target_id,'_rsvp_coupon_code',$code);
	}
	foreach($coupons['coupon_discounts'] as $code) {
		$new_coupons['coupon_discounts'][] = $code;
		add_post_meta($target_id,'_rsvp_coupon_discount',$code);
	}
	foreach($coupons['coupon_methods'] as $code) {
		$new_coupons['coupon_methods'][] = $code;
		add_post_meta($target_id,'_rsvp_coupon_method',$code);
	}
	add_post_meta($target_id,'_rsvp_coupons',$new_coupons);
}

if(!empty($deadlinedays) || !empty($deadlinehours))
	rsvpmaker_deadline_from_template($target_id,$deadlinedays,$deadlinehours);
if(!empty($regdays) || !empty($reghours))
	rsvpmaker_reg_from_template($target_id,$regdays,$reghours);

$terms = get_the_terms( $source_id, 'rsvpmaker-type' );						
if ( $terms && ! is_wp_error( $terms ) ) { 
	$rsvptypes = array();

	foreach ( $terms as $term ) {
		$rsvptypes[] = $term->term_id;
	}
wp_set_object_terms( $target_id, $rsvptypes, 'rsvpmaker-type', true );

	} 
}

function rsvpmaker_deadline_from_template($target_id,$deadlinedays,$deadlinehours) {
	$t = get_rsvpmaker_timestamp($target_id);
	if(!empty($deadlinedays))
		$t -= ($deadlinedays * 60 * 60 * 24);
	if(!empty($deadlinehours))
		$t -= ($deadlinehours * 60 * 60);
	update_post_meta($target_id,'_rsvp_deadline',$t);
}

function rsvpmaker_reg_from_template($target_id,$days,$hours) {
	$t = get_rsvpmaker_timestamp($target_id);
	if(!empty($days))
		$t -= ($days * 60 * 60 * 24);
	if(!empty($hours))
		$t -= ($hours * 60 * 60);
	update_post_meta($target_id,'_rsvp_start',$t);
}

function rsvp_time_options ($post_id) {
global $rsvp_options;
$forms = rsvpmaker_get_forms();
if(empty($post_id))
{
	$icons = $rsvp_options["calendar_icons"];
	$add_timezone = $rsvp_options["add_timezone"];
	$convert_timezone = $rsvp_options["convert_timezone"];
	$rsvp_timezone = '';
}
else {
	$icons = get_post_meta($post_id,"_calendar_icons",true);
	$add_timezone = get_post_meta($post_id,"_add_timezone",true);
	$convert_timezone = get_post_meta($post_id,"_convert_timezone",true);
	$rsvp_timezone = get_post_meta($post_id,"_rsvp_timezone_string",true);	
}
if(isset($_GET['page']) && ( ($_GET['page'] == 'rsvpmaker_details') ) )
{
?>
<input type="checkbox" name="calendar_icons" value="1" <?php if($icons) echo ' checked="checked" ';?> /> <?php esc_html_e('Show Add to Google / Download to Outlook (iCal) icons','rsvpmaker'); ?> 
<br />
<p id="timezone_options">
<?php
if(!strpos($rsvp_options["time_format"],'T') )
{
?>
<input type="checkbox" name="add_timezone" value="1" <?php if($add_timezone) echo ' checked="checked" '; ?> /><?php esc_html_e('Display timezone code as part of date/time','rsvpmaker'); echo ' '; ?>
<?php
}
?>
<input type="checkbox" name="convert_timezone" value="1" <?php if($convert_timezone) echo ' checked="checked" '; ?> /><?php esc_html_e('Show timezone conversion button next to calendar icons','rsvpmaker'); ?>
</p>
<p>Timezone <select id="timezone_string" name="setrsvp[timezone_string]">
	<option value="<?php echo esc_attr($rsvp_timezone);?>"><?php echo (empty($rsvp_timezone)) ? __('Default','rsvpmaker') : $rsvp_timezone?></option>
<optgroup label="U.S. Mainland">
<option value="America/New_York">New York</option>
<option value="America/Chicago">Chicago</option>
<option value="America/Denver">Denver</option>
<option value="America/Los_Angeles">Los Angeles</option>
</optgroup>
<optgroup label="Africa">
<option value="Africa/Abidjan">Abidjan</option>
<option value="Africa/Accra">Accra</option>
<option value="Africa/Addis_Ababa">Addis Ababa</option>
<option value="Africa/Algiers">Algiers</option>
<option value="Africa/Asmara">Asmara</option>
<option value="Africa/Bamako">Bamako</option>
<option value="Africa/Bangui">Bangui</option>
<option value="Africa/Banjul">Banjul</option>
<option value="Africa/Bissau">Bissau</option>
<option value="Africa/Blantyre">Blantyre</option>
<option value="Africa/Brazzaville">Brazzaville</option>
<option value="Africa/Bujumbura">Bujumbura</option>
<option value="Africa/Cairo">Cairo</option>
<option value="Africa/Casablanca">Casablanca</option>
<option value="Africa/Ceuta">Ceuta</option>
<option value="Africa/Conakry">Conakry</option>
<option value="Africa/Dakar">Dakar</option>
<option value="Africa/Dar_es_Salaam">Dar es Salaam</option>
<option value="Africa/Djibouti">Djibouti</option>
<option value="Africa/Douala">Douala</option>
<option value="Africa/El_Aaiun">El Aaiun</option>
<option value="Africa/Freetown">Freetown</option>
<option value="Africa/Gaborone">Gaborone</option>
<option value="Africa/Harare">Harare</option>
<option value="Africa/Johannesburg">Johannesburg</option>
<option value="Africa/Juba">Juba</option>
<option value="Africa/Kampala">Kampala</option>
<option value="Africa/Khartoum">Khartoum</option>
<option value="Africa/Kigali">Kigali</option>
<option value="Africa/Kinshasa">Kinshasa</option>
<option value="Africa/Lagos">Lagos</option>
<option value="Africa/Libreville">Libreville</option>
<option value="Africa/Lome">Lome</option>
<option value="Africa/Luanda">Luanda</option>
<option value="Africa/Lubumbashi">Lubumbashi</option>
<option value="Africa/Lusaka">Lusaka</option>
<option value="Africa/Malabo">Malabo</option>
<option value="Africa/Maputo">Maputo</option>
<option value="Africa/Maseru">Maseru</option>
<option value="Africa/Mbabane">Mbabane</option>
<option value="Africa/Mogadishu">Mogadishu</option>
<option value="Africa/Monrovia">Monrovia</option>
<option value="Africa/Nairobi">Nairobi</option>
<option value="Africa/Ndjamena">Ndjamena</option>
<option value="Africa/Niamey">Niamey</option>
<option value="Africa/Nouakchott">Nouakchott</option>
<option value="Africa/Ouagadougou">Ouagadougou</option>
<option value="Africa/Porto-Novo">Porto-Novo</option>
<option value="Africa/Sao_Tome">Sao Tome</option>
<option value="Africa/Tripoli">Tripoli</option>
<option value="Africa/Tunis">Tunis</option>
<option value="Africa/Windhoek">Windhoek</option>
</optgroup>
<optgroup label="America">
<option value="America/Adak">Adak</option>
<option value="America/Anchorage">Anchorage</option>
<option value="America/Anguilla">Anguilla</option>
<option value="America/Antigua">Antigua</option>
<option value="America/Araguaina">Araguaina</option>
<option value="America/Argentina/Buenos_Aires">Argentina - Buenos Aires</option>
<option value="America/Argentina/Catamarca">Argentina - Catamarca</option>
<option value="America/Argentina/Cordoba">Argentina - Cordoba</option>
<option value="America/Argentina/Jujuy">Argentina - Jujuy</option>
<option value="America/Argentina/La_Rioja">Argentina - La Rioja</option>
<option value="America/Argentina/Mendoza">Argentina - Mendoza</option>
<option value="America/Argentina/Rio_Gallegos">Argentina - Rio Gallegos</option>
<option value="America/Argentina/Salta">Argentina - Salta</option>
<option value="America/Argentina/San_Juan">Argentina - San Juan</option>
<option value="America/Argentina/San_Luis">Argentina - San Luis</option>
<option value="America/Argentina/Tucuman">Argentina - Tucuman</option>
<option value="America/Argentina/Ushuaia">Argentina - Ushuaia</option>
<option value="America/Aruba">Aruba</option>
<option value="America/Asuncion">Asuncion</option>
<option value="America/Atikokan">Atikokan</option>
<option value="America/Bahia">Bahia</option>
<option value="America/Bahia_Banderas">Bahia Banderas</option>
<option value="America/Barbados">Barbados</option>
<option value="America/Belem">Belem</option>
<option value="America/Belize">Belize</option>
<option value="America/Blanc-Sablon">Blanc-Sablon</option>
<option value="America/Boa_Vista">Boa Vista</option>
<option value="America/Bogota">Bogota</option>
<option value="America/Boise">Boise</option>
<option value="America/Cambridge_Bay">Cambridge Bay</option>
<option value="America/Campo_Grande">Campo Grande</option>
<option value="America/Cancun">Cancun</option>
<option value="America/Caracas">Caracas</option>
<option value="America/Cayenne">Cayenne</option>
<option value="America/Cayman">Cayman</option>
<option value="America/Chicago">Chicago</option>
<option value="America/Chihuahua">Chihuahua</option>
<option value="America/Costa_Rica">Costa Rica</option>
<option value="America/Creston">Creston</option>
<option value="America/Cuiaba">Cuiaba</option>
<option value="America/Curacao">Curacao</option>
<option value="America/Danmarkshavn">Danmarkshavn</option>
<option value="America/Dawson">Dawson</option>
<option value="America/Dawson_Creek">Dawson Creek</option>
<option value="America/Denver">Denver</option>
<option value="America/Detroit">Detroit</option>
<option value="America/Dominica">Dominica</option>
<option value="America/Edmonton">Edmonton</option>
<option value="America/Eirunepe">Eirunepe</option>
<option value="America/El_Salvador">El Salvador</option>
<option value="America/Fortaleza">Fortaleza</option>
<option value="America/Glace_Bay">Glace Bay</option>
<option value="America/Godthab">Godthab</option>
<option value="America/Goose_Bay">Goose Bay</option>
<option value="America/Grand_Turk">Grand Turk</option>
<option value="America/Grenada">Grenada</option>
<option value="America/Guadeloupe">Guadeloupe</option>
<option value="America/Guatemala">Guatemala</option>
<option value="America/Guayaquil">Guayaquil</option>
<option value="America/Guyana">Guyana</option>
<option value="America/Halifax">Halifax</option>
<option value="America/Havana">Havana</option>
<option value="America/Hermosillo">Hermosillo</option>
<option value="America/Indiana/Indianapolis">Indiana - Indianapolis</option>
<option value="America/Indiana/Knox">Indiana - Knox</option>
<option value="America/Indiana/Marengo">Indiana - Marengo</option>
<option value="America/Indiana/Petersburg">Indiana - Petersburg</option>
<option value="America/Indiana/Tell_City">Indiana - Tell City</option>
<option value="America/Indiana/Vevay">Indiana - Vevay</option>
<option value="America/Indiana/Vincennes">Indiana - Vincennes</option>
<option value="America/Indiana/Winamac">Indiana - Winamac</option>
<option value="America/Inuvik">Inuvik</option>
<option value="America/Iqaluit">Iqaluit</option>
<option value="America/Jamaica">Jamaica</option>
<option value="America/Juneau">Juneau</option>
<option value="America/Kentucky/Louisville">Kentucky - Louisville</option>
<option value="America/Kentucky/Monticello">Kentucky - Monticello</option>
<option value="America/Kralendijk">Kralendijk</option>
<option value="America/La_Paz">La Paz</option>
<option value="America/Lima">Lima</option>
<option value="America/Los_Angeles">Los Angeles</option>
<option value="America/Lower_Princes">Lower Princes</option>
<option value="America/Maceio">Maceio</option>
<option value="America/Managua">Managua</option>
<option value="America/Manaus">Manaus</option>
<option value="America/Marigot">Marigot</option>
<option value="America/Martinique">Martinique</option>
<option value="America/Matamoros">Matamoros</option>
<option value="America/Mazatlan">Mazatlan</option>
<option value="America/Menominee">Menominee</option>
<option value="America/Merida">Merida</option>
<option value="America/Metlakatla">Metlakatla</option>
<option value="America/Mexico_City">Mexico City</option>
<option value="America/Miquelon">Miquelon</option>
<option value="America/Moncton">Moncton</option>
<option value="America/Monterrey">Monterrey</option>
<option value="America/Montevideo">Montevideo</option>
<option value="America/Montserrat">Montserrat</option>
<option value="America/Nassau">Nassau</option>
<option value="America/New_York">New York</option>
<option value="America/Nipigon">Nipigon</option>
<option value="America/Nome">Nome</option>
<option value="America/Noronha">Noronha</option>
<option value="America/North_Dakota/Beulah">North Dakota - Beulah</option>
<option value="America/North_Dakota/Center">North Dakota - Center</option>
<option value="America/North_Dakota/New_Salem">North Dakota - New Salem</option>
<option value="America/Ojinaga">Ojinaga</option>
<option value="America/Panama">Panama</option>
<option value="America/Pangnirtung">Pangnirtung</option>
<option value="America/Paramaribo">Paramaribo</option>
<option value="America/Phoenix">Phoenix</option>
<option value="America/Port-au-Prince">Port-au-Prince</option>
<option value="America/Port_of_Spain">Port of Spain</option>
<option value="America/Porto_Velho">Porto Velho</option>
<option value="America/Puerto_Rico">Puerto Rico</option>
<option value="America/Rainy_River">Rainy River</option>
<option value="America/Rankin_Inlet">Rankin Inlet</option>
<option value="America/Recife">Recife</option>
<option value="America/Regina">Regina</option>
<option value="America/Resolute">Resolute</option>
<option value="America/Rio_Branco">Rio Branco</option>
<option value="America/Santa_Isabel">Santa Isabel</option>
<option value="America/Santarem">Santarem</option>
<option value="America/Santiago">Santiago</option>
<option value="America/Santo_Domingo">Santo Domingo</option>
<option value="America/Sao_Paulo">Sao Paulo</option>
<option value="America/Scoresbysund">Scoresbysund</option>
<option value="America/Sitka">Sitka</option>
<option value="America/St_Barthelemy">St Barthelemy</option>
<option value="America/St_Johns">St Johns</option>
<option value="America/St_Kitts">St Kitts</option>
<option value="America/St_Lucia">St Lucia</option>
<option value="America/St_Thomas">St Thomas</option>
<option value="America/St_Vincent">St Vincent</option>
<option value="America/Swift_Current">Swift Current</option>
<option value="America/Tegucigalpa">Tegucigalpa</option>
<option value="America/Thule">Thule</option>
<option value="America/Thunder_Bay">Thunder Bay</option>
<option value="America/Tijuana">Tijuana</option>
<option value="America/Toronto">Toronto</option>
<option value="America/Tortola">Tortola</option>
<option value="America/Vancouver">Vancouver</option>
<option value="America/Whitehorse">Whitehorse</option>
<option value="America/Winnipeg">Winnipeg</option>
<option value="America/Yakutat">Yakutat</option>
<option value="America/Yellowknife">Yellowknife</option>
</optgroup>
<optgroup label="Antarctica">
<option value="Antarctica/Casey">Casey</option>
<option value="Antarctica/Davis">Davis</option>
<option value="Antarctica/DumontDUrville">DumontDUrville</option>
<option value="Antarctica/Macquarie">Macquarie</option>
<option value="Antarctica/Mawson">Mawson</option>
<option value="Antarctica/McMurdo">McMurdo</option>
<option value="Antarctica/Palmer">Palmer</option>
<option value="Antarctica/Rothera">Rothera</option>
<option value="Antarctica/Syowa">Syowa</option>
<option value="Antarctica/Troll">Troll</option>
<option value="Antarctica/Vostok">Vostok</option>
</optgroup>
<optgroup label="Arctic">
<option value="Arctic/Longyearbyen">Longyearbyen</option>
</optgroup>
<optgroup label="Asia">
<option value="Asia/Aden">Aden</option>
<option value="Asia/Almaty">Almaty</option>
<option value="Asia/Amman">Amman</option>
<option value="Asia/Anadyr">Anadyr</option>
<option value="Asia/Aqtau">Aqtau</option>
<option value="Asia/Aqtobe">Aqtobe</option>
<option value="Asia/Ashgabat">Ashgabat</option>
<option value="Asia/Baghdad">Baghdad</option>
<option value="Asia/Bahrain">Bahrain</option>
<option value="Asia/Baku">Baku</option>
<option value="Asia/Bangkok">Bangkok</option>
<option value="Asia/Beirut">Beirut</option>
<option value="Asia/Bishkek">Bishkek</option>
<option value="Asia/Brunei">Brunei</option>
<option value="Asia/Chita">Chita</option>
<option value="Asia/Choibalsan">Choibalsan</option>
<option value="Asia/Colombo">Colombo</option>
<option value="Asia/Damascus">Damascus</option>
<option value="Asia/Dhaka">Dhaka</option>
<option value="Asia/Dili">Dili</option>
<option value="Asia/Dubai">Dubai</option>
<option value="Asia/Dushanbe">Dushanbe</option>
<option value="Asia/Gaza">Gaza</option>
<option value="Asia/Hebron">Hebron</option>
<option value="Asia/Ho_Chi_Minh">Ho Chi Minh</option>
<option value="Asia/Hong_Kong">Hong Kong</option>
<option value="Asia/Hovd">Hovd</option>
<option value="Asia/Irkutsk">Irkutsk</option>
<option value="Asia/Jakarta">Jakarta</option>
<option value="Asia/Jayapura">Jayapura</option>
<option value="Asia/Jerusalem">Jerusalem</option>
<option value="Asia/Kabul">Kabul</option>
<option value="Asia/Kamchatka">Kamchatka</option>
<option value="Asia/Karachi">Karachi</option>
<option value="Asia/Kathmandu">Kathmandu</option>
<option value="Asia/Khandyga">Khandyga</option>
<option value="Asia/Kolkata">Kolkata</option>
<option value="Asia/Krasnoyarsk">Krasnoyarsk</option>
<option value="Asia/Kuala_Lumpur">Kuala Lumpur</option>
<option value="Asia/Kuching">Kuching</option>
<option value="Asia/Kuwait">Kuwait</option>
<option value="Asia/Macau">Macau</option>
<option value="Asia/Magadan">Magadan</option>
<option value="Asia/Makassar">Makassar</option>
<option value="Asia/Manila">Manila</option>
<option value="Asia/Muscat">Muscat</option>
<option value="Asia/Nicosia">Nicosia</option>
<option value="Asia/Novokuznetsk">Novokuznetsk</option>
<option value="Asia/Novosibirsk">Novosibirsk</option>
<option value="Asia/Omsk">Omsk</option>
<option value="Asia/Oral">Oral</option>
<option value="Asia/Phnom_Penh">Phnom Penh</option>
<option value="Asia/Pontianak">Pontianak</option>
<option value="Asia/Pyongyang">Pyongyang</option>
<option value="Asia/Qatar">Qatar</option>
<option value="Asia/Qyzylorda">Qyzylorda</option>
<option value="Asia/Rangoon">Rangoon</option>
<option value="Asia/Riyadh">Riyadh</option>
<option value="Asia/Sakhalin">Sakhalin</option>
<option value="Asia/Samarkand">Samarkand</option>
<option value="Asia/Seoul">Seoul</option>
<option value="Asia/Shanghai">Shanghai</option>
<option value="Asia/Singapore">Singapore</option>
<option value="Asia/Srednekolymsk">Srednekolymsk</option>
<option value="Asia/Taipei">Taipei</option>
<option value="Asia/Tashkent">Tashkent</option>
<option value="Asia/Tbilisi">Tbilisi</option>
<option value="Asia/Tehran">Tehran</option>
<option value="Asia/Thimphu">Thimphu</option>
<option value="Asia/Tokyo">Tokyo</option>
<option value="Asia/Ulaanbaatar">Ulaanbaatar</option>
<option value="Asia/Urumqi">Urumqi</option>
<option value="Asia/Ust-Nera">Ust-Nera</option>
<option value="Asia/Vientiane">Vientiane</option>
<option value="Asia/Vladivostok">Vladivostok</option>
<option value="Asia/Yakutsk">Yakutsk</option>
<option value="Asia/Yekaterinburg">Yekaterinburg</option>
<option value="Asia/Yerevan">Yerevan</option>
</optgroup>
<optgroup label="Atlantic">
<option value="Atlantic/Azores">Azores</option>
<option value="Atlantic/Bermuda">Bermuda</option>
<option value="Atlantic/Canary">Canary</option>
<option value="Atlantic/Cape_Verde">Cape Verde</option>
<option value="Atlantic/Faroe">Faroe</option>
<option value="Atlantic/Madeira">Madeira</option>
<option value="Atlantic/Reykjavik">Reykjavik</option>
<option value="Atlantic/South_Georgia">South Georgia</option>
<option value="Atlantic/Stanley">Stanley</option>
<option value="Atlantic/St_Helena">St Helena</option>
</optgroup>
<optgroup label="Australia">
<option value="Australia/Adelaide">Adelaide</option>
<option value="Australia/Brisbane">Brisbane</option>
<option value="Australia/Broken_Hill">Broken Hill</option>
<option value="Australia/Currie">Currie</option>
<option value="Australia/Darwin">Darwin</option>
<option value="Australia/Eucla">Eucla</option>
<option value="Australia/Hobart">Hobart</option>
<option value="Australia/Lindeman">Lindeman</option>
<option value="Australia/Lord_Howe">Lord Howe</option>
<option value="Australia/Melbourne">Melbourne</option>
<option value="Australia/Perth">Perth</option>
<option value="Australia/Sydney">Sydney</option>
</optgroup>
<optgroup label="Europe">
<option value="Europe/Amsterdam">Amsterdam</option>
<option value="Europe/Andorra">Andorra</option>
<option value="Europe/Athens">Athens</option>
<option value="Europe/Belgrade">Belgrade</option>
<option value="Europe/Berlin">Berlin</option>
<option value="Europe/Bratislava">Bratislava</option>
<option value="Europe/Brussels">Brussels</option>
<option value="Europe/Bucharest">Bucharest</option>
<option value="Europe/Budapest">Budapest</option>
<option value="Europe/Busingen">Busingen</option>
<option value="Europe/Chisinau">Chisinau</option>
<option value="Europe/Copenhagen">Copenhagen</option>
<option value="Europe/Dublin">Dublin</option>
<option value="Europe/Gibraltar">Gibraltar</option>
<option value="Europe/Guernsey">Guernsey</option>
<option value="Europe/Helsinki">Helsinki</option>
<option value="Europe/Isle_of_Man">Isle of Man</option>
<option value="Europe/Istanbul">Istanbul</option>
<option value="Europe/Jersey">Jersey</option>
<option value="Europe/Kaliningrad">Kaliningrad</option>
<option value="Europe/Kiev">Kiev</option>
<option value="Europe/Lisbon">Lisbon</option>
<option value="Europe/Ljubljana">Ljubljana</option>
<option value="Europe/London">London</option>
<option value="Europe/Luxembourg">Luxembourg</option>
<option value="Europe/Madrid">Madrid</option>
<option value="Europe/Malta">Malta</option>
<option value="Europe/Mariehamn">Mariehamn</option>
<option value="Europe/Minsk">Minsk</option>
<option value="Europe/Monaco">Monaco</option>
<option value="Europe/Moscow">Moscow</option>
<option value="Europe/Oslo">Oslo</option>
<option value="Europe/Paris">Paris</option>
<option value="Europe/Podgorica">Podgorica</option>
<option value="Europe/Prague">Prague</option>
<option value="Europe/Riga">Riga</option>
<option value="Europe/Rome">Rome</option>
<option value="Europe/Samara">Samara</option>
<option value="Europe/San_Marino">San Marino</option>
<option value="Europe/Sarajevo">Sarajevo</option>
<option value="Europe/Simferopol">Simferopol</option>
<option value="Europe/Skopje">Skopje</option>
<option value="Europe/Sofia">Sofia</option>
<option value="Europe/Stockholm">Stockholm</option>
<option value="Europe/Tallinn">Tallinn</option>
<option value="Europe/Tirane">Tirane</option>
<option value="Europe/Uzhgorod">Uzhgorod</option>
<option value="Europe/Vaduz">Vaduz</option>
<option value="Europe/Vatican">Vatican</option>
<option value="Europe/Vienna">Vienna</option>
<option value="Europe/Vilnius">Vilnius</option>
<option value="Europe/Volgograd">Volgograd</option>
<option value="Europe/Warsaw">Warsaw</option>
<option value="Europe/Zagreb">Zagreb</option>
<option value="Europe/Zaporozhye">Zaporozhye</option>
<option value="Europe/Zurich">Zurich</option>
</optgroup>
<optgroup label="Indian">
<option value="Indian/Antananarivo">Antananarivo</option>
<option value="Indian/Chagos">Chagos</option>
<option value="Indian/Christmas">Christmas</option>
<option value="Indian/Cocos">Cocos</option>
<option value="Indian/Comoro">Comoro</option>
<option value="Indian/Kerguelen">Kerguelen</option>
<option value="Indian/Mahe">Mahe</option>
<option value="Indian/Maldives">Maldives</option>
<option value="Indian/Mauritius">Mauritius</option>
<option value="Indian/Mayotte">Mayotte</option>
<option value="Indian/Reunion">Reunion</option>
</optgroup>
<optgroup label="Pacific">
<option value="Pacific/Apia">Apia</option>
<option value="Pacific/Auckland">Auckland</option>
<option value="Pacific/Chatham">Chatham</option>
<option value="Pacific/Chuuk">Chuuk</option>
<option value="Pacific/Easter">Easter</option>
<option value="Pacific/Efate">Efate</option>
<option value="Pacific/Enderbury">Enderbury</option>
<option value="Pacific/Fakaofo">Fakaofo</option>
<option value="Pacific/Fiji">Fiji</option>
<option value="Pacific/Funafuti">Funafuti</option>
<option value="Pacific/Galapagos">Galapagos</option>
<option value="Pacific/Gambier">Gambier</option>
<option value="Pacific/Guadalcanal">Guadalcanal</option>
<option value="Pacific/Guam">Guam</option>
<option value="Pacific/Honolulu">Honolulu</option>
<option value="Pacific/Johnston">Johnston</option>
<option value="Pacific/Kiritimati">Kiritimati</option>
<option value="Pacific/Kosrae">Kosrae</option>
<option value="Pacific/Kwajalein">Kwajalein</option>
<option value="Pacific/Majuro">Majuro</option>
<option value="Pacific/Marquesas">Marquesas</option>
<option value="Pacific/Midway">Midway</option>
<option value="Pacific/Nauru">Nauru</option>
<option value="Pacific/Niue">Niue</option>
<option value="Pacific/Norfolk">Norfolk</option>
<option value="Pacific/Noumea">Noumea</option>
<option value="Pacific/Pago_Pago">Pago Pago</option>
<option value="Pacific/Palau">Palau</option>
<option value="Pacific/Pitcairn">Pitcairn</option>
<option value="Pacific/Pohnpei">Pohnpei</option>
<option value="Pacific/Port_Moresby">Port Moresby</option>
<option value="Pacific/Rarotonga">Rarotonga</option>
<option value="Pacific/Saipan">Saipan</option>
<option value="Pacific/Tahiti">Tahiti</option>
<option value="Pacific/Tarawa">Tarawa</option>
<option value="Pacific/Tongatapu">Tongatapu</option>
<option value="Pacific/Wake">Wake</option>
<option value="Pacific/Wallis">Wallis</option>
</optgroup>
<optgroup label="UTC">
<option value="UTC">UTC</option>
</optgroup>
<optgroup label="Manual Offsets">
<option value="UTC-12">UTC-12</option>
<option value="UTC-11.5">UTC-11:30</option>
<option value="UTC-11">UTC-11</option>
<option value="UTC-10.5">UTC-10:30</option>
<option value="UTC-10">UTC-10</option>
<option value="UTC-9.5">UTC-9:30</option>
<option value="UTC-9">UTC-9</option>
<option value="UTC-8.5">UTC-8:30</option>
<option value="UTC-8">UTC-8</option>
<option value="UTC-7.5">UTC-7:30</option>
<option value="UTC-7">UTC-7</option>
<option value="UTC-6.5">UTC-6:30</option>
<option value="UTC-6">UTC-6</option>
<option value="UTC-5.5">UTC-5:30</option>
<option value="UTC-5">UTC-5</option>
<option value="UTC-4.5">UTC-4:30</option>
<option value="UTC-4">UTC-4</option>
<option value="UTC-3.5">UTC-3:30</option>
<option value="UTC-3">UTC-3</option>
<option value="UTC-2.5">UTC-2:30</option>
<option value="UTC-2">UTC-2</option>
<option value="UTC-1.5">UTC-1:30</option>
<option value="UTC-1">UTC-1</option>
<option value="UTC-0.5">UTC-0:30</option>
<option value="UTC+0">UTC+0</option>
<option value="UTC+0.5">UTC+0:30</option>
<option value="UTC+1">UTC+1</option>
<option value="UTC+1.5">UTC+1:30</option>
<option value="UTC+2">UTC+2</option>
<option value="UTC+2.5">UTC+2:30</option>
<option value="UTC+3">UTC+3</option>
<option value="UTC+3.5">UTC+3:30</option>
<option value="UTC+4">UTC+4</option>
<option value="UTC+4.5">UTC+4:30</option>
<option value="UTC+5">UTC+5</option>
<option value="UTC+5.5">UTC+5:30</option>
<option value="UTC+5.75">UTC+5:45</option>
<option value="UTC+6">UTC+6</option>
<option value="UTC+6.5">UTC+6:30</option>
<option value="UTC+7">UTC+7</option>
<option value="UTC+7.5">UTC+7:30</option>
<option value="UTC+8">UTC+8</option>
<option value="UTC+8.5">UTC+8:30</option>
<option value="UTC+8.75">UTC+8:45</option>
<option value="UTC+9">UTC+9</option>
<option value="UTC+9.5">UTC+9:30</option>
<option value="UTC+10">UTC+10</option>
<option value="UTC+10.5">UTC+10:30</option>
<option value="UTC+11">UTC+11</option>
<option value="UTC+11.5">UTC+11:30</option>
<option value="UTC+12">UTC+12</option>
<option value="UTC+12.75">UTC+12:45</option>
<option value="UTC+13">UTC+13</option>
<option value="UTC+13.75">UTC+13:45</option>
<option value="UTC+14">UTC+14</option>
</optgroup></select>
<?php
	printf('<a href="%s" >%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),__('More Event Options','rsvpmaker')); 
}//end content not displayed on initial setup page	
?>

</p>
<?php
}

function ajax_rsvpmaker_date_handler() {
	$post_id = (int) $_REQUEST['post_id'];
	if(!$post_id)
		wp_die();
	if(isset($_REQUEST['date']))
	{
	$t = rsvpmaker_strtotime($_REQUEST['date']);
	$date = rsvpmaker_date("Y-m-d H:i:s",$t);
	$current_date = get_rsvp_date($post_id);
	update_post_meta($post_id,'_rsvp_dates',$date,$current_date);
	delete_transient('rsvpmakerdates');
	}
    wp_die();
}

function rsvp_customize_form_url($post_id) {
	global $rsvp_options;
	$current_form = get_post_meta($post_id,'_rsvp_form',true);
	if(empty($current_form))
		$current_form = $rsvp_options['rsvp_form'];
	if(!is_numeric($current_form))
		return;
	return admin_url('?post_id='.$post_id.'&customize_form='.$current_form); // customize url 
}

function rsvp_form_url($post_id) {
	global $rsvp_options;
	$current_form = get_post_meta($post_id,'_rsvp_form',true);
	if(empty($current_form))
		$current_form = $rsvp_options['rsvp_form'];
	if(!is_numeric($current_form))
		return;
	$form_post = get_post($current_form);
	if(empty($form_post->post_parent) ||($form_post->post_parent != $post_id))
		return admin_url('?post_id='.$post_id.'&customize_form='.$current_form); // customize url 
	else
		return admin_url('post.php?action=edit&post=').$current_form; // edit url
}

function rsvp_confirm_url($post_id) {
	global $rsvp_options;
	$current = get_post_meta($post_id,'_rsvp_confirm',true);
	if(empty($current))
		$current = $rsvp_options['rsvp_confirm'];
	if(!is_numeric($current))
		return;
	$confirm = get_post($current);
	if(empty($confirm->post_parent) || ($confirm->post_parent != $post_id))
		return admin_url('?post_id='.$post_id.'&customize_rsvpconfirm='.$current); // customize url 
	else
		return admin_url('post.php?action=edit&post=').$current; // edit url
}

function rsvpmaker_templates_dropdown ($select = 'template') {
	$templates = rsvpmaker_get_templates();
	$o = '';
	if(is_array($templates))
	foreach($templates as $template)
	{
		$o .= sprintf('<option value="%d">%s</option>',$template->ID,$template->post_title);
	}
return sprintf('<select name="%s">%s</select>',$select,$o);
}

function toolbar_rsvpmaker( $wp_admin_bar ) {
global $post;
if(isset($post->post_type) && 'rsvpemail' == $post->post_type) {
	$args = array(
		'parent'    => 'new-post',
		'id' => 'email_to_post',
		'title' => __('Copy to Post','rsvpmaker'),
		'href'  => admin_url('edit.php?email_to_post='.intval($post->ID)),
		'meta'  => array( 'class' => 'rsvpmaker')
	);
	$wp_admin_bar->add_node( $args );	
}

$args = array(
	'parent'    => 'new-rsvpmaker',
	'id' => 'rsvpmaker_setup_template',
	'title' => 'New Event Template',
	'href'  => admin_url('post-new.php?post_type=rsvpmaker_template'),
	'meta'  => array( 'class' => 'rsvpmaker_setup')
);
$wp_admin_bar->add_node( $args );
$templates = rsvpmaker_get_templates();
foreach($templates as $template) {
	$args = array(
		'parent'    => 'new-rsvpmaker',
		'id' => 'template'.$template->ID,
		'title' => 'Create/Update: '.$template->post_title,
		'href'  => admin_url('post-new.php?post_type=rsvpmaker&t='.$template->ID),
		'meta'  => array( 'class' => 'new_from_template')
	);
	$wp_admin_bar->add_node( $args );
}

if($post && (('rsvpmaker' == $post->post_type) || ('rsvpmaker_template' == $post->post_type)))
{
	$args = array(
		'parent'    => 'new-rsvpmaker_template',
		'id' => 'copy_to_rsvp_template',
		'title' => 'Copy to New Template',
		'href'  => admin_url('?copy_to_rsvp_template='.intval($post->ID)),
		'meta'  => array( 'class' => 'rsvpmaker')
	);
	$wp_admin_bar->add_node( $args );	
}
if(!empty($post->post_type) && ($post->post_type != 'rsvpemail'))
{
	$typelabel = ('rsvpmaker' == $post->post_type) ? 'Event' : ucfirst($post->post_type);
	$args = array(
		'parent'    => 'new-rsvpemail',
		'id' => 'post_to_email',
		'title' => 'Copy '.$typelabel.' to Email',
		'href'  => admin_url('edit.php?post_type=rsvpemail&post_to_email='.intval($post->ID)),
		'meta'  => array( 'class' => 'rsvpmaker')
	);
	$wp_admin_bar->add_node( $args );
	if($post->post_type != 'rsvpmaker')
	{
		$args = array(
			'parent'    => 'new-rsvpemail',
			'id' => 'excerpt_to_email',
			'title' => __('Excerpt to Email','rsvpmaker'),
			'href'  => admin_url('edit.php?post_type=rsvpemail&excerpt=1&post_to_email='.intval($post->ID)),
			'meta'  => array( 'class' => 'rsvpmaker')
		);
		$wp_admin_bar->add_node( $args );	
	}
}

$args = array(
	'parent'    => 'new-rsvpemail',
	'id' => 'rsvp_newsletter_builder',
	'title' => __('Newsletter Builder','rsvpmaker'),
	'href'  => admin_url('edit.php?post_type=rsvpemail&page=email_get_content'),
	'meta'  => array( 'class' => 'rsvpmaker_newsletter')
);	
$wp_admin_bar->add_node( $args );

$noview = true;
$argarg = get_related_documents ();
if(empty($argarg))
return;
	foreach($argarg as $args) {
		$wp_admin_bar->add_node($args);
		if($args['id'] == 'view-event')
		$wp_admin_bar->remove_node( 'view' );
	}
}

function rsvpmaker_quick_post() {
	global $current_user;
	if( ! wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		return;
	$_POST = stripslashes_deep($_POST);
	if(!empty($_POST['type']))
		$types[] = (int) $_POST['type'];
	if(!empty($_POST['type2']))
		$types[] = (int) $_POST['type2'];
	if(!empty($_POST['newtype']))
	{
		$result = wp_insert_term(sanitize_text_field($_POST['newtype']),'rsvpmaker-type');
		if(is_array($result) && !empty($result["term_id"]))
			$types[] = $result["term_id"];
	}

	foreach($_POST["quicktitle"] as $index => $title) {
		if(!empty($title)) {
		$datetime = trim(sanitize_text_field($_POST["quick_rsvp_date"][$index].' '.$_POST["quick_rsvp_time"][$index]));
		if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',$datetime)) {
			echo 'invalid time'.$datetime;
			continue;
		}
		$title = sanitize_text_field($title);
		$content = (empty($_POST["quickcontent"][$index])) ? '' : wp_kses_post( rsvpautog($_POST["quickcontent"][$index]));
		$post_id = wp_insert_post(array('post_type' => 'rsvpmaker', 'post_title' => $title, 'post_content' => $content, 'post_author' => $current_user->ID, 'post_status' => sanitize_text_field($_POST['status'])));
		$end_type = sanitize_text_field($_POST["quick_end_time_type"][$index]);
		$end_time = sanitize_text_field($_POST["quick_rsvp_time_end"][$index]);
		if(empty($end_time)) {
			$t = strtotime($datetime." +1 hour");
			$end_time = date('H:i',$t);
		}
		rsvpmaker_add_event_row($post_id,$datetime,$end_time,$end_type);
		if(!empty($types)) {
			wp_set_object_terms( $post_id, $types, 'rsvpmaker-type' );
		}
		if(!empty($_POST['rsvp_on']))
			add_post_meta($post_id,'_rsvp_on',1);
		if(!empty($_POST['calendar_icons']))
			add_post_meta($post_id,'_calendar_icons',1);
		if(!empty($_POST['add_timezone']))
			add_post_meta($post_id,'_add_timezone',1);
		if(!empty($_POST['convert_timezone']))
			add_post_meta($post_id,'_convert_timezone',1);

		if(empty($confirmation)) {
			$confirmation = sprintf('<h3>%s</h3>',__('Event Posts Created','rsvpmaker'));
			echo wp_kses_post($confirmation);
		}
		printf('<p><a href="%s">View</a> <a href="%s">Edit</a> %s %s</p>',get_permalink($post_id),admin_url("post.php?post=$post_id&action=edit"),$title,$datetime);
		}		
	}
}

function print_quick_date_entry($i,$date_text_default,$datedefault) {
	echo '<div class="quickentry">';
	echo '<div id="event_date'.$i.'" >
	<p><label>Date</label>
	<input name="quick_rsvp_date[]" type="date" class="quick-rsvp-date" id="quick-rsvp-date-'.$i.'" count="'.$i.'"> <span id="date-weekday-'.$i.'"></span>
	</p>
	<p><label>Time</label> 
	<input name="quick_rsvp_time[]" type="time" class="quick-rsvp-time" id="quick-rsvp-time-'.$i.'" count="'.$i.'" value="12:00:00"> <span id="date-weekday-'.$i.'"></span>
	</p>
	<p><label>End Time</label> <input type="hidden" id="end_time_type-'.$i.'" name="quick_end_time_type[]" class="end_time_type" value="set" >
	<input name="quick_rsvp_time_end[]" type="time" class="quick-rsvp-time-end" id="quick-rsvp-time-end-'.$i.'" size="5" count="'.$i.'" value="13:00:00">
	</p></div>';
	printf('<div class="quickfield"><label>%s</label><input type="text" id="quicktitle-'.$i.'" class="quicktitle" name="quicktitle[]"></div>',__('Title','rsvpmaker'));
	printf('<div class="quickfield"><label>%s</label><br /><textarea name="quickcontent[]" rows="2" cols="100"></textarea></div>',__('Starter Text','rsvpmaker'));
	echo '<div id="quickmessage-'.$i.'"></div>';
	echo '</div>';
}

function rsvpmaker_quick_ui() {
	global $rsvp_options;
	$t = strtotime('tomorrow noon');
	$datedefault = rsvpmaker_date('Y-m-d H:i:s',$t);
	$date_text_default = rsvpmaker_date('F j, Y '.$rsvp_options['time_format'],$t);
	$limit = (int) $_GET['quick'];
	if(!$limit)
		$limit = 5;
	printf('<h3>%s</h3>',__('Quickly Setup Multiple Event Posts','rsvpmaker'));
	printf('<p>%s</p>',__('Enter start time and title for each event to be created. Optionally, you can include post body text. Can include multiple paragraphs, separated by a blank line. Events can be published immediately or saved as drafts for further editing.','rsvpmaker'));
	printf('<p>%s</p>',__('You must enter at least a title for the event to be recorded.','rsvpmaker'));
	printf('<p>%s</p>',__('Setting the date changes the default date for all the events that follow, which is useful for example for setting up a conference program or other series of events on the same day or subsequent days.','rsvpmaker'));
	printf('<form method="post" action="%s">',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_setup'));
	for($i = 0; $i < 50; $i++) {
		if($i >= $limit)
			echo '<div class="quick-extra-blank" id="quick-extra-blank-'.$i.'">';
		echo '<div>Entry '.($i+1).'</div>';
		print_quick_date_entry($i,$date_text_default,$datedefault);
		if($i >= $limit)
			echo '</div>';
	}
	echo '<div class="quick-extra-blank" id="quick-extra-blank-'.$i.'"><em>Maximum Entries Reached</em></div>';
	echo '<div><button id="add-quick-blank" start="'.$limit.'">+ Show more entries</button></div>';
	//echo '<div id="quick_entry_more"></div><p><button id="quick_entry_add">Add More</button></p><div id="quick_entry_hidden">';
	//print_quick_date_entry('x',$date_text_default,$datedefault);
	//echo '</div>';
	echo '<div>';
	wp_dropdown_categories( array(
		'taxonomy'      => 'rsvpmaker-type',
		'hide_empty'    => 0,
		'orderby'       => 'name',
		'order'         => 'ASC',
		'name'          => 'type',
		'show_option_none' => __('Event Type (optional)','rsvpmaker'),
		'option_none_value' => 0
	) );
	wp_dropdown_categories( array(
		'taxonomy'      => 'rsvpmaker-type',
		'hide_empty'    => 0,
		'orderby'       => 'name',
		'order'         => 'ASC',
		'name'          => 'type2',
		'show_option_none' => __('Event Type (optional)','rsvpmaker'),
		'option_none_value' => 0
	) );
	echo '<label>New Event Type </label> <input type="text" name="newtype"> <div>';
	?>
	<p>
	<?php esc_html_e('Collect RSVPs','rsvpmaker');?>
	  <input type="radio" name="rsvp_on" id="setrsvpon" value="1" <?php if( !empty($rsvp_options['rsvp_on']) ) echo 'checked="checked" ';?> />
	<?php esc_html_e('YES','rsvpmaker');?> <input type="radio" name="rsvp_on" id="setrsvpon" value="0" <?php if( empty($rsvp_options['rsvp_on']) ) echo 'checked="checked" ';?> />
	<?php esc_html_e('NO','rsvpmaker');?> </p>
	<p><input type="checkbox" name="calendar_icons" value="1" <?php if($rsvp_options["calendar_icons"]) echo ' checked="checked" ';?> /> <?php esc_html_e('Show Add to Google / Download to Outlook (iCal) icons','rsvpmaker'); ?> 
	<br />
	<p id="timezone_options">
	<?php
	if(!strpos($rsvp_options["time_format"],'T') )
	{
	?>
	<input type="checkbox" name="add_timezone" value="1" <?php if($rsvp_options["add_timezone"]) echo ' checked="checked" '; ?> /><?php esc_html_e('Display timezone code as part of date/time','rsvpmaker'); echo ' '; ?>
	<?php
	}
	?>
	<input type="checkbox" name="convert_timezone" value="1" <?php if($rsvp_options["convert_timezone"]) echo ' checked="checked" '; ?> /><?php esc_html_e('Show timezone conversion button next to calendar icons','rsvpmaker'); ?>
	</p>
	<?php
	rsvpmaker_nonce();
	echo '<div><input type="radio" name="status" value="draft" checked="checked"> Draft <input type="radio" name="status" value="publish"> Publish </div><p><button>Submit</button></p></form>';
}

function rsvpmaker_setup () {

global $rsvp_options, $current_user, $wpdb;

?>
<style>
select {
	max-width: 228px;
}
.quickentry label {
	display: inline-block;
	width: 100px;
	font-weight: bold;
}
</style>

<div class="wrap">
<?php  

if(isset($_POST["quicktitle"]))
	rsvpmaker_quick_post();
elseif(isset($_GET["quick"])) {
	rsvpmaker_admin_heading(__('Quick Event Setup','rsvpmaker'),__FUNCTION__,'quick');
	rsvpmaker_quick_ui();
	echo '</div>';
	return;
}
elseif(isset($_GET['new_template']))
	rsvpmaker_admin_heading(__('Event Template Setup','rsvpmaker'),__FUNCTION__,'new_template');
else
	rsvpmaker_admin_heading(__('Event Setup','rsvpmaker'),__FUNCTION__);

$title = '';
$template = 0;
if(isset($_GET['t']))
{
	$post = get_post((int) $_GET['t']);
	$title = htmlentities($post->post_title);
	$template = $post->ID;
	$future = get_events_by_template($template);
	$shortlist = $morelist = '';
	if($future) {
		foreach($future as $index => $event) {
			$temp = sprintf('<p><a href="%s">Edit event</a>: %s %s</p>',admin_url('post.php?action=edit&post='.$event->ID),esc_html($event->post_title),esc_html($event->date));
			if($index < 5)
				$shortlist .= $temp;
			else
				$morelist .= $temp;
		}
	if(!empty($morelist))
		$morelist = '<p id="morelink"><a onclick="document.getElementById'."('moreprojected').style.display='block'".';document.getElementById'."('morelink').style.display='none'".'" >Show More</a></p><div id="moreprojected" style="display: none;">'.$morelist.'</div>';
	echo '<div style="border: medium solid #999; padding: 15px;"><h2>'.__('Previously Scheduled','rsvpmaker').'</h2>'.$shortlist.$morelist.'</div>';

	echo '<p><em>'.__('To create a new event based on this template, use the form below.','rsvpmaker').'</em><p>';
	}
}

?>
<?php

if(!isset($_GET['new_template']) && !isset($_GET['t'])){
	echo '<div style="background-color: #fff; padding: 10px; border: thin dotted #555; width: 50%;">';
	printf('%s %s<br /><a href="%s">%s</a>',__('For recurring events','rsvpmaker'),__('create a' ,'rsvpmaker'),admin_url('post-new.php?post_type=rsvpmaker_template'),__('New Template','rsvpmaker'));
	printf('<form method="get" action="%s"><input type="hidden" name="post_type" value="rsvpmaker"><br />%s  %s<br >%s</form>',admin_url('post-new.php'),__('Or add an event based on'),rsvpmaker_templates_dropdown('t'),get_submit_button('Submit'));
	do_action('rsvpmaker_setup_template_prompt');
	printf('<form method="get" action="%s"><input type="hidden" name="post_type" value="rsvpmaker" /><input type="hidden" name="page" value="rsvpmaker_setup">%s <select name="quick"><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="20">20</option><option value="30">30</option><option value="40">40</option><option value="50">50</option></select> %s %s</form>',admin_url('edit.php'),__('Or quickly create up to','rsvpmaker'),__('events without a template','rsvpmaker'),get_submit_button('Show Form'));
	echo '</div>';
}				

	$myevents = get_events_by_author($current_user->ID);
	if($myevents)
	{
		printf('<h3>%s</h3>',__('Your Event Posts','rsvpmaker'));
		foreach($myevents as $event){
			$draft = ($event->post_status == 'draft') ? ' <strong>(draft)</strong>' : '';
			printf('<p><a href="%s">Edit event</a>: %s %s %s</p>',admin_url('post.php?action=edit&post='. $event->ID),esc_html($event->post_title),esc_html($event->date),esc_html($draft));
		}
	}
	$templates = rsvpmaker_get_templates();
	$tedit = $list = '';
	if(is_array($templates))
	foreach($templates as $template)
	{
	$eds = get_additional_editors($template->ID);
	if(($current_user->ID == $template->post_author) || (!empty($eds) && in_array($current_user->ID,$eds) ) )
	{
		$tedit .= sprintf('<option value="%s">%s</option>',esc_attr($template->ID),esc_html($template->post_title));
		$list .= '<p><strong>'.$template->post_title.'</strong></p>';
		$event = rsvpmaker_next_by_template($template->ID);
		if($event)
		{
		$draft = ($event->post_status == 'draft') ? ' <strong>(draft)</strong>' : '';
		$list .= sprintf('<p><a href="%s">Edit next event</a>: %s %s %s</p>',admin_url('post.php?action=edit&post='.$event->ID),esc_html($event->post_title),esc_html($event->date), esc_html($draft));			
		}
		$list .= sprintf('<p><a href="%s">Add event</a> based on template: %s</p>',admin_url('post-new.php?post_type=rsvpmaker&t='.$template->ID),esc_html($template->post_title));			
		$list .= sprintf('<p><a href="%s">Create / Update</a> multiple events based on: %s</p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$template->ID),esc_html($template->post_title));
		$list .= sprintf('<p><a href="%s">Edit template</a> %s</p>',admin_url('post.php?action=edit&post='.$template->ID),esc_html($template->post_title));		
	}

	}

	if(!empty($tedit))
	{
		printf('<h3>%s</h3><p>%s</p>',__('Your Templates','rsvpmaker'),__('Your templates and any others you have editing rights to are listed here. Templates allow you to generate multiple events based on a recurring schedule and common details for events in the series.','rsvpmaker'));
		echo $list;

		printf('<form action="%s" method="get"><p><input type="hidden" name="action" value="edit"><select name="post">%s</select>%s</p></form>',admin_url('post.php'),$tedit,get_submit_button(__('Edit Template','rsvpmaker')));

		printf('<form action="%s" method="get">
		<input type="hidden" name="post_type" value="rsvpmaker">
		<input type="hidden" name="page" value="rsvpmaker_template_list">
		<p><select name="t">%s</select>%s</p></form>',admin_url('edit.php'),$tedit,get_submit_button(__('Create/Update','rsvpmaker')));
	}
}

function rsvpmaker_setup_post ($ajax = false) {
if(!empty($_POST["rsvpmaker_new_post"])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		$t = 0;
		$slug = $title = sanitize_text_field(stripslashes($_POST["rsvpmaker_new_post"]));
		$post_type = (isset($_POST['sked'])) ? 'rsvpmaker_template' : 'rsvpmaker';
		$content = array('post_title' => $title,'post_name' => $slug, 'post_type' => $post_type,'post_status' => 'draft','post_content' => '');
		if(!empty($_POST['template']))
		{	
			$t = (int) $_POST['template'];
			$template = get_post($t);
			$content['post_content'] = $template->post_content;
		}
		$post_id = wp_insert_post($content);
		if($post_id)
		{		
		if($t) {
			add_post_meta($post_id,'_meet_recur',$t);
			rsvpmaker_copy_metadata($t, $post_id);
		}
		else {
			save_rsvp_meta($post_id, true);
		}
		if(!empty($_POST['rsvp_form']))
			update_post_meta($post_id,'_rsvp_form', (int) $_POST['rsvp_form']);
		if(!empty($_POST['timezone_string']))
			update_post_meta($post_id,'_rsvp_timezone_string', sanitize_text_field($_POST['timezone_string']));
		if('rsvpmaker' == $post_type) {
			rsvpmaker_save_calendar_data($post_id);
			$date = sanitize_text_field($_POST['newrsvpdate'].' '.$_POST['newrsvptime']);
			$end = sanitize_text_field($_POST['rsvpendtime']);
			$display_type = sanitize_text_field($_POST['end_time_type']);
			$timezone = (empty($_POST['timezone_string'])) ? '' : sanitize_text_field($_POST['timezone_string']);
			rsvpmaker_add_event_row($post_id,$date,$end,$display_type,$timezone);	
		}
		$editurl = admin_url('post.php?action=edit&post='.$post_id);
		if($ajax)
			return $editurl;
		wp_redirect($editurl);
		die();			
		}
	}

	//post-new.php?post_type=rsvpmaker
	if(strpos(sanitize_text_field($_SERVER['REQUEST_URI']),'post-new.php') && isset($_GET['post_type']))
	{
		$post_type = $_GET['post_type'];
		//if(('rsvpmaker' == $post_type) || ('rsvpmaker_template' == $post_type)) {
		if('rsvpmaker' == $post_type) {
			$url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_setup');
			if ('rsvpmaker_template' == $post_type)
				$url .= '&new_template=1';
			wp_redirect($url);
			die();
		}
	}
}

function rsvpmaker_import_cleanup () {
	global $wpdb;
	$sql = "SELECT ID, post_title from $wpdb->posts WHERE post_type='rsvpmaker' AND post_title LIKE '%rsvpid%' ";
	$results = $wpdb->get_results($sql);
	if(is_array($results))
	foreach($results as $post)
	{
	$title = preg_replace('/rsvpid.+/','',$post->post_title);
	$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_title=%s WHERE ID=%d",$title,$post->ID);
	$wpdb->query($sql);
	}
}

function rsvpmaker_export_screen () {
	global $wpdb, $rsvp_options;
?>
	<h1>Import/Export RSVPMaker Events</h1>
	<?php
	?>
	<p>This is an alternative to the standard WordPress export / import functions, allowing you to make a connection between two live websites.</p>
	<h3>Export Events</h3>
<?php
if(isset($_GET['resetrsvpcode'])) {
	$jt = strtotime('+ 24 hour');
	$export_code = rand().':'.$jt;
	update_option('rsvptm_export_lock',$export_code);
}
else {
	$export_code = get_option('rsvptm_export_lock');
	$parts = explode(':',$export_code);
	$jt = (empty($parts[1])) ? 0 : (int) $parts[1]; 	
}
if(empty($export_code) || ($jt < time())) {
	printf('<p>Coded url is expired or has not been set. To enable importing of event records from this site into another site, (<a href="%s">set code</a>)</p>',admin_url('tools.php?page=rsvpmaker_export_screen&resetrsvpcode=1'));
}
else {
	$url = rest_url('/rsvpmaker/v1/import/'.$export_code);
	printf('<p>To move your club\'s event records to another website that also uses this software, copy this web address:</p>
	<pre>%s</pre>
	<p>This link will expire at %s. (<a href="%s">reset</a>)</p>',$url,rsvpmaker_date($rsvp_options['short_date'].' '.$rsvp_options['time_format'].' T',$jt),admin_url('tools.php?page=rsvpmaker_export_screen&resetrsvpcode=1'));	
}
?>
<h3>Import Events</h3>
<p>Copy the link from the site you are <em>exporting from</em> and enter it here on the site you are <em>importing events into</em>.</p>
<form method="post" id="importform" action="<?php echo admin_url('tools.php?page=rsvpmaker_export_screen'); ?>">
<div><input type="text" name="importrsvp" id="importrsvp" value="<?php if(isset($_POST['importurl'])) echo sanitize_text_field($_POST['importrsvp']); ?>" /></div>
<input type="hidden" id="importnowurl" value="<?php echo rest_url('/rsvpmaker/v1/importnow'); ?>" />
<div><button id="import-button">Import</button></div>
</form>
<div id="import-result"></div>
<p><em>Note: This function does not automatically import images or correct links that may point to the old website.</em></p>
<?php
rsvpmaker_jquery_inline('import');									 
}

function rsvpmaker_override () {
	global $post, $current_user;
	if(isset($_POST['rsvp_tx_template'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		update_post_meta((int) $_POST['rsvp_tx_post_id'],'rsvp_tx_template',sanitize_text_field($_POST['rsvp_tx_template']));
	if(!empty($_GET['post']) && !empty($_GET['action']) && ($_GET['action'] == 'edit') )
	{
		$post_id = (int) $_GET['post'];
		if(current_user_can('edit_post',$post_id))
			return; // don't mess with it
		if(empty($post))
		$post = get_post($post_id);
		if(isset($post->post_author) && ($post->post_author != $current_user->ID)) {
			$eds = get_additional_editors($post_id);
			if(in_array($current_user->ID,$eds))
			{
			if(!in_array($post->post_author,$eds))
			{
			add_post_meta($post_id, '_additional_editors',$post->post_author);
			}
			wp_update_post(array('ID' => $post_id, 'post_author' => $current_user->ID));
			}
		}
	}
}

//add_action('admin_init','rsvpmaker_override',1);

function rsvpmaker_share() {
?>	
	<h1>Share Templates</h1>
	<p>When you create an event template, you have the option of designating other users who will have the same authoring / editing rights to that template (and all the events based on it) as you do. This is helpful for organizations where more than one person needs to be able to post and update events.</p>
	<p>Be careful to only grant this permission to trusted collaborators.</p>
<?php	
	global $current_user;
	if(isset($_REQUEST['t']))
		{
			$t = (int) $_REQUEST['t'];
			$post = get_post($t);
		}

	if(!empty($_POST['editor_email']) && !empty($t)  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		$email = sanitize_text_field($_POST['editor_email']);
		if(!rsvpmail_contains_email($email))
		{
			echo '<p>Invalid email</p>';
		}
		else {
			$user = get_user_by('email',$email);
			if($user) {
				add_post_meta($t,'_additional_editors',$user->ID);
				echo '<p>Adding '.$email.'</p>';
			}
			else {
			$user["user_login"] = $email;
			$user["user_email"] = $email;
			$user["display_name"] = 'Editor added by '.$current_user->user_email;
			$user["user_pass"] = wp_generate_password();
			$user['role'] = 'author';
			$id = wp_insert_user($user);
			if($id)
			{
			add_post_meta($t,'_additional_editors',$id);
$lostpassword = site_url('wp-login.php?action=lostpassword');
?>
<h3>Editor account created</h3>
<p>Email and username are both set to <?php echo esc_html($email); ?></p>
<p><strong>IMPORTANT</strong>: Please contact the person you have added and let them know to set their password so they will be able to assist you. Send them this link:</p>
<p><a href="<?php echo esc_attr($lostpassword); ?>"><?php echo esc_html($lostpassword); ?></a></p>
<?php
			}

			}
		}
	}

	if(isset($_POST['remove_editor']) && is_array($_POST['remove_editor']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	foreach($_POST['remove_editor'] as $ed)
		delete_post_meta($t,'_additional_editors',(int) $ed);

	if(!empty($t))
	{
	$template = get_post($t);
	$editors = '';
	$eds = get_additional_editors($template->ID);
	if(!is_array($eds))
		$eds = array();
	if(current_user_can('edit_rsvpmaker',$template->ID) || (!empty($eds) && in_array($current_user->ID,$eds) ) )
	{
		if(!in_array($template->post_author,$eds))
		{
			$eds[] = $template->post_author;
		}
		foreach($eds as $ed) {
			$user = get_userdata($ed);
			$remove = (isset($_GET['remove'])) ? sprintf('<input type="checkbox" name="remove_editor[]" value="%s" /> Remove ',$ed) : '';
			$editors .= '<div>'.$remove.$user->user_email.' '.$user->display_name.'</div>';
		}
	}

	if(!empty($editors))
	{
		$editors = '<h3>Current Editors</h3>'.$editors;
	}
		printf('<h2>Update Editors List: %s</h2><form action="%s" method="post">%s
		<p><input type="hidden" name="t" value="%s" />
		Add by Email: <input type="email" name="editor_email" />
		%s</p>%s</form>',esc_html($post->post_title), admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_share'), $editors,$t,get_submit_button(__('Save','rsvpmaker')),rsvpmaker_nonce('return'));
	}

	$templates = rsvpmaker_get_templates();
	$tedit = $list = '';
	if(is_array($templates))
	foreach($templates as $template)
	{
	$eds = get_additional_editors($template->ID);
	if(current_user_can('edit_rsvpmaker',$template->ID) || (!empty($eds) && in_array($current_user->ID,$eds) ) )
	{
		$s = (!empty($t) && ($t == $template->ID)) ? ' selected="selected" ' : '';
		$tedit .= sprintf('<option value="%s" %s>%s</option>',$template->ID,$s,esc_html($template->post_title));
	}

	}
if(empty($tedit))
	echo "<p>You don't have any templates</p>";
else
{
		printf('<form action="%s" method="get">
		<input type="hidden" name="post_type" value="rsvpmaker">
		<input type="hidden" name="page" value="rsvpmaker_share">
		<p><select name="t">%s</select>%s</p>%s</form>',admin_url('edit.php'),$tedit,get_submit_button(__('Choose Template','rsvpmaker')),rsvpmaker_nonce('return'));

		printf('<form action="%s" method="get">
		<input type="hidden" name="post_type" value="rsvpmaker">
		<input type="hidden" name="page" value="rsvpmaker_share">
		<input type="hidden" name="remove" value="1">
		<p><select name="t">%s</select>%s</p>%s</form>',admin_url('edit.php'),$tedit,get_submit_button(__('Remove Editors','rsvpmaker')),rsvpmaker_nonce('return'));
}

}

function rsvpmaker_submission ($atts) {
global $rsvp_options;
$defaultto = isset($rsvp_options['submissions_to']) ? $rsvp_options['submissions_to'] : $rsvp_options['rsvp_to'];
$to = (isset($atts['to'])) ? $atts['to'] : $defaultto;
ob_start();
?>
<style>#rsvpmaker_submission label {
	display: inline-block;
	width: 100px;
}
</style>
<script>
tinymce.init({
selector:"textarea",plugins: "link",
block_formats: 'Paragraph=p',
menu: {
format: { title: 'Format', items: 'bold italic | removeformat' },
style_formats: [
{ title: 'Inline', items: [
	{ title: 'Bold', format: 'bold' },
	{ title: 'Italic', format: 'italic' },
]},]},
toolbar: 'bold italic link',
relative_urls: false,
remove_script_host : false,
document_base_url : "'.site_url().'/",
});	
</script>
<?php
printf('<form method="post" action="%s" id="rsvpmaker_submission">',get_permalink());
rsvpmaker_nonce();
if(isset($_GET['submission_error']))
{
	echo '<h2 id="results">Error</h2>';
	printf('<p>%s</p>',esc_html($_GET['submission_error']));
}

if(isset($_GET['success']))
{
echo '<h2 id="results">Event Submitted for Review</h2>';
$post_id = (int) $_GET['success'];
$post = get_post($post_id);
$expired = rsvpmaker_strtotime('-5 minutes');
$submitted_at = rsvpmaker_strtotime($post->post_modified);
if($submitted_at < $expired)
{
	echo '<p>Preview expired</p>';
}
else {
	echo '<p>Preview</p><div style="border: thin dotted #111; padding: 10px; margin: 10px;">';
	$t = get_rsvpmaker_timestamp($post_id);
	$date = rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$t);
	printf('<h3>%s</h3><h3>%s</h3>%s',esc_html($post->post_title),esc_html($date),wp_kses_post($post->post_content));	
	echo '</div>';
}
}
	$month = (int) date('m');
	$year = (int) date('Y');
	$day = (int) date('j');
	$hour = 12;
	$endhour = 13;
	$minutes = 0;
	$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
printf('<input type="hidden" name="pagelink" value="%s">',get_permalink());
rsvphoney_ui();
?>	
<h2>Event Title: <input name="event_title"></h2>
	<div id="date"><label><?php echo __('Date','rsvpmaker');?></label> <input type="date" name="date">
	</div> 
	<div><label><?php echo __('Time','rsvpmaker');?></label> <input id="time" type="time" name="time" value="12:00"> to <input id="endtime" type="time" name="endtime" value="13:00">
<?php
if(!empty($atts['timezone']))
{
?>
<div><label>Timezone</label> 
<select id="timezone_string" name="timezone_string">
<script>
var tz = jstz.determine();
var tzstring = tz.name();
document.write('<option selected="selected" value="' + tzstring + '">' + tzstring + '</option>');
</script>
<optgroup label="U.S. (Common Choices)">
<option value="America/New_York">New York</option>
<option value="America/Chicago">Chicago</option>
<option value="America/Denver">Denver</option>
<option value="America/Los_Angeles">Los Angeles</option>
</optgroup>
<?php $choices = wp_timezone_choice('');
echo str_replace('<option selected="selected" value="">Select a city</option>','',$choices);
?>
</select> <br /><em>Choose a city in the same timezone as you.</em>
</div>
<?php
}//end display timezone
?>
<div><label>Your Name</label><input name="rsvpmaker_submission_contact" id="rsvpmaker_submission_contact" /></div>
<div><label>Email</label><input name="rsvpmaker_submission_email" id="rsvpmaker_submission_email" /></div>
<div><em>If you want your contact information to be published as part of the event listing, also include it in the description below.</em></div>
<p>Event Details<br /><textarea id="rsvpmaker_submission_description" name="rsvpmaker_submission_description" rows="5" cols="100"></textarea></p>
<input type="hidden" name="to" value="<?php echo esc_attr($to); ?>" /> 
<input type="hidden" name="rsvpmaker_submission_post" value="<?php echo get_permalink(); ?>" />
<?php rsvpmaker_recaptcha_output(); ?>
	<p><button>Submit</button></p></form>
<script>
jQuery(document).ready(function( $ ) {

var addhour = 1;

$('#time').change(function() {
	var time = $( this ).val();
	var date = new Date('2000-01-01T'+time);
	date.setTime(date.getTime()+(60*60*1000));
	$('#endtime').val(date.toLocaleTimeString('en-GB'));
});

});
</script>
	<?php
	return ob_get_clean();
}

function rsvpmaker_submission_post() {
	global $rsvp_options, $post;

	if(isset($_POST['rsvpmaker_submission_post']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		$permalink = sanitize_text_field($_POST['rsvpmaker_submission_post']);
		$author = isset($rsvp_options['submission_author']) ? $rsvp_options['submission_author'] : 1;
		$title = sanitize_text_field(stripslashes($_POST['event_title']));
		$datetime = sanitize_text_field($_POST['date']) .' '.sanitize_text_field($_POST['time']);
		$t = rsvpmaker_strtotime($datetime);
		$endtime = sanitize_text_field($_POST['endtime']);
		$contact = sanitize_text_field(stripslashes($_POST['rsvpmaker_submission_contact']));
		$email = sanitize_text_field($_POST['rsvpmaker_submission_email']);
		$description = sanitize_textarea_field(stripslashes($_POST['rsvpmaker_submission_description']));
		$description = strip_tags($description,'<strong><em><a><b><i>');
		$description = wp_kses_post(rsvpautog($description));
		if($t < time()) {
			return; //ignore dates from the past. common spam pattern. no error message to give them cues	
		}

		if(!is_admin() && !empty($rsvp_options["rsvp_recaptcha_site_key"]) && !empty($rsvp_options["rsvp_recaptcha_secret"]))
		{
		if(!rsvpmaker_recaptcha_check ($rsvp_options["rsvp_recaptcha_site_key"],$rsvp_options["rsvp_recaptcha_secret"]))	{
			$r = add_query_arg('submission_error','Failed security check',$permalink).'#results';
			wp_redirect($r);
			exit();
			}	
		}

		$to = sanitize_text_field($_POST['to']);
		if(!rsvpmail_contains_email($to))
			$to = $rsvp_options['rsvp_to'];
		if(empty($title))
			$missing[] = 'event title';
		if(empty($datetime))
			$missing[] = 'date of event';
		if(empty($description))
			$missing[] = 'description';
		if(empty($contact))
			$missing[] = 'contact name';
		if(empty($email))
			$missing[] = 'contact email';
		if(!empty($missing))
		{
			$r = add_query_arg('submission_error',sprintf('missing data %s',implode("\n",$missing)),$permalink).'#results';
			wp_redirect($r);
			exit();
		}
		if(!rsvpmail_contains_email($email))
		{
			$r = add_query_arg('submission_error','invalid email address',$permalink).'#results';
			wp_redirect($r);
			exit();
		}

		$data['post_title'] = $title;
		$data['post_content'] = $description.'<!-- wp:rsvpmaker/placeholder {"text":"Submitted by '.$contact.' '.$email.'"} /-->';
		$data['post_author'] = $author;
		$data['post_status'] = 'draft';
		$data['post_type'] = 'rsvpmaker';
		$post_id = wp_insert_post($data);

		add_rsvpmaker_date($post_id,$datetime,'set',$endtime );
		if(!empty($_POST['timezone_string']) )
		{
			add_post_meta($post_id,"_add_timezone",true);
			add_post_meta($post_id,"_convert_timezone",true);
			add_post_meta($post_id,"_rsvp_timezone_string",sanitize_text_field($_POST['timezone_string']));		
		}
		add_post_meta($post_id,'_rsvpmaker_submission',date('Y-m-d H:i:s'));

		$mail['subject'] = "Event submission: ".$title.' '.$datetime;
		$mail['html'] = $description.sprintf('<hr />
		<p>Event date: '.rsvpmaker_date($rsvp_options['long_date'].' '.$rsvp_options['time_format'],$t).'</p>
		<p><a href="%s">Edit / Approve</a></p>
		<p>Submitted by %s %s / <a href="%s">submission page</a></p>',admin_url('post.php?action=edit&post='.$post_id),esc_html($contact),esc_html($email),esc_url_raw($_POST['pagelink']));
		$mail['fromname'] = $contact;
		$mail['from'] = $email;
		if(strpos($to,','))
		{
			$emails = explode(',',$to);
			foreach($emails as $to)
			{
				$mail['to'] = trim($to);
				rsvpmailer($mail);
			}
		}
		else
		{
			$mail['to'] = $to;
			rsvpmailer($mail);
		}
		$r = add_query_arg('success',$post_id,$permalink).'#results';
		wp_redirect($r);
		exit();
	}
}

add_shortcode('rsvpmaker_submission','rsvpmaker_submission');

add_filter('manage_rsvpmaker_template_posts_columns', 'rsvpmaker_template_edit_columns');
// the above hook will add columns only for default 'post' post type, for CPT:
// manage_{POST TYPE NAME}_posts_columns
function rsvpmaker_template_edit_columns( $column_array ) {
	unset($column_array["tags"]);
	$column_array['template_schedule'] = __('Template Schedule','rsvpmaker');
	return $column_array;
}

/*
 * New columns
 */
add_filter('manage_rsvpmaker_posts_columns', 'rsvpmaker_edit_columns');
// the above hook will add columns only for default 'post' post type, for CPT:
// manage_{POST TYPE NAME}_posts_columns
function rsvpmaker_edit_columns( $column_array ) {
	unset($column_array["tags"]);
	$column_array['event_dates'] = __('Event Start','rsvpmaker');
	$column_array['rsvpmaker_end'] = __('Event End','rsvpmaker');
	$column_array['rsvpmaker_display'] = __('Display','rsvpmaker');
	return $column_array;
}

/*
 * quick_edit_custom_box allows to add HTML in Quick Edit
 * Please note: it files for EACH column, so it is similar to manage_posts_custom_column
 */

function rsvpmaker_quick_edit_fields( $column_name, $post_type ) {
global $post;
if('rsvpmaker' != $post->post_type)
	return;
$event = get_rsvpmaker_event($post->ID);
if(!$event)
	return; // only for dated events, not templates etc
	// you can check post type as well but is seems not required because your columns are added for specific CPT anyway

	switch( $column_name ) :
		case 'event_dates': {
			rsvpmaker_nonce();

			echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><div class="inline-edit-group wp-clearfix">';

			$dateparts = explode(' ',$event->date);

			echo '<label class="alignleft">
					<span class="title">Event Date and Time</span>
					<span class="input-text-wrap"><input class="quick_event_date_new" id="start_date-'.$post->ID.'" post_id="'.$post->ID.'" name="start_date" type="text" value=""></span>
					<span class="input-text-wrap"><input class="quick_event_date_new" id="start_time-'.$post->ID.'" post_id="'.$post->ID.'" name="start_time" type="time" value=""></span>
					<span id="quick_event_date_text-'.$post->ID.'"></span>
				</label>';

			break;

		}

	endswitch;
}

/*
 * Quick Edit Save
 */

function rsvpmaker_quick_edit_save( $post_id ){
	if(empty($_POST['start_date']))
		return;
	// check user capabilities
	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// check nonce
	if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		return;
	$datetime = $_POST['start_date'].' '.$_POST['start_time'];
	if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$datetime)) {
		rsvpmaker_update_start_time (intval($_POST['post_ID']), $datetime);
	}
}
