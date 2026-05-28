<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
if (Auth::isAdmin()) redirect('/admin/dashboard');

$visitor_ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')[0]);
$locked_out = !login_rate_limit($visitor_ip);
$error = false;

if (!$locked_out && $_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$email = post('email');
	$pass  = post('password');
	if ($email === ADMIN_EMAIL && password_verify($pass, ADMIN_PASSWORD_HASH)) {
		Auth::loginAdmin();
		redirect('/admin/dashboard');
	}
	login_rate_limit($visitor_ip, true); // record failure
	$error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="pb-admin-body pb-login-body">
<div class="pb-login-card">
	<div class="pb-login-logo">
		<img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pb-login-logo-img">
	</div>
	<?php if ($locked_out): ?>
	<div class="pb-alert pb-alert-error">Too many failed attempts. Please wait 15 minutes.</div>
	<?php elseif ($error): ?>
	<div class="pb-alert pb-alert-error">Incorrect email or password.</div>
	<?php endif; ?>
	<form method="post" <?= $locked_out ? 'onsubmit="return false"' : '' ?>>
		<?= csrf_field() ?>
		<div class="pb-field">
			<label for="email">Email</label>
			<input type="email" name="email" id="email" autocomplete="email" required>
		</div>
		<div class="pb-field">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" autocomplete="current-password" required>
		</div>
		<button type="submit" class="pb-btn pb-btn-primary pb-btn-full">Log In</button>
	</form>
</div>
</body>
</html>
