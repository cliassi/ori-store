<?php
$post->UID = $_SESSION['UID'];
// customer_order creation/edition page (mobile) - adapted from _resources/pages/forms/customer_order.php
// Expects POST from home.php as product[<variance_id>] => qty and optional customer_id

// $get, $post are prepared by index.php

// If products are posted, immediately create an order for the current session user and show invoice view
if (!empty($post->product) && is_array($post->product)) {
  $customerId = isset($_SESSION['UID']) ? $_SESSION['UID'] : (isset($post->customer_id) ? $post->customer_id : null);
  $inv = R::dispense('customer_order');
  $inv->status = 'New';
  $inv->customer_id = $customerId;
  $inv->invoice_date = isset($post->date) ? $post->date : today();
  $inv->created_by = 2;
  R::store($inv);

  $items = [];
  $grand = 0;
  foreach ($post->product as $id => $qty) {
    $qty = (int)$qty;
    if ($qty <= 0) continue;
    $variance = R::load('product_variance', $id);
    if (!$variance || !$variance->id) continue;
    $product = R::load('product', $variance->product_id);
    $price = isset($post->price[$id]) ? (float)$post->price[$id] : (float)$variance->price;

    $ii = R::dispense('customer_order_item');
    $ii->customer_order_id = $inv->id;
    $ii->product_id = $product->id;
    $ii->product_variance_id = $variance->id;
    $ii->quantity = $qty;
    $ii->price = $price;
    $ii->cost = $variance->cost;
    $ii->name = $product->name;
    $ii->description = $variance->particulars;
    $ii->delivery_date = $inv->customer_order_date;
    R::store($ii);

    $line = (object) [
      'image' => getImageOrPlaceholder($variance->image, $variance->name),
      'name' => $product->name,
      'desc' => $variance->particulars,
      'qty' => $qty,
      'price' => $price,
      'total' => $price * $qty,
    ];
    $items[] = $line;
    $grand += $line->total;
  }
  ?>
  <style>
    .po-container{max-width:720px;margin:0 auto;padding:10px 10px 40px;}
    .po-row{display:grid;grid-template-columns:1fr;gap:8px;}
    .po-card{display:flex;align-items:center;gap:10px;background:#fff;border-radius:12px;padding:8px 10px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
    .po-thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;background:#f3f4f6;flex:0 0 auto;}
    .po-thumb img{width:100%;height:100%;object-fit:cover;}
    .po-name{font-weight:600;color:#0f172a;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}
    .po-unit{color:#6b7280;font-size:11px;}
    .po-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;min-width:110px;margin-left:auto;}
    .po-total{font-weight:700;color:#0f172a;font-size:13px;}
    .po-qty-pill{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;border-radius:9999px;padding:3px 8px;font-size:12px;color:#111827;}
    .po-title{font-size:16px;font-weight:700;margin:0 0 6px;color:#0f172a;}
    .po-meta{color:#6b7280;font-size:12px;margin-bottom:10px;}
    .po-grand{margin-top:10px;text-align:right;font-size:14px;color:#0f172a;}
    .po-actions{margin-top:12px;display:flex;gap:8px;justify-content:center;}
    .po-btn{padding:10px 16px;border-radius:9999px;border:1px solid #e5e7eb;background:#fff;color:#0f172a;text-decoration:none;}
    .po-btn-primary{background:#22c55e;color:#fff;border:none;}
    .po-btn-return{
      background:#fbbf24;color:#111827;border:none;margin-top: 15px;
      border-radius:30px;padding:5px 56px;font-weight:500;
      text-decoration:none;display:inline-flex;align-items:center;gap:10px;
      font-size: 12px;
    }
    .po-btn-return:active{opacity:.8;}
    .po-title svg{
      display: inline-block;
      margin-top: -8px;
    }
  </style>

  <div class="po-container">
    <div class="po-title" style="
    color: #008048;
    font-size: 1rem;
    text-align: center;
    margin: 0 24px;padding: 15px 0;font-weight:500;
    display: block;
<div style="display:flex;align-items:center;gap:10px;">
  
  <span>Order has been Confirmed <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:1.6em;height:1.6em;">
    <path fill="rgb(34, 181, 94)" d="M320 576C178.6 576 64 461.4 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576zM438 209.7C427.3 201.9 412.3 204.3 404.5 215L285.1 379.2L233 327.1C223.6 317.7 208.4 317.7 199.1 327.1C189.8 336.5 189.7 351.7 199.1 361L271.1 433C276.1 438 282.9 440.5 289.9 440C296.9 439.5 303.3 435.9 307.4 430.2L443.3 243.2C451.1 232.5 448.7 217.5 438 209.7z"/>
  </svg></span>
</div>
    <!-- <div class="po-meta">Order #<?php echo htmlspecialchars($inv->id); ?> • <?php echo htmlspecialchars($inv->customer_order_date); ?></div> -->

    <div class="po-row">
      <?php foreach ($items as $it): ?>
        <div class="po-card">
          <div class="po-thumb"><img src="<?php echo $it->image; ?>" alt="<?php echo htmlspecialchars($it->name); ?>"></div>
          <div style="flex:1 1 auto; min-width:0;">
            <div class="po-name"><?php echo htmlspecialchars($it->name); ?></div>
            <div class="po-unit">Unit: RM <?php echo number_format($it->price, 2); ?></div>
          </div>
          <div class="po-right">
            <div class="po-total">RM <?php echo number_format($it->total, 2); ?></div>
            <div class="po-qty-pill">Qty: <span><?php echo (int)$it->qty; ?></span></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="po-grand"><strong>Grand Total: RM <?php echo number_format($grand, 2); ?></strong></div>
    <div class="po-actions">
      <a class="po-btn-return" href="?page=home&uid=<?php print $_SESSION['UID']; ?>"><span style="display:inline-flex;align-items:center;line-height:0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width:13px;height:13px;fill:currentColor"><path d="M255.545 8c-66.269.119-126.438 26.233-170.86 68.685L48.971 40.971C33.851 25.851 8 36.559 8 57.941V192c0 13.255 10.745 24 24 24h134.059c21.382 0 32.09-25.851 16.971-40.971l-41.75-41.75c30.864-28.899 70.801-44.907 113.23-45.273 92.398-.798 170.283 73.977 169.484 169.442C423.236 348.009 349.816 424 256 424c-41.127 0-79.997-14.678-110.63-41.556-4.743-4.161-11.906-3.908-16.368.553L89.34 422.659c-4.872 4.872-4.631 12.815.482 17.433C133.798 479.813 192.074 504 256 504c136.966 0 247.999-111.033 248-247.998C504.001 119.193 392.354 7.755 255.545 8z"/></svg></span><span>Return to Home</span></a>
    </div>
  </div>
  <?php
  return; // stop legacy form from rendering
}

// Prepare base customer_order object and state
$obj = R::dispense('customer_order');
$obj->status = 'New';

// If editing (not used in mobile flow yet, but kept for compatibility)
if (defined('METHOD') && METHOD == 'edit' && defined('ID')) {
  $obj = R::load('customer_order', ID);
} else {
  // Preselect customer if provided
  if (isset($post->customer_id)) {
    $obj->customer_id = $post->customer_id;
  }
}

// Handle save
if (isset($post->save)) {
  // Create multiple customer_orders: one customer_order per product selected
  if (!empty($post->product) && is_array($post->product)) {
    foreach ($post->product as $id => $qty) {
      $qty = (int)$qty;
      if ($qty <= 0) continue;

      // Build a fresh customer_order for each product
      $inv = R::dispense('customer_order');
      $inv->status = 'New';
      $inv->customer_id = isset($post->customer_id) ? $post->customer_id : null;
      if (isset($post->salesman) && function_exists('nn') ? nn($post->salesman) : !empty($post->salesman)) {
        $inv->salesman = $post->salesman;
        $salesman = R::findOne('staff_salary', 'name=?', [$post->salesman]);
        if ($salesman && isset($salesman->incentive)) {
          $inv->incentive = $salesman->incentive;
        }
      }
      $inv->customer_order_date = isset($post->date) ? $post->date : today();
      $inv->created_by = 2;

      R::store($inv);

      // Create single customer_order_item for this customer_order
      $variance = R::load('product_variance', $id);
      if (!$variance || !$variance->id) continue;
      $product = R::load('product', $variance->product_id);

      $ii = R::dispense('customer_order_item');
      $ii->customer_order_id = $inv->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = isset($post->price[$id]) ? $post->price[$id] : $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;
      $ii->delivery_date = $inv->customer_order_date;
      R::store($ii);
    }
  }

  // Clear all cart_qty localStorage variables after successful save
  echo "<script>
    // Remove all cart_qty_* keys from localStorage
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && key.startsWith('cart_qty_')) {
        localStorage.removeItem(key);
        i--; // Adjust index since we removed an item
      }
    }
  </script>";
  
  // Redirect to customer details like the reference implementation
  if (isset($post->customer_id)) {
    redir("?page=customer_details&id={$post->customer_id}");
  } else {
    redir("?page=home");
  }
}
?>
<style>
  
  /* Compact layout for mobile */
  .inv-compact * {
    font-size: 12px;
  }

  .inv-compact .card-body {
    padding: 8px;
  }

  .inv-compact .form-label {
    margin-bottom: 2px;
    font-size: 11px;
  }

  .inv-compact .form-control,
  .inv-compact .form-select {
    height: 30px;
    padding: 2px 6px;
    font-size: 12px;
  }

  .inv-compact table.table {
    margin-bottom: 6px;
  }

  .inv-compact table.table thead th {
    padding: 6px 6px;
    white-space: nowrap;
    font-size: 11px;
  }

  .inv-compact table.table tbody td {
    padding: 6px 6px;
    vertical-align: middle;
  }

  .inv-compact table.table tbody tr td:first-child {
    border-right: 1px solid #e5e7eb;
  }

  .inv-compact table.table tbody tr td:last-child {
    border-left: 1px solid #e5e7eb;
  }

  .inv-compact input.price {
    max-width: 90px;
  }

  .inv-compact td.total {
    white-space: nowrap;
  }

  .inv-compact .w100 {
    max-width: 70px;
  }

  .inv-compact img.item-img {
    height: 48px;
    width: auto;
  }

  .inv-compact .grand-wrap {
    margin-top: 6px;
    text-align: right;
    font-size: 16px;
  }

  .inv-compact .btn {
    padding: 6px 10px;
    font-size: 12px;
  }

  .inv-compact .toolbar {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    align-items: end;
  }

  .inv-compact .row-2col {
    display: grid;
    grid-template-columns: 1fr 140px;
    gap: 8px;
    align-items: end;
  }

  /* Alternating group background */
  .inv-compact .group-even td {
    background: #f8fafc;
  }

  .inv-compact .group-odd td {
    background: #ffffff;
  }

  .inv-compact td.label {
    white-space: nowrap;
    color: #6b7280;
  }
</style>

<form method="post" class="inv-compact">
  <input type='hidden' name='UID' value='<?php print $post->UID; ?>' >
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="card-body">
          <div class="toolbar">
            <div>
              <label class="form-label">Customer</label>
              <select type="text" class="form-select" name="customer_id" required>
                <option value=''>Please select</option>
                <?php
                $customers = R::find('customer');
                foreach ($customers as $key => $customer) {
                  print "<option value='{$customer->id}' ";
                  if ($obj->customer_id == $customer->id) print 'selected';
                  print ">{$customer->company}</option>";
                }
                ?>
              </select>
            </div>
            <div class="row-2col">
              <div>
                <label class="form-label">Marketing</label>
                <select class='form-select supplier-select' name='salesman'>
                  <option value=''>Please select</option>
                  <?php
                  $salesman = select('distinct name', 'staff_salary', "category='Marketing'");
                  while ($man = mysqli_fetch_object($salesman)) {
                    print "<option>{$man->name}</option>";
                  }
                  ?>
                </select>
              </div>
              <div>
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="date" value="<?php print date('Y-m-d', time()); ?>">
              </div>
            </div>
          </div>

          <div class="col-12 mt-2">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Item</th>
                    <th class="text-center">Act</th>
                    <th class="text-center"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $i = 1;
                  $total = 0;
                  if (!empty($post->product) && is_array($post->product)) {
                    // reverse order like reference for UX
                    $post->product = array_reverse($post->product, true);
                    foreach ($post->product as $id => $qty) {
                      $variance = R::load('product_variance', $id);
                      $product = R::load('product', $variance->product_id);
                      $price = $variance->price;
                      $groupClass = ($i % 2 == 0) ? 'group-even' : 'group-odd';
                      // Row 1: index, image, name/desc, action
                      print "<tr class='{$groupClass}'>";
                      print "<td class='align-middle'>{$i}</td>";
                      print "<td id='image-{$variance->id}' class='text-center align-middle'><img class='item-img' src='" . getImageOrPlaceholder($variance->image, $variance->name) . "'></td>";
                      // Combine product name and particulars compactly
                      $itemLabel = htmlspecialchars($product->name, ENT_QUOTES);
                      $itemDesc = htmlspecialchars($variance->particulars, ENT_QUOTES);
                      print "<td class='align-middle'><div><strong>{$itemLabel}</strong></div><div>{$itemDesc}</div></td>";
                      print "<td class='text-center align-middle'><i class='ti ti-trash f-20'></i></td>";
                      print "<td class='text-center align-middle inv-remove-cell' rowspan='2'><button type='button' class='inv-remove-btn px-2 py-1 font-bold text-gray-600 hover:text-red-600' data-id='{$id}'>X</button></td>";
                      print "</tr>";
                      // Row 2: qty, price, total (no wrapper div; align to 4 columns)
                      print "<tr class='{$groupClass}'>";
                      print "<td></td>"; // empty under index
                      print "<td><input type='number' class='form-control w100 qty' name='product[{$id}]' placeholder='Qty' value='{$qty}' ></td>"; // qty under Product col
                      print "<td><input type='number' class='form-control price' name='price[{$id}]' step='.01' placeholder='Price' value='{$price}' ></td>"; // price under Item col
                      print "<td class='total'>RM " . nf($price * $qty) . "</td>"; // total under Act col
                      print "</tr>";
                      $total += $price * $qty;
                      $i++;
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-12 grand-wrap" style="font-size:1.2rem">
            <p class="mb-1" style="font-size:1.2rem">Grand Total: <strong class='grand-total' style="font-size:1.2rem">RM <?php print nf($total); ?></strong></p>
          </div>
          <div class="col-12" style="margin-top:-32px">
            <button class="btn btn-primary px-4 ml-4" name="save">Save</button>

          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</form>

<div class="col-12 mb-3">
  <button type="button" class="btn btn-light" onclick="window.history.back();" style="font-size:1rem">
    ← Back
  </button>
</div>


<div id="removeRowModal" class="hidden fixed inset-0 z-50 items-center justify-center">
  <div class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-white w-11/12 max-w-sm rounded-lg shadow-lg p-4">
    <div class="text-lg font-semibold">Remove item?</div>
    <div class="mt-2 text-sm text-gray-600">This will remove the item from the customer_order.</div>
    <div class="mt-4 flex justify-end gap-2">
      <button type="button" id="removeRowCancel" class="px-4 py-2 rounded border border-gray-300 bg-white">Cancel</button>
      <button type="button" id="removeRowConfirm" class="px-4 py-2 rounded bg-red-600 text-white">Remove</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  // Remove row and update total
  const removeModal = document.getElementById('removeRowModal');
  const removeCancel = document.getElementById('removeRowCancel');
  const removeConfirm = document.getElementById('removeRowConfirm');
  let pendingRemove = null;

  function recalcGrandTotal() {
    let sum = 0;
    document.querySelectorAll('.total').forEach(t => {
      const v = parseFloat(t.textContent.replace('RM ', ''));
      if (!isNaN(v)) sum += v;
    });
    const grandEl = document.querySelector('.grand-total');
    if (grandEl) grandEl.textContent = 'RM ' + sum.toFixed(2);
  }

  function openRemoveModal(payload) {
    pendingRemove = payload;
    if (!removeModal) return;
    removeModal.classList.remove('hidden');
    removeModal.classList.add('flex');
  }

  function closeRemoveModal() {
    pendingRemove = null;
    if (!removeModal) return;
    removeModal.classList.add('hidden');
    removeModal.classList.remove('flex');
  }

  document.querySelectorAll('.inv-remove-btn').forEach(el => {
    el.addEventListener('click', function() {
      const row1 = this.closest('tr');
      const row2 = row1 ? row1.nextElementSibling : null;
      openRemoveModal({ row1, row2 });
    });
  });

  document.querySelectorAll('.qty').forEach(inp => {
    inp.addEventListener('blur', function() {
      const qty = parseFloat(this.value) || 0;
      if (qty !== 0) return;
      const row2 = this.closest('tr');
      const row1 = row2 ? row2.previousElementSibling : null;
      if (!row1 || !row2) return;
      openRemoveModal({ row1, row2 });
    });
  });

  removeCancel && removeCancel.addEventListener('click', () => closeRemoveModal());
  removeModal && removeModal.addEventListener('click', (e) => {
    if (e.target === removeModal) closeRemoveModal();
  });

  removeConfirm && removeConfirm.addEventListener('click', () => {
    if (!pendingRemove) {
      closeRemoveModal();
      return;
    }
    if (pendingRemove.row2) pendingRemove.row2.remove();
    if (pendingRemove.row1) pendingRemove.row1.remove();
    recalcGrandTotal();
    closeRemoveModal();
  });

  // Live total updates
  document.querySelectorAll('.qty,.price').forEach(inp => {
    inp.addEventListener('keyup', function() {
      recalcRow(this);
    });
    inp.addEventListener('change', function() {
      recalcRow(this);
    });
  });

  function recalcRow(node) {
    const row = node.closest('tr');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    row.querySelector('.total').textContent = 'RM ' + (qty * price).toFixed(2);
    let sum = 0;
    document.querySelectorAll('.total').forEach(t => {
      const v = parseFloat(t.textContent.replace('RM ', ''));
      if (!isNaN(v)) sum += v;
    });
    document.querySelector('.grand-total').textContent = 'RM ' + sum.toFixed(2);
  }
</script>