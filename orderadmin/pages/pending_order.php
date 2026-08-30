<?php
$button = "<a href='' class='has-checkbox hidden'><span><input type='checkbox' id='all-order' checked data-type='all'> Order</span></a>";

if(isset($post->delivery_staff) && isset($post->assign)){
  $deliveryStaff = trim($post->delivery_staff);
  $selectedIids = [];
  $isUnassign = ($deliveryStaff === '--UNASSIGN--');
  
  if (isset($post->iid) && is_array($post->iid)) {
    foreach (array_keys($post->iid) as $iid) {
      $iid = (int) $iid;
      if ($iid <= 0) continue;
      if ($isUnassign) {
        update("invoice_item", "assigned_at=NULL, assigned_by=NULL, assigned_to=NULL", "id=$iid");
      } else {
        update("invoice_item", "assigned_at=NOW(), assigned_by=" . uid() . ", assigned_to='" . mysqli_real_escape_string($c, $deliveryStaff) . "'", "id=$iid");
      }
      $selectedIids[] = $iid;
    }
  }
  
  if (!$selectedIids && isset($post->selected_customer) && is_array($post->selected_customer)) {
    $selectedCustomerIds = array_values(array_unique(array_filter(array_map('intval', $post->selected_customer))));
    if ($selectedCustomerIds) {
      $customerIdsSql = implode(',', $selectedCustomerIds);
      $fallbackQuery = "SELECT ii.id
              FROM invoice_item ii
              INNER JOIN invoice i ON i.id=ii.invoice_id
              WHERE i.customer_id IN ($customerIdsSql)
                AND IFNULL(ii.delivery_date,i.invoice_date) <= CURDATE()
                AND ii.quantity > ii.delivered";
      $fallbackItems = select($fallbackQuery);
      while ($fallbackItems && ($fallbackItem = mysqli_fetch_object($fallbackItems))) {
        $iid = (int) $fallbackItem->id;
        if ($iid <= 0) continue;
        if ($isUnassign) {
          update("invoice_item", "assigned_at=NULL, assigned_by=NULL, assigned_to=NULL", "id=$iid");
        } else {
          update("invoice_item", "assigned_at=NOW(), assigned_by=" . uid() . ", assigned_to='" . mysqli_real_escape_string($c, $deliveryStaff) . "'", "id=$iid");
        }
        $selectedIids[] = $iid;
      }
    }
  }
  
  if ($selectedIids) {
    // redir("?assigned=1");
  } else {
    // redir("?");
  }
  // exit;
}
if (isset($post->collect)) {
  // $post = $_POST;
  // var_dump($post);
  $collectedExpr = "(ii.collected_at IS NOT NULL OR EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
  if (isset($post->collect)) {
    // Get delivery staff from POST or from the first selected item's assigned_to field, or default to logged-in staff
    $obj->delivery_staff = '';
    
    // First, try to get from POST data
    if (isset($post->delivery_staff) && !empty($post->delivery_staff)) {
      $obj->delivery_staff = trim($post->delivery_staff);
    }
    
    // If not in POST, try to get from the first selected item's assigned_to field
    if (!nn($obj->delivery_staff) && isset($post->iid) && is_array($post->iid)) {
      $firstIid = (int) array_keys($post->iid)[0];
      if ($firstIid > 0) {
        $staffQuery = "SELECT assigned_to FROM invoice_item WHERE id=$firstIid AND assigned_to IS NOT NULL AND assigned_to != ''";
        $staffResult = select($staffQuery);
        if ($staffResult && ($staffRow = mysqli_fetch_object($staffResult))) {
          $obj->delivery_staff = trim($staffRow->assigned_to);
        }
      }
    }
    
    // If still not set, default to logged-in staff name
    if (!nn($obj->delivery_staff)) {
      $uid = isset($_SESSION['UID']) ? (int)$_SESSION['UID'] : 0;
      $rid = getFieldValue('sys_user_role', 'ur_role_id', "ur_user_id=" . $uid);
      $rolename = getFieldValue('sys_role', 'r_name', "id=" . $rid);
      if ($rolename === 'Delivery Staff') {
        $obj->delivery_staff = getFieldValue('staff_salary', 'name', "user_id=" . $uid);
      }
    }
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    $selectedIids = [];
    if (isset($post->iid) && is_array($post->iid)) {
      foreach (array_keys($post->iid) as $iid) {
        $iid = (int) $iid;
        if ($iid <= 0) continue;
        update("invoice_item", "collected_at=IFNULL(collected_at, NOW()), collected_by=" . uid() . ", delivery_staff='$obj->delivery_staff'", "id=$iid");
        $selectedIids[] = $iid;
      }
    }

    if ($selectedIids && !$stored) {
      R::store($obj);
      $stored = true;
    }

    if ($selectedIids) {
      foreach ($selectedIids as $iid) {
        $invItem = R::load('invoice_item', $iid);
        if (!$invItem || !$invItem->id) continue;

        $variance = null;
        $product = null;
        if (isset($invItem->product_variance_id) && (int)$invItem->product_variance_id > 0) {
          $variance = R::load('product_variance', (int)$invItem->product_variance_id);
          if ($variance && $variance->id && isset($variance->product_id) && (int)$variance->product_id > 0) {
            $product = R::load('product', (int)$variance->product_id);
          }
        }

        $sci = R::dispense('stock_collect_item');
        $sci->stock_collect_id = $obj->id;
        $sci->invoice_item_id = $invItem->id;
        $sci->product_id = ($product && $product->id) ? $product->id : (isset($invItem->product_id) ? (int)$invItem->product_id : 0);
        $sci->product_variance_id = ($variance && $variance->id) ? $variance->id : (isset($invItem->product_variance_id) ? (int)$invItem->product_variance_id : 0);
        $sci->quantity = (float)$invItem->quantity;
        $sci->price = isset($invItem->price) ? (float)$invItem->price : (($variance && isset($variance->price)) ? (float)$variance->price : 0);
        $sci->cost = ($variance && isset($variance->cost)) ? (float)$variance->cost : 0;
        $sci->name = ($product && isset($product->name)) ? $product->name : '';
        $sci->description = ($variance && isset($variance->particulars, $variance->size, $variance->unit)) ? "$variance->particulars $variance->size x $variance->unit" : '';
        $sci->created_by = uid();
        R::store($sci);
      }
    }

    if (!$selectedIids && isset($post['selected_customer']) && is_array($post['selected_customer'])) {
      $selectedCustomerIds = array_values(array_unique(array_filter(array_map('intval', $post['selected_customer']))));
      if ($selectedCustomerIds) {
        $customerIdsSql = implode(',', $selectedCustomerIds);
        $fallbackQuery = "SELECT ii.id
                FROM invoice_item ii
                INNER JOIN invoice i ON i.id=ii.invoice_id
                WHERE i.customer_id IN ($customerIdsSql)
                  AND IFNULL(ii.delivery_date,i.invoice_date) <= CURDATE()
                  AND ii.quantity > ii.delivered
                  AND NOT $collectedExpr";
        $fallbackItems = select($fallbackQuery);
        while ($fallbackItems && ($fallbackItem = mysqli_fetch_object($fallbackItems))) {
          $iid = (int) $fallbackItem->id;
          if ($iid <= 0) continue;
          update("invoice_item", "collected_at=IFNULL(collected_at, NOW()), collected_by=" . uid() . ", delivery_staff='$obj->delivery_staff'", "id=$iid");
          $selectedIids[] = $iid;
        }
      }
    }

    $hasVarianceQty = false;
    if (isset($post->variance) && is_array($post->variance)) {
      foreach ($post->variance as $id => $qty) {
        if ($qty == 0) continue;
        $hasVarianceQty = true;
        if (!$stored) {
          R::store($obj);
          $stored = true;
        }
        $variance = R::load("product_variance", $id);
        $product = R::load("product", $variance->product_id);
        $ii = R::dispense("stock_collect_item");

        $ii->stock_collect_id = $obj->id;
        $ii->product_id = $product->id;
        $ii->product_variance_id = $variance->id;
        $ii->quantity = $qty;
        $ii->price = $variance->price;
        $ii->cost = $variance->cost;
        $ii->name = $product->name;
        $ii->description = "$variance->particulars $variance->size x $variance->unit";
        $ii->created_by = uid();

        R::store($ii);
      }
    }

    if (!$selectedIids && !$hasVarianceQty) {
      // redir("?");
      // exit;
    }

    // redir(ROOT . "/dcollect_collect");
    // redir(ROOT . "/dcollect_delivery");
    // exit;
  }
}
?>

    <style type="text/css">
      th {
        text-align: center;
      }


      .w-100{
        margin-bottom: 0px !important;
      }

      .customer-item td:nth-child(n+3),
      .customer-item th:nth-child(n+3) {
        width: 50px;
        white-space: nowrap;
      }

      td {
        vertical-align: top !important;
      }

      select,
      select option {
        font-size: .7rem;
      }

      footer.pc-footer {
        display: none !important;
      }

      /* Mobile-first adjustments */
      .mobile-container {
        width: 100%;
        padding: 8px
      }

      .table-customer {
        font-size: 12px
      }

      .table-responsive {
        overflow-x: auto
      }

      .grid-areas {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px
      }

      .has-checkbox span {
        width: auto
      }

      input[type=checkbox] {
        width: 15px;
        height: 15px
      }

      @media (min-width: 576px) {
        .grid-areas {
          grid-template-columns: repeat(4, 1fr)
        }

        .table-customer {
          font-size: 13px
        }
      }

      @media (max-width: 576px) {
        td[style] {
          width: auto !important
        }
      }

      .customer-container {
        display: flex;
        flex-direction: row;
        gap: 10px;
      }

      select {}

      /* Cards inside .orders */
      .orders {
        padding-bottom: 90px
      }

      /* Single column list */
      .orders .customer-container {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 10px
      }

      @media (min-width:540px) {
        .orders .customer-container {
          grid-template-columns: repeat(1, minmax(0, 1fr))
        }
      }

      @media (min-width:768px) {
        .orders .customer-container {
          grid-template-columns: repeat(1, minmax(0, 1fr))
        }
      }

      .orders .customer-item {
        display: block !important;
        background: #fff;
        border: 1px solid #e5e7ebca;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        overflow: hidden;
        width: 100% !important;
        padding: 5px;
      }

      .orders .customer-item table {
        width: 100%;
        margin: 0;
        table-layout: auto !important;
      }

      .orders .customer-item thead th,
      .orders .customer-item tbody td,
      .orders .customer-item tbody th {
        padding: 8px 10px;
        vertical-align: middle;
        word-wrap: break-word
      }

      .orders .toggle-store-info.hide-info {
        display: table
      }

      .orders input[type='number'] {
        width: 100%;
        min-width: 70px
      }

      .orders a {
        text-decoration: underline
      }

      .orders a.customer-link {
        text-decoration: none !important;
        color: #333 !important
      }

      .orders .all-collected {
        display: none
      }

      .orders .all-collected.show {
        display: table-row
      }
    </style>
    <?php

    // Determine if logged-in user is Delivery Staff and get their staff name
    $uid = isset($_SESSION['UID']) ? (int)$_SESSION['UID'] : 0;
    $rid = getFieldValue('sys_user_role', 'ur_role_id', "ur_user_id=" . $uid);
    $rolename = getFieldValue('sys_role', 'r_name', "id=" . $rid);
    $isDeliveryStaff = ($rolename === 'Delivery Staff');
    $loggedStaffName = $isDeliveryStaff ? getFieldValue('staff_salary', 'name', "user_id=" . $uid) : '';

    if (isset($post->deliver)) {
      // dd($post);
      foreach ($post->iid as $key => $val) {
        $iid = (int)$key;
        if ($iid <= 0) continue;
        
        $ii = R::load("invoice_item", $iid);
        if (!$ii || !$ii->id) continue;
        
        // Calculate remaining quantity to deliver
        $remainingQty = (float)$ii->quantity - (float)$ii->delivered;
        if ($remainingQty <= 0) continue;
        
        $inv = R::load("invoice", $ii->invoice_id);
        $deliveryStaffValue = isset($post->delivery_staff) && !empty($post->delivery_staff) ? trim($post->delivery_staff) : $loggedStaffName;
        $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaffValue);
        update("invoice_item", "delivered=quantity, delivered_by=" . uid() . ", delivered_at=NOW(),delivery_staff='$deliveryStaffSql'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
        insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$iid, $remainingQty, " . uid() . ",'$deliveryStaffSql'");
      }
    }

    /*if (isset($post->collect)) {
      $obj = R::dispense("stock_collect");
      $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
      if (isset($post->collect)) {
        $obj->delivery_staff = $post->delivery_staff;
        $obj->date = today();
        $obj->created_by = uid();
        // $obj->due_date = $post->due_date;
        // $obj->delivery_date = $post->delivery_date;
        // $obj->note = $post->note;

        $stored = false;

        foreach ($post->variance as $id => $qty) {
          if ($qty == 0) continue;
          if (!$stored) {
            R::store($obj);
            $stored = true;
          }
          $variance = R::load("product_variance", $id);
          $product = R::load("product", $variance->product_id);
          $ii = R::dispense("stock_collect_item");

          $ii->stock_collect_id = $obj->id;
          $ii->product_id = $product->id;
          $ii->product_variance_id = $variance->id;
          $ii->quantity = $qty;
          $ii->price = $variance->price;
          $ii->cost = $variance->cost;
          $ii->name = $product->name;
          $ii->description = "$variance->particulars $variance->size x $variance->unit";
          $ii->created_by = uid();

          R::store($ii);
        }

        // redir("?");
      }
    }*/




    if (isset($post->update_delivery_date)) {
      $ii = R::load('invoice_item', $post->id);
      $ii->delivery_date = $post->date;
      dd($ii);
      R::store($ii);
    }

    if (isset($post->save)) {
      $obj = R::dispense("stock_collect");
      $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
      $obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
      if (isset($post->save)) {
        $obj->delivery_staff = $post->delivery_staff;
        $obj->date = today();
        $obj->created_by = uid();
        // $obj->due_date = $post->due_date;
        // $obj->delivery_date = $post->delivery_date;
        // $obj->note = $post->note;

        $stored = false;

        foreach ($post->variance as $id => $qty) {
          if ($qty == 0) continue;
          if (!$stored) {
            R::store($obj);
            $stored = true;
          }
          $variance = R::load("product_variance", $id);
          $product = R::load("product", $variance->product_id);
          $ii = R::dispense("stock_collect_item");

          $ii->stock_collect_id = $obj->id;
          $ii->product_id = $product->id;
          $ii->product_variance_id = $variance->id;
          $ii->quantity = $qty;
          $ii->price = $variance->price;
          $ii->cost = $variance->cost;
          $ii->name = $product->name;
          $ii->description = "$variance->particulars $variance->size x $variance->unit";
          $ii->created_by = uid();
          $ii->branch_id = $obj->branch_id;

          R::store($ii);
        }

        // redir(ROOT . "/delivery?s=$obj->delivery_staff");
      }
    }

    ?>
    <style type="text/css">
      th {
        text-align: left;
      }

      td span {
        display: inline-block;
        /*		border: solid 1px #ccc;*/
      }

      th span:nth-child(n+0) {
        /* width: 55px; */
      }

      td span:nth-child(n+0) {
        width: 20px;
      }

      a.has-checkbox {
        text-decoration: none;
        color: #000;
      }
    </style>
    <div class="text-center mb-3-3 position-relative" style="background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%); padding: 20px 15px; margin: -8px -8px 0px -8px; border-radius: 0 0 20px 20px; box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);">
      <h1 class="h3 mb-0" style="color: white; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Pen Order</h1>
      <div style="position: absolute; top: 15px; right: 15px; text-align:right;">
        <?php if ($isDeliveryStaff) { ?>
          <!-- Hidden select retained for JS value -->
          <select id="delivery-staff-filter" class="form-select form-select-sm" style="min-width: 120px; margin-top: 6px; font-size: 0.8rem; display:none;">
            <option value="<?php echo htmlspecialchars($loggedStaffName); ?>" selected><?php echo htmlspecialchars($loggedStaffName); ?></option>
          </select>
          <!-- Visible label for logged-in staff -->
          <div style="margin-top:6px; padding-right:12px; color:#fff; font-size:0.9rem; font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,.2);">
            <?php echo htmlspecialchars($loggedStaffName); ?>
          </div>
        <?php } else { ?>
          <select id="delivery-staff-filter" class="form-select form-select-sm" style="min-width: 120px; margin-top: 6px; font-size: 0.8rem;">
            <option value="">All Staff</option>
            <?php
            $deliveryStaff = select('distinct name', 'staff_salary', "category='Delivery Staff' ORDER BY name");
            while ($staff = mysqli_fetch_object($deliveryStaff)) {
              echo "<option value='" . htmlspecialchars($staff->name) . "'>" . htmlspecialchars($staff->name) . "</option>";
            }
            ?>
          </select>
        <?php } ?>
      </div>
    </div>
    <div class="container-fluid p-2 mobile-container">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped table-customer w-100">
          <thead class='hidden'>
            <tr>
              <th>
                <?php
                print $button;
                ?>
                <!-- <span><input type='check='pending-list' data-type='pending-list'> Pending Delivery List</span> -->
              </th>
              <?php
              $areas = R::find('city');
              // Hide category columns for mobile view
              ?>
            </tr>
          </thead>
          <tbody>

            <?php
            print "<tr>";
            print "<td style='width:600px'>";
            print "<label class='mb-1 d-block' style='cursor: pointer;' onclick='toggleAreas()'>Select Area(s) <span id='area-arrow' style='float: right;'>▼</span></label>";
            print "<select id='areas' multiple class='form-select' size='8' style='min-width:220px; display: none;'>";
            foreach ($areas as $key => $area) {
              print "<option value='$area->id'>" . ucfirst(strtolower($area->name)) . "</option>";
            }
            print "</select>";
            print "</td>";

            // Category product columns removed for mobile-only area list
            print "</tr>";
          </tbody>
        </table>
      </div>
    </div>
    <div style="padding: 8px 10px; display:flex; align-items:center; gap:10px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:999;">
      <label style="margin:0; font-weight:600;">
        <input type="checkbox" id="select-all-orders"> Select all
      </label>
      <button type="button" class="btn btn-primary btn-sm" id="change-delivery-date">Change Delivery Date</button>
    </div>

    <div class='orders'></div>
    <?php
    $deliveryStaff = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
    ?>
    <div id="dcollectActionBar" style="position: fixed;
    right: 5px;
    left: 0;
    bottom: 10px;
    text-align: center;
    background: #efefff;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 9999;
    font-size: 0.75rem;
    justify-content: space-between;">
      <?php if ($isDeliveryStaff) { ?>
        <select id="delivery_staff" class="form-select" name="delivery_staff" form="dcollectForm" style="max-width: 120px; font-size:.75rem; display:none;">
          <option value="<?php echo htmlspecialchars($loggedStaffName); ?>" selected><?php echo htmlspecialchars($loggedStaffName); ?></option>
        </select>
      <?php } else { ?>
        <select id="delivery_staff" class="form-select" name="delivery_staff" form="dcollectForm" required style="max-width: 120px; font-size:.75rem;">
          <option value="">Please select</option>
          <?php while ($man = mysqli_fetch_object($deliveryStaff)) {
            print "<option>" . htmlspecialchars($man->name) . "</option>";
          } ?>
        </select>
      <?php } ?>
      
      <script>
        (function() {
          var form = document.getElementById('dcollectForm');
          var deliveryStaffSelect = document.getElementById('delivery_staff');
          if (form && deliveryStaffSelect) {
            form.addEventListener('submit', function(e) {
              if (!deliveryStaffSelect.value) {
                e.preventDefault();
                alert('Delivery staff must be selected');
              }
            });
          }
        })();
      </script>

      <button id="btnCollect" class="btn btn-warning" type="submit" name="collect" value="1" form="dcollectForm" style="flex: 1; max-width: 110px; font-size:.75rem; margin-left:auto;">Collect</button>
    </div>

    <?php if (!$isDeliveryStaff) { ?>
    <div class="modal fade" id="modal-modify-quantity" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" autocomplete="off" enctype='multipart/form-data'>
            <input type="hidden" name="invoice_item_id" id="invoice_item_id" value=''>
            <div class="modal-header">
              <h4 class="modal-title">Update Quantity</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <table>
                <tr>
                  <td>Update Quantity</td>
                  <td nowrap><input type='number' id='new-quantity' name='quantity' step='1' class='form-control'></td>
                </tr>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success" id='update_quantity_button' name="update_quantity">Save</button>
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php } ?>
    <div class="modal fade" id="modal-modify-price" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" autocomplete="off" enctype='multipart/form-data'>
            <input type="hidden" name="invoice_item_id" id="invoice_item_id" value=''>
            <div class="modal-header">
              <h4 class="modal-title">Update Price</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <table>
                <tr>
                  <td>Update Price</td>
                  <td nowrap><input type='number' id='new-price' name='price' step='1' class='form-control'></td>
                </tr>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success" id='update_price_button' name="update_price_button">Save</button>
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Modal -->
    <div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="dateModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="dateForm" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dateModalLabel">Set Date</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Hidden field to store ID -->
            <input type="hidden" id="hiddenId" name="id">

            <!-- Date picker -->
            <div class="mb-3-3">
              <label for="datepicker" class="form-label">Select Date</label>
              <input type="date" class="form-control" id="datepicker" name="date" required>

              <div class="form-text">Current delivery date: <span id="currentDeliveryDate"></span></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" name='update_delivery_date'>Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Customer Location Modal -->
    <div class="modal fade" id="cust-loc-modal" tabindex="-1" aria-labelledby="custLocModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="custLocModalLabel">Customer Location</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="cust-loc-body">
            <!-- Customer location content will be loaded here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script type="text/javascript">
      var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
      var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;

      function setItemId(id) {
        $('#invoice_item_id').val(id);
      }

      function setItemIdPrice(id, price) {
        $("#new-price").val(price);
        setItemId(id);
      }

      let dateModal;

      $('.has-checkbox').click(function(e) {
        e.preventDefault();
      })

      document.addEventListener('DOMContentLoaded', () => {
        dateModal = new bootstrap.Modal(document.getElementById('dateModal'));

        // Form submit handler
        document.getElementById('dateForm').addEventListener('submit', function(e) {
          e.preventDefault();
          const date = document.getElementById('datepicker').value;
          const id = getSelectedInvoiceItemIds() || document.getElementById('hiddenId').value;
          const updateCount = id ? id.split(',').filter(Boolean).length : 0;

          if (!updateCount || !confirm('Update delivery date for ' + updateCount + ' selected item(s) to ' + date + '?')) {
            return;
          }

          // You can send the data to server or handle it as needed
          console.log('ID:', id, 'Date:', date);

          $.post('/ajax/update_invoice_item_date.php', {
              update_date: 'update_date',
              date: date,
              invoice_item_id: id
            })
            .done((response) => {
              // $('#invoice-item-date-' + invoice_item_id).data('dd', response);
              // var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));
              // debugger;
              load();
              setTimeout(() => {
                $('#pending-only').trigger('change');
              }, 1000);
            })
            .fail(() => {});
          dateModal.hide();
        });
      });

      // Function to call and show modal with ID
      function setDate(el, id) {
        if (!__canEditAnything) return;
        var selectedIds = getSelectedInvoiceItemIds();
        if (selectedIds) {
          document.getElementById('hiddenId').value = selectedIds;
        } else {
          document.getElementById('hiddenId').value = id;
        }

        // document.getElementById('hiddenId').value = id;
        document.getElementById('datepicker').value = ''; // Optional: clear previous value
        document.getElementById('currentDeliveryDate').innerHTML = $(el).data('dd');
        dateModal.show();
      }

      function getSelectedInvoiceItemIds() {
        const selectedIds = [];

        document.querySelectorAll('.orders input.iid-date:checked').forEach(function(cb) {
          const ids = cb.dataset.iids ? cb.dataset.iids.split(',') : [cb.value];
          ids.forEach(function(id) {
            id = String(id).trim();
            if (id && !selectedIds.includes(id)) selectedIds.push(id);
          });
        });

        return selectedIds.join(',');
      }

      $("#update_quantity_button").click(function() {
        const quantity = $('#new-quantity').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/ajax/update_invoice_item_quantity.php', {
            update_quantity: 'update_quantity',
            quantity: quantity,
            invoice_item_id: invoice_item_id
          })
          .done((response) => {
            $('#invoice-item-' + invoice_item_id).text(quantity);
            const price = $('#invoice-item-price-' + invoice_item_id).data('price');
            $('#invoice-item-price-' + invoice_item_id).text((parseFloat(price) * parseFloat(quantity)).toFixed(2));
            var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));

            // Then call the hide() method
            myModal.hide();
          })
          .fail(() => {});
      });
      $("#update_price_button").click(function() {
        const price = $('#new-price').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/ajax/update_invoice_item_price.php', {
            update_price: 'update_price',
            price: price,
            invoice_item_id: invoice_item_id
          })
          .done((response) => {
            if (response != "")
              $('.invoice-item-price-' + invoice_item_id).text(price);
              $('#invoice-item-price-' + invoice_item_id).text(response);
            var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-price'));

            // Then call the hide() method
            myModal.hide();
          })
          .fail(() => {});
      });
      $("input[type=checkbox]").change(function() {
        let selectedCustomers = $('.checkbox-area:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        let selectedProducts = $('.checkbox-product:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        const deliveryStaff = $("#delivery-staff-filter").val();
        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending, delivery, collection, pendingList, 'Staff:', deliveryStaff);
        $.post('/ajax/pending_order.php', {
            customers: selectedCustomers,
            products: selectedProducts,
            order: order,
            pending: pending,
            delivery: delivery,
            collection: collection,
            pendingList: pendingList,
            deliveryStaff: deliveryStaff,
              UID: <?php print $_SESSION['UID']; ?>
          })
          .done((response) => {
            $('.orders').html(response);
            syncDeliveryStaffToForm();
            updateActionBar();
          })
          .fail(() => {});
      });

      function load() {
        let selectedCustomers = $('.checkbox-area:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        let selectedProducts = $('.checkbox-product:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        const deliveryStaff = $("#delivery-staff-filter").val();
        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending, 'Staff:', deliveryStaff);
        $.post('/ajax/pending_order.php', {
            customers: selectedCustomers,
            products: selectedProducts,
            order: order,
            pending: pending,
            delivery: delivery,
            collection: collection,
            pendingList: pendingList,
            deliveryStaff: deliveryStaff,
              UID: <?php print $_SESSION['UID']; ?>
          })
          .done((response) => {
            $('.orders').html(response);
            syncDeliveryStaffToForm();
            updateActionBar();
          })
          .fail(() => {});
      }

      load();

      function syncDeliveryStaffToForm() {
        var sel = document.getElementById('delivery_staff');
        if (!sel) return;
        var form = document.getElementById('dcollectForm');
        if (!form) return;

        var existing = form.querySelector('select[name="delivery_staff"]');
        if (existing && existing !== sel) {
          existing.value = sel.value;
        }
      }

      function updateActionBar() {
        var collectBtn = document.getElementById('btnCollect');
        var deliverBtn = document.getElementById('btnDeliver');
        if (!collectBtn || !deliverBtn) return;

        var orderMode = document.getElementById('all-order');
        var deliveryMode = document.querySelector('.delivery-list');
        var isOrder = orderMode ? !!orderMode.checked : true;
        var isDelivery = deliveryMode ? !!deliveryMode.checked : false;

        collectBtn.style.display = isOrder ? '' : 'none';
        deliverBtn.style.display = isDelivery ? '' : 'none';
      }

      document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'delivery_staff') {
          try {
            localStorage.setItem('dcollect_delivery_staff', e.target.value || '');
          } catch (err) {}
          syncDeliveryStaffToForm();
        }
        updateActionBar();
      });

      (function restoreDeliveryStaff() {
        var sel = document.getElementById('delivery_staff');
        if (!sel) return;
        try {
          var v = localStorage.getItem('dcollect_delivery_staff');
          if (v) sel.value = v;
        } catch (err) {}
      })();

      updateActionBar();

      $(function() {
        $("#areas").on('change', function() {
          const selectedAreas = $(this).val() ? $(this).val().join(',') : '';
          const selectedProducts = '';
          const order = $("#all-order").prop('checked');
          const pending = $("#pending-only").prop('checked');
          const delivery = $(".delivery-list").prop('checked');
          const collection = $(".collection-list").prop('checked');
          const pendingList = $("#pending-list").prop('checked');
          const deliveryStaff = $("#delivery-staff-filter").val();
          $.post('/ajax/pending_order.php', {
              customers: selectedAreas,
              products: selectedProducts,
              order: order,
              pending: pending,
              delivery: delivery,
              collection: collection,
              pendingList: pendingList,
              deliveryStaff: deliveryStaff,
              UID: <?php print $_SESSION['UID']; ?>
            })
            .done((response) => {
              $('.orders').html(response);
              syncDeliveryStaffToForm();
              updateActionBar();
            })
            .fail(() => {});
        });
        // Add event listener for delivery staff filter to trigger reload
        $("#delivery-staff-filter").change(function() {
          load();
        });
      });
      
      // Customer Location Modal functionality
      (function(){
        var modal = document.getElementById('cust-loc-modal');
        var body = document.getElementById('cust-loc-body');
        var activeCust = null;

        if (!modal || !body) return;

        var bsModal = new bootstrap.Modal(modal);

        document.addEventListener('click', function(e) {
          var target = e.target.closest('.customer-link');
          if (!target) return;

          e.preventDefault();
          var custId = target.getAttribute('data-cust');
          if (!custId) return;

          activeCust = custId;
          body.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
          
          // Show modal
          bsModal.show();

          // Load customer location data
          fetch('/ajax/customer_location.php?id=' + encodeURIComponent(custId))
            .then(response => response.text())
            .then(data => {
              body.innerHTML = data;
            })
            .catch(error => {
              body.innerHTML = '<div class="alert alert-danger">Error loading customer location</div>';
            });
        });
      })();

      // Toggle areas dropdown function
      function toggleAreas() {
        const areasSelect = document.getElementById('areas');
        const arrow = document.getElementById('area-arrow');
        
        if (areasSelect.style.display === 'none') {
          areasSelect.style.display = 'block';
          arrow.innerHTML = '▲';
        } else {
          areasSelect.style.display = 'none';
          arrow.innerHTML = '▼';
        }
      }
    </script>