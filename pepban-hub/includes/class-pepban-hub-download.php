<?php
defined( 'ABSPATH' ) || exit;

/**
 * Serves the pepban-client plugin as a downloadable zip.
 *
 * Only active clients (or admins) can download.
 * Endpoint: POST/GET to /wp-admin/admin-post.php?action=pepban_download_client
 *
 * Source resolution order:
 *  1. Admin-configured path (pepban_hub_settings['client_plugin_path'])
 *  2. Sibling pepban-client directory in the same wp-content/plugins folder
 *  3. Bundled copy at pepban-hub/client-dist/pepban-client/
 */
class PepBan_Hub_Download {

	public static function init() {
		add_action( 'admin_post_pepban_download_client',        array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_pepban_download_client', array( __CLASS__, 'handle' ) );
	}

	public static function handle() {
		// Must be logged in
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( admin_url( 'admin-post.php?action=pepban_download_client' ) ) );
			exit;
		}

		// Must be admin or have an active client record
		if ( ! current_user_can( 'manage_options' ) ) {
			$client = PepBan_Hub_Database::get_client_by_user_id( get_current_user_id() );
			if ( ! $client || 'active' !== $client->subscription_status ) {
				wp_die( 'Your account is not active. Please contact support.', 'Access Denied', array( 'response' => 403 ) );
			}
		}

		$zip = self::build_zip();
		if ( is_wp_error( $zip ) ) {
			wp_die( esc_html( $zip->get_error_message() ), 'Download Failed', array( 'response' => 500 ) );
		}

		$filename = 'pepban-client-' . PEPBAN_HUB_VERSION . '.zip';
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $zip ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Flush any output buffering before streaming
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $zip );
		unlink( $zip );
		exit;
	}

	private static function build_zip() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'no_zip', 'PHP ZipArchive extension is not available on this server. Ask your host to enable it.' );
		}

		$source = self::locate_client_dir();
		if ( ! $source ) {
			return new WP_Error(
				'no_source',
				'PepBan Client plugin files not found. ' .
				'Upload the pepban-client folder to ' . WP_PLUGIN_DIR . ' or set a custom path in PepBan Hub Settings.'
			);
		}

		$zip_path = get_temp_dir() . 'pepban-client-' . time() . '-' . wp_rand() . '.zip';
		$zip      = new ZipArchive();

		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new WP_Error( 'zip_open', 'Could not create zip file.' );
		}

		self::zip_directory( $zip, $source, 'pepban-client' );
		$zip->close();

		return $zip_path;
	}

	/**
	 * Returns the absolute path to the pepban-client directory, or null.
	 */
	private static function locate_client_dir() {
		$candidates = array();

		// 1. Admin-configured custom path
		$settings = get_option( 'pepban_hub_settings', array() );
		if ( ! empty( $settings['client_plugin_path'] ) ) {
			$candidates[] = trailingslashit( $settings['client_plugin_path'] );
		}

		// 2. Sibling in wp-content/plugins/
		$candidates[] = WP_PLUGIN_DIR . '/pepban-client/';

		// 3. Bundled inside this plugin (production distribution)
		$candidates[] = PEPBAN_HUB_DIR . 'client-dist/pepban-client/';

		foreach ( $candidates as $path ) {
			if ( is_dir( $path ) && file_exists( $path . 'pepban-client.php' ) ) {
				return $path;
			}
		}

		return null;
	}

	private static function zip_directory( ZipArchive $zip, $source_dir, $zip_prefix ) {
		$source_dir = rtrim( realpath( $source_dir ), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
		$files      = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $files as $file ) {
			$real     = $file->getRealPath();
			$relative = substr( $real, strlen( $source_dir ) );
			$entry    = $zip_prefix . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $relative );

			if ( $file->isDir() ) {
				$zip->addEmptyDir( $entry );
			} else {
				$zip->addFile( $real, $entry );
			}
		}
	}
}
