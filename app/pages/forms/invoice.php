<?php
$obj = R::dispense("invoice");
$customer_id = isset($post->customer_id) ? (int) $post->customer_id : 0;
$products = (isset($post->product) && is_array($post->product)) ? $post->product : [];

if (METHOD == 'edit' && defined('ID')) {
  $obj = R::dispense("invoice");
  $obj->status = "New";
  if (METHOD == 'edit' && defined('ID')) {
    $obj = R::load('invoice', ID);
  } else {
    $obj->customer_id = $post->customer_id;
  }
  if (isset($post->save)) {
    $obj->salesman = $post->salesman;
    $salesman = R::findOne('staff_salary', 'name=?', [$post->salesman]);
    if($salesman){
      $obj->incentive = $salesman->incentive;
    }
    // $obj->salesman_id = $post->salesman_id;
    $obj->invoice_date = isset($post->date) ? $post->date : today();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    R::store($obj);
    
    // Get existing invoice items for this invoice
    $existingItems = R::find("invoice_item", "invoice_id=?", [ID]);
    $existingItemsMap = [];
    foreach ($existingItems as $item) {
      $existingItemsMap[$item->product_variance_id] = $item;
    }
    
    // Track which items are being updated
    $updatedVarianceIds = [];
    
    foreach ($post->product as $id => $qty) {
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $updatedVarianceIds[] = $id;
      
      // Get posted price if available, otherwise use variance price
      $postedPrice = isset($post->price[$id]) ? (float) $post->price[$id] : $variance->price;
      
      // Check if item already exists
      if (isset($existingItemsMap[$id])) {
        // Update existing item
        $ii = $existingItemsMap[$id];
        $ii->quantity = $qty;
        $ii->price = $postedPrice;
        $ii->cost = $variance->cost;
        $ii->name = $product->name;
        $ii->description = $variance->particulars;
        $ii->updated_by = uid();
        $ii->updated_at = now();
      } else {
        // Create new item
        $ii = R::dispense("invoice_item");
        $ii->invoice_id = $obj->id;
        $ii->product_id = $product->id;
        $ii->product_variance_id = $variance->id;
        $ii->quantity = $qty;
        $ii->price = $postedPrice;
        $ii->cost = $variance->cost;
        $ii->name = $product->name;
        $ii->description = $variance->particulars;
        $ii->created_by = nn($obj->created_by) ? $obj->created_by : uid();
      }
      
      R::store($ii);
    }
    
    // Delete items that are no longer in the product list
    foreach ($existingItems as $item) {
      if (!in_array($item->product_variance_id, $updatedVarianceIds)) {
        R::trash($item);
      }
    }

    redir(ROOT . "/customer/details/$post->customer_id");
  }
} else {
  $obj->status = "New";
  $cp = [];
  if ($customer_id > 0) {
    $obj->customer_id = $customer_id;
	$cp = toA("customer_product_variance", "product_variance_id", "price", "customer_id = '$customer_id'");
  }
  //vd($cp);
  if (isset($post->save)) {
    // dd($post->price[31]);
    foreach ($post->product as $id => $qty) {
      $obj = R::dispense("invoice");
      if(isset($post->customer_order_id)){
        $delete_id = (int)$post->customer_order_id;
        $orderBean = R::load('customer_order', $delete_id);
        if ($orderBean && $orderBean->id) {
          if (strtolower((string)$orderBean->status) !== 'approved') {
            $itemsBeans = R::find('customer_order_item', ' customer_order_id = ? ', [$delete_id]);
            foreach ($itemsBeans as $item) {
              R::trash($item);
            }
            R::trash($orderBean);
          }
        }
      }
      
      $obj->status = "New";
      if (METHOD == 'edit' && defined('ID')) {
        $obj = R::load('invoice', ID);
      } else {
        $obj->customer_id = $post->customer_id;
      }
      $obj->salesman = $post->salesman;
      $salesman = R::findOne('staff_salary', 'name=?', [$post->salesman]);
      //$obj->incentive = $salesman->incentive;
      // $obj->salesman_id = $post->salesman_id;
      $obj->invoice_date = isset($post->date) ? $post->date : today();
      // $obj->due_date = $post->due_date;
      // $obj->delivery_date = $post->delivery_date;
      // $obj->note = $post->note;
      $obj->created_by = uid();

      R::store($obj);
      if (METHOD == 'edit' && defined('ID')) {
        del("invoice_item", "invoice_id=" . ID);
      }
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("invoice_item");
	  
	  if(isset($cp[$variance->id])){
		  $variance->price = $cp[$variance->id];
	  }

      $ii->invoice_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $post->price[$id];
      $ii->delivery_date = $obj->invoice_date;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->branch_id = $branch_id;
      $ii->description = $variance->particulars;
      $ii->created_by = nn($obj->created_by) ? $obj->created_by : uid();

      R::store($ii);
    }
    redir(ROOT . "/customer/details/$post->customer_id");
  }
}
?>
<!-- [ Main Content ] start -->
<form method="post">
  <?php
    if(isset($post->customer_order_id)){
      print "<input type='hidden' name='customer_order_id' value='$post->customer_order_id'>";
    }
  ?>
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="mb-3 mb-0">
              <table class='table'>
                <tr>
                  <td width="200px">
                    <label class="form-label">Select Customer</label>
                    <select type="text" class="form-select" name="customer_id" required>
                      <option value=''>Please select</option>
                      <?php
                      $customers = R::find('customer');
                      foreach ($customers as $key => $customer) {
                        print "<option value='$customer->id' ";
                        if ($obj->customer_id == $customer->id)
                          print "selected";
                        print ">$customer->company</option>";
                      }
                      ?>
                    </select>
                  </td>
                  <td width="200px" class='hidden'>

                    <label class="form-label">Select Marketing</label>
                    <select class='form-select supplier-select' name='salesman'>
                      <option value=''>Please select</option>";
                      <?php
                      $salesman = select('distinct name', 'staff_salary', "category='Marketing'");
                      while ($man = mysqli_fetch_object($salesman)) {
                        print "<option";
                        print ">$man->name</option>";
                      }
                      ?>
                    </select>
                  </td>
                  <td width="150px">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?php print date("Y-m-d", time()); ?>">
                  </td>
                  <td></td>
                </tr>
              </table>
            </div>


            <!-- <div class="col-sm-6 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Invoice id</label>
                  <input type="text" class="form-control" disabled placeholder="#xxxx" >
                </div>
              </div>
              <div class="col-sm-6 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Status</label>
                  <input class="form-control" id="exampleFormControlSelect1" disabled value="<?php print $obj->status; ?>">
                </div>
              </div>
              <div class="col-sm-6 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Invoice Date</label>
                  <input type="datetime-local" name="invoice_date" class="form-control" >
                </div>
              </div>
              <div class="col-sm-6 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Due Date</label>
                  <input type="datetime-local" name="due_date" class="form-control" >
                </div>
              </div>
              <div class="col-sm-6 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Requested Delivery Date</label>
                  <input type="datetime-local" name="delivery_date" class="form-control" >
                </div> -->
          </div>
          <!-- <div class="col-xl-6">
                    <div class="border rounded p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0">From:</h6>
                        <button
                          class="btn btn-sm btn-light-secondary d-flex align-items-center gap-2"
                          data-bs-toggle="modal"
                          data-bs-target="#address-edit_add-modal"
                          ><i class="ph-duotone ph-pencil-simple-line"></i> Change</button
                        >
                      </div>
                      <h5>Garcia-Cameron and Sons</h5>
                      <p class="mb-0">8534 Saunders Hill Apt. 583</p>
                      <p class="mb-0">(970) 982-3353</p>
                      <p class="mb-0">brandon07@pierce.com</p>
                    </div>
                  </div>
                  <div class="col-xl-6">
                    <div class="border rounded p-3 h-100">
                      <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">To:</h6>
                        <button
                          class="btn btn-sm btn-light-secondary d-flex align-items-center gap-2"
                          data-bs-toggle="modal"
                          data-bs-target="#address-edit_add-modal"
                          ><i class="ph-duotone ph-plus-circle"></i> Add</button
                        >
                      </div>
                    </div>
                  </div> -->
          <div class="col-12">
            <h5>Detail</h5>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th><span class="text-danger">*</span>Name</th>
                    <th><span class="text-danger">*</span>Description</th>
                    <th><span class="text-danger">*</span>Qty</th>
                    <th><span class="text-danger">*</span>Price</th>
                    <th>Total Amount</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $i = 1;
                  $total = 0;

                  if (METHOD == 'edit' && defined('ID')) {
                    $items = R::find("invoice_item", "invoice_id=?", [ID]);
                    foreach ($items as $item) {
                      $id = $item->product_variance_id;
                      $qty = $item->quantity;
                      $variance = R::load("product_variance", $item->product_variance_id);
                      $product = R::load("product", $item->product_id);
					  
					  if(isset($cp[$variance->id])){
						  $variance->price = $cp[$variance->id];
					  }
						
                      // Load price from invoice_item (database) instead of variance
                      $price = $item->price;

                      print "<tr>";
                      print "<td>$i</td>";
                      print "<td id='image-$variance->id' class='text-center'><img src='" . ROOT . "/{$variance->image}' height='80px'></a></td>";
                      print "<td><input type='text' class='form-control' readonly value='$product->name' ></td>";
                      print "<td><input type='text' class='form-control' readonly value='$variance->particulars' ></td>";
                      print "<td><input type='number' class='form-control w100 qty' name='product[$id]' placeholder='Qty' value='$qty' ></td>";
                      print "<td><input type='number' class='form-control price' name='price[$id]' step='.01' placeholder='Price' value='$price' ></td>";
                      print "<td class='total'>RM " . nf($price * $qty) . "</td>";
                      print "<td class='text-center'><i class='ti ti-trash f-20'></i></td>";
                      print "</tr>";
                      $total += $price * $qty;
                      $i++;
                    }
                  } else {
                    $products = array_reverse($products, true);

                    foreach ($products as $id => $qty) {
                      $variance = R::load("product_variance", $id);
                      $product = R::load("product", $variance->product_id);
					  if(isset($cp[$variance->id])){
						  $variance->price = $cp[$variance->id];
					  }
                      // $customer_product_variance = R::findOne("customer_product_variance", "customer_id=? AND product_variance_id=?", [$obj->customer_id, $variance->id]);
                      // if ($customer_product_variance) {
                      //   $price = $customer_product_variance->price;
                      // } else {
                      // $price = $variance->price;
                      // }
                      $price = $variance->price;
                      print "<tr>";
                      print "<td>$i</td>";
                      print "<td id='image-$variance->id' class='text-center'><img src='" . ROOT . "/{$variance->image}' height='80px'></a></td>";
                      print "<td><input type='text' class='form-control' readonly value='$product->name' ></td>";
                      print "<td><input type='text' class='form-control' readonly value='$variance->particulars' ></td>";
                      print "<td><input type='number' class='form-control w100 qty' name='product[$id]' placeholder='Qty' value='$qty' ></td>";
                      print "<td><input type='number' class='form-control price' name='price[$id]' placeholder='Price' step='.01' value='$price' ></td>";
                      print "<td class='total'>RM " . nf($price * $qty) . "</td>";
                      print "<td class='text-center'><i class='ti ti-trash f-20'></i></td>";
                      print "</tr>";
                      $total += $price * $qty;
                      $i++;
                    }
                  }

                  ?>
                </tbody>
              </table>
            </div>
            <!-- <div class="text-start">
                      <hr class="mb-4 mt-0 border-secondary border-opacity-50">
                      <button class="btn btn-light-primary d-flex align-items-center gap-2"><i class="ti ti-plus"></i> Add new item</button>
                    </div> -->
          </div>
          <div class="col-12">
            <div class="invoice-total ms-auto">
              <div class="row">
                <!-- <div class="col-6">
                          <div class="mb-3">
                            <label class="form-label">Discount(%)</label>
                            <input type="text" class="form-control" value="0" >
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="mb-3">
                            <label class="form-label">Taxes(%)</label>
                            <input type="text" class="form-control" value="0" >
                          </div>
                        </div>
                        <div class="col-6"> <p class="text-muted mb-1 text-start">Sub Total :</p></div>
                        <div class="col-6"> <p class="f-w-600 mb-1 text-end">$20.00</p></div>
                        <div class="col-6"> <p class="text-muted mb-1 text-start">Discount :</p></div>
                        <div class="col-6"> <p class="f-w-600 mb-1 text-end text-success">$10.00</p></div>
                        <div class="col-6"> <p class="text-muted mb-1 text-start">Taxes :</p></div>
                        <div class="col-6"> <p class="f-w-600 mb-1 text-end">$5.000</p></div> -->
                <div class="col-6">
                  <p class="f-w-600 mb-1 text-start">Grand Total : <span class='grand-total'>RM
                      <?php print nf($total); ?></span></p>
                </div>
                <!-- <div class="col-6"> <p class="f-w-600 mb-1 text-end grand-total"></p></div> -->
              </div>
            </div>
          </div>
          <!-- <div class="col-12">
                    <div class="mb-3 mb-0">
                      <label class="form-label">Note</label>
                      <textarea class="form-control" name="note" rows="3" placeholder="Note"></textarea>
                    </div>
                  </div> -->
          <div class="col-12">
            <div class="row align-items-end justify-content-between g-3">
              <div class="col-sm-auto">
                <!-- <label class="form-label">Set Currency*</label>
                        <select class="form-select w-auto">
                          <option>USD (US Dollar)</option>
                          <option>INR (Rupes)</option>
                        </select> -->
              </div>
              <div class="col-sm-auto btn-page">
                <!-- <a href="../application/invoice-view.html" class="btn btn-outline-secondary">Preview</a> -->
                <button class="btn btn-outline-primary" name="save">Save</button>
                <!-- <button class="btn btn-primary">Create & Send</button> -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</form>
