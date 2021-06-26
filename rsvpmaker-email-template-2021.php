<?php

/**

 * The template for displaying eblast previews.
 */

$email_context = true;
email_content_minfilters();
//no javascript
$wp_scripts = wp_scripts();
foreach ( $wp_scripts->registered as $wp_script ) {
	wp_deregister_script( $wp_script->handle );
}

ob_start();
wp_head();
$head = ob_get_clean();

	global $post;
	global $email_styles;
	global $custom_fields;

	global $email_context;

	global $chimp_options;

	global $wp_query;

	$email_context = true;

	ob_start();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?> >
	<head>
	<title>*|MC:SUBJECT|*</title>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php //wp_head(); ?>
	<style id="imported">
<?php
rsvpmailer_combine_styles(true);
?>
</style>
<?php
//rsvpmailer_wp_head();
?>
<style>
	#email-content {
		background-color: #fff !important;
		color: #000 !important;
		max-width: 600px;
		margin-left: auto;
		margin-right: auto;
		padding: 10px;
	}
	</style>

	</head>
	<body class="rsvpmailer">
	<!-- controls go here -->
	<article>
	<div class="entry-content">
	<div id="email-content">

	<!-- editors note goes here -->

		<?php
		//print_r($post);
		the_post();
		the_content();
		?>

	<!-- footer -->

	</div>
	</div>
	</article>
	</body>
	</html>
	<?php
	$content = ob_get_clean();
	$content = rsvpmailer_clean_css($content);
	$content = rsvpmaker_inliner( $content );
	//$content = preg_replace('/<style id="imported">[^<]+<\/style>/is','',$content);

	$htmlfooter = '<div id="messagefooter">

    *|LIST:DESCRIPTION|*<br>

    <br>

    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>

    <br>

    <strong>Our mailing address is:</strong><br>

    *|LIST:ADDRESS|*<br>

    <em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    



*|REWARDS|*</div>';

$chimpfooter_text = '



==============================================

*|LIST:DESCRIPTION|*



Forward to a friend:

*|FORWARD|*



Unsubscribe *|EMAIL|* from this list:

*|UNSUB|*



Update your profile:

*|UPDATE_PROFILE|*



Our mailing address is:

*|LIST:ADDRESS|*

Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';



$rsvp_htmlfooter = '<div id="messagefooter">

*|LIST:DESCRIPTION|*<br>

<br>

<a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>

<br>

<strong>Our mailing address is:</strong><br>

*|LIST:ADDRESS|*<br>

<em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>

*|REWARDS|*</div>';



$rsvpfooter_text = '



==============================================

*|LIST:DESCRIPTION|*



Unsubscribe *|EMAIL|* from this list:

*|UNSUB|*



Our mailing address is:

*|LIST:ADDRESS|*

Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';



$content = preg_replace( '/(?<!")(https:\/\/www.youtube.com\/watch\?v=|https:\/\/youtu.be\/)([a-zA-Z0-9_\-]+)/', '<p><a href="$0">Watch on YouTube: $0<br /><img src="https://img.youtube.com/vi/$2/mqdefault.jpg" width="320" height="180" /></a></p>', $content );



global $templatefooter;

if ( strpos( $content, '*|UNSUB|*' ) ) {

	$templatefooter = true;// footer code already added
}

$chimp_text = rsvpmaker_text_version( $content, $chimpfooter_text );

if ( $templatefooter ) {

	$rsvp_html = $chimp_html = $content;

} else {

	$chimp_html = str_replace( '<!-- footer -->', $htmlfooter, $content );

	$rsvp_html = str_replace( '<!-- footer -->', $rsvp_htmlfooter, $content );

}

$rsvp_text = rsvpmaker_text_version( $content, $rsvpfooter_text );

