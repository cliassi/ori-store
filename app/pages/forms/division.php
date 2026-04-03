<?php
$id = defined('ID') ? ID : 0;
$division = $id ? R::load('division', $id) : R::dispense('division');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $division->serial = filter_input(INPUT_POST, 'serial', FILTER_SANITIZE_STRING);
    $division->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $division->code = strtoupper(filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING));
    $division->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $division->status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) === 'active' ? 'active' : 'inactive';
    if(isset($post->parent_id)) $division->parent_id = $post->parent_id;
    
    try {
        $id = R::store($division);

        $branches = R::find('branch', "division_id IN (SELECT id FROM division WHERE parent_id = $id)");
        if($branches){
          foreach ($branches as $branch) {
            $branch->code = $division->serial.zerofill($branch->serial, 4);
            R::store($branch);
          }
        }
        print 'Division ' . ($id ? 'updated' : 'added') . ' successfully!';
        redir("/store/division_branch?tab=".(strpos(METHOD, 'district') ? 'districts' : 'divisions'));
    } catch (Exception $e) {
        print "Error: " . $e->getMessage();
    }
}

$type = strpos(METHOD, 'district') ? 'District' : 'Division';
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5><?php echo $id ? 'Edit' : 'Add New'; ?> <?php echo $type; ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="serial" class="form-label">Serial <span class="text-danger">*</span></label>
                                <input type="number" step="1" class="form-control" id="serial" name="serial" required 
                                       value="<?php echo htmlspecialchars(isset($division->serial) ? $division->serial : ''); ?>">
                            </div>
                        </div>
                        <?php if($type=='District'): ?>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Division Name <span class="text-danger">*</span></label>
                                <select name="parent_id" id="parent_id" class="form-control">
                                    <option value="">Select Division</option>
                                    <?php 
                                    $divs = R::find('division', 'parent_id IS NULL ORDER BY name');
                                    foreach ($divs as $div) {
                                        echo "<option value=\"{$div->id}\" " . ($div->id == $division->parent_id ? 'selected' : '') . ">{$div->name}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo $type; ?> Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($division->name); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" required 
                                       value="<?php echo htmlspecialchars($division->code); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo htmlspecialchars($division->description); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusActive" 
                                           value="active" <?php echo (!isset($division->status) || $division->status === 'active') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusActive">Active</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusInactive" 
                                           value="inactive" <?php echo (isset($division->status) && $division->status === 'inactive') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusInactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                        <a href="<?php echo ROOT . "/$page"; ?>" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-generate code from name if code is empty
    $('#name').on('blur', function() {
        if (!$('#code').val()) {
            const name = $(this).val();
            const code = name.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 5);
            $('#code').val(code);
        }
    });
});
</script>
