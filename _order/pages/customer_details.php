<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Customer Details</title>
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        /* .transaction-row:nth-child(even) { background-color: rgba(24, 204, 99, 0.1); }
        .transaction-row:nth-child(odd) { background-color: rgba(21, 108, 214, 0.1); } */
        td{
            white-space: nowrap;
        }
        a,button{
            font-size: 12px;
        }
        tr.even{
            background-color: #efefef !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Load customer data
$obj = R::dispense('customer');
if (isset($get->id)) {
    $obj = R::load('customer', $get->id);
}

// Handle form submissions
if(isset($post->deliver)){
    foreach($post->delivered_by as $key => $value){
        $ii = R::load('invoice_item', $key);
        $ii->delivered_by = trim($value);
        $ii->delivered_at = now();
        R::store($ii);
    }
    redir("?");
}

if(uid()==1 && isset($get->delids)){
    if(isset($get->delids)){
        del("invoice_item", "invoice_id IN ($get->delids)");
        del("invoice", "id IN ($get->delids)");
    }
    redir("?");
}

if(isset($post->idToDelete)){
    $inv = R::load("invoice", $post->idToDelete);
    del("invoice_item", "invoice_id='$inv->id'");
    R::trash($inv);
}

if(isset($post->idToDelete2)){
    $col = R::load("collection", $post->idToDelete2);
    R::trash($col);
}

if(isset($post->save_remarks)){
    $customer_remarks = R::dispense("customer_remarks");
    $customer_remarks->customer_id = $obj->id;
    $customer_remarks->notes = $post->remarks;
    $customer_remarks->priority = 'high';
    $customer_remarks->entry_by = uid();
    R::store($customer_remarks);
    redir("?");
}

if(isset($get->rm)){
    $customer_remarks = R::load("customer_remarks", $get->rm);
    R::trash($customer_remarks);
    redir("?");
}

if(isset($get->delivered)){
    $invoice = R::load('invoice', $get->delivered);
    $invoice->delivered_by = uid();
    $invoice->delivery_date = now();
    R::store($invoice);
    redir("?");
}

if(isset($get->approved)){
    $collection = R::load('collection', $get->approved);
    $collection->approved_by = uid();
    $collection->approved_at = now();
    R::store($collection);
    redir("?");
}

if(isset($post->save_delivery_date)){
    $invoice = R::load("invoice", $post->save_delivery_date);
    $invoice->invoice_date = $post->delivery_date;
    R::store($invoice);
}

// Get transactions
$limit = "";
$opening = 0;

    $trans = select("SELECT * FROM (SELECT * FROM (
        SELECT 'invoice' src, '' pm, '' ab, i.id, ii.id id2, IFNULL(ii.delivery_date,i.invoice_date) dd, i.invoice_date sort_date, i.invoice_date date, i.created_at, i.delivered_by, i.created_by, (SELECT particulars FROM product_variance WHERE product_variance.id=ii.product_variance_id) particulars, ii.price * ii.quantity amount FROM `invoice` i, `invoice_item` ii WHERE i.id=ii.invoice_id AND i.customer_id=$obj->id
        UNION
        SELECT 'collection' src, payment_method pm, approved_by ab, id, 0 id2, '' dd, created_at sort_date, date, created_at, approved_by delivered_by, created_by, description particulars, amount FROM `collection` WHERE customer_id=$obj->id
    ) a ORDER BY date DESC, created_at DESC $limit) b ORDER BY sort_date, id");

$users = userList();
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-4">
            <a href="javascript:history.back()" class="text-white">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-semibold">Customer Details</h1>
            <div class="w-6"></div>
        </div>
        
        <!-- Customer Info Card -->
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
            <h2 class="text-xl font-bold text-white"><?php echo $obj->company; ?></h2>
            <p class="text-white/80"><?php echo $obj->contact; ?></p>
            <a href="tel:<?php echo $obj->mobile; ?>" class="text-white/90 hover:text-white">
                <!-- <i class="fas fa-phone mr-1"></i><?php echo $obj->mobile; ?> -->
                <strong>RM <span id='last_balance'></span></h3></strong>
            </a>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="max-w-sm mx-auto -mt-4 mb-2">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <div class="grid grid-cols-3 gap-3">
            <a href="?page=home&customer=<?php echo $get->id; ?>" class="bg-green-500 hover:bg-green-600 text-white text-center p-1 rounded-lg font-medium transition-colors">
                <i class="fas fa-shopping-cart mr-2"></i>Order
            </a>
            <a href="?page=customer_collection&customer=<?php echo $get->id; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white text-center p-1 rounded-lg font-medium transition-colors">
                <i class="fas fa-money-bill-wave mr-2"></i>Collection
            </a>
            <button onclick="exportToExcel()" class="bg-blue-500 hover:bg-blue-600 text-white text-center py-1 rounded-lg text-sm transition-colors">
                <i class="fas fa-file-excel mr-1"></i>Export
            </button>
        </div>
        <!-- <div class="grid grid-cols-2 gap-3 bg-gray-100 p-2 rounded-lg p-2 mt-2">
            <h3>Last Balance: 
        </div> -->
    </div>
