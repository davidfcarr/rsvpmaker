import React from "react"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import Forms from '../Forms.js';

export default function FormSetup (props) { 
    return (
        <QueryClientProvider client={queryClient}>
            <Forms form_id={rsvpmaker_rest.form_id} event_id={rsvpmaker_rest.event_id} />
        </QueryClientProvider>
    );
};
