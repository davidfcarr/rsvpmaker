<?php
// Import the Postmark Client Class:
require_once('postmark/vendor/autoload.php');
use Postmark\PostmarkClient;
use Postmark\PostmarkAdminClient;
use Postmark\Models\PostmarkException;
use Postmark\Models\Suppressions\SuppressionChangeRequest;

function get_rsvpmaker_postmark_options() {
    global $postmark_settings;
    $postmark_settings = get_option('rsvpmaker_postmark',array('postmark_mode' => '','root' => false, 'postmark_override_local' => 0));
    $postmark_settings['root'] = false;
    $postmark_settings['network_shared'] = false;
    $postmark_settings['central_share_enabled'] = false;

    if(is_multisite() && is_main_site()) {
        $postmark_settings['central_share_enabled'] = !empty($postmark_settings['postmark_network_share']);
    }

    if(is_multisite() && !is_main_site()) {
        $root_postmark_settings = get_blog_option(1,'rsvpmaker_postmark',array());
        $postmark_settings['central_share_enabled'] = !empty($root_postmark_settings['postmark_network_share']);
        $postmark_settings['postmark_override_local'] = !empty($postmark_settings['postmark_override_local']) ? 1 : 0;

        if($postmark_settings['central_share_enabled'] && empty($postmark_settings['postmark_override_local'])) {
            if(!empty($root_postmark_settings)) {
                $postmark_settings = array_merge($postmark_settings, $root_postmark_settings);
                $postmark_settings['root'] = true;
                $postmark_settings['network_shared'] = true;
                $postmark_settings['central_share_enabled'] = true;
                $postmark_settings['postmark_override_local'] = 0;
            }
        }
    }
    if((!empty($postmark_settings['restricted']) && !empty($postmark_settings['enabled'])) && !in_array(get_current_blog_id(),$postmark_settings['enabled']))
        $postmark_settings['postmark_mode'] = '';//disable
    if((!empty($postmark_settings['restricted']) && !empty($postmark_settings['sandbox_only'])) && in_array(get_current_blog_id(),$postmark_settings['sandbox_only']))
        $postmark_settings['postmark_mode'] = 'sandbox';
    if(empty($postmark_settings['sender_domains']))
        $postmark_settings['sender_domains'] = array();
    if((!empty($postmark_settings['postmark_production_key']) && 'production' == $postmark_settings['postmark_mode']) && (!is_multisite() || 1 == get_current_blog_id() )) {
        if(!wp_get_schedule('rsvpmaker_postmark_suppressions')) {
            //if we're sending bulk email, be sure to schedule a daily check for Postmark suppressions
            wp_schedule_event( rsvpmaker_strtotime('23:00:00'), 'daily', 'rsvpmaker_postmark_suppressions' );
        }
    }
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
    if(rsvpmaker_postmark_is_live()) {
        echo '<p>RSVPMaker\'s integration with the Postmark service is live, ensuring reliable message delivery</p>';
    }
    elseif(rsvpmaker_postmark_is_active())
        echo '<p>Postmark integration is in sandbox mode, meaning RSVPMaker messages will only be sent to a test instance of the Postmark cloud.</p>';
    else
        echo '<p>RSVPMaker\'s integration with Postmark is not active on this site.</p>';
    do_action('show_rsvpmaker_postmark_status');
}

function rsvpmaker_postmark_options() {
return printf('<p>Postmark Email Settings can now be found on a the <a href="%s">Postmark tab</a> of the main RSVPMaker settings page.</p>',admin_url('options-general.php?page=rsvpmaker_settings&tab=postmark'));    
//disabled
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
        $postmark_settings['sandbox_only'] = (isset($_POST['sandbox_only'])) ? array_map('intval',$_POST['sandbox_only']) : array();
        $postmark_settings['postmark_load_alert_emails'] = sanitize_text_field($_POST['postmark_load_alert_emails']);
        if(empty(trim($_POST['sender_domains'])))
        $postmark_settings['sender_domains'] = array();
        else {
            $sd = explode(',',sanitize_text_field($_POST['sender_domains']));
            foreach($sd as $index => $s)
                $sd[$index] = trim($s);
            $postmark_settings['sender_domains'] = $sd;
        }
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
        if(empty($postmark_settings['postmark_load_alert_emails']))
            $postmark_settings['postmark_load_alert_emails'] = rsvpmaker_postmark_admin_email();
    echo '<p>To fill in these variables, first <a href="https://account.postmarkapp.com/sign_up" target="_blank">create a Postmark account</a>. Postmark provides reliable email deliver for both broadcast / mailing list messages and transactional messages such as RSVP confirmations. Premium add-ons and customization services for managing email forwarding and metered access for multisite site owners are available from <a href="mailto:david@rsvpmaker.com" target="_blank">david@rsvpmaker.com</a>.</p>';        
    printf('<form method="post" action="%s">',admin_url('options-general.php?page=rsvpmaker_settings&tab=email'));
    $checked = (empty($postmark_settings['postmark_mode'])) ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="" %s> Off - Postmark not managing email</p>',$checked);
    $checked = ($postmark_settings['postmark_mode'] == 'sandbox') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="sandbox" %s> Sandbox / Test, Key <input type="text" name="postmark_sandbox_key" value="%s"></p>',$checked, $postmark_settings['postmark_sandbox_key']);
    $checked = ($postmark_settings['postmark_mode'] == 'production') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="production" %s> Production, Key <input type="text" name="postmark_production_key" value="%s"></p>',$checked, $postmark_settings['postmark_production_key']);
    printf('<p>Transactional Messages From: <input type="text" name="postmark_tx_from" value="%s"> Stream ID <input type="text" name="postmark_tx_slug" value="%s"></p>',$postmark_settings['postmark_tx_from'],$postmark_settings['postmark_tx_slug']);
    printf('<p>Broadcast Messages From: <input type="text" name="postmark_broadcast_from" value="%s"> Stream ID <input type="text" name="postmark_broadcast_slug" value="%s"></p>',$postmark_settings['postmark_broadcast_from'],$postmark_settings['postmark_broadcast_slug']);
    printf('<p>Sender Domains: <input type="text" name="sender_domains" value="%s" size="100"> <br ?><em>Comma-separated list of domains for which sender signature has been configured to allow all sender addresses.</em></p>',implode(',',$postmark_settings['sender_domains']));
    printf('<p>Heavy Load Alerts Email: <input type="text" name="postmark_load_alert_emails" value="%s"><br />Alert to a high volume of emails sent</p>',$postmark_settings['postmark_load_alert_emails']);
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
    error_log('rsvpmaker_postmark_broadcast start '.var_export($recipients,true));
    $recipients = rsvpmaker_recipients_no_problems($recipients);
    error_log('rsvpmaker_postmark_broadcast filtered '.var_export($recipients,true));
    if(empty($recipients))
        return;
    if(sizeof($recipients) > 200) {
        $chunks = array_chunk($recipients,200);
        echo $log = sprintf('message split into %s chunks for '.$post_id,sizeof($chunks));
        error_log($log);
        $recipients = array_shift($chunks);
        foreach($chunks as $chunk) {
            $size = sizeof($chunk);
            $meta_id = add_post_meta($post_id,'rsvprelay_to_batch',$chunk);
            $check = $wpdb->get_row($wpdb->prepare("select * from %i where meta_id=%d",$wpdb->postmeta,$meta_id));
            error_log('saving emails from '.$chunk[0].' to '.$chunk[$size-1]. 'for post '.$post_id.' rsvprelay_to_batch result '.var_export($meta_id,true).' check '.var_export($check,true));
        }
        error_log('scheduling rsvpmaker_postmark_chunked_batches');
        wp_schedule_event( strtotime('+95 seconds'), 'minute', 'rsvpmaker_postmark_chunked_batches' );
    }

    $postmark_settings = get_rsvpmaker_postmark_options();
    $postmark_settings_key = ('production' == $postmark_settings['postmark_mode']) ? $postmark_settings['postmark_production_key'] : $postmark_settings['postmark_sandbox_key'];
    if(empty($message_stream))
        $message_stream = (sizeof($recipients) > 1) ? $postmark_settings['postmark_broadcast_slug'] : $postmark_settings['postmark_tx_slug'];
    $mpost = get_post($post_id);

    $html = rsvpmaker_email_html($mpost,$post_id);
    $text = rsvpmaker_text_version($html);
    $mail['Subject'] = do_shortcode($mpost->post_title);
    $mail['MessageStream'] = $message_stream;
    $mail['Tag'] = rsvpemail_tag($post_id);
    $reply_to = get_post_meta($post_id,'rsvprelay_from',true);
    if(!empty($reply_to))
        $mail['ReplyTo'] = $reply_to;
    else
        $mail['ReplyTo'] = get_option('admin_email');
    $mail['From'] = ($message_stream == $postmark_settings['postmark_tx_slug']) ? $postmark_settings['postmark_tx_from'] : $postmark_settings['postmark_broadcast_from'];
    $forwardto_from = rsvpmaker_postmark_forwardto_from_replyto($mail['ReplyTo']);
    if($forwardto_from)
        $mail['From'] = $forwardto_from;
    $fromname = get_post_meta($post_id,'rsvprelay_fromname',true);
    if(empty($fromname))
        $fromname = get_bloginfo('name');
    $mail['From'] = rsvpmaker_email_add_name($mail['From'],$fromname);

    error_log('postmark broadcast array'.var_export($mail,true));

    $client = new PostmarkClient($postmark_settings_key);

    foreach($recipients as $index => $to) {
        if(isset($recipient_names[$to]))
            $mail['To'] = rsvpmaker_email_add_name($to,$recipient_names[$to]);
        else
            $mail['To'] = $to;
        $mail['HtmlBody'] = rsvpmaker_personalize_email($html,$to);
        $mail['TextBody'] = str_replace('*|EMAIL|*',$to,$text);
        $mail['Headers'] = array('X-Auto-Response-Suppress' => 'OOF'); //tells Exchange not to send out of office auto replies
        $batch[] = $mail;
        $wpdb->query($wpdb->prepare("update %i SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE %s AND post_id=%d "
    ,$wpdb->postmeta,$to,$post_id));
    }

    $hash = rsvpmaker_postmark_batch_hash($batch,$recipients);
    error_log('postmark broadcast hash '.$hash);
    if(rsvpmaker_postmark_duplicate($hash)) {
        error_log('rsvpmaker postmark broadcast duplicate message ');
        return 'Duplicate message';
    }

    $responses = $client->sendEmailBatch($batch);
    error_log('postmark broadcast responses '.var_export($responses,true));

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
            error_log('postmark send error '.var_export($error,true).' '.var_export($recipients,true).' '.var_export($mail,true));
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
    return count($sent);
}

