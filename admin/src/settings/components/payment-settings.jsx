import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { usePaypalOptions, useStripeOptions} from '../hooks';
import { Notices } from './notices';
import {useState, useEffect} from 'react';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Payment Gateway Settings', 'rsvpmaker' ) }
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

const PaymentSettings = () => {
	const [ paypalOptions, setPaypalOptions, savePaypalOptions ] = usePaypalOptions() || [{}, () => {}, () => {}];
	const [ stripeOptions, setStripeOptions, saveStripeOptions ] = useStripeOptions() || [{}, () => {}, () => {}];

	if ( !paypalOptions || !stripeOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const fields = [
		{
			id: 'sk',
			label: __( 'Secret Key', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sandbox_sk',
			label: __( 'Secret Key (Sandbox mode)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'pk',
			label: __( 'Private Key', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sandbox_pk',
			label: __( 'Private Key (Sandbox mode)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'webhook',
			label: __( 'Webhook URL (optional)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
	];

	const form = {
		fields: [
			{
				id: 'stripe',
				label: __( 'Stripe Setup', 'rsvpmaker' ),
				children: [ 'pk', 'sk', 'sandbox_pk', 'sandbox_sk', 'webhook' ],
				layout: { type: 'card', withHeader: true },
			},
		],
	};

		const ppfields = [
		{
			id: 'client_id',
			label: __( 'Client ID', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sandbox_client_id',
			label: __( 'Client ID (Sandbox mode)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'client_secret',
			label: __( 'Client Secret', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sandbox_client_secret',
			label: __( 'Client Secret (Sandbox mode)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'funding_sources',
			label: __( 'Funding Sources (optional)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'excluded_funding_sources',
			label: __( 'Excluded Funding Sources (optional)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sandbox',
			label: __( 'Mode', 'rsvpmaker' ),
			type: 'integer',
			Edit: 'select',
			elements: [
				{ label: __( 'Production', 'rsvpmaker' ), value: 0 },
				{ label: __( 'Sandbox', 'rsvpmaker' ), value: 1 },
			],
		},
	];

	const ppform = {
		fields: [
			{
				id: 'paypal',
				label: __( 'PayPal Setup', 'rsvpmaker' ),
				children: [ 'client_id', 'sandbox_client_id', 'client_secret', 'sandbox_client_secret', 'funding_sources', 'excluded_funding_sources', 'sandbox' ],
				layout: { type: 'card', withHeader: true },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<SettingsTitle />
			<Notices />
			<DataForm
				data={ stripeOptions }
				fields={ fields }
				form={ form }
				onChange={ ( edits ) =>
					setStripeOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<DataForm
				data={ paypalOptions }
				fields={ ppfields }
				form={ ppform }
				onChange={ ( edits ) =>
					setPaypalOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<p>{__( 'Visit the Basic Settings and Defaults screen to set your default payment gateway to PayPal, Stripe, or both.', 'rsvpmaker' )}</p>
			<Notices />
			<SaveButton onClick={ () => { saveStripeOptions(); savePaypalOptions(); } } />
		</VStack>
	);
};

export { PaymentSettings };
