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
		if (!in_array('flagged_for_review', $cols, true)) {
			$db->query("ALTER TABLE pepban_banned_customers ADD COLUMN flagged_for_review TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER store_count");
		}
		// pepban_clients columns — drop plaintext api_key, keep only hash + prefix
		$rows2 = $db->fetchAll(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pepban_clients'"
		);
		$cols2 = array_column(array_map(fn($r) => (array)$r, $rows2), 'COLUMN_NAME');
		if (in_array('api_key', $cols2, true)) {
			$db->query("ALTER TABLE pepban_clients DROP COLUMN api_key");
		}
		// audit log table
		$db->query("CREATE TABLE IF NOT EXISTS pepban_audit_log (
			id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			actor       VARCHAR(50)  NOT NULL DEFAULT 'admin',
			action      VARCHAR(100) NOT NULL,
			target_type VARCHAR(50)  NOT NULL DEFAULT '',
			target_id   INT UNSIGNED NOT NULL DEFAULT 0,
			details     TEXT         NOT NULL,
			created_at  DATETIME     NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		$db->query("ALTER TABLE pepban_banned_customers AUTO_INCREMENT = 7029");
		// feedback table
		$db->query("CREATE TABLE IF NOT EXISTS pepban_feedback (
			id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			client_id    INT UNSIGNED NOT NULL DEFAULT 0,
			client_email VARCHAR(255) NOT NULL DEFAULT '',
			message      TEXT         NOT NULL,
			created_at   DATETIME     NOT NULL,
			read_at      DATETIME     DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		$db->query("CREATE TABLE IF NOT EXISTS pepban_disputes (
			id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
			email       VARCHAR(200) NOT NULL,
			name        VARCHAR(200) NOT NULL DEFAULT '',
			store_hint  VARCHAR(255) NOT NULL DEFAULT '',
			reason      TEXT         NOT NULL,
			status      VARCHAR(20)  NOT NULL DEFAULT 'open',
			admin_notes TEXT         NOT NULL DEFAULT '',
			date_added  DATETIME     NOT NULL,
			PRIMARY KEY (id),
			KEY email  (email),
			KEY status (status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		// Generate static PNG favicon so Nginx serves it directly without going through PHP
		$favicon_png = __DIR__ . '/../favicon.png';
		$favicon_ico = __DIR__ . '/../favicon.ico';
		if (!file_exists($favicon_png) && function_exists('imagecreatetruecolor')) {
			$size = 64;
			$img  = imagecreatetruecolor($size, $size);
			imagealphablending($img, false);
			imagesavealpha($img, true);
			$t = imagecolorallocatealpha($img, 0, 0, 0, 127);
			imagefill($img, 0, 0, $t);
			$bg    = imagecolorallocate($img, 12, 12, 30);
			$red   = imagecolorallocate($img, 220, 38, 38);
			$dark  = imagecolorallocate($img, 185, 28, 28);
			$white = imagecolorallocate($img, 255, 255, 255);
			imagefilledrectangle($img, 0, 0, 63, 63, $bg);
			imagefilledpolygon($img, [32,8, 52,16, 56,36, 32,58, 8,36, 12,16], $red);
			imagefilledpolygon($img, [32,14, 48,21, 51,36, 32,53, 13,36, 16,21], $dark);
			$f = 5;
			imagestring($img, $f, (int)(($size - 2 * imagefontwidth($f)) / 2), (int)(($size - imagefontheight($f)) / 2), 'PB', $white);
			imagepng($img, $favicon_png);
			copy($favicon_png, $favicon_ico);
			imagedestroy($img);
		}
	}
}
