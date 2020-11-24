// JavaScript Document
jQuery(document).ready(function( $ ) {
    $.ajaxSetup({
        headers: {
            'X-WP-Nonce': rsvpmaker_rest.nonce,
        }
	});
	
	$('#quickdate-0').change(
		function () {
			var startdate = $(this).val();
			$('.quickdate').each(
				function() {
					$(this).val(startdate);
				}
			);
		}
	);

	$('.quicktime, .quicktitle').change(
		function() {
			var newvalue = $(this).val();
			var id = $(this).attr('id');
			var messagetarget = '';
			var othertarget = '';
			if(id.search('time') > 0)
			{
				messagetarget = id.replace('quicktime','quickmessage');
				othertarget = id.replace('quicktime','quicktitle');
				if(newvalue.match(/[^0-9: ampAMP]/))
					$('#'+messagetarget).html('<span style="color: red;">Date format not valid</span>');
				else if($('#'+othertarget).val() == '')
					$('#'+messagetarget).html('Both date and time are required');
				else
					$('#'+messagetarget).html('');
			}
			else {
				messagetarget = id.replace('quicktitle','quickmessage');
				othertarget = id.replace('quicktitle','quicktime');
				if($('#'+othertarget).val() == '')
					$('#'+messagetarget).html('Both date and time are required');
				else
					$('#'+messagetarget).html('<span style="color: green; font-weight: bold;">OK</span>');
			}
		}
	);

	$('select.rsvpsort').change(function() {
		var sort = $( this ).val();
		var parts = window.location.href.split('&');
		var url = parts[0]+'&rsvpsort='+sort;
		var top = $('#bulk-action-selector-top').val();
		var bottom = $('#bulk-action-selector-bottom').val();
		if((top == '-1') && (bottom == '-1'))
			window.location.replace(url);
		$('.rsvpsortwrap').html('<a style="font-size: large;" href="'+url+'">View: '+sort+'</a>');
	});

	$(document).on( 'click', '.rsvpmaker-nav-tab-wrapper a', function() {
		$('.rsvpmaker-nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('section.rsvpmaker').hide();
		$('section.rsvpmaker').eq($(this).index()).show();
		return false;
	});

	var activetab = $('#activetab').val();
	if(activetab) {
		$('section.rsvpmaker').hide();
		$('section#'+activetab).show();
		$('.rsvpmaker-nav-tab').removeClass('nav-tab-active');
		$('section#'+activetab).addClass('nav-tab-active');
		$('#activetab').val('');
	}

  $(function() {
    var dialog;
 
    function setRsvpForm() {

	var extras = '';
	var label = '';
	var radiosplit;
	var radiovalues;
	var guestfield = '';
	var checked = '';
	var field = '';
	var name_email_hidden = $('#name_email_hidden').val();
		
if(name_email_hidden == 'hidden')
	var newform = '[rsvpfield hidden="first" ][rsvpfield hidden="last"][rsvpfield hidden="email"]';
else if(name_email_hidden == 'email_first')
	var newform = '<p><label>Email:</label> [rsvpfield textfield="email" required="1"]</p>' +"\n" + '<p><label>First Name:</label> [rsvpfield textfield="first" required="1"]</p>' +"\n" +
'<p><label>Last Name:</label> [rsvpfield textfield="last" required="1"]</p>'  +"\n";
else
	var newform = '<p><label>First Name:</label> [rsvpfield textfield="first" required="1"]</p>' +"\n" +
'<p><label>Last Name:</label> [rsvpfield textfield="last" required="1"]</p>'  +"\n" +
'<p><label>Email:</label> [rsvpfield textfield="email" required="1"]</p>' +"\n";

	guestfield = ' guestfield="' + $('#guest_phoneandtype').val()+'" ';

var note = '<p>Note:<br />'+"\n"+'<textarea name="note" cols="60" rows="2" id="note">[rsvpnote]</textarea></p>';
var extrafields = $("#extrafields").val();

for(i=1; i <= extrafields; i++)
{
	var extra = $("#extra" + i).val().trim();
	var type = $("#type" + i + ' option:selected').text();
	if(type == 'text')
		type = 'textfield';
	if(extra != '')
		{
		guestfield = ' guestfield="' + $('#guest' + i).val() + '" ';
		if( $('#private' + i).is(':checked') )
			private = ' private="1" ';
		else
			private = ' private="0" ';			
		if(extra.indexOf(':') != -1 )
			{
				radiosplit = extra.split(':');
				label = radiosplit[0];
				radiovalues = radiosplit[1].split(',');
				checked = radiovalues[0].trim();
				field = label.toLowerCase().replace(/[^a-z0-9]+/g,'_');
				extras = extras.concat('<p class="'+ field +'"><label>'+label+'</label> [rsvpfield '+type+'="'+field+'" options="'+ radiosplit[1] + '" checked="' + checked + '" '+ guestfield+ ' ' + private + ']</p>'+"\n");
			}
		else
			{
			field = extra.toLowerCase().replace(/[^a-z0-9]+/g,'_');
		extras = extras.concat('<p class="'+ field +'"><label>'+extra+'</label> [rsvpfield textfield="'+field+'" '+ guestfield+ ' ' + private + ']</p>'+"\n");
			}
		}
}

	if(extras != '')
		newform = newform.concat(extras);
	var maxguests = parseInt($('#maxguests').val());
	if( $('#guests').is(':checked') && maxguests)
		newform = newform.concat('[rsvpguests max_party="'+(maxguests + 1)+'"]'+"\n");
	else if( $('#guests').is(':checked') )
		newform = newform.concat('[rsvpguests]'+"\n");
	if( $('#note').is(':checked') )
		newform = newform.concat(note);			
	if( $('#emailcheckbox').is(':checked') )
		newform = newform.concat('<p>[rsvpfield checkbox="email_list_ok" value="yes" checked="1"] Add me to your email list</p>');			
		
        $( "#rsvpform" ).val( newform );
        dialog.dialog( "close" );
      return;
    }
 
    dialog = $( "#rsvp-dialog-form" ).dialog({
      autoOpen: false,
      height: 600,
      width: 800,
      modal: true,
      buttons: {
        "Create form": setRsvpForm,
        Cancel: function() {
          dialog.dialog( "close" );
        }
      },
    });
 
    form = dialog.find( "form" ).on( "submit", function( event ) {
      event.preventDefault();
      setRsvpForm();
    });
 
    $( "#create-form" ).button().on( "click", function(event) {
      event.preventDefault();
      dialog.dialog( "open" );
    });
  });

$('#enlarge').click(function() {
  jQuery('#rsvpform').attr('rows','40');
  return false;
});

$(function() {
    var ppdialog;

function savePayPalConfig () {
	var user = $("#pp_user").val().trim();
	var password = $("#pp_password").val().trim();
	var signature = $("#pp_signature").val().trim();
	
$.post(
   ajaxurl, 
    {
        'action': 'rsvpmaker_paypal_config',
		'user' : user,
		'password' : password,
		'signature' : signature
    }, 
    function(response){
		$("#paypal_config").val(response);
    }
);

	ppdialog.dialog( "close" );
    return;
}
    ppdialog = $( "#pp-dialog-form" ).dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      modal: true,
      buttons: {
        "Save": savePayPalConfig,
        Cancel: function() {
          ppdialog.dialog( "close" );
        }
      }
    });
 
    ppform = ppdialog.find( "form" ).on( "submit", function( event ) {
      event.preventDefault();
      savePayPalConfig();
    });
 
    $( "#paypal_setup" ).button().on( "click", function(event) {
      event.preventDefault();
      ppdialog.dialog( "open" );
    });
  });

  $( function() {
 $("#datepicker0, #datepicker1, #datepicker2, #datepicker3, #datepicker4, #datepicker5").datepicker(
{
      showOn: "button",
      buttonImage: "../wp-content/plugins/rsvpmaker/datepicker.gif",
      buttonImageOnly: true,
      buttonText: "Select date",
	  dateFormat: "yy-mm-dd", 
    onSelect: function()
    { 
        var count = $(this).attr('id').replace('datepicker','');
		var dateObject = $(this).datepicker('getDate'); 
		$('#day'+count+' option[value="'+ dateObject.getDate() +'"]').attr("selected", "selected");
		$('#year'+count+' option[value="'+ dateObject.getFullYear() +'"]').attr("selected", "selected");
		$('#month'+count+' option[value="'+ (dateObject.getMonth() + 1) +'"]').attr("selected", "selected");
		return false;
    }
});
  } );

$("#multireminder #checkall").click(function(){
    $('#multireminder input:checkbox').not(this).prop('checked', this.checked);
});	

$('.end_time').hide();

$('.end_time_type').each(function() {
	var type = $( this ).val();
	//alert(type);
	if(type == 'set')
		$('.end_time').show();
});

$('.end_time_type').change(function() {
	var type = $( this ).val();
	//alert(type);
	if((type == 'set') || (type.search('ulti') > 0))
		{
			default_end_time();
			$('.end_time').show();
		}
	else
		$('.end_time').hide();
});

function default_end_time() {
	var hour = $('#hour0').val();
	var minutes = $('#minutes0').val();
	var time = new Date(Date.parse('2020-01-01 '+ hour+':'+minutes+':00') + (60*60*1000));
	var endhours = time.getHours();
	if(endhours < 10)
		endhours = '0' + endhours;
	$('#endhour0').val(endhours);
	var endminutes = time.getMinutes();
	if(endminutes < 10)
		endminutes = '0' + endminutes;
	$('#endminutes0').val(endminutes);
	//time.setTime();
}

$('.rsvphour').change(function() {
	var hour = $( this ).val();
	var target = '#end' + $( this ).attr('id');
	var endhour = parseInt(hour) + 1;
	var endhourstring = '';
	if(endhour == 24)
		endhourstring = '00';
	else if(endhour < 10)
		endhourstring = '0'+endhour.toString();
	else
		endhourstring = endhour.toString();
	$(target).val(endhourstring);
});

$('.rsvpminutes').change(function() {
	var minutes = $( this ).val();
	var target = '#end' + $( this ).attr('id');
	$(target).val(minutes);
});

$('.end_time select').change(function() {
	$('.end_time_type').val('set');
});

$('#reset_stripe_production').click(function(event) {
	event.preventDefault();
	$('#stripe_production').html('<p>Publishable Key (Production):<br /><input name="rsvpmaker_stripe_keys[pk]" value=""></p><p>Secret Key (Production):<br /><input name="rsvpmaker_stripe_keys[sk]" value=""></p>');
});

$('#reset_stripe_sandbox').click(function(event) {
	event.preventDefault();
	$('#stripe_sandbox').html('<p>Publishable Key (Sandbox):<br /><input name="rsvpmaker_stripe_keys[sandbox_pk]" value=""></p><p>Secret Key (Sandbox):<br /><input name="rsvpmaker_stripe_keys[sandbox_sk]" value=""></p>');
});

$('#reset_paypal_production').click(function(event) {
	event.preventDefault();
	$('#paypal_production').html('<p>Client ID (Production):<br /><input name="rsvpmaker_paypal_rest_keys[client_id]" value=""></p><p>Client Secret (Production):<br /><input name="rsvpmaker_paypal_rest_keys[client_secret]" value=""></p>');
});
$('#reset_paypal_sandbox').click(function(event) {
	event.preventDefault();
	$('#paypal_sandbox').html('<p>Client ID (Sandbox):<br /><input name="rsvpmaker_paypal_rest_keys[sandbox_client_id]" value=""></p><p>Client Secret (Sandbox):<br /><input name="rsvpmaker_paypal_rest_keys[sandbox_client_secret]" value=""></p>');
});

$( "form#rsvpmaker_setup" ).submit(function( event ) {
	var data = $( this ).serializeArray();
	var url = rsvpmaker_rest.rest_url+'rsvpmaker/v1/setup';
	$( "form#rsvpmaker_setup" ).html('<h1>Creating Draft ...</h1>');
	jQuery.post(url, data, function(editurl) {
		window.location.href = editurl;
		$( "form#rsvpmaker_setup" ).html('<h1>Draft Created: <a href="'+editurl+'">Edit</a></h1><p>Draft should load automatically.</p>');
	});
	event.preventDefault();
});

$( "form#email_templates" ).submit(function( event ) {
	var data = $( this ).serializeArray();
	var url = rsvpmaker_rest.rest_url+'rsvpmaker/v1/email_templates';
	$( "form#email_templates" ).html('<h1>Saving ...</h1>');
	jQuery.post(url, data, function(response) {
		$( "form#email_templates" ).html(response);
	});
	event.preventDefault();
});

$( "form#rsvpmaker_notification_templates" ).submit(function( event ) {
	var data = $( this ).serializeArray();
	var url = rsvpmaker_rest.rest_url+'rsvpmaker/v1/notification_templates';
	$( "form#rsvpmaker_notification_templates" ).html('<h1>Saving ...</h1>');
	jQuery.post(url, data, function(response) {
		document.getElementById("rsvpmaker_notification_templates").scrollIntoView(true);
		$( "form#rsvpmaker_notification_templates" ).html(response);
	});
	event.preventDefault();
});

$( "form#rsvpmaker_details" ).submit(function( event ) {
	var data = $( this ).serializeArray();
	var url = rsvpmaker_rest.rest_url+'rsvpmaker/v1/rsvpmaker_details';
	$( "#rsvpmaker_details_status" ).html('<h1>Saving ...</h1>');
	document.getElementById("headline").scrollIntoView(true);
	jQuery.post(url, data, function(response) {
		$( "#rsvpmaker_details_status" ).html(response);
	});
	event.preventDefault();
});

$('#month0').change( function () {
var datestring = $('#year0').val()+'-'+$('#month0').val()+'-'+$('#day0').val();
var target = new Date(datestring);
var now = new Date();
var nextyear = parseInt($('#year0').val()) +1; 
if(target < now)
	var nextyeartext = nextyear.toString();
	$('#year0').val(nextyeartext);
}
);

$('.quick_event_date').change(
	function () {
		const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
		var datestring = $(this).val();
		var id = $(this).attr('id');
		var post_id = $(this).attr('post_id');
		var target = id.replace('quick_event_date','quick_event_date_text');
		var date = new Date(datestring);
		var localestring = date.toLocaleDateString(undefined, options)+' '+date.toLocaleTimeString();
		$('#'+target).html(localestring);
		//$( '.event_dates', '#post-'+post_id ).text(localestring);
	}
);

$('.quick_end_time').change(
	function () {
		var timestring = $(this).val();
		var id = $(this).attr('id');
		var post_id = $(this).attr('post_id');
		var datestr = $('#quick_event_date-'+post_id).val();
		var dateparts = datestr.split(' ');
		var target = id.replace('quick_end_time','quick_end_time_text');
		console.log('date '+dateparts[0]+' time '+timestring);
		var date = new Date(dateparts[0]+' '+timestring);
		var localestring = date.toLocaleTimeString().replace(':00 ',' ');
		$('#'+target).html(localestring);
		//$( '.event_dates', '#post-'+post_id ).text(localestring);
	}
);

// update quick edit ui based on https://rudrastyh.com/wordpress/quick-edit-tutorial.html
// it is a copy of the inline edit function
var wp_inline_edit_function = inlineEditPost.edit;

// we overwrite the it with our own
inlineEditPost.edit = function( post_id ) {

	// let's merge arguments of the original function
	wp_inline_edit_function.apply( this, arguments );

	// get the post ID from the argument
	var id = 0;
	if ( typeof( post_id ) == 'object' ) { // if it is object, get the ID number
		id = parseInt( this.getId( post_id ) );
	}

	//if post id exists
	if ( id > 0 ) {

		// add rows to variables
		var specific_post_edit_row = $( '#edit-' + id ),
			specific_post_row = $( '#post-' + id ),
			datetext = $( '.event_dates', specific_post_row ).text(); 
		var dt = new Date(datetext);
		var dtstring = dt.getFullYear() + '-' + pad2(dt.getMonth()+1) + '-' + pad2(dt.getDate()) + ' ' + pad2(dt.getHours()) + ':' + pad2(dt.getMinutes()) + ':' + pad2(dt.getSeconds());
		var justdate = dt.getFullYear() + '-' + pad2(dt.getMonth()+1) + '-' + pad2(dt.getDate());

		var endtext = $( '.rsvpmaker_end', specific_post_row ).text();
		if(endtext.search(/\d\d\d\d/) > 0)
			{
				console.log('1 calc date based on '+endtext);
				var end_date_time = new Date(endtext);
			}
		else
			{
				var end_date_text = justdate+' '+endtext;
				console.log('2 calc date based on '+end_date_text);
				var end_date_time = new Date(end_date_text);
			}
		var end_tstring = pad2(end_date_time.getHours()) + ':' + pad2(end_date_time.getMinutes());

		// populate the inputs with column data
		//$( ':input[name="quick_event_date"]', specific_post_edit_row ).val( dtstring );
		$( ':input[name="event_dates"]', specific_post_edit_row ).val( dtstring );
		//$('#quick_event_date_text-'+id).html(datetext);
		$(':input[name="end_time"]', specific_post_edit_row ).val( end_tstring );
		//$('#quick_end_time_text-'+id).html(endtext);
	}
}

function pad2(number) {  
	return (number < 10 ? '0' : '') + number
}

});