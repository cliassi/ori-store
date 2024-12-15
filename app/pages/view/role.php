<script type="text/javascript">

</script>
<table align="center" class="table table-bordered table-stripe" width="100%">
<tr><th>No.</th><th>Role Name</th><th>Date Added</th><th>Owner</th><th>Status</th><th><?php print options2('role','', array('add')); ?></th></tr>
<?php
	$roles = select("r.*, u.r_name u_username","sys_role r LEFT JOIN sys_role u ON (u.id=r.r_owner)", "r.id > 1");
	$i = 1;
	while($role = mysqli_fetch_object($roles)){
		echo "<tr>";
		echo "<td>$i</td>";
		echo "<td>$role->r_name</td>";
		echo "<td>$role->r_date_created</td>";		
		echo "<td>$role->u_username</td>";
		echo "<td>";
		if($role->r_active==1){
			echo "Enabled";
		} else{
			echo "Disabled";	
		}
		echo "</td><td>".options2('role',$role->id, array('edit', 'remove', $role->r_active?'deactivate':'activate'))."</td>";	
		echo "</tr>";
		$i++;
	}
	echo "<tr><th colspan='10'>".options2('role','', array('add'))."</td></tr>";	
?>
</table>