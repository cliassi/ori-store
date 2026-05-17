<style type="text/css">
	.highlight-exchange {
		background-color: yellow;
		font-weight: bold;
	}

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

	.large {
		font-size: 14px;
		text-transform: uppercase;
		font-weight: 700;
	}

	th {
		text-align: center;
	}

	input::-webkit-outer-spin-button,
	input::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}

	input[type=number] {
		-moz-appearance: textfield;
	}

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
		$user = R::load('sys_user', (int) uid());
		$username = strtolower(trim((string) ($user->u_username ?? '')));
		return (int) uid() === 1 || $username === 'adminn';
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

if (!function_exists('cash_ensure_column')) {
	function cash_ensure_column($table, $column, $definition)
	{
		if (!cash_has_column($table, $column)) {
			R::exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
		}
	}
}

if (!function_exists('cash_build_file_url')) {
	function cash_build_file_url($path)
	{
		$path = trim((string) $path);
		if ($path === '') {
			return '';
		}
		$path = str_replace('\\', '/', ltrim($path, '/'));
		if (preg_match('#^https?://#i', $path)) {
			return $path;
		}
		if (strpos($path, 'uploads/') === 0) {
			return '/store/' . $path;
		}
		if (strpos($path, 'app/pages/') === 0) {
			return '/store/' . $path;
		}
		if (strpos($path, 'reports/uploads/') === 0) {
			return '/store/app/pages/' . $path;
		}
		return '/store/app/pages/' . $path;
	}
}

if (!function_exists('cash_can_manage_manager_notes')) {
	function cash_can_manage_manager_notes()
	{
		return cash_is_admin_approver() || isUserIn(['superadmin', 'amla', 'orange']);
	}
}

if (!function_exists('cash_resolve_file_path')) {
	function cash_resolve_file_path($path)
	{
		$path = trim((string) $path);
		if ($path === '' || preg_match('#^https?://#i', $path)) {
			return '';
		}

		$path = str_replace('\\', '/', ltrim($path, '/'));
		$rootDir = dirname(__DIR__, 3);
		$candidates = [
			$rootDir . '/' . $path,
			$rootDir . '/app/pages/' . $path,
		];

		foreach ($candidates as $candidate) {
			if (is_file($candidate)) {
				return $candidate;
			}
		}

		return $candidates[0];
	}
}

if (!function_exists('cash_delete_stored_file')) {
	function cash_delete_stored_file($path)
	{
		$absolutePath = cash_resolve_file_path($path);
		if ($absolutePath !== '' && is_file($absolutePath)) {
			@unlink($absolutePath);
		}
	}
}

if (!function_exists('cash_is_valid_approval_source')) {
	function cash_is_valid_approval_source($source)
	{
		$allowedSources = [
			'bd_handover',
			'cw_cash',
			'cw_bank',
			'cw_cash_withdraw',
			'expense_account_entry',
			'payment',
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
$canManageManagerNotes = cash_can_manage_manager_notes();
cash_ensure_column('expense_petty_cash_manager', 'expense_id', "INT(11) NULL");
cash_ensure_column('expense_petty_cash_manager', 'file_path', "VARCHAR(255) NULL");
cash_ensure_column('expense_petty_cash_manager', 'file_name', "VARCHAR(255) NULL");
cash_ensure_column('expense_account_entry', 'opex_or_capex', "VARCHAR(20) NULL DEFAULT 'Capex'");
cash_ensure_column('payment', 'opex_or_capex', "VARCHAR(20) NULL DEFAULT 'Capex'");
cash_ensure_column('payment', 'ref_no', "VARCHAR(20) NULL");

// Migration: Categorize all existing expense_account_entry rows under Capex (account path /3/)
try {
	R::exec("UPDATE expense_account_entry e 
		JOIN expense_account ea ON e.accountid = ea.id 
		SET e.accountid = (
			SELECT id FROM expense_account 
			WHERE breadcrumbs LIKE '%/3/%' OR path LIKE '/3/%' 
			ORDER BY id ASC LIMIT 1
		)
		WHERE ea.breadcrumbs NOT LIKE '%/3/%' AND ea.path NOT LIKE '/3/%'
		AND e.id > 0");
} catch (\Throwable $ignored) {
}

if (isset($post->approve_with_color)) {
	if (!$canApproveStatus) {
		echo json_encode(['success' => false, 'message' => 'Only admin can change approval status']);
		exit;
	}
	$status = strtolower(trim((string) ($post->status ?? 'approved'))) === 'pending' ? 'Pending' : 'Approved';
	$updated = cash_update_entry_status((string) ($post->source ?? ''), (int) ($post->id ?? 0), $status);
	if (!$updated) {
		echo json_encode(['success' => false, 'message' => 'Invalid row selected for approval']);
		exit;
	}
	echo json_encode(['success' => true]);
	exit;
}

if (isset($post->save_carwash)) {
	$carwash = R::dispense("cw_sites");
	$carwash->name = $post->name;
	$carwash->entry_by = uid();
	R::store($carwash);
}

if (isset($post->save)) {
	$object = R::dispense("cw_customer");
	$function = 'add';
	$post->company = $id;
	require_once("model/cw_customer.php");
}

if (isset($post->save_cash)) {
	$deposit = R::dispense("cw_cash");
	$deposit->date = isset($post->date) ? $post->date : today();
	$deposit->particulars = $post->particulars;
	$deposit->amount = $post->amount;
	$deposit->company = $get->cw;
	$deposit->entry_by = uid();
	$deposit->entry_time = now();
	R::store($deposit);
	redir("?");
}

if (isset($post->add_bank)) {
	$deposit = R::dispense("cw_bank");
	$deposit->date = isset($post->date) ? $post->date : today();
	$deposit->particulars = $post->particulars;
	$deposit->amount = $post->amount;
	$deposit->company = $get->cw;
	$deposit->entry_by = uid();
	$deposit->entry_time = now();
	R::store($deposit);
	redir("?");
}

if (isset($post->save_bank)) {
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date2) ? $post->date2 : today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2) ? $post->date2 : today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = 0 - $post->amount;
	$cw_bank->company = $get->cw;
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	$cw_bank->cash_id = $cw_cash->id;
	R::store($cw_bank);
	$cw_cash->bank_id = $cw_bank->id;
	R::store($cw_cash);
	redir("?");
}

if (isset($post->save_bank_deposit)) {
	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2) ? $post->date2 : today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = $post->amount;
	$cw_bank->company = $get->cw;
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	R::store($cw_bank);
	$cashRedirectD = isset($get->d) ? (string) $get->d : '';
	$cashRedirectT = isset($get->t) ? (string) $get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '')
		? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT))
		: '?';
	redir($cashRedirectUrl);
}

if (isset($post->withdraw)) {
	$withdraw = R::dispense("cw_cash_withdraw");
	$withdraw->particulars = $post->particulars;
	$withdraw->date = isset($post->date2) ? (nn($post->date2) ? $post->date2 : today()) : today();
	$withdraw->amount = $post->amount;
	$withdraw->company = $get->cw;
	$withdraw->entry_by = uid();
	$withdraw->entry_time = now();
	R::store($withdraw);
	$cashRedirectD = isset($get->d) ? (string) $get->d : '';
	$cashRedirectT = isset($get->t) ? (string) $get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '')
		? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT))
		: '?';
	redir($cashRedirectUrl);
}

