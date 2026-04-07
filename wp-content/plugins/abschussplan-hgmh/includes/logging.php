<?php
/**
 * Zentrales Logging für das Hegegemeinschafts-Plugin.
 *
 * Sendet Events an die zentrale Log-API unter https://log.broetzens.de/api/log.
 * Fire-and-forget: blockiert nie den Request, scheitert still bei Fehlern.
 *
 * @package Abschussplan_HGMH
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sendet ein Log-Event an die zentrale Log-API.
 *
 * @param string $event   Event-Name (z.B. 'submission_created').
 * @param array  $payload Optionale Event-Daten. Keine personenbezogenen Daten!
 */
function hege_send_log( string $event, array $payload = [] ): void {
    $api_key = get_option( 'hege_log_api_key' );

    if ( empty( $api_key ) ) {
        return;
    }

    wp_remote_post( 'https://log.broetzens.de/api/log', array(
        'blocking' => false,
        'headers'  => array(
            'Content-Type' => 'application/json',
            'X-Api-Key'    => $api_key,
        ),
        'body'     => wp_json_encode( array(
            'tool'         => 'hegegemeinschaft',
            'tool_version' => AHGMH_PLUGIN_VERSION,
            'instance'     => site_url(),
            'event'        => $event,
            'payload'      => $payload,
        ) ),
    ) );
}
