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
function create_block_admin_block_init() {
	register_block_type( __DIR__ . '/build' );
	register_block_type( __DIR__ . '/build/youtube-email' );
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