<?php

function totp_random_bytes($length)
{
	if (!function_exists('openssl_random_pseudo_bytes')) {
		throw new RuntimeException('Secure random generation is unavailable.');
	}
	$strong = false;
	$bytes = openssl_random_pseudo_bytes($length, $strong);
	if ($bytes === false || !$strong) {
		throw new RuntimeException('Secure random generation failed.');
	}
	return $bytes;
}

function totp_base32_encode($data)
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$buffer = 0;
	$bits = 0;
	$output = '';
	for ($i = 0; $i < strlen($data); $i++) {
		$buffer = ($buffer << 8) | ord($data[$i]);
		$bits += 8;
		while ($bits >= 5) {
			$bits -= 5;
			$output .= $alphabet[($buffer >> $bits) & 31];
		}
		$buffer &= (1 << $bits) - 1;
	}
	if ($bits > 0) {
		$output .= $alphabet[($buffer << (5 - $bits)) & 31];
	}
	return $output;
}

function totp_base32_decode($secret)
{
	$secret = strtoupper(preg_replace('/[\s=-]/', '', $secret));
	if ($secret === '' || preg_match('/[^A-Z2-7]/', $secret)) {
		throw new InvalidArgumentException('Invalid Base32 secret.');
	}
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$buffer = 0;
	$bits = 0;
	$output = '';
	for ($i = 0; $i < strlen($secret); $i++) {
		$buffer = ($buffer << 5) | strpos($alphabet, $secret[$i]);
		$bits += 5;
		if ($bits >= 8) {
			$bits -= 8;
			$output .= chr(($buffer >> $bits) & 255);
		}
		$buffer &= (1 << $bits) - 1;
	}
	return $output;
}

function totp_generate_secret()
{
	return totp_base32_encode(totp_random_bytes(20));
}

function totp_code($secret, $timestamp)
{
	$key = totp_base32_decode($secret);
	$counter = (int) floor($timestamp / 30);
	$high = (int) floor($counter / 4294967296);
	// Avoid modulo here: on 32-bit PHP, 4294967296 becomes a float and is cast to zero.
	$low = (int) ($counter - ($high * 4294967296));
	$hash = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
	$offset = ord($hash[strlen($hash) - 1]) & 15;
	$binary = ((ord($hash[$offset]) & 127) << 24)
		| ((ord($hash[$offset + 1]) & 255) << 16)
		| ((ord($hash[$offset + 2]) & 255) << 8)
		| (ord($hash[$offset + 3]) & 255);
	return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
}

function totp_verify($secret, $code, $timestamp = null, $window = 1)
{
	if (!preg_match('/^\d{6}$/D', (string) $code)) {
		return false;
	}
	$timestamp = $timestamp === null ? time() : (int) $timestamp;
	$currentCounter = (int) floor($timestamp / 30);
	for ($offset = -$window; $offset <= $window; $offset++) {
		$counter = $currentCounter + $offset;
		if ($counter >= 0 && hash_equals(totp_code($secret, $counter * 30), (string) $code)) {
			return $counter;
		}
	}
	return false;
}

function totp_encryption_key()
{
	$encoded = getenv('TOTP_ENCRYPTION_KEY');
	if ($encoded === false && defined('TOTP_ENCRYPTION_KEY')) {
		$encoded = constant('TOTP_ENCRYPTION_KEY');
	}
	$key = is_string($encoded) ? base64_decode(trim($encoded), true) : false;
	if ($key === false || strlen($key) !== 32) {
		throw new RuntimeException('TOTP_ENCRYPTION_KEY must be a Base64-encoded 32-byte key.');
	}
	return $key;
}

