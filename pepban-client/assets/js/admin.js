/* PepBan Client admin scripts */
jQuery(function ($) {

	// ── Report customer from order screen ────────────────────────────────────
	$(document).on('click', '.pepban-report-btn', function () {
		var $btn      = $(this);
		var orderId   = $btn.data('order-id');
		var nonce     = $btn.data('nonce');
		var $box      = $('#pepban-report-box');
		var reason    = $box.find('textarea[name="pepban_reason"]').val().trim();

		if (!reason) {
			alert('Please enter a reason for the ban.');
			return;
		}

		$btn.prop('disabled', true).text('Reporting…');

		$.post(ajaxurl, {
			action:   'pepban_report_customer',
			order_id: orderId,
			reason:   reason,
			nonce:    nonce,
		}, function (res) {
			if (res.success) {
				var customerId = res.data.customer_id;
				var reportedAt = res.data.reported_at;
				$box.html(
					'<p style="color:#00a32a">&#10003; ' + res.data.message + '</p>' +
					'<p>Reported on: <strong>' + reportedAt + '</strong></p>' +
					(customerId ? '<p>PepBan Customer ID: <strong>' + customerId + '</strong></p>' +
						'<button type="button" class="button pepban-whitelist-btn" ' +
						'data-order-id="' + orderId + '" ' +
						'data-customer-id="' + customerId + '" ' +
						'data-nonce="' + nonce + '">' +
						'Whitelist on This Site</button>' : '')
				);
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Report to PepBan');
			}
		}).fail(function () {
			alert('Request failed. Please try again.');
			$btn.prop('disabled', false).text('Report to PepBan');
		});
	});

	// ── Whitelist from order meta box ────────────────────────────────────────
	$(document).on('click', '.pepban-whitelist-btn', function () {
		var $btn       = $(this);
		var orderId    = $btn.data('order-id');
		var customerId = $btn.data('customer-id');
		var nonce      = $btn.data('nonce');
		var notes      = prompt('Optional notes about why this customer is whitelisted on your site:') || '';

		$btn.prop('disabled', true).text('Whitelisting…');

		$.post(ajaxurl, {
			action:      'pepban_whitelist_add',
			order_id:    orderId,
			customer_id: customerId,
			notes:       notes,
			nonce:       nonce,
		}, function (res) {
			if (res.success) {
				$btn.replaceWith('<p style="color:#00a32a">&#10003; ' + res.data + '</p>');
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Whitelist on This Site');
			}
		});
	});

	// ── Add to whitelist by email (whitelist page) ──────────────────────────
	$('#pepban-whitelist-lookup').on('click', function () {
		var email   = $('#pepban-whitelist-email').val().trim();
		var $result = $('#pepban-whitelist-result');
		if (!email) { alert('Please enter an email address.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Looking up…');
		$result.hide();

		$.post(ajaxurl, {
			action: 'pepban_whitelist_add_by_email',
			email:  email,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			$result.show();
			if (res.success) {
				$result.html('<p style="color:#00a32a">&#10003; ' + res.data.message + ' — ' + res.data.email + ' (' + (res.data.name || 'Unknown') + ')</p>');
				$('#pepban-whitelist-email').val('');
				setTimeout(function () { location.reload(); }, 1500);
			} else {
				$result.html('<p style="color:#d63638">&#10007; ' + (res.data || 'Unknown error.') + '</p>');
			}
		}).fail(function () {
			$result.show().html('<p style="color:#d63638">&#10007; Request failed. Please try again.</p>');
		}).always(function () {
			$btn.prop('disabled', false).text('Look Up & Whitelist');
		});
	});

	// ── Customer blacklist page ──────────────────────────────────────────────
	function buildAddress(prefix) {
		var street = $('#' + prefix + '-street').val().trim();
		var city   = $('#' + prefix + '-city').val().trim();
		var state  = $('#' + prefix + '-state').val().trim();
		var zip    = $('#' + prefix + '-zip').val().trim();
		// If any address field is filled, all four are required
		if (street || city || state || zip) {
			if (!street || !city || !state || !zip) {
				alert('All four address fields (Street, City, State, ZIP) are required.');
				return null;
			}
		}
		if (!street) return '';
		return [street, city, state, zip].join(' ');
	}

	function submitEntries(entries, action, reason, $btn, $result, btnText, clearIds) {
		if (!entries.length) { alert('Please fill in at least one field.'); return; }
		$btn.prop('disabled', true).text('Adding…');
		$result.hide();
		var done = 0, succeeded = 0, errors = [];
		entries.forEach(function (entry) {
			$.post(ajaxurl, { action: action, type: entry.type, value: entry.value, reason: reason, nonce: pepbanClient.nonce },
			function (res) {
				if (res.success) { succeeded++; } else { errors.push(res.data || 'Error'); }
			}).fail(function () {
				errors.push('Request failed for ' + entry.type);
			}).always(function () {
				done++;
				if (done < entries.length) return;
				$result.show();
				if (errors.length) {
					$result.html('<p style="color:#d63638">&#10007; ' + errors.join('; ') + '</p>');
					$btn.prop('disabled', false).text(btnText);
				} else {
					$result.html('<p style="color:#00a32a">&#10003; Added ' + succeeded + ' entr' + (succeeded === 1 ? 'y' : 'ies') + '.</p>');
					$(clearIds).val('');
					setTimeout(function () { location.reload(); }, 1200);
				}
			});
		});
	}

	$('#pepban-bl-add').on('click', function () {
		var addr    = buildAddress('pepban-bl');
		if (addr === null) return;
		var entries = [
			{ type: 'email',   value: $('#pepban-bl-email').val().trim() },
			{ type: 'ip',      value: $('#pepban-bl-ip').val().trim() },
			{ type: 'address', value: addr },
		].filter(function (e) { return e.value !== ''; });
		submitEntries(entries, 'pepban_blacklist_add', $('#pepban-bl-reason').val().trim(),
			$(this), $('#pepban-bl-result'), 'Add to Blacklist',
			'#pepban-bl-email, #pepban-bl-ip, #pepban-bl-street, #pepban-bl-city, #pepban-bl-state, #pepban-bl-zip, #pepban-bl-reason');
	});

	// ── Customer whitelist page ──────────────────────────────────────────────
	$('#pepban-wl-add').on('click', function () {
		var addr    = buildAddress('pepban-wl');
		if (addr === null) return;
		var entries = [
			{ type: 'email',   value: $('#pepban-wl-email').val().trim() },
			{ type: 'ip',      value: $('#pepban-wl-ip').val().trim() },
			{ type: 'address', value: addr },
		].filter(function (e) { return e.value !== ''; });
		submitEntries(entries, 'pepban_whitelist_local_add', $('#pepban-wl-reason').val().trim(),
			$(this), $('#pepban-wl-result'), 'Add to Whitelist',
			'#pepban-wl-email, #pepban-wl-ip, #pepban-wl-street, #pepban-wl-city, #pepban-wl-state, #pepban-wl-zip, #pepban-wl-reason');
	});

	$(document).on('click', '.pepban-wl-remove', function () {
		var $btn  = $(this);
		var type  = $btn.data('type') || 'email';
		var value = String($btn.data('value') || '');
		if (!confirm('Remove "' + value + '" from the local whitelist?')) return;
		$btn.prop('disabled', true).text('Removing…');
		$.post(ajaxurl, { action: 'pepban_whitelist_local_remove', type: type, value: value, nonce: pepbanClient.nonce },
		function (res) {
			if (res.success) {
				$('.pepban-wl-row[data-type="' + type + '"][data-value="' + value + '"]').fadeOut(300, function () { $(this).remove(); });
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Remove');
			}
		});
	});

	$(document).on('click', '.pepban-bl-report', function () {
		var $btn   = $(this);
		var email  = $btn.data('email');
		var reason = $btn.data('reason');
		if (!confirm('Report ' + email + ' to the global PepBan network? All member stores will see this customer as banned.')) return;

		$btn.prop('disabled', true).text('Reporting…');

		$.post(ajaxurl, { action: 'pepban_blacklist_report', email: email, reason: reason, nonce: pepbanClient.nonce },
		function (res) {
			if (res.success) {
				$btn.replaceWith('<span style="color:#00a32a">&#10003; Reported</span>');
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Report to PepBan');
			}
		});
	});

	$(document).on('click', '.pepban-bl-remove', function () {
		var $btn  = $(this);
		var type  = $btn.data('type') || 'email';
		var value = String($btn.data('value') || $btn.data('email') || '');
		if (!confirm('Remove "' + value + '" from the local blacklist?')) return;
		$btn.prop('disabled', true).text('Removing…');
		$.post(ajaxurl, { action: 'pepban_blacklist_remove', type: type, value: value, nonce: pepbanClient.nonce },
		function (res) {
			if (res.success) {
				$('.pepban-bl-row[data-type="' + type + '"][data-value="' + value + '"]').fadeOut(300, function () { $(this).remove(); });
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Remove');
			}
		});
	});

	// ── Domain blacklist page ────────────────────────────────────────────────
	$('#pepban-domain-add').on('click', function () {
		var domain  = $('#pepban-domain-input').val().trim().replace(/^@/, '');
		var reason  = $('#pepban-domain-reason').val().trim();
		var $result = $('#pepban-domain-result');
		if (!domain) { alert('Please enter a domain.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Blocking…');
		$result.hide();

		$.post(ajaxurl, {
			action: 'pepban_domain_add',
			domain: domain,
			reason: reason,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			$result.show();
			if (res.success) {
				$result.html('<p style="color:#00a32a">&#10003; ' + res.data.message + '</p>');
				$('#pepban-domain-input').val('');
				$('#pepban-domain-reason').val('');
				setTimeout(function () { location.reload(); }, 1200);
			} else {
				$result.html('<p style="color:#d63638">&#10007; ' + (res.data || 'Error.') + '</p>');
			}
		}).fail(function () {
			$result.show().html('<p style="color:#d63638">&#10007; Request failed.</p>');
		}).always(function () {
			$btn.prop('disabled', false).text('Block Domain');
		});
	});

	$(document).on('click', '.pepban-domain-remove', function () {
		var domain = $(this).data('domain');
		if (!confirm('Remove ' + domain + ' from the blacklist?')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).text('Removing…');

		$.post(ajaxurl, {
			action: 'pepban_domain_remove',
			domain: domain,
			nonce:  pepbanClient.nonce,
		}, function (res) {
			if (res.success) {
				$('#pepban-domain-row-' + domain).fadeOut(300, function () { $(this).remove(); });
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Remove');
			}
		});
	});

	// ── Bulk CSV import on blacklist page ───────────────────────────────────
	$('#pepban-bl-csv-import').on('click', function () {
		var file = $('#pepban-bl-csv-file')[0].files[0];
		if (!file) { alert('Please select a CSV file first.'); return; }

		var formData = new FormData();
		formData.append('action', 'pepban_blacklist_import_csv');
		formData.append('nonce',  pepbanClient.nonce);
		formData.append('csv_file', file);

		var $btn = $(this);
		var $res = $('#pepban-bl-csv-result');
		$btn.prop('disabled', true).text('Importing…');
		$res.hide();

		$.ajax({
			url:         pepbanClient.ajaxurl,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			success: function (res) {
				$btn.prop('disabled', false).text('Import CSV');
				if (res.success) {
					$res.html('<span style="color:#00a32a">&#10003; ' + res.data.message + '</span>').show();
					if (res.data.added > 0) setTimeout(function() { location.reload(); }, 1200);
				} else {
					$res.html('<span style="color:#d63638">&#10007; ' + (res.data || 'Import failed.') + '</span>').show();
				}
			},
			error: function () {
				$btn.prop('disabled', false).text('Import CSV');
				$res.html('<span style="color:#d63638">&#10007; Request failed.</span>').show();
			}
		});
	});

	// ── Remove from whitelist page ───────────────────────────────────────────
	$(document).on('click', '.pepban-remove-whitelist', function () {
		if (!confirm('Remove this customer from your site whitelist? They will be blocked again at checkout.')) return;

		var $btn       = $(this);
		var customerId = $btn.data('customer-id');
		var nonce      = $btn.data('nonce');

		$btn.prop('disabled', true).text('Removing…');

		$.post(ajaxurl, {
			action:      'pepban_whitelist_remove',
			customer_id: customerId,
			nonce:       nonce,
		}, function (res) {
			if (res.success) {
				$btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
			} else {
				alert('Error: ' + (res.data || 'Unknown error.'));
				$btn.prop('disabled', false).text('Remove from Whitelist');
			}
		});
	});
});
