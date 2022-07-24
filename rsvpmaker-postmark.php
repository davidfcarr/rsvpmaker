<?php
// Import the Postmark Client Class:
require_once('postmark/vendor/autoload.php');
use Postmark\PostmarkClient;
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
            update_option('rsvpmaker_postmark',$postmark_settings);
        else
            update_option('rsvpmaker_postmark',$postmark_settings);
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
}

function rsvpmaker_postmark_broadcast($recipients,$post_id,$message_stream='',$recipient_names=array()) {
    global $wpdb;
    $recipients = rsvpmaker_recipients_no_problems($recipients);
    if(sizeof($recipients) > 201) {
        $chunks = array_chunk($recipients,200);
        echo $log = sprintf('<p>split into %s chunks</p>',sizeof($chunks));
        rsvpmaker_debug_log($log,'broadcast recipient chunks');
        $recipients = array_shift($chunks);
        foreach($chunks as $chunk) {
            add_post_meta($post_id,'rsvprelay_to_batch',$chunk);
        }    
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
        do_action('postmark_sent',$sent,$mail['Subject']);
        printf('<p>Successful sends %d ending with %s</p>',count($sent),$sent[sizeof($sent)-1]);
        foreach($sent as $e) {
            add_post_meta($post_id,'rsvpmail_sent_postmark',$e);
        }
    }
    if(count($send_error)) {
        printf('<p>Errors %d (see log)</p>',count($send_error));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
}

function rsvpmaker_postmark_send($mail) {
    rsvpmaker_debug_log($mail,'rsvpmaker_postmark_send start');
    $postmark_settings = get_rsvpmaker_postmark_options();
    $mail['MessageStream'] = $postmark_settings['postmark_tx_slug'];
    $batch = rsvpmaker_postmark_batch($mail, $mail['to']);
    rsvpmaker_debug_log($batch,'rsvpmaker_postmark_send batch');
    $result = rsvpmaker_postmark_batch_send($batch);
    rsvpmaker_debug_log($result,'rsvpmaker_postmark_batch_send result');
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
    rsvpmaker_debug_log($hosts_and_subdomains,'hosts and subdomains');
	foreach($forwarders as $email) {
        rsvpmaker_debug_log($email,'forwarders as email loop');
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
        rsvpmaker_debug_log($slug_and_id,'slug_and_id');
        if(!empty($slug_and_id)) {
            $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj);
            rsvpmaker_debug_log($recipients,'rsvpmail_recipients_by_slug_and_id');
            if($recipients) {
                $batch = rsvpmaker_postmark_batch($emailobj, $recipients, $slug_and_id);
                $result = rsvpmaker_postmark_batch_send($batch);
                rsvpmaker_debug_log($result,'rsvpmaker_postmark_batch_send result');
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

add_shortcode('rsvpmaker_postmark_array_test','rsvpmaker_postmark_array_test');

function rsvpmaker_postmark_array_test() {
    $mail['to'] = 'david@carrcommunications.com';
    $mail['cc'] = 'david@rsvpmaker.com';
    $mail['subject'] = 'postmark array test';
    $mail['MessageStream'] = 'outbound';
    $mail['html'] = '<p>message</p>';
    $mail = rsvpmaker_postmark_array($mail);
    return var_export($mail,true);
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
    rsvpmaker_debug_log($template,'postmark array template');
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
        do_action('postmark_sent',$sent,$batch[0]['Subject']);
        $output .= sprintf('<p>Successful sends %d</p>',count($sent));
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

add_shortcode('postmark_invite_test','postmark_invite_test');
function postmark_invite_test() {
$post_id = 122762;
$from_email = 'rsvp@rsvpmaker.com';
$rsvp_email = 'david@carrcommunications.com';
$conf = get_post(117934);
$ical = rsvpmaker_to_ical_email( $post_id, $from_email, $rsvp_email, rsvpmaker_text_version(do_blocks($conf->post_content)) );
$timestamp = date('r');
$mail['to'] = 'david@carrcommunications.com';
$mail['subject'] = 'invite test '.$timestamp;
$mail['from'] = 'david@rsvpmaker.com';
$mail['fromname'] = 'RSVPMaker Test';
$base64 = base64_encode($ical);
$mail['Attachments'][] = array('ContentType' => 'application/ics','Name'=>'invite.ics','Content'=>$base64);
$mail['Attachments'][] = array('ContentType' => 'text/calendar; charset=\"UTF-8\"; method=REQUEST','Name'=>'Invitation.ics','Content'=>$base64);
$mail['html'] = '<p>this is a test</p>';

$output = '';
$output .= 'test send '.$timestamp . rsvpmaker_postmark_send($mail);
$output .= '<p><textarea cols="200" rows="50">'.$ical.'</textarea></p>';
//$base64 = "QkVHSU46VkNBTEVOREFSDQpQUk9ESUQ6LS8vR29vZ2xlIEluYy8vR29vZ2xlIENhbGVuZGFyIDcwLjkwNTQvL0VODQpWRVJTSU9OOjIuMA0KQ0FMU0NBTEU6R1JFR09SSUFODQpNRVRIT0Q6UkVRVUVTVA0KQkVHSU46VkVWRU5UDQpEVFNUQVJUOjIwMjIwNzE4VDE2MDAwMFoNCkRURU5EOjIwMjIwNzE4VDE3MDAwMFoNCkRUU1RBTVA6MjAyMjA3MThUMTI1ODMzWg0KT1JHQU5JWkVSO0NOPURhdmlkIEYuIENhcnI6bWFpbHRvOmRhdmlkQGNhcnJjb21tdW5pY2F0aW9ucy5jb20NClVJRDo3aGt2ZnJldjB0b2dwZW1xaWdlcDcyMTM0akBnb29nbGUuY29tDQpBVFRFTkRFRTtDVVRZUEU9SU5ESVZJRFVBTDtST0xFPVJFUS1QQVJUSUNJUEFOVDtQQVJUU1RBVD1ORUVEUy1BQ1RJT047UlNWUD0NCiBUUlVFO0NOPXpvb21ndWVzdEByc3ZwbWFrZXIuY29tO1gtTlVNLUdVRVNUUz0wOm1haWx0bzp6b29tZ3Vlc3RAcnN2cG1ha2VyLmMNCiBvbQ0KQVRURU5ERUU7Q1VUWVBFPUlORElWSURVQUw7Uk9MRT1SRVEtUEFSVElDSVBBTlQ7UEFSVFNUQVQ9QUNDRVBURUQ7UlNWUD1UUlVFDQogO0NOPURhdmlkIEYuIENhcnI7WC1OVU0tR1VFU1RTPTA6bWFpbHRvOmRhdmlkQGNhcnJjb21tdW5pY2F0aW9ucy5jb20NClgtTUlDUk9TT0ZULUNETy1PV05FUkFQUFRJRDotMzk0NDQ0NTIwDQpDUkVBVEVEOjIwMjIwNzE4VDEyNTgwNloNCkRFU0NSSVBUSU9OOlBsZWFzZSBqb2luIG1lIGluIGEgWm9vbSBtZWV0aW5nIGF0Jm5ic3BcOzxhIGhyZWY9Imh0dHBzOi8vem9vbQ0KIC51cy9qLzQ3NDc0NzE5NjM/cHdkPVFYcFlaMWxoZG1adVJHcDRRVkJPVUZWRGFHVmxkejA5IiBpZD0ib3c2NzIiIF9faXNfb3duZQ0KIHI9InRydWUiPmh0dHBzOi8vem9vbS51cy9qLzx3YnI+NDc0NzQ3MTk2Mz9wd2Q9PHdicj5RWHBZWjFsaGRtWnVSR3A0UVZCT1VGVg0KIERhR1ZsZHo8d2JyPjA5PC9hPjxicj48YnI+VG8gam9pbiBmcm9tIHRoZSBab29tIGFwcFwsIGVudGVyIHRoaXMgbWVldGluZyBJRA0KICBhbmQgcGFzc3dvcmQ8YnI+PGJyPk1lZXRpbmcgSUQ6IDQ3NCA3NDcgMTk2Mzxicj5QYXNzd29yZDogMDAyNDQ1XG5cbi06On46fg0KIDo6fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46Og0KIH46fjo6LVxuRG8gbm90IGVkaXQgdGhpcyBzZWN0aW9uIG9mIHRoZSBkZXNjcmlwdGlvbi5cblxuVmlldyB5b3VyIGV2ZW50IGF0IA0KIGh0dHBzOi8vY2FsZW5kYXIuZ29vZ2xlLmNvbS9jYWxlbmRhci9ldmVudD9hY3Rpb249VklFVyZlaWQ9TjJocmRtWnlaWFl3ZEc5bg0KIGNHVnRjV2xuWlhBM01qRXpOR29nZW05dmJXZDFaWE4wUUhKemRuQnRZV3RsY2k1amIyMCZ0b2s9TWpnalpHRjJhV1JBWTJGeWNtTg0KIHZiVzExYm1sallYUnBiMjV6TG1OdmJXTmtNelZrT1RBNU1XTmlOMlppWmpJd05qRTVZV1UwWW1Jd09HWmlNek5tTVRnMk5qRTFORw0KIFkmY3R6PUFtZXJpY2ElMkZOZXdfWW9yayZobD1lbiZlcz0xLlxuLTo6fjp+Ojp+On46fjp+On46fjp+On46fjp+On46fjp+On46fg0KIDp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjp+On46fjo6fjp+OjotDQpMQVNULU1PRElGSUVEOjIwMjIwNzE4VDEyNTgzMloNCkxPQ0FUSU9OOmh0dHBzOi8vem9vbS51cy9qLzQ3NDc0NzE5NjM/cHdkPVFYcFlaMWxoZG1adVJHcDRRVkJPVUZWRGFHVmxkejA5DQpTRVFVRU5DRTowDQpTVEFUVVM6Q09ORklSTUVEDQpTVU1NQVJZOlRlc3QNClRSQU5TUDpPUEFRVUUNCkVORDpWRVZFTlQNCkVORDpWQ0FMRU5EQVINCg==";
//$output .= '<p>Sample:<br><textarea cols="200" rows="50">'.str_replace(' ','| |',base64_decode($base64)).'</textarea></p>';

return $output;
}