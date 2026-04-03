<?php
session_start();
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");


if(isset($_POST['id']) && isset($_POST['payment'])){	

  if(uid() == 1){
    $payment = R::load("hotel_statement_worker_payment", $_POST['id'] + 0);
    $payment->approved_by = uid();
    $payment->approved_time = now();

    R::store($payment);

    $transfer = R::findOne("transfer_tran", "hotel_payment_ref=?", [$payment->id]);
    if($transfer){
      $transfer->approved = 1;
      $transfer->approved_by = uid();
      $transfer->approved_time = now();
      R::stor($transfer);
    }
    print "OK";
  }
}