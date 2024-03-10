<?php
if ( ! wp_is_json_request() ) {

	add_shortcode( 'rsvpautorenew_test', 'rsvpautorenew_test' );

	add_shortcode( 'rsvpmaker_embed_form', 'rsvpmaker_form' );

	add_shortcode( 'rsvpmaker_form', 'rsvpmaker_form' );

	add_shortcode( 'event_listing', 'rsvpmaker_event_listing' );

	add_shortcode( 'rsvpmaker_upcoming', 'rsvpmaker_upcoming' );

	add_shortcode( 'rsvpmaker_calendar', 'rsvpmaker_calendar' );

	add_shortcode( 'rsvpmaker_timed', 'rsvpmaker_timed' );

	add_shortcode( 'rsvpmaker_looking_ahead', 'rsvpmaker_looking_ahead' );

	add_shortcode( 'ylchat', 'ylchat' );

	add_shortcode( 'rsvpmaker_next', 'rsvpmaker_next' );

	add_shortcode( 'rsvpmaker_one', 'rsvpmaker_one' );

	add_shortcode( 'rsvpdateblock', 'rsvpdateblock' );

	add_shortcode( 'rsvpmaker_daily_schedule', 'rsvpmaker_daily_schedule' );

	add_shortcode( 'rsvpmaker_email_content', 'rsvpmaker_email_content' );

	add_shortcode( 'rsvpmaker_upcoming_email', 'rsvpmaker_upcoming_email' );

	add_shortcode( 'rsvpmaker_recent_blog_posts', 'rsvpmaker_recent_blog_posts' );

	add_shortcode( 'rsvpcount', 'rsvpcount' );

	add_shortcode( 'embed_dateblock', 'embed_dateblock' );

	add_shortcode( 'rsvp_report_shortcode', 'rsvp_report_shortcode' );

	add_shortcode( 'rsvpguests', 'rsvpguests' );

	add_shortcode( 'rsvpprofiletable', 'rsvpprofiletable' );

	add_shortcode( 'rsvpnote', 'rsvpnote' );

	add_shortcode( 'rsvpfield', 'rsvpfield' );

	add_shortcode( 'rsvpmaker_formchimp', 'rsvpmaker_formchimp' );// add me to your email list checkbox for form

	add_shortcode( 'rsvpmaker_stripe_checkout', 'rsvpmaker_stripe_checkout' );

	add_shortcode( 'RSVPMaker_chimpshort', 'RSVPMaker_chimpshort' );

	// primarily used in email confirmation messages etc.

	add_shortcode( 'rsvptitle', 'rsvptitle_shortcode' );

	add_shortcode( 'rsvpdate', 'rsvpdate_shortcode' );

	add_shortcode( 'datetime', 'rsvpdatetime_shortcode' );

	add_shortcode( 'event_title_link', 'event_title_link' );

}



// event_content defined in rsvpmaker-pluggable.php to allow for variations

add_filter( 'the_content', 'rsvpmaker_event_content_anchor', 50 );

function rsvpmaker_event_content_anchor( $content ) {

	global $post;

	if ( ! is_single() || (( $post->post_type != 'rsvpmaker' ) && ( $post->post_type != 'rsvpmaker_template' )) ) {

		return $content;
	}

	return '<div id="rsvpmaker_top"></div>' . $content;

}

add_filter( 'the_content', 'event_content', 5 );

function event_js( $content ) {

	global $post;

	if ( is_email_context() ) {

		return $content;
	}

	if ( ! is_single() ) {

		return $content;
	}

	if ( ! strpos( $content, 'id="rsvpform"' ) ) {

		return $content;
	}

	if ( $post->post_type != 'rsvpmaker' ) {

		return $content;
	}

	return $content . rsvp_form_jquery();

}



add_filter( 'the_content', 'event_js', 15 );



function rsvp_url_date_query( $direction = '' ) {

	$date = '';

	if ( ! isset( $_GET['cy'] ) || ! isset( $_GET['cm'] ) ) {

		return '';
	}

	$date .= (int) $_GET['cy'];

	$cm = (int) $_GET['cm'];

	$date .= ( $cm < 10 ) ? '-0' . $cm : '-' . $cm;

	if ( isset( $_GET['cd'] ) ) {

		$cd = (int) $_GET['cd'];

		$date .= ( $cd < 10 ) ? '-0' . $cd : '-' . $cd;

	} elseif ( $direction == 'past' ) {

		$date .= '-31';

	} else {
		$date .= '-01';
	}

	return $date;

}



function rsvpmaker_event_listing( $atts = array() ) {

	global $rsvp_options;

	$events = rsvpmaker_upcoming_data( $atts );

	$date_format = ( isset( $atts['date_format'] ) ) ? $atts['date_format'] : $rsvp_options['long_date'];

	if ( ! empty( $atts['time'] ) ) {

		$date_format .= ' ' . $rsvp_options['time_format'];
	}

	$listings = '';

	if ( is_array( $events ) ) {

		foreach ( $events as $event ) {

			$t = ( $event->ts_start ) ? (int) $event->ts_start : rsvpmaker_strtotime( $event->datetime );

			$dateline = rsvpmaker_date( $date_format, $t ); // rsvpmaker_long_date($event->ID, isset($atts['time']), false);

			$listings .= sprintf( '<li><a href="%s">%s</a> %s</li>' . "\n", esc_url_raw( get_permalink( $event->ID ) ), esc_html( strip_tags($event->post_title)  ), $dateline );

			$test = var_export( $event, true );

		}
	}

	if ( ! empty( $atts['limit'] ) && ! empty( $rsvp_options['eventpage'] ) ) {

		$listings .= '<li><a href="' . esc_url( $rsvp_options['eventpage'] ) . '">' . __( 'Go to Events Page', 'rsvpmaker' ) . '</a></li>';
	}

	if ( ! empty( $atts['title'] ) ) {

		$listings = '<p><strong>' . esc_html( $atts['title'] ) . "</strong></p>\n<ul id=\"eventheadlines\">\n$listings</ul>\n";

	} else {
		$listings = "<ul id=\"eventheadlines\">\n$listings</ul>\n";
	}

	if ( isset( $_GET['debug'] ) ) {

		$listings .= '<pre>' . var_export( $events, true ) . '</pre>';
	}

	return $listings;

}



function get_next_events_link( $label = '', $no_events = '' ) {

	global $last_time;

	global $wpdb;

	$sql = "SELECT post_id from $wpdb->postmeta JOIN $wpdb->posts ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE meta_key='_rsvp_dates' AND meta_value > '" . rsvpmaker_date( 'Y-m-d H:i:s', $last_time ) . "' AND post_status='publish' ";

	$at_least_one = $wpdb->get_var( $sql );

	if ( ! $at_least_one ) {

		if ( ! empty( $no_events ) ) {

			return '<p class="no_events">' . $no_events . '</p>';
		}
	}

	$link = get_rsvpmaker_archive_link();

	$link .= ( strpos( $link, '?' ) ) ? '&' : '?';

	$link .= 'cd=' . rsvpmaker_date( 'd', $last_time ) . '&cm=' . date( 'm', $last_time ) . '&cy=' . date( 'Y', $last_time );

		$attr = apply_filters( 'next_posts_link_attributes', '' );

		$link = '<a href="' . $link . "\" $attr>" . esc_html( $label ) . ' &raquo;</a>';

	if ( isset( $link ) ) {

		return "<p class=\"more_events\">$link</p>";
	}

}



function rsvpmaker_select( $select ) {

	global $wpdb;

	$select .= ", ID as postID, rsvpdates.date as datetime, date_format(rsvpdates.date,'%M %e, %Y') as date, rsvpdates.enddate, rsvpdates.display_type, rsvpdates.ts_start, rsvpdates.ts_end, rsvpdates.timezone";

	return $select;

}

function rsvpmaker_join( $join ) {

	global $wpdb;

	if ( strpos( $join, 'rsvpdates' ) ) {

		return $join; // don't add twice
	}

	return $join . ' JOIN ' . $wpdb->prefix . "rsvpmaker_event rsvpdates ON rsvpdates.event = $wpdb->posts.ID ";

}



function rsvpmaker_groupby( $groupby ) {

	global $wpdb;

	return " $wpdb->posts.ID ";

}



function rsvpmaker_distinct( $distinct ) {

	return 'DISTINCT';

}



function rsvpmaker_where_schedule( $where ) {

	$where .= " AND rsvpdates.date > CURDATE( ) AND post_content NOT LIKE '%rsvpmaker/schedule%' ";

	return $where;

}



function rsvpmaker_where( $where ) {

	global $startday;
	global $datelimit;

	if ( isset( $_REQUEST['cm'] ) ) {

		$date = rsvp_url_date_query();

		$where .= " AND ( ( rsvpdates.date >= '" . $date . "' ) OR ( rsvpdates.enddate >= '" . $date . "' ))";

		if ( ! empty( $datelimit ) ) {

			$where .= "AND rsvpdates.date < DATE_ADD('" . $date . "',INTERVAL $datelimit) ";
		}

		return $where;

	} elseif ( isset( $startday ) && $startday ) {

		$t = rsvpmaker_strtotime( $startday );

		$d = rsvpmaker_date( 'Y-m-d', $t );

		 $where .= " AND rsvpdates.enddate > '$d' ";

		if ( ! empty( $datelimit ) ) {

			$where .= " AND ( (rsvpdates.date < DATE_ADD('$d',INTERVAL $datelimit) OR (rsvpdates.enddate < DATE_ADD('$d',INTERVAL $datelimit) ) ";
		}

		 return $where;

	} elseif ( isset( $_GET['startdate'] ) ) {

		$d = sanitize_text_field( $_GET['startdate'] );

		$where .= " AND ( (rsvpdates.date > '$d') OR (rsvpdates.enddate > '$d') ) ";

		if ( ! empty( $datelimit ) ) {

			$where .= " AND ( ( rsvpdates.date < DATE_ADD('$d',INTERVAL $datelimit) OR ( rsvpdates.enddate < DATE_ADD('$d',INTERVAL $datelimit)) ) ";
		}

		return $where;

	} else {
		$curdate = rsvpmaker_date('Y-m-d');

		$where .= " AND ( ( rsvpdates.date > '$curdate' OR rsvpdates.enddate > '$curdate' ) )";

		if ( ! empty( $datelimit ) ) {

			$where .= " AND ( ( rsvpdates.date < DATE_ADD('$curdate',INTERVAL $datelimit)) OR ( rsvpdates.enddate < DATE_ADD('$curdate',INTERVAL $datelimit)) ) ";
		}
		return $where;

	}

}

function rsvpmaker_where_afternow( $where ) {

	global $offset_hours;

	$startfrom = ( ! empty( $offset_hours ) ) ? ' DATE_SUB("' . get_sql_now() . '", INTERVAL ' . $offset_hours . ' HOUR) ' : '"' . get_sql_now() . '"';

	$where .= " AND ( ( rsvpdates.date > $startfrom OR rsvpdates.enddate > $startfrom ) )";

	return $where;

}



function rsvpmaker_orderby( $orderby ) {

	return ' rsvpdates.date';

}



// if listing past dates

function rsvpmaker_where_past( $where ) {
	global $startday;

	if ( isset( $_REQUEST['cm'] ) ) {

		$date = rsvp_url_date_query( 'past' );

		return $where . " AND rsvpdates.date < '" . $date . "'";

	} elseif ( isset( $startday ) && $startday ) {

		$t = rsvpmaker_strtotime( $startday );

		$d = rsvpmaker_date( 'Y-m-d', $t );

		return $where . " AND rsvpdates.date < '$d'";

	} elseif ( isset( $_GET['startday'] ) ) {

		$t = rsvpmaker_strtotime( $_GET['startday'] );

		$d = date( 'Y-m-d', $t );

		return $where . " AND rsvpdates.date < '$d'";

	} else {
		return $where . ' AND rsvpdates.date < CURDATE( )';
	}
}

