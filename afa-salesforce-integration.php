<?php
/**
 * Plugin Name: AFA Salesforce Integration
 * Plugin URI: https://www.afa.org
 * Description: Integrates WordPress forms with Salesforce APEX REST API using JWT authentication. Provides AJAX form submission with real-time validation and OAuth 2.0 user login with PKCE.
 * Version: 1.1.2
 * Author: Air Force Association
 * Author URI: https://www.afa.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: afa-salesforce
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'AFA_SALESFORCE_VERSION', '1.1.2' );

/**
 * Plugin directory path
 */
define( 'AFA_SALESFORCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 */
define( 'AFA_SALESFORCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for translations
 */
function afa_salesforce_load_textdomain() {
	load_plugin_textdomain( 'afa-salesforce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'afa_salesforce_load_textdomain' );

/**
 * Activation hook
 */
function afa_salesforce_activate() {
	// Check minimum requirements
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'AFA Salesforce Integration requires PHP 7.4 or higher.', 'afa-salesforce' ) );
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'afa_salesforce_activate' );

/**
 * Deactivation hook
 */
function afa_salesforce_deactivate() {
	// Clear cached tokens
	delete_transient( 'salesforce_access_token' );

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'afa_salesforce_deactivate' );

/**
 * Include required files
 */
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/class-salesforce-integration.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/class-salesforce-oauth.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/form-shortcode.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/enqueue-scripts.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'includes/helper-functions.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'admin/admin-menu.php';
require_once AFA_SALESFORCE_PLUGIN_DIR . 'admin/settings-page.php';


/**
 * Add popup to footer
 */
function afa_salesforce_popup() {

	echo "<div id='mm-popup' class='mm-popup' style='display:none;'>";
	echo "<button type='button' class='mm-popup-close' aria-label='Close'> × </button>";
	echo do_shortcode( '[afa_salesforce_form]' );
	echo "</div>";

}
add_action( 'wp_footer', 'afa_salesforce_popup' );

/**
 * Initialize the cookies
 */
function afa_salesforce_cookies() {

	if ( ! headers_sent() ) {

		$expires = time() + 3600;

		$trigger = $_COOKIE['afa_mm_trigger'];
		$status = $_COOKIE['afa_mm_status'];
		error_log( "trigger is " . $trigger );
		error_log( "status is " . $status );
		error_log( print_r( $trigger, 1 ) );
		error_log( "wordpress_test_cookie is " . $_COOKIE['wordpress_test_cookie'] );

		if ( ! isset( $_COOKIE['afa_mm_trigger'] ) ) {
			error_log( 'creating afa_mm_trigger' );
			setcookie( 'afa_mm_trigger', 0, $expires, '/' );
		}

		if ( ! isset( $_COOKIE['afa_mm_status'] ) ) {
			error_log( 'creating afa_mm_status' );
			setcookie( 'afa_mm_status', 'none', $expires, '/' );
		}

	}

}
add_action( 'init', 'afa_salesforce_cookies' );


/**
 * Initialize the plugin
 */
function afa_salesforce_init() {
	// Plugin initialization code here if needed
}
add_action( 'init', 'afa_salesforce_init' );
