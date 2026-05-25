<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>Customer Blacklist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Blacklisted customers are blocked on <strong>this site only</strong>. Use <em>Report to PepBan</em> to also add them to the global network so all member stores are protected.
	</div>

	<div style="margin:16px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0">Add Customer</h3>
		<div style="display:flex;gap:8px;margin-bottom:8px">
			<input type="email" id="pepban-bl-email" class="regular-text" placeholder="customer@example.com" style="flex:1">
			<input type="text" id="pepban-bl-reason" class="regular-text" placeholder="Reason (optional)" style="flex:1">
		</div>
		<button id="pepban-bl-add" class="button button-primary">Add to Blacklist</button>
		<div id="pepban-bl-result" style="margin-top:10px;display:none"></div>
	</div>

	<?php if ( empty( $customers ) ) : ?>
		<p>No customers are blacklisted on this site yet.</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped" style="max-width:820px">
		<thead>
			<tr>
				<th>Email</th>
				<th>Reason</th>
				<th>Date Added</th>
				<th>PepBan Network</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody id="pepban-bl-table">
		<?php foreach ( $customers as $entry ) :
			$email    = $entry['email'] ?? '';
			$reported = ! empty( $entry['reported_to_hub'] );
		?>
			<tr class="pepban-bl-row" data-email="<?php echo esc_attr( $email ); ?>">
				<td><?php echo esc_html( $email ); ?></td>
				<td><?php echo esc_html( $entry['reason'] ?? '' ); ?></td>
				<td><?php echo esc_html( $entry['date_added'] ?? '' ); ?></td>
				<td>
					<?php if ( $reported ) : ?>
						<span style="color:#00a32a">&#10003; Reported</span>
					<?php else : ?>
						<button class="button button-small pepban-bl-report"
							data-email="<?php echo esc_attr( $email ); ?>"
							data-reason="<?php echo esc_attr( $entry['reason'] ?? '' ); ?>">
							Report to PepBan
						</button>
					<?php endif; ?>
				</td>
				<td>
					<button class="button button-small pepban-bl-remove" data-email="<?php echo esc_attr( $email ); ?>">
						Remove
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
