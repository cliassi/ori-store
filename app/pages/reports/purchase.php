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
$canApprovePurchase = uid() == 1 || username() == 'Adminn';

if(isset($post->collect_cash)){
  if(isUserIn([])){
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d", strtotime($post->date))]);
  } else{
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d")]);
  }
  if(!$handover){
    $handover = R::dispense("bd_handover");
    $handover->date = today();
    $handover->branch_id = $branch_id;
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


if(isset($post->delivered)){
  if($canApprovePurchase){
    $order = R::load('order', $post->delivered);
    $order->delivered_by = uid();
    $order->delivery_date = now();
    R::store($order);
  } else{
    alert("Only uid 1 or Adminn can approve pending purchase.");
  }
}

if(isset($post->update_outsource_expense)){
    $expense = R::load("expenditure", $post->id);
    $expense->amount = $post->amount; 
    $expense->modify_by = uid(); 
    $expense->modify_time = now(); 
    R::store($expense);
}

if(isset($post->delete_outsource_expense)){
    $expense = R::load("expenditure", $post->id);
    R::trash($expense);
}

  if(isset($post->save_outsource_expense) || isset($post->save_online_expense) || isset($post->save_office_expense) || isset($post->save_visa_expense) || isset($post->save_salary_expense)){
    $expense = R::dispense("expenditure");
    if(isset($post->company))
    $expense->company = $post->company; 
    if(isset($post->expense_sector))
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
    if(isset($post->save_outsource_expense)){
      $expense->payment_method = 'Outsource';
      $expense->payment_method = $post->payment_method;
    } else{
      $expense->payment_method = 'Online';
      $expense->payment_method = 'Online';
    }

    if(isset($post->rtk)){
      $expense->rtk = $post->rtk;
    }

    if(isset($post->worker) && nn($post->worker)){
      $expense->worker = $post->worker;
    }
    $expense->source = 'Daily Collection';
    $expense->created_by = uid(); 
    $expense->created_at = now(); 
    R::store($expense);

    if(isset($_FILES['file']['name'])){
      $file = upload($_FILES, time(), "uploads/receipts");
      $expense->file = $file;
      R::store($expense);
    }
    alert("Saved!");
    redir("?");
  }
  // $get->d = $d;
  // $get->t = $t;
  $dateLimit = false;
  // if (uid() != 1 || uid() != 47 || uid() != 45) {
  //   $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
  //   $dateLimit = $sevenDaysAgo;
  //   if (daydiff($d, $sevenDaysAgo) > 0) {
  //       $d = $sevenDaysAgo;
  //       $get->d = $d;
  //   }
  // }

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
  $supplier_id = isset($get->supplier_id) ? ($get->supplier_id + 0) : 0;
  $product_variance_id = isset($get->product_variance_id) ? ($get->product_variance_id + 0) : 0;
  $suppliers = toA('supplier', 'id', 'company');
  $supplierOptions = "<option value=''>All Suppliers</option>";
  foreach($suppliers as $sid => $sname){
    if($sid === ""){
      continue;
    }
    $supplierNameEsc = htmlspecialchars($sname, ENT_QUOTES);
    $supplierOptions .= "<option value='$sid'".($supplier_id == $sid ? " selected" : "").">$supplierNameEsc</option>";
  }
  $productVarianceOptions = "<option value=''>All Variants</option>";
  $productVariances = select("id, particulars, size, unit", "product_variance", "", "ORDER BY particulars, size, unit");
  while($pv = mysqli_fetch_object($productVariances)){
    $pvName = $pv->particulars . (nn($pv->size) ? " {$pv->size} x {$pv->unit}" : "");
    $pvNameEsc = htmlspecialchars($pvName, ENT_QUOTES);
    $productVarianceOptions .= "<option value='$pv->id'".($product_variance_id == $pv->id ? " selected" : "").">$pvNameEsc</option>";
  }

  openFilterForm("get");
  print "<input type='hidden' name='pm' value='$pm'> Order Date ".dp('d', $d, $dateLimit)." - ".dp('t', $t, $dateLimit);  print " Supplier <select name='supplier_id' class='form-control form-control-fluid w200'>$supplierOptions</select>";
  print " Product Variant <select name='product_variance_id' class='form-control form-control-fluid w250'>$productVarianceOptions</select>";
  closeFilterForm();
  $companies = toA("company");
  $userList = userList();
?>
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
  <tr><th>No.</th><th>Date</th><th>INV #</th><th>Supplier</th><th>Products</th><th>Quantity</th><th>Total</th><th>Status</th></tr>

  <?php

    $orderFilter = "i.id=ii.order_id AND order_date BETWEEN '$d' AND '$t'";
    if($supplier_id > 0){
      $orderFilter .= " AND i.supplier_id=$supplier_id";
    }
    if($product_variance_id > 0){
      $orderFilter .= " AND ii.product_variance_id=$product_variance_id";
    }
    $trans = select("SELECT * FROM (SELECT i.*, GROUP_CONCAT('<div style=\"border-bottom: solid 1px #ccc;\">',description,', <b class=\"frht\">(', price ,' X ', quantity,' = ',(quantity*price),')</b></div>' SEPARATOR '') particulars, SUM(quantity) quantity, SUM(quantity*price) total FROM `order` i, order_item ii WHERE $orderFilter GROUP BY i.id) a ORDER BY id");

    $i = 1;
    while ($item = mysqli_fetch_object($trans)) {
      print "<tr>";
      print "<td>$i</td>";
      print "<td>".df($item->order_date)."</td>";
      print "<td>INV".zerofill($item->id, 5)."</td>";
      print "<td><a href='/store/supplier/details/$item->supplier_id'>".$suppliers[$item->supplier_id]."</a></td>";
      print "<td>$item->particulars</td>";
      print "<td class='text-right'>".nf0($item->quantity)."</td>";
      print "<td class='text-right'>".nf($item->total)."</td>";
      if($item->delivered_by){
        print "<td><span class='btn btn-success btn-sm'>Approved</span></td>";
      } elseif($canApprovePurchase){
        print "<td><form method='post' style='margin:0'><input type='hidden' name='delivered' value='$item->id'><button class='btn btn-warning btn-sm'>Pending</button></form></td>";
      } else{
        print "<td><button class='btn btn-warning btn-sm' type='button' disabled title='Only uid 1 or Adminn can approve'>Pending</button></td>";
      }
      sum('total',$item->total);
      sum('quantity',$item->quantity);
      print "</tr>";
      $i++;
    }

  print "<tr><th colspan='5'>TOTAL</th><th class='text-right'>".sum('quantity')."</th><th class='text-right'>".nf(sum('total'))."</th><th></th></tr>";
  ?>
</table>

<!--  -->
<div class="modal fade outsource" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Outsource Expense</h4>
      </div>
      <?php openForm('post', true); ?>
        <div class="modal-body">
          <table>
            <tr><td>Expense Sector</td><td><?php print sop2("expense_sector", "", ["class"=>'w250', "extra"=>"ORDER BY name", 'filter'=>'id>0', 'optional'=>true, 'class'=>'required']); ?></td></tr>
            <tr><th>Expense Category</th><th><?php print sop2("parent-category", "", ["extraFields"=>"show_month","filter"=>"type='Outsource' AND (parent IS NULL OR parent=0) AND hidden=0", 'optional'=>true, "class"=>'w250'], "expense_category"); ?></th></tr>
            <tr><td>Expense Type</td><td>
              <?php 
                //print sop2("category_id", "", ["filter"=>"type='Outsource' AND parent>0", "extraFields"=>"parent", 'optional'=>true, "class"=>'w250'], "expense_category"); 
                $categories = R::find("expense_category", "type=? AND parent > ?", ['Outsource', 0]);
                foreach ($categories as $key => $category) {
                  print "<div class='outsource-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' class='".($category->rtk ? 'rtk':'')."' ".($key == 0 ? 'required' : '')." value='$category->id'> ".$category->name."</div>";
                }
              ?></td></tr>
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
              <td>Date</td><td><input type="date" name="date" class="form-control" required value='<?php print today(); ?>' ></td>
            </tr>
            <tr><td>Particulars</td><td><textarea class="form-control particulars" name="particulars" required></textarea></td></tr>
            <tr><td>Amount</td><td><input type="number" name="amount" id="outsource-amount" class="form-control" step=".01" required></td></tr>
            <tr><td>Worker (Optional)</td><td><?php print sop2("worker", '', ["optional"=>true]); ?></td></tr>
            <tr class='hidden'><td>Payment Mode</td><td><?php print selectEnum("name='payment_method' class='form-control'", "expenditure", "payment_method", 'Cash'); ?></td></tr>
            <tr class='hidden'><td>Receipt</td><td><input type="file" name="file" class="form-control"></td></tr>
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Outsource Expense</h4>
      </div>
      <?php openForm('post', true); ?>
        <input type='hidden' name='id' class='id'>
        <div class="modal-body">
          <table>
            <tr><td>Amount</td><td><input type="number" name="amount" id="outsource-amount" class="form-control" step=".01" required></td></tr>
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Online Expense</h4>
      </div>
      <?php openForm('post', true); ?>
        <input type='hidden' name='payment_method' value=''>
        <!-- <input type='hidden' name='particulars' value=''> -->
        <div class="modal-body">
          <table>
            <tr><td>Expense Sector</td><td><?php print sop2("expense_sector", "", ["class"=>'w250', "extra"=>"ORDER BY name", 'filter'=>'id>0', 'optional'=>true, 'class'=>'required']); ?></td></tr>
            <tr><th>Expense Category</th><th><?php print sop2("online-parent-category", "", ["extraFields"=>"show_month","filter"=>"type='Online' AND (parent IS NULL OR parent=0)", 'optional'=>true, "class"=>'w250'], "expense_category"); ?></th></tr>
            <tr><td>Expense Type</td><td>
              <?php 

                //print sop2("category_id", "", ["filter"=>"type='Online' AND parent>0", "extraFields"=>"parent", 'optional'=>true, "class"=>'w250'], "expense_category"); 
                $categories = R::find("expense_category", "type=? AND parent > ?", ['Online', 0]);
                foreach ($categories as $key => $category) {
                  print "<div class='online-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' class='".($category->rtk ? 'ortk':'')."' ".($key == 0 ? 'required' : '')." value='$category->id'> ".$category->name."</div>";
                  // print "<div class='online-category parent-{$category->parent}' style='display:none'><input type='radio' name='category_id' ".($key == 0 ? 'required' : '')." value='$category->id'> ".$category->name."</div>";
                }
              ?>
                
              </td></tr>

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
            <tr><td>Date</td><td><input type="date" name="date" class="form-control" required value='<?php print today(); ?>' ></td></tr>
            <tr><td>Particulars</td><td><textarea class="form-control particulars" name="particulars" required></textarea></td></tr>
            <tr><td>Amount</td><td><input type="number" name="amount" class="form-control" step=".01" required></td></tr>
            <tr><td>Worker (Optional)</td><td><?php print sop2("worker", '', ["optional"=>true]); ?></td></tr>
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


<script type="text/javascript">
  $("#parent-category").change(function(){
      // filter('.category_id', 'parent', $("#parent-category").val());
    var parent = $("#parent-category").val();
    $(".outsource-category").prop('checked', false).hide();
    $(".parent-" + parent).show();
    console.log(parent, parent == "94", parent == 94)
    if(parent == "94"){
      $("#outsource-amount").val('').attr("required", false);//.attr('readonly', true);
    }

    var show_month = $(this).find("option:selected").data('show_month');
    if(show_month == 1){
      $(".select-month").show();
    } else{
      $(".select-month").hide();
    }
  });
  $("#online-parent-category").change(function(){
    var parent = $("#online-parent-category").val();
    $(".online-category").prop('checked', false).hide();
    $(".parent-" + parent).show();

    var show_month = $(this).find("option:selected").data('show_month');
    console.log(show_month)
    if(show_month == 1){
      $(".select-month").show();
    } else{
      $(".select-month").hide();
    }
  });

  $("input.rtk").click(function(){
    $(".outsource .rtk-selection").show();
    $(".outsource .rtk-selection input:first-child").prop('required', true);
  });

  $("input.ortk").click(function(){
    $(".online .rtk-selection").show();
    $(".online .rtk-selection input:first-child").prop('required', true);
  });

  particulars = "";

  $(".outsource input[type='radio']").click(function(){
    var text = $(this).parent().text();
    particulars = text;
    var show_month = $(this).parent().parent().parent().parent().find("select.parentcategory option:selected").data('show_month');
    if(show_month == 1){
      text = $("#outsource_month_mon option:selected").text() + ' ' + $("#outsource_month_year option:selected").text() + ' ' + text;
    }
    $(this).parent().parent().parent().parent().find(".particulars").val(text);
  })

  $(".online input[type='radio']").click(function(){
    var text = $(this).parent().text();
    particulars = text;
    var show_month = $(this).parent().parent().parent().parent().find("select.onlineparentcategory option:selected").data('show_month');
    if(show_month == 1){
      text = $("#online_month_mon option:selected").text() + ' ' + $("#online_month_year option:selected").text() + ' ' + text;
    }
    $(this).parent().parent().parent().parent().find(".particulars").val(text);
  })

  function setExpense(id){
    $(".edit-amount .id").val(id);
  }

  function setExpense2(id){
    $(".delete-expenditure .id").val(id);
  }

  $("#outsource_month_mon,#outsource_month_year").change(function(){
    var text = $("#outsource_month_mon option:selected").text() + ' ' + $("#outsource_month_year option:selected").text() + ' ' + particulars;
    $(".outsource .particulars").val(text);
  });

  $("#online_month_mon,#online_month_year").change(function(){
    var text = $("#online_month_mon option:selected").text() + ' ' + $("#online_month_year option:selected").text() + ' ' + particulars;
    $(".online .particulars").val(text);
  });
</script>
