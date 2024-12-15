<?php 
if (isset($post->save)) {
  $obj = R::dispense("stock_return");
  if(isset($post->save)){
    $stock_collect = R::load("stock_collect", ID);
    $obj->salesman_id = $stock_collect->salesman_id;
    $obj->stock_collect_id = $stock_collect->id;
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post->variance as $id => $qty) {
      if($qty == 0) continue;
      if(!$stored){ R::store($obj); $stored = true; }
      $stock_collect_item = R::load("stock_collect_item", $id);
      $variance = R::load("product_variance", $stock_collect_item->product_variance_id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("stock_return_item");

      $ii->stock_collect_item_id = $stock_collect_item->id;
      $ii->stock_return_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = "$variance->particulars $variance->size x $variance->unit";
      $ii->created_by = uid();

      R::store($ii);

      $stock_collect_item->returned_quantity += $qty;
      R::store($stock_collect_item);
    }

    redir(ROOT."/salesman/details/$obj->salesman_id");
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
            <td><h5>Stock Return</h5></td>
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
              $i = 1;
                print "<thead><tr><th>No.</th><th>Product</th><th>Particulars</th><th>Image</th><th>Size</th><th>Unit</th><th>Stock Collect</th><th colspan='2'>Stock Return</th></tr></thead>";
                $items = select("
                  SELECT oi.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity - oi.returned_quantity quantity, `date`
                  FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND o.id=".ID."");
                while($var = mysqli_fetch_object($items)){
                  if(strpos($var->quantity, ".") !== FALSE){
                    $q = explode(".", $var->quantity);
                    $whole = $q[0];
                    $pcs = nf(".{$q[1]}" * $var->unit,0) + 0;
                    $var->quantity = "$whole + $pcs Pcs";
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
                    print "<td><input class='form-control w100' type='number' name='variance[$var->id]' max='$var->quantity' step='any'></td>";
                    print "<td><button class='btn btn-success btn-sm' name='save'>Return</button></td>";
                    print "</tr>";
                    $i++;
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

