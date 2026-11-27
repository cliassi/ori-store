<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function pushQueueRespond($statusCode, array $body)
{
    http_response_code($statusCode);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    pushQueueRespond(405, ['ok' => false, 'error' => 'GET required']);
}

$remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($remoteAddress !== '' && !in_array($remoteAddress, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    pushQueueRespond(403, ['ok' => false, 'error' => 'Local requests only']);
}

require_once __DIR__ . '/onesignal_client.php';

global $c, $config;
if (!isset($c) || !is_object($c) || !method_exists($c, 'prepare')) {
    if (
        !isset($config->db_host, $config->db_user, $config->db_pass, $config->db_name, $config->db_port)
        || !function_exists('mysqli_connect')
    ) {
        error_log('Push queue runner cannot initialize its database connection.');
        pushQueueRespond(500, ['ok' => false, 'error' => 'Database unavailable']);
    }

    $c = mysqli_connect(
        $config->db_host,
        $config->db_user,
        $config->db_pass,
        $config->db_name,
        (int) $config->db_port
    );
}

if (!$c || (isset($c->connect_errno) && $c->connect_errno)) {
    error_log('Push queue runner database connection failed.');
    pushQueueRespond(500, ['ok' => false, 'error' => 'Database unavailable']);
}

$c->set_charset('utf8mb4');
$c->query("SET time_zone = '+08:00'");

function pushQueueAddAdminTestRows()
{
    global $c;

    $subscriptions = $c->query(
        "SELECT MIN(id) AS recipient_id, uuid "
        . "FROM push_client WHERE `type` = 'a' AND uuid <> '' GROUP BY uuid"
    );
    if (!$subscriptions) {
        throw new RuntimeException('Could not read admin subscriptions: ' . $c->error);
    }

    $statement = $c->prepare(
        'INSERT INTO push_delivery_log '
        . '(recipient_type, recipient_id, subscription_uuid, title, message, payload, status, outcome) '
        . "VALUES ('a', ?, ?, ?, ?, ?, 'pending', 'queued')"
    );
    if (!$statement) {
        throw new RuntimeException('Could not prepare admin test push rows: ' . $c->error);
    }

    $title = 'ORI Push Test';
    $message = 'This is a test push notification to confirm admin push delivery.';
    $queueIds = [];
    while ($subscription = $subscriptions->fetch_assoc()) {
        $recipientId = (int) $subscription['recipient_id'];
        $uuid = substr(trim((string) $subscription['uuid']), 0, 64);
        $payload = json_encode([
            'tag' => $uuid,
            'message' => $message,
            'title' => $title,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($uuid === '' || $payload === false) {
            continue;
        }

        $statement->bind_param('issss', $recipientId, $uuid, $title, $message, $payload);
        if (!$statement->execute()) {
            error_log('Could not queue admin test push: ' . $statement->error);
            continue;
        }
        $queueIds[] = (int) $c->insert_id;
    }
    $statement->close();
    $subscriptions->free();

    return $queueIds;
}

$isTestRequest = isset($_GET['test']);
$testQueueIds = [];
if ($isTestRequest) {
    try {
        $testQueueIds = pushQueueAddAdminTestRows();
    } catch (Throwable $error) {
        error_log('Admin push test setup failed: ' . $error->getMessage());
        pushQueueRespond(500, ['ok' => false, 'error' => 'Could not queue admin test']);
    }
    if (!$testQueueIds) {
        pushQueueRespond(200, ['ok' => true, 'test' => true, 'queued' => 0, 'sent' => 0, 'failed' => 0]);
    }
}

function pushQueueFinishRow($queueId, $status, $outcome, $response, $errorMessage)
{
    global $c;

    $response = $response === null ? null : substr((string) $response, 0, 32768);
    $errorMessage = $errorMessage === null ? null : substr((string) $errorMessage, 0, 4000);
    try {
        $statement = $c->prepare(
            'UPDATE push_delivery_log '
            . 'SET status = ?, outcome = ?, http_status = NULL, relay_response = ?, error_message = ? '
            . 'WHERE id = ? AND status = \'processing\''
        );
        if (!$statement) {
            error_log('Push queue result update prepare failed: ' . $c->error);
            return false;
        }

        $statement->bind_param('ssssi', $status, $outcome, $response, $errorMessage, $queueId);
        $saved = $statement->execute();
        if (!$saved) {
            error_log('Push queue result update failed: ' . $statement->error);
        }
        $statement->close();
        return $saved;
    } catch (Throwable $error) {
        error_log('Push queue result update failed: ' . $error->getMessage());
        return false;
    }
}

try {
    if (!$c->begin_transaction()) {
        throw new RuntimeException('Could not start queue transaction.');
    }

    if ($isTestRequest) {
        $testIdList = implode(',', array_map('intval', $testQueueIds));
        $selectSql = "SELECT id, payload FROM push_delivery_log "
            . "WHERE status = 'pending' AND id IN ({$testIdList}) ORDER BY id ASC FOR UPDATE";
    } else {
        $selectSql = "SELECT id, payload FROM push_delivery_log "
            . "WHERE status = 'pending' ORDER BY id ASC LIMIT 50 FOR UPDATE";
    }
    $result = $c->query($selectSql);
    if (!$result) {
        throw new RuntimeException('Could not read pending push rows: ' . $c->error);
    }

    $queueRows = [];
    while ($row = $result->fetch_assoc()) {
        $queueRows[] = $row;
    }
    $result->free();

    $claim = $c->prepare(
        "UPDATE push_delivery_log SET status = 'processing', outcome = 'processing' "
        . "WHERE id = ? AND status = 'pending'"
    );
    if (!$claim) {
        throw new RuntimeException('Could not prepare queue claim: ' . $c->error);
    }

    foreach ($queueRows as $row) {
        $queueId = (int) $row['id'];
        $claim->bind_param('i', $queueId);
        if (!$claim->execute() || $claim->affected_rows !== 1) {
            throw new RuntimeException('Could not claim push queue row ' . $queueId . '.');
        }
    }
    $claim->close();

    if (!$c->commit()) {
        throw new RuntimeException('Could not commit queue claim.');
    }
} catch (Throwable $error) {
    if (isset($c) && method_exists($c, 'rollback')) {
        $c->rollback();
    }
    error_log('Push queue claim failed: ' . $error->getMessage());
    pushQueueRespond(500, ['ok' => false, 'error' => 'Queue unavailable']);
}

$sent = 0;
$failed = 0;
foreach ($queueRows as $row) {
    $queueId = (int) $row['id'];
    $payload = json_decode((string) $row['payload'], true);
    if (
        !is_array($payload)
        || empty($payload['tag'])
        || !isset($payload['message'])
        || trim((string) $payload['message']) === ''
    ) {
        pushQueueFinishRow($queueId, 'failed', 'invalid_payload', null, 'Queue payload is missing tag or message.');
        $failed++;
        continue;
    }

    try {
        $result = sendPush(
            (string) $payload['tag'],
            (string) $payload['message'],
            isset($payload['title']) ? (string) $payload['title'] : 'ORI'
        );
        $response = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $errors = isset($result['errors']) ? $result['errors'] : null;
        if (empty($errors)) {
            pushQueueFinishRow($queueId, 'sent', 'onesignal_accepted', $response, null);
            $sent++;
        } else {
            $errorMessage = is_string($errors)
                ? $errors
                : json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            pushQueueFinishRow($queueId, 'failed', 'onesignal_rejected', $response, $errorMessage);
            $failed++;
        }
    } catch (Throwable $error) {
        error_log('Push queue delivery failed for row ' . $queueId . ': ' . $error->getMessage());
        pushQueueFinishRow($queueId, 'failed', 'send_error', null, $error->getMessage());
        $failed++;
    }
}

pushQueueRespond(200, [
    'ok' => true,
    'test' => $isTestRequest,
    'claimed' => count($queueRows),
    'sent' => $sent,
    'failed' => $failed,
]);