<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Supplier Due</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from sdue.php
$fields = [
    "id" => '',
    "company" => ["label"=>"Company Name", "display"=>'', 'link'=>'../../supplier/details'],
    "contact" => ["label"=>"Contact Person", "display"=>''],
    "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    "city" => ["label"=>"City", "display"=>''],
    "due" => ["label"=>"Due", "display"=>'', 'type'=>'due'],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image'],
];
$objs = R::find('supplier');
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Supplier Due Report</h2>
        <div class="relative">
            <input type="text" id="search" placeholder="Search supplier" class="w-full px-3 py-2 rounded text-gray-800 text-sm">
        </div>
    </div>
</div>

<!-- Summary Card -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="text-center">
            <div class="text-gray-600 text-sm">Total Due Amount</div>
            <div class="font-bold text-2xl text-red-600"><?php echo nf(sum('total')); ?></div>
        </div>
    </div>
</div>

<!-- Supplier Table -->
<div class="max-w-sm mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="supplier-table" class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">Company</th>
                        <th class="px-2 py-2 text-left">Contact</th>
                        <th class="px-2 py-2 text-left">Mobile</th>
                        <th class="px-2 py-2 text-left">City</th>
                        <th class="px-2 py-2 text-right">Due</th>
                        <th class="px-2 py-2 text-center">Photo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($objs as $key => $obj) {
                        $transfer_tran = getSum("`order` i, order_item ii", "cost*quantity", "i.id=ii.order_id AND supplier_id=$obj->id");
                        $transfer_col = getSum("payment", "amount", "supplier_id=$obj->id");
                        $due_amount = $transfer_tran - $transfer_col;
                        
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'><a href='../../supplier/details/$obj->id' class='text-blue-600 font-medium'>$obj->company</a></td>";
                        echo "<td class='px-2 py-1'>$obj->contact</td>";
                        echo "<td class='px-2 py-1'>$obj->mobile</td>";
                        echo "<td class='px-2 py-1'>$obj->city</td>";
                        echo "<td class='px-2 py-1 text-right font-semibold text-red-600'>".nf($due_amount)."</td>";
                        echo "<td class='px-2 py-1 text-center'>";
                        if($obj->image) {
                            echo "<img src='/store/{$obj->image}' height='40px' class='rounded'>";
                        }
                        echo "</td>";
                        echo "</tr>";
                        sum('total', $due_amount);
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('search').addEventListener('keyup', function() {
    var searchKey = this.value.toLowerCase();
    
    document.querySelectorAll('#supplier-table tbody tr').forEach(function(row) {
        var rowText = row.textContent.toLowerCase();
        
        if (rowText.includes(searchKey)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