$chimp_html = rsvpmaker_inliner( $chimp_html );

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

		$chimp_html = str_replace( '<!-- editors note goes here -->', '<h2>' . $editorsnote['add_to_head'] . "</h2>\n" . $editorsnote['note'], $chimp_html );

		$rsvp_html = str_replace( '<!-- editors note goes here -->', '<h2>' . $editorsnote['add_to_head'] . "</h2>\n" . $editorsnote['note'], $chimp_html );

		$chimp_text = $editorsnote['add_to_head'] . "\n\n" . strip_tags( $editorsnote['note'] ) . "\n\n" . $chimp_text . "\n\n";

		$rsvp_text = $editorsnote['add_to_head'] . "\n\n" . strip_tags( $editorsnote['note'] ) . "\n\n" . $rsvp_text . "\n\n";

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

	$chimp_options = get_option( 'chimp' );

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

			$content_result = $MailChimp->put(
				'campaigns/' . $campaign['id'] . '/content',
				array(

					'html' => $chimp_html,
					'text' => $chimp_text,
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

				$mail['html'] = rsvpmaker_personalize_email( $rsvp_html, $mail['to'], '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

				$mail['text'] = rsvpmaker_personalize_email( $rsvp_text, $mail['to'], 'This message was sent to you as a member of ' . get_bloginfo( 'name' ) );

				$result = rsvpmailer( $mail );

				// print_r($result);

			}
		}
	}



	if ( ! empty( $cron['cron_to'] ) && ( $rsvpmaker_cron_context == 2 ) ) {

			$mail['to'] = $cron['cron_to'];

			$mail['from'] = $from_email;

			$mail['fromname'] = $from_name;

			$mail['subject'] = $subject;

			$mail['html'] = rsvpmaker_personalize_email( $rsvp_html, $mail['to'], '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

			$mail['text'] = rsvpmaker_personalize_email( $rsvp_text, $mail['to'], 'This message was sent to you as a member of ' . get_bloginfo( 'name' ) );

			$result = rsvpmailer( $mail );

			// print_r($result);

	}

	if ( $cron['cron_preview'] && ( $rsvpmaker_cron_context == 1 ) ) {

			$mail['to'] = $previewto;

			$mail['from'] = $from_email;

			$mail['fromname'] = $from_name;

			$mail['subject'] = 'PREVIEW:' . $subject;

			$mail['html'] = rsvpmaker_personalize_email( $rsvp_html, $mail['to'], '<div class="rsvpexplain">This message was sent to you as a member of ' . get_bloginfo( 'name' ) . '</div>' );

			$mail['text'] = rsvpmaker_personalize_email( $rsvp_text, $mail['to'], 'This message was sent to you as member of ' . get_bloginfo( 'name' ) );

			$result = rsvpmailer( $mail );

			// print_r($result);

			update_option( 'rsvpmaker_cron_preview_result', $result . ': ' . var_export( $mail, true ) );

	}
}

$preview = str_replace( '*|MC:SUBJECT|*', 'Email: ' . $post->post_title, $content );
$preview = preg_replace( '/<body[^>]*>/', '$0' . '<div id="email-preview-background" style="width: 100%; margin: 0; padding: 5px; color: #fff; background-color: #000;"> <p>Email Preview: '.$post->post_title.'</p> <div id="email-preview-wrapper" style="max-width: 700px; margin-left: auto; margin-right: auto; color: #000; background-color: #fff;">', $preview );
$preview = str_replace('</body>','</div></div></body>',$preview);

if ( isset( $_GET['template_preview'] ) ) {

		$preview = rsvpmaker_personalize_email( $preview, 'david@carrcommunications.com', '<div class="rsvpexplain">This message is a demo.</div>' );

} elseif ( current_user_can( 'publish_rsvpemails' ) ) {
		$preview = str_replace('<!-- controls go here -->',rsvpmaker_email_send_ui( $chimp_html, $chimp_text, $rsvp_html, $rsvp_text, $templates, $t_index ),$preview);
}
/* cannot be escaped because of embedded form content. Escaping belongs in the functions that create this output variable */
echo $preview;
