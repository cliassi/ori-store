<?php
session_start();
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");
require_once("../core/functions.php");

$canEditDate = canEditDateOnly();

// Determine logged-in delivery staff name (if applicable)
$staff_name = '';
$rid = getFieldValue('sys_user_role', 'ur_role_id', "ur_user_id=" . $_POST['UID']);
$rolename = getFieldValue('sys_role', 'r_name', "id=" . $rid);

// print $rolename;
if ($rolename === 'Delivery Staff') {
  $staff_name = getFieldValue('staff_salary', 'name', "user_id=" . $_POST['UID']);
}

// print $staff_name;


error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (!function_exists('ensureMysqlColumn')) {
  function ensureMysqlColumn($table, $column, $definition)
  {
    global $c;

    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if (!$table || !$column) return false;

    $check = $c->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows > 0) return true;

    return (bool) $c->query("ALTER TABLE `$table` ADD `$column` $definition");
  }
}

ensureMysqlColumn("invoice_item", "collected_at", "DATETIME NULL DEFAULT NULL");
ensureMysqlColumn("invoice_item", "collected_by", "INT NULL DEFAULT NULL");

if (!function_exists('elapsedIntervalLabel')) {
  function elapsedIntervalLabel($startTs, $endTs = null)
  {
    if (!$startTs) return "N/A";
    $endTs = $endTs ?: time();

    $seconds = max(0, $endTs - $startTs);
    $days = (int) floor($seconds / 86400);
    $hours = (int) floor(($seconds % 86400) / 3600);
    $minutes = (int) floor(($seconds % 3600) / 60);

    $parts = [];
    if ($days > 0) $parts[] = $days . "d";
    if ($hours > 0 || $days > 0) $parts[] = $hours . "h";
    $parts[] = $minutes . "m";

    return implode(" ", $parts);
  }
}

extract($_POST);
print "<!-- Debug POST: customers=$customers, products=$products -->";

// Get delivery staff filter (will be overridden if role is Delivery Staff)
$deliveryStaff = isset($_POST['deliveryStaff']) ? trim($_POST['deliveryStaff']) : '';
// If logged-in user is Delivery Staff, always enforce filtering by their own name
if ($rolename === 'Delivery Staff' && function_exists('nn') && nn($staff_name)) {
  $deliveryStaff = $staff_name;
} elseif (function_exists('nn') && nn($staff_name) && !nn($deliveryStaff)) {
  // Otherwise, default to logged-in staff when available and no explicit filter provided
  $deliveryStaff = $staff_name;
}
// Debug: show resolved staff filter (HTML comment)
print "<!-- deliveryStaffResolved=" . htmlspecialchars($deliveryStaff) . ", staff_name=" . htmlspecialchars($staff_name) . " -->";

$collectedExpr = "(ii.collected_at IS NOT NULL OR EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";

$filter = " WHERE ";
$cities = preg_replace('/[^0-9,]/', '', $customers);
$products = preg_replace('/[^0-9,]/', '', $products);

if (nn($cities)) {
  $filter .= " city.id IN ($cities)";
}

if (nn($products)) {
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " p.product_category_id IN ($products)";
}

$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) < curdate()";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) >= DATE_SUB(curdate(), INTERVAL 7 DAY)";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity > ii.delivered";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " NOT $collectedExpr ";
// $filter .= ($filter != " WHERE " ? " AND " : " ") . " (ii.assigned_at IS NULL OR ii.assigned_to IS NULL OR ii.assigned_to = '')";

if (nn($branch_id)) {
  $branchId = (int) $branch_id;
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " (c.branch_id = $branchId OR c.branch_id IS NULL)";
}

$itemQuery = "SELECT c.id cust_id, c.company, c.code, c.city, c.location, c.mobile,
                     stock(pv.id) stock, i.id invoice_id, pc.name pc_name, ii.id iid, pv.id vid, pv.particulars, pv.min_stock, 
                     ii.quantity quantity, ii.delivered delivered, ii.price, ii.old_price, 
                     IFNULL(ii.delivery_date,i.invoice_date) dd
              FROM invoice i 
              INNER JOIN invoice_item ii ON i.id=ii.invoice_id
              INNER JOIN customer c ON c.id=i.customer_id
              LEFT JOIN city ON city.name=c.city
              INNER JOIN product_variance pv ON pv.id=ii.product_variance_id 
              INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
              INNER JOIN product_category pc ON p.product_category_id=pc.id
              $filter
              ORDER BY c.company, pc.name, ii.id";