</div>

<!-- Transactions Table -->
<div class="max-w-sm mx-auto px-4 mb-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="font-semibold text-gray-800">Transaction History</h3>
            <?php if(isset($get->show) && $get->show == "all"): ?>
                <a href="?page=customer_details&id=<?php echo $get->id; ?>" class="text-sm text-blue-600 float-right">Show last 10</a>
            <?php else: ?>
                <a href="?page=customer_details&id=<?php echo $get->id; ?>&show=all" class="text-sm text-blue-600 float-right">Show all</a>
            <?php endif; ?>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">#</th>
                        <th class="px-2 py-2 text-left">Date</th>
                        <th class="px-2 py-2 text-left">Ref No.</th>
                        <th class="px-2 py-2 text-left">Particulars</th>
                        <th class="px-2 py-2 text-left">Delivery</th>
                        <th class="px-2 py-2 text-left">DD</th>
                        <th class="px-2 py-2 text-left">Invoice By</th>
                        <th class="px-2 py-2 text-center">Select</th>
                        <th class="px-2 py-2 text-right">Debit</th>
                        <th class="px-2 py-2 text-right">Credit</th>
                        <th class="px-2 py-2 text-right">Balance</th>
                        <th class="px-2 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $counter = $trans->num_rows;
                    $lastDate = '';
                    $class = 'odd';
                    $totalDebit = 0;
                    $totalCredit = 0;
                    $balance = 0;
                    $show = isset($get->show) ? $get->show : 10;
                    while ($item = mysqli_fetch_object($trans)) {
                        $sort_date = substr($item->sort_date, 0, 10);
                        if($lastDate == '') $lastDate = $sort_date;
                        if($lastDate != $sort_date){
                            $class = strpos($class, 'even') !== false ? 'odd' : 'even';
                            $lastDate = $sort_date;
                        } 
                        $lastDate = $sort_date;
                        if($show != 'all'){
                            $class .= ' '.($counter > $show ? 'hidden' : '');
                            $counter--;
                        }
                        $row = "<tr class='transaction-row $item->src $class'>";
                        $row .= "<td class='px-2 py-1'> $i</td>";
                        $row .= "<td class='px-2 py-1'>".df($sort_date)."</td>";
                        
                        if($item->src == 'invoice'){
                            $row .= "<td class='px-2 py-1 text-center'>INV".zerofill($item->id, 5)."-D</td>";
                        } else {
                            $row .= "<td class='px-2 py-1 text-center'>OR".zerofill($item->id, 7)."</td>";
                        }
                        
                        if($item->src == 'invoice'){
                            $oi = R::load("invoice_item", $item->id2);
                            if(!nn($oi->description)){
                                $oi->description = $item->particulars;
                            }
                            $row .= "<td class='px-2 py-1'>";
                            $row .= "<span class='text-xs'>$oi->description</span> ";
                            $row .= "<span class='text-xs '>($oi->price X $oi->quantity = ".nf($oi->quantity*$oi->price).")</span>";
                            $row .= "</td>";
                            
                           
                            $row .= "<td class='px-2 py-1 text-center'>";
                            if($oi->delivered_by) {
                                $row .= "<span class='inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded'>";
                                $row .= "<i class='fas fa-shipping-fast mr-1'></i>$oi->delivery_staff";
                                $row .= "</span>";
                            } else {
                                $row .= "<span class='inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded'>";
                                $row .= "<i class='fas fa-clock mr-1'></i>Pending";
                                $row .= "</span>";
                            }
                            $row .= "</td>";
                            $row .= "<td>";
                            if($item->src == 'invoice' && $item->dd) {
                                $row .= "<div class='text-xs '>".df($item->dd)."</div>";
                            }
                            $row .= "</td>";
                            $row .= "<td class='px-2 py-1 text-center'>".$users[$item->created_by]."</td>";
                            $row .= "<td class='px-2 py-1 text-center'>";
                            $row .= "<input type='radio' id='item_$item->id' value='$item->id' class='form-radio'>";
                            $row .= "</td>";
                            $row .= "<td class='px-2 py-1 text-right'>".nf($item->amount)."</td>";
                            $row .= "<td class='px-2 py-1'></td>";
                            $totalDebit += $item->amount;
                            $balance += $item->amount;
                        } else {
                            if(strrpos($item->particulars, 'bank account') !== FALSE){
                                $row .= "<td class='px-2 py-1'>".df($item->date)." $item->particulars</td>";
                                if($item->ab){
                                    $row .= "<td class='px-2 py-1 text-center'><span class='bg-green-100 text-green-800 text-xs px-2 py-1 rounded'>Approved</span></td>";
                                } else {
                                    $row .= "<td class='px-2 py-1 text-center'><span class='bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded'>Pending</span></td>";
                                }
                            } else {
                                $particulars = str_replace('kora hoyese ', '', $item->particulars);
                                $particulars = str_replace('a banking korse ', '', $particulars);
                                $row .= "<td class='px-2 py-1'>".df($item->date)." $particulars</td>";
                                $row .= "<td class='px-2 py-1'></td>";
                            }
                            $row .= "<td class='px-2 py-1'></td>";
                            $row .= "<td class='px-2 py-1'></td>";
                            $row .= "<td class='px-2 py-1'></td>";
                            $row .= "<td class='px-2 py-1'></td>";
                            $row .= "<td class='px-2 py-1 text-right'>".nf($item->amount)."</td>";
                            $totalCredit += $item->amount;
                            $balance -= $item->amount;
                        }
                        
                        $row .= "<td class='px-2 py-1 text-right font-medium'>".nf($balance)."</td>";
                        $row .= "<td class='px-2 py-1 text-center'>";
                        
                        if($item->src == 'invoice'){
                            if($_SESSION['UID'] == 1){
                                $row .= "<a href='".ROOT."/invoice/edit/$item->id' class='text-blue-600 mr-2'><i class='fas fa-edit'></i></a>";
                                $row .= "<button onclick='deleteConfirmation($item->id)' class='text-red-600'><i class='fas fa-trash'></i></button>";
                            }
                        } else {
                            if($_SESSION['UID'] == 1){
                                $row .= "<button onclick='deleteConfirmation2($item->id)' class='text-red-600'><i class='fas fa-trash'></i></button>";
                            }
                        }
                        $row .= "</td>";
                        $row .= "</tr>";
                        echo $row;
                        if(!strpos($class, 'hidden')) $i++;
                    }
                    ?>
                    
                    <!-- Totals Row -->
                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="8" class="px-2 py-2">TOTAL</td>
                        <td class="px-2 py-2 text-right"><?php echo nf($totalDebit); ?></td>
                        <td class="px-2 py-2 text-right"><?php echo nf($totalCredit); ?></td>
                        <td class="px-2 py-2 text-right" id='due'><?php echo nf($balance); ?></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Remarks -->
