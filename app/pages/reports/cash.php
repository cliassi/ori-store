<style type="text/css">
	.highlight{
		font-size: 18px;
		color: #337ab7;
	}
	.highlight2, .highlight2 a{
		font-size: 18px;
		color: #000;
	}
	.bd_handover .particulars{
		color: #5cb85c;
		font-weight: 700;
	}
	input[type=checkbox], input[type=radio]{
		outline: 2px solid;
    margin-left: 5%;
  }

  .checked-pending,.checked-banned{
  	height: 20px;
    opacity: .5;
    padding-left: 20px;
    filter: grayscale(1);
    cursor: pointer;
  }
  .checked-pending:hover,.checked-banned:hover{
    filter: grayscale(.2);
    transform: scale(1.1);
  }
  .checked{
  	height: 20px;
    padding-left: 20px;
  }
  tr.expense_account_entry td{
  	border: solid 1px lightgreen !important;
  }
</style>
<style type="text/css">
	.large{
    font-size: 14px;
    text-transform: uppercase;
    font-weight: 700;
	}
	th{
		text-align: center;
	}
	/* Chrome, Safari, Edge, Opera */
	input::-webkit-outer-spin-button,
	input::-webkit-inner-spin-button {
	  -webkit-appearance: none;
	  margin: 0;
	}

	/* Firefox */
	input[type=number] {
	  -moz-appearance: textfield;
	}
</style>
<style type="text/css">
	div span{
		display: inline-block;
	}
	.special,.special a{
		 font-size: 2rem; font-weight: 700; color: magenta !important; background: cyan;
	}
	#hints{
		padding-left: 5px;
	}
	input[type="radio"]{
		padding-right: 5px;
		margin-right: 5px;
	}
	.spc{
		font-weight: 700;
		border: solid 1px #aaf;
		padding: 3px;
		border-radius: 3px;
	}
	.spc-1{
		background-color: palegreen;
	}
	.spc-2{
		background-color: aqua;
	}
	.spc-3{
		background-color: yellow;
	}
	.spc-4{
		background-color: pink;
	}
	.attendance-sheet div{
		margin-top: 10px;
		background-color: #efefef;
		border-radius: 5px;
		box-shadow: 0 0 3px #000;
		padding: 10px 30px;
		text-align: center;
		cursor: pointer;
	}
	.attendance-sheet i{
		position: absolute;
    right: 20px;
/*    top: 16px;*/
	}
	.btn-A{
		background-color: #5bc0de;
    border-color: #46b8da;
    color: #fff;
	}
	.btn-B{
		background-color: #337ab7;
    border-color: #2e6da4;
    color: #fff;
	}
	.btn-C{
		background-color: #f0ad4e;
    border-color: #eea236;
    color: #fff;
	}
	.btn-D{
    background-color: #d9534f;
    border-color: #d43f3a;
    color: #fff;
	}
	.btn-E{
		background-color: #5bc0de;
    border-color: #46b8da;
	}
	.notice{
    color: orangered;
    font-weight: 700;
    margin-bottom: 10px;
	}
</style>
<?php
$get->petty_cash_details = 1;
$get->cw=1;
if(isset($get->company))
$id = $get->company;

