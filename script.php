<?php
/*
* Load JS and Css
*/

function rsvpmaker_rest_array() {
	global $post, $rsvpmaker_nonce;
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	return array(
		'post_id'  => $post_id,
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'rest_url' => rest_url(),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'rsvpmaker_json_url' => site_url( '/wp-json/rsvpmaker/v1/' ),
		'timelord' => $rsvpmaker_nonce['value'],
		);
}

function rsvpmaker_admin_enqueue( $hook ) {
	global $post, $rsvpscript;
	$scriptversion = get_rsvpversion();

	$rsvpmailer_editor_stylesheet = get_option('rsvpmailer_editor_stylesheet');
	//rsvpmaker_debug_log($rsvpmailer_editor_stylesheet,'$rsvpmailer_editor_stylesheet');
	if($rsvpmailer_editor_stylesheet)
		wp_enqueue_style( 'rsvpmaker_editor_style', $rsvpmailer_editor_stylesheet, array(), time(), true );
	if(is_network_admin())
		return;
	rsvpmaker_event_scripts(); // want the front end scripts, too
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	if ( ( ! function_exists( 'do_blocks' ) && isset( $_GET['action'] ) ) || ( isset( $_GET['post_type'] ) && (( $_GET['post_type'] == 'rsvpmaker' ) || ( $_GET['post_type'] == 'rsvpmaker_template' )) ) || ( ( isset( $_GET['page'] ) &&
	( ( strpos( $_GET['page'], 'rsvp_report' ) !== false ) || ( strpos( $_GET['page'], 'rsvpmaker-admin.php' ) !== false ) || ( strpos( $_GET['page'], 'toast' ) !== false ) ) ) ) ) {
		wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'rsvpmaker_jquery_ui', plugin_dir_url( __FILE__ ) . 'jquery-ui.css', array(), '4.1', true );
		wp_enqueue_script( 'rsvpmaker_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery', 'rsvpmaker_js' ), $scriptversion, true );
		wp_enqueue_style( 'rsvpmaker_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css', array(), $scriptversion, true );
	}
	$hastabs = (isset($_GET['page']) && ('rsvpmaker-admin.php' == $_GET['page']));
	$hastabs = apply_filters('rsvpmaker_tab_pages',$hastabs);
	if($hastabs)
		wp_enqueue_script( 'rsvpmaker_tabs', plugin_dir_url( __FILE__ ) . 'tabs.js', array( 'jquery', 'rsvpmaker_js' ), $scriptversion, true );
}

function rsvpmaker_event_scripts($frontend = true) {
	$scriptversion = get_rsvpversion();
	global $post, $rsvpmaker_nonce;
	$post_id       = isset( $post->ID ) ? $post->ID : 0;
	global $rsvp_options;
	wp_enqueue_script( 'jquery' );
	$myStyleUrl = ( isset( $rsvp_options['custom_css'] ) && $rsvp_options['custom_css'] ) ? $rsvp_options['custom_css'] : plugins_url( 'style.css', __FILE__ );
	wp_register_style( 'rsvp_style', $myStyleUrl, array(), $scriptversion );
	wp_enqueue_style( 'rsvp_style' );
	wp_enqueue_script( 'rsvpmaker_js', plugins_url( 'rsvpmaker.min.js', __FILE__ ), array(), $scriptversion, true );
	wp_localize_script( 'rsvpmaker_js', 'rsvpmaker_rest', rsvpmaker_rest_array() );
	wp_enqueue_script( 'wp-tinymce' );
	wp_enqueue_script( 'rsvpmaker_timezone', plugins_url( 'jstz.min.js', __FILE__ ), array(), $scriptversion, true );
} // end event scripts

function rsvpmaker_jquery_inline( $routine, $atts = array() ) {
	global $post, $current_user, $wpdb;
	?>
<script>
jQuery(document).ready(function($) {
$.ajaxSetup({
	headers: {
		'X-WP-Nonce': '<?php echo esc_attr(wp_create_nonce( 'wp_rest' )); ?>',
	}
});
	<?php
	if ( $routine == 'import' ) {
		?>
var totalImported = 0;
function importRSVP(url, data) {
	$.post(url, data, function(response) {
	console.log(response);
	if(response.error) {
		$('#import-result').html(response.error);
		$('#import-result').css({borderColor: 'red'});
	}
	else
	{
		$('#import-result').css({borderColor: 'green'});
		$('#importform').hide();
		if(response.imported && response.top) {
			$('#import-result').html('Imported '+response.imported+' events, ending with #'+response.top+', fetching more');
			data.start = response.top;
			totalImported += parseInt(response.imported);
			importRSVP(url, data);
		} else {
			totalImported += parseInt(response.imported);
			$('#import-result').html('Total imported '+totalImported+', done');
		}
	} 

	});
} 

$('#import-button').click(function(e) {
e.preventDefault();
var remoteurl = $('#importrsvp').val();
$('#importrsvp').val('');//clear the field
$('#import-result').css({padding: '10px',borderWidth: 'thick',borderStyle: 'solid',borderColor: 'gray'});
$('#import-result').html('Trying '+remoteurl+' please wait ...');

var data = {
	'importrsvp': remoteurl,
	'start': 0,
};
var importnowurl = $('#importnowurl').val();
importRSVP(importnowurl,data);
});
		<?php
	}//end import
	?>
});

</script>
	<?php
}

