<?php
defined('PEPBAN_VERSION') || die;

class Mailer {

	public static function send(string $to, string $subject, string $body): bool {
		if (defined('SMTP_HOST') && SMTP_HOST) {
			return self::smtp($to, $subject, $body);
		}
		$headers = implode("\r\n", [
			'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
			'Reply-To: ' . MAIL_FROM,
			'Content-Type: text/plain; charset=UTF-8',
		]);
		return mail($to, $subject, $body, $headers);
	}

	// Minimal SMTP client — handles STARTTLS and AUTH LOGIN.
	// No external libraries required.
	private static function smtp(string $to, string $subject, string $body): bool {
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
			error_log("PepBan SMTP: connection failed to {$addr} — {$errstr} ({$errno})");
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

		$read(); // 220 greeting

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
		if ((int)$r !== 334) { error_log("PepBan SMTP: AUTH LOGIN rejected — {$r}"); fclose($socket); return false; }
		$cmd(base64_encode(SMTP_USER));
		$r = $cmd(base64_encode(SMTP_PASS));
		if ((int)$r !== 235) { error_log("PepBan SMTP: authentication failed — {$r}"); fclose($socket); return false; }

		$cmd('MAIL FROM:<' . MAIL_FROM . '>');
		$r = $cmd('RCPT TO:<' . $to . '>');
		if ((int)$r > 299) { error_log("PepBan SMTP: recipient rejected ({$to}) — {$r}"); fclose($socket); return false; }

		// Headers + body
		$cmd('DATA');
		$date    = date('r');
		$msgId   = '<' . time() . '.' . rand(1000, 9999) . '@pepban.com>';
		$headers =
			"Date: {$date}\r\n" .
			"Message-ID: {$msgId}\r\n" .
			"From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n" .
			"To: {$to}\r\n" .
			"Subject: {$subject}\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"MIME-Version: 1.0\r\n";

		// Dot-stuffing: lines starting with '.' must be doubled
		$escaped = preg_replace('/^\.$/m', '..', $body);
		fwrite($socket, $headers . "\r\n" . $escaped . "\r\n.\r\n");

		$r = $read(); // 250 queued
		$cmd('QUIT');
		fclose($socket);

		if ((int)$r !== 250) {
			error_log("PepBan SMTP: message not queued for {$to} — {$r}");
			return false;
		}
		return true;
	}

