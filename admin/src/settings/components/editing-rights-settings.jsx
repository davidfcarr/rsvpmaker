import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Notices } from './notices';

const EditingRightsSettings = () => {
	const [ roles, setRoles ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState( '' );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice, removeAllNotices } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/rsvpmaker/v1/rsvp_options' } )
			.then( ( data ) => {
				setRoles( data.email_role_caps || [] );
				setLoadError( '' );
			} )
			.catch( ( error ) => {
				setLoadError( error?.message || __( 'Could not load editing/sending rights.', 'rsvpmaker' ) );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	const setRoleLevel = ( slug, level ) => {
		setRoles( ( current ) => current.map( ( role ) => ( role.slug === slug ? { ...role, level } : role ) ) );
	};

	const save = async () => {
		setIsSaving( true );
		removeAllNotices?.();
		const updates = {};
		roles.forEach( ( role ) => {
			updates[ role.slug ] = role.level || 'none';
		} );

		try {
			await apiFetch( {
				path: '/rsvpmaker/v1/rsvp_options',
				method: 'POST',
				data: [
					{
						type: 'email_cap_update',
						value: updates,
					},
				],
			} );
			createSuccessNotice( __( 'Editing/Sending rights saved.', 'rsvpmaker' ), { isDismissible: true } );
		} catch ( error ) {
			createErrorNotice( __( 'Error saving editing/sending rights.', 'rsvpmaker' ), { isDismissible: true } );
		} finally {
			setIsSaving( false );
		}
	};

	if ( isLoading ) {
		return <p>{ __( 'Loading...', 'rsvpmaker' ) }</p>;
	}

	if ( loadError ) {
		return <p>{ __( 'Could not load editing/sending rights. Check your permissions and REST API response.', 'rsvpmaker' ) }</p>;
	}

	return (
		<div>
			<p>{ __( 'Control which roles can edit drafts or publish/send RSVP Email broadcasts.', 'rsvpmaker' ) }</p>
			<Notices />
			{ roles.map( ( role ) => (
				<SelectControl
					key={ role.slug }
					label={ role.name }
					value={ role.level || 'none' }
					onChange={ ( value ) => setRoleLevel( role.slug, value ) }
					options={ [
						{ label: __( 'No Broadcast Rights', 'rsvpmaker' ), value: 'none' },
						{ label: __( 'Edit Draft Emails', 'rsvpmaker' ), value: 'edit' },
						{ label: __( 'Publish and Send Broadcasts', 'rsvpmaker' ), value: 'publish' },
					] }
				/>
			) ) }
			<Button variant="primary" onClick={ save } disabled={ isSaving }>
				{ isSaving ? __( 'Saving...', 'rsvpmaker' ) : __( 'Save', 'rsvpmaker' ) }
			</Button>
		</div>
	);
};

export { EditingRightsSettings };
