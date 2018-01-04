jQuery(document).ready(function($) {
	$('.timezone_on').click( function () {

		$('.timezone_hint').each( function () {
		
		var utc = $(this).attr('utc');
		var target = $(this).attr('target');
		var localdate = new Date(utc);
		localstring = localdate.toString();
		$('#'+target).html('<div>'+localstring+'<div>');
		var data = {
			'action': 'rsvpmaker_localstring',
			'localstring': localstring
		};
		jQuery.post(ajaxurl, data, function(response) {
		$('#'+target).html('<div>'+response+'</div>');
		});
		
		});
	});
});