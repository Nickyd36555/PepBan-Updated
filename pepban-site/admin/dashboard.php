<?php
$db   = Database::get();
$page_title = 'Dashboard';
$stats = [
	'banned'  => $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE status='active'"),
	'clients' => $db->scalar("SELECT COUNT(*) FROM pepban_clients WHERE subscription_status='active'"),
	'reports' => $db->scalar("SELECT COUNT(*) FROM pepban_ban_reports"),
	'recent'  => $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
];
require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-stats-grid">
	<div class="pb-stat-card pb-stat-banned">
		<span class="pb-stat-num"><?= e($stats['banned']) ?></span>
		<span class="pb-stat-label">Active Bans</span>
	</div>
	<div class="pb-stat-card pb-stat-clients">
		<span class="pb-stat-num"><?= e($stats['clients']) ?></span>
		<span class="pb-stat-label">Active Clients</span>
	</div>
	<div class="pb-stat-card pb-stat-reports">
		<span class="pb-stat-num"><?= e($stats['reports']) ?></span>
		<span class="pb-stat-label">Total Reports</span>
	</div>
	<div class="pb-stat-card pb-stat-recent">
		<span class="pb-stat-num"><?= e($stats['recent']) ?></span>
		<span class="pb-stat-label">New Bans (30d)</span>
	</div>
</div>

<div class="pb-quick-actions">
	<a href="<?= url('/admin/banned') ?>" class="pb-btn pb-btn-primary">View Banned Customers</a>
	<a href="<?= url('/admin/banned?action=add') ?>" class="pb-btn pb-btn-secondary">+ Add Ban</a>
	<a href="<?= url('/admin/clients') ?>" class="pb-btn pb-btn-secondary">Manage Clients</a>
</div>

<div class="pb-info-block">
	<span class="pb-info-label">API Base URL</span>
	<code><?= e(SITE_URL . '/api/v1') ?></code>
</div>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
