<style type="text/css">
  td {
    text-align: left;
  }

  .modal th {
    background-color: rgba(120, 250, 70, .1) !important;
  }

  .modal th span {
    font-weight: 700;
  }
</style>
<?php

$hotel_start_date = '2023-01-18 23:59:59';

$d = isset($get->d) ? $get->d : today();
$t = isset($get->t) ? $get->t : today();

if (!isUserIn(['apple', 'superadmin', 'melon', 'lemon', 'orange', 'mango', 'berry', 'Olive'])) {
  // exit;
}

if (isset($post->collect_cash)) {
  if (isUserIn(["superadmin", "lemon", "ebrahim"])) {
    $handover = R::findOne("bd_handover", "branch_id=? AND date=?", [$branch_id, date("Y-m-d", strtotime($post->date))]);
  } else {
    $handover = R::findOne("bd_handover", "branch_id=? AND date=?", [$branch_id, date("Y-m-d")]);
  }
  if (!$handover) {
    $handover = R::dispense("bd_handover");
    $handover->date = isset($post->date) ? date("Y-m-d", strtotime($post->date)) : today();
    $handover->account = 1;
    $handover->branch_id = $branch_id;
    if (isset($post->amount))
      $handover->amount = $post->amount;
    if (isset($post->bank_amount))
      $handover->bank_amount = $post->bank_amount;
    $handover->created_by = uid();
    $handover->created_at = now();
    R::store($handover);

    // $petty_cash_report = R::dispense("petty_cash_report");
    // $petty_cash_report->date = today();
    // $petty_cash_report->from_date = $d;
    // $petty_cash_report->to_date = $t;
    // $petty_cash_report->report = pettyCashReport();
    // $petty_cash_report->created_by = uid();
    // $petty_cash_report->created_at = now();
    // $petty_cash_report->handover_id = $handover->id;

    // R::store($petty_cash_report);


    alert("Added");
    redir("?");
  } else {
    $handover->date = isset($post->date) ? date("Y-m-d", strtotime($post->date)) : today();
    $handover->account = 1;
    if (isset($post->amount))
      $handover->amount = $post->amount;
    if (isset($post->bank_amount))
      $handover->bank_amount = $post->bank_amount;
    $handover->created_by = uid();
    $handover->created_at = now();
    R::store($handover);

    // $petty_cash_report = R::dispense("petty_cash_report");
    // $petty_cash_report->date = today();
    // $petty_cash_report->from_date = $d;
    // $petty_cash_report->to_date = $t;
    // $petty_cash_report->report = pettyCashReport();
    // $petty_cash_report->created_by = uid();
    // $petty_cash_report->created_at = now();
    // $petty_cash_report->handover_id = $handover->id;

    // R::store($petty_cash_report);


    alert("Updated");
    redir("?");

    // print "<div class='alert alert-danger'>Already submitted for today. Thank you</div>";
  }
}

if (isset($post->update_outsource_expense)) {
  $expense = R::load("expenditure", $post->id);
  $expense->amount = $post->amount;
  $expense->modify_by = uid();
  $expense->modify_time = now();
  R::store($expense);
}

if (isset($post->delete_outsource_expense)) {
  $expense = R::load("expenditure", $post->id);
  R::trash($expense);
}

if (isset($post->save_outsource_expense) || isset($post->save_online_expense) || isset($post->save_office_expense) || isset($post->save_visa_expense) || isset($post->save_salary_expense)) {
  $expense = R::dispense("expenditure");
  if (isset($post->company))
    $expense->company = $post->company;
  if (isset($post->expense_sector))
    $expense->expense_sector = $post->expense_sector;

  $expense->particulars = $post->particulars;
  $expense->date = $post->date;
  $expense->amount = $post->amount + 0;
  $expense->category_id = $post->category_id;
  // if(isset($post->save_office_expense)){
  //   $expense->category = 'Office';
  // } elseif(isset($post->save_salary_expense)){
  //   $expense->category = 'Salary';
  // } else{
  //   $expense->category = 'Visa';   
  // }
  if (isset($post->save_outsource_expense)) {
    $expense->payment_method = 'Outsource';
    $expense->payment_method = $post->payment_method;
  } else {
    $expense->payment_method = 'Online';
    $expense->payment_method = 'Online';
  }

  if (isset($post->rtk)) {
    $expense->rtk = $post->rtk;
  }

  if (isset($post->worker) && nn($post->worker)) {
    $expense->worker = $post->worker;
  }
  $expense->source = 'Daily Collection';
  $expense->created_by = uid();
  $expense->created_at = now();
  R::store($expense);

  if (isset($_FILES['file']['name'])) {
    $file = upload($_FILES, time(), "uploads/receipts");
    $expense->file = $file;
    R::store($expense);
  }
  alert("Saved!");
  redir("?");
}
// $get->d = $d;
// $get->t = $t;
// $canPickPast = in_array((int) uid(), [1, 47, 51], true);
// if (!$canPickPast) {
//   if (daydiff($d, prevDay()) > 0) {
//     $d = prevDay();
//     $get->d = $d;
//   }
// }
$canPickPast = in_array((int) uid(), [1, 47], true);

