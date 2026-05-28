<?php
defined('PEPBAN_VERSION') || die('Direct access not allowed.');
$page_title = 'Bulk Import';
$msg = $msg_type = '';
$result_stats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();

	if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
		$msg = 'Upload failed (error ' . ($_FILES['csv_file']['error'] ?? 'none') . ').';
		$msg_type = 'error';
	} else {
		$handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
		if (!$handle) {
			$msg = 'Could not read uploaded file.'; $msg_type = 'error';
		} else {
			$db      = Database::get();
			$added   = $updated = $skipped = 0;
			$row_num = 0;
			$errors  = [];

			while (($row = fgetcsv($handle)) !== false) {
				$row_num++;
				// Skip header row
				if ($row_num === 1 && strtolower(trim($row[0] ?? '')) === 'email') continue;

				$email = strtolower(trim($row[0] ?? ''));
				if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$skipped++;
					if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email) {
						$errors[] = "Row {$row_num}: invalid email &ldquo;" . htmlspecialchars($email) . "&rdquo;";
					}
					continue;
				}

				$first  = trim($row[1] ?? '');
				$last   = trim($row[2] ?? '');
				$phone  = trim($row[3] ?? '');
				$reason = trim($row[4] ?? 'Bulk import');

				$existing = $db->fetch('SELECT id, reports_count FROM pepban_banned_customers WHERE email = ?', [$email]);
				if ($existing) {
					$db->query(
						'UPDATE pepban_banned_customers SET reports_count = reports_count + 1, last_updated = NOW() WHERE id = ?',
						[$existing->id]
					);
					// Recalculate risk
					$sc = (int) $db->scalar('SELECT COUNT(DISTINCT client_id) FROM pepban_ban_reports WHERE customer_id = ?', [$existing->id]);
					$rc = (int)$existing->reports_count + 1;
					$score = min(100, 20 + min($sc,5)*8 + min(max($rc-1,0),5)*4);
					$db->query('UPDATE pepban_banned_customers SET risk_score = ?, store_count = ? WHERE id = ?', [$score, $sc, $existing->id]);
					$updated++;
				} else {
					$db->insert('pepban_banned_customers', [
						'email'              => $email,
						'first_name'         => $first,
						'last_name'          => $last,
						'phone'              => $phone,
						'billing_address'    => '',
						'ip_address'         => '',
						'reason'             => $reason,
						'reported_by_site'   => 'bulk_import',
						'reported_by_client' => 0,
						'date_added'         => date('Y-m-d H:i:s'),
						'last_updated'       => date('Y-m-d H:i:s'),
						'status'             => 'active',
						'admin_notes'        => '',
						'reports_count'      => 1,
						'risk_score'         => 28,
						'store_count'        => 0,
					]);
					$added++;
				}
			}
			fclose($handle);

			$result_stats = compact('added', 'updated', 'skipped', 'errors');
			$msg      = "Import complete: <strong>{$added}</strong> added, <strong>{$updated}</strong> updated, <strong>{$skipped}</strong> skipped.";
			$msg_type = 'success';
		}
	}
}

require __DIR__ . '/../templates/admin-layout.php';
?>

<div class="pb-admin-section">
	<h2 style="margin-top:0">Bulk Import Banned Customers</h2>
	<p style="color:#7070a0;margin-bottom:24px">Upload a CSV to add multiple banned customers at once. Existing emails will have their report count incremented.</p>

	<?php if ($msg): ?>
		<div class="pepban-alert pepban-alert-<?= $msg_type ?>" style="margin-bottom:20px"><?= $msg ?></div>
		<?php if (!empty($result_stats['errors'])): ?>
			<ul style="color:#f0a500;margin-top:-12px;margin-bottom:20px;padding-left:20px;font-size:.85rem">
				<?php foreach (array_slice($result_stats['errors'], 0, 10) as $err): ?>
					<li><?= $err ?></li>
				<?php endforeach; ?>
				<?php if (count($result_stats['errors']) > 10): ?>
					<li>…and <?= count($result_stats['errors']) - 10 ?> more</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>

	<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px">

		<div style="background:var(--bg-2,#12121e);border:1px solid var(--border,#2a2a3e);border-radius:10px;padding:24px">
			<h3 style="margin-top:0;font-size:1rem">Upload CSV</h3>
			<form method="post" enctype="multipart/form-data">
				<?= csrf_field() ?>
				<p style="font-size:.82rem;color:#7070a0;margin-bottom:16px">
					Required column: <code>email</code><br>
					Optional columns: <code>first_name, last_name, phone, reason</code>
				</p>
				<input type="file" name="csv_file" accept=".csv,text/csv" required
				       style="display:block;width:100%;margin-bottom:16px;color:#e8e8f5;background:#080810;border:1px solid #2a2a3e;border-radius:6px;padding:8px 12px;cursor:pointer">
				<button type="submit" class="pepban-btn pepban-btn-primary">Import</button>
			</form>
		</div>

		<div style="background:var(--bg-2,#12121e);border:1px solid var(--border,#2a2a3e);border-radius:10px;padding:24px">
			<h3 style="margin-top:0;font-size:1rem">CSV Format</h3>
			<pre style="font-size:.78rem;color:#a0a0c0;background:#080810;border-radius:6px;padding:12px;overflow:auto;margin:0">email,first_name,last_name,phone,reason
scammer@example.com,John,Doe,555-1234,Chargeback fraud
bad@test.com,Jane,Smith,,Card testing
noreply@spam.net,,,, Bulk import</pre>
			<p style="font-size:.8rem;color:#7070a0;margin-top:12px;margin-bottom:0">
				Header row is optional. Only <code>email</code> is required. Invalid rows are skipped.
			</p>
		</div>

	</div>
</div>
