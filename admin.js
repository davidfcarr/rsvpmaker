// JavaScript Document
jQuery(document).ready(function( $ ) {

	$(document).on( 'click', '.rsvpmaker-nav-tab-wrapper a', function() {
		$('.rsvpmaker-nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('section.rsvpmaker').hide();
		$('section.rsvpmaker').eq($(this).index()).show();
		return false;
	});

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

$( document ).on( 'click', '.rsvpmaker-notice .notice-dismiss', function () {
	// Read the "data-notice" information to track which notice
	// is being dismissed and send it via AJAX
	var type = $( this ).closest( '.rsvpmaker-notice' ).data( 'notice' );
	$.ajax( ajaxurl,
	  {
		type: 'POST',
		data: {
		  action: 'rsvpmaker_dismissed_notice_handler',
		  type: type,
		}
	  } );
  } );		

$("#multireminder #checkall").click(function(){
    $('#multireminder input:checkbox').not(this).prop('checked', this.checked);
});	

	
});