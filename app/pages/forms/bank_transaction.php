<style type="text/css">
	.nav-tabs>li.active>a{
		color: #fff !important;
		background-color: #44d49d !important;
	}
</style>
<?php

$id = ID;
$object = R::dispense('bank_transaction');
if(METHOD == 'add'){
	$object->account = $id;
}
openForm('post', true);
print "<table align='center'>
	<tr><td colspan='5'><b>Bank Transaction Details</b></td><tr>
		<tr><td>Date</td><td>".dateSelector("date", $object->date)."</td><td>".space(5)."</td><td>Account</td><td>".selectOption("name='account' id='account' class='form-control selectpicker' data-live-search='true'", 'bank', 'name', 'id',$object->account)."</td></tr>
	</table>";
if(METHOD == 'add'){
	print "<textarea name='transactions' placeholder='Paste Transactions here' id='transactions' class='form-control' '' rows='10'>$object->transactions</textarea>";
	print "<div class='container'><div class='row'>";
		print "<div class='col-md-6'>";
			print "<h2>UPLOAD CSV FILE</h2>";
			print "<input type='file' name='file' placeholder='CSV for RHB' class='form-control'>";
		print "</div>";
		print "<div class='col-md-6'>";
			print "<h2>UPLOAD EXCEL FILE</h2>";
			print "<input type='file' name='file_xls' placeholder='EXCEL for MayBank' class='form-control'>";
		print "</div>";
	print "</div>";
	print "</div>";
}
if(METHOD != 'add'){
	$tab = isset($get->t) ? $get->t : 'i';
	print "<input type='hidden' name='tab' value='$tab'>";
	?>
	<ul class="nav nav-tabs">
	  <li class="<?php print $tab == 'i' ? 'active' : ''; ?>"><a class="<?php print $tab == 'i' ? 'btn-success' : ''; ?>" href="?t=i">Credit (In)</a></li>
	  <li class="<?php print $tab == 'o' ? 'active' : ''; ?>"><a class="<?php print $tab == 'o' ? 'btn-success' : ''; ?>" href="?t=o">Debit (Out)</a></li>
	</ul>

	<?php if($tab == 'i'): ?>
	<table class="table">
		<thead>
			<tr>
				<th>No.</th>
				<th>Date</th>
				<th>Amount</th>
				<th>Name</th>
				<th>Passport No</th>
				<th>Company</th>
				<th>Join Banking / Particulars</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$transactions = R::find("bank_transaction_item", "bank_transaction=? AND credit>0", [$object->id]);
				$i = 1;
				foreach ($transactions as $key => $t) {
					print "<tr>
					<td class='w50'>".($i++)."</td>
					<td class='w100'>".date("d/m/y", time())."</td>
					<td class='w100'>".nf($t->credit)."</td>
					<td class='w120'>".sop2("worker_{$t->id}", $t->worker, ['optional'=>true, 'extraFields'=>"company,passport", 'class'=>'worker'], "worker")."</td>
					<td class='w120'><input type='hidden' name='trans[]' value='{$t->id}'><input class='form-control w100 passport' name='passport_{$t->id}' value='{$t->passport}'></td>
					<td class='w120'><input type='hidden' class='company' name='company_{$t->id}' value='{$t->company}'><input class='form-control company_name w120' name='company_name_{$t->id}' value='{$t->company_name}'></td>
					<td><input class='form-control' name='particulars_{$t->id}' value='{$t->particulars}'></td>
					<td class='w140'>";
				if ($t->status == 1){
					print "<span class='btn btn-success w120'><i class='fa fa-check flft'></i> Approved</span>";
				} else{
					print "<span class='btn btn-warning w120'><i class='fa fa-warning flft'></i> Pending</span>";
				}


				if(uid() == 1){
					print "";
				}

					//"<span class='btn btn-success w120' title='{$users[$transaction[5]]}'><i class='fa fa-check flft'></i> Approved</span>"
			// :(hasAccess('invoice', 'approve')?"<a id='transaction_$i' class='btn btn-danger w120' data-type='{$transaction[6]}' data-id='{$transaction[7]}' href='javascript:approve($i)'><i class='fa fa-warning flft'></i>Pending</a>":"<a class='btn btn-danger w120'><i class='fa fa-warning flft'></i>Pending</a>"))."</td>"
				print "</td>
				</tr>";
				}

				$listOfCompanies = R::find("company");
				$companies = [];
				foreach ($listOfCompanies as $key => $company) {
					array_push($companies, ['id'=>$company->id, 'name'=>$company->name]);
				}
			?>
			
		</tbody>
	</table>

	<script type="text/javascript">
		setTimeout(function(){
			$("select.worker").change(function(e){
			const passport = $(this).find('option:selected').data('passport');
			const companies = JSON.parse('<?php print json_encode($companies); ?>');
			const company = $(this).find('option:selected').data('company');
			const company_name = companies.find(c => c.id == company).name;
			$(this).parent().parent().parent().find('.passport').val(passport);
			$(this).parent().parent().parent().find('.company_name').val(company_name);
			$(this).parent().parent().parent().find('.company').val(company);
			console.log($(this).parent().parent().parent());
		});
		}, 3000);
		
	</script>
	<?php endif; ?>

	<?php if($tab == 'o'): ?>
	<table class="table">
		<thead>
			<tr>
				<th>No.</th>
				<th>Date</th>
				<th>Amount</th>
				<th>Particulars</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$transactions = R::find("bank_transaction_item", "bank_transaction=? AND debit>0", [$object->id]);
				$i = 1;
				foreach ($transactions as $key => $t) {
					print "<tr>
					<td>".($i++)."</td>
					<td class='w100'>".date("d/m/y", time())."</td>
					<td class='w100'>".nf($t->debit)."</td>
					<td><input type='hidden' name='trans[]' value='{$t->id}'><input class='form-control' name='particulars_{$t->id}' value='{$t->particulars}'></td>
				</tr>";
				}

				$listOfCompanies = R::find("company");
				$companies = [];
				foreach ($listOfCompanies as $key => $company) {
					array_push($companies, ['id'=>$company->id, 'name'=>$company->name]);
				}
			?>
		</tbody>
	</table>
	<?php endif; ?>

	<?php
}
print "<br clear='all' /><hr />
		  	<div class='row'><div align='center' class='col-md-3'></div><div align='center' class='col-md-3'><button type='submit' name='save' class='form-control btn btn-success'>Save</button></div>
		<div align='center' class='col-md-3'><a type='reset' class='form-control btn btn-warning' href='?'>Reset</a></div>
		</div>
		</form>";

