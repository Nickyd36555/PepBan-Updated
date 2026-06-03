<?php
Auth::requireClient();
$client  = Auth::client();
$new_key = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['new_api_key']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'send_feedback') {
	verify_csrf();
	$msg = trim(post('feedback_message'));
	if (strlen($msg) >= 10) {
		Database::get()->insert('pepban_feedback', [
			'client_id'    => $client->id,
			'client_email' => $client->owner_email,
			'message'      => $msg,
			'created_at'   => date('Y-m-d H:i:s'),
		]);
		Mailer::adminFeedback($client->owner_name, $client->owner_email, $client->site_url, $msg);
		flash('success', 'Thanks for your feedback!');
	} else {
		flash('error', 'Message too short — please write at least 10 characters.');
	}
	redirect('/portal');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'regen_key') {
	verify_csrf();
	if (!isset($_POST['confirm'])) redirect('/portal');
	$raw_key = generate_api_key();
	Database::get()->update('pepban_clients', [
		'api_key_hash'   => password_hash($raw_key, PASSWORD_DEFAULT),
		'api_key_prefix' => substr($raw_key, 0, 8),
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
				<code><?= e($client->api_key_prefix) ?>&hellip;</code>
				<small style="display:block;margin-top:4px;color:var(--muted)">Regenerate your key below to reveal the full value.</small>
			</span>
		</div>
	</div>
</div>

<?php
$rp_per   = 10;
$rp_page  = max(1, (int) get_param('rp', '1'));
$rp_total = (int) Database::get()->scalar('SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ?', [$client->id]);
$rp_pages = max(1, (int) ceil($rp_total / $rp_per));
$rp_page  = min($rp_page, $rp_pages);
$rp_offset = ($rp_page - 1) * $rp_per;

$total_reports  = $rp_total;
$month_reports  = (int) Database::get()->scalar('SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ? AND date_reported >= ?', [$client->id, date('Y-m-01')]);
$recent_reports = Database::get()->fetchAll(
	'SELECT r.date_reported, r.reason, b.email, b.first_name, b.last_name
	 FROM pepban_ban_reports r
	 JOIN pepban_banned_customers b ON b.id = r.customer_id
	 WHERE r.client_id = ? ORDER BY r.date_reported DESC LIMIT ' . $rp_per . ' OFFSET ' . $rp_offset,
	[$client->id]
);
?>

<div class="pepban-card pepban-card-wide" style="margin-top:20px">
	<div class="pepban-card-header">
		<div class="pepban-card-icon">&#128202;</div>
		<h3>Network Activity</h3>
	</div>
	<div class="pepban-card-inner">
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:4px">
			<div style="background:var(--bg-card-hover);border:1px solid var(--border-light);border-radius:10px;padding:20px 16px;text-align:center">
				<div style="font-size:2rem;font-weight:700;color:var(--text)"><?= $total_reports ?></div>
				<div style="font-size:.82rem;color:var(--text-muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em">Total Reports</div>
			</div>
			<div style="background:var(--bg-card-hover);border:1px solid var(--border-light);border-radius:10px;padding:20px 16px;text-align:center">
				<div style="font-size:2rem;font-weight:700;color:var(--text)"><?= $month_reports ?></div>
				<div style="font-size:.82rem;color:var(--text-muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em">This Month</div>
			</div>
		</div>
	</div>
	<?php if ($recent_reports): ?>
	<div class="pepban-card-body-flush">
		<table style="width:100%;border-collapse:collapse;font-size:.88rem">
			<thead>
				<tr style="border-bottom:1px solid var(--border)">
					<th style="padding:10px 24px;text-align:left;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Email</th>
					<th style="padding:10px 16px;text-align:left;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Name</th>
					<th style="padding:10px 16px;text-align:left;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Reason</th>
					<th style="padding:10px 24px;text-align:left;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Date</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($recent_reports as $r): ?>
				<tr style="border-bottom:1px solid var(--border)">
					<td style="padding:10px 24px;color:var(--text)"><?= e($r->email) ?></td>
					<td style="padding:10px 16px;color:var(--text-muted)"><?= e(trim($r->first_name . ' ' . $r->last_name)) ?: '—' ?></td>
					<td style="padding:10px 16px;color:var(--text-muted)"><?= e($r->reason) ?: '—' ?></td>
					<td style="padding:10px 24px;color:var(--text-muted);white-space:nowrap"><?= date('M j, Y', strtotime($r->date_reported)) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ($rp_pages > 1): ?>
		<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 24px;border-top:1px solid var(--border)">
			<span style="font-size:.82rem;color:var(--text-muted)">Page <?= $rp_page ?> of <?= $rp_pages ?></span>
			<div style="display:flex;gap:8px">
				<?php if ($rp_page > 1): ?>
				<a href="?rp=<?= $rp_page - 1 ?>#activity" class="pepban-btn pepban-btn-secondary" style="padding:5px 14px;font-size:.8rem">&larr; Prev</a>
				<?php endif; ?>
				<?php if ($rp_page < $rp_pages): ?>
				<a href="?rp=<?= $rp_page + 1 ?>#activity" class="pepban-btn pepban-btn-secondary" style="padding:5px 14px;font-size:.8rem">Next &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php elseif ($rp_page === 1): ?>
	<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:.9rem">No reports submitted yet.</div>
	<?php endif; ?>
</div>
<span id="activity"></span>

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
	<h3>Share Feedback</h3>
	<p>Have a feature request, bug report, or general comment? We read everything.</p>
	<form method="post">
		<?= csrf_field() ?>
		<input type="hidden" name="action" value="send_feedback">
		<textarea name="feedback_message" rows="4" placeholder="Your message…" style="width:100%;box-sizing:border-box;background:var(--bg-card-hover);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:12px;font-size:.9rem;resize:vertical;margin-bottom:10px"></textarea>
		<button type="submit" class="pepban-btn pepban-btn-secondary">Send Feedback</button>
	</form>
</div>

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

<div class="pepban-regen-section" id="changelog" style="padding-bottom:0">
	<h3>What's New</h3>
	<p>Latest updates to the PepBan plugin.</p>
</div>
<?php
$tag_css     = ['New' => 'new', 'Fix' => 'fix', 'Improved' => 'improvement'];
$_cl_all     = pepban_plugin_changelog();
$_cl_per     = 10;
$_cl_total   = count($_cl_all);
$_cl_pages   = (int) ceil($_cl_total / $_cl_per);
$_cl_page    = max(1, min($_cl_pages, (int) ($_GET['cl_page'] ?? 1)));
$_cl_slice   = array_slice($_cl_all, ($_cl_page - 1) * $_cl_per, $_cl_per);

foreach ($_cl_slice as $release):
?>
<div class="pepban-regen-section" style="padding-top:16px;padding-bottom:16px;border-top:1px solid var(--border)">
	<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
		<span style="font-weight:700;font-size:1rem;color:var(--text)">v<?= e($release['version']) ?></span>
		<?php if (!empty($release['latest'])): ?>
		<span style="background:var(--red);color:#fff;font-size:.7rem;font-weight:700;letter-spacing:.06em;padding:2px 8px;border-radius:20px;text-transform:uppercase">Latest</span>
		<?php endif; ?>
		<span style="font-size:.82rem;color:var(--text-muted)"><?= e($release['date']) ?></span>
	</div>
	<ul style="margin:0;padding-left:0;list-style:none;display:flex;flex-direction:column;gap:7px">
		<?php foreach ($release['items'] as $item): ?>
		<li style="display:flex;align-items:flex-start;gap:8px;font-size:.88rem;color:var(--text-muted)">
			<span style="flex-shrink:0;font-size:.7rem;font-weight:700;letter-spacing:.07em;padding:2px 7px;border-radius:4px;margin-top:1px;
				<?php if ($item['tag']==='New'): ?>background:rgba(34,197,94,.12);color:#4ade80;
				<?php elseif ($item['tag']==='Fix'): ?>background:rgba(239,68,68,.1);color:#f87171;
				<?php else: ?>background:rgba(99,102,241,.12);color:#818cf8;<?php endif; ?>
			"><?= e($item['tag']) ?></span>
			<?= $item['text'] ?>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endforeach; ?>

<?php if ($_cl_pages > 1): ?>
<div class="pepban-regen-section" style="padding-top:20px;padding-bottom:20px;border-top:1px solid var(--border)">
	<div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
		<?php if ($_cl_page > 1): ?>
		<a href="<?= url('/portal?cl_page=' . ($_cl_page - 1) . '#changelog') ?>"
		   style="display:inline-flex;align-items:center;gap:6px;font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:6px 14px;border:1px solid var(--border);border-radius:8px;transition:border-color .15s,color .15s"
		   onmouseover="this.style.borderColor='var(--red)';this.style.color='var(--red)'"
		   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">
			&#8592; Newer
		</a>
		<?php else: ?>
		<span></span>
		<?php endif; ?>

		<span style="font-size:.82rem;color:var(--text-muted)">Page <?= $_cl_page ?> of <?= $_cl_pages ?></span>

		<?php if ($_cl_page < $_cl_pages): ?>
		<a href="<?= url('/portal?cl_page=' . ($_cl_page + 1) . '#changelog') ?>"
		   style="display:inline-flex;align-items:center;gap:6px;font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:6px 14px;border:1px solid var(--border);border-radius:8px;transition:border-color .15s,color .15s"
		   onmouseover="this.style.borderColor='var(--red)';this.style.color='var(--red)'"
		   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">
			Older &#8594;
		</a>
		<?php else: ?>
		<span></span>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<div class="pepban-portal-footer">
	<span>PepBan &copy; <?= date('Y') ?></span>
	<div class="pepban-portal-footer-links">
		<a href="<?= url('/account') ?>">Account settings</a>
		<a href="<?= url('/logout') ?>">Log out</a>
	</div>
</div>

</div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
