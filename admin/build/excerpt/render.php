<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
global $post;
$max = (isset($attributes["max"])) ? intval($attributes["max"]) : 55;
echo rsvpmaker_excerpt_body($post, $max);
?>
</div>