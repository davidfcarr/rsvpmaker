import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Notices } from './notices';

const MailPoetSettings = () => {
	const [ settings, setSettings ] = useState( null );
	const [ loadError, setLoadError ] = useState( '' );
	const [ selectedList, setSelectedList ] = useState( '' );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice, removeAllNotices } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/rsvpmaker/v1/rsvp_options' } )
			.then( ( data ) => {
				const mailpoet = data.mailpoet || {};
				setSettings( mailpoet );
				setSelectedList( mailpoet.selected_list ? String( mailpoet.selected_list ) : '' );
				setLoadError( '' );
			} )
			.catch( ( error ) => {
				setLoadError( error?.message || __( 'Could not load MailPoet settings.', 'rsvpmaker' ) );
			} );
	}, [] );

	const save = async () => {
		setIsSaving( true );
		removeAllNotices?.();
		try {
			await apiFetch( {
				path: '/rsvpmaker/v1/rsvp_options',
				method: 'POST',
				data: [
					{
						type: 'option_raw',
						key: 'rsvpmaker_mailpoet_list',
						value: parseInt( selectedList, 10 ) || 0,
					},
				],
			} );
			createSuccessNotice( __( 'MailPoet settings saved.', 'rsvpmaker' ), { isDismissible: true } );
		} catch ( error ) {
			createErrorNotice( __( 'Error saving MailPoet settings.', 'rsvpmaker' ), { isDismissible: true } );
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! settings ) {
		if ( loadError ) {
			return <p>{ __( 'Could not load MailPoet settings. Check your permissions and REST API response.', 'rsvpmaker' ) }</p>;
		}
		return <p>{ __( 'Loading...', 'rsvpmaker' ) }</p>;
	}

	if ( ! settings.enabled ) {
		return (
			<div>
				<p>{ __( 'MailPoet is not active. Activate MailPoet to enable this integration.', 'rsvpmaker' ) }</p>
			</div>
		);
	}

	const options = [ { label: __( 'Choose List', 'rsvpmaker' ), value: '' } ];
	( settings.lists || [] ).forEach( ( list ) => {
		options.push( { label: list.label, value: String( list.value ) } );
	} );

	return (
		<div>
			<p>{ __( 'This is optional functionality for using MailPoet with RSVPMaker (Postmark integration is generally recommended instead, and compatibility with current releases of MailPoet is not guaranteed). Select the MailPoet list used for "Add me to your email list" opt-ins.', 'rsvpmaker' ) }</p>
			<Notices />
			<SelectControl
				label={ __( 'MailPoet List', 'rsvpmaker' ) }
				value={ selectedList }
				options={ options }
				onChange={ setSelectedList }
			/>
			<Button variant="primary" onClick={ save } disabled={ isSaving }>
				{ isSaving ? __( 'Saving...', 'rsvpmaker' ) : __( 'Save', 'rsvpmaker' ) }
			</Button>
		</div>
	);
};

export { MailPoetSettings };
