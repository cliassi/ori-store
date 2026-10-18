<?php
define('CUSTOMER_BALANCE_LIMIT', 2000);

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

function customerOrderWouldExceedBalance($customerId, $amount)
{
  return customerLedgerBalance($customerId) + (float) $amount > CUSTOMER_BALANCE_LIMIT;
}
