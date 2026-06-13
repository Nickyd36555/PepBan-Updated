<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
Auth::requireAdmin();

$releases_dir  = __DIR__ . '/../releases';
$rollback_file = __DIR__ . '/../.rollback_version';
$rollback_ver  = file_exists($rollback_file) ? trim(file_get_contents($rollback_file)) : '';
$message       = '';
$error         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$action = post('action');

	if ($action === 'set_rollback') {
		$ver = preg_replace('/[^0-9.]/', '', post('version'));
		$zip = $releases_dir . '/pepban-client-' . $ver . '.zip';
		if (!$ver || !preg_match('/^\d+\.\d+\.\d+$/', $ver)) {
			$error = 'Invalid version number.';
		} elseif (!file_exists($zip)) {
			$error = 'No saved ZIP found for v' . $ver . '. That version has not been downloaded yet.';
		} else {
			file_put_contents($rollback_file, $ver);
			$rollback_ver = $ver;
			$message = 'Rollback activated. All stores will receive v' . $ver . ' on their next update check (within 6 hours).';
		}
	} elseif ($action === 'clear_rollback') {
		if (file_exists($rollback_file)) unlink($rollback_file);
		$rollback_ver = '';
		$message = 'Rollback cleared. Stores will now receive v' . PEPBAN_PLUGIN_VERSION . ' (latest).';
	}
}

// List available release ZIPs
$releases = [];
if (is_dir($releases_dir)) {
	foreach (glob($releases_dir . '/pepban-client-*.zip') as $f) {
		if (preg_match('/pepban-client-(\d+\.\d+\.\d+)\.zip$/', basename($f), $m)) {
			$releases[] = $m[1];
		}
	}
	usort($releases, 'version_compare');
	$releases = array_reverse($releases); // newest first
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Plugin Versions — <?= e(SITE_NAME) ?> Admin</title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<style>
.ver-card{max-width:700px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:40px}
.ver-card h1{margin:0 0 6px;font-size:20px;color:#111827}
.ver-card .sub{margin:0 0 28px;color:#6b7280;font-size:14px}
.ver-table{width:100%;border-collapse:collapse;margin-top:16px}
.ver-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af;padding:8px 12px;border-bottom:2px solid #f3f4f6}
.ver-table td{padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:14px;vertical-align:middle}
.ver-table tr:last-child td{border-bottom:none}
.badge-current{background:#f0fdf4;color:#166534;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px}
.badge-rollback{background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px}
.rollback-banner{background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:16px 20px;margin-bottom:24px}
.rollback-banner strong{color:#92400e}
</style>
</head>
<body class="pb-admin-body">
<div class="ver-card">
	<a href="<?= url('/admin/dashboard') ?>" style="font-size:13px;color:#6b7280;text-decoration:none;display:block;margin-bottom:20px">&larr; Dashboard</a>
	<h1>Plugin Version Control</h1>
	<p class="sub">Manage which plugin version is served to member stores. Use rollback to revert all stores to a previous release if an update causes problems.</p>

	<?php if ($message): ?>
	<div class="pb-alert" style="background:#f0fdf4;border-color:#22c55e;color:#166534;margin-bottom:24px"><?= e($message) ?></div>
	<?php endif; ?>
	<?php if ($error): ?>
	<div class="pb-alert pb-alert-error" style="margin-bottom:24px"><?= e($error) ?></div>
	<?php endif; ?>

	<?php if ($rollback_ver): ?>
	<div class="rollback-banner">
		<strong>&#9888; Rollback active &mdash; v<?= e($rollback_ver) ?></strong>
		<p style="margin:8px 0 12px;font-size:14px;color:#78350f">Stores are being served v<?= e($rollback_ver) ?> instead of v<?= e(PEPBAN_PLUGIN_VERSION) ?>. Stores will automatically downgrade on their next update check.</p>
		<form method="post">
			<?= csrf_field() ?>
			<input type="hidden" name="action" value="clear_rollback">
			<button type="submit" class="pb-btn pb-btn-primary" style="background:#22c55e">
				Clear Rollback &mdash; Resume Serving v<?= e(PEPBAN_PLUGIN_VERSION) ?>
			</button>
		</form>
	</div>
	<?php endif; ?>

	<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
		<h2 style="font-size:16px;margin:0">Saved Releases</h2>
		<span style="font-size:13px;color:#6b7280">Latest: <strong>v<?= e(PEPBAN_PLUGIN_VERSION) ?></strong></span>
	</div>

	<?php if (empty($releases)): ?>
	<p style="color:#6b7280;font-size:14px;margin-top:16px">No releases archived yet. Release ZIPs are saved automatically the first time each version is downloaded from the portal.</p>
	<?php else: ?>
	<table class="ver-table">
		<thead>
			<tr>
				<th>Version</th>
				<th>ZIP</th>
				<th>Status</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($releases as $ver): ?>
			<?php
				$zip_file = $releases_dir . '/pepban-client-' . $ver . '.zip';
				$size_kb  = round(filesize($zip_file) / 1024);
				$is_curr  = ($ver === PEPBAN_PLUGIN_VERSION);
				$is_rb    = ($ver === $rollback_ver);
			?>
			<tr>
				<td><strong>v<?= e($ver) ?></strong></td>
				<td style="color:#6b7280"><?= $size_kb ?> KB</td>
				<td>
					<?php if ($is_curr && !$rollback_ver): ?>
						<span class="badge-current">&#10003; Currently served</span>
					<?php elseif ($is_rb): ?>
						<span class="badge-rollback">&#9888; Rollback target</span>
					<?php endif; ?>
				</td>
				<td style="text-align:right">
					<?php if (!$is_curr || $rollback_ver): ?>
					<?php if (!$is_rb): ?>
					<form method="post" style="display:inline"
						onsubmit="return confirm('Roll back all stores to v<?= e($ver) ?>? They will receive a downgrade notification within 6 hours.')">
						<?= csrf_field() ?>
						<input type="hidden" name="action" value="set_rollback">
						<input type="hidden" name="version" value="<?= e($ver) ?>">
						<button type="submit" class="pb-btn" style="background:#f59e0b;color:#fff;padding:6px 14px;font-size:13px">
							Rollback to v<?= e($ver) ?>
						</button>
					</form>
					<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<div style="margin-top:32px;padding-top:24px;border-top:1px solid #f3f4f6;font-size:13px;color:#9ca3af">
		<strong>How it works:</strong> When rollback is active, the plugin update endpoint reports the rollback version to all stores.
		WordPress shows an "update available" notification — clicking it installs the older version.
		Once stores are stable, clear the rollback to resume serving the latest.
	</div>
</div>
</body>
</html>
