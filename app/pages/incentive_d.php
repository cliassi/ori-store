<?php 
if(METHOD == 'add'){
 
} else{ 
  if(isset($post->incentive)){
    // vd($post);
    update("staff_salary", "incentive='$post->incentive'", "name='$post->name'");
  }
  if(isset($post->save_incentive)){
    $incentive = R::dispense("incentive");
    $incentive->salesman = $get->s;
    $incentive->incentive = $post->incentive;
    // $incentive->particulars = $post->particulars;
    // $incentive->amount = $post->amount;
    $incentive->created_by = uid();
    $incentive->created_at = now();

    R::store($incentive);
  }
  if(isset($get->token)){
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if($token){
      R::trash($token);
      if(isset($get->del) && isset($get->id)){
        $st = R::load('staff_salary', $get->id);
        R::trash($st);
        redir("?");
      }
    }
  }
  if (isset($post->save)) {
    try {
      if(isset($post->id)){
        $staff = R::load('staff_salary', $post->id);
      } else{
        $staff = R::dispense('staff_salary');
      }
      $staff->user_id = $post->user_id;
      $staff->name = $post->name;
      $staff->category = 'Delivery Staff';
      $staff->incentive = $post->incentive;
      R::store($staff);

      if (count($_FILES) > 0) {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
          $file = upload($_FILES, 'image' . $staff->id . "-" . time(), 'uploads', 'image');
          $staff->image = "uploads/$file";
        }
        if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
          $file = upload($_FILES, 'logo' . $staff->id . "-" . time(), '../uploads', 'logo');
          $staff->logo = "uploads/$file";
        }
        R::store($staff);
      }
          // print "<script>location.href = '".ROOT."/product'; </script>";
    } catch (\Throwable $th) {
      dump($th);
    }
  }
  //End Save

  $month = isset($get->month) ? $get->month : date("Y-m-01");
  $contant = "";
  ?>
  <style type="text/css">
    
  #selected-staff{
    font-weight: 700;
    font-size: 1.5rem;
  }
  </style>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <?php
          if(isset($get->s)){
          ?>
          <table class='table table-striped table-bordered nowrap'>
            <thead>
              <tr>
                <th>No.</th>
                <th>Date</th>
                <th>Ref</th>
                <th><?php print "<span id='selected-staff'>$get->s</span>"; ?> <span data-bs-toggle='modal' data-bs-target='.payment' class='frht btn btn-sm btn-primary'>Payment</span></th>
                <th>Company<br>Profit</th>
                <th>Staff<br>Incentive</th>
                <th>Credit</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php
print "<tbody>";
if(isset($get->show) && $get->show == "all"){
  $limit = "";
} else{
  $limit = " limit 0,10";
}
$trans = select("SELECT * FROM (SELECT * FROM (
  SELECT 'invoice' src, id, invoice_date date, created_at, incentive, delivered_by, invoiceItems2(id) particulars, (SELECT SUM((price-cost) * quantity) FROM `invoice_item` ii WHERE ii.invoice_id=invoice.id) profit FROM `invoice` WHERE salesman='$get->s'
) a ORDER BY created_at $limit) b");

$i = 1;
while ($item = mysqli_fetch_object($trans)) {
  print "<tr>";
  print "<td>$i</td>";
  print "<td>".df($item->date)."</td>";
  print "<td>INV".zerofill($item->id, 5)."</td>";
  print "<td>$item->particulars</td>";
  print "<td class='text-right'>".nf($item->profit)."</td>";
  print "<td class='text-right'>".nf($item->profit * ($item->incentive / 100))."</td>";
  print "<td class='text-right'></td>";
  sum('profit', $item->profit);
  sum('incentive', $item->profit * (5 / 100));
  sum('balance', $item->profit * (5 / 100));
  print "<td class='text-right'>".nf(sum('balance'))."</td>";
  print "</tr>";
  $i++;
}
$ics = R::find("incentive");
foreach ($ics as $key => $item) {
  print "<tr>";
  print "<td>$i</td>";
  print "<td>".df($item->date)."</td>";
  print "<td>INCP".zerofill($item->id, 5)."</td>";
  print "<td>$item->particulars</td>";
  print "<td class='text-right'></td>";
  print "<td class='text-right'></td>";
  print "<td class='text-right'>$item->amount</td>";
  sum('balance', -$item->amount);
  print "<td class='text-right'>".nf(sum('balance'))."</td>";
  print "</tr>";
  $i++;
}
print "<tr>
  <td colspan='2'></td>
<th></th>
<th class='text-right'>TOTAL</th>
<th class='text-right'>".nf(sum('profit'))."</th>
<th class='text-right'>".nf(sum('incentive'))."</th>
<th class='text-right'>".nf(sum('credit'))."</th>
<th class='text-right'>".nf(sum('balance'))."</th>
</tr>";

