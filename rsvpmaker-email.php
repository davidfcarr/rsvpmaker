<?php
function rsvpmailer($mail) {
	global $post, $rsvp_options, $unsubscribed;
	if(defined('RSVPMAILOFF'))
	{
		$log = sprintf('<p style="color:red">RSVPMaker Email Disabled</p><pre>%s</pre>',var_export($mail,true));
		//rsvpmaker_debug_log($log,'disabled email');
		return;
	}
	if(strpos($mail['to'],'@example.com'))
		return; // don't try to send to fake addresses
	if(empty($unsubscribed))
	$unsubscribed = get_option('rsvpmail_unsubscribed');
	if(empty($unsubscribed)) $unsubscribed = array();
	//rsvpmaker_debug_log($unsubscribed,'testing unsub list vs '.$mail['to']);
	if(in_array(strtolower($mail['to']),$unsubscribed)) {
		$mail['html'] = '[content omitted]';
		rsvpmaker_debug_log($mail,'rsvpmailer blocked sending to unsubscribed email');
		return $mail['to'].' sending blocked - unsubscribed list';
	}
	
	if(empty($rsvp_options["from_always"]) && !empty($rsvp_options["smtp_useremail"]))
		$rsvp_options["from_always"] = $rsvp_options["smtp_useremail"];
	
	$site_url = get_site_url();
	$p = explode('//',$site_url);
	$via = $p[1];
	if(empty($mail['fromname']))
		$mail['fromname'] = get_bloginfo('name');

	if(!empty($rsvp_options['from_always']) && ($rsvp_options['from_always'] != $mail['from']))
	{
		$mail['replyto'] = $mail['from'];
		$mail['from'] = $rsvp_options['from_always'];
	}

	if(!strpos($mail['fromname'],'(via'))
		$mail['fromname'] = $mail['fromname'] . ' (via '.$via.')';

	if(!empty($rsvp_options["log_email"]) && isset($post->ID))
		add_post_meta($post->ID, '_rsvpmaker_email_log',$mail);
	$rsvp_options = apply_filters('rsvp_email_options',$rsvp_options);
	if(empty($mail['html']))
	$mail['html'] = wpautop($mail['text']);
	if(empty($mail['text']))
	$mail['text'] = strip_tags($mail['html']);

	if(!strpos($mail['text'],'rsvpmail_unsubscribe'))
		$mail['text'] .= "\n\nUnsubscribe from email notifications\n".site_url('?rsvpmail_unsubscribe='.$mail['to']);

	if(!strpos($mail['html'],'/html>'))
		$mail['html'] = "<html><body>\n".$mail['html']."\n</body></html>";		
	if(!strpos($mail['html'],'rsvpmail_unsubscribe'))
		$mail['html'] = str_replace('</html>',"\n<p>".sprintf('Unsubscribe from email notifications<br /><a href="%s">%s</a></p>',site_url('?rsvpmail_unsubscribe='.$mail['to']),site_url('?rsvpmail_unsubscribe='.$mail['to'])).'</html>',$mail['html']);
	
	if(function_exists('rsvpmailer_override'))
		return rsvpmailer_override($mail);
	
	if(!isset($rsvp_options["smtp"]) || empty($rsvp_options["smtp"]))
		{
		$to = $mail["to"];
		$subject = $mail["subject"];
		if(!empty($mail["html"]))
			{
			$mail["html"] = str_replace('*|UNSUB|*',site_url('?rsvpmail_unsubscribe='.$to),$mail["html"]);
			
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
		$rsvpmail->SMTPSecure = $rsvp_options["smtp_prefix"];                 // sets the prefix to the server
	$rsvpmail->Port=$rsvp_options["smtp_port"];
	}
 	
	}
	
 $rsvpmail->Username= (!empty($rsvp_options["smtp_username"]) ) ? $rsvp_options["smtp_username"] : '';
 $rsvpmail->Password= (!empty($rsvp_options["smtp_password"]) ) ? $rsvp_options["smtp_password"] : '';
 $rsvpmail->AddAddress($mail["to"]);
 if(isset($mail["cc"]) )
	 $rsvpmail->AddCC($mail["cc"]);
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
	
	try {
		$rsvpmail->Send();
	} catch (phpmailerException $e) {
		echo $e->errorMessage();
	} catch (Exception $e) {
		echo $e->getMessage(); //Boring error messages from anything else!
	}
	return $rsvpmail->ErrorInfo;
}

/*
mailchimp api version3 wrapper by Drew McLellan
https://github.com/drewm/mailchimp-api
*/

/**
 * Super-simple, minimum abstraction MailChimp API v3 wrapper
 * MailChimp API v3: http://developer.mailchimp.com
 * This wrapper: https://github.com/drewm/mailchimp-api
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.2
 */
