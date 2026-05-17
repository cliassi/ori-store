<?php
// Investment Management Page - CRUD with soft delete

// Soft delete action - uses trash column
if (isset($get->delete) && (int) $get->delete > 0) {
    $bean = R::load('investment', (int) $get->delete);
    if ($bean && $bean->id) {
        $bean->deleted_by = uid();
        $bean->deleted_at = now();
        $bean->trash = 1;
        R::store($bean);
    }
    redir('invest');
}

// Restore action
if (isset($get->restore) && (int) $get->restore > 0) {
    $bean = R::load('investment', (int) $get->restore);
    if ($bean && $bean->id) {
        $bean->deleted_by = null;
        $bean->deleted_at = null;
        $bean->trash = 0;
        R::store($bean);
    }
    redir('invest');
}

// Save action (add/edit)
if (isset($post->save_investment)) {
    $id = (int) (isset($post->investment_id) ? $post->investment_id : 0);

    $bean = $id > 0 ? R::load('investment', $id) : R::dispense('investment');

    if ($bean) {
        $bean->amount = (float) (isset($post->amount) ? $post->amount : 0);
        $bean->particulars = (string) (isset($post->particulars) ? $post->particulars : '');
        $bean->payment_method = (string) (isset($post->payment_method) ? $post->payment_method : 'Bank');
        $bean->date = (string) (isset($post->date) ? $post->date : today());

        if ($id == 0) {
            // New record
            $bean->created_by = uid();
            $bean->created_at = now();
            $bean->trash = 0;
        } else {
            // Update record
            $bean->updated_by = uid();
            $bean->updated_at = now();
        }

        R::store($bean);
    }
    redir('invest');
}

$showAllInvestments = isset($get->show) && $get->show === 'all';
$investmentLimitSql = $showAllInvestments ? '' : ' LIMIT 10';
$fromDate = isset($get->from) && $get->from ? (string) $get->from : date('Y-m-d', strtotime('-30 days'));
$toDate = isset($get->to) && $get->to ? (string) $get->to : today();
$filterQuery = 'from=' . urlencode($fromDate) . '&to=' . urlencode($toDate);

