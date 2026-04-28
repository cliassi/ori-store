<?php
$objs = select('distinct id, name, incentive', 'staff_salary', "category='Delivery Staff'");

// Handle date range filtering from dropdowns
$startDay = isset($get->start_day) ? str_pad((int)$get->start_day, 2, '0', STR_PAD_LEFT) : date('d');
$startMonth = isset($get->start_month) ? str_pad((int)$get->start_month, 2, '0', STR_PAD_LEFT) : date('m');
$startYear = isset($get->start_year) ? (int)$get->start_year : date('Y');
$startDate = "$startYear-$startMonth-$startDay";

$endDay = isset($get->end_day) ? str_pad((int)$get->end_day, 2, '0', STR_PAD_LEFT) : date('d');
$endMonth = isset($get->end_month) ? str_pad((int)$get->end_month, 2, '0', STR_PAD_LEFT) : date('m');
$endYear = isset($get->end_year) ? (int)$get->end_year : date('Y');
$endDate = "$endYear-$endMonth-$endDay";

// Ensure start date is not after end date
if ($startDate > $endDate) {
  $temp = $startDate;
  $startDate = $endDate;
  $endDate = $temp;
}

// Shift both dates by -1/+1 day (Prev/Next)
if (isset($get->shift)) {
  $shift = (string)$get->shift;
  if ($shift === 'prev' || $shift === 'next') {
    $delta = $shift === 'prev' ? '-1 day' : '+1 day';
    $startDate = date('Y-m-d', strtotime($delta, strtotime($startDate)));
    $endDate = date('Y-m-d', strtotime($delta, strtotime($endDate)));
    if ($startDate > $endDate) {
      $temp = $startDate;
      $startDate = $endDate;
      $endDate = $temp;
    }
  }
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
        sci.stock_collect_id,
        sci.id AS stock_collect_item_id,
        i.id AS invoice_id,
        DATE(IFNULL(sc.date, sc.created_at)) AS collect_date,
        ii.product_id,
        ii.product_variance_id,
        IFNULL(sci.name, p.name) AS product_name,
        pv.particulars AS product_particulars,
        c.code AS customer_code,
        c.id AS customer_id,
        sci.quantity AS collected_qty,
        0 as delivered_qty,
        0 AS returned_qty
      FROM stock_collect sc
      INNER JOIN stock_collect_item sci ON sci.stock_collect_id=sc.id
      INNER JOIN invoice_item ii ON ii.id=sci.invoice_item_id
      LEFT JOIN product p ON p.id=ii.product_id
      LEFT JOIN product_variance pv ON pv.id=ii.product_variance_id
      INNER JOIN invoice i ON i.id=ii.invoice_id
      LEFT JOIN customer c ON c.id=i.customer_id
      WHERE sc.delivery_staff='$staffNameSql' AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')
      ORDER BY DATE(IFNULL(sc.date, sc.created_at)) ASC, IFNULL(sci.name, p.name) ASC";
    $collectRows = select($collectSql);
    // echo "<div style='background:#fff3cd; padding:10px; margin:10px 0; border:1px solid #ffc107; border-radius:4px;'><strong>DEBUG - Collect Query:</strong> " . htmlspecialchars($collectSql) . " <br><strong>Rows Found:</strong> " . ($collectRows ? mysqli_num_rows($collectRows) : 0) . "</div>";
    
    // Build collect data array from query results
    $collectData = [];
    if ($collectRows && mysqli_num_rows($collectRows) > 0) {
      mysqli_data_seek($collectRows, 0);
      while ($cRow = mysqli_fetch_object($collectRows)) {
        $cKey = $cRow->stock_collect_id . '_' . $cRow->product_id . '_' . $cRow->product_variance_id . '_' . $cRow->customer_id;
        $collectData[$cKey] = $cRow;
      }
      mysqli_data_seek($collectRows, 0);
    }
    
    // Get delivery data separately to avoid multiplication
    $deliveryData = [];
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
      // echo "<div style='background:#fff3cd; padding:10px; margin:10px 0; border:1px solid #ffc107; border-radius:4px;'><strong>DEBUG - Delivery Query:</strong> " . htmlspecialchars($deliverySql) . " <br><strong>Rows Found:</strong> " . ($deliveryRows ? mysqli_num_rows($deliveryRows) : 0) . "</div>";
      if ($deliveryRows) {
        while ($dRow = mysqli_fetch_object($deliveryRows)) {
          $key = $dRow->stock_collect_id . '_' . $dRow->product_id . '_' . $dRow->product_variance_id . '_' . $dRow->customer_id;
          // Set value directly instead of accumulating to avoid duplicates
          $deliveryData[$key] = (float)$dRow->delivered_qty;
        }
        // echo "<div style='background:#d1ecf1; padding:10px; margin:10px 0; border:1px solid #0c5460; border-radius:4px;'><strong>DEBUG - Delivery Data Array:</strong> <pre>" . htmlspecialchars(print_r($deliveryData, true)) . "</pre></div>";
      }
    }
  }

  ?>
  <style type="text/css">
    h1{
      text-align: center;
    }
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

  <?php
  // Calculate previous month (first day)
  $currentMonthStart = strtotime($startYear . '-' . $startMonth . '-01');
  $prevMonthStart = strtotime('-1 month', $currentMonthStart);
  $prevMonthEnd = strtotime('last day of ' . date('Y-m-d', $prevMonthStart));
  
  // Calculate next month (first day to last day)
  $nextMonthStart = strtotime('+1 month', $currentMonthStart);
  $nextMonthEnd = strtotime('last day of ' . date('Y-m-d', $nextMonthStart));
  ?>

  <div style="width: 100%; padding: 10px 0;">
    <div class="d-flex justify-content-center align-items-center" style="padding: 10px 50px 0;">
      <form method="get" class="d-flex align-items-center gap-2" style="width: 100%; justify-content: space-between;">
        <input type="hidden" name="h" value="<?= (int) $staffId ?>">
        
        <!-- Previous Button -->
        <a href="?h=<?= (int) $staffId ?>&start_day=01&start_month=<?= date('m', $prevMonthStart) ?>&start_year=<?= date('Y', $prevMonthStart) ?>&end_day=<?= date('d', $prevMonthEnd) ?>&end_month=<?= date('m', $prevMonthEnd) ?>&end_year=<?= date('Y', $prevMonthEnd) ?>" class="btn btn-outline-secondary" style="white-space: nowrap; padding: 8px 16px; font-size: 14px; color: #333; border-color: #666;">← Prev</a>
        
        <div class="d-flex align-items-center gap-2">
          <label class="mb-0" style="margin-right: 10px;"><strong>From</strong></label>
          <?php
          $startParts = explode('-', $startDate);
          $startYear = isset($startParts[0]) ? $startParts[0] : date('Y');
          $startMonth = isset($startParts[1]) ? $startParts[1] : date('m');
          $startDay = isset($startParts[2]) ? $startParts[2] : date('d');
          ?>
          <select name="start_day" class="form-select" style="width: 80px;">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>" <?= $startDay == str_pad($d, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
          <select name="start_month" class="form-select" style="width: 120px;">
            <?php 
            $months = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];
            foreach ($months as $m => $name): ?>
              <option value="<?= $m ?>" <?= $startMonth == $m ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
          <select name="start_year" class="form-select" style="width: 100px;">
            <?php for ($y = 2020; $y <= date('Y') + 1; $y++): ?>
              <option value="<?= $y ?>" <?= $startYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          
          <label class="mb-0" style="margin-left: 20px; margin-right: 10px;"><strong>To</strong></label>
          <?php
          $endParts = explode('-', $endDate);
          $endYear = isset($endParts[0]) ? $endParts[0] : date('Y');
          $endMonth = isset($endParts[1]) ? $endParts[1] : date('m');
          $endDay = isset($endParts[2]) ? $endParts[2] : date('d');
          ?>
          <select name="end_day" class="form-select" style="width: 80px;">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>" <?= $endDay == str_pad($d, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
          <select name="end_month" class="form-select" style="width: 120px;">
            <?php foreach ($months as $m => $name): ?>
              <option value="<?= $m ?>" <?= $endMonth == $m ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
          <select name="end_year" class="form-select" style="width: 100px;">
            <?php for ($y = 2020; $y <= date('Y') + 1; $y++): ?>
              <option value="<?= $y ?>" <?= $endYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          
          <button type="submit" class="btn btn-primary" style="margin-left: 20px;">Filter</button>
          <a href="?h=<?= (int) $staffId ?>" class="btn btn-light">Reset</a>
        </div>
        
        <!-- Next Button -->
        <a href="?h=<?= (int) $staffId ?>&start_day=01&start_month=<?= date('m', $nextMonthStart) ?>&start_year=<?= date('Y', $nextMonthStart) ?>&end_day=<?= date('d', $nextMonthEnd) ?>&end_month=<?= date('m', $nextMonthEnd) ?>&end_year=<?= date('Y', $nextMonthEnd) ?>" class="btn btn-outline-secondary" style="white-space: nowrap; padding: 8px 16px; font-size: 14px; color: #333; border-color: #666;">Next →</a>
      </form>
    </div>
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
          $totalCollectQty = 0;
          $totalDeliveryQty = 0;
          $totalReturnQty = 0;
          $totalBalanceQty = 0;
          
          // Get total collect and delivery per day
          $dailyTotals = [];
          if ($collectRows && mysqli_num_rows($collectRows) > 0) {
            mysqli_data_seek($collectRows, 0);
            while ($row = mysqli_fetch_object($collectRows)) {
              $date = $row->collect_date;
              if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = ['collect' => 0, 'delivery' => 0, 'return' => 0];
              }
              $dailyTotals[$date]['collect'] += (float)$row->collected_qty;
              $dailyTotals[$date]['return'] += (float)(isset($row->returned_qty) ? $row->returned_qty : 0);
            }
            
            // Add delivery totals per day
            if ($deliveryRows) {
              mysqli_data_seek($deliveryRows, 0);
              while ($dRow = mysqli_fetch_object($deliveryRows)) {
                $deliveryDate = date('Y-m-d');
                if (!isset($dailyTotals[$deliveryDate])) {
                  $dailyTotals[$deliveryDate] = ['collect' => 0, 'delivery' => 0, 'return' => 0];
                }
                $dailyTotals[$deliveryDate]['delivery'] += (float)$dRow->delivered_qty;
              }
            }
          }
          
          // Reset pointer to display rows
          if ($collectRows) {
            mysqli_data_seek($collectRows, 0);
          }
          
          // Iterate through collectData array instead of result set
          foreach ($collectData as $cKey => $row):
            if ($row->collect_date < $startDate || $row->collect_date > $endDate) {
              continue;
            }
            
            // Skip rows where both collect and delivery are 0
            $collectedQty = (float) $row->collected_qty;
            $deliveryKey = $row->stock_collect_id . '_' . $row->product_id . '_' . $row->product_variance_id . '_' . $row->customer_id;
            $deliveredQty = isset($deliveryData[$deliveryKey]) ? $deliveryData[$deliveryKey] : 0;
            if ($collectedQty == 0 && $deliveredQty == 0) {
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
              
              // Accumulate totals
              $totalCollectQty += $collectedQty;
              $totalDeliveryQty += $deliveredQty;
              $totalReturnQty += $returnedQty;
              ?>
              <td><?= $balanceQty != 0 ? nf2($balanceQty) : '' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($visibleRows === 0): ?>
            <tr>
              <td colspan="8" class="text-center">No collection found for <?= htmlspecialchars($staffName) ?> between
                <?= htmlspecialchars(df($startDate)) ?> and <?= htmlspecialchars(df($endDate)) ?>.
              </td>
            </tr>
          <?php else: ?>
            <?php
            // Get summary totals using SQL SUM()
            // Collect totals
            $summaryCollectQuery = "SELECT SUM(sci.quantity) as total_collected
              FROM stock_collect sc
              INNER JOIN stock_collect_item sci ON sci.stock_collect_id=sc.id
              WHERE sc.delivery_staff='$staffNameSql' AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')";
            $collectResult = select($summaryCollectQuery);
            $collectRow = $collectResult ? mysqli_fetch_object($collectResult) : null;
            $sumCollect = $collectRow ? (float)$collectRow->total_collected : 0;
            
            // Delivery totals
            $deliveryQuery = "SELECT COALESCE(SUM(iid.quantity), 0) as total_delivered
              FROM invoice_item_delviery iid
              WHERE iid.delivery_staff='$staffNameSql' AND iid.delivered_at >= '$startDate 00:00:00' AND iid.delivered_at <= '$endDate 23:59:59'";
            $deliveryResult = select($deliveryQuery);
            $deliveryRow = $deliveryResult ? mysqli_fetch_object($deliveryResult) : null;
            $sumDelivery = $deliveryRow ? (float)$deliveryRow->total_delivered : 0;
            
            // Return totals
            $returnQuery = "SELECT COALESCE(SUM(sri.quantity), 0) as total_returned
              FROM stock_return_item sri
              INNER JOIN stock_collect_item sci ON sci.id=sri.stock_collect_item_id
              INNER JOIN stock_collect sc ON sc.id=sci.stock_collect_id
              WHERE sc.delivery_staff='$staffNameSql' AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')";
            $returnResult = select($returnQuery);
            $returnRow = $returnResult ? mysqli_fetch_object($returnResult) : null;
            $sumReturn = $returnRow ? (float)$returnRow->total_returned : 0;
            
            $sumBalance = $sumCollect - $sumDelivery - $sumReturn;
            ?>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
              <td colspan="4">TOTAL</td>
              <td style="background-color: #f2f2f2;"><?= nf2($sumCollect) ?></td>
              <td style="background-color: #e6f2f2;"><?= nf2($sumDelivery) ?></td>
              <td style="background-color: #f2f2e6;"><?= nf2($sumReturn) ?></td>
              <td><?= nf2($sumBalance) ?></td>
            </tr>
          <?php endif; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php
  // Print debug info after table (only if variables are defined)
  // if (isset($summaryCollectQuery) && isset($collectRow)) {
  //   echo "<div style='background:#fff3cd; padding:10px; margin:10px 50px; border:1px solid #ffc107; border-radius:4px;'><strong>DEBUG - Summary Collect Query:</strong><br><pre>" . htmlspecialchars($summaryCollectQuery) . "</pre><strong>Staff Name SQL:</strong> " . htmlspecialchars($staffNameSql) . "<br><strong>Date Range:</strong> $startDate to $endDate</div>";
  //   echo "<div style='background:#d1ecf1; padding:10px; margin:10px 50px; border:1px solid #0c5460; border-radius:4px;'><strong>DEBUG - Summary Collect Result:</strong> " . ($collectRow ? $collectRow->total_collected : 'NULL') . "</div>";
  // }
  ?>

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
    <form method="get" class="d-flex align-items-center justify-content-center gap-2" style="margin-bottom:10px; flex-wrap: wrap; width:100%;">
      <button type="submit" name="shift" value="prev" class="btn btn-outline-secondary" style="align-self: flex-end; margin-bottom: 0;">Prev</button>
      <div style='text-align:center'>
        <label class="mb-2" style="display: block;"><strong>From</strong></label>
        <div class="d-flex gap-2">
          <?php
          $startParts = explode('-', $startDate);
          $startYear = isset($startParts[0]) ? $startParts[0] : date('Y');
          $startMonth = isset($startParts[1]) ? $startParts[1] : date('m');
          $startDay = isset($startParts[2]) ? $startParts[2] : date('d');
          ?>
          <select name="start_day" class="form-select" style="width: 80px;">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>" <?= $startDay == str_pad($d, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
          <select name="start_month" class="form-select" style="width: 120px;">
            <?php 
            $months = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];
            foreach ($months as $m => $name): ?>
              <option value="<?= $m ?>" <?= $startMonth == $m ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
          <select name="start_year" class="form-select" style="width: 100px;">
            <?php for ($y = 2020; $y <= date('Y') + 1; $y++): ?>
              <option value="<?= $y ?>" <?= $startYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      
      <div>
        <label class="mb-2" style="display: block;"><strong>To</strong></label>
        <div class="d-flex gap-2">
          <?php
          $endParts = explode('-', $endDate);
          $endYear = isset($endParts[0]) ? $endParts[0] : date('Y');
          $endMonth = isset($endParts[1]) ? $endParts[1] : date('m');
          $endDay = isset($endParts[2]) ? $endParts[2] : date('d');
          ?>
          <select name="end_day" class="form-select" style="width: 80px;">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>" <?= $endDay == str_pad($d, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
          <select name="end_month" class="form-select" style="width: 120px;">
            <?php foreach ($months as $m => $name): ?>
              <option value="<?= $m ?>" <?= $endMonth == $m ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
          <select name="end_year" class="form-select" style="width: 100px;">
            <?php for ($y = 2020; $y <= date('Y') + 1; $y++): ?>
              <option value="<?= $y ?>" <?= $endYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      
      <button type="submit" name="shift" value="next" class="btn btn-outline-secondary" style="align-self: flex-end; margin-bottom: 0;">Next</button>
    </form>

    <script>
      (function () {
        var form = document.querySelector('form[method="get"].d-flex');
        if (!form) return;

        var selects = form.querySelectorAll('select[name^="start_"] , select[name^="end_"]');
        selects.forEach(function (sel) {
          sel.addEventListener('change', function () {
            form.submit();
          });
        });
      })();
    </script>
    <table class="table table-hover table-bordered">
      <thead>
        <th width="70">ID</th>
        <th>Name</th>
        <th width="100">Balance</th>
      </thead>
      <tbody>
        <?php 
        $summaryTotalBalance = 0;
        while ($obj = mysqli_fetch_object($objs)): ?>
          <?php
          $staffId = (int) $obj->id;
