const recaptchaConfig = document.querySelector('.rsvpmaker-recaptcha-v3-config[data-site-key]');
console.log('Recaptcha config:', recaptchaConfig);
if (recaptchaConfig && !window.rsvpmakerRecaptchaV3Init) {
	window.rsvpmakerRecaptchaV3Init = true;
	window.rsvpmakerRecaptchaV3SiteKey = recaptchaConfig.getAttribute('data-site-key');
	window.rsvpmakerRecaptchaV3Action = recaptchaConfig.getAttribute('data-action') || 'rsvpmaker_form';
	console.log('Recaptcha site key:', window.rsvpmakerRecaptchaV3SiteKey);
	console.log('Recaptcha action:', window.rsvpmakerRecaptchaV3Action);
	window.rsvpmakerRecaptchaV3Refresh = function () {
		if (typeof grecaptcha === 'undefined') {
			console.log('Recaptcha not loaded yet');
			return Promise.resolve('');
		}
		return new Promise(
			function(resolve) {
				console.log('Recaptcha ready, executing...',grecaptcha);
				grecaptcha.ready(
					function () {
						grecaptcha.execute(
							window.rsvpmakerRecaptchaV3SiteKey,
							{ action: window.rsvpmakerRecaptchaV3Action }
						).then(
							function(token) {
								document.querySelectorAll('.rsvpmaker-recaptcha-response').forEach(
									(node) => {
										node.value = token;
									}
								);
								resolve(token);
							}
						);
					}
				);
			}
		);
	};
	window.rsvpmakerRecaptchaV3Refresh();
	setInterval(window.rsvpmakerRecaptchaV3Refresh, 90000);
}
