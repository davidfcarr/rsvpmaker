<?php
// Import the Postmark Client Class:
require_once('postmark/vendor/autoload.php');
use Postmark\PostmarkClient;
use Postmark\PostmarkAdminClient;
use Postmark\Models\PostmarkException;

function get_rsvpmaker_postmark_options() {
    global $postmark_settings;
    if(is_multisite())
        $postmark_settings = get_blog_option(1,'rsvpmaker_postmark');
    else
        $postmark_settings = get_option('rsvpmaker_postmark');
    if(empty($postmark_settings))
        $postmark_settings['postmark_mode'] = '';
    elseif(!empty($postmark_settings['enabled']) && !in_array(get_current_blog_id(),$postmark_settings['enabled']))
        $postmark_settings['postmark_mode'] = '';//disable
    return $postmark_settings;
}

function rsvpmaker_postmark_is_live() {
    global $postmark_settings;
    //if(empty($postmark_settings))
        $postmark_settings = get_rsvpmaker_postmark_options();
    return (!empty($postmark_settings['postmark_production_key']) && 'production' == $postmark_settings['postmark_mode']);
}

function rsvpmaker_postmark_is_active() {
    global $postmark_settings;
    if(empty($postmark_settings))
        $postmark_settings = get_rsvpmaker_postmark_options();
    return ((!empty($postmark_settings['postmark_production_key']) && 'production' == $postmark_settings['postmark_mode']) || (!empty($postmark_settings['postmark_sandbox_key']) && 'sandbox' == $postmark_settings['postmark_mode']));
}

