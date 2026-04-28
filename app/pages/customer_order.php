<?php
 if (isset($post->delete_id)) {
   $delete_id = (int)$post->delete_id;
   $orderBean = R::load('customer_order', $delete_id);
   if ($orderBean && $orderBean->id) {
     if (strtolower((string)$orderBean->status) !== 'approved') {
       $itemsBeans = R::find('customer_order_item', ' customer_order_id = ? ', [$delete_id]);
       foreach ($itemsBeans as $item) {
         R::trash($item);
       }
       R::trash($orderBean);
     }
   }
  //  header('Location: ?page=customer_order');
  //  exit;
 }
// Detail endpoint: return just the modal body HTML when ?detail=ID
if(isset($post->approve_id)){
  $approve_id = (int)$post->approve_id;
  // Fetch the order and items
  $orderBean = R::load('customer_order', $approve_id);
  if ($orderBean && $orderBean->id) {
    // Insert into invoice
    $invoice = R::dispense('invoice');
    $invoice->customer_id = (int)$orderBean->customer_id;
    $invoice->order_id = $approve_id;
    $invoice->invoice_date = $orderBean->invoice_date;
    $invoice->delivery_date = $orderBean->invoice_date;
    $invoice->status = 'approved';
    $invoice_id = R::store($invoice);

    // Insert each item into invoice_item
    $itemsBeans = R::findAll('customer_order_item', ' customer_order_id = ? ', [$approve_id]);
    foreach ($itemsBeans as $item) {
      $invItem = R::dispense('invoice_item');
      $invItem->invoice_id = $invoice_id;
      $invItem->product_id = isset($item->product_id) ? (int)$item->product_id : null;
      $invItem->product_variance_id = isset($item->product_variance_id) ? (int)$item->product_variance_id : null;
      $invItem->quantity = isset($item->quantity) ? (float)$item->quantity : 0.0;
      $invItem->price = isset($item->price) ? (float)$item->price : 0.0;
      R::store($invItem);
    }

    // Update order as approved
    $orderBean->status = 'approved';
    R::store($orderBean);
  }

}
// Build and auto-submit a form to /store/invoice for a given order id
if (isset($get) && isset($get->approve_form)) {
  $id = (int)$get->approve_form;
  $order = R::load('customer_order', $id);
  if (!($order && $order->id)) {
    echo '<div class="text-danger">Order not found.</div>';
    exit;
  }

  if (strtolower((string)$order->status) !== 'approved') {
    $items = R::find('customer_order_item', ' customer_order_id = ? ', [$id]);

    $invoice = R::dispense('invoice');
    $invoice->customer_id = (int)$order->customer_id;
    $invoice->customer_order_id = (int)$order->id;
    $invoice->invoice_date = $order->invoice_date;
    $invoice->delivery_date = $order->invoice_date;
    $invoice->status = 'approved';
    $invoice_id = R::store($invoice);

    foreach ($items as $item) {
      $invItem = R::dispense('invoice_item');
      $invItem->invoice_id = (int)$invoice_id;
      $invItem->product_id = isset($item->product_id) ? (int)$item->product_id : null;
      $invItem->product_variance_id = isset($item->product_variance_id) ? (int)$item->product_variance_id : null;
      $invItem->quantity = isset($item->quantity) ? (float)$item->quantity : 0.0;
      $invItem->price = isset($item->price) ? (float)$item->price : 0.0;
      R::store($invItem);
    }

    foreach ($items as $item) {
      R::trash($item);
    }
    R::trash($order);
  }

  // redir('?page=customer_order');
}
if (isset($get) && isset($get->detail)) {
  $id = (int)$get->detail;
  $hres = select("SELECT co.*, c.company FROM customer_order co LEFT JOIN customer c ON c.id=co.customer_id WHERE co.id=$id LIMIT 1");
  $h = $hres ? mysqli_fetch_object($hres) : null;
  $items = select("SELECT i.*, pv.particulars AS variant, p.name AS product_name FROM customer_order_item i LEFT JOIN product_variance pv ON pv.id=i.product_variance_id LEFT JOIN product p ON p.id=i.product_id WHERE i.customer_order_id=$id");
  ob_start();
  ?>
  <div class="container-fluid">
    <?php if ($h): ?>
      <div class="mb-2">
        <div><strong>Order #</strong> <?php echo (int)$h->id; ?></div>
        <div><strong>Customer</strong> <?php echo htmlspecialchars($h->company ?: '—'); ?></div>
        <div><strong>Order Date</strong> <?php echo htmlspecialchars($h->order_date ?: '—'); ?></div>
        <div><strong>Status</strong> <?php echo htmlspecialchars($h->status ?: '—'); ?></div>
      </div>
    <?php else: ?>
      <div class="text-danger">Order not found.</div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-sm table-bordered table-striped align-middle mb-0">
        <thead>
          <tr>
            <th style="width:60%">Item</th>
            <th class="text-center" style="width:10%">Qty</th>
            <th class="text-end" style="width:15%">Price</th>
            <th class="text-end" style="width:15%">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $totalQty = 0; $totalAmt = 0.0;
          if ($items) while ($row = mysqli_fetch_object($items)) {
            $name = $row->product_name ?: $row->name;
            $variant = $row->variant ?: $row->description;
            $qty = (int)$row->quantity;
            $price = (float)($row->price );
            $sub = $qty * $price;
            $totalQty += $qty; $totalAmt += $sub;
            echo '<tr>';
            echo '<td>'.htmlspecialchars(trim($name.' '.$variant)).'</td>';
            echo '<td class="text-center">'.$qty.'</td>';
            echo '<td class="text-end">'.number_format($price,2).'</td>';
            echo '<td class="text-end">'.number_format($sub,2).'</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
        <tfoot>
          <tr>
            <th class="text-end">Totals</th>
            <th class="text-center"><?php echo (int)$totalQty; ?></th>
            <th></th>
            <th class="text-end"><?php echo number_format($totalAmt,2); ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <?php
  echo ob_get_clean();
  exit;
}
?>

<div class="row">
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Customer Orders</h5>
      </div>
      <div class="card-body">
        <?php
        $today = date('Y-m-d');
        $defaultFrom = date('Y-m-d', strtotime('-5 days'));

        $fromDate = $defaultFrom;
        $toDate = $today;
        if (isset($get) && isset($get->from) && $get->from) {
          $fromDate = preg_replace('/[^0-9\-]/', '', (string)$get->from);
        }
        if (isset($get) && isset($get->to) && $get->to) {
          $toDate = preg_replace('/[^0-9\-]/', '', (string)$get->to);
        }

        $statusFilter = 'non-approved';
        if (isset($get) && isset($get->status) && $get->status) {
          $statusFilter = (string)$get->status;
        }
        ?>

        <form class="row g-2 align-items-end mb-3" method="get" action="">
          <input type="hidden" name="page" value="customer_order">
          <div class="col-sm-2">
            <label class="form-label mb-1">From</label>
            <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
          </div>
          <div class="col-sm-2">
            <label class="form-label mb-1">To</label>
            <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
          </div>
          <div class="col-sm-1">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
        </form>

        <div class="table-responsive">
          <table id="co-table" class="table table-hover table-bordered table-striped align-middle">
            <thead>
              <tr>
                <th style="width:80px">ID</th>
                <th>Shop Name</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th style="width:120px">Time Passed</th>
                <th>Items</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Amount</th>
                <th style="width:200px"></th>
              </tr>
            </thead>
            <tbody>
              <?php
              global $c;
              $fromSql = isset($c) ? mysqli_real_escape_string($c, $fromDate) : $fromDate;
              $toSql = isset($c) ? mysqli_real_escape_string($c, $toDate) : $toDate;
              $where = " WHERE DATE(co.invoice_date) BETWEEN '$fromSql' AND '$toSql' ";

              $sql = "SELECT co.id, co.customer_id, co.invoice_date, co.status, c.company, c.contact,
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
                       LIMIT 100";
              $res = select($sql);
              if ($res === false) {
                global $c;
                $err = isset($c) ? mysqli_error($c) : 'Query failed.';
                echo '<tr><td colspan="9" class="text-danger">'.htmlspecialchars($err).'</td></tr>';
              } elseif ($res) {
                $i = 1;
                while ($o = mysqli_fetch_object($res)) {
                  $elapsedStr = '—';
                  $baseDateStr = '';
                  if (isset($o->order_date) && $o->order_date) {
                    $baseDateStr = (string)$o->order_date;
                  } elseif (isset($o->invoice_date) && $o->invoice_date) {
                    $baseDateStr = (string)$o->invoice_date;
                  }
                  if ($baseDateStr) {
                    try {
                      $start = new DateTime($baseDateStr);
                      $now = new DateTime();
                      if ($start <= $now) {
                        $diff = $start->diff($now);
                        $days = (int)$diff->days;
                        $hours = (int)$diff->h;
                        $elapsedStr = $days . 'd ' . $hours . 'h';
                      } else {
                        $elapsedStr = '0d 0h';
                      }
                    } catch (Exception $e) {
                      $elapsedStr = '—';
                    }
                  }
                  echo '<tr class="co-row" data-id="'.(int)$o->id.'">';
                  echo '<td>'.(int)$i++.'</td>';
                  echo '<td><a href="'.ROOT.'/customer/details/'.(int)$o->customer_id.'">'.htmlspecialchars($o->company ?: '—').'</a></td>';
                  echo '<td><a href="'.ROOT.'/customer/details/'.(int)$o->customer_id.'">'.htmlspecialchars($o->contact ?: '—').'</a></td>';
                  echo '<td>'.htmlspecialchars($o->invoice_date ?: '—').'</td>';
                  echo '<td>'.htmlspecialchars($elapsedStr).'</td>';
                  echo '<td>'.($o->items_html ?: '—').'</td>';
                  echo '<td class="text-center">'.(int)$o->qty.'</td>';
                  echo '<td class="text-end">'.number_format((float)$o->amount,2).'</td>';
                  echo '<td>';
                  if (strtolower($o->status) !== 'approved') {
                    echo '<button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); approveOrder('.(int)$o->id.')">Approve</button>';
                    echo ' <button type="button" class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteOrder('.(int)$o->id.')">Delete</button>';
                  }
                  echo '</td>';

                  echo '</tr>';
                }
              }
              ?>
            </tbody>
          </table>
        </div>

        <form id="coDeleteForm" method="post" action="?page=customer_order" style="display:none">
          <input type="hidden" name="delete_id" id="coDeleteId" value="">
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="coDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    // function openDetail(id){
    //   const body = document.querySelector('#coDetailModal .modal-body');
    //   if (body) body.innerHTML = '<div class="text-muted">Loading...</div>';
    //   const url = '?page=customer_order&detail=' + encodeURIComponent(id);
    //   fetch(url, { credentials: 'same-origin' })
    //     .then(r => r.text())
    //     .then(html => { if (body) body.innerHTML = html; })
    //     .catch(() => { if (body) body.innerHTML = '<div class="text-danger">Failed to load details.</div>'; });

    //   const modalEl = document.getElementById('coDetailModal');
    //   try{
    //     if (typeof bootstrap !== 'undefined' && bootstrap.Modal){
    //       const m = bootstrap.Modal.getOrCreateInstance(modalEl);
    //       m.show();
    //     } else {
    //       modalEl.classList.add('show');
    //       modalEl.style.display = 'block';
    //     }
    //   }catch(e){
    //     modalEl.classList.add('show');
    //     modalEl.style.display = 'block';
    //   }
    // }

    // Expose approve submitter globally for inline onclick
    window.approveOrder = function(id){
    debugger;
      // Redirect to server endpoint that prepares and auto-submits a full form to /store/invoice
      const url = '?page=customer_order&approve_form=' + encodeURIComponent(id);
      window.location.href = url;
    };

    window.deleteOrder = function(id){
      if (!confirm('Delete this order? This cannot be undone.')) return;
      const input = document.getElementById('coDeleteId');
      const form = document.getElementById('coDeleteForm');
      if (!input || !form) return;
      input.value = id;
      form.submit();
    };

    document.querySelectorAll('#co-table .co-row').forEach(tr => {
      tr.style.cursor = 'pointer';
      tr.addEventListener('click', () => openDetail(tr.getAttribute('data-id')));
    });
  })();
</script>
