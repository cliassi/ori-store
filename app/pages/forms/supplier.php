<?php
$obj = R::dispense('supplier');
if (defined('ID')) {
    $obj = R::load('supplier', ID);
}
if (isset($post->save)) {
    try {
      $obj->company = $post->company;
      $obj->contact = $post->contact;
      $obj->mobile = $post->mobile;
      // $obj->city = $post->city;
      // $obj->address = $post->address;
      R::store($obj);

      // if (count($_FILES) > 0) {
      //     if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
      //         $file = upload($_FILES, 'image' . $obj->id . "-" . time(), 'uploads', 'image');
      //         $obj->image = "uploads/$file";
      //     }
      //     if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
      //         $file = upload($_FILES, 'logo' . $obj->id . "-" . time(), '../uploads', 'logo');
      //         $obj->logo = "uploads/$file";
      //     }
      //     R::store($obj);
      // }
      redir(ROOT."/supplier/details/$obj->id");
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
                <h5>New Supplier</h5>
              </div>
              <div class="card-body">
                <form class="forms-sample" method="post" enctype="multipart/form-data">
                  <div class="row g-4">
                    <?php
                      $formItems = [
                        'company' => ['col' => 6, 'label' => 'Supplier Name', 'type' => 'text', 'value' => $obj->company],
                        'contact' => ['col' => 6, 'label' => 'Contact Person', 'type' => 'text', 'value'=>$obj->contact],
                        'mobile' => ['col' => 6, 'label' => 'C.P. Mobile', 'type' => 'text', 'value'=>$obj->mobile],
                        // 'city' => ['col' => 6, 'label' => 'City', 'type' => 'dropdown', 'value'=>$obj->city, 'table'=>'city', 'valueField'=>'name'],
                        // 'address' => ['col' => 6, 'label' => 'Address', 'type' => 'textarea', 'value' => $obj->address],
                        // 'image' => ['col' => 6, 'label' => 'Photo', 'type' => 'image', 'value'=>$obj->image],
                      ];

                      print buildForm($formItems);

                    ?>
                  </div>

                  <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-primary" name='save' type="submit">Save Supplier</button>
                  </div>
                </form>
              </div>
            </div>
          </div>