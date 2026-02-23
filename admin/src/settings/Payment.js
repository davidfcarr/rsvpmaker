import React, {useState, useEffect, Suspense} from "react"
import {useOptions, useOptionsMutation} from './queries.js'
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';
import { SanitizedHTML } from "./SanitizedHTML.js";
import {useSaveControls} from './SaveControls';
import { OptionsToggle,OptRadio,OptSelect,OptText,OptTextArea } from "./OptionControls.js";

export default function Payment (props) {
    const {data,isLoading,isError} = useOptions('payment');
    if(isError)
        return <p>Error loading payment options</p>
    const {changes,addChange,setChanges} = props;
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();

   function paymentSetChanges() {
      saveStripe();
      savePayPal();
      setChanges();
   }

    if(isLoading || !data.data.stripe)
        return <p>Loading ...</p>
    console.log(data.data.stripe);
    const [stripe,setStripe] = useState(data.data.stripe);
    const [paypal,setPaypal] = useState(data.data.paypal);
    const [chosenGateway,setChosenGateway] = useState(data.data.chosen_gateway);
    const [currency,setCurrency] = useState(data.data.rsvp_options.paypal_currency);
    const [send_payment_reminders,setSendPaymentReminders] = useState(data.data.rsvp_options.send_payment_reminders);
    const [cancel_unpaid_hours,setCancelUnpaidHours] = useState(parseInt(data.data.rsvp_options.cancel_unpaid_hours));
    const [minimum_payment,setMinimum] = useState((data.data.rsvp_options.minimum_payment) ? data.data.rsvp_options.minimum_payment : '5.00');
    const [currencyFormat,setCurrencyFormat] = useState(data.data.rsvp_options.currency_decimal+'|'+data.data.rsvp_options.currency_thousands);
    const cformats = [{'label':'1,000.00','value':'.|,'},{'label':'1.000,00','value':',|.'},{'label':'1 000,00','value':',| '}];
    const modeoptions = [{'label':'Production','value':'production'},{'label':'Sandbox','value':'sandbox'}];
       
    return <div className="rsvptab payment">
    <div className={(isSaving) ? "rsvptab-saving": ""}>
    <TextControl label="Currency" className="payment" value={currency} onChange={(value) => {setCurrency(value); addChange('paypal_currency',value);}} /> <p><a href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes">(list of currency codes)</a></p>
    <SelectControl className="payment" label="Currency Format" value={currencyFormat} options={cformats} onChange={(value) => {setCurrencyFormat(value); const split = value.split('|'); addChange('currency_decimal',split[0]); addChange('currency_thousands',split[1]); } } />
    <TextControl label="Minimum Transaction" className="payment" value={minimum_payment} onChange={(value) => {setMinimum(value); addChange('minimum_payment',value);}} />

    <h3>Stripe</h3>
    <div className="key-inputs">
    <div className="production">
    {stripe.pk != 'set' && <TextControl label="Stripe Public Key" value={stripe.pk} onChange={(value) => {let prev = {...stripe}; prev.pk=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');} } /> }
    {stripe.pk != 'set' && <TextControl label="Stripe Secret Key" value={stripe.sk} onChange={(value) => {let prev = {...stripe}; prev.sk=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');} } />}
    {stripe.pk != 'set' && <TextControl label="Stripe Webhook Key" value={stripe.webook} onChange={(value) => {let prev = {...stripe}; prev.webhook=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');} } />}
    {stripe.pk == 'set' && <p>Stripe Production Keys Set <button onClick={() => {let prev = {...stripe}; prev.pk=''; prev.sk=''; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}}>Reset</button></p>}
    </div>
    <div className="sandbox">
    {stripe.sandbox_pk != 'set' && <TextControl label="Stripe Sandbox Public Key" value={stripe.sandbox_pk} onChange={(value) => {let prev = {...stripe}; prev.sandbox_pk=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}} />}
    {stripe.sandbox_pk != 'set' && <TextControl label="Stripe Sandbox Secret Key" value={stripe.sandbox_sk} onChange={(value) => {let prev = {...stripe}; prev.sandbox_sk=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}} />}
    {stripe.sandbox_pk != 'set' && <TextControl label="Stripe Sandbox Webhook Key" value={stripe.sandbox_webhook} onChange={(value) => {let prev = {...stripe}; prev.sandbox_webhook=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}} />}
    {stripe.sandbox_pk == 'set' && <p>Stripe Sandbox Keys Set <button onClick={() => {let prev = {...stripe}; prev.sandbox_pk=''; prev.sandbox_sk=''; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}}>Reset</button></p>}
    </div>
    </div>
    <RadioControl label="Stripe Mode" selected={stripe.mode} options={modeoptions} onChange={(value) => {let prev = {...stripe}; prev.mode=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}} />
    <TextControl label="Notification Email for Stripe (optional)" value={stripe.notify} onChange={(value) => {let prev = {...stripe}; prev.notify=value; setStripe(prev); addChange('rsvpmaker_stripe_keys',prev,'mergearray');}} />
    
    <h3>PayPal</h3>
    <div className="key-inputs">
    <div className="production">
    {paypal.client_id != 'set' && <TextControl label="PayPal Client ID" className="payment" value={paypal.client_id} onChange={(value) => {let prev = {...paypal}; prev.client_id=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}} />}
    {paypal.client_id != 'set' && <TextControl label="PayPal Client Secret" className="payment" value={paypal.client_secret} onChange={(value) => {let prev = {...paypal}; prev.client_secret=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray'); } } />}
    {paypal.client_id == 'set' && <p>PayPal Production Keys Set <button onClick={() => {let prev ={...paypal}; prev.client_id=''; prev.client_secret=''; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}}>Reset</button></p>}
    </div>
    <div className="sandbox">
    {paypal.sandbox_client_id != 'set' && <TextControl className="payment"  label="PayPal Sandbox Client ID" value={paypal.sandbox_client_id} onChange={(value) => {let prev = {...paypal}; prev.sandbox_client_id=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}} />}
    {paypal.sandbox_client_id != 'set' && <TextControl className="payment" label="PayPal Sandbox Client Secret" value={paypal.sandbox_client_secret} onChange={(value) => {let prev = {...paypal}; prev.sandbox_client_secret=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}} />}
    {paypal.sandbox_client_id == 'set' && <p>PayPal Sandbox Keys Set <button onClick={() => {let prev = {...paypal}; prev.sandbox_client_id=''; prev.sandbox_client_secret=''; setPaypal(prev);addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}}>Reset</button></p>}
    </div>
    </div>
    <TextControl className="payment"  label="PayPal Additional Funding Sources" value={paypal.funding_sources} onChange={(value) => {let prev = {...paypal}; prev.funding_sources=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}} />
    <p><em>Example: "venmo" to add Venmo button - leave blank for defaults</em></p>
    <TextControl className="payment"  label="PayPal Excluded Funding Sources" value={paypal.excluded_funding_sources} onChange={(value) => {let prev = {...paypal}; prev.excluded_funding_sources=value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray');}} />    
    <p><em>Example: "paylater,card" to remove Pay Later and Credit Card buttons - leave blank for defaults</em></p>
    
    <RadioControl label="PayPal Mode" selected={paypal.mode} options={modeoptions} onChange={(value) => {let prev={...paypal}; prev.mode = value; setPaypal(prev); addChange('rsvpmaker_paypal_rest_keys',prev,'mergearray'); }} />
    <SelectControl label="Chosen Gateway" value={chosenGateway} options={data.data.gateways} onChange={(value) => {setChosenGateway(value); addChange('payment_gateway',value); } } />
    <RadioControl label="Send Payment Reminder" selected={send_payment_reminders} options={[{'label':'Yes','value':1},{'label':'No','value':0}]} onChange={(value) => {value=parseInt(value); setSendPaymentReminders(value); addChange('send_payment_reminders',value); }} />
    <TextControl label="Delete Unpaid RSVPs? Enter Deadline as Number of Hours or 0 for None" value={cancel_unpaid_hours} onChange={(value) => {value = parseInt(value); setCancelUnpaidHours(value); addChange('cancel_unpaid_hours',value); }} />

    </div>
    <SaveControls changes={changes} setChanges={setChanges} />
   </div>
}