// Get investments
$investments = R::getAll("SELECT i.*, 
    u1.u_fullname as created_by_name, 
    u2.u_fullname as updated_by_name,
    u3.u_fullname as deleted_by_name
    FROM investment i
    LEFT JOIN sys_user u1 ON i.created_by = u1.id
    LEFT JOIN sys_user u2 ON i.updated_by = u2.id
    LEFT JOIN sys_user u3 ON i.deleted_by = u3.id
    WHERE i.date BETWEEN ? AND ?
    ORDER BY i.date DESC, i.created_at DESC" . $investmentLimitSql, [$fromDate, $toDate]);

// Calculate running totals separately for Cash and Bank
$cashBalance = 0;
$bankBalance = 0;
foreach ($investments as &$inv) {
    $amount = (float) (isset($inv['amount']) ? $inv['amount'] : 0);
    $inv['payment_method'] = isset($inv['payment_method']) ? $inv['payment_method'] : 'Bank';

    if (empty($inv['trash'])) {
        if ($inv['payment_method'] == 'Cash') {
            $cashBalance += $amount;
        } else {
            $bankBalance += $amount;
        }
    }

    $inv['cash_balance'] = $cashBalance;
    $inv['bank_balance'] = $bankBalance;
}
unset($inv);

?>

<style>
    .bg-cash {
        background-color: rgba(255, 235, 59, 0.65) !important;
    }

    .bg-bank {
        background-color: rgba(255, 127, 80, 0.65) !important;
    }

    .amount-cell {
        text-align: right !important;
    }

    .bg-investment-total {
        background-color: #f3f4f6 !important;
    }

    .bg-slate {
        background-color: #e2e8f0 !important;
    }

    h2,
    h3,
    h4,
    h5 {
        text-align: center;
    }

    .investment-header {
        position: relative;
        display: block !important;
    }

    .investment-header h4 {
        width: 100%;
        margin: 0;
    }

    .investment-header .btn {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header investment-header">
                <h4>Investment Management Report</h4>

            </div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        Date <input type="date" name="from" class="form-control w150" style='display:inline-block'
                            value="<?php echo htmlspecialchars($fromDate, ENT_QUOTES); ?>">
                        -
                        <input type="date" name="to" class="form-control w150" style='display:inline-block'
                            value="<?php echo htmlspecialchars($toDate, ENT_QUOTES); ?>">
                        <button type="submit" class="btn btn-primary"
                            style='width: 80px;display:inline-block;margin-left:20px;' ;>Filter</button>
                    </div>
                    <?php if ($showAllInvestments): ?>
                        <input type="hidden" name="show" value="all">
                    <?php endif; ?>
                    <div class="col-md-5">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#investmentModal"
                            onclick="resetForm()">
                            <i class="fa fa-plus"></i> Add Investment
                        </button>
                    </div>
                </form>
                <div class="mb-2 text-end">
                    <?php if ($showAllInvestments): ?>
                        <a href="invest?<?php echo $filterQuery; ?>">Show Last 10</a>
                    <?php else: ?>
                        <a href="invest?<?php echo $filterQuery; ?>&show=all">Show All</a>
                    <?php endif; ?>
                </div>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th class="amount-cell">Cash</th>
                            <th class="amount-cell">Total Cash<br>Investment</th>
                            <th class="amount-cell">Bank</th>
                            <th class="amount-cell">Total Bank<br>Investment</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        $lastIndex = count($investments) - 1;
                        foreach ($investments as $inv): ?>
                            <tr
                                class="<?php echo ($inv['trash'] ? 'table-danger ' : '') . (($i - 1) === $lastIndex ? 'fw-bold' : ''); ?>">
                                <td class="bg-slate">
                                    <?php echo $i++; ?>
                                </td>
                                <td class="bg-slate">
                                    <?php echo $inv['date'] ? df($inv['date']) : ''; ?>
                                </td>
                                <td class="bg-slate">
                                    <?php echo htmlspecialchars(isset($inv['particulars']) ? $inv['particulars'] : ''); ?>
                                </td>
                                <td class="bg-cash amount-cell">
                                    <?php echo ($inv['payment_method'] == 'Cash' && (float) $inv['amount'] > 0) ? nf($inv['amount']) : ''; ?>
                                </td>
                                <td class="bg-cash amount-cell"><strong>
                                        <?php echo nf($inv['cash_balance']); ?>
                                    </strong></td>
                                <td class="bg-bank amount-cell">
                                    <?php echo ($inv['payment_method'] == 'Bank' && (float) $inv['amount'] > 0) ? nf($inv['amount']) : ''; ?>
                                </td>
                                <td class="bg-bank amount-cell"><strong>
                                        <?php echo nf($inv['bank_balance']); ?>
                                    </strong></td>
                                <td class="bg-slate">
                                    <?php echo isset($inv['created_by_name']) ? $inv['created_by_name'] : 'System'; ?>
                                </td>
                                <td class="bg-slate">
                                    <?php echo $inv['created_at'] ? date('d/m/Y H:i', strtotime($inv['created_at'])) : '-'; ?>
                                </td>
                                <td class="bg-slate">
                                    <?php if (!$inv['trash']): ?>
                                        <button class="btn btn-sm btn-warning"
                                            onclick="editInvestment(<?php echo htmlspecialchars(json_encode($inv), ENT_QUOTES); ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $inv['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this investment?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?restore=<?php echo $inv['id']; ?>" class="btn btn-sm btn-success"
                                            onclick="return confirm('Restore this investment?')">
                                            <i class="fa fa-undo"></i> Restore
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($investments)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No investments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="10" class="text-end bg-investment-total">
                                <h3>Total Investment :
                                    <?php echo nf($cashBalance + $bankBalance); ?>
                                </h3>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="investmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="investment_id" id="investment_id" value="0">

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="date" value="<?php echo today(); ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Particulars</label>
                        <textarea class="form-control" name="particulars" id="particulars" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" name="payment_method" id="payment_method">
                            <option value="Bank">Bank</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="save_investment">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('investment_id').value = '0';
        document.getElementById('date').value = '<?php echo today(); ?>';
        document.getElementById('amount').value = '';
        document.getElementById('particulars').value = '';
        document.getElementById('payment_method').value = 'Bank';
        document.getElementById('modalTitle').textContent = 'Add Investment';
    }

    function editInvestment(data) {
        document.getElementById('investment_id').value = data.id;
        document.getElementById('date').value = data.date;
        document.getElementById('amount').value = data.amount;
        document.getElementById('particulars').value = data.particulars || '';
        document.getElementById('payment_method').value = data.payment_method || 'Bank';
        document.getElementById('modalTitle').textContent = 'Edit Investment';

        var modal = new bootstrap.Modal(document.getElementById('investmentModal'));
        modal.show();
    }
</script>