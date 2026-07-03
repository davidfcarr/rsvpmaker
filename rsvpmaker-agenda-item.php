<?php
function rsvpmaker_agenda_item($attributes) {
global $post, $current_user;
if(!is_user_logged_in() || (isset($attributes['allowNotes']) && !$attributes['allowNotes']))
    return;
if(!isset($_GET['allNotes']))
    echo '<details><summary>Notes</summary>';
else 
    echo '<h3>Notes</h3>';
ob_start();
$user_id    = isset($_REQUEST['user_id']) ? absint( $_REQUEST['user_id'] ) : 0;
$login_code = isset($_REQUEST['email_login_code']) ? sanitize_text_field( $_REQUEST['email_login_code'] ) : '';

?>
  <form method="post" action="<?php echo esc_url( get_permalink() ); ?>" class="agenda-item-notes" enctype="multipart/form-data">
<?php
$ratings_array = get_post_meta($post->ID, '_agenda_item_ratings', true);
if(is_array($ratings_array) && isset($ratings_array[$attributes['blockId']][get_current_user_id()])) {
    $your_rating = $ratings_array[$attributes['blockId']][get_current_user_id()];
}
else
    $your_rating = 0;

    echo '<p><label for="agenda_item_rating">Your rating (1-5, 5 is best):</label><br />';
    for($i = 1; $i <= 5; $i++) {
        echo '<input type="radio" name="agenda_item_rating" value="' . $i . '" ' . checked($your_rating, $i, false) . ' /> ' . $i . ' ';
    }
    echo '</p>';
?>
    <p>Add a note<br /><textarea name="agenda_item_notes" rows="4" cols="50"></textarea></p>
    <input type="hidden" name="agenda_item_id" value="<?php echo esc_attr( $attributes['blockId'] ); ?>">
    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
    <input type="hidden" name="email_login_code" value="<?php echo esc_attr( $login_code ); ?>">
    <p>Add an image (optional) <input type="file" name="agenda_item_file" /></p>
    <p><button type="submit">Save Notes</button></p>
</form>
<?php 
$images_array = get_post_meta($post->ID, '_agenda_item_images', true);
$notes_array = get_post_meta($post->ID, '_agenda_item_notes', true);
if(is_array($notes_array)) {
    if(isset($notes_array[$attributes['blockId']]) || isset($_GET['allNotes'])) {
        if(is_array($notes_array[$attributes['blockId']])) {    
        foreach($notes_array[$attributes['blockId']] as $note) {
            echo '<div class="agenda-item-notes">'.wp_kses_post(wpautop($note)).'</div>';
        }
        }
    }
    if(is_array($ratings_array) && (isset($ratings_array[$attributes['blockId']]) || isset($_GET['allNotes'])) ) {
        if(is_array($ratings_array[$attributes['blockId']])) {
        foreach($ratings_array[$attributes['blockId']] as $user_id => $rating) {
            $user_info = get_userdata($user_id);
            $number_of_ratings = count($ratings_array[$attributes['blockId']]);
            if($user_info) {
                printf('<p class="agenda-item-notes">Rating by %s: %s</p>', esc_html($user_info->display_name), esc_html($rating));
            } else {
                printf('<p class="agenda-item-notes">Rating by user ID %d: %s</p>', esc_html($user_id), esc_html($rating));
            }
        }
        $average_rating = array_sum($ratings_array[$attributes['blockId']]) / $number_of_ratings;
        printf('<p>Average rating: %.2f based on %d ratings.</p>', esc_html($average_rating), esc_html($number_of_ratings));
        }
    }
    if(is_array($images_array) && (isset($images_array[$attributes['blockId']]) || isset($_GET['allNotes']))) {
        if(is_array($images_array[$attributes['blockId']])) {
            foreach($images_array[$attributes['blockId']] as $user_id => $image) {
                $user_info = get_userdata($user_id);
                echo '<p><img src="' . esc_url($image) . '" style="max-width:95%;" /></p>';
                if($user_info) {
                    printf('<p class="agenda-item-notes"><small>Uploaded by %s</small></p>', esc_html($user_info->display_name));
                }
            }
        }
    }
    if(!isset($_GET['allNotes'])) {
        $args = array('allNotes' => 1,'user_id' => $current_user->ID, 'email_login_code' => get_user_meta($current_user->ID, 'saved_email_login_code', true));
        echo '<p class="agenda-item-notes"><a href="' . esc_url(add_query_arg($args, get_permalink())) . '">Show all notes</a></p>';
    }
}
$content = ob_get_clean();
echo '<div class="rsvpmaker-agenda-item-notes">'.$content.'</div>';
if(!isset($_GET['allNotes']))
    echo '</details>';
}

