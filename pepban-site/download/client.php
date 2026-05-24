<?php
// Only logged-in active clients (or admin) can download the plugin.
if (!Auth::isClient() && !Auth::isAdmin()) {
	redirect('/login?next=/download/client');
}

if (Auth::isClient()) {
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
$zip_path = sys_get_temp_dir() . '/pepban-client-' . PEPBAN_VERSION . '.zip';

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
	$zip->addFile($real, $local);
}
$zip->close();

// Stream the ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="pepban-client-' . PEPBAN_VERSION . '.zip"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-store');
readfile($zip_path);
unlink($zip_path);
exit;
