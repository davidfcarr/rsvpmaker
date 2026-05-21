( function () {
	function maybeRefreshExpiredRSVPMakerLoops() {
		if ( typeof window.URL === 'undefined' || typeof window.URLSearchParams === 'undefined' ) {
			return;
		}

		var currentUrl = new window.URL( window.location.href );
		if ( currentUrl.searchParams.has( 'rsvpmaker_refresh' ) ) {
			return;
		}

		var nowTs = Math.floor( Date.now() / 1000 );
		var wrappers = document.querySelectorAll( '[data-rsvpmaker-expire-after-end="1"][data-rsvpmaker-first-ts-end]' );

		for ( var i = 0; i < wrappers.length; i++ ) {
			var tsEnd = parseInt( wrappers[ i ].getAttribute( 'data-rsvpmaker-first-ts-end' ), 10 );
			if ( tsEnd && tsEnd <= nowTs ) {
				currentUrl.searchParams.set( 'rsvpmaker_refresh', String( nowTs ) );
				window.location.replace( currentUrl.toString() );
				return;
			}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', maybeRefreshExpiredRSVPMakerLoops );
	} else {
		maybeRefreshExpiredRSVPMakerLoops();
	}
} )();
