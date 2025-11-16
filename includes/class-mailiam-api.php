<?php
/**
 * Mailiam API Client
 *
 * Handles communication with the Mailiam API
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mailiam_API {

    /**
     * API base URL
     */
    private $api_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_url = MAILIAM_API_URL;
    }

    /**
     * Create a public API key for this WordPress site
     *
     * @param string $admin_key Admin or usage API key
     * @param string $domain Domain for the public key
     * @return array|WP_Error Response data or error
     */
    public function create_public_key($admin_key, $domain) {
        $response = wp_remote_post($this->api_url . '/v1/apikeys', array(
            'headers' => array(
                'X-Api-Key' => $admin_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'name' => 'WordPress - ' . $domain,
                'type' => 'public',
                'domain' => $domain,
                'permissions' => array('forms:send'),
                'rateLimit' => 100,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 && $status_code !== 201) {
            return new WP_Error(
                'mailiam_api_error',
                isset($body['error']) ? $body['error'] : 'Failed to create public key',
                array('status' => $status_code)
            );
        }

        return $body;
    }

    /**
     * Submit form data to Mailiam
     *
     * @param string $domain Domain to send to
     * @param array $data Form data
     * @param string $public_key Public API key (optional)
     * @return array|WP_Error Response data or error
     */
    public function submit_form($domain, $data, $public_key = '') {
        $headers = array(
            'Content-Type' => 'application/json',
        );

        // Add API key if provided
        if (!empty($public_key)) {
            $headers['X-Api-Key'] = $public_key;
        }

        // Add origin header for validation
        $headers['Origin'] = get_site_url();

        $response = wp_remote_post($this->api_url . '/v1/' . $domain . '/send', array(
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 && $status_code !== 201) {
            return new WP_Error(
                'mailiam_api_error',
                isset($body['error']) ? $body['error'] : 'Failed to submit form',
                array('status' => $status_code)
            );
        }

        return $body;
    }

    /**
     * Test API key validity
     *
     * @param string $api_key API key to test
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function test_api_key($api_key) {
        $response = wp_remote_get($this->api_url . '/v1/apikeys', array(
            'headers' => array(
                'X-Api-Key' => $api_key,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return true;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return new WP_Error(
            'mailiam_invalid_key',
            isset($body['error']) ? $body['error'] : 'Invalid API key',
            array('status' => $status_code)
        );
    }

    /**
     * Delete an API key
     *
     * @param string $admin_key Admin or usage API key
     * @param string $key_id Key ID to delete
     * @return bool|WP_Error True if successful, error otherwise
     */
    public function delete_api_key($admin_key, $key_id) {
        $response = wp_remote_request($this->api_url . '/v1/apikeys/' . $key_id, array(
            'method' => 'DELETE',
            'headers' => array(
                'X-Api-Key' => $admin_key,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200 || $status_code === 204) {
            return true;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return new WP_Error(
            'mailiam_api_error',
            isset($body['error']) ? $body['error'] : 'Failed to delete API key',
            array('status' => $status_code)
        );
    }

    /**
     * Get list of API keys
     *
     * @param string $admin_key Admin or usage API key
     * @return array|WP_Error Array of keys or error
     */
    public function list_api_keys($admin_key) {
        $response = wp_remote_get($this->api_url . '/v1/apikeys', array(
            'headers' => array(
                'X-Api-Key' => $admin_key,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            return new WP_Error(
                'mailiam_api_error',
                isset($body['error']) ? $body['error'] : 'Failed to list API keys',
                array('status' => $status_code)
            );
        }

        return $body;
    }

    /**
     * Send transactional email
     *
     * @param string $usage_key Usage or admin API key
     * @param array $email_data Email data (from, to, subject, html, text)
     * @return array|WP_Error Response data or error
     */
    public function send_transactional_email($usage_key, $email_data) {
        // Validate required fields
        $required = array('from', 'to', 'subject');
        foreach ($required as $field) {
            if (empty($email_data[$field])) {
                return new WP_Error(
                    'mailiam_invalid_email',
                    "Missing required field: {$field}",
                    array('status' => 400)
                );
            }
        }

        // Must have either HTML or text content
        if (empty($email_data['html']) && empty($email_data['text'])) {
            return new WP_Error(
                'mailiam_invalid_email',
                'Email must have either HTML or text content',
                array('status' => 400)
            );
        }

        $response = wp_remote_post($this->api_url . '/v1/send', array(
            'headers' => array(
                'X-Api-Key' => $usage_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($email_data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 && $status_code !== 201) {
            return new WP_Error(
                'mailiam_api_error',
                isset($body['error']) ? $body['error'] : 'Failed to send email',
                array('status' => $status_code)
            );
        }

        return $body;
    }
}
