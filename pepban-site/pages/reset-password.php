<?php
$token  = get_param('token');
$record = $token ? Auth::verifyResetToken($token) : null;
$errors = [];
$done   = false;

if (!$record) {
	flash('error', 'This reset link is invalid or has expired.');
	redirect('/forgot-password');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$pass  = post('password');
	$pass2 = post('password_confirm');
	if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
	if ($pass !== $pass2)  $errors[] = 'Passwords do not match.';

	if (empty($errors)) {
		Database::get()->update('pepban_clients',
			['password_hash' => password_hash($pass, PASSWORD_DEFAULT)],
			['id' => $record->client_id]
		);
		Auth::deleteResetToken($token);
		flash('success', 'Password updated. You can now log in.');
		redirect('/login');
	}
}

$page_title = 'New Password — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>
<div class="pepban-public">
<div class="pepban-auth-card">
	<h2>Choose a new password</h2>
	<?php if ($errors): ?>
	<div class="pepban-alert pepban-alert-error"><?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?></div>
	<?php endif; ?>
	<form method="post" class="pepban-form">
		<?= csrf_field() ?>
		<div class="pepban-field">
			<label for="password">New Password</label>
			<input type="password" name="password" id="password" minlength="8" required>
		</div>
		<div class="pepban-field">
			<label for="password_confirm">Confirm Password</label>
			<input type="password" name="password_confirm" id="password_confirm" minlength="8" required>
		</div>
		<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full">Set New Password</button>
	</form>
</div>
</div>
<?php require __DIR__ . '/../templates/layout-end.php'; ?>
