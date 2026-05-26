<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Settings {

	const OPTION_KEY = 'pepban_client_settings';

	public static function init() {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_pepban_client_save_settings',    array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_pepban_client_check_for_update', array( __CLASS__, 'force_update_check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	public static function force_update_check() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'pepban_force_update_check' );
		delete_transient( 'pepban_plugin_update_info' );
		delete_transient( 'pepban_client_connection_status' );
		delete_site_transient( 'update_plugins' );

		// Fetch fresh plugin info now so the settings page shows the correct status after redirect
		$result = PepBan_Client_API::get( '/plugin/info' );
		if ( ! is_wp_error( $result ) && ! empty( $result['version'] ) ) {
			set_transient( 'pepban_plugin_update_info', $result, 6 * HOUR_IN_SECONDS );
		}

		wp_update_plugins();
		wp_safe_redirect( admin_url( 'admin.php?page=pepban-client&pepban_update_checked=1' ) );
		exit;
	}

	public static function get( $key = null, $default = null ) {
		$settings = get_option( self::OPTION_KEY, array() );
		if ( null === $key ) return $settings;
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function get_api_key() {
		return self::get( 'api_key', '' );
	}

	public static function get_hub_url() {
		return 'https://pepban.com';
	}

	public static function is_configured() {
		return ! empty( self::get_api_key() );
	}

	public static function register_menu() {
		add_menu_page(
			'PepBan',
			'PepBan',
			'manage_woocommerce',
			'pepban-client',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-shield',
			57
		);
		add_submenu_page( 'pepban-client', 'Settings',  'Settings',  'manage_woocommerce', 'pepban-client',          array( __CLASS__, 'render_settings_page' ) );
		add_submenu_page( 'pepban-client', 'Customer Whitelist',  'Customer Whitelist',  'manage_woocommerce', 'pepban-client-whitelist',  array( 'PepBan_Client_Whitelist',  'render_page' ) );
		add_submenu_page( 'pepban-client', 'Customer Blacklist', 'Customer Blacklist', 'manage_woocommerce', 'pepban-client-blacklist',  array( 'PepBan_Client_Blacklist',  'render_page' ) );
		add_submenu_page( 'pepban-client', 'Domain Blacklist',   'Domain Blacklist',   'manage_woocommerce', 'pepban-client-domains',    array( 'PepBan_Client_Domains',    'render_page' ) );
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'pepban-client' ) === false ) return;
		wp_enqueue_style(  'pepban-client-admin', PEPBAN_CLIENT_URL . 'assets/css/admin.css', array(), PEPBAN_CLIENT_VERSION );
		wp_enqueue_script( 'pepban-client-admin', PEPBAN_CLIENT_URL . 'assets/js/admin.js',  array( 'jquery' ), PEPBAN_CLIENT_VERSION, true );
		wp_localize_script( 'pepban-client-admin', 'pepbanClient', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pepban_client_nonce' ),
		) );
	}

	public static function render_settings_page() {
		$settings = get_option( self::OPTION_KEY, array() );
		include PEPBAN_CLIENT_DIR . 'admin/views/settings.php';
	}

	public static function save_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'pepban_client_save_settings' );

		$settings = array(
			'api_key'              => sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ),
			'block_on_ban'         => ! empty( $_POST['block_on_ban'] ),
			'block_message'        => sanitize_textarea_field( wp_unslash( $_POST['block_message'] ?? '' ) ),
			'check_email'          => ! empty( $_POST['check_email'] ),
			'check_phone'          => ! empty( $_POST['check_phone'] ),
			'check_ip'             => ! empty( $_POST['check_ip'] ),
			'check_billing_address'=> ! empty( $_POST['check_billing_address'] ),
			'auto_report_on_flag'  => ! empty( $_POST['auto_report_on_flag'] ),
			'show_ban_notice_admin'=> ! empty( $_POST['show_ban_notice_admin'] ),
			'risk_score_threshold' => max( 0, min( 100, (int) ( $_POST['risk_score_threshold'] ?? 40 ) ) ),
		);

		update_option( self::OPTION_KEY, $settings );

		// Clear cached connection and update status
		delete_transient( 'pepban_client_connection_status' );
		delete_transient( 'pepban_plugin_update_info' );
		delete_site_transient( 'update_plugins' );

		set_transient( 'pepban_client_notice', 'Settings saved.', 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=pepban-client' ) );
		exit;
	}

	public static function admin_notices() {
		$msg = get_transient( 'pepban_client_notice' );
		if ( $msg ) {
			delete_transient( 'pepban_client_notice' );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	public static function test_connection() {
		$cached = get_transient( 'pepban_client_connection_status' );
		if ( $cached ) return $cached;

		$result = PepBan_Client_API::get( '/status' );

		if ( is_wp_error( $result ) ) {
			update_option( 'pepban_last_connection_error', $result->get_error_message() );
			set_transient( 'pepban_client_connection_status', 'error', 60 );
			return 'error';
		}

		$status = ( ( $result['status'] ?? '' ) === 'ok' || true === ( $result['success'] ?? false ) ) ? 'connected' : 'error';
		if ( $status === 'error' ) {
			update_option( 'pepban_last_connection_error', wp_json_encode( $result ) );
		} else {
			delete_option( 'pepban_last_connection_error' );
		}

		set_transient( 'pepban_client_connection_status', $status, 300 );
		return $status;
	}
}
