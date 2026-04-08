<?php
/**
 * Central remote logging for the Hegegemeinschaft plugin.
 *
 * Sends events to the log API at https://log.broetzens.de/api/log.
 * Fire-and-forget: never blocks the request, fails silently on errors.
 *
 * Test: Set 'hege_log_api_key' option to a valid key, call
 *       hege_send_log( 'test_event', [ 'foo' => 'bar' ] ), and verify
 *       that no exception is thrown.  With an empty key the function
 *       must return immediately without making an HTTP request.
 *
 * @package AbschussplanHGMH
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send a log event to the central logging API.
 *
 * @param string $event   Event name, e.g. 'submission_created'.
 * @param array  $payload Optional event data.  Must not contain PII.
 */
function hege_send_log( string $event, array $payload = [] ): void {
    $api_key = get_option( 'hege_log_api_key' );

    if ( empty( $api_key ) ) {
        return;
    }

    wp_remote_post(
        'https://log.broetzens.de/api/log',
        array(
            'blocking' => false,
            'headers'  => array(
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $api_key,
            ),
            'body'     => wp_json_encode(
                array(
                    'tool'         => 'hegegemeinschaft',
                    'tool_version' => AHGMH_PLUGIN_VERSION,
                    'instance'     => site_url(),
                    'event'        => $event,
                    'payload'      => $payload,
                )
            ),
        )
    );
}
