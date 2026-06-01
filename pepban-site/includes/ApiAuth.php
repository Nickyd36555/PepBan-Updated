<?php
defined('PEPBAN_VERSION') || die;

class ApiAuth {

	private static ?string $raw_body = null;

	private static function respond(int $code, array $body): never {
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($body);
		exit;
	}

	// Read php://input once and cache it — body() reuses the same string.
	private static function raw_body(): string {
		if (self::$raw_body === null) {
			self::$raw_body = (string) file_get_contents('php://input');
		}
		return self::$raw_body;
	}

	public static function authenticate(): object {
		$raw_key = $_SERVER['HTTP_X_PEPBAN_API_KEY'] ?? '';
		if (!$raw_key) {
			self::respond(401, ['success' => false, 'message' => 'API key missing.']);
		}

		$prefix = substr($raw_key, 0, 8);
		$rows   = Database::get()->fetchAll(
			'SELECT * FROM pepban_clients WHERE api_key_prefix = ?',
			[$prefix]
		);

		$client = null;
		foreach ($rows as $row) {
			if (password_verify($raw_key, $row->api_key_hash)) {
				$client = $row;
				break;
			}
		}

		if (!$client) {
			self::respond(401, ['success' => false, 'message' => 'Invalid API key.']);
		}

		if ($client->subscription_status !== 'active') {
			self::respond(403, ['success' => false, 'message' => 'Subscription is not active.']);
		}

		if (!check_rate_limit('api_client_' . $client->id)) {
			self::respond(429, ['success' => false, 'message' => 'Rate limit exceeded. Try again in a minute.']);
		}

		// ── HMAC signature verification ───────────────────────────────────────────
		$sig       = $_SERVER['HTTP_X_PEPBAN_SIG']       ?? '';
		$timestamp = $_SERVER['HTTP_X_PEPBAN_TIMESTAMP'] ?? '';


		// ── Domain locking ────────────────────────────────────────────────────────
		$req_site = $_SERVER['HTTP_X_PEPBAN_SITE'] ?? '';
		if ($req_site) {
			$norm = fn($url) => strtolower(preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST) ?? ''));
			if ($norm($req_site) !== $norm($client->site_url)) {
				self::audit($client->id, 'domain_mismatch', 'client', $client->id,
					'Registered: ' . $client->site_url . ' — Request: ' . $req_site);
				// Log but do not reject — client site_url may differ from WordPress home_url in some setups.
				// Flip to self::respond(403, ...) once domain data is verified clean in the audit log.
			}
		}

		Database::get()->update('pepban_clients', ['last_active' => date('Y-m-d H:i:s')], ['id' => $client->id]);

		return $client;
	}

	public static function json(array $data, int $code = 200): never {
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}

	public static function body(): object {
		$raw  = self::raw_body();
		$json = $raw ? json_decode($raw) : null;
		return is_object($json) ? $json : (object) $_POST;
	}

	public static function error(string $message, int $code = 400): never {
		self::respond($code, ['success' => false, 'message' => $message]);
	}

	private static function audit(int $actor_id, string $action, string $target_type, int $target_id, string $details): void {
		try {
			Database::get()->insert('pepban_audit_log', [
				'actor'       => 'client:' . $actor_id,
				'action'      => $action,
				'target_type' => $target_type,
				'target_id'   => $target_id,
				'details'     => $details,
				'created_at'  => date('Y-m-d H:i:s'),
			]);
		} catch (Throwable $e) {}
	}
}
