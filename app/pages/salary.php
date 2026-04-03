<?php $page = 18; $get->page = $page; ?>
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
	.fa-lock{
		color: red;
	}
	.fa-unlock{
		color: grey;
	}
	td{
		vertical-align: middle !important;
	}
</style>
<?php
$mon = isset($get->mon) ? substr($get->mon,0,7) : (date("d", time())<=10 ? date("Y-m", strtotime(subMonth(1))) : date("Y-m", time()));

if(uid() == 1 && isset($get->approve)){
	if($get->approve == 'Income') update("hotel_income", "status='Approved'", "id=$get->id");
	if($get->approve == 'Expense') update("hotel_expense", "status='Approved'", "id=$get->id");
	if($get->approve == 'ExpenseEntry') update("expense_account_entry", "approve_by=".uid().", approve_time='".now()."'", "id=$get->id");
	if($get->approve == 'Capital') update("hotel_capital", "status='Approved'", "id=$get->id");
	if($get->approve == 'Withdraw') update("hotel_withdraw", "status='Approved'", "id=$get->id");
}

$bt_url = "";
if(isset($get->add_bt)){
	$bt_url = "&add_bt=$get->add_bt";
}

if(isset($post->save_remarks)){
	$hotel_statement = R::load("hotel_statement", $get->h);
	$hotel_statement->remarks = $post->remarks;
	$hotel_statement->text_color = $post->text_color;
	$hotel_statement->background_color = $post->background_color;
	R::store($hotel_statement);

	$hotel_statement_remarks = R::dispense("hotel_statement_remarks");
	$hotel_statement_remarks->statement = $hotel_statement->id;
	$hotel_statement_remarks->remarks = $post->remarks;
	$hotel_statement_remarks->text_color = $post->text_color;
	$hotel_statement_remarks->background_color = $post->background_color;
	$hotel_statement_remarks->entry_by = uid();
	R::store($hotel_statement_remarks);
}

if(uid() == 1 && isset($get->del)){
	if(isset($get->conf)){		
			
		if($get->del == 'Income') del("hotel_income","id=$get->id");
		if($get->del == 'Expense') del("hotel_expense", "id=$get->id");
		redir("?page=$get->page&h=$get->h&statement");

	} else{
		?>
		<script type="text/javascript">
			if(prompt("Please key in your PIN to remove this <?php print title($controller); ?>?") == "<?php print upin(); ?>"){
				location.href = "?conf&<?php print "page=$get->page&h=$get->h&statement&del=$get->del&id=$get->id";?>";
			} else{
				alert("Wrong PIN entered!");
				location.href = "?<?php print "page=$get->page&h=$get->h&statement";?>";
			}
			// if(confirm("Are you sure you want to completly remove this Worker?")){
			// 	location.href = "?conf";
			// } else{
			// 	location.href = "../view";	
			// }
		</script>
		<?php
	}
}

if(isUserIn(['superadmin'])){
	if(isset($post->set_category)){
		$worker = R::load("hotel_statement_worker", $post->id);
		$worker->category = $post->set_category;
		R::store($worker);
	}

	if(isset($get->lock)){
		$w = R::load("hotel_statement_worker", $get->w);
		$w->lock = $get->lock == 0 ? 1 : 0;
		R::store($w);
		redir("?page=3&h=".$get->h);
	}
}

// var_dump($_FILES);

if(count($_FILES)){
		$hotel_statement = R::load("hotel_statement", $get->h);
	
		if(isset($_FILES['file1']['name']) && nn($_FILES['file1']['name'])){
			$hotel_attendance = upload($_FILES, 'file1', "uploads/hotel_attendance/$get->h", 'file1');
			// dd($hotel_attendance);
			$hotel_statement->file1 = $hotel_attendance;
		}
	
		if(isset($_FILES['file2']['name']) && nn($_FILES['file2']['name'])){
			$hotel_attendance = upload($_FILES, 'file2', "uploads/hotel_attendance/$get->h", 'file2');
			$hotel_statement->file2 = $hotel_attendance;
		}
	
		if(isset($_FILES['file3']['name']) && nn($_FILES['file3']['name'])){
			$hotel_attendance = upload($_FILES, 'file3', "uploads/hotel_attendance/$get->h", 'file3');
			$hotel_statement->file3 = $hotel_attendance;
		}
	
		if(isset($_FILES['file4']['name']) && nn($_FILES['file4']['name'])){
			$hotel_attendance = upload($_FILES, 'file4', "uploads/hotel_attendance/$get->h", 'file4');
			$hotel_statement->file4 = $hotel_attendance;
		}

		// vd($hotel_statement);
		R::store($hotel_statement);
}

