<?php
if (isset($_POST['collect'])) {
$post = $_POST;
$obj = R::dispense("stock_collect");
$obj->salesman_id = isset($post['salesman']) ? $post['salesman'] : 0;
if (isset($post['collect'])) {
$obj->delivery_staff = isset($post['delivery_staff']) ? $post['delivery_staff'] : '';
if (!nn($obj->delivery_staff)) {
redir("?");
exit;
}
$obj->date = today();
$obj->created_by = uid();
// $obj->due_date = $post->due_date;
// $obj->delivery_date = $post->delivery_date;
// $obj->note = $post->note;

$stored = false;

$selectedIids = [];
if (isset($post['iid']) && is_array($post['iid'])) {
foreach (array_keys($post['iid']) as $iid) {
$iid = (int) $iid;
if ($iid <= 0) continue;
  update("invoice_item", "collected_at=IFNULL(collected_at, NOW()), collected_by=" . uid(), "id=$iid" );
  $selectedIids[]=$iid;
  }
  }

  if (!$selectedIids && isset($post['selected_customer']) && is_array($post['selected_customer'])) {
  $selectedCustomerIds=array_values(array_unique(array_filter(array_map('intval', $post['selected_customer']))));
  if ($selectedCustomerIds) {
  $customerIdsSql=implode(',', $selectedCustomerIds);
  $fallbackQuery="SELECT ii.id
                          FROM invoice_item ii
                          INNER JOIN invoice i ON i.id=ii.invoice_id
                          WHERE i.customer_id IN ($customerIdsSql)
                            AND IFNULL(ii.delivery_date,i.invoice_date) <= CURDATE()
                            AND ii.quantity > ii.delivered
                            AND NOT $collectedExpr" ;
  $fallbackItems=select($fallbackQuery);
  while ($fallbackItems && ($fallbackItem=mysqli_fetch_object($fallbackItems))) {
  $iid=(int) $fallbackItem->id;
  if ($iid <= 0) continue;
    update("invoice_item", "collected_at=IFNULL(collected_at, NOW()), collected_by=" . uid(), "id=$iid" );
    $selectedIids[]=$iid;
    }
    }
    }

    $hasVarianceQty=false;
    if (isset($post['variance']) && is_array($post['variance'])) {
    foreach ($post['variance'] as $id=> $qty) {
    if ($qty == 0) continue;
    $hasVarianceQty = true;
    if (!$stored) {
    R::store($obj);
    $stored = true;
    }
    $variance = R::load("product_variance", $id);
    $product = R::load("product", $variance->product_id);
    $ii = R::dispense("stock_collect_item");

    $ii->stock_collect_id = $obj->id;
    $ii->product_id = $product->id;
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

    if (!$selectedIids && !$hasVarianceQty) {
    redir("?");
    exit;
    }

    redir(ROOT . "/dcollect_delivery");
    exit;
    }
    }


    if (isset($_POST['update_delivery_date'])) {
    $post = $_POST;
    $ii = R::load('invoice_item', $post['id']);
    $ii->delivery_date = $post['date'];
    dd($ii);
    R::store($ii);
    }

    if (isset($_POST['save'])) {
    $post = $_POST;
    $obj = R::dispense("stock_collect");
    $obj->salesman_id = isset($post['salesman']) ? $post['salesman'] : 0;
    if (isset($post['save'])) {
    $obj->delivery_staff = $post['delivery_staff'];
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post['variance'] as $id => $qty) {
    if ($qty == 0) continue;
    if (!$stored) {
    R::store($obj);
    $stored = true;
    }
    $variance = R::load("product_variance", $id);
    $product = R::load("product", $variance->product_id);
    $ii = R::dispense("stock_collect_item");

    $ii->stock_collect_id = $obj->id;
    $ii->product_id = $product->id;
    $ii->product_variance_id = $variance->id;
    $ii->quantity = $qty;
    $ii->price = $variance->price;
    $ii->cost = $variance->cost;
    $ii->name = $product->name;
    $ii->description = "$variance->particulars $variance->size x $variance->unit";
    $ii->created_by = uid();

    R::store($ii);
    }

    redir(ROOT . "/delivery?s=$obj->delivery_staff");
    }
    }
    ?>

    <style type="text/css">
      th {
        text-align: center;
      }


      .customer-item td:nth-child(n+3),
      .customer-item th:nth-child(n+3) {
        width: 50px;
        white-space: nowrap;
      }

      td {
        vertical-align: top !important;
      }

      select,
      select option {
        font-size: .7rem;
      }

      footer.pc-footer {
        display: none !important;
      }

      /* Mobile-first adjustments */
      .mobile-container {
        width: 100%;
        padding: 8px
      }

      .table-customer {
        font-size: 12px
      }

      .table-responsive {
        overflow-x: auto
      }

      .grid-areas {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px
      }

      .has-checkbox span {
        width: auto
      }

      input[type=checkbox] {
        width: 22px;
        height: 22px
      }

      @media (min-width: 576px) {
        .grid-areas {
          grid-template-columns: repeat(4, 1fr)
        }

        .table-customer {
          font-size: 13px
        }
      }

      @media (max-width: 576px) {
        td[style] {
          width: auto !important
        }
      }

      .customer-container {
        display: flex;
        flex-direction: row;
        gap: 10px;
      }

      select {}

      /* Cards inside .orders */
      .orders {
        padding-bottom: 90px
      }

      /* Single column list */
      .orders .customer-container {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 10px
      }

      @media (min-width:540px) {
        .orders .customer-container {
          grid-template-columns: repeat(1, minmax(0, 1fr))
        }
      }

      @media (min-width:768px) {
        .orders .customer-container {
          grid-template-columns: repeat(1, minmax(0, 1fr))
        }
      }

      .orders .customer-item {
        display: block !important;
        background: #fff;
        border: 1px solid #e5e7ebca;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        overflow: hidden;
        width: 100% !important;
        padding: 5px;
      }

      .orders .customer-item table {
        width: 100%;
        margin: 0;
        table-layout: auto !important;
      }

      .orders .customer-item thead th,
      .orders .customer-item tbody td,
      .orders .customer-item tbody th {
        padding: 8px 10px;
        vertical-align: middle;
        word-wrap: break-word
      }

      .orders .toggle-store-info.hide-info {
        display: table
      }

      .orders input[type='number'] {
        width: 100%;
        min-width: 70px
      }

      .orders a {
        text-decoration: underline
      }

      .orders a.customer-link {
        text-decoration: none !important;
        color: #333 !important
      }

      .orders .all-collected {
        display: none
      }

      .orders .all-collected.show {
        display: table-row
      }
    </style>
    <?php

    if (isset($post->deliver)) {
      // dd($post);
      foreach ($post->iid as $key => $qty) {
        if ($qty > 0) {
          $ii = R::load("invoice_item", $key);
          $inv = R::load("invoice", $ii->invoice_id);
          update("invoice_item", "delivered=quantity, delivered_by=" . uid() . ", delivered_at=NOW(),delivery_staff='$post->delivery_staff'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
          insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$key, $qty, " . uid() . ",'$post->delivery_staff'");
        }
      }
    }

    if (isset($post->collect)) {
      $obj = R::dispense("stock_collect");
      $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
      if (isset($post->collect)) {
        $obj->delivery_staff = $post->delivery_staff;
        $obj->date = today();
        $obj->created_by = uid();
        // $obj->due_date = $post->due_date;
        // $obj->delivery_date = $post->delivery_date;
        // $obj->note = $post->note;

        $stored = false;

        foreach ($post->variance as $id => $qty) {
          if ($qty == 0) continue;
          if (!$stored) {
            R::store($obj);
            $stored = true;
          }
          $variance = R::load("product_variance", $id);
          $product = R::load("product", $variance->product_id);
          $ii = R::dispense("stock_collect_item");

          $ii->stock_collect_id = $obj->id;
          $ii->product_id = $product->id;
          $ii->product_variance_id = $variance->id;
          $ii->quantity = $qty;
          $ii->price = $variance->price;
          $ii->cost = $variance->cost;
          $ii->name = $product->name;
          $ii->description = "$variance->particulars $variance->size x $variance->unit";
          $ii->created_by = uid();

          R::store($ii);
        }

        redir("?");
      }
    }




    if (isset($post->update_delivery_date)) {
      $ii = R::load('invoice_item', $post->id);
      $ii->delivery_date = $post->date;
      dd($ii);
      R::store($ii);
    }

    if (isset($post->save)) {
      $obj = R::dispense("stock_collect");
      $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
      if (isset($post->save)) {
        $obj->delivery_staff = $post->delivery_staff;
        $obj->date = today();
        $obj->created_by = uid();
        // $obj->due_date = $post->due_date;
        // $obj->delivery_date = $post->delivery_date;
        // $obj->note = $post->note;

        $stored = false;

        foreach ($post->variance as $id => $qty) {
          if ($qty == 0) continue;
          if (!$stored) {
            R::store($obj);
            $stored = true;
          }
          $variance = R::load("product_variance", $id);
          $product = R::load("product", $variance->product_id);
          $ii = R::dispense("stock_collect_item");

          $ii->stock_collect_id = $obj->id;
          $ii->product_id = $product->id;
          $ii->product_variance_id = $variance->id;
          $ii->quantity = $qty;
          $ii->price = $variance->price;
          $ii->cost = $variance->cost;
          $ii->name = $product->name;
          $ii->description = "$variance->particulars $variance->size x $variance->unit";
          $ii->created_by = uid();

          R::store($ii);
        }

        redir(ROOT . "/delivery?s=$obj->delivery_staff");
      }
    }

    ?>
    <style type="text/css">
      th {
        text-align: left;
      }

      td span {
        display: inline-block;
        /*		border: solid 1px #ccc;*/
      }

      th span:nth-child(n+0) {
        /* width: 55px; */
      }

      td span:nth-child(n+0) {
        width: 20px;
      }

      a.has-checkbox {
        text-decoration: none;
        color: #000;
      }
    </style>
    <div class="container-fluid p-2 mobile-container">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped table-customer w-100">
          <thead>
            <tr>
              <th>
                <?php
                print $button;
                ?>
                <!-- <span><input type='check='pending-list' data-type='pending-list'> Pending Delivery List</span> -->
              </th>
              <?php
              $areas = R::find('city');
              // Hide category columns for mobile view
              ?>
            </tr>
          </thead>
          <tbody>

            <?php
            print "<tr>";
            print "<td style='width:600px'>";
            print "<label class='mb-1 d-block'>Select Area(s)</label>";
            print "<select id='areas' multiple class='form-select' size='8' style='min-width:220px'>";
            foreach ($areas as $key => $area) {
              print "<option value='$area->id'>" . ucfirst(strtolower($area->name)) . "</option>";
            }
            print "</select>";
            print "</td>";

            // Category product columns removed for mobile-only area list
            print "</tr>";
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class='orders'></div>


    <div class="modal fade" id="modal-modify-quantity" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" autocomplete="off" enctype='multipart/form-data'>
            <input type="hidden" name="invoice_item_id" id="invoice_item_id" value=''>
            <div class="modal-header">
              <h4 class="modal-title">Update Quantity</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <table>
                <tr>
                  <td>Update Quantity</td>
                  <td nowrap><input type='number' id='new-quantity' name='quantity' step='1' class='form-control'></td>
                </tr>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success" id='update_quantity_button' name="update_quantity">Save</button>
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="modal fade" id="modal-modify-price" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" autocomplete="off" enctype='multipart/form-data'>
            <input type="hidden" name="invoice_item_id" id="invoice_item_id" value=''>
            <div class="modal-header">
              <h4 class="modal-title">Update Price</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <table>
                <tr>
                  <td>Update Price</td>
                  <td nowrap><input type='number' id='new-price' name='price' step='1' class='form-control'></td>
                </tr>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success" id='update_price_button' name="update_price_button">Save</button>
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Modal -->
    <div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="dateModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="dateForm" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dateModalLabel">Set Date</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Hidden field to store ID -->
            <input type="hidden" id="hiddenId" name="id">

            <!-- Date picker -->
            <div class="mb-3">
              <label for="datepicker" class="form-label">Select Date</label>
              <input type="date" class="form-control" id="datepicker" name="date" required>

              <div class="form-text">Current delivery date: <span id="currentDeliveryDate"></span></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" name='update_delivery_date'>Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>


    <script type="text/javascript">
      var __canEditPriceQty = <?php echo canEditPriceAndQuantity() ? 'true' : 'false'; ?>;
      var __canEditAnything = <?php echo canEditAnything() ? 'true' : 'false'; ?>;

      function setItemId(id) {
        if (!__canEditPriceQty) return;
        $('#invoice_item_id').val(id);
      }

      function setItemIdPrice(id, price) {
        if (!__canEditPriceQty) return;
        $("#new-price").val(price);
        setItemId(id);
      }

      let dateModal;

      $('.has-checkbox').click(function(e) {
        e.preventDefault();
      })

      document.addEventListener('DOMContentLoaded', () => {
        dateModal = new bootstrap.Modal(document.getElementById('dateModal'));

        // Form submit handler
        document.getElementById('dateForm').addEventListener('submit', function(e) {
          e.preventDefault();
          debugger;
          const id = document.getElementById('hiddenId').value;
          const date = document.getElementById('datepicker').value;

          // You can send the data to server or handle it as needed
          console.log('ID:', id, 'Date:', date);

          $.post('/ajax/update_invoice_item_date.php', {
              update_date: 'update_date',
              date: date,
              invoice_item_id: id
            })
            .done((response) => {
              // $('#invoice-item-date-' + invoice_item_id).data('dd', response);
              // var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));
              // debugger;
              load();
              setTimeout(() => {
                $('#pending-only').trigger('change');
              }, 1000);

              // Then call the hide() method
              myModal.hide();
            })
            .fail(() => {});
          dateModal.hide();
        });
      });

  // Function to call and show modal with ID
  function setDate(el, id) {
    if (!__canEditAnything) return;
        var checked = $(".iid-date:checked");
        debugger;
        if (checked.length > 0) {
          // map values into array, join with commas
          var values = checked.map(function() {
            return this.value;
          }).get().join(",");

          document.getElementById('hiddenId').value = values;
        } else {
          // fallback to the argument
          document.getElementById('hiddenId').value = id;
        }

        // document.getElementById('hiddenId').value = id;
        document.getElementById('datepicker').value = ''; // Optional: clear previous value
        document.getElementById('currentDeliveryDate').innerHTML = $(el).data('dd');
        dateModal.show();
      }

      $("#update_quantity_button").click(function() {
        if (!window.__canEditPriceQty) return;
        const quantity = $('#new-quantity').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/ajax/update_invoice_item_quantity.php', {
            update_quantity: 'update_quantity',
            quantity: quantity,
            invoice_item_id: invoice_item_id
          })
          .done((response) => {
            $('#invoice-item-' + invoice_item_id).text(quantity);
            var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));

            // Then call the hide() method
            myModal.hide();
          })
          .fail(() => {});
      });
      $("#update_price_button").click(function() {
        if (!window.__canEditPriceQty) return;
        const price = $('#new-price').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/ajax/update_invoice_item_price.php', {
            update_price: 'update_price',
            price: price,
            invoice_item_id: invoice_item_id
          })
          .done((response) => {
            if (response != "")
              $('#invoice-item-price-' + invoice_item_id).parent().text(response);
            var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-price'));

            // Then call the hide() method
            myModal.hide();
          })
          .fail(() => {});
      });
      $("input[type=checkbox]").change(function() {
        let selectedCustomers = $('.checkbox-area:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        let selectedProducts = $('.checkbox-product:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending, delivery, collection, pendingList);
        $.post('/ajax/dcollect.php', {
            customers: selectedCustomers,
            products: selectedProducts,
            order: order,
            pending: pending,
            delivery: delivery,
            collection: collection,
            pendingList: pendingList
          })
          .done((response) => {
            $('.orders').html(response);
          })
          .fail(() => {});
      });

      function load() {
        let selectedCustomers = $('.checkbox-area:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        let selectedProducts = $('.checkbox-product:checked').map(function() {
          return $(this).data('id');
        }).get().join(',');
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending);
        $.post('/ajax/dcollect.php', {
            customers: selectedCustomers,
            products: selectedProducts,
            order: order,
            pending: pending,
            delivery: delivery,
            collection: collection,
            pendingList: pendingList
          })
          .done((response) => {
            $('.orders').html(response);
          })
          .fail(() => {});
      }

      load();

      $(function() {
        $("#areas").on('change', function() {
          const selectedAreas = $(this).val() ? $(this).val().join(',') : '';
          const selectedProducts = '';
          const order = $("#all-order").prop('checked');
          const pending = $("#pending-only").prop('checked');
          const delivery = $(".delivery-list").prop('checked');
          const collection = $(".collection-list").prop('checked');
          const pendingList = $("#pending-list").prop('checked');
          $.post('/ajax/dcollect.php', {
              customers: selectedAreas,
              products: selectedProducts,
              order: order,
              pending: pending,
              delivery: delivery,
              collection: collection,
              pendingList: pendingList
            })
            .done((response) => {
              $('.orders').html(response);
            })
            .fail(() => {});
        });
      });
    </script>