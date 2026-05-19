import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function RSVPExpireAfterEnd(props) {
	const { attributes, setAttributes } = props;
	const { query } = attributes;
	const expireAfterEnd = query.expireAfterEnd ? query.expireAfterEnd : false;

	return (
		<ToggleControl
			label={__('Expire after event end time', 'rsvpmaker')}
			help={__('When enabled, events are hidden after their end time passes. By default, events show until the end of the day.', 'rsvpmaker')}
			checked={expireAfterEnd}
			onChange={(value) => {
				setAttributes({ query: { ...query, expireAfterEnd: value } });
			}}
		/>
	);
}