print "</tbody>";

                    print "</tbody>
                  </table>";
              ?>
            </tbody>
            <tfoot></tfoot>
          </table>
          <?php
          } else{
            $objs = select('distinct ss.id, su.id as user_id, su.u_fullname, ss.name, ss.incentive', 'staff_salary ss LEFT JOIN sys_user su ON ss.user_id = su.id', "ss.category='Delivery Staff'");
            print "<div class='card-header'>
            <div class='row'>
            <div class='col-6'><h5>Staff</h5></div>
            <div class='col-6 text-right'>
            </div>
            <div class='card-body'>
            <div class='dt-responsive table-responsive'>
            <table id='simpletable' class='table table-striped table-bordered nowrap'>
            <thead>
            <tr>
            <th class='w100'>No</th>
            <th>Name</th>
            <th>Username</th>
            <th class='w150'>Incentive %</th>
            <th class='w100'><span data-bs-toggle='modal' data-bs-target='#productFrommOdal' class='frht btn btn-sm btn-primary' onclick='clearForm()'><i class='fas fa-plus'></i> Staff</span></th>
            </tr>
            </thead>
            <tbody>";
            $i = 1;
            while ($obj = mysqli_fetch_object($objs)) {
              print "<tr>";
              print "<td>$obj->id</td>";
              print "<td class='name'><a href='?s=$obj->u_fullname'><u>$obj->name</u></a></td>";
              print "<td class='username'>$obj->u_fullname</td>";
              print "<td class='incentive' data-name='$obj->u_fullname'>$obj->incentive</td>";
              print "<td><a class='btn btn-primary btn-sm' data-id='$obj->id' data-user-id='$obj->user_id' data-name='$obj->name' data-username='$obj->u_fullname' data-incentive='$obj->incentive' onClick='setStaff(this)' data-bs-toggle='modal' data-bs-target='#productFrommOdal'><i class='fas fa-edit'></i></a> <a href='?del&id=$obj->id' class='protected-link btn btn-danger btn-sm'><i class='fas fa-trash'></i></a> </td>";
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

  
        <div id="productFrommOdal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productFrommOdalLabel" aria-hidden="true">
          <form class="forms-sample" method="post" enctype="multipart/form-data">
            <input type='hidden' name='id' class='id'>
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="productFrommOdalLabel">Add Staff <span></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class='form-group'>
                    <lable>Staff User</lable>
                      <select class='form-control' required name='user_id' id="user_id">
                        <option value="">Select Staff</option>
                        <?php
                        $users = select('id, u_fullname', 'sys_user', "u_status=1");
                        while ($user = mysqli_fetch_object($users)) {
                          echo "<option value='$user->id'>$user->u_fullname</option>";
                        }
                        ?>
                      </select>
                  </div>
                  <div class='form-group'>
                    <lable>Staff Name</lable>
                      <input class='form-control' required name='name' id="name" placeholder="Enter staff name">
                  </div>
                  <br><div class='row'>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>Incentive</lable>
                        <input class='form-control' type="number" step="1" required name='incentive' id="incentive">
                      </div>
                    </div>
                  </div>
                  <!-- <div class='row'>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>Basic Salary</lable>
                        <input class='form-control' type="number" step="1" required name='basic' id="size">
                      </div>
                    </div>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>Days</lable>
                        <input class='form-control' type="number" step='1' name='days' id="days">
                      </div>
                    </div><div class='col-sm-6'>
                        <lable>Staff Photo</lable>
                      <input type='file' class='form-control' required  name='image' id="image">
                    </div>
                  </div> -->
                  <br>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" name='save' class="btn btn-primary">Save changes</button>
                </div>
              </div>
            </div>
          </form>
        </div>    

        <script type="text/javascript">
          function setStaff(el){
            const id = $(el).data('id');
            const userId = $(el).data('user-id');
            const name = $(el).data('name');
            const username = $(el).data('username');
            const incentive = $(el).data('incentive');

            console.log(id, userId, name, username, incentive);
            $(".id").val(id);
            $("#user_id").val(userId);
            $("#name").val(name);
            $("#incentive").val(incentive);
            
            // Update modal title to show we're editing
            $("#productFrommOdalLabel").html("Edit Staff <span>(" + name + ")</span>");
          }
          
          function clearForm(){
            // Clear all form fields for adding new staff
            $(".id").val('');
            $("#user_id").val('');
            $("#name").val('');
            $("#incentive").val('');
            
            // Update modal title for adding
            $("#productFrommOdalLabel").html("Add Staff <span></span>");
          }
        </script>
  <?php } ?>

  <form method="post" id='save_incentive'>
    <input name='name' id='ff-name' type="hidden">
    <input name='incentive' id='ff-incentive' type="hidden">
  </form>

  <div class="modal fade payment" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">Add Payment</h4>
        </div>
        <form method="post">
        <div class="modal-body">
          <table class="table table-bordered">
            <tr><th>Date</th><th><?php print ds('date'); ?></th></tr>
            <tr><th>Amount</th><th><input type='number' class='form-control' onkeyup="setPart()" required name='amount' id="amount" min="1" ></th></tr>
            <tr><th>Particulars</th><th>
              <select id='month' class="form-control">
                <?php for($i = 1; $i <= 12; $i++) print "<option>".date("F", strtotime("2020-$i-01"))."</option>"; ?>
              </select>
              <div id="hints">
               <!-- <div><input type='radio' name='r'>Madam er may bank  personal account theke petty cash a taka add cash kora hoyese Rm </div> -->
              </div>
              <br>
              <textarea class='form-control' id='particulars' name='particulars'>mase incentive add kora hoyese RM </textarea>
            </th></tr>
          </table>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit" name="save_incentive">Save</button>
          <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div>

  <script type="text/javascript">
    $(".incentive").on('dblclick', function(){
      let name = $(this).data('name');
      let inc = prompt("Enter incentive % for " + name);
      let num = parseFloat(inc);
      if(!isNaN(num)){
        $("#ff-name").val(name);
        $("#ff-incentive").val(num);
        $("#save_incentive").submit();
      }
    });
    $("#month").change(setPart);
    function setPart(){
      const month = $("#month option:selected").val();
      const part = month + " mase incentive add kora hoyese RM " + $("#amount").val();
      $("#particulars").val(part);
    }
  </script>