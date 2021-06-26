<?php

require_once 'autoload.php';
use Pelago\Emogrifier\CssInliner;

function rsvpmaker_inliner( $content, $css = '' ) {
	if ( ! strpos( $content, '>' ) ) { // if there is no html
		return $content;
	}
	// if button is styled in the template, remove default inline CSS
	if ( strpos( $content, 'a.rsvplink' ) && strpos( $content, 'class="rsvplink"' ) ) {
		$content = preg_replace( '/<a style="[^"]+" class="rsvplink"/', '<a class="rsvplink"', $content );
	}
	if(empty($css))
		$content = CssInliner::fromHtml( $content )->inlineCss()->render();
	else{
		echo '<p>Applying '.$css.'</p>';
		$content = CssInliner::fromHtml( $content )->inlineCss($css)->render();
	}
	return $content;
}
