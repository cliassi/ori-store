<?php
if (!defined('ID')) {
    setFlash('danger', 'Invalid request');
    redirect(ROOT . "/$page");
}

$branch = R::load('branch', ID);
if (!$branch->id) {
    setFlash('danger', 'Branch not found');
    redirect(ROOT . "/$page");
}

// Load the related division
$division = R::load('division', $branch->division_id);
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Branch Details</h5>
                <div class="float-end">
                    <a href="<?php echo ROOT . "/$page/edit_branch/" . $branch->id; ?>" class="btn btn-primary btn-sm">
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
                                <td><?php echo htmlspecialchars($branch->name); ?></td>
                            </tr>
                            <tr>
                                <th>Code:</th>
                                <td><?php echo htmlspecialchars($branch->code); ?></td>
                            </tr>
                            <tr>
                                <th>Division:</th>
                                <td>
                                    <?php if ($division->id): ?>
                                        <a href="<?php echo ROOT . "/$page/details_division/" . $division->id; ?>">
                                            <?php echo htmlspecialchars($division->name); ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php echo $branch->status == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($branch->status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($branch->phone)): ?>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($branch->phone); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($branch->email)): ?>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($branch->email); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($branch->manager)): ?>
                            <tr>
                                <th>Manager:</th>
                                <td><?php echo htmlspecialchars($branch->manager); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($branch->address)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Address</h5>
                        <div class="border p-3">
                            <?php echo nl2br(htmlspecialchars($branch->address)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="<?php echo ROOT . "/$page/edit_branch/" . $branch->id; ?>" class="btn btn-primary">
                        <i class='fa fa-edit'></i> Edit Branch
                    </a>
                    <a href="<?php echo ROOT . "/$page"; ?>" class="btn btn-secondary">
                        <i class='fa fa-arrow-left'></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
