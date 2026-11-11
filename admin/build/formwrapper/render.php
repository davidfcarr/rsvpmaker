<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(prsvpmaker_flexible_form_wrapper ($attributes, $content), dcarr_kses_form());
?>
</div>