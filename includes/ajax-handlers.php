<?php
/**
 * AJAX Handlers for Salesforce Form Submission
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AJAX handler for Salesforce NEW USER form submission
 */
function afa_salesforce_handle_ajax_submission() {
	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'afa_salesforce_ajax_nonce' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Security check failed. Please refresh the page and try again.', 'afa-salesforce' )
		), 403 );
	}

	// Sanitize and validate form data
	$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
	$last_name = sanitize_text_field( $_POST['last_name'] ?? '' );
	$email = sanitize_email( $_POST['email'] ?? '' );
	$affiliation = sanitize_text_field( $_POST['affiliation'] ?? '' );
	$branch = sanitize_text_field( $_POST['branch'] ?? '' );
	$zip_code = sanitize_text_field( $_POST['zip_code'] ?? '' );
	$chapter_recruited = sanitize_text_field( $_POST['chapter_recruited'] ?? '0' );
	$source = sanitize_text_field( $_POST['source'] ?? '' );
	$intent = sanitize_text_field( $_POST['intent'] ?? '' );

	// Validate required fields
	$errors = array();
	if ( empty( $first_name ) ) {
		$errors[] = __( 'First name is required', 'afa-salesforce' );
	}
	if ( empty( $last_name ) ) {
		$errors[] = __( 'Last name is required', 'afa-salesforce' );
	}
	if ( empty( $email ) ) {
		$errors[] = __( 'Email is required', 'afa-salesforce' );
	}
	if ( ! empty( $email ) && ! is_email( $email ) ) {
		$errors[] = __( 'Email address is invalid', 'afa-salesforce' );
	}
	if ( empty( $zip_code ) ) {
		$errors[] = __( 'A zip or postal code is required', 'afa-salesforce' );
	}

	// Handle validation errors
	if ( ! empty( $errors ) ) {
		wp_send_json_error( array(
			'message' => implode( ' ', $errors ),
			'errors'  => $errors
		), 400 );
	}

	// Prepare user data for Salesforce
	$user_data = array(
		'first_name'        => $first_name,
		'last_name'         => $last_name,
		'email'             => $email,
		'affiliation'       => $affiliation,
		'branch'            => $branch,
		'zip_code'          => $zip_code,
		'chapter_recruited' => $chapter_recruited,
		'source'            => $source,
		'intent'            => $intent,
	);

	// Allow filtering of user data before sending to Salesforce
	$user_data = apply_filters( 'afa_salesforce_user_data', $user_data, $_POST );

	// Initialize Salesforce integration
	try {
		$salesforce = new AFA_Salesforce_Integration();

		// Create user in Salesforce
		$result = $salesforce->create_user( $user_data );

		// Handle result
		if ( is_wp_error( $result ) ) {
			// Log detailed error information
			error_log( 'Salesforce API Error: ' . $result->get_error_message() );
			$error_data = $result->get_error_data();
			if ( ! empty( $error_data ) ) {
				error_log( 'Salesforce Error Data: ' . print_r( $error_data, true ) );
			}

			// Check if it's a duplicate email error (409)
			if ( isset( $error_data['status_code'] ) && $error_data['status_code'] === 409 ) {
				wp_send_json_error( array(
					'message' => __( 'A user with this email address already exists in our system.', 'afa-salesforce' ),
					'code' => 'duplicate_email'
				), 409 );
			}

			// Generic error response
			$user_message = __( 'An error occurred while creating your account. Please try again later.', 'afa-salesforce' );

			// In debug mode, include more details
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$user_message = $result->get_error_message();
			}

			wp_send_json_error( array(
				'message' => $user_message
			), 500 );
		} else {
			// Success - extract user ID from response
			$salesforce_user_id = isset( $result['data']['contactId'] ) ? $result['data']['contactId'] :
								 ( isset( $result['data']['Id'] ) ? $result['data']['Id'] : 'Unknown' );

			// Log success
			error_log( 'Salesforce user created successfully. ID: ' . $salesforce_user_id );

			// Allow actions after successful creation
			do_action( 'afa_salesforce_user_created', $salesforce_user_id, $user_data, $result );

			// Return success response
			wp_send_json_success( array(
				'message' => __( 'Your account has been created successfully!', 'afa-salesforce' ),
				'contact_id' => $salesforce_user_id
			), 201 );
		}
	} catch ( Exception $e ) {
		error_log( 'Salesforce Integration Exception: ' . $e->getMessage() );
		wp_send_json_error( array(
			'message' => __( 'An unexpected error occurred. Please try again later.', 'afa-salesforce' )
		), 500 );
	}
}
// Register AJAX handlers for both logged-in and non-logged-in users
add_action( 'wp_ajax_afa_salesforce_submit', 'afa_salesforce_handle_ajax_submission' );
add_action( 'wp_ajax_nopriv_afa_salesforce_submit', 'afa_salesforce_handle_ajax_submission' );

