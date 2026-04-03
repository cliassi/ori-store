<style type="text/css">
  a.btn {
    color: #fff !important;
  }
</style>
<?php
$obj = R::dispense('supplier');
if (defined('ID')) {
  $obj = R::load('supplier', ID);
}
// ===== Date and lorry filter INPUTS =====
$f_from = (isset($get->from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $get->from)) ? $get->from : '';
$f_to = (isset($get->to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $get->to)) ? $get->to : '';
$f_lorry = isset($get->lorry) ? (int) $get->lorry : 0;

// ===== ORDER FILTER CONDITION =====
$orderWhere = "supplier_id = {$obj->id}";
if ($f_from)
  $orderWhere .= " AND DATE(created_at) >= '$f_from'";
if ($f_to)
  $orderWhere .= " AND DATE(created_at) <= '$f_to'";

/* ✅ when lorry filter used: ONLY orders, and exclude NULL lorry_id rows */
if ($f_lorry) {
  $orderWhere .= " AND lorry_id = $f_lorry AND lorry_id IS NOT NULL";
}

/* goods_return / payment / refund have no lorry_id in your UI */
$grWhere = "supplier_id = {$obj->id}";
if ($f_from)
  $grWhere .= " AND DATE(order_date) >= '$f_from'";
if ($f_to)
  $grWhere .= " AND DATE(order_date) <= '$f_to'";

$payWhere = "supplier_id = {$obj->id}";
if ($f_from)
  $payWhere .= " AND DATE(date) >= '$f_from'";
if ($f_to)
  $payWhere .= " AND DATE(date) <= '$f_to'";

$refundWhere = "supplier_id = {$obj->id} AND payment_method='Supplier ID'";
if ($f_from)
  $refundWhere .= " AND DATE(date) >= '$f_from'";
if ($f_to)
  $refundWhere .= " AND DATE(date) <= '$f_to'";

/* ===================== LORRY OPTIONS ===================== */
$lorryOptions = "<option value=''>All</option>";
$lorries = R::find('lorry', '1 ORDER BY id DESC');
foreach ($lorries as $l) {
  $sel = ($f_lorry == (int) $l->id) ? "selected" : "";
  $lorryOptions .= "<option value='{$l->id}' $sel>{$l->lorry_no} - {$l->driver_name}</option>";
}
// ===== END Date and lorry filter INPUTS =====
if (isset($post->idToDelete)) {
  $inv = R::load("order", $post->idToDelete);
  del("order_item", "order_id='$inv->id'");
  R::trash($inv);
}
if (isset($post->idToDelete2)) {
  $col = R::load("goods_return", $post->idToDelete2);
  del("goods_return_item", "order_id=$post->idToDelete2");
  R::trash($col);
}
if (isset($post->idToDelete3)) {
  $col = R::load("payment", $post->idToDelete3);
  R::trash($col);
}
if (isset($post->idToDelete4)) {
  $col = R::load("refund", $post->idToDelete4);
  R::trash($col);
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

$dir = "uploads/supplier/$obj->id";
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
  $supplier_remarks = R::dispense("supplier_remarks");
  $supplier_remarks->supplier_id = $obj->id;
  $supplier_remarks->notes = $post->remarks;
  $supplier_remarks->priority = 'high';
  $supplier_remarks->entry_by = uid();
  R::store($supplier_remarks);
  redir("?");
}

if (isset($get->rm)) {
  $note = R::load("supplier_remarks", $get->rm);
  R::trash($note);
  redir("?");
}
if (isset($get->delivered)) {
  $order = R::load('order', $get->delivered);
  $order->delivered_by = uid();
  $order->delivery_date = now();
  R::store($order);
}

print "
<div class='row'>
  <div class='col-sm-12'>
    <div class='card'>
      <div class='card-header text-center'>
        <h5>$obj->company $obj->code</h5>
        <div><img src='" . ROOT . "/$obj->image' height='64px'></div>
        <h5>$obj->contact</h5>
        <h5><a class=' px-5 mb-1' href='tel:$obj->mobile'>$obj->mobile</a></h5>
      </div>

      <div class='px-3 py-2'>
        <form method='get' class='form-inline d-flex align-items-center flex-wrap' action='?'>
          <input type='hidden' name='id' value='" . ID . "'>
          <input type='hidden' name='show' value='" . (isset($get->show) ? $get->show : '') . "'>

          <div class='form-group d-flex align-items-center mr-3'>
            <label class='mr-2 mb-0'>From</label>
            <input type='date' name='from' value='" . $f_from . "' class='form-control'>
          </div>

          <div class='form-group d-flex align-items-center mr-3'>
            <label class='mr-2 mb-0'>To</label>
            <input type='date' name='to' value='" . $f_to . "' class='form-control'>
          </div>

          <div class='form-group d-flex align-items-center mr-3'>
            <label class='mr-2 mb-0'>Lorry</label>
            <select name='lorry' class='form-control'>
              $lorryOptions
            </select>
          </div>

          <button class='btn btn-primary mr-2' type='submit'>Filter</button>
          " . (($f_from || $f_to || $f_lorry) ? "<a class='btn btn-secondary' href='?id=" . ID . "'>Reset</a>" : "") . "
        </form>
      </div>

      <div class='card-body'>
        <div class='dt-responsive table-responsive'>
          <table id='simpletable' class='table table-striped table-bordered nowrap'>
            <thead>
              <tr>
                <th class='text-center'><a class='btn btn-success' href='../../order/add?supplier=" . ID . "'>Order</a></th>
                <th class='text-center'><a class='btn btn-warning' href='../../payment/add?supplier=" . ID . "'>Payment</a></th>
                <th class='text-center'><a class='btn btn-danger' href='../../refund/add?supplier=" . ID . "'>Refund</a></th>
              </tr>
            </thead>
            <tbody>";