if (isset($post->cash_edit_save)) {
	$cashRedirectD = isset($get->d) ? (string) $get->d : '';
	$cashRedirectT = isset($get->t) ? (string) $get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '')
		? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT))
		: '?';
	$editSource = isset($post->cash_edit_source) ? (string) $post->cash_edit_source : '';
	$editId = isset($post->cash_edit_id) ? (int) $post->cash_edit_id : 0;
	$editDate = isset($post->cash_edit_date) ? (string) $post->cash_edit_date : '';
	$editParticulars = isset($post->cash_edit_particulars) ? (string) $post->cash_edit_particulars : '';
	$editAmount = isset($post->cash_edit_amount) ? (float) $post->cash_edit_amount : 0;
	$editOpexOrCapex = isset($post->cash_edit_opex_or_capex) ? (string) $post->cash_edit_opex_or_capex : '';

	$canEditCash = uid() == 1 || isUserIn(['superadmin', 'amla', 'orange']);
	if (!$canEditCash) {
		redir($cashRedirectUrl);
		exit;
	}

	if ($editId > 0 && nn($editSource)) {
		if (in_array($editSource, ['cw_cash', 'cw_bank', 'cw_cash_withdraw'], true)) {
			$bean = R::load($editSource, $editId);
			if ($bean && $bean->id) {
				if (nn($editDate)) {
					$bean->date = $editDate;
				}
				if (nn($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				R::store($bean);

				if ($editSource === 'cw_cash' && isset($bean->bank_id) && (int) $bean->bank_id > 0) {
					$linked = R::load('cw_bank', (int) $bean->bank_id);
					if ($linked && $linked->id) {
						$linked->amount = (float) $bean->amount;
						if (nn($editDate)) {
							$linked->date = $bean->date;
						}
						if (nn($editParticulars)) {
							$linked->particulars = $bean->particulars;
						}
						R::store($linked);
					}
				}
				if ($editSource === 'cw_bank' && isset($bean->cash_id) && (int) $bean->cash_id > 0) {
					$linked = R::load('cw_cash', (int) $bean->cash_id);
					if ($linked && $linked->id) {
						$linked->amount = (float) $bean->amount;
						if (nn($editDate)) {
							$linked->date = $bean->date;
						}
						if (nn($editParticulars)) {
							$linked->particulars = $bean->particulars;
						}
						R::store($linked);
					}
				}
			}
		} elseif (in_array($editSource, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$bean = R::load('expense_account_entry', $editId);
			if ($bean && $bean->id) {
				$expectedPaymentMethod = ($editSource === 'expense_account_entry') ? 'Cash' : 'Online';

				if (nn($editDate)) {
					$bean->expense_date = $editDate;
				}
				if (nn($editParticulars)) {
					$bean->particulars = $editParticulars;
				}
				if ($editAmount > 0) {
					$bean->amount = $editAmount;
				}
				if (in_array($editOpexOrCapex, ['Capex', 'Opex'], true)) {
					$bean->opex_or_capex = $editOpexOrCapex;
				}
				$bean->payment_method = $expectedPaymentMethod;
				R::store($bean);
			}
		}
	}
	redir($cashRedirectUrl);
}

if (isset($post->cash_expense_edit_save)) {
	$cashRedirectD = isset($get->d) ? (string) $get->d : '';
	$cashRedirectT = isset($get->t) ? (string) $get->t : '';
	$cashRedirectUrl = ($cashRedirectD !== '' && $cashRedirectT !== '')
		? ('?d=' . urlencode($cashRedirectD) . '&t=' . urlencode($cashRedirectT))
		: '?';
	$editSource = isset($post->cash_expense_edit_source) ? (string) $post->cash_expense_edit_source : '';
	$editId = isset($post->cash_expense_edit_id) ? (int) $post->cash_expense_edit_id : 0;
	$editAccountId = isset($post->cash_expense_accountid) ? (int) $post->cash_expense_accountid : 0;
	$editDate = isset($post->cash_expense_expense_date) ? (string) $post->cash_expense_expense_date : '';
	$editParticulars = isset($post->cash_expense_particulars) ? (string) $post->cash_expense_particulars : '';
	$editAmount = isset($post->cash_expense_amount) ? (float) $post->cash_expense_amount : 0;
	$editOpexOrCapex = isset($post->cash_expense_opex_or_capex) ? (string) $post->cash_expense_opex_or_capex : '';

	$canEditCash = uid() == 1 || isUserIn(['superadmin', 'amla', 'orange']);
	if (!$canEditCash) {
		redir($cashRedirectUrl);
		exit;
	}

	if ($editId > 0 && in_array($editSource, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
		$bean = R::load('expense_account_entry', $editId);
		if ($bean && $bean->id) {
			$expectedPaymentMethod = ($editSource === 'expense_account_entry') ? 'Cash' : 'Online';

			if ($editAccountId > 0) {
				$acc = R::load('expense_account', $editAccountId);
				if ($acc && $acc->id) {
					$bean->accountid = (int) $acc->id;
					$bean->accountpath = (string) ($acc->path ?? '');
				}
			}
			if ($editAmount > 0) {
				$bean->amount = $editAmount;
			}
			if (nn($editDate)) {
				$bean->expense_date = $editDate;
			}
			if (nn($editParticulars)) {
				$bean->particulars = $editParticulars;
			}
			if (in_array($editOpexOrCapex, ['Capex', 'Opex'], true)) {
				$bean->opex_or_capex = $editOpexOrCapex;
			}
			$bean->payment_method = $expectedPaymentMethod;
			R::store($bean);
		}
	}
	redir($cashRedirectUrl);
}
if ($canApproveStatus && isset($get->approve) && isset($get->id)) {
	cash_update_entry_status((string) $get->approve, (int) $get->id, 'Approved');
}

if ($canApproveStatus && isset($post->approvem)) {
	foreach ($post->approvem as $data) {
		[$approveSource, $approveId] = cash_parse_approval_value($data);
		cash_update_entry_status((string) $approveSource, (int) $approveId, 'Approved');
	}
	redir("?d=$d&t=$t");
}

if (isset($post->save_petty_manager)) {
	$expenseId = (int) $post->expense_id;
	$managerId = (int) $post->name;
	$note = trim((string) $post->note);

	$expense = R::load('expense_account_entry', $expenseId);
	$manager = R::load('petty_cash_managers', $managerId);

	if ($expense->id && $manager->id) {
		$relation = R::dispense('expense_petty_cash_manager');
		$relation->expense_id = $expenseId;
		$relation->petty_cash_manager_id = $managerId;
		$relation->note = $note;
		R::store($relation);
	} else {
		echo "<script>if(window.Swal&&typeof Swal.fire==='function'){Swal.fire({icon:'error',text:'Invalid expense or manager selected'});}else{alert('Invalid expense or manager selected');}</script>";
	}
	redir("?d=$d&t=$t");
}

if (isset($post->approve)) {
	if ($canApproveStatus) {
		$approveSource = isset($post->approve_source) ? cash_normalize_source($post->approve_source) : '';
		$updated = cash_update_entry_status((string) $approveSource, (int) $post->approve, 'Approved');
		if (!$updated) {
			echo "<script>if(window.Swal&&typeof Swal.fire==='function'){Swal.fire({icon:'error',text:'Invalid row selected for approval'});}else{alert('Invalid row selected for approval');}</script>";
		}
		redir("?d=$d&t=$t");
	} else {
		echo "<script>if(window.Swal&&typeof Swal.fire==='function'){Swal.fire({icon:'warning',text:'Only admin can change approval status'});}else{alert('Only admin can change approval status');}</script>";
	}
}

if (isset($get->token)) {
	$token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
	if ($token) {
		R::trash($token);
		if ($canManageManagerNotes && isset($get->del_petty_note)) {
			$relation = R::load('expense_petty_cash_manager', (int) $get->del_petty_note);
			if ($relation->id) {
				cash_delete_stored_file((string) ($relation->file_path ?? ''));
				R::trash($relation);
			}
			redir("?d=$d&t=$t");
		}
		if ($canManageManagerNotes && isset($get->del_petty_file)) {
			$relation = R::load('expense_petty_cash_manager', (int) $get->del_petty_file);
			if ($relation->id) {
				cash_delete_stored_file((string) ($relation->file_path ?? ''));
				$relation->file_path = null;
				$relation->file_name = null;
				$relation->note = null;
				R::store($relation);
			}
			redir("?d=$d&t=$t");
		}
		if ($canApproveStatus && isset($get->approve) && isset($get->id)) {
			cash_update_entry_status((string) $get->approve, (int) $get->id, 'Approved');
			redir("?d=$d&t=$t");
		}
		if (uid() == 1 && isset($get->del) && isset($get->id)) {
			if (in_array($get->del, ['payment_bank', 'payment_cash']))
				$get->del = 'payment';
			if (in_array($get->del, ['expense_account_entry_bank', 'expense_account_entry_cash']))
				$get->del = 'expense_account_entry';
			$object = R::load($get->del, $get->id);
			if ($get->del == 'payment') {
				R::exec(
					"DELETE FROM `expense_account_entry` WHERE entry_id = ? AND entry_type IN ('Supplier - Payment', 'supplier - Payment', 'contractor - Payment', 'Contractor - Payment')",
					[(int) $get->id]
				);
			}
			if ($get->del == 'cw_bank') {
				$cw_cash = R::load("cw_cash", $object->cash_id);
				R::trash($cw_cash);
			}
			R::trash($object);
			redir("?d=$d&t=$t");
		}
		if (isset($get->del)) {
			if ($get->del == 'expense_account_entry') {
				R::trash(R::load("expense_account_entry", $get->id));
			} elseif ($get->del == 'cw_cash') {
				$ee = R::load("cw_cash", $get->id);
				$be = R::findOne("cw_bank", "cash_id=?", [$get->id]);
				if ($be)
					R::trash($be);
				R::trash($ee);
			} elseif ($get->del == 'cw_cash_withdraw') {
				R::trash(R::load("cw_cash_withdraw", $get->id));
			} elseif ($get->del == 'cw_bank') {
				$ee = R::load("cw_bank", $get->id);
				if ($ee->cash_id) {
					$be = R::findOne("cw_cash", "id=?", [$ee->cash_id]);
					if ($be)
						R::trash($be);
				}
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
	foreach (['name', 'phone', 'brand', 'model', 'number', 'roadtax', 'next_service_date', 'photo_file'] as $f)
		$cus_new->$f = $cus->$f;
	$cus_new->company = $id;
	$cus_new->entry_by = uid();
	$cus_new->entry_time = now();
	R::store($cus_new);
	redir("/app/cw_customer/view/$cus_new->id");
}

// ─────────────────────────────────────────────────────────────
// MAIN DISPLAY
// ─────────────────────────────────────────────────────────────
if (isset($get->cw)) {
	$company = R::load("cw_company", $get->cw);
	print "<h3><strong>Store</strong> Petty Cash Report</h3>";
	$d = isset($get->d) && !empty($get->d) ? $get->d : subDay(10);
	$t = isset($get->t) && !empty($get->t) ? $get->t : today();

	// Validate date format to prevent SQL errors
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
		$d = subDay(10);
	}
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
		$t = today();
	}

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
	if (isUserIn(['superadmin', 'amla', 'orange'])) {
		print "<a data-bs-toggle='modal' data-bs-target='.withdraw' class='btn btn-primary'>Cash Withdraw</a>" . space(3);
		print "<a data-bs-toggle='modal' onclick='setCash()' data-bs-target='.cash' class='btn btn-primary'>Add Cash</a>" . space(3);
		print "<a data-bs-toggle='modal' onclick='setBank()' data-bs-target='.cash' class='btn btn-success'>Add Bank</a>" . space(3);
		print "<a data-bs-toggle='modal' data-bs-target='.bank' class='btn btn-secondary'>Bank to Petty Cash</a>";
	}
	print "</div>";
	print "<div class='col-md-2'>";
	print "<a class='btn btn-sm btn-danger' style='font-size: 1.2rem;' href='/store/expense_account/carwash?company=3&t=capex'>Capex</a>" . space(5);
	print "<a class='btn btn-sm btn-warning' style='font-size: 1.2rem;' href='/store/expense_account/carwash?company=1&t=opex'>Opex</a>";
	print "</div>";
	print "</div>";

	$cw_cash = getSum("cw_cash", "amount", "date<'$d'");
	$cw_bank = getSum("cw_bank", "amount", "date<'$d'");
	$withdraw = getSum("cw_cash_withdraw", "amount", "date<'$d'");
	$expense_entry_cash = getSum("expense_account_entry", "amount", "payment_method='Cash' AND tran_type='Debit' AND expense_date<'$d'");
	$expense_entry_bank = getSum("expense_account_entry", "amount", "payment_method<>'Cash' AND tran_type='Debit' AND expense_date<'$d'");
	$payment_cash = getSum("payment", "amount", "date<'$d' AND payment_method='Cash'");
	$payment_bank = getSum("payment", "amount", "date<'$d' AND payment_method='Bank'");

	$ct = $cw_cash - $payment_cash - $expense_entry_cash;
	$bt = $cw_bank - $payment_bank - $expense_entry_bank;

	$query = "SELECT * FROM (
			SELECT '' se, 'bd_handover' source, id, amount, `date`, created_at entry_time, created_by entry_by, 'Bank & Cash Handover from Daily Collection' particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM bd_handover WHERE (amount>0 OR bank_amount > 0) AND date BETWEEN '$d' AND '$t'
			UNION
			SELECT '' se, 'cw_payment' source, 0 id, SUM(amount) amount, date, MIN(entry_time) entry_time, MIN(entry_by) entry_by, 'Total Bank Collection' particulars, '', '','','', 0 checked, '' opex_or_capex, 0 accountid FROM cw_payment WHERE amount>0 AND company=$company->id AND (date BETWEEN '$d' AND '$t') AND particulars NOT LIKE '%cash%' GROUP BY date
			UNION
			SELECT ea.breadcrumbs se, 'expense_account_entry' source, e.id, e.amount, CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END `date`, e.entry_time, e.entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, COALESCE(e.opex_or_capex, 'Capex') opex_or_capex, e.accountid accountid FROM expense_account_entry e LEFT JOIN expense_account ea ON e.accountid=ea.id WHERE e.payment_method='Cash' AND e.tran_type='Debit' AND CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END BETWEEN '$d 00:00:00' AND '$t 23:59:59'
			UNION
		SELECT ea.breadcrumbs se, 'expense_account_entry_bank' source, e.id, e.amount, CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END `date`, e.entry_time, e.entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, COALESCE(e.opex_or_capex, 'Opex') opex_or_capex, e.accountid accountid FROM expense_account_entry e LEFT JOIN expense_account ea ON e.accountid=ea.id WHERE e.payment_method<>'Cash' AND e.tran_type='Debit' AND CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END BETWEEN '$d 00:00:00' AND '$t 23:59:59'
		UNION
		SELECT '' se, 'cw_cash_withdraw' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_cash_withdraw WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id
		UNION
		SELECT bank_id se, 'cw_cash' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_cash WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id AND amount>0
		UNION
		SELECT cash_id se, 'cw_bank' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_bank WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id AND amount>0
		
	) t ORDER BY entry_time";

	$query = "SELECT * FROM (
			SELECT '' se, 'bd_handover' source, id, amount, `date`, created_at entry_time, created_by entry_by, 'Bank & Cash Handover from Daily Collection' particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM bd_handover WHERE (amount>0 OR bank_amount > 0) AND date BETWEEN '$d' AND '$t'
			UNION
			SELECT '' se, 'cw_payment' source, 0 id, SUM(amount) amount, date, MIN(entry_time) entry_time, MIN(entry_by) entry_by, 'Total Bank Collection' particulars, '', '','','', 0 checked, '' opex_or_capex, 0 accountid FROM cw_payment WHERE amount>0 AND company=$company->id AND (date BETWEEN '$d' AND '$t') AND particulars NOT LIKE '%cash%' GROUP BY date
			UNION
			SELECT ea.breadcrumbs se, 'expense_account_entry' source, e.id, e.amount, e.expense_date date, e.entry_time, e.entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, COALESCE(e.opex_or_capex, 'Capex') opex_or_capex, e.accountid accountid FROM expense_account_entry e LEFT JOIN expense_account ea ON e.accountid=ea.id WHERE e.payment_method='Cash' AND e.tran_type='Debit' AND CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END BETWEEN '$d 00:00:00' AND '$t 23:59:59'
			UNION
		SELECT ea.breadcrumbs se, 'expense_account_entry_bank' source, e.id, e.amount, e.expense_date date, e.entry_time, e.entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, COALESCE(e.opex_or_capex, 'Opex') opex_or_capex, e.accountid accountid FROM expense_account_entry e LEFT JOIN expense_account ea ON e.accountid=ea.id WHERE e.payment_method<>'Cash' AND e.tran_type='Debit' AND CASE WHEN e.expense_date IS NULL THEN e.entry_time ELSE e.expense_date END BETWEEN '$d 00:00:00' AND '$t 23:59:59'
		UNION
		SELECT '' se, 'cw_cash_withdraw' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_cash_withdraw WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id
		UNION
		SELECT bank_id se, 'cw_cash' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_cash WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id AND amount>0
		UNION
		SELECT cash_id se, 'cw_bank' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked, '' opex_or_capex, 0 accountid FROM cw_bank WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id AND amount>0
		
	) t ORDER BY date";

	$trans = select($query);

	print "<form method='post'>";
	print "<table class='table table-bordered'>";
	print "<tr><th>No.</th><th>Date</th><th>Particulars</th><th>User</th><th>Type</th><th class='w120'>Cash In</th><th class='w120'>Cash Out</th><th class='w120'>Balance</th><th class='w80'>Manager</th><th class='w120'>Bank In</th><th class='w120'>Bank Out</th><th class='w120'>Balance</th><th class='w80'>Status</th><th class='w80'>Action</th></tr>";
	print "<tr><th colspan='5' class='text-left'>Opening Balance</th><th></th><th></th><th>" . nf($ct) . "</th><th></th><th></th><th></th><th>" . nf($bt) . "</th><th></th><th></th></tr>";

	$cashBalance = (float) $ct;
	$bankBalance = (float) $bt;

	$i = 1;
	$userList = userList();
	$userList[0] = '';

	if (!$trans) {
		global $c;
		$dbError = isset($c) ? mysqli_error($c) : 'Unknown database error';
		print "<tr><td colspan='14' class='text-danger'>Unable to load petty cash rows. " . htmlspecialchars($dbError, ENT_QUOTES) . "</td></tr>";
	}

	while ($trans && ($tr = mysqli_fetch_object($trans))) {
		$rawParticulars = isset($tr->particulars) ? (string) $tr->particulars : '';
		if (in_array((string) $tr->source, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$trimmedParticulars = trim($rawParticulars);
			if (
				$trimmedParticulars !== ''
				&& (stripos($trimmedParticulars, 'Purchase:') === 0 || stripos($trimmedParticulars, 'Purchase Expenses:') === 0)
				&& stripos($trimmedParticulars, 'ref ORD') !== false
				&& preg_match('/total\s*0\/\-\s*$/i', $trimmedParticulars)
			) {
				continue;
			}
		}

		$particularsHtml = str_ireplace('exchcange', '<span class="highlight-exchange">exchcange</span>', (string) $tr->particulars);
		if (in_array((string) $tr->source, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$expenseEntry = R::load('expense_account_entry', (int) $tr->id);
			$entryType = strtolower(trim((string) ($expenseEntry->entry_type ?? '')));
			$paymentId = (int) ($expenseEntry->entry_id ?? 0);
			if ($paymentId > 0 && strpos($entryType, 'payment') !== false) {
				$payment = R::load('payment', $paymentId);
				$supplierId = (int) ($payment->supplier_id ?? 0);
				$contractorId = (int) ($payment->contractor_id ?? 0);
				if ($supplierId > 0) {
					$href = '/store/supplier/details/' . $supplierId;
					$particularsHtml = "<a href='" . htmlspecialchars($href, ENT_QUOTES) . "' target='_blank'>" . $particularsHtml . "</a>";
				} elseif ($contractorId > 0) {
					$href = '/store/contractor/details/' . $contractorId;
					$particularsHtml = "<a href='" . htmlspecialchars($href, ENT_QUOTES) . "' target='_blank'>" . $particularsHtml . "</a>";
				}
			}
		}
		if ($tr->source == 'bd_handover') {
			$petty_cash_report = R::findOne("petty_cash_report", "handover_id=?", [$tr->id]);
			if ($petty_cash_report) {
				$particularsHtml = "<a href='report/petty_cash/$tr->id' target='_blank'>" . $particularsHtml . "</a>";
			}
		}

		$userName = isset($userList[$tr->entry_by]) ? $userList[$tr->entry_by] : '';
		$typeText = '';
		if (in_array((string) $tr->source, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
			$opexOrCapex = isset($tr->opex_or_capex) && $tr->opex_or_capex ? (string) $tr->opex_or_capex : 'Capex';
			if (strtolower($opexOrCapex) === 'capex') {
				$typeText = 'Capex';
			} elseif (strtolower($opexOrCapex) === 'opex') {
				$typeText = 'Opex';
			} else {
				$typeText = $opexOrCapex; // Fallback to original value
			}
		} elseif ((string) $tr->source === 'bd_handover') {
			$typeText = 'Handover';
		} else {
			$typeText = '';
		}

		$cashIn = 0.0;
		$cashOut = 0.0;
		$bankIn = 0.0;
		$bankOut = 0.0;
		$amount = (float) ($tr->amount ?? 0);

		if ((string) $tr->source === 'bd_handover') {
			$handover = R::load("bd_handover", (int) $tr->id);
			$cashIn = (float) $amount;
			$bankIn = (float) ($handover->bank_amount ?? 0);
		} elseif ((string) $tr->source === 'cw_cash') {
			// cw_cash: cash added; if se exists it's bank->cash transfer
			$cashIn = (float) $amount;
			if (!empty($tr->se)) {
				$bankOut = (float) $amount;
			}
		} elseif ((string) $tr->source === 'cw_bank') {
			// cw_bank: bank added; if se exists it's cash->bank transfer
			if ($amount > 0) {
				$bankIn = (float) $amount;
				if (!empty($tr->se)) {
					$cashOut = (float) $amount;
				}
			} else {
				$bankOut = (float) abs($amount);
			}
		} elseif ((string) $tr->source === 'cw_cash_withdraw') {
			$cashOut = (float) $amount;
		} elseif ((string) $tr->source === 'expense_account_entry') {
			$cashOut = (float) $amount;
		} elseif ((string) $tr->source === 'expense_account_entry_bank') {
			$bankOut = (float) $amount;
		} elseif ((string) $tr->source === 'cw_payment') {
			// treat bank collection summary as bank in
			$bankIn = (float) $amount;
		} elseif ((string) $tr->source === 'investment') {
			if (strtolower((string) $tr->se) === 'cash') {
				$cashIn = (float) $amount;
			} else {
				$bankIn = (float) $amount;
			}
			$typeText = 'Investment';
		}

		$cashBalance += ($cashIn - $cashOut);
		$bankBalance += ($bankIn - $bankOut);

		$isExpenseRow = (string) $tr->source === 'expense_account_entry';
		$expenseFileUrl = '';
		$managerStatusHtml = '';
		$detailLink = "#";

		// Get material/supplier link for expense entries
		if ($isExpenseRow) {
			$expenseEntry = R::load('expense_account_entry', $tr->id);
			if ($expenseEntry->entry_id > 0) {
				$purchase = R::load('purchase', $expenseEntry->entry_id);
				if ($purchase->main_material_id > 0) {
					$detailLink = "/store/materials/details/?id=" . (int) $purchase->main_material_id;
				}
			}
		}

		if ($isExpenseRow) {
			$managerList = R::find('expense_petty_cash_manager', 'expense_id = ? ORDER BY id DESC', [(int) $tr->id]);
			$managerCount = 0;
			foreach ($managerList as $managerRow) {
				if ((int) ($managerRow->petty_cash_manager_id ?? 0) > 0) {
					$managerCount++;
				}
				if (
					$expenseFileUrl === ''
					&& isset($managerRow->file_path)
					&& trim((string) $managerRow->file_path) !== ''
				) {
					$candidateFileUrl = cash_build_file_url((string) $managerRow->file_path);
					if ($candidateFileUrl !== '') {
						$expenseFileUrl = $candidateFileUrl;
					}
				}
			}
			$amountNum = (float) $amount;
			$required = $amountNum > 50000 ? 3 : ($amountNum > 10000 ? 2 : 1);
			$decisionPending = $managerCount < $required;
			$decisionColor = $decisionPending ? 'rgb(220, 38, 38)' : 'inherit';
			$decisionCountClass = $decisionPending ? 'decided-count-pending' : 'decided-count-complete';

			$managerStatusHtml = "<small class='{$decisionCountClass}'>{$managerCount} / {$required}</small>";
			$addManagerClick = ((int) uid() === 1)
				? "openAddModal({$tr->id}); return false;"
				: "cash_admin_only_alert('Only admin can add manager'); return false;";
			$managerStatusHtml = "<a class='decided-action-link' title='Add Petty Cash Manager' href='javascript:void(0)' onclick=\"{$addManagerClick}\"><i class='fa fa-plus decided-action-icon'></i></a><br>"
				. "<a class='decided-action-link' title='Show Managers' href='javascript:void(0)' onclick='toggleAccordion({$tr->id}); return false;'><i class='fa fa-chevron-down decided-action-icon'></i></a><br>"
				. $managerStatusHtml;
		}
		$viewFileActionHtml = '';
		if ($isExpenseRow && $expenseFileUrl !== '') {
			$viewFileActionHtml = "<a class='decided-action-link mt-1' title='View uploaded file' href='" . htmlspecialchars($expenseFileUrl, ENT_QUOTES) . "' target='_blank'><i class='fa fa-eye decided-action-icon'></i></a>";
		}

		$editHtml = "";
		$canEditCash = uid() == 1;
		if (in_array((string) $tr->source, ['cw_cash', 'cw_bank', 'cw_cash_withdraw', 'expense_account_entry', 'expense_account_entry_bank', 'cw_payment'], true)) {
			if ($canEditCash) {
				if (in_array((string) $tr->source, ['expense_account_entry', 'expense_account_entry_bank'], true)) {
					$editHtml = "<a class='decided-action-link mx-2' href='javascript:void(0)' title='Edit' "
						. "data-bs-toggle='modal' data-bs-target='#cashExpenseEditModal' "
						. "data-edit-source='" . htmlspecialchars((string) $tr->source, ENT_QUOTES) . "' "
						. "data-edit-id='" . (int) $tr->id . "' "
						. "data-edit-date='" . htmlspecialchars(date('Y-m-d', strtotime((string) $tr->date)), ENT_QUOTES) . "' "
						. "data-edit-particulars='" . htmlspecialchars((string) $rawParticulars, ENT_QUOTES) . "' "
						. "data-edit-amount='" . htmlspecialchars((string) $amount, ENT_QUOTES) . "' "
						. "data-edit-opex-or-capex='" . htmlspecialchars((string) ($tr->opex_or_capex ?? ''), ENT_QUOTES) . "' "
						. "data-edit-accountid='" . (int) ($tr->accountid ?? 0) . "'"
						. "><i class='fa fa-edit decided-action-icon'></i></a>";
				} else {
					$editHtml = "<a class='decided-action-link mx-2' href='javascript:void(0)' title='Edit' "
						. "data-bs-toggle='modal' data-bs-target='#cashEditModal' "
						. "data-edit-source='" . htmlspecialchars((string) $tr->source, ENT_QUOTES) . "' "
						. "data-edit-id='" . (int) $tr->id . "' "
						. "data-edit-date='" . htmlspecialchars(date('Y-m-d', strtotime((string) $tr->date)), ENT_QUOTES) . "' "
						. "data-edit-particulars='" . htmlspecialchars((string) $rawParticulars, ENT_QUOTES) . "' "
						. "data-edit-amount='" . htmlspecialchars((string) $amount, ENT_QUOTES) . "' "
						. "data-edit-opex-or-capex='" . htmlspecialchars((string) ($tr->opex_or_capex ?? ''), ENT_QUOTES) . "'"
						. "><i class='fa fa-edit decided-action-icon'></i></a>";
				}
			} else {
				$editHtml = "<a class='decided-action-link mx-2' href='javascript:void(0)' title='Edit' onclick=\"cash_admin_only_alert('Only admin can edit'); return false;\"><i class='fa fa-edit decided-action-icon'></i></a>";
			}
		}
		$delHtml = "";
		if (uid() == 1) {
			$delHtml .= "<a class='protected-link' href='?d=$d&t=$t&del=$tr->source&id=$tr->id' target='_blank'><i class='fa fa-trash'></i></a>";
		} else {
			$delHtml .= "<a class='protected-link' href='javascript:void(0)' onclick=\"cash_admin_only_alert('Only admin can delete'); return false;\"><i class='fa fa-trash'></i></a>";
		}
		if ($isExpenseRow) {
			$delHtml .= "<a class='px-3 decided-action-link' title='Upload file' href='javascript:void(0)' onclick='openUploadModal($tr->id)'><i class='fa fa-file decided-action-icon'></i></a>";
			$delHtml .= $viewFileActionHtml;
		}
		$delHtml = $editHtml . $delHtml;

		print "<tr class='$tr->source'>";
		print "<td>$i</td>";
		print "<td title='" . htmlspecialchars((string) $tr->entry_time . " > " . (string) $tr->status, ENT_QUOTES) . "'>" . date('Y-m-d', strtotime((string) $tr->date)) . "</td>";
		print "<td class='particulars pre-wrap'>{$particularsHtml}</td>";
		print "<td><small>" . htmlspecialchars((string) $userName, ENT_QUOTES) . "</small></td>";
		print "<td>" . htmlspecialchars((string) $typeText, ENT_QUOTES) . "</td>";
		print "<td class='right'>" . ($cashIn > 0 ? nf($cashIn) : '') . "</td>";
		// Cash Out with clickable link for expense entries (exclude salary)
		$hasSalary = stripos((string) $tr->particulars, 'salary') !== false;
		if ($cashOut > 0 && $isExpenseRow && $detailLink !== "#" && !$hasSalary) {
			print "<td class='right'><a href='" . htmlspecialchars($detailLink, ENT_QUOTES) . "'>" . nf($cashOut) . "</a></td>";
		} else {
			print "<td class='right'>" . ($cashOut > 0 ? nf($cashOut) : '') . "</td>";
		}
		print "<td class='right'>" . nf($cashBalance) . "</td>";
		print "<td class='center'>" . ($managerStatusHtml !== '' ? $managerStatusHtml : '') . "</td>";
		print "<td class='right'>" . ($bankIn > 0 ? nf($bankIn) : '') . "</td>";
		// Bank Out with clickable link for expense bank entries
		$isExpenseBankRow = (string) $tr->source === 'expense_account_entry_bank';
		$bankDetailLink = "#";
		if ($isExpenseBankRow) {
			$expenseEntry = R::load('expense_account_entry', $tr->id);
			if ($expenseEntry->entry_id > 0) {
				// Check if it's a contractor payment
				if ($expenseEntry->entry_type === 'contractor - Payment') {
					$payment = R::load('payment', $expenseEntry->entry_id);
					if (isset($payment->contractor_id) && $payment->contractor_id > 0) {
						$bankDetailLink = "/store/contractor/details/" . (int) $payment->contractor_id;
					}
				}
				// Check if it's a contractor invoice
				elseif ($expenseEntry->entry_type === 'contractor - Invoice') {
					$payment = R::load('payment', $expenseEntry->entry_id);
					if (isset($payment->contractor_id) && $payment->contractor_id > 0) {
						$bankDetailLink = "/store/contractor/details/" . (int) $payment->contractor_id;
					}
				}
				// Check if it's a supplier payment (has purchase record)
				else {
					$purchase = R::load('purchase', $expenseEntry->entry_id);
					if (isset($purchase->supplier_id) && $purchase->supplier_id > 0) {
						$bankDetailLink = "/store/supplier/details/" . (int) $purchase->supplier_id;
					}
					// Fallback to material link if no supplier
					elseif ($purchase->main_material_id > 0) {
						$bankDetailLink = "/store/materials/details/?id=" . (int) $purchase->main_material_id;
					}
				}
			}
		}
		$hasSalaryBank = stripos((string) $tr->particulars, 'salary') !== false;
		if ($bankOut > 0 && $isExpenseBankRow && $bankDetailLink !== "#" && !$hasSalaryBank) {
			print "<td class='right'><a href='" . htmlspecialchars($bankDetailLink, ENT_QUOTES) . "'>" . nf($bankOut) . "</a></td>";
		} else {
			print "<td class='right'>" . ($bankOut > 0 ? nf($bankOut) : '') . "</td>";
		}
		print "<td class='right'>" . nf($bankBalance) . "</td>";
		print "<td class='center'>" . cash_render_status_button($tr->status, $tr->source, $tr->id, $canApproveStatus) . "</td>";
		print "<td>{$delHtml}</td>";

		print "</tr>";

		if ($isExpenseRow) {
			$managers = R::find('expense_petty_cash_manager', 'expense_id = ?', [(int) $tr->id]);
			print "<tr class='accordion-row' id='accordion-$tr->id' style='display:none'><td colspan='14'>";
			if ($managers) {
				print "<ul>";
				foreach ($managers as $manager) {
					$managerId = (int) ($manager->petty_cash_manager_id ?? 0);
					$isManagerAssigned = $managerId > 0;
					$managerName = '';
					if ($isManagerAssigned) {
						$pcm = R::load('petty_cash_managers', $managerId);
						$managerName = trim((string) ($pcm->name ?? ''));
					}
					$noteText = trim((string) ($manager->note ?? ''));
					$hasUploadedFile = isset($manager->file_path) && trim((string) $manager->file_path) !== '';
					if (!$isManagerAssigned && $noteText === '' && !$hasUploadedFile) {
						continue;
					}
					$noteDeleteHtml = '';
					if ($canManageManagerNotes && $isManagerAssigned) {
						$noteDeleteHtml = " <a class='cash-inline-delete protected-link' title='Delete note' href='?d=$d&t=$t&del_petty_note={$manager->id}' onclick=\"return confirm('Delete this decided person note?');\"><i class='fa fa-times'></i></a>";
					}
					$fileDeleteHtml = '';
					if ($canManageManagerNotes && $hasUploadedFile) {
						$fileDeleteHtml = " <a class='cash-inline-delete protected-link' title='Delete uploaded file' href='?d=$d&t=$t&del_petty_file={$manager->id}' onclick=\"return confirm('Delete this uploaded file?');\"><i class='fa fa-times'></i></a>";
					}
					if ($isManagerAssigned) {
						$displayName = $managerName !== '' ? $managerName : 'Unknown Manager';
						print "<li><strong>" . htmlspecialchars($displayName) . "</strong>: " . htmlspecialchars($noteText) . $noteDeleteHtml . $fileDeleteHtml . "</li>";
					} else {
						$uploadedFileName = trim((string) ($manager->file_name ?? ''));
						$uploadText = $noteText !== '' ? $noteText : ($uploadedFileName !== '' ? $uploadedFileName : 'File uploaded');
						print "<li><strong>Uploaded file</strong>: " . htmlspecialchars($uploadText) . $fileDeleteHtml . "</li>";
					}
				}
				print "</ul>";
			} else {
				print "No managers added.";
			}
			print "</td></tr>";
		}
		$i++;
	}

	print "<tr>"
		. "<th colspan='7' class='text-right'>Closing Balance</th>"
		. "<th class='right'>" . nf($cashBalance) . "</th>"
		. "<th></th>"
		. "<th></th>"
		. "<th></th>"
		. "<th class='right'>" . nf($bankBalance) . "</th>"
		. "<th></th>"
		. "<th></th>"
		. "</tr>";
	print "</table>";
	print "</form>";
	print "<div class='right'><a href='?' class='btn btn-danger'>Cancel</a></div>";

	print "<hr>";

} else {

	$mon = isset($get->mon) ? substr($get->mon, 0, 7) : date("Y-m", time());
	$bt_url = isset($get->add_bt) ? "&add_bt=$get->add_bt" : "";
	print "<br>";
	$date = date("Y-m-d", strtotime("$mon-01"));
	?>
	<div class="row">
		<div class="col-md-4 text-right"><strong style="font-size:3rem;font-weight:700;text-shadow:0 0 5 #000">Petty Cash
				Report</strong></div>
		<div class="col-md-4">
			<?php
			print "<span style='float:right'>
			<a class='pointer btn btn-default' href='?page=18&company=&mon=" . subMonth(1, $date) . "$bt_url'><i class='fa fa-chevron-left'></i>Prev</a>" . space(3);
			print monthSelector('mon', date("Y-m-d", strtotime($mon . "-01")));
			print space(3) . "<a class='pointer btn btn-default' href='?page=18&company=&mon=" . addMonth(1, $date) . "$bt_url'>Next <i class='fa fa-chevron-right'></i></a></span>";
			print "<br><br>";
			?>
		</div>
		<div class="col-md-4 rht">
			<input class='form-control-fluid search-cars' placeholder="Search..."
				style="height:unset;line-height:3rem;font-size:24px;">
			<a class='pointer' data-bs-toggle='modal' data-bs-target='#modal-customer' style="padding:0 20px;">
				<i class='fa fa-plus'></i>
				<img src='assets/car.jpeg' height="40px">
			</a>
		</div>
	</div>
	<hr>
	<div id='search-result'></div>
	<?php
}

?>

<!-- ══════════════════════════════════════════
	 ADD PETTY CASH MANAGER MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="mpmModal" tabindex="-1" aria-labelledby="mpmModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="mpmModalLabel">Add Petty Cash Manager</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="mpmForm" method="POST">
					<input type="hidden" name="id" id="id">
					<input type="hidden" name="expense_id" id="expense_id">
					<div class="mb-3">
						<label class="form-label">Manager</label>
						<select name="name" id="mpmName" class="form-control" required>
							<option value="">Select a Manager</option>
							<?php
							$managers = R::find("petty_cash_managers", " 1 ORDER BY id ASC ");
							foreach ($managers as $manager): ?>
								<option value="<?= $manager->id ?>"><?= htmlspecialchars($manager->name) ?></option>
							<?php endforeach; ?>
						</select>
						<label class="form-label mt-2">Note</label>
						<textarea name="note" id="note" class="form-control" required></textarea>
					</div>
					<button type="submit" name="save_petty_manager" class="btn btn-primary">Save</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- ══════════════════════════════════════════
	 UPLOAD FILE MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="uploadForm" method="POST" enctype="multipart/form-data">
					<input type="hidden" name="upload_expense_id" id="upload_expense_id">
					<input type="hidden" name="mock_fail" id="upload_mock_fail" value="0">
					<div class="mb-3">
						<label for="upload_file_input" class="form-label">Choose file</label>
						<input type="file" class="form-control" name="file" id="upload_file_input" required>
					</div>
					<button type="submit" class="btn btn-primary" id="upload_submit_btn">Upload</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- OTHER MODALS -->
<div class="modal fade" id="modal-create" role="dialog">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<form method="post" autocomplete="off">
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

<div class="modal fade" id="modal-customer" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<form method="post" autocomplete="off" enctype='multipart/form-data'>
				<div class="modal-header">
					<button type="button" class="close" data-bs-dismiss="modal">&times;</button>
					<h4 class="modal-title alert alert-success">New Customer</h4>
				</div>
				<div class="modal-body">
					<?php
					$object = R::dispense('cw_customer');
					openForm('post', true);
					print "<table align='center'>
					<tr><td colspan='5'><b>" . str("Customer Details") . "</b></td></tr>
					<tr><td>" . str("Number") . "</td><td><input type='text' name='number' value='$object->number' class='form-control required' required /></td><td>" . space(3) . "</td><td>" . str("Phone") . "</td><td><input type='text' name='phone' value='$object->phone' class='form-control' /></td></tr>
					<tr><td>" . str("Brand") . "</td><td>" . sop2("brand", $object->brand, ['optional' => true, 'width' => '']) . "</td><td>" . space(3) . "</td><td>" . str("Model") . "</td><td id='td-model'></td></tr>
					<tr><td>" . str("Branch") . "</td><td>" . sop2("company", $id, ['optional' => true, 'attr' => 'readonly disabled', 'width' => ''], "cw_company") . "</td><td>" . space(3) . "</td><td>" . str("Roadtax") . "</td><td>" . dateSelector("roadtax", $object->roadtax) . "</td></tr>
					<tr><td>" . str("Next Service Date") . "</td><td>" . dateSelector("next_service_date", $object->next_service_date) . "</td><td>" . space(3) . "</td><td>Photo</td><td><input name='photo_file' type='file' class='form-control w240' /></td></tr>
					</table>";
					closeForm();
					?>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade cash" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
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
							<th><textarea class='form-control' id='add_cash_particulars' name='particulars'></textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" id="save_cash" name="save_cash">Save</button>
					<button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade bank" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
				<h4 class="modal-title">Bank to Petty Cash</h4>
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
							<th><input type='number' class='form-control' required name='amount' id="bd-amount"
									onkeyup="setBDPart()" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th><textarea class='form-control' id='bd-particulars'
									name='particulars'>Taka Bank Account theke exchcange kore to Petty Cash a neoya hoyese</textarea>
							</th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="save_bank">Save</button>
					<button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade withdraw" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
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
							<th><input type='number' class='form-control' onkeyup="setCWPart()" required name='amount'
									id="cw-amount" min="1"></th>
						</tr>
						<tr>
							<th>Particulars</th>
							<th><textarea class='form-control' id='withdraw_particulars' required
									name='particulars'>Rm : Cash Withdrawal for</textarea></th>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" type="submit" name="withdraw">Save</button>
					<button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="cashEditModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post">
				<div class="modal-header">
					<h5 class="modal-title">Edit Entry</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="cash_edit_source" id="cash_edit_source">
					<input type="hidden" name="cash_edit_id" id="cash_edit_id">
					<div class="mb-3">
						<label class="form-label">Date</label>
						<input type="date" class="form-control" name="cash_edit_date" id="cash_edit_date" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Amount</label>
						<input type="number" step="0.01" min="0" class="form-control" name="cash_edit_amount"
							id="cash_edit_amount" required>
					</div>
					<div class="mb-3" id="cash_edit_opex_wrapper" style="display:none">
						<label class="form-label">OPEX/CAPEX</label>
						<select class="form-control" name="cash_edit_opex_or_capex" id="cash_edit_opex_or_capex">
							<option value="">--SELECT--</option>
							<option value="Capex">Capex</option>
							<option value="Opex">Opex</option>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Particulars</label>
						<textarea class="form-control" rows="3" name="cash_edit_particulars" id="cash_edit_particulars"
							required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary" name="cash_edit_save">Save</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="cashExpenseEditModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<form method="post">
				<div class="modal-header">
					<h5 class="modal-title">Edit Expense Entry</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="cash_expense_edit_source" id="cash_expense_edit_source">
					<input type="hidden" name="cash_expense_edit_id" id="cash_expense_edit_id">
					<div class="mb-3">
						<label class="form-label">Account</label>
						<select name="cash_expense_accountid" id="cash_expense_accountid"
							class="form-control selectpicker" data-live-search="true" required>
							<option value="">--SELECT--</option>
							<?php echo getAccountsWithChild(''); ?>
						</select>
					</div>
					<div class="row">
						<div class="col-md-6 mb-3">
							<label class="form-label">Amount</label>
							<input type="number" step="0.01" min="0" class="form-control" name="cash_expense_amount"
								id="cash_expense_amount" required>
						</div>
						<div class="col-md-6 mb-3">
							<label class="form-label">Expense Date</label>
							<input type="date" class="form-control" name="cash_expense_expense_date"
								id="cash_expense_expense_date" required>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">OPEX/CAPEX</label>
						<select class="form-control" name="cash_expense_opex_or_capex" id="cash_expense_opex_or_capex">
							<option value="">--SELECT--</option>
							<option value="Capex">Capex</option>
							<option value="Opex">Opex</option>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Particulars</label>
						<textarea class="form-control" rows="4" name="cash_expense_particulars"
							id="cash_expense_particulars" required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary" name="cash_expense_edit_save">Save</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
	var currentD = '<?php echo $d; ?>';
	var currentT = '<?php echo $t; ?>';

	function cash_admin_only_alert(message) {
		var msg = message || 'Only admin can delete';
		if (window.Swal && typeof Swal.fire === 'function') {
			Swal.fire({ icon: 'warning', text: msg });
			return;
		}
		alert(msg);
	}

	const cashEditModal = document.getElementById('cashEditModal');
	if (cashEditModal) {
		cashEditModal.addEventListener('show.bs.modal', function (event) {
			const button = event.relatedTarget;
			if (!button) return;

			const src = button.getAttribute('data-edit-source') || '';
			const id = button.getAttribute('data-edit-id') || '';
			const dateVal = button.getAttribute('data-edit-date') || '';
			const parts = button.getAttribute('data-edit-particulars') || '';
			const amt = button.getAttribute('data-edit-amount') || '';
			const oc = button.getAttribute('data-edit-opex-or-capex') || '';

			const elSource = document.getElementById('cash_edit_source');
			const elId = document.getElementById('cash_edit_id');
			const elDate = document.getElementById('cash_edit_date');
			const elParts = document.getElementById('cash_edit_particulars');
			const elAmt = document.getElementById('cash_edit_amount');
			const elOpexWrap = document.getElementById('cash_edit_opex_wrapper');
			const elOpex = document.getElementById('cash_edit_opex_or_capex');

			if (elSource) elSource.value = src;
			if (elId) elId.value = id;
			if (elDate) elDate.value = dateVal;
			if (elParts) elParts.value = parts;
			if (elAmt) elAmt.value = amt;

			if (elOpexWrap && elOpex) {
				const showOpex = (oc !== '' && oc !== null);
				elOpexWrap.style.display = showOpex ? '' : 'none';
				elOpex.value = showOpex ? oc : '';
			}
		});
	}

	const cashExpenseEditModal = document.getElementById('cashExpenseEditModal');
	if (cashExpenseEditModal) {
		cashExpenseEditModal.addEventListener('show.bs.modal', function (event) {
			const button = event.relatedTarget;
			if (!button) return;

			const src = button.getAttribute('data-edit-source') || '';
			const id = button.getAttribute('data-edit-id') || '';
			const dateVal = button.getAttribute('data-edit-date') || '';
			const parts = button.getAttribute('data-edit-particulars') || '';
			const amt = button.getAttribute('data-edit-amount') || '';
			const oc = button.getAttribute('data-edit-opex-or-capex') || '';
			const accountid = button.getAttribute('data-edit-accountid') || '';

			const elSource = document.getElementById('cash_expense_edit_source');
			const elId = document.getElementById('cash_expense_edit_id');
			const elDate = document.getElementById('cash_expense_expense_date');
			const elParts = document.getElementById('cash_expense_particulars');
			const elAmt = document.getElementById('cash_expense_amount');
			const elOpex = document.getElementById('cash_expense_opex_or_capex');
			const elAccount = document.getElementById('cash_expense_accountid');

			if (elSource) elSource.value = src;
			if (elId) elId.value = id;
			if (elDate) elDate.value = dateVal;
			if (elParts) elParts.value = parts;
			if (elAmt) elAmt.value = amt;
			if (elOpex) elOpex.value = oc;
			if (elAccount) elAccount.value = accountid;

			if (window.jQuery && typeof jQuery.fn.selectpicker === 'function') {
				jQuery(elAccount).selectpicker('refresh');
			}
		});
	}

	// ── Upload modal opener ──────────────────────────────────────
	function openUploadModal(expenseId) {
		document.getElementById('upload_expense_id').value = expenseId;
		document.getElementById('upload_mock_fail').value = (new URLSearchParams(window.location.search).get('mock_fail') === '1') ? '1' : '0';
		// reset file input without losing the hidden field value
		var fileInput = document.getElementById('upload_file_input');
		if (fileInput) fileInput.value = '';
		$('#uploadModal').modal('show');
	}

	$(document).ready(function () {
		$('#uploadForm').on('submit', function (e) {
			e.preventDefault();

			var expenseId = Number($('#upload_expense_id').val() || 0);
			var fileInput = document.getElementById('upload_file_input');
			if (expenseId <= 0) {
				Swal.fire({ icon: 'error', title: 'Upload Failed', text: 'Invalid upload request (expense ID missing)' });
				return;
			}
			if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
				Swal.fire({ icon: 'error', title: 'Upload Failed', text: 'No file was selected' });
				return;
			}

			var submitBtn = document.getElementById('upload_submit_btn');
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.innerText = 'Uploading...';
			}

			$.ajax({
				url: '/store/ajax/petty_cash_upload.php',
				type: 'POST',
				data: new FormData(document.getElementById('uploadForm')),
				processData: false,
				contentType: false,
				dataType: 'json'
			}).done(function (response) {
				var ok = response && response.success === true;
				var msg = (response && response.message) ? response.message : (ok ? 'File uploaded successfully!' : 'Upload failed.');
				if (ok) {
					$('#uploadModal').modal('hide');
					Swal.fire({ icon: 'success', title: 'Success', text: msg }).then(function () {
						window.location.href = '?d=' + encodeURIComponent(currentD) + '&t=' + encodeURIComponent(currentT);
					});
				} else {
					Swal.fire({ icon: 'error', title: 'Upload Failed', text: msg });
				}
			}).fail(function (xhr) {
				var msg = 'Upload failed.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
					msg = xhr.responseJSON.message;
				} else if (xhr && xhr.responseText) {
					try {
						var parsed = JSON.parse(xhr.responseText);
						if (parsed && parsed.message) {
							msg = parsed.message;
						}
					} catch (err) { }
				}
				Swal.fire({ icon: 'error', title: 'Upload Failed', text: msg });
			}).always(function () {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerText = 'Upload';
				}
			});
		});
	});

	// ── Add Manager modal opener ─────────────────────────────────
	function openAddModal(expenseId) {
		document.getElementById('expense_id').value = expenseId;
		document.getElementById('id').value = '';
		document.getElementById('mpmName').value = '';
		document.getElementById('note').value = '';
		$('#mpmModal').modal('show');
	}

	// ── Accordion toggle ─────────────────────────────────────────
	function toggleAccordion(id) {
		var row = document.getElementById('accordion-' + id);
		if (!row) return;
		row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
	}

	// ── Cash / Bank modal helpers ────────────────────────────────
	function setCash() { $(".cash").find('.modal-title').text('Add Cash'); $("#save_cash").attr('name', 'save_cash'); }
	function setBank() { $(".cash").find('.modal-title').text('Add Bank'); $("#save_cash").attr('name', 'add_bank'); }
	function setACPart() {
		var isCash = $(".cash").find(".modal-title").text() === "Add Cash";
		$("#add_cash_particulars").val($("#amount").val() + (isCash ? " Taka petty cash a joma kora hoyese" : " Taka bank a transfer kora hoyese"));
	}
	function setCWPart() { $("#withdraw_particulars").val("Rm : " + $("#cw-amount").val() + " Cash Withdrawal for"); }
	function setBDPart() { $("#bd-particulars").val($("#bd-amount").val() + " Taka Bank Account theke exchcange kore to Petty Cash a neoya hoyese"); }

	// ── Car search ───────────────────────────────────────────────
	$(".search-cars").keyup(function () {
		var key = $(this).val();
		if (key.length > 1)
			$.post("/app/ajax/cars.php", { key: key, company: <?php print 1; ?> }, function (data) {
				$("#search-result").html(data);
			});
	});

	$("select.brand").change(getModels);
	getModels();
	function getModels() {
		$.post('/app/ajax/model.php', { name: $("select.brand option:selected").val() }, function (data) {
			$("#td-model").html(data);
		});
	}

	// ── Approval trigger ─────────────────────────────────────────
	var currentApprovalSource = '';
	var currentApprovalId = '';

	$(document).on('click', '.approval-trigger', function (e) {
		e.preventDefault();
		var canApprove = Number($(this).data('can-approve'));
		if (isNaN(canApprove)) canApprove = <?php echo $canApproveStatus ? '1' : '0'; ?>;

		if (canApprove !== 1) {
			Swal.fire({ icon: 'warning', text: 'Only admin can change approval status' });
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
					Swal.fire({ icon: 'warning', text: result.message || 'Only admin can change approval status' });
					return;
				}
				window.location.href = '?d=' + currentD + '&t=' + currentT;
			});
		};

		Swal.fire({
			icon: 'question',
			title: 'Approve this entry?',
			showCancelButton: true,
			confirmButtonText: 'Yes, Approve'
		}).then(function (result) {
			if (result.isConfirmed) doApprove();
		});
	});
</script>