function rsvpmaker_postmark_options() {
    rsvpmaker_admin_heading(__('RSVPMaker Mailer for Postmark','rsvpmaker'),__FUNCTION__,'',$sidebar);
    global $postmark_settings, $wpdb;
    if(isset($_POST['postmark_mode']) && rsvpmaker_verify_nonce()){
        $postmark_settings['postmark_mode'] = sanitize_text_field($_POST['postmark_mode']);
        $postmark_settings['postmark_sandbox_key'] = sanitize_text_field($_POST['postmark_sandbox_key']);
        $postmark_settings['postmark_production_key'] = sanitize_text_field($_POST['postmark_production_key']);
        $postmark_settings['postmark_tx_from'] = sanitize_text_field($_POST['postmark_tx_from']);
        $postmark_settings['postmark_broadcast_from'] = sanitize_text_field($_POST['postmark_broadcast_from']);
        $postmark_settings['postmark_tx_slug'] = sanitize_text_field($_POST['postmark_tx_slug']);
        $postmark_settings['postmark_broadcast_slug'] = sanitize_text_field($_POST['postmark_broadcast_slug']);
        $postmark_settings['handle_incoming'] = sanitize_text_field($_POST['handle_incoming']);
        $postmark_settings['restricted'] = (empty($_POST['restricted'])) ? 0 : intval($_POST['restricted']);
        $postmark_settings['enabled'] = ($postmark_settings['restricted']) ? $_POST['enabled'] : array();
        if(is_multisite())
            update_blog_option(1,'rsvpmaker_postmark',$postmark_settings);
        else
            update_option('rsvpmaker_postmark',$postmark_settings);
        if('production' == $postmark_settings['postmark_mode'])
            wp_unschedule_hook( 'rsvpmaker_relay_init_hook' );
    }
    else {
        $postmark_settings = get_rsvpmaker_postmark_options();
    }
    if(empty($postmark_settings['postmark_domain']))
        $postmark_settings['postmark_domain'] = $domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
        if(empty($postmark_settings['postmark_mode']))
            $postmark_settings['postmark_mode'] = '';
        if(empty($postmark_settings['postmark_sandbox_key']))
            $postmark_settings['postmark_sandbox_key'] = '';
        if(empty($postmark_settings['postmark_production_key']))
            $postmark_settings['postmark_production_key'] = '';
        if(empty($postmark_settings['postmark_tx_from']))
            $postmark_settings['postmark_tx_from'] = 'headsup@'.$domain;
        if(empty($postmark_settings['postmark_broadcast_from']))
            $postmark_settings['postmark_broadcast_from'] = 'shoutout@'.$domain;
        if(empty($postmark_settings['postmark_tx_slug']))
            $postmark_settings['postmark_tx_slug'] = 'outbound';
        if(empty($postmark_settings['postmark_broadcast_slug']))
            $postmark_settings['postmark_broadcast_slug'] = 'broadcast';
        if(empty($postmark_settings['handle_incoming']))
            $postmark_settings['handle_incoming'] = '';
        if(empty($postmark_settings['restricted']))
            $postmark_settings['restricted'] = '0';
        if(empty($postmark_settings['enabled']))
            $postmark_settings['enabled'] = array();
    printf('<form method="post" action="%s">',admin_url('admin.php?page='.$_GET['page']));
    $checked = (empty($postmark_settings['postmark_mode'])) ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="" %s> Off - Postmark not managing email</p>',$checked);
    $checked = ($postmark_settings['postmark_mode'] == 'sandbox') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="sandbox" %s> Sandbox / Test, Key <input type="text" name="postmark_sandbox_key" value="%s"></p>',$checked, $postmark_settings['postmark_sandbox_key']);
    $checked = ($postmark_settings['postmark_mode'] == 'production') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="production" %s> Production, Key <input type="text" name="postmark_production_key" value="%s"></p>',$checked, $postmark_settings['postmark_production_key']);
    printf('<p>Transactional Messages From: <input type="text" name="postmark_tx_from" value="%s"> Stream ID <input type="text" name="postmark_tx_slug" value="%s"></p>',$postmark_settings['postmark_tx_from'],$postmark_settings['postmark_tx_slug']);
    printf('<p>Broadcast Messages From: <input type="text" name="postmark_broadcast_from" value="%s"> Stream ID <input type="text" name="postmark_broadcast_slug" value="%s"></p>',$postmark_settings['postmark_broadcast_from'],$postmark_settings['postmark_broadcast_slug']);
    $code = (empty($postmark_settings['handle_incoming'])) ? wp_create_nonce('handle_incoming') : $postmark_settings['handle_incoming'];
    $url = rest_url('rsvpmaker/v1/postmark_incoming/'.$code);
    $ckyes = (!empty($postmark_settings['handle_incoming'])) ? ' checked="checked" ' : '';
    $ckno = (empty($postmark_settings['handle_incoming'])) ? ' checked="checked" ' : '';
    printf('<p>Handle Incoming Webhook: <input type="radio" name="handle_incoming" value="%s" %s> Yes <input type="radio" name="handle_incoming" value="" %s> No<br>Webhook address to register in Postmark %s</p>',$code,$ckyes, $ckno,$url);
    if(is_multisite()) {
        $checkyes = ($postmark_settings['restricted']) ? 'checked="checked"' : '';
        $checkno = (!$postmark_settings['restricted']) ? 'checked="checked"' : '';
        printf('<p>Enable for <input type="radio" name="restricted" value="0" %s> All sites <input type="radio" name="restricted" value="1" %s> Just the sites listed below</p>',$checkno,$checkyes);
        $sites = get_sites();
        foreach($sites as $site) {
            $checked = (in_array($site->blog_id,$postmark_settings['enabled'])) ? 'checked="checked"' : '';
            printf('<div class="enabled_sites"><input type="checkbox" name="enabled[]" value="%d" %s> %s</div>',$site->blog_id, $checked ,$site->domain);
        }
    }
    rsvpmaker_nonce();
    echo '<button>Submit</button></form>';

if(!isset($_GET['debug']))
    return;

if(!empty($postmark_settings['postmark_production_key']))
{
    $client = new PostmarkClient($postmark_settings['postmark_production_key']);
    $server = $client->getServer();
    $trackopens = true;
    $tracklinks = 'HtmlOnly';
    if($server['inboundhookurl'] != $url)
    {
        //$client->tweakServer($url);
        $client->editServer($server['name'],$server['color'],$server['rawemailenabled'],
        $server['smtpapiactivated'], $url, $server['bouncehookurl'],$server['openhookurl'],$server['firstopenonly'],
        $trackopens,$tracklinks);
        echo "<p>Updating server settings</p>";
    }    
    echo "<p>Server settings are current</p>";

    /*
editServer($name = NULL, $color = NULL, $rawEmailEnabled = NULL,
		$smtpApiActivated = NULL, $inboundHookUrl = NULL, $bounceHookUrl = NULL,
		$openHookUrl = NULL, $postFirstOpenOnly = NULL, $trackOpens = NULL,
		$inboundDomain = NULL, $inboundSpamThreshold = NULL,
		$trackLinks = NULL, 

[id] => 9167770
            [name] => rsvpmaker sandbox
            [apitokens] => Array
                (
                    [0] => 1ad00992-cb28-43cd-9cfe-35858d3ae3f2
                )

            [color] => green
            [smtpapiactivated] => 1
            [rawemailenabled] => 
            [deliverytype] => Sandbox
            [serverlink] => https://account.postmarkapp.com/servers/9167770/overview
            [inboundaddress] => 73883bf9421838397d8fa35453d24452@inbound.postmarkapp.com
            [inboundhookurl] => https://rsvpmaker.com/wp-json/rsvpmaker/v1/postmark_incoming/1c71259c92
            [bouncehookurl] => 
            [openhookurl] => 
            [deliveryhookurl] => 
            [postfirstopenonly] => 
            [inbounddomain] => 
            [inboundhash] => 73883bf9421838397d8fa35453d24452
            [inboundspamthreshold] => 0
            [trackopens] => 
            [tracklinks] => None
*/


    echo '<pre>';
    print_r($server);
    echo '</pre>';
}

}

