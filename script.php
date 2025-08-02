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

function rsvp_form_jquery( $atts = array()) {
	/** updated to eliminate the need for inline javascript output */
	global $post;
	$hide = get_post_meta( $post->ID, '_hiddenrsvpfields', true );
	if($hide)
		$hide = json_encode($hide, true);
	return sprintf('<div id="formvars" hide="%s" events_to_add="%d" options="%s" is_admin="%d" email_lookup="%s" ></div>',$hide, isset($atts['events_to_add']) ? $atts['events_to_add'] : 0, isset($atts['options']) ? str_replace("'","\'",$atts['options']) : '', is_admin() ? 1 : 0, rest_url( 'rsvpmaker/v1/email_lookup/' . wp_create_nonce( 'rsvp_email_lookup' ) .'/'.$post->ID));
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
