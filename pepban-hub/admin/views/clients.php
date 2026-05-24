<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>PepBan Clients
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients&action=add' ) ); ?>" class="page-title-action">+ Add Client</a>
	</h1>

	<?php if ( 'add' === $action ) : ?>
	<div class="pepban-card">
		<h2>Create New Client</h2>
		<p>Creates a client record and generates an API key. Use this for manually onboarded clients or testing.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pepban_hub_action' ); ?>
			<input type="hidden" name="action" value="pepban_hub_action">
			<input type="hidden" name="pepban_action" value="create_client">
			<table class="form-table">
				<tr><th><label for="site_url">Site URL *</label></th>
					<td><input type="url" name="site_url" id="site_url" class="regular-text" placeholder="https://their-peptide-site.com" required></td></tr>
				<tr><th><label for="owner_email">Owner Email *</label></th>
					<td><input type="email" name="owner_email" id="owner_email" class="regular-text" required></td></tr>
				<tr><th><label for="owner_name">Owner Name</label></th>
					<td><input type="text" name="owner_name" id="owner_name" class="regular-text"></td></tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary">Create Client &amp; Generate Key</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients' ) ); ?>" class="button">Cancel</a>
			</p>
		</form>
	</div>

	<?php else : ?>
	<table class="wp-list-table widefat fixed striped pepban-table">
		<thead>
			<tr>
				<th>ID</th>
				<th>Site URL</th>
				<th>Owner</th>
				<th>Key Prefix</th>
				<th>Status</th>
				<th>Registered</th>
				<th>Last Active</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $clients ) ) : ?>
			<tr><td colspan="8">No clients yet.</td></tr>
		<?php else : ?>
			<?php foreach ( $clients as $c ) : ?>
			<tr>
				<td><?php echo esc_html( $c->id ); ?></td>
				<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $c->id ) ); ?>"><?php echo esc_html( $c->site_url ); ?></a></td>
				<td><?php echo esc_html( $c->owner_name ?: $c->owner_email ); ?></td>
				<td><code><?php echo esc_html( $c->api_key_prefix ); ?>…</code></td>
				<td><span class="pepban-status pepban-status-<?php echo esc_attr( $c->subscription_status ); ?>"><?php echo esc_html( ucfirst( $c->subscription_status ) ); ?></span></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $c->date_registered ) ) ); ?></td>
				<td><?php echo $c->last_active ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $c->last_active ) ) ) : '—'; ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients&action=view&id=' . $c->id ) ); ?>">Manage</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