<!-- [ Main Content ] end -->
</div>
</div>
<div class="modal fade" id="address-edit_add-modal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header justify-content-between">
        <div class="collapse multi-collapse show">
          <h5 class="mb-0">Select address</h5>
        </div>
        <div class="collapse multi-collapse">
          <h5 class="mb-0">Add New address</h5>
        </div>
        <div class="d-flex align-items-center justify-content-end">
          <div class="collapse multi-collapse show" data-bs-toggle="tooltip" title="Add New">
            <a href="#" class="avtar avtar-s btn-link-secondary" data-bs-toggle="collapse"
              data-bs-target=".multi-collapse">
              <i class="ti ti-plus f-20"></i>
            </a>
          </div>
          <a href="#" class="avtar avtar-s btn-link-danger" data-bs-dismiss="modal" data-bs-toggle="tooltip"
            title="Close">
            <i class="ti ti-x f-20"></i>
          </a>
        </div>
      </div>
      <div class="modal-body">
        <div class="collapse multi-collapse show">
          <div class="address-check-block">
            <div class="address-check border rounded p-3">
              <div class="form-check">
                <input type="radio" name="radio1" class="form-check-input input-primary" id="address-check-1"
                  checked="">
                <label class="form-check-label d-block" for="address-check-1">
                  <span class="h6 mb-0 d-block">Ian Carpenter <small class="text-muted">(Home)</small></span>
                  <span class="text-muted address-details">1754 Ureate Path, 695 Newga View, Seporcus, Rhode Island,
                    Belgium - SA5 5BO</span>
                  <span class="row align-items-center justify-content-between">
                    <span class="col-6">
                      <span class="text-muted mb-0">+91 1234567890</span>
                    </span>
                    <span class="col-6 text-end">
                      <span class="address-btns d-flex align-items-center justify-content-end">
                        <a href="#" class="avtar avtar-s btn-link-danger btn-pc-default">
                          <i class="ti ti-trash f-20"></i>
                        </a>
                        <span class="btn btn-sm btn-outline-primary">Deliver to this address</span>
                      </span>
                    </span>
                  </span>
                </label>
              </div>
            </div>
            <div class="address-check border rounded p-3">
              <div class="form-check">
                <input type="radio" name="radio1" class="form-check-input input-primary" id="address-check-2">
                <label class="form-check-label d-block" for="address-check-2">
                  <span class="h6 mb-0 d-block">Ian Carpenter <small class="text-muted">(Home)</small></span>
                  <span class="text-muted address-details">1754 Ureate Path, 695 Newga View, Seporcus, Rhode Island,
                    Belgium - SA5 5BO</span>
                  <span class="row align-items-center justify-content-between">
                    <span class="col-6">
                      <span class="text-muted mb-0">+91 1234567890</span>
                    </span>
                    <span class="col-6 text-end">
                      <span class="address-btns d-flex align-items-center justify-content-end">
                        <a href="#" class="avtar avtar-s btn-link-danger btn-pc-default">
                          <i class="ti ti-trash f-20"></i>
                        </a>
                        <span class="btn btn-sm btn-outline-primary">Deliver to this address</span>
                      </span>
                    </span>
                  </span>
                </label>
              </div>
            </div>
            <div class="address-check border rounded p-3">
              <div class="form-check">
                <input type="radio" name="radio1" class="form-check-input input-primary" id="address-check-3">
                <label class="form-check-label d-block" for="address-check-3">
                  <span class="h6 mb-0 d-block">Ian Carpenter <small class="text-muted">(Home)</small></span>
                  <span class="text-muted address-details">1754 Ureate Path, 695 Newga View, Seporcus, Rhode Island,
                    Belgium - SA5 5BO</span>
                  <span class="row align-items-center justify-content-between">
                    <span class="col-6">
                      <span class="text-muted mb-0">+91 1234567890</span>
                    </span>
                    <span class="col-6 text-end">
                      <span class="address-btns d-flex align-items-center justify-content-end">
                        <a href="#" class="avtar avtar-s btn-link-danger btn-pc-default">
                          <i class="ti ti-trash f-20"></i>
                        </a>
                        <span class="btn btn-sm btn-outline-primary">Deliver to this address</span>
                      </span>
                    </span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="collapse multi-collapse">
          <div class="row">
            <div class="col-12">
              <div class="mb-3 row align-items-center">
                <label class="col-lg-4 col-form-label">Address Type :<small class="text-muted d-block">Enter Add
                    Type</small></label>
                <div class="col-lg-8">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="addtypecheck1" checked>
                    <label class="form-check-label" for="addtypecheck1"> Home (All day Delivery) </label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="addtypecheck2">
                    <label class="form-check-label" for="addtypecheck2"> Work (Between 10 AM to 5 PM) </label>
                  </div>
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">First Name :<small class="text-muted d-block">Enter your first
                    name</small></label>
                <div class="col-lg-8">
                  <input type="text" class="form-control">
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">Last Name :<small class="text-muted d-block">Enter your last
                    name</small></label>
                <div class="col-lg-8">
                  <input type="text" class="form-control">
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">Email Id :<small class="text-muted d-block">Enter Email
                    id</small></label>
                <div class="col-lg-8">
                  <input type="email" class="form-control">
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">Date of Birth :<small class="text-muted d-block">Enter the date
                    of birth</small></label>
                <div class="col-lg-8">
                  <input type="date" class="form-control">
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">Phone number :<small class="text-muted d-block">Enter Phone
                    number</small></label>
                <div class="col-lg-8">
                  <input type="text" class="form-control">
                </div>
              </div>
              <div class="mb-3 row">
                <label class="col-lg-4 col-form-label">City :<small class="text-muted d-block">Enter City
                    name</small></label>
                <div class="col-lg-8">
                  <input type="text" class="form-control">
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="" id="checkaddres" checked>
                  <label class="form-check-label" for="checkaddres"> Save this new address for future shipping </label>
                </div>
              </div>
              <div class="text-end btn-page mb-0 mt-4">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                  data-bs-target=".multi-collapse">Cancel</button>
                <button class="btn btn-primary" data-bs-dismiss="modal">Save & Deliver to this Address</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between collapse multi-collapse show">
        <ul class="list-inline me-auto mb-0">
          <li class="list-inline-item align-bottom">
            <a href="#" class="avtar avtar-s btn-link-danger w-sm-auto" data-bs-toggle="tooltip" title="Delete">
              <i class="ti ti-trash f-18"></i>
            </a>
          </li>
        </ul>
        <div class="flex-grow-1 text-end">
          <button type="button" class="btn btn-link-danger" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>


