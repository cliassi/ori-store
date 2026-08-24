<?php
session_start();
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
require_once("../core/functions.php");

$restrictedUser = !canEditPriceAndQuantity();
$canEditDate = canEditDateOnly();

if (!function_exists('ensureMysqlColumn')) {
  function ensureMysqlColumn($table, $column, $definition)
  {
    global $c;

    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if (!$table || !$column)
      return false;

    $check = $c->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows > 0)
      return true;

    return (bool) $c->query("ALTER TABLE `$table` ADD `$column` $definition");
  }
}

if (!function_exists('elapsedIntervalLabel')) {
  function elapsedIntervalLabel($startTs, $endTs = null)
  {
    if (!$startTs)
      return "N/A";
    $endTs = $endTs ?: time();

    $seconds = max(0, $endTs - $startTs);
    $days = (int) floor($seconds / 86400);
    $hours = (int) floor(($seconds % 86400) / 3600);
    $minutes = (int) floor(($seconds % 3600) / 60);

    $parts = [];
    if ($days > 0)
      $parts[] = $days . "d";
    if ($hours > 0 || $days > 0)
      $parts[] = $hours . "h";
    $parts[] = $minutes . "m";

    return implode(" ", $parts);
  }
}

ensureMysqlColumn("invoice_item", "collected_at", "DATETIME NULL DEFAULT NULL");
ensureMysqlColumn("invoice_item", "collected_by", "INT NULL DEFAULT NULL");

extract($_POST);
if ($order == 'false' && $pending == 'false' && $delivery == 'false' && $collection == 'false')
  exit;

// Get delivery staff filter
$deliveryStaff = isset($_POST['deliveryStaff']) ? trim($_POST['deliveryStaff']) : '';

$filter = " WHERE ";
$cities = preg_replace('/[^0-9,]/', '', $customers);
$products = preg_replace('/[^0-9,]/', '', $products);

if (nn($cities)) {
  $filter .= " city.id IN ($cities)";
}

if (nn($products)) {
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " p.product_category_id IN ($products)";
}

$filter .= ($filter != " WHERE " ? " AND " : " ") . ($pending == 'true' ? " IFNULL(ii.delivery_date,i.invoice_date) < curdate()" : " IFNULL(ii.delivery_date,i.invoice_date) = curdate()");
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity >= ii.delivered AND ii.collected_by > 0";

// Add delivery staff filter if specified
if (nn($deliveryStaff)) {
  $deliveryStaffSql = mysqli_real_escape_string($c, $deliveryStaff);
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.assigned_to = '$deliveryStaffSql'";
}

if (uid() == 1) {
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " (c.branch_id = $branch_id OR c.branch_id IS NULL)";
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

// Optimize: Fetch all status metadata and items in bulk queries
$customerIds = [];
$customersResult = select($query);
while ($customer = mysqli_fetch_object($customersResult)) {
  $customerIds[] = $customer->id;
}
$customerIdsStr = implode(',', $customerIds);

// Bulk fetch status metadata for all customers
$statusMetaData = [];
if (!empty($customerIds)) {
  $bulkStatusFilter = "i.customer_id IN ($customerIdsStr)";
  if ($pending) {
    $bulkStatusFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) < CURDATE()";
  } else {
    $bulkStatusFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) = CURDATE()";
  }
  if ($delivery || $collection) {
    $bulkStatusFilter = "i.customer_id IN ($customerIdsStr) AND IFNULL(ii.delivery_date,i.invoice_date) = CURDATE()";
  }
  if (nn($cities)) {
    $bulkStatusFilter .= " AND city.id IN ($cities)";
  }
  if (nn($products)) {
    $bulkStatusFilter .= " AND p.product_category_id IN ($products)";
  }

  $bulkStatusQuery = "SELECT
                        i.customer_id,
                        MIN(COALESCE(ii.collected_at, sci.collected_at)) collected_at,
                        MAX(ii.delivered_at) delivered_at, 
                        MAX(sci.delivery_staff) delivery_staff,
                        SUM(CASE WHEN ii.delivered < ii.quantity THEN 1 ELSE 0 END) pending_items
                      FROM invoice i
                      INNER JOIN invoice_item ii ON i.id=ii.invoice_id
                      INNER JOIN customer c ON c.id=i.customer_id
                      LEFT JOIN city ON c.city=city.name
                      INNER JOIN product_variance pv ON pv.id=ii.product_variance_id
                      INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
                      LEFT JOIN (
                        SELECT invoice_item_id, MIN(sc.created_at) collected_at, delivery_staff
                        FROM stock_collect sc
                        INNER JOIN stock_collect_item sci ON sc.id=sci.stock_collect_id
                        WHERE invoice_item_id IS NOT NULL
                        GROUP BY invoice_item_id, delivery_staff
                      ) sci ON sci.invoice_item_id=ii.id
                      WHERE $bulkStatusFilter
                      GROUP BY i.customer_id";
  $statusResults = select($bulkStatusQuery);
  while ($statusRow = mysqli_fetch_object($statusResults)) {
    $statusMetaData[$statusRow->customer_id] = $statusRow;
  }
}

