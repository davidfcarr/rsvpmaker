<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * `wp-blocks`: includes block type registration and related functions.
 *
 * @since 1.0.0
 */

function rsvpmaker_check_string($value) {
	if(is_string($value))
		return $value;
	return '';
}

function rsvpmaker_block_category( $categories, $post ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'rsvpmaker',
				'title' => __( 'RSVPMaker', 'rsvpmaker' ),
			),
		)
	);
}

add_action( 'init', function(){
global $rsvp_options;

add_filter( 'block_categories_all', 'rsvpmaker_block_category', 10, 2);

$args = array(
 		'type'		=> 'string',
		 'single'	=> true,
		 'default' => '',
		 'show_in_rest'	=> true,
		 'sanitize_callback' => 'rsvpmaker_check_string',
		 'auth_callback' => function() {
			return current_user_can('edit_posts');
		}
	);
	$rsvpmaker_strings = array('_rsvp_to','_rsvp_max','_rsvp_show_attendees','_rsvp_instructions','simple_price','simple_price_label','venue','_template_start_hour','_template_start_minutes','_sked_minutes','_sked_stop','_sked_duration','_sked_duration');
	foreach($rsvpmaker_strings as $field) {
		register_meta( 'post', $field, $args );
	}

	$args = array(
			'type'		=> 'string',
			'single'	=> true,
			'default' => get_rsvpmaker_payment_gateway(),
			'show_in_rest'	=> true,
			'sanitize_callback' => 'rsvpmaker_check_string',
			'auth_callback' => function() {
			return current_user_can('edit_posts');
		}
	);
	register_meta( 'post', '_payment_gateway', $args );

	$args = array(
		'type'		=> 'string',
		'single'	=> true,
		'default' => $rsvp_options['paypal_currency'],
		'show_in_rest'	=> true,
		'sanitize_callback' => 'rsvpmaker_check_string',
		'auth_callback' => function() {
		return current_user_can('edit_posts');
	}
);
register_meta( 'post', '_rsvp_currency', $args );

	$args = array(
		'type'		=> 'string',
		'single'	=> true,
		'default' => '12',
		'show_in_rest'	=> true,
		'sanitize_callback' => 'rsvpmaker_check_string',
		'auth_callback' => function() {
		return current_user_can('edit_posts');
	}
	);
	register_meta( 'post', '_sked_hour', $args );
	$args = array(
		'type'		=> 'string',
		'single'	=> true,
		'default' => '13:00',
		'show_in_rest'	=> true,
		'sanitize_callback' => 'rsvpmaker_check_string',
		'auth_callback' => function() {
		return current_user_can('edit_posts');
	}
	);
	register_meta( 'post', '_sked_end', $args );

	$args = array(
 		'type'		=> 'integer',
		 'single'	=> true,
		 'default' => 0,
		 'show_in_rest'	=> true,
		 'auth_callback' => function() {
			return current_user_can('edit_posts');
		}
	);
	register_meta( 'post', 'rsvp_tx_template', $args );
	register_meta( 'post', '_rsvp_start', $args );
	register_meta( 'post', '_rsvp_deadline', $args );
	$args = array(
		'type'		=> 'string',
		'single'	=> true,
		'default' => '0',
		'show_in_rest'	=> true,
		'auth_callback' => function() {
		   return current_user_can('edit_posts');
	   }
   );
	register_meta( 'post', '_rsvp_deadline_daysbefore', $args );
	register_meta( 'post', '_rsvp_deadline_hours', $args );
	register_meta( 'post', '_rsvp_reg_daysbefore', $args );
	register_meta( 'post', '_rsvp_reg_hours', $args );
	
	$args = array(
		'type'		=> 'string',
		'single'	=> true,
		'default' => '1',
		'show_in_rest'	=> true,
		'auth_callback' => function() {
		   return current_user_can('edit_posts');
	   }
   );
   register_meta( 'post', '_rsvp_count_party', $args );

	$args = array(
			 'type'		=> 'boolean',
			 'single'	=> true,
			 'default' => false,
			 'show_in_rest'	=> true,
			 'auth_callback' => function() {
				return current_user_can('edit_posts');
			}
	);
	$rsvpmaker_bool = array('rsvp_on','add_timezone','convert_timezone','calendar_icons','rsvp_end_display','rsvp_rsvpmaker_send_confirmation_email','rsvp_confirmation_after_payment','rsvp_confirmation_after_payment','rsvp_confirmation_include_event','rsvp_count','rsvp_yesno','rsvp_captcha','rsvp_login_required','rsvp_form_show_date');

	foreach($rsvpmaker_bool as $field) {
		$args['default'] = !empty($rsvp_options[$field]);
		register_meta( 'post', '_'.$field, $args );
	}
	$args = array(
		'object_subtype' => 'rsvpmaker',
		'type'		=> 'string',
		'single'	=> true,
		'default' => '',
		'show_in_rest'	=> true,
		'sanitize_callback' => 'rsvpmaker_check_string',
		'auth_callback' => function() {
		   return current_user_can('edit_posts');
	   }
   );
	$date_fields = array('_rsvp_dates','_firsttime','_rsvp_end_date'); 
	foreach($date_fields as $field)
		register_meta( 'post', $field, $args );	
	$args = array(
		'object_subtype' => 'rsvpmaker_template',
 		'type'		=> 'boolean',
		 'single'	=> true,
		 'default' => false,
		 'show_in_rest'	=> true,
		 'auth_callback' => function() {
			return current_user_can('edit_posts');
		}
	);
	$template_fields = array('_sked_Varies','_sked_First','_sked_Second','_sked_Third','_sked_Fourth','_sked_Last','_sked_Every','_sked_Sunday','_sked_Monday','_sked_Tuesday','_sked_Wednesday','_sked_Thursday','_sked_Friday','_sked_Saturday');
	foreach($template_fields as $field)
		register_meta( 'post', $field, $args );	
	register_meta( 'post', 'rsvpautorenew', $args );

},99);

