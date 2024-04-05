<?php
add_action('init', 'rsvpevent_to_email' );
add_action('init', 'rsvpmaker_init_router' );
add_action('init', 'rsvp_options_defaults', 1 );
add_action('init','rsvpmail_unsubscribe');
add_action('init','rsvpmail_confirm_subscribe');
//add_action('plugins_loaded','rsvpmail_list_rsvpmodal_controller',1); //trying to get this earlier than init
add_action('init', 'remove_save_content_filters', 99 );
add_action('init','rsvpmaker_create_nonce',1);
add_action('init','rsvphoney_login',1);

add_action('admin_init','rsvpmaker_queue_post_type');

add_action( 'admin_bar_menu', 'toolbar_rsvpmaker', 99 );

add_action( 'admin_enqueue_scripts', 'rsvpmaker_admin_enqueue' );
add_action( 'admin_head', 'rsvpmaker_template_admin_title' );

add_action('admin_init', 'rsvpmaker_plugin_add_privacy_policy_content' );
add_action('admin_init', 'rsvpmaker_template_checkbox_post' );

add_action('admin_init', 'rsvpmaker_add_one' );
add_action('admin_init', 'rsvpmaker_editors' );
add_action('admin_init', 'add_rsvpemail_caps' );
add_action('admin_init', 'rsvp_csv' );
add_action('admin_init', 'additional_editors_setup' );
//add_action('admin_init', 'rsvpmaker_setup_post' );
add_action('admin_init', 'add_rsvpemail_caps' );

add_action( 'admin_menu', 'my_events_menu' );
add_action( 'admin_menu', 'my_rsvpemails_menu' );
//todo checkthis?
add_action( 'admin_menu', 'my_rsvpemail_menu' );
add_action( 'admin_menu', 'rsvpmaker_admin_menu' );

add_action( 'admin_notices', 'rsvpmaker_admin_notice' );

add_action( 'current_screen', 'rsvp_print', 999 );
add_action( 'export_wp', 'export_rsvpmaker' );
add_action( 'import_end', 'import_rsvpmaker' );
add_action( 'log_paypal', 'log_paypal' );
add_action( 'manage_posts_extra_tablenav', 'rsvpmaker_sort_message' );
//add_action( 'pre_get_posts', 'rsvpmaker_archive_pages' );
add_action( 'plugins_loaded', 'rsvpmaker_load_plugin_textdomain' );

add_action( 'rsvp_daily_reminder_event', 'rsvp_daily_reminder' );
add_action( 'rsvpmaker_cron_email_preview', 'rsvpmaker_cron_email_preview',10,3 );
add_action( 'rsvpmaker_cron_email', 'rsvpmaker_cron_email_send',10,3 );

add_action( 'rsvpmaker_email_list_okay', 'rsvpmaker_email_list_okay', 10, 1 );
add_action( 'rsvpmaker_replay_email', 'rsvpmaker_replay_email', 10, 3 );
add_action( 'rsvpmaker_send_reminder_email', 'rsvpmaker_send_reminder_email', 10, 2 );

add_action( 'save_post', 'rsvpmaker_save_calendar_data' );
// stripe
add_action( 'sc_after_charge', 'rsvpmaker_sc_after_charge' );

add_action( 'template_redirect', 'rsvpmaker_email_template_redirect' );

add_action('post_updated', function($post_id, $post_after, $post_before) {
global $wpdb;
if('rsvpemail' == $post_after->post_type) {
	$html = rsvpmaker_email_html($post_after); //update the styled html metadata
}
if(('rsvpmaker' == $post_after->post_type) && $post_after->post_title != $post_before->post_title) {
	$table = get_rsvpmaker_event_table();
	$wpdb->query($wpdb->prepare("update $table SET post_title=%s where event=%d",$post_after->post_title,$post_after->ID)); //keep title in sync
}

},10,3);

add_action( 'user_register', 'RSVPMaker_register_chimpmail' );
add_action(
	'widgets_init',
	function() {
		return register_widget( 'CPEventsWidget' );
	}
);
add_action(
	'widgets_init',
	function() {
		return register_widget( 'RSVPTypeWidget' );
	}
);
add_action(
	'widgets_init',
	function() {
		return register_widget( 'RSVPMakerByJSON' );
	}
);
add_action('wp', 'clear_rsvp_cookies' );
add_action('wp', 'rsvp_reminder_activation' );

