<?php 
if(METHOD == 'add'){
  require 'forms/product.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/product.php';
}  elseif(METHOD == 'details'){
  require 'details/product.php';
} elseif(METHOD == 'sell'){
  require 'details/sell3.php';
}  elseif(METHOD == 'sell3'){
  require 'details/sell3.php';
} elseif(METHOD == 'sell2'){
  require 'details/sell2.php';
} elseif(METHOD == 'checkout'){
  require 'details/checkout.php';
} else{ 
  $fields = [
    "id" => '',
    "sort_order" => ["label"=>"Order", "display"=>'sort_order'],
    "name" => ["label"=>"Product Name", "display"=>'', 'link'=>'details'],
    // "contact" => ["label"=>"Contact Person", "display"=>''],
    // "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    // "city" => ["label"=>"City", "display"=>''],
    // "address" => ["label"=>"Shop Address", "display"=>''],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image'],
    "" => ["display"=>'', 'type'=>'link', 'action'=>'edit'],
  ];
  $objs = R::find('product', 'id>0 ORDER BY sort_order');
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <h5>Product List</h5>
        </div>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="simpletable" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <?php foreach ($fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
                  <th><a href="<?php print $page; ?>/add"><i class='fa fa-file'></i> Add</a></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                foreach ($objs as $key => $obj) {
                  print "<tr>";
                  foreach ($fields as $key => $value) {
                    if(isset($value['display'])) {
                      $printed = false;
                      if(isset($value['type'])){
                        if($value['type'] == "image") {
                          print "<td>".($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}' height='32px' ></a>" : "")."</td>";
                          $printed = true;
                        } elseif($value['type'] == "link") {
                          print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a></td>";
                          $printed = true;
                        }
                      }
                      if(!$printed) {
                        print "<td>";
                        if(isset($value['link'])) print "<a href='$page/{$value['link']}#product-$obj->id'>";
                        print $obj->$key;
                        if(isset($value['link'])) print "</a>";
                        print "</td>";
                      }
                    }
                  }
                  print "</tr>";
                }
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
        <script>
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
        <?php } ?>