function rsvp_form_jquery( $atts = null) {
	global $rsvp_required_field;
	global $post;
	ob_start();
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {

	<?php
	$hide = get_post_meta( $post->ID, '_hiddenrsvpfields', true );
	printf( "var hide = '%s';\n", empty($hide) ? '' : wp_json_encode( $hide ) );
	if(is_array($atts) && $atts['events_to_add']) {
		printf("var events_to_add=%d;\n",$atts['events_to_add']);
		printf("var options='%s';\n",str_replace("'","\'",$atts['options']));
		?>
		$('#rsvp_more_events_click').click(
			() => {
				if(events_to_add > 0) {
				$('#more_rsvp_events').append('<p><select name="rsvpmultievent[]">'+options+'</select></p>');
				}
				else if(0 == events_to_add)
				$('#more_rsvp_events').append('<p>Reached limit</p>');
				events_to_add--;
			}
		);
		<?php
	}
	?>
	$('#coupon_field').hide();
	$('#coupon_field_add').click(() => {$('#coupon_field').show(); $('#coupon_field_prompt').hide(); });

	$('#guest_count_pricing select').change(function() {
	  //reset hidden fields
	  $('#rsvpform input').prop( "disabled", false );
	  $('#rsvpform select').prop( "disabled", false );
	  $('#rsvpform div').show();
	  $('#rsvpform p').show();
	  var pricechoice = $(this).val();
	  //alert( "Price choice" + hide[pricechoice] );
	  var hideit = hide[pricechoice];
	  $.each(hideit, function( index, value ) {
	  //alert( index + ": " + value );
	  $('div.'+value).hide();
	  $('p.'+value).hide();
	  $('.'+value).prop( "disabled", true );
	});

	});
	var max_guests = $('#max_guests').val();
	console.log('max guests ',max_guests);
	var last;
	var blank = $('#first_blank').html();
	console.log('initial blank',blank);
	var firstblank_hidden = true;
	let number_to_add = 0;
	let guestline = '';
	$('#add_guests').click(function(event){
		event.preventDefault();
		number_to_add = parseInt($('#number_to_add').val());
		console.log('number_to_add',number_to_add);
		last = $('#last').val();
		console.log('number to add',number_to_add);
		console.log('guestcount',guestcount);
		if(firstblank_hidden) {
			firstblank_hidden = false;
			let firstblank = blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount);
			let defaultlast = (last != '') ? last : 'TBD';
			firstblank = firstblank.replace(/\[first\][^\>]+value="/,'$&Guest '+guestcount).replace(/\[last\][^\>]+value="/,'$&'+defaultlast);
			$('#first_blank').html(firstblank);
			$('#first_blank').show();
			guestcount++;
			number_to_add--;
			if(!number_to_add)
				return;
		}
	for(let i = 0; i < number_to_add; i++) {
	<?php if(!is_admin()) {
		//do not enforce for admin working in RSVP Report
	?>
		if(guestcount > max_guests)
		{
		console.log('guest limit reached');
		console.log('guest count',guestcount);
		console.log('max_guests',max_guests);
		$('#first_blank').append('<p><em><?php esc_html_e( 'Guest limit reached', 'rsvpmaker' ); ?></em></p>');
		return;
		}
	<?php } ?>
	console.log('guestline loop',i);
	console.log('guestline number to add',number_to_add);
	console.log('guestline guestcount',guestcount);
	guestline = '<div class="guest_blank">' +
		blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount).replace(/\[first\][^\>]+value="/,'$&Guest '+guestcount).replace(/\[last\][^\>]+value="/,'$&'+last) +
		'</div>';
	guestcount++;
	$('#first_blank').append(guestline);
	}

	if(hide)
	{
	  var pricechoice = $("#guest_count_pricing select").val();
	  var hideit = hide[pricechoice];
	  $.each(hideit, function( index, value ) {
	  //alert( index + ": " + value );
	  $('div.'+value).hide();
	  $('p.'+value).hide();
	  $('.'+value).prop( "disabled", true );
	});
	}

	});

		jQuery("#rsvpform").submit(function() {
		var leftblank = '';
		var required = jQuery("#required").val();
		var required_fields = required.split(',');
		$.each(required_fields, function( index, value ) {
			if(value == 'privacy_consent')
				{
				if(!jQuery('#privacy_consent:checked').val())
				leftblank = leftblank + '<' + 'div class="rsvp_missing">privacy policy consent checkbox<' +'/div>';				
				}
			else if(jQuery("#"+value).val() === '') leftblank = leftblank + '<' + 'div class="rsvp_missing">'+value+'<' +'/div>';
		});
		if(leftblank != '')
			{
			jQuery("#jqerror").html('<' +'div class="rsvp_validation_error">' + "Required fields left blank:\n" + leftblank + ''+'<' +'/div>');
			return false;
			}
		else
			return true;
	});

	//search for previous rsvps
	var searchRequest = null;

	$(function () {
		var minlength = 3;

		$("#email").keyup(function () {
			var that = this;
			value = $(this).val();
			var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
			var post_id = $('#event').val();
			if ((value.length >= minlength ) && (value.match(mailformat)) ) {
				if (searchRequest != null) 
					searchRequest.abort();
				var data = {
					'email_search': value,
				};
				jQuery.get('<?php echo rest_url( 'rsvpmaker/v1/email_lookup/' . wp_create_nonce( 'rsvp_email_lookup' ) ); ?>/'+post_id, data, function(response) {
				$('#rsvp_email_lookup').html('<div style="border: medium solid red; padding: 5px; background-color:#fff; color: red;">'+response+'</div>');
				});
			}
		});
	});	

	});
	</script>
	<?php
	return ob_get_clean();
}

function rsvpmaker_timezone_footer() {
	if ( isset( $_GET['tz'] ) ) {
		$id = (int) $_GET['tz'];
		?>
<script>
jQuery(document).ready(function($) {
	$('#timezone_on<?php echo esc_attr($id); ?>').click();
});
</script>
		<?php
	}
}

?>
