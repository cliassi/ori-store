<style type="text/css">
	.highlight {
		font-size: 18px;
		color: #337ab7;
	}

	.highlight2,
	.highlight2 a {
		font-size: 18px;
		color: #000;
	}

	.bd_handover .particulars {
		color: #5cb85c;
		font-weight: 700;
	}

	input[type=checkbox],
	input[type=radio] {
		outline: 2px solid;
		margin-left: 5%;
	}

	.checked-pending,
	.checked-banned {
		height: 20px;
		opacity: .5;
		padding-left: 20px;
		filter: grayscale(1);
		cursor: pointer;
	}

	.checked-pending:hover,
	.checked-banned:hover {
		filter: grayscale(.2);
		transform: scale(1.1);
	}

	.checked {
		height: 20px;
		padding-left: 20px;
	}

	tr.expense_account_entry td {
		border: solid 1px lightgreen !important;
	}

	tr.cw_cash_withdraw td {
		color: #dc3545 !important;
		font-weight: 700 !important;
	}

	tr.cw_cash_withdraw .cash-out-amount {
		color: #dc3545 !important;
		font-weight: 700 !important;
	}

	tr.cw_cash_withdraw .particulars {
		color: #dc3545 !important;
		font-weight: 700 !important;
	}

	.select-cell {
		cursor: pointer;
		background: #fff;
	}

	.select-cell:hover {
		background: #e9ecef;
	}

	tr.row-selected {
		background: #d1e7ff !important;
	}
</style>
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

	.cash-in-bd {
		color: #5cb85c !important;
		font-weight: 700;
	}

	.cash-out-amount {
		color: black !important;
		font-weight: 700;
	}

	.bank-out-amount {
		color: black !important;
		font-weight: 700;
	}

	tr.cash-out-row .particulars {
		color: black;
	}

	tr.bank-out-row .particulars {
		color: black;
	}

	.decided-action-link {
		display: inline-block;
		color: #333;
		text-decoration: none;
		line-height: 1.4;
	}

	.decided-action-link:hover {
		color: #000;
	}

	.decided-action-icon {
		font-size: 1.15rem;
		font-weight: 700;
	}

	.decided-count-pending {
		color: #ff8c00;
		font-weight: 700;
	}

	.decided-count-complete {
		color: inherit;
		font-weight: 700;
	}

	.cash-inline-delete {
		color: #d9534f;
		text-decoration: none;
		font-weight: 700;
		margin-left: 6px;
	}

	.cash-inline-delete:hover {
		color: #a94442;
	}

	.total-sum {
		font-size: 1.5rem;
		font-weight: 700;
	}
</style>
<?php
$get->petty_cash_details = 1;
$get->cw = 1;
if (isset($get->company))
	$id = $get->company;

if (!function_exists('cash_normalize_source')) {
	function cash_normalize_source($source)
	{
		if (in_array($source, ['payment_bank', 'payment_cash'], true))
			return 'payment';
		if (in_array($source, ['expense_account_entry_bank', 'expense_account_entry_cash'], true))
			return 'expense_account_entry';
		return $source;
	}
}

if (!function_exists('cash_is_admin_approver')) {
	function cash_is_admin_approver()
	{
		return (int) uid() === 1 || isUserIn(['superadmin', 'amla', 'orange']);
	}
}

if (!function_exists('cash_status_label')) {
	function cash_status_label($status)
	{
		$raw = trim((string) $status);
		if ($raw === '')
			$raw = 'Pending';
		$parts = explode('-', $raw);
		return trim((string) $parts[0]);
	}
}

if (!function_exists('cash_is_pending_status')) {
	function cash_is_pending_status($status)
	{
		return strtolower(cash_status_label($status)) === 'pending';
	}
}

if (!function_exists('cash_render_status_button')) {
	function cash_render_status_button($status, $source, $id, $canApproveStatus)
	{
		if (!cash_is_pending_status($status)) {
			return "<span class='btn btn-success btn-sm w80'>Approved</span>";
		}
		$normalized = cash_normalize_source($source);
		if (!cash_is_valid_approval_source($normalized)) {
			return "<span class='btn btn-warning btn-sm w80'>Pending</span>";
		}
		$currentStatus = htmlspecialchars((string) $status, ENT_QUOTES);
		$source = htmlspecialchars((string) $source, ENT_QUOTES);
		$id = (int) $id;
		$can = $canApproveStatus ? 1 : 0;
		return "<a href='#' class='approval-trigger' data-source='{$source}' data-id='{$id}' data-current-status='{$currentStatus}' data-can-approve='{$can}' style='cursor:pointer'><span class='btn btn-danger btn-sm w80'>Pending</span></a>";
	}
}

