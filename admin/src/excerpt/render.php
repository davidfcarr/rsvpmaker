<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
global $post;
$max = (isset($attributes["max"])) ? intval($attributes["max"]) : 55;
echo wp_kses(rsvpmaker_excerpt_body($post, $max), dcarr_kses_form());
?>
</div>