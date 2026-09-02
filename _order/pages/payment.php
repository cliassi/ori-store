<?php
if ((defined('GUEST') && GUEST) || !defined('UID')) {
  redir('?page=home');
  return;
}

$uid = (int) UID;
$cust = R::findOne('customer', ' id = ? ', [$uid]);
if (!$cust) {
  redir('?page=home');
  return;
}

$saved = isset($get->saved);
if (isset($post->save)) {
  $amount = isset($post->amount) ? (float)$post->amount : 0;
  $date = isset($post->date) ? trim((string)$post->date) : today();
  $method = isset($post->payment_method) ? trim((string)$post->payment_method) : 'Cash';
  if (!in_array($method, ['Cash', 'Bank'], true)) {
    $method = 'Cash';
  }
  $description = isset($post->description) ? trim((string)$post->description) : '';

  if ($amount > 0) {
    $obj = R::dispense('collection');
    $obj->customer_id = $uid;
    $obj->date = $date ?: today();
    $obj->amount = $amount;
    $obj->payment_method = $method;
    $obj->description = $description;
    $obj->created_by = $uid;
    R::store($obj);
    redir('?page=payment&saved=1');
    return;
  }
}

$payments = R::find('collection', ' customer_id = ? ORDER BY id DESC LIMIT 10 ', [$uid]);
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-2">
      <a href="?page=account_menu" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold">Payment</h1>
      <div class="w-6"></div>
    </div>
    <p class="text-center text-white/90 text-sm"><?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</div>

<div class="max-w-sm mx-auto px-4 -mt-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <?php if ($saved): ?>
      <div class="mb-4 rounded-lg bg-green-50 text-green-800 text-sm px-3 py-2">Payment saved.</div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
        <input type="date" name="date" value="<?php echo htmlspecialchars(today(), ENT_QUOTES, 'UTF-8'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
        <input type="number" name="amount" id="payAmount" step="any" min="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter amount">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Amount</label>
        <div class="grid grid-cols-3 gap-2">
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="100">100</button>
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="200">200</button>
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="300">300</button>
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="500">500</button>
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="1000">1000</button>
          <button type="button" class="pay-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm" data-amount="1500">1500</button>
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
        <div class="flex space-x-4">
          <label class="flex items-center">
            <input type="radio" name="payment_method" value="Cash" class="mr-2" checked>
            <span class="text-sm">Cash</span>
          </label>
          <label class="flex items-center">
            <input type="radio" name="payment_method" value="Bank" class="mr-2">
            <span class="text-sm">Bank</span>
          </label>
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter description"></textarea>
      </div>
      <button type="submit" name="save" value="1" class="w-full bg-primary hover:bg-primaryDark text-white py-3 px-4 rounded-lg font-medium">Save Payment</button>
    </form>
  </div>

  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b">
      <h2 class="font-semibold text-gray-800 text-sm">Recent Payments</h2>
    </div>
    <?php if (!$payments): ?>
      <p class="px-4 py-6 text-center text-sm text-gray-500">No payments yet.</p>
    <?php else: ?>
      <ul class="divide-y divide-gray-100">
        <?php foreach ($payments as $p): ?>
          <li class="px-4 py-3 flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string)df($p->date ?: $p->created_at), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="text-xs text-gray-500"><?php echo htmlspecialchars((string)$p->payment_method, ENT_QUOTES, 'UTF-8'); ?><?php if ($p->description): ?> · <?php echo htmlspecialchars($p->description, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
            </div>
            <div class="text-sm font-semibold text-gray-900">RM <?php echo number_format((float)$p->amount, 2); ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<script>
  document.querySelectorAll('.pay-amount').forEach((btn) => {
    btn.addEventListener('click', () => {
      const amount = document.getElementById('payAmount');
      if (amount) amount.value = btn.getAttribute('data-amount') || '';
    });
  });
</script>
