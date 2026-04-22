<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Expenses</title>
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
// Business logic from expense_account.php
$object = R::dispense('expense_account');

if(isset($post->save)){
    $fields = ['name','code','fullcode','parent','description','opening_balance', 'company', 'hotel'];
    
    foreach ($fields as $field) {
        if(isset($post->$field) && nn($post->$field)) {
            $object->$field = trim($post->$field);
        }
    }
    
    if($post->parent){
        $parent = R::load("expense_account", $post->parent);	
        $parent->has_child = 1;
        $parentCode = getName("expense_account", $parent->parent, 'fullcode');
        $parent->fullcode = "{$parentCode}{$parent->code}";
        R::store($parent);
        $object->breadcrumbs = $parent->breadcrumbs.' > '.$object->name;
        $object->company = $parent->company;
        $object->hotel = $parent->hotel;
    } else{
        $object->breadcrumbs = $object->name;
    }
    
    if(!isset($get->id) || !$get->id) {
        $object->entry_by = uid();
        $object->entry_time = now();
        if($post->parent){
            $object->code = getNextCount("expense_account", "code", "parent=$post->parent");
        } else{
            $object->code = getNextCount("expense_account", "code", "parent IS NULL");
        }
    }
    if(isset($get->id) && $get->id && $post->parent) {
        $object->modify_by = uid();
        $object->modify_time = now();
        if(!nn($object->code)){
            if($post->parent){
                $object->code = getNextCount("expense_account", "code", "parent=$post->parent");
            } else{
                $object->code = getNextCount("expense_account", "code", "parent IS NULL");
            }
        }
        $object->path = $parent->path.$object->id."/";
    }
    
    R::store($object); 
    
    if(isset($post->parent) && nn($post->parent)){
        $object->path = $parent->path.$object->id."/";
    } else{
        $object->path = "/$object->id/";
    }
    
    if(!isset($get->id) || !$get->id){
        $object->sortorder = $object->id;
    }
    
    $object->depth = substr_count($object->path, "/") - 1;
    
    if(!$object->has_child){
        $parentCode = getName("expense_account", $post->parent, 'fullcode');
        $object->fullcode = $parentCode . zerofill($object->code, 6 - strlen($parentCode));
    }
    R::store($object);
    
    redir('/store/expense_account/');
}

// Get expense accounts for display
$expense_accounts = R::find('expense_account', 'ORDER BY breadcrumbs');
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Expense Accounts</h2>
        <div class="text-center">
            <button onclick="openModal('addExpenseModal')" class="bg-white text-primary px-4 py-2 rounded font-semibold">
                <span class="material-symbols-outlined text-sm mr-1">add</span>
                Add Account
            </button>
        </div>
    </div>
</div>

<!-- Expense Accounts List -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">Account Name</th>
                        <th class="px-2 py-2 text-left">Code</th>
                        <th class="px-2 py-2 text-left">Full Code</th>
                        <th class="px-2 py-2 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($expense_accounts as $account) {
                        echo "<tr class='border-b'>";
                        echo "<td class='px-2 py-1'>$account->name</td>";
                        echo "<td class='px-2 py-1'>$account->code</td>";
                        echo "<td class='px-2 py-1'>$account->fullcode</td>";
                        echo "<td class='px-2 py-1 text-right'>".nf($account->opening_balance)."</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expense Account Modal -->
<div class="modal custom-fallback" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Expense Account</h5>
                <button type="button" class="btn-close" onclick="closeModal('addExpenseModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Parent Account</label>
                            <select name="parent" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">--SELECT--</option>
                                <?php
                                // Get accounts with children for parent selection
                                $parent_accounts = R::find('expense_account', 'has_child = 0 ORDER BY breadcrumbs');
                                foreach ($parent_accounts as $parent) {
                                    echo "<option value='$parent->id'>$parent->breadcrumbs</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Account Name</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Opening Balance</label>
                            <input type="number" name="opening_balance" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addExpenseModal')">Close</button>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
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

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});
</script>

</body>
</html>
