# AFA Salesforce Integration

A WordPress plugin that seamlessly integrates WordPress forms with Salesforce using JWT-based OAuth2 authentication and AJAX form submission.

## Features

- 🔐 **Secure JWT Bearer Flow Authentication** - Server-to-server communication without user credentials
- 🔑 **OAuth 2.0 User Login** - Authenticate Salesforce Users via OAuth without creating WordPress accounts
- ⚡ **AJAX Form Submission** - No page reloads, smooth user experience
- 🍪 **Cookie-Based Sessions** - Store authenticated user ID in secure cookies
- ✅ **Real-time Validation** - Email and zip code validation as users type
- 🔄 **Token Caching** - Improved performance with 4-minute token cache
- 🌍 **Multi-environment Support** - Works with both Sandbox and Production
- 🎨 **Customizable** - Hooks and filters for developers
- 🛡️ **Content Gating** - Member-only content with shortcodes and helper functions
- 🌐 **Translation Ready** - i18n support for multiple languages
- 📱 **Mobile Responsive** - Works perfectly on all devices
- 🛡️ **Secure** - CSRF protection with WordPress nonces
- 📊 **Comprehensive Logging** - Detailed error logs for troubleshooting

## What's New in v1.1.0

- ✨ **OAuth 2.0 Login**: Users can now authenticate using their Salesforce credentials
- 🚫 **No WordPress Users**: Authentication doesn't create WordPress user accounts
- 🍪 **Secure Cookies**: Salesforce User ID stored in HttpOnly cookies
- 📦 **Helper Functions**: New PHP functions for authentication checks
- 🎯 **Shortcodes**: `[afa_members_only]`, `[afa_user_info]`, `[afa_logout_button]`
- 📚 **Documentation**: Comprehensive setup guides and quick reference

See [OAUTH_SETUP.md](OAUTH_SETUP.md) for OAuth configuration details.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenSSL PHP extension
- cURL PHP extension
- Salesforce account with API access
- HTTPS (recommended)

## Installation

### Quick Install

1. Download the latest release
2. Upload to `/wp-content/plugins/` directory
3. Activate through the WordPress Plugins menu
4. Go to **Salesforce > Settings** to configure

### Manual Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed setup instructions including Salesforce configuration.

## Quick Start

### 1. Configure Salesforce

- Create External Client App
- Generate RSA certificate and key
- Create integration user
- Deploy APEX REST class

### 2. Configure Plugin

Navigate to **Salesforce > Settings** and enter:

- Environment (Sandbox/Production)
- Salesforce Instance URL
- Consumer Key
- Integration Username
- Private Key
- APEX Endpoint

### 3. Add Form to Page

Use the shortcode on any page or post:

```
[afa_salesforce_form]
```

### 4. Test

Go to **Salesforce > Test Connection** to verify setup.

## Usage

### Basic Implementation

Simply add the shortcode to any page:

```
[afa_salesforce_form]
```

### Advanced - Programmatic Usage

```php
// Create Salesforce integration instance
$salesforce = new AFA_Salesforce_Integration();

// Prepare user data
$user_data = array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'zip_code' => '12345',
    'affiliation' => 'Active Duty',
    'branch' => 'United States Air Force'
);

// Create user in Salesforce
$result = $salesforce->create_user( $user_data );

if ( is_wp_error( $result ) ) {
    // Handle error
    echo $result->get_error_message();
} else {
    // Success
    echo 'Contact ID: ' . $result['data']['contactId'];
}
```

### Hooks and Filters

**Filter user data before sending to Salesforce:**

```php
add_filter( 'afa_salesforce_user_data', function( $user_data, $post_data ) {
    // Add custom field
    $user_data['custom_field'] = 'custom_value';
    return $user_data;
}, 10, 2 );
```

**Action after successful user creation:**

```php
add_action( 'afa_salesforce_user_created', function( $contact_id, $user_data, $result ) {
    // Send email notification
    wp_mail( 'admin@example.com', 'New Salesforce Contact', 'ID: ' . $contact_id );
}, 10, 3 );
```

**Control script enqueueing:**

