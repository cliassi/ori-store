<style type="text/css">
    .product-display .card{
      flex-direction: row;
      justify-content: center;
    }
    .products-wrapper{
      display: flex;
      white-space: wrap;
      overflow-x: auto;
      background: #fefefe;
      border: solid 2px #fafafa;
      border-radius: 5px;
      margin-bottom: 15px;
      box-shadow: 0 0 5px #ddd;
      flex-wrap: wrap;
    }
    .products-wrapper table{
      font-size: .75rem
    }
    .qty-wrapper{
      position: absolute;
      right:81px;
      height: 40px;
    }
    .ol .qty-wrapper{
    	right: 0px;
    }
    .qty-wrapper select{
      border: solid 1px #eee;
    }
    .qty-wrapper select::-ms-expand {
      -webkit-appearance: none;
      -moz-appearance: none;
      text-indent: 1px;
      text-overflow: '';
    }
    .products-wrapper option{
      text-align: center;
    }
    .products-wrapper .ol td{
      height: 43px;
    }
    .products-wrapper .op td{
      width: 47px;
    }
    .products-wrapper .ol table{
      width: 340px;
    }
    .products-wrapper .op table{
      width: 80px;
    }
    .products-wrapper table td{
      border: solid 1px #ccc;
      overflow: hidden;
      white-space: nowrap;
      text-align: center;
      vertical-align: middle;
      font-weight: 700;
      font-size: .95rem;
    }
    .products-wrapper .ol, .products-wrapper .op{
      border: solid 1px #aaaaff;
      padding: 1px;
      overflow: hidden;
      flex: 0 0 340px;
      position: relative;
    }
    .products-wrapper .ol{
      width: 340px;
      margin-right: 10px;
    }
    .products-wrapper .op{
      display: flex;
      width: 384px;
      margin-right: 10px;
      white-space: nowrap;
    }
    .products-wrapper .ol div.img{
      object-fit: cover;
      display: inline-block;
      height: 320px;
      width: 340px;
      background-color: rgba(0,0,50,.5);
    }
    .products-wrapper .op div.img{
      object-fit: cover;
      display: inline-block;
      height: 360px;
      width: 300px;
      background-color: rgba(0,0,50,.5);
    }
    .products-wrapper div.img img{
      width: 100%;
      height: 100%;
    }
    .inline-block{
      display: inline-block;
      width: 80px;
    }
    .rotate{
      height: 280px;
    }
    .rotate span{
      transform: rotate(-90deg);
      display: inline-block;
      position: absolute;
      margin-left: -22px;
      width: 30px;
    }
    small{
      font-weight: .4rem;
      padding-right: 3px;
    }
    .unit{
      color: blue;
      font-weight: 700;
    }
    .price{
      color: red;
      font-weight: 700;
      height: 40px;
    }
  </style>
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
			if($var->image_orientation == 'L'){
        print "<div class='ol' title='$var->id, SI: {$var->si} SO: {$var->so} MS: {$var->min_stock}'>";
        print "<span class='stock'>".($var->min_stock)."=>".($var->si - $var->so)."</span>";
        print "<div class='qty-wrapper'><select class='cart-item' data-product='$var->id'>";
        for($counter = 0; $counter<=500; $counter+=50) { print "<option class='dropdown-item qty qty-$counter' value='$counter'>".($counter>0?'+':'')."$counter</option>"; }
        print "</select></div>";
        print "<div class='img'><img src='".ROOT."/{$var->image}' height='64px'></div>
          <table><tr><td width='40%' class='unit'>$var->size x $var->unit</td><td width='35%'>$var->particulars</td><td width='25%' class='price'><small>RM</small>$var->price</td></tr></table>
        </div>";
      } else{
        print "<div class='op' title='$var->id, SI: {$var->si} SO: {$var->so} MS: {$var->min_stock}'>";
        print "<span class='stock'>".($var->min_stock)."=>".($var->si - $var->so)."</span>";
        print "<div class='qty-wrapper'><select class='cart-item' data-product='$var->id'>";
        for($counter = 0; $counter<=500; $counter+=50) { print "<option class='dropdown-item qty qty-$counter' value='$counter'>".($counter>0?'+':'')."$counter</option>"; }
        print "</select></div>";
        print "
          <div class='img'><img src='".ROOT."/{$var->image}' height='64px'></div>
          <div class='inline-block'>
            <table>
              <tr><td class='unit'>$var->size<br> x $var->unit</td></tr>
              <tr><td class='rotate'><span>$var->particulars</span></td></tr>
              <tr><td class='price'><small>RM</small>$var->price</td></tr>
            </table>
          </div>
        </div>";
      }
			/*print "<div class='product-wrapper' title='SI: {$var->si} SO: {$var->so} MS: {$var->min_stock}' style='background:url(".ROOT."/$var->image)'>";
			print "<span class='stock'>".($var->min_stock)."=>".($var->si - $var->so)."</span>";
			print '<div class="btn-group mb-2 me-2">
			<button class="btn btn-info dropdown-toggle cart-item" data-product="'.$var->id.'" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">0</button>
			<div class="dropdown-menu" style="">';
			for($counter = 0; $counter<=300; $counter+=50) { print "<a class='dropdown-item qty qty-$counter'>".($counter>0?'+':'')."$counter</a>"; }
				print '</div></div>';
			print "<img src='".ROOT."/$var->image' height='64px' class='hidden'>
			<i class='fas fa-shopping-cart' data-product='".$var->id."' data-qty='0'></i>
			</div>";
			*/
		}
		?>
	</div>
</div>
</div>