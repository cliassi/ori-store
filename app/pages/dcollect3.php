<style type="text/css">
    th{
        text-align: center;
    }
    td{
        vertical-align: top !important;
    }
    footer.pc-footer{
        display: none !important;
    }
</style>
<?php

if (isset($post->deliver)) {
    // dd($post);
    foreach ($post->iid as $key => $qty) {
        if($qty > 0){
            $ii = R::load("invoice_item", $key);
            $inv = R::load("invoice", $ii->invoice_id);
            update("invoice_item", "delivered=quantity, delivered_by=".uid().", delivered_at=NOW(),delivery_staff='$post->delivery_staff'", "product_variance_id=$ii->product_variance_id AND delivery_date='$ii->delivery_date' AND invoice_id IN (select id FROM invoice WHERE customer_id=$inv->customer_id)");
            insert("invoice_item_delviery", "`invoice_item_id`, `quantity`, `delivered_by`, delivery_staff", "$key, $qty, ".uid().",'$post->delivery_staff'");
        }
    }
}

if (isset($post->collect)) {
    $obj = R::dispense("stock_collect");
    $obj->salesman_id = isset($post->salesman)?$post->salesman:0;
    if(isset($post->collect)){
      $obj->delivery_staff = $post->delivery_staff;
      $obj->date = today();
      $obj->created_by = uid();
      // $obj->due_date = $post->due_date;
      // $obj->delivery_date = $post->delivery_date;
      // $obj->note = $post->note;
 
      $stored = false;

    //   dd($post);
 
      foreach ($post->variance as $id => $qty) {
        if($qty == 0) continue;
        if(!$stored){ R::store($obj); $stored = true; }
        $item = R::load("invoice_item", $id);
        $variance = R::load("product_variance", $item->product_variance_id);
        $product = R::load("product", $variance->product_id);
        $ii = R::dispense("stock_collect_item");
 
        $ii->stock_collect_id = $obj->id;
        $ii->product_id = $product->id;
        $ii->invoice_item_id = $item->id;
        $ii->product_variance_id = $variance->id;
        $ii->quantity = $qty;
        $ii->price = $variance->price;
        $ii->cost = $variance->cost;
        $ii->name = $product->name;
        $ii->description = "$variance->particulars $variance->size x $variance->unit";
        $ii->created_by = uid();
 
        R::store($ii);
      }
 
    //   redir("?");
    }
  }
  


if(isset($post->update_delivery_date)){
    $ii = R::load('invoice_item', $post->id);   
    $ii->delivery_date = $post->date;
    dd($ii);
    R::store($ii);
}

