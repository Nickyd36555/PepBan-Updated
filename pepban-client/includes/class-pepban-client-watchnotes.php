<?php
defined( 'ABSPATH' ) || exit;

/**
 * Per-customer internal watch notes — not a ban, just a flag for store staff.
 * Notes are stored by email so they work for both guest and registered customers.
 * Shows an orange banner on order edit pages and a field on user profiles.
 */
class PepBan_Client_WatchNotes {

	private static $option = 'pepban_watch_notes';

	public static function init() {
		// Order edit page: banner + meta box
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'show_order_banner' ) );
		add_action( 'add_meta_boxes',                                   array( __CLASS__, 'add_meta_box' ) );

		// User profile page
		add_action( 'show_user_profile',          array( __CLASS__, 'show_profile_field' ) );
		add_action( 'edit_user_profile',          array( __CLASS__, 'show_profile_field' ) );
		add_action( 'personal_options_update',    array( __CLASS__, 'save_profile_field' ) );
		add_action( 'edit_user_profile_update',   array( __CLASS__, 'save_profile_field' ) );

		// AJAX save from order meta box
		add_action( 'wp_ajax_pepban_save_watch_notes', array( __CLASS__, 'ajax_save' ) );
	}

	// ── Storage helpers ───────────────────────────────────────────────────────

	private static function key( string $email ): string {
		return strtolower( trim( $email ) );
	}

	public static function get( string $email ): array {
		$all = get_option( self::$option, array() );
		return $all[ self::key( $email ) ] ?? array();
	}

	public static function save( string $email, string $notes, string $saved_by ): void {
		$all                          = get_option( self::$option, array() );
		$k                            = self::key( $email );
		if ( $notes === '' ) {
			unset( $all[ $k ] );
		} else {
			$all[ $k ] = array(
				'notes'      => $notes,
				'updated'    => current_time( 'mysql' ),
				'updated_by' => $saved_by,
			);
		}
		update_option( self::$option, $all, false );
	}

	// ── Order edit page banner ────────────────────────────────────────────────

	public static function show_order_banner( $order ) {
		$email = $order->get_billing_email();
		if ( ! $email ) return;

		$entry = self::get( $email );
		if ( empty( $entry['notes'] ) ) return;
		?>
		<div class="notice notice-warning" style="margin:10px 0;padding:14px 16px;border-left-color:#f59e0b">
			<strong>&#128064; PepBan Watch Note</strong>
			<p style="margin:8px 0 4px;white-space:pre-wrap"><?php echo esc_html( $entry['notes'] ); ?></p>
			<p style="margin:4px 0;font-size:11px;color:#888">
				Last updated <?php echo esc_html( $entry['updated'] ?? '' ); ?>
				by <?php echo esc_html( $entry['updated_by'] ?? '' ); ?>
			</p>
		</div>
		<?php
	}

	// ── Order edit page meta box ──────────────────────────────────────────────

	public static function add_meta_box() {
		$screens = function_exists( 'wc_get_page_screen_id' )
			? array( 'woocommerce_page_wc-orders', 'shop_order' )
			: array( 'shop_order' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'pepban-watch-notes',
				'PepBan — Watch Notes',
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

		$email = $order->get_billing_email();
		$entry = $email ? self::get( $email ) : array();
		$notes = $entry['notes'] ?? '';
		$nonce = wp_create_nonce( 'pepban_watch_notes_' . $order_id );
		?>
		<div id="pepban-watch-notes-box">
			<p style="margin:0 0 6px;font-size:12px;color:#666">
				Internal only — not visible to customers.<br>
				Shared across all orders for <strong><?php echo esc_html( $email ); ?></strong>.
			</p>
			<textarea id="pepban-watch-notes-text" rows="5"
				style="width:100%;margin:0 0 8px;border-color:<?php echo $notes ? '#f59e0b' : '#ddd'; ?>"
				placeholder="e.g. Claims non-delivery on orders #123 and #456 — monitoring…"><?php echo esc_textarea( $notes ); ?></textarea>
			<?php if ( ! empty( $entry['updated'] ) ) : ?>
			<p style="margin:0 0 6px;font-size:11px;color:#888">
				Saved <?php echo esc_html( $entry['updated'] ); ?> by <?php echo esc_html( $entry['updated_by'] ); ?>
			</p>
			<?php endif; ?>
			<button type="button" class="button pepban-save-watch-notes-btn" style="width:100%"
				data-order-id="<?php echo esc_attr( $order_id ); ?>"
				data-email="<?php echo esc_attr( $email ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>">
				Save Watch Note
			</button>
			<span id="pepban-watch-notes-status" style="display:none;font-size:12px;margin-top:6px;display:block"></span>
		</div>
		<?php
	}

	// ── AJAX save from order meta box ─────────────────────────────────────────

	public static function ajax_save() {
		$order_id = absint( $_POST['order_id'] ?? 0 );

		if ( ! $order_id ) {
			wp_send_json_error( 'Invalid request.' );
		}

		if ( ! check_ajax_referer( 'pepban_watch_notes_' . $order_id, 'nonce', false ) ) {
			wp_send_json_error( 'Security check failed.' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		// Derive email from order rather than trusting $_POST
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Order not found.' );
		}
		$email = $order->get_billing_email();
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

		if ( ! $email ) {
			wp_send_json_error( 'No billing email on order.' );
		}

		$saved_by = wp_get_current_user()->user_login;
		self::save( $email, $notes, $saved_by );

		wp_send_json_success( array(
			'message' => $notes ? 'Watch note saved.' : 'Watch note cleared.',
			'updated' => current_time( 'mysql' ),
			'by'      => $saved_by,
		) );
	}

	// ── User profile field ────────────────────────────────────────────────────

	public static function show_profile_field( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;

		$email = $user->user_email;
		$entry = self::get( $email );
		$notes = $entry['notes'] ?? '';
		?>
		<h2>PepBan Watch Notes</h2>
		<table class="form-table">
			<tr>
				<th><label for="pepban_watch_notes">Internal Watch Note</label></th>
				<td>
					<textarea name="pepban_watch_notes" id="pepban_watch_notes" rows="5" cols="30"
						class="large-text" style="border-color:<?php echo $notes ? '#f59e0b' : ''; ?>"
						placeholder="Internal notes about this customer — not visible to them."><?php echo esc_textarea( $notes ); ?></textarea>
					<?php if ( ! empty( $entry['updated'] ) ) : ?>
					<p class="description">
						Last saved <?php echo esc_html( $entry['updated'] ); ?> by <?php echo esc_html( $entry['updated_by'] ); ?>
					</p>
					<?php else : ?>
					<p class="description">Not visible to the customer. Shared across all their orders.</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save_profile_field( $user_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		if ( ! isset( $_POST['pepban_watch_notes'] ) ) return;
		if ( ! check_admin_referer( 'update-user_' . $user_id ) ) return;

		$user  = get_user_by( 'id', $user_id );
		$email = $user ? $user->user_email : '';
		if ( ! $email ) return;

		$notes    = sanitize_textarea_field( wp_unslash( $_POST['pepban_watch_notes'] ) );
		$saved_by = wp_get_current_user()->user_login;
		self::save( $email, $notes, $saved_by );
	}
}
