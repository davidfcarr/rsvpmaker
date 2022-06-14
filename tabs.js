jQuery( document ).ready(
	function( $ ) {
        $('section').hide();
        $('section').first().show();
		$( document ).on(
			'click',
			'.rsvpmaker-nav-tab-wrapper a, .nav-tab-wrapper a',
			function() {
				$( '.rsvpmaker-nav-tab, .nav-tab' ).removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' );
				$( 'section' ).hide();
				$( 'section' ).eq( $( this ).index() ).show();
				return false;
			}
		);
        /*
		if($('#activetab')) {
			var activetab = '#'+$('#activetab').val();
			$( 'section' ).hide();
			$( 'section' + activetab ).show();
		}
		else {
			var active = $( '.nav-tab-active' );
			if (active) {
			var activetab = active.attr('href');
			$( 'section' ).hide();
			$( 'section' + activetab ).show();
			}
		}
        */
});
