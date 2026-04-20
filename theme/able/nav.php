<style type="text/css">
  ul{
    z-index: 99;
  }
  /*.pc-navbar .pc-hasmenu:nth-child(2){
    opacity: 0;
  }*/
  #incentive-staffs:hover .incentive-staffs{
    display: block !important;
    position: absolute;
    margin-left: 220px;
    background: #fff;
    border-radius: 3px;
    padding: 10px;
    margin-top: -55px;
    width: 200px;
  }
  .incentive-staffs{
    display: none !important;
  }
  nav a{
    text-decoration: none;
    color: #333 !important;
  }
  nav .pc-hasmenu ul a:hover{
    background-color: rgba(70, 128, 255,.1);
    border-radius: 5px;
    background-clip border-box;
  }
</style>




<!-- <a href='/store/report/cash' style='position: absolute; left: 125px; top: 121px; font-weight: bold;'>Petty Cash</a> -->
<?php
$accs = ['bank'=>'Bank Accounts'];

$banks = R::find('bank');
foreach ($banks as $key => $b) {
  $accs['bank_transaction/history/'.$b->id] = $b->name;
}

$menu_content = "";
$menus = [
  [
    'name'=>'Store',
    'children'=>[
      'dashboard'=>'Dashboard', 
      'customer'=>'Customer +', 
      // 'area'=>'Area +', 
      'report/daily'=>'Daily Sales Collection', 
      'report/cash'=>'Petty Cash Statement', 
      'report/order'=>'Daily Order', 
      'report/purchase'=>'Daily Purchase', 
      'supplier'=>'Supplier +',
      'company'=>'File Manager',
      'order/return?supplier=0'=>'Supplier Goods Return',
      'report/goods_return'=>'Supplier Return Report',
      // 'report/due'=>'Customer Due Reports',
    ]
  ],  
  // [
  //   'name'=>'&nbsp;',
  //   'children'=>[
  //     'a'=>''
  //   ]
  // ],
  // [
  //   'name'=>'Customer',
  //   'children'=>[
  //     'customer'=>'Customer +', 
  //     'area'=>'Area +', 
  //   ],
  // ],
  // [
  //   'name' => 'Orders <i class="fas fa-bell"></i>',
  //   'children'=>[
  //     'customer_order'=>'Customer Order',
  //     'customer_confirm_order'=>'Pending Delivery',
  //   ],
  // ],
  [
    
    'name'=>'Order n Delivery',
    'children'=>[
      'dcollect_order'=>'Order List',
      'dcollect_collect'=>'Pending Collect',
      // 'dcollect_delivery'=>'Delivery Status',
      'dcollect_delivery_status'=>'Delivery Status', 
      'dcollect_pending'=>'Pending Order',
      'cnr'=>'C n R Report',
      // 'delivery2'=>'Report',
      // 'dcollect'=>'Order,Pending, Delivery',
      // 'customer'=>'Deliver',
      // 'delivery'=>'Return',
      // 'report/pending_delivery'=>'Pending Delivery Report',
      /*
      'report/order'=>'Order',
      'dcollect'=>'Collect',
      'customer'=>'Deliver',
      'delivery'=>'Return',
      'dreport'=>'Report',
      */
    ]
  ],  
  [
    'name'=>'Product',
    'children'=>[
      'product'=>'Main Product', 
      'product/details#product-0'=>'Product +', 
      'product/details#product-1'=>'Pricing',
      // 'Product'=>'Product Aging Report',
      'report/stock'=>'Product Stock Report',
      'order/damage'=>'Damage/Loss',
      'report/damage'=>'Damage/Loss Report',
      'Packing'=>'Packing',
      'product_category'=>'Product Category',
    ],
  ],
  [  
    'name'=>'Employee',
    'children'=>[
      // 'staff'=>'Staff +',
      'salary'=>'Staff Salary',
      // 'Purchase'=>'Purchase Persons ',
      // 'Order'=>'Order Person ',
      // 'salesman'=>'Sales Persons +',
      // 'Collection'=>'Collection Persons ',
      'incentive'=>'Sales Staff',
      'store_staff'=>'Store Staff',
      // 'delivery'=> 'Delivery Staff Incentive',
      'incentive_d'=> 'Delivery Staff',
      'lorry' => 'Lorry',
      // 'Approve'=>'Approve',
    ]
  ],
  // [
  //   'name'=>'Supplier',
  //   'children'=>[
  //     'supplier'=>'Supplier +',
  //     'Goods'=>'Goods Receive',
  //     'order/return?supplier=0'=>'Supplier Goods Return',
  //     'report/goods_return'=>'Supplier Return Report',
  //   ]
  // ],
  [
    'name'=>'Acc',
    'children'=> $accs
  ],
  // [
  //   'name'=>'Expense',
  //   'children'=>[
  //     'expense_account/carwash?company=1'=>'Expense', 
  //   ]
  // ],
  [
    'name'=>'Low-Stock',
    'children'=>[
      'lowstock'=>'Low-Stock'
    ]
  ],
  // [
  //   'name'=>'Warehouse',
  //   'children'=>[
  //     // 'report/warehouse'=>'Warehouse',
  //     'report/stockin'=>'Stock In',
  //     'report/stockout'=>'Stock Out',
  //     'report/stock'=>'Stock Report',
  //     'lowstock'=>'Low-Stock',
  //   ]
  // ],
  [
    'name'=>'User',
    'children'=>[
      'user/add'=>'Add User',
      'user'=>'List of User',
      'user/role'=>'List of Roles',
      'user/permission'=>'User Permission',
      'auth?logout'=>'Logout ('.username().')',
    ]
  ],   
  [
    'name'=>'Outlet',
    'children'=>[
      'report/outlet'=>'Outlet Account',
      'division_branch?tab=branches'=>'Branch',
      'division_branch/add_branch'=>'Add Branch',
      'division_branch?tab=districts'=>'District',
      'division_branch/add_district'=>'Add District',
      'division_branch?tab=divisions'=>'Division',
      'division_branch/add_division'=>'Add Division',
    ]
  ],                    
];
foreach ($menus as $key => $menu) {
  if(isset($menu['children'])){
    $menu_content .= "<li class='pc-item pc-caption item-$key'>
    <label>{$menu['name']}</label>
    <svg class='pc-icon'>
      <use xlink:href='#custom-presentation-chart'></use>
    </svg>
    </li>";
    foreach ($menu['children'] as $key => $p) {
      if(isset($p['children'])){
        $menu_content .= "<li class='pc-item item-$key' id='incentive-staffs'>
        <a href='".ROOT."/$key' class='pc-link'>
        <span class='pc-micon'>
        <svg class='pc-icon'>
        <use xlink:href='#custom-story'></use>
        </svg>
        </span>
        <span class='pc-mtext'>{$p['name']}</span>
        </a>
        <div class='incentive-staffs'>";
        $objs = select('distinct name, incentive', 'staff_salary', "category='Marketing'");
        while ($ob = mysqli_fetch_object($objs)) {
          $menu_content .= "<div><a href='".ROOT."/incentive?s=$ob->name'>$ob->name</a></div><dr>";
        }
        $menu_content .= "</div>
        </li>
        ";

      } else{
        $menu_content .= "<li class='pc-item item-$key'>
        <a href='".ROOT."/$key' class='pc-link'>
        <span class='pc-micon'>
        <svg class='pc-icon'>
        <use xlink:href='#custom-story'></use>
        </svg>
        </span>
        <span class='pc-mtext'>$p</span>
        </a>
        </li>";
      }
    }
  } else{
    // Handle menu items without children - check if it's a direct link or label
    reset($menu);
    $link = key($menu);
    $label = current($menu);
    if($link !== 'name' && $link !== 'children' && !is_null($link)){
      // Direct link format: ['link'=>'Label']
      $menu_content .= "<li class='pc-item pc-caption item-$link'>
      <a href='".ROOT."/$link' class='pc-link'>
      <span class='pc-micon'>
      <svg class='pc-icon'>
      <use xlink:href='#custom-story'></use>
      </svg>
      </span>
      <span class='pc-mtext'>$label</span>
      </a>
      </li>";
    } else{
      // Fallback for items with 'name' key but no children
      $menu_content .= "<li class='pc-item pc-caption item-$key'>
      <label>{$menu['name']}</label>
      <svg class='pc-icon'>
      <use xlink:href='#custom-presentation-chart'></use>
      </svg>
      </li>";
    }
  }
}
print "<ul class='pc-navbar'>$menu_content</ul>";
$branches = R::find('branch', '1=1 ORDER BY name');
?>
<span><input type='text' name='key' id='search-key' class='form-control' placeholder="Search..." style="width: 135px; display: inline-block;"></span>
<select id='branch-selector' class='form-control' style='width: 150px; display: inline-block; margin-left: 5px;'>
  <option value=''>Switch Branch</option>
  <?php foreach ($branches as $branch): ?>
    <option value='<?php echo $branch->id; ?>' <?php echo (isset($_SESSION['branch_id']) && $_SESSION['branch_id'] == $branch->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch->name); ?></option>
  <?php endforeach; ?>
</select>
</div>
</div>


<script type="text/javascript">
  $("#search-key").keyup(function(){
    const key = $("#search-key").val().trim();
    if(key.length > 1){
      $.post('/store/ajax/search_customer.php', { key: key })
      .done((response) => {
        $("#search-result-wrapper").html(response);
        $("#content-wrapper").hide();
      });
    } else{
      $("#content-wrapper").show();
      $("#search-result-wrapper").html('');
    }
  });

  $("#branch-selector").change(function(){
    const branchId = $(this).val();
    if(branchId) {
      $.ajax({
        type: 'POST',
        url: '/store/ajax/switch_branch.php',
        data: { branch_id: branchId },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataType: 'json',
        success: function(response) {
          if(response.success) {
            window.location.reload();
          }
        }
      });
    }
  });
</script>

<!--   <a data-bs-toggle='modal' data-bs-target='#orderModal'>
    <span style="float: right; font-size: 3rem; margin-right: 50px; margin-top: -15px;"><i class='fas fa-shopping-cart'></i></span>
  </a>
 -->