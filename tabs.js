jQuery( document ).ready(
	function( $ ) {
        $('.rsvpmaker section').hide();
		$( 'section' ).eq( $( '.rsvpmaker-nav-tab-wrapper .nav-tab-active' ).index() ).show().css('background-color','#fff');
		$( document ).on(
			'click',
			'.rsvpmaker-nav-tab-wrapper a, .nav-tab-wrapper a',
			function() {
				$( '.rsvpmaker-nav-tab, .nav-tab' ).removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' );
				$( 'section' ).hide();
				$( 'section' ).eq( $( this ).index() ).show().css('background-color','#fff');
				return false;
			}
		);
});