function rsvpmaker_orderby_past( $orderby ) {

	return ' rsvpdates.date DESC';

}



function rsvpmaker_upcoming_query( $atts = array() ) {

	global $wpdb, $dataloop;

	if ( isset( $_GET['debug_query'] ) ) {

		add_filter( 'posts_request', 'rsvpmaker_examine_query' );
	}

	if ( isset( $atts['startday'] ) ) {

		$startday = $atts['startday'];
	}

	$limit = isset( $atts['limit'] ) ? $atts['limit'] : 10;

	if ( isset( $atts['posts_per_page'] ) ) {

		$limit = $atts['posts_per_page'];
	}

	if ( isset( $atts['days'] ) ) {

		$datelimit = $atts['days'] . ' DAY';

	} else {
		$datelimit = '180 DAY';
	}

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	add_filter( 'posts_join', 'rsvpmaker_join' );

	add_filter( 'posts_groupby', 'rsvpmaker_groupby' );

	add_filter( 'posts_distinct', 'rsvpmaker_distinct' );

	add_filter( 'posts_fields', 'rsvpmaker_select' );

	if ( isset( $atts['past'] ) && $atts['past'] ) {

		add_filter( 'posts_where', 'rsvpmaker_where_past' );

		add_filter( 'posts_orderby', 'rsvpmaker_orderby_past' );

	} elseif ( isset( $atts['afternow'] ) ) {

		add_filter( 'posts_where', 'rsvpmaker_where_afternow' );

		add_filter( 'posts_orderby', 'rsvpmaker_orderby' );

	} else {

		add_filter( 'posts_where', 'rsvpmaker_where' );

		add_filter( 'posts_orderby', 'rsvpmaker_orderby' );

		if ( $paged == 1 ) {
			cache_rsvp_dates( $limit + 20 );
		}
	}


	$queryarg = array(
		'post_type'   => 'rsvpmaker',
		'post_status' => 'publish',
		'paged'       => $paged,
	);

	if ( ! empty( $atts['author'] ) ) {

		$queryarg['author'] = $atts['author'];
	}

	if ( isset( $atts['one'] ) && ! empty( $atts['one'] ) ) {

		$queryarg['posts_per_page'] = 1;

		if ( is_numeric( $atts['one'] ) ) {

			$queryarg['p'] = $atts['one'];
		}

		elseif ( $atts['one'] != 'next' ) {

			$queryarg['name'] = $atts['one'];
		}

	}

	if ( isset( $atts['type'] ) ) {

		$queryarg['rsvpmaker-type'] = $atts['type'];
	}

	if ( $limit ) {

		$queryarg['posts_per_page'] = $limit;
	}

	if ( ! empty( $atts['post_id'] ) && is_numeric( $atts['post_id'] ) ) {

		$queryarg['p'] = $atts['post_id'];

		$dataloop = true;// prevents more events link from being displayed

	}

	if ( isset( $atts['meta_key'] ) ) {
		$queryarg['meta_key'] = $atts['meta_key'];
	}

	if ( isset( $atts['meta_value'] ) ) {
		$queryarg['meta_value'] = $atts['meta_value'];
	}

	if ( isset( $_GET['debug'] ) ) {

		$wpdb->show_errors();
	}
	
	//add_filter('posts_request','rsvpmaker_modify_query');
	$wp_query = new WP_Query( $queryarg );

	// clean up so this doesn't interfere with other operations

	remove_filter( 'posts_join', 'rsvpmaker_join' );

	remove_filter( 'posts_groupby', 'rsvpmaker_groupby' );

	remove_filter( 'posts_distinct', 'rsvpmaker_distinct' );

	remove_filter( 'posts_fields', 'rsvpmaker_select' );

	remove_filter( 'posts_where', 'rsvpmaker_where_past' );

	remove_filter( 'posts_orderby', 'rsvpmaker_orderby_past' );

	remove_filter( 'posts_where', 'rsvpmaker_where_afternow' );

	remove_filter( 'posts_where', 'rsvpmaker_where' );

	remove_filter( 'posts_orderby', 'rsvpmaker_orderby' );

	return $wp_query;

}

function rsvpmaker_upcoming( $atts = array() ) {
	$no_events = ( isset( $atts['no_events'] ) ) ? $atts['no_events'] : 'No events currently listed.';

	if ( isset( $atts['calendar'] ) && ( $atts['calendar'] == 2 ) ) {

		return rsvpmaker_calendar( $atts );
	}

	global $post;

	if ( ! empty( $post->post_type ) && ( $post->post_type == 'rsvpmaker' ) ) {

		// no infinite loops, please

		return 'The events listing cannot be displayed inside an individual event';

	}

	$post_backup = $post;

	global $wp_query, $wpdb, $showbutton, $startday, $rsvp_options, $datelimit, $last_time, $dataloop, $email_context;

	$last_time = time();

	$listings = '';

	$showbutton = true;

	$format = ( empty( $atts['format'] ) ) ? '' : $atts['format'];

	if ( ! empty( $atts['excerpt'] ) ) {

		$format = 'excerpt';
	}

	if ( $email_context && ( ( $format == 'form' ) || $format == 'with_form' ) ) {

		$format = 'button';
	}

	$backup = $wp_query;

	$wp_query = rsvpmaker_upcoming_query( $atts );

	ob_start();

	if ( isset( $_GET['dctest'] ) ) {

		print_r( $atts );

		echo esc_html($querystring);

	}

	if ( isset( $atts['demo'] ) ) {

		$demo = "<div><strong>Shortcode:</strong></div>\n<code>[rsvpmaker_upcoming";

		if ( is_array( $atts ) ) {

			foreach ( $atts as $name => $value ) {

				if ( $name == 'demo' ) {

					continue;
				}

				$demo .= ' ' . $name . '="' . $value . '"';

			}
		}

		$demo .= "]</code>\n";

		$demo .= "<div><strong>Output:</strong></div>\n";

		echo wp_kses_post($demo);

	}

	echo '<div class="rsvpmaker_upcoming">';

	if ( have_posts() ) {

		global $events_displayed;

		while ( have_posts() ) :
			the_post();

			if ( ! empty( $atts['exclude_type'] ) ) {

				$termscheck = array();

				$terms = get_the_terms( $post->ID, 'rsvpmaker-type' );

				if ( $terms ) {

					foreach ( $terms as $term ) {

						$termscheck[] = $term->slug;

					}
				}

				if ( in_array( $atts['exclude_type'], $termscheck ) ) {

					continue;
				}
			}

			$events_displayed[] = $post->ID;

			if ( $format == 'compact' ) {

				echo rsvpmaker_compact_format( $post );

				continue;

			} elseif ( $format == 'compact_form' ) {

				$atts['show_form'] = 1;

				$content = rsvpmaker_compact_format( $post, $atts );

			} elseif ( $format == 'form' ) {

				// form only

				echo rsvpmaker_form( $post );

				continue;

			} elseif ( $format == 'button_only' ) {

				echo get_rsvp_link( $post->ID );

				continue;

			} elseif ( $format == 'embed_dateblock' ) {

				echo embed_dateblock( $atts );

				continue;

			}

			?>



<div id="rsvpmaker-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://schema.org/Event" >  

<h1 class="rsvpmaker-entry-title"><a class="rsvpmaker-entry-title-link" href="<?php the_permalink(); ?>"  itemprop="url"><span itemprop="name"><?php the_title(); ?></span></a></h1>

<div class="rsvpmaker-entry-content">

			<?php

			if ( $format == 'excerpt' ) {

				echo rsvpmaker_excerpt( $post );

			} elseif ( $format == 'with_form' ) {

				echo rsvpmaker_form( $post, do_blocks( $post->post_content ) );

			} else {
				the_content();
			}
			?>



</div><!-- .entry-content -->



			<?php

			if ( ! isset( $atts['hideauthor'] ) || ! $atts['hideauthor'] ) {

				$authorlink = sprintf(
					'<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
					get_author_posts_url( get_the_author_meta( 'ID' ) ),
					/* translators: placeholder = author name */

					sprintf( esc_attr__( 'View all posts by %s', 'rsvpmaker' ), get_the_author() ),
					get_the_author()
				);

				?>

<div class="event_author">
				<?php
				esc_html_e( 'Posted by', 'rsvpmaker' );
				echo " $authorlink on ";
				?>
<span class="rsvpupdated" datetime="<?php the_modified_date( 'c' ); ?>"><?php the_modified_date(); ?></span></div>

					<?php

			}

			?>

</div>

			<?php

			if ( current_user_can( 'edit_post', $post->ID ) && ! is_email_context() ) {

				echo '<p><a href="' . admin_url( 'post.php?action=edit&post=' . (int) $post->ID ) . '">Edit</a></p>';

			}

	endwhile;

		?>

<p>
		<?php

	}//end has_posts

	echo '</div><!-- end rsvpmaker_upcoming -->';

	$wp_query = $backup;

	wp_reset_postdata();

	$post = $post_backup;

	if ( ( isset( $atts['calendar'] ) && $atts['calendar'] ) || ( isset( $atts['format'] ) && ( $atts['format'] == 'calendar' ) ) ) {

		if ( ! ( isset( $atts['one'] ) && $atts['one'] ) ) {

			$listings = rsvpmaker_calendar( $atts );
		}
	}

	$listings .= ob_get_clean();

	if ( is_email_context() ) {

		$listings = str_replace( '>Edit<', '><', $listings ); // todo preg replace

		$listings = str_replace( '><a', '> <a', $listings ); // todo preg replace

	}

	return $listings;
}

// get all of the dates for the month

function rsvpmaker_calendar_where( $where ) {

	global $startday;

	if ( isset( $_REQUEST['cm'] ) ) {

		$d = "'" . rsvp_url_date_query() . "'";

	} elseif ( isset( $startday ) && $startday ) {

		$t = rsvpmaker_strtotime( $startday );

		$d = "'" . rsvpmaker_date( 'Y-m-d', $t ) . "'";

	} elseif ( isset( $_GET['startday'] ) ) {

		$t = rsvpmaker_strtotime( $_GET['startday'] );

		$d = "'" . rsvpmaker_date( 'Y-m-d', $t ) . "'";

	} else {
		$d = "'" . rsvpmaker_date( 'Y-m' ) . "-01'";
	}

	// $d = ' CURDATE() ';

	return $where . ' AND enddate > ' . $d . ' AND enddate < DATE_ADD(' . $d . ', INTERVAL 5 WEEK) ';

}



function rsvpmaker_calendar_clear( $g ) {

	return '';

}



function rsvpmaker_item_class( $post_id, $post_title ) {

	$tp = preg_split( '/[^A-Za-z]{1,5}/', $post_title );

	$tp = array_slice( $tp, 0, 4 );

	$class = implode( '_', $tp );

	$tax_terms = wp_get_post_terms( $post_id, 'rsvpmaker-type' );

	if ( is_array( $tax_terms ) ) {
		foreach ( $tax_terms as $tax_term ) {
			$class .= ' ' . preg_replace( '/[^A-Za-z]{1,5}/', '_', $tax_term->name );
		}
	}

	return $class;
}

