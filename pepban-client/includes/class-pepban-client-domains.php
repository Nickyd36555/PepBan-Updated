<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Domains {

	public static function init() {
		add_action( 'wp_ajax_pepban_domain_add',    array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pepban_domain_remove', array( __CLASS__, 'ajax_remove' ) );
	}

	public static function render_page() {
		$domains = PepBan_Client_API::blocked_domains_list();
		include PEPBAN_CLIENT_DIR . 'admin/views/domains.php';
	}

	public static function ajax_add() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$domain = sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $domain ) wp_send_json_error( 'Domain is required.' );

		$result = PepBan_Client_API::blocked_domain_add( $domain, $reason );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'message' => 'Domain blocked successfully.', 'domain' => $domain ) );
	}

	public static function ajax_remove() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$domain = sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) );
		if ( ! $domain ) wp_send_json_error( 'Domain is required.' );

		$result = PepBan_Client_API::blocked_domain_remove( $domain );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( 'Domain removed.' );
	}
}
