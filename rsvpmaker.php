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
* Version: 11.9.8
*/

function get_rsvpversion() {
	return '11.9.8';
}

global $wp_version;
global $default_tz;
global $rsvpmaker_event;
global $rsvpmakers;
$default_tz = date_default_timezone_get();

function rsvpmaker_load_plugin_textdomain() {
	load_plugin_textdomain( 'rsvpmaker', false, basename( dirname( __FILE__ ) ) . '/translations/' );
}

global $rsvp_options;

$rsvp_options = get_option( 'RSVPMAKER_Options' );

$locale = get_locale();

function rsvp_options_defaults() {

	global $rsvp_options, $wpdb;

	if ( empty( $rsvp_options ) ) {
		$rsvp_options = array();
	}

	// defaults

	$rsvp_defaults = array(
		'calendar_icons'                    => true,

		'social_title_date'                 => true,

		'default_content'                   => '',

		'rsvp_to'                           => get_bloginfo( 'admin_email' ),

		'confirmation_include_event'        => false,

		'rsvpmaker_send_confirmation_email' => true,

		'rsvp_instructions'                 => '',

		'rsvp_count'                        => true,

		'rsvp_count_party'                  => true,

		'rsvp_yesno'                        => true,

		'send_payment_reminders'            => true,
		'cancel_unpaid_hours'            => 0,

		'rsvp_on'                           => false,

		'rsvp_max'                          => 0,

		'login_required'                    => false,

		'rsvp_captcha'                      => false,

		'show_attendees'                    => false,

		'convert_timezone'                  => false,

		'add_timezone'                      => false,

		'rsvp_form_title'                   => __( 'RSVP Now!', 'rsvpmaker' ),

		'defaulthour'                       => '19',

		'defaultmin'                        => '00',

		'long_date'                         => 'l F j, Y',

		'short_date'                        => 'M j',

		'time_format'                       => 'g:i A',

		'smtp'                              => '',

		'paypal_currency'                   => 'USD',

		'currency_decimal'                  => '.',

		'currency_thousands'                => ',',

		'payment_minimum'                   => '5.00',
		'dashboard'                 => '',
		'dashboard_message'                 => '',

		'rsvpmaker_send_confirmation_email' => true,

		'update_rsvp'                       => __( 'Update RSVP', 'rsvpmaker' ),
		'rsvp_recaptcha_site_key' => '',
		'rsvp_recaptcha_secret' => '',
		'debug' => false,
		'payment_gateway' => 'Cash or Custom',
		'report_security' => 'publish_rsvpmakers',
	);

	$update = false;
	foreach ( $rsvp_defaults as $index => $value ) {
		if ( is_bool( $value ) && isset( $rsvp_options[ $index ] ) && ! is_bool( $rsvp_options[ $index ] ) ) {
			$rsvp_options[ $index ] = (bool) $rsvp_options[ $index ];
			$update = true;
		}
		elseif(('defaulthour' == $index) || ('defaultmin' == $index)) {
			if ( isset( $rsvp_options[ $index ] ) && ! is_string( $rsvp_options[ $index ] ) ) {
				$v = strval( $rsvp_options[ $index ] );
				$rsvp_options[ $index ] = ( strlen( $v ) == 1 ) ? '0' . $v : $v;
				$update = true;
			}
		}
	}

	$rsvp_defaults = apply_filters( 'rsvpmaker_defaults', $rsvp_defaults );

	foreach ( $rsvp_defaults as $index => $value ) {
		if ( ! isset( $rsvp_options[ $index ] ) ) {
			$rsvp_options[ $index ] = $rsvp_defaults[ $index ];
			$update = true;
		}
	}
	$rsvp_options['rsvplink'] = get_rsvp_link();
	$rsvp_options['rsvp_button'] = get_option('rsvpmaker_link_template_post');
	$rsvp_options['rsvplink_edit'] = admin_url('post.php?action=edit&post='.$rsvp_options['rsvp_button']);

	if ( empty( $rsvp_options['long_date'] ) || ( strpos( $rsvp_options['long_date'], '%' ) !== false ) ) {

		$rsvp_options['long_date'] = 'l F j, Y';

		$rsvp_options['short_date'] = 'M j';

		$rsvp_options['time_format'] = 'g:i A';

		$update = true;
	}

	if ( isset( $rsvp_options['rsvp_to_current'] ) && $rsvp_options['rsvp_to_current'] && is_user_logged_in() ) {

		global $current_user;

		$rsvp_options['rsvp_to'] = $current_user->user_email;

	}

	//fix any forms assigned the wrong post type
	$wpdb->query("update $wpdb->posts set post_type='rsvpmaker_form' where post_content LIKE '%wp:rsvpmaker/formfield%' and post_content LIKE '%\"slug\":\"first\"%' AND (post_type != 'rsvpmaker_form' and post_type != 'revision');");

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

		$data['post_type'] = 'rsvpmaker_form';

		$rsvp_options['rsvp_form'] = wp_insert_post( $data );

		update_post_meta( $rsvp_options['rsvp_form'], '_rsvpmaker_special', 'RSVP Form' );

		$update = true;

	} elseif ( ! is_numeric( $rsvp_options['rsvp_form'] ) ) {

		$data['post_title'] = 'Form:Default';

		$data['post_content'] = $rsvp_options['rsvp_form'];

		$data['post_status'] = 'publish';

		$data['post_type'] = 'rsvpmaker_form';

		$data['post_author'] = 1;

		$rsvp_options['rsvp_form'] = wp_insert_post( $data );

		$update = true;

	}

	$rsvp_defaults['rsvp_form'] = $rsvp_options['rsvp_form'];

	if ( strpos( $rsvp_options['rsvplink'], '*|EMAIL|*' ) ) {

		$rsvp_options['rsvplink'] = str_replace( '?e=*|EMAIL|*#rsvpnow', '', $rsvp_options['rsvplink'] );

		$update = true;

	}

	// if html removed (recover from error with sanitization on settings screen)

	if ( ! strpos( $rsvp_options['rsvplink'], '</a>' ) ) {

		$rsvp_options['rsvplink'] = '<p><a style="width: 8em; display: block; border: medium inset #FF0000; text-align: center; padding: 3px; background-color: #0000FF; color: #FFFFFF; font-weight: bolder; text-decoration: none;" class="rsvplink" href="%s">' . __( 'RSVP Now!', 'rsvpmaker' ) . '</a></p>';

		$update = true;

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

		$update = true;

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

		$update = true;

	}
	if ( $update ) {
		update_option( 'RSVPMAKER_Options', $rsvp_options );
	}
}