$items = select($itemQuery);
// print "<div style='background:#f0f0f0; padding:10px; margin:10px; border:1px solid #ccc; font-size:11px; overflow-x:auto;'><strong>DEBUG QUERY:</strong><br><pre>" . htmlspecialchars($itemQuery) . "</pre></div>";

?>
<form method='post'>
  <div class="customer-container">
    <?php
    $currentCustomerId = null;
    $customerSerial = 1;
    $itemSerial = 1;
    $cat = "";
    
    while ($item = mysqli_fetch_object($items)) {
      // Check if we're switching to a new customer
      if ($currentCustomerId !== $item->cust_id) {
        // Close previous customer table if exists
        if ($currentCustomerId !== null) {
          echo "</tbody></table></div>";
        }
        
        // Build customer location text
        $locParts = [];
        $locParts[] = $item->company;
        if (nn($item->code)) $locParts[] = "Code: $item->code";
        if (nn($item->city)) $locParts[] = "Area: $item->city";
        if (nn($item->location)) $locParts[] = "Location: $item->location";
        if (nn($item->mobile)) $locParts[] = "Mobile: $item->mobile";
        $custLocText = htmlspecialchars(implode("\n", $locParts), ENT_QUOTES);
        
        // Print new customer header
        echo "<div class='customer-item customer-$item->cust_id'>
          <table class='table table-bordered'>
            <thead>
              <tr>
                <th colspan='4' class='text-center'>
                  <span id='cust-chevron-$item->cust_id' class='material-icons cust-toggle' data-cust='$item->cust_id' style='cursor:pointer; font-size:18px; vertical-align:middle;'>expand_more</span>
                  <a class='customer-link' href='#' style='cursor:pointer'>$item->company ($item->city) - <strong>$item->code</strong></a>
                  <span class='material-icons cust-loc-icon cust-loc-toggle' data-cust='$item->cust_id' data-loc='$custLocText'>chevron_right</span>
                  <input type='checkbox' class='selected-customer' data-cust='$item->cust_id' name='selected_customer[]' value='$item->cust_id' style='float:right; margin-top:2px;'>
                </th>
              </tr>
            </thead>
            <tbody id='cust-body-$item->cust_id'>";
        
        $currentCustomerId = $item->cust_id;
        $itemSerial = 1;
        $cat = "";
        $customerSerial++;
      }
      
      // Print category header if category changed
      if ($item->pc_name != $cat) {
        echo "<tr><th colspan='4'>$item->pc_name</th></tr>";
        $cat = $item->pc_name;
      }
      
      // Calculate remaining quantity
      $remainingQty = $item->quantity - $item->delivered;
      if ($remainingQty <= 0) continue;
      
      // Print item row
      $partAttr = htmlspecialchars($item->particulars, ENT_QUOTES);
      echo "<tr data-vid='$item->vid' data-qty='$remainingQty' data-unit-price='$item->price' data-particulars='$partAttr'>
        <td style='width:30px;'><input type='checkbox' class='iid-date' name='iid[$item->iid]' value='$item->iid'> " . ($canEditDate ? "<a href='#' id='invoice-item-date-$item->iid' data-dd='" . df($item->dd) . "' onclick='setDate(this, $item->iid); return false;' style='text-decoration:none; color:#333; font-weight:bold; cursor:pointer;'>$itemSerial</a>" : "<span style='font-weight:bold;'>$itemSerial</span>") . "</td>
        <td>$item->particulars</td>
        <td style='text-align:center; width:60px;'>" . nf($item->price * $remainingQty) . "</td>
        <td style='text-align:center; width:50px;'>$remainingQty</td>
      </tr>";
      $itemSerial++;
    }
    
    // Close last customer table
    if ($currentCustomerId !== null) {
      echo "</tbody></table></div>";
    }
    ?>
  </div>
