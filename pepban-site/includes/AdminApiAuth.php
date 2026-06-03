<?php
defined('PEPBAN_VERSION') || die;

class AdminApiAuth {

    public static function getToken(): string {
        $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
        $auth = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    public static function require(): void {
        $token = self::getToken();
        if (!$token) self::error('Unauthorized', 401);

        $db   = Database::get();
        $hash = hash('sha256', $token);
        $row  = $db->fetch(
            "SELECT id FROM pepban_admin_tokens WHERE token_hash = ? AND expires_at > NOW()",
            [$hash]
        );
        if (!$row) self::error('Unauthorized', 401);

        $db->query("UPDATE pepban_admin_tokens SET last_used_at = NOW() WHERE token_hash = ?", [$hash]);
    }

    public static function json(array $data, int $code = 200): never {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function error(string $msg, int $code = 400): never {
        self::json(['error' => $msg], $code);
    }

    public static function body(): object {
        $raw = file_get_contents('php://input');
        return json_decode($raw ?: '{}') ?: new stdClass();
    }

    public static function paginate(int $page, int $per = 25): array {
        $page   = max(1, $page);
        $offset = ($page - 1) * $per;
        return ['limit' => $per, 'offset' => $offset, 'page' => $page];
    }
}
