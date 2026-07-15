<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Lookup {

	public static function init() {
		add_action( 'wp_ajax_pepban_customer_lookup', array( __CLASS__, 'ajax_lookup' ) );
	}

	public static function render_page() {
		include PEPBAN_CLIENT_DIR . 'admin/views/lookup.php';
	}

	public static function ajax_lookup() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Security check failed.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
		if ( ! $email || ! is_email( $email ) ) {
			wp_send_json_error( 'Please enter a valid email address.' );
		}

		$result = array(
			'email'       => $email,
			'network'     => null,
			'watch_notes' => null,
			'blacklisted' => false,
			'blacklist'   => null,
			'whitelisted' => false,
		);

		// ── PepBan network check ───────────────────────────────────────────────
		$api = PepBan_Client_API::check_customer( $email );
		if ( ! is_wp_error( $api ) ) {
			$result['network'] = array(
				'banned'       => ! empty( $api['banned'] ),
				'whitelisted'  => ! empty( $api['whitelisted'] ),
				'report_count' => $api['report_count'] ?? 0,
				'store_count'  => $api['store_count']  ?? 0,
				'reason'       => $api['customer']['reason'] ?? '',
				'first_name'   => $api['customer']['first_name'] ?? '',
				'last_name'    => $api['customer']['last_name']  ?? '',
			);
			$result['whitelisted'] = ! empty( $api['whitelisted'] );
		} else {
			$result['network_error'] = $api->get_error_message();
		}

		// ── Local watch notes ──────────────────────────────────────────────────
		$notes = PepBan_Client_WatchNotes::get( $email );
		if ( ! empty( $notes['notes'] ) ) {
			$result['watch_notes'] = array(
				'notes'    => $notes['notes'],
				'saved_by' => $notes['saved_by'] ?? '',
				'saved_at' => $notes['saved_at'] ?? '',
			);
		}

		// ── Local blacklist ────────────────────────────────────────────────────
		foreach ( PepBan_Client_Blacklist::get_all() as $entry ) {
			$type  = $entry['type']  ?? 'email';
			$value = $entry['value'] ?? ( $entry['email'] ?? '' );
			if ( $type === 'email' && strtolower( $value ) === $email ) {
				$result['blacklisted'] = true;
				$result['blacklist']   = array(
					'reason'     => $entry['reason']     ?? '',
					'date_added' => $entry['date_added'] ?? '',
					'reported'   => ! empty( $entry['reported_to_hub'] ),
				);
				break;
			}
		}

		wp_send_json_success( $result );
	}
}
