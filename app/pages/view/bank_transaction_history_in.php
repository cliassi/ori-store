<style type="text/css">
	.nav-tabs>li.active>a{
		color: #fff !important;
		background-color: #44d49d !important;
	}
	.description{
		overflow: hidden;
		white-space: nowrap;
		border-right: solid 1px #ccc;
	}

	input[type=checkbox], input[type=radio]{
		outline: 2px solid;
    margin-left: 5%;
  }
  .fa-check-circle{
  	font-size: 1.6em;
  }
  td.particulars{
	white-space: pre-line !important;
  }
</style>
<?php
$uaccs = ['superadmin', 'apple', 'orange', 'Sagor'];
$id = ID;

print "<h1 class='alert alert-success text-center'>".getName('bank', $id, 'account_name')." Statement</h1>";
if(isset($post->trans)){
	if($post->tab == 'i'){
		foreach ($post->trans as $key => $index) {
			$worker = isset($post->{"worker_$index"}) ? $post->{"worker_$index"} : false;
			$passport = isset($post->{"passport_$index"}) ? $post->{"passport_$index"} : false;
			$company = isset($post->{"company_$index"}) ? $post->{"company_$index"} : false;
			$company_name = isset($post->{"company_name_$index"}) ? $post->{"company_name_$index"} : '';
			$particulars = isset($post->{"particulars_$index"}) ? $post->{"particulars_$index"} : '';
			update("bank_transaction_item", ($worker ? "`worker`='$worker', " : "").($company ? "`company`='$company', " : "").($passport ? "`passport`='$passport', " : "").($company_name ? "`company_name`='$company_name', " : "").($particulars ? "`particulars`='$particulars'" : "")."", "id=$index");
		}
	} elseif($post->tab == 'o'){
		foreach ($post->trans as $key => $index) {
			if(isset($post->{"particulars_$index"})){
				$worker = isset($post->{"worker_$index"}) ? $post->{"worker_$index"} : false;
				$passport = isset($post->{"passport_$index"}) ? $post->{"passport_$index"} : false;
				$company = isset($post->{"company_$index"}) ? $post->{"company_$index"} : false;
				$company_name = isset($post->{"company_name_$index"}) ? $post->{"company_name_$index"} : '';
				$particulars = $post->{"particulars_$index"};
				update("bank_transaction_item", ($worker ? "`worker`='$worker', " : "").($company ? "`company`='$company', " : "").($passport ? "`passport`='$passport', " : "").($company_name ? "`company_name`='$company_name', " : "").($particulars ? "`particulars`='$particulars'" : "")."", "id=$index");
				// update("bank_transaction_item", "`particulars`='$particulars'", "id=$index");
			}
		}
	}

	if(isset($post->approvem)){
		foreach ($post->approvem as $key => $value) {
			update("bank_transaction_item", "`status`=1", "id=$value");
			// print "<h1>$value</h1>";
		}
	}

	
}

if(isset($post->update_expense)){
	$expense_entry = R::load("expense_account_entry", $post->expense_entry);
	$expense_entry->bank_tran_id = $post->tran_id;
	$tran = R::load("bank_transaction_item", $post->tran_id);

	$tran->expense_entry = $expense_entry->id;
	$tran->particulars = $expense_entry->particulars;
	
	R::store($tran);
	R::store($expense_entry);

}

// $get->tab = 'i';

	//$page = is("page", 1, "", FALSE);
	$page = isset($get->page) ? $get->page : 1; //is("page", 1);
	$offset = 6000;
	$tab = isset($get->tab) ? $get->tab : 'i';
  $dateFrom = isset($get->dateFrom)?$get->dateFrom:subDay(7);
  $dateTo = isset($get->dateTo)?$get->dateTo:today();

// dd($tab);

if(isUserIn($uaccs) && isset($get->del)){
	if(isset($get->conf)){		
			$object = R::load("bank_transaction_item", $get->del);
			$object->trash = 1;
			$object->removed_by = uid();
			$object->removed_at = now();
			R::store($object);
			redir("?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo");
		} else{
			?>
			<script type="text/javascript">
				if(confirm("Are you sure you want to remove this Bank Transaction?")){
					location.href = "<?php print "?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo&del=$get->del&conf"; ?>";
				} else{
					location.href = "<?php print "?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo"; ?>";	
				}
			</script>
			<?php
		}
}
if(isUserIn(['sagor']) && isset($get->unlink)){
	if(isset($get->conf)){		
			$object = R::load("bank_transaction_item", $get->unlink);
			$expense_entry = R::load("expense_account_entry", $object->expense_entry);
			$expense_entry->bank_tran_id = null;
			R::store($expense_entry);
			// $object->trash = 1;
			// $object->removed_by = uid();
			// $object->removed_at = now();
			$object->expense_entry = null;
			$object->particulars = null;
			R::store($object);


			redir("?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo");
		} else{
			?>
			<script type="text/javascript">
				if(confirm("Are you sure you want to unlink this Bank Transaction?")){
					location.href = "<?php print "?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo&unlink=$get->unlink&conf"; ?>";
				} else{
					location.href = "<?php print "?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo"; ?>";	
				}
			</script>
			<?php
		}
}

