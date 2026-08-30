<?php
global $c;

// Handle approve action - redirect to invoice form with selected order items
$approveId = 0;
if (isset($post) && isset($post->approve_form)) {
    $approveId = (int)$post->approve_form;
} elseif (isset($get) && isset($get->approve_form) && $get->approve_form) {
    $approveId = (int)$get->approve_form;
}

if ($approveId) {
    $id = $approveId;
    
    // Load order and items
    $orderSql = "SELECT * FROM customer_order WHERE id = $id LIMIT 1";
    $orderResult = mysqli_query($c, $orderSql);
    $order = mysqli_fetch_object($orderResult);
    
    if (!$order || !$order->id) {
        echo '<div class="alert alert-danger">Order not found.</div>';
        exit;
    }
    
    $selectedIds = [];
    if (isset($post) && isset($post->coi) && is_array($post->coi)) {
        $selectedIds = $post->coi;
    }

    $selectedIds = array_values(array_filter(array_map('intval', (array)$selectedIds)));
    if (empty($selectedIds)) {
        echo "<script>alert('Please select at least one item to approve.');window.history.back();</script>";
        exit;
    }

    $idList = implode(',', $selectedIds);
    $itemsSql = "SELECT * FROM customer_order_item WHERE customer_order_id = $id AND id IN ($idList)";
    $itemsResult = mysqli_query($c, $itemsSql);

    // Store invoice + invoice_items here (no redirect to invoice page)
    $invoice = R::dispense('invoice');
    $invoice->customer_id = (int)$order->customer_id;
    $invoice->customer_order_id = (int)$order->id;
    $invoice->invoice_date = isset($order->invoice_date) ? $order->invoice_date : today();
    $invoice->delivery_date = $invoice->invoice_date;
    $invoice->status = 'approved';
    $invoice->created_by = isset($_SESSION['UID']) ? (int)$_SESSION['UID'] : (function_exists('uid') ? uid() : null);
    $invoice->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
    $invoiceId = R::store($invoice);

    $approvedItemIds = [];
    while ($item = mysqli_fetch_object($itemsResult)) {
        $varianceId = isset($item->product_variance_id) ? (int)$item->product_variance_id : 0;
        if ($varianceId <= 0) continue;

        $ii = R::dispense('invoice_item');
        $ii->invoice_id = (int)$invoiceId;
        $ii->product_id = isset($item->product_id) ? (int)$item->product_id : null;
        $ii->product_variance_id = $varianceId;
        $ii->quantity = isset($item->quantity) ? (float)$item->quantity : 0;
        $ii->price = isset($item->price) ? (float)$item->price : 0;
        $ii->delivery_date = $invoice->invoice_date;
        R::store($ii);

        if (isset($item->id)) {
            $approvedItemIds[] = (int)$item->id;
        }
    }

    if (!empty($approvedItemIds)) {
        $approvedList = implode(',', array_values(array_filter(array_map('intval', $approvedItemIds))));
        mysqli_query($c, "DELETE FROM customer_order_item WHERE customer_order_id = $id AND id IN ($approvedList)");
    }

    $remainRes = mysqli_query($c, "SELECT COUNT(*) c FROM customer_order_item WHERE customer_order_id = $id");
    $remain = $remainRes ? (int)mysqli_fetch_object($remainRes)->c : 0;
    if ($remain <= 0) {
        mysqli_query($c, "DELETE FROM customer_order WHERE id = $id");
    }

    if (isset($order->customer_id)) {
        // redir("?page=customer_details&id=" . (int)$order->customer_id);
    }
    // redir("?page=customer_order");
}