$staffName = isset($obj->name) ? trim((string) $obj->name) : '';
$staffNameSql = $staffName !== '' ? mysqli_real_escape_string($c, $staffName) : '';

$balanceQty = 0;

if ($staffNameSql !== '') {

  // 1) Get total collected quantity for the staff
  $collectTotalSql = "SELECT SUM(IFNULL(sci.quantity, 0)) AS total_collected
    FROM stock_collect sc
    INNER JOIN stock_collect_item sci ON sci.stock_collect_id = sc.id
    WHERE sc.delivery_staff = '$staffNameSql'
      AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')";
  
  // Debug: Print query for first staff member
  if ($staffId == 68) {
    // echo "<div style='background:#fff3cd; padding:10px; margin:10px 0; border:1px solid #ffc107; border-radius:4px;'><strong>DEBUG - Summary Collect Query for Abadul:</strong><br><pre>" . htmlspecialchars($collectTotalSql) . "</pre></div>";
  }
  
  $collectTotalResult = select($collectTotalSql);
  $totalCollected = 0;
  if ($collectTotalResult) {
    $collectRow = mysqli_fetch_object($collectTotalResult);
    $totalCollected = (float)($collectRow ? $collectRow->total_collected : 0);
    if ($staffId == 68) {
      // echo "<div style='background:#d1ecf1; padding:10px; margin:10px 0; border:1px solid #0c5460; border-radius:4px;'><strong>DEBUG - Collect Result:</strong> " . $totalCollected . "</div>";
    }
  }

  // 2) Get total delivered quantity for the staff
  $deliveryTotalSql = "SELECT SUM(IFNULL(iid.quantity, 0)) AS total_delivered
    FROM invoice_item_delviery iid
    WHERE iid.delivery_staff = '$staffNameSql'
      AND iid.delivered_at >= '$startDate 00:00:00' AND iid.delivered_at <= '$endDate 23:59:59'";
  
  $deliveryTotalResult = select($deliveryTotalSql);
  $totalDelivered = 0;
  if ($deliveryTotalResult) {
    $deliveryRow = mysqli_fetch_object($deliveryTotalResult);
    $totalDelivered = (float)($deliveryRow ? $deliveryRow->total_delivered : 0);
  }

  // 3) Get total returned quantity for the staff
  $returnTotalSql = "SELECT SUM(IFNULL(sri.quantity, 0)) AS total_returned
    FROM stock_return_item sri
    INNER JOIN stock_collect_item sci ON sci.id = sri.stock_collect_item_id
    INNER JOIN stock_collect sc ON sc.id = sci.stock_collect_id
    WHERE sc.delivery_staff = '$staffNameSql'
      AND (sc.created_at >= '$startDate 00:00:00' AND sc.created_at <= '$endDate 23:59:59')";
  
  $returnTotalResult = select($returnTotalSql);
  $totalReturned = 0;
  if ($returnTotalResult) {
    $returnRow = mysqli_fetch_object($returnTotalResult);
    $totalReturned = (float)($returnRow ? $returnRow->total_returned : 0);
  }

  // 4) Calculate balance
  $balanceQty = $totalCollected - $totalDelivered - $totalReturned;
  $summaryTotalBalance += $balanceQty;
}
          ?>
          <tr>
            <td><?= $obj->id ?></td>
            <td><a href="?h=<?= $obj->id ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>"><?= $obj->name ?></a></td>
            <td class='text-right'><?= nf2($balanceQty, 0) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
      <tfoot>
        <tr style="font-weight: bold; background-color: #f0f0f0;">
          <td colspan="2">TOTAL</td>
          <td class='text-right'><?= nf2($summaryTotalBalance, 0) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php
}
?>