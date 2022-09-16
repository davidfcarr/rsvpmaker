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
    elseif(!empty($postmark_settings['sandbox_only']) && in_array(get_current_blog_id(),$postmark_settings['sandbox_only']))
        $postmark_settings['postmark_mode'] = 'sandbox';
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

function show_rsvpmaker_postmark_status() {
    if(rsvpmaker_postmark_is_live())
        echo '<p>RSVPMaker\'s integration with the Postmark service is live, ensuring reliable message delivery</p>';
    elseif(rsvpmaker_postmark_is_active())
        echo '<p>Postmark integration is in sandbox mode, meaning RSVPMaker messages will only be sent to a test instance of the Postmark cloud.</p>';
    else
        echo '<p>RSVPMaker\'s integration with Postmark is not active on this site.</p>';
    do_action('show_rsvpmaker_postmark_status');
}

function rsvpmaker_postmark_options() {
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
        $postmark_settings['enabled'] = ($postmark_settings['restricted'] && !empty($_POST['enabled'])) ? array_map('intval',$_POST['enabled']) : array();
        $postmark_settings['limited'] = (empty($_POST['limited'])) ? 0 : intval($_POST['limited']);
        $postmark_settings['site_admin_message'] = !empty($_POST['site_admin_message']) ? wp_kses_post(stripslashes($_POST['site_admin_message'])) : '';
        $postmark_settings['sandbox_only'] = array_map('intval',$_POST['sandbox_only']);
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
        if(empty($postmark_settings['limited']))
            $postmark_settings['limited'] = '0';
        if(empty($postmark_settings['sandbox_only']))
            $postmark_settings['sandbox_only'] = array();
    echo '<p>To fill in these variables, first <a href="https://account.postmarkapp.com/sign_up" target="_blank">create a Postmark account</a>. Postmark provides reliable email deliver for both broadcast / mailing list messages and transactional messages such as RSVP confirmations. Premium add-ons and customization services for managing email forwarding and metered access for multisite site owners are available from <a href="mailto:david@rsvpmaker.com" target="_blank">david@rsvpmaker.com</a>.</p>';        
    printf('<form method="post" action="%s">',admin_url('options-general.php?page=rsvpmaker-admin.php&tab=email'));
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
        $sites = get_sites(array('orderby' => 'domain'));
        $col1 = $col2 = '';
        $checkyes = ($postmark_settings['restricted']) ? 'checked="checked"' : '';
        $checkno = (!$postmark_settings['restricted']) ? 'checked="checked"' : '';
        printf('<p><strong>Enable for</strong> <input type="radio" name="restricted" value="0" %s> All sites <input type="radio" name="restricted" value="1" %s> Just the sites checked below&nbsp;&nbsp;&nbsp;</p>',$checkno,$checkyes);
        foreach($sites as $site) {
            $checked = (in_array($site->blog_id,$postmark_settings['enabled'])) ? 'checked="checked"' : '';
            $col1 .= sprintf('<div class="enabled_sites"><input type="checkbox" name="enabled[]" value="%d" %s> %s</div>',$site->blog_id, $checked ,$site->domain);
            $checked = (in_array($site->blog_id,$postmark_settings['sandbox_only'])) ? 'checked="checked"' : '';
            $col2 .= sprintf('<div class="enabled_sites"><input type="checkbox" name="sandbox_only[]" value="%d" %s> %s</div>',$site->blog_id, $checked ,$site->domain);
        }
        printf('<table><tr><th>Enabled</th><th>Sandbox Only</th></tr><tr><td>%s</td><td>%s</td></tr></table>',$col1,$col2);
        $message = isset($postmark_settings['site_admin_message']) ? $postmark_settings['site_admin_message'] : 'Your site is not currently allowed to send to more than 100 recipients. Contact the network administrator.';
        echo '<p>Message to administrators of sites not authorized to send to > 100 recipients.<br><textarea name="site_admin_message" cols="100" rows="5">'.$message.'</textarea></p>';
    }
    rsvpmaker_nonce();
    echo '<input type="hidden" name="tab" value="email">';
    submit_button();
    echo '</form>';

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
    if(sizeof($recipients) > 200) {
        $chunks = array_chunk($recipients,200);
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
    
    $html = rsvpmaker_email_html($mpost,$post_id);
    $html = rsvpmail_replace_placeholders($html);
    $text = rsvpmaker_text_version($html);
    $mail['Subject'] = do_shortcode($mpost->post_title);
    $mail['MessageStream'] = $message_stream;
    $mail['Tag'] = rsvpemail_tag($post_id);
    if(isset($meta['rsvprelay_from'][0]))
        $mail['ReplyTo'] = $meta['rsvprelay_from'][0];
    $mail['From'] = ($message_stream == $postmark_settings['postmark_tx_slug']) ? $postmark_settings['postmark_tx_from'] : $postmark_settings['postmark_broadcast_from'];
    $fromname = get_post_meta($post_id,'rsvprelay_fromname',true);
    if(empty($fromname))
        $fromname = get_bloginfo('name');
    $mail['From'] = rsvpmaker_email_add_name($mail['From'],$fromname);
    $client = new PostmarkClient($postmark_settings_key);
    if(!strpos($html,'rmail='))
    	$html = preg_replace_callback('/href="([^"]+)/','add_rsvpmail_arg',$html);		

    foreach($recipients as $index => $to) {
        if(isset($recipient_names[$to]))
            $mail['To'] = rsvpmaker_email_add_name($to,$recipient_names[$to]);
        else
            $mail['To'] = $to;
        $mail['HtmlBody'] = str_replace('*|EMAIL|*',$to,$html);
        $mail['TextBody'] = str_replace('*|EMAIL|*',$to,$text);
        $mail['Headers'] = array('X-Auto-Response-Suppress' => 'OOF'); //tells Exchange not to send out of office auto replies
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
        rsvpmaker_postmark_sent_log($sent,$mail['Subject'],$hash,$mail['Tag']);
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
    //wp_suspend_cache_addition(true);
    global $wpdb;
    $log = '';
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='rsvprelay_to_batch'";
	$results = $wpdb->get_results($sql);
	if($results) {
        $batchrow = $results[0];
        $doneafterthis = sizeof($results) == 1;
		$recipients = unserialize($batchrow->meta_value);
		$wpdb->query("update $wpdb->postmeta set meta_key='rsvprelay_to_batch_done' where meta_id=$batchrow->meta_id");
        $log .= rsvpmaker_postmark_broadcast($recipients,$batchrow->post_id);
        $postmark_options = get_rsvpmaker_postmark_options();
        if(!empty($postmark_options['notify_batch_send']))
            wp_mail(postmark_admin_email(),'Batched sending of email in progress',sizeof($recipients).' recipients ending with '.array_pop($recipients));
        if($doneafterthis) {
            $title = get_the_title($batchrow->post_id);
            $mail['subject'] = 'Sent: '.$title;
            $mail['html'] = sprintf('<p>The RSVPMaker Mailer for Postmark email broadcast is complete.</p> </p>See the results on the <a href="%s">Postmark Email Log</a> page. </p>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1&tag=rsvpemail-'.get_current_blog_id().'-'.$batchrow->post_id));
            $mail['from'] = $mail['to'] = get_option('admin_email');
            $mail['fromname'] = get_option('blogname');
            rsvpmailer($mail);
            $postmark_admin = postmark_admin_email();
            if($postmark_admin != $mail['to']) {
                $mail['to'] = $postmark_admin;
                rsvpmailer($mail);
            }
            wp_clear_scheduled_hook('rsvpmaker_postmark_chunked_batches');
        }
	}
    //wp_suspend_cache_addition(false);
}

