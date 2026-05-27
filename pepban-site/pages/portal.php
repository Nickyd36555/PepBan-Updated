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

<div class="pepban-card pepban-card-wide">
	<div class="pepban-card-header">
		<div class="pepban-card-icon">&#127968;</div>
		<h3>Your Store</h3>
	</div>
	<div class="pepban-card-inner">
		<div class="pepban-info-row">
			<span class="pepban-info-label">Store URL</span>
			<span class="pepban-info-value"><?= e($client->site_url) ?></span>
		</div>
		<div class="pepban-info-row">
			<span class="pepban-info-label">Member since</span>
			<span class="pepban-info-value"><?= date('M j, Y', strtotime($client->created_at)) ?></span>
		</div>
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
	</div>
</div>

<?php
$total_reports  = Database::get()->scalar('SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ?', [$client->id]);
$month_reports  = Database::get()->scalar('SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ? AND date_reported >= ?', [$client->id, date('Y-m-01')]);
$recent_reports = Database::get()->fetchAll('SELECT r.date_reported, r.reason, b.email, b.first_name, b.last_name FROM pepban_ban_reports r JOIN pepban_banned_customers b ON b.id = r.customer_id WHERE r.client_id = ? ORDER BY r.date_reported DESC LIMIT 10', [$client->id]);
?>

<div class="pepban-card pepban-card-wide" style="margin-top:20px">
    <div class="pepban-card-header">
        <div class="pepban-card-icon">&#128202;</div>
        <h3>Network Activity</h3>
    </div>
    <div class="pepban-card-inner">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
            <div style="background:var(--surface-2,#f8f8fa);border-radius:10px;padding:16px;text-align:center">
                <div style="font-size:2rem;font-weight:700;color:var(--accent,#7c3aed)"><?= (int)$total_reports ?></div>
                <div style="font-size:.85rem;color:var(--muted)">Total Reports Submitted</div>
            </div>
            <div style="background:var(--surface-2,#f8f8fa);border-radius:10px;padding:16px;text-align:center">
                <div style="font-size:2rem;font-weight:700;color:var(--accent,#7c3aed)"><?= (int)$month_reports ?></div>
                <div style="font-size:.85rem;color:var(--muted)">This Month</div>
            </div>
        </div>
    </div>
    <?php if ($recent_reports): ?>
    <div class="pepban-card-body-flush">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="border-bottom:2px solid var(--border,#e5e7eb)">
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--muted)">Email</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--muted)">Name</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--muted)">Reason</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--muted)">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_reports as $r): ?>
                <tr style="border-bottom:1px solid var(--border,#e5e7eb)">
                    <td style="padding:10px 16px"><?= e($r->email) ?></td>
                    <td style="padding:10px 16px"><?= e(trim($r->first_name . ' ' . $r->last_name)) ?></td>
                    <td style="padding:10px 16px"><?= e($r->reason) ?></td>
                    <td style="padding:10px 16px;white-space:nowrap"><?= date('M j, Y', strtotime($r->date_reported)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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
		<li>Go to <strong>PepBan &rarr; Settings</strong> and enter your <strong>API Key</strong> from above</li>
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
		<a href="<?= url('/account') ?>">Account settings</a>
		<a href="<?= url('/logout') ?>">Log out</a>
	</div>
</div>

</div>
</div>

<script>
function pepbanCopyPortalKey(btn){var t=document.getElementById('pepban-portal-key').textContent;function d(){btn.textContent='✓ Copied!';btn.classList.add('pepban-copy-done');}if(navigator.clipboard){navigator.clipboard.writeText(t).then(d);}else{var el=document.createElement('textarea');el.value=t;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el);d();}}
</script>
<?php require __DIR__ . '/../templates/layout-end.php'; ?>
