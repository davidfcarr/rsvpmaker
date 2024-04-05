<div <?php echo get_block_wrapper_attributes(); ?>>
<?php
global $post;
$d = rsvp_date_block($post->ID);
echo $d['dateblock'];
$parts = explode('</p>',$post->post_content);
if(empty($attributes['hide_excerpt'])) {
    $excerpt = trim(strip_tags($parts[0]));
    if(strlen($excerpt) > 150)
        $excerpt = substr($excerpt,0,150). ' ...';
    echo '<p>'.esc_html($excerpt).'</p>'; 
}
if(!empty($attributes['show_rsvp_button']) && get_post_meta($post->ID,'_rsvp_on',true))
    echo get_rsvp_link( $post->ID );
?>
</div>