<?php
/**
 * PepBan Server Monitor
 * Run via cron every 5 minutes:
 *   php /path/to/pepban-site/cron/monitor.php
 *
 * Sends an alert email (via PepBan SMTP) if any threshold is breached.
 * Rate-limited to one alert per hour to avoid inbox flooding.
 */

// Bootstrap — load config and mailer only, no HTTP context
define('PEPBAN_VERSION', 'cron');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Mailer.php';

// ── Thresholds ────────────────────────────────────────────────────────────────
const LOAD_WARN    = 2.0;   // 1-minute load average (adjust to your CPU count)
const DISK_WARN_GB = 2.0;   // alert when free disk space drops below this (GB)
const MEM_WARN_PCT = 90;    // alert when RAM usage exceeds this %

// ── Rate limit — one alert email per hour ─────────────────────────────────────
$flag_file = __DIR__ . '/.last_alert';
if (file_exists($flag_file) && (time() - filemtime($flag_file)) < 3600) {
	exit(0);
}

// ── Gather metrics ────────────────────────────────────────────────────────────
$alerts  = [];
$metrics = [];

// Load average
$load = sys_getloadavg();
$metrics['load_1m']  = round($load[0], 2);
$metrics['load_5m']  = round($load[1], 2);
$metrics['load_15m'] = round($load[2], 2);
if ($load[0] >= LOAD_WARN) {
	$alerts[] = "Load average: {$load[0]} (threshold " . LOAD_WARN . ")";
}

// Disk space
$disk_free  = disk_free_space('/');
$disk_total = disk_total_space('/');
if ($disk_free !== false && $disk_total !== false) {
	$disk_free_gb  = round($disk_free  / 1073741824, 2);
	$disk_total_gb = round($disk_total / 1073741824, 2);
	$disk_used_pct = round((1 - $disk_free / $disk_total) * 100, 1);
	$metrics['disk_free_gb']  = $disk_free_gb;
	$metrics['disk_total_gb'] = $disk_total_gb;
	$metrics['disk_used_pct'] = $disk_used_pct;
	if ($disk_free_gb < DISK_WARN_GB) {
		$alerts[] = "Disk space: {$disk_free_gb} GB free of {$disk_total_gb} GB ({$disk_used_pct}% used)";
	}
}

// Memory (Linux only)
$mem_info = @file_get_contents('/proc/meminfo');
if ($mem_info) {
	preg_match('/MemTotal:\s+(\d+)/',     $mem_info, $total_m);
	preg_match('/MemAvailable:\s+(\d+)/', $mem_info, $avail_m);
	if ($total_m && $avail_m) {
		$mem_total_mb = round($total_m[1] / 1024);
		$mem_avail_mb = round($avail_m[1] / 1024);
		$mem_used_pct = round((1 - $avail_m[1] / $total_m[1]) * 100, 1);
		$metrics['mem_total_mb'] = $mem_total_mb;
		$metrics['mem_avail_mb'] = $mem_avail_mb;
		$metrics['mem_used_pct'] = $mem_used_pct;
		if ($mem_used_pct >= MEM_WARN_PCT) {
			$alerts[] = "Memory: {$mem_used_pct}% used ({$mem_avail_mb} MB available of {$mem_total_mb} MB)";
		}
	}
}

// ── No alerts — exit quietly ──────────────────────────────────────────────────
if (empty($alerts)) {
	exit(0);
}

// ── Send alert ────────────────────────────────────────────────────────────────
touch($flag_file); // stamp before send so a slow SMTP doesn't allow double-send

$alert_list  = implode("\n", array_map(fn($a) => "  • {$a}", $alerts));
$metric_dump = implode("\n", array_map(fn($k, $v) => "  {$k}: {$v}", array_keys($metrics), $metrics));
$hostname    = gethostname() ?: 'pepban.com';
$time        = date('Y-m-d H:i:s T');

$subject = '[PepBan] Server alert — ' . count($alerts) . ' issue(s) on ' . $hostname;
$body    =
	"PepBan server monitor detected the following issues at {$time}:\n\n" .
	$alert_list . "\n\n" .
	"Current metrics:\n" . $metric_dump . "\n\n" .
	"Check your Cloudways panel for more detail.\n\n— PepBan Monitor";

Mailer::adminError($subject, $body);

echo "[" . date('Y-m-d H:i:s') . "] Alert sent: " . implode('; ', $alerts) . "\n";
exit(0);
