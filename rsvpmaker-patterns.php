<?php
add_action('init', 'rsvpmaker_patterns');
function rsvpmaker_patterns() {
register_block_pattern_category( 'rsvpmaker',  array( 'label' => 'RSVPMaker' ) );

register_block_pattern(
        'rsvpmaker/calendar-single-column',
        array(
            'title'       => __( 'RSVPMaker Calendar, Single Column Events Listing', 'rsvpmaker' ),
            'description' => __( 'Display calendar grid, followed by an event listing in a single column.', 'rsvpmaker' ),
            'categories'    => ['rsvpmaker'],
            'content'     => '<!-- wp:query {"queryId":13,"query":{"perPage":10,"pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","rsvp_only":false,"excludeType":0},"namespace":"rsvpmaker/loop-plus-calendar"} -->
<div class="wp-block-query"><!-- wp:rsvpmaker/calendar /-->

<!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:post-title {"isLink":true} /-->

<!-- wp:post-featured-image /-->

<!-- wp:rsvpmaker/rsvpdateblock /-->

<!-- wp:rsvpmaker/excerpt /-->

<!-- wp:read-more {"content":"Read More","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}}} /-->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"10px","topRight":"10px","bottomLeft":"10px","bottomRight":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:10px;border-top-right-radius:10px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;background-color:#f71b1b">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button -->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>No events found.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->',
        )
    ); 

register_block_pattern(
        'rsvpmaker/event',
        array(
            'title'       => __( 'RSVPMaker Featured Event, Followed by Headline Listing', 'rsvpmaker' ),
            'description' => __( 'A pattern for displaying a single event with RSVP button, followed by a headline listing of upcoming events.', 'rsvpmaker' ),
            'categories'    => ['rsvpmaker'],
            'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","excludeType":0},"namespace":"rsvpmaker/loop-event"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:post-title {"isLink":true} /-->

<!-- wp:post-featured-image /-->

<!-- wp:rsvpmaker/rsvpdateblock /-->

<!-- wp:rsvpmaker/excerpt /-->

<!-- wp:read-more {"content":"Read More","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}}} /-->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"10px","topRight":"10px","bottomLeft":"10px","bottomRight":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:10px;border-top-right-radius:10px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;background-color:#f71b1b">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:rsvpmaker/future-rsvp-links {"skipfirst":true} /--></div>
<!-- /wp:group -->',
        )
    ); 

register_block_pattern(
        'rsvpmaker/featured-title-button',
        array(
            'title'       => __( 'RSVPMaker Featured Event, Followed by Title/Date/Button', 'rsvpmaker' ),
            'description' => __( 'A compact event listing format.', 'rsvpmaker' ),
            'categories'    => ['rsvpmaker'],
            'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","excludeType":0},"namespace":"rsvpmaker/loop-event"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:post-title {"isLink":true} /-->

<!-- wp:post-featured-image /-->

<!-- wp:rsvpmaker/rsvpdateblock /-->

<!-- wp:rsvpmaker/excerpt /-->

<!-- wp:read-more {"content":"Read More","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}}} /-->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"10px","topRight":"10px","bottomLeft":"10px","bottomRight":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:10px;border-top-right-radius:10px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;background-color:#f71b1b">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:query {"queryId":1,"query":{"perPage":"5","pages":0,"offset":1,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","excludeType":0,"rsvp_only":false},"namespace":"rsvpmaker/rsvpmaker-loop"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-title {"isLink":true} /-->

<!-- wp:rsvpmaker/date-element {"show":"start","start_format":"l F j","style":{"spacing":{"padding":{"bottom":"0"}}},"fontSize":"x-large"} /--></div>
<!-- /wp:group -->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"5px","topRight":"5px","bottomLeft":"5px","bottomRight":"5px"}},"spacing":{"padding":{"left":"5px","right":"5px","top":"5px","bottom":"5px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:5px;border-top-right-radius:5px;border-bottom-left-radius:5px;border-bottom-right-radius:5px;background-color:#f71b1b;padding-top:5px;padding-right:5px;padding-bottom:5px;padding-left:5px">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->',
        )
    );

register_block_pattern(
        'rsvpmaker/compact-cover',
        array(
            'title'       => __( 'RSVPMaker Upcoming Events, Title and Date in Featured Image Cover Block', 'rsvpmaker' ),
            'description' => __( 'A fancy events listing that uses the featured image as a background with title and date overlay.', 'rsvpmaker' ),
            'categories'    => ['rsvpmaker'],
            'content'     => '<!-- wp:query {"queryId":0,"query":{"perPage":"12","pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","excludeType":""},"namespace":"rsvpmaker/rsvpmaker-loop"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":30,"customOverlayColor":"#534850","isUserOverlayColor":true,"isDark":false,"className":"swankcover","style":{"dimensions":{"aspectRatio":"auto"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}},"css":"color: white;\ntext-shadow: \n    -1px -1px 0 #000,  \n     1px -1px 0 #000,\n    -1px  1px 0 #000,\n     1px  1px 0 #000;"},"textColor":"base-2","layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light swankcover has-custom-css has-base-2-color has-text-color has-link-color"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-30 has-background-dim" style="background-color:#534850"></span><div class="wp-block-cover__inner-container"><!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} /-->

<!-- wp:rsvpmaker/rsvpdateblock {"alignment":"center"} /--></div></div>
<!-- /wp:cover -->

<!-- wp:rsvpmaker/loop-blocks -->
<div class="wp-block-rsvpmaker-loop-blocks"><!-- wp:read-more {"content":"Read More \u003e\u003e","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}}} /-->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"10px","topRight":"10px","bottomLeft":"10px","bottomRight":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:10px;border-top-right-radius:10px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;background-color:#f71b1b">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button --></div>
<!-- /wp:rsvpmaker/loop-blocks -->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>No upcoming events found.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->',
        )
    ); 

register_block_pattern(
        'rsvpmaker/title-button',
        array(
            'title'       => __( 'RSVPMaker Title/Date/Button', 'rsvpmaker' ),
            'description' => __( 'A compact event listing format.', 'rsvpmaker' ),
            'categories'    => ['rsvpmaker'],
            'content'     => '<!-- wp:query {"queryId":1,"query":{"perPage":"5","pages":0,"offset":0,"postType":"rsvpmaker","order":"asc","author":"","search":"","exclude":[],"sticky":"","inherit":false,"eventOrder":"future","excludeType":0,"rsvp_only":false},"namespace":"rsvpmaker/rsvpmaker-loop"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":1}} -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-title {"isLink":true} /-->

<!-- wp:rsvpmaker/date-element {"show":"start","start_format":"l F j","style":{"spacing":{"padding":{"bottom":"0"}}},"fontSize":"x-large"} /--></div>
<!-- /wp:group -->

<!-- wp:rsvpmaker/button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"base","style":{"color":{"background":"#f71b1b"},"className":"rsvplink","elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"radius":{"topLeft":"5px","topRight":"5px","bottomLeft":"5px","bottomRight":"5px"}},"spacing":{"padding":{"left":"5px","right":"5px","top":"5px","bottom":"5px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-text-color has-background has-link-color wp-element-button" href="#rsvpnow" style="border-top-left-radius:5px;border-top-right-radius:5px;border-bottom-left-radius:5px;border-bottom-right-radius:5px;background-color:#f71b1b;padding-top:5px;padding-right:5px;padding-bottom:5px;padding-left:5px">RSVP Now!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:rsvpmaker/button -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->',
        )
    ); 

}
