<?php 
if(isset($post->var_id_del)){
  $variance = R::load('product_variance', $post->var_id_del);
  R::trash($variance);
}

if (isset($post->save)) {
  try {
      // if($obj->code == ''){
      //   $obj->code = 'AP'.rand(1000,9999);
      // }
    // $variance = R::findOne('product_variance', "product_id=?", [ID, $post->index]);
    // if(!$variance) 
    if(isset($post->var_id)){
      $variance = R::load('product_variance', $post->var_id);
    } else{
      $variance = R::dispense('product_variance');
    }
    $variance->product_id = $post->product_id;
    $variance->index = 1;//$post->index;
    $variance->particulars = $post->particulars;
    $variance->cost = $post->cost;
    $variance->price = $post->price;
    $variance->size = $post->size;
    $variance->unit = $post->unit;
    R::store($variance);

    if (count($_FILES) > 0) {
      if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
        $file = upload($_FILES, 'image' . $variance->id . "-" . time(), 'uploads', 'image');
        $variance->image = "uploads/$file";
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

$objs = R::find('product');
$contant = "";
?>
<div class="row">
  <!-- Zero config table start -->
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header">
        <h5>Stock Out Report </h5>
      </div>
      <div class="card-body">
        <div class="dt-responsive table-responsive">
          <table id="simpletable" class="table table-striped table-bordered nowrap">
            <thead>
              <tr>
                <th>No.</th>
                <th>Date</th>
                <th>Product</th>
                <th>Particulars</th>
                <th>Image</th>
                <th>Size</th>
                <th>Unit</th>
                <th>Qty Out</th>
              </tr>
            <tbody>
              <?php 
              $i = 1;
              if(defined('ID')){
                $items = select("SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity, invoice_date FROM `product` p, `product_variance` v, `invoice_item` oi, `invoice` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.invoice_id AND v.id=".ID." ORDER BY invoice_date");
                while($var = mysqli_fetch_object($items)){
                    print "<tr id='var-'>";
                    print "<td>$i</td>";
                    print "<td id='particulars-$var->id'>".df($var->invoice_date)."</td>";
                    print "<td id='particulars-$var->id'>$var->pname</td>";
                    print "<td id='particulars-$var->id'>$var->particulars</td>";
                    print "<td id='image-$var->id' class='text-center'><img src='".ROOT."/{$var->vimage}' height='80px'></a></td>";
                    print "<td id='size-$var->id'>$var->size<t/d>";
                    print "<td id='unit-$var->id'>$var->unit<t/d>";
                    // print "<td id='cost-$var->id'>$var->cost</td>";
                    // print "<td id='price-$var->id'>$var->price</td>";
                    print "<td id='quantity-$var->id'>$var->quantity</td>";
                    //print "<td><a  onclick='setProductEdit($var->id, $var->id)' id='product-$var->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'> <i class='fa fa-edit'></i></a>".space(3)."                            <a onclick='delProduct($var->id)'><i class='fa fa-trash'></i></a></td>";
                    print "</tr>";
                    $i++;
                }
              } else{
                $items = select("SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, SUM(oi.quantity) quantity, invoice_date FROM `product` p, `product_variance` v, `invoice_item` oi, `invoice` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.invoice_id GROUP BY v.id ORDER BY invoice_date");
                while($var = mysqli_fetch_object($items)){
                    print "<tr id='var-'>";
                    print "<td>$i</td>";
                    print "<td id='particulars-$var->id'>".df($var->invoice_date)."</td>";
                    print "<td id='particulars-$var->id'>$var->pname</td>";
                    print "<td id='particulars-$var->id'><a href='".METHOD."/$var->id'>$var->particulars</a></td>";
                    print "<td id='image-$var->id' class='text-center'><img src='".ROOT."/{$var->vimage}' height='80px'></a></td>";
                    print "<td id='size-$var->id'>$var->size<t/d>";
                    print "<td id='unit-$var->id'>$var->unit<t/d>";
                    // print "<td id='cost-$var->id'>$var->cost</td>";
                    // print "<td id='price-$var->id'>$var->price</td>";
                    print "<td id='quantity-$var->id'>$var->quantity</td>";
                    //print "<td><a  onclick='setProductEdit($var->id, $var->id)' id='product-$var->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'> <i class='fa fa-edit'></i></a>".space(3)."                            <a onclick='delProduct($var->id)'><i class='fa fa-trash'></i></a></td>";
                    print "</tr>";
                    $i++;
                }
              }
              // foreach ($objs as $key => $obj) {
              //   print "<tr id='product-$obj->id'>";
              //   print "<th colspan='3'></th>";
              //   print "<th>$i</th>";
              //   print "<td class='text-right'>$obj->name</td>";
              //   print "<td>ml/L</td>";
              //   print "<td>Unit</td>";
              //   print "<td>Cost</td>";
              //   print "<td>Price</td>";
              //   print "<td><a type='button' class='btn btn-primary' onclick='setProduct($obj->id)' id='product-$obj->id' data-bs-toggle='modal' data-bs-target='#productFrommOdal'>Add Product</a></td>";
              //   print "</tr>";

              //   $variances = R::find("product_variance", "product_id=?", [$obj->id]);
              //   $vi = 1;
              //   foreach ($variances as $key => $var) {
              //   }
              //   $i++;
              // }
              ?>
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
        <div id="productFrommOdal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productFrommOdalLabel" aria-hidden="true">
          <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="productFrommOdalLabel">Product <span></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  <input type="hidden" name="product_id" id="product_id">
                  <input type="hidden" name="var_id" id="var_id">
                </div>
                <div class="modal-body">
                  <div class='form-group'>
                    <lable>Particulars</lable>
                    <textarea class='form-control' required name="particulars" id="particulars"></textarea>
                  </div>
                  <br>
                  <div class='row'>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>ml/L</lable>
                        <input class='form-control' required step='any' name='size' id="size">
                      </div>
                    </div>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>Unit</lable>
                        <input class='form-control' required type="number" step='any' name='unit' id="unit">
                      </div>
                    </div>
                    <div class='col-sm-3'>              
                      <div class='form-group'>
                        <lable>Cost</lable>
                        <input class='form-control' required type="number" step='any' name='cost' id="cost">
                      </div>
                    </div>
                    <div class='col-sm-3'>           
                      <div class='form-group'>
                        <lable>Price</lable>
                        <input class='form-control' required type="number" step='any' name='price' id="price">
                      </div>
                    </div>
                  </div>
                  <br>
                  <div>
                    <input type='file' class='form-control' required  name='image' id="image">
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
