<?php
if (isset($_POST['month']) && isset($_POST['year']) && isset($_POST['label']) && isset($_POST['count'])) {
	$month = intval($_POST['month']);
	$year = intval($_POST['year']);
	$label = $_POST['label'];
	$denomination = floatval($_POST['denomination']);
	$count = floatval($_POST['count']);

	require_once("../safeboot.php");
	ensurePettyCashCurrencyTables();

	global $c;
	$stmt = $c->prepare("INSERT INTO petty_cash_currency_data (month, year, label, denomination, count, entry_by, entry_time) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE count=?, modify_by=?, modify_time=NOW()");
	$stmt->bind_param("iisddidi", $month, $year, $label, $denomination, $count, uid(), $count, uid());
	$stmt->execute();

	print json_encode(['ok' => true, 'count' => $count, 'total' => $denomination * $count]);
}
