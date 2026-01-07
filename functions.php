<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get current user's MockGenie API data from the options table.
 *
 * @return array
 */
function mockgenie_get_user_api_data() {
    $data = get_option( 'login_api_data', [] );
    return is_array( $data ) ? $data : [];
}
/**
 * Check if the current user can generate more images.
 *
 * @return bool True if under limit, false if limit reached.
 */
function mockgenie_can_generate_image() {
    $data = mockgenie_get_user_api_data();

    $user_usage = isset( $data['user_usage'] ) ? intval( $data['user_usage'] ) : 0;
    $user_limit = isset( $data['user_limit'] ) ? intval( $data['user_limit'] ) : 0;

    // If user_limit is 0, allow unlimited
    if ( $user_limit === 0 ) {
        return true;
    }

    // Check if user has remaining quota
    return $user_usage < $user_limit;
}


/**
 * Decrease user remaining quota after successful generation.
 */
function mockgenie_decrease_user_usage() {
    $data = mockgenie_get_user_api_data();

    $user_usage = isset( $data['user_usage'] ) ? intval( $data['user_usage'] ) : 0;
    $user_limit = isset( $data['user_limit'] ) ? intval( $data['user_limit'] ) : 0;

    // If limit is 0 (unlimited), do nothing
    if ( $user_limit === 0 ) {
        return;
    }

    // Increment usage count by 1
    $user_usage++;

    // Update stored data
    $data['user_usage'] = $user_usage;
    update_option( 'login_api_data', $data );
}


/**
 * Retrieve stored API Bearer token.
 *
 * @return string|false The Bearer token if available, otherwise false.
 */
function mockgini_get_api_token() {
    $login_api_data = get_option( 'login_api_data' );

    if ( ! empty( $login_api_data['api_key'] ) ) {
        return sanitize_text_field( $login_api_data['api_key'] );
    }

    return false;
}

/**
 * Retrieve stored user status.
 *
 * @return string|false The user status if available, otherwise false.
 */
function mockgini_get_user_status() {
    $login_api_data = get_option( 'login_api_data' );

    if ( ! empty( $login_api_data['user_status'] ) ) {
        return sanitize_text_field( $login_api_data['user_status'] );
    }

    return false;
}





/**
 * Get the percentage of image usage for the current user.
 *
 * @return float Percentage used.
 */
function mockgenie_get_usage_percentage() {
    $data = mockgenie_get_user_api_data();

    $user_limit = isset($data['user_limit']) ? intval($data['user_limit']) : 0;
    $user_usage = isset($data['user_usage']) ? intval($data['user_usage']) : 0;

    if ($user_limit === 0) {
        return 0; // Avoid division by zero
    }

    return min(100, ($user_usage / $user_limit) * 100);
}

/**
 * Get URL based on type.
 *
 * @param string $type The type of URL ('site' or 'upgrade').
 * @return string
 */
function mockgenie_get_url( $type = 'site' ) {
    switch ( strtolower( $type ) ) {
        case 'upgrade':
            return 'https://addonmaster.com/';
        case 'site':
        default:
            return 'https://www.progressivebyte.com/';
    }
}

