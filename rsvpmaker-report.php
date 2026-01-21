<?php

function rsvp_report() {

if(isset($_GET['unpaid_upcoming']) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))) {
	rsvpmaker_admin_heading('RSVP Report: Unpaid',__FUNCTION__);
	printf('<p><a href="%s">%s</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report'),__('Back to Events List','rsvpmaker'));
	rsvp_report_unpaid();
	return;
}

		global $wpdb, $post, $rsvp_options, $is_rsvp_report;
		$is_rsvp_report = true;
		$daily_count = [];

		$wpdb->show_errors();

		if ( isset( $_GET['event'] ) ) {
			$post = get_post( (int) $_GET['event'] );
		}

		$guest_check = '';

		if(!empty($_POST))
		$postdata = $_POST;

		?>

<div class="wrap"> 

	<div id="icon-edit" class="icon32"><br /></div>
		<?php

		if(isset($_GET['rsvpsearch'])) {
			$search = sanitize_text_field($_GET['rsvpsearch']);
			$date_limit = (isset($_GET['datelimit'])) ? 'AND events.date > CURDATE()' : '';			
			$results = $wpdb->get_results($wpdb->prepare("select * from %i rsvpmaker JOIN %i events ON rsvpmaker.event=events.event WHERE (email LIKE %s OR first LIKE %s OR last LIKE %s) AND master_rsvp='0' ".$date_limit." ORDER BY events.ts_start"
		,$wpdb->prefix.'rsvpmaker',$wpdb->prefix.'rsvpmaker_event','%'.$wpdb->esc_like($search).'%','%'.$wpdb->esc_like($search).'%','%'.$wpdb->esc_like($search).'%'));
		if(!empty($results))
			printf('<h3>%s</h3>',esc_html__('Search Results'));
			foreach($results as $row) {
				printf('<p>%s %s %s<br /><a href="%s">%s: %s</a></p>',$row->first,$row->last,$row->email,admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&event='.$row->event.'#rsvprow'.$row->id),$row->post_title,rsvpmaker_date($rsvp_options['long_date'],$row->ts_start));
			}
		}

		$param = ( empty( $_GET['limit'] ) ) ? '' : sanitize_text_field($_GET['limit']) . ' ' . sanitize_text_field($_GET['detail']);

		if ( sizeof( $_GET ) > 2 ) {
			$title = sprintf( '<a href="%s">%s</a> - %s %s', admin_url( 'edit.php?post_type=rsvpmaker&page=rsvp_report' ), __( 'RSVP Report', 'rsvpmaker' ), __( 'Details', 'rsvpmaker' ), $param );
		} else {
			$title = __( 'RSVP Report', 'rsvpmaker' );
		}
		if(!isset($_GET['rsvp_print']))
		rsvpmaker_admin_heading($title,__FUNCTION__);

		if ( isset( $postdata['move_rsvp'] ) && isset( $postdata['move_to'] ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  ) {

			if ( empty( $postdata['move_rsvp'] ) ) {

				printf( '<div class="notice notice-error"><p>%s</p></option>', __( 'No RSVP entry selected', 'rsvpmaker' ) );

			} else {
				$move_rsvp = (int) $postdata['move_rsvp'];
				$move_to   = (int) $postdata['move_to'];
				$moved_post = get_post($move_to);
				$moved_event = get_rsvpmaker_event($move_to);
				$count = $wpdb->query( $wpdb->prepare("UPDATE %i SET event=%d WHERE id=%d ",$wpdb->prefix . "rsvpmaker",$move_to,$move_rsvp) );
				printf('<div class="notice notice-info"><p>%d entries moved, see <a href="%s">%s %s</a></p></div>',$count,admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&event='.$move_to),$moved_post->post_title,rsvpmaker_date($rsvp_options['long_date'],$moved_event->ts_start));
			}
		}

		if ( ! empty( $_GET['fields'] ) ) {
			rsvp_report_table();

			echo '</div>';

			return;

		}

		if ( isset( $postdata['deletenow'] ) && current_user_can( 'edit_others_posts' ) && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key'))  ) {

			if (  ! wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

				die( 'failed security check' );
			}

			foreach ( $postdata['deletenow'] as $d ) {

				$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "rsvpmaker where id=".intval($d) );
			}
		}

		if ( isset( $_GET['delete'] ) && current_user_can( 'edit_others_posts' ) ) {

			$delete = intval($_GET['delete']);

			$row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM %i WHERE id=%d",$wpdb->prefix . "rsvpmaker",$delete) );

			$guests = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i WHERE master_rsvp=%d",$wpdb->prefix . "rsvpmaker",$delete) );

			if ( is_array( $guests ) ) {

				foreach ( $guests as $guest ) {

					$guestcheck .= sprintf( '<input type="checkbox" name="deletenow[]" value="%s" checked="checked" /> Delete guest: %s %s<br />', $guest->id, esc_html( $guest->first ), esc_html( $guest->last ) );
				}
			}

			echo sprintf(
				'<form action="%s" method="post">

<h2 style="color: red;">' . __( 'Confirm ' ) . ' %s %s</h2>

<input type="hidden" name="deletenow[]" value="%s"  />

%s

%s

<input type="submit" style="color: red;" value="' . __( 'Delete Now', 'rsvpmaker' ) . '"  />

</form>

',
				admin_url() . 'edit.php?post_type=rsvpmaker&page=rsvp_report',
				esc_html( $row->first ),
				esc_html( $row->last ),
				$delete,
				$guestcheck,
				rsvpmaker_nonce('return')
			);

		}

		if(isset($_GET['allcontacts'])) {
			$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM  %i WHERE master_rsvp=0 ORDER BY timestamp desc",$wpdb->prefix."rsvpmaker"),ARRAY_A);
			format_rsvp_details($results);
			echo '</div>';
			return;
		}

		if ( isset( $_GET['event'] ) ) {

			$eventid   = (int) $_GET['event'];
			$permalink = get_permalink( $eventid );
			$pricing = get_post_meta($eventid,'pricing',true);

			if(isset($_GET['show_unpaid'])) {
				echo '<h2>Looking up unpaid records</h2>';
				$matched = '';
				$unmatched = '';
				$unpaid = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE post_id=%d AND meta_key='rsvpmaker_unpaid' ",$wpdb->postmeta,$eventid));
				foreach($unpaid as $unp) {
					$unpaid_record = unserialize($unp->meta_value);
					$restore = admin_url("edit.php?post_type=rsvpmaker&page=rsvp_report&event=$eventid&restore_unpaid=$unp->meta_id");

					$match = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE event=%d AND email=%s", $wpdb->prefix."rsvpmaker",$eventid,$unpaid_record[0]->email ));
					$names = [];
					if($match) {
						$names[] = $match->first.' '.$match->last;
						$matchguests = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE event=%d AND master_rsvp=%d".$wpdb->prefix."rsvpmaker",$eventid,$match->id));
						foreach($matchguests as $matchg)
							$names[] = $matchg->first.' '.$matchg->last;
						$matched .= sprintf('<p><a href="%s">Restore #%d</a> %s %s %s %s %s party of %d</p>',$restore,$unpaid_record[0]->id,$unpaid_record[0]->timestamp,$unpaid_record[0]->first,$unpaid_record[0]->last,$unpaid_record[0]->email,$unpaid_record[0]->owed,sizeof($unpaid_record));
						$matched .= sprintf('<p><a href="#rsvprow%d">Possible match</a>, party of %d, %s</p>',$match->id,sizeof($names),implode(', ',$names));
					}
					else
						$unmatched .= sprintf('<p><a href="%s">Restore #%d</a> %s %s %s %s %s party of %d</p>',$restore,$unpaid_record[0]->id,$unpaid_record[0]->timestamp,$unpaid_record[0]->first,$unpaid_record[0]->last,$unpaid_record[0]->email,$unpaid_record[0]->owed,sizeof($unpaid_record));
				}
				if($unmatched)
					echo '<h3>Unpaid</h3>'.$unmatched;
				if($matched)
					echo '<h3>May have subsequently paid or partially paid</h3>'.$matched;

			}

			if(isset($_GET['restore_unpaid'])) {
				$restore_id = intval($_GET['restore_unpaid']);
				$unpaid = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE meta_id=%d ",$wpdb->postmeta,$restore_id));
				if($unpaid) {
					echo '<h2>Restoring unpaid record</h2>';
					$unpaid_record = unserialize($unpaid->meta_value);
					foreach($unpaid_record as $restore) {
						printf('<p>%s %s</p>',$restore->first,$restore->last);
						$restore = (array) $restore;
						$wpdb->replace($wpdb->prefix.'rsvpmaker',$restore);
					}
					$wpdb->query($wpdb->prepare("UPDATE %i SET meta_key='rsvpmaker_restored' WHERE meta_id=%d ",$wpdb->postmeta,$restore_id));	
				}
			}

			$event_row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM %i WHERE event=%d",$wpdb->prefix."rsvpmaker_event",$eventid) );

			$date = $event_row->date;

			$t = rsvpmaker_strtotime( $date );

			$title = '<a target="_blank" href="' . $permalink . '">' . esc_html( $event_row->post_title ) . ' ' . rsvpmaker_date( $rsvp_options['long_date'], $t ) . '</a>';

			if(!$_GET['event']) {
				echo '<h2>' . __( 'Contact Form Entries', 'rsvpmaker' ). "</h2>\n";
			}
			else {
				echo '<h2>' . __( 'RSVPs for', 'rsvpmaker' ) . ' ' . $title . "</h2>\n";
				if(!empty($rsvp_options['cancel_unpaid_hours']) && ! isset( $_GET['rsvp_print'] ))
					printf('<p><a href="%s">%s</a></p>',admin_url("edit.php?post_type=rsvpmaker&page=rsvp_report&event=$eventid&show_unpaid=1"),__('Show Unpaid','rsvpmaker'));
			}

			if ( ! isset( $_GET['rsvp_print'] ) ) {

				echo '<div style="float: right; margin-left: 15px; margin-bottom: 15px;"><a href="edit.php?post_type=rsvpmaker&page=rsvp_report">' . __( 'Show Events List', 'rsvpmaker' ) . '</a> |

<a href="edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . $eventid . '&rsvp_order=alpha">Alpha Order</a> | 
<a href="edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . $eventid . '&rsvp_order=timestamp">Most Recent First</a> | 
<a href="edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . $eventid . '&rsvp_order=host">Sort By Host/Party</a>

		</div>';

				echo '<p><a href="' . sanitize_text_field($_SERVER['REQUEST_URI']) . '&print_rsvp_report=1&rsvp_print=1&' . rsvpmaker_nonce('query') . '" target="_blank" >Format for printing</a></p>';

				echo '<p><a href="edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . $eventid . '&paypal_log=1">Show PayPal Log</a></p>';

				if ( isset( $phpexcel_enabled ) ) {

					echo '<p><a href="#excel">Download to Excel</a></p>';
				}
			}

			if ( ! empty( $_GET['paypal_log'] ) ) {

				$log = get_post_meta( $eventid, '_paypal_log' );

				if ( $log ) {

					echo '<div style="border: thin solid red; padding: 5px;"><strong>PayPal</strong><br />';

					echo implode( '', $log );

					echo '</div>';

				}
			}

			if ( ! empty( $postdata['paymentAmount'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

				rsvp_admin_payment( $rsvp_id );

			}

			if ( ! empty( $postdata['markpaid'] )  && wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) ) {

				foreach ( $postdata['markpaid'] as $rsvp_id => $value ) {
					rsvp_admin_payment( $rsvp_id, $value );
				}
			}

			if ( isset( $_GET['rsvp'] ) ) {

				$rsvprow = $wpdb->get_row( $wpdb->prepare("SELECT * FROM %i WHERE id=%d",$wpdb->prefix.'rsvpmaker',$_GET['rsvp']), ARRAY_A );

				if ( $rsvprow ) {

					$master_rsvp = $rsvprow['id'];

					$answer = ( $rsvprow['yesno'] ) ? __( 'Yes', 'rsvpmaker' ) : __( 'No', 'rsvpmaker' );

					if ( empty( $rsvpconfirm ) ) {

						$rsvpconfirm = '';
					}

					$rsvpconfirm .= '<div style="border: medium solid #555; padding: 10px;"><p>' . esc_html( $rsvprow['first'] . ' ' . $rsvprow['last'] ) . ": $answer</p>\n";

					$profile = $details = rsvp_row_to_profile( $rsvprow );

					if ( isset( $details['fee_total'] ) && $details['fee_total'] ) {

						$rsvp_id = (int) $_GET['rsvp'];

						$invoice_id = (int) get_post_meta( $eventid, '_open_invoice_' . $rsvp_id, true );

						$paid = $rsvprow['amountpaid'];

						$charge = $details['fee_total'] - $paid;

						$price_display = ( $charge == $details['fee_total'] ) ? $details['fee_total'] : $details['fee_total'] . ' - ' . $paid . ' = ' . $charge;

						if ( $invoice_id ) {

							update_post_meta( $eventid, '_invoice_' . $rsvp_id, $charge );

						} else {

							$invoice_id = 'rsvp' . add_post_meta( $eventid, '_invoice_' . $rsvp_id, $charge );

							add_post_meta( $eventid, '_open_invoice_' . $rsvp_id, $invoice_id );

						}

						$rsvpconfirm .= '<p><strong>' . wp_kses_post( __( 'Event Fee', 'rsvpmaker' ) . ' ' . $details['payingfor'] . ' = ' . number_format( $details['fee_total'], 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'] ) . '</strong></p>';

						if ( $charge != $details['fee_total'] ) {

							$rsvpconfirm .= '<p><strong>' . wp_kses_post( __( 'Previously Paid', 'rsvpmaker' ) . ' ' . number_format( $paid, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'] ) . '</strong></p>';
						}

						if ( $charge > 0 ) {

							$rsvpconfirm .= '<form method="post" name="donationform" id="donationform" action="' . admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&event=' . $eventid ) . '">

<p>' . __( 'Amount Owed', 'rsvpmaker' ) . ': ' . $charge . '<input name="markpaid[]" type="hidden" id="markpaid_' . $master_rsvp . '"  value="' . $charge . '"> ' . $rsvp_options['paypal_currency'] . '</p><input name="rsvp_id" type="hidden" id="rsvp_id" value="' . $rsvp_id . '" ><input type="submit" name="Submit" value="' . __( 'Mark Paid 1', 'rsvpmaker' ) . '"></p>
'.rsvpmaker_nonce('return').'
</form>';

						}
					}

					$rsvpconfirm .= '</div>';

					echo $rsvpconfirm;

				}
			}

			if ( isset( $_GET['edit_rsvp'] ) && current_user_can( 'edit_rsvpmakers' ) ) {
				admin_edit_rsvp( intval($_GET['edit_rsvp']), $eventid );
			}

			if( !isset( $_GET['rsvp_order'] ) || ( $_GET['rsvp_order'] == 'host' ) )
			{				
				$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i WHERE event=%d AND master_rsvp=0 ORDER BY ".(isset($_GET['latest']) ? ' timestamp DESC ' : ' yesno DESC, last, first '),$wpdb->prefix."rsvpmaker",$eventid), ARRAY_A );	
				format_rsvp_details( $results, true, true );	
			}
			else {
				$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i WHERE event=%d ".( isset( $_GET['rsvp_order'] ) && ( $_GET['rsvp_order'] == 'alpha' ) ) ? ' ORDER BY yesno DESC, last, first' : ' ORDER BY yesno DESC, timestamp DESC',$wpdb->prefix . "rsvpmaker",$wpdb->prefix . "rsvpmaker"), ARRAY_A );
				format_rsvp_details( $results );	
			}

		} elseif ( isset( $_GET['detail'] ) ) {

			if ( ! isset( $_GET['rsvp_print'] ) ) {

				echo '<p><a href="' . admin_url( 'edit.php?post_type=rsvpmaker&page=rsvp_report' ) . '">' . __( 'Show Events List', 'rsvpmaker' ) . '</a> | <a href="' . sanitize_text_field($_SERVER['REQUEST_URI']) . '&print_rsvp_report=1&rsvp_print=1&' . rsvpmaker_nonce('query') . '" target="_blank" >' . __( 'Format for printing', 'rsvpmaker' ) . '</a></p>';
			}

			$limit = (int) $_GET['limit'];

			if ( $_GET['detail'] == 'future' ) {

				$future = rsvpmaker_get_future_events( '', $limit );

			} else {
				$future = get_past_rsvp_events( '', $limit );
			}

			$all_emails = array();

			if ( is_array( $future ) ) {

				foreach ( $future as $f ) {

					

					

					$rsvps = $wpdb->get_results( $wpdb->prepare("SELECT * FROM %i WHERE event=%d ORDER BY yesno DESC, timestamp DESC",$wpdb->prefix . "rsvpmaker",$f->ID), ARRAY_A );

					if ( ! empty( $rsvps ) ) {

						printf( '<h1>RSVPs for <a target="_blank" href="%s">%s %s</a></h1>', get_permalink( $f->ID ), esc_html( $f->post_title ), esc_html( $f->date ) );

						$emails = format_rsvp_details( $rsvps );

						if ( ! empty( $emails ) ) {

							$all_emails = array_merge( $all_emails, $emails );
						}
					}
				}
			}

			if ( ! empty( $all_emails ) ) {

				$attendees = implode( ', ', $all_emails );

				$label = __( 'Email Attendees (all)', 'rsvpmaker' );

				printf( '<p><a href="mailto:%s">%s: %s</a>', esc_attr( $attendees ), esc_html( $label ), esc_html( $attendees ) );

			}
		} else { // show events list

			$eventlist = '';

			$sql = 'SELECT * FROM ' . $wpdb->prefix . 'rsvpmaker_event ';

			if ( ! isset( $_GET['show'] ) ) {

				$sql2 = $sql . ' WHERE date < CURDATE( ) ORDER BY date DESC LIMIT 0,20';

				$sql .= ' WHERE date > CURDATE( ) ORDER BY date';

				$eventlist .= '<p>' . __( 'Showing future and recent events', 'rsvpmaker' ) . ' (<a href="' . sanitize_text_field($_SERVER['REQUEST_URI']) . '&show=all">show all</a>)<p>';

				?>

<form action="edit.php" method="get">
<?php rsvpmaker_nonce(); ?>
				<?php esc_html_e( 'Show details for', 'rsvpmaker' ); ?>

<input type="hidden" name="page" value="rsvp_report">

<input type="hidden" name="post_type" value="rsvpmaker">

<select name="limit">

<option value="5">5</option>

<option value="10">10</option>

<option value="25">25</option>

<option value="50">50</option>

<option value="100">100</option>

</select>

<select name="detail">

<option value="past">past</option>

<option value="future">future</option>

</select> events 

<button><?php esc_html_e( 'Show', 'rsvpmaker' ); ?></button>

</form>

<form action="edit.php" method="get">
<?php rsvpmaker_nonce(); ?>
<?php esc_html_e( 'Search by ', 'rsvpmaker' ); ?>

<input type="hidden" name="page" value="rsvp_report">

<input type="hidden" name="post_type" value="rsvpmaker">

<p><input type="text" name="rsvpsearch" value="" placeholder="email, first name, or last name" style="width: 250px;" /> <input type="checkbox" name="datelimit" value="1" checked="checked" /> <?php esc_html_e( 'Future Events', 'rsvpmaker' ); ?> </p>
<button><?php esc_html_e( 'Search', 'rsvpmaker' ); ?></button>

</form>
<p><a href="<?php echo admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&unpaid_upcoming&'.rsvpmaker_nonce('query')); ?>"><?php esc_html_e( 'Show Unpaid Future RSVPs', 'rsvpmaker' ); ?></a></p>
				<?php

			} else {

				$eventlist .= '<p>' . esc_html( __( 'Showing past events (for which RSVPs were active) as well as upcoming events.', 'rsvpmaker' ) ) . '<p>';

				$sql .= ' ORDER BY date DESC';

			}

			$wpdb->show_errors();

			$results = $wpdb->get_results( $sql );

			if ( $results ) {

				foreach ( $results as $row ) {

					if ( empty( $events[ $row->event ] ) && get_post_meta($row->event,'_rsvp_on',true) ) {
						$events[ $row->event ] = $row->post_title;
						$t = rsvpmaker_strtotime( $row->date );
						$events[ $row->event ] .= ' ' . rsvpmaker_date( $rsvp_options['long_date'], $t );	
					}

				}
			}

			if ( ! empty( $sql2 ) ) {

				$results = $wpdb->get_results( $sql2 );

				if ( $results ) {

					foreach ( $results as $row ) {

						if ( empty( $events[ $row->event ] ) && get_post_meta($row->event,'_rsvp_on',true) ) {
							$past_events[ $row->event ] = $row->post_title;
							$t = rsvpmaker_strtotime( $row->date );
							$past_events[ $row->event ] .= ' ' . rsvpmaker_date( $rsvp_options['long_date'], $t );
						}

					}
				}
			}

			if ( ! empty( $events ) ) {

				foreach ( $events as $post_id => $event ) {



					if ( $rsvpcount = $wpdb->get_var( $wpdb->prepare('SELECT count(*) FROM %i WHERE yesno=1 AND event=%d',$wpdb->prefix . 'rsvpmaker',$post_id) ) ) {
						$eventlist .= "<h3>$event</h3>";
						$eventlist .= '<p><a href="' . admin_url() . 'edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . intval( $post_id ) . '">' . __( 'RSVP', 'rsvpmaker' ) . ' ' . __( 'Yes', 'rsvpmaker' ) . ': ' . $rsvpcount . '</a></p>';
					}
				}
			}

			$pastlist = '';
			if ( ! empty( $past_events ) ) {

				foreach ( $past_events as $post_id => $event ) {
					if ( $rsvpcount = $wpdb->get_var( $wpdb->prepare('SELECT count(*) FROM %i WHERE yesno=1 AND event=%d',$wpdb->prefix . 'rsvpmaker',$post_id) ) ) {
						$pastlist .= "<h3>$event</h3>";
						$pastlist .= '<p><a href="' . admin_url() . 'edit.php?post_type=rsvpmaker&page=rsvp_report&event=' . intval( $post_id ) . '">' . __( 'RSVP', 'rsvpmaker' ) . ' ' . __( 'Yes', 'rsvpmaker' ) . ': ' . $rsvpcount . '</a></p>';
					}
				}
			}

			$contacts = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'rsvpmaker where event=0');

			echo '<div style="display:flex; gap: 3rem;">';
			if ( $contacts && ! isset( $_GET['rsvp_print'] ) ) {
				echo '<div><h2>' . esc_html( __( 'Contact Form Entries', 'rsvpmaker' ) ) . "</h2>\n".'<p><a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&event=0&rsvp_order=timestamp').'">View ' . $contacts.'</a></div>';
			}
			printf('<div><h2><a href="%s">%s</a></h2></div>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&allcontacts=1'),__('List All Contacts'));//'<div><h2>' . esc_html( __( 'Contact Form Entries', 'rsvpmaker' ) ) . "</h2>\n".'<p><a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&event=0&rsvp_order=timestamp').'">View ' . $contacts.'</a></div>';
			echo '</div>';

			echo '<div style="display:flex; gap: 3rem;">';
			if ( $eventlist && ! isset( $_GET['rsvp_print'] ) ) {
				$headline = (isset($_GET['show'])) ? __( 'All Events', 'rsvpmaker' ) : __( 'Upcoming Events', 'rsvpmaker' );
				echo '<div><h2>' . esc_html( $headline ) . "</h2>\n" . $eventlist.'</div>';
			}
			if ( $pastlist && ! isset( $_GET['rsvp_print'] ) ) {
				echo '<div><h2>' . esc_html( __( 'Recent Events', 'rsvpmaker' ) ) . "</h2>\n" . $pastlist.'</div>';
			}
			echo '</div>';
		}
	$rsvp_report_api_code = get_option('rsvp_report_api_code');
	if(isset($_GET['enable_api'])) {
		$rsvp_report_api_code = wp_generate_password(20,false);
		update_option('rsvp_report_api_code',$rsvp_report_api_code);
	}
echo '<div style="background-color: #fff; padding: 10px; margin-top: 20px;">';
?>

<h3>RSVP Report API access for Google Sheets or Your Own Scripts</h3>
<p>See the <a href="https://rsvpmaker.com/knowledge-base/rsvp-report-google-sheet-with-api-access/">Google Sheets example</a>.</p>
<?php
	if($rsvp_report_api_code) {
		printf('<p id="api_link">API access URL: <a href="%s">%s</a></p>',site_url('/wp-json/rsvpmaker/v1/rsvp_report?code='.$rsvp_report_api_code),site_url('/wp-json/rsvpmaker/v1/rsvp_report?code='.$rsvp_report_api_code));
		printf('<p id="api_link"><a href="%s">Reset API Access Key</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&enable_api=1#api_link'));
	}
	else
		printf('<p id="api_link"><a href="%s">Enable API Access</a></p>',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&enable_api=1#api_link'));
echo '</div>';
	}
// end rsvp report

function format_rsvp_row($row, $fields, $pricing = null) {
	global $post, $rsvpmaker_additional_fields,$rsvp_options;
	$owed_list = '';
	if(!empty($row['event'])) {
		if(isset($_GET['allcontacts'])) {
			$event = get_rsvpmaker_event($row['event']);
			printf('<h2>%s %s</h2>',$event->post_title,rsvpmaker_date($rsvp_options['long_date'],intval($event->ts_start)));
		}
		echo '<h3 id="rsvprow'.intval($row['id']).'">' . esc_html( $row['yesno'] . ' ' . $row['first'] . ' ' . $row['last'] . ' ' . $row['email'] );
	}
	else {
		if(isset($_GET['allcontacts']))
			echo '<h2>Contact Form</h2>';
		echo '<h3 id="rsvprow'.intval($row['id']).'">'. esc_html($row['first'] . ' ' . $row['last'] . ' ' . $row['email']);
	}

	if ( $row['guestof'] ) {

		echo esc_html( ' (' . __( 'guest of', 'rsvpmaker' ) . ' ' . $row['guestof'] . ')' );
	}

	echo '</h3>';

	$permalink = get_permalink($row['event']);

	if ( $row['master_rsvp'] ) {

		if ( isset( $guestcount[ $row['master_rsvp'] ] ) ) {

			$guestcount[ $row['master_rsvp'] ]++;

		} else {
			$guestcount[ $row['master_rsvp'] ] = 1;
		}
	} else {
		$master_row[ $row['id'] ] = $row['first'] . ' ' . $row['last'];
		if(empty($_GET['rsvp_print'])) {
			$url = add_query_arg('update',$row['id'],$permalink);
			$url = add_query_arg('e',$row['email'],$url);
			$url = add_query_arg('t',time(),$url).'#rsvpnow';
			if(!empty($row['event']))							
			printf('<p>Update link: <a href="%s" target="_blank">%s</a></p><p><em>Share with users who want to update their details or add guests.</em></p>',$url,$url);	
		}
	}

	if ( $row['details'] ) {
		$details = unserialize( $row['details'] );
	}

	if ( $pricing && isset( $details['fee_total'] ) ) {
		echo '<div style="font-weight: bold;">' . __( 'fee_total', 'rsvpmaker' ) . ': ' . esc_html( number_format($details['fee_total'],2) ) . '</div>';
	}

	if ( ! empty( $details['payingfor'] ) ) {
		echo '<div style="font-weight: bold;">' . __( 'Paying For', 'rsvpmaker' ) . ': ' . wp_kses_post( $details['payingfor'] ) . '</div>';
	}

	if ( $row['amountpaid'] > 0 ) {
		echo '<div style="color: #006400;font-weight: bold;">' . __( 'Paid', 'rsvpmaker' ) . ': ' . esc_html( $row['amountpaid'] ) . ' - '.sprintf('<a href="%s">Transaction List</a>',admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_stripe_report&email='.$row['email'])).'</div>';
	}

	if ( isset( $details['fee_total'] ) ) {
		$owed = $details['fee_total'] - $row['amountpaid'];
		if ( $owed ) {
			echo '<div style="color: red;font-weight: bold;">' . __( 'Owed', 'rsvpmaker' ) . ': ' . number_format($owed,2) . '</div>';
			$url = get_permalink($row['event']);
			$url = add_query_arg('rsvp',$row['id'],$url);
			$url = add_query_arg('e',$row['email'],$url);					
			printf('<p>Payment link: <a href="%s" target="_blank">%s</a></p>',$url,$url);
			if ( $owed > 0 ) {
				if(is_admin()) {
					echo '<form method="post" name="donationform" id="donationform" action="' . admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&event=' . intval($row['event']) ) . '"><input name="markpaid[' . intval($row['id']) . ']" type="text" id="markpaid_' . $row['id'] . '"  value="' . $owed . '"> ' . $rsvp_options['paypal_currency'] . '</p><input name="rsvp_id" type="hidden" id="rsvp_id" value="' . intval($rsvp_id) . '" ><input type="submit" name="Submit" value="' . __( 'Mark Paid', 'rsvpmaker' ) . '"></p>'.rsvpmaker_nonce('return').'</form>';	
				}
					$owed_list = sprintf( '<p><input type="checkbox" name="markpaid[%s]" value="%s">%s %s %s %s</p>', esc_attr( $row['id'] ), esc_html( $owed ), esc_html( $row['first'] ), esc_html( $row['last'] ), esc_html( $owed ), __( 'Owed', 'rsvpmaker' ) );
			}
		}
	}

	echo '<p>';

	if ( $row['details'] ) {

		$details    = unserialize( $row['details'] );
		$newdetails = array();
		if ( is_array( $details ) ) {
			foreach ( $details as $name => $value ) {
				if(('id' == $name) || ('details' == $name))
					continue;
				if ( $value ) {
					if ( strpos( $name, ' ' ) ) {
						$update = true;
					}
					$label = get_post_meta( $row['event'], 'rsvpform' . $name, true );
					if ( $label ) {
						$name = $label;
					}
					if('payingfor' != $name)//already displayed
						echo esc_attr($name) . ': ' . esc_attr( $value ) . '<br />';

					$field                = preg_replace( '/[^a-z0-9_]/', '_', strtolower( $name ) );
					$newdetails[ $field ] = $value;
					if ( ! in_array( $name, $fields ) ) {
						$rsvpmaker_additional_fields[] = $name;
					}
				}
			}
		}
	}

	if ( $row['note'] ) {
		echo ' note: ' . nl2br( esc_attr( $row['note'] ) ) . '<br />';
	}
	return $owed_list;
}

function format_rsvp_details( $results, $editor_options = true, $check_guests = false ) {

		global $rsvp_options, $wpdb, $post, $rsvpmaker_additional_fields;
		$pricing = get_post_meta($post->ID,'pricing',true);
		$update      = false;

		$missing = $owed_list = '';

		$members = $nonmembers = 0;

		if ( $results ) {
			$fields = array( 'yesno', 'first', 'last', 'email', 'guestof', 'amountpaid', 'owed', 'fee_total' );
			$guestfields = array( 'yesno', 'first', 'last', 'email', 'guestof' );
		}

		$number_registered = sizeof($results);
		$unpaidcount = 0;

		foreach ( $results as $index => $row ) {

			$row['yesno'] = ( $row['yesno'] ) ? 'YES' : 'NO';

			if ( $row['yesno'] ) {
				$emails[ $row['email'] ] = $row['email'];
			}

			if ( !empty($row['email']) && get_user_by( 'email', $row['email'] ) ) {
				$members++;
			} else {
				$nonmembers++;
			}
			$owed_list .= format_rsvp_row($row,$fields, $pricing);

			$is_unpaid = !empty($row['fee_total']) && '0.00' != $row['fee_total'] && '0.00' != $row['owed'] && !empty($row['owed']);
			if($is_unpaid)
				$unpaidcount++;

			if($check_guests) {
				$sql = $wpdb->prepare("select * from ".$wpdb->prefix."rsvpmaker where master_rsvp=%d ORDER by last, first",$row['id']);
				$g = $wpdb->get_results($sql, ARRAY_A);
				if($g) {
					$number_registered += sizeof($g);
					if($is_unpaid)
						$unpaidcount+= sizeof($g);
					echo '<blockquote>';
					foreach($g as $grow) {
						$grow['yesno'] = '';
						$grow['guestof'] = '';
						format_rsvp_row($grow,$guestfields,$pricing);
					}
					echo '</blockquote>';
				}
			}

			$t = rsvpmaker_strtotime( $row['timestamp'] );

			echo 'posted: ' . rsvpmaker_date( $rsvp_options['short_date'], $t );

			if(isset($daily_count[rsvpmaker_date( 'Y-m-d', $t )]))
				$daily_count[rsvpmaker_date( 'Y-m-d', $t )]++;
			else
				$daily_count[rsvpmaker_date( 'Y-m-d', $t )] = 1;

			echo '</p>';

			if ( $update ) {
				$sql = $wpdb->prepare( 'UPDATE %i SET details=%s WHERE id=%d',$wpdb->prefix . 'rsvpmaker', serialize( $newdetails ), $row['id'] );
				$wpdb->query( $sql );
			}

			if ( ! isset( $_GET['rsvp_print'] ) && current_user_can( 'edit_others_posts' ) && $editor_options ) {
				$editlink = ($row['master_rsvp']) ? '' : sprintf( '<a href="%s&edit_rsvp=%d">Edit %s %s</a> | ', admin_url() . 'edit.php?post_type=rsvpmaker&page=rsvp_report&event='.$post->ID, $row['id'], esc_attr( $row['first'] ), esc_attr( $row['last'] ) );

				echo sprintf( '<p>%s <a href="%s&delete=%d">Delete %s %s</a></p>', $editlink, admin_url() . 'edit.php?post_type=rsvpmaker&page=rsvp_report', intval($row['id']), esc_attr( $row['first'] ), esc_attr( $row['last'] ) );
			}

			$userrsvps[] = $row['user_id'];

		}
		printf('<p>%d registered, including %d unpaid</p>',$number_registered,$unpaidcount);

		echo '<div class="noprint">';

		if ( ! empty( $rsvp_options['missing_members'] ) ) {

			$blogusers = get_users( 'blog_id=1&orderby=nicename' );

			foreach ( $blogusers as $user ) {

				if ( in_array( $user->ID, $userrsvps ) ) {

					continue;
				}

				$userdata = get_userdata( $user->ID );

				$missing .= "<p>$userdata->display_name $userdata->user_email</p>\n";

			}
		}

		if ( ! empty( $missing ) ) {

			echo '<hr /><h3>' . __( 'Members Who Have Not Responded', 'rsvpmaker' ) . '</h3>' . esc_html( $missing );

		}

		if ( ! empty( $emails ) ) {

			$emails = apply_filters( 'rsvp_yes_emails', $emails );
		}

		if ( isset( $emails ) && is_array( $emails ) ) {

			$emails = array_filter( $emails ); // removes empty elements

			$attendees = implode( ', ', $emails );

			$label = __( 'Email Attendees', 'rsvpmaker' );

			printf( '<p><a href="mailto:%s">%s: %s</a>', esc_attr( $attendees ), esc_html( $label ), esc_html( $attendees ) );

		}

		if ( $members && $nonmembers ) {

			printf( '<p>Responses from %d members with user accounts and %d nonmembers.</p>', $members, $nonmembers );
		}

		if(!empty($daily_count) && empty($_GET['allcontacts']))
		{
			echo '<h3>RSVPs Per Day</h3>';
			foreach($daily_count as $day => $count) {
				printf('<p>%s: %s</p>',$day, $count);
			}
		}

		if ( !isset( $_GET['event'] ) && !isset( $_GET['allcontacts']) ) {
			return;
		}

		global $phpexcel_enabled; // set if excel extension is active

		if ( isset( $fields ) ) {
			if(is_array($rsvpmaker_additional_fields))
			$fields = array_merge($fields,$rsvpmaker_additional_fields);
			if ( $fields && ! isset( $_GET['rsvp_print'] ) && ! isset( $_GET['limit'] ) ) {

				$fields[] = 'note';

				$fields[] = 'timestamp';

				foreach ( $fields as $field ) {

					// no duplicates, please

					$i = preg_replace( '/[^a-z0-9]/', '_', strtolower( $field ) );

					if ( $i == 'first_name' ) {

						$i = 'first';
					}

					if ( $i == 'last_name' ) {

						$i = 'last';
					}

					$newfields[ $i ] = $i;

				};
				?>

<div id="excel" name="excel" style="padding: 10px; border: thin dotted #333; width: 300px;margin-top: 30px;">

<h3><?php esc_html_e( 'Data Table / Spreadsheet', 'rsvpmaker' ); ?></h3>

<form method="get" action="edit.php" target="_blank">

				<?php

				foreach ( $_GET as $name => $value ) {
					echo sprintf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $name ), esc_attr( $value ) );
				}

				foreach ( $newfields as $i => $field ) {
					if(empty($_GET['event']) && in_array($field,array('yesno','fee_total','owed','amountpaid')))//don't apply to contact form
						continue;
					echo '<input type="checkbox" name="fields[]" value="' . $i . '" checked="checked" /> ' . $field . "<br />\n";
				}

				echo '<input type="checkbox" name="rsvp_print" value="1"> '.__('Format for printing','rsvpmaker').'<br />';
				rsvpmaker_nonce();

				?>

<p><button name="print_rsvp_report" value="1" ><?php esc_html_e( 'Print Report', 'rsvpmaker' ); ?></button> <button name="rsvp_csv" value="1" ><?php esc_html_e( 'Download CSV', 'rsvpmaker' ); ?></button></p>

				<?php

				if ( isset( $phpexcel_enabled ) ) {

					$rsvpexcel = wp_create_nonce( 'rsvpexcel' );

					printf( '<p><button name="rsvpexcel" value="%s" />%s</button></p>', $rsvpexcel, __( 'Download to Excel', 'rsvpmaker' ) );

				} else {

					echo '<br />';

					esc_html_e( 'Additional RSVPMaker Excel plugin required for download to Excel function.', 'rsvpmaker' );

					echo '<a href="https://wordpress.org/plugins/rsvpmaker-excel/">https://wordpress.org/plugins/rsvpmaker-excel/</a>';

				}

				?>

</form>

</div>

				<?php

			}
		}

		$options = $name = '';

		if ( is_admin() && ! isset( $_GET['rsvp_print'] ) ) {
			$event_id = isset($_GET['event']) ? intval($_GET['event']) : 0;
			$results = $wpdb->get_results($wpdb->prepare("select * from %i where event=%d AND master_rsvp=0 ORDER BY last, first",$wpdb->prefix."rsvpmaker",$event_id));
				foreach ( $results as $row ) {
					$options .= sprintf( '<option value="%d">%s</option>', $row->id, esc_html( $row->first.' '.$row->last ) );
				}
			?>

<h3><?php esc_html_e( 'Add/Edit Entries', 'rsvpmaker' ); ?></h3>

<form action="edit.php" method="get">
<?php rsvpmaker_nonce(); ?>
<select name="edit_rsvp"><option value="0">Add New</option><?php echo $options; ?></select>

<input type="hidden" name="page" value="rsvp_report">

<input type="hidden" name="post_type" value="rsvpmaker">

<input type="hidden" name="event" value="<?php echo intval($_GET['event']); ?>">

<button><?php esc_html_e( 'Edit', 'rsvpmaker' ); ?></button>

</form>
<h3><?php esc_html_e( 'Move Between Events', 'rsvpmaker' ); ?></h3>

<p><?php esc_html_e( 'Transfers the individual who registered and any guests registered as part of the same party to another event. Payment status is also transferred.' ); ?></p>

<form action="<?php echo admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&event=' . sanitize_text_field($_GET['event']) ); ?>" method="post">
<?php rsvpmaker_nonce(); ?>
<p><select name="move_rsvp"><option value=""><?php esc_html_e( 'Pick Entry', 'rsvpmaker' ); ?></option><?php echo $options; ?></select>

to <select name="move_to">

			<?php

			$future = rsvpmaker_get_future_events( '', 50 );

			if ( $future ) {

				foreach ( $future as $event ) {

					if ( $event->ID != $_GET['event'] ) {

						printf( '<option value="%d">%s - %s</option>', $event->ID, esc_html( $event->post_title ), esc_html( $event->date ) );
					}
				}
			}

			?>

</select> </p>

<button><?php esc_html_e( 'Move', 'rsvpmaker' ); ?></button>

</form>
			<?php
			if ( ! empty( $owed_list ) ) {

				printf( '<h3>Record Payments</h3><form action="%s" method="post">%s', admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&event=' . intval( $_GET['event'] ) ), rsvpmaker_nonce('return') );

				echo $owed_list;

				?>

<button><?php esc_html_e( 'Mark Paid', 'rsvpmaker' ); ?></button>

</form>

				<?php

			} // end is admin
		echo '</div>'; //end of noprint
		}

		if ( ! empty( $emails ) ) {

			return $emails;
		}

	}
// end format_rsvp_details
function admin_edit_rsvp( $id, $event ) {

	global $wpdb;

	global $profile;

	global $master_rsvp;

	global $post;

	if ( $id == 0 ) {

		$profile = array( 'yesno' => 1 );

	} else {

		$row = $wpdb->get_row( $wpdb->prepare('SELECT * FROM %i WHERE id=%d',$wpdb->prefix . 'rsvpmaker', $id), ARRAY_A );

		$profile = rsvp_row_to_profile( $row );

	}

	$master_rsvp = $id;

	$custom_fields = get_rsvpmaker_custom( $event );

	global $rsvp_options;

	$form = $custom_fields['_rsvp_form'][0];

	printf( '<form action="%s" method="post">', admin_url( 'edit.php?page=rsvp_report&post_type=rsvpmaker&rsvp_order=host&event=' . $event.'#rsvprow'.$event ) );
	rsvpmaker_nonce();
	echo '<p>';
	?>
	<input name="yesno" type="radio" value="1" <?php echo ( $profile['yesno'] ) ? 'checked="checked"' : ''; ?> /> <?php echo __( 'Yes', 'rsvpmaker' ); ?> <input name="yesno" type="radio" value="0" <?php echo ( ! $profile['yesno'] ) ? 'checked="checked"' : ''; ?> /> 
														<?php
														echo __( 'No', 'rsvpmaker' ) . '</p>';

														$results = get_rsvp_dates( $event );

														if ( $results ) {

															$start = 2;

															$firstrow = null;

															$dateblock = '';

															global $last_time;

															foreach ( $results as $row ) {

																$timeblock = '<span class="time">';

																if ( ! $firstrow ) {

																	$firstrow = $row;
																}

																$last_time = $t = rsvpmaker_strtotime( $row['datetime'] );

																$dateblock .= '<div itemprop="startDate" datetime="' . date( 'c', $t ) . '">';

																$dateblock .= mb_convert_encoding( rsvpmaker_date( $rsvp_options['long_date'], $t ), 'UTF-8' );

																$dur = $row['duration'];

																$timeblock .= rsvpmaker_date( ' ' . $rsvp_options['time_format'], $t );

																// dchange

																if ( $dur == 'set' ) {

																	$dur = rsvpmaker_strtotime( $row['end_time'] );
																}

																if ( is_numeric( $dur ) ) {

																	$timeblock .= ' <span class="end_time">' . __( 'to', 'rsvpmaker' ) . ' ' . rsvpmaker_date( $rsvp_options['time_format'], $dur ) . '</span>';
																}

																if ( ( $dur != 'allday' ) && ! strpos( $dur, '|' ) ) {

																	$dateblock .= $timeblock . '<span>';
																}

																$dateblock .= "</div>\n";

															}
														}

														echo '<div class="dateblock">' . $dateblock . "\n</div>\n";

														if ( $dur && ( $slotlength = $custom_fields['_rsvp_timeslots'][0] ) ) {

															?>

<div><?php echo __( 'Number of Participants', 'rsvpmaker' ); ?>: <select name="participants">

	<option value="1">1</option>

	<option value="2">2</option>

	<option value="3">3</option>

	<option value="4">4</option>

	<option value="5">5</option>

	<option value="6">6</option>

	<option value="7">7</option>

	<option value="8">8</option>

	<option value="9">9</option>

	<option value="10">10</option>

  </select></div>
<div><?php echo __( 'Choose timeslots', 'rsvpmaker' ); ?></div>

															<?php

															$t = rsvpmaker_strtotime( $firstrow['datetime'] );

															$dur = $firstrow['duration'];

															if ( strpos( $dur, ':' ) ) {

																$dur = rsvpmaker_strtotime( $dur );
															}

															$day = rsvpmaker_date( 'j', $t );

															$month = rsvpmaker_date( 'n', $t );

															$year = date( 'Y', $t );

															$hour = rsvpmaker_date( 'G', $t );

															$minutes = rsvpmaker_date( 'i', $t );

															$slotlength = explode( ':', $slotlength );

															$min_add = $slotlength[0] * 60;

															$min_add = $min_add + $slotlength[1];

															for ( $i = 0; ( $slot = rsvpmaker_mktime( $hour, $minutes + ( $i * $min_add ), 0, $month, $day, $year ) ) < $dur; $i++ ) {

																$sql = $wpdb->prepare("SELECT SUM(participants) from %i WHERE time=%d AND event = %d",$wpdb->prefix . "rsvp_volunteer_time",$slot,$post->ID);

																$signups = ( $signups = $wpdb->get_var( $sql ) ) ? $signups : 0;

																echo '<div><input type="checkbox" name="timeslot[]" value="' . $slot . '" /> ' . rsvpmaker_date( ' ' . $rsvp_options['time_format'], $slot ) . " $signups participants signed up</div>";

															}
														}

														if ( isset( $custom_fields['_per'][0] ) && $custom_fields['_per'][0] ) {

															$pf = '';

															$options = '';

															$per = unserialize( $custom_fields['_per'][0] );

															if ( isset( $custom_fields['_rsvp_count_party'][0] ) && $custom_fields['_rsvp_count_party'][0] ) {

																foreach ( $per['unit'] as $index => $value ) {

																	$price = (float) $per['price'][ $index ];

																	if ( ! $price ) {

																		break;
																	}

																	$display[] = $value . ' @ ' . ( ( $rsvp_options['paypal_currency'] == 'USD' ) ? '$' : $rsvp_options['paypal_currency'] ) . ' ' . number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] );

																}

																$number_prices = ( empty( $display ) ) ? 0 : sizeof( $display );

																if ( $number_prices ) {

																	if ( $number_prices == 1 ) { // don't show options, just one choice

																		printf( '<h3 id="guest_count_pricing"><input type="hidden" name="guest_count_price" value="%s">%s ' . __( 'per person', 'rsvpmaker' ) . '</h3>', 0, esc_html( $display[0] ) );

																	} else {

																		foreach ( $display as $index => $value ) {

																			$s = ( $index == $profile['pricechoice'] ) ? ' selected="selected" ' : '';

																			$options .= sprintf( '<option value="%d" %s>%s</option>', $index, $s, esc_html( $value ) );

																		}

																			printf( '<div id="guest_count_pricing">' . __( 'Options', 'rsvpmaker' ) . ': <select name="guest_count_price">%s</select></div>', $options );

																	}
																}
															} else {

																foreach ( $per['unit'] as $index => $value ) {

																	$price = (float) $per['price'][ $index ];

																	if ( ! $price ) {

																		break;
																	}

																	$pf .= '<div><select name="payingfor[' . $index . ']" class="tickets"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="unit[' . $index . ']" value="' . $value . '" />' . $value . ' @ <input type="hidden" name="price[' . $index . ']" value="' . $price . '" />' . ( ( $rsvp_options['paypal_currency'] == 'USD' ) ? '$' : $rsvp_options['paypal_currency'] ) . ' ' . number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . '</div>' . "\n";

																}

																if ( ! empty( $pf ) ) {

																	echo '<h3>' . __( 'Paying For', 'rsvpmaker' ) . '</h3><p>' . $pf . "</p>\n";
																}
															}
														}

														if ( is_numeric( $form ) ) {

															$fpost = get_post( $form );

															$form = $fpost->post_content;

															if ( function_exists( 'do_blocks' ) ) {

																$form = do_blocks( $form );
															}
														}
														$pricing = rsvp_get_pricing($post->ID);
														if ($pricing && ($pricing[0]->price != '0.00')) {

															$pf = '';

															$options = '';

															foreach ( $pricing as $index => $price_row ) {

																if ( empty($price_row->price ) ) { // no price = $0 where no other price is specified
																	continue;
																}

																$price = (float) $price_row->price;

																$deadstring = '';

																if ( ! empty( $price_row->price_deadline ) ) {

																	$deadline = (int) $price_row->price_deadline;

																	if ( time() > $deadline ) {

																		continue;

																	} else {
																		if(!empty($price_row->niceDeadline))
																			$deadstring = ' (' . __( 'until', 'rsvpmaker' ) . ' ' . $price_row->niceDeadline . ')';
																	}
																}

																$display[ $index ] = $price_row->unit . ' @ ' . ( ( $rsvp_options['paypal_currency'] == 'USD' ) ? '$' : $rsvp_options['paypal_currency'] ) . ' ' . number_format( $price, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . $deadstring;

															}

															if ( isset( $custom_fields['_rsvp_count_party'][0] ) && $custom_fields['_rsvp_count_party'][0] ) {

																$number_prices = sizeof( $display );

																if ( $number_prices ) {

																	if ( $number_prices == 1 ) { // don't show options, just one choice

																		foreach ( $display as $index => $value ) {
																			printf( '<h3 id="guest_count_pricing"><input type="hidden" name="guest_count_price" value="%s">%s</h3>', $index, esc_html( $value ) );
																		}
																	} else {

																		foreach ( $display as $index => $value ) {

																			$s = ( isset( $profile['pricechoice'] ) && ( $index == $profile['pricechoice'] ) ) ? ' selected="selected" ' : '';

																			$options .= sprintf( '<option value="%d" %s>%s</option>', $index, $s, esc_html( $value ) );

																		}

																		printf( '<div id="guest_count_pricing"><label>' . __( 'Options', 'rsvpmaker' ) . ':</label><select name="guest_count_price"  id="guest_count_price">%s</select></div>', $options );

																	}
																}
															} else {

																if ( sizeof( $display ) ) {

																	foreach ( $display as $index => $value ) {

																		if ( empty( $pricing ) ) {

																			continue;
																		}

																		if(!empty($pricing[$index])) {
																			$price = (float) $pricing[ $index ]->price;
																			$unit = $pricing[ $index ]->unit;
																			$pf .= '<div class="paying_for_tickets"><select name="payingfor[' . $index . ']" class="tickets"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="unit[' . $index . ']" value="' . esc_attr( $unit ) . '" />' . esc_html( $value ) . '<input type="hidden" name="price[' . $index . ']" value="' . esc_attr( $price ) . '" /></div>' . "\n";	
																		} 

																	}
																}

																if ( ! empty( $pf ) ) {

																	echo '<h3>' . __( 'Paying For', 'rsvpmaker' ) . '</h3><p>' . $pf . "</p>\n";
																}
															}

															// coupon code

															if ( ! empty( $custom_fields['_rsvp_coupon_code'][0] ) ) {
																printf( '<p><label>Coupon Code:</label> <input type="text" name="coupon_code" size="10" value="%s" /><br /><em>If you have a coupon code, enter it above</em>.</p>', empty($profile['coupon_code']) ? '' : esc_attr($profile['coupon_code']));
															}
														}

														echo rsvpmaker_email_html( $form );

														printf( '<input type="hidden" name="rsvp_id" id="rsvp_id" value="%d" /><input type="hidden" id="event" name="event" value="%d" />%s<p><button>Submit</button></p></form>', $id, $event, rsvpmaker_nonce() );

														echo '<p>' . __( 'Tip: If you do not have an email address for someone you registered offline, you can use the format firstnamelastname@example.com (example.com is an Internet domain reserved for examples and testing). You will get an error message if you try to leave it blank' ) . '</p>';

														echo rsvp_form_jquery();

}

function rsvp_print() {

		if ( (isset( $_GET['rsvp_print']) || isset( $_GET['print_rsvp_report']) ) && isset( $_GET['page'] ) && is_admin() ) {

			if(!wp_verify_nonce(rsvpmaker_nonce_data('data'),rsvpmaker_nonce_data('key')) )
				die("Security error");
			if ( 'word' == $_GET['rsvp_print'] ) {
				global $post;
				$fname = (empty($post->post_name)) ? time() : $post->post_name;
				$fname = apply_filters('rsvp_print_to_word',$fname);
				header( 'Content-Type: application/msword' );
				header( 'Content-disposition: attachment; filename=' . $fname . '.doc' );
			}

			$slug = sanitize_text_field($_GET['page']);

			$hookname = get_plugin_page_hookname( $slug, '' );

			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>' . get_admin_page_title() . '</title>

</head>
<style>
.noprint {display: none}
a {text-decoration: none; color:black;}
body {padding-left: 10px; padding-right: 10px; border: none; background-color: white;}
</style>
<body>

';

			do_action( $hookname );

			echo '</body></html>';

			exit();

		}

}//end rsvp_print()
// if exists

function rsvp_report_unpaid() {

		global $wpdb, $post, $rsvp_options, $is_rsvp_report;

		$action = admin_url('edit.php?post_type=rsvpmaker&page=rsvp_report&unpaid_upcoming&'.rsvpmaker_nonce('query'));

		$is_rsvp_report = true;

		$sql = $wpdb->prepare("select * from %i where date > CURDATE() order by date",$wpdb->prefix.'rsvpmaker_event');

		$events = $wpdb->get_results($sql);

		foreach($events as $event) {

			$sql = $wpdb->prepare('select * from %i WHERE event=%d AND owed > 0.00 ',$wpdb->prefix.'rsvpmaker',$event->event);

			$unpaids = $wpdb->get_results($sql);

			if(!empty($unpaids)) {
				$titledate = rsvpmaker_date($rsvp_options['short_date'], $event->ts_start).' - '.$event->post_title;
				printf('<h1>%s</h1>',$titledate);

				foreach($unpaids as $unpaid) {
					ob_start();

					$payment_link = add_query_arg(array('rsvp'=>$unpaid->id,'e'=>$unpaid->email),get_permalink($event->event)).'#rsvpconfirm';

					printf('<h2>%s %s %s</h2><p>Owed: %s, payment link <a href="%s">%s</a></p>',$unpaid->first,$unpaid->last,$unpaid->email,$unpaid->owed, $payment_link, $payment_link);

					$sql = $wpdb->prepare('select * from %i WHERE event=%d AND master_rsvp=%d ',$wpdb->prefix.'rsvpmaker',$event->event, $unpaid->id);

					$guests = $wpdb->get_results($sql);

					if(!empty($guests)) {

						$guestnames = [];

						foreach($guests as $guest)

							$guestnames[] = $guest->first.' '.$guest->last;

						printf('<p>Guests in party: %s</p>',implode(', ',$guestnames));

					}



			$sql = $wpdb->prepare('select * from %i events join %i rsvps on events.event=rsvps.event WHERE events.event!=%d and rsvps.email=%s and events.date > CURDATE() ',$wpdb->prefix.'rsvpmaker_event',$wpdb->prefix.'rsvpmaker',$event->event, $unpaid->email);

			$other_events = $wpdb->get_results($sql);

			if($other_events) {

				$other_status = [];

				foreach($other_events as $other) {

					$status = $other->post_title;

					$status .= ($other->owed > 0.00) ? ' <span style="color:red">'.$other->owed.' owed</span>' : ' PAID';

					$other_status[] = $status;

				}

				printf('<p>Other registrations: %s</p>',implode(', ',$other_status));
			}
				$mail['html'] = ob_get_flush();
				if(isset($_POST['send_reminders']) && in_array($event->event,$_POST['send_reminders']) || 'all' == $_POST['send_reminders'][0]) {
					$mail['html'] = wpautop(stripslashes($_POST['reminder_message']))."\n\n".sprintf('<h1>%s</h1>',$titledate)."\n\n".$mail['html'];
					$mail['to'] = (isset($_POST['test'])) ? $rsvp_options['rsvp_to'] : $unpaid->email;
					$mail['subject'] = __('Payment Reminder for ','rsvpmaker').$titledate;
					$mail['from'] = $rsvp_options['rsvp_to'];
					$mail['fromname'] = get_bloginfo('name');
					rsvpmailer($mail);
					printf('<p>Reminder sent to %s</p>',$mail['to']);
				}

				}

			}
		$send_reminders[$event->event] = $event->post_title;
		}

		if(isset($_POST['reminder_message']))
			update_option('rsvpmaker_unpaid_reminder_message',stripslashes($_POST['reminder_message']));
		else
			$message = get_option('rsvpmaker_unpaid_reminder_message','You registered for this event but our records indicate that your payment is still pending. If you have already made your payment, please disregard this message. If not, please use the link below to complete your payment. Thank you!');

		printf('<h2>Send Reminders</h2><form action="%s" method="post">', $action);
		printf('<p><input type="checkbox" name="send_reminders[]" value="all" /> %s</p>',__('Send to all events listed above','rsvpmaker'));
		foreach($send_reminders as $id => $title)
			printf('<p><input type="checkbox" name="send_reminders[]" value="%d" /> %s</p>',$id,$title);
		printf('<h3>Message to Include</h3><p><textarea name="reminder_message" style="width: 100%%; height: 200px;">%s</textarea></p>',$message);
		echo '<p><input type="checkbox" name="test" value="1" /> Test</p>';
		submit_button('Send');
		echo '</form>';
	}

