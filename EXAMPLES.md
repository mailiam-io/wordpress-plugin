# Mailiam WordPress Plugin - Examples

This guide shows real-world examples of using Mailiam with WordPress.

## Table of Contents

- [Quick Start Example](#quick-start-example)
- [Contact Form](#contact-form)
- [Newsletter Signup](#newsletter-signup)
- [Demo Request Form](#demo-request-form)
- [Multi-Step Form](#multi-step-form)
- [Custom Styled Form](#custom-styled-form)
- [Page Builder Integration](#page-builder-integration)
- [Advanced Customization](#advanced-customization)

---

## Quick Start Example

**Step 1: Install and configure**

```bash
# In WordPress
1. Install Mailiam plugin
2. Go to Settings > Mailiam
3. Enter API key (get with: mailiam apikeys create)
4. Save settings
```

**Step 2: Configure in Mailiam**

```yaml
# mailiam.config.yaml
domains:
  yoursite.com:
    forms:
      contact:
        recipient: admin@yoursite.com
```

**Step 3: Deploy**

```bash
mailiam push
```

**Step 4: Add to WordPress page**

```
[mailiam_form id="contact"]
```

Done! Your form is live.

---

## Contact Form

### Simple Contact Form

**WordPress shortcode:**
```
[mailiam_form id="contact"]
```

**Mailiam configuration:**
```yaml
domains:
  yoursite.com:
    forms:
      contact:
        recipient: admin@yoursite.com
        acknowledgment:
          enabled: true
          subject: "Thanks for contacting us!"
          template: contact-thanks

templates:
  contact-thanks:
    html: |
      <h1>Thank you for reaching out!</h1>
      <p>We've received your message and will respond within 24 hours.</p>
      <p>Best regards,<br>The Team</p>
    text: |
      Thank you for reaching out!
      We've received your message and will respond within 24 hours.

      Best regards,
      The Team
```

### Contact Form with Custom Button

```
[mailiam_form id="contact" button="Get in Touch" class="contact-section"]
```

### Contact Form with Redirect

```
[mailiam_form id="contact" redirect="/thank-you"]
```

---

## Newsletter Signup

### Simple Email Capture

**Custom form fields (functions.php):**
```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'newsletter') {
        return '
            <div class="mailiam-form-row">
                <label for="email">
                    Email Address <span class="mailiam-required">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    class="mailiam-input"
                    placeholder="your@email.com"
                />
            </div>

            <div class="mailiam-form-row">
                <label>
                    <input type="checkbox" name="marketing_consent" value="yes" required />
                    I agree to receive marketing emails
                </label>
            </div>

            <!-- Honeypot for spam protection -->
            <input type="text" name="pooh-bear" value="" style="display:none !important" />
        ';
    }
    return $html;
}, 10, 2);
```

**WordPress shortcode:**
```
[mailiam_form id="newsletter" button="Subscribe"]
```

**Mailiam configuration:**
```yaml
domains:
  yoursite.com:
    forms:
      newsletter:
        recipient: newsletter@yoursite.com
        acknowledgment:
          enabled: true
          subject: "Welcome to our newsletter!"
          template: newsletter-welcome

templates:
  newsletter-welcome:
    html: |
      <h1>Welcome aboard!</h1>
      <p>You're now subscribed to our newsletter.</p>
      <p>Expect great content every Tuesday.</p>
```

---

## Demo Request Form

### Sales Demo Form

**Custom fields (functions.php):**
```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'demo') {
        return '
            <div class="mailiam-form-row">
                <label>Full Name <span class="mailiam-required">*</span></label>
                <input type="text" name="name" required class="mailiam-input" />
            </div>

            <div class="mailiam-form-row">
                <label>Work Email <span class="mailiam-required">*</span></label>
                <input type="email" name="email" required class="mailiam-input" />
            </div>

            <div class="mailiam-form-row">
                <label>Company</label>
                <input type="text" name="company" class="mailiam-input" />
            </div>

            <div class="mailiam-form-row">
                <label>Company Size</label>
                <select name="company_size" class="mailiam-input">
                    <option value="">Select...</option>
                    <option value="1-10">1-10 employees</option>
                    <option value="11-50">11-50 employees</option>
                    <option value="51-200">51-200 employees</option>
                    <option value="201+">201+ employees</option>
                </select>
            </div>

            <div class="mailiam-form-row">
                <label>How can we help?</label>
                <textarea name="message" rows="4" class="mailiam-textarea"></textarea>
            </div>

            <input type="text" name="pooh-bear" value="" style="display:none !important" />
        ';
    }
    return $html;
}, 10, 2);
```

**Shortcode:**
```
[mailiam_form id="demo" button="Request Demo"]
```

**Configuration:**
```yaml
domains:
  yoursite.com:
    forms:
      demo:
        recipient: sales@yoursite.com
        acknowledgment:
          enabled: true
          subject: "Demo Request Received"
        settings:
          rateLimit: 20  # Lower limit for demo requests
```

---

## Multi-Step Form

Use JavaScript to create a multi-step experience:

**Custom JavaScript (add to theme):**
```javascript
jQuery(document).ready(function($) {
    $('.mailiam-multi-step').each(function() {
        const $form = $(this);
        const $steps = $form.find('.form-step');
        let currentStep = 0;

        // Show first step
        $steps.eq(0).show();

        // Next button
        $form.on('click', '.step-next', function(e) {
            e.preventDefault();
            if (currentStep < $steps.length - 1) {
                $steps.eq(currentStep).hide();
                currentStep++;
                $steps.eq(currentStep).show();
            }
        });

        // Previous button
        $form.on('click', '.step-prev', function(e) {
            e.preventDefault();
            if (currentStep > 0) {
                $steps.eq(currentStep).hide();
                currentStep--;
                $steps.eq(currentStep).show();
            }
        });
    });
});
```

**Custom form (functions.php):**
```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'multi-step') {
        return '
            <div class="form-step">
                <h3>Step 1: Your Information</h3>
                <div class="mailiam-form-row">
                    <label>Name <span class="mailiam-required">*</span></label>
                    <input type="text" name="name" required class="mailiam-input" />
                </div>
                <div class="mailiam-form-row">
                    <label>Email <span class="mailiam-required">*</span></label>
                    <input type="email" name="email" required class="mailiam-input" />
                </div>
                <button type="button" class="step-next">Next →</button>
            </div>

            <div class="form-step" style="display:none;">
                <h3>Step 2: Your Project</h3>
                <div class="mailiam-form-row">
                    <label>Project Type</label>
                    <select name="project_type" class="mailiam-input">
                        <option>Web Development</option>
                        <option>Mobile App</option>
                        <option>Consulting</option>
                    </select>
                </div>
                <div class="mailiam-form-row">
                    <label>Budget Range</label>
                    <select name="budget" class="mailiam-input">
                        <option>$5k - $10k</option>
                        <option>$10k - $25k</option>
                        <option>$25k+</option>
                    </select>
                </div>
                <button type="button" class="step-prev">← Back</button>
                <button type="button" class="step-next">Next →</button>
            </div>

            <div class="form-step" style="display:none;">
                <h3>Step 3: Details</h3>
                <div class="mailiam-form-row">
                    <label>Tell us about your project</label>
                    <textarea name="message" rows="5" required class="mailiam-textarea"></textarea>
                </div>
                <button type="button" class="step-prev">← Back</button>
                <!-- Form will have its own submit button -->
            </div>

            <input type="text" name="pooh-bear" value="" style="display:none !important" />
        ';
    }
    return $html;
}, 10, 2);
```

**Shortcode:**
```
[mailiam_form id="multi-step" button="Submit Project" class="mailiam-multi-step"]
```

---

## Custom Styled Form

### Brand-Matched Design

**Custom CSS (add to theme):**
```css
/* Override Mailiam defaults with your brand */
.my-branded-form .mailiam-form {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.my-branded-form .mailiam-form label {
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.my-branded-form .mailiam-input,
.my-branded-form .mailiam-textarea {
    background: rgba(255,255,255,0.9);
    border: 2px solid transparent;
    border-radius: 8px;
    padding: 1rem;
    font-size: 1rem;
}

.my-branded-form .mailiam-input:focus,
.my-branded-form .mailiam-textarea:focus {
    background: white;
    border-color: #ffd700;
    box-shadow: 0 0 0 3px rgba(255,215,0,0.2);
}

.my-branded-form .mailiam-submit-button {
    background: #ffd700;
    color: #333;
    font-weight: 700;
    padding: 1rem 3rem;
    border-radius: 50px;
    font-size: 1.125rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.my-branded-form .mailiam-submit-button:hover {
    background: #ffed4e;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255,215,0,0.4);
}

.my-branded-form .mailiam-success {
    background: rgba(255,255,255,0.95);
    border: none;
    color: #10b981;
    border-radius: 8px;
    padding: 1.5rem;
}
```

**Shortcode:**
```
[mailiam_form id="contact" class="my-branded-form"]
```

---

## Page Builder Integration

### Elementor

1. Add a "Shortcode" widget
2. Enter: `[mailiam_form id="contact"]`
3. Style using Elementor's styling controls

### Divi Builder

1. Add a "Code" module
2. Insert: `[mailiam_form id="contact"]`
3. Use Divi's design settings to style

### Gutenberg

1. Add a "Shortcode" block
2. Enter: `[mailiam_form id="contact"]`
3. Wrap in Group block for additional styling

### WPBakery

1. Add "Raw HTML" element
2. Enter: `[mailiam_form id="contact"]`
3. Use design options to customize

---

## Advanced Customization

### Conditional Form Fields

```php
add_filter('mailiam_form_fields', function($html, $form_id) {
    if ($form_id === 'support') {
        // Check if user is logged in
        $user = wp_get_current_user();
        $email_field = '';

        if (!is_user_logged_in()) {
            $email_field = '
                <div class="mailiam-form-row">
                    <label>Email <span class="mailiam-required">*</span></label>
                    <input type="email" name="email" required class="mailiam-input" />
                </div>
            ';
        } else {
            // Pre-fill for logged-in users
            $email_field = '
                <input type="hidden" name="email" value="' . esc_attr($user->user_email) . '" />
                <input type="hidden" name="name" value="' . esc_attr($user->display_name) . '" />
            ';
        }

        return '
            ' . $email_field . '
            <div class="mailiam-form-row">
                <label>Issue Type</label>
                <select name="issue_type" class="mailiam-input">
                    <option>Technical Support</option>
                    <option>Billing Question</option>
                    <option>Feature Request</option>
                </select>
            </div>
            <div class="mailiam-form-row">
                <label>Description</label>
                <textarea name="message" rows="6" required class="mailiam-textarea"></textarea>
            </div>
            <input type="text" name="pooh-bear" value="" style="display:none !important" />
        ';
    }
    return $html;
}, 10, 2);
```

### Programmatic Submission

```php
// Send email when user completes action
add_action('user_register', function($user_id) {
    $user = get_userdata($user_id);
    $api = new Mailiam_API();
    $settings = mailiam_get_settings();

    $result = $api->submit_form($settings['domain'], [
        'name' => $user->display_name,
        'email' => $user->user_email,
        'message' => 'New user registration: ' . $user->user_login,
        '_notification_type' => 'user_registration',
    ], $settings['public_key']);

    if (is_wp_error($result)) {
        error_log('Failed to send registration notification: ' . $result->get_error_message());
    }
});
```

### Custom Success Handler

```javascript
// Add to your theme's JavaScript
jQuery(document).ready(function($) {
    $(document).on('mailiam_success', function(e, response, $form) {
        // Custom success logic
        console.log('Form submitted!', response);

        // Track with Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'form_submission', {
                'form_id': $form.data('form-id')
            });
        }

        // Show custom modal, etc.
    });
});
```

---

## Tips & Best Practices

### 1. Always Use Honeypot

Include the honeypot field in custom forms:
```html
<input type="text" name="pooh-bear" value="" style="display:none !important" tabindex="-1" autocomplete="off" />
```

### 2. Rate Limiting

Adjust rate limits based on form type:
```yaml
forms:
  contact:
    settings:
      rateLimit: 50  # Standard contact form

  demo:
    settings:
      rateLimit: 20  # More restrictive for high-value forms

  newsletter:
    settings:
      rateLimit: 100  # Higher for newsletter signups
```

### 3. Acknowledgment Emails

Always send acknowledgments for better UX:
```yaml
forms:
  contact:
    acknowledgment:
      enabled: true
      subject: "We got your message!"
      template: quick-ack
```

### 4. Testing

Test forms before going live:
```bash
# Test form submission
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=mailiam_submit" \
  -d "form_id=contact" \
  -d "form_data[0][name]=email" \
  -d "form_data[0][value]=test@example.com"
```

### 5. Monitor Submissions

Check Mailiam logs regularly:
```bash
mailiam logs --follow
```

---

## Need Help?

- **Documentation:** [docs.mailiam.io](https://docs.mailiam.io)
- **Examples:** This file!
- **Support:** support@mailiam.io
- **Community:** [Discord](https://discord.gg/mailiam)

Happy form building! 🚀
