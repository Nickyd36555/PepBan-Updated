<?php
if (Auth::isAdmin()) redirect('/admin/dashboard');

$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$email = post('email');
	$pass  = post('password');
	if ($email === ADMIN_EMAIL && password_verify($pass, ADMIN_PASSWORD_HASH)) {
		Auth::loginAdmin();
		redirect('/admin/dashboard');
	}
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
	<?php if ($error): ?>
	<div class="pb-alert pb-alert-error">Incorrect email or password.</div>
	<?php endif; ?>
	<form method="post">
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