function rsvpmaker_postmark_send($mail) {
    $postmark_settings = get_rsvpmaker_postmark_options();
    $mail['MessageStream'] = $postmark_settings['postmark_tx_slug'];
    $batch = rsvpmaker_postmark_batch($mail, $mail['to']);
    $result = rsvpmaker_postmark_batch_send($batch);
    return $result;
}

function rsvpmaker_postmark_incoming($forwarders,$emailobj,$post_id) {
    //wp_suspend_cache_addition(true);
    $admin_email = postmark_admin_email();
    $result = '';
    if($admin_email == $emailobj->From && 'stop' == $emailobj->Subject) {
        //emergency cutoff
        $postmark_settings = get_rsvpmaker_postmark_options();
        $postmark_settings['postmark_mode'] = '';
        update_blog_option(1,'rsvpmaker_postmark',$postmark_settings);
        mail($admin_email,'postmark deactivated',date('r'));
    }
    $postmark_settings = get_rsvpmaker_postmark_options();

	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
 	foreach($forwarders as $email) {
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
        if(!empty($slug_and_id)) {
            rsvpmaker_debug_log($slug_and_id,'slug and id');
            $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj);
            foreach($recipients as $index => $email)
                $recipients[$index] = rsvpmaker_email_add_name($email,'forwarded');
            if($recipients) {
                $batch = rsvpmaker_postmark_batch($emailobj, $recipients, $slug_and_id);
                $result = rsvpmaker_postmark_batch_send($batch);
            }
        }
	}
    //wp_suspend_cache_addition(false);
    return $result;
}

