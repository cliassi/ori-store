<style>
  .w150 {
    width: 250px !important;
  }

  .hidden-variant {
    display: none;
  }
  th{
    text-align: center !important;
  }

  .highlight-row {
    animation: highlight 2s ease-out;
  }

  @keyframes highlight {
    from {
      background-color: #fff3cd;
    }

    to {
      background-color: transparent;
    }
  }
</style>

<?php
// PIN Verification AJAX Handler
if (isset($post->idToDelete) && isset($post->pin)) {
  // Verify PIN and username
  $user = R::findOne('sys_user', 'u_username = ? AND u_pin = ?', ['Adminn', $post->pin]);

  if ($user) {
    $product = R::load('product', $post->idToDelete);
    R::trash($product);
    redir("?");
  } else {
    // Check if user exists but is not Adminn
    $currentUser = R::load('sys_user', uid());
    if ($currentUser->u_pin == $post->pin && $currentUser->u_username !== 'Adminn') {
      echo "<script>alert('You are not permitted to delete products!');</script>";
    } else {
      echo "<script>alert('Invalid PIN!');</script>";
    }
  }
}

// if(isset($post->var_id_del)){
//   $variance = R::load('product_variance', $post->var_id_del);
//   R::trash($variance);
// }

if (isset($post->var_id_del) && isset($post->pin_variance)) {
  // Verify PIN and username
  $user = R::findOne('sys_user', 'u_username = ? AND u_pin = ?', ['Adminn', $post->pin_variance]);

  if ($user) {
    $variance = R::load('product_variance', $post->var_id_del);
    R::trash($variance);
    redir("?");
  } else {
    // Check if user exists but is not Adminn
    $currentUser = R::load('sys_user', uid());
    if ($currentUser->u_pin == $post->pin_variance && $currentUser->u_username !== 'Adminn') {
      echo "<script>alert('You are not permitted to delete product variants!');</script>";
    } else {
      echo "<script>alert('Invalid PIN!');</script>";
    }
  }
}


if (isset($get->v)) {
  $product = R::load("product_variance", $get->id);
  $product->visible = $get->v;
  R::store($product);
}

if (isset($get->del)) {
  $product = R::load('product', $get->del);
  R::trash($product);
  redir("?");
}
if (isset($post->save)) {
  try {
    // if($obj->code == ''){
    //   $obj->code = 'AP'.rand(1000,9999);
    // }
    // $variance = R::findOne('product_variance', "product_id=?", [ID, $post->index]);
    // if(!$variance) 
    if (isset($post->var_id)) {
      $variance = R::load('product_variance', $post->var_id);
    } else {
      $variance = R::dispense('product_variance');
    }
    $variance->product_id = $post->product_id;
    $variance->index = 1;//$post->index;
    $variance->particulars = $post->particulars;
    $variance->cost = $post->cost;
    $variance->cost_before_tax = $post->cost_before_tax;
    $variance->tax = $post->tax;
    $variance->sort_order = $post->sort_order;
    $variance->price = $post->price;
    $variance->image_orientation = $post->image_orientation;
    $variance->min_stock = $post->min_stock;
    // $variance->size = $post->size;
    $variance->unit = $post->unit;
    $variance->frozen = $post->frozen;
    $variance->wprice = $post->price;
    R::store($variance);

    if (count($_FILES) > 0) {
      if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
        $file = upload($_FILES, 'image' . $variance->id . "-" . time(), 'uploads', 'image');
        $variance->image = "uploads/$file";
      }
      if (isset($_FILES['image_single']['name']) && !empty($_FILES['image_single']['name'])) {
        $file = upload($_FILES, 'image_single' . $variance->id . "-" . time(), 'uploads', 'image_single');
        $variance->image_single = "uploads/$file";
      }
      if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
        $file = upload($_FILES, 'logo' . $variance->id . "-" . time(), '../uploads', 'logo');
        $variance->logo = "uploads/$file";
      }
      R::store($variance);
    }
    // print "<script>location.href = '".ROOT."/product'; </script>";
  } catch (\Throwable $th) {
    dump($th);
  }

}

