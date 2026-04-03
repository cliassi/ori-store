<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

$filter = "trash=0";
// if(isset($_REQUEST['company']) && nn($_REQUEST['company'])){
  // $filter .= ' AND show_in_carwash>0 ORDER BY show_in_carwash';
// } else{
//   $filter .= " AND id<>13";
// }
print sop2("bank", "", ['optional'=>true, 'attr'=>'required', 'filter'=>$filter]);
