<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo rsvpmaker_stripe_form_wrapper ($attributes, $content);
?>
</div>