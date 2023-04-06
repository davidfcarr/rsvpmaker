import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import { SanitizedHTML } from "./SanitizedHTML.js";
import {useSaveControls} from './SaveControls';
import { OptionsToggle,OptRadio,OptSelect,OptText,OptTextArea } from "./OptionControls.js";

export default function Payment (props) {
    const {data,isLoading} = useOptions('payment');
    const {changes,addChange,setChanges} = props;
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();

    if(isLoading || !data.data.stripe)
        return <p>Loading ...</p>
    console.log(data.data.stripe);
    const [stripePk,setStripePk] = useState(data.data.stripe.pk);
    const [stripeSk,setStripeSk] = useState(data.data.stripe.sk);
    const [paypalCi,setPaypalCi] = useState(data.data.paypal.client_id);
    const [paypalCs,setPaypalCs] = useState(data.data.paypal.client_secret);
    const [stripeSandboxPk,setStripeSandboxPk] = useState(data.data.stripe.sandbox_pk);
    const [stripeSandboxSk,setStripeSandboxSk] = useState(data.data.stripe.sandbox_sk);
    const [paypalSandboxCi,setPaypalSandboxCi] = useState(data.data.paypal.sandbox_client_id);
    const [paypalSandboxCs,setPaypalSandboxCs] = useState(data.data.paypal.sandbox_client_secret);
    const [stripeMode,setStripeMode] = useState(data.data.stripe.mode);
    const [paypalMode,setPaypalMode] = useState(data.data.paypal.sandbox ? 'sandbox': 'production');
    const [stripeNotify,setStripeNotify] = useState(data.data.stripe.notify);
    const [chosenGateway,setChosenGateway] = useState(data.data.chosen_gateway);
    const [currency,setCurrency] = useState(data.data.rsvp_options.paypal_currency);
    const [currencyFormat,setCurrencyFormat] = useState(data.data.rsvp_options.currency_decimal+'|'+data.data.rsvp_options.currency_thousands);
    const cformats = [{'label':'1,000.00','value':'.|,'},{'label':'1.000,00','value':',|.'},{'label':'1 000,00','value':',| '}];
    const modeoptions = [{'label':'Production','value':'production'},{'label':'Sandbox','value':'sandbox'}];
    
    function saveStripe() {
     const newstripe = {'mode':stripeMode};
     if(stripePk && stripeSk) {
        newstripe.pk = stripePk;   
        newstripe.sk = stripeSk;
     }
     if(stripeSandboxPk && stripeSandboxSk) {
        newstripe.sandbox_pk = stripeSandboxPk;   
        newstripe.sandbox_sk = stripeSandboxSk;
     }
     newstripe.notify = stripeNotify;
     console.log('payment update',newstripe);
     addChange('rsvpmaker_stripe_keys',newstripe,'mergearray');
    }

    function savePayPal() {
        const newpaypal = {'mode':('sandbox' == paypalMode) ? 1 : 0};
        if(paypalCi && paypalCs) {
           newpaypal.client_id = paypalCi;   
           newpaypal.client_secret = paypalCs;
        }
        if(paypalSandboxCi && paypalSandboxCs) {
            newpaypal.sandbox_client_id = paypalSandboxCi;   
            newpaypal.sandbox_client_secret = paypalSandboxCs;
         }
        addChange('rsvpmaker_paypal_rest_keys',newpaypal,'mergearray');
       }
   
    return <div className="rsvptab payment">
    <div className={(isSaving) ? "rsvptab-saving": ""}>
    <TextControl label="Currency" className="payment" value={currency} onChange={(value) => {setCurrency(value); addChange('paypal_currency',value);}} /> <p><a href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes">(list of currency codes)</a></p>
    <SelectControl className="payment" label="Currency Format" value={currencyFormat} options={cformats} onChange={(value) => {setCurrencyFormat(value); const split = value.split('|'); addChange('currency_decimal',split[0]); addChange('currency_thousands',split[1]); } } />

    <h3>Stripe</h3>
    <div className="key-inputs">
    <div className="production">
    {stripePk != 'set' && <TextControl label="Stripe Public Key" value={stripePk} onChange={(value) => {setStripePk(value); saveStripe();}} />}
    {stripePk != 'set' && <TextControl label="Stripe Secret Key" value={stripeSk} onChange={(value) => {setStripeSk(value); saveStripe()} } />}
    {stripePk == 'set' && <p>Stripe Production Keys Set <button onClick={() => {setStripePk(''); setStripeSk('');}}>Reset</button></p>}
    </div>
    <div className="sandbox">
    {stripeSandboxPk != 'set' && <TextControl label="Stripe Sandbox Public Key" value={stripeSandboxPk} onChange={(value) => {setStripeSandboxPk(value);saveStripe()}} />}
    {stripeSandboxPk != 'set' && <TextControl label="Stripe Sandbox Secret Key" value={stripeSandboxSk} onChange={(value) => {setStripeSandboxSk(value);saveStripe();}} />}
    {stripeSandboxPk == 'set' && <p>Stripe Sandbox Keys Set <button onClick={() => {setStripeSandboxPk(''); setStripeSandboxSk('');}}>Reset</button></p>}
    </div>
    </div>
    <RadioControl label="Stripe Mode" selected={stripeMode} options={modeoptions} onChange={setStripeMode} />
    <TextControl label="Notification Email for Stripe (optional)" value={stripeNotify} onChange={(value) => {setStripeNotify(value);saveStripe();}} />
    
    <h3>PayPal</h3>
    <div className="key-inputs">
    <div className="production">
    {paypalCi != 'set' && <TextControl label="PayPal Client ID" className="payment" value={paypalCi} onChange={(value) => {setPaypalCi(value); savePayPal();}} />}
    {paypalCi != 'set' && <TextControl label="PayPal Client Secret" className="payment" value={paypalCs} onChange={(value) => {setPaypalCs(value); savePayPal()} } />}
    {paypalCi == 'set' && <p>PayPal Production Keys Set <button onClick={() => {setPaypalCi(''); setPaypalcs('');}}>Reset</button></p>}
    </div>
    <div className="sandbox">
    {paypalSandboxCi != 'set' && <TextControl className="payment"  label="PayPal Sandbox Client ID" value={paypalSandboxCi} onChange={(value) => {setPaypalSandboxCi(value);savePayPal()}} />}
    {paypalSandboxCi != 'set' && <TextControl className="payment" label="PayPal Sandbox Client Secret" value={paypalSandboxCs} onChange={(value) => {setPaypalSandboxCs(value);savePayPal();}} />}
    {paypalSandboxCi == 'set' && <p>PayPal Sandbox Keys Set <button onClick={() => {setPaypalSandboxCi(''); setPaypalSandboxCs('');}}>Reset</button></p>}
    </div>
    </div>
    <RadioControl label="PayPal Mode" selected={paypalMode} options={modeoptions} onChange={setPaypalMode} />

    <SelectControl label="Chosen Gateway" value={chosenGateway} options={data.data.gateways} onChange={(value) => {setChosenGateway(value); addChange('payment_gateway',value); } } />

    </div>
    <SaveControls changes={changes} setChanges={setChanges} />
   </div>
}