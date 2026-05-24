<?php defined( 'ABSPATH' ) || exit; ?>
<div class="pepban-portal-wrap">

	<?php if ( isset( $_GET['pepban_registered'] ) ) : ?>
	<div class="pepban-alert pepban-alert-success">
		<strong>&#10003; Account created!</strong> Check your email for your API key and setup instructions.
	</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['pepban_new_key'] ) ) : ?>
	<div class="pepban-alert pepban-alert-info">
		<strong>New key generated.</strong> See below and check your email.
	</div>
	<?php endif; ?>

	<?php if ( $new_key ) : ?>
	<div class="pepban-key-reveal">
		<h3>&#128274; Your API Key &mdash; Copy Now</h3>
		<p><strong>This is the only time this key will be displayed.</strong>
		   Copy it and paste it into the PepBan Client plugin settings on your store.</p>
		<div class="pepban-key-box">
			<code id="pepban-key-text"><?php echo esc_html( $new_key ); ?></code>
			<button type="button" class="pepban-copy-btn" onclick="pepbanCopyKey(this)">Copy</button>
		</div>
		<p class="pepban-key-note">A copy has also been sent to <strong><?php echo esc_html( $client->owner_email ); ?></strong>.</p>
	</div>
	<script>
	function pepbanCopyKey(btn) {
		var text = document.getElementById('pepban-key-text').textContent;
		if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(function() {
				btn.textContent = '✓ Copied!';
				btn.classList.add('pepban-copy-done');
			});
		} else {
			// Fallback for older browsers
			var el = document.createElement('textarea');
			el.value = text;
			document.body.appendChild(el);
			el.select();
			document.execCommand('copy');
			document.body.removeChild(el);
			btn.textContent = '✓ Copied!';
		}
	}
	</script>
	<?php endif; ?>

	<div class="pepban-portal-card">
		<h2>Your PepBan Portal</h2>
		<table class="pepban-info-table">
			<tr>
				<th>Name</th>
				<td><?php echo esc_html( $client->owner_name ); ?></td>
			</tr>
			<tr>
				<th>Email</th>
				<td><?php echo esc_html( $client->owner_email ); ?></td>
			</tr>
			<tr>
				<th>Store URL</th>
				<td><?php echo esc_html( $client->site_url ); ?></td>
			</tr>
			<tr>
				<th>API Key</th>
				<td><code><?php echo esc_html( $client->api_key_prefix ); ?>&hellip;</code>
					<span class="pepban-key-hint">(first 8 characters shown for verification)</span>
				</td>
			</tr>
			<tr>
				<th>Status</th>
				<td>
					<span class="pepban-badge pepban-badge-<?php echo esc_attr( $client->subscription_status ); ?>">
						<?php echo esc_html( ucfirst( $client->subscription_status ) ); ?>
					</span>
					<?php if ( 'pending' === $client->subscription_status ) : ?>
						<span class="pepban-hint"> &mdash; awaiting admin approval</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>Member Since</th>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $client->date_registered ) ) ); ?></td>
			</tr>
		</table>
	</div>

	<?php if ( 'active' === $client->subscription_status ) : ?>
	<div class="pepban-section pepban-download-section">
		<h3>&#11015; Download Client Plugin</h3>
		<p>Install this plugin on your WooCommerce store to start blocking banned customers automatically.</p>
		<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=pepban_download_client' ) ); ?>"
		   class="pepban-btn pepban-btn-download">
			Download PepBan Client (v<?php echo esc_html( PEPBAN_HUB_VERSION ); ?>)
		</a>

		<div class="pepban-setup-guide">
			<h4>Installation Steps</h4>
			<ol>
				<li>Click the download button above to get <strong>pepban-client-<?php echo esc_html( PEPBAN_HUB_VERSION ); ?>.zip</strong></li>
				<li>In your WooCommerce store admin go to <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong></li>
				<li>Upload the zip file and click <strong>Install Now</strong>, then <strong>Activate</strong></li>
				<li>Go to <strong>PepBan &rarr; Settings</strong> and enter:
					<ul>
						<li><strong>Hub URL:</strong> <code><?php echo esc_html( home_url( '/' ) ); ?></code></li>
						<li><strong>API Key:</strong> your key from above (or from the welcome email)</li>
					</ul>
				</li>
				<li>Click <strong>Save Settings</strong> &mdash; you should see a green &ldquo;Connected&rdquo; status</li>
			</ol>
		</div>
	</div>
	<?php else : ?>
	<div class="pepban-alert pepban-alert-warning">
		<strong>Account Pending.</strong> Your account is awaiting activation. You will receive an email once it is approved and you can download the plugin.
	</div>
	<?php endif; ?>

	<div class="pepban-section pepban-key-actions">
		<h3>Lost Your API Key?</h3>
		<p>Generate a new API key below. Your existing key will stop working immediately &mdash; update the plugin settings on your store with the new key.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			  onsubmit="return confirm('This will invalidate your current API key. Your store will stop connecting to PepBan until you update the settings with the new key. Continue?')">
			<?php wp_nonce_field( 'pepban_request_new_key' ); ?>
			<input type="hidden" name="action" value="pepban_request_new_key">
			<button type="submit" class="pepban-btn pepban-btn-secondary">Generate New API Key</button>
		</form>
	</div>

	<div class="pepban-portal-footer">
		<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Log out</a>
		&nbsp;&bull;&nbsp;
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Change password</a>
	</div>

</div>
