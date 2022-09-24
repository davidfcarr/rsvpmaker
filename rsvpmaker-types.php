<?php

function rsvpmaker_create_post_type() {

	global $rsvp_options;

	$menu_label = ( isset( $rsvp_options['menu_label'] ) ) ? $rsvp_options['menu_label'] : __( 'RSVP Events', 'rsvpmaker' );
	$rewrite = array('slug' => 'rsvpmaker','with_front' => false);

	$supports = array( 'title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail', 'revisions' );

	register_post_type(
		'rsvpmaker',
		array(

			'labels'             => array(

				'name'          => $menu_label,

				'add_new_item'  => __( 'Add New RSVP Event', 'rsvpmaker' ),

				'edit_item'     => __( 'Edit RSVP Event', 'rsvpmaker' ),

				'new_item'      => __( 'RSVP Events', 'rsvpmaker' ),

				'singular_name' => __( 'RSVP Event', 'rsvpmaker' ),

			),

			'menu_icon'          => 'dashicons-calendar-alt',

			'public'             => true,

			'can_export'         => false,

			'publicly_queryable' => true,

			'show_ui'            => true,

			'query_var'          => true,

			'rewrite'            => $rewrite,

			'capability_type'    => 'rsvpmaker',

			'map_meta_cap'       => true,

			'has_archive'        => true,

			'hierarchical'       => false,

			'menu_position'      => 15,

			'supports'           => $supports,

			'show_in_rest'       => true,

			'taxonomies'         => array( 'rsvpmaker-type', 'post_tag' ),

		)
	);

	register_post_type(
		'rsvpmaker_template',
		array(

			'labels'             => array(

				'name'          => 'Event Templates',

				'add_new_item'  => __( 'Add New RSVP Template', 'rsvpmaker' ),

				'edit_item'     => __( 'Edit RSVP Template', 'rsvpmaker' ),

				'new_item'      => __( 'RSVP Template', 'rsvpmaker' ),

				'singular_name' => __( 'RSVP Template', 'rsvpmaker' ),

			),

			'menu_icon'          => 'dashicons-calendar-alt',

			'public'             => true,

			'can_export'         => false,

			'publicly_queryable' => true,

			'show_ui'            => true,

            'show_in_menu' => 'edit.php?post_type=rsvpmaker', //make submenu

			'query_var'          => true,

			'rewrite'            => array(
				'slug'       => 'rsvpmaker_template',
				'with_front' => false,
			),

			'capability_type'    => 'rsvpmaker',

			'map_meta_cap'       => true,

			'has_archive'        => true,

			'hierarchical'       => false,

			'menu_position'      => 15,

			'supports'           => $supports,

			'show_in_rest'       => true,

			'taxonomies'         => array( 'rsvpmaker-type', 'post_tag' ),

		)
	);

	// Add new taxonomy, make it hierarchical (like categories)

	$labels = array(

		'name'              => _x( 'Event Types', 'taxonomy general name', 'rsvpmaker' ),

		'singular_name'     => _x( 'Event Type', 'taxonomy singular name', 'rsvpmaker' ),

		'search_items'      => __( 'Search Event Types', 'rsvpmaker' ),

		'all_items'         => __( 'All Event Types', 'rsvpmaker' ),

		'parent_item'       => __( 'Parent Event Type', 'rsvpmaker' ),

		'parent_item_colon' => __( 'Parent Event Type:', 'rsvpmaker' ),

		'edit_item'         => __( 'Edit Event Type', 'rsvpmaker' ),

		'update_item'       => __( 'Update Event Type', 'rsvpmaker' ),

		'add_new_item'      => __( 'Add New Event Type', 'rsvpmaker' ),

		'new_item_name'     => __( 'New Event Type', 'rsvpmaker' ),

		'menu_name'         => __( 'Event Type', 'rsvpmaker' ),

	);

	register_taxonomy(
		'rsvpmaker-type',
		array( 'rsvpmaker','rsvpmaker_template' ),
		array(

			'hierarchical' => true,

			'labels'       => $labels,

			'show_ui'      => true,

			'show_in_rest' => true,

			'query_var'    => true,

		)
	);

	global $rsvp_options;
	if ( isset( $rsvp_options['flush'] ) && $rsvp_options['flush'] ) {
		flush_rewrite_rules();
	}
	// if there is a logged in user, set editing roles
	global $current_user;

	if ( isset( $current_user ) ) {
		rsvpmaker_roles();
	}
}

function create_rsvpemail_post_type() {
    global $rsvp_options;
      register_post_type( 'rsvpemail',
        array(
          'labels' => array(
            'name' => __( 'RSVP Email Newsletters and Notifications','rsvpmaker' ),
            'add_new_item' => __( 'Add New Email','rsvpmaker' ),
            'edit_item' => __( 'Edit Email','rsvpmaker' ),
            'new_item' => __( 'RSVP Emails','rsvpmaker' ),
            'singular_name' => __( 'RSVP Email','rsvpmaker' )
          ),
        'public' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => true,
        'show_ui' => true, 
        'query_var' => true,
        'rewrite' => true,
        'capabilities' => array(
            'edit_post' => 'edit_rsvpemail',
            'edit_posts' => 'edit_rsvpemails',
            'edit_others_posts' => 'edit_others_rsvpemails',
            'publish_posts' => 'publish_rsvpemails',
            'read_post' => 'read_rsvpemail',
            'read_private_posts' => 'read_private_rsvpemails',
            'delete_post' => 'delete_rsvpemail'
        ),
        'hierarchical' => false,
        'menu_position' => 15,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => array('title','editor'),
        'show_in_rest' => true,
        )
      );
}
    