<?php
/**
 * Plugin Name: PepBan Client
 * Plugin URI:  https://pepban.com
 * Description: Connects your WooCommerce store to the PepBan central ban database. Blocks banned customers at checkout and lets you report bad actors directly from orders.
 * Version:     1.0.6
 * Author:      PepBan
 * License:     GPL-2.0+
 * Text Domain: pepban-client
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PEPBAN_CLIENT_VERSION', '1.0.6' );
define( 'PEPBAN_CLIENT_FILE',    __FILE__ );
define( 'PEPBAN_CLIENT_DIR',     plugin_dir_path( __FILE__ ) );
define( 'PEPBAN_CLIENT_URL',     plugin_dir_url( __FILE__ ) );

require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-settings.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-api.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-checker.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-reporter.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-whitelist.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-ip-blocker.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-domains.php';
require_once PEPBAN_CLIENT_DIR . 'includes/class-pepban-client-updater.php';

// Updater runs outside plugins_loaded so it catches WordPress's early update checks
PepBan_Client_Updater::init();

add_action( 'plugins_loaded', 'pepban_client_init' );
function pepban_client_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>PepBan Client</strong> requires WooCommerce to be active.</p></div>';
		} );
		return;
	}

	PepBan_Client_Settings::init();
	PepBan_Client_Checker::init();
	PepBan_Client_Reporter::init();
	PepBan_Client_Whitelist::init();
	PepBan_Client_IP_Blocker::init();
	PepBan_Client_Domains::init();
}
