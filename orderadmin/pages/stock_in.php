<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Stock In</title>
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
// Business logic from stockin.php
if(isset($post->var_id_del)){
  $variance = R::load('product_variance', $post->var_id_del);
  R::trash($variance);
}

if (isset($post->save)) {
  try {
    if(isset($post->var_id)){
      $variance = R::load('product_variance', $post->var_id);
    } else{
      $variance = R::dispense('product_variance');
    }
    $variance->product_id = $post->product_id;
    $variance->index = 1;
    $variance->particulars = $post->particulars;
    $variance->cost = $post->cost;
    $variance->price = $post->price;
    $variance->size = $post->size;
    $variance->unit = $post->unit;
    R::store($variance);

    if (count($_FILES) > 0) {
      if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
        $file = upload($_FILES, 'image' . $variance->id . "-" . time(), 'uploads', 'image');
        $variance->image = "uploads/$file";
      }
      if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
        $file = upload($_FILES, 'logo' . $variance->id . "-" . time(), '../uploads', 'logo');
        $variance->logo = "uploads/$file";
      }
      R::store($variance);
    }
  } catch (\Throwable $th) {
    dump($th);
  }
}

$objs = R::find('product');
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Stock In Report</h2>
        <div class="text-center">
            <button onclick="openModal('productModal')" class="bg-white text-primary px-4 py-2 rounded font-semibold">
                <span class="material-symbols-outlined text-sm mr-1">add</span>
                Add Product
            </button>
        </div>
    </div>
</div>

<!-- Stock Table -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left">No.</th>
                        <th class="px-2 py-2 text-left">Date</th>
                        <th class="px-2 py-2 text-left">Product</th>
                        <th class="px-2 py-2 text-left">Particulars</th>
                        <th class="px-2 py-2 text-center">Image</th>
                        <th class="px-2 py-2 text-left">Size</th>
                        <th class="px-2 py-2 text-left">Unit</th>
                        <th class="px-2 py-2 text-right">Qty In</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    if(isset($get->id) && $get->id){
                        $items = select("SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity, order_date FROM `product` p, `product_variance` v, `order_item` oi, `order` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.order_id AND v.id=".$get->id." ORDER BY order_date");
                        while($var = mysqli_fetch_object($items)){
                            echo "<tr class='border-b'>";
                            echo "<td class='px-2 py-1'>$i</td>";
                            echo "<td class='px-2 py-1'>".df($var->order_date)."</td>";
                            echo "<td class='px-2 py-1'>$var->pname</td>";
                            echo "<td class='px-2 py-1'>$var->particulars</td>";
                            echo "<td class='px-2 py-1 text-center'><img src='".ROOT."/{$var->vimage}' height='40px' class='rounded'></td>";
                            echo "<td class='px-2 py-1'>$var->size</td>";
                            echo "<td class='px-2 py-1'>$var->unit</td>";
                            echo "<td class='px-2 py-1 text-right'>$var->quantity</td>";
                            echo "</tr>";
                            $i++;
                        }
                    } else{
                        $items = select("SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, SUM(oi.quantity) quantity, order_date FROM `product` p, `product_variance` v, `order_item` oi, `order` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.order_id GROUP BY v.id ORDER BY order_date");
                        while($var = mysqli_fetch_object($items)){
                            echo "<tr class='border-b'>";
                            echo "<td class='px-2 py-1'>$i</td>";
                            echo "<td class='px-2 py-1'>".df($var->order_date)."</td>";
                            echo "<td class='px-2 py-1'>$var->pname</td>";
                            echo "<td class='px-2 py-1'><a href='?id=$var->id' class='text-blue-600'>$var->particulars</a></td>";
                            echo "<td class='px-2 py-1 text-center'><img src='".ROOT."/{$var->vimage}' height='40px' class='rounded'></td>";
                            echo "<td class='px-2 py-1'>$var->size</td>";
                            echo "<td class='px-2 py-1'>$var->unit</td>";
                            echo "<td class='px-2 py-1 text-right'>$var->quantity</td>";
                            echo "</tr>";
                            $i++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal custom-fallback" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" onclick="closeModal('productModal')" aria-label="Close">×</button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="var_id" id="var_id">
                <div class="modal-body">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Particulars</label>
                            <textarea name="particulars" id="particulars" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Size (ml/L)</label>
                                <input type="text" name="size" id="size" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Unit</label>
                                <input type="number" name="unit" id="unit" step="any" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cost</label>
                                <input type="number" name="cost" id="cost" step="any" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Price</label>
                                <input type="number" name="price" id="price" step="any" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Image</label>
                            <input type="file" name="image" id="image" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">Close</button>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="post" id="del-form" style="display: none;">
    <input type="hidden" name="var_id_del" id="var_id_del">
</form>

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

function setProduct(i) {
    document.getElementById("product_id").value = i;
}

function setProductEdit(pid, vid) {
    document.getElementById("product_id").value = pid;
    document.getElementById("var_id").value = vid;
    
    document.getElementById("particulars").value = document.getElementById("particulars-" + vid).textContent;
    document.getElementById("size").value = document.getElementById("size-" + vid).textContent;
    document.getElementById("unit").value = document.getElementById("unit-" + vid).textContent;
    document.getElementById("cost").value = document.getElementById("cost-" + vid).textContent;
    document.getElementById("price").value = document.getElementById("price-" + vid).textContent;
    document.getElementById("image").removeAttribute('required');
}

function delProduct(vid){
    if(confirm("Are you sure?")){
        document.getElementById("var_id_del").value = vid;
        document.getElementById("del-form").submit();
    }
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