add_action('rsvpmaker_postmark_chunked_batches','rsvpmaker_postmark_chunked_batches');
function rsvpmaker_postmark_chunked_batches() {
    //wp_suspend_cache_addition(true);
    global $wpdb;
    $log = '';
	$sql = $wpdb->prepare("SELECT * FROM %i WHERE meta_key='rsvprelay_to_batch'",$wpdb->postmeta);
    error_log($sql);
	$results = $wpdb->get_results($sql);
    error_log('rsvpmaker_postmark_chunked_batches '.sizeof($results));
	if($results) {
    $last_batchrow = null;
	foreach($results as $index => $batchrow) {
    if(10 == $index)
        break;
	$recipients = unserialize($batchrow->meta_value);
        $last_batchrow = $batchrow;
        //returns number sent, 'duplicate message' or null
	$batch_result = rsvpmaker_postmark_broadcast($recipients,$batchrow->post_id);
	if($batch_result)
	{
        $sql = $wpdb->prepare("update %i set meta_key='rsvprelay_to_batch_done' where meta_id=%d",$wpdb->postmeta,$batchrow->meta_id);
        error_log($sql);
	$wpdb->query($sql);
	}
        $postmark_options = get_rsvpmaker_postmark_options();
        if(!empty($postmark_options['notify_batch_send']))
            wp_mail(rsvpmaker_postmark_admin_email(),'Batched sending of email in progress',sizeof($recipients).' recipients ending with '.array_pop($recipients));
    }
	if(!rsvpmaker_postmark_pending_batch_count()) {
		if($last_batchrow) {
			$title = get_the_title($last_batchrow->post_id);
            $mail['subject'] = 'Sent: '.$title;
            $mail['html'] = sprintf('<p>The RSVPMaker Mailer for Postmark email broadcast is complete.</p> </p>See the results on the <a href="%s">Postmark Email Log</a> page. </p>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1&tag=rsvpemail-'.get_current_blog_id().'-'.$last_batchrow->post_id));
            $mail['from'] = $mail['to'] = get_option('admin_email');
            $mail['fromname'] = get_option('blogname');
            rsvpmailer($mail);
            $postmark_admin = rsvpmaker_postmark_admin_email();
            if($postmark_admin != $mail['to']) {
                $mail['to'] = $postmark_admin;
                rsvpmailer($mail);
            }
        }
        wp_clear_scheduled_hook('rsvpmaker_postmark_chunked_batches');
    }
	else {
		error_log('keeping postmark chunked batches schedule active: '.rsvpmaker_postmark_pending_batch_count().' batches remain');
	}
	}
    else {
        error_log('ending postmark chunked batches');
        wp_clear_scheduled_hook('rsvpmaker_postmark_chunked_batches');
    }
    //wp_suspend_cache_addition(false);
}

function rsvpmaker_postmark_pending_batch_count() {
	global $wpdb;
	$sql = $wpdb->prepare("SELECT count(*) FROM %i WHERE meta_key='rsvprelay_to_batch'",$wpdb->postmeta);
	return (int) $wpdb->get_var($sql);
}

function rsvpmaker_postmark_send($mail) {
    $postmark_settings = get_rsvpmaker_postmark_options();
    $mail['MessageStream'] = $postmark_settings['postmark_tx_slug'];
    $batch = rsvpmaker_postmark_batch($mail, $mail['to']);
    $result = rsvpmaker_postmark_batch_send($batch);
    error_log('rsvpmaker_postmark_send result '.var_export($result,true));
    return $result;
}

function rsvpmaker_postmark_incoming_list_signup($emailobj, $forwarders) {
    set_transient('postmark_incoming_list_input',$emailobj,DAY_IN_SECONDS);
    $email = $emailobj->From;
    $name = (empty($emailobj->FromFull->Name)) ? '' : $emailobj->FromFull->Name;
    if(empty($name)) {
        $first = $last = '';
    }
    else
        {
            $parts = explode(' ',$name);
            $last = array_pop($parts);
            $first = implode(' ',$parts);
        }
    $result = rsvpmaker_guest_list_add($email, $first, $last, 'incoming_email_signup', false);
}


function rsvpmaker_parse_domain($full_domain) {
    $parts = explode('.', $full_domain);
    $count = count($parts);
    
    // List of common two-part TLDs (add more as needed for your target audience)
    $multi_part_tlds = ['co.uk', 'me.uk', 'org.uk', 'ltd.uk', 'com.au', 'net.au', 'co.nz', 'co.za'];
    
    // If we have at least 3 parts, check if the last two match a known multi-part TLD
    if ($count >= 3) {
        $possible_tld = $parts[$count - 2] . '.' . $parts[$count - 1];
        
        if (in_array($possible_tld, $multi_part_tlds)) {
            // It's a multi-part TLD like example.co.uk
            if ($count > 3) {
                // We have a subdomain! (e.g., sub.example.co.uk)
                $prefix = array_shift($parts); // Extract 'sub'
                $domain = implode('.', $parts); // Leaves 'example.co.uk'
                return ['prefix' => $prefix, 'domain' => $domain];
            } else {
                // No subdomain (e.g., example.co.uk)
                return ['prefix' => '', 'domain' => $full_domain];
            }
        }
    }
    
    // Fallback standard behavior for 2-part TLDs (like .com, .org)
    if ($count > 2) {
        $prefix = array_shift($parts); // Extract 'sub'
        $domain = implode('.', $parts); // Leaves 'example.com'
        return ['prefix' => $prefix, 'domain' => $domain];
    }
    
    // Naked domain with standard TLD (e.g., example.com)
    return ['prefix' => '', 'domain' => $full_domain];
}

function rsvpmaker_valid_mailing_lists($cache_ok = true) {
    if(is_multisite() && !is_main_site())
        switch_to_blog(1);
    if($cache_ok)
        $lists = get_transient('rsvpmaker_valid_mailing_lists');
    if(!empty($lists))
        return $lists;
    $members = array();
    $officers = array();
    $subdomains = array();
    $subdomain_id = array();
    $domain_id = array();
    $domains = array();
    $info = array();
    $id_by = array();
    if(is_multisite()) {
        $sites = get_sites(array('number'=>1000));
        foreach($sites as $site) {
            $parsed = rsvpmaker_parse_domain($site->domain);
            $prefix = $parsed['prefix'];
            $domain = $parsed['domain'];
            $parts = explode('.',$site->domain);
            if(!empty($prefix)) {
                $subdomains[] = $prefix;
                $id_by[$prefix] = $site->blog_id;
                $domains[] = $domain;
                $members[] = $prefix.'@'.$domain;
                $members[] = $prefix.'-members@'.$domain;
                $officers[] = $prefix.'-officers@'.$domain;
                $info[] = $prefix.'-info@'.$domain;
            }
            else {
                $domain = $site->domain;
                $id_by[$domain] = $site->blog_id;
                $domains[] = $domain;
                $subdomains[] = '';
                $members[] = 'members@'.$domain;
                $officers[] = 'officers@'.$domain;
                $info[] = 'info@'.$domain;
            }
        }
    }
    else {
        $url = get_option('siteurl');
        $domain = str_replace('www.','',parse_url(strtolower($url), PHP_URL_HOST));
        $domains[] = $domain;
        $id_by[$domain] = 1;
        $subdomains[] = '';
        $members[] = 'members@'.$domain;
        $officers[] = 'officers@'.$domain;
        $info[] = 'info@'.$domain;
    }
    $lists = array('members' => $members, 'officers' => $officers, 'info' => $info, 'subdomains' => $subdomains, 'id_by' => $id_by, 'domains' => $domains);
    set_transient('rsvpmaker_valid_mailing_lists',$lists,HOUR_IN_SECONDS);
    if(is_multisite())
        restore_current_blog();
    return $lists;
}

function rsvpmaker_postmark_incoming_forwarder_parse($email, $list_targets) {
    if(!is_email($email))
        return false;
    $parts = explode('@',$email);
    $prefix_parts = explode('-',$parts[0]);
    $prefix_parts_size = sizeof($prefix_parts);
    $forwarder_slug = '';
    $prefix = '';
    if('forwardto' == $prefix_parts[0] && 2 == $prefix_parts_size) {
        $type = 'forwardto';
        $blog_id = 0;
        $prefix = '';
        $forwarder_slug = $prefix_parts[1];
    }
    elseif(in_array($email,$list_targets['members'])) {
        $type = 'members';
        if($prefix_parts_size > 1 && !empty($list_targets['id_by'][$prefix_parts[0]])) {
            $blog_id = isset($list_targets['id_by'][$prefix_parts[0]]) ? $list_targets['id_by'][$prefix_parts[0]] : 0;
        }
        elseif($prefix_parts_size == 1 && in_array($prefix_parts[0], $list_targets['subdomains']) && !empty($list_targets['id_by'][$prefix_parts[0]])) {
            $blog_id = $list_targets['id_by'][$prefix_parts[0]];
        }
        elseif($prefix_parts_size == 1 && !empty($list_targets['id_by'][$parts[1]])) {
            $blog_id = $list_targets['id_by'][$parts[1]];
        }
        else {
            return false;
        }
    }
    elseif(in_array($email,$list_targets['officers'])) {
        $type = 'officers';
        if($prefix_parts_size > 1 && !empty($list_targets['id_by'][$prefix_parts[0]])) {
            $blog_id = $list_targets['id_by'][$prefix_parts[0]];
            $prefix = $prefix_parts[0];
        }
        elseif($prefix_parts_size == 1 && !empty($list_targets['id_by'][$parts[1]])) {
            $blog_id = $list_targets['id_by'][$parts[1]];
            $prefix = '';
        }
        else {
            return false;
        }
    }
    elseif(in_array($email,$list_targets['info'])) {
        $type = 'info';
        $forwarder_slug = 'info';
        if($prefix_parts_size > 1 && !empty($list_targets['id_by'][$prefix_parts[0]])) {
            $blog_id = $list_targets['id_by'][$prefix_parts[0]];
            $prefix = $prefix_parts[0];
        }
        elseif($prefix_parts_size == 1 && !empty($list_targets['id_by'][$parts[1]])) {
            $blog_id = $list_targets['id_by'][$parts[1]];
            $prefix = '';
        }
        else {
            return false;
        }
    }
    elseif ($prefix_parts_size > 1 && !empty($list_targets['id_by'][$prefix_parts[0]])) {
        $type = 'forwarder';
        $blog_id = is_numeric($prefix_parts[0]) ? intval($prefix_parts[0]) : (isset($list_targets['id_by'][$prefix_parts[0]]) ? $list_targets['id_by'][$prefix_parts[0]] : 0);
        $prefix = $prefix_parts[0];
        $forwarder_slug = $prefix_parts[1];
    }
    elseif(!empty($list_targets['id_by'][$parts[1]])) {
        $type = 'forwarder';
        $blog_id = is_numeric($prefix_parts[0]) ? intval($prefix_parts[0]) : (isset($list_targets['id_by'][$prefix_parts[0]]) ? $list_targets['id_by'][$prefix_parts[0]] : 0);
        $prefix = '';
        $forwarder_slug = $prefix_parts[0];
    }
    else {
        return false;
    }
    return array('type' => $type, 'blog_id' => $blog_id, 'subdomain' => $prefix, 'forwarder_slug' => $forwarder_slug,'domain' => $parts[1]);
}

