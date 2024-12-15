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
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flickity/1.0.0/flickity.pkgd.js"></script>
  <style type="text/css">
    .product-display .card{
      flex-direction: row;
      justify-content: center;
    }
    .products-wrapper{
      white-space: wrap;
      overflow-x: auto;
    }
    .product-wrapper{
      padding: 15px;
      text-align: center;
      box-shadow: rgb(200, 200, 200) 2px 2px 4px;
      margin-top: 1px;
      margin-right: 15px;
      border-radius: 15px;
      width: 250px;
    }
    .product-wrapper img{
      height: 128px;
      width: auto;
    }
    .product-wrapper .dropdown-menu .dropdown-item{
      display: inline;
      padding: 3px;
      border-radius: 4px;
      border: solid 1px #ccc;
    }
    .product-wrapper .dropdown-menu .dropdown-item:hover{
      background: #ddd;
      color: #000;
      border: solid 1px #aaa;
    }
    button, a.btn{
      white-space: nowrap;
      padding: 5px !important;
      border-radius: 5px !important;
    }
    .product-wrapper .btn-group{
      /*    position: absolute;*/
      /*    margin-left: 140px;*/
  }
  .btn-qty-wrapper {
    position: absolute;
      /* width: calc(100% - 45px); */
    text-align: center;
    width: 42px;
    right: 20px;

    /*border: solid .5px #ccc;
    padding: 5px;
    height:   125px;*/
/*    background-color:   #ffefff;*/
  }
  span.badge.rounded-pill.text-bg-success{
    padding: 5px;
    border-radius: 50%;
    width: 21px;
    left: 0;
    margin-top: 20px;
  }
  .product-pane{
    height: calc(100vh - 195px);
    overflow-y: auto;
    overflow-x: hidden;
  }
  .pc-footer,.card-header{
    display:  none;
  }
  .var-name{
    display: block;
/*    margin-left: -15px;*/
    width: 200px;
    position: absolute;
    margin-top: -16px;
    line-height: 1;;
    background: #ffffffbb;
    font-weight: 700;
  }
  .var-price{
    display: block;
    width: 200px;
    position: absolute;
    margin-top: -16px;
    line-height: 1;;
    background: #ffffffbb;
    font-weight: 700;
    color: orangered;
  }
  .splide__pagination{
    display: none;
  }
  </style>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card product-pane">
        <div class="card-header">
          <!-- <h5>Product List <span style='float:right'><a href="<?php print ROOT.'/'.$page; ?>/add"><i class='fa fa-file'></i> Add</a></span></h5> -->
        </div>
        <div class="card-body">
          <?php
          $products = R::find('product');
          foreach($products as $product){
            print "<section id='product-{$product->id}' class='splide{$product->id} splide splide-sell' aria-label='Splide Basic HTML Example' style='overflow: hidden'><div class='splide{$product->id}__track splide__track'><ul class='splide{$product->id}__list splide__list'>";
                // print "<span class='nav-product'><a href='/store/product/sell#product-$product->id'><img src='".ROOT."/$product->image' height='64px'></a></span>";
            $variances = R::find("product_variance", "product_id=? AND deleted_by IS NULL ORDER BY product_id", [$product->id]);
            $i = 1;
            foreach ($variances as $key => $var) {?>
              <li class='splide__slide'>
                <div class='product-wrapper'>
                  <span class='var-price'><span style='font-size: .7rem'>RM</span> <?php print $var->price; ?></span>
                  <div class="btn-qty-wrapper">
                    <div class="btn-group mb-2 me-2">
                      <button class="btn btn-info dropdown-toggle cart-item" data-product="<?php print $var->id; ?>" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">0</button>
                      <div class="dropdown-menu" style="">
                        <?php for($counter = 0; $counter<=10; $counter++) { print "<a class='dropdown-item qty qty-$counter'>".($counter>0?'+':'')."$counter</a>"; } ?>
                      </div>
                    </div>
                    <!-- <hr> -->
                    <!-- <span class="badge rounded-pill text-bg-success"><?php print chr($i+64); ?></span> -->
                  </div>
                  <img src='<?php print ROOT."/$var->image"; ?>' height='64px'>
                  <span class='var-name'><?php print $var->particulars; ?></span>
                </div>
              </li>
              <?php
              $i++;
            }
            print "</ul></div></section><hr>";
          }
          ?>

          <!-- <div class="gallery js-flickity" data-flickity-options='{ "wrapAround": true }'>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
            <div class="gallery-cell"></div>
          </div> -->

          <div class="product-display">
            <?php 
            /*
            $i = 1;
            $variances = R::find("product_variance", "deleted_by IS NULL ORDER BY product_id");
            $vi = false;
            foreach ($variances as $key => $var) {
              if($i == 1){
                print "<div id='product-$var->product_id' class='card products-wrapper gallery js-flickity' data-flickity-options='{ \"wrapAround\": true }'>";
              }

              if($i > 1 && $vi != $var->product_id){
                print "</div>";
                print "<div id='product-$var->product_id' class='card products-wrapper gallery js-flickity' data-flickity-options='{ \"wrapAround\": true }'>";
              }

              print "<div class='product-wrapper gallery-cell'>";
              print "<div class='row'>";
              print "<div class='col-9'>";
              print "<img src='".ROOT."/{$var->image}'>";
              print "</div>";
              print "<div class='col-3'>";
              print '<div class="btn-group mb-2 me-2">
              <button class="btn btn-info dropdown-toggle cart-item" data-product="'.$var->id.'" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">0</button>
                <div class="dropdown-menu" style="">';
              for($counter = 0; $counter<=10; $counter++) { print "<a class='dropdown-item qty qty-$counter'>".($counter>0?'+':'')."$counter</a>"; }
              print '
                </div>
              </div>';
              print "</div>";
              print "</div>";
              print '</div>';
              $i++;
              $vi = $var->product_id;
            }
            */
            ?>
          </div>
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
              <div class='col-sm-4'>              
                <div class='form-group'>
                  <lable>ml/L</lable>
                  <input class='form-control' required step='any' name='size' id="size">
                </div>
              </div>
              <div class='col-sm-4'>              
                <div class='form-group'>
                  <lable>Unit</lable>
                  <input class='form-control' required type="number" step='any' name='unit' id="unit">
                </div>
              </div>
              <div class='col-sm-4'>              
                <div class='form-group'>
                  <lable>Cost</lable>
                  <input class='form-control' required type="number" step='any' name='cost' id="cost">
                </div>
              </div>
              <div class='col-sm-4'>           
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

  <div id="orderModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="orderModal" aria-hidden="true">
    <form method="post" action="<?php print ROOT; ?>/invoice" id="form-order">

      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderModalLabel">Order <span></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class='form-group'>
              <lable>Select Customer</lable>
              <select type="text" class="form-select" name="customer_id" required>
                <option value=''>Please select</option>
                <?php
                $customers = R::find('customer');
                foreach ($customers as $key => $customer) {
                  print "<option value='$customer->id'>$customer->company</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name='order' class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </form>  
  </form>
  <script type="text/javascript">
    $(".fa-shopping-cart").click(function(){
      placeOrder();
    });

    $(document).ready(function() {
      $(window).scrollTop(-40);
    });

    function placeOrder(){
      // $('#form-order').empty();
          // Iterate over each cart-item button
      $('.cart-item').each(function() {
              var productId = $(this).data('product'); // Get the product ID from data-product attribute
              var quantity = $(this).text(); // Get the quantity from button text

              if (quantity > 0) { // Only include items with quantity greater than 0
                  // Create a hidden input field and append it to the form
                $('#orderModal .modal-title').after(
                  $('<input>').attr({
                    type: 'hidden',
                    name: 'product[' + productId + ']',
                    value: quantity
                  })
                  );
              }
            });
      // $("#form-order").submit();
    }
    $(document).ready(function() {
      // Adjust scroll behavior for anchor links
      $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));

        if (target.length) {
          event.preventDefault();
                var offset = 40; // Adjust this value for the top margin
                $('html, body').animate({
                  scrollTop: target.offset().top - offset
                }, 500); // Adjust animation speed as needed
              }
            });
    });

    setTimeout(function(){
      $(".product-wrapper").click(function(){
        const t = $(this).find('button');
        let qty = parseInt(t.text());
        qty = qty+1;
        t.text(qty)
      });

      $('.product-wrapper button').on('click', function(event) {
          event.stopPropagation(); // Prevents the click from bubbling up to the div
        });
    }, 1000);
    
    function setProduct(i) {
      $("#product_id").val(i);
    }
    $(".qty").click(function(){
      const t = $(this).parent().parent().find("button");
      if($(this).text() == "0"){
        t.text(-1);
      } else{
        let qty = parseInt(t.text()) + parseInt($(this).text()) - 1;
        t.text(qty);
      }
    });
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

    function addToCart(id){
      // let items = JSON.parse(localStorage.getItem('items')) ?? []
      // localStorage.setItem('items', JSON.stringify(items.push(id)));
      Swal.fire({
        title: "Added to Cart",
        icon: "success"
      });
    }


  /*
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
    }*/
  </script>
