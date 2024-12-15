<?php
if(uid()!=1){
	die("Access denied");
}
if(isset($post->permission)){
	$privileges = "";
	update("sys_acl", "access=0", "appliesto={$post->appliesto} AND utype='{$post->utype}'");
	if(isset($post->privilege)) foreach($post->privilege as $r){
		$privileges .= ($privileges!=""?",":"")."$r";
		replace("sys_acl", "appliesto, utype, privilege, access, entryby", "{$post->appliesto}, '{$post->utype}', $r, 1, ".uid());
	}
	if($post->utype == 'u'){
		select("REPLACE INTO `sys_acl` SELECT {$post->appliesto}, 'u', id, 0, '".now()."', ".uid()." FROM sys_permission WHERE `user`={$post->appliesto} AND id NOT IN ($privileges)");
	}
	// deleteAllFiles('temps/menus/');
	// redir(makeUri("$module/$controller", $function, '', "?u={$post->utype}&a={$post->appliesto}"));//"?u={$post->utype}&a={$post->appliesto}");
}
?>
<style type="text/css">
	table#permission{
		border: solid .5 #ccc;
		border-collapse: collapse;
	}
	table#permission td, table#permission th{
		border: solid .5px #ccc;
	}
</style>
<script type="text/javascript">
function selectall(cls){
	$("."+cls).attr('checked', true);
}
function clearall(cls){
	$("."+cls).attr('checked', false);
}
</script>
<br />
<?php
$func = isset($get->f)?($get->f==""?"set":$get->f):'set';
if(isset($get->id)) { $id = $get->id; }
$u = isset($get->u)?$get->u:'r';
$a = isset($get->a)?$get->a:'';
$s = isset($get->s)?$get->s:site();
?>
<div class='row'>
	<div class='col-md-2'></div>
	<div class='col-md-8'>
	<form method="post">
		<div align="center">
		<h4>Set permission for <span onkeyup="userRoles()"><select name="utype" id="utype" class='form-control-fluid' onchange="userRoles()" onfocus="userRoles()"><option value="u" <?php print $u=='u'?"selected":"";?>>User</option><option value="r" <?php print $u=='r'?"selected":"";?>>Role</option></select></span>
		<span onkeyup="loadPermissionSettings()"><select name="appliesto" id="appliesto"  class='form-control-fluid' onchange="loadPermissionSettings()" onfocus="loadPermissionSettings()"></select></span></h4>
		</div>
		<table class='grid w750' id="permission">
		</table>
	</form>
	</div>
	<div class='col-md-2'></div>
</div>
<script type="text/javascript">
$(function(){
	userRoles();	
});
function userRoles(){
	$.post("<?php print ROOT."/ajax/user_roles.php?".now(); ?>", {
		type: $("#utype").val(),
		def: '<?php print $a; ?>',
		app: '<?php print APP; ?>'},
		function(data){
			console.log(data);
			$("#appliesto").html(data);
			loadPermissionSettings();
	}); 
}
function loadPermissionSettings(){
	$.post("<?php print ROOT."/ajax/permission.php?".now(); ?>", {
		type: $("#utype").val(),
		appliesto: $("#appliesto").val(),
		app: '<?php print APP; ?>'},
		function(data){
			$("#permission").html(data);
	});	
}

</script>