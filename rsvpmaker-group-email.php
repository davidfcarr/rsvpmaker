<?php

/*
Group Email Functions
*/

function rsvpmaker_relay_active_lists() {

	$active = get_option( 'rsvpmaker_discussion_active' );

	$lists = array();

	if ( ! $active ) {

		return array();
	}

	$vars = get_option( 'rsvpmaker_discussion_member' );

	if ( ! empty( $vars['password'] ) ) {

		$lists['member'] = $vars['user'];
	}

	$vars = get_option( 'rsvpmaker_discussion_officer' );

	if ( ! empty( $vars['password'] ) ) {

		$lists['officer'] = $vars['user'];
	}

	return $lists;

}

function rsvpmaker_relay_manual_test() {
	rsvpmaker_admin_heading(__('Manually Trigger Check of Email Lists','rsvpmaker'),__FUNCTION__);
	//echo 'about to call rsvpmaker_relay_init';
	if(rsvpmaker_postmark_is_live()) {
		echo '<p>Postmark Integration is Live</p>';
		rsvpmaker_postmark_chunked_batches();
	}
	else {
		$html = rsvpmaker_relay_init( true );
		if(!wp_get_schedule('rsvpmaker_relay_init_hook')) {
			wp_schedule_event( strtotime('+2 minutes'), 'doubleminute', 'rsvpmaker_relay_init_hook' );
			echo '<p>Activating rsvpmaker_relay_init_hook</p>';
		}	
		if ( !empty($html) ) {

			echo wp_kses_post( $html );

		} else {
			echo '<p>' . __( 'No messages', 'rsvpmaker' ) . '</p>';
		}		
	}
}

add_action( 'rsvpmaker_relay_init_hook', 'rsvpmaker_relay_init' );

function rsvpmaker_relay_init( $show = false ) {

	$active = get_option( 'rsvpmaker_discussion_active' );

	$result = $qresult = '';
	$qresult = rsvpmaker_relay_queue();

	if ( ! $active && ! $show ) {

		return;
	}

	$postmark = get_rsvpmaker_postmark_options();
	if(!empty($postmark['handle_incoming']))
		$result = '<p>Incoming messages being handled by Postmark</p>';
	elseif ( empty( $qresult ) ) {

		$result = rsvpmaker_relay_get_pop( 'member' );

		if ( ! strpos( $result, 'Mail:' ) ) {

			$result .= rsvpmaker_relay_get_pop( 'officer' );
		}

		if ( ! strpos( $result, 'Mail:' ) ) {

			$result .= rsvpmaker_relay_get_pop( 'extra' );
		}

		if ( ! strpos( $result, 'Mail:' ) ) {

			$result .= rsvpmaker_relay_get_pop( 'bot' );
		}
	}
	$html = $qresult . $result;
	do_action('rspvmaker_relay_cron_check',$html);

	if ( $show ) {
		return $html;
	}
}

function rsvpmaker_relay_queue() {
	if(rsvpmaker_postmark_is_live()) {
		echo '<p>queue off in favor of Postmark</p>';
		return;
	}
	else 
		echo '<p>queue active</p>';
	global $wpdb, $post, $page, $pages;
	$rsvpmaker_message_type = 'email_rule_group_email';
	$limit = (int) get_option('rsvpmaker_email_queue_limit');
	if(empty($limit))
		$limit = 10;
	$count = 0;
	$log = '';
	$sent = 0;
	$last_post_id = 0;
	$posts = $wpdb->posts;
	$postmeta = $wpdb->postmeta;
	$sql = "select ID as post_id, count(*) as hits, post_title as subject, post_content as html, meta_value as `to` FROM $posts JOIN $postmeta ON $posts.ID = $postmeta.post_id WHERE meta_key='rsvprelay_to' group by ID ORDER BY ID LIMIT 0, $limit";
	$results = $wpdb->get_results($sql);
	foreach($results as $mail) {
		$mail = (array) $mail;
		$log .= $mail['subject'].": ".$mail["hits"]."\n";
		$mail['message_type'] = 'email_rule_group_email';
		$mail['override'] = 1;
		$epost_id = $mail['post_id'];
		$hits = $mail['hits'];
		$saved = $wpdb->get_var("SELECT meta_value from $postmeta WHERE post_id=$epost_id AND meta_key='mail_array' ");
		if($saved)
		{
			//saved broadcast message
			$mail = unserialize($saved);
		}
		else {
			$epost = get_post($epost_id);
			$mail['html'] = rsvpmaker_email_html($epost);
			$sql = "select * from $wpdb->postmeta WHERE meta_key!='rsvpmail_sent' AND meta_key!='rsvprelay_to' AND post_id=$epost_id";
			$meta = $wpdb->get_results($sql);
			set_transient('group email meta',$meta);
			foreach($meta as $row) {
				if('rsvprelay_from' == $row->meta_key) {
					$mail['replyto'] = $mail['from'] = $row->meta_value;
					$log .= 'from: '.$mail['from']."\n";
				}
				if('rsvprelay_fromname' == $row->meta_key)
					$mail['fromname'] = $row->meta_value;
				if('message_description' == $row->meta_key)
					$mail['message_description'] = $row->meta_value;
			}	
		}
		set_transient('group email mail',$mail);

		if($hits == 1) {
			$message_description = empty($message_description) ? '' : '<div class="rsvpexplain">' . $message_description . '</div>';
			$result = rsvpmailer( $mail, $message_description );
			$result = $mail['to'] . ' ' . rsvpmaker_date( 'r' ).' '.$result;
			$wpdb->query("update $postmeta SET meta_key='rsvpmail_sent' WHERE meta_key='rsvprelay_to' AND post_id=$epost_id ");
			$log .= $result."\n";
			$count++;
			$limit--;
		}
		else {
			$broadcasts[$epost_id] = $mail;
			update_post_meta($epost_id,'mail_array',$mail);
		}
	}

	if($limit && !empty($broadcasts)) {
		foreach($broadcasts as $epost_id => $mail) {
			$log .= $mail['subject'] . " broadcast\n";
			if($limit < 1)
				break;
			$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$epost_id AND meta_key='rsvprelay_to' LIMIT 0, $limit";
			$results = $wpdb->get_results($sql);
			//print_r($results);
			//return;

			if(empty($message_description))
				$message_description = '';

			foreach($results as $row) {
				$mail['to'] = $row->meta_value;
				$result = rsvpmailer( $mail, '<div class="rsvpexplain">' . $message_description . '</div>' );
				$result = $mail['to'] . ' ' . rsvpmaker_date( 'r' ).' '.$result;
				$wpdb->query("update $postmeta SET meta_key='rsvpmail_sent' WHERE meta_id=$row->meta_id ");
				$log .= $result."\n";
				$limit--;
			}
		}	
	}

	//used with postmark integration
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='rsvprelay_to_batch'";
	$batchrow = $wpdb->get_row($sql);
	if($batchrow) {
		$recipients = unserialize($batchrow->meta_value);
		if(empty($recipients))
			echo 'done';
		rsvpmaker_postmark_broadcast($recipients,$batchrow->post_id);
		$wpdb->query("delete from $wpdb->postmeta where meta_id=$batchrow->meta_id");
		$log .= sizeof($recipients)." added from batch\n";
	}

	do_action('rsvpmaker_relay_queue_log_message',$log);
	return nl2br($log);
}

