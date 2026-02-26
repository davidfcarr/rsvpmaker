import axios from "axios";

function buildAxiosObject( rsvpmaker_rest ) {
	return {
		baseURL: rsvpmaker_rest.rest_url + 'rsvpmaker/v1/',
		headers: {
			"Content-type": "application/json",
			'X-WP-Nonce': rsvpmaker_rest.nonce,
		},
		validateStatus: function ( status ) {
			return status < 400; // Resolve only if the status code is less than 400
		}
	}
}

// Export a function that creates a configured axios instance
export function createConfiguredAxios( settings ) {
	const config = buildAxiosObject( settings );
	console.log('Creating configured axios with config',config);
	return axios.create( config );
}

// Create a basic instance for backward compatibility
export default axios.create();

/* setup to use apiClient in components, but need to wait until we have the settings from the store, which is why we export the function above and use that in the component instead of this default instancenp
import { createConfiguredAxios } from './http-common.js';

    const rsvpmaker_rest = useSelect( ( select ) => {
    const rs = select( 'rsvpmaker' );
    if(!rs)
    {
        
        return {};
    }
    const rsvpmaker_rest = rs.getSettings();
    return rsvpmaker_rest;
    } );

    const apiClient = createConfiguredAxios( rsvpmaker_rest );
*/
