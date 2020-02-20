<?php
function contributor_replyto() {
global $current_user;
global $rsvp_options;
$rsvp_options["rsvp_to"] = $current_user->user_email;
}
add_action('admin_init','contributor_replyto');

function ajax_guest_lookup() {
if(!isset($_GET["ajax_guest_lookup"]))
return;
$event = $_GET["ajax_guest_lookup"];
global $wpdb;
$sql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE event=$event AND yesno=1 ORDER BY id DESC";
$attendees = $wpdb->get_results($sql);
echo '<div class="attendee_list">';
foreach($attendees as $row)
{
?>
<h3 class="attendee"><?php echo $row->first;?> <?php echo $row->last;?></h3>
<?php
//print_r($row);
if($row->details)
{
$detailsarray = unserialize($row->details);
if($detailsarray["roommate"])  {
$roommate = htmlentities(strip_tags($detailsarray["roommate"]));
echo "<p>Roommate: $roommate</p>";
}
}
if($row->note);
echo wpautop($row->note);
}
echo '</div>';
exit();
}

function rsvpmaker_excerpt ($atts)
{

$no_events = (isset($atts["no_events"]) && $atts["no_events"]) ? $atts["no_events"] : 'No events currently listed.';

global $post;
global $wp_query;
global $wpdb;

$backup = $wp_query;

add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_where', 'rsvpmaker_where' );
add_filter('posts_groupby', 'rsvpmaker_groupby' );
add_filter('posts_orderby', 'rsvpmaker_orderby' );
add_filter('posts_distinct', 'rsvpmaker_distinct' );
remove_filter('the_content','event_content',5);

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$querystring = "post_type=rsvpmaker&post_status=publish&paged=$paged";
if(isset($atts["type"]))
	$querystring .= "&rsvpmaker-type=".$atts["type"];
if(isset($atts["limit"]))
	$querystring .= "&posts_per_page=".$atts["limit"];
if(isset($atts["add_to_query"]))
	{
		if(!strpos($atts["add_to_query"],'&'))
			$atts["add_to_query"] = '&'.$atts["add_to_query"];
		$querystring .= $atts["add_to_query"];
	}

$wp_query = new WP_Query($querystring);

// clean up so this doesn't interfere with other operations
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_where', 'rsvpmaker_where' );
remove_filter('posts_groupby', 'rsvpmaker_groupby' );
remove_filter('posts_orderby', 'rsvpmaker_orderby' );
remove_filter('posts_distinct', 'rsvpmaker_distinct' );

ob_start();
	
if ( have_posts() ) {
while ( have_posts() ) : the_post();

$sql = "SELECT * FROM ".$wpdb->prefix."rsvp_dates WHERE postID=".$post->ID.' ORDER BY datetime';
$results = $wpdb->get_results($sql,ARRAY_A);
if($results)
{
$dateblock = '';
foreach($results as $row)
	{
	$t = rsvpmaker_strtotime($row["datetime"]);
	if(!empty($dateblock))
		$dateblock .= ', ';
	$dateblock .= rsvpmaker_date('F jS',$t);
	}
}
?>

<div id="post-<?php the_ID();?>" <?php post_class();?> >
<h3 class="entry-title"><a href="<?php the_permalink(); ?>" ><?php the_title(); echo ' - '.$dateblock; ?></span></a></h3>
<div class="entry-content">
<?php the_excerpt(); ?>
</div><!-- .entry-content -->
</div>
<?php
endwhile;
} 
else
	echo "<p>$no_events</p>\n";
$wp_query = $backup;

add_filter('the_content','event_content',5);

wp_reset_postdata();

return ob_get_clean();

}

add_shortcode("rsvpmaker_excerpt","rsvpmaker_excerpt");

if ( ! wp_next_scheduled( 'rsvp_cleanup_hook' ) ) {
  wp_schedule_event( time(), 'daily', 'rsvp_cleanup_hook' );
}

