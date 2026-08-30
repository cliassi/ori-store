<?php
if (METHOD == 'add') {
  require 'forms/supplier.php';
} elseif (METHOD == 'edit' && defined('ID')) {
  require 'forms/supplier.php';
} elseif (METHOD == 'details' && defined('ID')) {
  require 'details/supplier.php';
} else {

  ensureMysqlColumn('supplier', 'created_by', "INT DEFAULT NULL AFTER `active`");
  ensureMysqlColumn('supplier', 'created_at', "DATETIME DEFAULT NULL AFTER `created_by`");

  if (isset($get->v)) {
    $supplier = R::load("supplier", $get->id);
    $supplier->active = (int) $get->v;
    R::store($supplier);
    redir("?");
  }

  if (isset($get->token)) {
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if ($token) {
      R::trash($token);
      if (uid() == 1 && isset($get->del)) {
        $object = R::load('supplier', $get->del);
        R::trash($object);
        redir("?");
      }

    } else {
      redir("?");
    }
  }

  $fields = [
    "id" => '',
    "company" => ["label" => "Comapny Name", "display" => '', 'link' => 'details'],

    //"company" => ["label"=>"Comapny Name", "display"=>'', 'link'=>'/store/product/supplierProduct'],
    "contact" => ["label" => "Contact Person", "display" => ''],
    "mobile" => ["label" => "C.P. Mobile", "display" => ''],
    "city" => ["label" => "City", "display" => ''],
    "address" => ["label" => "Shop Address", "display" => ''],
    "image" => ["label" => "Photo", "display" => '', 'type' => 'image'],
    "avatar" => ["label" => "Entry", "style" => "width: 50px;", "display" => '', 'type' => 'avatar'],
    "" => ["display" => '', 'type' => 'link', 'action' => 'edit'],
  ];
  $branch_id = isset($branch_id) ? $branch_id : (isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1);
  $objs = R::find('supplier', "branch_id = $branch_id OR branch_id IS NULL");
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <h5>Supplier List</h5>
            </div>
            <div class="col"><input class="form-control" placeholder="Search Supplier" id="search"></div>
          </div>

        </div>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="supplier-table" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <th>Sl.</th>
                  <?php foreach ($fields as $key => $value)
                    if (isset($value['label']))
                      print "<th>{$value['label']}</th>"; ?>
                  <th>Status</th>
                  <th><a href="<?php print $page; ?>/add"><i class='fa fa-file'></i> Add</a></th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                foreach ($objs as $key => $obj) {
                  print "<tr>";
                  print "<td>$i</td>";
                  $i++;
                  foreach ($fields as $key => $value) {
                    if (isset($value['display'])) {
                      $printed = false;
                      if (isset($value['type'])) {
                        if ($value['type'] == "image") {
                          print "<td height='60px'>" . ($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}'  height='60px'></a>" : "") . "</td>";
                          $printed = true;
                        } elseif ($value['type'] == "avatar") {
                          $avatar = "";
                          if (nn($obj->created_by)) {
                            $creator = R::load("sys_user", $obj->created_by);
                            if (file_exists("uploads/user/avatar/$creator->u_avatar") && nn($creator->u_avatar)) {
                              $avatar = "<img src='" . BASEURL . APP . "/uploads/user/avatar/$creator->u_avatar' class='w30'>";
                            } else {
                              $avatar = $creator->u_fullname;
                            }
                          }
                          print "<td data-tippy-content='" . (nn($obj->created_by) ? $creator->u_fullname . " @ " . $obj->created_at : '') . "'>$avatar</td>";
                          $printed = true;
                        } elseif ($value['type'] == "link") {
                          print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a> <a class='protected-link' href='?del=$obj->id'><i class='fa fa-trash'></i></a></td>";
                          $printed = true;
                        }
                      }
                      if (!$printed) {
                        print "<td>";
                        //if(isset($value['link'])) print "<a href='$page/{$value['link']}/$obj->id'>";
                        if (isset($value['link'])) {
                          // Check if the link value starts with a '/', meaning it's an absolute path (like /store/product/supplierProduct)
                          if (strpos($value['link'], '/') === 0) {
                            // Use the full link path provided + the object ID
                            print "<a href='{$value['link']}/$obj->id'>";
                          } else {
                            // Use the old relative path logic (for 'edit', 'details', etc.)
                            print "<a href='$page/{$value['link']}/$obj->id'>";
                          }
                        }

                        print $obj->$key;
                        if (isset($value['link']))
                          print "</a>";
                        print "</td>";
                      }
                    }
                  }
                  print "<td style='text-align:center'>";
                  print $obj->active ? "<a href='?id=$obj->id&v=0'><i class='fas fa-eye'></i></a>" : "<a href='?id=$obj->id&v=1'><i class='fas fa-eye-slash'></i></a>";
                  print "</td>";
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

  <style>
    .w30 {
      width: 30px;
    }
  </style>
  <script>
    $(document).ready(function () {
      $('#search').on('keyup', function () {
        var searchKey = $(this).val().toLowerCase(); // Get the search value and convert to lowercase

        $('table tbody tr').each(function () {
          var rowText = $(this).text().toLowerCase(); // Get the text content of the row and convert to lowercase

          if (rowText.includes(searchKey)) {
            $(this).show(); // Show the row if it matches the search key
          } else {
            $(this).hide(); // Hide the row if it does not match
          }
        });
      });
    });

  </script>
<?php } ?>