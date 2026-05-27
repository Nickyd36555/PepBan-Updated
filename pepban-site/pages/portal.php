<?php
Auth::requireClient();
$client  = Auth::client();
$new_key = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['new_api_key']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'regen_key') {
	verify_csrf();
	if (!isset($_POST['confirm'])) redirect('/portal');
	$raw_key = generate_api_key();
	Database::get()->update('pepban_clients', [
		'api_key_hash'   => password_hash($raw_key, PASSWORD_DEFAULT),
		'api_key_prefix' => substr($raw_key, 0, 8),
		'api_key'        => $raw_key,
	], ['id' => $client->id]);
	Mailer::newKey($client, $raw_key);
	$_SESSION['new_api_key'] = $raw_key;
	flash('info', 'New API key generated. Your old key no longer works — update your store settings.');
	redirect('/portal');
}

$page_title = 'My Portal — ' . SITE_NAME;
require __DIR__ . '/../templates/layout.php';
$initials = strtoupper(substr($client->owner_name, 0, 1));
$status   = $client->subscription_status;
?>

<div class="pepban-public">
<div class="pepban-portal-wrap">

<?php if (isset($_GET['registered'])): ?>
<div class="pepban-alert pepban-alert-success">
	<strong>Welcome to PepBan!</strong> Your account is set up. Check your email for a copy of your API key.
</div>
<?php endif; ?>

<?php if ($new_key): ?>
<div class="pepban-key-reveal">
	<div class="pepban-key-reveal-header">
		<span class="pepban-key-icon">&#128274;</span>
		<div>
			<h3>Your API Key — Copy It Now</h3>
			<p>This is the <strong>only time</strong> this key will be displayed. A copy was emailed to <?= e($client->owner_email) ?>.</p>
		</div>
	</div>
	<div class="pepban-key-reveal-body">
		<div class="pepban-key-box">
			<code id="pepban-key-text"><?= e($new_key) ?></code>
			<button type="button" class="pepban-copy-btn" onclick="pepbanCopyKey(this)">Copy</button>
		</div>
		<p class="pepban-key-note">Enter this in PepBan &rarr; Settings &rarr; API Key on your store.</p>
	</div>
</div>
<script>
function pepbanCopyKey(btn){var t=document.getElementById('pepban-key-text').textContent;function d(){btn.textContent='✓ Copied!';btn.classList.add('pepban-copy-done');}if(navigator.clipboard){navigator.clipboard.writeText(t).then(d);}else{var el=document.createElement('textarea');el.value=t;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el);d();}}
</script>
<?php endif; ?>

<div class="pepban-portal-header">
	<div class="pepban-portal-header-left">
		<div class="pepban-portal-avatar"><?= e($initials) ?></div>
		<div class="pepban-portal-greeting">
			<strong><?= e($client->owner_name) ?></strong>
			<span><?= e($client->owner_email) ?></span>
		</div>
	</div>
	<span class="pepban-portal-status pepban-status-<?= e($status) ?>"><?= e(ucfirst($status)) ?></span>
</div>

<div class="pepban-portal-grid">
	<div class="pepban-card">
		<div class="pepban-card-header">
			<div class="pepban-card-icon">&#127968;</div>
			<h3>Your Store</h3>
		</div>
		<div class="pepban-card-inner">
			<div class="pepban-info-row">
				<span class="pepban-info-label">URL</span>
				<span class="pepban-info-value"><?= e($client->site_url) ?></span>
			</div>
			<div class="pepban-info-row">
				<span class="pepban-info-label">Member since</span>
				<span class="pepban-info-value"><?= date('M j, Y', strtotime($client->created_at)) ?></span>
			</div>
		</div>
	</div>
	<div class="pepban-card">
		<div class="pepban-card-header">
			<div class="pepban-card-icon">&#128273;</div>
			<h3>API Key</h3>
		</div>
		<div class="pepban-card-inner">
			<div class="pepban-info-row">
				<span class="pepban-info-label">API Key</span>
				<span class="pepban-info-value">
					<?php if ($client->api_key): ?>
					<div class="pepban-key-inline">
						<code id="pepban-portal-key"><?= e($client->api_key) ?></code>
						<button type="button" class="pepban-copy-btn pepban-copy-sm" onclick="pepbanCopyPortalKey(this)">Copy</button>
					</div>
					<?php else: ?>
					<code><?= e($client->api_key_prefix) ?>&hellip;</code>
					<small style="display:block;margin-top:4px;color:var(--muted)">Regenerate your key to reveal the full value.</small>
					<?php endif; ?>
				</span>
			</div>
			<div class="pepban-info-row">
				<span class="pepban-info-label">Hub URL</span>
				<span class="pepban-info-value"><code><?= e(SITE_URL) ?></code></span>
			</div>
		</div>
	</div>
</div>

<?php if ($status === 'active'): ?>
<div class="pepban-download-card">
	<h3>&#11015;&nbsp; Download Client Plugin</h3>
	<p>Install this on your WooCommerce store to start blocking banned customers automatically.</p>
	<a href="<?= url('/download/client') ?>" class="pepban-btn pepban-btn-download">
		Get PepBan Plugin
	</a>
</div>
<div class="pepban-steps">
	<h4>Setup Guide</h4>
	<ol>
		<li>Download the zip file above</li>
		<li>In your store: go to <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong></li>
		<li>Upload the zip, click <strong>Install Now</strong>, then <strong>Activate</strong></li>
		<li>Go to <strong>PepBan &rarr; Settings</strong> and enter:<br>
			<strong>Hub URL:</strong> <code><?= e(SITE_URL) ?></code><br>
			<strong>API Key:</strong> your key from above or from your welcome email</li>
		<li>Save &mdash; you should see a green <strong>Connected</strong> status &#10003;</li>
	</ol>
</div>
<?php else: ?>
<div class="pepban-pending-block">
	<span class="pepban-pending-icon">&#9203;</span>
	<h3>Account Pending Activation</h3>
	<p>Your account is awaiting admin approval. You'll get an email the moment it's activated.</p>
</div>
<?php endif; ?>

<div class="pepban-regen-section">
	<h3>Lost your API key?</h3>
	<p>Generate a new one. Your current key stops working immediately — update your store settings right away.</p>
	<form method="post" onsubmit="return confirm('This will invalidate your current key immediately. Continue?')">
		<?= csrf_field() ?>
		<input type="hidden" name="action" value="regen_key">
		<input type="hidden" name="confirm" value="1">
		<button type="submit" class="pepban-btn pepban-btn-secondary">Generate New API Key</button>
	</form>
</div>

<div class="pepban-portal-footer">
	<span>PepBan &copy; <?= date('Y') ?></span>
	<div class="pepban-portal-footer-links">
		<a href="<?= url('/forgot-password') ?>">Change password</a>
		<a href="<?= url('/logout') ?>">Log out</a>
	</div>
</div>

</div>
</div>

<script>
function pepbanCopyPortalKey(btn){var t=document.getElementById('pepban-portal-key').textContent;function d(){btn.textContent='✓ Copied!';btn.classList.add('pepban-copy-done');}if(navigator.clipboard){navigator.clipboard.writeText(t).then(d);}else{var el=document.createElement('textarea');el.value=t;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el);d();}}
</script>
<?php require __DIR__ . '/../templates/layout-end.php'; ?>
