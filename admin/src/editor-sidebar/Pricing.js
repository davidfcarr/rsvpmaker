import React, {useState} from 'react';
import { TextControl, RadioControl } from '@wordpress/components';
import apiClient from '../http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';
import { MetaSelectControl, MetaTextControl, MetaRadioControl } from './metadata_components.js';
const { __ } = wp.i18n; // Import __() from wp.i18n

export default function Pricing() {
    const [status,setStatus] = useState('');
    const [priceToAdd,setPriceToAdd] = useState('0.00');
    const [unitToAdd,setUnitToAdd] = useState('');
    const [deadlineDate,setDeadlineDate] = useState('');
    const [deadlineTime,setDeadlineTime] = useState('');
    const [multiple,setMultiple] = useState(1);
    const [toUpdate,setToUpdate] = useState(-1);
    const [code,setCode] = useState('');
    const [discount,setDiscount] = useState('0.00');
    const [method,setMethod] = useState('percent');
    const [codeToUpdate,setCodeToUpdate] = useState(-1);
    
    function fetchPricing() {
        return apiClient.get('pricing?event_id='+rsvpmaker_ajax.event_id);
    }
    const {data,isLoading} = useQuery(['pricing'], fetchPricing, { enabled: true, retry: 2, 
    onSuccess: (data, error, variables, context) => {
        console.log('rsvp pricing query',data);
    }, onError: (err, variables, context) => {
        console.log('error retrieving pricing',err);
    }, refetchInterval: false });
        
    const queryClient = useQueryClient();
    async function updatePricing (command) {
        return await apiClient.post('pricing?event_id='+rsvpmaker_ajax.event_id, command);
    }
    
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
                change.push({'unit':unitToAdd,'price':priceToAdd,'deadlineDate':deadlineDate,'deadlineTime':deadlineTime,'price_multiple':multiple});
            else 
                change[toUpdate] = {'unit':unitToAdd,'price':priceToAdd,'deadlineDate':deadlineDate,'deadlineTime': deadlineTime,'price_multiple':multiple};
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
            setMethod('');
            setDiscount('percent');
            setCodeToUpdate(-1);
        }

        function displayDiscount(discount,method) {
            if('percent' == method)
                return (discount * 100)+'% off';
            else
                return discount +' discount';
        }

        const pricingdata = (isLoading) ? null : data.data;
        const pricing = (isLoading) ? [] : pricingdata.pricing;
        const coupon_codes = (isLoading || !pricingdata.coupon_codes) ? [] : pricingdata.coupon_codes;
        const coupon_methods = (isLoading || !pricingdata.coupon_methods) ? [] : pricingdata.coupon_methods;
        const coupon_discounts = (isLoading || !pricingdata.coupon_discounts) ? [] : pricingdata.coupon_discounts;
    
    return <div>
        <p><MetaSelectControl label="Payment Gateway" metaKey="_payment_gateway" options={rsvpmaker_ajax.payment_gateway_options.map((value) => { return {'label':value,'value':value}})} /></p>
        <p><MetaTextControl label="Currency" metaKey="_rsvp_currency" /></p>
        <p><MetaRadioControl label="Price Calculation" metaKey="_rsvp_count_party" options={[{'label':__('Multiply by size of party'),'value':'1'},{'label':__('Let user specify number of admissions per category'),'value':'0'}]} /></p>
        {isLoading && <p>Loading prices ...</p>}

        {pricing.map(
            (item, index) => {
                return <p>{item.unit}: {item.price} {item.niceDeadline && <span>deadline: {item.niceDeadline}</span>} multiple: {item.price_multiple} <button onClick={() => { console.log('click on index',index); setToUpdate(index); setUnitToAdd(item.unit); setPriceToAdd(item.price); setMultiple(item.price_multiple); setDeadlineDate(item.deadlineDate); setDeadlineTime(item.deadlineTime); } }>Edit</button> <button onClick={() => {deletePrice(index)} }>Delete</button></p>
            } 
        )}
        <p><label>Unit</label> <TextControl value={unitToAdd} onChange={setUnitToAdd} /></p>
        <p><label>Price</label> <TextControl value={priceToAdd} onChange={setPriceToAdd} /></p>
        <p><label>Deadline (optional)</label> <input type="date" value={deadlineDate} onChange={(e) =>{setDeadlineDate(e.target.value)} } /> <input type="time" value={deadlineTime} onChange={(e) =>{setDeadlineTime(e.target.value)} } /> {(deadlineDate || deadlineTime) && <button onClick={() => {setDeadlineDate('');setDeadlineTime('')} }>Remove Deadline</button>}
        <br /><em>Example: early bird rate to register for a conference.</em></p>
        <p><label>Multiple (optional)</label> <input type="number" value={multiple} onChange={(e) =>{setMultiple(e.target.value)} } />
        <br /><em>Example: price for a table of 8 at a dinner, rather than a single registration.</em></p>
        <p><button disabled={isLoading} onClick={postPrice}>{(toUpdate > -1) ? 'Update Price' : 'Add Price'}</button> {status}</p>

        <h2>Discounts</h2>
        {isLoading && <p>Loading discounts ...</p>}
        {coupon_codes.map(  
            (code, index) => {
                let method = coupon_methods[index];
                let discount = coupon_discounts[index];
                return (
                    <div>Code: {code} {displayDiscount(discount,method)} <span><button onClick={() => { console.log('click on index',index); setCodeToUpdate(index); setCode(code); setMethod(method); setDiscount(discount); } }>Edit</button> <button onClick={() => {deleteCode(index)} }>Delete</button></span> </div>
                )
            }
        )
        
        }
        <div>
        <TextControl label="Coupon Code" value={code} onChange={(value) => {setCode(value)} } />
        <RadioControl label="Method" selected={method} options={[{'label':'Discount Amount','value':'amount'},{'label':'Discount Percent','value':'percent'}]} onChange={(value) => {setMethod(value)} } />
        <TextControl label="Discount" value={discount} onChange={(value) => {setDiscount(value)} } />
        <p>{displayDiscount(discount,method)}</p>
        <p><button disabled={isLoading} onClick={postCode}>{(codeToUpdate > -1) ? 'Update Code' : 'Add Code'}</button> {status}</p>
        </div>

    </div>
}
