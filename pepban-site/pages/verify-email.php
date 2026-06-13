<?php
if (Auth::isClient()) redirect('/portal');

$db    = Database::get();
$token = trim($_GET['token'] ?? '');

// ── Resend flow ───────────────────────────────────────────────────────────────
if (!$token && !empty($_POST['resend_email'])) {
	verify_csrf();
	$resend_email = sanitize_email(post('resend_email'));
	if ($resend_email) {
		$client = $db->fetch(
			'SELECT * FROM pepban_clients WHERE owner_email = ? AND email_verified_at IS NULL',
			[$resend_email]
		);
		if ($client) {
			$new_token = Auth::createEmailToken($client->id);
			Mailer::emailVerification($client, $new_token);
		}
	}
	// Always show success to avoid email enumeration
	$page_title = 'Check Your Email — ' . SITE_NAME;
	require __DIR__ . '/../templates/layout.php';
	?>
	<div class="pepban-public" style="min-height:60vh;display:flex;align-items:center;justify-content:center">
	<div style="max-width:440px;width:100%;text-align:center;padding:40px 20px">
		<div style="font-size:48px;margin-bottom:16px">&#128231;</div>
		<h1 style="font-size:24px;margin:0 0 12px">Verification email sent</h1>
		<p style="color:#6b7280;margin:0 0 24px">If an unverified account exists for that address, we've sent a new verification link. Check your inbox (and spam folder).</p>
		<a href="<?= url('/login') ?>" class="pb-btn pb-btn-ghost">Back to Login</a>
	</div>
	</div>
	<?php
	require __DIR__ . '/../templates/layout-end.php';
	exit;
}

// ── Verify token ──────────────────────────────────────────────────────────────
$verified = false;
$expired  = false;
$client   = null;

if ($token) {
	$client = Auth::verifyEmailToken($token);
	if ($client) {
		// Generate API key now that email is confirmed
		$raw_key = generate_api_key();
		$status  = AUTO_APPROVE_CLIENTS ? 'active' : 'inactive';
		$db->query(
			'UPDATE pepban_clients SET
				api_key_hash = ?, api_key_prefix = ?,
				email_verified_at = NOW(), email_verify_token = NULL, email_verify_expires_at = NULL,
				subscription_status = ?, activated_at = ?
			 WHERE id = ?',
			[
				password_hash($raw_key, PASSWORD_DEFAULT),
				substr($raw_key, 0, 8),
				$status,
				$status === 'active' ? date('Y-m-d H:i:s') : null,
				$client->id,
			]
		);
		$client = $db->fetch('SELECT * FROM pepban_clients WHERE id = ?', [$client->id]);
		Mailer::welcome($client, $raw_key);

		$_SESSION['new_api_key'] = $raw_key;
		Auth::loginClient($client);
		redirect('/portal', ['registered' => '1']);
	} else {
		// Check if it exists but is expired
		$expired = true;
	}
}

$page_title = 'Verify Email — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>
<div class="pepban-public" style="min-height:60vh;display:flex;align-items:center;justify-content:center">
<div style="max-width:480px;width:100%;text-align:center;padding:40px 20px">

<?php if ($expired || ($token && !$client)): ?>
	<div style="font-size:48px;margin-bottom:16px">&#9200;</div>
	<h1 style="font-size:24px;margin:0 0 12px">Link expired</h1>
	<p style="color:#6b7280;margin:0 0 28px">That verification link has expired or already been used. Request a new one below.</p>
	<form method="post" action="<?= url('/verify-email') ?>">
		<?= csrf_field() ?>
		<div class="pepban-field" style="text-align:left">
			<label for="resend_email">Email address</label>
			<input type="email" name="resend_email" id="resend_email" placeholder="your@email.com" required>
		</div>
		<button type="submit" class="pb-btn pb-btn-primary pb-btn-full">Resend Verification Email</button>
	</form>
<?php else: ?>
	<div style="font-size:48px;margin-bottom:16px">&#128683;</div>
	<h1 style="font-size:24px;margin:0 0 12px">Invalid link</h1>
	<p style="color:#6b7280;margin:0 0 24px">This verification link is not valid.</p>
	<a href="<?= url('/signup') ?>" class="pb-btn pb-btn-ghost">Sign Up</a>
<?php endif; ?>

</div>
</div>
<?php require __DIR__ . '/../templates/layout-end.php'; ?>
