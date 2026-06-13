<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');

// Only reachable during the post-password TOTP step — not after full login
if (Auth::isAdmin()) redirect('/admin/dashboard');
if (empty($_SESSION['pb_admin_totp_pending'])) redirect('/admin/login');

require_once __DIR__ . '/../includes/Totp.php';

$db       = Database::get();
$totp_row = $db->fetch('SELECT * FROM pepban_admin_totp WHERE id = 1 AND enabled = 1');

// TOTP was disabled between sessions — complete login without it
if (!$totp_row) {
	unset($_SESSION['pb_admin_totp_pending']);
	$ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')[0]);
	Mailer::adminLoginAlert(true, $ip, ADMIN_EMAIL);
	Auth::loginAdmin();
	redirect('/admin/dashboard');
}

$error = false;
$ip    = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')[0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$code = post('totp_code');
	if (Totp::verify($totp_row->secret, $code)) {
		unset($_SESSION['pb_admin_totp_pending']);
		Mailer::adminLoginAlert(true, $ip, ADMIN_EMAIL);
		Auth::loginAdmin();
		redirect('/admin/dashboard');
	}
	// Record the failed attempt (password was already good, so only log here)
	Mailer::adminLoginAlert(false, $ip, ADMIN_EMAIL . ' [TOTP failed]');
	$error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Two-Factor Auth — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="pb-admin-body pb-login-body">
<div class="pb-login-card">
	<div class="pb-login-logo">
		<img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pb-login-logo-img">
	</div>
	<h2 style="text-align:center;margin:0 0 6px;font-size:18px">Two-Factor Authentication</h2>
	<p style="text-align:center;color:#6b7280;font-size:14px;margin:0 0 20px">Enter the 6-digit code from your authenticator app.</p>
	<?php if ($error): ?>
	<div class="pb-alert pb-alert-error">Incorrect code. Please try again.</div>
	<?php endif; ?>
	<form method="post">
		<?= csrf_field() ?>
		<div class="pb-field">
			<label for="totp_code">Authenticator Code</label>
			<input type="text" name="totp_code" id="totp_code"
				inputmode="numeric" pattern="\d{6}" maxlength="6"
				autocomplete="one-time-code" autofocus required
				placeholder="000000"
				style="text-align:center;font-size:1.4em;letter-spacing:.25em">
		</div>
		<button type="submit" class="pb-btn pb-btn-primary pb-btn-full">Verify</button>
	</form>
	<p style="text-align:center;margin-top:16px;font-size:13px;color:#6b7280">
		<a href="<?= url('/admin/login') ?>" style="color:#dc2626">Back to login</a>
	</p>
</div>
</body>
</html>
