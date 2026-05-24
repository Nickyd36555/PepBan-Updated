<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>PepBan Hub Settings</h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'pepban_hub_action' ); ?>
		<input type="hidden" name="action" value="pepban_hub_action">
		<input type="hidden" name="pepban_action" value="save_settings">

		<table class="form-table">
			<tr>
				<th><label for="rate_limit_per_min">API Rate Limit (requests/minute per client)</label></th>
				<td>
					<input type="number" name="rate_limit_per_min" id="rate_limit_per_min" value="<?php echo esc_attr( $settings['rate_limit_per_min'] ?? 60 ); ?>" min="1" max="600" class="small-text">
					<p class="description">Default: 60. Clients exceeding this limit receive a 429 response.</p>
				</td>
			</tr>
			<tr>
				<th><label for="require_https">Require HTTPS</label></th>
				<td>
					<label>
						<input type="checkbox" name="require_https" id="require_https" value="1" <?php checked( $settings['require_https'] ?? true ); ?>>
						Warn in the dashboard if the hub site is not served over HTTPS
					</label>
				</td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary">Save Settings</button></p>
	</form>

	<hr>
	<h2>API Endpoint Reference</h2>
	<table class="widefat" style="max-width:700px">
		<thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
		<tbody>
			<tr><td>GET</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/status' ) ); ?></code></td><td>Verify key &amp; subscription</td></tr>
			<tr><td>POST</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/check' ) ); ?></code></td><td>Check if customer is banned</td></tr>
			<tr><td>POST</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/report' ) ); ?></code></td><td>Report a banned customer</td></tr>
			<tr><td>POST</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/whitelist/add' ) ); ?></code></td><td>Whitelist for this site</td></tr>
			<tr><td>POST</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/whitelist/remove' ) ); ?></code></td><td>Remove from whitelist</td></tr>
			<tr><td>GET</td><td><code><?php echo esc_html( rest_url( 'pepban/v1/whitelist' ) ); ?></code></td><td>List whitelisted customers</td></tr>
		</tbody>
	</table>
	<p><strong>Auth header:</strong> <code>X-PepBan-API-Key: pbk_…</code></p>
</div>
