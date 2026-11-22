<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$historyStartDate = '2026-11-01';
$invoices = [];
$historyItems = select("SELECT i.id invoice_id, i.invoice_date,
    c.company customer_name, c.contact customer_contact, c.mobile customer_mobile, c.city customer_city,
    ii.description, ii.quantity, ii.price, ii.delivered, ii.delivered_at,
    ii.assigned_to, ii.assigned_at, ii.collected_at, ss.phone assigned_phone,
    pv.particulars,
    EXISTS (SELECT 1 FROM stock_collect_item sci WHERE sci.invoice_item_id = ii.id LIMIT 1) collected_in_stock
  FROM invoice i
  INNER JOIN invoice_item ii ON ii.invoice_id = i.id
  LEFT JOIN customer c ON c.id = i.customer_id
  LEFT JOIN staff_salary ss ON ss.name = ii.assigned_to AND ss.category = 'Delivery Staff'
  LEFT JOIN product_variance pv ON pv.id = ii.product_variance_id
  WHERE i.customer_id = $uid
    AND i.invoice_date >= '$historyStartDate'
  ORDER BY i.invoice_date DESC, i.id DESC, ii.id DESC");
while ($historyItems && ($row = mysqli_fetch_object($historyItems))) {
  $invoiceId = (int)$row->invoice_id;
  $dateKey = $row->invoice_date ? (string)$row->invoice_date : 'undated';
  $customerName = trim((string)$row->customer_name);
  if ($customerName === '') {
    $customerName = trim((string)$row->customer_contact);
  }
  if ($customerName === '') {
    $customerName = 'Walk-in';
  }
  if (!isset($invoices[$dateKey])) {
    $invoices[$dateKey] = (object) [
      'date' => $row->invoice_date,
      'customer_name' => $customerName,
      'customer_contact' => trim((string)$row->customer_contact),
      'customer_mobile' => trim((string)$row->customer_mobile),
      'customer_city' => trim((string)$row->customer_city),
      'assignees' => [],
      'items' => [],
      'total' => 0,
      'item_count' => 0,
      'assigned_count' => 0,
      'collected_count' => 0,
      'delivered_count' => 0,
      'progress_stage' => 1,
    ];
  }
  $name = trim((string)$row->particulars);
  if ($name === '') {
    $name = trim((string)$row->description);
  }
  $qty = (float)$row->quantity;
  $price = (float)$row->price;
  $total = $qty * $price;
  $assignedTo = trim((string)$row->assigned_to);
  $assignedPhone = trim((string)$row->assigned_phone);
  $isDelivered = $qty > 0 && (float)$row->delivered >= $qty;
  $isCollected = !empty($row->collected_at) || !empty($row->collected_in_stock) || $isDelivered;
  $isAssigned = $assignedTo !== '' || !empty($row->assigned_at) || $isCollected;
  if ($assignedTo !== '') {
    $invoices[$dateKey]->assignees[$assignedTo] = $assignedPhone;
  }
  $invoices[$dateKey]->item_count++;
  $invoices[$dateKey]->assigned_count += $isAssigned ? 1 : 0;
  $invoices[$dateKey]->collected_count += $isCollected ? 1 : 0;
  $invoices[$dateKey]->delivered_count += $isDelivered ? 1 : 0;
  $invoices[$dateKey]->items[] = (object) [
    'invoice_id' => $invoiceId,
    'reference_prefix' => 'INV',
    'name' => $name,
    'qty' => $qty,
    'price' => $price,
    'total' => $total,
  ];
  $invoices[$dateKey]->total += $total;
}
$pendingOrders = select("SELECT co.id order_id, co.invoice_date, co.created_at,
    c.company customer_name, c.contact customer_contact, c.mobile customer_mobile, c.city customer_city,
    coi.description, coi.quantity, coi.price, pv.particulars
  FROM customer_order co
  INNER JOIN customer_order_item coi ON coi.customer_order_id = co.id
  LEFT JOIN customer c ON c.id = co.customer_id
  LEFT JOIN product_variance pv ON pv.id = coi.product_variance_id
  WHERE co.customer_id = $uid
    AND COALESCE(co.invoice_date, DATE(co.created_at)) >= '$historyStartDate'
    AND (co.invoice_id IS NULL OR co.invoice_id = 0)
    AND LOWER(COALESCE(co.status, '')) NOT IN ('approved', 'ordered')
    AND co.deleted_at IS NULL
    AND coi.deleted_at IS NULL
  ORDER BY co.invoice_date DESC, co.id DESC, coi.id DESC");
while ($pendingOrders && ($row = mysqli_fetch_object($pendingOrders))) {
  $orderId = (int)$row->order_id;
  $orderKey = 'pending-' . $orderId;
  $customerName = trim((string)$row->customer_name);
  if ($customerName === '') {
    $customerName = trim((string)$row->customer_contact);
  }
  if ($customerName === '') {
    $customerName = 'Walk-in';
  }
  if (!isset($invoices[$orderKey])) {
    $invoices[$orderKey] = (object) [
      'date' => $row->invoice_date ?: $row->created_at,
      'customer_name' => $customerName,
      'customer_contact' => trim((string)$row->customer_contact),
      'customer_mobile' => trim((string)$row->customer_mobile),
      'customer_city' => trim((string)$row->customer_city),
      'assignees' => [],
      'items' => [],
      'total' => 0,
      'item_count' => 0,
      'assigned_count' => 0,
      'collected_count' => 0,
      'delivered_count' => 0,
      'progress_stage' => 1,
      'is_pending' => true,
    ];
  }
  $name = trim((string)$row->particulars);
  if ($name === '') {
    $name = trim((string)$row->description);
  }
  $qty = (float)$row->quantity;
  $price = (float)$row->price;
  $total = $qty * $price;
  $invoices[$orderKey]->items[] = (object) [
    'invoice_id' => $orderId,
    'reference_prefix' => 'ORD',
    'name' => $name,
    'qty' => $qty,
    'price' => $price,
    'total' => $total,
  ];
  $invoices[$orderKey]->item_count++;
  $invoices[$orderKey]->total += $total;
}
uasort($invoices, function ($left, $right) {
  $leftDate = strtotime((string)$left->date) ?: 0;
  $rightDate = strtotime((string)$right->date) ?: 0;
  if ($leftDate === $rightDate) {
    return 0;
  }
  return $leftDate < $rightDate ? 1 : -1;
});
foreach ($invoices as $invoice) {
  if ($invoice->item_count > 0 && $invoice->assigned_count === $invoice->item_count) {
    $invoice->progress_stage = 2;
  }
  if ($invoice->item_count > 0 && $invoice->collected_count === $invoice->item_count) {
    $invoice->progress_stage = 3;
  }
  if ($invoice->item_count > 0 && $invoice->delivered_count === $invoice->item_count) {
    $invoice->progress_stage = 4;
  }
}
$visibleInvoices = [];
$deliveredCount = 0;
foreach ($invoices as $invoiceKey => $invoice) {
  if ($invoice->progress_stage < 4) {
    $visibleInvoices[$invoiceKey] = $invoice;
  } elseif ($deliveredCount < 3) {
    $visibleInvoices[$invoiceKey] = $invoice;
    $deliveredCount++;
  }
}
$invoices = $visibleInvoices;
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
  .receipt-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;color:#111827;box-shadow:0 2px 8px rgba(15,23,42,.08);}
  .receipt-header{text-align:center;border-bottom:1px dashed #9ca3af;padding-bottom:12px;}
  .receipt-top-row{display:flex;align-items:baseline;justify-content:space-between;gap:8px;margin-bottom:8px;text-align:left;}
  .receipt-city{font-size:11px;font-weight:600;color:#4b5563;}
  .receipt-title{font-size:16px;font-weight:800;letter-spacing:.08em;margin:0 0 8px;}
  .receipt-title-date{font-size:10px;font-weight:500;letter-spacing:normal;color:#6b7280;text-align:right;white-space:nowrap;}
  .receipt-subtitle{font-size:12px;color:#4b5563;margin:2px 0;}
  .receipt-progress{position:relative;margin:16px 0 2px;padding:0;list-style:none;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));}
  .receipt-progress-track{position:absolute;top:15px;left:12.5%;right:12.5%;height:3px;background:#e5e7eb;border-radius:999px;}
  .receipt-progress-fill{display:block;height:100%;background:#22b55e;border-radius:999px;transition:width .25s ease;}
  .receipt-progress-step{position:relative;z-index:1;display:flex;min-width:0;flex-direction:column;align-items:center;gap:5px;color:#6b7280;font-size:9px;line-height:1.15;text-align:center;}
  .receipt-progress-marker{display:flex;width:32px;height:32px;align-items:center;justify-content:center;border:2px solid #d1d5db;border-radius:50%;background:#fff;color:#6b7280;font-size:12px;font-weight:700;}
  .receipt-progress-step.is-complete,.receipt-progress-step.is-current{color:#16803c;font-weight:700;}
  .receipt-progress-step.is-complete .receipt-progress-marker,.receipt-progress-step.is-current .receipt-progress-marker{border-color:#22b55e;background:#22b55e;color:#fff;}
  .receipt-progress-step.is-pending{color:#c2410c;}
  .receipt-progress-step.is-pending .receipt-progress-marker{border-color:#f97316;background:#fff;color:#c2410c;}
  .receipt-progress-label{max-width:78px;}
  .receipt-meta{margin:12px 0;font-size:12px;}
  .receipt-meta strong,.receipt-assignee-row strong{color:#374151;}
  .receipt-assignees{margin:10px 0;font-size:12px;}
  .receipt-assignee-row{display:flex;justify-content:space-between;gap:12px;padding:4px 0;border-bottom:1px solid #e5e7eb;}
  .receipt-assignee-row span:first-child{min-width:0;}
  .receipt-assignee-row span:last-child{text-align:right;white-space:nowrap;}
  .receipt-item{border-top:1px solid #d1d5db;padding:9px 0;}
  .receipt-item-head{display:flex;align-items:flex-start;gap:8px;}
  .receipt-item-invoice{font-size:10px;color:#4b5563;white-space:nowrap;}
  .receipt-item-name{display:block;flex:1;font-size:10px;font-weight:600;min-width:0;}
  .receipt-item-values{display:grid;grid-template-columns:1fr .55fr 1fr;gap:8px;margin-top:6px;padding-left:54px;box-sizing:border-box;font-size:11px;}
  .receipt-item-values-header{font-size:10px;color:#6b7280;margin-top:10px;}
  .receipt-item-values-header span:last-child{text-align:right;}
  .receipt-item-value{display:flex;flex-direction:column;gap:2px;}
  .receipt-item-value:last-child{text-align:right;}
  .receipt-totals{width:min(100%,240px);margin:12px 0 0 auto;font-size:12px;}
  .receipt-total{display:flex;justify-content:space-between;border-top:1px dashed #9ca3af;margin-top:7px;padding-top:8px;font-size:15px;font-weight:800;}
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-3">
    <div class="flex items-center justify-between mb-2">
      <a href="?page=account_menu" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold m-0">Order History</h1>
      <div class="w-6"></div>
    </div>
    <p class="text-center text-white/90 text-sm m-0">Orders, invoices and items</p>
  </div>
</div>

<div class="max-w-sm mx-auto px-4 mt-3 mb-6">
  <?php if (empty($invoices)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center text-gray-500">
      No order history found.
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($invoices as $invoice): ?>
        <article class="receipt-card">
          <header class="receipt-header">
            <div class="receipt-top-row">
              <span class="receipt-city"><?php echo htmlspecialchars($invoice->customer_city !== '' ? $invoice->customer_city : '—', ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="receipt-title-date"><?php echo htmlspecialchars($invoice->date ? (string)df($invoice->date) : '—', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <h1 class="receipt-title">ORDER STATUS</h1>
            <div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($invoice->customer_contact !== '' && $invoice->customer_contact !== $invoice->customer_name): ?><div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_contact, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($invoice->customer_mobile !== ''): ?><div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_mobile, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <div class="receipt-progress" role="group" aria-label="Order progress">
              <div class="receipt-progress-track" aria-hidden="true">
                <span class="receipt-progress-fill" style="width: <?php echo (($invoice->progress_stage - 1) / 3) * 100; ?>%"></span>
              </div>
              <?php
              $progressLabels = [!empty($invoice->is_pending) ? 'Pending' : 'Confirmed', 'Assigned', 'Out for Delivery', 'Delivered'];
              foreach ($progressLabels as $stepIndex => $progressLabel):
                $stepNumber = $stepIndex + 1;
                $stepClass = $stepNumber < $invoice->progress_stage
                  ? 'is-complete'
                  : ($stepNumber === $invoice->progress_stage ? 'is-current' : '');
                if (!empty($invoice->is_pending) && $stepNumber === 1) {
                  $stepClass .= ' is-pending';
                }
              ?>
                <div class="receipt-progress-step <?php echo $stepClass; ?>"<?php echo $stepNumber === $invoice->progress_stage ? ' aria-current="step"' : ''; ?>>
                  <span class="receipt-progress-marker"><?php echo $stepNumber < $invoice->progress_stage ? '&#10003;' : $stepNumber; ?></span>
                  <span class="receipt-progress-label"><?php echo htmlspecialchars($progressLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </header>
          <div class="receipt-assignees">
            <?php if (empty($invoice->assignees)): ?>
              <div class="receipt-assignee-row"><span><strong>Assigned To:</strong> —</span><span><strong>Phone:</strong> —</span></div>
            <?php else: ?>
              <?php foreach ($invoice->assignees as $assigneeName => $assigneePhone): ?>
                <div class="receipt-assignee-row">
                  <span><strong>Assigned To:</strong> <?php echo htmlspecialchars($assigneeName, ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><strong>Phone:</strong> <?php echo $assigneePhone !== '' ? htmlspecialchars($assigneePhone, ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="receipt-item-list">
            <div class="receipt-item-values receipt-item-values-header"><span>Price</span><span>Qty</span><span>Total</span></div>
            <?php $i = 1; foreach ($invoice->items as $it): ?>
              <div class="receipt-item">
                <div class="receipt-item-head">
                  <span class="receipt-item-invoice"><?php echo $i++; ?>. <?php echo htmlspecialchars($it->reference_prefix, ENT_QUOTES, 'UTF-8'); ?><?php echo zerofill($it->invoice_id, 5); ?></span>
                  <span class="receipt-item-name"><?php echo htmlspecialchars((string)$it->name, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="receipt-item-values">
                  <div class="receipt-item-value"><strong>RM <?php echo number_format($it->price, 2); ?></strong></div>
                  <div class="receipt-item-value"><strong><?php echo number_format($it->qty, 0); ?></strong></div>
                  <div class="receipt-item-value"><strong>RM <?php echo number_format($it->total, 2); ?></strong></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="receipt-totals">
            <div class="flex justify-between"><span>Subtotal</span><span>RM <?php echo number_format($invoice->total, 2); ?></span></div>
            <div class="receipt-total"><span>Total</span><span>RM <?php echo number_format($invoice->total, 2); ?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 flex justify-center">
      <a href="?page=home" class="inline-flex items-center gap-2 bg-yellow-400 text-gray-900 rounded-full px-8 py-2 text-sm font-medium no-underline">Return to Home</a>
    </div>
  <?php endif; ?>
</div>
