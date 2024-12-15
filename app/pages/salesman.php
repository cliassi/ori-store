<?php 
if(METHOD == 'add'){
 
} elseif(METHOD == 'details' && defined('ID')){
  require 'details/salesman.php';
} elseif(METHOD == 'return' && defined('ID')){
  require 'forms/stock_return.php';
} else{ 
  if (isset($post->save)) {
    try {
      if(isset($post->id)){
        $salesman = R::load('salesman', $post->id);
      } else{
        $salesman = R::dispense('salesman');
      }
      $salesman->name = $post->name;
      $salesman->basic = $post->basic;//$post->index;
      R::store($salesman);

      if (count($_FILES) > 0) {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
          $file = upload($_FILES, 'image' . $salesman->id . "-" . time(), 'uploads', 'image');
          $salesman->image = "uploads/$file";
        }
        if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
          $file = upload($_FILES, 'logo' . $salesman->id . "-" . time(), '../uploads', 'logo');
          $salesman->logo = "uploads/$file";
        }
        R::store($salesman);
      }
          // print "<script>location.href = '".ROOT."/product'; </script>";
    } catch (\Throwable $th) {
      dump($th);
    }
  }
  //End Save

  $month = isset($get->month) ? $get->month : date("Y-m-01");
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <?php
          $objs = R::find('salesman');
          print "<div class='card-header'>
          <div class='row'>
          <div class='col-6'><h5>Salesman</h5></div>
          <div class='col-6 text-right'>
          </div>
          </div>
          <div class='card-body'>
          <div class='dt-responsive table-responsive'>
          <table id='simpletable' class='table table-striped table-bordered nowrap'>
          <thead>
          <tr>
          <th>No</th>
          <th>Name</th>
          <th>Basic</th>
          <th>Photo</th>
          <th colspan='2' class='text-right'><a class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#productFrommOdal'><i class='fa fa-file'></i> Add</a></th>
          </tr>
          </thead>
          <tbody>";
          $i = 1;
          foreach ($objs as $key => $obj) {
            print "<tr>";
            print "<td>$i</td>";
            print "<td><a href='salesman/details/$obj->id'><u>$obj->name</u></a></td>";
            print "<td>$obj->basic</td>";
            $key = 'image';
            print "<td>".($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}' height='24px' class='opacity-50'></a>" : "")."</td>";
            print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a></td>";
            print "<td class='w100'><a class='btn btn-danger' href='?duplicate=$obj->id'><i class='fas fa-trash-alt'></i> Del</a></td>";
            print "</tr>";
            $i++;
          }
          print '</tbody>
          </table>
          </div>
          </div>';
        ?>
      </div>
    </div>
  </div>

  
        <div id="productFrommOdal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productFrommOdalLabel" aria-hidden="true">
          <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="productFrommOdalLabel">Add Salesman <span></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class='form-group'>
                    <lable>Name</lable>
                      <input class='form-control' required name='name' id="name">
                  </div>
                  <br>
                  <div class='row'>
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
                    </div>                    
                    <div class='col-sm-6'>
                        <lable>Salesman Photo</lable>
                      <input type='file' class='form-control' required  name='image' id="image">
                    </div>
                  </div>
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