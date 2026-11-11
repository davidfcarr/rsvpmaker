<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvpmaker_paypal_button_embed ($attributes), dcarr_kses_form());
?>
</div>