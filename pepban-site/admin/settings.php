<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$page_title = 'Settings';

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();

	$lines   = [];
	$lines[] = '<?php';
	$lines[] = "define('DB_HOST', " . var_export(post('db_host'), true) . ');';
	$lines[] = "define('DB_NAME', " . var_export(post('db_name'), true) . ');';
	$lines[] = "define('DB_USER', " . var_export(post('db_user'), true) . ');';

	$new_pass = post('db_pass');
	if ($new_pass !== '') {
		$lines[] = "define('DB_PASS', " . var_export($new_pass, true) . ');';
	} else {
		$lines[] = "define('DB_PASS', " . var_export(DB_PASS, true) . ');';
	}

	$lines[] = "define('SITE_URL', " . var_export(rtrim(post('site_url'), '/'), true) . ');';
	$lines[] = "define('SITE_NAME', " . var_export(post('site_name'), true) . ');';
	$lines[] = "define('ADMIN_EMAIL', " . var_export(post('admin_email'), true) . ');';

	$new_hash = post('admin_password_new');
	if ($new_hash !== '') {
		$lines[] = "define('ADMIN_PASSWORD_HASH', " . var_export(password_hash($new_hash, PASSWORD_DEFAULT), true) . ');';
	} else {
		$lines[] = "define('ADMIN_PASSWORD_HASH', " . var_export(ADMIN_PASSWORD_HASH, true) . ');';
	}

	$lines[] = "define('SECRET_KEY', " . var_export(post('secret_key'), true) . ');';
	$lines[] = "define('RATE_LIMIT_PER_MINUTE', " . (int) post('rate_limit') . ');';
	$lines[] = "define('AUTO_APPROVE_CLIENTS', " . (post('auto_approve') === '1' ? 'true' : 'false') . ');';
	$lines[] = "define('PEPBAN_VERSION', " . var_export(post('pepban_version'), true) . ');';
	$lines[] = '';

	file_put_contents(__DIR__ . '/../config.local.php', implode("\n", $lines));
	admin_flash('success', 'Settings saved. Reload config by refreshing the page.');
	redirect('/admin/settings');
}

require __DIR__ . '/../templates/admin-layout.php';
?>

<?php foreach (get_flashes() as $f): ?>
<div class="pb-alert pb-alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="pb-card" style="max-width:680px">
	<div class="pb-card-header"><h3>Site Settings</h3></div>
	<div class="pb-card-body">
		<p style="color:#6b7280;margin-bottom:20px">Changes are saved to <code>config.local.php</code> which overrides <code>config.php</code>.</p>
		<form method="post" class="pb-form">
			<?= csrf_field() ?>

			<h4 class="pb-section-title">Database</h4>
			<div class="pb-grid-2">
				<div class="pb-field"><label>DB Host</label><input type="text" name="db_host" value="<?= e(DB_HOST) ?>" required></div>
				<div class="pb-field"><label>DB Name</label><input type="text" name="db_name" value="<?= e(DB_NAME) ?>" required></div>
			</div>
			<div class="pb-grid-2">
				<div class="pb-field"><label>DB User</label><input type="text" name="db_user" value="<?= e(DB_USER) ?>" required></div>
				<div class="pb-field"><label>DB Password <small>(leave blank to keep current)</small></label><input type="password" name="db_pass" autocomplete="new-password" placeholder="••••••••"></div>
			</div>

			<h4 class="pb-section-title">Site</h4>
			<div class="pb-field"><label>Site URL</label><input type="url" name="site_url" value="<?= e(SITE_URL) ?>" required></div>
			<div class="pb-field"><label>Site Name</label><input type="text" name="site_name" value="<?= e(defined('SITE_NAME') ? SITE_NAME : 'PepBan') ?>"></div>
			<div class="pb-field"><label>Plugin Version</label><input type="text" name="pepban_version" value="<?= e(PEPBAN_VERSION) ?>"></div>

			<h4 class="pb-section-title">Admin Login</h4>
			<div class="pb-field"><label>Admin Email</label><input type="email" name="admin_email" value="<?= e(ADMIN_EMAIL) ?>" required></div>
			<div class="pb-field"><label>New Admin Password <small>(leave blank to keep current)</small></label><input type="password" name="admin_password_new" autocomplete="new-password" placeholder="New password…"></div>

			<h4 class="pb-section-title">API &amp; Security</h4>
			<div class="pb-field"><label>Secret Key <small>(64+ hex chars)</small></label><input type="text" name="secret_key" value="<?= e(SECRET_KEY) ?>" required></div>
			<div class="pb-grid-2">
				<div class="pb-field"><label>Rate Limit (requests/minute)</label><input type="number" name="rate_limit" value="<?= e(RATE_LIMIT_PER_MINUTE) ?>" min="1" max="600"></div>
				<div class="pb-field">
					<label>Auto-Approve New Clients</label>
					<select name="auto_approve" class="pb-select">
						<option value="0" <?= !AUTO_APPROVE_CLIENTS ? 'selected' : '' ?>>No — require manual activation</option>
						<option value="1" <?= AUTO_APPROVE_CLIENTS ? 'selected' : '' ?>>Yes — activate on sign-up</option>
					</select>
				</div>
			</div>

			<button type="submit" class="pb-btn pb-btn-primary" style="margin-top:8px">Save Settings</button>
		</form>
	</div>
</div>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
