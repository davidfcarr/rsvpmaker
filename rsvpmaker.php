<?php
/*
* Plugin Name: RSVPMaker
* Plugin URI: http://www.rsvpmaker.com
* Description: Schedule events, send invitations to your mailing list and track RSVPs. You get all your familiar WordPress editing tools with extra options for setting dates and RSVP options. Online payments with PayPal or Stripe can be added with a little extra configuration. Email invitations can be sent through MailChimp or to members of your website community who have user accounts. Recurring events can be tracked according to a schedule such as "First Monday" or "Every Friday" at a specified time, and the software will calculate future dates according to that schedule and let you track them together. <a href="options-general.php?page=rsvpmaker-admin.php">Options</a>
* Author: David F. Carr
* Author URI: http://www.carrcommunications.com
* Text Domain: rsvpmaker
* Domain Path: /translations
* Requires at least: 5.2
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Version: 11.6
*/

function get_rsvpversion() {
	return '11.6'; 
}

global $wp_version;
global $default_tz;
global $rsvpmaker_event;
global $rsvpmakers;
$default_tz = date_default_timezone_get();

if ( version_compare( $wp_version, '3.0', '<' ) ) {
	exit( __( 'RSVPmaker plugin requires WordPress 3.0 or greater', 'rsvpmaker' ) );
}

function rsvpmaker_load_plugin_textdomain() {
	load_plugin_textdomain( 'rsvpmaker', false, basename( dirname( __FILE__ ) ) . '/translations/' );
}

global $rsvp_options;

$rsvp_options = get_option( 'RSVPMAKER_Options' );

$locale = get_locale();