```php
add_filter( 'afa_salesforce_enqueue_scripts', function( $should_enqueue ) {
    // Force enqueue on specific page
    if ( is_page( 'custom-page' ) ) {
        return true;
    }
    return $should_enqueue;
} );
```

## Configuration

### Settings Overview

| Setting | Description | Example |
|---------|-------------|---------|
| Environment | Sandbox or Production | Sandbox |
| Sandbox URL | Your sandbox instance | https://org--sandbox.my.salesforce.com |
| Instance URL | Production instance | https://org.my.salesforce.com |
| Consumer Key | From External Client App | 3MVG9... |
| Username | Integration user | wp-integration@org.com.fullsb |
| Private Key | RSA private key | -----BEGIN RSA PRIVATE KEY----- |
| APEX Endpoint | REST endpoint path | /services/apexrest/AFACreateUser |
| Redirect URL | Success page | https://site.com/thank-you/ |

### Salesforce APEX Class

The plugin expects an APEX REST class with this signature:

```apex
@RestResource(urlMapping='/AFACreateUser/*')
global with sharing class AFACreateUserService {
    @HttpPost
    global static ResponseWrapper createUser() {
        // Implementation
    }
}
```

Expected request format:
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "zip_code": "12345",
    "affiliation": "Active Duty",
    "branch": "United States Air Force"
}
```

Expected response format:
```json
{
    "success": true,
    "message": "Contact created successfully",
    "contactId": "003XXXXXXXXXXXXXXX"
}
```

## Multi-Site Deployment

The plugin is designed for easy deployment to multiple WordPress sites:

### Option 1: Manual Configuration

1. Install plugin on each site
2. Configure settings via WordPress admin
3. Test each installation

### Option 2: Configuration Export/Import

1. Configure first site completely
2. Document all settings
3. Apply same configuration to other sites

### Option 3: Automated (WP-CLI)

```bash
# Export from configured site
wp option get afa_salesforce_consumer_key > config.txt

# Import to new site
wp option update afa_salesforce_consumer_key "$(cat config.txt)"
```

See [INSTALLATION.md](INSTALLATION.md) for detailed multi-site deployment instructions.

## Troubleshooting

### Common Issues

**Connection Failed**
- Verify Consumer Key is correct
- Check private key format
- Ensure integration user has API access
- Verify user has app permission

**Form Not Submitting**
- Check browser console for JavaScript errors
- Verify AJAX URL is correct
- Ensure nonce is valid

**Duplicate Email Not Detected**
- Verify APEX class returns 409 status
- Check Salesforce field uniqueness settings

### Debug Mode

Enable WordPress debugging:

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check logs at: `wp-content/debug.log`

### Test Connection

Go to **Salesforce > Test Connection** to diagnose issues.

## Security

- All form submissions use nonce verification
- Input is sanitized and validated
- Private key stored in WordPress database
- HTTPS strongly recommended
- Token caching reduces API calls
- Comprehensive error logging

### Best Practices

1. Use HTTPS for all sites
2. Keep WordPress and PHP updated
3. Limit API user permissions in Salesforce
4. Regular security audits
5. Monitor error logs
6. Backup configuration regularly

## Performance

- **Token Caching**: Access tokens cached for 4 minutes
- **Conditional Loading**: Scripts only load on pages with form
- **AJAX Submission**: No page reloads
- **Optimized Queries**: Minimal database impact

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Changelog

### Version 1.0.0 (Initial Release)

- JWT Bearer Flow authentication
- AJAX form submission
- Real-time validation
- Token caching
- Sandbox and Production support
- Translation ready
- Comprehensive error handling
- Admin settings page
- Test connection tool

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- **Documentation**: See [INSTALLATION.md](INSTALLATION.md)
- **Issues**: Report issues on GitHub
- **Email**: support@afa.org

## Credits

Developed by Air Force Association

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Links

- [WordPress Plugin Directory](https://wordpress.org/plugins/afa-salesforce-integration/)
- [Salesforce JWT Documentation](https://help.salesforce.com/articleView?id=remoteaccess_oauth_jwt_flow.htm)
- [APEX REST API Guide](https://developer.salesforce.com/docs/atlas.en-us.apexcode.meta/apexcode/apex_rest.htm)