if ($canPickPast) {
  $minDate = '';
  $maxDate = '';
} else {
  $minDate = date('Y-m-d', strtotime('-7 days')); // previous 7 days (including today)
  $maxDate = date('Y-m-t');                                           // last day of current month

  // Clamp GET dates so users can't bypass via URL
  if (strtotime($d) < strtotime($minDate)) {
    $d = $minDate;
    $get->d = $d;
  }
  if (strtotime($t) < strtotime($minDate)) {
    $t = $minDate;
    $get->t = $t;
  }
  if (strtotime($d) > strtotime($maxDate)) {
    $d = $maxDate;
    $get->d = $d;
  }
  if (strtotime($t) > strtotime($maxDate)) {
    $t = $maxDate;
    $get->t = $t;
  }

  // Optional (recommended): keep from <= to
  if (strtotime($d) > strtotime($t)) {
    $tmp = $d;
    $d = $t;
    $t = $tmp;
    $get->d = $d;
    $get->t = $t;
  }
}

$com = isset($get->company) ? $get->company : '';
$ec = isset($get->expense_category) ? $get->expense_category : '';

if (!isset($get->collection) && !isset($get->expense) && !isset($get->handover)) {
  $_collection = $_handover = $_expense = true;
} else {
  $_collection = isset($get->collection) ? $get->collection : false;
  $_handover = isset($get->handover) ? $get->handover : false;
  $_expense = isset($get->expense) ? $get->expense : false;
  // $_expense = isset($get->expense) ? $get->expense : (($_collection || $_handover) ? isset($_expense) : true);
}

$pm = isset($get->pm) ? $get->pm : 'Outsource';

