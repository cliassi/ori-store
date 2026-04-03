<?php
session_start();
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
$staff_name = '';
if(rolename() == 'Delivery Staff'){
  $staff_name = getFieldValue('staff_salary', 'name', "user_id=" . uid());
}

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
if ($order == 'false' && $pending == 'false' && $delivery == 'false' && $collection == 'false') exit;

// Get delivery staff filter
$deliveryStaff = isset($_POST['deliveryStaff']) ? trim($_POST['deliveryStaff']) : '';
// If a Delivery Staff is logged in and no explicit filter provided, default to their name
if (nn($staff_name) && !nn($deliveryStaff)) {
  $deliveryStaff = $staff_name;
}

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
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity > ii.delivered";
$filter .= ($filter != " WHERE " ? " AND " : " ") . (($delivery == 'true' || $collection == 'true') ? " $collectedExpr " : " NOT $collectedExpr ");
$filter .= ($filter != " WHERE " ? " AND " : " ") . " (ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";

// Add delivery staff filter if specified
if (nn($deliveryStaff)) {
  $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.assigned_to = '$deliveryStaffSql'";
}

if (uid() == 1 && nn($branch_id)) {
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
// print $query;

?>
<style>
  @import url('https://fonts.googleapis.com/icon?family=Material+Icons');

  * {
    box-sizing: border-box;
  }

  .customer-container {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
    font-size: .7rem;
    align-items: start;
  }

  .customer-container.has-consolidated {
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  }

  .customer-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .customer-container.has-consolidated .customer-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .customer-item {
    padding: 5px;
    /* margin: 5px; */
    width: 100%;
    white-space: nowrap;
    border: 1px solid #ccc;
    overflow-x: auto;
  }

  #consolidated-container {
    grid-column: 2;
    grid-row: 1;
    align-self: start;
  }

  .customer-item table {
    width: 100%;
    /* table-layout: fixed; */
  }

  .customer-item th:nth-child(1),
  .customer-item td:nth-child(1) {
    width: 20px !important;
    min-width: 20px !important;
    max-width: 20px !important;
    padding: 0 !important;
    vertical-align: middle;
  }

  .customer-item td:nth-child(1) {
    overflow: hidden;
  }

  .customer-item td:nth-child(1) div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
  }

  .customer-item td:nth-child(1) input[type=checkbox] {
    display: block;
    margin: 0 auto;
  }

  .customer-item td:nth-child(1) a {
    display: block;
    text-align: center;
    line-height: 1;
    margin-top: 0;
  }

  .customer-item td:nth-child(n+3),
  .customer-item th:nth-child(n+3) {
    width: 95px;
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
    min-width: 80px;
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

  .customer-item .price-col,
  .customer-item .order-qty-col {
    width: 62px !important;
    min-width: 62px;
    white-space: nowrap;
    text-align: center;
  }

  .order-meta {
    margin-top: 4px;
    font-size: .72rem;
    line-height: 1.25;
    color: #666;
    white-space: normal;
  }

  .cust-loc-icon {
    margin-left: 6px;
    cursor: pointer;
    font-size: 18px;
    vertical-align: middle;
    color: #444;
  }

  .cust-loc-modal {
    position: fixed;
    z-index: 99999;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: min(360px, 92vw);
    background: #fff;
    border: 1px solid rgba(0, 0, 0, .15);
    box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
    padding: 12px 14px;
    border-radius: 10px;
    display: none;
  }

  .cust-loc-modal.show {
    display: block;
  }

  .cust-loc-modal .title {
    font-weight: 700;
    margin-bottom: 6px;
  }

  .cust-loc-modal .body {
    font-size: .85rem;
    line-height: 1.35;
    white-space: pre-wrap;
  }
</style>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<form method='post'>
  <div class="customer-container">
    <div class="customer-grid">
      <?php
      $pending = $pending == "true";
      $delivery = $delivery == "true";
      $collection = $collection == "true";
      $pendingList = false; //$pendingList == "true";
      if ($pendingList) $delivery = $pendingList;
      $customerSerial = 1;
      while ($customer = mysqli_fetch_object($customers)) {
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
        if ($delivery || $collection) {
          $con .= "<th colspan='5'>TOTAL CONFIRMED ORDERED LIST <span style='float:right'><i class='fas fa-eye' onclick='toggleCollected()'></i></span></th>";
        } else {
         $con .= "<th class='serial-col text-center' style='width:20px; min-width:20px; max-width:20px;'>$customerSerial</th> 
         <th colspan='3' class='text-center' style='white-space:normal'><span id='cust-chevron-$customer->id' class='material-icons cust-toggle' data-cust='$customer->id' style='margin-right:6px; cursor:pointer; font-size:18px; vertical-align:middle;'>expand_more</span><a class='customer-link' href='" . ROOT . "/customer/details/$customer->id' style='cursor:pointer'>$customer->company ($customer->city) - <strong style='font-size:large'>$customer->code</strong></a><span class='material-icons cust-loc-icon cust-loc-toggle' data-cust='$customer->id' data-loc='$custLocText'>chevron_right</span>$pendingOrderMeta";
          if ($order == 'true' || $pending || $delivery) {
            $con .= " <input type='checkbox' class='selected-customer' data-cust='$customer->id' name='selected_customer[]' value='$customer->id' style='float:right; margin-top:2px;'>";
          }
          $con .= "</th>";
        }
        $con .= "</tr>
                </thead>
                <tbody id='cust-body-$customer->id'>";

        $cat = "";
        $filter = "i.customer_id=$customer->id ";
        if ($filter) {
          $filter .= " AND ";
        }
        $filter .= " ii.delivered < ii.quantity AND IFNULL(ii.delivery_date,i.invoice_date) <= curdate() AND IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-25'";
        if ($delivery || $collection) {
          $filter = "IFNULL(ii.delivery_date,i.invoice_date) <= curdate()";
        }

        if (nn($cities)) {
          if ($filter) {
            $filter .= " AND ";
          }
          $filter .= " city.id IN ($cities)";
        }

        if (nn($products)) {
          if ($filter) {
            $filter .= " AND ";
          }
          $filter .= " p.product_category_id IN ($products)";
        }
        if ($filter) {
          $filter .= " AND ";
        }
        $filter .= (($delivery || $collection) ? $collectedExpr : "NOT $collectedExpr");
        $filter .= " AND (ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";
        // When a delivery staff is targeted (explicitly or inferred), further constrain items
        if (nn($deliveryStaff)) {
          $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
          $filter .= " AND ii.assigned_to = '$deliveryStaffSql'";
        }
        $stField = 'stock';
        // if($delivery) $stField = 'stockCurrent';
        // if($pendingList) $stField = 'stockPending';
        $pq = "SELECT $stField(pv.id) stock, i.customer_id, i.id, p.name, pc.name pc_name, ii.id iid, p.id pid, p.name, pv.id vid, pv.particulars, pv.min_stock, SUM(ii.quantity) quantity, 
                        ii.delivered delivered, ii.price, ii.old_price, IFNULL(ii.delivery_date,i.invoice_date) dd FROM invoice i 
                        INNER JOIN invoice_item ii ON i.id=ii.invoice_id
                        INNER JOIN customer c ON c.id=i.customer_id
                        LEFT JOIN city ON c.city=city.name
                        INNER JOIN `product_variance` pv ON pv.id=ii.product_variance_id 
                        INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
                        inner join product_category pc ON p.product_category_id=pc.id
                        " . ($filter ? "WHERE" : "") . " $filter ";
        $tq = $pq . "GROUP BY pc.name, ii.product_variance_id";
        if ($delivery == "true") {
          $pq .= "GROUP BY pc.name, ii.product_variance_id";
        } else {
          $pq .= "GROUP BY pc.name, ii.product_variance_id, ii.id";
        }
        // $pq .= "GROUP BY pc.name, ii.product_variance_id, ii.id";
        // if($customer->id == 3) print $pq;
        $items = select($pq);
        $ordered_qty = [];
        if ($collection == "true") {
          $tqs = select($tq);
          while ($q = mysqli_fetch_object($tqs)) {
            $ordered_qty[$q->vid] = $q->quantity - $q->delivered;
          }
        }

        $printedCustomerBlock = ($items && $items->num_rows > 0);
        if ($printedCustomerBlock) {
          print $con; 
          $customerSerial++;
        }

        // Get current assigned delivery staff for this customer
        $currentAssignedStaff = '';
        $hasMultipleAssignedStaff = false;
        if ($printedCustomerBlock) {
          // Mirror the same filter used for visible items to avoid counting historical/filtered-out rows
          $asFilter = [];
          $asFilter[] = "i.customer_id={$customer->id}";
          $asFilter[] = "ii.delivered < ii.quantity";
          // Date window depends on delivery/collection toggle (same behavior as items list)
          $asFilter[] = "IFNULL(ii.delivery_date,i.invoice_date) <= curdate()";
          if (!($delivery || $collection)) {
            $asFilter[] = "IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-25'";
          }
          if (nn($cities)) {
            $asFilter[] = "city.id IN ($cities)";
          }
          if (nn($products)) {
            $asFilter[] = "p.product_category_id IN ($products)";
          }
          // Collected vs not collected toggle
          $asFilter[] = ($delivery || $collection) ? $collectedExpr : ("NOT " . $collectedExpr);
          // Only consider rows that actually have an assignment
          $asFilter[] = "(ii.assigned_at IS NOT NULL AND ii.assigned_to IS NOT NULL AND ii.assigned_to != '')";
          // If a deliveryStaff filter is active, match it (to reflect the current working set)
          if (nn($deliveryStaff)) {
            $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
            $asFilter[] = "ii.assigned_to = '" . $deliveryStaffSql . "'";
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
        }

        $ci = 1;
        $ic = 0;
        // vd($items);
        while ($i = mysqli_fetch_object($items)) {
          if ($i->pc_name != $cat) {
            print "<tr class='pc-header-row'><th colspan='2' class='pc-header-cell' style='cursor:pointer'>$i->pc_name</th>";
            if ($delivery) {
              print "<th>Avail</th><th>Shortage</th><th>Ordered</th></tr>";
            } elseif ($collection) {
              print "<th>Invoice</th><th>Avail</th><th>Shortage</th><th><a href='javascript:fillAllCollectionQty()'>Ordered</a></th><th>Collected</th><th>Collection</th><th><i onClick='toggleInfo()' class='fas fa-eye'></i></th>";
              // print "<th>Return</th><th>Return</th><th>Damaged</th><th>Balance</th>";
              print "</tr>";
            } else {
              print "<th class='price-col'>Price</th><th class='order-qty-col'>Ord Qty</th><!--th>Select</th--></tr>";
            }
            $cat = $i->pc_name;
          }
          $i->quantity = $i->quantity - $i->delivered;
          if ($i->quantity > 0) {
            $collected = 0;

            if ($collection) {
              $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
            }
            $partAttr = htmlspecialchars($i->particulars, ENT_QUOTES);
            print "<tr data-vid='$i->vid' data-qty='$i->quantity' data-collected='$collected' data-unit-price='$i->price' data-particulars='$partAttr' class='" . ($i->quantity - $collected == 0 ? 'all-collected' : '') . "'><td><div><input type='checkbox' class='iid-date' name='iid[$i->iid]' value='$i->iid'> <a href='#' id='invoice-item-date-$i->iid' data-dd='" . df($i->dd) . "' onClick='setDate(this, $i->iid)'>$ci</a></div></td>
                            <td>$i->particulars ( <a data-id='$i->iid' class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>$i->price</a>)</td>";

            if ($delivery) {
              $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
              print "<td class='text-center avail" . ($i->stock < $i->min_stock ? ' color-red' : '') . "' title='$i->stock + $i->quantity:'>" . ($i->stock) . "</td>";
              print "<td class='text-center shortage' title='$i->particulars'>" . abs($i->stock > 0 ? 0 : $i->stock) . "</td>";
              print "<td class='text-center ordered' title='Deliverd: $i->delivered'>$i->quantity</td>";
            } elseif ($collection) {
              print "<td>INV" . zerofill($i->id, 5) . "</td>";
              // print "<td class='text-center".($i->stock < $i->min_stock ? ' color-red' : '')."'>".($i->stock > 0 ? $i->stock + $i->quantity: 0)."</td>";
              print "<td class='text-center" . ($i->stock < $i->min_stock ? ' color-red' : '') . "'>" . ($i->stock) . "</td>";
              print "<td class='text-center' title='$i->particulars'>" . abs($i->stock > 0 ? 0 : $i->stock) . "</td>";
              print "<td class='text-center ordered-qty custom-tooltip' data-balance='" . ($i->quantity - $collected) . "' title='$i->delivered'>" . ($i->quantity - $collected > 0 ? "<a href='javascript:fillCollectionQty(\"iid-$i->iid\", " . ($i->quantity - $collected) . ")'>$i->quantity</a>" : "$i->quantity") . "</td>";
              print "<td class='text-center'>$collected</td>";
              print "<td class='text-center custom-tooltip' data-bs-toggle='tooltip'  title='$i->particulars, TOTAL COLLECTION {$ordered_qty[$i->vid]}'>
                                <input type='number' id='iid-$i->iid' data-bs-toggle='tooltip' class='form-control' style='width:60px; padding: 0px 3px' name='variance[$i->iid]' max='" . ($i->quantity - $collected) . "' min='0' ></td>"; //
              // print "<td class='text-center custom-tooltip' data-bs-toggle='tooltip' title='$customer->company ($customer->city) - $customer->code'><i class='fas fa-eye'></i></td>";
              print "<td class='store-detail'>$customer->company ($customer->city) - $customer->code</td>";
              // print "<td class='text-center'><i class='fas fa-undo'></i></th>";
              // print "<td class='text-center' title='$i->particulars'><input type='number' class='form-control' style='width:60px; padding: 0px 3px' name='variance[$i->vid]' min='0' max='".($i->quantity - $collected)."'></td>";
              // print "<td class='text-center' title='$i->particulars'><input type='number' class='form-control' style='width:60px; padding: 0px 3px' name='variance[$i->vid]' min='0' max='".($i->quantity - $collected)."'></td>";
              // print "<td class='text-center'>0</th>";
            } else {
              $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
              $collected = $i->quantity;
              print "<td class='price-col'>";
              if ($i->old_price && false) {
                print nf($i->price * $i->quantity);
              } else {
                print "<a data-id='$i->iid' id='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price * $i->quantity) . "</a>";
              }
              print "</td><td class='text-center order-qty-col'><a data-id='$i->iid' class='invoice-item-$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$collected</a></td>";
            }
            print "</tr>";
            $ic++;
          }
          $ci++;
        }
        // if ($ic == 0) print '<script>$(".customer-' . $customer->id . '").hide();</script>';
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
        if ($printedCustomerBlock) {
          print "</tbody>
            </table>";
          
          // Add delivery staff dropdown for reassignment
          print "<div style='padding: 5px 8px; border-top: 1px solid #ddd; background-color: #f9f9f9;'>";
          print "<label style='font-weight: bold; margin-right: 8px; font-size: 0.9rem;'>Assigned to:</label>";
          print "<select class='form-control delivery-staff-reassign' data-customer-id='$customer->id' style='width: 180px; display: inline-block; padding: 3px 6px; font-size: 0.85rem;'>";
          // When multiple different staff are assigned, show a disabled placeholder as selected
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
          print "</div>";
        }
        if ($delivery || $collection) break;
      }
      ?>
    </div>

    <div id='consolidated-container' class='customer-item' style='display:none;'>
      <table class='table table-bordered toggle-store-info hide-info'>
        <thead>
          <tr>
            <th style='width:50px; text-align:center;'>#</th>
            <th>Name</th>
            <th class='price-col'>Price</th>
            <th class='order-qty-col'>Order Qty</th>
          </tr>
        </thead>
        <tbody id='consolidated-body'></tbody>
      </table>
    </div>

    <div style='position: fixed; right: 20px; bottom: 20px; padding-left: 30px; padding-right: 30px'>
      <table align="center">
        <tr>
          <td>
            <?php
            // if ($order == 'true' || $collection == 'true' || $pending == 'true') {
            //   print "<select class='supplier-select form-control' name='delivery_staff' style='width:150px' required>
            //               <option value=''>Please select</option>";

            //   $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
            //   while ($man = mysqli_fetch_object($objs)) {
            //     print "<option ";
            //     print ">$man->name</option>";
            //   }
            //   print "</select>";
            // }
            ?>
          </td>
          <?php if ($order || $pending) print "<td><button class='btn btn-warning' type='submit' name='collect'>Collect</button></td>"; ?>
        </tr>
      </table>
    </div>

    <div id='cust-loc-modal' class='cust-loc-modal'>
      <div class='title'>Customer Location</div>
      <div id='cust-loc-body' class='body'></div>
    </div>

    <script>
      window.__dcollectCollapsedOnce = window.__dcollectCollapsedOnce || false;

      function setItemId(id) {
        $('#invoice_item_id').val(id);
      }

      function setItemIdPrice(id, price) {
        $('#new-price').val(price);
        setItemId(id);
      }

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
        var customerContainer = document.querySelector('.customer-container');
        var tbody = document.getElementById('consolidated-body');
        if (!container || !tbody) return;

        if (!selected.length) {
          container.style.display = 'none';
          if (customerContainer) customerContainer.classList.remove('has-consolidated');
          tbody.innerHTML = '';
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
            var iidCb = row.querySelector('input.iid-date');
            var iid = iidCb && iidCb instanceof HTMLInputElement ? iidCb.value : '';
            if (!vid || qty <= 0) return;

            var cur = map.get(vid);
            if (!cur) {
              map.set(vid, {
                particulars: particulars,
                qty: qty,
                unitPrice: unitPrice,
                iids: iid ? new Set([String(iid)]) : new Set()
              });
            } else {
              cur.qty += qty;
              if (iid) cur.iids.add(String(iid));
            }
          });
        });

        tbody.innerHTML = '';

        if (!map.size) {
          container.style.display = 'none';
          if (customerContainer) customerContainer.classList.remove('has-consolidated');
          return;
        }

        container.style.display = '';
        if (customerContainer) customerContainer.classList.add('has-consolidated');

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

            var iids = item.iids ? Array.from(item.iids) : [];
            cb.dataset.iids = iids.join(',');

            if (iids.length) {
              var states = iids.map(function(id) {
                var el = document.querySelector('input.iid-date[value="' + String(id).replace(/"/g, '\\"') + '"]');
                return el && el instanceof HTMLInputElement ? el.checked : false;
              });
              var allChecked = states.length > 0 && states.every(function(v) {
                return v;
              });
              var anyChecked = states.some(function(v) {
                return v;
              });
              cb.checked = allChecked;
              cb.indeterminate = anyChecked && !allChecked;
            }
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
        // Build consolidated totals footer (Qty only)
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

        if (cb.classList.contains('consolidated-toggle')) {
          var ids = (cb.dataset.iids || '').split(',').map(function(s) {
            return s.trim();
          }).filter(Boolean);

          ids.forEach(function(id) {
            var el = document.querySelector('input.iid-date[value="' + String(id).replace(/"/g, '\\"') + '"]');
            if (el && el instanceof HTMLInputElement) {
              el.checked = cb.checked;
            }
          });

          rebuildConsolidatedTable();
          return;
        }

        if (cb.classList.contains('iid-date')) {
          rebuildConsolidatedTable();
        }

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

      document.addEventListener('click', function(e) {
        var headerCell = e.target.closest('.pc-header-cell');
        if (!headerCell) return;

        var headerRow = headerCell.closest('tr');
        var tbody = headerCell.closest('tbody');
        if (!headerRow || !tbody) return;

        e.preventDefault();

        var rows = Array.from(tbody.querySelectorAll('tr'));
        var headerIndex = rows.indexOf(headerRow);
        if (headerIndex < 0) return;

        var itemRows = [];
        for (var i = headerIndex + 1; i < rows.length; i++) {
          if (rows[i].classList && rows[i].classList.contains('pc-header-row')) break;
          itemRows.push(rows[i]);
        }

        var checkboxes = [];
        itemRows.forEach(function(r) {
          r.querySelectorAll('input.iid-date').forEach(function(cb) {
            if (cb instanceof HTMLInputElement) checkboxes.push(cb);
          });
        });

        if (!checkboxes.length) return;

        var allChecked = checkboxes.every(function(cb) {
          return cb.checked;
        });
        var nextState = !allChecked;

        checkboxes.forEach(function(cb) {
          cb.checked = nextState;
          cb.dispatchEvent(new Event('change', {
            bubbles: true
          }));
        });
      });

      (function(){
        var modal = document.getElementById('cust-loc-modal');
        var body = document.getElementById('cust-loc-body');
        var activeCust = null;

        function hide(){
          if (!modal) return;
          modal.classList.remove('show');
          activeCust = null;
        }

        function show(text, custId){
          if (!modal || !body) return;
          body.textContent = text || '';
          modal.classList.add('show');
          activeCust = custId;
        }

        document.addEventListener('click', function(e){
          var toggle = e.target.closest('.cust-loc-toggle');
          if (toggle) {
            e.preventDefault();
            var custId = toggle.getAttribute('data-cust');
            if (activeCust && custId && activeCust === custId && modal && modal.classList.contains('show')) {
              hide();
              return;
            }
            var loc = toggle.getAttribute('data-loc') || '';
            show(loc, custId);
            return;
          }

          if (!modal || !modal.classList.contains('show')) return;
          if (e.target.closest('#cust-loc-modal')) return;
          hide();
        });
      })();

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

      // Handle update price button click
      $("#update_price_button").click(function() {
        const price = $('#new-price').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/store/ajax/update_invoice_item_price.php', {
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
    </script>
</form>

<!-- Hidden form for delivery staff reassignment -->
<form id="reassign-form" method="post" style="display: none;">
  <input type="hidden" name="assign" value="yes">
  <input type="hidden" name="delivery_staff" id="reassign-delivery-staff">
  <input type="hidden" name="selected_customer[]" id="reassign-customer-id">
</form>

<!-- Modal for modifying price -->
<div class="modal fade" id="modal-modify-price" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="invoice_item_id" id="invoice_item_id" value="">
        <div class="modal-header">
          <h4 class="modal-title">Update Price</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <table>
            <tr>
              <td>Update Price</td>
              <td nowrap><input type="number" id="new-price" name="price" step="1" class="form-control"></td>
            </tr>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" id="update_price_button" name="update_price_button">Save</button>
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php

// if (isset($_POST['deliver'])) {
//     $post = $_POST;
//     // dd($post);
//     foreach ($post['iid'] as $key => $qty) {
//         if($qty > 0){
//             $ii = R::load("invoice_item", $key);
//             $inv = R::load("invoice", $ii->invoice_id);
//             update("invoice_item", "delivered=quantity, delivered_by=".uid().", delivered_at=NOW(),delivery_staff='$post[delivery_staff]'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
//             insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$key, $qty, ".uid().",'$post[delivery_staff]'");
//         }
//     }
// }

if (isset($post->deliver) || isset($_POST['deliver'])) {
  $post_data = isset($_POST['deliver']) ? $_POST : (array)$post;
  $deliveryStaff = isset($post_data['delivery_staff']) ? trim($post_data['delivery_staff']) : '';
  if (!nn($deliveryStaff)) {
    redir("?");
    exit;
  }
  $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
  $selectedItems = (isset($post_data['iid']) && is_array($post_data['iid'])) ? array_keys($post_data['iid']) : [];

  foreach ($selectedItems as $iid) {
    $iid = (int) $iid;
    if ($iid <= 0) continue;

    $ii = R::load("invoice_item", $iid);
    if (!$ii->id) continue;

    $remainingQty = (float) $ii->quantity - (float) $ii->delivered;
    if ($remainingQty <= 0) continue;

    update("invoice_item", "delivered=quantity, delivered_by=" . uid() . ", delivered_at=NOW(), delivery_staff='$deliveryStaffSql', collected_at=IFNULL(collected_at, NOW()), collected_by=IFNULL(collected_by, " . uid() . ")", "id=$iid");
    insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$iid, $remainingQty, " . uid() . ",'$deliveryStaffSql'");
  }

  // If it's an AJAX request, return success message
  if (isset($_POST['deliver']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode(['success' => true, 'message' => 'Delivery marked successfully']);
    exit;
  }

  redir(ROOT . "/dcollect_delivery_status");
  exit;
}

?>