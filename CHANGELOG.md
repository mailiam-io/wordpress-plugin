# Changelog

All notable changes to the Mailiam WordPress plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

- **1.0.0** - Initial release with core functionality
