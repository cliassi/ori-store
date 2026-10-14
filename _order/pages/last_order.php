<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$invoices = [];
$historyItems = select("SELECT i.id invoice_id, i.invoice_date,
    c.company customer_name, c.contact customer_contact, c.mobile customer_mobile, c.city customer_city,
    ii.description, ii.quantity, ii.price, ii.assigned_to, ss.phone assigned_phone,
    pv.particulars
  FROM invoice i
  INNER JOIN invoice_item ii ON ii.invoice_id = i.id
  LEFT JOIN customer c ON c.id = i.customer_id
  LEFT JOIN staff_salary ss ON ss.name = ii.assigned_to AND ss.category = 'Delivery Staff'
  LEFT JOIN product_variance pv ON pv.id = ii.product_variance_id
  WHERE i.customer_id = $uid
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
  if ($assignedTo !== '') {
    $invoices[$dateKey]->assignees[$assignedTo] = $assignedPhone;
  }
  $invoices[$dateKey]->items[] = (object) [
    'invoice_id' => $invoiceId,
    'name' => $name,
    'qty' => $qty,
    'price' => $price,
    'total' => $total,
  ];
  $invoices[$dateKey]->total += $total;
}
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
  .receipt-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;color:#111827;box-shadow:0 2px 8px rgba(15,23,42,.08);}
  .receipt-header{text-align:center;border-bottom:1px dashed #9ca3af;padding-bottom:12px;}
  .receipt-title{font-size:16px;font-weight:800;letter-spacing:.08em;margin:0 0 8px;}
  .receipt-subtitle{font-size:12px;color:#4b5563;margin:2px 0;}
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
  .receipt-item-desc{display:block;color:#6b7280;font-size:10px;}
  .receipt-item-values{display:grid;grid-template-columns:1fr .55fr 1fr;gap:8px;margin-top:6px;font-size:11px;}
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
    <p class="text-center text-white/90 text-sm m-0">Invoices and items</p>
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
            <h2 class="receipt-title">INVOICE</h2>
            <div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($invoice->customer_city !== ''): ?><div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_city, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          </header>
          <div class="receipt-meta">
            <div><strong>Assigned To:</strong>
              <?php if (empty($invoice->assignees)): ?>—<?php else: ?>
                <?php $assigneeIndex = 0; foreach ($invoice->assignees as $assigneeName => $assigneePhone): ?>
                  <?php if ($assigneeIndex > 0): ?>, <?php endif; ?>
                  <?php echo htmlspecialchars($assigneeName, ENT_QUOTES, 'UTF-8'); ?><?php if ($assigneePhone !== ''): ?> · Phone: <?php echo htmlspecialchars($assigneePhone, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                  <?php $assigneeIndex++; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($invoice->customer_name, ENT_QUOTES, 'UTF-8'); ?><?php if ($invoice->customer_contact !== '' && $invoice->customer_contact !== $invoice->customer_name): ?> · <?php echo htmlspecialchars($invoice->customer_contact, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
            <div><strong>Sales Date:</strong> <?php echo htmlspecialchars($invoice->date ? (string)df($invoice->date) : '—', ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($invoice->customer_mobile !== ''): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($invoice->customer_mobile, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          </div>
          <div class="receipt-item-list">
            <div class="receipt-item-values receipt-item-values-header"><span>Price</span><span>Qty</span><span>Total</span></div>
            <?php foreach ($invoice->items as $it): ?>
              <div class="receipt-item">
                <div class="receipt-item-head">
                  <span class="receipt-item-invoice">INV<?php echo zerofill($it->invoice_id, 5); ?></span>
                  <span class="receipt-item-name">
                    <?php echo htmlspecialchars((string)$it->name, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($it->desc && trim((string)$it->desc) !== trim((string)$it->name)): ?><span class="receipt-item-desc"><?php echo htmlspecialchars((string)$it->desc, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                  </span>
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
