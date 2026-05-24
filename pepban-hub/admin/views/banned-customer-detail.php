<?php defined( 'ABSPATH' ) || exit;
if ( ! $customer ) {
	echo '<div class="wrap"><p>Customer not found. <a href="' . esc_url( admin_url( 'admin.php?page=pepban-hub-banned' ) ) . '">Back</a></p></div>';
	return;
}
?>
<div class="wrap pepban-wrap">
	<h1>
		Banned Customer: <?php echo esc_html( $customer->email ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned' ) ); ?>" class="page-title-action">&larr; Back</a>
	</h1>

	<div class="pepban-detail-grid">
		<div class="pepban-card">
			<h2>Customer Details</h2>
			<table class="form-table">
				<tr><th>Email</th><td><?php echo esc_html( $customer->email ); ?></td></tr>
				<tr><th>Name</th><td><?php echo esc_html( $customer->first_name . ' ' . $customer->last_name ); ?></td></tr>
				<tr><th>Phone</th><td><?php echo esc_html( $customer->phone ); ?></td></tr>
				<tr><th>Billing Address</th><td><?php echo nl2br( esc_html( $customer->billing_address ) ); ?></td></tr>
				<tr><th>IP Address</th><td><?php echo esc_html( $customer->ip_address ); ?></td></tr>
				<tr><th>First Reported By</th><td><?php echo esc_html( $customer->reported_by_site ); ?></td></tr>
				<tr><th>Date Added</th><td><?php echo esc_html( $customer->date_added ); ?></td></tr>
				<tr><th>Last Updated</th><td><?php echo esc_html( $customer->last_updated ); ?></td></tr>
				<tr><th>Total Reports</th><td><?php echo esc_html( $customer->reports_count ); ?></td></tr>
				<tr><th>Status</th><td><span class="pepban-status pepban-status-<?php echo esc_attr( $customer->status ); ?>"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span></td></tr>
				<tr><th>Reason</th><td><?php echo nl2br( esc_html( $customer->reason ) ); ?></td></tr>
				<?php if ( $customer->admin_notes ) : ?>
				<tr><th>Admin Notes</th><td><?php echo nl2br( esc_html( $customer->admin_notes ) ); ?></td></tr>
				<?php endif; ?>
			</table>
		</div>

		<div class="pepban-card pepban-card-actions">
			<h2>Actions</h2>
			<p><em>Only you (the admin) can change or remove bans.</em></p>

			<!-- Status Change -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:12px">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="update_customer_status">
				<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>">
				<select name="status">
					<option value="active"   <?php selected( $customer->status, 'active' ); ?>>Active (blocked)</option>
					<option value="inactive" <?php selected( $customer->status, 'inactive' ); ?>>Inactive (unblocked)</option>
					<option value="pending"  <?php selected( $customer->status, 'pending' ); ?>>Pending review</option>
				</select>
				<button type="submit" class="button button-secondary">Update Status</button>
			</form>

			<!-- Permanent Delete (admin only) -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Permanently delete this customer from the database? This cannot be undone.')">
				<?php wp_nonce_field( 'pepban_hub_action' ); ?>
				<input type="hidden" name="action" value="pepban_hub_action">
				<input type="hidden" name="pepban_action" value="delete_customer">
				<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>">
				<button type="submit" class="button button-link-delete">Permanently Remove from Database</button>
			</form>
		</div>
	</div>

	<div class="pepban-card" style="margin-top:20px">
		<h2>Report History (<?php echo count( $reports ); ?> reports)</h2>
		<?php if ( empty( $reports ) ) : ?>
			<p>No individual reports found.</p>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped pepban-table">
			<thead>
				<tr>
					<th>Site</th>
					<th>Client</th>
					<th>Reason</th>
					<th>Order ID</th>
					<th>IP</th>
					<th>Date</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $reports as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r->site_url ); ?></td>
					<td><?php echo esc_html( $r->owner_name ?: '—' ); ?></td>
					<td><?php echo nl2br( esc_html( $r->reason ) ); ?></td>
					<td><?php echo esc_html( $r->order_id ?: '—' ); ?></td>
					<td><?php echo esc_html( $r->ip_address ?: '—' ); ?></td>
					<td><?php echo esc_html( $r->date_reported ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
</div>
