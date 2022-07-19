<?php
// Import the Postmark Client Class:
require_once('postmark/vendor/autoload.php');
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

function get_rsvpmaker_postmark_options() {
    global $postmark;
    if(is_multisite())
        $postmark = get_blog_option(1,'rsvpmaker_postmark');
    else
        $postmark = get_option('rsvpmaker_postmark');
    if(empty($postmark))
        $postmark['postmark_mode'] = '';
    return $postmark;
}

function rsvpmaker_postmark_is_live() {
    global $postmark;
    //if(empty($postmark))
        $postmark = get_rsvpmaker_postmark_options();
    return (!empty($postmark['postmark_production_key']) && 'production' == $postmark['postmark_mode']);
}

function rsvpmaker_postmark_is_active() {
    global $postmark;
    if(empty($postmark))
        $postmark = get_rsvpmaker_postmark_options();
    return ((!empty($postmark['postmark_production_key']) && 'production' == $postmark['postmark_mode']) || (!empty($postmark['postmark_sandbox_key']) && 'sandbox' == $postmark['postmark_mode']));
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
    if(empty($postmark['postmark_domain']))
        $postmark['postmark_domain'] = $domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
        if(empty($postmark['postmark_mode']))
            $postmark['postmark_mode'] = '';
        if(empty($postmark['postmark_sandbox_key']))
            $postmark['postmark_sandbox_key'] = '';
        if(empty($postmark['postmark_production_key']))
            $postmark['postmark_production_key'] = '';
        if(empty($postmark['postmark_tx_from']))
            $postmark['postmark_tx_from'] = 'headsup@'.$domain;
        if(empty($postmark['postmark_broadcast_from']))
            $postmark['postmark_broadcast_from'] = 'shoutout@'.$domain;
        if(empty($postmark['postmark_tx_slug']))
            $postmark['postmark_tx_slug'] = 'outbound';
        if(empty($postmark['postmark_broadcast_slug']))
            $postmark['postmark_broadcast_slug'] = 'broadcast';
        if(empty($postmark['handle_incoming']))
            $postmark['handle_incoming'] = '';
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
}

function rsvpmaker_postmark_menu() {
    add_menu_page('RSVPMaker Postmark Settings (Beta)','RSVPMaker Postmark Settings (Beta)','manage_options','rsvpmaker_postmark_testscreen','rsvpmaker_postmark_testscreen');
}
add_action('admin_menu','rsvpmaker_postmark_menu');

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

    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    if(empty($message_stream))
        $message_stream = (sizeof($recipients) > 1) ? $postmark['postmark_broadcast_slug'] : $postmark['postmark_tx_slug'];
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
    $postmark = get_rsvpmaker_postmark_options();
    $mail['MessageStream'] = $postmark['postmark_tx_slug'];
    $batch = rsvpmaker_postmark_batch($emailobj, $recipients);
    $result = rsvpmaker_postmark_batch_send($batch);
    rsvpmaker_debug_log($result,'rsvpmaker_postmark_batch_send result');
    return $result;
/*
    global $wpdb;
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    $message_stream = 'outbound';


    $from = $postmark['postmark_tx_from'];
    if(!empty($mail['fromname']) && !strpos($from,'<')) // add name if not already added
        $from = rsvpmaker_email_add_name($from,$mail['fromname']);
    rsvpmaker_debug_log($from,'postmark send from');
    $post_id = empty($mail['post_id']) ? 0 : $mail['post_id'];
    $recipients = rsvpmaker_expand_recipients($mail['to']);
    if(is_array($recipients) && !empty($recipients)) {
        if($post_id)
            $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$mail['to']."' AND post_id=$post_id ");
        $recipients = rsvpmaker_recipients_no_problems($recipients);
        $output = '';
        foreach($recipients as $to) {
            $mail['to'] = $to;
            $output .= ' '.rsvpmaker_postmark_send($mail);
        }
        return $output;
    }
    try {
        $client = new PostmarkClient($postmark_key);
        $result = $client->sendEmail($from, $mail['to'], $mail['subject'], $mail['html']);    
    }
    catch (PostmarkException $e) {
        $result = $e;
        //print_r($e);
    }
    if($post_id)
        $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$mail['to']."' AND post_id=$post_id ");
    do_action('postmark_sent',array($mail['to']),$mail['subject']);
    return var_export($result,true);
*/
}

function rsvpmaker_postmark_incoming($forwarders,$emailobj,$post_id) {
    if('david@carrcommunications.com' == $emailobj->From && 'stop' == $emailobj->Subject) {
        $postmark = get_rsvpmaker_postmark_options();
        $postmark['postmark_mode'] = '';
        update_blog_option(1,'rsvpmaker_postmark',$postmark);
        mail('david@carrcommunications.com','postmark deactivated',date('r'));
    }
	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
	foreach($forwarders as $email) {
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
        if(!empty($slug_and_id)) {
            $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj);
            if($recipients) {
                $batch = rsvpmaker_postmark_batch($emailobj, $recipients, $slug_and_id);
                $result = rsvpmaker_postmark_batch_send($batch);
                rsvpmaker_debug_log($result,'rsvpmaker_postmark_batch_send result');
                /*
                $recipient_names = get_transient('recipient_names');
                if(empty($recipient_names))
                    $recipient_names = array();
                if(!strpos($emailobj->Subject,']'))
                    $emailobj->Subject = '['.$slug_and_id['slug'].'] '.$emailobj->Subject;
                rsvpmail_postmark_forward($recipients,$emailobj,$post_id,$recipient_names,$email);
                */
            }
        }
	}
}