function rsvpmaker_postmark_incoming($forwarders,$emailobj,$post_id) {
    global $custom_from;
    set_transient('postmark_incoming_emailobj',$emailobj,DAY_IN_SECONDS);

    rsvpmaker_testlog('postmark_incoming_emailobj',$emailobj);
    if(strpos($emailobj->Subject,'Add me to your email list') !== false) {
        rsvpmaker_postmark_incoming_list_signup($emailobj, $forwarders);
        return;
    }
    rsvpmaker_testlog('postmark_incoming_forwarders',$forwarders);
    //wp_suspend_cache_addition(true);
    $admin_email = rsvpmaker_postmark_admin_email();
    $result = '';

    if($admin_email == $emailobj->From && 'stop' == $emailobj->Subject) {
        //emergency cutoff
        $postmark_settings = get_rsvpmaker_postmark_options();
        $postmark_settings['postmark_mode'] = '';
        update_blog_option(1,'rsvpmaker_postmark',$postmark_settings);
        mail($admin_email,'postmark deactivated',date('r'));
    }
    $postmark_settings = get_rsvpmaker_postmark_options();
    $tx_from = trim(strtolower($postmark_settings['postmark_tx_from']));
    $broadcast_from = trim(strtolower($postmark_settings['postmark_broadcast_from']));
    $list_targets = rsvpmaker_valid_mailing_lists();
    $flattened = rsvpmaker_all_flattened_forwarders();

    //test new approach
    $from = strtolower($emailobj->From);
    $testoutput = '';
    $testrecipients = [];
    $recipients = array();
    $sent = [];
    foreach($forwarders as $email) {
        $email = trim(strtolower($email));
        $result = rsvpmaker_postmark_incoming_forwarder_parse($email, $list_targets);
        $blog_id = $result['blog_id'];
        $blacklist = (!is_multisite() || 1 == $blog_id) ? get_option('rsvpmail_blacklist') : get_blog_option($blog_id, 'rsvpmail_blacklist');
        if(is_array($blacklist) && in_array($from,$blacklist))
            {   
                error_log("$from on rvspmail_blacklist for $blog_id");
                rsvpmaker_testlog('postmark_incoming_output',$testoutput);
                continue;
            }
        $output .= sprintf('<p>%s = %s</p>',$email,var_export($result,true));
        if('info' == $result['type']) {
            $from = is_multisite() ? get_blog_option($result['blog_id'],'admin_email') : get_option('admin_email');
            do_action('rsvpmaker_postmark_autoreply',$emailobj,$result['blog_id'],$from);
            error_log(sprintf('Autoreply action called for %s from %s %s',$email,$from,htmlentities(var_export($emailobj,true))));
            $result['type'] = 'forwarder'; //continue processing as forwarder
        }
        if('forwarder' == $result['type']) {
            if(!empty($flattened[$email])) {
                $recipients = array_merge($recipients,$flattened[$email]);
            }
            else {
                error_log('No flattened forwarder for '.$email);
            }
        }
        elseif('forwardto' == $result['type']) {
            $list = rsvpmaker_forward_to_user($result['forwarder_slug']);
            $recipients = array_merge($recipients,$list);
        }
        elseif('members' == $result['type']) {
            $list = rsvpmaker_get_mailing_list_forwarders($result['blog_id'],$result['type'],$from);
            if(is_array($list))
                $recipients = array_merge($recipients,$list);
            else {
                if('BLOCKED' == $list)
                    error_log('postmark incoming BLOCKED '.$email.' '.$from);
                else
                    error_log('postmark incoming no members list for '.$email.' '.$from);
            }
            $emailobj->Subject = '[members] '.$emailobj->Subject;
        }
        elseif('officers' == $result['type']) {
            $list = rsvpmaker_get_mailing_list_forwarders($result['blog_id'],$result['type'],$from);
            if(is_array($list))
                $recipients = array_merge($recipients,$list);
            else {
                if('BLOCKED' == $list)
                    error_log('postmark incoming BLOCKED '.$email.' '.$from);
                else
                    error_log('postmark incoming no officers list for '.$email.' '.$from);
            }
            $emailobj->Subject = '[officers] '.$emailobj->Subject;
        }
    }
    if(!empty($recipients)) {
        $recipients = array_unique($recipients);
        $batch = rsvpmaker_postmark_batch($emailobj, $recipients, $breakdown);
        $result = rsvpmaker_postmark_batch_send($batch);
        error_log('postmark forwarding for address '.var_export($forwarders,true)."\n".var_export($recipients,true));
        $testoutput .= "\nSEND\n".$result;
    }
    error_log('postmark_incoming_output '.$testoutput);
    return $testoutput;
}

function rsvpmaker_postmark_array($source, $message_stream = 'broadcast', $slug_and_id = NULL) {
    //wp_suspend_cache_addition(true);
    global $via, $custom_from;
    $slug = '';
    if(is_array($slug_and_id) && 'forwardto' == $slug_and_id['subdomain'])
        $slug = '[fwd] ';
    elseif(is_array($slug_and_id) && !empty($slug_and_id['slug']))
        $slug = '['.$slug_and_id['slug'].'] ';
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
        $mail['From'] = rsvpmaker_postmark_array_from($mail['From'],$slug_and_id,$mail['ReplyTo'], $postmark_settings);
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
        $mail['ReplyTo'] = $source['From'];
        $mail['From'] = ($postmark_settings['postmark_broadcast_slug'] == $message_stream) ? $postmark_settings['postmark_broadcast_from'] : $postmark_settings['postmark_tx_from'];//check
        $mail['From'] = rsvpmaker_postmark_array_from($mail['From'],$slug_and_id,$mail['ReplyTo'],$postmark_settings);
        if(!empty($source['FromName']))
            $mail['From'] = rsvpmaker_email_add_name($mail['From'],$source['FromName'].$via);
		$body['MessageStream'] = $message_stream;
        if(isset($source['post_id'])) {
            $mail['post_id'] = $source['post_id'];
            $mail['Tag'] = rsvpemail_tag($source['post_id'],$blog_id);
        }
    }
    $mail['MessageStream'] = $message_stream;
    //wp_suspend_cache_addition(false);
    return $mail;
}

function rsvpmaker_postmark_array_from($from,$slug_and_id,$reply_to,$postmark_settings) {
    $forwardto_from = rsvpmaker_postmark_forwardto_from_replyto($reply_to,$slug_and_id);
    if($forwardto_from)
        return $forwardto_from;

    $rparts = explode('@',$reply_to);
	$good_domains = $postmark_settings['sender_domains'];
	if(in_array($rparts[1],$good_domains))
	{	
		return $reply_to;
	}
	elseif(is_array($slug_and_id)) {
        $user = get_user_by('email',$reply_to);
		if($user && !strpos($user->user_login,'@')) {
            $from = 'forwardto-'.$user->user_login.'@'.rsvpmaker_postmark_sender_domain($slug_and_id);
		}
		elseif(is_array($slug_and_id) && in_array($slug_and_id['domain'],$good_domains))
		{	
			if(empty($slug_and_id['subdomain']))
				$from = 'info@'.$slug_and_id['domain'];
			else
				$from = $slug_and_id['subdomain'].'-info@'.$slug_and_id['domain'];
		}	
	}
	return $from;
}

function rsvpmaker_postmark_sender_domain($slug_and_id = NULL) {
    if(is_multisite()) {
        $home = get_blog_option(1,'home');
        $home = parse_url(strtolower($home), PHP_URL_HOST);
        error_log('rsvpmaker_postmark_sender_domain multisite home '.$home);
    }
    elseif(is_array($slug_and_id) && !empty($slug_and_id['blog_id'])) {
        $home = get_option($slug_and_id['blog_id']);
    }
    if(empty($home)) {
        $home = get_option('home');
    }
    if(empty($home))
        $home = get_site_url();
    $domain = parse_url(strtolower($home), PHP_URL_HOST);
    error_log('rsvpmaker_postmark_sender_domain '.$domain.' from '.var_export($slug_and_id,true).' based on home '.$home);
    if(empty($domain))
        return '';
    return preg_replace('/^www\./','',$domain);
}

function rsvpmaker_forward_to_user($login_or_id) {
    $recipients = array();
    $user = (is_numeric($login_or_id)) ? get_user_by('id',$login_or_id) : get_user_by('login',$login_or_id);
    if($user && !empty($user->user_email))
        $recipients[] = $user->user_email;
    return $recipients;
}

function rsvpmaker_postmark_forwardto_from_replyto($reply_to,$slug_and_id = NULL) {
    global $postmark_settings;
    if(empty($postmark_settings))
        $postmark_settings = get_rsvpmaker_postmark_options();
    $reply_to_email = sanitize_email($reply_to);
    $domain = is_multisite() ? get_blog_option(1,'home') : get_option('home');
    $domain = str_replace('www.','',parse_url(strtolower($domain), PHP_URL_HOST));
    error_log('rsvpmaker_postmark_forwardto_from_replyto '.$reply_to.' '.$domain.' '.var_export($slug_and_id,true));
    if(strpos($reply_to,$domain)) {
        error_log('rsvpmaker_postmark_forwardto_from_replyto returning '.$reply_to_email);
        return $reply_to_email;
    }
    if(empty($reply_to_email) && preg_match('/<([^>]+)>/',$reply_to,$matches))
        $reply_to_email = sanitize_email($matches[1]);
    if(empty($reply_to_email))
        return 'info@'.$domain;
    $user = get_user_by('email',$reply_to_email);
    if(!$user)
        return $postmark_settings['postmark_broadcast_from'];
    error_log('rsvpmaker_postmark_forwardto_from_replyto '.$user->user_login.' '.$user->ID.' '.$domain);
    if(strpos($user->user_login,'@') || strpos($user->user_login,'-'))
        return 'forwardto-'.$user->ID.'@'.$domain;
    if(empty($domain)) {
        error_log('no domain found in rsvpmaker_postmark_forwardto_from_replyto');
        return $postmark_settings['postmark_broadcast_from'];
    }
    return 'forwardto-'.$user->user_login.'@'.$domain;
}

