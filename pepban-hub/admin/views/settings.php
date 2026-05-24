<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>PepBan Hub Settings</h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'pepban_hub_action' ); ?>
		<input type="hidden" name="action" value="pepban_hub_action">
		<input type="hidden" name="pepban_action" value="save_settings">

		<h2>Sign-Up &amp; Portal Pages</h2>
		<p>Create two WordPress pages, assign the shortcodes, then select them here. Or use the button below to create them automatically.</p>

		<table class="form-table">
			<tr>
				<th><label for="signup_page_id">Sign-Up Page</label></th>
				<td>
					<?php
					wp_dropdown_pages( array(
						'name'              => 'signup_page_id',
						'id'                => 'signup_page_id',
						'selected'          => get_option( 'pepban_signup_page_id', 0 ),
						'show_option_none'  => '— Select page —',
						'option_none_value' => 0,
					) );
					?>
					<p class="description">This page should contain the shortcode <code>[pepban_signup]</code></p>
				</td>
			</tr>
			<tr>
				<th><label for="portal_page_id">Client Portal Page</label></th>
				<td>
					<?php
					wp_dropdown_pages( array(
						'name'              => 'portal_page_id',
						'id'                => 'portal_page_id',
						'selected'          => get_option( 'pepban_portal_page_id', 0 ),
						'show_option_none'  => '— Select page —',
						'option_none_value' => 0,
					) );
					?>
					<p class="description">This page should contain the shortcode <code>[pepban_portal]</code></p>
				</td>
			</tr>
		</table>

		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pepban_hub_action&pepban_action=create_pages' ), 'pepban_hub_action' ) ); ?>"
			   class="button"
			   onclick="return confirm('Create a Sign-Up page and a Portal page automatically?')">
				Auto-Create Sign-Up &amp; Portal Pages
			</a>
		</p>

		<h2>Client Approval</h2>
		<table class="form-table">
			<tr>
				<th>Auto-Approve Sign-Ups</th>
				<td>
					<label>
						<input type="checkbox" name="auto_approve_clients" value="1" <?php checked( $settings['auto_approve_clients'] ?? false ); ?>>
						Automatically activate new clients immediately after sign-up
					</label>
					<p class="description">If unchecked, new accounts are set to <em>pending</em> and you activate them manually from the Clients page.</p>
				</td>
			</tr>
		</table>

		<h2>API Settings</h2>
		<table class="form-table">
			<tr>
				<th><label for="rate_limit_per_min">Rate Limit (requests/minute per client)</label></th>
				<td>
					<input type="number" name="rate_limit_per_min" id="rate_limit_per_min"
						value="<?php echo esc_attr( $settings['rate_limit_per_min'] ?? 60 ); ?>"
						min="1" max="600" class="small-text">
					<p class="description">Default: 60. Clients exceeding this receive a 429 response.</p>
				</td>
			</tr>
		</table>

		<h2>Plugin Download</h2>
		<table class="form-table">
			<tr>
				<th><label for="client_plugin_path">Client Plugin Path</label></th>
				<td>
					<input type="text" name="client_plugin_path" id="client_plugin_path"
						value="<?php echo esc_attr( $settings['client_plugin_path'] ?? '' ); ?>"
						class="regular-text"
						placeholder="<?php echo esc_attr( WP_PLUGIN_DIR . '/pepban-client/' ); ?>">
					<p class="description">
						Absolute path to the <code>pepban-client</code> folder on this server.
						Leave blank to use the default location (<code><?php echo esc_html( WP_PLUGIN_DIR . '/pepban-client/' ); ?></code>).
					</p>
					<?php
					$candidates = array(
						WP_PLUGIN_DIR . '/pepban-client/',
						PEPBAN_HUB_DIR . 'client-dist/pepban-client/',
					);
					$found = false;
					foreach ( $candidates as $c ) {
						if ( is_dir( $c ) && file_exists( $c . 'pepban-client.php' ) ) {
							$found = $c;
							break;
						}
					}
					if ( $found ) {
						echo '<p class="description" style="color:#00a32a">&#10003; Client plugin files found at: <code>' . esc_html( $found ) . '</code></p>';
					} else {
						echo '<p class="description" style="color:#d63638">&#10007; Client plugin files not found. Place the <code>pepban-client</code> folder at <code>' . esc_html( WP_PLUGIN_DIR ) . '/pepban-client/</code> or set a custom path above.</p>';
					}
					?>
				</td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary">Save Settings</button></p>
	</form>

	<hr>
	<h2>Shortcodes</h2>
	<table class="widefat" style="max-width:500px;margin-bottom:24px">
		<thead><tr><th>Shortcode</th><th>Purpose</th></tr></thead>
		<tbody>
			<tr><td><code>[pepban_signup]</code></td><td>Public sign-up form &amp; portal (auto-switches based on login state)</td></tr>
			<tr><td><code>[pepban_portal]</code></td><td>Client portal only (for a separate portal page)</td></tr>
		</tbody>
	</table>

	<h2>REST API Reference</h2>
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
