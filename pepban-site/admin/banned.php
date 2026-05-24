<?php
$db     = Database::get();
$action = get_param('action', 'list');
$id     = (int) get_param('id');

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$act = post('_action');

	if ($act === 'add') {
		$db->insert('pepban_banned_customers', [
			'email'              => strtolower(post('email')),
			'first_name'         => post('first_name'),
			'last_name'          => post('last_name'),
			'phone'              => post('phone'),
			'billing_address'    => post('billing_address'),
			'ip_address'         => post('ip_address'),
			'reason'             => post('reason'),
			'reported_by_site'   => 'admin',
			'reported_by_client' => 0,
			'date_added'         => date('Y-m-d H:i:s'),
			'last_updated'       => date('Y-m-d H:i:s'),
			'status'             => 'active',
			'admin_notes'        => '',
			'reports_count'      => 1,
		]);
		flash('success', 'Customer added to ban list.');
		redirect('/admin/banned');
	}

	if ($act === 'update_status' && $id) {
		$db->update('pepban_banned_customers',
			['status' => post('status'), 'last_updated' => date('Y-m-d H:i:s')],
			['id' => $id]
		);
		flash('success', 'Status updated.');
		redirect('/admin/banned?action=view&id=' . $id);
	}

	if ($act === 'delete' && $id) {
		$db->delete('pepban_banned_customers', ['id' => $id]);
		$db->query('DELETE FROM pepban_ban_reports WHERE customer_id = ?', [$id]);
		$db->query('DELETE FROM pepban_whitelists WHERE customer_id = ?', [$id]);
		flash('success', 'Customer permanently removed.');
		redirect('/admin/banned');
	}
}