function rsvpmaker_sanitize_options( $value ) {
	return is_array( $value ) ? $value : array();
}

function rsvpmaker_normalize_postmark_options( $value ) {
	if ( ! is_array( $value ) ) {
		$value = array();
	}

	$integer_fields = array( 'restricted' );
	foreach ( $integer_fields as $field ) {
		$value[ $field ] = isset( $value[ $field ] ) ? (int) $value[ $field ] : 0;
	}

	$array_of_int_fields = array();
	foreach ( $array_of_int_fields as $field ) {
		if ( empty( $value[ $field ] ) || ! is_array( $value[ $field ] ) ) {
			$value[ $field ] = array();
		} else {
			$value[ $field ] = array_map( 'intval', $value[ $field ] );
		}
	}

	$array_of_string_fields = array( 'enabled', 'sandbox_only' );
	foreach ( $array_of_string_fields as $field ) {
		if ( empty( $value[ $field ] ) || ! is_array( $value[ $field ] ) ) {
			$value[ $field ] = array();
		} else {
			$value[ $field ] = array_values(
				array_filter(
					array_map(
						function( $item ) {
							return sanitize_text_field( strval( $item ) );
						},
						$value[ $field ]
					),
					function( $item ) {
						return '' !== trim( $item );
					}
				)
			);
		}
	}

	$string_fields = array(
		'handle_incoming',
		'postmark_mode',
		'postmark_sandbox_key',
		'postmark_production_key',
		'postmark_tx_from',
		'postmark_broadcast_from',
		'postmark_tx_slug',
		'postmark_broadcast_slug',
		'postmark_load_alert_emails',
	);

	foreach ( $string_fields as $field ) {
		if ( ! isset( $value[ $field ] ) || ! is_string( $value[ $field ] ) ) {
			$value[ $field ] = isset( $value[ $field ] ) ? strval( $value[ $field ] ) : '';
		}
	}

	return $value;
}

