<?php
if (!defined('ID')) {
    setFlash('danger', 'Invalid request');
    redirect(ROOT . "/$page");
}

$division = R::load('division', ID);
if (!$division->id) {
    setFlash('danger', 'Division not found');
    redirect(ROOT . "/$page");
}
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Division Details</h5>
                <div class="float-end">
                    <a href="<?php echo ROOT . "/$page/edit_division/" . $division->id; ?>" class="btn btn-primary btn-sm">
                        <i class='fa fa-edit'></i> Edit
                    </a>
                    <a href="<?php echo ROOT . "/$page"; ?>" class="btn btn-secondary btn-sm">
                        <i class='fa fa-arrow-left'></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Name:</th>
                                <td><?php echo htmlspecialchars($division->name); ?></td>
                            </tr>
                            <tr>
                                <th>Code:</th>
                                <td><?php echo htmlspecialchars($division->code); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php echo $division->status == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($division->status); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($division->description)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Description</h5>
                        <div class="border p-3">
                            <?php echo nl2br(htmlspecialchars($division->description)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="<?php echo ROOT . "/$page/edit_division/" . $division->id; ?>" class="btn btn-primary">
                        <i class='fa fa-edit'></i> Edit Division
                    </a>
                    <a href="<?php echo ROOT . "/$page"; ?>" class="btn btn-secondary">
                        <i class='fa fa-arrow-left'></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
