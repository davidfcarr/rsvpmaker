const { __ } = wp.i18n;
const { InnerBlocks, InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl,  SelectControl } = wp.components;
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
	const { attributes, className, setAttributes, isSelected } = props;
	const blockProps = useBlockProps({ className });

	return (
		<div { ...blockProps }>
            <InspectorControls key="stripeformwrapperinspector">
            <PanelBody title={ __( 'Payment', 'rsvpmaker' ) } >
			<SelectControl
							label={ __( 'Payment Type', 'rsvpmaker' ) }
							value={ attributes.paymentType }
							onChange={ ( paymentType ) => setAttributes( { paymentType } ) }
							options={ [
								{ value: '', label: __( 'One Time', 'rsvpmaker' ) },
								{ value: 'subscription:monthly', label: __( 'Recurring Payment: monthly', 'rsvpmaker' ) },
								{ value: 'subscription:6 months', label: __( 'Recurring Payment: every 6 months', 'rsvpmaker' ) },
								{ value: 'subscription:1 year', label: __( 'Recurring Payment: annual', 'rsvpmaker' ) },
							] }
						/>
					<TextControl
							label={ __( 'Amount', 'rsvpmaker' ) }
							value={ attributes.amount }
							onChange={ ( amount ) => setAttributes( { amount } ) }
						/>
					<TextControl
							label={ __( 'Description', 'rsvpmaker' ) }
							value={ attributes.description }
							onChange={ ( description ) => setAttributes( { description } ) }
						/>
				</PanelBody>
            </InspectorControls>
<div class="stripe-wrapper-border">{__('START Stripe form wrapper')}</div>
	<InnerBlocks template={[
    [ 'rsvpmaker/formfield', { label:'Name',slug:'name',sluglocked: true, guestform:false } ], 
]} />
<div class="stripe-wrapper-border">{__('END Stripe form wrapper')}</div>
		</div>
		);
}