openFilterForm("get");
print "<input type='hidden' name='pm' value='$pm'> Collection" . space(2);
print "<input type='checkbox' name='collection' value='1' " . ($_collection ? "checked" : '') . "> Collection" . space(2);
print "<input type='checkbox' name='expense' value='1' " . ($_expense ? "checked" : '') . "> Expense" . space(2);
print "<input type='checkbox' name='handover' value='1' " . ($_handover ? "checked" : '') . "> Handover " . space(8);
print "Date " . dp('d', $d, !$canPickPast ? prevDay() : false) . " - " . dp('t', $t, !$canPickPast ? prevDay() : false);
print "Company " . sop2("company", $com, ["class" => 'w150', "extra" => "ORDER BY seq", 'optional' => true]);
print "Expense Category " . sop2("expense_category", $ec, ["filter" => "hidden=0", "class" => 'w150', "extra" => "ORDER BY name", 'dataField' => "CONCAT(name,' - ', type)", 'optional' => true]);
closeFilterForm();
$companies = toA("company");
$userList = userList();
?>
<table class="table table-bordered">
  <tr>
    <th colspan="7">

      <?php if ($canPickPast || uid() == 21 || isUserIn(['apple', 'melon', 'lemon'])) { ?>
        <!-- <a data-toggle='modal' data-target='.office' class='btn btn-primary'>Office Expense</a> 
      <a data-toggle='modal' data-target='.salary' class='btn btn-warning'>Salary Expense</a> 
      <a data-toggle='modal' data-target='.expense' class='btn btn-danger'>Visa Expense</a> -->
        <!-- <a data-toggle='modal' data-target='.outsource' class='btn btn-danger'>Outsource Expense</a> -->
        <!-- <?php print "<a href='?expense=1&d=$d&t=$t&company=$com&expense_category=$ec&pm=Outsource' class='btn btn-danger'>" . ($pm == "Outsource" ? '<img src="../assets/verified.png" width="22px">' : "") . " Outsource Report</a>"; ?>
      |
      <a data-toggle='modal' data-target='.online' class='btn btn-warning'>Online Expense</a> -->
        <?php //print "<a href='?expense=1&d=$d&t=$t&company=$com&expense_category=$ec&pm=Online' class='btn btn-warning'>".($pm == "Online" ? '<img src="../assets/verified.png" width="22px">': "")." Online Report</a>"; ?>

      <?php } ?>
    </th>
  </tr>
  <tr>
    <th rowspan="2">No.</th>
    <th rowspan="2">Particulars</th>
    <th rowspan="2">Name</th>
    <th rowspan="2">Company</th>
    <th rowspan="2">Entry By</th>
    <th colspan="3" class='center'>Cash</th>
    <th colspan="3" class='center'>Bank</th>
  </tr>
  <tr>
    <th>Credit</th>
    <th>Debit</th>
    <th>Balance</th>
    <th>Credit</th>
    <th>Debit</th>
    <th>Balance</th>
  </tr>

  <?php
  $_trans = [];


  // $opening_result = select("SELECT
  //   (SELECT IFNULL(SUM(amount),0) FROM bd_handover WHERE date < '$d') handover,
  //   (SELECT IFNULL(SUM(amount),0) FROM bd_loan WHERE date < '$d') loan,
  //   (SELECT IFNULL(SUM(amount),0) FROM bd_cash_withdraw WHERE date < '$d') withdraw,
  //   (SELECT IFNULL(SUM(amount),0) FROM expenditure WHERE date < '$d') expenditure,
  //   (SELECT IFNULL(SUM(amount),0) FROM hotel_statement_worker_payment WHERE date > '$hotel_start_date' AND date < '$d' AND particulars LIKE 'Petty Cash theke Taka nise%') hotel,
  //   (SELECT IFNULL(SUM(amount),0) FROM bd_bank_deposit WHERE date < '$d') bank_deposit,
  //   (SELECT IFNULL(SUM(amount),0) FROM bd_cash WHERE date < '$d') bd_cash
  // ");
  // $opening = (object)mysqli_fetch_object($opening_result);
  

  // print "<tr><th colspan='8'>Opening Balance</th><th>".nf($opening->opening)."</th><th colspan='3' class='center'></th></tr>";
  
  function addTran($tran, $particulars, $name, $mobile, $company, $entry, $credit, $debit, $balance, $credit_b, $debit_b, $balance_b)
  {
    return array_push($tran, ["particulars" => $particulars, "name" => $name, "mobile" => $mobile, "company" => $company, "entry" => $entry, "credit" => $credit, "debit" => $debit, "balance" => $balance, "credit_b" => $credit_b, "debit_b" => $debit_b, "balance_b" => $balance_b]);
  }
  $i = 1;
  $total = $credit = $debit = $total_b = $credit_b = $debit_b = 0;
  // $total = $or->opening;
  // if((isset($get->d)?(isset($get->collection)?true:false):true) && !$ec){
  if ($_collection) {
    ensureMysqlColumn('collection', 'payment_date', 'DATE NULL');
    $official_receipts = select("o.*, w.contact name, w.company, w.id wid", "collection o, customer w", "(w.branch_id = $branch_id OR w.branch_id IS NULL) AND o.customer_id=w.id AND o.deleted_by IS NULL AND (o.created_at BETWEEN '$d 00:00:00' AND '$t 23:59:59')");
    while ($official_receipt = mysqli_fetch_object($official_receipts)) {
      $avatar = getName("sys_user", $official_receipt->created_by, 'u_avatar');
      if (file_exists("uploads/user/avatar/$avatar") && nn($avatar)) {
        $avatar = "<img src='$appurl/uploads/user/avatar/$avatar' style='width:27px'>";
      } else {
        $avatar = $userList[$official_receipt->created_by];
      }
      $actualPaymentDate = nn($official_receipt->payment_date) ? $official_receipt->payment_date : $official_receipt->date;
      $plainParticulars = stripslashes($official_receipt->description);
      $plainParticulars = preg_replace('/^\s*\d{1,2}\s+[A-Za-z]{3},\s+\d{4}\s+/', '', $plainParticulars);
      $displayPaymentDate = (nn($actualPaymentDate) && strtotime($actualPaymentDate)) ? date('d M, Y', strtotime($actualPaymentDate)) : '';
      $displayParticulars = trim($displayPaymentDate . " " . $plainParticulars);

      if ($official_receipt->payment_method == 'Cash') {
        addTran($_trans, $displayParticulars, "<a href='/store/customer/statement/$official_receipt->wid' target='_blank'>$official_receipt->name</a>", '', $official_receipt->company, $avatar, $official_receipt->amount, '', '', '', '', '');
      } else {
        addTran($_trans, $displayParticulars, "<a href='/store/customer/statement/$official_receipt->wid' target='_blank'>$official_receipt->name</a>", '', $official_receipt->company, '', '', '', $avatar, $official_receipt->amount, '', '');
      }
      print "<tr title='or'>
          <td>$i</td>
          <td";
      if (strpos($displayParticulars, 'Hotel salary theke permit') !== FALSE) {
        print " style='background:#00ff0033'";
      }
      print ">" . $displayParticulars . "</td>
          <td>$official_receipt->name</td>
          <td><a href='/store/customer/details/$official_receipt->wid'>$official_receipt->company</a></td>
          <td title='$official_receipt->created_by: $official_receipt->created_at' style='text-align: center; vertical-align:middle; padding: 0px'>$avatar</td>";
      if ($official_receipt->payment_method == 'Cash') {
        $total += $official_receipt->amount;
        $credit += $official_receipt->amount;
        print "<td>" . nf($official_receipt->amount) . "</td><td></td><td>" . nf($total) . "</td><td></td><td></td><td></td>";
        sum("cash", $official_receipt->amount);
      } else {
        $total_b += $official_receipt->amount;
        $credit_b += $official_receipt->amount;
        print "<td></td><td></td><td></td><td>" . nf($official_receipt->amount) . "</td><td></td><td>" . nf($total_b) . "</td>";
        sum("bank", $official_receipt->amount);
      }
      $i++;
    }
  }
  ?>
  <!-- //Expense  -->
  <?php
  /*
  if($_expense){      
    // $expenses = R::find("expenditure",  ($com ? "company=$com AND " : "")."payment_method=? AND source=? AND date BETWEEN ? AND ?", ['$pm', 'Daily Collection', $d, $t]);
    // foreach ($expenses as $key => $expense) {
    $expenses = select("e.*, w.name, w.mobile, ec.parent", "expenditure e
        LEFT JOIN expense_category ec ON e.category_id = ec.id 
        LEFT JOIN worker w ON e.worker = w.id",  ($com ? " e.company=$com AND " : "")."e.payment_method='$pm' AND e.source='Daily Collection' ".($ec ? " AND (e.category_id=$get->expense_category OR e.category_id IN (SELECT id FROM expense_category WHERE parent=$get->expense_category)) " : "")." AND e.date BETWEEN '$d' AND '$t'");
    while($expense = mysqli_fetch_object($expenses)){
      print "<tr title='exp'>";
      print "<td>$i</td>";
      // print "<td colspan='3'><a class='lighbox' href='$appurl/uploads/receipts/$expense->file' data-lightbox='roundtrip'>$expense->particulars ($expense->category)</a></td>";
      if($expense->worker){
        print "<td colspan='2'><a href='/store/customer/statement/{$expense->worker}'>{$expense->particulars} ({$expense->category}) - $expense->name/$expense->mobile</a></td>";
      } else{
        print "<td colspan='2'>{$expense->particulars}</td>";
      }
      print "<td nowrap>";
      if(uid() == 1 && $expense->parent == 94) {
        print "<a class='btn btn-primary' data-toggle='modal' data-target='.edit-amount' onclick='setExpense($expense->id)'>Edit</a> <a class='btn btn-danger' data-toggle='modal' data-target='.delete-expenditure' onclick='setExpense2($expense->id)'>Del</a>"; 
      }
      print "</td>";
      print "<td>{$companies[$expense->company]}</td>";
      print "<td>{$userList[$expense->created_by]}</td>";
      print "<td></td>";
      if($expense->payment_method == 'Cash'){
        $total -= $expense->amount;
        $debit += $expense->amount;
        print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
        sum("cash_exp", $expense->amount);
      } else{
        $total_b -= $expense->amount;
        $debit_b += $expense->amount;
        print "<td></td><td></td><td></td><td>".nf($expense->amount)."</td><td>".nf($total_b)."</td>";
        sum("bank_exp", $expense->amount);
      }
      print "</tr>";
      $i++;
    }

    $expense_entries = select("SELECT * FROM `expense_account_entry` WHERE (created_at BETWEEN '$d 00:00:00' AND '$t 23:59:59') AND accountpath <> '/1/55/' AND accountpath LIKE '/1/%'");
    while($expense = mysqli_fetch_object($expense_entries)){
      print "<tr title='expense-entry' style='color:green'>";
      print "<td>$i</td>";
      print "<td colspan='2'>{$expense->particulars} RM".nf($expense->amount)."</td>";
      print "<td nowrap></td>";
      print "<td></td>";
      print "<td>{$userList[$expense->created_by]}</td>";
      print "<td></td>";

      if($expense->payment_method == 'Cash'){
        $total -= $expense->amount;
        $debit += $expense->amount;
        print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
        sum("cash_exp", $expense->amount);
      } else{
        $total_b -= $expense->amount;
        $debit_b += $expense->amount;
        print "<td></td><td></td><td></td><td>".nf($expense->amount)."</td><td>".nf($total_b)."</td>";
        sum("bank_exp", $expense->amount);
      }

      // $total -= $expense->amount;
      // $debit += $expense->amount;
      // print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
      // sum("cash_exp", $expense->amount);

      print "</tr>";
      $i++;
    }


    $hotel_payments = select("SELECT 'hotel_statement_worker_payment' source, p.created_by, p.id, p.amount, p.date, p.created_at, CONCAT('<u>', h.name, '</u> er staff <u>', w.name, '</u>, ', DATE_FORMAT(CONCAT(s.month,'-01'), '%b %Y'), ' maser salary ', p.particulars) particulars, IFNULL(p.approved_by, 'Pending') status, '' ref FROM `hotel_statement_worker_payment` p, `hotel_statement_worker` w, `hotel_statement` s, `hotel` h WHERE p.worker=w.id AND w.statement=s.id AND s.hotel=h.id AND p.date>'$hotel_start_date' AND (p.date BETWEEN '$d' AND '$t') AND (p.particulars LIKE 'Petty Cash theke Taka nise%' OR p.particulars LIKE 'Me2 te%')");
    while($expense = mysqli_fetch_object($hotel_payments)){
      print "<tr title='hotel_payment'>";
      print "<td>$i</td>";
      print "<td colspan='2'>{$expense->particulars}</td>";
      print "<td nowrap></td>";
      print "<td></td>";
      print "<td>{$userList[$expense->created_by]}</td>";
      print "<td></td>";
      $total -= $expense->amount;
      $debit += $expense->amount;
      print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
      sum("cash_exp", $expense->amount);

      print "</tr>";
      $i++;
    }


    $hotel_expenses = select("SELECT 'hotel_expense' source, created_by, id, amount, DATE, created_at, particulars, STATUS, '' ref FROM hotel_expense WHERE payment_method='Cash' AND date>'$hotel_start_date' AND (date BETWEEN '$d' AND '$t')");
    while($expense = mysqli_fetch_object($hotel_expenses)){
      print "<tr title='hotel_expense'>";
      print "<td>$i</td>";
      print "<td colspan='2'>{$expense->particulars}</td>";
      print "<td nowrap></td>";
      print "<td></td>";
      print "<td>{$userList[$expense->created_by]}</td>";
      print "<td></td>";
      $total -= $expense->amount;
      $debit += $expense->amount;
      print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
      sum("cash_exp", $expense->amount);

      print "</tr>";
      $i++;
    }



    $bd_cash = select("SELECT * FROM bd_cash WHERE date BETWEEN '$d' AND '$t'");
    while($expense = mysqli_fetch_object($bd_cash)){
      print "<tr title='bd_cash'>";
      print "<td>$i</td>";
      print "<td colspan='2'>{$expense->particulars}</td>";
      print "<td nowrap></td>";
      print "<td></td>";
      print "<td>{$userList[$expense->created_by]}</td>";
      print "<td></td>";
      $total -= $expense->amount;
      $debit += $expense->amount;
      print "<td>".nf($expense->amount)."</td><td>".nf($total)."</td><td></td><td></td><td></td>";
      sum("cash_exp", $expense->amount);

      print "</tr>";
      $i++;
    }
  }

  // if((isset($get->d)?(isset($get->handover)?true:false):true) && !$ec){
  if($_handover){
    $handovers = select("*", "bd_handover", "date=CURDATE()");
    while($s = mysqli_fetch_object($handovers)){
      $total -= $s->amount;
      $debit += $s->amount;
      print "<tr title='handover'>";
      print "<td>$i</td>";
      print "<td colspan='4'>Cash Handover</td>";
      print "<td>{$userList[$s->created_by]}</td><td></td><td>$s->amount</td>";  
      print "<td>".nf($total)."</td>";
      print "<tr>";
      $i++;
    }
  }
  */
  print "<tr><th colspan='5'>TOTAL</th><th style='font-size:18px; color: #337ab7;' title='Collect $credit, Cash $total'>" . nfz($credit) . "</th><th>" . nf($debit) . "</th><th>" . nf($total) . "</th><th>" . nf($credit_b) . "</th><th>" . nf($debit_b) . "</th><th>" . nf($total_b) . "</th></tr>";
  ?>
