import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useRsvpOptions = () => {
	const [ rsvp_options, setRsvpOptions ] = useState( {} );

	const { createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
			setRsvpOptions( wpSettings.RSVPMAKER_Options || {} );
		} );
	}, [] );

	const saveRsvpOptions = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				RSVPMAKER_Options: rsvp_options,
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'Settings saved.', 'rsvpmaker' )
			);
		} );
	};

	return [ rsvp_options, setRsvpOptions, saveRsvpOptions ];
};

export default useRsvpOptions
