<?php
defined('PEPBAN_VERSION') || die;

class Database {

	private static ?Database $instance = null;
	private PDO $pdo;

	private function __construct() {
		$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
		$this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_EMULATE_PREPARES   => false,
		]);
	}

	public static function get(): self {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	public function query(string $sql, array $params = []): PDOStatement {
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		return $stmt;
	}

	public function fetch(string $sql, array $params = []): ?object {
		$row = $this->query($sql, $params)->fetch();
		return $row ?: null;
	}

	public function fetchAll(string $sql, array $params = []): array {
		return $this->query($sql, $params)->fetchAll();
	}

	public function scalar(string $sql, array $params = []): mixed {
		return $this->query($sql, $params)->fetchColumn();
	}

	public function insert(string $table, array $data): int {
		$cols   = implode(', ', array_keys($data));
		$places = implode(', ', array_fill(0, count($data), '?'));
		$this->query("INSERT INTO {$table} ({$cols}) VALUES ({$places})", array_values($data));
		return (int) $this->pdo->lastInsertId();
	}

	public function update(string $table, array $data, array $where): int {
		$set   = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
		$conds = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
		return $this->query(
			"UPDATE {$table} SET {$set} WHERE {$conds}",
			[...array_values($data), ...array_values($where)]
		)->rowCount();
	}

	public function delete(string $table, array $where): int {
		$conds = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
		return $this->query("DELETE FROM {$table} WHERE {$conds}", array_values($where))->rowCount();
	}

	public static function maybe_migrate(): void {
		$db   = self::get();
		// pepban_banned_customers columns
		$rows = $db->fetchAll(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pepban_banned_customers'"
		);
		$existing = array_map(fn($r) => (array)$r, $rows);
		$cols     = array_column($existing, 'COLUMN_NAME');
		if (!in_array('risk_score', $cols, true)) {
			$db->query("ALTER TABLE pepban_banned_customers ADD COLUMN risk_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER reports_count");
		}
		if (!in_array('store_count', $cols, true)) {
			$db->query("ALTER TABLE pepban_banned_customers ADD COLUMN store_count SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER risk_score");
		}
		// pepban_clients columns
		$rows2 = $db->fetchAll(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pepban_clients'"
		);
		$cols2 = array_column(array_map(fn($r) => (array)$r, $rows2), 'COLUMN_NAME');
		if (!in_array('api_key', $cols2, true)) {
			$db->query("ALTER TABLE pepban_clients ADD COLUMN api_key VARCHAR(80) NOT NULL DEFAULT '' AFTER api_key_prefix");
		}
	}
}
