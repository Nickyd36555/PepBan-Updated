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

$type_labels = array( 'email' => 'Email', 'ip' => 'IP Address', 'address' => 'Address' );
?>
<div class="wrap pepban-wrap">
	<h1>Customer Blacklist</h1>

	<div class="notice notice-info" style="padding:10px 12px">
		<strong>Note:</strong> Blacklisted customers are blocked on <strong>this site only</strong>. Use <em>Report to PepBan</em> on email entries to also add them to the global network.
	</div>

	<div style="margin:16px 0;padding:20px 24px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:520px">
		<h3 style="margin-top:0;margin-bottom:16px">Add to Blacklist</h3>
		<table class="form-table" style="margin:0">
			<tr>
				<th style="width:100px;padding:8px 0"><label for="pepban-bl-type">Block by</label></th>
				<td style="padding:8px 0">
					<select id="pepban-bl-type">
						<option value="email">Email Address</option>
						<option value="ip">IP Address</option>
						<option value="address">Billing Address</option>
					</select>
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-bl-value">Value</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-bl-value" class="regular-text" placeholder="customer@example.com">
					<p id="pepban-bl-hint" class="description" style="margin-top:4px">Block a specific email address at checkout.</p>
				</td>
			</tr>
			<tr>
				<th style="padding:8px 0"><label for="pepban-bl-reason">Reason</label></th>
				<td style="padding:8px 0">
					<input type="text" id="pepban-bl-reason" class="regular-text" placeholder="Optional">
				</td>
			</tr>
		</table>
		<div style="margin-top:14px">
			<button id="pepban-bl-add" class="button button-primary">Add to Blacklist</button>
		</div>
		<div id="pepban-bl-result" style="margin-top:10px;display:none"></div>
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
