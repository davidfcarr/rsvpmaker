<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvp_form_text($attributes, $content), dcarr_kses_form());
?>
</div>