if(isset($post->cash_edit_save)){
	$cashRedirectD = isset($get->d) ? (string)$get->d : '';
	$cashRedirectT = isset($get->t) ? (string)$get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '') ? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT)) : '?';
	
	$editSource = isset($post->cash_edit_source) ? (string)$post->cash_edit_source : '';
	$editId = isset($post->cash_edit_id) ? (int)$post->cash_edit_id : 0;
	$editDate = isset($post->cash_edit_date) ? (string)$post->cash_edit_date : '';
	$editParticulars = isset($post->cash_edit_particulars) ? (string)$post->cash_edit_particulars : '';
	$editAmount = isset($post->cash_edit_amount) ? (float)$post->cash_edit_amount : 0;
	
	$canEditCash = uid() == 1 || isUserIn(['superadmin', 'amla', 'orange']);
	if(!$canEditCash) {
		redir($cashRedirectUrl);
		exit;
	}
	
	if($editId > 0 && !empty($editSource)) {
		if(in_array($editSource, ['cw_cash', 'cw_bank', 'cw_cash_withdraw'], true)) {
			$bean = R::load($editSource, $editId);
			if($bean && $bean->id) {
				if(!empty($editDate)) {
					$bean->date = $editDate;
				}
				if(!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif(in_array($editSource, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$bean = R::load('expense_account_entry', $editId);
			if($bean && $bean->id) {
				if(!empty($editDate)) {
					$bean->expense_date = $editDate;
				}
				if(!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif(in_array($editSource, ['payment_cash', 'payment_bank'], true)) {
			$bean = R::load('payment', $editId);
			if($bean && $bean->id) {
				if(!empty($editDate)) {
					$bean->date = $editDate;
				}
				if(!empty($editParticulars)) {
					$bean->description = $editParticulars;
				}
				if($editAmount > 0) {
					$bean->amount = -abs($editAmount);
				}
				if(isset($post->cash_edit_payment_method) && in_array($post->cash_edit_payment_method, ['Cash', 'Bank'], true)) {
					$bean->payment_method = $post->cash_edit_payment_method;
				}
				R::store($bean);
			}
		} elseif($editSource === 'bd_handover') {
			$bean = R::load('bd_handover', $editId);
			if($bean && $bean->id) {
				if(!empty($editDate)) {
					$bean->date = $editDate;
				}
				if($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif($editSource === 'investment') {
			$bean = R::load('investment', $editId);
			if($bean && $bean->id) {
				if(!empty($editDate)) {
					$bean->date = $editDate;
				}
				if(!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		}
	}
	redir($cashRedirectUrl);
}

if(isset($post->save_carwash)){
	$carwash = R::dispense("cw_sites");
	$carwash->name = $post->name;
	$carwash->entry_by = uid();
	$carwash->branch_id = $branch_id;
	R::store($carwash);
}

if(isset($post->save)){
	$object = R::dispense("cw_customer");
	$function = 'add';
	$post->company = $id;
	$post->branch_id = $branch_id;
	require_once("model/cw_customer.php");
}


if(isset($post->save_cash)){
	$deposit = R::dispense("cw_cash");
	$deposit->date = isset($post->date)?$post->date:today();
	$deposit->particulars = $post->particulars;
	$deposit->branch_id = $branch_id;
	$deposit->amount = $post->amount;
	$deposit->company = $get->cw;
	$deposit->entry_by = uid();
	$deposit->entry_time = now();
	R::store($deposit);
	redir("?");
}

if(isset($post->save_outlet)){
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date3)?$post->date3:today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = 0 - $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->branch_id = $branch_id;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_outlet = R::dispense("cw_outlet");
	$cw_outlet->date = isset($post->date3)?$post->date3:today();
	$cw_outlet->particulars = $post->particulars;
	$cw_outlet->amount = $post->amount;
	$cw_outlet->company = $get->cw;
	$cw_outlet->branch_id = $branch_id;
	$cw_outlet->entry_by = uid();
	$cw_outlet->entry_time = now();
	$cw_outlet->cash_id = $cw_cash->id;
	R::store($cw_outlet);
	redir("?");
}

if(isset($post->save_bank)){
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date2)?$post->date2:today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = 0 - $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->branch_id = $branch_id;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2)?$post->date2:today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = $post->amount;
	$cw_bank->company = $get->cw;
	$cw_bank->branch_id = $branch_id;
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	$cw_bank->cash_id = $cw_cash->id;
	R::store($cw_bank);
	redir("?");
}

if(isset($post->save_bank_deposit)){
	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2)?$post->date2:today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = $post->amount;
	$cw_bank->company = $get->cw;
	$cw_bank->branch_id = $branch_id;
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	R::store($cw_bank);
	redir("?");
}


if(isset($post->save_investment)){
	$investment = R::dispense("investment");
	$investment->date = isset($post->date) ? $post->date : today();
	$investment->particulars = $post->particulars;
	$investment->amount = $post->amount;
	$investment->payment_method = isset($post->payment_method) ? $post->payment_method : 'Bank';
	$investment->created_by = uid();
	$investment->created_at = now();
	$investment->trash = 0;
	R::store($investment);
	redir("?d=$d&t=$t");
}

if(isset($post->withdraw)){
	// dd($post);
	$withdraw = R::dispense("cw_cash_withdraw");
	$withdraw->particulars = $post->particulars;
	$withdraw->date = isset($post->date2)?(nn($post->date2)?$post->date2:today()):today();
	$withdraw->amount = 0 - $post->amount;
	$withdraw->company = $get->cw;
	$withdraw->branch_id = $branch_id;
	$withdraw->entry_by = uid();
	$withdraw->entry_time = now();
	R::store($withdraw);
	redir("?");
}

	$d = isset($get->d)?$get->d:subDay(5);
	$t = isset($get->t)?$get->t:today();


	// dd([uid(), isset($get->approve) ,isset($get->id)]);
if(uid()==1 && isset($get->approve) && isset($get->id)){
	if(in_array($get->approve, ['payment_bank', 'payment_cash'])){
		$get->approve = 'payment';
	}
	if(in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash', 'account_entry_cash'])){
		$get->approve = 'expense_account_entry';
	}
	$object = R::load($get->approve, $get->id);
	$object->status = 'Approved';
	// $object->trash = 1;
	R::store($object);
	redir("?d=$d&t=$t");
}	
if(uid()==1 && isset($post->approvem)){

	foreach($post->approvem as $tv){
		$type_id = explode("-", $tv);
		$get->approve = $type_id[0];
		$get->id = $type_id[1];
		if(in_array($get->approve, ['payment_bank', 'payment_cash'])){
			$get->approve = 'payment';
		}
		if(in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash', 'account_entry_cash'])){
			$get->approve = 'expense_account_entry';
		}
		$object = R::load($get->approve, $get->id);
		$object->status = 'Approved';
		// $object->trash = 1;
		R::store($object);
	}
	redir("?d=$d&t=$t");
}	


if(isset($get->token)){
	$token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
	if($token){
		R::trash($token);
		if(uid()==1 && isset($get->approve) && isset($get->id)){
			if(in_array($get->approve, ['payment_bank', 'payment_cash'])){
				$get->approve = 'payment';
			}
			if(in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash'])){
				$get->approve = 'expense_account_entry';
			}
			$object = R::load($get->approve, $get->id);
			$object->status = 'Approved';
			// $object->trash = 1;
			R::store($object);
			redir("?d=$d&t=$t");
		}

		if(uid()==1 && isset($get->del) && isset($get->id)){
			if(in_array($get->del, ['payment_bank', 'payment_cash'])){
				$get->del = 'payment';
			}
			if(in_array($get->del, ['expense_account_entry_bank', 'expense_account_entry_cash'])){
				$get->del = 'expense_account_entry';
			}
			$object = R::load($get->del, $get->id);
			if($get->del == 'cw_bank'){
				$cw_cash = R::load("cw_cash", $object->cash_id);
				R::trash($cw_cash);
			}
			// $object->trash = 1;
			// R::store($object);
			R::trash($object);
			redir("?d=$d&t=$t");
		}

		if(isset($get->del)){
			if($get->del == 'expense_account_entry'){
				$ee = R::load("expense_account_entry", $get->id);
				R::trash($ee);

			} elseif($get->del == 'cw_cash'){
				$ee = R::load("cw_cash", $get->id);
				$be = R::findOne("cw_bank", "cash_id=?", [$get->id]);
				if($be){
					R::trash($be);
				}
				R::trash($ee);
			} elseif($get->del == 'cw_cash_withdraw'){
				$ee = R::load("cw_cash_withdraw", $get->id);
				R::trash($ee);
			} elseif($get->del == 'cw_bank'){
				$ee = R::load("cw_bank", $get->id);
				if($ee->cash_id){
					$be = R::findOne("cw_cash", "id=?", [$ee->cash_id]);
					if($be){
						R::trash($be);
					}
				}
				R::trash($ee);
			} elseif($get->del == 'cw_cash_withdraw'){
				$ee = R::load("cw_cash_withdraw", $get->id);
				R::trash($ee);
			}
			redir("?d=$d&t=$t");
		}
	} else{
		redir("?d=$d&t=$t");
	}
}



if(isset($get->copy) && $get->copy > 0){
	$cus = R::load("cw_customer", $get->copy);

	$cus_new = R::dispense("cw_customer");
	$cus_new->name = $cus->name;
	$cus_new->phone = $cus->phone;
	$cus_new->brand = $cus->brand;
	$cus_new->model = $cus->model;
	$cus_new->number = $cus->number;
	$cus_new->roadtax = $cus->roadtax;
	$cus_new->next_service_date = $cus->next_service_date;
	$cus_new->photo_file = $cus->photo_file;
	$cus_new->company = $id;
	$cus_new->entry_by = uid();
	$cus_new->entry_time = now();

	R::store($cus_new);

	redir("/app/cw_customer/view/$cus_new->id");

}

if(isset($get->cw)){
	$company = R::load("cw_company", $get->cw); 
	print "<h3><strong>$company->name</strong> Petty Cash Report</h3>";
	$d = isset($get->d)?$get->d:addDay(-5);
	$t = isset($get->t)?$get->t:today();
	print "<div class='row'>";
	print "<div class='col-md-5'>";
	print "<form>";
	print "<input type='hidden' name='page' value='18'>";
	print "<input type='hidden' name='cw' value='$get->cw'>";
	print "<input type='hidden' name='petty_cash_details' value=''>";
	print "Date ".dp2("d", $d)." - ".dp2("t", $t)." <button class='btn btn-primary'>Filter</button>";
	print "</form>";
	print "</div>";
	print "<div class='col-md-5'>";
	if(isUserIn(['superadmin','amla','orange', 'parvez'])){
		print "<a data-bs-toggle='modal' data-bs-target='.invest' class='btn btn-warning'>Asset Invest</a>".space(5);
		print "<a data-bs-toggle='modal' data-bs-target='.withdraw' class='btn btn-primary'>Cash Withdraw</a>".space(5);
 	} 
	if(isUserIn(['superadmin','amla','orange', 'parvez'])){
		print "<a data-bs-toggle='modal' data-bs-target='.cash' class='btn btn-primary'>Add Cash</a>".space(5);
		// print "<a data-bs-toggle='modal' data-bs-target='.bankdeposit' class='btn btn-success'>Bank Deposit</a>".space(5);
		print "<a data-bs-toggle='modal' data-bs-target='.outlet' class='btn btn-secondary'>Petty Cash to Outlet Account</a>";
		// print "<a data-bs-toggle='modal' data-bs-target='.bank' class='btn btn-secondary'>Petty Cash to Bank</a>";
 	} 
	print "</div>";
	print "<div class='col-md-2'>";
	print "<a class='btn btn-sm btn-danger' style='font-size: 1.2rem;' href='/store/expense_account/carwash?company=1&t=capex'>Capex</a>" . space(5);
	print "<a class='btn btn-sm btn-warning' style='font-size: 1.2rem;' href='/store/expense_account/carwash?company=3&t=opex'>Opex</a>";
	print "</div>";
	print "</div>";

							

	$collection = getSum("cw_payment", "amount", "(branch_id = $branch_id OR branch_id IS NULL) AND date<'$d'");
	$handover_cash = getSum("bd_handover", "amount", "(branch_id = $branch_id OR branch_id IS NULL) AND amount>0 AND date<'$d'");
	$handover_bank = getSum("bd_handover", "bank_amount", "(branch_id = $branch_id OR branch_id IS NULL) AND bank_amount>0 AND date<'$d'");
	$withdraw = getSum("cw_cash_withdraw", "amount", "(branch_id = $branch_id OR branch_id IS NULL) AND date<'$d'");
	// $withdraw2 = getSum('cw_cash_withdraw' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_cash_withdraw WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id
			// $expense_entry = getSum("expense_account_entry", "amount", "payment_method='Cash' AND tran_type='Debit' AND company=$company->id AND entry_time<'$d'");
	$expense_entry = getSum("expense_account_entry", "amount", "(branch_id = $branch_id OR branch_id IS NULL) AND tran_type='Debit' AND company=$company->id AND expense_date<'$d'");

	$summary2 = mysqli_fetch_object(select("SELECT 
    (SELECT IFNULL(SUM(amount),0)
        FROM cw_cash
        WHERE (branch_id = $branch_id OR branch_id IS NULL)
          AND company = $company->id
          AND `date` < '$d'
    ) add_cash,

    (SELECT IFNULL(SUM(amount),0)
        FROM bd_handover
        WHERE (branch_id = $branch_id OR branch_id IS NULL)
          AND `date` < '$d'
    ) cash_handover,

    (SELECT IFNULL(SUM(amount),0)
        FROM expense_account_entry
        WHERE (branch_id = $branch_id OR branch_id IS NULL)
          AND company = $company->id
          AND payment_method = 'Cash'
          AND tran_type = 'Debit'
          AND expense_date < '$d'
    ) cash_expense,

    (SELECT IFNULL(SUM(amount),0)
        FROM payment
        WHERE (branch_id = $branch_id OR branch_id IS NULL)
          AND payment_method = 'Cash'
          AND `date` < '$d'
    ) cash_payment,

    (SELECT IFNULL(SUM(amount),0)
        FROM cw_cash_withdraw
        WHERE (branch_id = $branch_id OR branch_id IS NULL)
          AND company = $company->id
          AND `date` < '$d'
    ) withdraw
"));

	$total = $summary2->cash_handover
		+ $summary2->add_cash
		- abs($summary2->withdraw)
		- $summary2->cash_payment
		- $summary2->cash_expense;

	// print "$handover_cash - $withdraw - $cash_expenditure + $loan->total - $bank_deposit - $hotel_payment + $cash - $expense_entry - $hotel_expense";
	//CONCAT(IF(e.expense_date IS NULL, '', DATE_FORMAT(e.entry_time, '%e-%b-%Y')), ' ',e.particulars) 
	//CONCAT(IF(e.expense_date IS NULL, '', DATE_FORMAT(e.entry_time, '%e-%b-%Y')), ' ',e.particulars) 
	$trans = select("SELECT * FROM (
			SELECT '' se, 'bd_handover' source, id, amount, concat(`date`, ' 23:59:59') `date`, created_at entry_time, created_by entry_by, 'Bank & Cash Handover from Daily Collection' particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM bd_handover WHERE branch_id=$branch_id AND (amount>0 OR bank_amount > 0) AND date BETWEEN '$d' AND '$t'
			UNION
			SELECT '' se, 'cw_payment' source, 0 id, SUM(amount) amount, date, entry_time, entry_by, 'Total Bank Collection' particulars, '', '','','', 0 checked FROM cw_payment WHERE branch_id=$branch_id AND amount>0 AND company=$company->id AND (date BETWEEN '$d' AND '$t') AND particulars NOT LIKE '%cash%' GROUP BY DATE
			UNION
			SELECT ea.breadcrumbs se, 'expense_account_entry' source, e.id, e.amount, e.expense_date `date`, e.entry_time, e.entry_by, e.particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM expense_account_entry e LEFT JOIN  expense_account ea ON e.accountid=ea.id WHERE e.branch_id=$branch_id AND e.payment_method='Cash' AND e.tran_type='Debit' AND e.expense_date  BETWEEN '$d 00:00:00' AND '$t 23:59:59'
			UNION
			SELECT ea.breadcrumbs se, 'expense_account_entry_bank' source, e.id, e.amount, e.expense_date `date`, e.entry_time, e.entry_by, e.particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM expense_account_entry e LEFT JOIN  expense_account ea ON e.accountid=ea.id WHERE e.branch_id=$branch_id AND e.payment_method<>'Cash' AND e.tran_type='Debit' AND e.expense_date  BETWEEN '$d 00:00:00' AND '$t 23:59:59'
			UNION
			SELECT '' se, 'cw_cash_withdraw' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_cash_withdraw WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t') AND company=$company->id
			UNION
			SELECT '' se, 'cw_cash' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_cash WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t')  AND company=$company->id AND amount>0
			UNION
			SELECT '' se, 'cw_outlet' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_outlet WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t')  AND company=$company->id
			UNION
			SELECT cash_id se, 'cw_bank' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_bank WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t')  AND company=$company->id
			UNION
			SELECT '' se, 'payment_cash' source, id, amount, `date`, created_at entry_time, created_by entry_by, description particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM payment WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t')  AND payment_method='Cash'
			UNION
			SELECT '' se, 'payment_bank' source, id, amount, `date`, created_at entry_time, created_by entry_by, description particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM payment WHERE  branch_id=$branch_id AND (date BETWEEN '$d' AND '$t')  AND payment_method='Bank'
			UNION
			SELECT payment_method se, 'investment' source, id, amount, `date`, created_at entry_time, created_by entry_by, particulars, '' `status`, '' ref, '' done_by, '' done_time, 0 checked FROM investment WHERE COALESCE(trash,0)=0 AND date BETWEEN '$d' AND '$t'
		) t ORDER BY date");

// print "SELECT 'hotel_statement_worker_payment' source, p.id, p.amount, p.date, p.entry_time, CONCAT('<u>', h.name, '</u> er staff <u>', w.name, '</u>, ', DATE_FORMAT(CONCAT(s.month,'-01'), '%b %Y'), ' maser salary ', p.particulars), IFNULL(p.approved_by, 'Pending') status, '' ref FROM `hotel_statement_worker_payment` p, `hotel_statement_worker` w, `hotel_statement` s, `hotel` h WHERE p.worker=w.id AND w.statement=s.id AND s.hotel=h.id AND p.date>'$hotel_start_date' AND (p.date BETWEEN '$d' AND '$t') AND (p.particulars LIKE 'Petty Cash theke%' OR p.particulars LIKE 'Me2 te%')";

print "<form method='post'>";
	print "<table class='table table-bordered'>";
	// print "<tr><th>No.</th><th>Date</th><th>Particulars</th><th></th><th>User</th><th>Cash In</th><th class='w120'></th><th>Cash Out</th><th class='w120'></th><th>Balance</th><th></th>";

	//<th><a href='add_cash'>Add Cash</a><br><a href='widthdraw'>Cash Withdraw</a></th><th>Invested Capital</th>
	print "<tr><th>No.</th><th>Date</th><th>Particulars</th><th>Cash In</th><th>Cash Out</th><th>Balance</th><th>Manager</th><th>Bank In</th><th>Bank Out</th><th>Balance</th><th>Approval</th>";
	if(uid() == 1){
		print "<th>Del</th>";
	}
	print "</tr>";
	$openingTailColspan = 5 + (uid() == 1 ? 1 : 0);
	print "<tr><th colspan='5'>Opening Balance</th><th>".nf($total)."</th><th colspan='".$openingTailColspan."'></th></tr>";
	$i = 1;
	// vd($opening);
	$userList = userList();
	$userList[0] = '';
	$total_debit = 0;
	$cashInTotal = 0;
	$cashOutTotal = 0;
	$bankInTotal = 0;
	$bankOutTotal = 0;
	while($tr = mysqli_fetch_object($trans)){
		$cashBefore = $total;
		$bankBefore = sum('bank');
		
		$r = "<td title='$tr->entry_time > $tr->status'>".df($tr->date)."</td>";
		$r .= "<td class='particulars pre-wrap'>";

		if($tr->source == 'hotel_statement_worker_payment'){
			$r .= '<img src="assets/verified.png" width="32px">';
		} elseif ($tr->source == 'expenditure' && in_array($tr->ref, [91,92])){
			$r .= '<img src="assets/verified2.png" width="32px">';
		} 
		if($tr->source == 'bd_handover'){
			$petty_cash_report = R::findOne("petty_cash_report", "handover_id=?", [$tr->id]);
			if($petty_cash_report){
				$r .= "<a href='report/petty_cash/$tr->id' target='_blank'>$tr->particulars</a>";
			} else{
				$r .= "$tr->particulars";
			}
		} else{
			$r .= "$tr->particulars";
		}

		if($tr->source == 'expenditure'){
			if(!$tr->checked){
				if(isUserIn(['superadmin'])){
					$r .= "<img src='/app/assets/done.png' class='checked-pending' data-src='$tr->source' data-id='$tr->id'>";
					$r .= "<img src='/app/assets/ban.png' class='checked-banned' data-src='$tr->source' data-id='$tr->id'>";
				} else{
					// $r .= '<img src="/app/assets/done.png" class="checked">';
				}
			} elseif($tr->checked == 1){
				$r .= '<img src="/app/assets/done.png" class="checked">';
			} elseif($tr->checked == 2){
				if(isUserIn(['superadmin'])){
					$r .= "<img src='/app/assets/done.png' class='checked-pending' data-src='$tr->source' data-id='$tr->id'>";
				} 
				$r .= '<img src="/app/assets/ban.png" class="checked">';
			}
		}

		$r .= "</td>";

		// .($tr->source == 'hotel_statement_worker_payment' ? '<img src="assets/verified.png" width="32px">' : (($tr->source == 'expenditure' && in_array($tr->ref, [91,92])) ?  '<img src="assets/verified2.png" width="32px">': ''))."$tr->particulars</td>";
		$approvalCell = '';
		if($tr->status=='Pending'){
			$approvalCell = "<input type='checkbox' name='approvem[]' value='{$tr->source}-{$tr->id}'>";
		}

		sum($tr->source, $tr->amount);
		if($tr->source == 'bd_handover'){
			$handover = R::load("bd_handover", $tr->id);
			sum('bank', $handover->bank_amount);
			sum('banko', $handover->bank_amount);
			$total += $tr->amount;
		} elseif($tr->source == 'cw_cash'){
			sum('cash', $tr->amount);
			// if($tr->amount<0) sum('bank', 0 - $tr->amount);
			$total += $tr->amount;
		} elseif($tr->source == 'cw_bank'){
		// if($tr->amount<0) sum('bank', 0 - $tr->amount);
			sum('bank', $tr->amount);
			sum('banko', $tr->amount);
			// $total += $tr->amount;
		} elseif($tr->source == 'payment_cash'){
			$total -= $tr->amount;
		} elseif($tr->source == 'payment_bank'){
			// $total -= $tr->amount;

			sum('bank', 0-$tr->amount);
			sum('banko', 0-$tr->amount);
		} elseif($tr->source == 'cw_payment'){
			// $total += $tr->amount;
		} elseif($tr->source == 'expense_account_entry' || $tr->source == 'cw_outlet'){
			$total -= $tr->amount;
			if($tr->source == 'cw_outlet'){
				sum('expense_account_entry', abs($tr->amount));
			}
		} elseif($tr->source == 'expense_account_entry_bank'){
			// $total -= $tr->amount;
			sum('bank', 0 - $tr->amount);
		} elseif($tr->source == 'cw_cash_withdraw'){
			$total -= abs($tr->amount);
		} elseif($tr->source == 'investment'){
			$total += $tr->amount;
		} else{
			$total -= $tr->amount;
		}
		$cashAfter = $total;
		$bankAfter = sum('bank');
		$cashDelta = $cashAfter - $cashBefore;
		$bankDelta = $bankAfter - $bankBefore;
		if ($cashDelta > 0) {
			$cashInTotal += $cashDelta;
		} elseif ($cashDelta < 0) {
			$cashOutTotal += abs($cashDelta);
		}
		if ($bankDelta > 0) {
			$bankInTotal += $bankDelta;
		} elseif ($bankDelta < 0) {
			$bankOutTotal += abs($bankDelta);
		}

		$cashIn = $cashDelta > 0 ? nf($cashDelta) : '';
		$cashOut = $cashDelta < 0 ? nf(abs($cashDelta)) : '';
		$bankIn = $bankDelta > 0 ? nf($bankDelta) : '';
		$bankOut = $bankDelta < 0 ? nf(abs($bankDelta)) : '';

		$r .= "<td class='right'>".$cashIn."</td>";
		$r .= "<td class='right'>".$cashOut."</td>";
		$r .= "<td class='rht'>".nf($cashAfter)."</td>";
		$r .= "<td title='$tr->source'><small>".$userList[$tr->entry_by]."</small></td>";
		$r .= "<td class='right'>".$bankIn."</td>";
		$r .= "<td class='right'>".$bankOut."</td>";
		$r .= "<td class='rht'>".nf($bankAfter)."</td>";
		$r .= "<td class='center'>".$approvalCell."</td>";
		/*
		if(uid() != 1 && isUserIn(['orange']) && $tr->source == 'hotel_statement_worker_payment'){
			if($tr->done_by){
				$r .= "<td><img src='assets/done.png' width='18px'></td>";
			} else{
				$r .= "<td><input type='checkbox' name='donem[]' class='done' value='$tr->id' data-amount='$tr->amount'></td>";
				//$r .= "<td><a href='?page=18&petty_cash_details=&d=$d&t=$t&done=$tr->source&id=$tr->id' class='btn btn-sm btn-warning'>Done</a></td>";
				// $r .= "<td><a href='?page=18&petty_cash_details=&d=$d&t=$t&done=$tr->source&id=$tr->id' class='btn btn-sm btn-warning'>Done</a></td>";
			}
		} elseif(isUserIn(['superadmin']) && $tr->source == 'hotel_statement_worker_payment'){
			if($tr->done_by){
				$r .= "<td><img src='assets/done.png' width='18px'></td>";
			} else{
				$r .= "<td><span class='btn btn-sm btn-warning'>Done</span></td>";
			}
		} else{
			$r .= "<td></td>";
		}
		*/
		if(uid() == 1){
			$r .= "<td><a href='#' class='edit-transaction' data-source='$tr->source' data-id='$tr->id' data-bs-toggle='modal' data-bs-target='#editModal'><i class='fa fa-edit' style='color: #0066cc; margin-right: 10px;'></i></a><a class='protected-link' href='?d=$d&t=$t&del=$tr->source&id=$tr->id' target='_blank'><i class='fa fa-trash'></i></a></td>";
		}
		print "<tr class='$tr->source'><td title='".($tr->source == 'expense_account_entry' ? $tr->se : $tr->source)."'>$i</td>$r</tr>";
		$i++;
	}

	$investment_period_total = getSum("investment", "amount", "date BETWEEN '$d' AND '$t' AND trash=0");

	print "<tr><th colspan='3' class='text-right'>Total Investment</th><th class='right text-danger' colspan='2'><a href='../invest'>" . nf($investment_period_total) . "</a></th><th class='right'>".nf($cashInTotal)."</th><th class='right'>".nf($cashOutTotal)."</th><th class='right'>".nf($total)."</th><th></th><th class='right'>".nf($bankInTotal)."</th><th class='right'>".nf($bankOutTotal)."</th><th class='right'>".nf(sum('bank'))."</th><th></th>".(uid()==1?"<th></th>":"")."</tr>";

	if(uid() == 1){
		print "<tr><td colspan='13' class='cntr'><button class='btn btn-success'>Approve Selected</button></td></tr>";
	}

	if(uid() != 1 && isUserIn(['orange'])){
		print "<tr><td colspan='11' class='cntr'><button class='btn btn-success'>Done Selected</button>  <span class='alert alert-warning'>TOTAL SELECTED : <span class='total-selected'></span></span></td></tr>";
	}
	print "</table>";
	print "</form>";
	print "<div class='right'><a href='?' class='btn btn-danger'>Cancel</a></div>";
	print "<hr>";
} else{

	$mon = isset($get->mon) ? substr($get->mon,0,7) : date("Y-m", time());

	$bt_url = "";
	if(isset($get->add_bt)){
		$bt_url = "&add_bt=$get->add_bt";
	}


	print "<br>";


	$date = date("Y-m-d", strtotime("$mon-01"));

	?>

	<div class="row">
		<!-- <div class="col-md-4"></div> -->
		<!-- <div class="col-md-4"><a class='pointer btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-create'>Create Car Wash</a></div> -->
		<div class="col-md-4 text-right"><strong style="font-size: 3rem; font-weight:700; text-shadow: 0 0 5 #000">Petty Cash Report</strong></div>
		<div class="col-md-4">
	<?php



	print "<span style='float:right'>
		<a class='pointer btn btn-default'  href='?page=18&company=&mon=". subMonth(1, $date)."$bt_url'><i class='fa fa-chevron-left'></i>Prev</a>".space(5);
		//."<b>";
	// print date("M Y", strtotime($mon."-01"))."</b>";
	print monthSelector('mon', date("Y-m-d", strtotime($mon."-01")));
	print space(5).
		"<a class='pointer btn btn-default' href='?page=18&company=&mon=". addMonth(1, $date)."$bt_url'>Next <i class='fa fa-chevron-right'></i></a>
		</span>";
	print "<br><br>";

	?>	
		</div>
		<div class="col-md-4 rht">
			<input class='form-control-fluid search-cars' placeholder="Search..." style="height: unset; line-height: 3rem; font-size: 24px;">
			<a class='pointer' data-bs-toggle='modal' data-bs-target='#modal-customer' style="padding: 0 20px;">
				<!--  <i class='fa fa-car'></i> -->
				<i class='fa fa-plus'></i>
				<img src='assets/car.jpeg' height="40px">
				</a>
		</div>
	</div>
	<hr>
	<div id='search-result'></div>

	<!-- <table class="table table-hover table-striped table-bordered">
		<thead>
			<tr>
				<th>#</th>
				<th>Carwash</th>
				<th>Cash Handover</th>
				<th>Income</th>
				<th>Expense</th>
				<th>Petty Cash Balance</th>
				<th><a data-bs-toggle='modal' data-bs-target='#modal-create'><img src='assets/carwash.jpeg' height="32px"></a></th>
			</tr>
		</thead>
		<tbody> -->
			<?php
			/*
				$carwashes = select("*", "cw_company", "trash=0 AND id=$id");
				$i = 1;
				while ($cw = mysqli_fetch_object($carwashes)) {
					$cash = getSum("cw_cash", "amount", "company=$id AND date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");
					$handover = getSum("bd_handover", "amount", "company=$id AND date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");
					$income = getSum("cw_payment", "amount", "company=$id AND date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");
					$bank = getSum("cw_payment", "amount", "company=$id AND particulars LIKE '%bank%' AND date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");
					$withdraw = getSum("cw_cash_withdraw", "amount", "company=$id AND date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");

					// $expense = getSum("expense_account_entry", "amount", "company=$id and payment_method='Cash'");
					$expense_cash = getSum("expense_account_entry", "amount", "company=$id and payment_method='Cash' AND expense_date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");
					$expense = getSum("expense_account_entry", "amount", "company=$id AND expense_date BETWEEN '".firstDay($date)."' AND '".lastDate($date)."'");


					print "<div class='row'>
					<div class='col-md-6'>";
					$con = "<table class='table table-bordered'>
						<tr class='highlight'><th><a href='?d=$d&t=$t&cw=$cw->id'>Petty Cash</a></th><th title='$handover'>".nf($handover + $cash - $expense_cash - $withdraw)."</a></th></tr>
				<tr class='highlight2'><th>Bank</th><th>".nf($bank)."</th></tr>
				<tr class='highlight2'><th>Income</th><th>".nf($income)."</a></th></tr>
				<tr class='highlight2'><th><a href='/app/expense_account/carwash?company=$id'>Expense</a></th><th>".nf($expense)."</a></th></tr>
				<tr class='highlight2'><th>Add Cash</th><th>".nf($cash)."</a></th></tr>
				<tr class='highlight2'><th>Withdraw</th><th>".nf($withdraw)."</a></th></tr>
				";

			print $con;
			print "</table></div></div>";
					// print "<tr>
					// 	<td>$i</td>
					// 	<td><a style='font-size:2rem;font-weight:700' href='?page=18&cw=$cw->id'>PETTY CASH</a></td>
					// 	<td class='cntr'>".nf($handover)."</td>
					// 	<td class='cntr'>".nf($income - $bank)." + ".nf($bank)."<br></td>
					// 	<td class='cntr'>".nf($expense)."</td>
					// 	<td class='cntr'>".nf($income-$expense)."</td>
					// 	<th>
					// 		<a class='btn btn-sm btn-danger' href='/app/expense_account/carwash?company=$id'>Expense</a> 
					// 	</th>
					// </tr>";
					// $i++;
				}
				*/
			?>
		<!-- </tbody>
		<tfoot>
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th>TOTAL</th>
				<th><?php print nf(0); ?></th>
				<th></th>
		</tfoot>
	</table> -->
<?php
	}
?>	

<div class="modal fade" id="modal-create" role="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="Fulltime" value='Fulltime'>
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
	        <h4 class="modal-title">Create Carwash</h4>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Name</td><td><input name='name' class='form-control' required></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_carwash">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-sales" role="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="Fulltime" value='Fulltime'>
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
	        <h4 class="modal-title alert alert-success">Sales Entry</h4>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Name</td><td><input name='name' class='form-control' required></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_carwash">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-expense" role="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="Fulltime" value='Fulltime'>
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
	        <h4 class="modal-title alert alert-danger">Expense Entry</h4>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Name</td><td><input name='name' class='form-control' required></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_carwash">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-customer" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="Fulltime" value='Fulltime'>
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
	        <h4 class="modal-title alert alert-success">New Customer</h4>
	      </div>
	      <div class="modal-body">
<?php
$object = R::dispense('cw_customer');
openForm('post', true);
print "<table align='center'>
	<tr><td colspan='5'><b>".str("Customer Details")."</b></td><tr>
		<tr><td>".str("Number")."</td><td><input type='text' name='number' id='number' value='$object->number' class='form-control required' required /></td><td>".space(5)."</td><td>".str("Phone")."</td><td><input type='text' name='phone' id='phone' value='$object->phone' class='form-control' /></td></tr>
		<tr><td>".str("Brand")."</td><td>".sop2("brand", $object->brand, ['optional'=>true, 'width'=>''])."</td><td>".space(5)."</td><td>".str("Model")."</td><td id='td-model'></td></tr>
		<tr><td>".str("Branch")."</td><td>".sop2("company", $id, ['optional'=>true, 'attr'=>'readonly disabled', 'width'=>''], "cw_company")."</td><td>".space(5)."</td><td>".str("Roadtax")."</td><td>".dateSelector("roadtax", $object->roadtax)."</td></tr>
		<tr><td>".str("Next Service Date")."</td><td>".dateSelector("next_service_date", $object->next_service_date)."</td><td>".space(5)."</td><td>Photo</td>
			<td><input name='photo_file' type='file' class='form-control w240' />".(file_exists("uploads/cars/$object->id/$object->photo_file")?"<a href='../../uploads/cars/$object->id/$object->photo_file?".time()."' target='_blank'><img src='../../uploads/cars/$object->id/$object->photo_file' width='64px'></a>":"")."</td></tr>
	</table>";
closeForm();
?>
	      </div>
	    </form>
    </div>
  </div>
</div>


	<div class="modal fade cash" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	  <div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Add Cash</h4>
	      </div>
	      <form method="post">
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date'); ?></th></tr>
	        	<tr><th>Amount</th><th><input type='number' class='form-control' onkeyup="setACPart()" required name='amount' id="amount" min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<div id="hints">
	        		 <!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
	        		</div>
	        		<br>
	        		<textarea class='form-control' id='add_cash_particulars' name='particulars'></textarea>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="save_cash">Save</button>
	        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>


<div class="modal fade bank" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Bank Deposit</h4>
	      </div>
	      <form method="post">
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date2'); ?></th></tr>
	        	<tr><th>Amount</th><th><input typye='number' class='form-control' required name='amount' id="bd-amount"  onkeyup="setBDPart()" min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<div id="hints">
	        		 <!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
	        		</div>
	        		<br>
	        		<textarea class='form-control' id='bd-particulars' name='particulars'>Petty Cash Exchcange to Bank Account Rm </textarea>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="save_bank">Save</button>
	        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>
</div>

<script type="text/javascript">
	function setACPart(){
		$("#add_cash_particulars").val("Rm : "  + $("#amount").val() + " Cash Investment for");
	}
	function setCWPart(){
		$("#withdraw_particulars").val("Rm : " + $("#cw-amount").val() + " Cash Withdrawal for");
	}
	function setBDPart(){
		$("#bd-particulars").val("Petty Cash Exchcange to Bank Account Rm " + $("#bd-amount").val());
	}
	function setOutletPart(){
		$("#outlet-particulars").val("Petty Cash Exchcange to Outlet Account Rm " + $("#outlet-amount").val());
	}
</script>

<div class="modal fade outlet" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">	  
	<div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Bank Deposit</h4>
	      </div>
	      <form method="post">
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date3'); ?></th></tr>
	        	<tr><th>Amount</th><th><input typye='number' class='form-control' required name='amount' id="outlet-amount"  onkeyup="setOutletPart()" min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<div id="hints">
	        		 <!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
	        		</div>
	        		<br>
	        		<textarea class='form-control' id='outlet-particulars' name='particulars'>Petty Cash Exchcange to Outlet Account Rm </textarea>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="save_outlet">Save</button>
	        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>
</div>
	<div class="modal fade bankdeposit" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	  <div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Petty Cash to Bank</h4>
	      </div>
	      <form method="post">
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date3'); ?></th></tr>
	        	<tr><th>Amount</th><th><input typye='number' class='form-control' required name='amount' min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<div id="hints">
	        		 <!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
	        		</div>
	        		<br>
	        		<textarea class='form-control' id='' name='particulars'></textarea>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="save_bank_deposit">Save</button>
	        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>



	<div class="modal fade withdraw" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	  <div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Cash Withdraw</h4>
	      </div>
	      <form method="post">
	      	<input type='hidden' name='account' value='46'>
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date2'); ?></th></tr>
	        	<tr><th>Amount</th><th><input typye='number' class='form-control' onkeyup="setCWPart()" required name='amount' id="cw-amount" min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<!-- <div id="hints">
	        		 <div><input type='radio' name='r'>Nadim Kazi may bank theke petty cash a taka add cash kora hoyese Rm </div>
	        		 <div><input type='radio' name='r'>Nadim Kazi  Rhb bank theke petty cash a taka add cash kora hoyese Rm </div>
	        		 <div><input type='radio' name='r'>Arif bhai may bank theke petty cash a taka add cash kora hoyese Rm </div>
	        		 <div><input type='radio' name='r'>Boss er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div>
	        		 <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div>
	        		</div>
	        		<br> -->
	        		<textarea class='form-control' id='withdraw_particulars' required name='particulars'></textarea>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="withdraw">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
 	</div><!-- /.modal-dialog -->
	</div>

	<div class="modal fade invest" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	  <div class="modal-dialog modal-md" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Asset Investment</h4>
	      </div>
	      <form method="post">
	      <div class="modal-body">
	        <table class="table table-bordered">
	        	<tr><th>Date</th><th><?php print ds('date'); ?></th></tr>
	        	<tr><th>Amount</th><th><input type='number' class='form-control' required name='amount' id="invest-amount" min="1" ></th></tr>
	        	<tr><th>Particulars</th><th>
	        		<textarea class='form-control' id='invest_particulars' required name='particulars'></textarea>
	        	</th></tr>
	        	<tr><th>Payment Method</th><th>
	        		<select class='form-control' name='payment_method' id='invest_payment_method'>
	        			<option value='Bank'>Bank</option>
	        			<option value='Cash'>Cash</option>
	        		</select>
	        	</th></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button class="btn btn-primary" type="submit" name="save_investment">Save</button>
	        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>

<div class="modal fade" id="editModal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" autocomplete="off" id="editForm">
        <input type="hidden" name="cash_edit_save" value="1">
        <input type="hidden" name="cash_edit_source" id="cash_edit_source">
        <input type="hidden" name="cash_edit_id" id="cash_edit_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalTitle">Edit Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="editTable"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
	$("select.cars").change(function(){
		// var car = $("select.cars");
	});

	$(".search-cars").keyup(function(){

		var key = $('.search-cars').val();
		if(key.length > 1)
		$.post("/app/ajax/cars.php", {key: key, company: <?php print 1 ?>}, function (data){
			$("#search-result").html(data);
		});

	});


	$("select.brand").change(getModels);

	getModels();

	function getModels(){
		$.post('/app/ajax/model.php', {name:$("select.brand option:selected").val()}, function(data){
			$("#td-model").html(data);
			// $("#td-model").find("select").selectpicker();
		});
	}

	$(document).on('click', '.edit-transaction', function(e){
		e.preventDefault();
		const source = $(this).data('source');
		const id = $(this).data('id');
		const $row = $(this).closest('tr');
		
		// Extract date from row (2nd column)
		const dateText = $row.find('td:eq(1)').text().trim();
		// Extract particulars from row (3rd column)
		const particularsText = $row.find('td:eq(2)').text().trim();
		
		$('#cash_edit_source').val(source);
		$('#cash_edit_id').val(id);
		
		// Fetch transaction data from database
		$.post('/store/ajax/get_cash_transaction.php', {
			source: source,
			id: id
		}, function(response) {
			if(response && response.success) {
				populateEditModal(source, response.data, dateText, particularsText);
			} else {
				populateEditModal(source, {}, dateText, particularsText);
			}
		}, 'json').fail(function() {
			populateEditModal(source, {}, dateText, particularsText);
		});
		
		return false;
	});
	
	function populateEditModal(source, data, dateText, particularsText) {
		let html = '';
		let title = '';
		const amount = data && data.amount ? parseFloat(data.amount) : 0;
		const date = data && data.date ? data.date : dateText;
		const particulars = data && data.particulars ? data.particulars : particularsText;
		
		switch(source) {
			case 'cw_cash':
				title = 'Add Cash';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'cw_cash_withdraw':
				title = 'Cash Withdraw';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + Math.abs(amount) + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'cw_bank':
				title = 'Bank Deposit';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'cw_outlet':
				title = 'Outlet Account Transfer';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'expense_account_entry':
		case 'expense_account_entry_bank':
				title = 'Expense Entry';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'payment_cash':
		case 'payment_bank':
				title = 'Payment';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Payment Method</label><div class="form-check">';
				html += '<input class="form-check-input" type="radio" name="cash_edit_payment_method" value="Cash" id="pm_cash" ' + (source === 'payment_cash' ? 'checked' : '') + '>';
				html += '<label class="form-check-label" for="pm_cash">Cash</label></div>';
				html += '<div class="form-check">';
				html += '<input class="form-check-input" type="radio" name="cash_edit_payment_method" value="Bank" id="pm_bank" ' + (source === 'payment_bank' ? 'checked' : '') + '>';
				html += '<label class="form-check-label" for="pm_bank">Bank</label></div></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + Math.abs(amount) + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
			case 'bd_handover':
				title = 'Bank & Cash Handover';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Cash Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				break;
			case 'investment':
				title = 'Investment';
				html = '<div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="cash_edit_date" value="' + date + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" name="cash_edit_amount" value="' + amount + '" required></div>';
				html += '<div class="mb-3"><label class="form-label">Particulars</label><textarea class="form-control" name="cash_edit_particulars" rows="3" required>' + particulars + '</textarea></div>';
				break;
		}
		
		$('#editModalTitle').text('Edit ' + title);
		$('#editTable').html(html);
		
		// Attach event listeners for payment method changes
		if(source === 'payment_cash' || source === 'payment_bank') {
			$('input[name="cash_edit_payment_method"]').on('change', function() {
				setParticulars();
			});
		}
	}
	
	function setParticulars() {
		const paymentMethod = $('input[name="cash_edit_payment_method"]:checked').val();
		const particulars = $('textarea[name="cash_edit_particulars"]').val();
		
		if(paymentMethod === 'Bank') {
			// Auto-set to bank if not already set
			if(!particulars.toLowerCase().includes('bank')) {
				$('textarea[name="cash_edit_particulars"]').val('Bank transfer - ' + particulars);
			}
		} else if(paymentMethod === 'Cash') {
			// Remove bank prefix if it exists
			if(particulars.toLowerCase().includes('bank transfer')) {
				$('textarea[name="cash_edit_particulars"]').val(particulars.replace(/Bank transfer - /i, ''));
			}
		}
	}
</script>