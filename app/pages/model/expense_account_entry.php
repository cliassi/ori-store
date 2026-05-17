<?php
ensureMysqlColumn('expense_account_entry', 'investment_id', "INT NULL DEFAULT NULL");

if (!function_exists('createFormInvestmentEntry')) {
	function createFormInvestmentEntry($date, $amount, $particulars, $paymentMethod, $investmentId = 0)
	{
		$investment = $investmentId > 0 ? R::load('investment', (int) $investmentId) : R::dispense('investment');
		$investment->date = nn($date) ? $date : today();
		$investment->amount = (float) $amount;
		$investment->particulars = (string) $particulars;
		$investment->payment_method = in_array(strtolower((string) $paymentMethod), ['bank', 'online'], true) ? 'Bank' : 'Cash';
		if (!$investment->id) {
			$investment->created_by = uid();
			$investment->created_at = now();
			$investment->trash = 0;
		}
		R::store($investment);
		return (int) $investment->id;
	}
}

$fields = ['accountid', 'amount', 'particulars', 'remarks', 'entry_type', 'entry_id', 'status', 'tran_type', 'payment_method', 'expense_date', 'bank_transaction', 'bank', 'month'];

foreach ($fields as $field) {
	if (isset($post->$field) && nn($post->$field)) {
		$object->$field = $post->$field;
	}
}

$account = R::load("expense_account", $object->accountid);

// vd($account);

$object->accountpath = $account->path;
$object->company = $account->company;
$object->branch_id = $branch_id;

if (METHOD == "add") {
	$object->tran_type = 'Debit';
	$object->entry_by = uid();
	$object->entry_time = now();
}
if (METHOD == "edit") {
	$object->modify_by = uid();
	$object->modify_time = now();
}



if (isset($post->workers)) {
	foreach ($post->workers as $key => $wid) {
		$invoice = R::findOne("invoice", "customer_id=? AND entry_by=0 ORDER BY id DESC", [$wid]);

		if ($invoice) {
			$invoice->note = $post->note_text;
			$invoice->note_color = $post->note_color;

			R::store($invoice);

			$invoice_notes = R::dispense("invoice_notes");
			$invoice_notes->note = $post->note_text;
			$invoice_notes->color = $post->note_color;
			$invoice_notes->invoice = $invoice->id;
			$invoice_notes->entry_time = now();
			$invoice_notes->entry_by = uid();

			R::store($invoice_notes);
		}

		// vd($worker_invoice);
	}
	// dd($post->workers);
}

R::store($object);

if (isset($post->is_investment) && $post->is_investment == '1') {
	$investmentId = createFormInvestmentEntry(
		$object->expense_date,
		$object->amount,
		$object->particulars,
		$object->payment_method,
		(int) ($object->investment_id ?? 0)
	);
	if ($investmentId > 0 && (int) ($object->investment_id ?? 0) !== $investmentId) {
		$object->investment_id = $investmentId;
		R::store($object);
	}
}

if (isset($post->bank_transaction)) {
	update("bank_transaction_item", "expense_entry=$object->id", "id=$post->bank_transaction");
}


// dd($object);
?>