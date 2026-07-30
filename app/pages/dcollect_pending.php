<style type="text/css">
  th {
    text-align: center;
  }

  td {
    vertical-align: top !important;
  }

  footer.pc-footer {
    display: none !important;
  }
</style>

<style type="text/css">
  th {
    text-align: left;
  }

  td span {
    display: inline-block;
    /*        border: solid 1px #ccc;*/
  }

  /* th span:nth-child(n+0) {
    width: 55px;
  } */

  td span:nth-child(n+0) {
    width: 20px;
  }

  a.has-checkbox {
    text-decoration: none;
    color: #000;
  }

  .tbody-toggle-cell {
    width: 24px;
    text-align: center;
  }

  a.tbody-toggle {
    text-decoration: none;
    color: #000;
    display: inline-block;
    padding: 0 6px;
    font-weight: 700;
    cursor: pointer;
    user-select: none;
  }

  .table-customer tbody {
    display: none;
  }

  .table-customer tbody.show {
    display: table-row-group;
  }
</style>
<div class="text-center">
  <h3>Pending Order</h3>
  <label style="font-weight: normal; float: right; margin-top:-30px; margin-right: 30px">
    <input type="checkbox" id="check-all-orders"> Select all
  </label>
</div>
<table class="table table-bordered table-hover table-striped table-customer">
  <thead>
    <tr>
      <th class='hidden'>
        <a href='' class='has-checkbox'><span><input type='checkbox' id='all-order' data-type='all'> Order</span></a>
        <a href='' class='has-checkbox'><span><input type='checkbox' id='delivery-list' class='delivery-list' data-type='delivery-list'> Order List</span></a>
        <a href='' class='has-checkbox'><span><input type='checkbox' checked='true' id='pending-only' data-type='all-pending'> Pending</span></a>
        <a href='' class='has-checkbox hidden'><span><input type='checkbox' id='collection-list' class='collection-list' data-type='collection-list'> Collection List</span></a>
        <!-- <span><input type='check='pending-list' data-type='pending-list'> Pending Delivery List</span> -->
      </th>
      <?php
      //$areas = R::find('city');
      $areas = R::find('city', '1 ORDER BY name ASC');
      $cats = R::find('product_category', 'sort_order > -1 ORDER BY sort_order');
    // foreach ($cats as $key => $cat) {
    //  print "<th><a href='' class='has-checkbox'><span><input type='checkbox' data-type='all-area'></span> ";
    //  print "<span>$cat->name</span></a></th>";
    // }
    print "<th></th>";

    foreach ($cats as $key => $cat) {

        print "<th><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-product' data-id='$cat->id'></span> ";
        print "<span>$cat->name</span></a></th>";
      }
      print "<th class='tbody-toggle-cell text-left'><a href='' class='tbody-toggle'>▶</a></th>";

      ?>
    </tr>
  </thead>
  <tbody>

    <?php

    print "<td style='width:1200px'><div style='display:grid;grid-template-columns: repeat(8, 1fr); grid-auto-flow: column; grid-template-rows: repeat(" . ceil(count($areas) / 8) . ", auto); gap: 5px'>";
    foreach ($areas as $key => $area) {
      print "<div><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-area' data-type='area' data-id='$area->id'></span><span>" . ucfirst(strtolower($area->name)) . "</span></a></div>";
    }
    print "</div></td>";

    foreach ($cats as $key => $cat) {
      $products = R::find("product", "product_category_id=?", [$cat->id]);
      print "<td>";
      // foreach($products as $product){
      //     print "<div><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-product' data-type='variance' data-id='$product->id'></span><span>$product->name</span></a></div>";
      // }
      print "</td>";
    }
    print "</tr>";
    ?>
  </tbody>
</table>
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
          <input type="date" class="form-control" id="datepicker" name="date" required value="<?= date('Y-m-d') ?>">

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
  var __restrictedUser = <?php echo (uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar')) ? 'true' : 'false'; ?>;

  function setItemId(id) {
    if (__restrictedUser) return;
    $('#invoice_item_id').val(id);
  }

  function setItemIdPrice(id, price) {
    if (__restrictedUser) return;
    $('#new-price').val(price);
    setItemId(id);
  }

  let dateModal;

  $('.has-checkbox').click(function(e) {
    e.stopPropagation();
    e.preventDefault();
    const checked = $(this).find('input').prop('checked');
    $(this).find('input').prop('checked', !checked).trigger('change');
  })

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('check-all-orders').addEventListener('change', function() {
      const checked = this.checked;
      document.querySelectorAll('.orders input.iid-date').forEach(function(cb) {
        cb.checked = checked;
        cb.indeterminate = false;
        cb.dispatchEvent(new Event('change', {
          bubbles: true
        }));
      });
    });

    document.querySelectorAll('.table-customer .tbody-toggle-cell .tbody-toggle').forEach((toggle) => {
      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const table = toggle.closest('table');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const isShown = tbody.classList.toggle('show');
        toggle.textContent = isShown ? '▼' : '▶';
      });
    });

    dateModal = new bootstrap.Modal(document.getElementById('dateModal'));

    // Form submit handler
    document.getElementById('dateForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const date = document.getElementById('datepicker').value;
      const id = getSelectedInvoiceItemIds() || document.getElementById('hiddenId').value;
      const updateCount = id ? id.split(',').filter(Boolean).length : 0;

      if (!updateCount || !confirm('Update delivery date for ' + updateCount + ' selected item(s) to ' + date + '?')) {
        return;
      }

      // You can send the data to server or handle it as needed
      console.log('ID:', id, 'Date:', date);

      $.post('/store/ajax/update_invoice_item_date.php', {
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
          dateModal.hide();
        })
        .fail(() => {});
    });
  });

  // Function to call and show modal with ID
  function setDate(el, id) {
    if (__restrictedUser) return;
    var selectedIds = getSelectedInvoiceItemIds();
    if (selectedIds) {
      document.getElementById('hiddenId').value = selectedIds;
    } else {
      document.getElementById('hiddenId').value = id;
    }

    // document.getElementById('hiddenId').value = id;
    document.getElementById('datepicker').value = new Date().toISOString().slice(0, 10);
    document.getElementById('currentDeliveryDate').innerHTML = $(el).data('dd');
    dateModal.show();
  }

  function getSelectedInvoiceItemIds() {
    const selectedIds = [];

    document.querySelectorAll('.orders input.iid-date:checked').forEach(function(cb) {
      const ids = cb.dataset.iids ? cb.dataset.iids.split(',') : [cb.value];
      ids.forEach(function(id) {
        id = String(id).trim();
        if (id && !selectedIds.includes(id)) selectedIds.push(id);
      });
    });

    return selectedIds.join(',');
  }

  $("#update_quantity_button").click(function() {
    if (__restrictedUser) return;
    const quantity = $('#new-quantity').val();
    const invoice_item_id = $('#invoice_item_id').val();
    $.post('/store/ajax/update_invoice_item_quantity.php', {
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
    if (__restrictedUser) return;
    const price = $('#new-price').val();
    const invoice_item_id = $('#invoice_item_id').val();
    $.post('/store/ajax/update_invoice_item_price.php', {
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
  $(".checkbox-area, .checkbox-product, #all-order, #delivery-list, #pending-only, #collection-list, #pending-list").change(triggerChange);

  triggerChange();

  function triggerChange() {
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
    $.post('/store/ajax/dcollect_pending.php', {
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
    $.post('/store/ajax/dcollect_pending.php', {
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
</script>
