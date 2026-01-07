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
 * Class Front
 */
class Front {

    use Hook;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->action( 'wp_head', [ $this, 'head' ] );
    }

    public function head(){
        // $login_api_data = get_option( 'login_api_data' );
        // $user_status    = $login_api_data['user_status'];
        // Helper::pri( $login_api_data, true );
    }
}