function rsvpmaker_postmark_broadcast($recipients,$post_id,$message_stream='',$recipient_names=array()) {
    global $wpdb;
    $recipients = rsvpmaker_recipients_no_problems($recipients);
    if(empty($recipients))
        return;
    if(sizeof($recipients) > 500) {
        $chunks = array_chunk($recipients,500);
        echo $log = sprintf('<p>split into %s chunks</p>',sizeof($chunks));
        $recipients = array_shift($chunks);
        foreach($chunks as $chunk) {
            add_post_meta($post_id,'rsvprelay_to_batch',$chunk);
        }
        wp_schedule_event( strtotime('+15 seconds'), 'minute', 'rsvpmaker_postmark_chunked_batches' );
    }

    $postmark_settings = get_rsvpmaker_postmark_options();
    $postmark_settings_key = ('production' == $postmark_settings['postmark_mode']) ? $postmark_settings['postmark_production_key'] : $postmark_settings['postmark_sandbox_key'];
    if(empty($message_stream))
        $message_stream = (sizeof($recipients) > 1) ? $postmark_settings['postmark_broadcast_slug'] : $postmark_settings['postmark_tx_slug'];
    $mpost = get_post($post_id);
    $meta = get_post_meta($post_id);
    if(isset($meta['_rsvpmail_html'][0]))
        $html = $meta['_rsvpmail_html'][0];
    else {
        $html = rsvpmaker_email_html($mpost,$post_id);
        update_post_meta($post_id,'_rsvpmail_html',$html);
    }
    $html = rsvpmail_replace_placeholders($html);
    $text = rsvpmaker_text_version($html);
    $mail['Subject'] = $mpost->post_title;
    $mail['MessageStream'] = $message_stream;
    if(isset($meta['rsvprelay_from'][0]))
        $mail['ReplyTo'] = $meta['rsvprelay_from'][0];
    $mail['From'] = ($message_stream == $postmark_settings['postmark_tx_slug']) ? $postmark_settings['postmark_tx_from'] : $postmark_settings['postmark_broadcast_from'];
    $fromname = (empty($meta['rsvprelay_fromname'][0])) ? get_bloginfo('name') : $meta['rsvprelay_fromname'][0];
    $mail['From'] = rsvpmaker_email_add_name($mail['From'],$fromname);
    $client = new PostmarkClient($postmark_settings_key);

    foreach($recipients as $index => $to) {
        if(isset($recipient_names[$to]))
            $mail['To'] = rsvpmaker_email_add_name($to,$recipient_names[$to]);
        else
            $mail['To'] = $to;
        $mail['HtmlBody'] = str_replace('*|EMAIL|*',$to,$html);
        $mail['TextBody'] = str_replace('*|EMAIL|*',$to,$text);
        $batch[] = $mail;
        $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '$to' AND post_id=$post_id ");
    }
    
    $hash = postmark_batch_hash($batch,$recipients);
    if(rsvpmaker_postmark_duplicate($hash))
        return 'Duplicate message';

    $responses = $client->sendEmailBatch($batch);

    // The response from the batch API returns an array of responses for each
    // message sent. You can iterate over it to get the individual results of sending.
    $sent = $send_error = array();
    foreach($responses as $key=>$response){
        if($response->message != 'OK')
            $send_error[] = var_export($response,true);
        else
            $sent[] = $response->to;
    }
    if(count($sent)) {
        rsvpmaker_postmark_sent_log($sent,$mail['Subject'],$hash);
        printf('Successful sends %d ending with %s',count($sent),$sent[sizeof($sent)-1]);
        foreach($sent as $e) {
            add_post_meta($post_id,'rsvpmail_sent_postmark',$e);
        }
    }
    if(count($send_error)) {
        printf('Errors %d (see log)',count($send_error));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
}

add_action('rsvpmaker_postmark_chunked_batches','rsvpmaker_postmark_chunked_batches');
function rsvpmaker_postmark_chunked_batches() {
    global $wpdb;
    //do we need to switch to blog 1?
    //used with postmark integration
    $log = '';
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='rsvprelay_to_batch'";
	$batchrow = $wpdb->get_row($sql);
	if($batchrow) {
		$recipients = unserialize($batchrow->meta_value);
		if(empty($recipients))
			$log .= 'done';
        else {
            if(!isset($_GET['page']))
                wp_mail(get_bloginfo('admin_email'),'batched sending of email batchrow '.$batchrow->post_id,sizeof($recipients).' recipients');
            $log .= rsvpmaker_postmark_broadcast($recipients,$batchrow->post_id);
        }
		$wpdb->query("update $wpdb->postmeta set meta_key='rsvprelay_to_batch_done' where meta_id=$batchrow->meta_id");
	} else {
        // until it's needed again
        wp_unschedule_hook( 'rsvpmaker_postmark_chunked_batches' );
        if(!isset($_GET['page'])) //if not checking manually
            wp_mail(get_bloginfo('admin_email'),'batched sending of email complete','No more messages in the queue');
        $log .= 'No batched messages waiting';
    }
}

function rsvpmaker_postmark_send($mail) {
    $postmark_settings = get_rsvpmaker_postmark_options();
    $mail['MessageStream'] = $postmark_settings['postmark_tx_slug'];
    $batch = rsvpmaker_postmark_batch($mail, $mail['to']);
    $result = rsvpmaker_postmark_batch_send($batch);
    return $result;
}

function rsvpmaker_postmark_incoming($forwarders,$emailobj,$post_id) {
    $admin_email = get_bloginfo('admin_email');
    $result = '';
    if($admin_email == $emailobj->From && 'stop' == $emailobj->Subject) {
        //emergency cutoff
        $postmark_settings = get_rsvpmaker_postmark_options();
        $postmark_settings['postmark_mode'] = '';
        update_blog_option(1,'rsvpmaker_postmark',$postmark_settings);
        mail($admin_email,'postmark deactivated',date('r'));
    }
	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
 	foreach($forwarders as $email) {
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
        if(!empty($slug_and_id)) {
            $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj);
            if($recipients) {
                $batch = rsvpmaker_postmark_batch($emailobj, $recipients, $slug_and_id);
                $result = rsvpmaker_postmark_batch_send($batch);
            }
        }
	}
    return $result;
}

