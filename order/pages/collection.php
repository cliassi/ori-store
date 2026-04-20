<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Collection</title>
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
        .modal.custom-fallback .modal-dialog{margin: 0; width: 92%; max-width: 600px;}
        .modal.custom-fallback .modal-content{ border-radius: 8px; overflow: hidden; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.35);}
        .modal.custom-fallback .modal-header, .modal.custom-fallback .modal-body, .modal.custom-fallback .modal-footer{background: #ffffff;}
        .modal.custom-fallback .modal-body{ padding: 16px; }
        body.modal-open{ overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// Business logic from dcollect3.php
if (isset($post->deliver)) {
    foreach ($post->iid as $key => $qty) {
        if($qty > 0){
            $ii = R::load("invoice_item", $key);
            $inv = R::load("invoice", $ii->invoice_id);
            update("invoice_item", "delivered=quantity, delivered_by=".uid().", delivered_at=NOW(),delivery_staff='$post->delivery_staff'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
            insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$key, $qty, ".uid().",'$post->delivery_staff'");
        }
    }
}

if (isset($post->collect)) {
    $obj = R::dispense("stock_collect");
    $obj->salesman_id = isset($post->salesman)?$post->salesman:0;
    if(isset($post->collect)){
        $obj->delivery_staff = $post->delivery_staff;
        $obj->date = today();
        $obj->created_by = uid();
        
        $stored = false;
        
        foreach ($post->variance as $id => $qty) {
            if($qty == 0) continue;
            if(!$stored){ R::store($obj); $stored = true; }
            $item = R::load("invoice_item", $id);
            $variance = R::load("product_variance", $item->product_variance_id);
            $product = R::load("product", $variance->product_id);
            $ii = R::dispense("stock_collect_item");
            
            $ii->stock_collect_id = $obj->id;
            $ii->product_id = $product->id;
            $ii->invoice_item_id = $item->id;
            $ii->product_variance_id = $variance->id;
            $ii->quantity = $qty;
            $ii->price = $variance->price;
            $ii->cost = $variance->cost;
            $ii->name = $product->name;
            $ii->description = "$variance->particulars $variance->size x $variance->unit";
            $ii->created_by = uid();
            
            R::store($ii);
        }
    }
}

if(isset($post->update_delivery_date)){
    $ii = R::load('invoice_item', $post->id);	
    $ii->delivery_date = $post->date;
    R::store($ii);
}
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
        <h2 class="text-xl font-bold text-center mb-4">Collection Items</h2>
</div>

<!-- Filter Options -->
<div class="max-w-4xl mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium mb-2">Areas</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    <?php
                    $areas = R::find('city');
                    foreach ($areas as $area) {
                        echo "<label class='flex items-center'>";
                        echo "<input type='checkbox' class='checkbox-area mr-2' data-id='$area->id'>";
                        echo "<span class='text-sm'>".ucfirst(strtolower($area->name))."</span>";
                        echo "</label>";
                    }
                    ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Categories</label>
                <div class="flex flex-row overflow-x-auto space-x-4 pb-2">
                    <?php
                    $cats = R::find('product_category', 'sort_order > -1 ORDER BY sort_order');
                    foreach ($cats as $cat) {
                        echo "<div class='flex-shrink-0 min-w-max whitespace-nowrap'>";
                        echo "<label class='flex items-center font-medium'>";
                        echo "<input type='checkbox' class='checkbox-category mr-2' data-id='$cat->id'>";
                        echo "<span>$cat->name</span>";
                        echo "</label>";
                        
                        $products = R::find("product", "product_category_id=?", [$cat->id]);
                        echo "<div class='ml-4 space-y-1'>";
                        foreach($products as $product){
                            echo "<label class='flex items-center text-sm '>";
                            echo "<input type='checkbox' class='checkbox-product mr-2' data-id='$product->id'>";
                            echo "<span>$product->name</span>";
                            echo "</label>";
                        }
                        echo "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collection Display -->
<div class="max-w-4xl mx-auto px-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div id="collection-container">
            <div class="text-center ">
                <span class="material-symbols-outlined text-4xl mb-2">inbox</span>
                <p>Select areas and products to view collection items</p>
            </div>
        </div>
    </div>
</div>
<br><br>

<div class="text-center p-2" style="position: fixed; bottom: 0px; width: 100%; text-align: center; background: #efefef">
    <button onclick="openModal('collectModal')" class="bg-white text-primary px-4 py-2 rounded font-semibold">
        <span class="material-symbols-outlined text-sm mr-1">add</span>
        Collect Items
    </button>
</div>

<!-- Collect Items Modal -->
<div class="modal custom-fallback" id="collectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Collect Items</h5>
                <button type="button" class="btn-close" onclick="closeModal('collectModal')" aria-label="Close">×</button>
            </div>
            <form method="post">
                <input type="hidden" name="collect" value="1">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium ">Delivery Staff</label>
                            <select name="delivery_staff" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Staff</option>
                                <?php
                                $staff = select('distinct name', 'staff_salary', "category='Delivery Staff'");
                                while ($man = mysqli_fetch_object($staff)) {
                                    echo "<option value='$man->name'>$man->name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div id="item-selection">
                            <label class="block text-sm font-medium ">Items to Collect</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                                <!-- Items will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('collectModal')">Close</button>
                    <button type="submit" class="btn btn-primary">Collect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

loadCollection();

// Load collection items based on selection
function loadCollection() {
    console.log('loadCollection');
    const selectedAreas = Array.from(document.querySelectorAll('.checkbox-area:checked')).map(cb => cb.dataset.id).join(',');
    const selectedProducts = Array.from(document.querySelectorAll('.checkbox-product:checked')).map(cb => cb.dataset.id).join(',');
    
    // if (!selectedAreas && !selectedProducts) {
    //     document.getElementById('collection-container').innerHTML = `
    //         <div class="text-center ">
    //             <span class="material-symbols-outlined text-4xl mb-2">inbox</span>
    //             <p>Select areas and products to view collection items</p>
    //         </div>
    //     `;
    //     return;
    // }
    
    // Show loading state
    document.getElementById('collection-container').innerHTML = `
        <div class="text-center ">
            <span class="material-symbols-outlined text-4xl mb-2">pending</span>
            <p>Loading collection items...</p>
        </div>
    `;
    
    // Make AJAX call to dcollect.php like dcollect3.php does
    $.post('/ajax/dcollect.php', { 
        customers: selectedAreas, 
        products: selectedProducts, 
        order: false, 
        pending: false, 
        delivery: false, 
        collection: true, 
        pendingList: false 
    })
    .done((response) => {
        document.getElementById('collection-container').innerHTML = response;
        
        // Update modal with items for collection
        updateCollectionModal();
    })
    .fail(() => {
        document.getElementById('collection-container').innerHTML = `
            <div class="text-center text-red-500">
                <span class="material-symbols-outlined text-4xl mb-2">error</span>
                <p>Failed to load collection items</p>
            </div>
        `;
    });
}

// Update the collection modal with available items
function updateCollectionModal() {
    const itemSelection = document.getElementById('item-selection');
    const items = document.querySelectorAll('input[name="variance[]"]');
    
    let modalContent = '';
    items.forEach((item, index) => {
        const row = item.closest('tr');
        const particulars = row.querySelector('td:nth-child(2)').textContent.trim();
        const maxQty = item.getAttribute('max') || 0;
        
        if (maxQty > 0) {
            modalContent += `
                <div class="flex items-center justify-between p-2 border-b">
                    <span class="text-sm">${particulars}</span>
                    <input type="number" name="variance[${item.name}]" 
                           class="w-20 px-2 py-1 border rounded text-sm" 
                           min="0" max="${maxQty}" value="0">
                </div>
            `;
        }
    });
    
    if (modalContent) {
        itemSelection.innerHTML = `
            <label class="block text-sm font-medium ">Items to Collect</label>
            <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                ${modalContent}
            </div>
        `;
    } else {
        itemSelection.innerHTML = `
            <div class="text-center  py-4">
                <span class="material-symbols-outlined text-2xl mb-2">inbox</span>
                <p class="text-sm">No items available for collection</p>
            </div>
        `;
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to checkboxes
    document.querySelectorAll('.checkbox-area, .checkbox-product').forEach(checkbox => {
        checkbox.addEventListener('change', loadCollection);
    });
    
    // Category checkbox toggles all products in that category
    document.querySelectorAll('.checkbox-category').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const categoryId = this.dataset.id;
            const productCheckboxes = document.querySelectorAll(`.checkbox-product[data-category="${categoryId}"]`);
            productCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            loadCollection();
        });
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
