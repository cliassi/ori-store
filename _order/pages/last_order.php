<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$order = R::findOne('customer_order', ' customer_id = ? ORDER BY id DESC ', [$uid]);
$items = [];
$grand = 0;
$orderDate = '';

if ($order && $order->id) {
  $orderDate = $order->invoice_date ?: ($order->customer_order_date ?: $order->created_at);
  $rows = R::find('customer_order_item', ' customer_order_id = ? ', [$order->id]);
  foreach ($rows as $row) {
    $qty = (int)$row->quantity;
    $price = (float)$row->price;
    $total = $qty * $price;
    $grand += $total;
    $variance = $row->product_variance_id ? R::load('product_variance', $row->product_variance_id) : null;
    $imageSrc = ($variance && !empty($variance->image))
      ? getImageOrPlaceholder($variance->image, $row->name)
      : getImageOrPlaceholder('', $row->name);
    $items[] = (object) [
      'image' => $imageSrc,
      'name' => $row->name ? $row->name : $row->description,
      'desc' => $row->description,
      'qty' => $qty,
      'price' => $price,
      'total' => $total,
    ];
  }
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
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-2">
      <a href="?page=home" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold">Last Order</h1>
      <div class="w-6"></div>
    </div>
    <?php if ($order && $order->id): ?>
      <p class="text-center text-white/90 text-sm">
        Order #<?php echo (int)$order->id; ?>
        <?php if ($orderDate): ?> · <?php echo htmlspecialchars((string)df($orderDate), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</div>

<div class="max-w-sm mx-auto px-4 -mt-4 mb-6">
  <?php if (!$order || !$order->id || empty($items)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center text-gray-500">
      No previous order found.
    </div>
  <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($items as $it): ?>
        <div class="po-card">
          <div class="po-thumb"><img src="<?php echo htmlspecialchars($it->image, ENT_QUOTES, 'UTF-8'); ?>" alt=""></div>
          <div style="flex:1 1 auto; min-width:0;">
            <div class="po-name"><?php echo htmlspecialchars((string)$it->name, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="po-unit">Unit: RM <?php echo number_format($it->price, 2); ?></div>
          </div>
          <div class="po-right">
            <div class="po-total">RM <?php echo number_format($it->total, 2); ?></div>
            <div class="po-qty-pill">Qty: <span><?php echo (int)$it->qty; ?></span></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-3 text-right font-semibold text-gray-900">Grand Total: RM <?php echo number_format($grand, 2); ?></div>
    <div class="mt-4 flex justify-center">
      <a href="?page=home" class="inline-flex items-center gap-2 bg-yellow-400 text-gray-900 rounded-full px-8 py-2 text-sm font-medium no-underline">Return to Home</a>
    </div>
  <?php endif; ?>
</div>
