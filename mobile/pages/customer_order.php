<?php
global $c;

// Handle approve action
if (isset($_POST['action']) && $_POST['action'] === 'approve' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $updateSql = "UPDATE customer_order SET status = 'approved' WHERE id = $orderId";
    mysqli_query($c, $updateSql);
    header("Location: ?page=customer_order");
    exit;
}

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    
    // First delete related items
    $deleteItemsSql = "DELETE FROM customer_order_item WHERE customer_order_id = $orderId";
    mysqli_query($c, $deleteItemsSql);
    
    // Then delete the order
    $deleteOrderSql = "DELETE FROM customer_order WHERE id = $orderId";
    mysqli_query($c, $deleteOrderSql);
    
    // Redirect to refresh the page
    header("Location: ?page=customer_order");
    exit;
}

// Get filter parameters
$fromDate = date('Y-m-d', strtotime('-5 days'));
$toDate = date('Y-m-d');

$get = (object)(isset($_GET) ? $_GET : []);
if (isset($get) && isset($get->from) && $get->from) {
    $fromDate = preg_replace('/[^0-9\-]/', '', (string)$get->from);
}
if (isset($get) && isset($get->to) && $get->to) {
    $toDate = preg_replace('/[^0-9\-]/', '', (string)$get->to);
}
?>

<form class="mb-3" method="get" action="">
    <input type="hidden" name="page" value="customer_order">
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="col-6">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Shop</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Time</th>
                <th>Items</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $fromSql = mysqli_real_escape_string($c, $fromDate);
            $toSql = mysqli_real_escape_string($c, $toDate);
            $where = " WHERE DATE(co.invoice_date) BETWEEN '$fromSql' AND '$toSql' ";

            $totalQtyAll = 0;
            $totalAmtAll = 0.0;

            $sql = "SELECT co.id, co.customer_id, co.invoice_date, co.status, c.company, c.contact,
                           coItems(co.id) items_html,
                           IFNULL(SUM(i.quantity),0) qty,
                           IFNULL(SUM(i.quantity*i.price),0) amount
                      FROM customer_order co
                      LEFT JOIN customer c ON c.id=co.customer_id
                      LEFT JOIN customer_order_item i ON i.customer_order_id=co.id
                      LEFT JOIN product_variance pv ON pv.id=i.product_variance_id
                      $where
                     GROUP BY co.id
                     ORDER BY co.id DESC
                     LIMIT 50";
            $res = mysqli_query($c, $sql);
            
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $totalQtyAll += $row['qty'];
                    $totalAmtAll += $row['amount'];
                    
                    // Calculate elapsed time
                    $orderDate = new DateTime($row['invoice_date']);
                    $now = new DateTime();
                    $interval = $orderDate->diff($now);
                    $days = $interval->days;
                    $hours = $interval->h;
                    $timePassed = ($days > 0) ? "{$days}d {$hours}h" : "{$hours}h";
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['company']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($row['invoice_date'])); ?></td>
                        <td><?php echo $timePassed; ?></td>
                        <td><?php echo $row['items_html']; ?></td>
                        <td><?php echo number_format($row['qty']); ?></td>
                        <td class="text-end"><?php echo number_format($row['amount'], 2); ?></td>
                        <td>
                            <?php if ($row['status'] != 'approved') { ?>
                                <form method="post" style="display:inline-block; margin-right:5px;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Delete this order?')" style="display:inline-block;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php } else { ?>
                                <span class="badge bg-success">Approved</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="9" class="text-center">No orders found</td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="table-primary fw-bold">
                <td colspan="6">Total</td>
                <td><?php echo number_format($totalQtyAll); ?></td>
                <td class="text-end"><?php echo number_format($totalAmtAll, 2); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
function confirmDelete(orderId) {
    if (confirm('Are you sure you want to delete this order?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="order_id" value="' + orderId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>