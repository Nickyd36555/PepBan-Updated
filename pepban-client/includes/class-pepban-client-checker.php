<?php
defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WooCommerce checkout to check customers against the PepBan database.
 * Runs on checkout validation — before the order is created — so banned customers
 * see an error and cannot complete the purchase.
 */
class PepBan_Client_Checker {

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
		$ip         = self::get_customer_ip();

		if ( empty( $email ) && empty( $phone ) ) return;

		// Per-site customer blacklist check
		if ( $email && PepBan_Client_Blacklist::is_blocked( $email ) ) {
			$message = PepBan_Client_Settings::get( 'block_message', '' );
			if ( empty( $message ) ) $message = 'We are unable to process your order at this time. Please contact us for assistance.';
			wc_add_notice( $message, 'error' );
			return;
		}

		// Per-site domain blacklist check (local, no API call needed)
		if ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			$message = PepBan_Client_Settings::get( 'block_message', '' );
			if ( empty( $message ) ) $message = 'We are unable to process your order at this time. Please contact us for assistance.';
			wc_add_notice( $message, 'error' );
			return;
		}

		$result = PepBan_Client_API::check_customer( $email, $phone, $first_name, $last_name, $ip );

		if ( is_wp_error( $result ) ) {
			// API unavailable — fail open or closed based on settings
			if ( PepBan_Client_Settings::get( 'block_on_api_error', false ) ) {
				wc_add_notice( 'Our system is temporarily unavailable. Please try again in a moment.', 'error' );
			}
			return;
		}

		if ( ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			$message = PepBan_Client_Settings::get( 'block_message', '' );
			if ( empty( $message ) ) {
				$message = 'We are unable to process your order at this time. Please contact us for assistance.';
			}
			wc_add_notice( $message, 'error' );
		}
	}

	// Universal safety net — fires for EVERY checkout type after order object is created.
	// Cancels and deletes the order if the customer is banned.
	public static function check_order_and_cancel( $order ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		if ( empty( $email ) && empty( $phone ) ) return;

		$blocked = false;

		if ( $email && PepBan_Client_Blacklist::is_blocked( $email ) ) {
			$blocked = true;
		} elseif ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			$blocked = true;
		} else {
			$result = PepBan_Client_API::check_customer( $email, $phone );
			if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
				$blocked = true;
			}
		}

		if ( $blocked ) {
			// Cancel and trash the order immediately
			$order->update_status( 'cancelled', 'Blocked by PepBan — banned customer.' );
			$order->save();
			wp_trash_post( $order->get_id() );

			$message = PepBan_Client_Settings::get( 'block_message', '' );
			if ( empty( $message ) ) $message = 'We are unable to process your order at this time. Please contact us for assistance.';

			// Show error to customer and halt execution
			wc_add_notice( $message, 'error' );
			throw new Exception( $message );
		}
	}

	// Fires on FunnelKit and most checkout builders — receives $data array and $errors WP_Error
	public static function check_after_validation( $data, $errors ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = sanitize_email( $data['billing_email'] ?? '' );
		$phone = sanitize_text_field( $data['billing_phone'] ?? '' );

		if ( empty( $email ) && empty( $phone ) ) return;

		$message = PepBan_Client_Settings::get( 'block_message', '' );
		if ( empty( $message ) ) $message = 'We are unable to process your order at this time. Please contact us for assistance.';

		if ( $email && PepBan_Client_Blacklist::is_blocked( $email ) ) {
			$errors->add( 'pepban_blocked', $message );
			return;
		}

		if ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			$errors->add( 'pepban_blocked', $message );
			return;
		}

		$result = PepBan_Client_API::check_customer(
			$email,
			$phone,
			sanitize_text_field( $data['billing_first_name'] ?? '' ),
			sanitize_text_field( $data['billing_last_name'] ?? '' ),
			self::get_customer_ip()
		);

		if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			$errors->add( 'pepban_banned', $message );
		}
	}

	public static function check_block_checkout( $order, $request ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();
		if ( empty( $email ) && empty( $phone ) ) return;

		if ( $email && PepBan_Client_Domains::is_blocked( $email ) ) {
			$message = PepBan_Client_Settings::get( 'block_message', 'We are unable to process your order at this time.' );
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pepban_banned', $message, 400 );
		}

		$result = PepBan_Client_API::check_customer(
			$email,
			$phone,
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			self::get_customer_ip()
		);

		if ( ! is_wp_error( $result ) && ! empty( $result['banned'] ) && empty( $result['whitelisted'] ) ) {
			$message = PepBan_Client_Settings::get( 'block_message', 'We are unable to process your order at this time.' );
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'pepban_banned',
				$message,
				400
			);
		}
	}

	public static function show_order_ban_warning( $order ) {
		if ( ! PepBan_Client_Settings::is_configured() ) return;

		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		$result = PepBan_Client_API::check_customer( $email, $phone );
		if ( is_wp_error( $result ) || empty( $result['banned'] ) ) return;

		$customer = $result['customer'] ?? array();
		$is_wl    = ! empty( $result['whitelisted'] );
		?>
		<div class="notice notice-error" style="margin:10px 0;padding:12px">
			<strong>&#128683; PepBan Alert</strong><?php echo $is_wl ? ' <em>(Whitelisted on this site)</em>' : ''; ?>
			<p>This customer (<strong><?php echo esc_html( $customer['email'] ?? $email ); ?></strong>) is in the PepBan database.</p>
			<p><strong>Reason:</strong> <?php echo esc_html( $customer['reason'] ?? 'N/A' ); ?></p>
			<p><strong>Reported by:</strong> <?php echo esc_html( $customer['reported_by_site'] ?? 'N/A' ); ?> &mdash;
			   <strong>Total reports:</strong> <?php echo esc_html( $customer['reports_count'] ?? 1 ); ?></p>
		</div>
		<?php
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
