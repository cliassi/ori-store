<style type="text/css">
  a.btn{
    color:#fff !important;
  }
</style>
<?php
$obj = R::dispense('supplier');
if (defined('ID')) {
    $obj = R::load('supplier', ID);
}
print "
<div class='row'>
          <!-- Zero config table start -->
          <div class='col-sm-12'>
            <div class='card'>
              <div class='card-header text-center'>
                <h5>$obj->company</h5>
                <h5><a class=' px-5 mb-1' href='tel:$obj->mobile'>$obj->contact  (<i class='fa fa-phone-alt'></i> $obj->mobile)</a></h5>
                <div><img src='".ROOT."/$obj->image' height='250px'></div>
              </div>
              <div class='card-body'>
                <div class='dt-responsive table-responsive'>
                  <table id='simpletable' class='table table-striped table-bordered nowrap'>
                    <thead>
                      <tr>
                        <th class='text-center'><a class='btn btn-success'>Order</a></th>
                        <th class='text-center'><a class='btn btn-warning'>refund</a></th>
                        <th class='text-center'><a class='btn btn-danger'>Refund</a></th>
                      </tr>
                    </thead>
                    <tbody>
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
        </div>";