<?php
if(isset($post->save)){
	$obj = R::dispense('city');

	if(isset($post->id)){
		$obj = R::load('city', $post->id);
	}
	$obj->name = $post->name;
  $obj->branch_id = $branch_id;
	R::store($obj);
	redir("?");
}

if(isset($get->token)){
  $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
  if($token){
    R::trash($token);
    if(uid()==1 && isset($get->del)){
      $object = R::load('city', $get->del);
      R::trash($object);
    redir("?");
    }

  } else{
    redir("?");
  }
}

?>
  <div class="row">
    <!-- Zero config table start -->
    <div class="col-sm-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col"><h5>Areas</h5></div>
            <div class="col"></div>
          </div>
          
        </div>
        <div class="card-body">
          <div class="dt-responsive table-responsive">
            <table id="customer-table" class="table table-striped table-bordered nowrap">
              <thead>
                <tr>
                  <th width="100px">No.</th>
                  <th>Area Name</th>
                  <th>Shop Count</th>
                  <th width="100px"><a data-bs-toggle='modal' data-bs-target='#modalForm' class='btn btn-success btn-sm'><i class='fa fa-file'></i> Add</a></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $objs = R::find('city', 'branch_id = '.$branch_id);
                $i = 1;
                foreach ($objs as $key => $obj) {
                  	print "<tr>";
                  	print "<td>$i</td>";
                  	print "<td id='item-{$obj->id}'>$obj->name</td>";
                  	print "<td><a href='customer?key=$obj->name'>".getFieldValue("customer", "COUNT(id)", "city='$obj->name'")."</a></td>";
	                print "<td><a data-bs-toggle='modal' data-bs-target='#modalForm' onClick='setItem($obj->id)' class='btn btn-primary btn-sm' ><i class='fas fa-edit'></i></a> 
                  <a class='btn btn-danger protected-link btn-sm' href='?del=$obj->id' ><i class='fas fa-trash'></i></a></td>";
                  	print "</tr>";
                  	$i++;
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

        <div id="modalForm" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="stockreTurnLabel" aria-hidden="true">
          <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="stockreTurnLabel">Add/Modify Area<span></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  <input type='hidden' name='id' class='id' value=''>
                </div>
                <div class="modal-body">
                  <div class='row'>
                    <div class='col-sm-12'>              
                      <div class='form-group'>
                        <lable>Area Name</lable>
                        <input class='form-control' type="text" step='1' name='name' id="input-name">
                      </div>
                    </div>  
                  </div>
                  <br>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" name='save' class="btn btn-primary">Save</button>
                </div>
              </div>
            </div>
          </form>
        </div>    
        <script>
        	function setItem(id){
        		$(".id").val(id);
        		$("#input-name").val($("#item-" + id).text());
        	}
        </script>
