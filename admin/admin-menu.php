<?php
/**
 * Admin Menu Configuration
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add admin menu pages
 */
function afa_salesforce_admin_menu() {

	add_options_page(
		'AFA Salesforce',
		'Salesforce',
		'manage_options',
		'afa-salesforce',
		'afa_salesforce_settings_page'
	);

	/*
	// Main settings page
	add_menu_page(
		__( 'Salesforce Integration', 'afa-salesforce' ),
		__( 'Salesforce', 'afa-salesforce' ),
		'manage_options',
		'afa-salesforce',
		'afa_salesforce_settings_page',
		'dashicons-cloud',
		80
	);

	// Settings submenu (same as main page)
	add_submenu_page(
		'afa-salesforce',
		__( 'Settings', 'afa-salesforce' ),
		__( 'Settings', 'afa-salesforce' ),
		'manage_options',
		'afa-salesforce',
		'afa_salesforce_settings_page'
	);

	// Test connection submenu
	add_submenu_page(
		'afa-salesforce',
		__( 'Test Connection', 'afa-salesforce' ),
		__( 'Test Connection', 'afa-salesforce' ),
		'manage_options',
		'afa-salesforce-test',
		'afa_salesforce_test_page'
	);
	*/
}
add_action( 'admin_menu', 'afa_salesforce_admin_menu' );

/**
 * Test connection page content
 */
function afa_salesforce_test_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized', 'afa-salesforce' ) );
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Salesforce Integration Test', 'afa-salesforce' ) . '</h1>';

	// Handle test connection button
	if ( isset( $_POST['test_connection'] ) && check_admin_referer( 'afa_salesforce_test' ) ) {
		try {
			$salesforce = new AFA_Salesforce_Integration();

			// Show configuration info
			echo '<h2>' . esc_html__( 'Configuration', 'afa-salesforce' ) . '</h2>';
			echo '<pre>' . esc_html( print_r( $salesforce->get_config_info(), true ) ) . '</pre>';

			// Test connection
			echo '<h2>' . esc_html__( 'Connection Test', 'afa-salesforce' ) . '</h2>';
			$result = $salesforce->test_connection();

			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Connection Failed:', 'afa-salesforce' ) . '</strong> ' . esc_html( $result->get_error_message() ) . '</p></div>';
				$error_data = $result->get_error_data();
				if ( ! empty( $error_data ) ) {
					echo '<pre>' . esc_html( print_r( $error_data, true ) ) . '</pre>';
				}
			} else {
				echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Connection Successful!', 'afa-salesforce' ) . '</strong></p></div>';
			}
		} catch ( Exception $e ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Error:', 'afa-salesforce' ) . '</strong> ' . esc_html( $e->getMessage() ) . '</p></div>';
		}
	}

	// Display test button
	echo '<form method="post">';
	wp_nonce_field( 'afa_salesforce_test' );
	echo '<p><button type="submit" name="test_connection" class="button button-primary">' . esc_html__( 'Test Connection', 'afa-salesforce' ) . '</button></p>';
	echo '</form>';

	// Display shortcode usage
	echo '<hr>';
	echo '<h2>' . esc_html__( 'Usage', 'afa-salesforce' ) . '</h2>';
	echo '<p>' . esc_html__( 'Add this shortcode to any page or post to display the Salesforce form:', 'afa-salesforce' ) . '</p>';
	echo '<code>[afa_salesforce_form]</code>';

	echo '</div>';
}
