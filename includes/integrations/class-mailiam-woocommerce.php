<?php
/**
 * Mailiam WooCommerce Integration
 *
 * Optional integration for sending order emails via Mailiam
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mailiam_WooCommerce {

    /**
     * Constructor
     */
    public function __construct() {
        // Only init if WooCommerce is active and transactional emails are enabled
        if (!class_exists('WooCommerce') || !mailiam_has_transactional()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Order status hooks
        add_action('woocommerce_order_status_completed', array($this, 'send_order_completed_email'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'send_order_processing_email'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'send_order_cancelled_email'), 10, 1);

        // New customer registration
        add_action('woocommerce_created_customer', array($this, 'send_welcome_email'), 10, 3);
    }

    /**
     * Send order completed email
     *
     * @param int $order_id Order ID
     */
    public function send_order_completed_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $settings = mailiam_get_settings();

        mailiam_send_email(array(
            'to' => $order->get_billing_email(),
            'from' => get_option('woocommerce_email_from_address', 'noreply@' . $settings['domain']),
            'subject' => sprintf('Your order #%s is complete', $order->get_order_number()),
            'html' => $this->get_order_completed_html($order),
            'text' => $this->get_order_completed_text($order),
        ));
    }

    /**
     * Send order processing email
     *
     * @param int $order_id Order ID
     */
    public function send_order_processing_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $settings = mailiam_get_settings();

        mailiam_send_email(array(
            'to' => $order->get_billing_email(),
            'from' => get_option('woocommerce_email_from_address', 'noreply@' . $settings['domain']),
            'subject' => sprintf('Order #%s received', $order->get_order_number()),
            'html' => $this->get_order_processing_html($order),
            'text' => $this->get_order_processing_text($order),
        ));
    }

    /**
     * Send order cancelled email
     *
     * @param int $order_id Order ID
     */
    public function send_order_cancelled_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $settings = mailiam_get_settings();

        mailiam_send_email(array(
            'to' => $order->get_billing_email(),
            'from' => get_option('woocommerce_email_from_address', 'noreply@' . $settings['domain']),
            'subject' => sprintf('Order #%s cancelled', $order->get_order_number()),
            'html' => $this->get_order_cancelled_html($order),
            'text' => $this->get_order_cancelled_text($order),
        ));
    }

    /**
     * Send welcome email to new customer
     *
     * @param int $customer_id Customer ID
     * @param array $new_customer_data Customer data
     * @param bool $password_generated Whether password was auto-generated
     */
    public function send_welcome_email($customer_id, $new_customer_data, $password_generated) {
        $customer = new WP_User($customer_id);
        $settings = mailiam_get_settings();

        mailiam_send_email(array(
            'to' => $customer->user_email,
            'from' => get_option('woocommerce_email_from_address', 'noreply@' . $settings['domain']),
            'subject' => 'Welcome to ' . get_bloginfo('name'),
            'html' => $this->get_welcome_html($customer),
            'text' => $this->get_welcome_text($customer),
        ));
    }

    /**
     * Get order completed HTML
     *
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private function get_order_completed_html($order) {
        ob_start();
        ?>
        <h1>Order Complete!</h1>
        <p>Hi <?php echo esc_html($order->get_billing_first_name()); ?>,</p>
        <p>Your order #<?php echo esc_html($order->get_order_number()); ?> has been completed.</p>

        <h2>Order Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Product</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Total</th>
            </tr>
            <?php foreach ($order->get_items() as $item) : ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($item->get_name()); ?> × <?php echo esc_html($item->get_quantity()); ?></td>
                    <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Total</td>
                <td style="text-align: right; padding: 8px; font-weight: bold;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
            </tr>
        </table>

        <p>Thank you for your business!</p>
        <?php
        return ob_get_clean();
    }

    /**
     * Get order completed plain text
     *
     * @param WC_Order $order Order object
     * @return string Plain text content
     */
    private function get_order_completed_text($order) {
        $text = "Order Complete!\n\n";
        $text .= "Hi " . $order->get_billing_first_name() . ",\n\n";
        $text .= "Your order #" . $order->get_order_number() . " has been completed.\n\n";
        $text .= "Order Details:\n";

        foreach ($order->get_items() as $item) {
            $text .= "- " . $item->get_name() . " × " . $item->get_quantity() . ": " . strip_tags($order->get_formatted_line_subtotal($item)) . "\n";
        }

        $text .= "\nTotal: " . strip_tags($order->get_formatted_order_total()) . "\n\n";
        $text .= "Thank you for your business!";

        return $text;
    }

    /**
     * Get order processing HTML
     *
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private function get_order_processing_html($order) {
        ob_start();
        ?>
        <h1>Order Received</h1>
        <p>Hi <?php echo esc_html($order->get_billing_first_name()); ?>,</p>
        <p>We've received your order #<?php echo esc_html($order->get_order_number()); ?> and it's being processed.</p>
        <p>We'll send you another email when your order ships.</p>
        <p>Thank you!</p>
        <?php
        return ob_get_clean();
    }

    /**
     * Get order processing plain text
     *
     * @param WC_Order $order Order object
     * @return string Plain text content
     */
    private function get_order_processing_text($order) {
        return "Order Received\n\n" .
               "Hi " . $order->get_billing_first_name() . ",\n\n" .
               "We've received your order #" . $order->get_order_number() . " and it's being processed.\n\n" .
               "We'll send you another email when your order ships.\n\n" .
               "Thank you!";
    }

    /**
     * Get order cancelled HTML
     *
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private function get_order_cancelled_html($order) {
        ob_start();
        ?>
        <h1>Order Cancelled</h1>
        <p>Hi <?php echo esc_html($order->get_billing_first_name()); ?>,</p>
        <p>Your order #<?php echo esc_html($order->get_order_number()); ?> has been cancelled.</p>
        <p>If you have any questions, please contact us.</p>
        <?php
        return ob_get_clean();
    }

    /**
     * Get order cancelled plain text
     *
     * @param WC_Order $order Order object
     * @return string Plain text content
     */
    private function get_order_cancelled_text($order) {
        return "Order Cancelled\n\n" .
               "Hi " . $order->get_billing_first_name() . ",\n\n" .
               "Your order #" . $order->get_order_number() . " has been cancelled.\n\n" .
               "If you have any questions, please contact us.";
    }

    /**
     * Get welcome email HTML
     *
     * @param WP_User $customer Customer object
     * @return string HTML content
     */
    private function get_welcome_html($customer) {
        ob_start();
        ?>
        <h1>Welcome to <?php echo esc_html(get_bloginfo('name')); ?>!</h1>
        <p>Hi <?php echo esc_html($customer->display_name); ?>,</p>
        <p>Thank you for creating an account with us!</p>
        <p>You can now log in at: <a href="<?php echo esc_url(wp_login_url()); ?>"><?php echo esc_url(wp_login_url()); ?></a></p>
        <p>Happy shopping!</p>
        <?php
        return ob_get_clean();
    }

    /**
     * Get welcome email plain text
     *
     * @param WP_User $customer Customer object
     * @return string Plain text content
     */
    private function get_welcome_text($customer) {
        return "Welcome to " . get_bloginfo('name') . "!\n\n" .
               "Hi " . $customer->display_name . ",\n\n" .
               "Thank you for creating an account with us!\n\n" .
               "You can now log in at: " . wp_login_url() . "\n\n" .
               "Happy shopping!";
    }
}

// Initialize WooCommerce integration if active
if (class_exists('WooCommerce') && mailiam_has_transactional()) {
    new Mailiam_WooCommerce();
}
