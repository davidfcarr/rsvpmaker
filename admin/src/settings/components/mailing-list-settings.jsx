import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { useFromOptions } from '../hooks';
import { Notices } from './notices';

const SaveButton = ( { label, onClick } ) => (
	<div>
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ label || __( 'Save', 'rsvpmaker' ) }
		</Button>
	</div>
);

const MailingListSettings = () => {
	const [ fromOptions, setFromOptions, saveFromOptions ] = useFromOptions() || [ {}, () => {}, () => {} ];

	if ( ! fromOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const fromFields = [
		{ id: 'email-from', label: __( 'Email or Reply-To Address for Sender', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'email-name', label: __( 'Email Name', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'company', label: __( 'Company/Organization', 'rsvpmaker' ), type: 'string', Edit: 'text' },
		{ id: 'mailing_address', label: __( 'Mailing Address', 'rsvpmaker' ), type: 'string', Edit: 'text' },
	];

	const fromForm = {
		fields: [
			{
				id: 'from_main',
				label: __( 'Email Basic Settings', 'rsvpmaker' ),
				children: [ 'email-from', 'email-name', 'company', 'mailing_address' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<p>
				{ __( 'These details are used as your sender identity and footer placeholders in emails.', 'rsvpmaker' ) }
			</p>
			<p><em>{ __( 'Providing a company/organization name and physical mailing address is recommended for anti-spam compliance.', 'rsvpmaker' ) }</em></p>
			<Notices />
			<SaveButton onClick={ () => { saveFromOptions(); } } />
			<DataForm
				data={ fromOptions }
				fields={ fromFields }
				form={ fromForm }
				onChange={ ( edits ) =>
					setFromOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<Notices />
			<SaveButton onClick={ () => { saveFromOptions(); } } />
		</VStack>
	);
};

export { MailingListSettings };
