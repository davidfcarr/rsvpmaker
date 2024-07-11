import React, {useState} from 'react';
import { TextControl, RadioControl } from '@wordpress/components';
import apiClient from '../http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';
import { MetaSelectControl, MetaTextControl, MetaRadioControl } from './metadata_components.js';
const { __ } = wp.i18n; // Import __() from wp.i18n

export default function Pricing() {
    const [status,setStatus] = useState('');
    const [priceToAdd,setPriceToAdd] = useState('0.00');
    const [extraGuestPrice,setExtraGuestPrice] = useState('0.00');
    const [unitToAdd,setUnitToAdd] = useState('');
    const [deadlineDate,setDeadlineDate] = useState('');
    const [deadlineTime,setDeadlineTime] = useState('');
    const [multiple,setMultiple] = useState(1);
    const [toUpdate,setToUpdate] = useState(-1);
    const [code,setCode] = useState('');
    const [discount,setDiscount] = useState('0.00');
    const [method,setMethod] = useState('percent');
    const [codeToUpdate,setCodeToUpdate] = useState(-1);
    //const [itemPrices,setItemPrices] = useState({'meal_choice:Steak':"15",'meal_choice:Chicken':"10"});
    const [itemPrices,setItemPrices] = useState(null);
    const [itemToUpdate,setItemToUpdate] = useState('');
    const [itemPriceToUpdate,setItemPriceToUpdate] = useState(0);
    
    function fetchPricing() {
        return apiClient.get('pricing?event_id='+rsvpmaker_ajax.event_id);
    }
    const {data,isLoading,isError} = useQuery(['pricing'], fetchPricing, { enabled: true, retry: 2, 
    onSuccess: (data, error, variables, context) => {
        console.log('rsvp pricing query',data);
    }, onError: (err, variables, context) => {
        console.log('error retrieving pricing',err);
    }, refetchInterval: false });
        
    const queryClient = useQueryClient();
    async function updatePricing (command) {
        return await apiClient.post('pricing?event_id='+rsvpmaker_ajax.event_id, command);
    }
    
    if(isError)
        return <p>Error loading pricing data.</p>

    const {mutate:mutatePricing} = useMutation(updatePricing, {
        onMutate: async (command) => {
            const previousValue = queryClient.getQueryData(['pricing']);
            await queryClient.cancelQueries(['pricing']);
            queryClient.setQueryData(['pricing'],(oldQueryData) => {
                const {data} = oldQueryData;
                data.change = command.change;
                const newdata = {
                    ...oldQueryData, data: data
                };
                return newdata;
            }) 
            //makeNotification('Updating ...');
            return {previousValue}
        },

        onSettled: (data, error, variables, context) => {
            queryClient.invalidateQueries(['pricing']);
        },
        onSuccess: (data, error, variables, context) => {
            console.log('updated',data);
            setStatus('');
            queryClient.setQueryData(['pricing'], data);
        },
        onError: (err, variables, context) => {
            console.log('update message error',err);
        },    
    });

        function postPrice() {
            setStatus('Posting ...');
            const change = [...pricing];
            if(toUpdate < 0)
                change.push({'unit':unitToAdd,'price':priceToAdd,'deadlineDate':deadlineDate,'deadlineTime':deadlineTime,'price_multiple':multiple,'extra_guest_price':extraGuestPrice});
            else 
                change[toUpdate] = {'unit':unitToAdd,'price':priceToAdd,'deadlineDate':deadlineDate,'deadlineTime': deadlineTime,'price_multiple':multiple,'extra_guest_price':extraGuestPrice};
            console.log(change);
            mutatePricing({'update':'pricing','change':change});
            setDeadlineDate('');
            setDeadlineTime('');
            setMultiple(1);
            setToUpdate(-1);
            setUnitToAdd('');
            setPriceToAdd('0.00');
        }   

        function deletePrice(index) {
            setStatus('Posting ...');
            const change = [...pricing];
            delete change[index];
            mutatePricing({'update':'pricing','change':change});
        }   

        function deleteCode(index) {
            setStatus('Posting ...');
            const change = [...coupon_codes];
            delete change[index];
            mutatePricing({'update':'coupon_codes','change':change});
        }

        function postCode() {
            const codes = [...coupon_codes];
            const discounts = [...coupon_discounts];
            const methods = [...coupon_methods];
            if(codeToUpdate < 0) {
                codes.push(code);
                discounts.push(discount);
                methods.push(method);
            }
            else {
                codes[codeToUpdate] = code;
                discounts[codeToUpdate] = discount;
                methods[codeToUpdate] = method;
            }                 
            setStatus('Posting ...');

            mutatePricing({'update':'coupon_codes','change':{'coupon_codes':codes,'coupon_discounts':discounts,'coupon_methods':methods}});
            setCode('');
            setMethod('percent');
            setDiscount(0.0);
            setCodeToUpdate(-1);
        }

        function displayDiscount(discount,method) {
            if(isNaN(discount))
                return;
            discount = parseFloat(discount);
            if('percent' == method)
                return (discount * 100).toFixed(2)+'% off, example 5 x $50 = $250, $'+(250 - (250 * discount)).toFixed(2)+' after discount';
            else if('totalamount' == method)
                return discount.toFixed(2) +' off total, example 5 x $50 = $250, $'+(250 - discount).toFixed(2)+' after discount';
            else
                return discount.toFixed(2) +' off each, example 5 x $50 = $250, $'+(250 - (discount * 5) ).toFixed(2)+' after discount';
        }

        const pricingdata = (isLoading) ? null : data.data;
        const pricing = (isLoading) ? [] : pricingdata.pricing;
        const coupon_codes = (isLoading || !pricingdata.coupon_codes) ? [] : pricingdata.coupon_codes;
        const coupon_methods = (isLoading || !pricingdata.coupon_methods) ? [] : pricingdata.coupon_methods;
        const coupon_discounts = (isLoading || !pricingdata.coupon_discounts) ? [] : pricingdata.coupon_discounts;
        const form_fields = (isLoading || !pricingdata.form_fields) ? [] : pricingdata.form_fields;
        const exclude = ['phone_type'];
        if(!isLoading && itemPrices != pricingdata.item_prices)
            setItemPrices(pricingdata.item_prices);
        const roptions = [];

        form_fields.map(
                (item, index) => {
                    if(exclude.includes(item.slug))
                        return;
                    if(!item.choicearray)
                        return;

                    item.choicearray.forEach( (choice) => { roptions.push(<p>{item.label+': '+choice} <TextControl value={(itemPrices && itemPrices[item.slug] && itemPrices[item.slug][choice]) ? itemPrices[item.slug][choice] : 0} onChange={(value) => {console.log('new value', value); const change = {...itemPrices}; change[item.slug][choice] = value; mutatePricing({'update':'item_prices','change':change}); }} /></p>); } );
                    console.log('roptions',roptions);
                } 
        )

    return <div>
        <p><a href="https://rsvpmaker.com/knowledge-base/pricing-per-person-and-by-options-like-meal-choice/" target="_blank">Documentation: Pricing Options</a></p>
        <p><MetaSelectControl label="Payment Gateway" metaKey="_payment_gateway" options={rsvpmaker_ajax.payment_gateway_options.map((value) => { return {'label':value,'value':value}})} /></p>
        <p><MetaTextControl label="Currency" metaKey="_rsvp_currency" /></p>
        {isLoading && <p>Loading prices ...</p>}

        {pricing && (pricing.length > 0) && <h3>Event Pricing</h3>}
        {pricing.map(
            (item, index) => {
                return <p><strong><button onClick={() => { console.log('click on index',index); setToUpdate(index); setUnitToAdd(item.unit); setPriceToAdd(item.price); setMultiple(item.price_multiple); setDeadlineDate(item.deadlineDate); setDeadlineTime(item.deadlineTime); setExtraGuestPrice(item.extra_guest_price); } }>Edit</button> <button onClick={() => {deletePrice(index)} }>Delete</button> {item.unit}: {item.price}</strong> {item.niceDeadline && <span>deadline: {item.niceDeadline}</span>} {(item.price_multiple > 1) && <span>multiple: {item.price_multiple} additional guests: {item.extra_guest_price}</span>}</p>
            } 
        )}
        <p><label>Unit (<em>Example: "Tickets" or "Dinners"</em>)</label> <TextControl value={unitToAdd} onChange={setUnitToAdd} /></p>
        <p><label>Price</label> <TextControl value={priceToAdd} onChange={setPriceToAdd} /></p>
        <p><label>Deadline (optional)</label> <input type="date" value={deadlineDate} onChange={(e) =>{setDeadlineDate(e.target.value)} } /> <input type="time" value={deadlineTime} onChange={(e) =>{setDeadlineTime(e.target.value)} } /> {(deadlineDate || deadlineTime) && <button onClick={() => {setDeadlineDate('');setDeadlineTime('')} }>Remove Deadline</button>}
        <br /><em>Example: early bird rate to register for a conference.</em></p>
        <p><label>Multiple (optional)</label> <input type="number" value={multiple} onChange={(e) =>{setMultiple(e.target.value)} } />
        <br /><em>Example: price for a table of 8 at a dinner, rather than a single registration.</em></p>
        {(multiple > 1) && <p><label>Additional Guest Price</label>  <TextControl value={extraGuestPrice} onChange={setExtraGuestPrice} /></p>}
        <p><button disabled={isLoading} onClick={postPrice}>{(toUpdate > -1) ? 'Update Price' : 'Add Price'}</button> {status}</p>

        <div><h2>Priced Options</h2>{(roptions.length > 0) && <div>{roptions}</div>}<div>If you include radio button options such as meal choice on your form, you can assign a price to individual choices.</div></div>

        <h2>Discounts</h2>
        {isLoading && <p>Loading discounts ...</p>}
        {coupon_codes.map(  
            (code, index) => {
                let method = coupon_methods[index];
                let discount = coupon_discounts[index];
                if(isNaN(discount))
                    discount = 0.0;
                return (
                    <div><span><button onClick={() => { console.log('click on index',index); setCodeToUpdate(index); setCode(code); setMethod(method); setDiscount(discount); } }>Edit</button> <button onClick={() => {deleteCode(index)} }>Delete</button></span> Code: {code} {displayDiscount(discount,method)} </div>
                )
            }
        )
        
        }
        <div>
        <TextControl label="Coupon Code" value={code} onChange={(value) => {setCode(value)} } />
        <RadioControl label="Method" selected={method} options={[{'label':'Discount Percent (enter decimal, 0.5 = 50%)','value':'percent'},{'label':'Discount Amount off Each','value':'amount'},{'label':'Discount Off Total','value':'totalamount'}]} onChange={(value) => {setMethod(value)} } />
        <TextControl label="Discount" value={discount} onChange={(value) => {if('percent' == method && value > 1.0) value=1.0; else if(isNaN(value)) value=0.0; setDiscount(value)} } />
        <p>{displayDiscount(discount,method)}</p>
        <p><button disabled={isLoading} onClick={postCode}>{(codeToUpdate > -1) ? 'Update Code' : 'Add Code'}</button> {status}</p>
        </div>

    </div>
}
