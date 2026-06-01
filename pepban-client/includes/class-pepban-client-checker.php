<?php
defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WooCommerce checkout to check customers against the PepBan database.
 * Runs on checkout validation — before the order is created — so banned customers
 * see an error and cannot complete the purchase.
 */
class PepBan_Client_Checker {

	// Per-request cache — prevents duplicate API calls when multiple hooks fire for the same checkout
	private static $check_cache = array();

	public static function init() {
		// Universal: fires for every checkout type before the order is saved
		add_action( 'woocommerce_checkout_order_created',    array( __CLASS__, 'check_order_and_cancel' ), 1, 1 );

		// Classic checkout + FunnelKit validation (adds user-facing error message)
		add_action( 'woocommerce_checkout_process',          array( __CLASS__, 'check_at_checkout' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'check_after_validation' ), 10, 2 );

		// Block-based checkout (WooCommerce Blocks)
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'check_block_checkout' ), 10, 2 );

		// Show a warning banner on the order edit page for already-placed orders
		if ( PepBan_Client_Settings::get( 'show_ban_notice_admin', true ) ) {
			add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'show_order_ban_warning' ) );
		}
	}

	public static function check_at_checkout() {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email      = sanitize_email( wp_unslash( $_POST['billing_email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['billing_phone'] ?? '' ) );
		$first_name = sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ?? '' ) );
		$ip         = PepBan_Client_Settings::get( 'check_ip', true ) ? self::get_customer_ip() : '';

		if ( empty( $email ) && empty( $phone ) ) return;

		$address = PepBan_Client_Settings::get( 'check_billing_address', true )
			? strtolower( implode( ' ', array_filter( array(
				sanitize_text_field( wp_unslash( $_POST['billing_address_1'] ?? '' ) ),
				sanitize_text_field( wp_unslash( $_POST['billing_city']      ?? '' ) ),
				sanitize_text_field( wp_unslash( $_POST['billing_state']     ?? '' ) ),
				sanitize_text_field( wp_unslash( $_POST['billing_postcode']  ?? '' ) ),
			) ) ) )
			: '';

		if ( PepBan_Client_Blacklist::is_whitelisted_locally( $email, $ip, $address ) ) return;

		if ( $email   && PepBan_Client_Blacklist::is_blocked( $email ) )            { wc_add_notice( self::get_block_message(), 'error' ); return; }
		if ( $ip      && PepBan_Client_Blacklist::is_blocked_ip( $ip ) )            { wc_add_notice( self::get_block_message(), 'error' ); return; }
		if ( $address && PepBan_Client_Blacklist::is_blocked_address( $address ) )  { wc_add_notice( self::get_block_message(), 'error' ); return; }
		if ( $email   && PepBan_Client_Domains::is_blocked( $email ) )              { wc_add_notice( self::get_block_message(), 'error' ); return; }

		$result = self::api_check( $email, $phone, $first_name, $last_name, $ip );

		if ( is_wp_error( $result ) ) {
			if ( PepBan_Client_Settings::get( 'block_on_api_error', false ) ) {
				wc_add_notice( 'Our system is temporarily unavailable. Please try again in a moment.', 'error' );
			}
			return;
		}

		if ( ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			wc_add_notice( self::get_block_message(), 'error' );
		}
	}

	// Universal safety net — fires for EVERY checkout type after order object is created.
	// Cancels and deletes the order if the customer is banned.
	public static function check_order_and_cancel( $order ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		if ( empty( $email ) && empty( $phone ) ) return;

		$ip      = PepBan_Client_Settings::get( 'check_ip', true ) ? self::get_customer_ip() : '';
		$address = PepBan_Client_Settings::get( 'check_billing_address', true )
			? strtolower( implode( ' ', array_filter( array(
				$order->get_billing_address_1(),
				$order->get_billing_city(),
				$order->get_billing_state(),
				$order->get_billing_postcode(),
			) ) ) )
			: '';

		$blocked = false;

		if ( PepBan_Client_Blacklist::is_whitelisted_locally( $email, $ip, $address ) ) return;

		if ( $email   && PepBan_Client_Blacklist::is_blocked( $email ) )           { $blocked = true; }
		elseif ( $ip  && PepBan_Client_Blacklist::is_blocked_ip( $ip ) )           { $blocked = true; }
		elseif ( $address && PepBan_Client_Blacklist::is_blocked_address( $address ) ) { $blocked = true; }
		elseif ( $email && PepBan_Client_Domains::is_blocked( $email ) )           { $blocked = true; }
		else {
			$result = self::api_check( $email, $phone );
			if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
				$blocked = true;
			}
		}

		if ( $blocked ) {
			$message = self::get_block_message();

			// Mark failed so payment gateways see a terminal state — do NOT trash,
			// as gateways may still hold a reference to this order ID.
			$order->update_status( 'failed', 'Blocked by PepBan — banned customer.' );
			$order->save();

			if ( PepBan_Client_Settings::get( 'notify_store_on_attempt', false ) ) {
				$tkey = 'pepban_alert_' . md5( $order->get_billing_email() );
				if ( ! get_transient( $tkey ) ) {
					$result_data = isset( $result ) && ! is_wp_error( $result ) ? $result : array();
					self::send_store_alert( $order->get_billing_email(), $order->get_billing_first_name(), $order->get_billing_last_name(), $result_data );
					set_transient( $tkey, 1, HOUR_IN_SECONDS );
				}
			}

			wc_add_notice( $message, 'error' );
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pepban_banned', $message, 400 );
		}
	}

	// Fires on FunnelKit and most checkout builders — receives $data array and $errors WP_Error
	public static function check_after_validation( $data, $errors ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = sanitize_email( $data['billing_email'] ?? '' );
		$phone = sanitize_text_field( $data['billing_phone'] ?? '' );

		if ( empty( $email ) && empty( $phone ) ) return;

		if ( $email && PepBan_Client_Blacklist::is_blocked( $email ) ) {
			$errors->add( 'pepban_blocked', self::get_block_message() );
			return;
		}

		if ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			$errors->add( 'pepban_blocked', self::get_block_message() );
			return;
		}

		$result = self::api_check(
			$email,
			$phone,
			sanitize_text_field( $data['billing_first_name'] ?? '' ),
			sanitize_text_field( $data['billing_last_name'] ?? '' ),
			self::get_customer_ip()
		);

		if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			$errors->add( 'pepban_banned', self::get_block_message() );
		}
	}

	public static function check_block_checkout( $order, $request ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();
		if ( empty( $email ) && empty( $phone ) ) return;

		if ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pepban_banned', self::get_block_message(), 400 );
		}

		$result = self::api_check(
			$email,
			$phone,
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			self::get_customer_ip()
		);

		if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pepban_banned', self::get_block_message(), 400 );
		}
	}

	public static function show_order_ban_warning( $order ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		$result = PepBan_Client_API::check_customer( $email, $phone );
		if ( is_wp_error( $result ) || empty( $result['banned'] ) ) return;

		$customer     = $result['customer']    ?? array();
		$is_wl        = ! empty( $result['whitelisted'] );
		$report_count = (int) ( $result['report_count'] ?? 1 );
		$store_count  = (int) ( $result['store_count']  ?? 1 );
		?>
		<div class="notice notice-<?php echo $is_wl ? 'warning' : 'error'; ?>" style="margin:10px 0;padding:14px 16px">
			<strong>&#128683; PepBan Alert</strong><?php echo $is_wl ? ' &mdash; <em>Whitelisted on this site (allowed through)</em>' : ''; ?>
			<p style="margin:8px 0 4px"><strong>Customer:</strong> <?php echo esc_html( $customer['email'] ?? $email ); ?></p>
			<p style="margin:4px 0"><strong>Reason:</strong> <?php echo esc_html( $customer['reason'] ?? 'N/A' ); ?></p>
			<div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px solid rgba(0,0,0,.1)">
				<div>
					<span style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#666">Total Reports</span><br>
					<strong><?php echo esc_html( $report_count ); ?></strong>
				</div>
				<div>
					<span style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#666">Stores Reported</span><br>
					<strong><?php echo esc_html( $store_count ); ?></strong>
				</div>
			</div>
		</div>
		<?php
	}

	// Returns the block message, appending an appeal link if one is configured.
	private static function get_block_message(): string {
		$message = PepBan_Client_Settings::get( 'block_message', '' );
		if ( empty( $message ) ) {
			$message = 'You have been reported as a scammer. Please contact the site administrator for assistance.';
		}
		$appeal = PepBan_Client_Settings::get( 'appeal_url', '' );
		if ( $appeal ) {
			$message .= ' <a href="' . esc_url( $appeal ) . '">Contact us to dispute this.</a>';
		}
		return $message;
	}

	private static function api_check( string $email, string $phone, string $first = '', string $last = '', string $ip = '' ) {
		$key = md5( $email . '|' . $phone );
		if ( ! isset( self::$check_cache[ $key ] ) ) {
			$result = PepBan_Client_API::check_customer( $email, $phone, $first, $last, $ip );
			self::$check_cache[ $key ] = $result;

			if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
				// Auto-report on block: silently report once per hour per email
				if ( PepBan_Client_Settings::get( 'auto_report_on_flag', false ) ) {
					$tkey = 'pepban_auto_rep_' . md5( $email );
					if ( ! get_transient( $tkey ) ) {
						PepBan_Client_API::report_customer( array_filter( array(
							'email'      => $email,
							'phone'      => $phone,
							'first_name' => $first,
							'last_name'  => $last,
							'ip_address' => $ip,
							'reason'     => 'Auto-reported: customer blocked at checkout',
						) ) );
						set_transient( $tkey, 1, HOUR_IN_SECONDS );
					}
				}

				// Alert store owner: once per hour per customer email
				if ( PepBan_Client_Settings::get( 'notify_store_on_attempt', false ) ) {
					$tkey = 'pepban_alert_' . md5( $email );
					if ( ! get_transient( $tkey ) ) {
						self::send_store_alert( $email, $first, $last, $result );
						set_transient( $tkey, 1, HOUR_IN_SECONDS );
					}
				}
			}
		}
		return self::$check_cache[ $key ];
	}

	private static function send_store_alert( string $email, string $first, string $last, array $result ): void {
		$configured = PepBan_Client_Settings::get( 'alert_email', '' );
		$to         = ( $configured && is_email( $configured ) ) ? $configured : get_bloginfo( 'admin_email' );
		$site_name    = get_bloginfo( 'name' );
		$customer     = $result['customer'] ?? array();
		$name         = trim( ( $customer['first_name'] ?? $first ) . ' ' . ( $customer['last_name'] ?? $last ) ) ?: 'Unknown';
		$reason       = $customer['reason'] ?? 'N/A';
		$report_count = (int) ( $result['report_count'] ?? 1 );
		$store_count  = (int) ( $result['store_count']  ?? 1 );
		$orders_url   = admin_url( 'edit.php?post_type=shop_order&s=' . urlencode( $email ) );
		$pepban_url   = 'https://pepban.com/admin/banned';

		$subject = '[PepBan] Banned customer attempted checkout on ' . $site_name;

		$html =
			'<!DOCTYPE html><html><head><meta charset="UTF-8"></head>' .
			'<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px"><tr><td align="center">' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%">' .
			'<tr><td style="background:#0c0c1e;border-radius:10px 10px 0 0;padding:24px 36px">' .
			'<span style="font-size:22px;font-weight:800;color:#fff;letter-spacing:-.02em">Pep<span style="color:#dc2626">Ban</span></span>' .
			'<span style="float:right;background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.06em;text-transform:uppercase;margin-top:4px">Alert</span>' .
			'</td></tr>' .
			'<tr><td style="background:#fff;padding:36px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb">' .
			'<h2 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#111827">Banned customer attempted checkout</h2>' .
			'<p style="margin:0 0 24px;font-size:14px;color:#6b7280">on <strong style="color:#374151">' . esc_html( $site_name ) . '</strong></p>' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px">' .
			'<tr style="background:#f9fafb"><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;width:130px">Email</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#111827;font-weight:600">' . esc_html( $email ) . '</td></tr>' .
			'<tr><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;border-top:1px solid #f3f4f6">Name</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#374151;border-top:1px solid #f3f4f6">' . esc_html( $name ) . '</td></tr>' .
			'<tr style="background:#fef2f2"><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#dc2626;border-top:1px solid #fecaca">Reason</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#7f1d1d;border-top:1px solid #fecaca">' . esc_html( $reason ) . '</td></tr>' .
			'<tr><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;border-top:1px solid #f3f4f6">Reports</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#374151;border-top:1px solid #f3f4f6"><strong>' . esc_html( $report_count ) . '</strong> report(s) across <strong>' . esc_html( $store_count ) . '</strong> store(s)</td></tr>' .
			'</table>' .
			'<table cellpadding="0" cellspacing="0"><tr>' .
			'<td style="padding-right:10px"><a href="' . esc_url( $orders_url ) . '" style="display:inline-block;background:#111827;color:#fff;padding:11px 22px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600">View Orders</a></td>' .
			'<td><a href="' . esc_url( $pepban_url ) . '" style="display:inline-block;background:#dc2626;color:#fff;padding:11px 22px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600">View in PepBan</a></td>' .
			'</tr></table>' .
			'</td></tr>' .
			'<tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 10px 10px;padding:16px 36px;text-align:center">' .
			'<p style="margin:0;font-size:12px;color:#9ca3af">This alert was sent by the PepBan plugin on ' . esc_html( $site_name ) . '. You receive one alert per customer per hour.</p>' .
			'</td></tr></table></td></tr></table></body></html>';

		$sent = wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		if ( ! $sent ) {
			error_log( 'PepBan: store alert wp_mail() failed for recipient ' . $to . ' (customer: ' . $email . ')' );
		}
	}

	private static function get_customer_ip() {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// X-Forwarded-For can be a comma-separated list
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
			}
		}
		return '';
	}
}
