<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Hub_Clients {

	/**
	 * Generate a fresh client record and return the plaintext API key (shown once).
	 */
	public static function create( $site_url, $owner_email, $owner_name = '' ) {
		return PepBan_Hub_Database::create_client( array(
			'site_url'    => $site_url,
			'owner_email' => $owner_email,
			'owner_name'  => $owner_name,
		) );
	}

	public static function activate( $client_id ) {
		PepBan_Hub_Database::update_client_subscription( $client_id, 'active' );
	}

	public static function deactivate( $client_id ) {
		PepBan_Hub_Database::update_client_subscription( $client_id, 'inactive' );
	}

	public static function get_client_by_owner_email( $email ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}pepban_clients WHERE owner_email = %s ORDER BY date_registered DESC LIMIT 1",
			sanitize_email( $email )
		) );
	}
}
