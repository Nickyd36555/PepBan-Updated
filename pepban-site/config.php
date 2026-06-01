<?php
/**
 * PepBan Hub — Configuration
 * To override any value, create config.local.php (gitignored).
 * config.local.php is loaded FIRST so its defines take precedence.
 */

// Load local overrides first so they win over the defaults below
if (file_exists(__DIR__ . '/config.local.php')) {
	require_once __DIR__ . '/config.local.php';
}

// ── Defaults (only set if not already defined by config.local.php) ────────────
if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_NAME'))    define('DB_NAME',    'pepban');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_PASS'))    define('DB_PASS',    '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

if (!defined('SITE_URL'))   define('SITE_URL',  'https://your-domain.com');
if (!defined('SITE_NAME'))  define('SITE_NAME', 'PepBan');
if (!defined('PEPBAN_VERSION'))        define('PEPBAN_VERSION',        '1.0.0');
if (!defined('PEPBAN_PLUGIN_VERSION')) define('PEPBAN_PLUGIN_VERSION', '1.5.0');

if (!defined('ADMIN_EMAIL'))         define('ADMIN_EMAIL',         'admin@pepban.com');
if (!defined('SUPPORT_EMAIL'))       define('SUPPORT_EMAIL',       'admin@pepban.com');
if (!defined('ADMIN_PASSWORD_HASH')) define('ADMIN_PASSWORD_HASH', '$2y$10$changethishashbygeneratingyourown.........');

if (!defined('MAIL_FROM'))      define('MAIL_FROM',      'noreply@your-domain.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'PepBan');

// SMTP — set these in config.local.php to enable SMTP sending (required on most hosts)
if (!defined('SMTP_HOST'))   define('SMTP_HOST',   '');
if (!defined('SMTP_PORT'))   define('SMTP_PORT',   587);
if (!defined('SMTP_USER'))   define('SMTP_USER',   '');
if (!defined('SMTP_PASS'))   define('SMTP_PASS',   '');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // 'tls' (STARTTLS) or 'ssl'

if (!defined('COOKIE_SECURE'))         define('COOKIE_SECURE',         true);
if (!defined('SECRET_KEY'))            define('SECRET_KEY',            'change-this-to-64-hex-chars');
if (!defined('RATE_LIMIT_PER_MINUTE')) define('RATE_LIMIT_PER_MINUTE', 60);
if (!defined('AUTO_APPROVE_CLIENTS'))  define('AUTO_APPROVE_CLIENTS',  false);
