<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

extract($_POST);
if($order == 'false' && $pending == 'false' && $delivery == 'false' && $collection == 'false') exit;

$filter = " WHERE ";
$cities = preg_replace('/[^0-9,]/', '', $customers);
$products = preg_replace('/[^0-9,]/', '', $products);
if(nn($cities)){
	$filter .= " city.id IN ($cities)";
}
if(nn($products)){
	$filter .= ($filter != " WHERE " ? " AND " : " ")." ii.product_id IN ($products)";
}
//$filter .= ($filter != " WHERE " ? " AND " : " ").($pending == 'true' ? " i.invoice_date < curdate()" : " i.invoice_date = curdate()");
$filter .= ($filter != " WHERE " ? " AND " : " ").($pending == 'true' ? " IFNULL(ii.delivery_date,i.invoice_date) < curdate()" : " IFNULL(ii.delivery_date,i.invoice_date) = curdate()");
$filter .= ($filter != " WHERE " ? " AND " : " ")." ii.quantity >= ii.delivered";
$query = "SELECT distinct c.* FROM customer c INNER JOIN invoice i ON c.id=i.customer_id INNER JOIN invoice_item ii ON i.id=ii.invoice_id LEFT JOIN city ON city.name=c.city $filter";

$customers = select($query);
// print $query;

