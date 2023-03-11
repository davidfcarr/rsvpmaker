import axios from "axios";

export default axios.create({
  baseURL: '/wp-json/rsvpmaker/v1/',
  headers: {
    "Content-type": "application/json",
    'X-WP-Nonce': rsvpmaker_rest.nonce,
  },
  validateStatus: function (status) {
    return status < 400; // Resolve only if the status code is less than 400
  }
});
