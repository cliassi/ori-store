<style type="text/css">
	.dragged {
	position: absolute;
	opacity: 0.4;
	z-index: 2000;
}
ol{
	background: #efefef;
	border-radius: 4px;
	padding: 4px;
	margin: 0 20px;
	min-height: 100px;
	min-width: 40px;
}
ol.example li.placeholder {
	position: relative;
}
ol.example li.placeholder:before {
	position: absolute;
}
ol{
	display: inline-block;
	vertical-align: top;
}
.editable{
	background: rgba(232,244,232,.4);
}
input[type=checkbox]{
	display: none;
}
tr:hover{background: #89CFF0CC !important;}
tr td a{color: #000; }
tr.depth-1 td.padded{padding-left: 18px !important; font-size: 1.05rem; font-weight: 700; text-decoration: underline;}
tr.depth-2 td.padded{padding-left: 38px !important; font-size: 1rem; font-weight: 600;}
tr.depth-3 td.padded{padding-left: 58px !important; font-size: 0.95rem; font-weight: 500;}
tr.depth-4 td.padded{padding-left: 77px !important; font-size: 0.9rem; font-weight: 400;}
tr.depth-5 td.padded{padding-left: 98px !important; font-size: 0.85rem}
tr.depth-6 td.padded{padding-left: 118px !important; font-size: 0.8rem}
tr.Credit td{
	background-color: #5cb85c55 !important;
}
.branc{
	position: absolute;
  height: 49px;
  border-left: solid 2px #999;
  border-top: solid 2px #999;
  width: 20px;
  margin-top: 9px;
  border-bottom: solid 2px #999;
}
tr.depth-1 .branch{left: 100px;}
tr.depth-2 .branch{left: 120px;}
tr.depth-3 .branch{left: 140px;}
tr.depth-4 .branch{left: 160px;}
tr.depth-5 .branch{left: 180px;}
tr.depth-6 .branch{left: 200px;}
</style>
<script src='<?php print $appurl ;?>/assets/jquery-sortable.js'></script>
<?php
if(isset($get->up)){
	$item1 = R::load("expense_account", $get->up);
	if(nn($item1->parent)){
		$prev = getMaxA("expense_account", "sortorder", "sortorder<'$item1->sortorder' AND parent=$item1->parent");
	} else{
		$prev = getMaxA("expense_account", "sortorder", "sortorder<'$item1->sortorder' AND parent IS NULL");
	}
	// vd($prev);
	if(nn($prev)){
		$item2 = R::findOne("expense_account", "sortorder=?", [$prev]);
		$item1_so = $item1->sortorder;
		$item2_so = $item2->sortorder;
		$item1->sortorder = $item2_so;
		$item2->sortorder = $item1_so;
		// vd($item1);
		// vd($item2);
		R::store($item1);
		R::store($item2);
	}
	// dd(0);
	redir("?");
}
if(isset($get->down)){
	$item1 = R::load("expense_account", $get->down);
	if(nn($item1->parent)){
		$prev = getMinA("expense_account", "sortorder", "sortorder>'$item1->sortorder' AND parent=$item1->parent");
	} else{
		$prev = getMinA("expense_account", "sortorder", "sortorder>'$item1->sortorder' AND parent IS NULL");
	}
	// dd($prev);
	if(nn($prev)){
		$item2 = R::findOne("expense_account", "sortorder=?", [$prev]);
		$item1_so = $item1->sortorder;
		$item2_so = $item2->sortorder;
		$item1->sortorder = $item2_so;
		$item2->sortorder = $item1_so;
		R::store($item1);
		R::store($item2);
	}
	redir("?");
}
	//$page = is("page", 1, "", FALSE);
	$page = is("page", 1);
	$offset = 1000;

	$month = isset($get->month)?$get->month:date("Y-m", time());

	print "<div><a class='frht btn btn-danger' href='/store/expense_account_entry/view'>History</a></div>";
	     
  $filter = "";
  openFilterForm("get");
	  print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
	  if(isset($get->name) && nn($get->name)){
	  	joinFilter($filter,"name like '%$get->name%'");
	  }
	  else{
	  	$get->name = '';
	  }
	  print str("Name")." <input type='text' name='name' id='filterkey' autofocus value='$get->name' class='form-control form-control-fluid w150' /> ";
	  $parent = isset($get->parent) ? $get->parent : 1;
	  $get->parent = $parent;
	  print "<input type='hidden' name='parent' value='$parent'>";
		print "Month ".monthSelector("month", "$month-01");
		// print str("Parent")." ".sop2('parent', $parent, ['optional'=>true, 'filter'=>'id<3'], 'expense_account');		
		// if($parent == 1){
		// 	print "<a class='btn btn-primary' href='?page=2&name=&d=$d&t=$t&month=$month&parent=2'>Outsource</a>";
		// }	else{
		// 	print "<a class='btn btn-warning' href='?page=2&name=&d=$d&t=$t&month=$month&parent=1'>Hotel</a>";
		// }
		print space(5);		
  closeFilterForm();
	$userlist = userList();
	
	// $nor = num_rows("a.*", "expense_account a", "$filter");
	// $nop = ceil($nor/$offset);

	
  $parentList = toA("expense_account");
    	
	print "<hr>";
	print "<table align='center' class='table table-responsive table-striped'>
	<thead><tr><th></th><th>#</th><th>Name</th><th class='text-right'>Income</th><th class='text-right'>Expense</th><th class='text-right'>Balance</th><th></th></tr></thead>
	    <tbody class='sortable'>";

	$i = 1; //$start + 1;
			// <td class='padded'>{$parentList[$expense_account->parent]}</td>
	
	$entryFilter = "(`month`='$month' OR expense_date LIKE '$month-%') AND ";
	printExpenseAccount($filter, $i);
	
	print "</tbody>
	<tfoot>";
	// print paging(5, $nop, $nor, $page);
	print "</tfoot>
	</table>";


function printExpenseAccount($_filter, $i, $parent = ''){
	global $entryFilter;
	global $get;
	$month = isset($get->month)?$get->month:date("Y-m", time());

	$filter = $_filter;
	if($parent){
		$filter .= "path LIKE '/$get->parent/%' AND parent=$parent ";
	} else{
		$filter .= "path LIKE '/$get->parent/%' AND parent IS NULL ";
	}
			// (SELECT IFNULL(SUM(IF(tran_type='Credit', amount, 0)),0) FROM `expense_account_entry` WHERE $entryFilter accountpath LIKE CONCAT(a.path,'%')) income, 
	$expense_accounts = select("a.*, 
			(SELECT IFNULL(SUM(IF(tran_type='Credit', amount, 0)),0) FROM `expense_account_entry` WHERE entry_time LIKE '$month-%' AND accountpath LIKE CONCAT(a.path,'%')) income, 
			(SELECT IFNULL(SUM(IF(tran_type='Debit', amount, 0)),0) FROM `expense_account_entry` WHERE $entryFilter accountpath LIKE CONCAT(a.path,'%')) expense", "expense_account a", "$filter", "order by sortorder");
	// exit;
	while($expense_account = mysqli_fetch_object($expense_accounts)){
		// $depth = substr_count($expense_account->path, "/") - 2;
		print "<tr class='$expense_account->type depth-$expense_account->depth' data-breadcrumbs='$expense_account->breadcrumbs'>
			<td style='line-height: 1; width:30px'><a href='?up=$expense_account->id'><i class='fa fa-chevron-up'></i></a><a href='?down=$expense_account->id'><i class='fa fa-chevron-down'></i></a></td>
			<td><a href='view/$expense_account->id'>$i</a></td>
			<td class='padded'>
				<div class='branch'></div>
				<a href='/store/expense_account_entry/view?accountid=$expense_account->id'>$expense_account->name</a><span class='hidden'>$expense_account->breadcrumbs</span>";
			if(strpos($expense_account->path, "/2/") === FALSE || strpos($expense_account->path, '/2/358/') !== FALSE){
				print "<a href='/store/expense_account_entry/add?a=$expense_account->id' class='frht btn btn-warning btn-sm' style='padding: 5px 20px; margin-left:15px'><i class='fas fa-money-bill'></i></a>";
			}
			print "<a href='add?parent=$expense_account->id' class='frht btn btn-info btn-sm' style='padding: 5px 20px;'><i class='fa fa-plus-circle'></i></a>";
			print "</td>";
			// <td class='padded'>$expense_account->breadcrumbs";
			// print "";
			// print "</td>";"
	    print "<td class='rht'>".nfz($expense_account->income)."</td>
	    <td class='rht'>".nfz($expense_account->expense)."</td>
	    <td class='rht'>".nfz($expense_account->income - $expense_account->expense)."</td>";
	    print "<td><a href='remove/$expense_account->id'><i class='fas fa-trash'></i></a></td>";
			// <td>".options2("", $expense_account->id, array("edit", "remove","erase"))."</td>
			print "</tr>";
		$i++;
		$i = printExpenseAccount($_filter, $i, $expense_account->id);
	}
	return $i;
}

?>

<!-- ol class="sortable">
	<li>1</li>
	<li>2</li>
	<li>3</li>
	<li>4</li>
</ol> -->

<script type="text/javascript">
	// $(".sortable").sortable({
	//   group: 'sortable'
	// });

	$("#filterkey").keyup(function(){
		var key = $(this).val();
		$('tr').hide();
		$('tr').filter(function () {
        return this.innerHTML.toLowerCase().includes(key.toLowerCase());
    }).show()
		console.log(key);
	});
</script>