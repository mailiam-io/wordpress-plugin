=== Mailiam ===
Contributors: mailiam
Tags: contact form, email, forms, spam protection, mail
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Powerful email forms with built-in spam protection, SRS forwarding, and reliable delivery. No SMTP configuration needed.

== Description ==

Mailiam brings enterprise-grade email infrastructure to WordPress. Stop worrying about unreliable wp_mail(), spam issues, and complex SMTP configurations.

**Why Mailiam?**

* **Reliable Delivery** - Built on AWS SES with queue-based processing
* **Advanced Spam Protection** - Multi-layer security with honeypot, rate limiting, and content filtering
* **SRS Forwarding** - Industry-first Sender Rewriting Scheme support
* **No SMTP Setup** - Works instantly, no server configuration needed
* **Public Token Security** - Safe to use API keys in your forms
* **Clean, Simple Forms** - Beautiful forms that match your theme

**Perfect for:**

* Contact forms
* Demo requests
* Support tickets
* Newsletter signups
* Any form that needs bulletproof email delivery

**How It Works**

1. Install the plugin
2. Enter your Mailiam API key (one-time setup)
3. Add `[mailiam_form id="contact"]` to any page
4. Configure forms in your `mailiam.config.yaml` file
5. Deploy with `mailiam push`

Form configuration stays in Mailiam, keeping your WordPress site clean and focused.

**Unique Features**

* **Three-Tier API Keys** - Public, usage, and admin keys for maximum security
* **Domain-Scoped Tokens** - Public keys only work for your specified domain
* **SRS Support** - Enable replies through forwarded emails
* **Email Retention** - S3 backup prevents email loss
* **Queue-Based Processing** - Never miss an email, even under load

== Installation ==

**From WordPress Admin:**

1. Go to Plugins > Add New
2. Search for "Mailiam"
3. Click "Install Now" and then "Activate"
4. Go to Settings > Mailiam to configure

**Manual Installation:**

1. Download the plugin zip file
2. Extract to `/wp-content/plugins/mailiam-wordpress/`
3. Activate through the 'Plugins' menu in WordPress
4. Go to Settings > Mailiam to configure

**Configuration:**

1. Sign up at [mailiam.io](https://mailiam.io)
2. Create an API key: `mailiam apikeys create`
3. Enter your API key in Settings > Mailiam
4. Add forms to your `mailiam.config.yaml`:

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

5. Deploy: `mailiam push`
6. Add to any page: `[mailiam_form id="contact"]`

== Frequently Asked Questions ==

= Do I need a Mailiam account? =

Yes, you'll need a Mailiam account to use this plugin. Sign up for free at [mailiam.io](https://mailiam.io).

= How do I create forms? =

Forms are configured in your `mailiam.config.yaml` file, not in WordPress. This keeps your WordPress site clean and makes forms easy to version control.

= Is my API key safe? =

Yes! The plugin uses Mailiam's public token system. Public tokens are designed to be safely embedded in forms - they're domain-scoped and have limited permissions.

= Can I customize the form fields? =

Yes! Use the `mailiam_form_fields` filter to customize form HTML:

```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'custom') {
        return '<div>Your custom form HTML</div>';
    }
    return $html;
}, 10, 2);
```

= Does it work with page builders? =

Yes! The shortcode works with all major page builders including Elementor, Divi, Beaver Builder, and WPBakery.

= What about spam protection? =

Mailiam includes enterprise-grade spam protection:
- Honeypot fields
- Rate limiting (100 requests/hour by default)
- IP-based throttling
- Content filtering
- Domain validation
- Origin checking

= Can I view submissions in WordPress? =

Currently, submissions are managed through the Mailiam API and CLI. A dashboard for viewing submissions is coming soon.

= How does pricing work? =

See current pricing at [mailiam.io/pricing](https://mailiam.io/pricing). The plugin itself is free.

== Screenshots ==

1. Clean, modern contact form
2. Simple settings page - just enter your API key
3. Shortcode in WordPress editor
4. Form configuration in mailiam.config.yaml

== Changelog ==

= 1.0.0 =
* Initial release
* Shortcode support: [mailiam_form]
* Public token auto-generation
* AJAX form submission
* Spam protection with honeypot
* Customizable success/error messages
* Clean, responsive form styling

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Mailiam WordPress plugin.

== Developer Documentation ==

**Filters:**

`mailiam_form_fields` - Customize form HTML
```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    // Return custom HTML
    return $html;
}, 10, 2);
```

**Shortcode Attributes:**

* `id` - Form identifier (matches your mailiam.config.yaml)
* `class` - Additional CSS class
* `button` - Button text (default: "Send Message")
* `redirect` - URL to redirect after success

**Example:**
```
[mailiam_form id="contact" class="my-form" button="Submit" redirect="/thank-you"]
```

**Requirements:**

* WordPress 5.0+
* PHP 7.4+
* Mailiam account
* Domain configured in Mailiam

== Support ==

* Documentation: [docs.mailiam.io](https://docs.mailiam.io)
* Issues: [github.com/mailiam/wordpress-plugin](https://github.com/mailiam/wordpress-plugin)
* Email: support@mailiam.io