// ── View single customer ──────────────────────────────────────────────────────
if ($action === 'view' && $id) {
	$customer = $db->fetch('SELECT * FROM pepban_banned_customers WHERE id = ?', [$id]);
	$reports  = $db->fetchAll(
		'SELECT r.*, c.owner_name FROM pepban_ban_reports r
		 LEFT JOIN pepban_clients c ON r.client_id = c.id
		 WHERE r.customer_id = ? ORDER BY r.date_reported DESC', [$id]
	);
	$page_title = 'Banned: ' . ($customer->email ?? '');
	require __DIR__ . '/../templates/admin-layout.php';
	if (!$customer) { echo '<p>Not found.</p>'; require __DIR__ . '/../templates/admin-layout-end.php'; exit; }
	?>
	<a href="<?= url('/admin/banned') ?>" class="pb-back-link">&larr; All bans</a>

	<div class="pb-detail-grid">
		<div class="pb-card">
			<div class="pb-card-header"><h3>Customer Details</h3></div>
			<div class="pb-card-body">
				<table class="pb-info-table">
					<tr><th>Email</th><td><?= e($customer->email) ?></td></tr>
					<tr><th>Name</th><td><?= e($customer->first_name . ' ' . $customer->last_name) ?></td></tr>
					<tr><th>Phone</th><td><?= e($customer->phone) ?: '—' ?></td></tr>
					<tr><th>Billing Address</th><td><?= nl2br(e($customer->billing_address)) ?: '—' ?></td></tr>
					<tr><th>IP Address</th><td><?= e($customer->ip_address) ?: '—' ?></td></tr>
					<tr><th>First Reported By</th><td><?= e($customer->reported_by_site) ?></td></tr>
					<tr><th>Date Added</th><td><?= e($customer->date_added) ?></td></tr>
					<tr><th>Reports</th><td><?= e($customer->reports_count) ?></td></tr>
					<tr><th>Status</th><td><span class="pb-badge pb-badge-<?= e($customer->status) ?>"><?= e(ucfirst($customer->status)) ?></span></td></tr>
					<tr><th>Reason</th><td><?= nl2br(e($customer->reason)) ?></td></tr>
				</table>
			</div>
		</div>

		<div class="pb-card">
			<div class="pb-card-header"><h3>Actions</h3></div>
			<div class="pb-card-body">
				<p><em>Only you can change or remove bans.</em></p>
				<form method="post" style="margin-bottom:12px">
					<?= csrf_field() ?>
					<input type="hidden" name="_action" value="update_status">
					<select name="status" class="pb-select">
						<option value="active"   <?= $customer->status==='active'?'selected':'' ?>>Active (blocked)</option>
						<option value="inactive" <?= $customer->status==='inactive'?'selected':'' ?>>Inactive (allowed)</option>
						<option value="pending"  <?= $customer->status==='pending'?'selected':'' ?>>Pending review</option>
					</select>
					<button type="submit" class="pb-btn pb-btn-secondary" style="margin-top:8px">Update Status</button>
				</form>
				<form method="post" onsubmit="return confirm('Permanently delete this customer from the database?')">
					<?= csrf_field() ?>
					<input type="hidden" name="_action" value="delete">
					<button type="submit" class="pb-btn pb-btn-danger">Permanently Remove</button>
				</form>
			</div>
		</div>
	</div>

	<div class="pb-card">
		<div class="pb-card-header"><h3>Report History (<?= count($reports) ?>)</h3></div>
		<div class="pb-card-body pb-card-body-flush">
			<?php if (!$reports): ?>
				<p style="padding:16px;color:#6b7280">No reports found.</p>
			<?php else: ?>
			<table class="pb-table">
				<thead><tr><th>Site</th><th>Client</th><th>Reason</th><th>Order</th><th>Date</th></tr></thead>
				<tbody>
				<?php foreach ($reports as $r): ?>
				<tr>
					<td><?= e($r->site_url) ?></td>
					<td><?= e($r->owner_name ?: '—') ?></td>
					<td><?= nl2br(e($r->reason)) ?></td>
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

// ── Add form ──────────────────────────────────────────────────────────────────
if ($action === 'add') {
	$page_title = 'Add Ban';
	require __DIR__ . '/../templates/admin-layout.php';
	?>
	<a href="<?= url('/admin/banned') ?>" class="pb-back-link">&larr; Back</a>
	<div class="pb-card" style="max-width:640px">
		<div class="pb-card-header"><h3>Add Banned Customer</h3></div>
		<div class="pb-card-body">
			<form method="post" class="pb-form">
				<?= csrf_field() ?>
				<input type="hidden" name="_action" value="add">
				<div class="pb-field"><label>Email *</label><input type="email" name="email" required></div>
				<div class="pb-grid-2">
					<div class="pb-field"><label>First Name</label><input type="text" name="first_name"></div>
					<div class="pb-field"><label>Last Name</label><input type="text" name="last_name"></div>
				</div>
				<div class="pb-field"><label>Phone</label><input type="text" name="phone"></div>
				<div class="pb-field"><label>Billing Address</label><textarea name="billing_address" rows="3"></textarea></div>
				<div class="pb-field"><label>IP Address</label><input type="text" name="ip_address" placeholder="0.0.0.0"></div>
				<div class="pb-field"><label>Reason *</label><textarea name="reason" rows="3" required></textarea></div>
				<button type="submit" class="pb-btn pb-btn-primary">Add to Ban List</button>
			</form>
		</div>
	</div>
	<?php
	require __DIR__ . '/../templates/admin-layout-end.php';
	exit;
}

// ── List ──────────────────────────────────────────────────────────────────────
$search   = get_param('s');
$status   = get_param('status', 'active');
$page_num = max(1, (int) get_param('paged', '1'));
$per_page = 25;

$where  = ['1=1'];
$params = [];
if ($status && $status !== 'all') { $where[] = 'status = ?'; $params[] = $status; }
if ($search) {
	$where[] = '(email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)';
	$like = '%' . $search . '%';
	$params = array_merge($params, [$like, $like, $like, $like]);
}
$where_sql = implode(' AND ', $where);
$total     = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE $where_sql", $params);
$pag       = paginate($total, $per_page, $page_num);
$customers = $db->fetchAll(
	"SELECT * FROM pepban_banned_customers WHERE $where_sql ORDER BY date_added DESC LIMIT $per_page OFFSET {$pag['offset']}",
	$params
);

$page_title = 'Banned Customers';
require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-toolbar">
	<form method="get" class="pb-filter-form">
		<input type="hidden" name="page" value="">
		<select name="status" class="pb-select" onchange="this.form.submit()">
			<option value="active"   <?= $status==='active'?'selected':'' ?>>Active</option>
			<option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
			<option value="all"      <?= $status==='all'?'selected':'' ?>>All</option>
		</select>
		<input type="search" name="s" value="<?= e($search) ?>" placeholder="Search email, name, phone…" class="pb-search">
		<button type="submit" class="pb-btn pb-btn-secondary">Search</button>
		<a href="<?= url('/admin/banned?action=add') ?>" class="pb-btn pb-btn-primary">+ Add Ban</a>
	</form>
</div>

<div class="pb-card">
	<table class="pb-table">
		<thead>
			<tr><th>Email</th><th>Name</th><th>Phone</th><th>Reported By</th><th>Reports</th><th>Date</th><th>Status</th></tr>
		</thead>
		<tbody>
		<?php if (!$customers): ?>
			<tr><td colspan="7" style="text-align:center;padding:32px;color:#6b7280">No banned customers found.</td></tr>
		<?php else: ?>
			<?php foreach ($customers as $c): ?>
			<tr>
				<td><a href="<?= url('/admin/banned?action=view&id=' . $c->id) ?>"><?= e($c->email) ?></a></td>
				<td><?= e(trim($c->first_name . ' ' . $c->last_name)) ?: '—' ?></td>
				<td><?= e($c->phone) ?: '—' ?></td>
				<td><?= e($c->reported_by_site) ?></td>
				<td><?= e($c->reports_count) ?></td>
				<td><?= date('M j, Y', strtotime($c->date_added)) ?></td>
				<td><span class="pb-badge pb-badge-<?= e($c->status) ?>"><?= e(ucfirst($c->status)) ?></span></td>
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
