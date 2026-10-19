<?php
require_once __DIR__ . '/../../core/totp.php';
error_reporting(E_ALL);
header('Cache-Control: no-store, private');
$msg = "";
$pendingKey = APP . '_totp_pending';
$messageKey = APP . '_totp_message';
if(isset($get->logout)){
  if (session_status() == PHP_SESSION_NONE) {
      session_start();
  }
  $_SESSION[ROOT.'_loggedin'] = false;
  unset($_SESSION[ROOT.'_loggedin']);
  unset($_SESSION['branch_id']);
  unset($_SESSION['branch_name']);
  unset($_SESSION[$pendingKey], $_SESSION[$messageKey], $_SESSION[APP . '_totp_verified'], $_SESSION[APP . '_totp_verified_user']);
  unset($_SESSION);
  redir(ROOT);
}
if (isset($get->cancel_totp)) {
  unset(
    $_SESSION[$pendingKey],
    $_SESSION[$messageKey],
    $_SESSION[ROOT . '_loggedin'],
    $_SESSION[ROOT . '_username'],
    $_SESSION['branch_id'],
    $_SESSION['branch_name'],
    $_SESSION[APP . '_id'],
    $_SESSION[APP . '_role'],
    $_SESSION[APP . '_rolecode'],
    $_SESSION[APP . '_role_name'],
    $_SESSION[APP . '_fullname'],
    $_SESSION[APP . '_pin'],
    $_SESSION[APP . '_totp_verified'],
    $_SESSION[APP . '_totp_verified_user']
  );
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
  }
}
if (isset($_SESSION[$messageKey])) {
  $msg = (string) $_SESSION[$messageKey];
  unset($_SESSION[$messageKey]);
}
if (isset($_SESSION[$pendingKey])) {
  $pending = $_SESSION[$pendingKey];
  if (!is_array($pending) || !isset($pending['expires']) || $pending['expires'] < time()) {
    unset($_SESSION[$pendingKey]);
    $msg = 'Your authentication session expired. Please sign in again.';
  } elseif ($pending['stage'] === 'enroll' && (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') === 'GET') {
    if (!empty($pending['rendered'])) {
      unset($_SESSION[$pendingKey]);
      $msg = 'Enrollment was cleared because the setup page was refreshed. Please sign in again.';
    } else {
      $pending['rendered'] = true;
      $_SESSION[$pendingKey] = $pending;
    }
  }
}
if (isset($post->email) && is_string($post->email) && isset($post->password) && is_string($post->password) && isset($post->login)) {
  //$url = BASEURL."/framework/home";
  $url = 'home';
  if (isset($post->url) && is_string($post->url) && preg_match('/^[A-Za-z0-9_\/.-]+$/D', $post->url)) {
    $url = ROOT . $post->url;
  }
  //die($url);
  $user = _checkLogin($post->email, md5($post->password));
  if ($user) {
    try {
      if ((int) $user->totp_enabled === 1) {
        if (!session_regenerate_id(true)) {
          throw new RuntimeException('Session renewal failed.');
        }
        $pending = array(
          'user_id' => (int) $user->id,
          'username' => $user->u_username,
          'stage' => !empty($user->totp_configured) ? 'verify' : 'enroll',
          'expires' => time() + 600,
          'attempts' => 0,
          'rendered' => false,
          'csrf' => bin2hex(totp_random_bytes(32)),
          'return_to' => $url
        );
        if ($pending['stage'] === 'enroll') {
          $pending['secret'] = totp_generate_secret();
        }
        $_SESSION[$pendingKey] = $pending;
        unset($_SESSION[APP . '_id'], $_SESSION[APP . '_role'], $_SESSION[APP . '_rolecode'], $_SESSION[APP . '_role_name'], $_SESSION[APP . '_fullname'], $_SESSION[APP . '_pin'], $_SESSION[APP . '_totp_verified'], $_SESSION[APP . '_totp_verified_user']);
        _redirectTotpLogin();
      } elseif (!_completeTotpLogin($user->id, $url, 0)) {
        $msg = 'Your account is not available. Please sign in again.';
      }
    } catch (Throwable $error) {
      unset($_SESSION[$pendingKey]);
      $msg = 'Authenticator setup is unavailable. Please contact an administrator.';
    }
  } else {
    $msg = 'Failed to login! Please retry with right credentials...';
    //redir($url);
  }
}
if (isset($_SESSION[$pendingKey]) && isset($post->totp_submit)) {
  $pending = $_SESSION[$pendingKey];
  if (!isset($post->totp_csrf) || !hash_equals($pending['csrf'], (string) $post->totp_csrf)) {
    unset($_SESSION[$pendingKey]);
    $_SESSION[$messageKey] = 'The verification request expired. Please sign in again.';
    _redirectTotpLogin();
  } else {
    try {
      if ($pending['stage'] === 'enroll') {
        $secret = $pending['secret'];
      } else {
        $account = _loadTotpAccount($pending['user_id']);
        if (!$account || (int) $account->totp_enabled !== 1 || empty($account->u_totp_secret)) {
          throw new RuntimeException('Authenticator enrollment is unavailable.');
        }
        $secret = totp_decrypt_secret($account->u_totp_secret);
      }
      $code = isset($post->totp_code) && is_string($post->totp_code) ? $post->totp_code : '';
      $counter = totp_verify($secret, $code);
      if ($counter === false) {
        $pending['attempts']++;
        if ($pending['attempts'] >= 5) {
          unset($_SESSION[$pendingKey]);
          $_SESSION[$messageKey] = 'Too many invalid codes. Please sign in again.';
        } else {
          $pending['rendered'] = false;
          $_SESSION[$pendingKey] = $pending;
          $_SESSION[$messageKey] = 'Invalid authenticator code. Please try again.';
        }
        _redirectTotpLogin();
      } else {
        if ($pending['stage'] === 'enroll' && !_storeTotpSecret($pending['user_id'], $secret)) {
          throw new RuntimeException('Authenticator enrollment could not be saved.');
        }
        $_SESSION[APP . '_totp_stepup_last_counter'] = $counter;
        if (!_completeTotpLogin($pending['user_id'], $pending['return_to'], 1)) {
          unset($_SESSION[$pendingKey]);
          $_SESSION[$messageKey] = 'Your account is not available. Please sign in again.';
          _redirectTotpLogin();
        }
      }
    } catch (Throwable $error) {
      error_log('TOTP verification failed at stage ' . (isset($pending['stage']) ? $pending['stage'] : 'unknown') . ' (' . get_class($error) . '): ' . $error->getMessage());
      unset($_SESSION[$pendingKey]);
      $_SESSION[$messageKey] = 'Authenticator verification is unavailable. Please contact an administrator.';
      _redirectTotpLogin();
    }
  }
}
if(!loggedin() && !isset($post->recover)):
?>
<?php if (isset($_SESSION[$pendingKey])): ?>
<?php $pending = $_SESSION[$pendingKey]; ?>
<form method='post'>
  <div class='auth-main'>
    <div class='auth-wrapper v1'>
      <div class='auth-form'>
        <div class='card my-5'>
          <div class='card-body'>
            <h4 class='text-center f-w-500 mb-3'><?php echo $pending['stage'] === 'enroll' ? 'Set up Google Authenticator' : 'Enter your authenticator code'; ?></h4>
            <?php if ($pending['stage'] === 'enroll'): ?>
              <?php
              $issuer = 'ORI Store';
              $otpAuthLabel = rawurlencode($issuer) . ':' . rawurlencode($pending['username']);
              $otpAuthUri = 'otpauth://totp/' . $otpAuthLabel . '?secret=' . rawurlencode($pending['secret']) . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
              ?>
              <p>In Google Authenticator, tap + and choose Scan a QR code. If you cannot scan it, use the account label and setup key below, with Time based and 6-digit codes.</p>
              <p><strong>Account:</strong> <?php echo htmlspecialchars($pending['username'], ENT_QUOTES, 'UTF-8'); ?></p>
              <div id='totp-qr' class='text-center mb-3' aria-label='Google Authenticator setup QR code'></div>
              <script src='https://cdn.jsdelivr.net/npm/qrcode-generator@2.0.4/dist/qrcode.js' integrity='sha384-e9EFD6BGC90bkW9aDV5xbbBfzwN7G8YImHao2lfLVKV/hPB0E0go+H3I64h7oHtA' crossorigin='anonymous'></script>
              <script>
                (function () {
                  var container = document.getElementById('totp-qr');
                  if (typeof qrcode !== 'function') {
                    container.textContent = 'QR code unavailable. Enter the setup key below.';
                    return;
                  }
                  var qr = qrcode(0, 'M');
                  qr.addData(<?php echo json_encode($otpAuthUri, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
                  qr.make();
                  var image = new Image();
                  image.alt = 'Google Authenticator setup QR code';
                  image.src = qr.createDataURL(5, 4);
                  container.appendChild(image);
                })();
              </script>
              <p><strong>Setup key:</strong> <code><?php echo htmlspecialchars($pending['secret'], ENT_QUOTES, 'UTF-8'); ?></code></p>
            <?php endif; ?>
            <?php if ($msg !== ''): ?>
              <p class='text-danger'><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <div class='mb-3'>
              <input type='text' class='form-control' name='totp_code' inputmode='numeric' autocomplete='one-time-code' pattern='[0-9]{6}' maxlength='6' required placeholder='6-digit code' />
            </div>
            <input type='hidden' name='totp_csrf' value='<?php echo htmlspecialchars($pending['csrf'], ENT_QUOTES, 'UTF-8'); ?>' />
            <div class='d-grid mt-4'>
              <button type='submit' name='totp_submit' class='btn btn-primary' value='1'>Verify</button>
            </div>
            <div class='d-grid mt-2'>
              <a href='?q=auth/login&amp;cancel_totp=1' class='btn btn-outline-secondary'>Back to login / switch user</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<?php else: ?>
<form method='post'>
  <div class='auth-main'>
    <div class='auth-wrapper v1'>
      <div class='auth-form'>
        <div class='card my-5'>
          <div class='card-body'>
            <h4 class='text-center f-w-500 mb-3'>Login with your username</h4>
            <?php if ($msg !== ''): ?>
              <p class='text-danger'><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <div class='mb-3'>
              <input class='form-control' id='floatingInput' name='email' placeholder='username' />
            </div>
            <div class='mb-3'>
              <input type='password' class='form-control' name='password' id='floatingInput1' placeholder='Password' />
            </div>
              <!-- 
              <div class='d-flex mt-1 justify-content-between align-items-center'>
                <div class='form-check'>
                  <input class='form-check-input input-primary' type='checkbox' id='customCheckc1' checked='' />
                  <label class='form-check-label text-muted' for='customCheckc1'>Remember me?</label>
                </div>
                <h6 class='text-secondary f-w-400 mb-0'>
                  <a href='forgot-password-v1.html'> Forgot Password? </a>
                </h6>
              </div> 
            -->
            <div class='d-grid mt-4'>
              <button type='submit' name="login" class='btn btn-primary' value="login">Login</button>
            </div>
              <!-- 
              <div class='d-flex justify-content-between align-items-end mt-4'>
                <h6 class='f-w-500 mb-0'>Don't have an Account?</h6>
                <a href='register-v1.html' class='link-primary'>Create Account</a>
              </div> 
            -->
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<?php endif; ?>


<?php
endif; 


function _checkLogin($username, $password)
{
  global $c;
  $stmt = $c->prepare("SELECT u.id, u.u_username, u.u_fullname, u.u_pin, r.id AS role, r.r_name AS role_name, r.code, COALESCE(u.totp_enabled, 0) AS totp_enabled, (u.u_totp_secret IS NOT NULL AND u.u_totp_secret <> '') AS totp_configured FROM sys_role r, sys_user_role ur, sys_user u WHERE u.u_username = ? AND u.u_password = ? AND r.id = ur.ur_role_id AND u.id = ur.ur_user_id AND u.u_status = 1 AND r.r_active = 1 LIMIT 1");
  if (!$stmt) {
    error_log('Login lookup prepare failed (' . $c->errno . '): ' . $c->error);
    return false;
  }
  $stmt->bind_param('ss', $username, $password);
  if (!$stmt->execute()) {
    error_log('Login lookup execute failed (' . $stmt->errno . '): ' . $stmt->error);
    $stmt->close();
    return false;
  }
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_object() : false;
  $stmt->close();
  if ($row) {
    //setcookie("show", 1);
    return $row;
  }
  $failed = $c->prepare("SELECT u.id, u.u_failed_attempt, u.u_status FROM sys_user_role ur, sys_user u WHERE u.id = ur.ur_user_id AND u.u_username = ? AND u.u_status = 1 LIMIT 1");
  if ($failed) {
    $failed->bind_param('s', $username);
    $failed->execute();
    $failedResult = $failed->get_result();
    if ($failedResult && $failedResult->num_rows > 0) {
      $user = $failedResult->fetch_object();
      if ($user->u_failed_attempt < 3 && (int) $user->u_status === 1) {
        $failed_attempt = (int) $user->u_failed_attempt + 1;
        $attempts = $c->prepare("UPDATE sys_user SET u_failed_attempt = ? WHERE id = ?");
        if ($attempts) {
          $attempts->bind_param('ii', $failed_attempt, $user->id);
          $attempts->execute();
          $attempts->close();
        }
        // update("sys_user", "u_failed_attempt=$failed_attempt", "`u_username` = '{$username}'");
      } elseif ($user->u_failed_attempt >= 3 && (int) $user->u_status === 1) {
        // update("sys_user", "u_status = 0", "`u_username` = '{$username}'");
      }
    } elseif ($failedResult && $failedResult->num_rows === 0) {
      $attemptCount = isset($_SESSION[APP . '_attempts_count']) ? (int) $_SESSION[APP . '_attempts_count'] + 1 : 1;
      $_SESSION[APP . '_attempts_count'] = $attemptCount;
      $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
      $fraud = $c->prepare("INSERT INTO sys_fraud_user (f_username, f_password, f_ip, f_attempts, f_date) VALUES (?, ?, ?, ?, NOW())");
      if ($fraud) {
        $fraud->bind_param('sssi', $username, $password, $ip, $attemptCount);
        $fraud->execute();
        $fraud->close();
      }
    }
    $failed->close();
  }
  return false;
}

function _redirectTotpLogin()
{
  header('Location: ' . rtrim(ROOT, '/') . '/?q=auth/login', true, 303);
  exit;
}

function _loadTotpAccount($userId)
{
  global $c;
  $stmt = $c->prepare("SELECT u_totp_secret, COALESCE(totp_enabled, 0) AS totp_enabled FROM sys_user WHERE id = ? AND u_status = 1");
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $account = $result ? $result->fetch_object() : false;
  $stmt->close();
  return $account;
}

function _storeTotpSecret($userId, $secret)
{
  global $c;
  $encrypted = totp_encrypt_secret($secret);
  $stmt = $c->prepare("UPDATE sys_user SET u_totp_secret = ? WHERE id = ? AND u_status = 1 AND totp_enabled = 1 AND COALESCE(u_totp_secret, '') = ''");
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param('si', $encrypted, $userId);
  $success = $stmt->execute() && $stmt->affected_rows === 1;
  $stmt->close();
  return $success;
}

function _completeTotpLogin($userId, $returnTo, $expectedTotpEnabled)
{
  global $c;
  $stmt = $c->prepare("SELECT u.id, u.u_username, u.u_fullname, u.u_pin, r.id AS role, r.r_name AS role_name, r.code FROM sys_role r, sys_user_role ur, sys_user u WHERE u.id = ? AND COALESCE(u.totp_enabled, 0) = ? AND r.id = ur.ur_role_id AND u.id = ur.ur_user_id AND u.u_status = 1 AND r.r_active = 1 LIMIT 1");
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param('ii', $userId, $expectedTotpEnabled);
  if (!$stmt->execute()) {
    $stmt->close();
    return false;
  }
  $result = $stmt->get_result();
  $user = $result ? $result->fetch_object() : false;
  $stmt->close();
  if (!$user || !session_regenerate_id(true)) {
    return false;
  }
  unset($_SESSION[APP . '_totp_pending']);
  $_SESSION[APP . '_id'] = $user->id;
  $_SESSION[APP . '_role'] = $user->role;
  $_SESSION[APP . '_rolecode'] = $user->code;
  $_SESSION[APP . '_role_name'] = $user->role_name;
  $_SESSION[APP . '_fullname'] = $user->u_fullname;
  $_SESSION[APP . '_pin'] = $user->u_pin;
  $_SESSION[APP . '_totp_verified'] = (int) $expectedTotpEnabled === 1;
  $_SESSION[APP . '_totp_verified_user'] = (int) $expectedTotpEnabled === 1 ? (int) $user->id : 0;
  $_SESSION[ROOT . '_loggedin'] = true;
  $_SESSION[ROOT . '_username'] = $user->u_username;
  $branch = R::load('branch', 1);
  $_SESSION['branch_id'] = $branch->id;
  $_SESSION['branch_name'] = $branch->name;
  $_SESSION[APP . '_branch'] = $branch->id;
  $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
  $update = $c->prepare("UPDATE sys_user SET u_last_login_time = NOW(), u_loggedin = 1, u_last_ip = ?, u_failed_attempt = 0 WHERE id = ?");
  if ($update) {
    $update->bind_param('si', $ip, $userId);
    $update->execute();
    $update->close();
  }
  if ($user->code === 'carwashstaff') {
    redir("?page=18");
  } else {
    redir($returnTo);
  }
  return true;
}