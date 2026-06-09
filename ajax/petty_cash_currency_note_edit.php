<?php
if (isset($_POST['id']) && isset($_POST['note_text'])) {
	$id = intval($_POST['id']);
	$note_text = $_POST['note_text'];
	$note_amount = isset($_POST['note_amount']) ? floatval($_POST['note_amount']) : 0;

	require_once("../safeboot.php");

	$note = R::load("petty_cash_currency_notes", $id);
	if ($note && $note->id) {
		$note->note_text = $note_text;
		$note->note_amount = $note_amount;
		R::store($note);
		print json_encode(['ok' => true, 'id' => $id, 'note_text' => $note_text, 'note_amount' => $note_amount]);
	} else {
		print json_encode(['ok' => false, 'error' => 'Note not found']);
	}
} else {
	print json_encode(['ok' => false, 'error' => 'Missing required parameters']);
}
