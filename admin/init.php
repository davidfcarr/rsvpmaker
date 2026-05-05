<?php
function rsvpmaker_check_string($value) {
	if(is_string($value))
		return $value;
	return '';
}

function rsvpmaker_block_category( $categories, $post ) {
	$post_type = '';
	if ( ! empty( $post->post_type ) ) {
		$post_type = $post->post_type;
	} elseif ( ! empty( $post->post->post_type ) ) {
		$post_type = $post->post->post_type;
	}

	$custom_categories = array();
	if ( 'rsvpmaker_form' === $post_type ) {
		$custom_categories[] = array(
			'slug'  => 'rsvpmaker-form',
			'title' => __( 'RSVPMaker Form Fields', 'rsvpmaker' ),
		);
	}

	$custom_categories[] = array(
		'slug'  => 'rsvpmaker',
		'title' => __( 'RSVPMaker', 'rsvpmaker' ),
	);

	return array_merge( $custom_categories, $categories );
}

function rsvpmaker_limit_form_blocks_to_forms( $allowed_block_types, $editor_context ) {
	$form_only_blocks = array(
		'rsvpmaker/formchimp',
		'rsvpmaker/formfield',
		'rsvpmaker/guests',
		'rsvpmaker/formnote',
		'rsvpmaker/formradio',
		'rsvpmaker/formselect',
		'rsvpmaker/formtextarea',
	);

	$core_form_document_blocks = array(
		'core/paragraph',
		'core/heading',
		'core/image',
		'core/list',
		'core/separator',
		'core/spacer',
		'core/group',
		'core/columns',
		'core/column',
		'core/buttons',
		'core/button',
		'core/row',
	);

	$form_document_allowed_blocks = array_merge( $form_only_blocks, $core_form_document_blocks );

	$email_only_blocks = array(
		'rsvpmaker/emailcontent',
		'rsvpmaker/emailbody',
	);

	$post_type = '';
	if ( ! empty( $editor_context->post ) && ! empty( $editor_context->post->post_type ) ) {
		$post_type = $editor_context->post->post_type;
	} elseif ( ! empty( $editor_context->post_type ) ) {
		$post_type = $editor_context->post_type;
	}

	$blocks_to_hide = array();
	if ( 'rsvpmaker_form' !== $post_type ) {
		$blocks_to_hide = array_merge( $blocks_to_hide, $form_only_blocks );
	}
	if ( 'rsvpemail' !== $post_type ) {
		$blocks_to_hide = array_merge( $blocks_to_hide, $email_only_blocks );
	}

	if ( 'rsvpmaker_form' === $post_type ) {
		$registered = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$registered_keys = array_keys( $registered );
		$available_for_forms = array_values( array_intersect( $form_document_allowed_blocks, $registered_keys ) );

		if ( true === $allowed_block_types ) {
			return $available_for_forms;
		}

		if ( is_array( $allowed_block_types ) ) {
			return array_values( array_intersect( $allowed_block_types, $available_for_forms ) );
		}

		return $available_for_forms;
	}

	if ( empty( $blocks_to_hide ) ) {
		return $allowed_block_types;
	}

	if ( true === $allowed_block_types ) {
		$registered = WP_Block_Type_Registry::get_instance()->get_all_registered();
		return array_values( array_diff( array_keys( $registered ), $blocks_to_hide ) );
	}

	if ( is_array( $allowed_block_types ) ) {
		return array_values( array_diff( $allowed_block_types, $blocks_to_hide ) );
	}

	return $allowed_block_types;
}

add_filter( 'allowed_block_types_all', 'rsvpmaker_limit_form_blocks_to_forms', 20, 2 );

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
	'type'		=> 'string',
	'single'	=> true,
	'default' => wp_timezone_string(),
	'show_in_rest'	=> true,
	'auth_callback' => function() {
	   return current_user_can('edit_posts');
   	}
	);
	register_meta( 'post', '_timezone', $args );

	$args = array(
			 'type'		=> 'boolean',
			 'single'	=> true,
			 'default' => false,
			 'show_in_rest'	=> true,
			 'auth_callback' => function() {
				return current_user_can('edit_posts');
			}
	);
	$rsvpmaker_bool = array('rsvp_on','add_timezone','convert_timezone','calendar_icons','rsvp_end_display','rsvp_rsvpmaker_send_confirmation_email','rsvp_confirmation_after_payment','rsvp_confirmation_after_payment','rsvp_confirmation_include_event','rsvp_count','rsvp_yesno','rsvp_captcha','rsvp_login_required','rsvp_form_show_date','show_rsvpmaker_options');

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

function rsvpmaker_block_cgb_editor_assets() {
	global $post, $rsvp_options, $current_user;
	wp_enqueue_script(
		'rsvpmaker_sidebar-js', // Handle.
		plugins_url( 'rsvpmaker/admin/build/editor-sidebar/sidebars.js' ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
		get_rsvpversion().'1',//filemtime( plugin_dir_path( 'rsvpmaker' ) . 'admin/dist/sidebars.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);
	rsvpmaker_localize();
} // End function rsvpmaker_block_cgb_editor_assets().

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'rsvpmaker_block_cgb_editor_assets' );

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

