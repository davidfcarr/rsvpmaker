<?php

function rsvpmaker_recaptcha_output($return = false) {

if($return)

	ob_start();



	global $rsvp_options;



	if ( ! empty( $rsvp_options['rsvp_recaptcha_site_key'] ) && ! empty( $rsvp_options['rsvp_recaptcha_secret'] ) ) {



		?>



<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $rsvp_options['rsvp_recaptcha_site_key'] ); ?>"></div>



<script type="text/javascript"



		src="https://www.google.com/recaptcha/api.js?hl=<?php echo get_locale(); ?>">



</script>



		<?php



	}

if($return)

	return ob_get_clean();

}



function rsvpmaker_recaptcha_check( $siteKey, $secret ) {



	if ( ! isset( $_POST['g-recaptcha-response'] ) ) {

		return false;

	}

	

	require_once 'recaptcha-master/src/autoload.php';



	$recaptcha = new \ReCaptcha\ReCaptcha( $secret );



	$resp = $recaptcha->verify( sanitize_text_field( $_POST['g-recaptcha-response'] ), sanitize_text_field($_SERVER['REMOTE_ADDR']) );



	if ( $resp->isSuccess() ) {



		return true;



	} else {

		return false;

	}



}

