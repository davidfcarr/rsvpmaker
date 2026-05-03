import axios from "axios";

const baseURL = (window.rsvpmaker && window.rsvpmaker.json_url)
  ? window.rsvpmaker.json_url
  : '/?rest_route=/rsvpmaker/v1/';

const client = axios.create({
  baseURL,
  headers: {
    "Content-type": "application/json",
  },
  validateStatus: function (status) {
    return status < 400; // Resolve only if the status code is less than 400
  }
});

export function setupNonceInterceptor(nonce) {
  client.interceptors.request.use((config) => {
    config.headers['X-WP-Nonce'] = nonce;
    return config;
  });
}

export default client;