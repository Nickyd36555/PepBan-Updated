<?php
$db = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$act = post('_action');

	if ($act === 'mark_read') {
		$ids = $_POST['ids'] ?? [];
		foreach ($ids as $fid) {
			$db->update('pepban_feedback', ['read_at' => date('Y-m-d H:i:s')], ['id' => (int)$fid]);
		}
		admin_flash('success', 'Marked as read.');
		redirect('/admin/feedback');
	}

	if ($act === 'delete') {
		$fid = (int) post('id');
		if ($fid) $db->delete('pepban_feedback', ['id' => $fid]);
		admin_flash('success', 'Deleted.');
		redirect('/admin/feedback');
	}
}

$filter   = get_param('filter', 'unread');
$page_num = max(1, (int) get_param('paged', '1'));
$per_page = 25;

$where  = $filter === 'unread' ? 'read_at IS NULL' : '1=1';
$total  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_feedback WHERE $where");
$pag    = paginate($total, $per_page, $page_num);
$items  = $db->fetchAll(
	"SELECT f.*, c.owner_name, c.site_url FROM pepban_feedback f
	 LEFT JOIN pepban_clients c ON c.id = f.client_id
	 WHERE $where ORDER BY f.created_at DESC LIMIT $per_page OFFSET {$pag['offset']}"
);

$unread_count = (int) $db->scalar('SELECT COUNT(*) FROM pepban_feedback WHERE read_at IS NULL');

$page_title = 'Feedback' . ($unread_count ? " ($unread_count)" : '');
require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-toolbar">
	<div style="display:flex;gap:8px;align-items:center">
		<a href="?filter=unread" class="pb-btn <?= $filter==='unread'?'pb-btn-primary':'pb-btn-ghost' ?>">Unread <?= $unread_count ? "($unread_count)" : '' ?></a>
		<a href="?filter=all"    class="pb-btn <?= $filter==='all'?'pb-btn-primary':'pb-btn-ghost' ?>">All</a>
	</div>
</div>

<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="_action" value="mark_read">
<div class="pb-card">
	<table class="pb-table">
		<thead>
			<tr>
				<th><input type="checkbox" id="cb-all" onclick="document.querySelectorAll('.cb-item').forEach(c=>c.checked=this.checked)"></th>
				<th>From</th><th>Store</th><th>Message</th><th>Date</th><th>Status</th><th></th>
			</tr>
		</thead>
		<tbody>
		<?php if (!$items): ?>
			<tr><td colspan="7" style="text-align:center;padding:32px;color:#6b7280">No feedback yet.</td></tr>
		<?php else: ?>
			<?php foreach ($items as $f): ?>
			<tr <?= !$f->read_at ? 'style="font-weight:600"' : '' ?>>
				<td><input type="checkbox" name="ids[]" value="<?= (int)$f->id ?>" class="cb-item"></td>
				<td><?= e($f->owner_name ?? $f->client_email ?? '—') ?></td>
				<td><?= e($f->site_url ?? '—') ?></td>
				<td style="max-width:420px;white-space:pre-wrap"><?= e($f->message) ?></td>
				<td style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($f->created_at)) ?></td>
				<td><?= $f->read_at ? '<span style="color:#6b7280">Read</span>' : '<span style="color:var(--green)">New</span>' ?></td>
				<td>
					<form method="post" style="display:inline" onsubmit="return confirm('Delete this feedback?')">
						<?= csrf_field() ?>
						<input type="hidden" name="_action" value="delete">
						<input type="hidden" name="id" value="<?= (int)$f->id ?>">
						<button type="submit" class="pb-btn pb-btn-danger" style="padding:4px 10px;font-size:.8rem">Delete</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
<?php if ($items): ?>
<div style="margin-top:12px">
	<button type="submit" class="pb-btn pb-btn-secondary">Mark selected as read</button>
</div>
<?php endif; ?>
</form>

<?php if ($pag['total_pages'] > 1): ?>
<div class="pb-pagination">
	<?php if ($pag['has_prev']): ?><a href="?filter=<?= e($filter) ?>&paged=<?= $page_num-1 ?>" class="pb-btn pb-btn-secondary">&larr; Prev</a><?php endif; ?>
	<span>Page <?= $page_num ?> of <?= $pag['total_pages'] ?></span>
	<?php if ($pag['has_next']): ?><a href="?filter=<?= e($filter) ?>&paged=<?= $page_num+1 ?>" class="pb-btn pb-btn-secondary">Next &rarr;</a><?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
