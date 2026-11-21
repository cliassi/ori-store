<?php

function orderadminAuthRequestValue($input, $post, $key)
{
    if (is_object($input) && isset($input->$key)) {
        return $input->$key;
    }

    if (is_object($post) && isset($post->$key)) {
        return $post->$key;
    }

    return null;
}

function orderadminAuthLogin($connection, $input, $post)
{
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return array(405, array('error' => 1, 'message' => 'POST required'));
    }

    $pin = orderadminAuthRequestValue($input, $post, 'password');
    $customerValue = orderadminAuthRequestValue($input, $post, 'is_customer');

    if (!is_string($pin) || trim($pin) === '' || strlen($pin) > 32) {
        return array(400, array('error' => 1, 'message' => 'Invalid request'));
    }

    if (is_bool($customerValue)) {
        $isCustomer = $customerValue;
    } elseif ($customerValue === 1 || $customerValue === '1' || $customerValue === 'true') {
        $isCustomer = true;
    } elseif ($customerValue === 0 || $customerValue === '0' || $customerValue === 'false') {
        $isCustomer = false;
    } else {
        return array(400, array('error' => 1, 'message' => 'Invalid request'));
    }

    if ($isCustomer) {
        $query = "SELECT id
                  FROM customer
                  WHERE password = ?
                    AND can_login = 1
                    AND login_time IS NOT NULL
                  LIMIT 1";
    } else {
        $query = "SELECT u.id
                  FROM sys_user u
                  INNER JOIN sys_user_role ur ON ur.ur_user_id = u.id
                  INNER JOIN sys_role r ON r.id = ur.ur_role_id
                  WHERE u.u_pin = ?
                    AND u.u_status = 1
                    AND r.r_active = 1
                    AND LOWER(r.r_name) <> 'customer'
                  LIMIT 1";
    }
    $pin = trim($pin);
    try {
        $lookup = $connection->prepare($query);
        if (!$lookup) {
            return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
        }
        $lookup->bind_param('s', $pin);
        if (!$lookup->execute()) {
            $lookup->close();
            return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
        }

        $result = $lookup->get_result();
        $user = $result ? $result->fetch_assoc() : false;
        $lookup->close();
    } catch (Throwable $error) {
        return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
    }

    if (!$user) {
        return array(401, array('error' => 1, 'message' => 'Invalid PIN'));
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Throwable $error) {
        return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
    }

    $userId = (int) $user['id'];
    try {
        $insert = $connection->prepare('INSERT INTO sys_token (user_id, token) VALUES (?, ?)');
        if (!$insert) {
            return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
        }
        $insert->bind_param('is', $userId, $token);
        $saved = $insert->execute();
        $insert->close();
    } catch (Throwable $error) {
        return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
    }
    if (!$saved) {
        return array(500, array('error' => 1, 'message' => 'Authentication unavailable'));
    }

    return array(200, array(
        'error' => 0,
        'message' => 'ok',
        'token' => $token,
        'user_id' => $userId,
    ));
}

function orderadminAuthLogout($connection)
{
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return array(405, array('error' => 1, 'message' => 'POST required'));
    }

    $authorization = isset($_SERVER['HTTP_AUTHORIZATION'])
        ? $_SERVER['HTTP_AUTHORIZATION']
        : (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $authorization = $value;
                break;
            }
        }
    }
    $token = is_string($authorization)
        && preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($authorization), $matches)
        ? $matches[1]
        : null;
    if ($token === null) {
        return array(401, array('error' => 1, 'message' => 'Bearer token required'));
    }

    try {
        $delete = $connection->prepare('DELETE FROM sys_token WHERE token = ?');
        if (!$delete) {
            return array(500, array('error' => 1, 'message' => 'Logout unavailable'));
        }
        $delete->bind_param('s', $token);
        $deleted = $delete->execute();
        $affectedRows = $delete->affected_rows;
        $delete->close();
    } catch (Throwable $error) {
        return array(500, array('error' => 1, 'message' => 'Logout unavailable'));
    }
    if (!$deleted) {
        return array(500, array('error' => 1, 'message' => 'Logout unavailable'));
    }
    if ($affectedRows < 1) {
        return array(401, array('error' => 1, 'message' => 'Invalid token'));
    }

    return array(200, array('error' => 0, 'message' => 'logged out'));
}