if (isset($post->save)) {
  $obj = R::dispense("stock_collect");
  $obj->salesman_id = isset($post->salesman)?$post->salesman:0;
  if(isset($post->save)){
    $obj->delivery_staff = $post->delivery_staff;
    $obj->date = today();
    $obj->created_by = uid();
    // $obj->due_date = $post->due_date;
    // $obj->delivery_date = $post->delivery_date;
    // $obj->note = $post->note;

    $stored = false;

    foreach ($post->variance as $id => $qty) {
      if($qty == 0) continue;
      if(!$stored){ R::store($obj); $stored = true; }
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

    redir(ROOT."/delivery?s=$obj->delivery_staff");
  }
}

?>
<style type="text/css">
    th{
        text-align: left;
    }
    td span{
        display: inline-block;
/* border: solid 1px #ccc;*/
    }
    th span:nth-child(n+0){
        width: 55px;
    }
    td span:nth-child(n+0){
        width: 20px;
    }
    a.has-checkbox{
        text-decoration: none;
        color: #000;
    }
</style>
<table class="table table-bordered table-hover table-striped table-customer">
    <thead>
        <tr>
            <th>
                <div class='hidden'>
                <a href='' class='has-checkbox2'><span><input type='checkbox' id='all-order' data-type='all'> Order</span></a>
                <a href='' class='has-checkbox2'><span><input type='checkbox' id='delivery-list' class='delivery-list' data-type='delivery-list'> Order List</span></a>
                <a href='' class='has-checkbox2'><span><input type='checkbox' id='pending-only' data-type='all-pending'> Pending</span></a>
                </div>
                <a href='' class='has-checkbox'><span><input type='checkbox' id='collection-list' class='collection-list' data-type='collection-list'> Collection List</span></a>
                </th>
            <?php
                //$areas = R::find('city');
                $areas = R::find('city', '1 ORDER BY name ASC');
                $cats = R::find('product_category', 'sort_order > -1 ORDER BY sort_order');
                // START CHANGE 1: Attach Category ID to the header checkbox
                foreach ($cats as $key => $cat) {
                    // Changed class to 'checkbox-category' and data-type to 'category'
                    print "<th><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-category' data-type='category' data-id='$cat->id'></span> ";
                    print "<span>$cat->name</span></a></th>";
                }
                // END CHANGE 1
            ?>
        </tr>
    </thead>
    <tbody>

        <?php
           print "<td style='width:1200px'><div style='display:grid;grid-template-columns: repeat(8, 1fr); grid-auto-flow: column; grid-template-rows: repeat(" . ceil(count($areas)/8) . ", auto); gap: 5px'>";
           foreach ($areas as $key => $area) {
               print "<div><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-area' data-type='area' data-id='$area->id'></span><span>".ucfirst(strtolower($area->name))."</span></a></div>";
           }
           print "</div></td>";
           
            foreach ($cats as $key => $cat) {
                $products = R::find("product", "product_category_id=?", [$cat->id]);
                print "<td>";
                    // foreach($products as $product){
                    //     // NOTE: This checkbox still holds the PRODUCT ID, but we will ignore it in the filter gathering logic
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
                    <tr><td>Update Quantity</td><td nowrap><input type='number' id='new-quantity' name='quantity' step='1' class='form-control'></td></tr>
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="dateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="dateForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateModalLabel">Set Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="hiddenId" name="id">

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
    function setItemId(id){
        $('#invoice_item_id').val(id);
    }

    tippy('[data-tippy-content]', {
        placement: 'top',
        animation: 'shift-away',
        theme: 'light-border',
        delay: [100, 100],  // [show, hide] delay in ms
    });

  let dateModal;

  $("#collection-list").prop('checked', true).trigger('change');
load();

    $('.has-checkbox').click(function(e){
        e.preventDefault();
        const checked = $(this).find('input').prop('checked');
        $(this).find('input').prop('checked', !checked).trigger('change');
    })

    document.addEventListener('DOMContentLoaded', () => {
        dateModal = new bootstrap.Modal(document.getElementById('dateModal'));

        // Form submit handler
        document.getElementById('dateForm').addEventListener('submit', function (e) {
            e.preventDefault();
            
            const id = document.getElementById('hiddenId').value;
            const date = document.getElementById('datepicker').value;
            
            // You can send the data to server or handle it as needed
            console.log('ID:', id, 'Date:', date);
            
            $.post('/store/ajax/update_invoice_item_date.php', { update_date :'update_date', date: date, invoice_item_id: id })
                .done((response) => {
                    $('#invoice-item-date-' + invoice_item_id).data('dd', response);
                    var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));
                    
                    setTimeout(() => {
                        $('#pending-only').trigger('change');             
                    }, 1000);

                    // Then call the hide() method
                    myModal.hide();
                })
                .fail(() => {
                });
            dateModal.hide();
        });
    });

    // Function to call and show modal with ID
    function setDate(el, id) {
        document.getElementById('hiddenId').value = id;
        document.getElementById('datepicker').value = ''; // Optional: clear previous value
        document.getElementById('currentDeliveryDate').innerHTML = $(el).data('dd');
        dateModal.show();
    }

    function toggleInfo(){
        $('.toggle-store-info').toggleClass('show-info hide-info');
    };
    function toggleCollected(){
        $('.all-collected').toggleClass('show');
    }

    $("#update_quantity_button").click(function(){
        const quantity = $('#new-quantity').val();
        const invoice_item_id = $('#invoice_item_id').val();
        $.post('/store/ajax/update_invoice_item_quantity.php', { update_quantity: 'update_quantity', quantity: quantity, invoice_item_id: invoice_item_id })
            .done((response) => {
                $('#invoice-item-' + invoice_item_id).text(quantity);
                var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-quantity'));

                // Then call the hide() method
                myModal.hide();
            })
            .fail(() => {
            });
    });

    // START CHANGE 2: Filter by Category ID
    $("input[type=checkbox]").change(function(){
        let selectedCustomers = $('.checkbox-area:checked').map(function () { return $(this).data('id');}).get().join(',');
        
        // This line now gathers the Category IDs from the TH checkboxes
        let selectedProducts = $('.checkbox-category:checked').map(function () { return $(this).data('id');}).get().join(',');
        
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending);
        $.post('/store/ajax/dcollect.php', { customers: selectedCustomers, products: selectedProducts, order: order, pending: pending , delivery: delivery , collection: collection , pendingList: pendingList })
        .done((response) => {
            $('.orders').html(response);
            
            
            
            document.addEventListener('DOMContentLoaded', function () {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        })
        .fail(() => {
        });
    });
    // END CHANGE 2

    function fillAllCollectionQty(){
        $('.ordered-qty').each(function() {
            if ($(this).find('a').length > 0) {
                $(this).find('a')[0].click(); // Trigger native click
            }
        });
    }

    function fillCollectionQty(el, qty){
        $("#" + el).val(qty);
    }

    function load(){
        let selectedCustomers = $('.checkbox-area:checked').map(function () { return $(this).data('id');}).get().join(',');
        
        // Use category checkboxes for initial load as well
        let selectedProducts = $('.checkbox-category:checked').map(function () { return $(this).data('id');}).get().join(',');
        
        const order = $("#all-order").prop('checked');
        const pending = $("#pending-only").prop('checked');
        const delivery = $(".delivery-list").prop('checked');
        const collection = $(".collection-list").prop('checked');
        // if(delivery) $("#pending-list").prop('checked', false);
        const pendingList = $("#pending-list").prop('checked');
        // if(pendingList) $(".delivery-list").prop('checked', false);

        console.log('Selected IDs:', selectedCustomers, selectedProducts, pending);
        $.post('/store/ajax/dcollect.php', { customers: selectedCustomers, products: selectedProducts, order: order, pending: pending , delivery: delivery , collection: collection , pendingList: pendingList })
        .done((response) => {
            $('.orders').html(response);
        })
        .fail(() => {
        });
    }

</script>
<?php /* ... (Commented out code block follows) ... */ ?>