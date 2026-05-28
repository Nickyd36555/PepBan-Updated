<?php
// If already logged in, go to portal
if (Auth::isClient()) redirect('/portal');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();

	$name  = post('name');
	$email = post('email');
	$site  = post('site_url');
	$pass  = post('password');
	$pass2 = post('password_confirm');
	$old   = compact('name', 'email', 'site');

	if (!$name)                           $errors[] = 'Your name is required.';
	if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
	if (!$site  || !filter_var($site,  FILTER_VALIDATE_URL))  $errors[] = 'A valid website URL is required (include https://).';
	if (strlen($pass) < 8)               $errors[] = 'Password must be at least 8 characters.';
	if ($pass !== $pass2)                 $errors[] = 'Passwords do not match.';

	if (empty($errors)) {
		$db = Database::get();

		if ($db->fetch('SELECT id FROM pepban_clients WHERE owner_email = ?', [$email])) {
			$errors[] = 'An account with this email already exists. <a href="' . url('/login') . '">Log in instead</a>.';
		}
	}

	if (empty($errors)) {
		$db       = Database::get();
		$raw_key  = generate_api_key();
		$status   = AUTO_APPROVE_CLIENTS ? 'active' : 'inactive';

		$id = $db->insert('pepban_clients', [
			'owner_name'          => $name,
			'owner_email'         => $email,
			'password_hash'       => password_hash($pass, PASSWORD_DEFAULT),
			'site_url'            => $site,
			'api_key_hash'        => password_hash($raw_key, PASSWORD_DEFAULT),
			'api_key_prefix'      => substr($raw_key, 0, 8),
			'api_key'             => $raw_key,
			'subscription_status' => $status,
			'created_at'          => date('Y-m-d H:i:s'),
			'activated_at'        => $status === 'active' ? date('Y-m-d H:i:s') : null,
			'admin_notes'         => '',
		]);

		$client = $db->fetch('SELECT * FROM pepban_clients WHERE id = ?', [$id]);
		Mailer::welcome($client, $raw_key);
		Mailer::adminNewSignup($client);

		// Store key in session to show once on portal
		$_SESSION['new_api_key'] = $raw_key;

		Auth::loginClient($client);
		redirect('/portal', ['registered' => '1']);
	}
}

$page_title = 'Sign Up — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
?>

<div class="pepban-public">
<div class="pepban-signup-layout">

	<div class="pepban-signup-brand">
		<div class="pepban-brand-logo">
			<img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pepban-signup-logo">
		</div>
		<p class="pepban-brand-tagline">Protect your store from repeat offenders</p>
		<p class="pepban-brand-sub">Join the network of peptide stores sharing a centralized ban list. One sign-up, instant protection.</p>
		<ul class="pepban-benefit-list">
			<li>Shared database across all member stores</li>
			<li>Automatic checkout blocking</li>
			<li>Report bad customers in one click</li>
			<li>Per-site whitelist control</li>
			<li>Real-time API — zero slowdown</li>
		</ul>
	</div>

	<div class="pepban-signup-form-panel">
		<h2>Create your account</h2>
		<p class="pepban-panel-sub">Get your API key instantly.</p>

		<?php if ($errors): ?>
		<div class="pepban-alert pepban-alert-error">
			<?php foreach ($errors as $e): ?>
				<p><?= $e ?></p>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<form method="post" class="pepban-form" novalidate>
			<?= csrf_field() ?>
			<div class="pepban-field">
				<label for="name">Full Name <span class="pepban-req">*</span></label>
				<input type="text" name="name" id="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Jane Smith" required>
			</div>
			<div class="pepban-field">
				<label for="email">Email Address <span class="pepban-req">*</span></label>
				<input type="email" name="email" id="email" value="<?= e($old['email'] ?? '') ?>" placeholder="jane@yourstore.com" required>
			</div>
			<div class="pepban-field">
				<label for="site_url">Your Store URL <span class="pepban-req">*</span></label>
				<input type="url" name="site_url" id="site_url" value="<?= e($old['site'] ?? '') ?>" placeholder="https://your-peptide-store.com" required>
				<small>The WooCommerce site you'll install the PepBan Client plugin on.</small>
			</div>
			<div class="pepban-field">
				<label for="password">Password <span class="pepban-req">*</span></label>
				<input type="password" name="password" id="password" minlength="8" required>
				<small>Minimum 8 characters</small>
			</div>
			<div class="pepban-field">
				<label for="password_confirm">Confirm Password <span class="pepban-req">*</span></label>
				<input type="password" name="password_confirm" id="password_confirm" minlength="8" required>
			</div>
			<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full" style="margin-top:6px">
				Create Account &amp; Get API Key
			</button>
		</form>

		<p class="pepban-alt-link">Already have an account? <a href="<?= url('/login') ?>">Sign in</a></p>
	</div>

</div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
