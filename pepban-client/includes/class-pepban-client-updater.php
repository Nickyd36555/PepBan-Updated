<?php
defined( 'ABSPATH' ) || exit;

class PepBan_Client_Updater {

	const PLUGIN_SLUG = 'pepban-client';
	const PLUGIN_FILE = 'pepban-client/pepban-client.php';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_directory_name' ), 10, 4 );
	}

	public static function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) return $transient;

		$info = self::get_remote_info();
		if ( ! $info || empty( $info['version'] ) ) return $transient;

		if ( version_compare( $info['version'], PEPBAN_CLIENT_VERSION, '>' ) ) {
			$transient->response[ self::PLUGIN_FILE ] = (object) array(
				'slug'        => self::PLUGIN_SLUG,
				'plugin'      => self::PLUGIN_FILE,
				'new_version' => $info['version'],
				'url'         => $info['details_url'] ?? 'https://pepban.com',
				'package'     => $info['download_url'] ?? '',
			);
		} else {
			// Let WordPress know this plugin is up to date
			$transient->no_update[ self::PLUGIN_FILE ] = (object) array(
				'slug'        => self::PLUGIN_SLUG,
				'plugin'      => self::PLUGIN_FILE,
				'new_version' => PEPBAN_CLIENT_VERSION,
				'url'         => 'https://pepban.com',
				'package'     => '',
			);
		}

		return $transient;
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) return $result;
		if ( ( $args->slug ?? '' ) !== self::PLUGIN_SLUG ) return $result;

		$info = self::get_remote_info();
		if ( ! $info ) return $result;

		return (object) array(
			'name'          => 'PepBan Client',
			'slug'          => self::PLUGIN_SLUG,
			'version'       => $info['version'],
			'author'        => '<a href="https://pepban.com">PepBan</a>',
			'homepage'      => 'https://pepban.com',
			'download_link' => $info['download_url'] ?? '',
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'sections'      => array(
				'description'  => $info['description']  ?? '',
				'installation' => $info['installation'] ?? '',
				'changelog'    => $info['changelog']    ?? '',
			),
		);
	}

	// WordPress extracts ZIPs into a folder named after the ZIP — rename it to pepban-client
	public static function fix_directory_name( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ( $hook_extra['plugin'] ?? '' ) !== self::PLUGIN_FILE ) return $source;
		$corrected = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
		if ( $source !== $corrected ) {
			global $wp_filesystem;
			$wp_filesystem->move( $source, $corrected );
			return $corrected;
		}
		return $source;
	}

	private static function get_remote_info() {
		$cached = get_transient( 'pepban_plugin_update_info' );
		if ( false !== $cached ) return $cached;

		$result = PepBan_Client_API::get( '/plugin/info' );
		if ( is_wp_error( $result ) || empty( $result['version'] ) ) {
			set_transient( 'pepban_plugin_update_info', array(), 30 * MINUTE_IN_SECONDS );
			return array();
		}

		set_transient( 'pepban_plugin_update_info', $result, 6 * HOUR_IN_SECONDS );
		return $result;
	}
}
