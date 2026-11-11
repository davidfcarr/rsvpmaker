<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
<?php
echo wp_kses(rsvpmaker_daily_schedule ($attributes), dcarr_kses_form());
?>
</div>