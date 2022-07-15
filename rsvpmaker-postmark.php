<?php
// Import the Postmark Client Class:
require_once('postmark/vendor/autoload.php');
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

function get_rsvpmaker_postmark_options() {
    if(is_multisite())
        $postmark = get_blog_option(1,'rsvpmaker_postmark');
    else
        $postmark = get_option('rsvpmaker_postmark');
    if(empty($postmark))
        $postmark['postmark_mode'] = '';
    return $postmark;
}

function rsvpmaker_postmark_testscreen() {
    global $postmark, $wpdb;
    if(isset($_POST['postmark_mode']) && rsvpmaker_verify_nonce()){
        $postmark['postmark_mode'] = sanitize_text_field($_POST['postmark_mode']);
        $postmark['postmark_sandbox_key'] = sanitize_text_field($_POST['postmark_sandbox_key']);
        $postmark['postmark_production_key'] = sanitize_text_field($_POST['postmark_production_key']);
        $postmark['postmark_tx_from'] = sanitize_text_field($_POST['postmark_tx_from']);
        $postmark['postmark_broadcast_from'] = sanitize_text_field($_POST['postmark_broadcast_from']);
        $postmark['postmark_tx_slug'] = sanitize_text_field($_POST['postmark_tx_slug']);
        $postmark['postmark_broadcast_slug'] = sanitize_text_field($_POST['postmark_broadcast_slug']);
        $postmark['handle_incoming'] = sanitize_text_field($_POST['handle_incoming']);
        printf('<p>Update postmark %s</p>',var_export($postmark,true));
        update_option('rsvpmaker_postmark',$postmark);
    }
    else {
        $postmark = get_rsvpmaker_postmark_options();
        printf('<p>Get postmark options %s</p>',var_export($postmark,true));
    }

    if(empty($postmark['postmark_mode']))
    {
        $postmark['postmark_domain'] = $domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
        $postmark['postmark_mode'] = '';
        $postmark['postmark_sandbox_key'] = '';
        $postmark['postmark_production_key'] = '';
        $postmark['postmark_tx_from'] = 'headsup@'.$domain;
        $postmark['postmark_broadcast_from'] = 'shoutout@'.$domain;
        $postmark['postmark_tx_slug'] = 'outbound';
        $postmark['postmark_broadcast_slug'] = 'broadcast';
        $postmark['handle_incoming'] = '';
    }
    echo 'mode'.$postmark['postmark_mode'];
    printf('<form method="post" action="%s">',admin_url('admin.php?page='.$_GET['page']));
    echo '<h3>Postmark Mode</h3>';
    $checked = (empty($postmark['postmark_mode'])) ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="" %s> Off</p>',$checked);
    $checked = ($postmark['postmark_mode'] == 'sandbox') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="sandbox" %s> Sandbox / Test, Key <input type="text" name="postmark_sandbox_key" value="%s"></p>',$checked, $postmark['postmark_sandbox_key']);
    $checked = ($postmark['postmark_mode'] == 'production') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="production" %s> Production, Key <input type="text" name="postmark_production_key" value="%s"></p>',$checked, $postmark['postmark_production_key']);
    printf('<p>Transactional Messages From: <input type="text" name="postmark_tx_from" value="%s"> Stream ID <input type="text" name="postmark_tx_slug" value="%s"></p>',$postmark['postmark_tx_from'],$postmark['postmark_tx_slug']);
    printf('<p>Broadcast Messages From: <input type="text" name="postmark_broadcast_from" value="%s"> Stream ID <input type="text" name="postmark_broadcast_slug" value="%s"></p>',$postmark['postmark_broadcast_from'],$postmark['postmark_broadcast_slug']);
    $code = (empty($postmark['handle_incoming'])) ? wp_create_nonce('handle_incoming') : $postmark['handle_incoming'];
    $url = rest_url('rsvpmaker/v1/postmark_incoming/'.$code);
    $ckyes = (!empty($postmark['handle_incoming'])) ? ' checked="checked" ' : '';
    $ckno = (empty($postmark['handle_incoming'])) ? ' checked="checked" ' : '';
    printf('<p>Handle Incoming Webhook: <input type="radio" name="handle_incoming" value="%s" %s> Yes <input type="radio" name="handle_incoming" value="" %s> No<br>Webhook address to register in Postmark %s</p>',$code,$ckyes, $ckno,$url);
    rsvpmaker_nonce();
    echo '<button>Submit</button></form>';

    if(isset($_GET['test'])) {
        $users = get_users();
        foreach($users as $user) {
            $recipients[] = $user->user_email;
            $recipient_names[$user->user_email] = $user->display_name;
        }
        $post_id = 123115;
        rsvpmaker_postmark_broadcast($recipients,$post_id,'broadcast',$recipient_names);
    }

    if(isset($_GET['guest'])) {
        $table = rsvpmaker_guest_list_table();
        $guests = $wpdb->get_results("select * from $table WHERE active");
        echo ' guests ';
        print_r($guests);
        $post_id = 123588;
        foreach($guests as $guest) {
            if(rsvpmail_is_problem($guest->email))
                continue;
            $recipients[] = $guest->email;
            $recipient_names[$guest->email] = $guest->first_name.' '.$guest->last_name;
        }
        rsvpmaker_postmark_broadcast($recipients,$post_id,'broadcast',$recipient_names);
    }
    if(isset($_GET['latest'])) {
        $table = rsvpmaker_guest_list_table();
        $guests = $wpdb->get_results("select * from $table WHERE active");
        echo ' guests ';
        print_r($guests);
        $post_id = rsvpmail_latest_post_promo();
        foreach($guests as $guest) {
            if(rsvpmail_is_problem($guest->email))
                continue;
            $recipients[] = $guest->email;
            $recipient_names[$guest->email] = $guest->first_name.' '.$guest->last_name;
        }
        rsvpmaker_postmark_broadcast($recipients,$post_id,'broadcast',$recipient_names);
    }
    if(isset($_GET['tx'])) {
        $mail['to'] = 'david@rsvpmaker.com';
        $mail['from'] = 'david@rsvpmaker.com';
        $mail['subject'] = 'TX email test';
        $mail['html'] = '<h1>Wow</h1>';
        echo $result = rsvpmaker_postmark_send($mail);
    }

}

