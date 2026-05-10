<?php
	//Globals
	$date = date("Y-m-d",time());
	$date1 = date("d-M-Y",time());
	$time = date("H:m:s", time());
	$events = array('li'=>1, 'lo'=>2, 'st'=>3, 'fa'=>4, 'ds'=>5, 'di'=>6, 'du'=>7, 'dd'=>8);
	$sum = array();
	$counta = array();
	$registry = array();
	function back(){
		print "<div class='cntr nfp dp'><a href='javascript:window.history.back()'><i class='fa fa-hand-o-left'></i> Back </a></div>";
	}

	function goBack(){
		print "<script>window.history.go(-2);</script>";
	}

	function dd($object){
		print "<pre>";
		var_dump($object);
		print "</pre>";
		die();
	}
	function vd($object){
		print "<pre>";
		var_dump($object);
		print "</pre>";
	}

	function storeid(){
		return 0;
	}
	//function
	//application related
	function menuUpdated(){
		deleteAllFiles('temps/menus/');
	}
	function v($object){
		var_dump($object);
	}
	/*function ralert($object, $func, $id){
		insert("alt_alert", "al_object, al_id, al_viewed_by, al_time, al_func", "'$object', $id, ".uid().", '".now()."', '$func'");
	}*/
	function ralert(){
		insert("alt_alert", "al_object, al_id, al_viewed_by, al_time, al_func", "'".$get->q."', '".(isset($get->id)?$get->id:'')."', ".uid().", '".now()."', '".(isset($get->f)?$get->f:'')."'");
	}

	function envvar($var, $value = NULL){
		$org_values = select("*", "_envvar", "ev_name='$var'");
		if($org_values->num_rows>0){
			$org_value = mysqli_fetch_object($org_values);
			if($value!==NULL){
				update("_envvar", "ev_value='$value'", "ev_name='$var'");
			} else{
				$value = $org_value->ev_value;
			}
			return $value;
		} else{
			if($value===NULL){
				insert("_envvar", "ev_name", "'$var'");
			} else{
				insert("_envvar", "ev_name, ev_value", "'$var', '$value'");
				return $value;
			}
		}
		return NULL;
	}

	function oRow(){
		print '<div class="row-fluid">';
	}

	function cRow(){
		print '</div>';
	}

	function replace2($string, $replace = [], $with = []){
    foreach ($replace as $key => $value) {
        $string = str_replace($value, $with[$key], $string);
    }
    return $string;
	}

	function replace3($string, $replace_with = []){
    foreach ($replace_with as $replace => $with) {
        $string = str_replace($replace, $with, $string);
    }
    return $string;
	}

	// show upload form, if file not available
	function fon($dir, $file, $w='', $h=''){
		createDir($dir);
		if(isset($post->upload)){
			if(file_exists($dir."/".$file)) unlink($dir."/".$file);
			upload($_FILES, $file, $dir);
			sleep(10);
			redir("?".uri());
		}
		if(file_exists($dir."/".$file.".png")){
			$con = "<img src='".$dir."/".$file.".png' ".nn($w, "width='{$w}px'")." ".nn($h, "height='{$h}px'")." />";
		}	else{
			$con = "<form method='post' enctype='multipart/form-data' />";
			$con .= "<img src='ico/noimage.png' /><br /><br />";
			$con .= "<input type='file' name='file' class='required' /> <input type='submit' class='upload' name='upload' value=''  /></form>";
		}
		return $con;
	}

	function widget($title = 'Untitled', $body = '', $span = 5, $height = 200){
		print "<div class='span$span'>
			<div class='widget-box'>
				<div class='widget-header widget-header-flat widget-header-small'>
					<h5>
						<i class='icon-info-sign'></i>
						$title
					</h5>
				</div>

				<div class='widget-body'>
					<div class='widget-main h$height'>
						$body
					</div>
				</div>
			</div>
		</div>";
	}

	function widget2($body = '', $span=6){
		print "<div class='span$span' style='padding:5px; background:rgba(245,245,255,.4); border: dotted 1px #d8d8d8!important'>$body</div>";
	}
	
	function formatId($id, $type, $date, $link = false, $option='print', $length = 4, $prefix = 'AGDIT'){
		if(strlen($type)>3){
			switch ($type){
				case 'Credit Note': { $type = 'CN';  $page = "sales_credit_note"; } break;
				case 'Debit Note': { $type = 'DN';  $page = "purchase_note"; } break;
				case 'Purchase Order': { $type = 'PO';  $page = "purchase_order"; } break;
				case 'Sales Quotation': { $type = 'RQ';  $page = "sales_quotation"; } break;
				case 'Quotation': { $type = 'SQ';  $page = "quotation"; } break;
				case 'Sales Order': { $type = 'SO';  $page = "sales_order"; } break;
				case 'Purchase Batch': { $type = 'PB';  $page = "purchase_batch"; } break;
				case 'Payment Voucher': { $type = 'PV';  $page = "purchase_payment_voucher"; } break;
				case 'Sales Receipt': { $type = 'OR';  $page = "sales_receipt"; } break;
				case 'Invoice': { $type = 'INV';  $page = "sales_invoice"; } break;
				case 'Delivery': { $type = 'DO';  $page = "sales_delivery_order"; } break;
				case 'Purchase Requisition': { $type = 'PR';  $page = "purchase_requisition"; } break;
			}
		}
		if(!nn($date)){ $date = today(); }
		$str = "$prefix/".strtoupper($type)."/".zerofill($id, $length)."/".date("Ymd", strtotime($date));
		if($link) $str = "<a href='$page?f=$option&id=$id'>$str</a>";
		return $str;
	}
	function currency_convert($from, $to, $amount=1, $date=''){
		$date = $date==''?today():$date;
		$rate = R::findOne("", "from_currency=? AND to_currency=? AND date>=?", [$from, $to, $date]);
		if(!$rate){
			return $amount;
		}
		return $amount * $rate->rate;
	}
	function userList($filter=""){
		return toArray("id, u_fullname", "sys_user", "", "id", "u_fullname");
	}
	function toF($table, $filter){
		return toA($table, "id", "name", $filter);
	}
	function toA($table, $key = "id", $value = "name", $filter = ""){
		return toArray("$key, `$value`", $table, $filter, $key, $value);
	}
	function toAA($table, $key = "id", $value = "arabic_name", $filter = ""){
		return toArray("$key, `$value`", $table, $filter, $key, $value);
	}
	function toArray($fields, $table, $filter, $key, $value){
		$list = array();
		$records = select($fields, $table, $filter);
		while($r = mysqli_fetch_object($records)){
			$list[$r->$key] = $r->$value;
		}
		$list[""] = "";
		return $list;
	}
	function list1($field, $table, $filter = '', $sort = true, $separator = ','){
		$list = '';
		$records = select($field, $table, $filter, $sort?"ORDER BY $field":'');
		while($r = mysqli_fetch_object($records)){
			$list .= (nn($list)?"$separator":'')."'".$r->$field."'";
		}
		return $list;
	}
	function openFilterForm($method = 'post'){
		print "<div align='center' class='dontprint'><form method='$method' id='filter-form'>";
        if(isset($get->f)){
            print "<input type='hidden' name='f' value='{$get->f}' />";
        }
	}
	function closeFilterForm(){
		print "<input type='submit' value='Filter' class='form-control btn btn-success w100'/></form></div>";
	}
	function openForm($method = 'post', $upload = false, $action=''){
		print "<form method='$method' action='$action'".($upload?" enctype='multipart/form-data'":"").">";
	}
	function closeForm($submit_button_value = 'Save', $submit_button_name = 'save', $reset_button = true){
		if($reset_button){       
		  print "<br clear='all' /><hr />
		  	<div class='row'><div align='center' class='col-md-3'></div><div align='center' class='col-md-3'><button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button></div>
		<div align='center' class='col-md-3'><button type='reset' class='form-control btn btn-warning'>Reset</button></div>
		</div>
		</form>";
		} else{
      print "<br clear='all' /><hr /><div align='center' class='col-md-4'></div><div align='center' class='col-md-4'><button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button></div></form>";
		}
	}
	function closeForm2($options = []){
		$options = (object)$options;
		global $function;
		$submit_button_value = isset($options->submit_button_value)?$options->submit_button_value:'Save';
		$submit_button_name = isset($options->submit_button_name)?$options->submit_button_name:'save';
		$submit_button2_value = isset($options->submit_button_value)?$options->submit_button_value:'Save & Add';
		$submit_button2_name = isset($options->submit_button_name)?$options->submit_button_name:'save_add';
		$reset_button = isset($options->reset_button);
		if(($function=='add' || $function == 'add_item') || $reset_button){       
		  print "<br clear='all' /><hr />
			  <div align='center' class='col-md-2'></div>
			  <div align='center' class='col-md-2'>
			  	<button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button>
			  </div>
			  <div align='center' class='col-md-2'>
			  	<button type='submit' name='$submit_button2_name' class='form-control btn btn-success'>$submit_button2_value</button>
			  </div>
				<div align='center' class='col-md-2'>
					<button type='reset' class='form-control btn btn-warning'>Reset</button>
				</div>
			</form>";
		} elseif($function=='add' || $function == 'add_item'){
      print "<br clear='all' /><hr />
      	<div align='center' class='col-md-3'></div>
      	<div align='center' class='col-md-3'>
      		<button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button>
      	</div>
      	<div align='center' class='col-md-3'>
      		<button type='submit' name='$submit_button2_name' class='form-control btn btn-primary'>$submit_button2_value</button>
      	</div>
      </form>";
		} elseif($function=='edit' && $reset_button){       
		  print "<br clear='all' /><hr />
			  <div align='center' class='col-md-3'></div>
			  <div align='center' class='col-md-3'>
			  	<button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button>
			  </div>
				<div align='center' class='col-md-3'>
					<button type='reset' class='form-control btn btn-warning'>Reset</button>
				</div>
			</form>";
		} else{
      print "<br clear='all' /><hr />
      	<div align='center' class='col-md-4'></div>
      	<div align='center' class='col-md-4'>
      		<button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button>
      	</div>
      </form>";
		}
	}
	function closeFormBS($submit_button_value = 'Save', $submit_button_name = 'save', $reset_button = true){
		if($reset_button){       
		  print "<br clear='all' /><hr /><div align='center' class='col-md-6'><button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button></div>
		<div align='center' class='col-md-6'><button type='reset' class='form-control btn btn-warning'>Reset</button></div></form>";
		} else{
      print "<br clear='all' /><hr />
      	<div align='center' class='col-md-2'></div><div align='center' class='col-md-2'><button type='submit' name='$submit_button_name' class='form-control btn btn-success'>$submit_button_value</button></div></form>";
		}
	}
	function form($form, $object){
		$attributes = select("fa.*", "frm_form f, frm_attributes fa", "f_name = '$form' AND f.id = att_form", "ORDER BY att_group, att_position");
		$count = 1;
		$form = "";
		$group = "";
		while ($att = mysqli_fetch_object($attributes)) {
		  if(nn($group) && $group != $att->att_group){
		    $form .= "<tr><td colspan='5'><hr ></td></tr>";
		  }
		  if($group != $att->att_group){
		    $form .= "<tr><td colspan='5'><b>$att->att_group_name</b></td><tr>";
		  }

		  if($count%2==1) {$form .= "<tr>";}
		  $value = $object->{$att->att_field};
		  $form .= "<td>$att->att_title </td><td>";
		  if($att->att_type=='Enum'){
		    if(strpos($att->att_type_helper, ";")){
		      $helper = explode(";", $att->att_type_helper);
		      $form .= selectEnum("name='$att->att_name' class='form-control'", $helper[0], $helper[1], $value);
		    }
		  } elseif($att->att_type=='Dropdown'){
		  	if(strpos($att->att_type_helper, ";")){
		      $helper = explode(";", $att->att_type_helper);
		      $form .= selectOption("name='$att->att_name' class='form-control'", $helper[0], $helper[1], isset($helper[2])?$helper[2]:$helper[1], $value);
		    }
		  } else{
		    $form .="<input type='$att->att_type' required name='$att->att_name' class='form-control' value='$value'/>";
		  }
		  $form .= "</td><td>".space(5)."</td>";

		  if($count%2==0 || $att->att_span==2)  {$form .= "</tr>"; $count = 0;}

		  $count++;
		  $group = $att->att_group;
		}
		return $form;
	}

	function view($controller, $view, $get){
	    $view = R::findOne("frm_view", "v_name=?", array($view));
	    $columns = R::find("frm_view_item", "vi_view=?", array($view->id));
	    $filters = R::find("frm_view_filter", "vf_view=?", array($view->id));
	    $page = is("page", 1, "", FALSE);
	    $offset = 20;

	    $content = "";

	    $qfilter = "";
	    $content = "<form id='filter-form'>";
	    foreach ($filters as $filter) {
	        //f($fname, $name, &$filter, $default, $values){
	        ${$filter->vf_name} = isf($filter->vf_field, $filter->vf_name, $qfilter, $get);
	        $content .= title($filter->vf_name)." <input type='text' name='$filter->vf_name' class='w100' value='${$filter->vf_name}' /> ";
	    }
	    $content .= "<button type='submit'>Filter</button><form>";
	    $nor = num_rows("id", "$view->v_schema", "$qfilter");
	    $nop = ceil($nor/$offset); if($page > $nop) $page = 1;

	    $start = ($page-1)*$offset;

	    $content .= "<table align='center' class='table table-responsive table-striped' width='100%'>
	    <thead><tr><th>#</th>";
	    $colcount = 2;
	    foreach ($columns as $ctitle) {
	        $content .= "<th>$ctitle->vi_title</th>";
	        $colcount++;
	    }

	    $content .= "<th>Action</th></tr>
	    </thead>
	    <tbody>";

	    $rows = select("*", "$view->v_schema", "$qfilter", "LIMIT $start, $offset");

	    $i = $start + 1;
	    while($row = mysqli_fetch_object($rows)){
	    	$content .= "<tr><td>$i</td>";
	        foreach ($columns as $ctitle) {
	            $content .= "<td>".$row->{$ctitle->vi_field}."</th>";
	        }
	        $content .= "<td>".options2($controller, $row->id, array("edit", "remove"))."</td></tr>";
	    	$i++;
	    }
	    $content .= "</tbody>
	    <tfoot>";
	    $content .= paging($colcount, $nop, $nor, $page);
	    $content .= "</tfoot>
	    </table>";

	    return $content;
	}
	function createForm($schema, $includeFields = array()){
		$form = "";
		$fields = tableFieldsArray($schema);
		$rows = array();
	    $i = 1;
	    foreach($fields as $field){
				if(!in_array($field->Field, $includeFields)){
						continue;
				}
				$class = "";
				if($field->Null=='NO'){
					$class .= "required ";
				}
				if(contains($field->Field, 'email')){
					$class .= "email ";
				}
				if(contains($field->Type, 'date') || contains($field->Type, 'time')){
					$class .= $field->Type." ";
				} elseif(contains($field->Type, 'int') || contains($field->Type, 'decimal')){
					$class .= "number ";
				}
				$class = trim($class);
				if(contains($field->Field, 'active') || contains($field->Type, 'tinyint')){
					$rows[$field->Field] = "<tr><td></td><td><input type='checkbox' name='".name($field->Field)."' id='".name($field->Field)."' value='1' />".title($field->Field)."?</td></tr>";
				} elseif(contains($field->Type, 'enum')){
					$rows[$field->Field] = "<tr><td>".title($field->Field)."</td><td>".selectEnum("name='".name($field->Field)."' id='".name($field->Field)."'", $schema, $field->Field)."</td></tr>";
				} elseif($field->Comment!=''){
					$comments = explode(" ", $field->Comment);
					$$rows[$field->Field] = "<tr><td>".title($field->Field)."</td><td>".selectOption("name='".name($field->Field)."' id='".name($field->Field)."'", $field->RefTable, $comments[0], "id")."</td></tr>";
				} elseif(contains($field->Type, 'text')){
					$rows[$field->Field] = "<tr><td>".title($field->Field)."</td><td><textarea name='".name($field->Field)."' id='".name($field->Field)."'  class='$class'></textarea></td></tr>";
				} else{
					$rows[$field->Field] = "<tr><td>".title($field->Field)."</td><td><input type='text' name='".name($field->Field)."' id='".name($field->Field)."'  class='$class' /></td></tr>";
				}
			}
		$form = "<table align='center' class='form grid'>";
		foreach($includeFields as $if){
			$form .= $rows[$if];
		}
		$form .= "</table>";
		return $form;
	}
	function formSave($schema, $includeFields = array(), $method = 'post'){
		$fields = tableFieldsArray($schema);
		$obj = R::dispense($schema);
		foreach($fields as $field){
			if(contains($field->Field, array("entry_by", "entry_time", "modify_by", "modify_time"))){
				$obj->{$field->Field} = $method=='post'?$post->name($field->Field):$get->name($field->Field);
			} elseif(!in_array($field->Field, $includeFields)){
				continue;
			} elseif(contains($field->Type, 'text')){
				$obj->{$field->Field} = addslashes($method=='post'?$post->name($field->Field):$get->name($field->Field));
			} else{
				$obj->{$field->Field} = $method=='post'?$post->name($field->Field):$get->name($field->Field);
			}
		}
		$obj_id = R::store($obj);
		return $obj_id;
	}
	function ot($width="", $class="grid", $id="", $style=""){ // Open Table tag
		print "<table class='$class' ".($width?"width='$width'":"")." ".($id?"id='$id'":"")." ".($style?"style='$style'":"").">";
	}
	function ct($class="grid", $width=""){	// Close Table Tag
		print "</table>";
	}
	function contains($haystak, $needles){
		if(is_array($needles)){
			foreach($needles as $needle){
				if(strpos($haystak, $needle)!==FALSE) return true;
			}
		} elseif(strpos($haystak, $needles)!==FALSE){
			return true;
		}
		return false;
	}
	function name($field, $min_length = 2){
    $names = explode("_", $field);
    $name = "";
    if(count($names)==1) return $field;
    foreach($names as $n){
	    if(strlen($n)<$min_length) continue;
	    $name .= $name!=""?"_":"";
	    $name .= $n;
    }
    return $name;
  }
  function title($field, $min_length = 2){
    return ucwords(str_replace("_", " ", name($field, $min_length)));
  }
  function varName($field){
  	$field = str_replace(" ", "_", $field);
  	$field = strtolower(preg_replace("/[^A-Za-z0-9_]/", '', $field));
  	return $field;
  }
	function title2($field){
		$names = explode("_", ucfirst(trim($field)));
		$name = "";
		$i = 0;
		if(count($names)==1) return $field;
		foreach($names as $n){
			$name .= $name!=""?" ":"";
			$name .= $n;
			$i++;
		}
		return ucwords($name);
	}
	function clear($elm){
		return '<img src="ico/clear_gray.png" width="16px" onclick="$(\''.$elm.'\').val(\'\')" />';
	}
	function feed($name, $data, $url, $method = 'post'){
		print "<form method='$method' id='feed-form' action='$url'><input type='hidden' name='$name' value='$data' /></from><script type='text/javascript'>\$('#feed-form').submit();</script>";
	}
	function addToolBox($name){
		print "<div class='toolbox curved-right' id='$name'>";
		include("system/tools/$name.php");
		print "</div>";
		print "<script type='text/javascript'>
				$('.toolbox-item').draggable({connectToSortable: '#editing-area',  	revert: 'invalid'});
				</script>";
	}
	function gridView($name, $headings, $data, $cells, $width = "100%"){
		echo "<table class='grid' width='$width'>";
		echo "<tr>";
		foreach($headings as $heading){
			echo "<th>$heading</th>";
		}
		echo "</tr>";
		echo "<tr>";
		while($row = mysqli_fetch_object($data)){
			foreach($cells as $cell){
				echo "<td>".$row->$cell."</td>";
			}
		}
		echo "<td>".options('','')."</td></tr>";
		echo "</table>";
	}
	function url(){
		return str_replace("q=", "", $_SERVER['QUERY_STRING']);
	}
	function uri($replace = '', $with = '', $remove = array()){
		$uri = $_SERVER['QUERY_STRING'];
		$not_in = true;
		$uri = explode("&", $uri);
		$ret_uri = "";
		foreach($uri as $q){
			$qe = explode("=", $q);
			if($qe[0] != "q"){
				if($replace != '' && $qe[0] == $replace){
					$ret_uri .= "$qe[0]=$with&";
					$not_in = false;
				} else{
					if(!in_array($qe[0], $remove)){
						$ret_uri .= $q."&";
					}
				}
			}
		}
		if($not_in){
			$ret_uri .= "$replace=$with&";
		}
		return substr($ret_uri,0,-1);
	}
	function marray($v = array()){
	    return is_array($v)?$v:array($v);
	}
	function muri($uri='', $replace = array(), $value = array(), $remove = array()){
		$uri = $uri==''?$_SERVER['QUERY_STRING']:$uri;
		$uri = explode("&", $uri);
		$ret_uri = "";
		$i = 0;
        $replace = marray($replace);
        $value = marray($value);
        $remove = marray($remove);
		foreach($uri as $u){
			$qe = explode("=", $u);
			if($qe[0] == 'q') continue;
			if(in_array($qe[0], $remove)){
			} elseif(in_array($qe[0], $replace)){
				$ret_uri .= ($ret_uri != ""?"&":"")."$qe[0]=".$value[$i++];
				$index = array_search($qe[0], $replace);
				unset($replace[$index]);
				unset($value[$index]);
			} else{
				$ret_uri .= ($ret_uri != ""?"&":"")."$qe[0]".(isset($qe[1])?"=$qe[1]":'');
			}
		}
		$i = 0;
		$value = array_values($value);
		foreach($replace as $re){
			$ret_uri .= ($ret_uri != ""?"&":"")."$re=".$value[$i++];
		}
		return $ret_uri;
	}
	function fs($words = array(), $separator = " ", $ignore = "0"){
		$return = "";
		foreach($words as $word){
			if(nn($word)){
				$return .= $word.$separator;
			}
		}
		return nn($return)?substr($return, 0, -strlen($separator)):$return;
	}
	function fn($fname, $mname = '', $lname ='', $op1 ='', $op2 =''){ //format name
		$name = $fname;
		if($mname <> ""){
			$name .= " $mname";
		}
		if($lname <> ""){
			$name .= " $lname";
		}
		if($op1 <> ""){
			$name .= " $op1";
		}
		if($op2 <> ""){
			$name .= " $op2";
		}
		return $name;
	}
	/*function acls(){
		return "SELECT acls_a.*, acls_p.link, acls_a.appliesto user FROM (sys_acl acls_a JOIN sys_privilege acls_p) WHERE ((acls_a.privilege = acls_p.id) AND (acls_a.utype = 'u'))UNION SELECT acls_a.*, acls_p.link, acls_r.ur_user_id user FROM ((sys_acl acls_a JOIN sys_privilege acls_p) JOIN sys_user_role acls_r) WHERE ((acls_a.privilege = acls_p.id) AND (acls_r.ur_role_id = acls_a.appliesto) AND (acls_a.utype = 'r'))";
	}
	function privileges(){
		return "SELECT pr_g.id gid, pr_g.title gtitle, pr_p.id pid, pr_p.position `position`, pr_p.title ptitle, pr_p.icon icon FROM (sys_privilege pr_g LEFT JOIN sys_privilege pr_p ON ((pr_g.id = pr_p.root))) WHERE ((pr_g.root = 0) AND (pr_g.active = 1) AND (pr_p.active = 1)) ORDER BY pr_g.position,pr_g.title,pr_p.link,pr_p.position";
	}
	function permissions(){
		return "SELECT distinct pe_p.id pid, pe_p.link, pe_p.module, pe_p.target, pe_p.glyphicon, pe_a.privilege privilege, pe_p.link link, pe_p.icon icon, pe_p.title title, pe_p.position position, pe_p.option `option`, pe_p.root root, pe_p.active active, pe_p.hidden hidden, pe_a.access access, pe_a.user user, pe_p.controller controller, pe_p.show_in_frontpage show_in_frontpage FROM (".acls().") pe_a JOIN sys_privilege pe_p WHERE (pe_a.privilege = pe_p.id) AND (pe_a.access=1)";
	}*/

	$accessGranted = array();
	function mbutton($text,$object,$option, $id=false, $color = 'green'){
		global $accessGranted;
		if(in_array($object.$option, $accessGranted)){
			$text = "<a type='submit' class='btn btn-$color' href='$object/$option".($id?"/$id":"")."'>$text</a>";
		} elseif(hasAccess($object, $option)){
			$text = "<a type='submit' class='btn btn-$color' href='$object/$option".($id?"/$id":"")."'>$text</a>";
			array_push($accessGranted, $object.$option);
		}
		return $text;
	}
	function mlink($text,$object,$option, $id=false){
		global $accessGranted;
		if(in_array($object.$option, $accessGranted)){
			$text = "<a href='".BASEURL.APP."/$object/$option".($id?"/$id":"")."'>$text</a>";
		} elseif(hasAccess($object, $option)){
			$text = "<a href='".BASEURL.APP."/$object/$option".($id?"/$id":"")."'>$text</a>";
			array_push($accessGranted, $object.$option);
		}
		return $text;
	}
	/*function hasAccess($object, $option = false){
		global $accessGranted;
		if(uid()==1){
			return true;
		} elseif(in_array($object.$option, $accessGranted)){
			return true;
		} else{
			$options = select(permissions()." AND pe_p.link='$object'".($option?" AND `option`='$option'":"")." AND user=".uid()." AND access=1");
			if($options->num_rows>0){
				array_push($accessGranted, $object.$option);
				return true;
			} else{
				return false;
			}
		}
		return false;
	}*/
	// function hasAccess($object, $function = false){
	// 	global $accessGranted;
	// 	if(uid()==1){
	// 		return true;
	// 	} elseif(in_array($object.$function, $accessGranted)){
	// 		return true;
	// 	} else{
	// 		$option = R::findOne("sys_permission", "user=? AND link=? AND `option`=? AND access=?", [uid(), $object, $function, 0]);
	// 		if($option){
	// 			return false;
	// 		} else{
	// 			$option = R::findOne("sys_permission", "user=? AND link=? AND `option`=? AND access=?", [uid(), $object, $function, 1]);
	// 			if($option){
	// 				array_push($accessGranted, $object.$function);
	// 				return true;
	// 			} else{
	// 				return false;
	// 			}
	// 		}
	// 	}
	// 	return false;
	// }

	function hasAccess($object, $function = false){
		if(!in_array(uid(), [0,23])){
			return true;
		}
		global $accessGranted;
		global $appurl;
		if(uid()==1){
			if($function){
				$option = R::findOne("sys_privilege", "link=? AND `option`=?", [$object, $function]);	
			} else{
				$option = R::findOne("sys_privilege", "link=?", [$object]);	
			}
			
			if(!$option){
				R::freeze(false);
				$sys_privilege_log = R::dispense("sys_privilege_log");
				$sys_privilege_log->user_id = uid();
				$sys_privilege_log->time = now();
				$sys_privilege_log->controller = $object;
				$sys_privilege_log->function = $function;
				$sys_privilege_log->remarks = "Unknown object";
				R::store($sys_privilege_log);
				if(uid() == 1 && role()==1){
					print "<div class='alert alert-danger'>Permisson ($object/$function) does not exists. Click <a href='$appurl/menu/menu/add/1?link=$object&option=$function' target='_blank'>here</a> to create now.</div>";	
				}				
				R::freeze(true);
			}
			return true;
		} elseif(in_array($object.$function, $accessGranted)){
			return true;
		} else{
			$option = R::findOne("sys_permission", "user=? AND link=? AND `option`=?", [uid(), $object, $function]);
			if($option){
				$option = R::findOne("sys_permission", "user=? AND link=? AND `option`=? AND access=?", [uid(), $object, $function, 1]);
				if($option->access == 1){
					array_push($accessGranted, $object.$function);
					return true;
				} else{
					return false;
				}				
			} else{				
				return false;
			}
		}
		return false;
	}

	function controllers($object = '', $not = array(), $size = '24'){
		$object = $object?$object:$get->q;
		$ret_str = "";
		if(uid()==1){
			$options = select("DISTINCT `option`, icon", "sys_privilege", "link='$object' AND controller=1");
		} else{
			$options = select(permissions()." AND link='$object' AND controller=1 AND user=".uid()." AND access=1");
		}
		while($ops = mysqli_fetch_object($options)){
			if(!in_array($ops->option, $not)){
				$ret_str .= "<a class='nfp' href='$object?f=$ops->option";
				$ret_str .= "'><img width='".$size."px' height='".$size."px' src='ico/$ops->icon' /></a> ";
			}
		}
		return $ret_str;
	}
	function options($object = '', $id = false, $not = array(), $size = '16', $button = false){
		$object = $object?$object:$get->q;
		$ret_str = "";
		if(uid()==1){
			$options = select("DISTINCT `option`, icon, title, target, module", "sys_privilege", "link='$object'");
		} else{
			$options = select(permissions()." AND link='$object' AND user=".uid()." AND access=1");
		}
		while($ops = mysqli_fetch_object($options)){
			if(!in_array($ops->option, $not)){
				if($button){
					$ret_str .= "<input type='button' onclick='redir(\"".BASEURL.'/'.(nn($ops->module)?"/$ops->module":"")."$object/$ops->option";
					if($id){
						$ret_str .= "/$id";
					}
					$ret_str .= "\")' value='$ops->title' />";
				} else{
					$ret_str .= "<a title='$ops->title' target='$ops->target' href='".BASEURL.APP.'/'.(nn($ops->module)?"/$ops->module":"")."$object/$ops->option";
					if($id){
						$ret_str .= "/$id";
					}
					$ret_str .= "'><img width='".$size."px' height='".$size."px' src='".BASEURL.'/'.(nn($ops->module)?"/$ops->module":"")."ico/$ops->icon' /></a> ";
				}
			}
		}
		return $ret_str;
	}
	function makeUri($controller, $function=false, $id=false, $extra=false){
		return BASEURL.APP."/$controller".($function?"/$function":'').($id?"/$id":'').($extra?"/$extra":'');
	}
	function ops($object = '', $id = false, $in = [], $target = ''){
		return options2($object, $id, $in, '16', false, 'btn btn-success','', $target);
	}
	function options2($object = '', $id = false, $in = array(), $size = '16', $button = false, $btn_class = 'btn btn-success', $text = '', $target = ''){
		global $controller;
		$object = $object?$object:$controller;
		$ret_str = "";
		if(uid()==1){
			// $options = select("`option`, glyphicon, icon, title, target, module", "sys_privilege", "link='$object' AND `option` IN ('".implode("','", $in)."')", 'ORDER BY position');
			$options = select("DISTINCT `option`, glyphicon, icon, title, target, module", "sys_privilege", "link='$object' AND `option` IN ('".implode("','", $in)."')", 'ORDER BY position');
		} else{
			$options = select("SELECT DISTINCT `option`, glyphicon, icon, title, target, module FROM sys_permission WHERE link='$object' AND user=".uid()." AND `option` IN ('".implode("','", $in)."') AND access=1 ORDER BY position");
		}
		while($ops = mysqli_fetch_object($options)){
			if(in_array($ops->option, $in)){if($button)
				{
					if(strpos($ops->option, 'edit') !== FALSE) $btn_class = "btn btn-primary btn-sm";
					if(strpos($ops->option, 'remove') !== FALSE || strpos($ops->option, 'del') !== FALSE || strpos($ops->option, 'erase') !== FALSE) $btn_class = "btn btn-danger btn-sm";
					$ret_str .= "<button class='$btn_class' onclick='redir(\"".BASEURL.APP.(nn($ops->module)?"/$ops->module":"")."/$object/$ops->option";
					if($id){
						$ret_str .= "/$id";
					}
					$ret_str .= "\")'>".($text?$text:$ops->title)."</button> ";
				} else{
					$ret_str .= "<a target='".($target?$target:$ops->target)."' href='".ROOT.(nn($ops->module)?"/$ops->module":"")."/$object/$ops->option";
					if($id){
						$ret_str .= "/$id";
					}
					$ret_str .= "'>";
					if(nn($ops->glyphicon)){
						$ret_str .= "<i class='$ops->glyphicon'></i>";
					} else{
						$ret_str .= "<img width='".$size."px' height='".$size."px' src='".ROOT.(nn($ops->module)?"/$ops->module":"")."ico/$ops->icon' />";
					}
					$ret_str .= "</a> ";
				}
			}
		}
		return $ret_str;
	}
	function now(){
		return date("Y-m-d H:i:s", time());
	}
	function today(){
		return date("Y-m-d", time());
	}
	function ctime(){
		return date("H:i:s", time());
	}
	function days($from_date = '', $to_date){
		$begin = new DateTime($from_date);
		$end = new DateTime($to_date);

		$interval = DateInterval::createFromDateString('1 day');
		return $period = new DatePeriod($begin, $interval, $end);
	}
	function hoursdiff($ds, $de){
		$dStart = new DateTime($ds);
		$dEnd  = new DateTime($de);
		 // use for point out relation: smaller/greater
		$dDiff = $dStart->diff($dEnd);
		$hours = $dDiff->h + ($dDiff->days*24);
		return $hours;
	}
	function daydiff($ds, $de){
		$dStart = new DateTime($ds);
		$dEnd  = new DateTime($de);
		 // use for point out relation: smaller/greater
		$dDiff = $dStart->diff($dEnd)->format('%R%a') + 0;
		return $dDiff;
	}

	function rec_debit($amount, $source, $ref){
		insert("transactions", "t_time, t_user, t_amount, r_source, t_ref, t_type, t_payment_type, t_first_payment", "NOW(), ".userid().", '$amount', '$source', '$ref', 'Debit', '$type', $firstpayment");
	}
	function pageBreak(){
		echo '<div class="page-break"></div>';
	}
	//Date format
	function dt($time){
		return (trim($time)!="" && $time!=null && $time!='0000-00-00' && $time!='0000-00-00 00:00:00') ? date("Y-m-d", strtotime($time)) : false;
	}
	function df($time){
		return (trim($time)!="" && $time!=null && $time!='0000-00-00' && $time!='0000-00-00 00:00:00') ? date("d M, Y", strtotime($time)) : false;
	}
	function dfd($time)
	{
		return (trim($time) != "" && $time != null && $time != '0000-00-00' && $time != '0000-00-00 00:00:00')
			? date("d", strtotime($time))
			: false;
	}
	function dfh($time){
		return (trim($time)!="" && $time!=null && $time!='0000-00-00' && $time!='0000-00-00 00:00:00') ? date("d-M-Y", strtotime($time)) : false;
	}
	function df2($date){
		if(nn($date)){
			return date("d-m-Y", strtotime($date));
		}
		return '';
	}
	function df3($date){
		if(nn($date)){
			return date("d M y", strtotime($date));
		}
		return '';
	}
	function now2(){
		return date("d-m-y H:i:s", time());
	}
	function eolToBreak($text){
		return str_replace(PHP_EOL, '<br>', $text);
	}
	//Number format
	function nf($num, $digit=2, $negetive_in_parenthesis = false){
		if($num == 0){
			return '';
		}
		if(abs($num) < 0.001){
			return '';
		}
		if($negetive_in_parenthesis && $num<0){
			return "(".number_format(abs($num), $digit).")";
		} else{
			return number_format($num, $digit);
		}
	}
	function nfz($num, $digit=2, $negetive_in_parenthesis = false){
		if($negetive_in_parenthesis && $num<0){
			return "(".number_format(abs($num), $digit).")";
		} else{
			return number_format($num, $digit);
		}
	}
	function nf0($num, $digit=0, $negetive_in_parenthesis = false){
		return nfz($num, $digit, $negetive_in_parenthesis);
	}
	function nfq($num){
		return $num + 0;
	}
	function ago($time1, $time2 = '', $precision = 6){
		return get_date_diff($time1, $time2, $precision);
	}
	function get_date_diff( $time1, $time2 = '', $precision = 6 ) {
		if($time2==''){
			$time2 = now();
		}
		// If not numeric then convert timestamps
		if( !is_int( $time1 ) ) {
			$time1 = strtotime( $time1 );
		}
		if( !is_int( $time2 ) ) {
			$time2 = strtotime( $time2 );
		}
		// If time1 > time2 then swap the 2 values
		if( $time1 > $time2 ) {
			list( $time1, $time2 ) = array( $time2, $time1 );
		}
		// Set up intervals and diffs arrays
		$intervals = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );
		$diffs = array();
		foreach( $intervals as $interval ) {
			// Create temp time from time1 and interval
			$ttime = strtotime( '+1 ' . $interval, $time1 );
			// Set initial values
			$add = 1;
			$looped = 0;
			// Loop until temp time is smaller than time2
			while ( $time2 >= $ttime ) {
				// Create new temp time from time1 and interval
				$add++;
				$ttime = strtotime( "+" . $add . " " . $interval, $time1 );
				$looped++;
			}
			$time1 = strtotime( "+" . $looped . " " . $interval, $time1 );
			$diffs[ $interval ] = $looped;
		}
		$count = 0;
		$times = array();
		foreach( $diffs as $interval => $value ) {
			// Break if we have needed precission
			if( $count >= $precision ) {
				break;
			}
			// Add value and interval if value is bigger than 0
			if( $value > 0 ) {
				if( $value != 1 ){
					$interval .= "s";
				}
				// Add value and interval to times array
				$times[] = $value . " " . $interval;
				$count++;
			}
		}
		// Return string with times
		return implode( ", ", $times );
	}

	function isMySQLFunc($func){
		$funcs = array('CURTIME()', 'CURDATE()', 'NOW()');
		if(in_array($func, $funcs)){return true;}
		return false;
	}

	//Join MySQL Clauses
	function joinFilter(&$filter, $tojoin, $logic = "AND"){
		if(nn($filter)){
			$filter = "($filter) $logic $tojoin";
		} else{
			$filter .= "$tojoin";
		}
	}

	//filter name, attribute name, filter variable, array of values, default value
    function isf($fname, $name, &$filter, $values, $default='', $exact = false){
    	global $c;
    	$value = '';
		if(isset($values->{$name})){
			$value = $values->{$name};
			// $_SESSION[$name] = $values->{$name};
			// $value = $_SESSION[$name];
		} 
		// else{
		// 	$value = $default;
		// }
		// elseif(isset($_SESSION[$name])){
		// 	$value = $_SESSION[$name];
		// } else{
		// 	$_SESSION[$name] = $default;
		// 	$value = $_SESSION[$name];
		// }

        $value = $c->real_escape_string($value);
        if(nn($value)){
            $filter .= $filter==""?" $fname":" AND $fname";
            $filter .= $exact?"='$value' ": " LIKE '$value%' ";
        }
        return $value;
    }
    function isf2($fname, $name, &$filter, $values, $default='', $exact = false){
    	global $c;
		if(isset($values->{$name})){
			$_SESSION[$name] = $values->{$name};
			$value = $_SESSION[$name];
		} 
		// else{
		// 	$value = $default;
		// }
		elseif(isset($_SESSION[$name])){
			$value = $_SESSION[$name];
		} else{
			$_SESSION[$name] = $default;
			$value = $_SESSION[$name];
		}

        $value = $c->real_escape_string($value);
        if(nn($value)){
            $filter .= $filter==""?" $fname":" AND $fname";
            $filter .= $exact?"='$value' ": " LIKE '$value%' ";
        }
        return $value;
    }
    //If object has specified attribute, it will return the attribute value if 'return value' is specified otherwise it will return true
    //If the attribute does not exists and there's no return value it will return false otherwise the return 'return value'
    function hasAttribute($object, $attribute, $return_value = false){
    	if(isset($object->{$attribute})){
    		if($return_value){
    			return $object->{$attribute};
    		} else{
    			return true;
    		}
    	} else{
    		if($return_value){
    			return $return_value;
    		} else{
    			return false;
    		}
    	}
    }
    function hasAttribute3($object, $attribute, $return_bool = false){
    	if(isset($object->{$attribute})){
    		if(!$return_bool){
    			return $object->{$attribute};
    		} else{
    			return true;
    		}
    	} else{
    		if(!$return_bool){
    			return '';
    		} else{
    			return false;
    		}
    	}
    }
    //If object has specified attribute it will return the 'return value' otherwise empty string
    function hasAttribute2($object, $attribute, $return_value = ''){
    	if(isset($object->{$attribute})){
    		return $return_value;
    	} else{
    		return '';
    	}
    }
	function iss($name, $default = ""){	//wrapper isset()
		return isset($_SESSION[$name])?$_SESSION[$name]:$default;
	}
	function is($name, $default = "", $method = "get", $remember = TRUE){	//wrapper isset()
		global $get, $post;

		if(isset($get->q)){
			if($method == 'post'){
				$default = isset($post->$name)?(nn($post->$name)?$post->$name:(isset($_SESSION[$get->q."_$name"])?$_SESSION[$get->q."_$name"]:$default)):(isset($_SESSION[$get->q."_$name"])?$_SESSION[$get->q."_$name"]:$default);
			} elseif($method == "get"){
				$default = isset($get->$name)?(nn($get->$name)?$get->$name:(isset($_SESSION[$get->q."_$name"])?$_SESSION[$get->q."_$name"]:$default)):(isset($_SESSION[$get->q."_$name"])?$_SESSION[$get->q."_$name"]:$default);
			}
			$_SESSION[$get->q."_$name"] = $default;
		} else{
			if($method == "post"){
				$default = isset($post->$name)?(nn($post->$name)?$post->$name:$default):$default;
			} elseif($method == "get"){
				$default = isset($get->$name)?(nn($get->$name)?$get->$name:$default):$default;
			}
		}

		return $default;
	}
	function is2($name, $default = "", $method = ""){	//wrapper isset()
		if($method == "post"){
			$default = isset($post->$name)?$post->$name:$default;
		} elseif($method == "get"){
			$default = isset($get->$name)?$get->$name:$default;
		} else{
			$default = isset($_REQUEST[$name])?$_REQUEST[$name]:$default;
		}
		return $default;
	}
	function adminonly(){
		if($_SESSION[APP.'_role']!=2){
			die("<h3>Admin area!</h3><h2>You must have admin privilege to access this page!</h2>");
		}
	}
	function useronly(){
		if($_SESSION[APP.'_role']<2){
			die("<h2>You must be logged in as a user to access this page!</h2>");
		}
	}
	function roleonly($role=1){
		if(is_numeric($role)) {$name = getFieldValue("role", "name", "id='$role'");}
			else {$name = $role; $role = getFieldValue("role", "id", "name='$role'");}
		if($_SESSION[APP.'_role']!=$role){
			die("<h2>You must be logged in as a(an) <i>$name</i> to access this page!</h2>");
		}
	}
	function ordinal($num){
		$num = $num + 0;
		if(is_int($num)){
			$last_digit = substr($num,strlen($num."")-1);
			if($num < 1){
				return $num."th";
			} elseif($last_digit == 1){;
				return $num."st";
			} elseif($last_digit == 2){
				return $num."nd";
			} elseif($last_digit == 3){
				return $num."rd";
			} else{
				return $num."th";
			}
		}
		return "";
	}
	function staffId(){
		$emp = select("*", "user_staff", "user_id=".uid()."");
		if($emp){
			if($emp->num_rows){
				$e = mfo($emp);
				return $e->staff_id;
			} else{
				return 0;
			}
		}
		return 0;
	}
	function cuid(){
		return getFieldValue("sales_customer", "id", "c_user_id=".uid());
	}
	function suid(){
		return getFieldValue("purchase_supplier", "id", "s_user_id=".uid());
	}
	function uid(){
		return iss(APP.'_id', 0);
	}
	function aid(){
		$account = R::load("sys_user", uid());
		return $account->account_id;
	}
	function upin(){
		return $_SESSION[APP.'_pin'];
	}
	function loggedin(){
		return isset($_SESSION[ROOT.'_loggedin'])?true:false;
	}
	function rid(){
		$roles = select("*", "sys_user_role", "ur_user_id=".uid());
		$role = mysqli_fetch_object($roles);
		return $role->ur_role_id;
		//return getFieldValue("sys_user_role", "ur_role_id", "ur_user_id=".uid());
	}
	function role(){
		return $_SESSION[APP.'_role'];
	}
	function site($default = ''){
		return isset($get->site)?$get->site:(isset($_SESSION[APP.'_site'])?$_SESSION[APP.'_site']:$default);
	}
	function sitename($id = false){
		if($id){
			return getFieldValue("sys_sites", "s_name", "id=$id");
		} else{
			return $_SESSION[APP.'_sitename'];
		}
	}
	function rolename($id=''){
		$rid = $id!=''?$id:rid();
		return getFieldValue("sys_role", "r_name", "id=$rid");
	}
	function rolecode(){
		if(isset($_SESSION[APP.'_rolecode'])){
			return $_SESSION[APP.'_rolecode'];
		} else{
			$rid = rid();
			return getFieldValue("sys_role", "code", "id=$rid");
		}
	}
	function bid(){
		return $_SESSION[APP.'_branch'];
	}
	function username($id = FALSE){
		if($id){
			$user = R::load("sys_user", $id);
			return $user->u_fullname;
		} else{
			return iss(APP.'_fullname', 'Guest User');
		}		
	}
	function theme($theme = '', $overwrite = true){
		if($theme == ''){
			return $_SESSION[APP.'_theme'];
		} elseif(!isset($_SESSION[APP.'_theme']) || $overwrite){
			$_SESSION[APP.'_theme'] = $theme;
		}
	}
	function isDate($date){
		return preg_match('/^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date);
	}
	function digitOnly($data){
		return preg_replace("/[^0-9.]/", "", $data);
	}
	function ds($name, $date = '', $optional = false){
		if($date == ''){
			$date = today();
		}

		$syear = substr($date,0,4);
		$smon = substr($date,5,2);
		$sday = substr($date,8,2);
		
		$data = createSelectOption("id='".$name."_day' onChange='setDate(\"$name\")' ".($optional == true?"":"required")." class='form-control date-form-control form-control date-form-control-day' onKeyUp='setDate(\"$name\")'", 1, 31, $sday==''?date("d", time()):$sday, 2, 'Day');
		$data .= "<select id='".$name."_mon' onChange='setDate(\"$name\")' class='form-control date-form-control form-control date-form-control-mon' ".($optional == true?"":"required")." onKeyUp='setDate(\"$name\")'>".($optional == true?"<option value=''>Month</option>":"").getMonthList($smon==''?date("m", time()):$smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDate(\"$name\")' class='form-control date-form-control form-control date-form-control-year' ".($optional == true?"":"required")." onKeyUp='setDate(\"$name\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear, 4, 'Year');
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDate(\"$name\");
				function setDate(obj){
					console.log(obj);
					val = $('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val().replace(/\D/g,'');
					if(val.length == 10){
						$('#'+obj).val(val);
					} else{
						$('#'+obj).val('');
					}
				}
			</script>";
		return $data;
	}
	function dateSelector($name, $sday='', $smon='', $syear=''){
		if($sday!='' && $smon==''){
			$syear = substr($sday,0,4);
			$smon = substr($sday,5,2);
			$sday = substr($sday,8,2);
		}
		$data = createSelectOption("id='".$name."_day' onChange='setDate(\"$name\")' class='form-control date-form-control form-control date-form-control-day' onKeyUp='setDate(\"$name\")'", 1, 31, $sday==''?date("d", time()):$sday, 2);
		$data .= "<select id='".$name."_mon' onChange='setDate(\"$name\")' class='form-control date-form-control form-control date-form-control-mon' onKeyUp='setDate(\"$name\")'>".getMonthList($smon==''?date("m", time()):$smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDate(\"$name\")' class='form-control date-form-control form-control date-form-control-year' onKeyUp='setDate(\"$name\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDate(\"$name\");
				function setDate(obj){
					val = $('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val();
					if(val.length == 10){
						$('#'+obj).val(val);
					} else{
						$('#'+obj).val('');
					}
				}
			</script>";
		return $data;
	}
	function dateSelectorOptional($name, $sday='', $smon='', $syear=''){
		if($sday!='' && $smon==''){
			$syear = substr($sday,0,4);
			$smon = substr($sday,5,2);
			$sday = substr($sday,8,2);
		}
		$data = createSelectOption("id='".$name."_day' onChange='setDateOptional(\"$name\")' class='form-control date-form-control' onKeyUp='setDateOptional(\"$name\")'", 1, 31, $sday, 2, 'SELECT');
		$data .= "<select id='".$name."_mon' onChange='setDateOptional(\"$name\")' class='form-control date-form-control' onKeyUp='setDateOptional(\"$name\")'><option>SELECT</option>".getMonthList($smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDateOptional(\"$name\")' class='form-control date-form-control' onKeyUp='setDateOptional(\"$name\")'", date("Y", time()) - 1, date("Y", time())+10, $syear, 4, 'SELECT');
		$data .= "<input type='hidden' name='$name' id='$name' /> <input type='hidden' name='_$name' id='_$name' />
			<script type='text/javascript'>
				setDateOptional(\"$name\");
				function setDateOptional(obj){
					val = $('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val();
					if(val.length == 10){
						$('#'+obj).val(val);
						$('#_'+obj).val($('#'+obj+'_day').val() + '-' + $('#'+obj+'_mon option:selected').text() + '-' + $('#'+obj+'_year').val());
					} else{
						$('#'+obj).val('');
						$('#_'+obj).val('');
					}
				}
			</script>";
		return $data;
	}
	$setDateM = false;
	function monthSelector($name, $sday='', $smon='', $syear='', $myear = 1930){
		global $setDateM;

		if($sday!='' && $smon==''){
			$syear = substr($sday,0,4);
			$smon = substr($sday,5,2);
			$sday = substr($sday,8,2);
		}
		$data = "<select id='".$name."_mon' onChange='setDateM(\"$name\")' class='form-control date-form-control ".$name."_mon' onKeyUp='setDateM(\"$name\")'>".getMonthList($smon==''?date("m", time()):$smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDateM(\"$name\")' class='form-control date-form-control ".$name."_year' onKeyUp='setDateM(\"$name\")'", $myear, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDateM(\"$name\");";
		if(!$setDateM){
			$setDateM = true;
			$data .= "
				function setDateM(obj){
					//console.log(obj);
					val = $('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon option:selected').val();
					//console.log(val);
					if(val.length == 7){
						$('#'+obj).val(val);
					} else{
						$('#'+obj).val('');
					}
					$('#'+obj).trigger('change');
				}";
		}
		$data .= "</script>";
		return $data;
	}
	function dateSelectorJS($name, $sday='', $smon='', $syear=''){
		if($sday!='' && $smon==''){
			$syear = substr($sday,0,4);
			$smon = substr($sday,5,2);
			$sday = substr($sday,8,2);
		}
		$data = createSelectOption("id='".$name."_day' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1, 31, $sday==''?date("d", time()):$sday, 2);
		$data .= "<select id='".$name."_mon' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'>".getMonthList($smon==''?date("m", time()):$smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= "<input type='hidden' name='$name' id='$name' />";
		return $data;
	}
	function datetimeSelector($name, $time='', $num=''){
		if($time!=''){
			$time = date("Y-m-d H:i:s", time());
		}
		$syear = substr($time,0,4);
		$smon = substr($time,5,2);
		$shour = substr($time,11,2);
		$smin = substr($time,14,2);
		$ssec = substr($time,17,2);
		$sday = substr($time,8,2);
		$data = createSelectOption("id='".$name.$num."_day' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'", 1, 31, $sday==''?date("d", time()):$sday, 2);
		$data .= "<select id='".$name.$num."_mon' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'>".getMonthList($smon==''?date("m", time()):$smon, 2)."</select>";
		$data .= createSelectOption("id='".$name.$num."_year' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= " - ".createSelectOption("id='".$name.$num."_hour' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'", 0, 23, $shour==''?date("H", time()):$shour, 2);
		$data .= ":".createSelectOption("id='".$name.$num."_min' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'", 0, 59, $smin==''?date("i", time()):$smin, 2);
		$data .= ":".createSelectOption("id='".$name.$num."_sec' onChange='setDate(\"$name$num\")' onKeyUp='setDate(\"$name$num\")'", 0, 59, $ssec==''?date("s", time()):$ssec, 2);
		$data .= "<input type='hidden' name='$name".($num?"[]":"")."' id='".$name.$num."' />
			<script type='text/javascript'>
				setDate(\"$name$num\");
				function setDate(obj){
					$('#'+obj).val($('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val()+' '+$('#'+obj+'_hour').val()+':'+$('#'+obj+'_min').val()+':'+$('#'+obj+'_sec').val());
				}
			</script>";
		return $data;
	}
	function timeSelector($name, $time='', $num=''){
		$time = $time==""?time():strtotime($time);
		$smin = date("i", $time);
		$ssec = date("s", $time);
		$shour = date("H", $time);
		$data = createSelectOption("id='".$name.$num."_hour' onChange='setTime(\"$name$num\")' onKeyUp='setTime(\"$name$num\")'", 0, 23, $shour==''?date("H", time()):$shour, 2);
		$data .= ":".createSelectOption("id='".$name.$num."_min' onChange='setTime(\"$name$num\")' onKeyUp='setTime(\"$name$num\")'", 0, 59, $smin==''?date("i", time()):$smin, 2);
		$data .= ":".createSelectOption("id='".$name.$num."_sec' onChange='setTime(\"$name$num\")' onKeyUp='setTime(\"$name$num\")'", 0, 59, $ssec==''?date("s", time()):$ssec, 2);
		$data .= "<input type='hidden' name='$name".($num?"[]":"")."' id='".$name.$num."' />
			<script type='text/javascript'>
				setTime(\"".$name.$num."\");
				function setTime(obj){
					$('#'+obj).val($('#'+obj+'_hour').val()+':'+$('#'+obj+'_min').val()+':'+$('#'+obj+'_sec').val());
				}
			</script>";
		return $data;
	}
	function datetimeSelector_v1($name, $sday='', $smon='', $syear='', $shour='', $smin='', $ssec=''){
		if($sday!='' && $smon==''){
			$syear = substr($sday,0,4);
			$smon = substr($sday,5,2);
			$shour = substr($sday,11,2);
			$smin = substr($sday,14,2);
			$ssec = substr($sday,17,2);
			$sday = substr($sday,8,2);
		}
		$data = createSelectOption("id='".$name."_day' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1, 31, $sday==''?date("d", time()):$sday, 2);
		$data .= "<select id='".$name."_mon' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'>".getMonthList($smon==''?date("m", time()):$smon, 2)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= " - ".createSelectOption("id='".$name."_hour' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 23, $shour==''?date("H", time()):$shour, 2);
		$data .= ":".createSelectOption("id='".$name."_min' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 59, $smin==''?date("i", time()):$smin, 2);
		$data .= ":".createSelectOption("id='".$name."_sec' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 59, $ssec==''?date("s", time()):$ssec, 2);
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDate(\"$name\");
				function setDate(obj){
					$('#'+obj).val($('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val()+' '+$('#'+obj+'_hour').val()+':'+$('#'+obj+'_min').val()+':'+$('#'+obj+'_sec').val());
				}
			</script>";
		return $data;
	}
	function timeSelector_v1($name, $shour='', $smin='', $ssec=''){
		if($shour!='' && $smin==''){
			$smin = substr($shour,3,2);
			$ssec = substr($shour,6,2);
			$shour = substr($shour,0,2);
		}
		$data = " - ".createSelectOption("id='".$name."_hour' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 23, $shour==''?date("H", time()):$shour, 2);
		$data .= ":".createSelectOption("id='".$name."_min' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 59, $smin==''?date("i", time()):$smin, 2);
		$data .= ":".createSelectOption("id='".$name."_sec' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 0, 59, $ssec==''?date("s", time()):$ssec, 2);
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDate(\"$name\");
				function setDate(obj){
					$('#'+obj+'_hour').val()+':'+$('#'+obj+'_min').val()+':'+$('#'+obj+'_sec').val());
				}
			</script>";
		return $data;
	}
	function dateSelector3($name, $sday='', $smon='', $syear=''){
		$data = createSelectOption("id='".$name."_day' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1, 31, $sday==''?date("d", time()):$sday);
		$data .= "<select id='".$name."_mon' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'>".getMonthList($smon==''?date("m", time()):$smon)."</select>";
		$data .= createSelectOption("id='".$name."_year' onChange='setDate(\"$name\")' onKeyUp='setDate(\"$name\")'", 1930, date("Y", time())+30, $syear==''?date("Y", time()):$syear);
		$data .= "<input type='hidden' name='$name' id='$name' />
			<script type='text/javascript'>
				setDate(\"$name\");
				function setDate(obj){
					$('#'+obj).val($('#'+obj+'_year').val()+'-'+$('#'+obj+'_mon').val()+'-'+$('#'+obj+'_day').val());
				}
			</script>";
		return $data;
	}
	function getMonthList($select = 1, $min = 0, $max = 0, $include_year = false){
		$month = "";
		for($i=($min?$min:1);$i<=($max?$max:12);$i++){
			if($i<=12){
				$mon = $i;
				$year = date("Y", time());
			} else{
				$mon = $i-12;
				$year = date("Y", time()) + ceil($mon/12);
			}
			$month .= "<option value='".($include_year?"$year-":"").zerofill($mon,2).($include_year?"-1":"")."' ".($select==$mon?'selected':'').">".date("M".($include_year?" - $year":""), strtotime("$year-$mon-1"))."</option>";
		}
		return $month;
	}
	function firstDay($date=''){
		return firstDate($date);
	}
	function firstDate($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		return date("Y-m-01", strtotime("-1 second", strtotime("+1 month", strtotime( date("Y", $time) . date("m", $time) . "01" ))));
	}
	function lastDate($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		return date("Y-m-d", strtotime('last day of this month', $time));
	}
	function lastDay($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		return date("d", strtotime("-1 second", strtotime("+1 month", strtotime( date("Y", $time) . date("m", $time) . "01" ))));
	}
	function dateEqual($date1, $date2=''){
		//if($date2==''){$date2 = date("Y-m-d",time());}
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		if($time1==$time2){
			return true;
		}
		return false;
	}
	function addDate($date1, $date2=''){
		if($date2==''){$date2 = date("Y-m-d",time());}
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		$timediff = 0;
		if($time1>$time2){
			$timediff = $time1+$time1-$time2;
		} else{
			$timediff = $time2+$time2-$time1;
		}
		return date("Y-m-d",$timediff);
	}
	function subDate($date1, $date2=''){
		if($date2==''){$date2 = date("Y-m-d",time());}
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		$timediff = 0;
		if($time1<$time2){
			$timediff = $time1+$time1-$time2;
		} else{
			$timediff = $time2<$time2-$time1;
		}
		return date("Y-m-d",$timediff);
	}
	function curDay($date=''){
		return addDay(0, $date);
	}
	function nextDay($date=''){
		return addDay(1, $date);
	}
	function prevDay($date=''){
		return subDay(1, $date);
	}
	function curMonth($date=''){
		return date("m", time());
	}
	function nextMonth($date=''){
		return addMonth(1, $date);
	}
	function nextMonthName($date=''){
		return date("M", strtotime(addMonth(1, $date)));
	}
	function prevMonth($date=''){
		return subMonth(1, $date);
	}
	function prevMonthName($date=''){
		return date("M", strtotime(subMonth(1, $date)));
	}
	function curYear($date=''){
		return date("Y", time());
	}
	function nextYear($date=''){
		return addYear(1, $date);
	}
	function prevYear($date=''){
		return subYear(1, $date);
	}
	function day($date=''){
		return date("d", $date==''?time():strtotime($date));
	}
	function month($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		return date("m",$date==''?time():strtotime($date));
	}
	function monthName($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		return date("M",$date==''?time():strtotime($date));
	}
	function monthFullname($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		return date("F",$date==''?time():strtotime($date));
	}
	function year($date=''){
		if($date==''){$date = date("Y-m-d",time());}
		return date("Y",$date==''?time():strtotime($date));
	}
	function addMonth($month, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		$cday = date("d",$time);
		$cmon = date("m",$time);
		$cyear = date("Y",$time);
		$months = ($cyear * 12) + $cmon + $month;
		$month = $months % 12;
		$year = ($months-$month) / 12;
		return date("Y-m-d",strtotime("$year-$month-$cday"));
	}
	function subMonth($month, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		$cday = date("d",$time);
		$cmon = date("m",$time);
		$cyear = date("Y",$time);
		$months = ($cyear * 12) + $cmon - $month;
		$month = $months % 12;
		$year = ($months-$month) / 12;
		return date("Y-m-d",strtotime("$year-$month-$cday"));
	}
	function addHour($hours, $time=''){
		if($time==''){$time = date("h:i:s",time());}
		$time = strtotime($time);
		return date("h:i:s",$time + ($hours*60*60));
	}
	function addDay($day, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		return date("Y-m-d",$time + ($day*24*60*60));
	}
	function subDay($day, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		return date("Y-m-d",$time - ($day*24*60*60));
	}
	function addYear($year, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		$year = date("Y",$time) + $year;
		return date($year."-m-d",$time);
	}
	function subYear($year, $date=''){
		if($date==''){$date = date("Y-m-d",time());}
		$time = strtotime($date);
		$year = date("Y",$time) - $year;
		return date($year."-m-d",$time);
	}
	function createEventLog($event, $etype, $details, $user, $script, $url=''){
        insert("event_log", "event, etype, details, user, date, script, url",
		      "'$event', '$etype', '$details', '$user', NOW(), '$script', '$url'");
    }
	function letterhead($title1, $title2, $width = "100%", $logo = ""){
		$head = "<table class='print' align='center' width='$width'>";
		if($logo != ""){
			$head .= "<tr><td colspan='3' align='center'><img src='images/{$logo}' height='80px'/></td></tr>";
		}
		$head .= "<tr><td width='150px'><img src='images/logo.png' /></td><td align='center' class='btm'><h2>$title1</h2><h4>$title2</h4></td><td width='150px'></td></tr>
<tr><td colspan='3'><div style='width:100%; height:4px; background:#333;'></div></td></tr>
</table>";
		return $head;
	}
	function isonline(){
		if (!$sock = @fsockopen('www.google.com', 80, $num, $error, 5)){
			return true;
		}
		return false;
	}
	function ping($ip, $port = 80){
    $sock = @fsockopen($ip, $port, $num, $error, 5);
    return $sock;
	}
	function pagetitle(){
		if(isset($get->q)){
		  if($get->q=='view'){
		  	echo mktitle($get->view);
		  }  else{
			  $option = isset($get->f)?$get->f:"view";
				$ptitle = mysqli_fetch_object(select("IF(COUNT(title)=0, '', title) as title, link", "sys_privilege", "link='{$get->q}' AND `option`='$option'"));
				if($ptitle->title!=""){
				  return "$ptitle->link;$ptitle->title";
				} else{
					return "";
				  //echo $_SERVER['QUERY_STRING'];
				}
			}
		}else {return "";}
	}
	function build_form($fid){
		$form = select("*", "form","form_id=$fid AND active=1");
		if($form->num_rows) {
			$form = mysqli_fetch_object($form);
			$fields = select("*", "form_field", "form_id=$form->form_id AND active=1", "ORDER BY position");
			if($fields->num_rows) {
				echo "<form action='d' method='post' id='form'>
					<input type='hidden' id='type' name='type' value='$form->table' />
					<input type='hidden' id='action' name='action' value='".strtolower($form->action)."' />
					<input type='hidden' id='url' name='url' value='{$get->q}' />";
				echo "<table class='form'>";
				echo "<tr><th class='header' colspan='2'>$form->form_title</th></tr>";
				while($field = mysqli_fetch_object($fields)){
					echo "<tr><td class='rht'>$field->field_title</td><td class='lft'>";
					switch(strtoupper($field->field_type)){
						case "TEXT": {
							$class = 'undefined';
							echo "<input type='text' name='$field->field_name' id='$field->field_name' size='' value='' ";
							$field->required ? $class="required" : $class="";
							echo "class='$class $field->class' />";
						} break;
						case "COMBO BOX": {
							$a_table = array();
							$class = 'undefined';
							echo "<select name='$field->field_name' id='$field->field_name' ";
							$field->required ? $class="required" : $class="";
							echo "class='$class $field->class' >";
							if($field->value_from=="Table"){
								$tables = explode("!:", $field->value);
								foreach($tables as $table){
									$table = explode(";", $table);
									$a_table[$table[0]] = $table;
								}
								$options = explode(";", $tables[0]);
								echo selectOption($options[0],$options[1],$options[2],$options[3],$options[4]);
							}elseif($field->value_from=="List"){
								$options = explode(";", $field->value);
								echo selectOption($options[0],$options[1],$options[2],$options[3],$options[4]);
							}else{
								$values = explode(",", $field->value);
								foreach($values as $value){
									echo "<option>$value</option>";
								}
							}
							echo "</select>";
							if(nn($field->relies_on)){
								?>
                                <script type="text/javascript">
									$(".<?php echo $field->relies_on; ?>").click(function(){
										$.cookie("option", this.value);
										alert("<?php echo $_COOKIE['option']; setcookie("option", "", time()-1); ?>");
										//aselect("{$field->field_name}", ""+this.value+"", ""+this.value+"", ""+this.value+"", ""+this.value+"", ""+this.value+"");
									});
								</script>
                                <?php
								//$(\"#{$field->field_name}\")(\"\");
								//js("\$(\"#.$field->relies_on\").click(function() { alert('Handler for .click() called.'); });");
								//js("$('.$field->relies_on').click(function {alert(0);});");
								//js("$('.$field->field_name').click(function {alert(0);});");
							}
							//print_r($a_table);
						} break;
						case "RADIO BUTTON": {
							$class = 'undefined';
							if($field->value_from=="List"){
								$values = explode(",", $field->value);
							} elseif($field->value_from=="EnumField"){
								$options = explode(";", $field->value);
								echo radioEnum($field->field_name, $options[0], $options[1], $options[2]);
							}
							if(nn($field->relies_on)){
								alert($field->relies_on);
							}
							/*echo "<select name='$field->field_name'";
							$field->required ? $class="required" : $class="";
							echo "class='$class $field->class' >";
							if(nn($field->value)){
								$options = explode(";", $field->value);
								echo selectOption($options[0],$options[1],$options[2],$options[3],$options[4]);
							}if(nn($field->value)){
								$options = explode(";", $field->value);
								echo selectOption($options[0],$options[1],$options[2],$options[3],$options[4]);
							}else
								$values = explode(",", $field->value);
								foreach($values as $value){
									echo "<option>$value</option>"
								}
							}
							echo "</select>";*/
						} break;
						case "AUTO COMPLETE":{

						} break;
						default:{

						} break;
					}
					echo "</td></tr>";
				}
				echo "<tr><td></td><td class='submission'><input type='submit' value='Submit' />&nbsp;&nbsp;<input type='reset' value='Clear All' /></td></tr>";
				echo "</table></form>";
			} else{
				echo "Form does not contain any field.<br />Click <a href='form_add_field?fid=$form->form_id'>here</a> to add Field(s).";
			}
		} elseif(select("*", "form","form_id=$fid AND active=0")->num_rows) {
			echo "Form inactive";
		} else{
			echo "Form does not exist";
		}

	}

	function upload_v1($files, $name='', $dir = '', $_name='file'){
		$dir = $dir==''?'files/uploads':$dir;
		if(!file_exists($dir)){
			mkdir2($dir);
		}
		if($name == ''){
			$name = $files[$_name]["name"];
			$filename = $name;
		} else{
			$filename = $name.ext($files[$_name]["name"]);
		}
		//print $filename;
		if ($files[$_name]["error"] > 0){
			echo "Return Code: ".$files[$_name]["error"] . "<br />";
			$filename=false;
		}else{
			//if (file_exists("$dir/$filename")){
				//echo $files[$_name]["name"] . " already exists. ";
				//$filename=false;
			//} else {
				move_uploaded_file($files[$_name]["tmp_name"], "$dir/$filename");
				//$filename->name = $name.ext($files["file"]["name"]);
				//$filename->url = "uploads/".$name.ext($files["file"]["name"]);
				//echo "<img src='".$path.time().substr($files["file"]["name"],strlen($files["file"]["name"])-4)."' />";
			//}
		}
		return $filename;
	}

	function mkdir2($path){
		$dirs = explode("/", $path);
		$cur_path = "";
		foreach ($dirs as $dir) {
			$cur_path = $cur_path==""?"$dir":"$cur_path/$dir";
			if(!file_exists($cur_path)){
				mkdir($cur_path);
			}
		}		
	}

	function ext($filename){
		$filename = trim($filename);
		$ext = '';
		$len = 2;
		while(!strpos("a".$ext, ".")){
			$ext = substr($filename,strlen($filename)-$len++);
			if($len>12){$ext=''; break;}
		}
		return $ext;
	}
	function rcopy($src, $dst) {
        // if (file_exists ( $dst ))
            // rrmdir ( $dst );
        if (is_dir ( $src )) {
            mkdir ( $dst );
            $files = scandir ( $src );
            foreach ( $files as $file )
                if ($file != "." && $file != "..")
                    rcopy ( "$src/$file", "$dst/$file" );
        } else if (file_exists ( $src ))
            copy ( $src, $dst );
    }
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file)
                if ($file != "." && $file != "..") rrmdir("$dir/$file");
            rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
    }
    

	function rename_win($oldfile,$newfile) {
	    if (!rename($oldfile,$newfile)) {
	        if (copy ($oldfile,$newfile)) {
	            unlink($oldfile);
	            return TRUE;
	        }
	        return FALSE;
	    }
	    return TRUE;
	}
	/*function upload($files, $name='', $prefix='', $path = 'files'){
		$filename;
		//id($table, $field, $length, $alphanumeric = false, $prefix = "", $suffix = "")
		if($name=='') {$name=id(0, $prefix);}
		if ($files["file"]["error"] > 0){
			echo "Return Code: ".$files["file"]["error"] . "<br />";
			$filename=false;
		}else{
			if (file_exists($path.$name.ext($files["file"]["name"]))){
				echo $files["file"]["name"] . " already exists. ";
				$filename=false;
			} else {
				move_uploaded_file($files["file"]["tmp_name"], $path.$name.ext($files["file"]["name"]));
				$filename->name = $name.ext($files["file"]["name"]);
				$filename->url = $path.$name.ext($files["file"]["name"]);
				//echo "<img src='".$path.time().substr($files["file"]["name"],strlen($files["file"]["name"])-4)."' />";
			}
		}
		return $filename;
	}*/
	/*function reg($var, $val){
		global $registry;
		if(!$val){
			if(isset($registry[$var])){
				$value = $registry[$var];
				unset($registry[$var]);
				return $value;
			} else {
				return 0;
			}
		}
		if(!isset($registry[$var])){
			$registry[$var]=$val;
		} else{
			$registry[$var]+=$val;
		}
	}*/

	function reg($key, $value = '', $description = ''){
		$kv = R::findOne("sys_register", "`key`=?", [$key]);
		if($kv){
			return $kv;
		} else{
			$kv = R::dispense("sys_register");
			$kv->key = $key;
			$kv->value = $value;
			$kv->description = $description;
			R::store($kv);
			return $kv;
		}
	}

	function regUpdate($key, $value = '', $description = ''){
		$kv = R::findOne("sys_register", "`key`=?", [$key]);
		if($kv){
			$kv->value = $value;
			R::store($kv);
			return $kv;
		} else{
			$kv = R::dispense("sys_register");
			$kv->key = $key;
			$kv->value = $value;
			$kv->description = $description;
			R::store($kv);
			return $kv;
		}
	}

	function regValue($key, $value = '', $description = ''){
		$kv = R::findOne("sys_register", "`key`=?", [$key]);
		if($kv){
			return $kv->value;
		} else{
			return FALSE;
		}
	}

	function isReg($key, $value = '', $description = ''){
		$kv = R::findOne("sys_register", "`key`=?", [$key]);
		if($kv){
			return true;
		}
		return false;
	}

	function includeifexists($filename, $dir = "./pages/", $level = 0, $found = false){
		if (!$found){
			if (is_dir($dir)) {
				if ($dh = opendir($dir)) {
					while (($file = readdir($dh)) !== false) {
						if (filetype($dir."/".$file)=="dir") {
							if($file!="." && $file!=".."){
								if($file=="." || $file==".." || in_array($file, array('ajax', 'forms', 'objects', 'pss', 'print', 'view'))){
								} else{
									$found = includeifexists($filename, $dir."/".$file, $level+1, $found);
								}
							}
						}
						else {
							if($file==$filename){
								include($dir."/".$file);
								$found = true;
								return $found;
							}
						}
					}
				closedir($dh);
				}
			}
		}
		return $found;
	}

	function filelist($dir="."){
		$files = array();
		$folders = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($dir."/".$file)=="dir") {
					if($file!="." && $file!=".."){
						if($file=="." || $file==".."){
						} else{
							array_push($folders, $file);
						}
					}
				}
				else {
					if($file!="." && $file!=".."){
						array_push($files, $file);
					}
				}
			}
		closedir($dh);
		}
		return array($files, $folders);
	}
	function folderlist($dir=".", $folders){
		//$folders = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($dir."/".$file)=="dir") {
					if($file!="." && $file!=".."){
						if($file=="." || $file==".."){
						} else{
							array_push($folders, $dir."/".$file);
							$folders = folderlist($dir."/".$file, $folders);
						}
					}
				}
			}
		closedir($dh);
		}
		return $folders;
	}
	function sum($index, $val=false){
		global $sum;
		if(!$val){
			if(isset($sum[$index])){
				if($sum[$index] == 0){
					return 0;
				}
				return $sum[$index];
			} else {
				return 0;
			}
		}
		if(!isset($sum[$index])){
			$sum[$index]=$val;
		} else{
			$sum[$index]+=$val;
		}
	}
	function counta($index, $get=false){
		global $counta;
		if($get){
			if(isset($counta[$index])){
				return $counta[$index];
			} else {
				return 0;
			}
		}
		if(!isset($counta[$index])){
			$counta[$index]=1;
		} else{
			$counta[$index]++;
		}
	}

	function uniqueName($input, $table, $field, $options = []){
		$uname = name($input);
		$try = 0;
		while(exists($table, "`$field` = '$uname'")){
			if($try == 0){
				$uname .= date("y", time());	
			} elseif($try == 1){
				$uname .= date("m", time());	
			} elseif($try == 2){
				$uname .= date("d", time());	
			} else{
				$uname .= rand(10,99);	
			}
			$try++;
		}
		return $uname;
	}

	function pin($table, $field){
		global $c;
		$uniquepin = false;
		$pin = rand(1,9).rand(0,9).substr(time(),4,6).rand(0,9).rand(0,9);
		$tries = 0;
		while(!$uniquepin){
			$tries++;
			$q = $c->query("SELECT COUNT(*) as isexist FROM $table WHERE $field={$pin}");
			$p = mysqli_fetch_object($q);
			if ($p->isexist==0){
				$uniquepin = true;
				return $pin;
			}
		}
	}

	function acode($length=10, $table = "sys_registration", $field = "r_verification"){
		global $c;
		$uniquepin = false;
		$tries = 0;
		while(!$uniquepin){
			$tries++;
			$pin = pina($length);
			$q = $c->query("SELECT * FROM $table WHERE $field='{$pin}'");
			if ($q->num_rows==0){
				$uniquepin = true;
				return $pin;
			}
		}
	}
	function pina($length=12){
		$acode = "";
		$ch = 1;
		for($i=0;$i<$length;$i++){
			$ch = rand(1,6);
			if($ch==1 || $ch==4){
				$acode .= chr(rand(65,90));
			} elseif($ch==2 || $ch==5){
				$acode .= chr(rand(97,122));
			} elseif($ch==3 || $ch==6){
				$acode .= rand(0,9);
			}
		}
		return $acode;
	}
	function zerofill($num, $digit){
		$zeros = $digit - strlen($num);
		for($i=0;$i<$zeros;$i++){
			$num = "0".$num;
		}
		return $num;
	}
	function js($js){
		echo "<script type='text/javascript'>$js</script>";
	}
	function cjsf($func){ //call javascript function
		js("$func();");
	}
	function mktitle($name){
		return ucwords(str_replace(array("_")," ",$name));
	}
	//===============================
	function secchk(){
		if(!isset($indexloaded)){die('');}
	}
	function operator($code){
		$code = substr($code,0,3);
		$qr = mysqli_fetch_object(select("id", "operator", "code='{$code}'"));
		return $qr->id;
	}
	function enumVals($table,$field,$sorted=true,$upper=false){
		global $c;
		$result=$c->query('SHOW COLUMNS FROM '.$table);
		$types = array();
		while($tuple=mysqli_fetch_object($result)){
			if($tuple->Field == $field){
				$types=$tuple->Type;
				$beginStr=strpos($types,"(")+1;
				$endStr=strpos($types,")");
				$types=substr($types,$beginStr,$endStr-$beginStr);
				$types=str_replace("'","",$types);
				$types=explode(',',$types);
				if($sorted)
					sort($types);
				break;
			}
		}
		return $types;
	}
	function se($table, $name, $select = '', $options = []){
		$field = preg_replace("/[^A-Za-z0-9_]/", '', $name);;
		$attribute = "name='$name' id='$name' class='selectpicker form-control'";
		return selectEnum($attribute, $table, $field, $select);
	}
	function selectEnum($attribute, $table, $field, $select="", $only=array(), $sorted=true, $upper=false, $optional=false, $optional_vlaue=''){
		$types = enumVals($table,$field,$sorted,$upper);
		if(!is_array($select)){
			$select = [$select];
		}
		$options = "";
		if($optional){
			$options = "<option>$optional_vlaue</option>";
		}
		foreach($types as $type){
			if(count($only)>0 && !in_array($type, $only)) continue;
			$options .= "<option";
			if(in_array($type, $select)) $options .= " selected='selected' ";
			$options .= ">".$type."</option>";
		}
		return "<select $attribute>".$options."</select>";
	}
	function se2($table, $field, $select="", $sorted=true, $upper=false, $optional=false){
		return "<select class='form-control' name='$field'>".selectEnum2($table, $field, $select, $sorted, $upper, $optional)."</select>";
	}

	function selectEnum2($table, $field, $select="", $sorted=true, $upper=false, $optional=false){
		$types = enumVals($table,$field,$sorted,$upper);
		$options = "";
		if($optional){
			$options = "<option></option>";
		}
		foreach($types as $type){
			$options .= "<option";
			if($select==$type) $options .= " selected='selected' ";
			$options .= ">".$type."</option>";
		}
		return $options;
	}
	function radioEnum($name,$table,$field,$select,$sorted=true,$upper=false){
		$types = enumVals($table,$field,$sorted,$upper);
		$options = "";
		foreach($types as $type){
			$options .= "<input type='radio' value='$type' name='$name' class='$name' ";
			if($type==$select){
				$options .= " checked='checked' ";
			}
			$options .= " />".$type."<br />";
		}
		return $options;
	}
	function selectOptionV2($options = array()){
		$options = (object)$options;
		$content = "<select".(isset($options->attributes)?" $options->attributes":"").">";
		if(isset($options->schema) && isset($options->column)){
			$dataField = $options->column;
			$valueField = hasAttribute($options, 'valueColumn', $options->column);
			$additional_column = isset($options->additionalColumns)?", ".implode($options->additionalColumns):"";
			$data = select("$valueField as value, $dataField as data$additional_column", $options->schema, hasAttribute($options, 'filter', ''), hasAttribute($options, 'extra', ''));
			$content .= hasAttribute2($options, 'optional', '<option></option>');
			while($row = mysqli_fetch_object($data)){
				$content .= "<option value='".$row->value."'";
				if(isset($options->select)){
					if(is_array($options->select)){
						if(in_array(trim($row->value), $options->select) || in_array(trim($row->data), $options->select)){
							$content .= " selected='selected' ";
						}
					} elseif($options->select != ""){
						if(trim($row->value)==trim($options->select) || trim($row->data)==trim($options->select)){
							$content .= " selected='selected' ";
							unset($content->select);
						}
					}
				}
				if(isset($options->additionalColumns)){
					$value = 'a_address';
					foreach ($options->additionalColumns as $key=>$value) {
						$content .= " $key='".$row->$value."'";
					}
				}
				$content .= ">".$row->data."</option>";

			}
		} else{
			return $content."<option>Not enough information.</option>"."</select>";
		}
		return $content."</select>";
	}

	function lg($name, $select='', $attr = [], $group=''){
		if($group == '')
			$group = preg_replace("/[^A-Za-z0-9 ]/", '', $name);
		$options = ['dataField'=>'`value`', 'valueField'=>'', "filter"=>"`group`='$group'"];
		foreach ($attr as $key => $value) {
			$options[$key] = $value;
		}
		$options = array_merge($options, $attr);
		return sop2($name, $select, $options, "list");
	}

	function listGroup($group, $select='', $attr = []){
		$group_name = preg_replace("/[^A-Za-z0-9 ]/", '', $group);
		$options = ['dataField'=>'`value`', 'valueField'=>'', "filter"=>"`group`='$group_name'"];
		foreach ($attr as $key => $value) {
			$options[$key] = $value;
		}
		$options = array_merge($options, $attr);
		return sop3($group, $select, $options, "list");
	}

	function selectFromArrayKeyValue($array, $select){
		$options = "";
		foreach($array as $key => $value){
			$options .= "<option";
			if($key==$select){
				$options .= " selected='selected'";
			} elseif($value==$select){
				$options .= " selected='selected'";
			}
			$options .= " value='$key'>$value</option>";
		}
		return $options;
	}
	function sop($name, $select = "", $table = ""){
		return selectOptionPicker($name, $table, $select);
	}
	function sop2($name, $select = "", $options = [], $table = ""){
		return selectOptionPicker2($name, $table, $select, $options);
	}
	function sop3($name, $select = "", $options = [], $table = ""){
		return selectOptionPicker2($name, $table, $select, $options, false);
	}
	
	function s($options = []){
		$params = (object)$options;
		if(count($options)<1){
			return "<span class='alert alert-danger'>INVALID</span>";
		} elseif(!isset($params->name)){
			return "<span class='alert alert-danger'>Name is required</span>";
		}

		$params->table = hasAttribute($params, 'table', $params->name);
		/*$class = hasAttribute3($options, 'class', '');
		$options->attribute = "name='$name' id='$name' class='form-control w150 selectpicker $name $class' data-live-search='true'";*/
		$class = "";
		if(hasAttribute($params, 'required')){
			$class = "required";
		}
		$params->attribute = "name='$params->name' id='$params->name' class='form-control w150 number selectpicker $params->name $class' data-live-search='true'";
		$params->dataField = hasAttribute($params, 'dataFields', 'name');
		$params->dataFieldSeparator = hasAttribute($params, 'dataFieldSeparator', ' - ');
		$dataFields = explode(",", $params->dataField);
		if(count($dataFields)>1){
			$params->dataField = "";
			foreach ($dataFields as $dataField) {
				$params->dataField .= ($params->dataField != ""?", '$params->dataFieldSeparator', ":"").$dataField;
			}
			$params->dataField = "CONCAT($params->dataField)";
		}
		$params->valueField = hasAttribute($params, 'valueField', 'id');
		$params->extraFields = hasAttribute3($params, 'extraFields', '');
		$params->select = hasAttribute3($params, 'select', '');
		$params->filter = hasAttribute3($params, 'filter');
		$params->extra = hasAttribute3($params, 'extra', '');
		$params->optional = hasAttribute3($params, 'optional');
		$params->optional_value = hasAttribute3($params, 'optional_value');
		$params->delay = hasAttribute($params, 'delay');
		//dd($params);
		return selectOption2($params);
	}
	function selectOptionPicker($name, $table = "", $select = "", $filter = "", $dataField = "name", $valueField = "id", $extra = "", $optional = false, $optional_value = ''){
		$table = ifnull($table, $name);
		$attribute = "name='$name' id='$name' class='form-control form-control-fluid w150 required number selectpicker $name' data-live-search='true'";
		return selectOption($attribute, $table, $dataField, $valueField, $select, $filter, $extra, $optional, $optional_value);
	}
	function selectOptionPicker2($name, $table = "", $select = "", $options = [], $live = true){
		$options = (object)$options;
		$options->table = $table==""?$name:$table;
		/*$class = hasAttribute3($options, 'class', '');
		$options->attribute = "name='$name' id='$name' class='form-control w150 selectpicker $name $class' data-live-search='true'";*/
		$class = "";
		$attr = "";
		if(isset($options->class)){
			$class = $options->class;
		}
		if(isset($options->attr)){
			$attr = $options->attr;
		}
		$width = "w150";
		if(isset($options->width)){
			$width = $options->width;
		}
		if(hasAttribute($options, 'required')){
			$class .= (nn($class)?" ":"")."required";
		}

		$clean_name = preg_replace("/[^A-Za-z0-9 _]/", '', $name);

		$options->multiple = hasAttribute($options, 'multiple');
		if($live){
			if(isset($options->delay)){
				$class = "$class selectpicker-delayed";
			} else{
				$class = "$class selectpicker";
			}
			$options->attribute = "name='$name".($options->multiple?"[]":'')."' id='$name' class='form-control form-control-fluid number $width $clean_name $class' data-live-search='true' $attr";
		} else{
			$options->attribute = "name='$name".($options->multiple?"[]":'')."' id='$name' class='form-control form-control-fluid number $width $clean_name $class' $attr";
		}
		

		$options->dataField = hasAttribute($options, 'dataField', 'name');
		$options->valueField = hasAttribute($options, 'valueField', 'id');
		$options->extraFields = hasAttribute3($options, 'extraFields', '');
		$options->select = $select;
		$options->active = hasAttribute3($options, 'active', 'active');
		$options->filter = hasAttribute3($options, 'filter');
		$options->extra = hasAttribute3($options, 'extra');
		// $options->extra = hasAttribute3($options, 'extra', ($options->dataField=='name'?'ORDER BY name':''));
		//if($options->dataField == 'currency'){ dd($options->extra); }
		$options->optional = hasAttribute3($options, 'optional');
		$options->optional_value = hasAttribute3($options, 'optional_value');
		$options->options_only = hasAttribute($options, 'options_only');
		//dd($options);
		return selectOption2($options);
	}
	function selectOption($attribute, $table, $dataField, $valueField = "", $select = "", $filter = "", $extra = "", $optional = false, $optional_value = ''){
		if($valueField=="") {$valueField=$dataField;}
		$values = select("$valueField as value, $dataField as data", $table, $filter, $extra);
		$options = "";
		if($optional){
			$options = "<option value=''>$optional_value</option>";
		}
		if($values){
			while($option = mysqli_fetch_object($values)){
				$options .= "<option value='".$option->value."'";
				if(isset($select)){
					if(is_array($select)){
						if(in_array(trim($option->value), $select) || in_array(trim($option->data), $select)){
							$options .= " selected='selected' ";
						}
					} elseif($select != ""){
						if(trim($option->value)==trim($select) || trim($option->data)==trim($select)){
							$options .= " selected='selected' ";
							unset($select);
						}
					}
				}
				$options .= ">".$option->data."</option>";
			}
		}
		//if ($new){
			//$options .= "<option onclick=\"newEntry('$table','$dataField')\">* NEW *</option>";
		//}
		return "<select $attribute>".$options."</select>";
	}
	function selectOptionVue($attribute, $table, $select = "", $dataField = "name", $valueField = "id", $filter = "", $extra = "", $optional = false, $optional_value = ''){
		if($valueField=="") {$valueField=$dataField;}
		$values = select("$valueField as value, $dataField as data", $table, $filter, $extra);
		$options = "";
		if($optional){
			$options = "<option value=''>$optional_value</option>";
		}
		if($values){
			while($option = mysqli_fetch_object($values)){
				$options .= "<option v-bind:selected='\"".$option->value."\"==\"".$select."\"' value='".$option->value."'";
				if(isset($select)){
					if(is_array($select)){
						if(in_array(trim($option->value), $select) || in_array(trim($option->data), $select)){
							$options .= " selected='selected' ";
						}
					} elseif($select != ""){
						if(trim($option->value)==trim($select) || trim($option->data)==trim($select)){
							$options .= " selected='selected' ";
							//unset($select);
						}
					}
				}
				$options .= ">".$option->data."</option>";
			}
		}
		//if ($new){
			//$options .= "<option onclick=\"newEntry('$table','$dataField')\">* NEW *</option>";
		//}
		return $options;
	}
	function selectOption2($settings = []){
		$settings = (object)$settings;
		$attribute = hasAttribute3($settings, 'attribute');
		$table = hasAttribute3($settings, 'table');
		$dataField = hasAttribute3($settings, 'dataField');
		$valueField = hasAttribute3($settings, 'valueField');
		$extraFields = hasAttribute3($settings, 'extraFields');
		$select = hasAttribute3($settings, 'select');
		$active = hasAttribute3($settings, 'active');
		$multiple = hasAttribute3($settings, 'multiple');
		$filter = hasAttribute3($settings, 'filter');
		$extra = hasAttribute3($settings, 'extra', false);
		$optional = hasAttribute3($settings, 'optional');
		$optional_value = hasAttribute3($settings, 'optional_value');
		$options_only = hasAttribute3($settings, 'options_only');
		if(!$optional_value) $optional_value = "Please Select";
		if($active){
			$filter .= (nn($filter)?" AND":"")." active=1";
		}

		if($valueField=="") {$valueField = $dataField;}
		$values = select("$valueField as value, $dataField as data".(nn($extraFields)?", $extraFields":""), $table, $filter, $extra);
		$options = "";
		if($optional){
			$options = "<option value=''>$optional_value</option>";
		}

		if($values){
			while($option = mysqli_fetch_object($values)){
				$options .= "<option value='".$option->value."'";
				if(nn($extraFields)){
					$ef = explode(",", $extraFields);
					foreach ($ef as $f) {
						$f =  preg_replace("/[^A-Za-z0-9 _]/", '', $f);
						$options .= " data-$f='".$option->$f."' ";
					}
					
				}
				if(isset($select)){
					if(is_array($select)){
						if(in_array(trim($option->value), $select) || in_array(trim($option->data), $select)){
							$options .= " selected='selected' ";
						}
					} elseif($select != ""){
						if(trim($option->value)==trim($select) || trim($option->data)==trim($select)){
							$options .= " selected='selected' ";
							unset($select);
						}
					}
				}
				$options .= ">".$option->data."</option>";
			}
		}
		//if ($new){
			//$options .= "<option onclick=\"newEntry('$table','$dataField')\">* NEW *</option>";
		//}
		if($options_only){
			return $options;
		} else{
			return "<select $attribute ".($multiple?"multiple":"").">".$options."</select>";
		}		
	}
	function selectOptionWithoutSelect($table, $dataField, $valueField = "", $select = "", $filter = "", $extra = "", $optional = false, $optional_value = ''){
		if($valueField=="") {$valueField=$dataField;}
		$values = select("$valueField as value, $dataField as data", $table, $filter, $extra);
		$options = "";
		if($optional){
			$options = "<option>$optional_value</option>";
		}
		if($values)
		while($option = mysqli_fetch_object($values)){
			$options .= "<option value='".$option->value."'";
			if(isset($select)){
				if(is_array($select)){
					if(in_array(trim($option->value), $select) || in_array(trim($option->data), $select)){
						$options .= " selected='selected' ";
					}
				} elseif($select != ""){
					if(trim($option->value)==trim($select) || trim($option->data)==trim($select)){
						$options .= " selected='selected' ";
						unset($select);
					}
				}
			}
			$options .= ">".$option->data."</option>";

		}
		return $options;
	}
	function tableFields($table, $include = [], $ignore = []){
		global $c;
		$arr_field = array();
		$fields=$c->query('SHOW FULL COLUMNS FROM '.$table);
		$strict = count($include)>0?true:false;
		while($field=mysqli_fetch_object($fields)){
			if($strict){
				if(!in_array($field->Field, $include)) continue;
			}
			if(in_array($field->Field, $ignore)) continue;
			array_push($arr_field,$field->Field);
		}
		return $arr_field;
	}
    function tableFieldsRaw($table){
	    global $c;
	    $fields=$c->query('SHOW FULL COLUMNS FROM '.$table);
	    return $fields;
    }
    function tableReferences($table){
        global $c;
        global $database;
        $refs = $c->query("SELECT * FROM information_schema.`KEY_COLUMN_USAGE` WHERE `CONSTRAINT_SCHEMA`='$database' AND `TABLE_NAME`='$table' AND `CONSTRAINT_NAME` NOT IN('PRIMARY', 'UNIQUE')");
        return $refs;
    }
    function columnReference($table, $column){
        global $config;
        //global $database;
        $refs = select("SELECT * FROM information_schema.`KEY_COLUMN_USAGE` WHERE `CONSTRAINT_SCHEMA`='".$config->db_name."' AND `TABLE_NAME`='$table' AND column_name='$column' AND `CONSTRAINT_NAME` NOT IN('PRIMARY', 'UNIQUE')");
        if($refs->num_rows>0){
            $ref = mysqli_fetch_object($refs);
            return array($ref->REFERENCED_TABLE_NAME, $ref->REFERENCED_COLUMN_NAME);
        }
        return false;
    }
  function tableFieldsArray($table){
    $rawfields=tableFieldsRaw($table);
      $fields = array();
      while($rawfield=mysqli_fetch_object($rawfields)){
          $ref = columnReference($table, $rawfield->Field);
          $field = array('Field'=>$rawfield->Field,
              'Type'=>$rawfield->Type,
              'Null'=>$rawfield->Null,
              'Field'=>$rawfield->Field,
              'Comment'=>$rawfield->Comment,
              'RefTable'=>$ref?$ref[0]:NULL,
              'RefColumn'=>$ref?$ref[1]:NULL);
          array_push($fields,(object)$field);
      }
    return $fields;
  }
  function hasField($table, $field){
  	global $c;
		$arr_field = array();
		$fields = $c->query('SHOW FULL COLUMNS FROM '.$table.' WHERE Field="'.$field.'"');
		return $fields->num_rows>0;
  }
  function tabulate($table = '', $hide = ['id']){
  	$rows = select("*", $table);
  	$dataRows = "";
  	$heading = "";
  	$first = TRUE;
  	foreach ($rows as $row) {
  		$dataRows .= "<tr>";
  		foreach ($row as $key => $value) {
  			if($first){
  				$heading .= "<th>".title($key)."</th>";
  			}
  			$dataRows .= "<td>$value</td>";
  		}
  		$dataRows .= "</tr>";
  		$first = FALSE;
  	}
  	$data = "<h1 class='center'>List of ".title($table)."</h1><table class='table table-striped table-bordered'>";
  	$data .= "<tr>$heading</tr>";
  	$data .= $dataRows;
  	return $data;
  }
	function tabulate2($heading, $data, $fields, $footer, $options = array()){
		$con = "<table align='center' class='tablesorter'>";
		$con .= "<thead><tr>";
		if(in_array('sl', $options)){ $con .= "<th>Sl.</th>"; }
		foreach($heading as $h){ $con .= "<th>$h</th>"; }
		$con .= "</tr></thead>";
		$con .= "<tbody>";
		foreach($footer as $ft){
			eval("$".$ft[1]."=0;");
		}
		$i = 1;
		while($row = mysqli_fetch_array($data)){
			$con .= "<tr>";
			if(in_array('sl', $options)){ $con .= "<td>$i</td>"; }
			foreach($fields as $f){
				if(is_array($f)){
					//eval('$con .= "<td>".$f[1]($row[$f[0]])."</td>";');
					$con .= "<td>".call_user_func($f[1],$row[$f[0]])."</td>";
				} else{
					$con .= "<td>".$row[$f]."</td>";
				}
			}
			$con .= "</tr>";
			foreach($footer as $ft){
				eval($ft[0].";");
			}
			$i++;
		}
		$con .= "</tbody>";
		$con .= "<tfoot><tr>";
		$i = 1;
		foreach($heading as $h){
			$con.= "<th>";
			foreach($footer as $ft){
				$key = key($footer);
				if($key==$i){
					//$con .= $$ft[1];
					$con .= $$footer[$key][1];
				}
				next($footer);
			}
			$con .= "</th>";
			$i++;
		}
		$con .= "</tr></tfoot>";
		$con .= "</table>";
		return $con;
	}
	function view_deletable($fields, $table, $filter = "", $options = "", $extra = "", $cal = ""){
		$acl = select($fields, $table, $filter, $options);
		$fields = array();
		$maps = array();
		$field_count = $acl->field_count;
		$link = "";
		$linkfield = "";
		$linkto = "";
		$viewid = strip($table, ",");
		echo "<table class='table' id='view-table-$viewid[0]' align='center'>";
		while($f = mysqli_fetch_field($acl)){
			array_push($fields, $f->name);
		}
		for($i=0; $i<$field_count; $i++){
			/*$field_maps = select("*", "field_map", "table = '$table' AND field = '{$fields[$i]}'");
			if($field_maps->num_rows > 0){
				$field_map = mysqli_fetch_object($field_maps);
				if($field_map->field == $fields[$i]){
					if(nn($field_map->link)){
						$link = $field_map->link;
						$linkfield = $field_map->field;
						$linkto = $field_map->linkfield;
					}
					array_push($maps, $field_map->alias);
				}
			} else{*/
				array_push($maps, mktitle($fields[$i]));
			//}
		}
		echo "<tr class='tableheading'><th>No.</th>";
		for($i=0; $i<$field_count; $i++){
			echo "<th class='th$fields[$i]'>".$maps[$i]."</th>";
		}
		echo "</tr>";
		$count = 1;
		while($a = mysqli_fetch_object($acl)){
			echo "<tr><td>$count</td>";
			for($i=0; $i<$field_count; $i++){
				$td = "<td>".$a->$fields[$i]."</td>";
				if(nn($link)){
					if($linkfield==$fields[$i]){
						echo "<td class='$fields[$i]'><a href='$link"."=".$a->$linkto."'>".$a->$fields[$i]."</a></td>";
					}
				}
				echo $td;
			}
			echo $extra;
			echo "</tr>";
			$count++;
		}
		echo "</table>";
	}
	class vForm{
		function __construct($field, $val, $url, $heading){
			$this->$field = $field;
			$this->$val = $val;
			$this->$url = $url;
			$this->$heading = $heading;
		}
		function build(){
			return "<form action=\'d\' method=\'post\'>
				<input type=\'hidden\' name=\'type\' value=\'holiday\' />
				<input type=\'hidden\' name=\'action\' value=\'update\' />
				<input type=\'hidden\' name=\'stat\' value=\'2\' />
				<input type=\'hidden\' name=\'id\' value=\'', h.id, '\' />
				<input type=\'hidden\' name=\'url\' value=\'holiday\' />
				<input type=\'submit\' value=\'Approve\' /></form>";
		}
	}
	function view2($fields, $table, $filter = "", $options = "", $extra = null){
		if($extra){print_r($extra);}
		/*$acl = select($fields, $table, $filter, $options);
		$fields = array();
		$maps = array();
		$field_count = $acl->field_count;
		$link = "";
		$linkfield = "";
		$linkto = "";
		$viewid = strip($table, ",");
		echo "<table class='grid' id='view-table-$viewid[0]' align='center'>";
		while($f = mysqli_fetch_field($acl)){
			array_push($fields, $f->name);
		}
		for($i=0; $i<$field_count; $i++){
			$field_maps = select("*", "field_map", "table = '$table' AND field = '{$fields[$i]}'");
			if($field_maps->num_rows > 0){
				$field_map = mysqli_fetch_object($field_maps);
				if($field_map->field == $fields[$i]){
					if(nn($field_map->link)){
						$link = $field_map->link;
						$linkfield = $field_map->field;
						$linkto = $field_map->linkfield;
					}
					array_push($maps, $field_map->alias);
				}j
			} else{
				array_push($maps, mktitle($fields[$i]));
			}
		}
		echo "<tr class='tableheading'>";
		for($i=0; $i<$field_count; $i++){
			echo "<th class='th$fields[$i]'>".$maps[$i]."</th>";
		}
		echo "</tr>";
		while($a = mysqli_fetch_object($acl)){
			echo "<tr>";
			for($i=0; $i<$field_count; $i++){
				$td = "<td class='$fields[$i]'>".$a->$fields[$i]."</td>";
				if(nn($link)){
					if($linkfield==$fields[$i]){
						echo "<td class='$fields[$i]'><a href='$link"."=".$a->$linkto."'>".$a->$fields[$i]."</a></td>";
					}
				}
				echo $td;
			}
			echo $extra;
			echo "</tr>";
		}
		echo "</table>";*/
	}
	function getxml($fields, $table, $filter = "", $options = "", $extra = ""){
		$acl = select($fields, $table, $filter, $options);
		$fields = array();
		$maps = array();
		$field_count = $acl->field_count;
		$link = "";
		$linkfield = "";
		$linkto = "";
		$viewid = strip($table, ",");
		echo "<$table>";
		while($f = mysqli_fetch_field($acl)){
			array_push($fields, $f->name);
		}
		for($i=0; $i<$field_count; $i++){
			$field_maps = select("*", "field_map", "table = '$table' AND field = '{$fields[$i]}'");
			if($field_maps->num_rows > 0){
				$field_map = mysqli_fetch_object($field_maps);
				if($field_map->field == $fields[$i]){
					if(nn($field_map->link)){
						$link = $field_map->link;
						$linkfield = $field_map->field;
						$linkto = $field_map->linkfield;
					}
					array_push($maps, $field_map->alias);
				}
			} else{
				array_push($maps, ucfirst($fields[$i]));
			}
		}
		//echo "<tr class='tableheading'>";
		//for($i=0; $i<$field_count; $i++){
			//echo "<th class='th$fields[$i]'>".$maps[$i]."</th>";
		//}
		//echo "</tr>";
		while($a = mysqli_fetch_object($acl)){
			echo "<tr>";
			for($i=0; $i<$field_count; $i++){
				$td = "<td class='$fields[$i]'>".$a->$fields[$i]."</td>";
				if(nn($link)){
					if($linkfield==$fields[$i]){
						echo "<td class='$fields[$i]'><a href='$link"."=".$a->$linkto."'>".$a->$fields[$i]."</a></td>";
					}
				}
				echo $td;
			}
			echo $extra;
			echo "</tr>";
		}
		echo "</$table>";
	}
	function getIdFromUser($table){
		getId($table);
	}
	function getId($table, $id = false){
		$object = R::findOne($table, "user=?", [$id?$id:uid()]);
		return $object->id;
	}
	function createUser2($fullname, $email, $phone, $password, $role){
		$hasRole = R::load("sys_role", $role);
		if($hasRole){
			$user = R::dispense("sys_user");
			$user->u_fullname = $fullname;
			$user->u_username = $email;
			$user->u_email = $email;
			$user->u_phone = $phone;
			$user->u_password = md5($password);
			$user->u_date_created = now();
			$user->u_created_by = uid();
			R::store($user);
			if($user){
				insert("sys_user_role", "ur_user_id, ur_role_id", "$user->id, $hasRole->id");
			}

			return $user; 	
		} else{
			return false;
		}		
	}
	function createUser($fullname, $username, $password, $role){
		$hasRole = R::findOne("sys_role", "r_name=?", [$role]);
		if($hasRole){
			$user = R::dispense("sys_user");
			$user->u_fullname = $fullname;
			$user->u_username = $username;
			$user->u_password = md5($password);
			$user->u_date_created = now();
			$user->u_email = $username;
			$user->u_created_by = uid();
			R::store($user);
			if($user){
				insert("sys_user_role", "ur_user_id, ur_role_id", "$user->id, $hasRole->id");
			}

			return $user; 	
		} else{
			return false;
		}		
	}
	function updateUser($id, $fullname, $username, $password = ''){
		$user = R::load("sys_user", $id);
		$user->u_fullname = $fullname;
		$user->u_username = $username;
		if(nn($password)){
			$user->u_password = md5($password);
		}
		R::store($user);

		return $user;
	}
	function getName($table, $id, $field = 'name'){
		if(!$id) return '';
		return getFieldValue($table, "$field", "id=$id");
	}
	function getFieldValue($tables, $field, $criteria="", $options=""){
		$recordset = select($field, $tables, $criteria, $options);
		$record = mysqli_fetch_object($recordset);
		if($record) {
			$fields = explode(".", $field); 
			$column = isset($fields[1])?$fields[1]:$fields[0];
			if(count($fields)>1) {
				return $record->$column;
			} else{
				return $record->$column;
			}
		} else {
			return '';
		}
	}
	function uuid() {
	    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	        // 32 bits for "time_low"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

	        // 16 bits for "time_mid"
	        mt_rand( 0, 0xffff ),

	        // 16 bits for "time_hi_and_version",
	        // four most significant bits holds version number 4
	        mt_rand( 0, 0x0fff ) | 0x4000,

	        // 16 bits, 8 bits for "clk_seq_hi_res",
	        // 8 bits for "clk_seq_low",
	        // two most significant bits holds zero and one for variant DCE1.1
	        mt_rand( 0, 0x3fff ) | 0x8000,

	        // 48 bits for "node"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	    );
	}
	function getNextCount($table, $field, $criteria = ""){
		$max = getMax($table, $field, $criteria);
		return $max;
	}
	function getMax($table, $field, $criteria = ""){
		$max = getFieldValue($table, "MAX($field)", $criteria) + 0;
		return $max;
	}
	function getMin($table, $field, $criteria = ""){
		$min = getFieldValue($table, "MIN($field)", $criteria) + 0;
		return $min;
	}
	function getMaxA($table, $field, $criteria = ""){
		$max = getFieldValue($table, "MAX($field)", $criteria);
		return $max;
	}
	function getMinA($table, $field, $criteria = ""){
		$min = getFieldValue($table, "MIN($field)", $criteria);
		return $min;
	}
	function getSum($tables, $field, $criteria = "", $options=""){
		$recordset = select("IFNULL(SUM($field),0) value", $tables, $criteria, $options);
		$record = mysqli_fetch_object($recordset);
		if($record) {$fields = explode(".", $field); if(count($fields)>1) {return $record->value;} else{return $record->value;}} else {return '';}
	}
	function getSump($tables, $field, $criteria = "", $options=""){
		$recordset = selectp("IFNULL(SUM($field),0) value", $tables, $criteria, $options);
		$record = mysqli_fetch_object($recordset);
		if($record) {$fields = explode(".", $field); if(count($fields)>1) {return $record->value;} else{return $record->value;}} else {return '';}
	}
	function getFunctionValue($function, $parameters = []){
		$params = "";
		foreach ($parameters as $param) {
			$params .= ($params!=""?",":"")."'$param'";
		}
		$rows = select("SELECT $function($params) value");
		if($rows){
			$row = mysqli_fetch_object($rows);
			return $row->value;
		} else{
			false;
		}
	}
    function num_rows($fields, $tables="", $criteria="", $options=""){
    	try {
    		$rows = select($fields, $tables, $criteria, $options);
    		if($rows) return $rows->num_rows;
    	} catch (Exception $e) {
    		return 0;
    	} 
    	return 0;       
    }
    function paging($colcount, $nop, $nor, $page){
        $return = "";
        if($nop>1){
            $return .= "<tr class='pagination-tr'><th colspan='$colcount'>";
            for($j=1;$j<=$nop;$j++){
            	if($page==$j){
            		$return .= "<span class='pagination-page current-page'>$j</span> ";
            	} else{
            		$return .= "<a href='?".muri("", "page", $j)."' class='pagination-page".($page==$j?" current-page":"")."'>$j</a> ";
            	}
            }
            $return .= "</th></tr>";
        }
        return $return;
    }
  function selectJson($fields, $tables="", $criteria="", $options=""){
  	$result = select($fields, $tables, $criteria, $options);
  	$outp = array();
		$outp = $result->fetch_all(MYSQLI_ASSOC);
		return json_encode($outp);
  }
	function select($fields, $tables="", $criteria="", $options=""){
		global $c;
		if($tables==""){
			$query = $fields;
		} else{
			$query = "SELECT ".$fields." FROM ".$tables;
			if($criteria!=""){
				$query.=" WHERE ".$criteria;
			}
			$query.=" ".$options;
		}
		//print($query);
		//print("<option>".$query."</option>");
		return $c->query($query);
	}
	function deleteAllFiles($dir){
		foreach(glob($dir.'*.*') as $v){
    		unlink($v);
		}
	}

	function makeRadioGroup($attr, $options = array(), $values = array(), $select = 0, $zerofill = false){
		$values = count($values)>0?$values:$options;
		$option = "";
		for($i=0;$i<count($options);$i++){
			$option .= "<input type='checkbox' $attr value='{$values[$i]}' data-off='{$values[$i]}' data-on='{$values[$i]}'";
			if($values[$i]==$select) {$option .= "checked='checked'";}
			$option .= ">{$options[$i]}";
		}
		return $option;
	}
	function makeSelectOption($attr, $options = array(), $values = array(), $select = 0, $zerofill = false){
		$values = count($values)>0?$values:$options;
		$option = "<select $attr>";
		for($i=0;$i<count($options);$i++){
			$option .= "<option value='{$values[$i]}' ";
			if($values[$i]==$select) {$option .= "selected='selected'";}
			$option .= ">{$options[$i]}</option>";
		}
		$option .= "</select>";
		return $option;
	}
	function createSelectOption($attr, $start, $end, $select = 0, $zerofill = false, $optional = false){
		$option = "<select ".(strpos($attr, 'class') === FALSE ? "class='form-control date-form-control'" : "")." $attr>";
		if($optional !== false){
			$option .= "<option value=''>$optional</option>";
		}
		for($i=$start; $i<=$end; $i++){
			$option .= "<option ";
			if($i==$select) {$option .= "selected='selected'";}
			$option .= ">";
			if($zerofill) {$option .= zerofill($i, $zerofill);} else {$option .= $i;}
			$option .= "</option>";
		}
		$option .= "</select>";
		return $option;
	}
	function createSelectOption2($attr, $start, $end, $options = array()){
		$option = "<select $attr>";
		for($i=$start; $i<=$end; $i++){
			$option .= "<option ";
			if(isset($options['select'])) {if($i==$options['select']) {$option .= "selected='selected'";}}
			$option .= " value='$i'>".(isset($options['ordinal'])?ordinal($i):$i)."</option>";
		}
		$option .= "</select>";
		return $option;
	}
	function selectp($fields, $tables="", $criteria="", $options=""){
		global $c;
		if($tables==""){
			$query = $fields;
		} else{
			$query = "SELECT ".$fields." FROM ".$tables;
			if($criteria!=""){
				$query.=" WHERE ".$criteria;
			}
			$query.=" ".$options;
		}
		//if(uid()==1)
		print("<div>".htmlentities($query)."</div>");
		//print("<option>".$query."</option>");
		return $c->query($query);
	}
	/*function mfo($result, $error_reporting = true){
		if($result){
			if($result->num_rows){
				return mysqli_fetch_object($result);
			} else{
				if($error_reporting){
					echo 'No records found!'; return false;
				} else{
					return false;
				}
			}
		} else{
			if($error_reporting){
				echo 'There is an error with the request!'; return false;
			} else{
				return false;
			}
		}
	}*/
	function mfos($fields, $tables="", $criteria="", $options=""){
		return mfo(select($fields, $tables, $criteria, $options));
	}
	function update($tables, $values, $criteria=""){
		global $c;
		$query = "UPDATE ".$tables." SET ".$values;
		if($criteria!=""){
			$query.=" WHERE ".$criteria;
		}
		//print($query);
		return $c->query($query);
	}
	function updatep($tables, $values, $criteria=""){
		global $c;
		$query = "UPDATE ".$tables." SET ".$values;
		if($criteria!=""){
			$query.=" WHERE ".$criteria;
		}
		print($query);
		return $c->query($query);
	}
	function del($tables, $criteria=""){
		global $c;
		$query = "DELETE FROM ".$tables;
		if($criteria!=""){
			$query.=" WHERE ".$criteria;
		}
		//print($query);
		msg("The ".ucfirst(str_replace("_", " ", $tables))." has been successfully deleted!", true);
		return $c->query($query);
	}
	function RBTrash(&$object){
		$object->trash = 1;
		R::store($object);
	}
	function RBRestore(&$object){
		$object->trash = 0;
		R::store($object);
	}
	function delp($tables, $criteria=""){
		global $c;
		$query = "DELETE FROM ".$tables;
		if($criteria!=""){
			$query.=" WHERE ".$criteria;
		}
		print($query);
		msg("The ".ucfirst(str_replace("_", " ", $tables))." has been successfully deleted!", true);
		return $c->query($query);
	}
	function insert($table, $fields = "", $values, $storeinevent = false, $extra = ""){
		global $c;
		if($fields!=""){
			$fields = " (".$fields.")";
		}
		$query = "INSERT INTO ".$table.$fields." VALUES(".$values.") $extra";
		//print($query)."<br />";
		$c->query($query);
		/*if($storeinevent){
			event($events['di'],"Database Insert");
		}*/
		return $c->insert_id;
	}
	function insertp($table, $fields = "", $values, $storeinevent = false){
		global $c;
		if($fields!=""){
			$fields = " (".$fields.")";
		}
		$query = "INSERT INTO ".$table.$fields." VALUES(".$values.")";
		print($query)."<br />";
		$c->query($query);
		/*if($storeinevent){
			event($events['di'],"Database Insert");
		}
		return $c->insert_id;*/
	}
	function inserts($table, $fields = "", $values, $storeinevent = false){
		global $c;
		if($fields!=""){
			$fields = " (".$fields.")";
		}
		$query = "INSERT INTO ".$table.$fields." VALUES(".$values.")";
		print($query)."<br />";
		// $c->query($query);
		/*if($storeinevent){
			event($events['di'],"Database Insert");
		}
		return $c->insert_id;*/
	}
	function replace($table, $fields = "", $values, $storeinevent = false){
		global $c;
		if($fields!=""){
			$fields = " (".$fields.")";
		}
		$query = "REPLACE INTO ".$table.$fields." VALUES(".$values.")";
		$c->query($query);
		return $c->insert_id;
	}
	function replacep($table, $fields = "", $values, $storeinevent = false){
		global $c;
		if($fields!=""){
			$fields = " (".$fields.")";
		}
		$query = "REPLACE INTO ".$table.$fields." VALUES(".$values.")";
		print($query)."<br />";
		$c->query($query);
		if($storeinevent){
			event($events['di'],"Database Insert");
		}
		return $c->insert_id;
	}
	function exists($tables, $filters = ""){
		$data = select("*", $tables, $filters);
		if ($data->num_rows > 0){
			return true;
		} else{
			return false;
		}
	}
	function id($table, $field, $length, $alphanumeric = false, $prefix = "", $suffix = ""){
		$uid = "";
		$unique = false;
		$first = true;
		while(!$unique){
			if($alphanumeric){

			} else{
				if($first){
					$uid .= rand(1,9);
					$first = false;
				}
				for($l = 2; $l<=$length; $l++){
					$uid .= rand(0,9);
				}
			}
			$uid = $prefix.$uid.$suffix;
			$unique = !isin($table, "$field = '$uid'");
			//if(!$unique) alert(0);
		}
		return $uid;
	}
	function event($type, $details){
		global $c;
		global $events;
		$type = $events[$type];
		$q = insert("event", "type,details,time", "'$type', '$details',now()", true);
	}
	function reportError($e){

	}
	function errorTranslation($e){
		global $config;
		$error = $e->getMessage();
		$errorMessage = "";
		if(strpos($error, "Cannot delete or update a parent row: a foreign key constraint fails") !== FALSE){
			$table = getSubString($error, "FOREIGN KEY (`", "`)");
			$referencedTable = getSubString($error, $config->db_name."`.`", "`,");
			return "Sorry you cannot delete this <b>".title($table)."</b> as it is linked with some <b>".title($referencedTable).'(s)</b>. You must remove the <b>'.title($referencedTable).'(s)</b> before removeing this <b>'.title($table).'.</b>';
		} else{
			return $error;
		}
	}
	function error($e){
		die("<div class='alert alert-danger'>$e</div>");
	}
	function getSubString($from, $start, $separator = ","){
    $offset = strpos($from, $start) + strlen($start);
    $length = strpos($from, $separator, $offset);
    return substr($from, $offset, $length - $offset);
	}
	function getfpath($name, $ext = "php"){

	}
	function strip($s,$c){
		$sa = array();
		$t = "";
		for ($i=0; $i<strlen($s); $i++){
			if($s[$i] != $c){
				$t .= $s[$i];
			} else{
				array_push($sa, $t);
				$t = "";
			}
		}
		if ($t != ""){
			array_push($sa, $t);
			$t = "";
		}
		return $sa;
	}
	//nn function checks whether the argument passed is null or not - returns true if not null, if default value passed it returns default value.
	function nn($var=null, $default = false){
		if(is_array($var)){
			if(count($var)>0){
				return true;
			}
			return false;
		}
		return (trim($var)!="" && $var!=null && $var!='0000-00-00' && $var!='0000-00-00 00:00:00' && $var!='00:00:00') ? ($default?$default:true) : ($default?$default:false);
	}

	function dbnull($val){
		return nn($val)?$val:'null';
	}

	function idbnull($field, $value){
		if(nn($value)){
			return "$field = '$value'";
		} else{
			return "$field IS NULL";
		}
	}

	//if value is null or if value is zero reuturns parameter 2
	function ifnull($var = null, $value = '', $zeroisnothing = true){
		if(!nn($var)){
			return $value;
		} elseif($zeroisnothing && $var==0){
			return $value;
		}
		return $var;
	}
	function sadm(){
		global $sadm;
		return (uid() == $sadm)? true : false;
	}
	function filename($filepath = ""){
		if($filepath==""){
			$filepath = $_SERVER['SCRIPT_FILENAME'];
		}
		foreach(strip($filepath, "/") as $r){
			if(strpos($r,".php") != false){
				return $r;
			}
		}
	}
	function msg($message = "", $append = false){
		/*if($message == ""){
			if(isset($_COOKIE['msg'])){
				$msg = $_COOKIE['msg'];
				setcookie('msg',"",time()-1);
				return '<span id="msg-text" style="background-color:#0CC; color:#ff0000; padding:2px 10px 0 10px;">'.$msg.'</span>';
			}
		} else{
			if($append){
				if(isset($_COOKIE['msg'])){
					$message = $_COOKIE['msg']."<br />".$message;
				}
			}
			setcookie("msg", $message);
		}*/
	}
	function showmsg(){
		if(isset($_COOKIE['msg'])) {
			$msg = $_COOKIE['msg'];
			setcookie('msg',"",time()-1);
			echo $msg;
		}
	}
	function setmsg($msg){
		setcookie("msg", $msg);
	}
	function dpath(){
		global $rd;
		$dir = "../".$rd;
		if(isset($get->q)){
			$dir .= "/".$get->q;
		}
		if(isset($get->s)){
			$dir .= "/".$get->s;
		}
		return $dir."/";
	}
	function fpath(){
		$fpath = dpath();
		if(isset($get->a)){
			$fpath .= $get->a.".php";
		}
		return $fpath;
	}
	function isDateOneYearOld($date) {
	    // Convert the given date to a DateTime object
	    $givenDate = new DateTime($date);
	    
	    // Get the current date
	    $currentDate = new DateTime();
	    
	    // Calculate the difference between the given date and the current date
	    $difference = $currentDate->diff($givenDate);
	    
	    // Check if the difference is at least 1 year
	    return $difference->y >= 1;  // 'y' represents the number of years
	}


	function reload(){
		echo "<script type='text/javascript'>location.reload();</script>";
	}
	function redir($url){
		echo "<script type='text/javascript'>location.href='{$url}';</script>";
	}
	function settitle($title){
		echo "<script type='text/javascript'>document.title += ' :: {$title}';</script>";
	}
	function alert($msg){
		echo "<script type='text/javascript'>alert('{$msg}');</script>";
	}
	function console_log($msg){
		echo "<script type='text/javascript'>console.log(\"{$msg}\");</script>";
	}
	function close(){
		echo "<script type='text/javascript'>window.close();</script>";
	}
	function idletime(){
		$tdr = select("TIME_TO_SEC(TIMEDIFF(CONCAT(CURDATE(),' ',CURTIME()),
							last_login)) AS t", "user", "id = '".uid()."'");
		$td = mysqli_fetch_object($tdr);
		return $td->t;
	}
	function checkidletime(){
		global $c;
		if(idletime() < 900000 ){
			update("user", "time = CURTIME()", "id = '".uid()."'");
		} else{
			//redir("?q=logout");
		}
	}
	function extractNumber($str){
		$num = "";
		for($i=0; $i< strlen($str); $i++){
			if(is_numeric($str[$i])){
				$num .= $str[$i];
			}
		}
		return $num;
	}
	function createDir($dir){
		$dirs = explode("/", $dir);
		$root = "";
		foreach($dirs as $dir){
			if(!file_exists($root.$dir)) { mkdir($root.$dir); }
			$root .= $dir."/";
		}
	}
	function nid($field, $table, $length = false, $prefix=""){
		$is = select($field, $table, "", "ORDER BY $field DESC LIMIT 1");
		$id = 1;
		if($is->num_rows) { $in = mysqli_fetch_object($is); $id = $in->$field + 1; }
		return $prefix.zerofill($id, $length);
	}
	function space($times){
		$spaces = "";
		for($i=0; $i<$times; $i++){
			$spaces .= "&nbsp;";
		}
		return $spaces;
	}
// To Kilos
function toKilo(&$weight, &$unit){
	switch (strtolower($unit)) {
		case 'gm':{
			if($weight>=1000){
				$weight = $weight / 1000;
				$unit = "kg";
			}
		} break;
		case 'ml':{
			if($weight>=1000){
				$weight = $weight / 1000;
				$unit = "ltr";
			}
		} break;

		default:{
			if($weight>=1000){
				$weight = $weight / 1000;
				$unit = "k$unit";
			}
		} break;
	}
}
	//===================== TO NUMBER
	$nwords = array("zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen", "twenty", 30 => "thirty", 40 => "forty", 50 => "fifty", 60 => "sixty", 70 => "seventy", 80 => "eighty", 90 => "ninety" );
function num2txt($x, $postfix = ""){
	$nums = explode(".", $x);
	$txt = num2txt2($nums[0]);
	if(isset($nums[1])){
		if($nums[1]>0){
			$txt .= " and ".num2txt2($nums[1]);
		}
	}
	return trim("$txt $postfix");
}
function num2txt2($x){
	$x = number_format($x,0,"","");
   global $nwords;
   if(!is_numeric($x))
   {
	   $w = '#';
   }else if(fmod($x, 1) != 0)
   {
	   $w = '#';
   }else{
	   if($x < 0)
	   {
		   $w = 'minus ';
		   $x = -$x;
	   }else{
		   $w = '';
	   }
	   if($x < 21)
	   {
		   $w .= $nwords[$x];
	   }else if($x < 100)
	   {
		   $w .= $nwords[10 * floor($x/10)];
		   $r = fmod($x, 10);
		   if($r > 0)
		   {
			   $w .= '-'. $nwords[$r];
		   }
	   } else if($x < 1000)
	   {
		   $w .= $nwords[floor($x/100)] .' hundred';
		   $r = fmod($x, 100);
		   if($r > 0)
		   {
			   $w .= ' and '. num2txt($r);
		   }
	   } else if($x < 100000)
	   {
		   $w .= num2txt(floor($x/1000)) .' thousand';
		   $r = fmod($x, 1000);
		   if($r > 0)
		   {
			   $w .= ' ';
			   if($r < 100)
			   {
				   $w .= 'and ';
			   }
			   $w .= num2txt($r);
		   }
	   } else {
		   $w .= num2txt(floor($x/1000000)) .' million';
		   $r = fmod($x, 1000000);
		   if($r > 0)
		   {
			   $w .= ' ';
			   if($r < 100)
			   {
				   $word .= 'and ';
			   }
			   $w .= num2txt($r);
		   }
	   }
   }
   return ucfirst($w);
}
function curl($url){
	// $ch = curl_init();
	// curl_setopt($ch, CURLOPT_URL, $url);
	// curl_setopt($ch, CURLOPT_POST, 1);
	// curl_setopt($ch, CURLOPT_POSTFIELDS,
	//             [
	// 				'payee_bank_account_no' => $payee_bank_account_no,
	// 				'payee_bank_name' => $payee_bank_name,
	// 				'payee_identification_id' => $payee_identification_id,
	// 				'payee_name' => $payee_name,
	// 				'transfer_amount' => $transfer_amount,
	// 				'transfer_reason' => $transfer_reason
	//             ]);
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// $server_output = curl_exec ($ch);
	// curl_close ($ch);
	// //var_dump($server_output);
	// return $server_output;
}
function getRemote($request_url, $console_out = false) {
	// print "<a href='$request_url' target='_blank'>Calling: $request_url</a>";
	// console_log($request_url);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $request_url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	$response = curl_exec($ch);
	curl_close($ch);

	return $response;
}
function sendFile($file, $email = false){
	if (function_exists('curl_file_create')) { // php 5.5+
	  $cFile = curl_file_create($file);
	} else { // 
	  $cFile = '@' . realpath($file);
	}
	$post = array('extra_info' => '123456','file_contents'=> $cFile);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$target_url);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$result=curl_exec ($ch);
	curl_close ($ch);

}
function getGeo(){
	return getRemote("http://freegeoip.net/csv/".$_SERVER['REMOTE_ADDR']);
}
function isEmail($email){
	$regex = "/\w+@\w+.\w+/";
	return preg_match($regex, $email);
}
function email($email, $subject, $body, $attachment = '', $embed_image = ''){
	$email = $email;
	require_once '../framework/core/vendor/phpmailer/class.phpmailer.php';
	require_once '../framework/core/vendor/phpmailer/class.smtp.php';

	try {
		$mail = new PHPMailer(true); //New instance, with exceptions enabled

		$mail->IsSMTP();                                      // set mailer to use SMTP
		$mail->SMTPAuth = true;     // turn on SMTP authentication
		$mail->SMTPSecure = '';//"tls";
		$mail->SMTPDebug = 1;
		$mail->Host = smtp;  // specify main and backup server
		$mail->Port = smtp_port;
		$mail->Username = email;  // SMTP username
		$mail->Password = password; // SMTP password

		$mail->From = email;
		$mail->FromName = email_from;
		if(is_array($email)){
			if(count($email)>0){
				foreach($email as $to){
					$mail->AddAddress($to, $to);
				}
			}
		} elseif($email != ""){
			$mail->AddAddress($email, $email);
		}

		if(defined('cc')){
			$mail->AddCC(cc, cc);	
		}
		// $mail->AddBCC('rronyy@gmail.com', 'rronyy@gmail.com');	
		
		$mail->WordWrap = 50;                                 // set word wrap to 50 characters
		$mail->IsHTML(true);                                  // set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $body;
		if(is_array($attachment)){
			if(count($attachment)>0){
				foreach($attachment as $attach){
					$mail->addAttachment($attach);
				}
			}
		} elseif($attachment != ""){
			$mail->addAttachment($attachment);
		}


		if(is_array($embed_image)){
			if(count($embed_image)>0){
				$i = 0;
				foreach($embed_image as $image){
					$mail->AddEmbeddedImage($image, "graph$i");
					$i++;
				}
			}
		} elseif($embed_image != ""){
			$mail->AddEmbeddedImage($embed_image, 'graph');
		}

		if(!$mail->Send())
		{
			 echo "Message could not be sent. <p>";
			 echo "Mailer Error: " . $mail->ErrorInfo;
			 exit;
		}

		//echo "Your verification code has been sent to $email.";
	} catch (phpmailerException $e) {
		echo $e->errorMessage();
	}
}
function email2($email, $subject, $body, $attachment = ''){
	$email      = $email;
	require_once '../../lib/phpmailer/class.phpmailer.php';
	require_once '../../lib/phpmailer/class.smtp.php';

	try {
		$mail = new PHPMailer(true); //New instance, with exceptions enabled

		$mail->IsSMTP();                                      // set mailer to use SMTP
		$mail->SMTPAuth = true;     // turn on SMTP authentication
		$mail->SMTPSecure = "tls";
		$mail->Host = "just129.justhost.com";  // specify main and backup server
		$mail->Port = 465;
		$mail->Username = "webmaster@agdits.com";  // SMTP username
		$mail->Password = '$ys1(m12'; // SMTP password

		$mail->From = "webmaster@agdits.com";
		$mail->FromName = "Fresh Meat";
		if(is_array($email)){
			if(count($email)>0){
				foreach($email as $to){
					$mail->AddAddress($to, $to);
				}
			}
		} elseif($email != ""){
			$mail->AddAddress($email, $email);
		}

		$mail->WordWrap = 50;                                 // set word wrap to 50 characters
		$mail->IsHTML(true);                                  // set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $body;
		if(is_array($attachment)){
			if(count($attachment)>0){
				foreach($attachment as $attach){
					$mail->addAttachment($attach);
				}
			}
		} elseif($attachment != ""){
			$mail->addAttachment($attachment);
		}

		if(!$mail->Send())
		{
			 echo "Message could not be sent. <p>";
			 echo "Mailer Error: " . $mail->ErrorInfo;
			 exit;
		}

		//echo "Your verification code has been sent to $email.";
	} catch (phpmailerException $e) {
		echo $e->errorMessage();
	}
}

//DATABASE
function tables(){
  global $config;
	$arr_field = array();
	$tables=select("table_name",  "information_schema.tables", "table_schema = '".$config->db_name."'"); //" AND table_name NOT LIKE 'sys_%'");
	while($table=mysqli_fetch_object($tables)){
		array_push($arr_field,$table->table_name);
	}
	return $arr_field;
}

function getTrashField($table){
	$fields=select("SHOW FULL COLUMNS FROM ".$table." WHERE FIELD LIKE '%trash'");
	if($fields->num_rows>0){
		$field = mysqli_fetch_object($fields);
		return $field->Field;
	}
	return false;
}
function getActiveField($table){
	$fields=select("SHOW FULL COLUMNS FROM ".$table." WHERE FIELD LIKE '%active'");
	if($fields->num_rows>0){
		$field = mysqli_fetch_object($fields);
		return $field->Field;
	}
	return false;
}
function getForeignKeys($table){
    global $database;
	$fields = select("SELECT * FROM information_schema.`KEY_COLUMN_USAGE` WHERE `CONSTRAINT_SCHEMA`='".$database."' AND `TABLE_NAME`='".$table."'AND `CONSTRAINT_NAME` NOT IN('PRIMARY', 'UNIQUE')");
	if($fields->num_rows>0){
		$field = mysqli_fetch_object($fields);
		return $field->Field;
	}
	return false;
}

function translate($en, $lang = ''){
	// return $en;
	if($lang == '' && isset($_SESSION[APP.'_lang'])){
		$lang = $_SESSION[APP.'_lang'];
	} elseif($lang==''){
		$lang = 'en';
	}
	if($lang == 'en'){
		return $en;
	}
	$translations = select("*", "translation", "english='".trim($en)."' AND language='$lang'");

	if($translations->num_rows){
		$translation = mysqli_fetch_object($translations);
		return nn($translation->translate)?$translation->translate:$en;
	} elseif(nn(trim($en))){
		insert("translation", "english, translate, language", "'".trim($en)."', '', '$lang'");
	}
	return $en;
}

function str($en, $lang = ''){
	return translate($en, $lang);
}
function strp($en, $lang = ''){
	print translate($en, $lang);
}

function token($page, $times = 1){ //$times = how man calls can be made
	$token = rand(100, time()).sha1(md5(time()).sha1(time() * uid())).rand(100, time());
	insert("sys_token", "user, page, token, times, valid_till", uid().", '$page', '$token', $times, '".addDay(1)."'");
	return $token;
}

function verifyToken($page, $token){
	$retrived_token = R::findOne("sys_token", "user=? AND page=? AND token=? AND status=?", [uid(), $page, $token, 1]);
	if($retrived_token){
		return true;
	}
	return false;
}

function sendSMS($number, $text){
	require('framework/core/vendor/twilio/Services/Twilio.php');
	$client = new Services_Twilio(TWILIO_SID, TWILIO_TOKEN);
	if(is_array($number)){
		$message_id = array();
		for ($i = 0; $i < count($number); $i++) {
			$recipient = $number[$i];
			if(is_array($text)){
				$msg = $text[$i];
				$message = $client->account->messages->sendMessage(TWILIO_NUMBER, $recipient, $msg);
			} else{
				$message = $client->account->messages->sendMessage(TWILIO_NUMBER, $recipient, $text);
			}
			$message_id[$recipient] =  $message->sid;
		}
	} else{
		$message = $client->account->messages->sendMessage(TWILIO_NUMBER, $number, $text);
		$message_id = $message->sid;
	}

	return $message_id;
}

// 200 = success
// 300 = failed to connect
// 400 = missing parameters
// 401 = Invalid user/pass
// 402 = Insuffient Credit
//17752982,60162763677,200
function sendSMSWebSMS2u($number, $text){
	require_once ("framework/core/vendor/websms2u/sendsms/sms_send_include.php");
	$sms_report = R::dispense("sms_report");
	$sms_report->phone = trim($number);
	$sms_report->text = $text;
	$sms_report->status = 'Processed';
	R::store($sms_report);
  $mysms = new sms();
  $mysms->setUser(WEBSMS2U_USER);
  $mysms->setPass(WEBSMS2U_PASSWORD);
  $APIresponse = $mysms->send("$number", "WebSMS2u", $text, "0", "dipping");
  $response = explode(",", $APIresponse);
  $sms_report->api_response = $APIresponse;
  $sms_report->smsid = $response[0];
  R::store($sms_report);
  return $response;
}

function checkSMSStatus($number, $text){
	require_once ("framework/core/vendor/websms2u/checkstatus/sms_checkstatus_include.php");
  $mysms = new sms();
  $mysms->setUser(WEBSMS2U_USER);
  $mysms->setPass(WEBSMS2U_PASSWORD);
	$APIresponse = $mysms->send ("checkstatus", $number, $text);
  $response = explode(",", $APIresponse);
  return $response;

}

function isSubset($needle, $haystack)
{
	return count(array_intersect($needle, $haystack)) == count($needle);
}
//COMMON FUNCTIONS
function gender($select){
	makeSelectOption($attr, ['Male', 'Female'], [], $select);
}