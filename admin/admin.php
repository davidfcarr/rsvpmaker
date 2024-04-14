<?php
/**
 * Description:       Example block scaffolded with Create Block tool.
 * @package           create-block
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */

 //legacy from old create guten block
include 'init.php';

function create_block_admin_block_init() {
	register_block_type( __DIR__ . '/build' );
	register_block_type( __DIR__ . '/build/upcoming' );
	register_block_type( __DIR__ . '/build/loop_excerpt_block' );
	register_block_type( __DIR__ . '/build/calendar' );
	register_block_type( __DIR__ . '/build/rsvpdateblock' );
	register_block_type( __DIR__ . '/build/rsvpbutton' );
}
add_action( 'init', 'create_block_admin_block_init' );

add_action('admin_enqueue_scripts', 'react_admin_script');

function react_admin_script() {
	global $post;
	if(isset($_GET['page']) && ('rsvpmaker_settings' == $_GET['page'] )) 
	{
		wp_enqueue_script(get_rsvpmaker_admin_script_handle('viewScript'));
		wp_enqueue_style(get_rsvpmaker_admin_script_handle('style'));
		wp_localize_script(get_rsvpmaker_admin_script_handle('viewScript'), 'rsvpmaker_rest',rsvpmaker_rest_array());
	}	
	elseif(isset($_GET['page']) && ('rsvpmaker_details' == $_GET['page'] ) && isset($_GET['post_id'])) 
	{
		wp_enqueue_script(
			'rsvpmaker_details', // Handle.
			plugins_url( 'rsvpmaker/admin/build/event-options.js'), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element','wp-components' ), // Dependencies, defined above.
			time(),
			true // Enqueue the script in the footer.
		);
		wp_enqueue_style(
			'rsvpmaker_details', // Handle.
			plugins_url( 'rsvpmaker/admin/build/style-index.css'), // Block.build.js: We register the block here. Built with Webpack.
			array( ), // Dependencies, defined above.
			time()
		);
		wp_enqueue_style(get_rsvpmaker_admin_script_handle('style'));
		wp_localize_script('rsvpmaker_details', 'rsvpmaker_rest',rsvpmaker_rest_array());
	}	

	elseif( ((isset($_GET['action']) && ('edit' == $_GET['action'] && 'rsvpmaker_template' == $post->post_type )) || (strpos($_SERVER['REQUEST_URI'],'post-new.php') && isset($_GET['post_type']) && 'rsvpmaker_template' == $_GET['post_type'] ) ) )
	{
		wp_enqueue_script(
			'rsvpmaker_meta', // Handle.
			plugins_url( 'rsvpmaker/admin/build/metabox.js'), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element','wp-components' ), // Dependencies, defined above.
			time(),
			true // Enqueue the script in the footer.
		);
		wp_enqueue_style(
			'rsvpmaker_meta', // Handle.
			plugins_url( 'rsvpmaker/admin/build/style-index.css'), // Block.build.js: We register the block here. Built with Webpack.
			array( ), // Dependencies, defined above.
			time()
		);
		wp_enqueue_style(get_rsvpmaker_admin_script_handle('style'));
		wp_localize_script('rsvpmaker_meta', 'rsvpmaker_rest',rsvpmaker_rest_array());
	}	

	elseif(isset($_GET['page']) && ('rsvpmaker_setup' == $_GET['page'] )) 
	{
		wp_enqueue_script(
			'rsvpmaker_setup', // Handle.
			plugins_url( 'rsvpmaker/admin/build/date-time.js'), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element','wp-components' ), // Dependencies, defined above.
			time(),
			true // Enqueue the script in the footer.
		);
		wp_enqueue_style(
			'rsvpmaker_setup', // Handle.
			plugins_url( 'rsvpmaker/admin/build/style-index.css'), // Block.build.js: We register the block here. Built with Webpack.
			array( ), // Dependencies, defined above.
			time()
		);
		wp_enqueue_style(get_rsvpmaker_admin_script_handle('style'));
		wp_localize_script('rsvpmaker_setup', 'rsvpmaker_rest',rsvpmaker_rest_array());
	}	

}

add_action('wp_enqueue_scripts', 'rsvpmaker_frontend_admin');
function rsvpmaker_frontend_admin () {
	global $post;
	if($post && 'rsvpmaker_form' == $post->post_type) 
	{
		wp_enqueue_script(
			'rsvpmaker_single_form', // Handle.
			plugins_url( 'rsvpmaker/admin/build/single-form.js'), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element','wp-components' ), // Dependencies, defined above.
			time(),
			true // Enqueue the script in the footer.
		);
		wp_enqueue_style(
			'rsvpmaker_single_form_style', // Handle.
			plugins_url( 'rsvpmaker/admin/build/style-index.css'), // Block.build.js: We register the block here. Built with Webpack.
			array( ), // Dependencies, defined above.
			time()
		);
		wp_enqueue_style(get_rsvpmaker_admin_script_handle('style'));
		wp_localize_script('rsvpmaker_single_form', 'rsvpmaker_rest',rsvpmaker_rest_array());
	}
}


function get_rsvpmaker_admin_script_handle ($type) {
	return generate_block_asset_handle( 'rsvpmaker/admin', $type);
}
	
function rsvpmaker_react_admin() {
	global $rsvp_options;
	echo '<h1>RSVPMaker Settings</h1><div id="rsvpmaker-admin" form_id="'.intval($rsvp_options['rsvp_form']).'"></div>';
}

function rsvpmaker_template_callback() {
	?>
	<div id="rsvpmaker-template-metabox">
		Loading Create/Update from Template
	</div>
	<?php
}