function rsvp_options_defaults() {

	global $rsvp_options;

	if ( empty( $rsvp_options ) ) {
		$rsvp_options = array();
	}

	// defaults

	$rsvp_defaults = array(
		'menu_security'                     => 'manage_options',

		'rsvpmaker_template'                => 'publish_rsvpmakers',

		'recurring_event'                   => 'publish_rsvpmakers',

		'multiple_events'                   => 'publish_rsvpmakers',

		'documentation'                     => 'edit_rsvpmakers',

		'calendar_icons'                    => 1,

		'social_title_date'                 => 1,

		'default_content'                   => '',

		'rsvp_to'                           => get_bloginfo( 'admin_email' ),

		'confirmation_include_event'        => 0,

		'rsvpmaker_send_confirmation_email' => 1,

		'rsvp_instructions'                 => '',

		'rsvp_count'                        => 1,

		'rsvp_count_party'                  => 1,

		'rsvp_yesno'                        => 1,

		'send_payment_reminders'            => 1,
		'cancel_unpaid_hours'            => 0,

		'rsvp_on'                           => 0,

		'rsvp_max'                          => 0,

		'login_required'                    => 0,

		'rsvp_captcha'                      => 0,

		'show_attendees'                    => 0,

		'convert_timezone'                  => 0,

		'add_timezone'                      => 0,

		'rsvp_form_title'                   => __( 'RSVP Now!', 'rsvpmaker' ),

		'defaulthour'                       => 19,

		'defaultmin'                        => 0,

		'long_date'                         => 'l F j, Y',

		'short_date'                        => 'M j',

		'time_format'                       => 'g:i A',

		'smtp'                              => '',

		'paypal_currency'                   => 'USD',

		'currency_decimal'                  => '.',

		'currency_thousands'                => ',',

		'payment_minimum'                   => '5.00',

		'paypal_invoiceno'                  => 1,

		'stripe'                            => 0,

		'show_screen_recurring'             => 0,

		'show_screen_multiple'              => 0,

		'dashboard_message'                 => '',

		'rsvpmaker_send_confirmation_email' => 1,

		'update_rsvp'                       => __( 'Update RSVP', 'rsvpmaker' ),

	);

	$rsvp_defaults = apply_filters( 'rsvpmaker_defaults', $rsvp_defaults );

	foreach ( $rsvp_defaults as $index => $value ) {
		if ( ! isset( $rsvp_options[ $index ] ) ) {
			$rsvp_options[ $index ] = $rsvp_defaults[ $index ];
		}
	}
	$rsvp_options['rsvplink'] = get_rsvp_link();
	$rsvp_options['rsvplink_edit'] = admin_url('post.php?action=edit&post='.get_option('rsvpmaker_link_template_post'));

	if ( empty( $rsvp_options['long_date'] ) || ( strpos( $rsvp_options['long_date'], '%' ) !== false ) ) {

		$rsvp_options['long_date'] = 'l F j, Y';

		$rsvp_options['short_date'] = 'M j';

		$rsvp_options['time_format'] = 'g:i A';

		update_option( 'RSVPMAKER_Options', $rsvp_options );
	}

	if ( isset( $rsvp_options['rsvp_to_current'] ) && $rsvp_options['rsvp_to_current'] && is_user_logged_in() ) {

		global $current_user;

		$rsvp_options['rsvp_to'] = $current_user->user_email;

	}

	if ( empty( $rsvp_options['rsvp_form'] ) || isset( $_GET['reset_form'] ) ) {

		if ( function_exists( 'do_blocks' ) && ! class_exists( 'Classic_Editor' ) ) {

			$form = '<!-- wp:rsvpmaker/formfield {"label":"First Name","slug":"first","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Last Name","slug":"last","guestform":true,"sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Email","slug":"email","sluglocked":true,"required":"required"} /-->

<!-- wp:rsvpmaker/formfield {"label":"Phone","slug":"phone"} /-->

<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} /-->

<!-- wp:rsvpmaker/guests -->

<div class="wp-block-rsvpmaker-guests"><!-- wp:paragraph -->

<p></p>

<!-- /wp:paragraph --></div>

<!-- /wp:rsvpmaker/guests -->

<!-- wp:rsvpmaker/formnote /-->';

		} else {

			$form = '<p><label>' . __( 'Email', 'rsvpmaker' ) . ':</label> [rsvpfield textfield="email" required="1"]</p>

		<p><label>' . __( 'First Name', 'rsvpmaker' ) . ':</label> [rsvpfield textfield="first" required="1"]</p>

		<p><label>' . __( 'Last Name', 'rsvpmaker' ) . ':</label> [rsvpfield textfield="last" required="1"]</p>

		[rsvpprofiletable show_if_empty="phone"]

		<p><label>' . __( 'Phone', 'rsvpmaker' ) . ':</label> [rsvpfield textfield="phone" size="20"]</p>

		<p><label>' . __( 'Phone Type', 'rsvpmaker' ) . ':</label> [rsvpfield selectfield="phone_type" options="Work Phone,Mobile Phone,Home Phone"]</p>

		[/rsvpprofiletable]

		[rsvpguests]

		<p>' . __( 'Note', 'rsvpmaker' ) . ':<br />

		<textarea name="note" cols="60" rows="2" id="note">[rsvpnote]</textarea></p>';

		}

		$data['post_title'] = 'Form:Default';

		$data['post_content'] = $form;

		$data['post_status'] = 'publish';

		$data['post_author'] = 1;

		$data['post_type'] = 'rsvpmaker';

		$rsvp_options['rsvp_form'] = wp_insert_post( $data );

		update_post_meta( $rsvp_options['rsvp_form'], '_rsvpmaker_special', 'RSVP Form' );

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	} elseif ( ! is_numeric( $rsvp_options['rsvp_form'] ) ) {

		$data['post_title'] = 'Form:Default';

		$data['post_content'] = $rsvp_options['rsvp_form'];

		$data['post_status'] = 'publish';

		$data['post_type'] = 'rsvpmaker';

		$data['post_author'] = 1;

		$rsvp_options['rsvp_form'] = wp_insert_post( $data );

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	}

	$rsvp_defaults['rsvp_form'] = $rsvp_options['rsvp_form'];

	if ( strpos( $rsvp_options['rsvplink'], '*|EMAIL|*' ) ) {

		$rsvp_options['rsvplink'] = str_replace( '?e=*|EMAIL|*#rsvpnow', '', $rsvp_options['rsvplink'] );

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	}

	// if html removed (recover from error with sanitization on settings screen)

	if ( ! strpos( $rsvp_options['rsvplink'], '</a>' ) ) {

		$rsvp_options['rsvplink'] = '<p><a style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;" class="rsvplink" href="%s">' . __( 'RSVP Now!', 'rsvpmaker' ) . '</a></p>';

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	}

	if ( empty( $rsvp_options['rsvp_confirm'] ) ) {

		$message = '<!-- wp:paragraph -->

<p>' . __( 'Thank you!', 'rsvpmaker' ) . '</p>

<!-- /wp:paragraph -->';

		$rsvp_options['rsvp_confirm'] = wp_insert_post(
			array(
				'post_title'   => 'Confirmation:Default',
				'post_content' => $message,
				'post_status'  => 'publish',
				'post_type'    => 'rsvpemail',
				'post_parent'  => 0,
			)
		);

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	} elseif ( ! is_numeric( $rsvp_options['rsvp_confirm'] ) ) {

		$rsvp_options['rsvp_confirm'] = wp_insert_post(
			array(
				'post_title'   => 'Confirmation:Default',

				'post_content' => rsvpautog( $rsvp_options['rsvp_confirm'] ),

				'post_status'  => 'publish',
				'post_type'    => 'rsvpemail',
				'post_parent'  => 0,
			)
		);

		update_option( 'RSVPMAKER_Options', $rsvp_options );

	}

}

