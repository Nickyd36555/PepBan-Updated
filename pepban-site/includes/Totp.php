<?php
defined('PEPBAN_VERSION') || die;

/**
 * Pure PHP RFC 6238 TOTP (Time-based One-Time Password) — no dependencies.
 */
class Totp {

	private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	public static function generateSecret(int $bytes = 20): string {
		return self::base32Encode(random_bytes($bytes));
	}

	public static function getUri(string $secret, string $label, string $issuer): string {
		return 'otpauth://totp/' . rawurlencode($label)
			. '?secret=' . $secret
			. '&issuer=' . rawurlencode($issuer)
			. '&digits=6&period=30';
	}

	/**
	 * Verify a 6-digit code. Window of ±1 step (±30 s) accommodates clock skew.
	 */
	public static function verify(string $secret, string $code, int $window = 1): bool {
		$code = preg_replace('/\D/', '', $code);
		if (strlen($code) !== 6) return false;
		$key  = self::base32Decode($secret);
		$step = (int) floor(time() / 30);
		for ($i = -$window; $i <= $window; $i++) {
			if (self::hotp($key, $step + $i) === $code) return true;
		}
		return false;
	}

	// ── Internals ─────────────────────────────────────────────────────────────

	private static function hotp(string $key, int $counter): string {
		// 8-byte big-endian counter — use two 32-bit packs to avoid 32-bit PHP issues
		$msg  = pack('N', 0) . pack('N', $counter);
		$hash = hash_hmac('sha1', $msg, $key, true);
		$off  = ord($hash[19]) & 0x0F;
		$otp  = (
			((ord($hash[$off])     & 0x7F) << 24) |
			((ord($hash[$off + 1]) & 0xFF) << 16) |
			((ord($hash[$off + 2]) & 0xFF) <<  8) |
			 (ord($hash[$off + 3]) & 0xFF)
		) % 1_000_000;
		return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
	}

	private static function base32Encode(string $data): string {
		$alpha  = self::ALPHABET;
		$result = '';
		$buf    = 0;
		$bits   = 0;
		for ($i = 0; $i < strlen($data); $i++) {
			$buf  = ($buf << 8) | ord($data[$i]);
			$bits += 8;
			while ($bits >= 5) {
				$bits   -= 5;
				$result .= $alpha[($buf >> $bits) & 31];
			}
		}
		if ($bits > 0) {
			$result .= $alpha[($buf << (5 - $bits)) & 31];
		}
		return $result;
	}

	private static function base32Decode(string $data): string {
		$data   = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $data));
		$map    = array_flip(str_split(self::ALPHABET));
		$result = '';
		$buf    = 0;
		$bits   = 0;
		for ($i = 0; $i < strlen($data); $i++) {
			$buf  = ($buf << 5) | ($map[$data[$i]] ?? 0);
			$bits += 5;
			if ($bits >= 8) {
				$bits   -= 8;
				$result .= chr(($buf >> $bits) & 0xFF);
			}
		}
		return $result;
	}
}
