# Multi-Site Deployment Guide

This guide will help you quickly deploy the AFA Salesforce Integration plugin to multiple WordPress sites.

## Prerequisites

- Plugin files ready for deployment
- Salesforce External Client App configured
- RSA certificate and private key generated
- All Salesforce URLs and credentials documented

## Deployment Methods

### Method 1: ZIP Upload (Recommended for Most Users)

**Step 1: Create Plugin ZIP**

```bash
cd /path/to/afa-salesforce-integration
zip -r afa-salesforce-integration.zip . -x "*.git*" "*.DS_Store"
```

**Step 2: Deploy to Each Site**

For each WordPress site:

1. Log in to WordPress admin
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Choose `afa-salesforce-integration.zip`
5. Click **Install Now**
6. Click **Activate Plugin**
7. Go to **Salesforce > Settings**
8. Enter configuration (see Configuration Template below)
9. Save settings
10. Go to **Salesforce > Test Connection** to verify

**Time estimate:** 5-10 minutes per site

### Method 2: FTP/SFTP Deployment

**Step 1: Upload Plugin Files**

For each site:

```bash
# Upload via FTP/SFTP
cd /local/path/afa-salesforce-integration
# Upload entire folder to: /wp-content/plugins/afa-salesforce-integration
```

**Step 2: Activate and Configure**

1. Log in to WordPress admin
2. Go to **Plugins**
3. Find "AFA Salesforce Integration"
4. Click **Activate**
5. Configure as in Method 1

**Time estimate:** 5-10 minutes per site

### Method 3: WP-CLI (Advanced Users)

**Step 1: Install Plugin**

```bash
# On each site
wp plugin install /path/to/afa-salesforce-integration.zip --activate
```

**Step 2: Configure via WP-CLI**

```bash
# Set all configuration options
wp option update afa_salesforce_is_sandbox 1
wp option update afa_salesforce_sandbox_url "https://airforceassociation--fullsb.sandbox.my.salesforce.com"
wp option update afa_salesforce_consumer_key "YOUR_CONSUMER_KEY"
wp option update afa_salesforce_username "wp-integration@afa.org.fullsb"
wp option update afa_salesforce_private_key "$(cat /path/to/salesforce.key)"
wp option update afa_salesforce_apex_endpoint_create "/services/apexrest/AFACreateUser"
wp option update afa_salesforce_apex_endpoint_auth "/services/apexrest/AFAUserAuth"
```

**Step 3: Test Connection**

```bash
# Test by viewing the test page
wp eval 'echo admin_url("admin.php?page=afa-salesforce-test");'
# Visit that URL in browser
```

**Time estimate:** 2-3 minutes per site

## Configuration Template

Create a secure document with these values to use across all sites:

### Sandbox Configuration

```
Environment: Sandbox
Sandbox URL: https://airforceassociation--fullsb.sandbox.my.salesforce.com
Consumer Key: 3MVG9XYZ123...
Integration Username: wp-integration@afa.org.fullsb
APEX Endpoint: /services/apexrest/AFACreateUser
```

### Private Key

```
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA...
(store securely - do not commit to version control)
...
-----END RSA PRIVATE KEY-----
```

### Per-Site Configuration

```
Site 1:
- Redirect URL: https://site1.com/thank-you/

Site 2:
- Redirect URL: https://site2.com/thank-you/

Site 3:
- Redirect URL: https://site3.com/thank-you/
```

## Automated Deployment Script

For deploying to many sites, use this bash script:

### deploy-salesforce-plugin.sh

```bash
#!/bin/bash

# Configuration
PLUGIN_ZIP="/path/to/afa-salesforce-integration.zip"
SITES=(
    "ssh://user@site1.com/path/to/wordpress"
    "ssh://user@site2.com/path/to/wordpress"
    "ssh://user@site3.com/path/to/wordpress"
)

# Salesforce Configuration
CONSUMER_KEY="your_consumer_key_here"
USERNAME="wp-integration@afa.org.fullsb"
PRIVATE_KEY_FILE="/path/to/salesforce.key"

for site in "${SITES[@]}"; do
    echo "Deploying to: $site"

    # Upload plugin
    wp plugin install "$PLUGIN_ZIP" --activate --ssh="$site"

    # Configure
    wp option update afa_salesforce_is_sandbox 1 --ssh="$site"
    wp option update afa_salesforce_sandbox_url "https://airforceassociation--fullsb.sandbox.my.salesforce.com" --ssh="$site"
    wp option update afa_salesforce_consumer_key "$CONSUMER_KEY" --ssh="$site"
    wp option update afa_salesforce_username "$USERNAME" --ssh="$site"
    wp option update afa_salesforce_private_key "$(cat $PRIVATE_KEY_FILE)" --ssh="$site"
    wp option update afa_salesforce_apex_endpoint_create "/services/apexrest/AFACreateUser" --ssh="$site"

    echo "Deployed to: $site"
    echo "---"
done

echo "Deployment complete!"
```

**Usage:**

```bash
chmod +x deploy-salesforce-plugin.sh
./deploy-salesforce-plugin.sh
```

## Verification Checklist

After deploying to each site, verify:

- [ ] Plugin appears in **Plugins** list
- [ ] Plugin is activated
- [ ] Settings page accessible at **Salesforce > Settings**
- [ ] All configuration fields populated
- [ ] Private key formatted correctly (includes BEGIN/END lines)
- [ ] Test connection successful (**Salesforce > Test Connection**)
- [ ] Form displays correctly with shortcode `[afa_salesforce_form]`
- [ ] Test form submission works
- [ ] Contact created in Salesforce
- [ ] Redirect works correctly