function rsvpmaker_defaults_for_post( $post_id ) {

	global $rsvp_options;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_on'                           => '_rsvp_on',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

	foreach ( $defaults as $index => $label ) {

		update_post_meta( $post_id, $label, $rsvp_options[ $index ] );
	}

}

function get_rsvpmaker_custom( $post_id ) {

	global $rsvp_options;

	$defaults = array(

		'calendar_icons'                    => '_calendar_icons',

		'rsvp_to'                           => '_rsvp_to',

		'rsvp_confirm'                      => '_rsvp_confirm',

		'rsvpmaker_send_confirmation_email' => '_rsvp_rsvpmaker_send_confirmation_email',

		'confirmation_include_event'        => '_rsvp_confirmation_include_event',

		'rsvp_instructions'                 => '_rsvp_instructions',

		'rsvp_count'                        => '_rsvp_count',

		'rsvp_count_party'                  => '_rsvp_count_party',

		'rsvp_yesno'                        => '_rsvp_yesno',

		'rsvp_max'                          => '_rsvp_max',

		'login_required'                    => '_rsvp_login_required',

		'rsvp_captcha'                      => '_rsvp_captcha',

		'show_attendees'                    => '_rsvp_show_attendees',

		'convert_timezone'                  => '_convert_timezone',

		'add_timezone'                      => '_add_timezone',

		'rsvp_form'                         => '_rsvp_form',

	);

	if ( strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) && ! isset( $_GET['clone'] ) ) {

		$custom['_rsvp_on'][0] = $rsvp_options['rsvp_on'];

		foreach ( $defaults as $default_key => $custom_key ) {

			$custom[ $custom_key ][0] = $rsvp_options[ $default_key ];
		}

		return $custom;

	} else {

		$custom = get_post_custom( $post_id );

		$custom['_rsvp_on'][0] = ( isset( $custom['_rsvp_on'][0] ) && $custom['_rsvp_on'][0] ) ? 1 : 0;

		foreach ( $defaults as $default_key => $custom_key ) {

			if ( ! isset( $custom[ $custom_key ][0] ) ) {

				$custom[ $custom_key ][0] = $rsvp_options[ $default_key ];
			}
		}

		return $custom;

	}

}

rsvpmaker_includes();
function rsvpmaker_includes() {
	$plugins_dir   = plugin_dir_path( __DIR__ );
	$rsvpmaker_dir = trailingslashit(plugin_dir_path( __FILE__ ));

	if ( file_exists( $plugins_dir . 'rsvpmaker-custom.php' ) ) {
		include_once $plugins_dir . 'rsvpmaker-custom.php';
	}

	include $rsvpmaker_dir . 'rsvpmaker-util.php';
	include $rsvpmaker_dir . 'rsvpmaker-types.php';
	include $rsvpmaker_dir . 'rsvpmaker-admin.php';
	include $rsvpmaker_dir . 'rsvpmaker-api-endpoints.php';
	include $rsvpmaker_dir . 'rsvpmaker-display.php';
	include $rsvpmaker_dir . 'rsvpmaker-plugabble.php';
	include $rsvpmaker_dir . 'mailchimp-api-master/src/MailChimp.php';
	include $rsvpmaker_dir . 'rsvpmaker-email.php';
	include $rsvpmaker_dir . 'rsvpmaker-privacy.php';
	include $rsvpmaker_dir . 'rsvpmaker-actions.php';
	include $rsvpmaker_dir . 'rsvpmaker-form.php';
	include $rsvpmaker_dir . 'rsvpmaker-widgets.php';
	include $rsvpmaker_dir . 'rsvpmaker-group-email.php';
	include $rsvpmaker_dir . 'script.php';
	include $rsvpmaker_dir . 'rsvpmaker-money.php';
	include $rsvpmaker_dir . 'rsvpmaker-ical.php';
	include $rsvpmaker_dir . 'rsvpmaker-postmark.php';
	include $rsvpmaker_dir . 'holidays.php';
	include $rsvpmaker_dir . '/admin/admin.php';
	//include $rsvpmaker_dir . '/upcoming/upcoming.php';
}

