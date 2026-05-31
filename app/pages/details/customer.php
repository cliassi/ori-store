<style type="text/css">
  a.btn {
    color: #fff !important;
  }

  .d-icon {
    min-width: 70px;
    padding: 0px 8px;
    margin-bottom: 4px;
  }

  .table-items th,
  .table-items td {
    /* border-bottom: solid 1px #ccc !important; */
  }

  .datatable-table td,
  .datatable-table th,
  .table td,
  .table th {
    padding: 2px;
  }

  tr.even {
    background: rgba(24, 204, 99, 0.2);
  }

  tr.odd {
    background: rgba(21, 108, 214, 0.2);
  }
</style>
<?php
$obj = R::dispense('customer');
if (defined('ID')) {
  $obj = R::load('customer', ID);
}
ensureMysqlColumn('collection', 'payment_date', 'DATE NULL');

if (isset($post->deliver)) {
  foreach ($post->delivered_by as $key => $value) {
    $ii = R::load('invoice_item', $key);
    $ii->delivered_by = trim($value);
    $ii->delivered_at = now();
    R::store($ii);
  }
  redir("?");
}

if (uid() == 1 && isset($get->delids)) {
  if (isset($get->delids)) {
    del("invoice_item", "invoice_id IN ($get->delids)");
    del("invoice", "id IN ($get->delids)");
  }
  redir("?");
}

if (isset($post->idToDelete)) {
  $inv = R::load("invoice", $post->idToDelete);
  del("invoice_item", "invoice_id='$inv->id'");
  R::trash($inv);
}
if (isset($post->idToDelete2)) {
  $col = R::load("collection", $post->idToDelete2);
  R::trash($col);
}

if (isset($post->idToDelete)) {
  $statement = R::load('statement', $post->idToDelete);
  R::trash($statement);
}
if (isset($post->idToDelete2)) {
  $staff_salary = R::load('staff_salary', $post->idToDelete2);
  R::trash($staff_salary);
  redir("?s=$staff_salary->statement_id");
}

if (isset($post->file_to_remove) && isset($post->pin)) {
  $user = R::load("sys_user", uid());
  if ($user->u_pin == $post->pin) {
    $new_path = "archive/" . substr($post->file_to_remove, 0, strrpos($post->file_to_remove, "/"));
    mkdir2($new_path);
    // vd($post->file_to_remove);
    // dd($new_path);
    rename("$post->file_to_remove", "archive/$post->file_to_remove");
  }
}
if (isset($post->folder_to_remove) && isset($post->pin)) {
  $user = R::load("sys_user", uid());
  if ($user->u_pin == $post->pin) {
    $new_path = "archive/" . time() . "/" . substr($post->folder_to_remove, 0, strpos($post->folder_to_remove, "/files") + 7);
    mkdir2($new_path);
    // dd("$post->folder_to_remove archive/$post->folder_to_remove $new_path");
    rename_win("$post->folder_to_remove", "archive/" . time() . "/$post->folder_to_remove");
  }
}

if (isset($post->file_to_rename) && isset($post->rename_to)) {
  $ext = ext($post->file_to_rename);
  $newname = $post->root_path . '/' . $post->rename_to . $ext;
  rename($post->file_to_rename, $newname);
  redir("?dir=" . $get->dir);
  // vd($ext);
  // vd($post);
}

if (isset($post->file_to_move) && isset($post->move_to)) {
  //src, dest
  // dd($post);
  rcopy($post->file_to_move, $post->move_to . "/" . $post->name);
  rrmdir($post->file_to_move);
  redir("?dir=" . $get->dir);
}

$dir = "uploads/customer/$obj->id";
$folder = isset($get->dir) ? $get->dir : 'files';

if (isset($post->name) && count($_FILES)) {
  $name = $post->name ? $post->name : '';
  $name = upload($_FILES, $name, "$dir/$folder");
}

if (isset($post->create_folder)) {
  if (isset($get->dir)) {
    mkdir2("$dir/$folder/$post->folder");
  }
}


