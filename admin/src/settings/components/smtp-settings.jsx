import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { useRsvpOptions } from '../hooks';
import { Notices } from './notices';

const SaveButton = ( { label, onClick } ) => (
	<div>
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ label || __( 'Save', 'rsvpmaker' ) }
		</Button>
	</div>
);

const SmtpSettings = () => {
	const [ rsvpOptions, setRsvpOptions, saveRsvpOptions ] = useRsvpOptions() || [ {}, () => {}, () => {} ];

	if ( ! rsvpOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const smtpOptions = [
		{ label: __( 'None - use wp_mail()', 'rsvpmaker' ), value: '' },
		{ label: __( 'Local Server or Custom', 'rsvpmaker' ), value: 'other' },
		{ label: 'Gmail', value: 'gmail' },
		{ label: 'Sendgrid', value: 'sendgrid' },
	];

	const fields = [
		{ id: 'smtp', label: __( 'SMTP Provider', 'rsvpmaker' ), type: 'string', elements: smtpOptions, Edit: 'select' },
		{ id: 'smtp_useremail', label: __( 'SMTP User Email', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_username', label: __( 'SMTP Username', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_password', label: __( 'SMTP Password', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_server', label: __( 'SMTP Server', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_port', label: __( 'SMTP Port', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'smtp_prefix', label: __( 'SMTP Prefix (ssl, tls)', 'rsvpmaker' ), type: 'string', Edit: 'text' },
	];

	const form = {
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
			<p>{ __( 'Optional SMTP settings used when Postmark is not active.', 'rsvpmaker' ) }</p>
			<Notices />
			<SaveButton onClick={ () => { saveRsvpOptions(); } } />
			<DataForm
				data={ rsvpOptions }
				fields={ fields }
				form={ form }
				onChange={ ( edits ) =>
					setRsvpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<Notices />
			<SaveButton onClick={ () => { saveRsvpOptions(); } } />
		</VStack>
	);
};

export { SmtpSettings };
