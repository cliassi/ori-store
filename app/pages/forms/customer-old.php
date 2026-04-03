<?php
$obj = R::dispense('customer');
if (defined('ID')) {
  $obj = R::load('customer', ID);
}
if (isset($post->save)) {

  /* ================= PRICE UPDATE ================= */
  if (uid() == 47 || isUserIn(['Parvez'])) {

    if (isset($_POST['addprice']) && isset($_POST['product_variance'])) {

      $variance = R::load('product_variance', (int) $_POST['product_variance']);

      if ($variance->id) {
        $variance->price = (float) $_POST['custom_price'];
        $variance->updated_by = uid();
        $variance->updated_at = date('Y-m-d H:i:s');
        R::store($variance);
      }

      // reload page to prevent resubmission
      redir(ROOT . "/customer/details/" . ID);
      exit;
    }
  }

  try {
    if ($obj->code == '') {
      $last_id = getMax('customer', 'code');
      $next = 0;
      if (substr($last_id, 1) < 99) {
        $next = $last_id + 1;
      } else {
        $next = (substr($last_id, 0, 1) + 1) * 1000;
      }
      $obj->code = $next;
    }
    $obj->company = $post->company;
    $obj->contact = $post->contact;
    $obj->mobile = $post->mobile;
    $obj->location = $post->location;
    $obj->city = $post->city;
    $obj->email = $post->email;
    $obj->branch_id = $branch_id;
    $obj->password = $post->password;
    if (uid() == 1) {
      $obj->branch_id = isset($post->branch_id) ? $post->branch_id : $branch_id;
    } else {
      $obj->branch_id = $branch_id;
    }
    if (!nn($post->password)) {
      $obj->password = $obj->code . date("y", time());
    }
    // $obj->address = $post->address;
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
    redir(ROOT . "/customer/details/$obj->id");
  } catch (\Throwable $th) {
    dump($th);
  }

}

$html = "<table class='table table-bordered'>";
if (uid() == 47 || isUserIn(['Parvez', 'lemon'])) {
  $html .= "<tr>
        <td>" . sop2('product_variance', $obj->id, [
      'dataField' => 'particulars',
      'extraFields' => 'price',
      'width' => '200'
    ]) . "</td>
        <td>
            <input type='hidden' name='product_variance' value='{$obj->id}'>
            <input type='number' step='any' name='custom_price' class='form-control' placeholder='New Price'>
        </td>
        <td>
            <button name='addprice' value='1' type='submit' class='btn btn-warning'>
                Update Price
            </button>
        </td>
    </tr>";
}
$html .= "</table>";

$html .= "<tr><td colspan='3'><table id='table' class='table table-bordered'>";
$variances = R::find('customer_product_variance', 'customer_id = ?', [$obj->id]);
foreach ($variances as $variance) {
  $html .= "<tr><td>" . getName('product_variance', $variance->product_variance_id, 'particulars') . "</td><td>" . $variance->price . "</td>";
  if (isUserIn([])) {
    $html .= "<td><a href='?delprice=$variance->id' type='button' ><i class='fa fa-trash'></i></a></td>";
  }
  $html .= "</tr>";
}
$html .= "</table></td></tr>
</table>";
?>

<!-- [ Main Content ] start -->
<div class="row">
  <!-- [ form-element ] start -->
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5>New Customer</h5>
      </div>
      <div class="card-body">
        <form class="forms-sample" method="post" enctype="multipart/form-data">
          <div class="row g-4">
            <?php
            $formItems = [
              'company' => ['col' => 6, 'label' => 'Shop Name', 'type' => 'text', 'value' => $obj->company],
              'contact' => ['col' => 6, 'label' => 'Contact Person', 'type' => 'text', 'value' => $obj->contact],
              'mobile' => ['col' => 6, 'label' => 'C.P. Mobile', 'type' => 'text', 'value' => $obj->mobile],
              'city' => ['col' => 6, 'label' => 'Area', 'type' => 'dropdown', 'value' => $obj->city, 'table' => 'city', 'valueField' => 'name', 'filter' => 'branch_id = ' . $branch_id],
              'html' => ['col' => 6, 'type' => 'html', 'html' => $html],
              'image' => ['col' => 6, 'label' => 'Photo', 'type' => 'image', 'value' => $obj->image],
              'location' => ['col' => 4, 'label' => 'Location', 'type' => 'text', 'value' => $obj->location],
              'email' => ['col' => 4, 'label' => 'Username', 'type' => 'text', 'value' => $obj->email],
              'password' => ['col' => 4, 'label' => 'Password', 'type' => 'text', 'value' => $obj->password],
              // 'email' => ['col' => 6, 'label' => 'Email', 'type' => 'email', 'value' => $obj->email],
              // 'password' => ['col' => 6, 'label' => 'Password', 'type' => 'password', 'value' => $obj->password],
            ];

            if (uid() == 1) {
              //$formItems['branch_id'] = ['col' => 6, 'label' => 'Branch', 'type' => 'dropdown', 'value'=> $obj->branch_id ? $obj->branch_id : $branch_id, 'table'=>'branch', 'valueField'=>'id'];
            }

            print buildForm($formItems);

            ?>
          </div>

          <div class="d-grid gap-2 mt-2">
            <button class="btn btn-primary" name='save' type="submit">Save Customer</button>
          </div>
        </form>
      </div>
    </div>
  </div>