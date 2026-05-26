<?php
// Authenticate download: accept a short-lived HMAC token (auto-updater) or a session (portal)
$token = trim($_GET['token'] ?? '');
$authenticated = false;

if ($token) {
	// Valid for the current and previous 12-hour bucket (~24 h window covers any transient TTL)
	$now  = (string) floor(time() / (12 * 3600));
	$prev = (string) floor((time() - 12 * 3600) / (12 * 3600));
	if (hash_equals(hash_hmac('sha256', 'dl:' . $now,  SECRET_KEY), $token) ||
		hash_equals(hash_hmac('sha256', 'dl:' . $prev, SECRET_KEY), $token)) {
		$authenticated = true;
	}
}

if (!$authenticated && !Auth::isClient() && !Auth::isAdmin()) {
	http_response_code(403);
	die('Access denied.');
}

// Locate the plugin source directory (pepban-client/)
$candidates = [
	defined('PEPBAN_CLIENT_PATH') ? PEPBAN_CLIENT_PATH : null,
	dirname(__DIR__, 2) . '/pepban-client',    // sibling of pepban-site/
	__DIR__ . '/../client-dist/pepban-client', // bundled fallback
];

$source_dir = null;
foreach ($candidates as $c) {
	if ($c && is_dir($c)) { $source_dir = $c; break; }
}

if (!$source_dir) {
	http_response_code(503);
	echo 'Plugin source not found on this server. Contact support.';
	exit;
}

if (!class_exists('ZipArchive')) {
	http_response_code(503);
	echo 'ZipArchive extension is not available.';
	exit;
}

// Build ZIP in temp directory
$zip_path = sys_get_temp_dir() . '/pepban-client-' . PEPBAN_PLUGIN_VERSION . '.zip';

// Rebuild each time (version or source may have changed)
if (file_exists($zip_path)) unlink($zip_path);

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
	http_response_code(500);
	echo 'Could not create ZIP archive.';
	exit;
}

$base  = realpath($source_dir);
$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
	RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
	$real  = $file->getRealPath();
	$local = 'pepban-client/' . ltrim(substr($real, strlen($base)), DIRECTORY_SEPARATOR);

	// Dynamically stamp the hub version into the main plugin file
	if (basename($real) === 'pepban-client.php' && dirname($real) === $base) {
		$content = file_get_contents($real);
		$content = preg_replace('/(\*\s*Version:\s*)[\d.]+/', '${1}' . PEPBAN_PLUGIN_VERSION, $content);
		$content = preg_replace("/(define\s*\(\s*'PEPBAN_CLIENT_VERSION'\s*,\s*')[^']+(')/", '${1}' . PEPBAN_PLUGIN_VERSION . '${2}', $content);
		$zip->addFromString($local, $content);
	} else {
		$zip->addFile($real, $local);
	}
}
$zip->close();

// Stream the ZIP — discard any buffered output before sending binary headers
ob_end_clean();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="pepban-client-' . PEPBAN_PLUGIN_VERSION . '.zip"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-store');
readfile($zip_path);
unlink($zip_path);
exit;
