<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Daily Sales</title>
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
    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function setAmount(amount) {
            document.querySelector('input[name="amount"]').value = amount;
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from daily.php
$hotel_start_date = '2023-01-18 23:59:59';

$d = isset($get->d)?$get->d:today();
$t = isset($get->t)?$get->t:today();

if(!isUserIn(['apple','superadmin','melon','lemon','orange','mango', 'berry', 'Olive'])){
  // exit;
}

if(isset($post->collect_cash)){
  if(isUserIn([])){
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d", strtotime($post->date))]);
  } else{
    $handover = R::findOne("bd_handover", "date=?", [date("Y-m-d")]);
  }
  if(!$handover){
    $handover = R::dispense("bd_handover");
    $handover->date = today();
    $handover->account = 1;
    if(isset($post->amount)) $handover->amount = $post->amount;
    if(isset($post->bank_amount)) $handover->bank_amount = $post->bank_amount;
    $handover->created_by = uid();
    $handover->created_at = now();
    R::store($handover);

    alert("Added");
    redir("?");
  } else{
    $handover->date = today();
    $handover->account = 1;
    if(isset($post->amount)) $handover->amount = $post->amount;
    if(isset($post->bank_amount)) $handover->bank_amount = $post->bank_amount;
    $handover->created_by = uid();
    $handover->created_at = now();
    R::store($handover);

    alert("Updated");
    redir("?");
  }
}

if(uid()!=1){
  if(daydiff($d, prevDay()) > 0){
    $d = prevDay();
    $get->d = $d;
  }
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

// Get collection data
$_trans = [];
$i = 1;
$total = $credit = $debit = $total_b = $credit_b = $debit_b = 0;

if($_collection){
  $official_receipts = select("o.*, w.contact name, w.company, w.id wid", "collection o, customer w", "o.customer_id=w.id AND o.deleted_by IS NULL AND (o.created_at BETWEEN '$d 00:00:00' AND '$t 23:59:59')");
  while($official_receipt = mysqli_fetch_object($official_receipts)){
    $avatar = getName("sys_user", $official_receipt->created_by, 'u_avatar');
    if(file_exists("uploads/user/avatar/$avatar") && nn($avatar)){
      $avatar = "<img src='$appurl/uploads/user/avatar/$avatar' style='width:27px'>";
    } else{
      $avatar = $userList[$official_receipt->created_by];
    }
    
    if($official_receipt->payment_method == 'Cash'){
      $total += $official_receipt->amount;
      $credit += $official_receipt->amount;
      sum("cash", $official_receipt->amount);
    } else{
      $total_b += $official_receipt->amount;
      $credit_b += $official_receipt->amount;
      sum("bank", $official_receipt->amount);
    }
    $i++;
  }
}
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Daily Sales Report</h2>
        
        <!-- Date Filter -->
        <form class="mb-4">
            <input type="hidden" name="page" value="daily_sales">
            <input type="hidden" name="pm" value="<?php echo $pm; ?>">
            <div class="flex gap-2 text-sm">
                <input type="date" name="d" value="<?php echo $d; ?>" class="flex-1 px-2 py-1 rounded text-gray-800" style="width: 110px" >
                <span class="text-white">to</span>
                <input type="date" name="t" value="<?php echo $t; ?>" class="flex-1 px-2 py-1 rounded text-gray-800" style="width: 110px" >
                <button type="submit" class="bg-white text-primary px-3 py-1 rounded font-semibold">Filter</button>
                
            </div>
        </form>
        
        <!-- Filter Options -->
        <div class="space-y-2 text-sm">
            <div class="flex gap-2">
                <label class="flex items-center text-white">
                    <input type="checkbox" name="collection" value="1" <?php echo $_collection ? 'checked' : ''; ?> class="mr-1">
                    Collection
                </label>
                <label class="flex items-center text-white">
                    <input type="checkbox" name="expense" value="1" <?php echo $_expense ? 'checked' : ''; ?> class="mr-1">
                    Expense
                </label>
                <label class="flex items-center text-white">
                    <input type="checkbox" name="handover" value="1" <?php echo $_handover ? 'checked' : ''; ?> class="mr-1">
                    Handover
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-gray-600">Cash Collection</div>
                <div class="font-bold text-lg"><?php echo nf($credit); ?></div>
            </div>
            <div class="text-center">
                <div class="text-gray-600">Bank Collection</div>
                <div class="font-bold text-lg"><?php echo nf($credit_b); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Collection Table -->
<div class="max-w-sm mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">No.</th>
                        <th class="px-2 py-2 text-left">Particulars</th>
                        <th class="px-2 py-2 text-left">Name</th>
                        <th class="px-2 py-2 text-left">Company</th>
                        <th class="px-2 py-2 text-center">Entry By</th>
                        <th class="px-2 py-2 text-right">Cash</th>
                        <th class="px-2 py-2 text-right">Bank</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    if($_collection){
                        $official_receipts = select("o.*, w.contact name, w.company, w.id wid", "collection o, customer w", "o.customer_id=w.id AND o.deleted_by IS NULL AND (o.created_at BETWEEN '$d 00:00:00' AND '$t 23:59:59')");
                        while($official_receipt = mysqli_fetch_object($official_receipts)){
                            $avatar = getName("sys_user", $official_receipt->created_by, 'u_avatar');
                            if(file_exists("uploads/user/avatar/$avatar") && nn($avatar)){
                                $avatar = "<img src='$appurl/uploads/user/avatar/$avatar' style='width:20px'>";
                            } else{
                                $avatar = $userList[$official_receipt->created_by];
                            }
                            
                            echo "<tr class='border-b'>";
                            echo "<td class='px-2 py-1'>$i</td>";
                            echo "<td class='px-2 py-1 text-xs'>".stripslashes($official_receipt->description)."</td>";
                            echo "<td class='px-2 py-1 text-xs'>$official_receipt->name</td>";
                            echo "<td class='px-2 py-1 text-xs'><a href='/store/customer/details/$official_receipt->wid' class='text-blue-600'>$official_receipt->company</a></td>";
                            echo "<td class='px-2 py-1 text-center'>$avatar</td>";
                            
                            if($official_receipt->payment_method == 'Cash'){
                                echo "<td class='px-2 py-1 text-right'>".nf($official_receipt->amount)."</td>";
                                echo "<td class='px-2 py-1 text-right'>-</td>";
                            } else{
                                echo "<td class='px-2 py-1 text-right'>-</td>";
                                echo "<td class='px-2 py-1 text-right'>".nf($official_receipt->amount)."</td>";
                            }
                            echo "</tr>";
                            $i++;
                        }
                    }
                    ?>
                    
                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="5" class="px-2 py-2">TOTAL</td>
                        <td class="px-2 py-2 text-right"><?php echo nf($credit); ?></td>
                        <td class="px-2 py-2 text-right"><?php echo nf($credit_b); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Collect Button -->
<?php if(isUserIn(['apple', 'superadmin', 'orange'])): ?>
<div class="max-w-sm mx-auto px-4 mt-4 text-center">
    <button type="button" onclick="openModal('collectionModal')" class="bg-blue-600 text-white px-6 py-2 rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Collect</button>
</div>
<?php endif; ?>

<!-- Collection Modal -->
<div id="collectionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" onclick="if(event.target === this) closeModal('collectionModal')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4">
        <div class="bg-primary text-white p-4 flex justify-between items-center rounded-t-lg">
            <h5 class="font-bold text-lg">New Collection</h5>
            <button type="button" class="text-white text-2xl font-bold hover:text-gray-200" onclick="closeModal('collectionModal')">&times;</button>
        </div>
        <div class="p-4">
                <form method="post" enctype="multipart/form-data" id="collectionForm">
                    <div class="space-y-4">
                        <!-- Customer -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Customer</label>
                            <select name="customer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                                <option value="">Select Customer</option>
                                <?php
                                $customers = R::find('customer', 'ORDER BY company');
                                foreach ($customers as $cust) {
                                    echo "<option value='" . $cust->id . "'>" . htmlspecialchars($cust->company) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Entry Date -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Entry Date</label>
                            <input type="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Payment Date -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Payment Date</label>
                            <input type="date" name="payment_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Amount</label>
                            <input type="number" name="amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" step="0.01" required>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="bg-gray-200 text-gray-800 py-2 rounded font-semibold text-sm" onclick="setAmount(100)">100</button>
                            <button type="button" class="bg-gray-200 text-gray-800 py-2 rounded font-semibold text-sm" onclick="setAmount(500)">500</button>
                            <button type="button" class="bg-gray-200 text-gray-800 py-2 rounded font-semibold text-sm" onclick="setAmount(1000)">1000</button>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Payment Method</label>
                            <div class="flex gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="Cash" class="mr-2" required> Cash
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="Bank" class="mr-2"> Bank
                                </label>
                            </div>
                        </div>

                        <!-- Particulars -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Particulars</label>
                            <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" rows="3" required></textarea>
                        </div>
                    </div>
                </form>
        </div>
        <div class="p-4 flex gap-2 justify-end border-t border-gray-200 rounded-b-lg bg-gray-50">
            <button type="button" class="bg-gray-300 text-gray-800 px-4 py-2 rounded font-semibold hover:bg-gray-400 transition-colors" onclick="closeModal('collectionModal')">Cancel</button>
            <button type="submit" form="collectionForm" name="save" class="bg-primary text-white px-4 py-2 rounded font-semibold hover:bg-primaryDark transition-colors">Save Collection</button>
        </div>
    </div>
</div>

</body>
</html>
