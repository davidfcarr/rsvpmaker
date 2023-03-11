import React from "react"
import ReactDOM from "react-dom"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import RSVPAdmin from './RSVPAdmin.js'

window.addEventListener('load', function(event) {
        ReactDOM.render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <RSVPAdmin />
                </QueryClientProvider>
          </React.StrictMode>,
                document.getElementById('rsvpmaker-admin'));        

});
