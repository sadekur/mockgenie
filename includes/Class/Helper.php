<?php
namespace MockGenie\Class;

defined( 'ABSPATH' ) || exit;

class Helper {

    /**
     * Get a value from the WordPress options table.
     *
     * @param string $option_name The name of the option to retrieve.
     * @param mixed  $default     Optional default value if option does not exist.
     *
     * @return mixed
     */
    public static function get_option( $option_name, $default = null ) {
        $value = get_option( $option_name, $default );
        return maybe_unserialize( $value );
    }

    /**
     * Print or return data inside a nicely formatted <pre> block for debugging.
     *
     * If $label is true, it will treat $data as an option name and fetch its value.
     *
     * @param mixed       $data    Data or option name to print.
     * @param string|bool $label   Label for the debug output or true to fetch option.
     * @param bool        $return  If true, return string instead of echoing.
     *
     * @return void|string
     */
    public static function pri( $data, $label = '', $return = false ) {

        // If label is true, fetch data from option table
        if ( $label === true && is_string( $data ) ) {
            $option_data = self::get_option( $data );
            $label       = 'Option: ' . $data; // Use option name as label
            $data        = $option_data;
        }

        $output  = '<pre style="background:#f4f4f4; color:#333; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px;">';

        if ( $label && is_string( $label ) ) {
            $output .= '<strong style="color:#d6336c;">' . esc_html( $label ) . ':</strong>' . PHP_EOL;
        }

        $type = gettype( $data );
        $output .= '<em style="color:#6c757d;">Type: ' . esc_html( $type ) . '</em>' . PHP_EOL;

        // Safely render array/object
        $output .= esc_html( var_export( $data, true ) );

        $output .= '</pre>';

        if ( $return ) {
            return $output;
        }

        echo $output;
    }


    /**
     * Get current user's MockGenie API data
     *
     * @return array
     */
    public static function get_user_api_data() {
        $data = get_option( 'login_api_data', [] );
        return is_array( $data ) ? $data : [];
    }

    /**
     * Check if user can generate more images
     *
     * @return bool True if under limit, false if limit reached
     */
    public static function can_generate_image() {
        $data = self::get_user_api_data();

        // Check if the API response is nested under 'data'
        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            $data = $data['data'];
        }

        $user_limit   = isset( $data['user_limit'] ) ? intval( $data['user_limit'] ) : 0;
        $actual_limit = isset( $data['limit'] ) ? intval( $data['limit'] ) : 0;

        // If actual_limit is 0, allow unlimited
        if ( $actual_limit === 0 ) {
            return true;
        }

        return $user_limit <= $actual_limit;
    }
    /**
     * Get usage percentage
     *
     * @return float Percentage used
     */
    public static function get_usage_percentage() {
        $data = self::get_user_api_data();

        $user_limit   = isset( $data['user_limit'] ) ? intval( $data['user_limit'] ) : 0;
        $actual_limit = isset( $data['limit'] ) ? intval( $data['limit'] ) : 0;

        if ( $actual_limit === 0 ) {
            return 0;
        }

        return min( 100, ( $user_limit / $actual_limit ) * 100 );
    }
}