add_action( 'wp_enqueue_scripts', 'rsvpmaker_event_scripts', 10000 );

// make sure new rules will be generated for custom post type - flush for admin but not for regular site visitors
if ( ! isset( $rsvp_options['flush'] ) ) {
	add_action('admin_init', 'flush_rewrite_rules' );
}
if ( ! isset( $rsvp_options['flush'] ) ) {
	add_action('admin_init', 'flush_rewrite_rules' );
}

if ( isset( $_GET['clean_duplicate_dates'] ) ) {
	add_action('init', 'rsvpmaker_duplicate_dates' );
}

if ( isset( $_GET['ical'] ) ) {
	add_action( 'wp', 'rsvpmaker_to_ical' );
}
if ( isset( $rsvp_options['social_title_date'] ) && $rsvp_options['social_title_date'] ) {
	add_action( 'wp_head', 'rsvpmaker_facebook_meta', 999 );
}
if ( isset( $_GET['rsvp_reminders'] ) ) {
	add_action( 'wp_print_scripts', 'rsvpmaker_dequeue_script', 100 );
}
if ( isset( $rsvp_options['dashboard'] ) && ! empty( $rsvp_options['dashboard'] ) ) {
	add_action( 'wp_dashboard_setup', 'rsvpmaker_add_dashboard_widgets' );
}

add_action( 'wp_ajax_rsvpmaker_date', 'ajax_rsvpmaker_date_handler' );
add_action( 'wp_ajax_rsvpmaker_meta', 'ajax_rsvpmaker_meta_handler' );
add_action( 'wp_ajax_rsvpmaker_dateformat', 'ajax_rsvpmaker_dateformat_handler' );
add_action( 'wp_ajax_rsvpmaker_dismissed_notice_handler', 'rsvpmaker_ajax_notice_handler' );
add_action( 'wp_ajax_rsvpmaker_template', 'ajax_rsvpmaker_template_handler' );

add_action('init', 'rsvpmaker_submission_post' );

add_action( 'wp_login', 'rsvpmaker_data_check' );
add_action( 'quick_edit_custom_box', 'rsvpmaker_quick_edit_fields', 10, 2 );
add_action( 'manage_posts_custom_column', 'rsvpmaker_custom_column', 99, 2 );
add_action( 'manage_posts_custom_column', 'rsvpmaker_template_custom_column', 99, 2 );
add_action( 'save_post', 'rsvpmaker_quick_edit_save', 1 );

function rsvpmaker_init_router() {
	add_rsvpmaker_roles();
	rsvpmaker_create_post_type();
	create_rsvpemail_post_type();
	if ( isset( $_GET['rsvpmaker_cron_email_preview'] ) ) {
		previewtest();// email preview
	}
	rsvp_options_defaults();
	rsvpmaker_localdate();
	if ( isset( $_GET['rsvpmaker_placeholder'] ) ) {
		rsvpmaker_placeholder_image();
	}
	if ( isset( $_POST['replay_rsvp'] ) ) {
		save_replay_rsvp();
	}
	if ( isset( $_POST['yesno'] ) || isset( $_POST['withdraw'] ) ) {
		save_rsvp();
	}
	if ( isset( $_GET['show_rsvpmaker_included_styles'] ) ) {
		show_rsvpmaker_included_styles();
	}
}

add_action('admin_init', 'rsvpmaker_filter_debug' );

function rsvpmaker_filter_debug() {
	if ( ! isset( $_GET['filter_debug'] ) ) {
		return;
	}
	global $wp_filter;
	echo '<pre>';
	// print_r($wp_filter);
	echo '</pre>';
	exit;
}

add_filter('get_the_excerpt','rsvpmaker_excerpt_filter',1);

add_filter('wp_headers','rsvpmaker_headers');

function rsvpmaker_headers($headers) {
	rsvpmail_list_rsvpmodal_controller();//get cookies in at same time as standard headers
	return $headers;
}