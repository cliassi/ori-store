<?php
$objs = select('distinct id, name, incentive', 'staff_salary', "category='Delivery Staff'");

// Handle date range filtering
$startDate = (isset($get->start_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get->start_date))
  ? $get->start_date
  : date('Y-m-d'); // Default to 7 days ago

$endDate = (isset($get->end_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get->end_date))
  ? $get->end_date
  : date('Y-m-d'); // Default to today

// Ensure start date is not after end date
if ($startDate > $endDate) {
  $temp = $startDate;
  $startDate = $endDate;
  $endDate = $temp;
}

if (isset($get->h)) {

  $staffId = (int) $get->h;
  $staffRow = select("id, name", "staff_salary", "id=$staffId");

  $staff = $staffRow ? mysqli_fetch_object($staffRow) : null;
  $staffName = $staff ? trim((string) $staff->name) : '';
  print "<h1>$staffName</h1>";
  $staffNameSql = $staffName !== '' ? mysqli_real_escape_string($c, $staffName) : '';

  if (isset($post->save_return)) {
    $returnQty = isset($post->return_qty) ? (float) $post->return_qty : 0;
    $returnQty = $returnQty < 0 ? 0 : $returnQty;

    $returnStockCollectId = isset($post->stock_collect_id) ? (int) $post->stock_collect_id : 0;
    $returnProductId = isset($post->product_id) ? (int) $post->product_id : 0;
    $returnVarianceId = isset($post->product_variance_id) ? (int) $post->product_variance_id : 0;
    $returnStockCollectItemId = isset($post->stock_collect_item_id) ? (int) $post->stock_collect_item_id : 0;
    $returnProductName = isset($post->product_name) ? trim((string) $post->product_name) : '';
    $returnCollectDate = isset($post->collect_date) ? trim((string) $post->collect_date) : '';

    if ($returnQty > 0 && $returnStockCollectId > 0 && $returnProductId > 0) {
      $srId = 0;
      $existingSr = select(
        'id',
        'stock_return',
        "stock_collect_id=$returnStockCollectId AND salesman_id=$staffId ORDER BY id DESC LIMIT 1"
      );
      if ($existingSr) {
        $existingSrObj = mysqli_fetch_object($existingSr);
        if ($existingSrObj && isset($existingSrObj->id)) {
          $srId = (int) $existingSrObj->id;
        }
      }

      if ($srId <= 0) {
        $sr = R::dispense('stock_return');
        $sr->date = today();
        $sr->salesman_id = $staffId;
        $sr->created_by = uid();
        $sr->stock_collect_id = $returnStockCollectId;
        R::store($sr);
        $srId = (int) $sr->id;
      }

      $existingItemId = 0;
      if ($returnStockCollectItemId > 0) {
        $existingItemRs = select(
          'id',
          'stock_return_item',
          "stock_collect_item_id=$returnStockCollectItemId LIMIT 1"
        );
        if ($existingItemRs) {
          $existingItemObj = mysqli_fetch_object($existingItemRs);
          if ($existingItemObj && isset($existingItemObj->id)) {
            $existingItemId = (int) $existingItemObj->id;
          }
        }
      }

      if ($existingItemId <= 0) {
        $existingItemRs = select(
          'id',
          'stock_return_item',
          "stock_return_id=$srId AND product_id=$returnProductId AND product_variance_id=$returnVarianceId LIMIT 1"
        );
        if ($existingItemRs) {
          $existingItemObj = mysqli_fetch_object($existingItemRs);
          if ($existingItemObj && isset($existingItemObj->id)) {
            $existingItemId = (int) $existingItemObj->id;
          }
        }
      }

      if ($existingItemId > 0) {
        update(
          'stock_return_item',
          "quantity=$returnQty, name='" . mysqli_real_escape_string($c, $returnProductName) . "', description='" . mysqli_real_escape_string($c, $returnCollectDate) . "'",
          "id=$existingItemId"
        );
      } else {
        $sri = R::dispense('stock_return_item');
        $sri->stock_return_id = $srId;
        $sri->product_id = $returnProductId;
        $sri->product_variance_id = $returnVarianceId;
        $sri->invoice_item_id = 0;
        $sri->name = $returnProductName;
        $sri->description = $returnCollectDate;
        $sri->quantity = $returnQty;
        $sri->price = 0;
        $sri->cost = 0;
        $sri->created_by = uid();
        if ($returnStockCollectItemId > 0) {
          $sri->stock_collect_item_id = $returnStockCollectItemId;
        }
        R::store($sri);
      }
    }

    $redirectStartDate = (isset($post->start_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $post->start_date))
      ? $post->start_date
      : $startDate;
    $redirectEndDate = (isset($post->end_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $post->end_date))
      ? $post->end_date
      : $endDate;
    redir("?h=$staffId&start_date=$redirectStartDate&end_date=$redirectEndDate");
    exit;
  }

  $collectRows = null;
  if ($staffNameSql !== '') {
    $collectSql = "SELECT 
        sc.id stock_collect_id, i.id invoice_id,
        MAX(sci.id) stock_collect_item_id,
        DATE(IFNULL(sc.date, sc.created_at)) collect_date,
        sci.product_id,
        sci.product_variance_id,
        IFNULL(sci.name, p.name) product_name,
        pv.particulars product_particulars,
        c.code customer_code,
        c.id customer_id,
        SUM(IFNULL(sci.quantity,0)) collected_qty,
        0 as delivered_qty,
        (
          SELECT SUM(IFNULL(sri.quantity,0))
          FROM stock_return sr
          INNER JOIN stock_return_item sri ON sri.stock_return_id=sr.id
          WHERE sr.stock_collect_id=sc.id
            AND sri.product_id=sci.product_id
            AND sri.product_variance_id=sci.product_variance_id
            AND DATE(sr.created_at) BETWEEN '$startDate' AND '$endDate'
        ) returned_qty
      FROM stock_collect sc
      INNER JOIN stock_collect_item sci ON sci.stock_collect_id=sc.id
      LEFT JOIN product p ON p.id=sci.product_id
      LEFT JOIN product_variance pv ON pv.id=sci.product_variance_id
      INNER JOIN invoice i ON i.id = (SELECT ii.invoice_id FROM invoice_item ii WHERE ii.id = sci.invoice_item_id LIMIT 1)
      LEFT JOIN customer c ON c.id = i.customer_id
      WHERE sc.delivery_staff='$staffNameSql' AND (DATE(sc.created_at) BETWEEN '$startDate' AND '$endDate') AND sc.created_at > '2026-03-26'
      GROUP BY sc.id, DATE(IFNULL(sc.date, sc.created_at)), sci.product_id, sci.product_variance_id, IFNULL(sci.name, p.name), pv.particulars, c.code, c.id
      ORDER BY DATE(IFNULL(sc.date, sc.created_at)) ASC, IFNULL(sci.name, p.name) ASC";
    $collectRows = select($collectSql);
    
    // Get delivery data separately to avoid multiplication
    if ($collectRows && mysqli_num_rows($collectRows) > 0) {
      $deliveryData = [];
      $deliverySql = "SELECT 
        sci.stock_collect_id,
        ii.product_id,
        ii.product_variance_id,
        i.customer_id,
        iid.quantity as delivered_qty
        FROM invoice_item_delviery iid
        INNER JOIN invoice_item ii ON ii.id=iid.invoice_item_id
        INNER JOIN stock_collect_item sci ON sci.invoice_item_id=ii.id
        INNER JOIN stock_collect sc ON sc.id=sci.stock_collect_id
        INNER JOIN invoice i ON i.id=ii.invoice_id
        WHERE sc.delivery_staff='$staffNameSql' AND DATE(sc.created_at) BETWEEN '$startDate' AND '$endDate'";
            $deliverySql = "SELECT 
        sci.stock_collect_id,
        ii.product_id,
        ii.product_variance_id,
        i.customer_id,
        iid.quantity as delivered_qty
        FROM invoice_item_delivery iid
        INNER JOIN invoice_item ii ON ii.id=iid.invoice_item_id
        INNER JOIN stock_collect_item sci ON sci.invoice_item_id=ii.id
        INNER JOIN stock_collect sc ON sc.id=sci.stock_collect_id
        INNER JOIN invoice i ON i.id=ii.invoice_id
        WHERE iid.delivery_staff='$staffNameSql' AND DATE(iid.delivered_at) BETWEEN '$startDate' AND '$endDate'";
      // print $deliverySql;
      $deliveryRows = select($deliverySql);
      if ($deliveryRows) {
        while ($dRow = mysqli_fetch_object($deliveryRows)) {
          $key = $dRow->stock_collect_id . '_' . $dRow->product_id . '_' . $dRow->product_variance_id . '_' . $dRow->customer_id;
          if (!isset($deliveryData[$key])) {
            $deliveryData[$key] = 0;
          }
          $deliveryData[$key] += (float)$dRow->delivered_qty;
        }
      }
    }
  }

  ?>
  <style type="text/css">
    .cnr-main-table thead th,
    .cnr-main-table tbody td {
      text-align: center;
      vertical-align: middle;
    }
    
    .cnr-main-table .date-group-1 {
      background-color: #f8f9fa;
    }
    
    .cnr-main-table .date-group-2 {
      background-color: #e9ecef;
    }
    
    .cnr-main-table .date-group-1 .collect-col {
      background-color: #e8f4f8 !important;
    }
    
    .cnr-main-table .date-group-2 .collect-col {
      background-color: #d1ecf1 !important;
    }
    
    .cnr-main-table .date-group-1 .delivery-col {
      background-color: #d4edda !important;
    }
    
    .cnr-main-table .date-group-2 .delivery-col {
      background-color: #c3e6cb !important;
    }
    
    .cnr-main-table .date-group-1 .return-col {
      background-color: #fff3cd !important;
    }
    
    .cnr-main-table .date-group-2 .return-col {
      background-color: #ffeaa7 !important;
    }
  </style>

  <div class="d-flex justify-content-center align-items-center" style="padding: 10px 50px 0;">
    <form method="get" class="d-flex align-items-center gap-2">
      <input type="hidden" name="h" value="<?= (int) $staffId ?>">
      <label class="mb-0" for="start_date"><strong>From</strong></label>
      <input type="date" id="start_date" name="start_date" class="form-control"
        value="<?= htmlspecialchars($startDate) ?>">
      <label class="mb-0" for="end_date"><strong>To</strong></label>
      <input type="date" id="end_date" name="end_date" class="form-control"
        value="<?= htmlspecialchars($endDate) ?>">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="?h=<?= (int) $staffId ?>" class="btn btn-light">Reset</a>
    </form>
  </div>

  <div class="table-responsive" style="padding: 20px 50px;">
    <table class="table table-hover table-bordered cnr-main-table">
      <thead>
        <th>No</th>
        <th>Date</th>
        <th>Code</th>
        <th class="text-start">Product</th>
        <th style="background-color: #f2f2f2;">Collect</th>
        <th style="background-color: #e6f2f2;">Delivery</th>
        <th style="background-color: #f2f2e6;">Return</th>
        <th>Balance</th>
      </thead>
      <tbody>
        <?php if (!$staffNameSql): ?>
          <tr>
            <td colspan="8" class="text-center">No staff selected.</td>
          </tr>
        <?php elseif (!$collectRows || mysqli_num_rows($collectRows) === 0): ?>
          <tr>
            <td colspan="8" class="text-center">No collection found for <?= htmlspecialchars($staffName) ?>.</td>
          </tr>
        <?php else: ?>
          <?php 
          $no = 0;
          $visibleRows = 0;
          $currentDate = '';
          $dateGroupIndex = 1;
          
          while ($row = mysqli_fetch_object($collectRows)):
            if ($row->collect_date < $startDate || $row->collect_date > $endDate) {
              continue;
            }
            
            // Check if date has changed
            if ($currentDate !== $row->collect_date) {
              $currentDate = $row->collect_date;
              $dateGroupIndex = $dateGroupIndex === 1 ? 2 : 1; // Alternate between 1 and 2
            }
            
            $no++;
            $visibleRows++; 
            $rowClass = "date-group-$dateGroupIndex";
            ?>
            <tr class="<?= $rowClass ?>">
              <td
                title='<?= htmlspecialchars($row->stock_collect_id) ?> | <?= htmlspecialchars($row->stock_collect_item_id) ?>'
                style="cursor: pointer; color: #007bff; text-decoration: underline;"
                onclick="window.location.href='/store/invoice/edit/<?= (int) $row->invoice_id ?>'">
                <?= $no ?>
              </td>
              <td><?= df($row->collect_date) ?></td>
              <td title='<?= htmlspecialchars($row->invoice_id) ?>'><a
                  href="/store/customer/details/<?= (int) $row->customer_id ?>"><?= htmlspecialchars($row->customer_code) ?></a>
              </td>
              <td class="text-start"><?= htmlspecialchars($row->product_particulars) ?></td>
              <td class="collect-col"><?= nf2((float) $row->collected_qty) ?></td>
              <td class="delivery-col">
                <?php
                $deliveryKey = $row->stock_collect_id . '_' . $row->product_id . '_' . $row->product_variance_id . '_' . $row->customer_id;
                $deliveredQty = isset($deliveryData[$deliveryKey]) ? $deliveryData[$deliveryKey] : 0;
                echo $deliveredQty > 0 ? nf2($deliveredQty) : '';
                ?>
              </td>
              <td class="cnr-return-cell return-col" style="cursor:pointer;"
                data-stock-collect-id="<?= (int) $row->stock_collect_id ?>"
                data-stock-collect-item-id="<?= (int) $row->stock_collect_item_id ?>"
                data-collect-date="<?= htmlspecialchars($row->collect_date) ?>"
                data-product-id="<?= (int) $row->product_id ?>"
                data-product-variance-id="<?= (int) $row->product_variance_id ?>"
                data-product-name="<?= htmlspecialchars($row->product_name) ?>" data-max="<?= (float) $row->collected_qty ?>">
                <?php
                $returnQty = (float) (isset($row->returned_qty) ? $row->returned_qty : 0);
                echo $returnQty > 0 ? nf($returnQty) : '';
                ?>
              </td>
              <?php
              $collectedQty = (float) $row->collected_qty;
              $deliveryKey = $row->stock_collect_id . '_' . $row->product_id . '_' . $row->product_variance_id . '_' . $row->customer_id;
              $deliveredQty = isset($deliveryData[$deliveryKey]) ? $deliveryData[$deliveryKey] : 0;
              $returnedQty = (float) (isset($row->returned_qty) ? $row->returned_qty : 0);
              $balanceQty = $collectedQty - $deliveredQty - $returnedQty;
              ?>
              <td><?= $balanceQty != 0 ? nf2($balanceQty) : '' ?></td>
            </tr>
          <?php endwhile; ?>
          <?php if ($visibleRows === 0): ?>
            <tr>
              <td colspan="8" class="text-center">No collection found for <?= htmlspecialchars($staffName) ?> between
                <?= htmlspecialchars(df($startDate)) ?> and <?= htmlspecialchars(df($endDate)) ?>.
              </td>
            </tr>
          <?php endif; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="modal fade" id="cnrReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" autocomplete="off">
          <input type="hidden" name="stock_collect_id" id="cnr_stock_collect_id" value="">
          <input type="hidden" name="stock_collect_item_id" id="cnr_stock_collect_item_id" value="">
          <input type="hidden" name="collect_date" id="cnr_collect_date" value="">
          <input type="hidden" name="start_date" id="cnr_start_date" value="<?= htmlspecialchars($startDate) ?>">
          <input type="hidden" name="end_date" id="cnr_end_date" value="<?= htmlspecialchars($endDate) ?>">
          <input type="hidden" name="product_id" id="cnr_product_id" value="">
          <input type="hidden" name="product_variance_id" id="cnr_product_variance_id" value="">
          <input type="hidden" name="product_name" id="cnr_product_name" value="">

          <div class="modal-header">
            <h5 class="modal-title">Return Quantity</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-2">
              <div><strong id="cnr_return_product_label"></strong></div>
              <div>Date: <span id="cnr_return_date_label"></span></div>
              <div>Collected: <span id="cnr_return_collected_label"></span></div>
            </div>

            <div class="mb-2">
              <label class="form-label" for="cnr_return_qty">Quantity</label>
              <input type="number" step="1" min="0" class="form-control" name="return_qty" id="cnr_return_qty" value="">
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" name="save_return" value="1">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('cnrReturnModal');
      if (!modalEl) return;
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      document.addEventListener('click', function (e) {
        var cell = e.target.closest('.cnr-return-cell');
        if (!cell) return;
        e.preventDefault();

        var stockCollectId = cell.getAttribute('data-stock-collect-id') || '';
        var stockCollectItemId = cell.getAttribute('data-stock-collect-item-id') || '';
        var collectDate = cell.getAttribute('data-collect-date') || '';
        var productId = cell.getAttribute('data-product-id') || '';
        var productVarianceId = cell.getAttribute('data-product-variance-id') || '';
        var productName = cell.getAttribute('data-product-name') || '';
        var max = cell.getAttribute('data-max') || '';

        document.getElementById('cnr_stock_collect_id').value = stockCollectId;
        document.getElementById('cnr_stock_collect_item_id').value = stockCollectItemId;
        document.getElementById('cnr_collect_date').value = collectDate;
        document.getElementById('cnr_product_id').value = productId;
        document.getElementById('cnr_product_variance_id').value = productVarianceId;
        document.getElementById('cnr_product_name').value = productName;

        document.getElementById('cnr_return_product_label').textContent = productName;
        document.getElementById('cnr_return_date_label').textContent = collectDate;
        document.getElementById('cnr_return_collected_label').textContent = max;

        var qtyInput = document.getElementById('cnr_return_qty');
        qtyInput.value = '';
        if (max !== '') {
          qtyInput.setAttribute('max', max);
        } else {
          qtyInput.removeAttribute('max');
        }

        modal.show();
      });
    });
  </script>
  <?php

} else {
  ?>
  <div class="table-responsive" style="padding: 20px 50px;">
    <form method="get" class="d-flex align-items-center gap-2" style="margin-bottom:10px;">
      <label class="mb-0" for="start_date"><strong>From</strong></label>
      <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
      <label class="mb-0" for="end_date"><strong>To</strong></label>
      <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="?" class="btn btn-light">Reset</a>
    </form>
    <table class="table table-hover table-bordered">
      <thead>
        <th width="70">ID</th>
        <th>Name</th>
        <th width="100">Balance</th>
      </thead>
      <tbody>
        <?php while ($obj = mysqli_fetch_object($objs)): ?>
          <?php
          $staffId = (int) $obj->id;
$staffName = isset($obj->name) ? trim((string) $obj->name) : '';
$staffNameSql = $staffName !== '' ? mysqli_real_escape_string($c, $staffName) : '';

$balanceQty = 0;

if ($staffNameSql !== '') {

  // 1) Get collected rows exactly like details view
  $collectSql = "SELECT 
      sc.id AS stock_collect_id,
      sci.product_id,
      sci.product_variance_id,
      c.id AS customer_id,
      SUM(IFNULL(sci.quantity,0)) AS collected_qty,
      (
        SELECT SUM(IFNULL(sri.quantity,0))
        FROM stock_return sr
        INNER JOIN stock_return_item sri ON sri.stock_return_id = sr.id
        WHERE sr.stock_collect_id = sc.id
          AND sri.product_id = sci.product_id
          AND sri.product_variance_id = sci.product_variance_id
          AND DATE(sr.created_at) BETWEEN '$startDate' AND '$endDate'
      ) AS returned_qty
    FROM stock_collect sc
    INNER JOIN stock_collect_item sci ON sci.stock_collect_id = sc.id
    INNER JOIN invoice i ON i.id = (
      SELECT ii.invoice_id
      FROM invoice_item ii
      WHERE ii.id = sci.invoice_item_id
      LIMIT 1
    )
    LEFT JOIN customer c ON c.id = i.customer_id
    WHERE sc.delivery_staff = '$staffNameSql'
      AND DATE(sc.created_at) BETWEEN '$startDate' AND '$endDate'
      AND sc.created_at > '2026-03-26'
    GROUP BY
      sc.id,
      sci.product_id,
      sci.product_variance_id,
      c.id";

  $collectRows = select($collectSql);

  // 2) Get delivery data exactly like details view
  $deliveryData = [];
  $deliverySql = "SELECT 
      sci.stock_collect_id,
      ii.product_id,
      ii.product_variance_id,
      i.customer_id,
      iid.quantity AS delivered_qty
    FROM invoice_item_delivery iid
    INNER JOIN invoice_item ii ON ii.id = iid.invoice_item_id
    INNER JOIN stock_collect_item sci ON sci.invoice_item_id = ii.id
    INNER JOIN stock_collect sc ON sc.id = sci.stock_collect_id
    INNER JOIN invoice i ON i.id = ii.invoice_id
    WHERE iid.delivery_staff = '$staffNameSql'
      AND DATE(iid.delivered_at) BETWEEN '$startDate' AND '$endDate'";

  $deliveryRows = select($deliverySql);
  if ($deliveryRows) {
    while ($dRow = mysqli_fetch_object($deliveryRows)) {
      $key = $dRow->stock_collect_id . '_' . $dRow->product_id . '_' . $dRow->product_variance_id . '_' . $dRow->customer_id;
      if (!isset($deliveryData[$key])) {
        $deliveryData[$key] = 0;
      }
      $deliveryData[$key] += (float) $dRow->delivered_qty;
    }
  }

  // 3) Sum balance exactly like details rows
  if ($collectRows) {
    while ($row = mysqli_fetch_object($collectRows)) {
      $deliveryKey = $row->stock_collect_id . '_' . $row->product_id . '_' . $row->product_variance_id . '_' . $row->customer_id;
      $collectedQty = (float) $row->collected_qty;
      $deliveredQty = isset($deliveryData[$deliveryKey]) ? (float) $deliveryData[$deliveryKey] : 0;
      $returnedQty = (float) ($row->returned_qty + 0);

      $balanceQty += ($collectedQty - $deliveredQty - $returnedQty);
    }
  }
}
          ?>
          <tr>
            <td><?= $obj->id ?></td>
            <td><a href="?h=<?= $obj->id ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>"><?= $obj->name ?></a></td>
            <td class='text-right'><?= nf2($balanceQty, 0) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php
}
?>