</table>
<?php
// if(isUserIn(['apple', 'superadmin', 'orange'])){
print "<div class='center' style='font-weight:700'><form method='post'>Collection Amount Cash <input type='number' name='amount' value='$credit' required step='.05' class='form-control form-control-fluid'>  Bank <input type='number' name='bank_amount' value='$credit_b' required step='.05' class='form-control form-control-fluid'>";
if ($canPickPast || isUserIn(["superadmin", "ebrahim", "lemon"])) {
  print "<input type='date' class='form-control form-control-fluid' value='" . today() . "' name='date'>";
}
print " <button type='submit' class='btn btn-primary' name='collect_cash'> Collect</button> </form><br>";
// print "<div class='center'><form method='post'>Collection Amount Bank <input type='number' name='bank_amount' value='' required step='.05' class='form-control-fluid'> <button type='submit' class='btn btn-danger' name='collect_cash'> Collect</button> </form><br><br><br>";
// }
?>
<!--  -->
<div class="modal fade outsource" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Outsource Expense</h4>
      </div>
      <?php openForm('post', true); ?>
      <div class="modal-body">
        <table>
          <tr>
            <td>Expense Sector</td>
            <td>
              <?php print sop2("expense_sector", "", ["class" => 'w250', "extra" => "ORDER BY name", 'filter' => 'id>0', 'optional' => true, 'class' => 'required']); ?>
            </td>
          </tr>
          <tr>
            <th>Expense Category</th>
            <th>
              <?php print sop2("parent-category", "", ["extraFields" => "show_month", "filter" => "type='Outsource' AND (parent IS NULL OR parent=0) AND hidden=0", 'optional' => true, "class" => 'w250'], "expense_category"); ?>
            </th>
          </tr>
          <tr>
            <td>Expense Type</td>
            <td>
              <?php
              //print sop2("category_id", "", ["filter"=>"type='Outsource' AND parent>0", "extraFields"=>"parent", 'optional'=>true, "class"=>'w250'], "expense_category"); 
              $categories = R::find("expense_category", "type=? AND parent > ?", ['Outsource', 0]);
              foreach ($categories as $key => $category) {
                print "<div class='outsource-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' class='" . ($category->rtk ? 'rtk' : '') . "' " . ($key == 0 ? 'required' : '') . " value='$category->id'> " . $category->name . "</div>";
              }
              ?>
            </td>
          </tr>
          <tr class='rtk-selection' style="display: none;">
            <td></td>
            <td>
              <div><input type='radio' name="rtk" value='RTK - 1'> RTK - 1</div>
              <div><input type='radio' name="rtk" value='RTK - 2'> RTK - 2</div>
            </td>
          </tr>
          <tr class='select-month'>
            <td>Payment for month</td>
            <td>
              <?php print monthSelector('outsource_month', '', '', '', date('Y', time()) - 1); ?>
            </td>
          </tr>
          <tr>
            <td>Date</td>
            <td><input type="date" name="date" class="form-control" required value='<?php print today(); ?>'></td>
          </tr>
          <tr>
            <td>Particulars</td>
            <td><textarea class="form-control particulars" name="particulars" required></textarea></td>
          </tr>
          <tr>
            <td>Amount</td>
            <td><input type="number" name="amount" id="outsource-amount" class="form-control" step=".01" required></td>
          </tr>
          <tr>
            <td>Worker (Optional)</td>
            <td><?php print sop2("worker", '', ["optional" => true]); ?></td>
          </tr>
          <tr class='hidden'>
            <td>Payment Mode</td>
            <td>
              <?php print selectEnum("name='payment_method' class='form-control'", "expenditure", "payment_method", 'Cash'); ?>
            </td>
          </tr>
          <tr class='hidden'>
            <td>Receipt</td>
            <td><input type="file" name="file" class="form-control"></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="save_outsource_expense">Save</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