print "<table class='table table-striped table-bordered w-auto nowrap'>";
print "<thead>";
print "<tr>";
print "<th># </th>
        <th>
          <div style='border-bottom: solid 1px #555;'>Entry Date</div>
          <div>Confirm Date</div>
        </th>
        <th>Ref No. </th>
        <th class='w-75'>Particulars</th>
        <th>Lorry</th>
        <th>Goods</th>
        <th>Approve ? </th>
        <th>Credit  </th>
        <th>Debit </th>
        <th>Balance</th>";
print "</tr>";
print "</thead>";
print "<tbody>";

$users = userList();
$lorries = R::find('lorry');
$lorryMap = [];
foreach ($lorries as $l) {
  $lorryMap[$l->id] = $l->lorry_no . ' - ' . $l->driver_name;
}

$bq = mysqli_fetch_object(select("SELECT 
(SELECT IFNULL(SUM(cost * quantity),0) amount FROM `order`, order_item ii WHERE  ii.order_id=order.id AND supplier_id=$obj->id) `order`,
(SELECT IFNULL(SUM(cost * quantity),0) amount FROM `goods_return`, `goods_return_item` ii WHERE ii.order_id=goods_return.id AND supplier_id=$obj->id) `return`,
(SELECT IFNULL(SUM(amount),0) FROM `payment` WHERE supplier_id=$obj->id) `payment`"));
sum('credit', $bq->return);
sum('credit', $bq->payment);
sum('debit', $bq->order);
sum('balance', $bq->order - $bq->return - $bq->payment);

if (isset($get->show) && $get->show == "all") {
  $limit = "";
} else {
  $limit = " limit 0,10";
}

$trans = select("SELECT * FROM (SELECT * FROM (
  SELECT 'order' src, id, created_at date, confirm_date, created_by, created_at, orderItems(id) particulars, delivered_by, (SELECT SUM(cost * quantity) FROM `order_item` ii WHERE ii.order_id=order.id) amount FROM `order` WHERE supplier_id=$obj->id
  UNION
  SELECT 'goods_return' src, id, order_date date, '' confirm_date, created_by, created_at, returnedItems(id) particulars, '' delivered_by, (SELECT SUM(cost * quantity) FROM `goods_return_item` ii WHERE ii.order_id=goods_return.id) amount FROM `goods_return` WHERE supplier_id=$obj->id
  UNION
  SELECT 'payment' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `payment` WHERE supplier_id=$obj->id
) a order by ccreated_at  DESC $limit) b  order by created_at ");
//New added for balance
$transForTotal = select("SELECT * FROM (SELECT * FROM (
  SELECT 'order' src, id, created_at date, confirm_date, created_by, created_at, orderItems(id) particulars, delivered_by, (SELECT SUM(cost * quantity) FROM `order_item` ii WHERE ii.order_id=order.id) amount FROM `order` WHERE supplier_id=$obj->id
  UNION
  SELECT 'goods_return' src, id, order_date date, '' confirm_date, created_by, created_at, returnedItems(id) particulars, '' delivered_by, (SELECT SUM(cost * quantity) FROM `goods_return_item` ii WHERE ii.order_id=goods_return.id) amount FROM `goods_return` WHERE supplier_id=$obj->id
  UNION
  SELECT 'payment' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `payment` WHERE supplier_id=$obj->id
) a order by created_at  DESC) b  order by created_at ");

// balance
$i = 1;
$users = userList();
while ($item = mysqli_fetch_object($transForTotal)) {
  if ($item->src == 'order') {
    sum('balance', 0 - $item->amount);
    sum('debit', 0 - $item->amount);
  } elseif ($item->src == 'goods_return') {
    sum('balance', $item->amount);
    sum('credit', 0 - $item->amount);
  } else {
    sum('balance', $item->amount);
    sum('credit', 0 - $item->amount);
  }
}

