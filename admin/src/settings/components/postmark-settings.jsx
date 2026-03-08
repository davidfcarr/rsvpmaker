import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { usePostmarkOptions } from '../hooks';
import { Notices } from './notices';
import {useState, useEffect} from 'react';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Postmark Settings', 'rsvpmaker' ) }
		</Heading>
	);
};

const SaveButton = ( { label, onClick } ) => {
	return (
		<div>
			<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
				{ label || __( 'Save', 'rsvpmaker' ) }
			</Button>
		</div>
	);
};

const PostmarkSettings = () => {
	const [ postmarkOptions, setPostmarkOptions, savePostmarkOptions ] = usePostmarkOptions() || [{}, () => {}, () => {}];

	if ( !postmarkOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

const MultiCheck = ( { value, onChange, field, data } ) => {
	const fieldId = field?.id || 'enabled';
	const selectedSource = Array.isArray( value )
		? value
		: Array.isArray( data?.[ fieldId ] )
			? data[ fieldId ]
			: Array.isArray( postmarkOptions?.[ fieldId ] )
				? postmarkOptions[ fieldId ]
				: [];
	const selectedDomains = selectedSource
		.map( ( item ) => String( item ).trim() )
		.filter( ( item ) => item.length > 0 );
	const domainOptions = Array.isArray( rsvpmaker_rest?.domains )
		? rsvpmaker_rest.domains.map( ( domain ) => String( domain ).trim() ).filter( ( domain ) => domain.length > 0 )
		: [];

	const handleChange = ( domain ) => {
		const normalizedDomain = String( domain ).trim();
		console.log('handling change for domain', domain);
		const newValue = selectedDomains.includes( normalizedDomain )
			? selectedDomains.filter( ( item ) => item !== normalizedDomain )
			: [ ...selectedDomains, normalizedDomain ];
		const updatedDomains = [ ...new Set( newValue ) ];
		console.log('new value for enabled', updatedDomains);

		setPostmarkOptions( ( current ) => ( {
			...current,
			[ fieldId ]: updatedDomains,
		} ) );
	};

	return (
		<div>
			{ domainOptions.map( ( domain ) => (
				<p key={ domain }><label>
					<input
						type="checkbox"
						value={ domain }
						checked={ selectedDomains.includes( String( domain ).trim() ) }
						onChange={ () => handleChange( domain ) }
					/>
					{ domain+' '+field.label }
				</label></p>
			) ) }
		</div>
	);
};
	console.log('postmark options restricted', postmarkOptions.restricted+" "+typeof postmarkOptions.restricted);
	console.log('postmark options enabled', postmarkOptions.enabled);
	const fields = [
		{
			id: 'postmark_mode',
			label: __( 'Mode', 'rsvpmaker' ),
			type: 'string',
			Edit: 'radio',
			elements: [
				{ label: __( 'Production', 'rsvpmaker' ), value: 'production' },
				{ label: __( 'Sandbox', 'rsvpmaker' ), value: 'sandbox' },
			],
		},
		{
			id: 'restricted',
			label: __( 'Availability', 'rsvpmaker' ),
			type: 'number',
			Edit: 'radio',
			elements: [
				{ label: __( 'All Domains', 'rsvpmaker' ), value: 0 },
				{ label: __( 'Selected Domains', 'rsvpmaker' ), value: 1 },
			],
		},
		{
			id: 'handle_incoming',
			label: __( 'Handle Incoming Messages', 'rsvpmaker' ),
			type: 'string',
			Edit: 'radio',
			elements: [
				{ label: __( 'Yes', 'rsvpmaker' ), value: postmarkOptions?.handle_incoming || rsvpmaker_rest.default_incoming_nonce },
				{ label: __( 'No', 'rsvpmaker' ), value: '' },
			],
		},
		{
			id: 'postmark_sandbox_key',
			label: __( 'Sandbox Key', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_production_key',
			label: __( 'Production Key', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_tx_from',
			label: __( 'Email Address for Transactional Messages', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_broadcast_from',
			label: __( 'Email Address for Broadcast Messages', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_tx_slug',
			label: __( 'Stream ID for Transactional Messages', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_broadcast_slug',
			label: __( 'Stream ID for Broadcast Messages', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'postmark_load_alert_emails',
			label: __( 'Load Alert Emails', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'enabled',
			label: __( 'Enabled', 'rsvpmaker' ),
			type: 'array',
			Edit: MultiCheck,
		},
		{
			id: 'sandbox_only',
			label: __( 'Sandbox Only', 'rsvpmaker' ),
			type: 'array',
			Edit: MultiCheck,
		},
	];
	/*
		{
			id: '',
			label: __( '', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
	*/
	const restrictedValue = Number(postmarkOptions?.restricted) === 1 ? 1 : 0;
	const formchildren = (rsvpmaker_rest.multisite == "1") ? ['postmark_mode', 'postmark_production_key', 'postmark_sandbox_key', 'postmark_tx_from', 'postmark_broadcast_from', 'postmark_tx_slug', 'postmark_broadcast_slug', 'postmark_load_alert_emails','handle_incoming','restricted']
	 : ['postmark_mode', 'postmark_production_key', 'postmark_sandbox_key', 'postmark_tx_from', 'postmark_broadcast_from', 'postmark_tx_slug', 'postmark_broadcast_slug', 'postmark_load_alert_emails','handle_incoming'];
	if(restrictedValue === 1 && rsvpmaker_rest.multisite == "1" && rsvpmaker_rest.domains && rsvpmaker_rest.domains.length > 0) {
		formchildren.push('enabled');
		formchildren.push('sandbox_only');
	}
	const form = {
		fields: [
			{
				id: 'pmsetup',
				label: __( 'Postmark Setup', 'rsvpmaker' ),
				children: formchildren,
				layout: { type: 'card', withHeader: true },
			},
		],
	};

	console.log('rsvpmaker_rest', rsvpmaker_rest);
/*
			<p>Multisite: {rsvpmaker_rest.multisite}</p>
			<p>Postmark Mode: {rsvpmaker_rest.postmark_mode}</p>
			<p>Postmark Root: {rsvpmaker_rest.postmark_root ? 'Yes' : 'No'}</p>
*/

	return (
		<VStack spacing={ 4 }>
			<SettingsTitle />
			{rsvpmaker_rest.multisite && rsvpmaker_rest.multisite > 1 && rsvpmaker_rest.postmark_root ? <p>{__( 'Note: By default, Postmark settings are shared across sites in a WordPress network. The root domain appears to already be set to: ', 'rsvpmaker' ) + rsvpmaker_rest.postmark_mode+'. '+__( 'Only set your credentials here if you want to override the root domain settings.', 'rsvpmaker' )}</p> : null}
			<Notices />
			<DataForm
				data={ postmarkOptions }
				fields={ fields }
				form={ form }
				onChange={ ( edits ) => {
					if ( Array.isArray( edits ) ) {
						return;
					}
					const normalizedEdits = { ...edits };
					if ( Object.prototype.hasOwnProperty.call( normalizedEdits, 'restricted' ) ) {
						normalizedEdits.restricted = Number( normalizedEdits.restricted ) === 1 ? 1 : 0;
					}
					setPostmarkOptions( ( current ) => ( {
						...current,
						...normalizedEdits,
					} ) );
				} }
			/>
			<Notices />
			<SaveButton onClick={ () => { savePostmarkOptions(); } } />
		</VStack>
	);
};

export { PostmarkSettings };
