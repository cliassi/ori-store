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

$photo = !empty($cust->image) ? getImageOrPlaceholder($cust->image, $cust->contact) : '';
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
      <a href="?page=home" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold">Customer</h1>
      <div class="w-6"></div>
    </div>
    <div class="text-center">
      <div class="w-20 h-20 bg-white rounded-full mx-auto mb-3 flex items-center justify-center overflow-hidden">
        <?php if ($photo): ?>
          <img src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-16 h-16 rounded-full object-cover">
        <?php else: ?>
          <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-blue-500 rounded-full"></div>
        <?php endif; ?>
      </div>
      <h2 class="text-xl font-bold"><?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?></h2>
      <p class="text-white/80"><?php echo htmlspecialchars($cust->contact, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </div>
</div>

<div class="max-w-sm mx-auto px-4 -mt-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <dl class="divide-y divide-gray-100">
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Code</dt>
        <dd class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string)$cust->code, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Shop Name</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->company, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Contact</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->contact, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Mobile</dt>
        <dd class="text-sm font-medium text-gray-800">
          <?php if (!empty($cust->mobile)): ?>
            <a href="tel:<?php echo htmlspecialchars($cust->mobile, ENT_QUOTES, 'UTF-8'); ?>" class="text-blue-600"><?php echo htmlspecialchars($cust->mobile, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endif; ?>
        </dd>
      </div>
      <div class="flex justify-between gap-4 px-4 py-3">
        <dt class="text-sm text-gray-500">Area</dt>
        <dd class="text-sm font-medium text-gray-800 text-right"><?php echo htmlspecialchars((string)$cust->city, ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
    </dl>
  </div>
</div>
