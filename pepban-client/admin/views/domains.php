<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>PepBan Domain Blacklist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Blocked domains only apply to <strong>this site</strong>. Customers using a blocked domain email will be prevented from checking out here, but other PepBan sites are not affected.
	</div>

	<div class="pepban-domain-add-form" style="margin:16px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0">Block a Domain</h3>
		<p style="color:#666;margin-top:-8px">e.g. <code>fda.gov</code> or <code>guerrillamail.com</code></p>
		<div style="display:flex;gap:8px;margin-bottom:8px">
			<input type="text" id="pepban-domain-input" class="regular-text" placeholder="example.com" style="flex:1">
			<input type="text" id="pepban-domain-reason" class="regular-text" placeholder="Reason (optional)" style="flex:1">
		</div>
		<button id="pepban-domain-add" class="button button-primary">Block Domain</button>
		<div id="pepban-domain-result" style="margin-top:10px;display:none"></div>
	</div>

	<?php if ( empty( $domains ) ) : ?>
		<p>No domains are blocked yet.</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped" style="max-width:760px">
		<thead>
			<tr>
				<th>Domain</th>
				<th>Reason</th>
				<th>Date Added</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody id="pepban-domain-table">
		<?php foreach ( $domains as $row ) : ?>
			<tr id="pepban-domain-row-<?php echo esc_attr( $row->domain ?? $row['domain'] ); ?>">
				<td><strong><?php echo esc_html( $row->domain ?? $row['domain'] ); ?></strong></td>
				<td><?php echo esc_html( $row->reason ?? $row['reason'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row->date_added ?? $row['date_added'] ?? '' ); ?></td>
				<td>
					<button class="button button-small pepban-domain-remove"
						data-domain="<?php echo esc_attr( $row->domain ?? $row['domain'] ); ?>">
						Remove
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
