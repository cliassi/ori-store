<style>
  table tr td:first-child{
    width: 50px;
  }
  th{
    text-align: center;
  }
</style>
<?php 
$tab = isset($get->tab) ? $get->tab : 'divisions';

if(isset($get->delete) && isset($get->token)){
		$token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
		if($token){
			R::trash($token);
      $table = $tab == 'divisions' ? 'division' : ($tab == 'districts' ? 'division' : 'branch');
      if($table == 'division'){
        $obj = R::load($table, $get->delete);
        R::trash($obj);
      } elseif($table == 'district'){
        $obj = R::load($table, $get->delete);
        R::trash($obj);
      } elseif($table == 'branch'){
        $obj = R::load($table, $get->delete);
        R::trash($obj);
      }
		}
    redir("?tab=$tab");
}
if(METHOD == 'add_division' || METHOD == 'edit_division'){
  require 'forms/division.php';
} elseif(METHOD == 'add_branch' || METHOD == 'edit_branch'){
  require 'forms/branch.php';
} elseif(METHOD == 'add_district' || METHOD == 'edit_district'){
  require 'forms/division.php';
} elseif(METHOD == 'details_division' && defined('ID')){
  require 'details/division.php';
} elseif(METHOD == 'details_branch' && defined('ID')){
  require 'details/branch.php';
} else { 
  // Division fields
  $division_fields = [
    "id" => '',
    "serial" => ["label"=>"Serial", "display"=>''],
    "name" => ["label"=>"Division Name", "display"=>'', 'link'=>'details_division'],
    "code" => ["label"=>"Code", "display"=>''],
    "description" => ["label"=>"Description", "display"=>''],
    "status" => ["label"=>"Status", "display"=>'']
  ];
  
  // District fields
  $district_fields = [
    "id" => '',
    "serial" => ["label"=>"Serial", "display"=>''],
    "parent_id" => ["label"=>"Division Name", "display"=>'name'],
    "name" => ["label"=>"District Name", "display"=>'', 'link'=>'details_division'],
    "code" => ["label"=>"Area Code", "display"=>''],
    "description" => ["label"=>"Description", "display"=>''],
    "status" => ["label"=>"Status", "display"=>'']
  ];

  // Branch fields
  $branch_fields = [
    "id" => '',
    "serial" => ["label"=>"Serial", "display"=>''],
    "division" => ["label"=>"District Name", "display"=>'name'],
    "name" => ["label"=>"Branch Name", "display"=>'', 'link'=>'details_branch'],
    "code" => ["label"=>"Code", "display"=>''],
    "address" => ["label"=>"Person", "display"=>''],
    "phone" => ["label"=>"Phone", "display"=>''],
    "status" => ["label"=>"Status", "display"=>'']
  ];

  $divisions = R::find('division', 'parent_id IS NULL ORDER BY serial');
  $districts = R::find('division', 'parent_id IS NOT NULL ORDER BY serial');
  $branches = R::find('branch', (isset($get->dist) ? "division_id=$get->dist" : '1=1').' ORDER BY serial');

?>

<div class="row">
  <div class="col-sm-12">
    <div class="card">
      <!-- <div class="card-header">
        <h5>Division, Disctrict & Branch Management</h5>
      </div> -->
      <div class="card-body">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link <?php echo $tab == 'divisions' ? 'active' : ''; ?>" id="divisions-tab" data-bs-toggle="tab" href="#divisions" role="tab" aria-controls="divisions" aria-selected="true">Divisions</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $tab == 'districts' ? 'active' : ''; ?>" id="districts-tab" data-bs-toggle="tab" href="#districts" role="tab" aria-controls="districts" aria-selected="false">Districts</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $tab == 'branches' ? 'active' : ''; ?>" id="branches-tab" data-bs-toggle="tab" href="#branches" role="tab" aria-controls="branches" aria-selected="false">Branches</a>
          </li>
        </ul>
        
        <!-- Tab panes -->
        <div class="tab-content">
          <!-- Divisions Tab -->
          <div class="tab-pane <?php echo $tab == 'divisions' ? 'active' : ''; ?>" id="divisions" role="tabpanel" aria-labelledby="divisions-tab">
            <div class="dt-responsive table-responsive">
              <div class="mb-3 mt-3">
                <a href="<?php print ROOT.'/'.$page; ?>/add_division" class="btn btn-primary"><i class='fa fa-plus'></i> Add New Division</a>
                <h3 style="text-align: center;">List of Divisions</h3>
              </div>
              <table id="divisions-table" class="table table-striped table-bordered nowrap">
                <thead>
                  <tr>
                    <?php foreach ($division_fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  if($divisions) {
                    foreach ($divisions as $div) {
                      print "<tr>";
                      foreach ($division_fields as $key => $value) {
                        if(isset($value['display'])) {
                          $printed = false;
                          if(isset($value['link'])) {
                            print "<td><a href='$page/{$value['link']}/$div->id'>".(isset($div->$key) ? $div->$key : '')."</a></td>";
                            $printed = true;
                          } else {
                            print "<td>".(isset($div->$key) ? $div->$key : '')."</td>";
                            $printed = true;
                          }
                        }
                      }
                      print "<td>";
                      print "<a href='$page/edit_division/$div->id' class='me-2'><i class='fa fa-edit'></i></a>";
                      print "<a href='?tab=divisions&delete=$div->id' class='text-danger protected-link delete-division' data-id='$div->id'><i class='fa fa-trash'></i></a>";
                      print "</td>";
                      print "</tr>";
                    }
                  } else {
                    echo "<tr><td colspan='".(count($division_fields) + 1)."' class='text-center'>No divisions found</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>

          
          <!-- Districts Tab -->
          <div class="tab-pane <?php echo $tab == 'districts' ? 'active' : ''; ?>" id="districts" role="tabpanel" aria-labelledby="districts-tab">
            <div class="dt-responsive table-responsive">
              <div class="mb-3 mt-3">
                <a href="<?php print ROOT.'/'.$page; ?>/add_district" class="btn btn-primary"><i class='fa fa-plus'></i> Add New District</a>
                <h3 style="text-align: center;">List of Districts</h3>
              </div>
              <table id="districts-table" class="table table-striped table-bordered nowrap">
                <thead>
                  <tr>
                    <?php foreach ($district_fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
                    <th>Branch</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  if($districts) {
                    foreach ($districts as $dist) {
                      //Number of branch
                      $count = mysqli_fetch_object(select("count(*) c", "branch", "division_id = {$dist->id}"));
                      print "<tr>";
                      foreach ($district_fields as $key => $value) {
                        if(isset($value['display'])) {
                          $printed = false;
                          if(isset($value['link'])) {
                            print "<td><a href='$page?tab=branches&dist=$dist->id'>".(isset($dist->$key) ? $dist->$key : '')."</a></td>";
                            $printed = true;
                          } else {
                            if($key == 'parent_id') {
                              $parent = R::load('division', $dist->parent_id);
                              print "<td>".(isset($parent->name) ? $parent->name : '')."</td>";
                            } else {
                              print "<td>".(isset($dist->$key) ? $dist->$key : '')."</td>";
                            }
                            $printed = true;
                          }
                        }
                      }
                      print "<td>$count->c | <a href='$page/add_branch?dist=$dist->id'><i class='fa fa-plus-circle'></i> branch</a></td>";
                      print "<td>";
                      print "<a href='$page/edit_district/$dist->id' class='me-2'><i class='fa fa-edit'></i></a>";
                      print "<a href='?tab=districts&delete=$dist->id' class='text-danger protected-link delete-district' data-id='$dist->id'><i class='fa fa-trash'></i></a>";
                      print "</td>";
                      print "</tr>";
                    }
                  } else {
                    echo "<tr><td colspan='".(count($district_fields) + 1)."' class='text-center'>No divisions found</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Branches Tab -->
          <div class="tab-pane <?php echo $tab == 'branches' ? 'active' : ''; ?>" id="branches" role="tabpanel" aria-labelledby="branches-tab">
            <div class="dt-responsive table-responsive">
              <div class="mb-3 mt-3">
                <a href="<?php print ROOT.'/'.$page; ?>/add_branch" class="btn btn-primary"><i class='fa fa-plus'></i> Add New Branch</a>
                <h3 style="text-align: center;">List of Branches</h3>
              </div>
              <table id="branches-table" class="table table-striped table-bordered nowrap">
                <thead>
                  <tr>
                    <?php foreach ($branch_fields as $key => $value) if(isset($value['label'])) print "<th>{$value['label']}</th>"; ?>
                    <th>Switch Branch</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  if($branches) {
                    foreach ($branches as $branch) {
                      print "<tr>";
                      foreach ($branch_fields as $key => $value) {
                        if(isset($value['display'])) {
                          if($key === 'division') {
                            $div = R::load('division', $branch->division_id);
                            print "<td>".($div->name)."</td>";
                          } else if(isset($value['link'])) {
                            print "<td><a href='$page/{$value['link']}/$branch->id'>".($branch->$key)."</a></td>";
                          } else {
                            print "<td>".($branch->$key)."</td>";
                          }
                        }
                      }
                      print "<td><a href='#' class='switch-branch' data-id='$branch->id'><i class='fa fa-exchange'></i> Switch Branch</a></td>";
                      print "<td>";
                      print "<a href='$page/edit_branch/$branch->id' class='me-2'><i class='fa fa-edit'></i></a>";
                      print "<a href='?tab=branches&delete=$branch->id' class='text-danger protected-link delete-branch' data-id='$branch->id'><i class='fa fa-trash'></i></a>";
                      print "</td>";
                      print "</tr>";
                    }
                  } else {
                    echo "<tr><td colspan='".(count($branch_fields) + 1)."' class='text-center'>No branches found</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this item?
        <input type="hidden" id="delete-id">
        <input type="hidden" id="delete-type">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirm-delete">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
    // Handle branch switching
  $(document).on('click', '.switch-branch', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    const button = $(this);
    const originalHtml = button.html();
    
    button.html('<i class="fa fa-spinner fa-spin"></i> Switching...').prop('disabled', true);
    
    $.post('<?php echo ROOT; ?>/ajax/switch_branch.php', { branch_id: id })
      .done((response) => {
        if (response.success) {
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          button.html(originalHtml).prop('disabled', false);
        }
      })
      .fail(() => {
        button.html(originalHtml).prop('disabled', false);
      });
  });

</script>

<style>
.nav-tabs .nav-link {
  color: #495057;
  font-weight: 500;
}
.nav-tabs .nav-link.active {
  font-weight: 600;
}
</style>
<?php } ?>
