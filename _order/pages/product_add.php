<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Pure Water - Add Product</title>
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
// Business logic from product.php
$obj = R::dispense('product');
if (isset($get->id) && $get->id) {
    $obj = R::load('product', $get->id);
}
if (isset($post->save)) {
    try {
        $obj->name = $post->name;
        $obj->image_orientation = $post->image_orientation;
        $obj->product_category_id = $post->product_category_id;
        $obj->sort_order = $post->sort_order;
        R::store($obj);

        if (count($_FILES) > 0) {
            if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                $file = upload($_FILES, 'image' . $obj->id . "-" . time(), 'uploads', 'image');
                $obj->image = "uploads/$file";
            }
            if (isset($_FILES['image2']['name']) && !empty($_FILES['image2']['name'])) {
                $file = upload($_FILES, 'image2' . $obj->id . "-" . time(), 'uploads', 'image2');
                $obj->image2 = "uploads/$file";
            }
            if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
                $file = upload($_FILES, 'logo' . $obj->id . "-" . time(), '../uploads', 'logo');
                $obj->logo = "uploads/$file";
            }
            R::store($obj);
        }
        if(isset($post->suppliers)){
          foreach ($post->suppliers as $key => $sid) {
            insert("product_supplier", "product_id, supplier_id", "$obj->id,$sid");
          }
        }
        print "<script>location.href = '".ROOT."/product'; </script>";
    } catch (\Throwable $th) {
        dump($th);
    }
}
?>

<!-- Mobile Header -->
<div class="bg-primary blob-shape text-white">
    <div class="max-w-sm mx-auto px-4 py-6">
        <h2 class="text-xl font-bold text-center mb-4">Add New Product</h2>
    </div>
</div>

<!-- Form -->
<div class="max-w-sm mx-auto px-4 -mt-6">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <form method="post" enctype="multipart/form-data">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                    <input type="text" name="name" value="<?php echo $obj->name; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image Type</label>
                        <select name="image_orientation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="L" <?php echo ($obj->image_orientation == 'L') ? 'selected' : ''; ?>>Landscape</option>
                            <option value="P" <?php echo ($obj->image_orientation == 'P') ? 'selected' : ''; ?>>Portrait</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                        <select name="sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php for($i = 0; $i < 99; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($obj->sort_order == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="product_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Category</option>
                        <?php
                        $categories = R::find('product_category');
                        foreach ($categories as $category) {
                            $selected = ($obj->product_category_id == $category->id) ? 'selected' : '';
                            echo "<option value='$category->id' $selected>$category->name</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                    <input type="file" name="image" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Suppliers</label>
                    <div class="space-y-2 max-h-32 overflow-y-auto border border-gray-200 rounded-md p-3">
                        <?php
                        $suppliers = R::find('supplier');
                        foreach ($suppliers as $supplier) {
                            echo "<label class='flex items-center'>";
                            echo "<input type='checkbox' name='suppliers[]' value='$supplier->id' class='mr-2'>";
                            echo "<span class='text-sm'>$supplier->company</span>";
                            echo "</label>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" name="save" class="w-full bg-primary text-white py-3 rounded-md font-semibold hover:bg-primaryDark transition-colors">
                    <span class="material-symbols-outlined text-sm mr-2">save</span>
                    Save Product
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