//END FILE OPS


if (isset($post->save_remarks)) {
  $customer_remarks = R::dispense("customer_remarks");
  $customer_remarks->customer_id = $obj->id;
  $customer_remarks->notes = $post->remarks;
  $customer_remarks->priority = 'high';
  $customer_remarks->entry_by = uid();
  R::store($customer_remarks);
  redir("?");
}
if (isset($get->rm)) {
  $customer_remarks = R::load("customer_remarks", $get->rm);
  R::trash($customer_remarks);
  redir("?");
}

if (isset($get->delivered)) {
  $invoice = R::load('invoice', $get->delivered);
  $invoice->delivered_by = uid();
  $invoice->delivery_date = now();
  R::store($invoice);
  redir("?");
}

if (isset($get->approved)) {
  $collection = R::load('collection', $get->approved);
  $collection->approved_by = uid();
  $collection->approved_at = now();
  R::store($collection);
  redir("?");
}

if (isset($post->save_delivery_date)) {
  $invoice = R::load("invoice", $post->save_delivery_date);
  $invoice->invoice_date = $post->delivery_date;
  R::store($invoice);
}

print "<form method='post'>
<div class='row'>
          <!-- Zero config table start -->
          <div class='col-sm-12'>
            <div class='card'>
              <div class='card-header text-center'>
                <h5>$obj->company $obj->code</h5>
                <div><img src='" . ROOT . "/$obj->image' height='64px'></div>
                <h5>$obj->contact</h5>
                <h5><a class=' px-5 mb-1' href='tel:$obj->mobile'>$obj->mobile</a></h5>
                <div class='text-end'><a class='btn btn-success' onClick='redirectWithSelected()'><i class='fas fa-download'></i> Download</a></div>
                <hr>
                <div class='text-end'><a href='/store/customer/edit/" . ID . "'><i class='fa fa-user'></i> Profile</a></div>
              </div>
              <div class='card-body'>
                <div class='dt-responsive table-responsive'>
                  <table id='simpletable' class='table table-striped table-bordered nowrap'>
                    <thead>
                      <tr>
                        <th class='text-center'><a class='btn btn-success' href='/store/product/sell?c=" . ID . "'>Order</a></th>
                        <th class='text-center'><a class='btn btn-warning' href='../../collection/add?customer=" . ID . "'>Collection</a></th>
                        <th class='text-center'>
                        <a class='btn btn-danger'>Refund</a>";
if (uid() == 1) {
  print "<a class='btn btn-warning' onClick='redirectWithSelected2()'><i class='fas fa-trash'></i> Delete</a>";
}
//  <a class='btn btn-info' onClick='redirectWithSelected()'><i class='fas fa-print'></i> Print</a>";
// print "<th class='text-center'><a class='btn btn-success' href='/store/customer/edit/" . ID . "'>Profile</a></th>
print "</th>
                      </tr>
                    </thead>
                    <tbody>";

print "<table class='table table-bordered nowrap'>";
print "<thead>";
print "<tr>";
print "<th width='20px'># </th>
<th width='50px'>D&D</th>
        <th width='50px'>Ref No. </th>
        <th>Particulars</th>
        <th width='50px'>Delivery</th>
        <th width='50px'>Invoice By</th>
        <th width='50px'>Approve?</th>
        <th width='20px'></th>
        <th width='30px'>Debit </th>
        <th width='30px'>Credit  </th>
        <th width='30px'>Balance</th>
        <th width='50px'></th>";
