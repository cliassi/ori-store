<?php
if(isset($post->approve)){
	$obj = R::load('expense_account_entry', $post->approve);
	$obj->status = 'Approved';
	R::store($obj);
}
	//$page = is("page", 1, "", FALSE);
	$page = is("page", 1);
	$offset = 1000;


	$d = isset($get->d)?$get->d:firstDate();
	$t = isset($get->t)?$get->t:today();
	$get->pm = isset($get->pm)?$get->pm: '';
	     
  if(!isset($get->month)){
  	$filter = "expense_date BETWEEN '$d' AND '$t 23:59:59'";
  }
joinFilter($filter, "branch_id = $branch_id");
  openFilterForm("get");
  print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
  if(isset($get->accountid) && nn($get->accountid)){
  	joinFilter($filter, "accountpath LIKE '%/".$get->accountid."/%'");
  } else{
  	$get->accountid = '';
  }
  if(isset($get->pm) && nn($get->pm)){
  	joinFilter($filter, "payment_method='$get->pm'");
  }
	print str("Account")." ".sop2('accountid', $get->accountid, ['optional'=>true], 'expense_account');				
	// function selectEnum($attribute, $table, $field, $select="", $only=array(), $sorted=true, $upper=false, $optional=false, $optional_vlaue=''){

	print str("Method")." ".selectEnum("name='pm' class='form-control-fluid'", 'expense_account_entry', 'payment_method', $get->pm, [], true, false, true);		
  if(isset($get->month)){
  	joinFilter($filter, "month = '".$get->month."'");
  	print "<input type='hidden' name='month' value='$get->month'>";
  } else{			
  	print "Date <input type='date' name='d' value='$d' class=' form-control-fluid w120' /> to <input type='date' name='t' value='$t' class=' form-control-fluid w120' />";
  }

  $particulars = isf("particulars", "particulars", $filter, $get);
  print str("Particulars")." <input type='text' name='particulars' value='$particulars' class='form-control-fluid' /> ";
  closeFilterForm();

	print "<a class='frht btn btn-danger' href='/app/expense_account/view'>Summary</a>";

	$userlist = userList();
	
	$nor = num_rows("a.*", "expense_account_entry a", "$filter");
	$nop = ceil($nor/$offset);

	$start = ($page-1)*$offset;
	$expense_account_entrys = selectp("a.*", "expense_account_entry a", "$filter", "LIMIT $start, $offset");
  $accountidList = toA("expense_account");
    	
	print "<hr>";
	print "<table align='center' class='table table-responsive table-bordered table-striped' style='width:100%'>
	<thead><tr><th>#</th><th>Date</th><th>".str("Account")."</th><th>".str("Particulars")."</th><th>".str("Remarks")."</th><th>".str("Entry Type")."</th><th>Credit</th><th>Debit</th><th>Payment Method</th><th>".str("Entry By")."</th><th>Status</th><th>".options2("", "", array("add"))."</th></tr></thead>
	    <tbody>";

	$i = $start + 1;
	while($expense_account_entry = mysqli_fetch_object($expense_account_entrys)){
		print "<tr>
			<td><a href='view/$expense_account_entry->id'>$i</a></td>
			<td>".(nn($expense_account_entry->expense_date) ? dfh($expense_account_entry->expense_date).' ' : '')."</td>
			<td>{$accountidList[$expense_account_entry->accountid]}</td>
			<td style='max-width: 500px; white-space: wrap;'>".stripslashes($expense_account_entry->particulars)."</td>
			<td>".stripslashes($expense_account_entry->remarks)."</td>
			<td>$expense_account_entry->entry_type</td>";
		if($expense_account_entry->tran_type == 'Credit'){
			print "<td>".nf($expense_account_entry->amount)."</td><td></td>";
			sum('credit', $expense_account_entry->amount);
		} else{
			print "<td></td><td>".nf($expense_account_entry->amount)."</td>";
			sum('debit', $expense_account_entry->amount);
		}
	    
		print "<td>$expense_account_entry->payment_method</td><td title='{$userlist[$expense_account_entry->modify_by]} $expense_account_entry->modify_time'>{$userlist[$expense_account_entry->entry_by]}<br>
				".df($expense_account_entry->entry_time)."</td>";
		if(isUserIn([])){
			if($expense_account_entry->status == 'Pending'){
        print "<td><form method='post'><input type='hidden' name='approve' value='$expense_account_entry->id'><button class='btn btn-warning'>$expense_account_entry->status</button></form></td>";
			} else{
				print "<td><a class='btn btn-success'>$expense_account_entry->status</a></td>";
			}
		} else{
			if($expense_account_entry->status == 'Pending'){
				print "<td><a class='btn btn-warning'>$expense_account_entry->status</a></td>";
			} else{
				print "<td><a class='btn btn-success'>$expense_account_entry->status</a></td>";
			}
		}
		print "<td>";
		if(uid()==1){
			print "<a href='edit/$expense_account_entry->id'><i class='fas fa-edit'></i></a>".space(3);
			print "<a href='erase/$expense_account_entry->id'><i class='fas fa-trash'></i></a>";
		} elseif(isUserIn(['orange'])){
			if($expense_account_entry->status == 'Pending'){
				print "<a href='edit/$expense_account_entry->id'>Edit</a>";
			}
		}
		print "</td></tr>";
		$i++;
	}
	print "</tbody>
	<tfoot>
	<tr>
		<th colspan='6' class='rht'>TOTAL</th><th class='rht'>".nf(sum('credit'))."</th><th class='rht'>".nf(sum('debit'))."</th><th></th><th></th><th></th></tr>
	</tr>";
	print paging(14, $nop, $nor, $page);
	print "</tfoot>
	</table>";