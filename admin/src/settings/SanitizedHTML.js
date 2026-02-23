import React from "react"
import DOMPurify from 'dompurify';

export function SanitizedHTML(props) {
    const {innerHTML} = props;
    const cleanHTML = DOMPurify.sanitize(innerHTML).replace('class=','className=');
    return <div dangerouslySetInnerHTML={{__html: cleanHTML}}></div>
}