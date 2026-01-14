<?php
/**
 * Rate Limiter Class
 * Prevents spam by limiting submissions per IP address
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate Limiter for IP-based submission limiting
 */
class AHGMH_Rate_Limiter {

    /**
     * Maximum submissions allowed per IP address per time window
     */
    const MAX_SUBMISSIONS = 5;

    /**
     * Time window in seconds (1 hour)
     */
    const TIME_WINDOW = 3600; // 1 hour in seconds

    /**
     * Transient prefix for rate limit storage
     */
    const TRANSIENT_PREFIX = 'ahgmh_rate_limit_';

    /**
     * Check if IP address has exceeded rate limit
     *
     * @param string $ip IP address to check
     * @return bool True if limit exceeded, false otherwise
     */
    public static function check_rate_limit($ip) {
        // Sanitize IP address
        $ip = sanitize_text_field($ip);

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            // Invalid IP - apply rate limit for safety
            return true;
        }

        // Get current submission count
        $count = self::get_submission_count($ip);

        // Check if limit exceeded
        if ($count >= self::MAX_SUBMISSIONS) {
            // Log rate limit hit if debugging enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AHGMH: Rate limit exceeded for IP ' . $ip . ' (' . $count . ' submissions)');
            }
            return true;
        }

        return false;
    }

    /**
     * Increment submission count for IP address
     *
     * @param string $ip IP address to increment
     * @return bool Success status
     */
    public static function increment_submission_count($ip) {
        // Sanitize IP address
        $ip = sanitize_text_field($ip);

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Get current count
        $count = self::get_submission_count($ip);

        // Increment count
        $new_count = $count + 1;

        // Generate transient key
        $transient_key = self::get_transient_key($ip);

        // Store updated count with expiry
        $result = set_transient($transient_key, $new_count, self::TIME_WINDOW);

        // Log increment if debugging enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AHGMH: Rate limit count for IP ' . $ip . ' incremented to ' . $new_count);
        }

        return $result;
    }

    /**
     * Get submission count for IP address
     *
     * @param string $ip IP address to check
     * @return int Current submission count
     */
    public static function get_submission_count($ip) {
        // Sanitize IP address
        $ip = sanitize_text_field($ip);

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 0;
        }

        // Generate transient key
        $transient_key = self::get_transient_key($ip);

        // Get count from transient
        $count = get_transient($transient_key);

        // Return 0 if transient not found (expired or never set)
        return ($count !== false) ? absint($count) : 0;
    }

    /**
     * Generate transient key for IP address
     *
     * @param string $ip IP address
     * @return string Transient key
     */
    private static function get_transient_key($ip) {
        // Hash the IP to create a consistent, safe key
        $hashed_ip = md5($ip);

        return self::TRANSIENT_PREFIX . $hashed_ip;
    }

    /**
     * Reset rate limit for IP address
     * Useful for testing or manual override
     *
     * @param string $ip IP address to reset
     * @return bool Success status
     */
    public static function reset_rate_limit($ip) {
        // Sanitize IP address
        $ip = sanitize_text_field($ip);

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Generate transient key
        $transient_key = self::get_transient_key($ip);

        // Delete transient
        $result = delete_transient($transient_key);

        // Log reset if debugging enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AHGMH: Rate limit reset for IP ' . $ip);
        }

        return $result;
    }

    /**
     * Get remaining submissions for IP address
     *
     * @param string $ip IP address to check
     * @return int Number of remaining submissions allowed
     */
    public static function get_remaining_submissions($ip) {
        $count = self::get_submission_count($ip);
        $remaining = self::MAX_SUBMISSIONS - $count;

        return max(0, $remaining);
    }

    /**
     * Get rate limit info for IP address
     *
     * @param string $ip IP address to check
     * @return array Rate limit information
     */
    public static function get_rate_limit_info($ip) {
        $count = self::get_submission_count($ip);
        $remaining = self::get_remaining_submissions($ip);
        $is_limited = self::check_rate_limit($ip);

        return array(
            'ip' => sanitize_text_field($ip),
            'current_count' => $count,
            'max_allowed' => self::MAX_SUBMISSIONS,
            'remaining' => $remaining,
            'is_limited' => $is_limited,
            'time_window_seconds' => self::TIME_WINDOW,
            'time_window_hours' => self::TIME_WINDOW / 3600
        );
    }
}
