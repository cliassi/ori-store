<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if($_POST['orders']){
	$items = select("SELECT pv.id, pv.image, ii.name, size, unit, stock(pv.id) stock, TRIM(particulars) particulars, SUM(quantity) quantity 
		FROM `invoice_item` ii 
		INNER JOIN product_variance pv ON ii.product_variance_id=pv.id 
		INNER JOIN product p ON p.id=pv.product_id 
		INNER JOIN product_category pc ON pc.id=p.product_category_id 
		WHERE invoice_id IN ({$_POST['orders']}) AND pv.frozen=".($_POST['frozen'])."
		GROUP BY product_variance_id ORDER BY pc.sort_order, p.sort_order");

	while($item = mysqli_fetch_object($items)){
		print "<tr><td><input type='checkbox' name='pvid[]' value='$item->id'></td><td><img src='/store/{$item->image}' class='w32'></td><td>$item->name $item->particulars</td><td class='text-right ".($item->quantity > $item->stock?"color-red":"")."'>$item->stock</td><td><input type='number' name='variance[$item->id]' value='$item->quantity' max='$item->stock'></td><td><button name='save' class='btn btn-sm btn-warning'>Collect</button></td></tr>";
	}
}