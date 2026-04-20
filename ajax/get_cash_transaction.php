<?php
session_start();
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

$response = ['success' => false, 'data' => null];

try {
    if (!isset($_POST['source']) || !isset($_POST['id'])) {
        throw new Exception('Missing required parameters');
    }

    $source = (string)$_POST['source'];
    $id = (int)$_POST['id'];

    $data = null;

    switch($source) {
        case 'cw_cash':
            $obj = R::load('cw_cash', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => $obj->amount,
                    'particulars' => $obj->particulars
                ];
            }
            break;

        case 'cw_cash_withdraw':
            $obj = R::load('cw_cash_withdraw', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => abs($obj->amount),
                    'particulars' => $obj->particulars
                ];
            }
            break;

        case 'cw_bank':
            $obj = R::load('cw_bank', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => $obj->amount,
                    'particulars' => $obj->particulars
                ];
            }
            break;

        case 'cw_outlet':
            $obj = R::load('cw_outlet', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => $obj->amount,
                    'particulars' => $obj->particulars
                ];
            }
            break;

        case 'expense_account_entry':
        case 'expense_account_entry_bank':
            $obj = R::load('expense_account_entry', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->expense_date,
                    'amount' => $obj->amount,
                    'particulars' => $obj->particulars
                ];
            }
            break;

        case 'payment_cash':
        case 'payment_bank':
            $obj = R::load('payment', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => abs($obj->amount),
                    'particulars' => $obj->description
                ];
            }
            break;

        case 'bd_handover':
            $obj = R::load('bd_handover', $id);
            if($obj && $obj->id) {
                $data = [
                    'date' => $obj->date,
                    'amount' => $obj->amount,
                    'particulars' => ''
                ];
            }
            break;
    }

    if($data !== null) {
        $response['success'] = true;
        $response['data'] = $data;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
