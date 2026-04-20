<?php
session_start();
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['source']) || !isset($_POST['id'])) {
        throw new Exception('Missing required parameters');
    }

    $source = $_POST['source'];
    $id = (int)$_POST['id'];
    $branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;

    switch($source) {
        case 'cw_cash':
            $obj = R::load('cw_cash', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = (float)($_POST['amount'] ?? $obj->amount);
            $obj->particulars = $_POST['particulars'] ?? $obj->particulars;
            R::store($obj);
            break;

        case 'cw_cash_withdraw':
            $obj = R::load('cw_cash_withdraw', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = -abs((float)($_POST['amount'] ?? abs($obj->amount)));
            $obj->particulars = $_POST['particulars'] ?? $obj->particulars;
            R::store($obj);
            break;

        case 'cw_bank':
            $obj = R::load('cw_bank', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = (float)($_POST['amount'] ?? $obj->amount);
            $obj->particulars = $_POST['particulars'] ?? $obj->particulars;
            R::store($obj);
            break;

        case 'cw_outlet':
            $obj = R::load('cw_outlet', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = (float)($_POST['amount'] ?? $obj->amount);
            $obj->particulars = $_POST['particulars'] ?? $obj->particulars;
            R::store($obj);
            break;

        case 'expense_account_entry':
        case 'expense_account_entry_bank':
            $obj = R::load('expense_account_entry', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->expense_date = $_POST['expense_date'] ?? $obj->expense_date;
            $obj->amount = (float)($_POST['amount'] ?? $obj->amount);
            $obj->particulars = $_POST['particulars'] ?? $obj->particulars;
            R::store($obj);
            break;

        case 'payment_cash':
        case 'payment_bank':
            $obj = R::load('payment', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = -abs((float)($_POST['amount'] ?? abs($obj->amount)));
            $obj->description = $_POST['description'] ?? $obj->description;
            R::store($obj);
            break;

        case 'bd_handover':
            $obj = R::load('bd_handover', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $obj->date = $_POST['date'] ?? $obj->date;
            $obj->amount = (float)($_POST['amount'] ?? $obj->amount);
            $obj->bank_amount = (float)($_POST['bank_amount'] ?? $obj->bank_amount);
            R::store($obj);
            break;

        default:
            throw new Exception('Unknown transaction type');
    }

    $response = [
        'success' => true,
        'message' => 'Transaction updated successfully'
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

header('Content-Type: application/json');
echo json_encode($response);
?>