// ✅ CORRECTED - Final query with WHERE conditions
$trans = select("SELECT * FROM (SELECT * FROM (
  SELECT 'order' src, id, created_at date, confirm_date, created_by, created_at, orderItems(id) particulars, delivered_by, (SELECT SUM(cost * quantity) FROM `order_item` ii WHERE ii.order_id=order.id) amount FROM `order` WHERE $orderWhere
  UNION
  SELECT 'goods_return' src, id, order_date date, '' confirm_date, created_by, created_at, returnedItems(id) particulars, '' delivered_by, (SELECT SUM(cost * quantity) FROM `goods_return_item` ii WHERE ii.order_id=goods_return.id) amount FROM `goods_return` WHERE $grWhere
  UNION
  SELECT 'payment' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `payment` WHERE $payWhere
  UNION
  SELECT 'refund' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `refund` WHERE $refundWhere
) a order by created_at DESC $limit) b order by created_at ");

$balance = 0;

if ($limit) {
  // ✅ CORRECTED - Opening balance query with WHERE conditions
  $otrans = select("SELECT SUM(CASE WHEN src = 'order' THEN amount ELSE 0 END) `order`, SUM(CASE WHEN src = 'goods_return' THEN amount ELSE 0 END) `goods_return`, SUM(CASE WHEN src = 'payment' THEN amount ELSE 0 END) `payment`, SUM(CASE WHEN src = 'refund' THEN amount ELSE 0 END) `refund` FROM (SELECT * FROM (
    SELECT 'order' src, id, created_at date, confirm_date, created_by, created_at, orderItems(id) particulars, delivered_by, (SELECT SUM(cost * quantity) FROM `order_item` ii WHERE ii.order_id=order.id) amount FROM `order` WHERE $orderWhere
    UNION
    SELECT 'goods_return' src, id, order_date date, '' confirm_date, created_by, created_at, returnedItems(id) particulars, '' delivered_by, (SELECT SUM(cost * quantity) FROM `goods_return_item` ii WHERE ii.order_id=goods_return.id) amount FROM `goods_return` WHERE $grWhere
    UNION
    SELECT 'payment' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `payment` WHERE $payWhere
    UNION
    SELECT 'refund' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `refund` WHERE $refundWhere
  ) a order by created_at DESC) b order by created_at ");
  $opening = mysqli_fetch_object($otrans);
  $balance = $opening->order + $opening->goods_return + $opening->payment + $opening->refund;

  sum('balance', $opening->order);
  sum('debit', $opening->order);
  sum('balance', 0 - $opening->goods_return);
  sum('credit', $opening->goods_return);

  sum('balance', 0 - $opening->refund);
  sum('credit', $opening->refund);
  sum('balance', 0 - $opening->payment);
  sum('credit', $opening->payment);

  while ($item = mysqli_fetch_object($trans)) {
    if ($item->src == 'order') {
      sum('balance', -$item->amount);
      sum('debit', -$item->amount);
    } elseif ($item->src == 'goods_return') {
      sum('balance', $item->amount);
      sum('credit', -$item->amount);
    } elseif ($item->src == 'refund') {
      sum('balance', $item->amount);
      sum('credit', -$item->amount);
    } else {
      sum('balance', $item->amount);
      sum('credit', -$item->amount);
    }
  }

  // ✅ CORRECTED - Refresh query with WHERE conditions
  $trans = select("SELECT * FROM (SELECT * FROM (
    SELECT 'order' src, id, created_at date, confirm_date, created_by, created_at, orderItems(id) particulars, delivered_by, (SELECT SUM(cost * quantity) FROM `order_item` ii WHERE ii.order_id=order.id) amount FROM `order` WHERE $orderWhere
    UNION
    SELECT 'goods_return' src, id, order_date date, '' confirm_date, created_by, created_at, returnedItems(id) particulars, '' delivered_by, (SELECT SUM(cost * quantity) FROM `goods_return_item` ii WHERE ii.order_id=goods_return.id) amount FROM `goods_return` WHERE $grWhere
    UNION
    SELECT 'payment' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `payment` WHERE $payWhere
    UNION
    SELECT 'refund' src, id, date, '' confirm_date, created_by, created_at, description particulars, '' delivered_by, amount FROM `refund` WHERE $refundWhere
  ) a order by created_at DESC $limit) b order by created_at ");

}
while ($item = mysqli_fetch_object($trans)) {
  print "<tr>";
  print "<td>$i</td>";
  if (nn($item->confirm_date)) {
    print "<td><div style='border-bottom: solid 1px #555;'>" . df($item->date) . "</div>" . df($item->confirm_date) . "</td>";
  } else {
    print "<td>" . df($item->date) . "</td>";
  }
  print "<td title='{$users[$item->created_by]} @ {$item->created_at}'>";
  if ($item->src == 'order') {
    print "ORD" . zerofill($item->id, 5) . "";
  } elseif ($item->src == 'goods_return') {
    print "GRT" . zerofill($item->id, 5) . "";
  } elseif ($item->src == 'refund') {
    print "RF" . zerofill($item->id, 5) . "";
  } else {
    print "PMT" . zerofill($item->id, 5) . "";
  }
  print "</td>";
  print "<td style='max-width: 250px; white-space: normal; word-wrap: break-word;'>$item->particulars</td>";
  // if ($item->src == 'order') {
  //   print "<td title='{$users[$item->created_by]}'>" . ($item->delivered_by ? "<a class='btn btn-success'>Received</a>" : "<a class='btn btn-warning' href='?delivered=$item->id'>Ordered</a>") . "</td>";
  // } else {
  //   print "<td title='{$users[$item->created_by]}'></td>";
  // }
  // ✅ LORRY COLUMN
  if ($item->src == 'order') {
    $order = R::load('order', $item->id);
    $lorryName = $order->lorry_id && isset($lorryMap[$order->lorry_id])
      ? $lorryMap[$order->lorry_id]
      : '';
    print "<td>$lorryName</td>";
  } else {
    print "<td></td>";
  }

  // ✅ GOODS COLUMN
  print "<td></td>";

  if ($item->src == 'order') {
    print "<td></td>";
    print "<td class='text-right'>" . nf($item->amount) . "</td>";
    sum('balance', $item->amount);
    sum('debit', $item->amount);
  } elseif ($item->src == 'goods_return') {
    print "<td class='text-right'>" . nf($item->amount) . "</td>";
    print "<td></td>";
    sum('balance', 0 - $item->amount);
    sum('credit', $item->amount);
  } elseif ($item->src == 'refund') {
    print "<td class='text-right'>" . nf($item->amount) . "</td>";
    print "<td></td>";
    sum('balance', 0 - $item->amount);
    sum('credit', $item->amount);
  } else {
    print "<td class='text-right'>" . nf($item->amount) . "</td>";
    print "<td></td>";
    sum('balance', 0 - $item->amount);
    sum('credit', $item->amount);
  }
  print "<td class='text-right'>" . nf(sum('balance')) . "</td>";

  if ($item->src == 'order') {
    print "<td><button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation($item->id)'><i class='fas fa-trash'></i></button></button>";
  } elseif ($item->src == 'goods_return') {
    print "<td><button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation2($item->id)'><i class='fas fa-trash'></i></button></td>";
  } elseif ($item->src == 'refund') {
    print "<td><button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation4($item->id)'><i class='fas fa-trash'></i></button></td>";
  } else {
    print "<td><button type='button' class='btn btn-sm btn-danger' onclick='deleteConfirmation3($item->id)'><i class='fas fa-trash'></i></button></td>";
  }
  print "</tr>";
  $i++;
}
print "<tr>
  <td colspan='2'><a class='btn btn-info' href='javascript:addRemarks()'>Remarks</a></td>
  <td><a href='?dir=files' class='btn btn-success'>Files</a></td>