?>
<style>
  .customer-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px; /* Optional spacing */
  }

  .customer-item {
    padding: 10px;
    margin: 5px;
    width: max-content; /* Takes up width based on content */
    white-space: nowrap; /* Prevent line breaks inside */
    border: 1px solid #ccc;
  }
  input[type='number']{
	width: 100%;
	min-width: 70px;
  }
  a{
	text-decoration: underline;
  }
  a.customer-link{
	text-decoration: none !important;
	color: #333 !important;
}
</style>
<form method='post'>
<div class="customer-container">
<?php
$pending = $pending == "true";
$delivery = $delivery == "true";
$collection = $collection == "true";
$pendingList = false; //$pendingList == "true";
if($pendingList) $delivery = $pendingList;
	while($customer = mysqli_fetch_object($customers)){
		// print "<div>$customer->id</div>";
		$con = "<div class='customer-item'>
			<table class='table table-bordered'>
				<thead>
					<tr>";
				if($delivery || $collection){
					$con .= "<th colspan='5'>TOTAL CONFIRMED ORDERED LIST</th>";
				} else{
					$con .= "<th>1</th><th colspan='4'><a class='customer-link' href='customer/details/$customer->id'>$customer->company ($customer->city) - <strong style='font-size:large'>$customer->code</strong></a></th>";
				}
				$con .= "</tr>
				</thead>
				<tbody>";
					
					$cat = "";
					$filter = "i.customer_id=$customer->id ";
					if($pending){
						if($filter){
							$filter .= " AND ";
						}
						// var_dump($pending);
						//IFNULL(ii.delivery_date, i.invoice_date) 
						$filter .= " ii.delivered = 0 AND IFNULL(ii.delivery_date,i.invoice_date) < curdate()";
					} else{
						if($filter){
							$filter .= " AND ";
						}
						$filter .= "IFNULL(ii.delivery_date,i.invoice_date) = curdate()";

					}
					if($delivery || $collection){
						$filter = "";
						$filter .= "IFNULL(ii.delivery_date,i.invoice_date) = curdate()";
					}

					
					if(nn($cities)){
						if($filter){
							$filter .= " AND ";
						}
						$filter .= " city.id IN ($cities)";
					}

					
					if(nn($products)){
						if($filter){
							$filter .= " AND ";
						}
						$filter .= " ii.product_id IN ($products)";
					}
					$stField = 'stock';
					// if($delivery) $stField = 'stockCurrent';
					// if($pendingList) $stField = 'stockPending';
					$pq = "SELECT $stField(pv.id) stock, p.name, pc.name pc_name, ii.id iid, p.id pid, p.name, pv.id vid, pv.particulars, SUM(ii.quantity) quantity, 
						ii.delivered delivered, ii.price, IFNULL(ii.delivery_date,i.invoice_date) dd FROM invoice i 
						INNER JOIN invoice_item ii ON i.id=ii.invoice_id
						INNER JOIN customer c ON c.id=i.customer_id
						LEFT JOIN city ON c.city=city.name
						INNER JOIN `product_variance` pv ON pv.id=ii.product_variance_id 
						INNER JOIN product p ON p.id=pv.product_id AND ii.product_id=p.id
						inner join product_category pc ON p.product_category_id=pc.id
						".($filter ? "WHERE" : "")." $filter
						GROUP BY pc.name, ii.product_variance_id
					";
					// if($customer->id == 3) print $pq;
					$items = select($pq);

					if($items->num_rows > 0) print $con;

					$ci = 1;
					// vd($itmes);
					while($i = mysqli_fetch_object($items)){
						if($i->pc_name != $cat){
							print "<tr><th colspan='2'>$i->pc_name</th>";
							if($delivery){
								print "<th>Avail</th><th>Ordered</th><th>Shortage</th></tr>";
							} elseif($collection){
								print "<th>Avail</th><th>Ordered</th><th>Shortage</th><th>Collected</th><th>Collection</th></tr>";
							} else{
								print "<th>Price</th><th>Select</th></tr>";
							}
							$cat = $i->pc_name;
						}
						$i->quantity = $i->quantity - $i->delivered;
						if($i->quantity > 0){
							print "<tr><td><a href='#' id='invoice-item-date-$i->iid' data-dd='".df($i->dd)."' onClick='setDate(this, $i->iid)'>$ci</a></td>
							<td>$i->particulars X <a data-id='$i->iid' id='invoice-item-$i->iid' href='#' data-bs-toggle='modal' onClick='setItemId($i->iid)' data-bs-target='#modal-modify-quantity'>$i->quantity</a></td>";
							if($delivery){
								$collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND DATE(created_at)=CURDATE()");
								print "<td class='text-center avail' title='$i->stock + $i->quantity:'>".($i->stock)."</td>";
								print "<td class='text-center ordered' title='$i->delivered'>$i->quantity</td>";
								print "<td class='text-center shortage' title='$i->particulars'>".abs($i->stock > 0 ? 0 : $i->stock)."</td>";
							} elseif($collection){
								$collected = getSum("stock_collect_item", "quantity", "product_variance_id=$i->vid AND DATE(created_at)=CURDATE()");
								print "<td class='text-center'>".($i->stock > 0 ? $i->stock + $i->quantity: 0)."</td>";
								print "<td class='text-center' title='$i->delivered'>$i->quantity</td>";
								print "<td class='text-center' title='$i->particulars'>".abs($i->stock > 0 ? 0 : $i->stock)."</td>";
								print "<td class='text-center'>$collected</td>";
								print "<td class='text-center' title='$i->particulars'><input type='number' class='form-control' style='width:60px; padding: 0px 3px' name='variance[$i->vid]' min='0' max='".($i->quantity - $collected)."'></td>";
							} else{
								print "<td>".nf($i->price * $i->quantity)."</td> <td><input type='checkbox' name='iid[{$i->iid}]' value='$i->quantity'> </td>";
							}
							print "</tr>";
						}
						$ci++;
					}
					// <tr><td>1</td><td>SeaMaster 1500ml X 12</td><td>180</td></tr>
					// <tr><td>2</td><td>100 Plus 250ml Ctn X 24</td><td>28</td></tr>
					// <tr><td>3</td><td>Coca Cola 250ml X 24</td><td>120</td></tr>
					// <tr><th colspan='3'>Fresh</th></tr>
					// <tr><td>4</td><td>Red Bull 250ml X 24</td><td>40</td></tr>
					// <tr><td>5</td><td>Pepsi 250ml X 24</td><td>26</td></tr>
					// <tr><td>6</td><td>100 Plus 250ml Ctn X 24</td><td>18</td></tr>
					// <tr><td>7</td><td>Coca Cola 250ml X 24</td><td>28</td></tr>
					// <tr><th colspan='3'>Frozen</th></tr>
					// <tr><td>8</td><td>Red Bull 250ml X 24</td><td>26</td></tr>
					// <tr><td>9</td><td>Pepsi 250ml X 24</td><td>18</td></tr>
					// <tr><td>10</td><td>100 Plus 250ml Ctn X 24</td><td>28</td></tr>
				print "</tbody>
			</table>
		</div>";
		if($delivery || $collection) break;
	}
?>
</div>
<div style='position: fixed; right: 20px; bottom: 20px; padding-left: 30px; padding-right: 30px'>
	<table><tr>
	<?php if($collection) print "<td><button class='btn btn-warning' type='submit' name='collect'>Collect</button></td>"; ?>
	<td>
<?php
								print "<select class='supplier-select form-control' name='delivery_staff' style='width:150px' required>
	                    <option value=''>Please select</option>";

	                    $objs = select('distinct name, incentive', 'staff_salary', "category='Delivery Staff'");
	                    while ($man = mysqli_fetch_object($objs)) {
	                      print "<option ";
	                      // if($man->name == $sm) print "selected";
	                      print ">$man->name</option>";
	                    }
	                print "</select>";
							?>
							</td>
	<?php if(!$collection) print "<td><button class='btn btn-success' type='submit' name='deliver'>Deliver</button></td>"; ?>
					</tr></table>
</div>
</form>