function rsvpmaker_calendar( $atts = array() ) {
	if ( is_admin() || wp_is_json_request() ) {
		return;
	}

	global $post;

	$post_backup = $post;

	global $wp_query;

	global $wpdb;

	global $showbutton;

	global $startday;

	global $rsvp_options;
	$atts['itembg'] = (empty($atts['itembg'])) ? '#00000' : $atts['itembg'];
	$atts['itemcolor'] = (empty($atts['itemcolor'])) ? '#FFFFFF' : $atts['itemcolor'];
	$atts['itemfontsize'] = (empty($atts['itemfontsize'])) ? 'x-small' : $atts['itemfontsize'];
	$itemstyle = sprintf('color:%s;background-color:%s;font-size:%spx',$atts['itemcolor'],$atts['itembg'],$atts['itemfontsize']);

	$date_format = ( isset( $atts['date_format'] ) ) ? $atts['date_format'] : $rsvp_options['short_date'];
	$debug = '';

	if ( isset( $atts['startday'] ) ) {

		$startday = $atts['startday'];
	}

	$self = $req_uri = get_permalink();

	$req_uri .= ( strpos( $req_uri, '?' ) ) ? '&' : '?';

	$showbutton = true;
	$backup     = $wp_query;

	// removing groupby, which interferes with display of multi-day events

	add_filter( 'posts_join', 'rsvpmaker_join' );
	add_filter( 'posts_where', 'rsvpmaker_calendar_where', 99 );
	add_filter( 'posts_orderby', 'rsvpmaker_orderby' );
	add_filter( 'posts_groupby', 'rsvpmaker_calendar_clear' );
	add_filter( 'posts_distinct', 'rsvpmaker_calendar_clear' );
	add_filter( 'posts_fields', 'rsvpmaker_select' );

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	$querystring = "post_type=rsvpmaker&post_status=publish&posts_per_page=-1&paged=$paged";

	if ( isset( $atts['type'] ) ) {
		$querystring .= '&rsvpmaker-type=' . $atts['type'];
	}

	if ( isset( $atts['add_to_query'] ) ) {
		if ( ! strpos( $atts['add_to_query'], '&' ) ) {
			$atts['add_to_query'] = '&' . $atts['add_to_query'];
		}
		$querystring .= $atts['add_to_query'];
	}

	$wpdb->show_errors();

	$wp_query = new WP_Query( $querystring );

	// clean up so this doesn't interfere with other operations

	remove_filter( 'posts_join', 'rsvpmaker_join' );

	remove_filter( 'posts_where', 'rsvpmaker_calendar_where', 99 );

	remove_filter( 'posts_orderby', 'rsvpmaker_orderby' );

	remove_filter( 'posts_groupby', 'rsvpmaker_calendar_clear' );

	remove_filter( 'posts_distinct', 'rsvpmaker_calendar_clear' );

	remove_filter( 'posts_fields', 'rsvpmaker_select' );

	$eventarray = array();

	if ( have_posts() ) {
		while ( have_posts() ) :
			the_post();

			if(isset($_GET['debug'])) {
				$post->post_content = '';
				$debug .= var_export($post,true)."\n\n";
				$checktime = rsvpmaker_strtotime($post->datetime);
				if($checktime != $post->ts_start)
					$debug .= "MISMATCH TIME $checktime != $post->ts_start \n\n";
				else
					$debug .= "$checktime == $post->ts_start \n\n";
					
			}

			if ( ! empty( $atts['exclude_type'] ) ) {

				$termscheck = array();

				$terms = get_the_terms( $post->ID, 'rsvpmaker-type' );

				if ( $terms ) {

					foreach ( $terms as $term ) {

						$termscheck[] = $term->slug;

					}
				}

				if ( in_array( $atts['exclude_type'], $termscheck ) ) {

					continue;
				}
			}

			// calendar entry

			if ( empty( $post->post_title ) ) {

				$post->post_title = __( 'Title left blank', 'rsvpmaker' );
			}

			$keys = array();
			$t = $post->ts_start;
			do {
				$keys[] = rsvpmaker_date( 'Y-m-d', $t );
				$t += DAY_IN_SECONDS;
			} while ($t < $post->ts_end);
			if(1 == sizeof($keys)) {
				$time = ( $post->display_type == 'allday' ) ? '' : '<br />&nbsp;' . rsvpmaker_timestamp_to_time( $t, false, $post->timezone );
				if ( ( $post->display_type == 'set' ) && ! empty( $end )  ) {
					$time .= '-' . rsvpmaker_timestamp_to_time( rsvpmaker_strtotime( $end, false, $post->timezone ) );	
				}	
			}
			else 
				$time = '<br>'.rsvpmaker_date($rsvp_options['short_date'],$post->ts_start, $post->timezone) .' - '.rsvpmaker_date($rsvp_options['short_date'],$post->ts_end, $post->timezone);

			if(isset($_GET['debug'])) {
				$debug .= $time."\n\n";
				$time .= $duration_type;
			}

			if ( isset( $_GET['debug'] ) ) {

				$msg = sprintf( '%s %s %s', $post->post_title, $post->datetime, $post->meta_id );

			}
			foreach($keys as $key)
				$eventarray[ $key ] = ( isset( $eventarray[ $key ] ) ) ? $eventarray[ $key ] . '<div><a style="'.$itemstyle.'" class="rsvpmaker-item rsvpmaker-tooltip ' . rsvpmaker_item_class( $post->ID, $post->post_title ) . '" href="' . get_post_permalink( $post->ID ) . '" title="' . htmlentities( $post->post_title ) . '">' . $post->post_title . $time . "</a></div>\n" : '<div><a  style="'.$itemstyle.'" class="rsvpmaker-item rsvpmaker-tooltip ' . rsvpmaker_item_class( $post->ID, $post->post_title ) . '" href="' . get_post_permalink( $post->ID ) . '" title="' . htmlentities( $post->post_title ) . '">' . $post->post_title . $time . "</a></div>\n";

	endwhile;

	}

	$wp_query = $backup;

	wp_reset_postdata();

	// calendar display routine

	$nav = isset( $atts['nav'] ) ? $atts['nav'] : 'bottom';

	$months = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

	$cm = ( isset( $_REQUEST['cm'] ) ) ? (int) $_REQUEST['cm'] : (int) rsvpmaker_date( 'm' );
	$cmpad = ($cm < 10) ? '0'.$cm : $cm;

	$cy = ( isset( $_REQUEST['cy'] ) ) ? (int) $_REQUEST['cy'] : (int) rsvpmaker_date( 'Y' );

	$monthname = $months[ $cm ];

	$date = $bom = rsvpmaker_strtotime( 'first day of ' . $monthname . ' ' . $cy );

	$eom = rsvpmaker_strtotime( 'last day of ' . $monthname . ' ' . $cy );

	$nowdate = rsvpmaker_date( 'Y-m-d', $bom );

	$yearmonth = rsvpmaker_date( 'Y-m-', $bom );

	$monthafter = $eom + ( DAY_IN_SECONDS * 32 );

	// Link to previous month (but do not link to too early dates)
	$linktime  = rsvpmaker_strtotime( $cy.'-'.$cmpad.'-01 -1 month' );
	$prev_link = '<a href="' . esc_attr( $req_uri . 'cm=' . rsvpmaker_date( 'm', $linktime ) . '&cy=' . rsvpmaker_date( 'Y', $linktime ) ) . '">' . rsvpmaker_date( 'F', $linktime ) . ' ' . rsvpmaker_date( 'Y', $linktime ) . '</a> &lt;';

	// Link to next month (but do not link to too early dates)
	$linktime  = rsvpmaker_strtotime( $cy.'-'.$cmpad.'-01 +1 month' );
	$next_link = '&gt; <a href="' . esc_attr( $req_uri . 'cm=' . rsvpmaker_date( 'm', $linktime ) . '&cy=' . rsvpmaker_date( 'Y', $linktime ) ) . '">' . rsvpmaker_date( 'F', $linktime ) . ' ' . rsvpmaker_date( 'Y', $linktime ) . '</a>';

	$linktime     = $bom;
	$current_link = '<a href="' . esc_attr( $req_uri . 'cm=' . rsvpmaker_date( 'm', $linktime ) . '&cy=' . rsvpmaker_date( 'Y', $linktime ) ) . '">' . rsvpmaker_date( 'F', $linktime ) . ' ' . rsvpmaker_date( 'Y', $linktime ) . '</a>';
	$page_id      = ( isset( $_GET['page_id'] ) ) ? '<input type="hidden" name="page_id" value="' . (int) $_GET['page_id'] . '" />' : '';

	// $Id: cal.php,v 1.47 2003/12/31 13:04:27 goba Exp $
	// Begin the calendar table

	$content = '<h3 style="text-align: center">' . rsvpmaker_date( '<b>%B %Y</b>', $bom ) . '</h3>';

	if ( ( $nav == 'top' ) || ( $nav == 'both' ) ) { // either it's top or both

		$content .= '<div class="rsvpmaker_nav"><span class="navprev">' . $prev_link . '</span> ' . $current_link . ' <span class="navnext">' .
		$next_link . '</span></div>';
	}

	$content .= '
<div class="calendarwrapper" style="background-color: #fff; color: #000; margin-bottom: 5px; width: 95%; margin-left: auto; margin-right: auto;" >
<table id="cpcalendar" style="width: 100%" cellspacing="0" cellpadding="3">' . "\n";

	if ( isset( $atts['weekstart'] ) && ( $atts['weekstart'] == 'Monday' ) ) {
		$content .= '<thead>

<tr>

<th>' . __( 'Monday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Tuesday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Wednesday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Thursday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Friday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Saturday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Sunday', 'rsvpmaker' ) . '</th> 

</tr>

</thead>

';

		$weekstart = 1;

	} else {

		$content .= '<thead>
<tr>

<th>' . __( 'Sunday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Monday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Tuesday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Wednesday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Thursday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Friday', 'rsvpmaker' ) . '</th> 

<th>' . __( 'Saturday', 'rsvpmaker' ) . '</th> 

</tr>

</thead>

';

		$weekstart = 0;

	}

	$content .= "\n<tbody><tr id=\"rsvprow1\">\n";

	$rowcount = 1;

	// Generate the requisite number of blank days to get things started

	for ( $days = $i = rsvpmaker_date( 'w', $bom ); $i > $weekstart; $i-- ) {

		$content .= '<td class="notaday">&nbsp;</td>';

	}

	$days = $days - $weekstart;// adjust if first day not sunday

	if ( isset( $_GET['debugpast'] ) ) {

		$todaydate = rsvpmaker_date( 'Y-m-d', rsvpmaker_strtotime( '+2 weeks' ) );

	} else {
		$todaydate = rsvpmaker_date( 'Y-m-d' );
	}

	// Print out all the days in this month

	for ( $i = 1; $i <= rsvpmaker_date( 't', $bom ); $i++ ) {

		// Print out day number and all events for the day

		$thisdate = $yearmonth . sprintf( '%02d', $i );

		$class = ( $thisdate == $todaydate ) ? 'today day' : 'day';

		if ( $thisdate < $todaydate ) {

			$class .= ' past';
		}

		if ( $thisdate > $todaydate ) {

			$class .= ' future';
		}

		$content .= '<td valign="top" class="' . $class . '">';

		if ( ! empty( $eventarray[ $thisdate ] ) ) {

			$content .= $i;

			$content .= $eventarray[ $thisdate ];

			$t = rsvpmaker_strtotime( $thisdate );

		} else {
			$content .= '<div class="' . esc_attr( $class ) . '">' . $i . '</div><p>&nbsp;</p>';
		}

		$content .= '</td>';

		// Break HTML table row if at end of week

		if ( ++$days % 7 == 0 ) {

			$content .= "</tr>\n";

			$rowcount++;

			$content .= '<tr id="rsvprow' . $rowcount . '">';

		}
	}

	// Generate the requisite number of blank days to wrap things up

	for ( ; $days % 7; $days++ ) {

		$content .= '<td class="notaday">&nbsp;</td>';

	}

	$content .= "\n</tr>";

	$content .= "<tbody>\n";

	// End HTML table of events

	$content .= "\n</table>\n";

	if ( $nav != 'top' ) { // either it's bottom or both

		$content .= '<div class="rsvpmaker_nav"><span class="navprev">' . $prev_link . '</span> ' . $current_link . ' <span class="navnext">' .

		'' . $next_link . '</span></div>';
	}

	// jump form

	$content .= sprintf( '<form class="rsvpmaker_jumpform" action="%s" method="get"> %s <input type="number" name="cm" value="%s" size="4" class="jumpmonth" />/<input type="number" name="cy" value="%s" size="4" class="jumpyear" /><button>%s</button>%s</form>', $self, __( 'Month/Year', 'rsvpmaker' ), rsvpmaker_date( 'm', $monthafter ), rsvpmaker_date( 'Y', $monthafter ), __( 'Go', 'rsvpmaker' ), $page_id );
	$content .= '<div>';
	$post = $post_backup;

	if(isset($_GET['debug']))
		$content .= "<pre>$debug</pre>";

	return $content;

}

