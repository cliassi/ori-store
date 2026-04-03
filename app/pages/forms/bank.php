<?php
openForm();
print "<table align='center'>
	<tr><td colspan='5'><b>".str("Bank Details")."</b></td><tr>
		<tr><td>".str("Name")."</td><td><input type='text' name='name' id='name' value='$object->name' class='form-control required' /></td><td>".space(5)."</td><td>".str("Account Name")."</td><td><input type='text' name='account_name' id='account_name' value='$object->account_name' class='form-control required' /></td></tr>
		<tr><td>".str("Account Number")."</td><td><input type='text' name='account_number' id='account_number' value='$object->account_number' class='form-control required' /></td><td>".space(5)."</td></tr>
	</table>";
closeForm();

//<td>".str("Opening Balance")."</td><td><input type='text' name='opening_balance' id='opening_balance' value='$object->opening_balance' class='form-control required number' /></td>