<?php
ob_start(); // capture any stray output so it doesn't corrupt the binary ZIP response
// Allow API-key-authenticated downloads (used by WordPress auto-updater)
$api_key_param = trim($_GET['api_key'] ?? '');
if ($api_key_param) {
	$prefix = substr($api_key_param, 0, 8);
	$rows   = Database::get()->fetchAll(
		'SELECT * FROM pepban_clients WHERE api_key_prefix = ?',
		[$prefix]
	);
	$api_client = null;
	foreach ($rows as $row) {
		if (password_verify($api_key_param, $row->api_key_hash)) { $api_client = $row; break; }
	}
	if (!$api_client || $api_client->subscription_status !== 'active') {
		http_response_code(403);
		die('Invalid or inactive API key.');
	}
	// Authenticated via API key — skip session checks below
} elseif (!Auth::isClient() && !Auth::isAdmin()) {
	redirect('/login?next=/download/client');
}

if (!$api_key_param && Auth::isClient()) {
	$client = Auth::client();
	if ($client->subscription_status !== 'active') {
		flash('error', 'Your account must be active to download the plugin.');
		redirect('/portal');
	}
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
