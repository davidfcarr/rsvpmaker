<?php

function rsvpmaker_recaptcha_mode() {
	global $rsvp_options;

	$mode = isset( $rsvp_options['rsvp_recaptcha_version'] ) ? sanitize_text_field( $rsvp_options['rsvp_recaptcha_version'] ) : '';
	if ( 'disabled' === $mode ) {
		return 'disabled';
	}

	if ( 'v3' === $mode ) {
		return 'v3';
	}

	if ( 'v2' === $mode ) {
		return 'v2';
	}

	if ( ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) ) {
		return 'v2';
	}

	return 'disabled';
}

function rsvpmaker_recaptcha_enabled() {
	global $rsvp_options;
	return ( 'disabled' !== rsvpmaker_recaptcha_mode() ) && ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] );
}

function rsvpmaker_recaptcha_output($return = false) {

if($return)

	ob_start();
	global $rsvp_options;
	if ( rsvpmaker_recaptcha_enabled() ) {
		$mode = rsvpmaker_recaptcha_mode();
		if ( 'v3' === $mode ) {
			static $v3_script_enqueued = false;
			?>
<input type="hidden" name="g-recaptcha-response" class="rsvpmaker-recaptcha-response" value="" />
<div class="rsvpmaker-recaptcha-v3-config" data-site-key="<?php echo esc_attr( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>" data-action="rsvpmaker_form"></div>
			<?php
			if ( ! $v3_script_enqueued ) {
				$v3_script_enqueued = true;
				$api_url = add_query_arg(
					array(
						'render' => $rsvp_options['rsvp_recaptcha_site_key'],
						'hl'     => get_locale(),
					),
					'https://www.google.com/recaptcha/api.js'
				);
				wp_enqueue_script( 'rsvpmaker_recaptcha_v3', esc_url_raw( $api_url ), array(), get_rsvpversion(), true );
				wp_enqueue_script( 'rsvpmaker_recaptcha_v3_init', esc_url_raw( plugins_url('assets/recaptcha.js', __FILE__) ), array(), get_rsvpversion(), true );
			}
		} else {
		?>
<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>"></div>
		<?php
			$api_url = add_query_arg(
				array(
					'hl' => get_locale(),
				),
				'https://www.google.com/recaptcha/api.js'
			);
			wp_enqueue_script( 'rsvpmaker_recaptcha_v2', esc_url_raw( $api_url ), array(), get_rsvpversion(), true );
		}
	}

if($return)

	return ob_get_clean();

}
function rsvpmaker_recaptcha_check( $siteKey, $secret ) {
	$mode = rsvpmaker_recaptcha_mode();
	if ( 'disabled' === $mode ) {
		return true;
	}

	if ( empty( $siteKey ) || empty( $secret ) ) {
		return false;
	}

	if ( ! isset( $_POST['g-recaptcha-response'] ) ) {

		return false;

	}
	require_once 'recaptcha-master/src/autoload.php';
	$recaptcha = new \ReCaptcha\ReCaptcha( $secret );
	$token = sanitize_text_field( $_POST['g-recaptcha-response'] );
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
	$resp = $recaptcha->verify( $token, $ip );
	if ( $resp->isSuccess() ) {
		if ( 'v3' === $mode && method_exists( $resp, 'getScore' ) ) {
			$score = $resp->getScore();
			if ( null !== $score && $score < 0.5 ) {
				return false;
			}
		}
		return true;
	} else {

		return false;

	}
}
