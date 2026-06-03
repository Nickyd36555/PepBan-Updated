<?php
defined('PEPBAN_VERSION') || die;

class Mailer {

	private static string $last_error = '';

	public static function last_error(): string { return self::$last_error; }

	/**
	 * Send an email. When $html is provided, sends multipart/alternative.
	 */
	public static function send(string $to, string $subject, string $plain, string $html = ''): bool {
		if (defined('SMTP_HOST') && SMTP_HOST) {
			return self::smtp($to, $subject, $plain, $html);
		}
		if ($html) {
			$boundary = 'pb_' . md5(uniqid('', true));
			$headers  = implode("\r\n", [
				'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
				'Reply-To: ' . MAIL_FROM,
				'MIME-Version: 1.0',
				'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
			]);
			$body =
				"--{$boundary}\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n\r\n" .
				$plain . "\r\n\r\n" .
				"--{$boundary}\r\n" .
				"Content-Type: text/html; charset=UTF-8\r\n\r\n" .
				$html . "\r\n\r\n" .
				"--{$boundary}--";
			return mail($to, $subject, $body, $headers);
		}
		$headers = implode("\r\n", [
			'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
			'Reply-To: ' . MAIL_FROM,
			'Content-Type: text/plain; charset=UTF-8',
		]);
		return mail($to, $subject, $plain, $headers);
	}

	// ── HTML template helpers ─────────────────────────────────────────────────────

