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
  
	  redir("?");
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
/*		border: solid 1px #ccc;*/
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
				<a href='' class='has-checkbox'><span><input type='checkbox' id='all-order' data-type='all'> Order</span></a>
				<a href='' class='has-checkbox'><span><input type='checkbox' id='delivery-list' class='delivery-list' data-type='delivery-list'> Order List</span></a>
				<a href='' class='has-checkbox'><span><input type='checkbox' id='pending-only' data-type='all-pending'> Pending</span></a>
				<a href='' class='has-checkbox hidden'><span><input type='checkbox' id='collection-list' class='collection-list' data-type='collection-list'> Collection List</span></a>
				<!-- <span><input type='check='pending-list' data-type='pending-list'> Pending Delivery List</span> -->
			</th>
			<?php
				$areas = R::find('city');
				$cats = R::find('product_category', 'sort_order > -1 ORDER BY sort_order');
				foreach ($cats as $key => $cat) {
					print "<th><a href='' class='has-checkbox'><span><input type='checkbox' data-type='all-area'></span> ";
					print "<span>$cat->name</span></a></th>";
				}
			?>
		</tr>
	</thead>
	<tbody>

		<?php
			print "<tr>";
			print "<td style='width:600px'><div style='display:grid;grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1px'>";
			foreach ($areas as $key => $area) {
				print "<div><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-area' data-type='area' data-id='$area->id'></span><span>".ucfirst(strtolower($area->name))."</span></a></div>";
			}
			print "</div></td>";

			foreach ($cats as $key => $cat) {
				$products = R::find("product", "product_category_id=?", [$cat->id]);
				print "<td>";
					foreach($products as $product){
						print "<div><a href='' class='has-checkbox'><span><input type='checkbox' class='checkbox-product' data-type='variance' data-id='$product->id'></span><span>$product->name</span></a></div>";
					}
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
	      		<tr><td>Update Price</td><td nowrap><input type='number' id='new-price' name='price' step='1' class='form-control'></td></tr>
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
	function setItemId(id){
		$('#invoice_item_id').val(id);
	}
	function setItemIdPrice(id, price){
		$("#new-price").val(price);
		setItemId(id);
	}

  let dateModal;

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
      debugger;
      const id = document.getElementById('hiddenId').value;
      const date = document.getElementById('datepicker').value;
      
      // You can send the data to server or handle it as needed
      console.log('ID:', id, 'Date:', date);
      
	  $.post('/store/ajax/update_invoice_item_date.php', { update_date :'update_date', date: date, invoice_item_id: id })
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
		.fail(() => {
		});
      dateModal.hide();
    });
  });

  // Function to call and show modal with ID
  function setDate(el, id) {
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
	$("#update_price_button").click(function(){
		const price = $('#new-price').val();
		const invoice_item_id = $('#invoice_item_id').val();
		$.post('/store/ajax/update_invoice_item_price.php', { update_price: 'update_price', price: price, invoice_item_id: invoice_item_id })
		.done((response) => {
			if(response != "")
			$('#invoice-item-price-' + invoice_item_id).parent().text(response);
			var myModal = bootstrap.Modal.getInstance(document.getElementById('modal-modify-price'));

			// Then call the hide() method
			myModal.hide();
		})
		.fail(() => {
		});
	});
	$("input[type=checkbox]").change(function(){
		let selectedCustomers = $('.checkbox-area:checked').map(function () { return $(this).data('id');}).get().join(',');
		let selectedProducts = $('.checkbox-product:checked').map(function () { return $(this).data('id');}).get().join(',');
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
	});

	function load(){
		let selectedCustomers = $('.checkbox-area:checked').map(function () { return $(this).data('id');}).get().join(',');
		let selectedProducts = $('.checkbox-product:checked').map(function () { return $(this).data('id');}).get().join(',');
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
<?php /* ?>
<form method="post">
	<table class="table">
		<thead>
			<tr>
				<th width="50%">Store Orders</th>
				<th>Stock to Collect</th>
			</tr>
		</thead>
		<tbody>
			<td>
				<table class="table table-bordered table-hover table-striped table-customer">
					<thead>
						<tr><th></th><th>Customer</th><th>Area</th><th>Order Date</th></tr>
						<tr><th><input type='checkbox' id='t-customer' onchange="toggleCustomer()" value="0"></th><th><input type='text' id='f-customer'></th><th><input id="f-area"></th><th><input type="checkbox" id='frozen'> Frozen</th></tr>
					</thead>
					<tbody>
						<?php
							$d = isset($get->d)?$get->d:subDay(30);
							$t = isset($get->t)?$get->t:today();
	  					$customers = toA('customer', 'id', 'company');
	  					$cities = toA('customer', 'id', 'city');
	  					$trans = select("SELECT * FROM (SELECT i.*, invoiceItems(i.id) particulars, SUM(quantity) quantity, SUM(quantity*price) total FROM invoice i, invoice_item ii WHERE i.id=ii.invoice_id AND invoice_date BETWEEN '$d' AND '$t' GROUP BY i.id) a ORDER BY id");
	  					while($order = mysqli_fetch_object($trans)){
	  						print "<tr><td><input type='checkbox' class='customer' value='$order->id'></td><td>".$customers[$order->customer_id]."</td><td>".$cities[$order->customer_id]."</td><td>".df($order->invoice_date)."</td></tr>";
	  					}
						?>
						
					</tbody>
				</table>
			</td>
			<td>
				<table class="table table-bordered table-hover table-striped table-product">
					<thead>
						<tr><th></th><th></th><th>Product</th><th>Quanity Avl.</th><th>Quanity</th></tr>
						<tr><th><input type='checkbox' id='t-product' onchange="toggleProduct()"></th><th><input id='f-product'></th><th><input id='f-size'></th><td colspan="2">
							<?php
								print "<select class='supplier-select' name='delivery_staff' required>
	                    <option value=''>Please select</option>";

	                    $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
	                    while ($man = mysqli_fetch_object($objs)) {
	                      print "<option ";
	                      // if($man->name == $sm) print "selected";
	                      print ">$man->name</option>";
	                    }
	                print "</select>";
							?>
						</td></tr>
					</thead>
					<tbody id='items'>
					</tbody>
				</table>
			</td>
		</tbody>
	</table>
</form>
<?php 
<script type="text/javascript">
	$(".table-customer input[type='checkbox']").change(loadProducts);
	$("#f-customer,#f-area").keyup(function(){
		var filterText = $(this).val().toLowerCase(); // Get the input text and convert to lowercase
    $('.table-customer tbody tr').filter(function() {
      if ($(this).text().toLowerCase().indexOf(filterText) > -1) {
        $(this).removeClass('hidden'); // Remove 'hidden' class if it matches
      } else {
        $(this).addClass('hidden'); // Add 'hidden' class if it doesn't match
      }
      $(".table-customer tbody tr.hidden input[type='checkbox']").prop('checked', false);
      if(filterText.length>1){
      	$(".table-customer tbody tr:not(.hidden) input[type='checkbox']").prop('checked', true);
      	setTimeout(loadProducts, 1000);
      }
    });
	});
	$("#f-product,#f-size").keyup(function(){
		var filterText = $(this).val().toLowerCase(); // Get the input text and convert to lowercase
    $('.table-product tbody tr').filter(function() {
      if ($(this).text().toLowerCase().indexOf(filterText) > -1) {
        $(this).removeClass('hidden'); // Remove 'hidden' class if it matches
      } else {
        $(this).addClass('hidden'); // Add 'hidden' class if it doesn't match
      }
      $(".table-product tbody tr.hidden input[type='checkbox']").prop('checked', false);
    	$(".table-product tbody tr:not(.hidden) input[type='checkbox']").prop('checked', true);
    });
	});

	function toggleCustomer(){
		const state = $("#t-customer").prop('checked');
		if(state){
	    $(".table-customer tbody tr.hidden input[type='checkbox']").prop('checked', false);
	    $(".table-customer tbody tr:not(.hidden) input[type='checkbox']").prop('checked', true);
	  } else{
	    $(".table-customer tbody tr input[type='checkbox']").prop('checked', false);
	  }
	}

	function toggleProduct(){
		const state = $("#t-product").prop('checked');
		if(state){
	    $(".table-product tbody tr.hidden input[type='checkbox']").prop('checked', false);
	    $(".table-product tbody tr:not(.hidden) input[type='checkbox']").prop('checked', true);
	  } else{
	    $(".table-product tbody tr input[type='checkbox']").prop('checked', false);
	  }
	}

	function loadProducts(){
		// var concatenatedValues = $(".table-customer tbody tr:not(.hidden) input[type='checkbox']:checked").map(function() {
		var concatenatedValues = $("input[type='checkbox'].customer:checked").map(function() {
      return $(this).val();
    }).get().join(', ');
    const frozen = $("#frozen").prop('checked');
    $.post("/store/ajax/dorder.php", {orders:concatenatedValues, frozen:frozen?'1':'0'}, function(res){
    	console.log(res);
    	$("#items").html(res);
    });
	}
</script>
*/ ?>