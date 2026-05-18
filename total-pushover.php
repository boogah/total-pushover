<?php
/**
 * Plugin Name:       Total Pushover
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Jason Cosper
 * Author URI:        https://littleroom.studio/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Description:       Redirects all outgoing site email to the Pushover notification service.
 * Text Domain:       total-pushover
 * GitHub Plugin URI: boogah/total-pushover
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_mail', 'wp_pushover_intercept_mail', 10, 1 );

/**
 * Intercept wp_mail and redirect the email to Pushover.
 *
 * @param array $atts {
 *     Array of email arguments.
 *     @type string $subject Email subject.
 *     @type string $message Email message.
 * }
 * @return array|false Original args if Pushover credentials not set, false to suppress the email.
 */
function wp_pushover_intercept_mail( $atts ) {
	$pushover_token = defined( 'PUSHOVER_API_TOKEN' ) ? PUSHOVER_API_TOKEN : '';
	$pushover_user  = defined( 'PUSHOVER_USER_KEY' ) ? PUSHOVER_USER_KEY : '';

	if ( empty( $pushover_token ) || empty( $pushover_user ) ) {
		return $atts;
	}

	$body = array(
		'token'   => sanitize_text_field( $pushover_token ),
		'user'    => sanitize_text_field( $pushover_user ),
		'title'   => sanitize_text_field( $atts['subject'] ),
		'message' => wp_kses_post( $atts['message'] ),
	);

	$response = wp_remote_post(
		'https://api.pushover.net/1/messages.json',
		array( 'body' => $body )
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'Pushover notification failed: ' . $response->get_error_message() );
	} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		error_log( 'Pushover notification failed with HTTP status: ' . wp_remote_retrieve_response_code( $response ) );
	}

	return false;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'total_pushover_action_links' );

/**
 * Add a Test link to the Plugins page.
 *
 * @param array $links Array of plugin action links.
 * @return array Modified array of plugin action links.
 */
function total_pushover_action_links( $links ) {
	$url = add_query_arg(
		array(
			'total_pushover_test' => '1',
			'_wpnonce'            => wp_create_nonce( 'total_pushover_test' ),
		),
		admin_url( 'plugins.php' )
	);

	$test_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Test', 'total-pushover' ) . '</a>';
	array_unshift( $links, $test_link );
	return $links;
}

add_action( 'admin_init', 'total_pushover_send_test_message' );

/**
 * Send a test message to Pushover when the Test link is clicked.
 */
function total_pushover_send_test_message() {
	if ( ! isset( $_GET['total_pushover_test'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'total-pushover' ) );
	}

	check_admin_referer( 'total_pushover_test' );

	$pushover_token = defined( 'PUSHOVER_API_TOKEN' ) ? PUSHOVER_API_TOKEN : '';
	$pushover_user  = defined( 'PUSHOVER_USER_KEY' ) ? PUSHOVER_USER_KEY : '';

	if ( empty( $pushover_token ) || empty( $pushover_user ) ) {
		set_transient( 'total_pushover_notice', 'error', 10 );
	} else {
		$body = array(
			'token'   => sanitize_text_field( $pushover_token ),
			'user'    => sanitize_text_field( $pushover_user ),
			'title'   => '[Total Pushover] Success!',
			'message' => 'This is a test of the Total Pushover Notification System. The admin of your WordPress install, in voluntary cooperation with Little Room, have deployed this plugin to keep you informed in the event of a site email. If this had been an actual email, you would have received it instead of this message. This is only a test.',
		);

		$response = wp_remote_post(
			'https://api.pushover.net/1/messages.json',
			array( 'body' => $body )
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( 'total_pushover_notice', 'error', 10 );
		} else {
			set_transient( 'total_pushover_notice', 'success', 10 );
		}
	}

	wp_safe_redirect( admin_url( 'plugins.php' ) );
	exit;
}

add_action( 'admin_notices', 'total_pushover_test_notice' );

/**
 * Display an admin notice after sending a test message to Pushover.
 */
function total_pushover_test_notice() {
	$notice = get_transient( 'total_pushover_notice' );

	if ( 'success' === $notice ) {
		delete_transient( 'total_pushover_notice' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Pushover test sent successfully.', 'total-pushover' ) . '</p></div>';
	} elseif ( 'error' === $notice ) {
		delete_transient( 'total_pushover_notice' );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Pushover test failed! Please check your API credentials.', 'total-pushover' ) . '</p></div>';
	}
}
