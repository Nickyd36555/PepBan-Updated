<?php
ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiAuth.php';
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/plugin-release.php';

Auth::start();

// Boot guard: refuse to serve if critical secrets are still at their placeholder defaults
if (SECRET_KEY === 'change-this-to-64-hex-chars' ||
    ADMIN_PASSWORD_HASH === '$2y$10$changethishashbygeneratingyourown.........') {
	http_response_code(503);
	die('Site not configured. Please set SECRET_KEY and ADMIN_PASSWORD_HASH in config.local.php.');
}

// ── Global error / exception handlers ────────────────────────────────────────
set_exception_handler(function (Throwable $e) {
	$msg = sprintf(
		"Uncaught %s: %s\nFile: %s:%d\nURL: %s %s\nTime: %s\n\nTrace:\n%s",
		get_class($e), $e->getMessage(),
		$e->getFile(), $e->getLine(),
		$_SERVER['REQUEST_METHOD'] ?? 'CLI',
		$_SERVER['REQUEST_URI']    ?? '',
		date('Y-m-d H:i:s'),
		$e->getTraceAsString()
	);
	error_log($msg);
	Mailer::adminError(get_class($e) . ' on ' . ($_SERVER['REQUEST_URI'] ?? ''), $msg);
	http_response_code(500);
	echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title><style>body{background:#080810;color:#e8e8f5;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{text-align:center;padding:48px}</style></head><body><div class="box"><h1>Something went wrong</h1><p style="color:#7070a0">The error has been reported. Please try again shortly.</p></div></body></html>';
	exit;
});
register_shutdown_function(function () {
	$e = error_get_last();
	if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		$msg = sprintf(
			"Fatal error: %s\nFile: %s:%d\nURL: %s %s\nTime: %s",
			$e['message'], $e['file'], $e['line'],
			$_SERVER['REQUEST_METHOD'] ?? 'CLI',
			$_SERVER['REQUEST_URI']    ?? '',
			date('Y-m-d H:i:s')
		);
		error_log($msg);
		Mailer::adminError('Fatal error on ' . ($_SERVER['REQUEST_URI'] ?? ''), $msg);
	}
});

// Run schema migrations once per deploy (flag file prevents repeat queries)
$_migration_flag = __DIR__ . '/.db_migrated_v12';
if (!file_exists($_migration_flag)) {
	try { Database::maybe_migrate(); file_put_contents($_migration_flag, date('c')); } catch (Throwable $e) {}
}

// ── IP blocking (before any routing) ─────────────────────────────────────────
$visitor_ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '';
if ($visitor_ip) {
    $visitor_ip = trim(explode(',', $visitor_ip)[0]);
    // Skip IP check for admin routes so admin can always access
    if (!str_starts_with(current_path(), '/admin') && !str_starts_with(current_path(), '/api/')) {
        $blocked = Database::get()->fetch(
            'SELECT id FROM pepban_blocked_ips WHERE ip_address = ?',
            [$visitor_ip]
        );
        if ($blocked) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access Denied</title><style>body{background:#080810;color:#e8e8f5;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{text-align:center;padding:48px}.box h1{font-size:2rem;margin-bottom:12px}.box p{color:#7070a0}</style></head><body><div class="box"><h1>Access Denied</h1><p>You are not authorized to access this site.</p></div></body></html>';
            exit;
        }
    }
}

$path = current_path();

// ── API routes (JSON, no session needed) ──────────────────────────────────────
if (str_starts_with($path, '/api/')) {
	require __DIR__ . '/api/index.php';
	exit;
}

// ── Favicon ───────────────────────────────────────────────────────────────────
if ($path === '/favicon.ico' || $path === '/favicon.png') {
	require __DIR__ . '/favicon.php';
	exit;
}

// ── Plugin download ───────────────────────────────────────────────────────────
if ($path === '/download/client') {
	require __DIR__ . '/download/client.php';
	exit;
}

// ── Admin routes ──────────────────────────────────────────────────────────────
if (str_starts_with($path, '/admin')) {
	$admin_path = ltrim(substr($path, 6), '/') ?: 'dashboard';
	$admin_path = preg_replace('/[^a-z0-9_-]/i', '', $admin_path);

	// Login/logout don't require auth
	if ($admin_path === 'login')       { require __DIR__ . '/admin/login.php';       exit; }
	if ($admin_path === 'logout')      { require __DIR__ . '/admin/logout.php';      exit; }
	if ($admin_path === 'totp-verify') { require __DIR__ . '/admin/totp-verify.php'; exit; }

	Auth::requireAdmin();

	$file = __DIR__ . '/admin/' . $admin_path . '.php';
	if (file_exists($file)) {
		require $file;
	} else {
		http_response_code(404);
		require __DIR__ . '/admin/dashboard.php';
	}
	exit;
}

// ── Public routes ─────────────────────────────────────────────────────────────
$routes = [
	'/'                 => 'pages/home.php',
	'/plugin'           => 'pages/plugin.php',
	'/signup'           => 'pages/signup.php',
	'/login'            => 'pages/login.php',
	'/logout'           => 'pages/logout.php',
	'/portal'           => 'pages/portal.php',
	'/account'          => 'pages/account.php',
	'/faq'              => 'pages/faq.php',
	'/changelog'        => 'pages/changelog.php',
	'/contact'          => 'pages/contact.php',
	'/dispute'          => 'pages/dispute.php',
	'/forgot-password'  => 'pages/forgot-password.php',
	'/reset-password'   => 'pages/reset-password.php',
	'/verify-email'     => 'pages/verify-email.php',
	'/privacy'          => 'pages/privacy.php',
	'/terms'            => 'pages/terms.php',
];

if (isset($routes[$path])) {
	require __DIR__ . '/' . $routes[$path];
} else {
	http_response_code(404);
	require __DIR__ . '/pages/404.php';
}