function rsvpmaker_template_fields( $select ) {
	$select .= ', tmeta.meta_value as sked';
	return $select;
}

function rsvpmaker_template_join( $join ) {
	global $wpdb;
	return $join . " JOIN $wpdb->postmeta tmeta ON tmeta.post_id = $wpdb->posts.ID ";
}

function rsvpmaker_template_where( $where ) {
	return " AND (tmeta.`meta_key` REGEXP '_sked_[A-Z].+' AND tmeta.meta_value)";
}

function rsvpmaker_template_orderby( $orderby ) {
	return ' post_title ';
}

function rsvpmaker_template_events_where( $where ) {

	global $rsvptemplate;

	if ( isset( $_GET['t'] ) ) {

		$rsvptemplate = (int) $_GET['t'];
	}

	if ( ! $rsvptemplate ) {

		return $where;
	}

	return $where . " AND meta_key='_meet_recur' AND meta_value=$rsvptemplate";

}

// utility function, template tag
function is_rsvpmaker($post_id = 0) {
	global $post;
	if($post_id)
		$post = get_post($post_id);
	if(is_null($post) || empty($post->post_type) )
		return false;
	return $post->post_type == 'rsvpmaker';
}
function is_rsvpemail($post_id = 0) {
	global $post;
	if($post_id)
		$post = get_post($post_id);
	if(is_null($post) || empty($post->post_type) )
		return false;
	return $post->post_type == 'rsvpemail';
}

function rsvpmaker_timed( $atts = array(), $content = '' ) {
	if ( ! empty( $atts['start'] ) ) {
		$start = rsvpmaker_strtotime( $atts['start'] );
		$now   = current_time( 'timestamp' );
		if ( $now < $start ) {
			if ( isset( $_GET['debug'] ) ) {
				return sprintf( '<p>start %s / now %s</p>', date( 'r', $start ), date( 'r', $now ) );
			} elseif ( isset( $atts['too_early'] ) ) {
				return '<p>' . esc_html( $atts['too_early'] ) . '</p>';
			} else {
				return '';
			}
		}
	}

	if ( ! empty( $atts['end'] ) ) {
		$end = rsvpmaker_strtotime( $atts['end'] );
		$now = current_time( 'timestamp' );
		if ( $now > $end ) {
			if ( isset( $_GET['debug'] ) ) {
				return sprintf( '<p>end %s / now %s</p>', date( 'r', $end ), date( 'r', $now ) );
			} elseif ( isset( $atts['too_late'] ) ) {
				return '<p>' . esc_html( $atts['too_late'] ) . '</p>';
			} else {
				return '';
			}
		}
	}

	if ( ! empty( $atts['post_id'] ) ) {
		$qs = 'posts_per_page=1&p=' . (int) $atts['post_id'];
		if ( $atts['post_type'] ) {
			$qs .= '&post_type=' . $atts['post_type'];
		}
		$cq = new WP_Query( $qs );
		if ( $cq->have_posts() ) :
			$cq->the_post();
			ob_start();
			global $post;
			$post_backup = $post;
			?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
<div class="entry-content">
			<?php
			the_content();
			?>
</div><!-- .entry-content -->
</div>
			<?php
			$content = ob_get_clean();
			$post    = $post_backup;
	endif;

	}

	// if we clear these two hurdles, return the content
	if ( ! empty( $atts['style'] ) ) {
		$content = '<div style="' . $atts['style'] . '">' . $content . '</div>';
	}

	return $content;
}

function rsvpmaker_looking_ahead( $atts ) {

	global $last_time;

	global $events_displayed;

	$listings = '';

	$limit = isset( $atts['limit'] ) ? $atts['limit'] : 10;

	if ( isset( $atts['days'] ) ) {

		$datelimit = $atts['days'] . ' DAY';

	} else {
		$datelimit = '30 DAY';
	}

	if ( ! $last_time ) {

		return 'last time not found';
	}

	$results = get_future_events( "meta_value > '" . date( 'Y-m-d', $last_time ) . "' AND meta_value < DATE_ADD('" . date( 'Y-m-d', $last_time ) . "',INTERVAL $datelimit)", $limit, ARRAY_A );

	if ( $results ) {
		foreach ( $results as $row ) {
			if ( in_array( $row['postID'], $events_displayed ) ) {
				continue;
			}

			$t = rsvpmaker_strtotime( $row['datetime'] );
			if ( empty( $dateline[ $row['postID'] ] ) ) {
				$dateline[ $row['postID'] ] = '';
			} else {
				$dateline[ $row['postID'] ] .= ', ';
			}

			$dateline[ $row['postID'] ] .= date( 'M. j', $t );

			$eventlist[ $row['postID'] ] = $row;
		}
	}

	// strpos test used to catch either "headline" or "headlines"

	if ( isset( $eventlist ) && is_array( $eventlist ) ) {
		foreach ( $eventlist as $event ) {
			if ( isset( $atts['permalinkhack'] ) ) {
				$permalink = site_url() . '?p=' . $event['postID'];
			} else {
				$permalink = get_post_permalink( $event['postID'] );
			}
			$listings .= sprintf( '<li><a href="%s">%s</a> %s</li>' . "\n", esc_url_raw( $permalink ), esc_html( $event['post_title'] ), esc_html( $dateline[ $event['postID'] ] ) );
		}

		if ( ! empty( $rsvp_options['eventpage'] ) ) {

			$listings .= '<li><a href="' . esc_url( $rsvp_options['eventpage'] ) . '">' . __( 'Go to Events Page', 'rsvpmaker' ) . '</a></li>';
		}

		if ( isset( $atts['title'] ) ) {
			$listings = '<p><strong>' . esc_html( $atts['title'] ) . "</strong></p>\n<ul id=\"eventheadlines\">\n$listings</ul>\n";
		} else {
			$listings = "<ul id=\"eventheadlines\">\n$listings</ul>\n";
		}
	}//end if $eventlist
	return $listings;
}

function get_adjacent_rsvp_join( $join ) {
	global $post;
	if ( $post->post_type != 'rsvpmaker' ) {
		return $join;
	}
	global $wpdb;
	return $join . ' JOIN ' . $wpdb->postmeta . ' ON p.ID=' . $wpdb->postmeta . ".post_id AND meta_key='_rsvp_dates' ";
}

add_filter( 'get_previous_post_join', 'get_adjacent_rsvp_join' );
add_filter( 'get_next_post_join', 'get_adjacent_rsvp_join' );

function get_adjacent_rsvp_sort( $sort ) {
	global $post;
	if ( $post->post_type != 'rsvpmaker' ) {
		return $sort;
	}
	global $wpdb;
	$sort = str_replace( 'p.post_date', $wpdb->postmeta . '.meta_value', $sort );
	return $sort;
}

add_filter( 'get_previous_post_sort', 'get_adjacent_rsvp_sort' );
add_filter( 'get_next_post_sort', 'get_adjacent_rsvp_sort' );

function get_adjacent_rsvp_where( $where ) {
	global $post;
	if ( $post->post_type != 'rsvpmaker' ) {
		return $where;
	}
	if ( ! get_rsvp_date( $post->ID ) ) { // if not an event, we don't want to display adjacent post links
		return 'WHERE false';
	}
	global $wpdb;

	$op = strpos( $where, '>' ) ? '>' : '<';

	$current_event_date = $wpdb->get_var( 'select meta_value from ' . $wpdb->postmeta . " WHERE meta_key='_rsvp_dates' AND post_id=" . $post->ID );

	// split and modify

	$wparts = explode( 'p.post_type', $where );
	$where  = 'WHERE ' . $wpdb->postmeta . ".meta_value $op '$current_event_date' AND p.ID != $post->ID AND p.post_type" . $wparts[1];

	return $where;
}

add_filter( 'get_previous_post_where', 'get_adjacent_rsvp_where' );

add_filter( 'get_next_post_where', 'get_adjacent_rsvp_where' );

// based on https://gist.github.com/hugowetterberg/81747

function rsvp_ical_split( $preamble, $value ) {

	$value = trim( $value );

	$value = strip_tags( $value );

	$value = str_replace( "\n", "\\n", $value );

	$value = str_replace( "\r", '', $value );

	$value = preg_replace( '/\s{2,}/', ' ', $value );

	$preamble_len = strlen( $preamble );

	$lines = array();

	while ( strlen( $value ) > ( 75 - $preamble_len ) ) {

		$space = ( 75 - $preamble_len );

		$mbcc = $space;

		while ( $mbcc ) {

			$line = mb_substr( $value, 0, $mbcc );

			$oct = strlen( $line );

			if ( $oct > $space ) {

				$mbcc -= $oct - $space;

			} else {

				$lines[] = $line;

				$preamble_len = 1; // Still take the tab into account

				$value = mb_substr( $value, $mbcc );

				break;

			}
		}
	}

	if ( ! empty( $value ) ) {

		$lines[] = $value;

	}

	return join( "\n\t", $lines );

}



function rsvpmaker_to_ical() {
	global $post;
	global $rsvp_options;
	global $wpdb;

	if ( ! isset( $post->post_type ) || ( $post->post_type != 'rsvpmaker' ) ) {
		return;
	}


	header( 'Content-type: text/calendar; charset=utf-8' );

	header( 'Content-Disposition: attachment; filename=' . $post->post_name . '.ics' );
	echo rsvpmaker_to_ical_email($post->ID);//call without email parameters to get core content
	exit;
}

function rsvpmaker_to_gcal( $post, $datetime, $duration ) {
	$venue_meta = get_post_meta( $post->ID, 'venue', true );
	$venue = ( empty( $venue_meta ) ) ? 'See: ' . get_permalink( $post->ID ) : $venue_meta;
	return sprintf( 'http://www.google.com/calendar/event?action=TEMPLATE&amp;text=%s&amp;dates=%s/%s&amp;details=%s&amp;location=%s&amp;trp=false&amp;sprop=%s&amp;sprop=name:%s', urlencode( $post->post_title ), get_utc_ical( $datetime ), get_utc_ical( $duration ), urlencode( get_bloginfo( 'name' ) . ' ' . get_permalink( $post->ID ) ), urlencode($venue), get_permalink( $post->ID ), urlencode( get_bloginfo( 'name' ) ) );
}

