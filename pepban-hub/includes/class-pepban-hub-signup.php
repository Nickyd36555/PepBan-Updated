<?php
defined( 'ABSPATH' ) || exit;

/**
 * Public-facing sign-up and client portal shortcodes.
 *
 * Shortcodes:
 *   [pepban_signup] – Registration form. Redirects to portal after success.
 *   [pepban_portal] – Logged-in client portal: shows status, API key (once), download button.
 *
 * Form submission handled via admin-post.php (works without REST API).
 */
class PepBan_Hub_Signup {

	public static function init() {
		add_shortcode( 'pepban_signup', array( __CLASS__, 'render_signup' ) );
		add_shortcode( 'pepban_portal', array( __CLASS__, 'render_portal' ) );

		add_action( 'admin_post_nopriv_pepban_register',    array( __CLASS__, 'handle_register' ) );
		add_action( 'admin_post_pepban_register',           array( __CLASS__, 'handle_register' ) );
		add_action( 'admin_post_pepban_request_new_key',    array( __CLASS__, 'handle_request_new_key' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	public static function enqueue_styles() {
		wp_enqueue_style( 'pepban-public', PEPBAN_HUB_URL . 'assets/css/public.css', array(), PEPBAN_HUB_VERSION );
	}

	// ── Shortcodes ───────────────────────────────────────────────────────────

	public static function render_signup( $atts ) {
		// If already logged in and has a client record, show portal
		if ( is_user_logged_in() ) {
			$client = PepBan_Hub_Database::get_client_by_user_id( get_current_user_id() );
			if ( $client ) {
				return self::get_portal_html( $client );
			}
		}

		$errors     = array();
		$old_values = array();

		if ( isset( $_GET['pepban_error'] ) ) {
			$ekey   = sanitize_key( $_GET['pepban_error'] );
			$stored = get_transient( 'pepban_signup_err_' . $ekey );
			if ( $stored ) {
				$errors     = $stored;
				$old_values = get_transient( 'pepban_signup_val_' . $ekey ) ?: array();
				delete_transient( 'pepban_signup_err_' . $ekey );
				delete_transient( 'pepban_signup_val_' . $ekey );
			}
		}

		ob_start();
		include PEPBAN_HUB_DIR . 'public/views/signup-form.php';
		return ob_get_clean();
	}

	public static function render_portal( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="pepban-portal-login">
				<p>Please <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">log in</a> to access your PepBan portal.</p>
			</div>
			<?php
			return ob_get_clean();
		}

		$client = PepBan_Hub_Database::get_client_by_user_id( get_current_user_id() );
		if ( ! $client ) {
			$signup_url = get_permalink( get_option( 'pepban_signup_page_id' ) ) ?: home_url( '/' );
			return '<p>No PepBan account found. <a href="' . esc_url( $signup_url ) . '">Sign up here</a>.</p>';
		}

		return self::get_portal_html( $client );
	}

	private static function get_portal_html( $client ) {
		$new_key = '';
		$tkey    = 'pepban_new_api_key_user_' . $client->wp_user_id;
		$stored  = get_transient( $tkey );
		if ( $stored ) {
			$new_key = $stored;
			delete_transient( $tkey );
		}

		ob_start();
		include PEPBAN_HUB_DIR . 'public/views/portal.php';
		return ob_get_clean();
	}

	// ── Handlers ─────────────────────────────────────────────────────────────

	public static function handle_register() {
		check_admin_referer( 'pepban_register' );

		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$site_url = esc_url_raw( wp_unslash( $_POST['site_url'] ?? '' ) );
		$pass     = wp_unslash( $_POST['password'] ?? '' );
		$pass2    = wp_unslash( $_POST['password_confirm'] ?? '' );

		$referer    = wp_get_referer();
		$errors     = array();
		$old_values = array( 'email' => $email, 'name' => $name, 'site_url' => $site_url );
		$ekey       = substr( md5( $email . wp_rand() ), 0, 12 );

		if ( empty( $email ) || ! is_email( $email ) )              $errors[] = 'A valid email address is required.';
		if ( empty( $name ) )                                        $errors[] = 'Your name is required.';
		if ( empty( $site_url ) || ! filter_var( $site_url, FILTER_VALIDATE_URL ) ) $errors[] = 'A valid website URL is required (include https://).';
		if ( strlen( $pass ) < 8 )                                   $errors[] = 'Password must be at least 8 characters.';
		if ( $pass !== $pass2 )                                      $errors[] = 'Passwords do not match.';

		if ( ! empty( $errors ) ) {
			self::store_errors_and_redirect( $ekey, $errors, $old_values, $referer );
		}

		// Existing WP user?
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			$existing_client = PepBan_Hub_Database::get_client_by_user_id( $existing_user->ID );
			if ( $existing_client ) {
				self::store_errors_and_redirect(
					$ekey,
					array( 'An account with this email already exists. Please <a href="' . esc_url( wp_login_url() ) . '">log in</a>.' ),
					$old_values,
					$referer
				);
			}
			$user_id = $existing_user->ID;
		} else {
			// Create WP user
			$username = sanitize_user( explode( '@', $email )[0] );
			if ( username_exists( $username ) ) {
				$username .= '_' . wp_rand( 1000, 9999 );
			}
			$user_id = wp_create_user( $username, $pass, $email );
			if ( is_wp_error( $user_id ) ) {
				self::store_errors_and_redirect( $ekey, array( $user_id->get_error_message() ), $old_values, $referer );
			}
			$parts = explode( ' ', $name, 2 );
			wp_update_user( array(
				'ID'           => $user_id,
				'display_name' => $name,
				'first_name'   => $parts[0],
				'last_name'    => $parts[1] ?? '',
			) );
		}

		// Create client record
		$settings     = get_option( 'pepban_hub_settings', array() );
		$auto_approve = ! empty( $settings['auto_approve_clients'] );

		$result = PepBan_Hub_Database::create_client( array(
			'site_url'    => $site_url,
			'owner_email' => $email,
			'owner_name'  => $name,
			'wp_user_id'  => $user_id,
		) );

		if ( $auto_approve ) {
			PepBan_Hub_Database::update_client_subscription( $result['id'], 'active' );
		}

		// Store key in transient so portal shows it once
		set_transient( 'pepban_new_api_key_user_' . $user_id, $result['api_key'], 600 );

		// Send welcome email
		self::send_welcome_email( $email, $name, $site_url, $result['api_key'], $auto_approve );

		// Log in the new user
		wp_set_auth_cookie( $user_id, false );
		wp_set_current_user( $user_id );

		$portal_page_id = get_option( 'pepban_portal_page_id' );
		$redirect       = $portal_page_id ? get_permalink( $portal_page_id ) : get_permalink();
		wp_safe_redirect( add_query_arg( 'pepban_registered', '1', $redirect ) );
		exit;
	}

	public static function handle_request_new_key() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		check_admin_referer( 'pepban_request_new_key' );

		$user_id = get_current_user_id();
		$client  = PepBan_Hub_Database::get_client_by_user_id( $user_id );
		if ( ! $client ) {
			wp_die( 'No client record found.' );
		}

		$raw_key = PepBan_Hub_Database::regenerate_api_key( $client->id );
		set_transient( 'pepban_new_api_key_user_' . $user_id, $raw_key, 600 );

		// Email too, in case they miss it on screen
		$user    = get_userdata( $user_id );
		self::send_key_email( $user->user_email, $client->owner_name, $raw_key );

		$portal_page_id = get_option( 'pepban_portal_page_id' );
		$redirect       = $portal_page_id ? get_permalink( $portal_page_id ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'pepban_new_key', '1', $redirect ) );
		exit;
	}

	// ── Email helpers ────────────────────────────────────────────────────────

	private static function send_welcome_email( $to, $name, $site_url, $api_key, $is_active ) {
		$portal_url = get_permalink( get_option( 'pepban_portal_page_id' ) ) ?: home_url( '/' );
		$status_msg = $is_active
			? "Your account is active and ready to use right now."
			: "Your account is pending approval. You will receive an email once it is activated.";

		$subject = 'Welcome to PepBan — Your API Key';
		$message =
			"Hi {$name},\n\n" .
			"Thank you for signing up for PepBan!\n\n" .
			"{$status_msg}\n\n" .
			"Your API key (copy and save this — it won't be shown again):\n\n" .
			"  {$api_key}\n\n" .
			"To set up PepBan on {$site_url}:\n" .
			"  1. Log in to your portal: {$portal_url}\n" .
			"  2. Download the PepBan Client plugin\n" .
			"  3. Install it via WP Admin > Plugins > Add New > Upload Plugin\n" .
			"  4. Go to PepBan > Settings and enter:\n" .
			"       Hub URL:  " . home_url( '/' ) . "\n" .
			"       API Key:  {$api_key}\n\n" .
			"Questions? Reply to this email.\n\n" .
			"— PepBan";

		wp_mail( $to, $subject, $message );
	}

	private static function send_key_email( $to, $name, $api_key ) {
		$subject = 'PepBan — Your New API Key';
		$message =
			"Hi {$name},\n\n" .
			"A new API key has been generated for your PepBan account:\n\n" .
			"  {$api_key}\n\n" .
			"Update your PepBan Client plugin settings with this key.\n\n" .
			"— PepBan";
		wp_mail( $to, $subject, $message );
	}

	// ── Internal helpers ─────────────────────────────────────────────────────

	private static function store_errors_and_redirect( $ekey, $errors, $old_values, $referer ) {
		set_transient( 'pepban_signup_err_' . $ekey, $errors,     120 );
		set_transient( 'pepban_signup_val_' . $ekey, $old_values, 120 );
		wp_safe_redirect( add_query_arg( 'pepban_error', $ekey, $referer ) );
		exit;
	}
}