<!--  -->


<!--  -->
<div class="modal fade edit-amount" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Outsource Expense</h4>
      </div>
      <?php openForm('post', true); ?>
      <input type='hidden' name='id' class='id'>
      <div class="modal-body">
        <table>
          <tr>
            <td>Amount</td>
            <td><input type="number" name="amount" id="outsource-amount" class="form-control" step=".01" required></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="update_outsource_expense">Save</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
<!--  -->

<!--  -->
<div class="modal fade delete-expenditure" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Outsource Expense</h4>
      </div>
      <?php openForm('post', true); ?>
      <input type='hidden' name='id' class='id'>
      <div class="modal-body">
        <h1>Are you sure you want to remove this expense?</h1>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" name="delete_outsource_expense">Yes</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
<!--  -->

<!--  -->
<div class="modal fade online" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Online Expense</h4>
      </div>
      <?php openForm('post', true); ?>
      <input type='hidden' name='payment_method' value=''>
      <!-- <input type='hidden' name='particulars' value=''> -->
      <div class="modal-body">
        <table>
          <tr>
            <td>Expense Sector</td>
            <td>
              <?php print sop2("expense_sector", "", ["class" => 'w250', "extra" => "ORDER BY name", 'filter' => 'id>0', 'optional' => true, 'class' => 'required']); ?>
            </td>
          </tr>
          <tr>
            <th>Expense Category</th>
            <th>
              <?php print sop2("online-parent-category", "", ["extraFields" => "show_month", "filter" => "type='Online' AND (parent IS NULL OR parent=0)", 'optional' => true, "class" => 'w250'], "expense_category"); ?>
            </th>
          </tr>
          <tr>
            <td>Expense Type</td>
            <td>
              <?php

              //print sop2("category_id", "", ["filter"=>"type='Online' AND parent>0", "extraFields"=>"parent", 'optional'=>true, "class"=>'w250'], "expense_category"); 
              $categories = R::find("expense_category", "type=? AND parent > ?", ['Online', 0]);
              foreach ($categories as $key => $category) {
                print "<div class='online-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' class='" . ($category->rtk ? 'ortk' : '') . "' " . ($key == 0 ? 'required' : '') . " value='$category->id'> " . $category->name . "</div>";
                // print "<div class='online-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' ".($key == 0 ? 'required' : '')." value='$category->id'> ".$category->name."</div>";
              }
              ?>

            </td>
          </tr>

          <tr class='rtk-selection' style="display: none;">
            <td></td>
            <td>
              <div><input type='radio' name="rtk" value='RTK - 1'> RTK - 1</div>
              <div><input type='radio' name="rtk" value='RTK - 2'> RTK - 2</div>
            </td>
          </tr>

          <tr class='select-month'>
            <td>Payment for month</td>
            <td>
              <?php print monthSelector('online_month', '', '', '', date('Y', time()) - 1); ?>
            </td>
          </tr>
          <tr>
            <td>Date</td>
            <td><input type="date" name="date" class="form-control" required value='<?php print today(); ?>'></td>
          </tr>
          <tr>
            <td>Particulars</td>
            <td><textarea class="form-control particulars" name="particulars" required></textarea></td>
          </tr>
          <tr>
            <td>Amount</td>
            <td><input type="number" name="amount" class="form-control" step=".01" required></td>
          </tr>
          <tr>
            <td>Worker (Optional)</td>
            <td><?php print sop2("worker", '', ["optional" => true]); ?></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="save_online_expense">Save</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker3.min.css">
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>

