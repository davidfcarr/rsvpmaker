<?php

/*
RSVPMaker Widgets

*/



/**

 * CPEventsWidget Class
 */

class CPEventsWidget extends WP_Widget {

	function __construct() {

		parent::__construct( false, $name = 'RSVPMaker Events' );

	}

	function widget( $args, $instance ) {

		extract( $args );

		global $rsvpwidget;

		$rsvpwidget = true;

		$title = ( isset( $instance['title'] ) ) ? sanitize_text_field( $instance['title'] ) : __( 'Events', 'rsvpmaker' );

		$title = apply_filters( 'widget_title', $title );

		$atts['limit'] = ( isset( $instance['limit'] ) ) ? (int) $instance['limit'] : 10;

		if ( ! empty( $instance['event_type'] ) ) {

			$atts['type'] = ( isset( $instance['event_type'] ) ) ? sanitize_text_field( $instance['event_type'] ) : null;
		}

		$dateformat = ( isset( $instance['dateformat'] ) ) ? sanitize_text_field( $instance['dateformat'] ) : 'M. j';

		$break = empty( $instance['break'] ) ? ' - ' : '<br />';

		global $rsvp_options;?>

			  <?php echo $before_widget; ?>

				  <?php
					if ( $title ) {

						echo $before_title . esc_html( $title ) . $after_title;}
					?>

			  <?php

				$events = rsvpmaker_upcoming_data( $atts );

				if ( ! empty( $events ) ) {

					echo "\n<ul>\n";

					foreach ( $events as $event ) {
						$date = rsvpmaker_date( $dateformat, rsvpmaker_strtotime( $event->datetime ) );
						printf( '<li class="rsvpmaker-widget-li"><span class="rsvpmaker-widget-title"><a href="%s">%s</a></span>%s<span class="rsvpmaker-widget-date">%s</span></li>', get_permalink( $event->ID ), esc_html( $event->post_title ), $break, esc_html( $date ) );
					}

					if ( ! empty( $rsvp_options['eventpage'] ) ) {

						echo '<li><a href="' . esc_attr( $rsvp_options['eventpage'] ) . '">' . __( 'Go to Events Page', 'rsvpmaker' ) . '</a></li>';
					}

					echo "\n</ul>\n";

				}

				echo $after_widget;
				?>

		<?php

		$rsvpwidget = false;

	}



	/** @see WP_Widget::update */

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( strip_tags( $new_instance['title'] ) );

		$instance['dateformat'] = sanitize_text_field( strip_tags( $new_instance['dateformat'] ) );

		$instance['limit'] = (int) $new_instance['limit'];

		$instance['event_type'] = sanitize_text_field( $new_instance['event_type'] );

		$instance['break'] = wp_kses_post( $new_instance['break'] );

		return $instance;

	}

	/** @see WP_Widget::form */

	function form( $instance ) {

		$title = ( isset( $instance['title'] ) ) ? esc_attr( $instance['title'] ) : __( 'Events', 'rsvpmaker' );

		$limit = ( isset( $instance['limit'] ) ) ? $instance['limit'] : 10;

		$dateformat = ( isset( $instance['dateformat'] ) ) ? $instance['dateformat'] : 'M. j';

		$event_type = ( ! empty( $instance['event_type'] ) ) ? $instance['event_type'] : '';

		$break = ! empty( $instance['break'] );

		?>

			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php esc_html_e( 'Number to Show:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id( 'dateformat' ); ?>"><?php esc_html_e( 'Date Format:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'dateformat' ); ?>" name="<?php echo $this->get_field_name( 'dateformat' ); ?>" type="text" value="<?php echo esc_attr( $dateformat ); ?>" /></label> (PHP <a target="_blank" href="http://us2.php.net/manual/en/function.date.php">date</a> format string)</p>
			
			<p><label for="<?php echo $this->get_field_id( 'break' ); ?>"><?php esc_html_e( 'Date on Separate Line:', 'rsvpmaker' ); ?> <select id="<?php echo $this->get_field_id( 'break' ); ?>" name="<?php echo $this->get_field_name( 'break' ); ?>" ><option value="0" ><?php esc_html_e( 'No', 'rsvpmaker' ); ?></option><option value="1" 
									  <?php
										if ( $break ) {
											echo 'selected="selected"';}
										?>
				 ><?php esc_html_e( 'Yes', 'rsvpmaker' ); ?></option> </select></label></p>

<p><label for="<?php echo $this->get_field_id( 'event_type' ); ?>"><?php esc_html_e( 'Event Type:', 'rsvpmaker' ); ?>

		<?php

		$tax_terms = get_terms( 'rsvpmaker-type' );

		?>

<select class="widefat" id="<?php echo $this->get_field_id( 'event_type' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'event_type' ) ); ?>" ><option value=""><?php esc_html_e( 'All', 'rsvpmaker' ); ?></option>

		<?php

		if ( is_array( $tax_terms ) ) {

			foreach ( $tax_terms as $tax_term ) {

				$s = ( $tax_term->name == $event_type ) ? ' selected="selected" ' : '';

				echo '<option value="' . esc_attr( $tax_term->name ) . '" ' . $s . '>' . esc_html( $tax_term->name ) . '</option>';

			}
		}

		?>

</select>

</p>
<p>CSS classes:
<br />rsvpmaker-widget-li event
<br />rsvpmaker-widget-title event post title
<br />rsvpmaker-widget-date event date
</p>

		<?php

	}



} // class CPEventsWidget



