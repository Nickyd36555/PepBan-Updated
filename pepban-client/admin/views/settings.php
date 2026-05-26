<?php defined( 'ABSPATH' ) || exit;
$connection_status = PepBan_Client_Settings::test_connection();
?>
<div class="wrap pepban-wrap">
	<h1>PepBan Settings</h1>

	<div class="pepban-connection-status pepban-connection-<?php echo esc_attr( $connection_status ); ?>">
		<?php if ( 'connected' === $connection_status ) : ?>
			&#10003; Connected to PepBan Hub
		<?php elseif ( PepBan_Client_Settings::is_configured() ) :
			$last_error = get_option( 'pepban_last_connection_error', '' ); ?>
			&#10007; Cannot reach hub — <?php echo esc_html( $last_error ?: 'check API key' ); ?>
		<?php else : ?>
			&#9888; Not configured — enter your hub URL and API key below
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'pepban_client_save_settings' ); ?>
		<input type="hidden" name="action" value="pepban_client_save_settings">

		<h2>Connection</h2>
		<table class="form-table">
			<tr>
				<th><label for="api_key">API Key *</label></th>
				<td>
					<input type="password" name="api_key" id="api_key" class="regular-text"
						value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>"
						placeholder="pbk_…" autocomplete="off">
					<p class="description">Your API key from your <a href="https://pepban.com/portal" target="_blank">PepBan portal</a>.</p>
				</td>
			</tr>
		</table>

		<h2>Checkout Behavior</h2>
		<table class="form-table">
			<tr>
				<th>Check by Email</th>
				<td><label><input type="checkbox" name="check_email" value="1" <?php checked( $settings['check_email'] ?? true ); ?>> Check customer email against the ban database</label></td>
			</tr>
			<tr>
				<th>Check by Phone</th>
				<td><label><input type="checkbox" name="check_phone" value="1" <?php checked( $settings['check_phone'] ?? true ); ?>> Check customer phone number against the ban database</label></td>
			</tr>
			<tr>
				<th>Check IP Address</th>
				<td><label><input type="checkbox" name="check_ip" value="1" <?php checked( $settings['check_ip'] ?? true ); ?>> Check customer IP against global and local IP block lists</label></td>
			</tr>
			<tr>
				<th>Check Billing Address</th>
				<td><label><input type="checkbox" name="check_billing_address" value="1" <?php checked( $settings['check_billing_address'] ?? true ); ?>> Check billing address against the local blacklist and whitelist</label></td>
			</tr>
			<tr>
				<th>Block Banned Customers</th>
				<td><label><input type="checkbox" name="block_on_ban" value="1" <?php checked( $settings['block_on_ban'] ?? true ); ?>> Prevent banned customers from completing checkout</label></td>
			</tr>
			<tr>
				<th><label for="block_message">Block Message</label></th>
				<td>
					<textarea name="block_message" id="block_message" rows="3" class="regular-text"><?php echo esc_textarea( $settings['block_message'] ?? '' ); ?></textarea>
					<p class="description">Message shown to blocked customers. Leave blank for the default.</p>
				</td>
			</tr>
		</table>

		<h2>Admin Notices</h2>
		<table class="form-table">
			<tr>
				<th>Show Ban Warning on Orders</th>
				<td><label><input type="checkbox" name="show_ban_notice_admin" value="1" <?php checked( $settings['show_ban_notice_admin'] ?? true ); ?>> Show a red alert on the order edit screen when the customer is in the ban database</label></td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary">Save Settings</button></p>
	</form>

	<hr>
	<h2>Plugin Updates</h2>
	<p>Current version: <strong><?php echo esc_html( PEPBAN_CLIENT_VERSION ); ?></strong><?php
		$info = get_transient( 'pepban_plugin_update_info' );
		if ( false === $info ) {
			// Transient expired — fetch live so the status is always accurate
			$info = PepBan_Client_API::get( '/plugin/info' );
			if ( ! is_wp_error( $info ) && ! empty( $info['version'] ) ) {
				set_transient( 'pepban_plugin_update_info', $info, 6 * HOUR_IN_SECONDS );
			} else {
				$info = array();
			}
		}
		if ( ! empty( $info['version'] ) && version_compare( $info['version'], PEPBAN_CLIENT_VERSION, '>' ) ) {
			echo ' &mdash; <span style="color:#d63638">Version ' . esc_html( $info['version'] ) . ' available — <a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">update now</a></span>';
		} else {
			echo ' &mdash; <span style="color:#00a32a">Up to date</span>';
		}
	?></p>
	<?php if ( ! empty( $_GET['pepban_update_checked'] ) ) : ?>
		<div class="notice notice-success inline"><p>Update check complete.</p></div>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'pepban_force_update_check' ); ?>
		<input type="hidden" name="action" value="pepban_client_check_for_update">
		<button type="submit" class="button">Check for Updates</button>
	</form>
</div>
