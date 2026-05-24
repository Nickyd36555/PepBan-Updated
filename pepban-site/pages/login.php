<?php
if (Auth::isClient()) redirect('/portal');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$email = post('email');
	$pass  = post('password');

	$client = Database::get()->fetch('SELECT * FROM pepban_clients WHERE owner_email = ?', [$email]);
	if ($client && password_verify($pass, $client->password_hash)) {
		Auth::loginClient($client);
		$next = get_param('next', '/portal');
		redirect($next);
	} else {
		$errors[] = 'Incorrect email or password.';
	}
}

$page_title = 'Log In — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>

<div class="pepban-public">
<div class="pepban-auth-card">
	<div class="pepban-auth-logo">
		<svg width="40" height="40" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="10" fill="#2563eb"/><path d="M18 6L8 10.5V19.5C8 24.747 12.477 29.223 18 30C23.523 29.223 28 24.747 28 19.5V10.5L18 6Z" fill="white" fill-opacity=".15" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 18L16.5 20.5L22 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</div>
	<h2>Welcome back</h2>
	<p class="pepban-panel-sub">Log in to your PepBan portal</p>

	<?php if ($errors): ?>
	<div class="pepban-alert pepban-alert-error">
		<?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?>
	</div>
	<?php endif; ?>

	<form method="post" class="pepban-form" novalidate>
		<?= csrf_field() ?>
		<div class="pepban-field">
			<label for="email">Email</label>
			<input type="email" name="email" id="email" autocomplete="email" required>
		</div>
		<div class="pepban-field">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" autocomplete="current-password" required>
		</div>
		<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full">Log In</button>
	</form>

	<p class="pepban-alt-link"><a href="<?= url('/forgot-password') ?>">Forgot your password?</a></p>
	<p class="pepban-alt-link">No account? <a href="<?= url('/signup') ?>">Sign up</a></p>
</div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
