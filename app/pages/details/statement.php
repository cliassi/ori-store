<style type="text/css">
	div span{
		display: inline-block;
	}
	.special,.special a{
		 font-size: 2rem; font-weight: 700; color: magenta !important; background: cyan;
	}
	#hints{
		padding-left: 5px;
	}
	input[type="radio"]{
		padding-right: 5px;
		margin-right: 5px;
	}
</style>
<?php
$object = R::load('customer', ID);
if(isset($post->save_collection)){
	$tran = R::dispense("transfer_tran");
	$tran->company = 11;
	$tran->customer = ID;
	$tran->date = $post->date;
	$tran->particulars = $post->particulars;
	$tran->method = $post->method;
	$tran->amount = $post->amount;
	$tran->entry_by = uid();
	$tran->entry_time = now();
	R::store($tran);
	redir("?");
} elseif (isset($post->save_send)) {
	$tran = R::dispense("transfer_col");
	$tran->company = 11;
	$tran->customer = ID;
	$tran->method = $post->method;
	$tran->date = $post->date2;
	$tran->particulars = $post->particulars;
	$tran->amount = $post->amount;
	$tran->rate = $post->rate;
	$tran->amount_bdt = $post->amount_bdt;
	$tran->entry_by = uid();
	$tran->entry_time = now();
	$tran->bank_name = isset($post->bank_name) ? $post->bank_name : '';
	$tran->district = isset($post->district) ? $post->district : '';
	$tran->branch_name = isset($post->branch_name) ? $post->branch_name : '';
	$tran->account_name = isset($post->account_name) ? $post->account_name : '';
	$tran->account_no = isset($post->account_no) ? $post->account_no : '';
	$tran->phone = isset($post->phone) ? $post->phone : '';
	R::store($tran);
	redir("?");
}
if(isset($post->approve_transfer_tran)){
	$tran = R::load("transfer_tran", $post->id);
	$tran->approved = 1;
	$tran->approved_by = uid();
	$tran->approved_time = now();
	R::store($tran);
}
if(isset($post->approve_transfer_col)){
	$tran = R::load("transfer_col", $post->id);
	$tran->approved = 1;
	$tran->approved_by = uid();
	$tran->approved_time = now();
	R::store($tran);
}
print "<div class='center'>";
print "<h1>$object->name</h1>";
print "<h3>$object->phone</h3>";
print "<hr>";
print "</div>";

$limit = isset($get->showall) ? 1000 : 10;

?>
<div class="row">
	<div class="col-md-4 text-center"><a class='btn btn-success' data-toggle="modal" data-target="#collection">Collection</a></div>
	<div class="col-md-4 text-center"><a class='btn btn-primary' data-toggle="modal" data-target="#send">Send</a></div>
	<div class="col-md-4 text-center"><a class='btn btn-warning' data-toggle="modal" data-target="#refund">Refund</a></div>
</div>
<br>

<!-- <div style="background: #ccc; padding: 5px; border-radius: 10px" class="right">
	<a class='btn btn-primary' data-toggle="modal" data-target="#collection">Invoice</a>
	<a class='btn btn-success' data-toggle="modal" data-target="#send">Collection</a>
