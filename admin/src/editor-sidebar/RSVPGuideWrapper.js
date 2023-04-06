import React, {useState} from "react"
import RSVPGuide from "./RSVPGuide";
const {ToggleControl} = wp.components;

export default function RSVPGuideWrapper(props) {
    const [ isOpen, setOpen ] = useState( true );

    return (
        <div>
        <ToggleControl label="Show RSVPMaker Guide" checked={isOpen} onClick={() => {setOpen(!isOpen)} } />
        {isOpen && (
            <RSVPGuide {...props} onFinish={ () => setOpen( false ) } />
            )
    }</div>
    )
}