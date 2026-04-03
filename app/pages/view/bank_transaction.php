<?php
$id = ID;
if(isset($get->reload) && isset($id)){
	$count = 1;
	$msg = "";
	$transactions = explode(PHP_EOL, $object->transactions);
	// dd($object);
		foreach($transactions as $t){
			$tr = str_replace("\t", "|", $t);
			$parts = explode("|", $tr);
			// $parts[2] = digitOnly($parts[2]);
			var_dump($parts);
			// $parts = preg_split("/\t+/", $t);
			// $parts[2] = digitOnly($parts[2]);
			// var_dump($parts);
			$debit = $credit = 0;
			if(nn($parts[3])){
				$credit = digitOnly($parts[3]) + 0;
			} else{
				$debit = digitOnly($parts[2]) + 0;
			}
			// if(!exists("bank_transaction_item", "`date`='".date("Y-m-d", strtotime($parts[0]))."' AND description='{$parts[1]}' AND credit=$credit AND debit=$debit")){
				print "<div class='alert alert-default'>";
				print "<h5>$t</h5>";
				insert("bank_transaction_item", "`bank_transaction`, `date`, `description`, `credit`, `debit`", "$object->id, '".date("Y-m-d", strtotime($parts[0]))."', '{$parts[1]}', '$credit', '$debit'");
				print "</div>";
				$msg .= "<div class='alert alert-success'><b>{$count}. Success :</b> <u><i>$t</i></u></div>";
			// } else{
			// 	$msg .= "<div class='alert alert-danger'><b>{$count}. Already Exists :</b> <u><i>$t</i></u></div>";
			// }
			$count++;
		}
}
else{

	//$page = is("page", 1, "", FALSE);
print "<h1 class='alert alert-success text-center'>".getName('bank', $id, 'account_name')." - Add Transaction</h1>";
	$page = is("page", 1);
	$offset = 20;
	     
	    $filter = "trash=0";
	    openFilterForm("get");
	    print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
	    $dateFrom = isset($get->dateFrom)?$get->dateFrom:subDay(7);
	    $dateTo = isset($get->dateTo)?$get->dateTo:today();
	    joinFilter($filter, "`date` BETWEEN '$dateFrom' AND '$dateTo'");
	    if($id){
		    joinFilter($filter, "account=$id");
	    }
	    print "Date <input type='date' name='dateFrom' value='$dateFrom' class='form-control-fluid' /> to <input type='date' name='dateTo' value='$dateTo' class='form-control-fluid' /> ";
	    closeFilterForm();
	$userlist = userList();
	
	$nor = num_rows("a.*", "bank_transaction a", "trash=0".(nn($filter)?" AND $filter":""));
	$nop = ceil($nor/$offset);

	$start = ($page-1)*$offset;
	$bank_transactions = select("a.*, (SELECT CONCAT(count(id),':',sum(credit),':',sum(debit)) FROM bank_transaction_item b WHERE a.id=b.bank_transaction) counts", "bank_transaction a", "trash=0".(nn($filter)?" AND $filter":""), "LIMIT $start, $offset");
  $accountList = toA("bank");
    	
	print "<hr>";
	//options2("", "", array("add"))
	print "<table align='center' class='table table-responsive table-striped'>
	<thead><tr><th>#</th><th>Date</th><th>Account</th><th>Count</th><th>Debit</th><th>Credit</th><th>Entry By</th><th>Modify By</th><th>".(hasAccess('','add') ? '<a target="_top" href="../../bank_transaction/add/'.$id.'"><i class="fa fa-file"></i></a>':'')."</th></tr></thead>
	    <tbody>";

	$i = $start + 1;
	while($bank_transaction = mysqli_fetch_object($bank_transactions)){
		$details = explode(":", $bank_transaction->counts);
		print "<tr><td><a href='view/$bank_transaction->id'>$i</a></td>
			<td>$bank_transaction->date</td>
			<td>{$accountList[$bank_transaction->account]}</td>
			<td>{$details[0]}</td>
			<td>".nf(isset($details[2])?$details[2]:0)."</td>
			<td>".nf(isset($details[1])?$details[1]:0)."</td>
			<td title='$bank_transaction->entry_time'>{$userlist[$bank_transaction->entry_by]}</td>
			<td title='$bank_transaction->modify_time'>{$userlist[$bank_transaction->modify_by]}</td>
			<td>".options2("", $bank_transaction->id, array("edit", "remove","erase"))."</td></tr>";
		$i++;
	}
	print "</tbody>
	<tfoot>";
	print paging(11, $nop, $nor, $page);
	print "</tfoot>
	</table>";
}
?>