<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Hub_Database {

	// ── Banned customers ────────────────────────────────────────────────────

	public static function get_banned_customers( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_banned_customers';

		$defaults = array(
			'status'   => 'active',
			'search'   => '',
			'per_page' => 25,
			'page'     => 1,
			'orderby'  => 'date_added',
			'order'    => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s)';
			$params   = array_merge( $params, array( $like, $like, $like, $like ) );
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
		$orderby   = in_array( $args['orderby'], array( 'email', 'date_added', 'reports_count', 'last_updated' ), true )
			? $args['orderby'] : 'date_added';
		$order     = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$params[] = absint( $args['per_page'] );
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function count_banned_customers( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_banned_customers';

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s)';
			$params  = array_merge( $params, array( $like, $like, $like, $like ) );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(*) FROM $table WHERE $where_sql";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}

	public static function get_banned_customer( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_banned_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_banned_customer_by_email( $email ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_banned_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", sanitize_email( $email ) ) );
	}

	public static function get_banned_customer_by_phone( $phone ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'pepban_banned_customers';
		$cleaned = preg_replace( '/\D/', '', $phone );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = %s AND status = 'active'",
			$cleaned
		) );
	}

	/**
	 * Add or update a banned customer. Returns the customer ID.
	 */
	public static function upsert_banned_customer( $data, $client_id ) {
		global $wpdb;
		$customers_table = $wpdb->prefix . 'pepban_banned_customers';
		$reports_table   = $wpdb->prefix . 'pepban_ban_reports';

		$email    = sanitize_email( $data['email'] );
		$existing = self::get_banned_customer_by_email( $email );
		$now      = current_time( 'mysql' );

		if ( $existing ) {
			// Increment report count and update auxiliary fields if provided
			$wpdb->update(
				$customers_table,
				array(
					'reports_count' => $existing->reports_count + 1,
					'last_updated'  => $now,
					'phone'         => ! empty( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : $existing->phone,
					'billing_address' => ! empty( $data['billing_address'] ) ? sanitize_textarea_field( $data['billing_address'] ) : $existing->billing_address,
					'ip_address'    => ! empty( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : $existing->ip_address,
				),
				array( 'id' => $existing->id ),
				array( '%d', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			$customer_id = $existing->id;
		} else {
			$client = self::get_client( $client_id );
			$wpdb->insert(
				$customers_table,
				array(
					'email'              => $email,
					'first_name'         => sanitize_text_field( $data['first_name'] ?? '' ),
					'last_name'          => sanitize_text_field( $data['last_name'] ?? '' ),
					'phone'              => sanitize_text_field( $data['phone'] ?? '' ),
					'billing_address'    => sanitize_textarea_field( $data['billing_address'] ?? '' ),
					'ip_address'         => sanitize_text_field( $data['ip_address'] ?? '' ),
					'reason'             => sanitize_textarea_field( $data['reason'] ?? '' ),
					'reported_by_site'   => $client ? esc_url_raw( $client->site_url ) : '',
					'reported_by_client' => $client_id,
					'date_added'         => $now,
					'last_updated'       => $now,
					'status'             => 'active',
					'reports_count'      => 1,
				),
				array( '%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d' )
			);
			$customer_id = $wpdb->insert_id;
		}

		// Always log the individual report
		$client = self::get_client( $client_id );
		$wpdb->insert(
			$reports_table,
			array(
				'customer_id'   => $customer_id,
				'client_id'     => $client_id,
				'site_url'      => $client ? esc_url_raw( $client->site_url ) : '',
				'reason'        => sanitize_textarea_field( $data['reason'] ?? '' ),
				'order_id'      => sanitize_text_field( $data['order_id'] ?? '' ),
				'ip_address'    => sanitize_text_field( $data['ip_address'] ?? '' ),
				'date_reported' => $now,
			),
			array( '%d','%d','%s','%s','%s','%s','%s' )
		);

		return $customer_id;
	}

	public static function delete_banned_customer( $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'pepban_banned_customers', array( 'id' => absint( $id ) ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'pepban_ban_reports', array( 'customer_id' => absint( $id ) ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'pepban_whitelists', array( 'customer_id' => absint( $id ) ), array( '%d' ) );
	}

	public static function update_banned_customer_status( $id, $status ) {
		global $wpdb;
		$allowed = array( 'active', 'inactive', 'pending' );
		if ( ! in_array( $status, $allowed, true ) ) return false;
		return $wpdb->update(
			$wpdb->prefix . 'pepban_banned_customers',
			array( 'status' => $status, 'last_updated' => current_time( 'mysql' ) ),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function get_ban_reports_for_customer( $customer_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_ban_reports';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT r.*, c.owner_name FROM $table r
			 LEFT JOIN {$wpdb->prefix}pepban_clients c ON r.client_id = c.id
			 WHERE r.customer_id = %d ORDER BY r.date_reported DESC",
			$customer_id
		) );
	}

	// ── Clients ─────────────────────────────────────────────────────────────

	public static function get_clients( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_clients';

		$defaults = array(
			'status'   => '',
			'per_page' => 25,
			'page'     => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'subscription_status = %s';
			$params[] = $args['status'];
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
		$sql       = "SELECT * FROM $table WHERE $where_sql ORDER BY date_registered DESC LIMIT %d OFFSET %d";
		$params[]  = absint( $args['per_page'] );
		$params[]  = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_client( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_clients';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_client_by_api_key( $raw_key ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'pepban_clients';
		$prefix = substr( $raw_key, 0, 8 );
		$rows   = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table WHERE api_key_prefix = %s",
			$prefix
		) );
		foreach ( $rows as $row ) {
			if ( wp_check_password( $raw_key, $row->api_key_hash ) ) {
				return $row;
			}
		}
		return null;
	}

	public static function create_client( $data ) {
		global $wpdb;
		$raw_key = 'pbk_' . wp_generate_password( 36, false );
		$wpdb->insert(
			$wpdb->prefix . 'pepban_clients',
			array(
				'site_url'            => esc_url_raw( $data['site_url'] ),
				'api_key_hash'        => wp_hash_password( $raw_key ),
				'api_key_prefix'      => substr( $raw_key, 0, 8 ),
				'owner_email'         => sanitize_email( $data['owner_email'] ),
				'owner_name'          => sanitize_text_field( $data['owner_name'] ?? '' ),
				'subscription_status' => 'inactive',
				'date_registered'     => current_time( 'mysql' ),
			),
			array( '%s','%s','%s','%s','%s','%s','%s' )
		);
		return array(
			'id'      => $wpdb->insert_id,
			'api_key' => $raw_key,
		);
	}

	public static function update_client_subscription( $client_id, $status, $woo_subscription_id = 0 ) {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . 'pepban_clients',
			array(
				'subscription_status' => sanitize_text_field( $status ),
				'woo_subscription_id' => absint( $woo_subscription_id ),
			),
			array( 'id' => absint( $client_id ) ),
			array( '%s', '%d' ),
			array( '%d' )
		);
	}

	public static function update_client_last_active( $client_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pepban_clients',
			array( 'last_active' => current_time( 'mysql' ) ),
			array( 'id' => absint( $client_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function regenerate_api_key( $client_id ) {
		global $wpdb;
		$raw_key = 'pbk_' . wp_generate_password( 36, false );
		$wpdb->update(
			$wpdb->prefix . 'pepban_clients',
			array(
				'api_key_hash'   => wp_hash_password( $raw_key ),
				'api_key_prefix' => substr( $raw_key, 0, 8 ),
			),
			array( 'id' => absint( $client_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		return $raw_key;
	}

	public static function delete_client( $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'pepban_clients', array( 'id' => absint( $id ) ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'pepban_whitelists', array( 'client_id' => absint( $id ) ), array( '%d' ) );
	}

	// ── Whitelists ───────────────────────────────────────────────────────────

	public static function is_whitelisted( $client_id, $customer_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pepban_whitelists';
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE client_id = %d AND customer_id = %d",
			$client_id, $customer_id
		) );
	}

	public static function add_whitelist( $client_id, $customer_id, $notes = '' ) {
		global $wpdb;
		if ( self::is_whitelisted( $client_id, $customer_id ) ) return true;
		return $wpdb->insert(
			$wpdb->prefix . 'pepban_whitelists',
			array(
				'client_id'   => absint( $client_id ),
				'customer_id' => absint( $customer_id ),
				'date_added'  => current_time( 'mysql' ),
				'notes'       => sanitize_textarea_field( $notes ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	public static function remove_whitelist( $client_id, $customer_id ) {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . 'pepban_whitelists',
			array( 'client_id' => absint( $client_id ), 'customer_id' => absint( $customer_id ) ),
			array( '%d', '%d' )
		);
	}

	public static function get_whitelist_for_client( $client_id ) {
		global $wpdb;
		$wl = $wpdb->prefix . 'pepban_whitelists';
		$bc = $wpdb->prefix . 'pepban_banned_customers';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT w.*, b.email, b.first_name, b.last_name FROM $wl w
			 JOIN $bc b ON w.customer_id = b.id
			 WHERE w.client_id = %d ORDER BY w.date_added DESC",
			$client_id
		) );
	}

	// ── Dashboard stats ──────────────────────────────────────────────────────

	public static function get_stats() {
		global $wpdb;
		return array(
			'total_banned'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pepban_banned_customers WHERE status='active'" ),
			'total_clients'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pepban_clients WHERE subscription_status='active'" ),
			'total_reports'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pepban_ban_reports" ),
			'recent_banned'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pepban_banned_customers WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ),
		);
	}
}
