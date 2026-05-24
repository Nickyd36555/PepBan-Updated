<?php
$db     = Database::get();
$action = get_param('action', 'list');
$id     = (int) get_param('id');

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$act = post('_action');

	if ($act === 'activate' && $id) {
		$db->update('pepban_clients',
			['subscription_status' => 'active', 'activated_at' => date('Y-m-d H:i:s')],
			['id' => $id]
		);
		$client = $db->fetch('SELECT * FROM pepban_clients WHERE id = ?', [$id]);
		if ($client) {
			require_once __DIR__ . '/../includes/Mailer.php';
			Mailer::activated($client);
		}
		flash('success', 'Client activated.');
		redirect('/admin/clients?action=view&id=' . $id);
	}

	if ($act === 'deactivate' && $id) {
		$db->update('pepban_clients',
			['subscription_status' => 'inactive'],
			['id' => $id]
		);
		flash('success', 'Client deactivated.');
		redirect('/admin/clients?action=view&id=' . $id);
	}

	if ($act === 'suspend' && $id) {
		$db->update('pepban_clients',
			['subscription_status' => 'suspended'],
			['id' => $id]
		);
		flash('success', 'Client suspended.');
		redirect('/admin/clients?action=view&id=' . $id);
	}

	if ($act === 'regen_key' && $id) {
		$client = $db->fetch('SELECT * FROM pepban_clients WHERE id = ?', [$id]);
		if ($client) {
			$raw_key = generate_api_key();
			$db->update('pepban_clients', [
				'api_key_hash'   => password_hash($raw_key, PASSWORD_DEFAULT),
				'api_key_prefix' => substr($raw_key, 0, 8),
			], ['id' => $id]);
			require_once __DIR__ . '/../includes/Mailer.php';
			Mailer::newKey($client, $raw_key);
			flash('success', 'API key regenerated and emailed to ' . $client->owner_email . '.');
		}
		redirect('/admin/clients?action=view&id=' . $id);
	}

	if ($act === 'delete' && $id) {
		$db->query('DELETE FROM pepban_whitelists WHERE client_id = ?', [$id]);
		$db->delete('pepban_clients', ['id' => $id]);
		flash('success', 'Client permanently deleted.');
		redirect('/admin/clients');
	}

	if ($act === 'update_notes' && $id) {
		$db->update('pepban_clients',
			['admin_notes' => post('admin_notes')],
			['id' => $id]
		);
		flash('success', 'Notes saved.');
		redirect('/admin/clients?action=view&id=' . $id);
	}
}

// ── View single client ────────────────────────────────────────────────────────
if ($action === 'view' && $id) {
	$client = $db->fetch('SELECT * FROM pepban_clients WHERE id = ?', [$id]);
	if (!$client) {
		flash('error', 'Client not found.');
		redirect('/admin/clients');
	}

	$reports_count     = (int) $db->scalar('SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ?', [$id]);
	$whitelists_count  = (int) $db->scalar('SELECT COUNT(*) FROM pepban_whitelists WHERE client_id = ?', [$id]);
	$recent_reports    = $db->fetchAll(
		'SELECT r.*, b.email as customer_email FROM pepban_ban_reports r
		 LEFT JOIN pepban_banned_customers b ON r.customer_id = b.id
		 WHERE r.client_id = ? ORDER BY r.date_reported DESC LIMIT 10',
		[$id]
	);

	$status_map = [
		'active'    => 'Active',
		'inactive'  => 'Inactive',
		'suspended' => 'Suspended',
		'pending'   => 'Pending',
	];

	$page_title = 'Client: ' . $client->owner_name;
	require __DIR__ . '/../templates/admin-layout.php';
	?>
	<a href="<?= url('/admin/clients') ?>" class="pb-back-link">&larr; All clients</a>

	<?php foreach (get_flashes() as $f): ?>
	<div class="pb-alert pb-alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
	<?php endforeach; ?>

	<div class="pb-detail-grid">
		<div class="pb-card">
			<div class="pb-card-header"><h3>Client Details</h3></div>
			<div class="pb-card-body">
				<table class="pb-info-table">
					<tr><th>Owner</th><td><?= e($client->owner_name) ?></td></tr>
					<tr><th>Email</th><td><?= e($client->owner_email) ?></td></tr>
					<tr><th>Site URL</th><td><a href="<?= e($client->site_url) ?>" target="_blank" rel="noopener"><?= e($client->site_url) ?></a></td></tr>
					<tr><th>Status</th><td><span class="pb-badge pb-badge-<?= e($client->subscription_status) ?>"><?= e($status_map[$client->subscription_status] ?? ucfirst($client->subscription_status)) ?></span></td></tr>
					<tr><th>Reports Sent</th><td><?= e($reports_count) ?></td></tr>
					<tr><th>Whitelists</th><td><?= e($whitelists_count) ?></td></tr>
					<tr><th>Joined</th><td><?= e($client->created_at) ?></td></tr>
					<tr><th>Activated</th><td><?= $client->activated_at ? e($client->activated_at) : '—' ?></td></tr>
				</table>
			</div>
		</div>

		<div class="pb-card">
			<div class="pb-card-header"><h3>Actions</h3></div>
			<div class="pb-card-body">
				<div class="pb-action-stack">
					<?php if ($client->subscription_status !== 'active'): ?>
					<form method="post">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="activate">
						<button type="submit" class="pb-btn pb-btn-primary pb-btn-full">Activate Client</button>
					</form>
					<?php endif; ?>
					<?php if ($client->subscription_status === 'active'): ?>
					<form method="post">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="deactivate">
						<button type="submit" class="pb-btn pb-btn-secondary pb-btn-full">Deactivate</button>
					</form>
					<form method="post">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="suspend">
						<button type="submit" class="pb-btn pb-btn-warning pb-btn-full">Suspend</button>
					</form>
					<?php endif; ?>
					<form method="post">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="regen_key">
						<button type="submit" class="pb-btn pb-btn-secondary pb-btn-full" onclick="return confirm('Regenerate API key? The old key will stop working immediately.')">Regenerate API Key</button>
					</form>
					<form method="post" onsubmit="return confirm('Permanently delete this client and all their whitelist data?')">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="delete">
						<button type="submit" class="pb-btn pb-btn-danger pb-btn-full">Delete Client</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="pb-card">
		<div class="pb-card-header"><h3>Admin Notes</h3></div>
		<div class="pb-card-body">
			<form method="post">
				<?= csrf_field() ?>
				<input type="hidden" name="_action" value="update_notes">
				<textarea name="admin_notes" class="pb-textarea" rows="4" placeholder="Internal notes about this client…"><?= e($client->admin_notes ?? '') ?></textarea>
				<button type="submit" class="pb-btn pb-btn-secondary" style="margin-top:8px">Save Notes</button>
			</form>
		</div>
	</div>

	<div class="pb-card">
		<div class="pb-card-header"><h3>Recent Reports (last 10)</h3></div>
		<div class="pb-card-body pb-card-body-flush">
			<?php if (!$recent_reports): ?>
				<p style="padding:16px;color:#6b7280">No reports yet.</p>
			<?php else: ?>
			<table class="pb-table">
				<thead><tr><th>Customer Email</th><th>Reason</th><th>Order</th><th>Date</th></tr></thead>
				<tbody>
				<?php foreach ($recent_reports as $r): ?>
				<tr>
					<td>
						<?php if ($r->customer_id): ?>
						<a href="<?= url('/admin/banned?action=view&id=' . $r->customer_id) ?>"><?= e($r->customer_email ?: '—') ?></a>
						<?php else: ?>
						<?= e($r->customer_email ?? '—') ?>
						<?php endif; ?>
					</td>
					<td><?= e(mb_substr($r->reason, 0, 80)) ?><?= strlen($r->reason) > 80 ? '…' : '' ?></td>
					<td><?= e($r->order_id ?: '—') ?></td>
					<td><?= e($r->date_reported) ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
	<?php
	require __DIR__ . '/../templates/admin-layout-end.php';
	exit;
}

