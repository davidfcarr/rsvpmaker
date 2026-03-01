import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const usePostmarkOptions = () => {
	const [ postmarkOptions, setPostmarkOptions ] = useState( {} );

	const { createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
			setPostmarkOptions( wpSettings.rsvpmaker_postmark || {} );
		} );
	}, [] );

	const savePostmarkOptions = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				rsvpmaker_postmark: postmarkOptions,
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'Postmark settings saved.', 'rsvpmaker' )
			);
		} );
	};

	return [ postmarkOptions, setPostmarkOptions, savePostmarkOptions ];
};

export default usePostmarkOptions;
