<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manages per-site whitelisting via the hub API.
 * Whitelisted customers remain in the global ban database —
 * they are just allowed to order on THIS site specifically.
 */
class PepBan_Client_Whitelist {

	public static function init() {
		add_action( 'wp_ajax_pepban_whitelist_add',    array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pepban_whitelist_remove', array( __CLASS__, 'ajax_remove' ) );
	}

	public static function render_page() {
		$whitelist = PepBan_Client_API::whitelist_list();
		include PEPBAN_CLIENT_DIR . 'admin/views/whitelist.php';
	}

	public static function ajax_add() {
		$order_id    = absint( $_POST['order_id'] ?? 0 );
		$customer_id = absint( $_POST['customer_id'] ?? 0 );

		if ( ! $customer_id || ! check_ajax_referer( 'pepban_whitelist_' . $order_id, 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$notes  = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$result = PepBan_Client_API::whitelist_add( $customer_id, $notes );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Record locally on the order for quick reference
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->add_order_note( 'Customer (PepBan ID: ' . $customer_id . ') whitelisted on this site.' );
				$order->save();
			}
		}

		wp_send_json_success( $result['message'] ?? 'Customer whitelisted.' );
	}

	public static function ajax_remove() {
		$customer_id = absint( $_POST['customer_id'] ?? 0 );

		if ( ! $customer_id || ! check_ajax_referer( 'pepban_whitelist_remove_' . $customer_id, 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$result = PepBan_Client_API::whitelist_remove( $customer_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( 'Removed from whitelist.' );
	}
}
