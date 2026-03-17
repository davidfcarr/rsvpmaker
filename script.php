<?php
/*
* Load JS and Css
*/

function rsvpmaker_rest_array() {
	global $post, $rsvpmaker_nonce, $rsvp_options;
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	$post_type = isset( $post->post_type ) ? $post->post_type : '';
	$time = '';
	$sked = [];
	if(isset($post->post_type) && 'rsvpmaker' == $post->post_type) {
		$event = get_rsvpmaker_event( $post_id );
		$parts = explode( ' ', $event->date );
		if ( count( $parts ) > 1 ) {
			$time = $parts[1];
		}
	}
	elseif(isset($post->post_type) && 'rsvpmaker_template' == $post->post_type) {
		$sked = get_template_sked($post_id);
		if(!empty($sked['hour']))
			$time = $sked['hour'].':'.$sked['minutes'].':00';
	}
	$email_template = get_option('rsvpmailer_default_block_template');
	$email_template_design = admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template');
	$postmark = get_rsvpmaker_postmark_options();
	$default_incoming_nonce = wp_create_nonce('handle_incoming');
	$domains = [];
    if(is_multisite()) {
       $multisite = get_current_blog_id();
    }
    else {
        $multisite = 0;
    }        

	if(isset($_GET['page']) && 'rsvpmaker_settings' == $_GET['page'] && is_multisite()) {
		$sites = get_sites();
		foreach($sites as $key => $site) {
			$domains[] = $site->domain;
		}
	}
	if(!empty($rsvpmaker_rest))
		return $rsvpmaker_rest;
	$post_id = (empty($post->ID)) ? 0 : $post->ID;
	$post_type = (isset($post->post_type)) ? $post->post_type: '';
	$template_id = 0;
	//if(is_admin() && !empty($post) && (($post_type == 'rsvpmaker') || ($post_type == 'rsvpmaker_template')) ) //&& ( (isset($_GET['action']) && $_GET['action'] == 'edit') || strpos($_SERVER['REQUEST_URI'],'post-new.php') ) )
		//{

		$projected_label = '';
		$projected_url = '';
		$template_label = '';
		$template_url = '';
		$template_msg = '';
		$top_message = '';
		$bottom_message= '';
		$sked = get_template_sked($post_id);
		$top_message = apply_filters('rsvpmaker_rest_top_message',$top_message);
		$bottom_message = apply_filters('rsvpmaker_rest_bottom_message',$bottom_message);

		if($sked)
		{
			$projected_label = __('Create/update events from template','rsvpmaker');
			$projected_url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post_id);
			$template_msg = sked_to_text($sked);
		}
		$template_id = (int) get_post_meta($post_id,'_meet_recur',true);
		if($template_id && !$sked)
		{
		$template_label = __('Edit Template','rsvpmaker');
		$template_url = admin_url('post.php?action=edit&post='.$template_id);
		}

	$post_id = (empty($post_id)) ? 0 : $post_id;
	$date = get_rsvp_date($post_id);
	$datecount = sizeof(get_rsvp_dates($post_id));
	$end = get_post_meta($post_id,'_end'.$date,true);
	if(empty($end))
		$end = rsvpmaker_date('H:i',rsvpmaker_strtotime($date." +1 hour"));
	$duration = '';
	if(empty($date))
	{
	//$date = rsvpmaker_date("Y-m-d H:i:s",rsvpmaker_strtotime('7 pm'));
	$sked = get_template_sked($post_id);//get_post_meta($post_id,'_sked',true);
	if(empty($sked))
		$sked = array();
	}
	else
	{
		$sked = array();
		$duration = get_post_meta($post_id,'_'.$date,true);
		if(!empty($duration))
		{
			$diff = rsvpmaker_strtotime($duration) - rsvpmaker_strtotime($date);
			$duration = rsvpmaker_date('H:i',$diff);
		}
	}

	$confirm = rsvp_get_confirm($post_id,true);
	$excerpt = strip_tags($confirm->post_content);
	$excerpt = (strlen($excerpt) > 100) ? substr($excerpt, 0, 100).' ...' : $excerpt;
	$confirmation_type = '';
	if($confirm->post_parent == 0)
		$confirmation_type =__('Message is default from settings','rsvpmaker');
	elseif($confirm->post_parent != $post_id)
		$confirmation_type = __('Message inherited from template','rsvpmaker');

	$form_id = get_post_meta($post_id,'_rsvp_form',true);
	if(empty($form_id))
		$form_id = (int) $rsvp_options['rsvp_form'];
	$fpost = get_post($form_id);
	if(!$fpost)
	{
		delete_post_meta($post_id,'_rsvp_form');
		$form_id = (int) $rsvp_options['rsvp_form'];
		$form = get_post($form_id);
	}
	$form_customize = admin_url('?post_id='. $post_id. '&customize_form='.$fpost->ID);
	$guest = (strpos($fpost->post_content,'rsvpmaker-guests')) ? 'Yes' : 'No';
	$note = (strpos($fpost->post_content,'name="note"') || strpos($fpost->post_content,'formnote')) ? 'Yes' : 'No';
	preg_match_all('/\[([A-Za-z0_9_]+)/',$fpost->post_content,$matches);
	if(!empty($matches[1]))
	foreach($matches[1] as $match)
		$fields[$match] = $match;
	preg_match_all('/"slug":"([^"]+)/',$fpost->post_content,$matches);
	if(!empty($matches[1]))
	foreach($matches[1] as $match)
		$fields[$match] = $match;	
	$merged_fields = (empty($fields)) ? '' : implode(', ',$fields);
	$form_fields = sprintf('Fields: %s, Guests: %s, Note field: %s',$merged_fields,$guest,$note);
	$form_type = '';
	if($fpost->post_parent == 0)
		$form_type = __('Form is default from settings','rsvpmaker');//printf('<div id="editconfirmation"><a href="%s" target="_blank">Edit</a> (default from Settings)</div><div><a href="%s" target="_blank">Customize</a></div>',$edit,$customize);
	elseif($fpost->post_parent != $post_id)
		$form_type = __('Form inherited from template','rsvpmaker');//printf('<div id="editconfirmation"><a href="%s" target="_blank">Edit</a> (default from Settings)</div><div><a href="%s" target="_blank">Customize</a></div>',$edit,$customize);
	$email_templates_array = get_rsvpmaker_email_template();
	if($email_templates_array)
	foreach($email_templates_array as $index => $template) {
		if($index > 0)
		$confirmation_email_templates[] = array('label' => $template['slug'], 'value' => $index);
	}

	return apply_filters('rsvpmaker_rest_array', array(
		'post_id'  => $post_id,
		'post_type' => $post_type,
		'date'    => (empty($event) || empty($event->date)) ? date('Y-m-d').' '.$time : $event->date,
		'time'    => $time,
		'hour12' => strpos($rsvp_options['time_format'], 'A') !== false,
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'admin_url' => admin_url(),
		'rest_url' => rest_url(),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'rsvpmaker_json_url' => rest_url( 'rsvpmaker/v1/' ),
		'timelord' => $rsvpmaker_nonce['value'],
		'default_email_template' => $email_template,
		'email_design_screen' => $email_template_design,
		'default_incoming_nonce' => $default_incoming_nonce,
		'postmark_mode' => $postmark['postmark_mode'],
		'postmark_root' => isset($postmark['root']) ? $postmark['root'] : false,
		'multisite' => $multisite,
		'domains' => $domains,
		'projected_label' => $projected_label,
		'projected_url' => $projected_url,
		'template_label' => $template_label,
		'template_url' => $template_url,
		'eventdata' => get_rsvpmaker_event($post_id),
		'event_id' => $post_id,
		'top_message' => $top_message,
		'bottom_message' => $bottom_message,
		'confirmation_excerpt' => $excerpt,
		'confirmation_edit' => admin_url('post.php?action=edit&post='.$confirm->ID.'&back='.$post_id),
		'rsvp_tx_template_choices' => $confirmation_email_templates,
		'form_id' => $fpost->ID,
		'form_fields' => $form_fields,
		'form_type' => $form_type,
		'payment_gateway_options' => get_rsvpmaker_payment_options(),
		'payment_gateway' => get_rsvpmaker_payment_gateway(),
		'confirmation_links' => rsvpmaker_get_conf_links($post_id, $template_id, 'rsvp_options')
		));
}

