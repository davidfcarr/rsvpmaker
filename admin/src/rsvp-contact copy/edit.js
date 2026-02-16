/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
//import Forms from '../Forms.js';
import { SanitizedHTML } from "../SanitizedHTML.js";
import { PanelBody, TextControl, RadioControl, TextareaControl, ToggleControl } from '@wordpress/components';
import {SelectCtrl} from '../Ctrl.js'

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import React, { useState, useEffect } from 'react';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */

export default function Edit(props) {
    const { attributes, setAttributes, isSelected } = props;
    const {form_id} = attributes;
    const [form,setForm] = useState([]);
    const [formOptions,setFormOptions] = useState([]);
    console.log('form_id',form_id);
    async function getForm() {
        const url = "/wp-json/rsvpmaker/v1/rsvp_form?form_id="+form_id+"&post_id=0&contact=1"
        console.log('url',url);
        try {
            const response = await fetch(url);
            if (!response.ok) {
              throw new Error(`Response status: ${response.status}`);
            }
            console.log('extracting json');
            const json = await response.json();
            if(json && json.form)
                setForm(json.form);
            if(json && json.form_id)
                setAttributes({'form_id':json.form_id});
            if(json && json.form_options) {
                let formoptions = json.form_options.filter((option) => {return (!option.label.includes('Clone') && !option.label.includes('Select'))} );
                formoptions = formoptions.map( (option) => { option.label = option.label.replace('Edit',''); return option; } );
                setFormOptions(formoptions);
            }
          } catch (error) {
            console.error(error.message);
          }
    }
    useEffect(() => {
        getForm();
      }, [form_id]);
    if(!form.length)
    getForm();

    const fieldslist = [{'label':'Field containing order information','value':''}];
    if(form.length > 0)
    form.map((block, blockindex) => {
        if(block.attrs && block.attrs.label && block.attrs.slug)
            fieldslist.push({'label':block.attrs.label,'value':block.attrs.slug});
    })

    if(!attributes.unique_id) {
        setAttributes({'unique_id':'rsvpcontactform'+Date.now()});
    }

    return (
        <div { ...useBlockProps() }>
        <InspectorControls key="contactinspector">
            <PanelBody title={ __( 'Contact Form', 'rsvpmaker' ) } >
            <p><a href="https://rsvpmaker.com/knowledge-base/contact-form-payment-gift-certificate/" target="_blank">{__('Documentation','rsvpmaker')}</a></p>
            <SelectCtrl label="Switch Form" value={form_id} options={formOptions} onChange={(id) => {
            setAttributes({'form_id':id});
             }} />
            <p><a target="_blank" href={"/wp-admin/post.php?post="+form_id+"&action=edit"}>{__('Edit form','rsvpmaker')}</a></p>
            <p>{__('To create new forms, see RSVPMaker Settings','rsvpmaker')}: <a target="_blank" href="/wp-admin/options-general.php?page=rsvpmaker_settings&tab=forms">Forms</a></p>
            <TextControl label="Email To (leave blank to use RSVPMaker default)" value={attributes.email} onChange={(value) => {
                setAttributes({'email':value});
             }} />
            {!attributes.order && <TextControl label="Subject Prefix (leave blank for 'Contact')" value={attributes.subject_prefix} onChange={(value) => {
                setAttributes({'subject_prefix':value});
             }} />}
            <SelectCtrl label="Order Field (optional)" value={attributes.order} options={fieldslist} onChange={(value) => {
                setAttributes({'order':value});
             }} />
            {attributes.order && <><SelectCtrl label="Payment Gateway" value={attributes.gateway} options={[{'label':'Default','value':'Default'},{'label':'PayPal','value':'PayPal REST API'},{'label':'Stripe','value':'Stripe'}]} onChange={(value) => {
                setAttributes({'gateway':value})
             }} />
             <SelectCtrl label="Gift Certificate?" value={attributes.gift} options={[{'label':'No','value':'0'},{'label':'Yes','value':'1'}]} onChange={(value) => {
                setAttributes({'gift':value})
             }} />
            </>}
         <p>{__('For a simple order form, using RSVPMaker PayPal/Stripe integration, include a select or radio buttons field with a value in the format product description:amount (Gift Certificate: $10). Include the word "Gift" in the description to have RSVPMaker generate a gift certificate coupon code that can be entered into the RSVP form for priced events.','rsvpmaker')}</p>
            </PanelBody>
        </InspectorControls>
        {form.length == 0 && <p>Loading form</p>}
        {isSelected && <div>See sidebar for form options</div>}
        {form.length > 0 && !attributes.order.length && <TextControl label='Subject' />}
        {form.length > 0 &&
        form.map((block, blockindex) => {
            const isrsvp = block.blockName && block.blockName.indexOf('rsvpmaker') > -1;
            console.log('block',block);
            let choices = [];
            if(block?.attrs.choicearray)
                choices = block.attrs.choicearray.map((item) => {return {'label':item,'value':item}} );
            if(null == block.blockName)
                return;
            return (
                <div>
                    {('rsvpmaker/formfield' == block.blockName) && <TextControl label={block.attrs.label} />}
                    {('rsvpmaker/formtextarea' == block.blockName) && <TextareaControl label={block.attrs.label} />}
                    {('rsvpmaker/formselect' == block.blockName) && <SelectCtrl label={block.attrs.label} options={choices} />}
                    {('rsvpmaker/formradio' == block.blockName) && <RadioControl label={block.attrs.label} options={choices} />}
                    {('rsvpmaker/formchimp' == block.blockName) && <p><input type="checkbox" /> Add me to your email list</p>}
                    {('rsvpmaker/formnote' == block.blockName) && <TextareaControl label={'Note'} />}
                    {!isrsvp && block.innerHTML && <SanitizedHTML innerHTML={block.innerHTML} />}
                </div>
            );
        })
        }
        {form.length > 0 && <p><button>Send</button></p>}
        </div>
    );
}

//<Forms />