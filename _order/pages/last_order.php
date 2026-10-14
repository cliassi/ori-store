<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$invoices = [];
$historyItems = select("SELECT i.id invoice_id, i.invoice_date, i.salesman, c.company customer_name,
    c.contact customer_contact, c.mobile customer_mobile, c.city customer_city,
    ii.name, ii.description, ii.quantity, ii.price, ii.assigned_to, ss.phone assigned_phone,
    pv.particulars, p.name product_name
  FROM invoice i
  INNER JOIN invoice_item ii ON ii.invoice_id = i.id
  LEFT JOIN customer c ON c.id = i.customer_id
  LEFT JOIN staff_salary ss ON ss.name = ii.assigned_to AND ss.category = 'Delivery Staff'
  LEFT JOIN product_variance pv ON pv.id = ii.product_variance_id
  LEFT JOIN product p ON p.id = ii.product_id
  WHERE i.customer_id = $uid
  ORDER BY i.id DESC, ii.id DESC");
while ($historyItems && ($row = mysqli_fetch_object($historyItems))) {
  $invoiceId = (int)$row->invoice_id;
  $customerName = trim((string)$row->customer_name);
  if ($customerName === '') {
    $customerName = trim((string)$row->customer_contact);
  }
  if ($customerName === '') {
    $customerName = 'Walk-in';
  }
  if (!isset($invoices[$invoiceId])) {
    $invoices[$invoiceId] = (object) [
      'id' => $invoiceId,
      'date' => $row->invoice_date,
      'agent' => trim((string)$row->salesman),
      'customer_name' => $customerName,
      'customer_mobile' => trim((string)$row->customer_mobile),
      'customer_city' => trim((string)$row->customer_city),
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
  $invoices[$invoiceId]->items[] = (object) [
    'name' => $name,
    'desc' => $row->description,
    'assigned_to' => trim((string)$row->assigned_to),
    'assigned_phone' => trim((string)$row->assigned_phone),
    'qty' => $qty,
    'price' => $price,
    'total' => $total,
  ];
  $invoices[$invoiceId]->total += $total;
}
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
  .receipt-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;color:#111827;box-shadow:0 2px 8px rgba(15,23,42,.08);}
  .receipt-header{text-align:center;border-bottom:1px dashed #9ca3af;padding-bottom:12px;}
  .receipt-title{font-size:16px;font-weight:800;letter-spacing:.08em;margin:0 0 8px;}
  .receipt-subtitle{font-size:12px;color:#4b5563;margin:2px 0;}
  .receipt-meta{display:grid;grid-template-columns:1fr 1fr;gap:5px 12px;margin:12px 0;font-size:12px;}
  .receipt-meta strong{color:#374151;}
  .receipt-items{width:100%;border-collapse:collapse;font-size:12px;}
  .receipt-items th,.receipt-items td{padding:7px 3px;border-top:1px solid #d1d5db;vertical-align:top;}
  .receipt-items th{font-size:11px;text-align:left;}
  .receipt-items .numeric{text-align:right;white-space:nowrap;}
  .receipt-item-name{font-weight:600;}
  .receipt-item-desc{display:block;color:#6b7280;font-size:10px;}
  .receipt-assignment{border-top:1px dashed #9ca3af;margin-top:10px;padding-top:9px;font-size:12px;}
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
            <h2 class="receipt-title">SALES INVOICE</h2>
            <div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($invoice->customer_city !== ''): ?><div class="receipt-subtitle"><?php echo htmlspecialchars($invoice->customer_city, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          </header>
          <div class="receipt-meta">
            <div><strong>Agent:</strong> <?php echo htmlspecialchars($invoice->agent !== '' ? $invoice->agent : '—', ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($invoice->customer_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Invoice No:</strong> INV<?php echo zerofill($invoice->id, 5); ?></div>
            <div><strong>Sales Date:</strong> <?php echo htmlspecialchars($invoice->date ? (string)df($invoice->date) : '—', ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($invoice->customer_mobile !== ''): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($invoice->customer_mobile, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          </div>
          <table class="receipt-items">
            <thead>
              <tr>
                <th style="width:8%">No.</th>
                <th style="width:38%">Item</th>
                <th class="numeric" style="width:19%">Price</th>
                <th class="numeric" style="width:12%">Qty.</th>
                <th class="numeric" style="width:23%">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($invoice->items as $index => $it): ?>
                <tr>
                  <td><?php echo (int)$index + 1; ?></td>
                  <td>
                    <span class="receipt-item-name"><?php echo htmlspecialchars((string)$it->name, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($it->desc && trim((string)$it->desc) !== trim((string)$it->name)): ?><span class="receipt-item-desc"><?php echo htmlspecialchars((string)$it->desc, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    <?php if ($it->assigned_to !== ''): ?>
                      <span class="receipt-item-desc">Assigned delivery to: <?php echo htmlspecialchars($it->assigned_to, ENT_QUOTES, 'UTF-8'); ?><?php if ($it->assigned_phone !== ''): ?> · <?php echo htmlspecialchars($it->assigned_phone, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="numeric">RM <?php echo number_format($it->price, 2); ?></td>
                  <td class="numeric"><?php echo number_format($it->qty, 0); ?></td>
                  <td class="numeric">RM <?php echo number_format($it->total, 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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
