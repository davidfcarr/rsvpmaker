import React from "react"
import ReactDOM from "react-dom"
import { QueryClient, QueryClientProvider } from "react-query";
const queryClient = new QueryClient();

import TemplateProjected from "./editor-sidebar/TemplateProjected";

window.addEventListener('load', function(event) {
    const doc = document.getElementById('rsvpmaker-template-metabox');
    ReactDOM.render(
    <React.StrictMode>
        <QueryClientProvider client={queryClient}>
            <TemplateProjected />
        </QueryClientProvider>
    </React.StrictMode>,
        doc);
});