/**
 * AJAX handler for initiating OAuth login
 *
 * Returns the OAuth authorization URL for the user to be redirected to
 */
/*
function afa_salesforce_handle_oauth_login() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'afa_salesforce_ajax_nonce' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Security check failed. Please refresh the page and try again.', 'afa-salesforce' )
		), 403 );
	}

	try {
		$oauth = new AFA_Salesforce_OAuth();

		// Validate OAuth configuration
		$config_valid = $oauth->validate_config();
		if ( is_wp_error( $config_valid ) ) {
			error_log( 'Salesforce OAuth Config Error: ' . $config_valid->get_error_message() );

			$user_message = __( 'OAuth is not properly configured. Please contact the administrator.', 'afa-salesforce' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$user_message = $config_valid->get_error_message();
			}

			wp_send_json_error( array(
				'message' => $user_message
			), 500 );
		}

		// Generate authorization URL with state parameter
		$state = wp_generate_password( 32, false );

		// Store the return URL (originating page) with the state
		$return_url = isset( $_POST['return_url'] ) ? esc_url_raw( $_POST['return_url'] ) : home_url();
		set_transient( 'afa_sf_oauth_return_' . $state, $return_url, 600 ); // 10 minutes

		$auth_url = $oauth->get_authorization_url( $state );

		// Return the URL for client-side redirect
		wp_send_json_success( array(
			'auth_url' => $auth_url
		), 200 );

	} catch ( Exception $e ) {
		error_log( 'Salesforce OAuth Exception: ' . $e->getMessage() );
		wp_send_json_error( array(
			'message' => __( 'An unexpected error occurred. Please try again later.', 'afa-salesforce' )
		), 500 );
	}
}
*/
// DISABLED WHILE WE STICK WITH EXISTING SAML PLUGIN for 2026
// add_action( 'wp_ajax_afa_salesforce_oauth_login', 'afa_salesforce_handle_oauth_login' );
// add_action( 'wp_ajax_nopriv_afa_salesforce_oauth_login', 'afa_salesforce_handle_oauth_login' );

/**
 * Handle OAuth callback from Salesforce
 *
 * This processes the authorization code and sets the auth cookie
 */
