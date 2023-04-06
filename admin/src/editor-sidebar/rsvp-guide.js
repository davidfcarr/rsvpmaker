import React, {useState} from "react"
import RSVPGuide from "./RSVPGuide";

export default function RSVPGuideWrapper(props) {
    const [ isOpen, setOpen ] = useState( true );

    return (

        {isOpen && (
            <RSVPGuide {...props} onFinish={ () => setOpen( false ) } />
            )
        }
    )
}