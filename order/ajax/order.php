<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('display_startup_errors', '1');
  
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");

// Functions loaded successfully from f.inc.php

// var_dump($_POST);
// var_dump($post);


$customers = isset($_POST['customers']) ? $_POST['customers'] : '';
$products = isset($_POST['products']) ? $_POST['products'] : '';

extract($_POST);

$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;

$filter = " WHERE ";
$cities = preg_replace('/[^0-9,]/', '', $customers);
$products = preg_replace('/[^0-9,]/', '', $products);

if (function_exists('nn')) {
  if (nn($cities)) {
    $filter .= " city.id IN ($cities)";
  }

  if (nn($products)) {
    $filter .= ($filter != " WHERE " ? " AND " : " ") . " p.product_category_id IN ($products)";
  }
} else {
  // Fallback if nn() function doesn't exist
  if (!empty($cities) && $cities !== '') {
    $filter .= " city.id IN ($cities)";
  }

  if (!empty($products) && $products !== '') {
    $filter .= ($filter != " WHERE " ? " AND " : " ") . " p.product_category_id IN ($products)";
  }
}

$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-26'";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity > ii.delivered";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " (ii.assigned_at IS NULL OR ii.assigned_to IS NULL OR ii.assigned_to = '')";
$filter .= ($filter != " WHERE " ? " AND " : " ") . " (ii.collected_at IS NULL AND NOT EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";

// Check if functions exist before using them
if (function_exists('nn') && nn($branch_id)) {
  $branchId = (int) $branch_id;
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " (c.branch_id = $branchId OR c.branch_id IS NULL)";
}

// Optimized query: Get customers with their order data in one query
$query = "SELECT c.*, 
                 COUNT(DISTINCT ii.id) as total_items,
                 SUM(ii.quantity - ii.delivered) as total_quantity,
                 SUM((ii.quantity - ii.delivered) * ii.price) as total_amount,
                 GROUP_CONCAT(DISTINCT pc.name ORDER BY pc.name SEPARATOR ', ') as categories
          FROM customer c 
          INNER JOIN invoice i ON c.id=i.customer_id 
          INNER JOIN invoice_item ii ON i.id=ii.invoice_id 
          LEFT JOIN city ON city.name=c.city 
          INNER JOIN product p ON p.id=ii.product_id 
          INNER JOIN product_category pc ON p.product_category_id=pc.id
          $filter
          GROUP BY c.id
          ORDER BY c.company";

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

  /* Narrower Qty column (4th column) */
  .customer-item td:nth-child(4),
  .customer-item th:nth-child(4) {
    width: 18px;
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
    font-size: .9rem;
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

      // Build customer location text for modal (like ajax/dcollect.php)
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
        <span id='cust-chevron-$customer->id' class='material-icons cust-toggle' data-cust='$customer->id' style='cursor: pointer;
    font-size: 18px;
    vertical-align: middle;margin-top:4px; padding: 5px;
    float: left; margin-left: -12px;'>expand_more</span><a class='customer-link' href='?page=customer_details&id=$customer->id' style='cursor:pointer'>$customer->company ($customer->city) - <strong style='font-size:largee'>$customer->code</strong></a><span class='material-icons cust-loc-icon cust-loc-toggle' data-cust='$customer->id' data-loc='$custLocText' style='cursor:pointer; padding: 5px; position:absolute; font-size:18px; float: right; vertical-align:middle; user-select:none; color:#444; margin-left:8px;'>chevron_right</span>$pendingOrderMeta";
        $con .= "
        <span style='display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:#eee; color:#333; font-weight:600; border:1px solid #ccc;'>$customerSerial</span>
         <input type='checkbox' class='selected-customer' data-cust='$customer->id' name='selected_customer[]' value='$customer->id' style='float:right; margin-top:2px;'>";
        $con .= "</th>";
      $con .= "</tr>
				</thead>
				<tbody id='cust-body-$customer->id'>";

      $cat = "";
      $itemFilter = "i.customer_id=$customer->id ";
      $itemFilter .= " AND ii.delivered < ii.quantity";
      $itemFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
      $itemFilter .= " AND IFNULL(ii.delivery_date,i.invoice_date) >= '2026-03-26'";
      $itemFilter .= " AND (ii.assigned_at IS NULL OR ii.assigned_to IS NULL OR ii.assigned_to = '')";
      $itemFilter .= " AND (ii.collected_at IS NULL AND NOT EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id=ii.id LIMIT 1))";

      if (nn($cities)) {
        $itemFilter .= " AND city.id IN ($cities)";
      }

      if (nn($products)) {
        $itemFilter .= " AND p.product_category_id IN ($products)";
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
          print "<th>Price</th><th>Qty</th></tr>";
          $cat = $i->pc_name;
        }

$collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
        $partAttr = htmlspecialchars($i->particulars, ENT_QUOTES);
        print "<tr data-vid='$i->vid' data-qty='$i->quantity' data-collected='$collected' data-unit-price='$i->price' data-particulars='$partAttr' class='" . ($i->quantity - $collected == 0 ? 'all-collected' : '') . "'><td><div><input type='checkbox' class='iid-date' name='iid[$i->iid]' value='$i->iid'> <a href='#' id='invoice-item-date-$i->iid' data-dd='" . df($i->dd) . "' onClick='setDate(this, $i->iid)'>$ci</a></div></td>
							<td>$i->particulars (<a class='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>$i->price</a>)</td>";
$collected = $i->quantity;
        print "<td>";
        if ($i->old_price && false) {
          print nf($i->price * $i->quantity);
        } else {
          print "<a data-id='$i->iid' id='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price * $i->quantity) . "</a>";
        }
        //order-qty-col
        print "</td><td class='text-center '>
        <a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$i->quantity</a>
        </td>";
        print "</tr>";
        $ic++;

        $ci++;
      }
      if ($printedCon) {
        print "</tbody>
					</table>
				</div>";
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
            <th style='width:40px; text-align:center;' class='order-qty-col'>Qty</th>
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

      container.classList.remove('show');
      tbody.innerHTML = '';
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

      if (!map.size) {
        container.classList.remove('show');
        return;
      }

      container.classList.add('show');

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

      // If nothing was appended, hide the consolidated container
      if (!tbody || tbody.children.length === 0) {
        container.classList.remove('show');
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
    });
  })();
</script>
</form>

