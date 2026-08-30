<?php
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");
require_once("../core/functions.php");

$restrictedUser = !canEditPriceAndQuantity();
$canEditDate = canEditDateOnly();

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$order = $_POST['order'] ?? 'false';
$pending = $_POST['pending'] ?? 'false';
$delivery = $_POST['delivery'] ?? 'false';
$collection = $_POST['collection'] ?? 'false';
$customers = $_POST['customers'] ?? '';
$products = $_POST['products'] ?? '';

extract($_POST);
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
if ($order == 'false' && $pending == 'false' && $delivery == 'false' && $collection == 'false') exit;

$filter = " WHERE ";
$cities = preg_replace('/[^0-9,]/', '', $customers);
$products = preg_replace('/[^0-9,]/', '', $products);
if (nn($cities)) {
  $filter .= " city.id IN ($cities)";
}
if (nn($products)) {
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.product_id IN ($products)";
}
//$filter .= ($filter != " WHERE " ? " AND " : " ").($pending == 'true' ? " i.invoice_date < curdate()" : " i.invoice_date = curdate()");
$filter .= ($filter != " WHERE " ? " AND " : " ") . ($pending == 'true' ? " IFNULL(ii.delivery_date,i.invoice_date) < curdate()" : " IFNULL(ii.delivery_date,i.invoice_date) = curdate()");
$filter .= ($filter != " WHERE " ? " AND " : " ") . " ii.quantity >= ii.delivered";

if (nn($branch_id)) {
  $branchId = (int) $branch_id;
  $filter .= ($filter != " WHERE " ? " AND " : " ") . " (c.branch_id = $branchId OR c.branch_id IS NULL)";
}

$query = "SELECT distinct c.* FROM customer c INNER JOIN invoice i ON c.id=i.customer_id INNER JOIN invoice_item ii ON i.id=ii.invoice_id LEFT JOIN city ON city.name=c.city $filter";

$customers = select($query);
// print $query;

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
    width: 50px;
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
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
  }

  .customer-item td:nth-child(1) input[type=checkbox] {
    display: block;
    margin: 0 auto;
  }

  .customer-item td:nth-child(1) a {
    display: block;
    text-align: center;
    line-height: 1;
    margin-top: 2px;
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
    font-size: .9rem;
  }