print "</tr>";
print "</thead>";
print "<tbody>";
if (isset($get->show) && $get->show == "all") {
  $limit = "";
} else {
  $limit = " limit 0,10";
}
$limit = "";
$opening = 0;
if (METHOD == 'pending_delivery') {
  $trans = select("SELECT * FROM (SELECT * FROM (
    SELECT 'invoice' src, id, invoice_date date, created_at, created_by, delivered_by, '' particulars, (SELECT SUM(price * quantity) FROM `invoice_item` ii WHERE ii.invoice_id=invoice.id) amount FROM `invoice` WHERE customer_id=$obj->id
  ) a ORDER BY created_at $limit) b ORDER BY date, created_at");
} else {

  // SELECT 'invoice' src, id, invoice_date date, created_at, delivered_by, '' particulars, (SELECT SUM(price * quantity) FROM `invoice_item` ii WHERE ii.invoice_id=invoice.id) amount FROM `invoice` WHERE customer_id=$obj->id
  $trans = select("SELECT * FROM (SELECT * FROM (
    SELECT 'invoice' src, '' pm, '' ab, i.id, ii.id id2, IFNULL(ii.delivery_date,i.invoice_date) dd, i.created_at sort_date, i.invoice_date date, '' payment_date, i.created_at, i.delivered_by, i.created_by, (SELECT particulars FROM product_variance WHERE product_variance.id=ii.product_variance_id) particulars, ii.price * ii.quantity amount FROM `invoice` i, `invoice_item` ii WHERE i.id=ii.invoice_id AND i.customer_id=$obj->id
    UNION
    SELECT 'collection' src, payment_method pm, approved_by ab, id, 0 id2, '' dd, created_at sort_date, date, payment_date, created_at, approved_by delivered_by, created_by, description particulars, amount FROM `collection` WHERE customer_id=$obj->id
  ) a ORDER BY date DESC, created_at DESC $limit) b ORDER BY date, src, created_at");

  // $opq = select("SELECT SUMT() ");
}

