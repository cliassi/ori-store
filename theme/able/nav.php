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
</style>




<!-- <a href='/store/report/cash' style='position: absolute; left: 125px; top: 121px; font-weight: bold;'>Petty Cash</a> -->
<?php
$menu_content = "";
$menus = [
  [
    'name'=>'Store',
    'children'=>[
      'dashboard'=>'Dashboard', 
      'report/daily'=>'Daily Sales Collection', 
      'report/cash'=>'Petty Cash Statement', 
      'report/order'=>'Daily Order', 
      'report/purchase'=>'Daily Purchase', 
      // 'report/due'=>'Customer Due Reports',
    ]
  ],  
  // [
  //   'name'=>'&nbsp;',
  //   'children'=>[
  //     'a'=>''
  //   ]
  // ],
  [
    'name'=>'Customer',
    'children'=>[
      'customer'=>'Customer +', 
    ],
  ],
  [
    'name'=>'Delivery Process',
    'children'=>[
      'dcollect'=>'Order Detail Report',
      'report/stock'=>'Collection+',
      'delivery?s=all'=>'Return+ Report',
      // 'customer'=>'Deliver',
      // 'delivery'=>'Return',
      'report/pending_delivery'=>'Pending Delivery Report',
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
    'name'=>'Employee',
    'children'=>[
      // 'staff'=>'Staff +',
      'salary'=>'Staff Salary',
      // 'Purchase'=>'Purchase Persons ',
      // 'Order'=>'Order Person ',
      // 'salesman'=>'Sales Persons +',
      // 'Collection'=>'Collection Persons ',
      'incentive'=>'Incentive Staff',
      'delivery'=>[
        'name'=>'Staff Incentive Statement',
        'children'=>[
          'salesman'=>'Sales Persons +',
        ]
      ],
      // 'Approve'=>'Approve',
    ]
  ],
  [
    'name'=>'Product',
    'children'=>[
      'product'=>'Main Product', 
      'product/details#product-0'=>'Product +', 
      'product/details#product-1'=>'Pricing',
      'Product'=>'Product Aging Report',
      'report/stock'=>'Product Stock Report',
      'order/damage'=>'Damage/Loss',
      'report/damage'=>'Damage/Loss Report',
      'Packing'=>'Packing',
    ],
  ],
  [
    'name'=>'Supplier',
    'children'=>[
      'supplier'=>'Supplier +',
      'Goods'=>'Goods Receive',
      'order/return?supplier=0'=>'Goods Return',
      'report/goods_return'=>'Goods Return Report',
    ]
  ],
  [
    'name'=>'Expense',
    'children'=>[
      'expense_account/carwash?company=1'=>'Expense', 
    ]
  ],
  [
    'name'=>'Warehouse',
    'children'=>[
      // 'report/warehouse'=>'Warehouse',
      'report/stockin'=>'Stock In',
      'report/stockout'=>'Stock Out',
      'report/stock'=>'Stock Report',
      'order/add'=>'Low-Stock',
    ]
  ],
  [
    'name'=>'User',
    'children'=>[
      'user'=>'Manage User',
      ''=>'',
      'auth?logout'=>'Logout',
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
    $menu_content .= "<li class='pc-item item-$key'>
    <label>{$menu['name']}</label>
    <svg class='pc-icon'>
    <use xlink:href='#custom-presentation-chart'></use>
    </svg>
    </li><li class='pc-item'>
    <a href='".ROOT."' class='pc-link'>
    <span class='pc-micon'>
    <svg class='pc-icon'>
    <use xlink:href='#custom-story'></use>
    </svg>
    </span>
    <span class='pc-mtext'></span>
    </a>
    </li>";
  }
}
print "<ul class='pc-navbar'>$menu_content</ul>";
?>
</div></div>



<!--   <a data-bs-toggle='modal' data-bs-target='#orderModal'>
    <span style="float: right; font-size: 3rem; margin-right: 50px; margin-top: -15px;"><i class='fas fa-shopping-cart'></i></span>
  </a>
 -->