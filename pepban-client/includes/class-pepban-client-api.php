<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_API {

	private static function base_url(): string {
		return rtrim( PepBan_Client_Settings::get_hub_url(), '/' ) . '/api/v1';
	}

	/**
	 * Build signed request headers.
	 * Signature = HMAC-SHA256(api_key, "timestamp\nMETHOD\n/api/v1{endpoint}\nbody_sha256")
	 * This binds the key, the timestamp, the exact endpoint, and the body together — a stolen
	 * key alone cannot be replayed and cannot be used for any other endpoint or body.
	 */
	private static function signed_headers( string $method, string $endpoint, string $body ): array {
		$api_key   = PepBan_Client_Settings::get_api_key();
		$timestamp = (string) time();
		$path      = '/api/v1' . $endpoint;
		$sig_data  = implode( "\n", [ $timestamp, strtoupper( $method ), $path, hash( 'sha256', $body ) ] );

		return array(
			'X-PepBan-API-Key'   => $api_key,
			'X-PepBan-Timestamp' => $timestamp,
			'X-PepBan-Sig'       => hash_hmac( 'sha256', $sig_data, $api_key ),
			'X-PepBan-Site'      => home_url(),
			'Content-Type'       => 'application/json',
			'Accept'             => 'application/json',
		);
	}

	public static function get( string $endpoint ) {
		if ( ! PepBan_Client_Settings::is_configured() ) {
			return new WP_Error( 'pepban_not_configured', 'PepBan is not configured.' );
		}
		$response = wp_remote_get(
			self::base_url() . $endpoint,
			array(
				'headers' => self::signed_headers( 'GET', $endpoint, '' ),
				'timeout' => 10,
			)
		);
		return self::parse_response( $response );
	}

	public static function post( string $endpoint, array $body = array() ) {
		if ( ! PepBan_Client_Settings::is_configured() ) {
			return new WP_Error( 'pepban_not_configured', 'PepBan is not configured.' );
		}
		$json     = wp_json_encode( $body ) ?: '';
		$response = wp_remote_post(
			self::base_url() . $endpoint,
			array(
				'headers' => self::signed_headers( 'POST', $endpoint, $json ),
				'body'    => $json,
				'timeout' => 10,
			)
		);
		return self::parse_response( $response );
	}

	private static function parse_response( $response ): array|WP_Error {
		if ( is_wp_error( $response ) ) return $response;
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code >= 400 ) {
			return new WP_Error( 'pepban_api_error_' . $code, $body['message'] ?? 'API error ' . $code );
		}
		return is_array( $body ) ? $body : array();
	}

	// ── Convenience wrappers ─────────────────────────────────────────────────────

	public static function check_customer( string $email, string $phone = '', string $first_name = '', string $last_name = '', string $ip = '' ) {
		return self::post( '/check', array_filter( array(
			'email'      => $email,
			'phone'      => $phone,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'ip_address' => $ip,
		) ) );
	}

	public static function report_customer( array $data ) {
		return self::post( '/report', $data );
	}

	public static function whitelist_add( int $customer_id ) {
		return self::post( '/whitelist/add', array( 'customer_id' => $customer_id ) );
	}

	public static function whitelist_remove( int $customer_id ) {
		return self::post( '/whitelist/remove', array( 'customer_id' => $customer_id ) );
	}

	public static function whitelist_list(): array {
		$result = self::get( '/whitelist' );
		if ( is_wp_error( $result ) ) return array();
		return $result['whitelisted'] ?? array();
	}
}