	public static function adminNewDispute(string $email, string $name, string $reason): void {
		$admin_url = rtrim(SITE_URL, '/') . '/admin/disputes';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Ban Dispute: ' . $email,
			"A customer has submitted a ban dispute.\n\n" .
			"Email:  {$email}\n" .
			"Name:   {$name}\n\n" .
			"Reason:\n{$reason}\n\n" .
			"Review in admin: {$admin_url}\n\n" .
			"— PepBan"
		);
	}

	public static function customerBanned(string $email, string $name, string $reason): void {
		$dispute_url = rtrim(SITE_URL, '/') . '/dispute';
		$greeting    = $name ? "Hi {$name}," : 'Hello,';
		$sent = self::send(
			$email,
			'Important notice regarding your account',
			"{$greeting}\n\n" .
			"Your account has been flagged and you may be blocked from completing purchases at stores in the PepBan network.\n\n" .
			"Reason on file:\n  {$reason}\n\n" .
			"If you believe this is a mistake, you can submit a dispute for review:\n" .
			"  {$dispute_url}\n\n" .
			"— PepBan"
		);
		if (!$sent) {
			error_log("PepBan: customerBanned email failed for {$email}");
		}
	}

	public static function disputeResolved(string $email, string $name): void {
		self::send(
			$email,
			'PepBan — Your Dispute Has Been Reviewed',
			"Hi {$name},\n\n" .
			"We've reviewed your dispute and resolved it in your favor. Your account has been removed from the ban list and you should now be able to check out at member stores.\n\n" .
			"If you continue to experience issues, please contact us at " . SUPPORT_EMAIL . ".\n\n" .
			"— PepBan"
		);
	}

	public static function disputeDismissed(string $email, string $name): void {
		self::send(
			$email,
			'PepBan — Your Dispute Has Been Reviewed',
			"Hi {$name},\n\n" .
			"We've reviewed your dispute. After investigation, we were unable to remove the ban at this time.\n\n" .
			"If you have additional information to provide, please contact us at " . SUPPORT_EMAIL . ".\n\n" .
			"— PepBan"
		);
	}

	public static function adminError(string $subject, string $body): void {
		// Suppress all exceptions — error handler must never itself throw
		try {
			self::send(ADMIN_EMAIL, '[PepBan Error] ' . $subject, $body);
		} catch (Throwable $e) {}
	}

	public static function adminFeedback(string $from_name, string $from_email, string $site, string $message): void {
		self::send(
			ADMIN_EMAIL,
			'PepBan Feedback from ' . $from_name,
			"New feedback submitted from your PepBan portal.\n\n" .
			"From:    {$from_name} <{$from_email}>\n" .
			"Store:   {$site}\n\n" .
			"Message:\n{$message}\n\n" .
			"— PepBan"
		);
	}

	public static function adminNewBan(string $email, string $name, string $reason, string $reported_by): void {
		$admin_url = (defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://pepban.com') . '/admin/banned';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Customer Banned: ' . $email,
			"A customer has been added to the ban list.\n\n" .
			"Email:       {$email}\n" .
			"Name:        {$name}\n" .
			"Reason:      {$reason}\n" .
			"Reported by: {$reported_by}\n\n" .
			"View in admin: {$admin_url}\n\n" .
			"— PepBan"
		);
	}

	public static function adminNewSignup(object $client): void {
		$admin_url = (defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://pepban.com') . '/admin/clients';
		self::send(
			ADMIN_EMAIL,
			'PepBan — New Store Signup: ' . $client->owner_name,
			"A new store has signed up for PepBan.\n\n" .
			"Name:   {$client->owner_name}\n" .
			"Email:  {$client->owner_email}\n" .
			"Store:  {$client->site_url}\n" .
			"Status: {$client->subscription_status}\n\n" .
			"View in admin: {$admin_url}\n\n" .
			"— PepBan"
		);
	}

	public static function welcome(object $client, string $raw_key): void {
		$status = ($client->subscription_status === 'active')
			? "Your account is active and ready to use."
			: "Your account is pending admin approval. You'll get an email once it's activated.";

		$portal_url = url('/portal');

		self::send(
			$client->owner_email,
			'Welcome to PepBan — Your API Key',
			"Hi {$client->owner_name},\n\n" .
			"Thanks for signing up for PepBan!\n\n" .
			"{$status}\n\n" .
			"Your API key (copy and save this — it won't be shown again):\n\n" .
			"  {$raw_key}\n\n" .
			"Setup steps:\n" .
			"  1. Log in to your portal: {$portal_url}\n" .
			"  2. Download the PepBan Client plugin\n" .
			"  3. Install it on {$client->site_url} via Plugins > Add New > Upload Plugin\n" .
			"  4. Go to PepBan > Settings and enter:\n" .
			"       API Key:  {$raw_key}\n\n" .
			"— PepBan"
		);
	}

	public static function newKey(object $client, string $raw_key): void {
		self::send(
			$client->owner_email,
			'PepBan — Your New API Key',
			"Hi {$client->owner_name},\n\n" .
			"A new API key has been generated for your account.\n\n" .
			"  {$raw_key}\n\n" .
			"Update the API Key field in PepBan > Settings on {$client->site_url}.\n\n" .
			"— PepBan"
		);
	}

	public static function activated(object $client): void {
		self::send(
			$client->owner_email,
			'PepBan — Your Account is Active',
			"Hi {$client->owner_name},\n\n" .
			"Your PepBan account has been approved and is now active.\n\n" .
			"Log in to your portal to download the client plugin:\n" .
			url('/portal') . "\n\n" .
			"— PepBan"
		);
	}

	public static function passwordReset(string $email, string $token): void {
		$link = url('/reset-password') . '?token=' . urlencode($token);
		self::send(
			$email,
			'PepBan — Password Reset',
			"Click the link below to reset your password (expires in 1 hour):\n\n" .
			"{$link}\n\n" .
			"If you didn't request this, ignore this email.\n\n" .
			"— PepBan"
		);
	}
}
