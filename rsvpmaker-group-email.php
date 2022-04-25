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


function rsvpmaker_relay_menu_pages() {

	$parent_slug = 'edit.php?post_type=rsvpemail';

	add_submenu_page(
		$parent_slug,
		__( 'Group Email', 'rsvpmaker' ),
		__( 'Group Email', 'rsvpmaker' ),
		'manage_options',
		'rsvpmaker_relay_manual_test',
		'rsvpmaker_relay_manual_test'
	);

	add_submenu_page(
		$parent_slug,
		__( 'Group Email Log', 'rsvpmaker' ),
		__( 'Group Email Log', 'rsvpmaker' ),
		'manage_options',
		'rsvpmaker_relay_queue_monitor',
		'rsvpmaker_relay_queue_monitor'
	);

}

add_action( 'admin_menu', 'rsvpmaker_relay_menu_pages' );

function rsvpmaker_relay_manual_test() {

	echo '<h1>' . __( 'Manually Trigger Check of Email Lists', 'rsvpmaker' ) . '</h1>';

	$html = rsvpmaker_relay_init( true );

	if ( $html ) {

		echo wp_kses_post( $html );

	} else {
		echo '<p>' . __( 'No messages', 'rsvpmaker' ) . '</p>';
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

	if ( empty( $qresult ) ) {

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

	if ( $show ) {

		return $qresult . $result;
	}

	if ( ! empty( $qresult ) || strpos( $result, 'Mail:' ) ) {

		rsvpmaker_debug_log( $qresult . $result, 'rsvpmaker_relay_result' );

	}

}

function rsvpmaker_relay_queue() {
	global $wpdb, $post, $page, $pages;

	$rsvpmaker_message_type = 'email_rule_group_email';
	//select a message with pending sends
	$sql = "SELECT ID FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE meta_key='rsvprelay_to' AND (post_status='publish' OR post_status='draft')";
	$epost_id = $wpdb->get_var($sql);
	if(empty($epost_id))
		return;
	$sql = "SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE ID=$epost_id AND meta_key='rsvprelay_to' AND (post_status='publish' OR post_status='draft') ";
	$results = $wpdb->get_results( $sql );
	if ( empty( $results ) ) {
		return;
	}
	$total_to_send = sizeof($results);
	$limit = 10;
	$sent = 0;

	$html = '<p>Results: ' . sizeof( $results ) . '</p>';

	$mail['message_type'] = 'email_rule_group_email';
	$mail['override'] = 1;
	$mail['from'] = get_post_meta( $epost_id, 'rsvprelay_from', true );;
	$mail['fromname'] = get_post_meta( $epost_id, 'rsvprelay_fromname', true );
	$message_description = get_post_meta( $epost_id, 'message_description', true );
	$mail['html'] = get_post_meta($epost_id,'_rsvpmail_html',true); //rsvpmail broadcast
	//rsvpmaker_debug_log($mail['html'],'_rsvpmail_html_for_'.$epost_id);
	if(empty($mail['html']))
	{
		$post = get_post($epost_id);
		$template = '<html>
		<head>
		<title>*|MC:SUBJECT|*</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		</head>
		<body>
		
		[rsvpmaker_email_content]
		
		<div id="messagefooter" style="padding: 5px; background-color: #eee; color: #000;">
		*|LIST:DESCRIPTION|*<br>
		<br>
		<a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
		</div>
		
		</body>
		</html>';
		$mail['html'] = do_blocks( do_shortcode( $template ) );
		rsvpmaker_debug_log($mail,'group_email_from_template');
	}
	else
		rsvpmaker_debug_log($mail,'group_email_from_meta');
	$count = 0;
	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			if($count == $limit)
				break;
			$count++;
			if ( ! isset( $_GET['nodelete'] ) ) {
				$sql = "DELETE FROM $wpdb->postmeta WHERE meta_id=" . $row->meta_id;
				$wpdb->query( $sql );
			}
			if ( rsvpmaker_cronmail_check_duplicate( $row->meta_value . $row->post_content ) ) {
				$html .= '<div>skipped duplicate to ' . esc_html( $row->meta_value ) . '</div>';
				continue;
			}
			if ( empty( $row->post_title ) || empty( $row->post_content ) ) {
				continue;
			}
			if ( ! empty( $attachments ) ) {
				$mail['attachments'] = $attachments;
			}
			$mail['subject'] = $row->post_title;
			$mail['to'] = $row->meta_value;
			$html .= sprintf( '<p>%s to %s</p>', $row->post_title, $row->meta_value );
			$post = get_post( $row->ID );
			$post_id = $row->ID;
			if ( isset( $_GET['debug'] ) ) {
				printf( '<pre>%s</pre>', htmlentities( $template ) );
				printf( '<pre>%s</pre>', htmlentities( $mail['html'] ) );
			}
			rsvpmailer( $mail, '<div class="rsvpexplain">' . $message_description . '</div>' );
			add_post_meta( $post->ID, 'rsvpmail_sent', $mail['to'] . ' ' . rsvpmaker_date( 'r' ) );
			sleep(2);
		}
		//delete old transients used to prevent duplicate sends
		$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_cronemail%' AND (option_value < ".(time() - DAY_IN_SECONDS) ." OR option_value LIKE '%@%' )";
		$wpdb->query($sql);
		return $html;
	}
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



function rsvpmaker_relay_get_pop( $list_type = '' ) {

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
			'post_status' => 'draft',
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

		$atturls = array();

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

		$log_id = wp_insert_post( $qpost );
		update_post_meta( $log_id, 'headerinfo', $headerinfo );

		// (($list_type == 'extra') && in_array('autoresponder@example.com',$additional_recipients))
		if ( $list_type == 'bot' ) {
			echo "Action call: 'rsvpmaker_autoreply'";
			do_action( 'rsvpmaker_autoreply', $qpost, $user, $from, $headerinfo->toaddress, $fromname, $headerinfo->to );
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

function rsvpmaker_relay_interval( $schedules ) {

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
	global $current_user;
	if(is_multisite()) // send through root blog
		switch_to_blog(1);
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
	$qpost['post_status'] = 'draft';
	$from = $mail['from'];
	$fromname = $mail['fromname'];

	if(!empty($qpost['post_content']) && !empty($from))  
		$post_id = wp_insert_post($qpost);

	if($post_id) {
		//add_post_meta($post_id,'imap_message_id',$headerinfo->message_id);
		add_post_meta($post_id,'rsvprelay_from',$from);
		foreach($recipients as $email)
			add_post_meta($post_id,'rsvprelay_to',$email);
		//for debugging
		//add_post_meta($post_id,'imap_body',imap_body($mail,$n));
		if(empty($fromname))
			$fromname = $from;
		add_post_meta($post_id,'rsvprelay_fromname',$fromname);
		if(!empty($_html))
			add_post_meta($post_id,'_rsvpmail_html',$_html);
		$mail['html'] = 'hidden';
		rsvpmaker_debug_log($mail,'rsvpmaker_qemail_mail_array');
		rsvpmaker_debug_log($recipients,'rsvpmaker_qemail_recipients_added');
	}
	rsvpmaker_debug_log($post_id,'rsvpmaker_qemail_insert_post_result');
	if(is_multisite())
		restore_current_blog();
}

function rsvpmaker_relay_queue_monitor () {
	do_action('rsvpmaker_relay_queue_monitor');
	global $wpdb;
	$sql = "SELECT ID, post_title, wpt_postmeta.meta_key, wpt_postmeta.meta_value FROM `wpt_posts` JOIN wpt_postmeta on wpt_posts.ID = wpt_postmeta.post_id WHERE post_type='rsvpemail' AND (post_status='draft' OR post_status='publish') AND meta_key='rsvprelay_to' ORDER BY ID DESC";
	$results = $wpdb->get_results($sql);
	$was = 0;
	echo '<h1>In Queue</h2>';
	if(empty($results))
		echo '<p>none</p>';
	else
	foreach($results as $row)
	{
		if($row->ID != $was)
			printf('<h2>%s</h2>',$row->post_title);
		printf('<p>%s %s</p>',$row->meta_key, $row->meta_value);
		$was = $row->ID;
	}
	echo '<h1>Sent (200 Latest)</h2>';
	$sql = "SELECT ID, post_title, wpt_postmeta.meta_key, wpt_postmeta.meta_value FROM `wpt_posts` JOIN wpt_postmeta on wpt_posts.ID = wpt_postmeta.post_id WHERE post_type='rsvpemail' AND meta_key='rsvpmail_sent' ORDER BY ID DESC LIMIT 0, 200";
	$results = $wpdb->get_results($sql);
	$was = 0;
	foreach($results as $row)
	{
		if($row->ID != $was)
			printf('<h2>%s</h2>',$row->post_title);
		printf('<p>%s %s</p>',$row->meta_key, $row->meta_value);
		$was = $row->ID;
	}
}