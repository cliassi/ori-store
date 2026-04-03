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
                <input type="date" name="d" value="<?php echo $d; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
                <span class="text-white">to</span>
                <input type="date" name="t" value="<?php echo $t; ?>" class="flex-1 px-2 py-1 rounded text-gray-800">
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

<!-- Collection Form -->
<?php if(isUserIn(['apple', 'superadmin', 'orange'])): ?>
<div class="max-w-sm mx-auto px-4 mt-4">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <form method="post" class="space-y-3">
            <div class="text-center font-semibold text-gray-800 mb-3">Collection Summary</div>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cash Amount</label>
                    <input type="number" name="amount" value="<?php echo $credit; ?>" step="0.05" required class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Bank Amount</label>
                    <input type="number" name="bank_amount" value="<?php echo $credit_b; ?>" step="0.05" required class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <?php if(isUserIn([])): ?>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?php echo today(); ?>" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <?php endif; ?>
            
            <div class="text-center">
                <button type="submit" name="collect_cash" class="bg-blue-600 text-white px-4 py-2 rounded font-semibold text-sm">Collect</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

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
