<?php
$page_title = 'Upload Logo';
$msg = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
		$msg = 'Invalid CSRF token.'; $type = 'error';
	} elseif (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
		$msg = 'Upload failed (error code ' . ($_FILES['logo']['error'] ?? 'none') . ').'; $type = 'error';
	} else {
		$tmp  = $_FILES['logo']['tmp_name'];
		$info = getimagesize($tmp);
		if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'], true)) {
			$msg = 'Only PNG, JPG, WebP, or SVG files are allowed.'; $type = 'error';
		} else {
			$ext  = match($info['mime']) {
				'image/jpeg'     => 'jpg',
				'image/webp'     => 'webp',
				'image/svg+xml'  => 'svg',
				default          => 'png',
			};
			$dest = __DIR__ . '/../assets/images/logo.' . $ext;
			// Remove old logo files
			foreach (glob(__DIR__ . '/../assets/images/logo.*') as $old) {
				@unlink($old);
			}
			if (move_uploaded_file($tmp, $dest)) {
				$msg = 'Logo uploaded successfully → assets/images/logo.' . $ext; $type = 'success';
			} else {
				$msg = 'Could not save file. Check directory permissions on assets/images/.'; $type = 'error';
			}
		}
	}
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-admin-section">
	<h2>Upload Site Logo</h2>
	<p style="color:var(--text-muted);margin-bottom:20px">Replaces <code>assets/images/logo.png</code>. PNG with transparent background works best on the dark nav.</p>

	<?php if ($msg): ?>
		<div class="pepban-alert pepban-alert-<?= $type === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px"><?= e($msg) ?></div>
	<?php endif; ?>

	<?php
	$existing = null;
	foreach (['logo.png','logo.jpg','logo.webp','logo.svg'] as $f) {
		if (file_exists(__DIR__ . '/../assets/images/' . $f)) { $existing = $f; break; }
	}
	?>
	<?php if ($existing): ?>
		<div style="margin-bottom:24px;padding:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;display:inline-block">
			<p style="color:var(--text-muted);font-size:.8rem;margin-bottom:8px">Current logo (<?= e($existing) ?>)</p>
			<img src="<?= e(rtrim(SITE_URL,'/')) ?>/assets/images/<?= e($existing) ?>?v=<?= time() ?>"
			     alt="Current logo" style="max-height:80px;max-width:320px;border-radius:6px">
		</div>
	<?php else: ?>
		<p style="color:var(--text-muted);margin-bottom:20px"><em>No logo file found yet.</em></p>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data" style="max-width:480px">
		<input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token']) ?>">
		<div style="margin-bottom:16px">
			<label style="display:block;color:var(--text-muted);font-size:.85rem;margin-bottom:8px">Select PNG / JPG / WebP / SVG</label>
			<input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"
			       style="color:var(--text);background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 14px;width:100%;cursor:pointer">
		</div>
		<button type="submit" class="pepban-btn pepban-btn-primary">Upload Logo</button>
	</form>
</div>