function rsvpmaker_postmark_array($source, $message_stream = 'broadcast', $slug_and_id = NULL) {
    rsvpmaker_debug_log($source,'postmark mail array source');
    //wp_suspend_cache_addition(true);
    global $via;
    $slug = (is_array($slug_and_id) && !empty($slug_and_id['slug'])) ? '['.$slug_and_id['slug'].'] ' : '';
    $blog_id = (is_array($slug_and_id) && !empty($slug_and_id['blog_id'])) ? $slug_and_id['blog_id'] : get_current_blog_id();
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
        if(isset($source['post_id'])) {
            $mail['post_id'] = $source['post_id'];
            $mail['Tag'] = rsvpemail_tag($source['post_id'],$blog_id);
        }
    }
    $mail['MessageStream'] = $message_stream;
    rsvpmaker_debug_log($mail,'postmark mail array out');
    //wp_suspend_cache_addition(false);
    return $mail;
}

function rsvpmaker_postmark_batch($mail, $recipients, $slug_and_id = NULL) {
    //wp_suspend_cache_addition(true);
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
        if(empty($mail['HtmlBody'])) {
            $mail['TextBody'] = rsvpmaker_personalize_email($mail['TextBody'],$to);
            $mail['HtmlBody'] = wpautop($mail['TextBody']);
        }
        else {
            $mail['HtmlBody'] = rsvpmaker_personalize_email($mail['HtmlBody'],$to);
            $mail['TextBody'] = (empty($mail['TextBody'])) ? rsvpmaker_text_version($mail['HtmlBody']) : rsvpmaker_text_version($mail['TextBody']);    
        }
        $mail['To'] = (isset($recipient_names[$to])) ? rsvpmaker_email_add_name($to,$recipient_names[$to]) : $to;
        $batch[] = $mail;
    }
    //wp_suspend_cache_addition(false);
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
        rsvpmaker_postmark_sent_log($sent,$batch[0]['Subject'],$hash,$batch[0]['Tag']);
        $output .= sprintf('Successful sends %d',count($sent));
        foreach($sent as $e) {
            if($post_id)
                $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$e."' AND post_id=$post_id ");
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
	$sql = $wpdb->prepare("select count(*) duplicates, subject, recipients, blog_id FROM ".$wpdb->base_prefix."postmark_tally where hash=%s AND time > DATE_SUB(NOW(), INTERVAL 120 MINUTE)",$hash);
	$row = $wpdb->get_row($sql);
    if(!empty($row->duplicates))
    {
        rsvpmaker_debug_log($row,'postmark duplicate blocked');
        return true;
    }
    return false;
}

