/* PepBan Hub admin scripts */
jQuery(function ($) {
	// Copy API key to clipboard
	$(document).on('click', '.pepban-copy-key', function () {
		var key = $(this).data('key');
		if (navigator.clipboard) {
			navigator.clipboard.writeText(key).then(function () {
				alert('API key copied to clipboard.');
			});
		}
	});

	// Confirm destructive actions (supplementary to PHP onsubmit)
	$(document).on('submit', '.pepban-confirm-form', function () {
		var msg = $(this).data('confirm') || 'Are you sure?';
		return confirm(msg);
	});
});
