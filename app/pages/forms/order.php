<?php
if (METHOD == 'collect') {
  $obj = R::dispense("stock_collect");
  $obj->status = "New";
  $obj->supplier_id = isset($get->supplier) ? $get->supplier : 0;
  if (isset($post->save)) {
    if (empty($post->lorry_id)) {
      alert('Please select a lorry');
      redir($_SERVER['REQUEST_URI']);
    }

    $obj->supplier_id = $post->supplier;
    $obj->lorry_id = $post->lorry_id;
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    R::store($obj);

    foreach ($post->variance as $id => $qty) {
      if ($qty == 0)
        continue;
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("stock_collect_item");

      $ii->stock_collect_id = $obj->id;

      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      if (isset($post->sst[$id])) {
        $ii->sst = $post->sst[$id];
      } else {
        $ii->sst = 0;
      }
      $ii->cost = $variance->cost * (1 + ($ii->sst / 100));
      $ii->branch_id = $branch_id;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;
      // dd($ii);
      R::store($ii);
    }

    redir(ROOT . "/supplier/details/$obj->supplier_id");
  }
}
if (METHOD == 'return_collect') {
  $obj = R::dispense("stock_return");
  $obj->status = "New";
  $obj->supplier_id = isset($get->supplier) ? $get->supplier : 0;
  if (isset($post->save)) {
    $obj->supplier_id = $post->supplier;
    $obj->order_date = today();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    R::store($obj);


    foreach ($post->variance as $id => $qty) {
      if ($qty == 0)
        continue;
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("stock_return_item");

      $ii->order_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;

      R::store($ii);
    }

    redir(ROOT . "/supplier/details/$obj->supplier_id");
  }
}
if (METHOD == 'return') {
  $obj = R::dispense("goods_return");
  $obj->status = "New";
  $obj->supplier_id = isset($get->supplier) ? $get->supplier : 0;
  if (isset($post->save)) {
    $obj->supplier_id = $post->supplier;
    $obj->order_date = today();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    R::store($obj);


    foreach ($post->variance as $id => $qty) {
      if ($qty == 0)
        continue;
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("goods_return_item");

      $ii->order_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;

      R::store($ii);
    }

    redir(ROOT . "/supplier/details/$obj->supplier_id");
  }
} elseif (METHOD == 'damage') {
  if (isset($post->save)) {
    foreach ($post->variance as $id => $qty) {
      if ($qty == 0)
        continue;
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("damaged_item");

      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;

      R::store($ii);
    }
    redir(ROOT . "/report/damage");
  }
} else {
  $obj = R::dispense("order");
  $obj->status = "New";
  $obj->supplier_id = isset($get->supplier) ? $get->supplier : 0;
  if (isset($post->save)) {
    $obj->supplier_id = $post->supplier;
    $obj->lorry_id = !empty($post->lorry_id) ? $post->lorry_id : null; // ✅ LORRY
    $obj->order_date = today();
    // $obj->entry_date = $post->entry_date;
    $obj->created_by = uid();
    $obj->confirm_date = $post->confirm_date;
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    R::store($obj);


    foreach ($post->variance as $id => $qty) {
      if ($qty == 0)
        continue;
      $variance = R::load("product_variance", $id);
      $product = R::load("product", $variance->product_id);
      $ii = R::dispense("order_item");

      $ii->order_id = $obj->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = $variance->price;
      if (isset($post->sst[$id])) {
        $ii->sst = $post->sst[$id];
      } else {
        $ii->sst = 0;
      }
      $ii->cost = $post->cost[$id] * (1 + ($ii->sst / 100));
      $ii->name = $product->name;
      $ii->description = $variance->particulars;

      R::store($ii);
    }

    redir(ROOT . "/supplier/details/$obj->supplier_id");
  }
}
?>
<style type="text/css">
  .variance {
    display: inline-block;
    margin-right: 5px;
    border: solid 1px #ccc;
    border-radius: 3px;
    padding: 3px;
    cursor: pointer;
  }

  tr.active {
    background: lightsteelblue;
  }

  tr:not(.active) .btn-success {
    display: none;
  }
