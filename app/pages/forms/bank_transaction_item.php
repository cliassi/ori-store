<?php
openForm();
print "<table align='center'>
	<tr><td colspan='5'><b>".str("Bank Transaction Item Details")."</b></td><tr>
		<tr><td>".str("Description")."</td><td colspan='4'><textarea name='description' id='description' class='form-control '>$object->description</textarea></td></tr>
		<tr><td>".str("Credit")."</td><td><input type='text' name='credit' id='credit' value='$object->credit' class='form-control required number' /></td><td>".space(5)."<a class='btn btn-success' onclick='sw()'><i class='fa fa-exchange'></i></a>".space(5)."</td><td>".str("Debit")."</td><td><input type='text' name='debit' id='debit' value='$object->debit' class='form-control required number' /></td></tr>
	</table>";
closeForm();

?>

<script type="text/javascript">
	function sw(){
		var credit = $("#credit").val();
		var debit = $("#debit").val();
		$("#credit").val(debit);
		$("#debit").val(credit);
	}
</script>