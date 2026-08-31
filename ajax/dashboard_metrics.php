<?php
session_start();
$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0;
require_once(__DIR__ . "/../env.php");
require_once(__DIR__ . "/../core/config.php");
require_once(__DIR__ . "/../core/f.inc.php");

header('Content-Type: text/html; charset=utf-8');

$mon = isset($_GET['mon']) ? preg_replace('/[^0-9\-]/', '', $_GET['mon']) : '';

$filter1 = "branch_id=$branch_id";            // cw_cash, cw_bank, cw_withdraw (entry_time scope when mon)
$filter   = "branch_id=$branch_id";            // created_at scope for most tables
$filter_exp = "branch_id=$branch_id";          // expense_account_entry (expense_date scope when mon)
$filter_invoice = "i.branch_id=$branch_id";    // invoice table scoped filter (created_at when mon)
if ($mon) {
  $date = "$mon-01";
  $last = lastDate($date);
  $filter1 = "entry_time BETWEEN '$date' AND '$last'";
  $filter_exp = "branch_id=$branch_id AND expense_date BETWEEN '$date' AND '$last'";
  $filter = "created_at BETWEEN '$date' AND '$last'";
  $filter_invoice = "i.created_at BETWEEN '$date' AND '$last' AND i.branch_id=$branch_id";
}

