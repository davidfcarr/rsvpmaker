import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useChimpOptions = () => {
	const [ chimpOptions, setChimpOptions ] = useState( {} );

	const { createSuccessNotice } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
			setChimpOptions( wpSettings.chimp || {} );
		} );
		apiFetch( { path: '/rsvpmaker/v1/chimp_lists' } ).then( ( chimpLists ) => {
			setChimpOptions( (prevOptions) => ({ ...prevOptions, chimp_lists:chimpLists.chimp_lists }) );
		} );
	}, [] );

	const saveChimpOptions = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				chimp: chimpOptions,
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'Email settings saved.', 'rsvpmaker' )
			);
		} );
	};

	return [ chimpOptions, setChimpOptions, saveChimpOptions ];
};

export default useChimpOptions;
