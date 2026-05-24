<?php
defined( 'ABSPATH' ) || exit;
$user        = get_userdata( $client->wp_user_id );
$initials    = strtoupper( substr( $client->owner_name, 0, 1 ) );
$status      = $client->subscription_status;
$portal_url  = get_permalink( get_option( 'pepban_portal_page_id' ) ) ?: get_permalink();
?>
<div class="pepban-public">
<div class="pepban-portal-wrap">

	<?php if ( ! empty( $errors ?? array() ) ) : ?>
	<div class="pepban-alert pepban-alert-error" style="margin-bottom:20px">
		<?php foreach ( $errors as $e ) : ?><p><?php echo esc_html( $e ); ?></p><?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['pepban_registered'] ) ) : ?>
	<div class="pepban-alert pepban-alert-success">
		<strong>Welcome to PepBan!</strong> Your account is set up. Check your email for a copy of your API key and setup instructions.
	</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['pepban_new_key'] ) ) : ?>
	<div class="pepban-alert pepban-alert-info">
		<strong>New key generated.</strong> Your old key no longer works — see below for the new one and update your store settings.
	</div>
	<?php endif; ?>

	<!-- API key reveal (shown only once after sign-up or regen) -->
	<?php if ( $new_key ) : ?>
	<div class="pepban-key-reveal">
		<div class="pepban-key-reveal-header">
			<span class="pepban-key-icon">&#128274;</span>
			<div>
				<h3>Your API Key — Copy It Now</h3>
				<p>This is the <strong>only time</strong> this key will be shown. A copy has been emailed to <?php echo esc_html( $client->owner_email ); ?>.</p>
			</div>
		</div>
		<div class="pepban-key-reveal-body">
			<div class="pepban-key-box">
				<code id="pepban-key-text"><?php echo esc_html( $new_key ); ?></code>
				<button type="button" class="pepban-copy-btn" onclick="pepbanCopyKey(this)">Copy</button>
			</div>
			<p class="pepban-key-note">Enter this in the PepBan Client plugin settings on your store (PepBan &rarr; Settings &rarr; API Key).</p>
		</div>
	</div>
	<script>
	function pepbanCopyKey(btn) {
		var text = document.getElementById('pepban-key-text').textContent;
		function done() { btn.textContent = '✓ Copied!'; btn.classList.add('pepban-copy-done'); }
		if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done); }
		else { var t=document.createElement('textarea'); t.value=text; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); done(); }
	}
	</script>
	<?php endif; ?>

	<!-- Portal header -->
	<div class="pepban-portal-header">
		<div class="pepban-portal-header-left">
			<div class="pepban-portal-avatar"><?php echo esc_html( $initials ); ?></div>
			<div class="pepban-portal-greeting">
				<strong><?php echo esc_html( $client->owner_name ); ?></strong>
				<span><?php echo esc_html( $client->owner_email ); ?></span>
			</div>
		</div>
		<div class="pepban-portal-header-actions">
			<span class="pepban-portal-status pepban-status-<?php echo esc_attr( $status ); ?>">
				<?php echo esc_html( ucfirst( $status ) ); ?>
			</span>
		</div>
	</div>

	<!-- Account info grid -->
	<div class="pepban-portal-grid">
		<div class="pepban-card">
			<div class="pepban-card-header">
				<div class="pepban-card-icon">&#127968;</div>
				<h3>Your Store</h3>
			</div>
			<div class="pepban-card-inner">
				<div class="pepban-info-row">
					<span class="pepban-info-label">URL</span>
					<span class="pepban-info-value"><?php echo esc_html( $client->site_url ); ?></span>
				</div>
				<div class="pepban-info-row">
					<span class="pepban-info-label">Member since</span>
					<span class="pepban-info-value"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $client->date_registered ) ) ); ?></span>
				</div>
			</div>
		</div>

		<div class="pepban-card">
			<div class="pepban-card-header">
				<div class="pepban-card-icon">&#128273;</div>
				<h3>API Key</h3>
			</div>
			<div class="pepban-card-inner">
				<div class="pepban-info-row">
					<span class="pepban-info-label">Key prefix</span>
					<span class="pepban-info-value"><code><?php echo esc_html( $client->api_key_prefix ); ?>&hellip;</code></span>
				</div>
				<div class="pepban-info-row">
					<span class="pepban-info-label">Hub URL</span>
					<span class="pepban-info-value"><code><?php echo esc_html( rtrim( home_url('/'), '/' ) ); ?></code></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Download or pending -->
	<?php if ( 'active' === $status ) : ?>
	<div class="pepban-download-card">
		<h3>&#11015;&nbsp; Download Client Plugin</h3>
		<p>Install this on your WooCommerce store to start blocking banned customers automatically at checkout.</p>
		<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=pepban_download_client' ) ); ?>"
		   class="pepban-btn pepban-btn-download">
			Download pepban-client v<?php echo esc_html( PEPBAN_HUB_VERSION ); ?>.zip
		</a>
	</div>

	<div class="pepban-steps">
		<h4>Setup Guide</h4>
		<ol>
			<li>Download the zip file above</li>
			<li>In your store: go to <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong></li>
			<li>Upload the zip, click <strong>Install Now</strong>, then <strong>Activate</strong></li>
			<li>
				Go to <strong>PepBan &rarr; Settings</strong> and enter:<br>
				<strong>Hub URL:</strong> <code><?php echo esc_html( home_url( '/' ) ); ?></code><br>
				<strong>API Key:</strong> your key from above or from your welcome email
			</li>
			<li>Save &mdash; you should see a green <strong>Connected</strong> status &#10003;</li>
		</ol>
	</div>

	<?php else : ?>
	<div class="pepban-pending-block">
		<span class="pepban-pending-icon">&#9203;</span>
		<h3>Account Pending Activation</h3>
		<p>Your account is awaiting admin approval. You'll get an email the moment it's activated and you can download the plugin.</p>
	</div>
	<?php endif; ?>

	<!-- Regenerate key -->
	<div class="pepban-regen-section">
		<h3>Lost your API key?</h3>
		<p>Generate a new one below. Your current key will stop working immediately — update your store settings with the new key right away.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			  onsubmit="return confirm('This will invalidate your current key immediately. Your store will lose connection until you update it. Continue?')">
			<?php wp_nonce_field( 'pepban_request_new_key' ); ?>
			<input type="hidden" name="action" value="pepban_request_new_key">
			<button type="submit" class="pepban-btn pepban-btn-secondary">Generate New API Key</button>
		</form>
	</div>

	<!-- Footer -->
	<div class="pepban-portal-footer">
		<span>PepBan &copy; <?php echo esc_html( date( 'Y' ) ); ?></span>
		<div class="pepban-portal-footer-links">
			<a href="<?php echo esc_url( wp_lostpassword_url( $portal_url ) ); ?>">Change password</a>
			<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Log out</a>
		</div>
	</div>

</div>
</div>
