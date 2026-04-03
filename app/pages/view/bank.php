
<?php

	    $filter = "trash=0";
	    openFilterForm("get");
	    print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
	    $name = isf("name", "name", $filter, $get);
	    print str("Name")." <input type='text' name='name' value='$name' class='form-control-fluid' /> ";
	    $account_name = isf("account_name", "account_name", $filter, $get);
	    print str("Account Name")." <input type='text' name='account_name' value='$account_name' class='form-control-fluid' /> ";
	    $account_number = isf("account_number", "account_number", $filter, $get);
	    print str("Account Number")." <input type='text' name='account_number' value='$account_number' class='form-control-fluid' /> ";
	    closeFilterForm();
	$userlist = userList();
	
	$banks = select("a.*", "bank a", "trash=0".(nn($filter)?" AND $filter":""));
	print "<hr>";
	print "<table align='center' class='table table-striped'>
	<thead><tr><th>#</th><th>".str("Bank Name")."</th><th>".str("Account Name")."</th><th>".str("Account Number")."</th><th>".str("Entry By")."</th><th>".str("Entry Time")."</th><th>".options2("", "", array("add"))."</th></tr></thead>
	    <tbody>";

	$i = 1;
	while($bank = mysqli_fetch_object($banks)){
		print "<tr><td><a href='view/$bank->id'>$i</a></td>
			<td>$bank->name</td>
			<td>$bank->account_name</td>
			<td>$bank->account_number</td>
			<td>{$userlist[$bank->entry_by]}</td>
			<td>$bank->entry_time</td>
			<td>".options2("", $bank->id, array("edit", "remove","erase"))."</td></tr>";
		$i++;
	}
	print "</tbody>
	</table>";