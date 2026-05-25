<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Blacklist {

	const OPTION_KEY = 'pepban_client_customer_blacklist';

	public static function init() {
		add_action( 'wp_ajax_pepban_blacklist_add',         array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pepban_blacklist_remove',      array( __CLASS__, 'ajax_remove' ) );
		add_action( 'wp_ajax_pepban_blacklist_report',      array( __CLASS__, 'ajax_report_to_hub' ) );
	}

	public static function get_all(): array {
		return get_option( self::OPTION_KEY, array() );
	}

	public static function is_blocked( string $email ): bool {
		$email = strtolower( trim( $email ) );
		foreach ( self::get_all() as $entry ) {
			if ( strtolower( $entry['email'] ) === $email ) return true;
		}
		return false;
	}

	public static function render_page() {
		$customers = self::get_all();
		include PEPBAN_CLIENT_DIR . 'admin/views/blacklist.php';
	}

	public static function ajax_add() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$email  = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $email ) wp_send_json_error( 'Email is required.' );
		if ( ! is_email( $email ) ) wp_send_json_error( 'Invalid email address.' );

		$customers = self::get_all();
		foreach ( $customers as $entry ) {
			if ( strtolower( $entry['email'] ) === $email ) {
				wp_send_json_error( 'This customer is already blacklisted.' );
			}
		}

		$customers[] = array(
			'email'          => $email,
			'reason'         => $reason,
			'date_added'     => current_time( 'mysql' ),
			'reported_to_hub'=> false,
		);
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( array( 'message' => 'Customer blacklisted on this site.', 'email' => $email ) );
	}

	public static function ajax_remove() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
		if ( ! $email ) wp_send_json_error( 'Email is required.' );

		$customers = array_values( array_filter( self::get_all(), fn( $e ) => strtolower( $e['email'] ) !== $email ) );
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( 'Customer removed from blacklist.' );
	}

	public static function ajax_report_to_hub() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$email  = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $email ) wp_send_json_error( 'Email is required.' );

		$result = PepBan_Client_API::report_customer( array(
			'email'  => $email,
			'reason' => $reason ?: 'Reported from site blacklist.',
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Mark as reported in local list
		$customers = self::get_all();
		foreach ( $customers as &$entry ) {
			if ( strtolower( $entry['email'] ) === $email ) {
				$entry['reported_to_hub'] = true;
			}
		}
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( array( 'message' => 'Reported to PepBan network.' ) );
	}
}
