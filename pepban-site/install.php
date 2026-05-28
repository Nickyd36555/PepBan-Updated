<?php
/**
 * PepBan Hub — Database Installer
 * Run once: https://your-domain.com/install.php
 * DELETE THIS FILE after installation.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple protection: require a token in the URL
$token = get_param('token');
$expected = hash_hmac('sha256', 'install', SECRET_KEY);
if (!hash_equals($expected, $token)) {
	die('Access denied.');
}

try {
	$db  = Database::get();
	$sql = <<<SQL

CREATE TABLE IF NOT EXISTS pepban_clients (
	id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
	owner_name          VARCHAR(200)  NOT NULL DEFAULT '',
	owner_email         VARCHAR(200)  NOT NULL,
	password_hash       VARCHAR(255)  NOT NULL DEFAULT '',
	site_url            VARCHAR(255)  NOT NULL,
	api_key_hash        VARCHAR(255)  NOT NULL,
	api_key_prefix      VARCHAR(10)   NOT NULL,
	subscription_status VARCHAR(20)   NOT NULL DEFAULT 'pending',
	created_at          DATETIME      NOT NULL,
	activated_at        DATETIME      DEFAULT NULL,
	last_active         DATETIME      DEFAULT NULL,
	admin_notes         TEXT          NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE KEY api_key_hash (api_key_hash),
	KEY subscription_status (subscription_status),
	KEY owner_email (owner_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_banned_customers (
	id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
	email               VARCHAR(200)  NOT NULL,
	first_name          VARCHAR(100)  NOT NULL DEFAULT '',
	last_name           VARCHAR(100)  NOT NULL DEFAULT '',
	phone               VARCHAR(50)   NOT NULL DEFAULT '',
	billing_address     TEXT          NOT NULL,
	ip_address          VARCHAR(45)   NOT NULL DEFAULT '',
	reason              TEXT          NOT NULL,
	reported_by_site    VARCHAR(255)  NOT NULL DEFAULT '',
	reported_by_client  INT UNSIGNED  NOT NULL DEFAULT 0,
	date_added          DATETIME      NOT NULL,
	last_updated        DATETIME      NOT NULL,
	status              VARCHAR(20)   NOT NULL DEFAULT 'active',
	admin_notes         TEXT          NOT NULL,
	reports_count       INT UNSIGNED  NOT NULL DEFAULT 1,
	PRIMARY KEY (id),
	UNIQUE KEY email (email),
	KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_ban_reports (
	id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
	customer_id   INT UNSIGNED NOT NULL,
	client_id     INT UNSIGNED NOT NULL DEFAULT 0,
	site_url      VARCHAR(255) NOT NULL DEFAULT '',
	reason        TEXT         NOT NULL,
	order_id      VARCHAR(100) NOT NULL DEFAULT '',
	ip_address    VARCHAR(45)  NOT NULL DEFAULT '',
	date_reported DATETIME     NOT NULL,
	PRIMARY KEY (id),
	KEY customer_id (customer_id),
	KEY client_id   (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_whitelists (
	id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
	client_id   INT UNSIGNED NOT NULL,
	customer_id INT UNSIGNED NOT NULL,
	date_added  DATETIME     NOT NULL,
	notes       TEXT         NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY client_customer (client_id, customer_id),
	KEY client_id   (client_id),
	KEY customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_password_resets (
	id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
	client_id   INT UNSIGNED NOT NULL,
	token_hash  VARCHAR(64)  NOT NULL,
	expires_at  DATETIME     NOT NULL,
	PRIMARY KEY (id),
	KEY client_id (client_id),
	KEY token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_blocked_domains (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
	domain     VARCHAR(255) NOT NULL,
	reason     TEXT         NOT NULL DEFAULT '',
	date_added DATETIME     NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pepban_blocked_ips (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
	ip_address VARCHAR(45)  NOT NULL,
	reason     TEXT         NOT NULL DEFAULT '',
	date_added DATETIME     NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SQL;

	foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
		$db->query($statement);
	}

	echo '<h2 style="font-family:sans-serif;color:green">&#10003; Installation complete!</h2>';
	echo '<p style="font-family:sans-serif"><strong>Delete this file now</strong> (install.php) before going live.</p>';

} catch (Exception $e) {
	echo '<h2 style="font-family:sans-serif;color:red">&#10007; Error</h2>';
	echo '<pre style="font-family:monospace">' . htmlspecialchars($e->getMessage()) . '</pre>';
}