class MailChimpRSVP
{
    private $api_key;
    private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';
    private $data_center;
	/*  SSL Verification
        Read before disabling:
        http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
    */
    public $verify_ssl = true;
    private $request_successful = false;
    private $last_error         = '';
    private $last_response      = array();
    private $last_request       = array();
    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     * @throws \Exception
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        if (strpos($this->api_key, '-') === false) {
            throw new \Exception('Invalid MailChimp API key supplied.');
        }
        list(, $data_center) = explode('-', $this->api_key);
        $this->api_endpoint  = str_replace('<dc>', $data_center, $this->api_endpoint);
        $this->last_response = array('headers' => null, 'body' => null);
		$this->data_center = $data_center;
    }
	/* when saved as draft, direct user to url for draft */
	public function draft_link() {
	return 'https://'.$this->data_center.'.admin.mailchimp.com/campaigns/';
	}

    /**
     * Create a new instance of a Batch request. Optionally with the ID of an existing batch.
     * @param string $batch_id Optional ID of an existing batch, if you need to check its status for example.
     * @return Batch            New Batch object.
     */
	public function new_batch($batch_id = null)
    {
        return new Batch($this, $batch_id);
    }
    /**
     * Convert an email address into a 'subscriber hash' for identifying the subscriber in a method URL
     * @param   string $email The subscriber's email address
     * @return  string          Hashed version of the input
     */
    public function subscriberHash($email)
    {
        return md5(strtolower($email));
    }
    /**
     * Was the last request successful?
     * @return bool  True for success, false for failure
     */
    public function success()
    {
        return $this->request_successful;
    }
    /**
     * Get the last error returned by either the network transport, or by the API.
     * If something didn't work, this should contain the string describing the problem.
     * @return  array|false  describing the error
     */
    public function getLastError()
    {
        return $this->last_error;
    }
    /**
     * Get an array containing the HTTP headers and the body of the API response.
     * @return array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
    /**
     * Get an array containing the HTTP headers and the body of the API request.
     * @return array  Assoc array
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }
    /**
     * Make an HTTP DELETE request - for deleting data
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (if any)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function delete($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }
    /**
     * Make an HTTP GET request - for retrieving data
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function get($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }
    /**
     * Make an HTTP PATCH request - for performing partial updates
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function patch($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }
    /**
     * Make an HTTP POST request - for creating and updating items
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }
    /**
     * Make an HTTP PUT request - for creating new items
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function put($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }
    /**
     * Performs the underlying HTTP request. Not very exciting.
     * @param  string $http_verb The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method The API method to be called
     * @param  array $args Assoc array of parameters to be passed
     * @param int $timeout
     * @return array|false Assoc array of decoded result
     * @throws \Exception
     */
    private function makeRequest($http_verb, $method, $args = array(), $timeout = 10)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }
        $url = $this->api_endpoint . '/' . $method;
        $this->last_error         = '';
        $this->request_successful = false;
        $response                 = array('headers' => null, 'body' => null);
        $this->last_response      = $response;
        $this->last_request = array(
            'method'  => $http_verb,
            'path'    => $method,
            'url'     => $url,
            'body'    => '',
            'timeout' => $timeout,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: apikey ' . $this->api_key
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/MailChimp-API/3.0 (github.com/drewm/mailchimp-api)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
                break;
            case 'get':
                $query = http_build_query($args);
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }
        $response['body']    = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        if (isset($response['headers']['request_header'])) {
            $this->last_request['headers'] = $response['headers']['request_header'];
        }
        if ($response['body'] === false) {
            $this->last_error = curl_error($ch);
        }
        curl_close($ch);
        $formattedResponse = $this->formatResponse($response);
        $this->determineSuccess($response, $formattedResponse);
        return $formattedResponse;
    }
    /**
     * Encode the data and attach it to the request
     * @param   resource $ch cURL session handle, used by reference
     * @param   array $data Assoc array of data to attach
     */
    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }
    /**
     * Decode the response and format any error messages for debugging
     * @param array $response The response from the curl request
     * @return array|false    The JSON decoded into an array
     */
    private function formatResponse($response)
    {
        $this->last_response = $response;
        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }
        return false;
    }
    /**
     * Check if the response was successful or a failure. If it failed, store the error.
     * @param array $response The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     * @return bool     If the request was successful
     */
    private function determineSuccess($response, $formattedResponse)
    {
        $status = $this->findHTTPStatus($response, $formattedResponse);
        if ($status >= 200 && $status <= 299) {
            $this->request_successful = true;
            return true;
        }
        if (isset($formattedResponse['detail'])) {
            $this->last_error = sprintf('%d: %s', $formattedResponse['status'], $formattedResponse['detail']);
            return false;
        }
        $this->last_error = 'Unknown error, call getLastResponse() to find out what happened.';
        return false;
    }
    /**
     * Find the HTTP status code from the headers or API response body
     * @param array $response The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     * @return int  HTTP status code
     */
    private function findHTTPStatus($response, $formattedResponse)
    {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int) $response['headers']['http_code'];
        }
        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int) $formattedResponse['status'];
        }
        return 418;
    }
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
              if (!empty($saved)) {
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
              $options = $this->get_options();
              
              if (isset($_POST["emailsubmitted"])) {
              		
              		//check security
              		check_admin_referer('email-nonce');
              		
                  //$options = array();
                  foreach ($options as $name => $value)
				  	{
					if(isset($_POST[$name]))
					$options[$name] = $_POST[$name];
				  	}
				  if(empty($_POST['chimp_add_new_users']))
					 $options['chimp_add_new_users'] = false;
                  update_option($this->db_option, $options);

				if(isset($_POST["add_cap"]))
					{
						foreach($_POST["add_cap"] as $role => $type)
							{
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
								remove_rsvpemail_caps_role($role);								
							}
					}
                  
                  echo '<div class="updated fade"><p>'.__('Plugin settings saved - mailing list.','rsvpmaker').'</p></div>';
              }
              
              // URL for form submit, equals our current page
              $action_url = admin_url('options-general.php?page=rsvpmaker-admin.php');
; ?>
<div class="wrap" style="max-width:950px !important;">
	<h3><?php _e('RSVPMaker Email List','rsvpmaker');?></h3>
	<p><?php _e("These settings are related to integration with the MailChimp broadcast email service, as well as RSVPMaker's own functions for broadcasting email to website members or people who have registered for your events.",'rsvpmaker');?></p>			
	<div id="poststuff" style="margin-top:10px;">

	 <div id="mainblock" style="width:710px">
	 
		<div class="dbx-content">
		 	<form name="EmailOptions" action="<?php echo $action_url ; ?>" method="post">
					<input type="hidden" name="emailsubmitted" value="1" /> 
					
					<?php wp_nonce_field('email-nonce'); ?>
					
                    <p><?php _e('Email From','rsvpmaker');?>: 
                      <input type="text" name="email-from" id="email-from" value="<?php echo $options["email-from"]; ?>" />
                    </p>
                    <p><?php _e('Email Name','rsvpmaker');?>: 
                      <input type="text" name="email-name" id="email-name" value="<?php echo $options["email-name"]; ?>" />
                    </p>
                    <p><?php _e('MailChimp API-Key','rsvpmaker');?>: 
                      <input type="text" name="chimp-key" id="chimp-key" value="<?php echo $options["chimp-key"]; ?>" />
                    <br /><a target="_blank" href="http://kb.mailchimp.com/integrations/api-integrations/about-api-keys"><?php _e('Get an API key for MailChimp','rsvpmaker');?></a>
                    </p>
                    <p><?php _e('Default List','rsvpmaker');?>: 
                      <select name="chimp-list" id="chimp-list" ><?php echo mailchimp_list_dropdown($options["chimp-key"], $options["chimp-list"]); ?></select>
                    </p>
                    <p><?php _e('Attempt to Subscribe New WordPress user emails','rsvpmaker');?>: 
                      <input type="checkbox" name="chimp_add_new_users" id="chimp_add_new_users" value="1" <?php echo ($options["chimp_add_new_users"]) ? ' checked="checked" ' : ''; ?> />
                    </p>
                    <p><?php _e('Email to notify on API listSubscribe success/failure (optional)','rsvpmaker');?>: 
                      <input type="text" name="add_notify" id="add_notify" value="<?php echo $options["add_notify"]; ?>" />
                    </p>

                    <p><?php _e('Mailing Address','rsvpmaker');?>: 
                      <input type="text" name="mailing_address" id="mailing_address" value="<?php echo $options["mailing_address"]; ?>" />
                    </p>
                    <p><?php _e('Company','rsvpmaker');?>: 
                      <input type="text" name="company" id="company" value="<?php echo $options["company"]; ?>" />
                    </p>
<h3><?php _e('Who Can Publish and Send Email?','rsvpmaker');?></h3>
<p><?php _e('By default, only the administrator has this right, but you can add it to other roles.','rsvpmaker');?></p>
<?php $allroles = get_editable_roles(  ); 
foreach($allroles as $slug => $properties)
{
if($slug == 'administrator')
	continue;
	echo $properties["name"];
	if(isset($properties["capabilities"]['publish_rsvpemails']))
		printf(' %s <input type="checkbox" name="remove_cap[%s]" value="1" /> %s <br />',__('can publish and send broadcasts','rsvpmaker'),$slug,__('Remove','rsvpmaker'));
	elseif(isset($properties["capabilities"]['edit_rsvpemails']))
		printf(' %s <input type="checkbox" name="remove_cap[%s]" value="1" /> %s <br />',__('can edit draft emails','rsvpmaker'),$slug,__('Remove','rsvpmaker'));
	else
		printf(' %s <input type="radio" name="add_cap[%s]" value="edit" /> %s <input type="radio" name="add_cap[%s]" value="publish" /> %s <br />',__('grant right to','rsvpmaker'),$slug,__('Edit','rsvpmaker'),$slug,__('Publish and Send','rsvpmaker'));
}
?>

              <div class="submit"><input type="submit" name="Submit" value="<?php _e('Update','rsvpmaker');?>" /></div>
			</form>
<p>See also: <a target="_blank" href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template'); ?>">Email Template</a></p>

		</div>
				
	 </div>

	</div>
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

add_filter( 'cron_schedules', 'rsvpmaker_add_weekly_schedule' ); 
function rsvpmaker_add_weekly_schedule( $schedules ) {
  $schedules['weekly'] = array(
    'interval' => 7 * 24 * 60 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
    'display' => __( 'Once Weekly', 'rsvpmaker' )
  );
  return $schedules;
}

function rsvpmaker_next_scheduled( $post_id, $returnint = false ) {
	global $rsvp_options;
	global $rsvpnext_time;
	if($returnint && !empty($rsvpnext_time[$post_id]))
		return $rsvpnext_time[$post_id];
	fix_timezone();
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
						return utf8_encode(strftime($rsvp_options["long_date"].' '.$rsvp_options["time_format"],$timestamp)).' '.$schedule;
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
	<div id="icon-edit" class="icon32"><br /></div>
<h2><?php _e('Scheduled Email','rsvpmaker'); ?>  </h2> 
<p><?php _e('Use this screen to create or edit a schedule for sending your email at a specific date and time or on a recurring schedule.','rsvpmaker'); ?></p>
<?php

	if(isset($_REQUEST['post_id']))
	{
		$post = get_post($_REQUEST['post_id']);
		printf('<h3>Email Post: %s</h3><p><a href="post.php?action=edit&post=%s">Edit Post</a> | <a href="%s">View Post</a></p>',$post->post_title,$post->ID,get_permalink($post->ID));
		printf('<form action="%s" method="post">',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id=').$post->ID);
		echo '<input type="hidden" name="post_id" value="'.$post->ID.'" />';
		RSVPMaker_draw_blastoptions();
		echo '<button>Save</button></form>';
	}
	else {
?>
<form method="get" action="edit.php"><input type="hidden" name="post_type" value="rsvpemail" /><input type="hidden" name="page" value="rsvpmaker_scheduled_email_list" /><h3><?php _e('Choose a RSVP Mailer Post','rsvpmaker'); ?></h3>
	<select name="post_id"><?php
$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type='rsvpemail' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC ";
$results = $wpdb->get_results($sql);
foreach($results as $row)
{
	printf('<option value="%d">%s</option>',$row->ID,$row->post_title);
}
		  ?></select>
<button>Get</button>
</form>
<?php
	}

	fix_timezone();
    $crons = _get_cron_array();
    if ( empty($crons) )
        _e('None','rsvpmaker');
	else
	{
	printf('<h3>Scheduled</h3>
	<table  class="wp-list-table widefat fixed posts" cellspacing="0"><thead><tr><th>%s</th><th>%s</th></tr></thead><tbody>',__('Title','rsvpmaker'),__('Schedule','rsvpmaker'));
    foreach ( $crons as $timestamp => $cron ) {
		foreach($cron as $hook => $properties)
			{
			if($hook == 'rsvpmaker_cron_email')
				foreach($properties as $key => $property_array)
					{
					//print_r($property_array);
					$post_id = array_shift($property_array["args"]);
					$post = get_post($post_id);
					if(!empty($post))
						{
						printf('<tr><td>%s <br /><a href="%s">%s</a> | <a href="%s">%s</a></td><td>',$post->post_title,admin_url('post.php?post='.$post_id.'&action=edit'),__('Edit Post','rsvpmaker'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id='.$post_id),__('Schedule Options','rsvpmaker'));
						$schedule = (empty($property_array["schedule"])) ? '' : $property_array["schedule"];
						
						echo utf8_encode(strftime($rsvp_options["long_date"].' '.$rsvp_options["time_format"],$timestamp)).' '.$schedule;
						echo '</td></tr>';
						}
					}
			}
    } // end cron loop
	echo '</table>';
	}
?>
<h3><?php _e('Shortcodes for Scheduled Email Newsletters','rsvpmaker');?></h3>
<p><?php _e('Shortcodes you can include with scheduled email include [rsvpmaker_upcoming] (which should be used without the calendar grid) and these others, intended specifically for newsletter style messages. The attributes are optional and shown with the default values.','rsvpmaker');?></p>
<p>[rsvpmaker_recent_blog_posts weeks=&quot;1&quot;] (<?php _e('shows blog posts published within the timeframe, default 1 week','rsvpmaker');?>)</p>
<p>[rsvpmaker_looking_ahead days=&quot;30&quot; limit=&quot;10&quot;] (<?php _e('include after rsvpmaker_upcoming for a linked listing of just the headlines and dates of events farther out on the schedule','rsvpmaker');?>)</p>
<?php
}

function cron_schedule_options() {
global $post, $wpdb, $rsvp_options;
$event_timestamp = (int) get_post_meta($post->ID,'event_timestamp',true);
$args = array($post->ID);
$cron = get_post_meta($post->ID,'rsvpmaker_cron_email',true);
$notekey = get_rsvp_notekey();
$chimp_options = get_option('chimp');
fix_timezone();
$ts = rsvpmaker_next_scheduled($post->ID);
if(empty($ts))
	{
	echo '<p>Next broadcast: NOT SET</p>';
	$timestamp = strtotime('+1 hour');
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
<p><?php if($chimp_options["chimp-key"]) { ?> <input type="checkbox" name="cron_mailchimp" value="1"  <?php if(!empty($cron["cron_mailchimp"])) echo 'checked="checked"' ?> > <?php echo __('Send to MailChimp List','rsvpmaker'); } ?> <input type="checkbox" name="cron_members" value="1"  <?php if(!empty($cron["cron_members"])) echo 'checked="checked"' ?> > <?php echo __('Send to Website Members','rsvpmaker');?><br />
<?php echo __('Send to This Address','rsvpmaker');?>: <input type="text" name="cron_to" value="<?php if(!empty($cron['cron_to'])) echo $cron['cron_to']; ?>" />
</p>
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
<?php _e('Recurrence','rsvpmaker');?> <select name="cronrecur"><option value=""><?php echo __('None','rsvpmaker');?></option>
<?php
$sked_meta = (empty($cron["cronrecur"])) ? ''  : $cron["cronrecur"];
$schedules = array('weekly','daily');
foreach ($schedules as $sked)
	{
	$selected = ($sked == $sked_meta) ? ' selected="selected" ' : '';
	printf('<option  value="%s" %s>%s</option>',$sked,$selected,$sked);
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
			
		$evopt .= '<option value="'.$reminder.'"'.$s.'>'.strftime($rsvp_options['short_date'].' '.$rsvp_options['time_format'],$reminder).$dtext.'</option>';
	}
	$checked = (!empty($cron["cron_active"]) && ($cron["cron_active"]) == "relative") ? 'checked="checked"' : '';
printf('<p><input type="radio" name="cron_active" value="relative" '.$checked.' /> Set reminder relative to event %s<br /><select name="cron_relative">%s</select></p>',strftime($rsvp_options['short_date'].' '.$rsvp_options['time_format'],$event_timestamp),$evopt);
}
$checked = (!empty($cron["cron_active"]) && ($cron["cron_active"]) == "strtotime") ? 'checked="checked"' : '';
$timestring = ($ts) ? date('Y-m-d H:i:s',$ts) : date('Y-m-d H:00:00',strtotime('+ 1 hour'));
?>
<p><input type="radio" name="cron_active" value="strtotime" <?php echo $checked; ?> /> Custom date time string <input type="text" name="cron_strtotime" value="<?php echo $timestring; ?>" /></p>
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
<?php _e('Preview','rsvpmaker');?> <select name="cron_preview"><?php echo $preview_options; ?></select>
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
<?php _e('Test for','rsvpmaker');?>: <select name="cron_condition"><?php echo $condition_options; ?></select>
<br /><em><?php _e('Broadcast will not be sent if it does not meet this test.','rsvpmaker');?></em>
</p>
<?php
	
$chosen = (int) get_post_meta($post->ID,$notekey,true);
$editorsnote["add_to_head"] = $editorsnote["note"] = '';

$recent = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='post' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC LIMIT 0,20");
if(!empty($recent))
foreach($recent as $blog)
	{
	$s = ($blog->ID == $chosen) ? ' selected="selected"' : '';
	if($blog->ID == $chosen)
		$chosentitle = $blog->post_title;
	$title = ($blog->post_status == 'draft') ? $blog->post_title. ' (draft)' : $blog->post_title;
	$blog_options .= sprintf('<option value="%d" %s>%s</option>',$blog->ID,$s,$title);
	}

if($chosen)
{
	$blog = get_post($chosen);
	$chosentitle = $blog->post_title;
	$blog_options .= sprintf('<option value="%d" selected="selected">%s</option><option value="">(Clear Selection)</option>',$blog->ID,$blog->post_title);
	printf('<p>The current editor\'s note is based on the blog post <strong>%s</strong>. <a href="%s">(Edit)</a></p>',$chosentitle,admin_url('post.php?action=edit&post='.$chosen));
	echo "<p>notekey $notekey</p>";
}
?>
<h3 id="editorsnote"><?php _e("Add Editor's Note for",'rsvpmaker'); if(empty($stamp)) echo ' Next broadcast'; else echo ' '.$ts;?></h3>
<input type="hidden" name="notekey" value="<?php echo $notekey; ?>">

<p><?php _e("A blog post, either public or draft, can be featured as the editor's note at the top of your next email newsletter broadcast. The content of the post title will be added to the end of the email subject line, and the content of the post (up to the more tag, if included) will be included in the body of your email. There are two ways to add an Editor's Note blog post.",'rsvpmaker');?></p>

<p><input type="radio" name="status" value="" checked="checked" /> <strong><?php _e('Pick a blog post to feature','rsvpmaker');?>:</strong> <select name="chosen"><option value=""><?php _e('Select Blog Post','rsvpmaker');?></option><?php echo $blog_options; ?></select></p>

	<p><input type="radio" name="status" value="draft" /> <strong>Create a draft</strong> based on the headline and message below <br /><input type="radio" name="status" value="publish" /> <strong>Create and publish</strong> blog based on the headline and message below</strong><br /> <em>(<?php _e('This post will be used as the editors note at the top of your broadcast. Making it public on the blog is optional.','rsvpmaker');?>)</p>

<p><?php _e('Title/Subject','rsvpmaker');?>: <input type="text" name="notesubject" value="" /></p>
<p>Message:<br />
<textarea cols="100" rows="5" name="notebody"></textarea></p>

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
<tr><td><?php _e('From Name','rsvpmaker');?>:</td><td><input type="text"  size="80" name="scheduled_email[email-name]" value="<?php echo $scheduled_email["email-name"]; ?>" /></td></tr>
<tr><td><?php _e('From Email','rsvpmaker');?>:</td><td><input type="text" size="80"  name="scheduled_email[email-from]" value="<?php echo $scheduled_email["email-from"]; ?>" /></td></tr>
<tr><td><?php _e('Preview To','rsvpmaker');?>:</td><td><input type="text" size="80" name="scheduled_email[preview_to]" value="<?php echo $scheduled_email['preview_to']; ?>" /></td></tr>
</table>

<p><?php _e('MailChimp List','rsvpmaker');?> <select name="scheduled_email[list]">
<?php
$chosen = (isset($scheduled_email["list"])) ? $scheduled_email["list"] : $chimp_options["chimp-list"];
echo mailchimp_list_dropdown($chimp_options["chimp-key"], $chosen);
?>
</select></p>

<?php
if(current_user_can('publish_rsvpemails'))
	cron_schedule_options();
}

function RSVPMaker_email_notice () {
global $post;
?>
	<div><h3>Email Editor</h3><p>Use the WordPress editor to compose the body of your message, with the post title as your subject line. <a href="<?php echo get_permalink($post->ID); ?>">View Post</a> will display your content in an email template, with a user interface for addressing options.</p>
<p>See also <a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_scheduled_email_list&post_id=').$post->ID; ?>">Scheduled email options</a></p>
</div><?php
}

function my_rsvpemails_menu() {
if(!function_exists('do_blocks'))
add_meta_box( 'BlastBox', 'RSVPMaker Email Options', 'RSVPMaker_email_notice', 'rsvpemail', 'normal', 'high' );
}

add_action('admin_init','save_rsvpemail_data');

function save_rsvpemail_data() {

if(empty($_POST) || empty($_REQUEST['post_id']) || empty($_REQUEST['page']) || ($_REQUEST['page'] != 'rsvpmaker_scheduled_email_list'))
	return;
$postID = $_REQUEST['post_id'];

if(isset($_POST['scheduled_email']))
{
	update_post_meta($postID,'scheduled_email',$_POST['scheduled_email']);
}

if(!empty($_POST["email"]["from_name"]))
	{
	global $wpdb;
	global $current_user;
			
		$ev = $_POST["email"];
		if(empty($ev["headline"]))
			$ev["headline"] = 0;
		foreach($ev as $name => $value)
			{
			$field = '_email_'.$name;
			$single = true;
			$current = get_post_meta($postID, $field, $single);
			 
			if($value && ($current == "") )
				add_post_meta($postID, $field, $value, true);
			
			elseif($value != $current)
				update_post_meta($postID, $field, $value);
			
			elseif($value == "")
				delete_post_meta($postID, $field, $current);
			}
	}
	if(isset($_POST["cron_active"]) || !empty($_POST["cron_relative"])) {
	$chosen = (int) $_POST["chosen"]; 
	if(empty($_POST['cronday']))
	{
		$cronday = (int) $_POST['cronday'];
		$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		$day = $days[$cronday];
	}
	if(!empty($_POST['notesubject']) || !empty($_POST['notebody']))
	{
		global $current_user;
		$newpost['post_title'] = stripslashes($_POST['notesubject']);
		$newpost['post_content'] = rsvpautog(stripslashes($_POST['notebody']));
		$newpost['post_type'] = 'post';
		$newpost['post_status'] = $_POST['status'];
		$newpost['post_author'] = $current_user->ID;
		$chosen = wp_insert_post( $newpost );
	}
	
	if(!empty($_POST['notekey']))	
		update_post_meta($postID,$_POST['notekey'],$chosen);
	//rsvpmaker_debug_log($_POST,'schedule email message');
	//rsvpmaker_debug_log($chosen,'chosen schedule email message');
	$args = array('post_id' => $postID);
	$cron_checkboxes = array("cron_active", "cron_mailchimp", "cron_members", "cron_preview");
	foreach($cron_checkboxes as $check)
		{
			$cron[$check] = (isset($_POST[$check])) ? $_POST[$check] : 0;
		}
	$cron['cron_to'] = $_POST['cron_to'];
	//clear if previously set
	wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
	wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
	update_post_meta($postID,'rsvpmaker_cron_email',$cron);

	if($cron["cron_active"] == '1')
		{
			$cron_fields = array("cronday", "cronhour", "cronrecur","cron_condition");
			foreach($cron_fields as $field)
				$cron[$field] = $_POST[$field];
			fix_timezone();
			$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
			$t = strtotime($days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
			if($t < time())
				$t = strtotime('next '. $days[$cron["cronday"]] .' '.$cron["cronhour"].':00');
		}
	elseif(($cron["cron_active"] == 'relative') && !empty($_POST["cron_relative"]))
		$t = (int) $_POST["cron_relative"];
	elseif(($cron["cron_active"] == 'strtotime') && !empty($_POST["cron_strtotime"])) {
		fix_timezone();
		$t = strtotime($_POST["cron_strtotime"]);
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
		delete_post_meta($postID,'rsvpmaker_cron_email');
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email', $args );
		wp_clear_scheduled_hook( 'rsvpmaker_cron_email_preview', $args );
		}
	
	header('Location: ' . site_url($_SERVER['REQUEST_URI']));
	die();
	//$message = var_export($args,true).var_export($_POST,true);
	}
}

function rsvpevent_to_email () {
global $current_user;
if(!empty($_GET["rsvpevent_to_email"]) || !empty($_GET["post_to_email"]))
	{
		if(!empty($_GET["post_to_email"]))
			{
				$id = $_GET["post_to_email"];
				$post = get_post($id);
				$content = $post->post_content;
				$title = $post->post_title;
			}
		else
		{
			$id = $_GET["rsvpevent_to_email"];
		if(is_numeric($id))
			{
				if(empty($content))
					$content = '<!-- wp:rsvpmaker/event {"post_id":"'.$id.'","one_format":"button"} /-->';
				$title = get_the_title($id);
				$date = get_rsvp_date($id);		
				if($date) {
				fix_timezone();
				$t = strtotime($date);
				global $rsvp_options;
				$title .= ' - '.strftime($rsvp_options["short_date"],$t);			
				}
			}
		elseif($id == 'upcoming') {
			$content .= '<!-- wp:rsvpmaker/upcoming {"posts_per_page":"20","hideauthor":"true"} /-->';
			$title = 'Upcoming Events';
		}
		else
			return;
		}
		$my_post['post_title'] = $title;
		$my_post['post_content'] = $content;
		$my_post['post_type'] = 'rsvpemail';
		$my_post['post_status'] = 'publish';
		$my_post['post_author'] = $current_user->ID;
		if($postID = wp_insert_post( $my_post ) )
			{
			if(!empty($t))
				add_post_meta($postID,'event_timestamp',$t);
			$loc = admin_url("post.php?action=edit&post=".$postID);
			wp_redirect($loc);
			die();
			}
	}
}



function create_rsvpemail_post_type() {
global $rsvp_options;
  register_post_type( 'rsvpemail',
    array(
      'labels' => array(
        'name' => __( 'RSVP Mailer','rsvpmaker' ),
        'add_new_item' => __( 'Add New Email','rsvpmaker' ),
        'edit_item' => __( 'Edit Email','rsvpmaker' ),
        'new_item' => __( 'RSVP Emails','rsvpmaker' ),
        'singular_name' => __( 'RSVP Email','rsvpmaker' )
      ),
	'public' => true,
	'exclude_from_search' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'query_var' => true,
    'rewrite' => true,
    'capabilities' => array(
        'edit_post' => 'edit_rsvpemail',
        'edit_posts' => 'edit_rsvpemails',
        'edit_others_posts' => 'edit_others_rsvpemails',
        'publish_posts' => 'publish_rsvpemails',
        'read_post' => 'read_rsvpemail',
        'read_private_posts' => 'read_private_rsvpemails',
        'delete_post' => 'delete_rsvpemail'
    ),
    'hierarchical' => false,
    'menu_position' => 20,
	'menu_icon' => 'dashicons-email-alt',
    'supports' => array('title','editor'),
	'show_in_rest' => true
    )
  );
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
function rsvpemail_template_redirect()
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

function rsvpmaker_text_version($content, $chimpfooter_text)
{
//match text links (not link around image, which would start with <)
$content = str_replace('*|MC:SUBJECT|*','',$content);
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

function rsvpmaker_personalize_email($content,$to,$description = '') {
$chimp_options = get_option('chimp');
global $post;
$content = str_replace('*|EMAIL|*',$to,$content);
$content = str_replace('*|UNSUB|*',site_url('?rsvpmail_unsubscribe='.$to),$content);
$content = str_replace('*|REWARDS|*','',$content);
$content = str_replace('*|LIST:DESCRIPTION|*',$description,$content);
$content = str_replace('*|LIST:ADDRESS|*',$chimp_options['mailing_address'],$content);
$content = str_replace('*|HTML:LIST_ADDRESS_HTML|*',$chimp_options['mailing_address'],$content);
$content = str_replace('*|LIST:COMPANY|*',$chimp_options['company'],$content);
$content = str_replace('*|CURRENT_YEAR|*',date('Y'),$content);
$content = str_replace('*|ARCHIVE|*',get_permalink($post->ID),$content);
$content = preg_replace('/\*\|.+\|\*/','',$content); // not recognized, get rid of it.

$content = str_replace(' | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>','',$content);
$content = str_replace('Forward to a friend:
*|FORWARD|*','',$content);
$content = str_replace('Update your profile:
*|UPDATE_PROFILE|*','',$content);
return $content;	
}

function rsvpmaker_email_send_ui($chimp_html, $chimp_text, $rsvp_html, $rsvp_text)
{
global $post;
global $custom_fields;
global $wpdb;
global $current_user;
global $unsubscribed;
global $rsvpmaker_cron_context;
global $rsvp_options;
if(!empty($rsvpmaker_cron_context))
	return;
$chimp_options = get_option('chimp');

ob_start();

if(!current_user_can('publish_rsvpemails') )
	return;

$chimp_options = get_option('chimp');

if(!empty($_POST["preview"]))
	{
	$previewto = trim($_POST["previewto"]);
	if(is_email($previewto))
		{
		echo '<p>Sending preview to '.$previewto.'</p>';
		$mail["to"] = $previewto;
		$mail["from"] = (isset($_POST["user_email"])) ? $current_user->user_email : $_POST["from_email"];
		$mail["fromname"] =  stripslashes($_POST["from_name"]);
		$mail["subject"] =  stripslashes($_POST["subject"]);
		$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],__('You were sent this message as a preview','rsvpmaker'));
		$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],__('You were sent this message as a preview','rsvpmaker'));
		echo $result = rsvpmailer($mail);
		}
	else
		echo '<div style="color:red;">Error: '.$previewto.' - '.__('Error, not a single valid email address','rsvpmaker').'</div>';
		
	}

if(!empty($_POST["attendees"]) && !empty($_POST["event"]))
{
$unsub = get_option('rsvpmail_unsubscribed');
if(empty($unsub)) $unsub = array();

if($_POST["event"] == 'any')
{
$sql = "SELECT DISTINCT email 
FROM  `".$wpdb->prefix."rsvpmaker`";
$title = 'one of our previous events';	
}
else {
$event = (int) $_POST["event"];
$event_post = get_post($event);
$sql = "SELECT * 
FROM  `".$wpdb->prefix."rsvpmaker` 
WHERE  `event` = ".$event." ORDER BY  `email` ASC";
$title = $event_post->post_title;
}
$results = $wpdb->get_results($sql);
if(!empty($results))
{
echo '<p>'.__('Sending to','rsvpmaker').' '.sizeof($results).' '. __('event attendees','rsvpmaker').'</p>';
foreach($results as $row)
	{
	if(in_array(strtolower($row->email),$unsub))
		{
			$unsubscribed[] = $row->email;
			continue;
		}
	$mail["to"] = $row->email;
	$mail["from"] = (isset($_POST["user_email"])) ? $current_user->user_email : $_POST["from_email"];
	$mail["fromname"] =  stripslashes($_POST["from_name"]);
	$mail["subject"] =  stripslashes($_POST["subject"]);
	$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$title);
	$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$title);
	rsvpmailer($mail);
	}
}

}

if(!empty($_POST["rsvps_since"]) && !empty($_POST["since"]))
{
$unsub = get_option('rsvpmail_unsubscribed');
if(empty($unsub)) $unsub = array();

$t = strtotime('-'.$_POST["since"].' days');

$date = date('Y-m-d',$t);

$sql = "SELECT DISTINCT email 
FROM  `".$wpdb->prefix."rsvpmaker` WHERE `timestamp` > '$date'";
$title = 'one of our previous events';

$results = $wpdb->get_results($sql);
if(!empty($results))
{
echo '<p>'.__('Sending to','rsvpmaker').' '.sizeof($results).' '. __('RSVPs within the last ','rsvpmaker').' '.$_POST["since"].' days</p>';
foreach($results as $row)
	{
	if(in_array(strtolower($row->email),$unsub))
		{
			$unsubscribed[] = $row->email;
			continue;
		}
	$mail["to"] = $row->email;
	$mail["from"] = (isset($_POST["user_email"])) ? $current_user->user_email : $_POST["from_email"];
	$mail["fromname"] =  stripslashes($_POST["from_name"]);
	$mail["subject"] =  stripslashes($_POST["subject"]);
	$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$title);
	$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$title);
	rsvpmailer($mail);
	}
}

}	
	
if(!empty($_POST["members"]))
{
$users = get_users();
printf('<p>Sending to %s website members</p>',sizeof($users));
$unsub = get_option('rsvpmail_unsubscribed');
if(empty($unsub)) $unsub = array();
foreach($users as $user)
	{
	if(is_array($unsub) && in_array(strtolower($user->user_email),$unsub))
		{
			$unsubscribed[] = $user->user_email;
			continue;
		}
	$mail["to"] = $user->user_email;
	$mail["from"] = (isset($_POST["user_email"])) ? $current_user->user_email : $_POST["from_email"];
	$mail["fromname"] =  stripslashes($_POST["from_name"]);
	$mail["subject"] =  stripslashes($_POST["subject"]);
	$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],__('This message was sent to you as a member of','rsvpmaker').' '.$_SERVER['SERVER_NAME']);
	$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],__('This message was sent to you as a member of','rsvpmaker').' '.$_SERVER['SERVER_NAME']);
	$result = rsvpmailer($mail);
	if(strpos($result,'ailed'))
		{
			echo $result;
			break;
		}
	}
}

