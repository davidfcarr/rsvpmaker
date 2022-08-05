<?php
use RSVPbyDrewM\MailChimp\MailChimp as MailChimpRSVP;

$rsvpmaker_message_type = '';

function rsvpemail_tag($post_id = 0, $blog_id = 0) {
	global $post;
	if(empty($post_id) && !empty($post->ID))
		$post_id = $post->ID;
	if(empty($blog_id))
		$blog_id = get_current_blog_id();
	return 'rsvpemail-'.$blog_id.'-'.$post_id;
}

function rsvpmailer($mail, $description = '') {
	if(defined('RSVPMAILOFF'))
	{
		$log = sprintf('<p style="color:red">RSVPMaker Email Disabled</p><pre>%s</pre>',var_export($mail,true));
		return;
	}
	global $post, $rsvp_options, $rsvpmaker_message_type;
	if(empty($mail['Tag']))
		$mail['Tag'] = rsvpemail_tag();
	if(strpos($mail['to'],'@example.com'))
		return; // don't try to send to fake addresses
	$mail = apply_filters('rsvpmailer_mail',$mail);
	if(empty($mail['skip_check']))
		$problem = rsvpmail_is_problem($mail['to']);
	else
		$problem = false;
	if($problem) {
		$mail['html'] = '[content omitted]';
		//rsvpmaker_debug_log($mail,'rsvpmailer blocked sending to email: '.$problem);
		//rsvpemail_error_log('rsvpmailer blocked sending to email: '.$problem,$mail);
		return $mail['to'].' not sent - '.$problem;
	}
	if(isset($mail['message_type'])) {
		$rsvpmailer_rule = apply_filters('rsvpmailer_rule','permit',$mail['to'], $mail['message_type']);
		if($rsvpmailer_rule == 'deny') {
			$mail['html'] = '[content omitted]';
			$message = $mail['to'].' blocks messages of the type: '.$rsvpmaker_message_type;
			rsvpemail_error_log($message,$mail);
			return $message;
		}	
	}

	$mail['html'] = rsvpmaker_personalize_email($mail['html'],$mail['to'],$description);
	if(isset($mail['text']))
		$mail['text'] = rsvpmaker_personalize_email($mail['text'],$mail['to'],$description);

	if(isset($mail['subject']))
		$mail['subject'] = do_shortcode($mail['subject']);

	if(isset($mail['html']))
	{
		$mail['html'] = do_shortcode($mail['html']);
	}
	
	if(empty($rsvp_options["from_always"]) && !empty($rsvp_options["smtp_useremail"]))
		$rsvp_options["from_always"] = $rsvp_options["smtp_useremail"];
	
	$site_url = get_site_url();
	$p = explode('//',$site_url);
	$via = $p[1];
	if(empty($mail['fromname']))
		$mail['fromname'] = get_bloginfo('name');

	if(!strpos($mail['fromname'],'(via'))
		$mail['fromname'] = $mail['fromname'] . ' (via '.$via.')';

	if(!empty($rsvp_options["log_email"]) && isset($post->ID))
		{
			$mail['timestamp'] = date('Y-m-d H:i');
			add_post_meta($post->ID, '_rsvpmaker_email_log',$mail);
		}
	$rsvp_options = apply_filters('rsvp_email_options',$rsvp_options);
	if(empty($mail['html']))
	$mail['html'] = wpautop($mail['text']);
	if(empty($mail['text']))
	$mail['text'] = strip_tags($mail['html']);

	$unsubscribe_email = (strpos($mail['to'],'noreply')) ? '' : $mail['to'];

	if(!strpos($mail['text'],'rsvpmail_unsubscribe'))
		$mail['text'] .= "\n\nUnsubscribe from email notifications\n".site_url('?rsvpmail_unsubscribe='.$unsubscribe_email);

	if(!strpos($mail['html'],'/html>'))
		$mail['html'] = "<html><body>\n".$mail['html']."\n</body></html>";		
	if(!strpos($mail['html'],'rsvpmail_unsubscribe'))
		$mail['html'] = str_replace('</html>',"\n<p>".sprintf('Unsubscribe from email notifications<br /><a href="%s">%s</a></p>',site_url('?rsvpmail_unsubscribe='.$mail['to']),site_url('?rsvpmail_unsubscribe='.$unsubscribe_email)).'</html>',$mail['html']);

	$postmark = get_rsvpmaker_postmark_options();
	if(rsvpmaker_postmark_is_active()) {
		return rsvpmaker_postmark_send($mail);
	}

	if(function_exists('rsvpmailer_override'))
		return rsvpmailer_override($mail);
	if(!empty($rsvp_options['from_always']) && ($rsvp_options['from_always'] != $mail['from']))
	{
		if(empty($mail['replyto']))
			$mail['replyto'] = $mail['from'];
		$mail['from'] = $rsvp_options['from_always'];
	}
		
	if(!isset($rsvp_options["smtp"]) || empty($rsvp_options["smtp"]))
		{
		$to = $mail["to"];
		$subject = $mail["subject"];
		if(!empty($mail["html"]))
			{
			$mail["html"] = str_replace('*|UNSUB|*',site_url('?rsvpmail_unsubscribe='.$unsubscribe_email),$mail["html"]);
			
				$body = $mail["html"];
				
				if(function_exists('set_html_content_type') ) // if using sendgrid plugin
					add_filter('wp_mail_content_type', 'set_html_content_type');
				else
					$headers[] = 'Content-Type: text/html; charset=UTF-8';
			}
		else {
			$body = $mail["text"];			
		}
		$headers[] = 'From: '.$mail["fromname"]. ' <'.$mail["from"].'>'."\r\n";
		if(!empty($mail["replyto"]))
			$headers[] = 'Reply-To: '.$mail["replyto"] ."\r\n";
		if(!empty($mail['attachments'])) {
			$attachments = $mail['attachments'];
			printf('<p>Attachments: %s</p>',var_export($attachments,true));
		}
		else
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
	global $wp_version;//once 5.5 is out of beta, delete 2nd test
	if(is_wp_version_compatible('5.5')) {
	require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
	require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
	$rsvpmail = new PHPMailer\PHPMailer\PHPMailer();	
	}
	else
	{
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$rsvpmail = new PHPMailer();	
	}
	
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
		$rsvpmail->SMTPSecure = $rsvp_options["smtp_prefix"];                 // sets the prefix to the server
	$rsvpmail->Port=$rsvp_options["smtp_port"];
	}
 	
	}
	
 $rsvpmail->Username= (!empty($rsvp_options["smtp_username"]) ) ? $rsvp_options["smtp_username"] : '';
 $rsvpmail->Password= (!empty($rsvp_options["smtp_password"]) ) ? $rsvp_options["smtp_password"] : '';
 $rsvpmail->CharSet = 'UTF-8';
 if(!empty($mail['toname']))
	$name = $mail['toname'];
else
	$name = rsvpmaker_email_to_name($mail["to"]);
 if(empty($name))
	 $rsvpmail->AddAddress($mail["to"]);
else
	$rsvpmail->AddAddress($mail["to"],$name);

 if(isset($mail["cc"]) )
	 $rsvpmail->AddCC($mail["cc"]);
if(isset($_GET['debug']))
{
	if(isset($mail['attachments']))
		echo '<p>Attachments set</p>';
	else
		echo '<p>Attachments NOT set</p>';
}
if(isset($mail['attachments']) && is_array($mail['attachments']))
	foreach($mail['attachments'] as $path) {
		$rsvpmail->AddAttachment($path);
		if(isset($_GET['debug']))
			printf('<p>Trying to add %s</p>',$path);
	}
$site_url = get_site_url();
$p = explode('//',$site_url);
$via = "(via ". $p[1].')';
if(is_admin() && isset($_GET['debug']))
	$rsvpmail->SMTPDebug = 4;
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

if(!empty($mail["bcc"]) && is_array($mail["bcc"]))
{
	 foreach($mail["bcc"] as $bcc)
		 $rsvpmail->AddBCC($bcc);
}

 $rsvpmail->Subject = $mail["subject"];
if($mail["html"])
	{
	$rsvpmail->isHTML(true);
	$rsvpmail->Body = $mail["html"];	
	if(isset($mail["text"]) && !strpos($mail["text"],'</')) // make sure there's no html in our text part
		$rsvpmail->AltBody = $mail["text"];
	else
	{
		$striphead = preg_replace('/<html.+\/head>/si','',$mail["html"]);
		$rsvpmail->AltBody = trim(strip_tags($striphead) );		
		$rsvpmail->WordWrap = 150;
	}
	}
	else
		{
			$rsvpmail->Body = $mail["text"];
			$rsvpmail->WordWrap = 150;
		}

	if(isset($mail["ical"]))
		$rsvpmail->Ical = $mail["ical"];
	$errors = '';
	try {
		$rsvpmail->Send();
	} catch (phpmailerException $e) {
		echo esc_html($e->errorMessage());
		$errors .= $e->errorMessage();
	} catch (Exception $e) {
		echo esc_html($e->getMessage()); //Boring error messages from anything else!
		$errors .= $e->getMessage();
	}
	$errors .= $rsvpmail->ErrorInfo;
	rsvpemail_error_log($errors,$mail);
	if(empty($errors) && isset($mail['post_id']))
		add_post_meta($mail['post_id'],'rsvpmail_sent',$mail['to']);
	return $errors;
}

function rsvpemail_error_log($errors,$mail = array()) {
	if(empty($errors))
		return;
	$mail['html'] = $mail['text'] = '';
	$errors .= ' '.date('r').' '.var_export($mail,true);
	//rsvpmaker_debug_log($errors,'rsvpmail_error_log');
	if(!empty($mail['post_id']))
		add_post_meta($mail['post_id'],'rsvpmail_error_log',$errors);
}

  // Avoid name collisions.
  if (!class_exists('RSVPMaker_Email_Options'))
      : class RSVPMaker_Email_Options
      {
          // this variable will hold url to the plugin  
          var $plugin_url;
          
          // name for our options in the DB
          var $db_option = 'chimp';
          
          // Initialize the plugin
          function __construct()
          {
              $this->plugin_url = trailingslashit( WP_PLUGIN_URL.'/'. dirname( plugin_basename(__FILE__) ) );

          }
          
          // handle plugin options
          function get_options()
          {
              $email = get_option('admin_email');
			  // default values
              $options = array(
			  'email-from' => $email
			  ,'email-name' => get_bloginfo('name')
			  ,'reply-to' => $email
			  ,'chimp-key' => ''
			  ,'chimp-list' => ''
			  ,'mailing_address' => ''
			  ,'chimp_add_new_users' => ''
			  ,'company' => ''
			  ,"add_notify" => $email
			  );
              
              // get saved options
              $saved = get_option($this->db_option);
              
              // assign them
              if (is_array($saved)) {
                  foreach ($saved as $key => $option)
                      $options[$key] = $option;
              }
              
              // update the options if necessary
              if ($saved != $options)
                  update_option($this->db_option, $options);
              
              //return the options  
              return $options;
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
			if(isset($_POST['rsvpmailer_list_confirmation_message']) && rsvpmaker_verify_nonce()) {
				update_option('rsvpmailer_list_confirmation_message',wp_kses_post(stripslashes($_POST['rsvpmailer_list_confirmation_message'])));
			}
			if(!empty($_POST['rsvpelist'])) {
				if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
					die('data error');
			}

              $options = $this->get_options();
              
              if (isset($_POST["emailsubmitted"]) || isset($_POST["mailing_address"])) {
                 		
				  //$options = array();
				  if(is_array($options))
                  foreach ($options as $name => $value)
				  	{
					if(isset($_POST[$name]))
					$options[$name] = sanitize_text_field($_POST[$name]);
				  	}
				  if(empty($_POST['chimp_add_new_users']))
					 $options['chimp_add_new_users'] = false;
                  update_option($this->db_option, $options);

				if(isset($_POST["add_cap"]))
					{
						foreach($_POST["add_cap"] as $role => $type)
							{
								$role = sanitize_text_field($role);
								if($type == 'publish')
									add_rsvpemail_caps_role($role, true);
								else
									add_rsvpemail_caps_role($role);								
							}
					}

				if(isset($_POST["remove_cap"]))
					{
						foreach($_POST["remove_cap"] as $role => $type)
							{
								remove_rsvpemail_caps_role(sanitize_text_field($role));								
							}
					}
                  
                  echo '<div class="updated fade"><p>'.__('Plugin settings saved - mailing list.','rsvpmaker').'</p></div>';
              }
              
              // URL for form submit, equals our current page
              $action_url = admin_url('options-general.php?page=rsvpmaker-admin.php');
?>
<script>
tinymce.init({
selector:"textarea.mce",plugins: "link",
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
});	
</script>
<div class="wrap" style="max-width:950px !important;">
<?php rsvpmaker_admin_heading(__('RSVPMaker Email List','rsvpmaker'),__FUNCTION__,'email_list'); ?>
<p>RSVPMaker provides its own mailing list management, which is most useful in combination with the Postmark integration.</p>	
<p>See <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_guest_list'); ?>">RSVPMailer Mailing list</a></p>
<?php rsvpmaker_add_to_list_on_rsvp_form(); ?>
<p>If you would like to set a message to be displayed whenever someone confirms their subscription to the RSVPMaker's own email list, you can set that here. The placeholder code *|EMAIL|* maybe used to display the subscriber's email address.</p>
<form method="post" action="<?php echo admin_url('options-general.php?page=rsvpmaker-admin.php'); ?>">
<p><textarea name="rsvpmailer_list_confirmation_message" class="mce"><?php echo get_option('rsvpmailer_list_confirmation_message');?></textarea></p>
<p><?php esc_html_e('Mailing Address','rsvpmaker');?>: 
<input type="text" name="mailing_address" id="mailing_address" value="<?php echo esc_attr($options["mailing_address"]); ?>" /> <em>Providing a physical mailing list address is recommended as a bulk email best practice</em>
</p>

<?php 
rsvpmaker_nonce();
submit_button(); 

$form = rsvpmail_signup_form();
printf('<p>This snippet of code can be embedded in any external site from which you accept email list signups.</p><pre>%s</pre>',htmlentities($form));

?>
</form>
<h3>Mailchimp Integration</h3>
<p><?php esc_html_e("These settings are related to integration with the MailChimp broadcast email service, as well as RSVPMaker's own functions for broadcasting email to website members or people who have registered for your events.",'rsvpmaker');?></p>			
	<div id="poststuff" style="margin-top:10px;">
	 <div id="mainblock" style="width:710px">
	<div class="dbx-content">
		 	<form name="EmailOptions" action="<?php echo esc_attr($action_url); ?>" method="post">
			 <?php rsvpmaker_nonce(); ?>
<?php
if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'email')
{
?>
<input type="hidden" id="activetab" value="email" />
<?php	
}
?>
<input type="hidden" name="tab" value="email">
					<input type="hidden" name="emailsubmitted" value="1" /> 
					
                    <p><?php esc_html_e('Email From','rsvpmaker');?>: 
                      <input type="text" name="email-from" id="email-from" value="<?php echo esc_attr($options["email-from"]); ?>" />
                    </p>
                    <p><?php esc_html_e('Email Name','rsvpmaker');?>: 
                      <input type="text" name="email-name" id="email-name" value="<?php echo esc_attr($options["email-name"]); ?>" />
                    </p>
                    <p><?php esc_html_e('MailChimp API-Key','rsvpmaker');?>: 
                      <input type="text" name="chimp-key" id="chimp-key" value="<?php echo esc_attr($options["chimp-key"]); ?>" />
                    <br /><a target="_blank" href="http://kb.mailchimp.com/integrations/api-integrations/about-api-keys"><?php esc_html_e('Get an API key for MailChimp','rsvpmaker');?></a>
                    </p>
                    <p><?php esc_html_e('Default List','rsvpmaker');?>: 
                      <select name="chimp-list" id="chimp-list" ><?php echo mailchimp_list_dropdown($options["chimp-key"], $options["chimp-list"]); ?></select>
                    </p>
                    <p><?php esc_html_e('Attempt to Subscribe New WordPress user emails','rsvpmaker');?>: 
                      <input type="checkbox" name="chimp_add_new_users" id="chimp_add_new_users" value="1" <?php echo ($options["chimp_add_new_users"]) ? ' checked="checked" ' : ''; ?> />
                    </p>
                    <p><?php esc_html_e('Email to notify on API listSubscribe success/failure (optional)','rsvpmaker');?>: 
                      <input type="text" name="add_notify" id="add_notify" value="<?php echo esc_attr($options["add_notify"]); ?>" />
                    </p>

                    <p><?php esc_html_e('Mailing Address','rsvpmaker');?>: 
                      <input type="text" name="mailing_address" id="mailing_address" value="<?php echo esc_attr($options["mailing_address"]); ?>" />
                    </p>
                    <p><?php esc_html_e('Company','rsvpmaker');?>: 
                      <input type="text" name="company" id="company" value="<?php echo esc_attr($options["company"]); ?>" />
                    </p>
<h3><?php esc_html_e('Who Can Publish and Send Email?','rsvpmaker');?></h3>
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
?>

              <div class="submit"><input type="submit" name="Submit" value="<?php esc_html_e('Update','rsvpmaker');?>" /></div>
			</form>
<p>See also: <a target="_blank" href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template'); ?>">Email Template</a></p>

		</div>
				
	 </div>

	</div>
</div>

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
<h2>Add to Email Checkbox</h2>
<p>You can include an "Add me to your email list" checkbox on your RSVP forms to enlist people when they sign up for your events. This works with both MailChimp and MailPoet.</p>
<p><image src="<?php echo plugins_url('rsvpmaker/images/add_to_email_block.png'); ?>" width="600" height="348">
<br />Adding the Mailing List Checkbox block</p>
<p><image src="<?php echo plugins_url('rsvpmaker/images/add_to_email_checkbox.png'); ?>" width="468" height="578">
<br />Checkbox as it appears on the form</p>
</div>

<?php              
          }
      }
  
  else
      : exit("Class already declared!");
  endif;
  
  // create new instance of the class
  $RSVPMaker_Email_Options = new RSVPMaker_Email_Options();
  global $RSVPMaker_Email_Options;
  if (isset($RSVPMaker_Email_Options)) {
      // register the activation function by passing the reference to our instance
      register_activation_hook(__FILE__, array(&$RSVPMaker_Email_Options, 'install'));
  }

function RSVPMaker_Chimp_Add($email, $merge_vars, $status = 'pending') {
$chimp_options = get_option('chimp');
if(empty($chimp_options) || empty($chimp_options["chimp-key"]))
	return;

$apikey = $chimp_options["chimp-key"];
$listId = $chimp_options["chimp-list"]; 

try {
	$MailChimp = new MailChimpRSVP($apikey);
} catch (Exception $e) {
		wp_mail($chimp_options["add_notify"],"RSVPMaker_Chimp_Add error for $email ",$e->getMessage() .' email'.$email.' '.var_export($merge_vars,true));
    return;
}

$MailChimp = new MailChimpRSVP($apikey);

$result = $MailChimp->post("lists/$listId/members", array(
                'email_address' => $email,
                'merge_fields'        => $merge_vars,
				'status' => $status));

	if(!empty($chimp_options["add_notify"]))
	{
		 if($MailChimp->success() ) {
			wp_mail($chimp_options["add_notify"],"RSVPMaker_Chimp_Add invite sent to $email ",var_export($merge_vars, true));
		}
		else  {
			// factor out already on list?
			wp_mail($chimp_options["add_notify"],"RSVPMaker_Chimp_Add error for $email ",$MailChimp->getLastError());
		return $MailChimp->getLastError();
		}
	}
}

function RSVPMaker_register_chimpmail($user_id) {
$chimp_options = get_option('chimp');
//attempt to add people who register with website, if specified on user form
if(empty($chimp_options["chimp_add_new_users"]))
	return;
$new_user = get_userdata($user_id);
$email = $new_user->user_email;
$merge_vars["FNAME"] = $new_user->first_name;
$merge_vars["LNAME"] = $new_user->last_name;
RSVPMaker_Chimp_Add($email, $merge_vars);
}

function rsvpmaker_next_scheduled( $post_id, $returnint = false ) {
	global $rsvp_options;
	global $rsvpnext_time;
	if($returnint && !empty($rsvpnext_time[$post_id]))
		return $rsvpnext_time[$post_id];
	//
    $crons = _get_cron_array();
    if ( empty($crons) )
        return false;
	$msg = '';
    foreach ( $crons as $timestamp => $cron ) {
		foreach($cron as $hook => $properties)
			{
			if($hook == 'rsvpmaker_cron_email')
				foreach($properties as $key => $property_array)
					{
					if(in_array($post_id,$property_array["args"]))
						{
						$schedule = (empty($property_array["schedule"])) ? '' : $property_array["schedule"];
						$rsvpnext_time[$post_id] = $timestamp;
						if($returnint)
							return $timestamp;
						return utf8_encode(rsvpmaker_date($rsvp_options["long_date"].' '.$rsvp_options["time_format"],$timestamp)).' '.$schedule;
						}
					}
			}
    }
    return false;
}

