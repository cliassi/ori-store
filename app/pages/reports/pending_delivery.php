<?php 
if(METHOD == 'add'){
  require 'forms/customer.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/customer.php';
}  elseif(METHOD == 'details' && defined('ID')){
  require 'details/customer.php';
} else{ 
  $fields = [
    "id" => '',
    "code" => ["label"=>"Code", "display"=>''],
    "company" => ["label"=>"Comapny Name", "display"=>'', 'link'=>'../../customer/pending_delivery'], 
    "contact" => ["label"=>"Contact Person", "display"=>''],
    "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    "city" => ["label"=>"City", "display"=>''],
    "due" => ["label"=>"Due", "display"=>'', 'type'=>'due'],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image'],
  ];
  $objs = R::find('customer', "id IN (SELECT DISTINCT customer_id FROM invoice i INNER JOIN invoice_item ii WHERE i.id=ii.invoice_id AND ii.delivered_at IS NULL)");
  $contant = "";
  ?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col"><h5>Customer Due Report</h5></div>
            <div class="col"><input class="form-control" placeholder="Search Customer" id="search"></div>
          </div>
          
        </div>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="customer-table" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <?php foreach ($fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
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
                          print "<td height='84px'>".($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='/store/{$obj->$key}' height='60px'></a>" : "")."</td>";
                          $printed = true;
                        } elseif($value['type'] == 'due'){                        	
													$transfer_tran = getSum("invoice i, invoice_item ii", "price*quantity", "i.id=ii.invoice_id AND customer_id=$obj->id");
													$transfer_col = getSum("collection", "amount", "customer_id=$obj->id");
                          print "<td class='right'>".nf($transfer_tran - $transfer_col)."</td>";
                          sum('total',$transfer_tran - $transfer_col);
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
                      <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class='right'>Total</th>
                        <th class='right'><?php print nf(sum('total')); ?></th>
                        <th></th>
                        <th></th>
                      </tr>
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