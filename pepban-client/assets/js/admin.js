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
	$('#pepban-bl-add').on('click', function () {
		var reason  = $('#pepban-bl-reason').val().trim();
		var $result = $('#pepban-bl-result');
		var entries = [
			{ type: 'email',   value: $('#pepban-bl-email').val().trim() },
			{ type: 'ip',      value: $('#pepban-bl-ip').val().trim() },
			{ type: 'address', value: $('#pepban-bl-address').val().trim() },
		].filter(function (e) { return e.value !== ''; });

		if (!entries.length) { alert('Please fill in at least one field.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Adding…');
		$result.hide();

		var done = 0, succeeded = 0, errors = [];

		entries.forEach(function (entry) {
			$.post(ajaxurl, { action: 'pepban_blacklist_add', type: entry.type, value: entry.value, reason: reason, nonce: pepbanClient.nonce },
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
					$btn.prop('disabled', false).text('Add to Blacklist');
				} else {
					$result.html('<p style="color:#00a32a">&#10003; Added ' + succeeded + ' entr' + (succeeded === 1 ? 'y' : 'ies') + ' to the blacklist.</p>');
					$('#pepban-bl-email, #pepban-bl-ip, #pepban-bl-address, #pepban-bl-reason').val('');
					setTimeout(function () { location.reload(); }, 1200);
				}
			});
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
