import React, {useState, useEffect} from "react"
import { ToggleControl, TextControl, RadioControl, TextareaControl } from '@wordpress/components';
import {SelectCtrl} from './Ctrl.js'
import { SanitizedHTML } from "./SanitizedHTML.js";
import {useSaveControls} from './SaveControls';
import {Up,Down,Delete} from './icons.js';
import apiClient from './http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';

export default function Forms (props) {
    const [formId,setFormId] = useState(props.form_id);
    const [addfield,setAddfield] = useState('rsvpmaker/formfield');
    const [addfieldLabel,setAddfieldLabel] = useState('');
    const [addfieldChoices,setAddfieldChoices] = useState('');
    const [showPreview,setShowPreview] = useState(false);
    const [newForm,setNewForm] = useState('');
    const {isSaving,saveEffect,SaveControls,makeNotification} = useSaveControls();
    const {changes,addChange,setChanges} = props;
    const event_id = wp?.data?.select("core/editor")?.getCurrentPostId();
    console.log('Forms props',props);
    console.log('Forms formId',formId);
    const [editForm,setEditForm] = useState('');

function fetchForms() {
    let name = newForm;
    if(name) {
        setEditForm('');
        setNewForm('');
    }
    return apiClient.get('rsvp_form?form_id='+formId+name+'&post_id='+wp?.data?.select("core/editor")?.getCurrentPostId());
}
const {data,isLoading,isError} = useQuery(['rsvp_form',formId], fetchForms, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
    if(!formId)
        setFormId(data.data.form_id);
    else if(data.data.form_changed)
        setFormId(data.data.form_id);//strip off 'clone'
    console.log('rsvp forms query',data);
}, onError: (err, variables, context) => {
    console.log('error retrieving rsvp forms',err);
}, refetchInterval: false });

if(isError)
    return <p>Error loading form options</p>

const queryClient = useQueryClient();
async function updateForm (form) {
    console.log('updateForm');
    return await apiClient.post('rsvp_form?form_id='+formId+'&post_id='+wp?.data?.select("core/editor")?.getCurrentPostId(), {'form':form,'newForm':newForm,'event_id':(props.event_id) ? props.event_id : 0});
}

