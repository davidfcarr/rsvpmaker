<?php
if(! defined('WP_UNINSTALL_PLUGIN'))
	die;
global $wpdb;
$tables = array( 'rsvp_dates', 'rsvp_volunteer_time', 'rsvpmaker', 'rsvpmaker_event','rsvp_mailer_blocked' );
foreach ( $tables as $slug ) {
	$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $slug;
	$wpdb->query( $sql );
}
$sql = 'SELECT ID FROM ' . $wpdb->posts . " WHERE post_type='rsvpmaker' OR post_type='rsvpmaker_template' OR post_type='rsvpemail' OR post_type='rsvpmaker_form' ";
$items = $wpdb->get_results( $sql );
if($items)
foreach($items as $item)
	wp_delete_post($item->ID,true);

delete_option( 'RSVPMAKER_Options' );
delete_option( 'rsvpmaker_stripe_keys');
delete_option( 'rsvpmaker_paypal_rest_keys');
delete_option( 'rsvpmaker_help');
delete_option( 'rsvpmaker_last_data_check2');
delete_option( 'rsvpmaker_forms');
delete_option( 'rsvpmailer_tx_block_template');
delete_option( 'rsvpemail_from_settings');
delete_option( 'rsvpmaker_missing_help');
delete_option( 'widget_rsvpmaker_by_json');
delete_option( 'widget_rsvpmaker_type_widget');
