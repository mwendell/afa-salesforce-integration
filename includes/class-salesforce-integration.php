<?php
/**
 * Salesforce Integration Class
 *
 * Handles JWT-based OAuth2 authentication and user creation
 * in Salesforce via their APEX REST API.
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class AFA_Salesforce_Integration {

	/**
	 * Salesforce configuration
	 */
	private $is_sandbox;
	private $sandbox_url;
	private $login_url;
	private $consumer_key;
	private $username;
	private $apex_endpoint_create;
	// private $apex_endpoint_auth;

	/**
	 * Constructor
	 *
	 * @param array $config Optional configuration array to override defaults
	 */
	public function __construct( $config = array() ) {
		// Get settings from WordPress options or use defaults
		$this->load_settings( $config );

		// Validate required configuration
		$this->validate_configuration();
	}

	/**
	 * Load settings from WordPress options or config array
	 *
	 * @param array $config Optional configuration array
	 */
	private function load_settings( $config = array() ) {
		// Determine if using sandbox or production
		$this->is_sandbox = isset( $config['is_sandbox'] )
			? $config['is_sandbox']
			: get_option( 'afa_salesforce_is_sandbox', true );

		// Set login URL based on environment
		if ( $this->is_sandbox ) {
			$this->login_url = 'https://test.salesforce.com';
			$this->sandbox_url = isset( $config['sandbox_url'] )
				? $config['sandbox_url']
				: get_option( 'afa_salesforce_sandbox_url', '' );
			$this->username = isset( $config['username'] )
				? $config['username']
				: get_option( 'afa_salesforce_username', '' );
		} else {
			$this->login_url = 'https://login.salesforce.com';
			$this->sandbox_url = isset( $config['instance_url'] )
				? $config['instance_url']
				: get_option( 'afa_salesforce_instance_url', '' );
			$this->username = isset( $config['username'] )
				? $config['username']
				: get_option( 'afa_salesforce_username', '' );
		}

		// Load other configuration
		$this->consumer_key = isset( $config['consumer_key'] )
			? $config['consumer_key']
			: get_option( 'afa_salesforce_consumer_key', '' );

		$this->apex_endpoint_create = isset( $config['apex_endpoint_create'] )
			? $config['apex_endpoint_create']
			: get_option( 'afa_salesforce_apex_endpoint_create', '/services/apexrest/AFACreateUserContact' );

		/*
		$this->apex_endpoint_auth = isset( $config['apex_endpoint_auth'] )
			? $config['apex_endpoint_auth']
			: get_option( 'afa_salesforce_apex_endpoint_auth', '/services/apexrest/AFAUserAuth' );
		*/
		}

	/**
	 * Validate that all required configuration is present
	 *
	 * @return void
	 */
	private function validate_configuration() {
		$errors = array();

		if ( empty( $this->consumer_key ) ) {
			$errors[] = 'Consumer Key is not configured';
		}

		if ( empty( $this->username ) ) {
			$errors[] = 'Username is not configured';
		}

		if ( empty( get_option( 'afa_salesforce_private_key' ) ) ) {
			$errors[] = 'Private Key is not set';
		}

		if ( empty( $this->sandbox_url ) ) {
			$errors[] = 'Salesforce Instance URL is not configured';
		}

		if ( ! empty( $errors ) ) {
			$error_message = 'Salesforce Integration Configuration Error: ' . implode( ', ', $errors );
			error_log( $error_message );

			// In development, show errors to admins
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
				add_action( 'admin_notices', function() use ( $error_message ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $error_message ) . '</p></div>';
				} );
			}
		}
	}

	/**
	 * UNUSED - Generate JWT token for Salesforce authentication
	 *
	 * @return string|WP_Error JWT token or error
	 */
	private function generate_jwt() {
		$private_key = get_option( 'afa_salesforce_private_key' );

		if ( empty( $private_key ) ) {
			$error_msg = 'Unable to read private key';
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'key_read_error', $error_msg );
		}

		// JWT Header
		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT'
		);

		// JWT Claims
		$current_time = time();
		$claims = array(
			'iss' => $this->consumer_key,
			'sub' => $this->username,
			'aud' => $this->login_url,
			'exp' => $current_time + 300 // Token expires in 5 minutes
		);

		// Encode Header and Claims
		$header_encoded = $this->base64url_encode( json_encode( $header ) );
		$claims_encoded = $this->base64url_encode( json_encode( $claims ) );

		// Create signature
		$signature_input = $header_encoded . '.' . $claims_encoded;
		$signature = '';

		$key_resource = openssl_pkey_get_private( $private_key );
		if ( $key_resource === false ) {
			$error_msg = 'Unable to parse private key: ' . openssl_error_string();
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'key_parse_error', $error_msg );
		}

		$sign_success = openssl_sign( $signature_input, $signature, $key_resource, OPENSSL_ALGO_SHA256 );

		// Only free the key resource for PHP < 8.0 (automatic in PHP 8.0+)
		if ( PHP_VERSION_ID < 80000 ) {
			openssl_free_key( $key_resource );
		}

		if ( ! $sign_success ) {
			$error_msg = 'Unable to sign JWT: ' . openssl_error_string();
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'signing_error', $error_msg );
		}

		$signature_encoded = $this->base64url_encode( $signature );

		// Complete JWT
		$jwt = $header_encoded . '.' . $claims_encoded . '.' . $signature_encoded;

		return $jwt;
	}

	/**
	 * UNUSED - Base64 URL encode (JWT standard)
	 *
	 * @param string $data Data to encode
	 * @return string Encoded data
	 */
	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * UNUSED - Get OAuth2 access token using JWT bearer flow
	 *
	 * @return string|WP_Error Access token or error
	 */
	private function get_access_token() {
		// Check for cached token
		$cached_token = get_transient( 'salesforce_access_token' );
		if ( $cached_token !== false && ! empty( $cached_token ) ) {
			return $cached_token;
		}

		// Generate JWT
		$jwt = $this->generate_jwt();
		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}

		// Prepare OAuth2 token request
		$token_url = $this->login_url . '/services/oauth2/token';
		$params = array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion' => $jwt
		);

		// Make token request
		$response = wp_remote_post( $token_url, array(
			'body' => $params,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded'
			)
		) );

		// Check for request errors
		if ( is_wp_error( $response ) ) {
			$error_msg = 'Token request failed: ' . $response->get_error_message();
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'token_request_failed', $error_msg );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$token_data = json_decode( $response_body, true );

		// Check response code
		if ( $response_code !== 200 ) {
			$error_message = isset( $token_data['error_description'] ) ? $token_data['error_description'] : 'Unknown error';
			$full_error = 'Token request failed (HTTP ' . $response_code . '): ' . $error_message;
			error_log( 'Salesforce Integration Error: ' . $full_error );
			error_log( 'Salesforce Token Response: ' . print_r( $token_data, true ) );
			return new WP_Error( 'token_error', $full_error, $token_data );
		}

		// Extract access token
		if ( ! isset( $token_data['access_token'] ) ) {
			$error_msg = 'Access token not found in response';
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			error_log( 'Salesforce Token Response: ' . print_r( $token_data, true ) );
			return new WP_Error( 'missing_token', $error_msg, $token_data );
		}

		// Cache token for 4 minutes (tokens are valid for 5 minutes)
		set_transient( 'salesforce_access_token', $token_data['access_token'], 240 );

		return $token_data['access_token'];
	}

	/**
	 * Create user in Salesforce via APEX REST API
	 *
	 * @param array $user_data User data to send to Salesforce
	 * @return array|WP_Error Response data or error
	 */
	public function create_user( $user_data ) {
		// Validate user data
		if ( empty( $user_data ) || ! is_array( $user_data ) ) {
			return new WP_Error( 'invalid_data', 'User data must be a non-empty array' );
		}

		// Get access token
		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		// Prepare APEX REST API request
		$api_url = $this->sandbox_url . $this->apex_endpoint_create;

		$response = wp_remote_post( $api_url, array(
			'body' => json_encode( $user_data ),
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json'
			)
		) );

		// Check for request errors
		if ( is_wp_error( $response ) ) {
			$error_msg = 'API request failed: ' . $response->get_error_message();
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'api_request_failed', $error_msg );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		// Log the response for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Salesforce API Response Code: ' . $response_code );
			error_log( 'Salesforce API Response Body: ' . $response_body );
		}

		// Handle response based on status code
		if ( $response_code >= 200 && $response_code < 300 ) {
			// Success
			return array(
				'success' => true,
				'status_code' => $response_code,
				'data' => $response_data
			);
		} else {
			// Error
			$error_message = 'API request failed (HTTP ' . $response_code . ')';
			if ( is_array( $response_data ) && isset( $response_data['message'] ) ) {
				$error_message .= ': ' . $response_data['message'];
			}
			error_log( 'Salesforce Integration Error: ' . $error_message );
			return new WP_Error( 'api_error', $error_message, array(
				'status_code' => $response_code,
				'response' => $response_data
			) );
		}
	}

	/**
	 * UNUSED - Verify user credentials via APEX REST API
	 *
	 * Calls the UserCredentialVerifier Apex class to validate credentials
	 *
	 * @param string $username Email/username to verify
	 * @param string $password Password to verify
	 * @return array|WP_Error Response data or error
	 */
	/*
	public function verify_credentials( $username, $password ) {
		// Validate input
		if ( empty( $username ) || empty( $password ) ) {
			return new WP_Error( 'invalid_credentials', 'Username and password are required' );
		}

		// Get access token
		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		// Prepare APEX REST API request
		$api_url = $this->sandbox_url . $this->apex_endpoint_auth;

		// Prepare request body
		$request_body = array(
			'username' => $username,
			'password' => $password
		);

		// Make API request
		$response = wp_remote_post( $api_url, array(
			'body' => json_encode( $request_body ),
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json'
			)
		) );

		// Check for request errors
		if ( is_wp_error( $response ) ) {
			$error_msg = 'Credential verification request failed: ' . $response->get_error_message();
			error_log( 'Salesforce Integration Error: ' . $error_msg );
			return new WP_Error( 'api_request_failed', $error_msg );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		// Log the response for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Salesforce Credential Verification Response Code: ' . $response_code );
			error_log( 'Salesforce Credential Verification Response Body: ' . $response_body );
		}

		// Handle response based on status code
		if ( $response_code >= 200 && $response_code < 300 ) {
			// Success
			return array(
				'success' => true,
				'status_code' => $response_code,
				'data' => $response_data
			);
		} else {
			// Error
			$error_message = 'Credential verification failed (HTTP ' . $response_code . ')';
			if ( is_array( $response_data ) && isset( $response_data['message'] ) ) {
				$error_message .= ': ' . $response_data['message'];
			}
			error_log( 'Salesforce Integration Error: ' . $error_message );
			return new WP_Error( 'api_error', $error_message, array(
				'status_code' => $response_code,
				'response' => $response_data
			) );
		}
	}
	*/

	/**
	 * Test connection to Salesforce
	 *
	 * @return bool|WP_Error True if connection successful, error otherwise
	 */
	public function test_connection() {
		$access_token = $this->get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}
		return true;
	}

	/**
	 * Clear cached access token
	 *
	 * @return void
	 */
	public function clear_token_cache() {
		delete_transient( 'salesforce_access_token' );
	}

	/**
	 * Get configuration details (for debugging)
	 *
	 * @return array Configuration details
	 */
	public function get_config_info() {
		return array(
			'environment'           => $this->is_sandbox ? 'Sandbox' : 'Production',
			'login_url'             => $this->login_url,
			'instance_url'          => $this->sandbox_url,
			'username'              => $this->username,
			'private_key_set'       => ! empty( get_option( 'afa_salesforce_private_key' ) ),
			'consumer_key_set'      => ! empty( $this->consumer_key ),
			'apex_endpoint_create'  => $this->apex_endpoint_create,
			// 'apex_endpoint_auth' => $this->apex_endpoint_auth,
		);
	}
}
