<?php defined( 'ABSPATH' ) || exit;
if ( ! $client ) {
	echo '<div class="wrap"><p>Client not found. <a href="' . esc_url( admin_url( 'admin.php?page=pepban-hub-clients' ) ) . '">Back</a></p></div>';
	return;
}
$new_key = '';
if ( isset( $_GET['new_client'] ) ) {
	$new_key = get_transient( 'pepban_new_api_key_' . $client->id );
	if ( $new_key ) delete_transient( 'pepban_new_api_key_' . $client->id );
}
?>
<div class="wrap pepban-wrap">
	<h1>
		Client: <?php echo esc_html( $client->site_url ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients' ) ); ?>" class="page-title-action">&larr; Back</a>
	</h1>

	<?php if ( $new_key ) : ?>
	<div class="notice notice-warning" style="padding:16px">
		<h3 style="margin-top:0">&#128274; API Key — Copy Now</h3>
		<p>This key will <strong>never be shown again</strong>. Copy it and give it to the client.</p>
		<code style="font-size:16px;display:block;padding:10px;background:#fff;border:1px solid #ccc;word-break:break-all"><?php echo esc_html( $new_key ); ?></code>
	</div>
	<?php endif; ?>

	<div class="pepban-detail-grid">
		<div class="pepban-card">
			<h2>Client Info</h2>
			<table class="form-table">
				<tr><th>ID</th><td><?php echo esc_html( $client->id ); ?></td></tr>
				<tr><th>Site URL</th><td><?php echo esc_html( $client->site_url ); ?></td></tr>
				<tr><th>Owner</th><td><?php echo esc_html( $client->owner_name ); ?></td></tr>
				<tr><th>Owner Email</th><td><?php echo esc_html( $client->owner_email ); ?></td></tr>
				<tr><th>API Key Prefix</th><td><code><?php echo esc_html( $client->api_key_prefix ); ?>…</code></td></tr>
				<tr><th>Subscription</th><td><span class="pepban-status pepban-status-<?php echo esc_attr( $client->subscription_status ); ?>"><?php echo esc_html( ucfirst( $client->subscription_status ) ); ?></span></td></tr>
				<tr><th>WooCommerce Sub ID</th><td><?php echo $client->woo_subscription_id ? esc_html( $client->woo_subscription_id ) : '—'; ?></td></tr>
				<tr><th>Registered</th><td><?php echo esc_html( $client->date_registered ); ?></td></tr>
				<tr><th>Last API Call</th><td><?php echo $client->last_active ? esc_html( $client->last_active ) : '—'; ?></td></tr>
			</table>
		</div>

		<div class="pepban-card pepban-card-actions">
			<h2>Actions</h2>

			<?php if ( 'active' !== $client->subscription_status ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="activate_client">
				<input type="hidden" name="client_id" value="<?php echo esc_attr( $client->id ); ?>">
				<button type="submit" class="button button-primary">Activate Subscription</button>
			</form>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="deactivate_client">
				<input type="hidden" name="client_id" value="<?php echo esc_attr( $client->id ); ?>">
				<button type="submit" class="button button-secondary">Deactivate Subscription</button>
			</form>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px" onsubmit="return confirm('This will invalidate the current API key immediately. The client will need the new key to continue using PepBan.')">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="regenerate_key">
				<input type="hidden" name="client_id" value="<?php echo esc_attr( $client->id ); ?>">
				<button type="submit" class="button">Regenerate API Key</button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete this client? Their whitelist entries will also be removed.')">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="delete_client">
				<input type="hidden" name="client_id" value="<?php echo esc_attr( $client->id ); ?>">
				<button type="submit" class="button button-link-delete">Delete Client</button>
			</form>
		</div>
	</div>

	<?php if ( ! empty( $whitelist ) ) : ?>
	<div class="pepban-card" style="margin-top:20px">
		<h2>Site Whitelist (<?php echo count( $whitelist ); ?> entries)</h2>
		<p>These banned customers are allowed to order on this site. They remain in the global ban list.</p>
		<table class="wp-list-table widefat fixed striped pepban-table">
			<thead>
				<tr><th>Email</th><th>Name</th><th>Notes</th><th>Whitelisted On</th></tr>
			</thead>
			<tbody>
			<?php foreach ( $whitelist as $w ) : ?>
				<tr>
					<td><?php echo esc_html( $w->email ); ?></td>
					<td><?php echo esc_html( $w->first_name . ' ' . $w->last_name ); ?></td>
					<td><?php echo esc_html( $w->notes ); ?></td>
					<td><?php echo esc_html( $w->date_added ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