function get_utc_ical( $timestamp ) {

	return gmdate( 'Ymd\THis\Z', rsvpmaker_strtotime( $timestamp ) );

}



function rsvp_row_to_profile( $row ) {

	if ( empty( $row['details'] ) ) {

		$profile = array();

	} else {
		$profile = unserialize( $row['details'] );
	}

	if ( is_array( $row ) ) {

		foreach ( $row as $field => $value ) {

			if ( isset( $profile[ $field ] ) || ( $field == 'details' ) ) {

				continue;

			} else {
				$profile[ $field ] = $value;
			}
		}
	}

	return $profile;

}



function rsvpmaker_type_dateorder( $sql ) {

	echo esc_html($sql);

	return $sql;

}



function rsvpmaker_archive_pages( $query ) {

	if ( is_admin() || wp_is_json_request() ) {

		return;
	}

	if ( is_archive() && $query->is_main_query() && isset( $query->query['post_type'] ) && ( $query->query['post_type'] == 'rsvpmaker' ) ) {

		add_filter( 'posts_join', 'rsvpmaker_join' );

		add_filter( 'posts_groupby', 'rsvpmaker_groupby' );

		add_filter( 'posts_distinct', 'rsvpmaker_distinct' );

		add_filter( 'posts_fields', 'rsvpmaker_select' );

		add_filter( 'posts_where', 'rsvpmaker_where' );

		add_filter( 'posts_orderby', 'rsvpmaker_orderby' );

		if ( isset( $_GET['debug_query'] ) ) {

			add_filter( 'posts_request', 'rsvpmaker_examine_query' );
		}
	}

	if ( is_archive() && $query->is_main_query() && ! empty( $query->query['rsvpmaker-type'] ) ) {

		add_filter( 'posts_join', 'rsvpmaker_join' );

		add_filter( 'posts_groupby', 'rsvpmaker_groupby' );

		add_filter( 'posts_distinct', 'rsvpmaker_distinct' );

		add_filter( 'posts_fields', 'rsvpmaker_select' );

		add_filter( 'posts_where', 'rsvpmaker_where' );

		add_filter( 'posts_orderby', 'rsvpmaker_orderby' );

		if ( isset( $_GET['debug_query'] ) ) {

			add_filter( 'posts_request', 'rsvpmaker_examine_query' );
		}
	}

	if(is_archive() && !$query->is_main_query() ) {

		remove_filter( 'posts_join', 'rsvpmaker_join' );

		remove_filter( 'posts_groupby', 'rsvpmaker_groupby' );

		remove_filter( 'posts_distinct', 'rsvpmaker_distinct' );

		remove_filter( 'posts_fields', 'rsvpmaker_select' );

		remove_filter( 'posts_where', 'rsvpmaker_where' );

		remove_filter( 'posts_orderby', 'rsvpmaker_orderby' );
	}

}

function get_rsvpmaker_archive_link( $page = 1 ) {

	$link = get_post_type_archive_link( 'rsvpmaker' );

	$link .= ( strpos( $link, '?' ) ) ? '&paged=' . $page : '?paged=' . $page;

	return $link;

}

function rsvpmaker_examine_query( $request ) {

	$log = var_export( $request, true );

	mail( 'david@carrcommunications.com', 'query test', $log );

	return $request;

}



function rsvpmaker_facebook_meta() {

	global $post;

	global $rsvp_options;

	if ( ! isset( $post->post_type ) || ( $post->post_type != 'rsvpmaker' ) ) {

		return; // don't mess with other post types
	}

	$date = rsvpmaker_short_date( $post->ID, true );

	$title = get_the_title( $post->ID );

	$titlestr = $title . ' - ' . $date . ' - ' . get_bloginfo( 'name' );

	printf( '<meta property="og:title" content="%s" /><meta property="twitter:title" content="%s" />', esc_attr( $titlestr ), esc_attr( $titlestr ) );
}

function ylchat( $atts ) {

	global $post;

	preg_match( '/(https:\/\/www.youtube.com\/watch\?v=|https:\/\/youtu.be\/)([^\s]+)/', $post->post_content, $matches );

	if ( ! isset( $matches[2] ) ) {
		return;
	}

	$url = sprintf( 'https://www.youtube.com/live_chat?v=%s&amp;embed_domain=%s', esc_attr( $matches[2] ), esc_attr( sanitize_text_field($_SERVER['SERVER_NAME']) ) );

	$login_url = 'https://accounts.google.com/ServiceLogin?uilel=3&service=youtube&hl=en&continue=https%3A%2F%2Fwww.youtube.com%2Fsignin%3Ffeature%3Dcomments%26next%3D%252Flive_chat%253Fis_popout%253D1%2526v%253D' . esc_attr( $matches[2] ) . '%26hl%3Den%26action_handle_signin%3Dtrue%26app%3Ddesktop&passive=true';

	$test = file_get_contents( $url );

	if ( strpos( $test, 'live-chat-unavailable' ) ) {
		return;
	}

	$note = ( isset( $atts['note'] ) ) ? '<p>' . esc_html( $atts['note'] ) . '</p>' : '';

	$width = ( isset( $atts['width'] ) ) ? esc_attr( $atts['width'] ) : '100%';

	$height = ( isset( $atts['height'] ) ) ? esc_attr( $atts['height'] ) : '200';

	if ( isset( $_GET['height'] ) ) {

		$height = (int) $_GET['height'];
	}

	return $note . sprintf( '<iframe src="%s" width="%s" height="%s"></iframe>', esc_url_raw( $url ), esc_attr( $width ), esc_attr( $height ) ) . sprintf( '<p>%s <a href="%s" target="_blank">%s</a>. %s</p>', __( 'If the chat prompt does not appear below,', 'rsvpmaker' ), $login_url, __( 'please login to your YouTube/Google account', 'rsvpmaker' ), __( 'Then refresh this window.', 'rsvpmaker' ) );

}



function rsvpmaker_next( $atts = array( 'post_id' => 'next' ) ) {
	if ( ! empty( $atts['rsvp_on'] ) ) {

		$atts['post_id'] = 'nextrsvp';
	}

	return rsvpmaker_one( $atts );

}



function rsvpmaker_one( $atts = array() ) {
	email_content_minfilters();

	if ( isset( $atts['one_format'] ) ) {

		$atts['format'] = $atts['one_format'];
	}

	if ( empty( $atts['format'] ) ) {
		$atts['format'] = is_email_context() ? 'button' : 'with_form';
	}

	$atts['limit'] = 1;

	$atts['hideauthor'] = 1;

	if ( empty( $atts['post_id'] ) || ( $atts['post_id'] == 'next' ) ) {

		$atts['post_id'] = '';

	} elseif ( $atts['post_id'] == 'nextrsvp' ) {

		$event = get_next_rsvp_on();

		if ( $event ) {

			$atts['post_id'] = $event->ID;

		} else {
			return;
		}
	}

	return rsvpmaker_upcoming( $atts );

}



function rsvpmaker_compact_format( $post, $atts = array() ) {
	global $rsvp_options;

	$post_id = $post->ID;

	global $events_displayed;

	ob_start();

	echo '<div class="rsvpmaker_compact">';

	$dateblock = ', ' . rsvpmaker_short_date( $post->ID, true );

	$dateblock = str_replace( ':00', '', $dateblock );

	?>

<div id="rsvpmaker-<?php echo esc_attr( $post_id ); ?>" itemscope itemtype="http://schema.org/Event" >  

<p class="rsvpmaker-compact-title" itemprop="url"><span itemprop="name">
	<?php
	echo esc_html( get_the_title( $post ) );
	echo wp_kses_post($dateblock);
	?>
</span></p>

	<?php

	if ( isset( $atts['show_form'] ) ) {

		echo rsvpmaker_form( $atts );

	} else {

		echo get_rsvp_link( $post_id );

	}

	echo '</div></div><!-- end rsvpmaker_compact -->';

	return ob_get_clean();

}



function rsvpmaker_next_rsvps( $atts = array() ) {
	if ( is_admin() ) {

		return;
	}

	global $wp_query, $post, $dataloop, $rsvp_options;

	$dataloop = true;

	$atts['posts_per_page'] = ( empty( $atts['number_of_posts'] ) ) ? 5 : $atts['number_of_posts'];

	$atts['meta_key'] = '_rsvp_on';

	$atts['meta_value'] = '1';

	$backup_query = $wp_query;

	$backup_post = $post;

	$wp_query = rsvpmaker_upcoming_query( $atts );

	$output = $list = '';

	$count = 0;

	if ( have_posts() ) {

		$output .= '<div class="rsvpmaker_next_events">';

		while ( have_posts() ) :
			the_post();

			$count++;

			if ( $count == 1 ) {

				$output .= '<div class="rsvpmaker_next_events_featured">';

				$url = get_permalink( $post->ID );

				$output .= '<h2 class="rsvpmaker-compact-title" itemprop="url"><span itemprop="name"><a href="' . $url . '">' . get_the_title( $post ) . '</a></span></h2>';

				$d = rsvp_date_block( $post->ID );
				$output .= $d['dateblock'];

				$output .= get_rsvp_link( $post->ID );

				$output .= '</div><!-- end next featured -->';

			} else {

				if ( empty( $list ) ) {

					$list = '<ul>';
				}

				$url = get_permalink( $post->ID ) . '#rsvpnow';

				$t = rsvpmaker_strtotime( $post->datetime );

				$datetime = rsvpmaker_date( '', $t );

				$list .= sprintf( '<li><a href="%s">%s</a></li>', esc_url_raw( $url ), esc_html( $post->post_title . '&#8212;' . $datetime ) );

			}

			// printf('<p>Additional %s</p>',$post->post_title);

		endwhile;

		if ( ! empty( $list ) ) {

			$list .= '</ul>';

			$output .= $list;

		}

		$output .= '</div><!-- end rsvpmaker_next_events -->';

	}

	$wp_query = $backup_query;

	$post = $backup_post;

	wp_reset_postdata();

	return $output;

}



function rsvpmaker_compact( $atts = array() ) {
	global $post;

	global $wp_query;

	global $wpdb;

	global $showbutton;

	global $startday;

	global $rsvp_options;

	if ( isset( $atts['post_id'] ) ) {

		$post_id = (int) $atts['post_id'];

	} elseif ( isset( $atts['one'] ) ) {

		$post_id = $atts['one'];

	} else {
		return;
	}

	$backup_post = $post;

	$backup_query = $wp_query;

	$querystring = 'post_type=rsvpmaker&post_status=publish&posts_per_page=1&p=' . (int) $post_id;

	$wp_query            = new WP_Query( $querystring );
	$wp_query->is_single = false;

	global $rsvp_options;

	ob_start();

	echo '<div class="rsvpmaker_compact">';

	if ( have_posts() ) {

		global $events_displayed;

		while ( have_posts() ) :
			the_post();

			$dateblock = ', ' . rsvpmaker_short_date( $post->ID );

			$dateblock = str_replace( ':00', '', $dateblock );

			?>

<div id="rsvpmaker-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://schema.org/Event" >  

<p class="rsvpmaker-compact-title" itemprop="url"><span itemprop="name">
			<?php
			the_title();
			echo wp_kses_post($dateblock);
			?>
</span></p>

				<?php

				if ( is_rsvpmaker_deadline_future( $post_id ) ) {

					if ( isset( $atts['show_form'] ) ) {

						echo rsvpmaker_form( $atts );

					} else {

						echo get_rsvp_link( $post_id );

					}
				} else {
					esc_html_e( 'Event date is past', 'rsvpmaker' );
				}

	endwhile;

	}

	echo '</div></div><!-- end rsvpmaker_upcoming -->';

	$wp_query = $backup_query;

	$post = $backup_post;

	wp_reset_postdata();

	return ob_get_clean();

}



