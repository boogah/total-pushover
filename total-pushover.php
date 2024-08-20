<?php
/**
 * Plugin Name:       Total Pushover
 * Version:           1.1.0
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

/**
 * Add a filter to intercept the wp_mail function
 * This filter will allow us to modify or cancel the email before it's sent
 */
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

/**
 * Add our Test link next to the "Deactivate" link in the Plugins page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'total_pushover_action_links');

/**
 * Add a Test link to the Plugins page
 *
 * @param array $links Array of plugin links
 *
 * @return array Array of plugin links with the custom link added
 */
function total_pushover_action_links($links)
{
    $test_link = '<a href="' . esc_url(add_query_arg('total_pushover_test', 'true', admin_url('plugins.php'))) . '">' . __('Test') . '</a>';
    array_unshift($links, $test_link);
    return $links;
}

/**
 * Handle the test message when "Test" link is clicked
 */
add_action('admin_init', 'total_pushover_send_test_message');

/**
 * Send a test message to Pushover when the "Send Test" link is clicked
 */
function total_pushover_send_test_message()
{
    // Check if the test message was requested
    if (isset($_GET['total_pushover_test']) && $_GET['total_pushover_test'] === 'true') {
        // Start output buffering to prevent any output
        ob_start();

        $pushover_token = defined('PUSHOVER_API_TOKEN') ? PUSHOVER_API_TOKEN : '';
        $pushover_user = defined('PUSHOVER_USER_KEY') ? PUSHOVER_USER_KEY : '';

        if (empty($pushover_token) || empty($pushover_user)) {
            set_transient('total_pushover_notice', 'error', 10);
        } else {
            $message = [
                'token' => sanitize_text_field($pushover_token),
                'user' => sanitize_text_field($pushover_user),
                'title' => '[Total Pushover] Success!',
                'message' => 'This is a test of the Total Pushover Notification System. The admin of your WordPress install, in voluntary cooperation with Little Room, have deployed this plugin to keep you informed in the event of a site email. If this had been an actual email, you would have received it instead of this message. This is only a test.',
            ];

            $response = wp_remote_post('https://api.pushover.net/1/messages.json', [
                'body' => $message,
            ]);

            if (is_wp_error($response)) {
                set_transient('total_pushover_notice', 'error', 10);
            } else {
                set_transient('total_pushover_notice', 'success', 10);
            }
        }

        // Redirect back to the Plugins page
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }
}

/**
 * Display admin notice after test message
 */
add_action('admin_notices', 'total_pushover_test_notice');

/**
 * Display an admin notice after sending a test message to Pushover
 */
function total_pushover_test_notice()
{
    // Check if there is a notice to display
    $notice = get_transient('total_pushover_notice');

    if ($notice === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Pushover test sent successfully.') . '</p></div>';
        delete_transient('total_pushover_notice');
    } elseif ($notice === 'error') {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Pushover test failed! Please check your API credentials.') . '</p></div>';
        delete_transient('total_pushover_notice');
    }
}