// ── List ──────────────────────────────────────────────────────────────────────
$search   = get_param('s');
$status   = get_param('status', '');
$page_num = max(1, (int) get_param('paged', '1'));
$per_page = 25;

$where  = ['1=1'];
$params = [];
if ($status && $status !== 'all') { $where[] = 'subscription_status = ?'; $params[] = $status; }
if ($search) {
	$where[] = '(owner_name LIKE ? OR owner_email LIKE ? OR site_url LIKE ?)';
	$like = '%' . $search . '%';
	$params = array_merge($params, [$like, $like, $like]);
}
$where_sql = implode(' AND ', $where);
$total     = (int) $db->scalar("SELECT COUNT(*) FROM pepban_clients WHERE $where_sql", $params);
$pag       = paginate($total, $per_page, $page_num);
$clients   = $db->fetchAll(
	"SELECT * FROM pepban_clients WHERE $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET {$pag['offset']}",
	$params
);

$page_title = 'Clients';
require __DIR__ . '/../templates/admin-layout.php';
?>

<?php foreach (get_flashes() as $f): ?>
<div class="pb-alert pb-alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="pb-toolbar">
	<form method="get" class="pb-filter-form">
		<input type="hidden" name="page" value="">
		<select name="status" class="pb-select" onchange="this.form.submit()">
			<option value=""    <?= $status===''?'selected':'' ?>>All Statuses</option>
			<option value="pending"   <?= $status==='pending'?'selected':'' ?>>Pending</option>
			<option value="active"    <?= $status==='active'?'selected':'' ?>>Active</option>
			<option value="inactive"  <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
			<option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
		</select>
		<input type="search" name="s" value="<?= e($search) ?>" placeholder="Search name, email, URL…" class="pb-search">
		<button type="submit" class="pb-btn pb-btn-secondary">Search</button>
	</form>
</div>

<div class="pb-card">
	<table class="pb-table">
		<thead>
			<tr><th>Owner</th><th>Email</th><th>Site URL</th><th>Status</th><th>Joined</th></tr>
		</thead>
		<tbody>
		<?php if (!$clients): ?>
			<tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280">No clients found.</td></tr>
		<?php else: ?>
			<?php foreach ($clients as $c): ?>
			<tr>
				<td><a href="<?= url('/admin/clients?action=view&id=' . $c->id) ?>"><?= e($c->owner_name) ?></a></td>
				<td><?= e($c->owner_email) ?></td>
				<td><?= e($c->site_url) ?></td>
				<td><span class="pb-badge pb-badge-<?= e($c->subscription_status) ?>"><?= e(ucfirst($c->subscription_status)) ?></span></td>
				<td><?= date('M j, Y', strtotime($c->created_at)) ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php if ($pag['total_pages'] > 1): ?>
<div class="pb-pagination">
	<?php if ($pag['has_prev']): ?><a href="?status=<?= e($status) ?>&s=<?= e($search) ?>&paged=<?= $page_num-1 ?>" class="pb-btn pb-btn-secondary">&larr; Prev</a><?php endif; ?>
	<span>Page <?= $page_num ?> of <?= $pag['total_pages'] ?></span>
	<?php if ($pag['has_next']): ?><a href="?status=<?= e($status) ?>&s=<?= e($search) ?>&paged=<?= $page_num+1 ?>" class="pb-btn pb-btn-secondary">Next &rarr;</a><?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