if(uid() == 1 && isset($get->approve)){
	update("bank_transaction_item", "`status`=1", "id=$get->approve");
	$url = base64_decode($get->url);
	redir("?$url");
}
  $filter = "b.id=a.bank_transaction AND a.trash=0";
  openFilterForm("get");
  print "<input type='hidden' name='page' value='$page'/>";
  print "<input type='hidden' name='tab' value='$tab'/>";
  joinFilter($filter, "a.`date` BETWEEN '$dateFrom' AND '$dateTo'");
  print "Date <input type='date' name='dateFrom' value='$dateFrom' class='form-control-fluid' /> to <input type='date' name='dateTo' value='$dateTo' class='form-control-fluid' /> ";
  print "<input type='submit' value='Filter' class='form-control btn btn-success w100'/>";
  print "<a class='btn btn-primary' href='../view/$id' style='float:right'><i class='fa fa-plus'></i> Add transaction</a>";
	print "</form>";

	if($id){
    joinFilter($filter, "b.account=$id");
  }
	$userlist = userList();

	joinFilter($filter, $tab == 'i' ? "credit>0" : "debit>0");
	
	$nor = num_rows("a.id", "bank_transaction b, bank_transaction_item a", (nn($filter)?"$filter":""));
	$nop = ceil($nor/$offset);
	print $nop;

	$start = ($page-1)*$offset;
	$bank_transactions = select("a.*", "bank_transaction b, bank_transaction_item a", (nn($filter)?" $filter":""), " ORDER BY a.date LIMIT $start, $offset");
  $accountList = toA("bank");

	print "<hr>";
?>
<ul class="nav nav-tabs">
  <li class="<?php print $tab == 'i' ? 'active' : ''; ?>"><a class="btn <?php print $tab == 'i' ? 'btn-success' : 'btn-info'; ?>" href="?tab=i&dateFrom=<?php print "$dateFrom&dateTo=$dateTo"; ?>" name='tab' value='i'>Credit (In)</a></li>
  <li class="<?php print $tab == 'o' ? 'active' : ''; ?>"><a class="btn <?php print $tab == 'o' ? 'btn-success' : 'btn-info'; ?>" href="?tab=o&dateFrom=<?php print "$dateFrom&dateTo=$dateTo"; ?>" name='tab' value='o'>Debit (Out)</a></li>
</ul>

<?php



