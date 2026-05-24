<?php
defined('PEPBAN_VERSION') || die;

class ApiAuth {

	private static function respond(int $code, array $body): never {
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($body);
		exit;
	}

	public static function authenticate(): object {
		$raw_key = $_SERVER['HTTP_X_PEPBAN_API_KEY'] ?? '';
		if (!$raw_key) {
			self::respond(401, ['success' => false, 'message' => 'API key missing. Send X-PepBan-API-Key header.']);
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

		// Update last_active (non-blocking)
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
		$raw  = file_get_contents('php://input');
		$json = $raw ? json_decode($raw) : null;
		return is_object($json) ? $json : (object) $_POST;
	}

	public static function error(string $message, int $code = 400): never {
		self::respond($code, ['success' => false, 'message' => $message]);
	}
}
