<?php

// Invoice creation/edition page (mobile) - adapted from _resources/pages/forms/invoice.php
// Expects POST from home.php as product[<variance_id>] => qty and optional customer_id

// $get, $post are prepared by index.php

// Prepare base invoice object and state
$obj = R::dispense('invoice');
$obj->status = 'New';

// If editing (not used in mobile flow yet, but kept for compatibility)
if (defined('METHOD') && METHOD == 'edit' && defined('ID')) {
  $obj = R::load('invoice', ID);
} else {
  // Preselect customer if provided
  if (isset($post->customer_id)) {
    $obj->customer_id = $post->customer_id;
  }
}

// Handle save
if (isset($post->save)) {
  // Check if this is from customer order approval
  if (isset($post->customer_order_id)) {
    // Handle customer order approval - delete original order and items
    $orderId = (int)$post->customer_order_id;
    
    // Load order to check status
    $orderCheckSql = "SELECT * FROM customer_order WHERE id = $orderId LIMIT 1";
    $orderResult = mysqli_query($c, $orderCheckSql);
    $order = mysqli_fetch_object($orderResult);
    
    if ($order && $order->id && strtolower($order->status) !== 'approved') {
      $selectedItemIds = [];
      if (isset($post->customer_order_item_id)) {
        $selectedItemIds = $post->customer_order_item_id;
      }

      $selectedItemIds = array_values(array_filter(array_map('intval', (array)$selectedItemIds)));
      if (!empty($selectedItemIds)) {
        $idList = implode(',', $selectedItemIds);
        $deleteItemsSql = "DELETE FROM customer_order_item WHERE customer_order_id = $orderId AND id IN ($idList)";
        mysqli_query($c, $deleteItemsSql);
      } else {
        // Fallback to old behavior (approve all items)
        $deleteItemsSql = "DELETE FROM customer_order_item WHERE customer_order_id = $orderId";
        mysqli_query($c, $deleteItemsSql);
      }

      // Remove order only if no items remain
      $remainSql = "SELECT COUNT(*) c FROM customer_order_item WHERE customer_order_id = $orderId";
      $remainRes = mysqli_query($c, $remainSql);
      $remain = $remainRes ? (int)mysqli_fetch_object($remainRes)->c : 0;
      if ($remain <= 0) {
        $deleteOrderSql = "DELETE FROM customer_order WHERE id = $orderId";
        mysqli_query($c, $deleteOrderSql);
      }
    }
  }
  
  // Create multiple invoices: one invoice per product selected
  if (!empty($post->product) && is_array($post->product)) {
    foreach ($post->product as $id => $qty) {
      $qty = (int)$qty;
      if ($qty <= 0) continue;

      // Build a fresh invoice for each product
      $inv = R::dispense('invoice');
      $inv->status = 'New';
      $inv->customer_id = isset($post->customer_id) ? $post->customer_id : null;
      if (isset($post->salesman) && function_exists('nn') ? nn($post->salesman) : !empty($post->salesman)) {
        $inv->salesman = $post->salesman;
        $salesman = R::findOne('staff_salary', 'name=?', [$post->salesman]);
        if ($salesman && isset($salesman->incentive)) {
          $inv->incentive = $salesman->incentive;
        }
      }
      $inv->invoice_date = isset($post->date) ? $post->date : today();
      $inv->created_by = isset($_SESSION['UID']) ? $_SESSION['UID'] : (function_exists('uid') ? uid() : null);
      $inv->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;

      R::store($inv);

      // Create single invoice_item for this invoice
      $variance = R::load('product_variance', $id);
      if (!$variance || !$variance->id) continue;
      $product = R::load('product', $variance->product_id);

      $ii = R::dispense('invoice_item');
      $ii->invoice_id = $inv->id;
      $ii->product_id = $product->id;
      $ii->product_variance_id = $variance->id;
      $ii->quantity = $qty;
      $ii->price = isset($post->price[$id]) ? $post->price[$id] : $variance->price;
      $ii->cost = $variance->cost;
      $ii->name = $product->name;
      $ii->description = $variance->particulars;
      $ii->delivery_date = $inv->invoice_date;
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
    <div class="mt-2 text-sm text-gray-600">This will remove the item from the invoice.</div>
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