add_action( 'rsvp_cleanup_hook', 'rsvp_cleanup_function' );

function rsvp_cleanup_function() {
echo "starting cleanup<br />";
global $wpdb;
$wpdb->show_errors();
$wpdb->query("DELETE from wp_posts where post_author=3 AND (post_status='pending' OR post_status='draft') ");
$wpdb->query("DELETE `wp_rsvp_dates`.* FROM `wp_rsvp_dates` LEFT JOIN wp_posts ON `wp_rsvp_dates`.postID=wp_posts.ID WHERE wp_posts.ID IS NULL");


if(date('w') == '0')
	{
global $post;
global $wp_query;
$backup = $wp_query;
add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_where', 'rsvpmaker_where' );
add_filter('posts_groupby', 'rsvpmaker_groupby' );
add_filter('posts_orderby', 'rsvpmaker_orderby' );
add_filter('posts_distinct', 'rsvpmaker_distinct' );
add_filter('posts_fields', 'rsvpmaker_select' );
$querystring = "post_type=rsvpmaker&post_status=publish&paged=$paged";
$querystring .= "&rsvpmaker-type=featured";
$wp_query = new WP_Query($querystring);
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_where', 'rsvpmaker_where' );
remove_filter('posts_groupby', 'rsvpmaker_groupby' );
remove_filter('posts_orderby', 'rsvpmaker_orderby' );
remove_filter('posts_distinct', 'rsvpmaker_distinct' );
remove_filter('posts_fields', 'rsvpmaker_select' );
if ( have_posts() ) {
while ( have_posts() ) : the_post();
	$dates = get_rsvp_dates($post->ID);
	foreach($dates as $datearr)
		{
			$meta_id = $datearr["meta_id"];
			$newdate = rsvpmaker_date('Y-m-d H:i:s',rsvpmaker_strtotime($datearr["datetime"] . ' +1 week'));
			$duration = (empty($datearr["duration"]) || ($datearr["duration"] == 'allday') ) ? $datearr["duration"] : rsvpmaker_date('Y-m-d H:i:s',rsvpmaker_strtotime($datearr["duration"] . ' +1 week'));
   			$wpdb->query("update `wp_postmeta` SET meta_value = '$newdate' WHERE meta_id=".$meta_id);
			if(!empty($duration))
				{
				add_post_meta($post->ID, '_'.$newdate, $duration);
				delete_post_meta($post->ID, '_'.$datearr["datetime"]);
				}
		}
endwhile;
}
$wp_query = $backup;
wp_reset_postdata();

	}
}

function cleanup() {
if(isset($_GET["rsvp_cleanup"]))
	rsvp_cleanup_function();

if(isset($_GET["see_pending"]))
	{
	global $wpdb;
	$row = $wpdb->get_row("SELECT ID from wp_posts where post_status='pending'");
	$post = get_post($row->ID);
	print_r($post);
	exit();
	}
}
add_action('admin_init','cleanup');

