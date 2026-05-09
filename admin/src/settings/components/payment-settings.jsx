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
	const restRoot = window?.rsvpmaker_rest?.rest_url || '/wp-json/';
	const paypalWebhookUrl = `${ restRoot.replace( /\/?$/, '/' ) }rsvpmaker/v1/paypal_webhook`;

	if ( !paypalOptions || !stripeOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const fields = [
		{
			id: 'pk',
			label: __( 'Private Key', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'sk',
			label: __( 'Secret Key', 'rsvpmaker' ),
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
			id: 'sandbox_sk',
			label: __( 'Secret Key (Sandbox mode)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'webhook',
			label: __( 'Webhook Signing Secret (optional)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'mode',
			label: __( 'Mode', 'rsvpmaker' ),
			type: 'string',
			Edit: 'select',
			elements: [
				{ label: __( 'Production', 'rsvpmaker' ), value: 'production' },
				{ label: __( 'Sandbox', 'rsvpmaker' ), value: 'sandbox' },
			],
		},
	];

	const form = {
		fields: [
			{
				id: 'stripe',
				label: __( 'Stripe Setup', 'rsvpmaker' ),
				children: [ 'pk', 'sk', 'sandbox_pk', 'sandbox_sk', 'webhook', 'mode' ],
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
			id: 'client_secret',
			label: __( 'Client Secret', 'rsvpmaker' ),
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
				children: [ 'client_id', 'client_secret', 'sandbox_client_id', 'sandbox_client_secret', 'funding_sources', 'excluded_funding_sources', 'sandbox' ],
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
			<p>{ __( 'Stripe webhook values are signing secrets from Stripe, not callback URLs.', 'rsvpmaker' ) }</p>
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
			<div style={{ backgroundColor: '#f0f0f0', padding: '12px', borderRadius: '4px', marginTop: '8px' }}>
				<p style={{ marginBottom: '8px', marginTop: 0 }}>{ __( 'PayPal webhook endpoint for this site (copy into your PayPal app webhook settings):', 'rsvpmaker' ) }</p>
				<code style={{ display: 'block', padding: '8px', backgroundColor: '#fff', border: '1px solid #ddd', borderRadius: '4px', wordBreak: 'break-all', fontSize: '12px' }}>
					{ paypalWebhookUrl }
				</code>
				<p style={{ marginBottom: 0, marginTop: '8px' }}>{ __( 'This URL is generated automatically from your site address.', 'rsvpmaker' ) }</p>
			</div>
			<p>{__( 'Visit the Basic Settings and Defaults screen to set your default payment gateway to PayPal, Stripe, or both.', 'rsvpmaker' )}</p>
			<Notices />
			<SaveButton onClick={ () => { saveStripeOptions(); savePaypalOptions(); } } />
		</VStack>
	);
};

export { PaymentSettings };
