<?php
defined( 'ABSPATH' ) || exit;

// Normalize entries (backwards compat: old entries used 'email' key)
$entries = array_map( function( $e ) {
	if ( ! isset( $e['type'] ) ) {
		$e['type']  = 'email';
		$e['value'] = $e['email'] ?? '';
	}
	return $e;
}, $customers );

$type_labels = array( 'email' => 'Email', 'ip' => 'IP Address', 'address' => 'Address', 'phone' => 'Phone' );
?>
<div class="wrap pepban-wrap">
	<h1>Customer Blacklist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Blacklisted customers are blocked on <strong>this site only</strong>. Use <em>Report to PepBan</em> on email entries to also add them to the global network.
	</div>

	<div style="margin:16px 0;padding:20px 24px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0;margin-bottom:4px">Add to Blacklist</h3>
		<p class="description" style="margin-top:0;margin-bottom:16px">Fill in any combination — each field you fill will block the customer.</p>
		<table class="form-table" style="margin:0">
			<tr>
				<th style="width:120px;padding:8px 0"><label for="pepban-bl-email">Email Address</label></th>
				<td style="padding:8px 0">
					<input type="email" id="pepban-bl-email" class="regular-text" placeholder="customer@example.com">
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-bl-ip">IP Address</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-bl-ip" class="regular-text" placeholder="192.168.1.1">
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-bl-phone">Phone Number</label></th>
				<td style="padding:8px 0">
					<input type="tel" id="pepban-bl-phone" class="regular-text" placeholder="+1 555 123 4567">
					<p class="description" style="margin-top:4px">Digits only are stored — dashes, spaces, and + are stripped automatically.</p>
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0;vertical-align:top;padding-top:14px"><label>Billing Address</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-bl-street" class="regular-text" placeholder="Street (e.g. 123 Main St)" style="margin-bottom:6px;display:block">
					<div style="display:flex;gap:6px;flex-wrap:wrap">
						<input type="text" id="pepban-bl-city"  style="flex:2;min-width:120px" placeholder="City">
						<input type="text" id="pepban-bl-state" style="flex:1;min-width:60px;max-width:80px" placeholder="State">
						<input type="text" id="pepban-bl-zip"   style="flex:1;min-width:80px;max-width:100px" placeholder="ZIP">
					</div>
					<p class="description" style="margin-top:4px">All four fields are required for address blocking.</p>
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-bl-reason">Reason</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-bl-reason" class="regular-text" placeholder="Optional">
					<p class="description" style="margin-top:6px;color:#b45309;background:#fffbeb;border:1px solid #fcd34d;border-radius:4px;padding:5px 8px;line-height:1.5">
						&#128274; <strong>Shared if reported.</strong> This reason stays on your site only — but if you click <em>Report to PepBan</em>, it becomes visible to all member stores.
					</p>
				</td>
			</tr>
		</table>
		<div style="margin-top:14px">
			<button id="pepban-bl-add" class="button button-primary">Add to Blacklist</button>
		</div>
		<div id="pepban-bl-result" style="margin-top:10px;display:none"></div>
	</div>

	<div style="margin:16px 0;padding:20px 24px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0;margin-bottom:4px">Bulk Import via CSV</h3>
		<p class="description" style="margin-top:0;margin-bottom:12px">
			CSV columns: <code>type, value, reason</code> &mdash; or single-column list of emails.<br>
			Valid types: <code>email</code>, <code>ip</code>, <code>address</code>, <code>phone</code><br>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pepban_download_template' ), 'pepban_download_template' ) ); ?>" style="font-weight:600">&#11015; Download example CSV template</a>
		</p>
		<input type="file" id="pepban-bl-csv-file" accept=".csv,text/csv"
		       style="display:block;margin-bottom:10px">
		<button id="pepban-bl-csv-import" class="button button-secondary">Import CSV</button>
		<div id="pepban-bl-csv-result" style="margin-top:10px;display:none"></div>
	</div>

	<?php if ( empty( $entries ) ) : ?>
		<p>No entries on this site's blacklist yet.</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped" style="max-width:900px">
		<thead>
			<tr>
				<th style="width:90px">Type</th>
				<th>Value</th>
				<th>Reason</th>
				<th style="width:160px">Date Added</th>
				<th style="width:130px">PepBan Network</th>
				<th style="width:80px">Actions</th>
			</tr>
		</thead>
		<tbody id="pepban-bl-table">
		<?php foreach ( $entries as $entry ) :
			$type     = $entry['type'];
			$value    = $entry['value'];
			$reported = ! empty( $entry['reported_to_hub'] );
		?>
			<tr class="pepban-bl-row" data-type="<?php echo esc_attr( $type ); ?>" data-value="<?php echo esc_attr( $value ); ?>">
				<td><span style="font-size:11px;font-weight:600;background:#f0f0f1;padding:2px 6px;border-radius:3px;text-transform:uppercase"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span></td>
				<td style="font-family:monospace;font-size:13px"><?php echo esc_html( $value ); ?></td>
				<td><?php echo esc_html( $entry['reason'] ?? '' ); ?></td>
				<td><?php echo esc_html( $entry['date_added'] ?? '' ); ?></td>
				<td>
					<?php if ( $type === 'email' ) : ?>
						<?php if ( $reported ) : ?>
							<span style="color:#00a32a">&#10003; Reported</span>
						<?php else : ?>
							<button class="button button-small pepban-bl-report"
								data-email="<?php echo esc_attr( $value ); ?>"
								data-reason="<?php echo esc_attr( $entry['reason'] ?? '' ); ?>">
								Report to PepBan
							</button>
						<?php endif; ?>
					<?php else : ?>
						<span style="color:#999">—</span>
					<?php endif; ?>
				</td>
				<td>
					<button class="button button-small pepban-bl-remove"
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