<script type="text/javascript">
  $(".ti-trash").click(function () {
    if (confirm("Are you sure?")) {
      let total = parseFloat($(this).parent().parent().find(".total").text().replace("RM ", ""));
      let grandTotal = parseFloat($(".grand-total").text().replace("RM ", ""));
      $(".grand-total").text('RM ' + (grandTotal - total).toFixed(2));
      $(this).parent().parent().remove();
    }
  });
  $(".qty,.price").keyup(function () {
    const qty = parseFloat($(this).parent().parent().find('.qty').val());
    const price = parseFloat($(this).parent().parent().find('.price').val());
    $(this).parent().parent().find('.total').text('RM ' + (qty * price).toFixed(2));
    let sum = 0;
    $('.total').each(function () {
      // Parse the text as a number and add to sum
      const text = $(this).text().replace("RM ", "");
      const value = parseFloat(text);
      if (!isNaN(value)) { // Ensure it's a valid number
        sum += value;
        $(".grand-total").text('RM ' + sum.toFixed(2));
      }
    });
  });
  $(".price").change(function () {
    const qty = parseFloat($(this).parent().parent().find('.qty').val());
    const price = parseFloat($(this).parent().parent().find('.price').val());
    $(this).parent().parent().find('.total').text('RM ' + (qty * price).toFixed(2));
    let sum = 0;
    $('.total').each(function () {
      // Parse the text as a number and add to sum
      const text = $(this).text().replace("RM ", "");
      const value = parseFloat(text);
      if (!isNaN(value)) { // Ensure it's a valid number
        sum += value;
        $(".grand-total").text('RM ' + sum.toFixed(2));
      }
    });
  });
</script>
