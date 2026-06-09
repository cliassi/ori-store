<?php
if (isset($_POST['id'])) {
	$id = intval($_POST['id']);
	require_once("../safeboot.php");

	$note = R::load("petty_cash_currency_notes", $id);
	if ($note) {
		R::trash($note);
		print json_encode(['ok' => true]);
	} else {
		print json_encode(['ok' => false, 'error' => 'Note not found']);
	}
}
