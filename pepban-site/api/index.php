<?php
// API is bootstrapped via the main index.php router, which sets up all includes.
// This file handles all /api/v1/* routes.

$method  = $_SERVER['REQUEST_METHOD'];
$path    = current_path(); // e.g. /api/v1/check
$segment = preg_replace('#^/api/v1/?#', '', $path); // e.g. "check"

$auth    = ApiAuth::authenticate();
$db      = Database::get();

// ── POST /api/v1/check ────────────────────────────────────────────────────────
if ($segment === 'check' && $method === 'POST') {
	$body  = ApiAuth::body();
	$email = strtolower(trim($body->email ?? ''));
	$ip    = trim($body->ip_address ?? '');

	if (!$email) ApiAuth::error('email is required', 422);

	// Check global IP block list first — fastest bail-out
	if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
		$blocked_ip = $db->fetch(
			'SELECT id FROM pepban_blocked_ips WHERE ip_address = ?',
			[$ip]
		);
		if ($blocked_ip) {
			ApiAuth::json(['banned' => true, 'whitelisted' => false, 'reason' => 'blocked_ip', 'customer' => null]);
		}
	}

	$customer = $db->fetch(
		"SELECT id, email, first_name, last_name, phone, status, reason, reports_count
		 FROM pepban_banned_customers WHERE email = ? AND status = 'active'",
		[$email]
	);

	// Check blocked domain even if not in banned_customers
	$email_domain = strtolower(substr(strrchr($email, '@'), 1));
	$blocked_domain = $db->fetch(
		'SELECT id FROM pepban_blocked_domains WHERE domain = ?',
		[$email_domain]
	);
	if ($blocked_domain) {
		ApiAuth::json(['banned' => true, 'whitelisted' => false, 'reason' => 'blocked_domain', 'customer' => null]);
	}

	if (!$customer) {
		ApiAuth::json(['banned' => false, 'whitelisted' => false]);
	}

	// Check per-site whitelist
	$whitelisted = $db->fetch(
		'SELECT id FROM pepban_whitelists WHERE customer_id = ? AND client_id = ?',
		[$customer->id, $auth->id]
	);

	$store_count = (int) $db->scalar(
		'SELECT COUNT(DISTINCT client_id) FROM pepban_ban_reports WHERE customer_id = ?',
		[$customer->id]
	);

	ApiAuth::json([
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
	]);
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

	if ($existing) {
		$customer_id = $existing->id;
		$db->query(
			'UPDATE pepban_banned_customers SET reports_count = reports_count + 1, last_updated = NOW() WHERE id = ?',
			[$customer_id]
		);
	} else {
		$first_name  = $body->first_name ?? '';
		$last_name   = $body->last_name  ?? '';
		$ban_reason  = $body->reason     ?? '';
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
	$rows = $db->fetchAll('SELECT id, domain, reason, date_added FROM pepban_blocked_domains ORDER BY date_added DESC');
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

// ── GET /api/v1/plugin/info ───────────────────────────────────────────────────
if ($segment === 'plugin/info' && $method === 'GET') {
	require_once __DIR__ . '/../includes/plugin-release.php';
	$bucket = (string) floor(time() / (12 * 3600));
	$token  = hash_hmac('sha256', 'dl:' . $bucket, SECRET_KEY);
	ApiAuth::json([
		'version'      => PEPBAN_PLUGIN_VERSION,
		'download_url' => rtrim(SITE_URL, '/') . '/download/client?token=' . $token,
		'details_url'  => rtrim(SITE_URL, '/') . '/changelog',
		'description'  => pepban_plugin_description(),
		'installation' => pepban_plugin_installation(),
		'changelog'    => pepban_plugin_changelog_html(),
	]);
}

// ── Fallback ──────────────────────────────────────────────────────────────────
ApiAuth::error('Endpoint not found', 404);
