<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Enqueue scripts and styles for Salesforce form
 */
function afa_salesforce_enqueue_scripts() {
	global $post;

	/*
	// Check if the shortcode is present on the page
	$has_shortcode = false;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afa_salesforce_form' ) ) {
		$has_shortcode = true;
	}

	// Allow filtering to force enqueue on specific pages
	$has_shortcode = apply_filters( 'afa_salesforce_enqueue_scripts', $has_shortcode );

	// Only enqueue if shortcode is present
	if ( ! $has_shortcode ) {
		return;
	}
	*/

	// Enqueue jQuery (WordPress includes it by default)
	wp_enqueue_script( 'jquery' );

	// Enqueue custom CSS
	wp_enqueue_style(
		'afa-salesforce-form-styles',
		AFA_SALESFORCE_PLUGIN_URL . 'assets/css/salesforce-form-styles.css',
		array(),
		AFA_SALESFORCE_VERSION
	);

	// Enqueue custom JavaScript
	wp_enqueue_script(
		'afa-salesforce-integration',
		AFA_SALESFORCE_PLUGIN_URL . 'assets/js/salesforce-integration.js',
		array( 'jquery' ),
		AFA_SALESFORCE_VERSION,
		true
	);

	// Pass AJAX URL and nonce to JavaScript
	wp_localize_script( 'afa-salesforce-integration', 'afaSalesforceAjax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'afa_salesforce_ajax_nonce' )
	) );
}
add_action( 'wp_enqueue_scripts', 'afa_salesforce_enqueue_scripts' );
