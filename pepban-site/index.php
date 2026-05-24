<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiAuth.php';
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/includes/functions.php';

Auth::start();

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
	'/forgot-password'  => 'pages/forgot-password.php',
	'/reset-password'   => 'pages/reset-password.php',
];

if (isset($routes[$path])) {
	require __DIR__ . '/' . $routes[$path];
} else {
	http_response_code(404);
	echo '404 Not Found';
}
