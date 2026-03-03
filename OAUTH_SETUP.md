# OAuth 2.0 Authentication Setup Guide

## Overview

Version 1.1.0 of the AFA Salesforce Integration plugin introduces OAuth 2.0 (OpenID Connect) authentication for user login. This replaces the previous Apex-based authentication and allows WordPress to authenticate Salesforce Users without creating WordPress user accounts.

## What Changed

### Previous Behavior (v1.0.0)
- Used an Apex REST endpoint to verify username/password
- Only worked with Salesforce Person records
- Required password entry in the WordPress login form

### New Behavior (v1.1.0)
- Uses OAuth 2.0 Web Server Flow (Authorization Code Grant)
- Works with Salesforce Users (standard user accounts)
- Redirects to Salesforce for secure authentication
- Sets a cookie with the Salesforce User ID upon successful login
- Does NOT create WordPress users or WordPress sessions

## Architecture

The authentication flow works as follows:

1. User tries to register with an email that already exists in Salesforce
2. System shows a 409 error and displays "Log In with Salesforce" button
3. User clicks button → WordPress makes AJAX call to get OAuth authorization URL
4. User is redirected to Salesforce login page
5. User logs in with their Salesforce credentials
6. Salesforce redirects back to WordPress callback URL with authorization code
7. WordPress exchanges code for access token
8. WordPress retrieves user info from Salesforce
9. WordPress sets cookie with Salesforce User ID
10. User is redirected back to WordPress (authenticated)

## Setup Instructions

### 1. Create a Salesforce Connected App

You need to create a Connected App in Salesforce for OAuth authentication:

1. Log into your Salesforce org (sandbox or production)
2. Go to **Setup** → **Apps** → **App Manager**
3. Click **New Connected App**
4. Fill in the basic information:
   - **Connected App Name**: `WordPress OAuth Login`
   - **API Name**: `WordPress_OAuth_Login`
   - **Contact Email**: Your admin email
5. Enable OAuth Settings:
   - Check **Enable OAuth Settings**
   - **Callback URL**: `https://yourwordpresssite.com/salesforce-auth-callback/`
     - Replace with your actual WordPress site URL
     - Include the trailing slash
   - **Selected OAuth Scopes**: Add these scopes:
     - `openid` - Required for OpenID Connect
     - `id` - Access unique user ID
     - `email` - Access user email
     - `profile` - Access user profile
6. Click **Save**
7. Click **Continue**
8. On the Connected App detail page, click **Manage Consumer Details**
9. Verify your identity (enter verification code sent to email)
10. Copy the **Consumer Key** (Client ID)
11. Copy the **Consumer Secret** (Client Secret)

### 2. Configure the WordPress Plugin

1. Go to **WordPress Admin** → **Salesforce** → **Settings**
2. Scroll to the **OAuth Login Settings** section
3. Fill in the following fields:
   - **OAuth Client ID**: Paste the Consumer Key from Salesforce
   - **OAuth Client Secret**: Paste the Consumer Secret from Salesforce
   - **OAuth Redirect URI**: Should be `https://yoursite.com/salesforce-auth-callback/`
     - This must match EXACTLY what you entered in Salesforce
     - The default is auto-filled based on your site URL
4. Click **Save Settings**

### 3. Test the Authentication Flow

1. Create a test page with the shortcode: `[afa_salesforce_form]`
2. Try to register with an email that already exists in Salesforce
3. You should see the "Log In with Salesforce" button
4. Click it and verify you're redirected to Salesforce
5. Log in with Salesforce credentials
6. Verify you're redirected back and the cookie is set

## Cookie Details

After successful authentication, the plugin sets a cookie named `afa_sf_user_id`:

- **Name**: `afa_sf_user_id`
- **Value**: Salesforce User ID (18-character ID)
- **Expiration**: 30 days
- **Path**: `/` (site-wide)
- **HttpOnly**: Yes (not accessible via JavaScript)
- **Secure**: Yes (on HTTPS sites)
- **SameSite**: Lax

## Checking if a User is Authenticated

### In PHP

```php
// Include the OAuth class
$oauth = new AFA_Salesforce_OAuth();

// Check if authenticated
if ( $oauth->is_authenticated() ) {
    $user_id = $oauth->get_current_user_id();
    echo "User ID: " . $user_id;
} else {
    echo "Not authenticated";
}
```

### Accessing Member-Only Content

You can use the authentication state to gate content:

```php
$oauth = new AFA_Salesforce_OAuth();

if ( ! $oauth->is_authenticated() ) {
    // Show login prompt or redirect
    echo '<p>Please log in to view this content.</p>';
    echo do_shortcode('[afa_salesforce_form]');
} else {
    // Show member content
    echo '<h2>Welcome, Member!</h2>';
    echo '<p>This is exclusive member content.</p>';
}
```

## Logout Functionality

To add a logout button:

```php
if ( $oauth->is_authenticated() ) {
    $oauth->clear_auth_cookie();
    wp_redirect( home_url() );
    exit;
}
```

## Troubleshooting

### "OAuth is not properly configured" error
- Check that you've entered the Client ID and Client Secret in WordPress settings
- Verify the redirect URI matches exactly between Salesforce and WordPress

### "Invalid state parameter" error
- This is a CSRF protection error
- Usually means the user took too long (>10 minutes) between clicking login and completing it
- Ask them to try again

### "Failed to obtain access token" error
- Check that the Client Secret is correct
- Verify the redirect URI matches exactly
- Check that the Connected App is approved for use

### "User ID not found" error
- Check the Salesforce response in the error logs
- The user might not have the proper scopes granted

### Callback URL returns 404
- The callback is handled by `template_redirect` hook
- Ensure the plugin is active
- Check for conflicting redirect rules in `.htaccess` or other plugins

## Security Considerations

1. **HTTPS Required**: OAuth should only be used on HTTPS sites in production
2. **State Parameter**: Prevents CSRF attacks (automatically handled)
3. **Cookie Security**: HttpOnly prevents XSS, Secure flag requires HTTPS
4. **No Password Storage**: Passwords are never sent to or stored by WordPress
5. **Token Expiration**: OAuth state tokens expire after 10 minutes

## Differences from SAML

OAuth 2.0 was chosen over SAML 2.0 because:

- **Simpler Implementation**: OAuth is REST-based and easier to implement
- **Better Mobile Support**: OAuth works well on mobile devices
- **Native Salesforce Support**: Salesforce has excellent OAuth support
- **Lighter Weight**: Fewer dependencies, no XML parsing needed
- **Industry Standard**: OpenID Connect is widely adopted

## Backward Compatibility

The existing Apex-based user creation flow remains unchanged. Only the login flow has been modified to use OAuth.

- User registration still uses JWT + Apex REST API
- Contact creation still works the same way
- Only the login after a 409 error uses OAuth

## Additional Resources

- [Salesforce OAuth 2.0 Documentation](https://help.salesforce.com/s/articleView?id=sf.remoteaccess_oauth_web_server_flow.htm)
- [OpenID Connect Specification](https://openid.net/connect/)
- [WordPress HTTP API](https://developer.wordpress.org/plugins/http-api/)