$i = 1;
$counter = $trans->num_rows;
$users = userList();
$lastDate = '';
$class = 'odd';
while ($item = mysqli_fetch_object($trans)) {
  if ($lastDate == '')
    $lastDate = $item->sort_date;
  if ($lastDate != $item->sort_date) {
    $class = $class == 'even' ? 'odd' : 'even';
    $lastDate = $item->sort_date;
  }
  $lastDate = $item->sort_date;
  if (isset($get->show) && $get->show == "all") {
    print "<tr class='$item->src $class'>"; //$counter--;
    print "<td title='$item->sort_date'>$i</td>";
  } else {
    print "<tr class='$item->src $class " . ($i <= ($counter - 10) ? 'hidden' : '') . "'>"; //$counter--;
    print "<td title='$item->sort_date'>" . ($i - $counter + 10) . "</td>";
  }
  // print "<td>".df($item->sort_date).(date('Ymd', strtotime($item->date)) != date('Ymd', strtotime($item->created_at)) ? " (".df($item->date).")":"")."</td>";
  print "<td>" . df($item->sort_date) . "" . (($item->src == 'invoice' ? "<div style='border-top: solid 1px #555'><a href='javascript:void(0)' onclick='openDeliveryDateModal(" . $item->id . ", " . $item->id2 . ", \"" . $item->dd . "\")' style='color: #0066cc; cursor: pointer;'>" . df($item->dd) . "</a></div>" : '')) . "</td>";
  if ($item->src == 'invoice') {
    $delivered_by = "Select";
    if ($item->delivered_by) {
      $delivered_by = $item->delivered_by;
    }
    print "<td class='text-center'>INV" . zerofill($item->id, 5) . "-D";

    print "<div>";
    if (METHOD == 'pending_delivery') {
      print '<form method="post">
        <input type="date" name="delivery_date" value="<?php print $item->date; ?>" onchange="updateDeliveryDate()" class="form-control">
        <button name="save_delivery_date" id="update-delivery" class="btn btn-warning hidden" value=' . $item->id . '>Change Date</button>
      </form>';
    } else {
      print "";
    }
    print "</div>";
    print "</td>";
  } else {
    print "<td class='text-center'>
      <div>OR" . zerofill($item->id, 5) . "</div></td>";
  }
  if ($item->src == 'invoice') {
    $oi = R::load("invoice_item", $item->id2);
    if (!nn($oi->description)) {
      $oi->description = $item->particulars;
    }
    print "<td>";
    print "<table class='table table-bordered table-items'>";
    $k = 1;
    $pv = R::load("product_variance", $oi->product_variance_id);
    print "<tr>
        <td><div class='order-item'>$oi->description <span class='item-price'>($oi->price X <span class='item-qty'> $oi->quantity</span> = " . nf($oi->quantity * $oi->price) . ")</span></div></td></tr>";
    $k++;
    print "</table>";


    print "</td>";
    print "<td>";
    print "<table class='table table-bordered table-items'>";
    $k = 1;
    $pv = R::load("product_variance", $oi->product_variance_id);
    print "<tr>
        <td title='$oi->delivered_by'>";
    print "<button title='$oi->delivered_by " . (nn($oi->delivered_at) ? ' @ ' . df($oi->delivered_at) : '') . "' class='btn btn-sm " . ($oi->delivered_by ? "btn-success" : "btn-warning") . " selected-delivery-man' type='button' aria-expanded='false'><i class='fas fa-shipping-fast'></i> " . ($oi->delivered_by ? "$oi->delivery_staff" : "") . "<span></span></button>";
    // <div class='dropdown-menu'>";
    // $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
    // while ($man = mysqli_fetch_object($objs)) {
    //   print "<a class='dropdown-item delivery-man-item' data-id='$oi->id'>$man->name</a>";
    // }
    // print "</div>";
    // print "<span class='d-icon btn btn-sm ".($oi->delivered_by ? "btn-success":"btn-warning")."' data-id='$oi->id'><i class='fas fa-shipping-fast'></i></span></td>";
    print "</tr>";
    $k++;
    print "</table>";


    print "</td>";
    print "<td class='text-center'>" . ($users[$item->created_by]) . "</td>";
    // } elseif(strrpos($item->particulars, 'bank account') !== FALSE){
    //   print "<td>$item->particulars</td>";

    //   if($item->ab){
    //     print "<td><a class='btn btn-sm btn-success'><i class='fas fa-check'></i> Approved</a></td>";
    //   } elseif(isUserIn([])){
    //     print "<td><a href='?approved=$item->id' class='btn btn-sm btn-warning'><i class='fas fa-clock'></i> Pending</a></td>";
    //   } else {
    //     print "<td><a class='btn btn-sm btn-warning'><i class='fas fa-clock'></i> Pending</a></td>";
    //   }
    // } else{
    //   print "<td>".df($item->date)." $item->particulars</td>";

    //   print "<td></td>";
    // }


  } else {
    if ($item->src == 'collection') {
      $particularText = preg_replace('/^\s*\d{1,2}\s+[A-Za-z]{3},\s+\d{4}\s+/', '', (string) $item->particulars);
      $actualPaymentDate = nn($item->payment_date) ? $item->payment_date : $item->date;
      $displayDate = (nn($actualPaymentDate) && strtotime($actualPaymentDate)) ? date('d M, Y', strtotime($actualPaymentDate)) : '';
      $particularText = trim($displayDate . " " . $particularText);
      print "<td class='text-wrap w-25'> $particularText</td>";
      print "<td></td>"; // Delivery (empty for collections)
      print "<td></td>"; // Invoice By (empty for collections)
    } else {
      print "<td class='text-wrap w-25'> $item->particulars</td>";
      // print "<td class='text-center'>".($users[$item->created_by])."</td>";
    }
    // print "<td></td>";
  }



  //  print "<td nowrap class='text-right'>";



  // Add Approve column
  print "<td class='text-center'>";
  if ($item->src == 'collection' && $item->pm == 'Bank') {
    if ($item->ab) {
      print "<a class='btn btn-sm btn-success' title='Approved by user {$item->ab}'><i class='fas fa-check'></i> Approved</a>";
    } elseif (uid() == 1) {
      print "<a href='javascript:approveCollection($item->id)' class='btn btn-sm btn-warning'><i class='fas fa-clock'></i> Pending</a>";
    } else {
      print "<a class='btn btn-sm btn-warning'><i class='fas fa-clock'></i> Pending</a>";
    }
  }
  print "</td>";




  if ($item->src == 'invoice') {
    print "<td><input class='form-check-input' type='radio' id='itme_$item->id' value='$item->id'></td>";
    print "<td class='text-right'>" . nf($item->amount) . "</td>";
    print "<td></td>";
    sum('balance', $item->amount);
    sum('debit', $item->amount);
  } else {
    print "<td></td>"; // no radio for collections
    print "<td></td>"; // Debit empty
    print "<td class='text-right'>" . nf($item->amount) . "</td>"; // Credit
    sum('balance', 0 - $item->amount);
    sum('credit', $item->amount);
  }
  print "<td class='text-right'>" . nf(sum('balance')) . "</td>";
  print "<td nowrap class='text-right'>";
  if ($item->src == 'invoice') {
    //print "<a type='button' class='btn btn-sm btn-warning' href='".ROOT."/invoice/print/$item->id' ><i class='fas fa-print'></i></a>";
    if (uid() == 1) {
      print "<a type='button' class='btn btn-sm btn-info pb-1' href='" . ROOT . "/invoice/edit/$item->id' ><i class='fas fa-edit'></i></a>";
      print "<button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation($item->id)'><i class='fas fa-trash'></i></button>";
    }
  } else {
    if (uid() == 1) {
      if ($item->src == 'collection') {
        print "<a type='button' class='btn btn-sm btn-info pb-1' href='" . ROOT . "/collection/edit/$item->id'><i class='fas fa-edit'></i></a> ";
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
  <td colspan='3'>
  <a class='btn btn-info' href='javascript:addRemarks()'>Remarks</a> <a href='?dir=files' class='btn btn-sm btn-success'>Files</a>
";
if (isset($get->show) && $get->show == "all") {
  print "<a href='?show=10' class='btn btn-sm btn-shadow btn-light frht' style='color: #000 !important'>Show last 10</a>";
} else {
  print "<a href='?show=all' class='btn btn-sm btn-shadow btn-light frht' style='color: #000 !important'>Show all</a>";
}
print "</td>
<th class='text-right' colspan='5'><button name='deliver' class='btn btn-sm btn-success hidden'>Submit</button> TOTAL</th>
<th class='text-right'>" . nf(sum('debit')) . "</th>
<th class='text-right'>" . nf(sum('credit')) . "</th>
<th class='text-right'>" . nf(sum('balance')) . "</th>
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
$folder = isset($get->dir) ? $get->dir : 'files';

if (count($_FILES)) {
  $name = $post->name ? $post->name : '';
  $name = upload($_FILES, $name, "$dir/$folder");
}

if (isset($post->create_folder)) {
  if (isset($get->dir)) {
    mkdir2("$dir/$folder/$post->folder");
  }
}

print "<div style='margin: 0 30px;'>";
print "<div class='col-md-9' id='files'>";
if (file_exists($dir . "/$folder")) {
  mkdir2($dir . "/$folder");
}
if (isset($get->dir)) {
  if (file_exists($dir . "/$folder")) {
    $files = scandir("$dir/$folder");
    $i = 1;

    $dirlinks = "";
    $filelinks = "";

    foreach ($files as $file) {
      if ($file != "." && $file != "..") {
        if (is_dir("$dir/$folder/$file")) {
          $dirlinks .= "<div class='col-md-4 file-folder' style='padding-bottom: 3px;' data-path='$dir/$folder/$file' data-name='$file' data-root='$dir/$folder'><a style='width: calc(100% - 36px);text-align:left;font-size:12px;' href='?dir=$folder/$file' class='btn btn-warning'><i class='fa fa-folder'></i>  $file</a> <a data-path='$dir/$folder/$file' class='btn btn-sm btn-danger remove-folder'><i class='fa fa-times'></i></a></div>";
        }
      }
    }
    foreach ($files as $file) {
      if ($file != "." && $file != "..") {
        if (!is_dir("$dir/$folder/$file")) {
          $filelinks .= "<div class='col-md-4 file-file' style='padding-bottom: 3px;' data-path='$dir/$folder/$file' data-name='$file' data-root='$dir/$folder'><a style='width: calc(100% - 36px);text-align:left;font-size:12px;' target='_blank' href='../../$dir/$folder/$file' class='btn btn-success'>$i. $file</a> <a data-path='$dir/$folder/$file' class='btn btn-sm btn-danger remove-file'><i class='fa fa-times'></i></a></div>";
          $i++;
        }
      }
    }

    print "<div class='row'>$dirlinks</div>";
    print "<div class='row'>$filelinks</div>";
  }
  $pwd = substr($folder, 0, strrpos($folder, "/"));
  if ($pwd) {
    print "<br><a href='?dir=$pwd' class='btn btn-danger'><< BACK</a> ";
  } else {
    print "<br><a href='?' class='btn btn-danger'><< BACK</a> ";
  }

} else {
  // print "<a href='?dir=files' class='btn btn-success'>Files</a> ";
}
print "</div>";
if (isset($get->dir)) {
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

while ($remark = mysqli_fetch_object($remarks)) {
  $priority = "info";
  if ($remark->priority == 'High') {
    $priority = 'danger';
  } elseif ($remark->priority == 'Low') {
    $priority = 'success';
  }
  print "<div class='alert alert-sm alert-$priority'>$remark->notes ";
  if (uid() == 1)
    print "<a href='?rm=$remark->id'><i class='fa fa-trash frht pointer'></i></a>";
  print "</div>";
}

$remarks = select("*", "customer_remarks", "customer_id=$obj->id AND trash=0 AND entry_by<>1", "ORDER BY id DESC");

while ($remark = mysqli_fetch_object($remarks)) {
  $priority = "info";
  if ($remark->priority == 'High') {
    $priority = 'danger';
  } elseif ($remark->priority == 'Low') {
    $priority = 'success';
  }
  print "<div class='alert alert-danger d-flex align-items-center' role='alert'>
 
  <div>$remark->notes</div><a class='frht' href='?rm=$remark->id'><i class='fa fa-trash  pointer'></i></a></div>";
}


?>
</form>

<div class="modal fade" id="modal-change-delivery-date" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" autocomplete="off">
        <input type="hidden" name="invoice_id" id="delivery_invoice_id">
        <input type="hidden" name="invoice_item_id" id="delivery_invoice_item_id">
        <div class="modal-header">
          <h4 class="modal-title">Change Delivery Date</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <table>
            <tr>
              <td>Delivery Date</td>
              <td nowrap><input type="date" id="delivery_date_input" name="delivery_date" class="form-control" required>
              </td>
            </tr>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" onclick="saveDeliveryDate()">Save</button>
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
  function addRemarks() {

    <?php //if(uid() != 1): ?>
    setTimeout(function () {
      $(".swal2-textarea").after("<div id='remarks_hints' style='margin-left:30px'></div>");
      $("#remarks_hints input[type='radio']").click(function () {
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

  function redirectWithSelected() {
    // Select all checked radio buttons
    const selectedRadios = document.querySelectorAll('input[type="radio"]:checked');

    // Extract their values and join with commas
    const ids = Array.from(selectedRadios).map(r => r.value).join(',');

    // Build the new URL
    const newPage = "http://store.apurewater.com/store/app/pages/view/exportables/invoice.php?id=" + ids;


    window.open(newPage, '_blank');
  }

  function redirectWithSelected2() {
    if (confirm("Are you sure?")) {
      // Select all checked radio buttons
      const selectedRadios = document.querySelectorAll('input[type="radio"]:checked');

      // Extract their values and join with commas
      const ids = Array.from(selectedRadios).map(r => r.value).join(',');

      // Build the new URL
      const newPage = "?delids=" + ids;
      location.href = newPage;
    }

  }

  $(".delivery-man-item").click(function () {
    const id = $(this).data('id');
    const val = $(this).text();
    console.log(val);
    $(this).parent().parent().find('span').text(val);
    $('.oi-' + id).val(val);

    // const id = $(this).data('id');
    // const delivered_by = $(".selected-delivery-man").text().trim();
    // if(delivered_by == "Select"){
    //   swal.fire({
    //   title: 'Error!',
    //   text: 'Please select Delivery Staff',
    //   icon: 'error',
    //   confirmButtonText: 'OK',
    // });
    // } else{
    //   $(this).addClass('btn-success');
    //   $(this).removeClass('btn-warning');
    //   $.post("/store/ajax/save_state.php", {id:id,delivered_by:delivered_by}, function(res){
    //     console.log(res);
    //     $("#items").html(res);
    //   });
    // }
  });


  function updateDeliveryDate() {
    Swal.fire({
      title: "Do you want to change delivery date?",
      showCancelButton: true,
      confirmButtonText: "Yes",
      denyButtonText: `No`
    }).then((result) => {
      /* Read more about isConfirmed, isDenied below */
      if (result.isConfirmed) {
        $("#update-delivery").trigger('click');
      } else if (result.isDenied) {
      }
    });
  }

  $(".toggle-checkbox").change(function () {
    const state = $(this).prop('checked');
    $(this).parent().parent().find("input").prop('checked', state);
  });

  $(".d-icon").click(function () {
    const id = $(this).data('id');
    const delivered_by = $(".selected-delivery-man").text().trim();
    if (delivered_by == "Select") {
      swal.fire({
        title: 'Error!',
        text: 'Please select Delivery Staff',
        icon: 'error',
        confirmButtonText: 'OK',
      });
    } else {
      $(this).addClass('btn-success');
      $(this).removeClass('btn-warning');
      $.post("/store/ajax/save_state.php", { id: id, delivered_by: delivered_by }, function (res) {
        console.log(res);
        $("#items").html(res);
      });
    }
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


  $('input[type="radio"]').mousedown(function (e) {
    if (this.checked) {
      $(this).data('wasChecked', true);
    } else {
      $(this).data('wasChecked', false);
    }
  });

  $('input[type="radio"]').click(function (e) {
    if ($(this).data('wasChecked')) {
      $(this).prop('checked', false).trigger('change');
    }
  });

  $(".remove-file").click(function () {
    var pin = prompt("Enter Pin");
    if (pin != "") {
      $("#file-to-remove").val($(this).attr("data-path"))
      $("#pin").val(pin)
      $("#remove-form").submit();
    }
  });

  $(".remove-folder").click(function () {
    var pin = prompt("Enter Pin");
    if (pin != "") {
      $("#folder-to-remove").val($(this).attr("data-path"))
      $("#folder-pin").val(pin)
      $("#folder-remove-form").submit();
    }
  });

  function approveCollection(id) {
    if (confirm("Are you sure you want to approve this collection?")) {
      location.href = "?approved=" + id;
    }
  }

  function openDeliveryDateModal(invoiceId, invoiceItemId, currentDate) {
    $("#delivery_invoice_id").val(invoiceId);
    $("#delivery_invoice_item_id").val(invoiceItemId);
    $("#delivery_date_input").val(currentDate);
    $("#modal-change-delivery-date").modal('show');
  }

  function saveDeliveryDate() {
    var invoiceItemId = $("#delivery_invoice_item_id").val();
    var newDate = $("#delivery_date_input").val();

    if (!newDate) {
      alert("Please select a date");
      return;
    }

    $.post("/store/ajax/update_invoice_item_date.php", {
      invoice_item_id: invoiceItemId,
      date: newDate,
      update_date: 1
    }, function (response) {
      $("#modal-change-delivery-date").modal('hide');
      location.reload();
    }).fail(function () {
      alert("Error updating delivery date");
    });
  }

</script>