function rsvpmaker_scheduled_email_list(  ) {
global $wpdb;
global $rsvp_options;
global $post;
?>
<div class="wrap">
<?php rsvpmaker_admin_heading(__('Scheduled Email List','rsvpmaker'),__FUNCTION__); ?>
<p><?php esc_html_e('Use this screen to create or edit a schedule for sending your email at a specific date and time or on a recurring schedule.','rsvpmaker'); ?></p>
<?php


	if(isset($_REQUEST['post_id']))
	{
		$post_id = (int) $_REQUEST['post_id'];
		$permalink = get_permalink($post_id);
		printf('<iframe width="%s" height="1000" src="%s"></iframe>','100%',add_query_arg('scheduling',1,$permalink));

		/*
		printf('<h3>Email Post: %s</h3><p><a href="post.php?action=edit&post=%s">Edit Post</a> | <a href="%s">View Post</a></p>',esc_html($post->post_title),esc_attr($post->ID),get_permalink($post->ID));
		printf('<form action="%s" method="post">',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id=').$post->ID);
		echo '<input type="hidden" name="post_id" value="'.$post->ID.'" />';
		RSVPMaker_draw_blastoptions();
		rsvpmaker_nonce();
		echo '<button>Save</button></form>';
		*/
	}
	elseif(isset($_GET['editor_note'])) {
		rsvpmail_editors_note_ui(intval($_GET['editor_note']));
	}
	else {
?>
<form method="get" action="edit.php"><input type="hidden" name="post_type" value="rsvpemail" /><input type="hidden" name="page" value="rsvpmaker_scheduled_email_list" /><h3><?php esc_html_e('Choose a RSVP Mailer Post','rsvpmaker'); ?></h3>
	<select name="post_id"><?php
$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type='rsvpemail' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC ";
$results = $wpdb->get_results($sql);
if(is_array($results))
foreach($results as $row)
{
	printf('<option value="%d">%s</option>',esc_attr($row->ID),esc_html($row->post_title));
}
		  ?></select>
<button>Get</button>
</form>
<?php
	}

	if(isset($_GET['cancel'])) {
		$args[] = intval($_GET['cancel']);
		$args[] = intval($_GET['user_id']);
		$timestamp = intval($_GET['timestamp']);
		wp_unschedule_event($timestamp,'rsvpmailer_delayed_send',$args);
	}
	
    $crons = _get_cron_array();
    if ( empty($crons) )
        esc_html_e('None','rsvpmaker');
	else
	{
	printf('<h3>%s</h3>',__('Scheduled','rsvpmaker'));
	printf('<table  class="wp-list-table widefat fixed posts" cellspacing="0"><thead><tr><th>%s</th><th>%s</th></tr></thead><tbody>',__('Title','rsvpmaker'),__('Schedule','rsvpmaker'));
    foreach ( $crons as $timestamp => $cron ) {
		foreach($cron as $hook => $properties)
			{
			if($hook == 'rsvpmaker_cron_email') {
				foreach($properties as $key => $property_array)
					{
					////print_r($property_array);
					$post_id = array_shift($property_array["args"]);
					$post = get_post($post_id);
					if(!empty($post))
						{
						printf('<tr><td>%s <br /><a href="%s">%s</a> | <a href="%s">%s</a> | <a href="%s">%s</a></td><td>',$post->post_title,admin_url('post.php?post='.$post_id.'&action=edit'),__('Edit Post','rsvpmaker'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id='.$post_id),__('Schedule Options','rsvpmaker'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&editor_note='.$post_id),__('Editor\'s Note','rsvpmaker'));
						$schedule = (empty($property_array["schedule"])) ? '' : $property_array["schedule"];
						
						echo utf8_encode(rsvpmaker_date($rsvp_options["long_date"].' '.$rsvp_options["time_format"],$timestamp)).' '.$schedule;
						echo '</td></tr>';
						}
					}
				}
				if($hook == 'rsvpmailer_delayed_send') {
					//wp_schedule_single_event( $t, 'rsvpmailer_delayed_send', array($post->ID, $current_user->ID));
					foreach($properties as $key => $property_array)
						{
						////print_r($property_array);
						$post_id = $property_array["args"][0];
						$user_id = $property_array["args"][1];
						$post = get_post($post_id);
						if(!empty($post))
							{
							printf('<tr><td>%s (%s)<br /><a href="%s">%s</a> | <a href="%s">%s</a></td><td>',$post->post_title,__('Delayed Send','rsvpmaker'),admin_url('post.php?post='.$post_id.'&action=edit'),__('Edit Post','rsvpmaker'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&timestamp='.$timestamp.'&cancel='.$post_id.'&user_id='.$user_id),__('Cancel','rsvpmaker'));
							$schedule = (empty($property_array["schedule"])) ? '' : $property_array["schedule"];
							
							echo utf8_encode(rsvpmaker_date($rsvp_options["long_date"].' '.$rsvp_options["time_format"],$timestamp)).' '.$schedule;
							echo '</td></tr>';
							}
						}
					}
			}
    } // end cron loop
	echo '</table>';
	}
?>
<h3><?php esc_html_e('Shortcodes for Scheduled Email Newsletters','rsvpmaker');?></h3>
<p><?php esc_html_e('Shortcodes you can include with scheduled email include [rsvpmaker_upcoming] (which should be used without the calendar grid) and these others, intended specifically for newsletter style messages. The attributes are optional and shown with the default values.','rsvpmaker');?></p>
<p>[rsvpmaker_recent_blog_posts weeks=&quot;1&quot;] (<?php esc_html_e('shows blog posts published within the timeframe, default 1 week','rsvpmaker');?>)</p>
<p>[rsvpmaker_looking_ahead days=&quot;30&quot; limit=&quot;10&quot;] (<?php esc_html_e('include after rsvpmaker_upcoming for a linked listing of just the headlines and dates of events farther out on the schedule','rsvpmaker');?>)</p>
<?php
}

function rsvpmaker_cron_schedule_options() {
global $post, $wpdb, $rsvp_options;
$event_timestamp = (int) get_post_meta($post->ID,'event_timestamp',true);
$args = array($post->ID);
$cron = get_post_meta($post->ID,'rsvpmaker_cron_email',true);
$notekey = get_rsvp_notekey();

$ts = rsvpmaker_next_scheduled($post->ID);
if(empty($ts))
	{
	echo '<p>Next broadcast: NOT SET</p>';
	$timestamp = rsvpmaker_strtotime('+1 hour');
	$day = (empty($cron["cron_active"])) ? (int) date('w',$timestamp) : $cron["cronday"];
	$hour = (empty($cron["cron_active"])) ? (int) date('G',$timestamp)  : $cron["cronhour"];
	}
else
	{
	printf('<p>Next broadcast: %s</p>',$ts);
	$ts = rsvpmaker_next_scheduled($post->ID, true);//get the integer value
	$day = date('w',$ts);
	$hour = date('G',$ts);
	}
?>
<p><input type="radio" name="cron_active" value="1" <?php if(!empty($cron["cron_active"]) && ($cron['cron_active']) == '1') echo 'checked="checked"' ?> /> <?php echo __('Create schedule relative to this day/time','rsvpmaker');?>: <select name="cronday">
<?php
$days = array(__('Sunday','rsvpmaker'),__('Monday','rsvpmaker'),__('Tuesday','rsvpmaker'),__('Wednesday','rsvpmaker'),__('Thursday','rsvpmaker'),__('Friday','rsvpmaker'),__('Saturday','rsvpmaker'));
foreach($days as $index => $daytext)
	{
	$selected = ($index == $day) ? ' selected="selected" ' : '';
	printf('<option  value="%s" %s>%s</option>',$index,$selected,$daytext);
	}
?>
</select>
 <select name="cronhour"> 
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

	printf('<option  value="%s" %s>%s / %s</option>',$padded,$selected,$twelvehour,$padded);
	}
?>
</select>
<?php esc_html_e('Recurrence','rsvpmaker');?> <select name="cronrecur"><option value=""><?php echo __('None','rsvpmaker');?></option>
<?php
$sked_meta = (empty($cron["cronrecur"])) ? ''  : $cron["cronrecur"];
$schedules = array('weekly','daily');
foreach ($schedules as $sked)
	{
	$selected = ($sked == $sked_meta) ? ' selected="selected" ' : '';
	printf('<option  value="%s" %s>%s</option>',esc_attr($sked),$selected,esc_html($sked));
	}
?>
</select>
</p>

<?php
if($event_timestamp)
{
	$evopt = '';
	$i = 1;
	$limit = 24 * 5;
	$days = 0;
	$dtext = '';
	while($i <= $limit)
	{
		if($i < 13)
			$i++;
		elseif($i == 13)
			{
				$i = 24;
				$days = 1;
				$dtext = ' (1 day before)';
			}
		else
			{
				$i += 24;
				$days++;
				$dtext = ' ('.$days .' days before)';
			}
		$deduct = $i * 60 * 60;
		$reminder = $event_timestamp - $deduct;
		$s = ($reminder == $ts) ? ' selected="selected" ' : '';
			
		$evopt .= '<option value="'.esc_attr($reminder).'"'.$s.'>'.rsvpmaker_date($rsvp_options['short_date'].' '.$rsvp_options['time_format'],$reminder).$dtext.'</option>';
	}
	$checked = (!empty($cron["cron_active"]) && ($cron["cron_active"]) == "relative") ? 'checked="checked"' : '';
printf('<p><input type="radio" name="cron_active" value="relative" '.$checked.' /> Set reminder relative to event %s<br /><select name="cron_relative">%s</select></p>',rsvpmaker_date($rsvp_options['short_date'].' '.$rsvp_options['time_format'],$event_timestamp),$evopt);
}
$checked = (!empty($cron["cron_active"]) && ($cron["cron_active"]) == "rsvpmaker_strtotime") ? 'checked="checked"' : '';
$timestring = ($ts) ? rsvpmaker_date('Y-m-d H:i:s',$ts) : rsvpmaker_date('Y-m-d H:00:00',rsvpmaker_strtotime('+ 1 hour'));
?>
<p><input type="radio" name="cron_active" value="rsvpmaker_strtotime" <?php echo $checked; ?> /> Custom date time string <input type="text" name="cron_rsvpmaker_strtotime" value="<?php echo esc_attr($timestring); ?>" /></p>
<p><input type="radio" name="cron_active" value="clear" /> Clear schedule</p>

<p>
<?php
$preview = (!empty($cron["cron_preview"]) ) ? (int) $cron["cron_preview"] : 0;
$preview_options = '';
for($i = 0; $i < 25; $i++)
	{
	$s = ($i == $preview) ? ' selected="selected"' : '';
	$label = ($i) ? $i.' hours before' : 'none';
	$preview_options .= sprintf('<option value="%d" %s>%s</option>',$i,$s,$label);
	}
?>
<?php esc_html_e('Preview','rsvpmaker');?> <select name="cron_preview"><?php echo $preview_options; ?></select>
</p>

<p>
<?php
$condition = (!empty($cron["cron_condition"]) ) ? $cron["cron_condition"] : 'none';
$blog_options = $condition_options = '';
$conditions = array('none' => __('none','rsvpmaker'),'events' => __('Future events','rsvpmaker'),'posts' => __('Recent posts','rsvpmaker'),'and' => __('Both events and posts','rsvpmaker'),'or' => __('Either events or posts','rsvpmaker'));
foreach($conditions as $slug => $text)
	{
	$s = ($slug == $condition) ? ' selected="selected"' : '';
	$condition_options .= sprintf('<option value="%s" %s>%s</option>',$slug,$s,$text);
	}
?>
<?php esc_html_e('Test for','rsvpmaker');?>: <select name="cron_condition"><?php echo $condition_options; ?></select>
<br /><em><?php esc_html_e('Broadcast will not be sent if it does not meet this test.','rsvpmaker');?></em>
</p>
<?php
}

function RSVPMaker_draw_blastoptions() {
global $post;
$chimp_options = get_option('chimp');
if(empty($chimp_options["email-from"]))
	{
	printf('<p>%s: <a href="%s">%s</a></p>',__('You must fill in the RSVP Mailer settings before first use','rsvpmaker'),admin_url('options-general.php?page=rsvpmaker-email.php'),__('Settings','rsvpmaker'));
	return;
	}
if(empty($_GET["post_id"]))
	return;
//$post = get_post($_GET["post_id"]);
$scheduled_email = get_post_meta($post->ID,'scheduled_email',true);
if(empty($scheduled_email))
	$scheduled_email = array();
foreach($chimp_options as $label => $value)
{
	if(empty($scheduled_email[$label]))
		$scheduled_email[$label] = $value;
}
	
if(empty($scheduled_email['preview_to']))
	$scheduled_email['preview_to'] = $scheduled_email['email-from'];
if(empty($scheduled_email['template']))
	$scheduled_email['template'] = '';
	
$permalink = get_permalink($post->ID);
$template = get_rsvpmaker_email_template();
?>
<table>
<tr><td><?php esc_html_e('From Name','rsvpmaker');?>:</td><td><input type="text"  size="80" name="scheduled_email[email-name]" value="<?php echo esc_attr($scheduled_email["email-name"]); ?>" /></td></tr>
<tr><td><?php esc_html_e('From Email','rsvpmaker');?>:</td><td><input type="text" size="80"  name="scheduled_email[email-from]" value="<?php echo esc_attr($scheduled_email["email-from"]); ?>" /></td></tr>
<tr><td><?php esc_html_e('Preview To','rsvpmaker');?>:</td><td><input type="text" size="80" name="scheduled_email[preview_to]" value="<?php echo esc_attr($scheduled_email['preview_to']); ?>" />
</td></tr>
</table>

<p><?php esc_html_e('MailChimp List','rsvpmaker');?> <select name="scheduled_email[list]">
<?php
$chosen = (isset($scheduled_email["list"])) ? $scheduled_email["list"] : $chimp_options["chimp-list"];
echo mailchimp_list_dropdown($chimp_options["chimp-key"], $chosen);
?>
</select></p>

<?php
if(current_user_can('publish_rsvpemails'))
	rsvpmaker_cron_schedule_options();
}

function RSVPMaker_email_notice () {
global $post;
?>
	<div><h3>Email Editor</h3><p>Use the WordPress editor to compose the body of your message, with the post title as your subject line. <a href="<?php echo get_permalink($post->ID); ?>">View Post</a> will display your content in an email template, with a user interface for addressing options.</p>
<p>See also <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id=').esc_attr($post->ID); ?>">Scheduled email options</a></p>
</div><?php
}

function my_rsvpemails_menu() {
if(!function_exists('do_blocks'))
add_meta_box( 'BlastBox', 'RSVPMaker Email Options', 'RSVPMaker_email_notice', 'rsvpemail', 'normal', 'high' );
}

add_action('admin_init','save_rsvpemail_data');

//legacy
function save_rsvpemail_data() {

if(empty($_POST) || empty($_REQUEST['post_id']) || empty($_REQUEST['page']) || ($_REQUEST['page'] != 'rsvpmaker_scheduled_email_list'))
	return;
if( ! wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	return;
$post_id = (int) $_REQUEST['post_id'];

if(isset($_POST['scheduled_email'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
	if(is_array($_POST['scheduled_email']))
		$scheduled_email = array_map('sanitize_text_field',$_POST['scheduled_email']);
	else
		$scheduled_email = sanitize_text_field($_POST['scheduled_email']);
	update_post_meta($post_id,'scheduled_email',$scheduled_email);
}

if(!empty($_POST["email"]["from_name"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
	global $wpdb, $current_user, $post;
	$post_id = $post->ID;		
		$ev = array_map('sanitize_text_field',$_POST["email"]);
		if(empty($ev["headline"]))
			$ev["headline"] = 0;
		foreach($ev as $name => $value)
			{
			$value = sanitize_text_field($value);
			$field = '_email_'.$name;
			$single = true;
			$current = get_post_meta($post_id, $field, $single);
			 
			if($value && ($current == "") )
				add_post_meta($post_id, $field, $value, true);
			
			elseif($value != $current)
				update_post_meta($post_id, $field, $value);
			
			elseif($value == "")
				delete_post_meta($post_id, $field, $current);
			}
	}
	if( (isset($_POST["cron_active"]) || !empty($_POST["cron_relative"])) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	$chosen = (int) $_POST["chosen"]; 
	if(empty($_POST['cronday']))
	{
		$cronday = (int) $_POST['cronday'];
		$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		$day = $days[$cronday];
	}
	if(!empty($_POST['notesubject']) || !empty($_POST['notebody']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		global $current_user;
		$newpost['post_title'] = sanitize_text_field(stripslashes($_POST['notesubject']));
		$newpost['post_content'] = wp_kses_post(rsvpautog(stripslashes($_POST['notebody'])));
		$newpost['post_type'] = 'post';
		$newpost['post_status'] = sanitize_text_field($_POST['status']);
		$newpost['post_author'] = $current_user->ID;
		$chosen = wp_insert_post( $newpost );
	}
	
	if(!empty($_POST['notekey']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )	
	{
		if(!empty($_POST['notesubject']) || !empty($_POST['notebody']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		{
			global $current_user;
			$newpost['post_title'] = sanitize_text_field(stripslashes($_POST['notesubject']));
			$newpost['post_content'] = wp_kses_post(rsvpautog(stripslashes($_POST['notebody'])));
			$newpost['post_type'] = 'post';
			$newpost['post_status'] = sanitize_text_field($_POST['status']);
			$newpost['post_author'] = $current_user->ID;
			$chosen = wp_insert_post( $newpost );
		}
		update_post_meta($post_id,sanitize_text_field($_POST['notekey']),$chosen);	
	}
	$args = array('post_id' => $post_id);
	$cron_checkboxes = array("cron_active", "cron_mailchimp", "cron_members", "cron_preview");
	foreach($cron_checkboxes as $check)
		{
			$cron[$check] = (isset($_POST[$check])) ? sanitize_text_field($_POST[$check]) : 0;
		}
	$cron['cron_to'] = sanitize_text_field($_POST['cron_to']);
	//clear if previously set
	wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
	wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
	update_post_meta($post_id,'rsvpmaker_cron_email',$cron);

	if($cron["cron_active"] == '1')
		{
			$cron_fields = array("cronday", "cronhour", "cronrecur","cron_condition");
			foreach($cron_fields as $field)
				$cron[$field] = sanitize_text_field($_POST[$field]);
			$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
			$t = rsvpmaker_strtotime($days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
			if($t < time())
				$t = rsvpmaker_strtotime('next '. $days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
		}
	elseif(($cron["cron_active"] == 'relative') && !empty($_POST["cron_relative"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		$t = (int) $_POST["cron_relative"];
	elseif(($cron["cron_active"] == 'rsvpmaker_strtotime') && !empty($_POST["cron_rsvpmaker_strtotime"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		$t = rsvpmaker_strtotime(sanitize_text_field($_POST["cron_rsvpmaker_strtotime"]));
	}
	
	if(!empty($t))
		{
			if($cron["cron_preview"])
				{
					$preview = $t - ($cron["cron_preview"] * 3600);
				}
			else
				$preview = 0;
			if(empty($cron["cronrecur"]))
				{
					// single cron
					wp_schedule_single_event( $t, 'rsvpmaker_cron_email', $args );
					if($preview)
						wp_schedule_single_event( $preview, 'rsvpmaker_cron_email_preview', $args );
				}
			else
				{
					wp_schedule_event( $t, $cron["cronrecur"], 'rsvpmaker_cron_email', $args );
					if($preview)
						wp_schedule_event( $preview, $cron["cronrecur"], 'rsvpmaker_cron_email_preview', $args );
				}
		}
	else
		{
		delete_post_meta($post_id,'rsvpmaker_cron_email');
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
		}
	header('Location: ' . site_url(sanitize_text_field($_SERVER['REQUEST_URI'])));
	die();
	}
}

function rsvpmailer_default_block_template_wrapper($content, $transactional = false) {
	if($transactional)
		$rsvpmailer_default_block_template = get_rsvpmailer_tx_block_template();
	else
	$rsvpmailer_default_block_template = get_rsvpmailer_default_block_template();
	if(!empty($rsvpmailer_default_block_template))
		$content = preg_replace('/<div[^>]+class="wp-block-rsvpmaker-emailcontent"[^>]*>/',"$0 $content",$rsvpmailer_default_block_template, 1);
	return $content;
}

function rsvpevent_to_email () {
global $current_user, $rsvp_options, $email_context;

if(!current_user_can('edit_posts'))
	return;

if(!empty($_GET["rsvpevent_to_email"]) || !empty($_GET["post_to_email"]))
	{
		$email_context = true;
		if(!empty($_GET["post_to_email"]))
			{
				$id = (int) $_GET["post_to_email"];
				$permalink = get_permalink($id);
				$post = get_post($id);
				$content = '';
				if($post->post_type == 'rsvpmaker')
				{
					$content .= sprintf("<!-- wp:heading -->\n<h2>%s</h2>\n<!-- /wp:heading -->\n",$post->post_title);
					$block = rsvp_date_block($id);
					$blockgraph = str_replace('</div><div class="rsvpcalendar_buttons">','<br />',$block['dateblock']);
					$blockgraph = "<!-- wp:paragraph -->\n<p><strong>".strip_tags($blockgraph,'<br><a>').'</strong></p>'."\n<!-- /wp:paragraph -->";
					$content .= $blockgraph;
				}
				if(!empty($_GET['excerpt'])) {
					$content .= sprintf("<!-- wp:heading -->\n".'<h2><a href="%s" class="article">%s</a></h2>'."\n<!-- /wp:heading -->\n",$permalink,$post->post_title);
					$graphs = explode("<!-- /wp:paragraph -->",$post->post_content);
					for($i = 0; $i < 5; $i++)
					{
						if(!empty($graphs[$i]))
						$content .= $graphs[$i]."<!-- /wp:paragraph -->";
					}
					$content .= sprintf('<!-- wp:paragraph -->
					<p><a href="%s" class="readmore">Read More</a></p>
					<!-- /wp:paragraph -->',$permalink);		
				}
				else
					$content .= $post->post_content;
				if( ( ($post->post_type == 'rsvpmaker') || ($post->post_type == 'rsvpmaker_template') ) && get_rsvpmaker_meta($post->ID,'_rsvp_on',true))
				{
					$rsvplink = sprintf($rsvp_options['rsvplink'],get_permalink($id).'#rsvpnow');
					$content .= "\n\n<!-- wp:paragraph -->\n".$rsvplink."\n<!-- /wp:paragraph -->";
				}

				$title = $post->post_title;
			}
		else
		{
		$id = sanitize_text_field($_GET["rsvpevent_to_email"]);
		if(is_numeric($id))
			{
				if(empty($content))
					$content = '<!-- wp:rsvpmaker/event {"post_id":"'.$id.'","one_format":"button"} /-->';
				$title = get_the_title($id);
				$date = get_rsvp_date($id);		
				if($date) {
				
				$t = rsvpmaker_strtotime($date);
				global $rsvp_options;
				$title .= ' - '.rsvpmaker_date($rsvp_options["short_date"],$t);
				
				}
			}
		elseif($id == 'upcoming') {
			$content .= '<!-- wp:rsvpmaker/upcoming {"posts_per_page":"20","hideauthor":"true"} /-->';
			$title = 'Upcoming Events';
		}
		else
			return;
		}
		$content = rsvpmailer_default_block_template_wrapper($content);
		$my_post['post_title'] = $title;
		$my_post['post_content'] = $content;
		$my_post['post_type'] = 'rsvpemail';
		$my_post['post_status'] = 'publish';
		$my_post['post_author'] = $current_user->ID;
		if($post_id = wp_insert_post( $my_post ) )
			{
			if(!empty($t))
				add_post_meta($post_id,'event_timestamp',$t);
			$loc = admin_url("post.php?action=edit&post=".$post_id);
			wp_redirect($loc);
			exit;
			}
	}
}


function add_rsvpemail_caps() {
    // gets the administrator role
    $admins = get_role( 'administrator' );
    $admins->add_cap( 'edit_rsvpemail' ); 
    $admins->add_cap( 'edit_rsvpemails' ); 
    $admins->add_cap( 'edit_others_rsvpemails' ); 
    $admins->add_cap( 'publish_rsvpemails' ); 
    $admins->add_cap( 'read_rsvpemail' ); 
    $admins->add_cap( 'read_private_rsvpemails' ); 
    $admins->add_cap( 'delete_rsvpemail' ); 
}

function add_rsvpemail_caps_role($role, $publish = false) {
    // gets the administrator role
    $emailers= get_role( $role );
    $emailers->add_cap( 'edit_rsvpemail' ); 
    $emailers->add_cap( 'edit_rsvpemails' );
    $emailers->add_cap( 'edit_others_rsvpemails' ); 
    $emailers->add_cap( 'read_rsvpemail' ); 
    $emailers->add_cap( 'read_private_rsvpemails' ); 
    $emailers->add_cap( 'delete_rsvpemail' ); 
	if($publish)
    	$emailers->add_cap( 'publish_rsvpemails' ); 
}

function remove_rsvpemail_caps_role($role) {
    // gets the administrator role
    $emailers= get_role( $role );
    $emailers->remove_cap( 'edit_rsvpemail' ); 
    $emailers->remove_cap( 'edit_rsvpemails' );
    $emailers->remove_cap( 'edit_others_rsvpemails' ); 
    $emailers->remove_cap( 'read_rsvpemail' ); 
    $emailers->remove_cap( 'read_private_rsvpemails' ); 
    $emailers->remove_cap( 'delete_rsvpemail' ); 
   	$emailers->remove_cap( 'publish_rsvpemails' ); 
}

// Template selection
function rsvpmaker_email_template_redirect()
{

global $wp;
global $wp_query;

	if (isset($wp->query_vars["post_type"]) && ($wp->query_vars["post_type"] == "rsvpemail"))
	{
		if (have_posts())
		{
			include(WP_PLUGIN_DIR . '/rsvpmaker/rsvpmaker-email-template.php');
			die();
		}
		else
		{
			$wp_query->is_404 = true;
		}
	}
}

function rsvpmaker_text_version($content, $chimpfooter_text = '')
{
//match text links (not link around image, which would start with <)
$content = preg_replace('/<head.+<\/head>/s','',$content);
preg_match_all('/href="([^"]+)[^>]*>([^<]+)/',$content,$matches);
if(!empty($matches))
	{
	$content .= "\n\nLinks:\n\n";
		foreach($matches[1] as $index => $link)
			{
			$content .= $matches[2][$index] ."\n"; //anchor text	
			$content .= $link ."\n\n";
			}
	}
$text = trim(strip_tags($content));
$text = preg_replace("/[\r\n]{3,}/","\n\n",$text);

$text .= $chimpfooter_text;
return $text;
}

function rsvpmaker_personalize_email($content,$to,$description = '', $post_id = 0) {
$chimp_options = get_option('chimp');
if(empty($chimp_options['mailing_address'])) $chimp_options['mailing_address'] = apply_filters('rsvpmaker_mailing_address','[not set in RSVPMaker Mailing List settings]');
global $post;
if($post_id)
	$post = get_post($post_id);
$content = str_replace('*|EMAIL|*',$to,$content);
$content = str_replace('*|UNSUB|*',site_url('?rsvpmail_unsubscribe='.$to),$content);
$content = str_replace('*|REWARDS|*','',$content);
$content = str_replace('*|LIST:DESCRIPTION|*',$description,$content);
$content = str_replace('*|LIST:ADDRESS|*',$chimp_options['mailing_address'],$content);
$content = str_replace('*|HTML:LIST_ADDRESS_HTML|*',$chimp_options['mailing_address'],$content);
$content = str_replace('*|LIST:COMPANY|*',$chimp_options['company'],$content);
$content = str_replace('*|CURRENT_YEAR|*',date('Y'),$content);
if(isset($post->ID))
$content = str_replace('/\*.{1,4}ARCHIVE.{1,4}\*/',get_permalink($post->ID),$content);
$content = preg_replace('/<a .+FORWARD.+/','',$content);
$content = preg_replace('/\*.+\*/','',$content); // not recognized, get rid of it.
return $content;
}

function rsvpmailer_submitted($html,$text,$postvars,$post_id,$user_id) {
	global $wpdb,$rsvp_options;
	$sender_user = get_userdata($user_id);
	$recipients = array();
	$post = get_post($post_id);
	$mail['post_id'] = $post_id;
	rsvpmaker_debug_log($html,'rsvpmailer_submitted html');
	rsvpmaker_debug_log($postvars,'rsvpmailer_submitted postvars');
	rsvpmaker_debug_log($post_id,'rsvpmailer_submitted post id');
	rsvpmaker_debug_log($user_id,'rsvpmailer_submitted user id');
	$ednote = rsvpmailer_add_editors_note($post);
	rsvpmaker_debug_log($ednote,'rsvpmailer_submitted ednote');
	if(!empty($ednote['html']))
		$html = $ednote['html'];
	$mail['html'] = $html;
	$from = (isset($postvars["user_email"])) ? $sender_user->user_email : $postvars["from_email"];
	printf('<p>from %s</p>',$from);
	update_post_meta($post_id,'rsvprelay_from',$from);
	update_post_meta($post_id,'rsvprelay_fromname',$postvars["from_name"]);

	$recipients = rsvpmaker_postvars_to_recipients($postvars);
	rsvpmaker_debug_log($recipients,'postvars to recipients');

	rsvpmaker_debug_log($recipients,'submitted recipients');

	if(!empty($recipients)) {
		if(rsvpmaker_postmark_is_active()) {
			printf('<p>Trying Postmark: %s</p>',$rsvp_options['postmark_mode']);
			rsvpmaker_debug_log($recipients,'handoff to broadcast');
			$result = rsvpmaker_postmark_broadcast($recipients,$post_id);
			rsvpmaker_debug_log($result,'submitted postmark broadcast result');
			add_post_meta($post_id,'rsvprelay_sending',$recipients);
		}
		else {
			foreach($recipients as $email)
				add_post_meta($post_id,'rsvprelay_to',$email);
			}
	}
	
	if(!empty($postvars["mailchimp"]) )
	{
	$chimp_options = get_option('chimp');
	$MailChimp = new MailChimpRSVP($chimp_options["chimp-key"]);
	$listID = sanitize_text_field($postvars["mailchimp_list"]);
	update_post_meta($post_id, "_email_list",$listID);
	$custom_fields["_email_list"][0] = $listID;
	$segment_opts = array();
	
	if(!empty($postvars["mailchimp_exclude_rsvp"]))
	{
	$event = (int) $postvars["mailchimp_exclude_rsvp"];	
	$sql = "SELECT * 
	FROM  `".$wpdb->prefix."rsvpmaker` 
	WHERE  `event` = ".$event;
	$results = $wpdb->get_results($sql);
	if(is_array($results))
	foreach($results as $row)
		$rsvped[] = array('field' => 'EMAIL','condition_type' => 'EmailAddress','op' => 'not','value' => $row->email);
	if(!empty($rsvped))
		$segment_opts = array('match' => 'all','conditions' => $rsvped );
	}
	
	$input = array(
					'type' => 'regular',
					'recipients'        => array('list_id' => $listID),
					'segment_opts'        => $segment_opts,
					'settings' => array('subject_line' => sanitize_text_field(stripslashes($postvars["subject"])),'from_email' => sanitize_text_field($postvars["from_email"]), 'from_name' => sanitize_text_field($postvars["from_name"]), 'reply_to' => sanitize_text_field($postvars["from_email"]))
	);
	
	$campaign = $MailChimp->post("campaigns", $input);
	if(!$MailChimp->success())
		{
		echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
		return;
		}
	if(!empty($campaign["id"]))
	{
	$html = str_replace('<!-- mailchimp -->','<a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a><br>',$html);
	$content_result = $MailChimp->put("campaigns/".$campaign["id"].'/content', array(
	'html' => $html, 'text' => $text) );
	if(!$MailChimp->success())
		{
		echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
		return;
		}
	if(empty($postvars["chimp_send_now"]))
		{
		echo '<div>'.__('View draft on mailchimp.com','rsvpmaker').'</div>';
		}
	else // send now
		{
	$send_result = $MailChimp->post("campaigns/".$campaign["id"].'/actions/send');
	if($MailChimp->success())
		echo '<div>'.__('Sent MailChimp campaign','rsvpmaker').': '.$campaign["id"].'</div>';
	else
		echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
		}
	}
	
	}
	
	if(!empty($postvars))
		do_action("rsvpmaker_email_send_ui_submit",$postvars, $html, $text);
	
	// $unsubscribed is global, can be modified by action above
	if(!empty($unsubscribed))
		printf(__('Skipped %d unsubscribed emails','rsvpmaker'),count($unsubscribed) );
	
	//if any messages queued, make sure group email schedule is set
	if(get_post_meta($post->ID,'rsvprelay_to',true) && !wp_get_schedule('rsvpmaker_relay_init_hook') && !rsvpmaker_postmark_is_live())
		wp_schedule_event( time(), 'doubleminute', 'rsvpmaker_relay_init_hook' );
} //end rsvpmailer_submitted


function rsvpmaker_postvars_to_recipients($postvars) {

	global $wpdb;
	$post_id = $postvars['post_id'];
	$recipients = array();
	if(!empty($postvars["preview"])) {
		$recipients[] = $postvars["previewto"];
		echo '<p>Sending preview</p>';
	}

if(!empty($postvars["attendees"]) && !empty($postvars["event"]) )
{
$sending_to[] = 'event attendees';

if($postvars["event"] == 'any')
{
$sql = "SELECT DISTINCT email 
FROM  `".$wpdb->prefix."rsvpmaker`";
$title = 'one of our previous events';	
}
else {
$event = (int) $postvars["event"];
$event_post = get_post($event);
$sql = "SELECT * 
FROM  `".$wpdb->prefix."rsvpmaker` 
WHERE  `event` = ".$event." ORDER BY  `email` ASC";
$title = $event_post->post_title;
}
$results = $wpdb->get_results($sql);
if(!empty($results))
{
echo '<p>'.__('Looking up','rsvpmaker').' '. __('event attendees','rsvpmaker').'</p>';
foreach($results as $row)
	{
	if($problem = rsvpmail_is_problem($row->email))
		{
			add_post_meta($post_id,'rsvpmail_blocked',$problem);
			continue;
		}
		$email = $row->email;
		if(!in_array( $email,$recipients))
			$recipients[] = $email;
	//add_post_meta($post_id,'rsvprelay_to',$row->email);
	}
}

}

if(!empty($postvars["rsvps_since"]) && !empty($postvars["since"]) )
{
if(!in_array('event_attendees',$sending_to))
	$sending_to[] = 'event attendees';
$since = (int) $postvars["since"];
$t = rsvpmaker_strtotime('-'.$since.' days');

$date = date('Y-m-d',$t);

$sql = "SELECT DISTINCT email 
FROM  `".$wpdb->prefix."rsvpmaker` WHERE `timestamp` > '$date'";
$title = 'one of our previous events';

$results = $wpdb->get_results($sql);
if(!empty($results))
{
echo '<p>'.__('Looking up','rsvpmaker').' '.sizeof($results).' '. __('RSVPs within the last ','rsvpmaker').' '.sanitize_text_field($postvars["since"]).' days</p>';
foreach($results as $row)
	{
	if($problem = rsvpmail_is_problem($row->email))
		{
			add_post_meta($post_id,'rsvpmail_blocked',$problem);
			$unsubscribed[] = $row->email;
			continue;
		}
		$email = $row->email;
		if(!in_array( $email,$recipients))
			$recipients[] = $email;
	//add_post_meta($post_id,'rsvprelay_to',$row->email);
	}
}

}

if(!empty($postvars['custom_list'])) {
	preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", sanitize_textarea_field($postvars['custom_list']), $emails);
	if(!empty($emails[0]))
	{
		$sending_to[] = 'custom list';
		$from = (isset($postvars["user_email"])) ? $sender_user->user_email : $postvars["from_email"];
		update_post_meta($post_id,'rsvprelay_fromname',stripslashes($postvars["from_name"]));
		foreach($emails[0] as $email)
			{
			if( $problem = rsvpmail_is_problem($email) )
				{
					$unsubscribed[] = $email;
					add_post_meta($post_id,'rsvpmail_blocked',$problem);
					continue;
				}
			if(!in_array($recipients,$email))
				$recipients[] = $email;
			//add_post_meta($post_id,'rsvprelay_to',$email);
			}					
	}
}

if(!empty($postvars["members"]))
{
$users = get_users('blog='.get_current_blog_id());
printf('<p>Looking up %s website members</p>',sizeof($users));
$from = (isset($postvars["user_email"])) ? $sender_user->user_email : $postvars["from_email"];
update_post_meta($post_id,'rsvprelay_fromname',stripslashes($postvars["from_name"]));
foreach($users as $user)
	{
	if( $problem = rsvpmail_is_problem($user->user_email) )
		{
			$unsubscribed[] = $user->user_email;
			add_post_meta($post_id,'rsvpmail_blocked',$problem);
			continue;
		}
		$email = $user->user_email;
		if(!in_array( $email,$recipients))
			$recipients[] = $email;
		//		add_post_meta($post_id,'rsvprelay_to',$user->user_email);
	}
$sending_to[] = 'members of '.sanitize_text_field($_SERVER['SERVER_NAME']);
}

if(!empty($postvars["rsvp_guest_list"]))
{
$from = (isset($postvars["user_email"])) ? $sender_user->user_email : $postvars["from_email"];
update_post_meta($post_id,'rsvprelay_fromname',stripslashes($postvars["from_name"]));
$segment_text = '';
if(!empty($postvars['segment'])) {
	$guests = get_rsvpmaker_email_segment(sanitize_text_field($postvars['segment']));
	$segment_text = ' ('.$postvars['segment'].')';
}
else
	$guests = get_rsvpmaker_guest_list();
$count = 0;
foreach($guests as $guest)
	{
	$email = $guest->email;
	if( $problem = rsvpmail_is_problem($guest->email) )
		{
			$unsubscribed[] = $guest->email;
			add_post_meta($post_id,'rsvpmail_blocked',$problem);
			continue;
		}
	$count++;
	$email = $guest->email;
	if(!in_array( $email,$recipients))
		$recipients[] = $email;
	//		add_post_meta($post_id,'rsvprelay_to',$guest->email);
	}
	printf('<p>Looking up %s members of the guest email list %s</p>',$count, $segment_text);
	$sending_to[] = 'guest list';
}

if(!empty($postvars["network_members"]) && user_can('manage_network',$user_id) )
{
$from = (isset($postvars["user_email"])) ? $sender_user->user_email : $postvars["from_email"];
update_post_meta($post_id,'rsvprelay_fromname',sanitize_text_field(stripslashes($postvars["from_name"])));
$users = get_users('blog='.get_current_blog_id());
$sending_to[] = 'website network members';
printf('<p>Looking up website members</p>',sizeof($users));
foreach($users as $user)
	{
	if($problem = rsvpmail_is_problem($user->user_email))
		{
			add_post_meta($post_id,'rsvpmail_blocked',$problem);
			$unsubscribed[] = $user->user_email;
			continue;
		}
	$email = $user->user_email;
	if(!in_array( $email,$recipients))
		$recipients[] = $email;
		//update_post_meta($post_id,'rsvprelay_to',$user->user_email);
	}
}
if(!empty($sending_to))
update_post_meta($post_id,'message_description',__('This message was sent from','rsvpmaker').' '.sanitize_text_field($_SERVER['SERVER_NAME']).' to '.implode(', ',$sending_to));

if(!empty($postvars["members_rsvp"]) && !empty($recipients))
{
$event = $postvars['members_rsvp_event'];
$rule = ($postvars["members_rsvp"] == 1) ? 'include' : 'exclude';
$emails = $recipients;
$recipients = array();
$count = 0;
foreach($emails as $email)
	{
	$sql = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker WHERE email LIKE %s AND event=%d', $email, $event );
	$row = $wpdb->get_row( $sql );
	if($row && 'exclude' == $rule)
		continue;
	if(!$row && 'include' == $rule)
		continue;
	$count++;
	$recipients[] = $email;
	//		add_post_meta($post_id,'rsvprelay_to',$user->user_email);
	}
	printf('<p>Filtering to %s by RSVP status (%s)</p>',$count,$rule);
}
return array_unique($recipients);
}


add_shortcode('rsvpmaker_template_inline_test','rsvpmaker_template_inline_test');

function rsvpmaker_template_inline_test($atts) {
	if(is_admin())
		return;
	if(isset($atts['id']))
	$html = rsvpmaker_template_inline(intval($atts['id']));
	$text = get_post_meta(intval($atts['id']),'_rsvpmail_text',true);
	return $html . '<pre>'.$text.'</pre>';
}

function rsvpmailer_delayed_send($post_id,$user_id, $postvars = null) {
	if(empty($postvars))
		$postvars = get_post_meta($post_id,'scheduled_send_vars',true);
	$html = get_post_meta($post_id,'_rsvpmail_html',true);
	$text = get_post_meta($post_id,'_rsvpmail_text',true);
	ob_start();
	$result = rsvpmailer_submitted($html,$text,$postvars,$post_id,$user_id);
	$result .= ob_get_clean();
	rsvpmaker_debug_log($result,'rsvpmailer_submitted results');
}

function rsvpmailer_add_editors_note($post) {
	$editorsnote['add_to_head'] = '';
	$notekey = get_rsvp_notekey($post->ID);
	$chosen = (int) get_post_meta( $post->ID, $notekey, true );
	if(!$chosen)
		return $editorsnote;

	$notepost = get_post( $chosen );
	if(!$notepost)
		return $editorsnote;

	$editorsnote['add_to_head'] = ': '.$notepost->post_title;

	$postparts = explode( '<!--more-->', $notepost->post_content );

	$note = str_replace( '<!-- wp:more -->', '', $postparts[0] );

	if ( ! empty( $postparts[1] ) ) {

		$note .= sprintf( '<p><a href="%s">%s</a>', get_permalink( $chosen ), __( 'Read more', 'rsvpmaker' ) );
	}

	$note = '<h2>'.$editorsnote['add_to_head']."</h2>\n".$note;

	if(strpos($post->post_content,'<!-- editors note goes here -->'))
		$editorsnote['html'] = str_replace( '<!-- editors note goes here -->', $note, $post->post_content );
	elseif(strpos($post->post_content,'wp-block-rsvpmaker-emailcontent'))
		$editorsnote['html'] = preg_replace('/<div[^>]+class="wp-block-rsvpmaker-emailcontent"[^>]*>/',"$0 $note",$post->post_content, 1);
	else
		$editorsnote['html'] = $note."\n".$post->post_content;
	$editorsnote['html'] = rsvpmaker_email_html($post->post_content);
	return $editorsnote;
}

add_action('rsvpmailer_delayed_send','rsvpmailer_delayed_send',10,2);

function rsvpmaker_email_send_ui($html, $text)
{
global $post;
global $custom_fields;
global $wpdb;
global $current_user;
global $rsvpmaker_cron_context;
global $rsvp_options;
if(!empty($rsvpmaker_cron_context))
	return;
$chimp_options = get_option('chimp');
$post_id = $post->ID;

ob_start();

if(isset($_POST['bigsend'])) {
	echo '<p>Big email broadcast confirmed!</p>';
	wp_schedule_single_event( time(), 'rsvpmailer_delayed_send', array($post->ID, $current_user->ID));
}

if(isset($_POST['preview_text'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	update_post_meta($post->ID,'_rsvpmailer_preview',sanitize_text_field(stripslashes($_POST['preview_text'])));

if(!current_user_can('publish_rsvpemails') )
	return;

$chimp_options = get_option('chimp');

if(!empty($_POST["subject"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		$subject = sanitize_text_field(stripslashes($_POST["subject"]));
		if($post->post_title != $subject)
		{
			$post->post_title = $subject;
			$postarr["ID"] = $post->ID;
			$postarr["post_title"] = $subject;
			wp_update_post($postarr);
		}
	}

if(!empty($_POST["send_when"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
	$postvars = $_POST;
	$postvars = array_map('sanitize_text_field',$postvars);
	$postvars['post_id'] = $post->ID;
	$manage_network = current_user_can('manage_network');
	$recipients = rsvpmaker_postvars_to_recipients($postvars);
	if('now' == $_POST["send_when"]) {
		update_post_meta($post->ID,'scheduled_send_vars',$postvars);
		$size = sizeof($recipients);
		echo "<p>Size of list $size</p>";
		if($size > 100) {
			$showprompt = false;
			$postmark_settings = get_rsvpmaker_postmark_options();
			if(empty($postmark_settings['postmark_mode']))
				$showprompt = true;
			elseif(empty($postmark_settings['limited']))
				$showprompt = true;
			elseif(is_array($postmark_settings['allowed']) && in_array(get_current_blog_id(),$postmark_settings['allowed']))
				$showprompt = true;
			//better get confirmation
			if($showprompt) {
			echo '<div style="border: medium solid red; padding: 10px; margin-bottom: 20px;">';
			printf('<h3>Confirmation Required</h3><p>Email broadcasts to more than 100 emails require confirmation (this list is %d). Do you wish to proceed?</p>',sizeof($recipients));
			printf('<form method="post" action="%s"><input type="hidden" name="bigsend" value="1"><button>Confirm</button>',get_permalink());
			rsvpmaker_nonce();
			echo '</form>';
			echo '</div>';
		}
		else {
			echo '<div style="border: medium solid red; padding: 10px; margin-bottom: 20px;">';
			echo '<h3>Sending to Lists of Over 100 Not Allowed</h3>'.$postmark_settings['site_admin_message'];
			echo '</div>';
		}
	}
		else
			rsvpmailer_delayed_send($post->ID, $current_user->ID, $postvars);
	}
	elseif('schedule' == $_POST["send_when"])
	{
		$t = rsvpmaker_strtotime($_POST['send_date'].' '.$_POST['send_time']);
		wp_schedule_single_event( $t, 'rsvpmailer_delayed_send', array($post->ID, $current_user->ID));
		printf('<p><em>Scheduling to send at %s to %s recipients.</em></p>',rsvpmaker_date($rsvp_options['short_date'].' '.$rsvp_options['time_format'],$t), sizeof($recipients) );
	}
	elseif('advanced' == $_POST["send_when"])
	{
	update_post_meta($post->ID,'scheduled_send_vars',$postvars);
	if( (isset($_POST["cron_active"]) || !empty($_POST["cron_relative"])) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
		$chosen = (int) $_POST["chosen"]; 
		if(empty($_POST['cronday']))
		{
			$cronday = (int) $_POST['cronday'];
			$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
			$day = $days[$cronday];
		}
		if(!empty($_POST['notesubject']) || !empty($_POST['notebody']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
		{
			global $current_user;
			$newpost['post_title'] = sanitize_text_field(stripslashes($_POST['notesubject']));
			$newpost['post_content'] = wp_kses_post(rsvpautog(stripslashes($_POST['notebody'])));
			$newpost['post_type'] = 'post';
			$newpost['post_status'] = sanitize_text_field($_POST['status']);
			$newpost['post_author'] = $current_user->ID;
			$chosen = wp_insert_post( $newpost );
		}		
		if(!empty($_POST['notekey']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )	
			update_post_meta($post_id,sanitize_text_field($_POST['notekey']),$chosen);
		$args = array('post_id' => $post_id);
		$cron_checkboxes = array("cron_active", "cron_preview");
		foreach($cron_checkboxes as $check)
			{
				$cron[$check] = (isset($_POST[$check])) ? sanitize_text_field($_POST[$check]) : 0;
			}
		//clear if previously set
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
		update_post_meta($post_id,'rsvpmaker_cron_email',$cron);
	
		if($cron["cron_active"] == '1')
			{
				$cron_fields = array("cronday", "cronhour", "cronrecur","cron_condition");
				foreach($cron_fields as $field)
					$cron[$field] = sanitize_text_field($_POST[$field]);
				$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
				$t = rsvpmaker_strtotime($days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
				if($t < time())
					$t = rsvpmaker_strtotime('next '. $days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
			}
		elseif(($cron["cron_active"] == 'relative') && !empty($_POST["cron_relative"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
			$t = (int) $_POST["cron_relative"];
		elseif(($cron["cron_active"] == 'rsvpmaker_strtotime') && !empty($_POST["cron_rsvpmaker_strtotime"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
			$t = rsvpmaker_strtotime(sanitize_text_field($_POST["cron_rsvpmaker_strtotime"]));
		}
		
		if(!empty($t))
			{
				if($cron["cron_preview"])
					{
						$preview = $t - ($cron["cron_preview"] * 3600);
					}
				else
					$preview = 0;
				if(empty($cron["cronrecur"]))
					{
						// single cron
						wp_schedule_single_event( $t, 'rsvpmaker_cron_email', $args );
						if($preview)
							wp_schedule_single_event( $preview, 'rsvpmaker_cron_email_preview', $args );
					}
				else
					{
						wp_schedule_event( $t, $cron["cronrecur"], 'rsvpmaker_cron_email', $args );
						if($preview)
							wp_schedule_event( $preview, $cron["cronrecur"], 'rsvpmaker_cron_email_preview', $args );
					}
			}
		else
			{
			delete_post_meta($post_id,'rsvpmaker_cron_email');
			wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
			wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
			}
		}
	}//end send_when advanced
}

$permalink = get_permalink($post->ID);
if(isset($_GET['scheduling']))
	$permalink = add_query_arg('scheduling',1,$permalink);
$edit_link = get_edit_post_link($post->ID);
$events_dropdown = get_events_dropdown ();	
$queued = get_post_meta($post->ID,'rsvprelay_to');
if($queued) {
	rsvpmaker_relay_queue();
	//make sure this is turned on
	update_option('rsvpmaker_discussion_active',true);
	if(!wp_next_scheduled('rsvpmaker_relay_init_hook') && !rsvpmaker_postmark_is_active())
		wp_schedule_event( time(), 'doubleminute', 'rsvpmaker_relay_init_hook' );
	$queued = get_post_meta($post->ID,'rsvprelay_to');
	if($queued) {
		//if more in queue
		printf('<p>%s emails queued to send (<a href="%s">Refresh</a>)</p>',sizeof($queued),$permalink);
		if(isset($_GET['show_log']))
			printf('</p>%s</p>',implode(', ',$queued));
	}
}
$sent = get_post_meta($post->ID,'rsvpmail_sent');
$blocked = get_post_meta($post->ID,'rsvpmail_blocked');
if($sent && !rsvpmaker_postmark_is_active()) {
	printf('<p>%s emails sent, %s blocked (<a href="%s">Refresh</a> | <a href="%s">Show Addresses</a>)</p>',sizeof($sent),sizeof($blocked),$permalink,add_query_arg('show_log',1,$permalink));
	if(isset($_GET['show_log'])){
		printf('</p>%s</p>',implode('<br>',$sent));
		printf('</p>%s</p>',implode('<br>',$blocked));
	}
}
if(!isset($_POST))
{
	$mailchimp_sent = get_post_meta($post->ID,'rsvp_mailchimp_sent');
	if($mailchimp_sent)
		printf('</p>%s</p>',implode(', ',$mailchimp_sent));	
}

wp_admin_bar_render(); 
$cronpostvars = get_post_meta($post->ID,'scheduled_send_vars',true);
if(empty($cronpostvars))
	$cronpostvars = array();
?>
<div style="width: 150px; float:right;"><button onclick="hideControls()">Hide Controls</button></div>
<form method="post" action="<?php echo esc_attr($permalink); ?>">
<?php rsvpmaker_nonce(); ?>
<table>
<tr><td><?php esc_html_e('Subject','rsvpmaker');?>:</td><td><input type="text"  size="50" name="subject" value="<?php echo esc_attr($post->post_title); ?>" /></td></tr>
<?php 
if(empty($chimp_options["email-name"]))
$chimp_options["email-name"] = $chimp_options["email-from"] = '';
?>
<tr><td><?php esc_html_e('From Name','rsvpmaker');?>:</td><td><input type="text"  size="50" name="from_name" value="<?php echo (isset($custom_fields["_email_from_name"]) && isset($custom_fields["_email_from_name"][0])) ? esc_attr($custom_fields["_email_from_name"][0]) : esc_attr($chimp_options["email-name"]); ?>" /></td></tr>
<tr><td><?php esc_html_e('From Email','rsvpmaker');?>:</td><td><input type="text" size="50"  name="from_email" value="<?php echo (isset($custom_fields["_email_from_email"][0])) ? esc_attr($custom_fields["_email_from_email"][0]) : esc_attr($chimp_options["email-from"]); ?>" />
</td></tr>
<tr><td><?php esc_html_e('Preview Text','rsvpmaker');?>:</td><td><input type="text" size="50"  name="preview_text" value="<?php echo rsvpmailer_preview(array()); ?>" />
</td></tr>
</table>
<?php
if(!empty($chimp_options["chimp-key"]))
{
?>
<div id="mailchimp-option">
<input type="checkbox" name="mailchimp" onclick="hideNonChimp()" value="1" <?php if(isset($_GET['mailchimp']) || !empty($cronpostvars['mailchimp'])) echo ' checked="checked" '; ?> > <?php esc_html_e('MailChimp list','rsvpmaker');?> <select name="mailchimp_list">
<?php
$chosen = (isset($custom_fields["_email_list"][0])) ? $custom_fields["_email_list"][0] : $chimp_options["chimp-list"];
echo mailchimp_list_dropdown($chimp_options["chimp-key"], $chosen);
?>
</select> <select name="chimp_send_now"><option value="1"><?php esc_html_e('Create and Send','rsvpmaker'); ?></option><option value="" <?php if(isset($_POST["mailchimp"]) && empty($_POST["chimp_send_now"])) echo ' selected="selected" '; ?> ><?php esc_html_e('Save as draft on mailchimp.com','rsvpmaker'); ?></option></select>
</div>
<?php
}
?>
<div id="nonchimp">
<div><input type="checkbox" name="preview" value="1" <?php if(!empty($cronpostvars['preview'])) echo 'checked="checked"'; ?> > <?php esc_html_e('Preview to','rsvpmaker');?>: <input type="text" name="previewto" value="<?php echo (isset($custom_fields["_email_preview_to"][0])) ? $custom_fields["_email_preview_to"][0] : $chimp_options["email-from"]; ?>" />
<br><em>Send yourself a test first to check email formatting.</em>
</div>
<div><input type="checkbox" name="members" value="1" <?php if(isset($_GET['list']) && ($_GET['list'] == 'members') || !empty($cronpostvars['members'])) echo 'checked="checked"'; ?> > <?php esc_html_e('Website members','rsvpmaker');?></div>
<div>
<input type="checkbox" name="rsvp_guest_list" value="1" <?php if(!empty($cronpostvars['rsvp_guest_list'])) echo ' checked="checked" '; ?> > RSVP Mail Email List <?php rsvpmaker_email_segments_dropdown(); ?>
</div>
<div id="showmore_wrapper"><input type="checkbox" id="showmore" onclick="showMore();"> Show More Options</div>
<div id="moreoptions" style="display: none;">
<div><?php esc_html_e('Custom List','rsvpmaker');?><br /><textarea name="custom_list" rows="3" cols="80"></textarea></div>
<?php if(is_multisite() && current_user_can('manage_network') && (get_current_blog_id() == 1)) {
?>
<div style="border: thin dotted red;"><strong>Network Administrator Only:</strong><br /> 
<input type="checkbox" name="network_members" value="1" <?php if(!empty($cronpostvars['network_members'])) echo ' checked="checked" '; ?> > <?php esc_html_e('All users','rsvpmaker');?>
</div>
<?php
} ?>
<div><input type="checkbox" name="attendees" value="1" <?php if(!empty($cronpostvars['attendees'])) echo ' checked="checked" '; ?> > <?php esc_html_e('Attendees','rsvpmaker');?> <select name="event"><option value=""><?php esc_html_e('Select Event','rsvpmaker');?></option><option value="any"><?php esc_html_e('Any event','rsvpmaker');?></option><?php echo $events_dropdown; ?></select></div>
<div><input type="checkbox" name="rsvps_since" value="1" <?php if(!empty($cronpostvars['rsvps_since'])) echo ' checked="checked" '; ?> > <?php esc_html_e('RSVPs more recent than ','rsvpmaker');?> <input type="text" name="since" value="30" /> <?php esc_html_e('Days','rsvpmaker');?></div>
<?php
do_action("rsvpmaker_email_send_ui_options");
?>
<p>For any of the above (not Mailchimp), send ONLY to list members who<br>
<input type="radio" name="members_rsvp" value="1" <?php if(!empty($cronpostvars['members_rsvp']) && 1 == $cronpostvars['members_rsvp'] ) echo ' checked="checked" '; ?>>
<?php esc_html_e('RSVP\'ed to the event specified below','rsvpmaker');?><br><input type="radio" name="members_rsvp" value="2" <?php if(!empty($cronpostvars['members_rsvp']) && 2 == $cronpostvars['members_rsvp'] ) echo ' checked="checked" '; ?> > <?php esc_html_e('DID NOT RSVP to the event specified below','rsvpmaker');?><br><input type="radio" name="members_rsvp" value="0" checked="checked" > N/A
<br><select name="members_rsvp_event">
<option value="">Choose Event</option>
<?php
echo $events_dropdown;
?>
</select>	
</p>
</div><!--end more options -->
</div><!--end nonchimp -->
<p><button><?php esc_html_e('Send','rsvpmaker');?></button> <input type="radio" name="send_when" value="now" <?php if(!isset($_GET['scheduling'])) echo 'checked="checked"'; ?>> Now <input type="radio" name="send_when" value="schedule" > Schedule for <input type="date" name="send_date" value="<?php echo rsvpmaker_date('Y-m-d'); ?>"> <input name="send_time" type="time" value="<?php echo rsvpmaker_date('H:i',strtotime('+1 hour')); ?>"> <input type="radio" name="send_when" value="advanced" onclick="showCron()" <?php if(isset($_GET['scheduling'])) echo 'checked="checked"'; ?> > Advanced</p>
<?php 
printf('<div id="cron_schedule_options" %s>',(isset($_GET['scheduling'])) ? '' : 'style="display:none"');
rsvpmaker_cron_schedule_options();
echo '</div>';
?>
</form>
<script>
function hideControls() {
var x = document.getElementById("control-wrapper");
x.style.display = "none";
}
function showMore() {
var x = document.getElementById("moreoptions");
x.style.display = "block";
var x = document.getElementById("showmore_wrapper");
x.style.display = "none";
}
function hideNonChimp() {
var x = document.getElementById("nonchimp");
x.style.display = "none";
}

function showCron() {
var x = document.getElementById("cron_schedule_options");
x.style.display = "block";
}

</script>
<?php

$ts = rsvpmaker_next_scheduled($post->ID);
if($ts)
	printf('<p><a href="%s">Preview scheduled broadcast</a> for %s',add_query_arg('cronemailpreview',$post->ID,$permalink),esc_html($ts));
$pmstatus = (rsvpmaker_postmark_is_active()) ? '<p>The Postmark service for reliable email delivery is active.</p>' : '';
	
return '<div id="control-wrapper" ><h5>RSVP Mail Controls</h5>'.ob_get_clean().$pmstatus.'</div>';
}


function RSVPMaker_extract_email() {

global $wpdb;
$inchimp = '';
if(isset($_POST["emails"]) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{

$chimp_options = get_option('chimp');

$apikey = $chimp_options["chimp-key"];
$listId = $chimp_options["chimp-list"];
 
	preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", wp_kses_post($_POST["emails"]), $emails);
	$emails = $emails[0];
	foreach($emails as $email)
		{
			$email = strtolower($email);
			$unique[$email] = $email;
		}
	sort($unique);
	foreach($unique as $email)
		{
		$email = strtolower($email);
		$hash = md5($email);
		if(!empty($_POST["in_mailchimp"]))
			{
			if(!isset($MailChimp) && !empty($apikey))
				$MailChimp = new MailChimpRSVP($apikey);
			$member = $MailChimp->get("/lists/".$listId."/members/".$hash);
			if(!empty($member["id"]) )
				{
				$inchimp .= "\n<br />$email";
				continue;
				}
			}
		echo "\n<br />$email";
		}
if($inchimp)
	echo "<h3>In MailChimp</h3>$inchimp";

	}

rsvpmaker_admin_heading(__('Extract Email Addresses','rsvpmaker'),__FUNCTION__); ?>
<p><?php esc_html_e('You can enter an disorganized list of emails mixed in with other text, and this utility will extract just the email addresses.','rsvpmaker');?></p>
<form id="form1" name="form1" method="post" action="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_extract_email'); ?>">
<?php rsvpmaker_nonce(); ?>
  <p>
    <textarea name="emails" id="emails" cols="45" rows="5"></textarea>
  </p>
  <p><?php esc_html_e('Filter out emails that','rsvpmaker');?>:</p>
  <p>
    <input name="in_mailchimp" type="checkbox" id="in_mailchimp" checked="checked" />
  <?php esc_html_e('Are Registered in MailChimp','rsvpmaker');?></p>
  <p>
    <input type="submit" name="button" id="button" value="Submit" />
  </p>
</form>
<?php
}

function inline_array($text) {
$lines = explode("\n",$text);
$inline_array = array();
foreach($lines as $line)
	{
		$line = trim($line);
		if(strpos($line,'='))
			{	
			$parts = explode('=',$line);
			$inline_array[$parts[0]] = $parts[1];
			}
	}
return $inline_array;
}	

function filter_allowed_block_types_for_rsvpemail( $allowed_block_types, $editor_context ) {
	global $post;
	//print_r($allowed_block_types);
    if ( 'rsvpemail' == $post->post_type ) {
		$allowed_block_types = array();
		$block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();
		foreach($block_types as $block_name => $block) {
			if(!strpos($block_name,'columns') && !strpos($block_name,'row') && !strpos($block_name,'ore/grid'))
			$allowed_block_types[] = $block_name;
		}
    }
    return $allowed_block_types;
}
 
//add_filter( 'allowed_block_types_all', 'filter_allowed_block_types_for_rsvpemail', 10, 2 );

function rsvpmail_candidate_templates($alt_template = false) {
	global $wpdb;
	if($alt_template)
		$sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE meta_key='alt_template' and meta_value=1 ORDER BY ID DESC";
	else
		$sql = "SELECT * FROM $wpdb->posts WHERE post_content LIKE '%wp-block-rsvpmaker-emailcontent%' and post_status='publish' and post_type='rsvpemail' ORDER BY ID DESC";
	$candidates = $wpdb->get_results($sql);
	if(empty($candidates))
		$candidates = array();
	return $candidates;
}

function rsvpmaker_email_template () {
?>
<style>
.currentdefault {
	margin: 10px;
	border: thin dotted #000;
	padding: 10px;
	background-color: #fff;
}
textarea {
	width: 80%;
}

</style>
<?php rsvpmaker_admin_heading(__('RSVPMaker Email Design Templates','rsvpmaker'),__FUNCTION__); ?>
<?php
if(!empty($_POST['timelord'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
{
if(!empty($_POST['newtemplate']) )
	{
		update_option('rsvpmailer_default_block_template',intval($_POST['newtemplate']));
	}
if(!empty($_POST['txtemplate']) )
{
	update_option('rsvpmailer_tx_block_template',intval($_POST['txtemplate']));
}
if(!empty($_POST['alt_template']) )
{
	update_post_meta(intval($_POST['alt_template']),'alt_template',1);
}
if(isset($_POST['welcome']))
	update_option('rsvpmaker_guest_email_welcome',intval($_POST['welcome']));

}

$candidates = rsvpmail_candidate_templates();
$options = '';
foreach($candidates as $candidate) {
	$options .= sprintf('<option value="%d">%s %s</option>',$candidate->ID, $candidate->post_title, $candidate->post_modified);
}
?>
<p style="text-align: center;"><strong>Jump to: </strong> <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template#emailtemplates') ?>">Email Templates</a> | <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template#customcss') ?>">Custom CSS</a></p>
<p>Use this screen to change your default email template for newsletters and other broadcasts, your transactional template (for messages such as RSVP Confirmations), and any alternate designs you may use from time to time. You can see previews of your current <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template#emailtemplates') ?>">Email Templates</a> and add any <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template#customcss') ?>">Custom CSS</a> you would like used with your messages. Codes like *|EMAIL|* are MailChimp template codes, also used by RSVPMaker independent of WordPress. Including an unsubscribe link and information about who is responsible for messages sent from your website is important for regulatory compliance.</p>
<?php
if(!empty($options)) {
?>
<form id="email_style" name="email_style" method="post" action="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template'); ?>">
<?php
	$options = '<option value="">'.__('Choose Message','rsvpmaker').'</option>'.$options;
	$default = rsvpmaker_post_option_by_setting('rsvpmailer_default_block_template');
	echo "<p>".__('Template for Editor','rsvpmaker')."<br><select name=\"newtemplate\">$default $options</select></p>";
	$default = rsvpmaker_post_option_by_setting('rsvpmailer_tx_block_template');
	echo "<p>".__('Template for Transactional Messages','rsvpmaker')."<br><select name=\"txtemplate\">$default $options</select>
	<br>Example: RSVP confirmation messages.
	</p>";
	$welcome = rsvpmaker_post_option_by_setting('rsvpmaker_guest_email_welcome');
	echo "<p>".__('Welcome Message','rsvpmaker')."<br><select name=\"welcome\">$welcome $options</select>
	<br>Optional: send to new members of list upon registration
	</p>";
	echo "<p>".__('Add Alternate Templates','rsvpmaker')."<br><select name=\"alt_template\">$options</select>
	<br>Example: a newsletter template containing a latest posts block that you use, but not all the time.";
	$candidates = rsvpmail_candidate_templates(true);
foreach($candidates as $candidate) {
	$alts[] = sprintf('<a href="%s">%s</a>',admin_url('post.php?post='.$candidate->ID.'&action=edit'),$candidate->post_title.' '.$candidate->post_modified);
}
if(!empty($alts)) {
	echo '<br>Previously registered: '.implode(', ',$alts);
}
echo "</p>\n";
rsvpmaker_nonce();
global $rsvp_options;
$chimp_options = get_option('chimp');
if(empty($chimp_options['mailing_address']))
	printf('<p><strong>%s</strong></p>',__('A physical mailing address should be entered in in RSVPMaker Mailing List settings.','rsvpmaker'));
?>
<p>
<button><?php esc_html_e('Save','rsvpmaker');?></button>
</p>
</form>

<?php

$candidates = rsvpmail_candidate_templates(true);
$alt = '';
foreach($candidates as $candidate) {
	$alt .= sprintf('<option value="%d">%s %s</option>',$candidate->ID, $candidate->post_title, $candidate->post_modified);
}

$options = '<optgroup label="Templates">'.$alt.'</optgroup><optgroup label="Previous Emails">'.$options.'</optgroup>';

echo '<h2>'.esc_html('Create Based on Template or Previous Message','rsvpmaker').'</h2>';

printf('<form id="alt_template" name="alt_template" method="get" action="%s"><input type="hidden" name="post_type" value="rsvpemail"><select name="template">%s</select><button>Copy to New Message</button></form>',admin_url('post-new.php'),$options);

}

echo '<h3 id="emailtemplates">Email Templates</h3>';

$content = get_rsvpmailer_default_block_template(true);
if(!empty($content))
	printf('<p>Current default email template</p><div class="currentdefault" id="currentdefault">%s</div>',rsvpmail_filter_style($content));

$content = get_rsvpmailer_tx_block_template(true);
if(!empty($content))
	printf('<p>Current transactional template</p><div id="currenttx"  class="currentdefault" >%s</div>',rsvpmail_filter_style($content));

$welcome = get_option('rsvpmaker_guest_email_welcome');
if($welcome)
{
	$welcome_post = get_post($welcome);
	printf('<p>Current welcome message</p><div id="currentwelcome"  class="currentdefault" >%s</div>','<p><a href="'.admin_url("post.php?post=$welcome&action=edit").'">'.__('Edit','rsvpmaker').'</p>'.rsvpmaker_email_html($welcome_post));
}

$candidates = rsvpmail_candidate_templates(true);
foreach($candidates as $candidate) {
	printf('<p>Alternate template %s</p><div  class="currentdefault" >%s</div>',$candidate->post_title,'<p><a href="'.admin_url("post.php?post=$candidate->ID&action=edit").'">'.__('Edit','rsvpmaker').'</p>'.rsvpmaker_email_html($candidate));
}

$custom_style_array = array();
if(isset($_POST['rsvpmaker_email_base_font'])) {
	$rsvpmaker_email_base_font = stripslashes(sanitize_text_field($_POST['rsvpmaker_email_base_font']));
	update_option('rsvpmaker_email_base_font',$rsvpmaker_email_base_font);	
}
else
	$rsvpmaker_email_base_font = get_option('rsvpmaker_email_base_font');

if(isset($_POST['rsvpmaker_custom_email_tag_styles']))
{
	foreach($_POST['rsvpmaker_custom_email_tag_styles'] as $index => $value) {
		$rsvpmaker_custom_email_tag_styles[$index] = sanitize_text_field($value);
	}
	update_option('rsvpmaker_custom_email_tag_styles',$rsvpmaker_custom_email_tag_styles);
}
else 
	$rsvpmaker_custom_email_tag_styles = get_option('rsvpmaker_custom_email_tag_styles');

if(isset($_POST['custom_style_input'])){
	$custom = stripslashes(sanitize_textarea_field($_POST['custom_style_input']));
	$custom_style_array = rsvpmaker_css_to_array($custom);
	update_option('rsvpmaker_email_custom_styles',$custom_style_array);
}
else {
	$custom_style_array = get_option('rsvpmaker_email_custom_styles');
}
$custom = '';
if(!empty($custom_style_array)) {
	$custom = '';
	foreach($custom_style_array as $class => $style)
		$custom .= '.'.$class.'{'.$style.';}'."\n";
	$custom = str_replace(';;',';',$custom);
}

if(empty($rsvpmaker_custom_email_tag_styles)) {
$rsvpmaker_custom_email_tag_styles['p']='';
$rsvpmaker_custom_email_tag_styles['h1']='';
$rsvpmaker_custom_email_tag_styles['h2']='';
$rsvpmaker_custom_email_tag_styles['h3']='';
$rsvpmaker_custom_email_tag_styles['h4']='';
}

printf('<h3 id="customcss">%s</h3><p>%s</p><code>.my-custom-class{background-image:linear-gradient(red,yellow);padding-bottom:5px}</code><br>
<form id="custom_styles" name="custom_styles" method="post" action="%s"><textarea name="custom_style_input">%s</textarea><br>
<label style="display:inline-block; width: 100px;">Base font</label> <input type="text" name="rsvpmaker_email_base_font" value="%s"> - set the base font to be used for all text unless otherwise specified<br />
Example: make the base body text font larger (headlines will be sized up proportionately)<br><code>font-size: 20px;</code><br>
Exemple: set the font size and font family<br><code>font-family: Verdana, sans-serif;font-size: 20px;</code><br>
<label style="display:inline-block; width: 100px;">CSS for p</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[p]" value="%s" /><br>
<label style="display:inline-block; width: 100px;">CSS for h1</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[h1]" value="%s" /><br>
<label style="display:inline-block; width: 100px;">CSS for h2</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[h2]" value="%s" /><br>
<label style="display:inline-block; width: 100px;">CSS for h3</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[h3]" value="%s" /><br>
<label style="display:inline-block; width: 100px;">CSS for h4</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[h4]" value="%s" /><br>
<label style="display:inline-block; width: 100px;">CSS for a</label> <input type="text" name="rsvpmaker_custom_email_tag_styles[a]" value="%s" /><br>
%s
<button>Submit</button></form>',__('Custom Inline Styles','rsvpmaker'),__('Add custom styles that will replace a single class in the format ','rsvpmaker'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template'),$custom,$rsvpmaker_email_base_font,
$rsvpmaker_custom_email_tag_styles['p'],
$rsvpmaker_custom_email_tag_styles['h1'],
$rsvpmaker_custom_email_tag_styles['h2'],
$rsvpmaker_custom_email_tag_styles['h3'],
$rsvpmaker_custom_email_tag_styles['h4'],
$rsvpmaker_custom_email_tag_styles['a'],
rsvpmaker_nonce('return'));
echo '<p>Notes: customizations will not be reflected in the editor. If changing the font family, use <a href="https://www.w3schools.com/cssref/css_websafe_fonts.asp">web safe fonts</a> that do not rely on external stylesheets.</p>';

echo '<h2>Current Class-to-Style Conversions</h2><div style="background-color: #fff">';
$style_sub = rsvpmaker_get_style_substitutions();
foreach($style_sub as $index => $value) {
	printf('<p>class to CSS<code>.%s{%s}</code></p>',esc_html($index),esc_html($value));
	if(strpos($index,'lign'))
		printf('<div style="clear: both; border: thin solid #000;  font-size: 20px; %s">Aligned content</div><p>ipsum lorem ipsum lorem</p><p>ipsum lorem ipsum lorem</p>',esc_html($value));
	elseif(strpos($index,'background'))
		printf('<div style="clear: both; border: thin solid #000; font-size: 20px; %s">ipsum lorem ipsum lorem</div>',esc_html($value));
	elseif(strpos($index,'column'))
		printf('<div style="20px; %s">ipsum lorem ipsum lorem</div><div style="20px; %s">ipsum lorem ipsum lorem</div>',esc_html($value),esc_html($value));
	else
		printf('<div><span style="background-color: white; font-size: 20px; %s">Sample on white background</span> <span style="background-color: gray; font-size: 20px; %s">Sample on gray background</span> <span style="background-color: black; font-size: 20px; %s">Sample on black background</span></div>',esc_html($value),esc_html($value),esc_html($value));
}
echo '</div>';

} // end rsvpemail template form

function rsvpmaker_post_option_by_setting($option_slug) {
	$option = '';
	$id = (int) get_option($option_slug);
	if($id) {
		$post = get_post($id);
		if($post)
			$option = sprintf('<option value="%d">%s</option>',$id,$post->post_title);
	}
	return $option;
}	

function my_rsvpemail_menu() {
global $rsvp_options;

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Content for Email",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "email_get_content";
$function = "email_get_content";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("RSVPMaker Email List",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_guest_list";
$function = "rsvpmaker_guest_list";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Unsubscribed List",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "unsubscribed_list";
$function = "unsubscribed_list";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Email Design Templates",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_email_template";
$function = "rsvpmaker_email_template";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Notification Templates",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_notification_templates";
$function = "rsvpmaker_notification_templates";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Scheduled Email",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_scheduled_email_list";
$function = "rsvpmaker_scheduled_email_list";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Extract Addresses",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_extract_email";
$function = "rsvpmaker_extract_email";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

if(!empty($rsvp_options["log_email"]))
{
$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Email Log",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "email_log";
$function = "email_log";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
}

}

function rsvpmaker_mailpoet_notice() {
	$screen = get_current_screen();
	if(strpos($screen->id,'mailpoet-newsletter-editor')) {
		echo '<div class="notice notice-info">';
		echo '<div><p><button id="showhide_mailpoet_shortcodes">Show RSVPMaker Shortcodes for MailPoet</button></p></div>';
		echo '<div id="rsvpmaker_mailpoet_shortcodes_notice">';
		rsvpmaker_mailpoet_shortcodes();
		echo '<div><p><button id="showhide_mailpoet_shortcodes2">Hide RSVPMaker Shortcodes for MailPoet</button></p></div>';
		echo '</div>';
		echo '</div>';
?>
<script>
jQuery(document).ready(function( $ ) {

console.log('mailpoet shortcodes button');

$('#rsvpmaker_mailpoet_shortcodes_notice').hide();
var mailpoetshow = false;
function toggleMailPoetShort()  {
		if(mailpoetshow)
		{
			$('#showhide_mailpoet_shortcodes').text('Show RSVPMaker Shortcodes for MailPoet');
			$('#rsvpmaker_mailpoet_shortcodes_notice').hide();
			mailpoetshow = false;
		}
		else {
			$('#showhide_mailpoet_shortcodes').text('Hide RSVPMaker Shortcodes for MailPoet');
			$('#rsvpmaker_mailpoet_shortcodes_notice').show();
			mailpoetshow = true;
		}
}

$('#showhide_mailpoet_shortcodes').click( function() {
	toggleMailPoetShort();
}
);

$('#showhide_mailpoet_shortcodes2').click( function() {
	toggleMailPoetShort();
}
);

});
</script>
<?php
	} 
}

add_action('admin_notices','rsvpmaker_mailpoet_notice');

function rsvpmaker_mailpoet_shortcodes() {
?>

<p>You can use standard <a href="https://rsvpmaker.com/knowledge-base/shortcodes/" target="_blank">RSVPMaker shortcodes</a> with a custom:prefix. For rsvpmaker_upcoming, rsvpmaker_next, and rsvpmaker_one, you can include a formatting attribtue, such as [custom:rsvpmaker_next format="compact"]<br />
Useful formatting codes for email ("excerpt" works well in most cases):
<br />format="excerpt" - shows the first few paragraphs, or all the content up to the more tag (if included), plus a link to read more and the RSVP button if active.
<br />format="compact" - just the headline, date and button (if RSVPs active).
<br />format="button_only" - embeds just the RSVP button
<br />format="embed_dateblock" - embeds just the date and time block
</p>
<textarea rows="10" style="width:80%;">
[custom:rsvpmaker_upcoming hideauthor="1" limit="5" days="14"] list upcoming events
[custom:event_listing show_time="1" title="Upcoming Events"] links with dates and titles of upcoming events
[custom:rsvpmaker_next format="excerpt"] next event
[custom:rsvpmaker_next rsvp_on="1" format="excerpt"] next event with RSVPs active
[custom:rsvpmaker_youtube url="YOUTUBE URL" link="LINK IF DIFFERENT"] display preview image of a youtube video, with to view
<?php
	$events = get_future_events(array('limit' => 20));
	foreach($events as $event) {
		printf('[custom:rsvpmaker_one post_id="%d" format="excerpt"] %s %s'."\n",$event->ID,$event->post_title,$event->date);
	}
echo '</textarea>';
}

function email_log () {
global $wpdb;
$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_rsvpmaker_email_log' ORDER BY meta_id DESC LIMIT 0, 100";
$results = $wpdb->get_results($sql);
if($results)
foreach($results as $row)
	{
		$mail = unserialize($row->meta_value);
		if(is_array($mail))
		foreach($mail as $index => $value)
			printf('<p><strong>%s</strong></p><div>%s</div>',$index,$value);
	}
}

function unsubscribed_list () {
global $wpdb;
$table = $wpdb->prefix . "rsvpmailer_blocked";
$action = admin_url('edit.php?post_type=rsvpemail&page=unsubscribed_list');
if(isset($_POST['remove']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {
	foreach($_POST['remove'] as $email) {
		rsvpmail_remove_problem($email);
	}
}

if(isset($_POST['problems']))
{
	$code = sanitize_text_field($_POST['code']);
	preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $_POST['problems'], $emails);
	$emails = $emails[0];
	foreach($emails as $email)
		{
			rsvpmail_add_problem($email,$code);
			$email = strtolower($email);
		}
}

rsvpmaker_admin_heading(__('Unsubscribed and Blocked','rsvpmaker'),__FUNCTION__);

printf('<p>%s</p>',__('If recipients have clicked unsubscribe on a confirmation message or any other message sent directly from RSVPMaker (as opposed to via MailChimp) they will be listed here. You can also track messages that are being blocked by the recipient\'s ISP (not currently automated). You can manually remove emails from this list, but should only do so <strong><em>at the request of the recipient</em></strong>.','rsvpmaker'));
$sql = "SELECT * FROM $table ORDER BY code, timestamp DESC";
$results = $wpdb->get_results($sql);
if(!empty($results))
{
printf('<form method="post" action="%s"><table><tr><th>Unblock</th><th>Email</th><th>Issue</th></tr>',$action);
foreach($results as $row)
{
	printf('<tr><td><input type="checkbox" name="remove[]" value="%s" /></td><td>%s</td><td>%s</td></tr>',$row->email,$row->email,$row->code);	
}
echo '</table><p><input type="submit" value="Submit"></p>'.rsvpmaker_nonce('return').'</form>';
}

printf('<h2>Add an Email Addresses as Unsubscribed Or Blocked</h2><form method="post" action="%s">
<p>
<textarea rows="5" cols="60" name="problems"></textarea>
<br><em>Separated by spaces of on separate lines</em>
</p>%s
<p>
<input type="radio" name="code" value="unsubscribed" checked="checked"> Unsubscribed
<input type="radio" name="code" value="blocked"> Blocked
<button>Add</button></form>',$action,rsvpmaker_nonce('return'));

}


function RSVPMaker_chimpshort($atts, $content = NULL ) {

$atts = shortcode_atts( array(
  'query' => 'post_type=post&posts_per_page=5',
  'format' => '',
  ), $atts );

	ob_start();
	query_posts($atts["query"]);

if ( have_posts() ) {
while ( have_posts() ) : the_post(); ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<h3 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<?php
if(isset($atts["format"]) && ($atts["format"] == 'excerpt'))
	{
; ?>
<div class="excerpt-content">

<?php the_excerpt(); ?>

</div><!-- .excerpt-content -->
<?php	
	}
elseif(isset($atts["format"]) && ($atts["format"] == 'full'))
	{
; ?>
<div class="entry-content">

<?php the_content(); ?>

</div><!-- .entry-content -->
<?php
}
?>
</div>
<?php 
endwhile;
wp_reset_query();
} 
	
	$content = ob_get_clean();

	return $content;
}

function email_get_content () {
global $wpdb;
;?>
<?php rsvpmaker_admin_heading('Content for Email',__FUNCTION__); 

$candidates = rsvpmail_candidate_templates();
$options = '';
foreach($candidates as $candidate) {
	$options .= sprintf('<option value="%d">%s %s</option>',$candidate->ID, $candidate->post_title, $candidate->post_modified);
}
$candidates = rsvpmail_candidate_templates(true);
$alt = '';
foreach($candidates as $candidate) {
	$alt .= sprintf('<option value="%d">%s %s</option>',$candidate->ID, $candidate->post_title, $candidate->post_modified);
}

$options = '<optgroup label="Templates">'.$alt.'</optgroup><optgroup label="Previous Emails">'.$options.'</optgroup>';

echo '<h2>'.esc_html('Create Based on Template or Previous Message','rsvpmaker').'</h2>';

printf('<form id="alt_template" name="alt_template" method="get" action="%s"><input type="hidden" name="post_type" value="rsvpemail"><select name="template">%s</select></p><p><button>%s</button></p>',admin_url('post-new.php'),$options,__('Load Content','rsvpmaker'));

$event_options = $options = '<option value="">'.__('None selected','rsvpmaker').'</option>';
$event_options .= '<option value="upcoming">'.__('Upcoming Events','rsvpmaker').'</option>';
$posts = '';
$future = get_future_events();
if(is_array($future))
foreach($future as $event)
	{
	$event_options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',rsvpmaker_strtotime($event->datetime)));
	}


$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC LIMIT 0, 50";
$wpdb->show_errors();
$results = $wpdb->get_results($sql, ARRAY_A);
if($results)
{
foreach ($results as $row)
	{
	$posts .= sprintf("<option value=\"%d\">%s</option>\n",$row["ID"],substr($row["post_title"],0,80));
	}
$posts = '<optgroup label="'.__('Recent Posts','rsvpmaker').'">'.$posts."</optgroup>\n";
}

$po = '';
$pages = get_pages();
foreach($pages as $page)
	$po .= sprintf("<option value=\"%d\">%s</option>\n",$page->ID,substr($page->post_title,0,80));
?>

<form action="<?php echo admin_url('edit.php?post_type=rsvpemail'); ?>" method="get">
<?php rsvpmaker_nonce(); ?>
<h2><?php esc_html_e('Email Based on Event','rsvpmaker');?></h2><p><select name="rsvpevent_to_email"><?php echo $event_options; ?></select>
</select>
</p>
<button><?php esc_html_e('Load Content','rsvpmaker');?></button>
</form>	
<form action="<?php echo admin_url('edit.php?post_type=rsvpemail'); ?>" method="get">
<?php rsvpmaker_nonce(); ?>
<h2><?php esc_html_e('Email Based on Post','rsvpmaker');?></h2><p><select name="post_to_email"><?php echo $posts; ?></select>
</select>
<br /><input type="radio" name="excerpt" value="0" checked="checked"> <?php esc_html_e('Full text','rsvpmaker');?> <input type="radio" name="excerpt" value="1"> <?php esc_html_e('Excerpt','rsvpmaker');?>
</p>
<button><?php esc_html_e('Load Content','rsvpmaker');?></button>
</form>	
<form action="<?php echo admin_url('edit.php?post_type=rsvpemail'); ?>" method="get">
<?php rsvpmaker_nonce(); ?>
<h2><?php esc_html_e('Email Based on Page','rsvpmaker');?></h2>
<p><select name="post_to_email"><?php echo $po; ?></select>
</select>
<br /><input type="radio" name="excerpt" value="0" checked="checked"> <?php esc_html_e('Full text','rsvpmaker');?> <input type="radio" name="excerpt" value="1"> <?php esc_html_e('Excerpt','rsvpmaker');?>
</p>
<button><?php esc_html_e('Load Content','rsvpmaker');?></button>
</form>	

<h2>Shortcodes for MailPoet</h2>
<p>If you use the MailPoet integration, you can include a variations on the <a href="https://rsvpmaker.com/knowledge-base/shortcodes/" target="_blank">RSVPMaker Shortcodes</a> that include the custom: prefix.</p>

<?php

rsvpmaker_mailpoet_shortcodes();

} // end chimp get content

function rsvpmaker_email_list_okay ($rsvp) {
		$mergevars["FNAME"] = stripslashes($rsvp["first"]);
		$mergevars["LNAME"] = stripslashes($rsvp["last"]);
		RSVPMaker_Chimp_Add($rsvp["email"],$mergevars);
		mailpoet_email_list_okay($rsvp);
		rsvpmaker_guest_list_add($rsvp['email'],$rsvp['first'],$rsvp['last'],'rsvp_form_signup',0);
}

function get_rsvpmaker_email_template() {
global $rsvpmail_templates;
//$templates = get_option('rsvpmaker_email_template');

$templates[0]['slug'] = 'default';
$templates[0]['html'] = '<html>
<head>
<title>*|MC:SUBJECT|*</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
#background {background-color: #FFFFFF; padding: 10px; margin-top: 0; max-width: 800px;}
#content {padding: 5px; background-color: #FFFFFF; margin-left: auto; margin-right: auto; margin-top: 10px; margin-bottom: 10px; padding-bottom: 50px;}
</style>
</head>
<body>
<div style="display: none">[rsvpmailer_preview]</div>
<div id="background">
<div id="content">

<div style="font-size: small; border: thin dotted #999;">Email not displaying correctly? <a href="*|ARCHIVE|*" class="adminText">View it in your browser.</a></div>

[rsvpmaker_email_content]

</div><!-- end content area -->
</div><!-- end background -->

<div id="messagefooter">
*|LIST:DESCRIPTION|*<br>
<br>
<a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
<br>
<strong>Our mailing address is:</strong><br>
*|LIST:ADDRESS|*<br>
<em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    
*|REWARDS|*</div>
</body>
</html>';
$templates[1]['slug'] = 'transactional';
$templates[1]['html'] = '<html>
<head>
<title>*|MC:SUBJECT|*</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<div style="display: none">[rsvpmailer_preview]</div>
<div id="tx-background">
<div id="tx-content">

[rsvpmaker_email_content]

<div id="messagefooter">
*|LIST:DESCRIPTION|*<br>
<br>
<a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
<br>
<strong>Our mailing address is:</strong><br>
*|LIST:ADDRESS|*<br>
<em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    
*|REWARDS|*</div>

</div><!-- end content area -->
</div><!-- end background -->
</body>
</html>';

$styles = rsvpmaker_included_styles();
foreach($templates as $index => $template)
{
	$html = $template['html'];
	$html = add_style_to_email_html($html);
	$templates[$index]['html'] = $html;
}
$rsvpmail_templates = $templates;	
return $templates;
}

function add_style_to_email_html($html) {
	$styles = rsvpmaker_included_styles();
	if(strpos($html,'<style'))
		$html = preg_replace('/<styl.+>/','<style type="text/css">'."\n".$styles."\n",$html);
	else
		$html = str_replace('</head>',"<style>\n".$styles."\n</style></head>",$html);
	return $html;
}

function rsvpmaker_tx_email($event_post, $mail) {

//used with rsvpmaker_email_content shortcode in template
global $rsvpmaker_tx_content;
$rsvpmaker_tx_content = rsvpmailer_default_block_template_wrapper($mail["html"],true);
$rsvpmaker_tx_content = rsvpmail_filter_style($rsvpmaker_tx_content);
$rsvpfooter_text = '

==============================================
*|LIST:DESCRIPTION|*

Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*
';

$mail["html"] = rsvpmaker_email_html($mail['html']);
$mail['text'] = rsvpmaker_text_version($mail["html"], $rsvpfooter_text);

$problem = rsvpmail_is_problem($mail["to"]);
	if($problem)
		{
			rsvpemail_error_log('rsvpmailer blocked sending to email: '.$problem,$mail);
			return;
		}
	rsvpmailer($mail,__('<div class="rsvpexplain">This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$event_post->post_title.'</div>' );
}

function rsvpmaker_email_content ($atts, $content) {
global $wp_filter;
global $post;
global $templatefooter;
$templatefooter = isset($atts["templatefooter"]);
global $rsvpmaker_tx_content;
if(!empty($rsvpmaker_tx_content))
	return $rsvpmaker_tx_content;
if(function_exists('bp_set_theme_compat_active'))
bp_set_theme_compat_active( false );//stop buddypress from causing trouble

ob_start();
$corefilters = array('convert_chars','wpautop','wptexturize','event_content');
foreach($wp_filter["the_content"] as $priority => $filters)
	foreach($filters as $name => $details)
		{
		//keep only core text processing or shortcode
		if(!in_array($name,$corefilters) && !strpos($name,'hortcode'))
			{
			if(isset($_GET["debug"]))
				echo '<br />Remove '.$name.' '.$priority;
			$r = remove_filter( 'the_content', $name, $priority );
			}
		}
if(isset($_GET["debug"])) {
	echo '<pre>';
	//print_r($wp_filter);
	echo '</pre>';
}

global $rsvp_options;

?>
<!-- editors note goes here -->
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php if(get_post_meta($post->ID,"_email_headline",true)) { ; ?>
<h1 class="entry-title"><?php the_title(); ?></h1>
<?php } ; ?>
<div class="entry-content">
<?php echo wp_kses_post($post->post_content); ?>
</div><!-- .entry-content -->
</div><!-- #post-## -->
<div class="footer"><!-- footer --></div>
<?php 
$content = ob_get_clean();
$content = rsvpmaker_email_html($content);
return $content;
}

function mailchimp_list_dropdown($apikey, $chosen = '') {
if(empty($apikey))
	return '<option value="">none</option>';
try {
    $MailChimp = new MailChimpRSVP($apikey);
} catch (Exception $e) {
    return '<option value="">none '.$e->getMessage().'</option>';
}

$retval = $MailChimp->get('lists');

$options = '';
if (is_array($retval)){
	foreach ($retval["lists"] as $list){
		$s = ($chosen == $list['id']) ? ' selected="selected" ' : '';
		$options .=  '<option value= "'.esc_attr($list['id']).'"'. " $s >".esc_html($list['name']).'</option>';
	}
}
return $options;
}

function event_to_embed($post_id, $event_post = NULL, $context = '') {
		global $email_context;
		global $rsvp_options;
		global $post;
		$backup = $post;
		$email_context = true;
		if(empty($event_post))
			$event_post = get_post($post_id);
		$event_embed["subject"] = $event_post->post_title;
		$event_embed["content"] = sprintf('<!-- wp:heading -->
<h2 class="email_event"><a href="%s">%s</a></h2>
<!-- /wp:heading -->'."\n",get_permalink($post_id),apply_filters('the_title',$event_post->post_title));
		if($event_post->post_type == 'rsvpmaker')
		{
		$date_array = rsvp_date_block($post_id);
		$dateblock = trim(strip_tags($date_array["dateblock"]));
		$dur = $date_array["dur"];
		$last_time = $date_array["last_time"];
		$tmlogin = (strpos($event_post->post_content,'[toastmaster')) ? sprintf('<!-- wp:paragraph -->
<p><a href="%s">Login</a> to sign up for roles</p>
<!-- /wp:paragraph -->',wp_login_url( get_post_permalink( $post_id ) ) ) : '';
		$event_embed["content"] .= sprintf('<!-- wp:paragraph -->
<p><strong>%s</strong></p>
<!-- /wp:paragraph -->',$dateblock).$tmlogin;			
		}
		$event_embed["content"] .= rsvpmaker_email_html($event_post->post_content);
		if(get_rsvpmaker_meta($post_id,'_rsvp_on',true))
		{
		if(get_post_meta($post_id,'_rsvp_count',true))
			$event_embed["content"] .= rsvpcount($post_id);
		if($context != 'confirmation')
			{ // add the rsvp button / link except in confirmation messages that include Update RSVP version
				$rsvplink = get_rsvp_link($post_id);
				$event_embed["content"] .= "<!-- wp:paragraph -->\n".$rsvplink."\n<!-- /wp:paragraph -->";		
			}
		}
		$post = $backup;
		if(function_exists('do_blocks')){
			$event_embed["content"] = rsvpmaker_email_html($event_embed["content"]);			
		}
		else 
		$event_embed["content"] = wpautop($event_embed["content"]);
		$post = $backup;
		return $event_embed;
}

function rsvpmaker_upcoming_email($atts) {
	$output = '';
	$weeks = (empty($atts["weeks"])) ? 4 : $atts["weeks"];
	$end = date('Y-m-d',rsvpmaker_strtotime('+'.$weeks.' weeks')). ' 23:59:59';
	$upcoming = get_future_events(' a1.meta_value < "'.$end.'"');
	if(is_array($upcoming))
	foreach($upcoming as $embed)
		{
		$event = event_to_embed($embed->ID,$embed);
		$output .= $event["content"]."\n\n";
		}
	if(isset($atts["looking_ahead"]))
		{
			$weeksmore = $atts["looking_ahead"];
			$label = (empty($atts["looking_ahead_label"])) ? '<h2>Looking Ahead</h2>' : '<h2 class="looking_ahead">'.$atts["looking_ahead_label"].'</h2>';
			$extra = date('Y-m-d',rsvpmaker_strtotime($end .' +'.$weeksmore.' weeks')). ' 23:59:59';
			$upcoming = get_future_events(' a1.meta_value > "'.$end .'" AND  a1.meta_value < "'.$extra.'"');
			if(is_array($upcoming))
				{
					$output .= $label."\n";
					foreach($upcoming as $ahead)
						$output .= sprintf('<p><a href="%s">%s - %s</a></p>',get_permalink($ahead->ID),$ahead->post_title,date('F j',rsvpmaker_strtotime($ahead->datetime)));
				}
		}	
	return $output;
}

function is_email_context () {
		global $email_context;
		return (isset($email_context) && $email_context);
}

add_shortcode('rsvpmaker_cron_email_send_test','rsvpmaker_cron_email_send_test');
function rsvpmaker_cron_email_send_test() {
	return 'send test'.date('r') . rsvpmaker_cron_email_send(123824);
}

function rsvpmaker_cron_email_send($post_id) {
	global $rsvpmaker_cron_context;
	$rsvpmaker_cron_context = 2; // 1 means preview
	$user_id = get_post_meta($post_id,'scheduled_send_user',true);
	if(!$user_id) {
		$post = get_post($post_id);
		$user_id = $post->post_author;	
	}
	rsvpmailer_delayed_send($post_id,$user_id);
}

function rsvpmaker_cron_email_preview($args) {
global $rsvpmaker_cron_context;
global $wp_query;
$rsvpmaker_cron_context = 1; // 1 means preview
$user_id = get_post_meta($post_id,'scheduled_send_user',true);
if(!$user_id) {
	$post = get_post($post_id);
	$user_id = $post->post_author;	
}
rsvpmailer_delayed_send($post_id,$user_id);
}

function rsvpmaker_cron_email_preview_now() {
	if(isset($_GET['cronemailpreview']))
	{
		rsvpmaker_cron_email_preview(sanitize_text_field($_GET['cronemailpreview']));
		die('scheduled email preview');
	}
}

add_filter( 'post_row_actions', 'rsvpmaker_row_actions', 10, 2 );
function rsvpmaker_row_actions( $actions, WP_Post $post ) {
	global $current_user;
    if ($post->post_type == 'rsvpemail') {
        return $actions;
    }
	if($post->post_type == 'rsvpmaker_template') {
		$actions['rsvpmaker_options'] = sprintf('<a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=').$post->ID,__('Create / Update','rsvpmaker'));
	}

	if(current_user_can('edit_post',$post->ID))
	{
		if($post->post_type == 'rsvpmaker') {
			$actions['rsvpmaker_options'] = sprintf('<a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=').$post->ID,__('Event Options','rsvpmaker'));
			$actions['rsvpmaker_invite2'] = sprintf('<a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpemail&rsvpevent_to_email=').$post->ID,__('Embed in RSVP Email','rsvpmaker'));	
			}
		$actions['rsvpmaker_invite'] = sprintf('<a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpemail&post_to_email=').$post->ID,__('Copy to RSVP Email','rsvpmaker'));
	}
	else {
	if($post->post_type == 'rsvpmaker')
	{
		$eds = get_additional_editors($post->ID);
		if(!empty($eds) && in_array($current_user->ID,$eds))
			$actions['edit_override'] = sprintf('<a href="%s">%s</a>',admin_url('post.php?action=edit&post=').$post->ID,__('Edit','rsvpmaker'));
	}
	}
return $actions;
}

//based on Austin Matzko's code from wp-hackers email list
function filter_where_recent($where = '') {
global $blog_weeks_ago;

if(0 == (int) $blog_weeks_ago)
	$blog_weeks_ago = 1;
	$week_ago_stamp = rsvpmaker_strtotime('-'.$blog_weeks_ago.' week');
	$week_ago = date('Y-m-d H:i:s',$week_ago_stamp);
    $where .= " AND post_date > '" . $week_ago . "'";
    return $where;
}

function get_rsvp_notekey($epost_id = 0) {
	global $post, $rsvpmaker_cron_context;
	if(!$epost_id && isset($post->ID))
		$epost_id = $post->ID;
	
	if(!empty($rsvpmaker_cron_context) && $rsvpmaker_cron_context == 2)
	{
		$notekey = 'editnote'.rsvpmaker_date('Y-m-d',time()); // live not preview broadcast or editing
	}
	else {
		$stamp = rsvpmaker_next_scheduled($epost_id, true);
		//$stamp = preg_replace('/M [a-z]+$/','M',$stamp);
		$notekey = 'editnote'.rsvpmaker_date('Y-m-d',$stamp);//date('YmdH',rsvpmaker_strtotime($stamp));
	}
	return $notekey;
}

function rsvpmaker_recent_blog_posts ($atts) {
global $wp_query;
global $post;
$backup = $wp_query;
$was = $post;
global $blog_weeks_ago;
$blog_weeks_ago = (!empty($atts["weeks"])) ? $atts["weeks"] : 1;

$ts = rsvpmaker_next_scheduled($post->ID);
$cron = get_post_meta($post->ID,'rsvpmaker_cron_email',true);
$notekey = get_rsvp_notekey();
$chosen = (int) get_post_meta($post->ID,$notekey,true);

add_filter('posts_where', 'filter_where_recent');
query_posts('post_type=post');
if (have_posts()) :
while (have_posts()) : the_post(); 
if($post->ID == $chosen)
	{
	continue;
	}
if($post->comment_count)
	$c = sprintf(" (%d comments)",$post->comment_count);
else
	$c = "";
$output .= '<h4><a href="'. get_permalink() .'" rel="bookmark">'. get_the_title() .'</a> By '. get_the_author() . $c . "</h4>\n<p>".get_the_excerpt()."</p>\n";
 endwhile;
endif;
remove_filter('posts_where', 'filter_where_recent');
if(!empty($output))
	$output = '<h3>'.__('From the Blog','rsvpmaker')."</h3>\n".$output;
$wp_query = $backup;
$post = $was;
return $output;
}

function rsvpmaker_cron_active ($cron_active,$cron){
if(empty($cron["cron_condition"]) || ($cron["cron_condition"] == 'none'))
	return $cron_active;
if(! $cron_active)
	return $cron_active;
if($cron["cron_condition"] == 'events')
	{
	if(!empty($_GET["cron_filter_debug"]))
	echo "<p>test:".$cron["cron_condition"]."</p>";
	return count_future_events();
	}
elseif($cron["cron_condition"] == 'posts')
	{
	if(!empty($_GET["cron_filter_debug"]))
	echo "<p>test:".$cron["cron_condition"]."</p>";
	return count_recent_posts();
	}
elseif($cron["cron_condition"] == 'and')
	{
	if(!empty($_GET["cron_filter_debug"]))
	echo "<p>test:".$cron["cron_condition"]."</p>";
	return (count_recent_posts() && count_future_events()) ? 1 : 0;
	}
elseif($cron["cron_condition"] == 'or')
	{
	if(!empty($_GET["cron_filter_debug"]))
	echo "<p>test:".$cron["cron_condition"]."</p>";
	return (count_recent_posts() || count_future_events()) ? 1 : 0;
	}
return $cron_active;
}
add_filter('rsvpmaker_cron_active','rsvpmaker_cron_active',5,2);

function rsvpmail_unsubscribe () {
if(!isset($_REQUEST['rsvpmail_unsubscribe']))
	return;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php bloginfo( 'name' ); echo ' - '.__('Email Unsubscribe'); ?></title>
<style>
body {background-color: #000;}
#main {background-color: #FFF; max-width: 600px; margin-left: auto; margin-right: auto; margin-top: 25px; padding: 25px;}
h1 {font-size: 20px;}
</style>
</head>
<body>
<div id="main">
<h1><?php bloginfo( 'name' ); echo ' - '.__('Email Unsubscribe'); ?></h1>
<?php
if(isset($_POST['rsvpmail_unsubscribe']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
{
$e = sanitize_text_field(strtolower(trim($_POST['rsvpmail_unsubscribe'])));
if(!is_email($e))
	echo 'Error: invalid email address';
else
	{
	rsvpmail_add_problem($e,'unsubscribed');
	echo '<p>'.__('Unsubscribed from website email lists','rsvpmaker').'</p>';
	$msg = 'RSVPMaker unsubscribe: '.$e;
	$chimp_options = get_option('chimp');
	if(!empty($chimp_options) && !empty($chimp_options["chimp-key"]))
	{
	$apikey = $chimp_options["chimp-key"];
	$listId = $chimp_options["chimp-list"];
	$MailChimp = new MailChimpRSVP($apikey);
	$result = $MailChimp->patch("lists/$listId/members/".md5(strtolower($e)), array(
				'status' => 'unsubscribed'));
	if($MailChimp->success())
		{
		echo '<p>'.__('Unsubscribed from MailChimp email list','rsvpmaker').': '.esc_html($listId).'</p>';
		$msg .= "\n\nRemoved from MailChimp list";
		}
	else
		{
		echo '<p>'.__('Error attempting to unsubscribe from MailChimp email list','rsvpmaker').': '.esc_html($listId).'</p>';	
		$msg .= "\n\nMailChimp unsubscribe error";
		}
	}

	wp_mail(get_option('admin_email'), $e.' '.__('unsubscribed','rsvpmaker').': '.get_option('blogname').' (RSVPMaker)',$msg);

	do_action('rsvpmail_unsubscribe',$e);
	}
}
if(isset($_GET['rsvpmail_unsubscribe']))
{
$e = sanitize_text_field(trim($_GET['rsvpmail_unsubscribe']));
?>
<form method="post" action="<?php echo site_url(); ?>">
<?php rsvpmaker_nonce(); ?>
<input type="text" name="rsvpmail_unsubscribe" value="<?php echo esc_attr($e); ?>">
<button><?php esc_html_e('Unsubscribe','rsvpmaker'); ?></button>
</form>
<?php
}

printf('<p>%s <a href="%s">%s</a></p>',__('Continue to','rsvpmaker'),site_url(),site_url());

?>
</div>
</body>
</html>
<?php
exit();
}

add_action('init','rsvpmail_unsubscribe');

function rsvpmail_confirm_subscribe () {
	if(!isset($_REQUEST['rsvpmail_subscribe']))
		return;
	?>
	<!doctype html>
	<html>
	<head>
	<meta charset="utf-8">
	<title><?php bloginfo( 'name' ); echo ' - '.__('Email Unsubscribe'); ?></title>
	<style>
	body {background-color: #000;}
	#main {background-color: #FFF; max-width: 600px; margin-left: auto; margin-right: auto; margin-top: 25px; padding: 25px;}
	h1 {font-size: 20px;}
	</style>
	</head>
	<body>
	<div id="main">
	<h1><?php bloginfo( 'name' ); echo ' - '.__('Confirm Email List Subscription'); ?></h1>
	<?php
	$e = sanitize_text_field(strtolower(trim($_REQUEST['rsvpmail_subscribe'])));
	if(!is_email($e))
		echo 'Error: invalid email address';
	else
		{
		rsvpmail_confirm_email($e);
		echo str_replace('*|EMAIL|*',$e,get_option('rsvpmailer_list_confirmation_message'));
		rsvpmaker_guest_email_welcome($e);
		echo '<p>'.__('Confirmed subscription to website email list for ','rsvpmaker').$e.'</p>';
		do_action('rsvpmail_subscribe',$e);
		}
	
	printf('<p>%s <a href="%s">%s</a></p>',__('Continue to','rsvpmaker'),site_url(),site_url());
	
	?>
	</div>
	</body>
	</html>
	<?php
exit();
}
	
add_action('init','rsvpmail_confirm_subscribe');

function rsvpmaker_notification_templates () {

rsvpmaker_admin_heading(__('Notification Templates','rsvpmaker'),__FUNCTION__);
echo '<p>'.__('Use this form to customize notification and confirmation messages and the information to be included in them. Template placeholders such as [rsvpdetails] are documented at the bottom of the page.').'</p>';

if ( isset( $_POST['ntemp'] ) && rsvpmaker_verify_nonce() ) {
	$output = '<h2>' . __( 'Updated', 'rsvpmaker' ) . '</h2>';
	$ntemp = $_POST['ntemp'];
	foreach($ntemp as $index => $data) {
		$ntemp[$index]['subject'] = sanitize_text_field($ntemp[$index]['subject']);
		$ntemp[$index]['body'] = wp_kses_post($ntemp[$index]['body']);
	}
	if ( ! empty( $_POST['newtemplate']['subject'] ) && ! empty( $_POST['newtemplate_label'] ) ) {
		$index = sanitize_text_field($_POST['newtemplate_label']);
		$ntemp[ $index ]['subject'] = sanitize_text_field( $_POST['newtemplate']['subject'] );
		$ntemp[ $index ]['body']    = wp_kses_post( $_POST['newtemplate']['body'] );
	}
	update_option( 'rsvpmaker_notification_templates', stripslashes_deep( $ntemp ) );
	$output .= sprintf( '<p><a href="%s">%s</a></p>', admin_url( 'edit.php?post_type=rsvpemail&page=rsvpmaker_notification_templates' ), __( 'Edit', 'rsvpmaker' ) );
}

$sample_data = array('rsvpdetails' => "first: John\nlast: Smith\nemail:js@example.com",'rsvpyesno' => __('YES','rsvpmaker'), 'rsvptitle' => 'Special Event', 'rsvpdate' => 'January 1, 2020','rsvpmessage' => 'Thank you!', 'rsvpupdate' => '<p><a style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;" class="rsvplink" href="%s">'. __('RSVP Update','rsvpmaker').'</a></p>');
$sample_data = apply_filters('rsvpmaker_notification_sample_data',$sample_data);
$template_forms = get_rsvpmaker_notification_templates ();
printf('<form id="rsvpmaker_notification_templates" action="%s" method="post">',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_notification_templates'));
rsvpmaker_nonce();
foreach($template_forms as $slug => $form)
	{
	if(!is_array($form))
		continue;
	echo '<div style="border: thin dotted #555; margin-bottom: 5px;">';
	printf('<h2>%s</h2>',ucfirst(str_replace('_',' ',$slug)));
	foreach($form as $field => $value)
		{
			printf('<div>%s</div>',ucfirst(str_replace('_',' ',$field)));
			if($field == 'body')
				echo '<p><textarea name="ntemp['.$slug.']['.$field.']" style="width: 90%; height: 100px;">'.esc_attr($value).'</textarea></p>';
			elseif($field == 'sample_data')
				$sample_data = $value;
			else
				echo '<p><input type="text" name="ntemp['.$slug.']['.$field.']" value="'.esc_attr($value).'" style ="width: 90%" /></p>';
		}
	if(isset($_GET[$slug]))
	{
	echo '<h3>Example</h3>';
	$example = '<p><strong>Subject: </strong>'.$form['subject']."</p>\n\n".$form['body'];
	foreach($sample_data as $field => $value)
		$example = str_replace('['.$field.']',$value,$example);
	
	$example = wpautop($example);
	echo rsvpmaker_email_html($example);
	}
	echo '</div>';//end border

	}
	printf('<h3>%s: <input type="text" name="newtemplate_label"></h3>',__('Custom Label','rsvpmaker-for-toastmasters'));
	echo '<p>Subject<br /><input type="text" name="newtemplate[subject]" value="" style ="width: 90%" /></p>';
	echo '<p>Body<br /><textarea name="newtemplate[body]" style="width: 90%; height: 100px;"></textarea></p>';

echo submit_button().'</form>';

printf('<p><a href="%s">Reset to defaults</a></p>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_notification_templates&reset=1'));
echo   '<p>'.__("RSVPMaker template placeholders:<br />[rsvpyesno] YES/NO<br />[rsvptitle] event post title<br />[rsvpdate] event date<br />[datetime] event date and time<br />[rsvpmessage] the message you supplied when you created/edited the event (default is Thank you!)<br />[rsvpdetails] information supplied by attendee<br />[rsvpupdate] button users can click on to update their RSVP<br />[rsvpcount] number of people registered<br />[event_title_link] a link to the event, with the event title and date/time",'rsvpmaker').'</p>';
echo '<p>[rsvpmessage] and [rsvpdetails] should only be used in a notification template. Other codes can be used in the body of a confirmation message or the subject line of a reminder.</p>';
do_action('rsvpmaker_notification_templates_doc');
rsvpmaker_admin_page_bottom($hook);
}

function get_rsvpmaker_notification_templates () {
global $email_context;
$email_context = true;
$templates = get_option('rsvpmaker_notification_templates');
//$template_forms represents the defaults
$template_forms['notification'] = array('subject' => 'RSVP [rsvpyesno] for [rsvptitle] on [rsvpdate]','body' => "Just signed up:\n\n<div class=\"rsvpdetails\">[rsvpdetails]</div>");
$template_forms['confirmation'] = array('subject' => 'Confirming RSVP [rsvpyesno] for [rsvptitle] on [rsvpdate]','body' => "<div class=\"rsvpmessage\">[rsvpmessage]</div>\n\n<div class=\"rsvpdetails\">[rsvpdetails]</div>\n\nIf you wish to change your registration, you can do so using the button below. [rsvpupdate]");
$template_forms['confirmation_after_payment'] = array('subject' => 'Confirming payment for [rsvptitle] on [rsvpdate]','body' => "<div class=\"rsvpmessage\">[rsvpmessage]</div>\n\n<div class=\"rsvpdetails\">[rsvpdetails]</div>\n\nIf you wish to change your registration, you can do so using the button below. [rsvpupdate]");
$template_forms['payment_reminder'] = array('subject' => 'Payment Required: [rsvptitle] on [rsvpdate]','body' => "We received your registration, but it is not complete without a payment. Please follow the link below to complete your registration and payment.

[rsvpupdate]

<div class=\"rsvpdetails\">[rsvpdetails]<div>");
if(isset($_GET['reset']))
	{

	}

$template_forms = apply_filters('rsvpmaker_notification_template_forms',$template_forms);
if(empty($templates))
	return $template_forms;
if(isset($_GET['reset']))
	{
		$templates = $template_forms;
		update_option('rsvpmaker_notification_templates',$templates);
	}
else {
	//fill in the blanks
	foreach($template_forms as $slug => $form)
	{
	foreach($form as $field => $value)
		{
			if(empty($templates[$slug][$field]))
				$templates[$slug][$field] = $template_forms[$slug][$field];
		}
	}
}
return $templates;
}

function rsvpcount ($atts) {
global $wpdb;
global $post;
if(isset($atts['post_id']))
	$post_id = (int) $atts['post_id'];
elseif(!empty($atts) && is_numeric($atts))
	$post_id = $atts;
else
	$post_id = $post->ID;
	
if(!$post_id)
	return;
$sql = "SELECT count(*) FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post_id AND yesno=1 ORDER BY id DESC";
$total = (int) $wpdb->get_var($sql);
$rsvp_max = get_post_meta($post_id,'_rsvp_max',true);
$output = $total.' '.__('signed up so far.','rsvpmaker');
if($rsvp_max)
	$output .= ' '.__('Limit','rsvpmaker').': '.$rsvp_max;
return '<p class="signed_up">'.$output.'</p>';
}

function rsvp_notifications_via_template ($rsvp,$rsvp_to,$rsvpdata) {
global $post;
global $rsvp_options;
include_once 'rsvpmaker-ical.php';

$templates = get_rsvpmaker_notification_templates();

$notification_subject = $templates['notification']['subject']; 
foreach($rsvpdata as $field => $value)
	$notification_subject = str_replace('['.$field.']',$value,$notification_subject);

$notification_body = $templates['notification']['body']; 
foreach($rsvpdata as $field => $value)
	$notification_body = str_replace('['.$field.']',$value,$notification_body);
	$notification_body = rsvpmaker_email_html($notification_body);

	$rsvp_to_array = explode(",", $rsvp_to);
	$rsvp_to_array = apply_filters('rsvp_to_array',$rsvp_to_array);
	foreach($rsvp_to_array as $to)
	{
	$mail["to"] = $to;
	$mail['toname'] = get_bloginfo('name');
	$mail["from"] = $rsvp["email"];
	$mail["fromname"] = $rsvp["first"].' '.$rsvp["last"];
	$mail["subject"] = $notification_subject;
	$mail["html"] = wpautop($notification_body);
	rsvpmaker_tx_email($post, $mail);
	}

$send_confirmation = get_post_meta($post->ID,'_rsvp_rsvpmaker_send_confirmation_email',true);
$confirm_on_payment = get_post_meta($post->ID,'_rsvp_confirmation_after_payment',true);

if(($send_confirmation ||!is_numeric($send_confirmation)) && $rsvpdata['yesno'] && empty($confirm_on_payment) )//if it hasn't been set to 0, send it
{
$confirmation_subject = $templates['confirmation']['subject']; 
foreach($rsvpdata as $field => $value)
	$confirmation_subject = str_replace('['.$field.']',$value,$confirmation_subject);

$confirmation_body = $templates['confirmation']['body']; 
foreach($rsvpdata as $field => $value)
	$confirmation_body = str_replace('['.$field.']',$value,$confirmation_body);
	
	$confirmation_body = rsvpmaker_email_html($confirmation_body);	
	$mail["html"] = wpautop($confirmation_body);
	if(isset($post->ID)) // not for replay
	$mail["ical"] = rsvpmaker_to_ical_email ($post->ID, $rsvp_to, $rsvp["email"], rsvpmaker_text_version($confirmation_body));
	rsvpmaker_debug_log($mail["ical"],'ical text rsvp_notifications_via_template');
	$mail["to"] = $rsvp["email"];
	if(!empty($rsvp['first']))
		$mail['toname'] = $rsvp['first'].' '.$rsvp['last'];
	$mail["from"] = $rsvp_to_array[0];
	$mail["fromname"] = get_bloginfo('name');
	$mail["subject"] = $confirmation_subject;
	rsvpmaker_tx_email($post, $mail);	
}

}

function rsvp_payment_reminder ($rsvp_id) {
global $post;
global $rsvp_options;
global $wpdb;
$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=$rsvp_id";
$rsvp = (array) $wpdb->get_row($sql);
$post = get_post($rsvp['event']);
$rsvpdata = unserialize($rsvp['details']);
if($rsvpdata['total'] <= $rsvp['amountpaid'])
	return;
	
$details = '';
foreach($rsvpdata as $label => $value)
	$details .= sprintf('%s: %s'."\n",$label,$value);;

$templates = get_rsvpmaker_notification_templates();
$rsvp_to = get_post_meta($post->ID,'_rsvp_to',true);
$rsvp_to_array = explode(",", $rsvp_to);
$notification_subject = $templates['payment_reminder']['subject']; 
foreach($rsvpdata as $field => $value)
	$notification_subject = str_replace('['.$field.']',$value,$notification_subject);

$notification_body = $templates['payment_reminder']['body']; 
foreach($rsvpdata as $field => $value)
	$notification_body = str_replace('['.$field.']',$value,$notification_body);
$notification_body = str_replace('[rsvpdetails]',$details,$notification_body);

$url = get_permalink($rsvp['event']);
$url = add_query_arg('rsvp',$rsvp['id'],$url);
$url = add_query_arg('e',$rsvp['email'],$url);

$notification_body = str_replace('[rsvpupdate]',sprintf('<a href="%s">Complete Registration</a>',$url),$notification_body);
	
$notification_body = rsvpmaker_email_html($notification_body).'<p>after shortcode and blocks</p>';
$mail["to"] = $rsvp['email'];
if(!empty($rsvp['first']))
	$mail['toname'] = $rsvp['first'].' '.$rsvp['last'];
$mail["from"] = $rsvp_to_array[0];
$mail["fromname"] = get_bloginfo('name');
$mail["subject"] = $notification_subject;
$mail["html"] = wpautop($notification_body);
rsvpmaker_tx_email($post, $mail);
}

function rsvp_confirmation_after_payment ($rsvp_id) {
	include_once 'rsvpmaker-ical.php';
	global $post;
	global $rsvp_options;
	global $wpdb;
	$rsvp_id = intval($rsvp_id);
	$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=".intval($rsvp_id);
	$rsvp = (array) $wpdb->get_row($sql);
	$post = get_post($rsvp['event']);
	$rsvpdata = unserialize($rsvp['details']);

	$guests = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=$rsvp_id");
	if($guests) {
		foreach($guests as $guestrow) {
			$guestarr[] = $guestrow->first.' '.$guestrow->last;
		}
		$rsvpdata['guests'] = implode(', ',$guestarr);
	}

	//rsvpmaker_debug_log($rsvpdata,'rsvp_confirmation_after_payment');
		
	$details = '';
	foreach($rsvpdata as $label => $value)
		$details .= sprintf('%s: %s'."\n",$label,$value);

	$templates = get_rsvpmaker_notification_templates();
	$rsvp_to = get_post_meta($post->ID,'_rsvp_to',true);
	$rsvp_to_array = explode(",", $rsvp_to);
	$rsvpdata['rsvpmessage'] = '';
	$message_id = get_post_meta($post->ID,'_rsvp_confirm',true);
	if($message_id)
	{
	  $message_post = get_post($message_id);
	  $rsvpdata['rsvpmessage'] .= rsvpmaker_email_html($message_post->post_content)."\n\n";
	}
	$message_id = get_post_meta($post->ID,'payment_confirmation_message',true);
	if($message_id)
	{
	  $message_post = get_post($message_id);
	  $rsvpdata['rsvpmessage'] .= rsvpmaker_email_html($message_post->post_content);
	}

	$notification_subject = $templates['confirmation_after_payment']['subject'];
	foreach($rsvpdata as $field => $value)
		$notification_subject = str_replace('['.$field.']',$value,$notification_subject);
	
	$notification_body = $templates['confirmation_after_payment']['body']; 
	foreach($rsvpdata as $field => $value)
		$notification_body = str_replace('['.$field.']',$value,$notification_body);
	$notification_body = str_replace('[rsvpdetails]',$details,$notification_body);
	
	$url = get_permalink($rsvp['event']);
	$url = add_query_arg('rsvp',$rsvp['id'],$url);
	$url = add_query_arg('e',$rsvp['email'],$url);
	
	$notification_body = str_replace('[rsvpupdate]',sprintf('<a href="%s">Complete Registration</a>',$url),$notification_body);	
	$notification_body = rsvpmaker_email_html($notification_body);

	$mail["to"] = $rsvp['email'];
	if(!empty($rsvp['first']))
		$mail['toname'] = $rsvp['first'].' '.$rsvp['last'];
	$mail["from"] = $rsvp_to_array[0];
	$mail["fromname"] = get_bloginfo('name');
	$mail["ical"] = rsvpmaker_to_ical_email ($post->ID, $rsvp_to, $rsvp["email"]);
	$mail["subject"] = $notification_subject;
	$mail["html"] = $payment_confirmation_message . wpautop($notification_body);
	rsvpmaker_tx_email($post, $mail);	
}

add_action('init','rsvp_payment_reminder_test');
function rsvp_payment_reminder_test () {
	if(!isset($_GET['payrem']))
		return;
	rsvp_payment_reminder(sanitize_text_field($_GET['payrem']));
}

add_action('rsvp_payment_reminder','rsvp_payment_reminder',10,1);

function rsvpmaker_payment_reminder_cron ($rsvp_id) {
	$time = rsvpmaker_strtotime('+30 minutes');
	wp_clear_scheduled_hook( 'rsvp_payment_reminder',array($rsvp_id) );
	wp_schedule_single_event($time,'rsvp_payment_reminder',array($rsvp_id));
}

function previewtest () {
		rsvpmaker_cron_email_preview(array('post_id' => (int) $_GET['rsvpmaker_cron_email_preview']));
		die('preview end');
}

function check_mailchimp_email ($email) {
$chimp_options = get_option('chimp');
$apikey = $chimp_options["chimp-key"];
$listId = $chimp_options["chimp-list"];
$email = trim(strtolower($email));
$MailChimp = new MailChimpRSVP($apikey);	
$member = $MailChimp->get("/lists/".$listId."/members/".md5($email));
if(isset($_GET['debug']))
{
	echo '<pre>';
	//print_r($member);
	echo '</pre>';
}
if (!empty($member["id"]) && ($member["status"] == 'subscribed'))
	return $member;
else
	return false;
}

//weed out filters that don't belong in email
function email_content_minfilters() {
	global $wp_filter, $post, $email_context;
	$log = '';
		$corefilters = array('convert_chars','wpautop','wptexturize','event_content','
		wp_make_content_images_responsive');
		foreach($wp_filter["the_content"] as $priority => $filters)
			foreach($filters as $name => $details)
				{
				if(!in_array($name,$corefilters) && !strpos($name,'hortcode') && !strpos($name,'lock'))//don't mess with block/shortcode processing
					{
					$r = remove_filter( 'the_content', $name, $priority );
					}
				}	
}

function rsvpmailer_template_preview() {
	global $wpdb;
	if(isset($_GET['preview_broadcast_in_template'])) {
		$template = (int) $_GET['preview_broadcast_in_template'];
		$title = 'Demo: Broadcast Email Message';
		$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title='$title' ");
		if(!$id) {
			$postarray['post_title'] = $title;
			$postarray['post_status'] = 'publish';
			$postarray['post_type'] = 'rsvpemail';
			$postarray['post_content'] = '<!-- wp:paragraph {"dropCap":true,"fontSize":"larger"} -->
			<p class="has-drop-cap has-larger-font-size">You have a story to tell about your business, its products, and its services. The catch is the story can\'t be all about you.</p>
			<!-- /wp:paragraph -->
			
			<!-- wp:paragraph -->
			<p>Product features and service quality are important, but you are not the hero of the story. Your customers and future customers must be able to see themselves as the heroes. Your nifty product may be the hot rod spaceship that will ensure their victory, but you want them to envision themselves at the controls.</p>
			<!-- /wp:paragraph -->
			
			<!-- wp:paragraph -->
			<p>Technology companies often let their marketing get lost in the details. We help them tell stories that matter.</p>
			<!-- /wp:paragraph -->
			
			<!-- wp:paragraph -->
			<p>Our storytellers pay attention to the details, of course, and seek a deep understanding of them. But not all details are equally important. Not all details help tell a clear, convincing story.</p>
			<!-- /wp:paragraph -->
			
			<!-- wp:paragraph -->
			<p><a href="https://www.carrcommunications.com/tell-us-your-story/">Tell us your story</a>, the way you tell it today, or the story you want to take to the market. We will help you tell it better, or suggest a different story that would be more effective.</p>
			<!-- /wp:paragraph -->
			
			<!-- wp:paragraph -->
			<p>Learn how <a href="https://carrcommunications.com">Carr Communications</a> can help you tell a clear, convincing story.</p>
			<!-- /wp:paragraph -->';
			$id = wp_insert_post($postarray);
		}
		$permalink = get_permalink($id);
		wp_redirect(add_query_arg('template_preview',1,$permalink));
		exit;
	}
	if(isset($_GET['preview_confirmation_in_template'])) {
		global $rsvp_options;
		$template = (int) $_GET['preview_confirmation_in_template'];
		$title = 'RSVP YES for Demo Event Confirmation on April 1';
		$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title='$title' ");
		if(!$id || isset($_GET['reset'])) {
			$postarray['post_title'] = $title;
			$postarray['post_status'] = 'publish';
			$postarray['post_type'] = 'rsvpemail';
			$postarray['post_content'] = '<div class="rsvpmessage">
			<p>Thank you! [your confirmation message here]</p>
			</div>
			<div class="rsvpdetails">First Name: David F.<br>
			Last Name: Carr<br>
			Email: david@carrcommunications.com<br>
			Guests: Beth Anne Carr, Theresa Carr</div>
			<p><em>If you wish to change your registration, you can do so using the button below. </em></p>
			<p><a class="rsvplink" href="https://dev.local/rsvpmaker/gallery-talk-edouard-manet/?e=*EMAIL*#rsvpnow" style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;">'.$rsvp_options['update_rsvp'].'</a></p>';
			if( isset($_GET['reset']) )
			{
				$postarray["ID"] = $id;
				wp_update_post($postarray);
			}
			else
				$id = wp_insert_post($postarray);
		}
		update_post_meta($id,'_email_template',$template);
		$permalink = get_permalink($id);
		wp_redirect(add_query_arg('template_preview',1,$permalink));
		exit;
	}
}

function event_title_link () {
	global $post, $rsvp_options;
	$time_format = $rsvp_options["time_format"];
	$add_timezone = get_rsvpmaker_meta($post->ID,'_add_timezone',true);	
	if(!strpos($time_format,'T') && $add_timezone )
		{
		$time_format .= ' T';
		}
	$t = get_rsvpmaker_timestamp($post->ID);
	$display_date = utf8_encode(rsvpmaker_date($rsvp_options["long_date"].' '.$time_format,$t));
	$permalink = get_permalink($post->ID);
	return sprintf('<p class="event-title-link"><a href="%s">%s - %s</a></p>',$permalink,esc_html($post->post_title),esc_html($display_date));
}

function rsvpmaker_mailchimp_init() {
$chimp_options = get_option('chimp');
if(empty($chimp_options["chimp-key"]))
	return;
$apikey = $chimp_options["chimp-key"];
return new MailChimpRSVP($apikey);
}

function rsvptitle_shortcode($atts) {
	global $post;
	return esc_html($post->post_title);
}

function rsvpdate_shortcode($atts = array()) {
	global $post, $rsvp_options;
	$format = empty($atts['format']) ? $rsvp_options['long_date'] : $atts['format'];
	$daterow = get_rsvpmaker_event($post->ID);
	rsvpmaker_debug_log($daterow,'rsvpdate shortcode daterow');
	if(!$daterow)
		return;
	$start_date = preg_replace('/ .+/','',$daterow->date);//date not time
	$end_date = preg_replace('/ .+/','',$daterow->enddate);//date not time
	$t = (int) $daterow->ts_start;
	if($start_date == $end_date){
		return rsvpmaker_date($format,$t);
	}
	else {
		$endt = (int) $daterow->ts_end;
		return rsvpmaker_date($format,$t).' - '.rsvpmaker_date($format,$endt);
	}
}

function rsvpdatetime_shortcode($atts) {
	global $post, $rsvp_options;
	$format = empty($atts['format']) ? $rsvp_options['long_date'].' '.$rsvp_options['time_format'] : $atts['format'];
	$t = get_rsvpmaker_timestamp($post->ID);
	return rsvpmaker_date($format,$t);
}

function rsvpmaker_cronmail_check_duplicate($content) {
	$key = 'cronemail'.md5($content);
	$found = get_transient($key);
	if($found)
		return true;
	set_transient($key,time()); // used to set content
	return false;
}

function rsvpmailer_preview($atts = array()) {
global $post, $rsvpmaker_tx_content;
$preview = get_post_meta($post->ID,'_rsvpmailer_preview',true);
if(empty($preview)) {

if(!empty($rsvpmaker_tx_content))
	$preview = $rsvpmaker_tx_content;
else
	$preview = trim(strip_tags($post->post_content));
$preview = trim(strip_tags($preview));
if(strlen($preview) > 200)
	$preview = substr($preview,0,200).' ...';
}
return $preview;
}

add_shortcode('rsvpmailer_preview','rsvpmailer_preview');

function rsvpmailer_block_styles() {
	global $rsvmailer_css;
	if(!empty($rsvmailer_css))
		return $rsvmailer_css;
	$site_url = site_url();
	$updir = wp_get_upload_dir()['basedir'];
	$home_path = preg_replace('/wp-content.+/','',$updir);
	$wp_styles = wp_styles();
	$combined = '';
	foreach($wp_styles->queue as $handle) {
		$item = $wp_styles->registered[$handle]->src;
		$combined .= "/* url $item */\n";
		if(strpos($item,'ttp') && !strpos($item,$_SERVER['SERVER_NAME']))
			continue; // ignore reference to external domains
		$item = $home_path.str_replace($site_url,'',$item);
		if(strpos($home_path,'\\'))
			$item = str_replace('/','\\',$item);
		$item = str_replace("//","/",$item);
		$item = str_replace("\\\\","\\",$item);
		$combined .= "/* path $item */\n";
		$combined .= file_get_contents($item)."\n";
	}
	$combined = rsvpmailer_clean_css($combined);
	return $combined;
}

//remove styles emogrifier chokes on
function rsvpmailer_clean_css($content) {
	//nonesuch ignored by inliner 'any-link','first-of-type','last-of-type','nth-last-of-type','only-of-type','optional','required'
	$unsupported = array('last-of-type','first-of-type','only-of-type');//
	foreach($unsupported as $bad) {
		$content = str_replace(':'.$bad,'nonesuch',$content); // remove pseudo references emogrifier chokes on
	}
	//$content = str_replace('*','nonesuch',$content); // remove all wildcard references
	$content = str_replace('menu','nonesuch',$content); // remove all menu references
	$content = preg_replace('/\bbody/','nonesuch',$content);
	return $content;
}

/* deprecated */
function rsvpmaker_included_styles () {
	return;
	global $rsvpemail_styles;
	if(!empty($rsvpemail_styles))
		return $rsvpemail_styles;
	
	$rsvpemail_styles = '/* =WordPress Core
	-------------------------------------------------------------- */
	.alignnone {
		margin: 5px 20px 20px 0;
	}
	
	.aligncenter,
	div.aligncenter {
		display: block;
		margin: 5px auto 5px auto;
	}
	
	.alignright {
		float:right;
		margin: 5px 0 20px 20px;
	}
	
	.alignleft {
		float: left;
		margin: 5px 20px 20px 0;
	}
	
	a img.alignright {
		float: right;
		margin: 5px 0 20px 20px;
	}
	
	a img.alignnone {
		margin: 5px 20px 20px 0;
	}
	
	a img.alignleft {
		float: left;
		margin: 5px 20px 20px 0;
	}
	
	a img.aligncenter {
		display: block;
		margin-left: auto;
		margin-right: auto;
	}
	
	.wp-caption {
		background: #fff;
		border: 1px solid #f0f0f0;
		max-width: 96%; /* Image does not overflow the content area */
		padding: 5px 3px 10px;
		text-align: center;
	}
	
	.wp-caption.alignnone {
		margin: 5px 20px 20px 0;
	}
	
	.wp-caption.alignleft {
		margin: 5px 20px 20px 0;
	}
	
	.wp-caption.alignright {
		margin: 5px 0 20px 20px;
	}
	
	.wp-caption img {
		border: 0 none;
		height: auto;
		margin: 0;
		max-width: 98.5%;
		padding: 0;
		width: auto;
	}
	
	.wp-caption p.wp-caption-text {
		font-size: 11px;
		line-height: 17px;
		margin: 0;
		padding: 0 4px 5px;
	}
	#email-content {
		background-color: #fff !important;
		color: #000 !important;
		max-width: 600px;
		margin-left: auto;
		margin-right: auto;
		padding: 10px;
	}
	body {
		background-color: #fff;
		color: #000;
		font-weight: normal;
		font-size: initial;
	}
	.has-background {
		padding: 5px;
	}
	a {
		display: inline-block !important;
	}
	#messagefooter {
		margin-top: 20px;
		padding: 20px;
		background-color: #eee;
		color: #222;
	}
	img {
		max-width: 95% !important;
	}
	';
	
	//add common block styles
	$rsvpemail_styles .= rsvpmailer_block_styles();
	$extra_email_styles = get_option('extra_email_styles');
	if(!empty($extra_email_styles))
		$rsvpemail_styles .= "\n".$extra_email_styles."\n";
	$dir = get_stylesheet_directory();
	$rsvmailer_css = $dir.'/rsvpemail-editor-style.css';
	file_put_contents($rsvmailer_css,$rsvpemail_styles);
	return $rsvpemail_styles;
	}
	
	function show_rsvpmaker_included_styles () {
			echo '<pre>';
			echo rsvpmaker_included_styles();
			echo '</pre>';
			die();
	}
	
function rsvpmaker_template_inline($query_post_id = 0) {
	//email_content_minfilters();
	//no javascript
		
		global $post;
		global $email_styles;
		global $custom_fields;
		global $email_context;
		global $chimp_options;
		global $wp_query;
		global $email_context;	
		$email_context = true;
		email_content_minfilters();
		$wp_query_backup = $wp_query;
		if($query_post_id)
		{
			query_posts('post_type=rsvpemail&p='.$query_post_id);
		}
	
		ob_start();
		wp_head();
		$head = ob_get_clean();
		
		ob_start();
		?>
		<!doctype html>
		<html <?php language_attributes(); ?> >
		<head>
		<title>*|MC:SUBJECT|*</title>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
		<style id="imported">
	<?php
	echo rsvpmaker_included_styles ();
	?>
	</style>
		</head>
		<body class="rsvpmailer" >
		<!-- controls go here -->
		<article>
		<div class="entry-content">
		<div id="email-content">
	
		<!-- editors note goes here -->
	
			<?php
			//print_r($post);
			the_post();
			the_content();
			if(!strpos($post->post_content,'*|LIST:DESCRIPTION|*'))
			{
			?>
	
	<div id="messagefooter">
	*|LIST:DESCRIPTION|*<br>
	<br>
	<a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list <span style="display: none">*|UNSUB|*</span>
	<br>
	<!-- mailchimp -->
	<strong>Our mailing address is:</strong><br>
	*|LIST:ADDRESS|*<br>
	<em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    
*|REWARDS|*</div>
<?php
		}
	?>
		</div>
		</div>
		</article>
		</body>
		</html>
		<?php
		$content = ob_get_clean();
		$content = rsvpmaker_email_html($content,$post->ID);
update_post_meta($post->ID,'_rsvpmail_text',rsvpmaker_text_version($content));
$wp_query = $wp_query_backup;
return $content;
}

function disable_image_lazy_loading_for_email( $default, $tag_name, $context ) {
	global $post;
    if ( isset($post->post_type) && $post->post_type == 'rsvpemail' ) {
        return false;
    }
    return $default;
}
add_filter(
    'wp_lazy_loading_enabled',
    'disable_image_lazy_loading_for_email',
    999,
    3
);
//jetpack
add_filter(
    'lazy_load_is_enabled',
    'disable_image_lazy_loading_for_email',
    999,
    3
);

function rsvpmaker_get_style_substitutions() {
	global $style_sub;
	if(empty($style_sub)) {
		$colors = array(
			'cool-gray' => '#A9B2B1', //wordpress defaults
			'dark_gray' => '#28303D',
			'gray'      => '#39414D',
			'green'     => '#D1E4DD',
			'blue'      => '#D1DFE4',
			'purple'    => '#D1D1E4',
			'red'       => '#E4D1D1',
			'orange'    => '#E4DAD1',
			'yellow'    => '#EEEADD',
			'aliceblue'=>'#f0f8ff',
			'true-maroon' => '#772432', //toastmasters
			'loyal-blue' => '#004165',
			'happy-yellow' => '#F2DF74',
			'antiquewhite'=>'#faebd7', //css defaults
			'aqua'=>'#00ffff',
			'aquamarine'=>'#7fffd4',
			'azure'=>'#f0ffff',
			'beige'=>'#f5f5dc',
			'bisque'=>'#ffe4c4',
			'black'=>'#000000',
			'blanchedalmond'=>'#ffebcd',
			'blueviolet'=>'#8a2be2',
			'brown'=>'#a52a2a',
			'burlywood'=>'#deb887',
			'cadetblue'=>'#5f9ea0',
			'chartreuse'=>'#7fff00',
			'chocolate'=>'#d2691e',
			'coral'=>'#ff7f50',
			'cornflowerblue'=>'#6495ed',
			'cornsilk'=>'#fff8dc',
			'crimson'=>'#dc143c',
			'cyan'=>'#00ffff',
			'darkblue'=>'#00008b',
			'darkcyan'=>'#008b8b',
			'darkgoldenrod'=>'#b8860b',
			'darkgray'=>'#a9a9a9',
			'darkgrey'=>'#a9a9a9',
			'darkgreen'=>'#006400',
			'darkkhaki'=>'#bdb76b',
			'darkmagenta'=>'#8b008b',
			'darkolivegreen'=>'#556b2f',
			'darkorange'=>'#ff8c00',
			'darkorchid'=>'#9932cc',
			'darkred'=>'#8b0000',
			'darksalmon'=>'#e9967a',
			'darkseagreen'=>'#8fbc8f',
			'darkslateblue'=>'#483d8b',
			'darkslategray'=>'#2f4f4f',
			'darkslategrey'=>'#2f4f4f',
			'darkturquoise'=>'#00ced1',
			'darkviolet'=>'#9400d3',
			'deeppink'=>'#ff1493',
			'deepskyblue'=>'#00bfff',
			'dimgray'=>'#696969',
			'dimgrey'=>'#696969',
			'dodgerblue'=>'#1e90ff',
			'firebrick'=>'#b22222',
			'floralwhite'=>'#fffaf0',
			'forestgreen'=>'#228b22',
			'fuchsia'=>'#ff00ff',
			'gainsboro'=>'#dcdcdc',
			'ghostwhite'=>'#f8f8ff',
			'gold'=>'#ffd700',
			'goldenrod'=>'#daa520',
			'grey'=>'#808080',
			'greenyellow'=>'#adff2f',
			'honeydew'=>'#f0fff0',
			'hotpink'=>'#ff69b4',
			'indianred '=>'#cd5c5c',
			'indigo '=>'#4b0082',
			'ivory'=>'#fffff0',
			'khaki'=>'#f0e68c',
			'lavender'=>'#e6e6fa',
			'lavenderblush'=>'#fff0f5',
			'lawngreen'=>'#7cfc00',
			'lemonchiffon'=>'#fffacd',
			'lightblue'=>'#add8e6',
			'lightcoral'=>'#f08080',
			'lightcyan'=>'#e0ffff',
			'lightgoldenrodyellow'=>'#fafad2',
			'lightgray'=>'#d3d3d3',
			'lightgrey'=>'#d3d3d3',
			'lightgreen'=>'#90ee90',
			'lightpink'=>'#ffb6c1',
			'lightsalmon'=>'#ffa07a',
			'lightseagreen'=>'#20b2aa',
			'lightskyblue'=>'#87cefa',
			'lightslategray'=>'#778899',
			'lightslategrey'=>'#778899',
			'lightsteelblue'=>'#b0c4de',
			'lightyellow'=>'#ffffe0',
			'lime'=>'#00ff00',
			'limegreen'=>'#32cd32',
			'linen'=>'#faf0e6',
			'magenta'=>'#ff00ff',
			'maroon'=>'#800000',
			'mediumaquamarine'=>'#66cdaa',
			'mediumblue'=>'#0000cd',
			'mediumorchid'=>'#ba55d3',
			'mediumpurple'=>'#9370d8',
			'mediumseagreen'=>'#3cb371',
			'mediumslateblue'=>'#7b68ee',
			'mediumspringgreen'=>'#00fa9a',
			'mediumturquoise'=>'#48d1cc',
			'mediumvioletred'=>'#c71585',
			'midnightblue'=>'#191970',
			'mintcream'=>'#f5fffa',
			'mistyrose'=>'#ffe4e1',
			'moccasin'=>'#ffe4b5',
			'navajowhite'=>'#ffdead',
			'navy'=>'#000080',
			'oldlace'=>'#fdf5e6',
			'olive'=>'#808000',
			'olivedrab'=>'#6b8e23',
			'orangered'=>'#ff4500',
			'orchid'=>'#da70d6',
			'palegoldenrod'=>'#eee8aa',
			'palegreen'=>'#98fb98',
			'paleturquoise'=>'#afeeee',
			'palevioletred'=>'#d87093',
			'papayawhip'=>'#ffefd5',
			'peachpuff'=>'#ffdab9',
			'peru'=>'#cd853f',
			'pink'=>'#ffc0cb',
			'plum'=>'#dda0dd',
			'powderblue'=>'#b0e0e6',
			'rosybrown'=>'#bc8f8f',
			'royalblue'=>'#4169e1',
			'saddlebrown'=>'#8b4513',
			'salmon'=>'#fa8072',
			'sandybrown'=>'#f4a460',
			'seagreen'=>'#2e8b57',
			'seashell'=>'#fff5ee',
			'sienna'=>'#a0522d',
			'silver'=>'#c0c0c0',
			'skyblue'=>'#87ceeb',
			'slateblue'=>'#6a5acd',
			'slategray'=>'#708090',
			'slategrey'=>'#708090',
			'snow'=>'#fffafa',
			'springgreen'=>'#00ff7f',
			'steelblue'=>'#4682b4',
			'tan'=>'#d2b48c',
			'teal'=>'#008080',
			'thistle'=>'#d8bfd8',
			'tomato'=>'#ff6347',
			'turquoise'=>'#40e0d0',
			'violet'=>'#ee82ee',
			'wheat'=>'#f5deb3',
			'white'=>'#ffffff',
			'whitesmoke'=>'#f5f5f5',
			'yellowgreen'=>'#9acd32',
			
				);
			foreach($colors as $index => $color) {
				if(strpos($index,'light') !== false)
					{
						$index = str_replace('light','light-',$index);
						$colors[$index] = $color;
					}
				if(strpos($index,'dark') !== false)
					{
						$index = str_replace('dark','dark-',$index);
						$colors[$index] = $color;
					}
				if(strpos($index,'medium') !== false)
					{
						$index = str_replace('medium','medium-',$index);
						$colors[$index] = $color;
					}
			}
			$theme_colors = rsvpmail_filter_style_json();
			if(!empty($theme_colors)) {
				foreach($theme_colors as $index => $value) {
					$colors[$index] = $value;
				}
			}

			$style_sub = array(
				'aligncenter'=>'text-align: center',
				'alignright'=>'float: right; padding-left: 10px; margin-left: 10px;',
				'alignleft'=>'float: left; padding-right:10px; margin-right: 10px;',
				'wp-block-column'=>'display:inline-block; box-sizing: border-box; width: 45%; padding-left: 4%; padding-right: 4%; margin-left: 0; margin-right: 0;vertical-align: top;', // support for 2 columns, not 3 or more
			);			
		foreach($colors as $index => $color)
			{
				$style_sub['has-'.$index.'-color'] = 'color:'.$color;
				$style_sub['has-'.$index.'-background-color'] = 'padding: 5px 30px 5px 30px;background-color:'.$color;
			}

			$custom_style_array = get_option('rsvpmaker_email_custom_styles');
			if(!empty($custom_style_array)) {
				foreach($custom_style_array as $class => $style)
					$style_sub[$class] = $style;
			}
			$rsvpmaker_email_base_font = get_option('rsvpmaker_email_base_font');
			if($rsvpmaker_email_base_font)
				$style_sub['wp-block-rsvpmaker-emailbody'] = stripslashes($rsvpmaker_email_base_font);
			else
				$style_sub['wp-block-rsvpmaker-emailbody'] = 'font-size: 20px;';
		}

	return $style_sub;
}

function rsvpmaker_css_to_array($css) {
	$custom_styles = array();
    $css = strtolower(str_replace("\n",'',$css));
	$css = preg_replace('/\s{2,}/',' ',$css);
	rsvpmaker_debug_log($css,'css input to rsvpmaker_css_to_array');
	preg_match_all('/\.([a-z0-9\-\_]+)\s{0,1}{([^}]+)}/',$css,$matches);
	rsvpmaker_debug_log($matches,'matches rsvpmaker_css_to_array');
	foreach($matches[1] as $index => $class)
	{
		$style = $matches[2][$index];
		$custom_styles[$class] = $style;
	}
	return $custom_styles;
}

function rsvpmaker_filter_style_substititions ($classarray) {
	$style_sub = rsvpmaker_get_style_substitutions();
	$classes = explode(' ',$classarray[1]);
	$style = '';
	$tag = $classarray[0];
	foreach($classes as $class) {
		if(!empty($style_sub[$class]))
			$style .= $style_sub[$class].'; ';
	}
	if( strpos($tag,'style=') )
		$tag = preg_replace('/style=\"[^"]+/',"$0; $style",$tag);
	else
	{
		$tag = str_replace('>'," style=\"$style\">", $tag);
	}
	return $tag;
}

function rsvpmail_filter_style_json() {
	$theme_colors = array();
	$json = new WP_Theme_JSON_Resolver();
	$jsondata = (array) $json->get_merged_data('theme');
	$p = array_pop($jsondata);
	$p = $p['settings']['color'];
	$palette = $p['palette']['default'];
	if(isset($p['palette']['theme'])) {
		$theme = $p['palette']['theme'];
	}
	else {
		$theme = array();
	}
	foreach($palette as $index => $item) {
		if(isset($item['slug'])) {
			$theme_colors[$item['slug']] = $item['color'];
		}
	}
	foreach($theme as $index => $item) {
		if(isset($item['slug']))
			$theme_colors[$item['slug']] = $item['color'];
	}
	return $theme_colors;
}

function rsvpmail_filter_style($content) {
	$content = preg_replace('/<style.+<\/style>/is','',$content);
	$content = preg_replace('/width="[^"]+"/','',$content);
	$content = preg_replace('/height="[^"]+"/','',$content);
	$content = str_replace('<img ','<img style="object-fit: contain; max-width: 100%; max-height: 100%;"',$content);
	$content = str_replace('<figcaption','<figcaption style="text-align: center; font-style: italic;" ',$content);
	$content = str_replace('<table','<table style="width: 100%;" ',$content);
	$content = str_replace('<td','<td style="border: thin solid #000;" ',$content);
	$content = str_replace('<th','<th style="border: thin solid #000;" ',$content);
	$content = preg_replace_callback('/<[a-z]+[^>]*class="([^"]+)"[^>]*>/','rsvpmaker_filter_style_substititions',$content);
	$rsvpmaker_custom_email_tag_styles = get_option('rsvpmaker_custom_email_tag_styles');
	if(is_array($rsvpmaker_custom_email_tag_styles)) {
		foreach($rsvpmaker_custom_email_tag_styles as $tag => $style) {
			if(!empty($style))
			$content = preg_replace_callback('/\<('.$tag.')[^>]*>/','rsvpmaker_tag_style_substitutions',$content);
		}
	}
	$content = preg_replace('/;{2,10}/',';',$content);
	return $content;	
}

function rsvpmaker_tag_style_substitutions($atts) {
	$rsvpmaker_custom_email_tag_styles = get_option('rsvpmaker_custom_email_tag_styles');
	if(!is_array($rsvpmaker_custom_email_tag_styles))
		$rsvpmaker_custom_email_tag_styles = array();
	$rsvpmaker_custom_email_tag_styles = apply_filters('rsvpmaker_custom_email_tag_styles',$rsvpmaker_custom_email_tag_styles);
	$tag = $atts[1];
	$style = '';
	if(is_array($rsvpmaker_custom_email_tag_styles) && !empty($rsvpmaker_custom_email_tag_styles[$tag]) )
		$style .= $rsvpmaker_custom_email_tag_styles[$tag];
	$style = str_replace(';;','',$style);
    if(strpos($atts[0],'style='))
        $atts[0] = preg_replace('/style="[^"]+/',"$0; $style",$atts[0]);
    else
        $atts[0] = str_replace('>',' style="'.$style.'">',$atts[0]);
    return $atts[0];
}
function get_rsvpmailer_tx_block_template( $edit = false ) {
	$content = get_option('rsvpmailer_tx_block_template');
	$post_id = 0;
	if(is_numeric($content)) {
		$post_id = $content;
		$post = get_post($post_id);
		if($post)
			$content = $post->post_content;
		else
			$content = 0;
	}
	elseif(empty($content)) {
		$content = '<!-- wp:rsvpmaker/emailbody -->
		<div style="background-color:#efefef;color:#000;padding:5px" class="wp-block-rsvpmaker-emailbody">
		<!-- wp:paragraph -->
		<p></p>
		<!-- /wp:paragraph -->
		
		<!-- wp:rsvpmaker/emailcontent -->
		<div style="background-color:#fff;color:#000;padding:5px;margin-left:auto;margin-right:auto;max-width:600px;border:thin solid gray;min-height:20px;margin-bottom:5px" class="wp-block-rsvpmaker-emailcontent"><!-- wp:paragraph {"placeholder":"Email content"} -->
		<p></p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:rsvpmaker/emailcontent -->
		
		<!-- wp:rsvpmaker/emailcontent -->
		<div style="background-color:#fff;color:#000;padding:5px;margin-left:auto;margin-right:auto;max-width:600px;border:thin solid gray;min-height:20px;margin-bottom:5px" class="wp-block-rsvpmaker-emailcontent"><!-- wp:paragraph -->
		<p>*|LIST:DESCRIPTION|*</p>
		<!-- /wp:paragraph -->
		
		<!-- wp:paragraph -->
		<p><a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list <span>*|UNSUB|*</span>
				<br><strong>Our mailing address is:</strong><br>*|LIST:ADDRESS|*<br><em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>*|REWARDS|*</p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:rsvpmaker/emailcontent --></div>
		<!-- /wp:rsvpmaker/emailbody -->';
		$post['post_title'] = 'Transactional Email Template';
		$post['post_type'] = 'rsvpemail';
		$post['post_status'] = 'publish';
		$post['post_content'] = $content;
		$post_id = wp_insert_post($post);
		update_option('rsvpmailer_tx_block_template', $post_id);
	}
	if($edit)
		$content = sprintf('<p><a href="%s">Edit</a></p>',admin_url("post.php?post=$post_id&action=edit")).$content;
	return $content;
}


function get_rsvpmailer_default_block_template($edit = false) {
	if(isset($_GET['template']))
		$content = intval($_GET['template']);
	else
		$content = get_option('rsvpmailer_default_block_template');
	$post_id = 0;
	if(is_numeric($content)) {
		$post_id = $content;
		$post = get_post($post_id);
		if($post)
			$content = $post->post_content;
		else
			$content = 0;
	}
	elseif(empty($content)) {
		$content = '<!-- wp:rsvpmaker/emailbody -->
		<div style="background-color:#efefef;color:#000;padding:5px" class="wp-block-rsvpmaker-emailbody">
		<!-- wp:paragraph -->
		<p></p>
		<!-- /wp:paragraph -->
		
		<!-- wp:rsvpmaker/emailcontent -->
		<div style="background-color:#fff;color:#000;padding:5px;margin-left:auto;margin-right:auto;max-width:600px;border:thin solid gray;min-height:20px;margin-bottom:5px" class="wp-block-rsvpmaker-emailcontent"><!-- wp:paragraph {"placeholder":"Email content"} -->
		<p></p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:rsvpmaker/emailcontent -->
		
		<!-- wp:rsvpmaker/emailcontent -->
		<div style="background-color:#fff;color:#000;padding:5px;margin-left:auto;margin-right:auto;max-width:600px;border:thin solid gray;min-height:20px;margin-bottom:5px" class="wp-block-rsvpmaker-emailcontent"><!-- wp:paragraph -->
		<p>*|LIST:DESCRIPTION|*</p>
		<!-- /wp:paragraph -->
		
		<!-- wp:paragraph -->
		<p><a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list <span>*|UNSUB|*</span>
				<br><strong>Our mailing address is:</strong><br>*|LIST:ADDRESS|*<br><em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>*|REWARDS|*</p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:rsvpmaker/emailcontent --></div>
		<!-- /wp:rsvpmaker/emailbody -->';
		$post['post_title'] = 'Default Email Template';
		$post['post_type'] = 'rsvpemail';
		$post['post_status'] = 'publish';
		$post['post_content'] = $content;
		$post_id = wp_insert_post($post);
		update_option('rsvpmailer_default_block_template', $post_id);
	}
	else {
		$post['post_title'] = 'Default Email Template';
		$post['post_type'] = 'rsvpemail';
		$post['post_status'] = 'publish';
		$post['post_content'] = $content;
		$post_id = wp_insert_post($post);
		update_option('rsvpmailer_default_block_template', $post_id);
	}
	if($edit)
		$content = sprintf('<p><a href="%s">Edit</a></p>',admin_url("post.php?post=$post_id&action=edit")).$content;
	return $content;
}

function rsvpmaker_guest_list_table() {
	global $wpdb;
	$guest_table = $wpdb->prefix.'rsvpmaker_guest_email';
	$guest_meta_table = $guest_table.'_meta';
	$version = 7;
	$ver = (int) get_option('rsvpmaker_guest_email_table');
	//$test = @ $wpdb->get_var("SELECT 1 FROM `$history_table` LIMIT 1");
	if($ver < $version)
	{
		update_option('rsvpmaker_guest_email_table',$version);
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql = "CREATE TABLE `$guest_table` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`email` varchar(255) DEFAULT '',
			`first_name` varchar(255) DEFAULT '',
			`last_name` varchar(255) DEFAULT '',
			`active` smallint(6) DEFAULT 1,
			`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `email` (`email`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
		  dbDelta($sql);
	
		$sql = "CREATE TABLE `$guest_meta_table` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`guest_id` int(11) NOT NULL,
			`meta_key` varchar(255) NOT NULL,
			`meta_value` text NULL,
			PRIMARY KEY (`id`),
			KEY `guest_id` (`guest_id`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		  //CONSTRAINT `".$speech_history_table."_ibfk_1` FOREIGN KEY (`history_id`) REFERENCES `$history_table` (`id`) ON DELETE CASCADE
		  dbDelta($sql);
		}
	return $guest_table;
}	

function rsvpmaker_guestlist_nextprev($start) {
	global $wpdb;
	$table = rsvpmaker_guest_list_table();
	$table_meta = $table.'_meta';
	$count = $wpdb->get_var("SELECT count(*) FROM $table");
	if($count < 500)
		return '';
	$next = ($count > $start + 500) ? sprintf('<a href="%s">Next</a>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_guest_list&start='.($start + 500))) : '';
	for($i = 500; $i <= $count; $i += 500) {
		$next .= ' | '.sprintf('<a href="%s">%d</a>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_guest_list&start='.($i)),$i);
	}
	$previous = ($start) ? sprintf('<a href="%s">Previous</a>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_guest_list&start='.($start - 500))) : '';
	return $previous.' '.$next.' showing up to 500 at a time';
}

function get_rsvpmaker_guest_list($start = 0, $limit = false, $active = 1, $search = '') {
	global $wpdb;
	$table = rsvpmaker_guest_list_table();
	$table_meta = $table.'_meta';
	$where = ($active) ? ' WHERE active=1 ' : '';
	if(!empty($search)) {
		if(!empty($where)) 
			$where .= ' AND ';
		else
			$where = ' WHERE ';
		$where .= " (email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%')";			
	}
	$sql = "SELECT $table.*, email as user_email, $table_meta.meta_value as segment FROM $table LEFT JOIN $table_meta ON `$table`.id = `$table_meta`.guest_id $where ORDER BY email";
	if($limit)
		$sql .= " LIMIT $start, $limit";
	$results = $wpdb->get_results($sql);
	foreach($results as $row) {
		if(empty($returnarray[$row->email]))
			$returnarray[$row->email] = $row;
		else
			$returnarray[$row->email]->segment .= ', '.$row->segment;
	}
	return $returnarray;
}

function get_rsvpmaker_email_segment($segment, $active = true) {
	global $wpdb;
	$table = rsvpmaker_guest_list_table();
	$table_meta = $table.'_meta';
	if($active)
		$sql = "SELECT *, email as user_email, meta_value as segment FROM $table LEFT JOIN $table_meta ON `$table`.id = `$table_meta`.guest_id WHERE active=1 AND meta_value='$segment' ORDER BY last_name, first_name";
	else
		$sql = "SELECT *, email as user_email, meta_value as segment FROM $table LEFT JOIN $table_meta ON `$table`.id = `$table_meta`.guest_id WHERE meta_value='$segment' ORDER BY last_name, first_name";
	return $wpdb->get_results($sql);
}

function rsvpmail_confirm_email($e) {
	if(is_email($e)) {
		global $wpdb;
		$table = rsvpmaker_guest_list_table();
		$sql = "UPDATE $table SET active=1 WHERE email='$e' ";
		$wpdb->query($sql);	
	}
}

function rsvpmaker_guest_list_add($email, $first_name = '', $last_name='', $segment='', $active=1) {
	$email = trim(strtolower($email));
	global $wpdb;
	$output = '';
	$id = $exists = $confirmed = 0;
	$table = rsvpmaker_guest_list_table();
	$sql = $wpdb->prepare("SELECT * FROM $table where email LIKE %s",$email);
	$row = $wpdb->get_row($sql);
	if($row) {
		$exists = $id = $row->id;
		$active = $confirmed = $row->active;
	}

	if($id) 
	{
		$sql = $wpdb->prepare("UPDATE $table SET email=%s, first_name=%s, last_name=%s, active=%d WHERE id=%d ",$email,$first_name,$last_name,$active, $id);
		$result = $wpdb->query($sql);
	}
	else {
		$sql = $wpdb->prepare("INSERT INTO $table SET email=%s, first_name=%s, last_name=%s, active=%d ",$email,$first_name,$last_name,$active);
		$result = $wpdb->query($sql);
		$id = $wpdb->insert_id;
	}
	if($id && !empty($segment)) {
		$sql = $wpdb->prepare("SELECT * from ".$table."_meta WHERE guest_id=%d AND meta_key='segment' AND meta_value=%s",$id,$segment);
		$row = $wpdb->get_row($sql);
		if(!$row) {
			$sql = "insert into ".$table."_meta SET guest_id='$id', meta_key='segment', meta_value='$segment' ";
			$wpdb->query($sql);	
		}
	}

	$list_active = get_option('rsvpmaker_guest_list_active');
	if($exists && $confirmed)
		$output .= '<p>You are already a confirmed member of the email list.</p>';
	elseif(!$active && $list_active) {
		//confirmation required
		$mail['to'] = $email;
		$mail['subject'] = 'Please confirm your subscription to the email list';
		$mail['from'] = get_bloginfo('admin_email');
		$mail['fromname'] = get_option('blogname');
		$confirm = site_url('?rsvpmail_subscribe='.$email);
		$mail['html'] = sprintf('<p>Please <a href="%s">confirm your subscription</a> to the email list.</p><p>Follow this link to confirm<br><a href="%s">%s</a></p><p>If you did not initiate a subscription request, please ignore this note and accept our apologies.</p>',$confirm,$confirm,$confirm);
		rsvpmailer($mail);
		$output .= '<p><em>Please check your email for a message asking you to confirm your subscription.</em></p>';
		if($exists)
			$output .=  '<p>Looks like you may have signed up previously but not confirmed your subscription.</p>';
	}
	return $output;
}

function rsvpmaker_add_contact_segment($id, $segment='') {
	global $wpdb;
	$table = rsvpmaker_guest_list_table();
	if($id && !empty($segment)) {
		$sql = "select * from ".$table."_meta where guest_id='$id' and meta_key='segment' and meta_value='$segment' ";
		if(!$wpdb->get_row($sql)) {
			$sql = "insert into ".$table."_meta SET guest_id='$id', meta_key='segment', meta_value='$segment' ";
			$wpdb->query($sql);	
		}
	}
}

function rsvpmaker_email_segments_dropdown($field_name='segment', $echo = true) {
	$segments = get_option('rsvpmail_segments');
	if(!is_array($segments))
		$segments = array();
	if(empty($segments['rsvp_form_signup']))
		$segments['rsvp_form_signup'] = 'RSVP Form Signup';
	$segment_options = '<option value="">General List</option>';
	foreach($segments as $index => $segment) {
		if(!empty($segment))
			$segment_options .= sprintf('<option value="%s">%s</option>',$index,$segment);
		}
	$output = sprintf('<select name="'.$field_name.'">'.$segment_options.'</select>');
	if($echo)
		echo $output;
	return $output;
}

function rsvpmaker_guest_list() {
	global $wpdb;
	$table = rsvpmaker_guest_list_table();
	rsvpmaker_admin_heading(__('RSVPMaker Email List','rsvpmaker'),__FUNCTION__);
	$active = (int) get_option('rsvpmaker_guest_list_active');
	$segments = get_option('rsvpmail_segments');
	if(!is_array($segments))
		$segments = array();
	if(empty($segments['rsvp_form_signup']))
		$segments['rsvp_form_signup'] = 'RSVP Form Signup';

	$mailpoet_table = $wpdb->prefix.'mailpoet_subscribers';

	if(!empty($_POST['timelord'])  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')))
	{	
		if(isset($_POST['active'])) {
			$active = intval($_POST['active']);
			update_option('rsvpmaker_guest_list_active',$active);	
		}

		$segment = '';
		if(isset($_POST['newsegment'])) {
			$segment = sanitize_text_field($_POST['newsegment']);
			$segmentindex = preg_replace('[^a-z0-9]','_',strtolower($segment));
			$segmentindex = trim($segmentindex);
			if(!empty($segmentindex)) {
				$segments[$segmentindex] = $segment;
				ksort($segments);
				update_option('rsvpmail_segments',$segments);
				$segment = $segmentindex;	
			}
		}
		if(empty($segment) && !empty($_POST['segment']) )
			$segment = sanitize_text_field($_POST['segment']);

		if(!empty($_POST['email'][0]))
		{
			foreach($_POST['email'] as $index => $email) {
				if(is_email($email)) {
					$first_name = sanitize_text_field($_POST['first_name'][$index]);
					$last_name = sanitize_text_field($_POST['last_name'][$index]);
					rsvpmaker_guest_list_add($email, $first_name,$last_name,$segment);
				}
			}
		}

		if(isset($_POST['add_to_segment']) && !empty($_POST['segment'])) {
			$segment = sanitize_text_field($_POST['segment']);
			foreach($_POST['add_to_segment'] as $id) {
				echo "<br>$id $segment";
				rsvpmaker_add_contact_segment(intval($id), $segment);
			}
		}

		if(isset($_POST['mailpoet'])) {
			$segment = sanitize_text_field($_POST['segment']);
			$sql = "SELECT * FROM $mailpoet_table WHERE status='subscribed' ";
			$results = $wpdb->get_results($sql);
			if($results) {
				printf('<p>Importing %s confirmed subscribers from MailPoet</p>',sizeof($results));
				foreach($results as $index => $row) {
					rsvpmaker_guest_list_add($row->email, $row->first_name,$row->last_name,$segment);
				}	
			}
		}

		if(!empty($_POST['delete']))
		{
			echo '<div class="notice"><p>Deleting selected emails</p></div>';
			foreach($_POST['delete'] as $id) {
				$sql = "delete from $table where id = $id";
				$wpdb->query($sql);
			}
		}

		if(!empty($_POST['resend']))
		{
			echo '<div class="notice"><p>Resending email requesting confirmation</p></div>';
			$mail['from'] = get_option('admin_email');
			$mail['fromname'] = get_option('blogname');
			$mail['subject'] = 'Please confirm your subscription to the email list for '.$mail['fromname'];
			foreach($_POST['resend'] as $id) {
				$sql = "select email from $table where id = $id";
				$mail['to'] = $wpdb->get_var($sql);
				$confirm = site_url('?rsvpmail_subscribe='.$mail['to']);
				$mail['html'] = "<p>We have your email list signup on file but need confirmation before we can add you to our active list.</p>";
				$mail['html'] .= sprintf('<p>Please <a href="%s">confirm your subscription</a> to the email list.</p><p>Follow this link to confirm<br><a href="%s">%s</a></p><p>If you did not initiate a subscription request, please ignore this note and accept our apologies.</p>',$confirm,$confirm,$confirm);
				rsvpmailer($mail);
			}
		}

		rsvpmaker_email_upload_to_array($segment);
	}

	if(!is_array($segments))
		$segment_options = '<option value="">None Configured</option>';
	else {
		$segment_options = '<option value="">General List</option>';
		foreach($segments as $index => $segment) {
			if(!empty($segment))
				$segment_options .= sprintf('<option value="%s">%s</option>',$index,$segment);
		}
	}

	?>
	<p>This built-in email list is an optional feature of RSVPMaker that allows you to maintain an email list from within WordPress, independent of an external service such as Mailchimp or MailPoet. For lists of more than 100 recipients, consider using the <a href="https://rsvpmaker.com/rsvpmaker-postmark/">RSVPMaker Mailer for Postmark</a> integration for better management of anti-spam issues.</p>
	<?php
	printf('<form method="post" enctype="multipart/form-data" action="%s">',admin_url('admin.php?page=rsvpmaker_guest_list'));
	rsvpmaker_nonce();
	if($active)
		printf('<p>%s <input type="radio" name="active" value="1" checked="checked"> %s <input type="radio" name="active" value="0" > %s </p>',__('Active','rsvpmaker'),__('Yes','rsvpmaker'),__('No','rsvpmaker'));
	else
		printf('<p>%s <input type="radio" name="active" value="1"> %s <input type="radio" name="active" value="0"  checked="checked"> %s </p>',__('Active','rsvpmaker'),__('Yes','rsvpmaker'),__('No','rsvpmaker'));
		rsvpmaker_add_to_list_on_rsvp_form();
		rsvpmail_signup_page_add();
		printf('<p>Email to add <input type="text" name="email[]"> First Name <input type="text" name="first_name[]"> Last Name <input type="text" name="last_name[]"> </p>');
		printf('<p>%s: <input type="file" name="upload_file" /><br>You can upload a CSV data file with columns in the order email, first name, and last name</p>',__('Select file to upload','rsvpmaker'));
		$sql = "SHOW TABLES LIKE '$mailpoet_table' ";
		if($wpdb->get_var($sql))
			printf('<p><input type="checkbox" name="mailpoet" value="1" /> %s</p>',__('Import MailPoet subscriber list','rsvpmaker'));
		printf('<p>List segment (optional): <select name="segment">%s</select>. For a new segment, enter a label <input type="text" name="newsegment"></p>',$segment_options);
	submit_button();
	echo '</form>';
		echo '<h2>'.__('Current List','rsvpmaker').'</h2>';
		$start = (isset($_GET['start'])) ? intval($_GET['start']) : 0;
		$search = (isset($_GET['s'])) ? sanitize_text_field($_GET['s']) : '';
		if(isset($_GET['unconfirmed']))
			$list = $wpdb->get_results("SELECT * from $table WHERE active=0");
		else
			$list = get_rsvpmaker_guest_list($start, 500, 0, $search);
		printf('<form method="get" action="%s"><input type="hidden" name="post_type" value="rsvpemail"><input type="hidden" name="page" value="rsvpmaker_guest_list"><input type="text" name="s"><button>Search</button> or <a href="%s&unconfirmed=1">show unconfirmed</a></form>',admin_url('edit.php'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_guest_list'));
		$nextprev = rsvpmaker_guestlist_nextprev($start);
		echo "<p>$nextprev</p>";
		if(empty($list))
			echo '<p>Empty</p>';
		else {
			printf('<form method="post" action="%s">',admin_url('admin.php?page=rsvpmaker_guest_list'));
			echo '<table class="wp-list-table widefat striped"><tr><th>Delete</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Segment</th><th>Add checked to<br>'.rsvpmaker_email_segments_dropdown('segment',false).'</th></tr>';
			foreach($list as $item) {
				$prompt = ($item->active) ? '' : '<br><strong>Not confirmed.</strong> <input type="checkbox" name="resend[]" value="'.$item->id.'"> Resend confirmation prompt? <button>Submit</button>';
				printf('<tr><td><input type="checkbox" name="delete[]" value="%s"></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><input type="checkbox" name="add_to_segment[]" value="%s"></td></tr>',$item->id,$item->email.$prompt,$item->first_name, $item->last_name, $item->segment,$item->id);
			}
			echo '</table>';
			rsvpmaker_nonce();
			submit_button();
			echo '</form>';
			echo "$nextprev";
		}
}

function rsvpmaker_email_upload_to_array($segment = '') {
	$csv_array  = array();
	if ( ! empty( $_FILES['upload_file']['tmp_name'] ) ) {
		$file = fopen( $_FILES['upload_file']['tmp_name'], 'r' );
		if ( $file ) {
			while ( ( $line = fgetcsv( $file ) ) !== false ) {
				// $line is an array of the csv elements
				array_push( $csv_array, $line );
			}
			fclose( $file );
		}
	}

	if ( ! empty( $csv_array ) ) {
		foreach ( $csv_array as $linenumber => $cells ) {
				$email = $cells[0];
				if(is_email($email)) {
					$first_name = empty($cells[1]) ? '' : $cells[1];
					$last_name = empty($cells[2]) ? '' : $cells[2];
					rsvpmaker_guest_list_add($email, $first_name, $last_name,$segment);
				}
		}
	}
}

function rsvpmaker_email_to_name($to) {
	$name = '';
	global $wpdb;
	$sql = "select display_name from $wpdb->users WHERE user_email LIKE '$to' ";
	//rsvpmaker_debug_log($sql,'email to name');
	$name .= $wpdb->get_var($sql);
	if(!empty($name)) {
		rsvpmaker_debug_log($name,'email to name');
		return $name;
	}
	$sql = "select * from ".$wpdb->prefix."rsvpmaker WHERE email LIKE '$to' ";
	//rsvpmaker_debug_log($sql,'email to name');
	$row = $wpdb->get_row($sql);
	if(!empty($row->first) || !empty($row->last)) {
		$name .= $row->first.' '.$row->last;
		//rsvpmaker_debug_log($name,'email to name');
		return $name;
	}
	$table = rsvpmaker_guest_list_table();
	$sql = "select * from $table WHERE email LIKE '$to' ";
	//rsvpmaker_debug_log($sql,'email to name');
	$row = $wpdb->get_row($sql);
	if(!empty($row->first_name) || !empty($row->last_name)) {
		$name = $row->first_name.' '.$row->last_name;
		//rsvpmaker_debug_log($name,'email to name');
		return $name;
	}
	return $name;
}

function rsvpmaker_guest_email_welcome($email) {
	global $post;
	$welcome_id = get_option('rsvpmaker_guest_email_welcome');
	if(!$welcome_id || !get_option('rsvpmaker_guest_list_active') || rsvpmaker_on_guest_email_list($email))
		return;
	$wpost = get_post($welcome_id);
	//rsvpmaker_debug_log($post,'welcome_post');
	if(!$wpost)
		return;
	$mail['to'] = $email;
	$mail['from'] = get_bloginfo('admin_email');	
	$mail['fromname'] = get_bloginfo('name');
	$mail['subject'] = $wpost->post_title;
	$mail['html'] = rsvpmaker_email_html($wpost->post_content);
	$result = rsvpmailer($mail);
	echo '<p><em>Watch for a welcome email.</em></p>';
}

function rsvpmaker_on_guest_email_list($email, $checkuserlist = true) {
	$status = false;
	global $wpdb;
	if($checkuserlist)
	{
		$sql = "select ID from $wpdb->users WHERE user_email LIKE '$email' ";
		$id = $wpdb->get_var($sql);
		if($id) {
			return true;
		}	
	}
	$table = rsvpmaker_guest_list_table();
	$sql = "select * from $table WHERE email LIKE '$email' ";
	//rsvpmaker_debug_log($sql,'on guest list lookup');
	return $wpdb->get_row($sql);
}

function rsvpmaker_emailpostorposts($atts) {
global $wp_query, $post;
$backup = $wp_query;
ob_start();
if(!empty($atts['selection']) && is_numeric($atts['selection']))
	$args['p'] = intval($atts['selection']);
else {
	$posts_per_page = (isset($atts['posts_per_page'])) ? intval($atts['posts_per_page']) : 1;
	$args = array( 'posts_per_page' => $posts_per_page );
	$args['category_name'] =  (isset($atts['selection'])) ? sanitize_text_field($atts['selection']) : '';
}
// the query
$wp_query = new WP_Query( $args );?>
<div class="email-posts">
<?php while ( have_posts() ) : the_post(); 
$permalink = get_permalink();
?>
<div class="email-post">
<h2><a href="<?php echo $permalink; ?>"><?php the_title(); ?></a></h2>
<?php
if ( has_post_thumbnail() ) {
    the_post_thumbnail();
}
?>
<p class="the_excerpt"><?php echo $excerpt = get_the_excerpt();?></p>
<?php 
if(!strpos($excerpt,$permalink))
{
?>
<p class="email-read-more"><a href="<?php echo $permalink; ?>"><?php _e('Read More','rsvpmaker'); ?></a></p>
<?php 
}
?>
</div>
<?php endwhile; // End of the loop. ?>
</div>
<?php
$wp_query = $backup;
wp_reset_postdata();
return ob_get_clean();//'hello from the server '.var_export($atts,true);
}

function rsvpmaker_emailguestsignup ($atts) {
global $rsvp_options;
	ob_start();
	$check = true;
if(!empty($_POST['rsvpguest_list_email'])) {
	if(!is_admin() && !empty($rsvp_options["rsvp_recaptcha_site_key"]) && !empty($rsvp_options["rsvp_recaptcha_secret"]))
	{
	if(!rsvpmaker_recaptcha_check ($rsvp_options["rsvp_recaptcha_site_key"],$rsvp_options["rsvp_recaptcha_secret"]))	{
		echo 'Failed security check';
		$check = false;
		}	
	}
	else {
		if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
			echo 'Failed security check';
			$check = false;	
		}
	}
	$entry = array('email' => sanitize_text_field($_POST['rsvpguest_list_email']),'first' => sanitize_text_field($_POST['rsvpguest_list_first']),'last' => sanitize_text_field($_POST['rsvpguest_list_last']) );

	if($check && is_email($entry['email'])) {
		//add to email list
		echo '<p>Adding: '.$entry['email'].'</p>';
		rsvpmaker_email_list_okay ($entry);
	}
	elseif($check) {
		echo 'invalid email '.$entry['email'];
	}
}
printf('<form action="%s" method="post" class="guest-email-signup"><h4>%s</h4>',esc_url(get_permalink()),__('Email List Signup','rsvpmaker'));
$fields = empty($atts['fields']) ? '' : sanitize_text_field($atts['fields']);
if('' == $fields) {
	?>
<p><label><?php echo __('First Name','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<p><label><?php echo __('Last Name','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_last"  name="rsvpguest_list_last" /></p>
<p><label><?php echo __('Email','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
	<?php
}
elseif('first' == $fields) {
	?>
<p><label><?php echo __('First Name','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label><?php echo __('Email','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
	<?php
}
elseif('email' == $fields) {
	?>
<input type="hidden" id="rsvpguest_list_first"  name="rsvpguest_list_first" />
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label><?php echo __('Email','rsvpmaker'); ?></label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
	<?php
}
rsvpmaker_nonce();
rsvpmaker_recaptcha_output();
printf('<button>%s</button></form>',__('Sign Up','rsvpmaker'));
return ob_get_clean();

}

add_action('init','rsvpmaker_queue_post_type');
function rsvpmaker_queue_post_type() {
	$result = register_post_status('rsvpmessage',array('label'=>'Group Message','internal'=>true,'show_in_admin_status_list'=>true,'show_in_admin_all_list'=>false));
	global $wpdb;
	$sql = "SELECT ID, post_title, meta_key, meta_value, post_status FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE post_type='rsvpemail' AND (meta_key='headerinfo' OR meta_key='rsvpmail_sent') AND post_status='draft' ORDER BY ID DESC LIMIT 0, 200";
	$results = $wpdb->get_results($sql);
	$was = 0;
	foreach($results as $row)
	{
		if($row->ID != $was) {
			if('draft' == $row->post_status) {
				$sql = "update $wpdb->posts SET post_status='rsvpmessage' WHERE ID=$row->ID ";
				$result = $wpdb->query($sql);
				print_r("<p>post status change result %s %s</p>",$sql,var_export($result,true));
			}
		}
		$was = $row->ID;
	}
	$sql ="SELECT ID FROM $wpdb->posts WHERE `post_status` LIKE 'rsvpmessage' AND post_date < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
	$results = $wpdb->get_results($sql);
	foreach($results as $row) {
		wp_delete_post( $row->ID, true ); // delete old posts and their metadata
	}
	
}

function rsvpmaker_email_add_name($email,$name) {
	if(empty($name) || strpos($email,'<')  || strpos($email,'"'))
		return $email;
	return "\"".addslashes($name)."\" <".$email.">";
}

function rsvpmail_latest_post_promo($args = array()) {
	if(empty($args['additional_posts'])) $args['additional_posts'] = 4; 
	if(empty($args['paragraphs'])) $args['paragraphs'] = 5; 
    $posts = query_posts( array('posts_per_page'=>$args['additional_posts']+1,'post_type'=>'post','post_status'=>'publish') );
    $featured = array_shift($posts);
	$promo_id = get_post_meta($featured->ID,'promo_email',true);
	if($promo_id) //update rather than create
		$new['ID'] = $promo_id;
    $permalink = get_permalink($featured->ID);
    $new['post_title'] = $featured->post_title;
    $html = '<!-- wp:heading -->
    <h2><a href="'.$permalink.'">'.$featured->post_title.'</a></h2>
    <!-- /wp:heading -->
    '."\n";
    $paragraphs = explode('<!-- wp:paragraph -->',$featured->post_content);
    $paragraphs = array_slice($paragraphs,0,intval($args['paragraphs']));
    $excerpt = implode('<!-- wp:paragraph -->',$paragraphs);
    $html .= $excerpt;
	$html .= '<!-- wp:paragraph -->
	<p><a href="'.$permalink.'">--&gt;&gt; Read More</a></p>
	<!-- /wp:paragraph -->
	';
	if(!empty($posts)) {
		$html .= '<!-- wp:heading -->
		<h2>More Headlines</h2>
		<!-- /wp:heading -->
		';
		foreach($posts as $p) {
			$permalink = get_permalink($p->ID);
			$html .= '<!-- wp:paragraph -->
			<p><a href="'.$permalink.'">'.$p->post_title.'</a></p>
			<!-- /wp:paragraph -->
			';
		}		
	}

    $rsvpmailer_default_block_template = get_rsvpmailer_default_block_template();
    $parts = preg_split('/<\/div>\s+<!-- \/wp:rsvpmaker\/emailcontent -->/m',$rsvpmailer_default_block_template,2);
    if(!empty($parts[1]))
        $html = $parts[0].$html."</div>\n<!-- /wp:rsvpmaker/emailcontent -->".$parts[1];
    $new['post_content'] = $html;
    $new['post_type'] = 'rsvpemail';
    $new['post_status'] = 'publish';
	$promo_id = wp_insert_post($new);
	add_post_meta($featured->ID,'promo_email',$promo_id);
	$html = rsvpmaker_email_html($html);
	update_post_meta($promo_id,'_rsvpmail_html',$html);

	$html = sprintf('<p><a href="%s">Preview/Send</a></p>
		<p><a href="%s">Edit</a></p>',add_query_arg('verify',wp_create_nonce( 'verify_email' ),get_permalink($promo_id)),admin_url("post.php?post=$promo_id&action=edit"))."\n\n".$html;
	return array('subject' => $featured->post_title,'html' => $html);
}

function rsvpmail_latest_posts_notification_setup() {
	rsvpmaker_admin_heading('New Posts Promo',__FUNCTION__);
	if(isset($_POST['rsvpmaker_new_post_promos']))
	{
		$args = $_POST['rsvpmaker_new_post_promos'];
		foreach($args as $key => $value)
			$args[$key] = intval($value);
		update_option('rsvpmaker_new_post_promos',$args);
	}
	else {
		$args = get_option('rsvpmaker_new_post_promos');
		if(empty($args))
		$args = array();
		if(empty($args['additional_posts'])) $args['additional_posts'] = 4; 
		if(empty($args['paragraphs'])) $args['paragraphs'] = 5;	
	}

	$subject_html = rsvpmail_latest_post_promo($args);
	?>
	<p>RSVPMaker can generate a new posts promotion every time a new item is published to your blog.</p>
	<h3>Preferences</h3>
	<form method="post" action="<?php echo admin_url('edit.php?page=rsvpmail_latest_posts_notification_setup'); ?>">
	<p><input type="radio" name="rsvpmaker_new_post_promos[notify]" value="1" <?php if(!empty($args['notify'])) echo 'checked="checked"'; ?> > Send notifications <input type="radio" name="rsvpmaker_new_post_promos[notify]" value="0" <?php if(empty($args['notify'])) echo 'checked="checked"'; ?> > Notifications off</p>
	<p>Include <input type="text" size="5" name="rsvpmaker_new_post_promos[paragraphs]" value="<?php echo $args['paragraphs']; ?>" > paragraphs from the latest post and links to <input type="text" size="5" name="rsvpmaker_new_post_promos[additional_posts]" value="<?php echo $args['additional_posts']; ?>" > additional recent posts.</p>
	<?php
	submit_button();
	echo '</form>';
	echo "<h3>New post promo:".$subject_html['subject'].'</h3>'.$subject_html['html'];
}

function rsvpmail_latest_posts_notification($new_status, $old_status, $post ) {
	if($new_status != $old_status && 'publish' == $new_status && 'post' == $post->post_type) {
		$args = get_option('rsvpmaker_new_post_promos');
		if(empty($args) || empty($args['notify']))
			return;
		$subject_html = rsvpmail_latest_post_promo($args);
		$mail['to'] = $mail['from'] = get_bloginfo('admin_email');
		$mail['subject'] = 'New post promo: '.$post->post_title;
		$mail['html'] = $subject_html['html'];
		rsvpmailer($mail);
	}
}

add_action( 'transition_post_status', 'rsvpmail_latest_posts_notification',10,3);

function rsvpmail_replace_placeholders($content,$description='') {
	$chimp_options = get_option('chimp');
	$address = isset($chimp_options['mailing_address']) ? $chimp_options['mailing_address'] : '<strong>NOT SET</strong>';
	$company = isset($chimp_options['company']) ? $chimp_options['company'] : get_bloginfo('name');
	$content = str_replace('*|UNSUB|*',site_url('?rsvpmail_unsubscribe=*|EMAIL|*'),$content);
	$content = str_replace('*|REWARDS|*','',$content);
	$content = str_replace('*|LIST:DESCRIPTION|*',$description,$content);
	$content = str_replace('*|LIST:ADDRESS|*',$address,$content);
	$content = str_replace('*|HTML:LIST_ADDRESS_HTML|*',$address,$content);
	$content = str_replace('*|LIST:COMPANY|*',$company,$content);
	$content = str_replace('*|CURRENT_YEAR|*',date('Y'),$content);
	return $content;
}

function rsvpmaker_email_html ($post_or_html, $post_id = 0) {
	$html = '';
	if(is_object($post_or_html)) {
		$html = $post_or_html->post_content;
		$post_id = $post_or_html->ID;
	}
	if(is_array($post_or_html) && isset($post_or_html['post_content']))
		$html = $post_or_html['post_content'];
	if(is_string($post_or_html))
		$html = $post_or_html;
	if(strpos($html,'<-- wp:paragraph'))
		$html = do_blocks($html);
	if(strpos($html,']'))
		$html = do_shortcode($html);
	if(strpos($html,'youtu'))
		$html = rsvpmaker_youtube_email($html);
	$html = rsvpmail_filter_style($html);
	if($post_id)
		update_post_meta($post_id,'_rsvpmail_html',$html);
	return $html;
}

function rsvpmail_editors_note_ui($post_id) {
global $wpdb;
$notekey = get_rsvp_notekey();
if(!empty($_POST['notekey']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )	
{
	if(!empty($_POST['notesubject']) || !empty($_POST['notebody']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
	{
		global $current_user;
		$newpost['post_title'] = sanitize_text_field(stripslashes($_POST['notesubject']));
		$newpost['post_content'] = wp_kses_post(rsvpautog(stripslashes($_POST['notebody'])));
		$newpost['post_type'] = 'post';
		$newpost['post_status'] = sanitize_text_field($_POST['status']);
		$newpost['post_author'] = $current_user->ID;
		$chosen = wp_insert_post( $newpost );
	}
	elseif('reset' == $_POST['status'])
		delete_post_meta($post_id,sanitize_text_field($_POST['notekey']));
	elseif(!empty($_POST['chosen']))
		$chosen = intval($_POST['chosen']);
	if($chosen)
		update_post_meta($post_id,sanitize_text_field($_POST['notekey']),$chosen);
}
else
	$chosen = get_post_meta($post_id,$notekey,true);

printf('<form action="%s" method="post">',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&editor_note='.$post_id));
rsvpmaker_nonce();
?>
<h3 id="editorsnote"><?php esc_html_e("Add Editor's Note for",'rsvpmaker'); if(empty($stamp)) echo ' Next broadcast'; else echo ' '.$ts;?> (optional)</h3>

<input type="hidden" name="notekey" value="<?php echo esc_attr($notekey); ?>">

<p><?php esc_html_e("A blog post, either public or draft, can be featured as the editor's note at the top of your next email newsletter broadcast. The content of the post title will be added to the end of the email subject line, and the content of the post (up to the more tag, if included) will be included in the body of your email.",'rsvpmaker');?></p>

<?php
$ts = rsvpmaker_next_scheduled($post_id);
$recent = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='post' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC LIMIT 0,20");
if(is_array($recent))
foreach($recent as $blog)
	{
	$s = ($blog->ID == $chosen) ? ' selected="selected"' : '';
	if($blog->ID == $chosen)
		$chosentitle = $blog->post_title;
	$title = ($blog->post_status == 'draft') ? $blog->post_title. ' (draft)' : $blog->post_title;
	$blog_options .= sprintf('<option value="%d" %s>%s</option>',esc_attr($blog->ID),$s,esc_html($title));
	}

if($chosen)
{
	$blog = get_post($chosen);
	$chosentitle = $blog->post_title;
	$blog_options .= sprintf('<option value="%d" selected="selected">%s</option><option value="">(Clear Selection)</option>',esc_attr($blog->ID),esc_html($blog->post_title));
	printf('<p>The current editor\'s note is based on the blog post <strong>%s</strong>. <a href="%s">(Edit)</a></p>',esc_html($chosentitle),admin_url('post.php?action=edit&post='.(int)$chosen));
	echo '<p><input type="radio" name="status" value="reset"> Reset</p>';
}
?>

<p><input type="radio" name="status" value="" checked="checked" /> <strong>
<?php esc_html_e('Pick a blog post to feature','rsvpmaker');?>:</strong> <select name="chosen"><option value=""><?php esc_html_e('None','rsvpmaker');?></option>
<?php echo $blog_options; ?></select></p>

<p><input type="radio" name="status" value="draft" /> <strong>Create a draft</strong> based on the headline and message below (will not appear on the live website unless you subsequently choose to publish the draft)<br /><input type="radio" name="status" value="publish" /> <strong>Create and publish</strong> blog based on the headline and message below</strong><br /> <em>(<?php esc_html_e('This post will be used as the editors note at the top of your broadcast. Making it public on the blog is optional.','rsvpmaker');?>)</em></p>

<p><?php esc_html_e('Title/Subject','rsvpmaker');?>: <input type="text" name="notesubject" value="" /></p>
<p>Message:<br />
<textarea cols="100" rows="5" name="notebody"></textarea></p>
<?php submit_button(); ?>
</form>
<?php
}

add_action('admin_head','rsvpmaker_editor_css',99);
function rsvpmaker_editor_css() {
echo '<style>'."\n";
echo '
.editor-styles-wrapper div.calendar-background{background-image:url(https://rsvpmaker.com/wp-content/uploads/2016/11/calendar-1600.jpg) !important;background-color: #000 !important; background-repeat:no-repeat;background-attachment:fixed;background-position:centertop;padding-top:15px; !important}
.editor-styles-wrapper .has-blue-background-color{padding: 5px 30px 5px 30px;background-color:#D1DFE4 !important}
';
echo '</style>'."\n";
}

function get_rsvpmail_signup_key () {
	$key = get_option('rsvpmail_signup_key');
	if(empty($key)) {
		$key = wp_generate_password();
		update_option('rsvpmail_signup_key',$key);
	}
	return $key;
}

function rsvpmail_signup_form( $atts = array() ) {
$key = get_rsvpmail_signup_key();
if(empty($atts['fields']))
{
	$fields = '<p>Email<br>
	<input name="email"></p>
	<p>First Name<br>
	<input name="first"></p>
	<p>Last Name<br>
	<input name="last"></p>
';
}
elseif('first' == $atts['fields'])
{
	$fields = '<p>Email<br>
	<input name="email"></p>
	<p>First Name<br>
	<input name="first"></p>
';
}
if('email' == $atts['fields'])
{
	$fields = '<p>Email<br>
	<input name="email"></p>
';
}

$url = rest_url('rsvpmaker/v1/rsvpmailer_signup/'.$key);
return "
<form id=\"email_signup_form\" method=\"post\" 
  action=\"$url\">
$fields
<p><button>Submit</button></p>
</form>
<div id=\"signup_message\"></div>
<script>
const form = document.getElementById('email_signup_form');
const message = document.getElementById('signup_message');
console.log(message.innerHTML);
form.addEventListener('submit', function(e) {
    // Prevent default behavior:
    e.preventDefault();
    // Create payload as new FormData object:
    const payload = new FormData(form);
    // Post the payload using Fetch:
    fetch('$url', {
    method: 'POST',
    body: payload,
    })
    .then(res => res.json())
    .then(data => showMessage(data))
})
function showMessage(data) {
message.innerHTML = data.message;
if(data.success)
form.style.display = 'none';
}
</script>";
}
