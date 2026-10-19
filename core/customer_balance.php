<?php
function customerDueLimit($customerId)
{
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
