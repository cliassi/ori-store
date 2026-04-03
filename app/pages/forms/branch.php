<?php
$id = defined('ID') ? ID : 0;
$branch = $id ? R::load('branch', $id) : R::dispense('branch');
$divisions = R::find('division', ' parent_id IS NOT NULL AND status = ? ORDER BY name', ['active']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $branch->serial = filter_input(INPUT_POST, 'serial', FILTER_SANITIZE_STRING);
    $branch->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $branch->division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $branch->address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $branch->phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $branch->status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) === 'active' ? 'active' : 'inactive';
    
    $div = R::load('division', $branch->division_id);
    $div = R::load('division', $div->parent_id);
    $branch->code = $div->serial.zerofill($branch->serial, 4);
    
    try {
        $id = R::store($branch);
        print "Branch " . ($id ? 'updated' : 'added') . ' successfully!';
        // redirect(ROOT . "/$page");
        redir("/store/division_branch?tab=branches");
    } catch (Exception $e) {
        print "Error: " . $e->getMessage();
    }
}
if(isset($get->dist)){
    $branch->division_id = $get->dist;
}
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5><?php echo $id ? 'Edit' : 'Add New'; ?> Branch</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="serial" class="form-label">Serial <span class="text-danger">*</span></label>
                                <input type="number" step="1" class="form-control" id="serial" name="serial" required 
                                       value="<?php echo htmlspecialchars(isset($branch->serial) ? $branch->serial : ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars(isset($branch->name) ? $branch->name : ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" disabled
                                       value="<?php echo htmlspecialchars(isset($branch->code) ? $branch->code : ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="division_id" class="form-label">District <span class="text-danger">*</span></label>
                                <select class="form-select" id="division_id" name="division_id" required>
                                    <option value="">-- Select District --</option>
                                    <?php foreach ($divisions as $div): ?>
                                        <option value="<?php echo $div->id; ?>" 
                                            <?php echo (isset($branch->division_id) && $branch->division_id == $div->id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($div->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($branch->phone); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="address" class="form-label">Person</label>
                                <input class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($branch->address); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusActive" 
                                           value="active" <?php echo (!isset($branch->status) || $branch->status === 'active') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusActive">Active</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusInactive" 
                                           value="inactive" <?php echo (isset($branch->status) && $branch->status === 'inactive') ? 'checked' : ''; ?>>
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
    
    // Initialize select2 for better dropdown experience
    if ($.fn.select2) {
        $('#division_id').select2({
            placeholder: '-- Select Division --',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
