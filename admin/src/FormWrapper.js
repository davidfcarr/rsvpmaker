import React, {useState, lazy, Suspense} from "react"

const Forms = lazy(() => import('./Forms.js'));
export default function FormWrapper(props) {
    const {form_id,event_id} = props;
    const [open,setOpen] = useState(false);
    if(open)
    return (
        <Suspense fallback={<p><em>Loading form editor ...</em></p>}>
            <Forms form_id={form_id} event_id={event_id} />
        </Suspense>
    )
    else
    return <p><button onClick={(e) => {e.preventDefault(); setOpen(true)}}>Customize Form</button></p>
}
