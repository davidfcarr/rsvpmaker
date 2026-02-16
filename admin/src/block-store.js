import { createReduxStore, register } from '@wordpress/data';

const DEFAULT_STATE = {
    settings: window.rsvpmakerSettings || {},
};

const store = createReduxStore( 'rsvpmaker', {
    reducer: ( state = DEFAULT_STATE ) => state,

    selectors: {
        getSettings: ( state ) => state.settings,
        getRestUrl: ( state ) => state.settings.rest_url,
        getRsvpJsonUrl: ( state ) => state.settings.rsvpmaker_json_url,
        getNonce: ( state ) => state.settings.nonce,
        getPostId: ( state ) => state.settings.post_id,
    },
} );

register( store );