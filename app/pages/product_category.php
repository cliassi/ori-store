<?php
// ============ DELETE with PIN Verification ============
if (isset($post->idToDelete) && isset($post->pin)) {
    $user = R::findOne('sys_user', 'u_username = ? AND u_pin = ?', ['Adminn', $post->pin]);
    if ($user) {
        $cat = R::load('product_category', $post->idToDelete);
        R::trash($cat);
        redir("?");
    } else {
        $currentUser = R::load('sys_user', uid());
        if ($currentUser->u_pin == $post->pin && $currentUser->u_username !== 'Adminn') {
            echo "<script>alert('You are not permitted to delete product categories!');</script>";
        } else {
            echo "<script>alert('Invalid PIN!');</script>";
        }
    }
}

// ============ SAVE (Add/Edit) ============
if (isset($post->save_category)) {
    $id = isset($post->category_id) ? (int)$post->category_id : 0;
    $cat = $id > 0 ? R::load('product_category', $id) : R::dispense('product_category');
    
    $cat->name = trim(isset($post->name) ? $post->name : '');
    $cat->sort_order = isset($post->sort_order) && $post->sort_order !== '' ? (int)$post->sort_order : null;
    $cat->uom = isset($post->uom) && trim($post->uom) !== '' ? trim($post->uom) : null;
    $cat->uom2 = isset($post->uom2) && trim($post->uom2) !== '' ? trim($post->uom2) : null;
    
    R::store($cat);
    redir("?");
}

// ============ FETCH DATA ============
$categories = R::find('product_category', '1 =1 ORDER BY sort_order ASC, name ASC');
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5>Product Category List</h5>
                    </div>
                    <div class="col text-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-category" onclick="openAddModal()">
                            <i class='fa fa-plus'></i> Add Category
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table class="table table-striped table-bordered nowrap">
                        <thead>
                            <tr>
                                <th width="60">Sl.</th>
                                <th>Name</th>
                                <th width="100">Sort Order</th>
                                <th width="120">UOM</th>
                                <th width="120">UOM2</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($cat->name) ?></td>
                                <td><?= isset($cat->sort_order) && $cat->sort_order !== '' ? $cat->sort_order : '-' ?></td>
                                <td><?= htmlspecialchars(isset($cat->uom) && $cat->uom !== '' ? $cat->uom : '-') ?></td>
                                <td><?= htmlspecialchars(isset($cat->uom2) && $cat->uom2 !== '' ? $cat->uom2 : '-') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-link" onclick="openEditModal(<?= $cat->id ?>, '<?= addslashes($cat->name) ?>', <?= isset($cat->sort_order) && $cat->sort_order !== '' ? $cat->sort_order : 0 ?>, '<?= addslashes(isset($cat->uom) && $cat->uom !== '' ? $cat->uom : '') ?>', '<?= addslashes(isset($cat->uom2) && $cat->uom2 !== '' ? $cat->uom2 : '') ?>')">
                                        <i class='fa fa-edit'></i>
                                    </button>
                                    <button class="btn btn-sm btn-link text-danger" onclick="openDeleteModal(<?= $cat->id ?>, '<?= addslashes($cat->name) ?>')">
                                        <i class='fa fa-trash'></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($categories) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No categories found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="modal-category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="category_id" id="category_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="cat_name" class="form-control" required maxlength="128">
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" id="cat_sort_order" class="form-control" min="0" max="255">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label">UOM</label>
                                <input type="text" name="uom" id="cat_uom" class="form-control" maxlength="128">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label">UOM2</label>
                                <input type="text" name="uom2" id="cat_uom2" class="form-control" maxlength="128">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_category" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete PIN Modal -->
<div class="modal fade" id="modal-delete-pin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-top-4 border-danger">
            <form method="post">
                <input type="hidden" name="idToDelete" id="delete_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Security Check</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Enter PIN to delete: <b id="delete_name"></b></p>
                    <div class="mb-3">
                        <label class="form-label">PIN</label>
                        <input type="password" name="pin" class="form-control text-center" maxlength="10" required autofocus autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Delete</button>
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('category_id').value = '0';
    document.getElementById('modal-title').textContent = 'Add Category';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_sort_order').value = '';
    document.getElementById('cat_uom').value = '';
    document.getElementById('cat_uom2').value = '';
}

function openEditModal(id, name, sortOrder, uom, uom2) {
    document.getElementById('category_id').value = id;
    document.getElementById('modal-title').textContent = 'Edit Category';
    document.getElementById('cat_name').value = name;
    document.getElementById('cat_sort_order').value = sortOrder || '';
    document.getElementById('cat_uom').value = uom || '';
    document.getElementById('cat_uom2').value = uom2 || '';
    
    var modal = new bootstrap.Modal(document.getElementById('modal-category'));
    modal.show();
}

function openDeleteModal(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    var modal = new bootstrap.Modal(document.getElementById('modal-delete-pin'));
    modal.show();
}
</script>
