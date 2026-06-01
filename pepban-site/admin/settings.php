<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$page_title = 'Settings';

$test_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$act = post('_action');

	// ── Save settings (and optionally test email) ─────────────────────────────
	if (in_array($act, ['save', 'save_and_test'], true)) {
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
		$lines[] = "define('SUPPORT_EMAIL', " . var_export(post('support_email'), true) . ');';

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

		// SMTP
		$lines[] = "define('MAIL_FROM', " . var_export(post('mail_from'), true) . ');';
		$lines[] = "define('MAIL_FROM_NAME', " . var_export(post('mail_from_name'), true) . ');';
		$lines[] = "define('SMTP_HOST', " . var_export(post('smtp_host'), true) . ');';
		$lines[] = "define('SMTP_PORT', " . (int) post('smtp_port') . ');';
		$lines[] = "define('SMTP_SECURE', " . var_export(post('smtp_secure'), true) . ');';
		$lines[] = "define('SMTP_USER', " . var_export(post('smtp_user'), true) . ');';

		$new_smtp_pass = post('smtp_pass');
		if ($new_smtp_pass !== '') {
			$lines[] = "define('SMTP_PASS', " . var_export($new_smtp_pass, true) . ');';
		} else {
			$lines[] = "define('SMTP_PASS', " . var_export(SMTP_PASS, true) . ');';
		}

		file_put_contents(__DIR__ . '/../config.local.php', implode("\n", $lines));

		if ($act === 'save_and_test') {
			// Re-load the constants we just wrote so the test uses the new SMTP config.
			// Parse var_export() output safely — no eval().
			foreach ($lines as $line) {
				if (preg_match("/define\('(SMTP_HOST|SMTP_PORT|SMTP_USER|SMTP_PASS|SMTP_SECURE|MAIL_FROM|MAIL_FROM_NAME)',\s*(.+)\);/", $line, $m)) {
					if (!defined($m[1])) {
						$raw = trim($m[2]);
						if (preg_match("/^'(.*)'$/s", $raw, $qm)) {
							$val = str_replace("\\'", "'", $qm[1]);
						} elseif (is_numeric($raw)) {
							$val = (int) $raw;
						} elseif ($raw === 'true') {
							$val = true;
						} elseif ($raw === 'false') {
							$val = false;
						} else {
							$val = $raw;
						}
						define($m[1], $val);
					}
				}
			}
			$to = post('test_to') ?: ADMIN_EMAIL;
			$ok = Mailer::send($to, 'PepBan — Test Email', "This is a test email from your PepBan admin panel.\n\nIf you receive this, SMTP is working correctly.\n\n— PepBan");
			$test_result = $ok
				? ['type' => 'success', 'msg' => "Settings saved. Test email sent to {$to} — check your inbox (and spam folder)."]
				: ['type' => 'error',   'msg' => "Settings saved, but the test email failed. Check the PHP error log for a PepBan SMTP: line."];
		} else {
			admin_flash('success', 'Settings saved.');
			redirect('/admin/settings');
		}
	}
}

require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-card" style="max-width:680px">
	<div class="pb-card-header"><h3>Site Settings</h3></div>
	<div class="pb-card-body">
		<p style="color:#6b7280;margin-bottom:20px">Changes are saved to <code>config.local.php</code> which overrides <code>config.php</code>.</p>
		<form method="post" class="pb-form">
			<?= csrf_field() ?>
			<input type="hidden" name="_action" value="save" id="settings-action">

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
			<div class="pb-grid-2">
				<div class="pb-field"><label>Admin Email</label><input type="email" name="admin_email" value="<?= e(ADMIN_EMAIL) ?>" required></div>
				<div class="pb-field"><label>Support Email</label><input type="email" name="support_email" value="<?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?>"></div>
			</div>
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

			<h4 class="pb-section-title">Email / SMTP</h4>
			<div class="pb-grid-2">
				<div class="pb-field"><label>From Name</label><input type="text" name="mail_from_name" value="<?= e(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'PepBan') ?>" placeholder="PepBan"></div>
				<div class="pb-field"><label>From Address <small>(reply-to)</small></label><input type="email" name="mail_from" value="<?= e(defined('MAIL_FROM') ? MAIL_FROM : '') ?>" placeholder="noreply@pepban.com"></div>
			</div>
			<div class="pb-grid-2">
				<div class="pb-field"><label>SMTP Host</label><input type="text" name="smtp_host" value="<?= e(defined('SMTP_HOST') ? SMTP_HOST : '') ?>" placeholder="smtp.gmail.com"></div>
				<div class="pb-field"><label>SMTP Port</label><input type="number" name="smtp_port" value="<?= e(defined('SMTP_PORT') ? SMTP_PORT : 587) ?>" placeholder="587"></div>
			</div>
			<div class="pb-grid-2">
				<div class="pb-field"><label>SMTP Username</label><input type="text" name="smtp_user" value="<?= e(defined('SMTP_USER') ? SMTP_USER : '') ?>" placeholder="you@gmail.com"></div>
				<div class="pb-field"><label>SMTP Password <small>(leave blank to keep current)</small></label><input type="password" name="smtp_pass" autocomplete="new-password" placeholder="App password…"></div>
			</div>
			<div class="pb-field">
				<label>SMTP Security</label>
				<select name="smtp_secure" class="pb-select">
					<option value="tls" <?= (defined('SMTP_SECURE') && SMTP_SECURE === 'tls') ? 'selected' : '' ?>>TLS / STARTTLS (port 587)</option>
					<option value="ssl" <?= (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') ? 'selected' : '' ?>>SSL (port 465)</option>
				</select>
			</div>

			<?php if ($test_result): ?>
				<div class="pb-alert pb-alert-<?= e($test_result['type']) ?>" style="margin:12px 0"><?= e($test_result['msg']) ?></div>
			<?php endif; ?>

			<div style="display:flex;gap:10px;align-items:flex-end;margin-top:16px;flex-wrap:wrap">
				<button type="submit" class="pb-btn pb-btn-primary">Save Settings</button>
				<div style="display:flex;gap:8px;align-items:center;flex:1;min-width:200px">
					<input type="email" name="test_to" placeholder="<?= e(ADMIN_EMAIL) ?>"
					       style="flex:1;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem">
					<button type="submit" class="pb-btn pb-btn-secondary" style="white-space:nowrap"
					        onclick="document.getElementById('settings-action').value='save_and_test'">
						Save &amp; Test Email
					</button>
				</div>
			</div>
			<p style="color:#6b7280;font-size:.8rem;margin-top:6px">Enter an email address and click "Save &amp; Test Email" to save settings and send a test in one step.</p>
		</form>
	</div>
</div>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
