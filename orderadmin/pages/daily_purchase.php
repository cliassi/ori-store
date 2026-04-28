<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Daily Purchase</title>
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
        td{text-align: left;}
        .modal th{background-color: rgba(120,250,70, .1) !important;}
        .modal th span{font-weight: 700;}
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from purchase.php
$hotel_start_date = '2023-01-18 23:59:59';

$d = isset($get->d)?$get->d:today();
$t = isset($get->t)?$get->t:today();

if(!isUserIn(['apple','superadmin','melon','lemon','orange','mango', 'berry', 'Olive'])){
  exit;
}

if(isset($post->delivered)){
  $order = R::load('order', $post->delivered);
  $order->delivered_by = uid();
  $order->delivery_date = now();
  R::store($order);
}

$com = isset($get->company) ? $get->company : '';
$ec = isset($get->expense_category) ? $get->expense_category : '';

if(!isset($get->collection) && !isset($get->expense) && !isset($get->handover)){
  $_collection =  $_handover = $_expense = true;
} else{
  $_collection = isset($get->collection) ? $get->collection : false;
  $_handover = isset($get->handover) ? $get->handover : false;
  $_expense = isset($get->expense) ? $get->expense : false;
}

$pm = isset($get->pm) ? $get->pm : 'Outsource';

$companies = toA("company");
$userList = userList();
$suppliers = toA('supplier', 'id', 'company');

$trans = select("SELECT * FROM (SELECT i.*, GROUP_CONCAT('<div style=\"border-bottom: solid 1px #ccc;\">',description,', <b class=\"frht\">(', price ,' X ', quantity,' = ',(quantity*price),')</b></div>' SEPARATOR '') particulars, SUM(quantity) quantity, SUM(quantity*price) total FROM `order` i, order_item ii WHERE i.id=ii.order_id AND order_date BETWEEN '$d' AND '$t' GROUP BY i.id) a ORDER BY id");
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Daily Purchase Report</h2>
        
        <!-- Date Filter -->
        <form class="mb-4">
            <input type="hidden" name="page" value="daily_purchase">
            <input type="hidden" name="pm" value="<?php echo $pm; ?>">
            <div class="flex gap-2 text-sm">
                <input type="date" name="d" value="<?php echo $d; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
                <span class="text-white">to</span>
                <input type="date" name="t" value="<?php echo $t; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
                <button type="submit" class="bg-white text-primary px-3 py-1 rounded font-semibold">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-gray-600">Total Orders</div>
                <div class="font-bold text-lg"><?php echo mysqli_num_rows($trans); ?></div>
            </div>
            <div class="text-center">
                <div class="text-gray-600">Total Amount</div>
                <div class="font-bold text-lg"><?php echo nf(sum('total')); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Table -->
<div class="max-w-sm mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">No.</th>
                        <th class="px-2 py-2 text-left">Date</th>
                        <th class="px-2 py-2 text-left">Invoice</th>
                        <th class="px-2 py-2 text-left">Supplier</th>
                        <th class="px-2 py-2 text-left">Products</th>
                        <th class="px-2 py-2 text-right">Qty</th>
                        <th class="px-2 py-2 text-right">Total</th>
                        <th class="px-2 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($item = mysqli_fetch_object($trans)) {
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'>$i</td>";
                        echo "<td class='px-2 py-1'>".df($item->order_date)."</td>";
                        echo "<td class='px-2 py-1'>INV".zerofill($item->id, 5)."</td>";
                        echo "<td class='px-2 py-1'><a href='/store/supplier/details/$item->supplier_id' class='text-blue-600'>".$suppliers[$item->supplier_id]."</a></td>";
                        echo "<td class='px-2 py-1 text-xs'>$item->particulars</td>";
                        echo "<td class='px-2 py-1 text-right'>".nf0($item->quantity)."</td>";
                        echo "<td class='px-2 py-1 text-right'>".nf($item->total)."</td>";
                        
                        // Status
                        if($item->delivered_by) {
                            echo "<td class='px-2 py-1 text-center'><span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Received</span></td>";
                        } else {
                            echo "<td class='px-2 py-1 text-center'>";
                            echo "<form method='post' class='inline'>";
                            echo "<input type='hidden' name='delivered' value='$item->id'>";
                            echo "<button type='submit' class='bg-yellow-100 text-yellow-800 px-1 py-0.5 rounded text-xs'>Ordered</button>";
                            echo "</form>";
                            echo "</td>";
                        }
                        
                        echo "</tr>";
                        sum('total',$item->total);
                        sum('quantity',$item->quantity);
                        $i++;
                    }
                    ?>
                    
                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="5" class="px-2 py-2">TOTAL</td>
                        <td class="px-2 py-2 text-right"><?php echo nf0(sum('quantity')); ?></td>
                        <td class="px-2 py-2 text-right"><?php echo nf(sum('total')); ?></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                </tbody>
            </table>
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

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});
</script>

</body>
</html>
