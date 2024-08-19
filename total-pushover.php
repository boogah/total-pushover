<?php
/**
 * Plugin Name:       Total Pushover
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Jason Cosper
 * Author URI:        https://littleroom.studio/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Description:       Redirects all outgoing site email to the Pushover notification service.
 * GitHub Plugin URI: boogah/total-pushover
 */

// Exit if accessed directly to prevent unauthorized access
if (! defined('ABSPATH')) {
    exit;
}

// Add a filter to intercept the wp_mail function
// This filter will allow us to modify or cancel the email before it's sent
add_filter('wp_mail', 'wp_pushover_intercept_mail', 10, 1);

/**
 * Intercept the wp_mail function and redirect the email to Pushover
 *
 * @param array $atts {
 *     Array of email arguments.
 *     @type string $subject Email subject.
 *     @type string $message Email message.
 * }
 *
 * @return bool|mixed False to prevent wp_mail from sending the email, or the original email arguments if Pushover credentials are not set.
 */
function wp_pushover_intercept_mail($atts)
{
    // Check if Pushover API token and user key are defined
    // These constants should be defined in the site's wp-config.php file
    $pushover_token = defined('PUSHOVER_API_TOKEN') ? PUSHOVER_API_TOKEN : '';
    $pushover_user = defined('PUSHOVER_USER_KEY') ? PUSHOVER_USER_KEY : '';

    // If either the token or user key is empty, return the original email arguments
    // This will allow the email to be sent as usual
    if (empty($pushover_token) || empty($pushover_user)) {
        return $atts;
    }

    // Prepare the message for Pushover
    // Sanitize the input data, for security's sake
    $message = [
        'token' => sanitize_text_field($pushover_token),
        'user' => sanitize_text_field($pushover_user),
        'title' => sanitize_text_field($atts['subject']),
        'message' => wp_kses_post($atts['message']),
    ];

    // Send the message to Pushover using the WordPress HTTP API
    // This will make a POST request to the Pushover API with the prepared message data
    $response = wp_remote_post('https://api.pushover.net/1/messages.json', [
        'body' => $message,
    ]);

    // Check if the response is a WP_Error object
    // This indicates that an error occurred during the request
    if (is_wp_error($response)) {
        // Log the error message for debugging purposes
        error_log('Pushover notification failed: '. $response->get_error_message());
    }

    // Return false to prevent wp_mail from sending the email
    // This will effectively redirect the email to Pushover
    return false;
}