// Bulk fetch items for all customers
$allItemsData = [];
if (!empty($customerIds)) {
  $bulkItemFilter = "i.customer_id IN ($customerIdsStr)";
  if ($pending) {
    $bulkItemFilter .= " AND ii.delivered = 0 AND IFNULL(ii.delivery_date,i.invoice_date) < curdate()";
  } else {
    $bulkItemFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
  }
  if ($delivery || $collection) {
    $bulkItemFilter = "i.customer_id IN ($customerIdsStr) AND IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
  }
  if (nn($cities)) {
    $bulkItemFilter .= " AND city.id IN ($cities)";
  }
  if (nn($products)) {
    $bulkItemFilter .= " AND p.product_category_id IN ($products)";
  }

  $stField = 'stock';
  $bulkItemsQuery = "SELECT $stField(pv.id) stock, i.customer_id, i.id, p.name, pc.name pc_name, ii.id iid, p.id pid, p.name, pv.id vid, pv.particulars, pv.min_stock, SUM(ii.quantity) quantity, 
                      ii.delivered delivered, ii.price, ii.old_price, IFNULL(ii.delivery_date,i.invoice_date) dd FROM invoice i 
                      INNER JOIN invoice_item ii ON i.id=ii.invoice_id
                      INNER JOIN customer c ON c.id=i.customer_id
                      LEFT JOIN city ON c.city=city.name
                      INNER JOIN `product_variance` pv ON pv.id=ii.product_variance_id 
                      INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
                      INNER JOIN product_category pc ON p.product_category_id=pc.id
                      WHERE $bulkItemFilter ";
  if ($delivery == "true") {
    $bulkItemsQuery .= "GROUP BY pc.name, ii.product_variance_id, i.customer_id";
  } else {
    $bulkItemsQuery .= "GROUP BY pc.name, ii.product_variance_id, ii.id, i.customer_id";
  }
  $bulkItemsQuery .= " ORDER BY i.customer_id, pc.name, pv.particulars";
  
  $allItemsResults = select($bulkItemsQuery);
  while ($itemRow = mysqli_fetch_object($allItemsResults)) {
    if (!isset($allItemsData[$itemRow->customer_id])) {
      $allItemsData[$itemRow->customer_id] = [];
    }
    $allItemsData[$itemRow->customer_id][] = $itemRow;
  }
}

