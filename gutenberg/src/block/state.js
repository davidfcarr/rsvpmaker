var display = (rsvpmaker_ajax._rsvp_end_display) ? rsvpmaker_ajax._rsvp_end_display : '';
var end = (rsvpmaker_ajax._rsvp_end) ? rsvpmaker_ajax._rsvp_end : '';

const DEFAULT_STATE = {
	date: '',
	endtime: {"display":display,"end":end},
};

// This is the reducer
function reducer( state = DEFAULT_STATE, action ) {
  var newstate = state;
  if ( action.type === 'UPDATE_DATE' ) {
    newstate.date = action.date;
  }
  if ( action.type === 'UPDATE_ON' ) {
    newstate.on = action.on;
  }
  if ( action.type === 'UPDATE_END' ) {
    newstate.end = action.end;
  }
  if ( action.type === 'UPDATE_END_DISPLAY' ) {
    newstate.end_display = action.end_display;
  }
  if ( action.type === 'UPDATE_END_TIME' ) {
    newstate.endtime = action.endtime;
  }
  return newstate;
}

function setEndTime( endtime ) {
  return {
    type: 'UPDATE_END_TIME',
    endtime: endtime,
  };
}

function setRSVPdate( date ) {
  return {
    type: 'UPDATE_DATE',
    date: date,
  };
}

function setRSVPMakerOn( on ) {
  return {
    type: 'UPDATE_ON',
    on: on,
  };
}

function setRSVPEnd( end ) {
  return {
    type: 'UPDATE_END',
    end: end,
  };
}

function setRSVPEndDisplay( end_display ) {
  return {
    type: 'UPDATE_END',
    end_display: end_display,
  };
}

function setRsvpMeta( key, value ) {
if(key == '_rsvp_on')
  return {
    type: 'UPDATE_ON',
    on: value,
  };
}

// selectors

function getEndTime( state ) {
  return state.endtime;
}

function getRSVPdate( state ) {
  return state.date;
}

function getRSVPMakerOn( state ) {
  return state.on;
}

function getRSVPEnd( state ) {
  return state.end;
}

function getRSVPEndDisplay( state ) {
  return state.end_display;
}

// Now let's register our custom namespace
var myNamespace = 'rsvpevent';
wp.data.registerStore( 'rsvpevent', { 
  reducer: reducer,
  selectors: { getRSVPdate: getRSVPdate, getRSVPMakerOn: getRSVPMakerOn, getRSVPEnd: getRSVPEnd, getRSVPEndDisplay: getRSVPEndDisplay, getEndTime, getEndTime },
  actions: { setRSVPdate: setRSVPdate, setRsvpMeta: setRsvpMeta, setRSVPEnd: setRSVPEnd, setRSVPEndDisplay: setRSVPEndDisplay, setEndTime: setEndTime  },
} );
