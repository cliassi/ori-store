<?php 
if(METHOD == 'add'){
  require 'forms/customer.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/customer.php';
} elseif(METHOD == 'details' && defined('ID')){
  require 'details/customer.php';
} elseif(METHOD == 'pending_delivery' && defined('ID')){
  require 'details/customer.php';
  // require 'details/customer_pending_delivery.php';
} elseif(METHOD == 'statement' && defined('ID')){
  require 'details/statement.php';
} else{ 

  if(isset($get->token)){
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if($token){
      R::trash($token);
      if(isset($get->del) && isset($get->id)){
        $st = R::load('customer', $get->id);
        R::trash($st);
        redir("?");
      }
    }
  }

  $fields = [
    "id" => '',
    "code" => ["label"=>"Code", "display"=>''],
    "password" => ["label"=>"PIN", "display"=>''],
    "company" => ["label"=>"Shop Name", "display"=>'', 'link'=>'details'],
    "contact" => ["label"=>"Contact Person", "display"=>''],
    "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    "city" => ["label"=>"<a href='area'>Area</a>", "display"=>''],
    // "address" => ["label"=>"Shop Address", "display"=>''],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image'],
    "" => ["display"=>'', 'type'=>'link', 'action'=>'edit'],
  ];
  if(isUserIn(['parvez'])){
  } else{
    unset($fields['password']);
  }
  // vd($fields);
  $objs = R::find('customer', "branch_id = $branch_id OR branch_id IS NULL");
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col"><h5>Customer List</h5></div>
            <div class="col"><input class="form-control" placeholder="Search Customer" value='<?php if(isset($get->key)) print $get->key; ?>' id="search"></div>
          </div>
          
        </div>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="customer-table" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <th>Sl.</th>
                  <?php foreach ($fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
                  <th><a href="<?php print $page; ?>/add"><i class='fa fa-file'></i> Add</a></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $i = 1;
                foreach ($objs as $key => $obj) {
                  print "<tr>";
                  print "<td>".($i++)."</td>";
                  foreach ($fields as $key => $value) {
                    if(isset($value['display'])) {
                      $printed = false;
                      if(isset($value['type'])){
                        if($value['type'] == "image") {
                          print "<td height='60px'>".($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}' height='60px'></a>" : "")."</td>";
                          $printed = true;
                        } elseif($value['type'] == "link") {
                          print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a>";
                          if(uid() == 1){
                            print space(5)."<a href='?del&id=$obj->id' class='protected-link'><i class='fas fa-trash-alt'></i></a><td></td>";
                          }
                          $printed = true;
                        }
                      }
                      if(!$printed) {
                        print "<td>";
                        if(isset($value['link'])) print "<a href='$page/{$value['link']}/$obj->id'>";
                        if($key == 'code' && nn($obj->location)){
                          print "<a href='$obj->location' target='_blank'>".$obj->$key."</td>";
                        } else{
                          print $obj->$key;
                        }
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
          $(document).ready(function() {
            $('#search').on('keyup', function() {
              var searchKey = $(this).val().toLowerCase(); // Get the search value and convert to lowercase
              
              $('table tbody tr').each(function() {
                  var rowText = $(this).text().toLowerCase(); // Get the text content of the row and convert to lowercase
                  
                  if (rowText.includes(searchKey)) {
                      $(this).show(); // Show the row if it matches the search key
                    } else {
                      $(this).hide(); // Hide the row if it does not match
                    }
                  });
            });
            <?php if(isset($get->key)){ ?>
              $("#search").trigger('keyup');
            <?php } ?>
          });

        </script>
        <?php } ?>