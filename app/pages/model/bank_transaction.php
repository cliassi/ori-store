<?php 
use PhpOffice\PhpSpreadsheet\IOFactory;
$object = R::dispense('bank_transaction');
$fields = ['date','account'];
$function = METHOD;

$hold = false;
$msg = "";

foreach ($fields as $field) {
	if(isset($post->$field) && nn($post->$field)) {
		$object->$field = $post->$field;
	}
}

if($function=="add") {
	$object->transactions = $post->transactions;
	$object->entry_by = uid();
	$object->entry_time = now();

}
if($function=="edit") {
	$object->modify_by = uid();
	$object->modify_time = now();
}

R::store($object); 
$count = 1;
if($function=="add") {
	if(isset($_FILES['file']['name']) && nn($_FILES['file']['name'])){
		$name = upload($_FILES, time(), "uploads/rhb");
		$path = "uploads/rhb/$name";

		$trans = getTranFromCSV($path);

		vd((object)$trans[0]);

		$count = 1;
		foreach ($trans as $key => $tran) {
			//$fields = ['Date', 'BRH', 'Description', 'Sender', 'Reference1', 'Reference2', 'RefNum', 'Debit', 'Credit', 'Balance'];
			$tran = (object)$tran;
			insert("bank_transaction_item", "`bank_transaction`, `date`, `description`, `credit`, `debit`", "$object->id, '".$tran->Date."', '$tran->Description, $tran->Sender, $tran->Reference1', '$tran->Credit', '$tran->Debit'");
			// $msg .= "<div class='alert alert-success'><b>{$count}. Success :</b> <u><i>$t</i></u></div>";
			$count++;
		}
		// dd($trans);
		
	} if(isset($_FILES['file_xls']['name']) && nn($_FILES['file_xls']['name'])){
		define('FIRSTROW', 5);

    $file = $_FILES['file_xls']['tmp_name'];
    
    // Load the Excel file
    $spreadsheet = IOFactory::load($file);

    // Get the first worksheet
    $worksheet = $spreadsheet->getActiveSheet();

    // Get the highest row number
    $highestRow = $worksheet->getHighestRow();

    // Read the content row by row

    $cols = ['A','C','D'];
    $data = [];
    $content = "<table border='1'>";
    $record = 0;
    for ($row = 1; $row <= $highestRow; $row++) {
    	if($row < FIRSTROW) continue;
    	$content .= "<tr>";
    	foreach ($cols as $key => $col) {
    		$content .= "<td>".$worksheet->getCell($col. $row)->getValue()."</td>";
    	}
    	$content .= "<tr>";
    	if(!empty($worksheet->getCell('A'. $row)->getValue())){
    		$amount = $worksheet->getCell('D'. $row)->getValue()."";
    		$dateParts = explode("/", $worksheet->getCell('A'. $row)->getValue()."");
  	  	$data[$record]['date'] = date("Y-{$dateParts[1]}-{$dateParts[0]}", time());
  	  	$data[$record]['description'] = trim($worksheet->getCell('C'. $row)->getValue())."";
  	  	if(strpos($amount, "+")){
  	  		$data[$record]['credit'] = digitOnly($amount);
  	  		$data[$record]['debit'] = 0;
  	  	} else{
  	  		$data[$record]['debit'] = digitOnly($amount);
  	  		$data[$record]['credit'] = 0;
  	  	}
  	  	$record++;
    	} else{
    		$data[$record - 1]['description'] .= ", ".trim($worksheet->getCell('C'. $row)->getValue());
    	}
    }
    $content .= "</table>";
    // print count($data);
    // var_dump($data);
    // die(0);

    $count = 1;
		foreach ($data as $key => $tran) {
			//$fields = ['Date', 'BRH', 'Description', 'Sender', 'Reference1', 'Reference2', 'RefNum', 'Debit', 'Credit', 'Balance'];
			$tran = (object)$tran;
			// $cnt = zerofill($count, 3);
			
			$t = R::dispense("bank_transaction_item");

			$t->bank_transaction = $object->id;
			$t->date = $tran->date;
			$t->description = $tran->description;
			$t->credit = $tran->credit;
			$t->debit = $tran->debit;

			R::store($t);

			// inserts("bank_transaction_item", "`bank_transaction`, `date`, `description`, `credit`, `debit`", "$object->id, '".$tran->date."', '{$cnt}. $tran->description', '$tran->credit', '$tran->debit'");
			// $msg .= "<div class='alert alert-success'><b>{$count}. Success :</b> <u><i>$t</i></u></div>";
			$count++;
		}
    // die(0);

	} else{
		$transactions = explode(PHP_EOL, $post->transactions);
		foreach($transactions as $t){
			/*$parts = preg_split("/\t+/", $t);
			$parts[2] = digitOnly($parts[2]);
			// var_dump($parts);
			$debit = $credit = 0;
			if(count($parts) == 3){
				$credit = $parts[2] + 0;
			} else{
				$debit = $parts[2] + 0;
			}
			*/
			$tr = str_replace("\t", "|", $t);
			$parts = explode("|", $tr);
			$debit = $credit = 0;
			if(nn($parts[3])){
				$credit = digitOnly($parts[3]) + 0;
			} else{
				$debit = digitOnly($parts[2]) + 0;
			}
			// if(!exists("bank_transaction_item", "`date`='".date("Y-m-d", strtotime($parts[0]))."' AND description='{$parts[1]}' AND credit=$credit AND debit=$debit")){
				insert("bank_transaction_item", "`bank_transaction`, `date`, `description`, `credit`, `debit`", "$object->id, '".date("Y-m-d", strtotime($parts[0]))."', '{$parts[1]}', '$credit', '$debit'");
				$msg .= "<div class='alert alert-success'><b>{$count}. Success :</b> <u><i>$t</i></u></div>";
			// } else{
			// 	$msg .= "<div class='alert alert-danger'><b>{$count}. Already Exists :</b> <u><i>$t</i></u></div>";
			// }
			$count++;
		}
	}
} else{
	if($post->tab == 'i'){
		foreach ($post->trans as $key => $index) {
			$worker = $post->{"worker_$index"};
			$passport = $post->{"passport_$index"};
			$company = $post->{"company_$index"};
			$company_name = $post->{"company_name_$index"};
			$particulars = $post->{"particulars_$index"};
			update("bank_transaction_item", ($worker ? "`worker`='$worker', " : "").($company ? "`company`='$company', " : "")."`passport`='$passport', `company_name`='$company_name', `particulars`='$particulars'", "id=$index");
		}
	} elseif($post->tab == 'o'){
		foreach ($post->trans as $key => $index) {
			$particulars = $post->{"particulars_$index"};
			update("bank_transaction_item", "`particulars`='$particulars'", "id=$index");
		}
	}
}

// if($hold){
	print "$msg";
	// die('');
// }
// die(0);
?>