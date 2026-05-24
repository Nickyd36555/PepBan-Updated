<?php
/**
 * Plugin Name: PepBan Hub
 * Plugin URI:  https://pepban.com
 * Description: Central hub for the PepBan banned-customer database network. Exposes a REST API for client sites, manages subscriptions via WooCommerce Subscriptions, and provides an admin dashboard.
 * Version:     1.0.0
 * Author:      PepBan
 * License:     GPL-2.0+
 * Text Domain: pepban-hub
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PEPBAN_HUB_VERSION', '1.0.0' );
define( 'PEPBAN_HUB_FILE',    __FILE__ );
define( 'PEPBAN_HUB_DIR',     plugin_dir_path( __FILE__ ) );
define( 'PEPBAN_HUB_URL',     plugin_dir_url( __FILE__ ) );

require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-activator.php';
require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-database.php';
require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-clients.php';
require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-api.php';
require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-admin.php';
require_once PEPBAN_HUB_DIR . 'includes/class-pepban-hub-subscriptions.php';

register_activation_hook( __FILE__, array( 'PepBan_Hub_Activator', 'activate' ) );

add_action( 'plugins_loaded', 'pepban_hub_init' );
function pepban_hub_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>PepBan Hub</strong> requires WooCommerce to be active.</p></div>';
		} );
		return;
	}

	PepBan_Hub_API::init();
	PepBan_Hub_Admin::init();
	PepBan_Hub_Subscriptions::init();
}