<td>";
if (isset($get->show) && $get->show == "all") {
  print "<a href='?show=10' class='btn btn-shadow btn-light' style='color: #000 !important'>Show last 10</a>";
} else {
  print "<a href='?show=all' class='btn btn-shadow btn-light' style='color: #000 !important'>Show all</a>";
}
print "
<th></th>
<th class='text-right'>TOTAL</th>
<th class='text-right'>" . nf(sum('credit')) . "</th>
<th class='text-right'>" . nf(sum('debit')) . "</th>
<th class='text-right'>" . nf(sum('balance')) . "</th>
</tr>";

print "</tbody>";

print "</tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>";

$dir = "uploads/supplier/$obj->id";
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

print "<div class='row' style='margin: 0 30px;'>";
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
  print "<input type='text' class='form-control' name='folder' placeholder='Create Folder'/><br>";
  print "<button name='create_folder' class='btn btn-warning'>Create</button></form>";
  print "<br>";
  // print "</div>";
  // print "<div class='col-md-3'>";
  openForm("post", true);
  print "<input type='text' class='form-control' name='name' placeholder='File name'/>";
  print "<input type='file' class='form-control' name='file' />";
  print "<button name='upload_file' class='btn btn-success'>Upload</button></form>";
  print "</div>";
  print "</div>";
}


$remarks = select("*", "supplier_remarks", "supplier_id=$obj->id AND trash=0 AND entry_by=1", "ORDER BY id DESC");

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

$remarks = select("*", "supplier_remarks", "supplier_id=$obj->id AND trash=0 AND entry_by<>1", "ORDER BY id DESC");

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



</script>