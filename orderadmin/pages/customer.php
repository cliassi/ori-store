<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Customers</title>
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
// Business logic from customer.php
if(isset($get->token)){
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if($token){
        R::trash($token);
        if(isset($get->del) && isset($get->id)){
            $st = R::load('customer', $get->id);
            R::trash($st);
            redir("?");
        }
    }
}

$fields = [
    "id" => '',
    "code" => ["label"=>"Code", "display"=>''],
    "password" => ["label"=>"PIN", "display"=>''],
    "company" => ["label"=>"Shop Name", "display"=>'', 'link'=>'details'],
    "contact" => ["label"=>"Contact Person", "display"=>''],
    "mobile" => ["label"=>"C.P. Mobile", "display"=>''],
    "city" => ["label"=>"Area", "display"=>''],
    "image" => ["label"=>"Photo", "display"=>'', 'type'=>'image']
];


if(!isUserIn(['parvez'])){
    // unset($fields['password']);
} else{
    $fields[''] = ["display"=>'', 'type'=>'link', 'action'=>'edit'];
}

$objs = R::find('customer', 'branch_id=1');
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <div class="relative mb-4">
            <input type="text" id="search" placeholder="Search Customer" class="w-full px-3 py-2 rounded text-gray-800 text-sm">
        </div>
        <div class="text-center">
            
        </div>
    </div>
</div>

<!-- Customer Table -->
 <br>
 <br>
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="customer-table" class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">Code</th>
                        <th class="px-2 py-2 text-left">PIN</th>
                        <th class="px-2 py-2 text-left">Shop Name</th>
                        <th class="px-2 py-2 text-left">Contact</th>
                        <th class="px-2 py-2 text-left">Mobile</th>
                        <th class="px-2 py-2 text-left">Area</th>
                        <th class="px-2 py-2 text-center">Photo</th>
                        <th class="px-2 py-2 text-center"><a href="?page=customer_add" class="text-blue-600 font-medium">Add New</a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    foreach ($objs as $key => $obj) {
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'>$obj->code</td>";
                        echo "<td class='px-2 py-1'>$obj->password</td>";
                        echo "<td class='px-2 py-1'><a href='?page=customer_details&id=$obj->id' class='text-blue-600 font-medium'>$obj->company</a></td>";
                        echo "<td class='px-2 py-1'>
                            <a href='https://wa.me/{$obj->contact}' target='_blank' style='margin-right: 5px; display: inline-block'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 640' style='width:18px; height:18px;'><path fill='rgb(34, 181, 94)' d='M476.9 161.1C435 119.1 379.2 96 319.9 96C197.5 96 97.9 195.6 97.9 318C97.9 357.1 108.1 395.3 127.5 429L96 544L213.7 513.1C246.1 530.8 282.6 540.1 319.8 540.1L319.9 540.1C442.2 540.1 544 440.5 544 318.1C544 258.8 518.8 203.1 476.9 161.1zM319.9 502.7C286.7 502.7 254.2 493.8 225.9 477L219.2 473L149.4 491.3L168 423.2L163.6 416.2C145.1 386.8 135.4 352.9 135.4 318C135.4 216.3 218.2 133.5 320 133.5C369.3 133.5 415.6 152.7 450.4 187.6C485.2 222.5 506.6 268.8 506.5 318.1C506.5 419.9 421.6 502.7 319.9 502.7zM421.1 364.5C415.6 361.7 388.3 348.3 383.2 346.5C378.1 344.6 374.4 343.7 370.7 349.3C367 354.9 356.4 367.3 353.1 371.1C349.9 374.8 346.6 375.3 341.1 372.5C308.5 356.2 287.1 343.4 265.6 306.5C259.9 296.7 271.3 297.4 281.9 276.2C283.7 272.5 282.8 269.3 281.4 266.5C280 263.7 268.9 236.4 264.3 225.3C259.8 214.5 255.2 216 251.8 215.8C248.6 215.6 244.9 215.6 241.2 215.6C237.5 215.6 231.5 217 226.4 222.5C221.3 228.1 207 241.5 207 268.8C207 296.1 226.9 322.5 229.6 326.2C232.4 329.9 268.7 385.9 324.4 410C359.6 425.2 373.4 426.5 391 423.9C401.7 422.3 423.8 410.5 428.4 397.5C433 384.5 433 373.4 431.6 371.1C430.3 368.6 426.6 367.2 421.1 364.5z'/></svg></a>
                        
                            $obj->contact</td>";
                        echo "<td class='px-2 py-1'>$obj->mobile</td>";
                        echo "<td class='px-2 py-1'>$obj->city</td>";
                        echo "<td class='px-2 py-1 text-center'>";
                        if($obj->image) {
                            echo "<img src='{$obj->image}' height='40px' class='rounded'>";
                        }
                        echo "</td>";
                        echo "<td class='px-2 py-1 text-center'>";
                        if($_SESSION['UID'] == 1){
                            echo "<a href='edit/$obj->id' class='text-blue-600 mr-2'>Edit</a>";
                            echo "<a href='?del&id=$obj->id' class='text-red-600'>Delete</a>";
                        }
                        echo "</td>";
                        echo "</tr>";
                        $i++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 flex justify-center">
        <button onclick="openModal('addCustomerModal')" aria-label="Add Customer" class="bg-primary hover:bg-primaryDark text-white rounded-full p-3 shadow-lg focus:outline-none focus:ring-2 focus:ring-primaryDark">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal custom-fallback" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" onclick="closeModal('addCustomerModal')" aria-label="Close">×</button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Shop Name</label>
                            <input type="text" name="company" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                            <input type="text" name="contact" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
                            <input type="text" name="mobile" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Area</label>
                            <select name="city" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Area</option>
                                <?php
                                $cities = R::find('city');
                                foreach ($cities as $city) {
                                    echo "<option value='$city->name'>$city->name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email/Username</label>
                            <input type="text" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="text" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Photo</label>
                            <input type="file" name="image" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addCustomerModal')">Close</button>
                    <button type="submit" name="save" class="btn btn-primary">Save Customer</button>
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

// Search functionality
document.getElementById('search').addEventListener('keyup', function() {
    var searchKey = this.value.toLowerCase();
    
    document.querySelectorAll('#customer-table tbody tr').forEach(function(row) {
        var rowText = row.textContent.toLowerCase();
        
        if (rowText.includes(searchKey)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
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
