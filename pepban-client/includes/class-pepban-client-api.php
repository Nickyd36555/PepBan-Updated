<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_API {

	private static function base_url() {
		$hub = PepBan_Client_Settings::get_hub_url();
		return trailingslashit( $hub ) . 'api/v1';
	}

	private static function headers() {
		return array(
			'X-PepBan-API-Key' => PepBan_Client_Settings::get_api_key(),
			'Content-Type'     => 'application/json',
			'Accept'           => 'application/json',
		);
	}

	public static function get( $endpoint ) {
		if ( ! PepBan_Client_Settings::is_configured() ) {
			return new WP_Error( 'pepban_not_configured', 'PepBan is not configured.' );
		}

		$response = wp_remote_get(
			self::base_url() . $endpoint,
			array(
				'headers' => self::headers(),
				'timeout' => 10,
			)
		);

		return self::parse_response( $response );
	}

	public static function post( $endpoint, $body = array() ) {
		if ( ! PepBan_Client_Settings::is_configured() ) {
			return new WP_Error( 'pepban_not_configured', 'PepBan is not configured.' );
		}

		$response = wp_remote_post(
			self::base_url() . $endpoint,
			array(
				'headers' => self::headers(),
				'body'    => wp_json_encode( $body ),
				'timeout' => 10,
			)
		);

		return self::parse_response( $response );
	}

	private static function parse_response( $response ) {
		if ( is_wp_error( $response ) ) return $response;

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = $body['message'] ?? 'API error ' . $code;
			return new WP_Error( 'pepban_api_error_' . $code, $message );
		}

		return is_array( $body ) ? $body : array();
	}

	// ── Convenience wrappers ─────────────────────────────────────────────────

	/**
	 * Check a customer against the hub. Returns array with 'banned', 'whitelisted', 'customer' keys.
	 */
	public static function check_customer( $email, $phone = '', $first_name = '', $last_name = '', $ip = '' ) {
		$result = self::post( '/check', array_filter( array(
			'email'      => $email,
			'phone'      => $phone,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'ip_address' => $ip,
		) ) );

		if ( is_wp_error( $result ) ) return $result;
		return $result;
	}

	/**
	 * Report a banned customer to the hub. Returns the customer_id on success.
	 */
	public static function report_customer( $data ) {
		$result = self::post( '/report', $data );
		if ( is_wp_error( $result ) ) return $result;
		return $result;
	}

	/**
	 * Add a customer to this site's whitelist. customer_id is the hub's ID.
	 */
	public static function whitelist_add( $customer_id, $notes = '' ) {
		return self::post( '/whitelist/add', array(
			'customer_id' => absint( $customer_id ),
			'notes'       => $notes,
		) );
	}

	public static function whitelist_remove( $customer_id ) {
		return self::post( '/whitelist/remove', array(
			'customer_id' => absint( $customer_id ),
		) );
	}

	public static function whitelist_list() {
		$result = self::get( '/whitelist' );
		if ( is_wp_error( $result ) ) return array();
		return $result['whitelisted'] ?? array();
	}

	public static function blocked_domains_list() {
		$result = self::get( '/blocked-domains' );
		if ( is_wp_error( $result ) ) return array();
		return $result['blocked_domains'] ?? array();
	}

	public static function blocked_domain_add( $domain, $reason = '' ) {
		return self::post( '/blocked-domains/add', array( 'domain' => $domain, 'reason' => $reason ) );
	}

	public static function blocked_domain_remove( $domain ) {
		return self::post( '/blocked-domains/remove', array( 'domain' => $domain ) );
	}
}