</form>
<style>
  @import url('https://fonts.googleapis.com/icon?family=Material+Icons');

  * {
    box-sizing: border-box;
  }

  .customer-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    /* Optional spacing */
    font-size: .7rem;
  }

  .customer-item {
    width: 100%;
    max-width: 100%;
    flex: 0 0 100%;
    white-space: normal;
    overflow-x: auto;
  }

  .customer-item table {
    width: 100%;
    table-layout: fixed;
  }

  .customer-item th,
  .customer-item td {
    vertical-align: middle;
    overflow: hidden;
  }

  .customer-item td:nth-child(1),
  .customer-item th:nth-child(1) {
    width: 35px;
    text-align: center;
  }

  .customer-item th:nth-child(1) {
    padding: 0;
    vertical-align: middle;
  }

  .customer-item td:nth-child(1) {
    /* white-space: nowrap;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px; */
  }

  .customer-item td:nth-child(1) div {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 3px;
  }

  .customer-item td:nth-child(1) input[type=checkbox] {
    display: block;
    margin: 0;
    width: 15px;
    height: 15px;
  }

  input[type=checkbox] {
    width: 15px;
    height: 15px;
  }

  .customer-item td:nth-child(1) a {
    display: block;
    text-align: center;
    line-height: 1;
    margin: 0;
  }

  .customer-item td:nth-child(n+3),
  .customer-item th:nth-child(n+3) {
    width: 40px;
    white-space: nowrap;
  }

  .customer-item td:nth-child(2) {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .customer-item td:nth-child(3) a,
  .customer-item td:nth-child(2) a {
    display: inline-block;
    max-width: 100%;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    vertical-align: bottom;
  }

  input[type='number'] {
    width: 100%;
    min-width: 70px;
  }

  a {
    text-decoration: underline;
  }

  a.customer-link {
    text-decoration: none !important;
    color: #333 !important;
  }

  .hide-info .store-detail,
  .all-collected {
    display: none;
  }

  .all-collected.show {
    display: table-row;
  }

  .order-qty-col {
    /* font-size: .9rem; */
  }

  .order-meta {
    font-size: 0.75rem;
    color: #666;
    margin-top: 4px;
    line-height: 1.2;
  }

  #consolidated-container {
    display: none !important;
  }

  #consolidated-container.show {
    display: block !important;
  }