function rsvpjsonlisting ($atts) {
if(empty($atts['url']))
	return;
$url = $atts['url'];
$limit = (empty($atts['limit'])) ? 10: (int) $atts['limit'];
$morelink = (empty($atts['morelink'])) ? '' : $atts['morelink'];
$slug = rand(0,1000000);
ob_start();
?>
	<div id="rsvpjsonwidget-<?php echo esc_attr($slug); ?>">Loading ...</div>
<script>
var jsonwidget<?php echo esc_attr($slug); ?> = new RSVPJsonWidget('rsvpjsonwidget-<?php echo esc_attr($slug); ?>','<?php echo esc_attr($url); ?>',<?php echo esc_attr($limit); ?>,'<?php echo esc_attr($morelink); ?>');
</script>
<?php
return ob_get_clean();
}

add_action('init','rsvpmaker_server_block_render',1);

function rsvpmaker_block_cgb_block_assets() {
	// Styles.
	global $post;
	wp_enqueue_style(
		'rsvpmaker_block-cgb-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array( 'wp-blocks' ), // Dependency to include the CSS after it.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: filemtime — Gets file modification time.
	);
} // End function rsvpmaker_block_cgb_block_assets().

// Hook: Frontend assets.
add_action( 'enqueue_block_assets', 'rsvpmaker_block_cgb_block_assets' );

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * `wp-blocks`: includes block type registration and related functions.
 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
 * `wp-i18n`: To internationalize the block's text.
 *
 * @since 1.0.0
 */