const {mutate:formMutate} = useMutation(updateForm, {
    onMutate: async (form) => {
        const previousValue = queryClient.getQueryData(['rsvp_form',formId]);
        console.log('optimistic update form',form);
        await queryClient.cancelQueries(['rsvp_form',formId]);
        queryClient.setQueryData(['rsvp_form',formId],(oldQueryData) => {
            //function passed to setQueryData
            const {data} = oldQueryData;
            data.form = form;
            const newdata = {
                ...oldQueryData, data: data
            };
            console.log('newdata optimistic form update',newdata);
            return newdata;
        }) 
        //makeNotification('Updating ...');
        console.log('updating options');
        return {previousValue}
    },
    onSettled: (data, error, variables, context) => {
        queryClient.invalidateQueries(['rsvp_form',formId]);
    },
    onSuccess: (data, error, variables, context) => {
        console.log('updated');
        setFormId(data.data.form_id);
    },
    onError: (err, variables, context) => {
        //makeNotification('Error '+err.message);
        console.log('update options error',err);
        queryClient.setQueryData(['rsvp_form',formId], context.previousValue);
    },    
});
    
    if(isLoading)
        return <p>Loading ...</p>
       
    const form = data.data.form.filter((block) => block.blockName);
    const formOptions = data.data.form_options;
    console.log('formOptions',formOptions);
    let lastblock = form.length - 1;
    
    function toServerTs(datestr) {
        const newdate = new Date(datestr);
        return newdate.getTime()-correction;
    }

    //check for presence of guest and note
    const guestfields = [];
    for(let i = 0; i < lastblock; i++) {
        let block = form[i];
        if(block?.attrs.guestform)
            guestfields.push(i);
        else
            console.log('guestfields attr not found',i);
        if(['rsvpmaker/formnote','rsvpmaker/guests'].includes(block.blockName))
        {
            lastblock = i - 1;
            break;
        }
    };
    const addfields = [{'label':'Choose Field Type','value':''},{'label':'Text Field','value':'rsvpmaker/formfield'},{'label':'Text Area','value':'rsvpmaker/formtextarea'},{'label':'Select','value':'rsvpmaker/formselect'},{'label':'Radio Buttons (Multiple Choice)','value':'rsvpmaker/formradio'},{'label':'Add to Email List Checkbox','value':'rsvpmaker/formchimp'}];
    let hasguests = false;
    let hasnote = false;
    for(let i = 0; i < form.length; i++) {
        let block = form[i];
        console.log('check for end fields',block);
        if('rsvpmaker/guests' == block.blockName)
        {
            hasguests = true;
            console.log('check for end fields found guests');
        }
        if('rsvpmaker/formnote' == block.blockName)
        {
            hasnote = true;
            console.log('check for end fields found note');
        }
    };
    if(!hasguests)
        addfields.push({'label':'Guest Fields','value':'rsvpmaker/guests'});
    if(!hasnote)
        addfields.push({'label':'Note Field','value':'rsvpmaker/formnote'});
    
    function DeleteButton(props) {
            const [deletemode,setDeleteMode] = useState(false);
            const {blockindex} = props;
                return <>{!deletemode && <button className="blockmove deletebutton" onClick={() => {setDeleteMode(true);}}><Delete /> Delete</button>} {deletemode && <button className="blockmove" onClick={() => {moveBlock(blockindex, 'delete');}}><Delete /> Confirm Delete</button>} </>
    }

    function moveBlock(blockindex,direction) {
        const newform = [];
        const current = form[blockindex];
        let placed = ('delete' == direction) ? true : false;
        form.forEach( (block,index) => {
            if((null == block.blockName) || (index == blockindex))
                ;
            else {
                if(('up' == direction) && (index == (blockindex -1))) {
                    newform.push(current);
                    placed = true;
                }
                if(('down' == direction) && (index == (blockindex +2)))
                    {
                        newform.push(current);
                        placed = true;
                    }
                newform.push(block);
            }            
        });
        if(!placed)
            newform.push(current);
        console.log('newform',newform);
        formMutate(newform);
    }

    function addFieldNow() {
        if(!addfield)
            return;
        console.log(addfield);
        console.log(addfieldLabel);
        let newfield;
        if('rsvpmaker/formnote' == addfield)
            newfield = {"blockName":"rsvpmaker/formnote","attrs":[],"innerBlocks":[],"innerHTML":"","innerContent":[]};
        else if('rsvpmaker/guests' == addfield)
            newfield = {"blockName":"rsvpmaker/guests","attrs":[],"innerBlocks":[{"blockName":"core/paragraph","attrs":[],"innerBlocks":[],"innerHTML":"\n<p><\/p>\n","innerContent":["\n<p><\/p>\n"]}],"innerHTML":"\n<div class=\"wp-block-rsvpmaker-guests\"><\/div>\n","innerContent":["\n<div class=\"wp-block-rsvpmaker-guests\">",null,"</div>\n"]};
        else {
            if(!addfieldLabel) {
                alert('a field label is required');
                return;
            }
            let slug = addfieldLabel.toLowerCase().replace(/[^a-z0-9]/,'_');
            if('first_name' == slug)
                slug = 'first';
            if('last_name' == slug)
                slug = 'last';
            newfield = {'blockName':addfield,'innerHTML':'','innerBlocks':[],'innerContent':[],'attrs':{'label':addfieldLabel,'slug':slug}};
        }
        if(addfieldChoices)
            newfield.attrs.choicearray = addfieldChoices.split('\n');
        console.log('newfield',newfield);
        const newform = [];
        form.forEach(
            (block,blockindex) => {
                newform.push(block);
                if(blockindex == lastblock)
                    newform.push(newfield);
            }
        );
        formMutate(newform);
        setAddfield('rsvpmaker/formfield');
        setAddfieldLabel('');
        setAddfieldChoices('');
    }

    function setFormItemAttr(blockindex,field,value) {
        const newform = [...form];
        newform[blockindex].attrs[field] = value;
        formMutate(newform);
    }

    function FormItem(props) {
        const {blockindex, setFormItemAttr} = props;
        const [choices,setChoices] = useState(props.attrs.choicearray);
        const [req,setReq] = useState(props.attrs.required);
        const [label,setLabel] = useState(props.attrs.label);
        const [guestform,setGuestform] = useState(props.attrs.guestform);
        const [defaultToFirst,setDefaultToFirst] = useState(props.attrs.defaultToFirst);
        return (
        <div className="formAtts">
        {['rsvpmaker/formfield','rsvpmaker/formselect','rsvpmaker/formradio','rsvpmaker/formtextarea'].includes(props.blockName) && <p><label>Field Label</label> <input type="text" value={label} onChange={(e) => { setLabel(e.target.value); } } onBlur={() => {setFormItemAttr(blockindex,'label',label)} } /></p>}
        {props.attrs.choicearray && <p>Choices<br /><textarea rows="5" value={choices.join('\n')} onChange={(e) => { let newchoices = e.target.value.split('\n'); setChoices(newchoices); }} onBlur={() => {setFormItemAttr(blockindex,'choicearray',choices)} } /><br /><em>Enter one choice per line</em></p>}
        {['rsvpmaker/formfield','rsvpmaker/formselect','rsvpmaker/formradio','rsvpmaker/formtextarea'].includes(props.blockName) && (props.attrs.slug != 'email') && <ToggleControl label="Required" checked={req == 'required'} onChange={() => { let newr = (req=='required') ? '' : 'required'; setReq(newr); setFormItemAttr(blockindex,'required',newr) }} />}
        {(props.attrs.slug == 'email') && <p><em>Email is always required</em></p>}
        {['rsvpmaker/formfield','rsvpmaker/formselect','rsvpmaker/formradio','rsvpmaker/formtextarea'].includes(props.blockName) && <ToggleControl label="Include on Guest Form" checked={guestform} onChange={() => { let newg = !guestform; setGuestform(newg); setFormItemAttr(blockindex,'guestform',newg); }} />}
        {['rsvpmaker/formradio'].includes(props.blockName) && <ToggleControl label="Default to First Item" checked={defaultToFirst} onChange={() => { let newg = !defaultToFirst; setDefaultToFirst(newg); setFormItemAttr(blockindex,'defaultToFirst',newg); }} />}
        </div>)
    }

    function Guests() {
        console.log('guestfields',guestfields);
        const [open,setOpen] = useState(false);
        if(!open)
            return (<p><a onClick={() => {setOpen(true)} }>+ Add Guests</a></p>);
        return (<div><p>Guest 1</p>
            {guestfields.map(
                (i) => {
                    const block = form[i];
                    console.log('guestfields forEach',block);
                    let choices = [];
                    if(block?.attrs.choicearray)
                        choices = block.attrs.choicearray.map((item) => {return {'label':item,'value':item}} );
                return (
                <div className="formAtts">
                {('rsvpmaker/formfield' == block.blockName) && <TextControl label={block.attrs.label} />}
                {('rsvpmaker/formtextarea' == block.blockName) && <TextareaControl label={block.attrs.label} />}
                {('rsvpmaker/formselect' == block.blockName) && <SelectCtrl label={block.attrs.label} options={choices} />}
                {('rsvpmaker/formradio' == block.blockName) && <RadioControl label={block.attrs.label} options={choices} />}
                </div>               
                    )
                }
            )}
        </div>)        
    }

    let fieldlabel = '';
    let isrsvp = true;

    return (<div className="rsvptab">
    {data.data.is_default && <div><h3>Default Form Selected</h3><p>Changes you make below will apply to all events that use the default form. Switch to a custom form to make customizations for this event only. Forms can also be customized from an event template.</p></div>}
    {data.data.is_inherited && <div><h3>Form Is Inherited</h3><p>This form is inherited from a template or another document. Changes you make below will apply to all events that use the same form. Switch to a custom form to make customizations for this event only. Forms can also be customized from an event template.</p></div>}
    <ToggleControl label="Show Form Preview" checked={showPreview} onChange={() => {setShowPreview(!showPreview)} } />
    {showPreview && form.map((block, blockindex) => {
        isrsvp = block.blockName.indexOf('rsvpmaker') > -1;
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
                {('rsvpmaker/guests' == block.blockName) && <Guests />}
                {('rsvpmaker/formchimp' == block.blockName) && <p><input type="checkbox" /> Add me to your email list</p>}
                {!isrsvp && block.innerHTML && <SanitizedHTML innerHTML={block.innerHTML} />}
            </div>
        );
    })}
    <h4>Form Editing Controls</h4>
    {!props.single_form && <div><SelectCtrl label="Switch Form" value={editForm} options={formOptions} onChange={(id) => {
        if(id.toString().includes('clone')) {
            let name = prompt('Name for reusable form? (optional)');
            if(name)
                name = '|'+name;
            setNewForm(name);
            console.log('new form name ',name);
        }        
         setFormId(id); setEditForm(id); console.log('new form id '+id); 
         }} /> Currently Selected: {data.data.current_form}</div>}
    {(editForm || ('Custom' == data.data.current_form) || data.data.current_form.includes('Reusable')) &&  <p>You can also edit this form as a separate document <a target="_blank" href={'/wp-admin/post.php?action=edit&post='+formId}>in the WordPress editor</a>.</p>}
    {(editForm || ('Custom' == data.data.current_form) || data.data.current_form.includes('Reusable')) &&
        form.map((block, blockindex) => {
        fieldlabel = block.blockName.replace(/^[^/]+\//,'');
        isrsvp = block.blockName.indexOf('rsvpmaker') > -1;
        if('formchimp' == fieldlabel)
            fieldlabel = 'Add to Email List Checkbox';
        else if('formnote' == fieldlabel)
            fieldlabel = 'NOTE';
        else if('formfield' == fieldlabel)
            fieldlabel = 'TEXT FIELD';
        else if('formselect' == fieldlabel)
            fieldlabel = 'SELECT';
        else if('formradio' == fieldlabel)
            fieldlabel = 'CHOICE';
        else
            fieldlabel = fieldlabel.toUpperCase();

        if(null == block.blockName)
            return;
        return (
            <div className="formblock">
            <div class="blockheader">
            <div className="reorganize-drag">
                <div className="titleline"><h2>{fieldlabel} {block.attrs.label && <span className="fieldlabel">{block.attrs.label}</span>}</h2> {!['rsvpmaker/formnote','rsvpmaker/guests'].includes(block.blockName) && blockindex > 0 && <button className="blockmove" onClick={() => { moveBlock(blockindex, 'up') } }><Up /></button>}  {!['rsvpmaker/formnote','rsvpmaker/guests'].includes(block.blockName) && (blockindex != lastblock) && <button className="blockmove" onClick={() => { moveBlock(blockindex, 'down') } }><Down /></button>} <DeleteButton blockindex={blockindex} /> </div>
                {'rsvpmaker/guests'== block.blockName && <p><em>Gathers information about guests.</em></p>}
                {'rsvpmaker/formnote'== block.blockName && <p><em>A free text entry note at the bottom of the form.</em></p>}
                {'rsvpmaker/formchimp'== block.blockName && <p><em>Displays an Add to Email List checkbox on the form</em></p>}
                {'rsvpmaker/formradio'== block.blockName && <p><em>Multiple choice. Prices for options can be set on the Pricing tab for an event.</em></p>}
                {!isrsvp && block.innerHTML && <div><SanitizedHTML innerHTML={block.innerHTML} /> <br /><em><a href={'/wp-admin/post.php?action=edit&post='+formId}>Open in the WordPress editor</a></em></div>}
                <FormItem attrs={block.attrs} blockName={block.blockName} blockindex={blockindex} setFormItemAttr={setFormItemAttr} />
                </div>
            </div>
 
    </div>)
    })}
    {(editForm || ('Custom' == data.data.current_form) || data.data.current_form.includes('Reusable')) && <div>
    <h3>Add a form field</h3><p><SelectCtrl label="Type" options={addfields} value={addfield} onChange={((value) => {setAddfield(value);})} /> {!['rsvpmaker/formnote','rsvpmaker/guests','rsvpmaker/formchimp'].includes(addfield) && <TextControl label="Field Label" value={addfieldLabel} onChange={((value) => {setAddfieldLabel(value);})} />} </p>
   {['rsvpmaker/formselect','rsvpmaker/formradio'].includes(addfield) && <p><label>Choices</label><br /><textarea value={addfieldChoices} onChange={(e) => { setAddfieldChoices(e.target.value); }} /><br /><em>Enter one choice per line</em></p> }
   <p><button onClick={addFieldNow}>Add</button></p>
   {data.data.copied && <p>Copied <strong>{data.data.copied}</strong></p>}
    </div>
    }
 
    </div>);
}
