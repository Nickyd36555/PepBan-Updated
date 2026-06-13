<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
Auth::requireAdmin();

require_once __DIR__ . '/../includes/Totp.php';

$db       = Database::get();
$totp_row = $db->fetch('SELECT * FROM pepban_admin_totp WHERE id = 1');
$enabled  = (bool) ($totp_row->enabled ?? false);
$secret   = $totp_row->secret ?? '';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$action = post('totp_action');

	if ($action === 'enable') {
		$new_secret = post('totp_secret');
		$code       = preg_replace('/\D/', '', post('totp_code'));
		if (!$new_secret || !Totp::verify($new_secret, $code)) {
			$error = 'Verification failed — make sure your authenticator app is scanning the right code and try again.';
		} else {
			$db->query('UPDATE pepban_admin_totp SET secret = ?, enabled = 1, setup_at = NOW() WHERE id = 1', [$new_secret]);
			$enabled = true;
			$secret  = $new_secret;
			$success = 'Two-factor authentication is now enabled. You will need your authenticator app on every login.';
		}
	} elseif ($action === 'disable') {
		$code = preg_replace('/\D/', '', post('totp_code'));
		if (!$secret || !Totp::verify($secret, $code)) {
			$error = 'Incorrect code. TOTP has not been disabled.';
		} else {
			$db->query("UPDATE pepban_admin_totp SET secret = '', enabled = 0, setup_at = NULL WHERE id = 1");
			$enabled = false;
			$secret  = '';
			$success = 'Two-factor authentication has been disabled.';
		}
	}
}

// Generate a provisional secret for the setup QR (reuse existing if present, generate new if empty)
$provisional = ($secret) ? $secret : Totp::generateSecret();
$otp_uri     = Totp::getUri($provisional, 'PepBan Admin', 'PepBan');
// Format secret in groups of 4 for readability
$secret_display = implode(' ', str_split($provisional, 4));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Two-Factor Auth — <?= e(SITE_NAME) ?> Admin</title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<style>
.totp-card{max-width:520px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:40px}
.totp-card h1{margin:0 0 6px;font-size:20px;color:#111827}
.totp-card .sub{margin:0 0 28px;color:#6b7280;font-size:14px}
.qr-wrap{text-align:center;margin:0 0 24px}
#qrcode canvas,#qrcode img{border-radius:8px;border:6px solid #fff;box-shadow:0 0 0 1px #e5e7eb}
.secret-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 18px;text-align:center;margin:0 0 24px;font-family:'SF Mono',Menlo,Monaco,Consolas,monospace;font-size:15px;letter-spacing:.12em;color:#111827;word-break:break-all}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:20px}
.status-on{background:#f0fdf4;color:#166534}
.status-off{background:#fef2f2;color:#991b1b}
</style>
</head>
<body class="pb-admin-body">
<div class="totp-card">
	<a href="<?= url('/admin/settings') ?>" style="font-size:13px;color:#6b7280;text-decoration:none;display:block;margin-bottom:20px">&larr; Back to Settings</a>
	<h1>Two-Factor Authentication</h1>
	<p class="sub">Require a code from your authenticator app every time you log in to the admin panel.</p>

	<?php if ($success): ?>
	<div class="pb-alert" style="background:#f0fdf4;border-color:#22c55e;color:#166534;margin-bottom:24px"><?= e($success) ?></div>
	<?php endif; ?>
	<?php if ($error): ?>
	<div class="pb-alert pb-alert-error" style="margin-bottom:24px"><?= e($error) ?></div>
	<?php endif; ?>

	<div>
		<span class="status-badge <?= $enabled ? 'status-on' : 'status-off' ?>">
			<?php if ($enabled): ?>&#10003; Enabled<?php else: ?>&#10007; Disabled<?php endif; ?>
		</span>
	</div>

	<?php if (!$enabled): ?>
	<!-- ── Setup form ───────────────────────────────────────────────────── -->
	<p style="margin:0 0 16px;font-size:14px;color:#374151">
		<strong>Step 1</strong> — Scan the QR code with Google Authenticator, Authy, or any TOTP app.
	</p>
	<div class="qr-wrap">
		<div id="qrcode"></div>
	</div>
	<p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-align:center">Or enter the secret manually:</p>
	<div class="secret-box"><?= e($secret_display) ?></div>

	<p style="margin:0 0 16px;font-size:14px;color:#374151">
		<strong>Step 2</strong> — Enter the 6-digit code shown in your app to confirm setup.
	</p>
	<form method="post">
		<?= csrf_field() ?>
		<input type="hidden" name="totp_action" value="enable">
		<input type="hidden" name="totp_secret" value="<?= e($provisional) ?>">
		<div class="pb-field">
			<label for="totp_code">Verification Code</label>
			<input type="text" name="totp_code" id="totp_code"
				inputmode="numeric" pattern="\d{6}" maxlength="6"
				autocomplete="one-time-code" autofocus required
				placeholder="000000"
				style="text-align:center;font-size:1.3em;letter-spacing:.2em">
		</div>
		<button type="submit" class="pb-btn pb-btn-primary pb-btn-full">Enable Two-Factor Auth</button>
	</form>

	<?php else: ?>
	<!-- ── Disable form ─────────────────────────────────────────────────── -->
	<p style="margin:0 0 20px;font-size:14px;color:#374151">
		Two-factor authentication is protecting your admin login. To disable it, enter a valid code from your authenticator app.
	</p>
	<form method="post">
		<?= csrf_field() ?>
		<input type="hidden" name="totp_action" value="disable">
		<div class="pb-field">
			<label for="totp_code">Current Authenticator Code</label>
			<input type="text" name="totp_code" id="totp_code"
				inputmode="numeric" pattern="\d{6}" maxlength="6"
				autocomplete="one-time-code" required
				placeholder="000000"
				style="text-align:center;font-size:1.3em;letter-spacing:.2em">
		</div>
		<button type="submit" class="pb-btn pb-btn-full" style="background:#6b7280;color:#fff">Disable Two-Factor Auth</button>
	</form>
	<?php endif; ?>
</div>

<?php if (!$enabled): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), {
	text:   <?= json_encode($otp_uri) ?>,
	width:  200,
	height: 200,
	colorDark:  '#0c0c1e',
	colorLight: '#ffffff',
	correctLevel: QRCode.CorrectLevel.M,
});
</script>
<?php endif; ?>
</body>
</html>
