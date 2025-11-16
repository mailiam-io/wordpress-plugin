<?php
/**
 * Mailiam Form Handler
 *
 * Handles form rendering and submission
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mailiam_Form {

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
        add_shortcode('mailiam_form', array($this, 'render_form_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_mailiam_submit', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_mailiam_submit', array($this, 'handle_ajax_submission'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue if Mailiam is configured
        if (!mailiam_is_configured()) {
            return;
        }

        wp_enqueue_style(
            'mailiam-forms',
            MAILIAM_PLUGIN_URL . 'assets/css/form.css',
            array(),
            MAILIAM_VERSION
        );

        wp_enqueue_script(
            'mailiam-forms',
            MAILIAM_PLUGIN_URL . 'assets/js/form.js',
            array('jquery'),
            MAILIAM_VERSION,
            true
        );

        // Pass settings to JavaScript
        $settings = mailiam_get_settings();
        wp_localize_script('mailiam-forms', 'mailiamSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mailiam_submit'),
            'successMessage' => $settings['success_message'],
            'errorMessage' => $settings['error_message'],
        ));
    }

    /**
     * Render form shortcode
     *
     * Usage: [mailiam_form id="contact"]
     * Optional: [mailiam_form id="contact" class="my-custom-class" button="Send Message"]
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render_form_shortcode($atts) {
        // Check if configured
        if (!mailiam_is_configured()) {
            if (current_user_can('manage_options')) {
                return '<div class="mailiam-notice mailiam-error">
                    <p>Mailiam is not configured. Please <a href="' . admin_url('options-general.php?page=mailiam') . '">configure it here</a>.</p>
                </div>';
            }
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 'contact',
            'class' => '',
            'button' => 'Send Message',
            'redirect' => '',
        ), $atts, 'mailiam_form');

        $form_id = sanitize_key($atts['id']);
        $custom_class = sanitize_html_class($atts['class']);
        $button_text = esc_html($atts['button']);
        $redirect = esc_url($atts['redirect']);

        // Generate unique form instance ID
        $instance_id = 'mailiam-form-' . $form_id . '-' . uniqid();

        ob_start();
        ?>
        <div class="mailiam-form-wrapper <?php echo esc_attr($custom_class); ?>" id="<?php echo esc_attr($instance_id); ?>">
            <form class="mailiam-form" data-form-id="<?php echo esc_attr($form_id); ?>" data-redirect="<?php echo esc_attr($redirect); ?>">
                <?php
                // Apply filter to allow custom form fields
                $fields = apply_filters('mailiam_form_fields', $this->get_default_fields($form_id), $form_id);
                echo $fields;
                ?>

                <div class="mailiam-form-row mailiam-submit-row">
                    <button type="submit" class="mailiam-submit-button">
                        <?php echo $button_text; ?>
                    </button>
                </div>

                <div class="mailiam-message" style="display: none;"></div>

                <?php wp_nonce_field('mailiam_submit', 'mailiam_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get default form fields
     *
     * @param string $form_id Form identifier
     * @return string HTML for form fields
     */
    private function get_default_fields($form_id) {
        ob_start();
        ?>
        <div class="mailiam-form-row">
            <label for="mailiam-name-<?php echo esc_attr($form_id); ?>">
                Name <span class="mailiam-required">*</span>
            </label>
            <input
                type="text"
                id="mailiam-name-<?php echo esc_attr($form_id); ?>"
                name="name"
                required
                class="mailiam-input"
            />
        </div>

        <div class="mailiam-form-row">
            <label for="mailiam-email-<?php echo esc_attr($form_id); ?>">
                Email <span class="mailiam-required">*</span>
            </label>
            <input
                type="email"
                id="mailiam-email-<?php echo esc_attr($form_id); ?>"
                name="email"
                required
                class="mailiam-input"
            />
        </div>

        <div class="mailiam-form-row">
            <label for="mailiam-message-<?php echo esc_attr($form_id); ?>">
                Message <span class="mailiam-required">*</span>
            </label>
            <textarea
                id="mailiam-message-<?php echo esc_attr($form_id); ?>"
                name="message"
                rows="5"
                required
                class="mailiam-textarea"
            ></textarea>
        </div>

        <!-- Honeypot field for spam protection -->
        <input type="text" name="pooh-bear" value="" style="display:none !important" tabindex="-1" autocomplete="off" />
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX form submission
     */
    public function handle_ajax_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mailiam_submit')) {
            wp_send_json_error(array(
                'message' => 'Security verification failed.',
            ), 403);
        }

        // Get settings
        $settings = mailiam_get_settings();

        if (empty($settings['public_key']) || empty($settings['domain'])) {
            wp_send_json_error(array(
                'message' => 'Mailiam is not properly configured.',
            ), 500);
        }

        // Get form data
        $form_id = isset($_POST['form_id']) ? sanitize_key($_POST['form_id']) : '';
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        // Sanitize form data
        $sanitized_data = array();
        foreach ($form_data as $field) {
            $name = sanitize_key($field['name']);
            $value = sanitize_text_field($field['value']);

            // Skip empty honeypot field
            if ($name === 'pooh-bear' && empty($value)) {
                continue;
            }

            // Honeypot triggered - likely spam
            if ($name === 'pooh-bear' && !empty($value)) {
                wp_send_json_error(array(
                    'message' => 'Spam detected.',
                ), 400);
            }

            $sanitized_data[$name] = $value;
        }

        // Add form context
        $sanitized_data['_form_id'] = $form_id;
        $sanitized_data['_source'] = 'wordpress';
        $sanitized_data['_site_url'] = get_site_url();

        // Submit to Mailiam API
        $result = $this->api->submit_form(
            $settings['domain'],
            $sanitized_data,
            $settings['public_key']
        );

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $settings['error_message'],
                'details' => $result->get_error_message(),
            ), 500);
        }

        // Success response
        wp_send_json_success(array(
            'message' => $settings['success_message'],
        ));
    }
}