function rsvpmaker_postmark_sent_log($sent, $subject='',$hash='', $tag='') {
	global $wpdb, $message_blog_id;
    $postmark = get_rsvpmaker_postmark_options();
	if(empty($message_blog_id))
		$message_blog_id = get_current_blog_id();
	$sql = $wpdb->prepare("insert into ".$wpdb->base_prefix."postmark_tally set count=%d, subject=%s, blog_id=%s, recipients=%s,hash=%s, tag=%s",sizeof($sent),$subject,$message_blog_id,implode(',',$sent), $hash, $tag);
	$wpdb->query($sql);
	$sent_lately = $wpdb->get_var("SELECT SUM(count) FROM ".$wpdb->base_prefix."postmark_tally WHERE time > DATE_SUB(NOW(), INTERVAL 15 MINUTE) ");
	$message = var_export($sent,true)."\n\n $sent_lately sent in the last 15 minutes";
	if((!empty($postmark['circuitbreaker'])) && ($sent_lately > $postmark['circuitbreaker'])) {
		switch_to_blog(1);
		$postmark = get_option('rsvpmaker_postmark');
		$postmark['postmark_mode'] = '';
		update_option('rsvpmaker_postmark',$postmark);
	}
    if($sent_lately > 50) {
        $overloadmessage = '';
        $score = 0;
        $sql = "SELECT `count`, recipients, subject FROM `".$wpdb->base_prefix."postmark_tally` WHERE time > DATE_SUB(NOW(), INTERVAL 15 MINUTE) group by recipients";
        $results = $wpdb->get_results($sql);
        foreach($results as $row) {
            $overloadmessage .= sprintf('%d %s %s'."\n",$row->count, $row->email,$row->subject);
            if($row->tally > 20)
                $score += $row->tally;
        }
        if($score > 50)
        {
            switch_to_blog(1);
            $postmark = get_option('rsvpmaker_postmark');
            $postmark['postmark_mode'] = '';
            update_option('rsvpmaker_postmark',$postmark);
            wp_mail(postmark_admin_email(),'Shutting down RSVPMaker/Postmark email delivery service because of overload',"Heavy use, $sent_lately within 15 minutes, warning score $score, resulting in this stream of messages\n".$overloadmessage);    
        }
        elseif(!empty($postmark['volume_warning']) && !empty($overloadmessage))
            wp_mail(postmark_admin_email(),'Recent email volume on RSVPMaker/Postmark >' .$sent_lately. ' in past 15 minutes',"Heavy use $sent_lately within 15 minutes, warning score $score, resulting in this stream of messages\n".$overloadmessage);
    }
}

