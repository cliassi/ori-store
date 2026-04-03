<?php 
$fields = ['bank_transaction','date','description','credit','debit','worker','passport','company','company_name','expense','particulars','removed_by','removed_at'];

foreach ($fields as $field) {
	if(isset($post->$field) && nn($post->$field)) {
		$object->$field = $post->$field;
	}
}

$object->status = isset($post->status)?$post->status:0;
R::store($object); 
?>