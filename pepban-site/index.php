<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiAuth.php';
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/includes/functions.php';

Auth::start();

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

// ── Plugin download ───────────────────────────────────────────────────────────
if ($path === '/download/client') {
	require __DIR__ . '/download/client.php';
	exit;
}

// ── Admin routes ──────────────────────────────────────────────────────────────
if (str_starts_with($path, '/admin')) {
	$admin_path = ltrim(substr($path, 6), '/') ?: 'dashboard';

	// Login/logout don't require auth
	if ($admin_path === 'login')  { require __DIR__ . '/admin/login.php';  exit; }
	if ($admin_path === 'logout') { require __DIR__ . '/admin/logout.php'; exit; }

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
	'/signup'           => 'pages/signup.php',
	'/login'            => 'pages/login.php',
	'/logout'           => 'pages/logout.php',
	'/portal'           => 'pages/portal.php',
	'/faq'              => 'pages/faq.php',
	'/changelog'        => 'pages/changelog.php',
	'/contact'          => 'pages/contact.php',
	'/forgot-password'  => 'pages/forgot-password.php',
	'/reset-password'   => 'pages/reset-password.php',
];

if (isset($routes[$path])) {
	require __DIR__ . '/' . $routes[$path];
} else {
	http_response_code(404);
	echo '404 Not Found';
}
