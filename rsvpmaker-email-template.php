<?php

/**

 * The template for displaying eblast previews.
 */
global $post;
global $email_styles;
global $custom_fields;
global $email_context;
global $chimp_options;
global $wp_query;
global $email_context;
$email_context = true;

$mail['html'] = $html = rsvpmaker_template_inline();

$text = get_post_meta($post->ID,'_rsvpmail_text',true);

	$cron = get_post_meta( $post->ID, 'rsvpmaker_cron_email', true );
	$subject = $post->post_title;

	$notekey = get_rsvp_notekey();

	$chosen = (int) get_post_meta( $post->ID, $notekey, true );

if ( $chosen ) {

	$notepost = get_post( $chosen );

	$editorsnote['add_to_head'] = $notepost->post_title;

	$postparts = explode( '<!--more-->', $notepost->post_content );

	$note = str_replace( '<!-- wp:more -->', '', $postparts[0] );

	if ( ! empty( $postparts[1] ) ) {

		$note .= sprintf( '<p><a href="%s">%s</a>', get_permalink( $chosen ), __( 'Read more', 'rsvpmaker' ) );
	}

	$editorsnote['note'] = $note;

	if ( ! empty( $editorsnote['add_to_head'] ) ) {

		$subject .= ' - ' . $editorsnote['add_to_head'];
	}

	if ( ! empty( $editorsnote['note'] ) ) {

		if ( ! strpos( $editorsnote['note'], '</p>' ) ) {

			$editorsnote['note'] = wpautop( $editorsnote['note'] );
		}

		$html = str_replace( '<!-- editors note goes here -->', '<h2>' . $editorsnote['add_to_head'] . "</h2>\n" . $editorsnote['note'], $html );

		$text = $editorsnote['add_to_head'] . "\n\n" . strip_tags( $editorsnote['note'] ) . "\n\n" . $text . "\n\n";

	}
}

global $rsvpmaker_cron_context;

if ( isset( $_GET['cronic'] ) && current_user_can( 'publish_rsvpemails' ) ) {

	$rsvpmaker_cron_context = (int) $_GET['cronic'];
}

$cron_active = empty( $cron['cron_active'] ) ? 0 : $cron['cron_active'];

$cron_active = apply_filters( 'rsvpmaker_cron_active', $cron_active, $cron );

if ( ! empty( $_GET['debug'] ) ) {

	echo "<p>active: $cron_active </p>";
}

