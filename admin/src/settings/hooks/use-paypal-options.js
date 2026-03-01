import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const usePaypalOptions = () => {
	const [ paypalOptions, setPaypalOptions ] = useState( {} );

	const { createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
			setPaypalOptions( wpSettings.rsvpmaker_paypal_rest_keys || {} );
		} );
	}, [] );

	const savePaypalOptions = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				rsvpmaker_paypal_rest_keys: paypalOptions,
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'PayPal settings saved.', 'rsvpmaker' )
			);
		} );
	};

	return [ paypalOptions, setPaypalOptions, savePaypalOptions ];
};

export default usePaypalOptions;