<script>
  $(function () {
    // noConflict AFTER bootstrap-datepicker is loaded
    if ($.fn.datepicker && $.fn.datepicker.noConflict) {
      var bs = $.fn.datepicker.noConflict();
      $.fn.bsDatepicker = bs;
    } else {
      $.fn.bsDatepicker = $.fn.datepicker;
    }

    var canPickPast = <?= $canPickPast ? 'true' : 'false' ?>;
    var minDate = "<?= $minDate ?>"; // "" or YYYY-MM-01
    var maxDate = "<?= $maxDate ?>"; // "" or YYYY-MM-DD

    var $inputs = $("input[name='d'], input[name='t']");

    $inputs.each(function () {
      var $el = $(this);

      // make sure browser native date input doesn't interfere
      if (($el.attr('type') || '').toLowerCase() === 'date') {
        $el.attr('type', 'text');
      }

      // stop the generic picker if dp() added inline handlers
      $el.removeAttr('onfocus').removeAttr('onclick');

      var opts = {
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        orientation: 'bottom auto'
      };

      // Only NORMAL users get blocked
      if (!canPickPast && minDate) opts.startDate = minDate;
      if (!canPickPast && maxDate) opts.endDate = maxDate;

      $el.bsDatepicker(opts);
    });
  });
</script>

