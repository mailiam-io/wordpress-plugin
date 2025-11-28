<?php
/**
 * Mailiam Admin Interface
 *
 * Handles WordPress admin settings page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mailiam_Admin {

    /**
     * API client instance
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Mailiam_API();
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'admin_notices'));

        // AJAX handlers for test emails
        add_action('wp_ajax_mailiam_test_public_key', array($this, 'ajax_test_public_key'));
        add_action('wp_ajax_mailiam_test_usage_key', array($this, 'ajax_test_usage_key'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Mailiam Settings',
            'Mailiam',
            'manage_options',
            'mailiam',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('mailiam_settings', 'mailiam_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * Sanitize and save settings
     */
    public function sanitize_settings($input) {
        $settings = mailiam_get_settings();
        $output = $settings;

        $setup_method = isset($input['setup_method']) ? $input['setup_method'] : 'auto';

        // === MANUAL SETUP MODE ===
        if ($setup_method === 'manual') {
            $manual_public_key = isset($input['manual_public_key']) ? sanitize_text_field($input['manual_public_key']) : '';
            $manual_usage_key = isset($input['manual_usage_key']) ? sanitize_text_field($input['manual_usage_key']) : '';

            // Validate public key format
            if (!empty($manual_public_key)) {
                if (strpos($manual_public_key, 'mlm_pk_') !== 0) {
                    add_settings_error(
                        'mailiam_settings',
                        'invalid_public_key',
                        'Public key must start with mlm_pk_',
                        'error'
                    );
                    return $settings;
                }

                // Test the public key
                $test_result = $this->api->test_api_key($manual_public_key);
                if (is_wp_error($test_result)) {
                    add_settings_error(
                        'mailiam_settings',
                        'invalid_public_key',
                        'Invalid public key: ' . $test_result->get_error_message(),
                        'error'
                    );
                    return $settings;
                }

                $output['public_key'] = $manual_public_key;
                $output['domain'] = parse_url(get_site_url(), PHP_URL_HOST);
            }

            // Validate usage key (optional)
            if (!empty($manual_usage_key)) {
                if (strpos($manual_usage_key, 'mlm_sk_') !== 0) {
                    add_settings_error(
                        'mailiam_settings',
                        'invalid_usage_key',
                        'Usage key must start with mlm_sk_',
                        'error'
                    );
                    return $settings;
                }

                // Test the usage key
                $test_result = $this->api->test_api_key($manual_usage_key);
                if (is_wp_error($test_result)) {
                    add_settings_error(
                        'mailiam_settings',
                        'invalid_usage_key',
                        'Invalid usage key: ' . $test_result->get_error_message(),
                        'error'
                    );
                    return $settings;
                }

                $output['usage_key'] = $manual_usage_key;
                $output['api_key'] = $manual_usage_key; // Use usage key as api_key
            }

            add_settings_error(
                'mailiam_settings',
                'manual_setup_success',
                'Keys configured successfully! Your WordPress site is now connected to Mailiam.',
                'success'
            );

            return $output;
        }

        // === AUTO SETUP MODE (existing logic) ===
        if (isset($input['setup_api_key']) && !empty($input['setup_api_key'])) {
            $setup_key = sanitize_text_field($input['setup_api_key']);

            // Test the API key
            $test_result = $this->api->test_api_key($setup_key);

            if (is_wp_error($test_result)) {
                add_settings_error(
                    'mailiam_settings',
                    'invalid_api_key',
                    'Invalid API key: ' . $test_result->get_error_message(),
                    'error'
                );
                return $settings;
            }

            // Create public key for this domain
            $domain = parse_url(get_site_url(), PHP_URL_HOST);
            $result = $this->api->create_public_key($setup_key, $domain);

            if (is_wp_error($result)) {
                add_settings_error(
                    'mailiam_settings',
                    'public_key_error',
                    'Failed to create public key: ' . $result->get_error_message(),
                    'error'
                );
                return $settings;
            }

            // Save the public key and other settings
            $output['api_key'] = $setup_key;
            $output['public_key'] = $result['apiKey']['key'];
            $output['public_key_id'] = $result['apiKey']['keyId'];
            $output['domain'] = $domain;

            add_settings_error(
                'mailiam_settings',
                'setup_success',
                'Successfully configured Mailiam! Your public key has been created.',
                'success'
            );
        }

        // Update custom messages if provided
        if (isset($input['success_message'])) {
            $output['success_message'] = sanitize_text_field($input['success_message']);
        }

        if (isset($input['error_message'])) {
            $output['error_message'] = sanitize_text_field($input['error_message']);
        }

        // Handle usage key for transactional emails (optional)
        if (isset($input['usage_key'])) {
            $usage_key = sanitize_text_field($input['usage_key']);

            if (!empty($usage_key)) {
                // Validate it's a usage/admin key
                if (strpos($usage_key, 'mlm_sk_') !== 0) {
                    add_settings_error(
                        'mailiam_settings',
                        'invalid_usage_key',
                        'Usage key must be a server-side key (mlm_sk_*). Public keys (mlm_pk_*) cannot be used for transactional emails.',
                        'error'
                    );
                } else {
                    // Test the key
                    $test_result = $this->api->test_api_key($usage_key);

                    if (is_wp_error($test_result)) {
                        add_settings_error(
                            'mailiam_settings',
                            'invalid_usage_key',
                            'Invalid usage key: ' . $test_result->get_error_message(),
                            'error'
                        );
                    } else {
                        $output['usage_key'] = $usage_key;
                        add_settings_error(
                            'mailiam_settings',
                            'usage_key_success',
                            'Usage key saved! You can now send transactional emails programmatically.',
                            'success'
                        );
                    }
                }
            } else {
                // Remove usage key if emptied
                $output['usage_key'] = '';
            }
        }

        // Handle email override toggle
        // Checkboxes only send value when checked, so we need to handle both states
        $output['email_override_enabled'] = isset($input['email_override_enabled']) && $input['email_override_enabled'] == '1';

        // Handle public key regeneration
        if (isset($input['regenerate_public_key']) && !empty($output['api_key'])) {
            // Delete old key if we have the ID
            if (!empty($output['public_key_id'])) {
                $this->api->delete_api_key($output['api_key'], $output['public_key_id']);
            }

            // Create new public key
            $result = $this->api->create_public_key($output['api_key'], $output['domain']);

            if (!is_wp_error($result)) {
                $output['public_key'] = $result['apiKey']['key'];
                $output['public_key_id'] = $result['apiKey']['keyId'];

                add_settings_error(
                    'mailiam_settings',
                    'regenerate_success',
                    'Public key regenerated successfully!',
                    'success'
                );
            }
        }

        return $output;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_mailiam') {
            return;
        }

        wp_enqueue_style(
            'mailiam-admin',
            MAILIAM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MAILIAM_VERSION
        );
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        settings_errors('mailiam_settings');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = mailiam_get_settings();
        $is_configured = !empty($settings['public_key']);
        ?>
        <div class="wrap">
            <h1>Mailiam Settings</h1>

            <p>Configure Mailiam to power your WordPress forms with reliable email delivery, spam protection, and SRS forwarding.</p>

            <form method="post" action="options.php">
                <?php settings_fields('mailiam_settings'); ?>

                <table class="form-table">
                    <?php if (!$is_configured) : ?>
                        <tr>
                            <th scope="row">Setup Method</th>
                            <td>
                                <label>
                                    <input type="radio" name="mailiam_settings[setup_method]" value="auto" checked />
                                    <strong>Automatic</strong> - Create new public key for this domain
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="mailiam_settings[setup_method]" value="manual" />
                                    <strong>Manual</strong> - Use existing API keys
                                </label>
                                <p class="description">
                                    Choose "Manual" if you already have API keys and want to avoid creating duplicates.
                                </p>
                            </td>
                        </tr>

                        <!-- Automatic Setup Fields -->
                        <tr class="auto-setup-field">
                            <th scope="row">
                                <label for="setup_api_key">Setup API Key</label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="setup_api_key"
                                    name="mailiam_settings[setup_api_key]"
                                    class="regular-text"
                                    placeholder="mlm_sk_..."
                                />
                                <p class="description">
                                    Admin or usage key (mlm_sk_*) - will be used to create a domain-scoped public key.
                                    <br><strong>Get your API key:</strong> Run <code>mailiam keys create</code> in your terminal.
                                </p>
                            </td>
                        </tr>

                        <!-- Manual Setup Fields -->
                        <tr class="manual-setup-field" style="display:none;">
                            <th scope="row">
                                <label for="manual_public_key">Public Key</label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="manual_public_key"
                                    name="mailiam_settings[manual_public_key]"
                                    class="regular-text"
                                    placeholder="mlm_pk_..."
                                />
                                <p class="description">
                                    Public key (mlm_pk_*) for contact forms. Must be scoped to your domain.
                                </p>
                            </td>
                        </tr>

                        <tr class="manual-setup-field" style="display:none;">
                            <th scope="row">
                                <label for="manual_usage_key">Usage Key (Optional)</label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="manual_usage_key"
                                    name="mailiam_settings[manual_usage_key]"
                                    class="regular-text"
                                    placeholder="mlm_sk_..."
                                />
                                <p class="description">
                                    Usage key (mlm_sk_*) for transactional emails and wp_mail() override.
                                </p>
                            </td>
                        </tr>

                        <!-- Domain Field (shared by both modes) -->
                        <tr>
                            <th scope="row">
                                <label for="domain">Domain</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="domain"
                                    name="mailiam_settings[domain]"
                                    class="regular-text"
                                    value="<?php echo esc_attr($settings['domain']); ?>"
                                />
                                <p class="description">
                                    The domain configured in your Mailiam account (e.g., example.com).
                                </p>
                            </td>
                        </tr>

                        <!-- JavaScript to toggle fields -->
                        <script>
                        jQuery(function($) {
                            $('input[name="mailiam_settings[setup_method]"]').on('change', function() {
                                if ($(this).val() === 'auto') {
                                    $('.auto-setup-field').show();
                                    $('.manual-setup-field').hide();
                                } else {
                                    $('.auto-setup-field').hide();
                                    $('.manual-setup-field').show();
                                }
                            });
                        });
                        </script>
                    <?php else : ?>
                        <tr>
                            <th scope="row">Status</th>
                            <td>
                                <span style="color: #46b450; font-weight: bold;">✓ Configured</span>
                                <p class="description">
                                    Your WordPress site is connected to Mailiam.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Domain</th>
                            <td>
                                <code><?php echo esc_html($settings['domain']); ?></code>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Public Key</th>
                            <td>
                                <code style="background: #f0f0f0; padding: 5px; display: inline-block; max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html($settings['public_key']); ?>
                                </code>
                                <p class="description">
                                    This public key is safe to use in your forms. It's domain-scoped to <strong><?php echo esc_html($settings['domain']); ?></strong>.
                                </p>
                                <button type="button" id="test_public_key" class="button button-secondary" style="margin-top: 10px;">
                                    Test Public Key
                                </button>
                                <span id="test_public_key_result" style="margin-left: 10px;"></span>
                                <p class="description" style="margin-top: 10px;">
                                    <label>
                                        <input type="checkbox" name="mailiam_settings[regenerate_public_key]" value="1" />
                                        Regenerate public key (useful if compromised)
                                    </label>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($is_configured) : ?>
                        <tr>
                            <th colspan="2" style="padding-top: 2rem; padding-bottom: 1rem;">
                                <h3 style="margin: 0;">Transactional Emails (Optional)</h3>
                                <p style="font-weight: normal; color: #666; margin: 0.5rem 0 0 0;">
                                    For programmatic emails like order confirmations, password resets, etc.
                                </p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="usage_key">Usage API Key</label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="usage_key"
                                    name="mailiam_settings[usage_key]"
                                    class="regular-text"
                                    value="<?php echo esc_attr(!empty($settings['usage_key']) ? $settings['usage_key'] : ''); ?>"
                                    placeholder="mlm_sk_..."
                                />
                                <p class="description">
                                    <strong>Optional:</strong> Server-side API key (mlm_sk_*) for sending transactional emails programmatically.
                                    <br><span style="color: #d63638;">⚠️ Keep this secret! Never expose in frontend code.</span>
                                    <br>
                                    <?php if (!empty($settings['usage_key'])) : ?>
                                        <span style="color: #46b450;">✓ Configured - You can use <code>mailiam_send_email()</code> function</span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($settings['usage_key'])) : ?>
                                    <button type="button" id="test_usage_key" class="button button-secondary" style="margin-top: 10px;">
                                        Send Test Email
                                    </button>
                                    <span id="test_usage_key_result" style="margin-left: 10px;"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($is_configured && !empty($settings['usage_key'])) : ?>
                        <tr>
                            <th colspan="2" style="padding-top: 2rem; padding-bottom: 1rem;">
                                <h3 style="margin: 0;">WordPress Email Routing</h3>
                                <p style="font-weight: normal; color: #666; margin: 0.5rem 0 0 0;">
                                    Route all WordPress emails through Mailiam instead of SMTP.
                                </p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_override_enabled">Use Mailiam for All Emails</label>
                            </th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        id="email_override_enabled"
                                        name="mailiam_settings[email_override_enabled]"
                                        value="1"
                                        <?php checked(!empty($settings['email_override_enabled']), true); ?>
                                    />
                                    Route all WordPress emails through Mailiam
                                </label>
                                <p class="description">
                                    When enabled, ALL emails sent by WordPress (password resets, notifications, plugin emails, etc.) will be sent via Mailiam instead of your SMTP settings.
                                </p>
                                <p class="description" style="margin-top: 0.5rem;">
                                    <strong>Requirements:</strong>
                                </p>
                                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                    <li>Usage API Key configured (above) ✓</li>
                                    <li>Verified sender domain in Mailiam</li>
                                </ul>
                                <p class="description" style="background: #e7f5fe; padding: 10px; border-left: 3px solid #0073aa;">
                                    <strong>ℹ️ Fallback Protection:</strong> If Mailiam is unavailable, emails will automatically fall back to WordPress default email system. This ensures your emails always send.
                                </p>
                                <?php if (!empty($settings['email_override_enabled'])) : ?>
                                    <p style="background: #d4edda; padding: 10px; border-left: 3px solid #46b450; margin-top: 0.5rem;">
                                        <strong>✓ Active:</strong> All WordPress emails are being sent through Mailiam.
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <th colspan="2" style="padding-top: 2rem; padding-bottom: 1rem;">
                            <h3 style="margin: 0;">Form Messages</h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="success_message">Success Message</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="success_message"
                                name="mailiam_settings[success_message]"
                                class="large-text"
                                value="<?php echo esc_attr($settings['success_message']); ?>"
                            />
                            <p class="description">
                                Message shown when form submission succeeds.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="error_message">Error Message</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="error_message"
                                name="mailiam_settings[error_message]"
                                class="large-text"
                                value="<?php echo esc_attr($settings['error_message']); ?>"
                            />
                            <p class="description">
                                Message shown when form submission fails.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button($is_configured ? 'Save Settings' : 'Setup Mailiam'); ?>
            </form>

            <?php if ($is_configured) : ?>
                <hr>
                <h2>How to Use</h2>
                <p>Add a form to any page or post using the shortcode:</p>
                <pre style="background: #f0f0f0; padding: 15px; border-radius: 4px;"><code>[mailiam_form id="contact"]</code></pre>

                <p><strong>Configure your forms in Mailiam:</strong></p>
                <ol>
                    <li>Edit your <code>mailiam.config.yaml</code> file</li>
                    <li>Add form configuration under <code>domains.<?php echo esc_html($settings['domain']); ?>.forms</code></li>
                    <li>Run <code>mailiam push</code> to deploy</li>
                </ol>

                <p><strong>Example configuration:</strong></p>
                <pre style="background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>domains:
  <?php echo esc_html($settings['domain']); ?>:
    forms:
      contact:
        recipient: admin@<?php echo esc_html($settings['domain']); ?>
        acknowledgment:
          enabled: true
          subject: "Thanks for contacting us!"</code></pre>

                <p>
                    <a href="https://docs.mailiam.io" target="_blank" class="button">View Documentation</a>
                </p>
            <?php endif; ?>

            <!-- JavaScript for test email buttons -->
            <script type="text/javascript">
            jQuery(function($) {
                // Test public key button
                $('#test_public_key').on('click', function() {
                    var $button = $(this);
                    var $result = $('#test_public_key_result');

                    $button.prop('disabled', true).text('Testing...');
                    $result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mailiam_test_public_key',
                            nonce: '<?php echo wp_create_nonce('mailiam_test_public_key'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span style="color: #46b450;">✓ ' + response.data + '</span>');
                            } else {
                                $result.html('<span style="color: #d63638;">✗ ' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span style="color: #d63638;">✗ Network error</span>');
                        },
                        complete: function() {
                            $button.prop('disabled', false).text('Test Public Key');
                        }
                    });
                });

                // Test usage key button (send test email)
                $('#test_usage_key').on('click', function() {
                    var $button = $(this);
                    var $result = $('#test_usage_key_result');

                    $button.prop('disabled', true).text('Sending...');
                    $result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mailiam_test_usage_key',
                            nonce: '<?php echo wp_create_nonce('mailiam_test_usage_key'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span style="color: #46b450;">✓ ' + response.data + '</span>');
                            } else {
                                $result.html('<span style="color: #d63638;">✗ ' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span style="color: #d63638;">✗ Network error</span>');
                        },
                        complete: function() {
                            $button.prop('disabled', false).text('Send Test Email');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler for testing public key
     */
    public function ajax_test_public_key() {
        // Security check
        check_ajax_referer('mailiam_test_public_key', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $settings = mailiam_get_settings();
        $public_key = $settings['public_key'];

        if (empty($public_key)) {
            wp_send_json_error('No public key configured');
            return;
        }

        // Test the public key by calling the API
        $test_result = $this->api->test_api_key($public_key);

        if (is_wp_error($test_result)) {
            wp_send_json_error($test_result->get_error_message());
            return;
        }

        wp_send_json_success('Public key is valid! Form submissions will work correctly.');
    }

    /**
     * AJAX handler for testing usage key by sending a test email
     */
    public function ajax_test_usage_key() {
        // Security check
        check_ajax_referer('mailiam_test_usage_key', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $settings = mailiam_get_settings();
        $usage_key = $settings['usage_key'];

        if (empty($usage_key)) {
            wp_send_json_error('No usage key configured');
            return;
        }

        // Get the admin email for the test
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $domain = $settings['domain'];

        // Send a test email
        $email_data = array(
            'from' => 'noreply@' . $domain,
            'to' => $admin_email,
            'subject' => 'Mailiam Test Email - ' . $site_name,
            'html' => '<h1>Test Email Successful!</h1><p>This is a test email from your WordPress site (<strong>' . $site_name . '</strong>) using Mailiam.</p><p>Your usage key is working correctly and you can now send transactional emails.</p>',
            'text' => 'Test Email Successful! This is a test email from your WordPress site (' . $site_name . ') using Mailiam. Your usage key is working correctly and you can now send transactional emails.'
        );

        $result = $this->api->send_transactional_email($usage_key, $email_data);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to send test email: ' . $result->get_error_message());
            return;
        }

        wp_send_json_success('Test email sent successfully to ' . $admin_email . '! Check your inbox.');
    }
}
