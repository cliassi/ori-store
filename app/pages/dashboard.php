<br>
<br>
<style type="text/css">
	.font-bold{
		font-weight: 700;
	}

	.products-wrapper{
	}
	.product-wrapper{
		display: inline-block;
		padding: 15px;
		text-align: center;
		box-shadow: rgb(200, 200, 200) 2px 2px 4px;
		margin-top: 1px;
		margin-right: 15px;
		border-radius: 15px;
		width: 150px;
		height: 100px;
		background-size: contain !important;
		background-repeat: no-repeat !important;
		background-position: center !important;
	}
	.product-wrapper .fa-shopping-cart{
		position: absolute;
	}
	.product-wrapper img{
		height: 128px;
		width: auto;
	}
	.product-wrapper .dropdown-menu .dropdown-item{
		display: inline;
		padding: 3px;
		border-radius: 4px;
		border: solid 1px #ccc;
	}
	.product-wrapper .dropdown-menu .dropdown-item:hover{
		background: #ddd;
		color: #000;
		border: solid 1px #aaa;
	}
	button, a.btn{
		white-space: nowrap;
		padding: 5px !important;
		border-radius: 5px !important;
	}
	.product-wrapper .btn-group{
		position: absolute;
		margin-left: 30px;
		margin-top: -8px;
	}
	.product-wrapper .fa-shopping-cart{
		position: absolute;
		margin-left: 38px;
		margin-top: 50px;
		background: #fff;
		padding: 5px;
		border-radius: 5px;
	}
	span.stock {
    position: absolute;
    background: #ffffffee;
    padding: 0 3px;
    border-radius: 10px;
    /*display: inline-block;
    text-align: left;
    margin-top: 60px;
    margin-left: -71px;*/
}
#customer-table{
	font-size: 110%;
}
</style>
<div class="row">
	<!-- Zero config table start -->
	<div class="col-sm-12">
		<div class="card font-bold">
			<div class="card-body">
				<div class="dt-responsive table-responsive text-center">
					<div style="display: inline-block;vertical-align: top; padding: 15px; text-align: left;">
						<div>
							<a href="/store/report/stock" class="pc-link">
								<span class="pc-micon">
									<svg class="pc-icon">
										<use xlink:href="#custom-story"></use>
									</svg>
								</span>
								<span class="pc-mtext">Stock Report</span>
							</a>
						</div>
						<br>
						<br>
						<div>
							<a href="/store/order/add?supplier=0" class="pc-link">
								<span class="pc-micon">
									<svg class="pc-icon">
										<use xlink:href="#custom-story"></use>
									</svg>
								</span>
								<span class="pc-mtext">Re-order</span>
							</a>
						</div>
					</div>
					<div style="display: inline-block;">
						<table id="customer-table" class="table table-striped table-bordered nowrap">
							<tbody>
								<?php
								$filter1 = "1=1";
								$filter = "1=1";
								$filter3 = "";
								if(isset($get->mon)){
									$date = "{$get->mon}-01";
									$filter1 = "entry_time BETWEEN '$date' AND '".lastDate($date)."'";
									$filter = "created_at BETWEEN '$date' AND '".lastDate($date)."'";
									$filter3 = "AND oi.created_at BETWEEN '$date' AND '".lastDate($date)."'";
									// vd($filter);
								}
								$summary = mysqli_fetch_object(select("SELECT 
									(SELECT SUM(IFNULL(amount,0)) FROM `cw_cash` WHERE $filter1) add_cash, 
									(SELECT SUM(IFNULL(amount,0)) FROM `cw_cash` WHERE amount>0 AND $filter1) cash, 
									(SELECT SUM(IFNULL(amount,0)) FROM `cw_bank` WHERE $filter1) bank, 
									(SELECT SUM(IFNULL(amount,0)) FROM `cw_cash_withdraw` WHERE $filter1) withdraw, 
									(SELECT SUM(IFNULL(amount,0)) FROM `bd_handover` WHERE $filter) cash_handover, 
									(SELECT SUM(IFNULL(bank_amount,0)) FROM `bd_handover` WHERE $filter) bank_handover,
									(SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Cash' AND $filter) cash_payment, 
									(SELECT SUM(IFNULL(amount,0)) FROM `payment` WHERE payment_method='Bank' AND $filter) bank_payment,
									(SELECT SUM(IFNULL(amount,0)) FROM `collection` WHERE payment_method='Cash' AND $filter) cash_collection, 
									(SELECT SUM(IFNULL(amount,0)) FROM `collection` WHERE payment_method='Bank' AND $filter) bank_collection,
									(SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Cash' AND $filter1) cash_expense, 
									(SELECT SUM(IFNULL(amount,0)) FROM `expense_account_entry` WHERE payment_method='Online' AND $filter1) bank_expense"));
								$sreturn = mysqli_fetch_object(select("SELECT IFNULL(SUM(quantity*cost),0) amount FROM `goods_return_item` WHERE $filter"));
								$damage = mysqli_fetch_object(select("SELECT IFNULL(SUM(quantity*cost),0) amount FROM `damaged_item` WHERE $filter"));
								$sdue = mysqli_fetch_object(select("SELECT (SELECT SUM(quantity*cost) FROM `order_item` WHERE $filter) - (SELECT IFNULL(SUM(amount),0) FROM `payment` WHERE $filter) amount"));
								$due = mysqli_fetch_object(select("SELECT (SELECT SUM(quantity*price) FROM `invoice_item` WHERE $filter) - (SELECT IFNULL(SUM(amount),0) FROM `collection` WHERE $filter) amount"));
								$profit = mysqli_fetch_object(select("SELECT SUM(quantity*(price-cost)) amount FROM `invoice_item` WHERE $filter"));
								$store_value = mysqli_fetch_object(select("
									SELECT IFNULL(SUM(stock(product_variance_id)*pv.cost),0) amount FROM `order_item` oi, `product_variance` pv WHERE oi.product_variance_id=pv.id $filter3"));
								if(!$store_value){
									$store_value = (object)['amount'=>0];
								}
								$petty_cash = $summary->add_cash - $summary->withdraw  + $summary->cash_collection - $summary->cash_payment - $summary->cash_expense;
								$bank = $summary->bank_collection - $summary->bank_expense - $summary->bank_payment;
								?>
								<tr><td><a href='/store/report/cash'>Petty Cash</a></td><td>:</td><td width="250px" title="<?php print "$summary->add_cash - $summary->withdraw  + $summary->cash_collection - $summary->cash_payment - $summary->cash_expense"; ?>"><?php print nf($petty_cash); ?></td></tr>
								<tr><td>Invested Cash Capital</td><td>:</td><td><?php print nf($summary->cash); ?></td></tr>
								<tr><td>Bank</td><td>:</td><td><?php print nf($bank + $summary->bank); ?></td></tr>
								<tr><td><a href='/store/report/due'>Customer Due</a></td><td>:</td><td><?php print nf($due->amount); ?></td></tr>
								<tr><td><a href='/store/report/sdue'>Supplier Due</a></td><td>:</td><td><?php print nf($sdue->amount - $sreturn->amount); ?></td></tr>
								<tr><td><a href='/store/report/sdue'>Goods Return</a></td><td>:</td><td><?php print nf($sreturn->amount); ?></td></tr>
							</tbody>
						</table>
					</div>

					<div style="display: inline-block; margin-left: 50px; vertical-align: top;">
						<table id="customer-table" class="table table-striped table-bordered nowrap">
							<tbody>
								
								<tr><td>Present Capital	</td><td>:</td><td title="<?php print "Petty Cash: ".nf($petty_cash).", Bank: ".nf($bank + $summary->bank ).", Customer Due: ".nf($due->amount).", Store Net Product Value: ".nf($store_value->amount).", Supplier Due: ".nf($sdue->amount).""; ?>"><?php print nf($petty_cash + $due->amount + $bank + $store_value->amount - $sdue->amount - $sreturn->amount + $summary->bank - $damage->amount); ?></td></tr>
								<!-- <tr><td>Present Capital	</td><td>:</td><td><?php print nf($petty_cash + $due->amount - $damage->amount + $bank + $store_value->amount - $sdue->amount); ?></td></tr> -->
								<tr><td>Store Stock in Items Value</td><td>:</td><td width="250px"><?php print nf($store_value->amount - $damage->amount - $sreturn->amount); ?></td></tr>
								<tr><td>Store Net Product Value</td><td>:</td><td width="250px"><?php print nf($store_value->amount - $damage->amount - $sdue->amount - $sreturn->amount); ?></td></tr>
								<tr><td>Expense</td><td>:</td><td><?php print nf($summary->cash_expense + $summary->bank_expense); ?></td></tr>
								<tr><td>Damage/Loss</td><td>:</td><td><?php print nf($damage->amount); ?></td></tr>
								<tr><td>Profit & Loss	</td><td>:</td><td><span style="font-weight: 300">(<?php print nf($profit->amount); ?>)</span> <?php print nf($profit->amount - ($summary->cash_expense + $summary->bank_expense + $damage->amount)); 
								
								// print nf($petty_cash + $due->amount + $store_value->amount - $sdue->amount - $summary->add_cash - $damage->amount - $summary->cash_handover);

								/*vd([[
								'petty_cash'=>$petty_cash, 'c_due'=> $due->amount, 'store_value'=>$store_value->amount], 
									['s_due'=>$sdue->amount,'add_cash'=> $summary->add_cash,'damage'=> $damage->amount ,'cash_handover'=> $summary->cash_handover
							]]);*/
								//print nf($petty_cash + $due->amount + $bank - $summary->add_cash + $store_value->amount - $sdue->amount); 
							?></td></tr>
							</tbody>
						</table>
					</div>
					<div class='center'>
						<form><?php print monthSelector('mon', isset($get->mon)?$get->mon:''); ?> <button class='btn btn-info'>Show</button>
					</div>

					<hr>
				</div>
			</div>
			<?php require_once('lowstock.php'); ?>
		</div>
	</div>

	<div class="modal fade modal-lightbox" id="lightboxModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				<div class="modal-body">
					<img src="../assets/images/light-box/l1.jpg" alt="images" class="modal-image img-fluid" />
				</div>
			</div>
		</div>
	</div>
	<script>
		$(document).ready(function() {
			$(".product-wrapper").click(function(){
				const t = $(this).find('button');
				let qty = parseInt(t.text());
				qty = qty+1;
				t.text(qty)
			});

			$('.product-wrapper button').on('click', function(event) {
		        event.stopPropagation(); // Prevents the click from bubbling up to the div
		    });
			function setProduct(i) {
				$("#product_id").val(i);
			}
			$(".qty").click(function(){
				const t = $(this).parent().parent().find("button");
				if($(this).text() == "0"){
					t.text(-1);
				} else{
					let qty = parseInt(t.text()) + parseInt($(this).text()) - 1;
					t.text(qty);
				}
			});
			$(".product-wrapper .fa-shopping-cart").click(function(){
				location.href = '/store/order/add?v=' + $(this).data('product') + '&qty=' + $(this).parent().find('button.cart-item').text();
			});
		});

	</script>