<?php
$remarks = select("*", "customer_remarks", "customer_id=$obj->id AND trash=0", "ORDER BY id DESC");
if($remarks->num_rows > 0):
?>
<div class="max-w-sm mx-auto px-4 mb-4">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Customer Remarks</h3>
        <?php
        while($remark = mysqli_fetch_object($remarks)){
            $priority = "info";
            if($remark->priority == 'High'){
                $priority = 'danger';
            } elseif($remark->priority == 'Low'){
                $priority = 'success';
            }
            echo "<div class='bg-$priority-100 border-l-4 border-$priority-500 p-3 mb-2 rounded'>";
            echo "<div class='flex justify-between items-start'>";
            echo "<p class='text-sm text-gray-700'>$remark->notes</p>";
            if(uid() == 1) {
                echo "<a href='?rm=$remark->id' class='text-red-600 ml-2'><i class='fas fa-trash'></i></a>";
            }
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
</div>
<?php endif; ?>

<!-- Hidden Forms -->
<form method="post" id="save_remarks_form">
    <input type="hidden" name='remarks' id='remarks'>
    <input type="hidden" name='save_remarks' id='save_remarks'>
</form>

<form method="post" id="delete_form">
    <input type="hidden" name='idToDelete' id='idToDelete'>
</form>

<form method="post" id="delete_form2">
    <input type="hidden" name='idToDelete2' id='idToDelete2'>
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Set last balance from due amount when page loads
document.addEventListener('DOMContentLoaded', function() {
    const dueAmount = document.getElementById('due').textContent;
    document.getElementById('last_balance').textContent = dueAmount;
});

function addRemarks(){
    Swal.fire({
        title: 'Enter your remarks',
        input: 'textarea',
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: (text) => {
            $("#remarks").val(text);
            $("#save_remarks_form").submit();
        }
    });
}

function redirectWithSelected() {
    const selectedRadios = document.querySelectorAll('input[type="radio"]:checked');
    const ids = Array.from(selectedRadios).map(r => r.value).join(',');
    const newPage = "http://18.138.68.206/store/app/pages/view/exportables/invoice.php?id=" + ids;
    window.open(newPage, '_blank');
}

function redirectWithSelected2() {
    if(confirm("Are you sure?")){
        const selectedRadios = document.querySelectorAll('input[type="radio"]:checked');
        const ids = Array.from(selectedRadios).map(r => r.value).join(',');
        const newPage = "?delids=" + ids;
        location.href = newPage;
    }
}

function deleteConfirmation(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $("#idToDelete").val(id);
            $("#delete_form").submit();
        }
    });
}