if (!function_exists('cash_has_column')) {
	function cash_has_column($table, $column)
	{
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
		$column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
		if ($table === '' || $column === '')
			return false;
		$row = R::getRow("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
		return is_array($row) && !empty($row);
	}
}

if (!function_exists('cash_is_valid_approval_source')) {
	function cash_is_valid_approval_source($source)
	{
		$allowedSources = [
			'payment',
			'expense_account_entry',
			'bd_handover',
			'cw_cash',
			'cw_bank',
			'cw_cash_withdraw',
			'cw_outlet',
		];
		return in_array((string) $source, $allowedSources, true);
	}
}

if (!function_exists('cash_update_entry_status')) {
	function cash_update_entry_status($source, $id, $status = 'Approved')
	{
		$table = cash_normalize_source($source);
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
		$id = (int) $id;
		$status = strtolower(trim((string) $status)) === 'pending' ? 'Pending' : 'Approved';

		if ($id <= 0 || $table === '' || !cash_is_valid_approval_source($table)) {
			return false;
		}

		$existingId = (int) R::getCell("SELECT `id` FROM `{$table}` WHERE `id`=? LIMIT 1", [$id]);
		if ($existingId <= 0) {
			return false;
		}

		R::exec("UPDATE `{$table}` SET `status`=? WHERE `id`=? LIMIT 1", [$status, $id]);
		return true;
	}
}

if (!function_exists('cash_parse_approval_value')) {
	function cash_parse_approval_value($value)
	{
		$value = trim((string) $value);
		$splitPos = strrpos($value, '-');
		if ($splitPos === false) {
			return [null, 0];
		}

		$source = substr($value, 0, $splitPos);
		$id = (int) substr($value, $splitPos + 1);
		return [$source, $id];
	}
}

$canApproveStatus = cash_is_admin_approver();

if (isset($post->approve_with_color)) {
	if (!$canApproveStatus) {
		echo json_encode(['success' => false, 'message' => 'Only admin can change approval status']);
		exit;
	}
	$status = strtolower(trim((string) (isset($post->status) ? $post->status : 'approved'))) === 'pending' ? 'Pending' : 'Approved';
	$updated = cash_update_entry_status((string) (isset($post->source) ? $post->source : ''), (int) (isset($post->id) ? $post->id : 0), $status);
	if (!$updated) {
		echo json_encode(['success' => false, 'message' => 'Invalid row selected for approval']);
		exit;
	}
	echo json_encode(['success' => true]);
	exit;
}

if (isset($post->cash_edit_save)) {
	$cashRedirectD = isset($get->d) ? (string) $get->d : '';
	$cashRedirectT = isset($get->t) ? (string) $get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '') ? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT)) : '?';

	$editSource = isset($post->cash_edit_source) ? (string) $post->cash_edit_source : '';
	$editId = isset($post->cash_edit_id) ? (int) $post->cash_edit_id : 0;
	$editDate = isset($post->cash_edit_date) ? (string) $post->cash_edit_date : '';
	$editParticulars = isset($post->cash_edit_particulars) ? (string) $post->cash_edit_particulars : '';
	$editAmount = isset($post->cash_edit_amount) ? (float) $post->cash_edit_amount : 0;

	$canEditCash = uid() == 1 || isUserIn(['superadmin', 'amla', 'orange']);
	if (!$canEditCash) {
		redir($cashRedirectUrl);
		exit;
	}

	if ($editId > 0 && !empty($editSource)) {
		if (in_array($editSource, ['cw_cash', 'cw_bank', 'cw_cash_withdraw'], true)) {
			$bean = R::load($editSource, $editId);
			if ($bean && $bean->id) {
				if (!empty($editDate)) {
					$bean->date = $editDate;
				}
				if (!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif (in_array($editSource, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$bean = R::load('expense_account_entry', $editId);
			if ($bean && $bean->id) {
				if (!empty($editDate)) {
					$bean->expense_date = $editDate;
				}
				if (!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif (in_array($editSource, ['payment_cash', 'payment_bank'], true)) {
			$bean = R::load('payment', $editId);
			if ($bean && $bean->id) {
				if (!empty($editDate)) {
					$bean->date = $editDate;
				}
				if (!empty($editParticulars)) {
					$bean->description = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = -abs($editAmount);
				}
				if (isset($post->cash_edit_payment_method) && in_array($post->cash_edit_payment_method, ['Cash', 'Bank'], true)) {
					$bean->payment_method = $post->cash_edit_payment_method;
				}
				R::store($bean);
			}
		} elseif ($editSource === 'bd_handover') {
			$bean = R::load('bd_handover', $editId);
			if ($bean && $bean->id) {
				if (!empty($editDate)) {
					$bean->date = $editDate;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		} elseif ($editSource === 'investment') {
			$bean = R::load('investment', $editId);
			if ($bean && $bean->id) {
				if (!empty($editDate)) {
					$bean->date = $editDate;
				}
				if (!empty($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);
			}
		}
	}
	redir($cashRedirectUrl);
}

if (isset($post->save_carwash)) {
	$carwash = R::dispense("cw_sites");
	$carwash->name = $post->name;
	$carwash->entry_by = uid();
	$carwash->branch_id = $branch_id;
	R::store($carwash);
}

if (isset($post->save)) {
	$object = R::dispense("cw_customer");
	$function = 'add';
	$post->company = $id;
	$post->branch_id = $branch_id;
	require_once("model/cw_customer.php");
}


if (isset($post->save_cash)) {
	$deposit = R::dispense("cw_cash");
	$deposit->date = isset($post->date) ? $post->date : today();
	$deposit->particulars = $post->particulars;
	$deposit->branch_id = $branch_id;
	$deposit->amount = $post->amount;
	$deposit->company = $get->cw;
	$deposit->entry_by = uid();
	$deposit->entry_time = now();
	R::store($deposit);
	redir("?");
}

if (isset($post->save_outlet)) {
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date3) ? $post->date3 : today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = 0 - $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->branch_id = $branch_id;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_outlet = R::dispense("cw_outlet");
	$cw_outlet->date = isset($post->date3) ? $post->date3 : today();
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

if (isset($post->save_bank)) {
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date2) ? $post->date2 : today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = 0 - $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->branch_id = $branch_id;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2) ? $post->date2 : today();
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

if (isset($post->save_bank_deposit)) {
	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2) ? $post->date2 : today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = $post->amount;
	$cw_bank->company = $get->cw;
	$cw_bank->branch_id = $branch_id;
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	R::store($cw_bank);
	redir("?");
}


if (isset($post->save_investment)) {
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

if (isset($post->withdraw)) {
	// dd($post);
	$withdraw = R::dispense("cw_cash_withdraw");
	$withdraw->particulars = $post->particulars;
	$withdraw->date = isset($post->date2) ? (nn($post->date2) ? $post->date2 : today()) : today();
	$withdraw->amount = 0 - $post->amount;
	$withdraw->company = $get->cw;
	$withdraw->branch_id = $branch_id;
	$withdraw->entry_by = uid();
	$withdraw->entry_time = now();
	R::store($withdraw);
	redir("?");
}

$d = isset($get->d) ? $get->d : subDay(5);
$t = isset($get->t) ? $get->t : today();


// dd([uid(), isset($get->approve) ,isset($get->id)]);
if (uid() == 1 && isset($get->approve) && isset($get->id)) {
	if (in_array($get->approve, ['payment_bank', 'payment_cash'])) {
		$get->approve = 'payment';
	}
	if (in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash', 'account_entry_cash'])) {
		$get->approve = 'expense_account_entry';
	}
	$object = R::load($get->approve, $get->id);
	$object->status = 'Approved';
	// $object->trash = 1;
	R::store($object);
	redir("?d=$d&t=$t");
}
if (uid() == 1 && isset($post->approvem)) {

	foreach ($post->approvem as $tv) {
		$type_id = explode("-", $tv);
		$get->approve = $type_id[0];
		$get->id = $type_id[1];
		if (in_array($get->approve, ['payment_bank', 'payment_cash'])) {
			$get->approve = 'payment';
		}
		if (in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash', 'account_entry_cash'])) {
			$get->approve = 'expense_account_entry';
		}
		$object = R::load($get->approve, $get->id);
		$object->status = 'Approved';
		// $object->trash = 1;
		R::store($object);
	}
	redir("?d=$d&t=$t");
}


if (isset($get->token)) {
	$token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
	if ($token) {
		R::trash($token);
		if (uid() == 1 && isset($get->approve) && isset($get->id)) {
			if (in_array($get->approve, ['payment_bank', 'payment_cash'])) {
				$get->approve = 'payment';
			}
			if (in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash'])) {
				$get->approve = 'expense_account_entry';
			}
			$object = R::load($get->approve, $get->id);
			$object->status = 'Approved';
			// $object->trash = 1;
			R::store($object);
			redir("?d=$d&t=$t");
		}

		if (uid() == 1 && isset($get->del) && isset($get->id)) {
			if (in_array($get->del, ['payment_bank', 'payment_cash'])) {
				$get->del = 'payment';
			}
			if (in_array($get->del, ['expense_account_entry_bank', 'expense_account_entry_cash'])) {
				$get->del = 'expense_account_entry';
			}
			$object = R::load($get->del, $get->id);
			if ($get->del == 'cw_bank') {
				$cw_cash = R::load("cw_cash", $object->cash_id);
				R::trash($cw_cash);
			}
			// $object->trash = 1;
			// R::store($object);
			R::trash($object);
			redir("?d=$d&t=$t");
		}

		if (isset($get->del)) {
			if ($get->del == 'expense_account_entry') {
				$ee = R::load("expense_account_entry", $get->id);
				R::trash($ee);

			} elseif ($get->del == 'cw_cash') {
				$ee = R::load("cw_cash", $get->id);
				$be = R::findOne("cw_bank", "cash_id=?", [$get->id]);
				if ($be) {
					R::trash($be);
				}
				R::trash($ee);
			} elseif ($get->del == 'cw_cash_withdraw') {
				$ee = R::load("cw_cash_withdraw", $get->id);
				R::trash($ee);
			} elseif ($get->del == 'cw_bank') {
				$ee = R::load("cw_bank", $get->id);
				if ($ee->cash_id) {
					$be = R::findOne("cw_cash", "id=?", [$ee->cash_id]);
					if ($be) {
						R::trash($be);
					}
				}
				R::trash($ee);
			} elseif ($get->del == 'cw_cash_withdraw') {
				$ee = R::load("cw_cash_withdraw", $get->id);
				R::trash($ee);
			}
			redir("?d=$d&t=$t");
		}
	} else {
		redir("?d=$d&t=$t");
	}
}



if (isset($get->copy) && $get->copy > 0) {
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

if (isset($get->petty_cash_currency)) {
	ensurePettyCashCurrencyTables();

	$cwId = isset($get->cw) ? intval($get->cw) : 0;
	$bId = isset($branch_id) ? intval($branch_id) : 0;

	$startDate = isset($get->start_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $get->start_date) ? $get->start_date : date('Y-m-d');
	$endDate = isset($get->end_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $get->end_date) ? $get->end_date : date('Y-m-d');

	$startDay = isset($get->start_day) ? str_pad(intval($get->start_day), 2, '0', STR_PAD_LEFT) : date('d', strtotime($startDate));
	$startMonth = isset($get->start_month) ? str_pad(intval($get->start_month), 2, '0', STR_PAD_LEFT) : date('m', strtotime($startDate));
	$startYear = isset($get->start_year) ? intval($get->start_year) : intval(date('Y', strtotime($startDate)));
	$startDate = "$startYear-$startMonth-$startDay";

	$endDay = isset($get->end_day) ? str_pad(intval($get->end_day), 2, '0', STR_PAD_LEFT) : date('d', strtotime($endDate));
	$endMonth = isset($get->end_month) ? str_pad(intval($get->end_month), 2, '0', STR_PAD_LEFT) : date('m', strtotime($endDate));
	$endYear = isset($get->end_year) ? intval($get->end_year) : intval(date('Y', strtotime($endDate)));
	$endDate = "$endYear-$endMonth-$endDay";

	if ($startDate > $endDate) {
		$temp = $startDate;
		$startDate = $endDate;
		$endDate = $temp;
	}

	if (isset($get->shift)) {
		$shift = $get->shift;
		if ($shift === 'prev' || $shift === 'next') {
			$delta = $shift === 'prev' ? '-1 day' : '+1 day';
			$startDate = date('Y-m-d', strtotime($delta, strtotime($startDate)));
			$endDate = date('Y-m-d', strtotime($delta, strtotime($endDate)));
			if ($startDate > $endDate) {
				$temp = $startDate;
				$startDate = $endDate;
				$endDate = $temp;
			}
		}
	}

	list($startYear, $startMonth, $startDay) = explode('-', $startDate);
	list($endYear, $endMonth, $endDay) = explode('-', $endDate);

	$cm = intval(date('m', strtotime($endDate)));
	$cy = intval(date('Y', strtotime($endDate)));

	$d = date('Y-m-d', strtotime($endDate . ' +1 day'));

	$add_cash = getSum("cw_cash", "amount", "(branch_id = $bId OR branch_id IS NULL) AND company=$cwId AND amount>0 AND date<'$d'");
	$cash_handover = getSum("bd_handover", "amount", "(branch_id = $bId OR branch_id IS NULL) AND amount>0 AND date<'$d'");
	$cash_expense = getSum("expense_account_entry", "amount", "(branch_id = $bId OR branch_id IS NULL) AND company=$cwId AND payment_method='Cash' AND tran_type='Debit' AND expense_date<'$d'");
	$cash_payment = getSum("payment", "amount", "(branch_id = $bId OR branch_id IS NULL) AND payment_method='Cash' AND date<'$d'");
	$withdraw = getSum("cw_cash_withdraw", "amount", "(branch_id = $bId OR branch_id IS NULL) AND company=$cwId AND date<'$d'");

	$petty_cash_value = $cash_handover + $add_cash - abs($withdraw) - $cash_payment - $cash_expense;
	$petty_cash_value_str = number_format($petty_cash_value, 2, '.', ',');
	$cData = [];
	$cRows = R::find("petty_cash_currency_data", "month=? AND year=?", [$cm, $cy]);
	foreach ($cRows as $row) {
		$cData[strtolower($row->label)] = $row->count;
	}
	$notes = R::find("petty_cash_currency_notes", "month=? AND year=? AND trash=0 ORDER BY sort_order ASC, id ASC", [$cm, $cy]);
	$denomDefs = [
		['label' => '1', 'value' => 1, 'type' => 'currency'],
		['label' => '5', 'value' => 5, 'type' => 'currency'],
		['label' => '10', 'value' => 10, 'type' => 'currency'],
		['label' => '20', 'value' => 20, 'type' => 'currency'],
		['label' => '50', 'value' => 50, 'type' => 'currency'],
		['label' => '100', 'value' => 100, 'type' => 'currency'],
		['label' => 'Coin', 'value' => 0, 'type' => 'Coin'],
		['label' => 'fd_1', 'value' => 100, 'type' => 'fd', 'display' => 'Fixed Deposit x 1'],
		['label' => 'fd_5', 'value' => 500, 'type' => 'fd', 'display' => 'Fixed Deposit x 5'],
		['label' => 'fd_10', 'value' => 1000, 'type' => 'fd', 'display' => 'Fixed Deposit x 10'],
		['label' => 'fd_20', 'value' => 2000, 'type' => 'fd', 'display' => 'Fixed Deposit x 20'],
		['label' => 'fd_50', 'value' => 5000, 'type' => 'fd', 'display' => 'Fixed Deposit x 50'],
		['label' => 'fd_100', 'value' => 10000, 'type' => 'fd', 'display' => 'Fixed Deposit x 100'],
	];
	$monthsList = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];
	print "<div class='container'>";
	print "<div class='row' style='margin-bottom:5px;'>";
	print "<div class='col-md-12'>";
	print "<a href='?page=18&petty_cash_details&cw=$cwId' class='btn btn-danger btn-sm float-end'><i class='fa fa-arrow-left' style='font-size: 24px;'></i></a>";
	print "</div></div>";
	print "<form method='get' style='margin-bottom:15px;'>";
	print "<input type='hidden' name='page' value='18'>";
	print "<input type='hidden' name='petty_cash_currency' value=''>";
	print "<input type='hidden' name='cw' value='$cwId'>";
	print "<div style='display:flex; justify-content:center; align-items:center; gap:8px; flex-wrap:wrap;'>";
	print "<button type='submit' name='shift' value='prev' class='btn btn-sm btn-outline-secondary'>&laquo; Prev</button>";
	print "<div style='text-align:center'>";
	print "<div style='display:flex; gap:4px;'>";
	print "<select name='start_day' class='form-control input-sm' style='width:70px; display:inline-block;'>";
	for ($d = 1; $d <= 31; $d++) {
		$sel = ($startDay == str_pad($d, 2, '0', STR_PAD_LEFT)) ? 'selected' : '';
		print "<option value='" . str_pad($d, 2, '0', STR_PAD_LEFT) . "' $sel>" . str_pad($d, 2, '0', STR_PAD_LEFT) . "</option>";
	}
	print "</select>";
	print "<select name='start_month' class='form-control input-sm' style='width:90px; display:inline-block;'>";
	foreach ($monthsList as $mv => $mn) {
		$sel = ($startMonth == $mv) ? 'selected' : '';
		print "<option value='$mv' $sel>$mn</option>";
	}
	print "</select>";
	print "<select name='start_year' class='form-control input-sm' style='width:90px; display:inline-block;'>";
	$curYear = date('Y');
	for ($y = $curYear - 5; $y <= $curYear + 5; $y++) {
		$sel = ($startYear == $y) ? 'selected' : '';
		print "<option value='$y' $sel>$y</option>";
	}
	print "</select>";
	print "</div></div>";
	print "<div style='text-align:center'>";
	print "<div style='display:flex; gap:4px;'>";
	print "<select name='end_day' class='form-control input-sm' style='width:70px; display:inline-block;'>";
	for ($d = 1; $d <= 31; $d++) {
		$sel = ($endDay == str_pad($d, 2, '0', STR_PAD_LEFT)) ? 'selected' : '';
		print "<option value='" . str_pad($d, 2, '0', STR_PAD_LEFT) . "' $sel>" . str_pad($d, 2, '0', STR_PAD_LEFT) . "</option>";
	}
	print "</select>";
	print "<select name='end_month' class='form-control input-sm' style='width:90px; display:inline-block;'>";
	foreach ($monthsList as $mv => $mn) {
		$sel = ($endMonth == $mv) ? 'selected' : '';
		print "<option value='$mv' $sel>$mn</option>";
	}
	print "</select>";
	print "<select name='end_year' class='form-control input-sm' style='width:90px; display:inline-block;'>";
	for ($y = $curYear - 5; $y <= $curYear + 5; $y++) {
		$sel = ($endYear == $y) ? 'selected' : '';
		print "<option value='$y' $sel>$y</option>";
	}
	print "</select>";
	print "</div></div>";
	print "<button type='submit' name='shift' value='next' class='btn btn-sm btn-outline-secondary'>Next &raquo;</button>";
	print "<button type='submit' class='btn btn-sm btn-primary'>Filter</button>";
	print "</div>";
	print "</form>";
	print "<div class='row'><div style='max-width:550px; margin:0 auto;'>";
	print "<a href='#' onclick='openAddNote(); return false;' style='cursor:pointer; font-size:20px; color:#5cb85c; float:right;'><i class='fa fa-plus-circle'></i></a>";
	print "<table class='table table-bordered' id='currencyTable' style='text-align:center;'>";
	print "<colgroup><col style='width:20%'><col style='width:10%'><col style='width:10%'></colgroup>";
	print "<tr style='font-weight:bold;'><td style='color:#0066cc !important;'>Petty Cash Currency Balance</td><td colspan='2' id='pettyCashBalance' style='color:#0066cc !important;'>" . $petty_cash_value_str . "</td></tr>";
	print "<tr style='font-weight:bold;'><td style='color:#009900 !important;'>Cash Available Balance</td><td colspan='2' id='cashAvailableBalance' style='color:#009900 !important;'>0.00</td></tr>";
	print "<tr style='font-weight:bold;'><td style='color:#cc0000 !important;'>Difference Balance</td><td colspan='2' id='diffBalance' style='color:#cc0000 !important;'>0.00</td></tr>";
	foreach ($denomDefs as $d) {
		$cnt = isset($cData[strtolower($d['label'])]) ? $d['type'] === 'Coin' ? number_format($cData[strtolower($d['label'])], 2, '.', '') : intval($cData[strtolower($d['label'])]) : ($d['type'] === 'Coin' ? '0.00' : '0');
		$displayLabel = isset($d['display']) ? $d['display'] : $d['label'];
		$rowClass = $d['type'] === 'fd' ? 'fd-row' : ($d['type'] === 'Coin' ? 'coin-row' : 'denom-row');
		if ($d['type'] === 'Coin') {
			print "<tr class='$rowClass' style='font-weight:bold;'><td style='color:#009900 !important;'>$displayLabel</td><td class='editable-coin' data-label='Coin' data-coin='$cnt' onclick='editcoin(this)' style='color:#009900 !important;'>$cnt</td><td class='coin-total' style='color:#009900 !important;'>$cnt</td></tr>";
		} else {
			$total = $d['value'] * intval($cnt);
			$totalStr = number_format($total, 0, '.', ',');
			print "<tr class='$rowClass' style='font-weight:bold;'><td style='color:#009900 !important;'>$displayLabel</td><td class='editable-count' data-label='{$d['label']}' data-value='{$d['value']}' data-count='$cnt' onclick='editCount(this)' style='color:#009900 !important;'>$cnt</td><td class='total-cell' style='color:#009900 !important;'>$totalStr</td></tr>";
		}
	}
	print "<tbody id='notesBody'>";
	foreach ($notes as $note) {
		$nid = intval($note->id);
		$ntext = htmlspecialchars($note->note_text);
		$namt = floatval($note->note_amount);
		$namtStr = $namt != 0 ? number_format($namt, 2, '.', ',') : '';
		print "<tr class='note-row' data-note-id='$nid'>";
		print "<td colspan='2' style='text-align:center; font-weight:bold;'>$ntext</td>";
		print "<td style='text-align:center; font-weight:bold;'>$namtStr <a href='#' onclick='deleteNote($nid, this); return false;' style='cursor:pointer; color:#d9534f; margin-left:6px;'><i class='fa fa-minus-circle'></i></a><a href='#' onclick='editNote($nid, this); return false;' style='cursor:pointer; color:#337ab7; margin-left:6px;'><i class='fa fa-file'></i></a></td>";
		print "</tr>";
	}
	print "</tbody>";
	print "<tr style='font-weight:bold; font-size:18px;'><td style='color:#0066cc !important;' colspan='2'>Total Amount Rm :</td><td style='color:#0066cc !important;' id='totalAmount'>0.00</td></tr>";
	print "</table>";
	print "</div></div>";
	print "</div>";
	print "
	<div class='modal fade' id='countModal' tabindex='-1' role='dialog'>
		<div class='modal-dialog modal-sm' role='document'>
			<div class='modal-content'>
				<div class='modal-header'>
					<button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>&times;</span></button>
					<h4 class='modal-title' id='countModalTitle'>Edit Count</h4>
				</div>
				<div class='modal-body'>
					<input type='number' class='form-control' id='countModalInput' min='0' step='1' value='0'>
				</div>
				<div class='modal-footer'>
				<button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancel</button>
				<button type='button' class='btn btn-primary' onclick='saveCount()'>Save</button>
				</div>
			</div>
		</div>
	</div>
	<div class='modal fade' id='coinModal' tabindex='-1' role='dialog'>
		<div class='modal-dialog modal-sm' role='document'>
			<div class='modal-content'>
				<div class='modal-header'>
					<button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>&times;</span></button>
					<h4 class='modal-title'>Edit coin Amount</h4>
				</div>
				<div class='modal-body'>
					<input type='number' class='form-control' id='coinModalInput' min='0' step='0.01' value='0'>
				</div>
				<div class='modal-footer'>
				<button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancel</button>
				<button type='button' class='btn btn-primary' onclick='savecoin()'>Save</button>
				</div>
			</div>
		</div>
	</div>
	<div class='modal fade' id='noteModal' tabindex='-1' role='dialog'>
		<div class='modal-dialog' role='document'>
			<div class='modal-content'>
				<div class='modal-header'>
					<button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>&times;</span></button>
					<h4 class='modal-title' id='noteModalTitle'>Add Note</h4>
				</div>
				<div class='modal-body'>
					<input type='hidden' id='editingNoteId' value=''>
					<textarea class='form-control' id='noteModalInput' rows='3' placeholder='Enter note'></textarea>
					<input type='number' class='form-control' id='noteModalAmount' min='0' step='0.01' value='0' placeholder='Amount (optional)' style='margin-top:10px;'>
				</div>
				<div class='modal-footer'>
				<button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancel</button>
				<button type='button' class='btn btn-primary' onclick='saveNote()' id='noteModalSaveBtn'>Save</button>
				</div>
			</div>
		</div>
	</div>
	<script>
	var currentEditCell = null;
	var ajaxMonth = $cm;
	var ajaxYear = $cy;
	var ajaxUrl = '/store/ajax/';
	function editCount(cell) {
		currentEditCell = cell;
		var count = parseInt(cell.getAttribute('data-count')) || 0;
		var value = cell.getAttribute('data-value');
		$('#countModalTitle').text('Edit count for RM ' + value);
		$('#countModalInput').val(count);
		$('#countModal').modal('show');
	}
	function saveCount() {
		var count = parseInt($('#countModalInput').val()) || 0;
		var value = parseInt(currentEditCell.getAttribute('data-value'));
		var label = currentEditCell.getAttribute('data-label');
		currentEditCell.setAttribute('data-count', count);
		currentEditCell.textContent = count;
		currentEditCell.nextElementSibling.textContent = (value * count).toLocaleString('en-US');
		$('#countModal').modal('hide');
		recalcAll();
		$.post(ajaxUrl + 'petty_cash_currency_save.php', {month: ajaxMonth, year: ajaxYear, label: label, denomination: value, count: count});
	}
	function editcoin(cell) {
		currentEditCell = cell;
		var coin = parseFloat(cell.getAttribute('data-coin')) || 0;
		$('#coinModalInput').val(coin);
		$('#coinModal').modal('show');
	}
	function savecoin() {
		var coin = parseFloat($('#coinModalInput').val()) || 0;
		currentEditCell.setAttribute('data-coin', coin);
		currentEditCell.textContent = coin.toFixed(2);
		currentEditCell.nextElementSibling.textContent = coin.toFixed(2);
		$('#coinModal').modal('hide');
		recalcAll();
		$.post(ajaxUrl + 'petty_cash_currency_save.php', {month: ajaxMonth, year: ajaxYear, label: 'Coin', denomination: 0, count: coin});
	}
	function openAddNote() {
		$('#noteModalInput').val('');
		$('#noteModalAmount').val('0');
		$('#editingNoteId').val('');
		$('#noteModalTitle').text('Add Note');
		$('#noteModalSaveBtn').text('Save');
		$('#noteModal').modal('show');
	}
	function saveNote() {
		var text = $('#noteModalInput').val().trim();
		var amount = parseFloat($('#noteModalAmount').val()) || 0;
		var editId = $('#editingNoteId').val();
		$('#noteModal').modal('hide');
		if (editId) {
			$.post(ajaxUrl + 'petty_cash_currency_note_edit.php', {id: editId, note_text: text, note_amount: amount}, function(resp) {
				try {
					var data = JSON.parse(resp);
				} catch(e) {
					console.error('Edit note: JSON parse error', resp);
					return;
				}
				if (data.ok) {
					var row = $('tr.note-row[data-note-id=\"' + editId + '\"]');
					var amtStr = amount != 0 ? amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
					row.find('td:eq(0)').html('<strong>' + $('<span>').text(text).html() + '</strong>');
					row.find('td:eq(1)').html(amtStr + ' <a href=\"#\" onclick=\"deleteNote(\'' + editId + '\', this); return false;\" style=\"cursor:pointer; color:#d9534f; margin-left:6px;\"><i class=\"fa fa-minus-circle\"></i></a><a href=\"#\" onclick=\"editNote(\'' + editId + '\', this); return false;\" style=\"cursor:pointer; color:#337ab7; margin-left:6px;\"><i class=\"fa fa-file\"></i></a>');
					recalcAll();
				}
			}).fail(function(jqXHR, textStatus) {
				console.error('Edit note AJAX error:', textStatus, jqXHR.responseText);
			});
		} else {
			$.post(ajaxUrl + 'petty_cash_currency_note_add.php', {month: ajaxMonth, year: ajaxYear, note_text: text, note_amount: amount}, function(resp) {
				try {
					var data = JSON.parse(resp);
				} catch(e) {
					console.error('Add note: JSON parse error', resp);
					return;
				}
				if (data.ok) {
					var id = data.id;
					var amtStr = amount != 0 ? amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
					var tr = document.createElement('tr');
					tr.className = 'note-row';
					tr.setAttribute('data-note-id', id);
					tr.innerHTML = '<td colspan=\"2\" style=\"text-align:center; font-weight:bold;\">' + $('<span>').text(text).html() + '</td><td style=\"text-align:center; font-weight:bold;\">' + amtStr + ' <a href=\"#\" onclick=\"deleteNote(' + id + ', this); return false;\" style=\"cursor:pointer; color:#d9534f; margin-left:6px;\"><i class=\"fa fa-minus-circle\"></i></a><a href=\"#\" onclick=\"editNote(' + id + ', this); return false;\" style=\"cursor:pointer; color:#337ab7; margin-left:6px;\"><i class=\"fa fa-file\"></i></a></td>';
					$('#notesBody').append(tr);
					recalcAll();
				}
			}).fail(function(jqXHR, textStatus) {
				console.error('Add note AJAX error:', textStatus, jqXHR.responseText);
			});
		}
	}
	function editNote(id, el) {
		var row = $(el).closest('tr.note-row');
		var text = row.find('td:eq(0)').text().trim();
		var amtText = row.find('td:eq(1)').clone().children().remove().end().text().replace(/,/g, '').trim();
		var amount = parseFloat(amtText) || 0;
		$('#editingNoteId').val(id);
		$('#noteModalInput').val(text);
		$('#noteModalAmount').val(amount);
		$('#noteModalTitle').text('Edit Note');
		$('#noteModalSaveBtn').text('Update');
		$('#noteModal').modal('show');
	}
	function deleteNote(id, el) {
		if (!confirm('Delete this note?')) return;
		$.post(ajaxUrl + 'petty_cash_currency_note_delete.php', {id: id}, function(resp) {
			try {
				var data = JSON.parse(resp);
			} catch(e) {
				console.error('Delete note: JSON parse error', resp);
				return;
			}
			if (data.ok) {
				$(el).closest('tr').remove();
				recalcAll();
			}
		}).fail(function(jqXHR, textStatus) {
			console.error('Delete note AJAX error:', textStatus, jqXHR.responseText);
		});
	}
	function recalcAll() {
		var cashTotal = 0;
		$('#currencyTable .denom-row, #currencyTable .fd-row').each(function() {
			var count = parseInt($(this).find('.editable-count').attr('data-count')) || 0;
			var value = parseInt($(this).find('.editable-count').attr('data-value'));
			var total = value * count;
			$(this).find('.total-cell').text(total.toLocaleString('en-US'));
			cashTotal += total;
		});
		var coin = parseFloat($('#currencyTable .coin-row .editable-coin').attr('data-coin')) || 0;
		cashTotal += coin;
		$('#currencyTable .note-row').each(function() {
			var amtText = $(this).find('td:eq(1)').text().replace(/,/g, '').trim();
			if (amtText !== '') {
				cashTotal += parseFloat(amtText) || 0;
			}
		});
		$('#cashAvailableBalance').text(cashTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
		var pettyCash = " . $petty_cash_value . ";
		var diff = cashTotal - pettyCash;
		$('#diffBalance').text(diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
		$('#totalAmount').text(cashTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
	}
	$(document).ready(function() { recalcAll(); });
	</script>";
	print "<hr>";
} elseif (isset($get->cw)) {
	$company = R::load("cw_company", $get->cw);
	print "<div style='padding: 0 20px;'>";
	print "<h3><strong><a href='?page=18&petty_cash_currency=&cw=$get->cw'>$company->name Petty Cash Report</a></strong></h3>";
	$d = isset($get->d) ? $get->d : addDay(-5);
	$t = isset($get->t) ? $get->t : today();
	print "<div class='row'>";
	print "<div class='col-md-5'>";
	print "<form>";
	print "<input type='hidden' name='page' value='18'>";
	print "<input type='hidden' name='cw' value='$get->cw'>";
	print "<input type='hidden' name='petty_cash_details' value=''>";
	print "Date " . dp2("d", $d) . " - " . dp2("t", $t) . " <button class='btn btn-primary'>Filter</button>";
	print "</form>";
	print "</div>";
	print "<div class='col-md-5'>";
	if (isUserIn(['superadmin', 'amla', 'orange', 'parvez'])) {
		print "<a data-bs-toggle='modal' data-bs-target='.invest' class='btn btn-warning'>Asset Invest</a>" . space(5);
		print "<a data-bs-toggle='modal' data-bs-target='.withdraw' class='btn btn-primary'>Cash Withdraw</a>" . space(5);
	}
	if (isUserIn(['superadmin', 'amla', 'orange', 'parvez'])) {
		print "<a data-bs-toggle='modal' data-bs-target='.cash' class='btn btn-primary'>Add Cash</a>" . space(5);
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

    (SELECT IFNULL(SUM(h.amount),0)
        FROM (SELECT MAX(id) id FROM bd_handover
              WHERE (branch_id = $branch_id OR branch_id IS NULL)
                AND `date` < '$d'
              GROUP BY `date`) latest
        JOIN bd_handover h ON h.id = latest.id
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
			SELECT '' se, 'bd_handover' source, h.id, h.amount, concat(h.`date`, ' 23:59:59') `date`, h.created_at entry_time, h.created_by entry_by, 'Bank & Cash Handover from Daily Collection' particulars, h.`status`, '' ref, '' done_by, '' done_time, 0 checked FROM bd_handover h JOIN (SELECT MAX(id) id FROM bd_handover WHERE branch_id=$branch_id AND (amount>0 OR bank_amount > 0) AND date BETWEEN '$d' AND '$t' GROUP BY `date`) latest ON h.id = latest.id
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
		) t ORDER BY date");

	// print "SELECT 'hotel_statement_worker_payment' source, p.id, p.amount, p.date, p.entry_time, CONCAT('<u>', h.name, '</u> er staff <u>', w.name, '</u>, ', DATE_FORMAT(CONCAT(s.month,'-01'), '%b %Y'), ' maser salary ', p.particulars), IFNULL(p.approved_by, 'Pending') status, '' ref FROM `hotel_statement_worker_payment` p, `hotel_statement_worker` w, `hotel_statement` s, `hotel` h WHERE p.worker=w.id AND w.statement=s.id AND s.hotel=h.id AND p.date>'$hotel_start_date' AND (p.date BETWEEN '$d' AND '$t') AND (p.particulars LIKE 'Petty Cash theke%' OR p.particulars LIKE 'Me2 te%')";

	print "<form method='post'>";
	print "<table class='table table-bordered'>";
	// print "<tr><th>No.</th><th>Date</th><th>Particulars</th><th></th><th>User</th><th>Cash In</th><th class='w120'></th><th>Cash Out</th><th class='w120'></th><th>Balance</th><th></th>";

	//<th><a href='add_cash'>Add Cash</a><br><a href='widthdraw'>Cash Withdraw</a></th><th>Invested Capital</th>
	print "<tr><th>No.</th><th>Date</th><th>Particulars</th><th>Cash In</th><th width='80px'></th><th>Cash Out</th><th>Balance</th><th>Manager</th><th>Bank In</th><th>Bank Out</th><th>Balance</th><th>Status</th><th>Approval</th>";
	if (uid() == 1) {
		print "<th>Del</th>";
	}
	print "</tr>";
	$openingTailColspan = 6 + (uid() == 1 ? 1 : 0);
	print "<tr><th colspan='6'>Opening Balance</th><th>" . nf($total) . "</th><th colspan='" . $openingTailColspan . "'></th></tr>";
	$i = 1;
	// vd($opening);
	$userList = userList();
	$userList[0] = '';
	$total_debit = 0;
	$cashInTotal = 0;
	$cashOutTotal = 0;
	$bankInTotal = 0;
	$bankOutTotal = 0;
	while ($tr = mysqli_fetch_object($trans)) {
		$cashBefore = $total;
		$bankBefore = sum('bank');

		$r = "<td title='$tr->entry_time > $tr->status'>" . df($tr->date) . "</td>";
		$r .= "<td class='particulars pre-wrap'>";

		if ($tr->source == 'hotel_statement_worker_payment') {
			$r .= '<img src="assets/verified.png" width="32px">';
		} elseif ($tr->source == 'expenditure' && in_array($tr->ref, [91, 92])) {
			$r .= '<img src="assets/verified2.png" width="32px">';
		}
		if ($tr->source == 'bd_handover') {
			$petty_cash_report = R::findOne("petty_cash_report", "handover_id=?", [$tr->id]);
			if ($petty_cash_report) {
				$r .= "<a href='report/petty_cash/$tr->id' target='_blank'>$tr->particulars</a>";
			} else {
				$r .= "$tr->particulars";
			}
		} else {
			$r .= "$tr->particulars";
		}

		if ($tr->source == 'expenditure') {
			if (!$tr->checked) {
				if (isUserIn(['superadmin'])) {
					$r .= "<img src='/app/assets/done.png' class='checked-pending' data-src='$tr->source' data-id='$tr->id'>";
					$r .= "<img src='/app/assets/ban.png' class='checked-banned' data-src='$tr->source' data-id='$tr->id'>";
				} else {
					// $r .= '<img src="/app/assets/done.png" class="checked">';
				}
			} elseif ($tr->checked == 1) {
				$r .= '<img src="/app/assets/done.png" class="checked">';
			} elseif ($tr->checked == 2) {
				if (isUserIn(['superadmin'])) {
					$r .= "<img src='/app/assets/done.png' class='checked-pending' data-src='$tr->source' data-id='$tr->id'>";
				}
				$r .= '<img src="/app/assets/ban.png" class="checked">';
			}
		}

		$r .= "</td>";

		// .($tr->source == 'hotel_statement_worker_payment' ? '<img src="assets/verified.png" width="32px">' : (($tr->source == 'expenditure' && in_array($tr->ref, [91,92])) ?  '<img src="assets/verified2.png" width="32px">': ''))."$tr->particulars</td>";
		$statusCell = cash_render_status_button($tr->status, $tr->source, $tr->id, $canApproveStatus);
		$approvalCell = '';
		if ($tr->status == 'Pending') {
			$approvalCell = "<input type='checkbox' name='approvem[]' value='{$tr->source}-{$tr->id}'>";
		}

		sum($tr->source, $tr->amount);
		if ($tr->source == 'bd_handover') {
			$handover = R::load("bd_handover", $tr->id);
			sum('bank', $handover->bank_amount);
			sum('banko', $handover->bank_amount);
			$total += $tr->amount;
		} elseif ($tr->source == 'cw_cash') {
			sum('cash', $tr->amount);
			// if($tr->amount<0) sum('bank', 0 - $tr->amount);
			$total += $tr->amount;
		} elseif ($tr->source == 'cw_bank') {
			// if($tr->amount<0) sum('bank', 0 - $tr->amount);
			sum('bank', $tr->amount);
			sum('banko', $tr->amount);
			// $total += $tr->amount;
		} elseif ($tr->source == 'payment_cash') {
			$total -= $tr->amount;
		} elseif ($tr->source == 'payment_bank') {
			// $total -= $tr->amount;

			sum('bank', 0 - $tr->amount);
			sum('banko', 0 - $tr->amount);
		} elseif ($tr->source == 'cw_payment') {
			// $total += $tr->amount;
		} elseif ($tr->source == 'expense_account_entry' || $tr->source == 'cw_outlet') {
			$total -= $tr->amount;
			if ($tr->source == 'cw_outlet') {
				sum('expense_account_entry', abs($tr->amount));
			}
		} elseif ($tr->source == 'expense_account_entry_bank') {
			// $total -= $tr->amount;
			sum('bank', 0 - $tr->amount);
		} elseif ($tr->source == 'cw_cash_withdraw') {
			$total -= abs($tr->amount);
		} elseif ($tr->source == 'investment') {
			$total += $tr->amount;
		} else {
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

		$cashInClass = 'right' . ($tr->source == 'bd_handover' && $cashDelta > 0 ? ' cash-in-bd' : '');
		$cashOutClass = 'right' . ($cashDelta < 0 ? ' cash-out-amount' : '');
		$bankOutClass = 'right' . ($bankDelta < 0 ? ' bank-out-amount' : '');
		$r .= "<td class='$cashInClass'>" . $cashIn . "</td>";
		$r .= "<td class='center select-cell' data-cashout='" . ($cashDelta < 0 ? abs($cashDelta) : 0) . "'></td>";
		$r .= "<td class='$cashOutClass'>" . $cashOut . "</td>";
		$r .= "<td class='rht'>" . nf($cashAfter) . "</td>";
		$r .= "<td title='$tr->source'><small>" . $userList[$tr->entry_by] . "</small></td>";
		$r .= "<td class='right'>" . $bankIn . "</td>";
		$r .= "<td class='$bankOutClass'>" . $bankOut . "</td>";
		$r .= "<td class='rht'>" . nf($bankAfter) . "</td>";
		$r .= "<td class='center'>" . $statusCell . "</td>";
		$r .= "<td class='center'>" . $approvalCell . "</td>";
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
		if (uid() == 1) {
			$r .= "<td><a href='#' class='edit-transaction' data-source='$tr->source' data-id='$tr->id' data-bs-toggle='modal' data-bs-target='#editModal'><i class='fa fa-edit' style='color: #0066cc; margin-right: 10px;'></i></a><a class='protected-link' href='?d=$d&t=$t&del=$tr->source&id=$tr->id' target='_blank'><i class='fa fa-trash'></i></a></td>";
		}
		$rowClasses = $tr->source;
		if ($cashDelta < 0)
			$rowClasses .= ' cash-out-row';
		if ($bankDelta < 0)
			$rowClasses .= ' bank-out-row';
		print "<tr class='$rowClasses'><td title='" . ($tr->source == 'expense_account_entry' ? $tr->se : $tr->source) . "'>$i</td>$r</tr>";
		$i++;
	}

	$investment_period_total = getSum("investment", "amount", "date BETWEEN '$d' AND '$t' AND trash=0");

	print "<tr><th colspan='2' class='text-right'>Total Investment</th><th class='right text-danger' colspan='1'><a href='../invest'>" . nf($investment_period_total) . "</a></th><th class='right'>" . nf($cashInTotal) . "</th><th></th><th class='right'>" . nf($cashOutTotal) . "</th><th class='right'>" . nf($total) . "</th><th></th><th class='right'>" . nf($bankInTotal) . "</th><th class='right'>" . nf($bankOutTotal) . "</th><th class='right'>" . nf(sum('bank')) . "</th>" . (uid() == 1 ? "<th></th>" : "") . "</tr>";

	if (uid() == 1) {
		print "<tr><td colspan='14' class='cntr'><button class='btn btn-success'>Approve Selected</button></td></tr>";
	}

	if (uid() != 1 && isUserIn(['orange', 'lemon'])) {
		print "<tr><td colspan='13' class='cntr'><button class='btn btn-success'>Done Selected</button>  <span class='alert alert-warning'>TOTAL SELECTED : <span class='total-selected'></span></span></td></tr>";
	}
	print "<tr id='totalCashOutRow'><td colspan='13' class='cntr'><span class='total-sum'>Total Cash Out Selected : <strong class='total-cashout-selected'>0.00</strong></span></td></tr>";
	print "</table>";
	print "<div class='text-center fs-2 fw-bold mb-3'>Total Petty Cash Amount: " . nf($total) . "</div>";
	print "</form>";
	print "<div class='right'><a href='?' class='btn btn-danger'>Cancel</a></div>";
	print "<hr>";
	print "</div>";
} else {

	$mon = isset($get->mon) ? substr($get->mon, 0, 7) : date("Y-m", time());

	$bt_url = "";
	if (isset($get->add_bt)) {
		$bt_url = "&add_bt=$get->add_bt";
	}


	print "<br>";


	$date = date("Y-m-d", strtotime("$mon-01"));

	?>

	<div class="row">
		<!-- <div class="col-md-4"></div> -->
		<!-- <div class="col-md-4"><a class='pointer btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-create'>Create Car Wash</a></div> -->
		<div class="col-md-4 text-right"><strong style="font-size: 3rem; font-weight:700; text-shadow: 0 0 5 #000">Petty
				Cash Report</strong></div>
		<div class="col-md-4">
			<?php



			print "<span style='float:right'>
		<a class='pointer btn btn-outline-secondary'  href='?page=18&company=&mon=" . subMonth(1, $date) . "$bt_url'><i class='fa fa-chevron-left'></i>Prev</a>" . space(5);
			//."<b>";
			// print date("M Y", strtotime($mon."-01"))."</b>";
			print monthSelector('mon', date("Y-m-d", strtotime($mon . "-01")));
			print space(5) .
				"<a class='pointer btn btn-outline-secondary' href='?page=18&company=&mon=" . addMonth(1, $date) . "$bt_url'>Next <i class='fa fa-chevron-right'></i></a>
		</span>";
			print "<br><br>";

			?>
		</div>
		<div class="col-md-4 rht">
			<input class='form-control-fluid search-cars' placeholder="Search..."
				style="height: unset; line-height: 3rem; font-size: 24px;">
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
						<tr>
							<td>Name</td>
							<td><input name='name' class='form-control' required></td>
						</tr>
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
						<tr>
							<td>Name</td>
							<td><input name='name' class='form-control' required></td>
						</tr>
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
						<tr>
							<td>Name</td>
							<td><input name='name' class='form-control' required></td>
						</tr>
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
	<tr><td colspan='5'><b>" . str("Customer Details") . "</b></td><tr>
		<tr><td>" . str("Number") . "</td><td><input type='text' name='number' id='number' value='$object->number' class='form-control required' required /></td><td>" . space(5) . "</td><td>" . str("Phone") . "</td><td><input type='text' name='phone' id='phone' value='$object->phone' class='form-control' /></td></tr>
		<tr><td>" . str("Brand") . "</td><td>" . sop2("brand", $object->brand, ['optional' => true, 'width' => '']) . "</td><td>" . space(5) . "</td><td>" . str("Model") . "</td><td id='td-model'></td></tr>
		<tr><td>" . str("Branch") . "</td><td>" . sop2("company", $id, ['optional' => true, 'attr' => 'readonly disabled', 'width' => ''], "cw_company") . "</td><td>" . space(5) . "</td><td>" . str("Roadtax") . "</td><td>" . dateSelector("roadtax", $object->roadtax) . "</td></tr>
		<tr><td>" . str("Next Service Date") . "</td><td>" . dateSelector("next_service_date", $object->next_service_date) . "</td><td>" . space(5) . "</td><td>Photo</td>
			<td><input name='photo_file' type='file' class='form-control w240' />" . (file_exists("uploads/cars/$object->id/$object->photo_file") ? "<a href='../../uploads/cars/$object->id/$object->photo_file?" . time() . "' target='_blank'><img src='../../uploads/cars/$object->id/$object->photo_file' width='64px'></a>" : "") . "</td></tr>
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
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Add Cash</h4>
			</div>
			<form method="post">
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input type='number' class='form-control' onkeyup="setACPart()" required name='amount'
									id="amount" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<div id="hints">
									<!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
								</div>
								<br>
								<textarea class='form-control' id='add_cash_particulars' name='particulars'></textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_cash">Save</button>
					<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default"
						data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>


<div class="modal fade bank" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Bank Deposit</h4>
			</div>
			<form method="post">
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date2'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input typye='number' class='form-control' required name='amount' id="bd-amount"
									onkeyup="setBDPart()" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<div id="hints">
									<!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
								</div>
								<br>
								<textarea class='form-control' id='bd-particulars'
									name='particulars'>Petty Cash Exchcange to Bank Account Rm </textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_bank">Save</button>
					<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default"
						data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
</div>

<script type="text/javascript">
	function setACPart() {
		$("#add_cash_particulars").val("Rm : " + $("#amount").val() + " Cash Investment for");
	}
	function setCWPart() {
		$("#withdraw_particulars").val("Rm : " + $("#cw-amount").val() + " Cash Withdrawal for");
	}
	function setBDPart() {
		$("#bd-particulars").val("Petty Cash Exchcange to Bank Account Rm " + $("#bd-amount").val());
	}
	function setOutletPart() {
		$("#outlet-particulars").val("Petty Cash Exchcange to Outlet Account Rm " + $("#outlet-amount").val());
	}
</script>
<script type="text/javascript">
	$(document).ready(function () {
		$('#totalCashOutRow').hide();
		$('.select-cell').each(function () {
			if (parseFloat($(this).data('cashout')) === 0) {
				$(this).qtip({
					content: 'No Cash Out Balance',
					position: { my: 'top center', at: 'bottom center' },
					style: { classes: 'qtip-bootstrap', color: '#FDDC62' }
				});
			}
		});
		$(document).on('click', '.select-cell', function () {
			var $row = $(this).closest('tr');
			$row.toggleClass('row-selected');
			var total = 0;
			$('.row-selected .select-cell').each(function () {
				total += parseFloat($(this).data('cashout')) || 0;
			});
			$('.total-cashout-selected').text(total > 0 ? total.toFixed(2) : '0.00');
			$('#totalCashOutRow').toggle(total > 0);
		});
	});
</script>

<div class="modal fade outlet" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Bank Deposit</h4>
			</div>
			<form method="post">
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date3'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input typye='number' class='form-control' required name='amount' id="outlet-amount"
									onkeyup="setOutletPart()" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<div id="hints">
									<!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
								</div>
								<br>
								<textarea class='form-control' id='outlet-particulars'
									name='particulars'>Petty Cash Exchcange to Outlet Account Rm </textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_outlet">Save</button>
					<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default"
						data-bs-dismiss="modal">Close</button>
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
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Petty Cash to Bank</h4>
			</div>
			<form method="post">
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date3'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input typye='number' class='form-control' required name='amount' min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<div id="hints">
									<!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
								</div>
								<br>
								<textarea class='form-control' id='' name='particulars'></textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_bank_deposit">Save</button>
					<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default"
						data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>



<div class="modal fade withdraw" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Cash Withdraw</h4>
			</div>
			<form method="post">
				<input type='hidden' name='account' value='46'>
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date2'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input typye='number' class='form-control' onkeyup="setCWPart()" required name='amount'
									id="cw-amount" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<!-- <div id="hints">
					 <div><input type='radio' name='r'>Nadim Kazi may bank theke petty cash a taka add cash kora hoyese Rm </div>
					 <div><input type='radio' name='r'>Nadim Kazi  Rhb bank theke petty cash a taka add cash kora hoyese Rm </div>
					 <div><input type='radio' name='r'>Arif bhai may bank theke petty cash a taka add cash kora hoyese Rm </div>
					 <div><input type='radio' name='r'>Boss er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div>
					 <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div>
					</div>
					<br> -->
								<textarea class='form-control' id='withdraw_particulars' required
									name='particulars'></textarea>
							</th>
						</tr>
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
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Asset Investment</h4>
			</div>
			<form method="post">
				<div class="modal-body">
					<table class="table table-bordered">
						<tr>
							<th>Date</th>
							<th><?php print ds('date'); ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><input type='number' class='form-control' required name='amount' id="invest-amount"
									min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th>
								<textarea class='form-control' id='invest_particulars' required
									name='particulars'></textarea>
							</th>
						</tr>
						<tr>
							<th>Payment Method</th>
							<th>
								<select class='form-control' name='payment_method' id='invest_payment_method'>
									<option value='Bank'>Bank</option>
									<option value='Cash'>Cash</option>
								</select>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_investment">Save</button>
					<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default"
						data-bs-dismiss="modal">Close</button>
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
	$("select.cars").change(function () {
		// var car = $("select.cars");
	});

	$(".search-cars").keyup(function () {

		var key = $('.search-cars').val();
		if (key.length > 1)
			$.post("/app/ajax/cars.php", { key: key, company: <?php print 1 ?> }, function (data) {
				$("#search-result").html(data);
			});

	});


	$("select.brand").change(getModels);

	getModels();

	function getModels() {
		$.post('/app/ajax/model.php', { name: $("select.brand option:selected").val() }, function (data) {
			$("#td-model").html(data);
			// $("#td-model").find("select").selectpicker();
		});
	}

	$(document).on('click', '.edit-transaction', function (e) {
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
		}, function (response) {
			if (response && response.success) {
				populateEditModal(source, response.data, dateText, particularsText);
			} else {
				populateEditModal(source, {}, dateText, particularsText);
			}
		}, 'json').fail(function () {
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

		switch (source) {
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
		if (source === 'payment_cash' || source === 'payment_bank') {
			$('input[name="cash_edit_payment_method"]').on('change', function () {
				setParticulars();
			});
		}
	}

	function setParticulars() {
		const paymentMethod = $('input[name="cash_edit_payment_method"]:checked').val();
		const particulars = $('textarea[name="cash_edit_particulars"]').val();

		if (paymentMethod === 'Bank') {
			// Auto-set to bank if not already set
			if (!particulars.toLowerCase().includes('bank')) {
				$('textarea[name="cash_edit_particulars"]').val('Bank transfer - ' + particulars);
			}
		} else if (paymentMethod === 'Cash') {
			// Remove bank prefix if it exists
			if (particulars.toLowerCase().includes('bank transfer')) {
				$('textarea[name="cash_edit_particulars"]').val(particulars.replace(/Bank transfer - /i, ''));
			}
		}
	}

	// ── Approval trigger (single-click approve) ─────────────────
	var currentApprovalSource = '';
	var currentApprovalId = '';
	var currentD = '<?php echo addslashes((string) (isset($d) ? $d : '')); ?>';
	var currentT = '<?php echo addslashes((string) (isset($t) ? $t : '')); ?>';

	$(document).on('click', '.approval-trigger', function (e) {
		e.preventDefault();
		var canApprove = Number($(this).data('can-approve'));
		if (isNaN(canApprove)) canApprove = <?php echo $canApproveStatus ? '1' : '0'; ?>;

		if (canApprove !== 1) {
			if (window.Swal && typeof Swal.fire === 'function') {
				Swal.fire({ icon: 'warning', text: 'Only admin can change approval status' });
			} else {
				alert('Only admin can change approval status');
			}
			return false;
		}

		currentApprovalSource = $(this).data('source');
		currentApprovalId = $(this).data('id');

		var doApprove = function () {
			$.post('', {
				approve_with_color: true,
				source: currentApprovalSource,
				id: currentApprovalId,
				status: 'Approved'
			}, function (response) {
				var result = response;
				if (typeof response === 'string') { try { result = JSON.parse(response); } catch (e) { result = { success: true }; } }
				if (result && result.success === false) {
					if (window.Swal && typeof Swal.fire === 'function') {
						Swal.fire({ icon: 'warning', text: result.message || 'Only admin can change approval status' });
					} else {
						alert(result.message || 'Only admin can change approval status');
					}
					return;
				}
				window.location.href = '?d=' + encodeURIComponent(currentD) + '&t=' + encodeURIComponent(currentT);
			});
		};

		if (window.Swal && typeof Swal.fire === 'function') {
			Swal.fire({
				icon: 'question',
				title: 'Approve this entry?',
				showCancelButton: true,
				confirmButtonText: 'Yes, Approve'
			}).then(function (result) {
				if (result.isConfirmed) doApprove();
			});
		} else {
			if (confirm('Approve this entry?')) doApprove();
		}
	});
</script>