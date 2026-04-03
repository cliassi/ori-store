<?php
session_start();
if(isset($_POST['id']) && isset($_POST['salary'])){	
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
  $result = select("*", "hotel_statement_worker_payment", "worker=".$_POST['id']);
  $salary = str_replace(",", "", $_POST['salary']) + 0;
  $income = str_replace(",", "", $_POST['income']) + 0;
  $balance = $salary;
  print "<tr><td></td><td>Salary</td><td class='text-right info-salary'>".nf($salary)."</td><td></td><td class='text-right info-balance'>".nfz($balance)."</td><td></td><td></td></tr>";

  $incomes = R::find("hotel_statement_worker_income", "worker=?", [$_POST['id']]);

  foreach ($incomes as $key => $ic) {
    $balance = $balance + $ic->amount;
    $p = $ic;
    print "<tr class='tr-income-$p->id'><td>".df($ic->date)."</td><td>$ic->particulars</td><td class='text-right'>".nf($ic->amount)."</td><td></td><td class='text-right'>".nfz($balance)."</td>";
    if(uid()==1){
        print "<td id='hotel_statement_worker_income_status_{$p->id}'>";
        if($p->approved_by){
          print "<button type='button' class='btn btn-sm btn-success'>Approved</button>";
        } else{
          print "<button type='button' onClick='approveIncome($p->id)' class='btn btn-sm btn-danger'>Pending</button>";
        }
        print "</td><td><a href='javascript:deleteIncome($p->id, 0)'>X</a></td>";
     }
     print "</tr>";
  }

  if($result->num_rows){
  	while($p = mysqli_fetch_object($result)){
      $balance = $balance - $p->amount;
  	 print "<tr class='tr-payment-$p->id'><td>".df($p->date)."</td><td>$p->particulars</td><td></td><td class='text-right'>".nf($p->amount)."</td><td class='text-right'>".nfz($balance)."</td>";
     if(uid()==1){
        print "<td id='hotel_statement_worker_payment_status_{$p->id}'>";
        if($p->approved_by){
          print "<button type='button' class='btn btn-sm btn-success'>Approved</button>";
        } else{
          print "<button type='button' onClick='approvePayment($p->id)' class='btn btn-sm btn-danger'>Pending</button>";
        }
        print "</td><td><a href='javascript:deleteIncome($p->id, 1)'>X</a></td>";
     }
     print "</tr>";
    }
  }
}