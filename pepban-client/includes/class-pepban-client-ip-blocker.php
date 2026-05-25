<?php
defined('ABSPATH') || exit;

class PepBan_Client_IP_Blocker {

    public static function init() {
        add_action('template_redirect', [__CLASS__, 'check_visitor_ip']);
    }

    public static function check_visitor_ip() {
        if (!PepBan_Client_Settings::is_configured()) return;
        if (is_admin()) return;

        $ip = self::get_visitor_ip();
        if (!$ip) return;

        $blocked_ips = self::get_blocked_ips();
        if (in_array($ip, $blocked_ips, true)) {
            wp_die(
                '<h1>Access Denied</h1><p>You are not authorized to access this store.</p>',
                'Access Denied',
                ['response' => 403]
            );
        }
    }

    private static function get_blocked_ips(): array {
        $cached = get_transient('pepban_blocked_ips');
        if ($cached !== false) return $cached;

        $result = PepBan_Client_API::get('/blocked-ips');
        if (is_wp_error($result) || empty($result['blocked_ips'])) {
            // Cache empty result for 5 min on error, 1 hour on success
            set_transient('pepban_blocked_ips', [], 5 * MINUTE_IN_SECONDS);
            return [];
        }

        $ips = array_map('trim', (array) $result['blocked_ips']);
        set_transient('pepban_blocked_ips', $ips, HOUR_IN_SECONDS);
        return $ips;
    }

    private static function get_visitor_ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '';
    }
}
