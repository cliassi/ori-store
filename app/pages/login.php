<?php
error_reporting(E_ALL);
$msg = "";
if(isset($get->logout)){
  if (session_status() == PHP_SESSION_NONE) {
      session_start();
  }
  $_SESSION[ROOT.'_loggedin'] = false;
  unset($_SESSION[ROOT.'_loggedin']);
  unset($_SESSION['branch_id']);
  unset($_SESSION['branch_name']);
  unset($_SESSION);
  redir(ROOT);
}
if(isset($post->email) && isset($post->login)){
  //$url = BASEURL."/framework/home";
  $url = ROOT; 
  if(isset($post->url)){$url .= $post->url;} else{ $url = "home"; }
  //die($url);
  if (_checkLogin($post->email,md5($post->password), false)) {
    global $url;
    global $c;
    $_SESSION[ROOT.'_loggedin'] = true;
    $_SESSION[ROOT.'_username'] = $post->email;
    update("sys_user", "u_last_login_time = NOW(), u_loggedin = 1, u_last_ip='{$_SERVER['REMOTE_ADDR']}'", " id = '".uid()."'");
  if(rolecode()=='carwashstaff'){
    redir("?page=18");
  }

    if(isset($get->q)){
        redir(ROOT."/?".$get->q);  
    } else{
      redir($url);
    }
  } else {
    $msg = 'Failed to login! Please retry with right credentials...';
    //redir($url);
  }
}
if(!loggedin() && !isset($post->recover)):
?>
<form method='post'>
  <div class='auth-main'>
    <div class='auth-wrapper v1'>
      <div class='auth-form'>
        <div class='card my-5'>
          <div class='card-body'>
            <h4 class='text-center f-w-500 mb-3'>Login with your username</h4>
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


<?php
endif; 


function _checkLogin($username, $password, $remember) {
  $user = select("u.id AS id, r.id as role, r_name as role_name, u_fullname, u_pin, r.code", "sys_role r, sys_user_role ur, `sys_user` u", "u_username = '{$username}' AND u_password = '{$password}' AND r.id=ur_role_id AND u.id=ur_user_id AND u_status = 1 AND r_active = 1 ");
  if ($user->num_rows>0){
    //setcookie("show", 1);
    $row = mysqli_fetch_object($user);
    $_SESSION[APP.'_id'] = $row->id;
    $_SESSION[APP.'_role'] = $row->role;
    $_SESSION[APP.'_rolecode'] = $row->code;
    $_SESSION[APP.'_role_name'] = $row->role_name;
      $_SESSION[APP.'_fullname'] = $row->u_fullname;
      $_SESSION[APP.'_pin'] = $row->u_pin;
    update("sys_user", "u_failed_attempt = 0", "`id` = '$row->id'");
    return true;
  } else {
    $user = select("u_failed_attempt, u_status", "`sys_user_role` ur, `sys_user` u", "u.id=ur_user_id AND u_username = '{$username}' AND u_status = 1");
    if($user->num_rows){
      $u = mysqli_fetch_object($user);
      if($u->u_failed_attempt >= 3 && $u->u_status == 1){
        // update("sys_user", "u_status = 0", "`u_username` = '{$username}'");
      } else{
        $failed_attempt = $u->u_failed_attempt + 1;
        update("sys_user", "u_failed_attempt=$failed_attempt", "`u_username` = '{$username}'");
      }
    } else{
      $attempts = isset($_SESSION[APP.'_attempts_count'])?$_SESSION[APP.'_attempts_count']:0;
      $attempts++;
      insert("sys_fraud_user", "`f_username`, `f_password`, `f_ip`, `f_attempts`, `f_date`", "'{$username}', '{$password}', '{$_SERVER['REMOTE_ADDR']}', $attempts, NOW()");
    }
    return false;
  }
}