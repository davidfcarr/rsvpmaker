import React, {useState, useEffect, useRef} from "react"

export function SelectCtrl (props) {
    const style = {'display': (props.display) ? props.display : 'block', 'maxWidth': (props.width) ? props.width : '350px' };
    return (
        <div style={style}>
            <label style={{'display':'block','fontSize':'11px','textTransform':'uppercase'}}>{props.label}</label>
            <select value={props.value} onChange={(e) => props.onChange(e.target.value)}>
                {props.options.map((o) => <option value={o.value}>{o.label}</option>)}
            </select>
        </div>
    )
}

export function NumberCtrl (props) {
    const style = {'display': (props.display) ? props.display : 'block', 'maxWidth': (props.maxWidth) ? props.maxWidth : '300px' };
    return (
        <div style={style}>
            <label style={{'display':'block','fontSize':'11px','textTransform':'uppercase'}}>{props.label}</label>
            <input type="number" value={(props.value) ? props.value : 0} onChange={(e) => props.onChange(e.target.value)} />
        </div>
    )
}
/*
export function RadioCtrl (props) {
    const buttonDisplay = {'display': (props.buttonDisplay) ? props.buttonDisplay : 'inline-block' };
    return (
        <div onChange={(e) => props.onChange(e.target.value)}>
            <label style={{'display':'block','fontSize':'11px','textTransform':'uppercase'}}>{props.label}</label>
            <input type="number" value={(props.value) ? props.value : 0} onChange={(e) => props.onChange(e.target.value)} />
        </div>
    )
}
*/