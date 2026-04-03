<style type="text/css">
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
  #modal-worker-details td{
    white-space: wrap !important;
  }
</style>
<?php 
if(METHOD == 'add'){

} else{   

  if(isset($get->duplicate)){
    $statement = R::load("statement", $get->duplicate);
    $loan = R::dispense("statement");
    $ym = explode("-", $statement->month);
    $y = $ym[0];
    $m = $ym[1] + 1;
    if($m > 12){
      $y += 1;
      $m = 1;
    }
    $loan->month = "$y-".zerofill($m,2)."-01";
    $loan->created_by = uid();
    $loan->created_at = now();
    R::store($loan);

    $workers = R::find("staff_salary", "statement_id=?", [$statement->id]);
    foreach ($workers as $key => $w) {
      $worker = R::dispense("staff_salary");
      $worker->statement_id = $loan->id;
      $worker->name = $w->name;
      $worker->basic = $w->basic;
      $worker->account = $w->account;
      $worker->category = $w->category;
      $worker->created_by = uid();
      $worker->created_at = now();
      R::store($worker);
    }

    redir("?");
  }

  if(isset($post->idToDelete)){
    $statement = R::load('statement', $post->idToDelete);
    R::trash($statement);
  }
  if(isset($post->idToDelete2)){
    $staff_salary = R::load('staff_salary', $post->idToDelete2);
    R::trash($staff_salary);
    redir("?s=$staff_salary->statement_id");
  }
  if(isset($post->save_worker_payment_2)){
    $statement = R::load("statement", $get->s);
    $saved = false;
    foreach ($post->workers as $key => $worker) {
      $worker_salary = $post->salary[$key];
      $w = R::load("staff_salary", $worker);

      $salary =  round($w->basic / 30 * $w->working_days);
      if($statement->hourly && $w->working_days < 325){
        $salary -= 100;
      }
      $income = getSum("staff_income", "amount", "staff_id=$w->id");
      $salary = $salary + $income;
      // dd($salary);
      $paid = mysqli_fetch_object(select("SELECT IFNULL(SUM(amount),0) paid FROM `staff_payment` WHERE staff_id=$w->id"));

      // if(($salary >= ($paid->paid + $worker_salary)) || (!$w->working_days && ($worker_salary + $paid->paid) <= 1500 && $worker_salary > 0 && date("d", time()) > 14 && date("d", time()) < 21)){
        $payment = R::dispense("staff_payment");
        $payment->staff_id = $w->id;
        $payment->date = $post->payment_date;
        $payment->amount = $worker_salary;
        $payment->bank = $post->bank;
        $payment->particulars = $post->particulars;
        // $payment->created_by = uid();
        $payment->created_at = now();

        if(isset($w->id) && strpos($payment->particulars, 'Salary theke permit') !== FALSE){
          $payment->staff_id = $w->id;
        }

        R::store($payment);


        //NEW EXPENSE ENTRY
        // if($saved == false){
        //   $saved = true;
        //   $entry = accountEntry($hotel->accountid, $post->particulars, $post->amount, 'Debit', ['entry_id'=>$payment->id, 'entry_type'=>'Hotel - Salary Payment', 'month'=>$statement->month,'payment_method'=>'Online','hotel'=>$hotel->id,'bank'=>$post->bank, 'expense_date'=>$post->payment_date]);
        //   $payment->account_entry_id = $entry->id;
        //   R::store($payment);
        // }

        
        // redir("?page=3&h=$get->h");
      // } else{
      //   if(!$w->working_days && date("d", time() > 14)){
      //     alert("Sorry you cannot pay advance before 15 of the month");
      //   } else{
      //     alert("Sorry you cannot overpay");
      //   }
      // }
    }
    redir("?s=$get->s");
  }
  if(isset($post->save_salary) && isset($post->staff)){
    foreach ($post->staff as $key => $id) {
      $staff_salary = R::dispense("staff_salary");
      $staff_salary->staff_id = $id;
      $staff_salary->statement_id = $post->statement_id;
      $staff_salary->basic = $post->basic[$id];
      $staff_salary->days = $post->days[$id];
      $staff_salary->salary = $post->salary[$id];
      $staff_salary->extra = $post->extra[$id];
      R::store($staff_salary);
    }
    redir("?page=3&h=$get->h");
  }

  // if(isUserIn(['superadmin'])){
    if(isset($post->set_category)){
      $worker = R::load("staff_salary", $post->id);
      $worker->category = $post->set_category;
      R::store($worker);
    }

    if(isset($get->lock)){
      $w = R::load("staff_salary", $get->w);
      $w->lock = $get->lock == 0 ? 1 : 0;
      R::store($w);
      redir("?page=3&h=".$get->h);
    }
  // }

  if(isset($post->add_salary)){
    $income = R::dispense("staff_income");
    $income->staff_id = $post->id;
    $income->amount = $post->amount;
    $income->date = $post->date_ext;
    $income->particulars = $post->particulars;
    // $income->created_by = uid();
    $income->created_at = now();
    R::store($income);
    redir("?s=$get->s");
  }
  // if(isset($post->save_worker_account)){
  //   $worker = R::load("staff_salary", $post->id);
  //   $worker->account = $post->worker_account;
  //   R::store($worker);
  // }

  if(isset($post->deduct_salary)){
    $income = R::dispense("staff_income");
    $income->staff_id = $post->id;
    $income->amount = 0 - $post->amount;
    $income->date = $post->date_ext2;
    $income->particulars = $post->particulars;
    // $income->created_by = uid();
    $income->created_at = now();
    R::store($income);
    redir("?s=$get->s");
  }


  if(isset($post->save_staff)){
    if(isset($post->staff_id) && $post->staff_id){
      $staff_salary = R::load("staff_salary", $post->staff_id);
    } else{
      $staff_salary = R::dispense("staff_salary");
    }
    $staff_salary->name = $post->name;
    $staff_salary->category = $post->category;
    $staff_salary->statement_id = $post->statement_id;
    $staff_salary->basic = $post->basic;
    $staff_salary->days = $post->days + 0;
    R::store($staff_salary);
  }
  if (isset($post->save_month)) {
    try {
      if(isset($post->id)){
        $statement = R::load('statement', $post->id);
      } else{
        $statement = R::dispense('statement');
      }
      $statement->month = "{$post->year}-{$post->month}-01";
      R::store($statement);

    } catch (\Throwable $th) {
      dump($th);
    }
  }
  //End Save

  $month = isset($get->month) ? $get->month : date("Y-m-01");
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <?php 
        if(!isset($get->month)){
          $get->month = date("Y-m-01", time());
        }
        if(isset($get->s) || isset($get->month)){
          if(isset($get->s)){
            $obj = R::load('statement', $get->s);  
          } else{
            $month = date("Y-m-01",strtotime($get->month));
            $obj = R::findOne('statement', "month=?", [$month]);
            if(!$obj){
              $obj = R::dispense("statement");
              $obj->month = $month;
              R::store($obj);
            }
          }

          $mon = $month;
          $date = date("Y-m-d", strtotime("$mon-01"));
          
          print "<div class='card-header'>
          <div class='row'>
          <div class='col-4'><a class='btn btn-shadow btn-secondary' href='?'><i class='fas fa-backspace'></i> Back</a></div>
          <div class='col-4 text-center'><h5>APW Staff Salary - ".date('M Y', strtotime($obj->month))."</h5></div>
          <div class='col-4 text-right'>
          <a class='btn btn-light-primary' href='?month=".subMonth(1, $month)."'><i class='fas fa-angle-left'></i> Prev</a>".
          monthSelector('mon', date("Y-m-d", strtotime($mon."-01")))."
          <a class='btn btn-light-primary' href='?month=".addMonth(1, $month)."'>Next <i class='fas fa-angle-right'></i> </a>
          </div>
          </div>
          <div class='card-body'>
          <div class='dt-responsive table-responsive'>
          <table id='simpletable' class='table table-striped table-bordered nowrap'>
          <thead>
          <tr>
          <th class='text-center'><a onClick='pay()' data-bs-toggle='modal' data-bs-target='#modal-worker-payment-2' class='btn btn-light-primary'><i class='fab fa-amazon-pay icon-pay cursor'></i></a></th>
          <th>No</th>
          <th>Name</th>
          <th>Category</th>
          <th>Basic<br>Salary</th>
          <th>Working<br>Days</th>
          <th>Salary</th>
          <th>Extra<br>Salary</th>
          <th>Total<br>Salary</th>
          <th>Paid<br>Salary</th>
          <th>Balance<br>Salary</th>
          <th>Approved</th>
          <th colspan='2' class='text-right'><a class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#staffModal'><i class='fa fa-file'></i> Add</a></th>
          </tr>
          </thead>
          <tbody>";
          $i = 1;
          $sals = select("*, 
            IFNULL((SELECT SUM(amount) FROM staff_payment WHERE staff_id=staff_salary.id AND amount>0),0) paid, 
            IFNULL((SELECT SUM(amount) FROM staff_income WHERE staff_id=staff_salary.id AND amount>0),0) income, 
            IFNULL((SELECT SUM(amount) FROM staff_income WHERE staff_id=staff_salary.id AND amount<0),0) deduction", "staff_salary", "statement_id = $obj->id");
          while ($sal = mysqli_fetch_object($sals)) {
            $sal->salary = round(($sal->basic / 30 * $sal->days) + $sal->income + $sal->deduction,2);
            print "<tr class='row-$sal->id'>";
            print "<td class='text-center'><input type='radio' value='$sal->id' class='staff_salary_id' name='staff_salary_id[$sal->id]' data-name='$sal->name' data-id='$sal->id'></td>";
            print "<td>$i</td>";
            print "<td class='sal-name'><a data-bs-toggle='modal' data-bs-target='#modal-worker-details' onClick='showDetails($sal->statement_id, $sal->id,\"$sal->name\", \"".nf0($sal->salary)."\", \"".nf0($sal->income)."\", \"".nf0($sal->salary + $sal->income)."\",\"$sal->phone\",\"$sal->account\",$sal->lock)'>$sal->name</a>
            <div>$sal->phone</div></td>
            <td>$sal->category</td>";
            print "<td class='sal-basic'>$sal->basic</td>";
            print "<td class='sal-days'>$sal->days</td>";
            print "<td class='sal-salary'>$sal->salary</td>";
            print "<td class='sal-extra'>$sal->extra</td>";
            print "<td>".($sal->salary + $sal->extra)."</td>";
            print "<td>$sal->paid</td>";
            print "<td>".($sal->salary + $sal->extra - $sal->paid)."</td>";
            print "<td><button class='btn btn-warning'>Pending</button></td>";
            print "<td class='w100'><a class='btn btn-info' data-bs-toggle='modal' data-bs-target='#staffAddModal' onClick='setStaff($sal->id)'><i class='fas fa-edit'></i> </a></td>";
            print "<td class='w100'>
              <a class='btn btn-success'  data-bs-toggle='modal' data-bs-target='#modal-add-salary' onClick='setStaffId($sal->id)'><i class='fas fa-plus'></i></a> 
              <a class='btn btn-danger'  data-bs-toggle='modal' data-bs-target='#modal-deduct-salary' onClick='setStaffId($sal->id)'><i class='fas fa-minus'></i></a> </td>";
            print "<td class='w100'><a class='btn btn-danger' href='javascript:deleteConfirmation2($sal->id)'><i class='fas fa-trash-alt'></i></a></td>";
            print "</tr>";
            $i++;
          }
          print "</tbody>
          <tfoot>
          <tr>
          <th><a class='btn btn-info' data-bs-toggle='modal' data-bs-target='#staffAddModal'><i class='fas fa-plus'></i> </a></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          </tr>
          </tfoot>
          </table>
          </div>
          </div>";
        } else{
          $objs = R::find('statement','deleted_by IS null ORDER BY month');
          print "<div class='card-header'>
          <div class='row'>
          <div class='col-6'><h5>Staff Salary</h5></div>
          <div class='col-6 text-right'>
          </div>
          </div>
          <div class='card-body'>
          <div class='dt-responsive table-responsive'>
          <table id='simpletable' class='table table-striped table-bordered nowrap'>
          <thead>
          <tr>
          <th>No</th>
          <th>Month</th>
          <th>Particulars</th>
          <th colspan='2' class='text-right'><a class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#monthModal'><i class='fa fa-file'></i> Add</a></th>
          </tr>
          </thead>
          <tbody>";
          $i = 1;
          foreach ($objs as $key => $obj) {
            print "<tr>";
            print "<td>$i</td>";
            print "<td><a href='?s=$obj->id'><u>".date("M Y", strtotime($obj->month))."</u></a></td>";
            print "<td>Apw Staff Salary Statements  </td>";
            print "<td class='w100'><a class='btn btn-info' href='?duplicate=$obj->id'><i class='fas fa-copy'></i> Duplicate</a></td>";
            print "<td class='w100'><a class='btn btn-danger' href='javascript:deleteConfirmation($obj->id)'><i class='fas fa-trash-alt'></i> Del</a></td>";
            print "</tr>";
            $i++;
          }
          print '</tbody>
          </table>
          </div>
          </div>';
        }
        ?>
      </div>
    </div>
  </div>

  
  <div id="monthModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="monthmoDallaBel" aria-hidden="true">
    <form class="forms-sample" method="post" enctype="multipart/form-data">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="monthmoDallaBel">Add Month <span></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div class='row'>
              <div class='col-sm-6'>    
                <div class='form-group'>
                  <lable>Name</lable>
                  <select class="form-select" name='month'>
                    <?php 
                    $cm = date("m", time()); 
                    for($i = $cm; $i <= $cm + 12; $i++) { 
                      $k = zerofill($i > 12 ? $i - 12 : $i,2); 
                      print "<option value='$k'>".date("M", strtotime("2024-$k-01"))."</option>";
                    } 
                    ?>
                  </select>
                </div>
              </div>
              <div class='col-sm-6'>     
                <div class='form-group'>
                  <lable>Name</lable>
                  <select class="form-select" name='year'>
                    <?php $cy = date("Y", time()); for($i = $cy; $i <= $cy + 2 ; $i++) print "<option value='$i'>$i</option>"; ?>
                  </select>
                </div>
              </div>
            </div>
            <br>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name='save_month' class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </form>
  </div>    
  <?php if(isset($get->s) || isset($get->month)){ ?>  
    <div id="staffModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="staffModalLabel" aria-hidden="true">
      <form class="forms-sample" method="post" enctype="multipart/form-data">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="staffModalLabel">Add Staff Salary  <span></span></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <input type="hidden" name="statement_id" value="<?php print $obj->id; ?>">
            </div>
            <div class="modal-body">
              <table class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th></th>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Basic</th>
                    <th>Working Days</th>
                    <th>Salary</th>
                    <th>Extra Salary</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $staffs = select("*","staff", "id NOT IN (SELECT staff_id FROM staff_salary WHERE statement_id=$obj->id)");
                  $i = 1;
                  while($staff = mysqli_fetch_object($staffs)){
                    print "<tr>
                    <td><input type='radio' value='$staff->id' name='staff[$staff->id]'></td>
                    <td>$i</td>
                    <td>$staff->name</td>
                    <td><input type='number' class='w100' step='1' name='basic[$staff->id]' value='$staff->basic'></td>
                    <td><input type='number' class='w100' step='1' name='days[$staff->id]' value='0'></td>
                    <td><input type='number' class='w100' step='1' name='salary[$staff->id]' value='0'></td>
                    <td><input type='number' class='w100' step='1' name='extra[$staff->id]' value='0'></td>
                    </tr>";
                    $i++;
                  }
                  ?>
                </tbody>
              </table>
              <br>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" name='save_salary' class="btn btn-primary">Save changes</button>
            </div>
          </div>
        </div>
      </form>
    </div>    

    <div id="staffAddModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="staffAddModalLabel" aria-hidden="true">
      <form class="forms-sample" method="post" enctype="multipart/form-data">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="staffAddModalLabel">Add Staff   <span></span></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <input type="hidden" name="staff_id" id="staff_id">
              <input type="hidden" name="statement_id" value="<?php print $obj->id; ?>">
            </div>

            <div class="modal-body">
              <div class='row'>
                <div class='col-sm-9'>              
                  <div class='form-group'>
                    <lable>Name</lable>
                    <input class='form-control' required name='name' id="name">
                  </div>
                </div>
                <div class='col-sm-3'>              
                  <div class='form-group'>
                    <lable>Staff Category</lable>
                    <select class='form-control' name='category' required>
                      <option vale=''>Please select</option>
                      <option>Delivery Staff</option>
                      <option>Marketing</option>
                      <option>Store Staff</option>
                    </select>
                  </div>
                </div>
              </div>
              <br>
              <div class='row'>
                <div class='col-sm-3'>              
                  <div class='form-group'>
                    <lable>Basic Salary</lable>
                    <input class='form-control' type="number" step="1" required name='basic' id="basic">
                  </div>
                </div>
                <div class='col-sm-3'>              
                  <div class='form-group'>
                    <lable>Days</lable>
                    <input class='form-control' type="number" step='1' name='days' id="days">
                  </div>
                </div>                    
                <div class='col-sm-6'>
                  <lable>Staff Photo</lable>
                  <input type='file' class='form-control' name='image' id="image">
                </div>
              </div>
              <br>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" name='save_staff' class="btn btn-primary">Save changes</button>
            </div>
          </div>
        </div>
      </form>
    </div>    

    <div class="modal fade modal-worker" id="modal-add-salary" role="dialog">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <form method="post" autocomplete="off" enctype='multipart/form-data'>
          <input type="hidden" name="id" class="staff_id">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Add Salary</h4>
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
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade modal-category" id="modal-category" role="dialog">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <form method="post" autocomplete="off" enctype='multipart/form-data'>
          <input type="hidden" name="id" class="worker_id">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Set Category</h4>
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
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade modal-worker" id="modal-deduct-salary" role="dialog">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <form method="post" autocomplete="off" enctype='multipart/form-data'>
          <input type="hidden" name="id" class="staff_id">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Deduct Salary</h4>
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
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
                  <table>
                    <tr>
                      <td>
                        <input type="number" name="amount" required id="payment_amount" readonly class="form-control payment_amount w100" placeholder="Amount">
                      </td>
                      <td></td>
                      <td> &nbsp;&nbsp;
                        <span><input type='radio' class='st' name='st' required>pinjam</span>
                        <span><input type='radio' class='st' name='st'>salary</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr><td>Particulars</td><td>
                <br>
                <?php
                  $filter = "trash=0";
                  $filter .= " AND show_in_hotel>0 ORDER BY show_in_hotel";
                  print sop2("bank", "", ['optional'=>true, 'attr'=>'required', 'filter'=>$filter]);
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
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modal-worker-details" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" autocomplete="off" enctype='multipart/form-data'>
          <div class="modal-body">
            <table class='table table-bordered'>
              <thead>
                <tr>
                  <th class="text-center">
                    <?php 
                      // if(isUserIn(['superadmin' , 'orange'])) {
                        print "<a class='pointer w100 btn btn-danger' data-toggle='modal' id='payment-button' data-target='#modal-worker-payment'>Payment</a>";
                      // }
                    ?>
                  </th>
                  <th class='modal-title text-center large' colspan='2'></th>
                  <th class='salary text-center large' colspan='3'></th>
                </tr>
                <tr>
                  <th class="text-center">Date</th>
                  <th class="text-center">Perticular</th>
                  <th class="text-center">Income</th>
                  <th class="text-center">Payment</th>
                  <th class="text-center">Balance</th>
                  <?php print "<th></th>"; ?>
                </tr>
              </thead>
              <tbody>
              </tbody>
              <!-- <tfoot>
                <tr>
                  <td colspan="4"><a class='pointer w100 btn btn-danger' data-toggle='modal' data-target='#modal-worker-payment'>Payment</a></td>
                </tr>
              </tfoot> -->
            </table>
          </div>
          <div class="modal-footer">
            <div class="row">
              <div class="col-md-8">
                <form method='post'>
                  <div class="row">
                    <div class="col-md-10">
                      <input type='hidden' name='id' value='' id='worker'>
                      <input type='text' name='worker_account' id='account' class='form-control' style='background: #efefef; font-weight:bolder;' placeholder="Bank & Account">
                    </div>
                    <div class="col-md-2">
                      <button type="submit" class="btn btn-success" name='save_worker_account'>Save</button>
                    </div>
                  </div>
                </form>
              </div>
              <div class="col-md-4">
                <button type="button" class="btn btn-info frht float-right" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>


  <?php } ?>
  <script>
    function setStaff(i){
      const row = $(".row-" + i);
      $("#staff_id").val(i);
      $("#staffAddModal #name").val(row.find(".sal-name").text());
      $("#staffAddModal #basic").val(row.find(".sal-basic").text());
      $("#staffAddModal #days").val(row.find(".sal-days").text());
    }

    function setStaffId(id){
      $(".modal .staff_id").val(id);
    }
    function pay(){
      names = "";
      // <tr id='worker-list-empty-row'><td></td><td></td></tr>
      name_count = $(".staff_salary_id:checked").length;
      $(".worker-list-item").remove();
      $($(".staff_salary_id:checked")).each(function (i,e) {
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
      var particulars = $("#modal-worker-payment-2 #_banking_date-2").val() + ' ' + text;
      // particulars += ' account theke banking kora hoyese Rm ' + val + ' ' + name + ' er account a ' + st + ' deoya hoyese, ';
      name_count = $(".worker-payment:checked").length;


      workers = ''
      if(name_count > 1){
        $($(".worker-id")).each(function(i,e){
          var amt = parseFloat($(e).val());
          if(!isNaN(amt)){
            workers += ", " + $(e).parent().parent().find('.w-name').text().trim() + ' Rm ' + amt;
          }
        });
      }

      if(name_count > 1){
        particulars += ' account theke ' + name + ' er account a <?php print isset($hotel) ? $hotel->name : ' '; ?> er ' + st + ' deoya hoyese Rm ' + val + workers;
      } else{
        particulars += ' account theke ' + name + ' ke <?php print isset($hotel) ? $hotel->name : ' '; ?> er ' + st + ' deoya hoyese Rm ' + val + workers;
      }

      console.log(particulars)

      $("#modal-worker-payment-2 .particulars").val(particulars);
    }

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
        <?php //if(!isuserin(['superadmin'])): ?>
          $("#modal-worker-details").find('#account').prop('disabled', true);
        <?php //endif; ?>
      }
      $("#modal-worker-details").find('.modal-title').html(name);
      $(".worker-name").html(name);
      $("#modal-worker-details").find('.salary').html('<div>Basic: &nbsp;&nbsp;' + salary + '</div><div>Extra: &nbsp;&nbsp;' + income + '</div><div>Total: &nbsp;&nbsp;' + total + '</div>');
      $.post("/store/ajax/staff_payment.php", {id:id, salary:salary, income:income, total:total}, function(data){
        $("#modal-worker-details").find("tbody").html(data);
      });
    }
  </script>
  <?php } ?>