/*
function rsvpmail_postmark_forward($recipients,$emailobj,$post_id,$recipient_names=array(),$forwarder='')
{
    global $wpdb;
    $recipients = rsvpmaker_recipients_no_problems($recipients);
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    $message_stream = $postmark['postmark_broadcast_slug'];
    $mail['MessageStream'] = $message_stream;
    $mail['From'] = ($message_stream == $postmark['postmark_tx_slug']) ? $postmark['postmark_tx_from'] : $postmark['postmark_broadcast_from'];
    if(!empty($emailobj->FromName))
    {
        if(!empty($forwarder))
            $emailobj->FromName .= ' (via '.$forwarder.')';
        $mail['From'] = rsvpmaker_email_add_name($mail['From'],$emailobj->FromName);
    }
    $mail['ReplyTo'] = $emailobj->From;
    $mail['Subject'] = $emailobj->Subject;
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
        do_action('postmark_sent',$sent,$emailobj->Subject);
        printf('<p>Successful sends %d</p>',count($sent));
        foreach($sent as $e) {
            if($post_id)
                $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$e."' AND post_id=$post_id ");
            add_post_meta($post_id,'rsvpmail_sent_by_postmark',$e);
        }
    }
    if(count($send_error)) {
        printf('<p>Errors %d (see log)</p>',count($send_error));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
}
*/

function rsvpmaker_postmark_array($source, $message_stream = 'broadcast', $slug_and_id = NULL) {
    global $via;
    $slug = (is_array($slug_and_id) && !empty($slug_and_id['slug'])) ? '['.$slug_and_id['slug'].'] ' : '';
    $postmark = get_rsvpmaker_postmark_options();
    if(is_array($source) && isset($source['HtmlBody']))
        return $source;//already set up
    if(is_array($source)) {
        $mail['HtmlBody'] = $source['html'];
        $mail['ReplyTo'] = $source['from'];
        $mail['From'] = ($postmark['postmark_broadcast_slug'] == $message_stream) ? $postmark['postmark_broadcast_from'] : $postmark['postmark_tx_from'];//check
        if($source['fromname'])
        $mail['From'] = rsvpmaker_email_add_name($mail['From'],$source['fromname'].$via);
        $mail['Subject'] = $slug.$mail['subject'];
        if(isset($source['to']))
            $mail['To'] = $source['to'];
        if(isset($source['post_id']))
            $mail['post_id'] = $source['post_id'];
        if(isset($source['Attachments']))
            $mail['Attachments'] = $source['Attachments'];
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
        $mail['From'] = ($postmark['postmark_broadcast_slug'] == $message_stream) ? $postmark['postmark_broadcast_from'] : $postmark['postmark_tx_from'];//check
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
    $postmark = get_rsvpmaker_postmark_options();
    //use tx only for small batches like rsvp notification / confirmation
    $message_stream = ((sizeof($recipients) < 3) && is_array($mail) && $postmark['postmark_tx_slug'] == $mail['MessageStream']) ? $postmark['postmark_tx_slug'] : $postmark['postmark_broadcast_slug'];
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
    $postmark = get_rsvpmaker_postmark_options();
    $postmark_key = ('production' == $postmark['postmark_mode']) ? $postmark['postmark_production_key'] : $postmark['postmark_sandbox_key'];
    $client = new PostmarkClient($postmark_key);
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
        do_action('postmark_sent',$sent,$emailobj->Subject);
        printf('<p>Successful sends %d</p>',count($sent));
        foreach($sent as $e) {
            if($post_id)
                $wpdb->query("update $wpdb->postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND meta_value LIKE '".$e."' AND post_id=$post_id ");
            add_post_meta($post_id,'rsvpmail_sent_by_postmark',$e);
        }
    }
    if(count($send_error)) {
        printf('<p>Errors %d (see log)</p>',count($send_error));
        foreach($send_error as $error) {
            add_post_meta($post_id,'rsvpmail_postmark_error',$error);
        }
    }
}

//remove after testing
//add_action('postmark_incoming_email_object','postmark_incoming_email_object_test',10,2);
function postmark_incoming_email_object_test($emailobj, $json) {

	$upload_dir   = wp_upload_dir();

	if ( ! empty( $upload_dir['basedir'] ) ) {
		$fname = $upload_dir['basedir'].'/'.$emailobj->Subject.'_'.time().'.json';
		file_put_contents($fname, $json, FILE_APPEND);
	}
	if ( ! empty( $upload_dir['basedir'] )) {
    $batch = rsvpmaker_postmark_batch($emailobj,'david@carrcommunications.com');
    $fname = $upload_dir['basedir'].'/emailresults_'.date('Y-m-d').'.txt';
    file_put_contents($fname, var_export($batch,true)."\n\n", FILE_APPEND);
    $result = rsvpmaker_postmark_batch_send($batch,'david@carrcommunications.com');
    file_put_contents($fname, 'Result:'.$result."\n\n", FILE_APPEND);
    }
}
