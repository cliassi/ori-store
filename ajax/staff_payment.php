<?php
require_once("../safeboot.php"); 
if(isset($post->id) && isset($post->salary)){	
  $result = select("*", "staff_payment", "staff_id=".$post->id);
  $salary = str_replace(",", "", $post->salary) + 0;
  $income = str_replace(",", "", $post->income) + 0;
  $balance = $salary;
  print "<tr><td></td><td>Salary</td><td class='text-right'>".nf($salary)."</td><td></td><td class='text-right'>".nfz($balance)."</td></tr>";

  $incomes = R::find("staff_income", "staff_id=?", [$post->id]);

  foreach ($incomes as $key => $ic) {
    $balance = $balance + $ic->amount;
    $p = $ic;
    print "<tr><td>".df($ic->date)."</td><td>$ic->particulars</td><td class='text-right'>".nf($ic->amount)."</td><td></td><td class='text-right'>".nfz($balance)."</td>";
    if(uid()==1){
        print "<td id='staff_income_status_{$p->id}'>";
        if($p->approved_by){
          print "<button type='button' class='btn btn-sm btn-success'>Approved</button>";
        } else{
          print "<button type='button' onClick='approveIncome($p->id)' class='btn btn-sm btn-danger'>Pending</button>";
        }
        print "</td><td><a href='javascript:deleteIncome($p->id)'>X</a></td>";
     }
     print "</tr>";
  }

  if($result->num_rows){
  	while($p = mysqli_fetch_object($result)){
      $balance = $balance - $p->amount;
  	 print "<tr><td>".df($p->date)."</td><td>$p->particulars</td><td></td><td class='text-right'>".nf($p->amount)."</td><td class='text-right'>".nfz($balance)."</td>";
     if(uid()==1){
        print "<td id='staff_payment_status_{$p->id}'>";
        if($p->approved_by){
          print "<button type='button' class='btn btn-sm btn-success'>Approved</button>";
        } else{
          print "<button type='button' onClick='approvePayment($p->id)' class='btn btn-sm btn-danger'>Pending</button>";
        }
        print "</td><td><a href='javascript:deletePayment($p->id)'>X</a></td>";
     }
     print "</tr>";
    }
  }
}