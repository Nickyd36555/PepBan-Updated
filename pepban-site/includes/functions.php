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

// ── API key generation ────────────────────────────────────────────────────────

function generate_api_key(): string {
	return 'pbk_' . bin2hex(random_bytes(20));
}

// ── Rate limiting (file-based, no Redis needed) ───────────────────────────────

function check_rate_limit(string $identifier): bool {
	$dir  = sys_get_temp_dir() . '/pepban_rl/';
	if (!is_dir($dir)) mkdir($dir, 0700, true);
	$file = $dir . md5($identifier) . '_' . floor(time() / 60);
	$count = file_exists($file) ? (int) file_get_contents($file) : 0;
	if ($count >= RATE_LIMIT_PER_MINUTE) return false;
	file_put_contents($file, $count + 1);
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