function rsvpmaker_postmark_menu() {
    add_menu_page('RSVPMaker Postmark Settings (Beta)','RSVPMaker Postmark Settings (Beta)','manage_options','rsvpmaker_postmark_testscreen','rsvpmaker_postmark_testscreen');
}
add_action('admin_menu','rsvpmaker_postmark_menu');

function rsvpmaker_postmark_broadcast($recipients,$post_id,$message_stream='',$recipient_names=array()) {
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    if(empty($message_stream))
        $message_stream = (sizeof($recipients) > 1) ? $postmark['postmark_broadcast_slug'] : $postmark['postmark_tx_slug'];
    $mpost = get_post($post_id);
    $meta = get_post_meta($post_id);
    if(isset($meta['_rsvpmail_html'][0]))
        $html = $meta['_rsvpmail_html'][0];
    else {
        $html = rsvpmaker_email_html($mpost,$post_id);//rsvpmail_filter_style(do_blocks(do_shortcode($mpost->post_content)));
        update_post_meta($post_id,'_rsvpmail_html',$html);
    }
    $html = rsvpmail_replace_placeholders($html);
    $text = rsvpmaker_text_version($html);
    $mail['Subject'] = $mpost->post_title;
    $mail['MessageStream'] = $message_stream;
    if(isset($meta['rsvprelay_from'][0]))
        $mail['ReplyTo'] = $meta['rsvprelay_from'][0];
    $mail['From'] = ($message_stream == $postmark['postmark_tx_slug']) ? $postmark['postmark_tx_from'] : $postmark['postmark_broadcast_from'];
    $fromname = (empty($meta['rsvprelay_fromname'][0])) ? get_bloginfo('name') : $meta['rsvprelay_fromname'][0];
    $mail['From'] = rsvpmaker_email_add_name($mail['From'],$fromname);
    $client = new PostmarkClient($postmark_key);

    foreach($recipients as $index => $to) {
        if(isset($recipient_names[$to]))
            $mail['To'] = rsvpmaker_email_add_name($to,$recipient_names[$to]);
        else
            $mail['To'] = $to;
        $mail['HtmlBody'] = str_replace('*|EMAIL|*',$to,$html);
        $mail['TextBody'] = str_replace('*|EMAIL|*',$to,$text);
        $batch[] = $mail;
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
    if(count($send_error)) {
        printf('<p>Successful sends %d</p>',count($sent));
        foreach($sent as $e) {
            add_post_meta($post_id,'rsvpmail_sent',$e);
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
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    $message_stream = 'outbound';
    try {
        $client = new PostmarkClient($postmark_key);
        $result = $client->sendEmail($postmark['postmark_tx_from'], $mail['to'], $mail['subject'], $mail['html']);    
    }
    catch (PostmarkException $e) {
        $result = $e;
        //print_r($e);
    }
    return var_export($result,true);
}

function rsvpmaker_postmark_incoming($forwarders,$emailobj,$post_id) {
	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
	foreach($forwarders as $email) {
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
	    //actions like auto-replies
        //do_action('rsvpmail_relay_slug_and_id',$slug_and_id,$email,$emailobj);
        rsvpmaker_debug_log($email,'incoming email');
        rsvpmaker_debug_log($slug_and_id,'incoming slug and id');
        if(!empty($slug_and_id)) {
            $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj);
            rsvpmaker_debug_log($recipients,'incoming recpients');
            if($recipients) {
                $recipient_names = get_transient('recipient_names_'.$slug_and_id['slug']);
                if(empty($recipient_names))
                    $recipient_names = array();
                if(!strpos($emailobj->Subject,']'))
                    $emailobj->Subject = '['.$slug_and_id['slug'].'] '.$emailobj->Subject;
                rsvpmail_postmark_forward($recipients,$emailobj,$post_id,$recipient_names,$email);
            }           
        }
	}
}

function rsvpmail_postmark_forward($recipients,$emailobj,$post_id,$recipient_names=array(),$forwarder='')
{
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    $message_stream = (sizeof($recipients) > 5) ? $postmark['postmark_broadcast_slug'] : $postmark['postmark_tx_slug'];
    $mail['MessageStream'] = $message_stream;
    $mail['From'] = ($message_stream == $postmark['postmark_tx_slug']) ? $postmark['postmark_tx_from'] : $postmark['postmark_broadcast_from'];
    if(!empty($emailobj->FromName))
    {
        $mail['From'] = rsvpmaker_email_add_name($mail['From'],$emailobj->FromName);
        if(!empty($forwarder))
            $mail['From'] .= ' (via '.$forwarder.')';
    }
    $mail['ReplyTo'] = $emailobj->From;
    $mail['Subject'] = $emailobj->Subject;
    rsvpmaker_debug_log($mail,'rsvpmail_postmark_forward');
    $client = new PostmarkClient($postmark_key);

    foreach($recipients as $to) {
        if(isset($recipient_names[$to]))
            $mail['To'] = rsvpmaker_email_add_name($to,$recipient_names[$to]);
        else
            $mail['To'] = $to;
        $mail['HtmlBody'] = rsvpmaker_personalize_email($emailobj->HtmlBody,$to,'');
        $mail['TextBody'] = rsvpmaker_text_version($mail['HtmlBody']);
        $batch[] = $mail;
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
        printf('<p>Successful sends %d</p>',count($sent));
        foreach($sent as $e) {
            add_post_meta($post_id,'rsvpmail_sent',$e);
        }
    }
    if(count($send_error)) {
        printf('<p>Errors %d (see log)</p>',count($send_error));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
}
