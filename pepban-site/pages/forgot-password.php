<?php
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	if (!check_rate_limit('forgot_' . ($_SERVER['REMOTE_ADDR'] ?? ''))) {
		$sent = true; // show generic success to avoid revealing rate limit to attacker
	} else {
	$email  = post('email');
	$client = Database::get()->fetch('SELECT * FROM pepban_clients WHERE owner_email = ?', [$email]);
	if ($client) {
		$token = Auth::createResetToken($client->id);
		Mailer::passwordReset($email, $token);
	}
	// Always show success to prevent email enumeration
	$sent = true;
	} // end rate limit check
}

$page_title = 'Reset Password — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>
<div class="pepban-public">
<div class="pepban-auth-card">
	<h2>Reset your password</h2>
	<?php if ($sent): ?>
		<div class="pepban-alert pepban-alert-success">If that email is on file, a reset link has been sent.</div>
		<p class="pepban-alt-link"><a href="<?= url('/login') ?>">&larr; Back to log in</a></p>
	<?php else: ?>
		<p class="pepban-panel-sub">Enter your email and we'll send a reset link.</p>
		<form method="post" class="pepban-form">
			<?= csrf_field() ?>
			<div class="pepban-field">
				<label for="email">Email Address</label>
				<input type="email" name="email" id="email" required>
			</div>
			<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full">Send Reset Link</button>
		</form>
		<p class="pepban-alt-link"><a href="<?= url('/login') ?>">&larr; Back to log in</a></p>
	<?php endif; ?>
</div>
</div>
<?php require __DIR__ . '/../templates/layout-end.php'; ?>
