<?php 
if(isset($post->var_id_del)){
  $variance = R::load('product_variance', $post->var_id_del);
  R::trash($variance);
}


if(isset($get->token)){
  $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
  if($token){
    R::trash($token);
    $stock_damage = R::load("damaged_item", $get->id);
    $stock_damage->status = 'Approved';
    // $stock_damage->status = 'Approved';

    R::store($stock_damage);
    redir("?");
  }
}

if (isset($post->save)) {
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($post->salesman)?$post->salesman:0;
  if(isset($post->save)){
    $obj->delivery_staff = $post->delivery_staff;
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

    redir(ROOT."/delivery?s=$obj->delivery_staff");
  }
}

$objs = R::find('product');
$contant = "";
?>
<form method="post" id='main-form'>
<div class="row">
  <!-- Zero config table start -->
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header">
        <table class='table'>
          <tr>
            <td><h5>Stock In/Out Report </h5></td>
            <!-- td><button href='/store/order/collect?supplier=0' class='btn btn-sm btn-danger'>Salesman Collection</button></td>
            <td><a href='/store/order/return_collect?supplier=0' class='btn btn-sm btn-success'>Stock Return</a></td> -->
          </tr>
        </table>
      </div>
      <div class="card-body">
        <div class="dt-responsive table-responsive">
          <table id="simpletable" class="table table-striped table-bordered nowrap">
            
            <tbody>
              <?php 
              $users = userList();
              $i = 1;
              if(defined('ID')){
                print "<thead><tr><th>No.</th><th>Date</th><th>Product</th><th>Particulars</th><th>Image</th><th>Size</th><th>Unit</th><th>Staff</th><th>Approve</th><th>Purchase</th><th>Collection</th><th>Return</th><th>Damage</th><th>Balance</th></tr></thead>";
                //<th>Q.In</th><th>Q.Out</th><th>Q.Ret.</th><th>Q.Avl.</th>
                $items = select("SELECT *  FROM(
                  SELECT 'collection' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                  oi.quantity quantity, DATE `date`, oi.created_at, oi.created_by, delivery_staff staff, '' `remarks`, '' status
                  FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id 
                  AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND v.id=".ID."
                  UNION
                  SELECT 'ret' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                  oi.returned_quantity quantity, DATE `date`, oi.created_at, oi.created_by, delivery_staff staff, '' `remarks`, '' status
                  FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id 
                  AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND oi.returned_quantity > 0 AND v.id=".ID."
                  UNION
                  SELECT 'damage' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                  oi.damaged_quantity quantity, DATE `date`, oi.created_at, oi.created_by, delivery_staff staff, '' `remarks`, '' status
                  FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id 
                  AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND oi.damaged_quantity > 10000 AND v.id=".ID."
                  UNION
                  SELECT 'order' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity, order_date `date`, oi.created_at, oi.created_by, '' staff, 'Warehouse' `remarks`, '' status
                  FROM `product` p, `product_variance` v, `order_item` oi, `order` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.order_id AND v.id=".ID."
                  UNION
                  SELECT 'damage' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image_single vimage, size, unit, oi.quantity, oi.created_at  `date`, oi.created_at, oi.created_by, '' staff, 'Damage' `remarks`, status FROM `product` p, `product_variance` v, `damaged_item` oi WHERE p.id=v.product_id AND v.id=oi.product_variance_id  AND v.id=".ID."
                  ) a ORDER BY created_at");
                $bal = $balp = $damage = 0;
                while($var = mysqli_fetch_object($items)){
                    print "<tr id='var-' title='$var->src'>";
                    print "<td>$i</td>";
                    print "<td id='particulars-$var->id'>".date("d M y", strtotime($var->date))."</td>";
                    print "<td id='particulars-$var->id'>$var->pname</td>";
                    print "<td id='particulars-$var->id'>$var->particulars</td>";
                    print "<td id='image-$var->id' class='text-center'><img src='".ROOT."/{$var->vimage}' height='80px'></a></td>";
                    print "<td id='size-$var->id'>$var->size</td>";
                    if($var->remarks == 'Damage'){
                      print "<td id='unit-$var->id'>$var->quantity</td>";
                    } else{
                      print "<td id='unit-$var->id'>$var->unit</td>";
                    }
                    // print "<td id='unit-$var->id'>$var->remarks</td>";
                    print "<td id='cost-$var->id'>".(nn($var->staff) ? $var->staff : $users[$var->created_by])."</td>";
                    print "<td id='cost-$var->id'>";
                    if($var->remarks == 'Damage') {
                      if($var->status == 'Pending'){
                        print "<a class='btn btn-sm btn-danger protected-link' href='?id=$var->id'>Pending</a>";
                      } else{
                        print "<a class='btn btn-sm btn-success'>Approved</a>";
                      }
                    }
                    print "</td>";
                    // print "<td id='cost-$var->id'>$var->cost</td>";
                    // print "<td id='cost-$var->id'>$var->cost</td>";
                    // print "<td id='price-$var->id'>$var->price</td>";
                    if($var->src == 'order'){
                      $bal += $var->quantity;
                      // $balp += $var->quantity * $var->unit;
                      // $bundle = ceil($damage / $var->unit);
                      print "<td id='quantity-$var->id' class='text-center' style='background: rgba(15,240,70,.1)'>$var->quantity</td><td></td><td></td><td></td>";
                    } elseif($var->src == 'ret'){
                      $bal += $var->quantity;
                      // $balp += $var->quantity * $var->unit;
                      // $bundle = ceil($damage / $var->unit);
                      print "<td></td><td></td><td id='quantity-$var->id' class='text-center' style='background: rgba(15,70,240,.1)'>$var->quantity</td><td></td>";
                    } elseif($var->src == 'damage'){
                      // $bal -= $var->quantity;
                      $balp += $var->quantity; //$var->unit;
                      // $bundle = ceil($damage / $var->unit);
                      print "<td></td><td></td><td></td><td id='quantity-$var->id' class='text-center' style='background: rgba(240,70,15,.1)'>$var->quantity</td>";

                      // $damage += abs($var->quantity);
                      // $bundle = ceil($damage / $var->unit);
                      // $balp -= abs($var->quantity);
                      // print "<td></td><td></td><td class='text-center'>".($bal - $bundle)."</td><td></td><td id='quantity-$var->id' class='text-center' style='background: rgba(240,70,15,.1)'>$var->quantity</td><td id='quantity-$var->id' class='text-center'>$balp</td>";
                    } elseif($var->src == 'collection'){
                      $bal -= $var->quantity;
                      print "<td></td><td id='quantity-$var->id' class='text-center' style='background: rgba(240,70,15,.1)'>".abs($var->quantity)."</td><td></td><td></td>";
                    }
                    print "<td class='text-center'>".($balp > 0 ? " <span style='color:grey'>(- $balp pcs)</span>" : "")." $bal</td>";
                      // print "<td id='quantity-$var->id' class='text-center' style='background: rgba(240,15,70,.1)'>".abs($var->quantity)."</td>";
                      // print "<td id='quantity-$var->id' class='text-center'>$bal</td>";
                    //print "<td><a  onclick='setProductEdit($var->id, $var->id)' id='product-$var->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'> <i class='fa fa-edit'></i></a>".space(3)."                            <a onclick='delProduct($var->id)'><i class='fa fa-trash'></i></a></td>";
                    print "</tr>";
                    $i++;
                }
              } else{
                print "<thead><tr><th>No.</th><th>Product</th><th>Particulars</th><th>Image</th><th>Size</th><th>Unit</th><th>Stock Quantity</th><th colspan='2'>";

                print "<select class='form-select supplier-select' name='delivery_staff' required>
                    <option value=''>Please select</option>";

                    $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
                    while ($man = mysqli_fetch_object($objs)) {
                      print "<option ";
                      // if($man->name == $sm) print "selected";
                      print ">$man->name</option>";
                    }
                print "</select>";


                print "</th></tr></thead>";
                $items = select("SELECT id, pid, pname, pimage, particulars, cost, price, vimage, size, unit, SUM(quantity) quantity, `date`  FROM(
                  SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity quantity, invoice_date `date`
                  FROM `product` p, `product_variance` v, `invoice_item` oi, `invoice` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.invoice_id
                  UNION
                  SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity, order_date `date`
                  FROM `product` p, `product_variance` v, `order_item` oi, `order` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.order_id
                  UNION
                  SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity / unit, oi.created_at `date`
                  FROM `product` p, `product_variance` v, `damaged_item` oi WHERE p.id=v.product_id AND v.id=oi.product_variance_id
                  ) a GROUP BY id ORDER BY pid, particulars");
                while($var = mysqli_fetch_object($items)){
                  if(strpos($var->quantity, ".") !== FALSE){
                    $q = explode(".", $var->quantity);
                    $whole = $q[0];
                    $pcs = nf(".{$q[1]}" * $var->unit,0) + 0;
                    if($pcs){
                      $var->quantity = "<span style='color:grey'>(- $pcs Pcs)</span> $whole";
                    } else{
                      $var->quantity = "$whole";
                    }
                  }
                    print "<tr id='var-'>";
                    print "<td>$i</td>";
                    print "<td id='particulars-$var->id'>$var->pname</td>";
                    print "<td id='particulars-$var->id'><a href='".METHOD."/$var->id'>$var->particulars</a></td>";
                    print "<td id='image-$var->id' class='text-center'><img src='".ROOT."/{$var->vimage}' height='80px'></a></td>";
                    print "<td id='size-$var->id'>$var->size<t/d>";
                    print "<td id='unit-$var->id'>$var->unit<t/d>";
                    // print "<td id='cost-$var->id'>$var->cost</td>";
                    // print "<td id='price-$var->id'>$var->price</td>";
                    print "<td id='quantity-$var->id'>$var->quantity</td>";
                    //print "<td><a  onclick='setProductEdit($var->id, $var->id)' id='product-$var->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'> <i class='fa fa-edit'></i></a>".space(3)."                            <a onclick='delProduct($var->id)'><i class='fa fa-trash'></i></a></td>";
                    print "<td><input class='form-control w100' type='number' name='variance[$var->id]' step='any'></td>";
                    print "<td><button class='btn btn-success btn-sm' name='save'>Collect</button></td>";
                    print "</tr>";
                    $i++;
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</form>
            

<form method="post" id="del-form"><input type="hidden" name="var_id_del" id="var_id_del"></form>

<script type="text/javascript">
  function setProduct(i) {
    $("#product_id").val(i);
  }
  function setProductEdit(pid, vid) {
    $("#product_id").val(pid);
    $("#var_id").val(vid);

    $("#particulars").val($("#particulars-" + vid).text());
    $("#size").val($("#size-" + vid).text());
    $("#unit").val($("#unit-" + vid).text());
    $("#cost").val($("#cost-" + vid).text());
    $("#price").val($("#price-" + vid).text());
    $("#image").removeAttr('required');
  }

  function delProduct(vid){
    if(confirm("Are you sure?")){
      $("#var_id_del").val(vid);
      $("#del-form").submit();
    }
  }

  var lightboxModal = new bootstrap.Modal(document.getElementById('lightboxModal'));
  var elem = document.querySelectorAll('[data-lightbox]');
  for (var j = 0; j < elem.length; j++) {
    elem[j].addEventListener('click', function () {
      var images_path = event.target;
      if (images_path.tagName == 'IMG') {
        images_path = images_path.parentNode;
      }
      var recipient = images_path.getAttribute('data-lightbox');
      var image = document.querySelector('.modal-image');
      image.setAttribute('src', recipient);
      lightboxModal.show();
    });
  }

  function removeClassByPrefix(node, prefix) {
    for (let i = 0; i < node.classList.length; i++) {
      let value = node.classList[i];
      if (value.startsWith(prefix)) {
        node.classList.remove(value);
      }
    }
  }
</script>

