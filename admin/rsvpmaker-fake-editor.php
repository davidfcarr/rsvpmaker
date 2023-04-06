<?php
function rsvpmaker_fake_editor($post) {
	$block_editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );

	// Flag that we're loading the block editor.
	$current_screen = get_current_screen();
	$current_screen->is_block_editor( true );
	
	// Default to is-fullscreen-mode to avoid jumps in the UI.
	add_filter(
		'admin_body_class',
		static function( $classes ) {
			return "$classes is-fullscreen-mode";
		}
	);
	
	/*
	 * Emoji replacement is disabled for now, until it plays nicely with React.
	 */
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	
	/*
	 * Block editor implements its own Options menu for toggling Document Panels.
	 */
	add_filter( 'screen_options_show_screen', '__return_false' );
	
	wp_enqueue_script( 'heartbeat' );
	wp_enqueue_script( 'wp-edit-post' );
	
	$rest_path = rest_get_route_for_post( $post );
	
	// Preload common data.
	$preload_paths = array(
		'/wp/v2/types?context=view',
		'/wp/v2/taxonomies?context=view',
		add_query_arg(
			array(
				'context'  => 'edit',
				'per_page' => -1,
			),
			rest_get_route_for_post_type_items( 'wp_block' )
		),
		add_query_arg( 'context', 'edit', $rest_path ),
		sprintf( '/wp/v2/types/%s?context=edit', $post_type ),
		'/wp/v2/users/me',
		array( rest_get_route_for_post_type_items( 'attachment' ), 'OPTIONS' ),
		array( rest_get_route_for_post_type_items( 'page' ), 'OPTIONS' ),
		array( rest_get_route_for_post_type_items( 'wp_block' ), 'OPTIONS' ),
		array( rest_get_route_for_post_type_items( 'wp_template' ), 'OPTIONS' ),
		sprintf( '%s/autosaves?context=edit', $rest_path ),
		'/wp/v2/settings',
		array( '/wp/v2/settings', 'OPTIONS' ),
	);
	
	block_editor_rest_api_preload( $preload_paths, $block_editor_context );	
}
