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

	<div style="margin:16px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;max-width:640px">
		<h3 style="margin-top:0">Add to Blacklist</h3>
		<div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap">
			<select id="pepban-bl-type" style="flex:0 0 auto">
				<option value="email">Email Address</option>
				<option value="ip">IP Address</option>
				<option value="address">Billing Address</option>
			</select>
			<input type="text" id="pepban-bl-value" class="regular-text" placeholder="Enter value…" style="flex:1;min-width:180px">
			<input type="text" id="pepban-bl-reason" class="regular-text" placeholder="Reason (optional)" style="flex:1;min-width:140px">
		</div>
		<p id="pepban-bl-hint" style="margin:0 0 10px;font-size:12px;color:#666">Block a specific email address at checkout.</p>
		<button id="pepban-bl-add" class="button button-primary">Add to Blacklist</button>
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