function group_emails_extract( $text ) {
	preg_match_all( "/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $text, $emails );
	$emails = $emails[0];
	$unique = array();
	foreach ( $emails as $email ) {

		$email = strtolower( $email );

		$unique[ $email ] = $email;

	}

	return $unique;

}



function get_mime_type( &$structure ) {

	$primary_mime_type = array( 'TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER' );

	if ( $structure->subtype ) {

		return $primary_mime_type[ (int) $structure->type ] . '/' . $structure->subtype;

	}

		return 'TEXT/PLAIN';

}



function rsvpmaker_get_part( $stream, $msg_number, $mime_type, $structure = false, $part_number = false ) {

	if ( ! $structure ) {

		$structure = imap_fetchstructure( $stream, $msg_number );

	}

	if ( $structure ) {

		if ( $mime_type == get_mime_type( $structure ) ) {

			if ( ! $part_number ) {

				$part_number = '1';

			}

			$text = imap_fetchbody( $stream, $msg_number, $part_number );

			if ( $structure->encoding == 3 ) {

				return imap_base64( $text );

			} elseif ( $structure->encoding == 4 ) {

				return imap_qprint( $text );

			} else {

				return $text;

			}
		}

		if ( $structure->type == 1 ) { /* multipart */

			// while(list($index, $sub_structure) = each($structure->parts)) {

			foreach ( $structure->parts as $index => $sub_structure ) {

				$prefix = '';

				if ( $part_number ) {

					$prefix = $part_number . '.';

				}

				$data = rsvpmaker_get_part( $stream, $msg_number, $mime_type, $sub_structure, $prefix . ( $index + 1 ) );

				if ( $data ) {

					return $data;

				}
			} // END OF WHILE
		} // END OF MULTIPART
	} // END OF STRUTURE

		return false;

} // END OF FUNCTION



function rsvpmaker_relay_get_pop( $list_type = '', $return_count = false ) {

	global $wpdb;

	// $wpdb->show_errors();

	$server = get_option( 'rsvpmaker_discussion_server' );

	$recipients = array();

	$vars = get_option( 'rsvpmaker_discussion_' . $list_type );

	if ( empty( $vars ) || empty( $vars['password'] ) ) {

		return '<div>no password set for ' . $list_type . '</div>';
	}

	$user = $vars['user'];

	$password = $vars['password'];

	$p = explode( '@', $user );

	$actionslug = $p[0];

	$unsubscribed = get_option( 'rsvpmail_unsubscribed' );

	if ( empty( $unsubscribed ) ) {
		$unsubscribed = array();
	}
	// don't want loops, list sending to itself
	$unsubscribed[] = $user;

	$html = '';

	if ( isset( $_GET['test'] ) ) {

		mail( 'relay@toastmost.org', 'Subject', "This is a test\n\nmultiple lines of text" );
	}

	//  Connect to the mail server and grab headers from the mailbox

	$html .= sprintf( '<p>%s, %s, %s</p>', $server, $user, $password );

	$mail = @imap_open( $server, $user, $password, CL_EXPUNGE );

	if ( empty( $mail ) ) {

		return '<div>no mail connection found for ' . $list_type .' ! '. $server .' ! '. $user .' ! '. $password . '</div>';
	}

	$headers = imap_headers( $mail );

	if ( empty( $headers ) ) {

		return '<div>no messages found for ' . $list_type . '</div>';
	}

	$html .= '<pre>' . "Mail:\n" . var_export( $mail, true ) . '</pre>';

	$html .= '<pre>' . "Headers:\n" . var_export( $headers, true ) . '</pre>';

	if ( $list_type == 'member' ) {

		$members = get_site_members();

		foreach ( $members as $member ) {

			$recipients[] = strtolower( $member->user_email );
		}
	} elseif ( $list_type == 'officer' ) {

		// toastmasters integration

		$officers = get_option( 'wp4toastmasters_officer_ids' );

		if ( ! empty( $officers ) && is_array( $officers ) ) {

			foreach ( $officers as $id ) {

				$member = get_userdata( $id );

				if ( $member ) {

					$recipients[] = strtolower( $member->user_email );
				}
			}
		}
	}

	$subject_prefix = empty( $vars['subject_prefix'] ) ? '' : $vars['subject_prefix'];

	$whitelist = ( empty( $vars['whitelist'] ) ) ? array() : group_emails_extract( $vars['whitelist'] );

	$blocked = ( empty( $vars['blocked'] ) ) ? array() : group_emails_extract( $vars['blocked'] );

	$additional_recipients = ( empty( $vars['additional_recipients'] ) ) ? array() : group_emails_extract( $vars['additional_recipients'] );

	if ( ! empty( $additional_recipients ) ) {

		foreach ( $additional_recipients as $email ) {

			if ( ! in_array( $email, $recipients ) ) {

				$recipients[] = $email;
			}
		}
	}

	// loop through each email

	for ( $n = 1; $n <= count( $headers ); $n++ ) {

		$atturls = array();

		$html .= '<h3>' . $headers[ $n - 1 ] . '</h3><br />';

		$realdata = '';

		$headerinfo = imap_headerinfo( $mail, $n );

		if ( ! empty( $headerinfo->message_id ) && rsvpmaker_relay_duplicate( $headerinfo->message_id ) ) {
			echo 'duplicate for ' . $headerinfo->message_id;
			continue; // already tried to process this, something is wrong
		}

		if ( isset( $_GET['debug'] ) ) {

			$html .= '<pre>' . "Header Info:\n" . htmlentities( var_export( $headerinfo, true ) ) . '</pre>';
		}

		$subject = '';

		if ( ! empty( $headerinfo->subject ) ) {

			$subject = $headerinfo->subject;

		} elseif ( ! empty( $headerinfo->Subject ) ) {

			$subject = $headerinfo->Subject;
		}

		$decoded = imap_mime_header_decode($subject);
        $subject = $decoded[0]->text;

		if ( ! strpos( $subject, $subject_prefix . ']' ) && ! empty( $subject_prefix ) ) {

			$subject = '[' . $subject_prefix . '] ' . $subject;
		}

		$fromname = $headerinfo->from[0]->personal;

		$from = strtolower( $headerinfo->from[0]->mailbox . '@' . $headerinfo->from[0]->host );

		if ( in_array( $from, $recipients ) ) {

			$html .= '<p>' . $from . ' is a member email</p>';

		} else {
			$html .= '<p>' . $from . ' is <strong>NOT</strong> a member email</p>';
		}

		$html .= var_export( $headerinfo->from, true );

		$html .= '<h3>' . $subject . '<br />' . $fromname . ' ' . $from . '</h3>';

		$mailqtext = rsvpmaker_get_part( $mail, $n, 'TEXT/PLAIN' );

		$mailq = rsvpmaker_get_part( $mail, $n, 'TEXT/HTML' );

		$member_user = get_user_by( 'email', $from );

		$author = ( $member_user && ! empty( $member_user->ID ) ) ? $member_user->ID : 1;

		$qpost = array(
			'post_title'  => $subject,
			'post_type'   => 'rsvpemail',
			'post_status' => 'rsvpmessage',
			'post_author' => $author,
		);

		if ( $mailq ) {

			$html .= '<p>Capturing HTML email content</p>';

			$embedded_images = rsvpmailer_embedded_images( $mailq );

			$html .= sprintf( '<p>Embedded images: %s</p>', var_export( $embedded_images, true ) );

			$html .= $mailq;

			$qpost['post_content'] = preg_replace( "|<style\b[^>]*>(.*?)</style>|s", '', $mailq );

		} else {

			$html .= '<p>Capturing TEXT email content</p>';

			$temp = wpautop( $mailqtext );

			$qpost['post_content'] = $temp;

			$html .= $temp;

		}

		$struct = imap_fetchstructure( $mail, $n );

		if ( isset( $_GET['debug'] ) ) {

			$html .= sprintf( '<h1>Structure</h1><pre>%s</pre>', var_export( $struct, true ) );
		}

		$contentParts = count( $struct->parts );

		$upload_dir = wp_upload_dir();

		$t = time();

		$path = $upload_dir['path'] . '/';

		$urlpath = $upload_dir['url'] . '/';

		$image_types = array( 'jpg', 'jpeg', 'png', 'gif' );

		$imagecount = 0;

		if ( $contentParts >= 2 ) {

			for ( $i = 2;$i <= $contentParts;$i++ ) {

				$att[ $i - 2 ] = imap_bodystruct( $mail, $n, $i );

			}

			for ( $k = 0;$k < sizeof( $att );$k++ ) {

				$attachment = $att[ $k ];

				$strFileName = $strFileType = '';

				if ( ! empty( $att[ $k ]->parameters ) && is_array( $att[ $k ]->parameters ) && ! empty( $att[ $k ]->parameters[0]->value ) ) {

					if ( ( $att[ $k ]->parameters[0]->value == 'us-ascii' ) || ( $att[ $k ]->parameters[0]->value == 'US-ASCII' ) ) {

						if ( $att[ $k ]->parameters[1]->value != '' ) {

							$strFileName = $att[ $k ]->parameters[1]->value;

						}
					} elseif ( $att[ $k ]->parameters[0]->value != 'iso-8859-1' && $att[ $k ]->parameters[0]->value != 'ISO-8859-1' ) {

									$strFileName = $att[ $k ]->parameters[0]->value;

					}
				}//end is array

				if ( strpos( $strFileName, '.' ) ) { // if it's a filename

					$p = explode( '.', $strFileName );

					$strFileType = strtolower( array_pop( $p ) );

					if ( isset( $_GET['debug'] ) ) {

						$html .= sprintf( '<p>File: %s File type: %s</p>', $strFileName, $strFileType );
					}

					if ( in_array( $strFileType, $image_types ) && ! empty( $embedded_images ) ) {

						$html .= '<p>Is an image</p>';

						// $key = key($embedded_images);

						// printf('<p>key: %s</p>',$key);

						$html .= sprintf( '<p>Checking embedded image %s</p>', $imagecount );

						if ( ! empty( $embedded_images[ $imagecount ] ) ) {

							$cid = $embedded_images[ $imagecount ];

							$html .= 'cid key: ' . $imagecount;

							$imagecount++;

						} else {

							$html .= sprintf( '<p>No CID found for %s or %s</p>', $imagecount, $strFileName );

							$cid = '';

						}

						$addtopath = $t . $k;

					}//if it's an image

					else {

						if ( isset( $_GET['debug'] ) ) {

							$html .= '<p>Not an image</p>';
						}

						$addtopath = '';

						$cid = '';

					}

					if ( isset( $_GET['debug'] ) ) {

						$html .= sprintf( '<p>Handling attachment %s %s %s %s %s %s</p>', var_export( $attachment, true ), $i, $n, var_export( $mail, true ), $path . $addtopath, $urlpath . $addtopath );
					}

					 $atturl = rsvpmaker_relay_save_attachment( $attachment, $k + 2, $n, $mail, $path . $addtopath, $urlpath . $addtopath, $strFileName, $strFileType );

					 $link = sprintf( '<a href="%s" target="_blank">%s</a>', $atturl, $strFileName );

					 $atturls[] = $link;

					if ( ! empty( $cid ) ) {

						$qpost['post_content'] = str_replace( $cid, $atturl, $qpost['post_content'] );

						$html .= printf( '<p>replace %s with %s</p>', $cid, $atturl );

					}

					if ( isset( $_GET['debug'] ) ) {

						$html .= sprintf( '<div>Attachment:</div><pre>%s</pre>', var_export( $attachment, true ) );
					}
				}// is filename

			}//loop attachments

		}// loop content parts

		// if we weren't able to substitue url for embedded images coding

		$qpost['post_content'] = preg_replace( '/<img.+cid:[^>]+>/', 'IMAGE OMMITTED', $qpost['post_content'] );

		if ( sizeof( $atturls ) > 0 ) {
			$qpost['post_content'] .= '<div style="padding: 10px; margin: 5px; background-color: #eee; border: thin solid #555;"><p><strong>Attachments:</strong> <br />' . implode( '<br />', $atturls ) . '</p></div>';
		}

		if ( isset( $_GET['nosave'] ) ) {

			echo '<h1>Version to send (not saved)</h2>' . wp_kses_post( $qpost['post_content'] );

			return;

		}

		$qpost['ID'] = wp_insert_post( $qpost );
		update_post_meta( $qpost['ID'], 'headerinfo', $headerinfo );

		if ( $list_type == 'bot' ) {
			/* pass with header info, to and cc array of objects, structure $to[0]->mailbox, $to[0]->host  */
			echo "Action call: 'rsvpmaker_autoreply'";
			do_action( 'rsvpmaker_autoreply', $qpost, $user, $from, $headerinfo->toaddress, $fromname, $headerinfo->to, $headerinfo->cc );
		}

		if ( in_array( $from, $blocked ) ) {

			$rmail['subject'] = 'BLOCKED ' . $qpost['post_title'];

			$rmail['to'] = $from;

			$rmail['html'] = '<p>Your message was not delivered to the email list.</p>';

			$rmail['from'] = get_option( 'admin_email' );

			$rmail['fromname'] = get_option( 'blogname' );

			update_option( 'rsvpmaker_relay_latest_bounce', var_export( $rmail, true ) );

			rsvpmailer( $rmail );

		} elseif ( in_array( $from, $recipients ) || in_array( $from, $whitelist ) ) {

			$qpost['post_content'] .= "\n<p>*****</p>" . sprintf( '<p>Relayed from the <a href="mailto:%s" target="_blank">%s</a> email list</p><p>Replies will go to SENDER. <a target="_blank" href="mailto:%s?subject=Re:%s">Reply to list instead</a></p>', $user, $user, $user, $subject );
			$post_id                = 0;

			if ( ! empty( $qpost['post_content'] ) && ! empty( $from ) ) {

				$post_id = wp_insert_post( $qpost );
			}

			$html .= var_export( $qpost, true );

			if ( $post_id ) {
				add_post_meta( $post_id, 'imap_message_id', $headerinfo->message_id );

				add_post_meta( $post_id, 'rsvprelay_from', $from );

				// for debugging

				add_post_meta( $post_id, 'imap_body', imap_body( $mail, $n ) );

				if ( empty( $fromname ) ) {

					$fromname = $from;
				}

				add_post_meta( $post_id, 'rsvprelay_fromname', $fromname );

				if ( ! empty( $recipients ) ) {

					foreach ( $recipients as $to ) {

						$rsvpmailer_rule = apply_filters( 'rsvpmailer_rule', '', $to, 'email_rule_group_email' );

						if ( $rsvpmailer_rule == 'permit' ) {

							add_post_meta( $post_id, 'rsvprelay_to', $to );

						} elseif ( $rsvpmailer_rule == 'deny' ) {

							rsvpmaker_debug_log( $to, 'group email blocked' );

						} elseif ( ! in_array( $to, $unsubscribed ) ) {

							add_post_meta( $post_id, 'rsvprelay_to', $to );
						}
					}
				}
			}
		} elseif ( $list_type != 'bot' ) {

			$rmail['subject'] = 'NOT DELIVERED ' . $qpost['post_title'];

			$rmail['to'] = $from;

			$rmail['html'] = '<p>Your message was not delivered because it did not come from a recognized member email address.</p><p>Reply if you also use an alternate email address that needs to be added to our whitelist.</p>';

			$rmail['from'] = get_option( 'admin_email' );

			$rmail['fromname'] = get_option( 'blogname' );

			update_option( 'rsvpmaker_relay_latest_bounce', var_export( $rmail, true ) );

			rsvpmailer( $rmail );

		}

		if ( isset( $_GET['nodelete'] ) ) {

			$html .= '<p>Not deleting</p>';

		} else {

			$html .= sprintf( '<p>Delete %s</p>', $n );

			imap_delete( $mail, $n );

		}
	}

	imap_expunge( $mail );

	$html .= '<p>Expunge deleted messages</p>';

	if($return_count)
		return count( $headers ).' messages retrieved';

	return $html;

	// end function rsvpmaker_relay_get_pop() {
}

function rsvpmailer_embedded_images( $mailq ) {

	// preg_match_all('/<img.+(cid:[^"\']+)[^>]+/',$mailq, $matches);

	preg_match_all( '/cid:[^"\']+/', $mailq, $matches );

	return $matches[0];

	foreach ( $matches[1] as $index => $cid ) {

			$img[] = $cid;

	}

	if ( empty( $img ) ) {

		return;
	}

	return $img;

}



function rsvpmaker_relay_save_attachment( $att, $file, $msgno, $mbox, $path, $urlpath, $strFileName, $strFileType ) {

		printf( '<p>Check %s %s part number %s</p>', $strFileName, $strFileType, $file );

		$allowed = array( 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'gif', 'png', 'svg', 'ics', 'ifb', 'txt' );

	if ( ! in_array( $strFileType, $allowed ) ) {

		return $strFileName . ' (file type not supported: ' . $strFileType . ')';
	}

		$fileSize = $att->bytes;

		$fileContent = imap_fetchbody( $mbox, $msgno, $file );

		$ContentType = 'application/octetstream';

	if ( $strFileType == 'txt' ) {

		$ContentType = 'text/plain';
	}

	if ( ( $strFileType == 'ics' ) || ( $strFileType == 'ifb' ) ) {

		$ContentType = 'text/calendar';
	}

	if ( isset( $_GET['debug'] ) ) {

		printf( '<p>File characteristics: %s %s %s</p>', $ContentType, $strFileName, $fileSize );
	}

	$strFileName = preg_replace('/[^\.a-zA-Z0-9]/','_',$strFileName);

	$writepath = $path . $strFileName;

	$url = $urlpath . $strFileName;

	if ( substr( $ContentType, 0, 4 ) == 'text' ) {

		$content = imap_qprint( $fileContent );

	} else {

		$content = imap_base64( $fileContent );

	}

	 file_put_contents( $writepath, $content );

	if ( isset( $_GET['debug'] ) ) {

		printf( '<p>Writing to %s <a href="%s" target="_blank">%s</a></p>', $writepath, $url, $url );
	}

	 return $url;

}

add_filter( 'cron_schedules', 'rsvpmaker_relay_interval' );

function rsvpmaker_relay_interval( $schedules = array() ) {

	$schedules['minute'] = array(

		'interval' => 60,

		'display'  => esc_html__( 'Every Minute' ),
	);

	$schedules['doubleminute'] = array(

		'interval' => 120,

		'display'  => esc_html__( 'Every Two Minutes' ),
	);

	return $schedules;

}

function rsvpmaker_relay_duplicate( $message_id ) {
	global $wpdb;
	return $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value='$message_id' " );
}

function rsvpmaker_qemail ($mail, $recipients) {
	$recipients = rsvpmaker_recipients_no_problems($recipients);
	global $current_user;
	if(is_multisite()) // send through root blog
	{
		if(!rsvpmaker_postmark_is_active())
			switch_to_blog(1);
	}	
	if(strpos($mail['html'],'<body')) {
		preg_match('|<bod[^>]+>(.+)</body>|is',$mail['html'],$match);
		if(!empty($match[1])) {
			$_html = $mail['html'];
			$mail['html'] = $match[1];	
		}
	}
	$qpost['post_content'] = $mail['html'];
	$qpost['post_title'] = $mail['subject'];
	$qpost['post_type'] = 'rsvpemail';
	$qpost['post_author'] = $current_user->ID;
	$qpost['post_status'] = 'rsvpmessage';
	if(!empty($mail['post_id']))
		$qpost['ID'] = $mail['post_id'];
	$from = $mail['from'];
	$fromname = $mail['fromname'];

	if(!empty($qpost['post_content']) && !empty($from))  
		$post_id = wp_insert_post($qpost);

	if($post_id) {
		//add_post_meta($post_id,'imap_message_id',$headerinfo->message_id);
		add_post_meta($post_id,'rsvprelay_from',$from);
		if(empty($fromname))
			$fromname = $from;
		if(!empty($_html))
			add_post_meta($post_id,'_rsvpmail_html',$_html);
		add_post_meta($post_id,'rsvprelay_fromname',$fromname);
		if(rsvpmaker_postmark_is_active()) {
			rsvpmaker_postmark_broadcast($recipients,$post_id);
		}
		else {
			foreach($recipients as $email)
				add_post_meta($post_id,'rsvprelay_to',$email);
		}
		$mail['html'] = 'hidden';
	}
	if(is_multisite())
		restore_current_blog();
}

function rsvpmaker_relay_queue_monitor () {
	rsvpmaker_admin_heading(__('Group Email Log','rsvpmaker'),__FUNCTION__);

	do_action('rsvpmaker_relay_queue_monitor');
	global $wpdb;
	if(isset($_GET['cancel'])) {
		$sql = "DELETE FROM $wpdb->postmeta where post_id=".intval($_GET['cancel'])." AND meta_key='rsvprelay_to' ";
		$result = $wpdb->query($sql);
	}
	$sql = "SELECT ID, post_title, $wpdb->postmeta.meta_key, $wpdb->postmeta.meta_value FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE post_type='rsvpemail' AND (post_status='draft' OR post_status='publish'  OR post_status='rsvpmessage') AND (meta_key LIKE 'rsvpmail%' OR meta_key LIKE 'rsvprelay%') ORDER BY ID DESC";
	$results = $wpdb->get_results($sql);
	$was = 0;
	$action = admin_url('edit.php?post_type=rsvpemail&page=rsvpmaker_relay_queue_monitor&cancel=');
	echo '<h2>In Queue / Sent</h2>';
	if(empty($results))
		echo '<p>none</p>';
	else
	foreach($results as $row)
	{
		if($row->ID != $was)
			printf('<h2>%s</h2>',$row->post_title);
		if('rsvprelay_to' == $row->meta_key) {
			$row->meta_key ='<strong>'.$row->meta_key.'</strong>';
			printf('<p>%s %s <a href="%s%d">(cancel)</a></p>',$row->meta_key, $row->meta_value,$action,$row->ID);
		}
		else
			printf('<p>%s %s</p>',$row->meta_key, $row->meta_value);
		$was = $row->ID;
	}

	$sql = "SELECT ID, post_title, meta_key, meta_value, post_status FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE post_type='rsvpemail' AND (meta_key='headerinfo' OR meta_key='rsvpmail_sent') AND post_status='draft' ORDER BY ID DESC";
	$results = $wpdb->get_results($sql);
	$was = 0;
	foreach($results as $row)
	{
		if($row->ID != $was) {
			if('draft' == $row->post_status) {
				$sql = "update $wpdb->posts SET post_status='rsvpmessage' WHERE ID=$row->ID ";
				$result = $wpdb->query($sql);
				print_r("<p>post status change result %s %s</p>",$sql,var_export($result,true));
			}
		}
		$was = $row->ID;
	}

	echo '<h2>Sent (200 Latest)</h2>';
	$sql = "SELECT ID, post_title, meta_key, meta_value, post_status FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE post_type='rsvpemail' AND meta_key='rsvpmail_sent' ORDER BY ID DESC LIMIT 0, 200";
	$results = $wpdb->get_results($sql);
	$was = 0;
	foreach($results as $row)
	{
		if($row->ID != $was) {
			printf('<h2>%s status: %s</h2>',$row->post_title, $row->post_status);
			if('draft' == $row->post_status) {
				$sql = "update $wpdb->posts SET post_status='rsvpmessage' WHERE ID=$row->ID ";
				print_r("<p>post status change result %s %s</p>",$sql,var_export($result,true));
			}
			$result = $wpdb->query($sql);
		}
		printf('<p>%s %s</p>',$row->meta_key, $row->meta_value);
		$was = $row->ID;
	}
}

function hosts_and_subs_test () {
	if(wp_is_json_request())
		return;
	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
	$output = '';
	$test = array('op@toastmost.org','op-officers@toastmost.org','members@digitalcommunicators.org','members-digitalcommunicators.com@gmail.com','admins@toastmost.org');
	foreach($test as $email) {
		$slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
		$output .= sprintf('<p>%s %s</p>',$email,var_export($slug_and_id,true));
	}
	$output .= var_export($hosts_and_subdomains,true);
	return $output;
}

function rsvpmaker_get_hosts_and_subdomains() {
    global $wpdb, $hosts_and_subdomains;
	$hostalias = [];
	if(!empty($hosts_and_subdomains))
		return $hosts_and_subdomains;
	if(is_multisite()) {
		$basedomain = parse_url(get_blog_option(1, 'siteurl'), PHP_URL_HOST );
	}
    else
		$basedomain = parse_url( get_site_url(), PHP_URL_HOST );
	$basedomain = str_replace('www.','',$basedomain);
 	$hosts_and_subdomains = array('basedomain' => $basedomain, 'hosts' => array(),'subdomains' => array());
	if(!is_multisite()) {
		return $hosts_and_subdomains;
	}
        $sql = "SELECT * FROM $wpdb->blogs WHERE blog_id > 1";
        $results = $wpdb->get_results($sql);
        foreach($results as $site) {
			if(strpos($site->domain,$basedomain)) {
				$hosts_and_subdomains['subdomains'][$site->blog_id] = str_replace('.'.$basedomain,'',$site->domain);
			}
			else {
	            $hosts_and_subdomains['hosts'][$site->blog_id] = $site->domain;
				$parts = explode('.',$site->domain);
				//if base is toastmost.org and domain is tragicomedy.org, then tragicomedy@toastmost.org should still be recognized
				$hostalias[$site->blog_id] = $parts[0];
			}
        }
        restore_current_blog();
		if(sizeof($hostalias))
		{	
				//print_r($hostalias);
			foreach($hostalias as $blog_id => $h) {
				if(!in_array($h, $hosts_and_subdomains['subdomains'])) {
					$hosts_and_subdomains['subdomains'][$blog_id] = $h;
				}	
			}
		}
    return $hosts_and_subdomains;
}

function rsvpmail_recipients_by_slug_and_id($slug_and_id,$emailobj = NULL) {
	foreach($emailobj->ToFull as $one) {
		$addresses[] = $one->Email;
	}
	foreach($emailobj->CcFull as $one) {
		$addresses[] = $one->Email;
	}
	$from = $emailobj->From;
	$recipients = array();
	$recipient_names = array();
	$recipients = apply_filters('rsvpmail_recipients_from_forwarders',$recipients,$slug_and_id,$from,$addresses);

	if(empty($recipients) && ('members' == $slug_and_id['slug']) && get_option('rsvpmaker_discussion_active')) {
		$users = get_users('blog_id='.$slug_and_id['blog_id']);
		$vars = get_option( 'rsvpmaker_discussion_members');
		$blocked = ( empty( $vars['blocked'] ) ) ? array() : group_emails_extract( $vars['blocked'] );
		$whitelist = ( empty( $vars['whitelist'] ) ) ? array() : group_emails_extract( $vars['whitelist'] );
		$additional_recipients = ( empty( $vars['additional_recipients'] ) ) ? array() : group_emails_extract( $vars['additional_recipients'] );
		foreach($users as $user) {
			if(!rsvpmail_is_problem($user->user_email) && !in_array($user->user_email,$blocked)) {
				$email = $user->user_email;
				if(!empty($user->display_name)) {
					$recipient_names[$email] = $user->display_name;
				}
				$recipients[] = $email;
			}
		}
		$recipients = array_merge($recipients,$additional_recipients,$whitelist);
		if(!in_array(strtolower($from),$recipients))
			return 'BLOCKED'; //NOT FROM A RECOGNIZED MEMBER ADDRESS
		set_transient('recipient_names',$recipient_names);
	}
	return $recipients;
}

function rsvpmail_slug_and_id($email, $hosts_and_subdomains) {
	global $message_blog_id, $via;
	$via = ' (via '.$email.')';
	$slug_and_id = apply_filters('rsvpmail_slug_and_id',array('slug' => '','blog_id'=>0, 'forwarder' => $email),$email);
	if($slug_and_id['blog_id'])
		return $slug_and_id;
	$eparts = explode('@',$email);
	if(is_multisite()) {
		$message_blog_id = array_search($eparts[1],$hosts_and_subdomains['hosts']);
		if($message_blog_id) {
			return array('slug' => $eparts[0],'blog_id' => $message_blog_id, 'forwarder' => $email);
		}
		$nameparts = explode('-',$eparts[0]);
		$message_blog_id = array_search($nameparts[0],$hosts_and_subdomains['subdomains']);
		if($message_blog_id && ($eparts[1] == $hosts_and_subdomains['basedomain'])) {
			$slug = (empty($nameparts[1])) ? 'members' : $nameparts[1];
			return array('slug' => $slug,'blog_id' => $message_blog_id, 'forwarder' => $email);
		}	
	}
	if($eparts[1] == $hosts_and_subdomains['basedomain']) {
		return array('slug' => $eparts[0],'blog_id' => 1, 'forwarder' => $email);
	}
	return $slug_and_id;
}

add_shortcode('hosts_and_subs_test','hosts_and_subs_test');

function rsvpmaker_expand_recipients($email) {
	global $expand_done;
	if($expand_done)
		return $email;// don't get caught in loops
	$expand_done = true;
    $emailparts = explode('@',$email);
	$hosts_and_subdomains = rsvpmaker_get_hosts_and_subdomains();
    if(in_array($emailparts[1],$hosts_and_subdomains) || ($emailparts[1]==$hosts_and_subdomains['basedomain']))
    {
        $slug_and_id = rsvpmail_slug_and_id($email, $hosts_and_subdomains);
        $recipients = rsvpmail_recipients_by_slug_and_id($slug_and_id);
    }
    if($recipients)
        return $recipients;
    else
        return $email;
}

function rsvpmaker_recipients_no_problems($recipients) {
	$additional_recipients = $cleanrecipients = array();
    foreach($recipients as $email) {
		$email = rsvpmaker_expand_recipients($email);
		if(is_array($email))
			$additional_recipients = array_merge($email,$additional_recipients);
	}
	if(!empty($additional_recipients))
		$recipients = array_merge($recipients,$additional_recipients);
	foreach($recipients as $email) {
		if(!rsvpmail_is_problem($email) && !in_array($email,$cleanrecipients))
			$cleanrecipients[] = $email;	
	}
    return $cleanrecipients;
}

function rsvpmail_email_to_parts($email) {
	global $wpdb;
	preg_match('/([^@-]+)-{0,1}(.*)@(.+)/',$email,$match);
	if(is_multisite()) {
		$blog_id = get_blog_option(1,'old_email_subdomain_'.$match[1]);
		if(!$blog_id) {
			$sql = $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE domain=%s",$match[1].'.'.$match[3]);
			$blog_id = $wpdb->get_var($sql);	
		}
		if($blog_id) {
			$fkey = empty($match[2]) ? 'members' : $match[2];
			return array('subdomain'=>$match[1],'fwdkey'=>$fkey,'domain'=>$match[3],'blog_id'=>$blog_id);
		}
		else {
			//root domain or mapped domain
			$sql = $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE domain=%s",$match[3]);
			$blog_id = $wpdb->get_var($sql);
			if($blog_id)	
				return array('subdomain'=>'','fwdkey'=>$match[1],'domain'=>$match[3],'blog_id'=>$blog_id);
			else {
				$sql = $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE domain=www.%s",$match[3]);
				$blog_id = $wpdb->get_var($sql);
				if($blog_id)	
					return array('subdomain'=>'','fwdkey'=>$match[1],'domain'=>$match[3],'blog_id'=>$blog_id);	
			}
			}
		return false;
	}
	//not multisite
	if($match[3] == str_replace('www.','',parse_url(get_site_url(),PHP_URL_HOST)))
		return array('subdomain'=>'','fwdkey'=>$match[1],'domain'=>$match[3],'blog_id'=>1);
	else
		return false;
}

function rsvpmail_get_consolidated_forwarders($blog_id, $subdomain, $domain) {
    $join = ($subdomain) ? '-' : '';
    $slug_ids = get_officer_slug_ids($blog_id);
    if($slug_ids) {
        foreach($slug_ids as $slug => $slug_id) {
        foreach($slug_id as $user_id) {
                if($user_id) {
                    $officer = get_userdata($user_id);
                    $recipients[$subdomain.$join.$slug.'@'.$domain][] = $officer->user_email;
                }
            }
        }
    }
    $forward_by_id = (is_multisite()) ? get_blog_option($blog_id,'wpt_forward_general') : get_option('wpt_forward_general');
    $basecamp = (is_multisite()) ? get_blog_option($blog_id,'wpt_forward_basecamp') : get_option('wpt_forward_basecamp');
    if($forward_by_id) {
        $ffemail = (is_multisite()) ? get_blog_option($blog_id,'findafriend_email') : get_option('findafriend_email');
        $recipients[$ffemail] = [];
        foreach($forward_by_id as $forwarder => $email) {
            if(empty($recipients[$email]))
                $recipients[$ffemail][] = $email;
            else
                foreach($recipients[$email] as $f)
                    $recipients[$ffemail][] = $f;
            }
    }
    if($basecamp) {
        $recipients[$subdomain.$join.'basecamp@'.$domain] = [];
        foreach($basecamp as $forwarder => $email) {
            if(empty($recipients[$email]))
                $recipients[$subdomain.$join.'basecamp@'.$domain][] = $email;
            else
                foreach($recipients[$email] as $f)
                    $recipients[$subdomain.$join.'basecamp@'.$domain][] = $f;
        }
    }

    $custom_forwarders = (is_multisite()) ? get_blog_option($blog_id,'custom_forwarders') : get_option('custom_forwarders');
    if(!empty($custom_forwarders))
    {
        foreach($custom_forwarders as $forwarder => $emails) {
			$recipients[$forwarder] = [];
            foreach($emails as $email) {
                if(!empty($recipients[$email])) {
                    $recipients[$forwarder] = array_merge($recipients[$forwarder], $recipients[$email]);
                }
                else {
                    $recipients[$forwarder][] = $email;
                }
            }
        }
    }

    $members_on = (is_multisite() && $blog_id) ? get_blog_option($blog_id,'member_distribution_list', true) : get_option('member_distribution_list', true);
    $officers_on = (is_multisite()) ? get_blog_option($blog_id,'officer_distribution_list') : get_option('officer_distribution_list');

    if($members_on) {
        $listvars = (is_multisite() && $blog_id) ? get_blog_option($blog_id,'member_distribution_list_vars') : get_option('member_distribution_list_vars');
        $list_email = ($subdomain) ? $subdomain.'@'.$domain : "members@".$domain;
        $recipients[$list_email] = rsvpmail_get_member_emails($blog_id);
        if(!empty($listvars['additional']))
        foreach($listvars['additional'] as $email) {
            $recipients[$list_email][] = $email;
        }
        $recipients[$list_email.'_whitelist'] = $listvars['whitelist'];
    }

    if($officers_on) {
        $listvars = (is_multisite() && $blog_id) ? get_blog_option($blog_id,'officer_distribution_list_vars') : get_option('officer_distribution_list_vars');
        $list_email = ($subdomain) ? $subdomain.'-officers@'.$domain : "officers@".$domain;
        $officers = (is_multisite() && $blog_id) ? get_blog_option($blog_id,'wp4toastmasters_officer_ids') : get_option('wp4toastmasters_officer_ids');

        if($officers && is_array($officers)) {

            foreach($officers as $id) {

                $member = get_userdata($id);

                if($member) {

                    $email = strtolower($member->user_email);

                    $recipients[$list_email][] = $email;

                }

            }
            if(!empty($listvars['additional']))
            foreach($listvars['additional'] as $email) {
                $recipients[$list_email][] = $email;
            }
            $recipients[$list_email.'_whitelist'] = $listvars['whitelist'];
		}
    }
	$admin_email = (is_multisite() && $blog_id) ? get_blog_option($blog_id,'admin_email') : get_option('admin_option');
    $recipients[$subdomain.$join.'admin@'.$domain] = array($admin_email);
	$recipients = apply_filters('rsvpmaker_consolidated_forwarders',$recipients, $blog_id);
	return $recipients;
}

function rsvpmail_get_member_emails( $blog_id = 0 ) {

	if ( empty( $blog_id ) ) {

		$blog_id = get_current_blog_id();
	}

	$members = get_users(
		array(
			'blog_id' => $blog_id,
			'orderby' => 'display_name',
		)
	);

	$emails = array();

	foreach ( $members as $member ) {
		$emails[] = strtolower( $member->user_email );
	}
	return $emails;
}
