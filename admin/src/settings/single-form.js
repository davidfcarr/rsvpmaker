import React from "react"
import ReactDOM from "react-dom"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import Forms from './Forms.js'

window.addEventListener('load', function(event) {
    const doc = document.getElementById('rsvpmaker-single-form');
    const form_id = doc.getAttribute('form_id');
        ReactDOM.render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <Forms form_id={form_id} single_form={true} />
                </QueryClientProvider>
          </React.StrictMode>,
                doc);        

});
