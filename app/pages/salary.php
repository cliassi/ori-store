<?php $page = 18;
$get->page = $page;
ensureMysqlColumn('hotel_statement_worker', 'meal', "DECIMAL(10,2) NOT NULL DEFAULT 0");
ensureMysqlColumn('hotel_statement_worker', 'visa', "DECIMAL(10,2) NOT NULL DEFAULT 0");
ensureMysqlColumn('hotel_statement_worker', 'monthly_working', "INT NOT NULL DEFAULT 0"); ?>
<style type="text/css">
	.large {
		font-size: 14px;
		text-transform: uppercase;
		font-weight: 700;
	}

	th {
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
	div span {
		display: inline-block;
	}

	.special,
	.special a {
		font-size: 2rem;
		font-weight: 700;
		color: magenta !important;
		background: cyan;
	}

	#hints {
		padding-left: 5px;
	}

	input[type="radio"] {
		padding-right: 5px;
		margin-right: 5px;
	}

	.spc {
		font-weight: 700;
		border: solid 1px #aaf;
		padding: 3px;
		border-radius: 3px;
	}

	.spc-1 {
		background-color: palegreen;
	}

	.spc-2 {
		background-color: aqua;
	}

	.spc-3 {
		background-color: yellow;
	}

	.spc-4 {
		background-color: pink;
	}

	.attendance-sheet div {
		margin-top: 10px;
		background-color: #efefef;
		border-radius: 5px;
		box-shadow: 0 0 3px #000;
		padding: 10px 30px;
		text-align: center;
		cursor: pointer;
	}

	.attendance-sheet i {
		position: absolute;
		right: 20px;
		/*    top: 16px;*/
	}

	.btn-A {
		background-color: #5bc0de;
		border-color: #46b8da;
		color: #fff;
	}

	.btn-B {
		background-color: #337ab7;
		border-color: #2e6da4;
		color: #fff;
	}

	.btn-C {
		background-color: #f0ad4e;
		border-color: #eea236;
		color: #fff;
	}

	.btn-D {
		background-color: #d9534f;
		border-color: #d43f3a;
		color: #fff;
	}

	.btn-E {
		background-color: #5bc0de;
		border-color: #46b8da;
	}

	.notice {
		color: orangered;
		font-weight: 700;
		margin-bottom: 10px;
	}

	.fa-lock {
		color: red;
	}

	.fa-unlock {
		color: grey;
	}

	td {
		vertical-align: middle !important;
	}

	#modal-worker-payment-2 .worker-payment-layout {
		width: 100%;
		table-layout: fixed;
	}

	#modal-worker-payment-2 .worker-payment-form-cell {
		width: calc(100% - 380px);
		vertical-align: top;
	}

	#modal-worker-payment-2 #worker-list {
		width: 380px;
		min-width: 380px;
		max-width: 380px;
		overflow-x: auto;
		vertical-align: top;
	}

	#modal-worker-payment-2 #worker-list table {
		width: 100%;
		table-layout: fixed;
	}

	#modal-worker-payment-2 #worker-list .w-name {
		white-space: normal;
		word-break: break-word;
		margin-top: 15px !important;
	}

	#modal-worker-payment-2 #worker-list .worker-id {
		min-width: 90px;
	}

	#modal-worker-payment-2 #worker-list .worker-list-item td {
		word-wrap: break-word;
	}

	#modal-worker-payment-2 .worker-name {
		word-break: break-word;
		max-width: 0;
		width: 100%;
	}

	#modal-worker-payment-2 .table>tbody>tr>td {
		word-break: break-word;
	}
</style>
<?php
// $mon = isset($get->mon) ? substr($get->mon, 0, 7) : (date("d", time()) <= 10 ? date("Y-m", strtotime(subMonth(1))) : date("Y-m", time()));
$mon = isset($get->mon) ? substr($get->mon, 0, 7) : date("Y-m", time());

if (uid() == 1 && isset($get->approve)) {
	if ($get->approve == 'Income')
		update("hotel_income", "status='Approved'", "id=$get->id");
	if ($get->approve == 'Expense')
		update("hotel_expense", "status='Approved'", "id=$get->id");
	if ($get->approve == 'ExpenseEntry')
		update("expense_account_entry", "approve_by=" . uid() . ", approve_time='" . now() . "'", "id=$get->id");
	if ($get->approve == 'Capital')
		update("hotel_capital", "status='Approved'", "id=$get->id");
	if ($get->approve == 'Withdraw')
		update("hotel_withdraw", "status='Approved'", "id=$get->id");
}

$bt_url = "";
if (isset($get->add_bt)) {
	$bt_url = "&add_bt=$get->add_bt";
}

