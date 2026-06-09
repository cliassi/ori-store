<?php
if (isset($_POST['month']) && isset($_POST['year'])) {
	$month = intval($_POST['month']);
	$year = intval($_POST['year']);
	$note_text = isset($_POST['note_text']) ? $_POST['note_text'] : '';
	$note_amount = isset($_POST['note_amount']) ? floatval($_POST['note_amount']) : 0;

	require_once("../safeboot.php");
	ensurePettyCashCurrencyTables();

	$note = R::dispense("petty_cash_currency_notes");
	$note->month = $month;
	$note->year = $year;
	$note->note_text = $note_text;
	$note->note_amount = $note_amount;
	$note->entry_by = uid();
	$note->entry_time = now();
	$id = R::store($note);

	print json_encode(['ok' => true, 'id' => $id, 'note_text' => $note_text, 'note_amount' => $note_amount]);
}
