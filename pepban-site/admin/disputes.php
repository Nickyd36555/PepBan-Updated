<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$page_title = 'Disputes';
$db = Database::get();

// Handle status update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$id     = (int) ($_POST['dispute_id'] ?? 0);
	$action = $_POST['dispute_action'] ?? '';

	if ($id && in_array($action, ['resolve', 'dismiss', 'reopen'], true)) {
		$status_map = ['resolve' => 'resolved', 'dismiss' => 'dismissed', 'reopen' => 'open'];
		$db->query('UPDATE pepban_disputes SET status = ? WHERE id = ?', [$status_map[$action], $id]);
		$db->insert('pepban_audit_log', [
			'actor'       => 'admin',
			'action'      => 'dispute_' . $action,
			'target_type' => 'dispute',
			'target_id'   => $id,
			'details'     => '',
			'created_at'  => date('Y-m-d H:i:s'),
		]);
		admin_flash('success', 'Dispute updated.');
		redirect('/admin/disputes');
	}

	if ($id && $action === 'save_notes') {
		$notes = trim($_POST['admin_notes'] ?? '');
		$db->query('UPDATE pepban_disputes SET admin_notes = ? WHERE id = ?', [$notes, $id]);
		admin_flash('success', 'Notes saved.');
		redirect('/admin/disputes');
	}
}

$filter = get_param('status', 'open');
$allowed_filters = ['open', 'resolved', 'dismissed', 'all'];
if (!in_array($filter, $allowed_filters, true)) $filter = 'open';

$where  = $filter !== 'all' ? 'WHERE d.status = ?' : '';
$params = $filter !== 'all' ? [$filter] : [];

$disputes = $db->fetchAll(
	"SELECT d.*, b.id AS customer_id
	 FROM pepban_disputes d
	 LEFT JOIN pepban_banned_customers b ON b.email = d.email AND b.status = 'active'
	 {$where}
	 ORDER BY d.date_added DESC",
	$params
);

$counts = [];
foreach (['open', 'resolved', 'dismissed', 'all'] as $s) {
	$q = $s !== 'all'
		? $db->scalar('SELECT COUNT(*) FROM pepban_disputes WHERE status = ?', [$s])
		: $db->scalar('SELECT COUNT(*) FROM pepban_disputes');
	$counts[$s] = (int) $q;
}

require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-admin-section">

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
	<h2 style="margin:0">Ban Disputes</h2>
</div>

<div class="pb-filter-bar" style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
	<?php foreach (['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'all' => 'All'] as $s => $label): ?>
	<a href="<?= url('/admin/disputes') ?>?status=<?= $s ?>"
	   class="pb-filter-pill <?= $filter === $s ? 'pb-filter-active' : '' ?>"
	   style="padding:6px 14px;border-radius:20px;font-size:.85rem;text-decoration:none;border:1px solid var(--border);
	          <?= $filter === $s ? 'background:var(--accent);color:#fff;border-color:var(--accent)' : 'color:var(--text-muted)' ?>">
		<?= $label ?> <span style="opacity:.7">(<?= $counts[$s] ?>)</span>
	</a>
	<?php endforeach; ?>
</div>

<?php if (empty($disputes)): ?>
	<p style="color:var(--text-muted);padding:32px 0">No disputes found.</p>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:16px">
<?php foreach ($disputes as $d): ?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:20px 24px">
	<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px">
		<div>
			<strong><?= e($d->name) ?></strong>
			<span style="color:var(--text-muted);margin-left:8px"><?= e($d->email) ?></span>
			<?php if ($d->customer_id): ?>
				<a href="<?= url('/admin/banned') ?>?action=view&id=<?= $d->customer_id ?>"
				   style="margin-left:8px;font-size:.8rem;color:var(--accent)">View ban record →</a>
			<?php else: ?>
				<span style="margin-left:8px;font-size:.8rem;color:var(--text-muted)">(not in ban list)</span>
			<?php endif; ?>
		</div>
		<div style="display:flex;align-items:center;gap:8px">
			<?php
			$badge_colors = ['open' => '#f59e0b', 'resolved' => '#22c55e', 'dismissed' => '#6b7280'];
			$badge_color  = $badge_colors[$d->status] ?? '#6b7280';
			?>
			<span style="font-size:.78rem;padding:3px 10px;border-radius:20px;background:<?= $badge_color ?>22;color:<?= $badge_color ?>;border:1px solid <?= $badge_color ?>44">
				<?= ucfirst(e($d->status)) ?>
			</span>
			<span style="font-size:.8rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($d->date_added)) ?></span>
		</div>
	</div>

	<?php if ($d->store_hint): ?>
		<p style="font-size:.85rem;color:var(--text-muted);margin:0 0 10px">Store: <?= e($d->store_hint) ?></p>
	<?php endif; ?>

	<p style="white-space:pre-wrap;margin:0 0 16px;font-size:.9rem;line-height:1.6"><?= e($d->reason) ?></p>

	<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
		<?php if ($d->status === 'open'): ?>
			<form method="POST" style="display:inline">
				<?= csrf_field() ?>
				<input type="hidden" name="dispute_id" value="<?= $d->id ?>">
				<input type="hidden" name="dispute_action" value="resolve">
				<button type="submit" class="pb-btn pb-btn-sm" style="background:#22c55e22;color:#22c55e;border:1px solid #22c55e44">
					Mark Resolved
				</button>
			</form>
			<form method="POST" style="display:inline">
				<?= csrf_field() ?>
				<input type="hidden" name="dispute_id" value="<?= $d->id ?>">
				<input type="hidden" name="dispute_action" value="dismiss">
				<button type="submit" class="pb-btn pb-btn-sm" style="background:var(--bg);color:var(--text-muted);border:1px solid var(--border)">
					Dismiss
				</button>
			</form>
		<?php else: ?>
			<form method="POST" style="display:inline">
				<?= csrf_field() ?>
				<input type="hidden" name="dispute_id" value="<?= $d->id ?>">
				<input type="hidden" name="dispute_action" value="reopen">
				<button type="submit" class="pb-btn pb-btn-sm" style="background:var(--bg);color:var(--text-muted);border:1px solid var(--border)">
					Reopen
				</button>
			</form>
		<?php endif; ?>

		<details style="flex:1;min-width:200px">
			<summary style="cursor:pointer;font-size:.82rem;color:var(--text-muted);list-style:none">
				&#9658; Notes<?= $d->admin_notes ? ' (' . strlen($d->admin_notes) . ' chars)' : '' ?>
			</summary>
			<form method="POST" style="margin-top:10px">
				<?= csrf_field() ?>
				<input type="hidden" name="dispute_id" value="<?= $d->id ?>">
				<input type="hidden" name="dispute_action" value="save_notes">
				<textarea name="admin_notes" rows="3"
				          style="width:100%;padding:8px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.85rem;resize:vertical"
				          placeholder="Internal notes (not visible to the customer)…"><?= e($d->admin_notes) ?></textarea>
				<button type="submit" class="pb-btn pb-btn-sm" style="margin-top:6px">Save Notes</button>
			</form>
		</details>
	</div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