if (isset($post->save_remarks)) {
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

if (uid() == 1 && isset($get->del)) {
	if (isset($get->conf)) {

		if ($get->del == 'Income')
			del("hotel_income", "id=$get->id");
		if ($get->del == 'Expense')
			del("hotel_expense", "id=$get->id");
		redir("?page=$get->page&h=$get->h&statement");

	} else {
		?>
		<script type="text/javascript">
			if (prompt("Please key in your PIN to remove this <?php print title($controller); ?>?") == "<?php print upin(); ?>") {
				location.href = "?conf&<?php print "page=$get->page&h=$get->h&statement&del=$get->del&id=$get->id"; ?>";
			} else {
				alert("Wrong PIN entered!");
				location.href = "?<?php print "page=$get->page&h=$get->h&statement"; ?>";
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

if (isUserIn(['superadmin']) || uid() == 47) {
	if (isset($post->set_category)) {
		$worker = R::load("hotel_statement_worker", $post->id);
		$worker->category = $post->set_category;
		R::store($worker);
	}

	if (isset($get->lock)) {
		$w = R::load("hotel_statement_worker", $get->w);
		$w->lock = $get->lock == 0 ? 1 : 0;
		R::store($w);
		redir("?page=3&h=" . $get->h);
	}
}

// var_dump($_FILES);

if (count($_FILES)) {
	$hotel_statement = R::load("hotel_statement", $get->h);

	if (isset($_FILES['file1']['name']) && nn($_FILES['file1']['name'])) {
		$hotel_attendance = upload($_FILES, 'file1', "uploads/hotel_attendance/$get->h", 'file1');
		// dd($hotel_attendance);
		$hotel_statement->file1 = $hotel_attendance;
	}

	if (isset($_FILES['file2']['name']) && nn($_FILES['file2']['name'])) {
		$hotel_attendance = upload($_FILES, 'file2', "uploads/hotel_attendance/$get->h", 'file2');
		$hotel_statement->file2 = $hotel_attendance;
	}

	if (isset($_FILES['file3']['name']) && nn($_FILES['file3']['name'])) {
		$hotel_attendance = upload($_FILES, 'file3', "uploads/hotel_attendance/$get->h", 'file3');
		$hotel_statement->file3 = $hotel_attendance;
	}

	if (isset($_FILES['file4']['name']) && nn($_FILES['file4']['name'])) {
		$hotel_attendance = upload($_FILES, 'file4', "uploads/hotel_attendance/$get->h", 'file4');
		$hotel_statement->file4 = $hotel_attendance;
	}

	// vd($hotel_statement);
	R::store($hotel_statement);
}

if (isset($post->save_hotel)) {
	$loan = R::dispense("hotel");
	$loan->name = $post->name;
	$loan->basic = 0;
	$loan->type = 'salary';
	R::store($loan);
	$account = accMan(2, $post->name, ['contexttype' => 'hotel', 'contextid' => $loan->id]);
	if ($account) {
		$loan->accountid = $account->id;
	}
	R::store($loan);
	// redir("?page=3");
}
ensureMysqlColumn('hotel_statement', 'enum', "ENUM('meal','salary','hotel') NOT NULL DEFAULT 'salary' COMMENT 'Type of statement'");

// Handle duplicate deletion and recreation
if (isset($get->conf_del_duplicate)) {
	$oldStatement = R::load("hotel_statement", $get->conf_del_duplicate);
	if ($oldStatement && $oldStatement->id) {
		$workers = R::find("hotel_statement_worker", "statement=?", [$oldStatement->id]);
		foreach ($workers as $w) {
			$incomes = R::find("hotel_statement_worker_income", "worker=?", [$w->id]);
			foreach ($incomes as $inc) R::trash($inc);
			$payments = R::find("hotel_statement_worker_payment", "worker=?", [$w->id]);
			foreach ($payments as $pmt) {
				$entry = R::findOne("expense_account_entry", "entry_id=? AND entry_type=?", [$pmt->id, 'Factory - Salary Payment']);
				if ($entry) R::trash($entry);
				$receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$pmt->id]);
				if ($receipt) R::trash($receipt);
				R::trash($pmt);
			}
			R::trash($w);
		}
		$remarks = R::find("hotel_statement_remarks", "statement=?", [$oldStatement->id]);
		foreach ($remarks as $rm) R::trash($rm);
		R::trash($oldStatement);
	}
	$hotel = R::load("hotel", $get->hotel);
	$loan = R::dispense("hotel_statement");
	$loan->type = $get->type;
	$loan->enum = 'salary';
	$loan->month = $get->month;
	$loan->hotel = $get->hotel;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	$loan->accountid = isset($get->expense_account) && $get->expense_account ? $get->expense_account : null;
	R::store($loan);
	redir("?page=3&h=$loan->id");
}

if (isset($post->save_statement)) {
	$month = isset($post->month) ? $post->month : $post->month2;
	$existingStatement = R::findOne('hotel_statement', 'hotel = ? AND month = ? AND type = ? AND enum = ?', [$post->hotel, $month, $post->type, 'salary']);
	if ($existingStatement && $existingStatement->id) {
		?>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
			Swal.fire({
				icon: 'warning',
				title: 'Duplicate Entry',
				text: 'A statement for this hotel and month already exists. Delete it and create a new one?',
				showCancelButton: true,
				confirmButtonText: 'Yes, Delete & Recreate',
				cancelButtonText: 'Cancel'
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = '?page=3&conf_del_duplicate=<?php echo $existingStatement->id; ?>&hotel=<?php echo $post->hotel; ?>&month=<?php echo $month; ?>&type=<?php echo $post->type; ?>&expense_account=<?php echo isset($post->expense_account) ? $post->expense_account : ''; ?>';
				} else {
					window.location.href = '?page=3';
				}
			});
		</script>
		<?php
		exit;
	}
	$hotel = R::load("hotel", $post->hotel);
	$loan = R::dispense("hotel_statement");
	$loan->type = $post->type;
	$loan->enum = 'salary';
	$loan->month = $month;
	$loan->hotel = $post->hotel;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	$loan->accountid = isset($post->expense_account) && $post->expense_account ? $post->expense_account : null;
	try {
		R::store($loan);
	} catch (Exception $e) {
		if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
			?>
			<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
			<script>
				Swal.fire({
					icon: 'error',
					title: 'Duplicate Entry',
					text: 'A statement for this hotel, month and type already exists.',
					confirmButtonText: 'OK'
				}).then(() => {
					window.location.href = '?page=3';
				});
			</script>
			<?php
			exit;
		}
		throw $e;
	}

	//Account
	// $account = accMan($hotel->accountid, date("M Y", strtotime("{$post->month}-01")), ['contexttype'=>'hotel_statement', 'contextid'=>$loan->id]);
	// $loan->accountid = $account->id;
	// R::store($loan);
	redir("?page=3&h=$loan->id");
}
if (isset($get->delHotel)) {
	if (isset($get->conf)) {
		$statement = R::load("hotel_statement", $get->delHotel);

		// Delete hotel_statement_meal workers + their payments/incomes/expense entries
		$mealWorkers = R::find("hotel_statement_meal", "statement=?", [$statement->id]);
		foreach ($mealWorkers as $mw) {
			$mealIncomes = R::find("hotel_statement_meal_income", "worker=?", [$mw->id]);
			foreach ($mealIncomes as $mi)
				R::trash($mi);

			$mealPayments = R::find("hotel_statement_meal_payment", "worker=?", [$mw->id]);
			foreach ($mealPayments as $mp) {
				$me = R::findOne("expense_account_entry", "entry_id=? AND entry_type=?", [$mp->id, 'Factory - Meal Payment']);
				if ($me)
					R::trash($me);
				R::trash($mp);
			}
			R::trash($mw);
		}

		// Delete hotel_statement_worker workers + their payments/incomes/expense entries
		$workers = R::find("hotel_statement_worker", "statement=?", [$statement->id]);
		foreach ($workers as $w) {
			$incomes = R::find("hotel_statement_worker_income", "worker=?", [$w->id]);
			foreach ($incomes as $inc)
				R::trash($inc);

			$payments = R::find("hotel_statement_worker_payment", "worker=?", [$w->id]);
			foreach ($payments as $pmt) {
				$entry = R::findOne("expense_account_entry", "entry_id=? AND entry_type=?", [$pmt->id, 'Factory - Salary Payment']);
				if ($entry)
					R::trash($entry);
				$receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$pmt->id]);
				if ($receipt)
					R::trash($receipt);
				R::trash($pmt);
			}
			R::trash($w);
		}

		// Delete remarks
		$remarks = R::find("hotel_statement_remarks", "statement=?", [$statement->id]);
		foreach ($remarks as $rm)
			R::trash($rm);

		R::trash($statement);
		redir("?page=$get->page&mon=$mon");

	} else {
		?>
		<script type="text/javascript">
			if (prompt("Please key in your PIN to remove this <?php print title($controller); ?>?") == "<?php print upin(); ?>") {
				location.href = "?conf&<?php print "page=$get->page&delHotel=$get->delHotel&mon=$mon"; ?>";
			} else {
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

if (isset($get->duplicate)) {
	$statement = R::load("hotel_statement", $get->duplicate);

	// Check if a statement already exists for this hotel and month
	$ym = explode("-", $statement->month);
	$y = $ym[0];
	$m = $ym[1] + 1;
	if ($m > 12) {
		$y += 1;
		$m = 1;
	}
	$newMonth = "$y-" . zerofill($m, 2);

	$existingStatement = R::findOne('hotel_statement', 'hotel = ? AND month = ? AND type = ? AND enum = ?', [$statement->hotel, $newMonth, $statement->type, 'salary']);
	if ($existingStatement) {
		// Show SweetAlert and redirect back
		?>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
			Swal.fire({
				icon: 'error',
				title: 'Duplicate Entry',
				text: 'A salary statement for this hotel and month already exists. Please select a different Factory Salary.',
				confirmButtonText: 'OK'
			}).then(() => {
				window.location.href = '?page=3&mon=<?php echo $get->mon; ?>';
			});
		</script>
		<?php
		exit;
	}

	$loan = R::dispense("hotel_statement");
	$loan->month = $newMonth;
	$loan->hotel = $statement->hotel;
	$loan->type = $statement->type;
	$loan->enum = 'salary';
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
		$worker->meal = $w->meal;
		$worker->visa = $w->visa;
		$worker->monthly_working = $w->monthly_working;
		$worker->account = $w->account;
		$worker->working_days = 0;
		$worker->entry_by = uid();
		$worker->entry_time = now();
		R::store($worker);
	}

	redir("?page=3&h=$loan->id");
}
if (isset($post->save_worker)) {
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
if (isset($post->save_worker_update)) {
	$worker = R::load("hotel_statement_worker", $post->id);
	if (nn($post->name))
		$worker->name = $post->name;
	if (nn($post->basic))
		$worker->basic = $post->basic;
	// if(nn($post->pay)) $worker->pay = $post->pay;
	// if(nn($post->billed_amount)) $worker->billed_amount = $post->billed_amount;
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if (isset($post->add_salary)) {
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
if (isset($post->save_worker_account)) {
	$worker = R::load("staff_salary", $post->id);
	$worker->account = $post->worker_account;
	R::store($worker);
}

if (isset($post->deduct_salary)) {
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
if (isset($post->update_working_days)) {
	$worker = R::load("hotel_statement_worker", $post->id);
	$worker->working_days = $post->working_days;
	$worker->working_hours = 0;
	$worker->public_holiday = 0;
	$worker->mc = 0;
	if (strpos($post->working_days, ".")) {
		$dh = explode(".", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->working_hours = strlen($dh[1]) == 1 ? ($dh[1] + 0) * 10 : ($dh[1] + 0);
	} elseif (strpos($post->working_days, "++")) {
		$dh = explode("++", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->public_holiday = $dh[1] + 0;
	} elseif (strpos($post->working_days, "+")) {
		$dh = explode("+", $post->working_days);
		$worker->working_days = $dh[0] + 0;
		$worker->mc = $dh[1] + 0;
	}
	R::store($worker);
}
if (isset($post->save_meal)) {
	$worker = R::load("hotel_statement_worker", $post->id);
	$worker->meal = (float) $post->meal;
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if (isset($post->save_visa)) {
	$worker = R::load("hotel_statement_worker", $post->id);
	$worker->visa = (float) $post->visa;
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if (isset($post->save_monthly_working)) {
	$worker = R::load("hotel_statement_worker", $post->id);
	$worker->monthly_working = (int) $post->monthly_working;
	R::store($worker);
	redir("?page=3&h=$get->h");
}
if (isset($post->remove_id)) {
	$payment = R::load("hotel_statement_worker_payment", $post->remove_id);
	$official_receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$payment->id]);
	if ($official_receipt) {
		// dd($official_receipt);
		R::trash($official_receipt);
	}
	// dd($payment);
	R::trash($payment);
}
if (isset($post->remove_income)) {
	$payment = R::load("hotel_statement_worker_income", $post->remove_income);
	R::trash($payment);
}
if (isset($post->remove_worker)) {
	$remove_worker = R::load("hotel_statement_worker", $post->remove_worker);

	$incomes = R::find("hotel_statement_worker_income", "worker=?", [$remove_worker->id]);
	foreach ($incomes as $income) {
		R::trash($income);
	}

	$payments = R::find("hotel_statement_worker_payment", "worker=?", [$remove_worker->id]);
	foreach ($payments as $payment) {
		$entry = R::findOne("expense_account_entry", "entry_id=? AND entry_type=?", [$payment->id, 'Factory - Salary Payment']);
		if ($entry)
			R::trash($entry);

		$receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$payment->id]);
		if ($receipt)
			R::trash($receipt);

		R::trash($payment);
	}

	R::trash($remove_worker);
}
if (isset($post->save_worker_payment)) {
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$w = R::load("hotel_statement_worker", $post->worker);
	$me2 = false;
	if (isset($post->phone) && nn($post->phone)) {
		$me2 = true;
		if (!nn($w->phone)) {
			$w->phone = $post->phone;
			R::store($w);
		}
	}
	// var_dump($statement);
	// vd($post);
	$salary = ($statement->type == 'Parttime' ? round($w->basic * ($w->working_days + ($w->working_hours / 100)), 2) : round($w->basic / 30 * ($w->working_days + ($w->working_hours / 100))));
	$income = getSum("hotel_statement_worker_income", "amount", "worker=$post->worker AND branch_id=" . bid());
	$salary = $salary + $income;
	// dd($salary);
	$paid = mysqli_fetch_object(select("SELECT IFNULL(SUM(amount),0) paid FROM `hotel_statement_worker_payment` WHERE worker=$post->worker AND branch_id=" . bid()));

	// if(($salary >= ($paid->paid + $post->amount)) || (!$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time() > 14))){

	// $notOverpaid  = $salary >= ($paid->paid + $post->amount);
	// $notAdvancedOverpaid = !$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time() > 14);

	// vd([$salary, $paid->paid , $post->amount]);
	// vd($notOverpaid);
	// dd($notAdvancedOverpaid);

	// if($notOverpaid && $notAdvancedOverpaid){
	// if(($salary >= ($paid->paid + $post->amount)) || (!$w->working_days && ($post->amount + $paid->paid) <= 1500 && $post->amount > 0 && date("d", time()) > 14 && date("d", time()) < 21)){
	// dd([$salary, $income, $paid->paid + $post->amount]);
	$pay = true;
	if (true) {
		$payment = R::dispense("hotel_statement_worker_payment");
		$payment->worker = $post->worker;
		$payment->date = $post->payment_date;
		$payment->amount = $post->amount;
		$payment->particulars = $post->particulars;
		$payment->entry_by = uid();
		$payment->entry_time = now();

		if (isset($post->worker) && strpos($payment->particulars, 'Salary theke permit') !== FALSE) {
			$payment->worker = $post->worker;
		}
		R::store($payment);

		if (isset($hotel->accountid) && $hotel->accountid > 0) {
			// Validate that the hotel accountid exists in expense_account table
			$accountExists = R::findOne('expense_account', 'id = ?', [$hotel->accountid]);
			if ($accountExists) {
				try {
					// $particulars = "$hotel->name, " . date("M Y", strtotime("{$statement->month}-01")) . " maser salary $payment->particulars";
					$particulars = "$hotel->name, $post->particulars";

					// Check if expense entry already exists for this payment
					$existingEntry = R::findOne('expense_account_entry', 'entry_id = ? AND entry_type = ?', [$payment->id, 'Factory - Salary Payment']);

					if (!$existingEntry || !$existingEntry->id) {
						$existingEntry = R::dispense('expense_account_entry');
						$existingEntry->entry_time = now();
					}

					$existingEntry->accountid = (int) $hotel->accountid;
					$existingEntry->amount = (float) $post->amount;
					$existingEntry->particulars = $particulars;
					$existingEntry->remarks = 'Factory - Salary Payment';
					$existingEntry->entry_type = 'Factory - Salary Payment';
					$existingEntry->entry_id = (int) $payment->id;
					$existingEntry->tran_type = 'Debit';
					$existingEntry->entry_by = uid();
					$existingEntry->month = $statement->month;
					$existingEntry->expense_date = $post->payment_date;
					$existingEntry->accountpath = $accountExists->path;
					$existingEntry->company = $accountExists->company;
					$existingEntry->branch_id = bid();
					$existingEntry->payment_method = 'Cash';
					$existingEntry->modify_by = uid();
					$existingEntry->modify_time = now();

					$entryId = R::store($existingEntry);
					$payment->account_entry_id = $entryId;
					R::store($payment);
				} catch (Exception $e) {
					// Log error but don't fail the payment
				}
			}
		}

		if (isset($post->transfer_customer) && strpos($payment->particulars, "Me2 te") !== FALSE) {
			$payment->transfer_customer = $post->transfer_customer;
			$customer = R::load("transfer_customer", $post->transfer_customer);
			$transfer_tran = R::dispense("transfer_tran");
			$transfer_tran->company = 11;
			$transfer_tran->customer = $customer->id;
			$transfer_tran->date = $post->payment_date;
			$transfer_tran->particulars = "$w->name $hotel->name staff  " . date("F Y", strtotime($statement->month . "-01")) . " ,Hotel salary theke me2 te taka pathano hoyese";
			$transfer_tran->method = 'Cash';
			$transfer_tran->amount = $post->amount;
			$transfer_tran->entry_by = uid();
			$transfer_tran->entry_time = now();
			$transfer_tran->hotel_payment_ref = $payment->id;
			R::store($transfer_tran);
		}

		if (isset($post->worker) && strpos($payment->particulars, 'Salary theke permit') !== FALSE) {
			$customer = R::load("worker", $post->worker2);
			$official_receipt = R::dispense("official_receipt");
			$official_receipt->customer_id = $customer->id;
			$official_receipt->date = $post->payment_date;
			$official_receipt->amount = $post->amount;
			$official_receipt->payment_mode = 'Cash';
			$official_receipt->account = aid();
			$official_receipt->remarks = "$w->name $hotel->name staff  " . date("F Y", strtotime($statement->month . "-01")) . " ,Hotel salary theke permit er jonno te taka kata hoyese";
			$official_receipt->entry_by = uid();
			$official_receipt->entry_time = now();
			$official_receipt->hotel_payment_id = $payment->id;

			R::store($official_receipt);

			$entryO = accountEntryO('Official Receipt', $official_receipt->remarks, $official_receipt->amount, 'Credit', ['entry_id' => $official_receipt->id, 'entry_type' => 'Official Receipt']);

			$official_receipt->account_entry_id = $entryO->id;

			R::store($official_receipt);
		}
		redir("?page=3&h=$get->h");
	}
}
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

//save_worker_payment_2
if (isset($post->save_worker_payment_2)) {
	require_once 'salary_log.php';
	ensureMysqlColumn('expense_account_entry', 'investment_id', "INT NULL DEFAULT NULL");
	ensureMysqlColumn('expense_account_entry', 'hotel', "INT NULL DEFAULT NULL COMMENT 'Associated hotel ID'");

	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);

	//salary_log("Starting salary payment processing for statement: {$get->h}");
	//salary_log("Hotel: {$hotel->name}, Account ID: {$hotel->accountid}");

	foreach ($post->workers as $key => $worker) {
		// Use salary_hidden[] if salary[] is empty (for non-admin users with disabled inputs)
		$worker_salary = !empty($post->salary[$key]) ? $post->salary[$key] : (isset($post->salary_hidden[$key]) ? $post->salary_hidden[$key] : '');
		$w = R::load("hotel_statement_worker", $worker);

		//salary_log("Processing worker {$worker} - {$w->name}, salary: {$worker_salary}");

		$me2 = false;
		if (isset($post->phone) && nn($post->phone)) {
			$me2 = true;
			if (!nn($w->phone)) {
				$w->phone = $post->phone;
				R::store($w);
			}
		}

		$salary = ($statement->type == 'Parttime' ? round($w->basic * ($w->working_days + ($w->working_hours / 100)), 2) : round($w->basic / 30 * ($w->working_days + ($w->working_hours / 100))));
		if ($statement->hourly && $w->working_days < 325) {
			$salary -= 100;
		}
		$income = getSum("hotel_statement_worker_income", "amount", "worker=$w->id");
		$salary = $salary + $income;
		$paid = mysqli_fetch_object(select("SELECT IFNULL(SUM(amount),0) paid FROM `hotel_statement_worker_payment` WHERE worker=$w->id AND branch_id=" . bid()));

		//salary_log("Payment validation details - Worker: {$w->name}, UID: " . uid() . ", Working days: {$w->working_days}, Basic salary: {$w->basic}, Calculated salary: {$salary}, Paid: {$paid->paid}, This payment: {$worker_salary}, Current date: " . date("d") . ", Current time: " . time());

		$isAdvance = isset($post->salary_type) && $post->salary_type === 'Advance';
		$pay = true;

		//salary_log("Payment validation - Salary: {$salary}, Paid: {$paid->paid}, This payment: {$worker_salary}, Can pay: " . ($pay ? 'YES' : 'NO') . ", UID: " . uid());

		if ($worker_salary > 0) {
			$payment = R::dispense("hotel_statement_worker_payment");
			$payment->worker = $w->id;
			$payment->date = $post->payment_date;
			$payment->amount = $worker_salary;
			$payment->bank = isset($post->bank) ? $post->bank : null;

			// Use individual particulars from the array
			$payment->particulars = isset($post->particulars[$key]) ? $post->particulars[$key] : '';

			$payment->entry_by = uid();
			$payment->entry_time = now();

			if (isset($w->id) && strpos($payment->particulars, 'Salary theke permit') !== FALSE) {
				$payment->worker = $w->id;
			}
			if (isset($post->transfer_customer) && strpos($payment->particulars, "Me2 te") !== FALSE) {
				$payment->transfer_customer = $post->transfer_customer;
			}

			try {
				$paymentId = R::store($payment);
				//salary_log("Payment stored successfully with ID: {$paymentId}");

				// Create expense entry for each worker payment - Direct RedBeanPHP approach
				$selectedAccountId = isset($post->expense_account_id) && $post->expense_account_id > 0 ? (int) $post->expense_account_id : (int) ($hotel->accountid ? $hotel->accountid : 0);
				if ($selectedAccountId > 0) {
					// Validate that the selected account exists
					$accountExists = R::findOne('expense_account', 'id = ?', [$selectedAccountId]);
					if (!$accountExists) {
						//salary_log("ERROR: Selected account ID {$selectedAccountId} does not exist in expense_account table");
						continue;
					}

					try {
						// $particulars = "$hotel->name, " . date("M Y", strtotime("{$statement->month}-01")) . " maser salary $payment->particulars";
						$particulars = "$hotel->name, $payment->particulars";

						// Check if expense entry already exists for this payment
						$existingEntry = R::findOne('expense_account_entry', 'entry_id = ? AND entry_type = ?', [$payment->id, 'Factory - Salary Payment']);

						if (!$existingEntry || !$existingEntry->id) {
							$existingEntry = R::dispense('expense_account_entry');
							$existingEntry->entry_time = now();
						}

						$existingEntry->accountid = $selectedAccountId;
						$existingEntry->amount = (float) $worker_salary;
						$existingEntry->particulars = $particulars;
						$existingEntry->remarks = 'Factory - Salary Payment';
						$existingEntry->entry_type = 'Factory - Salary Payment';
						$existingEntry->entry_id = (int) $payment->id;
						$existingEntry->tran_type = 'Debit';
						$existingEntry->entry_by = uid();
						$existingEntry->month = $statement->month;
						$existingEntry->expense_date = $post->payment_date;
						$existingEntry->accountpath = $accountExists->path;
						$existingEntry->company = $accountExists->company;
						$existingEntry->branch_id = bid();
						$existingEntry->modify_by = uid();
						$existingEntry->modify_time = now();
						$existingEntry->opex_or_capex = isset($post->opex_or_capex) ? $post->opex_or_capex : 'Capex';
						$pmRaw = isset($post->payment_method) ? strtolower($post->payment_method) : 'cash';
						$existingEntry->payment_method = in_array($pmRaw, ['bank', 'online'], true) ? 'Bank' : 'Cash';
						$existingEntry->hotel = (int) $hotel->id;

						// Handle investment entry
						if (isset($post->is_investment) && (int) $post->is_investment === 1) {
							$existingEntry->investment_id = createFormInvestmentEntry(
								$post->payment_date,
								(float) $worker_salary,
								$particulars,
								$existingEntry->payment_method,
								(int) ($existingEntry->investment_id ? $existingEntry->investment_id : 0)
							);
						}

						$entryId = R::store($existingEntry);
						$payment->account_entry_id = $entryId;
						R::store($payment);

						//salary_log("Expense entry created with ID: {$entryId}");
						//salary_log("Expense particulars: {$particulars}");
					} catch (Exception $e) {
						//salary_log("ERROR: Failed to create expense entry: " . $e->getMessage());
					}
				} else {
					//salary_log("ERROR: Hotel account ID missing or invalid: " . (isset($hotel->accountid) ? $hotel->accountid : 'NULL'));
				}

			} catch (Exception $e) {
				//salary_log("ERROR: Failed to store payment: " . $e->getMessage());
			}
		}
	}

	//salary_log("Salary payment processing completed");
	redir("?page=3&h=$get->h");
}
if (isset($post->save_loan)) {
	$loan = R::dispense("hotel_loan");
	$loan->hotel = $get->h;
	$loan->direction = $post->direction;
	$loan->date = isset($post->date_loan) ? $post->date_loan : today();
	;
	$loan->amount = $post->amount;
	$loan->account = $post->account;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if (isset($post->save_expense)) {
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);
	$loan = R::dispense("hotel_expense");
	$loan->hotel = $hotel->id;
	$loan->statement = $get->h;
	$loan->payment_method = $post->payment_method;
	$loan->date = isset($post->date_expense) ? $post->date_expense : today();
	;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();

	R::store($loan);

	//NEW EXPENSE ENTRY
	$particulars = date("M Y", strtotime("{$statement->month}-01")) . " $post->particulars";
	$entry = accountEntry($hotel->accountid, $particulars, $post->amount, 'Debit', ['entry_id' => $loan->id, 'entry_type' => 'Hotel - Expense', 'url' => "/store/?page=3&h=$get->h&statement", 'month' => $statement->month]);
	$loan->account_entry_id = $entry->id;
	if (isset($get->add_bt)) {
		update("expense_account_entry", "bank_transaction=$get->add_bt", "id=$entry->id");
		update("hotel_expense", "bank_transaction=$get->add_bt", "id=$loan->id");
		update("bank_transaction_item", "expense_entry=$entry->id, hotel_expense=$loan->id", "id=$get->add_bt");
	}
	R::store($loan);

	redir("?page=3&h=$get->h");
}
if (isset($post->save_income)) {
	$statement = R::load("hotel_statement", $get->h);
	$hotel = R::load("hotel", $statement->hotel);

	$loan = R::dispense("hotel_income");
	$loan->hotel = $hotel->id;
	$loan->statement = $get->h;
	$loan->date = isset($post->date_income) ? $post->date_income : today();
	;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);

	//NEW EXPENSE ENTRY
	$particulars = date("M Y", strtotime("{$statement->month}-01")) . " $post->particulars";
	$entry = accountEntry($hotel->accountid, $particulars, $post->amount, 'Credit', ['entry_id' => $loan->id, 'entry_type' => 'Hotel - Income', 'month' => $statement->month]);
	$loan->account_entry_id = $entry->id;
	R::store($loan);


	redir("?page=3&h=$get->h");
}
if (isset($post->save_hotel_invoice)) {
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
if (isset($post->save_capital)) {
	$loan = R::dispense("hotel_capital");
	$loan->hotel = $get->h;
	$loan->date = isset($post->date_capital) ? $post->date_capital : today();
	;
	$loan->amount = $post->amount;
	$loan->type = $post->type;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if (isset($post->save_withdraw)) {
	$loan = R::dispense("hotel_withdraw");
	$loan->hotel = $get->h;
	$loan->date = isset($post->date_withdraw) ? $post->date_withdraw : today();
	;
	$loan->amount = $post->amount;
	$loan->particulars = $post->particulars;
	$loan->entry_by = uid();
	$loan->entry_time = now();
	R::store($loan);
	redir("?page=3&h=$get->h");
}
if (isset($post->approve)) {
	$worker = R::load("hotel_statement_worker", $post->worker);
	$worker->approved = 1;
	R::store($worker);
}

print "<br><br>";
if (!isset($get->h))
	print "<a class='pointer btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-create-fulltime'>Create Fulltime Staff Statement</a>" . space(2) . "<a class='pointer btn btn-secondary' data-bs-toggle='modal' data-bs-target='#modal-create-parttime'>Create Parttime Staff Statement</a>";

if (isset($get->h)) {
	$hotel_statement = R::load("hotel_statement", $get->h);
	$hourly = $hotel_statement->hourly == 1;
	$dp = $hourly ? 2 : 0;
	$mon = $hotel_statement->month;
}

$date = date("Y-m-d", strtotime("$mon-01"));

// print $date;
// print addMonth(1,$date);
print "<div style='display:flex; align-items:center; justify-content:space-between;'>";
if (isset($get->h)) {
	$hotel = R::load("hotel", $hotel_statement->hotel);
	print "<div><a class='btn btn-info' href='?page=3'>Back</a></div>";
	print "<div style='flex:1; text-align:center;'><h1 class='center panel panel-success' style='display:inline-block; margin:0;'><span id='title-hotel'>$hotel->name</span> Statement - <span id='title-month'>" . date("M Y", strtotime("{$hotel_statement->month}-01")) . "</span></h1></div>";

	// Find prev/next hotel_statement for this hotel
	$prevStmt = R::findOne("hotel_statement", "hotel=? AND id<? ORDER BY id DESC", [$hotel_statement->hotel, $hotel_statement->id]);
	$nextStmt = R::findOne("hotel_statement", "hotel=? AND id>? ORDER BY id ASC", [$hotel_statement->hotel, $hotel_statement->id]);
	// If no prev/next statement, fall back to listing view with month navigation
	$prevLink = $prevStmt && $prevStmt->id ? "?page=3&h={$prevStmt->id}" : "?page=3&mon=" . subMonth(1, $date);
	$nextLink = $nextStmt && $nextStmt->id ? "?page=3&h={$nextStmt->id}" : "?page=3&mon=" . addMonth(1, $date);
} else {
	$hotel = null;
	print "<div></div><div style='flex:1;'></div>";
	$prevLink = "?page=3&mon=" . subMonth(1, $date) . "$bt_url";
	$nextLink = "?page=3&mon=" . addMonth(1, $date) . "$bt_url";
}
print "<div>
	<a class='pointer btn btn-info' href='$prevLink'><i class='fa fa-chevron-left'></i>Prev</a>" . space(5) .
	monthSelector('mon', date("Y-m-d", strtotime($mon . "-01"))) .
	space(5) .
	"<a class='pointer btn btn-info' href='$nextLink'>Next <i class='fa fa-chevron-right'></i></a>
</div>
</div>";
print "<br><br>";

// Month	Hotel	Worker	Income	Expense	Profit	Status
// NOV,2022	aloft	20				Close

if (isset($get->h)) {
	print "
		<table class='table table-bordered table-striped'>
			<thead>
				<tr>
					<th><i class='fa fa-lock'></i></th>
					<th>No</th>
					<th>" . "<i class='fa fa-shopping-cart pointer' data-bs-toggle='modal' data-bs-target='#modal-worker-payment-2' onClick='setWorkerIds()'></i><br><input type='checkbox' id='worker-payment-select-all' title='Select all workers'>" . "</th>
					<th>Name</th>
				<th>" . ($hotel_statement->hourly ? 'Monthly Salary<br>Per Hour' : 'Monthly<br>Salary') . "</th>
				<th>Meal</th>
				<th>Visa</th>
				<th>Total<br>Salary</th>
				<th>Monthly<br>Working</th>
				<th>Per Day<br>Salary</th>
				<th colspan='2'>" . ($hotel_statement->hourly ? 'Working<br>Hours' : 'Working<br>Days') . "</th>
				<th>Payable<br>Salary</th>
				<th>Extra<br>Salary</th>
				<th>Paid<br>Salary</th>
				<th>Balance<br>Salary</th>
					<th>Status</th>";
	if (isUserIn(['superadmin'])) {
		print "<th></th>";
	}

	if (isUserIn(['superadmin'])) {
		print "<th></th>";
		print "<th></th>";

	}

	print "</tr>
			</thead>
			<tbody>";

	$i = 1;
	$workers = select("*, (SELECT IFNULL(SUM(amount),0) from hotel_statement_worker_payment WHERE worker = hotel_statement_worker.id) paid", "hotel_statement_worker", "statement=$hotel_statement->id", "ORDER BY trim(name)");
	$total_salary = 0;
	$hotel_salary = 0;
	while ($w = mysqli_fetch_object($workers)) {
		$displayBasic = $w->basic;
		$meal = (float) $w->meal;
		$visa = (float) $w->visa;
		$totalSalary = $displayBasic + $meal + $visa;
		$monthlyWorking = (int) $w->monthly_working;
		$perDay = $monthlyWorking > 0 ? round($totalSalary / $monthlyWorking, 2) : 0;
		$absent = max(0, $monthlyWorking - $w->working_days);
		$payable = $displayBasic - round($perDay * $absent);
		if ($hotel_statement->hourly == 0) {
			$hotel_salary = $w->billed_amount / 30;
			if ($meal > 0 || $visa > 0 || $monthlyWorking > 0) {
				$salary = $payable;
			} elseif ($hotel_statement->type == 'Fulltime') {
				$salary = round(($w->basic / 30 * ($w->working_days + $w->public_holiday + ($w->working_hours / 100))) + ($w->working_days > 25 ? 0000 : 0));
			} else {
				$salary = $w->basic * ($w->working_days + ($w->working_hours / 100));
			}
		} else {
			$hotel_salary = $w->billed_amount / 30;
			$salary = $w->basic * ($w->working_days + ($w->working_hours / 100));
			if ($w->working_days < 325 && $w->working_days > 100) {
				$salary -= 100;
			}
		}
		$payable = $salary;

		$income = getSum("hotel_statement_worker_income", "amount", "worker=$w->id");
		$total_salary += $salary + $income;
		if (!$w->category) {
			$w->category = space(1);
		}

		$working_days = $w->working_days;
		if ($w->working_hours) {
			$working_days = "{$w->working_days}.{$w->working_hours}";
		}
		if ($w->public_holiday) {
			$working_days = "{$w->working_days}++{$w->public_holiday}";
			// print " + {$w->public_holiday}PH";
		}
		if ($w->mc) {
			$working_days = "{$w->working_days}+{$w->mc}";
		}
		$bal = $salary + $income - $w->paid + 0;
		if ($bal > -.49 && $bal < .50) {
			$bal = 0;
		}
		$bal_data = round($bal, 2);
		print "<tr>
					<td>";
		if (isUserIn(['superadmin']) || uid() == 47) {
			print "<a href='?page=3&h=$get->h&lock=$w->lock&w=$w->id'>";
		}
		print "<i class='fa fa-" . ($w->lock ? 'lock' : 'unlock') . "'></i>";
		if (isUserIn(['superadmin']) || uid() == 47) {
			print "</a>";
		}
		print "</td>
					<td class='text-center'>" . ($i++) . "</td>
					<td>" . ($w->lock ? "" : "<input type='checkbox' class='worker-payment' data-name='$w->name' data-id='$w->id' data-basic='$displayBasic' data-total='" . ($salary + $income) . "' data-balance='$bal_data' data-meal='$w->meal' data-visa='$w->visa' data-monthly-working='$w->monthly_working'>") . "</td>
					<td>
						<a href='#' style='color:blue' data-bs-toggle='modal' data-bs-target='#modal-worker-details'  onClick='showDetails($w->statement, $w->id,\"$w->name\", \"" . nf0($salary) . "\", \"" . nf0($income) . "\", \"" . nf0($salary + $income) . "\",\"$w->phone\",\"$w->account\",$w->lock)'>$w->name</a>
						<button class='btn btn-$w->category frht' style='' " . (isUserIn(['superadmin']) || uid() == 47 ? "onClick='setCategory($w->id)'" : '') . ">$w->category</button>
						<div>$w->phone</div>
					</td>
				<td class='text-center'>" . nf0($w->basic, $dp) . "</td>
				<td class='text-center' ondblclick='editMeal($w->id, this)' id='meal{$w->id}'>" . nf0($w->meal, $dp) . "</td>
					<td class='text-center' ondblclick='editVisa($w->id, this)' id='visa{$w->id}'>" . nf0($w->visa, $dp) . "</td>
					<td class='text-center'>" . nf0($totalSalary, $dp) . "</td>
					<td class='text-center' ondblclick='editMonthlyWorking($w->id, this)' id='mw{$w->id}'>" . ($monthlyWorking > 0 ? $monthlyWorking : '') . "</td>
					<td class='text-center'>" . nf0($perDay, $dp) . "</td>
					<td class='text-center' style='color:blue;' ondblclick='editWorkingDays($w->id)' data-data='$working_days' id='w{$w->id}'>";
		if ($w->working_hours && $hotel_statement->hourly == 0) {
			print "$w->working_days  + {$w->working_hours}HR";
		} else {
			print ($w->working_days + ($w->working_hours / 100));
		}
		if ($w->public_holiday) {
			print " + {$w->public_holiday}PH";
		}
		if ($w->mc) {
			print " + {$w->mc}MC";
		}
		print "</td>";
		if ($w->verified) {
			print "<td class='w30 cntr' id='w-$w->id'><i class='fa fa-check-circle' style='color:limegreen; cursor: pointer' ondblclick='unverify($w->id)'></i></td>";
		} else {
			print "<td class='w30 cntr' id='w-$w->id'><i class='fa fa-check-circle' style='color:grey' onclick='verify($w->id)'></i></td>";
		}
		print "<td class='text-center'>" . nf0($salary) . "</td>
					<td class='text-center'>" . nf0($income) . "</td>
					<td class='text-center'>" . nf0($w->paid) . "</td>
					<td class='text-center' style='color:" . ($bal < 0 ? 'orangered' : 'green') . "'>" . nf0($bal) . "</td>
					<td class='text-center'>";
		if ($w->approved) {
			print '<a class="btn btn-success">Approved</a>';
		} else {
			if (uid() == 1) {
				print "<form method='post' onsubmit=\"return confirm('Are you sure?');\"><input type='hidden' name='worker' value='" . $w->id . "'><button class='btn btn-danger' name='approve'>Pending</button></form>";
			} else {
				print "<button class='btn btn-danger' onClick='showApprovePermissionAlert()' name='approve'>Pending</button>";
			}
		}
		print "</td>";
		if (isUserIn(['superadmin', 'orange', 'lemon'])) {
			print "<td class='text-center'><a data-bs-toggle='modal' data-bs-target='#modal-worker-edit' onClick='setWorkerId($w->id)' class='btn btn-sm btn-warning'><i class='fa fa-edit'></i></a></td>";
		} else {
			print "<td class='text-center'><a onClick='showEditPermissionAlert()' class='btn btn-sm btn-warning'><i class='fa fa-edit'></i></a></td>";
		}
		if (isUserIn(['superadmin', 'orange', 'lemon'])) {
			print "<td class='text-center' nowrap>
							<a data-bs-toggle='modal' data-bs-target='#modal-deduct-salary' onClick='setWorkerId($w->id)' class='btn btn-sm btn-danger'><i class='fa fa-minus'></i></a>
							<a data-bs-toggle='modal' data-bs-target='#modal-add-salary' onClick='setWorkerId($w->id)' class='btn btn-sm btn-warning'><i class='fa fa-plus'></i></a>
						</td>";
		}
		if (isUserIn(['superadmin', 'orange', 'lemon'])) {
			print "<td class='text-center'><a href='javascript:deleteWorker($w->id)' class='btn btn-sm btn-warning'><i class='fa fa-trash'></i></a></td>";
		} else {
			print "<td class='text-center'><a onClick='showDeletePermissionAlert()' class='btn btn-sm btn-warning'><i class='fa fa-trash'></i></a></td>";
		}
		print "</tr>";
		sum('basic', $displayBasic);
		sum('meal', $w->meal);
		sum('visa', $w->visa);
		sum('total_salary', $totalSalary);
		sum('monthly_working', $monthlyWorking);
		sum('per_day', $perDay);
		if ($hourly) {
			sum('working_days', $w->working_days + ($w->working_hours / 100));
		} else {
			sum('working_days', $w->working_days);
		}
		sum('payable', $payable);
		sum('income', $income);
		sum('paid', $w->paid);
		sum('bal', $payable + $income - $w->paid);
	}
	print "
			</tbody>
			<tfoot>
				<tr>
				<td></td>
				<td colspan='3'><a data-bs-toggle='modal' data-bs-target='#modal-worker'><i class='fa fa-plus'></i></a> <b class='frht style='color:teal;'></b></td>
				<th class='text-center'>" . nf0(sum('basic'), $dp) . "</th>
				<th class='text-center'>" . nf0(sum('meal'), $dp) . "</th>
				<th class='text-center'>" . nf0(sum('visa'), $dp) . "</th>
				<th class='text-center'>" . nf0(sum('total_salary')) . "</th>
				<th class='text-center'>" . nf0(sum('monthly_working')) . "</th>
				<th class='text-center'>" . nf0(sum('per_day')) . "</th>
				<th class='text-center'>" . nf0(sum('working_days'), $dp) . "</th>
				<th></th>
				<th class='text-center'>" . nf0(sum('payable')) . "</th>
				<th class='text-center'>" . nf0(sum('income')) . "</th>
				<th class='text-center'>" . nf0(sum('paid')) . "</th>
				<th class='text-center'>" . nf0(sum('bal')) . "</th>
				<th></th>
					<th></th>
					<th></th>
				</tr>
			</tfoot>
		</table>";

	// print "<div style='color: orangered; font-weight:700;margin-bottom:10px' class='cntr'>PLEASE NOTE IF OUTSOURCE AND HOTEL INVOICE DOES NOT MATCH</div>";
	// print "<div style='padding: 5px 15px; font-weight:700; font-size:1.5rem; margin-bottom:10px; border-radius; background-color: ".(nn($hotel_statement->background_color) ? $hotel_statement->background_color : "#efefef")."; color: $hotel_statement->text_color'>$hotel_statement->remarks <a data-bs-toggle='modal' data-bs-target='#save_remarks_modal'><i class='fa fa-plus-circle'></i></a></div>";

	if (isset($get->statement)) {
		$stt = R::load("hotel_statement", $get->h);
		// $d = $hotel_statement->month."-01"; //isset($get->d)?$get->d:subDay(30);
		// $t = $hotel_statement->month."-31"; //isset($get->t)?$get->t:today();
		$method = isset($get->method) ? $get->method : '';
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
		while ($row = mysqli_fetch_object($rows)) {
			$type = "";
			if ($row->source == 'hotel_capital')
				$type = 'Capital';
			if ($row->source == 'hotel_income')
				$type = 'Income';
			if ($row->source == 'hotel_expense')
				$type = 'Expense';
			if ($row->source == 'hotel_withdraw')
				$type = 'Withdraw';
			if ($method != '') {
				if ($method != $type) {
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>" . df($row->date) . "</td>";
			print "<td>$row->particulars</td>";
			if ($type == 'Income') {
				print "<td class='text-right'>" . nf0($row->amount) . "</td><td></td>";
				$balance += $row->amount;
				sum('itc', $row->amount);
			} else {
				print "<td></td><td class='text-right'>" . nf0($row->amount) . "</td>";
				$balance -= $row->amount;
				sum('itd', $row->amount);
			}
			print "<td class='text-right'>" . nf0($balance) . "</td>";
			if ($row->status == 'Pending') {
				if (uid() == 1) {
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";
				} else {
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";
				}
			} else {
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";
			}
			print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fa fa-trash'></i></a></td>";

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>" . nf0(sum('itc')) . "</th><th>" . nf0(sum('itd')) . "</th><th class='text-right'>" . nf0($balance) . "</th><th></th></tr></thead>";


		print "<tr><th colspan='12'><h3>Advance</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, expense_date date, amount, particulars, entry_time, '' status, 'expense_account_entry' source FROM `expense_account_entry` WHERE particulars LIKE '%advance%' AND entry_id IN (SELECT id FROM hotel_statement_worker_payment WHERE worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$get->h))
		) t ORDER BY entry_time");


		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while ($row = mysqli_fetch_object($rows)) {
			$type = "";
			if ($row->source == 'hotel_capital')
				$type = 'Capital';
			if ($row->source == 'hotel_income')
				$type = 'Income';
			if ($row->source == 'hotel_expense')
				$type = 'Expense';
			if ($row->source == 'hotel_withdraw')
				$type = 'Withdraw';
			if ($method != '') {
				if ($method != $type) {
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>" . df($row->date) . "</td>";
			print "<td>$row->particulars</td>";
			if ($type == 'Income') {
				print "<td class='text-right'>" . nf0($row->amount) . "</td><td></td>";
				$balance += $row->amount;
				sum('ptc', $row->amount);
			} else {
				print "<td></td><td class='text-right'>" . nf0($row->amount) . "</td>";
				$balance -= $row->amount;
				sum('ptd', $row->amount);
			}
			print "<td class='text-right'>" . nf0($balance) . "</td>";
			if ($row->status == 'Pending') {
				if (uid() == 1) {
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";
				} else {
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";
				}
			} else {
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";
			}
			print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fa fa-trash'></i></a></td>";

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>" . nf0(sum('ptc')) . "</th><th>" . nf0(sum('ptd')) . "</th><th class='text-right'>" . nf0($balance) . "</th><th></th></tr></thead>";

		print "<tr><th colspan='12'><h3>Salary</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, expense_date date, amount, particulars, entry_time, '' status, 'expense_account_entry' source FROM `expense_account_entry` WHERE particulars LIKE '%salary%' AND entry_id IN (SELECT id FROM hotel_statement_worker_payment WHERE worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$get->h))

		) t ORDER BY entry_time");

		//SELECT e.id, e.expense_date date, e.amount, e.particulars, e.entry_time, '' status, 'expense_account_entry' source FROM expense_account_entry e, expense_account a WHERE e.accountid=a.id AND a.hotel=$stt->hotel AND e.expense_date LIKE '{$stt->month}-%' AND e.particulars LIKE '%salary%'



		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while ($row = mysqli_fetch_object($rows)) {
			$type = "";
			if ($row->source == 'hotel_capital')
				$type = 'Capital';
			if ($row->source == 'hotel_income')
				$type = 'Income';
			if ($row->source == 'hotel_expense')
				$type = 'Expense';
			if ($row->source == 'hotel_withdraw')
				$type = 'Withdraw';
			if ($method != '') {
				if ($method != $type) {
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>" . df($row->date) . "</td>";
			print "<td>$row->particulars</td>";
			if ($type == 'Income') {
				print "<td class='text-right'>" . nf0($row->amount) . "</td><td></td>";
				$balance += $row->amount;
				sum('stc', $row->amount);
			} else {
				print "<td></td><td class='text-right'>" . nf0($row->amount) . "</td>";
				$balance -= $row->amount;
				sum('std', $row->amount);
			}
			print "<td class='text-right'>" . nf0($balance) . "</td>";
			if ($row->status == 'Pending') {
				if (uid() == 1) {
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";
				} else {
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";
				}
			} else {
				print "<td class='text-center'><a class='btn btn-success'>$row->status</a></td>";
			}
			print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fa fa-trash'></i></a></td>";

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>" . nf0(sum('stc')) . "</th><th>" . nf0(sum('std')) . "</th><th class='text-right'>" . nf0($balance) . "</th><th></th></tr></thead>";
		// print "<tr><td class='text-center'>$i</td><td></td><td>Total salary paid</td><td></td><td class='text-right'>".nf0(sum('salary'))."</td><td class='text-right'>".nf0($balance - sum('salary'))."</td></tr>";

		print "<tr><th colspan='12'><h3>Expense</h3></tr>";
		$rows = select("SELECT * FROM (
			SELECT id, date, amount, particulars, entry_time, status, 'hotel_expense' source FROM hotel_expense WHERE statement=$get->h
			UNION
			SELECT e.id, e.expense_date date, e.amount, e.particulars, e.entry_time, `status`, 'expense_account_entry' source FROM expense_account_entry e, expense_account a WHERE e.accountid=a.id AND a.hotel=$stt->hotel AND e.expense_date LIKE '{$stt->month}-%' AND e.particulars NOT LIKE '%advance%' AND e.particulars NOT LIKE '%salary%'
		) t ORDER BY entry_time");


		print "<thead><tr><th>#</th><th>Date</th><th>Particulars</th><th>Credit</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>";
		$i = 1;
		while ($row = mysqli_fetch_object($rows)) {
			$type = "";
			if ($row->source == 'hotel_capital')
				$type = 'Capital';
			if ($row->source == 'hotel_income')
				$type = 'Income';
			if ($row->source == 'hotel_expense')
				$type = 'Expense';
			if ($row->source == 'hotel_withdraw')
				$type = 'Withdraw';
			if ($row->source == 'expense_account_entry')
				$type = 'ExpenseEntry';

			if ($method != '') {
				if ($method != $type) {
					continue;
				}
			}
			print "<tr>";
			print "<td class='text-center'>$i</td>";
			print "<td class='text-center' nowrap>" . df($row->date) . "</td>";
			print "<td>$row->particulars</td>";
			if ($type == 'Income') {
				print "<td class='text-right'>" . nf0($row->amount) . "</td><td></td>";
				$balance += $row->amount;
				sum('etc', $row->amount);
			} else {
				print "<td></td><td class='text-right'>" . nf0($row->amount) . "</td>";
				$balance -= $row->amount;
				sum('etd', $row->amount);
			}
			print "<td class='text-right'>" . nf0($balance) . "</td>";
			if ($row->status == 'Pending') {
				if (uid() == 1) {
					print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&approve=$type&id=$row->id' class='btn btn-danger'>$row->status</a></td>";
				} else {
					print "<td class='text-center'><a class='btn btn-danger'>$row->status</a></td>";
				}
			} else {
				print "<td class='text-center'><a class='btn btn-success'>Approved</a></td>";
			}
			print "<td class='text-center'><a href='?page=$get->page&h=$get->h&statement&del=$type&id=$row->id'><i class='fa fa-trash'></i></a></td>";

			print "</tr>";
			$i++;
		}
		print "<tr><th></th><th></th><th>TOTAL</th><th>" . nf0(sum('etc')) . "</th><th>" . nf0(sum('etd')) . "</th><th class='text-right'>" . nf0($balance) . "</th><th></th></tr></thead>";
		print "</table>";


		print "<div class='right'><a class='btn btn-success' href='?page=$get->page&h=$get->h'>Back</a></div>";
	} else {
		/*
		$stt = R::load("hotel_statement", $get->h);
		// vd($stt);
		$income = getSum("hotel_income", "amount", "statement=$get->h");
		$capital = getSum("hotel_capital", "amount", "hotel=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$get->h");
		// $expense = getSum("hotel_expense", "amount", "statement=$get->h");
		// $salary = getSum("hotel_statement_worker", "basic", "statement=$hotel_statement->id");
		$paid = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$stt->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");
		//SELECT * FROM `expense_account_entry` WHERE accountpath LIKE '%/41/%' AND MONTH='2024-01'
		$expense = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$stt->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");

		$withdraw = getSum("hotel_withdraw", "amount", "hotel=$hotel->id");
		$loan_given = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Give'");
		$loan_taken = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Collect'");

				// <tr><th>Salary</th><th>".nf0($total_salary)."</th></tr>
		print "<div class='col-md-6'>
			<table class='table table-bordered'>
				<tr><th>Income</th><th>".nf0($income)."</th></tr>
				<tr><th>Salary</th><th>".nf0(sum('paid'))."</th></tr>
				<tr><th><a href='/store/expense_account/hotel?hotel=$hotel->id&month=$mon'>Expense</a></th><th>".nf0($expense)."</th></tr>
				<tr><th><a href='?page=$get->page&h=$get->h&statement'>Profit</a></th><th>".nf0($income + $loan_taken - $expense - $total_salary - $loan_given)."</th></tr>
			</table>
		</div>";
		print "<div class='col-md-6 right'>
			<!--a class='pointer w100 btn btn-warning'  data-bs-toggle='modal' data-bs-target='#modal-capital'>PSI</a-->
			";
		if(($hotel_statement->invoice)){
			// print "<a class='pointer w160 btn btn-success' target='_blank' href='/store/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice'>Print Hotel Invoice</a>";
			print "<div class='dropdown' style='display:inline-block'>
				  <button class='btn btn-success dropdown-toggle' type='button' data-bs-toggle='dropdown'>Print Hotel Invoice
				  <span class='caret'></span></button>
				  <ul class='dropdown-menu'>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=STEWARDING'>STEWARDING</a></li>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=HOUSEKEEPING'>HOUSEKEEPING</a></li>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice.php?id=$hotel_statement->invoice&type=CLEANING'>CLEANING</a></li>
				  </ul>
				</div>";
		} else{
			if($hotel_statement->type == 'Fulltime' || $hotel_statement->hourly){
				// print  "<a class='pointer w160 btn btn-info' target='_blank' href='/store/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id'>Preview Invoice</a>";
				print "<div class='dropdown' style='display:inline-block'>
				  <button class='btn btn-info dropdown-toggle' type='button' data-bs-toggle='dropdown'>Preview Invoice
				  <span class='caret'></span></button>
				  <ul class='dropdown-menu'>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=STEWARDING'>STEWARDING</a></li>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=HOUSEKEEPING'>HOUSEKEEPING</a></li>
					<li><a target='_blank' href='/store/view/exportables/hotel_invoice_preview.php?id=$hotel_statement->id&type=CLEANING'>CLEANING</a></li>
				  </ul>
				</div>";
			} else{
				print  "<a class='pointer w160 btn btn-info' target='_blank' href='/store/view/exportables/hotel_parttime_invoice_preview.php?id=$hotel_statement->id'>Preview Invoice</a>";
			}
			if(isUserIn(['superadmin', 'orange'])){
				print "<a class='pointer w160 btn btn-warning'  data-bs-toggle='modal' data-bs-target='#modal-hotel-invoice'>Create Factory Invoice</a>";
			}
		} 
		print "<a class='pointer w100 btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-income'>Income</a>
			<a class='pointer w100 btn btn-danger' id='btn-expense' href='/store/expense_account/hotel?hotel=$hotel->id&month=$mon' data-bs-toggle='modal--' data-bs-target='#modal-expense--'>Expense</a>
			<!--a class='pointer w100 btn btn-info'  data-bs-toggle='modal' data-bs-target='#modal-withdraw'>Withdraw</a-->";
		print "</div>";
/*
		print "<div class='col-md-3'>";
			if($hotel_statement->file1){
				print "
				<div class='attendance-sheet'>
					<div>
						<i class='fa fa-upload' onClick='clk(1)'></i>
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
						<i class='fa fa-upload' onClick='clk(3)'></i>
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
						<i class='fa fa-upload' onClick='clk(2)'></i>
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
						<i class='fa fa-upload' onClick='clk(4)'></i>
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
} else {
	$filter = "h.id=s.hotel AND h.type IN ('salary','both') AND s.enum='salary'";
	if (!isset($get->showall)) {
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
		<th>" . (isset($get->showall) ? "<a href='?page=$page'>Show less</a>" : "<a href='?page=$page&showall'>Show all</a>") . "</th>
	</tr>";
	print "</tbody>";

	$i = 1;
	while ($hotel = mysqli_fetch_object($hotels)) {
		//////////
		$hotel_statement = R::load("hotel_statement", $hotel->id);
		$workers = select("basic, working_days, working_hours, meal, visa, monthly_working", "hotel_statement_worker", "statement=$hotel_statement->id", "ORDER BY name");
		$total_salary = 0;
		while ($w = mysqli_fetch_object($workers)) {
			if ((float) $w->meal > 0 || (float) $w->visa > 0 || (int) $w->monthly_working > 0) {
				$m = (float) $w->meal;
				$v = (float) $w->visa;
				$totalS = $w->basic + $m + $v;
				$mw = (int) $w->monthly_working;
				$pd = $mw > 0 ? round($totalS / $mw, 2) : 0;
				$abs = max(0, $mw - $w->working_days);
				$salary = $w->basic - round($pd * $abs);
			} elseif ($hotel_statement->type == 'Fulltime') {
				$salary = round(($w->basic / 30 * ($w->working_days + ($w->working_hours / 100))) + ($w->working_days > 30 ? 0000 : 0));
			} else {
				$salary = $w->basic * ($w->working_days + ($w->working_hours / 100));
			}
			$total_salary += $salary;
		}
		$total_salary += getSum("hotel_statement_worker_income", "amount", "worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$hotel_statement->id)");

		$income = getSum("hotel_income", "amount", "statement=$hotel->id");
		$capital = getSum("hotel_capital", "amount", "hotel=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$hotel->id");
		// $expense = getSum("hotel_expense", "amount", "statement=$hotel->id");
		// $salary = getSum("hotel_statement_worker", "basic", "statement=$hotel_statement->id");
		$paid = getSum("expense_account_entry", "amount", "accountpath LIKE '%/$hotel->haid/%' AND month='{$hotel_statement->month}' AND tran_type='Debit' AND particulars NOT LIKE '%salary%' AND particulars NOT LIKE '%advance%'");
		$salary_paid = getSum("hotel_statement_worker_payment", "amount", "worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$hotel_statement->id)");

		$withdraw = getSum("hotel_withdraw", "amount", "hotel=$hotel->id");
		$extra_salary = getSum("hotel_statement_worker_income", "amount", "worker IN (SELECT id FROM hotel_statement_worker WHERE statement=$hotel->id)");
		$loan_given = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Give'");
		$loan_taken = 0; //getSum("hotel_loan", "amount", "hotel=$hotel->id AND `direction`='Collect'");
		$profit = $income + $loan_taken - $paid - $total_salary - $loan_given - $extra_salary;

		// dd($hotel);

		$total_paid = $salary_paid;
		print "<tr>";
		print "<td class='text-center'>$i</td>";
		print "<td>" . date("M Y", strtotime("{$hotel->month}-01")) . "</td>";
		print "<td><a href='?page=3&h=$hotel->id{$bt_url}'>$hotel->name ($hotel_statement->type)</a></td>";
		print "<td class='text-center'>$hotel->workers</td>";
		sum("workers", $hotel->workers);
		// print "<td class='text-center'>".nf0($income)."</td>"; sum("income", $income);
		print "<td class='text-center'>" . nf0($total_salary) . "</td>";
		sum("salary", $total_salary);
		print "<td class='text-center'>" . nf0($total_paid) . "</td>";
		sum("expense", $total_paid);
		print "<td class='text-center'>" . nf0($total_salary - $total_paid) . "</td>";
		sum("balance", $total_salary - $total_paid);
		// print "<td class='text-center'>".nf0($profit)."</td>"; sum("profit", $profit);
		// print "<td class='text-center'><a href='?page=3&h=$hotel->id'>$hotel->pending</a></td>"; sum("pending", $hotel->pending);
		// print "<td class='text-center'><a href='?page=3&h=$hotel->id&statement#expense'>$hotel->pending_expense</a></td>"; sum("pending_expense", $hotel->pending_expense);
		print "<td class='text-center'>
				<a class='btn btn-warning' href='?page=$get->page&duplicate=$hotel->id&mon=$mon'><i class='fa fa-copy'></i> Duplicate</a>
				<a class='btn btn-danger' href='#' onclick='confirmDeleteHotel($hotel->id, \"$mon\")'><i class='fa fa-trash'></i> Del</a>
			</td>";
		print "</tr>";
		$i++;
	}
	print "<tr>
		<tfoot>
			<th></th>
			<th></th>
			<th></th>
			<th>" . nf0(sum("workers")) . "</th>
			<th>" . nf0(sum("salary")) . "</th>
			<th>" . nf0(sum("paid")) . "</th>
			<th>" . nf0(sum("balance")) . "</th>
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
						<tr>
							<td>Store</td>
							<td><?php print sop2('hotel', '', ['filter' => "type IN ('salary','both')"]); ?> <a
									data-bs-toggle='modal' data-bs-target='#modal-hotel'><i class='fa fa-plus'></i></a>
							</td>
						</tr>
						<tr>
							<td>Month</td>
							<td nowrap><?php print monthSelector("month", today()); ?></td>
						</tr>
						<tr>
							<td>Account (optional)</td>
							<td><?php print sop2('expense_account', '', ['optional' => true, 'optional_value' => 'Select Account (optional)']); ?>
							</td>
						</tr>
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
						<tr>
							<td>Store</td>
							<td><?php print sop2('hotel', '', ['filter' => "type IN ('salary','both')"]); ?> <a
									data-bs-toggle='modal' data-bs-target='#modal-hotel'><i class='fa fa-plus'></i></a>
							</td>
						</tr>
						<tr>
							<td>Month</td>
							<td nowrap><?php print monthSelector("month2", today()); ?></td>
						</tr>
						<tr>
							<td>Account (optional)</td>
							<td><?php print sop2('expense_account', '', ['optional' => true, 'optional_value' => 'Select Account (optional)']); ?>
							</td>
						</tr>
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
					<h4 class="modal-title">Create Salary Factory</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<table>
						<tr>
							<td>Factory</td>
							<td><input name="name" required class="form-control" placeholder="Factory name"></td>
						</tr>
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
						<tr>
							<td>Invoice Date</td>
							<td nowrap><?php print dateSelector("invoice_date", today()); ?></td>
						</tr>
						<tr>
							<td>Start Date</td>
							<td nowrap>
								<?php
								if (nn($hotel->startdate)) {
									$startdate = date("Y-m-", strtotime(subMonth(1, $hotel->startdate))) . date("d", strtotime($hotel->startdate));
								} else {
									$startdate = firstDay("$hotel_statement->month-01");
								}
								print dateSelector("start_date", $startdate);
								?>
							</td>
						</tr>
						<tr>
							<td>End Date</td>
							<td nowrap>
								<?php
								if (nn($hotel->enddate)) {
									$enddate = date("Y-m-", strtotime(subMonth(1))) . date("d", strtotime($hotel->enddate));
								} else {
									$enddate = lastDate("$hotel_statement->month-01");
								}
								print dateSelector("end_date", $enddate);
								?>
							</td>
						</tr>
						<tr class='hidden'>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_income", today()); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" id="income-amount" class="form-control"
									placeholder="Amount" step=".01"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_capital"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" class="form-control" placeholder="Amount"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
						<tr>
							<td></td>
							<td>
								<input type="radio" name="type" checked value='Invest'> Invest <br>
								<input type="radio" name="type" value='Collect'> Collect
							</td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_expense"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" id="" required class="form-control amount" step='.01'
									min='1' <?php
									if (isset($get->h) && isset($get->add_bt)) {
										$bt = R::load("bank_transaction_item", $get->add_bt);
										print "value='$bt->debit'";
									}
									?>
									placeholder="Amount"></td>
						</tr>
						<tr>
							<td>Payment Method</td>
							<td>
								<span><input required="required" type='radio' name='payment_method' class='required pm'
										value='cash'> Cash</span>
								<span><input type='radio' name='payment_method' class='required on' value='online'>
									Online</span>
							</td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td>
								<div>
									<div><input class='part' type='radio' name='part'>Hostel er jonno pay kora hoyese
									</div>
									<div><input class='part' type='radio' name='part'>TNB er jonno pay kora hoyese</div>
									<div><input class='part' type='radio' name='part'>Water bill er jonno pay kora
										hoyese</div>
									<div><input class='part' type='radio' name='part'>UNiFi bill er jonno pay kora
										hoyese</div>
									<div><input class='part' type='radio' name='part'>Indah water er jonno pay kora
										hoyese</div>
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
						<tr>
							<td></td>
							<td>
								<textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control particulars"></textarea>
							</td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_withdraw"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" class="form-control" placeholder="Amount"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_loan"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" class="form-control" placeholder="Amount"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
						<tr>
							<td>Account</td>
							<td><?php print sop2("account"); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><input type='radio' name='direction' value='Give'>Give Loan <br> <input type='radio'
									name='direction' value='Collect'>Collect/Return Loan </td>
						</tr>
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
						<tr>
							<td>Name</td>
							<td><input name="name" id="worker-name-input" class="form-control" placeholder="Name"
									required></td>
						</tr>
						<!-- <tr><td>Basic Salary</td><td><input type="number" name="basic" class="form-control" placeholder="Basic Salary"></td></tr> -->
						<!-- <tr><td>Working Days</td><td><input type="number" name="working_days" class="form-control" placeholder="Working Days" value="0"></td></tr> -->
					</table>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success" name="save_worker"
						onclick="return validateWorkerName()">Save</button>
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
						<tr>
							<td>Name</td>
							<td><input name="name" class="form-control" placeholder="Name"></td>
							<!--<td><input type='radio' name='pay' value='Monthly' id='ms' checked> <a href='javascript:selectMs();'>Monthly Salary</a></td-->
						</tr>
						<?php if (isUserIn(['superadmin'])) { ?>
							<tr>
								<td>Basic Salary</td>
								<td><input type="number" name="basic" class="form-control" step=".01"
										placeholder="Basic Salary"></td>
								<!--td><input type='radio' value='Daily' id='ds' name='pay'>  <a href='javascript:selectDs();'>Daily Salary</a></td-->
							</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_ext"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" class="form-control"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
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
						<tr>
							<td>Date</td>
							<td nowrap><?php print dateSelector("date_ext2"); ?></td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" class="form-control"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td><textarea name="particulars" rows="5" placeholder="Particulars"
									class="form-control"></textarea></td>
						</tr>
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

<div class="modal fade" id="modal-meal" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" autocomplete="off">
				<input type="hidden" name="id" class="meal-worker-id">
				<div class="modal-header">
					<h4 class="modal-title">Meal Allowance</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<table>
						<tr>
							<td>Meal</td>
							<td><input type="number" name="meal" class="form-control meal-input" step=".01"
									placeholder="Meal Allowance"></td>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success" name="save_meal">Save</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-visa" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" autocomplete="off">
				<input type="hidden" name="id" class="visa-worker-id">
				<div class="modal-header">
					<h4 class="modal-title">Visa Cost</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<table>
						<tr>
							<td>Visa</td>
							<td><input type="number" name="visa" class="form-control visa-input" step=".01"
									placeholder="Visa Cost"></td>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success" name="save_visa">Save</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-monthly-working" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" autocomplete="off">
				<input type="hidden" name="id" class="mw-worker-id">
				<div class="modal-header">
					<h4 class="modal-title">Monthly Working Days</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<table>
						<tr>
							<td>Monthly Working</td>
							<td><input type="number" name="monthly_working" class="form-control mw-input"
									placeholder="Total days in month"></td>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success" name="save_monthly_working">Save</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<style>
	.swal-over-modal {
		z-index: 9999 !important;
	}

	.modal-lg table tr td:nth-child(2) {
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
								<?php if (uid() == 1)
									print "<th></th><th></th>"; ?>
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
					<table class="table table-bordered worker-payment-layout">
						<tr>
							<td colspan="2" class='worker-name cntr bold'></td>
						</tr>
						<tr>
							<td>Date</td>
							<td nowrap>
								<?php
								print today();
								print "<input type='hidden' name='payment_date' value='" . today() . "'>" . space(5);
								// print dateSelector("payment_date"); 
								?>
							</td>
						</tr>
						<tr>
							<td>Amount</td>
							<td><input type="number" name="amount" required id="payment_amount" class="form-control"
									placeholder="Amount"></td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td class='worker-payment-form-cell'>
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
								print dateSelectorOptional("banking_date", today(), '', '', 'alert');
								?>
								<textarea name="particulars" class="form-control particulars" id="particulars" required
									placeholder="Particulars"></textarea>
							</td>
						</tr>
						<!-- <tr class='me2-customer'>
					  <td>Me2 Customer</td><td><?php //print sop2("transfer_customer", "", ["optional"=>true]); ?></td>
				  </tr> -->
						<tr class='bdcon-worker'>
							<td>Outsource Worker</td>
							<td><?php print sop2("worker2", "", ["optional" => true, 'dataField' => "CONCAT(name,' - ', passport)", 'extraFields' => 'photo_file', 'width' => 'w150'], "worker") . space(5); ?>
								<img class='worker-photo w50'>
							</td>
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
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<form method="post" autocomplete="off" enctype='multipart/form-data'>
				<input type='hidden' name='save_worker_payment_2' value='1'>
				<input type='hidden' id='worker' name='worker'>
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<td colspan="2" class='text-wrap worker-name cntr bold'></td>
						</tr>
						<tr>
							<td>Expense Account / Type</td>
							<td>
								<div class="row">
									<div class="col-md-6">
										<?php
										$expenseAccounts = R::getAll("SELECT id, name FROM expense_account ORDER BY name");
										print "<select class='form-control' name='expense_account_id' id='expense_account_id' style='width: 100%;' required>";
										print "<option value=''>Select Expense Account</option>";
										foreach ($expenseAccounts as $ea) {
											$selected = (isset($hotel->accountid) && $ea['id'] == $hotel->accountid) ? "selected" : "";
											print "<option value='{$ea['id']}' {$selected}>{$ea['name']}</option>";
										}
										print "</select>";
										?>
									</div>
									<div class="col-md-6">
										<select class='form-control' id='salary_type' name='salary_type'
											style='width: 100%;' required>
											<option value=''>Select Salary Type</option>
											<option value='Salary'>Salary</option>
											<option value='Advance'>Advance</option>
											<option value='ME2'>ME2</option>
											<option value='Permit'>Permit</option>
										</select>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td>Date / Banking Date</td>
							<td>
								<div class="row">
									<div class="col-md-6">
										<label>Date:</label>
										<span id="payment-date-display"><?php print today(); ?></span>
										<input type='hidden' name='payment_date' value='<?php print today(); ?>'>
									</div>
									<div class="col-md-6">
										<label>Banking:</label>
										<?php print dateSelectorOptional("banking_date-2", today(), '', '', 'alert'); ?>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td>Name</td>
							<td><input type='text' class='form-control w300' placeholder="Enter account name"
									id='account_name'>
						<tr>
							<td>Amount / Payment Method</td>
							<td>
								<div class="row">
									<div class="col-md-4">
										<input type="number" name="amount" required id="payment_amount" min="0"
											step="any" class="form-control payment_amount" placeholder="Amount">
									</div>
									<div class="col-md-2"></div>
									<div class="col-md-6 float-end">
										<div class="form-check form-check-inline">
											<input class="form-check-input payment-method-radio" type="radio"
												name="payment_method" id="payment_method_cash" value="cash" required>
											<label class="form-check-label" for="payment_method_cash">Cash</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input payment-method-radio" type="radio"
												name="payment_method" id="payment_method_bank" value="online" required>
											<label class="form-check-label" for="payment_method_bank">Bank</label>
										</div>
										<div id="bank-selector" style="display: none; margin-top: 5px;">
											<select class="form-control" name="bank_account" id="bank_account">
												<option value="">Select Bank Account</option>
												<option value="Islami Bank Kt Tex account">Islami Bank Kt Tex account
												</option>
												<option value="City Bank 280 account">City Bank 280 account</option>
												<option value="City Bank ORI Ltd account">City Bank ORI Ltd account
												</option>
											</select>
										</div>
										<div style="margin-top: 5px;">
											<input type="checkbox" name="is_investment" id="is_investment" value="1">
											<label for="is_investment">Investment</label>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr hidden>
							<td>Entry Type</td>
							<td>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" name="opex_or_capex"
										id="opex_or_capex_capex" value="Capex" checked>
									<label class="form-check-label" for="opex_or_capex_capex">Capex</label>
								</div>
							</td>
						</tr>
						<tr>
							<td>Particulars</td>
							<td>
								<table class='table table-bordered'>
									<tr>
										<td style="vertical-align: top !important;;">
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
											<!-- <br> -->

											<div id="particulars-container"></div>
										</td>
										<td id='worker-list'>
											<table class='table table-bordered'>
												<tr id='worker-list-empty-row'>
													<td></td>
													<td>
														<div id="amount-total" class="text-muted"
															style="margin-top:5px;font-weight:700;text-align:left;font-size:1.2em;">
															Total: 0.00</div>
													</td>
												</tr>
											</table>
										</td>
									</tr>
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
						<tr>
							<td>Remarks</td>
							<td><textarea name="remarks" rows="5" placeholder="Notes" class="form-control"></textarea>
							</td>
						</tr>
						<tr>
							<td>Text Color</td>
							<td><input type="color" name='text_color'></td>
						</tr>
						<tr>
							<td>Background Color</td>
							<td><input type="color" name='background_color' value='#efefef'></td>
						</tr>
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
<style>
	.modal {
		z-index: 1077 !important;
	}
</style>

<form method="post" id="save_remarks_form" mehtod="post">
	<input type="hidden" name='remarks' id='remarks'>
	<input type="hidden" name='save_remarks' id='save_remarks'>
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<!-- Include Select2 (once in layout) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script type="text/javascript">
	var isAdmin = <?php echo (uid() == 1 || uid() == 65) ? 'true' : 'false'; ?>;

	$(document).ready(function () {
		// Initialize select2 for expense_account_id when modal is shown
		$('#modal-worker-payment-2').on('shown.bs.modal', function () {
			if ($.fn.select2) {
				$("#expense_account_id").select2({
					width: '100%',
					placeholder: 'Select Expense Account',
					allowClear: true,
					dropdownParent: $('#modal-worker-payment-2')
				});
			}
		});

		// Destroy select2 when modal is hidden to prevent issues
		$('#modal-worker-payment-2').on('hidden.bs.modal', function () {
			if ($.fn.select2) {
				$("#expense_account_id").select2('destroy');
			}
		});
	});

	function selectMs() {
		$("#ds").removeAttr("checked");
		$("#ms").attr("checked", "checked");
	}
	function selectDs() {
		$("#ms").removeAttr("checked");
		$("#ds").attr("checked", "checked");
	}
	<?php if (isset($get->h) && isset($get->add_bt) && !isset($post->save_expense)) { ?>
		setTimeout(function () {
			$("#modal-expense").modal();
		}, 500);
		// $("#btn-expense").trigger('click');
	<?php } ?>
	function showDetails(s, id, name, salary, income, total, phone, account, lock) {
		$("#phone-input").val(phone);
		if (lock == 1) {
			$("#payment-button").hide();
		} else {
			$("#payment-button").show();
		}
		$("#modal-worker-payment").find('#worker').val(id);
		$("#modal-worker-details").find('#worker').val(id);
		$("#modal-worker-details").find('#account').val(account);
		if (account != '') {
			<?php if (!isuserin(['superadmin'])): ?>
				$("#modal-worker-details").find('#account').prop('disabled', true);
			<?php endif; ?>
		}
		$("#modal-worker-details").find('.modal-title').html(`${name} Salary Statement`);
		$(".worker-name").html(name);
		$("#modal-worker-details").find('.salary').html('<div>Salary: &nbsp;&nbsp;' + salary + '</div><div>Extra: &nbsp;&nbsp;' + income + '</div><div>Total Salary: &nbsp;&nbsp;' + total + '</div>');
		// $("#modal-worker-details").modal();
		$.post("ajax/hotel_payments.php", { id: id, salary: salary, income: income, total: total }, function (data) {
			$("#modal-worker-details").find("tbody").html(data);
		});
	}

	$("select.worker2").change(function (e) {
		var id = $("select.worker2 option:selected").val();
		var photo = $("select.worker2 option:selected").data('photo_file');
		$(".worker-photo").attr('src');
		if (photo != "") {
			$(".worker-photo").attr('src', '/store/uploads/worker/' + id + '/' + photo);
		}
	});

	$("#mon").change(function () {
		var mon = $("#mon").val() + "-01";
		location.href = "?page=3&mon=" + mon + "<?php print $bt_url; ?>";
	});

	function setCategory(id) {
		$(".worker_id").val(id);
		$("#modal-category").modal();
	}

	function setWorkerId(id) {
		$(".modal-worker .worker_id").val(id);
	}

	function showEditPermissionAlert() {
		Swal.fire({
			icon: 'error',
			title: 'Permission Denied',
			text: 'Can not Edit Basic Salary'
		});
	}
	function showDeletePermissionAlert() {
		Swal.fire({
			icon: 'error',
			title: 'Permission Denied',
			text: 'Can not Delete Employee'
		});
	}

	function showApprovePermissionAlert() {
		Swal.fire({
			icon: 'error',
			title: 'Permission Denied',
			text: 'Can not Approve'
		});
	}

	function setWorkerIds() {
		names = "";
		// <tr id='worker-list-empty-row'><td></td><td></td></tr>
		name_count = $(".worker-payment:checked").length;
		$(".worker-list-item").remove();
		$("#particulars-container").empty(); // Clear previous textareas

		$($(".worker-payment:checked")).each(function (i, e) {
			var name = $(e).data('name').trim();
			var id = $(e).data('id');
			var basic = $(e).data('basic');
			var total = $(e).data('total');
			var balance = parseFloat($(e).data('balance'));

			var basicFmt = Number(basic).toLocaleString();
			var balanceFmt = Number(balance).toLocaleString();
			var totalFmt = Number(total).toLocaleString();
			var meal = $(e).data('meal');
			var visa = $(e).data('visa');
			var monthlyWorking = $(e).data('monthly-working');
			var mealFmt = Number(meal).toLocaleString();
			var visaFmt = Number(visa).toLocaleString();
			names += (names != '' ? ', ' : '') + name;
			if (name_count > 0) {
				var disabledAttr = isAdmin ? '' : 'disabled';
				// $("#worker-list-empty-row").before("<tr class='worker-list-item'><td nowrap class='w-name'>" + name + "<br><small class='text-muted'>Basic: " + basicFmt + " | Max: " + totalFmt + "</small> <input type='hidden' name='workers[]' value='" + id + "'></td><td><input type='number' step='any' name='salary[]' required class='form-control w80 worker-id' value='" + (isNaN(balance) ? '' : balance) + "' data-max='" + total + "' data-worker-name='" + name + "'><input type='hidden' name='salary_hidden[]' value='" + (isNaN(balance) ? '' : balance) + "'></td></tr>");
				$("#worker-list-empty-row").before("<tr class='worker-list-item'><td nowrap class='w-name'>Balance: " + balanceFmt + " | Meal: " + mealFmt + " | Visa: " + visaFmt + "<input type='hidden' name='workers[]' value='" + id + "'></td><td><input type='number' step='any' name='salary[]' required class='form-control w80 worker-id' value='" + (isNaN(balance) ? '' : balance) + "' data-max='" + total + "' data-worker-name='" + name + "'><input type='hidden' name='salary_hidden[]' value='" + (isNaN(balance) ? '' : balance) + "'></td></tr>");

				// Add individual textarea for this worker in particulars-container
				$("#particulars-container").append("<div class='worker-particulars-wrapper' data-worker-id='" + id + "' data-worker-name='" + name + "'><textarea name='particulars[]' class='form-control particulars w300 mb-1' rows='2' placeholder='e.g., " + name + " ke <?php print date("M", strtotime("{$hotel_statement->month}-01")); ?> Masher Salary Theke Advance " + (isNaN(balance) ? '' : balance) + " Taka Petty Cash Theke Deoya Hoyese'></textarea></div>");
			}
			$("#modal-worker-payment-2").find(".worker-name").text(names);
		});
		$(".worker-id").off("keyup", calcWorkerTotal).on("keyup", calcWorkerTotal);
		calcWorkerTotal();
		$(".payment_amount").attr('readonly', true);
		// if(name_count == 1){
		$("#account_name").val(names);
		$(".payment_amount").removeAttr('readonly');
		// }
	}
	function syncWorkerPaymentSelectAll() {
		var $selectAll = $("#worker-payment-select-all");
		var $workers = $(".worker-payment");
		if (!$selectAll.length) {
			return;
		}
		if (!$workers.length) {
			$selectAll.prop('checked', false).prop('indeterminate', false);
			return;
		}
		var checkedCount = $workers.filter(":checked").length;
		$selectAll
			.prop('checked', checkedCount === $workers.length)
			.prop('indeterminate', checkedCount > 0 && checkedCount < $workers.length);
	}
	$(document).on("change", "#worker-payment-select-all", function () {
		$(".worker-payment").prop("checked", $(this).is(":checked"));
		syncWorkerPaymentSelectAll();
		setWorkerIds(); // Call setWorkerIds to update particulars when select all is used
	});
	$(document).on("change", ".worker-payment", function () {
		syncWorkerPaymentSelectAll();
		setWorkerIds(); // Call setWorkerIds to update particulars when individual checkbox is changed
	});
	syncWorkerPaymentSelectAll();
	var _isUpdatingPaymentAmount = false;
	function calcWorkerTotal() {
		workerTotal = 0;
		workers = ''
		$($(".worker-id")).each(function (i, e) {
			var amt = parseFloat($(e).val());
			if (!isNaN(amt)) {
				workerTotal += amt;
				workers += ", " + $(e).parent().parent().find('.w-name').text().trim() + ' Rm ' + amt;
			}
		});
		$("#amount-total").text("Total: " + workerTotal.toFixed(2));
		_isUpdatingPaymentAmount = true;
		$("#modal-worker-payment-2 #payment_amount").val(workerTotal);
		_isUpdatingPaymentAmount = false;
		setParticulars2();
	}
	function distributePaymentAmount() {
		var newTotal = parseFloat($("#modal-worker-payment-2 #payment_amount").val());
		if (isNaN(newTotal) || newTotal < 0) return;
		var workers = $(".worker-id");
		var values = [];
		var oldTotal = 0;
		workers.each(function () {
			var v = parseFloat($(this).val()) || 0;
			values.push(v);
			oldTotal += v;
		});
		if (oldTotal === 0 && newTotal > 0) {
			var eachAmt = newTotal / workers.length;
			workers.each(function (i) {
				$(this).val(eachAmt.toFixed(2));
			});
		} else if (oldTotal !== 0) {
			workers.each(function (i) {
				var ratio = values[i] / oldTotal;
				$(this).val((ratio * newTotal).toFixed(2));
			});
		}
		calcWorkerTotal();
	}
	$(document).on("input", "#modal-worker-payment-2 #payment_amount", function () {
		if (!_isUpdatingPaymentAmount) {
			distributePaymentAmount();
		}
	});

	function approvePayment(id) {
		if (confirm("Are you sure?")) {
			$.post("ajax/hotel_payment_approve.php", { id: id, payment: 0 }, function (data) {
				if (data == "OK") {
					$("#hotel_statement_worker_payment_status_" + id).html("<button type='button' class='btn btn-sm btn-success'>Approved</button>");
				}
			});
		}
	}
	function addNotes() {
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

	function upload(e) {
		if (confirm("Are you sure?")) {
			$(e).parent().submit();
		}
	}

	function deletePayment(id) {
		if (prompt("Are you sure? Enter PIN") == "<?php print upin(); ?>") {
			// if(input("Are you sure?")){
			$("#form-hidden").html("<form method='post'><input type='hidden' name='remove_id' value='" + id + "'></form>");
			setTimeout(function () {
				$("#form-hidden").find("form").submit();
			}, 100);
		}
	}


	function approveIncome(id) {
		if (confirm("Are you sure?")) {
			$.post("ajax/hotel_income_approve.php", { id: id, income: 0 }, function (data) {
				if (data == "OK") {
					$("#hotel_statement_worker_income_status_" + id).html("<button type='button' class='btn btn-sm btn-success'>Approved</button>");
				}
			});
		}
	}

	function deleteIncome(id) {
		if (prompt("Are you sure? Enter PIN") == "<?php print upin(); ?>") {
			// if(input("Are you sure?")){
			$("#form-hidden").html("<form method='post'><input type='hidden' name='remove_income' value='" + id + "'></form>");
			setTimeout(function () {
				$("#form-hidden").find("form").submit();
			}, 100);
		}
	}

	function editWorkingDays(id) {
		var days = $("#w" + id).text();
		days = $("#w" + id).data('data');
		<?php //if(isuserin(['superadmin','durian', 'orange', 'apple'])): ?>
		<?php if (isuserin(['superadmin', 'orange', 'lemon']) || uid() == 47): ?>
			$("#w" + id).html("<form method='post'><input type='hidden' name='id' value='" + id + "'><input type='tel' step='.01' style='width:50px' max='31' name='working_days' value='" + days + "'><button class='btn btn-success btn-sm' name='update_working_days'>Save</button></form>");
		<?php endif; ?>
		<?php if (uid() != 1): ?>
			if (days == "0") {
				$("#w" + id).html("<form method='post'><input type='hidden' name='id' value='" + id + "'><input type='tel' step='.01' style='width:50px;' max='31' name='working_days' value='" + days + "'><button class='btn btn-success btn-sm' name='update_working_days'>Save</button></form>");
			}
		<?php endif; ?>
	}

	function deleteWorker(id) {
		if (confirm("Are you sure?")) {
			$("#form-hidden").html("<form method='post'><input type='hidden' name='remove_worker' value='" + id + "'></form>");
			setTimeout(function () {
				$("#form-hidden").find("form").submit();
			}, 100);
		}
	}

	$("#income-amount").keyup(setParticulars);

	function setParticulars() {
		var text = $("#title-month").text() + " " + $("#title-hotel").text() + " Invoice Rm " + $("#income-amount").val();
		$("#modal-income").find('textarea').val(text);
	}

	$("#modal-worker-payment input[type='radio']").click(function () {
		var text = $(this).parent().text();
		console.log(text)
		if (text.includes("Me2 te")) {
			$(".me2-customer").find('select').attr('required', true).show();
			// $("#phone-input").attr("required", true);
		} else {
			$(".me2-customer").find('select').attr('required', false).hide();
			// $("#phone-input").attr("required", false);
		}
		var val = $("#payment_amount").val();
		var particulars = $("#_banking_date").val() + ' ' + text + ' ' + val;
		console.log(particulars)
		$("#modal-worker-payment .particulars").val(particulars);
	});

	$("#modal-worker-payment-2 input[type='radio']").click(function () {
		if ($(this).hasClass('st')) {
			$('.st-selected').removeClass('st-selected');
			$(this).addClass('st-selected');
		} else {
			$('.bank-account-selected').removeClass('bank-account-selected');
			$(this).addClass('bank-account-selected');
		}
		setParticulars2();
	});
	$("#account_name,#payment_amount").keyup(setParticulars2);
	$("select.bank").change(setParticulars2);
	$("#salary_type").change(setParticulars2);
	$(document).on("change", "#banking_date-2_day, #banking_date-2_mon, #banking_date-2_year", function () {
		setTimeout(function () {
			var dt = $("#banking_date-2").val();
			if (dt) {
				$("#modal-worker-payment-2 input[name='payment_date']").val(dt);
				$("#payment-date-display").text(dt);
			}
			setParticulars2();
		}, 10);
	});

	// Handle payment method radio button change
	$(".payment-method-radio").change(function () {
		if ($(this).val() === 'online') {
			$("#bank-selector").show();
		} else {
			$("#bank-selector").hide();
		}
		setParticulars2();
	});

	// Handle bank account selection
	$("#bank_account").change(setParticulars2);

	// Investment Swal confirmation on modal-worker-payment-2 form submit
	$("#modal-worker-payment-2 form").on("submit", function (e) {
		var form = this;

		// Validate each worker's salary against max payable
		var salaryType = $("#salary_type").val();
		var overpaid = [];
		$(".worker-id").each(function () {
			var val = parseFloat($(this).val());
			var max = parseFloat($(this).data('max'));
			var name = $(this).data('worker-name');
			if (!isNaN(val) && !isNaN(max) && val > max) {
				if (salaryType === 'Advance' && val <= 5000) {
					return;
				}
				overpaid.push(name + " maximum payable is " + Number(max).toLocaleString() + ", but entered " + Number(val).toLocaleString());
			}
		});
		if (overpaid.length > 0) {
			e.preventDefault();
			Swal.fire({
				icon: 'error',
				title: 'Overpayment Detected',
				html: overpaid.join('<br>'),
				confirmButtonText: 'OK'
			});
			return false;
		}

		if (!$("#is_investment", form).is(":checked") && !$(form).data("investment-confirmed")) {
			e.preventDefault();
			Swal.fire({
				icon: 'question',
				title: 'Is this an investment?',
				text: 'Choose Yes to create an investment entry for this payment.',
				showCancelButton: true,
				confirmButtonText: 'Yes',
				cancelButtonText: 'No',
				didOpen: function () {
					document.querySelector('.swal2-container').style.zIndex = '99999';
				}
			}).then(function (result) {
				if (result.isConfirmed) {
					$("#is_investment", form).prop("checked", true);
				}
				if (result.isConfirmed || result.dismiss) {
					$(form).data("investment-confirmed", true);
					form.submit();
				}
			});
			return false;
		}
		if ($("#is_investment", form).is(":checked") && !$(form).data("investment-confirmed")) {
			$(form).data("investment-confirmed", true);
		}
	});

	function setParticulars2() {
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

		// Get payment method and bank account
		var paymentMethod = $("input[name='payment_method']:checked").val();
		var bankAccount = $("#bank_account").val();
		var paymentSource = "Petty Cash";
		if (paymentMethod === 'online' && bankAccount) {
			paymentSource = bankAccount;
		}

		// Get salary type (Salary or Advance)
		var salaryType = $("#salary_type").val();

		// Populate each worker's individual textarea with their specific particulars
		$(".worker-particulars-wrapper").each(function (i, e) {
			var workerName = $(e).data('worker-name');
			var workerId = $(e).data('worker-id');
			// Find the corresponding salary input for this worker
			var salaryInput = $(".worker-id").eq(i);
			var amt = parseFloat(salaryInput.val());
			if (!isNaN(amt)) {
				var monthText = '<?php print date("M", strtotime("{$hotel_statement->month}-01")); ?>';
				if (salaryType === 'Advance') {
					var workerParticulars = `${workerName} ke ${monthText} Masher Salary Theke Advance ${amt} Taka ${paymentSource} Theke Deoya Hoyese`;
				}
				else if (salaryType === 'ME2') {
					var workerParticulars = `${workerName} er Jul Masher Salary Theke Rm ${amt} Taka ME2 Te Bangladeshe Pathano Hoyece`;
				}
				else if (salaryType === 'Permit') {
					var workerParticulars = `${workerName} er Jul Masher Salary Theke Rm ${amt} Taka Kete Visa Babod Outsource Joma Kora Hoyece`;
				}
				else {
					var workerParticulars = `${workerName} ke ${monthText} Masher Salary ${amt} Taka ${paymentSource} Theke Deoya Hoyese`;
				}
				$(e).find('textarea').val(workerParticulars);
			}
		});
	}

	$("#modal-expense .amount").keyup(setParticularsExpense);

	$("#modal-expense input[type='radio']").click(setParticularsExpense);

	function setParticularsExpense() {
		var exp_part = $("#modal-expense input[name='part']:checked").parent().text();
		var exp_bank = $("#modal-expense input[name='bank']:checked").parent().text();

		// var text = $(this).parent().text();
		var val = $("#modal-expense .amount").val();

		$("#modal-expense .particulars").val($("#title-hotel").text() + ' er ' + exp_part + ' Rm ' + val + ' ' + exp_bank);
	}

	<?php if (isuserin(['superadmin'])) { ?>
		function verify(id) {
			$.post("<?php print BASEURL . APP; ?>/ajax/verify_worker_working_days.php",
				{
					'app': '<?php print APP; ?>', 'id': id
				}, function (data) {
					$("#w-" + id).html("<i class='fa fa-check-circle' style='color:limegreen; cursor: pointer' ondblclick='unverify($w->id)'></i>");
				}
			);
		}
		function unverify(id) {
			$.post("<?php print BASEURL . APP; ?>/ajax/verify_worker_working_days.php",
				{
					'app': '<?php print APP; ?>', 'id': id, 'un': 'un'
				}, function (data) {
					$("#w-" + id).html("<i class='fa fa-check-circle' style='color:grey; cursor: pointer' onclick='verify($w->id)'></i>");
				}
			);
		}
	<?php } ?>

	function confirmDeleteHotel(hotelId, mon) {
		Swal.fire({
			title: 'Are you sure?',
			text: "You won't be able to revert this!",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#3085d6',
			confirmButtonText: 'Yes, delete it!'
		}).then((result) => {
			if (result.isConfirmed) {
				window.location.href = '?page=18&delHotel=' + hotelId + '&mon=' + mon + '&conf';
			}
		});
	}

	function validateWorkerName() {
		var workerName = $("#worker-name-input").val().trim();
		if (workerName === "") {
			// alert("Worker is empty!");
			Swal.fire({ icon: 'error', text: "Worker name is empty!" });
			return false;
		}
		return true;
	}

	function editMeal(id, cell) {
		var current = cell.textContent.trim();
		$('.meal-worker-id').val(id);
		$('.meal-input').val(current);
		$('#modal-meal').modal('show');
	}
	function editVisa(id, cell) {
		var current = cell.textContent.trim();
		$('.visa-worker-id').val(id);
		$('.visa-input').val(current);
		$('#modal-visa').modal('show');
	}
	function editMonthlyWorking(id, cell) {
		var current = cell.textContent.trim();
		$('.mw-worker-id').val(id);
		$('.mw-input').val(current);
		$('#modal-monthly-working').modal('show');
	}
</script>