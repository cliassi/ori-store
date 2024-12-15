<table class='table table-bordered'>
	<thead>
		<tr>
			<th>#</th>
			<th>Date</th>
			<th>Particulars</th>
			<th class='text-right'>Amount</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$report = R::find("cw_cash", "amount < 0");
			$i = 1;
			foreach ($report as $key => $value) {
				print "<tr>
				<td>$i</td>
				<td>".df($value->date)."</td>
				<td>$value->particulars</td>
				<td class='text-right'>".nf0(abs($value->amount))."</td>
				<td></td>
				</tr>";
				sum('total', abs($value->amount));
				$i++;
			}
		?>
	</tbody>
	<tfoot>
		<tr>
			<th>#</th>
			<th>Date</th>
			<th class='text-right'>TOTAL</th>
			<th class='text-right'><?php print nf0(sum('total')); ?></th>
			<th></th>
		</tr>
	</tfoot>
</table>