/*
function afa_salesforce_oauth_callback() {
	// Only process on the callback URL
	$request_uri = $_SERVER['REQUEST_URI'];
	if ( strpos( $request_uri, '/salesforce-auth-callback' ) === false ) {
		return;
	}

	// Check for error from Salesforce
	if ( isset( $_GET['error'] ) ) {
		$error_description = isset( $_GET['error_description'] )
			? urldecode( $_GET['error_description'] )
			: 'Authorization failed';

		error_log( 'Salesforce OAuth Error: ' . $error_description );

		// Redirect to home with error message
		wp_redirect( add_query_arg( array(
			'sf_auth' => 'error',
			'sf_msg' => urlencode( $error_description )
		), home_url() ) );
		exit;
	}

	// Verify we have required parameters
	if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
		error_log( 'Salesforce OAuth: Missing code or state parameter' );
		wp_redirect( add_query_arg( array(
			'sf_auth' => 'error',
			'sf_msg' => urlencode( 'Invalid callback parameters' )
		), home_url() ) );
		exit;
	}

	$code = sanitize_text_field( $_GET['code'] );
	$state = sanitize_text_field( $_GET['state'] );

	try {
		$oauth = new AFA_Salesforce_OAuth();

		// Verify state parameter (CSRF protection)
		if ( ! $oauth->verify_state( $state ) ) {
			error_log( 'Salesforce OAuth: Invalid state parameter' );
			wp_redirect( add_query_arg( array(
				'sf_auth' => 'error',
				'sf_msg' => urlencode( 'Invalid state parameter' )
			), home_url() ) );
			exit;
		}

		// Exchange code for token (with PKCE code_verifier)
		$token_data = $oauth->exchange_code_for_token( $code, $state );
		if ( is_wp_error( $token_data ) ) {
			error_log( 'Salesforce OAuth Token Error: ' . $token_data->get_error_message() );
			wp_redirect( add_query_arg( array(
				'sf_auth' => 'error',
				'sf_msg' => urlencode( 'Failed to obtain access token' )
			), home_url() ) );
			exit;
		}

		// Get user info
		$user_info = $oauth->get_user_info( $token_data['access_token'] );
		if ( is_wp_error( $user_info ) ) {
			error_log( 'Salesforce OAuth UserInfo Error: ' . $user_info->get_error_message() );
			wp_redirect( add_query_arg( array(
				'sf_auth' => 'error',
				'sf_msg' => urlencode( 'Failed to retrieve user information' )
			), home_url() ) );
			exit;
		}

		// Extract Salesforce User ID
		$sf_user_id = isset( $user_info['user_id'] ) ? $user_info['user_id'] : null;

		// If user_id is not directly available, try the 'sub' claim (standard OpenID Connect)
		if ( empty( $sf_user_id ) && isset( $user_info['sub'] ) ) {
			// The 'sub' claim contains the full URL, extract the ID
			$sub_parts = explode( '/', $user_info['sub'] );
			$sf_user_id = end( $sub_parts );
		}

		if ( empty( $sf_user_id ) ) {
			error_log( 'Salesforce OAuth: User ID not found in response' );
			error_log( 'User Info: ' . print_r( $user_info, true ) );
			wp_redirect( add_query_arg( array(
				'sf_auth' => 'error',
				'sf_msg' => urlencode( 'User ID not found' )
			), home_url() ) );
			exit;
		}

		// Set authentication cookie
		$oauth->set_auth_cookie( $sf_user_id, array(
			'email' => isset( $user_info['email'] ) ? $user_info['email'] : '',
			'name' => isset( $user_info['name'] ) ? $user_info['name'] : '',
			'organization_id' => isset( $user_info['organization_id'] ) ? $user_info['organization_id'] : ''
		) );

		// Log successful login
		error_log( 'Salesforce OAuth: User logged in successfully. ID: ' . $sf_user_id );

		// Allow actions after successful login
		do_action( 'afa_salesforce_user_logged_in', $sf_user_id, $user_info );

		// Get the stored return URL (page user was on before login)
		$return_url = get_transient( 'afa_sf_oauth_return_' . $state );
		if ( $return_url ) {
			// Clean up the transient
			delete_transient( 'afa_sf_oauth_return_' . $state );
		} else {
			// Fallback to home if no return URL stored
			$return_url = home_url();
		}

		// Redirect back to the originating page with success parameter
		wp_redirect( add_query_arg( array(
			'sf_auth' => 'success'
		), $return_url ) );
		exit;

	} catch ( Exception $e ) {
		error_log( 'Salesforce OAuth Exception: ' . $e->getMessage() );
		wp_redirect( add_query_arg( array(
			'sf_auth' => 'error',
			'sf_msg' => urlencode( 'An unexpected error occurred' )
		), home_url() ) );
		exit;
	}
}
*/
// DISABLED WHILE WE STICK WITH EXISTING SAML PLUGIN for 2026
// Hook into template_redirect to handle callback before any output
// add_action( 'template_redirect', 'afa_salesforce_oauth_callback' );
