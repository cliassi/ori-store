<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Daily Order</title>
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
        body.modal-dcollect_pendingopen{ overflow: hidden; }
        td{text-align: left;}
        .modal th{background-color: rgba(120,250,70, .1) !important;}
        .modal th span{font-weight: 700;}
        .w150{width: 150px !important;}
        td{
            white-space: nowrap;
            border: solid 1px #e7eaee;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from order.php
$hotel_start_date = '2023-01-18 23:59:59';

$d = isset($get->d)?$get->d:today();
$t = isset($get->t)?$get->t:today();

if(!isUserIn(['apple','superadmin','melon','lemon','orange','mango', 'berry', 'Olive','Sagor'])){
  // exit;
}

if (isset($post->collect) && isset($get->salesman)) {
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($get->salesman)?$get->salesman:0;
  $obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
  if(isset($post->save)){
    $obj->salesman_id = $get->salesman;
    $obj->date = today();
    $obj->created_by = uid();

    $stored = false;

    foreach ($post->variance as $id => $qty) {
      if($qty == 0) continue;
      if(!$stored){ R::store($obj); $stored = true; }
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("stock_collect_item");

      $ii->stock_collect_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = "$variance->particulars $variance->size x $variance->unit";
      $ii->created_by = uid();
      $ii->branch_id = $obj->branch_id;

      R::store($ii);
    }

    redir(ROOT."/salesman/details/$obj->salesman_id");
  }
}

if(isset($post->save_delivery)){
  $order = R::load('invoice', $post->invoice_id);
  $order->delivered_by = uid();
  $order->delivery_staff = $post->delivery_staff;
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
$sm = isset($get->salesman) ? $get->salesman : '';
$cat = isset($get->cat) ? $get->cat + 0 : '';
$prod = isset($get->prod) ? $get->prod + 0 : '';
$pv = isset($get->pv) ? $get->pv + 0 : '';

$customers = toA('customer', 'id', 'company');
$salesmans = toA('salesman');

$query = "SELECT * FROM (
SELECT IFNULL(ii.delivery_date,i.invoice_date) dd, c.mobile, i.*, invoiceItems(i.id) particulars, 
    SUM(ii.quantity) quantity, SUM(ii.quantity*ii.price) total, SUM(ii.quantity*(ii.price-ii.cost)) profit, SUM(ii.quantity*(ii.cost)) cost FROM invoice i
  INNER JOIN invoice_item ii ON i.id=ii.invoice_id 
  INNER JOIN customer c ON c.id=i.customer_id INNER JOIN city ON c.city=city.name 
  INNER JOIN product_variance pv ON pv.id=ii.product_variance_id 
  INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id 
  INNER JOIN product_category pc ON p.product_category_id=pc.id 
  WHERE ".($prod ? "p.id = $prod AND " : "")." ".($pv ? "pv.id = $pv AND " : "")." i.invoice_date BETWEEN '$d' AND '$t' ".($sm ? "AND i.salesman_id=$sm":"")." GROUP BY i.id) a ORDER BY id";

$trans = select($query);

// Pre-calculate totals for summary cards
$totalQty = 0;
$totalAmt = 0;
$transData = [];
while ($item = mysqli_fetch_object($trans)) {
    $transData[] = $item;
    $totalQty += $item->quantity;
    $totalAmt += $item->total;
}
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Daily Order Report</h2>
        
        <!-- Date Filter -->
        <form class="mb-4">
            <input type="hidden" name="page" value="daily_order">
            <input type="hidden" name="pm" value="<?php echo $pm; ?>">
            <div class="flex gap-1 text-sm items-center">
                <input type="date" name="d" value="<?php echo $d; ?>" class="flex-1 px-1 py-0.5 rounded text-gray-800 text-xs" style="min-width: 0;">
                <span class="text-white text-xs">to</span>
                <input type="date" name="t" value="<?php echo $t; ?>" class="flex-1 px-1 py-0.5 rounded text-gray-800 text-xs" style="min-width: 0;">
                <button type="submit" class="bg-white text-primary px-2 py-0.5 rounded font-semibold text-xs whitespace-nowrap">Filter</button>
            </div>
        </form>
        
        <!-- Additional Filters -->
        <div class="space-y-2 text-sm">
            <div class="flex gap-2">
                <select name="prod" id="prodFilter" class="w-1/2 px-1 py-1 rounded text-gray-800 text-xs" style="min-width: 0;" onchange="updateVarianceFilter()">
                    <option value="">All Products</option>
                    <?php
                    $products = select('*', 'product');
                    $productList = [];
                    while($product = mysqli_fetch_object($products)) {
                        $productList[$product->id] = $product;
                        $selected = ($prod == $product->id) ? 'selected' : '';
                        echo "<option value='$product->id' $selected>$product->name</option>";
                    }
                    ?>
                </select>
                <select name="pv" id="pvFilter" class="w-1/2 px-1 py-1 rounded text-gray-800 text-xs" style="min-width: 0;">
                    <option value="">All Variances</option>
                    <?php
                    $variances = select('*', 'product_variance', 'visible=1', 'ORDER BY particulars');
                    $varianceList = [];
                    while($pv = mysqli_fetch_object($variances)) {
                        $varianceList[$pv->id] = $pv;
                        $selected = ($get->pv == $pv->id) ? 'selected' : '';
                        echo "<option value='$pv->id' data-product='$pv->product_id' $selected>$pv->particulars</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <script>
        function updateVarianceFilter() {
            const prodId = document.getElementById('prodFilter').value;
            const pvSelect = document.getElementById('pvFilter');
            const options = pvSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                const productId = option.getAttribute('data-product');
                if (!prodId || productId === prodId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset variance selection if current selection doesn't match product
            const selectedOption = pvSelect.options[pvSelect.selectedIndex];
            if (selectedOption && selectedOption.value !== '' && selectedOption.getAttribute('data-product') !== prodId && prodId !== '') {
                pvSelect.value = '';
            }
        }
        // Initialize on page load
        updateVarianceFilter();
        </script>
    </div>
</div>

<!-- Summary Cards -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                <div class="text-gray-600">Total Orders</div>
                <div class="font-bold text-lg"><?php echo count($transData); ?></div>
            </div>
            <div class="text-center">
                <div class="text-gray-600">Total Quantity</div>
                <div class="font-bold text-lg"><?php echo nf0($totalQty); ?></div>
            </div>
            <div class="text-center">
                <div class="text-gray-600">Total Amount</div>
                <div class="font-bold text-lg"><?php echo nf($totalAmt); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="max-w-sm mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <form method="post">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left">No.</th>
                            <th class="px-2 py-2 text-left">Date</th>
                            <th class="px-2 py-2 text-left">Invoice</th>
                            <th class="px-2 py-2 text-left">Customer</th>
                            <th class="px-2 py-2 text-left">Products</th>
                            <th class="px-2 py-2 text-right">Qty</th>
                            <th class="px-2 py-2 text-right">Total</th>
                            <th class="px-2 py-2 text-center">Status</th>
                            <th class="px-2 py-2 text-center">Salesman</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($transData as $item) {
                            echo "<tr class='border-b'>";
                            echo "<td class='px-2 py-1'>$i</td>";
                            echo "<td class='px-2 py-1'>".df($item->invoice_date)."<br>".df($item->dd)."</td>";
                            echo "<td class='px-2 py-1'>INV".zerofill($item->id, 5)."</td>";
                            echo "<td class='px-2 py-1'>
                            <a href='https://wa.me/{$item->mobile}' target='_blank' style='margin-right: 5px; display: inline-block'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 640' style='width:18px; height:18px;'><path fill='rgb(34, 181, 94)' d='M476.9 161.1C435 119.1 379.2 96 319.9 96C197.5 96 97.9 195.6 97.9 318C97.9 357.1 108.1 395.3 127.5 429L96 544L213.7 513.1C246.1 530.8 282.6 540.1 319.8 540.1L319.9 540.1C442.2 540.1 544 440.5 544 318.1C544 258.8 518.8 203.1 476.9 161.1zM319.9 502.7C286.7 502.7 254.2 493.8 225.9 477L219.2 473L149.4 491.3L168 423.2L163.6 416.2C145.1 386.8 135.4 352.9 135.4 318C135.4 216.3 218.2 133.5 320 133.5C369.3 133.5 415.6 152.7 450.4 187.6C485.2 222.5 506.6 268.8 506.5 318.1C506.5 419.9 421.6 502.7 319.9 502.7zM421.1 364.5C415.6 361.7 388.3 348.3 383.2 346.5C378.1 344.6 374.4 343.7 370.7 349.3C367 354.9 356.4 367.3 353.1 371.1C349.9 374.8 346.6 375.3 341.1 372.5C308.5 356.2 287.1 343.4 265.6 306.5C259.9 296.7 271.3 297.4 281.9 276.2C283.7 272.5 282.8 269.3 281.4 266.5C280 263.7 268.9 236.4 264.3 225.3C259.8 214.5 255.2 216 251.8 215.8C248.6 215.6 244.9 215.6 241.2 215.6C237.5 215.6 231.5 217 226.4 222.5C221.3 228.1 207 241.5 207 268.8C207 296.1 226.9 322.5 229.6 326.2C232.4 329.9 268.7 385.9 324.4 410C359.6 425.2 373.4 426.5 391 423.9C401.7 422.3 423.8 410.5 428.4 397.5C433 384.5 433 373.4 431.6 371.1C430.3 368.6 426.6 367.2 421.1 364.5z'/></svg></a>
                            <a href='/store/customer/details/$item->customer_id' class='text-blue-600'>".$customers[$item->customer_id]."</a></td>";
                            echo "<td class='px-2 py-1 text-xs'>$item->particulars</td>";
                            echo "<td class='px-2 py-1 text-right'>".nf0($item->quantity)."</td>";
                            echo "<td class='px-2 py-1 text-right'>".nf($item->total)."</td>";
                            
                            // Status and delivery
                            if($item->delivered_by) {
                                echo "<td class='px-2 py-1 text-center'><span class='bg-green-100 text-green-800 px-1 py-0.5 rounded text-xs'>Received</span></td>";
                            } else {
                                echo "<td class='px-2 py-1 text-center'><button onclick='openDeliveryModal($item->id)' class='bg-yellow-100 text-yellow-800 px-1 py-0.5 rounded text-xs'>Ordered</button></td>";
                            }
                            
                            echo "<td class='px-2 py-1 text-center text-xs'>$item->salesman</td>";
                            echo "</tr>";
                            
                            $i++;
                        }
                        ?>
                        
                        <tr class="bg-gray-100 font-semibold">
                            <td colspan="5" class="px-2 py-2">TOTAL</td>
                            <td class="px-2 py-2 text-right"><?php echo nf0($totalQty); ?></td>
                            <td class="px-2 py-2 text-right"><?php echo nf($totalAmt); ?></td>
                            <td colspan="2" class="px-2 py-2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- Delivery Modal -->
<div class="modal custom-fallback" id="deliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Assignment</h5>
                <button type="button" class="btn-close" onclick="closeModal('deliveryModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <input type="hidden" name="invoice_id" id="invoice_id" value="">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Delivery Staff</label>
                            <select name="delivery_staff" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Please select</option>
                                <?php
                                $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
                                while ($man = mysqli_fetch_object($objs)) {
                                    echo "<option value='$man->name'>$man->name</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deliveryModal')">Close</button>
                    <button type="submit" name="save_delivery" class="btn btn-primary">Save</button>
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

function openDeliveryModal(invoiceId) {
    document.getElementById('invoice_id').value = invoiceId;
    openModal('deliveryModal');
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
