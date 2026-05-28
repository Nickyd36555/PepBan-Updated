<?php
defined('PEPBAN_VERSION') || die;

class Auth {

	public static function start(): void {
		if (session_status() === PHP_SESSION_NONE) {
			session_set_cookie_params([
				'lifetime' => 0,
				'path'     => '/',
				'secure'   => defined('COOKIE_SECURE') ? COOKIE_SECURE : true,
				'httponly' => true,
				'samesite' => 'Lax',
			]);
			session_start();
		}
	}

	// ── Client auth ───────────────────────────────────────────────────────────

	public static function loginClient(object $client): void {
		session_regenerate_id(true);
		$_SESSION['pb_client_id'] = $client->id;
	}

	public static function isClient(): bool {
		return !empty($_SESSION['pb_client_id']);
	}

	public static function client(): ?object {
		if (!self::isClient()) return null;
		return Database::get()->fetch(
			'SELECT * FROM pepban_clients WHERE id = ?',
			[$_SESSION['pb_client_id']]
		);
	}

	public static function requireClient(): void {
		if (!self::isClient()) redirect('/login', ['next' => current_path()]);
	}

	// ── Admin auth ────────────────────────────────────────────────────────────

	public static function loginAdmin(): void {
		session_regenerate_id(true);
		$_SESSION['pb_admin'] = true;
	}

	public static function isAdmin(): bool {
		return !empty($_SESSION['pb_admin']);
	}

	public static function requireAdmin(): void {
		if (!self::isAdmin()) redirect('/admin/login');
	}

	// ── Shared ────────────────────────────────────────────────────────────────

	public static function logout(): void {
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$p = session_get_cookie_params();
			setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
		}
		session_destroy();
	}

	// ── Password reset tokens ─────────────────────────────────────────────────

	public static function createResetToken(int $client_id): string {
		$db    = Database::get();
		$token = bin2hex(random_bytes(32));
		$hash  = hash_hmac('sha256', $token, SECRET_KEY);
		$db->query(
			'DELETE FROM pepban_password_resets WHERE client_id = ?',
			[$client_id]
		);
		$db->insert('pepban_password_resets', [
			'client_id'  => $client_id,
			'token_hash' => $hash,
			'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
		]);
		return $token;
	}

	public static function verifyResetToken(string $token): ?object {
		$hash = hash_hmac('sha256', $token, SECRET_KEY);
		return Database::get()->fetch(
			"SELECT r.*, c.owner_email FROM pepban_password_resets r
			 JOIN pepban_clients c ON r.client_id = c.id
			 WHERE r.token_hash = ? AND r.expires_at > NOW()",
			[$hash]
		);
	}

	public static function deleteResetToken(string $token): void {
		$hash = hash_hmac('sha256', $token, SECRET_KEY);
		Database::get()->query('DELETE FROM pepban_password_resets WHERE token_hash = ?', [$hash]);
	}
}
