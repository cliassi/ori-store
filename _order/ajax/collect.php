<?php
session_start();
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");

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
print "<!-- Debug POST: order=$order, customers=$customers, products=$products -->";
if ($order == 'false') exit;

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

$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) <= curdate()";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-25'";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity > ii.delivered";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " NOT $collectedExpr ";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " (ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";

// Add delivery staff filter if specified
if (nn($deliveryStaff)) {
  $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " LOWER(TRIM(ii.assigned_to)) = LOWER(TRIM('$deliveryStaffSql'))";
}

if (nn($branch_id)) {
  $branchId = (int) $branch_id;
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " (c.branch_id = $branchId OR c.branch_id IS NULL)";
}

$query = "SELECT distinct c.* FROM customer c 
          INNER JOIN invoice i ON c.id=i.customer_id 
          INNER JOIN invoice_item ii ON i.id=ii.invoice_id 
          INNER JOIN product_variance pv ON pv.id=ii.product_variance_id
          INNER JOIN product p ON p.id=pv.product_id
          LEFT JOIN city ON city.name=c.city 
          $filter";

$customers = select($query);
print "<!-- Debug Query: " . htmlspecialchars($query) . " -->";

?>
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
    $customerSerial = 1;
    while ($customer = mysqli_fetch_object($customers)) {
      // Calculate order meta (Order Time and Pending Time)
      $pendingOrderMeta = "";
      $metaQuery = "SELECT created_at order_at, created_by, GREATEST(0, TIMESTAMPDIFF(MINUTE, created_at, NOW())) pending_minutes FROM invoice WHERE customer_id=$customer->id ORDER BY created_at DESC LIMIT 1";
      $meta = mysqli_fetch_object(select($metaQuery));
      if ($meta && nn($meta->order_at)) {
        $orderTs = strtotime($meta->order_at);
        $orderByName = '';
        if (isset($meta->created_by) && (int)$meta->created_by > 0) {
          $orderByName = getName('sys_user', (int)$meta->created_by, 'u_username');
        }
        if (!nn($orderByName) && isset($meta->created_by) && (int)$meta->created_by > 0) {
          $orderByName = "User #" . (int)$meta->created_by;
        }

        $pendingMinutesTotal = isset($meta->pending_minutes) ? (int) $meta->pending_minutes : 0;
        if ($pendingMinutesTotal < 0) $pendingMinutesTotal = 0;
        $pendingHoursTotal = (int) floor($pendingMinutesTotal / 60);
        $pendingMinutes = $pendingMinutesTotal % 60;
        $pendingDays = (int) floor($pendingHoursTotal / 24);
        $pendingHours = $pendingHoursTotal % 24;
        $pendingLabel = ($pendingDays > 0 ? ($pendingDays . "d ") : "") . sprintf("%02d:%02d", $pendingHours, $pendingMinutes);

        $pendingOrderMeta = "<div class='order-meta'>Order Time: " . date('h:i a', $orderTs) . (nn($orderByName) ? " <b>$orderByName</b>" : "") . "<br>Pending Time: " . $pendingLabel . "</div>";
      }

      // Build customer location text for modal, similar to ajax/order.php
      $locParts = [];
      $locParts[] = $customer->company;
      if (isset($customer->code) && nn($customer->code)) $locParts[] = "Code: $customer->code";
      if (isset($customer->city) && nn($customer->city)) $locParts[] = "Area: $customer->city";
      if (isset($customer->location) && nn($customer->location)) $locParts[] = "Location: $customer->location";
      if (isset($customer->phone) && nn($customer->phone)) $locParts[] = "Phone: $customer->phone";
      if (isset($customer->mobile) && nn($customer->mobile)) $locParts[] = "Mobile: $customer->mobile";
      $custLocText = htmlspecialchars(implode("\n", $locParts), ENT_QUOTES);

      // vd($customer);
      // print "<div>$customer->id</div>";
      $con = "<div class='customer-item customer-$customer->id'>
			<table class='table table-bordered toggle-store-info hide-info'>
				<thead>
					<tr>";
$con .= "<th colspan='5' style='text-align:center; text-transform:uppercase'>
        <span id='cust-chevron-$customer->id' class='material-icons cust-toggle' data-cust='$customer->id' style='cursor:pointer; font-size:18px; vertical-align:middle; margin-top:4px; float:left; margin-left:-12px; padding: 5px;'>expand_more</span><a class='customer-link' href='?page=customer_details&id=$customer->id' onclick='event.stopPropagation()' style='cursor:pointer'>$customer->company ($customer->city) - <strong style='font-size:large'>$customer->code</strong></a><span class='material-icons cust-loc-icon cust-loc-toggle' data-cust='$customer->id' data-loc='$custLocText' style='cursor:pointer; padding: 5px; position:absolute; font-size:18px; float: right; vertical-align:middle; user-select:none; color:#444; margin-left:8px;'>chevron_right</span>$pendingOrderMeta";
        $con .= " \n        <span style='display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:#eee; color:#333; font-weight:600; border:1px solid #ccc;'>$customerSerial</span>\n         <input type='checkbox' class='selected-customer' data-cust='$customer->id' name='selected_customer[]' value='$customer->id' style='float:right; margin-top:2px;'>";
        $con .= "</th>";
      $con .= "</tr>
				</thead>
				<tbody id='cust-body-$customer->id'>";

      $cat = "";
      $itemFilter = "i.customer_id=$customer->id ";
      $itemFilter .= " AND ii.delivered < ii.quantity";
      $itemFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) <= curdate()";
      $itemFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-26'";
      $itemFilter .= " AND (ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";
      $itemFilter .= " AND (ii.collected_at IS NULL AND NOT EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";

      if (nn($cities)) {
        $itemFilter .= " AND city.id IN ($cities)";
      }

      if (nn($products)) {
        $itemFilter .= " AND p.product_category_id IN ($products)";
      }
      if (function_exists('nn') && nn($deliveryStaff)) {
        $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
        $itemFilter .= " AND LOWER(TRIM(ii.assigned_to)) = LOWER(TRIM('$deliveryStaffSql'))";
      }
      $stField = 'stock';
      // if($delivery) $stField = 'stockCurrent';
      // if($pendingList) $stField = 'stockPending';
      $pq = "SELECT stock(pv.id) stock, i.customer_id, i.id, p.name, pc.name pc_name, ii.id iid, p.id pid, p.name, pv.id vid, pv.particulars, pv.min_stock, SUM(ii.quantity) quantity, 
						ii.delivered delivered, ii.price, ii.old_price, IFNULL(ii.delivery_date,i.invoice_date) dd FROM invoice i 
						INNER JOIN invoice_item ii ON i.id=ii.invoice_id
						INNER JOIN customer c ON c.id=i.customer_id
						LEFT JOIN city ON c.city=city.name
						INNER JOIN `product_variance` pv ON pv.id=ii.product_variance_id 
						INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
						INNER JOIN product_category pc ON p.product_category_id=pc.id
						" . ($itemFilter ? "WHERE" : "") . " $itemFilter ";
      $pq .= "GROUP BY pc.name, ii.product_variance_id, ii.id";
      // $pq .= "GROUP BY pc.name, ii.product_variance_id, ii.id";
      // if($customer->id == 3) print $pq;
      $items = select($pq);

      $printedCon = false;

      $ci = 1;
      $ic = 0;
      // vd($items);
      while ($i = mysqli_fetch_object($items)) {
        $i->quantity = $i->quantity - $i->delivered;
        if ($i->quantity <= 0) {
          $ci++;
          continue;
        }

        if (!$printedCon) {
          print $con;
          $printedCon = true;
        }

if ($i->pc_name != $cat) {
          print "<tr><th colspan='2'>$i->pc_name</th>";
          print "<th>Price</th><th class='order-qty-col'>Qty</th></tr>";
          $cat = $i->pc_name;
        }

$collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
        $partAttr = htmlspecialchars($i->particulars, ENT_QUOTES);
        print "<tr data-vid='$i->vid' data-qty='$i->quantity' data-collected='$collected' data-unit-price='$i->price' data-particulars='$partAttr' class='" . ($i->quantity - $collected == 0 ? 'all-collected' : '') . "'><td><div><input type='checkbox' class='iid-date' name='iid[$i->iid]' value='$i->iid'> <a href='#' id='invoice-item-date-$i->iid' data-dd='" . df($i->dd) . "' onClick='setDate(this, $i->iid)'>$ci</a></div></td>
							<td>$i->particulars (<a class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>$i->price</a>) </td>";
$collected = $i->quantity;
        print "<td>";
        if ($i->old_price && false) {
          print nf($i->price * $i->quantity);
        } else {
          print "<a data-id='$i->iid' id='invoice-item-price-$i->iid' class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price * $i->quantity) . "</a>";
        }
        print "</td><td class='text-center order-qty-col'><a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$collected</a></td>";
        print "</tr>";
        $ic++;

        $ci++;
      }
      if ($printedCon) {
        print "</tbody>
					</table>";
        
        // Determine predominant assigned staff using the same filters as visible items
        $currentAssignedStaff = '';
        $hasMultipleAssignedStaff = false;

        $asFilter = [];
        $asFilter[] = "i.customer_id={$customer->id}";
        $asFilter[] = "ii.delivered < ii.quantity";
        $asFilter[] = "IFNULL(ii.delivery_date,i.invoice_date) <= curdate()";
        // Mirror the mobile list lower bound used above
        $asFilter[] = "IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-26'";
        $asFilter[] = "(ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";
        $asFilter[] = "(ii.collected_at IS NULL AND NOT EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";
        if (function_exists('nn') && nn($cities)) {
          $asFilter[] = "city.id IN ($cities)";
        }
        if (function_exists('nn') && nn($products)) {
          $asFilter[] = "p.product_category_id IN ($products)";
        }
        if (function_exists('nn') && nn($deliveryStaff)) {
          $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
          $asFilter[] = "LOWER(TRIM(ii.assigned_to)) = LOWER(TRIM('$deliveryStaffSql'))";
        }

        $asWhere = $asFilter ? ("WHERE " . implode(' AND ', $asFilter)) : '';
        $assignedQuery = "SELECT ii.assigned_to, COUNT(*) cnt
                         FROM invoice_item ii 
                         INNER JOIN invoice i ON i.id=ii.invoice_id 
                         INNER JOIN customer c ON c.id=i.customer_id
                         LEFT JOIN city ON c.city=city.name
                         INNER JOIN product_variance pv ON pv.id=ii.product_variance_id
                         INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
                         $asWhere
                         GROUP BY ii.assigned_to
                         ORDER BY cnt DESC";
        $assignedResult = select($assignedQuery);
        if ($assignedResult) {
          $first = mysqli_fetch_object($assignedResult);
          if ($first && isset($first->assigned_to)) {
            $currentAssignedStaff = $first->assigned_to;
            $hasMultipleAssignedStaff = ($assignedResult->num_rows > 1);
          }
        }
        
        // Add delivery staff dropdown for reassignment (hidden for Delivery Staff role)
        if ($rolename !== 'Delivery Staff') {
          print "<div style='padding: 5px 8px; border-top: 1px solid #ddd; background-color: #f9f9f9;'>";
          print "<label style='font-weight: bold; margin-right: 8px; font-size: 0.9rem;'>Assigned to:</label>";
          print "<select class='form-control delivery-staff-reassign' data-customer-id='$customer->id' style='width: 180px; display: inline-block; padding: 3px 6px; font-size: 0.85rem;'>";
          if ($hasMultipleAssignedStaff) {
            print "<option value='' selected disabled>-- MULTIPLE --</option>";
          } else {
            print "<option value=''>Select delivery staff...</option>";
          }
          print "<option value='--UNASSIGN--' style='color: #dc3545; font-weight: bold;'>-- UNASSIGN --</option>";
          
          $staffObjs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
          while ($staff = mysqli_fetch_object($staffObjs)) {
            $selected = (!$hasMultipleAssignedStaff && $staff->name == $currentAssignedStaff) ? 'selected' : '';
            print "<option value='" . htmlspecialchars($staff->name) . "' $selected>" . htmlspecialchars($staff->name) . "</option>";
          }
          
          print "</select>";
          print "</div>";
        }
        print "</div>";
      }
      if ($ic == 0) print '<script>$(".customer-' . $customer->id . '").hide();</script>';
      // <tr><td>1</td><td>SeaMaster 1500ml X 12</td><td>180</td></tr>
      // <tr><td>2</td><td>100 Plus 250ml Ctn X 24</td><td>28</td></tr>
      // <tr><td>3</td><td>Coca Cola 250ml X 24</td><td>120</td></tr>
      // <tr><th colspan='3'>Fresh</th></tr>
      // <tr><td>4</td><td>Red Bull 250ml X 24</td><td>40</td></tr>
      // <tr><td>5</td><td>Pepsi 250ml X 24</td><td>26</td></tr>
      // <tr><td>6</td><td>100 Plus 250ml Ctn X 24</td><td>18</td></tr>
      // <tr><td>7</td><td>Coca Cola 250ml X 24</td><td>28</td></tr>
      // <tr><th colspan='3'>Frozen</th></tr>
      // <tr><td>8</td><td>Red Bull 250ml X 24</td><td>26</td></tr>
      // <tr><td>9</td><td>Pepsi 250ml X 24</td><td>18</td></tr>
      // <tr><td>10</td><td>100 Plus 250ml Ctn X 24</td><td>28</td></tr>
if (!$printedCon) continue;
      $customerSerial++;
    }
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
      var map = new Map();

      var selected = document.querySelectorAll('input.selected-customer:checked');
      var container = document.getElementById('consolidated-container');
      var tbody = document.getElementById('consolidated-body');
      if (!container || !tbody) return;

      // Always start by hiding the container
      container.classList.remove('show');
      tbody.innerHTML = '';
      console.log('rebuildConsolidatedTable called. Selected customers:', selected.length);

      if (!selected.length) {
        return;
      }

      selected.forEach(function(cb) {
        var custId = cb.getAttribute('data-cust');
        var body = document.getElementById('cust-body-' + custId);
        if (!body) return;

        body.querySelectorAll('tr[data-vid]').forEach(function(row) {
          var vid = row.getAttribute('data-vid');
          var qty = parseFloat(row.getAttribute('data-qty') || '0') || 0;
          var particulars = row.getAttribute('data-particulars') || '';
          var unitPrice = parseFloat(row.getAttribute('data-unit-price') || '0') || 0;
          if (!vid || qty <= 0) return;

          var cur = map.get(vid);
          if (!cur) {
            map.set(vid, {
              particulars: particulars,
              qty: qty,
              unitPrice: unitPrice
            });
          } else {
            cur.qty += qty;
          }
        });
      });

      tbody.innerHTML = '';

      // Only show container if we have actual items in the map
      if (!map.size || map.size === 0) {
        container.classList.remove('show');
        console.log('Hiding consolidated container - no items in map. Map size:', map.size);
        return;
      }

      // Only show if we have items to display
      container.classList.add('show');
      console.log('Showing consolidated container - items found. Map size:', map.size);

      var fmt = new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 2
      });
      var serial = 1;

      Array.from(map.values())
        .sort(function(a, b) {
          return (a.particulars || '').localeCompare(b.particulars || '');
        })
        .forEach(function(item) {
          var tr = document.createElement('tr');

          var tdSerial = document.createElement('td');
          tdSerial.className = 'text-center';
          var wrap = document.createElement('div');

          var cb = document.createElement('input');
          cb.type = 'checkbox';
          cb.className = 'iid-date consolidated-toggle';
          cb.checked = true; // Auto-select consolidated items
          wrap.appendChild(cb);

          var a = document.createElement('a');
          a.href = '#';
          a.className = 'has-checkbox';
          a.textContent = String(serial++);
          wrap.appendChild(a);

          tdSerial.appendChild(wrap);

          var tdName = document.createElement('td');
          tdName.textContent = item.particulars;

          var tdPrice = document.createElement('td');
          tdPrice.className = 'text-center';
          tdPrice.textContent = fmt.format(item.unitPrice * item.qty);

          var tdOrderQty = document.createElement('td');
          tdOrderQty.className = 'text-center order-qty-col';
          tdOrderQty.textContent = fmt.format(item.qty);

          tr.appendChild(tdSerial);
          tr.appendChild(tdName);
          tr.appendChild(tdPrice);
          tr.appendChild(tdOrderQty);
          tbody.appendChild(tr);
        });

      // build consolidated totals footer (Qty only)
      var totalQty = 0;
      map.forEach(function (item) {
        totalQty += (parseFloat(item.qty) || 0);
      });

      var table = container.querySelector('table');
      if (table) {
        var oldFoot = table.querySelector('tfoot');
        if (oldFoot) oldFoot.remove();
        var tfoot = document.createElement('tfoot');
        var trf = document.createElement('tr');

        var thLabel = document.createElement('th');
        thLabel.colSpan = 3; // #, Name, Price
        thLabel.className = 'text-end';
        thLabel.textContent = 'Total';

        var thQty = document.createElement('th');
        thQty.className = 'text-center order-qty-col';
        thQty.textContent = fmt.format(totalQty);

        trf.appendChild(thLabel);
        trf.appendChild(thQty);
        tfoot.appendChild(trf);
        table.appendChild(tfoot);
      }
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