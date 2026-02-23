import React from "react"
import ReactDOM from "react-dom"
import DateTimeMaker from "./DateTimeMaker.js";

window.addEventListener('load', function(event) {
    const doc = document.getElementById('react_date_time');
    const event_id = doc.getAttribute('event_id');
        ReactDOM.render(
            <React.StrictMode>
                <DateTimeMaker event_id={event_id} />
          </React.StrictMode>,
                doc);
});
