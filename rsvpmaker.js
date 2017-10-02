jQuery(document).ready(function($) {
	$('.timezone_on').click( function () {

		$('.timezone_hint').each( function () {
		
		var utc = $(this).attr('utc');
		var localdate = new Date(utc);
		localstring = localdate.toString();
		$(this).html('<br />'+localstring);
		
		});
	});
});