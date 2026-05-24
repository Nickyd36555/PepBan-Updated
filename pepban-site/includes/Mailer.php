<?php
defined('PEPBAN_VERSION') || die;

class Mailer {

	public static function send(string $to, string $subject, string $body): bool {
		$headers = implode("\r\n", [
			'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
			'Reply-To: ' . MAIL_FROM,
			'Content-Type: text/plain; charset=UTF-8',
			'X-Mailer: PepBan/' . PEPBAN_VERSION,
		]);
		return mail($to, $subject, $body, $headers);
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
			"       Hub URL:  " . SITE_URL . "\n" .
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
