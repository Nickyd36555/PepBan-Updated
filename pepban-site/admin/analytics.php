<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$page_title = 'Analytics';
$db = Database::get();

// ── Summary stats ─────────────────────────────────────────────────────────────
$today_bans  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE DATE(date_added) = CURDATE()");
$week_bans   = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$month_bans  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$total_bans  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE status='active'");

$month_reports  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_ban_reports WHERE date_reported >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$total_reports  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_ban_reports");

$open_disputes      = (int) $db->scalar("SELECT COUNT(*) FROM pepban_disputes WHERE status='open'");
$resolved_disputes  = (int) $db->scalar("SELECT COUNT(*) FROM pepban_disputes WHERE status='resolved'");
$dismissed_disputes = (int) $db->scalar("SELECT COUNT(*) FROM pepban_disputes WHERE status='dismissed'");

$active_clients = (int) $db->scalar("SELECT COUNT(*) FROM pepban_clients WHERE subscription_status='active'");

// ── Bans per day — last 30 days ───────────────────────────────────────────────
$raw_daily = $db->fetchAll(
	"SELECT DATE(date_added) as day, COUNT(*) as cnt
	 FROM pepban_banned_customers
	 WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
	 GROUP BY DATE(date_added) ORDER BY day ASC"
);
$daily_map = [];
foreach ($raw_daily as $r) $daily_map[$r->day] = (int) $r->cnt;
$daily_labels = $daily_data = [];
for ($i = 29; $i >= 0; $i--) {
	$d = date('Y-m-d', strtotime("-{$i} days"));
	$daily_labels[] = date('M j', strtotime($d));
	$daily_data[]   = $daily_map[$d] ?? 0;
}

// ── Bans per month — last 12 months ──────────────────────────────────────────
$raw_monthly = $db->fetchAll(
	"SELECT DATE_FORMAT(date_added, '%Y-%m') as month, COUNT(*) as cnt
	 FROM pepban_banned_customers
	 WHERE date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
	 GROUP BY month ORDER BY month ASC"
);
$monthly_map = [];
foreach ($raw_monthly as $r) $monthly_map[$r->month] = (int) $r->cnt;
$monthly_labels = $monthly_data = [];
for ($i = 11; $i >= 0; $i--) {
	$m = date('Y-m', strtotime("-{$i} months"));
	$monthly_labels[] = date('M Y', strtotime($m . '-01'));
	$monthly_data[]   = $monthly_map[$m] ?? 0;
}

// ── Top reporting stores ──────────────────────────────────────────────────────
$top_stores = $db->fetchAll(
	"SELECT site_url, COUNT(*) as cnt
	 FROM pepban_ban_reports
	 GROUP BY site_url ORDER BY cnt DESC LIMIT 10"
);
$max_store_cnt = $top_stores ? max(array_map(fn($r) => (int)$r->cnt, $top_stores)) : 1;

// ── Top ban reasons ───────────────────────────────────────────────────────────
$top_reasons = $db->fetchAll(
	"SELECT reason, COUNT(*) as cnt
	 FROM pepban_banned_customers
	 WHERE reason != '' AND reason IS NOT NULL
	 GROUP BY reason ORDER BY cnt DESC LIMIT 8"
);
$reason_total = array_sum(array_map(fn($r) => (int)$r->cnt, $top_reasons)) ?: 1;

require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-stats-grid" style="grid-template-columns:repeat(4,1fr)">
	<div class="pb-stat-card">
		<span class="pb-stat-num"><?= $today_bans ?></span>
		<span class="pb-stat-label">Bans Today</span>
	</div>
	<div class="pb-stat-card">
		<span class="pb-stat-num"><?= $week_bans ?></span>
		<span class="pb-stat-label">Bans This Week</span>
	</div>
	<div class="pb-stat-card pb-stat-recent">
		<span class="pb-stat-num"><?= $month_bans ?></span>
		<span class="pb-stat-label">Bans (30 days)</span>
	</div>
	<div class="pb-stat-card pb-stat-banned">
		<span class="pb-stat-num"><?= $total_bans ?></span>
		<span class="pb-stat-label">Total Active Bans</span>
	</div>
</div>

<!-- Bans Over Time -->
<div class="pb-card" style="margin-top:24px">
	<div class="pb-card-header" style="display:flex;align-items:center;justify-content:space-between">
		<h3>Bans Over Time</h3>
		<div style="display:flex;gap:6px">
			<button class="pb-btn pb-btn-secondary" style="padding:4px 12px;font-size:.8rem" id="btn-30d" onclick="showRange('30d')">30 days</button>
			<button class="pb-btn pb-btn-secondary" style="padding:4px 12px;font-size:.8rem" id="btn-12m" onclick="showRange('12m')">12 months</button>
		</div>
	</div>
	<div class="pb-card-body">
		<canvas id="bansChart" style="max-height:260px"></canvas>
	</div>
</div>

