<?php
use MockGenie\Class\Helper;

// Prefix all global variables
$mockgenie_login_api_data = get_option( 'login_api_data' );

$mockgenie_user_usage = $mockgenie_login_api_data['user_usage'] ?? 0;
$mockgenie_user_limit = $mockgenie_login_api_data['user_limit'] ?? 0;

$mockgenie_site_url = mockgenie_get_url();

// Get current user ID and meta
$mockgenie_current_user_id   = get_current_user_id();
$mockgenie_name              = get_user_meta( $mockgenie_current_user_id, 'mg_name', true ) ?: '';
$mockgenie_email             = get_user_meta( $mockgenie_current_user_id, 'mg_email', true ) ?: '';
$mockgenie_images_per_page   = get_user_meta( $mockgenie_current_user_id, 'mg_images_per_page', true ) ?: 8;
?>

<div class="mg_container">

    <!-- Header -->
    <header class="mg_header">
        <h1 class="mg_logo"><?php echo esc_html( 'MockGenie' ); ?></h1>
        <div class="mg_header-right">
            <div class="mg_progress-info">
                <span class="mg_progress-text">
                    <?php echo esc_html( $mockgenie_user_usage . ' / ' . $mockgenie_user_limit . ' Images generated' ); ?>
                </span>
                <div class="mg_progress-bar">
                    <div class="mg_progress-fill" style="width: <?php echo esc_attr( mockgenie_get_usage_percentage() ); ?>%;"></div>
                </div>
            </div>
            <button class="mg_upgrade-btn"><?php esc_html_e( 'Upgrade', 'mockgenie' ); ?></button>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="mg_nav-tabs">
        <button class="mg_nav-tab" data-tab="generation"><?php esc_html_e( 'Image Generation', 'mockgenie' ); ?></button>
        <button class="mg_nav-tab" data-tab="settings"><?php esc_html_e( 'Account Settings', 'mockgenie' ); ?></button>
    </nav>

    <!-- Main Content -->
    <main class="mg_main-content">

        <!-- Generate Image Button -->
        <section class="mg_generate-section mg_tab-section" data-tab="generation">
            <div class="mg-action-buttons" style="margin-top:20px;">
                <a href="http://test.local/wp-admin/upload.php" target="_blank" class="mg-btn mg-btn-primary">
                    <?php esc_html_e( 'Generate Image', 'mockgenie' ); ?>
                </a>

                <a href="<?php echo esc_url( $mockgenie_site_url ); ?>" target="_blank" class="mg-btn mg-btn-primary">
                    <?php esc_html_e( '📖 Read Documentation', 'mockgenie' ); ?>
                </a>

                <a href="<?php echo esc_url( $mockgenie_site_url ); ?>" target="_blank" class="mg-btn mg-btn-secondary">
                    <?php esc_html_e( '🎬 Watch Tutorial', 'mockgenie' ); ?>
                </a>
            </div>
        </section>

        <!-- Info Section -->
        <section class="mg_info-section mg_tab-section" data-tab="generation">
            <h2><?php esc_html_e( 'How to Generate Images?', 'mockgenie' ); ?></h2>
            <p><?php esc_html_e( 'Simply go to your media library and you\'ll get the option to generate images right inside your media library.', 'mockgenie' ); ?></p>
            <div class="mg-action-buttons" style="margin-top:20px;">
                <a href="<?php echo esc_url( $mockgenie_site_url ); ?>" target="_blank" class="mg-btn mg-btn-primary">
                    <?php esc_html_e( '📖 Read Documentation', 'mockgenie' ); ?>
                </a>
                <a href="<?php echo esc_url( $mockgenie_site_url ); ?>" target="_blank" class="mg-btn mg-btn-secondary">
                    <?php esc_html_e( '🎬 Watch Tutorial', 'mockgenie' ); ?>
                </a>
            </div>
        </section>

        <!-- Service Section -->
        <section class="mg_service-section mg_tab-section" data-tab="generation">
            <h3><?php esc_html_e( 'Image Generation Service', 'mockgenie' ); ?></h3>

            <!-- MockGenie Option -->
            <div class="mg_service-option">
                <label for="mockgenie" class="mg_service-label">
                    <input type="radio" id="mockgenie" name="mg_service" checked>
                    <span class="mg_option-text"><?php esc_html_e( 'Use MockGenie Image Generation', 'mockgenie' ); ?></span>
                    <span class="mg_badge mg_recommended"><?php esc_html_e( 'Recommended', 'mockgenie' ); ?></span>
                </label>
                <p class="mg_option-description">
                    <?php esc_html_e( 'You\'ll be using our image generation services. You can get started as soon as you install the plugin. If you run out of credits for image generation, just buy new credits.', 'mockgenie' ); ?>
                </p>
            </div>

            <!-- Own API Option -->
            <div class="mg_service-option">
                <label for="own-api" class="mg_service-label">
                    <input type="radio" id="own-api" name="mg_service">
                    <span class="mg_option-text"><?php esc_html_e( 'Use My Own API Key', 'mockgenie' ); ?></span>
                    <span class="mg_badge mg_coming-soon"><?php esc_html_e( 'Coming Soon', 'mockgenie' ); ?></span>
                </label>
                <p class="mg_option-description">
                    <?php esc_html_e( 'You can use your own API key. Right now, we support Gemini 2.5 API.', 'mockgenie' ); ?>
                </p>
            </div>

            <button class="mg_save-btn"><?php esc_html_e( 'Save Changes', 'mockgenie' ); ?></button>
        </section>

        <!-- Generation History Section -->
        <section class="mg_history-section mg_tab-section" data-tab="generation">
            <h3><?php esc_html_e( 'Generation History', 'mockgenie' ); ?></h3>
            <div class="mg_image-grid">
                <?php
                // Memory-safe query: only fetch IDs, limit to 20 per page
                $mockgenie_attachment_ids = get_posts( array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'posts_per_page' => 20,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'     => 'mockgenie_image',
                            'compare' => 'EXISTS',
                        ),
                    ),
                ) );

                if ( ! empty( $mockgenie_attachment_ids ) ) {
                    foreach ( $mockgenie_attachment_ids as $mockgenie_attachment_id ) {
                        $mockgenie_image_url = wp_get_attachment_url( $mockgenie_attachment_id );
                        $mockgenie_prompt    = get_post_meta( $mockgenie_attachment_id, 'mockgenie_image', true );
                        ?>
                        <div class="mg_image-item mg_image-box">
                            <img src="<?php echo esc_url( $mockgenie_image_url ); ?>" alt="<?php echo esc_attr( mb_strimwidth( $mockgenie_prompt, 0, 50, '...' ) ); ?>" />
                        </div>
                        <?php
                    }
                } else {
                    // Show 10 placeholders if no images
                    for ( $i = 0; $i < 10; $i++ ) :
                        ?>
                        <div class="mg_image-placeholder mg_image-box">
                            <svg class="mg_placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <polyline points="21,15 16,10 5,21" />
                            </svg>
                        </div>
                        <?php
                    endfor;
                }
                ?>
            </div>
            <div class="mg_pagination"></div>
        </section>

        <!-- Account Settings Section -->
        <section class="mg_login-section mg_tab-section" data-tab="settings">
            <form method="post">
                <?php wp_nonce_field( 'mg_save_account_settings', 'mg_account_nonce' ); ?>
                <div class="mg_form-group">
                    <input type="text" name="mg_name" class="mg_input mg_name" value="<?php echo esc_attr( $mockgenie_name ); ?>" placeholder="<?php esc_attr_e( 'John Smith', 'mockgenie' ); ?>">
                    <input type="email" name="mg_email" class="mg_input mg_email" value="<?php echo esc_attr( $mockgenie_email ); ?>" placeholder="<?php esc_attr_e( 'john@email.com', 'mockgenie' ); ?>">
                    <label for="mg_images_per_page"><?php esc_html_e( 'Images per page', 'mockgenie' ); ?></label>
                    <input type="number" id="mg_images_per_page" name="mg_images_per_page" class="mg_input" value="<?php echo esc_attr( $mockgenie_images_per_page ); ?>" min="1" max="50">
                </div>
                <div class="mg_button-group">
                    <button type="submit" name="mg_save_account" class="mg_btn-save"><?php esc_html_e( 'Save Changes', 'mockgenie' ); ?></button>
                    <button type="button" class="mg_btn-password"><?php esc_html_e( 'Change Password', 'mockgenie' ); ?></button>
                </div>
            </form>
        </section>

        <!-- Logout Section -->
        <section class="mg_logout-section mg_tab-section" data-tab="settings">
            <form method="post">
                <?php wp_nonce_field( 'mg_delete_login_api_data_action', 'mg_delete_login_api_data_nonce' ); ?>
                <button type="submit" name="mg_delete_login_api_data" class="mg-btn mg-btn-logout">
                    <?php esc_html_e( 'Log Out', 'mockgenie' ); ?>
                </button>
            </form>

            <?php
            if ( isset( $_POST['mg_delete_login_api_data'], $_POST['mg_delete_login_api_data_nonce'] ) &&
                 wp_verify_nonce( $_POST['mg_delete_login_api_data_nonce'], 'mg_delete_login_api_data_action' )
            ) {
                delete_option( 'login_api_data' );
                wp_safe_redirect( $_SERVER['REQUEST_URI'] );
                exit;
            }
            ?>
        </section>

        <!-- Additional Settings Section -->
        <section class="mg_settings-section mg_tab-section" data-tab="settings" style="display:none;">
            <h2><?php esc_html_e( 'Connected Website', 'mockgenie' ); ?></h2>
            <p><?php esc_html_e( 'Your image generation quota is shared to all the sites you connected through this account.', 'mockgenie' ); ?></p>
        </section>

        <section class="mg_site-section mg_tab-section" data-tab="settings" style="display:none;">
            <h2><?php esc_html_e( 'Site Status: Not Connected', 'mockgenie' ); ?></h2>
            <p><?php esc_html_e( 'Site Name: ', 'mockgenie' ); ?></p>
        </section>

    </main>
</div>
