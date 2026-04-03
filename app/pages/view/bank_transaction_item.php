
<?php
if($id){
	print "<table class='table table-responsive table-striped table-bordered table-detailed-view'>
		<tr><th>".str("Bank Transaction")."</th><td> $object->bank_transaction</td></tr>
		<tr><th>".str("Date")."</th><td> $object->date</td></tr>
		<tr><th>".str("Description")."</th><td> $object->description</td></tr>
		<tr><th>".str("Credit")."</th><td> $object->credit</td></tr>
		<tr><th>".str("Debit")."</th><td> $object->debit</td></tr>
		<tr><th>".str("Worker")."</th><td> $object->worker</td></tr>
		<tr><th>".str("Passport")."</th><td> $object->passport</td></tr>
		<tr><th>".str("Company")."</th><td> $object->company</td></tr>
		<tr><th>".str("Company Name")."</th><td> $object->company_name</td></tr>
		<tr><th>".str("Expense")."</th><td> $object->expense</td></tr>
		<tr><th>".str("Particulars")."</th><td> $object->particulars</td></tr>
		<tr><th>".str("Status")."</th><td> $object->status</td></tr>
		<tr><th>".str("Trash")."</th><td> $object->trash</td></tr>
		<tr><th>".str("Removed By")."</th><td> $object->removed_by</td></tr>
		<tr><th>".str("Removed At")."</th><td> $object->removed_at</td></tr>
  </table>";
  back();
} else{
	//$page = is("page", 1, "", FALSE);
	$page = is("page", 1);
	$offset = 20;
	     
	    $filter = "trash=0";
	    openFilterForm("get");
	    print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
	    $dateFrom = isset($get->dateFrom)?$get->dateFrom:today();
	    $dateTo = isset($get->dateTo)?$get->dateTo:today();
	    joinFilter($filter, "`date` BETWEEN '$dateFrom' AND '$dateTo'");
	    print str("Date")." <input type='date' name='dateFrom' value='$dateFrom' class='form-control-fluid' /> to <input type='date' name='dateTo' value='$dateTo' class='form-control-fluid' /> ";
	    $description = isf("description", "description", $filter, $get);
	    print str("Description")." <input type='text' name='description' value='$description' class='form-control-fluid' /> ";
	    $company_name = isf("company_name", "company_name", $filter, $get);
	    print str("Company Name")." <input type='text' name='company_name' value='$company_name' class='form-control-fluid' /> ";
	    closeFilterForm();
	
	$nor = num_rows("a.*", "bank_transaction_item a", "trash=0".(nn($filter)?" AND $filter":""));
	$nop = ceil($nor/$offset);

	$start = ($page-1)*$offset;
	$bank_transaction_items = select("a.*", "bank_transaction_item a", "trash=0".(nn($filter)?" AND $filter":""), "LIMIT $start, $offset");
	print "<hr>";
	print "<table align='center' class='table table-responsive table-striped'>
	<thead><tr><th>#</th><th>".str("Bank Transaction")."</th><th>".str("Date")."</th><th>".str("Description")."</th><th>".str("Credit")."</th><th>".str("Debit")."</th><th>".str("Worker")."</th><th>".str("Passport")."</th><th>".str("Company")."</th><th>".str("Company Name")."</th><th>".str("Expense")."</th><th>".str("Particulars")."</th><th>".str("Status")."</th><th>".options2("", "", array("add"))."</th></tr></thead>
	    <tbody>";

	$i = $start + 1;
	while($bank_transaction_item = mysqli_fetch_object($bank_transaction_items)){
		print "<tr><td><a href='view/$bank_transaction_item->id'>$i</a></td>
			<td>$bank_transaction_item->bank_transaction</td>
			<td>$bank_transaction_item->date</td>
			<td>".stripslashes($bank_transaction_item->description)."</td>
			<td>$bank_transaction_item->credit</td>
			<td>$bank_transaction_item->debit</td>
			<td>$bank_transaction_item->worker</td>
			<td>$bank_transaction_item->passport</td>
			<td>$bank_transaction_item->company</td>
			<td>$bank_transaction_item->company_name</td>
			<td>$bank_transaction_item->expense</td>
			<td>".stripslashes($bank_transaction_item->particulars)."</td>
			<td>".($bank_transaction_item->status?"Yes":"No")."</td>
			<td>".options2("", $bank_transaction_item->id, array("edit", "remove","erase"))."</td></tr>";
		$i++;
	}
	print "</tbody>
	<tfoot>";
	print paging(14, $nop, $nor, $page);
	print "</tfoot>
	</table>";
}
?>