openForm();
	print "<input type='hidden' name='tab' value='$tab'>";

	print "<table align='center' class='table table-bordered table-responsive table-striped'>
	<thead><tr><th>#</th><th>Date</th><th>Description</th>";
	// if($tab == 'i'){
		// print "<th>Worker</th><th>Passport</th><th>Company</th>";
	// }
	print "<th style='max-width: 500px; width: 500px;'>Particulars</th><th></th><th class='w100'>Amount</th><th></th><th></th><th style='width: 150px !important'>Status</th>";
	// if(isUserIn($uaccs)){
	if(isUserIn(['superadmin'])){
		print "<th></th>";
	}
	// print "<th>".options2("", "", array("add"))."</th>";
	print "</tr></thead><tbody>";

	$i = $start + 1;
	while($t = mysqli_fetch_object($bank_transactions)){
		print "<tr><td style='width:40px'><input type='hidden' name='trans[]' value='{$t->id}'><a href='view/$t->id'>$i</a></td>
			<td class='w100 date-$t->id'>".df($t->date)."</td>
			<td class='w150 description description-$t->id' title='$t->description'><small>$t->description</small></td>";
		// if($tab == 'i'){
			// if($t->worker || !isUserIn(['lemon', 'orange'])){
			// 	print "<td class='w120'><a href='$appurl/worker/statement/{$t->worker}'>".getName("worker", $t->worker)."</a></td>";
			// 	print "<td class='w120'>$t->passport</td>";
			// 	print "<td class='w120'>$t->company_name</td>";
			// } else{
			// 	print "<td class='w120'>".sop2("worker_{$t->id}", $t->worker, ['optional'=>true, 'extraFields'=>"company,passport", 'class'=>'worker'], "worker")."</td>";
			// 	print "<td class='w120'><input class='form-control w100 passport' name='passport_{$t->id}' value='{$t->passport}'></td>
			// 		<td class='w120'><input type='hidden' class='company' name='company_{$t->id}' value='{$t->company}'><input class='form-control company_name w80' name='company_name_{$t->id}' value='{$t->company_name}'></td>";
			// }
		// }
		print "<td".(uid()==1?' class="particulars tid-'.$t->id.'" ondblclick="javascript:edit('.$t->id.',\''.$t->particulars.'\')"':" class='particulars'").">{$t->particulars}";
		if(nn($t->particulars)) print " Rm ".nf($t->credit > 0 ? $t->credit : $t->debit);
		if(!nn($t->particulars)){ // && isUserIn($uaccs)
			// print "<input class='form-control' class='w150' name='particulars_{$t->id}'>";
			print "<a class='pointer btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-expense' onclick='setId($t->id)'><i class='fa fa-chat'></i></a></td><td>";

		} else{
			print "<input type='hidden' name='particulars_{$t->id}' value='$t->particulars'></td><td><a style='color: grey; float:right' href='?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo&unlink=$t->id'><i class='fa fa-unlink'></i></a>";
		}
		print "</td>";
		print "<td class='text-right'>".nf($t->credit > 0 ? $t->credit : $t->debit)."</td>";
		print "<td id='bti-$t->id' data-value='$t->checked' onclick='toggleCheck($t->id)'>";
		if($t->checked){
			print "<i class='fa fa-check-circle' style='color:green'></i>";
		} else{
			print "<i class='fa fa-check-circle' style='color:grey'></i>";
		}
		print "</td>";
		if(uid()==1){
			if ($t->status == 1){
				print "<td></td><td><span class='btn btn-success btn-sm w80'>Approved</span></td>";
			} else{
				print "<td><input type='checkbox' name='approvem[]' value='$t->id'></td><td><span class='btn btn-warning btn-sm w80' onclick='approve($t->id)'>Pending</span></td>";
			}
		} else{
			if ($t->status == 1){
				print "<td></td><td><span class='btn btn-success btn-sm w80'>Approved</span></td>";
			} else{
				print "<td></td><td><span class='btn btn-warning btn-sm w80'>Pending</span></td>";
			}
		}
		if(isUserIn(['superadmin', 'orange'])){
			print "<td style='width:170px'>";
			// if(!$t->hotel_expense && !$t->expense_entry){
			// 	print "<a href='/app/expense_account_entry/add?bt=$t->id' target='_blank' class='btn btn-warning btn-sm'><i class='fa fa-money'></i> Expense</a>
			// 	<a href='/app/?page=3&add_bt=$t->id' target='_blank' class='btn btn-info btn-sm'><i class='fa fa-money'></i>Hotel Expense</a>";
			// }
			if(isUserIn(['superadmin'])){
				print "
					<a href='/app/bank_transaction_item/edit/$t->id' target='_blank' class='btn btn-primary btn-sm'><i class='fa fa-edit'></i> Edit</a>
					<a href='?tab=$tab&dateFrom=$dateFrom&dateTo=$dateTo&del=$t->id' class='btn btn-danger btn-sm'><i class='fa fa-trash'></i> Delete</a>";
			}
			print "</td>";
		}  else{
			print "<td></td>";
		}
		print "</tr>";
		$i++;
	}
	print "</tbody>
	<tfoot>";
	print paging(11, $nop, $nor, $page);
	print "</tfoot>
	</table>";


print "<hr />
		  	<div class='row'><div align='center' class='col-md-3'></div><div align='center' class='col-md-3'><button type='submit' name='save' class='form-control btn btn-success'>Save</button></div>
		<div align='center' class='col-md-3'><a type='reset' class='form-control btn btn-warning' href='?'>Reset</a></div>
		</div>
		</form><br><br>";

$listOfCompanies = R::find("company");
$companies = [];
foreach ($listOfCompanies as $key => $company) {
	array_push($companies, ['id'=>$company->id, 'name'=>$company->name]);
}
?>

<div class="modal fade" id="modal-expense" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="tran_id" id="tran_id">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal">&times;</button>
	        <h4 class="modal-title"></h4>
	      </div>
	      <div class="modal-body lft">
	      	<?php
						// $ee = R::find("expense_account_entry", "bank_tran_id IS NULL AND bank=? AND expense_date BETWEEN ? AND ?", [$id, $dateFrom, $dateTo]);
						$ee = R::find("expense_account_entry", "bank_tran_id IS NULL AND bank=? ORDER BY entry_time", [$id]);
						foreach($ee as $key=>$e){
							print "<div><input type='radio' name='expense_entry' value='$e->id'>$e->particulars";
							//if(strpos($e->particulars, 'Mardhiyyah') !== FALSE) die($e->particulars);
							if(strpos($e->particulars, 'Hotel') === FALSE){
								if(substr(trim($e->particulars),-2) != 'Rm'){
								 print " Rm $e->amount";
								} else{
								 print " $e->amount";
								}
							}
							print "</div>";
						}
						// var_dump($ee);
					?>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="update_expense">Save</button>
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>


<script type="text/javascript">
	function setId(id){
		var title = "<div>" + $(".date-" + id).html() + "</div><div>" +  $(".description-" + id).text() + "</div>";
		$(".modal-title").html(title);
		$("#tran_id").val(id);
	}
</script>