</div>
<br> -->
<table class='table table-bordered'>
	<thead>
		<tr>
			<th>No.</th>
			<th>Date</th>
			<th>Ref.</th>
			<th>Particulars</th>
			<th>Method</th>
			<th>Prepared By</th>
			<th>Debit</th>
			<th>Credit</th>
			<th>Balance</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$total_collection = $total_send = $total_bd = 0;
			$transfer_tran = getSum("invoice i, invoice_item ii", "price*quantity", "i.id=ii.invoice_id AND customer_id=".ID);
			$transfer_col = getSum("collection", "amount", "customer_id=".ID);

			$query = "SELECT * FROM (
				SELECT * FROM (
					SELECT i.id, customer_id, invoice_date `date`, description particulars, '' method, (price*quantity) amount, 0 rate, 0 amount_bdt, i.created_by entry_by, i.created_at entry_time, 'Invoice' source, 'transfer_tran' `type`
, '' approved, '' phone, '' bank_name FROM invoice i, invoice_item ii WHERE i.id=invoice_id AND customer_id=".ID."
					UNION
					SELECT id, customer_id, `date`, description particulars, '' method, amount, 0 rate, 0 amount_bdt, created_by entry_by, created_at entry_time, 'Collection' source, 'transfer_col' `type`, approved, '' phone, '' bank_name FROM collection WHERE customer_id=".ID."
				)t ORDER BY `date` DESC, `entry_time` DESC LIMIT $limit) u ORDER BY `date`, `entry_time`
			";



			$trans = select($query);
			$users = userList();
			$userAvatars = toA("sys_user", "id", "u_avatar");
			$i = 1;
			$con = "";
			while ($tran = mysqli_fetch_object($trans)) {
				$avatar = "";
				if(file_exists("uploads/user/avatar/{$userAvatars[$tran->entry_by]}") && nn($userAvatars[$tran->entry_by])){
					$avatar = "<img src='$appurl/uploads/user/avatar/{$userAvatars[$tran->entry_by]}' class='w30'>";
				} else{
					$avatar = $users[$tran->entry_by];
				}

				$con .=  "<tr>";
				$con .=  "<td class='text-center'>$i</td>";
				$con .=  "<td>".df($tran->date)."</td>";
				$con .=  "<td class='text-center'>".($tran->source=='Invoice'?'INV':'COL').zerofill($tran->id, 5)."</td>";
				$con .=  "<td>$tran->particulars ".(nn($tran->phone) ? ' (Acc '.$tran->phone.')' : '').(nn($tran->bank_name) ? ' (Bank '.$tran->bank_name.')' : '')."</td>";
				$con .=  "<td class='text-center'>$tran->method</td>";
				$con .=  "<td>$avatar</td>";
				if($tran->source=='Invoice'){
					$con .=  "<td class='text-right'>".nf($tran->amount)."</td>";
					$con .=  "<td></td>";
					$total_send += $tran->amount;
				} else{
					$con .=  "<td></td>";
					$con .=  "<td class='text-right'>".nf($tran->amount)."</td>";
					$total_collection += $tran->amount;
				}
				$total_bd += $tran->amount_bdt;
				$con .=  "<td class='text-right'>".nf($total_send - $total_collection + $transfer_tran - $transfer_col)."</td>";
				$con .=  "<td class='text-center'>";
				if($tran->approved){
					$con .=  "<button class='btn btn-success'>Approved</button>";
				} else{
					if(uid()==1){
						$con .=  "<form method='post' onsubmit='return validate()'><button class='btn btn-warning' type='submit' name='approve_{$tran->type}'>Pending</button><input type='hidden' name='id' value='$tran->id'></form>";
					} else{
						$con .= "<button class='btn btn-warning' type='button'>Pending</button>";
					}
				}
				$con .=  "</td>";
				$con .=  "<td class='text-center'>";// && hasAccess('transfer_tran', 'edit')
				if($tran->source=='Invoice'){
					// $con .=  "<a href=''><i class='fa fa-edit'></i></a>";
					$con .=  options2("transfer_tran", $tran->id, array("edit", "erase"));
				} else{
					$con .=  options2("transfer_col", $tran->id, array("edit", "erase"));
				}
				$con .=  "</td>";
				$con .=  "</tr>";
				$i++;
			}
			print $con;
		?>
	</tbody>
	<tfoot>
		<tr>
			<th><?php print isset($get->showall) ? "<a class='btn btn-sm btn-danger' href='?'>Show less</a>": "<a class='btn btn-sm btn-success' href='?showall'>Show all</a>"; ?></th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th class='text-right'>TOTAL</th>
			<th class='text-right'><?php print nf($total_collection); ?></th>
			<th class='text-right'><?php print nf($total_send); ?></th>
			<th class='text-right'><?php print nfz($transfer_tran - $transfer_col); ?></th>
			<th></th>
		</tr>
	</tfoot>
</table>

