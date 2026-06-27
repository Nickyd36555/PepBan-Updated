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
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';

        // Only trust CF-Connecting-IP when the connection actually comes from Cloudflare
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && self::is_cloudflare_ip( $remote ) ) {
            $ip = sanitize_text_field( wp_unslash( trim( explode( ',', $_SERVER['HTTP_CF_CONNECTING_IP'] )[0] ) ) );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }

        // X-Forwarded-For only when REMOTE_ADDR is a private/loopback address (local reverse proxy)
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && filter_var( $remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
            $ip = sanitize_text_field( wp_unslash( trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] ) ) );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }

        if ( filter_var( $remote, FILTER_VALIDATE_IP ) ) return $remote;
        return '';
    }

    public static function is_cloudflare_ip_public( string $ip ): bool {
        return self::is_cloudflare_ip( $ip );
    }

    private static function is_cloudflare_ip( string $ip ): bool {
        // Cloudflare published IP ranges (IPv4 and IPv6)
        $ranges = [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ];
        foreach ( $ranges as $range ) {
            if ( self::ip_in_cidr( $ip, $range ) ) return true;
        }
        return false;
    }

    private static function ip_in_cidr( string $ip, string $cidr ): bool {
        list( $subnet, $bits ) = explode( '/', $cidr );
        if ( strpos( $ip, ':' ) !== false ) {
            // IPv6
            if ( strpos( $subnet, ':' ) === false ) return false;
            $ip_bin  = inet_pton( $ip );
            $sub_bin = inet_pton( $subnet );
            if ( $ip_bin === false || $sub_bin === false ) return false;
            $mask = str_repeat( "\xff", (int) floor( (int) $bits / 8 ) );
            if ( (int) $bits % 8 ) $mask .= chr( 0xff & ( 0xff << ( 8 - ( (int) $bits % 8 ) ) ) );
            $mask = str_pad( $mask, strlen( $ip_bin ), "\x00" );
            return ( $ip_bin & $mask ) === ( $sub_bin & $mask );
        }
        // IPv4
        if ( strpos( $subnet, ':' ) !== false ) return false;
        return ( ip2long( $ip ) & ~( ( 1 << ( 32 - (int) $bits ) ) - 1 ) ) === ip2long( $subnet );
    }
}
