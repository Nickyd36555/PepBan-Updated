<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Hub_Admin {

	public static function init() {
		add_action( 'admin_menu',   array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_pepban_hub_action', array( __CLASS__, 'handle_post_action' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	public static function register_menus() {
		add_menu_page(
			'PepBan Hub',
			'PepBan Hub',
			'manage_options',
			'pepban-hub',
			array( __CLASS__, 'page_dashboard' ),
			'dashicons-shield',
			56
		);
		add_submenu_page( 'pepban-hub', 'Banned Customers', 'Banned Customers', 'manage_options', 'pepban-hub-banned',   array( __CLASS__, 'page_banned' ) );
		add_submenu_page( 'pepban-hub', 'Clients',          'Clients',          'manage_options', 'pepban-hub-clients',  array( __CLASS__, 'page_clients' ) );
		add_submenu_page( 'pepban-hub', 'Settings',         'Settings',         'manage_options', 'pepban-hub-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'pepban-hub' ) === false ) return;
		wp_enqueue_style(  'pepban-hub-admin', PEPBAN_HUB_URL . 'assets/css/admin.css', array(), PEPBAN_HUB_VERSION );
		wp_enqueue_script( 'pepban-hub-admin', PEPBAN_HUB_URL . 'assets/js/admin.js',  array( 'jquery' ), PEPBAN_HUB_VERSION, true );
		wp_localize_script( 'pepban-hub-admin', 'pepbanHub', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pepban_hub_nonce' ),
		) );
	}

	// ── Page renderers ────────────────────────────────────────────────────────

	public static function page_dashboard() {
		$stats = PepBan_Hub_Database::get_stats();
		include PEPBAN_HUB_DIR . 'admin/views/dashboard.php';
	}

	public static function page_banned() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( 'view' === $action && $id ) {
			$customer = PepBan_Hub_Database::get_banned_customer( $id );
			$reports  = PepBan_Hub_Database::get_ban_reports_for_customer( $id );
			include PEPBAN_HUB_DIR . 'admin/views/banned-customer-detail.php';
			return;
		}

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page_num = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$status   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'active';

		$customers = PepBan_Hub_Database::get_banned_customers( array(
			'search'   => $search,
			'page'     => $page_num,
			'status'   => $status,
			'per_page' => 25,
		) );
		$total     = PepBan_Hub_Database::count_banned_customers( array( 'search' => $search, 'status' => $status ) );
		include PEPBAN_HUB_DIR . 'admin/views/banned-customers.php';
	}

	public static function page_clients() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( 'view' === $action && $id ) {
			$client    = PepBan_Hub_Database::get_client( $id );
			$whitelist = PepBan_Hub_Database::get_whitelist_for_client( $id );
			include PEPBAN_HUB_DIR . 'admin/views/client-detail.php';
			return;
		}

		$clients = PepBan_Hub_Database::get_clients();
		include PEPBAN_HUB_DIR . 'admin/views/clients.php';
	}

	public static function page_settings() {
		$settings = get_option( 'pepban_hub_settings', array() );
		include PEPBAN_HUB_DIR . 'admin/views/settings.php';
	}

	// ── POST action handler ───────────────────────────────────────────────────

	public static function handle_post_action() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'pepban_hub_action' );

		$action = sanitize_key( $_POST['pepban_action'] ?? '' );

		switch ( $action ) {

			case 'delete_customer':
				$id = absint( $_POST['customer_id'] ?? 0 );
				if ( $id ) PepBan_Hub_Database::delete_banned_customer( $id );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-banned' ), 'Customer permanently removed.' );
				break;

			case 'update_customer_status':
				$id     = absint( $_POST['customer_id'] ?? 0 );
				$status = sanitize_key( $_POST['status'] ?? '' );
				if ( $id && $status ) PepBan_Hub_Database::update_banned_customer_status( $id, $status );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-banned&action=view&id=' . $id ), 'Status updated.' );
				break;

			case 'add_customer':
				$data = array(
					'email'           => sanitize_email( $_POST['email'] ?? '' ),
					'first_name'      => sanitize_text_field( $_POST['first_name'] ?? '' ),
					'last_name'       => sanitize_text_field( $_POST['last_name'] ?? '' ),
					'phone'           => sanitize_text_field( $_POST['phone'] ?? '' ),
					'billing_address' => sanitize_textarea_field( $_POST['billing_address'] ?? '' ),
					'ip_address'      => sanitize_text_field( $_POST['ip_address'] ?? '' ),
					'reason'          => sanitize_textarea_field( $_POST['reason'] ?? '' ),
				);
				// Admin-initiated bans are credited to client 0 (the hub itself)
				PepBan_Hub_Database::upsert_banned_customer( $data, 0 );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-banned' ), 'Customer added to ban list.' );
				break;

			case 'create_client':
				$result = PepBan_Hub_Clients::create(
					sanitize_text_field( $_POST['site_url'] ?? '' ),
					sanitize_email( $_POST['owner_email'] ?? '' ),
					sanitize_text_field( $_POST['owner_name'] ?? '' )
				);
				// Store raw key in transient so it can be shown once
				set_transient( 'pepban_new_api_key_' . $result['id'], $result['api_key'], 300 );
				self::redirect_with_notice(
					admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $result['id'] . '&new_client=1' ),
					'Client created. Copy the API key below — it will not be shown again.'
				);
				break;

			case 'activate_client':
				$id = absint( $_POST['client_id'] ?? 0 );
				PepBan_Hub_Clients::activate( $id );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $id ), 'Client activated.' );
				break;

			case 'deactivate_client':
				$id = absint( $_POST['client_id'] ?? 0 );
				PepBan_Hub_Clients::deactivate( $id );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $id ), 'Client deactivated.' );
				break;

			case 'regenerate_key':
				$id      = absint( $_POST['client_id'] ?? 0 );
				$raw_key = PepBan_Hub_Database::regenerate_api_key( $id );
				set_transient( 'pepban_new_api_key_' . $id, $raw_key, 300 );
				self::redirect_with_notice(
					admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $id . '&new_client=1' ),
					'API key regenerated. Copy it below — it will not be shown again.'
				);
				break;

			case 'delete_client':
				$id = absint( $_POST['client_id'] ?? 0 );
				PepBan_Hub_Database::delete_client( $id );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-clients' ), 'Client deleted.' );
				break;

			case 'save_settings':
				update_option( 'pepban_hub_settings', array(
					'rate_limit_per_min'   => absint( $_POST['rate_limit_per_min'] ?? 60 ),
					'auto_approve_clients' => ! empty( $_POST['auto_approve_clients'] ),
					'client_plugin_path'   => sanitize_text_field( wp_unslash( $_POST['client_plugin_path'] ?? '' ) ),
				) );
				// Page ID options stored separately so get_option('pepban_portal_page_id') works directly
				update_option( 'pepban_signup_page_id', absint( $_POST['signup_page_id'] ?? 0 ) );
				update_option( 'pepban_portal_page_id', absint( $_POST['portal_page_id'] ?? 0 ) );
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-settings' ), 'Settings saved.' );
				break;

			case 'create_pages':
				self::create_shortcode_pages();
				self::redirect_with_notice( admin_url( 'admin.php?page=pepban-hub-settings' ), 'Pages created and selected.' );
				break;
		}

		wp_die( 'Unknown action.' );
	}

	private static function redirect_with_notice( $url, $message ) {
		set_transient( 'pepban_admin_notice', $message, 60 );
		wp_safe_redirect( $url );
		exit;
	}

	public static function admin_notices() {
		$msg = get_transient( 'pepban_admin_notice' );
		if ( $msg ) {
			delete_transient( 'pepban_admin_notice' );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	private static function create_shortcode_pages() {
		$pages = array(
			array(
				'title'   => 'PepBan — Sign Up',
				'content' => '<!-- wp:shortcode -->[pepban_signup]<!-- /wp:shortcode -->',
				'option'  => 'pepban_signup_page_id',
			),
			array(
				'title'   => 'PepBan — My Portal',
				'content' => '<!-- wp:shortcode -->[pepban_portal]<!-- /wp:shortcode -->',
				'option'  => 'pepban_portal_page_id',
			),
		);

		foreach ( $pages as $def ) {
			$existing = get_option( $def['option'], 0 );
			if ( $existing && get_post( $existing ) ) continue;

			$page_id = wp_insert_post( array(
				'post_title'   => $def['title'],
				'post_content' => $def['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( $def['option'], $page_id );
			}
		}
	}
}
