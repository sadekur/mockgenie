<?php
/**
 * Plugin Name: MockGenie
 * Plugin URI:  https://www.progressivebyte.com/
 * Description: Generate and edit images inside WordPress with MockGenie AI.
 * Version:     1.0.0
 * Author:      Mahbub
 * Author URI:  https://www.progressivebyte.com/
 * Text Domain: mockgenie
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


////////////////////////////////////////////////////////////////////////////////
// Constants
////////////////////////////////////////////////////////////////////////////////

define( 'MOCKGENIE_VERSION', '1.0.0' );
define( 'MOCKGENIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOCKGENIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MOCKGENIE_ASSETS_URL', MOCKGENIE_PLUGIN_URL . 'assets/' );
define( 'MOCKGENIE_INCLUDES_DIR', MOCKGENIE_PLUGIN_DIR . 'includes/' );

define( 'API_SITE_URL', 'https://mockgenie-server.local/' );
define( 'API_BASE', API_SITE_URL . 'wp-json/mockginiapi/v1' );

////////////////////////////////////////////////////////////////////////////////
// Autoloader
////////////////////////////////////////////////////////////////////////////////

// Load Composer autoloader.
require_once MOCKGENIE_PLUGIN_DIR . 'vendor/autoload.php';

register_activation_hook(__FILE__, function () {
    set_transient('mockgenie_after_activation_notice', 1, 60);
});

////////////////////////////////////////////////////////////////////////////////
// Initialize Plugin
////////////////////////////////////////////////////////////////////////////////

/**
 * Initialize MockGenie plugin classes.
 */
add_action( 'plugins_loaded', function() {

    // Admin-specific classes.
    if ( is_admin() ) {
        if ( class_exists( 'MockGenie\\Admin' ) ) {
            new MockGenie\Admin();
        }

        if ( class_exists( 'MockGenie\\Ajax' ) ) {
            new MockGenie\Ajax();
        }
    }
    // Frontend-specific classes.
    else {
        if ( class_exists( 'MockGenie\\Front' ) ) {
            new MockGenie\Front();
        }
    }

    if ( class_exists( 'MockGenie\\Common' ) ) {
        new MockGenie\Common();
    }

} );
