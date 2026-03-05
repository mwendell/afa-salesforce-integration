<?php
/**
 * Admin Settings Page
 *
 * @package AFA_Salesforce
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register settings
 */
function afa_salesforce_register_settings() {
	// Register settings
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_is_sandbox' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_sandbox_url' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_instance_url' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_consumer_key' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_username' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_private_key' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_apex_endpoint_create' );
	// register_setting( 'afa_salesforce_settings', 'afa_salesforce_apex_endpoint_auth' );

	// OAuth settings
	// register_setting( 'afa_salesforce_settings', 'afa_salesforce_oauth_client_id' );
	// register_setting( 'afa_salesforce_settings', 'afa_salesforce_oauth_client_secret' );
	// register_setting( 'afa_salesforce_settings', 'afa_salesforce_oauth_redirect_uri' );

	register_setting( 'afa_salesforce_settings', 'afa_salesforce_join_form_html' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_success_html' );
	register_setting( 'afa_salesforce_settings', 'afa_salesforce_existing_user_html' );

	// Add settings sections
	add_settings_section(
		'afa_salesforce_environment_section',
		__( 'Environment Settings', 'afa-salesforce' ),
		'afa_salesforce_environment_section_callback',
		'afa-salesforce'
	);

	add_settings_section(
		'afa_salesforce_credentials_section',
		__( 'Salesforce Credentials', 'afa-salesforce' ),
		'afa_salesforce_credentials_section_callback',
		'afa-salesforce'
	);

	add_settings_section(
		'afa_salesforce_advanced_section',
		__( 'Advanced Settings', 'afa-salesforce' ),
		'afa_salesforce_advanced_section_callback',
		'afa-salesforce'
	);

	add_settings_section(
		'afa_salesforce_html_section',
		__( 'Message HTML Settings', 'afa-salesforce' ),
		'afa_salesforce_html_section_callback',
		'afa-salesforce'
	);

	/*
	add_settings_section(
		'afa_salesforce_oauth_section',
		__( 'OAuth Login Settings', 'afa-salesforce' ),
		'afa_salesforce_oauth_section_callback',
		'afa-salesforce'
	);
	*/

	// Add settings fields - Environment
	add_settings_field(
		'afa_salesforce_is_sandbox',
		__( 'Environment (Ignore)', 'afa-salesforce' ),
		'afa_salesforce_is_sandbox_callback',
		'afa-salesforce',
		'afa_salesforce_environment_section'
	);

	add_settings_field(
		'afa_salesforce_sandbox_url',
		__( 'Sandbox URL', 'afa-salesforce' ),
		'afa_salesforce_sandbox_url_callback',
		'afa-salesforce',
		'afa_salesforce_environment_section'
	);

	add_settings_field(
		'afa_salesforce_instance_url',
		__( 'Production Instance URL', 'afa-salesforce' ),
		'afa_salesforce_instance_url_callback',
		'afa-salesforce',
		'afa_salesforce_environment_section'
	);

	// Add settings fields - Credentials
	add_settings_field(
		'afa_salesforce_consumer_key',
		__( 'Consumer Key (Client ID)', 'afa-salesforce' ),
		'afa_salesforce_consumer_key_callback',
		'afa-salesforce',
		'afa_salesforce_credentials_section'
	);

	add_settings_field(
		'afa_salesforce_username',
		__( 'Integration Username', 'afa-salesforce' ),
		'afa_salesforce_username_callback',
		'afa-salesforce',
		'afa_salesforce_credentials_section'
	);

	add_settings_field(
		'afa_salesforce_private_key',
		__( 'Private Key', 'afa-salesforce' ),
		'afa_salesforce_private_key_callback',
		'afa-salesforce',
		'afa_salesforce_credentials_section'
	);

	add_settings_field(
		'afa_salesforce_apex_endpoint_create',
		__( 'New User Endpoint', 'afa-salesforce' ),
		'afa_salesforce_apex_endpoint_create_callback',
		'afa-salesforce',
		'afa_salesforce_advanced_section'
	);

	add_settings_field(
		'afa_salesforce_join_form_html',
		'Join Form HTML',
		'afa_salesforce_join_form_html_callback',
		'afa-salesforce',
		'afa_salesforce_html_section'
	);

	add_settings_field(
		'afa_salesforce_success_html',
		'Successfully Joined HTML',
		'afa_salesforce_success_html_callback',
		'afa-salesforce',
		'afa_salesforce_html_section'
	);

	add_settings_field(
		'afa_salesforce_existing_user_html',
		'Existing User HTML',
		'afa_salesforce_existing_user_html',
		'afa-salesforce',
		'afa_salesforce_html_section'
	);

	/*
	add_settings_field(
		'afa_salesforce_apex_endpoint_auth',
		__( 'User Auth Endpoint', 'afa-salesforce' ),
		'afa_salesforce_apex_endpoint_auth_callback',
		'afa-salesforce',
		'afa_salesforce_advanced_section'
	);

	// OAuth fields
	add_settings_field(
		'afa_salesforce_oauth_client_id',
		__( 'OAuth Client ID', 'afa-salesforce' ),
		'afa_salesforce_oauth_client_id_callback',
		'afa-salesforce',
		'afa_salesforce_oauth_section'
	);

	add_settings_field(
		'afa_salesforce_oauth_client_secret',
		__( 'OAuth Client Secret', 'afa-salesforce' ),
		'afa_salesforce_oauth_client_secret_callback',
		'afa-salesforce',
		'afa_salesforce_oauth_section'
	);

	add_settings_field(
		'afa_salesforce_oauth_redirect_uri',
		__( 'OAuth Redirect URI', 'afa-salesforce' ),
		'afa_salesforce_oauth_redirect_uri_callback',
		'afa-salesforce',
		'afa_salesforce_oauth_section'
	);
	*/
}
add_action( 'admin_init', 'afa_salesforce_register_settings' );

/**
 * Section callbacks
 */
function afa_salesforce_environment_section_callback() {
	echo '<p>' . esc_html__( 'Configure your Salesforce environment settings.', 'afa-salesforce' ) . '</p>';
}

function afa_salesforce_credentials_section_callback() {
	echo '<p>' . esc_html__( 'Enter your Salesforce API credentials from your External Client App.', 'afa-salesforce' ) . '</p>';
}

function afa_salesforce_advanced_section_callback() {
	echo '<p>' . esc_html__( 'Advanced configuration options.', 'afa-salesforce' ) . '</p>';
}

function afa_salesforce_html_section_callback() {
	echo '<p>' . esc_html__( 'Insert valid HTML for user messages.', 'afa-salesforce' ) . '</p>';
}

/*
function afa_salesforce_oauth_section_callback() {
	echo '<p>' . esc_html__( 'Configure OAuth 2.0 settings for user authentication. This is required for the "Log In with Salesforce" functionality.', 'afa-salesforce' ) . '</p>';
}
*/

/**
 * Field callbacks
 */
function afa_salesforce_is_sandbox_callback() {
	$value = get_option( 'afa_salesforce_is_sandbox', true );
	?>
	<select name="afa_salesforce_is_sandbox" id="afa_salesforce_is_sandbox">
		<option value="1" <?php selected( $value, true ); ?>><?php _e( 'Sandbox', 'afa-salesforce' ); ?></option>
		<option value="0" <?php selected( $value, false ); ?>><?php _e( 'Production', 'afa-salesforce' ); ?></option>
	</select>
	<p class="description"><?php _e( 'This is not effective, place appropriate URLs in both slots below to configure.', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_sandbox_url_callback() {
	$value = get_option( 'afa_salesforce_sandbox_url', 'https://airandspaceforcesassociation--fullsb.sandbox.my.salesforce.com' );
	?>
	<input type="text" name="afa_salesforce_sandbox_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php _e( 'Your Salesforce sandbox URL (e.g., https://airandspaceforcesassociation--fullsb.sandbox.my.salesforce.com)', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_instance_url_callback() {
	$value = get_option( 'afa_salesforce_instance_url', 'https://airandspaceforcesassociation.my.salesforce.com' );
	?>
	<input type="text" name="afa_salesforce_instance_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php _e( 'Your Salesforce production instance URL (e.g., https://airandspaceforcesassociation.my.salesforce.com)', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_consumer_key_callback() {
	$value = get_option( 'afa_salesforce_consumer_key', '' );
	?>
	<input type="text" name="afa_salesforce_consumer_key" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
	<p class="description"><?php _e( 'Consumer Key (Client ID) from your Salesforce External Client App.', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_username_callback() {
	$value = get_option( 'afa_salesforce_username', '' );
	?>
	<input type="text" name="afa_salesforce_username" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php _e( 'The integration user username (e.g., wp-integration@afa.org).', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_private_key_callback() {
	$value = get_option( 'afa_salesforce_private_key', '' );
	?>
	<textarea name="afa_salesforce_private_key" rows="10" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( 'Paste your RSA private key here (including -----BEGIN RSA PRIVATE KEY----- and -----END RSA PRIVATE KEY----- lines).', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_apex_endpoint_create_callback() {
	$value = get_option( 'afa_salesforce_apex_endpoint_create', '/services/apexrest/AFACreateUserContact' );
	?>
	<input type="text" name="afa_salesforce_apex_endpoint_create" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php _e( 'The APEX REST endpoint path for user creation (e.g., /services/apexrest/AFACreateUserContact).', 'afa-salesforce' ); ?></p>
	<?php
}

/*
function afa_salesforce_apex_endpoint_auth_callback() {
	$value = get_option( 'afa_salesforce_apex_endpoint_auth', '/services/apexrest/AFAUserAuth' );
	?>
	<input type="text" name="afa_salesforce_apex_endpoint_auth" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description"><?php _e( 'The APEX REST endpoint path for user authorization (e.g., /services/apexrest/AFAUserAuth).', 'afa-salesforce' ); ?></p>
	<?php
}
*/

function afa_salesforce_oauth_client_id_callback() {
	$value = get_option( 'afa_salesforce_oauth_client_id', '' );
	?>
	<input type="text" name="afa_salesforce_oauth_client_id" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
	<p class="description"><?php _e( 'OAuth Client ID from your Salesforce Connected App (for user login).', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_oauth_client_secret_callback() {
	$value = get_option( 'afa_salesforce_oauth_client_secret', '' );
	?>
	<input type="password" name="afa_salesforce_oauth_client_secret" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
	<p class="description"><?php _e( 'OAuth Client Secret from your Salesforce Connected App.', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_oauth_redirect_uri_callback() {
	$default = home_url( '/salesforce-auth-callback/' );
	$value = get_option( 'afa_salesforce_oauth_redirect_uri', $default );
	?>
	<input type="text" name="afa_salesforce_oauth_redirect_uri" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
	<p class="description">
		<?php
		printf(
			__( 'OAuth Callback URL (add this to your Salesforce Connected App). Default: %s', 'afa-salesforce' ),
			'<code>' . esc_html( $default ) . '</code>'
		);
		?>
	</p>
	<?php
}


function afa_salesforce_join_form_html_callback() {
	$value = get_option( 'afa_salesforce_join_form_html', "<h5>Enjoying This?</h5>/n<p><b>Become an AFA member for free</b> to unlock exclusive content, receive breaking Air Force & Space Force news, and join the community that's moving our mission forward.</p>/n<p>There's no cost to join. Just fill out the form below, and you're in.</p>" );
	?>
	<textarea name="afa_salesforce_join_form_html" rows="6" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( 'this is displayed at the top of the Mission Member Join form.', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_join_form_html_callback() {
	$value = get_option( 'afa_salesforce_success_html', "<h2>Thank You!</h2>/n<p><b>Check your inbox to verify your email address and finish setting up your AFA account.</b></p>" );
	?>
	<textarea name="afa_salesforce_success_html" rows="6" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( 'This is displayed when the Mission member form has successfully added a new user.', 'afa-salesforce' ); ?></p>
	<?php
}

function afa_salesforce_join_form_html_callback() {
	$value = get_option( 'afa_salesforce_existing_user_html', "<h2>Welcome Back!</h2>/n<p><b>An account with this email already exists. Please log in to continue.</b></p>" );
	?>
	<textarea name="afa_salesforce_existing_user_html" rows="6" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php _e( 'Displayed when a visitor fills out the form with an email address that is already in Salesforce.', 'afa-salesforce' ); ?></p>
	<?php
}


/**
 * Settings page content
 */
function afa_salesforce_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized', 'afa-salesforce' ) );
	}

	// Handle clear cache button
	if ( isset( $_POST['clear_cache'] ) && check_admin_referer( 'afa_salesforce_clear_cache' ) ) {
		delete_transient( 'salesforce_access_token' );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Token cache cleared successfully.', 'afa-salesforce' ) . '</p></div>';
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'afa_salesforce_settings' );
			do_settings_sections( 'afa-salesforce' );
			submit_button( __( 'Save Settings', 'afa-salesforce' ) );
			?>
		</form>

		<hr>

		<h2><?php _e( 'Utilities', 'afa-salesforce' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'afa_salesforce_clear_cache' ); ?>
			<p>
				<button type="submit" name="clear_cache" class="button"><?php _e( 'Clear Token Cache', 'afa-salesforce' ); ?></button><br/>
				<span class="description"><?php _e( 'Clear the cached Salesforce access token to force a new authentication request.', 'afa-salesforce' ); ?></span>
			</p>
		</form>
		<!--
		<hr>
		<h2><?php _e( 'Form Shortcode', 'afa-salesforce' ); ?></h2>
		-->

		<p><?php _e( 'Use this shortcode to display the Salesforce form on any page or post:', 'afa-salesforce' ); ?></p>
		<p><code>[afa_salesforce_form]</code></p>

		<!--
		<hr>

		<h2><?php _e( 'Documentation', 'afa-salesforce' ); ?></h2>
		<p><?php _e( 'For setup instructions and troubleshooting, please refer to the plugin documentation.', 'afa-salesforce' ); ?></p>
		-->

		<ul>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=afa-salesforce-test' ) ); ?>"><?php _e( 'Test Salesforce Connection', 'afa-salesforce' ); ?></a></li>
		</ul>
	</div>
	<?php
}
