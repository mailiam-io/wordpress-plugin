# Mailiam for WordPress

Powerful email forms with built-in spam protection, SRS forwarding, and reliable delivery. No SMTP configuration needed.

## Features

- **Reliable Email Delivery** - Built on AWS SES with queue-based processing
- **Advanced Spam Protection** - Multi-layer security with honeypot, rate limiting, and content filtering
- **SRS Forwarding** - Industry-first Sender Rewriting Scheme support
- **Public Token Security** - Domain-scoped API keys safe to use in forms
- **Simple Integration** - One shortcode, infinite possibilities
- **No SMTP Setup** - Works instantly without server configuration

## Installation

### From GitHub Releases (Recommended)

1. **Download** the latest release:
   - Visit: [Latest Release](https://github.com/mailiam/wordpress-plugin/releases/latest)
   - Download `mailiam-wordpress.zip`

2. **Install** in WordPress:
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the downloaded ZIP file
   - Click "Install Now" and then "Activate"

3. **Configure Mailiam**:
   - Go to Settings → Mailiam
   - Enter your Mailiam API key (get one with `mailiam apikeys create`)
   - Save settings (plugin auto-generates a public token)

4. **Add a form to any page**:
   ```
   [mailiam_form id="contact"]
   ```

5. **Configure the form in Mailiam**:

   Edit `mailiam.config.yaml`:
   ```yaml
   domains:
     yoursite.com:
       forms:
         contact:
           recipient: admin@yoursite.com
           acknowledgment:
             enabled: true
             subject: "Thanks for contacting us!"
   ```

6. **Deploy**:
   ```bash
   mailiam push
   ```

**Automatic Updates:** Once installed, the plugin will automatically check for updates and show notifications in your WordPress admin. One-click updates, just like WordPress.org plugins!

### For Developers (Git Clone)

```bash
cd wp-content/plugins
git clone https://github.com/mailiam/wordpress-plugin.git mailiam-wordpress
cd mailiam-wordpress
# Activate in WordPress admin
```

## Usage

### Contact Forms (Shortcode)

#### Basic Shortcode

```
[mailiam_form id="contact"]
```

#### Custom Button Text

```
[mailiam_form id="contact" button="Send Message"]
```

#### Custom CSS Class

```
[mailiam_form id="demo" class="demo-form-wrapper"]
```

#### Redirect After Submission

```
[mailiam_form id="contact" redirect="/thank-you"]
```

#### All Options

```
[mailiam_form id="contact" class="my-form" button="Submit" redirect="/success"]
```

### Transactional Emails (Programmatic)

Send emails programmatically for order confirmations, password resets, etc.

#### Setup

1. Go to Settings > Mailiam
2. Add a **Usage API Key** (mlm_sk_*)
3. Save settings

#### Basic Usage

```php
mailiam_send_email([
    'to' => 'customer@example.com',
    'from' => 'orders@yourstore.com',
    'subject' => 'Order Confirmation',
    'html' => '<h1>Thank you for your order!</h1>',
    'text' => 'Thank you for your order!'
]);
```

#### With Template Variables

```php
mailiam_send_email([
    'to' => 'customer@example.com',
    'from' => 'orders@yourstore.com',
    'subject' => 'Order #{{order_number}} Confirmed',
    'html' => '<h1>Order #{{order_number}}</h1><p>Total: {{total}}</p>',
    'data' => [
        'order_number' => '12345',
        'total' => '$99.99'
    ]
]);
```

#### WooCommerce Integration

The plugin automatically hooks into WooCommerce events if:
- WooCommerce is installed
- Usage API key is configured

Supported events:
- Order completed
- Order processing
- Order cancelled
- New customer registration

**Disable WooCommerce integration:**
```php
// In your theme's functions.php
remove_action('woocommerce_order_status_completed', array('Mailiam_WooCommerce', 'send_order_completed_email'));
```

#### Custom Email Example

```php
// Send welcome email on user registration
add_action('user_register', function($user_id) {
    $user = get_userdata($user_id);

    mailiam_send_email([
        'to' => $user->user_email,
        'from' => 'welcome@yoursite.com',
        'subject' => 'Welcome to Our Site!',
        'html' => '<h1>Welcome!</h1><p>Thanks for joining.</p>'
    ]);
});
```

## Form Configuration

Forms are configured in your `mailiam.config.yaml` file, not in WordPress. This approach:

- Keeps WordPress clean and focused
- Makes forms easy to version control
- Enables powerful server-side features
- Centralizes email configuration

### Example Configuration

```yaml
project:
  name: My WordPress Site
  slug: my-site

domains:
  example.com:
    sender:
      name: Example Company
      email: noreply@example.com

    allowedOrigins:
      - https://example.com
      - https://www.example.com

    forms:
      # Contact form
      contact:
        name: Contact Form
        recipient: admin@example.com
        acknowledgment:
          enabled: true
          subject: "Thanks for contacting us!"
          template: contact-acknowledgment
        settings:
          rateLimit: 50

      # Demo request
      demo:
        name: Demo Request
        recipient: sales@example.com
        acknowledgment:
          enabled: true
          subject: "Your demo request"

      # Support ticket
      support:
        name: Support Form
        recipient: support@example.com

templates:
  contact-acknowledgment:
    subject: "Thanks for contacting us!"
    html: |
      <h1>Thank you for reaching out!</h1>
      <p>We've received your message and will get back to you soon.</p>
    text: |
      Thank you for reaching out!
      We've received your message and will get back to you soon.
```

## Customization

### Custom Form Fields

Use the `mailiam_form_fields` filter to customize form HTML:

```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'newsletter') {
        return '
            <div class="mailiam-form-row">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required />
            </div>
            <div class="mailiam-form-row">
                <label>
                    <input type="checkbox" name="subscribe" value="yes" />
                    Subscribe to newsletter
                </label>
            </div>
        ';
    }
    return $html;
}, 10, 2);
```

### Custom Styling

Override the default styles:

```css
/* Your theme's style.css */
.mailiam-form {
    background: #f9f9f9;
    padding: 2rem;
    border-radius: 8px;
}

.mailiam-submit-button {
    background: #your-brand-color;
}
```

### Programmatic Form Submission

Submit forms programmatically:

```php
$api = new Mailiam_API();
$result = $api->submit_form('example.com', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello!'
], get_option('mailiam_settings')['public_key']);

if (is_wp_error($result)) {
    // Handle error
    echo $result->get_error_message();
} else {
    // Success
    echo 'Email sent!';
}
```

## Security

### Public Token System

Mailiam uses a three-tier API key system:

1. **Public Keys** (`mlm_pk_*`)
   - Safe to embed in forms
   - Domain-scoped (only work for specified domain)
   - Limited permissions (forms:send only)
   - 100 requests/hour rate limit

2. **Usage Keys** (`mlm_sk_*`)
   - Server-side only
   - Standard permissions
   - 1000 requests/hour

3. **Admin Keys** (`mlm_sk_admin_*`)
   - Full account access
   - Used for setup only
   - Never exposed to frontend

The plugin automatically creates a public token during setup and stores it safely in WordPress options.

### Spam Protection

Multiple layers of spam protection:

- **Honeypot field** - Hidden field catches bots
- **Rate limiting** - IP-based throttling
- **Domain validation** - Origin header checking
- **Content filtering** - Server-side spam detection
- **Nonce verification** - WordPress security tokens

## Architecture

### File Structure

```
mailiam-wordpress/
├── mailiam.php              # Main plugin file
├── includes/
│   ├── class-mailiam-api.php    # API client
│   ├── class-mailiam-admin.php  # Admin settings
│   └── class-mailiam-form.php   # Form handler
├── assets/
│   ├── js/
│   │   └── form.js          # Frontend JavaScript
│   └── css/
│       ├── form.css         # Form styling
│       └── admin.css        # Admin styling
├── README.md                # This file
└── readme.txt               # WordPress.org readme
```

### Request Flow

```
WordPress Page
    ↓
[mailiam_form] shortcode
    ↓
HTML form rendered
    ↓
User submits (AJAX)
    ↓
wp_ajax_mailiam_submit
    ↓
Mailiam API (/v1/{domain}/send)
    ↓
Redis Queue (BullMQ)
    ↓
Queue Worker
    ↓
AWS SES
    ↓
Email delivered
```

## API Reference

### Classes

#### `Mailiam_API`

Main API client for communicating with Mailiam.

**Methods:**

- `create_public_key($admin_key, $domain)` - Create public token
- `submit_form($domain, $data, $public_key)` - Submit form data
- `test_api_key($api_key)` - Validate API key
- `delete_api_key($admin_key, $key_id)` - Delete API key
- `list_api_keys($admin_key)` - List all API keys

#### `Mailiam_Admin`

Admin interface and settings management.

**Hooks:**
- `admin_menu` - Adds settings page
- `admin_init` - Registers settings

#### `Mailiam_Form`

Form rendering and submission handling.

**Hooks:**
- `wp_enqueue_scripts` - Enqueues assets
- `wp_ajax_mailiam_submit` - Handles AJAX (logged in)
- `wp_ajax_nopriv_mailiam_submit` - Handles AJAX (logged out)

**Shortcodes:**
- `[mailiam_form]` - Renders contact form

### Filters

#### `mailiam_form_fields`

Customize form HTML.

```php
apply_filters('mailiam_form_fields', string $html, string $form_id)
```

**Parameters:**
- `$html` - Default form HTML
- `$form_id` - Form identifier

**Returns:** Custom HTML string

### Helper Functions

#### `mailiam_get_settings()`

Get plugin settings.

```php
$settings = mailiam_get_settings();
// Returns array with: api_key, public_key, domain, success_message, error_message
```

#### `mailiam_is_configured()`

Check if plugin is configured.

```php
if (mailiam_is_configured()) {
    // Plugin ready to use
}
```

## Development

### Requirements

- WordPress 5.0+
- PHP 7.4+
- Mailiam account

### Local Development

1. **Clone the repo**
   ```bash
   git clone https://github.com/mailiam/wordpress-plugin.git
   cd wordpress-plugin
   ```

2. **Symlink to WordPress**
   ```bash
   ln -s $(pwd) /path/to/wordpress/wp-content/plugins/mailiam-wordpress
   ```

3. **Activate and test**
   - Activate in WordPress admin
   - Configure with test API key
   - Test form submissions

### Testing

Test form submission:

```bash
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=mailiam_submit" \
  -d "nonce=YOUR_NONCE" \
  -d "form_id=contact" \
  -d "form_data[0][name]=name" \
  -d "form_data[0][value]=John" \
  -d "form_data[1][name]=email" \
  -d "form_data[1][value]=john@example.com"
```

## Troubleshooting

### Forms not submitting

1. Check plugin is configured: Settings > Mailiam
2. Verify public key is present
3. Check browser console for JavaScript errors
4. Verify form ID matches `mailiam.config.yaml`

### API key errors

- Make sure you're using an admin or usage key (mlm_sk_*)
- Public keys (mlm_pk_*) can't be used for setup
- Test key validity: `mailiam apikeys list`

### Email not delivering

1. Check Mailiam configuration: `mailiam push`
2. Verify domain is configured
3. Check form recipient is set
4. Review Mailiam logs: `mailiam logs`

### Styling conflicts

If form styles conflict with your theme:

```css
/* Disable plugin styles */
.mailiam-form {
    all: unset;
}

/* Add your custom styles */
```

## Support

- **Documentation:** [docs.mailiam.io](https://docs.mailiam.io)
- **Issues:** [GitHub Issues](https://github.com/mailiam/wordpress-plugin/issues)
- **Email:** support@mailiam.io
- **Community:** [Discord](https://discord.gg/mailiam)

## License

MIT License - see LICENSE file for details

## Credits

Built with love by the Mailiam team.

Powered by:
- AWS SES for reliable email delivery
- Redis/BullMQ for queue processing
- Node.js for the API server
- PostgreSQL for data storage
