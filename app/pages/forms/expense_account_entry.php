<style>
	.w150 {
		width: 250px !important;
	}
</style>
<?php
if (METHOD == 'add') {
	$acc = R::load("expense_account", $get->a);
} else {
	$acc = R::load("expense_account", $object->accountid);
}
ensureMysqlColumn('expense_account_entry', 'investment_id', "INT NULL DEFAULT NULL");
openForm();
if (isset($get->month)) {
	print "<input type='hidden' name='month' value='$get->month'>";
}
if (isset($get->bt)) {
	$bank_transaction = R::load("bank_transaction_item", $get->bt);
	// vd($bank_transaction);
	$object->payment_method = 'Online';
	$object->expense_date = $bank_transaction->date;
	$object->amount = $bank_transaction->debit;
	$object->particulars = $bank_transaction->description;
	$object->bank_transaction = $bank_transaction->id;
	print "<input type='hidden' name='bank_transaction' value='$object->bank_transaction'>";
}
print "<table align='center'>
	<tr><td colspan='5'><b>" . str("Expense Account Entry Details") . "</b></td><tr>";
if (isset($get->a) && nn($get->a)) {
	print "<tr><td colspan='5'>" . getFieldValue("expense_account", "breadcrumbs", "id=$get->a") . "</tr>";
}
print "
		<tr>
		<td>" . str("Accountid") . "</td><td><select name='accountid' class='parent form-control selectpicker required' required data-live-search='true'><option value=''>--SELECT--</option>" . getAccountsWithChild($object->accountid ? $object->accountid : (isset($get->a) ? $get->a : '')) . "</select></td>
		<td>" . space(5) . "</td>
		<td>" . str("Amount") . "</td><td><input type='text' name='amount' id='amount' value='$object->amount' autofocus required class='form-control required number' /></td>
		<td>" . space(5) . "</td>
		<td>";
print "<input required type='radio' name='payment_method' class='pm' value='Cash' " . ($object->payment_method == 'Cash' ? 'checked' : '') . "> Cash" . space(5);
print "<input required type='radio' name='payment_method' class='pm' value='Online' " . ($object->payment_method == 'Online' ? 'checked' : '') . "> Online ";
print space(5) . "<label><input type='checkbox' name='is_investment' id='is_investment' value='1' " . (!empty($object->investment_id) ? 'checked' : '') . "> Investment</label>";
// .se2("expense_account_entry///", "payment_method", ($object->payment_method ? $object->payment_method : 'Cash')).
print "</tr>";
print "<tr><td>Expense Date</td><td>" . ds("expense_date", $object->expense_date, true) . "</td><td></td><td>Account</td><td id='bank_account'>
</td></tr>";
print "<tr><td colspan='5'><hr></td></tr>";
print "<tr><td>Particulars Date</td><td>" . ds("particular_date", $object->particular_date, true) . "</td><td></td><td></td></tr>";
print "<tr><td>" . str("Particulars") . "</td><td colspan='7'><textarea name='particulars' rows='5' id='particulars' class='form-control required'>$object->particulars</textarea></td></tr>
	</table>";

closeForm();

?>

<div class='hidden'>
	<div id='hint-1'></div>
	<div id='hint-2'></div>
	<div id='worker_names'></div>
</div>

