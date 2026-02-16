const { __ } = wp.i18n;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { ToggleControl, TextControl,  SelectControl } = wp.components;

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import PayPalLogo from './paypal-logo.svg';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const {  attributes: { description, showdescription, amount, paymentType, january, february, march, april, may, june, july, august, september, october, november, december, currency }, setAttributes, className, isSelected } = props;
	const show = (paymentType.toString() == 'schedule') ? true : false;
	let currency_symbol = '';
	if(currency.toString() == 'usd') currency_symbol = '$';
	else if(currency.toString() == 'eur') currency_symbol = '€';
	const blockProps = useBlockProps({ className });

	return (
			<div { ...blockProps }>
				<InspectorControls>
						<TextControl
        label={ __( 'Description', 'rsvpmaker' ) }
        value={ description }
        onChange={ ( description ) => setAttributes( { description } ) }
    />	
<div>		<SelectControl
			label={ __( 'Show Amount/Description Under Button', 'rsvpmaker' ) }
			value={ showdescription }
			onChange={ ( showdescription ) => setAttributes( { showdescription } ) }
			options={ [
				{ value: 'yes', label: __( 'Yes', 'rsvpmaker' ) },
				{ value: 'no', label: __( 'No', 'rsvpmaker' ) },
			] }
		/>

		<SelectControl
			label={ __( 'Payment Type', 'rsvpmaker' ) }
			value={ paymentType }
			onChange={ ( paymentType ) => setAttributes( { paymentType } ) }
			options={ [
				{ value: 'one-time', label: __( 'One time, fixed fee', 'rsvpmaker' ) },
				{ value: 'schedule', label: __( 'Dues schedule', 'rsvpmaker' ) },
				{ value: 'donation', label: __( 'Donation', 'rsvpmaker' ) },
			] }
		/>
				</div>
{
!show &&	<TextControl
        label={ __( 'Fee', 'rsvpmaker' ) }
        value={ amount }
		placeholder="$0.00"
        onChange={ ( amount ) => setAttributes( { amount } ) }
    />			
}
{
show &&	
<div>    <TextControl
        label={ __( 'January', 'rsvpmaker' ) }
        value={ january }
        onChange={ ( january ) => setAttributes( { january } ) }
    />
    <TextControl
        label={ __( 'February', 'rsvpmaker' ) }
        value={ february }
        onChange={ ( february ) => setAttributes( { february } ) }
    />
    <TextControl
        label={ __( 'March', 'rsvpmaker' ) }
        value={ march }
        onChange={ ( march ) => setAttributes( { march } ) }
    />
    <TextControl
        label={ __( 'April', 'rsvpmaker' ) }
        value={ april }
        onChange={ ( april ) => setAttributes( { april } ) }
    />
    <TextControl
        label={ __( 'May', 'rsvpmaker' ) }
        value={ may }
        onChange={ ( may ) => setAttributes( { may } ) }
    />
    <TextControl
        label={ __( 'June', 'rsvpmaker' ) }
        value={ june }
        onChange={ ( june ) => setAttributes( { june } ) }
    />
    <TextControl
        label={ __( 'July', 'rsvpmaker' ) }
        value={ july }
        onChange={ ( july ) => setAttributes( { july } ) }
    />
    <TextControl
        label={ __( 'August', 'rsvpmaker' ) }
        value={ august }
        onChange={ ( august ) => setAttributes( { august } ) }
    />
    <TextControl
        label={ __( 'September', 'rsvpmaker' ) }
        value={ september }
        onChange={ ( september ) => setAttributes( { september } ) }
    />
    <TextControl
        label={ __( 'October', 'rsvpmaker' ) }
        value={ october }
        onChange={ ( october ) => setAttributes( { october } ) }
    />
    <TextControl
        label={ __( 'November', 'rsvpmaker' ) }
        value={ november }
        onChange={ ( november ) => setAttributes( { november } ) }
    />
    <TextControl
        label={ __( 'December', 'rsvpmaker' ) }
        value={ december }
        onChange={ ( december ) => setAttributes( { december } ) }
    />
</div>
 }
	<TextControl
        label={ __( 'Currency Code (lowercase)', 'rsvpmaker' ) }
        value={ currency }
        onChange={ ( currency ) => setAttributes( { currency } ) }
    />
</InspectorControls>
<div style={{textAlign: 'center',padding: '10px', backgroundColor: '#ffc439'}}><img src={PayPalLogo} alt="PayPal" role="presentation" className="paypal-logo paypal-logo-paypal paypal-logo-color-blue" /></div>
<div style={{textAlign: 'center', fontSize: '10px'}}>Powered by <img src={PayPalLogo} width="32" alt="PayPal" role="presentation" className="paypal-logo paypal-logo-paypal paypal-logo-color-blue" /></div>
{description && showdescription == 'yes' ? <p className="description">{currency_symbol}{amount} {currency}<br />{description}</p> : null}
<p><em>Preview is approximate</em></p>
			</div>
	);
}
