import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { useChimpOptions } from '../hooks';
import { Notices } from './notices';

const SaveButton = ( { label, onClick } ) => (
	<div>
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ label || __( 'Save', 'rsvpmaker' ) }
		</Button>
	</div>
);

const MailchimpSettings = () => {
	const [ chimpOptions, setChimpOptions, saveChimpOptions ] = useChimpOptions() || [ {}, () => {}, () => {} ];

	if ( ! chimpOptions ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}

	const fields = [
		{ id: 'chimp-key', label: __( 'Mailchimp API Key', 'rsvpmaker' ), type: 'string', Edit: 'text' },
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

	const form = {
		fields: [
			{
				id: 'chimp_main',
				label: __( 'Mailchimp Settings', 'rsvpmaker' ),
				children: [ 'chimp-key', 'chimp-list', 'add_notify', 'chimp_add_new_users' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<p>{ __( 'Optional Mailchimp integration for list sync and campaign sending.', 'rsvpmaker' ) }</p>
			<Notices />
			<SaveButton onClick={ () => { saveChimpOptions(); } } />
			<DataForm
				data={ chimpOptions }
				fields={ fields }
				form={ form }
				onChange={ ( edits ) =>
					setChimpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<Notices />
			<SaveButton onClick={ () => { saveChimpOptions(); } } />
		</VStack>
	);
};

export { MailchimpSettings };