// Handle delete action
if (isset($post) && isset($post->action) && $post->action === 'delete' && isset($post->order_id)) {
    $orderId = (int)$post->order_id;
    $fromRaw = isset($post->from) ? (string)$post->from : '';
    $toRaw = isset($post->to) ? (string)$post->to : '';
    $from = $fromRaw !== '' ? preg_replace('/[^0-9\-]/', '', $fromRaw) : '';
    $to = $toRaw !== '' ? preg_replace('/[^0-9\-]/', '', $toRaw) : '';
    
    // First delete related items
    $deleteItemsSql = "DELETE FROM customer_order_item WHERE customer_order_id = $orderId";
    mysqli_query($c, $deleteItemsSql);
    
    // Then delete the order
    $deleteOrderSql = "DELETE FROM customer_order WHERE id = $orderId";
    mysqli_query($c, $deleteOrderSql);
    
    // Redirect to refresh the page
    $qs = '';
    if ($from !== '' && $to !== '') {
        $qs = '&from=' . urlencode($from) . '&to=' . urlencode($to);
    }
    // header("Location: ?page=customer_order" . $qs);
    // exit;
}

// Get filter parameters
$fromDate = date('Y-m-d', strtotime('-5 days'));
$toDate = date('Y-m-d');

$get = (object)(isset($_GET) ? $_GET : []);
if (isset($get) && isset($get->from) && $get->from) {
    $fromDate = preg_replace('/[^0-9\-]/', '', (string)$get->from);
}
if (isset($get) && isset($get->to) && $get->to) {
    $toDate = preg_replace('/[^0-9\-]/', '', (string)$get->to);
}

// Fetch orders
$fromSql = mysqli_real_escape_string($c, $fromDate);
$toSql = mysqli_real_escape_string($c, $toDate);
$where = " WHERE DATE(co.invoice_date) BETWEEN '$fromSql' AND '$toSql' ";

$sql = "SELECT co.id, co.customer_id, co.invoice_date, co.status, c.company, c.contact, c.mobile,
               coItems(co.id) items_html,
               IFNULL(SUM(i.quantity),0) qty,
               IFNULL(SUM(i.quantity*i.price),0) amount
          FROM customer_order co
          LEFT JOIN customer c ON c.id=co.customer_id
          LEFT JOIN customer_order_item i ON i.customer_order_id=co.id
          LEFT JOIN product_variance pv ON pv.id=i.product_variance_id
          $where
         GROUP BY co.id
         ORDER BY co.id DESC
         LIMIT 50";
$res = mysqli_query($c, $sql);

