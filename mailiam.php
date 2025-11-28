<?php
/**
 * Plugin Name: Mailiam
 * Plugin URI: https://mailiam.io
 * Description: Powerful email forms with built-in spam protection, SRS forwarding, and reliable delivery. No SMTP configuration needed.
 * Version: 1.2.1
 * Author: Mailiam
 * Author URI: https://mailiam.io
 * License: MIT
 * Text Domain: mailiam
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MAILIAM_VERSION', '1.2.1');
define('MAILIAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAILIAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAILIAM_API_URL', 'https://api.mailiam.dev');

// Load required files
require_once MAILIAM_PLUGIN_DIR . 'includes/class-mailiam-api.php';
require_once MAILIAM_PLUGIN_DIR . 'includes/class-mailiam-admin.php';
require_once MAILIAM_PLUGIN_DIR . 'includes/class-mailiam-form.php';
require_once MAILIAM_PLUGIN_DIR . 'includes/class-mailiam-mailer.php';

// Load integrations
if (file_exists(MAILIAM_PLUGIN_DIR . 'includes/integrations/class-mailiam-woocommerce.php')) {
    require_once MAILIAM_PLUGIN_DIR . 'includes/integrations/class-mailiam-woocommerce.php';
}

// Load Plugin Update Checker for GitHub releases
if (file_exists(MAILIAM_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php')) {
    require_once MAILIAM_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

    $mailiam_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/mailiam-io/wordpress-plugin/',
        __FILE__,
        'mailiam'
    );

    // Use the main branch for updates
    $mailiam_update_checker->setBranch('main');
}

/**
 * Main Mailiam plugin class
 */
class Mailiam {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin initialization
        if (is_admin()) {
            new Mailiam_Admin();
        }

        // Frontend initialization
        new Mailiam_Form();

        // Initialize email override if enabled
        $settings = get_option('mailiam_settings', array());
        if (!empty($settings['email_override_enabled']) && !empty($settings['usage_key'])) {
            new Mailiam_Mailer();
        }

        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('mailiam_settings')) {
            add_option('mailiam_settings', array(
                'api_key' => '',
                'public_key' => '',
                'usage_key' => '',
                'domain' => parse_url(get_site_url(), PHP_URL_HOST),
                'success_message' => 'Thank you! Your message has been sent.',
                'error_message' => 'Sorry, there was an error sending your message. Please try again.',
                'email_override_enabled' => false,
            ));
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed (keeping settings for now)
    }
}

// Initialize the plugin
function mailiam_init() {
    return Mailiam::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'mailiam_init');

/**
 * Helper function to get plugin settings
 */
function mailiam_get_settings() {
    return get_option('mailiam_settings', array(
        'api_key' => '',
        'public_key' => '',
        'usage_key' => '',
        'domain' => parse_url(get_site_url(), PHP_URL_HOST),
        'success_message' => 'Thank you! Your message has been sent.',
        'error_message' => 'Sorry, there was an error sending your message. Please try again.',
        'email_override_enabled' => false,
    ));
}

/**
 * Helper function to check if plugin is configured
 */
function mailiam_is_configured() {
    $settings = mailiam_get_settings();
    return !empty($settings['public_key']);
}

/**
 * Helper function to check if transactional emails are enabled
 */
function mailiam_has_transactional() {
    $settings = mailiam_get_settings();
    return !empty($settings['usage_key']);
}

/**
 * Send a transactional email via Mailiam
 *
 * @param array $args Email arguments
 *   Required: to, subject, from
 *   Optional: html, text, template, data, reply_to, cc, bcc
 * @return bool|WP_Error True on success, WP_Error on failure
 *
 * Examples:
 *   mailiam_send_email([
 *       'to' => 'customer@example.com',
 *       'from' => 'orders@yourstore.com',
 *       'subject' => 'Order Confirmation',
 *       'html' => '<h1>Thank you for your order!</h1>'
 *   ]);
 *
 *   mailiam_send_email([
 *       'to' => 'customer@example.com',
 *       'from' => 'orders@yourstore.com',
 *       'subject' => 'Order #{{order_number}}',
 *       'template' => 'order-confirmation',
 *       'data' => ['order_number' => '12345', 'total' => '$99.99']
 *   ]);
 */
function mailiam_send_email($args) {
    // Check if transactional emails are enabled
    if (!mailiam_has_transactional()) {
        return new WP_Error(
            'mailiam_not_configured',
            'Transactional emails not configured. Please add a usage API key in Settings > Mailiam.'
        );
    }

    $settings = mailiam_get_settings();
    $api = new Mailiam_API();

    // Handle template rendering if specified
    if (!empty($args['template']) && !empty($args['data'])) {
        // Simple variable substitution
        if (!empty($args['subject'])) {
            $args['subject'] = mailiam_render_template($args['subject'], $args['data']);
        }
        if (!empty($args['html'])) {
            $args['html'] = mailiam_render_template($args['html'], $args['data']);
        }
        if (!empty($args['text'])) {
            $args['text'] = mailiam_render_template($args['text'], $args['data']);
        }
    }

    // Prepare email data
    $email_data = array(
        'from' => $args['from'],
        'to' => $args['to'],
        'subject' => $args['subject'],
    );

    // Add optional fields
    if (!empty($args['html'])) {
        $email_data['html'] = $args['html'];
    }
    if (!empty($args['text'])) {
        $email_data['text'] = $args['text'];
    }
    if (!empty($args['reply_to'])) {
        $email_data['replyTo'] = $args['reply_to'];
    }
    if (!empty($args['cc'])) {
        $email_data['cc'] = $args['cc'];
    }
    if (!empty($args['bcc'])) {
        $email_data['bcc'] = $args['bcc'];
    }

    // Send via API
    $result = $api->send_transactional_email($settings['usage_key'], $email_data);

    if (is_wp_error($result)) {
        // Log error
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Mailiam send error: ' . $result->get_error_message());
        }
        return $result;
    }

    return true;
}

/**
 * Render template with variable substitution
 *
 * @param string $template Template string with {{variable}} placeholders
 * @param array $data Data to substitute
 * @return string Rendered template
 */
function mailiam_render_template($template, $data) {
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}