function rsvpmaker_postmark_array($source, $message_stream = 'broadcast', $slug_and_id = NULL) {
    global $via;
    $slug = (is_array($slug_and_id) && !empty($slug_and_id['slug'])) ? '['.$slug_and_id['slug'].'] ' : '';
    $postmark_settings = get_rsvpmaker_postmark_options();
    if(is_array($source) && isset($source['HtmlBody']))
        return $source;//already set up
    if(is_array($source)) {
        foreach($source as $key => $value)
        {
            if($key == 'html')
                $key = 'HtmlBody';
            else
                $key = ucfirst($key);
            $mail[$key] = $value;
        }
        $mail['ReplyTo'] = $source['from'];
        $mail['From'] = ($postmark_settings['postmark_broadcast_slug'] == $message_stream) ? $postmark_settings['postmark_broadcast_from'] : $postmark_settings['postmark_tx_from'];//check
        if($source['fromname'])
            $mail['From'] = rsvpmaker_email_add_name($mail['From'],$source['fromname'].$via);
        $mail['Subject'] = $slug.$mail['Subject'];
        if(isset($source['ical'])) {
            $base64 = base64_encode($source['ical']);
            $mail['Attachments'][] = array('ContentType' => 'text/calendar; charset=\"UTF-8\"; method=REQUEST','Name'=>'Invitation.ics','Content'=>$base64);
            $mail['Attachments'][] = array('ContentType' => 'application/ics','Name'=>'invite.ics','Content'=>$base64);
            unset($mail['Ical']);    
        }
    }
    else {
        $source = (array) $source;
        $fields = array('From','Subject','HtmlBody','TextBody','Attachments');
        foreach($fields as $field) {
            if(!empty($source[$field]))
                $mail[$field] = $source[$field];
        }
        if(!strpos($mail['Subject'],']'))
            $mail['Subject'] = $slug.$mail['Subject'];
        $mail['From'] = ($postmark_settings['postmark_broadcast_slug'] == $message_stream) ? $postmark_settings['postmark_broadcast_from'] : $postmark_settings['postmark_tx_from'];//check
        if(!empty($source['FromName']))
            $mail['From'] = rsvpmaker_email_add_name($mail['From'],$source['FromName'].$via);
		$body['MessageStream'] = $message_stream;
        $mail['ReplyTo'] = $source['From'];
        if(isset($source['post_id']))
            $mail['post_id'] = $source['post_id'];
    }
    $mail['MessageStream'] = $message_stream;
    return $mail;
}

