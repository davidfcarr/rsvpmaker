<?php
require_once __DIR__ . '/../../../wp-load.php';

$admins = get_users(
	array(
		'role' => 'administrator',
		'number' => 1,
		'fields' => 'ids',
	)
);

if ( empty( $admins ) ) {
	echo "NO_ADMIN\n";
	exit( 1 );
}

wp_set_current_user( (int) $admins[0] );

$request = new WP_REST_Request( 'GET', '/rsvpmaker/v1/rsvp_options' );
$response = rest_do_request( $request );
$status = $response->get_status();
$data = $response->get_data();

echo 'STATUS:' . $status . "\n";

if ( is_wp_error( $data ) ) {
	echo 'WP_ERROR:' . $data->get_error_code() . ':' . $data->get_error_message() . "\n";
	exit( 0 );
}

if ( isset( $data['group_email'] ) ) {
	echo "GROUP_EMAIL_OK\n";
}
if ( isset( $data['mailpoet'] ) ) {
	echo "MAILPOET_OK\n";
}
if ( isset( $data['email_role_caps'] ) ) {
	echo 'EMAIL_ROLE_CAPS_OK:' . count( $data['email_role_caps'] ) . "\n";
}

echo 'KEY_COUNT:' . count( $data ) . "\n";
