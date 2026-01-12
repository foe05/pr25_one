<?php
/**
 * Feature Flags Manager
 *
 * Enables safe, gradual rollout of new features
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class HGMH_Feature_Flags {

    const OPTION_KEY = 'hgmh_feature_flags';

    // Flag Definitions
    const FLAG_NEW_DB_SCHEMA = 'use_new_db_schema';
    const FLAG_PUBLIC_FORM = 'use_public_form';
    const FLAG_MODERATION = 'use_moderation';
    const FLAG_ACTIVITY_LOG = 'use_activity_log';

    private static $flags = null;

    /**
     * Initialize flags from database
     */
    private static function load_flags() {
        if (self::$flags === null) {
            $defaults = array(
                self::FLAG_NEW_DB_SCHEMA => false,
                self::FLAG_PUBLIC_FORM => false,
                self::FLAG_MODERATION => false,
                self::FLAG_ACTIVITY_LOG => false
            );

            self::$flags = get_option(self::OPTION_KEY, $defaults);
        }
    }

    /**
     * Check if a feature flag is enabled
     *
     * @param string $flag_name Flag constant
     * @return bool
     */
    public static function is_enabled($flag_name) {
        self::load_flags();
        return isset(self::$flags[$flag_name]) && self::$flags[$flag_name] === true;
    }

    /**
     * Enable a feature flag
     *
     * @param string $flag_name Flag constant
     * @return bool True on success
     */
    public static function enable($flag_name) {
        self::load_flags();
        self::$flags[$flag_name] = true;
        return update_option(self::OPTION_KEY, self::$flags);
    }

    /**
     * Disable a feature flag
     *
     * @param string $flag_name Flag constant
     * @return bool True on success
     */
    public static function disable($flag_name) {
        self::load_flags();
        self::$flags[$flag_name] = false;
        return update_option(self::OPTION_KEY, self::$flags);
    }

    /**
     * Get all flags with metadata
     *
     * @return array Array of flag definitions with labels and descriptions
     */
    public static function get_all_flags() {
        return array(
            self::FLAG_NEW_DB_SCHEMA => array(
                'label' => 'Neues Datenbank-Schema',
                'description' => 'Nutzt hgmh_submissions_v2 statt ahgmh_submissions',
                'critical' => true
            ),
            self::FLAG_PUBLIC_FORM => array(
                'label' => 'Öffentliches Formular',
                'description' => 'Aktiviert anonyme Meldungen mit Email-Verifizierung',
                'critical' => false
            ),
            self::FLAG_MODERATION => array(
                'label' => 'Moderation-Workflow',
                'description' => 'Aktiviert Approve/Reject/Edit in abschuss_table',
                'critical' => false
            ),
            self::FLAG_ACTIVITY_LOG => array(
                'label' => 'Activity Logging',
                'description' => 'Trackt alle User-Aktionen für Analytics',
                'critical' => false
            )
        );
    }
}
