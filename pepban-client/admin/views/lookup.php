<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap pepban-wrap">
	<h1>Customer Lookup</h1>
	<p class="description" style="margin-bottom:20px">Search any email address to see their ban status, watch notes, and local blacklist status — and take action directly from here.</p>

	<div style="display:flex;gap:10px;align-items:flex-start;max-width:520px;margin-bottom:24px">
		<input type="email" id="pepban-lookup-email" class="regular-text" placeholder="customer@example.com" style="flex:1;height:36px">
		<button id="pepban-lookup-btn" class="button button-primary" style="height:36px;white-space:nowrap">Search</button>
	</div>

	<div id="pepban-lookup-result" style="max-width:720px;display:none"></div>
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
					if (n.reason)       html += '<tr><td style="font-weight:600">Reason</td><td>' + escHtml(n.reason) + '</td></tr>';
					if (n.report_count) html += '<tr><td style="font-weight:600">Reports</td><td>' + escHtml(n.report_count) + ' report' + (n.report_count !== 1 ? 's' : '') + ' from ' + escHtml(n.store_count) + ' store' + (n.store_count !== 1 ? 's' : '') + '</td></tr>';
					if (d.whitelisted)  html += '<tr><td style="font-weight:600">Whitelist</td><td>' + badge('Whitelisted on this site', '#fff', '#2271b1') + '</td></tr>';
				} else {
					html += '<tr><td style="width:140px;font-weight:600">Status</td><td>' + badge('CLEAR', '#fff', '#00a32a') + '</td></tr>';
				}
				if (n.first_name || n.last_name) {
					html += '<tr><td style="font-weight:600">Name on file</td><td>' + escHtml((n.first_name + ' ' + n.last_name).trim()) + '</td></tr>';
				}
			} else {
				html += '<tr><td colspan="2" style="color:#888">No result from network.</td></tr>';
			}
			html += '</tbody></table>';

			// ── Watch notes ───────────────────────────────────────────────────
			var currentNote = d.watch_notes ? d.watch_notes.notes : '';
			html += '<table class="widefat" style="margin-bottom:16px"><thead><tr><th colspan="2" style="background:#f9f9f9">&#128064; Watch Notes</th></tr></thead><tbody>';
			if (d.watch_notes) {
				var wn = d.watch_notes;
				html += '<tr><td colspan="2"><div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:4px;padding:10px 12px;font-size:13px;line-height:1.6;margin-bottom:6px">' + escHtml(wn.notes) + '</div>';
				if (wn.saved_by || wn.saved_at) {
					html += '<span style="font-size:11px;color:#888">';
					if (wn.saved_by) html += 'Saved by <strong>' + escHtml(wn.saved_by) + '</strong>';
					if (wn.saved_at) html += (wn.saved_by ? ' &mdash; ' : '') + escHtml(wn.saved_at);
					html += '</span>';
				}
				html += '</td></tr>';
			} else {
				html += '<tr><td colspan="2" style="color:#888;padding-bottom:4px">No watch notes on this customer.</td></tr>';
			}
			// Edit / add note form
			html += '<tr><td colspan="2" style="padding-top:10px">'
				+ '<textarea id="pepban-lookup-note-text" rows="3" style="width:100%;margin-bottom:8px;border-color:#ddd;border-radius:3px;padding:6px 8px;font-size:13px" placeholder="Add or update a watch note…">' + escHtml(currentNote) + '</textarea>'
				+ '<div style="display:flex;gap:8px;align-items:center">'
				+ '<button class="button button-secondary pepban-lookup-save-note" data-email="' + escHtml(d.email) + '">Save Watch Note</button>'
				+ (currentNote ? '<button class="button pepban-lookup-clear-note" data-email="' + escHtml(d.email) + '" style="color:#d63638">Clear Note</button>' : '')
				+ '<span class="pepban-lookup-note-status" style="font-size:12px;color:#00a32a;display:none"></span>'
				+ '</div>'
				+ '</td></tr>';
			html += '</tbody></table>';

			// ── Local blacklist ───────────────────────────────────────────────
			html += '<table class="widefat"><thead><tr><th colspan="2" style="background:#f9f9f9">&#128683; Local Blacklist (This Site)</th></tr></thead><tbody>';
			if (d.blacklisted && d.blacklist) {
				var bl = d.blacklist;
				html += '<tr><td style="width:140px;font-weight:600">Status</td><td>' + badge('BLACKLISTED', '#fff', '#b45309') + (bl.reported ? ' &nbsp;' + badge('Reported to Network', '#fff', '#6b21a8') : '') + '</td></tr>';
				if (bl.reason)     html += '<tr><td style="font-weight:600">Reason</td><td>' + escHtml(bl.reason) + '</td></tr>';
				if (bl.date_added) html += '<tr><td style="font-weight:600">Date Added</td><td>' + escHtml(bl.date_added) + '</td></tr>';
				html += '<tr><td colspan="2" style="padding-top:8px">'
					+ '<button class="button pepban-lookup-bl-remove" data-email="' + escHtml(d.email) + '" style="color:#d63638">Remove from Blacklist</button>'
					+ ' <span class="pepban-lookup-bl-status" style="font-size:12px;display:none"></span>'
					+ '</td></tr>';
			} else {
				html += '<tr><td colspan="2" style="color:#888;padding-bottom:6px">Not on local blacklist.</td></tr>';
				html += '<tr><td colspan="2" style="padding-top:4px">'
					+ '<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:8px">'
					+ '<div style="flex:1;min-width:200px"><label style="font-size:11px;color:#666;display:block;margin-bottom:4px">Reason (optional)</label>'
					+ '<input type="text" id="pepban-lookup-bl-reason" class="regular-text" style="width:100%" placeholder="e.g. Chargeback fraud"></div>'
					+ '<button class="button button-secondary pepban-lookup-bl-add" data-email="' + escHtml(d.email) + '">Add Email to Blacklist</button>'
					+ '</div>'
					+ '<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">'
					+ '<div style="flex:1;min-width:200px"><label style="font-size:11px;color:#666;display:block;margin-bottom:4px">Also block by phone number</label>'
					+ '<input type="tel" id="pepban-lookup-bl-phone" class="regular-text" style="width:100%" placeholder="e.g. +1 555 123 4567"></div>'
					+ '<button class="button button-secondary pepban-lookup-bl-add-phone" data-email="' + escHtml(d.email) + '">Add Phone to Blacklist</button>'
					+ '</div>'
					+ '<p style="margin:8px 0 0;font-size:11px;color:#b45309;background:#fffbeb;border:1px solid #fcd34d;border-radius:3px;padding:4px 8px">&#128274; <strong>Local only.</strong> This adds the customer to your site blacklist only. Use <em>Report to PepBan</em> on an order to add them to the global network.</p>'
					+ '<span class="pepban-lookup-bl-status" style="font-size:12px;display:none;margin-top:6px;display:none"></span>'
					+ '</td></tr>';
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

	// ── Save watch note ───────────────────────────────────────────────────────
	$(document).on('click', '.pepban-lookup-save-note', function () {
		var $btn   = $(this);
		var email  = $btn.data('email');
		var notes  = $('#pepban-lookup-note-text').val();
		var $status = $btn.closest('td').find('.pepban-lookup-note-status');
		$btn.prop('disabled', true).text('Saving…');
		$.post(pepbanClient.ajaxurl, {
			action: 'pepban_save_watch_notes_by_email',
			email:  email,
			notes:  notes,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (res.success) {
				$status.text(res.data.message).css('color','#00a32a').show();
				setTimeout(function () { doLookup(); }, 800);
			} else {
				$status.text('Error: ' + escHtml(res.data || 'Unknown error.')).css('color','#d63638').show();
			}
		}).fail(function () {
			$status.text('Request failed.').css('color','#d63638').show();
		}).always(function () {
			$btn.prop('disabled', false).text('Save Watch Note');
		});
	});

	// ── Clear watch note ──────────────────────────────────────────────────────
	$(document).on('click', '.pepban-lookup-clear-note', function () {
		if (!confirm('Clear the watch note for this customer?')) return;
		$('#pepban-lookup-note-text').val('');
		$(this).closest('td').find('.pepban-lookup-save-note').trigger('click');
	});

	// ── Add email to local blacklist ──────────────────────────────────────────
	$(document).on('click', '.pepban-lookup-bl-add', function () {
		var $btn    = $(this);
		var email   = $btn.data('email');
		var reason  = $('#pepban-lookup-bl-reason').val().trim();
		var $status = $btn.closest('td').find('.pepban-lookup-bl-status');
		$btn.prop('disabled', true).text('Adding…');
		$.post(pepbanClient.ajaxurl, {
			action: 'pepban_blacklist_add',
			type:   'email',
			value:  email,
			reason: reason,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (res.success) {
				$status.text('Added to local blacklist.').css('color','#00a32a').show();
				setTimeout(function () { doLookup(); }, 800);
			} else {
				$status.text('Error: ' + escHtml(res.data || 'Unknown error.')).css('color','#d63638').show();
				$btn.prop('disabled', false).text('Add Email to Blacklist');
			}
		}).fail(function () {
			$status.text('Request failed.').css('color','#d63638').show();
			$btn.prop('disabled', false).text('Add Email to Blacklist');
		});
	});

	// ── Add phone to local blacklist ──────────────────────────────────────────
	$(document).on('click', '.pepban-lookup-bl-add-phone', function () {
		var $btn    = $(this);
		var phone   = $('#pepban-lookup-bl-phone').val().trim();
		var reason  = $('#pepban-lookup-bl-reason').val().trim();
		var $status = $btn.closest('td').find('.pepban-lookup-bl-status');
		if (!phone) { alert('Please enter a phone number.'); return; }
		$btn.prop('disabled', true).text('Adding…');
		$.post(pepbanClient.ajaxurl, {
			action: 'pepban_blacklist_add',
			type:   'phone',
			value:  phone,
			reason: reason,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (res.success) {
				$status.text('Phone added to local blacklist.').css('color','#00a32a').show();
				$('#pepban-lookup-bl-phone').val('');
			} else {
				$status.text('Error: ' + escHtml(res.data || 'Unknown error.')).css('color','#d63638').show();
				$btn.prop('disabled', false).text('Add Phone to Blacklist');
			}
		}).fail(function () {
			$status.text('Request failed.').css('color','#d63638').show();
			$btn.prop('disabled', false).text('Add Phone to Blacklist');
		});
	});

	// ── Remove from local blacklist ───────────────────────────────────────────
	$(document).on('click', '.pepban-lookup-bl-remove', function () {
		var $btn   = $(this);
		var email  = $btn.data('email');
		if (!confirm('Remove ' + email + ' from the local blacklist?')) return;
		var $status = $btn.closest('td').find('.pepban-lookup-bl-status');
		$btn.prop('disabled', true).text('Removing…');
		$.post(pepbanClient.ajaxurl, {
			action: 'pepban_blacklist_remove',
			type:   'email',
			value:  email,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (res.success) {
				$status.text('Removed from blacklist.').css('color','#00a32a').show();
				setTimeout(function () { doLookup(); }, 800);
			} else {
				$status.text('Error: ' + escHtml(res.data || 'Unknown error.')).css('color','#d63638').show();
				$btn.prop('disabled', false).text('Remove from Blacklist');
			}
		}).fail(function () {
			$status.text('Request failed.').css('color','#d63638').show();
			$btn.prop('disabled', false).text('Remove from Blacklist');
		});
	});

	$('#pepban-lookup-btn').on('click', doLookup);
	$('#pepban-lookup-email').on('keypress', function (e) {
		if (e.which === 13) doLookup();
	});
});
</script>