function rsvpmaker_replay_form( $event_id ) {

	if ( is_rsvpmaker_future( $event_id, 1 ) ) {
		$permalink = get_permalink( $event_id );
		return sprintf( '<a href="%s">%s</a>', $permalink, __( 'Please register' ) );
	}

	// if start time in the future (or within one hour)

	global $post;

	$permalink         = get_permalink( $post->ID );
	$form              = get_post_meta( $event_id, '_rsvp_form', true );
	$captcha           = get_post_meta( $event_id, '_rsvp_captcha', true );
	$rsvp_instructions = get_post_meta( $event_id, '_rsvp_instructions', true );
	ob_start();
	if ( isset( $_GET['err'] ) ) {
		echo '<div style="padding: 10px; margin: 10px; width: 100%; border: medium solid red;">' . htmlentities( $_GET['err'] ) . '</div>';
	}
	?>

<form id="rsvpform" action="<?php echo esc_attr($permalink); ?>" method="post">
<?php rsvpmaker_nonce();?>
<input type="hidden" name="replay_rsvp" value="<?php echo esc_attr($permalink); ?>" />

<h3 id="rsvpnow"><?php echo __( 'Please Register', 'rsvpmaker' ); ?></h3> 

	<?php
	if ( $rsvp_instructions ) {
		echo '<p>' . nl2br( $rsvp_instructions ) . '</p>';}
	?>
	<?php
	basic_form( $form );

	if ( $captcha ) {
		?>
<p><img src="<?php echo plugins_url( '/captcha/captcha_ttf.php', __FILE__ ); ?>" alt="CAPTCHA image">
<br />
		<?php esc_html_e( 'Type the hidden security message', 'rsvpmaker' ); ?>:<br />                    
<input maxlength="10" size="10" name="captcha" type="text" />
</p>

		<?php

		do_action( 'rsvpmaker_after_captcha' );

	}
	global $rsvp_required_field;
	$rsvp_required_field['email'] = 'email';// at a minimum

	if ( function_exists( 'rsvpmaker_recaptcha_output' ) ) {

		rsvpmaker_recaptcha_output();
	}

	echo '<div id="jqerror"></div><input type="hidden" name="required" id="required" value="' . implode( ',', $rsvp_required_field ) . '" />';
	?>
		<p>
		  <input type="submit" id="rsvpsubmit" name="Submit" value="<?php esc_html_e( 'Submit', 'rsvpmaker' ); ?>" /> 
		</p> 
<input type="hidden" name="rsvp_id" id="rsvp_id" value="" /><input type="hidden" id="event" name="event" value="<?php echo esc_attr( $event_id ); ?>" /><input type="hidden" name="landing_id" value="<?php echo esc_attr( $post->ID ); ?>" /><?php rsvpmaker_nonce(); ?>

</form>	

	<?php
	return ob_get_clean();
}

function rsvpmaker_archive_loop_end() {

	global $wp_query;

	global $rsvpwidget;

	global $dataloop;

	if ( ! empty( $dataloop ) ) {

		return;// don't do this for rsvpmaker_upcoming_data
	}

	if ( ! empty( $rsvpwidget ) || empty( $wp_query->query['post_type'] ) ) {

		return;
	}

	if ( is_archive() && ( $wp_query->query['post_type'] == 'rsvpmaker' ) ) {
		?>
<div class="navigation"><p><?php posts_nav_link( ' | ', '&laquo; ' . __( 'Previous Events', 'rsvpmaker' ), __( 'More Events', 'rsvpmaker' ) . ' &raquo;' ); ?></p></div>
		<?php
	}
}

// keep jetpack from messing up

function rsvpmaker_no_related_posts( $options ) {
	global $post;
	if ( ( $post->post_type == 'rsvpmaker' ) || ( $post->post_type == 'rsvpemail' ) ) {
		$options['enabled'] = $options['headline'] = false;
	}
	return $options;
}

add_filter( 'jetpack_relatedposts_filter_options', 'rsvpmaker_no_related_posts' );

function rsvp_report_this_post() {
	global $wpdb;
	global $rsvp_options;
	global $post;
	if ( empty( $post->ID ) ) {
		return;
	}

	$eventid = $post->ID;
	$o       = '<h2>' . __( 'RSVPs', 'rsvpmaker' ) . "</h2>\n";

	$sql = 'SELECT * FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$eventid  ORDER BY yesno DESC, last, first";
	$wpdb->show_errors();
	$results = $wpdb->get_results( $sql, ARRAY_A );
	if ( empty( $results ) ) {
		return $o . '<p>' . __( 'None', 'rsvpmaker' ) . '</p>';
	}
	ob_start();
	format_rsvp_details( $results, false );
	$o .= ob_get_clean();
	return $o;
}

function clear_rsvp_cookies() {
	if ( isset( $_GET['clear'] ) ) {
		if ( isset( $_COOKIE ) ) {
			foreach ( $_COOKIE as $name => $value ) {
				if ( strpos( $name, 'svp_for' ) ) {
					setcookie( $name, 0 );// set to no value
					echo ' clear ' . esc_html( $name );
				}
			}
		}
	}

}

function sked_to_text( $sked ) {

	global $rsvp_options;

	$s = '';

		$weeks = ( empty( $sked['week'] ) ) ? array( 0 ) : $sked['week'];

		$dows = ( empty( $sked['dayofweek'] ) ) ? array() : $sked['dayofweek'];

		$dayarray = array( __( 'Sunday', 'rsvpmaker' ), __( 'Monday', 'rsvpmaker' ), __( 'Tuesday', 'rsvpmaker' ), __( 'Wednesday', 'rsvpmaker' ), __( 'Thursday', 'rsvpmaker' ), __( 'Friday', 'rsvpmaker' ), __( 'Saturday', 'rsvpmaker' ) );

		$weekarray = array( __( 'Varies', 'rsvpmaker' ), __( 'First', 'rsvpmaker' ), __( 'Second', 'rsvpmaker' ), __( 'Third', 'rsvpmaker' ), __( 'Fourth', 'rsvpmaker' ), __( 'Last', 'rsvpmaker' ), __( 'Every', 'rsvpmaker' ) );

	if ( (int) $weeks[0] == 0 ) {

		$s = __( 'Schedule Varies', 'rsvpmaker' );

	} else {

		foreach ( $weeks as $week ) {

			if ( empty( $s ) ) {

				$s = '';

			} else {
				$s .= '/ ';
			}

			$s .= $weekarray[ (int) $week ] . ' ';

		}

		if ( $dows && is_array( $dows ) ) {

			foreach ( $dows as $dow ) {

				$s .= $dayarray[ (int) $dow ] . ' ';
			}
		}
	}

		$t = rsvpmaker_mktime( $sked['hour'], $sked['minutes'], 0, date( 'n' ), date( 'j' ), date( 'Y' ) );

		$dateblock = $s . ' ' . rsvpmaker_timestamp_to_time( $t );

	return $dateblock;

}

function signed_up_ajax( $post_id ) {

	global $wpdb;

	$sql = 'SELECT count(*) FROM ' . $wpdb->prefix . "rsvpmaker WHERE event=$post_id AND yesno=1 ORDER BY id DESC";

	$total = (int) $wpdb->get_var( $sql );

	$rsvp_max = get_post_meta( $post_id, '_rsvp_max', true );

	if ( $total ) {

		$output = $total . ' ' . __( 'signed up so far.', 'rsvpmaker' );

		if ( $rsvp_max ) {

			$output .= ' ' . __( 'Limit', 'rsvpmaker' ) . ': ' . $rsvp_max;
		}

		return $output;

	}

}


function rsvpmaker_exclude_templates_special( $query ) {
	if ( is_admin() || ! $query->is_search() ) {

		return;
	}

	$query->set(
		'meta_query',
		array(

			array(

				'key'     => '_rsvpmaker_special',

				'compare' => 'NOT EXISTS',

			),

			array(

				'key'     => '_sked_template',

				'compare' => 'NOT EXISTS',

			),

			array(

				'key'     => '_sked',

				'compare' => 'NOT EXISTS',

			),
		)
	);
}

add_action( 'pre_get_posts', 'rsvpmaker_exclude_templates_special' );

function rsvpmaker_author_page( $query ) {

	if ( ! is_admin() && ! empty( $query->is_author ) && empty( $query->query_vars['suppress_filters'] ) ) {

		$query->set( 'post_type', array( 'post', 'rsvpmaker' ) );

		$query->set( 'post_parent', 0 );

	}

	return $query;

}

add_filter( 'pre_get_posts', 'rsvpmaker_author_page' );

function get_rsvp_link( $post_id = 0, $justlink = false, $email = '', $rsvp_id = 0 ) {

	global $rsvp_options, $rsvp_link_template;

	if(empty($rsvp_link_template)) {
		$link_template_post = get_option('rsvpmaker_link_template_post');
		if($link_template_post) {
			$tpost = get_post($link_template_post);
			$rsvp_link_template = (empty($tpost->post_content)) ? '' :  $tpost->post_content; 
		}
	}
	if(empty($rsvp_link_template)) {
		$rsvp_link_template = '<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#f71b1b","text":"#ffffff"}},"className":"rsvplink"} -->
		<div class="wp-block-button rsvplink"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#ffffff;background-color:#f71b1b" href="%s">RSVP Now!</a></div>
		<!-- /wp:button --></div>
		<!-- /wp:buttons -->';
		$newpost['post_title'] = 'RSVP Now! button template';	
		$newpost['post_type'] = 'rsvpmaker_form';
		$newpost['post_status'] = 'publish';
		$newpost['post_content'] = $rsvp_link_template;
		$link_template_post = wp_insert_post($newpost);
		update_option('rsvpmaker_link_template_post',$link_template_post);		
	}
	if(empty($post_id))
		return $rsvp_link_template;

	$rsvplink = ($post_id) ? get_permalink( $post_id ) : '%s';

	if(empty($email))
		$rsvplink = add_query_arg( 'e', '*|EMAIL|*', $rsvplink );
	else
		$rsvplink = add_query_arg( 'e', $email, $rsvplink );

	if($rsvp_id)
		$rsvplink = add_query_arg('update',$rsvp_id,$rsvplink);
	$rsvplink .= '#rsvpnow';

	if ( ! is_user_logged_in() && get_post_meta( $post_id, '_rsvp_login_required', true ) ) {

		$rsvplink = wp_login_url( $rsvplink );
	}

	if ( $justlink ) {

		return $rsvplink; // just the link, otherwise return button
	}
	return sprintf( $rsvp_link_template, $rsvplink );
}

function rsvpdateblock( $atts = array() ) {
	global $post;
	$dateblock = rsvpmaker_format_event_dates( $post->ID );
	return '<div class="dateblock">' . $dateblock . "\n</div>\n";
}