function rsvpmaker_postmark_batch($mail, $recipients, $slug_and_id = NULL) {
    if(!is_array($recipients))
        $recipients = array($recipients);
    $recipient_names = get_transient('recipient_names');
    if(empty($recipient_names))
        $recipient_names = array();
    $postmark_settings = get_rsvpmaker_postmark_options();
    //use tx only for small batches like rsvp notification / confirmation
    $message_stream = ((sizeof($recipients) < 3) && is_array($mail) && $postmark_settings['postmark_tx_slug'] == $mail['MessageStream']) ? $postmark_settings['postmark_tx_slug'] : $postmark_settings['postmark_broadcast_slug'];
    $template = rsvpmaker_postmark_array($mail, $message_stream, $slug_and_id);
    foreach($recipients as $to) {
        $mail = $template;
        $mail['HtmlBody'] = rsvpmaker_personalize_email($mail['HtmlBody'],$to);
        $mail['TextBody'] = rsvpmaker_text_version($mail['HtmlBody']);
        $mail['To'] = (isset($recipient_names[$to])) ? rsvpmaker_email_add_name($to,$recipient_names[$to]) : $to;
        $batch[] = $mail;
    }
    return $batch;
}

function rsvpmaker_postmark_batch_send($batch) {
    global $wpdb;
    $output = '';
    $post_id = (isset($batch[0]['post_id'])) ? $batch[0]['post_id'] : 0;
    $postmark_settings = get_rsvpmaker_postmark_options();
    $postmark_settings_key = ('production' == $postmark_settings['postmark_mode']) ? $postmark_settings['postmark_production_key'] : $postmark_settings['postmark_sandbox_key'];
    $client = new PostmarkClient($postmark_settings_key);
    $hash = postmark_batch_hash($batch);
    if(rsvpmaker_postmark_duplicate($hash))
        return;
    $responses = $client->sendEmailBatch($batch);
    // The response from the batch API returns an array of responses for each
    // message sent. You can iterate over it to get the individual results of sending.
    $sent = $send_error = array();
    foreach($responses as $key=>$response){
        if($response->message != 'OK')
            $send_error[] = var_export($response,true);
        else
            $sent[] = $response->to;
    }
    if(count($sent)) {
        rsvpmaker_postmark_sent_log($sent,$batch[0]['Subject'],$hash);
        $output .= sprintf('Successful sends %d',count($sent));
        foreach($sent as $e) {
            if($post_id)
                $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$e."' AND post_id=$post_id ");
            add_post_meta($post_id,'rsvpmail_sent_by_postmark',$e);
        }
    }
    if(count($send_error)) {
        $output .= sprintf('<p>Errors %d (see log) %s</p>',count($send_error),var_export($batch,true).' '.var_export($send_error,true));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
    return $output;
}