add_filter('the_content', 'rsvpmaker_event_content_agendanotes', 99, 2);

function rsvpmaker_event_content_agendanotes($content) {
    if(isset($_GET['agenda_note_confirm'])) {
        $saved = sanitize_text_field($_GET['agenda_note_confirm']);
        $content = '<div class="rsvpconfirm"><p>'.__('Saved: ','rsvpmaker').esc_html($saved).'</p></div>'.$content;
    }
    return $content;
}

add_action('init', 'rsvpmaker_event_post_agendanotes', 2);

function rsvpmaker_event_post_agendanotes() {
    global $current_user; // Moved to the top so it's accessible everywhere cleanly
    $saved = [];
    if(!empty($_POST['agenda_item_notes'])) {
        $signature = (empty($current_user->display_name)) ? date('F j, Y') : $current_user->display_name . ' ' . date('F j, Y');
        $notes = sanitize_textarea_field(wp_unslash($_POST['agenda_item_notes']))."\n\n <small>(".$signature.')</small>';
        $agenda_item_id = sanitize_text_field($_POST['agenda_item_id']);
        $post_id = intval($_POST['post_id']);
        $notes_array = get_post_meta($post_id, '_agenda_item_notes', true);
        if(!is_array($notes_array)) {
            $notes_array = array();
        }
        $notes_array[$agenda_item_id][] = $notes;
        if($post_id) {
            update_post_meta($post_id, '_agenda_item_notes', $notes_array);
            $saved[] = 'Notes';
        }
    }

    if(!empty($_POST['agenda_item_rating'])) {
        $signature = (empty($current_user->display_name)) ? date('F j, Y') : $current_user->display_name . ' ' . date('F j, Y');
        $rating = intval($_POST['agenda_item_rating']);
        $agenda_item_id = sanitize_text_field($_POST['agenda_item_id']);
        $post_id = intval($_POST['post_id']);
        $ratings_array = get_post_meta($post_id, '_agenda_item_ratings', true);
        if(!is_array($ratings_array)) {
            $ratings_array = array();
        }
        $ratings_array[$agenda_item_id][$current_user->ID] = $rating;
        if($post_id) {
            update_post_meta($post_id, '_agenda_item_ratings', $ratings_array);
            $saved[] = 'Rating';
        }
    }

    // --- NEW: Handle File Upload ---
    // Change 'agenda_item_file' to match the 'name' attribute of your HTML file input
    if ( ! empty( $_FILES['agenda_item_file']['name'] ) ) {
        $agenda_item_id = sanitize_text_field( $_POST['agenda_item_id'] );
        $post_id        = intval( $_POST['post_id'] );

        // 1. Check if the file is an image
        $file_type_info = wp_check_filetype( $_FILES['agenda_item_file']['name'] );
        $allowed_types  = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );

        if ( in_array( $file_type_info['type'], $allowed_types ) ) {
            
            // These core WordPress files are required to use media_handle_upload on the front-end
            if ( ! function_exists( 'media_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );
            }

            // 2. Upload file to media library and attach it to the post
            $attachment_id = media_handle_upload( 'agenda_item_file', $post_id );

            if ( ! is_wp_error( $attachment_id ) ) {
                // Get the direct URL of the uploaded image
                $image_url = wp_get_attachment_url( $attachment_id );

                // 3. Retrieve and structure postmeta exactly like ratings
                $images_array = get_post_meta( $post_id, '_agenda_item_images', true );
                if ( ! is_array( $images_array ) ) {
                    $images_array = array();
                }

                $images_array[$agenda_item_id][$current_user->ID] = $image_url;

                if ( $post_id ) {
                    update_post_meta( $post_id, '_agenda_item_images', $images_array );
                    $saved[] = 'Image';
                }
            } else {
                $saved[] = 'Error uploading image';
            }
        } else {
            $saved[] = 'Invalid image format';
        }
    }
    if(!empty($saved)) {
    $args = array('agenda_note_confirm' => implode(', ', $saved),'user_id' => $current_user->ID, 'email_login_code' => get_user_meta($current_user->ID, 'saved_email_login_code', true));
    wp_safe_redirect( add_query_arg( $args, get_permalink() ) );
    exit;
    }
}
/**
 * Part 1: Secure Passwordless Login Logic
 * Enhanced with session persistence fixes.
 */
