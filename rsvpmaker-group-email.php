<?php
/*
Group Email Functions
*/

function rsvpmaker_relay_active_lists() {
    $active = get_option('rsvpmaker_discussion_active');
    $lists = array();
    if(!$active)
        return array();
    $vars = get_option('rsvpmaker_discussion_member');
    if(!empty($vars['password']))
        $lists['member'] = $vars['user'];
    $vars = get_option('rsvpmaker_discussion_officer');
    if(!empty($vars['password']))
        $lists['officer'] = $vars['user'];
    return $lists;
}

function rsvpmaker_relay_menu_pages(){
    $parent_slug = "edit.php?post_type=rsvpemail";
    add_submenu_page($parent_slug, 
        __( 'Group Email', 'rsvpmaker' ),
        __( 'Group Email', 'rsvpmaker' ),
        'manage_options',
        'rsvpmaker_relay_manual_test',
        'rsvpmaker_relay_manual_test'
    ); 
}
add_action( 'admin_menu', 'rsvpmaker_relay_menu_pages' );

function rsvpmaker_relay_manual_test() {
echo '<h1>Manually Trigger Check of Email</h1>';
$html = rsvpmaker_relay_init(true);
if($html)
    echo $html;
else
    echo '<p>No messages</p>';
}

function rsvpmaker_relay_init ($show = false) {
    $active = get_option('rsvpmaker_discussion_active');
    if(!$active && !$show)
        return;
    $qresult = rsvpmaker_relay_queue();
    if(empty($qresult))
    {
        $result = rsvpmaker_relay_get_pop('member');
        if(!strpos($result,'Mail:'))
        $result .= rsvpmaker_relay_get_pop('officer');
        if(!strpos($result,'Mail:'))
        $result .= rsvpmaker_relay_get_pop('extra');    
    }
    if(!empty($qresult) || strpos($result,'Mail:'))
    {
        if($show)
            return $result;
        rsvpmaker_debug_log($result,'rsvpmaker_relay_result');
    }
}

add_action('init','rsvpmaker_relay_init');

function rsvpmaker_relay_queue() {
    global $wpdb;
    $sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE meta_key='rsvprelay_to' AND (post_status='publish' OR post_status='draft') LIMIT 0,40 ";
    $results = $wpdb->get_results($sql);
    if(empty($results))
        return;
    $html = '<p>Results: '.sizeof($results).'</p>';
    //print_r($results);
    if(!empty($results))
    {
        foreach($results as $row) {
            if(!isset($_GET['debug']))
            {
                $sql = "DELETE FROM $wpdb->postmeta WHERE meta_id=".$row->meta_id;
                $wpdb->query($sql);    
            }
            if(empty($row->post_title) || empty($row->post_content))
                continue;
            $html .= '<br />';
            $html .= var_export($row, true);
            $mail['from'] = 'noreply@'.str_replace('www.','',$_SERVER['SERVER_NAME']);
            $mail['replyto'] = get_post_meta($row->ID,'rsvprelay_from',true);
            $mail['fromname'] = get_post_meta($row->ID,'rsvprelay_fromname',true);
            $attachments = get_post_meta($row->ID,'rsvprelay_attpath',true);
            if(isset($_GET['debug']))
                printf('<p>Attachments - mail queue %s</p>',var_export($attachments,true));
            if(!empty($attachments))
                $mail['attachments'] = $attachments;
            $mail['subject'] = $row->post_title;
            $mail['html'] = $row->post_content;
            $mail['to'] = $row->meta_value;
            $html .= sprintf('<p>%s to %s</p>',$row->post_title,$row->meta_value);
            rsvpmailer($mail);
            //print_r($mail);
            //printf('<p>%s</p>',$sql);
        }
        return $html;
    }    
}

function group_emails_extract($text) {
preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $text, $emails);
$emails = $emails[0];
$unique = array();
foreach($emails as $email)
    {
        $email = strtolower($email);
        $unique[$email] = $email;
    }
return $unique;
}

function get_mime_type(&$structure) {
    $primary_mime_type = array("TEXT", "MULTIPART","MESSAGE", "APPLICATION", "AUDIO","IMAGE", "VIDEO", "OTHER");
    if($structure->subtype) {
        return $primary_mime_type[(int) $structure->type] . '/' .$structure->subtype;
    }
        return "TEXT/PLAIN";
    }

function get_part($stream, $msg_number, $mime_type, $structure = false,$part_number    = false) {
    
        if(!$structure) {
            $structure = imap_fetchstructure($stream, $msg_number);
        }
        if($structure) {
            if($mime_type == get_mime_type($structure)) {
                if(!$part_number) {
                    $part_number = "1";
                }
                $text = imap_fetchbody($stream, $msg_number, $part_number);
                if($structure->encoding == 3) {
                    return imap_base64($text);
                } else if($structure->encoding == 4) {
                    return imap_qprint($text);
                } else {
                return $text;
            }
        }
    
         if($structure->type == 1) /* multipart */ {
            while(list($index, $sub_structure) = each($structure->parts)) {
                if($part_number) {
                    $prefix = $part_number . '.';
                }
                $data = get_part($stream, $msg_number, $mime_type, $sub_structure,$prefix .    ($index + 1));
                if($data) {
                    return $data;
                }
            } // END OF WHILE
            } // END OF MULTIPART
        } // END OF STRUTURE
        return false;
} // END OF FUNCTION

