import React from "react"
import ReactDOM from "react-dom"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import FormWrapper from './FormWrapper.js'

window.addEventListener('load', function(event) {
    const doc = document.getElementById('react_event_options');
    const form_id = doc.getAttribute('form_id');
    const event_id = doc.getAttribute('event_id');
        ReactDOM.render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <FormWrapper form_id={form_id} event_id={event_id} />
                </QueryClientProvider>
          </React.StrictMode>,
                doc);        

});
