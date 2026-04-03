<?php 
$fields = ['accountid','amount','particulars','remarks','entry_type','entry_id','status','tran_type', 'payment_method', 'expense_date', 'bank_transaction', 'bank', 'month'];

foreach ($fields as $field) {
	if(isset($post->$field) && nn($post->$field)) {
		$object->$field = $post->$field;
	}
}

$account = R::load("expense_account", $object->accountid);

// vd($account);

$object->accountpath = $account->path;
$object->company = $account->company;
$object->branch_id = $branch_id;

if(METHOD=="add") {
	$object->tran_type = 'Debit';
	$object->entry_by = uid();
	$object->entry_time = now();
}
if(METHOD=="edit") {
	$object->modify_by = uid();
	$object->modify_time = now();
}



if(isset($post->workers)){
	foreach ($post->workers as $key => $wid) {
		$invoice = R::findOne("invoice", "customer_id=? AND entry_by=0 ORDER BY id DESC", [$wid]);

		if($invoice){
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
if(isset($post->bank_transaction)){
	update("bank_transaction_item", "expense_entry=$object->id", "id=$post->bank_transaction");
}


// dd($object);
?>