<?php
/**
 * PepBan Hub — Configuration
 * Copy this file to config.local.php and set your values there.
 * config.local.php is gitignored and will override these defaults.
 */

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'pepban');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── Site ──────────────────────────────────────────────────────────────────────
// No trailing slash. If in a subdirectory, include it: 'https://example.com/pepban'
define('SITE_URL', 'https://your-domain.com');
define('SITE_NAME', 'PepBan');
define('PEPBAN_VERSION', '1.0.0');

// ── Admin login ───────────────────────────────────────────────────────────────
// Run: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
// Paste the output as ADMIN_PASSWORD_HASH.
define('ADMIN_EMAIL',         'admin@example.com');
define('ADMIN_PASSWORD_HASH', '$2y$10$changethishashbygeneratingyourown.........');

// ── Email ─────────────────────────────────────────────────────────────────────
define('MAIL_FROM',      'noreply@your-domain.com');
define('MAIL_FROM_NAME', 'PepBan');

// ── Security ──────────────────────────────────────────────────────────────────
// Generate with: php -r "echo bin2hex(random_bytes(32));"
define('SECRET_KEY', 'change-this-to-64-hex-chars');

// ── API rate limit ────────────────────────────────────────────────────────────
define('RATE_LIMIT_PER_MINUTE', 60);

// ── Auto-approve new sign-ups ─────────────────────────────────────────────────
define('AUTO_APPROVE_CLIENTS', false);

// ── Load local overrides (not committed to git) ───────────────────────────────
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
