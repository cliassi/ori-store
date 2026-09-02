<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Customer Collection</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
$obj = R::dispense('collection');
if (isset($get->id)) {
    $obj = R::load('collection', $get->id);
}

if (isset($post->save)) {
    try {
        $obj->customer_id = $post->customer_id;
        $obj->date = $post->date;
        $obj->amount = $post->amount;
        $obj->payment_method = $post->payment_method;
        $obj->description = $post->description;
        $obj->created_by = $_SESSION['UID'];
        
        R::store($obj);

        if (count($_FILES) > 0) {
            if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                $file = upload($_FILES, 'image' . $obj->id . "-" . time(), 'uploads', 'image');
                $obj->image = "uploads/$file";
            }
            if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
                $file = upload($_FILES, 'logo' . $obj->id . "-" . time(), '../uploads', 'logo');
                $obj->logo = "uploads/$file";
            }
            R::store($obj);
        }
        print "<script>location.href = '?page=customer_details&id={$obj->customer_id}'; </script>";
    } catch (\Throwable $th) {
        dump($th);
    }
}
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-4">
            <a href="javascript:history.back()" class="text-white">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-lg font-semibold">New Collection</h1>
            <div class="w-6"></div>
        </div>
    </div>
</div>

<!-- Collection Form -->
<div class="max-w-sm mx-auto px-4 -mt-6 mb-4">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <form method="post" enctype="multipart/form-data">
            
            <!-- Customer Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                <select name="customer_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Select Customer</option>
                    <?php
                    $customers = R::find('customer');
                    foreach ($customers as $customer) {
                        $selected = ($obj->customer_id == $customer->id || (isset($get->customer) && $get->customer == $customer->id)) ? 'selected' : '';
                        echo "<option value='$customer->id' $selected>$customer->company</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Payment Date -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                <input type="date" name="date" value="<?php echo $obj->date ? $obj->date : date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <!-- Amount -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                <input type="number" name="amount" step='any' id="amount" value="<?php echo $obj->amount; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter amount">
            </div>

            <!-- Quick Amount Buttons -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Amount</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="100">100</button>
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="200">200</button>
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="300">300</button>
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="500">500</button>
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="1000">1000</button>
                    <button type="button" class="btn-amount bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-md text-sm transition-colors" data-amount="1500">1500</button>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="payment_method" value="Cash" class="payment_method mr-2" <?php echo ($obj->payment_method == 'Cash') ? 'checked' : ''; ?>>
                        <span class="text-sm">Cash</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="payment_method" value="Bank" class="payment_method mr-2" <?php echo ($obj->payment_method == 'Bank') ? 'checked' : ''; ?>>
                        <span class="text-sm">Bank</span>
                    </label>
                </div>
            </div>

            <!-- Particulars Options -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Particulars</label>
                <div class="space-y-2">
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Apure water may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Apure water may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Neat & Clean may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Neat & Clean may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Neat & Clean RHB bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Neat & Clean RHB bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Ddcon may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Ddcon may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Bdcon may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Bdcon may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="BdpZone May Bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">BdpZone May Bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Ekawin may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Ekawin may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Khandaker Tajul may bank a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Khandaker Tajul may bank a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Sohel May Bank account a banking korse Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Sohel May Bank account a banking korse Rm:</span>
                    </label>
                    <label class="flex items-start">
                        <input type="radio" name="particulars" value="Cash Collection kora hoyese Rm:" class="notes mr-2 mt-1">
                        <span class="text-sm">Cash Collection kora hoyese Rm:</span>
                    </label>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter description"><?php echo $obj->description; ?></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <button type="submit" name="save" class="bg-primary hover:bg-primaryDark text-white py-3 px-4 rounded-lg font-medium transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Collection
                </button>
                <button type="reset" class="bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                    <i class="fas fa-undo mr-2"></i>Reset
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Quick amount buttons
    $(".btn-amount").click(function(){
        const amount = $(this).data('amount');
        $("#amount").val(amount);
        setParticulars();
    });

    // Notes/particulars selection
    $(".notes").click(setParticulars);

    function setParticulars(){
        const notes = $('input[type="radio"].notes:checked').val() || '';
        const amount = $("#amount").val() || '';
        
        if(notes.includes('bank')){
            $('input:radio[name=payment_method][value=Bank]').prop('checked', true);
        } else if(notes.includes('Cash')){
            $('input:radio[name=payment_method][value=Cash]').prop('checked', true);
        }
        
        $("#description").val(notes + amount);
    }

    // Auto-update description when amount changes
    $("#amount").on('input', function() {
        setParticulars();
    });
});
</script>

</body>
</html>
