<?php 
if(METHOD == 'add'){
 
} else{ 
  // if(isset($get->token)){
  //   $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
  //   if($token){
  //     R::trash($token);
  //     if(isset($get->del) && isset($get->id)){
  //       $st = R::load('stock_collect', $get->id);
  //       del("stock_collect_item", "stock_collect_id=".($get->id + 0));
  //       R::trash($st);
  //       redir("?s=$get->s");
  //     }
  //   }
  // }
  if(isset($post->incentive)){
    // vd($post);
    update("staff_salary", "incentive='$post->incentive'", "name='$post->name'");
  }
  if(isset($post->save_incentive)){
    $incentive = R::dispense("incentive");
    $incentive->salesman = $get->s;
    $incentive->date = $post->date;
    $incentive->particulars = $post->particulars;
    $incentive->amount = $post->amount;
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
      $staff->name = trim($post->name);
      $staff->category = 'Delivery Staff';
      $staff->incentive = $post->incentive;//$post->index;
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

  if(isset($post->save_return)){
    // vd($post);
    $ret = R::load("stock_collect_item", $post->stock_item_id);
    $ret->returned_quantity = $post->returning;
    $ret->damaged_quantity = nn($post->damaged) ? $post->damaged : 0;
    $ret->damaged_cause = $post->particulars;
    R::store($ret);
  }

  $month = isset($get->month) ? $get->month : date("Y-m-01");
  $contant = "";
  ?>
  <style type="text/css">
    
  .btn-return{
    min-width: 70px;
    padding: 0px 8px;
/*    margin-bottom: 4px;*/
  }
  .order-item{
    border: solid 1px #ccc;
/*    display: flex;*/
/*    justify-content: space-between;*/
  }
  .hidden-white{
    color: #fff;
  }
  .pad-right-5{
    padding-right: 5px;
  }
  .pending{
    color: red;
  }
  input.w64{
    border: transparent;
    background: #efe;
  }
  .damage-item{
    display: inline-block;
    border: transparent;
    background: #fff;
    color: #ff0000;
    font-weight: 700;
    width: 32px;
    text-align: center;
  }
  #selected-staff{
    font-weight: 700;
    font-size: 1.5rem;
  }
.cta-container {
  position: relative;
  display: inline-block;
}

.ctas {
  padding: 5px 10px;
  background-color: #f0f0f0;
  border-radius: 4px;
  cursor: pointer;
}

.cta-menu {
  position: absolute;
  bottom: 100%; /* Position the cta-menu above the span */
  left: 50%;
  transform: translateX(-50%);
  display: none; /* Hide by default */
  background-color: white;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 5px;
  box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
  z-index: 10;
}

.cta-menu i {
  margin: 5px;
  cursor: pointer;
  color: #555;
  font-size: 14px;
}

.cta-menu i:hover {
  color: #007bff;
}

.ctas:hover + .cta-menu,
.cta-menu:hover {
  display: block; /* Show the cta-menu on hover */
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
                <th>  
                  <?php
                    if(isset($get->s) && $get->s != 'all') print "<span id='selected-staff'>$get->s</span>";
                  ?>
                </th>
                <th>Stock Returned</th>
                <th>Incentive</th>
                <!-- <th>Damaged</th> -->
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
$filter = "WHERE delivery_staff='$get->s'";
if($get->s == "all") {
  $filter = "";
}
$trans = select("SELECT * FROM (SELECT * FROM (
  SELECT 'stock_collect' src, id, delivery_staff, date, created_at, delivery_staff delivered_by, stockCollectItemsMini(id) particulars, (SELECT SUM(quantity) FROM `stock_collect_item` ii WHERE ii.stock_collect_id=stock_collect.id) profit, stockCollectItemsQty(id) particulars2, stockCollectItemsQtyDelivered(id) particulars3, stockCollectItemsQtyToReturn(id) particulars4,stockCollectItemsQtyPending(id) particulars5 FROM `stock_collect` $filter
) a ORDER BY created_at $limit) b");

/*

  SELECT 'invoice' src, id, delivery_staff, delivery_date date, created_at, delivered_by, invoiceItems2(id) particulars, (SELECT SUM(quantity) FROM `invoice_item` ii WHERE ii.invoice_id=invoice.id) profit, deliveredItems(id) particulars2 FROM `invoice` $filter
UNION
  */

$i = 1;
while ($tran = mysqli_fetch_object($trans)) {
  $stock = select("ii.*, p.name, pv.unit, pv.size, (SELECT IFNULL(SUM(IFNULL(quantity,0)),0) FROM `invoice_item` WHERE DATE(invoice_item.delivered_at)=CURDATE() 
AND invoice_item.product_variance_id=ii.product_variance_id) delivered", "`stock_collect_item` ii, `product_variance` pv, product p", "pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_collect_id=$tran->id");
  print "<tr>";
  print "<td>$i</td>";
  print "<td>".df($tran->date)."</td>";
  print "<td class='text-center'><div class='cta-container'><span class='ctas'>".($tran->src == 'invoice' ? 'INV' : 'STC').zerofill($tran->id, 5)."</span><div class='cta-menu'><a href='?s={$get->s}&del&id=$tran->id' class='protected-link'><i class='fas fa-trash-alt'></i></a></div></div></td>";
  $particular = $collect = $delivered = $incentive = $pending = $damaged = $returened = "";
  while ($item = mysqli_fetch_object($stock)) {
    $particular .= "<div class='order-item'>$item->name $item->description $item->size x $item->unit</div>";
    // $collect .= "<div class='order-item'>$item->quantity</div>";
    $returened .= "<div class='order-item'>".($item->quantity - $item->returned_quantity)."</div>";
    $incentive .= "<div class='order-item'>0</div>";
    // $returened .= "<div class='order-item'><input value='".($item->returned_quantity ? $item->returned_quantity : $item->quantity-$item->delivered)."' id='return-{$item->id}' class='w64'> <span class='damage-item' title='$item->damaged_cause'>".($item->damaged_quantity ? $item->damaged_quantity : '&nbsp;')."</span> <span class='pad-right-5'></span><a data-bs-toggle='modal' data-bs-target='#stockReturn' onClick='setId($item->id)' data-quantity class='btn btn-return btn-sm btn-".($item->returned_quantity==0?'warning':'success')."' data-id='$item->id'><i class='fas fa-people-carry'></i></a></div>";
    // $returened .= "<div class='order-item'><input value='".($item->returned_quantity)."' id='return-{$item->id}' class='w64'> <span class='damage-item hidden' title='$item->damaged_cause'>".($item->damaged_quantity ? $item->damaged_quantity : '&nbsp;')."</span> <span class='pad-right-5'></span><a data-bs-toggle='modal' data-bs-target='#stockReturn' onClick='setId($item->id)' data-quantity class='btn btn-return btn-sm btn-".($item->returned_quantity==0?'warning':'success')."' data-id='$item->id'><i class='fas fa-people-carry'></i></a></div>";
    // $pending .= "<div class='order-item' title='$item->damaged_cause'><b class='damage-item'>".($item->damaged_quantity)."</b></div>";
    // $pending .= "<div class='order-item' title='$item->quantity-$item->returned_quantity-$item->delivered-$item->damaged_quantity'>".($item->quantity-$item->returned_quantity-$item->delivered-$item->damaged_quantity)."</div>";
    // print "<td class='text-center'><div class='order-item'>$tran->particulars2</div></td>";
    // print "<td class='text-center'>$tran->particulars3</td>";
    // print "<td class='text-right'>$tran->particulars4</td>";
    // print "<td class='text-center'>$tran->particulars5</td>";
  }

  print "<td>$particular</td>";
  // print "<td class='text-center'>$collect</td>";
  print "<td class='text-center'>$returened</td>";
  print "<td class='text-center'>$incentive</td>";
  // print "<td class='text-center'>$pending</td>";
  sum('profit', $tran->profit);
  sum('incentive', $tran->profit * (5 / 100));
  sum('balance', $tran->profit * (5 / 100));
  // print "<td class='text-right'>";
  // print "<div>100".space(10)."<span class='btn btn-return btn-sm btn-warning'><i class='fas fa-people-carry'></i></span></div>";
  // print "<div>100".space(10)."<span class='btn btn-return btn-sm btn-warning'><i class='fas fa-people-carry'></i></span></div>";
  // print "<div>100".space(10)."<span class='btn btn-return btn-sm btn-warning'><i class='fas fa-people-carry'></i></span></div>";
  // print "<div>100".space(10)."<span class='btn btn-return btn-sm btn-warning'><i class='fas fa-people-carry'></i></span></div>";
  // print "<div>100".space(10)."<span class='btn btn-return btn-sm btn-warning'><i class='fas fa-people-carry'></i></span></div>";
  // print "</td>";
  print "</tr>";
  $i++;
}
// print "<tr>
//   <td colspan='2'></td>
// <th></th>
// <th class='text-right'>TOTAL</th>
// <th class='text-right'>".nf(sum('profit'))."</th>
// <th class='text-right'>".nf(sum('incentive'))."</th>
// <th class='text-right'>".nf(sum('credit'))."</th>
// <th class='text-right'>".nf(sum('balance'))."</th>
// </tr>";

print "</tbody>";

                    print "</tbody>
                  </table>";
              ?>
            </tbody>
            <tfoot></tfoot>
          </table>
          <?php
          } else{
            $objs = select('distinct id, name, incentive', 'staff_salary', "category='Delivery Staff'");
            print "<div class='card-header'>
            <div class='row'>
            <div class='col-6'><h5>Staff</h5></div>
            <div class='col-6 text-center'>
            </div>
            </div>
            <div class='card-body'>
            <div class='dt-responsive table-responsive'>
            <table id='simpletable' class='table table-striped table-bordered nowrap'>
            <thead>
            <tr>
            <th class='w100'>No</th>
            <th>Name</th>
            <th class='w150'>Incentive %</th>
                        <th class='w100'><span data-bs-toggle='modal' data-bs-target='#productFrommOdal' class='frht btn btn-sm btn-primary'><i class='fas fa-plus'></i> Staff</span></th>

            </tr>
            </thead>
            <tbody>";
            $i = 1;
            while ($obj = mysqli_fetch_object($objs)) {
              print "<tr>";
              print "<td>$i</td>";
              print "<td class='name'><a href='?s=$obj->name'><u>$obj->name</u></a></td>";
              // print "<td class='name'><u>$obj->name</u></a></td>";
              print "<td class='incentive' data-name='$obj->name'>$obj->incentive</td>";
              print "<td><a class='btn btn-primary btn-sm' data-id='$obj->id' data-name='$obj->name' data-incentive='$obj->incentive' onClick='setStaff(this)' data-bs-toggle='modal' data-bs-target='#productFrommOdal'><i class='fas fa-edit'></i></a> <a href='?del&id=$obj->id' class='protected-link btn btn-danger btn-sm'><i class='fas fa-trash'></i></a> </td>";

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

  
        <div id="stockReturn" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="stockreTurnLabel" aria-hidden="true">
          <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="stockreTurnLabel">Return<span></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  <input type='hidden' name='stock_item_id' id='stock_item_id' value=''>
                </div>
                <div class="modal-body">
                  <div class='row'>
                    <div class='col-sm-6'>              
                      <div class='form-group'>
                        <lable>Returning Quantity</lable>
                        <input class='form-control' type="number" step="1" required name='returning' id="returning-quantity" data-quantity=''>
                      </div>
                    </div>
                    <div class='col-sm-6'>              
                      <div class='form-group'>
                        <lable>Damaged/Lost </lable>
                        <input class='form-control' type="number" step='1' name='damaged' id="damaged-quantity">
                      </div>
                    </div> 
                    <div class='col-sm-12'>              
                      <div class='form-group hidden' id="damaged-particulars">
                        <lable>Paticulars & Settlement</lable>
                        <textarea class='form-control' name='particulars'></textarea>
                      </div>
                    </div>     
                  </div>
                  <br>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" name='save_return' class="btn btn-primary">Save changes</button>
                </div>
              </div>
            </div>
          </form>
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
                    <lable>Name</lable>
                      <input class='form-control' required name='name' id="name">
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
            const name = $(el).data('name');
            const incentive = $(el).data('incentive');

            console.log(id, name, incentive);
            $(".id").val(id);
            $("#name").val(name);
            $("#incentive").val(incentive);
          }
        </script>
  <?php } ?>

  

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

    function setId(id) {
      const ret = $("#return-" + id).val();
      $("#stock_item_id").val(id);
      $("#returning-quantity").data('quantity', ret);
      // $("#returning-quantity").val(ret);
      $("#returning-quantity").val(0);
    }

    $("#damaged-quantity").keyup(function(){
      const val = parseInt($(this).val());
      if(!isNaN(val) && val > 0){
        $("#damaged-particulars").removeClass('hidden');
      } else{
        $("#damaged-particulars").find('textarea').val('');
        $("#damaged-particulars").addClass('hidden');
      }
    });

    $(".btn-return").click(function(){
      const id = $(this).data('id');
      const quantity = $(this).parent().text();
      if(quantity != "0"){
        // $(this).addClass('btn-success');
        // $(this).removeClass('btn-warning');
        // $.post("/store/ajax/return.php", {id:id, quantity: quantity}, function(res){
        //   // console.log(res);
        //   // $("#items").html(res);
        // });
      }
    });
    $(".supplier-select").change(function(){
      const val = $(this).val();
      location.href = "?s=" + val;
    });
  </script>