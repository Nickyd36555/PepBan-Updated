<?php
// API is bootstrapped via the main index.php router, which sets up all includes.
// This file handles all /api/v1/* routes.

$method  = $_SERVER['REQUEST_METHOD'];
$path    = current_path(); // e.g. /api/v1/check
$segment = preg_replace('#^/api/v1/?#', '', $path); // e.g. "check"
$db      = Database::get();

// ── Admin API routes (Bearer-token auth, no API key needed) ───────────────────
if (str_starts_with($segment, 'admin')) {
	require_once __DIR__ . '/../includes/AdminApiAuth.php';
	$aseg = ltrim(substr($segment, 5), '/'); // strip "admin" and leading slash

	// POST /api/v1/admin/login ─────────────────────────────────────────────────
	if ($aseg === 'login' && $method === 'POST') {
		$body = AdminApiAuth::body();
		$pw   = trim($body->password ?? '');
		if (!$pw || !password_verify($pw, ADMIN_PASSWORD_HASH)) {
			AdminApiAuth::error('Invalid credentials', 401);
		}
		$token = bin2hex(random_bytes(32));
		$hash  = hash('sha256', $token);
		$exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
		$db->insert('pepban_admin_tokens', [
			'token_hash' => $hash,
			'created_at' => date('Y-m-d H:i:s'),
			'expires_at' => $exp,
		]);
		AdminApiAuth::json(['token' => $token, 'expires_at' => $exp]);
	}

	// All remaining admin routes require a valid token
	AdminApiAuth::require();

	// POST /api/v1/admin/logout ────────────────────────────────────────────────
	if ($aseg === 'logout' && $method === 'POST') {
		$hash = hash('sha256', AdminApiAuth::getToken());
		$db->query("DELETE FROM pepban_admin_tokens WHERE token_hash = ?", [$hash]);
		AdminApiAuth::json(['success' => true]);
	}

	// GET /api/v1/admin/dashboard ──────────────────────────────────────────────
	if ($aseg === 'dashboard' && $method === 'GET') {
		AdminApiAuth::json([
			'active_bans'    => (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE status = 'active'"),
			'active_clients' => (int) $db->scalar("SELECT COUNT(*) FROM pepban_clients WHERE subscription_status = 'active'"),
			'total_reports'  => (int) $db->scalar("SELECT COUNT(*) FROM pepban_ban_reports"),
			'new_bans_30d'   => (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
			'open_disputes'  => (int) $db->scalar("SELECT COUNT(*) FROM pepban_disputes WHERE status = 'open'"),
		]);
	}

	// GET /api/v1/admin/banned ─────────────────────────────────────────────────
	if ($aseg === 'banned' && $method === 'GET') {
		$p      = AdminApiAuth::paginate((int) ($_GET['page'] ?? 1));
		$search = trim($_GET['search'] ?? '');
		$status = trim($_GET['status'] ?? 'active');
		$where  = $status ? "WHERE status = ?" : "WHERE 1=1";
		$params = $status ? [$status] : [];
		if ($search) {
			$like    = '%' . $search . '%';
			$where  .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
			$params  = array_merge($params, [$like, $like, $like, $like]);
		}
		$total = (int) $db->scalar("SELECT COUNT(*) FROM pepban_banned_customers $where", $params);
		$rows  = $db->fetchAll(
			"SELECT id, email, first_name, last_name, phone, status, reason, reports_count, store_count, date_added, flagged_for_review
			 FROM pepban_banned_customers $where ORDER BY date_added DESC LIMIT {$p['limit']} OFFSET {$p['offset']}",
			$params
		);
		AdminApiAuth::json(['customers' => $rows, 'total' => $total, 'page' => $p['page'], 'pages' => (int) ceil($total / $p['limit'])]);
	}

	// GET /api/v1/admin/banned/{id} ────────────────────────────────────────────
	if (preg_match('#^banned/(\d+)$#', $aseg, $m) && $method === 'GET') {
		$id  = (int) $m[1];
		$row = $db->fetch("SELECT * FROM pepban_banned_customers WHERE id = ?", [$id]);
		if (!$row) AdminApiAuth::error('Not found', 404);
		$reports = $db->fetchAll(
			"SELECT r.id, r.site_url, r.reason, r.order_id, r.date_reported, c.owner_name
			 FROM pepban_ban_reports r LEFT JOIN pepban_clients c ON c.id = r.client_id
			 WHERE r.customer_id = ? ORDER BY r.date_reported DESC LIMIT 20",
			[$id]
		);
		AdminApiAuth::json(['customer' => $row, 'reports' => $reports]);
	}

	// PATCH /api/v1/admin/banned/{id} ──────────────────────────────────────────
	if (preg_match('#^banned/(\d+)$#', $aseg, $m) && $method === 'PATCH') {
		$id   = (int) $m[1];
		$body = AdminApiAuth::body();
		$upd  = [];
		if (isset($body->status))      $upd['status']       = $body->status;
		if (isset($body->admin_notes)) $upd['admin_notes']   = $body->admin_notes;
		if (isset($body->reason))      $upd['reason']        = $body->reason;
		if (!$upd) AdminApiAuth::error('Nothing to update', 422);
		$upd['last_updated'] = date('Y-m-d H:i:s');
		$db->update('pepban_banned_customers', $upd, ['id' => $id]);
		if (isset($body->status)) {
			$customer = $db->fetch("SELECT email FROM pepban_banned_customers WHERE id = ?", [$id]);
			$db->insert('pepban_audit_log', ['actor' => 'admin', 'action' => 'status_change', 'target_type' => 'customer', 'target_id' => $id, 'details' => ($customer->email ?? '') . ' → ' . $body->status, 'created_at' => date('Y-m-d H:i:s')]);
		}
		AdminApiAuth::json(['success' => true]);
	}

	// POST /api/v1/admin/banned ────────────────────────────────────────────────
	if ($aseg === 'banned' && $method === 'POST') {
		$body  = AdminApiAuth::body();
		$email = strtolower(trim($body->email ?? ''));
		if (!$email) AdminApiAuth::error('email is required', 422);
		$existing = $db->fetch("SELECT id FROM pepban_banned_customers WHERE email = ?", [$email]);
		if ($existing) AdminApiAuth::error('Customer already in database', 409);
		$id = $db->insert('pepban_banned_customers', [
			'email'              => $email,
			'first_name'         => trim($body->first_name ?? ''),
			'last_name'          => trim($body->last_name ?? ''),
			'phone'              => trim($body->phone ?? ''),
			'billing_address'    => trim($body->billing_address ?? ''),
			'ip_address'         => trim($body->ip_address ?? ''),
			'reason'             => trim($body->reason ?? ''),
			'reported_by_site'   => 'admin',
			'reported_by_client' => 0,
			'date_added'         => date('Y-m-d H:i:s'),
			'last_updated'       => date('Y-m-d H:i:s'),
			'status'             => 'active',
			'admin_notes'        => '',
			'reports_count'      => 1,
		]);
		$db->insert('pepban_audit_log', ['actor' => 'admin', 'action' => 'ban_add', 'target_type' => 'customer', 'target_id' => $id, 'details' => $email . ' (iOS)', 'created_at' => date('Y-m-d H:i:s')]);
		AdminApiAuth::json(['success' => true, 'id' => $id], 201);
	}

	// GET /api/v1/admin/clients ────────────────────────────────────────────────
	if ($aseg === 'clients' && $method === 'GET') {
		$p      = AdminApiAuth::paginate((int) ($_GET['page'] ?? 1));
		$search = trim($_GET['search'] ?? '');
		$status = trim($_GET['status'] ?? '');
		$where  = $status ? "WHERE subscription_status = ?" : "WHERE 1=1";
		$params = $status ? [$status] : [];
		if ($search) {
			$like    = '%' . $search . '%';
			$where  .= " AND (owner_name LIKE ? OR owner_email LIKE ? OR site_url LIKE ?)";
			$params  = array_merge($params, [$like, $like, $like]);
		}
		$total = (int) $db->scalar("SELECT COUNT(*) FROM pepban_clients $where", $params);
		$rows  = $db->fetchAll(
			"SELECT id, owner_name, owner_email, site_url, subscription_status, date_registered, last_active
			 FROM pepban_clients $where ORDER BY date_registered DESC LIMIT {$p['limit']} OFFSET {$p['offset']}",
			$params
		);
		AdminApiAuth::json(['clients' => $rows, 'total' => $total, 'page' => $p['page'], 'pages' => (int) ceil($total / $p['limit'])]);
	}

	// GET /api/v1/admin/clients/{id} ───────────────────────────────────────────
	if (preg_match('#^clients/(\d+)$#', $aseg, $m) && $method === 'GET') {
		$id     = (int) $m[1];
		$client = $db->fetch("SELECT id, owner_name, owner_email, site_url, subscription_status, date_registered, last_active, activated_at, admin_notes FROM pepban_clients WHERE id = ?", [$id]);
		if (!$client) AdminApiAuth::error('Not found', 404);
		AdminApiAuth::json([
			'client'          => $client,
			'report_count'    => (int) $db->scalar("SELECT COUNT(*) FROM pepban_ban_reports WHERE client_id = ?", [$id]),
			'whitelist_count' => (int) $db->scalar("SELECT COUNT(*) FROM pepban_whitelists WHERE client_id = ?", [$id]),
			'recent_reports'  => $db->fetchAll(
				"SELECT r.date_reported, r.reason, b.email, b.first_name, b.last_name
				 FROM pepban_ban_reports r JOIN pepban_banned_customers b ON b.id = r.customer_id
				 WHERE r.client_id = ? ORDER BY r.date_reported DESC LIMIT 10",
				[$id]
			),
		]);
	}

	// POST /api/v1/admin/clients/{id}/activate ─────────────────────────────────
	if (preg_match('#^clients/(\d+)/activate$#', $aseg, $m) && $method === 'POST') {
		$id = (int) $m[1];
		$db->update('pepban_clients', ['subscription_status' => 'active', 'activated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
		AdminApiAuth::json(['success' => true]);
	}

	// POST /api/v1/admin/clients/{id}/deactivate ───────────────────────────────
	if (preg_match('#^clients/(\d+)/deactivate$#', $aseg, $m) && $method === 'POST') {
		$id = (int) $m[1];
		$db->update('pepban_clients', ['subscription_status' => 'inactive'], ['id' => $id]);
		AdminApiAuth::json(['success' => true]);
	}

	// GET /api/v1/admin/disputes ───────────────────────────────────────────────
	if ($aseg === 'disputes' && $method === 'GET') {
		$p      = AdminApiAuth::paginate((int) ($_GET['page'] ?? 1));
		$status = trim($_GET['status'] ?? 'open');
		$where  = $status ? "WHERE status = ?" : "WHERE 1=1";
		$params = $status ? [$status] : [];
		$total = (int) $db->scalar("SELECT COUNT(*) FROM pepban_disputes $where", $params);
		$rows  = $db->fetchAll(
			"SELECT * FROM pepban_disputes $where ORDER BY date_added DESC LIMIT {$p['limit']} OFFSET {$p['offset']}",
			$params
		);
		AdminApiAuth::json(['disputes' => $rows, 'total' => $total, 'page' => $p['page'], 'pages' => (int) ceil($total / $p['limit'])]);
	}

	// POST /api/v1/admin/disputes/{id}/resolve ─────────────────────────────────
	if (preg_match('#^disputes/(\d+)/resolve$#', $aseg, $m) && $method === 'POST') {
		$id      = (int) $m[1];
		$dispute = $db->fetch("SELECT email FROM pepban_disputes WHERE id = ?", [$id]);
		$db->update('pepban_disputes', ['status' => 'resolved'], ['id' => $id]);
		if ($dispute) {
			$db->query("UPDATE pepban_banned_customers SET status = 'inactive', last_updated = NOW() WHERE email = ?", [$dispute->email]);
		}
		AdminApiAuth::json(['success' => true]);
	}

	// POST /api/v1/admin/disputes/{id}/dismiss ─────────────────────────────────
	if (preg_match('#^disputes/(\d+)/dismiss$#', $aseg, $m) && $method === 'POST') {
		$id = (int) $m[1];
		$db->update('pepban_disputes', ['status' => 'dismissed'], ['id' => $id]);
		AdminApiAuth::json(['success' => true]);
	}

	// GET /api/v1/admin/audit ──────────────────────────────────────────────────
	if ($aseg === 'audit' && $method === 'GET') {
		$p     = AdminApiAuth::paginate((int) ($_GET['page'] ?? 1), 50);
		$total = (int) $db->scalar("SELECT COUNT(*) FROM pepban_audit_log");
		$rows  = $db->fetchAll(
			"SELECT * FROM pepban_audit_log ORDER BY created_at DESC LIMIT {$p['limit']} OFFSET {$p['offset']}"
		);
		AdminApiAuth::json(['entries' => $rows, 'total' => $total, 'page' => $p['page'], 'pages' => (int) ceil($total / $p['limit'])]);
	}

	AdminApiAuth::error('Admin endpoint not found', 404);
}

// ── Store API routes — require API key auth ────────────────────────────────────
$auth = ApiAuth::authenticate();

// ── POST /api/v1/check ────────────────────────────────────────────────────────
if ($segment === 'check' && $method === 'POST') {
	$body  = ApiAuth::body();
	$email = strtolower(trim($body->email ?? ''));
	$ip    = trim($body->ip_address ?? '');

	if (!$email) ApiAuth::error('email is required', 422);

	$response = ['banned' => false, 'whitelisted' => false, 'report_count' => 0, 'store_count' => 0, 'customer' => null];

	// Check global IP block list first
	if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
		$blocked_ip = $db->fetch('SELECT id FROM pepban_blocked_ips WHERE ip_address = ?', [$ip]);
		if ($blocked_ip) {
			$response = ['banned' => true, 'whitelisted' => false, 'reason' => 'blocked_ip', 'customer' => null];
		}
	}

	// Check blocked domain
	if (!$response['banned']) {
		$email_domain   = strtolower(substr(strrchr($email, '@'), 1));
		$blocked_domain = $db->fetch('SELECT id FROM pepban_blocked_domains WHERE domain = ?', [$email_domain]);
		if ($blocked_domain) {
			$response = ['banned' => true, 'whitelisted' => false, 'reason' => 'blocked_domain', 'customer' => null];
		}
	}

	// Check banned customers table
	if (!$response['banned']) {
		$customer = $db->fetch(
			"SELECT id, email, first_name, last_name, phone, status, reason, reports_count
			 FROM pepban_banned_customers WHERE email = ? AND status = 'active'",
			[$email]
		);

		if ($customer) {
			$whitelisted = $db->fetch(
				'SELECT id FROM pepban_whitelists WHERE customer_id = ? AND client_id = ?',
				[$customer->id, $auth->id]
			);
			$store_count = (int) $db->scalar(
				'SELECT COUNT(DISTINCT client_id) FROM pepban_ban_reports WHERE customer_id = ?',
				[$customer->id]
			);
			$response = [
				'banned'       => true,
				'whitelisted'  => (bool) $whitelisted,
				'report_count' => (int) $customer->reports_count,
				'store_count'  => $store_count,
				'customer'     => [
					'id'         => $customer->id,
					'email'      => $customer->email,
					'first_name' => $customer->first_name,
					'last_name'  => $customer->last_name,
					'phone'      => $customer->phone,
					'reason'     => $customer->reason,
				],
			];
		}
	}

	// Send store owner alert via server SMTP — plugin passes notify_store=true with a rate-limited transient
	if ($response['banned'] && !$response['whitelisted'] && !empty($body->notify_store)) {
		$alert_to = trim($body->alert_email ?? '');
		if (!$alert_to || !filter_var($alert_to, FILTER_VALIDATE_EMAIL)) {
			$alert_to = $auth->owner_email ?? '';
		}
		if ($alert_to) {
			try {
				Mailer::storeAlert($alert_to, $email, $response, $auth->site_url ?? '');
			} catch (Throwable $e) {
				error_log('PepBan storeAlert error: ' . $e->getMessage());
			}
		}
	}

	ApiAuth::json($response);
}

// ── POST /api/v1/report ───────────────────────────────────────────────────────
if ($segment === 'report' && $method === 'POST') {
	$body  = ApiAuth::body();
	$email = strtolower(trim($body->email ?? ''));

	if (!$email) ApiAuth::error('email is required', 422);

	// Upsert banned customer
	$existing = $db->fetch(
		'SELECT id FROM pepban_banned_customers WHERE email = ?',
		[$email]
	);

	$first_name = trim($body->first_name ?? '');
	$last_name  = trim($body->last_name  ?? '');
	$ban_reason = $body->reason ?? '';

	if ($existing) {
		$customer_id = $existing->id;
		$db->query(
			'UPDATE pepban_banned_customers SET reports_count = reports_count + 1, last_updated = NOW() WHERE id = ?',
			[$customer_id]
		);
	} else {
		$customer_id = $db->insert('pepban_banned_customers', [
			'email'              => $email,
			'first_name'         => $first_name,
			'last_name'          => $last_name,
			'phone'              => $body->phone       ?? '',
			'billing_address'    => $body->billing_address ?? '',
			'ip_address'         => $body->ip_address  ?? '',
			'reason'             => $ban_reason,
			'reported_by_site'   => $auth->site_url,
			'reported_by_client' => $auth->id,
			'date_added'         => date('Y-m-d H:i:s'),
			'last_updated'       => date('Y-m-d H:i:s'),
			'status'             => 'active',
			'admin_notes'        => '',
			'reports_count'      => 1,
		]);
		Mailer::adminNewBan($email, trim($first_name . ' ' . $last_name), $ban_reason, $auth->site_url ?? 'API');
	}

	if (!empty($body->notify_customer)) {
		$reporter_name = trim($body->reporter_name ?? '');
		Mailer::customerBanned($email, trim($first_name . ' ' . $last_name), $ban_reason, $reporter_name);
	}

	// Insert report record
	$db->insert('pepban_ban_reports', [
		'customer_id'   => $customer_id,
		'client_id'     => $auth->id,
		'site_url'      => $auth->site_url,
		'reason'        => $body->reason    ?? '',
		'order_id'      => $body->order_id  ?? '',
		'date_reported' => date('Y-m-d H:i:s'),
	]);

	$db->insert('pepban_audit_log', [
		'actor'       => 'client:' . $auth->id,
		'action'      => 'api_report',
		'target_type' => 'customer',
		'target_id'   => $customer_id,
		'details'     => $email . ' via ' . ($auth->site_url ?? ''),
		'created_at'  => date('Y-m-d H:i:s'),
	]);
	ApiAuth::json(['success' => true, 'customer_id' => $customer_id]);
}

// ── POST /api/v1/whitelist/add ────────────────────────────────────────────────
if ($segment === 'whitelist/add' && $method === 'POST') {
	$body        = ApiAuth::body();
	$customer_id = (int) ($body->customer_id ?? 0);

	if (!$customer_id) ApiAuth::error('customer_id is required', 422);

	$customer = $db->fetch(
		'SELECT id FROM pepban_banned_customers WHERE id = ? AND status = ?',
		[$customer_id, 'active']
	);
	if (!$customer) ApiAuth::error('Customer not found or not banned', 404);

	$exists = $db->fetch(
		'SELECT id FROM pepban_whitelists WHERE customer_id = ? AND client_id = ?',
		[$customer_id, $auth->id]
	);
	if (!$exists) {
		$db->insert('pepban_whitelists', [
			'customer_id' => $customer_id,
			'client_id'   => $auth->id,
			'date_added'  => date('Y-m-d H:i:s'),
		]);
	}

	ApiAuth::json(['success' => true, 'whitelisted' => true]);
}

// ── POST /api/v1/whitelist/remove ─────────────────────────────────────────────
if ($segment === 'whitelist/remove' && $method === 'POST') {
	$body        = ApiAuth::body();
	$customer_id = (int) ($body->customer_id ?? 0);

	if (!$customer_id) ApiAuth::error('customer_id is required', 422);

	$db->query(
		'DELETE FROM pepban_whitelists WHERE customer_id = ? AND client_id = ?',
		[$customer_id, $auth->id]
	);

	ApiAuth::json(['success' => true, 'whitelisted' => false]);
}

// ── GET /api/v1/whitelist ─────────────────────────────────────────────────────
if ($segment === 'whitelist' && $method === 'GET') {
	$rows = $db->fetchAll(
		'SELECT w.customer_id, b.email, b.first_name, b.last_name
		 FROM pepban_whitelists w
		 JOIN pepban_banned_customers b ON b.id = w.customer_id
		 WHERE w.client_id = ?',
		[$auth->id]
	);

	ApiAuth::json(['whitelisted' => $rows]);
}

// ── GET /api/v1/status ────────────────────────────────────────────────────────
if ($segment === 'status' && $method === 'GET') {
	ApiAuth::json([
		'status'  => 'ok',
		'version' => PEPBAN_VERSION,
		'client'  => [
			'id'                  => $auth->id,
			'owner_name'          => $auth->owner_name,
			'subscription_status' => $auth->subscription_status,
		],
	]);
}

// ── GET /api/v1/blocked-domains ──────────────────────────────────────────────
if ($segment === 'blocked-domains' && $method === 'GET') {
	$rows = $db->fetchAll('SELECT domain, reason FROM pepban_blocked_domains ORDER BY domain ASC');
	ApiAuth::json(['blocked_domains' => $rows]);
}

// ── POST /api/v1/blocked-domains/add ─────────────────────────────────────────
if ($segment === 'blocked-domains/add' && $method === 'POST') {
	$body   = ApiAuth::body();
	$domain = strtolower(trim(ltrim($body->domain ?? '', '@')));
	$reason = trim($body->reason ?? '');

	if (!$domain) ApiAuth::error('domain is required', 422);

	try {
		$db->insert('pepban_blocked_domains', [
			'domain'     => $domain,
			'reason'     => $reason,
			'date_added' => date('Y-m-d H:i:s'),
		]);
		ApiAuth::json(['success' => true, 'domain' => $domain]);
	} catch (Exception $e) {
		ApiAuth::error('Domain already blocked or invalid.', 409);
	}
}

// ── POST /api/v1/blocked-domains/remove ──────────────────────────────────────
if ($segment === 'blocked-domains/remove' && $method === 'POST') {
	$body   = ApiAuth::body();
	$domain = strtolower(trim(ltrim($body->domain ?? '', '@')));

	if (!$domain) ApiAuth::error('domain is required', 422);

	$db->query('DELETE FROM pepban_blocked_domains WHERE domain = ?', [$domain]);
	ApiAuth::json(['success' => true]);
}

// ── GET /api/v1/blocked-ips ───────────────────────────────────────────────────
if ($segment === 'blocked-ips' && $method === 'GET') {
	$ips  = $db->fetchAll('SELECT ip_address FROM pepban_blocked_ips');
	$list = array_map(fn($r) => $r->ip_address, $ips);
	ApiAuth::json(['blocked_ips' => $list]);
}

// ── POST /api/v1/test-alert ───────────────────────────────────────────────────
if ($segment === 'test-alert' && $method === 'POST') {
	$body     = ApiAuth::body();
	$alert_to = trim($body->alert_email ?? '');
	if (!$alert_to || !filter_var($alert_to, FILTER_VALIDATE_EMAIL)) {
		$alert_to = $auth->owner_email ?? '';
	}
	if (!$alert_to) ApiAuth::error('No alert email configured', 422);

	$fake_response = [
		'banned'       => true,
		'whitelisted'  => false,
		'report_count' => 3,
		'store_count'  => 2,
		'customer'     => [
			'id'         => 0,
			'email'      => 'test-customer@example.com',
			'first_name' => 'Test',
			'last_name'  => 'Customer',
			'phone'      => '',
			'reason'     => 'This is a test alert — your store alerts are working correctly.',
		],
	];

	try {
		Mailer::storeAlert($alert_to, 'test-customer@example.com', $fake_response, $auth->site_url ?? '');
		ApiAuth::json(['success' => true, 'sent_to' => $alert_to]);
	} catch (Throwable $e) {
		error_log('PepBan test-alert error: ' . $e->getMessage());
		ApiAuth::error('Failed to send test email: ' . $e->getMessage(), 500);
	}
}

// ── GET /api/v1/plugin/info ───────────────────────────────────────────────────
if ($segment === 'plugin/info' && $method === 'GET') {
	require_once __DIR__ . '/../includes/plugin-release.php';
	$bucket = (string) floor(time() / (12 * 3600));
	$token  = hash_hmac('sha256', 'dl:' . $bucket, SECRET_KEY);
	ApiAuth::json([
		'version'      => PEPBAN_PLUGIN_VERSION,
		'download_url' => rtrim(SITE_URL, '/') . '/download/client?token=' . $token,
		'details_url'  => rtrim(SITE_URL, '/') . '/portal',
		'description'  => pepban_plugin_description(),
		'installation' => pepban_plugin_installation(),
		'changelog'    => pepban_plugin_changelog_html(),
	]);
}

// ── Fallback ──────────────────────────────────────────────────────────────────
ApiAuth::error('Endpoint not found', 404);
