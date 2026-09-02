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

$trans = select("SELECT * FROM (SELECT * FROM (
    SELECT 'invoice' src, i.id, ii.id id2, i.invoice_date sort_date, i.invoice_date date,
      (SELECT particulars FROM product_variance WHERE product_variance.id=ii.product_variance_id) particulars,
      ii.price * ii.quantity amount
    FROM `invoice` i, `invoice_item` ii
    WHERE i.id=ii.invoice_id AND i.customer_id=$uid
    UNION
    SELECT 'collection' src, id, 0 id2, created_at sort_date, date, description particulars, amount
    FROM `collection` WHERE customer_id=$uid
) a ORDER BY date DESC, sort_date DESC) b ORDER BY sort_date, id");
?>
<style>
  .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
  .statement-table td, .statement-table th { white-space: nowrap; }
</style>

<div class="bg-primary blob-shape text-white">
  <div class="max-w-sm mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-2">
      <a href="?page=home" class="text-white" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <h1 class="text-lg font-semibold">Statement</h1>
      <div class="w-6"></div>
    </div>
    <p class="text-center text-white/90 text-sm"><?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</div>

<div class="max-w-sm mx-auto px-4 -mt-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-xs statement-table">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-2 py-2 text-left">Date</th>
            <th class="px-2 py-2 text-left">Ref</th>
            <th class="px-2 py-2 text-left">Particulars</th>
            <th class="px-2 py-2 text-right">Debit</th>
            <th class="px-2 py-2 text-right">Credit</th>
            <th class="px-2 py-2 text-right">Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $totalDebit = 0;
          $totalCredit = 0;
          $balance = 0;
          $hasRows = false;
          if ($trans) {
            while ($item = mysqli_fetch_object($trans)) {
              $hasRows = true;
              $sort_date = substr((string)$item->sort_date, 0, 10);
              $particulars = (string)$item->particulars;
              if ($item->src == 'invoice') {
                $oi = R::load('invoice_item', $item->id2);
                if ($oi && !empty($oi->description)) {
                  $particulars = $oi->description;
                }
                if ($oi && $oi->quantity) {
                  $particulars .= ' (' . $oi->quantity . ')';
                }
                $totalDebit += $item->amount;
                $balance += $item->amount;
                $ref = 'INV' . zerofill($item->id, 5);
              } else {
                $totalCredit += $item->amount;
                $balance -= $item->amount;
                $ref = 'OR' . zerofill($item->id, 7);
              }
              echo "<tr class='border-b border-gray-100'>";
              echo "<td class='px-2 py-2'>" . htmlspecialchars((string)df($sort_date), ENT_QUOTES, 'UTF-8') . "</td>";
              echo "<td class='px-2 py-2'>" . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . "</td>";
              echo "<td class='px-2 py-2 whitespace-normal'>" . htmlspecialchars($particulars, ENT_QUOTES, 'UTF-8') . "</td>";
              if ($item->src == 'invoice') {
                echo "<td class='px-2 py-2 text-right'>" . nf($item->amount) . "</td>";
                echo "<td class='px-2 py-2 text-right'></td>";
              } else {
                echo "<td class='px-2 py-2 text-right'></td>";
                echo "<td class='px-2 py-2 text-right'>" . nf($item->amount) . "</td>";
              }
              echo "<td class='px-2 py-2 text-right font-medium'>" . nf($balance) . "</td>";
              echo "</tr>";
            }
          }
          if (!$hasRows):
          ?>
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">No transactions yet.</td>
            </tr>
          <?php else: ?>
            <tr class="bg-gray-100 font-semibold">
              <td colspan="3" class="px-2 py-2">TOTAL</td>
              <td class="px-2 py-2 text-right"><?php echo nf($totalDebit); ?></td>
              <td class="px-2 py-2 text-right"><?php echo nf($totalCredit); ?></td>
              <td class="px-2 py-2 text-right"><?php echo nf($balance); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
