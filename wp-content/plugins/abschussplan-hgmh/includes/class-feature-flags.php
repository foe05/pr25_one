<?php
/**
 * Feature Flags Manager
 *
 * Enables safe, gradual rollout of new features via a simple boolean
 * flag stored in a single wp_options row.
 *
 * Test: Enable FLAG_MODERATION via HGMH_Feature_Flags::enable( 'use_moderation' )
 *       and verify is_enabled() returns true.  Disable it and verify false.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static helper for reading / writing feature flags.
 *
 * Flags are stored as a single associative array in the
 * `hgmh_feature_flags` option.  Values are boolean.
 */
class HGMH_Feature_Flags {

    /** Option key in wp_options. */
    const OPTION_KEY = 'hgmh_feature_flags';

    // Flag name constants.
    const FLAG_NEW_DB_SCHEMA = 'use_new_db_schema';
    const FLAG_PUBLIC_FORM   = 'use_public_form';
    const FLAG_MODERATION    = 'use_moderation';
    const FLAG_ACTIVITY_LOG  = 'use_activity_log';

    /** @var array|null In-memory cache of current flags. */
    private static $flags = null;

    /**
     * Load flags from the database (once per request).
     */
    private static function load_flags() {
        if ( null !== self::$flags ) {
            return;
        }

        $defaults = array(
            self::FLAG_NEW_DB_SCHEMA => false,
            self::FLAG_PUBLIC_FORM   => false,
            self::FLAG_MODERATION    => false,
            self::FLAG_ACTIVITY_LOG  => false,
        );

        self::$flags = get_option( self::OPTION_KEY, $defaults );
    }

    /**
     * Check whether a flag is enabled.
     *
     * @param string $flag_name One of the FLAG_* constants.
     * @return bool
     */
    public static function is_enabled( $flag_name ) {
        self::load_flags();
        return isset( self::$flags[ $flag_name ] ) && true === self::$flags[ $flag_name ];
    }

    /**
     * Enable a feature flag.
     *
     * @param string $flag_name One of the FLAG_* constants.
     * @return bool True when the option was updated.
     */
    public static function enable( $flag_name ) {
        self::load_flags();

        $old_value                  = isset( self::$flags[ $flag_name ] ) ? self::$flags[ $flag_name ] : false;
        self::$flags[ $flag_name ]  = true;
        $result                     = update_option( self::OPTION_KEY, self::$flags );

        if ( $result && true !== $old_value ) {
            self::log_change( $flag_name, 'ENABLED' );
        }

        return $result;
    }

    /**
     * Disable a feature flag.
     *
     * @param string $flag_name One of the FLAG_* constants.
     * @return bool True when the option was updated.
     */
    public static function disable( $flag_name ) {
        self::load_flags();

        $old_value                  = isset( self::$flags[ $flag_name ] ) ? self::$flags[ $flag_name ] : false;
        self::$flags[ $flag_name ]  = false;
        $result                     = update_option( self::OPTION_KEY, self::$flags );

        if ( $result && false !== $old_value ) {
            self::log_change( $flag_name, 'DISABLED' );
        }

        return $result;
    }

    /**
     * Return all flag definitions with labels and descriptions.
     *
     * @return array Keyed by flag name.
     */
    public static function get_all_flags() {
        return array(
            self::FLAG_NEW_DB_SCHEMA => array(
                'label'       => __( 'Neues Datenbank-Schema', 'abschussplan-hgmh' ),
                'description' => __( 'Nutzt hgmh_submissions_v2 statt ahgmh_submissions', 'abschussplan-hgmh' ),
                'critical'    => true,
            ),
            self::FLAG_PUBLIC_FORM   => array(
                'label'       => __( 'Öffentliches Formular', 'abschussplan-hgmh' ),
                'description' => __( 'Aktiviert anonyme Meldungen mit Email-Verifizierung', 'abschussplan-hgmh' ),
                'critical'    => false,
            ),
            self::FLAG_MODERATION    => array(
                'label'       => __( 'Moderation-Workflow', 'abschussplan-hgmh' ),
                'description' => __( 'Aktiviert Approve/Reject/Edit in abschuss_table', 'abschussplan-hgmh' ),
                'critical'    => false,
            ),
            self::FLAG_ACTIVITY_LOG  => array(
                'label'       => __( 'Activity Logging', 'abschussplan-hgmh' ),
                'description' => __( 'Trackt alle User-Aktionen für Analytics', 'abschussplan-hgmh' ),
                'critical'    => false,
            ),
        );
    }

    /**
     * Write a flag change to the PHP error log.
     *
     * @param string $flag_name Flag that changed.
     * @param string $action    'ENABLED' or 'DISABLED'.
     */
    private static function log_change( $flag_name, $action ) {
        $user = wp_get_current_user();

        error_log(
            sprintf(
                '[HGMH Feature Flags] User "%s" (ID: %d) %s flag: %s at %s',
                $user->user_login,
                $user->ID,
                $action,
                $flag_name,
                current_time( 'mysql' )
            )
        );
    }
}