function rsvpmaker_format_event_dates( $post_id, $template = false ) {

	global $post, $rsvp_options;

	if ( is_admin() ) {

		return;
	}

	if ( empty( $post_id ) ) {

		$post_id = $post->ID;
	}
	$permalink = get_permalink( $post_id );
	$custom_fields = get_post_custom( $post_id );
	$time_format = $rsvp_options['time_format'];

	$dur = $tzbutton = '';
	if ( ! strpos( $time_format, 'T' ) && isset( $custom_fields['_add_timezone'][0] ) && $custom_fields['_add_timezone'][0] ) {
		$time_format .= ' T';
	}
	$dateblock = '<div class="dateblock">';

	if($template) {
		$sked = get_template_sked( $post->ID );
		$occur = array('Varies','First','Second','Third','Fourth','Last','Every');
		$schedule = '';
		$day = 'tomorrow';
		foreach($sked as $index => $value) {
			if(in_array($index,$occur) && $value)
				$schedule .= ' '.$index;
			if(strpos($index,'day') && $value) {
				if($day == 'tomorrow')
					$day = $index;
				$schedule .= ' '.$index;
			}
		}
		$dateblock .= '<p><em>Template '.$schedule.' displayed using '.$day."'s date</em> <br />" . sprintf( '<a href="%s">%s</a></p>', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $post_id ), __( 'Create/update events from template', 'rsvpmaker' ) );
		$t = rsvpmaker_strtotime( $day.' ' . $sked['hour'] . ':' . $sked['minutes'] );
		if(!empty($sked['end']))
			$endt = rsvpmaker_strtotime( $day.' ' . $sked['end'] );
		else
			$endt = $t + HOUR_IN_SECONDS;
		$eventrow = (object) array('date' => rsvpmaker_date('Y-m-d H:i:s',$t),'enddate' => rsvpmaker_date('Y-m-d H:i:s',$endt));
	}
	else {
		$eventrow = get_rsvpmaker_event( $post_id );
		if(empty($eventrow)){
			return;// don't do for non dated events
		}
		$t = (int) $eventrow->ts_start;			
		$endt = (isset($eventrow->ts_end)) ? (int) $eventrow->ts_end : 0;
	}

		$dateblock .= '<div id="startdate' . esc_attr( $post_id ) . '" itemprop="startDate" datetime="' . date( 'c', $t ) . '">';
		$startdate = rsvpmaker_date( $rsvp_options['long_date'], $t );
		$dateblock .= utf8_encode( $startdate );

		$dur = (isset($eventrow->display_type)) ? $eventrow->display_type : '';

		if ( $dur == 'set' && $endt ) {
			$enddate = rsvpmaker_date( $rsvp_options['long_date'], $endt );
			if($startdate == $enddate) {
				$dateblock .= '<span class="time"> ' . rsvpmaker_timestamp_to_time( $t );
				$dateblock .= ' <span class="end_time"> ' . __( 'to', 'rsvpmaker' ) . ' ' . rsvpmaker_timestamp_to_time( $endt ) . '</span>';	
				$dateblock .= '</span>';	
			}
			else {
				$dateblock .= '<span class="time"> ' . rsvpmaker_timestamp_to_time( $t ) . '</span>';
				$dateblock .= ' <span class="end_time"> <br />' . __( 'to', 'rsvpmaker' ) . ' ' . utf8_encode( $enddate ) . ' ' . rsvpmaker_timestamp_to_time( $endt ) . '</span>';	
			}
		} 
		elseif ( ( $dur != 'allday' ) ) {
			$dateblock .= '<span class="time"> ' . rsvpmaker_timestamp_to_time( $t ) . '</span>';
		}

		$dateblock .= '</div>';// end startdate div

		if ( isset( $custom_fields['_convert_timezone'][0] ) && $custom_fields['_convert_timezone'][0] ) {

			if ( is_email_context() ) {

				$tzbutton = sprintf( ' | <a href="%s">%s</a>', esc_url_raw( add_query_arg( 'tz', $post_id, get_permalink( $post_id ) ) ), __( 'Show in my timezone', 'rsvpmaker' ) );

			} else {

				$atts['time'] = $eventrow->date;

				if ( ! empty( $eventrow->display_type ) ) {

					$atts['end'] = $eventrow->enddate;
				}

				$atts['abbrev'] = rsvpmaker_date('T',$t);

				$tzbutton = rsvpmaker_timezone_converter( $atts );

			}

			$dateblock .= '<div><span id="timezone_converted' . $post_id . '"></span><span id="extra_timezone_converted' . $post_id . '"></span></div>';

		}

		if ( ( ( ! empty( $rsvp_options['calendar_icons'] ) && ! isset( $custom_fields['_calendar_icons'][0] ) ) || ! empty( $custom_fields['_calendar_icons'][0] ) ) ) {

			$j = ( strpos( $permalink, '?' ) ) ? '&' : '?';

			if ( is_email_context() ) {

				$dateblock .= sprintf( '<div class="rsvpcalendar_buttons"> <a href="%s" target="_blank">Google Calendar</a> | <a href="%s">Outlook/iCal</a> %s</div>', rsvpmaker_to_gcal( $post, $eventrow->date, $eventrow->enddate ), $permalink . $j . 'ical=1', $tzbutton );

			} else {
				$dateblock .= sprintf( '<div class="rsvpcalendar_buttons"><a href="%s" target="_blank" title="%s"><img src="%s" border="0" width="25" height="25" /></a>&nbsp;<a href="%s" title="%s"><img src="%s"  border="0" width="28" height="25" /></a> %s</div>', rsvpmaker_to_gcal( $post, $eventrow->date, $eventrow->enddate ), __( 'Add to Google Calendar', 'rsvpmaker' ), plugins_url( 'rsvpmaker/button_gc.gif' ), $permalink . $j . 'ical=1', __( 'Add to Outlook/iCal', 'rsvpmaker' ), plugins_url( 'rsvpmaker/button_ical.gif' ), $tzbutton );
			}
		} elseif ( ! empty( $custom_fields['_convert_timezone'][0] ) ) { // convert button without calendar icons

			$dateblock .= '<div class="rsvpcalendar_buttons">' . $tzbutton . '</div>';
		}

		$dateblock .= '</div>';// end of dateblock div

	return $dateblock;// .'<span class="format_function">test<span></div>';

}

function rsvpmaker_hide_time_posted( $time ) {

	global $post;

	if (( $post->post_type == 'rsvpmaker' ) || ( $post->post_type == 'rsvpmaker_template' )) {

		return '';
	}

	return $time;

}

add_filter( 'the_time', 'rsvpmaker_hide_time_posted', 20 );

function rsvpmaker_get_the_archive_title( $title ) {
	global $post;
	if ( isset($post->post_type) && ($post->post_type == 'rsvpmaker') ) {
		if ( is_single() ) {
			return '';
		} else {
			return __( 'Event Listings', 'rsvpmaker' );
		}
	}
	return $title;
}

add_filter( 'get_the_archive_title', 'rsvpmaker_get_the_archive_title', 20 );

function rsvpmaker_form( $atts = array(), $form_content = '' ) {
	global $post, $showbutton;
	$showbutton = false;
	$output     = '';
	$backup     = $post;
	if ( is_object( $atts ) ) {
		$post = $atts;
	} elseif ( ! empty( $atts['post_id'] ) ) {
		$post = get_post( $atts['post_id'] );
	}
	if ( isset($post->ID) ) {
		$output = event_content( $form_content, true ) . rsvp_form_jquery();
	}
	$post = $backup;
	return $output;
}

function rsvpmaker_daily_schedule( $atts ) {
	global $rsvp_options, $post, $wp_query;
	$backup      = $wp_query;
	$output      = '';
	$last        = '';
	$start_limit = $end_limit = 0;
	if ( isset( $atts['start'] ) ) {
		$start_limit = rsvpmaker_strtotime( $atts['start'] );
	}
	if ( isset( $atts['end'] ) ) {
		$end_limit = rsvpmaker_strtotime( $atts['end'] );
	}
	$atts['limit'] = ( empty( $atts['limit'] ) ) ? 50 : (int) $atts['limit'];

	$wp_query = rsvpmaker_upcoming_query( $atts );

	// $future = $wp_query->get_posts();
	$count = 0;
	while ( have_posts() ) :
		the_post();
		$event       = $post;
		$time_format = $rsvp_options['time_format'];
		if ( ! empty( $atts['convert_tz'] ) && ! strpos( $time_format, 'T' ) ) {
			$time_format .= ' T';
		}
		if ( isset( $_GET['debug'] ) ) {
			printf( '<p>%s %s %s</p>', $event->post_title, $event->datetime, $event->ts_start );
		}

		$t = (int) $event->ts_start;

		if ( $start_limit && ( $t < $start_limit ) ) {
			continue;
		}
		if ( $end_limit && ( $t > $end_limit ) ) {
			continue;
		}
		$terms = get_the_terms( $event->ID, 'rsvpmaker-type' );

		$wrapclass = '';

		$termslugs = array();

		$term_links = array();

		if ( $terms ) {

			foreach ( $terms as $term ) {

				$wrapclass .= ' ' . $term->slug;

				$termslugs[] = $term->slug;

				$term_links[] = '<a href="' . esc_attr( get_term_link( $term->slug, 'rsvpmaker-type' ) ) . '">' . __( $term->name ) . '</a>';
			}
		}

		$termline = '<p class="daily-schedule-event-types">'. wp_kses_post( implode( ', ', $term_links ) ) . '</p>';

		if ( isset( $atts['type'] ) && ! in_array( $atts['type'], $termslugs ) ) {

			continue;
		}

		$day = rsvpmaker_date( $rsvp_options['long_date'], (int) $event->ts_start );

		if ( $day != $last ) {

			$output .= sprintf( '<h3 class="rsvpmaker_schedule_date">%s</h3>', $day );
		}

		$last = $day;

		if ( $event->display_type == 'set' ) {

			$end = ' - <span class="tz-convert">' . rsvpmaker_date( $time_format, (int) $event->ts_end ) . '</span>';

		} else {
			$end = '';
		}

		$eventcontent = '<h3 class="rsvpmaker-schedule-headline"><span class="rsvpmaker_schedule_time tz-convert">' . rsvpmaker_date( $time_format, $t ) . '</span>' .  $end;

		$eventcontent .= ' <span class="rsvpmaker-schedule-title"><a href="' . get_permalink( $event->ID ) . '">' . esc_html( $event->post_title ) . '</a></span></h3>';

		if ( ! empty( $atts['convert_tz'] ) ) {

			$atts['time'] = $event->datetime;

			if ( ! empty( $event->display_type ) ) {

				$atts['end'] = $event->enddate;
			}
			if($count)
				$atts['nofluxbutton'] = 1;
			$count++;

			$eventcontent .= rsvpmaker_timezone_converter( $atts );

		}

		$eventcontent .= get_the_content('Read More',false,$event);

		$output .= '<div class="rsvpmaker-schedule-item' . $wrapclass . '">' . "\n" . $eventcontent . $termline . "\n" . '</div>';

	endwhile;

		// }

	$output = '<div class="rsvpmaker-schedule">' . "\n" . $output . "\n</div>";

	$wp_query = $backup;

	return $output;

}



function embed_dateblock( $atts ) {

	$d = rsvp_date_block( $atts['post_id'], get_post_custom( $atts['post_id'] ) );

	return $d['dateblock'];

}