function rsvpmaker_relay_get_pop($list_type = '') {
global $wpdb;
//$wpdb->show_errors();

$server = get_option('rsvpmaker_discussion_server');
$recipients = array();
$vars = get_option('rsvpmaker_discussion_'.$list_type);

if(empty($vars) || empty($vars['password']))
    return;

$unsubscribed = get_option('rsvpmail_unsubscribed');
if(empty($unsubscribed)) $unsubscribed = array();

$user = $vars['user'];
$password = $vars['password'];

$html = "";

//echo '<p>Full recipeints list '.implode(', ',$recipients).'</p>';

if(isset($_GET['test']))
    mail('relay@toastmost.org','Subject',"This is a test\n\nmultiple lines of text");

# Connect to the mail server and grab headers from the mailbox
$html .= sprintf('<p>%s, %s, %s</p>',$server,$user,$password);
$mail = imap_open($server,$user,$password);
if(empty($mail))
    return 'no mail connection found';
$headers = imap_headers($mail);
if(empty($headers))
    return 'no messages found for '.$list_type.' ';

$html .= '<pre>'."Mail:\n".var_export($mail,true).'</pre>';
$html .= '<pre>'."Headers:\n".var_export($headers,true).'</pre>';

if($list_type == 'member') {
    $members = get_club_members();
    foreach($members as $member)
        $recipients[] = strtolower($member->user_email);        
    }
    elseif($list_type == 'officer') {
    //toastmasters integration
    $officers = get_option('wp4toastmasters_officer_ids');
    if(!empty($officers) && is_array($officers))
        {
            foreach($officers as $id) {
                $member = get_userdata($id);
                if($member)
                    $recipients[] = strtolower($member->user_email);
            }
        }
    }
    
$subject_prefix = $vars['subject_prefix'];
$whitelist = (empty($vars['whitelist'])) ? array() : group_emails_extract($vars['whitelist']);
$blocked = (empty($vars['blocked'])) ? array() : group_emails_extract($vars['blocked']);
$additional_recipients = group_emails_extract($vars['additional_recipients']);

if(!empty($additional_recipients))
foreach($additional_recipients as $email)
    {
        if(!in_array($email,$recipients))
        $recipients[] = $email;
    }

if(empty($recipients)) {
    $html .= 'No recipients identified';
    return $html;
}

# loop through each email

//$html .= var_export($headers,true);

for ($n=1; $n<=count($headers); $n++) {
    $html .=  "<h3>".$headers[$n-1]."</h3><br />";
$realdata = '';
$headerinfo = imap_headerinfo($mail,$n);
$html .= '<pre>'."Header Info:\n".htmlentities(var_export($headerinfo,true)).'</pre>';

$subject = '';
if(!empty($headerinfo->subject))
    $subject = $headerinfo->subject;
elseif(!empty($headerinfo->Subject))
    $subject = $headerinfo->Subject;
if(!strpos($subject,$subject_prefix.']'))
    $subject = '['.$subject_prefix.'] '.$subject;

$fromname = $headerinfo->from[0]->personal;
$from = strtolower($headerinfo->from[0]->mailbox.'@'.$headerinfo->from[0]->host);

if(in_array($from,$recipients))
    $html .= '<p>'.$from.' is a member email</p>';
else
    $html .= '<p>'.$from.' is <strong>NOT</strong> a member email</p>';

$html .= var_export($headerinfo->from,true);

$html .= '<h3>'.$subject.'<br />'.$fromname.' '.$from.'</h3>';

$mailqtext = get_part($mail,$n,"TEXT/PLAIN");
$mailq = get_part($mail,$n,"TEXT/HTML");
$member_user = get_user_by('email',$from);
$author = ($member_user && !empty($member_user->ID)) ? $member_user->ID : 1;
$qpost = array('post_title' => $subject,'post_type' => 'rsvpemail', 'post_status' => 'draft','post_author' => $author);
if($mailq)
    {
        $html .= '<p>Capturing HTML email content</p>';
        $mailq = preg_replace('/<img.+cid:[^>]+>/','IMAGE OMMITTED (see attachments, below)',$mailq);
        $html .= $mailq;
        $qpost['post_content'] = $mailq;
    }
else {
    $html .= '<p>Capturing TEXT email content</p>';
    $temp = wpautop($mailqtext);
    $qpost['post_content'] = $temp;
    $html .= $temp;
}

$struct = imap_fetchstructure($mail,$n);
$contentParts = count($struct->parts);
$upload_dir = wp_upload_dir();
$path = $upload_dir['path'];
$urlpath = $upload_dir['url'];
$atturls = array();

if ($contentParts >= 2) {
    for ($i=2;$i<=$contentParts;$i++) {
    $attachment = imap_bodystruct($mail,$n,$i);
    if(strpos($attachment->parameters[0]->value,'.')) { //if it's a filename
    $atturls[] = rsvpmaker_relay_save_attachment($attachment,$i,$n,$mail,$path,$urlpath);
    if(isset($_GET['debug']))
        $html .= sprintf('<pre>%s</pre>',var_export($attachment,true));
    }

    }
}

if(in_array($from,$blocked))
{
    $rmail['subject'] = 'BLOCKED '.$qpost['post_title'];
    $rmail['to'] = $from;
    $rmail['html'] = '<p>Your message was not delivered to the email list.</p>';
    $rmail['from'] = get_option('admin_email');
    $rmail['fromname'] = get_option('blogname');
    update_option('rsvpmaker_relay_latest_bounce',var_export($rmail,true));
    rsvpmailer($rmail);
}
elseif(in_array($from,$recipients) || in_array($from,$whitelist))
{
    $qpost['post_content'] .= "\n<p>*****</p>".sprintf('<p>Relayed from the <a href="mailto:%s" target="_blank">%s</a> email list</p><p>Replies will go to SENDER. <a target="_blank" href="mailto:%s?subject=Re:%s">Reply to list instead</a></p>',$user,$user,$user,$subject);
    if (sizeof($atturls) > 0) {
        $qpost['post_content'] .= '<p>Attachments: <br />'.implode("<br />", $atturls)."</p>";
    }

    $post_id = 0;
    if(!empty($qpost['post_content']) && !empty($from))
        $post_id = wp_insert_post($qpost);
    $html .= var_export($qpost,true);
    if($post_id) {
        add_post_meta($post_id,'rsvprelay_from',$from);
        if(empty($fromname))
            $fromname = $from;
        add_post_meta($post_id,'rsvprelay_fromname',$fromname);
        add_post_meta($post_id,'rsvprelay_attpath',$attpath);
        if(!empty($recipients))
        foreach($recipients as $to) {
            if(!in_array($to,$unsubscribed))
                add_post_meta($post_id,'rsvprelay_to',$to);        
        }
    }    
}
else {
    $rmail['subject'] = 'NOT DELIVERED '.$qpost['post_title'];
    $rmail['to'] = $from;
    $rmail['html'] = '<p>Your message was not delivered because it did not come from a recognized member email address.</p><p>Reply if you also use an alternate email address that needs to be added to our whitelist.</p>';
    $rmail['from'] = get_option('admin_email');
    $rmail['fromname'] = get_option('blogname');
    update_option('rsvpmaker_relay_latest_bounce',var_export($rmail,true));
    rsvpmailer($rmail);
}

}

$limit = count($headers)+1;
for ($n=0; $n<=$limit; $n++) {
$html .= sprintf('<p>Delete %s</p>',$n);
imap_delete($mail,$n);
}
imap_expunge($mail);
$html .= '<p>Expunge deleted messages</p>';
return $html;
//end function rsvpmaker_relay_get_pop() {  
}

