<style type="text/css">
  a.btn{
    color:#fff !important;
  }
  .d-icon{
    min-width: 70px;
    padding: 0px 8px;
    margin-bottom: 4px;
  }
  .table-items th,.table-items td{
    border-bottom: solid 1px #ccc !important;
  }
</style>
<?php
$obj = R::dispense('customer');
if (defined('ID')) {
    $obj = R::load('customer', ID);
}

if(isset($post->idToDelete)){
  $inv = R::load("invoice", $post->idToDelete);
  del("invoice_item", "invoice_id='$inv->id'");
  R::trash($inv);
}
if(isset($post->idToDelete2)){
  $col = R::load("collection", $post->idToDelete2);
  R::trash($col);
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

if(isset($post->file_to_remove) && isset($post->pin)){
  $user = R::load("sys_user", uid());
  if($user->u_pin == $post->pin){
    $new_path = "archive/". substr($post->file_to_remove, 0, strrpos($post->file_to_remove, "/"));
    mkdir2($new_path);
    // vd($post->file_to_remove);
    // dd($new_path);
    rename("$post->file_to_remove", "archive/$post->file_to_remove");
  }
}
if(isset($post->folder_to_remove) && isset($post->pin)){
  $user = R::load("sys_user", uid());
  if($user->u_pin == $post->pin){
    $new_path = "archive/".time()."/". substr($post->folder_to_remove, 0, strpos($post->folder_to_remove, "/files") + 7);
    mkdir2($new_path);
    // dd("$post->folder_to_remove archive/$post->folder_to_remove $new_path");
    rename_win("$post->folder_to_remove", "archive/".time()."/$post->folder_to_remove");
  }
}

if(isset($post->file_to_rename) && isset($post->rename_to)){
  $ext = ext($post->file_to_rename);
  $newname = $post->root_path.'/'.$post->rename_to.$ext;
  rename($post->file_to_rename, $newname);
  redir("?dir=".$get->dir);
  // vd($ext);
  // vd($post);
}

if(isset($post->file_to_move) && isset($post->move_to)){
  //src, dest
  // dd($post);
  rcopy($post->file_to_move, $post->move_to."/".$post->name);
  rrmdir($post->file_to_move);
  redir("?dir=".$get->dir);
}

$dir = "uploads/customer/$obj->id";
$folder = isset($get->dir)?$get->dir:'files';

if(isset($post->name) && count($_FILES)){
  $name = $post->name?$post->name:'';
  $name = upload($_FILES, $name, "$dir/$folder");
}

if(isset($post->create_folder)){
  if(isset($get->dir)){
    mkdir2("$dir/$folder/$post->folder");
  }
}


//END FILE OPS


if(isset($post->save_remarks)){
  $customer_remarks = R::dispense("customer_remarks");
  $customer_remarks->customer_id = $obj->id;
  $customer_remarks->notes = $post->remarks;
  $customer_remarks->priority = 'high';
  $customer_remarks->entry_by = uid();
  R::store($customer_remarks);
  redir("?");
}

if(isset($get->delivered)){
  $invoice = R::load('invoice', $get->delivered);
  $invoice->delivered_by = uid();
  $invoice->delivery_date = now();
  R::store($invoice);
  redir("?");
}

if(isset($get->approved)){
  $collection = R::load('collection', $get->approved);
  $collection->approved_by = uid();
  $collection->approved_at = now();
  R::store($collection);
  redir("?");
}

print "
<div class='row'>
          <!-- Zero config table start -->
          <div class='col-sm-12'>
            <div class='card'>
              <div class='card-header text-center'>
                <h5>$obj->company $obj->code</h5>
                <div><img src='".ROOT."/$obj->image' height='64px'></div>
                <h5>$obj->contact</h5>
                <h5><a class=' px-5 mb-1' href='tel:$obj->mobile'>$obj->mobile</a></h5>
              </div>
              <div class='card-body'>
                <div class='dt-responsive table-responsive'>
                  <table id='simpletable' class='table table-striped table-bordered nowrap'>
                    <thead>
                      <tr>
                        <th class='text-center'><a class='btn btn-success' href='/store/product/sell'>Order</a></th>
                        <th class='text-center'><a class='btn btn-warning' href='../../collection/add?customer=".ID."'>Collection</a></th>
                        <th class='text-center'><a class='btn btn-danger'>Refund</a></th>
                      </tr>
                    </thead>
                    <tbody>";

print "<table class='table table-striped table-bordered nowrap'>";
print "<thead>";
print "<tr>";
print "<th># </th>
        <th>Date  </th>
        <th>Ref No. </th>
        <th>Particulars           </th>
        <th>Prepared By </th>
        <th>Approve ? </th>
        <th>Debit </th>
        <th>Credit  </th>
        <th>Balance</th>
        <th width='50px'></th>";
print "</tr>";
print "</thead>";
print "<tbody>";
if(isset($get->show) && $get->show == "all"){
  $limit = "";
} else{
  $limit = " limit 0,10";
}
$trans = select("SELECT * FROM (SELECT * FROM (
  SELECT 'invoice' src, id, invoice_date date, created_at, delivered_by, '' particulars, (SELECT SUM(price * quantity) FROM `invoice_item` ii WHERE ii.invoice_id=invoice.id) amount FROM `invoice` WHERE customer_id=$obj->id
  UNION
  SELECT 'collection' src, id, date, created_at, approved_by delivered_by, description particulars, amount FROM `collection` WHERE customer_id=$obj->id
) a ORDER BY created_at $limit) b");

