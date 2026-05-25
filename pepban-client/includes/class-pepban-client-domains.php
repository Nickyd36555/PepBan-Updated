<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Domains {

	const OPTION_KEY = 'pepban_client_blocked_domains';

	public static function init() {
		add_action( 'wp_ajax_pepban_domain_add',    array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pepban_domain_remove', array( __CLASS__, 'ajax_remove' ) );
	}

	public static function get_all(): array {
		return get_option( self::OPTION_KEY, array() );
	}

	public static function is_blocked( string $email ): bool {
		$domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );
		if ( ! $domain ) return false;
		foreach ( self::get_all() as $entry ) {
			if ( strtolower( $entry['domain'] ) === $domain ) return true;
		}
		return false;
	}

	public static function render_page() {
		$domains = self::get_all();
		include PEPBAN_CLIENT_DIR . 'admin/views/domains.php';
	}

	public static function ajax_add() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$domain = strtolower( trim( ltrim( sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) ), '@' ) ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $domain ) wp_send_json_error( 'Domain is required.' );

		$domains = self::get_all();
		foreach ( $domains as $entry ) {
			if ( $entry['domain'] === $domain ) {
				wp_send_json_error( 'Domain is already blocked.' );
			}
		}

		$domains[] = array(
			'domain'     => $domain,
			'reason'     => $reason,
			'date_added' => current_time( 'mysql' ),
		);
		update_option( self::OPTION_KEY, $domains );

		wp_send_json_success( array( 'message' => 'Domain blocked.', 'domain' => $domain ) );
	}

	public static function ajax_remove() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$domain  = strtolower( trim( sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) ) ) );
		if ( ! $domain ) wp_send_json_error( 'Domain is required.' );

		$domains = array_values( array_filter( self::get_all(), fn( $e ) => $e['domain'] !== $domain ) );
		update_option( self::OPTION_KEY, $domains );

		wp_send_json_success( 'Domain removed.' );
	}
}
