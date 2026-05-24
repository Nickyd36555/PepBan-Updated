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
