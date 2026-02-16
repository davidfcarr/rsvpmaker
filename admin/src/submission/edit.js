const { __ } = wp.i18n;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { ToggleControl, TextControl } = wp.components;

import { useState, useEffect } from 'react';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const blockProps = useBlockProps();
	const { attributes: { timezone, to }, setAttributes, isSelected } = props;
	
		return (
			<div {...blockProps}>

	<InspectorControls key="submissioninspector">
	<ToggleControl
        label={__("Prompt for Timezone",'rsvpmaker')}
        checked={ timezone }
        onChange={ ( timezone ) => { setAttributes( { timezone } ) } }
    />
	<TextControl 
		label={__("Notification Emails: To override default from Settings, enter one or more emails, separated by commas",'rsvpmaker')}
		value={to}
		onChange={ ( to ) => { setAttributes( { to } ) } }
	/>
</InspectorControls>
<h2>Event Title: <input name="event_title" /></h2>
<div id="date"><label>Date</label> <input type="date" name="date" inert tabIndex="-1" /></div> 
<div><label>Time</label> <input id="time" type="time" name="time" value="12:00" inert tabIndex="-1" /> to <input id="endtime" type="time" name="endtime" value="13:00" inert tabIndex="-1" /></div>
{ timezone ? <div><label>Timezone</label><select id="timezone_string" name="timezone_string">
<optgroup label="U.S. (Common Choices)">
<option value="America/New_York">New York</option>
<option value="America/Chicago">Chicago</option>
<option value="America/Denver">Denver</option>
<option value="America/Los_Angeles">Los Angeles</option>
</optgroup>
<option value="">Other Choices ...</option>
</select> <br /><em>Choose a city in the same timezone as you.</em></div>
: null
}
<div><label>Your Name</label><input name="rsvpmaker_submission_contact" id="rsvpmaker_submission_contact" inert tabIndex="-1" /></div>
<div><label>Email</label><input name="rsvpmaker_submission_email" id="rsvpmaker_submission_email" inert tabIndex="-1" /></div>
<div><em>If you want your contact information to be published as part of the event listing, also include it in the description below.</em></div>
<p>Event Details<br /><textarea id="rsvpmaker_submission_description" name="rsvpmaker_submission_description" rows="5" cols="100" inert tabIndex="-1" ></textarea></p>
<p><button>Submit</button></p>
</div>
);
}

