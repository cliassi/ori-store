<?php
if (METHOD == 'add') {
  require 'forms/customer.php';
} elseif (METHOD == 'edit' && defined('ID')) {
  require 'forms/customer.php';
} elseif (METHOD == 'details' && defined('ID')) {
  require 'details/customer.php';
} elseif (METHOD == 'pending_delivery' && defined('ID')) {
  require 'details/customer.php';
  // require 'details/customer_pending_delivery.php';
} elseif (METHOD == 'statement' && defined('ID')) {
  require 'details/statement.php';
} else {
  $isCustomerPinAdmin = (int) uid() === 1;
  $revealedCustomerPinId = 0;
  $customerPinMessage = '';
  $customerTotpModalContent = '';
  $showCustomerTotpModal = false;

  if (isset($post->totp_stepup_challenge)) {
    $customerId = filter_var(isset($post->reveal_customer_pin) ? $post->reveal_customer_pin : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$isCustomerPinAdmin) {
      http_response_code(403);
      $challengeFragment = '<p class="text-danger mb-0">You are not authorized to reveal customer PINs.</p>';
    } elseif ($customerId === false) {
      $challengeFragment = '<p class="text-danger mb-0">Invalid customer selected.</p>';
    } else {
      $customerToReveal = R::findOne('customer', 'id = ? AND (branch_id = ? OR branch_id IS NULL)', [$customerId, bid()]);
      $totpAccount = totp_step_up_account((int) uid());
      if (!$customerToReveal) {
        $challengeFragment = '<p class="text-danger mb-0">Customer not found.</p>';
      } elseif (
        !$totpAccount ||
        (int) $totpAccount->totp_enabled !== 1 ||
        !is_string($totpAccount->u_totp_secret) ||
        $totpAccount->u_totp_secret === ''
      ) {
        $challengeFragment = '<p class="text-danger mb-0">Authenticator verification must be enabled before a customer PIN can be revealed.</p>';
      } else {
        ob_start();
        $challengeStarted = totp_require_step_up('customer_pin_reveal', (int) $customerId);
        $challengeFragment = ob_get_clean();
        if ($challengeStarted) {
          $challengeFragment = '<p class="text-danger mb-0">Authenticator verification is unavailable. Please try again.</p>';
        }
      }
    }

    if (ob_get_level() > 0) {
      ob_clean();
    }
    header('Content-Type: text/html; charset=UTF-8');
    print $challengeFragment;
    exit;
  }

  if (isset($post->update_customer_order_only)) {
    $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $orderCustomerId = filter_var(isset($post->customer_id) ? $post->customer_id : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $orderOnlyValue = isset($post->customer_order_only) ? (string) $post->customer_order_only : '';
    $updated = false;
    $responseCode = 400;

    if (!$isAjaxRequest) {
      $responseCode = 403;
    } elseif ($orderCustomerId !== false && ($orderOnlyValue === '0' || $orderOnlyValue === '1')) {
      try {
        $orderCustomer = R::findOne('customer', 'id = ? AND (branch_id = ? OR branch_id IS NULL)', [$orderCustomerId, bid()]);
        if ($orderCustomer) {
          $orderCustomer->customer_order_only = (int) $orderOnlyValue;
          $updated = (int) R::store($orderCustomer) > 0;
          $responseCode = $updated ? 200 : 500;
        } else {
          $responseCode = 404;
        }
      } catch (\Throwable $error) {
        error_log('Customer order-only update failed: ' . $error->getMessage());
        $responseCode = 500;
      }
    }

    if (ob_get_level() > 0) {
      ob_clean();
    }
    http_response_code($responseCode);
    header('Content-Type: application/json; charset=UTF-8');
    print json_encode(['success' => $updated]);
    exit;
  }

  if ($isCustomerPinAdmin && isset($post->reveal_customer_pin)) {
    $requestedCustomerId = filter_var($post->reveal_customer_pin, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedCustomerId === false) {
      $customerPinMessage = 'Invalid customer selected.';
    } else {
      $customerToReveal = R::findOne('customer', 'id = ? AND (branch_id = ? OR branch_id IS NULL)', [$requestedCustomerId, bid()]);
      if (!$customerToReveal) {
        $customerPinMessage = 'Customer not found.';
      } else {
        $totpAccount = totp_step_up_account((int) uid());
        if (
          !$totpAccount ||
          (int) $totpAccount->totp_enabled !== 1 ||
          !is_string($totpAccount->u_totp_secret) ||
          $totpAccount->u_totp_secret === ''
        ) {
          $customerTotpModalContent = '<p class="text-danger mb-0">Authenticator verification must be enabled before a customer PIN can be revealed.</p>';
          $showCustomerTotpModal = true;
        } else {
          ob_start();
          $totpVerified = totp_require_step_up('customer_pin_reveal', (int) $requestedCustomerId);
          $customerTotpModalContent = ob_get_clean();
          if ($totpVerified) {
            $revealedCustomerPinId = (int) $requestedCustomerId;
          } else {
            $showCustomerTotpModal = true;
          }
        }
      }
    }
  }

  if (isset($get->token)) {
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if ($token) {
      R::trash($token);
      if (isset($get->del) && isset($get->id)) {
        $st = R::load('customer', $get->id);
        R::trash($st);
        redir("?");
      }
    }
  }

  $fields = [
    "id" => '',
    "code" => ["label" => "Code", "display" => ''],
    "login_status" => ["label" => "", "display" => '', 'type' => 'login_status'],
    "password" => ["label" => "PIN", "display" => '', 'type' => 'pin'],
    "customer_order_only" => ["label" => "Apps Order", "display" => '', 'type' => 'order_toggle'],
    "company" => ["label" => "Shop Name", "display" => '', 'link' => 'details'],
    "contact" => ["label" => "Contact Person", "display" => ''],
    "mobile" => ["label" => "C.P. Mobile", "display" => ''],
    "due_limit" => ["label" => "Due Limit", "display" => ''],
    "city" => ["label" => "<a href='area'>Area</a>", "display" => ''],
    // "address" => ["label"=>"Shop Address", "display"=>''],
    "image" => ["label" => "Photo", "display" => '', 'type' => 'image'],
    "avatar" => ["label" => "Entry", "style" => "width: 50px;", "display" => '', 'type' => 'avatar'],
    "" => ["display" => '', 'type' => 'link', 'action' => 'edit'],
  ];
  if (!$isCustomerPinAdmin) {
    unset($fields['password']);
  }
  // vd($fields);
  $objs = R::find('customer', "branch_id = $branch_id OR branch_id IS NULL");
  $creatorIds = [];
  foreach ($objs as $customer) {
    $creatorId = (int) $customer->created_by;
    if ($creatorId > 0) {
      $creatorIds[$creatorId] = $creatorId;
    }
  }
  $creators = [];
  if ($creatorIds) {
    $creatorRows = R::find('sys_user', 'id IN (' . implode(',', $creatorIds) . ')');
    foreach ($creatorRows as $creator) {
      $creatorName = (string) $creator->u_fullname;
      $avatarFilename = (string) $creator->u_avatar;
      if ($avatarFilename !== '' && file_exists("uploads/user/avatar/$avatarFilename")) {
        $avatarSrc = htmlspecialchars(BASEURL . APP . "/uploads/user/avatar/$avatarFilename", ENT_QUOTES, 'UTF-8');
        $creatorAvatar = "<img src='$avatarSrc' width='30' height='30' class='w30' loading='lazy' decoding='async' alt=''>";
      } else {
        $creatorAvatar = htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8');
      }
      $creators[(int) $creator->id] = [
        'name' => $creatorName,
        'avatar' => $creatorAvatar
      ];
    }
  }
  $contant = "";
?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <h5>Customer List</h5>
            </div>
            <div class="col"><input class="form-control" placeholder="Search Customer"
                value='<?php if (isset($get->key))
                          print $get->key; ?>' id="search"></div>
          </div>

        </div>
        <?php if ($customerPinMessage !== '') { ?>
          <div class="alert alert-danger m-3" role="alert"><?php print htmlspecialchars($customerPinMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="customer-table" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <th class='text-center'>Sl.</th>
                  <?php foreach ($fields as $key => $value)
                    if (isset($value['label']))
                      print "<th>{$value['label']}</th>"; ?>
                  <th><a href="<?php print $page; ?>/add"><i class='fa fa-file'></i> Add</a></th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                foreach ($objs as $key => $obj) {
                  print "<tr>";
                  print "<td class='text-center'>" . ($i++) . "</td>";
                  foreach ($fields as $key => $value) {
                    if (isset($value['display'])) {
                      $printed = false;
                      if (isset($value['type'])) {
                        if ($value['type'] == "image") {
                          print "<td height='60px'>" . ($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}' height='60px' loading='lazy' decoding='async'></a>" : "") . "</td>";
                          $printed = true;
                        } elseif ($value['type'] == "avatar") {
                          $creator = isset($creators[(int) $obj->created_by]) ? $creators[(int) $obj->created_by] : null;
                          $avatar = $creator ? $creator['avatar'] : '';
                          $creatorTitle = $creator
                            ? htmlspecialchars($creator['name'] . ' @ ' . (string) $obj->created_at, ENT_QUOTES, 'UTF-8')
                            : '';
                          print "<td title='$creatorTitle'>$avatar</td>";
                          $printed = true;
                        } elseif ($value['type'] == "link") {
                          print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a>";
                          if (uid() == 1) {
                            print space(5) . "<a href='?del&id=$obj->id' class='protected-link'><i class='fas fa-trash-alt'></i></a>";
                          }
                          $printed = true;
                        } elseif ($value['type'] == "login_status") {
                          $statusIcons = '';
                          if (isset($obj->login_time) && $obj->login_time !== '') {
                            $loginTime = htmlspecialchars((string) $obj->login_time, ENT_QUOTES, 'UTF-8');
                            $statusIcons .= "<img width='20' height='20' class='customer-login-status-icon' src='/store/assets/power-green.png' alt='Logged in' title='Login time: $loginTime'>";
                          }
                          if (isset($obj->logout_time) && $obj->logout_time !== '') {
                            $logoutTime = htmlspecialchars((string) $obj->logout_time, ENT_QUOTES, 'UTF-8');
                            $statusIcons .= "<img width='20' height='20' class='customer-login-status-icon' src='/store/assets/power-red.png' alt='Logged out' title='Logout time: $logoutTime'>";
                          }
                          print "<td class='text-center text-nowrap'>$statusIcons</td>";
                          $printed = true;
                        } elseif ($value['type'] == "pin") {
                          if ($revealedCustomerPinId === (int) $obj->id) {
                            $pin = (string) $obj->password;
                            print "<td>" . ($pin !== '' ? htmlspecialchars($pin, ENT_QUOTES, 'UTF-8') : 'Not set') . "</td>";
                          } else {
                            print "<td><button type='button' class='btn btn-link btn-sm p-1 customer-pin-reveal' data-customer-id='" . (int) $obj->id . "' aria-label='Verify and reveal PIN' title='Reveal PIN'><i class='fa fa-eye' style='font-size:12px;line-height:1;' aria-hidden='true'></i></button></td>";
                          }
                          $printed = true;
                        } elseif ($value['type'] == "order_toggle") {
                          $orderOnly = (int) $obj->customer_order_only === 1;
                          print "<td class='text-center'><label class='customer-order-switch' title='Order only: " . ($orderOnly ? 'On' : 'Off') . "'>";
                          print "<input type='checkbox' role='switch' class='customer-order-toggle' data-customer-id='" . (int) $obj->id . "' aria-label='Order only for " . htmlspecialchars((string) $obj->company, ENT_QUOTES, 'UTF-8') . "'" . ($orderOnly ? " checked" : "") . ">";
                          print "<span class='customer-order-slider'></span></label></td>";
                          $printed = true;
                        }
                      }
                      if (!$printed) {
                        print "<td>";
                        if (isset($value['link']))
                          print "<a href='$page/{$value['link']}/$obj->id'>";
                        if ($key == 'code' && nn($obj->location)) {
                          print "<a href='$obj->location' target='_blank'>" . $obj->$key . "</td>";
                        } else {
                          print $obj->$key;
                        }
                        if (isset($value['link']))
                          print "</a>";
                        print "</td>";
                      }
                    }
                  }
                  print "</tr>";
                }
                ?>
              </tbody>
              <tfoot>
                <!-- <tr>
                        <th>Comapny Name</th>
                        <th>Contact Person</th>
                        <th>C.P. Mobile</th>
                        <th>Shop Address</th>
                        <th></th>
                      </tr> -->
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade modal-lightbox" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <img src="../assets/images/light-box/l1.jpg" alt="images" class="modal-image img-fluid" />
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="customerPinTotpModal" tabindex="-1" aria-labelledby="customerPinTotpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="customerPinTotpModalLabel">Verify to reveal customer PIN</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="customerPinTotpModalBody">
          <?php print $customerTotpModalContent; ?>
        </div>
      </div>
    </div>
  </div>

  <style>
    .w30 {
      width: 30px;
    }

    .customer-login-status-icon {
      width: 20px;
      height: 20px;
      vertical-align: middle;
    }

    .customer-order-switch {
      display: inline-block;
      position: relative;
      width: 58px;
      height: 24px;
      margin: 0;
      cursor: pointer;
      vertical-align: middle;
    }

    .customer-order-switch input {
      width: 0;
      height: 0;
      opacity: 0;
      position: absolute;
    }

    .customer-order-slider {
      position: absolute;
      inset: 0;
      border-radius: 20px;
      background: #d5d5d5;
      transition: background-color .2s ease;
    }

    .customer-order-slider::before {
      content: "";
      position: absolute;
      top: 3px;
      left: 3px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
      transition: transform .2s ease;
    }

    .customer-order-slider::after {
      content: "OFF";
      position: absolute;
      top: 50%;
      right: 4px;
      color: #666;
      font-size: 9px;
      font-weight: 700;
      transform: translateY(-50%);
    }

    .customer-order-switch input:checked + .customer-order-slider {
      background: #20c933;
    }

    .customer-order-switch input:checked + .customer-order-slider::before {
      transform: translateX(34px);
    }

    .customer-order-switch input:checked + .customer-order-slider::after {
      content: "ON";
      right: auto;
      left: 7px;
      color: #fff;
    }

    .customer-order-switch input:focus-visible + .customer-order-slider {
      outline: 2px solid #222;
      outline-offset: 2px;
    }

    #customerPinTotpModal .modal-body > .container {
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    #customerPinTotpModal .modal-body > .container > .card {
      border: 0;
      box-shadow: none;
    }
  </style>
  <script>
    $(document).ready(function() {
      $('#search').on('keyup', function() {
        var searchKey = $(this).val().toLowerCase(); // Get the search value and convert to lowercase

        $('table tbody tr').each(function() {
          var rowText = $(this).text().toLowerCase(); // Get the text content of the row and convert to lowercase

          if (rowText.includes(searchKey)) {
            $(this).show(); // Show the row if it matches the search key
          } else {
            $(this).hide(); // Hide the row if it does not match
          }
        });
      });
      <?php if (isset($get->key)) { ?>
        $("#search").trigger('keyup');
      <?php } ?>
    });
    document.querySelectorAll('.customer-pin-reveal').forEach(function(button) {
      button.addEventListener('click', function() {
        var modalElement = document.getElementById('customerPinTotpModal');
        var modalBody = document.getElementById('customerPinTotpModalBody');
        var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        var request = new FormData();
        request.append('totp_stepup_challenge', '1');
        request.append('reveal_customer_pin', button.getAttribute('data-customer-id'));

        modalBody.innerHTML = '<div class="text-center py-3">Preparing authenticator challenge...</div>';
        modal.show();

        fetch(window.location.href, {
          method: 'POST',
          body: request,
          credentials: 'same-origin',
          headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
          return response.text();
        }).then(function(challengeHtml) {
          modalBody.innerHTML = challengeHtml;
        }).catch(function() {
          modalBody.innerHTML = '<p class="text-danger mb-0">Unable to start authenticator verification. Please try again.</p>';
        });
      });
    });
    document.querySelectorAll('.customer-order-toggle').forEach(function(toggle) {
      toggle.addEventListener('change', function() {
        var previousValue = !toggle.checked;
        toggle.disabled = true;
        var request = new FormData();
        request.append('update_customer_order_only', '1');
        request.append('customer_id', toggle.getAttribute('data-customer-id'));
        request.append('customer_order_only', toggle.checked ? '1' : '0');

        fetch(window.location.href, {
          method: 'POST',
          body: request,
          credentials: 'same-origin',
          headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
          return response.json().then(function(result) {
            if (!response.ok || !result.success) {
              throw new Error('Order setting update failed.');
            }
          });
        }).then(function() {
          toggle.parentElement.title = 'Order only: ' + (toggle.checked ? 'On' : 'Off');
        }).catch(function() {
          toggle.checked = previousValue;
          toggle.parentElement.title = 'Order only: ' + (previousValue ? 'On' : 'Off');
          alert('Could not update the order setting. Please try again.');
        }).finally(function() {
          toggle.disabled = false;
        });
      });
    });
    <?php if ($showCustomerTotpModal) { ?>
      var customerPinTotpModal = document.getElementById('customerPinTotpModal');
      bootstrap.Modal.getOrCreateInstance(customerPinTotpModal).show();
    <?php } ?>
  </script>
<?php } ?>