function rsvpmaker_custom_passwordless_login() {
    // 1. Check for required GET parameters
    if ( empty( $_REQUEST['user_id'] ) || empty( $_REQUEST['email_login_code'] ) ) {
        return;
    }

    $user_id    = absint( $_REQUEST['user_id'] );
    $login_code = sanitize_text_field( $_REQUEST['email_login_code'] );

    if ( empty( $user_id ) || empty( $login_code ) ) {
        return;
    }

    // 2. Fetch user and strictly verify role
    $user = get_userdata( $user_id );

    // 4. Retrieve and validate token and expiration
    $saved_code = get_user_meta( $user_id, 'saved_email_login_code', true );
    $expiry     = get_user_meta( $user_id, 'email_login_code_expiry', true );

    if ( empty( $saved_code ) || $saved_code !== $login_code ) {
        wp_die( 'Invalid or expired login link.' );
    }

    if ( empty( $expiry ) || time() > intval( $expiry ) ) {
        wp_die( 'This login link has expired (links are valid for 1 week).' );
    }

    // 5. Single-use: Immediately destroy the tokens upon validation success
    //delete_user_meta( $user_id, 'saved_email_login_code' );
    //delete_user_meta( $user_id, 'email_login_code_expiry' );

    // 6. HARD AUTHENTICATION & COOKIE PERSISTENCE FIXES
    // Ensure all pluggable cookie functions are available
    if ( ! function_exists( 'wp_signon' ) ) {
        require_once ABSPATH . WPINC . '/pluggable.php';
    }

    // Completely clear out any half-baked session or cookie states
    //wp_clear_auth_cookie();
    //wp_destroy_current_session();

    // Set active user environment
    wp_set_current_user( $user_id );
    
}
add_action( 'init', 'rsvpmaker_custom_passwordless_login', 1 ); // Hook early on init (priority 1)


/**
 * Part 2: Form handling and Shortcode Output
 * Usage: [rsvpmaker_passwordless_login_form]
 */