if(!empty($_POST["mailchimp"]))
{
$MailChimp = new MailChimpRSVP($chimp_options["chimp-key"]);
$listID = $_POST["mailchimp_list"];
update_post_meta($post->ID, "_email_list",$listID);
$custom_fields["_email_list"][0] = $listID;

$segment_opts = array();	

if(!empty($_POST["mailchimp_exclude_rsvp"]))
{
$event = (int) $_POST["mailchimp_exclude_rsvp"];	
$sql = "SELECT * 
FROM  `".$wpdb->prefix."rsvpmaker` 
WHERE  `event` = ".$event;
$results = $wpdb->get_results($sql);
foreach($results as $row)
	$rsvped[] = array('field' => 'EMAIL','condition_type' => 'EmailAddress','op' => 'not','value' => $row->email);
if(!empty($rsvped))
	$segment_opts = array('match' => 'all','conditions' => $rsvped );
/*	
$segment_id = get_post_meta($event, 'mailchimp_rsvp_segment', true);
if(empty($segment_id))
{
$segment_result = $MailChimp->post("lists/".$listID.'/segments', array('name' => $slug,'static_segment' => $rsvped));
echo '<pre>';
print_r($segment_result);
echo '</pre>';
if($segment_result->id)
	{
		$segment_id = $segment_result->id;
		update_post_meta($event,'mailchimp_rsvp_segment',$segment_id);
	}
}
else
	{
	$bulk = $MailChimp->post("lists/".$listID.'/segments/'.$segment_id, array('members_to_add' => $rsvped));
echo '<pre>';
print_r($bulk);
echo '</pre>';
	}
*/
}

$input = array(
                'type' => 'regular',
                'recipients'        => array('list_id' => $listID),
                'segment_opts'        => $segment_opts,
				'settings' => array('subject_line' => stripslashes($_POST["subject"]),'from_email' => $_POST["from_email"], 'from_name' => $_POST["from_name"], 'reply_to' => $_POST["from_email"])
);

rsvpmaker_debug_log(json_encode($input),'mailchimp request');

$campaign = $MailChimp->post("campaigns", $input);
if(!$MailChimp->success())
	{
	echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
	return;
	}
else {
	rsvpmaker_debug_log($campaign,'mailchimp result');
}
if(!empty($campaign["id"]))
{
$content_result = $MailChimp->put("campaigns/".$campaign["id"].'/content', array(
'html' => $chimp_html, 'text' => $chimp_text) );
if(!$MailChimp->success())
	{
	echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
	return;
	}
if(empty($_POST["chimp_send_now"]))
	{
	$link = $MailChimp->draft_link();
	echo '<div><a target="_blank" href="'.$link.'">'.__('View draft','rsvpmaker').'</a> '. __('on mailchimp.com','rsvpmaker').'</div>';
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

if(!empty($_POST))
	do_action("rsvpmaker_email_send_ui_submit",$_POST, $rsvp_html, $rsvp_text);

// $unsubscribed is global, can be modified by action above
if(!empty($unsubscribed))
	printf('<p>%s: %s',__('Skipped unsubscribed emails','rsvpmaker'),implode(', ',$unsubscribed) );

$permalink = get_permalink($post->ID);
$edit_link = get_edit_post_link($post->ID);

; ?>
<form method="post" action="<?php echo $permalink; ?>">

<table>
<tr><td><?php _e('Subject','rsvpmaker');?>:</td><td><input type="text"  size="50" name="subject" value="<?php echo htmlentities($post->post_title); ?>" /></td></tr>
<tr><td><?php _e('From Name','rsvpmaker');?>:</td><td><input type="text"  size="50" name="from_name" value="<?php echo (isset($custom_fields["_email_from_name"][0])) ? $custom_fields["_email_from_name"][0] : $chimp_options["email-name"]; ?>" /></td></tr>
<tr><td><?php _e('From Email','rsvpmaker');?>:</td><td><input type="text" size="50"  name="from_email" value="<?php echo (isset($custom_fields["_email_from_email"][0])) ? $custom_fields["_email_from_email"][0] : $chimp_options["email-from"]; ?>" />
</td></tr>
</table>
<div><input type="checkbox" name="user_email" value="1" checked="checked" /><?php _e('Except for MailChimp, use the email of the logged in user as from email.','rsvpmaker'); ?></div>

<p><strong>Stylesheets to Include</strong></p>
<?php	
$email_style_handles = get_option('email_style_handles');
if(empty($email_style_handles) || !is_array($email_style_handles))
$email_style_handles = array('wp-block-library','wp-core-blocks','rsvp_style');

global $wp_styles;
    foreach( $wp_styles->queue as $handle ) :
	if($handle == 'admin-bar')
		continue;
	$c = (in_array($handle,$email_style_handles)) ? ' checked="checked" ': '';
	echo '<div style="width: 48%; float: left;">';
	printf('<input type="checkbox" name="handles[]" value="%s" %s /> %s</div>',$handle,$c,$handle);
    endforeach;		

$events_dropdown = get_events_dropdown ();	
?>
	
<p style="clear:both;"><?php _e('Send','rsvpmaker');?></p>
<div><input type="checkbox" name="preview" value="1"> <?php _e('Preview to','rsvpmaker');?>: <input type="text" name="previewto" value="<?php echo (isset($custom_fields["_email_preview_to"][0])) ? $custom_fields["_email_preview_to"][0] : $chimp_options["email-from"]; ?>" /><br />
<input type="checkbox" name="members" value="1"> <?php _e('Website members','rsvpmaker');?><br />
<?php
if(!empty($chimp_options["chimp-key"]))
{
?>
<input type="checkbox" name="mailchimp" value="1"> <?php _e('MailChimp list','rsvpmaker');?> <select name="mailchimp_list">
<?php
$chosen = (isset($custom_fields["_email_list"][0])) ? $custom_fields["_email_list"][0] : $chimp_options["chimp-list"];
echo mailchimp_list_dropdown($chimp_options["chimp-key"], $chosen);
?>
</select> <select name="chimp_send_now"><option value="1"><?php _e('Send now','rsvpmaker'); ?></option><option value="" <?php if(isset($_POST["mailchimp"]) && empty($_POST["chimp_send_now"])) echo ' selected="selected" '; ?> ><?php _e('Save as draft on mailchimp.com','rsvpmaker'); ?></option></select></div>
<?php if(!empty($rsvp_options['debug']))
{ //only if debug is on, show this feature.
?>
<div style="margin-left: 20px;">
<?php _e('Exclude Recipients who RSVPed to','rsvpmaker');?> <select name="mailchimp_exclude_rsvp">
<option value="">Choose Event</option>
<?php
echo $events_dropdown;
?>
</select>	
</div>	
<?php
} // end if debug
}

?>
	<div><input type="checkbox" name="attendees" value="1"> <?php _e('Attendees','rsvpmaker');?> <select name="event"><option value=""><?php _e('Select Event','rsvpmaker');?></option><option value="any"><?php _e('Any event','rsvpmaker');?></option><?php echo $events_dropdown; ?></select></div>

	<div><input type="checkbox" name="rsvps_since" value="1"> <?php _e('RSVPs more recent than ','rsvpmaker');?> <input type="text" name="since" value="30" /> <?php _e('Days','rsvpmaker');?></div>
<?php
do_action("rsvpmaker_email_send_ui_options");
?>
<p><input type="submit" name="now" value="<?php _e('Send Now','rsvpmaker');?>" /></p>
</form>
<p><a href="<?php echo $edit_link; ?>"><?php _e('Edit','rsvpmaker');?></a> - <a href="<?php echo admin_url(); ?>"><?php _e('Dashboard','rsvpmaker');?></a> - <a href="<?php echo site_url(); ?>"><?php _e('Visit Site','rsvpmaker');?></a></p>
<?php

$ts = rsvpmaker_next_scheduled($post->ID);
if($ts)
	printf('<p><a href="%s">Preview scheduled broadcast</a> for %s',add_query_arg('cronemailpreview',$post->ID,$permalink),$ts);
	
return '<div style="background-color: #FFFFFF; color: #000000;">'.ob_get_clean().'</div>';
}

function rsvpmaker_included_styles () {
global $post;
if(!empty($post->ID))
	$email_style_handles = get_option('email_style_handles');
if(empty($email_style_handles) || !is_array($email_style_handles))
$email_style_handles = array('wp-block-library','wp-core-blocks','rsvp_style');

$styles = '';
do_action('wp_enqueue_scripts'); //both scripts and styles
global $wp_styles;
    foreach( $wp_styles->queue as $handle ) :
	if( in_array($handle, $email_style_handles))
	{
	if(!empty($styles))
		$styles .= "\n";
	$styles .= "/* $handle */\n";
	$res = wp_remote_get($wp_styles->registered[$handle]->src);
	if(is_wp_error($res))
		$styles .= '/*'.var_export($res,true).'*/ ';
	else
	$styles .= $res['body']."\n";		
	}
    endforeach;

$extra_email_styles = get_option('extra_email_styles');
	
if(!empty($extra_email_styles))
	$styles .= "\n/* extra_email_styles */\n".$extra_email_styles;
return $styles;
}

function show_rsvpmaker_included_styles () {
		echo '<pre>';
		echo rsvpmaker_included_styles();
		echo '</pre>';
		die();
}

function RSVPMaker_extract_email() {

global $wpdb;
$inchimp = '';
if(isset($_POST["emails"]))
	{

$chimp_options = get_option('chimp');

$apikey = $chimp_options["chimp-key"];
$listId = $chimp_options["chimp-list"];
 
	preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $_POST["emails"], $emails);
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

; ?>
<div id="icon-options-general" class="icon32"><br /></div>
<h2><?php _e('Extract Emails','rsvpmaker');?></h2>
<p><?php _e('You can enter an disorganized list of emails mixed in with other text, and this utility will extract just the email addresses.','rsvpmaker');?></p>
<form id="form1" name="form1" method="post" action="">

  <p>
    <textarea name="emails" id="emails" cols="45" rows="5"></textarea>
  </p>
  <p><?php _e('Filter out emails that','rsvpmaker');?>:</p>
  <p>
    <input name="in_mailchimp" type="checkbox" id="in_mailchimp" checked="checked" />
  <?php _e('Are Registered in MailChimp','rsvpmaker');?></p>
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
	
function rsvpemail_template () {
?>
<div id="icon-options-general" class="icon32"><br /></div>
<?php

	if(isset($_POST['extra_email_styles']))
		update_option('extra_email_styles',$_POST['extra_email_styles']);
	
	if(!empty($_POST['rsvpmaker_email_template']))
	{
	$templates = $_POST['rsvpmaker_email_template'];
	foreach($templates as $index => $template)
		{
		//echo '<br />template'.$index . ' ';
		//print_r($template);
		$template['html'] = stripslashes($template['html']);
		$template['inline_tags'] = inline_array($template['inline_tags']);
		$template['inline_class'] = inline_array($template['inline_class']);
		//echo '<br />template after inline array'.$index . ' ';
		//print_r($template);
		$templates[$index] = $template;
		}
	update_option('rsvpmaker_email_template',$templates);
	}

?>
<h2><?php _e('RSVPMaker Email Template','rsvpmaker');?></h2>
<form id="form1" name="form1" method="post" action="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template'); ?>">
<?php
global $rsvp_options;

$template = get_rsvpmaker_email_template();

?>
  <p><?php _e('You can create one or more templates for use with your email broadcasts.','rsvpmaker');?></p>
  <p><?php _e('Include the [rsvpmaker_email_content] placeholder wherever your message should appear.','rsvpmaker');?></p>
  <p><?php _e('Other placeholders like *|ARCHIVE|* are MailChimp template codes and will be replaced when your email is broadcast.','rsvpmaker');?></p>
  <p><?php _e('Inline CSS styles work best for formatting content for distribution via email. You can specify CSS styles that should be applied to either tags or classes.','rsvpmaker');?></p>
<?php
foreach($template as $index => $value)
{
; ?>
<div id="temp<?php echo $index; ?>">
  <p>
    <input type="text" name="rsvpmaker_email_template[<?php echo $index; ?>][slug]" id="rsvpmaker_email_template[<?php echo $index; ?>][slug]" value="<?php echo $template[$index]["slug"]; ?>" /> <a href="#" onclick="remove_template(<?php echo $index; ?>); return false;"><?php _e('Remove','rsvpmaker');?></a>
  </p>
  <p>
    <textarea name="rsvpmaker_email_template[<?php echo $index; ?>][html]" id="rsvpmaker_email_template[<?php echo $index; ?>][html]" cols="80" rows="10"><? echo $template[$index]["html"]; ?></textarea>
  </p>
  
</div>
<?php
}
$index++;
; ?>
<div id="add_template"><button id="addtemp"> (+) <?php _e('Add another template','rsvpmaker');?>
</button>
</div>
<p>
  <?php _e('Email Styles','rsvpmaker');?><br />
    <textarea name="extra_email_styles" cols="80" rows="5"><? echo get_option('extra_email_styles'); ?></textarea>
	  <br /><a target="_blank" href="<?php echo site_url('?show_rsvpmaker_included_styles=1') ?>"><?php _e('View default email styles','rsvpmaker');?></a>
  </p>
<?php
if(isset($_POST['rsvpmaker_tx_template']))
	update_option('rsvpmaker_tx_template', (int) $_POST['rsvpmaker_tx_template']);
$t_index = (int) get_option('rsvpmaker_tx_template');
?>
<p><?php _e('Template For Confirmation/Reminder Messages','rsvpmaker');?> <select name="rsvpmaker_tx_template">
<?php
foreach($template as $in => $value)
	{
	$c = ( $in == $t_index) ? ' selected="selected" ' : '';
	echo sprintf('<option value="%d" %s>%s</option>',$in,$c,$value["slug"]);
	}
; ?>
</select></p>

<p>
<button><?php _e('Save','rsvpmaker');?></button>
</p>

	<p><a href="<?php echo admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template&reset_email_template=1'); ?>"><?php _e('Reset to default template','rsvpmaker');?></a></p>

<script>
function remove_template(id) {
var t = document.getElementById('temp'+id);
var f = document.getElementById('form1');
f.removeChild(t);
}

jQuery(document).ready(function($){
$('#addtemp').click( function(event) {
	event.preventDefault();
	$('#add_template').html('<p><input type="text" name="rsvpmaker_email_template[<?php echo $index; ?>][slug]" id="rsvpmaker_email_template[<?php echo $index; ?>][slug]" value="<?php echo "template".($index+1); ?>" /></p><p><textarea name="rsvpmaker_email_template[<?php echo $index; ?>][html]" id="rsvpmaker_email_template[<?php echo $index; ?>][html]" cols="80" rows="10"></textarea></p>');
	} );

});

</script>

</form>

<?php

} // end rsvpemail template form

function my_rsvpemail_menu() {
global $rsvp_options;

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Scheduled Email",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_scheduled_email_list";
$function = "rsvpmaker_scheduled_email_list";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Email Template",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_email_template";
$function = "rsvpemail_template";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Notification Templates",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "rsvpmaker_notification_templates";
$function = "rsvpmaker_notification_templates";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Content for Email",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "email_get_content";
$function = "email_get_content";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Extract Addresses",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "extract";
$function = "RSVPMaker_extract_email";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=rsvpemail";
$page_title = __("Unsubscribed List",'rsvpmaker');
$menu_title = $page_title;
$capability = 'edit_others_rsvpemails';
$menu_slug = "unsubscribed_list";
$function = "unsubscribed_list";

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

function email_log () {
global $wpdb;
$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_rsvpmaker_email_log' ORDER BY meta_id DESC";
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

printf('<h1>%s</h1><p>%s</p>',__('Unsubscribed List','rsvpmaker'),__('If recipients have clicked unsubscribe on a confirmation message or any other message sent directly from RSVPMaker (as opposed to via MailChimp) they will be listed here.','rsvpmaker'));
	$unsub = get_option('rsvpmail_unsubscribed');
if(!empty($unsub))
{
printf('<form method="post" action="%s"><table><tr><th>Unblock</th><th>Email</th></tr>',admin_url('edit.php?post_type=rsvpemail&page=unsubscribed_list'));
foreach($unsub as $index => $e)
{
	if(isset($_POST['remove']) && in_array($e,$_POST['remove']))
		unset($unsub[$index]);
	else
		printf('<tr><td><input type="checkbox" name="remove[]" value="%s" /></td><td>%s</td></tr>',$e,$e);	
}
echo '</table><p><input type="submit" value="Submit"></p></form>';
	
if(isset($_POST['remove']))
	update_option('rsvpmail_unsubscribed',$unsub);
}

printf('<h2>Add an Email to Unsubscribed List</h2><form method="get" action="%s" target"_blank"><input name="rsvpmail_unsubscribe" /><button>Add</button></form>',site_url());

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
; ?>
</div>
<?php 
endwhile;
wp_reset_query();
} 
	
	$content = ob_get_clean();

	return $content;
}
add_shortcode('RSVPMaker_chimpshort', 'RSVPMaker_chimpshort');

function email_get_content () {
global $wpdb;
;?>
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Content for Email</h2>

<?php

$event_options = $options = '<option value="">'.__('None selected','rsvpmaker').'</option>';
$event_options .= '<option value="upcoming">'.__('Upcoming Events','rsvpmaker').'</option>';
$posts = '';
$future = get_future_events();
foreach($future as $event)
	{
	$event_options .= sprintf('<option value="%s">%s - %s</option>'."\n",$event->ID,$event->post_title,date('F j, Y',strtotime($event->datetime)));
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

?>
<form action="<?php echo admin_url(); ?>" method="get">
<p><?php _e('Email Based on Event','rsvpmaker');?>: <select name="rsvpevent_to_email"><?php echo $event_options; ?></select>
</select>
</p>
<button><?php _e('Load Content','rsvpmaker');?></button>
</form>	
<form action="<?php echo admin_url(); ?>" method="get">
<p><?php _e('Email Based on Post','rsvpmaker');?>: <select name="post_to_email"><?php echo $posts; ?></select>
</select>
</p>
<button><?php _e('Load Content','rsvpmaker');?></button>
</form>	

<?php
} // end chimp get content

function rsvpmaker_email_list_okay ($rsvp) {
		$mergevars["FNAME"] = stripslashes($rsvp["first"]);
		$mergevars["LNAME"] = stripslashes($rsvp["last"]);
		RSVPMaker_Chimp_Add($rsvp["email"],$mergevars);
}


function get_rsvpmaker_email_template() {

if(!isset($_GET["reset_email_template"]))
{
$template = get_option('rsvpmaker_email_template');
if(!empty($template))
	return $template;
}

	$template[0]['slug'] = 'default';
	$template[0]['html'] = '<html>
<head>
	<title>*|MC:SUBJECT|*</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<div id="background" style="width: 100%; background-color: #DDDDFF; padding: 10px; margin-top: 0;">
<div id="content" style="max-width: 600px; padding: 5px; background-color: #FFFFFF; margin-left: auto; margin-right: auto; margin-top: 10px; margin-bottom: 10px; padding-bottom: 50px;" >

<div style="font-size: small; border: thin dotted #999;">Email not displaying correctly? <a href="*|ARCHIVE|*" class="adminText">View it in your browser.</a></div>

<div class="headerBarText"><h1><a href="'.home_url().'">'.get_bloginfo('name').'</a></h1></div>

[rsvpmaker_email_content]

</div><!-- end content area -->
</div><!-- end background -->
</body>
</html>';
	$template[0]['inline_tags'] = array('h1' => 'color: #4444FF;','h2' => 'color: #4444FF;','h3' => 'color: #4444FF;');
	$template[0]['inline_class'] = array('alignright' => 'float: right; padding: 5px; background-color: #EFEFEF;','alignleft' => 'float: left; padding: 5px; background-color: #EFEFEF;','aligncenter' => 'margin-left: auto; margin-right: auto; background-color: #EFEFEF; padding: 5px;');

update_option('rsvpmaker_email_template',$template);
return $template;
}

add_shortcode('rsvpmaker_email_content', 'rsvpmaker_email_content');

function rsvpmaker_tx_email($event_post, $mail) {

//used with rsvpmaker_email_content shortcode in template
global $rsvpmaker_tx_content;
$rsvpmaker_tx_content = $mail["html"];
$templates = get_rsvpmaker_email_template();
$t_index = (int) get_option('rsvpmaker_tx_template');

$template = $templates[$t_index]["html"];

$rsvpmaker_tx_content .= '
    *|LIST:DESCRIPTION|*<br>
    <br>
    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list
';

$rsvpfooter_text = '

==============================================
*|LIST:DESCRIPTION|*

Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*
';

$rsvp_text = rsvpmaker_text_version($mail["html"], $rsvpfooter_text);

$mail["html"] = do_shortcode($template);

$mail["html"] = preg_replace('/(?<!")(https:\/\/www.youtube.com\/watch\?v=|https:\/\/youtu.be\/)([a-zA-Z0-9_\-]+)/','<p><a href="$0">Watch on YouTube: $0<br /><img src="https://img.youtube.com/vi/$2/hqdefault.jpg" width="480" height="360" /></a></p>',$mail["html"]);

global $unsub;
if(empty($unsub))
	$unsub = get_option('rsvpmail_unsubscribed');
if(empty($unsub)) $unsub = array();
	if(in_array(strtolower($mail["to"]),$unsub))
		{
			return;
		}
	$mail["html"] = rsvpmaker_personalize_email($mail["html"],$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$event_post->post_title);
	$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],__('This message was sent to you as a follow up to your registration for','rsvpmaker').' '.$event_post->post_title);
	rsvpmailer($mail);
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
	print_r($wp_filter);
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
<?php the_content(); ?>
</div><!-- .entry-content -->
</div><!-- #post-## -->
<div class="footer"><!-- footer --></div>
<?php 
$content = ob_get_clean();
if(function_exists('do_blocks'))
	$content = do_blocks($content);
$content = str_replace('<img ','<img style="display: block; max-width: 90%;" ',$content);
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
		$options .=  '<option value= "'.$list['id'].'"'. " $s >".$list['name'].'</option>';
	}
}
return $options;
}