</style>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<form method='post' id='dcollectForm'>
  <div class="customer-container">
    <?php
    // Items are already processed in the loop above
    ?>

    <div id='consolidated-container' class='customer-item' style='display:none;'>
      <table class='table table-bordered toggle-store-info hide-info'>
        <thead>
          <tr>
            <th style='width:50px; text-align:center;'>#</th>
            <th>Name</th>
            <th style='width:40px; text-align:center;'>Price</th>
            <th style='width:40px; text-align:center;' class='order-qty-col'>Order Qty</th>
            <!-- <th style='width:80px; text-align:center;'>Select</th> -->
          </tr>
        </thead>
        <tbody id='consolidated-body'></tbody>
      </table>
    </div>
  </div>

  <script>
    window.__dcollectCollapsedOnce = window.__dcollectCollapsedOnce || false;

    function collapseAllCustomers() {
      document.querySelectorAll('tbody[id^="cust-body-"]').forEach(function(tb) {
        tb.style.display = 'none';
      });
      document.querySelectorAll('[id^="cust-chevron-"]').forEach(function(ch) {
        if (ch.classList && ch.classList.contains('material-icons')) {
          ch.textContent = 'chevron_right';
        }
      });
    }

    function expandAllCustomers() {
      document.querySelectorAll('tbody[id^="cust-body-"]').forEach(function(tb) {
        tb.style.display = '';
      });
      document.querySelectorAll('[id^="cust-chevron-"]').forEach(function(ch) {
        if (ch.classList && ch.classList.contains('material-icons')) {
          ch.textContent = 'expand_more';
        }
      });
    }

    function rebuildConsolidatedTable() {
      var container = document.getElementById('consolidated-container');
      var tbody = document.getElementById('consolidated-body');
      if (container) container.classList.remove('show');
      if (tbody) tbody.innerHTML = '';
    }

    document.addEventListener('change', function(e) {
      var cb = e.target;
      if (!(cb instanceof HTMLInputElement)) return;

      if (cb.classList.contains('selected-customer')) {
        var custId = cb.getAttribute('data-cust');
        if (custId) {
          var body = document.getElementById('cust-body-' + custId);
          if (body) {
            body.querySelectorAll('input.iid-date').forEach(function(itemCb) {
              if (itemCb instanceof HTMLInputElement) {
                itemCb.checked = cb.checked;
              }
            });
          }
        }

        // Determine if any customers are selected
        var anySelected = document.querySelectorAll('input.selected-customer:checked').length > 0;
        if (!anySelected) {
          expandAllCustomers();
          window.__dcollectCollapsedOnce = false;
        } else if (cb.checked && !window.__dcollectCollapsedOnce) {
          collapseAllCustomers();
          window.__dcollectCollapsedOnce = true;
        }
        rebuildConsolidatedTable();
      }

      if (cb.classList.contains('iid-date')) {
        rebuildConsolidatedTable();
      }
    });

    document.addEventListener('click', function(e) {
      var toggleEl = e.target.closest('.cust-toggle');
      if (!toggleEl) return;

      e.preventDefault();

      var custId = toggleEl.getAttribute('data-cust');
      if (!custId) return;

      var body = document.getElementById('cust-body-' + custId);
      var chevron = document.getElementById('cust-chevron-' + custId);
      if (!body || !chevron) return;

      var isHidden = window.getComputedStyle(body).display === 'none';
      body.style.display = isHidden ? '' : 'none';

      chevron.textContent = isHidden ? 'expand_more' : 'chevron_right';
    });

    rebuildConsolidatedTable();

    // Handle delivery staff reassignment
    document.addEventListener('change', function(e) {
      if (!e.target.classList.contains('delivery-staff-reassign')) return;
      
      var select = e.target;
      var newStaff = select.value;
      var customerId = select.getAttribute('data-customer-id');
      var oldStaff = select.querySelector('option[selected]');
      var oldStaffName = oldStaff ? oldStaff.textContent : 'None';
      
      if (!newStaff) return;
      
      var isUnassign = (newStaff === '--UNASSIGN--');
      var title = isUnassign ? 'Unassign Delivery Staff?' : 'Reassign Delivery Staff?';
      var text = isUnassign ? `Remove assignment from "${oldStaffName}"?` : `Change assignment from "${oldStaffName}" to "${newStaff}"?`;
      var confirmText = isUnassign ? 'Yes, unassign!' : 'Yes, reassign!';
      var confirmColor = isUnassign ? '#d33' : '#3085d6';
      
      // Use SweetAlert for confirmation
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: title,
          text: text,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: confirmColor,
          cancelButtonColor: '#6c757d',
          confirmButtonText: confirmText
        }).then((result) => {
          if (result.isConfirmed) {
            reassignDeliveryStaff(customerId, newStaff);
          } else {
            // Reset dropdown to original value
            select.value = oldStaff ? oldStaff.value : '';
          }
        });
      } else {
        // Fallback to confirm dialog
        var confirmMessage = isUnassign ? `Remove assignment from "${oldStaffName}"?` : `Change assignment from "${oldStaffName}" to "${newStaff}"?`;
        if (confirm(confirmMessage)) {
          reassignDeliveryStaff(customerId, newStaff);
        } else {
          select.value = oldStaff ? oldStaff.value : '';
        }
      }
    });

    function reassignDeliveryStaff(customerId, newStaff) {
      // Use hidden form to submit reassignment
      var form = document.getElementById('reassign-form');
      var deliveryStaffInput = document.getElementById('reassign-delivery-staff');
      var customerIdInput = document.getElementById('reassign-customer-id');
      
      if (form && deliveryStaffInput && customerIdInput) {
        deliveryStaffInput.value = newStaff;
        customerIdInput.value = customerId;
        form.submit();
      }
    }
  </script>
  <div id="cust-loc-modal" style="position:fixed;z-index:99999;left:50%;top:30%;transform:translate(-50%,-30%);background:#fff;border:1px solid #ccc;border-radius:8px;padding:10px 12px;width:min(360px,calc(100vw - 40px));box-shadow:0 10px 25px rgba(0,0,0,.18);display:none;">
  <div style="font-weight:700;margin-bottom:6px;">Customer Location</div>
  <div id="cust-loc-body" style="font-size:.85rem;line-height:1.35;white-space:pre-wrap;"></div>
