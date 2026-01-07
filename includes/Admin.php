<?php
/**
 * Admin Class
 *
 * Handles admin menu, scripts, templates, and AJAX.
 *
 * @package MockGenie
 */

namespace MockGenie;

use MockGenie\Traits\Hook;
use MockGenie\Class\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin {

    use Hook;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->action( 'admin_menu', [ $this, 'register_menu' ] );
        $this->action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        $this->action( 'admin_footer', [ $this, 'admin_loader' ] );
        $this->action( 'admin_notices', [ $this, 'admin_notices' ] );

    }

    /**
     * Register admin menu page.
     */
    public function register_menu() {
        add_submenu_page(
            'tools.php',
            __( 'MockGenie', 'mockgenie' ),
            __( 'MockGenie', 'mockgenie' ),
            'manage_options',
            'mockgenie',
            [ $this, 'template' ]
        );
    }

    /**
     * Render admin template.
     */
    public function template() {
        $login_api_data = get_option( 'login_api_data', [] );
        if ( empty( $login_api_data ) ){
            $this->load_template( 'login' );
        } else {
            $this->load_template( 'dashboard' );
        } 
    }

    /**
     * Load template file.
     */
    public function load_template( $template, $args = [] ) {
        if ( empty( $template ) ) return;

        $template_file = MOCKGENIE_PLUGIN_DIR . 'templates/' . $template . '.php';

        if ( ! file_exists( $template_file ) ) return;

        if ( ! empty( $args ) && is_array( $args ) ) {
            extract( $args, EXTR_SKIP );
        }

        include $template_file;
    }

    /**
     * Enqueue admin styles and scripts.
     */
    public function enqueue_scripts( $hook ) {

        // Only load assets on MockGenie-related admin pages.
        if (
            $hook !== 'upload.php' &&                 // Media Library
            $hook !== 'plugins.php' &&                // Plugins page
            false === strpos($hook, 'mockgenie')      // Any Mockgenie page
        ) {
            return;
        }

        wp_enqueue_style(
            'mockgenie-admin',
            MOCKGENIE_ASSETS_URL . 'css/admin.css',
            [],
            MOCKGENIE_VERSION
        );

        // Toastr CSS
        wp_enqueue_style(
            'mockgenie-toastr',
            MOCKGENIE_ASSETS_URL . 'css/toastr.min.css',
            [],
            MOCKGENIE_VERSION
        );

        // Toastr JS (fixed dependency)
        wp_enqueue_script(
            'mockgenie-toastr',
            MOCKGENIE_ASSETS_URL . 'js/toastr.min.js',
            [ 'jquery' ],
            MOCKGENIE_VERSION,
            true
        );

        // Main admin JS
        wp_enqueue_script(
            'mockgenie-admin',
            MOCKGENIE_ASSETS_URL . 'js/admin.js',
            [ 'jquery', 'mockgenie-toastr' ],
            MOCKGENIE_VERSION,
            true
        );

        // Localize script with data
        $login_api_data      = get_option( 'login_api_data', [] );
        $current_user_id     = get_current_user_id();
        $mg_images_per_page  = get_user_meta( $current_user_id, 'mg_images_per_page', true ) ?: 8;

        $api_token = ! empty( $login_api_data['api_key'] ) ? sanitize_text_field( $login_api_data['api_key'] ) : '';

        wp_localize_script(
            'mockgenie-admin',
            'MOCKGENIE',
            [
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'mockgenie_nonce' ),
                'upgrade_url'     => mockgenie_get_url( 'upgrade' ),
                'api_url'         => API_BASE,
                'api_token'       => $api_token,
                'number_of_image' => $mg_images_per_page,
            ]
        );
    }

    /**
     * Output loader and modal HTML in admin footer.
     */
    public function admin_loader() {
        ?>
        <div id="mg_loader" class="mg_loader-overlay" style="display:none;">
            <div class="mg_loader-spinner"></div>
        </div>

        <!-- Full Image Modal -->
        <div id="mockgenie-full-modal" class="mockgenie-img-modal" style="display:none;">
            <div class="mockgenie-img-modal-content">
                <span class="mockgini-close">&times;</span>
                <div style="text-align:center;">
                    <img id="mockgenie-full-image" src="" alt="Full Image" style="max-width:90%; max-height:80vh; border-radius:10px;">
                    <p id="mockgenie-full-prompt" style="margin-top:10px; font-size:14px; color:#333;"></p>
                </div>
            </div>
        </div>

        <!-- Mockgini Modal -->
        <div id="mockgini-modal" class="mockgini-modal" style="display:none;">
            <div class="mockgini-modal-content">
                <span class="mockgini-close">&times;</span>
                <h2>Generate with Mockgini ✨</h2>
                <textarea id="mockgini-prompt-text" placeholder="Describe your image..."></textarea>
                <div class="mockgini-actions">
                    <button id="mockgini-generate" class="button button-primary">Generate</button>
                    <button id="mockgini-cancel" class="button">Cancel</button>
                </div>
                <div id="mockgini-loader" class="mockgini-loader" style="display:none;">
                    <div class="spinner"></div>
                    <div class="mg_loader-spinner"></div>
                </div>
                <div id="mockgini-output" class="mockgini-output"></div>
            </div>
        </div>

        <!-- Upgrade Confirmation Modal -->
        <div id="mg-upgrade-confirm" class="mg-confirm-modal" style="display: none;">
            <div class="mg-confirm-content">
                <span class="mg-confirm-close">&times;</span>
                <h2>Upgrade Confirmation</h2>
                <p>Are you sure you want to upgrade? You will be redirected to another site.</p>
                <div class="mg-confirm-actions">
                    <button id="mg-confirm-yes" class="mg-button">Confirm</button>
                    <button id="mg-confirm-no" class="mg-button">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <div id="mockgini-img-modal" class="mockgeni-img-modal" style="display:none;">
            <div class="mockgeni-img-modal-content">
                <span class="mockgini-close">&times;</span>
                <div id="mockgini-image-wrapper" style="text-align:center; margin-bottom: 20px;">
                    <img id="mockgini-generated-image" src="" alt="Generated Image" style="max-width:100%; border-radius:10px;">
                </div>
                <div class="mg-image-button" style="text-align:center;">
                    <button id="mockgini-regenerate" style="margin:5px; padding:8px 16px;">Regenerate</button>
                    <button id="mockgini-save" style="margin:5px; padding:8px 16px;">Save Image</button>
                    <button class="mockgini-img-close" style="margin:5px; padding:8px 16px;">Cancel</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display admin notice after plugin activation.
     */
    public function admin_notices() {
        // Check if transient is set.
        if ( ! get_transient( 'mockgenie_after_activation_notice' ) ) {
            return;
        }

        // Remove the transient after showing the notice.
        delete_transient( 'mockgenie_after_activation_notice' );

        // Ensure function exists before calling.
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();

        // Display only on the Plugins page.
        if ( ! $screen || 'plugins' !== $screen->id ) {
            return;
        }

        $url = admin_url( 'tools.php?page=mockgenie' );
        ?>
        <div class="mockgenie-notice" role="region" aria-label="<?php esc_attr_e( 'MockGenie activation', 'mockgenie' ); ?>">
            <div class="mg-content">
                <h2 class="mg-title"><?php esc_html_e( 'MockGenie is ready', 'mockgenie' ); ?></h2>
                <p class="mg-text"><?php esc_html_e( 'Open the MockGenie tools page to finish setup.', 'mockgenie' ); ?></p>
            </div>

            <a class="mg-button" href="<?php echo esc_url( $url ); ?>">
                <?php esc_html_e( 'Use MockGenie', 'mockgenie' ); ?>
                <span class="mg-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
        <?php
    }

}