function rsvpmaker_sanitize_postmark_options( $value ) {
	return rsvpmaker_normalize_postmark_options( $value );
}

add_filter( 'option_rsvpmaker_postmark', 'rsvpmaker_normalize_postmark_options', 5 );

function rsvpmaker_register_settings( $properties, $defaults ) {
	$schema  = array(
		'type'                 => 'object',
		'properties'           => $properties,
		'additionalProperties' => true,
	);

	register_setting(
		'options',
		'RSVPMAKER_Options',
		array(
			'type'              => 'object',
			'default'           => is_array( $defaults ) ? $defaults : array(),
			'sanitize_callback' => 'rsvpmaker_sanitize_options',
			'show_in_rest'      => array(
				'schema' => $schema,
			),
		)
	);
	$stripe_properties = array(
		'sk' => array( 'type' => 'string' ),
		'pk' => array( 'type' => 'string' ),
		'webhook' => array( 'type' => 'string' ),
		'sandbox_pk' => array( 'type' => 'string' ),
		'sandbox_sk' => array( 'type' => 'string' ),
		'mode' => array( 'type' => 'string' ),
	);
	$stripe_schema  = array(
		'type'                 => 'object',
		'properties'           => $stripe_properties,
		'additionalProperties' => true,
	);
	register_setting(
		'options',
		'rsvpmaker_stripe_keys',
		array(
			'type'              => 'object',
			'default'           => array('sk'=>'','pk'=>'','webhook'=>'','sandbox_pk'=>'','sandbox_sk'=>'','mode'=>'production'),
			'sanitize_callback' => 'rsvpmaker_sanitize_options',
			'show_in_rest'      => array(
				'schema' => $stripe_schema,
			),
		)
	);
	$paypal_properties = array(
		'client_id' => array( 'type' => 'string' ),
		'client_secret' => array( 'type' => 'string' ),
		'webhook' => array( 'type' => 'string' ),
		'sandbox_client_id' => array( 'type' => 'string' ),
		'sandbox_client_secret' => array( 'type' => 'string' ),
		'funding_sources' => array( 'type' => 'string' ),
		'excluded_funding_sources' => array( 'type' => 'string' ),
		'mode' => array( 'type' => 'string' ),
		'sandbox' => array( 'type' => 'integer' ),
	);
	$paypal_schema  = array(
		'type'                 => 'object',
		'properties'           => $paypal_properties,
		'additionalProperties' => true,
	);
	register_setting(
		'options',
		'rsvpmaker_paypal_rest_keys',
		array(
			'type'              => 'object',
			'default'           => array('client_id'=>'','client_secret'=>'','webhook'=>'','sandbox_client_id'=>'','sandbox_client_secret'=>'','funding_sources'=>'','excluded_funding_sources'=>'','mode'=>'','sandbox'=>0),
			'sanitize_callback' => 'rsvpmaker_sanitize_options',
			'show_in_rest'      => array(
				'schema' => $paypal_schema,
			),
		)
	);
	$stripe_properties = array(
		'sk' => array( 'type' => 'string' ),
		'pk' => array( 'type' => 'string' ),
		'webhook' => array( 'type' => 'string' ),
		'sandbox_pk' => array( 'type' => 'string' ),
		'sandbox_sk' => array( 'type' => 'string' ),
		'mode' => array( 'type' => 'string' ),
	);
	$stripe_schema  = array(
		'type'                 => 'object',
		'properties'           => $stripe_properties,
		'additionalProperties' => true,
	);
	register_setting(
		'options',
		'rsvpmaker_stripe_keys',
		array(
			'type'              => 'object',
			'default'           => array('sk'=>'','pk'=>'','webhook'=>'','sandbox_pk'=>'','sandbox_sk'=>'','mode'=>'production'),
			'sanitize_callback' => 'rsvpmaker_sanitize_options',
			'show_in_rest'      => array(
				'schema' => $stripe_schema,
			),
		)
	);
	$chimp_properties = array(
		'chimp-key' => array( 'type' => 'string' ),
		'email-name' => array( 'type' => 'string' ),
		'email-from' => array( 'type' => 'string' ),
		'company' => array( 'type' => 'string' ),
		'mailing_address' => array( 'type' => 'string' ),
		'chimplist' => array( 'type' => 'string' ),
		'add_notify' => array( 'type' => 'string' ),
		'chimp_add_new_users' => array( 'type' => 'boolean' ),
	);
	$chimp_schema  = array(
		'type'                 => 'object',
		'properties'           => $chimp_properties,
		'additionalProperties' => true,
	);
	register_setting(
		'options',
		'chimp',
		array(
			'type'              => 'object',
			'default'           => array('chimp-key'=>'','email-name'=>'','email-from'=>'','company'=>'','mailing_address'=>'','chimplist'=>'','add_notify'=>get_option('admin_email'),'chimp_add_new_users'=>false),
			'sanitize_callback' => 'rsvpmaker_sanitize_options',
			'show_in_rest'      => array(
				'schema' => $chimp_schema,
			),
		)
	);

$postmark_fields = array('postmark_mode', 'postmark_sandbox_key', 'postmark_production_key', 'postmark_tx_from', 'postmark_broadcast_from', 'postmark_tx_slug', 'postmark_broadcast_slug', 'postmark_load_alert_emails');
$postmark_properties = array();
$postmark_defaults = array();
foreach($postmark_fields as $field) {
	$postmark_properties[$field] = array( 'type' => 'string' );
	$postmark_defaults[$field] = '';
}
$postmark_defaults['postmark_mode'] = 'production';
$postmark_properties['handle_incoming'] = array( 'type' => 'string' );
$postmark_defaults['handle_incoming'] = '';
$postmark_properties['restricted'] = array( 'type' => 'integer' );
$postmark_defaults['restricted'] = 0;
	$postmark_properties['enabled'] = array(
		'type'  => 'array',
		'items' => array( 'type' => 'string' ),
	);
$postmark_defaults['enabled'] = array();
	$postmark_properties['sandbox_only'] = array(
		'type'  => 'array',
		'items' => array( 'type' => 'string' ),
	);
$postmark_defaults['sandbox_only'] = array();

$postmark_schema  = array(
		'type'                 => 'object',
		'properties'           => $postmark_properties,
		'additionalProperties' => true,
	);
	register_setting(
		'options',
		'rsvpmaker_postmark',
		array(
			'type'              => 'object',
			'default'           => $postmark_defaults,
			'sanitize_callback' => 'rsvpmaker_sanitize_postmark_options',
			'show_in_rest'      => array(
				'schema' => $postmark_schema,
			),
		)
	);
}

