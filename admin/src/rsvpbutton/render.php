<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
global $post;
if(!empty(get_post_meta($post->ID,'_rsvp_on',true))) {
    echo get_rsvp_link_custom($post->ID,$content);
}
?>
</div>