<script type="text/javascript">
  $("#parent-category").change(function () {
    // filter('.category_id', 'parent', $("#parent-category").val());
    var parent = $("#parent-category").val();
    $(".outsource-category").prop('checked', false).hide();
    $(".parent-" + parent).show();
    console.log(parent, parent == "94", parent == 94)
    if (parent == "94") {
      $("#outsource-amount").val('').attr("required", false);//.attr('readonly', true);
    }

    var show_month = $(this).find("option:selected").data('show_month');
    if (show_month == 1) {
      $(".select-month").show();
    } else {
      $(".select-month").hide();
    }
  });
  $("#online-parent-category").change(function () {
    var parent = $("#online-parent-category").val();
    $(".online-category").prop('checked', false).hide();
    $(".parent-" + parent).show();

    var show_month = $(this).find("option:selected").data('show_month');
    console.log(show_month)
    if (show_month == 1) {
      $(".select-month").show();
    } else {
      $(".select-month").hide();
    }
  });

  $("input.rtk").click(function () {
    $(".outsource .rtk-selection").show();
    $(".outsource .rtk-selection input:first-child").prop('required', true);
  });

  $("input.ortk").click(function () {
    $(".online .rtk-selection").show();
    $(".online .rtk-selection input:first-child").prop('required', true);
  });

  particulars = "";

  $(".outsource input[type='radio']").click(function () {
    var text = $(this).parent().text();
    particulars = text;
    var show_month = $(this).parent().parent().parent().parent().find("select.parentcategory option:selected").data('show_month');
    if (show_month == 1) {
      text = $("#outsource_month_mon option:selected").text() + ' ' + $("#outsource_month_year option:selected").text() + ' ' + text;
    }
    $(this).parent().parent().parent().parent().find(".particulars").val(text);
  })

  $(".online input[type='radio']").click(function () {
    var text = $(this).parent().text();
    particulars = text;
    var show_month = $(this).parent().parent().parent().parent().find("select.onlineparentcategory option:selected").data('show_month');
    if (show_month == 1) {
      text = $("#online_month_mon option:selected").text() + ' ' + $("#online_month_year option:selected").text() + ' ' + text;
    }
    $(this).parent().parent().parent().parent().find(".particulars").val(text);
  })

  function setExpense(id) {
    $(".edit-amount .id").val(id);
  }

  function setExpense2(id) {
    $(".delete-expenditure .id").val(id);
  }

  $("#outsource_month_mon,#outsource_month_year").change(function () {
    var text = $("#outsource_month_mon option:selected").text() + ' ' + $("#outsource_month_year option:selected").text() + ' ' + particulars;
    $(".outsource .particulars").val(text);
  });

  $("#online_month_mon,#online_month_year").change(function () {
    var text = $("#online_month_mon option:selected").text() + ' ' + $("#online_month_year option:selected").text() + ' ' + particulars;
    $(".online .particulars").val(text);
  });
</script>