<div class="modal fade" id="collection" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content center">
    	<form method="post">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal">&times;</button>
	        <h4 class="modal-title">Collection</h4>
	      </div>
	      <div class="modal-body center">
	        <table class='center table table-stripped'>
	        	<tr><td>Date</td><td><?php print dateSelector("date"); ?></td></tr>
	        	<tr><td>Amount</td><td><input type="number" name="amount" class="form-control"></td></tr>
	        	<tr><td>Particulars</td><td class='text-left cm'>
	        		<br>
	        		<div class="cm-hint-bank">
								<div><input type='radio' name='t'>Arif bhai er may bank account a taka banking korse.</div>
								<div><input type='radio' name='t'>Ekwin may bank account a taka banking korse.</div>
								<div><input type='radio' name='t'>Bdcon may bank account a taka banking korse.</div>
								<div><input type='radio' name='t'>Ddcon may bank account a taka banking korse.</div>
								<div><input type='radio' name='t'>Khandaker Tajul RHB account a taka banking korse.</div>
								<div><input type='radio' name='t'>Nadim Kazi RHB account a taka banking korse.</div>
								<div><input type='radio' name='t'>Neat & Clean RHB account a taka banking korse.</div>
								<div><input type='radio' name='t'>Neat & Clean  may bank account a taka banking korse.</div>
	        		</div>
	        		<div class="cm-hint-cash">
								<div><input type='radio' name='t'>Office a cash taka joma dise.</div>
								<div><input type='radio' name='t'>Cash taka joma dise.</div>	        			
	        		</div>
	        		<br>
	        		<textarea name="particulars" id="col-particulars" class="form-control"></textarea></td></tr>
	        	<tr><td>Payment Method</td><td><?php print selectEnum("name='method' class='form-control' id='collection_method'", "transfer_tran", "method", 'Bank'); ?></td></tr>
	        </table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_collection">Save</button>
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<div class="modal fade" id="send" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content center">
      <form method="post">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal">&times;</button>
	        <h4 class="modal-title">Send</h4>
	      </div>
	      <div class="modal-body center">
	        <table class='center'>
	        	<tr><td>Date</td><td><?php print dateSelector("date2"); ?></td></tr>
	        	<tr><td>MYR Amount</td><td><input type="number" name="amount" step=".01" v-model="send_amount" id="send_amount" class="form-control" v-on:keyup="myr"></td></tr>
	        	<tr><td>Rate</td><td><input type="number" name="rate" step=".01" v-model="send_rate" id="send_rate" readonly class="form-control" v-on:keyup="rate"></td></tr>
	        	<tr><td>BDT Amount</td><td><input type="number" name="amount_bdt" step=".01" v-model="send_amount_bdt" class="form-control" v-on:keyup="bdt"></td></tr>
	        	<tr><td>Particulars</td><td><textarea name="particulars" id='sm-particulars' class="form-control"></textarea></td></tr>
	        	<tr><td>Payment Method</td><td><?php print selectEnum("name='method' id='send-pm' required class='form-control'", "transfer_col", "method", '', [],true,false,true); ?></td></tr>

	        	<tr class='bKash-fields'><td>Phone Number</td><td><input type="text" name="phone" class="form-control" ></td></tr>

	        	<tr class='bank-fields'><td>Bank Name</td><td><?php print sop2("bank_name", '', ['valueField'=>'name', 'class'=>'w250'], "bd_banks"); ?></td></tr>
	        	<tr class='bank-fields'><td>District</td><td><?php print sop2("district", '', ['valueField'=>'name', 'class'=>'w250'], "bd_districts"); ?></td></tr>
	        	<tr class='bank-fields'><td>Branch Name</td><td><input type="text" name="branch_name" class="form-control" ></td></tr>
	        	<tr class='bank-fields'><td>Account Name</td><td><input type="text" name="account_name" class="form-control" ></td></tr>
	        	<tr class='bank-fields'><td>Account No</td><td><input type="text" name="account_no" class="form-control" ></td></tr>

	        </table>
	      </div>
	      <div class="modal-footer">
	        <button type="submit" class="btn btn-success" name="save_send">Save</button>
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      </div>
	    </form>
    </div>
  </div>
</div>

<script type="text/javascript">
	var app = new Vue({
		el:'#send',
		data: {
			send_amount: 0,
			send_rate: <?php print regValue('rate2'); ?>,
			send_amount_bdt: 0
		},
	  methods: {
	    myr: function () {
	      this.send_amount_bdt = parseFloat(this.send_amount) * parseFloat(this.send_rate);
	    },
	    rate: function () {
	      this.send_amount_bdt = parseFloat(this.send_amount) * parseFloat(this.send_rate);
	    },
	    bdt: function () {
	      this.send_amount = parseFloat(parseFloat(this.send_amount_bdt) / parseFloat(this.send_rate)).toFixed(2);
	    }
	  }
	});

	$("#collection_method").change(cmChanged);

	cmChanged();

	function cmChanged(){
		if($("#collection_method").val() == 'Bank'){
			$(".cm-hint-bank").show();
			$(".cm-hint-cash").hide();
		} else {
			$(".cm-hint-bank").hide();
			$(".cm-hint-cash").show();
		}	
	}

	$(".cm input[type='radio']").click(function(){
			var text = $(this).parent().text();
			$("#col-particulars").val(text);
		})

	$("#send-pm").change(pmChanged);

	pmChanged();

	function pmChanged(){
		var pm = $("#send-pm").val();
		if(pm != '')
		$("#sm-particulars").val(pm + " a taka pathaise RM " + $("#send_amount").val() + " Rates " + $("#send_rate").val());

		if(pm == 'Bank'){
			$(".bank-fields").show().attr('required', true);
			$(".bank-fields").attr('required', true);
			$(".bKash-fields").hide().removeAttr('required', true);
		} else { //if($("#send-pm").val() == 'bKash') {
			$(".bank-fields").hide().removeAttr('required', true);
			$(".bKash-fields").show().attr('required', true);
			$(".bKash-fields").attr('required', true);
		} /*else{
			$(".bank-fields").hide().removeAttr('required', true);
			$(".bKash-fields").hide().removeAttr('required', true);
		}*/
	}

	function validate() {
		return confirm("Are you sure?");
	}
</script>