function rsvpmaker_postmark_batch($mail, $recipients, $slug_and_id = NULL) {

    //error_log('rsvpmaker_postmark_batch slug and id '.var_export($slug_and_id,true));
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
    $hash = rsvpmaker_postmark_batch_hash($batch);
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
        rsvpmaker_postmark_sent_log($sent,$batch[0]['Subject'],$hash,isset($batch[0]['Tag']) ? $batch[0]['Tag'] : '');
        $output .= sprintf('Successful sends %d',count($sent));
        foreach($sent as $e) {
            if($post_id)
                $wpdb->query($wpdb->prepare("update %i SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE %s AND post_id=%d ",
            $wpdb->postmeta,$e,$post_id));
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

function rsvpmaker_postmark_batch_hash ($batch,$recipients = null) {
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
    rsvpmaker_check_postmark_tally_version();
	$sql = $wpdb->prepare("select count(*) duplicates, subject, recipients, blog_id FROM %i where hash=%s AND time > DATE_SUB(NOW(), INTERVAL 120 MINUTE)",$wpdb->base_prefix."postmark_tally",$hash);
	$row = $wpdb->get_row($sql);
    if(!empty($row->duplicates))
    {
        return true;
    }
    return false;
}

function rsvpmaker_postmark_sent_log($sent, $subject='',$hash='', $tag='') {
	global $wpdb, $message_blog_id;
    $name = get_bloginfo('name');
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
    if($sent_lately > 150) {
        $overloadmessage = '';
        $score = 0;
        $sql = $wpdb->prepare("SELECT `count`, recipients, subject FROM %i WHERE time > DATE_SUB(NOW(), INTERVAL 15 MINUTE) group by recipients",$wpdb->base_prefix."postmark_tally");
        $results = $wpdb->get_results($sql);
        foreach($results as $row) {
            $overloadmessage .= sprintf('%d %s %s',$row->count,$row->recipients,$row->subject);
            if($row->count > 20)
                $score += $row->count;
        }
        if($score > 500)
        {
            wp_mail($postmark['postmark_load_alert_emails'],$name . ' - Heavy email volume on RSVPMaker/Postmark >' .$sent_lately. ' in past 15 minutes',"Heavy use $sent_lately within 15 minutes, warning score $score, resulting in this stream of messages\n".$overloadmessage);
        }
        elseif(!empty($postmark['volume_warning']) && !empty($overloadmessage)){
            wp_mail($postmark['postmark_load_alert_emails'],$name . ' - Recent email volume on RSVPMaker/Postmark >' .$sent_lately. ' in past 15 minutes',"Heavy use $sent_lately within 15 minutes, warning score $score, resulting in this stream of messages\n".$overloadmessage);
        }
    }
}

function rsvpmaker_postmark_message_timestamp($message) {
    $date_keys = array('ReceivedAt','SubmittedAt','MessageDate','Date');
    foreach($date_keys as $key) {
        if(!empty($message[$key])) {
            $ts = strtotime($message[$key]);
            if($ts)
                return $ts;
        }
    }
    return 0;
}

function rsvpmaker_postmark_message_value($message, $key, $default = null) {
    if(is_array($message)) {
        if(array_key_exists($key,$message))
            return $message[$key];
        $alt_keys = array(
            strtolower($key),
            preg_replace('/_/', '', strtolower($key)),
        );
        foreach($alt_keys as $alt_key) {
            if(array_key_exists($alt_key,$message))
                return $message[$alt_key];
        }
    }
    if(is_object($message)) {
        if($message instanceof ArrayAccess && isset($message[$key]))
            return $message[$key];
        if(isset($message->$key))
            return $message->$key;
    }
    return $default;
}

function rsvpmaker_postmark_debug_to_array($v, $depth = 0) {
    if($depth > 6) return '...';
    if(is_object($v) && ($v instanceof Traversable)) {
        $out = array();
        foreach($v as $k => $val) {
            $out[$k] = rsvpmaker_postmark_debug_to_array($val, $depth + 1);
        }
        return $out;
    }
    if(is_array($v)) {
        return array_map(function($val) use ($depth) { return rsvpmaker_postmark_debug_to_array($val, $depth + 1); }, $v);
    }
    return $v;
}

function rsvpmaker_postmark_debug_collection($response, $key) {
    $collection = rsvpmaker_postmark_message_value($response,$key,array());
    if($collection instanceof Traversable)
        return iterator_to_array($collection);
    if(is_array($collection))
        return $collection;
    return array();
}

function rsvpmaker_postmark_extract_recipient($message) {
    // Opens/clicks API uses 'Recipient' (plain string)
    $r = rsvpmaker_postmark_message_value($message, 'Recipient', '');
    if(is_scalar($r) && '' !== (string)$r) return (string)$r;
    // Messages API uses 'Recipients' (array of email strings)
    $list = rsvpmaker_postmark_message_value($message, 'Recipients', null);
    if(null !== $list) {
        if(is_object($list) && ($list instanceof ArrayAccess)) {
            $first = $list[0];
            if(is_scalar($first)) return (string)$first;
        }
        if(is_array($list) && !empty($list)) return (string)$list[0];
        if(is_scalar($list)) return (string)$list;
    }
    return '';
}

function rsvpmaker_postmark_normalize_message_row($message, $stream_label, $stream_id) {
    $str = function($v) { return is_scalar($v) ? (string) $v : ''; };
    $recipient = rsvpmaker_postmark_extract_recipient($message);
    $row = array(
        'Subject'    => $str(rsvpmaker_postmark_message_value($message,'Subject','')),
        'To'         => $recipient,
        'Recipient'  => $recipient,
        'Status'     => $str(rsvpmaker_postmark_message_value($message,'Status','')),
        'MessageID'  => $str(rsvpmaker_postmark_message_value($message,'MessageID','')),
        'Opens'      => (int) rsvpmaker_postmark_message_value($message,'Opens',0),
        'Delivered'  => $str(rsvpmaker_postmark_message_value($message,'Delivered','')),
        'DeliveredAt'=> $str(rsvpmaker_postmark_message_value($message,'DeliveredAt','')),
        'ReceivedAt' => $str(rsvpmaker_postmark_message_value($message,'ReceivedAt','')),
        'SubmittedAt'=> $str(rsvpmaker_postmark_message_value($message,'SubmittedAt','')),
        'MessageDate'=> $str(rsvpmaker_postmark_message_value($message,'MessageDate','')),
        'Date'       => $str(rsvpmaker_postmark_message_value($message,'Date','')),
        'Clicks'     => (int) rsvpmaker_postmark_message_value($message,'Clicks',0),
        'stream_label' => $stream_label,
        'stream_id'    => $stream_id,
    );
    $row['_ts'] = rsvpmaker_postmark_message_timestamp($row);
    return $row;
}

function rsvpmaker_postmark_enrich_message_row($client, $row, &$details_cache, $force = array()) {
    $msg_id = !empty($row['MessageID']) ? (string) $row['MessageID'] : '';
    if(empty($msg_id)) {
        if(!empty($force['delivered']) && empty($row['Delivered']))
            $row['Delivered'] = 'true';
        return $row;
    }

    $need_details = empty($row['Subject'])
        || empty($row['Status'])
        || empty($row['Delivered'])
        || empty($row['DeliveredAt'])
        || !isset($row['Opens'])
        || !isset($row['Clicks'])
        || !is_numeric($row['Opens'])
        || !is_numeric($row['Clicks'])
        || (intval($row['Opens']) === 0 && intval($row['Clicks']) === 0 && empty($row['DeliveredAt']) && empty($row['Delivered']));

    if($need_details && !array_key_exists($msg_id, $details_cache)) {
        $details_cache[$msg_id] = array();
        try {
            $details = $client->getOutboundMessageDetails($msg_id);
            $details_cache[$msg_id] = rsvpmaker_postmark_debug_to_array($details);
        }
        catch(PostmarkException $e) {
            $details_cache[$msg_id] = array();
        }
    }

    if(!empty($details_cache[$msg_id])) {
        $details = $details_cache[$msg_id];
        if(empty($row['Subject']))
            $row['Subject'] = (string) rsvpmaker_postmark_message_value($details,'Subject','');
        if(empty($row['Status']))
            $row['Status'] = (string) rsvpmaker_postmark_message_value($details,'Status','');
        if(empty($row['Delivered']))
            $row['Delivered'] = (string) rsvpmaker_postmark_message_value($details,'Delivered','');
        if(empty($row['DeliveredAt']))
            $row['DeliveredAt'] = (string) rsvpmaker_postmark_message_value($details,'DeliveredAt','');
        if(!isset($row['Opens']) || intval($row['Opens']) === 0)
            $row['Opens'] = (int) rsvpmaker_postmark_message_value($details,'Opens',0);
        if(!isset($row['Clicks']) || intval($row['Clicks']) === 0)
            $row['Clicks'] = (int) rsvpmaker_postmark_message_value($details,'Clicks',0);
        if(empty($row['_ts'])) {
            $row['ReceivedAt'] = empty($row['ReceivedAt']) ? (string) rsvpmaker_postmark_message_value($details,'ReceivedAt','') : $row['ReceivedAt'];
            $row['SubmittedAt'] = empty($row['SubmittedAt']) ? (string) rsvpmaker_postmark_message_value($details,'SubmittedAt','') : $row['SubmittedAt'];
            $row['MessageDate'] = empty($row['MessageDate']) ? (string) rsvpmaker_postmark_message_value($details,'MessageDate','') : $row['MessageDate'];
            $row['Date'] = empty($row['Date']) ? (string) rsvpmaker_postmark_message_value($details,'Date','') : $row['Date'];
        }
    }

    if(!empty($force['delivered']) && empty($row['Delivered']) && empty($row['DeliveredAt']) && (empty($row['Status']) || false === stripos($row['Status'],'deliver')))
        $row['Delivered'] = 'true';

    $row['_ts'] = rsvpmaker_postmark_message_timestamp($row);
    return $row;
}

function rsvpmaker_postmark_search_stream_messages($client, $stream_id, $stream_label, $recipient = '', $subject = '', $status_filter = 'all', $debug = false) {
    // Only pass delivery-status filters to this endpoint; engagement (opened/clicked) uses a separate function
    $api_status = ('delivered' === $status_filter) ? 'delivered' : NULL;
    $results = array();
    $details_cache = array();
    $response = $client->getOutboundMessages(100, 0, $recipient ?: NULL, NULL, NULL, $subject ?: NULL, $api_status, NULL, NULL, NULL, $stream_id);
    if($debug) {
        $params = array('count'=>100,'recipient'=>$recipient?:null,'subject'=>$subject?:null,'status'=>$api_status,'messagestream'=>$stream_id);
        $raw = rsvpmaker_postmark_debug_to_array($response);
        $messages_debug = rsvpmaker_postmark_debug_collection($response,'Messages');
        $msg_count = count($messages_debug);
        $sample = $msg_count ? array_slice(array_map('rsvpmaker_postmark_debug_to_array',$messages_debug), 0, 3) : array();
        echo '<details open><summary><strong>Debug: getOutboundMessages &mdash; '.$stream_label.' ('.$stream_id.')</strong></summary>';
        echo '<p><strong>Params:</strong> <code>'.esc_html(json_encode($params)).'</code></p>';
        echo '<p><strong>TotalCount:</strong> '.esc_html((string) rsvpmaker_postmark_message_value($raw,'TotalCount','?')).' | <strong>Messages in response:</strong> '.$msg_count.'</p>';
        if($sample) echo '<pre style="overflow:auto;max-height:300px;background:#f6f7f7;padding:10px">'.esc_html(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre>';
        echo '</details>';
    }
    $messages = rsvpmaker_postmark_message_value($response,'Messages',array());
    if(empty($messages) || !(is_array($messages) || $messages instanceof Traversable))
        return $results;
    foreach($messages as $message) {
        $row = rsvpmaker_postmark_normalize_message_row($message,$stream_label,$stream_id);
        $results[] = rsvpmaker_postmark_enrich_message_row($client, $row, $details_cache, array('delivered' => ('delivered' === $status_filter)));
    }
    return $results;
}

function rsvpmaker_postmark_search_stream_engagement($client, $stream_id, $stream_label, $recipient = '', $subject = '', $type = 'opens', $debug = false) {
    $results = array();
    if('opens' === $type) {
        $response = $client->getOpenStatistics(100, 0, $recipient ?: NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $stream_id);
        $items = $response->Opens;
        $status_label = 'Opened';
    } else {
        $response = $client->getClickStatistics(100, 0, $recipient ?: NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $stream_id);
        $items = $response->Clicks;
        $status_label = 'Clicked';
    }
    if($debug) {
        $endpoint = ('opens' === $type) ? 'getOpenStatistics' : 'getClickStatistics';
        $response_key = ('opens' === $type) ? 'Opens' : 'Clicks';
        $params = array('count'=>100,'recipient'=>$recipient?:null,'messagestream'=>$stream_id);
        $raw = rsvpmaker_postmark_debug_to_array($response);
        $items_debug = rsvpmaker_postmark_debug_collection($response,$response_key);
        $item_count = count($items_debug);
        $sample = $item_count ? array_slice(array_map('rsvpmaker_postmark_debug_to_array',$items_debug), 0, 3) : array();
        echo '<details open><summary><strong>Debug: '.$endpoint.' &mdash; '.$stream_label.' ('.$stream_id.')</strong></summary>';
        echo '<p><strong>Params:</strong> <code>'.esc_html(json_encode($params)).'</code></p>';
        echo '<p><strong>TotalCount:</strong> '.esc_html((string) rsvpmaker_postmark_message_value($raw,'TotalCount','?')).' | <strong>Items in response:</strong> '.$item_count.'</p>';
        if($sample) echo '<pre style="overflow:auto;max-height:300px;background:#f6f7f7;padding:10px">'.esc_html(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre>';
        echo '</details>';
    }
    if(empty($items) || !(is_array($items) || $items instanceof Traversable))
        return $results;
    $str = function($v) { return is_scalar($v) ? (string)$v : ''; };
    $details_cache = array();
    $seen = array();
    foreach($items as $item) {
        $msg_id = $str(rsvpmaker_postmark_message_value($item,'MessageID',''));
        if(!empty($msg_id)) {
            if(isset($seen[$msg_id])) continue;
            $seen[$msg_id] = true;
        }
        $subj = $str(rsvpmaker_postmark_message_value($item,'Subject',''));
        $recip = $str(rsvpmaker_postmark_message_value($item,'Recipient',''));
        $received = $str(rsvpmaker_postmark_message_value($item,'ReceivedAt',''));
        $row = array(
            'Subject'     => $subj,
            'To'          => $recip,
            'Recipient'   => $recip,
            'Status'      => $status_label,
            'MessageID'   => $msg_id,
            'Opens'       => ('opens' === $type) ? 1 : 0,
            'Clicks'      => ('opens' !== $type) ? 1 : 0,
            'Delivered'   => '',
            'DeliveredAt' => '',
            'ReceivedAt'  => $received,
            'SubmittedAt' => '',
            'MessageDate' => '',
            'Date'        => '',
            'stream_label' => $stream_label,
            'stream_id'    => $stream_id,
        );
        $row = rsvpmaker_postmark_enrich_message_row($client, $row, $details_cache);
        if(!empty($subject) && false === stripos((string) $row['Subject'], $subject))
            continue;
        $results[] = $row;
    }
    return $results;
}

function rsvpmaker_postmark_status_flags($message) {
    $status = strtolower(isset($message['Status']) ? $message['Status'] : '');
    $opens   = isset($message['Opens'])  ? intval($message['Opens'])  : 0;
    $clicks  = isset($message['Clicks']) ? intval($message['Clicks']) : 0;
    $opened  = ($opens  > 0) || (strpos($status,'open') !== false);
    $clicked = ($clicks > 0) || (strpos($status,'link') !== false) || (strpos($status,'click') !== false);
    $processed = (!empty($message['MessageID']) || !empty($status));
    $delivered = (strpos($status,'deliver') !== false) || !empty($message['DeliveredAt']) || (!empty($message['Delivered']) && 'false' !== strtolower((string) $message['Delivered']));
    return array(
        'opened'    => $opened,
        'clicked'   => $clicked,
        'processed' => $processed,
        'delivered' => $delivered,
    );
}

function rsvpmaker_postmark_matches_status_filter($message, $status_filter = 'all') {
    if(empty($status_filter) || 'all' === $status_filter)
        return true;
    $flags = rsvpmaker_postmark_status_flags($message);
    if('opened'    === $status_filter) return !empty($flags['opened']);
    if('clicked'   === $status_filter) return !empty($flags['clicked']);
    if('delivered' === $status_filter) return !empty($flags['delivered']);
    return true;
}

function rsvpmaker_postmark_status_badges($message) {
    $flags = rsvpmaker_postmark_status_flags($message);
    $output = '';
    $output .= sprintf('<span class="postmark-status-badge %s">Opened</span>',$flags['opened']    ? 'on' : 'off');
    $output .= sprintf('<span class="postmark-status-badge %s">Clicked</span>',$flags['clicked']   ? 'on' : 'off');
    $output .= sprintf('<span class="postmark-status-badge %s">Delivered</span>',$flags['delivered'] ? 'on' : 'off');
    $output .= sprintf('<span class="postmark-status-badge %s">Processed</span>',$flags['processed'] ? 'on' : 'off');
    return $output;
}

function rsvpmaker_postmark_merge_status_rows($base_rows, $status_rows) {
    if(empty($base_rows) || empty($status_rows))
        return $base_rows;

    $base_index = array();
    foreach($base_rows as $index => $row) {
        if(!empty($row['MessageID']))
            $base_index[(string) $row['MessageID']] = $index;
    }

    foreach($status_rows as $status_row) {
        if(empty($status_row['MessageID']))
            continue;
        $msg_id = (string) $status_row['MessageID'];
        if(!isset($base_index[$msg_id]))
            continue;
        $target_index = $base_index[$msg_id];
        if(!empty($status_row['Opens']))
            $base_rows[$target_index]['Opens'] = max(intval($base_rows[$target_index]['Opens']), intval($status_row['Opens']));
        if(!empty($status_row['Clicks']))
            $base_rows[$target_index]['Clicks'] = max(intval($base_rows[$target_index]['Clicks']), intval($status_row['Clicks']));
        if(!empty($status_row['Delivered']))
            $base_rows[$target_index]['Delivered'] = $status_row['Delivered'];
        if(!empty($status_row['DeliveredAt']))
            $base_rows[$target_index]['DeliveredAt'] = $status_row['DeliveredAt'];
        if(empty($base_rows[$target_index]['Subject']) && !empty($status_row['Subject']))
            $base_rows[$target_index]['Subject'] = $status_row['Subject'];
        if(empty($base_rows[$target_index]['To']) && !empty($status_row['To']))
            $base_rows[$target_index]['To'] = $status_row['To'];
        if(empty($base_rows[$target_index]['Recipient']) && !empty($status_row['Recipient']))
            $base_rows[$target_index]['Recipient'] = $status_row['Recipient'];
        if(empty($base_rows[$target_index]['Status']) && !empty($status_row['Status']))
            $base_rows[$target_index]['Status'] = $status_row['Status'];
        $base_rows[$target_index]['_ts'] = rsvpmaker_postmark_message_timestamp($base_rows[$target_index]);
    }

    return $base_rows;
}

function rsvpmaker_postmark_show_sent_log() {
    rsvpmaker_admin_heading('Postmark Email Log',__FUNCTION__);
    global $wpdb,$rsvp_options;
    $time_format = (strpos($rsvp_options['time_format'],'T')) ? $rsvp_options['time_format'] : $rsvp_options['time_format'].' T';
    $table = $wpdb->base_prefix.'postmark_tally';
    $blog_id = isset($_GET['blog_id']) ? intval($_GET['blog_id']) : get_current_blog_id();
    $days = isset($_GET['days']) ? intval($_GET['days']) : 31;
    
    if(isset($_GET['monthly'])) {
        $sql = $wpdb->prepare("SELECT blog_id, sum(count) total, DATE_FORMAT(time,'%Y-%m') as ym FROM %i ".(($blog_id > 1) ? ' WHERE blog_id='.intval($blog_id).' ' : '') ." group by blog_id, ym order by ".((isset($_GET['by_volume'])) ? 'total DESC' : 'blog_id, ym DESC'),$table);
        $results = $wpdb->get_results($sql);
        if($results) {
            echo '<h2>Monthly Volume</h2>';
            if(!isset($_GET['by_volume'])) {
                $vlink = admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&monthly=1&by_volume=1');
                if(isset($_GET['blog_id']))
                    $vlink .= '&blog_id='.intval($_GET['blog_id']);
                printf('<p>Sort <a href="%s">by volume</a></p>',$vlink);
            }

            echo '<table class="wp-list-table widefat striped"><tr><th>Site</th><th>URL</th><th>Month</th><th>Total</th></tr>';
            foreach($results as $row) {
                $name = (is_multisite()) ? get_blog_option($row->blog_id,'blogname') : get_option('blogname');
                $home = (is_multisite()) ? get_blog_option($row->blog_id,'home') : get_option('home');
                printf('<tr><td>%s</td><td><a href="%s">%s</a></td><td>%s</td><td>%s</td></tr>',$name,$home,$home,$row->ym,$row->total);
            }
            echo '</table>';    
        }
    }
    echo '<p>Postmark is the service we use for reliable email delivery. Here is a record of emails submitted to the Postmark service within the last month.</p>';

    $search_recipient = isset($_GET['pm_recipient']) ? strtolower(sanitize_text_field(wp_unslash($_GET['pm_recipient']))) : '';
    $search_subject = isset($_GET['pm_subject']) ? strtolower(sanitize_text_field(wp_unslash($_GET['pm_subject']))) : '';
    $search_status = isset($_GET['pm_status']) ? sanitize_key(wp_unslash($_GET['pm_status'])) : 'all';
    if(!in_array($search_status,array('all','opened','clicked','delivered'),true))
        $search_status = 'all';
    $pm_debug = !empty($_GET['pm_debug']);
    $run_search = (!empty($search_recipient) || !empty($search_subject) || ('all' !== $search_status) || $pm_debug);

    echo '<style>
    .postmark-search-grid { display:grid; grid-template-columns: minmax(200px,1fr) minmax(200px,1fr) minmax(160px,220px) auto; gap: 12px; align-items:end; margin: 12px 0 18px 0; }
    .postmark-search-grid label { display:block; font-weight:600; margin-bottom:4px; }
    .postmark-search-grid input[type="text"], .postmark-search-grid input[type="email"], .postmark-search-grid select { width:100%; }
    .postmark-status-badge { display:inline-block; margin:0 6px 6px 0; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; border:1px solid #ccd0d4; }
    .postmark-status-badge.on { background:#e8f7ec; color:#0f5132; border-color:#7fd19a; }
    .postmark-status-badge.off { background:#f8f9fa; color:#6c757d; border-color:#d0d7de; }
    @media (max-width: 782px) { .postmark-search-grid { grid-template-columns: 1fr; } }
    </style>';

    printf('<form method="get" action="%s">',admin_url('edit.php'));
    echo '<input type="hidden" name="post_type" value="rsvpemail">';
    echo '<input type="hidden" name="page" value="rsvpmaker_postmark_show_sent_log">';
    if(isset($_GET['blog_id']))
        printf('<input type="hidden" name="blog_id" value="%d">',intval($_GET['blog_id']));
    if(isset($_GET['days']))
        printf('<input type="hidden" name="days" value="%d">',intval($_GET['days']));
    echo '<h3>Search Postmark Streams</h3>';
    echo '<p>Search sent messages across broadcast and transactional streams by recipient email and/or subject keywords.</p>';
    echo '<div class="postmark-search-grid">';
    printf('<div><label for="pm_recipient">Recipient</label><input type="text" id="pm_recipient" name="pm_recipient" value="%s" placeholder="name@example.com"></div>',esc_attr($search_recipient));
    printf('<div><label for="pm_subject">Subject Keywords</label><input type="text" id="pm_subject" name="pm_subject" value="%s" placeholder="meeting reminder"></div>',esc_attr($search_subject));
    printf('<div><label for="pm_status">Status</label><select id="pm_status" name="pm_status"><option value="all" %s>All</option><option value="opened" %s>Opened</option><option value="clicked" %s>Clicked</option><option value="delivered" %s>Delivered</option></select></div>',selected($search_status,'all',false),selected($search_status,'opened',false),selected($search_status,'clicked',false),selected($search_status,'delivered',false));
    echo '<div><button class="button button-primary">Search</button></div>';
    echo '</div>';
    printf('<p><label><input type="checkbox" name="pm_debug" value="1" %s> Show debug info (raw API query &amp; response)</label></p>',checked($pm_debug,true,false));
    echo '</form>';

    if($run_search) {
        if(rsvpmaker_postmark_is_active()) {
            $postmark_settings = get_rsvpmaker_postmark_options();
            $postmark_settings_key = ('production' == $postmark_settings['postmark_mode']) ? $postmark_settings['postmark_production_key'] : $postmark_settings['postmark_sandbox_key'];
            $client = new PostmarkClient($postmark_settings_key);
            $stream_map = array(
                'Broadcast' => empty($postmark_settings['postmark_broadcast_slug']) ? 'broadcast' : $postmark_settings['postmark_broadcast_slug'],
                'Transactional' => empty($postmark_settings['postmark_tx_slug']) ? 'outbound' : $postmark_settings['postmark_tx_slug'],
            );
            $search_results = array();
            $all_results_fallback = array();
            try {
                foreach($stream_map as $label => $stream_id) {
                    if('opened' === $search_status) {
                        $stream_results = rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'opens',$pm_debug);
                        $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'clicks',$pm_debug));
                        $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_messages($client,$stream_id,$label,$search_recipient,$search_subject,'delivered',$pm_debug));
                    } elseif('clicked' === $search_status) {
                        $stream_results = rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'clicks',$pm_debug);
                    } elseif('all' === $search_status) {
                        $stream_results = rsvpmaker_postmark_search_stream_messages($client,$stream_id,$label,$search_recipient,$search_subject,'all',$pm_debug);
                        if(!empty($stream_results))
                            $all_results_fallback = array_merge($all_results_fallback,$stream_results);
                        $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'opens',$pm_debug));
                        $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'clicks',$pm_debug));
                        $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_messages($client,$stream_id,$label,$search_recipient,$search_subject,'delivered',$pm_debug));
                    } else {
                        $stream_results = rsvpmaker_postmark_search_stream_messages($client,$stream_id,$label,$search_recipient,$search_subject,$search_status,$pm_debug);
                        if('delivered' === $search_status) {
                            $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'opens',$pm_debug));
                            $stream_results = rsvpmaker_postmark_merge_status_rows($stream_results, rsvpmaker_postmark_search_stream_engagement($client,$stream_id,$label,$search_recipient,$search_subject,'clicks',$pm_debug));
                        }
                    }
                    if(!empty($stream_results))
                        $search_results = array_merge($search_results,$stream_results);
                }
            }
            catch(PostmarkException $e) {
                printf('<p><strong>Search error:</strong> %s</p>',esc_html($e->getMessage()));
                $search_results = array();
            }

            if('all' !== $search_status) {
                $search_results = array_values(array_filter($search_results,function($row) use ($search_status) {
                    return rsvpmaker_postmark_matches_status_filter($row,$search_status);
                }));
            }
            elseif(empty($search_results) && !empty($all_results_fallback)) {
                $search_results = $all_results_fallback;
            }

            if(!empty($search_results)) {
                usort($search_results,function($a,$b){
                    return (($b['_ts'] ?? 0) <=> ($a['_ts'] ?? 0));
                });
            }

            if(!empty($search_results)) {
                printf('<p><strong>%d</strong> matching messages found across both streams.</p>',count($search_results));
                echo '<table class="wp-list-table widefat striped"><thead><tr><th>Subject</th><th>Date / Time</th><th>Recipient</th><th>Stream</th><th>Status</th></tr></thead><tbody>';
                foreach($search_results as $row) {
                    $subject = !empty($row['Subject']) ? $row['Subject'] : '(no subject)';
                    $recipient = !empty($row['To']) ? $row['To'] : (isset($row['Recipient']) ? $row['Recipient'] : '');
                    $status = !empty($row['Status']) ? $row['Status'] : 'Unknown';
                    $ts = !empty($row['_ts']) ? intval($row['_ts']) : 0;
                    $when = $ts ? rsvpmaker_date($rsvp_options['long_date'].' '.$time_format,$ts) : 'n/a';
                    printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s<br><small>%s</small></td><td>%s<br><small>%s</small></td></tr>',
                        esc_html($subject),
                        esc_html($when),
                        esc_html($recipient),
                        esc_html($row['stream_label']),
                        esc_html($row['stream_id']),
                        rsvpmaker_postmark_status_badges($row),
                        esc_html($status)
                    );
                }
                echo '</tbody></table>';
            }
            else {
                echo '<p>No matching sent messages found in broadcast or transactional streams.</p>';
            }
        }
        else {
            echo '<p>Postmark search is available when Postmark integration is active.</p>';
        }
    }

    printf('<p>See summary <a href="%s">by month</a> | <a href="%s">by volume</a> | Or <a href="%s">show opens/clicks</a> (opens / clicks)</p>',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&monthly=1'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&monthly=1&by_volume=1'),admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&clicks=1'));
    $grandtotal = 0;

    echo '<div id="postmark_sent_log_details" style="display:'.(isset($_GET['details']) || isset($_GET['clicks']) ? 'block' : 'none').'">';
    
    $results = $wpdb->get_results($wpdb->prepare("SELECT sum(count) total, blog_id FROM %i WHERE time > DATE_SUB(NOW(), INTERVAL %d DAY) ".($blog_id > 1) ? ' AND blog_id='.$blog_id : ''." group by blog_id",$table,$days));
    foreach($results as $row) {
        $name = (is_multisite()) ? get_blog_option($row->blog_id,'blogname') : get_option('blogname');
        $text = sprintf('<strong>%s</strong>: %d (<a href="%s">monthly</a>)',$name,$row->total,admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&monthly=1&blog_id='.$row->blog_id));
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
        if($client) { try {
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
                    $title = ('misc' == $tag) ? 'miscellaneous' : rsvpmaker_postmark_tag_to_title($tag);
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
                        $title = ('misc' == $tag) ? 'miscellaneous' : rsvpmaker_postmark_tag_to_title($tag);
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

        } catch(PostmarkException $e) {
            printf('<p><strong>Postmark stats error:</strong> %s</p>',esc_html($e->getMessage()));
        } }
    }

    echo '</div>';

    $days = (isset($_GET['days'])) ? intval($_GET['days']) : 31;
    printf('<form method="get" action="%s">Showing outgoing message data for <input type="hidden" name="post_type" value="rsvpemail" ><input type="hidden" name="page" value="rsvpmaker_postmark_show_sent_log" ><input name="days" value="%s"> days <button>Change</button></form>',admin_url('edit.php'),$days);

    if($blog_id > 1) {
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE time > DATE_SUB(NOW(), INTERVAL %d DAY) AND blog_id=%d ORDER BY id DESC",$table, $days, $blog_id));
        $showmulti = false;
    }
    else {
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE time > DATE_SUB(NOW(), INTERVAL 31 DAY) ORDER BY id DESC",$table));
        $showmulti = is_multisite();
    }

    echo '<table class="wp-list-table widefat striped"><thead><tr><th>Subject</th><th># Recipients</th><th>Blog ID</th><th>Recipients</th><th>Details</th></tr></thead><tbody>';
    foreach($results as $row) {
        if(isset($_GET['showall']) && $row->id == intval($_GET['showall']))
            $recipients = str_replace(',',', ',$row->recipients);
        else
            $recipients = (strlen($row->recipients) > 200) ? substr($row->recipients,0,100).'... (<a href="'.admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&showall='.$row->id).'#row'.$row->id.'">Show All</a>)' : $row->recipients;
        $prompt = empty($row->tag) ? '' : sprintf('<a href="%s">Opens/Clicks</a><br>%s',admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_postmark_show_sent_log&details=1&tag='.$row->tag),$row->tag);
        printf('<tr id="row%d"><td>%s<br>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',$row->id,$row->subject,rsvpmaker_date($rsvp_options['long_date'].' '.$time_format,strtotime($row->time)).' '.$row->time,$row->count,$row->blog_id,$recipients,$prompt);
    }
    echo '</tbody></table>';

    if(1 == $blog_id) {
        if(!wp_get_schedule('rsvpmaker_postmark_suppressions')) {
            wp_schedule_event( rsvpmaker_strtotime('23:00:00'), 'daily', 'rsvpmaker_postmark_suppressions' );
        }
        $ignore = get_option('ignore_postmark_supressions');	
        $suppressions = $client->getSuppressions('broadcast');
        if(isset($suppressions['suppressions'])) {
            echo '<p>Suppressions (bad or blocked): ';
            foreach($suppressions['suppressions'] as $s) {
                $email = strtolower($s['EmailAddress']);
                if(is_array($ignore) && in_array($email,$ignore)) {
                    $suppressionChanges = array(new SuppressionChangeRequest($email));
                    $messageStream = "broadcast";
                    $result = $client->deleteSuppressions($suppressionChanges, $messageStream);                        
                    $messageStream = "outbound";
                    $result = $client->deleteSuppressions($suppressionChanges, $messageStream);
                    continue;
                }
                else {
                    echo $email.' '.$s['CreatedAt'].' ';
                    rsvpmail_add_problem($email,$s['SuppressionReason']);    
                }
            } 
            echo '<p>';
        }            
    }

    $sql = $wpdb->prepare("SELECT meta_value from %i WHERE meta_key='rsvpmail_postmark_error' ORDER BY meta_id DESC LIMIT 100",$wpdb->postmeta);

    $results = $wpdb->get_results($sql);
    if($results) {
        echo '<h2>Recent Postmark Errors</h2><ul>';
        foreach($results as $row) {
            preg_match('/inactive addresses: ([^\s]+). Inactive/',$row->meta_value,$matches);
            if(!empty($matches[1])) {
            // Do not auto-block from historical error-log text. Active suppressions are synced above.
            echo '<li>inactive (log only): '.$matches[1]. ' '.var_export(rsvpmail_is_problem($matches[1]),true). '</li>';
            }
            else
            echo '<li>'.var_export($matches,true).' '.$row->meta_value.'</li>';
        }
        echo '</ul>';
    }

}
add_action('rsvpmaker_postmark_suppressions','rsvpmaker_postmark_suppressions');
function rsvpmaker_postmark_suppressions() {
    if(rsvpmaker_postmark_is_live()) {
    if(is_multisite())
        switch_to_blog(1);

    $postmark_settings = get_rsvpmaker_postmark_options();
    $client = new PostmarkClient($postmark_settings['postmark_production_key']);
    $suppressions = $client->getSuppressions('broadcast');
    $ignore = get_option('ignore_postmark_supressions');	
    $suppressions = $client->getSuppressions('broadcast');
    if(isset($suppressions['suppressions'])) {
        echo '<p>Suppressions (bad or blocked): ';
        foreach($suppressions['suppressions'] as $s) {
            $email = strtolower($s['EmailAddress']);
            if(is_array($ignore) && in_array($email,$ignore)) {
                $suppressionChanges = array(new SuppressionChangeRequest($email));
                $messageStream = "broadcast";
                $result = $client->deleteSuppressions($suppressionChanges, $messageStream);                            
                $messageStream = "outbound";
                $result = $client->deleteSuppressions($suppressionChanges, $messageStream);
                continue;
            } else {
                rsvpmail_add_problem($email,$s['SuppressionReason']);
            }
        } 
    }
}
}

function rsvpmaker_postmark_tag_to_title($tag) {
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
$sql = $wpdb->prepare("SELECT post_title, post_type FROM %i WHERE ID=%d",$wpdb->posts,$post_id);
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

function rsvpmaker_check_postmark_tally_version() {
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

function rsvpmaker_postmark_admin_email() {
    return (is_multisite()) ? get_blog_option(1,'admin_email') : get_option('admin_email');
}

function rsvpmaker_postmark_forwarder_tester() {
    $recipients = $combined = $original_to = $original_cc = [];
    $data = rsvpmaker_postmark_incoming_test();
    $original = '';
    $output = '';
    if(!empty($data->To))
        $original .= 'To: '.htmlentities($data->To);
    if(!empty($data->Cc))
        $original .= ' CC: '.htmlentities($data->Cc);
    foreach($data->ToFull as $index => $obj) {
        $combined[] = strtolower($obj->Email);
    }
    foreach($data->CcFull as $index => $obj) {
        $combined[] = strtolower($obj->Email);
    }

    $localdomains = ['toastmost.org','libertylakers.org'];
    $customdomains = ['libertylakers.org' => 33];
    foreach($combined as $to) {
        $domain_lookup = '';
        $femail = '';
        $parts = explode('@',$to);
        if(in_array($parts[1],$localdomains)) {
            if($parts[1] = $localdomains[0]) {
                if(strpos($parts[0],'-'))
                {
                    $leftparts = explode('-',$parts[0]);
                    $domain_lookup = str_replace('.','',$leftparts[0]);
                    $femail = $leftparts[1];
                }
                else {
                    $domain_lookup = 'members';
                    $femail = $parts[0];    
                }
            }
            else {
                $domain_lookup = $parts[1];
                $femail = $parts[0]; 
            }
            if($domain_lookup) {
                //$site_id = is_multisite() ? rsvpmail_site_id($domain_lookup) : 1;
                $site_id = rsvpmail_site_id($domain_lookup);
                if($site_id) {
                    $list = rsvpmaker_postmark_resolve_email($femail, $site_id);
                    if(empty($list))
                        $output .= '<p>No match for '.$femail.'/'.$site_id.'</p>';
                    else
                        $recipients = array_merge($recipients,$list);
                }
            }

        }
        $output .= sprintf('<p>%s - %s %s</p>',$to,$domain_lookup,$femail);
    }

    return $output.'<p>'.$original . ' combined: '.implode(', ',$combined).' <br>recipients '.implode(', ',$recipients).'</p>';

    return $output.'<pre>'.var_export($data,true).'</pre>';
}



function rsvpmaker_postmark_resolve_email($femail, $site_id) {
    $recipients = [];
    $list[22]['members'] = ['member1@example.com','member2@example.com','member3@example.com'];
    $list[22]['officers'] = ['officer1@example.com','officer2@example.com','demo-vpm@toastmost.org'];
    $list[300]['members'] = ['member31@example.com','member32@example.com','member33@example.com'];
    if(!empty($list[$site_id][$femail]))
        $recipients = $list[$site_id][$femail];
    return $recipients;
}

function rsvpmaker_postmark_incoming_test() {
$json = '{
    "FromName": "David F. Carr",
    "MessageStream": "inbound",
    "From": "david@carrcommunications.com",
    "FromFull": {
      "Email": "david@carrcommunications.com",
      "Name": "David F. Carr",
      "MailboxHash": ""
    },
    "To": "demo-officers@toastmost.org, demo-president@toastmost.org, \"David F. Carr\" <davidfcarr@gmail.com>",
    "ToFull": [
      {
        "Email": "demo-officers@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "demo-president@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "members@libertylakers.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "officers@libertylakers.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "libertylakers.org-officers@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "davidfcarr@gmail.com",
        "Name": "David F. Carr",
        "MailboxHash": ""
      }
    ],
    "Cc": "demo-vpe@toastmost.org, demo-vpm@toastmost.org, demo-mentors@toastmost.org, demo-crazy@toastmost.org",
    "CcFull": [
      {
        "Email": "demo-vpe@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "demo-vpm@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "demo-mentors@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      },
      {
        "Email": "demo-crazy@toastmost.org",
        "Name": "",
        "MailboxHash": ""
      }
    ],
    "Bcc": "e57457449614918835dd9c9189b67f3b@inbound.postmarkapp.com",
    "BccFull": [
      {
        "Email": "e57457449614918835dd9c9189b67f3b@inbound.postmarkapp.com",
        "Name": "",
        "MailboxHash": ""
      }
    ],
    "OriginalRecipient": "e57457449614918835dd9c9189b67f3b@inbound.postmarkapp.com",
    "Subject": "Forwarder and list test",
    "MessageID": "6a9bd15d-dcf2-4b89-b234-863ad1426f50",
    "ReplyTo": "",
    "MailboxHash": "",
    "Date": "Sat, 27 May 2023 09:15:47 -0400",
    "TextBody": "Test\n",
    "HtmlBody": "<div dir=\"ltr\">Test<\/div>\n",
    "StrippedTextReply": "",
    "Tag": "",
    "Headers": [
      {
        "Name": "Return-Path",
        "Value": "<SRS0=92bb=bq=carrcommunications.com=david@toastmost.org>"
      },
      {
        "Name": "Received",
        "Value": "by p-pm-inboundg02c-aws-useast1c.inbound.postmarkapp.com (Postfix, from userid 996)\tid 66A0B453CA3; Sat, 27 May 2023 13:16:04 +0000 (UTC)"
      },
      {
        "Name": "X-Spam-Checker-Version",
        "Value": "SpamAssassin 3.4.0 (2014-02-07) on\tp-pm-inboundg02c-aws-useast1c"
      },
      {
        "Name": "X-Spam-Status",
        "Value": "No"
      },
      {
        "Name": "X-Spam-Score",
        "Value": "4.5"
      },
      {
        "Name": "X-Spam-Tests",
        "Value": "DKIM_SIGNED,DKIM_VALID,HTML_MESSAGE,PYZOR_CHECK,\tRCVD_IN_DNSWL_NONE,RCVD_IN_ZEN_BLOCKED_OPENDNS,SPF_HELO_NONE,SPF_PASS,\tSUSPICIOUS_RECIPS,T_SCC_BODY_TEXT_LINE"
      },
      {
        "Name": "Received",
        "Value": "from delivery26.mailspamprotection.com (delivery26.mailspamprotection.com [185.56.84.25])\t(using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256\/256 bits))\t(No client certificate requested)\tby p-pm-inboundg02c-aws-useast1c.inbound.postmarkapp.com (Postfix) with ESMTPS id 069AB453CA2\tfor <e57457449614918835dd9c9189b67f3b@inbound.postmarkapp.com>; Sat, 27 May 2023 13:16:03 +0000 (UTC)"
      },
      {
        "Name": "Received",
        "Value": "from 218.69.208.35.bc.googleusercontent.com ([35.208.69.218] helo=c104924.sgvps.net)\tby se26.mailspamprotection.com with esmtps (TLSv1.2:AES128-GCM-SHA256:128)\t(Exim 4.92)\t(envelope-from <SRS0=92bb=bq=carrcommunications.com=david@toastmost.org>)\tid 1q2tm5-004kKX-Ak\tfor e57457449614918835dd9c9189b67f3b@inbound.postmarkapp.com; Sat, 27 May 2023 08:16:03 -0500"
      },
      {
        "Name": "DKIM-Signature",
        "Value": "v=1; a=rsa-sha256; q=dns\/txt; c=relaxed\/relaxed;\td=carrcommunications.com; s=default; h=Cc:To:Subject:Date:From:list-help:\tlist-unsubscribe:list-subscribe:list-post:list-owner:list-archive;\tbh=rnWn6hAFGzscwCpPPg\/xQ+158m1PejLdKZ07YBETSYI=; b=eOuZ4BsAWJH2DDV49+39ehL3V\/\tvBaCxv5Zu1tCGPDxopFtiQ6wa+lKP5UrEoW+AWhXLbpvqUvsw4gDmTZ02RBBqCHP1RD50w3Q\/WT1A\tbfNIwstckiyNJyIO\/A9\/fZ3pKOs\/yHqOIm3sRPPw\/im5E4tRCPpeO4tSevobBwwnOGfA=;"
      },
      {
        "Name": "Received",
        "Value": "from [35.208.244.18] (port=55914 helo=se15.mailspamprotection.com)\tby c104924.sgvps.net with esmtps  (TLS1.2) tls TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384\t(Exim 4.96)\t(envelope-from <david@carrcommunications.com>)\tid 1q2tm5-000Gr5-04\tfor demo-vpe@toastmost.org;\tSat, 27 May 2023 13:16:01 +0000"
      },
      {
        "Name": "Received",
        "Value": "from mail-yw1-f170.google.com ([209.85.128.170])\tby se15.mailspamprotection.com with esmtps (TLSv1.3:TLS_AES_256_GCM_SHA384:256)\t(Exim 4.92)\t(envelope-from <david@carrcommunications.com>)\tid 1q2tm3-005pKH-6s\tfor demo-vpe@toastmost.org; Sat, 27 May 2023 08:16:00 -0500"
      },
      {
        "Name": "Received",
        "Value": "by mail-yw1-f170.google.com with SMTP id 00721157ae682-565cfe4ece7so6943087b3.2        for <demo-vpe@toastmost.org>; Sat, 27 May 2023 06:15:58 -0700 (PDT)"
      },
      {
        "Name": "DKIM-Signature",
        "Value": "v=1; a=rsa-sha256; c=relaxed\/relaxed;        d=carrcommunications-com.20221208.gappssmtp.com; s=20221208; t=1685193358; x=1687785358;        h=cc:to:subject:message-id:date:from:mime-version:from:to:cc:subject         :date:message-id:reply-to;        bh=rnWn6hAFGzscwCpPPg\/xQ+158m1PejLdKZ07YBETSYI=;        b=E6wR1xfXHdpqHULeYIImy8A5lNSMNSVJeqMOZGGBCUQ7d7LOhxQvhZqxYPyC4PENNv         D0gU8zRv73iJ7BETobOgPAsyIkwuOTnXfBqd6W4XLBohs2Xh7CBLX5uzgJiIFpEDW2Be         \/xQ29T9oVnf24YL+Yd1w7+CZu4d4DWgVzNNJGRtuIMvNsMGXU+\/y9162LI7n3HEZlMM\/         NyzB1fIVAbJay7ENNSpRXJW+1ekSqIOJg02UP4matRkAECLEDgt1U6JdZSUHoFPwT+Ob         TuR14n5kaQEp\/\/G7rK\/edyNwYYooGqpyJyDQsDoGMJcsUWLoDTpIhPznakDJaOPYG0DP         O+XA=="
      },
      {
        "Name": "X-Google-DKIM-Signature",
        "Value": "v=1; a=rsa-sha256; c=relaxed\/relaxed;        d=1e100.net; s=20221208; t=1685193358; x=1687785358;        h=cc:to:subject:message-id:date:from:mime-version:x-gm-message-state         :from:to:cc:subject:date:message-id:reply-to;        bh=rnWn6hAFGzscwCpPPg\/xQ+158m1PejLdKZ07YBETSYI=;        b=EiN3CuKedZa9pCBzoGMxX0ew2wx90bvbQV+\/gytm9Hx8SI9z1VPx4EZkIE8J5X3lym         7kV+LYug2W+fLHh7K2twbyByosMjA6wgOcdHI7n+wjEDkMyQ8AqtPczYJhmR9evzetUo         +lIRe0a5likXu7jVqSIaxToNAL8O5K2vXMsETt7ecAY1RL8JCsqsNY2G+aF+8hBTQR2u         K+YYSxhmuPoDB9+CqNNk13EQrlId98orUk\/DYrRzxiJp9kUTRz2DujpFf1mChfS6pfT7         l218g1eJTs9oRAp0SmVAud4Uz\/Qf2pOFhQO\/mbolJuKV8p+x8vCCCmP5gsr1wuuX7Pe+         d\/Ew=="
      },
      {
        "Name": "X-Gm-Message-State",
        "Value": "AC+VfDzthYvHf5xUQcSbiUtb0aYCO5SsTrRUMkDnGYLHB8a2xUmZOkAi\t1qY8kwDVYFHb588WCqjUWDgHk31qFjqsRUAyvKh3bA=="
      },
      {
        "Name": "X-Google-Smtp-Source",
        "Value": "ACHHUZ4LCvDW2vvodMi0QXBl\/OIQE\/DZ6ntGS51ULmRhGgSJ+ZxPL1MMcC+BqpXItYxUmUmouc+pMbfk1ODNIk43iE4="
      },
      {
        "Name": "X-Received",
        "Value": "by 2002:a81:4fd5:0:b0:565:b4e9:74a7 with SMTP id d204-20020a814fd5000000b00565b4e974a7mr4917793ywb.47.1685193358280; Sat, 27 May 2023 06:15:58 -0700 (PDT)"
      },
      {
        "Name": "MIME-Version",
        "Value": "1.0"
      },
      {
        "Name": "Message-ID",
        "Value": "<CAJbdpGtwEEcXT0j_KwarXRmBbyOnvoZ7S49-PsJnCXj1e20JLQ@mail.gmail.com>"
      },
      {
        "Name": "Received-SPF",
        "Value": "softfail (se15.mailspamprotection.com: transitioning domain of carrcommunications.com does not designate 209.85.128.170 as permitted sender) client-ip=209.85.128.170; envelope-from=david@carrcommunications.com; helo=mail-yw1-f170.google.com;"
      },
      {
        "Name": "X-SPF-Result",
        "Value": "se15.mailspamprotection.com: transitioning domain of carrcommunications.com does not designate 209.85.128.170 as permitted sender"
      },
      {
        "Name": "Authentication-Results",
        "Value": "mailspamprotection.com; spf=softfail smtp.mailfrom=david@carrcommunications.com; dkim=pass header.i=carrcommunications-com.20221208.gappssmtp.com"
      },
      {
        "Name": "X-SpamExperts-Class",
        "Value": "ham"
      },
      {
        "Name": "X-SpamExperts-Evidence",
        "Value": "Combined (0.20)"
      },
      {
        "Name": "X-Recommended-Action",
        "Value": "accept"
      },
      {
        "Name": "X-Filter-ID",
        "Value": "Mvzo4OR0dZXEDF\/gcnlw0a1TH2PAsCdBCefHR9dLpBupSDasLI4SayDByyq9LIhVYPGAIdi8wKHq +CAAA8rH6kTNWdUk1Ol2OGx3IfrIJKywOmJyM1qr8uRnWBrbSAGDKm8oTPTOtXVsuiY3CQn636F6 9qtBqRJgshxH+yGeCXACL+VXs+wjOKavRDV9OsPmBMmyNbDn7R5kilAhwr3KtPvewi+NVw3s8AaB O3eL9xrfjNQgD4Wu4djl\/0ccnHnReA6scZZwpzkCo4WoBCri0uYUcfEloDWv8y1k883\/5L6WZvJ8 2v5qqDoKQEdlLW3+fNcjGq4w5IjuQj91OL62DoNxG38PukMPwTJbVShPzeKQ3fbbTo\/YRK+tB9pi rNkrfOd0Lki8UcIop6ZvPjacVKLMgEgpvRxWpdqomhYjh246G9pysxXCSB5rHvRaGN\/MRK7lwTrY egpy7\/8+6KMpZ7LGgaeKqwlxi0fxcLZvNmvlgTl6fJxyntEfhZCKje4Zt1xSC46hEx9PvRsXkCB2 dC7\/+fOTvqVqTiiM4NskccSe\/POvc5OCITYf544qt8hZy17P7cY\/rQjML4nS3F6wrP6BQUTeGC4w Nm1yxBPK+DO05aNHu9VCJ2sFLFRRTpVfKm8oTPTOtXVsuiY3CQn630DBSefh1lZL0lzlorhBKYvo nV+E7OMXRvgtdyMlnmWipHwY1Re3fm\/rN5b+XVFpEcFtR0giFHDBnkJCcJVHdAImx6vaPzx0Rxvl 3WkmepWb9APZ3SMlf7OqPszvbLHaZhk3ngOCCtU5l3QYjib1YiAtOHf2DruBH22jRxOqvTfdat7+ GwEHHNHRnEtwdn106Os2CQIyYooPwUZVbl9zufS6FzWfZGViQ\/A5Sq5EHoUKOh8Kt49Eciiho9aM 1tIJU5FzyxoJifLoWcPmYKuqbZH1H\/aAwarQpYDOYx\/6JtUOrqCyaAXLcDUjxja1GUwOM1eM8n\/T MI3XIe13AOrMyVmbO+8nMRYL9kekVL4dhx06V9ECIyIwp9c70LlMQgcOCfyPJLFl3NCaNLm80s\/h 4hA="
      },
      {
        "Name": "X-Report-Abuse-To",
        "Value": "spam@quarantine1.mailspamprotection.com"
      },
      {
        "Name": "X-Originating-IP",
        "Value": "35.208.69.218"
      },
      {
        "Name": "X-SpamExperts-Domain",
        "Value": "c104924.sgvps.net"
      },
      {
        "Name": "X-SpamExperts-Username",
        "Value": "35.208.69.218"
      },
      {
        "Name": "Authentication-Results",
        "Value": "mailspamprotection.com; auth=pass smtp.auth=35.208.69.218@c104924.sgvps.net"
      },
      {
        "Name": "X-SpamExperts-Outgoing-Class",
        "Value": "ham"
      },
      {
        "Name": "X-SpamExperts-Outgoing-Evidence",
        "Value": "Combined (0.23)"
      },
      {
        "Name": "X-Recommended-Action",
        "Value": "accept"
      },
      {
        "Name": "X-Filter-ID",
        "Value": "Pt3MvcO5N4iKaDQ5O6lkdGlMVN6RH8bjRMzItlySaT+sfiq3hx8pX3PnEcZQF6zAPUtbdvnXkggZ 3YnVId\/Y5jcf0yeVQAvfjHznO7+bT5yu6jD6\/cbRTNHps+4\/l9fISOJL5jgq0dAaqfZfZ+D5y5Vk L8v0v1QpawOm8sDExxi0eSLSY2NpLbwy4p7HRPiMuA3MH\/gmSvDV4MtyJ\/n1+wHge3HAogrU3AfZ MFKvceKBhUAuFeS54pPv2vsVEup9ygkN6HIGx0G7nQOa1bihorAHx8r\/iErsfoiNuSRmnkSVTg+6 Z8b7XNrLsN6w1rmbZpgz+8Zqo8\/0yqmhGrhFWmeupYYdzPm7YfRDaULOU2lkrQQyZ\/c\/nQCN\/+0h fTQW67Zt5GqKWaOBDqMDdTBiCeNwxuyQxxpFBZS72lACfRLGYP\/Zj29Dpz4JZGFu9hfz+sPa00Wf FmZwEM\/0ZmjjnuQc6n8m9KR6SmWJuaJzM+qUDH1Ezqi4hzy9Wek66NxEOhvacrMVwkgeax70Whjf zJ4cMWqZYm+yJsD+ZoGQ7p8slJ0rink5yGLuN603zpd088XcZRX4p0EGqucJfyPQaLwqyT5p50x8 1ZKcmzCu2U0dOwEwFz1hUezuoZZB7wEpDN6+8DsCup5AUR3adq+ACehggDoMTx64uTo4mWprJRUn WgAofHWoeO84ZebpAH6aTplPMI8qwV+3DFuSDk5MQkFJkPEV0x2OFYpBGzXLrfjaM0qxA8UPDxN9 lFIRIuejs5IZ5dm0Rb3W7BGb6p2Zbz2CH+yShT9dwqyR7QtAppgtuctFVQp078svLEqHvTCZfCmb vHuyadb25I64jbUw3PODXNyNV0iF8Gh0552UEF73Y80OmAux3oN13+ztUzne3UOLo8EIHCjLPS7S nAjVhGt3svE7ZJ3XtYW0BdoTQS1B00PlfzfoiNb1+Fn1ZdvibQD\/g7QRwBU9EDvXStC7Fr+xT1EK v3ObV9aqZ16sL1dm4zuNRcgRKiGg7nXFaZTxk\/yHlWZSM+xwews5pduy\/95D1GKddTNmnIIudXq9 lcPEBM2uaQykbc4VWfL7weynLd1iSIqnJE7c4F2H+M3UkIw9wpL5D\/yF8BAAxodc2tT3K7c+w3qs 5s7L8fDiSW7Ie1Huf6ZU9LftxXX6dQt7i9k\/ZkftvJ3hVLXKl1wZv7WEDrNRkbewq\/prMd40wB\/W 9jihx+Za\/cV70jOJzN2r4A=="
      },
      {
        "Name": "X-Report-Abuse-To",
        "Value": "spam@quarantine1.mailspamprotection.com"
      }
    ],
    "Attachments": []
  }
';
return json_decode($json);
}

add_shortcode('postmark_forwarder_tester','rsvpmaker_postmark_forwarder_tester');

function rsvpmaker_postmark_delete_supression($email) {
    $postmark_settings = get_rsvpmaker_postmark_options();
    $client = new PostmarkClient($postmark_settings['postmark_production_key']);
    $suppressionChanges = array(new SuppressionChangeRequest($email));
    $messageStream = "broadcast";
    $result = $client->deleteSuppressions($suppressionChanges, $messageStream);                        
    $messageStream = "outbound";
    $result2 = $client->deleteSuppressions($suppressionChanges, $messageStream);
    return $result;
}

// Restored for cross-plugin compatibility.
function rsvpmail_clear_allforwarders($blog_id) {
    if($blog_id != 1)
        switch_to_blog(1);
    delete_transient('allforwarders_'.$blog_id);
    if($blog_id != 1)
        switch_to_blog($blog_id);    
}

function rsvpmaker_reset_forwarder_cache() {
    if(is_multisite() && !is_main_site())
        switch_to_blog(1);
   delete_transient('rsvpmaker_valid_mailing_lists');
   delete_transient('all_flattened_forwarders');
   if(is_multisite())
       restore_current_blog();
}