if(isset($post->save_hotel)){
	$loan = R::dispense("hotel");
	$loan->name = $post->name;
	$loan->basic = 0;
	R::store($loan);
	$account = accMan(2, $post->name, ['contexttype'=>'hotel', 'contextid'=>$loan->id]);
	$loan->accountid = $account->id;
	R::store($loan);
	// redir("?page=3");
}
if(isset($post->save_statement)){
	$hotel = R::load("hotel", $post->hotel);
	$loan = R::dispense("hotel_statement");
	$loan->type = $post->type;
	$loan->month = isset($post->month) ? $post->month : $post->month2;
	$loan->hotel = $post->hotel;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);

	//Account
	// $account = accMan($hotel->accountid, date("M Y", strtotime("{$post->month}-01")), ['contexttype'=>'hotel_statement', 'contextid'=>$loan->id]);
	// $loan->accountid = $account->id;
	// R::store($loan);
	redir("?page=3&h=$loan->id");
}
if(isset($get->delHotel)){
	if(isset($get->conf)){		
		$statement = R::load("hotel_statement", $get->delHotel);
		R::trash($statement);
		redir("?page=$get->page&mon=$mon");

	} else{
		?>
		<script type="text/javascript">
			if(prompt("Please key in your PIN to remove this <?php print title($controller); ?>?") == "<?php print upin(); ?>"){
				location.href = "?conf&<?php print "page=$get->page&delHotel=$get->delHotel&mon=$mon";?>";
			} else{
				alert("Wrong PIN entered!");
				location.href = "../view";
			}
			// if(confirm("Are you sure you want to completly remove this Worker?")){
			// 	location.href = "?conf";
			// } else{
			// 	location.href = "../view";	
			// }
		</script>
		<?php
	}

}
if(isset($get->duplicate)){
	$statement = R::load("hotel_statement", $get->duplicate);
	$loan = R::dispense("hotel_statement");
	$ym = explode("-", $statement->month);
	$y = $ym[0];
	$m = $ym[1] + 1;
	if($m > 12){
		$y += 1;
		$m = 1;
	}
	$loan->month = "$y-".zerofill($m,2);
	$loan->hotel = $statement->hotel;
	$loan->type = $statement->type;
	$loan->hourly = $statement->hourly;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);

	$workers = R::find("hotel_statement_worker", "statement=?", [$statement->id]);
	foreach ($workers as $key => $w) {
		$worker = R::dispense("hotel_statement_worker");
		$worker->statement = $loan->id;
		$worker->name = $w->name;
		$worker->billed_amount = $w->billed_amount;
		$worker->basic = $w->basic;
		$worker->account = $w->account;
		$worker->working_days = 0;
		$worker->entry_by = uid();
		$worker->entry_time = now();
		R::store($worker);
	}

	redir("?page=3&h=$loan->id");
}
if(isset($post->save_worker)){
	$worker = R::dispense("hotel_statement_worker");
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$worker->statement = $get->h;
	$worker->name = $post->name;
	$worker->basic = $hotel->basic;
	$worker->working_days = 0;//$post->working_days;
	$worker->entry_by = uid();
	$worker->entry_time = now();
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if(isset($post->save_worker_update)){
	$worker = R::load("hotel_statement_worker", $post->id);
	if(nn($post->name)) $worker->name = $post->name;
	if(nn($post->basic)) $worker->basic = $post->basic;
	// if(nn($post->pay)) $worker->pay = $post->pay;
	// if(nn($post->billed_amount)) $worker->billed_amount = $post->billed_amount;
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if(isset($post->add_salary)){
	$income = R::dispense("hotel_statement_worker_income");
	$income->worker = $post->id;
	$income->amount = $post->amount;
	$income->date = $post->date_ext;
	$income->particulars = $post->particulars;
	$income->entry_by = uid();
	$income->entry_time = now();
	R::store($income);
	redir("?page=3&h=$get->h");
}
if(isset($post->save_worker_account)){
	$worker = R::load("staff_salary", $post->id);
	$worker->account = $post->worker_account;
	R::store($worker);
}

if(isset($post->deduct_salary)){
	$income = R::dispense("hotel_statement_worker_income");
	$income->worker = $post->id;
	$income->amount = 0 - $post->amount;
	$income->date = $post->date_ext2;
	$income->particulars = $post->particulars;
	$income->entry_by = uid();
	$income->entry_time = now();
	R::store($income);
	redir("?page=3&h=$get->h");
}
if(isset($post->update_working_days)){
	$worker = R::load("hotel_statement_worker", $post->id);
	$worker->working_days = $post->working_days;
	$worker->working_hours = 0;
	$worker->public_holiday = 0;
	$worker->mc = 0;
	if(strpos($post->working_days, ".")){
		$dh = explode(".", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->working_hours = $dh[1] + 0;
	} elseif(strpos($post->working_days, "++")){
		$dh = explode("++", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->public_holiday = $dh[1] + 0;
	} elseif(strpos($post->working_days, "+")){
		$dh = explode("+", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->mc = $dh[1] + 0;
	}
	R::store($worker);
}
if(isset($post->remove_id)){
	$payment = R::load("hotel_statement_worker_payment", $post->remove_id);
	$official_receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$payment->id]);
	if($official_receipt){
		// dd($official_receipt);
		R::trash($official_receipt);
	}
	// dd($payment);
	R::trash($payment);
}
if(isset($post->remove_income)){
	$payment = R::load("hotel_statement_worker_income", $post->remove_income);
	R::trash($payment);
}
if(isset($post->remove_worker)){
	$remove_worker = R::load("hotel_statement_worker", $post->remove_worker);
	R::trash($remove_worker);
}
if(isset($post->save_worker_payment)){
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$w = R::load("hotel_statement_worker", $post->worker);
	$me2 = false;
	if(isset($post->phone) && nn($post->phone)){
		$me2 = true;
		if(!nn($w->phone)){
			$w->phone = $post->phone;
			R::store($w);
		}
	}
	// var_dump($statement);
	// vd($post);
	$salary = ($statement->type == 'Parttime' ? round($w->basic * $w->working_days,2) : round($w->basic / 26 * $w->working_days)) + ($w->working_days > 25 ? 0000 : 0);
	$income = getSum("hotel_statement_worker_income", "amount", "worker=$post->worker");
	$salary = $salary + $income;
	// dd($salary);
	$paid = mysqli_fetch_object(select("SELECT IFNULL(SUM(amount),0) paid FROM `hotel_statement_worker_payment` WHERE worker=$post->worker"));

	// if(($salary >= ($paid->paid + $post->amount)) || (!$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time() > 14))){

	// $notOverpaid  = $salary >= ($paid->paid + $post->amount);
	// $notAdvancedOverpaid = !$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time() > 14);

	// vd([$salary, $paid->paid , $post->amount]);
	// vd($notOverpaid);
	// dd($notAdvancedOverpaid);

	// if($notOverpaid && $notAdvancedOverpaid){
	// if(($salary >= ($paid->paid + $post->amount)) || (!$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time()) > 14 && date("d", time()) < 21)){
	// dd([$salary, $income, $paid->paid + $post->amount]);
	$pay = ($salary >= ($paid->paid + $post->amount)) || (!$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time()) > 14 && date("d", time()) < 21);
	if(true){
		$payment = R::dispense("hotel_statement_worker_payment");
		$payment->worker = $post->worker;
		$payment->date = $post->payment_date;
		$payment->amount = $post->amount;
		$payment->particulars = $post->particulars;
		$payment->entry_by = uid();
		$payment->entry_time = now();

		if(isset($post->worker) && strpos($payment->particulars, 'Salary theke permit') !== FALSE){
			$payment->worker = $post->worker;
		}
		if(isset($post->transfer_customer) && strpos($payment->particulars, "Me2 te") !== FALSE){
			$payment->transfer_customer = $post->transfer_customer;
		}

		R::store($payment);


		//NEW EXPENSE ENTRY
		$particulars = "$hotel->name er staff $w->name, ".date("M Y", strtotime("{$statement->month}-01"))." maser salary $post->particulars";
		$entry = accountEntry($hotel->accountid, $particulars, $post->amount, 'Debit', ['entry_id'=>$payment->id, 'entry_type'=>'Hotel - Salary Payment', 'month'=>$statement->month]);
		$payment->account_entry_id = $entry->id;
		R::store($payment);


		if(isset($post->transfer_customer) && strpos($payment->particulars, "Me2 te") !== FALSE){
			$customer = R::load("transfer_customer", $post->transfer_customer);
			$transfer_tran = R::dispense("transfer_tran");
			$transfer_tran->company = 11;
			$transfer_tran->customer = $customer->id;
			$transfer_tran->date = $post->payment_date;
			$transfer_tran->particulars = "$w->name $hotel->name staff  ".date("F Y", strtotime($statement->month."-01"))." ,Hotel salary theke me2 te taka pathano hoyese";
			$transfer_tran->method = 'Cash';
			$transfer_tran->amount = $post->amount;
			$transfer_tran->entry_by = uid();
			$transfer_tran->entry_time = now();
			$transfer_tran->hotel_payment_ref = $payment->id;
			R::store($transfer_tran);
		}


		if(isset($post->worker) && strpos($payment->particulars, 'Salary theke permit') !== FALSE){
			$customer = R::load("worker", $post->worker2);
			$official_receipt = R::dispense("official_receipt");
			$official_receipt->customer_id = $customer->id;
			$official_receipt->date = $post->payment_date;
			$official_receipt->amount = $post->amount;
			$official_receipt->payment_mode = 'Cash';
			$official_receipt->account = aid();
			$official_receipt->remarks = "$w->name $hotel->name staff  ".date("F Y", strtotime($statement->month."-01"))." ,Hotel salary theke permit er jonno te taka kata hoyese";
			$official_receipt->entry_by = uid();
			$official_receipt->entry_time = now();
			$official_receipt->hotel_payment_id = $payment->id;

			R::store($official_receipt);

			$entryO = accountEntryO('Official Receipt', $official_receipt->remarks, $official_receipt->amount, 'Credit', ['entry_id'=>$official_receipt->id, 'entry_type'=>'Official Receipt']);

			$official_receipt->account_entry_id = $entryO->id;

			R::store($official_receipt);
		}
		redir("?page=3&h=$get->h");
	} else{
		if(!$w->working_days && date("d", time() > 14)){
			alert("Sorry you cannot pay advance before 15 of the month");
		} else{
			alert("Sorry you cannot overpay 1");
		}
	}
}
//save_worker_payment_2
if(isset($post->save_worker_payment_2)){
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$saved = false;
	foreach ($post->workers as $key => $worker) {
		$worker_salary = $post->salary[$key];
		$w = R::load("hotel_statement_worker", $worker);
		$me2 = false;
		if(isset($post->phone) && nn($post->phone)){
			$me2 = true;
			if(!nn($w->phone)){
				$w->phone = $post->phone;
				R::store($w);
			}
		}
		// var_dump($statement);
		// vd($post);
		$salary = ($statement->type == 'Parttime' ? round($w->basic * $w->working_days,2) : round($w->basic / 26 * $w->working_days)) + ($w->working_days > 25 ? 0000 : 0);
		if($statement->hourly && $w->working_days < 325){
			$salary -= 100;
		}
		$income = getSum("hotel_statement_worker_income", "amount", "worker=$w->id");
		$salary = $salary + $income;
		// dd($salary);
		$paid = mysqli_fetch_object(select("SELECT IFNULL(SUM(amount),0) paid FROM `hotel_statement_worker_payment` WHERE worker=$w->id"));

		$pay = ($salary >= ($paid->paid + $worker_salary)) || (!$w->working_days && ($worker_salary + $paid->paid) <= 1500 && $worker_salary > 0 && date("d", time()) > 14 && date("d", time()) < 21);
		if(true){
			$payment = R::dispense("hotel_statement_worker_payment");
			$payment->worker = $w->id;
			$payment->date = $post->payment_date;
			$payment->amount = $worker_salary;
			// $payment->bank = $post->bank;
			$payment->particulars = $post->particulars;
			$payment->entry_by = uid();
			$payment->entry_time = now();

			if(isset($w->id) && strpos($payment->particulars, 'Salary theke permit') !== FALSE){
				$payment->worker = $w->id;
			}
			if(isset($post->transfer_customer) && strpos($payment->particulars, "Me2 te") !== FALSE){
				$payment->transfer_customer = $post->transfer_customer;
			}

			R::store($payment);


			//NEW EXPENSE ENTRY
			if($saved == false){
				$saved = true;
				$entry = accountEntry($hotel->accountid, $post->particulars, $post->amount, 'Debit', ['entry_id'=>$payment->id, 'entry_type'=>'Hotel - Salary Payment', 'month'=>$statement->month,'payment_method'=>'Cash','hotel'=>$hotel->id,'bank'=>'', 'expense_date'=>$post->payment_date]);
				$payment->account_entry_id = $entry->id;
				R::store($payment);
			}

			
			// redir("?page=3&h=$get->h");
		} else{
			if(!$w->working_days && date("d", time() > 14)){
				alert("Sorry you cannot pay advance before 15 of the month");
			} else{
				alert("Sorry you cannot overpay 2");
			}
		}
	}
	redir("?page=3&h=$get->h");
}
if(isset($post->save_loan)){
	$loan = R::dispense("hotel_loan");
	$loan->hotel = $get->h;
	$loan->direction = $post->direction;
	$loan->date = isset($post->date_loan)?$post->date_loan:today();;
	$loan->amount = $post->amount;
	$loan->account = $post->account;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if(isset($post->save_expense)){
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$loan = R::dispense("hotel_expense");
	$loan->hotel = $hotel->id;
	$loan->statement = $get->h;
	$loan->payment_method = $post->payment_method;
	$loan->date = isset($post->date_expense)?$post->date_expense:today();;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();

	R::store($loan);

	//NEW EXPENSE ENTRY
	$particulars = date("M Y", strtotime("{$statement->month}-01"))." $post->particulars";
	$entry = accountEntry($hotel->accountid, $particulars, $post->amount, 'Debit', ['entry_id'=>$loan->id, 'entry_type'=>'Hotel - Expense','url'=>"/app/?page=3&h=$get->h&statement", 'month'=>$statement->month]);
	$loan->account_entry_id = $entry->id;
	if(isset($get->add_bt)){
		update("expense_account_entry", "bank_transaction=$get->add_bt", "id=$entry->id");
		update("hotel_expense", "bank_transaction=$get->add_bt", "id=$loan->id");
		update("bank_transaction_item", "expense_entry=$entry->id, hotel_expense=$loan->id", "id=$get->add_bt");
	}
	R::store($loan);

	redir("?page=3&h=$get->h");
}
if(isset($post->save_income)){
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);

	$loan = R::dispense("hotel_income");
	$loan->hotel = $hotel->id;
	$loan->statement = $get->h;
	$loan->date = isset($post->date_income)?$post->date_income:today();;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);

	//NEW EXPENSE ENTRY
	$particulars = date("M Y", strtotime("{$statement->month}-01"))." $post->particulars";
	$entry = accountEntry($hotel->accountid, $particulars, $post->amount, 'Credit', ['entry_id'=>$loan->id, 'entry_type'=>'Hotel - Income', 'month'=>$statement->month]);
	$loan->account_entry_id = $entry->id;
	R::store($loan);


	redir("?page=3&h=$get->h");
}
if(isset($post->save_hotel_invoice)){
	$hotel_statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $hotel_statement->hotel);

	$loan = R::dispense("hotel_invoice");
	$loan->hotel = $hotel->id;
	$loan->statement = $get->h;
	$loan->hotel_name = $hotel->name;
	$loan->hotel_address = $hotel->address;
	$loan->date = $post->invoice_date;
	$loan->start_date = $post->start_date;
	$loan->end_date = $post->end_date;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);

	$hotel_statement->invoice = $loan->id;
	R::store($hotel_statement);
	redir("?page=3&h=$get->h");
}
if(isset($post->save_capital)){
	$loan = R::dispense("hotel_capital");
	$loan->hotel = $get->h;
	$loan->date = isset($post->date_capital)?$post->date_capital:today();;
	$loan->amount = $post->amount;
	$loan->type = $post->type;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if(isset($post->save_withdraw)){
	$loan = R::dispense("hotel_withdraw");
	$loan->hotel = $get->h;
	$loan->date = isset($post->date_withdraw)?$post->date_withdraw:today();;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if(isset($post->approve)){
	$worker = R::load("hotel_statement_worker", $post->worker);
	$worker->approved = 1;
	R::store($worker);
}

print "<br><br>";
if(!isset($get->h))
print "<a class='pointer btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-create-fulltime'>Staff Salary Statement</a>";

if(isset($get->h)){
	$hotel_statement = R::load("hotel_statement", $get->h);
	$hourly = $hotel_statement->hourly == 1;
	$dp = $hourly ? 2 : 0;
	$mon = $hotel_statement->month;
}

$date = date("Y-m-d", strtotime("$mon-01"));

// print $date;
// print addMonth(1,$date);
if(isset($get->h))
print "<a class='btn btn-info' href='?'>Back</a>";
print "<span style='float:right'>
	<a class='pointer btn btn-info'  href='?page=3&mon=". subMonth(1, $date)."$bt_url'><i class='fas fa-chevron-left'></i>Prev</a>".space(5);
	//."<b>";
// print date("M Y", strtotime($mon."-01"))."</b>";
print monthSelector('mon', date("Y-m-d", strtotime($mon."-01")));
print space(5).
	"<a class='pointer btn btn-info' href='?page=3&mon=". addMonth(1, $date)."$bt_url'>Next <i class='fas fa-chevron-right'></i></a>
	</span>";
print "<br><br>";
						
// Month	Hotel	Worker	Income	Expense	Profit	Status
// NOV,2022	aloft	20				Close

if(isset($get->h)){
	$hotel = R::load("hotel", $hotel_statement->hotel);
	print "<h1 class='center panel panel-success'><span id='title-hotel'>$hotel->name</span> Statement - <span id='title-month'>".date("M Y", strtotime("{$hotel_statement->month}-01"))."</span></h1>";

	print "
		<table class='table table-bordered table-striped'>
			<thead>
				<tr>
					<th><i class='fas fa-lock'></i></th>
					<th>No</th>
					<th>".(isUserIn(['orange']) ? "<i class='fas fa-shopping-cart pointer' data-bs-toggle='modal' data-bs-target='#modal-worker-payment-2' onClick='setWorkerIds()'></i>" : "")."</th>
					<th>Name</th>
					<th>".($hotel_statement->hourly ? 'Basic Salary<br>Per Hour':'Basic<br>Salary')."</th>
					<th colspan='2'>".($hotel_statement->hourly ? 'Working<br>Hours':'Working<br>Days')."</th>
					<th>Salary</th>
					<th>Extra<br>Salary</th>
					<th>Total<br>Salary</th>
					<th>Paid<br>Salary</th>
					<th>Balance<br>Salary</th>
					<th>Approved</th>";
					if(isUserIn(['superadmin', 'durian', 'apple'])){
						print "<th></th>";
					}

					if(isUserIn(['superadmin', 'durian', 'apple'])){
						print "<th></th>";
						print "<th></th>";

					}

		print "</tr>
			</thead>
			<tbody>";

		$i = 1;
		$workers = select("*, (SELECT IFNULL(SUM(amount),0) from hotel_statement_worker_payment WHERE worker = hotel_statement_worker.id) paid","hotel_statement_worker", "statement=$hotel_statement->id", "ORDER BY trim(name)");
		$total_salary = 0;
		$hotel_salary = 0;
		while($w = mysqli_fetch_object($workers)){
			if($hotel_statement->hourly == 0){
				$hotel_salary = $w->billed_amount / 26;
				if($hotel_statement->type == 'Fulltime'){
					$salary = round(($w->basic / 26 * ($w->working_days + $w->public_holiday)) + ($w->working_days > 25 ? 0000 : 0));
				} else{
					$salary = $w->basic * $w->working_days;
				}
			} else{
				$hotel_salary = $w->billed_amount / 26;
				$salary = $w->basic * ($w->working_days + ($w->working_hours / 100));
				if($w->working_days < 325 && $w->working_days	> 100){
					$salary -= 100;
				}
			}
			
			$income = getSum("hotel_statement_worker_income", "amount", "worker=$w->id");
			$total_salary += $salary + $income;
			if(!$w->category){
				$w->category = space(1);
			}

			$working_days = $w->working_days;
			if($w->working_hours){
				$working_days = "{$w->working_days}.{$w->working_hours}";
			}
			if($w->public_holiday){
				$working_days = "{$w->working_days}++{$w->public_holiday}";
				// print " + {$w->public_holiday}PH";
			}
			if($w->mc){
				$working_days = "{$w->working_days}+{$w->mc}";
			}
			print "<tr>
					<td>";
					if(isUserIn([])){
						print "<a href='?page=3&h=$get->h&lock=$w->lock&w=$w->id'>";
					}
					print "<i class='fas fa-".($w->lock ? 'lock' : 'unlock')."'></i>";
					if(isUserIn([])){
						print "</a>";
					}
					print "</td>
					<td class='text-center'>".($i++)."</td>
					<td>".($w->lock ? "" : "<input type='checkbox' class='worker-payment' data-name='$w->name' data-id='$w->id'>")."</td>
					<td>
						<a style='color:blue' data-bs-toggle='modal' data-bs-target='#modal-worker-details'  onClick='showDetails($w->statement, $w->id,\"$w->name\", \"".nf0($salary)."\", \"".nf0($income)."\", \"".nf0($salary + $income)."\",\"$w->phone\",\"$w->account\",$w->lock)'>$w->name</a>
						<button class='btn btn-$w->category frht' style='' ".(isUserIn(['superadmin']) ? "onClick='setCategory($w->id)'" : '').">$w->category</button>
						<div>$w->phone</div>
					</td>
					<td class='text-center'>".nf0($w->basic,$dp)."</td>
					<td class='text-center' ondblclick='editWorkingDays($w->id)' data-data='$working_days' id='w{$w->id}'>";
					if($w->working_hours && $hotel_statement->hourly == 0){
						print "$w->working_days  + {$w->working_hours}HR";
					} else{
						print ($w->working_days  + ($w->working_hours/100));
					}
					if($w->public_holiday){
						print " + {$w->public_holiday}PH";
					}
					if($w->mc){
						print " + {$w->mc}MC";
					}
					print "</td>";
					if($w->verified){
						print "<td class='w30 cntr' id='w-$w->id'><i class='fas fa-check-circle' style='color:limegreen; cursor: pointer' ondblclick='unverify($w->id)'></i></td>";
					} else{
						print "<td class='w30 cntr' id='w-$w->id'><i class='fas fa-check-circle' style='color:grey' onclick='verify($w->id)'></i></td>";
					}
					$bal = $salary + $income - $w->paid + 0;
					if($bal >  -.49 && $bal < .50){
						$bal = 0;
					}
					print "<td class='text-center'>".nf0($salary)."</td>
					<td class='text-center'>".nf0($income)."</td>
					<td class='text-center'>".nf0($salary + $income)."</td>
					<td class='text-center'>".nf0($w->paid)."</td>
					<td class='text-center'>".nf0(round($bal,2))."</td>
					<td class='text-center'>";
					if($w->approved){
						print '<a class="btn btn-success">Approved</a>';
					} else{
						if(isUserIn(['superadmin'])){
							print "<form method='post' onsubmit=\"return confirm('Are you sure?');\"><input type='hidden' name='worker' value='".$w->id."'><button class='btn btn-danger' name='approve'>Pending</button></form>";
						} else{
							print "<button class='btn btn-danger' name='approve'>Pending</button>";
						}
					}
					print "</td>";
					if(isUserIn(['superadmin', 'orange', 'apple'])){
						print "<td class='text-center'><a data-bs-toggle='modal' data-bs-target='#modal-worker-edit' onClick='setWorkerId($w->id)' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a></td>";
					}
					if(isUserIn(['superadmin', 'orange', 'apple'])){
						print "<td class='text-center' nowrap>
							<a data-bs-toggle='modal' data-bs-target='#modal-deduct-salary' onClick='setWorkerId($w->id)' class='btn btn-sm btn-danger'><i class='fas fa-minus'></i></a>
							<a data-bs-toggle='modal' data-bs-target='#modal-add-salary' onClick='setWorkerId($w->id)' class='btn btn-sm btn-warning'><i class='fas fa-plus'></i></a>
						</td>";
					}
					if(isUserIn(['superadmin'])){
						print "<td class='text-center'><a href='javascript:deleteWorker($w->id)' class='btn btn-sm btn-warning'><i class='fas fa-trash'></i></a></td>";
					}
				print "</tr>";
				sum('basic', $w->basic);
				if($hourly){
					sum('working_days', $w->working_days + ($w->working_hours / 100));
				} else{
					sum('working_days', $w->working_days);
				}
				sum('salary', $salary);
				sum('income', $income);
				sum('paid', $w->paid);
				sum('bal', $salary + $income - $w->paid);
			}
	print "
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					<td colspan='3'><a data-bs-toggle='modal' data-bs-target='#modal-worker'><i class='fas fa-plus'></i></a> <b class='frht style='color:teal;'></b></td>
					<th class='text-center'>".nf0(sum('basic'),$dp)."</th>
					<th class='text-center'>".nf0(sum('working_days'),$dp)."</th>
					<td></td>
					<th class='text-center'>".nf0(sum('salary'))."</th>
					<th class='text-center'>".nf0(sum('income'))."</th>
					<th class='text-center'>".nf0(sum('salary') + sum('income'))."</th>
					<th class='text-center'>".nf0(sum('paid'))."</th>
					<th class='text-center'>".nf0(sum('bal'))."</th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
				</tr>
			</tfoot>
		</table>";

	// print "<div style='color: orangered; font-weight:700;margin-bottom:10px' class='cntr'>PLEASE NOTE IF OUTSOURCE AND HOTEL INVOICE DOES NOT MATCH</div>";
	// print "<div style='padding: 5px 15px; font-weight:700; font-size:1.5rem; margin-bottom:10px; border-radius; background-color: ".(nn($hotel_statement->background_color) ? $hotel_statement->background_color : "#efefef")."; color: $hotel_statement->text_color'>$hotel_statement->remarks <a data-bs-toggle='modal' data-bs-target='#save_remarks_modal'><i class='fas fa-plus-circle'></i></a></div>";

	if(isset($get->statement)){
		$stt = R::load("hotel_statement", $get->h);
		// $d = $hotel_statement->month."-01"; //isset($get->d)?$get->d:subDay(30);
		// $t = $hotel_statement->month."-31"; //isset($get->t)?$get->t:today();
		$method = isset($get->method)?$get->method:'';
		openFilterForm('get');
		print "<input type='hidden' name='page' value='$get->page'>";
		print "<input type='hidden' name='h' value='$get->h'>";
		print "<input type='hidden' name='statement' value=''>";
		// print "Date ".dateSelector("d", $d)." - ".dateSelector("t", $t);
		print "Type ";
	  print makeSelectOption("name='method' class='form-control-fluid'", ['All', 'Income', 'Capital', 'Expense', 'Withdraw'], ['', 'Income', 'Capital', 'Expense', 'Withdraw'], $method);

		closeFilterForm();

		$rows = select("SELECT * FROM (
			SELECT id, date, amount, particulars, entry_time, status, 'hotel_income' source FROM hotel_income WHERE statement=$get->h
		) t ORDER BY entry_time");
		print "<table id='expense' class='table table-bordered table-striped'>";
		print "<thead><tr><th colspan='12'><h3>Income</h3></tr>
		<tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		$balance = 0;
		while($row = mysqli_fetch_object($rows)){
			$type = "";
			if($row->source == 'hotel_capital') $type = 'Capital';
			if($row->source == 'hotel_income') $type = 'Income';
			if($row->source == 'hotel_expense') $type = 'Expense';
			if($row->source == 'hotel_withdraw') $type = 'Withdraw';
			if($method != ''){
				if($method != $type){
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>".df($row->date)."</td>";
			print "<td>$row->particulars</td>";
			if($type == 'Income'){
				print "<td class='text-right'>".nf0($row->amount)."</td><td></td>";
				$balance += $row->amount;
				sum('itc', $row->amount);
			} else{
				print "<td></td><td class='text-right'>".nf0($row->amount)."</td>";
				$balance -= $row->amount;
				sum('itd', $row->amount);
			}
			print "<td class='text-right'>".nf0($balance)."</td>";
			if($row->status == 'Pending'){
				if(uid()==1){
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";	
				} else{
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";	
				}
			} else{
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";	
			}
				print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fas fa-trash'></i></a></td>";	

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>".nf0(sum('itc'))."</th><th>".nf0(sum('itd'))."</th><th class='text-right'>".nf0($balance)."</th><th></th></tr></thead>";


		print "<tr><th colspan='12'><h3>Advance</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, expense_date date, amount, particulars, entry_time, '' status, 'expense_account_entry' source FROM `expense_account_entry` WHERE particulars LIKE '%advance%' AND entry_id IN (SELECT id FROM hotel_statement_worker_payment WHERE worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$get->h))
		) t ORDER BY entry_time");


		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while($row = mysqli_fetch_object($rows)){
			$type = "";
			if($row->source == 'hotel_capital') $type = 'Capital';
			if($row->source == 'hotel_income') $type = 'Income';
			if($row->source == 'hotel_expense') $type = 'Expense';
			if($row->source == 'hotel_withdraw') $type = 'Withdraw';
			if($method != ''){
				if($method != $type){
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>".df($row->date)."</td>";
			print "<td>$row->particulars</td>";
			if($type == 'Income'){
				print "<td class='text-right'>".nf0($row->amount)."</td><td></td>";
				$balance += $row->amount;
				sum('ptc', $row->amount);
			} else{
				print "<td></td><td class='text-right'>".nf0($row->amount)."</td>";
				$balance -= $row->amount;
				sum('ptd', $row->amount);
			}
			print "<td class='text-right'>".nf0($balance)."</td>";
			if($row->status == 'Pending'){
				if(uid()==1){
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";	
				} else{
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";	
				}
			} else{
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";	
			}
				print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fas fa-trash'></i></a></td>";	

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>".nf0(sum('ptc'))."</th><th>".nf0(sum('ptd'))."</th><th class='text-right'>".nf0($balance)."</th><th></th></tr></thead>";

		print "<tr><th colspan='12'><h3>Salary</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, expense_date date, amount, particulars, entry_time, '' status, 'expense_account_entry' source FROM `expense_account_entry` WHERE particulars LIKE '%salary%' AND entry_id IN (SELECT id FROM hotel_statement_worker_payment WHERE worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$get->h))

		) t ORDER BY entry_time");

		//SELECT e.id, e.expense_date date, e.amount, e.particulars, e.entry_time, '' status, 'expense_account_entry' source FROM expense_account_entry e, expense_account a WHERE e.accountid=a.id AND a.hotel=$stt->hotel AND e.expense_date LIKE '{$stt->month}-%' AND e.particulars LIKE '%salary%'



		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while($row = mysqli_fetch_object($rows)){
			$type = "";
			if($row->source == 'hotel_capital') $type = 'Capital';
			if($row->source == 'hotel_income') $type = 'Income';
			if($row->source == 'hotel_expense') $type = 'Expense';
			if($row->source == 'hotel_withdraw') $type = 'Withdraw';
			if($method != ''){
				if($method != $type){
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>".df($row->date)."</td>";
			print "<td>$row->particulars</td>";
			if($type == 'Income'){
				print "<td class='text-right'>".nf0($row->amount)."</td><td></td>";
				$balance += $row->amount;
				sum('stc', $row->amount);
			} else{
				print "<td></td><td class='text-right'>".nf0($row->amount)."</td>";
				$balance -= $row->amount;
				sum('std', $row->amount);
			}
			print "<td class='text-right'>".nf0($balance)."</td>";
			if($row->status == 'Pending'){
				if(uid()==1){
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";	
				} else{
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";	
				}
			} else{
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";	
			}
				print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fas fa-trash'></i></a></td>";	

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>".nf0(sum('stc'))."</th><th>".nf0(sum('std'))."</th><th class='text-right'>".nf0($balance)."</th><th></th></tr></thead>";
		// print "<tr><td class='text-center'>$i</td><td></td><td>Total salary paid</td><td></td><td class='text-right'>".nf0(sum('salary'))."</td><td class='text-right'>".nf0($balance - sum('salary'))."</td></tr>";

		print "<tr><th colspan='12'><h3>Expense</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, date, amount, particulars, entry_time, status, 'hotel_expense' source FROM hotel_expense WHERE statement=$get->h
			UNION
			SELECT e.id, e.expense_date date, e.amount, e.particulars, e.entry_time, `status`, 'expense_account_entry' source FROM expense_account_entry e, expense_account a WHERE e.accountid=a.id AND a.hotel=$stt->hotel AND e.expense_date LIKE '{$stt->month}-%' AND e.particulars NOT LIKE '%advance%' AND e.particulars NOT LIKE '%salary%'
		) t ORDER BY entry_time");


		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while($row = mysqli_fetch_object($rows)){
			$type = "";
			if($row->source == 'hotel_capital') $type = 'Capital';
			if($row->source == 'hotel_income') $type = 'Income';
			if($row->source == 'hotel_expense') $type = 'Expense';
			if($row->source == 'hotel_withdraw') $type = 'Withdraw';
			if($row->source == 'expense_account_entry') $type = 'ExpenseEntry';

			if($method != ''){
				if($method != $type){
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>".df($row->date)."</td>";
			print "<td>$row->particulars</td>";
			if($type == 'Income'){
				print "<td class='text-right'>".nf0($row->amount)."</td><td></td>";
				$balance += $row->amount;
				sum('etc', $row->amount);
			} else{
				print "<td></td><td class='text-right'>".nf0($row->amount)."</td>";
				$balance -= $row->amount;
				sum('etd', $row->amount);
			}
			print "<td class='text-right'>".nf0($balance)."</td>";
			if($row->status == 'Pending'){
				if(uid()==1){
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";	
				} else{
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";	
				}
			} else{
				print "<td class='text-center'><a class='btn btn-success'>Approved</a></td>";	
			}
				print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fas fa-trash'></i></a></td>";	

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>".nf0(sum('etc'))."</th><th>".nf0(sum('etd'))."</th><th class='text-right'>".nf0($balance)."</th><th></th></tr></thead>";
		print "</table>";


		print "<div class='right'><a class='btn btn-success' href='?page=$get->page&h=$get->h'>Back</a></div>";
	} else{
        /*
		$stt = R::load("hotel_statement", $get->h);
		// vd($stt);
		$income = getSum("hotel_income", "amount", "statement=$get->h");
		$capital = getSum("hotel_capital", "amount", "hotel=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$get->h");
		// $expense = getSum("expense_account_entry", "amount", "hotel=$stt->hotel AND expense_date LIKE '{$stt->month}-%'");
		$expense = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->accountid/%' AND month='{$stt->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");
		//SELECT * FROM `expense_account_entry` WHERE accountpath LIKE '%/41/%' AND MONTH='2024-01'
		$withdraw = getSum("hotel_withdraw", "amount", "hotel=$hotel->id");
		$loan_given = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Give'");
		$loan_taken = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Collect'");

				// <tr><th>Salary</th><th>".nf0($total_salary)."</th></tr>
		print "<div class='col-md-6'>
			<table class='table table-bordered'>
				<tr><th>Income</th><th>".nf0($income)."</th></tr>
				<tr><th>Salary</th><th>".nf0(sum('paid'))."</th></tr>
				<tr><th><a href='/app/expense_account/hotel?hotel=$hotel->id&month=$mon'>Expense</a></th><th>".nf0($expense)."</th></tr>
				<tr><th><a href='?page=$get->page&h=$get->h&statement'>Profit</a></th><th>".nf0($income + $loan_taken - $expense - $total_salary - $loan_given)."</th></tr>
			</table>
		</div>";
		print "<div class='col-md-6 right'>
			<!--a class='pointer w100 btn btn-warning'  data-bs-toggle='modal' data-bs-target='#modal-capital'>PSI</a-->
			";
		if(($hotel_statement->invoice)){
			// print "<a class='pointer w160 btn btn-success' target='_blank' href='/app/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice'>Print Hotel Invoice</a>";
			print "<div class='dropdown' style='display:inline-block'>
				  <button class='btn btn-success dropdown-toggle' type='button' data-bs-toggle='dropdown'>Print Hotel Invoice
				  <span class='caret'></span></button>
				  <ul class='dropdown-menu'>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=STEWARDING'>STEWARDING</a></li>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=HOUSEKEEPING'>HOUSEKEEPING</a></li>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=CLEANING'>CLEANING</a></li>
				  </ul>
				</div>";
		} else{
			if($hotel_statement->type == 'Fulltime' || $hotel_statement->hourly){
				// print  "<a class='pointer w160 btn btn-info' target='_blank' href='/app/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id'>Preview Invoice</a>";
				print "<div class='dropdown' style='display:inline-block'>
				  <button class='btn btn-info dropdown-toggle' type='button' data-bs-toggle='dropdown'>Preview Invoice
				  <span class='caret'></span></button>
				  <ul class='dropdown-menu'>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=STEWARDING'>STEWARDING</a></li>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=HOUSEKEEPING'>HOUSEKEEPING</a></li>
				    <li><a target='_blank' href='/app/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=CLEANING'>CLEANING</a></li>
				  </ul>
				</div>";
			} else{
				print  "<a class='pointer w160 btn btn-info' target='_blank' href='/app/view/exportables/hotel_parttime_invoice_preview.php?id=$hotel_statement->id'>Preview Invoice</a>";
			}
			if(isUserIn(['superadmin', 'orange'])){
				print "<a class='pointer w160 btn btn-warning'  data-bs-toggle='modal' data-bs-target='#modal-hotel-invoice'>Create Factory Invoice</a>";
			}
		} 
		print "<a class='pointer w100 btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-income'>Income</a>
			<a class='pointer w100 btn btn-danger' id='btn-expense' href='/app/expense_account/hotel?hotel=$hotel->id&month=$mon' data-bs-toggle='modal--' data-bs-target='#modal-expense--'>Expense</a>
			<!--a class='pointer w100 btn btn-info'  data-bs-toggle='modal' data-bs-target='#modal-withdraw'>Withdraw</a-->";
		print "</div>";
/*
		print "<div class='col-md-3'>";
			if($hotel_statement->file1){
				print "
				<div class='attendance-sheet'>
					<div>
						<i class='fas fa-upload' onClick='clk(1)'></i>
						<a target='_blank' href='uploads/hotel_attendance/$get->h/$hotel_statement->file1'>DOWNLOAD HK ATTN</a>
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file1' id='file1' onchange='upload(this)'></form>
				</div>";
			} else{
				print "
				<div class='attendance-sheet'>
					<div onClick='clk(1)'>
						UPLOAD HK ATTN
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file1' id='file1' onchange='upload(this)'></form>
				</div>";
			}
			if($hotel_statement->file3){
				print "
				<div class='attendance-sheet'>
					<div>
						<i class='fas fa-upload' onClick='clk(3)'></i>
						<a target='_blank' href='uploads/hotel_attendance/$get->h/$hotel_statement->file3'>DOWNLOAD HK INVOICE</a>
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file3' id='file3' onchange='upload(this)'></form>
				</div>";
			} else{
				print "
				<div class='attendance-sheet'>
					<div onClick='clk(3)'>
						UPLOAD HK INVOICE
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file3' id='file3' onchange='upload(this)'></form>
				</div>";
			}
		print "</div>";

		print "<div class='col-md-3'>";
			if($hotel_statement->file2){
				print "
				<div class='attendance-sheet'>
					<div>
						<i class='fas fa-upload' onClick='clk(2)'></i>
						<a target='_blank' href='uploads/hotel_attendance/$get->h/$hotel_statement->file2'>DOWNLOAD STW ATTN</a>
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file2' id='file2' onchange='upload(this)'></form>
				</div>";
			} else{
				print "
				<div class='attendance-sheet'>
					<div onClick='clk(2)'>
						UPLOAD STW ATTN
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file2' id='file2' onchange='upload(this)'></form>
				</div>";
			}

			if($hotel_statement->file4){
				print "
				<div class='attendance-sheet'>
					<div>
						<i class='fas fa-upload' onClick='clk(4)'></i>
						<a target='_blank' href='uploads/hotel_attendance/$get->h/$hotel_statement->file4'>DOWNLOAD STW INVOICE</a>
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file4' id='file4' onchange='upload(this)'></form>
				</div>";
			} else{
				print "
				<div class='attendance-sheet'>
					<div onClick='clk(4)'>
						UPLOAD STW INVOICE
					</div>
					<form method='post' enctype='multipart/form-data'><input type='file' style='display:none' class='card-input' name='file4' id='file4' onchange='upload(this)'></form>
				</div>";
			}
		print "</div>";
                */


		
		print '<br>
	<br>
	<br>
	<!--div class="float-right frht">
		<form method="post"> <input type="hidden" name="action" value="hotel">
			Create New Hotel <input type="text" name="value" placeholder="Hotel Name here..." required class="form-control-fluid">
			<button class="btn btn-success">Save</button>
		</form>
	</div-->';
	}
} else{
	$filter = "h.id=s.hotel";
	if(!isset($get->showall)){
		// $latest = mysqli_fetch_object(select("SELECT month FROM hotel_statement ORDER BY id DESC"));
		// $filter .= " AND s.month='$latest->month'";
		$filter .= " AND s.month='$mon'";
	}
	$hotels = select("h.name, h.accountid haid, s.*, 
		(SELECT COUNT(w.id) FROM hotel_statement_worker w WHERE statement=s.id) workers, 
		(SELECT COUNT(w.id) FROM hotel_statement_worker w WHERE statement=s.id AND approved=0) pending,
		(SELECT COUNT(id) FROM hotel_expense WHERE `date` like CONCAT(s.month,'%') AND hotel_expense.status='Pending') pending_expense", "hotel h, hotel_statement s", $filter, "ORDER BY month DESC, h.name");

	print "<table class='table table-responsive table-bordered'>";
	print "<tbody>";
	print "<tr>
		<th>No</th>
		<th>Month</th>
		<th>Staff Salary Statements</th>
		<th>Total Staffs</th>
		<th>Total Staffs Salary</th>
		<th>Paid</th>
		<th>Balance</th>
		<th>".(isset($get->showall) ? "<a href='?page=$page'>Show less</a>" : "<a href='?page=$page&showall'>Show all</a>")."</th>
	</tr>";
	print "</tbody>";

	$i = 1;
	while($hotel = mysqli_fetch_object($hotels)){
		//////////
		$hotel_statement = R::load("hotel_statement", $hotel->id);
		$workers = select("basic, working_days","hotel_statement_worker", "statement=$hotel_statement->id", "ORDER BY name");
		$total_salary = 0;
		while($w = mysqli_fetch_object($workers)){
			if($hotel_statement->type == 'Fulltime'){
				$salary = round(($w->basic / 26 * $w->working_days) + ($w->working_days > 25 ? 0000 : 0));
			} else{
				$salary = $w->basic * $w->working_days;
			}
			$total_salary += $salary;
		}
	
		$income = getSum("hotel_income", "amount", "statement=$hotel->id");
		$capital = getSum("hotel_capital", "amount", "hotel=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$hotel->id");
		// $salary = getSum("hotel_statement_worker", "basic", "statement=$hotel_statement->id");
		$paid = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$hotel_statement->month}' AND tran_type='Debit' AND (particulars LIKE '%salary%' OR particulars LIKE '%advance%')");
		// $expense = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$hotel_statement->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");

		$expense = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$hotel_statement->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");

		$withdraw = getSum("hotel_withdraw", "amount", "hotel=$hotel->id");
		$extra_salary = getSum("hotel_statement_worker_income", "amount", "worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$hotel->id)");
		$loan_given = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Give'");
		$loan_taken = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Collect'");
		$profit = $income + $loan_taken - $expense - $total_salary - $loan_given - $extra_salary;

		// dd($hotel);

		print "<tr>";
		print "<td class='text-center'>$i</td>";
		print "<td>".date("M Y", strtotime("{$hotel->month}-01"))."</td>";
		print "<td><a href='?page=3&h=$hotel->id{$bt_url}'>$hotel->name ($hotel_statement->type)</a></td>";
		print "<td class='text-center'>$hotel->workers</td>"; sum("workers", $hotel->workers);
		// print "<td class='text-center'>".nf0($income)."</td>"; sum("income", $income);
		print "<td class='text-center'>".nf0($total_salary)."</td>"; sum("salary", $total_salary);
		print "<td class='text-center'>".nf0($paid)."</td>"; sum("expense", $paid);
		print "<td class='text-center'>".nf0($total_salary - $paid)."</td>"; sum("balance", $total_salary - $paid);
		// print "<td class='text-center'>".nf0($profit)."</td>"; sum("profit", $profit);
		// print "<td class='text-center'><a href='?page=3&h=$hotel->id'>$hotel->pending</a></td>"; sum("pending", $hotel->pending);
		// print "<td class='text-center'><a href='?page=3&h=$hotel->id&statement#expense'>$hotel->pending_expense</a></td>"; sum("pending_expense", $hotel->pending_expense);
		print "<td>
				<a class='btn btn-warning protected-link' href='?page=$get->page&duplicate=$hotel->id&mon=$mon'><i class='fas fa-copy'></i> Duplicate</a>
				<a class='btn btn-danger' href='?page=$get->page&delHotel=$hotel->id&mon=$mon&conf'><i class='fas fa-trash'></i> Del</a>
			</td>";
		print "</tr>";
		$i++;
	}
	print "<tr>
		<tfoot>
			<th></th>
			<th></th>
			<th></th>
			<th>".nf0(sum("workers"))."</th>
			<th>".nf0(sum("salary"))."</th>
			<th>".nf0(sum("paid"))."</th>
			<th>".nf0(sum("balance"))."</th>
			<th></th>
		</tfoot>
	</tr>";
	print "</table>";
}
?>

<div class="modal fade" id="modal-create-fulltime" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="Fulltime" value='Fulltime'>
	      <div class="modal-header">
	        <h4 class="modal-title">Create Fulltime Staff Statement</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Factory</td><td><?php print sop2('hotel'); ?>  <a data-bs-toggle='modal' data-bs-target='#modal-hotel'><i class='fas fa-plus'></i></a></td></tr>
	      		<tr><td>Month</td><td nowrap><?php print monthSelector("month", today()); ?></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_statement">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-create-parttime" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type" value='Parttime'>
	      <div class="modal-header">
	        <h4 class="modal-title">Create Parttime Hotel Statement</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Hotel</td><td><?php print sop2('hotel'); ?>  <a data-bs-toggle='modal' data-bs-target='#modal-hotel'><i class='fas fa-plus'></i></a></td></tr>
	      		<tr><td>Month</td><td nowrap><?php print monthSelector("month2", today()); ?></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_statement">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-hotel" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Create Factory</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Factory</td><td><input name="name" required class="form-control" placeholder="Factory name"></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_hotel">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-hotel-invoice" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Create Hotel Invoice</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Invoice Date</td><td nowrap><?php print dateSelector("invoice_date", today()); ?></td></tr>
	      		<tr><td>Start Date</td><td nowrap>
	      			<?php 
	      				if(nn($hotel->startdate)){
	      					$startdate = date("Y-m-", strtotime(subMonth(1, $hotel->startdate))).date("d", strtotime($hotel->startdate));
	      				} else{
	      					$startdate = firstDay("$hotel_statement->month-01"); 
	      				}
	      					print dateSelector("start_date", $startdate); 
	      			?></td></tr>
	      		<tr><td>End Date</td><td nowrap>
	      			<?php 
	      				if(nn($hotel->enddate)){
	      					$enddate = date("Y-m-", strtotime(subMonth(1))).date("d", strtotime($hotel->enddate));
	      				} else{
	      					$enddate = lastDate("$hotel_statement->month-01"); 
	      				}
      					print dateSelector("end_date", $enddate); 
	      			?>
	      		</td></tr>
	      		<tr class='hidden'><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_hotel_invoice">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-income" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Income</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_income", today()); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" id="income-amount" class="form-control" placeholder="Amount" step=".01"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_income">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-capital" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">PSI</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_capital"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" class="form-control" placeholder="Amount"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      		<tr><td></td><td>
	      			<input type="radio" name="type" checked value='Invest'> Invest <br>
	      			<input type="radio" name="type" value='Collect'> Collect
	      		</td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_capital">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-expense" role="dialog">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Expense</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_expense"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" id="" required class="form-control amount" step='.01' min='1'
	      			<?php 
	      				if(isset($get->h) && isset($get->add_bt)){
									$bt = R::load("bank_transaction_item", $get->add_bt);
									print "value='$bt->debit'";
	      				}
	      			?>
	      		 placeholder="Amount"></td></tr>
	      		<tr><td>Payment Method</td>
	      			<td>
		      			<span><input required="required" type='radio' name='payment_method' class='required pm' value='Cash'> Cash</span>
								<span><input type='radio' name='payment_method' class='required on' value='Online'> Online</span>
							</td>
						</tr>
	      		<tr><td>Particulars</td>
	      			<td>
	      			<div>
									<div><input class='part' type='radio' name='part'>Hostel er jonno pay kora hoyese</div>
									<div><input class='part' type='radio' name='part'>TNB er jonno pay kora hoyese</div>
									<div><input class='part' type='radio' name='part'>Water bill er jonno pay kora hoyese</div>
									<div><input class='part' type='radio' name='part'>UNiFi  bill er jonno pay kora hoyese</div>
									<div><input class='part' type='radio' name='part'>Indah water er jonno pay kora hoyese</div>
		      			<br>
	      			</div>
	      			</td>
	      			<td>
	      			<div>
								<div><input class='bank' type='radio' name='bank'>Ddcon may bank ac theke</div>
								<div><input class='bank' type='radio' name='bank'>N & C may bank ac theke</div>
								<div><input class='bank' type='radio' name='bank'>Emon may bank ac theke</div>
								<div><input class='bank' type='radio' name='bank'>Tutul may bank ac theke</div>
	      			</div>
	      		<tr><td></td><td>
	      			<textarea name="particulars" rows="5" placeholder="Particulars" class="form-control particulars"></textarea>
	      		</td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_expense">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-withdraw" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Withdraw</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_withdraw"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" class="form-control" placeholder="Amount"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_withdraw">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-loan" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Loan</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_loan"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" class="form-control" placeholder="Amount"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      		<tr><td>Account</td><td><?php print sop2("account"); ?></td></tr>
	      		<tr><td></td><td><input type='radio' name='direction' value='Give'>Give Loan <br> <input type='radio' name='direction' value='Collect'>Collect/Return Loan </td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_loan">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-worker" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Add Worker</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Name</td><td><input name="name" class="form-control" placeholder="Name"></td></tr>
	      		<!-- <tr><td>Basic Salary</td><td><input type="number" name="basic" class="form-control" placeholder="Basic Salary"></td></tr> -->
	      		<!-- <tr><td>Working Days</td><td><input type="number" name="working_days" class="form-control" placeholder="Working Days" value="0"></td></tr> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_worker">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade modal-worker" id="modal-worker-edit" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="id" class="worker_id">
	      <div class="modal-header">
	        <h4 class="modal-title">Edit Worker</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<tr><td>Name</td><td><input name="name" class="form-control" placeholder="Name"></td><!--<td><input type='radio' name='pay' value='Monthly' id='ms' checked> <a href='javascript:selectMs();'>Monthly Salary</a></td--></tr>
	      		<?php if(isUserIn(['superadmin'])) { ?>
	      		<tr><td>Basic Salary</td><td><input type="number" name="basic" class="form-control" step=".01" placeholder="Basic Salary"></td><!--td><input type='radio' value='Daily' id='ds' name='pay'>  <a href='javascript:selectDs();'>Daily Salary</a></td--></tr>
	      		<?php } ?>
	      		<!-- <tr><td>Working Days</td><td><input type="number" name="working_days" class="form-control" placeholder="Working Days" value="0"></td></tr> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_worker_update">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade modal-worker" id="modal-add-salary" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="id" class="worker_id">
	      <div class="modal-header">
	        <h4 class="modal-title">Add Salary</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<!-- <tr><td>Name</td><td><input name="name" disabled class="form-control" placeholder="Name"></td></tr> -->
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_ext"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" class="form-control"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      		<!-- <tr><td>Working Days</td><td><input type="number" name="working_days" class="form-control" placeholder="Working Days" value="0"></td></tr> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="add_salary">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade modal-worker" id="modal-deduct-salary" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="id" class="worker_id">
	      <div class="modal-header">
	        <h4 class="modal-title">Deduct Salary</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table>
	      		<!-- <tr><td>Name</td><td><input name="name" disabled class="form-control" placeholder="Name"></td></tr> -->
	      		<tr><td>Date</td><td nowrap><?php print dateSelector("date_ext2"); ?></td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" class="form-control"></td></tr>
	      		<tr><td>Particulars</td><td><textarea name="particulars" rows="5" placeholder="Particulars" class="form-control"></textarea></td></tr>
	      		<!-- <tr><td>Working Days</td><td><input type="number" name="working_days" class="form-control" placeholder="Working Days" value="0"></td></tr> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="deduct_salary">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade modal-category" id="modal-category" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="id" class="worker_id">
	      <div class="modal-header">
	        <h4 class="modal-title">Set Category</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<button class='btn btn-A' name='set_category' value='A'>A</button>
	      	<button class='btn btn-B' name='set_category' value='B'>B</button>
	      	<button class='btn btn-C' name='set_category' value='C'>C</button>
	      	<button class='btn btn-D' name='set_category' value='D'>D</button>
	      	<button class='btn btn-E' name='set_category' value='E'>E</button>
	      	<button class='btn btn-X' name='set_category' value=''>&nbsp;</button>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<style>
	.modal-lg table tr td:nth-child(2){
		white-space: wrap;
	}
</style>


<div class="modal fade" id="modal-worker-details" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
	      <div class="modal-body">
	      	<table class='table table-bordered'>
	      		<thead>
	      			<tr>
	      				<!-- <th class="text-center"> -->
	      					<?php 
	      						// if(isUserIn(['superadmin' , 'orange'])) {
	      						// 	print "<a class='pointer w100 btn btn-danger' data-bs-toggle='modal' id='payment-button' data-bs-target='#modal-worker-payment'>Payment</a>";
	      						// }
	      					?>
	      				<!-- </th> -->
	      				<th class='modal-title text-center large' colspan='4'></th>
	      				<th class='salary text-left large' colspan='3'></th>
	      			</tr>
	      			<tr>
	      				<th class="text-center">Date</th>
	      				<th class="text-center">Perticular</th>
	      				<th class="text-center">Salary</th>
	      				<th class="text-center">Payment</th>
	      				<th class="text-center">Balance</th>
	      				<?php if(uid()==1) print "<th></th><th></th>"; ?>
	      			</tr>
	      		</thead>
	      		<tbody>
	      		</tbody>
	      		<!-- <tfoot>
	      			<tr>
	      				<td colspan="4"><a class='pointer w100 btn btn-danger' data-bs-toggle='modal' data-bs-target='#modal-worker-payment'>Payment</a></td>
	      			</tr>
	      		</tfoot> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        	<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
				<!-- <div class="row">
					<div class="col-md-9">
						<form method='post'>
							<div class="row">
								<div class="col-md-10">
									<input type='hidden' name='id' value='' id='worker'>
									<input type='text' name='worker_account' id='account' class='form-control' style='background: #efefef; font-weight:bolder;' placeholder="Bank & Account">
								</div>
								<div class="col-md-2">
									<button type="submit" name='save_worker_account'>Save</button>
								</div>
							</div>
						</form>
					</div>
					<div class="col-md-3">
						<button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
					</div>
				</div> -->
	      </div>
	    </form>
    </div>
  </div>
</div>



<div class="modal fade" id="modal-worker-payment" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type='hidden' id='worker' name='worker'>
	      <div class="modal-body">
	      	<table class="table table-bordered">
	      		<tr><td colspan="2" class='worker-name cntr bold'></td></tr>
	      		<tr><td>Date</td><td nowrap>
	      			<?php 
								print today();
								print "<input type='hidden' name='payment_date' value='".today()."'>".space(5);	  
	      				// print dateSelector("payment_date"); 
	      			?>
	      		</td></tr>
	      		<tr><td>Amount</td><td><input type="number" name="amount" required id="payment_amount" class="form-control" placeholder="Amount"></td></tr>
	      		<tr><td>Particulars</td><td>
	      			<br>
								<div class='spc spc-1'><input type='radio' name='r'>Petty Cash theke Taka nise Rm</div>
								<!--<div class='spc spc-4'><input type='radio' name='r'>Salary theke permit er jonno joma kora hoyese Rm</div>
								 <div class='spc spc-2'><input type='radio' name='r'>Me2 te Bank a Deshe Taka pathayse Rm</div>
								<div class='spc spc-3'><input type='radio' name='r'>Me2 te bKash a Deshe Taka pathayse Rm</div>
								<div><input type='radio' name='r'>Ari Bhai er May bank account theke Taka nise Rm </div>
								<div><input type='radio' name='r'>Ddcon account theke salary deoya hoyese Rm</div>
								<div><input type='radio' name='r'>Bdcon account theke salary deoya hoyese Rm</div>
								<div><input type='radio' name='r'>Ekawin account theke salary deoya hoyese Rm</div>
								<div><input type='radio' name='r'>Neat & Clean account theke salary deoya hoyese Rm</div> -->
	      			<br>
	      			<?php 
								print dateSelectorOptional("banking_date", today(),'','','alert');    					
								?>
	      			<textarea name="particulars" class="form-control particulars" id="particulars" required placeholder="Particulars"></textarea>
	      		</td></tr>
	      		<!-- <tr class='me2-customer'>
	      			<td>Me2 Customer</td><td><?php //print sop2("transfer_customer", "", ["optional"=>true]); ?></td>
	      		</tr> -->
	      		<tr class='bdcon-worker'>
	      			<td>Outsource Worker</td>
	      			<td><?php print sop2("worker2", "", ["optional"=>true, 'dataField'=>"CONCAT(name,' - ', passport)", 'extraFields'=>'photo_file', 'width'=>'w150'], "worker").space(5); ?> <img class='worker-photo w50' ></td>
	      		</tr>
	      		<!-- <tr id='phone-entry'>
	      			<td>Phone</td><td><input type="text" name="phone" id="phone-input"></td></tr>
	      		</tr> -->
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_worker_payment">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>


<div class="modal fade" id="modal-worker-payment-2" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type='hidden' id='worker' name='worker'>
	      <div class="modal-body">
	      	<table class="table table-bordered">
	      		<tr><td colspan="2" class='worker-name cntr bold'></td></tr>
	      		<tr><td>Date</td><td nowrap>
	      			<?php 
								print today();
								print "<input type='hidden' name='payment_date' value='".today()."'>".space(5);	  
	      				// print dateSelector("payment_date"); 
	      			?>
	      		</td></tr>
	      		<tr><td>Name</td><td><input type='text' class='form-control w300' required placeholder="Enter account name" id='account_name'>
	      		<tr>
	      			<td>Amount</td>
	      			<td>
	      				<!-- <table>
	      					<tr>
	      						<td> -->
	      							<input type="number" name="amount" required id="payment_amount" readonly class="form-control payment_amount w100" placeholder="Amount">
	      						<!-- </td> -->
	      						<!-- <td></td>
	      						<td> &nbsp;&nbsp;
	      							<span><input type='radio' class='st' name='st' required>advance</span>
	      							<span><input type='radio' class='st' name='st'>salary</span>
	      						</td> -->
	      					<!-- </tr>
	      				</table> -->
	      			</td>
	      		</tr>
	      		<tr><td>Particulars</td><td>
	      			<!-- <br> -->
	      			<?php
								$filter = "trash=0";
  							$filter .= " AND show_in_hotel>0 ORDER BY show_in_hotel";
	      				// print sop2("bank", "", ['optional'=>true, 'attr'=>'required', 'filter'=>$filter]);
	      			?>
								<!-- <div><input class='bank-account' type='radio' name='r'>Neat & Clean May bank </div>
								<div><input class='bank-account' type='radio' name='r'>Ddcon May bank </div>
								<div><input class='bank-account' type='radio' name='r'>Bdcon May bank </div>
								<div><input class='bank-account' type='radio' name='r'>Ekawin May bank </div>
								<div><input class='bank-account' type='radio' name='r'>Keep Clean May bank </div>
								<div><input class='bank-account' type='radio' name='r'>Kt May bank personal </div>
								<div><input class='bank-account' type='radio' name='r'>Kt Rhb bank personal </div>
								<div><input class='bank-account' type='radio' name='r'>Neat & Clean Rhb bank </div>
								<div><input class='bank-account' type='radio' name='r'>Emon May bank personal </div>
								<div><input class='bank-account' type='radio' name='r'>Tutul May bank personal </div> -->
	      			<br>
	      			<?php 
								print dateSelectorOptional("banking_date-2", today(),'','','alert');    					
								?>
	      			<textarea name="particulars" class="form-control particulars w300" rows='5' id="particulars" required placeholder="Particulars"></textarea>
	      		</td>
	      		<td id='worker-list'>
	      			<table class='table table-bordered'>
	      				<tr id='worker-list-empty-row'><td colspan="2"></td></tr>
	      			</table>
	      		</td>
	      		</tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_worker_payment_2">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>



<div class="modal fade" id="save_remarks_modal" role="dialog">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
    	<form method="post" autocomplete="off" enctype='multipart/form-data'>
    		<input type="hidden" name="type" id="type">
	      <div class="modal-header">
	        <h4 class="modal-title">Notes</h4>
	        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	      </div>
	      <div class="modal-body">
	      	<table width="100%">
	      		<tr><td>Remarks</td><td><textarea name="remarks" rows="5" placeholder="Notes" class="form-control"></textarea></td></tr>
	      		<tr><td>Text Color</td><td><input type="color" name='text_color'></td></tr>
	      		<tr><td>Background Color</td><td><input type="color" name='background_color' value='#efefef'></td></tr>
	      	</table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_remarks">Save</button>
	        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>


<div id='form-hidden'></div>


<form method="post" id="save_remarks_form" mehtod="post">
	<input type="hidden" name='remarks' id='remarks'>
	<input type="hidden" name='save_remarks' id='save_remarks'>
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script type="text/javascript">
	function selectMs(){
		$("#ds").removeAttr("checked");
		$("#ms").attr("checked","checked");
	}
	function selectDs(){
		$("#ms").removeAttr("checked");
		$("#ds").attr("checked","checked");
	}
	<?php if(isset($get->h) && isset($get->add_bt) && !isset($post->save_expense)){ ?>
		setTimeout(function(){
			$("#modal-expense").modal();
		}, 500);
		// $("#btn-expense").trigger('click');
	<?php } ?>
	function showDetails(s,id,name,salary, income, total, phone, account, lock) {
		$("#phone-input").val(phone);
		if(lock == 1){
			$("#payment-button").hide();
		} else{
			$("#payment-button").show();
		}
		$("#modal-worker-payment").find('#worker').val(id);
		$("#modal-worker-details").find('#worker').val(id);
		$("#modal-worker-details").find('#account').val(account);
		if(account != ''){
			<?php if(!isuserin(['superadmin'])): ?>
				$("#modal-worker-details").find('#account').prop('disabled', true);
			<?php endif; ?>
		}
		$("#modal-worker-details").find('.modal-title').html(`${name} Salary Statement`);
		$(".worker-name").html(name);
		$("#modal-worker-details").find('.salary').html('<div>Salary: &nbsp;&nbsp;' + salary + '</div><div>Extra: &nbsp;&nbsp;' + income + '</div><div>Total Salary: &nbsp;&nbsp;' + total + '</div>');
		// $("#modal-worker-details").modal();
		$.post("ajax/hotel_payments.php", {id:id, salary:salary, income:income, total:total}, function(data){
			$("#modal-worker-details").find("tbody").html(data);
		});
	}

	$("select.worker2").change(function(e){
		var id = $("select.worker2 option:selected").val();
		var photo = $("select.worker2 option:selected").data('photo_file');
		$(".worker-photo").attr('src');
		if(photo != ""){
			$(".worker-photo").attr('src', '/app/uploads/worker/' + id + '/' + photo);
		}
	});

	$("#mon").change(function(){
		var mon = $("#mon").val() + "-01";
		location.href	= "?page=3&mon=" + mon + "<?php print $bt_url; ?>";
	});

	function setCategory(id){
		$(".worker_id").val(id);
		$("#modal-category").modal();
	}

	function setWorkerId(id){
		$(".modal-worker .worker_id").val(id);
	}

	function setWorkerIds(){
		names = "";
		// <tr id='worker-list-empty-row'><td></td><td></td></tr>
		name_count = $(".worker-payment:checked").length;
		debugger;
		$(".worker-list-item").remove();
		$($(".worker-payment:checked")).each(function (i,e) {
			var name = $(e).data('name');
			var id = $(e).data('id');
			names += (names != '' ? ', ' : '') + name;
			console.table($(e).data('id'), $(e).data('name'));
			if(name_count > 0){
				$("#worker-list-empty-row").before("<tr id='worker-list-item'><td nowrap class='w-name'>"+name+" <input type='hidden' name='workers[]' value='"+id+"'></td><td><input type='number' step='any' name='salary[]' required class='form-control w80 worker-id'></td></tr>");
				$(".worker-id").keyup(calcWorkerTotal);
			}
			$("#modal-worker-payment-2").find(".worker-name").text(names);
		});
		$(".payment_amount").attr('readonly', true);
		// if(name_count == 1){
			$("#account_name").val(names);
			$(".payment_amount").removeAttr('readonly');
		// }
	}
	function calcWorkerTotal(){
		workerTotal = 0;
		workers = ''
		$($(".worker-id")).each(function(i,e){
			var amt = parseFloat($(e).val());
			if(!isNaN(amt)){
				workerTotal += amt;
				workers += ", " + $(e).parent().parent().find('.w-name').text().trim() + ' Rm ' + amt;
			}
		});
		$("#worker-list-empty-row td").text("Total : " + workerTotal.toFixed(2));
		$("#modal-worker-payment-2 #payment_amount").val(workerTotal);
		setParticulars2();
	}

	function approvePayment(id){
		if(confirm("Are you sure?")){
			$.post("ajax/hotel_payment_approve.php", {id:id, payment:0}, function(data){
				if(data == "OK"){
					$("#hotel_statement_worker_payment_status_" + id).html("<button type='button' class='btn btn-sm btn-success'>Approved</button>");
				}
			});
		}
	}
	function addNotes(){
		Swal.fire({
		  title: 'Enter your remarks',
		  input: 'textarea',
		  showCancelButton: true,
		  confirmButtonText: 'Save',
		  preConfirm: (text) => {
		    $("#remarks").val(text);
				$("#save_remarks_form").submit();
		  }
		})
	}

	function clk(id) {
		$("#file" + id).click();
	}

	function upload(e){
		if(confirm("Are you sure?")){
			$(e).parent().submit();
		}
	}

	function deletePayment(id){
		if(prompt("Are you sure? Enter PIN") == "<?php print upin(); ?>"){
		// if(input("Are you sure?")){
			$("#form-hidden").html("<form method='post'><input type='hidden' name='remove_id' value='"+id+"'></form>");
			setTimeout(function () {
				$("#form-hidden").find("form").submit();
			}, 100);
		}
	}


	function approveIncome(id){
		if(confirm("Are you sure?")){
			$.post("ajax/hotel_income_approve.php", {id:id, income:0}, function(data){
				if(data == "OK"){
					$("#hotel_statement_worker_income_status_" + id).html("<button type='button' class='btn btn-sm btn-success'>Approved</button>");
				}
			});
		}
	}

	function deleteIncome(id, type){
		if(prompt("Are you sure? Enter PIN") == "<?php print upin(); ?>"){
			$.post("ajax/delete_hotel_payment.php", {id:id, type: type}, function(data){
				if(type == 0){
					$(".tr-income-" + id).remove();
				} else{
					$(".tr-payment-" + id).remove();
				}
			});
		// if(input("Are you sure?")){
			// $("#form-hidden").html("<form method='post'><input type='hidden' name='remove_income' value='"+id+"'></form>");
			// setTimeout(function () {
			// 	$("#form-hidden").find("form").submit();
			// }, 100);
		}
	}

	function editWorkingDays(id){
		var days = $("#w" + id).text();
		days = $("#w" + id).data('data');
		<?php //if(isuserin(['superadmin','durian', 'orange', 'apple'])): ?>
		<?php if(isuserin(['superadmin', 'orange'])): ?>
			$("#w" + id).html("<form method='post'><input type='hidden' name='id' value='"+id+"'><input type='tel' step='.01' style='width:50px' max='31' name='working_days' value='" + days + "'><button class='btn btn-success btn-sm' name='update_working_days'>Save</button></form>");
		<?php endif; ?>
		<?php if(uid() != 1): ?>
			if(days == "0"){
				$("#w" + id).html("<form method='post'><input type='hidden' name='id' value='"+id+"'><input type='tel' step='.01' style='width:50px' max='31' name='working_days' value='" + days + "'><button class='btn btn-success btn-sm' name='update_working_days'>Save</button></form>");
			}
		<?php endif; ?>
	}

	function deleteWorker(id){
		if(confirm("Are you sure?")){
			$("#form-hidden").html("<form method='post'><input type='hidden' name='remove_worker' value='"+id+"'></form>");
			setTimeout(function () {
				$("#form-hidden").find("form").submit();
			}, 100);
		}
	}

	$("#income-amount").keyup(setParticulars);

	function setParticulars(){
		var text = $("#title-month").text() + " " + $("#title-hotel").text() + " Invoice Rm " + $("#income-amount").val() ;
		$("#modal-income").find('textarea').val(text);
	}

	$("#modal-worker-payment input[type='radio']").click(function(){
		var text = $(this).parent().text();
		console.log(text)
		if(text.includes("Me2 te")){
			$(".me2-customer").find('select').attr('required', true).show();
			// $("#phone-input").attr("required", true);
		} else{
			$(".me2-customer").find('select').attr('required', false).hide();
			// $("#phone-input").attr("required", false);
		}
		var val = $("#payment_amount").val();
		var particulars = $("#_banking_date").val() + ' ' + text + ' ' + val;
		console.log(particulars)
		$("#modal-worker-payment .particulars").val(particulars);
	});

	$("#modal-worker-payment-2 input[type='radio']").click(function(){
		if($(this).hasClass('st')){
			$('.st-selected').removeClass('st-selected');
			$(this).addClass('st-selected');
		} else{
			$('.bank-account-selected').removeClass('bank-account-selected');
			$(this).addClass('bank-account-selected');
		}
		setParticulars2();
	});
	$("#account_name,#payment_amount").keyup(setParticulars2);

	$("select.bank").change(setParticulars2);

	function setParticulars2(){
		// var text = $(".bank-account-selected").parent().text();
		var text = $(".bank option:selected").text();
		var st = $(".st-selected").parent().text();
		var val = $("#modal-worker-payment-2 #payment_amount").val();
		var name = $("#modal-worker-payment-2 #account_name").val();
		var dt = $("#modal-worker-payment-2 #_banking_date-2").val();
		const date = new Date(dt);

		// Get the month abbreviation and year
		const options = { month: 'short', year: 'numeric' };
		const formattedDate = date.toLocaleDateString('en-US', options);
		var particulars = ''; //$("#modal-worker-payment-2 #_banking_date-2").val() + ' ' + text;
		// particulars += ' account theke banking kora hoyese Rm ' + val + ' ' + name + ' er account a ' + st + ' deoya hoyese, ';
		name_count = $(".worker-payment:checked").length;


		workers = ''
		if(name_count > 1){
			$($(".worker-id")).each(function(i,e){
				var amt = parseFloat($(e).val());
				if(!isNaN(amt)){
					workers += ", " + $(e).parent().parent().find('.w-name').text().trim() + ' ke ' + amt + ' taka ';
				}
			});
		}

		if(name_count > 1){
			//11-Jan-2025  account theke Alamin ke General Staff Salary er  deoya hoyese Rm 1
			// particulars += ' account theke ' + name + ' er account a <?php print isset($hotel) ? $hotel->name : ' '; ?> er ' + st + ' deoya hoyese Rm ' + val + workers;
			// particulars += `<?php print isset($hotel) ? $hotel->name : ' '; ?>, ${formattedDate} maser salary ${name} ke ${val} taka Petty cash theke payment deoya hoyese`;
			particulars += `<?php print isset($hotel) ? $hotel->name : ' '; ?>, ${formattedDate} maser salary ${workers} Petty cash theke deoya hoyese`;
		} else{
			// particulars += ' account theke ' + name + ' ke <?php print isset($hotel) ? $hotel->name : ' '; ?> er ' + st + ' deoya hoyese Rm ' + val + workers;
			particulars += `<?php print isset($hotel) ? $hotel->name : ' '; ?>, ${formattedDate} maser salary ${name} ke ${val} taka Petty cash theke deoya hoyese`;
		}

		console.log(particulars)

		$("#modal-worker-payment-2 .particulars").val(particulars);
	}

	$("#modal-expense .amount").keyup(setParticularsExpense);

	$("#modal-expense input[type='radio']").click(setParticularsExpense);

	function setParticularsExpense(){
		var exp_part = $("#modal-expense input[name='part']:checked").parent().text();
		var exp_bank = $("#modal-expense input[name='bank']:checked").parent().text();

		// var text = $(this).parent().text();
		var val = $("#modal-expense .amount").val();

		$("#modal-expense .particulars").val($("#title-hotel").text() + ' er ' + exp_part + ' Rm ' + val + ' ' + exp_bank);	
	}

	<?php if(isuserin(['superadmin'])){ ?> 
		function verify(id){
			$.post("<?php print BASEURL.APP; ?>/ajax/verify_worker_working_days.php", 
        {
            'app': '<?php print APP; ?>', 'id': id
        }, function(data){
            $("#w-" + id).html("<i class='fas fa-check-circle' style='color:limegreen; cursor: pointer' ondblclick='unverify($w->id)'></i>");
        }
      );
		}
		function unverify(id){
			$.post("<?php print BASEURL.APP; ?>/ajax/verify_worker_working_days.php", 
        {
            'app': '<?php print APP; ?>', 'id': id, 'un': 'un'
        }, function(data){
            $("#w-" + id).html("<i class='fas fa-check-circle' style='color:grey; cursor: pointer' onclick='verify($w->id)'></i>");
        }
      );
		}
	<?php } ?>
</script>