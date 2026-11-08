<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
global $post;
echo wp_kses_post($content);
rsvpmaker_agenda_item($attributes);
?>
</div>