<?php
defined('PEPBAN_VERSION') || die;

// ── URL helpers ───────────────────────────────────────────────────────────────

function url(string $path = ''): string {
	return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path, array $query = []): never {
	$url = url($path);
	if ($query) $url .= '?' . http_build_query($query);
	header('Location: ' . $url);
	exit;
}

function current_path(): string {
	$uri  = $_SERVER['REQUEST_URI'] ?? '/';
	$base = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
	$path = parse_url($uri, PHP_URL_PATH);
	return '/' . ltrim(substr($path, strlen($base)), '/');
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

function csrf_token(): string {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function csrf_field(): string {
	return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void {
	$token = $_POST['_csrf'] ?? '';
	if (!hash_equals(csrf_token(), $token)) {
		http_response_code(403);
		die('Invalid CSRF token.');
	}
}

// ── Input helpers ─────────────────────────────────────────────────────────────

function e(mixed $val): string {
	return htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
}

function post(string $key, string $default = ''): string {
	return trim($_POST[$key] ?? $default);
}

function get_param(string $key, string $default = ''): string {
	return trim($_GET[$key] ?? $default);
}

// ── Flash messages ────────────────────────────────────────────────────────────

function flash(string $type, string $message): void {
	$_SESSION['flash'][] = compact('type', 'message');
}

function get_flashes(): array {
	$f = $_SESSION['flash'] ?? [];
	unset($_SESSION['flash']);
	return $f;
}

function admin_flash(string $type, string $message): void {
	$_SESSION['admin_flash'][] = compact('type', 'message');
}

function get_admin_flashes(): array {
	$f = $_SESSION['admin_flash'] ?? [];
	unset($_SESSION['admin_flash']);
	return $f;
}

// ── API key generation ────────────────────────────────────────────────────────

function generate_api_key(): string {
	return 'pbk_' . bin2hex(random_bytes(20));
}

// ── Trusted IP detection ─────────────────────────────────────────────────────
// Only trust proxy headers when the connection comes from a known Cloudflare IP range
// or a private/loopback address (local reverse proxy). Falls back to REMOTE_ADDR.

function get_visitor_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && _is_cloudflare_ip($remote)) {
        $ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }

    // X-Forwarded-For only when REMOTE_ADDR is a private/loopback (local proxy)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && !filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }

    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '127.0.0.1';
}

function _is_cloudflare_ip(string $ip): bool {
    static $ranges = [
        '173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22',
        '141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
        '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13',
        '104.24.0.0/14','172.64.0.0/13','131.0.72.0/22',
        '2400:cb00::/32','2606:4700::/32','2803:f800::/32','2405:b500::/32',
        '2405:8100::/32','2a06:98c0::/29','2c0f:f248::/32',
    ];
    foreach ($ranges as $cidr) {
        [$subnet, $bits] = explode('/', $cidr);
        if (strpos($ip, ':') !== false) {
            if (strpos($subnet, ':') === false) continue;
            $a = inet_pton($ip); $b = inet_pton($subnet);
            if ($a === false || $b === false) continue;
            $mask = str_repeat("\xff", (int)($bits / 8));
            if ($bits % 8) $mask .= chr(0xff & (0xff << (8 - $bits % 8)));
            $mask = str_pad($mask, strlen($a), "\x00");
            if (($a & $mask) === ($b & $mask)) return true;
        } else {
            if (strpos($subnet, ':') !== false) continue;
            if ((ip2long($ip) & ~((1 << (32 - (int)$bits)) - 1)) === ip2long($subnet)) return true;
        }
    }
    return false;
}

// ── Rate limiting (file-based, no Redis needed) ───────────────────────────────

// Login brute-force: max 10 failed attempts per 15 minutes per IP.
// Call with $record=false to check, $record=true to count a failure.
function login_rate_limit(string $ip, bool $record = false): bool {
	$dir  = sys_get_temp_dir() . '/pepban_rl/';
	if (!is_dir($dir)) mkdir($dir, 0700, true);
	$file = $dir . 'login_' . md5($ip) . '_' . floor(time() / 900);
	$count = file_exists($file) ? (int) file_get_contents($file) : 0;
	if ($count >= 10) return false;
	if ($record) file_put_contents($file, $count + 1, LOCK_EX);
	return true;
}

function check_rate_limit(string $identifier): bool {
	$dir  = sys_get_temp_dir() . '/pepban_rl/';
	if (!is_dir($dir)) mkdir($dir, 0700, true);
	$file = $dir . md5($identifier) . '_' . floor(time() / 60);

	// Atomic read-increment-write using an exclusive lock held across both operations.
	$fh = @fopen($file, 'c+');
	if (!$fh) return true; // fail open if filesystem is unavailable
	flock($fh, LOCK_EX);
	$count = (int) fread($fh, 20);
	if ($count >= RATE_LIMIT_PER_MINUTE) {
		flock($fh, LOCK_UN);
		fclose($fh);
		return false;
	}
	fseek($fh, 0);
	fwrite($fh, $count + 1);
	ftruncate($fh, ftell($fh));
	flock($fh, LOCK_UN);
	fclose($fh);

	// Clean stale files occasionally
	if (mt_rand(1, 50) === 1) {
		foreach (glob($dir . '*') as $f) {
			if (filemtime($f) < time() - 120) @unlink($f);
		}
	}
	return true;
}

// ── Pagination ────────────────────────────────────────────────────────────────

function paginate(int $total, int $per_page, int $current_page): array {
	$total_pages = (int) ceil($total / $per_page);
	return [
		'total'       => $total,
		'per_page'    => $per_page,
		'current'     => $current_page,
		'total_pages' => $total_pages,
		'offset'      => ($current_page - 1) * $per_page,
		'has_prev'    => $current_page > 1,
		'has_next'    => $current_page < $total_pages,
	];
}