function postmark_batch_hash ($batch,$recipients = null) {
    if($recipients)
        $rlist = implode('',$recipients);
    else {
        $rlist = '';
        foreach($batch as $mail)
            $rlist .= $mail['To'];
    }
    return hash('crc32c',var_export($batch[0],true).$rlist);
}

function rsvpmaker_postmark_duplicate($hash) {
    global $wpdb;
	$sql = $wpdb->prepare("select count(*) duplicates, subject, recipients FROM ".$wpdb->base_prefix."postmark_tally where hash=%s AND time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",$hash);
	$row = $wpdb->get_row($sql);

    if($row->duplicates > 1)
    {
        mail(get_bloginfo('admin_email'),'postmark duplicate blocked',var_export($row,true));
        return true;
    }
    return false;
}

function rsvpmaker_postmark_sent_log($sent, $subject='',$hash='') {
	global $wpdb, $message_blog_id;
    $postmark = get_rsvpmaker_postmark_options();
	if(empty($message_blog_id))
		$message_blog_id = get_current_blog_id();
	$sql = $wpdb->prepare("insert into ".$wpdb->base_prefix."postmark_tally set count=%d, subject=%s, blog_id=%s, recipients=%s,hash=%s",sizeof($sent),$subject,$message_blog_id,implode(',',$sent),$hash);
	$wpdb->query($sql);
	$sent_in_hour = $wpdb->get_var("SELECT SUM(count) FROM ".$wpdb->base_prefix."postmark_tally WHERE time > DATE_SUB(NOW(), INTERVAL 1 HOUR) ");
	$message = var_export($sent,true)."\n\n $sent_in_hour sent in the last hour";
	if((!empty($postmark['circuitbreaker'])) && ($sent_in_hour > $postmark['circuitbreaker'])) {
		switch_to_blog(1);
		$postmark = get_option('rsvpmaker_postmark');
		$postmark['postmark_mode'] = '';
		update_option('rsvpmaker_postmark',$postmark);
	}
    if($sent_in_hour > 50) {
        $overloadmessage = '';
        $score = 0;
        $sql = "SELECT count(*) tally, recipients, subject FROM `".$wpdb->base_prefix."postmark_tally` WHERE time > DATE_SUB(NOW(), INTERVAL 1 HOUR) group by recipients";
        $results = $wpdb->get_results($sql);
        foreach($results as $row) {
            $overloadmessage .= sprintf('%d %s %s'."\n",$row->tally, $row->email,$row->subject);
            if($row->tally > 20)
                $score += $row->tally;
        }
        if($score > 50)
        {
            switch_to_blog(1);
            $postmark = get_option('rsvpmaker_postmark');
            $postmark['postmark_mode'] = '';
            update_option('rsvpmaker_postmark',$postmark);
            wp_mail(get_bloginfo('admin_email'),'Shutting down RSVPMaker/Postmark email delivery service because of overload',"Heavy use, warning score $score, resulting in this stream of messages\n".$overloadmessage);    
        }
        elseif(!empty($overloadmessage))
            wp_mail(get_bloginfo('admin_email'),'Recent email volume on RSVPMaker/Postmark >' .$sent_in_hour. ' in past hour',"Heavy use, warning score $score, resulting in this stream of messages\n",$overloadmessage);
    }
}

