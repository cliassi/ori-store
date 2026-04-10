<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>A Pure Water - Collection & Return Report</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#72ccd8',
            primaryDark: '#5fbac6'
          }
        }
      }
    }
  </script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
  <!-- Material Symbols Outlined -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <style>
    body {
      font-family: 'Lato', sans-serif;
    }

    .blob-shape {
      border-bottom-left-radius: 24px;
      border-bottom-right-radius: 24px;
      position: relative;
      overflow: hidden
    }
    td,th{
      border: solid 1px #ccc;
      padding: 1px;
    }
    .icon-green {
      color: #47773f !important
    }

    .material-symbols-outlined {
      color: #47773f !important;
      font-variation-settings: 'FILL' 0, 'wght' 400, 'opsz' 24
    }

    .modal.custom-fallback {
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.6);
      z-index: 9999;
    }

    .modal.custom-fallback.show {
      display: flex;
    }

    .modal.custom-fallback .modal-dialog {
      margin: 0;
      width: 92%;
      max-width: 420px;
    }

    .modal.custom-fallback .modal-content {
      border-radius: 8px;
      overflow: hidden;
      background: #ffffff;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .modal.custom-fallback .modal-header,
    .modal.custom-fallback .modal-body,
    .modal.custom-fallback .modal-footer {
      background: #ffffff;
    }

    .modal.custom-fallback .modal-body {
      padding: 16px;
    }

    body.modal-open {
      overflow: hidden;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

  <?php
  $objs = select('distinct id, name, incentive', 'staff_salary', "category='Delivery Staff'");
  
  // Handle date range filtering
  $startDate = (isset($get->start_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get->start_date))
    ? $get->start_date
    : date('Y-m-d'); // Default to yesterday
  
  $endDate = (isset($get->end_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get->end_date))
    ? $get->end_date
    : date('Y-m-d'); // Default to today
  
  // Ensure start date is not after end date
  if ($startDate > $endDate) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
  }
  
  $reportDate = (isset($get->report_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get->report_date))
    ? $get->report_date
    : date('Y-m-d');

  $staffId = isset($get->h) ? (int) $get->h : 0;
  $staffRow = $staffId > 0 ? select("id, name", "staff_salary", "id=$staffId") : null;
  $staff = $staffRow ? mysqli_fetch_object($staffRow) : null;
  $staffName = $staff ? trim((string) $staff->name) : '';
  $staffNameSql = $staffName !== '' ? mysqli_real_escape_string($c, $staffName) : '';

  if ($staffId > 0 && isset($post->save_return)) {
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

    $redirectDate = (isset($post->report_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $post->report_date))
      ? $post->report_date
      : $reportDate;
    redir("?h=$staffId&report_date=$redirectDate");
    exit;
  }

  $collectRows = null;
  $deliveryData = [];
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
        c.company customer_company,
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
      INNER JOIN invoice_item ii ON ii.id=sci.invoice_item_id
      LEFT JOIN product p ON p.id=ii.product_id
      LEFT JOIN product_variance pv ON pv.id=ii.product_variance_id
      INNER JOIN invoice i ON i.id=ii.invoice_id
      LEFT JOIN customer c ON c.id=i.customer_id
      WHERE sc.delivery_staff='$staffNameSql' AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')
      GROUP BY sc.id, DATE(IFNULL(sc.date, sc.created_at)), sci.product_id, sci.product_variance_id, IFNULL(sci.name, p.name), pv.particulars, c.code, c.id
      ORDER BY DATE(IFNULL(sc.date, sc.created_at)) ASC, IFNULL(sci.name, p.name) ASC";
    $collectRows = select($collectSql);
    
    // Initialize delivery data array
    $deliveryData = [];
    
    // Get delivery data separately to avoid multiplication
    if ($collectRows && mysqli_num_rows($collectRows) > 0) {
      $deliverySql = "SELECT 
        sci.stock_collect_id,
        ii.product_id,
        ii.product_variance_id,
        i.customer_id,
        iid.quantity as delivered_qty
        FROM invoice_item_delviery iid
        INNER JOIN invoice_item ii ON ii.id=iid.invoice_item_id
        INNER JOIN invoice i ON i.id=ii.invoice_id
        INNER JOIN stock_collect_item sci ON sci.invoice_item_id=ii.id
        WHERE iid.delivery_staff='$staffNameSql' AND iid.delivered_at >= '$startDate 00:00:00' AND iid.delivered_at <= '$endDate 23:59:59'
        ORDER BY sci.stock_collect_id, ii.product_id, ii.product_variance_id, i.customer_id";
      
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

  <!-- Mobile Header -->
  <div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
      <h2 class="text-xl font-bold text-center mb-2">CNR Report</h2>
      <?php if ($staffId > 0 && $staffName !== ''): ?>
        <div class="text-center text-sm opacity-95">
          <div class="font-semibold"><?php echo htmlspecialchars($staffName); ?></div>
          <div class="opacity-90"><?php echo htmlspecialchars(df($reportDate)); ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($staffId <= 0): ?>
    <div class="max-w-sm mx-auto px-4 -mt-6">
      <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Select Delivery Staff</h3>
        <div class="grid grid-cols-3 gap-2 text-xs text-gray-500 mb-2">
          <div class="font-semibold">ID</div>
          <div class="font-semibold">Name</div>
          <div class="font-semibold text-right">Balance</div>
        </div>

        <div class="space-y-2">
          <?php while ($obj = mysqli_fetch_object($objs)): ?>
            <?php
            $staffIdRow = (int) $obj->id;
            $staffNameRow = isset($obj->name) ? trim((string) $obj->name) : '';
            $staffNameSqlRow = $staffNameRow !== '' ? mysqli_real_escape_string($c, $staffNameRow) : '';

            $balanceQty = 0;
            if ($staffNameSqlRow !== '') {
              $sumSql = "SELECT
                    (SELECT IFNULL(SUM(sci.quantity),0)
                     FROM stock_collect sc
                     INNER JOIN stock_collect_item sci ON sci.stock_collect_id=sc.id
                     WHERE sc.delivery_staff='$staffNameSqlRow' AND DATE(sc.created_at) = CURDATE()) collected_qty,
                    (SELECT IFNULL(SUM(delivery_qty),0)
                     FROM (
                       SELECT DISTINCT iid.id, iid.quantity as delivery_qty
                       FROM stock_collect sc
                       INNER JOIN stock_collect_item sci ON sci.stock_collect_id=sc.id
                       INNER JOIN invoice_item_delviery iid ON iid.invoice_item_id=sci.invoice_item_id
                       WHERE sc.delivery_staff='$staffNameSqlRow' AND DATE(sc.created_at) = CURDATE()
                     ) unique_deliveries) delivered_qty,
                    (SELECT IFNULL(SUM(sri.quantity),0)
                     FROM stock_return sr
                     INNER JOIN stock_return_item sri ON sri.stock_return_id=sr.id
                     INNER JOIN stock_collect sc ON sc.id=sr.stock_collect_id
                     WHERE sc.delivery_staff='$staffNameSqlRow' AND sr.salesman_id=$staffIdRow AND DATE(sr.created_at) = CURDATE()) returned_qty";
              $sumRs = select($sumSql);
              if ($sumRs) {
                $sumObj = mysqli_fetch_object($sumRs);
                if ($sumObj) {
                  $collectedQty = (float) $sumObj->collected_qty;
                  $deliveredQty = (float) $sumObj->delivered_qty;
                  $returnedQty = (float) $sumObj->returned_qty;
                  $balanceQty = $collectedQty - $deliveredQty - $returnedQty;
                }
              }
            }

            if (!is_numeric($balanceQty)) {
              $balanceQty = 0;
            }
            $balanceLabel = number_format((float) $balanceQty, 0, '.', ',');
            ?>
            <a href="?uid=<?php print UID; ?>&page=cnr&h=<?= (int) $obj->id ?>" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              <div class="grid grid-cols-3 gap-2 items-center">
                <div class="text-sm text-gray-700 font-semibold"><?= (int) $obj->id ?></div>
                <div class="text-sm text-gray-800 font-medium truncate"><?= htmlspecialchars($obj->name) ?></div>
                <div class="text-sm text-gray-800 font-semibold text-right"><?= $balanceLabel ?></div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="max-w-4xl mx-auto px-2 -mt-6">
      <div class="bg-white rounded-2xl shadow-sm p-4 mb-3">
        <form method="get" class="space-y-3">
      <input type="hidden" name="page" value="<?= $get->page ?>">
          <input type="hidden" name="h" value="<?= (int) $staffId ?>">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-semibold" for="start_date">From</label>
              <input type="date" id="start_date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div>
              <label class="text-sm font-semibold" for="end_date">To</label>
              <input type="date" id="end_date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 rounded-md bg-blue-500 text-white font-semibold text-sm">Filter</button>
            <a href="?h=<?= (int) $staffId ?>" class="px-4 py-2 rounded-md border border-gray-300 text-gray-600 font-semibold text-sm text-center">Reset</a>
          </div>
        </form>
      </div>

      <div class="space-y-3">
        <?php if (!$staffNameSql): ?>
          <div class="bg-white rounded-2xl shadow-sm p-4 text-center text-sm text-gray-600">No staff selected.</div>
        <?php elseif (!$collectRows || mysqli_num_rows($collectRows) === 0): ?>
          <div class="bg-white rounded-2xl shadow-sm p-4 text-center text-sm text-gray-600">No collection found for <?= htmlspecialchars($staffName) ?>.</div>
        <?php else: ?>
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left font-semibold text-gray-700">#</th>
                    <th class="text-left font-semibold text-gray-700">DATE</th>
                    <th class="text-left font-semibold text-gray-700">CODE</th>
                    <th class="text-left font-semibold text-gray-700">PRODUCT</th>
                    <th class="text-center font-semibold text-gray-700 bg-blue-50">COL</th>
                    <th class="text-center font-semibold text-gray-700 bg-green-50">DEL</th>
                    <th class="text-center font-semibold text-gray-700 bg-yellow-50">RET</th>
                    <th class="text-center font-semibold text-gray-700">BAL</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 0;
                  $visibleRows = 0;
                  while ($row = mysqli_fetch_object($collectRows)):
                    $no++;
                    $visibleRows++;
                    $collectedQty = (float) $row->collected_qty;
                    $deliveryKey = $row->stock_collect_id . '_' . $row->product_id . '_' . $row->product_variance_id . '_' . $row->customer_id;
                    $deliveredQty = isset($deliveryData[$deliveryKey]) ? $deliveryData[$deliveryKey] : 0;
                    $returnedQty = (float) (isset($row->returned_qty) ? $row->returned_qty : 0);
                    $balanceQty = $collectedQty - $deliveredQty - $returnedQty;
                    
                    // Debug: uncomment to see values
                    // echo "<!-- Debug: C=$collectedQty, D=$deliveredQty, R=$returnedQty, B=$balanceQty -->";
                    
                    $rowClass = $no % 2 == 0 ? 'bg-gray-50' : 'bg-white';
                  ?>
                    <tr class="<?= $rowClass ?> hover:bg-gray-100">
                      <td class="text-blue-600 font-semibold">
                        <a href="#" onclick="openReturnModal(this); return false;"
                          data-stock-collect-id="<?= (int) $row->stock_collect_id ?>"
                          data-stock-collect-item-id="<?= (int) $row->stock_collect_item_id ?>"
                          data-collect-date="<?= htmlspecialchars($row->collect_date) ?>"
                          data-product-id="<?= (int) $row->product_id ?>"
                          data-product-variance-id="<?= (int) $row->product_variance_id ?>"
                          data-product-name="<?= htmlspecialchars($row->product_name) ?>"
                          data-max="<?= (float) $row->collected_qty ?>"
                          data-current="<?= (float) $returnedQty ?>">
                          <?= $no ?>
                        </a>
                      </td>
                      <td class="text-gray-700" ><?= date('d M, Y', strtotime($row->collect_date)) ?></td>
                      <td class="">
                        <a class="text-blue-600 font-medium" href="?page=i&id=<?= (int) $row->customer_id ?>">
                          <?php 
                          $displayCode = $row->customer_code;
                          if (empty($displayCode)) {
                            $displayCode = $row->customer_company ?: 'ID-' . $row->customer_id;
                          }
                          echo htmlspecialchars($displayCode);
                          ?>
                        </a>
                      </td>
                      <td class="text-gray-700"><?= htmlspecialchars($row->product_particulars) ?></td>
                      <td class="text-center bg-blue-50 font-semibold"><?= $collectedQty > 0 ? number_format($collectedQty, 0) : '' ?></td>
                      <td class="text-center bg-green-50 font-semibold"><?= $deliveredQty > 0 ? number_format($deliveredQty, 0) : '' ?></td>
                      <td class="text-center bg-yellow-50 font-semibold"><?= $returnedQty > 0 ? number_format($returnedQty, 0) : '' ?></td>
                      <td class="text-center font-semibold <?= $balanceQty > 0 ? 'text-red-600' : 'text-gray-700' ?>"><?= $balanceQty != 0 ? number_format($balanceQty, 0) : '' ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($visibleRows === 0): ?>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center text-sm text-gray-600">No collection found for <?= htmlspecialchars($staffName) ?> between <?= htmlspecialchars(df($startDate)) ?> and <?= htmlspecialchars(df($endDate)) ?>.</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="modal custom-fallback" id="cnrReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Return Quantity</h5>
          <button type="button" class="btn-close" onclick="closeModal('cnrReturnModal')" aria-label="Close">×</button>
        </div>
        <form method="post">
          <input type="hidden" name="stock_collect_id" id="cnr_stock_collect_id" value="">
          <input type="hidden" name="stock_collect_item_id" id="cnr_stock_collect_item_id" value="">
          <input type="hidden" name="collect_date" id="cnr_collect_date" value="">
          <input type="hidden" name="report_date" id="cnr_report_date" value="<?= htmlspecialchars($reportDate) ?>">
          <input type="hidden" name="product_id" id="cnr_product_id" value="">
          <input type="hidden" name="product_variance_id" id="cnr_product_variance_id" value="">
          <input type="hidden" name="product_name" id="cnr_product_name" value="">
          <div class="modal-body">
            <div class="mb-3 text-sm">
              <div class="font-semibold" id="cnr_return_product_label"></div>
              <div class="text-gray-600">Date: <span id="cnr_return_date_label"></span></div>
              <div class="text-gray-600">Collected: <span id="cnr_return_collected_label"></span></div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700" for="cnr_return_qty">Quantity</label>
              <input type="number" step="1" min="0" name="return_qty" id="cnr_return_qty" value="" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('cnrReturnModal')">Close</button>
            <button type="submit" name="save_return" value="1" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Modal functions
    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.add('show');
      modal.style.display = 'flex';
      document.body.classList.add('modal-open');
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('show');
      modal.style.display = 'none';
      document.body.classList.remove('modal-open');
    }

    function openReturnModal(btn) {
      var stockCollectId = btn.getAttribute('data-stock-collect-id') || '';
      var stockCollectItemId = btn.getAttribute('data-stock-collect-item-id') || '';
      var collectDate = btn.getAttribute('data-collect-date') || '';
      var productId = btn.getAttribute('data-product-id') || '';
      var productVarianceId = btn.getAttribute('data-product-variance-id') || '';
      var productName = btn.getAttribute('data-product-name') || '';
      var max = btn.getAttribute('data-max') || '';
      var current = btn.getAttribute('data-current') || '';

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
      qtyInput.value = current !== '' ? current : '';
      if (max !== '') {
        qtyInput.setAttribute('max', max);
      } else {
        qtyInput.removeAttribute('max');
      }

      openModal('cnrReturnModal');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
      }
    });
  </script>

</body>

</html>