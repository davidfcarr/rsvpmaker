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
			static $v3_script_printed = false;
			?>
<input type="hidden" name="g-recaptcha-response" class="rsvpmaker-recaptcha-response" value="" />
			<?php
			if ( ! $v3_script_printed ) {
				$v3_script_printed = true;
				?>
<script type="text/javascript"
		src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>&hl=<?php echo esc_attr( get_locale() ); ?>">
</script>
<script type="text/javascript">
(function () {
	if ( window.rsvpmakerRecaptchaV3Init ) {
		return;
	}
	window.rsvpmakerRecaptchaV3Init = true;
	window.rsvpmakerRecaptchaV3SiteKey = '<?php echo esc_js( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>';

	window.rsvpmakerRecaptchaV3Refresh = function () {
		if ( typeof grecaptcha === 'undefined' ) {
			return Promise.resolve('');
		}
		return new Promise(function (resolve) {
			grecaptcha.ready(function () {
				grecaptcha.execute(window.rsvpmakerRecaptchaV3SiteKey, { action: 'rsvpmaker_form' }).then(function (token) {
					var nodes = document.querySelectorAll('.rsvpmaker-recaptcha-response');
					for (var i = 0; i < nodes.length; i++) {
						nodes[i].value = token;
					}
					resolve(token);
				});
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', window.rsvpmakerRecaptchaV3Refresh);
	} else {
		window.rsvpmakerRecaptchaV3Refresh();
	}

	setInterval(window.rsvpmakerRecaptchaV3Refresh, 90000);
})();
</script>
				<?php
			}
		} else {
		?>
<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>"></div>
<script type="text/javascript"
		src="https://www.google.com/recaptcha/api.js?hl=<?php echo get_locale(); ?>">
</script>
		<?php
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

