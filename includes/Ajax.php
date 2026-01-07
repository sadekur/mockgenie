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

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax
 */
class Ajax {

    use Hook;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->ajax( 'mg_create_user', [ $this, 'create_user' ] );        
        $this->ajax( 'mg_login_user', [ $this, 'login_user' ] );        
        $this->ajax( 'mg_reset_user', [ $this, 'reset_user' ] );        
        $this->ajax( 'mockgini_generate_image', [ $this, 'mockgini_generate_image' ] );      
        $this->ajax( 'mockgini_save_image', [ $this, 'save_image_after_generate' ] );        
        $this->ajax( 'mg_update_images_per_page', [ $this, 'update_images_per_page' ] );
        $this->ajax( 'mockgenie_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
        
    }    

    function save_image_after_generate() {

        // Verify nonce for security
        check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

        $prompt     = sanitize_text_field( wp_unslash( $_POST['prompt'] ?? '' ) );

        $image_base64 = isset( $_POST['image_base64'] )
            ? base64_encode( base64_decode( wp_unslash( $_POST['image_base64'] ), true ) )
            : '';


        if ( empty( $image_base64 ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'No image data provided.', 'mockgenie' ),
                )
            );
        }

        // Strip any data URL prefix if present
        $image_base64 = preg_replace( '#^data:image/\w+;base64,#i', '', $image_base64 );
        $image_data   = base64_decode( $image_base64 );

        if ( ! $image_data ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Invalid image format or corrupt base64 data.', 'mockgenie' ),
                )
            );
        }

        // Save as PNG under uploads
        $upload_dir = wp_upload_dir();
        $filename   = 'mockgenie-' . time() . '.png';
        $file_path  = trailingslashit( $upload_dir['path'] ) . $filename;

        file_put_contents( $file_path, $image_data );

        // Prepare attachment
        $filetype   = wp_check_filetype( $filename, null );
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $prompt ?: $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $file_path );
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // Save custom meta for this image
        update_post_meta( $attach_id, 'mockgenie_image', 1);

        $url = wp_get_attachment_url( $attach_id );

        wp_send_json_success(
            array(
                'message'   => esc_html__( 'Image saved.', 'mockgenie' ),
                'image_url' => esc_url_raw( $url ),
            )
        );
    }



    /**
     * AJAX handler to generate an image via MockGenie API.
     */
    function mockgini_generate_image() {

        // Verify nonce for security.
        check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

        // Dynamic URL to MockGenie settings page.
        $mockgenie_url = admin_url( 'tools.php?page=mockgenie' );

        // Sanitize and validate prompt.
        $prompt = isset( $_POST['prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt'] ) ) : '';
        if ( empty( $prompt ) ) {
            wp_send_json_error(
                [ 'message' => esc_html__( 'Prompt is empty.', 'mockgenie' ) ],
                400
            );
        }

        // Get API token.
        $api_token = mockgini_get_api_token();
        if ( ! $api_token ) {
            wp_send_json_error(
                [ 'message' => esc_html__( 'API token missing. Please log in again.', 'mockgenie' ) ],
                401
            );
        }

        // Get user status.
        $user_status = mockgini_get_user_status();
        if ( ! $user_status ) {
            wp_send_json_error(
                [
                    'message' => sprintf(
                        /* translators: %s: link to MockGenie settings page */
                        esc_html__( 'Please log in and check %s under Tools.', 'mockgenie' ),
                        '<a href="' . esc_url( $mockgenie_url ) . '" target="_blank">MockGenie settings</a>'
                    ),
                ],
                401
            );
        }

        // Check user limit.
        if ( ! mockgenie_can_generate_image() ) {
            wp_send_json_error(
                [
                    'message' => sprintf(
                        /* translators: %s: link to MockGenie settings page */
                        esc_html__( 'Your generation limit has been exceeded. Please %s or upgrade your plan.', 'mockgenie' ),
                        '<a href="' . esc_url( $mockgenie_url ) . '" target="_blank">check MockGenie settings</a>'
                    ),
                ],
                403
            );
        }

        // Build API URL.
        $api_url = add_query_arg(
            [ 'prompt' => urlencode( $prompt ) ],
            API_BASE . '/generate-image'
        );

        // Send request with Bearer token.
        $response = wp_remote_get(
            $api_url,
            [
                'timeout'   => 60,
                'sslverify' => false,
                'headers'   => [
                    'Authorization' => 'Bearer ' . $api_token,
                    'Accept'        => 'application/json',
                ],
            ]
        );

        // Handle network/API connection errors.
        if ( is_wp_error( $response ) ) {
            wp_send_json_error(
                [ 'message' => esc_html__( 'API not connected: ', 'mockgenie' ) . $response->get_error_message() ],
                502
            );
        }

        // Check HTTP response code.
        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );

        if ( $http_code < 200 || $http_code >= 300 ) {
            $data = json_decode( $body, true );

            if ( isset( $data['error'] ) ) {
                // Use the error object from the API directly
                wp_send_json_error(
                    array(
                        'message' => $data['error'],
                        'raw'     => $body,
                    ),
                    $http_code
                );
            }

            // If API doesn't return an 'error' key, just send raw body
            wp_send_json_error(
                array(
                    'message' => $body,
                ),
                $http_code
            );
        }




        // Get and validate response body.
        // $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            wp_send_json_error(
                [ 'message' => esc_html__( 'API connected but returned an empty response.', 'mockgenie' ) ],
                502
            );
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $data ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'Invalid or malformed API response.', 'mockgenie' ),
                    'raw'     => $body,
                ],
                500
            );
        }

        // Increase user usage count after successful generation.
        mockgenie_decrease_user_usage();

        // Return success response.
        wp_send_json_success(
            [
                'prompt'       => $prompt,
                'api_url'      => $api_url,
                'api_response' => $data,
            ]
        );
    }

    /**
     * Handle AJAX request to create a new user via MockGenie API.
     *
     * @return void
     */
    public function create_user() {

        // Verify nonce for security.
        check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

        // Sanitize and validate email.
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        if ( empty( $email ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'Email is required', 'mockgenie' ),
                ]
            );
        }

        // Call the REST API endpoint.
        $response = wp_remote_post(
            API_BASE . '/create-user',
            [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( [ 'email' => $email ] ),
            ]
        );

        // Check for WP_Error.
        if ( is_wp_error( $response ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html( $response->get_error_message() ),
                ]
            );
        }

        // Decode API response.
        $api_response = json_decode( wp_remote_retrieve_body( $response ), true );

        // Validate decoded response.
        if ( empty( $api_response ) || ! is_array( $api_response ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'Invalid API response', 'mockgenie' ),
                ]
            );
        }        

        // Return success or error.
        if ( ! empty( $api_response['success'] ) ) {
            wp_send_json_success( $api_response );
        }

        wp_send_json_error(
            [
                'message' => esc_html( $api_response['message'] ?? 'Account creation failed.' ),
            ]
        );
    }

    public function login_user() {

        // Verify nonce
        check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

        $login_input = sanitize_text_field( wp_unslash( $_POST['mg_username'] ?? '' ) );
        $password    = sanitize_text_field( wp_unslash( $_POST['mg_password'] ?? '' ) );

        if ( empty( $login_input ) || empty( $password ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Both username/email and password are required.', 'mockgenie' ) ] );
        }

        // Determine if input is an email or username
        $is_email = is_email( $login_input );

        // Prepare payload for API
        $payload = [
            'password' => $password,
        ];

        if ( $is_email ) {
            $payload['login']   = $login_input;
        } else {
            $payload['login']   = $login_input;
        }

        // Call REST API
        $response = wp_remote_post( API_BASE . '/login-user', [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => esc_html( $response->get_error_message() ) ] );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        update_option( 'login_api_data', $data['data'] );

        if ( ! empty( $data['success'] ) ) {
            update_option( 'login_api_data', $data['data'] );
            wp_send_json_success( $data );
        }

        wp_send_json_error( [ 'message' => $data['message'] ?? 'Login failed.' ] );
    }

	/**
	 * Reset user password via API.
	 *
	 * @return void
	 */
	public function reset_user() {

	    // Verify nonce for security.
	    check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

	    // Sanitize and validate email.
	    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	    if ( empty( $email ) ) {
	        wp_send_json_error(
	            [
	                'message' => esc_html__( 'Email is required.', 'mockgenie' ),
	            ]
	        );
	    }

	    // Call the REST API endpoint.
	    $response = wp_remote_post(
	        API_BASE . '/reset-password',
	        [
	            'headers' => [
	                'Content-Type' => 'application/json',
	            ],
	            'body'    => wp_json_encode( [ 'email' => $email ] ),
	        ]
	    );

	    // Check for WP_Error.
	    if ( is_wp_error( $response ) ) {
	        wp_send_json_error(
	            [
	                'message' => esc_html( $response->get_error_message() ),
	            ]
	        );
	    }

	    // Decode API response.
	    $api_response = json_decode( wp_remote_retrieve_body( $response ), true );

	    // Validate decoded response.
	    if ( empty( $api_response ) || ! is_array( $api_response ) ) {
	        wp_send_json_error(
	            [
	                'message' => esc_html__( 'Invalid API response.', 'mockgenie' ),
	            ]
	        );
	    }

	    if ( get_option( 'login_api_data', false ) !== false ) {
		    delete_option( 'login_api_data' );
		}


	    // Return success or error based on API response.
	    if ( ! empty( $api_response['success'] ) ) {
	        wp_send_json_success( $api_response );
	    }

	    wp_send_json_error(
	        [
	            'message' => esc_html( $api_response['data']['message'] ?? esc_html__( 'Password reset failed.', 'mockgenie' ) ),
	        ]
	    );
	}

    /**
     * Update the "Images Per Page" setting for the current user via AJAX.
     */
    function update_images_per_page() {
        // Verify nonce for security.
        check_ajax_referer( 'mockgenie_nonce', '_wpnonce' );

        // Get current user ID.
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'User not logged in.', 'mockgenie' ),
                ),
                401
            );
        }

        // Sanitize input and enforce limits (1–50).
        $images_per_page = isset( $_POST['images_per_page'] ) ? intval( wp_unslash( $_POST['images_per_page'] ) ) : 8;
        $images_per_page = max( 1, min( 50, $images_per_page ) );

        // Save to user meta.
        update_user_meta( $user_id, 'mg_images_per_page', $images_per_page );

        // Return success response.
        wp_send_json_success(
            array(
                'message' => esc_html__( 'Images per page updated successfully!', 'mockgenie' ),
                'value'   => $images_per_page,
            )
        );
    }

}