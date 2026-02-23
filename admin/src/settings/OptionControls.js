import React, {useState, useEffect, Suspense} from "react"
import {useSaveControls} from './SaveControls';
import { __experimentalNumberControl as NumberControl, SelectControl, ToggleControl, TextControl, RadioControl } from '@wordpress/components';

export function OptionsToggle (props) {
    const {rsvp_options,addChange} = props;
    const [on,setOn] = useState(rsvp_options[props.slug] == 1);
    return (
    <ToggleControl label={props.label} 
    checked={on} onChange={() => { let value = !on; setOn(value); addChange(props.slug,(value) ? 1 : 0); }}
    />)
}

export function OptSelect (props) {
    console.log('optselect props',props);
    const {rsvp_options,addChange} = props;
    const [choice,setChoice] = useState(rsvp_options[props.slug]);
    console.log('optselect slug',props.slug);
    console.log('optselect choice',choice);
    console.log('optselect rsvp_options',rsvp_options);
    return (
    <SelectControl label={props.label} value={choice} options={props.options} onChange={(value) => { setChoice(value); addChange(props.slug,value);}} />)
}

export function OptRadio (props) {
    const {rsvp_options,addChange} = props;
    const [choice,setChoice] = useState(rsvp_options[props.slug]);
    console.log('choice',choice);
    return (
    <RadioControl label={props.label} 
    selected={choice} options={props.options} onChange={(value) => { setChoice(value); addChange(props.slug,value);}}
    />)
}

export function OptText (props) {
    const {rsvp_options,addChange} = props;
    const [text,setText] = useState(rsvp_options[props.slug]);
    return (
    <>
    <input type="text" value={text} onChange={(e) => {setText(e.target.value); console.log(e.target.value)}} onBlur={() => {addChange(props.slug,text); } } />
    </>)
}

export function OptTextArea (props) {
    const {rsvp_options,addChange} = props;
    const [text,setText] = useState(rsvp_options[props.slug]);
    return (
    <>
    <textarea type="text" value={text} onChange={(e) => {setText(e.target.value); console.log(e.target.value)}} onBlur={() => { addChange(props.slug,text); } } />
    </>)
}
