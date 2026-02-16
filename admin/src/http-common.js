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
	return axios.create( buildAxiosObject( settings ) );
}

// Create a basic instance for backward compatibility
export default axios.create();
