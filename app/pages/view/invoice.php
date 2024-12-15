<table class="table">
	<tr>
		<td width="33%">
			Attn To:
		</td>
		<td class="text-center">
			<h1>INVOICE</h1>
		</td>
		<td width="33%" class="text-right">
			<div>Invoice No: </div>
			<div>Invoice Date: </div>
			<div>Due Date: </div>
			<div>Page 1/1</div>
		</td>
	</tr>
</table>

<table class="table table-bordered">
	<tr>
		<td width="50%">
			Send To:
		</td>
		<td width="50%">
			<div>Ship to: </div>
		</td>
	</tr>
</table>



<table class="table table-bordered">
	<thead>
		<tr>
			<th>No.</th>
			<th>Product Description</th>
			<th class="text-center">Price</th>
			<th class="text-center">Quantity</th>
			<th class="text-center">Total</th>
		</tr>
	</thead>
	<tbody>
		<?php
      $items = R::find("invoice_item", "invoice_id=?", [ID]);
      $i = 1;
      foreach ($items as $key => $item) {
      	print "<tr>
					<td>$i</td>
					<td>Sea Master 250ml Bottles 250ml x 24</td>
					<td class='text-center'>$item->price</td>
					<td class='text-center'>$item->quantity</td>
					<td class='text-right'>".($item->price * $item->quantity)."</td>
				</tr>";
				sum('total', $item->price * $item->quantity);
				$i++;
      }
    ?>

		
	</tbody>
	<tfoot>
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th class="text-right">TOTAL</th>
			<th class="text-right">
				<?php
					print sum('total');
				?>
			</th>
		</tr>
	</tfoot>
</table>