	private static function wrap(string $content, string $accent = '#dc2626'): string {
		$support = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '');
		$footer_support = $support
			? '<p style="margin:5px 0 0;font-size:12px;color:#9ca3af;font-family:inherit">Questions? <a href="mailto:' . $support . '" style="color:#dc2626;text-decoration:none">' . $support . '</a></p>'
			: '';
		$year = date('Y');
		return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PepBan</title></head>' .
			'<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif">' .
			'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:48px 20px"><tr><td align="center">' .
			'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">' .
			'<tr><td style="background-color:#0c0c1e;border-radius:12px 12px 0 0;padding:28px 40px;text-align:center">' .
			'<span style="font-size:26px;font-weight:800;letter-spacing:-.03em;color:#ffffff;font-family:inherit">Pep<span style="color:' . $accent . '">Ban</span></span>' .
			'</td></tr>' .
			'<tr><td style="background-color:#ffffff;padding:40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb">' .
			$content .
			'</td></tr>' .
			'<tr><td style="background-color:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center">' .
			'<p style="margin:0;font-size:12px;color:#9ca3af;font-family:inherit">&copy; ' . $year . ' PepBan &mdash; Fraud prevention for WooCommerce stores</p>' .
			$footer_support .
			'</td></tr></table></td></tr></table></body></html>';
	}

	private static function h1(string $t): string {
		return '<h1 style="margin:0 0 20px;font-size:22px;font-weight:700;color:#111827;letter-spacing:-.02em;font-family:inherit">' . $t . '</h1>';
	}

	private static function p(string $t, string $extra = ''): string {
		return '<p style="margin:0 0 18px;font-size:15px;color:#374151;line-height:1.7;font-family:inherit' . ($extra ? ';' . $extra : '') . '">' . $t . '</p>';
	}

	private static function notice(string $label, string $value, string $bg, string $border, string $fg): string {
		return '<div style="background-color:' . $bg . ';border-left:4px solid ' . $border . ';padding:16px 20px;border-radius:0 8px 8px 0;margin:0 0 24px">' .
			'<p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:' . $border . ';font-family:inherit">' . $label . '</p>' .
			'<p style="margin:0;font-size:14px;color:' . $fg . ';font-family:inherit">' . $value . '</p>' .
			'</div>';
	}

	private static function btn(string $url, string $label, string $color = '#dc2626'): string {
		return '<div style="text-align:center;margin:28px 0">' .
			'<a href="' . $url . '" style="display:inline-block;background-color:' . $color . ';color:#ffffff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;font-family:inherit">' . $label . '</a>' .
			'</div>';
	}

	private static function codeBox(string $val): string {
		return '<div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin:0 0 24px;text-align:center">' .
			'<code style="font-family:\'SF Mono\',Menlo,Monaco,Consolas,monospace;font-size:15px;color:#111827;letter-spacing:.04em;word-break:break-all">' . $val . '</code>' .
			'</div>';
	}

	private static function hr(): string {
		return '<hr style="border:none;border-top:1px solid #f3f4f6;margin:24px 0">';
	}

	// ── SMTP client (multipart-aware) ─────────────────────────────────────────────

	private static function smtp(string $to, string $subject, string $plain, string $html = ''): bool {
		$host   = SMTP_HOST;
		$port   = (int) SMTP_PORT;
		$secure = SMTP_SECURE;

		$ctx = stream_context_create(['ssl' => [
			'verify_peer'      => true,
			'verify_peer_name' => true,
		]]);

		$addr   = $secure === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
		$socket = stream_socket_client($addr, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
		if (!$socket) {
			self::$last_error = "Connection failed to {$addr}: {$errstr} ({$errno})";
			error_log('PepBan SMTP: ' . self::$last_error);
			return false;
		}

		stream_set_timeout($socket, 15);

		$read = fn() => fgets($socket, 1024);
		$cmd  = function(string $line) use ($socket, $read): string {
			fwrite($socket, $line . "\r\n");
			$resp = '';
			while ($r = fgets($socket, 1024)) {
				$resp = $r;
				if (strlen($r) < 4 || $r[3] !== '-') break;
			}
			return $resp;
		};

		$read();
		$cmd('EHLO ' . (gethostname() ?: 'localhost'));

		if ($secure === 'tls') {
			$r = $cmd('STARTTLS');
			if ((int)$r !== 220) { fclose($socket); return false; }
			if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
				fclose($socket); return false;
			}
			$cmd('EHLO ' . (gethostname() ?: 'localhost'));
		}

		$r = $cmd('AUTH LOGIN');
		if ((int)$r !== 334) {
			self::$last_error = "AUTH LOGIN rejected: {$r}";
			error_log('PepBan SMTP: ' . self::$last_error); fclose($socket); return false;
		}
		$cmd(base64_encode(SMTP_USER));
		$r = $cmd(base64_encode(SMTP_PASS));
		if ((int)$r !== 235) {
			self::$last_error = "Authentication failed (wrong SMTP username or password): {$r}";
			error_log('PepBan SMTP: ' . self::$last_error); fclose($socket); return false;
		}

		$envelope_from = SMTP_USER ?: MAIL_FROM;
		$cmd('MAIL FROM:<' . $envelope_from . '>');
		$r = $cmd('RCPT TO:<' . $to . '>');
		if ((int)$r > 299) { error_log("PepBan SMTP: recipient rejected ({$to}) — {$r}"); fclose($socket); return false; }

		$cmd('DATA');
		$date  = date('r');
		$msgId = '<' . time() . '.' . rand(1000, 9999) . '@pepban.com>';
		$dot   = fn(string $s) => preg_replace('/^\./m', '..', $s);

		if ($html) {
			$boundary  = 'pb_' . md5(uniqid('', true));
			$ct_header = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
			$mime_body =
				"--{$boundary}\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 7bit\r\n\r\n" .
				$dot($plain) . "\r\n\r\n" .
				"--{$boundary}\r\n" .
				"Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 7bit\r\n\r\n" .
				$dot($html) . "\r\n\r\n" .
				"--{$boundary}--";
		} else {
			$ct_header = 'Content-Type: text/plain; charset=UTF-8';
			$mime_body = $dot($plain);
		}

		$headers =
			"Date: {$date}\r\n" .
			"Message-ID: {$msgId}\r\n" .
			"From: " . MAIL_FROM_NAME . " <" . $envelope_from . ">\r\n" .
			"Reply-To: " . MAIL_FROM . "\r\n" .
			"To: {$to}\r\n" .
			"Subject: {$subject}\r\n" .
			"MIME-Version: 1.0\r\n" .
			$ct_header . "\r\n";

		fwrite($socket, $headers . "\r\n" . $mime_body . "\r\n.\r\n");

		$r = $read();
		$cmd('QUIT');
		fclose($socket);

		if ((int)$r !== 250) {
			self::$last_error = "Message rejected by server: {$r}";
			error_log("PepBan SMTP: message not queued for {$to} — {$r}");
			return false;
		}
		return true;
	}

	// ── Customer-facing emails ────────────────────────────────────────────────────

	public static function customerBanned(string $email, string $name, string $reason): void {
		$dispute_url = rtrim(SITE_URL, '/') . '/dispute';
		$support     = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL;
		$greeting    = $name ? "Hi {$name}," : 'Hello,';
		$first       = $name ? htmlspecialchars(explode(' ', trim($name))[0]) : 'there';

		$plain =
			"{$greeting}\n\n" .
			"Your account has been flagged and you may be blocked from completing purchases at stores in the PepBan network.\n\n" .
			"Reason on file:\n  {$reason}\n\n" .
			"If you believe this is a mistake, submit a dispute for review:\n  {$dispute_url}\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::h1('Important notice about your account') .
			self::p("Hi {$first},") .
			self::p('Your account has been flagged and you may be blocked from completing purchases at stores in the <strong style="color:#111827">PepBan</strong> network.') .
			self::notice('Reason on file', htmlspecialchars($reason), '#fef2f2', '#dc2626', '#7f1d1d') .
			self::p('If you believe this is a mistake, you can submit a dispute for review. We investigate every case and respond within 3&ndash;5 business days.') .
			self::btn($dispute_url, 'Submit a Dispute &rarr;') .
			self::hr() .
			self::p('Questions? Contact us at <a href="mailto:' . $support . '" style="color:#dc2626;text-decoration:none">' . $support . '</a>.', 'font-size:13px;color:#6b7280')
		);

		$sent = self::send($email, 'Important notice regarding your account', $plain, $html);
		if (!$sent) error_log("PepBan: customerBanned email failed for {$email}");
	}

	public static function disputeResolved(string $email, string $name): void {
		$support = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL;
		$first   = $name ? htmlspecialchars(explode(' ', trim($name))[0]) : 'there';

		$plain =
			"Hi {$name},\n\n" .
			"We've reviewed your dispute and resolved it in your favor. Your account has been removed from the ban list and you should now be able to check out at member stores.\n\n" .
			"If you continue to experience issues, please contact us at {$support}.\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::notice('Dispute resolved', 'Your account has been cleared', '#f0fdf4', '#22c55e', '#166534') .
			self::h1('Your dispute has been resolved') .
			self::p("Hi {$first},") .
			self::p("We&rsquo;ve reviewed your dispute and resolved it in your favor. Your account has been <strong style=\"color:#111827\">removed from the ban list</strong> and you should now be able to check out at member stores.") .
			self::hr() .
			self::p('If you continue to experience issues, contact us at <a href="mailto:' . $support . '" style="color:#dc2626;text-decoration:none">' . $support . '</a>.', 'font-size:13px;color:#6b7280'),
			'#22c55e'
		);

		self::send($email, 'PepBan — Your Dispute Has Been Resolved', $plain, $html);
	}

	public static function disputeDismissed(string $email, string $name): void {
		$support = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL;
		$first   = $name ? htmlspecialchars(explode(' ', trim($name))[0]) : 'there';

		$plain =
			"Hi {$name},\n\n" .
			"We've reviewed your dispute. After investigation, we were unable to remove the ban at this time.\n\n" .
			"If you have additional information to provide, please contact us at {$support}.\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::h1('Your dispute has been reviewed') .
			self::p("Hi {$first},") .
			self::p("We&rsquo;ve completed our review of your dispute. After investigation, we were <strong style=\"color:#111827\">unable to remove the ban</strong> at this time.") .
			self::p('If you have additional information that may change the outcome, please reach out directly.') .
			self::btn('mailto:' . $support, 'Contact Us', '#374151') .
			self::hr() .
			self::p('You can also reach us at <a href="mailto:' . $support . '" style="color:#dc2626;text-decoration:none">' . $support . '</a>.', 'font-size:13px;color:#6b7280'),
			'#6b7280'
		);

		self::send($email, 'PepBan — Your Dispute Has Been Reviewed', $plain, $html);
	}

	public static function welcome(object $client, string $raw_key): void {
		$portal_url   = url('/portal');
		$plain_status = ($client->subscription_status === 'active')
			? 'Your account is active and ready to use.'
			: "Your account is pending admin approval. You'll get an email once it's activated.";
		$html_status = ($client->subscription_status === 'active')
			? 'Your account is <strong style="color:#111827">active and ready to use</strong>.'
			: 'Your account is <strong style="color:#111827">pending admin approval</strong>. You&rsquo;ll receive an email once it&rsquo;s activated.';

		$plain =
			"Hi {$client->owner_name},\n\n" .
			"Thanks for signing up for PepBan!\n\n" .
			"{$plain_status}\n\n" .
			"Your API key (copy and save this — it won't be shown again):\n\n" .
			"  {$raw_key}\n\n" .
			"Setup:\n" .
			"  1. Log in: {$portal_url}\n" .
			"  2. Download the PepBan Client plugin\n" .
			"  3. Install on {$client->site_url} via Plugins > Add New > Upload Plugin\n" .
			"  4. Go to PepBan > Settings and enter your API Key\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::h1('Welcome to PepBan!') .
			self::p('Hi ' . htmlspecialchars($client->owner_name) . ',') .
			self::p('Thanks for signing up. ' . $html_status) .
			'<p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#9ca3af;font-family:inherit">Your API Key &mdash; save this now</p>' .
			'<p style="margin:0 0 10px;font-size:12px;color:#9ca3af;font-family:inherit">This is the only time it will be shown in full. A copy has been sent to this address.</p>' .
			self::codeBox(htmlspecialchars($raw_key)) .
			self::hr() .
			'<p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#374151;font-family:inherit">Setup steps</p>' .
			'<ol style="margin:0 0 24px;padding-left:20px;font-size:14px;color:#374151;line-height:2.2;font-family:inherit">' .
			'<li>Log in to your portal and download the plugin</li>' .
			'<li>On <strong>' . htmlspecialchars($client->site_url) . '</strong>: Plugins &rarr; Add New &rarr; Upload Plugin</li>' .
			'<li>Activate, then go to <strong>PepBan &rarr; Settings</strong></li>' .
			'<li>Paste your API key above &rarr; Save &rarr; green <strong>Connected</strong> confirms it</li>' .
			'</ol>' .
			self::btn($portal_url, 'Go to My Portal')
		);

		self::send($client->owner_email, 'Welcome to PepBan — Your API Key', $plain, $html);
	}

	public static function newKey(object $client, string $raw_key): void {
		$plain =
			"Hi {$client->owner_name},\n\n" .
			"A new API key has been generated for your account. Your old key is revoked.\n\n" .
			"  {$raw_key}\n\n" .
			"Update the API Key in PepBan > Settings on {$client->site_url}.\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::h1('Your new API key') .
			self::p('Hi ' . htmlspecialchars($client->owner_name) . ',') .
			self::p('A new API key has been generated. Your old key has been <strong style="color:#111827">revoked immediately</strong>.') .
			'<p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#9ca3af;font-family:inherit">New API Key</p>' .
			self::codeBox(htmlspecialchars($raw_key)) .
			self::p('Update this in <strong>PepBan &rarr; Settings &rarr; API Key</strong> on ' . htmlspecialchars($client->site_url) . ' now.')
		);

		self::send($client->owner_email, 'PepBan — Your New API Key', $plain, $html);
	}

	public static function activated(object $client): void {
		$portal_url = url('/portal');

		$plain =
			"Hi {$client->owner_name},\n\n" .
			"Your PepBan account has been approved and is now active.\n\n" .
			"Log in to your portal to download the client plugin:\n{$portal_url}\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::notice('Account status', 'Active and ready to use', '#f0fdf4', '#22c55e', '#166534') .
			self::h1('Your account is now active') .
			self::p('Hi ' . htmlspecialchars($client->owner_name) . ',') .
			self::p('Your PepBan account has been approved. You can now log in, download the plugin, and start protecting your store.') .
			self::btn($portal_url, 'Go to My Portal'),
			'#22c55e'
		);

		self::send($client->owner_email, 'PepBan — Your Account is Active', $plain, $html);
	}

	public static function passwordReset(string $email, string $token): void {
		$link = url('/reset-password') . '?token=' . urlencode($token);

		$plain =
			"Click the link below to reset your password (expires in 1 hour):\n\n" .
			"{$link}\n\n" .
			"If you didn't request this, ignore this email.\n\n" .
			"— PepBan";

		$html = self::wrap(
			self::h1('Reset your password') .
			self::p('You requested a password reset for your PepBan account. Click the button below to choose a new password.') .
			self::btn($link, 'Reset Password') .
			self::hr() .
			self::p('This link expires in <strong style="color:#111827">1 hour</strong>. If you didn&rsquo;t request this, your password has not changed &mdash; you can safely ignore this email.', 'font-size:13px;color:#6b7280')
		);

		self::send($email, 'PepBan — Password Reset', $plain, $html);
	}

	// ── Admin-only emails (plain text) ────────────────────────────────────────────

	public static function adminNewDispute(string $email, string $name, string $reason): void {
		$admin_url = rtrim(SITE_URL, '/') . '/admin/disputes';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Ban Dispute: ' . $email,
			"A customer has submitted a ban dispute.\n\n" .
			"Email:  {$email}\n" .
			"Name:   {$name}\n\n" .
			"Reason:\n{$reason}\n\n" .
			"Review in admin: {$admin_url}\n\n— PepBan"
		);
	}

	public static function adminNewBan(string $email, string $name, string $reason, string $reported_by): void {
		$admin_url = rtrim(SITE_URL, '/') . '/admin/banned';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Customer Banned: ' . $email,
			"A customer has been added to the ban list.\n\n" .
			"Email:       {$email}\n" .
			"Name:        {$name}\n" .
			"Reason:      {$reason}\n" .
			"Reported by: {$reported_by}\n\n" .
			"View in admin: {$admin_url}\n\n— PepBan"
		);
	}

	public static function storeAlert(string $to, string $customer_email, array $response, string $site_url): void {
		$customer     = $response['customer'] ?? [];
		$name         = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Unknown';
		$reason       = $customer['reason'] ?? 'N/A';
		$report_count = (int) ($response['report_count'] ?? 1);
		$store_count  = (int) ($response['store_count']  ?? 1);
		$site_name    = $site_url ?: 'your store';
		$orders_url   = rtrim(SITE_URL, '/') . '/portal';

		$plain =
			"A banned customer attempted checkout on {$site_name}.\n\n" .
			"Email:   {$customer_email}\n" .
			"Name:    {$name}\n" .
			"Reason:  {$reason}\n" .
			"Reports: {$report_count} report(s) across {$store_count} store(s)\n\n" .
			"PepBan portal: {$orders_url}\n\n— PepBan";

		$html =
			'<!DOCTYPE html><html><head><meta charset="UTF-8"></head>' .
			'<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px"><tr><td align="center">' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%">' .
			'<tr><td style="background:#0c0c1e;border-radius:10px 10px 0 0;padding:24px 36px">' .
			'<span style="font-size:22px;font-weight:800;color:#fff;letter-spacing:-.02em">Pep<span style="color:#dc2626">Ban</span></span>' .
			'<span style="float:right;background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.06em;text-transform:uppercase;margin-top:4px">Alert</span>' .
			'</td></tr>' .
			'<tr><td style="background:#fff;padding:36px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb">' .
			'<h2 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#111827">Banned customer attempted checkout</h2>' .
			'<p style="margin:0 0 24px;font-size:14px;color:#6b7280">on <strong style="color:#374151">' . htmlspecialchars($site_name) . '</strong></p>' .
			'<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px">' .
			'<tr style="background:#f9fafb"><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;width:130px">Email</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#111827;font-weight:600">' . htmlspecialchars($customer_email) . '</td></tr>' .
			'<tr><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;border-top:1px solid #f3f4f6">Name</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#374151;border-top:1px solid #f3f4f6">' . htmlspecialchars($name) . '</td></tr>' .
			'<tr style="background:#fef2f2"><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#dc2626;border-top:1px solid #fecaca">Reason</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#7f1d1d;border-top:1px solid #fecaca">' . htmlspecialchars($reason) . '</td></tr>' .
			'<tr><td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af;border-top:1px solid #f3f4f6">Reports</td>' .
			'<td style="padding:10px 16px;font-size:14px;color:#374151;border-top:1px solid #f3f4f6"><strong>' . $report_count . '</strong> report(s) across <strong>' . $store_count . '</strong> store(s)</td></tr>' .
			'</table>' .
			'<a href="' . htmlspecialchars($orders_url) . '" style="display:inline-block;background:#dc2626;color:#fff;padding:11px 22px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600">Go to PepBan Portal &rarr;</a>' .
			'</td></tr>' .
			'<tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 10px 10px;padding:16px 36px;text-align:center">' .
			'<p style="margin:0;font-size:12px;color:#9ca3af">Sent by PepBan. One alert per customer per hour.</p>' .
			'</td></tr></table></td></tr></table></body></html>';

		self::send($to, '[PepBan] Banned customer attempted checkout', $plain, $html);
	}

	public static function adminNewSignup(object $client): void {
		$admin_url = rtrim(SITE_URL, '/') . '/admin/clients';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Store Signup: ' . $client->owner_name,
			"A new store has signed up for PepBan.\n\n" .
			"Name:   {$client->owner_name}\n" .
			"Email:  {$client->owner_email}\n" .
			"Store:  {$client->site_url}\n" .
			"Status: {$client->subscription_status}\n\n" .
			"View in admin: {$admin_url}\n\n— PepBan"
		);
	}

	public static function adminFeedback(string $from_name, string $from_email, string $site, string $message): void {
		self::send(
			ADMIN_EMAIL,
			'PepBan Feedback from ' . $from_name,
			"New feedback submitted from your PepBan portal.\n\n" .
			"From:    {$from_name} <{$from_email}>\n" .
			"Store:   {$site}\n\n" .
			"Message:\n{$message}\n\n— PepBan"
		);
	}

	public static function adminError(string $subject, string $body): void {
		try {
			self::send(ADMIN_EMAIL, '[PepBan Error] ' . $subject, $body);
		} catch (Throwable $e) {}
	}
}
