import React from "react"
import ReactDOM from "react-dom"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import RSVPAdmin from './RSVPAdmin.js'

window.addEventListener('load', function(event) {
    const doc = document.getElementById('rsvpmaker-admin');
    const form_id = doc.getAttribute('form_id');
        ReactDOM.render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <RSVPAdmin form_id={form_id} />
                </QueryClientProvider>
          </React.StrictMode>,
                doc);        

});
