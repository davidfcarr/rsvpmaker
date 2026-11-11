<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvpdateblock($attributes), dcarr_kses_form());
?>
</div>