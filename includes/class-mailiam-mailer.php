<?php
/**
 * Mailiam Email Mailer
 *
 * Intercepts WordPress emails via phpmailer_init hook and routes them
 * through Mailiam's email infrastructure instead of SMTP.
 *
 * @package Mailiam
 * @version 1.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mailiam_Mailer {

    /**
     * API client instance
     */
    private $api;

    /**
     * Plugin settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Mailiam_API();
        $this->settings = get_option('mailiam_settings', array());
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into phpmailer_init with priority 10
        add_action('phpmailer_init', array($this, 'intercept_email'), 10, 1);
    }

    /**
     * Intercept email before PHPMailer sends
     *
     * @param PHPMailer $phpmailer PHPMailer instance
     */
    public function intercept_email($phpmailer) {
        // Safety check: Only intercept if we have a usage key
        if (empty($this->settings['usage_key'])) {
            return; // Let WordPress handle it
        }

        // Extract email data from PHPMailer object
        $email_data = $this->extract_email_data($phpmailer);

        // Send via Mailiam
        $result = $this->send_via_mailiam($email_data);

        // Success: Prevent PHPMailer from sending
        if ($result === true) {
            $this->prevent_smtp_send($phpmailer);

            // Log success if WP_DEBUG enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Mailiam: Email sent successfully via API - Subject: ' . $email_data['subject']);
            }
        }
        // Failure: Let PHPMailer continue (fallback)
        else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Mailiam: API failed, falling back to WordPress: ' .
                    (is_wp_error($result) ? $result->get_error_message() : 'Unknown error'));
            }
            // Don't modify PHPMailer - WordPress will send normally
        }
    }

    /**
     * Extract email data from PHPMailer object
     *
     * @param PHPMailer $phpmailer PHPMailer instance
     * @return array Email data array
     */
    private function extract_email_data($phpmailer) {
        $email_data = array(
            'subject' => $phpmailer->Subject,
        );

        // From address
        $email_data['from'] = $phpmailer->From;
        if (!empty($phpmailer->FromName)) {
            $email_data['from'] = $phpmailer->FromName . ' <' . $phpmailer->From . '>';
        }

        // To recipients (primary recipient)
        if (!empty($phpmailer->getToAddresses())) {
            $to_addresses = $phpmailer->getToAddresses();
            $email_data['to'] = $to_addresses[0][0]; // First To address
        }

        // CC recipients
        if (!empty($phpmailer->getCcAddresses())) {
            $cc_addresses = $phpmailer->getCcAddresses();
            $email_data['cc'] = array_map(function($addr) {
                return $addr[0];
            }, $cc_addresses);
        }

        // BCC recipients
        if (!empty($phpmailer->getBccAddresses())) {
            $bcc_addresses = $phpmailer->getBccAddresses();
            $email_data['bcc'] = array_map(function($addr) {
                return $addr[0];
            }, $bcc_addresses);
        }

        // Reply-To
        if (!empty($phpmailer->getReplyToAddresses())) {
            $reply_to = $phpmailer->getReplyToAddresses();
            $email_data['replyTo'] = $reply_to[0][0];
        }

        // Body content (HTML or plain text)
        if ($phpmailer->ContentType === 'text/html') {
            $email_data['html'] = $phpmailer->Body;
            if (!empty($phpmailer->AltBody)) {
                $email_data['text'] = $phpmailer->AltBody;
            }
        } else {
            $email_data['text'] = $phpmailer->Body;
        }

        // Attachments handling
        if (!empty($phpmailer->getAttachments())) {
            // For MVP: Log warning, don't send attachments
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Mailiam: Email has ' . count($phpmailer->getAttachments()) .
                    ' attachments (not yet supported, sending without)');
            }
            // Future: Add attachment support
        }

        return $email_data;
    }

    /**
     * Send email via Mailiam API
     *
     * @param array $email_data Email data to send
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function send_via_mailiam($email_data) {
        $usage_key = $this->settings['usage_key'];

        // Use existing mailiam_send_email() helper if available
        if (function_exists('mailiam_send_email')) {
            return mailiam_send_email($email_data);
        }

        // Fallback: Call API directly
        $result = $this->api->send_transactional_email($usage_key, $email_data);

        if (is_wp_error($result)) {
            return $result; // Return error for fallback
        }

        return true; // Success
    }

    /**
     * Prevent PHPMailer from sending (called after successful Mailiam send)
     *
     * @param PHPMailer $phpmailer PHPMailer instance
     */
    private function prevent_smtp_send($phpmailer) {
        // Clear all recipients - this causes wp_mail() to return true
        // but PHPMailer won't actually send via SMTP
        $phpmailer->clearAllRecipients();
        $phpmailer->clearCCs();
        $phpmailer->clearBCCs();
        $phpmailer->clearReplyTos();
        $phpmailer->clearAttachments();
    }
}
