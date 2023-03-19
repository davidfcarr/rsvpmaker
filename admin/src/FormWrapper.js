import React, {useState} from "react"
import Forms from './Forms.js';
export default function FormWrapper(props) {
    const {form_id,event_id} = props;
    const [open,setOpen] = useState(false);
    if(open)
    return <Forms form_id={form_id} event_id={event_id} />
    else
    return <p><button onClick={(e) => {e.preventDefault(); setOpen(true)}}>Customize Form</button></p>
}
