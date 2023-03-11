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
}

function get_rsvpmaker_admin_script_handle ($type) {
	return generate_block_asset_handle( 'rsvpmaker/admin', $type);
}
	
function rsvpmaker_react_admin() {
	echo '<h1>RSVPMaker Settings</h1><div id="rsvpmaker-admin"></div>';
}