$gateways = get_rsvpmaker_payment_options();
if ( in_array( 'Stripe', $gateways ) ) {
	require WP_PLUGIN_DIR . '/rsvpmaker/rsvpmaker-stripe.php';
}

if ( in_array( 'PayPal REST API', $gateways ) ) {
	require WP_PLUGIN_DIR . '/rsvpmaker/paypal-rest.php';
}	

if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	include WP_PLUGIN_DIR . '/rsvpmaker/rsvpmaker-recaptcha.php';
}

// make sure new rules will be generated for custom post type - flush for admin but not for regular site visitors
function cpevent_activate() {
	global $wpdb, $rsvp_options;
	if(!$rsvp_options)
		$rsvp_options = rsvp_options_defaults();

//load dbDelta
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$sql = "CREATE TABLE `{$wpdb->prefix}rsvpmaker` (
  `id` int NOT NULL auto_increment,
  `email` varchar(255)   CHARACTER SET utf8 COLLATE utf8_general_ci  default NULL,
  `yesno` tinyint(4) NOT NULL default '0',
  `first` varchar(255)  CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL default '',
  `last` varchar(255)  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `details` text  CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `event` int NOT NULL default '0',
  `owed` float(6,2) NOT NULL default '0.00',
  `amountpaid` float(6,2) NOT NULL default '0.00',
  `fee_total` float(6,2) NOT NULL default '0.00',
  `master_rsvp` int NOT NULL default '0',
  `guestof` varchar(255)   CHARACTER SET utf8 COLLATE utf8_general_ci  default NULL,
  `note` text   CHARACTER SET  utf8 COLLATE utf8_general_ci NOT NULL,
  `participants` INT NOT NULL DEFAULT '0',
  `user_id` INT NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `{$wpdb->prefix}rsvpmaker_event` (
  `event` int NOT NULL default '0',
  `post_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  `display_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  `date` datetime,
  `enddate` datetime,
  `ts_start` int NOT NULL default '0',
  `ts_end` int NOT NULL default '0',
  `timezone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  PRIMARY KEY  (`event`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `{$wpdb->prefix}rsvp_volunteer_time` (
  `id` int NOT NULL auto_increment,
  `event` int NOT NULL default '0',
  `rsvp` int NOT NULL default '0',
  `time` int default '0',
  `user_id` int default '0',
  `participants` int NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `{$wpdb->prefix}rsvpmailer_blocked` (
`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`email` varchar(100) NOT NULL DEFAULT '',
`code` varchar(50) NOT NULL DEFAULT '',
`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY  (`ID`),
KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
$dbversion = hash('sha256', $sql);
if(!empty($rsvp_options['dbversion']) && ($dbversion == $rsvp_options['dbversion']) )
	{
		return;
	}
$rsvp_options['dbversion'] = $dbversion;

$result = dbDelta($sql);
error_log('rsvpmaker dbDelata '.var_export($result,true));
rsvpmail_problem_init();

	$sql = 'SELECT slug FROM ' . $wpdb->prefix . 'terms JOIN `' . $wpdb->prefix . 'term_taxonomy` on ' . $wpdb->prefix . 'term_taxonomy.term_id= ' . $wpdb->prefix . "terms.term_id WHERE taxonomy='rsvpmaker-type' AND slug='featured'";

	if ( ! $wpdb->get_var( $sql ) ) {

		wp_insert_term(
			'Featured', // the term
			'rsvpmaker-type', // the taxonomy
			array(

				'description' => 'Featured event. Can be used to put selected events in a listing, for example on the home page',

				'slug'        => 'featured',

			)
		);

	}
	//update dbversion
	update_option( 'RSVPMAKER_Options', $rsvp_options );
}

register_activation_hook( __FILE__, 'cpevent_activate' );

function rsvpmaker_deactivate() {

	// Unregister the post type, so the rules are no longer in memory.

	unregister_post_type( 'rsvpmaker' );

	unregister_post_type( 'rsvpemail' );

	// Clear the permalinks to remove our post type's rules from the database.

	flush_rewrite_rules();

	wp_unschedule_hook( 'rsvp_cleanup_hook' );

	wp_unschedule_hook( 'rsvpmaker_relay_init_hook' );

	wp_unschedule_hook( 'rsvpmaker_cron_email' );

	wp_unschedule_hook( 'rsvpmaker_cron_email_preview' );

	wp_unschedule_hook( 'rsvp_daily_reminder_event' );

}

register_deactivation_hook( __FILE__, 'rsvpmaker_deactivate' );

add_filter('single_template_hierarchy','rsvpmaker_single_template_hierarchy');
function rsvpmaker_single_template_hierarchy($templates) {
	global $post;
	if(isset($post->post_type) && ($post->post_type == 'rsvpmaker'))
	{
		$index = array_search('single.php',$templates);
		if($index) {
			// prefer the page template, doesn't emphasize date posted as much in most themes
			$templates[$index] = 'page.php';
			$templates[] = 'single.php';
		}
	}
	return $templates;
}

function log_paypal( $message ) {

	global $post;

	$ts = rsvpmaker_date( 'r' );

	$invoice = sanitize_text_field($_SESSION['invoice']);

	$message .= "\n<br /><br />Post ID: " . $post->ID;

	$message .= "\n<br /><br />Invoice: " . $invoice;

	$message .= "\n<br />Email: " . sanitize_text_field($_SESSION['payer_email']);

	$message .= "\n<br />Time: " . $ts;

	add_post_meta( $post->ID, '_paypal_log', $message );

}

if ( ! function_exists( 'rsvpmaker_permalink_query' ) ) {

	function rsvpmaker_permalink_query( $id, $query = '' ) {

		$key = 'pquery_' . $id;

		$p = wp_cache_get( $key );

		if ( ! $p ) {

			$p = get_permalink( $id );

			$p .= strpos( $p, '?' ) ? '&' : '?';

			wp_cache_set( $key, $p );

		}

		if ( is_array( $query ) ) {

			foreach ( $query as $name => $value ) {

				$qstring .= $name . '=' . $value . '&';
			}
		} else {

			$qstring = $query;

		}

		return $p . $qstring;

	}
} // end function exists

function format_cddate( $year, $month, $day, $hours, $minutes ) {

	$month = (int) $month;

	if ( $month < 10 ) {

		$month = '0' . $month;
	}

	$day = (int) $day;

	if ( $day < 10 ) {

		$day = '0' . $day;
	}

	return $year . '-' . $month . '-' . $day . ' ' . $hours . ':' . $minutes . ':00';

}

function add_rsvpmaker_date( $post_id, $cddate, $duration = '', $end_time = '', $index = 0, $timezone = '' ) {
	if($end_time && !strpos($end_time,'-')) {
		$parts = explode(' ',$cddate);
		$end_time = $parts[0].' '.$end_time;
	}
	add_rsvpmaker_event($post_id,$cddate,$end_time,$duration, $timezone);
}

function update_rsvpmaker_date( $post_id, $cddate, $duration = '', $end_time = '', $index = 0 ) {
	rsvpmaker_update_event_field($post_id,'date',$cddate);
	if(!strpos($end_time,'-'))
	{
		$parts = explode(' ',$cddate);
		$end_time = $parts[0].' '.$end_time;
	} 
	rsvpmaker_update_event_field($post_id,'enddate',$end_time);
	rsvpmaker_update_event_field($post_id,'display_type',$duration);
}

function rsvpmaker_upcoming_data( $atts ) {
	global $post;

	global $dataloop;

	$waspost = $post;

	$dataloop = true; // prevent ui output of More Events link

	$rsvp_query = rsvpmaker_upcoming_query( $atts );
	$events = array();

	if ( $rsvp_query->have_posts() ) {

		while ( $rsvp_query->have_posts() ) :
			$rsvp_query->the_post();
			rsvpmakers_add($post);
			$events[] = $post;
		endwhile;

	}

	wp_reset_postdata();

	$post = $waspost;

	return $events;

}

function rsvpmaker_menu_order( $menu_ord ) {

	if ( ! $menu_ord || ! is_array( $menu_ord ) ) {
		return true;
	}

	foreach ( $menu_ord as $menu_item ) {

		if ( $menu_item == 'edit.php?post_type=page' ) {

			$neworder[] = 'edit.php?post_type=page';

			$neworder[] = 'edit.php?post_type=rsvpmaker';

			$neworder[] = 'edit.php?post_type=rsvpemail';

		} elseif ( ( $menu_item == 'edit.php?post_type=rsvpmaker' ) || ( $menu_item == 'edit.php?post_type=rsvpemail' ) ) {

		} else {
				$neworder[] = $menu_item;
		}
	}

	return $neworder;

}

add_filter( 'custom_menu_order', 'rsvpmaker_menu_order' ); // Activate custom_menu_order
add_filter( 'menu_order', 'rsvpmaker_menu_order' );

function rsvpmaker_sc_after_charge( $charge_response ) {

	global $post;

	if ( $post->post_type != 'rsvpmaker' ) {

		return;
	}

	$tx_id = $charge_response->id;

	$charge = $paid = $charge_response->amount / 100;

	if ( ! isset( $_COOKIE[ 'rsvp_for_' . $post->ID ] ) ) {

		echo '<p style="color:red;">Error logging payment to RSVP record</p>';

	}

	$rsvp_id = intval($_COOKIE[ 'rsvp_for_' . $post->ID ]);

	global $wpdb;

	global $post;

	$event = $post->ID;

	if ( get_post_meta( $event, '_stripe_' . $tx_id, true ) ) {

		echo '<p style="color:red;">Payment already recorded</p>';

		return; // if transaction ID recorded, do not duplicate payment

	}

	$paid_amounts = get_post_meta( $event, '_paid_' . $rsvp_id );

	if ( ! empty( $paid_amounts ) ) {

		foreach ( $paid_amounts as $payment ) {

			$paid += $payment;
		}
	}

	$wpdb->query( 'UPDATE ' . $wpdb->prefix . "rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id " );

	add_post_meta( $event, '_stripe_' . $tx_id, $charge );

	add_post_meta( $event, '_paid_' . $rsvp_id, $charge );

	delete_post_meta( $event, '_open_invoice_' . $rsvp_id );

	delete_post_meta( $event, '_invoice_' . $rsvp_id );

	$row = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE id=$rsvp_id ", ARRAY_A );

	$message = sprintf( '<p>%s ' . __( 'payment for', 'rsvpmaker' ) . ' %s %s ' . __( ' c/o Stripe transaction', 'rsvpmaker' ) . ' %s<br />' . __( 'Post ID', 'rsvpmaker' ) . ': %s<br />' . __( 'Time', 'rsvpmaker' ) . ': %s</p>', esc_html( $charge ), esc_html( $row['first'] ), esc_html( $row['last'] ), esc_html( $tx_id ), esc_html( $event ), date( 'r' ) );

	add_post_meta( $event, '_paypal_log', $message );

}

function rsvpmaker_custom_payment( $method, $paid, $rsvp_id, $event, $tx_id = 0 ) {

	global $wpdb;

	$charge = $paid;

	$paid_amounts = get_post_meta( $event, '_paid_' . $rsvp_id );

	if ( ! empty( $paid_amounts ) ) {

		foreach ( $paid_amounts as $payment ) {

			$paid += $payment;
		}
	}

	$wpdb->query( 'UPDATE ' . $wpdb->prefix . "rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id " );

	add_post_meta( $event, '_' . $method . '_' . $tx_id, $charge );

	add_post_meta( $event, '_paid_' . $rsvp_id, $charge );

	delete_post_meta( $event, '_open_invoice_' . $rsvp_id );

	delete_post_meta( $event, '_invoice_' . $rsvp_id );

	$log = sprintf( '%s amount: %s rsvp_id: %s event: %s, tx: %s', $method, $paid, $rsvp_id, $event, $tx_id = 0 );

	//rsvpmaker_debug_log( $log );

}

function add_rsvpmaker_roles() {

	$rsvpmakereditor = get_role( 'rsvpmakereditor' );

	if ( ! $rsvpmakereditor ) {

		add_role(
			'rsvpmakereditor',
			'RSVPMaker Editor',
			array(

				'read'                      => true,

				'upload_files'              => true,

				'delete_posts'              => true,

				'delete_private_posts'      => true,

				'delete_published_posts'    => true,

				'edit_posts'                => true,

				'edit_private_posts'        => true,

				'edit_published_posts'      => true,

				'publish_posts'             => true,

				'delete_others_rsvpmakers'  => true,

				'delete_rsvpmakers'         => true,

				'edit_rsvpmakers'           => true,

				'edit_others_rsvpmakers'    => true,

				'edit_published_rsvpmakers' => true,

				'publish_rsvpmakers'        => true,

				'read_private_rsvpmakers'   => true,

			)
		);
	}

}
function rsvpmaker_wp_editor( $content, $editor_id, $settings = array() ) {
	if ( function_exists( 'do_blocks' ) ) { // gutenberg world

		printf( '<p><textarea rows="10" cols="80" id="%s" name=%s>%s</textarea></p>', esc_attr( $editor_id ), esc_attr( $editor_id ), wp_kses_post( $content ) );

	} else {
		wp_editor( $content, $editor_id, $settings );
	}

}

function rsvpmaker_dequeue_script() {

	wp_dequeue_script( 'tiny_mce' );

}
function rsvpautog( $content ) {

	if ( strpos( $content, '<!-- /wp:paragraph -->' ) ) {

		return $content; // already coded for gutenberg
	}

	$content = wpautop( $content );

	$content = str_replace( '</p>', "</p>\n<!-- /wp:paragraph -->\n", $content );

	$content = str_replace( '<p>', "<!-- wp:paragraph -->\n<p>", $content );

	return $content;

}

function rsvpmaker_server_block_render() {

	if ( wp_is_json_request() ) {

		return;
	}

	register_block_type( 'rsvpmaker/event', array( 'render_callback' => 'rsvpmaker_one' ) );

	//register_block_type( 'rsvpmaker/upcoming', array( 'render_callback' => 'rsvpmaker_upcoming' ) );

	register_block_type( 'rsvpmaker/stripecharge', array( 'render_callback' => 'rsvpmaker_stripecharge' ) );

	register_block_type( 'rsvpmaker/paypal', array( 'render_callback' => 'rsvpmaker_paypay_button_embed' ) );

	register_block_type( 'rsvpmaker/limited', array( 'render_callback' => 'rsvpmaker_limited_time' ) );

	register_block_type( 'rsvpmaker/formfield', array( 'render_callback' => 'rsvp_form_text' ) );

	register_block_type( 'rsvpmaker/formtextarea', array( 'render_callback' => 'rsvp_form_textarea' ) );

	register_block_type( 'rsvpmaker/formselect', array( 'render_callback' => 'rsvp_form_select' ) );

	register_block_type( 'rsvpmaker/formradio', array( 'render_callback' => 'rsvp_form_radio' ) );

	register_block_type( 'rsvpmaker/formnote', array( 'render_callback' => 'rsvp_form_note' ) );

	register_block_type( 'rsvpmaker/guests', array( 'render_callback' => 'rsvp_form_guests' ) );

	register_block_type( 'rsvpmaker/stripe-form-wrapper', array( 'render_callback' => 'stripe_form_wrapper' ) );

	register_block_type( 'rsvpmaker/eventlisting', array( 'render_callback' => 'rsvpmaker_event_listing' ) );

	register_block_type( 'rsvpmaker/upcoming-by-json', array( 'render_callback' => 'rsvpjsonlisting' ) );

	register_block_type( 'rsvpmaker/embedform', array( 'render_callback' => 'rsvpmaker_form' ) );

	register_block_type( 'rsvpmaker/schedule', array( 'render_callback' => 'rsvpmaker_daily_schedule' ) );

	register_block_type( 'rsvpmaker/future-rsvp-links', array( 'render_callback' => 'future_rsvp_links' ) );

	register_block_type( 'rsvpmaker/submission', array( 'render_callback' => 'rsvpmaker_submission' ) );

	register_block_type( 'rsvpmaker/formchimp', array( 'render_callback' => 'rsvpmaker_formchimp' ) );
	register_block_type( 'rsvpmaker/next-events', array( 'render_callback' => 'rsvpmaker_next_rsvps' ) );

	register_block_type( 'rsvpmaker/embedposts', array( 'render_callback' => 'rsvpmaker_emailpostorposts' ) );
	register_block_type( 'rsvpmaker/emailguestsignup', array( 'render_callback' => 'rsvpmail_signup_form' ) );
	register_block_type( 'rsvpmaker/formwrapper', array( 'render_callback' => 'rsvpmaker_flexible_form_wrapper' ) );
}