</style>
<!-- [ Main Content ] start -->
<?php
$suppliers = R::find('supplier');
$lorries = R::find('lorry', '1 ORDER BY id DESC');
?>
<form method="post">
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <?php if (in_array(METHOD, ['collect', 'return_collect'])) { ?>
              <div class="col-sm-12 col-xl-2">
                <div class="mb-3 mb-0">
                  <label class="form-label">Select Salesman</label>
                  <select type="text" class="form-select supplier-select" name="supplier" required>
                    <option value=''>Please select</option>
                    <?php
                    $suppliers = R::find('supplier');
                    foreach ($suppliers as $key => $supplier) {
                      print "<option value='$supplier->id' ";
                      if ($obj->supplier_id == $supplier->id)
                        print "selected";
                      print ">$supplier->company</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
            <?php } else { ?>
              <div class="row">
                <div class="col-2">
                  <label class="form-label">Select Supplier</label>
                  <select type="text" class="form-select supplier-select" name="supplier" required>
                    <option value=''>Please select</option>
                    <?php
                    $suppliers = R::find('supplier');
                    foreach ($suppliers as $key => $supplier) {
                      print "<option value='$supplier->id' ";
                      if ($obj->supplier_id == $supplier->id)
                        print "selected";
                      print ">$supplier->company</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-2">
                  <label class="form-label">Confirm Date</label>
                  <input type="date" class="form-select" name="confirm_date" required>
                </div>
                <!-- <div class="col-2">
                  <label class="form-label">Entry Date</label>
                  <input type="date" class="form-select" name="entry_date" required>
                </div> -->
                <div class="col-2">
                  <label class="form-label">Select Lorry:</label>
                  <select name="lorry_id" class="form-select" style="width:320px" required>
                    <option value="">Please select</option>
                    <?php foreach ($lorries as $l): ?>
                      <option value="<?= $l->id ?>">
                        <?= $l->lorry_no ?> -
                        <?= $l->driver_name ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-8"></div>
              </div>
            <?php } ?>
          </div>

          <!-- SAVE BUTTON (TOP) -->
          <div class="mb-3 text-end">
            <button class="btn btn-outline-primary" name="save">Save</button>
          </div>

          <div class="col-12 mt-3">
            <h5>Detail</h5>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>

                    <th>Product</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Custom Qty</th>
                    <!-- <th>Carton Qty</th> -->
                    <?php if (METHOD != 'damage'): ?>
                      <th style="text-align:center">Cost</th>
                      <th style="text-align:center">Total Amount</th>
                      <th style="text-align:center">Sales TAX %</th>
                      <th style="text-align:center">Final Amount</th>
                    <?php endif; ?>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $i = 1;
                  $total = 0;
                  $products = select('*', 'product', "id IN (SELECT product_id FROM product_supplier WHERE supplier_id=$get->supplier)");
                  while ($product = mysqli_fetch_object($products)) {
                    $variances = R::find("product_variance", "product_id=?", [$product->id]);
                    print "<tr>";
                    print "<td rowspan='" . count($variances) . "'>$product->name</td>";
                    $vi = 1;
                    foreach ($variances as $variance) {
                      if (METHOD == 'add') {
                        $variance->sst = 5;
                      }
                      $active = false;
                      if (isset($get->v) && isset($get->qty) && $get->v == $variance->id)
                        $active = true;
                      if ($vi > 1)
                        print "<tr class='" . ($active ? 'active' : '') . "'>";
                      print "<td><img src='" . ROOT . "/$variance->image' class='w64'></td>";
                      print "<td>$variance->particulars</td>";
                      print "<td><input class='form-control custom-qty' style='width:100px' type='number' data-id='$variance->id' data-cost='$variance->cost_before_tax' name='variance[$variance->id]' id='qty_{$variance->id}' step='any' ";
                      if ($active) {
                        print " value='$get->qty'";
                      }
                      print "></td>";
                      // print "<td>";
                      // for($q = 0; $q <= 300; $q+=50){
                      //   print "<span class='variance' data-id='$variance->id' data-cost='$variance->cost_before_tax' data-qty='$q'>
                      //     <input type='radio' value='$q'> $q</span>";
                      // }
                      // print "</td>";
                      if (METHOD != 'damage'):
                        print "<td><input class='form-control custom-qty' name='cost[$variance->id]'  type='number' data-id='$variance->id' id='var_{$variance->id}' value='$variance->cost_before_tax' step='any'></td>";
                        print "<td><input class='form-control' readonly id='tot_{$variance->id}'";
                        if ($active) {
                          print " value='" . ($get->qty * $variance->cost_before_tax) . "'";
                        }
                        print "></td>";
                      endif;
                      print "<td><input class='form-control sst' type='number' data-id='$variance->id' name='sst[$variance->id]' id='sst_{$variance->id}' value='$variance->sst' step='any'></td>";
                      print "<td><input class='form-control total' type='number' width='50px' id='final_{$variance->id}' value='$variance->final' step='any'></td>";
                      print "<td><button class='btn btn-success' name='save'>Save</button></td>";
                      if ($vi > 1)
                        print "</tr>";
                      $vi++;
                      $active = false;
                    }
                    print "</tr>";
                    $i++;
                  }

                  ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="col-12">
            <div class="order-total ms-auto">
              <div class="row">
                <div class="col-10">
                  <p class="f-w-600 mb-1 text-start">Grand Total :</p>
                </div>
                <div class="col-2">
                  <p class="f-w-600 mb-1 grand-total">RM <?php print nf($total); ?></p>
                </div>
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
                <!-- <a href="../application/order-view.html" class="btn btn-outline-secondary">Preview</a> -->
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
  $(".supplier-select").change(function () {
    const id = $(this).val();
    location.href = "?supplier=" + id + "<?php if (isset($get->v) && isset($get->qty))
      print "&v=$get->v&qty=$get->qty"; ?>";
  });
  $(".ti-trash").click(function () {
    if (confirm("Are you sure?")) {
      let total = parseFloat($(this).parent().parent().find(".total").text().replace("RM ", ""));
      let grandTotal = parseFloat($(".grand-total").text().replace("RM ", ""));
      $(".grand-total").text('RM ' + (grandTotal - total).toFixed(2));
      $(this).parent().parent().remove();
    }
  });

  $(".variance").click(function () {
    $(this).parent().find('input').prop('checked', false);
    $(this).find('input').prop('checked', true);
    const id = $(this).data('id');
    const cost = $(this).data('cost');
    const qty = $(this).data('qty');
    if (qty > 0) {
      $(this).parent().parent().addClass('active');
    } else {
      $(this).parent().parent().removeClass('active');
    }
    $("#qty_" + id).val(qty);
    const sst = parseFloat($("#sst_" + id).val());
    if (!isNaN(sst)) {
      const final = parseFloat(cost) * parseFloat(qty) * (sst / 100);
      $("#final_" + id).val(final.toFixed(2));
    }
  });
  $(".custom-qty,.sst").keyup(function () {
    const id = $(this).data('id');
    const cost = parseFloat($("#var_" + id).val()); //$(this).data('cost');
    const qty = $('#qty_' + id).val();
    if (qty > 0) {
      $(this).parent().parent().addClass('active');
    } else {
      $(this).parent().parent().removeClass('active');
    }
    $("#tot_" + id).val((parseFloat(cost) * parseFloat(qty)).toFixed(2));
    const sst = parseFloat($("#sst_" + id).val());
    if (!isNaN(sst)) {
      const final = parseFloat(cost) * parseFloat(qty) * (1 + (sst / 100));
      $("#final_" + id).val(final.toFixed(2));
    }


    let totalQty = 0;

    $('.total').each(function () {
      totalQty += parseFloat($(this).val()) || 0;
    });
    $(".grand-total").text('RM ' + (totalQty).toFixed(2));
  });
</script>