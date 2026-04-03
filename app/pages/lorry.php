<?php
// lorry.php
// ADD / EDIT LORRY
if (isset($post->save_lorry)) {

    if (!empty($post->id)) {
        // Edit
        $obj = R::load('lorry', $post->id);
        if (!$obj->id)
            redir(ROOT . '/lorry');
    } else {
        // Add (prevent duplicate)
        $exists = R::findOne('lorry', 'lorry_no = ?', [trim($post->lorry_no)]);
        if ($exists) {
            alert('Lorry number already exists');
            redir(ROOT . '/lorry');
        }
        $obj = R::dispense('lorry');
    }

    $obj->lorry_no = trim($post->lorry_no);
    $obj->driver_name = trim($post->driver_name);

    R::store($obj);
    redir(ROOT . '/lorry');
}

// DELETE LORRY
if (isset($get->del)) {
    $obj = R::load('lorry', $get->del);
    if ($obj->id)
        R::trash($obj);
    redir(ROOT . '/lorry');
}

// LIST LORRIES (old RedBean compatible)
$lorries = R::find('lorry', '1 ORDER BY id DESC');
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between mb-3">
                    <h5>Lorry List</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lorryModal"
                        onclick="openAddLorry()">Add Lorry</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lorry No</th>
                                <th>Driver Name</th>
                                <th width="140">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lorries) {
                                $i = 1;
                                foreach ($lorries as $l) { ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($l->lorry_no) ?></td>
                                        <td><?= htmlspecialchars($l->driver_name) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#lorryModal" onclick="openEditLorry(
                        <?= $l->id ?>,
                        '<?= htmlspecialchars($l->lorry_no) ?>',
                        '<?= htmlspecialchars($l->driver_name) ?>'
                      )">Edit</button>

                                            <a href="?del=<?= $l->id ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No lorries found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="lorryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="lorryTitle">Add Lorry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="lorryForm">
                    <input type="hidden" name="id" id="lorry_id">

                    <div class="mb-3">
                        <label class="form-label">Lorry Number</label>
                        <input type="text" name="lorry_no" id="lorry_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Driver Name</label>
                        <input type="text" name="driver_name" id="driver_name" class="form-control" required>
                    </div>

                    <!-- IMPORTANT -->
                    <input type="hidden" name="save_lorry" value="1">
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveLorryBtn">Save</button>
            </div>

        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        // Open ADD
        window.openAddLorry = function () {
            $('#lorryTitle').text('Add Lorry');
            $('#lorry_id').val('');
            $('#lorry_no').val('');
            $('#driver_name').val('');
        }

        // Open EDIT
        window.openEditLorry = function (id, no, name) {
            $('#lorryTitle').text('Edit Lorry');
            $('#lorry_id').val(id);
            $('#lorry_no').val(no);
            $('#driver_name').val(name);
        }

        // SAVE (ADD / EDIT)
        $('#saveLorryBtn').on('click', function () {

            if ($('#lorry_no').val().trim() === '' || $('#driver_name').val().trim() === '') {
                alert('Please fill all fields');
                return;
            }

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $('#lorryForm').serialize(),
                success: function () {
                    location.reload(); // reload list
                },
                error: function () {
                    alert('Something went wrong');
                }
            });
        });

    });
</script>