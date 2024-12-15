<style type="text/css">
	th{
		text-align: center;
	}
	td{
		vertical-align: top !important;
	}
</style>
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
					<tr><th></th><th><input type='text' id='f-customer'></th><th><input id="f-area"></th><th></th></tr>
				</thead>
				<tbody>
					<?php
						$d = isset($get->d)?$get->d:subDay(30);
						$t = isset($get->t)?$get->t:today();
  					$customers = toA('customer', 'id', 'company');
  					$cities = toA('customer', 'id', 'city');
  					$trans = select("SELECT * FROM (SELECT i.*, invoiceItems(i.id) particulars, SUM(quantity) quantity, SUM(quantity*price) total FROM invoice i, invoice_item ii WHERE i.id=ii.invoice_id AND invoice_date BETWEEN '$d' AND '$t' GROUP BY i.id) a ORDER BY id");
  					while($order = mysqli_fetch_object($trans)){
  						print "<tr><td><input type='checkbox' value='$order->id'></td><td>".$customers[$order->customer_id]."</td><td>".$cities[$order->customer_id]."</td><td>".df($order->invoice_date)."</td></tr>";
  					}
					?>
					
				</tbody>
			</table>
		</td>
		<td>
			<table class="table table-bordered table-hover table-striped table-product">
				<thead>
					<tr><th></th><th>Product</th><th>Size</th><th>Quanity</th></tr>
					<tr><th></th><th><input id='f-product'></th><th><input id='f-size'></th><th></th></tr>
				</thead>
				<tbody id='items'>
				</tbody>
			</table>
		</td>
	</tbody>
</table>

<script type="text/javascript">
	$("input[type='checkbox']").change(loadProducts);
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

	function loadProducts(){
		var concatenatedValues = $("input[type='checkbox']:checked").map(function() {
      return $(this).val();
    }).get().join(', ');
    $.post("/store/ajax/dorder.php", {orders:concatenatedValues}, function(res){
    	console.log(res);
    	$("#items").html(res);
    });
	}
</script>