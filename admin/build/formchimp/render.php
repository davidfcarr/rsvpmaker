<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvpmaker_formchimp($attributes, $content), dcarr_kses_form());
?>
</div>