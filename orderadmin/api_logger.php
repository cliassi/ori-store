<?php

function orderadminSanitizeApiLogValue($value, $key = '')
{
    if (is_string($key)) {
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key);
        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $key));
        if (preg_match('/(^|_)(password|passcode|pass|pin|token|jwt|authorization|bearer|secret|credential|otp|totp|api_key|device_id|session|cookie|csrf)(_|$)/', $key)) {
            return '[REDACTED]';
        }
    }

    if (is_object($value)) {
        $value = get_object_vars($value);
    }

    if (is_array($value)) {
        $sanitized = array();
        foreach ($value as $childKey => $childValue) {
            $sanitized[$childKey] = orderadminSanitizeApiLogValue($childValue, $childKey);
        }
        return $sanitized;
    }

    if (is_string($value) && strlen($value) > 4096) {
        return substr($value, 0, 4096) . '[TRUNCATED]';
    }

    if (is_resource($value)) {
        return '[UNSUPPORTED]';
    }

    return $value;
}

function orderadminLogApiRequest($rawBody, $query, $form)
{
    $data = array();
    if (is_array($query) && count($query) > 0) {
        $data['query'] = orderadminSanitizeApiLogValue($query);
    }
    if (is_array($form) && count($form) > 0) {
        $data['form'] = orderadminSanitizeApiLogValue($form);
    }

    if (is_string($rawBody) && $rawBody !== '') {
        if (strlen($rawBody) > 16384) {
            $data['body'] = array('omitted' => 'payload too large', 'bytes' => strlen($rawBody));
        } else {
            $decodedBody = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBody)) {
                $data['body'] = orderadminSanitizeApiLogValue($decodedBody);
            } else {
                $data['body'] = array(
                    'omitted' => 'non-object or invalid JSON payload',
                    'bytes' => strlen($rawBody),
                );
            }
        }
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/';
    $endpoint = is_string($uri) && $uri !== '' ? $uri : '/';
    foreach (array('action', 'page') as $routeKey) {
        if (isset($query[$routeKey]) && is_scalar($query[$routeKey])) {
            $routeValue = preg_replace('/[^a-z0-9_-]/i', '', (string) $query[$routeKey]);
            if ($routeValue !== '') {
                $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&')
                    . rawurlencode($routeKey) . '=' . rawurlencode($routeValue);
            }
        }
    }
    $record = array(
        'datetime_utc' => gmdate('c'),
        'caller_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown',
        'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown',
        'endpoint' => $endpoint,
        'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '',
        'data' => $data,
    );

    $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($line)) {
        error_log('Orderadmin API request log encoding failed');
        return;
    }
    if (strlen($line) > 32768) {
        $record['data'] = array('omitted' => 'sanitized payload too large');
        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($line)) {
            error_log('Orderadmin API request log encoding failed');
            return;
        }
    }

    $logDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDirectory) || !is_writable($logDirectory)) {
        error_log('Orderadmin API log directory is missing or not writable');
        return;
    }
    $logFile = $logDirectory . DIRECTORY_SEPARATOR . 'orderadmin-api-' . gmdate('Y-m-d') . '.jsonl';

    $previousUmask = umask(0077);
    $stream = @fopen($logFile, 'ab');
    umask($previousUmask);
    if ($stream === false) {
        error_log('Orderadmin API request log could not be opened');
        return;
    }

    if (!@chmod($logFile, 0600)) {
        $permissions = @fileperms($logFile);
        if ($permissions === false || ($permissions & 0077) !== 0) {
            fclose($stream);
            error_log('Orderadmin API request log permissions are too broad');
            return;
        }
    }
    if (!flock($stream, LOCK_EX)) {
        fclose($stream);
        error_log('Orderadmin API request log could not be locked');
        return;
    }

    $written = fwrite($stream, $line . PHP_EOL);
    fflush($stream);
    flock($stream, LOCK_UN);
    fclose($stream);
    if ($written === false) {
        error_log('Orderadmin API request log write failed');
    }
}