add_action( 'add_meta_boxes', function() {
	global $post;
	//if('rsvpmaker-template' == $post->post_type)
	add_meta_box(
		'rsvpmaker-create-update',
		'RSVPMaker Create/Update',
		'rsvpmaker_template_callback',
		'rsvpmaker_template',
		'advanced'
	);
} );

function get_rsvpmaker_ajax() {
	global $post, $rsvp_options, $current_user, $rsvpmaker_ajax;
	if(!empty($rsvpmaker_ajax))
		return $rsvpmaker_ajax;
	$post_id = (empty($post->ID)) ? 0 : $post->ID;
	$post_type = (isset($post->post_type)) ? $post->post_type: '';
	if(isset($_GET['post_type']))
		$post_type = $_GET['post_type'];
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
		$complex_pricing = rsvp_complex_price($post_id);
		$complex_template = get_post_meta($post_id,'complex_template',true);
		$chosen_gateway = get_rsvpmaker_payment_gateway ();
		$edit_payment_confirmation = admin_url('?payment_confirmation&post_id='.$post_id);
		$sked = get_template_sked($post_id);
		if(!empty($post->post_content) && strpos($post->post_content,'wp:rsvpmaker/formfield'))
			$rsvpmaker_special = 'RSVP Form';
		else
			$rsvpmaker_special = get_post_meta($post_id,'_rsvpmaker_special',true);
		if(!empty($rsvpmaker_special))
			$top_message = $rsvpmaker_special;
		$top_message = apply_filters('rsvpmaker_ajax_top_message',$top_message);
		$bottom_message = apply_filters('rsvpmaker_ajax_bottom_message',$bottom_message);
		
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
	$confirm_edit_post = (current_user_can('edit_post',$confirm->ID));
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
	$form_edit = admin_url('post.php?action=edit&post='.$fpost->ID.'&back='.$post_id);
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
	$form_edit_post = (current_user_can('edit_post',$fpost->ID));
	$form_edit_post = true;
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
	
	//if(('rsvpmaker' == $post_type) || ('rsvpmaker_template' == $post_type))
	//{
		$related_documents = get_related_documents ();
		//rsvpmaker_debug_log($related_documents,'related documents for gutenberg');
		$rsvpmaker_ajax = array(
			'projected_label' => $projected_label,'projected_url' => $projected_url,
			'template_label' => $template_label,
			'template_url' => $template_url,
			'ajax_nonce'    => wp_create_nonce('ajax_nonce'),
			'eventdata' => get_rsvpmaker_event($post_id),
			'_rsvp_on' => (empty(get_post_meta($post_id,'_rsvp_on',true)) ? 'No' : 'Yes' ),
			'template_msg' => $template_msg,
			'event_id' => $post_id,
			'template_id' => $template_id,
			'special' => $rsvpmaker_special,
			'rsvpmaker_details' => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),
			'top_message' => $top_message,
			'bottom_message' => $bottom_message,
			'confirmation_excerpt' => $excerpt,
			'confirmation_edit' => admin_url('post.php?action=edit&post='.$confirm->ID.'&back='.$post_id),
			'confirmation_customize' => admin_url('?post_id='. $post_id. '&customize_rsvpconfirm='.$confirm->ID.'#confirmation'),
			'reminders' => admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id='.$post_id),
			'confirmation_type' => $confirmation_type,
			'confirm_edit_post' => $confirm_edit_post,
			'rsvp_tx_template_choices' => $confirmation_email_templates,
			'form_id' => $fpost->ID,
			'form_fields' => $form_fields,
			'form_edit' => $form_edit,
			'form_customize' => $form_customize,
			'form_type' => $form_type,
			'form_edit_post' => $form_edit_post,			
			'complex_pricing' => $complex_pricing,		
			'complex_template' => $complex_template,
			'edit_payment_confirmation' => $edit_payment_confirmation,
			'payment_gateway_options' => get_rsvpmaker_payment_options(),
			'payment_gateway' => get_rsvpmaker_payment_gateway(),
			'related_document_links' => $related_documents,
			'form_links' => get_form_links($post_id, $template_id, 'rsvp_options'),
			'confirmation_links' => get_conf_links($post_id, $template_id, 'rsvp_options'));
			
	//}
	//}
	return empty($rsvpmaker_ajax) ? [] : $rsvpmaker_ajax;
}

function rsvpmaker_localize () {
	global $post, $rsvp_options, $current_user;
	$post_type = (isset($post->post_type)) ? $post->post_type: '';

	if(isset($_GET['post_type']))
	$post_type = $_GET['post_type'];
wp_localize_script( 'rsvpmaker-admin-editor-script-2', 'rsvpmaker', array('post_type' => $post_type,'json_url', site_url('/wp-json/rsvpmaker/v1/')) );
if($post_type == 'rsvpemail') {
	wp_localize_script( 'rsvpmaker-admin-editor-script-2', 'related_documents', get_related_documents ($post->ID,'rsvpemail'));
	$template = get_option('rsvpmailer_default_block_template');
	wp_localize_script( 'rsvpmaker-admin-editor-script-2', 'rsvp_email_template', array('default' => $template,'edit_url' => admin_url('post.php?action=edit&post='.$template),'more'=>admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_email_template')));
}

wp_localize_script( 'rsvpmaker_sidebar-js', 'rsvpmaker', array('post_type' => $post_type,'json_url', site_url('/wp-json/rsvpmaker/v1/')) );
wp_localize_script( 'rsvpmaker_sidebar-js', 'rsvpmaker_rest', rsvpmaker_rest_array() );

$rsvpmaker_ajax = get_rsvpmaker_ajax();
wp_localize_script( 'rsvpmaker_sidebar-js', 'rsvpmaker_ajax',$rsvpmaker_ajax);
wp_localize_script( 'rsvpmaker-admin-editor-script-2', 'rsvpmaker_ajax',$rsvpmaker_ajax);

}