function deleteConfirmation2(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $("#idToDelete2").val(id);
            $("#delete_form2").submit();
        }
    });
}

// Radio button toggle functionality
$('input[type="radio"]').mousedown(function(e) {
    if (this.checked) {
        $(this).data('wasChecked', true);
    } else {
        $(this).data('wasChecked', false);
    }
});

$('input[type="radio"]').click(function(e) {
    if ($(this).data('wasChecked')) {
        $(this).prop('checked', false).trigger('change');
    }
});

function exportToExcel() {
    // Create a new workbook
    const wb = XLSX.utils.book_new();
    
    // Prepare data for export
    const exportData = [];
    
    // Add headers
    exportData.push([
        '#', 'Date', 'Ref No.', 'Particulars', 'Delivery', 'Invoice By', 
        'Debit', 'Credit', 'Balance'
    ]);
    
    // Get transaction data from the table
    const table = document.querySelector('table tbody');
    const rows = table.querySelectorAll('tr');
    
    rows.forEach((row, index) => {
        // Skip the totals row
        if (row.classList.contains('bg-gray-100')) return;
        
        const cells = row.querySelectorAll('td');
        if (cells.length >= 10) {
            const rowData = [
                cells[0].textContent.trim(), // #
                cells[1].textContent.trim(), // Date
                cells[2].textContent.trim(), // Ref No.
                cells[3].textContent.trim(), // Particulars
                cells[4].textContent.trim(), // Delivery
                cells[5].textContent.trim(), // Invoice By
                cells[7].textContent.trim(), // Debit
                cells[8].textContent.trim(), // Credit
                cells[9].textContent.trim()  // Balance
            ];
            exportData.push(rowData);
        }
    });
    
    // Add totals row
    const totalsRow = document.querySelector('tr.bg-gray-100');
    if (totalsRow) {
        const totalCells = totalsRow.querySelectorAll('td');
        if (totalCells.length >= 10) {
            exportData.push([
                'TOTAL', '', '', '', '', '',
                totalCells[7].textContent.trim(), // Total Debit
                totalCells[8].textContent.trim(), // Total Credit
                totalCells[9].textContent.trim()  // Total Balance
            ]);
        }
    }
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(exportData);
    
    // Set column widths
    ws['!cols'] = [
        { wch: 5 },   // #
        { wch: 12 },  // Date
        { wch: 15 },  // Ref No.
        { wch: 30 },  // Particulars
        { wch: 15 },  // Delivery
        { wch: 15 },  // Invoice By
        { wch: 12 },  // Debit
        { wch: 12 },  // Credit
        { wch: 12 }   // Balance
    ];
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Transaction History');
    
    // Generate filename
    const customerName = '<?php echo $obj->company; ?>';
    const filename = `Customer_${customerName}_Transactions_${new Date().toISOString().split('T')[0]}.xlsx`;
    
    // Save file
    XLSX.writeFile(wb, filename);
}
</script>

<!-- Include SheetJS library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>