function rsvpmaker_admin_enqueue( $hook ) {
	global $post, $rsvpscript;
	$scriptversion = get_rsvpversion().'.1';

	if(is_network_admin())
		return;
	// rsvpmaker_event_scripts() is hooked to wp_enqueue_scripts for frontend only
	// Styles for block editor are enqueued via rsvpmaker_enqueue_block_editor_assets()
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	if ( ( ! function_exists( 'do_blocks' ) && isset( $_GET['action'] ) ) || ( isset( $_GET['post_type'] ) && (( $_GET['post_type'] == 'rsvpmaker' ) || ( $_GET['post_type'] == 'rsvpmaker_template' )) ) || ( ( isset( $_GET['page'] ) &&
	( ( strpos( $_GET['page'], 'rsvp_report' ) !== false ) || ( strpos( $_GET['page'], 'rsvpmaker-admin.php' ) !== false ) || ( strpos( $_GET['page'], 'toast' ) !== false ) ) ) ) ) {
		wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'rsvpmaker_jquery_ui', plugin_dir_url( __FILE__ ) . 'jquery-ui.css', array(), '4.1', 'all' );
		wp_enqueue_script( 'rsvpmaker_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery', 'rsvpmaker_js' ), $scriptversion, true );
		wp_enqueue_style( 'rsvpmaker_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css', array('wp-components'), $scriptversion, 'all' );
	}
	if(isset($_GET['page']) && 'rsvpmaker_settings' == $_GET['page'] ) {
	wp_enqueue_script( 'rsvpmaker_react_settings', plugin_dir_url( __FILE__ ) . 'admin/build/settings/adminui.js', array(), $scriptversion, true );
	wp_enqueue_style( 'wp-components' );
	wp_enqueue_style( 'wp-block-library' );
	wp_enqueue_style( 'rsvpmaker_react_settings_style', plugin_dir_url( __FILE__ ) . 'admin/build/settings/style-adminui.css', array('wp-components','wp-block-library'), $scriptversion, 'all' );
	}

	wp_localize_script( 'rsvpmaker_react_settings', 'rsvpmaker_rest', rsvpmaker_rest_array() );
	$hastabs = (isset($_GET['page']) && ('rsvpmaker-admin.php' == $_GET['page']));
	$hastabs = apply_filters('rsvpmaker_tab_pages',$hastabs);
	if($hastabs)
		wp_enqueue_script( 'rsvpmaker_tabs', plugin_dir_url( __FILE__ ) . 'tabs.js', array( 'jquery', 'rsvpmaker_js' ), $scriptversion, true );
}

function rsvpmaker_enqueue_block_assets() {
	if ( ! is_admin() ) {
		return;
	}
	$scriptversion = get_rsvpversion();
	global $rsvp_options;
	$myStyleUrl = ( isset( $rsvp_options['custom_css'] ) && $rsvp_options['custom_css'] ) ? $rsvp_options['custom_css'] : plugins_url( 'style.css', __FILE__ );
	wp_enqueue_style( 'rsvp_style', $myStyleUrl, array(), $scriptversion );
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
	return sprintf('<div id="formvars" hide="%s" events_to_add="%d" is_admin="%d" email_lookup="%s" ></div>',$hide, isset($atts['events_to_add']) ? $atts['events_to_add'] : 0, is_admin() ? 1 : 0, rest_url( 'rsvpmaker/v1/email_lookup/' . wp_create_nonce( 'rsvp_email_lookup' ) .'/'.$post->ID));
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
