<?php
$obj = R::dispense('product');
if (defined('ID')) {
    $obj = R::load('product', ID);
}
if (isset($post->save)) {
    try {
      // if($obj->code == ''){
      //   $obj->code = 'AP'.rand(1000,9999);
      // }
        $obj->name = $post->name;
        $obj->image_orientation = $post->image_orientation;
        $obj->product_category_id = $post->product_category_id;
        $obj->sort_order = $post->sort_order;
        // $obj->contact = $post->contact;
        // $obj->mobile = $post->mobile;
        // $obj->city = $post->city;
        // $obj->address = $post->address;
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

        <!-- [ Main Content ] start -->
        <div class="row">
          <!-- [ form-element ] start -->
          <div class="col-md-12">
            <div class="card">
              <div class="card-header">
                <h5>New Product</h5>
              </div>
              <div class="card-body">
                <form class="forms-sample" method="post" enctype="multipart/form-data">
                  <div class="row g-4">
                    <?php
                      $sorts = [];
                      for($i = 0; $i < 99; $i++){
                        array_push($sorts, $i);
                      }
                      $formItems = [
                        'name' => ['col' => 6, 'label' => 'Product Name', 'type' => 'text', 'value' => $obj->name],
                        // 'quantity' => ['col' => 6, 'label' => 'Quantity', 'type' => 'text', 'value'=>$obj->name],
                        // 'description' => ['col' => 12, 'label' => 'Description', 'type' => 'textarea', 'value' => $obj->description],
                        'image_orientation' => ['col' => 2, 'label' => 'Image Type', 'type' => 'dropdown', 'options'=>['L', 'P'], 'value'=>$obj->image_orientation],
                        'product_category_id' => ['col' => 4, 'label' => 'Category', 'type' => 'dropdown', 'table'=>'product_category', 'value'=>$obj->product_category_id],
                        'sort_order' => ['col' => 2, 'label' => 'Sort Order', 'type' => 'dropdown', 'options'=>$sorts, 'value'=>$obj->sort_order],
                        'image' => ['col' => 4, 'label' => 'Photo', 'type' => 'image', 'value'=>$obj->image],
                        'image2' => ['col' => 4, 'label' => 'Photo Single', 'type' => 'image', 'value'=>$obj->image2],
                        // 'image3' => ['col' => 4, 'label' => 'Image 3', 'type' => 'image', 'value'=>$obj->image],
                      ];

                      print buildForm($formItems);
                      $suppliers = R::find('supplier');
                      print "<div class='col-12'>";
                      foreach ($suppliers as $key => $supplier) {
                        print "<div class='inline-block'><input type='checkbox' name='suppliers[]' value='$supplier->id'> $supplier->company</div>".space(10);
                      }
                      print "</div>";
                    ?>
                  </div>

                  <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-primary" name='save' type="submit">Save Product</button>
                  </div>
                </form>
              </div>
            </div>
          </div>