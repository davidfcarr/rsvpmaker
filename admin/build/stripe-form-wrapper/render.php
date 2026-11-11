<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvpmaker_stripe_form_wrapper ($attributes, $content), dcarr_kses_form());
?>
</div>