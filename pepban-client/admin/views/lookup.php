<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap pepban-wrap">
	<h1>Customer Lookup</h1>
	<p class="description" style="margin-bottom:20px">Search any email address to instantly see their PepBan network ban status, watch notes, and local blacklist status.</p>

	<div style="display:flex;gap:10px;align-items:flex-start;max-width:520px;margin-bottom:24px">
		<input type="email" id="pepban-lookup-email" class="regular-text" placeholder="customer@example.com" style="flex:1;height:36px">
		<button id="pepban-lookup-btn" class="button button-primary" style="height:36px;white-space:nowrap">Search</button>
	</div>

	<div id="pepban-lookup-result" style="max-width:680px;display:none"></div>
</div>

<script>
jQuery(function ($) {
	function escHtml(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function badge(text, color, bg) {
		return '<span style="display:inline-block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:2px 8px;border-radius:3px;color:' + color + ';background:' + bg + '">' + escHtml(text) + '</span>';
	}

	function doLookup() {
		var email = $('#pepban-lookup-email').val().trim();
		if (!email) { alert('Please enter an email address.'); return; }

		var $btn = $('#pepban-lookup-btn');
		var $res = $('#pepban-lookup-result');
		$btn.prop('disabled', true).text('Searching…');
		$res.hide().html('');

		$.post(pepbanClient.ajaxurl, {
			action: 'pepban_customer_lookup',
			email:  email,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (!res.success) {
				$res.html('<div class="notice notice-error inline" style="padding:10px 14px"><p>' + escHtml(res.data || 'Error.') + '</p></div>').show();
				return;
			}

			var d    = res.data;
			var html = '<div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:20px 24px">';
			html    += '<h3 style="margin-top:0;margin-bottom:16px;font-size:15px">Results for <code>' + escHtml(d.email) + '</code></h3>';

			// ── Network status ────────────────────────────────────────────────
			html += '<table class="widefat" style="margin-bottom:16px"><thead><tr><th colspan="2" style="background:#f9f9f9">&#127760; PepBan Network</th></tr></thead><tbody>';

			if (d.network_error) {
				html += '<tr><td colspan="2" style="color:#d63638">Could not reach PepBan network: ' + escHtml(d.network_error) + '</td></tr>';
			} else if (d.network) {
				var n = d.network;
				if (n.banned) {
					html += '<tr><td style="width:140px;font-weight:600">Status</td><td>' + badge('BANNED', '#fff', '#d63638') + '</td></tr>';
					if (n.reason)     html += '<tr><td style="font-weight:600">Reason</td><td>' + escHtml(n.reason) + '</td></tr>';
					if (n.report_count) html += '<tr><td style="font-weight:600">Reports</td><td>' + escHtml(n.report_count) + ' report' + (n.report_count !== 1 ? 's' : '') + ' from ' + escHtml(n.store_count) + ' store' + (n.store_count !== 1 ? 's' : '') + '</td></tr>';
					if (d.whitelisted) html += '<tr><td style="font-weight:600">Whitelist</td><td>' + badge('Whitelisted on this site', '#fff', '#2271b1') + '</td></tr>';
				} else {
					html += '<tr><td style="width:140px;font-weight:600">Status</td><td>' + badge('CLEAR', '#fff', '#00a32a') + '</td></tr>';
				}
				if (n.first_name || n.last_name) {
					html += '<tr><td style="font-weight:600">Name on file</td><td>' + escHtml(n.first_name + ' ' + n.last_name) + '</td></tr>';
				}
			} else {
				html += '<tr><td colspan="2" style="color:#888">No result from network.</td></tr>';
			}
			html += '</tbody></table>';

			// ── Watch notes ───────────────────────────────────────────────────
			html += '<table class="widefat" style="margin-bottom:16px"><thead><tr><th colspan="2" style="background:#f9f9f9">&#128064; Watch Notes</th></tr></thead><tbody>';
			if (d.watch_notes) {
				var wn = d.watch_notes;
				html += '<tr><td colspan="2"><div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:4px;padding:10px 12px;font-size:13px;line-height:1.6">' + escHtml(wn.notes) + '</div></td></tr>';
				if (wn.saved_by || wn.saved_at) {
					html += '<tr><td colspan="2" style="font-size:11px;color:#888;padding-top:4px">';
					if (wn.saved_by) html += 'Saved by <strong>' + escHtml(wn.saved_by) + '</strong>';
					if (wn.saved_at) html += (wn.saved_by ? ' &mdash; ' : '') + escHtml(wn.saved_at);
					html += '</td></tr>';
				}
			} else {
				html += '<tr><td colspan="2" style="color:#888">No watch notes on this customer.</td></tr>';
			}
			html += '</tbody></table>';

			// ── Local blacklist ───────────────────────────────────────────────
			html += '<table class="widefat"><thead><tr><th colspan="2" style="background:#f9f9f9">&#128683; Local Blacklist (This Site)</th></tr></thead><tbody>';
			if (d.blacklisted && d.blacklist) {
				var bl = d.blacklist;
				html += '<tr><td style="width:140px;font-weight:600">Status</td><td>' + badge('BLACKLISTED', '#fff', '#b45309') + (bl.reported ? ' &nbsp;' + badge('Reported to Network', '#fff', '#6b21a8') : '') + '</td></tr>';
				if (bl.reason)     html += '<tr><td style="font-weight:600">Reason</td><td>' + escHtml(bl.reason) + '</td></tr>';
				if (bl.date_added) html += '<tr><td style="font-weight:600">Date Added</td><td>' + escHtml(bl.date_added) + '</td></tr>';
			} else {
				html += '<tr><td colspan="2" style="color:#888">Not on local blacklist.</td></tr>';
			}
			html += '</tbody></table>';

			html += '</div>';
			$res.html(html).show();
		}).fail(function () {
			$res.html('<div class="notice notice-error inline" style="padding:10px 14px"><p>Request failed. Please try again.</p></div>').show();
		}).always(function () {
			$btn.prop('disabled', false).text('Search');
		});
	}

	$('#pepban-lookup-btn').on('click', doLookup);
	$('#pepban-lookup-email').on('keypress', function (e) {
		if (e.which === 13) doLookup();
	});
});
</script>
