<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options
 */
function afa_salesforce_delete_options() {
	delete_option( 'afa_salesforce_is_sandbox' );
	delete_option( 'afa_salesforce_sandbox_url' );
	delete_option( 'afa_salesforce_instance_url' );
	delete_option( 'afa_salesforce_consumer_key' );
	delete_option( 'afa_salesforce_username' );
	delete_option( 'afa_salesforce_private_key' );
	delete_option( 'afa_salesforce_apex_endpoint_create' );
	delete_option( 'afa_salesforce_apex_endpoint_auth' );
}

/**
 * Delete all transients
 */
function afa_salesforce_delete_transients() {
	delete_transient( 'salesforce_access_token' );
}

/**
 * Clean up on multisite
 */
function afa_salesforce_multisite_cleanup() {
	global $wpdb;

	if ( is_multisite() ) {
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			afa_salesforce_delete_options();
			afa_salesforce_delete_transients();
			restore_current_blog();
		}
	} else {
		afa_salesforce_delete_options();
		afa_salesforce_delete_transients();
	}
}

// Execute cleanup
afa_salesforce_multisite_cleanup();