</div>
<script>
  (function () {
    var modal = document.getElementById('cust-loc-modal');
    var body = document.getElementById('cust-loc-body');
    function hide() { if (modal) modal.style.display = 'none'; }
    function show(text) { if (!modal || !body) return; body.textContent = text || ''; modal.style.display = 'block'; }

    document.addEventListener('click', function (e) {
      var toggle = e.target && e.target.closest && e.target.closest('.cust-loc-toggle');
      if (toggle) {
        e.preventDefault();
        var loc = toggle.getAttribute('data-loc') || '';
        show(loc);
        return;
      }
      if (!modal || modal.style.display !== 'block') return;
      if (e.target.closest && e.target.closest('#cust-loc-modal')) return;
      hide();
    }, true);
  })();
</script>
<script>
  (function () {
    document.addEventListener('submit', function (e) {
      var f = e.target;
      if (!f || f.id !== 'dcollectForm') return;
      var anyChecked = document.querySelectorAll('#dcollectForm input.iid-date:not(.consolidated-toggle):checked').length > 0;
      if (!anyChecked) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'No items selected',
            text: 'Please select at least one item before submitting.'
          });
        } else {
          alert('Please select at least one item before submitting.');
        }
      }
    });
  })();
</script>
<script>
  (function () {
    document.addEventListener('click', function (e) {
      var btn = e.target && e.target.closest && e.target.closest('#btnCollect');
      if (!btn) return;
      var form = document.getElementById('dcollectForm');
      if (!form) return;
      var anyChecked = form.querySelectorAll('input.iid-date:not(.consolidated-toggle):checked').length > 0;
      if (!anyChecked) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'No items selected', text: 'Please select at least one item before submitting.' });
        } else {
          alert('Please select at least one item before submitting.');
        }
      }
    }, true);
  })();
</script>
</form>

<!-- Hidden form for delivery staff reassignment -->
<form id="reassign-form" method="post" style="display: none;">
  <input type="hidden" name="assign" value="yes">
  <input type="hidden" name="delivery_staff" id="reassign-delivery-staff">
  <input type="hidden" name="selected_customer[]" id="reassign-customer-id">
</form>

<?php
// Handle delivery staff assignment/reassignment
if (isset($_POST['assign']) && $_POST['assign'] == 'yes') {
  $deliveryStaff = isset($_POST['delivery_staff']) ? trim($_POST['delivery_staff']) : '';
  $selectedCustomers = isset($_POST['selected_customer']) && is_array($_POST['selected_customer']) ? $_POST['selected_customer'] : [];
  
  if (!empty($selectedCustomers)) {
    foreach ($selectedCustomers as $customerId) {
      $customerId = (int) $customerId;
      if ($customerId <= 0) continue;
      
      if ($deliveryStaff === '--UNASSIGN--') {
        // Unassign delivery staff
        update("invoice_item ii INNER JOIN invoice i ON i.id=ii.invoice_id", 
               "ii.assigned_to=NULL, ii.assigned_at=NULL", 
               "i.customer_id=$customerId AND ii.assigned_to IS NOT NULL");
      } else if (!empty($deliveryStaff)) {
        // Assign delivery staff
        $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
        update("invoice_item ii INNER JOIN invoice i ON i.id=ii.invoice_id", 
               "ii.assigned_to='$deliveryStaffSql', ii.assigned_at=NOW()", 
               "i.customer_id=$customerId AND ii.quantity > ii.delivered AND IFNULL(ii.delivery_date,i.invoice_date) <= curdate()");
      }
    }
  }
  
  // Redirect back to avoid resubmission
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit;
}
?>