<div <?php echo get_block_wrapper_attributes(); ?>>
<?php
global $post;
if(get_post_meta($post->ID,'_rsvp_on',true))
    echo str_replace('#rsvpnow',get_permalink($post->ID).'#rsvpnow',$content);
?>
</div>