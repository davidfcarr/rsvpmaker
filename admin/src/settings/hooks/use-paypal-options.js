import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const usePaypalOptions = () => {
	const [ paypalOptions, setPaypalOptions ] = useState( {} );

	const noticesDispatch = useDispatch( noticesStore );

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
			const noticeId = `paypal-save-${ Date.now() }`;
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

	return [ paypalOptions, setPaypalOptions, savePaypalOptions ];
};

export default usePaypalOptions;
