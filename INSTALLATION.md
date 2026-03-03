# AFA Salesforce Integration - Installation & Setup Guide

## Table of Contents

1. [Requirements](#requirements)
2. [Plugin Installation](#plugin-installation)
3. [Salesforce Setup](#salesforce-setup)
4. [Plugin Configuration](#plugin-configuration)
5. [Testing](#testing)
6. [Deployment to Multiple Sites](#deployment-to-multiple-sites)
7. [Troubleshooting](#troubleshooting)

## Requirements

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenSSL PHP extension enabled
- cURL PHP extension enabled
- HTTPS enabled (recommended)

### Salesforce Requirements
- Salesforce account (Sandbox or Production)
- API access enabled
- Permission to create External Client Apps
- Permission to deploy APEX classes
- Integration user account

## Plugin Installation

### Option 1: Upload via WordPress Admin

1. Download the `afa-salesforce-integration` folder as a ZIP file
2. Log in to WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file and click **Install Now**
6. Click **Activate Plugin**

### Option 2: Manual FTP Upload

1. Upload the `afa-salesforce-integration` folder to `/wp-content/plugins/`
2. Log in to WordPress admin panel
3. Navigate to **Plugins**
4. Find "AFA Salesforce Integration" and click **Activate**

### Option 3: Direct Copy

1. Copy the `afa-salesforce-integration` folder to `/wp-content/plugins/`
2. Ensure proper file permissions (755 for directories, 644 for files)
3. Activate via WordPress admin

## Salesforce Setup

### Step 1: Create External Client App

1. Log in to Salesforce
2. Go to **Setup > App Manager**
3. Click **New Connected App** (or **New External Client App**)
4. Fill in basic information:
   - Connected App Name: `WordPress Integration`
   - API Name: `WordPress_Integration`
   - Contact Email: Your email

5. Enable OAuth Settings:
   - Check **Enable OAuth Settings**
   - Callback URL: `https://yoursite.com/oauth/callback` (required but not used)
   - Check **Use Digital Signatures**
   - Click **Choose File** and upload your certificate (see below)

6. Select OAuth Scopes:
   - Access the identity URL service (id, profile, email, address, phone)
   - Manage user data via APIs (api)
   - Perform requests at any time (refresh_token, offline_access)

7. Additional Settings:
   - Permitted Users: **Admin approved users are pre-authorized**
   - IP Relaxation: **Relax IP restrictions** (for development)

8. Save and note your **Consumer Key**

### Step 2: Generate RSA Certificate and Key

Run these commands in your terminal:

```bash
# Generate private key
openssl genrsa -out salesforce.key 2048

# Generate certificate signing request
openssl req -new -key salesforce.key -out salesforce.csr

# Generate self-signed certificate (valid for 1 year)
openssl x509 -req -days 365 -in salesforce.csr -signkey salesforce.key -out salesforce.crt
```

Upload `salesforce.crt` to Salesforce External Client App.
Save `salesforce.key` - you'll paste this into WordPress settings.

### Step 3: Create Integration User

1. Go to **Setup > Users > Users**
2. Create new user or use existing
3. Username format:
   - Sandbox: `wp-integration@yourdomain.com.fullsb`
   - Production: `wp-integration@yourdomain.com`
4. Assign profile with **API Enabled** permission
5. Note the username for WordPress settings

### Step 4: Grant App Access to User

1. Go to **Setup > App Manager**
2. Find your External Client App
3. Click dropdown > **Manage**
4. Click **Edit Policies** or **Manage Profiles**
5. Add the integration user's profile
6. Save

### Step 5: Deploy APEX REST Class

Create this APEX class in Salesforce:

```apex
@RestResource(urlMapping='/AFACreateUser/*')
global with sharing class AFACreateUserService {
    
    @HttpPost
    global static ResponseWrapper createUser() {
        ResponseWrapper response = new ResponseWrapper();
        
        try {
            RestRequest req = RestContext.request;
            String requestBody = req.requestBody.toString();
            Map<String, Object> params = (Map<String, Object>) JSON.deserializeUntyped(requestBody);
            
            String firstName = (String) params.get('first_name');
            String lastName = (String) params.get('last_name');
            String email = (String) params.get('email');
            String zipCode = (String) params.get('zip_code');
            String affiliation = (String) params.get('affiliation');
            String branch = (String) params.get('branch');
            
            if (String.isBlank(firstName) || String.isBlank(lastName) || 
                String.isBlank(email) || String.isBlank(zipCode)) {
                response.success = false;
                response.message = 'Required fields missing';
                RestContext.response.statusCode = 400;
                return response;
            }
            
            List<Contact> existingContacts = [
                SELECT Id, Email 
                FROM Contact 
                WHERE Email = :email 
                LIMIT 1
            ];
            
            if (!existingContacts.isEmpty()) {
                response.success = false;
                response.message = 'A contact with this email already exists';
                response.contactId = existingContacts[0].Id;
                RestContext.response.statusCode = 409;
                return response;
            }
            
            Contact newContact = new Contact(
                FirstName = firstName,
                LastName = lastName,
                Email = email,
                MailingPostalCode = zipCode,
                AFA_Eligibility__c = affiliation,
                Branch_of_Service__c = branch
            );
            
            insert newContact;
            
            response.success = true;
            response.message = 'Contact created successfully';
            response.contactId = newContact.Id;
            RestContext.response.statusCode = 201;
            
        } catch (Exception e) {
            response.success = false;
            response.message = 'Error: ' + e.getMessage();
            RestContext.response.statusCode = 500;
        }
        
        return response;
    }
    
    global class ResponseWrapper {
        public Boolean success;
        public String message;
        public String contactId;
    }
}
```

**Grant APEX Class Access:**
1. Go to **Setup > Apex Classes**
2. Find `AFACreateUserService`
3. Click **Security**
4. Add integration user's profile
5. Save

## Plugin Configuration

### Step 1: Access Settings

1. Log in to WordPress admin
2. Go to **Salesforce > Settings**

### Step 2: Environment Settings

**Environment:** Select `Sandbox` or `Production`

**Sandbox URL:**
```
https://yourorg--fullsb.sandbox.my.salesforce.com
```

**Production Instance URL:**
```
https://yourorg.my.salesforce.com
```

### Step 3: Salesforce Credentials

**Consumer Key (Client ID):**
- Get from Salesforce App Manager > Your App > View
- Paste the entire key

**Integration Username:**
- Sandbox: `wp-integration@yourdomain.com.fullsb`
- Production: `wp-integration@yourdomain.com`

**Private Key:**
- Open `salesforce.key` file
- Copy entire contents including BEGIN/END lines
- Paste into textarea

Example format:
```
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA...
(multiple lines)
...end of key
-----END RSA PRIVATE KEY-----
```

### Step 4: Advanced Settings

**APEX REST Endpoint:**
```
/services/apexrest/AFACreateUser
```

**Success Redirect URL:**
```
https://yoursite.com/thank-you/
```

### Step 5: Save Settings

Click **Save Settings**

## Testing

### Test 1: Connection Test

1. Go to **Salesforce > Test Connection**
2. Click **Test Connection**
3. Verify configuration shows all required settings
4. Check for "Connection Successful!" message

If connection fails, check error messages and refer to Troubleshooting section.

### Test 2: Form Display

1. Create a new page in WordPress
2. Add shortcode: `[afa_salesforce_form]`
3. Publish page
4. View page to verify form displays correctly

### Test 3: Form Submission

1. Fill out the test form with valid data
2. Submit form
3. Verify:
   - Loading spinner appears
   - Success message displays
   - Redirect occurs (if configured)
   - Contact created in Salesforce

### Test 4: Validation

Test these scenarios:
- Empty required fields
- Invalid email format
- Invalid zip code format
- Duplicate email address

Verify appropriate error messages display.

## Deployment to Multiple Sites

### Method 1: Plugin Files + Configuration Export

1. **First Site Setup:**
   - Install and configure plugin completely
   - Test thoroughly

2. **Export Configuration:**
   - Document all settings from Salesforce > Settings
   - Save private key securely

3. **Deploy to Additional Sites:**
   - Install plugin on each site
   - Configure using documented settings
   - Test each installation

### Method 2: Database Export (Same Settings)

If all sites use the same Salesforce instance:

1. Install plugin on all sites
2. On first site, configure completely
3. Export these database options:
   ```sql
   SELECT * FROM wp_options WHERE option_name LIKE 'afa_salesforce_%';
   ```
4. Import to other sites (adjust table prefix if different)
5. Test each site

### Method 3: Automated Deployment

For advanced users with WP-CLI:

```bash
# On configured site, export settings
wp option get afa_salesforce_is_sandbox
wp option get afa_salesforce_consumer_key
# ... etc for all settings

# On new sites, import settings
wp option update afa_salesforce_is_sandbox 1
wp option update afa_salesforce_consumer_key "your_key"
# ... etc
```

### Security Considerations for Multi-Site Deployment

1. **Private Key Security:**
   - Never commit private key to version control
   - Use secure transfer methods (encrypted files)
   - Consider environment variables for production

2. **Different Credentials per Site:**
   - If sites need separate tracking, create separate Consumer Keys
   - Use different integration users per site
   - Document which site uses which credentials

3. **Staging vs Production:**
   - Use Sandbox for staging sites
   - Use Production for live sites
   - Test Sandbox thoroughly before switching to Production

## Troubleshooting

### Connection Fails

**Error: "Consumer Key is not configured"**
- Verify Consumer Key is entered in settings
- Check for extra spaces or characters
- Re-copy from Salesforce

**Error: "Unable to parse private key"**
- Verify key includes BEGIN/END lines
- Check for line breaks or formatting issues
- Regenerate certificate and key if needed

**Error: "user hasn't approved this consumer"**
- Grant app access to integration user profile
- Verify "Admin approved users are pre-authorized" is selected
- Check user has API Enabled permission

### Form Not Displaying

- Verify shortcode is correct: `[afa_salesforce_form]`
- Check for JavaScript errors in browser console
- Ensure plugin is activated
- Try clearing cache (if using caching plugin)

### AJAX Submission Fails

- Check browser console for errors
- Verify AJAX URL is correct (should be wp-admin/admin-ajax.php)
- Check nonce verification isn't failing
- Enable WP_DEBUG to see detailed errors

### Duplicate Email Not Detected

- Verify APEX class returns 409 status code for duplicates
- Check Salesforce debug logs
- Test APEX class directly in Salesforce

### Form Styling Issues

- Check for theme CSS conflicts
- Add `!important` to plugin CSS if needed
- Use browser inspector to identify conflicting styles

## Support

For additional support:
- Check plugin documentation
- Review Salesforce debug logs
- Enable WordPress debug logging
- Contact: support@afa.org

## Next Steps

After successful installation:
1. Customize form styling to match your theme
2. Create thank-you page for redirects
3. Set up Google Analytics tracking (if needed)
4. Create documentation for content editors
5. Schedule regular testing of integration
6. Monitor Salesforce API usage

## Security Checklist

- [ ] HTTPS enabled on WordPress site
- [ ] Private key stored securely
- [ ] WP_DEBUG disabled in production
- [ ] Strong passwords for integration user
- [ ] Regular security updates applied
- [ ] Access logs monitored
- [ ] Backup configuration documented