?>
<style>
  @import url('https://fonts.googleapis.com/icon?family=Material+Icons');

  * {
    box-sizing: border-box;
  }

  th {
    text-align: center;
  }

  .orders {
    padding-bottom: 20px;
  }

  .order-container {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 10px;
    font-size: .7rem;
  }

  .order-item {
    width: 100%;
    max-width: 100%;
    flex: 0 0 100%;
    white-space: normal;
    overflow-x: auto;
    display: block !important;
    background: #fff;
    border: 1px solid #e5e7ebca;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
    overflow: hidden;
    padding: 5px;
  }

  .btn{
    margin-right: 15px;
    border-radius: 15px;
    width: 80px;
  }
  .order-item table {
    width: 100%;
    table-layout: fixed;
    margin: 0;
    table-layout: auto !important;
  }

  .order-item th,
  .order-item td {
    vertical-align: middle;
    overflow: hidden;
  }

  .order-item thead th,
  .order-item tbody td,
  .order-item tbody th,
  .order-item tfoot th {
    padding: 8px 10px !important;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .orders .order-item table.table > :not(caption) > * > * {
    padding: 8px 10px !important;
  }

  .order-item td:nth-child(1),
  .order-item th:nth-child(1) {
    width: 35px;
    text-align: center;
  }

  input[type=checkbox] {
    width: 15px;
    height: 15px;
  }

  a {
    text-decoration: underline;
  }

  a.has-checkbox {
    text-decoration: none;
    color: #000;
  }

  a.customer-link {
    text-decoration: none !important;
    color: #333 !important;
  }

  .badge {
    font-size: 10px;
    padding: 3px 6px;
  }

  .filter-form {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
  }
</style>

<form class="filter-form" method="get" action="">
  <input type="hidden" name="page" value="customer_order">
  <div class="row g-2">
    <div class="col-6">
      <label class="form-label">From</label>
      <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
    </div>
    <div class="col-6">
      <label class="form-label">To</label>
      <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </div>
</form>

<div class="orders">
  <div class="order-container">
    <?php
    if ($res && mysqli_num_rows($res) > 0) {
      $serial = 1;
      while ($row = mysqli_fetch_assoc($res)) {

        // Calculate elapsed time
        $orderDate = new DateTime($row['invoice_date']);
        $now = new DateTime();
        $interval = $orderDate->diff($now);
        $days = $interval->days;
        $hours = $interval->h;
        $timePassed = ($days > 0) ? "{$days}d {$hours}h" : "{$hours}h";

        $orderId = (int)$row['id'];
        $custId = (int)$row['customer_id'];
        $orderSerial = (int)$serial++;

        echo '<div class="order-item order-' . $orderId . '">';
        echo '<form method="post" action="?page=customer_order&from=' . urlencode($fromDate) . '&to=' . urlencode($toDate) . '">';
        echo '<input type="hidden" name="from" value="' . htmlspecialchars($fromDate) . '">';
        echo '<input type="hidden" name="to" value="' . htmlspecialchars($toDate) . '">';
        echo '<input type="hidden" name="order_id" value="' . $orderId . '">';
        echo '<table class="table table-bordered">';
        echo '<thead>';
        echo '<tr>';
        echo '<th colspan="4" style="text-align:center; text-transform:uppercase; position:relative">';
        echo '<span id="order-chevron-' . $orderId . '" class="material-icons order-toggle" data-order="' . $orderId . '" style="cursor:pointer; font-size:18px; vertical-align:middle; margin-top:4px; float:left; margin-left:-12px; padding:5px;">expand_more</span>';

        $whatsappIcon = '<a href="https://wa.me/' . $row['mobile'] . '" target="_blank" style="position:absolute;right: 30px;margin-top: 0px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:18px; height:18px;"><path fill="rgb(34, 181, 94)" d="M476.9 161.1C435 119.1 379.2 96 319.9 96C197.5 96 97.9 195.6 97.9 318C97.9 357.1 108.1 395.3 127.5 429L96 544L213.7 513.1C246.1 530.8 282.6 540.1 319.8 540.1L319.9 540.1C442.2 540.1 544 440.5 544 318.1C544 258.8 518.8 203.1 476.9 161.1zM319.9 502.7C286.7 502.7 254.2 493.8 225.9 477L219.2 473L149.4 491.3L168 423.2L163.6 416.2C145.1 386.8 135.4 352.9 135.4 318C135.4 216.3 218.2 133.5 320 133.5C369.3 133.5 415.6 152.7 450.4 187.6C485.2 222.5 506.6 268.8 506.5 318.1C506.5 419.9 421.6 502.7 319.9 502.7zM421.1 364.5C415.6 361.7 388.3 348.3 383.2 346.5C378.1 344.6 374.4 343.7 370.7 349.3C367 354.9 356.4 367.3 353.1 371.1C349.9 374.8 346.6 375.3 341.1 372.5C308.5 356.2 287.1 343.4 265.6 306.5C259.9 296.7 271.3 297.4 281.9 276.2C283.7 272.5 282.8 269.3 281.4 266.5C280 263.7 268.9 236.4 264.3 225.3C259.8 214.5 255.2 216 251.8 215.8C248.6 215.6 244.9 215.6 241.2 215.6C237.5 215.6 231.5 217 226.4 222.5C221.3 228.1 207 241.5 207 268.8C207 296.1 226.9 322.5 229.6 326.2C232.4 329.9 268.7 385.9 324.4 410C359.6 425.2 373.4 426.5 391 423.9C401.7 422.3 423.8 410.5 428.4 397.5C433 384.5 433 373.4 431.6 371.1C430.3 368.6 426.6 367.2 421.1 364.5z"/></svg></a>';
        echo '<a class="customer-link" href="?page=customer_details&id=' . $custId . '" style="cursor:pointer">' . htmlspecialchars(($row['company'] ?: 'Unknown')) . ' - <strong>' . htmlspecialchars(($row['contact'] ?: 'Unknown')) . '</strong></a>' . $whatsappIcon;
        echo '<input type="checkbox" class="order-checkall" data-order="' . $orderId . '" style="float:right; margin-top:2px;" aria-label="select all">';
        echo '<div class="order-meta">';
        echo 'ORDER DATE: ' . htmlspecialchars(date('d/m/Y', strtotime($row['invoice_date']))) . '<br>';
        echo 'TIME PASSED: ' . htmlspecialchars($timePassed) . '</div>';
        echo '<div style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:#eee; color:#333; font-weight:600; border:1px solid #ccc; margin-top:6px;">' . $orderSerial . '</div>';
        echo '</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody id="order-body-' . $orderId . '">';

        // Items header like delivery.php
        echo '<tr><th style="width:50px;"></th><th>Drinks</th><th style="width:70px;" class="text-center">Price</th><th style="width:60px;" class="text-center">Qty</th></tr>';

        $itemsSql = "SELECT i.id, i.quantity, i.price, pv.particulars
                     FROM customer_order_item i
                     LEFT JOIN product_variance pv ON pv.id=i.product_variance_id
                    WHERE i.customer_order_id=$orderId";
        $itemsRes = mysqli_query($c, $itemsSql);
        $itemSerial = 1;
        if ($itemsRes) {
          while ($it = mysqli_fetch_object($itemsRes)) {

            $particulars = isset($it->particulars) ? (string)$it->particulars : '';
            $qty = isset($it->quantity) ? (float)$it->quantity : 0;
            $price = isset($it->price) ? (float)$it->price : 0;

            $itId = isset($it->id) ? (int)$it->id : 0;
            echo '<tr>';
            echo '<td><div><input type="checkbox" name="coi[]" value="' . $itId . '"> <a href="#" class="has-checkbox">' . (int)$itemSerial++ . '</a></div></td>';
            echo '<td>' . htmlspecialchars($particulars ?: '—') . '</td>';
            echo '<td class="text-center"><a href="#">' . number_format($price, 2) . '</a></td>';
            echo '<td class="text-center">' . number_format($qty) . '</td>';
            echo '</tr>';
          }
        }

        echo '</tbody>';

        // Keep actions visible (even when collapsed) like requested
        echo '<tfoot>';
        echo '<tr>';
        echo '<th colspan="4" style="text-align:center">';
        if (strtolower((string)$row['status']) !== 'approved') {
          echo '<button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" style="margin-left:5px;" onclick="return confirm(\'Delete this order?\')">Delete</button>';
          print "<span style='margin-left:3rem;'></span>";
          echo '<button type="submit" name="approve_form" value="' . $orderId . '" class="btn btn-success btn-sm">Approve</button>'; 
        } else {
          echo '<span class="badge bg-success">Approved</span>';
        }
        echo '</th>';
        echo '</tr>';
        echo '</tfoot>';

        echo '</table>';
        echo '</form>';
        echo '</div>';
      }
    } else {
      echo '<div class="alert alert-info">No orders found</div>';
    }
    ?>
  </div>
</div>

<script>
  (function () {
    document.addEventListener('click', function (e) {
      var toggleEl = e.target.closest('.order-toggle');
      if (!toggleEl) return;

      e.preventDefault();

      var orderId = toggleEl.getAttribute('data-order');
      if (!orderId) return;

      var body = document.getElementById('order-body-' + orderId);
      var chevron = document.getElementById('order-chevron-' + orderId);
      if (!body || !chevron) return;

      var isHidden = window.getComputedStyle(body).display === 'none';
      body.style.display = isHidden ? '' : 'none';
      chevron.textContent = isHidden ? 'expand_more' : 'chevron_right';
    });

    document.addEventListener('click', function (e) {
      var a = e.target.closest('a.has-checkbox');
      if (!a) return;
      e.preventDefault();
    });

    document.addEventListener('change', function (e) {
      var cb = e.target;
      if (!cb || !cb.classList || !cb.classList.contains('order-checkall')) return;

      var orderId = cb.getAttribute('data-order');
      if (!orderId) return;

      var container = document.querySelector('.order-' + orderId);
      if (!container) return;

      var form = container.querySelector('form');
      if (!form) return;

      var items = form.querySelectorAll('input[type="checkbox"][name="coi[]"]');
      items.forEach(function (x) {
        x.checked = cb.checked;
      });
    });
  })();
</script>