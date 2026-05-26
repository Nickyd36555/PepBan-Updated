<?php defined( 'ABSPATH' ) || exit;

$local_whitelist = PepBan_Client_Blacklist::whitelist_get_all();
$type_labels     = array( 'email' => 'Email', 'ip' => 'IP Address', 'address' => 'Address' );
?>
<div class="wrap pepban-wrap">
	<h1>PepBan Site Whitelist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Whitelisted customers bypass all blocking on <strong>this site only</strong> — local blacklist, domain blacklist, and the global PepBan network.
	</div>

	<div style="margin:16px 0;padding:20px 24px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0;margin-bottom:4px">Add to Whitelist</h3>
		<p class="description" style="margin-top:0;margin-bottom:16px">Fill in any combination — each field you fill will whitelist the customer.</p>
		<table class="form-table" style="margin:0">
			<tr>
				<th style="width:120px;padding:8px 0"><label for="pepban-wl-email">Email Address</label></th>
				<td style="padding:8px 0">
					<input type="email" id="pepban-wl-email" class="regular-text" placeholder="customer@example.com">
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-wl-ip">IP Address</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-wl-ip" class="regular-text" placeholder="192.168.1.1">
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0;vertical-align:top;padding-top:14px"><label>Billing Address</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-wl-street" class="regular-text" placeholder="Street (e.g. 123 Main St)" style="margin-bottom:6px;display:block">
					<div style="display:flex;gap:6px;flex-wrap:wrap">
						<input type="text" id="pepban-wl-city"  style="flex:2;min-width:120px" placeholder="City">
						<input type="text" id="pepban-wl-state" style="flex:1;min-width:60px;max-width:80px" placeholder="State">
						<input type="text" id="pepban-wl-zip"   style="flex:1;min-width:80px;max-width:100px" placeholder="ZIP">
					</div>
					<p class="description" style="margin-top:4px">All four fields are required for address whitelisting.</p>
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-wl-reason">Reason</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-wl-reason" class="regular-text" placeholder="Optional">
				</td>
			</tr>
		</table>
		<div style="margin-top:14px">
			<button id="pepban-wl-add" class="button button-primary">Add to Whitelist</button>
		</div>
		<div id="pepban-wl-result" style="margin-top:10px;display:none"></div>
	</div>

	<?php if ( empty( $local_whitelist ) ) : ?>
		<p>No customers are whitelisted on this site yet.</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped" style="max-width:900px">
		<thead>
			<tr>
				<th style="width:90px">Type</th>
				<th>Value</th>
				<th>Reason</th>
				<th style="width:160px">Date Added</th>
				<th style="width:80px">Actions</th>
			</tr>
		</thead>
		<tbody id="pepban-wl-table">
		<?php foreach ( $local_whitelist as $entry ) :
			$type  = $entry['type']  ?? 'email';
			$value = $entry['value'] ?? $entry['email'] ?? '';
		?>
			<tr class="pepban-wl-row" data-type="<?php echo esc_attr( $type ); ?>" data-value="<?php echo esc_attr( $value ); ?>">
				<td><span style="font-size:11px;font-weight:600;background:#f0f0f1;padding:2px 6px;border-radius:3px;text-transform:uppercase"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span></td>
				<td style="font-family:monospace;font-size:13px"><?php echo esc_html( $value ); ?></td>
				<td><?php echo esc_html( $entry['reason'] ?? '' ); ?></td>
				<td><?php echo esc_html( $entry['date_added'] ?? '' ); ?></td>
				<td>
					<button class="button button-small pepban-wl-remove"
						data-type="<?php echo esc_attr( $type ); ?>"
						data-value="<?php echo esc_attr( $value ); ?>">
						Remove
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
