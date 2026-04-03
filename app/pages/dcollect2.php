<style type="text/css">
	th{
		text-align: center;
	}
	td{
		vertical-align: top !important;
	}
</style>
<?php

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
<table class="table table-bordered table-hover table-striped table-customer">
	<thead>
		<tr>
				<th></th><th>
					Product Type <br>
				<input type="radio" name="type" value="Frozen"> Frozen <?php print space(3); ?>
				<input type="radio" name="type" value="Fresh"> Fresh <?php print space(3); ?>
				<input type="radio" name="type" value='General'> General 
			</th>
			<th>Area <br><?php print sop2('city', ''); ?></th>
			<th>Product Variant<br> <?php print sop2('product_variance', '', ['dataField'=>'particulars']); ?></th>
			<?php //print "<th>Customer Shop<br>".sop2('customer', '', ['dataField'=>'company'])."</th>"; ?>
			<th>Order Date</th>
		</tr>
		<!-- <tr><th><input type='checkbox' id='t-customer' onchange="toggleCustomer()" value="0"></th><th><input type='text' id='f-customer'></th><th><input id="f-area"></th><th><input type="checkbox" id='frozen'> Frozen</th></tr> -->
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
<?php */ ?>
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