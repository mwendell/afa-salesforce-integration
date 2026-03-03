<?php
/**
 * Salesforce OAuth Authentication Class
 *
 * Handles OAuth 2.0 / OpenID Connect authentication for Salesforce Users
 *
 * @package AFA_Salesforce
 * @since 1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class AFA_Salesforce_OAuth {

	/**
	 * Salesforce OAuth configuration
	 */
	private $client_id;
	private $client_secret;
	private $redirect_uri;
	private $authorize_url;
	private $token_url;
	private $userinfo_url;
	private $is_sandbox;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load OAuth settings from WordPress options
	 */
	private function load_settings() {
		$this->is_sandbox = get_option( 'afa_salesforce_is_sandbox', true );
		
		// OAuth-specific settings
		$this->client_id = get_option( 'afa_salesforce_oauth_client_id', '' );
		$this->client_secret = get_option( 'afa_salesforce_oauth_client_secret', '' );
		$this->redirect_uri = get_option( 'afa_salesforce_oauth_redirect_uri', home_url( '/salesforce-auth-callback/' ) );

		// Set URLs based on environment
		if ( $this->is_sandbox ) {
			$this->authorize_url = 'https://test.salesforce.com/services/oauth2/authorize';
			$this->token_url = 'https://test.salesforce.com/services/oauth2/token';
			$this->userinfo_url = 'https://test.salesforce.com/services/oauth2/userinfo';
		} else {
			$this->authorize_url = 'https://login.salesforce.com/services/oauth2/authorize';
			$this->token_url = 'https://login.salesforce.com/services/oauth2/token';
			$this->userinfo_url = 'https://login.salesforce.com/services/oauth2/userinfo';
		}
	}

	/**
	 * Generate code verifier for PKCE
	 *
	 * @return string Base64 URL-encoded random string
	 */
	private function generate_code_verifier() {
		$random_bytes = random_bytes( 32 );
		return rtrim( strtr( base64_encode( $random_bytes ), '+/', '-_' ), '=' );
	}

	/**
	 * Generate code challenge from verifier for PKCE
	 *
	 * @param string $verifier Code verifier
	 * @return string Base64 URL-encoded SHA256 hash
	 */
	private function generate_code_challenge( $verifier ) {
		$hash = hash( 'sha256', $verifier, true );
		return rtrim( strtr( base64_encode( $hash ), '+/', '-_' ), '=' );
	}

	/**
	 * Generate OAuth authorization URL with PKCE support
	 *
	 * @param string $state Random state parameter for CSRF protection
	 * @return string Authorization URL
	 */
	public function get_authorization_url( $state = null ) {
		if ( empty( $state ) ) {
			$state = wp_generate_password( 32, false );
		}

		// Generate PKCE code verifier and challenge
		$code_verifier = $this->generate_code_verifier();
		$code_challenge = $this->generate_code_challenge( $code_verifier );

		// Store state and code verifier in transient for later verification
		set_transient( 'afa_sf_oauth_state_' . $state, time(), 600 ); // 10 minutes
		set_transient( 'afa_sf_oauth_verifier_' . $state, $code_verifier, 600 ); // 10 minutes

		$params = array(
			'response_type' => 'code',
			'client_id' => $this->client_id,
			'redirect_uri' => $this->redirect_uri,
			'state' => $state,
			'scope' => 'openid id email profile',
			'code_challenge' => $code_challenge,
			'code_challenge_method' => 'S256'
		);

		return $this->authorize_url . '?' . http_build_query( $params );
	}

	/**
	 * Exchange authorization code for access token with PKCE
	 *
	 * @param string $code Authorization code from callback
	 * @param string $state State parameter for retrieving code verifier
	 * @return array|WP_Error Token data or error
	 */
	public function exchange_code_for_token( $code, $state = null ) {
		if ( empty( $code ) ) {
			return new WP_Error( 'invalid_code', 'Authorization code is required' );
		}

		$params = array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' => $this->redirect_uri
		);

		// Add code_verifier if state is provided (PKCE flow)
		if ( ! empty( $state ) ) {
			$code_verifier = get_transient( 'afa_sf_oauth_verifier_' . $state );
			if ( $code_verifier ) {
				$params['code_verifier'] = $code_verifier;
				// Clean up the verifier transient
				delete_transient( 'afa_sf_oauth_verifier_' . $state );
			}
		}

		$response = wp_remote_post( $this->token_url, array(
			'body' => $params,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded'
			)
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Salesforce OAuth Token Error: ' . $response->get_error_message() );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$token_data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$error_msg = isset( $token_data['error_description'] ) 
				? $token_data['error_description'] 
				: 'Token exchange failed';
			error_log( 'Salesforce OAuth Token Error (HTTP ' . $response_code . '): ' . $error_msg );
			return new WP_Error( 'token_error', $error_msg, $token_data );
		}

		if ( ! isset( $token_data['access_token'] ) ) {
			error_log( 'Salesforce OAuth: Access token not found in response' );
			return new WP_Error( 'missing_token', 'Access token not found in response' );
		}

		return $token_data;
	}

	/**
	 * Get user info from Salesforce using access token
	 *
	 * @param string $access_token Access token
	 * @return array|WP_Error User info or error
	 */
	public function get_user_info( $access_token ) {
		if ( empty( $access_token ) ) {
			return new WP_Error( 'invalid_token', 'Access token is required' );
		}

		$response = wp_remote_get( $this->userinfo_url, array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token
			)
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Salesforce OAuth UserInfo Error: ' . $response->get_error_message() );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$user_data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$error_msg = isset( $user_data['error'] ) 
				? $user_data['error'] 
				: 'Failed to retrieve user info';
			error_log( 'Salesforce OAuth UserInfo Error (HTTP ' . $response_code . '): ' . $error_msg );
			return new WP_Error( 'userinfo_error', $error_msg, $user_data );
		}

		return $user_data;
	}

	/**
	 * Verify OAuth state parameter
	 *
	 * @param string $state State parameter to verify
	 * @return bool True if valid
	 */
	public function verify_state( $state ) {
		if ( empty( $state ) ) {
			return false;
		}

		$stored = get_transient( 'afa_sf_oauth_state_' . $state );
		if ( $stored === false ) {
			return false;
		}

		// Delete after verification
		delete_transient( 'afa_sf_oauth_state_' . $state );
		return true;
	}

	/**
	 * Set authentication cookie with Salesforce User ID
	 *
	 * @param string $user_id Salesforce User ID
	 * @param array $user_data Additional user data to store
	 * @return void
	 */
	public function set_auth_cookie( $user_id, $user_data = array() ) {
		// Set cookie with user ID
		$cookie_name = 'afa_sf_user_id';
		$cookie_value = $user_id;
		$expiration = time() + ( 30 * DAY_IN_SECONDS ); // 30 days

		setcookie(
			$cookie_name,
			$cookie_value,
			array(
				'expires' => $expiration,
				'path' => '/',
				'domain' => '',
				'secure' => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax'
			)
		);

		// Store additional user data in session/transient if needed
		if ( ! empty( $user_data ) ) {
			set_transient( 'afa_sf_user_data_' . $user_id, $user_data, 30 * DAY_IN_SECONDS );
		}

		// Set $_COOKIE immediately for same-request access
		$_COOKIE[$cookie_name] = $cookie_value;
	}

	/**
	 * Get current authenticated user ID from cookie
	 *
	 * @return string|null User ID or null if not authenticated
	 */
	public function get_current_user_id() {
		return isset( $_COOKIE['afa_sf_user_id'] ) ? $_COOKIE['afa_sf_user_id'] : null;
	}

	/**
	 * Check if user is authenticated
	 *
	 * @return bool True if authenticated
	 */
	public function is_authenticated() {
		return ! empty( $this->get_current_user_id() );
	}

	/**
	 * Clear authentication cookie
	 *
	 * @return void
	 */
	public function clear_auth_cookie() {
		$user_id = $this->get_current_user_id();
		
		if ( $user_id ) {
			// Delete user data
			delete_transient( 'afa_sf_user_data_' . $user_id );
		}

		// Clear cookie
		setcookie(
			'afa_sf_user_id',
			'',
			array(
				'expires' => time() - 3600,
				'path' => '/',
				'domain' => '',
				'secure' => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax'
			)
		);

		unset( $_COOKIE['afa_sf_user_id'] );
	}

	/**
	 * Validate configuration
	 *
	 * @return bool|WP_Error True if valid, error otherwise
	 */
	public function validate_config() {
		$errors = array();

		if ( empty( $this->client_id ) ) {
			$errors[] = 'OAuth Client ID is not configured';
		}

		if ( empty( $this->client_secret ) ) {
			$errors[] = 'OAuth Client Secret is not configured';
		}

		if ( empty( $this->redirect_uri ) ) {
			$errors[] = 'OAuth Redirect URI is not configured';
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'config_error', implode( ', ', $errors ) );
		}

		return true;
	}
}