$summary = mysqli_fetch_object(select("SELECT 
  (SELECT SUM(IFNULL(amount,0)) FROM `cw_cash` WHERE $filter1) add_cash, 
  (SELECT IFNULL(SUM(IFNULL(amount,0)),0) FROM `cw_cash` WHERE amount>0 AND $filter1) cash, 
  (SELECT SUM(IFNULL(amount,0)) FROM `cw_bank` WHERE $filter1) bank, 
  (SELECT SUM(IFNULL(amount,0)) FROM `cw_cash_withdraw` WHERE $filter1) withdraw, 
  (SELECT SUM(IFNULL(amount,0)) FROM `bd_handover` WHERE $filter) cash_handover, 
  (SELECT SUM(IFNULL(bank_amount,0)) FROM `bd_handover` WHERE $filter) bank_handover,
  (SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Cash' AND $filter) cash_payment, 
  (SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Bank' AND $filter) bank_payment,
  (SELECT SUM(IFNULL(amount,0)) FROM `collection` WHERE payment_method='Cash' AND $filter) cash_collection, 
  (SELECT SUM(IFNULL(amount,0)) FROM `collection` WHERE payment_method='Bank' AND $filter) bank_collection,
  (SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Cash' AND $filter_exp) cash_expense, 
  (SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Online' AND $filter_exp) bank_expense"));
if (!$summary) {
  $summary = (object) [
    'add_cash'=>0,'cash'=>0,'bank'=>0,'withdraw'=>0,
    'cash_handover'=>0,'bank_handover'=>0,
    'cash_payment'=>0,'bank_payment'=>0,
    'cash_collection'=>0,'bank_collection'=>0,
    'cash_expense'=>0,'bank_expense'=>0
  ];
}

$summary2 = mysqli_fetch_object(select("SELECT 
  (SELECT SUM(IFNULL(amount,0)) FROM `cw_cash` WHERE branch_id=$branch_id) add_cash, 
  (SELECT IFNULL(SUM(h.amount),0) FROM (SELECT MAX(id) id FROM `bd_handover` WHERE branch_id=$branch_id GROUP BY `date`) latest JOIN `bd_handover` h ON h.id=latest.id) cash_handover, 
  (SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Cash' AND $filter_exp) cash_expense, 
  (SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Cash' AND $filter) cash_payment, 
  (SELECT IFNULL(SUM(IFNULL(amount,0)),0) FROM `cw_cash` WHERE amount>0 AND branch_id=$branch_id) cash, 
  (SELECT SUM(IFNULL(amount,0)) FROM `cw_cash_withdraw` WHERE branch_id=$branch_id) withdraw"));
if (!$summary2) {
  $summary2 = (object) [
    'add_cash'=>0,'cash_handover'=>0,'cash_expense'=>0,'cash_payment'=>0,'cash'=>0,'withdraw'=>0
  ];
}

$sreturn = mysqli_fetch_object(select("SELECT IFNULL(SUM(quantity*cost),0) amount FROM `goods_return_item` WHERE $filter")) ?: (object)['amount'=>0];
$damage = mysqli_fetch_object(select("SELECT IFNULL(SUM(quantity*cost),0) amount FROM `damaged_item` WHERE $filter")) ?: (object)['amount'=>0];
$sdue   = mysqli_fetch_object(select("SELECT (SELECT SUM(quantity*cost) FROM `order_item` WHERE $filter) - (SELECT IFNULL(SUM(amount),0) FROM `payment` WHERE $filter) amount")) ?: (object)['amount'=>0];
$due    = mysqli_fetch_object(select("SELECT (SELECT SUM(quantity*price) FROM `invoice_item` WHERE $filter) - (SELECT IFNULL(SUM(amount),0) FROM `collection` WHERE $filter) amount")) ?: (object)['amount'=>0];

require_once(__DIR__ . '/../app/pages/reports/customer_due_functions.php');
$customer_due = getCustomerDueTotal($branch_id, $filter);

// Build profit to match reports/order.php logic exactly
$profitWhere = "(c.branch_id=$branch_id OR c.branch_id IS NULL)";
if ($mon) {
  $profitWhere .= " AND (i.invoice_date BETWEEN '$date' AND '$last')";
}
$profitSql = "SELECT IFNULL(SUM(ii.quantity*(CAST(ii.price AS DECIMAL(15,4)) - CAST(ii.cost AS DECIMAL(15,4)))),0) amount 
  FROM invoice i
  INNER JOIN invoice_item ii ON i.id=ii.invoice_id
  INNER JOIN customer c ON c.id=i.customer_id
  WHERE $profitWhere";
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
  echo "<pre>Dashboard Profit SQL:\n" . htmlspecialchars($profitSql) . "</pre>";
}
$_profitRes = select($profitSql);
if (!$_profitRes) {
  $profit = (object)['amount'=>0];
} else {
  $profit = mysqli_fetch_object($_profitRes);
  if (!$profit) { $profit = (object)['amount'=>0]; }
}
$store_value = mysqli_fetch_object(select("SELECT IFNULL(SUM(stock2(id, $branch_id)*cost),0) amount FROM product_variance")) ?: (object)['amount'=>0];

// ---- Store (top-level expense account) Income/Expense — mirrors app/pages/details/expense_account.php exactly ----
$store_month = $mon ? $mon : date('Y-m', time());
$store_month_start = "$store_month-01";
$store_month_end = date('Y-m-t', strtotime($store_month_start));

$storeAccount = mysqli_fetch_object(select("SELECT id, path FROM `expense_account` WHERE parent IS NULL AND path='/1/' LIMIT 1")) ?: null;

$store_income = 0;
$store_expense = 0;
if ($storeAccount) {
  $storePath = $storeAccount->path;

  $store_credit_row = mysqli_fetch_object(select("SELECT IFNULL(SUM(IF(tran_type='Credit', amount, 0)),0) amt FROM `expense_account_entry` WHERE branch_id=$branch_id AND entry_time LIKE '$store_month-%' AND accountpath LIKE CONCAT('$storePath','%')")) ?: (object)['amt'=>0];
  $store_credit = (float)$store_credit_row->amt;

  // Same schema as $profitSql above: branch comes via customer, not invoice.branch_id
  $store_profit_row = mysqli_fetch_object(select("SELECT IFNULL(SUM(ii.quantity * (CAST(ii.price AS DECIMAL(15,4)) - CAST(ii.cost AS DECIMAL(15,4)))),0) amt 
    FROM invoice i 
    INNER JOIN invoice_item ii ON i.id = ii.invoice_id 
    INNER JOIN customer c ON c.id = i.customer_id
    WHERE (c.branch_id=$branch_id OR c.branch_id IS NULL) AND i.invoice_date BETWEEN '$store_month_start' AND '$store_month_end'")) ?: (object)['amt'=>0];
  $store_profit = (float)$store_profit_row->amt;

  $store_income = $store_credit + ($storePath === '/1/' ? $store_profit : 0);

  $store_expense_row = mysqli_fetch_object(select("SELECT IFNULL(SUM(IF(tran_type='Debit', amount, 0)),0) amt FROM `expense_account_entry` WHERE branch_id=$branch_id AND (`month`='$store_month' OR expense_date LIKE '$store_month-%') AND accountpath LIKE CONCAT('$storePath','%')")) ?: (object)['amt'=>0];
  $store_expense = (float)$store_expense_row->amt;
}

$petty_cash = $summary->add_cash - abs($summary->withdraw) + $summary->cash_collection - $summary->cash_payment - $summary->cash_expense;
$petty_cash = $summary2->cash_handover + $summary2->add_cash - abs($summary2->withdraw) - $summary2->cash_payment - $summary2->cash_expense;
$bank = $summary->bank_handover - $summary->bank_expense - $summary->bank_payment;
$m = date('M, Y', time());

// Build the two tables HTML exactly like dashboard
ob_start();
?>
<div id="metrics-tables">
  <div style="display: inline-block;">
    <table id="customer-table" class="table table-striped table-bordered nowrap"><tbody>
      <tr>
        <td><a href='/store/report/cash'>Petty Cash</a></td><td>:</td>
        <td width="250px" title="<?php print "$summary->add_cash - $summary->withdraw  + $summary->cash_collection - $summary->cash_payment - $summary->cash_expense"; ?>"><?php print nf($petty_cash); ?></td>
      </tr>
      <tr>
        <td>Invested Cash Capital</td><td>:</td>
        <td><?php print nf($summary2->cash - abs($summary2->withdraw)); ?></td>
      </tr>
      <tr>
        <td>Bank</td><td>:</td>
        <td><?php print nf($bank + $summary->bank); ?></td>
      </tr>
      <tr>
        <td><a href='/store/report/due'>Customer Due</a></td><td>:</td>
        <td><?php print nf($customer_due); ?></td>
      </tr>
      <tr>
        <td><a href='/store/report/sdue'>Supplier Due</a></td><td>:</td>
        <td><?php print nf($sdue->amount - $sreturn->amount); ?></td>
      </tr>
    </tbody></table>
  </div>

  <div style="display: inline-block; margin-left: 50px; vertical-align: top;">
    <table id="customer-table" class="table table-striped table-bordered nowrap"><tbody>
      <tr>
        <td>Present Capital </td><td>:</td>
        <td title="<?php print "Petty Cash: " . nf($petty_cash) . ", Bank: " . nf($bank + $summary->bank) . ", Customer Due: " . nf($due->amount) . ", Store Net Product Value: " . nf($store_value->amount) . ", Supplier Due: " . nf($sdue->amount) . ""; ?>">
          <?php print nf($petty_cash + $due->amount + $bank + ($store_value->amount * 0) - $sdue->amount - $sreturn->amount + $summary->bank - $damage->amount + ($profit->amount - ($summary->cash_expense + $summary->bank_expense + $damage->amount))); ?>
        </td>
      </tr>
      <tr>
        <td>Store Stock in Items Value</td><td>:</td>
        <td width="250px" title="<?php print "$store_value->amount - $damage->amount - $sreturn->amount"; ?>"><?php print nf($store_value->amount - $damage->amount - $sreturn->amount); ?></td>
      </tr>
      <tr>
        <td><a href='/store/expense_account_entry/view?d=<?php print subDay(7); ?>'>Expense (<?php print $m; ?>)</a></td><td>:</td>
        <td><?php print nf($store_expense); ?></td>
      </tr>
      <tr>
        <td>Damage/Loss (<?php print $m; ?>)</td><td>:</td>
        <td><?php print nf($damage->amount); ?></td>
      </tr>
      <tr>
        <td>Profit & Loss (<?php print $m; ?>)</td><td>:</td>
        <td><span style="font-weight: 300"><?php print '('.nf($store_income).')'; ?></span> <?php print nf($store_income - $store_expense); ?></td>
      </tr>
    </tbody></table>
  </div>
</div>
<?php
$html = ob_get_clean();
echo $html;
