<?php
defined( 'ABSPATH' ) || exit;

/**
 * REST API for client sites.
 *
 * Base URL: /wp-json/pepban/v1/
 * Auth:     X-PepBan-API-Key header (raw key, verified against stored hash)
 *
 * Endpoints:
 *   GET  /status              – Check API key validity & subscription
 *   POST /check               – Check if a customer is banned
 *   POST /report              – Report a banned customer
 *   POST /whitelist/add       – Add customer to site whitelist
 *   POST /whitelist/remove    – Remove customer from site whitelist
 *   GET  /whitelist           – List site's whitelisted customers
 */
class PepBan_Hub_API {

	const NS      = 'pepban/v1';
	const RL_OPT  = 'pepban_rate_limit_';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( self::NS, '/status', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'endpoint_status' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
		) );

		register_rest_route( self::NS, '/check', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'endpoint_check' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
			'args'                => array(
				'email' => array( 'required' => false, 'sanitize_callback' => 'sanitize_email' ),
				'phone' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'first_name' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'last_name'  => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'ip_address' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		register_rest_route( self::NS, '/report', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'endpoint_report' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
			'args'                => array(
				'email'           => array( 'required' => true,  'sanitize_callback' => 'sanitize_email' ),
				'reason'          => array( 'required' => true,  'sanitize_callback' => 'sanitize_textarea_field' ),
				'first_name'      => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'last_name'       => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'phone'           => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'billing_address' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
				'ip_address'      => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'order_id'        => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		register_rest_route( self::NS, '/whitelist/add', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'endpoint_whitelist_add' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
			'args'                => array(
				'customer_id' => array( 'required' => true, 'validate_callback' => 'is_numeric' ),
				'notes'       => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
			),
		) );

		register_rest_route( self::NS, '/whitelist/remove', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'endpoint_whitelist_remove' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
			'args'                => array(
				'customer_id' => array( 'required' => true, 'validate_callback' => 'is_numeric' ),
			),
		) );

		register_rest_route( self::NS, '/whitelist', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'endpoint_whitelist_list' ),
			'permission_callback' => array( __CLASS__, 'authenticate' ),
		) );
	}

	// ── Auth & rate limiting ─────────────────────────────────────────────────

	public static function authenticate( WP_REST_Request $request ) {
		$raw_key = $request->get_header( 'X-PepBan-API-Key' );
		if ( empty( $raw_key ) ) {
			return new WP_Error( 'pepban_no_key', 'API key missing.', array( 'status' => 401 ) );
		}

		$client = PepBan_Hub_Database::get_client_by_api_key( $raw_key );
		if ( ! $client ) {
			return new WP_Error( 'pepban_invalid_key', 'Invalid API key.', array( 'status' => 401 ) );
		}

		if ( 'active' !== $client->subscription_status ) {
			return new WP_Error( 'pepban_inactive', 'Subscription is not active.', array( 'status' => 403 ) );
		}

		if ( ! self::check_rate_limit( $client->id ) ) {
			return new WP_Error( 'pepban_rate_limit', 'Rate limit exceeded. Try again in a minute.', array( 'status' => 429 ) );
		}

		// Attach client to request for use in callbacks
		$request->set_param( '__pepban_client', $client );
		PepBan_Hub_Database::update_client_last_active( $client->id );

		return true;
	}

	private static function check_rate_limit( $client_id ) {
		$settings = get_option( 'pepban_hub_settings', array() );
		$limit    = isset( $settings['rate_limit_per_min'] ) ? (int) $settings['rate_limit_per_min'] : 60;
		$key      = self::RL_OPT . $client_id . '_' . floor( time() / 60 );
		$count    = (int) get_transient( $key );
		if ( $count >= $limit ) return false;
		set_transient( $key, $count + 1, 90 );
		return true;
	}

	// ── Endpoints ────────────────────────────────────────────────────────────

	public static function endpoint_status( WP_REST_Request $request ) {
		$client = $request->get_param( '__pepban_client' );
		return rest_ensure_response( array(
			'success' => true,
			'data'    => array(
				'client_id'           => $client->id,
				'site_url'            => $client->site_url,
				'subscription_status' => $client->subscription_status,
				'last_active'         => $client->last_active,
			),
		) );
	}

	public static function endpoint_check( WP_REST_Request $request ) {
		$client = $request->get_param( '__pepban_client' );
		$email  = $request->get_param( 'email' );
		$phone  = $request->get_param( 'phone' );

		if ( empty( $email ) && empty( $phone ) ) {
			return new WP_Error( 'pepban_missing_param', 'Provide at least email or phone.', array( 'status' => 400 ) );
		}

		$customer = null;
		if ( ! empty( $email ) ) {
			$customer = PepBan_Hub_Database::get_banned_customer_by_email( $email );
		}
		if ( ! $customer && ! empty( $phone ) ) {
			$customer = PepBan_Hub_Database::get_banned_customer_by_phone( $phone );
		}

		if ( ! $customer || 'active' !== $customer->status ) {
			return rest_ensure_response( array(
				'success' => true,
				'data'    => array( 'banned' => false ),
			) );
		}

		$whitelisted = PepBan_Hub_Database::is_whitelisted( $client->id, $customer->id );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => array(
				'banned'      => true,
				'whitelisted' => $whitelisted,
				'customer'    => array(
					'id'               => $customer->id,
					'email'            => $customer->email,
					'first_name'       => $customer->first_name,
					'last_name'        => $customer->last_name,
					'reason'           => $customer->reason,
					'reported_by_site' => $customer->reported_by_site,
					'date_added'       => $customer->date_added,
					'reports_count'    => (int) $customer->reports_count,
				),
			),
		) );
	}

	public static function endpoint_report( WP_REST_Request $request ) {
		$client = $request->get_param( '__pepban_client' );

		$data = array(
			'email'           => $request->get_param( 'email' ),
			'reason'          => $request->get_param( 'reason' ),
			'first_name'      => $request->get_param( 'first_name' ) ?? '',
			'last_name'       => $request->get_param( 'last_name' ) ?? '',
			'phone'           => $request->get_param( 'phone' ) ?? '',
			'billing_address' => $request->get_param( 'billing_address' ) ?? '',
			'ip_address'      => $request->get_param( 'ip_address' ) ?? '',
			'order_id'        => $request->get_param( 'order_id' ) ?? '',
		);

		$is_new      = ! (bool) PepBan_Hub_Database::get_banned_customer_by_email( $data['email'] );
		$customer_id = PepBan_Hub_Database::upsert_banned_customer( $data, $client->id );

		if ( ! $customer_id ) {
			return new WP_Error( 'pepban_report_failed', 'Failed to record ban.', array( 'status' => 500 ) );
		}

		do_action( 'pepban_customer_reported', $customer_id, $client->id, $is_new );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => array(
				'customer_id' => $customer_id,
				'is_new'      => $is_new,
			),
			'message' => $is_new ? 'Customer added to ban list.' : 'Existing ban updated with your report.',
		) );
	}

	public static function endpoint_whitelist_add( WP_REST_Request $request ) {
		$client      = $request->get_param( '__pepban_client' );
		$customer_id = (int) $request->get_param( 'customer_id' );
		$notes       = $request->get_param( 'notes' ) ?? '';

		$customer = PepBan_Hub_Database::get_banned_customer( $customer_id );
		if ( ! $customer ) {
			return new WP_Error( 'pepban_not_found', 'Customer not found in ban list.', array( 'status' => 404 ) );
		}

		PepBan_Hub_Database::add_whitelist( $client->id, $customer_id, $notes );

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Customer whitelisted for your site. They remain in the global ban list.',
		) );
	}

	public static function endpoint_whitelist_remove( WP_REST_Request $request ) {
		$client      = $request->get_param( '__pepban_client' );
		$customer_id = (int) $request->get_param( 'customer_id' );

		PepBan_Hub_Database::remove_whitelist( $client->id, $customer_id );

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Customer removed from your whitelist.',
		) );
	}

	public static function endpoint_whitelist_list( WP_REST_Request $request ) {
		$client = $request->get_param( '__pepban_client' );
		$list   = PepBan_Hub_Database::get_whitelist_for_client( $client->id );
		return rest_ensure_response( array(
			'success' => true,
			'data'    => $list,
		) );
	}
}
