<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds "Report to PepBan" button on WooCommerce order screens and handles
 * the AJAX submission that sends the customer to the hub.
 */
class PepBan_Client_Reporter {

	public static function init() {
		// Meta box on order edit screen
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_order_meta_box' ) );

		// AJAX: report customer from order screen
		add_action( 'wp_ajax_pepban_report_customer', array( __CLASS__, 'ajax_report_customer' ) );

		// Enqueue scripts for order screen
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_order_scripts' ) );
	}

	public static function add_order_meta_box() {
		$screens = function_exists( 'wc_get_page_screen_id' ) ? array( 'woocommerce_page_wc-orders', 'shop_order' ) : array( 'shop_order' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'pepban-report',
				'PepBan — Report Customer',
				array( __CLASS__, 'render_meta_box' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	public static function render_meta_box( $post_or_order ) {
		$order_id = is_a( $post_or_order, 'WC_Order' ) ? $post_or_order->get_id() : $post_or_order->ID;
		$order    = wc_get_order( $order_id );
		if ( ! $order ) return;

		$already_reported = $order->get_meta( '_pepban_reported' );
		$ban_customer_id  = $order->get_meta( '_pepban_customer_id' );
		?>
		<div id="pepban-report-box">
			<?php if ( $already_reported ) : ?>
				<p style="color:#00a32a">&#10003; Reported to PepBan on <?php echo esc_html( $already_reported ); ?></p>
				<?php if ( $ban_customer_id ) : ?>
					<p>PepBan Customer ID: <strong><?php echo esc_html( $ban_customer_id ); ?></strong></p>
					<button type="button" class="button pepban-whitelist-btn"
						data-order-id="<?php echo esc_attr( $order_id ); ?>"
						data-customer-id="<?php echo esc_attr( $ban_customer_id ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'pepban_whitelist_' . $order_id ) ); ?>">
						Whitelist on This Site
					</button>
				<?php endif; ?>
			<?php else : ?>
				<p>Report this customer's email, phone, and billing info to the PepBan network.</p>
				<label for="pepban-reason-<?php echo esc_attr( $order_id ); ?>">Reason for ban:</label><br>
				<textarea id="pepban-reason-<?php echo esc_attr( $order_id ); ?>" name="pepban_reason"
					rows="3" style="width:100%;margin:6px 0"
					placeholder="e.g. Chargeback, fraud, abuse…"></textarea>
				<button type="button" class="button button-primary pepban-report-btn" style="width:100%"
					data-order-id="<?php echo esc_attr( $order_id ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'pepban_report_' . $order_id ) ); ?>">
					Report to PepBan
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function enqueue_order_scripts( $hook ) {
		$order_screens = array( 'post.php', 'woocommerce_page_wc-orders' );
		if ( ! in_array( $hook, $order_screens, true ) ) return;

		$post_type = get_post_type( get_the_ID() );
		if ( 'shop_order' !== $post_type && 'woocommerce_page_wc-orders' !== $hook ) return;

		wp_enqueue_script(
			'pepban-client-order',
			PEPBAN_CLIENT_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			PEPBAN_CLIENT_VERSION,
			true
		);
		wp_localize_script( 'pepban-client-order', 'pepbanClient', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pepban_client_nonce' ),
		) );
	}

	public static function ajax_report_customer() {
		$order_id = absint( $_POST['order_id'] ?? 0 );
		$reason   = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $order_id || ! check_ajax_referer( 'pepban_report_' . $order_id, 'nonce', false ) ) {
			wp_send_json_error( 'Invalid request.' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		if ( empty( $reason ) ) {
			wp_send_json_error( 'Please provide a reason for the ban.' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Order not found.' );
		}

		$billing = $order->get_address( 'billing' );
		$address = implode( ', ', array_filter( array(
			$billing['address_1'] ?? '',
			$billing['address_2'] ?? '',
			$billing['city']      ?? '',
			$billing['state']     ?? '',
			$billing['postcode']  ?? '',
			$billing['country']   ?? '',
		) ) );

		$reveal   = (bool) PepBan_Client_Settings::get( 'reveal_reporter', false );
		$reporter = $reveal ? get_bloginfo( 'name' ) . ' (' . home_url() . ')' : '';

		$data = array(
			'email'           => $order->get_billing_email(),
			'first_name'      => $order->get_billing_first_name(),
			'last_name'       => $order->get_billing_last_name(),
			'phone'           => $order->get_billing_phone(),
			'billing_address' => $address,
			'ip_address'      => $order->get_customer_ip_address(),
			'reason'          => $reason,
			'order_id'        => (string) $order_id,
			'notify_customer' => (bool) PepBan_Client_Settings::get( 'notify_customer_on_ban', false ),
			'reporter_name'   => $reporter,
		);

		$result = PepBan_Client_API::report_customer( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Mark order as reported
		$order->update_meta_data( '_pepban_reported', current_time( 'mysql' ) );
		if ( isset( $result['customer_id'] ) ) {
			$order->update_meta_data( '_pepban_customer_id', $result['customer_id'] );
		}
		$order->add_order_note( 'Customer reported to PepBan. Reason: ' . $reason );
		$order->save();

		wp_send_json_success( array(
			'message'     => $result['is_new'] ?? true ? 'Customer added to PepBan.' : 'Existing ban updated.',
			'customer_id' => $result['customer_id'] ?? 0,
			'reported_at' => current_time( 'mysql' ),
		) );
	}
}