$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;
$objs = R::find('product', 'id > 0 and visible=1 ORDER BY sort_order');
$contant = "";
?>
<div class="row">
  <!-- Zero config table start -->
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header">
        <h5>Product List <span style='float:right'><a href="<?php print $page; ?>/add"><i class='fa fa-file'></i>
              Add</a></span></h5>
      </div>
      <div class="card-body">
        <div class="dt-responsive table-responsive">
          <table id="simpletable" class="table table-striped table-bordered nowrap">
            <tbody>
              <?php
              $i = 1;
              foreach ($objs as $key => $obj) {
                $pc = R::load("product_category", $obj->product_category_id);
                print "<tr id='product-$obj->id'>";
                print "<th colspan='3'></th>";
                print "<th><span class='badge rounded-circle bg-primary' style='width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;'>$i</span></th>";
                print "<th class='text-left'>$obj->name</th>";
                print "<th>Avl. Stock</th>";
                print "<th>Min. Stock</th>";
                print "<th>$pc->uom</th>";
                print "<th>$pc->uom2</th>";
                print "<th>Buy</th>";
                print "<th>SST</th>";
                print "<th>Cost</th>";
                print "<th>Price</th>";
                // print "<td>W.Price</td>";
                //print "<td><a type='button' class='btn btn-primary' onclick='setProduct($obj->id)' id='product-$obj->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'>Add Product</a> <a href='?del=$obj->id'><i class='fa fa-trash'></i></a></td>";
                print "<td style='text-align:center'><a type='button' class='btn btn-primary' onclick='setProduct($obj->id)' id='product-$obj->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'>Add Product</a> <a href='javascript:deleteProductWithPin($obj->id)'><i class='fa fa-trash'></i></a></td>";
                print "</tr>";

                $variances = select("*, stock2(id, $branch_id) stock", "product_variance", "product_id=$obj->id ORDER BY sort_order");
                $vi = 1;
                $totalVariants = 0;
                // foreach ($variances as $key => $var) {
                while ($var = mysqli_fetch_object($variances)) {
                  $totalVariants++;
                  $isHidden = ($vi > 10) ? 'hidden-variant' : '';

                  print "<tr id='var-$var->id'  class='variant-row $isHidden' data-product='$obj->id'>";
                  print "<td style='text-align:center'>$vi</td>";
                  // print "<td>$vi</td>";
                  print "<td class='text-center'><img src='" . ROOT . "/{$var->image}' height='80px' style='border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.15);'></td>";
                  // if($var->image_single){
                  //   print "<td><img src='".ROOT."/{$var->image_single}' height='80px'></td>";
                  // } else{
                  //   print "<td></td>";
                  // }
                  print "<td id='particulars-$var->id' data-ori='$var->image_orientation' data-sort-order='$var->sort_order' data-frozen='$var->frozen' data-uom='$pc->uom' data-uom2='$pc->uom2' colspan='3' class='text-center'><a href='/store/report/stock/$var->id'>$var->particulars</a></td>";
                  print "<td id='stock-$var->id' class='text-center " . ($var->stock < $var->min_stock ? 'color-red' : '') . "'>$var->stock</td>";
                  print "<td id='min_stock-$var->id' class='text-center'>$var->min_stock<t/d>";
                  print "<td id='size-$var->id' class='text-center'>$var->size<t/d>";
                  print "<td id='unit-$var->id' class='text-center'>$var->unit<t/d>";
                  print "<td id='cost_before_tax-$var->id' class='text-right'>$var->cost_before_tax</td>";
                  print "<td id='tax-$var->id' class='text-right'>$var->tax</td>";
                  print "<td id='cost-$var->id' class='text-right'>$var->cost</td>";
                  print "<td id='price-$var->id' class='text-right'>$var->price</td>";
                  // print "<td id='wprice-$var->id'>$var->wprice</td>";
                  print "<td style='text-align:center'>";
                  print $var->visible ? "<a href='?id=$var->id&v=0'><i class='fas fa-eye'></i></a>" : "<a href='?id=$var->id&v=1'><i class='fas fa-eye-slash'></i></a>" . space(2);
                  print "<a  onclick='setProductEdit($obj->id, $var->id)' id='product-$obj->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'> <i class='fa fa-edit'></i></a>" . space(2) . "
                            <a onclick='delProduct($var->id)'><i class='fa fa-trash'></i></a></td>";
                  print "</tr>";
                  $vi++;
                }
                if ($totalVariants > 10) {
                  print "
                  <tr class='show-more-row' data-product='$obj->id'>
                    <td colspan='15' class='text-center'>
                      <button type='button'
                              class='btn btn-sm btn-info show-more-btn'
                              data-product='$obj->id'
                              data-step='10'>
                        Show more (10)
                      </button>
                    </td>
                  </tr>";
                }
                $i++;
              }
              ?>
              <form method="post" id="delete-product-form">
                <input type="hidden" name="idToDelete" id="idToDelete">
                <input type="hidden" name="pin" id="delete-pin">
              </form>

              <form method="post" id="delete-variance-form">
                <input type="hidden" name="var_id_del" id="var_id_del">
                <input type="hidden" name="pin_variance" id="pin-variance">
              </form>

            </tbody>
            <tfoot>
              <!-- <tr>
                        <th>Comapny Name</th>
                        <th>Contact Person</th>
                        <th>C.P. Mobile</th>
                        <th>Shop Address</th>
                        <th></th>
                      </tr> -->
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade modal-lightbox" id="lightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">
        <img src="../assets/images/light-box/l1.jpg" alt="images" class="modal-image img-fluid" />
      </div>
    </div>
  </div>
