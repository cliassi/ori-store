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

    $form = '';
    $title = '';

    switch($source) {
        case 'cw_cash':
            $obj = R::load('cw_cash', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Add Cash';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='{$obj->amount}' required></th></tr>
                <tr><th>Particulars</th><th><textarea class='form-control' name='particulars' required>{$obj->particulars}</textarea></th></tr>
            </table>";
            break;

        case 'cw_cash_withdraw':
            $obj = R::load('cw_cash_withdraw', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Cash Withdraw';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='" . abs($obj->amount) . "' required></th></tr>
                <tr><th>Particulars</th><th><textarea class='form-control' name='particulars' required>{$obj->particulars}</textarea></th></tr>
            </table>";
            break;

        case 'cw_bank':
            $obj = R::load('cw_bank', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Bank Deposit';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='{$obj->amount}' required></th></tr>
                <tr><th>Particulars</th><th><textarea class='form-control' name='particulars' required>{$obj->particulars}</textarea></th></tr>
            </table>";
            break;

        case 'cw_outlet':
            $obj = R::load('cw_outlet', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Outlet Account Transfer';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='{$obj->amount}' required></th></tr>
                <tr><th>Particulars</th><th><textarea class='form-control' name='particulars' required>{$obj->particulars}</textarea></th></tr>
            </table>";
            break;

        case 'expense_account_entry':
        case 'expense_account_entry_bank':
            $obj = R::load('expense_account_entry', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Expense Entry';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='expense_date' value='{$obj->expense_date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='{$obj->amount}' required></th></tr>
                <tr><th>Particulars</th><th><textarea class='form-control' name='particulars' required>{$obj->particulars}</textarea></th></tr>
            </table>";
            break;

        case 'payment_cash':
        case 'payment_bank':
            $obj = R::load('payment', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Payment';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='" . abs($obj->amount) . "' required></th></tr>
                <tr><th>Description</th><th><textarea class='form-control' name='description' required>{$obj->description}</textarea></th></tr>
            </table>";
            break;

        case 'bd_handover':
            $obj = R::load('bd_handover', $id);
            if (!$obj->id) throw new Exception('Record not found');
            $title = 'Bank & Cash Handover';
            $form = "<table class='table table-bordered'>
                <tr><th>Date</th><th><input type='date' class='form-control' name='date' value='{$obj->date}' required></th></tr>
                <tr><th>Cash Amount</th><th><input type='number' step='0.01' class='form-control' name='amount' value='{$obj->amount}' required></th></tr>
                <tr><th>Bank Amount</th><th><input type='number' step='0.01' class='form-control' name='bank_amount' value='{$obj->bank_amount}' required></th></tr>
            </table>";
            break;

        default:
            throw new Exception('Unknown transaction type');
    }

    $response = [
        'success' => true,
        'title' => $title,
        'form' => $form
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