// Reset customers result for the main loop
$customers = select($query);

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
    font-size: .7rem;
    counter-reset: customer-counter;
  }

  .customer-item {
    padding: 5px;
    /* margin: 5px; */
    width: 31.33%;
    white-space: nowrap;
    border: 1px solid #ccc;
    overflow-x: auto;
    counter-increment: customer-counter;
  }

  .serial-col::before {
    content: counter(customer-counter);
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
    width: 80px;
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

  .customer-item .price-col,
  .customer-item .order-qty-col {
    width: 62px !important;
    min-width: 62px;
    white-space: nowrap;
    text-align: center;
  }

  .pc-header-cell {
    white-space: nowrap;
  }

  .collect-meta {
    margin-top: 4px;
    font-size: .72rem;
    line-height: 1.25;
    color: #666;
    white-space: normal;
    text-align: center;
  }

  .cust-loc-icon {
    margin-left: 6px;
    cursor: pointer;
    font-size: 18px;
    vertical-align: middle;
    user-select: none;
    color: #444;
  }

  .cust-loc-modal {
    position: fixed;
    z-index: 99999;
    left: 50%;
    top: 30%;
    transform: translate(-50%, -30%);
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 12px;
    width: min(360px, calc(100vw - 40px));
    box-shadow: 0 10px 25px rgba(0, 0, 0, .18);
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
<form method='post'>
  <div style='display:flex; align-items:flex-start; gap:16px; overflow:hidden;'>
  <div class="customer-container text-center" style='flex:1; min-width:0;'>
    <?php
    $pending = $pending == "true";
    $delivery = $delivery == "true";
    $collection = $collection == "true";
    $pendingList = false; //$pendingList == "true";
    if ($pendingList)
      $delivery = $pendingList;
    while ($customer = mysqli_fetch_object($customers)) {
      // Use pre-fetched status metadata
      $statusMeta = isset($statusMetaData[$customer->id]) ? $statusMetaData[$customer->id] : null;

      $collectAtRaw = ($statusMeta && nn($statusMeta->collected_at)) ? $statusMeta->collected_at : '';
      $deliveredAtRaw = ($statusMeta && nn($statusMeta->delivered_at)) ? $statusMeta->delivered_at : '';
      $pendingItems = $statusMeta ? (int) $statusMeta->pending_items : 0;
      $isDelivered = ($pendingItems === 0 && nn($deliveredAtRaw));

      $collectionMeta = "<div class='collect-meta'>";
      // $collectionMeta .= "Status: " . ($isDelivered ? "<strong style='color:#0b7d2a'>Delivered</strong>" : "<strong style='color:#c0392b'>Not Delivered</strong>") . "<br>";
      $deliveryStaff = ($statusMeta && isset($statusMeta->delivery_staff)) ? $statusMeta->delivery_staff : '';
      $collectionMeta .= "Collect Time: " . (nn($collectAtRaw) ? date('h:i a', strtotime($collectAtRaw)) : "N/A") . " <b>$deliveryStaff</b><br>";

      if (nn($collectAtRaw)) {
        // Set the timezone to Malaysia Time (Asia/Kuala_Lumpur)
        date_default_timezone_set('Asia/Kuala_Lumpur');

        // Convert the collect time to timestamp and calculate the time difference
        $startTs = strtotime($collectAtRaw);
        $endTs = time();
        $diffSeconds = max(0, $endTs - $startTs);

        // Convert the seconds difference into hours and minutes
        $diffMinutes = floor($diffSeconds / 60);
        $diffHours = floor($diffMinutes / 60);
        $diffMinutes = $diffMinutes % 60;

        // Add the time difference to the meta
        $collectionMeta .= "Pending Time: " . sprintf("%02d:%02d", $diffHours, $diffMinutes) . " as of " . date('h:i a');
      } else {
        $collectionMeta .= "Pending Time: N/A";
      }

      if ($isDelivered && nn($deliveredAtRaw)) {
        $collectionMeta .= "<br>Delivered At: " . date('d-m-y H:i', strtotime($deliveredAtRaw));
      }

      $collectionMeta .= "</div>";

      $locParts = [];
      $locParts[] = $customer->company;
      if (isset($customer->code) && nn($customer->code))
        $locParts[] = "Code: $customer->code";
      if (isset($customer->city) && nn($customer->city))
        $locParts[] = "Area: $customer->city";
      if (isset($customer->location) && nn($customer->location))
        $locParts[] = "Location: $customer->location";
      if (isset($customer->phone) && nn($customer->phone))
        $locParts[] = "Phone: $customer->phone";
      if (isset($customer->mobile) && nn($customer->mobile))
        $locParts[] = "Mobile: $customer->mobile";
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
        $con .= "<th class='serial-col text-center' style='width:20px; min-width:20px; max-width:20px;'></th> 
        <th colspan='3' style='white-space:break-spaces'><span id='cust-chevron-$customer->id' class='material-icons cust-toggle' data-cust='$customer->id' style='margin-right:6px; cursor:pointer; font-size:18px; vertical-align:middle;'>expand_more</span><a class='customer-link' href='" . ROOT . "/customer/details/$customer->id' style='cursor:pointer'>$customer->company ($customer->city) - <strong style='font-size:large'>$customer->code</strong></a><span class='material-icons cust-loc-icon cust-loc-toggle' data-cust='$customer->id' data-loc='$custLocText'>chevron_right</span>$collectionMeta";
        if ($order == 'true') {
          $con .= " <input type='checkbox' class='selected-customer' data-cust='$customer->id' name='selected_customer[]' value='$customer->id' style='float:right; margin-top:2px;'>";
        }
        $con .= "</th>";
      }
      $con .= "</tr>
                </thead>
                <tbody id='cust-body-$customer->id'>";
      
      // Store customer ID for later use in button
      $currentCustomerId = $customer->id;

      // Use pre-fetched items data
      $customerItems = isset($allItemsData[$customer->id]) ? $allItemsData[$customer->id] : [];
      $ordered_qty = [];

      $customerHtml = '';
      $printedCustomerBlock = !empty($customerItems);
      if ($printedCustomerBlock) {
        ob_start();
        print $con;
      }

      $ci = 1;
      $ic = 0;
      $cat = "";
      foreach ($customerItems as $i) {
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
          print "<tr data-vid='$i->vid' data-qty='$i->quantity' data-collected='$collected' data-unit-price='$i->price' data-particulars='$partAttr' class='" . ($i->quantity - $collected == 0 ? 'all-collected' : '') . "'><td><div><input type='checkbox' class='iid-date' name='iid[$i->iid]' value='$i->iid'> " . ($canEditDate ? "<a href='#' id='invoice-item-date-$i->iid' data-dd='" . df($i->dd) . "' onClick='setDate(this, $i->iid)'>$ci</a>" : "<span>$ci</span>") . "</div></td>
                            <td class='text-left'>$i->particulars (" . ($restrictedUser ? "<span>" . nf($i->price) . "</span>" : "<a data-id='$i->iid' class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price) . "</a>") . ")</td>";

          if ($delivery) {
            $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
            print "<td class='text-center avail" . ($i->stock < $i->min_stock ? ' color-red' : '') . "' title='$i->stock + $i->quantity:'>" . ($i->stock) . "</td>";
            print "<td class='text-center shortage' title='$i->particulars'>" . abs($i->stock > 0 ? 0 : $i->stock) . "</td>";
            print "<td class='text-center ordered' title='Deliverd: $i->delivered'>$i->quantity</td>";
          } elseif ($collection) {
            print "<td>INV" . zerofill($i->id, 5) . "</td>";
            // print "<td class='text-center".($i->stock < $i->min_stock ? ' color-red' : '')."'>".($i->stock > 0 ? $i->stock + $i->quantity: 0)."</td>";
            print "<td class='text-center" . ($i->stock < $i->min_stock ? ' color-red' : '') . "'>" . ($i->stock) . "</td>";
            print "<td class='' title='$i->particulars'>" . abs($i->stock > 0 ? 0 : $i->stock) . "</td>";
            print "<td class='text-center .ordered-qty custom-tooltip' data-balance='" . ($i->quantity - $collected) . "' title='$i->delivered'>" . ($i->quantity - $collected > 0 ? "<a href='javascript:fillCollectionQty(\"iid-$i->iid\", " . ($i->quantity - $collected) . ")'>$i->quantity</a>" : "$i->quantity") . "</td>";
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
            if ($restrictedUser) {
              print nf($i->price * $i->quantity);
            } elseif ($i->old_price && false) {
              print nf($i->price * $i->quantity);
            } else {
              print "<a data-id='$i->iid' id='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price * $i->quantity) . "</a>";
            }
            print "</td><td class='text-center order-qty-col'>" . ($restrictedUser ? "<span>$i->quantity</span>" : "<a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$i->quantity</a>") . "</td>";
          }
          print "</tr>";
          $ic++;
        }
        $ci++;
      }
      if ($ic == 0) {
        $emptyColspan = $collection ? 7 : ($delivery ? 5 : 4);
        print "<tr><td colspan='$emptyColspan' class='text-center' style='color:#0b7d2a'>All items delivered</td></tr>";
      }
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
      print "</tbody>
            </table>";
      
      // Collect all iid and invoice IDs from this block for Return to Order
      $invoiceItemIds = [];
      $invoiceIds = [];
      foreach ($customerItems as $i) {
        $invoiceItemIds[] = (int)$i->iid;
        $invoiceIds[] = (int)$i->id;
      }
      $invoiceIds = array_unique($invoiceIds);

      // Add Return to Order button directly under table
      if ($ic > 0) {
        $custId = (int)$customer->id;
        $iidStr = implode(',', array_unique($invoiceItemIds));
        $invStr = implode(',', $invoiceIds);
        print "<div style='padding: 10px; text-align: center; border: 1px solid #ddd; border-top: none;'>
          <button type='button' class='btn btn-warning btn-sm return-to-order-btn'
                  data-cust-id='$custId' data-inv-ids='$invStr'>
            <i class='fas fa-undo'></i> Return to Order
          </button>
        </div>";
      }
      
      print "</div>";

      if (!empty($customerItems)) {
        $customerHtml = ob_get_clean();
        if ($ic > 0) {
          print $customerHtml;
        }
      }
      if ($delivery || $collection)
        break;
    }
    ?>
    <?php
    // Build read-only summary: aggregate all visible items across all customers
    $summaryMap = [];
    foreach ($allItemsData as $custId => $items) {
      foreach ($items as $i) {
        $qty = $i->quantity - $i->delivered;
        if ($qty <= 0) continue;
        $vid = (int)$i->vid;
        if (!isset($summaryMap[$vid])) {
          $summaryMap[$vid] = ['name' => $i->particulars, 'price' => (float)$i->price, 'qty' => 0];
        }
        $summaryMap[$vid]['qty'] += $qty;
      }
    }
    uasort($summaryMap, function($a, $b) { return strcmp($a['name'], $b['name']); });
    $summaryTotalQty = array_sum(array_column($summaryMap, 'qty'));
    $summaryTotalPrice = array_sum(array_map(function($r) { return $r['price'] * $r['qty']; }, $summaryMap));
    ?>
  </div><!-- end customer-container -->
    <?php if (!empty($summaryMap)): ?>
    <!-- Read-only summary panel: hidden until a customer is selected -->
    <div id='delivery-summary-panel' style='display:none; width:280px; flex-shrink:0; position:sticky; top:80px; align-self:normal;'>
      <table class='table table-bordered table-sm mb-0' style='font-size: .7rem; table-layout:fixed; width:100%;'>
        <thead class='table-light'>
          <tr>
            <th style='width:28px; text-align:center;'>#</th>
            <th>NAME</th>
            <th style='width:60px; text-align:right;'>PRICE</th>
            <th style='width:52px; text-align:center;'>QTY</th>
          </tr>
        </thead>
        <tbody id='delivery-summary-body'>
          <?php $sn = 1; foreach ($summaryMap as $vid => $row): ?>
          <tr data-vid='<?= $vid ?>'
              data-name='<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>'
              data-price='<?= $row['price'] ?>'
              data-qty='<?= $row['qty'] ?>'>
            <td class='text-center'><?= $sn++ ?></td>
            <td style='word-break:break-word; white-space:normal;'><?= htmlspecialchars($row['name']) ?></td>
            <td class='text-right'><?= number_format($row['price'] * $row['qty'], 2) ?></td>
            <td class='text-center'><?= $row['qty'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan='2' class='text-right'>Total</th>
            <th class='text-right' id='delivery-summary-total-price'><?= number_format($summaryTotalPrice, 2) ?></th>
            <th class='text-center' id='delivery-summary-total'><?= $summaryTotalQty ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div><!-- end flex row -->

    <div style='position: fixed; right: 20px; bottom: 20px; padding-left: 30px; padding-right: 30px'>
      <table align="center">
        <tr>
          <td>
            <?php
            if ($order == 'true' || $collection == 'true' || $pending == 'true') {
              print "<select class='supplier-select form-control' name='delivery_staff' style='width:150px' required>
                          <option value=''>Please select</option>";

              $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
              while ($man = mysqli_fetch_object($objs)) {
                print "<option ";
                print ">$man->name</option>";
              }
              print "</select>";
            }
            ?>
          </td>
          <?php
          print "<td><button class='btn btn-success' type='submit' name='deliver'>Deliver</button></td>";
          ?>
        </tr>
      </table>
    </div>

    <script>
      document.addEventListener('change', function (e) {
        var cb = e.target;
        if (!(cb instanceof HTMLInputElement)) return;

        if (cb.classList.contains('selected-customer')) {
          var checkedBoxes = Array.from(document.querySelectorAll('input.selected-customer:checked'));
          var anySelected = checkedBoxes.length > 0;

          if (cb.checked) {
            var custId = cb.getAttribute('data-cust');
            if (custId) {
              var custItem = document.querySelector('.customer-item.customer-' + custId);
              if (custItem) {
                custItem.querySelectorAll('input.iid-date').forEach(function (iidCb) {
                  if (!(iidCb instanceof HTMLInputElement)) return;
                  iidCb.checked = true;
                  iidCb.dispatchEvent(new Event('change', { bubbles: true }));
                });
              }
            }
          }

          if (!anySelected) {
            document.querySelectorAll('.customer-item').forEach(function (item) {
              item.style.display = '';
            });
          } else {
            var selectedCustIds = checkedBoxes.map(function (x) {
              return x.getAttribute('data-cust');
            });

            document.querySelectorAll('.customer-item').forEach(function (item) {
              var custMatch = false;
              selectedCustIds.forEach(function (id) {
                if (!id) return;
                if (item.classList && item.classList.contains('customer-' + id)) custMatch = true;
              });
              item.style.display = custMatch ? '' : 'none';
            });
          }

          // Update summary panel visibility and content
          updateDeliverySummary(checkedBoxes);
        }
      });

      function updateDeliverySummary(checkedBoxes) {
        var panel = document.getElementById('delivery-summary-panel');
        if (!panel) return;

        if (!checkedBoxes || checkedBoxes.length === 0) {
          panel.style.display = 'none';
          return;
        }

        // Aggregate qty per vid from selected customers' rows
        var map = {};
        checkedBoxes.forEach(function(cb) {
          var custId = cb.getAttribute('data-cust');
          if (!custId) return;
          var custItem = document.querySelector('.customer-item.customer-' + custId);
          if (!custItem) return;
          custItem.querySelectorAll('tr[data-vid]').forEach(function(row) {
            var vid = row.getAttribute('data-vid');
            var qty = parseFloat(row.getAttribute('data-qty') || '0') || 0;
            var price = parseFloat(row.getAttribute('data-unit-price') || '0') || 0;
            var name = row.getAttribute('data-particulars') || '';
            if (!vid || qty <= 0) return;
            if (!map[vid]) {
              map[vid] = { name: name, price: price, qty: 0 };
            }
            map[vid].qty += qty;
          });
        });

        var tbody = document.getElementById('delivery-summary-body');
        var totalEl = document.getElementById('delivery-summary-total');
        if (!tbody) return;

        tbody.innerHTML = '';
        var totalQty = 0;
        var serial = 1;
        var entries = Object.values(map).sort(function(a, b) {
          return (a.name || '').localeCompare(b.name || '');
        });

        var totalPrice = 0;
        entries.forEach(function(item) {
          totalQty += item.qty;
          totalPrice += item.price * item.qty;
          var tr = document.createElement('tr');
          tr.innerHTML = '<td class="text-center">' + (serial++) + '</td>'
            + '<td>' + item.name + '</td>'
            + '<td class="text-right">' + (item.price * item.qty).toFixed(2) + '</td>'
            + '<td class="text-center">' + item.qty + '</td>';
          tbody.appendChild(tr);
        });

        if (totalEl) totalEl.textContent = totalQty;
        var totalPriceEl = document.getElementById('delivery-summary-total-price');
        if (totalPriceEl) totalPriceEl.textContent = totalPrice.toFixed(2);
        panel.style.display = entries.length ? '' : 'none';
      }

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.return-to-order-btn');
        if (!btn) return;
        e.preventDefault();
        if (!confirm('Return these items to order?')) return;
        var fd = new FormData();
        fd.append('return_to_order_customer_id', btn.getAttribute('data-cust-id'));
        fd.append('return_to_order_invoice_ids', btn.getAttribute('data-inv-ids'));
        fd.append('return_to_order', '1');
        fetch(window.location.href, { method: 'POST', body: fd })
          .then(function () { window.location.reload(); })
          .catch(function (err) { alert('Failed: ' + err); });
      });

      document.addEventListener('click', function (e) {
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

      document.addEventListener('click', function (e) {
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
        itemRows.forEach(function (r) {
          r.querySelectorAll('input.iid-date').forEach(function (cb) {
            if (cb instanceof HTMLInputElement) checkboxes.push(cb);
          });
        });

        if (!checkboxes.length) return;

        var allChecked = checkboxes.every(function (cb) {
          return cb.checked;
        });
        var nextState = !allChecked;

        checkboxes.forEach(function (cb) {
          cb.checked = nextState;
          cb.dispatchEvent(new Event('change', {
            bubbles: true
          }));
        });

        var currentCustomerItem = headerCell.closest('.customer-item');
        if (nextState) {
          document.querySelectorAll('.customer-item').forEach(function (item) {
            item.style.display = item === currentCustomerItem ? '' : 'none';
          });
        } else {
          document.querySelectorAll('.customer-item').forEach(function (item) {
            item.style.display = '';
          });
        }
      });

    </script>

    <div id='cust-loc-modal' class='cust-loc-modal'>
      <div class='title'>Customer Location</div>
      <div id='cust-loc-body' class='body'></div>
    </div>

    <script>
      (function () {
        var modal = document.getElementById('cust-loc-modal');
        var body = document.getElementById('cust-loc-body');
        var activeCust = null;

        function hide() {
          if (!modal) return;
          modal.classList.remove('show');
          activeCust = null;
        }

        function show(text, custId) {
          if (!modal || !body) return;
          body.textContent = text || '';
          modal.classList.add('show');
          activeCust = custId || null;
        }

        document.addEventListener('click', function (e) {
          var toggle = e.target.closest('.cust-loc-toggle');
          if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var custId = toggle.getAttribute('data-cust');
            if (activeCust && custId && activeCust === custId && modal.classList.contains('show')) {
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
    </script>
</form>


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
  $post_data = isset($_POST['deliver']) ? $_POST : (array) $post;
  // dd($post_data);
  foreach ($post_data['iid'] as $key => $qty) {
    if ($qty > 0) {
      $ii = R::load("invoice_item", $key);
      $inv = R::load("invoice", $ii->invoice_id);
      update("invoice_item", "delivered=quantity, delivered_by=" . uid() . ", delivered_at=NOW(),delivery_staff='{$post_data['delivery_staff']}'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
      insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$key, $qty, " . uid() . ",'{$post_data['delivery_staff']}'");
    }
  }

  // If it's an AJAX request, return success message
  if (isset($_POST['deliver']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode(['success' => true, 'message' => 'Delivery marked successfully']);
    exit;
  }
}

if (isset($_POST['collect'])) {
  $post = $_POST;
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($post['salesman']) ? $post['salesman'] : 0;
  if (isset($post['collect'])) {
    $obj->delivery_staff = $post['delivery_staff'];
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post['variance'] as $id => $qty) {
      if ($qty == 0)
        continue;
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

    redir("?");
  }
}


if (isset($_POST['update_delivery_date'])) {
  $post = $_POST;
  $ii = R::load('invoice_item', $post['id']);
  $ii->delivery_date = $post['date'];
  dd($ii);
  R::store($ii);
}

if (isset($_POST['save'])) {
  $post = $_POST;
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($post['salesman']) ? $post['salesman'] : 0;
  if (isset($post['save'])) {
    $obj->delivery_staff = $post['delivery_staff'];
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post['variance'] as $id => $qty) {
      if ($qty == 0)
        continue;
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

    redir(ROOT . "/delivery?s=$obj->delivery_staff");
  }
}
?>