function rsvpmaker_register_settings_for_rest() {
	$options = get_option( 'RSVPMAKER_Options', array() );
	if ( empty( $options ) ) {
		rsvp_options_defaults();
		$options = get_option( 'RSVPMAKER_Options', array() );
	}

	$properties = array();
	foreach ( $options as $index => $value ) {
		if ( is_bool( $value ) ) {
			$properties[ $index ] = array( 'type' => 'boolean' );
		} 
		elseif ( is_int( $value ) ) {
			$properties[ $index ] = array( 'type' => 'integer' );
		} elseif ( is_float( $value ) ) {
			$properties[ $index ] = array( 'type' => 'number' );
		} elseif ( is_array( $value ) ) {
			$properties[ $index ] = array( 'type' => 'array' );
		} else {
			$properties[ $index ] = array( 'type' => 'string' );
		}
	}

	rsvpmaker_register_settings( $properties, $options );
}

add_action( 'rest_api_init', 'rsvpmaker_register_settings_for_rest', 5 );

function rsvpmaker_defaults_for_post( $post_id, $filter = [] ) {

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

	if(!empty($filter)) {
		$defaults = array_intersect_key($defaults, array_flip($filter));
	}

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

	include $rsvpmaker_dir . 'rsvpmaker-util.php';
	include $rsvpmaker_dir . 'rsvpmaker-types.php';
	include $rsvpmaker_dir . 'rsvpmaker-admin.php';
	include $rsvpmaker_dir . 'rsvpmaker-api-endpoints.php';
	include $rsvpmaker_dir . 'rsvpmaker-display.php';
	include $rsvpmaker_dir . 'rsvpmaker-template.php';
	include $rsvpmaker_dir . 'mailchimp-api-master/src/MailChimp.php';
	include $rsvpmaker_dir . 'rsvpmaker-email.php';
	include $rsvpmaker_dir . 'rsvpmaker-privacy.php';
	include $rsvpmaker_dir . 'rsvpmaker-actions.php';
	include $rsvpmaker_dir . 'rsvpmaker-form.php';
	include $rsvpmaker_dir . 'rsvpmaker-widgets.php';
	include $rsvpmaker_dir . 'rsvpmaker-group-email.php';
	include $rsvpmaker_dir . 'rsvpmaker-quick-playground.php';
	include $rsvpmaker_dir . 'rsvpmaker-report.php';
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
function rsvpmaker_cpevent_activate() {
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

	

	if ( ! $wpdb->get_var( $wpdb->prepare('SELECT slug FROM %i terms JOIN %i term_taxonomy on term_taxonomy.term_id=terms.term_id WHERE taxonomy="rsvpmaker-type" AND slug="featured"',$wpdb->prefix.'terms',$wpdb->prefix.'term_taxonomy') ) ) {

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

register_activation_hook( __FILE__, 'rsvpmaker_cpevent_activate' );

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
		$index = strpos($templates[0],'php') ? array_search('single.php',$templates) : array_search('single',$templates);
		// prefer the page template, doesn't emphasize date posted as much in most themes
		if($index) {
			if(strpos($templates[0],'php')) {
			$templates[$index] = 'page.php';
			$templates[] = 'single.php';
			}
			else {
			$templates[$index] = 'page';
			$templates[] = 'single';
			}
		}
	}
	return $templates;
}

function rsvpmaker_log_paypal( $message ) {

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

function rsvpmaker_format_cddate( $year, $month, $day, $hours, $minutes ) {

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

	$wpdb->query( $wpdb->prepare("UPDATE %i SET amountpaid=%s WHERE id=%d ",$wpdb->prefix.'rsvpmaker',$paid,$rsvp_id) );

	add_post_meta( $event, '_stripe_' . $tx_id, $charge );

	add_post_meta( $event, '_paid_' . $rsvp_id, $charge );

	delete_post_meta( $event, '_open_invoice_' . $rsvp_id );

	delete_post_meta( $event, '_invoice_' . $rsvp_id );

	$row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM %i WHERE id=%d",$wpdb->prefix.'rsvpmaker',$rsvp_id), ARRAY_A );

	$message = sprintf( '<p>%s ' . __( 'payment for', 'rsvpmaker' ) . ' %s %s ' . __( ' c/o Stripe transaction', 'rsvpmaker' ) . ' %s<br />' . __( 'Post ID', 'rsvpmaker' ) . ': %s<br />' . __( 'Time', 'rsvpmaker' ) . ': %s</p>', esc_html( $charge ), esc_html( $row['first'] ), esc_html( $row['last'] ), esc_html( $tx_id ), esc_html( $event ), date( 'r' ) );

	add_post_meta( $event, '_paypal_log', $message );

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

