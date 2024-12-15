<div style="margin: 15px;">
				<a href="/store/report/stock" class="pc-link">
					<span class="pc-micon">
						<svg class="pc-icon">
							<use xlink:href="#custom-story"></use>
						</svg>
					</span>
					<span class="pc-mtext">Low Stock</span>
				</a>
				<br>
				<div class='products-wrapper'>
					<?php
					$items = select("SELECT * FROM (SELECT v.*, IFNULL((SELECT SUM(quantity) FROM order_item WHERE product_variance_id=v.id),0) si, IFNULL((SELECT SUM(quantity) FROM invoice_item WHERE product_variance_id=v.id),0) so FROM `product_variance` v) a WHERE si-so < min_stock ORDER BY product_id, particulars");
					while($var = mysqli_fetch_object($items)){
						print "<div class='product-wrapper' title='SI: {$var->si} SO: {$var->so} MS: {$var->min_stock}' style='background:url(".ROOT."/$var->image)'>";
						print "<span class='stock'>".($var->min_stock)."=>".($var->si - $var->so)."</span>";
						print '<div class="btn-group mb-2 me-2">
						<button class="btn btn-info dropdown-toggle cart-item" data-product="'.$var->id.'" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">0</button>
						<div class="dropdown-menu" style="">';
						for($counter = 0; $counter<=300; $counter+=50) { print "<a class='dropdown-item qty qty-$counter'>".($counter>0?'+':'')."$counter</a>"; }
							print '</div></div>';
						print "<img src='".ROOT."/$var->image' height='64px' class='hidden'>
						<i class='fas fa-shopping-cart' data-product='".$var->id."' data-qty='0'></i>
						</div>";
		                    /*print "<span style='display:inline-block; padding: 5px; border: solid 1px #ccc; background-color: #efefef; border-radius:5px; margin: 5px;'>
		                    	<a href='/store/report/stock/$var->id' target='_blank'>$var->particulars (".($var->si-$var->so).")</a>
		                    	</span>";*/
		                    }
		                    ?>
		                </div>
		            </div>
					<!-- <div>
						<a href="/store/report/stock" class="pc-link">
				      <span class="pc-micon">
				      <svg class="pc-icon">
				      <use xlink:href="#custom-story"></use>
				      </svg>
				      </span>
				      <span class="pc-mtext">Stock Report</span>
			      </a>
			  </div> -->
			</div>