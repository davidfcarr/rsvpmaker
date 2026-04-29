import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews/wp';
import { useRsvpOptions, useChimpOptions} from '../hooks';
import { Notices } from './notices';
import {useState, useEffect} from 'react';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'RSVPMaker General Settings', 'rsvpmaker' ) }
		</Heading>
	);
};

const EditLink = (props) => {
	const [postContent, setPostContent] = useState('<p>Loading...</p>');
	const post_id = props.data[props.field.id];
	useEffect(() => {
		fetch('/wp-json/rsvpmaker/v1/preview/do_blocks?post_id='+post_id).then((response) => response.json()).then((data) => { setPostContent(data); }).catch((error) => { console.error('Error fetching block content:', error); });
	}, [post_id]);
	return (
		<div>
		<p><strong>{props.field.label}</strong> <a target="_blank" href={'/wp-admin/post.php?post='+post_id+'&action=edit'}>Edit</a> {props.field.description}</p>
		<div>Preview:</div>
		<div dangerouslySetInnerHTML={{ __html: postContent }} />
		</div>
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

const RsvpmakerSettings = () => {
	const [ rsvp_options, setRsvpOptions, saveRsvpOptions ] = useRsvpOptions() || [{}, () => {}, () => {}];
	const [ chimpOptions, setChimpOptions, saveChimpOptions ] = useChimpOptions() || [{}, () => {}, () => {}];
	const [filter, setFilter] = useState({});
	const { createSuccessNotice, removeAllNotices, removeNotice } = useDispatch( noticesStore );
	async function myCopyDefaults(filter = {}) {
		const selectedFilters = Array.isArray(filter)
			? filter.filter((item) => item.value).map((item) => item.id)
			: Object.keys(filter || {}).filter((key) => !!filter[key]);

		const url = new URL('/wp-json/rsvpmaker/v1/copy_defaults', window.location.origin);
		selectedFilters.forEach((item) => {
			url.searchParams.append('filter[]', item);
		});

		//not in editor context, rsvpmaker_rest should be available.
		const response = await fetch(url.toString(), {
			method: 'GET',
			headers: {
				'X-WP-Nonce': rsvpmaker_rest.nonce,
				'Content-Type': 'application/json'
			}
		});
		const answer = await response.json();
		const noticeId = `copy-${ Date.now() }`;

		if(answer.updated) {
			console.log(answer);
			let updated = answer.updated;
			if(updated.length > 500)
				updated = updated.substring(0,500)+' ...';
			removeAllNotices();
			createSuccessNotice(updated, { id: noticeId, isDismissible: true } );
		}
		else {
			createSuccessNotice('Nothing updated', { id: noticeId, isDismissible: true } );
		}
		setTimeout(() => { removeNotice(noticeId); }, 5000);
	}

	if ( !rsvp_options ) {
		return <div>{ __( 'Loading...', 'rsvpmaker' ) }</div>;
	}
    const smtp_options = [{'label':'None - use wp_mail()','value':''},{'label':'Local Server or Custom','value':'other'},{'label':'Gmail','value':'gmail'},{'label':'Sendgrid','value':'sendgrid'}];

	const fields = [
		{
			id: 'rsvp_on',
			label: __( 'RSVP On (collect registrations)', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'calendar_icons',
			label: __( 'Show Add to Calendar Icons', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'add_timezone',
			label: __( 'Show Timezone', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'convert_timezone',
			label: __( 'Convert Timezone', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'rsvp_yesno',
			label: __( 'Show Yes/No at Top of RSVP Form', 'rsvpmaker' ),
			description: __( 'Provide option to say yes or no (if turned off, "yes" is assumed)', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'social_title_date',
			label: __( 'Show Social Title Date', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'confirmation_include_event',
			label: __( 'Include Event in Confirmation', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'rsvpmaker_send_confirmation_email',
			label: __( 'Send Confirmation Email', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'rsvp_count',
			label: __( 'Show RSVP Count', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
      Edit: 'toggleGroup',
      elements: [
        {
          label: '12 Hour AM/PM',
          value: 'g:i A'
        },
        {
          label: '24 Hour',
          value: 'H:i'
        },
        {
          label: '12 Hour AM/PM with Timezone',
          value: 'g:i A T'
        },
        {
          label: '24 Hour AM/PM with Timezone',
          value: 'H:i T'
        }
      ],
      id: 'time_format',
      label: 'Time Format',
      type: 'text'
    	},
		{
			id: 'rsvp_form_title',
			label: __( 'RSVP Form Title', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'update_rsvp',
			label: __( 'RSVP Update Button Label', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'rsvp_instructions',
			label: __( 'RSVP Form Instructions', 'rsvpmaker' ),
			type: 'string',
			Edit: 'textarea',
		},
		{
			id: 'rsvp_to',
			label: __( 'RSVP To Email', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'rsvp_count_party',
			label: __( 'Show RSVP Count Party', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'send_payment_reminders',
			label: __( 'Send Payment Reminders', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'login_required',
			label: __( 'Login Required', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'rsvp_captcha',
			label: __( 'Basic CAPTCHA (legacy)', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'show_attendees',
			label: __( 'Show Attendees', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},		
		{
			id: 'debug',
			label: __( 'Debug (capture errors)', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
		{
			id: 'long_date',
			label: __( 'Long Date Format *', 'rsvpmaker' ),
			description: __( 'Format for displaying long dates.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'short_date',
			label: __( 'Short Date Format *', 'rsvpmaker' ),
			description: __( 'Format for displaying short dates.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'dashboard',
			label: __( 'Dashboard Widget', 'rsvpmaker' ),
			description: __( 'Option to display a custom message on the dashboard.', 'rsvpmaker' ),
			type: 'string',
			elements: [{'label':'None','value':''},{'label':'Show on Dashboard','value':'show'},{'label':'Show on Top','value':'top'}],
			Edit: 'select',
		},
		{
			id: 'dashboard_message',
			label: __( 'Dashboard Message', 'rsvpmaker' ),
			description: __( 'Message displayed on the dashboard.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'textarea',
		},
		{
			id: 'defaulthour',
			label: __( 'Default Hour', 'rsvpmaker' ),
			description: __( 'Default hour for event times (24-hour format, 13 for 1 pm).', 'rsvpmaker' ),
			type: 'string',
			elements: [
				{
				label: '1 AM 01:00',
				value: '01'
				},
				{
				label: '2 AM 02:00',
				value: '02'
				},
				{
				label: '3 AM 03:00',
				value: '03'
				},
				{
				label: '4 AM 04:00',
				value: '04'
				},
				{
				label: '5 AM 05:00',
				value: '05'
				},
				{
				label: '6 AM 06:00',
				value: '06'
				},
				{
				label: '7 AM 07:00',
				value: '07'
				},
				{
				label: '8 AM 08:00',
				value: '08'
				},
				{
				label: '9 AM 09:00',
				value: '09'
				},
				{
				label: '10 AM 10:00',
				value: '10'
				},
				{
				label: '11 AM 11:00',
				value: '11'
				},
				{
				label: '12 PM 12:00',
				value: '12'
				},
				{
				label: '1 PM 13:00',
				value: '13'
				},
				{
				label: '2 PM 14:00',
				value: '14'
				},
				{
				label: '3 PM 15:00',
				value: '15'
				},
				{
				label: '4 PM 16:00',
				value: '16'
				},
				{
				label: '5 PM 17:00',
				value: '17'
				},
				{
				label: '6 PM 18:00',
				value: '18'
				},
				{
				label: '7 PM 19:00',
				value: '19'
				},
				{
				label: '8 PM 20:00',
				value: '20'
				},
				{
				label: '9 PM 21:00',
				value: '21'
				},
				{
				label: '10 PM 22:00',
				value: '22'
				},
				{
				label: '11 PM 23:00',
				value: '23'
				},
				{
				label: '12 AM 00:00',
				value: '00'
				},
			],
			Edit: 'select',
		},		
		{
			id: 'payment_gateway',
			label: __( 'Payment Gateway', 'rsvpmaker' ),
			description: __( 'Select the default payment gateway. Gateway credentials must be set on the Payments tab.', 'rsvpmaker' ),
			type: 'string',
			elements: [
				{label: 'Cash or Custom', value: 'Cash or Custom'},
				{label: 'PayPal REST API', value: 'PayPal REST API'},
				{label: 'Stripe', value: 'Stripe'},
				{label: 'Both Stripe and PayPal', value: 'Both Stripe and PayPal'},
			],
			Edit: 'select',
		},		
		{
			id: 'defaulthour',
			label: __( 'Default Hour', 'rsvpmaker' ),
			description: __( 'Default hour for event times (24-hour format, 13 for 1 pm).', 'rsvpmaker' ),
			type: 'string',
			elements: [
				{
				label: '1 AM 01:00',
				value: '01'
				},
				{
				label: '2 AM 02:00',
				value: '02'
				},
				{
				label: '3 AM 03:00',
				value: '03'
				},
				{
				label: '4 AM 04:00',
				value: '04'
				},
				{
				label: '5 AM 05:00',
				value: '05'
				},
				{
				label: '6 AM 06:00',
				value: '06'
				},
				{
				label: '7 AM 07:00',
				value: '07'
				},
				{
				label: '8 AM 08:00',
				value: '08'
				},
				{
				label: '9 AM 09:00',
				value: '09'
				},
				{
				label: '10 AM 10:00',
				value: '10'
				},
				{
				label: '11 AM 11:00',
				value: '11'
				},
				{
				label: '12 PM 12:00',
				value: '12'
				},
				{
				label: '1 PM 13:00',
				value: '13'
				},
				{
				label: '2 PM 14:00',
				value: '14'
				},
				{
				label: '3 PM 15:00',
				value: '15'
				},
				{
				label: '4 PM 16:00',
				value: '16'
				},
				{
				label: '5 PM 17:00',
				value: '17'
				},
				{
				label: '6 PM 18:00',
				value: '18'
				},
				{
				label: '7 PM 19:00',
				value: '19'
				},
				{
				label: '8 PM 20:00',
				value: '20'
				},
				{
				label: '9 PM 21:00',
				value: '21'
				},
				{
				label: '10 PM 22:00',
				value: '22'
				},
				{
				label: '11 PM 23:00',
				value: '23'
				},
				{
				label: '12 AM 00:00',
				value: '00'
				},
			],
			Edit: 'select',
		},		
		{
			id: 'defaultmin',
			label: __( 'Default Minute', 'rsvpmaker' ),
			description: __( 'Default minutes for event times (00-59).', 'rsvpmaker' ),
			type: 'string',
			elements: [
				{
				label: '00',
				value: '00'
				},
				{
				label: '01',
				value: '01'
				},
				{
				label: '02',
				value: '02'
				},
				{
				label: '03',
				value: '03'
				},
				{
				label: '04',
				value: '04'
				},
				{
				label: '05',
				value: '05'
				},
				{
				label: '06',
				value: '06'
				},
				{
				label: '07',
				value: '07'
				},
				{
				label: '08',
				value: '08'
				},
				{
				label: '09',
				value: '09'
				},
				{
				label: '10',
				value: '10'
				},
				{
				label: '15',
				value: '15'
			},
				{
				label: '16',
				value: '16'
				},
				{
				label: '21',
				value: '21'
				},
				{
				label: '22',
				value: '22'
				},
				{
				label: '23',
				value: '23'
				},
				{
				label: '24',
				value: '24'
				},
				{label: '25',
				value: '25'
				},
				{
				label: '26',
				value: '26'
				},
				{
				label: '27',
				value: '27'
				},
				{
				label: '28',
				value: '28'
				},
				{
				label: '29',
				value: '29'
				},
				{
				label: '30',
				value: '30'
				},
				{
				label: '31',
				value: '31'
				},
				{
				label: '32',
				value: '32'
				},
				{
				label: '33',
				value: '33'
				},
				{
				label: '34',
				value: '34'
				},
				{
				label: '35',
				value: '35'
				},
				{
				label: '36',
				value: '36'
				},
				{
				label: '37',
				value: '37'
				},
				{
				label: '38',
				value: '38'
				},
				{
				label: '39',
				value: '39'
				},
				{
				label: '40',
				value: '40'
				},
				{
				label: '41',
				value: '41'
				},
				{
				label: '42',
				value: '42'
				},
				{
				label: '43',
				value: '43'
				},
				{
				label: '44',
				value: '44'
				},
				{
				label: '45',
				value: '45'
				},
				{
				label: '46',
				value: '46'
				},
				{
				label: '47',
				value: '47'
				},
				{
				label: '48',
				value: '48'
				},
				{
				label: '49',
				value: '49'
				},
				{
				label: '50',
				value: '50'
				},
				{
				label: '51',
				value: '51'
				},
				{
				label: '52',
				value: '52'
				},
				{
				label: '53',
				value: '53'
				},
				{
				label: '54',
				value: '54'
				},
				{
				label: '55',
				value: '55'
				},
				{
				label: '56',
				value: '56'
				},
				{
				label: '57',
				value: '57'
				},
				{
				label: '58',
				value: '58'
				},
				{
				label: '59',
				value: '59'
				},
			],
			Edit: 'select',	
		},		
		{
			id: 'rsvp_max',
			label: __( 'RSVP Max', 'rsvpmaker' ),
			description: __( 'Maximum number of RSVPs allowed per event (0 for unlimited).', 'rsvpmaker' ),
			type: 'number',
			Edit: 'text',
		},		
		{
			id: 'rsvp_confirm',
			label: __( 'Default Confirmation Message', 'rsvpmaker' ),
			description: __( 'Message displayed after RSVP submission, sent via email. Can be changed in the template or per event.', 'rsvpmaker' ),
			type: 'integer',
			Edit: EditLink,
		},
		{
			id: 'rsvp_form',
			label: __( 'Default RSVP Form', 'rsvpmaker' ),
			description: __( 'Form displayed for RSVP submission. Can be changed in the template or per event.', 'rsvpmaker' ),
			type: 'integer',
			Edit: EditLink,
		},
		{
			id: 'rsvp_button',
			label: __( 'RSVP Button', 'rsvpmaker' ),
			description: __( 'Button displayed for link to RSVP form. You can edit the colors and formatting.', 'rsvpmaker' ),
			type: 'integer',
			Edit: EditLink,
		},	
		{
			id: 'rsvp_max',
			label: __( 'RSVP Max', 'rsvpmaker' ),
			description: __( 'Maximum number of RSVPs allowed per event (0 for unlimited).', 'rsvpmaker' ),
			type: 'number',
			Edit: 'text',
		},		
		{
			id: 'rsvp_recaptcha_site_key',
			label: __( 'reCAPTCHA Site Key **', 'rsvpmaker' ),
			description: __( 'Site key for Google reCAPTCHA integration.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'rsvp_recaptcha_secret',
			label: __( 'reCAPTCHA Secret Key **', 'rsvpmaker' ),
			description: __( 'Secret key for Google reCAPTCHA integration.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'paypal_currency',
			label: __( 'Payment Currency', 'rsvpmaker' ),
			description: __( 'Currency used for fees and payments.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'currency_decimal',
			label: __( 'Currency Decimal', 'rsvpmaker' ),
			description: __( 'Decimal separator used for fees and payments.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'currency_thousands',
			label: __( 'Currency Thousands Separator', 'rsvpmaker' ),
			description: __( 'Thousands separator used for fees and payments (defaults to comma).', 'rsvpmaker'),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'payment_minimum',
			label: __( 'Payment Minimum', 'rsvpmaker' ),
			description: __( 'Minimum payment amount required when a fee is collected for an RSVP.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},		
		{
			id: 'smtp',
			label: __( 'SMTP (optional, not needed if Postmark is active)', 'rsvpmaker' ),
			description: __( 'Select the SMTP service for sending emails.', 'rsvpmaker' ),
			type: 'string',
			elements: smtp_options,
			Edit: 'select',
		},
		{
			id: 'smtp_useremail',
			label: __( 'SMTP User Email', 'rsvpmaker' ),
			description: __( 'Email address used for SMTP authentication.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'smtp_username',
			label: __( 'SMTP Username', 'rsvpmaker' ),
			description: __( 'Username used for SMTP authentication.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'smtp_password',
			label: __( 'SMTP Password', 'rsvpmaker' ),
			description: __( 'Password used for SMTP authentication.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'smtp_server',
			label: __( 'SMTP Server', 'rsvpmaker' ),
			description: __( 'SMTP server used for sending emails.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'smtp_port',
			label: __( 'SMTP Port', 'rsvpmaker' ),
			description: __( 'Port used for SMTP server.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'smtp_prefix',
			label: __( 'SMTP Prefix', 'rsvpmaker' ),
			description: __( 'Prefix (ssl, tls)', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'report_security',
			label: __( 'Access to RSVP Report', 'rsvpmaker' ),
			type: 'string',
			Edit: 'select',
			elements: [
				{ label: __( 'Administrators (manage_options)', 'rsvpmaker' ), value: 'manage_options' },
				{ label: __( 'Editors (edit_others_rsvpmakers)', 'rsvpmaker' ), value: 'edit_others_rsvpmakers' },
				{ label: __( 'Authors (publish_rsvpmakers)', 'rsvpmaker' ), value: 'publish_rsvpmakers' },
				{ label: __( 'Contributors (edit_rsvpmakers)', 'rsvpmaker' ), value: 'edit_rsvpmakers' },
				{ label: __( 'Logged in Users (read)', 'rsvpmaker' ), value: 'read' },
			],
		},
	]

	const form = {
		fields: [
			{
				id: 'defaults',
				label: __( 'Alternatives', 'rsvpmaker' ),
				children: [ 'rsvp_on','calendar_icons','add_timezone','convert_timezone','rsvp_yesno','social_title_date','confirmation_include_event','rsvpmaker_send_confirmation_email','rsvp_count','rsvp_count_party','send_payment_reminders',
'login_required','show_attendees' ],
				layout: { type: 'card', withHeader: true },
			},
			{
				id: 'options',
				label: __( 'Options', 'rsvpmaker' ),
				children: [ 'long_date','short_date','defaulthour','defaultmin','time_format','rsvp_form_title','update_rsvp','rsvp_instructions','rsvp_to', 'rsvp_max','report_security','rsvp_recaptcha_site_key','rsvp_recaptcha_secret','rsvp_captcha' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	const form2 = {
		fields: [
			{
				id: 'options2',
				label: __( 'Options', 'rsvpmaker' ),
				children: [ 'payment_gateway','payment_minimum', 'paypal_currency', 'currency_decimal', 'currency_thousands', 'dashboard', 'dashboard_message','smtp' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
			{
				id: 'docs',
				label: __( 'Related Documents - RSVP Button, RSVP Form, and Confirmation', 'rsvpmaker' ),
				children: [ 'rsvp_confirm', 'rsvp_button','rsvp_form' ],
				layout: { type: 'card', isOpened: false, withHeader: true },
			},
			{
				id: 'smtp_settings',
				label: __( 'SMTP Settings (optional, not needed if Postmark is active)', 'rsvpmaker' ),
				children: [ 'smtp_useremail', 'smtp_username', 'smtp_password', 'smtp_server', 'smtp_port', 'smtp_prefix' ],
				layout: { type: 'card', isOpened: false, withHeader: true },
			},
		],
	};

	console.log('chimpOptions',chimpOptions);
	const chimpfields = [
		{
			id: 'company',
			label: __( 'Company/Organization', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'mailing_address',
			label: __( 'Mailing Address', 'rsvpmaker' ),
			description: __( 'Providing a Company/Organization name and mailing address is recommended for anti-spam compliance.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'chimp-key',
			label: __( 'Mailchimp API Key', 'rsvpmaker' ),
			description: __( 'API key for Mailchimp integration.', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'email-from',
			label: __( 'Email From', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'email-name',
			label: __( 'Email Name', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'chimplist',
			label: __( 'Default Mailchimp List', 'rsvpmaker' ),
			type: 'string',
			elements: chimpOptions.chimp_lists || [{label: 'No lists detected', value: ''}],
			Edit: 'select',
		},
		{
			id: 'add_notify',
			label: __( 'Notification Email', 'rsvpmaker' ),
			type: 'string',
			Edit: 'text',
		},
		{
			id: 'chimp_add_new_users',
			label: __( 'Add New WordPress User Emails to Mailchimp', 'rsvpmaker' ),
			type: 'boolean',
			Edit: 'toggle',
		},
	]
 
	const chimpform = {
		fields: [
			{
				id: 'email_essentials',
				label: __( 'Email Essentials', 'rsvpmaker' ),
				children: [ 'company','mailing_address' ],
				layout: { type: 'card', withHeader: true },
			},
			{
				id: 'chimpoptions',
				label: __( 'Mailchimp Setup (optional)', 'rsvpmaker' ),
				children: [ 'chimp-key','email-from','email-name','chimplist','add_notify','chimp_add_new_users' ],
				layout: { type: 'card', isOpened: false, withHeader: true },
			},
		],
	};

	const exclude = ['social_title_date','time_format', 'rsvp_form_title', 'update_rsvp','debug', 'long_date', 'short_date','rsvp_recaptcha_site_key', 'rsvp_recaptcha_secret', 'paypal_currency', 'currency_decimal', 'currency_thousands', 'payment_minimum'];
	const filterFields = [];
	const filterChildren = [];
	fields.forEach((field) => {
		if(exclude.includes(field.id))
			return;
		filterFields.push({id: field.id,label: field.label, type: 'boolean', Edit: 'toggle'});
		filterChildren.push(field.id);
	});
	const filterData = {};
	filterChildren.forEach((id) => {
		filterData[id] = !!filter[id];
	});
	const filterForm = {
		fields: [
			{
				id: 'filters',
				label: __( 'Selective Copy Checked Fields (Default is to copy all)', 'rsvpmaker' ),
				children: filterChildren,
				layout: { type: 'card', withHeader: true, isOpened: false },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<SettingsTitle />
			<div id="floating-save" style={{  width: '60%', textAlign: 'left', padding: '5px', position: 'fixed', bottom: '50px', left: '200px', zIndex: 100,}}>
			<Notices />
			<div style={{ display: 'inline-block', backgroundColor: 'white', padding: '10px', borderRadius: '5px', boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)' }}>
			<SaveButton onClick={ () => {saveRsvpOptions(); saveChimpOptions();} } />
			</div>
			</div>
			<DataForm
				data={ rsvp_options }
				fields={ fields }
				form={ form }
				onChange={ ( edits ) =>
					setRsvpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<DataForm
				data={ rsvp_options }
				fields={ fields }
				form={ form2 }
				onChange={ ( edits ) =>
					setRsvpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<DataForm
				data={ chimpOptions }
				fields={ chimpfields }
				form={ chimpform }
				onChange={ ( edits ) =>
					setChimpOptions( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<p>{__('* For date format settings, see','rsvpmaker')}: <a href="https://www.php.net/manual/en/function.date.php" target="_blank" rel="noopener noreferrer">{__('PHP date() function documentation','rsvpmaker')}</a></p>
			<p>{__('** For reCAPTCHA credentials, see','rsvpmaker')}: <a href="https://console.cloud.google.com/security/recaptcha" target="_blank" rel="noopener noreferrer">{__('Google Cloud reCAPTCHA dashboard','rsvpmaker')}</a></p>
			<h2>{__('Copy Defaults to Events','rsvpmaker')}</h2>
			<DataForm
				data={ filterData }
				fields={ filterFields }
				form={ filterForm }
				onChange={ ( edits ) =>
					setFilter( ( current ) => ( {
						...current,
						...edits,
					} ) )
				}
			/>
			<Notices />
			<SaveButton label={__('Copy Defaults', 'rsvpmaker')} onClick={ () => {myCopyDefaults(filter)} } />
		</VStack>
	);
};

export { RsvpmakerSettings };
