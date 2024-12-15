<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if(isset($_POST['type'])){ 
	$type = $_POST['type'];
	$appliesto = $_POST['appliesto'];
	if($appliesto=='') die("<h3>No Records Found!</h3>");
	
	$acls = "SELECT * FROM sys_acls";
	$privileges = "SELECT * FROM sys_privileges";
	$permission = "SELECT * FROM sys_permission";

	if($type=="u"){
		$rq = select("DISTINCT p.*, IF(a.access=1, 'checked', '') checked",
			"(($privileges) p LEFT JOIN ($permission) a ON(p.pid=a.privilege AND a.`user` = $appliesto))", "gid<>1", "ORDER BY gtitle, p.link, p.`option`");
	} elseif($type=="r"){
		$rq = select("DISTINCT p.*, IF(a.access=1, 'checked', '') checked",
			"(($privileges) p LEFT JOIN sys_acl a ON(p.pid=a.privilege AND a.utype='r' AND appliesto = '{$appliesto}')), `sys_privilege` s",
			"s.id=pid AND gid<>1", "ORDER BY gtitle, p.link, p.`option`");
		//var_dump($rq);
	}

	$groupname = "";
	$subgroupname = "";

	print "Select All: <font size='-1'> 
			[<a href='javascript:selectall(\"permission\")' style='text-decoration:none;color:#000'> Options </a>] 
			[<a href='javascript:selectall(\"add\")' style='text-decoration:none;color:#000'> Add / Create / New </a>] 
			[<a href='javascript:selectall(\"view\")' style='text-decoration:none;color:#000'> View / List </a>] 
			[<a href='javascript:selectall(\"edit\")' style='text-decoration:none;color:#000'> Edit </a>] 
			[<a href='javascript:selectall(\"remove\")' style='text-decoration:none;color:#000'> Remove </a>] 
			[<a href='javascript:selectall(\"report\")' style='text-decoration:none;color:#000'> Reports </a>] 
		</font>";
	print "<br >";
	print "Clear All: <font size='-1'> 
			[<a href='javascript:clearall(\"permission\")' style='text-decoration:none;color:#000'> Options </a>] 
			[<a href='javascript:clearall(\"add\")' style='text-decoration:none;color:#000'> Add / Create / New </a>] 
			[<a href='javascript:clearall(\"view\")' style='text-decoration:none;color:#000'> View / List </a>] 
			[<a href='javascript:clearall(\"edit\")' style='text-decoration:none;color:#000'> Edit </a>] 
			[<a href='javascript:clearall(\"remove\")' style='text-decoration:none;color:#000'> Remove </a>] 
			[<a href='javascript:clearall(\"report\")' style='text-decoration:none;color:#000'> Reports </a>] 
		</font>";

	while($r=mysqli_fetch_object($rq)){
		//var_dump($r);
		//die();
		if($groupname != $r->gtitle){
			$groupname = $r->gtitle;
			print "<hr /><font size='+1'><b>".title($groupname)."</b></font><font size='-1'> [<a href='javascript:selectall(\"{$r->gid}\")' style='text-decoration:none;color:#000'> Select All </a>] [<a href='javascript:clearall(\"{$r->gid}\")' style='text-decoration:none;color:#000'> Clear All </a>]<font>";
		}
		if($subgroupname != $r->link){
			$subgroupname = $r->link;
			print "<br /><font><b><u>".ucwords(title2($subgroupname))."</u></b></font><font size='-1'> [<a href='javascript:selectall(\"{$subgroupname}\")' style='text-decoration:none;color:#000'> Select All </a>] [<a href='javascript:clearall(\"{$subgroupname}\")' style='text-decoration:none;color:#000'> Clear All </a>]<font><br />";
		}
		$link = "<div class='w250 in'><input type='checkbox' class='$r->gid $subgroupname permission form-control-fluid w35 $r->option $r->link' name='privilege[]' value='{$r->pid}' $r->checked /> ".$r->ptitle."<br /><sub>($r->link/$r->option)</sub></div>";

		print $link;
	}

	print "<div align='center'><button type='submit' class='btn btn-success' name='permission'>Save</button></td></tr></div>";

}