</style>
<form method='post' id='dcollectForm'>
  <div class="customer-container">
    <?php
    $pending = $pending == "true";
    $delivery = $delivery == "true";
    $collection = $collection == "true";
    $pendingList = false; //$pendingList == "true";
    if ($pendingList) $delivery = $pendingList;
    while ($customer = mysqli_fetch_object($customers)) {
      // vd($customer);
      // print "<div>$customer->id</div>";
      $con = "<div class='customer-item customer-$customer->id'>
			<table class='table table-bordered toggle-store-info hide-info'>
				<thead>
					<tr>";
      if ($delivery || $collection) {
        $con .= "<th colspan='5'>TOTAL CONFIRMED ORDERED LIST <span style='float:right'><i class='fas fa-eye' onclick='toggleCollected()'></i></span></th>";
      } else {
        $con .= "<th style='width:20px'>1</th><th colspan='4'><span id='cust-chevron-$customer->id' class='material-icons cust-toggle' data-cust='$customer->id' style='margin-right:6px; cursor:pointer; font-size:18px; vertical-align:middle;'>expand_more</span><a class='customer-link cust-toggle' data-cust='$customer->id' href='#' style='cursor:pointer'>$customer->company ($customer->city) - <strong style='font-size:large'>$customer->code</strong></a>";
        if ($order == 'true') {
          $con .= " <input type='checkbox' class='selected-customer' data-cust='$customer->id' name='selected_customer[]' value='$customer->id' style='float:right; margin-top:2px;'>";
        }
        $con .= "</th>";
      }
      $con .= "</tr>
				</thead>
				<tbody id='cust-body-$customer->id'>";

      $cat = "";
      $filter = "i.customer_id=$customer->id ";
      if ($pending) {
        if ($filter) {
          $filter .= " AND ";
        }
        // var_dump($pending);
        //IFNULL(ii.delivery_date, i.invoice_date) 
        $filter .= " ii.delivered = 0 AND IFNULL(ii.delivery_date,i.invoice_date) < curdate()";
      } else {
        if ($filter) {
          $filter .= " AND ";
        }
        $filter .= "IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
      }
      if ($delivery || $collection) {
        $filter = "";
        $filter .= "IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
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
        $filter .= " ii.product_id IN ($products)";
      }
      $stField = 'stock';
      // if($delivery) $stField = 'stockCurrent';
      // if($pendingList) $stField = 'stockPending';
      $pq = "SELECT $stField(pv.id) stock, i.customer_id, i.id, p.name, pc.name pc_name, ii.id iid, p.id pid, p.name, pv.id vid, pv.particulars, pv.min_stock, SUM(ii.quantity) quantity, 
						ii.delivered delivered, ii.price, ii.old_price, IFNULL(ii.delivery_date,i.invoice_date) dd FROM invoice i 
						INNER JOIN invoice_item ii ON i.id=ii.invoice_id
						INNER JOIN customer c ON c.id=i.customer_id
						INNER JOIN city ON c.city=city.name
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
          if ($delivery) {
            print "<th>Avail</th><th>Shortage</th><th>Ordered</th></tr>";
          } elseif ($collection) {
            print "<th>Invoice</th><th>Avail</th><th>Shortage</th><th><a href='javascript:fillAllCollectionQty()'>Ordered</a></th><th>Collected</th><th>Collection</th><th><i onClick='toggleInfo()' class='fas fa-eye'></i></th>";
            // print "<th>Return</th><th>Return</th><th>Damaged</th><th>Balance</th>";
            print "</tr>";
          } else {
            print "<th>Price</th><th class='order-qty-col'>Ordered Qty</th><!--th>Select</th--></tr>";
          }
          $cat = $i->pc_name;
        }

        $collected = 0;
        if ($collection) {
          $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
        } elseif (!$delivery) {
          $collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND invoice_item_id=$i->iid AND DATE(created_at)=CURDATE()");
        }
        $partAttr = htmlspecialchars($i->particulars, ENT_QUOTES);
        print "<tr data-vid='$i->vid' data-qty='$i->quantity' data-collected='$collected' data-unit-price='$i->price' data-particulars='$partAttr' class='" . ($i->quantity - $collected == 0 ? 'all-collected' : '') . "'><td><div><input type='checkbox' class='iid-date' name='iid[$i->iid]' value='$i->iid'> " . ($canEditDate ? "<a href='#' id='invoice-item-date-$i->iid' data-dd='" . df($i->dd) . "' onClick='setDate(this, $i->iid)'>$ci</a>" : "<span>$ci</span>") . "</div></td>
							<td>$i->particulars X " . (!$canEditPriceQty ? "<span>$i->quantity</span>" : "<a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$i->quantity</a>") . "</td>";
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
          print "<td>";
          if (!$canEditPriceQty) {
            print nf($i->price * $i->quantity);
          } elseif ($i->old_price) {
            print nf($i->price * $i->quantity);
          } else {
            print "<a data-id='$i->iid' id='invoice-item-price-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemIdPrice($i->iid, $i->price)' data-price='$i->price' data-bs-target='#modal-modify-price'>" . nf($i->price * $i->quantity) . "</a>";
          }
          print "</td><td class='text-center order-qty-col'>$collected</td>";
          //<td>" . ($i->quantity <= $collected ? "<input type='checkbox' name='iid[{$i->iid}]' value='$i->quantity'>" : "") . "</td>";
        }
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
      if ($delivery || $collection) break;
    }
    ?>

    <div id='consolidated-container' class='customer-item' style='display:none;'>
      <table class='table table-bordered toggle-store-info hide-info'>
        <thead>
          <tr>
            <th style='width:50px; text-align:center;'>#</th>
            <th>Name</th>
            <th style='width:80px; text-align:center;'>Price</th>
            <th style='width:80px; text-align:center;' class='order-qty-col'>Order Qty</th>
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

    function rebuildConsolidatedTable() {
      var map = new Map();

      var selected = document.querySelectorAll('input.selected-customer:checked');
      var container = document.getElementById('consolidated-container');
      var tbody = document.getElementById('consolidated-body');
      if (!container || !tbody) return;

      if (!selected.length) {
        container.style.display = 'none';
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
        container.style.display = 'none';
        return;
      }

      container.style.display = '';

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
          cb.className = 'iid-date';
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
    }

    document.addEventListener('change', function(e) {
      var cb = e.target;
      if (!(cb instanceof HTMLInputElement)) return;

      if (cb.classList.contains('selected-customer')) {
        if (cb.checked && !window.__dcollectCollapsedOnce) {
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

    rebuildConsolidatedTable();
  </script>
</form>