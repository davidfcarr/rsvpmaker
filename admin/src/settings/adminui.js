import React from "react"
import ReactDOM from "react-dom"
import apiFetch from '@wordpress/api-fetch';
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import RSVPAdmin from './RSVPAdmin.js'

if ( window?.rsvpmaker_rest?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.rsvpmaker_rest.nonce ) );
}

window.addEventListener('load', function(event) {
    const doc = document.getElementById('rsvpmaker-admin');
    if (!doc) {
        return;
    }
    const form_id = doc.getAttribute('form_id');
        ReactDOM.render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <RSVPAdmin form_id={form_id} />
                </QueryClientProvider>
          </React.StrictMode>,
                doc);        

});
