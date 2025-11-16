# Mailiam WordPress Plugin - Quick Start Guide

Get your first form running in 5 minutes!

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- A Mailiam account ([sign up at mailiam.io](https://mailiam.io))

## Step 1: Install the Plugin (1 minute)

### Option A: From WordPress Admin
1. Go to **Plugins > Add New**
2. Search for "Mailiam"
3. Click **Install Now**
4. Click **Activate**

### Option B: Manual Installation
1. Download the plugin
2. Upload to `/wp-content/plugins/mailiam-wordpress/`
3. Activate from **Plugins** menu

## Step 2: Get Your API Key (1 minute)

In your terminal:

```bash
# Create a usage API key
mailiam apikeys create
```

Select:
- Type: **Usage** (mlm_sk_*)
- Name: **WordPress Site**
- Permissions: **Default**

Copy the API key (starts with `mlm_sk_`)

## Step 3: Configure the Plugin (1 minute)

1. Go to **Settings > Mailiam** in WordPress admin
2. Paste your API key in "Setup API Key"
3. Verify domain (should auto-fill your WordPress domain)
4. Click **Setup Mailiam**

✓ Plugin will automatically create a public token for your site!

## Step 4: Configure Your First Form (1 minute)

Edit your `mailiam.config.yaml`:

```yaml
domains:
  yoursite.com:  # Replace with your WordPress domain
    sender:
      name: Your Site Name
      email: noreply@yoursite.com

    allowedOrigins:
      - https://yoursite.com
      - https://www.yoursite.com

    forms:
      contact:
        recipient: admin@yoursite.com
        acknowledgment:
          enabled: true
          subject: "Thanks for contacting us!"
```

Deploy:

```bash
mailiam push
```

## Step 5: Add Form to Your Site (1 minute)

1. Edit any page or post
2. Add this shortcode:

```
[mailiam_form id="contact"]
```

3. Publish!

## Done! 🎉

Your contact form is now live with:
- ✓ Reliable email delivery via AWS SES
- ✓ Advanced spam protection
- ✓ Queue-based processing
- ✓ Automatic acknowledgment emails
- ✓ Clean, responsive design

## Test Your Form

1. Visit your page
2. Fill out the form
3. Submit
4. Check your email!

## Next Steps

### Customize Your Form

Change button text:
```
[mailiam_form id="contact" button="Send Message"]
```

Add custom CSS class:
```
[mailiam_form id="contact" class="my-custom-form"]
```

Redirect after submission:
```
[mailiam_form id="contact" redirect="/thank-you"]
```

### Add More Forms

```yaml
# mailiam.config.yaml
domains:
  yoursite.com:
    forms:
      contact:
        recipient: admin@yoursite.com

      demo:
        recipient: sales@yoursite.com

      support:
        recipient: support@yoursite.com
```

Then use:
```
[mailiam_form id="demo"]
[mailiam_form id="support"]
```

### Custom Form Fields

Add to your theme's `functions.php`:

```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'newsletter') {
        return '
            <div class="mailiam-form-row">
                <label>Email <span class="mailiam-required">*</span></label>
                <input type="email" name="email" required class="mailiam-input" />
            </div>
        ';
    }
    return $html;
}, 10, 2);
```

### Style Your Forms

Add to your theme's CSS:

```css
.mailiam-submit-button {
    background: #your-brand-color !important;
}
```

## Troubleshooting

**Form not submitting?**
- Check plugin is configured: Settings > Mailiam
- Verify form ID matches your config
- Check browser console for errors

**Not receiving emails?**
- Verify `mailiam push` was run
- Check recipient email in config
- Run `mailiam logs` to see status

**API key error?**
- Make sure you're using a usage/admin key (mlm_sk_*)
- Public keys (mlm_pk_*) can't be used for setup

## Get Help

- **Documentation:** [docs.mailiam.io](https://docs.mailiam.io)
- **Examples:** See EXAMPLES.md
- **Support:** support@mailiam.io

## What's Next?

Explore advanced features:
- Custom templates
- Email forwarding
- SRS (Sender Rewriting Scheme)
- Form analytics
- Webhook integrations

See [README.md](README.md) for complete documentation.
