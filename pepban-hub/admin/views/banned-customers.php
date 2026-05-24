<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>Banned Customers
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned&action=add' ) ); ?>" class="page-title-action">+ Add Manually</a>
	</h1>

	<?php if ( 'add' === $action ) : ?>
	<div class="pepban-card">
		<h2>Add Banned Customer</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pepban_hub_action' ); ?>
			<input type="hidden" name="action" value="pepban_hub_action">
			<input type="hidden" name="pepban_action" value="add_customer">
			<table class="form-table">
				<tr><th><label for="email">Email *</label></th>
					<td><input type="email" name="email" id="email" class="regular-text" required></td></tr>
				<tr><th><label for="first_name">First Name</label></th>
					<td><input type="text" name="first_name" id="first_name" class="regular-text"></td></tr>
				<tr><th><label for="last_name">Last Name</label></th>
					<td><input type="text" name="last_name" id="last_name" class="regular-text"></td></tr>
				<tr><th><label for="phone">Phone</label></th>
					<td><input type="text" name="phone" id="phone" class="regular-text"></td></tr>
				<tr><th><label for="billing_address">Billing Address</label></th>
					<td><textarea name="billing_address" id="billing_address" rows="3" class="regular-text"></textarea></td></tr>
				<tr><th><label for="ip_address">IP Address</label></th>
					<td><input type="text" name="ip_address" id="ip_address" class="regular-text"></td></tr>
				<tr><th><label for="reason">Reason *</label></th>
					<td><textarea name="reason" id="reason" rows="3" class="regular-text" required></textarea></td></tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary">Add to Ban List</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned' ) ); ?>" class="button">Cancel</a>
			</p>
		</form>
	</div>

	<?php else : ?>

	<form method="get">
		<input type="hidden" name="page" value="pepban-hub-banned">
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name="status">
					<option value="active"   <?php selected( $status, 'active' ); ?>>Active</option>
					<option value="inactive" <?php selected( $status, 'inactive' ); ?>>Inactive</option>
					<option value="all"      <?php selected( $status, 'all' ); ?>>All</option>
				</select>
				<button type="submit" class="button">Filter</button>
			</div>
			<div class="alignright">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search email, name, phone…">
				<button type="submit" class="button">Search</button>
			</div>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped pepban-table">
		<thead>
			<tr>
				<th>Email</th>
				<th>Name</th>
				<th>Phone</th>
				<th>Reported By</th>
				<th>Reports</th>
				<th>Date Added</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $customers ) ) : ?>
			<tr><td colspan="8">No banned customers found.</td></tr>
		<?php else : ?>
			<?php foreach ( $customers as $c ) : ?>
			<tr>
				<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned&action=view&id=' . $c->id ) ); ?>"><?php echo esc_html( $c->email ); ?></a></td>
				<td><?php echo esc_html( trim( $c->first_name . ' ' . $c->last_name ) ); ?></td>
				<td><?php echo esc_html( $c->phone ); ?></td>
				<td><?php echo esc_html( $c->reported_by_site ); ?></td>
				<td><?php echo esc_html( $c->reports_count ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $c->date_added ) ) ); ?></td>
				<td><span class="pepban-status pepban-status-<?php echo esc_attr( $c->status ); ?>"><?php echo esc_html( ucfirst( $c->status ) ); ?></span></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned&action=view&id=' . $c->id ) ); ?>">View</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php
	$total_pages = ceil( $total / 25 );
	if ( $total_pages > 1 ) {
		echo '<div class="tablenav bottom"><div class="tablenav-pages">';
		echo paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'current' => $page_num,
			'total'   => $total_pages,
		) );
		echo '</div></div>';
	}
	?>
	<?php endif; ?>
</div>