/**

 * RSVPTypeWidget Class
 */

class RSVPTypeWidget extends WP_Widget {

	/** constructor */

	function __construct() {

		parent::__construct( 'rsvpmaker_type_widget', $name = 'RSVPMaker Events by Type' );

	}

	/** @see WP_Widget::widget */

	function widget( $args, $instance ) {

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( empty( $title ) ) {

			$title = __( 'Events by Type', 'rsvpmaker' );
		}

		$atts['limit'] = ( $instance['limit'] ) ? (int) $instance['limit'] : 10;

		if ( ! empty( $instance['event_type'] ) ) {

			global $rsvp_options;
		}
		?>

			  <?php echo $before_widget; ?>

				  <?php
					if ( $title ) {

						echo $before_title . esc_html( $title ) . $after_title;}
					?>

			  <?php

				$args = array( 'hide_empty=0' );

				$terms = get_terms( 'rsvpmaker-type', $args );

				echo '<ul>';

				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

					$count = count( $terms );

					$i = 0;

					foreach ( $terms as $term ) {

						$i++;

						$atts['type'] = $term->name;

						$events = rsvpmaker_upcoming_data( $atts );

						$count = sizeof( $events );

						$countstr = ( $count ) ? '(' . $count . ')' : '';

						printf( '<li><a href="%s">%s</a> %s</li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ), $countstr );

					}
				}

				echo "\n</ul>\n";

				echo $after_widget;
				?>

		<?php

	}



	/** @see WP_Widget::update */

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( strip_tags( $new_instance['title'] ) );

		$instance['limit'] = (int) $new_instance['limit'];

		return $instance;

	}



	/** @see WP_Widget::form */

	function form( $instance ) {

		$title = ( isset( $instance['title'] ) ) ? esc_attr( $instance['title'] ) : '';

		$limit = ( isset( $instance['limit'] ) ) ? (int) $instance['limit'] : 10;

		?>

			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php esc_html_e( 'Number to Show:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" /></label></p>

		<?php

	}



}

class RSVPMakerByJSON extends WP_Widget {

	/** constructor */

	function __construct() {

		parent::__construct( 'rsvpmaker_by_json', $name = 'RSVPMaker Events (API)' );

	}



	/** @see WP_Widget::widget */

	function widget( $args, $instance ) {

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( empty( $title ) ) {

			$title = __( 'Events', 'rsvpmaker' );
		}

		$slug = strtolower( preg_replace( '/[^a-zA-Z0-9]/', '', $title ) );

		$url = ( $instance['url'] ) ? $instance['url'] : site_url( '/wp-json/rsvpmaker/v1/future' );

		$limit = ( $instance['limit'] ) ? $instance['limit'] : 0;

		$morelink = ( $instance['morelink'] ) ? $instance['morelink'] : '';

		global $rsvp_options;
		?>

			  <?php echo $before_widget; ?>

				  <?php
					if ( $title ) {

						echo $before_title . esc_html( $title ) . $after_title;}
					?>

<div id="rsvpjsonwidget-<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Loading', 'rsvpmaker' ); ?> ...</div>

<script>
jQuery(document).ready(function($) {

var jsonwidget<?php echo esc_attr( $slug ); ?> = new RSVPJsonWidget('rsvpjsonwidget-<?php echo esc_attr( $slug ); ?>','<?php echo esc_attr( $url ); ?>',<?php echo esc_attr( $limit ); ?>,'<?php echo esc_attr( $morelink ); ?>');

});
</script>

		<?php

		echo $after_widget;
		?>

		<?php

	}



	/** @see WP_Widget::update */

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( strip_tags( $new_instance['title'] ) );

		$instance['url'] = sanitize_text_field( trim( $new_instance['url'] ) );

		$instance['limit'] = (int) $new_instance['limit'];

		$instance['morelink'] = sanitize_text_field( trim( $new_instance['morelink'] ) );

		return $instance;

	}



	/** @see WP_Widget::form */

	function form( $instance ) {

		$title = ( isset( $instance['title'] ) ) ? esc_attr( $instance['title'] ) : '';

		if ( function_exists( 'rsvpmaker_upcoming' ) ) {

			$url = ( isset( $instance['url'] ) ) ? $instance['url'] : site_url( '/wp-json/rsvpmaker/v1/future' );

		} else {
			$url = ( isset( $instance['url'] ) ) ? $instance['url'] : 'rsvpmaker.com/wp-json/rsvpmaker/v1/future';
		}

		$limit = ( isset( $instance['limit'] ) ) ? $instance['limit'] : 10;

		$morelink = ( isset( $instance['morelink'] ) ) ? $instance['morelink'] : '';

		?>

			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p><label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php esc_html_e( 'JSON URL:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>" /></label>

			<br />Examples from rsvpmaker.com demo:

			<br /><a target="_blank" href="https://rsvpmaker.com/wp-json/rsvpmaker/v1/future">all future events</a>

			<br /><a target="_blank" href="https://rsvpmaker.com/wp-json/rsvpmaker/v1/type/featured">events tagged type/featured</a></p>

		  <p><label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Maximum # Displayed:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" /></label><br /><em>Use 0 for no limit</em></p>

			<p><label for="<?php echo esc_attr( $this->get_field_id( 'morelink' ) ); ?>"><?php esc_html_e( 'URL for more events:', 'rsvpmaker' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'morelink' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'morelink' ) ); ?>" type="text" value="<?php echo esc_attr( $morelink ); ?>" /></label></p>
		<?php
	}
}

?>