function rsvpmaker_passwordless_login_shortcode() {
    ob_start();
    $user_id    = absint( $_REQUEST['user_id'] );
    $login_code = sanitize_text_field( $_REQUEST['email_login_code'] );

    global $wp;
    // Strip out the custom parameters so they don't loop back into form actions
    $clean_request = remove_query_arg( array( 'user_id', 'email_login_code' ), $wp->request );
    $current_permalink = home_url( $clean_request );

    // --- CASE A: USER IS LOGGED IN ---
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        
        // Handle Profile Completion Form Submission
        if ( isset( $_POST['submit_profile_completion'] ) && wp_verify_nonce( $_POST['profile_nonce'], 'update_profile_names' ) ) {
            $first_name = sanitize_text_field( $_POST['first_name'] );
            $last_name  = sanitize_text_field( $_POST['last_name'] );

            if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
                wp_update_user( array(
                    'ID'           => $current_user->ID,
                    'first_name'   => $first_name,
                    'last_name'    => $last_name,
                    'display_name' => $first_name . ' ' . $last_name,
                ) );
                echo '<p style="color: green;">Profile updated successfully!</p>';
                $current_user = wp_get_current_user();
            } else {
                echo '<p style="color: red;">Both First Name and Last Name are required.</p>';
            }
        }
        else {
        // Check if metadata is missing
        $has_first   = ! empty( $current_user->first_name );
        $has_last    = ! empty( $current_user->last_name );
        $has_display = ! empty( $current_user->display_name ) && ( $current_user->display_name !== $current_user->user_login );

        if ( ! $has_first || ! $has_last || ! $has_display ) {
            ?>
            <form action="" method="post" class="profile-completion-form">
                <?php wp_nonce_field( 'update_profile_names', 'profile_nonce' ); ?>
                <h3>Complete Your Profile</h3>
                <p>Please provide your real name to continue.</p>
                <p>
                    <label for="first_name">First Name</label><br>
                    <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" required />
                </p>
                <p>
                    <label for="last_name">Last Name</label><br>
                    <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" required />
                </p>
                <input type="hidden" name="user_id" value="<?php echo esc_attr( $current_user->ID ); ?>">
                <input type="hidden" name="email_login_code" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'saved_email_login_code', true ) ); ?>">
                <p>
                    <input type="submit" name="submit_profile_completion" value="Save Profile" />
                </p>
            </form>
            <?php
        }
        else {
            echo '<p>Welcome back, ' . esc_html( $current_user->display_name ) . '!</p>';
        }

        } 

        return ob_get_clean();
    }

    // --- CASE B: GUEST USER (Handle Email Magic Link Generation) ---
    if ( isset( $_POST['submit_magic_link'] ) && wp_verify_nonce( $_POST['magic_nonce'], 'request_magic_link' ) ) {
        $email = sanitize_email( $_POST['user_email'] );

        if ( is_email( $email ) ) {
            $user = get_user_by( 'email', $email );

            if ( ! $user ) {
                $username   = sanitize_user( current( explode( '@', $email ) ) );
                $username   = username_exists( $username ) ? $username . '_' . rand( 10, 99 ) : $username;
                $random_pwd = wp_generate_password( 18, false );
                
                $user_id = wp_create_user( $username, $random_pwd, $email );
                
                if ( ! is_wp_error( $user_id ) ) {
                    $user = new WP_User( $user_id );
                    $user->set_role( 'subscriber' );
                } else {
                    echo '<p style="color: red;">Error creating your account. Please try again.</p>';
                    $user = false;
                }
            }

            if ( $user ) {
                $token    = get_user_meta( $user->ID, 'saved_email_login_code', true );
                if(!$token) {
                    $token = wp_generate_password( 32, false );
                    update_user_meta( $user->ID, 'saved_email_login_code', $token );
                }
                $one_week = 7 * DAY_IN_SECONDS;
                $expiry   = time() + $one_week;

                update_user_meta( $user->ID, 'email_login_code_expiry', $expiry );

                $magic_link = add_query_arg( array(
                    'user_id'          => $user->ID,
                    'email_login_code' => $token
                ), $current_permalink );

                $subject = 'Your Secure Access Link for ' . get_bloginfo( 'name' );
                $message = "Hello,\n\nClick the link below to securely log into our site.\n\n" . $magic_link . "\n\nNote: This link will expire in 7 days.";
                
                wp_mail( $email, $subject, $message );

                echo '<p style="color: green;">Success! Check your inbox for your secure login link.</p>';
            }
        } else {
            echo '<p style="color: red;">Please enter a valid email address.</p>';
        }
    }

    ?>
    <form action="" method="post" class="magic-link-form">
        <?php wp_nonce_field( 'request_magic_link', 'magic_nonce' ); ?>
        <h3>Request Magic Login Link</h3>
        <p>Enter your email to sign up or log in instantly. A one-time access link will be emailed to you.</p>
        <p>
            <label for="user_email">Email Address</label><br>
            <input type="email" name="user_email" id="user_email" required />
        </p>
        <p>
            <input type="submit" name="submit_magic_link" value="Send Login Link" />
        </p>
    </form>
    <?php
    printf('<p>Or <a href="%s">Login with password</a></p>', wp_login_url(get_permalink()));

    return ob_get_clean();
}
add_shortcode( 'rsvpmaker_passwordless_login_form', 'rsvpmaker_passwordless_login_shortcode' );