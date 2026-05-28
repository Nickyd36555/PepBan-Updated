<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$db       = Database::get();
$page_num = max(1, (int) get_param('paged', '1'));
$per_page = 50;

$total = (int) $db->scalar('SELECT COUNT(*) FROM pepban_audit_log');
$pag   = paginate($total, $per_page, $page_num);
$logs  = $db->fetchAll(
	"SELECT * FROM pepban_audit_log ORDER BY created_at DESC LIMIT $per_page OFFSET {$pag['offset']}"
);

$page_title = 'Audit Log';
require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-card">
	<table class="pb-table">
		<thead>
			<tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>Details</th></tr>
		</thead>
		<tbody>
		<?php if (!$logs): ?>
			<tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280">No audit entries yet.</td></tr>
		<?php else: ?>
			<?php foreach ($logs as $l): ?>
			<tr>
				<td style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($l->created_at)) ?></td>
				<td><?= e(ucfirst($l->actor)) ?></td>
				<td><code><?= e($l->action) ?></code></td>
				<td><?php if ($l->target_type && $l->target_id): ?>
					<?php if ($l->target_type === 'customer'): ?>
					<a href="<?= url('/admin/banned?action=view&id=' . $l->target_id) ?>"><?= e($l->target_type) ?> #<?= (int)$l->target_id ?></a>
					<?php else: ?>
					<?= e($l->target_type) ?> #<?= (int)$l->target_id ?>
					<?php endif; ?>
				<?php else: ?>—<?php endif; ?></td>
				<td><?= e($l->details) ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php if ($pag['total_pages'] > 1): ?>
<div class="pb-pagination">
	<?php if ($pag['has_prev']): ?><a href="?paged=<?= $page_num-1 ?>" class="pb-btn pb-btn-secondary">&larr; Prev</a><?php endif; ?>
	<span>Page <?= $page_num ?> of <?= $pag['total_pages'] ?></span>
	<?php if ($pag['has_next']): ?><a href="?paged=<?= $page_num+1 ?>" class="pb-btn pb-btn-secondary">Next &rarr;</a><?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