## Testing Workflow

### Quick Test (Per Site)

1. Go to **Salesforce > Test Connection**
2. Click **Test Connection**
3. Verify "Connection Successful!"

### Full Test (Per Site)

1. Create test page with `[afa_salesforce_form]` shortcode
2. Fill out form with test data
3. Submit form
4. Verify success message
5. Check Salesforce for new contact
6. Verify redirect works

### Test Data Template

```
First Name: Test
Last Name: User [Site Name]
Email: test+[sitename]@yourdomain.com
Affiliation: Active Duty
Branch: United States Air Force
Zip Code: 12345
```

Use unique emails per site for tracking.

## Troubleshooting Multi-Site Deployment

### Common Issues

**Plugin not appearing after upload**
- Check file permissions (755 for directories, 644 for files)
- Verify upload to correct directory
- Try activating via wp-admin/plugins.php

**Settings not saving**
- Check database connection
- Verify user has admin permissions
- Check for PHP errors in logs

**Different sites behaving differently**
- Verify same plugin version on all sites
- Check PHP versions match
- Compare WordPress versions
- Review theme conflicts

### Site-Specific Configuration

If sites need different configurations:

**Different Salesforce Instances**

Create separate External Client Apps:
- Site 1: Uses Consumer Key A
- Site 2: Uses Consumer Key B
- Site 3: Uses Consumer Key C

**Different Redirect URLs**

Configure per-site:
```bash
wp option update afa_salesforce_apex_endpoint_auth "/services/apexrest/AFAUserAuth"
```

**Different Form Fields**

Override form via theme:
1. Copy form template to theme
2. Customize fields per site
3. Use filter to modify data

## Monitoring and Maintenance

### Health Check Script

Create a script to verify all sites are working:

```bash
#!/bin/bash

SITES=(
    "https://site1.com"
    "https://site2.com"
    "https://site3.com"
)

for site in "${SITES[@]}"; do
    # Check if form page is accessible
    status=$(curl -s -o /dev/null -w "%{http_code}" "$site/contact/")

    if [ $status -eq 200 ]; then
        echo "✓ $site - OK"
    else
        echo "✗ $site - Error (HTTP $status)"
    fi
done
```

### Update Procedure

When updating the plugin on multiple sites:

1. Test update on staging site first
2. Backup all sites
3. Update plugin files
4. Test each site
5. Monitor error logs for 24 hours

### Centralized Monitoring

Consider implementing:
- Uptime monitoring (e.g., UptimeRobot)
- Error log aggregation (e.g., Papertrail)
- Salesforce API usage monitoring
- Form submission tracking

## Security Best Practices

### Private Key Management

**DO:**
- Store private key in secure password manager
- Use different keys for staging/production
- Rotate keys annually
- Document key locations

**DON'T:**
- Commit keys to version control
- Email keys unencrypted
- Store keys in plain text files
- Share keys via chat tools

### Access Control

- Limit WordPress admin access
- Use strong passwords for integration users
- Enable 2FA on WordPress admin accounts
- Regular security audits
- Monitor API usage in Salesforce

## Documentation for Team

Create a shared document with:

1. **Plugin Overview**
   - What it does
   - How it works
   - Support contact

2. **Site List**
   - URLs
   - WordPress admin URLs
   - Credentials (secure location)

3. **Configuration Reference**
   - All settings explained
   - Where to find Salesforce credentials
   - How to test

4. **Troubleshooting**
   - Common issues
   - Who to contact
   - Where to check logs

5. **Update Procedure**
   - When to update
   - How to update
   - Testing requirements

## Support Contacts

- **Plugin Issues**: [Your support email]
- **Salesforce Issues**: Salesforce admin contact
- **WordPress Issues**: WordPress admin contact
- **Emergency Contact**: [24/7 contact]

## Deployment Checklist

Use this checklist for each site:

```
Site: ________________________
Date: ________________________
Deployed by: __________________

Pre-Deployment:
[ ] Backup site
[ ] Document current state
[ ] Note current WordPress version
[ ] Note current PHP version

Deployment:
[ ] Upload plugin files
[ ] Activate plugin
[ ] Configure settings
[ ] Save settings

Testing:
[ ] Test connection successful
[ ] Form displays correctly
[ ] Test submission works
[ ] Contact created in Salesforce
[ ] Redirect works
[ ] Email validation works
[ ] Zip validation works

Post-Deployment:
[ ] Document completion
[ ] Update site inventory
[ ] Monitor for 24 hours
[ ] Archive old backups

Notes:
_________________________________
_________________________________
_________________________________
```

## Estimated Timeline

For deploying to multiple sites:

- **Preparation**: 30-60 minutes (one time)
- **Per Site (Manual)**: 10-15 minutes
- **Per Site (Scripted)**: 2-3 minutes
- **Testing Per Site**: 5-10 minutes

**Total for 6 sites:**
- Manual: 2-3 hours
- Scripted: 1-2 hours

## Next Steps After Deployment

1. Create thank-you pages on each site
2. Set up analytics tracking (if needed)
3. Train content editors on form usage
4. Schedule monthly verification tests
5. Plan for future updates
6. Document any site-specific customizations