function rsvpmaker_postmark_show_sent_log() {
    rsvpmaker_admin_heading('Postmark Email Log',__FUNCTION__);
    echo '<p>Postmark is the service we use for reliable email delivery. Here is a record of emails submitted to the Postmark service within the last month.</p>';
    global $wpdb;
    $table = $wpdb->base_prefix.'postmark_tally';
    $blog_id = get_current_blog_id();
    $days = isset($_GET['days']) ? intval($_GET['days']) : 31;
    $grandtotal = 0;
    $where = ($blog_id > 1) ? ' AND blog_id='.$blog_id : '';
    $sql = "SELECT sum(count) total, blog_id FROM `$table` WHERE time > DATE_SUB(NOW(), INTERVAL $days DAY) $where group by blog_id";
    $results = $wpdb->get_results($sql);
    foreach($results as $row) {
        $name = (is_multisite()) ? get_blog_option($row->blog_id,'blogname') : get_option('blogname');
        $text = sprintf('<strong>%s</strong>: %d ',$name,$row->total);
        $sums[$name] = $text;
        $grandtotal += $row->total;
    }
    if(!empty($sums)) {
        ksort($sums);
        echo '<p>Totals: '.implode(', ',$sums).'</p>';
        if($blog_id == 1)
            echo '<p><strong>Combined</strong>: '.$grandtotal.'</p>';
    }

    if(rsvpmaker_postmark_is_live()) {
        $postmark_settings = get_rsvpmaker_postmark_options();
        $client = new PostmarkClient($postmark_settings['postmark_production_key']);
        $detailsurl = admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1');
        $offset = 0;
        $recipient = NULL;
        $target_tag = isset($_GET['tag']) ? sanitize_text_field($_GET['tag']) : NULL;
        if($client) {
            $clicks = $client->getClickStatistics(500,$offset,$recipient,$target_tag);
            $clickcount = 0;
            if(!empty($clicks['clicks'])) {
                foreach($clicks['clicks'] as $click) {
                    if($blog_id > 1 && !strpos($click['Tag'],'-'.$blog_id.'-'))
                        continue;//ignore if not tagged for this blog id
                    //echo '<pre> click '.var_export($click,true).'</pre>';
                    $clickcount++;
                    if(strpos($click['originallink'],'unsubscribe'))
                        $unsub[] = isset($_GET['hide']) ? rsvpmaker_partiallyHideEmail($click['recipient']) : $click['recipient'];
                    else {
                        $tag = empty($click['Tag']) ? 'misc' : $click['tag'];
                        $email = isset($_GET['hide']) ? rsvpmaker_partiallyHideEmail($click['recipient']) : $click['recipient'];
                        $clicklog[$tag][] = sprintf('%s clicked by <strong>%s</strong> %s, Message ID %s',$click['originallink'],$email,$click['geo']['country'],$click['MessageId']);
                    }
                }
            }
            if($clickcount)
            {
                printf('<h3>Clicks: %d <a href="%s">(details)</a></h3>',$clickcount,$detailsurl);
                foreach($clicklog as $tag => $items)
                {
                    $title = ('misc' == $tag) ? 'miscellaneous' : postmark_tag_to_title($tag);
                    if(empty($title))
                        $title = 'miscellaneous';
                    printf('<p><strong>%s</strong> %s clicks</p>',$title,sizeof($items));
                    if(isset($_GET['details']))
                        echo '<p>'.implode('<br>',$items).'</p>';
                }
            }
            if(!empty($unsub))
                printf('<p>Unsubscribe clicks: %s</p>',implode(', ',$unsub));
            $opens = $client->getOpenStatistics(500, $offset, $recipient, $target_tag);
            $opencount = 0;
            if($opens['totalcount']) {
                foreach($opens['opens'] as $open) {
                    $tag = empty($open['Tag']) ? 'misc' : $open['Tag'];
                    if($blog_id > 1 && !strpos($open['Tag'],'-'.$blog_id.'-'))
                        continue;//ignore if not tagged for this blog id
                    //echo '<pre>open '.var_export($open,true).'</pre>';
                    $email = isset($_GET['hide']) ? rsvpmaker_partiallyHideEmail($open['recipient']) : $open['recipient'];
                    $opened[$tag][] = $email;
                    $opencount++;
                }
            }
            if($opencount)
                {
                printf('<h3>Opens: %d <a href="%s">(details)</a></h3>',$opencount,$detailsurl);                    
                foreach($opened as $tag => $items) 
                    {
                        $title = ('misc' == $tag) ? 'miscellaneous' : postmark_tag_to_title($tag);
                        if(empty($title))
                            $title = 'miscellaneous';
                        printf('<p><strong>%s</strong> (<a href="%s">Details</a>) %s opens</p>',$title,admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1&tag='.$tag),sizeof($items));
                        if(isset($_GET['details']))
                            echo '<p>'.implode(', ',$items).'</p>';
                    }
            }
            else {
                echo '<p>No email opens detected - check whether open tracking and link tracking are active on the Postmark server.</p>';
            }

            if(1 == $blog_id) {
                if(!wp_get_schedule('rsvpmaker_postmark_suppressions')) {
                    wp_schedule_event( rsvpmaker_strtotime('23:00:00'), 'daily', 'rsvpmaker_postmark_suppressions' );
                }	
                $suppressions = $client->getSuppressions('broadcast');
                if(isset($suppressions['suppressions'])) {
                    echo '<p>Suppressions (bad or blocked): ';
                    foreach($suppressions['suppressions'] as $s) {
                        echo $s['EmailAddress'].' ';
                        rsvpmail_add_problem($s['EmailAddress'],$s['SuppressionReason']);
                    } 
                    echo '<p>';
                }            
            }
        }
    }

    $days = (isset($_GET['days'])) ? intval($_GET['days']) : 31;
    printf('<form method="get" action="%s">Showing outgoing message data for <input type="hidden" name="post_type" value="rsvpemail" ><input type="hidden" name="page" value="rsvpmaker_postmark_show_sent_log" ><input name="days" value="%s"> days <button>Change</button></form>',admin_url('edit.php'),$days);

    if($blog_id > 1) {
        $sql = "SELECT * FROM $table WHERE time > DATE_SUB(NOW(), INTERVAL $days DAY) AND blog_id=$blog_id ORDER BY id DESC";
        $showmulti = false;
    }
    else {
        $sql = "SELECT * FROM $table  WHERE time > DATE_SUB(NOW(), INTERVAL 31 DAY) ORDER BY id DESC";
        $showmulti = is_multisite();
    }
    $results = $wpdb->get_results($sql);
    echo '<table class="wp-list-table widefat striped"><thead><tr><th>Subject</th><th># Recipients</th><th>Blog ID</th><th>Recipients</th><th>Details</th></tr></thead><tbody>';
    foreach($results as $row) {
        if(isset($_GET['showall']) && $row->id == intval($_GET['showall']))
            $recipients = str_replace(',',', ',$row->recipients);
        else
            $recipients = (strlen($row->recipients) > 200) ? substr($row->recipients,0,100).'... (<a href="'.admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&showall='.$row->id).'#row'.$row->id.'">Show All</a>)' : $row->recipients;
        $prompt = empty($row->tag) ? '' : sprintf('<a href="%s">Opens/Clicks</a><br>%s',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1&tag='.$row->tag),$row->tag);
        printf('<tr id="row%d"><td>%s<br>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',$row->id,$row->subject,$row->time,$row->count,$row->blog_id,$recipients,$prompt);
    }
    echo '</tbody></table>';

}
add_action('rsvpmaker_postmark_suppressions','rsvpmaker_postmark_suppressions');
function rsvpmaker_postmark_suppressions() {
    if(rsvpmaker_postmark_is_live()) {
        $postmark_settings = get_rsvpmaker_postmark_options();
        $client = new PostmarkClient($postmark_settings['postmark_production_key']);
    $suppressions = $client->getSuppressions('broadcast');
    if(isset($suppressions['suppressions'])) {
        foreach($suppressions['suppressions'] as $s) {
            rsvpmail_add_problem($s['EmailAddress'],$s['SuppressionReason']);
        } 
    }
    }
}

function postmark_tag_to_title($tag) {
global $wpdb;
$prefix = $wpdb->base_prefix;
$parts = explode('-',$tag);
$post_id = $blog_id = 0;
if(empty($parts[2]))
    return;//no post_id
$post_id = $parts[2];
if(!empty($parts[1]))
    $blog_id = $parts[1];
if($blog_id > 1)
    $prefix .= $blog_id.'_';
$sql = "SELECT post_title, post_type FROM ".$prefix."posts WHERE ID=$post_id";
$row = $wpdb->get_row($sql);
$title = $row->post_title;
if('rsvpmaker' == $row->post_type)
    {
        $event = get_rsvpmaker_event($post_id);
        if($event->ts_start)
        $title .= ' '.rsvpmaker_date('r',$event->ts_start);
    }
return $title;
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
        `tag` varchar(255) NOT NULL,
        `recipients` longtext NOT NULL,
        `hash` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
dbDelta($sql);
$version = 2;
if(is_multisite())
    update_blog_option(1,'postmark_tally_version',$version);
else
    update_option('postmark_tally_version',$version);
}

function check_postmark_tally_version() {
    $version = (int) (is_multisite()) ? get_blog_option(1,'postmark_tally_version') : get_option('postmark_tally_version');
    if($version < 2)
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

function postmark_admin_email() {
    return (is_multisite()) ? get_blog_option(1,'admin_email') : get_option('admin_email');
}
