<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$invoices = [];
$historyItems = select("SELECT i.id invoice_id, i.invoice_date, ii.name, ii.description, ii.quantity, ii.price,
    ii.assigned_to, pv.image, pv.particulars, p.name product_name
  FROM invoice i
  INNER JOIN invoice_item ii ON ii.invoice_id = i.id
  LEFT JOIN product_variance pv ON pv.id = ii.product_variance_id
  LEFT JOIN product p ON p.id = ii.product_id
  WHERE i.customer_id = $uid
  ORDER BY i.id DESC, ii.id DESC");
while ($historyItems && ($row = mysqli_fetch_object($historyItems))) {
  $invoiceId = (int)$row->invoice_id;
  if (!isset($invoices[$invoiceId])) {
    $invoices[$invoiceId] = (object) [
      'id' => $invoiceId,
      'date' => $row->invoice_date,
      'items' => [],
      'total' => 0,
    ];
  }
  $name = trim((string)$row->name);
  if ($name === '') {
    $name = trim((string)$row->product_name);
  }
  if ($name === '') {
    $name = trim((string)$row->particulars);
  }
  if ($name === '') {
    $name = (string)$row->description;
  }
  $qty = (float)$row->quantity;
  $price = (float)$row->price;
  $total = $qty * $price;
  $imageSrc = getImageOrPlaceholder((string)$row->image, $name);
  $invoices[$invoiceId]->items[] = (object) [
    'image' => $imageSrc,
    'name' => $name,
    'desc' => $row->description,
    'assigned_to' => trim((string)$row->assigned_to),
    'qty' => $qty,
    'price' => $price,
    'total' => $total,
  ];
  $invoices[$invoiceId]->total += $total;
}
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
  .po-card{display:flex;align-items:center;gap:10px;background:#fff;border-radius:12px;padding:8px 10px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
  .po-thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;background:#f3f4f6;flex:0 0 auto;}
  .po-thumb img{width:100%;height:100%;object-fit:cover;}
  .po-name{font-weight:600;color:#0f172a;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}
  .po-unit{color:#6b7280;font-size:11px;}
  .po-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;min-width:110px;margin-left:auto;}
  .po-total{font-weight:700;color:#0f172a;font-size:13px;}
  .po-qty-pill{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;border-radius:9999px;padding:3px 8px;font-size:12px;color:#111827;}
  .invoice-card{background:#fff;border-radius:16px;padding:12px;box-shadow:0 1px 4px rgba(15,23,42,.1);}
  .invoice-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;}
  .invoice-number{font-weight:700;color:#0f172a;font-size:14px;}
  .invoice-foot{display:flex;justify-content:space-between;gap:10px;border-top:1px solid #e5e7eb;margin-top:10px;padding-top:9px;font-size:12px;color:#4b5563;}
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
        <div class="invoice-card">
          <div class="invoice-head">
            <div>
              <div class="invoice-number">Invoice #<?php echo (int)$invoice->id; ?></div>
              <div class="po-unit">Date: <?php echo htmlspecialchars($invoice->date ? (string)df($invoice->date) : '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
          <div class="space-y-2">
            <?php foreach ($invoice->items as $it): ?>
              <div class="po-card">
                <div class="po-thumb"><img src="<?php echo htmlspecialchars($it->image, ENT_QUOTES, 'UTF-8'); ?>" alt=""></div>
                <div style="flex:1 1 auto; min-width:0;">
                  <div class="po-name"><?php echo htmlspecialchars((string)$it->name, ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php if ($it->desc): ?><div class="po-unit"><?php echo htmlspecialchars((string)$it->desc, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                  <?php if ($it->assigned_to !== ''): ?><div class="po-unit">Assigned to: <?php echo htmlspecialchars($it->assigned_to, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                  <div class="po-unit">Unit: RM <?php echo number_format($it->price, 2); ?></div>
                </div>
                <div class="po-right">
                  <div class="po-total">RM <?php echo number_format($it->total, 2); ?></div>
                  <div class="po-qty-pill">Qty: <span><?php echo number_format($it->qty, 0); ?></span></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="invoice-foot">
            <strong>Total: RM <?php echo number_format($invoice->total, 2); ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 flex justify-center">
      <a href="?page=home" class="inline-flex items-center gap-2 bg-yellow-400 text-gray-900 rounded-full px-8 py-2 text-sm font-medium no-underline">Return to Home</a>
    </div>
  <?php endif; ?>
</div>
