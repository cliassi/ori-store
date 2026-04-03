<?php
/**
 * Calculate total customer due with optional date filter
 * This function can be included in both dashboard.php and due.php
 * to ensure consistent calculations
 */
function getCustomerDueTotal($branch_id = null, $filter = null) {
    $total_due = 0;
    
    $objs = R::find('customer');
    
    foreach ($objs as $obj) {
        // Build the WHERE clause for transactions
        $tran_where = "i.id=ii.invoice_id AND customer_id=$obj->id";
        if ($filter) {
            $tran_where .= " AND ii.$filter";
        }
        
        // Build the WHERE clause for collections
        $col_where = "customer_id=$obj->id";
        if ($filter) {
            $col_where .= " AND $filter";
        }
        
        $transfer_tran = getSum(
            "invoice i, invoice_item ii", 
            "price*quantity", 
            $tran_where
        );
        
        $transfer_col = getSum(
            "collection", 
            "amount", 
            $col_where
        );
        
        $due = $transfer_tran - $transfer_col;
        
        if($due != 0) {
            $total_due += $due;
        }
    }
    
    return $total_due;
}
?>