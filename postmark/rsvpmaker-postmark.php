<?php
// Import the Postmark Client Class:
require_once('vendor/autoload.php');
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

function rsvpmaker_postmark_testscreen() {
    global $rsvp_options, $wpdb;
    if(isset($_POST['postmark_mode']) && rsvpmaker_verify_nonce()){
        $rsvp_options['postmark_mode'] = sanitize_text_field($_POST['postmark_mode']);
        $rsvp_options['postmark_sandbox_key'] = sanitize_text_field($_POST['postmark_sandbox_key']);
        $rsvp_options['postmark_production_key'] = sanitize_text_field($_POST['postmark_production_key']);
        $rsvp_options['postmark_tx_from'] = sanitize_text_field($_POST['postmark_tx_from']);
        $rsvp_options['postmark_broadcast_from'] = sanitize_text_field($_POST['postmark_broadcast_from']);
        $rsvp_options['postmark_tx_slug'] = sanitize_text_field($_POST['postmark_tx_slug']);
        $rsvp_options['postmark_broadcast_slug'] = sanitize_text_field($_POST['postmark_broadcast_slug']);
        update_option('RSVPMAKER_Options',$rsvp_options);
    }
    elseif(empty($rsvp_options['postmark_mode']))
    {
        $domain = str_replace('www.','',$_SERVER['SERVER_NAME']);
        $rsvp_options['postmark_mode'] = '';
        $rsvp_options['postmark_sandbox_key'] = '';
        $rsvp_options['postmark_production_key'] = '';
        $rsvp_options['postmark_tx_from'] = 'headsup@'.$domain;
        $rsvp_options['postmark_broadcast_from'] = 'shoutout@'.$domain;
        $rsvp_options['postmark_tx_slug'] = 'outbound';
        $rsvp_options['postmark_broadcast_slug'] = 'broadcast';
    }
    echo 'mode'.$rsvp_options['postmark_mode'];
    printf('<form method="post" action="%s">',admin_url('admin.php?page='.$_GET['page']));
    echo '<h3>Postmark Mode</h3>';
    $checked = (empty($rsvp_options['postmark_mode'])) ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="" %s> Off</p>',$checked);
    $checked = ($rsvp_options['postmark_mode'] == 'sandbox') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="sandbox" %s> Sandbox / Test, Key <input type="text" name="postmark_sandbox_key" value="%s"></p>',$checked, $rsvp_options['postmark_sandbox_key']);
    $checked = ($rsvp_options['postmark_mode'] == 'production') ? ' checked="checked" ' : '';
    printf('<p><input type="radio" name="postmark_mode" value="production" %s> Production, Key <input type="text" name="postmark_production_key" value="%s"></p>',$checked, $rsvp_options['postmark_production_key']);
    printf('<p>Transactional Messages From: <input type="text" name="postmark_tx_from" value="%s"> Stream ID <input type="text" name="postmark_tx_slug" value="%s"></p>',$rsvp_options['postmark_tx_from'],$rsvp_options['postmark_tx_slug']);
    printf('<p>Broadcast Messages From: <input type="text" name="postmark_broadcast_from" value="%s"> Stream ID <input type="text" name="postmark_broadcast_slug" value="%s"></p>',$rsvp_options['postmark_broadcast_from'],$rsvp_options['postmark_broadcast_slug']);
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

function postmark_menu() {
    add_menu_page('Postmark Test','Postmark Test','manage_options','rsvpmaker_postmark_testscreen','rsvpmaker_postmark_testscreen');
}
add_action('admin_menu','postmark_menu');

function rsvpmaker_postmark_broadcast($recipients,$post_id,$message_stream='',$recipient_names=array()) {
    global $rsvp_options;
    $postmark_key = ('production' == $rsvp_options['postmark_mode']) ? $rsvp_options['postmark_production_key'] : $rsvp_options['postmark_sandbox_key'];
    if(empty($message_stream))
        $message_stream = (sizeof($recipients) > 5) ? $rsvp_options['postmark_broadcast_slug'] : $rsvp_options['postmark_tx_slug'];
    $mpost = get_post($post_id);
    $meta = get_post_meta($post_id);
    if(isset($meta['_rsvpmail_html'][0]))
        $html = $meta['_rsvpmail_html'][0];
    else {
        $html = rsvpmail_filter_style(do_blocks(do_shortcode($mpost->post_content)));
        update_post_meta($post_id,'_rsvpmail_html',$html);
    }
    $html = rsvpmail_replace_placeholders($html);
    $text = rsvpmaker_text_version($html);
    $mail['Subject'] = $mpost->post_title;
    $mail['MessageStream'] = $message_stream;
    if(isset($meta['rsvprelay_from'][0]))
        $mail['ReplyTo'] = $meta['rsvprelay_from'][0];
    $mail['From'] = ($message_stream == $rsvp_options['postmark_tx_slug']) ? $rsvp_options['postmark_tx_from'] : $rsvp_options['postmark_broadcast_from'];
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
    global $rsvp_options;
    $postmark_key = ('production' == $rsvp_options['postmark_mode']) ? $rsvp_options['postmark_production_key'] : $rsvp_options['postmark_sandbox_key'];
    $message_stream = 'outbound';
    try {
        $client = new PostmarkClient($postmark_key);
        $result = $client->sendEmail($rsvp_options['postmark_tx_from'], $mail['to'], $mail['subject'], $mail['html']);    
    }
    catch (PostmarkException $e) {
        $result = $e;
        //print_r($e);
    }
    return var_export($result,true);
}