$i = 1;
while ($item = mysqli_fetch_object($trans)) {
  print "<tr>";
  print "<td>$i</td>";
  print "<td>".df($item->date)."</td>";
  if($item->src == 'invoice'){
    print "<td>INV".zerofill($item->id, 5)."</td>";
  } else{
    print "<td>OR".zerofill($item->id, 5)."</td>";
  }
  if($item->src == 'invoice'){
    $ois = R::find("invoice_item", "invoice_id=?", [$item->id]);
    print "<td>";
    print "<table class='table table-bordered table-items'><tr><th><input type='checkbox' class='toggle-checkbox'></th><th colspan='2'>";
print " Delivery by <span><select class='form-select supplier-select inline-block w150' name='salesman'>
      <option value=''>Please select</option>";
      $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
      while ($man = mysqli_fetch_object($objs)) {
        print "<option ";
        // if($man->name == $sm) print "selected";
        print ">$man->name</option>";
      }
  print "</select></span>";
    print "</th></tr>";
    $k = 1;
    foreach($ois as $oi){
      $pv = R::load("product_variance", $oi->product_variance_id);
      print "<tr>
        <td><input type='checkbox'></td>
        <td><div class='order-item'><span class='item-count'>$k.</span> ($oi->name $oi->description $pv->size x $pv->unit) <span class='item-price'>($oi->price X <span class='item-qty'> $oi->quantity</span> = ($oi->quantity*$oi->price)</span></div></td>
        <td title='$oi->delivered_by'><span class='d-icon btn btn-sm ".($oi->delivered_by ? "btn-success":"btn-warning")."' data-id='$oi->id'><i class='fas fa-shipping-fast'></i></span></td>
      </tr>";
      $k++;
    }
    print "</table>";
    print "</td>";
  } elseif(strrpos($item->particulars, 'bank account') !== FALSE){
  print "<td>$item->particulars</td>";
  } else{
    print "<td></td>";
  }
  print "<td></td>";
  if($item->src == 'invoice'){
    print "<td class='text-right'>".nf($item->amount)."</td>";
    print "<td></td>";
    sum('balance',$item->amount);
    sum('debit',$item->amount);
  } else{
    print "<td></td>";
    print "<td class='text-right'>".nf($item->amount)."</td>";
    sum('balance',0 - $item->amount);
    sum('credit',$item->amount);
  }
  print "<td class='text-right'>".nf(sum('balance'))."</td>";
  print "<td>";
  if($item->src == 'invoice'){
  print "<a type='button' class='btn btn-sm btn-warning' href='".ROOT."/invoice/print/$item->id' ><i class='fas fa-print'></i></a><br>";
  print "<a type='button' class='btn btn-sm btn-info pb-1' href='".ROOT."/invoice/edit/$item->id' ><i class='fas fa-edit'></i></a><br>";
    print "<button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation($item->id)'><i class='fas fa-trash'></i></button>";
  } else{
    if(uid() == 1){
      if($item->src == 'collection'){
        print "<a type='button' class='btn btn-sm btn-info pb-1' href='".ROOT."/collection/edit/$item->id'><i class='fas fa-edit'></i></a> ";
      }
      print "<button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation2($item->id)'><i class='fas fa-trash'></i></button>";
    }
  }
  print "</td>";

  //deleteConfirmation
  //deleteConfirmation2
  print "</tr>";
  $i++;
}
print "<tr>
  <td colspan='2'><a class='btn btn-info' href='javascript:addRemarks()'>Remarks</a></td>
  <td><a href='?dir=files' class='btn btn-success'>Files</a></td>
<td>";
if(isset($get->show) && $get->show == "all"){
  print "<a href='?show=10' class='btn btn-shadow btn-light' style='color: #000 !important'>Show last 10</a>";
} else{
  print "<a href='?show=all' class='btn btn-shadow btn-light' style='color: #000 !important'>Show all</a>";
}
print "
<th class='text-right'>TOTAL</th>
<th class='text-right'>".nf(sum('debit'))."</th>
<th class='text-right'>".nf(sum('credit'))."</th>
<th class='text-right'>".nf(sum('balance'))."</th>
<th></th>
</tr>";

print "</tbody>";

                    print "</tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>";

$dir = "uploads/customer/$obj->id";
    $folder = isset($get->dir)?$get->dir:'files';

    if(count($_FILES)){
      $name = $post->name?$post->name:'';
      $name = upload($_FILES, $name, "$dir/$folder");
    }

    if(isset($post->create_folder)){
      if(isset($get->dir)){
        mkdir2("$dir/$folder/$post->folder");
      }
    }

    print "<div style='margin: 0 30px;'>";
    print "<div class='col-md-9' id='files'>";
    if(file_exists($dir."/$folder")){
      mkdir2($dir."/$folder");
    }
    if(isset($get->dir)){
      if(file_exists($dir."/$folder")){
        $files = scandir("$dir/$folder");
        $i = 1;

        $dirlinks = "";
        $filelinks = "";
        
        foreach($files as $file){
          if($file!="." && $file != ".."){
            if(is_dir("$dir/$folder/$file")){
              $dirlinks .= "<div class='col-md-4 file-folder' style='padding-bottom: 3px;' data-path='$dir/$folder/$file' data-name='$file' data-root='$dir/$folder'><a style='width: calc(100% - 36px);text-align:left;font-size:12px;' href='?dir=$folder/$file' class='btn btn-warning'><i class='fa fa-folder'></i>  $file</a> <a data-path='$dir/$folder/$file' class='btn btn-sm btn-danger remove-folder'><i class='fa fa-times'></i></a></div>";  
            }
          }
        }
        foreach($files as $file){
          if($file!="." && $file != ".."){
            if(!is_dir("$dir/$folder/$file")){
              $filelinks .= "<div class='col-md-4 file-file' style='padding-bottom: 3px;' data-path='$dir/$folder/$file' data-name='$file' data-root='$dir/$folder'><a style='width: calc(100% - 36px);text-align:left;font-size:12px;' target='_blank' href='../../$dir/$folder/$file' class='btn btn-success'>$i. $file</a> <a data-path='$dir/$folder/$file' class='btn btn-sm btn-danger remove-file'><i class='fa fa-times'></i></a></div>"; 
              $i++;
            }
          }
        }

        print "<div class='row'>$dirlinks</div>";
        print "<div class='row'>$filelinks</div>";
      }
      $pwd = substr($folder, 0, strrpos($folder, "/"));
      if($pwd){
        print "<br><a href='?dir=$pwd' class='btn btn-danger'><< BACK</a> ";  
      } else{
        print "<br><a href='?' class='btn btn-danger'><< BACK</a> ";
      }
      
    } else{
      // print "<a href='?dir=files' class='btn btn-success'>Files</a> ";
    }
    print "</div>";
    if(isset($get->dir)){
    print "<div class='col-md-3'>";
    openForm("post", true);
      print "<input type='text' name='folder' />Create Folder<br>";
    print "<button name='create_folder' class='btn btn-warning'>Create</button></form>";
    print "<br>";
    // print "</div>";
    // print "<div class='col-md-3'>";
    openForm("post", true);
      print "<input type='text' name='name' />";
      print "<input type='file' name='file' />";
    print "<button name='upload_file' class='btn btn-success'>Upload</button></form>";
    print "</div>";
  print "</div>";
}


$remarks = select("*", "customer_remarks", "customer_id=$obj->id AND trash=0 AND entry_by=1", "ORDER BY id DESC");

  while($remark = mysqli_fetch_object($remarks)){
    $priority = "info";
    if($remark->priority == 'High'){
      $priority = 'danger';
    } elseif($remark->priority == 'Low'){
      $priority = 'success';
    }
    print "<div class='alert alert-sm alert-$priority'>$remark->notes ";
    if(uid()== 1) print "<a href='?rm=$remark->id'><i class='fa fa-trash frht pointer'></i></a>";
    print "</div>";
  }

  $remarks = select("*", "customer_remarks", "customer_id=$obj->id AND trash=0 AND entry_by<>1", "ORDER BY id DESC");

  while($remark = mysqli_fetch_object($remarks)){
    $priority = "info";
    if($remark->priority == 'High'){
      $priority = 'danger';
    } elseif($remark->priority == 'Low'){
      $priority = 'success';
    }
    print "<div class='alert alert-danger d-flex align-items-center' role='alert'>
 
  <div>$remark->notes</div><a class='frht' href='?rm=$remark->id'><i class='fa fa-trash  pointer'></i></a></div>";
  }
  

?>

<form method="post" id="save_remarks_form" mehtod="post">
  <input type="hidden" name='remarks' id='remarks'>
  <input type="hidden" name='save_remarks' id='save_remarks'>
</form>

<form method="post" id="remove-form" mehtod="post">
  <input type="hidden" name='file_to_remove' id='file-to-remove'>
  <input type="hidden" name='pin' id='pin'>
</form>


<form method="post" id="folder-remove-form" mehtod="post">
  <input type="hidden" name='folder_to_remove' id='folder-to-remove'>
  <input type="hidden" name='pin' id='folder-pin'>
</form>

<form method="post" id="rename-file-form" mehtod="post">
    <input type="hidden" name='file_to_rename' id='file_to_rename'>
    <input type="hidden" name='rename_to' id='rename_to'>
    <input type="hidden" name='root_path' id='root_path'>
</form>

<form method="post" id="move-file" mehtod="post">
    <input type="hidden" name='file_to_move' id='file_to_move'>
    <input type="hidden" name='move_to' id='move_to'>
    <input type="hidden" name='name' id='file_to_move_name'>
</form>


<script type="text/javascript">
  function addRemarks(){

    <?php //if(uid() != 1): ?>
    setTimeout(function(){
      $(".swal2-textarea").after("<div id='remarks_hints' style='margin-left:30px'></div>");
      $("#remarks_hints input[type='radio']").click(function(){
        var text = $(this).parent().text();
        $(".swal2-textarea").val(text);
      })
    }, 500);


    
    <?php //endif; ?>

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

  $(".toggle-checkbox").change(function(){
    const state = $(this).prop('checked');
    $(this).parent().parent().parent().find("input").prop('checked', state);
  });

  $(".d-icon").click(function(){
    $(this).addClass('btn-success');
    $(this).removeClass('btn-warning');
    const id = $(this).data('id');
    const delivered_by = $(".supplier-select").val();
    $.post("/store/ajax/save_state.php", {id:id,delivered_by:delivered_by}, function(res){
      console.log(res);
      $("#items").html(res);
    });
  });

/*
  
  $('#files').contextMenu({
        selector: 'div', 
        callback: function(key, options) {
            if(key == 'rename'){
                var newName = prompt("Enter new name");
                $("#file_to_rename").val($(this).data('path'));
                $("#root_path").val($(this).data('root'));
                $("#rename_to").val(newName);
                $("#rename-file-form").submit();
            } else if(key == 'cut'){
                $("#file_to_move").val($(this).data('path'));
                $("#file_to_move_name").val($(this).data('name'));
                $(this).remove();
                $(".file-file").css('opacity', '.2');
                $(".file-folder").click(function(e){
                    e.preventDefault();
                    var path = $(this).data('path');
                    $("#move_to").val(path);
                    $("#move-file").submit();
                });
                // $("#rename_to").val(newName);
                // $("#rename-file-form").submit();
            } else{
                var m = "clicked: " + key + " on " + $(this).text();
                window.console && console.log(m) || alert(m); 
            }
        },
        items: {
            "rename": {name: "Rename", icon: "edit"},
            "cut": {name: "Cut", icon: "cut"},
            // "delete": {name: "Delete", icon: "delete"},
            "sep1": "---------",
            "quit": {name: "Quit", icon: function($element, key, item){ return 'context-menu-icon context-menu-icon-quit'; }}
        }
    });
    */

    $(".remove-file").click(function(){
        var pin = prompt("Enter Pin");
        if(pin != ""){
            $("#file-to-remove").val($(this).attr("data-path"))
            $("#pin").val(pin)
            $("#remove-form").submit();
        }
    });

    $(".remove-folder").click(function(){
        var pin = prompt("Enter Pin");
        if(pin != ""){
            $("#folder-to-remove").val($(this).attr("data-path"))
            $("#folder-pin").val(pin)
            $("#folder-remove-form").submit();
        }
    });



</script>
