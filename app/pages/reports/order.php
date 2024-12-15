<style type="text/css">
  td{
    text-align: left;
  }
  .modal th{
    background-color: rgba(120,250,70, .1) !important;
  }
  .modal th span{
    font-weight: 700;
  }
</style>
<?php

$hotel_start_date = '2023-01-18 23:59:59';

$d = isset($get->d)?$get->d:today();
$t = isset($get->t)?$get->t:today();

if(!isUserIn(['apple','superadmin','melon','lemon','orange','mango', 'berry', 'Olive'])){
  exit;
}

if (isset($post->collect) && isset($get->salesman)) {
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($get->salesman)?$get->salesman:0;
  if(isset($post->save)){
    $obj->salesman_id = $get->salesman;
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post->variance as $id => $qty) {
      if($qty == 0) continue;
      if(!$stored){ R::store($obj); $stored = true; }
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("stock_collect_item");

      $ii->stock_collect_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = "$variance->particulars $variance->size x $variance->unit";
      $ii->created_by = uid();

      R::store($ii);
    }

    redir(ROOT."/salesman/details/$obj->salesman_id");
  }
}

if(isset($post->collect_cash)){
  if(isUserIn([])){
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d", strtotime($post->date))]);
  } else{
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d")]);
  }
  if(!$handover){
    $handover = R::dispense("bd_handover");
    $handover->date = today();
    $handover->account = 1;
    if(isset($post->amount)) $handover->amount = $post->amount;
    if(isset($post->bank_amount)) $handover->bank_amount = $post->bank_amount;
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
  } else{
    $handover->date = today();
    $handover->account = 1;
    if(isset($post->amount)) $handover->amount = $post->amount;
    if(isset($post->bank_amount)) $handover->bank_amount = $post->bank_amount;
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

if(isset($post->save_delivery)){
  // dd($post);
  $order = R::load('invoice', $post->invoice_id);
  $order->delivered_by = uid();
  $order->delivery_staff = $post->delivery_staff;
  $order->delivery_date = now();
  R::store($order);
}

  // $get->d = $d;
  // $get->t = $t;
  if(uid()!=1){
    if(daydiff($d, prevDay()) > 0){
      $d = prevDay();
      $get->d = $d;
    }
  }


  $com = isset($get->company) ? $get->company : '';
  $ec = isset($get->expense_category) ? $get->expense_category : '';

  if(!isset($get->collection) && !isset($get->expense) && !isset($get->handover)){
    $_collection =  $_handover = $_expense = true;
  } else{
    $_collection = isset($get->collection) ? $get->collection : false;
    $_handover = isset($get->handover) ? $get->handover : false;
    $_expense = isset($get->expense) ? $get->expense : false;
    // $_expense = isset($get->expense) ? $get->expense : (($_collection || $_handover) ? isset($_expense) : true);
  }

  $pm = isset($get->pm) ? $get->pm : 'Outsource';
  $sm = isset($get->salesman) ? $get->salesman : '';


  openFilterForm("get");
  print "<input type='hidden' name='pm' value='$pm'> Order  Date ".dp('d', $d, uid()!=1 ? prevDay() : false)." - ".dp('t', $t, uid()!=1 ? prevDay() : false);
  print " Salesman <span><select class='form-select supplier-select inline-block w150' name='salesman'>
      <option value=''>Please select</option>";
      $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
      while ($man = mysqli_fetch_object($objs)) {
        print "<option ";
        if($man->name == $sm) print "selected";
        print ">$man->name</option>";
      }
  print "</select></span>";
  closeFilterForm();
  $companies = toA("company");
  $userList = userList();
?>
<form method="post">
<table class="table table-bordered">
	<tr>
    <th colspan="7">

      <?php if(uid()==1 || uid()==21 || isUserIn(['apple', 'melon'])){ ?>
      <!-- <a data-toggle='modal' data-target='.office' class='btn btn-primary'>Office Expense</a> 
      <a data-toggle='modal' data-target='.salary' class='btn btn-warning'>Salary Expense</a> 
      <a data-toggle='modal' data-target='.expense' class='btn btn-danger'>Visa Expense</a> -->
      <!-- <a data-toggle='modal' data-target='.outsource' class='btn btn-danger'>Outsource Expense</a> -->
      <!-- <?php print "<a href='?expense=1&d=$d&t=$t&company=$com&expense_category=$ec&pm=Outsource' class='btn btn-danger'>".($pm == "Outsource" ? '<img src="../assets/verified.png" width="22px">': "")." Outsource Report</a>"; ?>
      |
      <a data-toggle='modal' data-target='.online' class='btn btn-warning'>Online Expense</a> -->
      <?php //print "<a href='?expense=1&d=$d&t=$t&company=$com&expense_category=$ec&pm=Online' class='btn btn-warning'>".($pm == "Online" ? '<img src="../assets/verified.png" width="22px">': "")." Online Report</a>"; ?>

      <?php } ?>
    </th>
  </tr>
	<tr><th></th><th>No.</th><th>Date</th><th>INV #</th><th>Customer</th><th>Products</th><th>Goods</th><th>Quantity</th><th>Total</th><th>Status</th><th>Salesman</th><th></th></tr>

  <?php

    $customers = toA('customer', 'id', 'company');
    $salesmans = toA('salesman');
    $trans = select("SELECT * FROM (SELECT i.*, invoiceItems(i.id) particulars, SUM(quantity) quantity, SUM(quantity*price) total FROM invoice i, invoice_item ii WHERE i.id=ii.invoice_id AND invoice_date BETWEEN '$d' AND '$t' ".($sm ? "AND i.salesman_id=$sm":"")." GROUP BY i.id) a ORDER BY id");

    $i = 1;
    while ($item = mysqli_fetch_object($trans)) {
      print "<tr>";
      print "<td><input type='checkbox' name='collect[$item->id]' value='$item->id'></td>";
      print "<td>$i</td>";
      print "<td>".df($item->invoice_date)."</td>";
      print "<td>INV".zerofill($item->id, 5)."</td>";
      print "<td><a href='/store/customer/details/$item->customer_id'>".$customers[$item->customer_id]."</a></td>";
      print "<td>$item->particulars</td>";
      print "<td>".($item->delivered_by ? "<a class='btn btn-success'>Received</a>" : "<form method='post'><input type='hidden' name='delivered' value='$item->id'><a class='btn btn-warning' data-bs-toggle='modal' data-bs-target='.deliver' onclick='setInvoice($item->id)'>Ordered</a>")."</td>";
      print "<td class='text-right'>".nf0($item->quantity)."</td>";
      print "<td class='text-right'>".nf($item->total)."</td>";
      print "<td>Pending</td>";
      print "<td>".$salesmans[$item->salesman_id]."</td>";
      print "<td>".(isset($get->salesman)?"<button class='btn btn-sm btn-success' name='collect'>Collect for Delivery</button>":"")."</td>";
      sum('total',$item->total);
      sum('quantity',$item->quantity);
      print "</tr>";
      $i++;
    }

  print "<tr><th colspan='7'>TOTAL</th><th class='text-right'>".sum('quantity')."</th><th class='text-right'>".sum('total')."</th><th></th></tr>";
  ?>
</table>
</form>
<div class="modal fade deliver" id="deliver" role="dialog">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <form method="post" autocomplete="off" enctype='multipart/form-data'>
        <input type="hidden" name="invoice_id" id="invoice_id" value=''>
        <div class="modal-header">
          <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
          <h4 class="modal-title">Delivery</h4>
        </div>
        <div class="modal-body">
          <table>
            <tr><td>Delivery Staff</td><td>
              <?php
                print " <span><select class='form-select supplier-select inline-block w150' required name='delivery_staff'>
                  <option value=''>Please select</option>";
                  $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
                  while ($man = mysqli_fetch_object($objs)) {
                    print "<option ";
                    if($man->name == $sm) print "selected";
                    print ">$man->name</option>";
                  }
              print "</select></span>";
              ?>
            </td></tr>
          </table>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success" name="save_delivery">Save</button>
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
  function setInvoice(id){
    $("#invoice_id").val(id);
  }
</script>
