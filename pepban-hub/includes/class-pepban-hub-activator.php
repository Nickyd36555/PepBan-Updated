<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Hub_Activator {

	public static function activate() {
		self::create_tables();
		add_option( 'pepban_hub_version', PEPBAN_HUB_VERSION );
		add_option( 'pepban_hub_settings', array(
			'require_https'        => true,
			'rate_limit_per_min'   => 60,
			'auto_approve_clients' => false,
		) );
	}

	private static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Banned customers — one canonical record per email
		dbDelta( "CREATE TABLE {$wpdb->prefix}pepban_banned_customers (
			id            bigint(20)   NOT NULL AUTO_INCREMENT,
			email         varchar(200) NOT NULL,
			first_name    varchar(100) NOT NULL DEFAULT '',
			last_name     varchar(100) NOT NULL DEFAULT '',
			phone         varchar(50)  NOT NULL DEFAULT '',
			billing_address text       NOT NULL DEFAULT '',
			ip_address    varchar(45)  NOT NULL DEFAULT '',
			reason        text         NOT NULL,
			reported_by_site   varchar(255) NOT NULL,
			reported_by_client bigint(20)   NOT NULL DEFAULT 0,
			date_added    datetime     NOT NULL,
			last_updated  datetime     NOT NULL,
			status        varchar(20)  NOT NULL DEFAULT 'active',
			admin_notes   text         NOT NULL DEFAULT '',
			reports_count int(11)      NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY   email (email),
			KEY          status (status)
		) $charset;" );

		// Every individual report (supports multiple sites reporting same email)
		dbDelta( "CREATE TABLE {$wpdb->prefix}pepban_ban_reports (
			id           bigint(20)   NOT NULL AUTO_INCREMENT,
			customer_id  bigint(20)   NOT NULL,
			client_id    bigint(20)   NOT NULL,
			site_url     varchar(255) NOT NULL,
			reason       text         NOT NULL,
			order_id     varchar(100) NOT NULL DEFAULT '',
			ip_address   varchar(45)  NOT NULL DEFAULT '',
			date_reported datetime    NOT NULL,
			PRIMARY KEY (id),
			KEY customer_id (customer_id),
			KEY client_id   (client_id)
		) $charset;" );

		// Registered API clients (one per subscribed site)
		dbDelta( "CREATE TABLE {$wpdb->prefix}pepban_clients (
			id                   bigint(20)   NOT NULL AUTO_INCREMENT,
			site_url             varchar(255) NOT NULL,
			api_key_hash         varchar(255) NOT NULL,
			api_key_prefix       varchar(10)  NOT NULL,
			owner_email          varchar(200) NOT NULL,
			owner_name           varchar(200) NOT NULL DEFAULT '',
			subscription_status  varchar(20)  NOT NULL DEFAULT 'inactive',
			date_registered      datetime     NOT NULL,
			last_active          datetime     DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY   api_key_hash (api_key_hash),
			KEY          subscription_status (subscription_status)
		) $charset;" );

		// Per-client whitelists (customer stays banned globally, but this site allows them)
		dbDelta( "CREATE TABLE {$wpdb->prefix}pepban_whitelists (
			id          bigint(20) NOT NULL AUTO_INCREMENT,
			client_id   bigint(20) NOT NULL,
			customer_id bigint(20) NOT NULL,
			date_added  datetime   NOT NULL,
			notes       text       NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			UNIQUE KEY  client_customer (client_id, customer_id),
			KEY         client_id   (client_id),
			KEY         customer_id (customer_id)
		) $charset;" );
	}
}