function rsvpmaker_relay_save_attachment($att,$file,$msgno,$mbox, $path,$urlpath) {
        $strFileName = $att->parameters[0]->value;
        $p = explode('.',$strFileName);
        $strFileType = strtolower(array_pop($p));
        $allowed = array('doc','docx','xls','xlsx','ppt','pptx','pdf','jpg','jpeg','gif','png','svg','ics','ifb','txt');
        if(!in_array($strFileType,$allowed))
            return $strFileName.' (file type not supported: '.$strFileType.')';
        $fileSize = $att->bytes;
        $fileContent = imap_fetchbody($mbox,$msgno,$file);
        $ContentType = 'application/octetstream';

        if ($strFileType == "txt")
            $ContentType = "text/plain";
        if (($strFileType == "ics") || ($strFileType == "ifb"))
            $ContentType = "text/calendar";
    
    if(isset($_GET['debug']))
    printf('<p>type: %s %s %s</p>',$ContentType,$strFileName,$fileSize);
    $writepath = $path .'/'. $strFileName;
    $url = $urlpath.'/'.$strFileName;
    if (substr($ContentType,0,4) == "text") {
     $content = imap_qprint($fileContent);
     } else {
     $content = imap_base64($fileContent);
     }
     file_put_contents($writepath, $content);
     if(isset($_GET['debug']))
     printf('<p>Writing to %s <a href="%s" target="_blank">%s</a></p>',$writepath,$url,$url);
     $link = sprintf('<a href="%s" target="_blank">%s</a>',$url,$strFileName);
     return $link;
}
