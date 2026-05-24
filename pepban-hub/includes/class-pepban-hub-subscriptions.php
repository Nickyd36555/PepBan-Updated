<?php
defined( 'ABSPATH' ) || exit;

/**
 * Bridges WooCommerce Subscriptions events to PepBan client activation/deactivation.
 *
 * Expects a product/subscription whose metadata or order meta includes:
 *   pepban_site_url   – the client's WordPress site URL
 *   pepban_owner_name – (optional) contact name
 *
 * When a subscription is activated the first time we create the client record
 * and email the plaintext API key to the subscriber.
 */
class PepBan_Hub_Subscriptions {

	public static function init() {
		// WooCommerce Subscriptions status hooks
		add_action( 'woocommerce_subscription_status_active',    array( __CLASS__, 'on_activated' ) );
		add_action( 'woocommerce_subscription_status_cancelled', array( __CLASS__, 'on_deactivated' ) );
		add_action( 'woocommerce_subscription_status_expired',   array( __CLASS__, 'on_deactivated' ) );
		add_action( 'woocommerce_subscription_status_on-hold',   array( __CLASS__, 'on_deactivated' ) );
		add_action( 'woocommerce_subscription_status_suspended', array( __CLASS__, 'on_deactivated' ) );

		// Renewal payment completed
		add_action( 'woocommerce_subscription_renewal_payment_complete', array( __CLASS__, 'on_renewal' ) );

		// Admin: allow manual API key display / regeneration
		add_action( 'woocommerce_subscription_details_after_subscription_table', array( __CLASS__, 'show_api_key_box' ) );
	}

	public static function on_activated( $subscription ) {
		$subscription_id = $subscription->get_id();
		$owner_email     = $subscription->get_billing_email();
		$owner_name      = trim( $subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name() );
		$site_url        = $subscription->get_meta( 'pepban_site_url' );

		if ( empty( $site_url ) ) {
			// Try order meta from the parent order
			$parent = $subscription->get_parent();
			if ( $parent ) {
				$site_url = $parent->get_meta( 'pepban_site_url' );
			}
		}

		if ( empty( $site_url ) ) return;

		// Check if client already exists for this subscription
		$existing = PepBan_Hub_Clients::get_client_by_woo_subscription( $subscription_id );
		if ( $existing ) {
			// Just re-activate
			PepBan_Hub_Clients::activate( $existing->id, $subscription_id );
			return;
		}

		// Also check by email (handles edge case where subscription ID changed)
		$existing = PepBan_Hub_Clients::get_client_by_owner_email( $owner_email );
		if ( $existing ) {
			PepBan_Hub_Clients::activate( $existing->id, $subscription_id );
			return;
		}

		// Brand-new subscriber — create client record
		$result = PepBan_Hub_Clients::create( $site_url, $owner_email, $owner_name );
		PepBan_Hub_Clients::activate( $result['id'], $subscription_id );

		// Email the raw API key (shown only once)
		self::email_api_key( $owner_email, $owner_name, $site_url, $result['api_key'] );

		// Store a flag on the subscription so we know the key was issued
		$subscription->update_meta_data( 'pepban_client_id', $result['id'] );
		$subscription->save();
	}

	public static function on_deactivated( $subscription ) {
		$subscription_id = $subscription->get_id();
		$client          = PepBan_Hub_Clients::get_client_by_woo_subscription( $subscription_id );
		if ( $client ) {
			PepBan_Hub_Clients::deactivate( $client->id );
		}
	}

	public static function on_renewal( $subscription ) {
		self::on_activated( $subscription );
	}

	private static function email_api_key( $to, $name, $site_url, $api_key ) {
		$subject = 'Your PepBan API Key';
		$message = sprintf(
			"Hi %s,\n\nYour PepBan subscription for %s is now active.\n\n" .
			"Your API key (save this — it will not be shown again):\n\n%s\n\n" .
			"Install the PepBan Client plugin on %s and paste this key in the plugin settings.\n\n" .
			"If you lose your key, contact support to have it regenerated.\n\nThank you,\nPepBan",
			$name, $site_url, $api_key, $site_url
		);
		wp_mail( $to, $subject, $message );
	}

	/**
	 * Show client info box on the WooCommerce subscription admin screen.
	 */
	public static function show_api_key_box( $subscription ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		$client_id = $subscription->get_meta( 'pepban_client_id' );
		if ( ! $client_id ) return;
		$client = PepBan_Hub_Database::get_client( $client_id );
		if ( ! $client ) return;
		?>
		<div class="woocommerce-order-data pepban-admin-box" style="margin-top:20px;padding:12px;border:1px solid #ddd;background:#f9f9f9;">
			<h3 style="margin-top:0">PepBan Client</h3>
			<p><strong>Client ID:</strong> <?php echo esc_html( $client->id ); ?></p>
			<p><strong>Site:</strong> <?php echo esc_html( $client->site_url ); ?></p>
			<p><strong>API Key Prefix:</strong> <code><?php echo esc_html( $client->api_key_prefix ); ?>…</code></p>
			<p><strong>Status:</strong> <?php echo esc_html( $client->subscription_status ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $client->id ) ); ?>">
					View in PepBan Dashboard
				</a>
			</p>
		</div>
		<?php
	}
}
