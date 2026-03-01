import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useStripeOptions = () => {
	const [ stripeOptions, setStripeOptions ] = useState( {} );

	const { createSuccessNotice } = useDispatch( noticesStore );

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
			createSuccessNotice(
				__( 'Stripe settings saved.', 'rsvpmaker' )
			);
		} );
	};

	return [ stripeOptions, setStripeOptions, saveStripeOptions ];
};

export default useStripeOptions;
