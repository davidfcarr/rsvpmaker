const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { Component } = wp.element;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { Panel, PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl, RadioControl } = wp.components;

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

	const { attributes: {fields}, className, setAttributes, isSelected } = props;
	const bodyStyle = {
		backgroundColor: '#fff',
		padding: '5px',
	};
	const blockProps = useBlockProps( { style: bodyStyle, className: 'wp-block-rsvpmaker-emailguestsignup' } );

	console.log(blockProps);

	return (
<div { ...blockProps} >
				<InspectorControls>
					<Panel>
						<PanelBody>
							<SelectControl
								label={__("Fields to Display", "rsvpmaker")}
								options={[
								{"label":__('First Name, Last Name, Email'),value:''},
								{"label":__('First Name, Email'),value:'first'},
								{"label":__('Email'),value:'email'},
							]}
								value={fields}
								onChange={(val) => setAttributes({fields: val})}
							/>
						</PanelBody>
					</Panel>
				</InspectorControls>
{'' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<p><label>{__('First Name','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<p><label>{__('Last Name','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_last"  name="rsvpguest_list_last" /></p>
<p><label>{__('Email','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}
{'first' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<p><label>{__('First Name','rsvpmaker')}</label><input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label>{__('Email','rsvpmaker')}</label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}
{'email' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<input type="hidden" id="rsvpguest_list_first"  name="rsvpguest_list_first" />
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label>{__('Email','rsvpmaker')}</label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}

</div>
	);
}

class ChoiceInspector extends Component {
	render() {
	const { attributes, setAttributes, className } = this.props;
	const choices =attributes.choicearray.join('\n');
	function setLabel(label) {
		let simpleSlug = label.trim().toLowerCase();
		simpleSlug = simpleSlug.replace(/[^a-z0-9]+/g,'_');
		if('first_name' == simpleSlug)
			simpleSlug = 'first';
		if('last_name' == simpleSlug)
			simpleSlug = 'last';
		setAttributes({slug: simpleSlug});
		setAttributes({label: label});
		setAttributes({guestform: true});
	}
		
	function setChoices(choices) {
		setAttributes({choicearray: choices.split('\n')});
	}
		return (
			<InspectorControls key="choiceinspector">
			<PanelBody title={ __( 'Field Properties', 'rsvpmaker' ) } >
			<TextControl
				label={ __( 'Label', 'rsvpmaker' ) }
				value={ attributes.label }
				onChange={ ( label ) => setLabel(label) }
			/>
			<TextareaControl
				label={ __( 'Choices', 'rsvpmaker' ) }
				value={ choices }
				onChange={ ( choices ) => setChoices( choices  ) }
			/>
				<div><em>Enter each choice on a separate line</em></div>
			<ToggleControl
				label={ __( 'Include on Guest Form', 'rsvpmaker' ) }
				checked={ attributes.guestform }
				help={ attributes.guestform ? 'Included' : 'Not included' } 
				onChange={ ( guestform ) => {setAttributes( {guestform: guestform} ) }}
			/>
			<ToggleControl
				label={ __( 'Check first choice by default', 'rsvpmaker' ) }
				checked={ attributes.defaultToFirst }
				help={ attributes.defaultToFirst ? 'First item selected by default' : 'No default' } 
				onChange={ ( defaultToFirst ) => {setAttributes( {defaultToFirst: defaultToFirst} ) }}
			/>
	</PanelBody>
	</InspectorControls>
);	} }
