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
    unset($fields['password']);
} else{
    $fields[''] = ["display"=>'', 'type'=>'link', 'action'=>'edit'];
}

$objs = R::find('customer');
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
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="customer-table" class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">Code</th>
                        <th class="px-2 py-2 text-left">Shop Name</th>
                        <th class="px-2 py-2 text-left">Contact</th>
                        <th class="px-2 py-2 text-left">Mobile</th>
                        <th class="px-2 py-2 text-left">Area</th>
                        <th class="px-2 py-2 text-center">Photo</th>
                        <th class="px-2 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    foreach ($objs as $key => $obj) {
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'>$obj->code</td>";
                        echo "<td class='px-2 py-1'><a href='?page=customer_details&id=$obj->id' class='text-blue-600 font-medium'>$obj->company</a></td>";
                        echo "<td class='px-2 py-1'>$obj->contact</td>";
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