if ( $rsvpmaker_cron_context && $cron_active ) {

	$scheduled_email = get_post_meta( $post->ID, 'scheduled_email', true );

	$chimp_options = get_option( 'chimp', array() );

	if ( ! empty( $scheduled_email ) ) {

		$from_name = $scheduled_email['email-name'];

		$from_email = $scheduled_email['email-from'];

		$previewto = $scheduled_email['preview_to'];

		$chimp_list = $scheduled_email['list'];

	} elseif ( ! empty( $custom_fields['_email_from_name'][0] ) && ! empty( $custom_fields['_email_from_email'][0] ) ) {

		$from_name = $custom_fields['_email_from_name'][0];

		$from_email = $custom_fields['_email_from_email'][0];

		$previewto = $custom_fields['_email_preview_to'][0];

		$chimp_list = $custom_fields['_email_list'][0];

	} else {

		$from_name = $chimp_options['email-name'];

		$from_email = $chimp_options['email-from'];

	}

	if ( empty( $from_email ) ) {

		return;
	}

	if ( $cron['cron_mailchimp'] && ( $rsvpmaker_cron_context == 2 ) ) {

		$MailChimp = new MailChimpRSVP( $chimp_options['chimp-key'] );

		$campaign = $MailChimp->post(
			'campaigns',
			array(

				'type'       => 'regular',

				'recipients' => array( 'list_id' => $chimp_list ),

				'settings'   => array(
					'subject_line' => $subject,
					'from_email'   => $from_email,
					'from_name'    => $from_name,
					'reply_to'     => $from_email,
				),

			)
		);

		if ( ! $MailChimp->success() ) {

			echo '<div>' . __( 'MailChimp API error', 'rsvpmaker' ) . ': ' . $MailChimp->getLastError() . '</div>';
			return;

		}



		if ( $campaign['id'] ) {
			$html = str_replace('<!-- mailchimp -->','<a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a><br>',$html);

			$content_result = $MailChimp->put(
				'campaigns/' . $campaign['id'] . '/content',
				array(

					'html' => $html,
					'text' => $text,
				)
			);

			if ( ! $MailChimp->success() ) {

				echo '<div>' . __( 'MailChimp API error', 'rsvpmaker' ) . ': ' . $MailChimp->getLastError() . '</div>';
				return;

			}

			// print_r($content_result);

			$send_result = $MailChimp->post( 'campaigns/' . $campaign['id'] . '/actions/send' );

			// print_r($send_result);

			if ( $MailChimp->success() ) {
				$chimpmsg = __( 'Sent MailChimp campaign', 'rsvpmaker' ) . ': ' . $campaign['id'];
				echo '<div>' . $chimpmsg . '</div>';
				add_post_meta( $post->ID, 'rsvp_mailchimp_sent', $chimpmsg . ' ' . rsvpmaker_date( 'r' ) );
			} else {
				echo '<div>' . __( 'MailChimp API error', 'rsvpmaker' ) . ': ' . $MailChimp->getLastError() . '</div>';
			}
		}
	}

	if ( $cron['cron_members'] && ( $rsvpmaker_cron_context == 2 ) ) {

		$users = get_users();

		if ( is_array( $users ) ) {

			foreach ( $users as $user ) {

				$mail['to'] = $user->user_email;

				$mail['from'] = $from_email;

				$mail['fromname'] = $from_name;

				$mail['subject'] = $subject;

				$result = rsvpmailer( $mail, '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

				// print_r($result);

			}
		}
	}



	if ( ! empty( $cron['cron_to'] ) && ( $rsvpmaker_cron_context == 2 ) ) {

			$mail['to'] = $cron['cron_to'];

			$mail['from'] = $from_email;

			$mail['fromname'] = $from_name;

			$mail['subject'] = $subject;

			$result = rsvpmailer( $mail, '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

			// print_r($result);

	}

	if ( $cron['cron_preview'] && ( $rsvpmaker_cron_context == 1 ) ) {

			$mail['to'] = $previewto;

			$mail['from'] = $from_email;

			$mail['fromname'] = $from_name;

			$mail['subject'] = 'PREVIEW:' . $subject;

			$result = rsvpmailer( $mail, '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

			update_option( 'rsvpmaker_cron_preview_result', $result . ': ' . var_export( $mail, true ) );

	}
}
$html = preg_replace('/<img [^>]+srcset[^>]+>/m','',$html);
$html = preg_replace('/<\/{0,1}noscript>/','',$html);

$preview = str_replace( '*|MC:SUBJECT|*', 'Email: ' . $post->post_title, $html );
$preview = str_replace('</head>',"<link rel='stylesheet' href=".'"'.admin_url('load-styles.php?c=1&amp;dir=ltr&amp;load%5Bchunk_0%5D=dashicons,admin-bar').'" type="text/css" media=\'all\' />
<style>
#email-content {
	max-width: 800px;
	margin-left:auto;
	margin-right: auto;
	background-color: #fff;
	color: #000;
	padding: 10px;
}
#control-wrapper, #control-wrapper p, #control-wrapper div {
	font-size: 16px;
	font-family: Arial;
	font-style: normal;
	font-weigth: normal;
}
button {
	background-color: darkblue;
	color: white;
	border-radius: 5px;
}
</style>', $preview);
$preview = preg_replace( '/<body[^>]*>/', '$0' . '<div id="email-preview-background" style="width: 100%; margin: 0; padding-top: 50px; color: #fff; background-color: #000;"><div id="email-preview-wrapper" style="max-width: 700px; margin-left: auto; margin-right: auto; color: #000; background-color: #fff; padding-top: 5px;border-radius: 25px; padding:25px; margin-bottom: 25px;">', $preview );
$preview = str_replace('</body>','</div></div></body>',$preview);

if(isset($_GET['cancel_promo']) && rsvpmaker_verify_nonce())
	wp_unschedule_hook('rsvpmailer_post_promo');

if ( isset( $_GET['template_preview'] ) ) {

		$preview = rsvpmaker_personalize_email( $preview, 'david@carrcommunications.com', '<div class="rsvpexplain">This message is a demo.</div>' );

} elseif ( current_user_can( 'publish_rsvpemails' ) ) {
		$preview = str_replace('<!-- controls go here -->',rsvpmaker_email_send_ui( $html, $text ),$preview);
}
$preview = rsvpmaker_personalize_email($preview,'rsvpmaker@example.com');
/* cannot be escaped because of embedded form content. Escaping belongs in the functions that create this output variable */
echo $preview;