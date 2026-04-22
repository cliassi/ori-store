<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Petty Cash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#72ccd8',
                        primaryDark: '#5fbac6'
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <!-- Material Symbols Outlined -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Lato', sans-serif; }
        .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
        .icon-green{color:#47773f !important}
        .material-symbols-outlined{color:#47773f !important;font-variation-settings:'FILL' 0, 'wght' 400, 'opsz' 24}
        .highlight{font-size: 18px; color: #337ab7;}
        .highlight2, .highlight2 a{font-size: 18px; color: #000;}
        .bd_handover .particulars{color: #5cb85c; font-weight: 700;}
        .checked-pending,.checked-banned{height: 20px; opacity: .5; padding-left: 20px; filter: grayscale(1); cursor: pointer;}
        .checked-pending:hover,.checked-banned:hover{filter: grayscale(.2); transform: scale(1.1);}
        .checked{height: 20px; padding-left: 20px;}
        tr.expense_account_entry td{border: solid 1px lightgreen !important;}
        .large{font-size: 14px; text-transform: uppercase; font-weight: 700;}
        th{text-align: center;}
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {-webkit-appearance: none; margin: 0;}
        input[type=number] {-moz-appearance: textfield; appearance: textfield;}
        .modal.custom-fallback{
            position: fixed; inset: 0; width: 100%; height: 100%; display: none;
            align-items: center; justify-content: center; background: rgba(0,0,0,0.6); z-index: 9999;
        }
        .modal.custom-fallback.show{ display: flex; }
        .modal.custom-fallback .modal-dialog{margin: 0; width: 92%; max-width: 420px;}
        .modal.custom-fallback .modal-content{ border-radius: 8px; overflow: hidden; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.35);}
        .modal.custom-fallback .modal-header, .modal.custom-fallback .modal-body, .modal.custom-fallback .modal-footer{background: #ffffff;}
        .modal.custom-fallback .modal-body{ padding: 16px; }
        body.modal-open{ overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from cash.php
$get->petty_cash_details = 1;
$get->cw=1;
if(isset($get->company))
$id = $get->company;

if(isset($post->save_carwash)){
	$carwash = R::dispense("cw_sites");
	$carwash->name = $post->name;
	$carwash->entry_by = uid();
	R::store($carwash);
}

if(isset($post->save)){
	$object = R::dispense("cw_customer");
	$function = 'add';
	$post->company = $id;
	require_once("model/cw_customer.php");
}

if(isset($post->save_cash)){
	$deposit = R::dispense("cw_cash");
	$deposit->date = isset($post->date)?$post->date:today();
	$deposit->particulars = $post->particulars;
	$deposit->amount = $post->amount;
	$deposit->company = $get->cw;
	$deposit->entry_by = uid();
	$deposit->entry_time = now();
	R::store($deposit);
	redir("?");
}

if(isset($post->save_bank)){
	$cw_cash = R::dispense("cw_cash");
	$cw_cash->date = isset($post->date2)?$post->date2:today();
	$cw_cash->particulars = $post->particulars;
	$cw_cash->amount = 0 - $post->amount;
	$cw_cash->company = $get->cw;
	$cw_cash->entry_by = uid();
	$cw_cash->entry_time = now();
	R::store($cw_cash);

	$cw_bank = R::dispense("cw_bank");
	$cw_bank->date = isset($post->date2)?$post->date2:today();
	$cw_bank->particulars = $post->particulars;
	$cw_bank->amount = $post->amount;
	$cw_bank->company = $get->cw;
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
	$cw_bank->entry_by = uid();
	$cw_bank->entry_time = now();
	R::store($cw_bank);
	redir("?");
}

if(isset($post->withdraw)){
	$withdraw = R::dispense("cw_cash_withdraw");
	$withdraw->particulars = $post->particulars;
	$withdraw->date = isset($post->date2)?(nn($post->date2)?$post->date2:today()):today();
	$withdraw->amount = 0 - $post->amount;
	$withdraw->company = $get->cw;
	$withdraw->entry_by = uid();
	$withdraw->entry_time = now();
	R::store($withdraw);
	redir("?");
}

$d = isset($get->d)?$get->d:subDay(5);
$t = isset($get->t)?$get->t:today();

// Approval logic
if(uid()==1 && isset($get->approve) && isset($get->id)){
	if(in_array($get->approve, ['payment_bank', 'payment_cash'])){
		$get->approve = 'payment';
	}
	if(in_array($get->approve, ['expense_account_entry_bank', 'expense_account_entry_cash', 'account_entry_cash'])){
		$get->approve = 'expense_account_entry';
	}
	$object = R::load($get->approve, $get->id);
	$object->status = 'Approved';
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
		R::store($object);
	}
	redir("?d=$d&t=$t");
}	

// Token-based operations
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
?>

<?php if(isset($get->cw)): ?>
<?php
$company = R::load("cw_company", $get->cw); 
$d = isset($get->d)?$get->d:firstDay();
$t = isset($get->t)?$get->t:today();

// Calculate summary data
$collection = getSum("cw_payment", "amount", "date<'$d'");
$handover_cash = getSum("bd_handover", "amount", "amount>0 AND date<'$d'");
$handover_bank = getSum("bd_handover", "bank_amount", "bank_amount>0 AND date<'$d'");
$withdraw = getSum("cw_cash_withdraw", "amount", "date<'$d'");
$expense_entry = getSum("expense_account_entry", "amount", "tran_type='Debit' AND company=$company->id AND expense_date<'$d'");

$summary2 = mysqli_fetch_object(select("SELECT 
	(SELECT SUM(IFNULL(amount,0)) FROM `cw_cash` WHERE entry_time<'$d 00:00:00') add_cash, 
	(SELECT SUM(IFNULL(amount,0)) FROM `bd_handover` WHERE created_at<'$d 00:00:00') cash_handover, 
	(SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Cash' AND expense_date<'$d 00:00:00') cash_expense, 
	(SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Cash' AND created_at<'$d 00:00:00') cash_payment, 
	(SELECT IFNULL(SUM(IFNULL(amount,0)),0) FROM `cw_cash` WHERE amount>0  AND entry_time<'$d 00:00:00') cash, 
	(SELECT SUM(IFNULL(amount,0)) FROM `cw_cash_withdraw` WHERE entry_time<'$d 00:00:00') withdraw"));

$total = $summary2->cash_handover + $summary2->add_cash - abs($summary2->withdraw) - $summary2->cash_payment - $summary2->cash_expense;

// Get transaction data
$trans = select("SELECT * FROM (
		SELECT '' se, 'bd_handover' source, id, amount, `date`, created_at entry_time, created_by entry_by, 'Bank & Cash Handover from Daily Collection' particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM bd_handover WHERE (amount>0 OR bank_amount > 0) AND date BETWEEN '$d' AND '$t'
		UNION
		SELECT '' se, 'cw_payment' source, 0 id, SUM(amount) amount, date, entry_time, entry_by, 'Total Bank Collection' particulars, '', '','','', 0 checked FROM cw_payment WHERE  amount>0 AND company=$company->id AND (date BETWEEN '$d' AND '$t') AND particulars NOT LIKE '%cash%' GROUP BY DATE
		UNION
		SELECT ea.breadcrumbs se, 'expense_account_entry' source, e.id, e.amount, e.expense_date `date`, e.entry_time, e.entry_by, CONCAT(IF(e.expense_date IS NULL, '', DATE_FORMAT(e.entry_time, '%e-%b-%Y')), ' ',e.particulars) particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM expense_account_entry e LEFT JOIN  expense_account ea ON e.accountid=ea.id WHERE e.payment_method='Cash' AND e.tran_type='Debit' AND e.company=$company->id AND e.expense_date  BETWEEN '$d 00:00:00' AND '$t 23:59:59'
		UNION
		SELECT ea.breadcrumbs se, 'expense_account_entry_bank' source, e.id, e.amount, e.expense_date `date`, e.entry_time, e.entry_by, CONCAT(IF(e.expense_date IS NULL, '', DATE_FORMAT(e.entry_time, '%e-%b-%Y')), ' ',e.particulars) particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM expense_account_entry e LEFT JOIN  expense_account ea ON e.accountid=ea.id WHERE e.payment_method<>'Cash' AND e.tran_type='Debit' AND e.company=$company->id AND e.expense_date  BETWEEN '$d 00:00:00' AND '$t 23:59:59'
		UNION
		SELECT '' se, 'cw_cash_withdraw' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_cash_withdraw WHERE (date BETWEEN '$d' AND '$t') AND company=$company->id
		UNION
		SELECT '' se, 'cw_cash' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_cash WHERE (date BETWEEN '$d' AND '$t')  AND company=$company->id AND amount>0
		UNION
		SELECT cash_id se, 'cw_bank' source, id, amount, `date`, entry_time, entry_by, particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM cw_bank WHERE (date BETWEEN '$d' AND '$t')  AND company=$company->id
		UNION
		SELECT '' se, 'payment_cash' source, id, amount, `date`, created_at entry_time, created_by entry_by, description particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM payment WHERE (date BETWEEN '$d' AND '$t')  AND payment_method='Cash'
		UNION
		SELECT '' se, 'payment_bank' source, id, amount, `date`, created_at entry_time, created_by entry_by, description particulars, `status`, '' ref, '' done_by, '' done_time, 0 checked FROM payment WHERE (date BETWEEN '$d' AND '$t')  AND payment_method='Bank'
	) t ORDER BY date");
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4"><?php echo $company->name; ?> Petty Cash</h2>
        
        <!-- Date Filter -->
        <form class="mb-4">
            <input type="hidden" name="page" value="petty_cash">
            <input type="hidden" name="cw" value="<?php echo $get->cw; ?>">
            <input type="hidden" name="petty_cash_details" value="">
            <div class="flex gap-2 text-sm">
                <input type="date" name="d" value="<?php echo $d; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
                <span class="text-white">to</span>
                <input type="date" name="t" value="<?php echo $t; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
                <button type="submit" class="bg-white text-primary px-3 py-1 rounded font-semibold">Filter</button>
            </div>
        </form>
        
        <!-- Action Buttons -->
        <div class="flex gap-2 text-sm">
            <?php if(isUserIn(['superadmin','amla','orange', 'parvez'])): ?>
                <button onclick="openModal('withdrawModal')" class="bg-white text-primary px-3 py-1 rounded font-semibold">Withdraw</button>
                <button onclick="openModal('cashModal')" class="bg-white text-primary px-3 py-1 rounded font-semibold">Add Cash</button>
                <button onclick="openModal('bankModal')" class="bg-white text-primary px-3 py-1 rounded font-semibold">To Bank</button>
            <?php endif; ?>
            <a href="/store/expense_account/carwash?company=<?php echo $get->cw; ?>" class="bg-red-600 text-white px-3 py-1 rounded font-semibold">Expense</a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-gray-600">Opening Balance</div>
                <div class="font-bold text-lg"><?php echo nf($total); ?></div>
            </div>
            <div class="text-center">
                <div class="text-gray-600">Bank Balance</div>
                <div class="font-bold text-lg"><?php echo nf(sum('bank')); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="max-w-sm mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <form method="post">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left">Date</th>
                            <th class="px-2 py-2 text-left">Particulars</th>
                            <th class="px-2 py-2 text-right">Cash</th>
                            <th class="px-2 py-2 text-right">Bank</th>
                            <th class="px-2 py-2 text-center">Status</th>
                            <?php if(uid() == 1): ?>
                                <th class="px-2 py-2 text-center">Del</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td colspan="5" class="px-2 py-2 font-semibold bg-gray-100">Opening Balance</td>
                            <td class="px-2 py-2 text-right font-semibold"><?php echo nf($total); ?></td>
                        </tr>
                        
                        <?php
                        $i = 1;
                        $userList = userList();
                        $userList[0] = '';
                        while($tr = mysqli_fetch_object($trans)):
                            $r = "<td class='px-2 py-1'>".df($tr->date)."</td>";
                            $r .= "<td class='px-2 py-1 text-xs'>".$tr->particulars."</td>";
                            
                            if($tr->status=='Pending'){
                                $r .= "<td class='px-2 py-1'><input type='checkbox' name='approvem[]' value='{$tr->source}-{$tr->id}' class='w-3 h-3'></td>";
                            } else{
                                $r .= "<td class='px-2 py-1'></td>";
                            }
                            
                            // Handle different transaction types
                            if($tr->source == 'bd_handover'){
                                $handover = R::load("bd_handover", $tr->id);
                                sum('bank', $handover->bank_amount);
                                sum('banko', $handover->bank_amount);
                                $r .= "<td class='px-2 py-1 text-right'>".nf($tr->amount)."</td>";
                                $r .= "<td class='px-2 py-1 text-right'>".nf($handover->bank_amount)."</td>";
                                $r .= "<td class='px-2 py-1 text-center'>".($tr->status=='Pending'?"<span class='bg-red-100 text-red-800 px-1 py-0.5 rounded text-xs'>Pending</span>":"<span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Approved</span>")."</td>";
                                $total += $tr->amount;
                            } elseif($tr->source == 'cw_cash'){
                                sum('cash', $tr->amount);
                                if($tr->amount>0) {
                                    $r .= "<td class='px-2 py-1 text-right'>".nf($tr->amount)."</td>";
                                    $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                    $r .= "<td class='px-2 py-1 text-center'>".($tr->status=='Pending'?"<span class='bg-red-100 text-red-800 px-1 py-0.5 rounded text-xs'>Pending</span>":"<span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Approved</span>")."</td>";
                                    $total += $tr->amount;
                                } else {
                                    $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                    $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                    $r .= "<td class='px-2 py-1 text-center'>-</td>";
                                }
                            } elseif($tr->source == 'cw_bank'){
                                $r .= "<td class='px-2 py-1 text-right'>".nf($tr->amount)."</td>";
                                if($tr->se){
                                    $r .= "<td class='px-2 py-1 text-right'>".nf(0-$tr->amount)."</td>";
                                    sum('cw_cash', 0 -$tr->amount);
                                    $total += 0 - $tr->amount;
                                } else{
                                    $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                }
                                $r .= "<td class='px-2 py-1 text-center'>".($tr->status=='Pending'?"<span class='bg-red-100 text-red-800 px-1 py-0.5 rounded text-xs'>Pending</span>":"<span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Approved</span>")."</td>";
                                sum('bank', $tr->amount);
                                sum('banko', $tr->amount);
                            } elseif($tr->source == 'expense_account_entry'){
                                $r .= "<td class='px-2 py-1 text-right text-red-600'>".nf($tr->amount)."</td>";
                                $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                $r .= "<td class='px-2 py-1 text-center'>".($tr->status=='Pending'?"<span class='bg-red-100 text-red-800 px-1 py-0.5 rounded text-xs'>Pending</span>":"<span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Approved</span>")."</td>";
                                $total -= $tr->amount;
                            } elseif($tr->source == 'cw_cash_withdraw'){
                                $r .= "<td class='px-2 py-1 text-right'>".nf($tr->amount)."</td>";
                                $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                $r .= "<td class='px-2 py-1 text-center'>".($tr->status=='Pending'?"<span class='bg-red-100 text-red-800 px-1 py-0.5 rounded text-xs'>Pending</span>":"<span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Approved</span>")."</td>";
                                $total -= abs($tr->amount);
                            } else {
                                $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                $r .= "<td class='px-2 py-1 text-right'>-</td>";
                                $r .= "<td class='px-2 py-1 text-center'>-</td>";
                            }
                            
                            if(uid() == 1){
                                $r .= "<td class='px-2 py-1 text-center'><a href='?d=$d&t=$t&del=$tr->source&id=$tr->id' class='text-red-600'>×</a></td>";
                            }
                            
                            echo "<tr class='border-b'>$r</tr>";
                            $i++;
                        endwhile;
                        ?>
                        
                        <tr class="bg-gray-100 font-semibold">
                            <td colspan="2" class="px-2 py-2">Balance</td>
                            <td class="px-2 py-2 text-right"><?php echo nf($total); ?></td>
                            <td class="px-2 py-2 text-right"><?php echo nf(sum('bank')); ?></td>
                            <td colspan="2" class="px-2 py-2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if(uid() == 1): ?>
                <div class="p-4 text-center">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded font-semibold">Approve Selected</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Default view when no company selected -->
<div class="max-w-sm mx-auto px-4 py-8">
    <div class="text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Petty Cash Report</h2>
        <p class="text-gray-600">Please select a company to view petty cash details.</p>
    </div>
</div>
<?php endif; ?>

<!-- Modals -->
<!-- Add Cash Modal -->
<div class="modal custom-fallback" id="cashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Cash</h5>
                <button type="button" class="btn-close" onclick="closeModal('cashModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="date" value="<?php echo today(); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" id="amount" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onkeyup="setACPart()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Particulars</label>
                            <textarea name="particulars" id="add_cash_particulars" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('cashModal')">Close</button>
                    <button type="submit" name="save_cash" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bank Deposit Modal -->
<div class="modal custom-fallback" id="bankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bank Deposit</h5>
                <button type="button" class="btn-close" onclick="closeModal('bankModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="date2" value="<?php echo today(); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" id="bd-amount" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onkeyup="setBDPart()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Particulars</label>
                            <textarea name="particulars" id="bd-particulars" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3">Petty Cash Exchange to Bank Account RM </textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('bankModal')">Close</button>
                    <button type="submit" name="save_bank" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cash Withdraw Modal -->
<div class="modal custom-fallback" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cash Withdraw</h5>
                <button type="button" class="btn-close" onclick="closeModal('withdrawModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <input type="hidden" name="account" value="46">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="date2" value="<?php echo today(); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" id="cw-amount" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onkeyup="setCWPart()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Particulars</label>
                            <textarea name="particulars" id="withdraw_particulars" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('withdrawModal')">Close</button>
                    <button type="submit" name="withdraw" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

// Auto-fill functions
function setACPart(){
    document.getElementById("add_cash_particulars").value = "RM : " + document.getElementById("amount").value + " Cash Investment for";
}

function setCWPart(){
    document.getElementById("withdraw_particulars").value = "RM : " + document.getElementById("cw-amount").value + " Cash Withdrawal for";
}

function setBDPart(){
    document.getElementById("bd-particulars").value = "Petty Cash Exchange to Bank Account RM " + document.getElementById("bd-amount").value;
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});
</script>

</body>
</html>
