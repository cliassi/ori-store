<?php

if (!function_exists('logPushDeliveryAttempt')) {
    function logPushDeliveryAttempt(
        $recipientType,
        $recipientId,
        $subscriptionUuid,
        $title,
        $message,
        $outcome,
        $httpStatus = null,
        $relayResponse = null,
        $errorMessage = null
    ) {
        global $c;

        if (!isset($c) || !is_object($c) || !method_exists($c, 'prepare')) {
            error_log('Push delivery log unavailable: database connection is missing.');
            return false;
        }

        $recipientType = in_array($recipientType, ['a', 'c'], true) ? $recipientType : null;
        $recipientId = $recipientId === null ? null : (int) $recipientId;
        $subscriptionUuid = $subscriptionUuid === null || $subscriptionUuid === ''
            ? null
            : substr((string) $subscriptionUuid, 0, 64);
        $title = substr((string) $title, 0, 255);
        $message = (string) $message;
        $outcome = substr((string) $outcome, 0, 32);
        $httpStatus = $httpStatus === null ? null : (int) $httpStatus;
        $relayResponse = $relayResponse === null
            ? null
            : substr((string) $relayResponse, 0, 32768);
        $errorMessage = $errorMessage === null
            ? null
            : substr((string) $errorMessage, 0, 4000);

        try {
            $statement = $c->prepare(
                'INSERT INTO push_delivery_log '
                . '(recipient_type, recipient_id, subscription_uuid, title, message, outcome, '
                . 'http_status, relay_response, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$statement) {
                error_log('Push delivery log prepare failed: ' . $c->error);
                return false;
            }

            $statement->bind_param(
                'sissssiss',
                $recipientType,
                $recipientId,
                $subscriptionUuid,
                $title,
                $message,
                $outcome,
                $httpStatus,
                $relayResponse,
                $errorMessage
            );
            $saved = $statement->execute();
            if (!$saved) {
                error_log('Push delivery log insert failed: ' . $statement->error);
            }
            $statement->close();

            return $saved;
        } catch (Throwable $error) {
            error_log('Push delivery log insert failed: ' . $error->getMessage());
            return false;
        }
    }
}

if (!function_exists('queuePushDelivery')) {
    function queuePushDelivery($recipientType, $recipientId, $subscriptionUuid, $title, $message)
    {
        global $c;

        if (!isset($c) || !is_object($c) || !method_exists($c, 'prepare')) {
            error_log('Push queue unavailable: database connection is missing.');
            return false;
        }

        $recipientType = in_array($recipientType, ['a', 'c'], true) ? $recipientType : null;
        $recipientId = $recipientId === null ? null : (int) $recipientId;
        $subscriptionUuid = substr(trim((string) $subscriptionUuid), 0, 64);
        if ($subscriptionUuid === '') {
            error_log('Push queue rejected a notification without a subscription UUID.');
            return false;
        }

        $title = substr((string) $title, 0, 255);
        $message = (string) $message;
        $payload = json_encode([
            'tag' => $subscriptionUuid,
            'message' => $message,
            'title' => $title,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            error_log('Push queue could not encode the notification payload.');
            return false;
        }

        try {
            $statement = $c->prepare(
                'INSERT INTO push_delivery_log '
                . '(recipient_type, recipient_id, subscription_uuid, title, message, payload, status, outcome) '
                . "VALUES (?, ?, ?, ?, ?, ?, 'pending', 'queued')"
            );
            if (!$statement) {
                error_log('Push queue prepare failed: ' . $c->error);
                return false;
            }

            $statement->bind_param(
                'sissss',
                $recipientType,
                $recipientId,
                $subscriptionUuid,
                $title,
                $message,
                $payload
            );
            $saved = $statement->execute();
            if (!$saved) {
                error_log('Push queue insert failed: ' . $statement->error);
            }
            $statement->close();

            return $saved ? (int) $c->insert_id : false;
        } catch (Throwable $error) {
            error_log('Push queue insert failed: ' . $error->getMessage());
            return false;
        }
    }
}

if (!function_exists('triggerPushQueueRunner')) {
    function triggerPushQueueRunner()
    {
        if (!function_exists('curl_init')) {
            error_log('Push queue runner was not triggered: cURL extension is unavailable.');
            return false;
        }

        $ch = curl_init('http://localhost:88/run.php');
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cache-Control: no-cache']);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log('Push queue runner request failed; queued rows remain pending: ' . $error);
            return false;
        }

        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpStatus < 200 || $httpStatus >= 300) {
            error_log("Push queue runner returned HTTP {$httpStatus}; queued rows remain pending.");
            return false;
        }

        return true;
    }
}
