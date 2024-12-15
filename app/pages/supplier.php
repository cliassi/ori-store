<?php 
if(METHOD == 'add'){
  require 'forms/supplier.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/supplier.php';
}  elseif(METHOD == 'details' && defined('ID')){
  require 'details/supplier.php';
} else{ 
  $fields = [
    "id" => '',
    "company" => ["label"=>"Comapny Name", "display"=>'', 'link'=>'details'],
    "contact" => ["label"=>"Contact Person", "display"=>''],
    "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    "city" => ["label"=>"City", "display"=>''],
    "address" => ["label"=>"Shop Address", "display"=>''],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image'],
    "" => ["display"=>'', 'type'=>'link', 'action'=>'edit'],
  ];
  $objs = R::find('supplier');
  $contant = "";
?>
<div class="row">
          <!-- Zero config table start -->
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header">
                <div class="row">
                  <div class="col"><h5>Supplier List</h5></div>
                  <div class="col"><input class="form-control" placeholder="Search Supplier" id="search"></div>
                </div>
                
              </div>
              <div class="card-body">
                <div class="dt-responsive table-responsive">
                  <table id="supplier-table" class="table table-striped table-bordered nowrap">
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
                          print "<td>$i</td>"; $i++;
                          foreach ($fields as $key => $value) {
                            if(isset($value['display'])) {
                              $printed = false;
                              if(isset($value['type'])){
                                if($value['type'] == "image") {
                                  print "<td height='60px'>".($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}'  height='60px'></a>" : "")."</td>";
                                  $printed = true;
                                } elseif($value['type'] == "link") {
                                  print "<td><a href='$page/edit/$obj->id'><i class='fa fa-edit'></i></a></td>";
                                  $printed = true;
                                }
                              }
                              if(!$printed) {
                                print "<td>";
                                if(isset($value['link'])) print "<a href='$page/{$value['link']}/$obj->id'>";
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
});

    </script>
        <?php } ?>