<?php
// Detail endpoint: return just the modal body HTML when ?detail=ID
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
        <div><strong>Invoice Date</strong> <?php echo htmlspecialchars($h->invoice_date ?: '—'); ?></div>
        <div><strong>Status</strong> <?php echo htmlspecialchars($h->status ?: '—'); ?></div>
      </div>
    <?php else: ?>
      <div class="text-danger">Order not found.</div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
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
            $price = (float)($row->price ?? 0);
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
        <div class="table-responsive">
          <table id="co-table" class="table table-hover table-striped align-middle">
            <thead>
              <tr>
                <th style="width:80px">ID</th>
                <th>Customer</th>
                <th>Invoice Date</th>
                <th>Status</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT co.id, co.invoice_date, co.status, c.company,
                             IFNULL(SUM(i.quantity),0) qty,
                             IFNULL(SUM(i.quantity*i.price),0) amount
                        FROM customer_order co
                        LEFT JOIN customer c ON c.id=co.customer_id
                        LEFT JOIN customer_order_item i ON i.customer_order_id=co.id
                       GROUP BY co.id
                       ORDER BY co.id DESC
                       LIMIT 100";
              $res = select($sql);
              if ($res) while ($o = mysqli_fetch_object($res)) {
                echo '<tr class="co-row" data-id="'.(int)$o->id.'">';
                echo '<td>'.(int)$o->id.'</td>';
                echo '<td>'.htmlspecialchars($o->company ?: '—').'</td>';
                echo '<td>'.htmlspecialchars($o->invoice_date ?: '—').'</td>';
                echo '<td>'.htmlspecialchars($o->status ?: '—').'</td>';
                echo '<td class="text-center">'.(int)$o->qty.'</td>';
                echo '<td class="text-end">'.number_format((float)$o->amount,2).'</td>';
                echo '</tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
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
    function openDetail(id){
      const body = document.querySelector('#coDetailModal .modal-body');
      if (body) body.innerHTML = '<div class="text-muted">Loading...</div>';
      const url = '?page=customer_order&detail=' + encodeURIComponent(id);
      fetch(url, { credentials: 'same-origin' })
        .then(r => r.text())
        .then(html => { if (body) body.innerHTML = html; })
        .catch(() => { if (body) body.innerHTML = '<div class="text-danger">Failed to load details.</div>'; });

      const modalEl = document.getElementById('coDetailModal');
      try{
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal){
          const m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else {
          modalEl.classList.add('show');
          modalEl.style.display = 'block';
        }
      }catch(e){
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
      }
    }

    document.querySelectorAll('#co-table .co-row').forEach(tr => {
      tr.style.cursor = 'pointer';
      tr.addEventListener('click', () => openDetail(tr.getAttribute('data-id')));
    });
  })();
</script>