</div>
<div id="productFrommOdal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productFrommOdalLabel"
  aria-hidden="true">
  <form class="forms-sample" method="post" enctype="multipart/form-data">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productFrommOdalLabel">Main Product <span></span></h5>
          <?php print sop2('product_id', '', [], 'product'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <!-- <input type="hidden" name="product_id" id="product_id"> -->
          <input type="hidden" name="var_id" id="var_id">
        </div>
        <div class="modal-body modal-lg">
          <div class='row'>
            <div class='col-sm-2'>
              <div class='form-group'>
                <lable>Serial</lable>
                <select class='form-control' required name="sort_order" id="sort_order">
                  <?php for ($so = 0; $so <= 99; $so++)
                    print "<option>$so</option>"; ?>
                </select>
              </div>
            </div>
            <div class='col-sm-6'>
              <div class='form-group'>
                <lable>Variant Name</lable>
                <input class='form-control' required name="particulars" id="particulars">
              </div>
            </div>
            <div class='col-sm-2'>
              <div class='form-group'>
                <lable>Min-Stock</lable>
                <input class='form-control' required step='any' name='min_stock' id="min_stock">
              </div>
            </div>
            <div class='col-sm-2'>
              <div class='form-group'>
                <lable id='uom2'>Unit</lable>
                <input class='form-control' required type="number" step='any' name='unit' id="unit">
              </div>
            </div>
          </div>
          <br>
          <div class='row'>
            <!-- <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable id='uom'>ml/L</lable>
                        <input class='form-control' required step='any' name='size' id="size">
                      </div>
                    </div> -->
            <div class='col-sm-2'>
              <div class='form-group'>
                <lable>Buy</lable>
                <input class='form-control' required type="number" step='any' name='cost_before_tax'
                  id="cost_before_tax">
              </div>
            </div>
            <div class='col-sm-2'>
              <div class='form-group'>
                <lable>SST</lable>
                <input class='form-control' required type="number" value='0' step='any' name='tax' id="tax">
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='form-group'>
                <lable>Cost</lable>
                <input class='form-control' readonly required type="number" step='any' name='cost' id="cost">
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='form-group'>
                <lable>Price</lable>
                <input class='form-control' required type="number" step='any' name='price' id="price">
              </div>
            </div>

            <div class='col-sm-2 hidden'>
              <div class='form-group'>
                <lable>W.Price</lable>
                <input class='form-control' value='0' type="number" step='any' name='wprice' id="wprice">
              </div>
            </div>
            <div class='col-sm-3 hidden'>
              <div class='form-group '>
                <lable>Frozen Item</lable>
                <select name='frozen' id='frozen' class="form-control">
                  <option value="0">No</option>
                  <option value="1">Yes</option>
                </select>
              </div>
            </div>
          </div>
          <br>
          <div class='row'>
            <div class='col-sm-3'>
              <div class='form-group'>
                <lable>Image Type</lable>
                <select name='image_orientation' id='image_orientation' class="form-control">
                  <option>L</option>
                  <option>P</option>
                </select>
              </div>
            </div>
            <div class='col-sm-9'>
              <div class='form-group'>
                <lable>Bundle Image</lable>
                <input type='file' class='form-control' required name='image' id="image">
              </div>
            </div>
          </div>
          <!-- <div class='row'>
                    <div class='col-sm-3'>   
                    </div>
                    <div class='col-sm-9'>           
                      <div class='form-group'>
                        <lable>Image Single</lable>
                        <input type='file' class='form-control' required  name='image_single' id="image_single">
                      </div>
                    </div>
                  </div> -->
          <div class='row'>
            <div class='col-sm-12'>
              <div class='form-group'>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name='save' class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- <form method="post" id="del-form"><input type="hidden" name="var_id_del" id="var_id_del"></form> -->

<script type="text/javascript">
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("show-more-btn")) return;

    const btn = e.target;
    const pid = btn.dataset.product;
    const step = parseInt(btn.dataset.step, 10);

    // find next hidden rows for this product
    const hiddenRows = Array.from(
      document.querySelectorAll(
        '.variant-row.hidden-variant[data-product="' + pid + '"]'
      )
    );

    // show next batch
    hiddenRows.slice(0, step).forEach(row => {
      row.classList.remove("hidden-variant");
    });

    // if nothing left → remove button
    if (
      document.querySelectorAll(
        '.variant-row.hidden-variant[data-product="' + pid + '"]'
      ).length === 0
    ) {
      btn.closest(".show-more-row").remove();
    }
  });
  /* Show All Button */

  function setProduct(i) {
    $("#product_id").val(i);
  }
  function setProductEdit(pid, vid) {
    $("#product_id").val(pid);
    $("#var_id").val(vid);

    $("#particulars").val($("#particulars-" + vid).text());
    $("#image_orientation").val($("#particulars-" + vid).data('ori'));
    $("#frozen").val($("#particulars-" + vid).data('frozen'));
    $("#uom").text($("#particulars-" + vid).data('uom'));
    $("#uom2").text($("#particulars-" + vid).data('uom2'));
    $("#sort_order").val($("#particulars-" + vid).data('sort-order'));
    $("#size").val($("#size-" + vid).text());
    $("#min_stock").val($("#min_stock-" + vid).text());
    $("#unit").val($("#unit-" + vid).text());
    $("#tax").val($("#tax-" + vid).text());
    $("#cost_before_tax").val($("#cost_before_tax-" + vid).text());
    $("#cost").val($("#cost-" + vid).text());
    $("#price").val($("#price-" + vid).text());
    $("#wprice").val($("#wprice-" + vid).text());
    $("#image").removeAttr('required');
    // $("#image_single").removeAttr('required');
  }

  function updateCost() {
    let costBeforeTax = parseFloat($("#cost_before_tax").val()) || 0;
    let tax = parseFloat($("#tax").val()) || 0;
    let cost = costBeforeTax * (1 + (tax / 100));
    $("#cost").val(cost.toFixed(2)); // format to 2 decimals
  }

  // trigger on keyup in either input
  $("#cost_before_tax, #tax").on("keyup change", updateCost);

  // function delProduct(vid){
  //   if(confirm("Are you sure?")){
  //     $("#var_id_del").val(vid);
  //     $("#del-form").submit();
  //   }
  // }

  function delProduct(vid) {
    if (confirm("Are you sure you want to delete this product variant?")) {
      var pin = prompt("Enter Admin PIN:");
      if (pin != "" && pin != null) {
        $("#var_id_del").val(vid);
        $("#pin-variance").val(pin);
        $("#delete-variance-form").submit();
      }
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

  function deleteProductWithPin(productId) {
    if (confirm("Are you sure you want to delete this product?")) {
      var pin = prompt("Enter Admin PIN:");
      if (pin != "" && pin != null) {
        $("#idToDelete").val(productId);
        $("#delete-pin").val(pin);
        $("#delete-product-form").submit();
      }
    }
  }




</script>