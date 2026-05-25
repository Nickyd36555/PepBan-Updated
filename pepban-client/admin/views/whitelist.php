<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>PepBan Site Whitelist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Whitelisted customers are still in the <em>global</em> PepBan database.
		They are only allowed to order on <strong>this specific site</strong>. Other sites in the network still see them as banned.
	</div>

	<div class="pepban-whitelist-add-form" style="margin:16px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:500px">
		<h3 style="margin-top:0">Add Customer to Whitelist</h3>
		<p style="color:#666;margin-top:-8px">Enter the customer's email to look them up and whitelist them on this site.</p>
		<div style="display:flex;gap:8px;align-items:flex-start">
			<input type="email" id="pepban-whitelist-email" class="regular-text" placeholder="customer@example.com" style="flex:1">
			<button id="pepban-whitelist-lookup" class="button button-primary">Look Up &amp; Whitelist</button>
		</div>
		<div id="pepban-whitelist-result" style="margin-top:10px;display:none"></div>
	</div>

	<?php if ( empty( $whitelist ) ) : ?>
		<p>No customers are whitelisted on this site yet.</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Email</th>
				<th>Name</th>
				<th>Notes</th>
				<th>Whitelisted On</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $whitelist as $entry ) : ?>
			<tr>
				<td><?php echo esc_html( $entry->email ?? '' ); ?></td>
				<td><?php echo esc_html( ( $entry->first_name ?? '' ) . ' ' . ( $entry->last_name ?? '' ) ); ?></td>
				<td><?php echo esc_html( $entry->notes ?? '' ); ?></td>
				<td><?php echo esc_html( $entry->date_added ?? '' ); ?></td>
				<td>
					<button class="button button-small pepban-remove-whitelist"
						data-customer-id="<?php echo esc_attr( $entry->customer_id ?? '' ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'pepban_whitelist_remove_' . ( $entry->customer_id ?? '' ) ) ); ?>">
						Remove from Whitelist
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