function stop_access_profile() {
if( current_user_can('publish_posts') || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
	return;

    if(IS_PROFILE_PAGE === true) {
        wp_die( 'Demo user cannot update profile.' );
    }

    remove_menu_page( 'profile.php' );
    remove_submenu_page( 'users.php', 'profile.php' );
}
add_action( 'admin_init', 'stop_access_profile' );

//add_filter("login_redirect", "rsvpmaker_login_redirect", 99, 3);

/*
function rsvpmaker_login_redirect($redirect_to, $request, $user){

mail("david@carrcommunications.com","rsvpmaker redirect",$redirect_to.", request:".$request);

	if(strpos($redirect_to, '-admin') )
		return '/wp-admin/';
	else
		return $redirect_to;
}
*/

function rebuild_mailchimp () {
global $wpdb;

$msg = '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Email</title>
</head>
<body>
<p>I am reaching out to people who have commented or asked questions on the RSVPMaker.com blog or website to ask you to sign up for my email list <strong>AND RECONFIRM IF YOU WERE ON IT PREVIOUSLY</strong>. I also want to let you know about a webinar coming up in a few weeks.</p>

<p>This is embarrassing for someone who is in the Mailchimp developer program, but I am having to go through the process of rebuilding my email list because they got too many bounce messages from my last couple of email campaigns. Partly, that may just have been because I had not been sending to it regularly, causing the list to "go stale."</p>

<p>At any rate, if you do want to receive regular updates about improvements to the plugin, you will need to visit the <a href="http://rsvpmaker.com/newsletter-signup/">mailing list signup page</a>, fill out the form, and respond to the message they send you confirming that you really, truly do want to be on the email list. I will not keep bugging you if you choose not to respond.</p>

<p>RSVPMaker has gotten a number of upgrades recently, and I am working on several improvements including an overhaul of the PayPal payment functions. That was the motivation for starting a webinar series, the next episode of which is described below. Hope to see you there.</p>

<p>&mdash; David F. Carr, author of RSVPMaker, President, <a href="http://www.carrcommunications.com">Carr Communications Inc.</a></p>

<div class="rsvpmaker_upcoming">
<div id="post-114893" class="post-114893 rsvpmaker type-rsvpmaker status-publish hentry rsvpmaker-type-webinar" itemscope itemtype="http://schema.org/Event" >  
<h1 class="entry-title"><a href="http://rsvpmaker.com/rsvpmaker/webinar-rsvpmaker-event-management-for-wordpress-february-24-at-7-pm-est-2016-2-24/"  itemprop="url"><span itemprop="name">Webinar: RSVPMaker Event Management for WordPress, February 24 at 7 pm EST</span></a></h1>
<div class="entry-content">

<div class="dateblock">
<h3 itemprop="startDate" datetime="2016-02-24T19:00:00-05:00">Wednesday February 24th, 2016 7:00 PM EST</h3>
</div>
<p>Join me for this webinar on how to promote and manage events using the combination of WordPress and the RSVPMaker plugin. As the name implies, you can also collect RSVPs (registrations) and PayPal payments. You can also send automated event reminders to make sure the people who register actually attend.</p>
<p>I&#8217;ll cover some new features, such as the improved event reminders system and the specific support for organizing webinars (like this one) taking advantage of Google&#8217;s Hangouts on Air free online events platform. You will learn how to create and manage events, set RSVP options, customize the RSVP form, and work with templates for recurring events.</p>
<p>In addition, the webinar will at least touch on some more advanced topics such as configuring PayPal payments and adding your own customizations. The webinar format will allow for Q&amp;A, so bring your questions, and I&#8217;ll answer them on air.
<p class="signed_up">2 signed up so far.</p>
<p><a style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;" class="rsvplink" href="http://rsvpmaker.com/rsvpmaker/webinar-rsvpmaker-event-management-for-wordpress-february-24-at-7-pm-est-2016-2-24/?e=*|EMAIL|*#rsvpnow">RSVP Now!</a></p>

</div><!-- .entry-content -->

<div class="event_author">Posted by <span class="author vcard"><a class="url fn n" href="http://rsvpmaker.com/blog/author/david/" title="View all posts by David F. Carr">David F. Carr</a></span> on <span class="updated" datetime="2016-01-28T14:06:36-05:00">January 28, 2016</span></div>
</div>
<p><p></p></div><!-- end rsvpmaker_upcoming -->
</body>
</html>';

$mail["subject"] = 'RSVPMaker mailing list update and webinar';
$mail["html"] = $msg;
$mail["from"] = 'david@rsvpmaker.com';
$mail["fromname"] = get_bloginfo('name');
//$mail["cc"] = 'david@carrcommunications.com';

$results = $wpdb->get_results('SELECT * 
FROM  `mailchimp_reboot` 
WHERE done =0
LIMIT 0 , 10');

$log = '';

foreach($results as $row)
{
	$notify = $row->email;
	$mail["to"] = $notify;
	rsvpmailer($mail);
	$wpdb->query("update `mailchimp_reboot` set done=1 WHERE email='".$row->email."'");
	$log .= $notify."\n";
}

if(empty($log))
	mail("david@carrcommunications.com","DONE: RSVPMaker mailchimp reconfirm",$log);
else
	mail("david@carrcommunications.com","RSVPMaker mailchimp reconfirm",$log);

}

if(isset($_GET["mailchimp_redo"]))
	add_action('init','rebuild_mailchimp');

function rsvmpmaker_spam_check ($email) {
$spam = array('yahoo@yahoo.com');
if(in_array($email,$spam))
	return false;
return $email;
}

add_action('rsvmpmaker_spam_check','rsvmpmaker_spam_check');

function regex_test ($atts, $content) {
$content = wpautop(do_shortcode($content));
preg_match_all('/<(.[^>]*)>([^<]*)/', $content, $matches);

$inline_tags = array('p' => 'color: red;','div' => 'border: thin solid #555;');
$inline_class = array('alignright' => 'float: right; padding: 5px; background-color: #EFEFEF;','alignleft' => 'float: left; padding: 5px; background-color: #EFEFEF;','aligncenter' => 'margin-left: auto; margin-right: auto; background-color: #EFEFEF; padding: 5px;');

//$content .= '<br /><br /><textarea rows="40" cols="600">'.var_export($matches, true).'</textarea>';

$newcontent = '';

foreach($matches[1] as $index => $value)
	{
		$style ='';
		
		preg_match('/^[a-z]+/',$value,$tagmatch);
		if(!empty($tagmatch))
			{
				foreach($inline_tags as $tag => $tagstyle)
				{
				if($tagmatch[0] == $tag)
					$style .= $tagstyle; 
				}

				foreach($inline_class as $class => $classstyle)
				{
				if(strpos($value, $class) )
					{
					$style .= $classstyle;
					str_replace($class, '', $value);
					}
				}
			}
		if(!empty($style) && ! strpos($value,$style) )
			{ // if not already added
			if(strpos($value,'style'))
				$value = preg_replace('/style="[^"]+/',"$0".$style,$value);
			else
				$value .= ' style="'.$style.'" ';
			}		
		$newcontent .= '<'.$value.'>'.$matches[2][$index];
	}
return $newcontent;
}
add_shortcode('regex_test','regex_test');


add_filter( 'jetpack_open_graph_image_default', 'rsvpmaker_change_default_image' );
function rsvpmaker_change_default_image( $image ) {
    return 'https://rsvpmaker.com/wp-content/uploads/2016/11/calendarbg4.jpg';
}

add_action('upside_down_promo','webinar_promo');

function webinar_promo () {

$next = rsvpmaker_next(array('hide_title' => 1, 'type' => 'webinar'));
if(empty($next) )
	return;

if( !is_front_page() || !isset($_COOKIE['rsvp_for_115456']) || is_user_logged_in() )
{
?>
<div id="call-to-action">
<div id="call-to-action-main">

<h1 >Register for the RSVPMaker Webinar</h1>
<?php
echo $next;
?>
</div>
<p><a id="bypass-promo" href="#site-navigation"><?php _e( 'Skip to menu', 'twentysixteen' ); ?></a></p>
</div>
<?php
}

}

function ssl_fix () {
$home = get_option('home');
$siteurl = get_option('siteurl');
global $wpdb;
$wpdb->show_errors();
if(strpos($home,'tp:'))
	{
	$newhome = str_replace('tp:','tps:',$home);
	update_option('home',$newhome);
	update_option('siteurl',$newhome);
	$ssl_replace = true;
	}
elseif(isset($_GET["ssl_replace"]))
	{
		$newhome = $home;
		$home = str_replace('s://','://',$home);
		$ssl_replace = true;
	}
else
		$ssl_replace = false;

if($ssl_replace)
	{

	$nonssl = array($home,'http://demo.toastmost.org','http://wp4toastmasters.com');
	$ssl = array($newhome,'https://demo.toastmost.org','https://wp4toastmasters.com');
	$sql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%tp\:%'";
	$results = $wpdb->get_results($sql);
	foreach($results as $row)
		{
			$content = $row->post_content;
			if(strpos($content,'tp:'))
				{
					$content = str_replace($home,$newhome,$content);
					$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_content=%s WHERE ID=%d",$content, $row->ID);
					//echo $sql;
					$wpdb->query($sql);
				}
		}
/*
	$sql = "SELECT option_id, option_value FROM $wpdb->options WHERE `option_value` LIKE '%tp\:%'";
	$results = $wpdb->get_results($sql);
	foreach($results as $row)
		{
			$content = $row->option_value;
			if(strpos($content,'tp:'))
				{
					$content = str_replace($home,$newhome,$content);
					$sql = $wpdb->prepare("UPDATE $wpdb->options SET option_value=%s WHERE option_id=%d",$content, $row->option_id);
					$wpdb->query($sql);
				}
		}
*/

	}
}

add_action('admin_init','ssl_fix');

if(!wp_is_json_request())
add_shortcode('rsvpmaker_shortcode_demo','rsvpmaker_shortcode_demo');

function rsvpmaker_shortcode_demo ($atts) {
$output = '';
$where = (isset($atts["where"])) ? $atts["where"] : '';
$limit = (isset($atts["limit"])) ? $atts["limit"] : 5;
$future = (isset($atts["past"])) ? 0 : 1;
if(isset($atts["after"]))
	{
		if(!empty($where))
			$where .= ' AND ';
		if(strpos($atts["after"],'-')) // if this looks like a mysql date
			$where .= "datetime > '".$atts["after"]."'";
		else // if this looks like a relative code like NOW() or DATE_ADD
			$where .= "datetime > ".$atts["after"];		
	}
if(isset($atts["before"]))
	{
		if(!empty($where))
			$where .= ' AND ';
		if(strpos($atts["after"],'-')) // if this looks like a mysql date
			$where .= "datetime < '".$atts["after"]."'";
		else // if this looks like a relative code like NOW() or DATE_ADD
			$where .= "datetime < ".$atts["after"];		
	}
if($future)
	$events = get_future_events($where,$limit);
else
	$events = get_past_events($where,$limit);	
if(isset($atts["showfields"]))
{
	$showdata = $events[0];
	$showdata->post_content = '[post content goes here]';
	return '<blockquote>'.var_export($showdata,true).'</blockquote>';
}	
foreach($events as $index => $event)
	{
		$style = ($index && ($index % 2)) ? 'background-color: #fee; padding: 10px;' : 'background-color: #eef; ; padding: 10px;';
		$output .= sprintf('<p style="%s">%s<br /><a href="%s">%s</a></p>',$style,$event->date,get_permalink($event->ID),$event->post_title);
	}
return $output;
}

function hide_youtube ()
	{
		if(isset($_GET["hide_youtube"]))
		remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
	}
add_action('init','hide_youtube');

function rsvpmaker_utilities_test () {
$output = '';
$events = get_future_events('post_author=1',10);
foreach($events as $event)
	{
	$output .= sprintf('<p><a href="%s">%s %s</a></p>',get_permalink($event->ID),$event->post_title,$event->date);
	}
return $output;
}
add_shortcode('rsvpmaker_utilities_test','rsvpmaker_utilities_test');

function time_test() {
$t1 = get_the_time( 'U' );
$t2 = get_the_modified_time( 'U' );
return $t1 .' / '. $t2;
}

add_shortcode('time_test','time_test');

add_action( 'admin_bar_menu', 'add_updates_to_admin_bar',999 );

function add_updates_to_admin_bar($admin_bar) {
$args = array('parent' => 'network-admin',
'id' => 'updates','title' =>'Updates','href'=>network_admin_url('update-core.php'),'meta'=>false);
$admin_bar->add_node( $args );
}
?>