function rsvpmaker_postmark_show_sent_log() {
    rsvpmaker_admin_heading('Postmark Email Log',__FUNCTION__);
    echo '<p>Postmark is the service we use for reliable email delivery. Here is a record of emails submitted to the Postmark service within the last month.</p>';
    global $wpdb;
    $table = $wpdb->base_prefix.'postmark_tally';
    $blog_id = get_current_blog_id();
    if($blog_id > 1) {
        $sql = "SELECT * FROM $table WHERE time > DATE_SUB(NOW(), INTERVAL 31 DAY) AND blog_id=$blog_id ORDER BY id DESC";
        $showmulti = false;
    }
    else {
        $sql = "SELECT * FROM $table  WHERE time > DATE_SUB(NOW(), INTERVAL 31 DAY) ORDER BY id DESC";
        $showmulti = is_multisite();
    }
    $results = $wpdb->get_results($sql);
    echo '<table class="wp-list-table widefat striped"><thead><tr><th>Subject</th><th># Recipients</th><th>Blog ID</th><th>Recipients</th></tr></thead><tbody>';
    foreach($results as $row) {
        $recipients = (strlen($row->recipients) > 200) ? substr($row->recipients,0,100).'...' : $row->recipients;
        printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',$row->subject,$row->count,$row->blog_id,$recipients);
        if(empty($count[$row->blog_id]))
            $counts[$row->blog_id] = $row->count;
        else
            $counts[$row->blog_id] = $row->count;
    }
    echo '</tbody></table>';

    if(!empty($counts)) {
        foreach($counts as $blog_id => $count) {
            if($showmulti)
                switch_to_blog($blog_id);
            $name = get_bloginfo('name');
            printf('<p><strong>%s</strong> sent %d messages within the last month</p>',$name,$count);
        }
    }
}

function rsvpmaker_postmark_log_table() {
global $wpdb;
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
$sql = 'CREATE TABLE `'.$wpdb->base_prefix.'postmark_tally` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `blog_id` int(11) NOT NULL DEFAULT \'0\',
        `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `count` int(11) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `recipients` longtext NOT NULL,
        `hash` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
dbDelta($sql);
add_option('postmark_tally_version',1);
}

function check_postmark_tally_version() {
    $version = (int) (is_multisite()) ? get_blog_option(1,'postmark_tally_version') : get_option('postmark_tally_version');
    if($version < 1)
        rsvpmaker_postmark_log_table();
}

add_filter('option_postmark_settings','rsvpmaker_option_postmark_settings');
function rsvpmaker_option_postmark_settings($option) {
    if((empty($options)) && rsvpmaker_postmark_is_live()) {
    $postmark_settings = get_rsvpmaker_postmark_options();
    $option = json_encode(array(
        'enabled'        => 1,
        'api_key'        => $postmark_settings['postmark_production_key'],
        'stream_name'    => $postmark_settings['postmark_tx_slug'],
        'sender_address' => $postmark_settings['postmark_tx_from'],
        'force_from'     => 0,
        'force_html'     => 0,
        'track_opens'    => 0,
        'track_links'    => 0,
        'enable_logs'    => 1
    ));
    }
    return $option;
}
