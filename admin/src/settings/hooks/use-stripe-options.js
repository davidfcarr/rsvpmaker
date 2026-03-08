import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useStripeOptions = () => {
	const [ stripeOptions, setStripeOptions ] = useState( {} );

	const noticesDispatch = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
			setStripeOptions( wpSettings.rsvpmaker_stripe_keys || {} );
		} );
	}, [] );

	const saveStripeOptions = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				rsvpmaker_stripe_keys: stripeOptions,
			},
		} ).then( () => {
			const noticeId = `stripe-save-${ Date.now() }`;
			noticesDispatch.removeAllNotices?.();
			noticesDispatch.createSuccessNotice(
				__( 'Payment settings saved.', 'rsvpmaker' ),
				{ id: noticeId, isDismissible: true }
			);

			setTimeout( () => {
				noticesDispatch.removeNotice?.( noticeId );
			}, 5000 );
		} );
	};

	return [ stripeOptions, setStripeOptions, saveStripeOptions ];
};

export default useStripeOptions;
