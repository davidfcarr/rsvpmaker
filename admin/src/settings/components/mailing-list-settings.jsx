import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { useChimpOptions, useRsvpOptions } from '../hooks';
import { Notices } from './notices';

const SaveButton = ( { label, onClick } ) => (
	<div>
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ label || __( 'Save', 'rsvpmaker' ) }
		</Button>
	</div>
);

const MailingListSettings = () => {
	const [ rsvpOptions, setRsvpOptions, saveRsvpOptions ] = useRsvpOptions() || [ {}, () => {}, () => {} ];
	const [ chimpOptions, setChimpOptions, saveChimpOptions ] = useChimpOptions() || [ {}, () => {}, () => {} ];

	if ( ! rsvpOptions || ! chimpOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const smtpOptions = [
		{ label: __( 'None - use wp_mail()', 'rsvpmaker' ), value: '' },
		{ label: __( 'Local Server or Custom', 'rsvpmaker' ), value: 'other' },
		{ label: 'Gmail', value: 'gmail' },
		{ label: 'Sendgrid', value: 'sendgrid' },
	];

	const chimpFields = [
		{ id: 'company', label: __( 'Company/Organization', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'mailing_address', label: __( 'Mailing Address', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'chimp-key', label: __( 'Mailchimp API Key', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'email-name', label: __( 'Email Name', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'email-from', label: __( 'Email From Address', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{
			id: 'chimp-list',
			label: __( 'Default Mailchimp List', 'rsvpmaker' ),
			type: 'string',
			elements: chimpOptions.chimp_lists || [ { label: __( 'No lists detected', 'rsvpmaker' ), value: '' } ],
			Edit: 'select',
		},
		{ id: 'add_notify', label: __( 'Notification Email', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'chimp_add_new_users', label: __( 'Add New WordPress User Emails to Mailchimp', 'rsvpmaker' ), type: 'boolean', Edit: 'toggle' },
	];

	const chimpForm = {
		fields: [
			{
				id: 'chimp_main',
				label: __( 'Mailing List Settings', 'rsvpmaker' ),
				children: [ 'company', 'mailing_address', 'chimp-key', 'email-name', 'email-from', 'chimp-list', 'add_notify', 'chimp_add_new_users' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	const smtpFields = [
		{ id: 'smtp', label: __( 'SMTP Provider', 'rsvpmaker' ), type: 'string', elements: smtpOptions, Edit: 'select' },
		{ id: 'smtp_useremail', label: __( 'SMTP User Email', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_username', label: __( 'SMTP Username', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_password', label: __( 'SMTP Password', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_server', label: __( 'SMTP Server', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_port', label: __( 'SMTP Port', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_prefix', label: __( 'SMTP Prefix (ssl, tls)', 'rsvpmaker' ), type: 'string', Edit: 'text' },
	];

	const smtpForm = {
		fields: [
			{
				id: 'smtp_main',
				label: __( 'SMTP Settings (optional, not needed if Postmark is active)', 'rsvpmaker' ),
				children: [ 'smtp', 'smtp_useremail', 'smtp_username', 'smtp_password', 'smtp_server', 'smtp_port', 'smtp_prefix' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<p>
				{ __( 'Configure settings used for mailing-list and broadcast workflows (Postmark, Mailchimp, and SMTP).', 'rsvpmaker' ) }
			</p>
			<Notices />
			<SaveButton onClick={ () => { saveChimpOptions(); saveRsvpOptions(); } } />
			<DataForm
				data={ chimpOptions }
				fields={ chimpFields }
				form={ chimpForm }
				onChange={ ( edits ) =>
					setChimpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<DataForm
				data={ rsvpOptions }
				fields={ smtpFields }
				form={ smtpForm }
				onChange={ ( edits ) =>
					setRsvpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<Notices />
			<SaveButton onClick={ () => { saveChimpOptions(); saveRsvpOptions(); } } />
		</VStack>
	);
};

export { MailingListSettings };
