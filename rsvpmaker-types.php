<?php

function rsvpmaker_create_post_type() {

	global $rsvp_options;

	$menu_label = ( isset( $rsvp_options['menu_label'] ) ) ? $rsvp_options['menu_label'] : __( 'RSVP Events', 'rsvpmaker' );
	$rewrite = array('slug' => 'rsvpmaker','with_front' => false);

	$supports = array( 'title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail', 'revisions','page-attributes','post-formats' );

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

			'can_export'         => true,

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
			'exclude_from_search' => true,

			'public'             => true,
			'can_export'         => true,

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

	register_post_type(
		'rsvpmaker_form',
		array(

			'labels'             => array(

				'name'          => 'Forms',

				'add_new_item'  => __( 'Add New RSVP Form', 'rsvpmaker' ),

				'edit_item'     => __( 'Edit RSVP Form', 'rsvpmaker' ),

				'new_item'      => __( 'RSVP Form', 'rsvpmaker' ),

				'singular_name' => __( 'RSVP Form', 'rsvpmaker' ),

			),

			'menu_icon'          => 'dashicons-clipboard',

			'public'             => true,

			'can_export'         => true,

			'publicly_queryable' => true,

			'show_ui'            => true,

            'show_in_menu' => 'edit.php?post_type=rsvpmaker', //make submenu

			'query_var'          => true,

			'rewrite'            => array(
				'slug'       => 'rsvpmaker_form',
				'with_front' => false,
			),

			'capability_type'    => 'rsvpmaker',

			'map_meta_cap'       => true,

			'has_archive'        => false,

			'hierarchical'       => false,

			'menu_position'      => 16,

			'supports'           => array( 'title', 'editor', 'author', 'custom-fields', 'revisions' ),

			'show_in_rest'       => true,

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

	$model_version = 1;
	if(empty($rsvp_options['model_version']) || $rsvp_options['model_version'] < $model_version) {
		global $wpdb;
		$wpdb->query("update $wpdb->posts set post_type='rsvpmaker_form' WHERE post_type='rsvpmaker' AND post_content LIKE '%wp:rsvpmaker/formfield%' ");
		$rsvp_options['model_version'] = $model_version;
		update_option('RSVPMAKER_Options',$rsvp_options);
	}

}

add_filter('the_content','rsvpmaker_form_single');

function rsvpmaker_form_single($content) {
	global $post, $rsvp_options;
	if(!isset($post->post_type) && ('rsvpmaker_form' != $post->post_type))
		return $content.' failed check '.var_export($_REQUEST,true);

	if(strpos($post->post_content,'wp:rsvpmaker/formfield')) {
		if(current_user_can('edit_post',$post->ID)) {
			return '<p><em>This form is meant for use as part of an event, but you can edit it here.</em></p><div id="rsvpmaker-single-form" form_id="'.$post->ID.'">Loading form editor ...</div>';
		}
		$add_to_top = '<div><h2>Form Preview</h2></div>';
		$content = $add_to_top."\n".$content;
	}
	elseif(isset($_POST['rsvpmultievent']))
	{
		ob_start();
		$currency = $rsvp_options['paypal_currency'];
		$mincount = $_POST['eventcount'];
		$multicount = 0;
		$chosen = [];

		$currency_symbol = '';

		if ( $currency == 'USD' ) {

		$currency_symbol = '$';

		} elseif ( $currency == 'EUR' ) {

		$currency_symbol = '€';
		}

		$blanks = 0;
		$events = [];
		foreach($_POST['rsvpmultievent'] as $post_id) {
			$post_id = intval($post_id);
			if($post_id) {
				if(in_array($post_id, $chosen))
					return '<p>Error: Duplicate events selected</p>';
				$event = get_post($post_id);
				if(!$event || 'rsvpmaker' != $event->post_type)
					return '<p>Error: Invalid event selected</p>';
				$events[] = $event->post_title;	
				$multicount++;
				$chosen[] = $post_id;
			}
		}

		if($multicount < $mincount)
			return sprintf('<p>%s %d</p>',__('You must choose at least ','rsvpmaker'), $mincount);

		$postdata = $_POST;
		$atts['discount_price'] = floatval($_POST['discount_price']);
		$rsvpmulti = 'rsvpmulti'.time();
		$party = 1;
		if($_POST['guest'])
		{
			foreach($_POST['guest']['first'] as $first)
			{
				if(!empty($first))
					$party++;
			}
		}
		/** Check capacity limit */
		$capacity_ok = true;
		$capacity_message = '';
		foreach($chosen as $post_id) {
			$capacity = rsvpmaker_check_availability($post_id);
			if(is_numeric($capacity) && $capacity < $party) {
				$capacity_ok = false;
				$capacity_message .= '<p>'.sprintf(__('Sorry, the event %s is limited to %d additional reservations.','rsvpmaker'),get_the_title($post_id),$capacity).'</p>';
			}
		}
		if($capacity_ok) {
			$events_count = sizeof($events);
			$postdata['multi_event_price'] = $party * $atts['discount_price'];
			$atts['rsvpmulti'] = $rsvpmulti;
			$atts['amount'] = $postdata['multi_event_price'] * $events_count;
			$priceline = $currency_symbol.number_format($atts['discount_price'], 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands']).' '.$currency;
			$atts['description'] = __('Registration for a party of','rsvpmaker').' '.$party.', '.$events_count.' events: '.implode(', ',$events).' @ '.$priceline.' '.__('per person, per event','rsvpmaker');
			$atts['showdescription'] = 'yes';
			echo rsvpmaker_paypay_button_embed($atts);
			$postdata['description'] = $atts['description'];
			$postdata['events_count'] = $events_count;
			set_transient($rsvpmulti, array_merge($postdata,$atts), HOUR_IN_SECONDS);
		}
		else {
			echo $capacity_message;
		}
		return ob_get_clean().$content.'<div style="margin-top:500px;"></div>';
	}

	return $content;
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
		'can_export' => true,
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