function rsvpmaker_block_cgb_editor_assets() {
	global $post, $rsvp_options, $current_user;
	wp_enqueue_script(
		'rsvpmaker_block-cgb-block-js', // Handle.
		plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);

	wp_enqueue_script(
		'rsvpmaker_sidebar-js', // Handle.
		plugins_url( 'rsvpmaker/admin/build/editor-sidebar/sidebars.js' ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
		time(),//filemtime( plugin_dir_path( 'rsvpmaker' ) . 'admin/dist/sidebars.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);
	$post_type = (isset($post->post_type)) ? $post->post_type: '';
	wp_localize_script( 'rsvpmaker_sidebar-js', 'rsvpmaker', array('post_type' => $post_type,'json_url', site_url('/wp-json/rsvpmaker/v1/')) );
	wp_localize_script( 'rsvpmaker_sidebar-js', 'rsvpmaker_rest', rsvpmaker_rest_array() );

	if(isset($_GET['post_type']))
		$post_type = $_GET['post_type'];
	wp_localize_script( 'rsvpmaker_block-cgb-block-js', 'rsvpmaker', array('post_type' => $post_type,'json_url', site_url('/wp-json/rsvpmaker/v1/')) );
	if($post_type == 'rsvpemail')
		wp_localize_script( 'rsvpmaker_block-cgb-block-js', 'related_documents', get_related_documents ($post->ID,'rsvpemail'));
	$template_id = 0;
	if(is_admin() && (($post_type == 'rsvpmaker') || ($post_type == 'rsvpmaker_template')) ) //&& ( (isset($_GET['action']) && $_GET['action'] == 'edit') || strpos($_SERVER['REQUEST_URI'],'post-new.php') ) )
		{
		
		$projected_label = '';
		$projected_url = '';
		$template_label = '';
		$template_url = '';
		$template_msg = '';
		$top_message = '';
		$bottom_message= '';
		$complex_pricing = rsvp_complex_price($post->ID);
		$complex_template = get_post_meta($post->ID,'complex_template',true);
		$chosen_gateway = get_rsvpmaker_payment_gateway ();
		$edit_payment_confirmation = admin_url('?payment_confirmation&post_id='.$post->ID);
		$sked = get_template_sked($post->ID);
		if(strpos($post->post_content,'wp:rsvpmaker/formfield'))
			$rsvpmaker_special = 'RSVP Form';
		else
			$rsvpmaker_special = get_post_meta($post->ID,'_rsvpmaker_special',true);
		if(!empty($rsvpmaker_special))
			$top_message = $rsvpmaker_special;
		$top_message = apply_filters('rsvpmaker_ajax_top_message',$top_message);
		$bottom_message = apply_filters('rsvpmaker_ajax_bottom_message',$bottom_message);
		
		if($sked)
		{
			$projected_label = __('Create/update events from template','rsvpmaker');
			$projected_url = admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t='.$post->ID);
			$template_msg = sked_to_text($sked);
		}
		$template_id = (int) get_post_meta($post->ID,'_meet_recur',true);
		if($template_id && !$sked)
		{
		$template_label = __('Edit Template','rsvpmaker');
		$template_url = admin_url('post.php?action=edit&post='.$template_id);
		}
		
	$post_id = (empty($post->ID)) ? 0 : $post->ID;
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

	$confirm = rsvp_get_confirm($post->ID,true);
	$confirm_edit_post = (current_user_can('edit_post',$confirm->ID));
	$excerpt = strip_tags($confirm->post_content);
	$excerpt = (strlen($excerpt) > 100) ? substr($excerpt, 0, 100).' ...' : $excerpt;
	$confirmation_type = '';
	if($confirm->post_parent == 0)
		$confirmation_type =__('Message is default from settings','rsvpmaker');
	elseif($confirm->post_parent != $post->ID)
		$confirmation_type = __('Message inherited from template','rsvpmaker');

	$form_id = get_post_meta($post->ID,'_rsvp_form',true);
	if(empty($form_id))
		$form_id = (int) $rsvp_options['rsvp_form'];
	$fpost = get_post($form_id);
	//rsvpmaker_debug_log($form_id);
	$form_edit = admin_url('post.php?action=edit&post='.$fpost->ID.'&back='.$post->ID);
	$form_customize = admin_url('?post_id='. $post->ID. '&customize_form='.$fpost->ID);
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
	elseif($fpost->post_parent != $post->ID)
		$form_type = __('Form inherited from template','rsvpmaker');//printf('<div id="editconfirmation"><a href="%s" target="_blank">Edit</a> (default from Settings)</div><div><a href="%s" target="_blank">Customize</a></div>',$edit,$customize);
	$email_templates_array = get_rsvpmaker_email_template();
	if($email_templates_array)
	foreach($email_templates_array as $index => $template) {
		if($index > 0)
		$confirmation_email_templates[] = array('label' => $template['slug'], 'value' => $index);
	}

	if(('rsvpmaker' == $post_type) || ('rsvpmaker_template' == $post_type))
	{
		$related_documents = get_related_documents ();
		//rsvpmaker_debug_log($related_documents,'related documents for gutenberg');
		$args = array(
			'projected_label' => $projected_label,'projected_url' => $projected_url,
			'template_label' => $template_label,
			'template_url' => $template_url,
			'ajax_nonce'    => wp_create_nonce('ajax_nonce'),
			'eventdata' => get_rsvpmaker_event($post->ID),
			'_rsvp_on' => (empty(get_post_meta($post->ID,'_rsvp_on',true)) ? 'No' : 'Yes' ),
			'template_msg' => $template_msg,
			'event_id' => $post_id,
			'template_id' => $template_id,
			'special' => $rsvpmaker_special,
			'rsvpmaker_details' => admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_details&post_id='.$post_id),
			'top_message' => $top_message,
			'bottom_message' => $bottom_message,
			'confirmation_excerpt' => $excerpt,
			'confirmation_edit' => admin_url('post.php?action=edit&post='.$confirm->ID.'&back='.$post->ID),
			'confirmation_customize' => admin_url('?post_id='. $post->ID. '&customize_rsvpconfirm='.$confirm->ID.'#confirmation'),
			'reminders' => admin_url('edit.php?post_type=rsvpmaker&page=rsvp_reminders&message_type=confirmation&post_id='.$post->ID),
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

		wp_localize_script( 'rsvpmaker_block-cgb-block-js', 'rsvpmaker_ajax',$args);
	}
				
		}
	
	// Styles.
	wp_enqueue_style(
		'rsvpmaker_block-cgb-block-editor-css', // Handle.
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' )
	);
	if(($post->post_type == 'rsvpmaker') || ($post->post_type == 'rsvpmaker_template')  || ($post->post_type == 'rsvpemail') )
	wp_enqueue_style(
		'rsvpmaker_fullscreen', // Handle.
		plugins_url( 'src/block/fullscreen.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ), '1.0' );

} // End function rsvpmaker_block_cgb_editor_assets().

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'rsvpmaker_block_cgb_editor_assets' );

//add_action( 'enqueue_block_editor_assets', 'rsvpmaker_block_hide_assets', 99 );

//if this is an rsvpmaker post, hide the rsvpmaker/upcoming and rsvpmaker/event blocks (no events within events)

function rsvpmaker_block_hide_assets () {
global $post;
if(empty($post->post_type))
	return;
if($post->post_type != 'rsvpmaker')
	return;
	wp_enqueue_script(
		'rsvpmaker-blacklist-blocks',
		plugins_url( 'dist/hide.js', dirname(__FILE__) ),
		array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'rsvpmaker_block-cgb-block-js' )
	);
}

function rsvpmaker_limited_time ($atts, $content) {
	global $post;
	$debug = '';
	if(isset($_GET['debug']))
		$debug .= ' attributes: '. var_export($atts, true);
	if(empty($atts['start_on']) && empty($atts['end_on']))
		return $content.$debug; // no parameters set
	
	$now = time();
	if(!empty($atts['start_on']) && !empty($atts['start']))
	{
	//test to see if we're before the start time
	$start = rsvpmaker_strtotime($atts['start']);
	if(isset($_GET['debug']))
		$debug .= sprintf('<p>Start time %s = %s, now = %s</p>',$atts['start'],$start,$now);
	if($now < $start)
		{
		
		return $debug;
		}
	}
	if(!empty($atts['end_on']) && !empty($atts['end']))
	{
	//test to see if we're past the end time
	$end = rsvpmaker_strtotime($atts['end']);
	$pattern = '/<!-- wp:rsvpmaker\/limited.+"end":"'.$atts["end"].'".+(\/wp:rsvpmaker\/limited -->)/sU';
	if(isset($_GET['debug']))
	{
		$debug .= sprintf('<p>End time %s = %s, now = %s</p>',$atts['end'],$end,$now);
		preg_match($pattern,$post->post_content,$matches);
		if(empty($matches[0]))
			$debug .= 'Regex failed';
		else
			$debug .= htmlentities($matches[0]);
	}
	if($now > $end)
	{
		if(!empty($atts['delete_expired']))
		{
		$update['ID'] = $post->ID;
		$update['post_content'] = preg_replace($pattern,'',$post->post_content);
		if(!empty($update['post_content']))
			wp_update_post($update);
		else
			$debug .= 'Preg replace came back empty';
		}
		
		return $debug;
	}
		
	}

return $content.$debug;
}

function add_rsvpmaker_block_category( $block_categories, $editor_context ) {
    if ( ! empty( $editor_context->post ) ) {
        array_push(
            $block_categories,
            array(
                'slug'  => 'rsvpmaker',
                'title' => __( 'RSVPMaker', 'rsvpmaker' ),
                'icon'  => null,
            )
        );
    }
    return $block_categories;
}
 
add_filter( 'block_categories_all', 'add_rsvpmaker_block_category', 10, 2 );
