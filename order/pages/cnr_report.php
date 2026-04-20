<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Collection & Return Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#72ccd8',
                        primaryDark: '#5fbac6'
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <!-- Material Symbols Outlined -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Lato', sans-serif; }
        .blob-shape{border-bottom-left-radius:24px;border-bottom-right-radius:24px;position:relative;overflow:hidden}
        .icon-green{color:#47773f !important}
        .material-symbols-outlined{color:#47773f !important;font-variation-settings:'FILL' 0, 'wght' 400, 'opsz' 24}
        .modal.custom-fallback{
            position: fixed; inset: 0; width: 100%; height: 100%; display: none;
            align-items: center; justify-content: center; background: rgba(0,0,0,0.6); z-index: 9999;
        }
        .modal.custom-fallback.show{ display: flex; }
        .modal.custom-fallback .modal-dialog{margin: 0; width: 92%; max-width: 420px;}
        .modal.custom-fallback .modal-content{ border-radius: 8px; overflow: hidden; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.35);}
        .modal.custom-fallback .modal-header, .modal.custom-fallback .modal-body, .modal.custom-fallback .modal-footer{background: #ffffff;}
        .modal.custom-fallback .modal-body{ padding: 16px; }
        body.modal-open{ overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from delivery2.php
if(isset($post->update_returned)){
    foreach($post->ri as $key => $value){
        update("stock_collect_item", "returned_quantity=$value", "id=$key");
    }
}

if(isset($get->token)){
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if($token){
        R::trash($token);
        if(isset($get->del) && isset($get->id)){
            $st = R::load('stock_collect', $get->id);
            del("stock_collect_item", "stock_collect_id=".($get->id + 0));
            R::trash($st);
            redir("?s=$get->s");
        }
    }
}

if(isset($post->incentive)){
    update("staff_salary", "incentive='$post->incentive'", "name='$post->name'");
}

if(isset($post->save_incentive)){
    $incentive = R::dispense("incentive");
    $incentive->salesman = $get->s;
    $incentive->date = $post->date;
    $incentive->particulars = $post->particulars;
    $incentive->amount = $post->amount;
    $incentive->created_by = uid();
    $incentive->created_at = now();
    R::store($incentive);
}

if(isset($post->save_return)){
    $ret = R::load("stock_collect_item", $post->stock_item_id);
    $ret->returned_quantity = $post->returning;
    $ret->damaged_quantity = nn($post->damaged) ? $post->damaged : 0;
    $ret->damaged_cause = $post->particulars;
    R::store($ret);
}

$month = isset($get->month) ? $get->month : date("Y-m-01");
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Collection & Return Report</h2>
        <?php if(isset($get->s) && $get->s != 'all'): ?>
            <div class="text-center">
                <span class="text-lg font-semibold"><?php echo $get->s; ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Staff Selection -->
<?php if(!isset($get->s)): ?>
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Select Delivery Staff</h3>
        <div class="space-y-2">
            <?php
            $objs = select('distinct id, name, incentive', 'staff_salary', "category='Delivery Staff'");
            $i = 1;
            while ($obj = mysqli_fetch_object($objs)) {
                echo "<a href='?s=$obj->name' class='block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors'>";
                echo "<div class='font-medium'>$obj->name</div>";
                echo "</a>";
                $i++;
            }
            ?>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Delivery Staff Report -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">No.</th>
                        <th class="px-2 py-2 text-left">Date</th>
                        <th class="px-2 py-2 text-left">Ref</th>
                        <th class="px-2 py-2 text-left">Stock Collect</th>
                        <th class="px-2 py-2 text-center">Delivered</th>
                        <th class="px-2 py-2 text-center">Return</th>
                        <th class="px-2 py-2 text-center">Damaged</th>
                        <th class="px-2 py-2 text-center">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filter = "WHERE delivery_staff='$get->s'";
                    if($get->s == "all") {
                        $filter = "";
                    }
                    $trans = select("SELECT * FROM (SELECT * FROM (
                        SELECT 'stock_collect' src, id, delivery_staff, date, created_at, 
                        delivery_staff delivered_by, stockCollectItemsMini(id) particulars, 
                        (SELECT SUM(quantity) FROM `stock_collect_item` ii WHERE ii.stock_collect_id=stock_collect.id) profit, 
                        stockCollectItemsQty(id) particulars2, 
                        stockCollectItemsQtyDelivered(id) particulars3, 
                        stockCollectItemsQtyToReturn(id) particulars4,stockCollectItemsQtyPending(id) particulars5 
                        FROM `stock_collect` $filter
                    ) a ORDER BY created_at) b");

                    $i = 1;
                    while ($tran = mysqli_fetch_object($trans)) {
                        $stock = select("ii.*, p.name, pv.unit, pv.size, 
                        (SELECT IFNULL(SUM(IFNULL(quantity,0)),0) FROM `invoice_item_delviery` iid WHERE iid.invoice_item_id=ii.invoice_item_id) delivered", 
                        "`stock_collect_item` ii, `product_variance` pv, product p", "pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_collect_id=$tran->id");
                        
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'>$i</td>";
                        echo "<td class='px-2 py-1'>".df($tran->date)."</td>";
                        echo "<td class='px-2 py-1 text-center'>STC".zerofill($tran->id, 5)."</td>";
                        
                        $particular = $collect = $delivered = $returned = $pending = $damaged = $balance = "";
                        while ($item = mysqli_fetch_object($stock)) {
                            $particular .= "<div class='text-xs border-b border-gray-200 py-1'>$item->name $item->description $item->size x $item->unit</div>";
                            $collect .= "<div class='text-center py-1'>$item->quantity</div>";
                            $delivered .= "<div class='text-center py-1'>$item->delivered</div>";
                            $returned .= "<div class='text-center py-1'>$item->returned_quantity</div>";
                            $pending .= "<div class='text-center py-1 text-red-600'>".($item->damaged_quantity)."</div>";
                            $balance .= "<div class='text-center py-1 font-semibold'>".($item->quantity - ($item->delivered + $item->returned_quantity + $item->damaged_quantity))."</div>";
                        }
                        
                        echo "<td class='px-2 py-1'>$particular</td>";
                        echo "<td class='px-2 py-1'>$collect</td>";
                        echo "<td class='px-2 py-1'>$delivered</td>";
                        echo "<td class='px-2 py-1'>$returned</td>";
                        echo "<td class='px-2 py-1'>$pending</td>";
                        echo "<td class='px-2 py-1'>$balance</td>";
                        echo "</tr>";
                        $i++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Return Modal -->
<div class="modal custom-fallback" id="stockReturn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Stock</h5>
                <button type="button" class="btn-close" onclick="closeModal('stockReturn')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <input type="hidden" name="stock_item_id" id="stock_item_id" value="">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Returning Quantity</label>
                            <input type="number" name="returning" id="returning-quantity" step="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Damaged/Lost</label>
                            <input type="number" name="damaged" id="damaged-quantity" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div id="damaged-particulars" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Particulars & Settlement</label>
                            <textarea name="particulars" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('stockReturn')">Close</button>
                    <button type="submit" name="save_return" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function setId(id) {
    document.getElementById('stock_item_id').value = id;
    document.getElementById('returning-quantity').value = 0;
}

// Show/hide damaged particulars
document.getElementById('damaged-quantity').addEventListener('input', function() {
    const val = parseInt(this.value);
    const particulars = document.getElementById('damaged-particulars');
    
    if (!isNaN(val) && val > 0) {
        particulars.classList.remove('hidden');
    } else {
        particulars.classList.add('hidden');
        particulars.querySelector('textarea').value = '';
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});
</script>

</body>
</html>
