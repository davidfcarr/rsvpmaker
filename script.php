<?php
/*
* Load JS and Css
*/
$scriptversion = '2022.0528.10';

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
	if(is_network_admin())
		return;
	rsvpmaker_event_scripts(); // want the front end scripts, too
	global $post, $scriptversion, $rsvpscript;
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	if ( ( ! function_exists( 'do_blocks' ) && isset( $_GET['action'] ) ) || ( isset( $_GET['post_type'] ) && ( $_GET['post_type'] == 'rsvpmaker' ) ) || ( ( isset( $_GET['page'] ) &&
	( ( strpos( $_GET['page'], 'rsvp' ) !== false ) || ( strpos( $_GET['page'], 'toast' ) !== false ) ) ) ) ) {
		wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'rsvpmaker_jquery_ui', plugin_dir_url( __FILE__ ) . 'jquery-ui.css', array(), '4.1', true );
		wp_enqueue_script( 'rsvpmaker_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery', 'rsvpmaker_js' ), $scriptversion, true );
		wp_enqueue_style( 'rsvpmaker_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css', array(), $scriptversion, true );
	}
}

function rsvpmaker_event_scripts($frontend = true) {
	global $post, $scriptversion,$rsvpmaker_nonce;
	$scriptversion = time();
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

function rsvp_form_jquery() {
	global $rsvp_required_field;
	global $post;
	ob_start();
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
	
	<?php
	$hide = get_post_meta( $post->ID, '_hiddenrsvpfields', true );
	if ( ! empty( $hide ) ) {
		printf( 'var hide = %s;', wp_json_encode( $hide ) );
		echo "\n";
		?>
	
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
	
		<?php
	}
	?>
	var max_guests = $('#max_guests').val();
	var blank = $('#first_blank').html();
	var firstblank_hidden = true;
	if(blank)
		{
		$('#first_blank').html(blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount) );
		$('#first_blank').hide();
		guestcount++;
		}
	$('#add_guests').click(function(event){
		event.preventDefault();
		if(firstblank_hidden) {
			firstblank_hidden = false;
			$('#first_blank').show();
			return;
		}
	if(guestcount >= max_guests)
		{
		$('#first_blank').append('<p><em><?php esc_html_e( 'Guest limit reached', 'rsvpmaker' ); ?></em></p>');
		return;
		}
	var guestline = '<' + 'div class="guest_blank">' +
		blank.replace(/\[\]/g,'['+guestcount+']').replace('###',guestcount) +
		'<' + '/div>';
	guestcount++;
	$('#first_blank').append(guestline);
	
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
				$('#rsvp_email_lookup').html(response);
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
