# Changelog

All notable changes to the Mailiam WordPress plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2024-11-28

### Added
- **Manual Setup Mode** - Use existing API keys instead of creating new ones
- Radio button setup method selection (Automatic vs Manual)
- Manual key entry fields for public key and usage key
- Automatic field toggling with JavaScript
- Key format validation (mlm_pk_* for public, mlm_sk_* for usage)
- API key testing before saving in manual mode
- **Test Email Buttons** - Test your keys directly from the WordPress admin
  - "Test Public Key" button to verify form submission capability
  - "Send Test Email" button to verify transactional email sending
  - Real-time AJAX testing with success/error feedback
  - Test emails sent to admin email address

### Changed
- Refactored `sanitize_settings()` to support both setup modes
- Domain field now auto-populated from site URL in manual mode
- Improved setup instructions and field descriptions

### Fixed
- Prevents duplicate API key creation when users already have keys
- Avoids hitting API key limits unnecessarily during setup
- Better UX for users migrating from CLI to WordPress plugin

### Technical Details
- Backward compatible with existing auto-setup flow
- Manual mode validates key prefixes before testing
- Both modes share the same validation and storage logic
- JavaScript-based UI toggling for clean user experience

## [1.2.0] - 2024-11-28

### Added
- **WordPress Email Override** - Route ALL WordPress emails through Mailiam instead of SMTP
- PHPMailer hook integration (`phpmailer_init`) for email interception
- "WordPress Email Routing" section in admin settings
- Opt-in checkbox to enable email override (disabled by default)
- Graceful fallback to WordPress default email system if Mailiam API fails
- `Mailiam_Mailer` class for handling email interception
- Automatic email data extraction (To, From, CC, BCC, Reply-To, Subject, Body)
- Debug logging when WP_DEBUG is enabled

### Changed
- Plugin version bumped to 1.2.0
- Enhanced admin settings with email routing configuration
- Updated default settings to include `email_override_enabled` flag

### Features
- Replaces WP Mail SMTP functionality
- Handles password resets, user notifications, plugin emails, etc.
- Compatible with all WordPress plugins and themes
- No conflicts with other email plugins
- Safe migration path from WP Mail SMTP

### Known Limitations
- Email attachments not yet supported (sends without attachments, logged as warning)
- Future enhancement: Will add attachment support via base64 encoding

## [1.1.0] - 2024-11-16

### Added
- **Transactional Email Support** - Send programmatic emails for order confirmations, password resets, etc.
- Usage API key field in settings for transactional emails
- `mailiam_send_email()` helper function for sending emails programmatically
- `mailiam_has_transactional()` helper function to check if transactional emails are enabled
- `mailiam_render_template()` function for variable substitution in emails
- `send_transactional_email()` method in API client class
- WooCommerce integration for automatic order emails
  - Order completed emails
  - Order processing emails
  - Order cancelled emails
  - New customer welcome emails
- Template variable support with `{{variable}}` syntax
- Security validation for usage keys (must be mlm_sk_* keys)

### Changed
- Enhanced admin settings page with transactional email section
- Updated documentation with transactional email examples
- Improved settings storage to include usage_key field

### Security
- Usage key validation ensures only server-side keys (mlm_sk_*) can be used
- Clear warnings in admin about keeping usage keys secret
- Separate storage for public tokens (forms) and usage keys (transactional)

## [1.0.0] - 2024-11-16

### Added
- Initial release of Mailiam WordPress plugin
- Core plugin architecture with modular class structure
- Settings page in WordPress admin (Settings > Mailiam)
- API key management with automatic public token generation
- `[mailiam_form]` shortcode for embedding forms
- AJAX-based form submission for smooth UX
- Honeypot spam protection (pooh-bear field)
- WordPress nonce verification for security
- Customizable success and error messages
- Clean, responsive form styling
- Admin interface for plugin configuration
- Support for custom CSS classes on forms
- Support for custom button text
- Support for post-submission redirects
- Form field customization via `mailiam_form_fields` filter
- Comprehensive documentation (README.md, EXAMPLES.md)
- WordPress.org compatible readme.txt
- MIT License

### Security
- Three-tier API key system integration
- Domain-scoped public tokens
- Origin header validation
- Honeypot spam protection
- WordPress nonce verification
- XSS prevention with proper escaping
- SQL injection prevention with WordPress best practices

### Developer Features
- `Mailiam_API` class for API communication
- `Mailiam_Admin` class for settings management
- `Mailiam_Form` class for form handling
- `mailiam_form_fields` filter for customization
- `mailiam_get_settings()` helper function
- `mailiam_is_configured()` helper function
- Well-documented code with inline comments
- Modular architecture for easy extension

### Documentation
- Complete README.md with installation and usage instructions
- EXAMPLES.md with real-world implementation examples
- Inline code documentation
- WordPress.org readme with FAQ section
- API reference documentation

## [Unreleased]

### Planned Features
- Gutenberg block for visual form embedding
- Form submission viewer in WordPress admin
- Email template preview in admin
- Form analytics dashboard
- Multi-language support (i18n)
- Integration with popular page builders
- Form submission export (CSV)
- Custom field types (file upload, etc.)
- Conditional form logic
- Form builder UI (optional alternative to YAML)

---

## Version History

- **1.2.1** - Manual setup mode (use existing API keys)
- **1.2.0** - WordPress email override (replaces WP Mail SMTP)
- **1.1.0** - Transactional emails and WooCommerce integration
- **1.0.0** - Initial release with core functionality