function rsvp_date_block_email( $post_id ) {

	global $rsvp_options;
	global $last_time;
	global $post;
	$post = get_post($post_id);

		$custom_fields = get_post_custom( $post_id );
	$time_format = ' '.$rsvp_options['time_format'];
	$dur = $tzbutton = '';
	$firstrow = array();

	if ( ! strpos( $time_format, 'T' ) && isset( $custom_fields['_add_timezone'][0] ) && $custom_fields['_add_timezone'][0] ) {
		$time_format .= ' T';
	}

	$permalink = get_permalink( $post_id );
	$event = get_rsvpmaker_event($post_id);
	$dateblock = '<!-- wp:paragraph -->'."\n<p><strong>";
	$t = $event->ts_start;
	$dateblock .= utf8_encode( rsvpmaker_date( $rsvp_options['long_date'], $t ) );

			if ( $event->display_type == 'set' ) {
				$tzcode = strpos( $time_format, 'T' );

				if ( $tzcode ) {

					$time_format = str_replace( 'T', '', $time_format );
				}

				$dateblock .= rsvpmaker_date( $time_format, $event->ts_start );

				$dateblock .= ' ' . __( 'to', 'rsvpmaker' ) . ' ' . rsvpmaker_date( $time_format, $event->ts_end );

				if ( $tzcode ) {

					$dateblock .= ' ' . rsvpmaker_date( 'T', $t );
				}

			} elseif ( ( $event->display_type != 'allday' ) && ! strpos( $event->display_type, '|' ) ) {
				$dateblock .= rsvpmaker_date( ' ' . $time_format, $t );
			}
			$dateblock .= '</strong></p>'."\n<!-- /wp:paragraph -->\n\n";
			$tzbutton = sprintf( ' | <a href="%s">%s</a>', esc_url( add_query_arg( 'tz', $post_id, get_permalink( $post_id ) ) ), __( 'Show in my timezone', 'rsvpmaker' ) );
			$dateblock .= "<!-- wp:paragraph -->\n<p>";
			$end_time = $event->enddate;
			$j = ( strpos( $permalink, '?' ) ) ? '&amp;' : '?';
			$dateblock .= sprintf( '<a href="%s" target="_blank">Google Calendar</a> | <a href="%s">Outlook/iCal</a> %s', rsvpmaker_to_gcal( $post, $event->date, $event->enddate ), $permalink . $j . 'ical=1', $tzbutton );
			$dateblock .= '</p>'."\n<!-- /wp:paragraph -->\n\n";
		return $dateblock;
}


function rsvp_date_block( $post_id, $custom_fields = array(), $top = true ) {

	global $rsvp_options;

	global $last_time;

	global $post;

	if ( is_admin() ) {

		return;
	}

	if ( empty( $post_id ) ) {
		$post_id = $post->ID;
	}

	$time_format = ' '.$rsvp_options['time_format'];

	$dur = $tzbutton = '';

	$firstrow = array();

	if ( ! strpos( $time_format, 'T' ) && isset( $custom_fields['_add_timezone'][0] ) && $custom_fields['_add_timezone'][0] ) {
		$time_format .= ' T';
	}

	$permalink = get_permalink( $post_id );

	if ( rsvpmaker_is_template( $post->ID ) ) {

		$sked = get_template_sked( $post->ID );

		// backward compatability

		if ( is_array( $sked['week'] ) ) {

				$weeks = $sked['week'];

				$dows = $sked['dayofweek'];

		} else {

				$weeks = array();

				$dows = array();

				$weeks[0] = $sked['week'];

				$dows[0] = ( empty( $sked['dayofweek'] ) ) ? 0 : $sked['dayofweek'];

		}

		$dayarray = array( __( 'Sunday', 'rsvpmaker' ), __( 'Monday', 'rsvpmaker' ), __( 'Tuesday', 'rsvpmaker' ), __( 'Wednesday', 'rsvpmaker' ), __( 'Thursday', 'rsvpmaker' ), __( 'Friday', 'rsvpmaker' ), __( 'Saturday', 'rsvpmaker' ) );

		$weekarray = array( __( 'Varies', 'rsvpmaker' ), __( 'First', 'rsvpmaker' ), __( 'Second', 'rsvpmaker' ), __( 'Third', 'rsvpmaker' ), __( 'Fourth', 'rsvpmaker' ), __( 'Last', 'rsvpmaker' ), __( 'Every', 'rsvpmaker' ) );

		if ( (int) $weeks[0] == 0 ) {

			$s = __( 'Schedule Varies', 'rsvpmaker' );

		} else {

			foreach ( $weeks as $week ) {

				if ( empty( $s ) ) {

					$s = '';

				} else {
					$s .= '/ ';
				}

				$s .= $weekarray[ (int) $week ] . ' ';

			}

			foreach ( $dows as $dow ) {

				$s .= $dayarray[ (int) $dow ] . ' ';
			}
		}

		$t = rsvpmaker_mktime( $sked['hour'], $sked['minutes'], 0, date( 'n' ), date( 'j' ), date( 'Y' ) );

		$dateblock = $s . ' ' . rsvpmaker_date( $rsvp_options['time_format'], $t );

		$dateblock .= '<div id="startdate' . $post_id . '" itemprop="startDate" datetime="' . date( 'c', $t ) . '"></div>';

		if ( current_user_can( 'edit_rsvpmakers' ) ) {

			$dateblock .= sprintf( '<br /><a href="%s">%s</a>', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list&t=' . $post_id ), __( 'Create/update events from template', 'rsvpmaker' ) );
		}
	}
	elseif ( $event = get_rsvpmaker_event($post_id) ) {
		$dateblock = '<span class="rsvp_date_block"></span>';
		
		global $last_time;

			$last_time = $t = $event->ts_start;

			$dateblock .= '<div id="startdate' . esc_attr( $post_id ) . '" itemprop="startDate" datetime="' . date( 'c', $t ) . '">';

			$dateblock .= utf8_encode( rsvpmaker_date( $rsvp_options['long_date'], $t ) );

			if ( $event->display_type == 'set' ) {
				$tzcode = strpos( $time_format, 'T' );

				if ( $tzcode ) {
					$time_format = str_replace( 'T', '', $time_format );
				}

				$dateblock .= '<span class="time">' . rsvpmaker_date( $time_format, $event->ts_start, $event->timezone );

				$dateblock .= ' <span class="end_time">' . __( 'to', 'rsvpmaker' ) . ' ' . rsvpmaker_date( $time_format, $event->ts_end, $event->timezone ) . '</span>';

				if ( $tzcode ) {
					$dateblock .= ' ' . rsvpmaker_date( 'T', $t, $event->timezone );
				}

				$dateblock .= '</span>';

			} elseif ( ( $event->display_type != 'allday' ) && ! strpos( $event->display_type, '|' ) ) {

				$dateblock .= '<span class="time">' . rsvpmaker_date( ' ' . $time_format, $t ) . '</span>';

			}

			if ( $top && isset( $custom_fields['_convert_timezone'][0] ) && $custom_fields['_convert_timezone'][0] ) {

				if ( is_email_context() ) {
					$tzbutton = sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'tz', $post_id, get_permalink( $post_id ) ) ), __( 'Show in my timezone', 'rsvpmaker' ) );
				} else {
					$atts['time'] = $event->date;

					if ( $event->display_type == 'set' ) {
						$atts['end'] = $event->enddate;
					}

					$tzbutton = rsvpmaker_timezone_converter( $atts );
				}
			}
			$dateblock .= '<div><span id="timezone_converted' . esc_attr( $post_id ) . '"></span><span id="extra_timezone_converted' . esc_attr( $post_id ) . '"></span></div></div>';
		

		// gcal link

		if ( ( ( ! empty( $rsvp_options['calendar_icons'] ) && ! isset( $custom_fields['_calendar_icons'][0] ) ) || ! empty( $custom_fields['_calendar_icons'][0] ) ) ) {
			$end_time = $event->enddate;
			$j = ( strpos( $permalink, '?' ) ) ? '&' : '?';
			if ( is_email_context() ) {
				$dateblock .= sprintf( '<div class="rsvpcalendar_buttons"> <a href="%s" target="_blank">Google Calendar</a> | <a href="%s">Outlook/iCal</a> | %s</div>', rsvpmaker_to_gcal( $post, $event->date, $event->enddate ), $permalink . $j . 'ical=1', $tzbutton );
			} else {
				$dateblock .= sprintf( '<div class="rsvpcalendar_buttons"><a href="%s" target="_blank" title="%s"><img src="%s" border="0" width="25" height="25" /></a>&nbsp;<a href="%s" title="%s"><img src="%s"  border="0" width="28" height="25" /></a> %s</div>', rsvpmaker_to_gcal( $post, $event->date, $event->enddate ), __( 'Add to Google Calendar', 'rsvpmaker' ), plugins_url( 'rsvpmaker/button_gc.gif' ), $permalink . $j . 'ical=1', __( 'Add to Outlook/iCal', 'rsvpmaker' ), plugins_url( 'rsvpmaker/button_ical.gif' ), $tzbutton );
			}
		} elseif ( ! empty( $custom_fields['_convert_timezone'][0] ) ) { // convert button without calendar icons
			$dateblock .= '<div class="rsvpcalendar_buttons">' . $tzbutton . '</div>';
		}
	}  else // no dates, no sked, maybe this is an agenda or a landing page

	{

		return array(
			'dateblock' => '',
			'dur'       => null,
			'last_time' => null,
		);

	}

	return array(
		'dateblock' => $dateblock,
		'dur'       => $event->display_type,
		'last_time' => $last_time,
		'firstrow'  => $firstrow,
	);

}



function future_rsvp_links( $atts = array() ) {

	global $rsvp_options;

	$output = '<ul>';

	$limit = ( empty( $atts['limit'] ) ) ? 5 : (int) $atts['limit'];

	$events = get_events_rsvp_on( $limit );

	if ( empty( $events ) ) {

		return;
	}

	foreach ( $events as $index => $event ) {

		if ( ( $index == 0 ) && ! empty( $atts['skipfirst'] ) ) {

			continue;
		}

		$url = get_permalink( $event->ID ) . '#rsvpnow';

		$t = rsvpmaker_strtotime( $event->datetime );

		$datetime = rsvpmaker_date( '', $t ) . ' ' . rsvpmaker_date( $rsvp_options['time_format'], $t );

		$output .= sprintf( '<li><a href="%s">%s</a></li>', esc_url_raw( $url ), esc_html( $event->post_title . ' ' . $datetime ) );

		$event->post_content = '';

		// $output .= '<li>'.var_export($event,true).'</li>';

	}

	$output .= '</ul>';

	return $output;
}

add_shortcode( 'future_rsvp_links', 'future_rsvp_links' );
add_action( 'wp_footer', 'rsvpmaker_timezone_footer' );

function rsvpmaker_timezone_converter( $atts ) {
	global $post;
	$post_id = ( empty( $post->ID ) ) ? 0 : $post->ID;
	if ( ! isset( $atts['time'] ) ) {
		return;
	}
	$abbrev = (isset($atts['abbrev'])) ? $atts['abbrev'] : '';
	$server_timezone = rsvpmaker_get_timezone_string();
	$time            = $atts['time'];
	$id              = 'convert' . strtotime( $time ) . rand();
	$end             = ( isset( $atts['end'] ) ) ? $atts['end'] : '';
	$format          = ( isset( $atts['format'] ) ) ? $atts['format'] : '';
	$nofluxbutton = (isset($atts['nofluxbutton'])) ? ' nofluxbutton="1" ' : '';
	return sprintf( '<div class="tz_converter" id="%s" time="%s" end="%s" format="%s" server_timezone="%s" post_id="%d" timezone_abbrev="%s" %s></div>', esc_attr( $id ), esc_attr( $time ), esc_attr( $end ), esc_attr( $format ), esc_attr( $server_timezone ), esc_attr( $post_id ), esc_attr($abbrev), $nofluxbutton );
}

add_shortcode( 'timezone_converter', 'rsvpmaker_timezone_converter' );

function rsvpmaker_404_message ($args) {
	global $wp_query;
	if(isset($wp_query->query['rsvpmaker-type'])) {
		echo '<p><strong>'.__('For an event category lookup, this may simply mean there is no currently scheduled event in this category','rsvpmaker').'</strong></p>';
	}
}

add_action('pre_get_search_form','rsvpmaker_404_message');

?>
