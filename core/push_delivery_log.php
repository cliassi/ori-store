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
