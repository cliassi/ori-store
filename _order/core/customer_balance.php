<?php
function customerDueLimit($customerId)
{
  static $columnChecked = false;
  if (!$columnChecked) {
    if (function_exists('ensureMysqlColumn')) {
      $columnChecked = ensureMysqlColumn('customer', 'due_limit', 'DECIMAL(12,2) NOT NULL DEFAULT 1500') !== false;
    } else {
      global $c;
      if (isset($c)) {
        $column = $c->query("SHOW COLUMNS FROM `customer` LIKE 'due_limit'");
        $columnChecked = $column && $column->num_rows > 0;
        if (!$columnChecked) {
          $columnChecked = (bool)$c->query("ALTER TABLE `customer` ADD `due_limit` DECIMAL(12,2) NOT NULL DEFAULT 1500");
        }
      }
    }
  }

  $customerId = (int) $customerId;
  if ($customerId <= 0) {
    return 1500;
  }
  $customer = R::load('customer', $customerId);
  if (!$customer || !$customer->id || !isset($customer->due_limit)) {
    return 1500;
  }
  return (float) $customer->due_limit;
}

function customerLedgerBalance($customerId)
{
  $customerId = (int) $customerId;
  if ($customerId <= 0) {
    return 0;
  }
  $debit = (float) getSum("invoice i, invoice_item ii", "price*quantity", "i.id=ii.invoice_id AND customer_id=$customerId");
  $credit = (float) getSum("collection", "amount", "customer_id=$customerId");
  return $debit - $credit;
}

function customerOrderExceedsDueLimit($customerId, $amount)
{
  return customerLedgerBalance($customerId) + (float) $amount > customerDueLimit($customerId);
}
