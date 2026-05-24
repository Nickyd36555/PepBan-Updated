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
		<svg width="36" height="36" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="10" fill="#2563eb"/><path d="M18 6L8 10.5V19.5C8 24.747 12.477 29.223 18 30C23.523 29.223 28 24.747 28 19.5V10.5L18 6Z" fill="white" fill-opacity=".15" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 18L16.5 20.5L22 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<span>PepBan Admin</span>
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
