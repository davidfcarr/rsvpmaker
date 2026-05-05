import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Notices } from './notices';

const defaults = {
	user: '',
	password: '',
	subject_prefix: '',
	whitelist: '',
	blocked: '',
	additional_recipients: '',
};

const GroupEmailSettings = () => {
	const [ settings, setSettings ] = useState( null );
	const [ loadError, setLoadError ] = useState( '' );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice, removeAllNotices } = useDispatch( noticesStore );

	useEffect( () => {
		apiFetch( { path: '/rsvpmaker/v1/rsvp_options' } )
			.then( ( data ) => {
				const incoming = data.group_email || {};
				setSettings( {
					active: !! incoming.active,
					server: incoming.server || '{localhost:995/pop3/ssl/novalidate-cert}',
					queue_limit: incoming.queue_limit || 10,
					postmark_handle_incoming: !! incoming.postmark_handle_incoming,
					is_toastmasters: !! incoming.is_toastmasters,
					member: { ...defaults, ...( incoming.member || {} ) },
					officer: { ...defaults, ...( incoming.officer || {} ) },
					extra: { ...defaults, ...( incoming.extra || {} ) },
					bot: { ...defaults, ...( incoming.bot || {} ) },
				} );
				setLoadError( '' );
			} )
			.catch( ( error ) => {
				setLoadError( error?.message || __( 'Could not load Group Email settings.', 'rsvpmaker' ) );
			} );
	}, [] );

	const updateList = ( list, key, value ) => {
		setSettings( ( current ) => ( {
			...current,
			[ list ]: {
				...current[ list ],
				[ key ]: value,
			},
		} ) );
	};

	const save = async () => {
		if ( ! settings ) {
			return;
		}

		setIsSaving( true );
		removeAllNotices?.();

		const payload = [
			{ type: 'option_raw', key: 'rsvpmaker_discussion_active', value: settings.active ? 1 : 0 },
			{ type: 'option_raw', key: 'rsvpmaker_discussion_server', value: settings.server || '' },
			{ type: 'option_raw', key: 'rsvpmaker_email_queue_limit', value: parseInt( settings.queue_limit, 10 ) || 10 },
			{ type: 'option_raw', key: 'rsvpmaker_discussion_member', value: settings.member },
			{ type: 'option_raw', key: 'rsvpmaker_discussion_officer', value: settings.officer },
			{ type: 'option_raw', key: 'rsvpmaker_discussion_extra', value: settings.extra },
			{ type: 'option_raw', key: 'rsvpmaker_discussion_bot', value: settings.bot },
		];

		try {
			await apiFetch( {
				path: '/rsvpmaker/v1/rsvp_options',
				method: 'POST',
				data: payload,
			} );
			createSuccessNotice( __( 'Group Email settings saved.', 'rsvpmaker' ), { isDismissible: true } );
		} catch ( error ) {
			createErrorNotice( __( 'Error saving Group Email settings.', 'rsvpmaker' ), { isDismissible: true } );
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! settings ) {
		if ( loadError ) {
			return <p>{ __( 'Could not load Group Email settings. Check your permissions and REST API response.', 'rsvpmaker' ) }</p>;
		}
		return <p>{ __( 'Loading...', 'rsvpmaker' ) }</p>;
	}

	const renderList = ( listKey, label, hideInbound = false ) => (
		<section style={ { borderTop: '1px solid #ddd', marginTop: '16px', paddingTop: '16px' } }>
            <p>{ __( 'These are optional settings for managing a mailing list with RSVPMaker. Configure email handling for this list. Use the whitelist and blocked fields to control which incoming messages are processed. Use additional recipients to specify any email addresses that should receive copies of messages sent to this list.', 'rsvpmaker' ) }</p>
			<h3>{ label }</h3>
			{ ! hideInbound && ! settings.postmark_handle_incoming && (
				<>
					<TextControl label={ __( 'Email/User', 'rsvpmaker' ) } value={ settings[ listKey ].user } onChange={ ( value ) => updateList( listKey, 'user', value ) } />
					<TextControl label={ __( 'Password', 'rsvpmaker' ) } value={ settings[ listKey ].password } onChange={ ( value ) => updateList( listKey, 'password', value ) } />
				</>
			) }
			{ listKey !== 'bot' && (
				<>
					<TextControl label={ __( 'Subject Prefix', 'rsvpmaker' ) } value={ settings[ listKey ].subject_prefix } onChange={ ( value ) => updateList( listKey, 'subject_prefix', value ) } />
					<TextareaControl label={ __( 'Whitelist', 'rsvpmaker' ) } value={ settings[ listKey ].whitelist } onChange={ ( value ) => updateList( listKey, 'whitelist', value ) } />
					<TextareaControl label={ __( 'Blocked', 'rsvpmaker' ) } value={ settings[ listKey ].blocked } onChange={ ( value ) => updateList( listKey, 'blocked', value ) } />
					<TextareaControl label={ __( 'Additional Recipients', 'rsvpmaker' ) } value={ settings[ listKey ].additional_recipients } onChange={ ( value ) => updateList( listKey, 'additional_recipients', value ) } />
				</>
			) }
		</section>
	);

	return (
		<div>
			<p>{ __( 'Configure member email relay and list routing options.', 'rsvpmaker' ) }</p>
			<Notices />
			<ToggleControl label={ __( 'Activate Group Email Relay', 'rsvpmaker' ) } checked={ settings.active } onChange={ ( value ) => setSettings( ( current ) => ( { ...current, active: value } ) ) } />
			{ ! settings.postmark_handle_incoming && (
				<>
					<TextControl label={ __( 'POP3 Server', 'rsvpmaker' ) } value={ settings.server } onChange={ ( value ) => setSettings( ( current ) => ( { ...current, server: value } ) ) } />
					<TextControl label={ __( 'Queue Limit', 'rsvpmaker' ) } type="number" value={ String( settings.queue_limit ) } onChange={ ( value ) => setSettings( ( current ) => ( { ...current, queue_limit: value } ) ) } />
				</>
			) }
			{ settings.postmark_handle_incoming && <p>{ __( 'Incoming messages are handled by Postmark integration.', 'rsvpmaker' ) }</p> }
			{ renderList( 'member', __( 'Member List', 'rsvpmaker' ) ) }
			{ settings.is_toastmasters && renderList( 'officer', __( 'Officer List', 'rsvpmaker' ) ) }
			{ renderList( 'extra', __( 'Extra List', 'rsvpmaker' ) ) }
			{ ! settings.postmark_handle_incoming && renderList( 'bot', __( 'Bot Account', 'rsvpmaker' ), true ) }
			<div style={ { marginTop: '16px' } }>
				<Button variant="primary" onClick={ save } disabled={ isSaving }>
					{ isSaving ? __( 'Saving...', 'rsvpmaker' ) : __( 'Save', 'rsvpmaker' ) }
				</Button>
			</div>
		</div>
	);
};

export { GroupEmailSettings };
