<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Blacklist {

	const OPTION_KEY          = 'pepban_client_customer_blacklist';
	const WHITELIST_OPTION_KEY = 'pepban_client_local_whitelist';
	const VALID_TYPES         = array( 'email', 'ip', 'address', 'phone' );

	public static function init() {
		add_action( 'wp_ajax_pepban_blacklist_add',        array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pepban_blacklist_remove',     array( __CLASS__, 'ajax_remove' ) );
		add_action( 'wp_ajax_pepban_blacklist_report',     array( __CLASS__, 'ajax_report_to_hub' ) );
		add_action( 'wp_ajax_pepban_blacklist_import_csv', array( __CLASS__, 'ajax_import_csv' ) );
		add_action( 'admin_post_pepban_download_template', array( __CLASS__, 'download_template' ) );
		add_action( 'wp_ajax_pepban_whitelist_local_add',  array( __CLASS__, 'ajax_whitelist_add' ) );
		add_action( 'wp_ajax_pepban_whitelist_local_remove', array( __CLASS__, 'ajax_whitelist_remove' ) );
	}

	public static function get_all(): array {
		return get_option( self::OPTION_KEY, array() );
	}

	// Backwards-compat: old entries used 'email' key instead of type/value
	private static function entry_type( array $e ): string {
		return $e['type'] ?? 'email';
	}
	private static function entry_value( array $e ): string {
		return $e['value'] ?? $e['email'] ?? '';
	}

	public static function is_blocked( string $email ): bool {
		$email = strtolower( trim( $email ) );
		foreach ( self::get_all() as $e ) {
			if ( self::entry_type( $e ) === 'email' && strtolower( self::entry_value( $e ) ) === $email ) return true;
		}
		return false;
	}

	public static function is_blocked_ip( string $ip ): bool {
		$ip = trim( $ip );
		foreach ( self::get_all() as $e ) {
			if ( self::entry_type( $e ) === 'ip' && self::entry_value( $e ) === $ip ) return true;
		}
		return false;
	}

	public static function normalize_phone( string $phone ): string {
		return preg_replace( '/[^0-9]/', '', $phone );
	}

	public static function is_blocked_phone( string $phone ): bool {
		$normalized = self::normalize_phone( $phone );
		if ( strlen( $normalized ) < 7 ) return false;
		foreach ( self::get_all() as $e ) {
			if ( self::entry_type( $e ) === 'phone' ) {
				$stored = self::normalize_phone( self::entry_value( $e ) );
				if ( $stored && $stored === $normalized ) return true;
			}
		}
		return false;
	}

	// $address should be the full billing address concatenated (lowercased by caller).
	// Stored value is matched as a case-insensitive substring so admins can block by zip, city, etc.
	public static function is_blocked_address( string $address ): bool {
		if ( empty( $address ) ) return false;
		$address = strtolower( $address );
		foreach ( self::get_all() as $e ) {
			if ( self::entry_type( $e ) !== 'address' ) continue;
			$val = strtolower( trim( self::entry_value( $e ) ) );
			if ( $val && strpos( $address, $val ) !== false ) return true;
		}
		return false;
	}

	public static function render_page() {
		$customers = self::get_all();
		include PEPBAN_CLIENT_DIR . 'admin/views/blacklist.php';
	}

	// ── Local whitelist ──────────────────────────────────────────────────────────

	public static function whitelist_get_all(): array {
		return get_option( self::WHITELIST_OPTION_KEY, array() );
	}

	public static function is_whitelisted_locally( string $email, string $ip = '', string $address = '' ): bool {
		foreach ( self::whitelist_get_all() as $e ) {
			$type  = $e['type']  ?? 'email';
			$value = $e['value'] ?? $e['email'] ?? '';
			if ( $type === 'email'   && $email   && strtolower( $value ) === strtolower( $email ) ) return true;
			if ( $type === 'ip'      && $ip      && $value === $ip ) return true;
			if ( $type === 'address' && $address ) {
				$val = strtolower( trim( $value ) );
				if ( $val && strpos( strtolower( $address ), $val ) !== false ) return true;
			}
		}
		return false;
	}

	public static function ajax_whitelist_add() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) wp_send_json_error( 'Invalid request.' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized.' );

		$type   = sanitize_text_field( wp_unslash( $_POST['type']   ?? 'email' ) );
		$value  = trim( sanitize_text_field( wp_unslash( $_POST['value']  ?? '' ) ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! in_array( $type, self::VALID_TYPES, true ) ) wp_send_json_error( 'Invalid type.' );
		if ( empty( $value ) ) wp_send_json_error( 'Value is required.' );

		if ( $type === 'email' ) {
			$value = strtolower( $value );
			if ( ! is_email( $value ) ) wp_send_json_error( 'Invalid email address.' );
		} elseif ( $type === 'ip' ) {
			if ( ! filter_var( $value, FILTER_VALIDATE_IP ) ) wp_send_json_error( 'Invalid IP address.' );
		}

		$entries = self::whitelist_get_all();
		foreach ( $entries as $e ) {
			if ( ( $e['type'] ?? 'email' ) === $type && strtolower( $e['value'] ?? $e['email'] ?? '' ) === strtolower( $value ) ) {
				wp_send_json_error( 'Already in whitelist.' );
			}
		}

		$entries[] = array(
			'type'       => $type,
			'value'      => $value,
			'reason'     => $reason,
			'date_added' => current_time( 'mysql' ),
		);
		update_option( self::WHITELIST_OPTION_KEY, $entries );
		wp_send_json_success( array( 'message' => 'Added to whitelist.', 'type' => $type, 'value' => $value ) );
	}

	public static function ajax_whitelist_remove() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) wp_send_json_error( 'Invalid request.' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized.' );

		$type  = sanitize_text_field( wp_unslash( $_POST['type']  ?? 'email' ) );
		$value = strtolower( trim( sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) ) ) );
		if ( empty( $value ) ) wp_send_json_error( 'Value is required.' );

		$entries = array_values( array_filter( self::whitelist_get_all(), function( $e ) use ( $type, $value ) {
			return ! ( ( $e['type'] ?? 'email' ) === $type && strtolower( $e['value'] ?? $e['email'] ?? '' ) === $value );
		} ) );
		update_option( self::WHITELIST_OPTION_KEY, $entries );
		wp_send_json_success( 'Removed from whitelist.' );
	}

	public static function ajax_add() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$type   = sanitize_text_field( wp_unslash( $_POST['type']   ?? 'email' ) );
		$value  = trim( sanitize_text_field( wp_unslash( $_POST['value']  ?? '' ) ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! in_array( $type, self::VALID_TYPES, true ) ) {
			wp_send_json_error( 'Invalid type.' );
		}
		if ( empty( $value ) ) {
			wp_send_json_error( 'Value is required.' );
		}

		if ( $type === 'email' ) {
			$value = strtolower( $value );
			if ( ! is_email( $value ) ) wp_send_json_error( 'Invalid email address.' );
		} elseif ( $type === 'ip' ) {
			if ( ! filter_var( $value, FILTER_VALIDATE_IP ) ) wp_send_json_error( 'Invalid IP address.' );
		} elseif ( $type === 'phone' ) {
			$value = self::normalize_phone( $value );
			if ( strlen( $value ) < 7 ) wp_send_json_error( 'Invalid phone number — must contain at least 7 digits.' );
		}

		$customers = self::get_all();
		foreach ( $customers as $e ) {
			if ( self::entry_type( $e ) === $type && strtolower( self::entry_value( $e ) ) === strtolower( $value ) ) {
				wp_send_json_error( 'This entry is already blacklisted.' );
			}
		}

		$customers[] = array(
			'type'             => $type,
			'value'            => $value,
			'reason'           => $reason,
			'date_added'       => current_time( 'mysql' ),
			'reported_to_hub'  => false,
		);
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( array( 'message' => 'Added to blacklist.', 'value' => $value, 'type' => $type ) );
	}

	public static function ajax_remove() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$type  = sanitize_text_field( wp_unslash( $_POST['type']  ?? 'email' ) );
		$value = strtolower( trim( sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) ) ) );

		if ( empty( $value ) ) wp_send_json_error( 'Value is required.' );

		$customers = array_values( array_filter( self::get_all(), function( $e ) use ( $type, $value ) {
			return ! ( self::entry_type( $e ) === $type && strtolower( self::entry_value( $e ) ) === $value );
		} ) );
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( 'Removed from blacklist.' );
	}

	public static function ajax_import_csv() {
		if ( ! check_ajax_referer( 'pepban_client_nonce', 'nonce', false ) ) wp_send_json_error( 'Invalid request.' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized.' );

		if ( empty( $_FILES['csv_file'] ) || (int) $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( 'File upload failed (error ' . ( $_FILES['csv_file']['error'] ?? 'none' ) . ').' );
		}

		if ( $_FILES['csv_file']['size'] > 1048576 ) {
			wp_send_json_error( 'File too large. Maximum upload size is 1 MB.' );
		}

		$finfo    = finfo_open( FILEINFO_MIME_TYPE );
		$mime     = $finfo ? finfo_file( $finfo, $_FILES['csv_file']['tmp_name'] ) : '';
		if ( $finfo ) finfo_close( $finfo );
		if ( $mime && ! in_array( $mime, array( 'text/plain', 'text/csv', 'application/csv', 'application/octet-stream' ), true ) ) {
			wp_send_json_error( 'Invalid file type. Please upload a CSV file.' );
		}

		$handle = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
		if ( ! $handle ) wp_send_json_error( 'Could not read file.' );

		$customers = self::get_all();
		$existing  = array();
		foreach ( $customers as $e ) {
			$existing[ self::entry_type( $e ) . ':' . strtolower( self::entry_value( $e ) ) ] = true;
		}

		$added = $skipped = 0;
		$errors = array();
		$row_num = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;
			// Support: single-column (email per line) OR type,value,reason
			if ( count( $row ) === 1 ) {
				$type   = 'email';
				$value  = trim( $row[0] );
				$reason = '';
			} else {
				// Skip header
				if ( $row_num === 1 && strtolower( trim( $row[0] ) ) === 'type' ) continue;
				$type   = strtolower( trim( $row[0] ?? 'email' ) );
				$value  = trim( $row[1] ?? '' );
				$reason = sanitize_text_field( $row[2] ?? '' );
			}

			if ( ! in_array( $type, self::VALID_TYPES, true ) ) { $skipped++; continue; }
			if ( empty( $value ) ) { $skipped++; continue; }

			if ( $type === 'email' ) {
				$value = strtolower( $value );
				if ( ! is_email( $value ) ) {
					$errors[] = "Row {$row_num}: invalid email '" . esc_html( $value ) . "'";
					$skipped++;
					continue;
				}
			} elseif ( $type === 'ip' ) {
				if ( ! filter_var( $value, FILTER_VALIDATE_IP ) ) {
					$errors[] = "Row {$row_num}: invalid IP '" . esc_html( $value ) . "'";
					$skipped++;
					continue;
				}
			} elseif ( $type === 'phone' ) {
				$value = self::normalize_phone( $value );
				if ( strlen( $value ) < 7 ) {
					$errors[] = "Row {$row_num}: invalid phone (need at least 7 digits)";
					$skipped++;
					continue;
				}
			}

			$key = $type . ':' . strtolower( $value );
			if ( isset( $existing[ $key ] ) ) { $skipped++; continue; }

			$existing[ $key ] = true;
			$customers[] = array(
				'type'            => $type,
				'value'           => $value,
				'reason'          => $reason,
				'date_added'      => current_time( 'mysql' ),
				'reported_to_hub' => false,
			);
			$added++;
		}
		fclose( $handle );

		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( array(
			'added'   => $added,
			'skipped' => $skipped,
			'errors'  => array_slice( $errors, 0, 5 ),
			'message' => "Imported {$added} entries ({$skipped} skipped).",
		) );
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

		$customers = self::get_all();
		foreach ( $customers as &$e ) {
			if ( self::entry_type( $e ) === 'email' && strtolower( self::entry_value( $e ) ) === $email ) {
				$e['reported_to_hub'] = true;
			}
		}
		update_option( self::OPTION_KEY, $customers );

		wp_send_json_success( array( 'message' => 'Reported to PepBan network.' ) );
	}

	public static function download_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}
		check_admin_referer( 'pepban_download_template' );

		$csv = "type,value,reason\n"
			. "email,scammer@example.com,Chargeback fraud\n"
			. "email,fraud123@gmail.com,Multiple chargebacks\n"
			. "ip,192.168.1.100,Repeated abuse attempts\n"
			. "ip,10.0.0.55,Fraudulent orders\n"
			. "address,123 Fake Street,Suspicious billing address\n"
			. "address,Springfield IL 62701,Block entire city or zip\n"
			. "phone,5551234567,Chargeback fraud — phone block\n"
			. "phone,15559876543,Repeat offender\n";

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pepban-import-template.csv"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		echo $csv;
		exit;
	}
}
