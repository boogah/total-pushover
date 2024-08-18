<?php
/**
 * Plugin Name:       Total Pushover
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Jason Cosper
 * Author URI:        https://littleroom.studio/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Description:       Redirects all WordPress email to the Pushover notification service.
 * GitHub Plugin URI: boogah/total-pushover
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Add a filter to intercept wp_mail
add_filter('wp_mail', 'wp_pushover_intercept_mail', 10, 1);

function wp_pushover_intercept_mail($atts)
{
    $pushover_token = defined('PUSHOVER_API_TOKEN') ? PUSHOVER_API_TOKEN : '';
    $pushover_user = defined('PUSHOVER_USER_KEY') ? PUSHOVER_USER_KEY : '';

    if (empty($pushover_token) || empty($pushover_user)) {
        return $atts;
    }

    // Prepare the message for Pushover
    $message = [
        'token' => sanitize_text_field($pushover_token),
        'user' => sanitize_text_field($pushover_user),
        'title' => sanitize_text_field($atts['subject']),
        'message' => wp_kses_post($atts['message']),
    ];

    // Send the message to Pushover
    $response = wp_remote_post('https://api.pushover.net/1/messages.json', [
        'body' => $message,
    ]);

    if (is_wp_error($response)) {
        // Handle error if needed
        error_log('Pushover notification failed: ' . $response->get_error_message());
    }

    // Return false to prevent wp_mail from sending the email
    return false;
}
