const DEFAULT_STATE = {
	date: '',
};

// This is the reducer
function reducer( state = DEFAULT_STATE, action ) {
  var newstate = state;
  if ( action.type === 'UPDATE_DATE' ) {
    newstate.date = action.date;
  }
  return newstate;
}

//actions
//wp.data.select('rsvpevent').getRSVPdate() wp.data.dispatch('rsvpevent').setRSVPdate()
function setRSVPdate( date ) {
  return {
    type: 'UPDATE_DATE',
    date: date,
  };
}

// selectors
function getRSVPdate( state ) {
  return state.date;
}

// Now let's register our custom namespace
var myNamespace = 'rsvpevent';
wp.data.registerStore( 'rsvpevent', { 
  reducer: reducer,
  selectors: { getRSVPdate: getRSVPdate },
  actions: { setRSVPdate: setRSVPdate },
} );
