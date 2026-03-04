=== AFA Salesforce Integration ===
Contributors: airandspaceforcesassociation
Tags: salesforce, crm, integration, forms, jwt
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates WordPress forms with Salesforce APEX REST API using JWT authentication with AJAX form submission.

== Description ==

AFA Salesforce Integration seamlessly connects your WordPress site with Salesforce using JWT-based OAuth2 authentication. The plugin provides an easy-to-use form that submits user data to your Salesforce instance via the APEX REST API.

**Key Features:**

* JWT Bearer Flow authentication for secure, server-to-server communication
* AJAX form submission with real-time validation
* Support for both Salesforce Sandbox and Production environments
* Token caching for improved performance
* Customizable redirect URL after successful submission
* Real-time email and zip code validation
* Comprehensive error handling and logging
* Translation-ready (i18n support)

**Perfect for:**

* Lead generation forms
* Contact registration
* User signup workflows
* Any WordPress-to-Salesforce integration needs

**Technical Highlights:**

* Uses WordPress best practices and coding standards
* Secure nonce verification for CSRF protection
* Sanitized input and validated data
* Cached access tokens (4-minute TTL)
* Detailed error logging for troubleshooting
* Mobile-responsive form design

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "AFA Salesforce Integration"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Extract the zip file
3. Upload the `afa-salesforce-integration` folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress

= Configuration =

1. Go to Salesforce > Settings in your WordPress admin
2. Configure your Salesforce environment (Sandbox or Production)
3. Enter your Salesforce Instance URL
4. Add your Consumer Key (Client ID) from Salesforce External Client App
5. Enter your integration user username
6. Paste your RSA private key
7. Set the APEX REST endpoint path
8. Configure the success redirect URL
9. Save settings

= Salesforce Setup =

Before using the plugin, you need to set up Salesforce:

1. Create an External Client App in Salesforce
2. Generate RSA certificate and key pair
3. Upload certificate to Salesforce External Client App
4. Create integration user with API access
5. Deploy APEX REST class
6. Grant permissions to integration user

For detailed setup instructions, see the included documentation.

== Usage ==

After configuring the plugin, use the shortcode to display the form:

`[afa_salesforce_form]`

You can add this shortcode to any page, post, or widget area.

= Testing =

1. Go to Salesforce > Test Connection
2. Click "Test Connection" button
3. Verify the connection is successful

== Frequently Asked Questions ==

= What is JWT Bearer Flow? =

JWT Bearer Flow is an OAuth 2.0 authentication method that allows server-to-server communication without user interaction. It's more secure than username/password authentication.

= Do I need a Salesforce developer account? =

You need a Salesforce account with API access and the ability to create External Client Apps and APEX REST classes. This is available in most Salesforce editions.

= Can I customize the form fields? =

Yes, you can modify the form fields by editing the plugin files. Hooks and filters are available for developers to customize the form and data processing.

= Is the form mobile-responsive? =

Yes, the form is fully responsive and works on all devices.

= Where are error logs stored? =

Errors are logged to the WordPress debug log. Enable WP_DEBUG and WP_DEBUG_LOG in wp-config.php to view logs.

= Can I use this with multiple Salesforce instances? =

The current version supports one Salesforce instance per WordPress site. Contact support for multi-instance requirements.

= Is my private key stored securely? =

The private key is stored in the WordPress database. For maximum security, ensure your database is properly secured and consider using environment variables.

== Screenshots ==

1. Plugin settings page with configuration options
2. Test connection page showing successful connection
3. Form display on frontend with validation
4. Success message after form submission
5. Admin menu in WordPress dashboard

== Changelog ==

= 1.0.0 =
* Initial release
* JWT Bearer Flow authentication
* AJAX form submission
* Real-time validation
* Token caching
* Sandbox and Production support
* Comprehensive error handling
* Translation-ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of AFA Salesforce Integration plugin.

== Developer Notes ==

= Hooks and Filters =

**Filters:**

* `afa_salesforce_user_data` - Filter user data before sending to Salesforce
* `afa_salesforce_enqueue_scripts` - Control script enqueueing

**Actions:**

* `afa_salesforce_user_created` - Fires after successful user creation in Salesforce

= Custom Implementation =

Developers can use the integration class directly:

`
$salesforce = new AFA_Salesforce_Integration();
$result = $salesforce->create_user( array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'zip_code' => '12345'
) );
`

== Privacy Policy ==

This plugin sends user-submitted data to Salesforce. Ensure your privacy policy discloses:

* What data is collected
* That data is sent to Salesforce
* How Salesforce uses the data
* User rights regarding their data

The plugin does not store personal data in WordPress beyond what is necessary for form processing.

== Support ==

For support, please visit:

* Documentation: Included in plugin files
* Email: support@afa.org
* GitHub: [Repository URL]

== Credits ==

Developed by Air Force Association

== License ==

This plugin is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
