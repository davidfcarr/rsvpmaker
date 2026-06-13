<?php
//https://www.geeksforgeeks.org/php/dynamically-generating-a-qr-code-using-php/
function rsvpmaker_qr($atts) {
    if(!class_exists('qrstr')) 
        include 'phpqrcode/qrlib.php';
    global $post;
    $atts = shortcode_atts( array(
        'url' => 'permalink',
        'queryString' => '',
        'pixel' => '5',
        'before' => '<div class="rsvpmaker_qr">',
        'after' => '</div>',
        'html' => true,
        'rsvpnow' => false,
    ), $atts, 'rsvpmaker_qr' );

    if(empty($atts['url']) ||  !strpos($atts['url'], '://')) {
        if(empty($post->ID))
            return '';
        else
            $atts['url'] = get_permalink($post->ID);
    }
    if($atts['queryString'] && strpos($atts['queryString'], '?') === 0) {
        $atts['url'] .= sanitize_text_field($atts['queryString']);
    }
    if($atts['rsvpnow'] && strpos($atts['url'], 'rsvpmaker') !== false) {
        $atts['url'] .= '#rsvpnow';
    }
    $upload_dir = wp_upload_dir();
    $path = $upload_dir['basedir'] . '/qr/';
    if (!file_exists($path)) {
        wp_mkdir_p($path);
    }
    $atts['url'] = esc_url($atts['url']);
    // $ecc stores error correction capability('L')
    $ecc = 'L';
    $frame_Size = 3;

    $urlparts = parse_url($atts['url']);
    $file = $path . trim(preg_replace('/[^a-zA-Z0-9]/', '', str_replace($urlparts['scheme'] . '://', '', $atts['url']))).'-'.$atts['pixel'].'.png';

    // Generates QR Code and Stores it in directory given
    if(!file_exists($file))
        QRcode::png($atts['url'], $file, $ecc, $atts['pixel'], $frame_Size);
    // Displaying the stored QR code from directory
    $src = $upload_dir['baseurl'] . '/qr/' . basename($file);
    if($atts['html']) {
        return sprintf("%s<a href='%s'><img src='%s'></a>%s", $atts['before'], $atts['url'], $src, $atts['after']);
    } else {
        return $src;
    }
}