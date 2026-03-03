<?php
/**
 * Helper Functions for Salesforce Authentication
 *
 * Provides convenient functions for checking authentication status
 * and gating content for authenticated users only.
 *
 * @package AFA_Salesforce
 * @since 1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if the current user is authenticated via Salesforce
 *
 * @return bool True if authenticated
 */
function afa_is_sf_user_authenticated() {
	$oauth = new AFA_Salesforce_OAuth();
	return $oauth->is_authenticated();
}

/**
 * Get the current Salesforce User ID from cookie
 *
 * @return string|null Salesforce User ID or null if not authenticated
 */
function afa_get_sf_user_id() {
	$oauth = new AFA_Salesforce_OAuth();
	return $oauth->get_current_user_id();
}

/**
 * Get the current Salesforce user data
 *
 * @return array|null User data or null if not found
 */
function afa_get_sf_user_data() {
	$user_id = afa_get_sf_user_id();
	if ( ! $user_id ) {
		return null;
	}

	return get_transient( 'afa_sf_user_data_' . $user_id );
}

/**
 * Log out the current Salesforce user
 *
 * @param string $redirect_to Optional URL to redirect to after logout
 * @return void
 */
function afa_sf_logout( $redirect_to = null ) {
	$oauth = new AFA_Salesforce_OAuth();
	$oauth->clear_auth_cookie();

	if ( $redirect_to ) {
		wp_redirect( $redirect_to );
		exit;
	}
}

/**
 * Require Salesforce authentication for content
 *
 * If not authenticated, displays login form or custom message.
 * If authenticated, returns true to allow content display.
 *
 * @param string $message Optional custom message to show when not authenticated
 * @return bool True if authenticated
 */
function afa_require_sf_auth( $message = null ) {
	if ( afa_is_sf_user_authenticated() ) {
		return true;
	}

	if ( $message === null ) {
		$message = __( 'Please log in to view this content.', 'afa-salesforce' );
	}

	echo '<div class="afa-auth-required">';
	echo '<p>' . esc_html( $message ) . '</p>';
	echo do_shortcode( '[afa_salesforce_form]' );
	echo '</div>';

	return false;
}

/**
 * Shortcode to display content only to authenticated Salesforce users
 *
 * Usage: [afa_members_only]This content is only visible to logged-in members[/afa_members_only]
 *
 * @param array $atts Shortcode attributes
 * @param string $content Content between shortcode tags
 * @return string Rendered content or login prompt
 */
function afa_members_only_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'message' => __( 'This content is only available to members. Please log in.', 'afa-salesforce' )
	), $atts, 'afa_members_only' );

	if ( ! afa_is_sf_user_authenticated() ) {
		ob_start();
		echo '<div class="afa-members-only-gate">';
		echo '<p class="afa-members-message">' . esc_html( $atts['message'] ) . '</p>';
		echo do_shortcode( '[afa_salesforce_form]' );
		echo '</div>';
		return ob_get_clean();
	}

	return do_shortcode( $content );
}
add_shortcode( 'afa_members_only', 'afa_members_only_shortcode' );

/**
 * Shortcode to display Salesforce user info
 *
 * Usage: [afa_user_info field="name"]
 *
 * Available fields: name, email, user_id
 *
 * @param array $atts Shortcode attributes
 * @return string Rendered user info
 */
function afa_user_info_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'field' => 'name',
		'default' => ''
	), $atts, 'afa_user_info' );

	if ( ! afa_is_sf_user_authenticated() ) {
		return $atts['default'];
	}

	$user_data = afa_get_sf_user_data();
	if ( ! $user_data ) {
		return $atts['default'];
	}

	$field = $atts['field'];
	
	// Special handling for user_id
	if ( $field === 'user_id' ) {
		return esc_html( afa_get_sf_user_id() );
	}

	// Return requested field if it exists
	if ( isset( $user_data[ $field ] ) ) {
		return esc_html( $user_data[ $field ] );
	}

	return $atts['default'];
}
add_shortcode( 'afa_user_info', 'afa_user_info_shortcode' );

/**
 * Shortcode to display logout button
 *
 * Usage: [afa_logout_button]
 *
 * @param array $atts Shortcode attributes
 * @return string Rendered logout button or empty if not authenticated
 */
function afa_logout_button_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'text' => __( 'Log Out', 'afa-salesforce' ),
		'redirect' => home_url(),
		'class' => 'afa-logout-button'
	), $atts, 'afa_logout_button' );

	if ( ! afa_is_sf_user_authenticated() ) {
		return '';
	}

	$logout_url = add_query_arg( 'afa_sf_logout', '1', $atts['redirect'] );
	$logout_url = wp_nonce_url( $logout_url, 'afa_sf_logout' );

	return sprintf(
		'<a href="%s" class="%s">%s</a>',
		esc_url( $logout_url ),
		esc_attr( $atts['class'] ),
		esc_html( $atts['text'] )
	);
}
add_shortcode( 'afa_logout_button', 'afa_logout_button_shortcode' );

/**
 * Handle logout action from URL parameter
 */
function afa_handle_logout_action() {
	if ( isset( $_GET['afa_sf_logout'] ) && isset( $_GET['_wpnonce'] ) ) {
		if ( wp_verify_nonce( $_GET['_wpnonce'], 'afa_sf_logout' ) ) {
			afa_sf_logout( remove_query_arg( array( 'afa_sf_logout', '_wpnonce' ) ) );
		}
	}
}
add_action( 'template_redirect', 'afa_handle_logout_action' );

/**
 * Add body class for authenticated users
 *
 * @param array $classes Current body classes
 * @return array Modified body classes
 */
function afa_add_sf_auth_body_class( $classes ) {
	if ( afa_is_sf_user_authenticated() ) {
		$classes[] = 'sf-authenticated';
	} else {
		$classes[] = 'sf-guest';
	}
	return $classes;
}
add_filter( 'body_class', 'afa_add_sf_auth_body_class' );