<!-- Stores + Disputes -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px">

	<!-- Top Reporting Stores -->
	<div class="pb-card">
		<div class="pb-card-header"><h3>Top Reporting Stores</h3></div>
		<?php if ($top_stores): ?>
		<div style="padding:0 0 8px">
			<?php foreach ($top_stores as $s): ?>
			<?php $pct = round(((int)$s->cnt / $max_store_cnt) * 100); ?>
			<div style="padding:10px 20px;border-bottom:1px solid var(--border)">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
					<span style="font-size:.85rem;color:var(--text);max-width:75%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($s->site_url ?: 'Unknown') ?></span>
					<span style="font-size:.85rem;font-weight:700;color:var(--text)"><?= $s->cnt ?></span>
				</div>
				<div style="height:4px;background:var(--border);border-radius:2px">
					<div style="height:4px;background:#dc2626;border-radius:2px;width:<?= $pct ?>%"></div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php else: ?>
		<div class="pb-card-body" style="color:#6b7280;text-align:center">No reports yet.</div>
		<?php endif; ?>
	</div>

	<!-- Dispute Breakdown -->
	<div class="pb-card">
		<div class="pb-card-header"><h3>Dispute Status</h3></div>
		<div class="pb-card-body">
			<?php
			$total_disputes = $open_disputes + $resolved_disputes + $dismissed_disputes;
			$d_items = [
				['label' => 'Open',      'count' => $open_disputes,      'color' => '#f59e0b'],
				['label' => 'Resolved',  'count' => $resolved_disputes,  'color' => '#22c55e'],
				['label' => 'Dismissed', 'count' => $dismissed_disputes, 'color' => '#6b7280'],
			];
			?>
			<?php if ($total_disputes > 0): ?>
			<div style="display:flex;height:12px;border-radius:6px;overflow:hidden;margin-bottom:20px">
				<?php foreach ($d_items as $di): ?>
				<?php if ($di['count'] > 0): ?>
				<div style="flex:<?= $di['count'] ?>;background:<?= $di['color'] ?>"></div>
				<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php else: ?>
			<div style="height:12px;background:var(--border);border-radius:6px;margin-bottom:20px"></div>
			<?php endif; ?>
			<?php foreach ($d_items as $di): ?>
			<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
				<div style="display:flex;align-items:center;gap:10px">
					<span style="width:10px;height:10px;border-radius:50%;background:<?= $di['color'] ?>;display:inline-block;flex-shrink:0"></span>
					<span style="font-size:.9rem;color:var(--text)"><?= $di['label'] ?></span>
				</div>
				<span style="font-weight:700;font-size:.95rem;color:var(--text)"><?= $di['count'] ?></span>
			</div>
			<?php endforeach; ?>
			<div style="display:flex;justify-content:space-between;padding:12px 0 0">
				<span style="font-size:.82rem;color:#6b7280">Total disputes</span>
				<span style="font-weight:700;font-size:.9rem;color:var(--text)"><?= $total_disputes ?></span>
			</div>
			<?php if ($open_disputes > 0): ?>
			<a href="<?= url('/admin/disputes') ?>" class="pb-btn pb-btn-secondary" style="margin-top:16px;width:100%;text-align:center;display:block">Review Open Disputes</a>
			<?php endif; ?>
		</div>
	</div>

</div>

<!-- Top Ban Reasons -->
<?php if ($top_reasons): ?>
<div class="pb-card" style="margin-top:24px">
	<div class="pb-card-header"><h3>Top Ban Reasons</h3></div>
	<div class="pb-card-body">
		<?php foreach ($top_reasons as $r): ?>
		<?php $pct = round(((int)$r->cnt / $reason_total) * 100); ?>
		<div style="margin-bottom:14px">
			<div style="display:flex;justify-content:space-between;margin-bottom:5px">
				<span style="font-size:.85rem;color:var(--text);max-width:80%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r->reason) ?></span>
				<span style="font-size:.82rem;color:#6b7280"><?= $r->cnt ?> (<?= $pct ?>%)</span>
			</div>
			<div style="height:6px;background:var(--border);border-radius:3px">
				<div style="height:6px;background:#dc2626;border-radius:3px;width:<?= $pct ?>%"></div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- Reports summary -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:24px">
	<div class="pb-stat-card pb-stat-reports">
		<span class="pb-stat-num"><?= $total_reports ?></span>
		<span class="pb-stat-label">Total Reports</span>
	</div>
	<div class="pb-stat-card">
		<span class="pb-stat-num"><?= $month_reports ?></span>
		<span class="pb-stat-label">Reports (30 days)</span>
	</div>
	<div class="pb-stat-card pb-stat-clients">
		<span class="pb-stat-num"><?= $active_clients ?></span>
		<span class="pb-stat-label">Active Clients</span>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const daily   = { labels: <?= json_encode($daily_labels) ?>,   data: <?= json_encode($daily_data) ?> };
const monthly = { labels: <?= json_encode($monthly_labels) ?>, data: <?= json_encode($monthly_data) ?> };

const textColor   = getComputedStyle(document.body).getPropertyValue('--text').trim()   || '#e2e8f0';
const mutedColor  = getComputedStyle(document.body).getPropertyValue('--text-muted').trim() || '#94a3b8';
const borderColor = getComputedStyle(document.body).getPropertyValue('--border').trim() || '#2d2d4e';

Chart.defaults.color = mutedColor;
Chart.defaults.borderColor = borderColor;

let chart;
function buildChart(range) {
	const src = range === '30d' ? daily : monthly;
	if (chart) chart.destroy();
	chart = new Chart(document.getElementById('bansChart'), {
		type: 'bar',
		data: {
			labels: src.labels,
			datasets: [{
				label: 'Bans',
				data: src.data,
				backgroundColor: 'rgba(220,38,38,0.75)',
				borderColor: '#dc2626',
				borderWidth: 1,
				borderRadius: 3,
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: { legend: { display: false } },
			scales: {
				x: { grid: { color: borderColor }, ticks: { color: mutedColor, maxTicksLimit: range === '30d' ? 10 : 12 } },
				y: { grid: { color: borderColor }, ticks: { color: mutedColor, stepSize: 1, precision: 0 }, beginAtZero: true }
			}
		}
	});
}

function showRange(range) {
	buildChart(range);
	document.getElementById('btn-30d').style.opacity = range === '30d' ? '1' : '.45';
	document.getElementById('btn-12m').style.opacity = range === '12m' ? '1' : '.45';
}

showRange('30d');
</script>

<?php require __DIR__ . '/../templates/admin-layout-end.php'; ?>