<script type="text/javascript">
	counter = 1;

	$("form").on("submit", function (e) {
		const form = this;

		if (!$("#is_investment").is(":checked") && !$(form).data("investment-confirmed")) {
			e.preventDefault();
			Swal.fire({
				icon: 'question',
				title: 'Is this an investment?',
				text: 'Choose Yes to create an investment entry for this expense.',
				showCancelButton: true,
				confirmButtonText: 'Yes',
				cancelButtonText: 'No'
			}).then(function (result) {
				if (result.isConfirmed) {
					$("#is_investment").prop("checked", true);
				}
				if (result.isConfirmed || result.dismiss) {
					$(form).data("investment-confirmed", true);
					if ($(form).find("input[name='save']").length === 0) {
						$(form).append("<input type='hidden' name='save' value='1'>");
					}
					form.submit();
				}
			});
			return false;
		}

		if ($("#is_investment").is(":checked") && !$(form).data("investment-confirmed")) {
			$(form).data("investment-confirmed", true);
		}
	});

	$("select.worker2").change(function (e) {
		var name = $("select.worker2 option:selected").text();
		var worker_names = $("#worker_names").text();
		if (worker_names != "") {
			worker_names += ", ";
		}
		worker_names += name;
		$("#worker_names").text(worker_names);

		var id = $("select.worker2 option:selected").val();
		var photo = $("select.worker2 option:selected").data('photo_file');
		$(".worker-photo").attr('src');
		var ph = "";
		if (photo != "") {
			// $(".worker-photo").attr('src', '/app/uploads/worker/' + id + '/' + photo);
			ph = "<img class='worker-photo w50' src='/app/uploads/worker/" + id + '/' + photo + "' >";
		}
		if (counter == 1) {
			$(".bdcon-worker").before("<tr><td><input type='hidden' name='workers[]' value='" + id + "'> Worker " + counter + "</td><td>" + name + "</td><td>" + ph + "</td><td></td><td rowspan='10'>" +
				"<table><tr><td><div id='hints'><div><input type='radio' name='r'>Levi payment done.</div><div><input type='radio' name='r'>Insurance payment done.</div><div><input type='radio' name='r'>Fomema payment done.</div><div><input type='radio' name='r'>Cidb payment done.</div><div><input type='radio' name='r'>SOCSO Payment done.</div><div><input type='radio' name='r'>SP Payment done.</div><div><input type='radio' name='r'>Contact medical payment done.</div></div></td><td><div id='hints_account'><div><input type='radio' name='b'>From may bank Tutul account</div><div><input type='radio' name='b'>From may bank Emon account</div><div><input type='radio' name='b'>From may bank Kt account</div><div><input type='radio' name='b'>From may bank Madam account</div><div><input type='radio' name='b'>From Ddcon may bank account</div><div><input type='radio' name='b'>From Bdcon may bank account</div><div><input type='radio' name='b'>From Ekawin may bank account</div><div><input type='radio' name='b'>From RHB bank Kt account</div><div><input type='radio' name='b'>From Neat & Clean May Bank Account</div><input type='date' class='form-control' id='date' onchange='dateChanged()'></div></td></tr></table><br>" +
				"<input type='text' name='note_text' id='note_text' value='' class='form-control'><br><input name='note_color' type='color' class='form-control-fluid' value='#ffffff'>" +
				"</td><tr>");

			$("#hints input[type='radio']").click(function () {
				var text = $(this).parent().text();
				$("#hint-1").text(text);
				var dt = $("#date").val();
				if (dt != '') { dt = moment(dt).format("DD/MM/YYYY"); }
				var nt = $("#hint-1").text() + " " + $("#hint-2").text() + " " + dt;
				$("#note_text").val(nt);
				worker_names = $("#worker_names").text();
				$("#particulars").val(nt + " (" + worker_names + ")");
			})
			$("#hints_account input[type='radio']").click(function () {
				var text = $(this).parent().text();
				$("#hint-2").text(text);
				var dt = $("#date").val();
				if (dt != '') { dt = moment(dt).format("DD/MM/YYYY"); }
				var nt = $("#hint-1").text() + " " + $("#hint-2").text() + " " + dt;
				$("#note_text").val(nt);
				worker_names = $("#worker_names").text();
				$("#particulars").val(nt + " (" + worker_names + ")");
			})
		} else {
			$(".bdcon-worker").before("<tr><td><input type='hidden' name='workers[]' value='" + id + "'> Worker " + counter + "</td><td>" + name + "</td><td>" + ph + "</td><tr>");
			dateChanged();
		}
		counter++;
	});

	$(".pm").change(function () {
		if ($(".pm:checked").val() == 'Online') {
			$.post("/store/ajax/bank_account.php", { 'company': '<?php print $acc->company; ?>' }, function (data) {
				$("#bank_account").html(data);
			});
			// $("#particulars").val('Online Payment kore ');
		} else {
			// 	$("#bank_account").html('');
			// $("#particulars").val('Petty Cash theke ');
		}
	});

	function dateChanged() {
		var dt = moment($("#date").val()).format("DD/MM/YYYY");
		var nt = $("#hint-1").text() + " " + $("#hint-2").text() + " " + dt;
		$("#note_text").val(nt);
		var worker_names = $("#worker_names").text();
		$("#particulars").val(nt + " (" + worker_names + ")");
	}
</script>