function event_to_embed($post_id, $embed = NULL) {
		global $email_context;
		global $rsvp_options;
		global $post;
		$backup = $post;
		$email_context = true;
		if(empty($embed))
			$post = get_post($post_id);
		$event_embed["subject"] = $post->post_title;
		$event_embed["content"] = sprintf('<!-- wp:heading -->
<h2 class="email_event"><a href="%s">%s</a></h2>
<!-- /wp:heading -->'."\n",get_permalink($post->ID),apply_filters('the_title',$post->post_title));
		if($post->post_type == 'rsvpmaker')
		{
		$date_array = rsvp_date_block($post_id);
		$dateblock = trim(strip_tags($date_array["dateblock"]));
		$dur = $date_array["dur"];
		$last_time = $date_array["last_time"];
		$tmlogin = (strpos($post->post_content,'[toastmaster')) ? sprintf('<!-- wp:paragraph -->
<p><a href="%s">Login</a> to sign up for roles</p>
<!-- /wp:paragraph -->',wp_login_url( get_post_permalink( $post_id ) ) ) : '';
		$event_embed["content"] .= sprintf('<!-- wp:paragraph -->
<p><strong>%s</strong></p>
<!-- /wp:paragraph -->',$dateblock).$tmlogin;			
		}
		$event_embed["content"] .= do_shortcode($post->post_content);
		if(get_post_meta($post_id,'_rsvp_on',true))
		{
		if(get_post_meta($post_id,'_rsvp_count',true))
			$event_embed["content"] .= rsvpcount($post_id);
		$rsvplink = get_rsvp_link($post_id);
		$event_embed["content"] .= "<!-- wp:paragraph -->\n".$rsvplink."\n<!-- /wp:paragraph -->";
		}
		$post = $backup;
		if(function_exists('do_blocks')){
			$event_embed["content"] = do_blocks($event_embed["content"]);			
		}
		else 
		$event_embed["content"] = wpautop($event_embed["content"]);
		$post = $backup;
		return $event_embed;
}

function rsvpmaker_upcoming_email($atts) {
	$output = '';
	$weeks = (empty($atts["weeks"])) ? 4 : $atts["weeks"];
	$end = date('Y-m-d',strtotime('+'.$weeks.' weeks')). ' 23:59:59';
	$upcoming = get_future_events(' a1.meta_value < "'.$end.'"');
	foreach($upcoming as $embed)
		{
		$event = event_to_embed($embed->ID,$embed);
		$output .= $event["content"]."\n\n";
		}
	if(isset($atts["looking_ahead"]))
		{
			$weeksmore = $atts["looking_ahead"];
			$label = (empty($atts["looking_ahead_label"])) ? '<h2>Looking Ahead</h2>' : '<h2 class="looking_ahead">'.$atts["looking_ahead_label"].'</h2>';
			$extra = date('Y-m-d',strtotime($end .' +'.$weeksmore.' weeks')). ' 23:59:59';
			$upcoming = get_future_events(' a1.meta_value > "'.$end .'" AND  a1.meta_value < "'.$extra.'"');
			if(!empty($upcoming))
				{
					$output .= $label."\n";
					foreach($upcoming as $ahead)
						$output .= sprintf('<p><a href="%s">%s - %s</a></p>',get_permalink($ahead->ID),$ahead->post_title,date('F j',strtotime($ahead->datetime)));
				}
		}	
	return $output;
}

add_shortcode('rsvpmaker_upcoming_email','rsvpmaker_upcoming_email');

function is_email_context () {
		global $email_context;
		return (isset($email_context) && $email_context);
}

function rsvpmaker_cron_email_send($post_id) {
global $rsvpmaker_cron_context;
global $wp_query;
$rsvpmaker_cron_context = 2; // 2 means send live
$wp_query = new WP_Query( array('post_type' => 'rsvpemail','p' => $post_id) );
include plugin_dir_path(__FILE__) . 'rsvpmaker-email-template.php';
}

function rsvpmaker_cron_email_preview($args) {
global $rsvpmaker_cron_context;
global $wp_query;
$rsvpmaker_cron_context = 1; // 1 means preview
if(isset($args['post_id']))
	$post_id = $args['post_id'];
else
	$post_id = (int) $args;// single argument comes as single value
$wp_query = new WP_Query( array('post_type' => 'rsvpemail','p' => $post_id) );
include plugin_dir_path(__FILE__) . 'rsvpmaker-email-template.php';
}

function rsvpmaker_cron_email_preview_now() {
	if(isset($_GET['cronemailpreview']))
	{
		rsvpmaker_cron_email_preview($_GET['cronemailpreview']);
		die('scheduled email preview');
	}
}

add_filter( 'post_row_actions', 'rsvpmaker_row_actions', 10, 2 );
function rsvpmaker_row_actions( $actions, WP_Post $post ) {
	global $current_user;
	$allowed = array('rsvpmaker','post');
    if ( !in_array($post->post_type,$allowed)) {
        return $actions;
    }
	if(current_user_can('edit_post',$post->ID))
	{
	if($post->post_type == 'rsvpmaker')
	$actions['rsvpmaker_options'] = sprintf('<a href="%s">%s</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id=').$post->ID,__('Event Options','rsvpmaker'));
    $actions['rsvpmaker_invite'] = sprintf('<a href="%s">%s</a>',admin_url('?rsvpevent_to_email=').$post->ID,__('Send RSVP Email','rsvpmaker'));		
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
fix_timezone();
if(0 == (int) $blog_weeks_ago)
	$blog_weeks_ago = 1;
	$week_ago_stamp = strtotime('-'.$blog_weeks_ago.' week');
	$week_ago = date('Y-m-d H:i:s',$week_ago_stamp);
    $where .= " AND post_date > '" . $week_ago . "'";
    return $where;
}

add_shortcode('rsvpmaker_recent_blog_posts','rsvpmaker_recent_blog_posts');

function get_rsvp_notekey() {
	global $post, $rsvpmaker_cron_context;
	fix_timezone();
	if(!empty($rsvpmaker_cron_context) && $rsvpmaker_cron_context == 2)
	{
		$notekey = 'editnote'.date('YmdH',time()); // live not preview broadcast or editing
	}
	else {
		$stamp = rsvpmaker_next_scheduled($post->ID, true);
		//$stamp = preg_replace('/M [a-z]+$/','M',$stamp);
		$notekey = 'editnote'.date('YmdH',$stamp);//date('YmdH',strtotime($stamp));
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
if(isset($_POST['rsvpmail_unsubscribe']))
{
$e = strtolower(trim($_POST['rsvpmail_unsubscribe']));
if(!is_email($e))
	echo 'Error: invalid email address';
else
	{
	$unsub = get_option('rsvpmail_unsubscribed');
	if(empty($unsub))
		$unsub = array();
	if(!in_array($e,$unsub))
		$unsub[] = $e;
	update_option('rsvpmail_unsubscribed',$unsub);
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
		echo '<p>'.__('Unsubscribed from MailChimp email list','rsvpmaker').': '.$listId.'</p>';
		$msg .= "\n\nRemoved from MailChimp list";
		}
	else
		{
		echo '<p>'.__('Error attempting to unsubscribe from MailChimp email list','rsvpmaker').': '.$listId.'</p>';	
		$msg .= "\n\nMailChimp unsubscribe error";
		}
	}

	wp_mail(get_option('admin_email'), $e.' '.__('unsubscribed','rsvpmaker').': '.get_option('blogname').' (RSVPMaker)',$msg);

	do_action('rsvpmail_unsubscribe',$e);
	}
}
if(isset($_GET['rsvpmail_unsubscribe']))
{
$e = trim($_GET['rsvpmail_unsubscribe']);
?>
<form method="post" action="<?php echo site_url(); ?>">
<input type="text" name="rsvpmail_unsubscribe" value="<?php echo $e; ?>">
<button><?php _e('Unsubscribe','rsvpmaker'); ?></button>
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
add_filter('init','rsvpmail_unsubscribe');

function rsvpmaker_notification_templates () {

$hook = rsvpmaker_admin_page_top(__('Notification Templates','rsvpmaker'));
echo '<p>'.__('Use this form to customize notification and confirmation messages and the information to be included in them. Template placeholders such as [rsvpdetails] are documented at the bottom of the page.').'</p>';

if(isset($_POST['ntemp']))
	{
	$ntemp = $_POST['ntemp'];
	if(!empty($_POST["newtemplate"]["subject"]) && !empty($_POST["newtemplate_label"]))
		{
		$ntemp[$_POST["newtemplate_label"]]["subject"] = $_POST["newtemplate"]["subject"];
		$ntemp[$_POST["newtemplate_label"]]["body"] = $_POST["newtemplate"]["body"];
		}
	update_option('rsvpmaker_notification_templates',stripslashes_deep($ntemp));
	}
	
$sample_data = array('rsvpdetails' => "first: John\nlast: Smith\nemail:js@example.com",'rsvpyesno' => __('YES','rsvpmaker'), 'rsvptitle' => 'Special Event', 'rsvpdate' => 'January 1, 2020','rsvpmessage' => 'Thank you!', 'rsvpupdate' => '<p><a style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;" class="rsvplink" href="%s">'. __('RSVP Update','rsvpmaker').'</a></p>');
$sample_data = apply_filters('rsvpmaker_notification_sample_data',$sample_data);
$template_forms = get_rsvpmaker_notification_templates ();
printf('<form action="%s" method="post">',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_notification_templates'));
foreach($template_forms as $slug => $form)
	{
	echo '<div style="border: thin dotted #555; margin-bottom: 5px;">';
	printf('<h2>%s</h2>',ucfirst(str_replace('_',' ',$slug)));
	foreach($form as $field => $value)
		{
			printf('<div>%s</div>',ucfirst(str_replace('_',' ',$field)));
			if($field == 'body')
				echo '<p><textarea name="ntemp['.$slug.']['.$field.']" style="width: 90%; height: 100px;">'.$value.'</textarea></p>';
			elseif($field == 'sample_data')
				$sample_data = $value;
			else
				echo '<p><input type="text" name="ntemp['.$slug.']['.$field.']" value="'.$value.'" style ="width: 90%" /></p>';
		}
	if(isset($_GET[$slug]))
	{
	echo '<h3>Example</h3>';
	$example = '<p><strong>Subject: </strong>'.$form['subject']."</p>\n\n".$form['body'];
	foreach($sample_data as $field => $value)
		$example = str_replace('['.$field.']',$value,$example);
	
	$example = wpautop($example);
	echo do_shortcode($example);
	}
	echo '</div>';//end border

	}
	printf('<h3>%s: <input type="text" name="newtemplate_label"></h3>',__('Custom Label','rsvpmaker-for-toastmasters'));
	echo '<p>Subject<br /><input type="text" name="newtemplate[subject]" value="" style ="width: 90%" /></p>';
	echo '<p>Body<br /><textarea name="newtemplate[body]" style="width: 90%; height: 100px;"></textarea></p>';

echo submit_button().'</form>';


echo   '<p>'.__("RSVPMaker template placeholders:<br />[rsvpyesno] YES/NO<br />[rsvptitle] event post title<br />[rsvpdate] event date<br />[rsvpmessage] the message you supplied when you created/edited the event (default is Thank you!)<br />[rsvpdetails] information supplied by attendee<br />[rsvpupdate] button users can click on to update their RSVP<br />[rsvpcount] number of people registered",'rsvpmaker').'</p>';
do_action('rsvpmaker_notification_templates_doc');
rsvpmaker_admin_page_bottom($hook);
}

function get_rsvpmaker_notification_templates () {
global $email_context;
$email_context = true;
$templates = get_option('rsvpmaker_notification_templates');
//$template_forms represents the defaults
$template_forms['notification'] = array('subject' => 'RSVP [rsvpyesno] for [rsvptitle] on [rsvpdate]','body' => "Just signed up:\n\n[rsvpdetails]");
$template_forms['confirmation'] = array('subject' => 'Confirming RSVP [rsvpyesno] for [rsvptitle] on [rsvpdate]','body' => "[rsvpmessage]\n\n[rsvpdetails]\n\nIf you wish to change your registration, you can do so using the button below. [rsvpupdate]");
$template_forms['payment_reminder'] = array('subject' => 'Payment Required: [rsvptitle] on [rsvpdate]','body' => "We received your registration, but it is not complete without a payment. Please follow the link below to complete your registration and payment.

[rsvpupdate]

[rsvpdetails]");
$template_forms = apply_filters('rsvpmaker_notification_template_forms',$template_forms);
if(empty($templates))
	return $template_forms;

foreach($template_forms as $slug => $form)
	{
	foreach($form as $field => $value)
		{
			if(empty($templates[$slug][$field]))
				$templates[$slug][$field] = $template_forms[$slug][$field];
		}
	}
return $templates;
}

add_shortcode('rsvpcount','rsvpcount');
function rsvpcount ($atts) {
global $wpdb;
global $post;
if(isset($atts['post_id']))
	$post_id = (int) $atts['post_id'];
elseif(!empty($atts) && is_numeric($atts))
	$post_id = $atts;
else
	$post_id = $post->ID;
	
//rsvpmaker_debug_log($atts,'rspcount atts');
//rsvpmaker_debug_log($post_id,'rspcount post_id');
	
if(!$post_id)
	return;
$sql = "SELECT count(*) FROM ".$wpdb->prefix."rsvpmaker WHERE event=$post_id AND yesno=1 ORDER BY id DESC";
//rsvpmaker_debug_log($sql,'rspcount sql');
$total = (int) $wpdb->get_var($sql);
//rsvpmaker_debug_log($total,'rspcount total');
$rsvp_max = get_post_meta($post_id,'_rsvp_max',true);
$output = $total.' '.__('signed up so far.','rsvpmaker');
if($rsvp_max)
	$output .= ' '.__('Limit','rsvpmaker').': '.$rsvp_max;
return '<p class="signed_up">'.$output.'</p>';
}

function rsvp_notifications_via_template ($rsvp,$rsvp_to,$rsvpdata) {
global $post;
global $rsvp_options;
include 'rsvpmaker-ical.php';

$templates = get_rsvpmaker_notification_templates();

$notification_subject = $templates['notification']['subject']; 
foreach($rsvpdata as $field => $value)
	$notification_subject = str_replace('['.$field.']',$value,$notification_subject);

$notification_body = $templates['notification']['body']; 
foreach($rsvpdata as $field => $value)
	$notification_body = str_replace('['.$field.']',$value,$notification_body);
	$notification_body = do_shortcode($notification_body);

	$rsvp_to_array = explode(",", $rsvp_to);
	foreach($rsvp_to_array as $to)
	{
	$mail["to"] = $to;
	$mail["from"] = $rsvp["email"];
	$mail["fromname"] = $rsvp["first"].' '.$rsvp["last"];
	$mail["subject"] = $notification_subject;
	$mail["html"] = wpautop($notification_body);
	rsvpmaker_tx_email($post, $mail);
	}

$send_confirmation = get_post_meta($post->ID,'_rsvp_rsvpmaker_send_confirmation_email',true);
if($send_confirmation ||!is_numeric($send_confirmation))//if it hasn't been set to 0, send it
{
$confirmation_subject = $templates['confirmation']['subject']; 
foreach($rsvpdata as $field => $value)
	$confirmation_subject = str_replace('['.$field.']',$value,$confirmation_subject);

$confirmation_body = $templates['confirmation']['body']; 
foreach($rsvpdata as $field => $value)
	$confirmation_body = str_replace('['.$field.']',$value,$confirmation_body);	
	$mail["html"] = wpautop($confirmation_body);
	if(isset($post->ID)) // not for replay
	$mail["ical"] = rsvpmaker_to_ical_email ($post->ID, $rsvp_to, $rsvp["email"]);
	$mail["to"] = $rsvp["email"];
	$mail["from"] = $rsvp_to_array[0];
	$mail["fromname"] = get_bloginfo('name');
	$mail["subject"] = $confirmation_subject;
	rsvpmaker_tx_email($post, $mail);	
}

}

function rsvp_payment_reminder ($rsvp_id) {
rsvpmaker_debug_log($rsvp_id,'payment_reminder_test');
global $post;
global $rsvp_options;
global $wpdb;
$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE id=$rsvp_id";
$rsvp = (array) $wpdb->get_row($sql);
$post = get_post($rsvp['event']);
$rsvpdata = unserialize($rsvp['details']);
rsvpmaker_debug_log($rsvpdata,'payment_reminder_test');
if($rsvpdata['total'] <= $rsvp['amountpaid'])
	return;
	
$details = '';
foreach($rsvpdata as $label => $value)
	$details .= sprintf('%s: %s'."\n",$label,$value);;
$rsvpdata['rsvptitle'] = $post->post_title;
$ts = strtotime(get_rsvp_date($rsvp['event']));
$rsvpdata['rsvpdate'] = strftime($rsvp_options['long_date'],$ts);

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
	
$notification_body = do_shortcode($notification_body);
$mail["to"] = $rsvp['email'];
$mail["from"] = $rsvp_to_array[0];
$mail["fromname"] = get_bloginfo('name');
$mail["subject"] = $notification_subject;
$mail["html"] = wpautop($notification_body);
rsvpmaker_tx_email($post, $mail);

}

add_action('init','rsvp_payment_reminder_test');
function rsvp_payment_reminder_test () {
	if(!isset($_GET['payrem']))
		return;
	rsvp_payment_reminder($_GET['payrem']);
}

add_action('rsvp_payment_reminder','rsvp_payment_reminder',10,1);

function rsvpmaker_payment_reminder_cron ($rsvp_id) {
	$time = strtotime('+30 minutes');
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
	print_r($member);
	echo '</pre>';
}
if (!empty($member["id"]) && ($member["status"] == 'subscribed'))
	return $member;
else
	return false;
}
//$user = get_user_by( 'email', $email );

//weed out filters that don't belong in email
function email_content_minfilters() {
	global $wp_filter, $post, $email_context;
	$log = '';
		$corefilters = array('convert_chars','wpautop','wptexturize','event_content','
		wp_make_content_images_responsive');
		foreach($wp_filter["the_content"] as $priority => $filters)
			foreach($filters as $name => $details)
				{
				//$log .= $name .': '. $priority."   \n";
				if(!in_array($name,$corefilters) && !strpos($name,'hortcode') && !strpos($name,'lock'))//don't mess with block/shortcode processing
					{
					$r = remove_filter( 'the_content', $name, $priority );
					//$log .= "REMOVED  \n";
					}
				}	
	//rsvpmaker_debug_log($log,'email filters scan');
}
?>