function totp_encrypt_secret($secret)
{
	$key = totp_encryption_key();
	$encryptionKey = hash_hmac('sha256', 'ori-store-totp-v2-encryption', $key, true);
	$macKey = hash_hmac('sha256', 'ori-store-totp-v2-authentication', $key, true);
	$iv = totp_random_bytes(16);
	$ciphertext = openssl_encrypt($secret, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
	if ($ciphertext === false) {
		throw new RuntimeException('TOTP secret encryption failed.');
	}
	$mac = hash_hmac('sha256', "ori-store-totp-v2\0" . $iv . $ciphertext, $macKey, true);
	return 'v2:' . base64_encode($iv . $ciphertext . $mac);
}

function totp_decrypt_secret($encrypted)
{
	if (!is_string($encrypted)) {
		throw new RuntimeException('Unsupported encrypted TOTP secret.');
	}

	if (strpos($encrypted, 'v2:') === 0) {
		$payload = base64_decode(substr($encrypted, 3), true);
		if ($payload === false || strlen($payload) < 64 || (strlen($payload) - 48) % 16 !== 0) {
			throw new RuntimeException('Invalid encrypted TOTP secret.');
		}
		$iv = substr($payload, 0, 16);
		$ciphertext = substr($payload, 16, strlen($payload) - 48);
		$mac = substr($payload, -32);
		$key = totp_encryption_key();
		$encryptionKey = hash_hmac('sha256', 'ori-store-totp-v2-encryption', $key, true);
		$macKey = hash_hmac('sha256', 'ori-store-totp-v2-authentication', $key, true);
		$expectedMac = hash_hmac('sha256', "ori-store-totp-v2\0" . $iv . $ciphertext, $macKey, true);
		if (!hash_equals($expectedMac, $mac)) {
			throw new RuntimeException('TOTP secret authentication failed.');
		}
		$secret = openssl_decrypt($ciphertext, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
	} elseif (strpos($encrypted, 'v1:') === 0) {
		if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70100) {
			throw new RuntimeException('This PHP version cannot decrypt legacy TOTP secrets.');
		}
		$payload = base64_decode(substr($encrypted, 3), true);
		if ($payload === false || strlen($payload) < 29) {
			throw new RuntimeException('Invalid encrypted TOTP secret.');
		}
		$nonce = substr($payload, 0, 12);
		$tag = substr($payload, 12, 16);
		$ciphertext = substr($payload, 28);
		$secret = openssl_decrypt($ciphertext, 'aes-256-gcm', totp_encryption_key(), OPENSSL_RAW_DATA, $nonce, $tag, 'ori-store-totp-v1');
	} else {
		throw new RuntimeException('Unsupported encrypted TOTP secret.');
	}

	if ($secret === false) {
		throw new RuntimeException('TOTP secret decryption failed.');
	}
	return $secret;
}

function totp_step_up_account($userId)
{
	global $c;
	$stmt = $c->prepare("SELECT COALESCE(totp_enabled, 0) AS totp_enabled, u_totp_secret FROM sys_user WHERE id = ? AND u_status = 1 LIMIT 1");
	if (!$stmt) {
		return false;
	}
	$stmt->bind_param('i', $userId);
	if (!$stmt->execute()) {
		$stmt->close();
		return false;
	}
	$result = $stmt->get_result();
	$account = $result ? $result->fetch_object() : false;
	$stmt->close();
	return $account;
}

function totp_step_up_hidden($name, $value)
{
	if (is_array($value)) {
		foreach ($value as $key => $item) {
			totp_step_up_hidden($name . '[' . $key . ']', $item);
		}
		return;
	}
	if (is_scalar($value)) {
		print "<input type='hidden' name='" . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . "' value='" . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . "' />";
	}
}

function totp_step_up_render($pending, $message)
{
	global $post;
	$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	print "<div class='container' style='max-width:480px;margin:40px auto;'><div class='card'><div class='card-body'>";
	print "<h4>Confirm this sensitive action</h4><p>Enter your Google Authenticator code to continue.</p>";
	if ($message !== '') {
		print "<p class='text-danger'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
	}
	if ($pending) {
		print "<form method='post' action='" . htmlspecialchars($requestUri, ENT_QUOTES, 'UTF-8') . "'>";
		foreach (get_object_vars($post) as $name => $value) {
			if (strpos($name, 'totp_stepup_') !== 0) {
				totp_step_up_hidden($name, $value);
			}
		}
		print "<div class='mb-3'><input class='form-control' type='text' name='totp_stepup_code' inputmode='numeric' autocomplete='one-time-code' pattern='[0-9]{6}' maxlength='6' required placeholder='6-digit code' /></div>";
		print "<input type='hidden' name='totp_stepup_csrf' value='" . htmlspecialchars($pending['csrf'], ENT_QUOTES, 'UTF-8') . "' />";
		print "<button class='btn btn-primary' type='submit' name='totp_stepup_submit' value='1'>Verify and continue</button></form>";
	}
	print "</div></div></div>";
}

function totp_require_step_up($action, $target)
{
	global $post;
	$userId = (int) uid();
	$account = totp_step_up_account($userId);
	if (!$account) {
		totp_step_up_render(false, 'Authenticator verification is unavailable. The action was not performed.');
		return false;
	}
	if ((int) $account->totp_enabled !== 1) {
		return true;
	}
	if (empty($_SESSION[APP . '_totp_verified']) || (int) (isset($_SESSION[APP . '_totp_verified_user']) ? $_SESSION[APP . '_totp_verified_user'] : 0) !== $userId) {
		totp_step_up_render(false, 'Please sign in again to verify your authenticator.');
		return false;
	}
	if (!is_string($account->u_totp_secret) || $account->u_totp_secret === '') {
		totp_step_up_render(false, 'Authenticator enrollment is unavailable. The action was not performed.');
		return false;
	}
	$context = hash('sha256', $userId . "\0" . (string) $action . "\0" . (string) $target);
	$pendingKey = APP . '_totp_stepup_pending';
	$pending = isset($_SESSION[$pendingKey]) && is_array($_SESSION[$pendingKey]) ? $_SESSION[$pendingKey] : null;
	$message = '';
	$showForm = true;
	if (isset($post->totp_stepup_submit)) {
		if (!$pending || !isset($pending['context'], $pending['csrf'], $pending['expires'], $pending['attempts']) || !hash_equals($pending['context'], $context) || $pending['expires'] < time() || !isset($post->totp_stepup_csrf) || !hash_equals($pending['csrf'], (string) $post->totp_stepup_csrf)) {
			unset($_SESSION[$pendingKey]);
			$pending = null;
			$message = 'The verification request expired. Please try again.';
		} elseif ($pending['attempts'] >= 5) {
			unset($_SESSION[$pendingKey]);
			$message = 'Too many invalid codes. Reload the action and try again.';
			$showForm = false;
		} else {
			try {
				$secret = totp_decrypt_secret($account->u_totp_secret);
				$code = isset($post->totp_stepup_code) && is_string($post->totp_stepup_code) ? $post->totp_stepup_code : '';
				$counter = totp_verify($secret, $code);
				$lastCounter = isset($_SESSION[APP . '_totp_stepup_last_counter']) ? (int) $_SESSION[APP . '_totp_stepup_last_counter'] : -1;
				if ($counter !== false && $counter > $lastCounter) {
					$_SESSION[APP . '_totp_stepup_last_counter'] = $counter;
					unset($_SESSION[$pendingKey]);
					return true;
				}
				$pending['attempts']++;
				if ($pending['attempts'] >= 5) {
					unset($_SESSION[$pendingKey]);
					$message = 'Too many invalid codes. Reload the action and try again.';
					$showForm = false;
				} else {
					$_SESSION[$pendingKey] = $pending;
					$message = 'Invalid authenticator code. Please try again.';
				}
			} catch (Throwable $error) {
				unset($_SESSION[$pendingKey]);
				$message = 'Authenticator verification is unavailable. The action was not performed.';
				$showForm = false;
			}
		}
	}
	if ($showForm) {
		if (!$pending || !isset($pending['context']) || !hash_equals($pending['context'], $context) || !isset($pending['expires']) || $pending['expires'] < time()) {
			try {
				$pending = array(
					'context' => $context,
					'csrf' => bin2hex(totp_random_bytes(32)),
					'expires' => time() + 300,
					'attempts' => 0
				);
				$_SESSION[$pendingKey] = $pending;
			} catch (Throwable $error) {
				$pending = null;
				$message = 'Authenticator verification is unavailable. The action was not performed.';
			}
		}
	} else {
		$pending = null;
	}
	totp_step_up_render($pending, $message);
	return false;
}
