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
			{ __( 'RSVPMaker Confirmation Message (Default)', 'rsvpmaker' ) }
		</Heading>
	);
};

const EditLink = (props) => {
	const [postContent, setPostContent] = useState('<p>Loading...</p>');
	const post_id = props.data[props.field.id];
	useEffect(() => {
		const previewUrl = rsvpmaker_rest.rest_url + 'rsvpmaker/v1/preview/do_blocks?post_id=' + post_id;
		fetch(previewUrl).then((response) => response.json()).then((data) => { setPostContent(data); }).catch((error) => { console.error('Error fetching block content:', error); });
	}, [post_id]);
	return (
		<div>
		<p><strong>{props.field.label}</strong> <a target="_blank" href={'/wp-admin/post.php?post='+post_id+'&action=edit'}>Edit</a> {props.field.description}</p>
		<div>Preview:</div>
		<div dangerouslySetInnerHTML={{ __html: postContent }} />
		</div>
	);
};

const DefaultDiff = () => {
	const [diffItems, setDiffItems] = useState(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [isResetting, setIsResetting] = useState(false);
	const { createSuccessNotice } = useDispatch( noticesStore );

	async function fetchDiff() {
		setLoading(true);
		setError(null);
		try {
			const response = await fetch(rsvpmaker_rest.rest_url + 'rsvpmaker/v1/default_diff');
			if (!response.ok) {
				throw new Error('Failed to fetch');
			}
			const data = await response.json();
			setDiffItems(Array.isArray(data.confirmation) ? data.confirmation : []);
		} catch (err) {
			setError(err.message);
		} finally {
			setLoading(false);
		}
	}

	async function resetConfirmationDefaults() {
		setIsResetting(true);
		setError(null);
		try {
			const url = new URL('rsvpmaker/v1/copy_defaults', rsvpmaker_rest.rest_url);
			url.searchParams.append('filter[]', 'rsvp_confirm');

			const response = await fetch(url.toString(), {
				method: 'GET',
				headers: {
					'X-WP-Nonce': rsvpmaker_rest.nonce,
					'Content-Type': 'application/json',
				},
			});
			const answer = await response.json();
			createSuccessNotice(answer?.updated || __( 'Confirmation messages reset to default.', 'rsvpmaker' ), {
				isDismissible: true,
			});
			await fetchDiff();
		} catch (err) {
			setError(err.message);
		} finally {
			setIsResetting(false);
		}
	}

	useEffect(() => {
		fetchDiff();
	}, []);

	if (loading) return <p>{ __( 'Loading customized confirmation messages…', 'rsvpmaker' ) }</p>;
	if (error) return <p>{ __( 'Error loading diff: ', 'rsvpmaker' ) }{ error }</p>;
	if (!diffItems || diffItems.length === 0) return <p>{ __( 'No events have a confirmation message that differs from the default.', 'rsvpmaker' ) }</p>;

	return (
		<div>
			<Heading level={ 2 }>{ __( 'Custom Confirmation Messages', 'rsvpmaker' ) }</Heading>
			<Button
				variant="secondary"
				onClick={ resetConfirmationDefaults }
				disabled={ isResetting }
				__next40pxDefaultSize
			>
				{ isResetting
					? __( 'Resetting...', 'rsvpmaker' )
					: __( 'Set All to Default Confirmation', 'rsvpmaker' ) }
			</Button>
			<ul>
				{ diffItems.map((item) => (
					<li key={ item.event_id }>{ item.title ?? __( '(Untitled)', 'rsvpmaker' ) }
						<li>
						<a target="_blank" href={ '/wp-admin/post.php?post=' + item.event_id + '&action=edit' }>
							{ __( 'Edit Event', 'rsvpmaker' ) }
						</a></li>
						<li> 
						<a target="_blank" href={ '/wp-admin/post.php?post=' + item.document_id + '&action=edit' }>
							{ __( 'Edit Confirmation Message', 'rsvpmaker' ) }
						</a>
						</li>
					</li>
				)) }
			</ul>
		</div>
	);
};

const ConfirmationSettings = () => {
	const [ rsvp_options, setRsvpOptions, saveRsvpOptions ] = useRsvpOptions() || [{}, () => {}, () => {}];

	const fields = [
		{
			id: 'rsvp_confirm',
			label: __( 'Default Confirmation Message', 'rsvpmaker' ),
			description: __( 'Message displayed after RSVP submission, sent via email. Can be changed in the template or per event.', 'rsvpmaker' ),
			type: 'integer',
			Edit: EditLink,
		}
	]

	const form = {
		fields: [
			{
				id: 'docs',
				label: __( 'Default Message', 'rsvpmaker' ),
				children: [ 'rsvp_confirm' ],
				layout: { type: 'card', isOpened: true, withHeader: true },
			},
		],
	};

	return (
		<VStack spacing={ 4 }>
			<SettingsTitle />
			<DataForm
				data={ rsvp_options }
				fields={ fields }
				form={ form }
			/>
			<DefaultDiff />
		</VStack>
	);
};

export { ConfirmationSettings };
