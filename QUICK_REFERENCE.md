# Quick Reference: Using OAuth Authentication

## Available Shortcodes

### 1. Members-Only Content
Wrap content that should only be visible to authenticated users:

```php
[afa_members_only]
This content is only visible to logged-in members.
[/afa_members_only]
```

With custom message:
```php
[afa_members_only message="You must be a member to view this."]
Secret member content here.
[/afa_members_only]
```

### 2. Display User Information
Show information about the logged-in user:

```php
Welcome, [afa_user_info field="name"]!
Your email: [afa_user_info field="email"]
Your Salesforce ID: [afa_user_info field="user_id"]
```

With default value if not logged in:
```php
Hello, [afa_user_info field="name" default="Guest"]
```

### 3. Logout Button
Display a logout link:

```php
[afa_logout_button]
```

With custom text and redirect:
```php
[afa_logout_button text="Sign Out" redirect="https://yoursite.com/goodbye"]
```

## PHP Functions

### Check if User is Authenticated
```php
if ( afa_is_sf_user_authenticated() ) {
    echo "User is logged in!";
}
```

### Get Salesforce User ID
```php
$user_id = afa_get_sf_user_id();
echo "Salesforce User ID: " . $user_id;
```

### Get User Data
```php
$user_data = afa_get_sf_user_data();
if ( $user_data ) {
    echo "Name: " . $user_data['name'];
    echo "Email: " . $user_data['email'];
}
```

### Require Authentication
Block content display unless user is authenticated:

```php
if ( ! afa_require_sf_auth() ) {
    return; // User not authenticated, login form displayed
}

// Content below only shown to authenticated users
echo "<h2>Member Dashboard</h2>";
```

With custom message:
```php
if ( ! afa_require_sf_auth( "Members only! Please log in." ) ) {
    return;
}
```

### Logout Programmatically
```php
afa_sf_logout(); // Logs out and stays on current page
afa_sf_logout( home_url() ); // Logs out and redirects to home
```

## Example Page Templates

### Member Dashboard Page
```php
<?php
// Template Name: Member Dashboard

if ( ! afa_require_sf_auth( "Please log in to access your dashboard." ) ) {
    get_footer();
    exit;
}

$user_data = afa_get_sf_user_data();
?>

<h1>Welcome, <?php echo esc_html( $user_data['name'] ); ?></h1>

<div class="member-info">
    <p>Email: <?php echo esc_html( $user_data['email'] ); ?></p>
    <p>User ID: <?php echo esc_html( afa_get_sf_user_id() ); ?></p>
</div>

<div class="member-content">
    <h2>Your Member Benefits</h2>
    <!-- Member-only content here -->
</div>

<?php echo do_shortcode( '[afa_logout_button text="Sign Out" redirect="/"]' ); ?>
```

### Mixed Public/Private Content
```php
<h1>Our Services</h1>

<div class="public-content">
    <p>This content is visible to everyone.</p>
</div>

<?php if ( afa_is_sf_user_authenticated() ) : ?>
    <div class="member-content">
        <h2>Member Benefits</h2>
        <p>Exclusive content for <?php echo esc_html( afa_get_sf_user_data()['name'] ); ?></p>
    </div>
<?php else : ?>
    <div class="login-prompt">
        <p>Log in to see member benefits!</p>
        <?php echo do_shortcode( '[afa_salesforce_form]' ); ?>
    </div>
<?php endif; ?>
```

## CSS Classes

The plugin adds CSS classes to the body tag based on authentication status:

- `sf-authenticated` - User is logged in via Salesforce
- `sf-guest` - User is not logged in

You can use these for styling:

```css
/* Show member badge only when authenticated */
.member-badge {
    display: none;
}

.sf-authenticated .member-badge {
    display: block;
}

/* Different header for guests vs members */
.sf-guest .main-header {
    background-color: #f0f0f0;
}

.sf-authenticated .main-header {
    background-color: #007bff;
}
```

## URL Parameters

### Success/Error Messages
After OAuth callback, the user is redirected with parameters:

- Success: `?sf_auth=success`
- Error: `?sf_auth=error&sf_msg=error+message`

You can check for these and display messages:

```php
if ( isset( $_GET['sf_auth'] ) && $_GET['sf_auth'] === 'success' ) {
    echo '<div class="success">Welcome! You are now logged in.</div>';
}

if ( isset( $_GET['sf_auth'] ) && $_GET['sf_auth'] === 'error' ) {
    $error_msg = isset( $_GET['sf_msg'] ) ? urldecode( $_GET['sf_msg'] ) : 'Login failed';
    echo '<div class="error">' . esc_html( $error_msg ) . '</div>';
}
```

## Common Patterns

### Navigation Menu with Login/Logout
```php
<nav class="main-nav">
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about">About</a></li>
        <?php if ( afa_is_sf_user_authenticated() ) : ?>
            <li><a href="/dashboard">Dashboard</a></li>
            <li><?php echo do_shortcode( '[afa_logout_button]' ); ?></li>
        <?php else : ?>
            <li><a href="/join">Join</a></li>
        <?php endif; ?>
    </ul>
</nav>
```

### User Greeting
```php
<div class="user-greeting">
    <?php if ( afa_is_sf_user_authenticated() ) : ?>
        Welcome back, <?php echo do_shortcode( '[afa_user_info field="name"]' ); ?>!
    <?php else : ?>
        Welcome, Guest! <a href="/join">Become a Member</a>
    <?php endif; ?>
</div>
```

### Tiered Content Access
```php
// Everyone can see this
<div class="tier-1">
    <h2>Basic Information</h2>
    <p>Public content here...</p>
</div>

<?php if ( afa_is_sf_user_authenticated() ) : ?>
    // Only authenticated users can see this
    <div class="tier-2">
        <h2>Member Resources</h2>
        <p>Member-only content here...</p>
    </div>
<?php endif; ?>
```
