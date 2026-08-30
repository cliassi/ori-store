# Branch ID Implementation Plan

This plan adds branch_id columns to invoice and stock_collect tables, updates existing records to branch_id=1, modifies PHP scripts to insert branch_id from session, and adds branch filtering to the stock report.


## PHP File Modifications

### Invoice Creation Files
1. **orderadmin/pages/invoice.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense('invoice')
2. **orderadmin/pages/customer_order.php** - Add `$invoice->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense('invoice')
3. **app/pages/customer_order.php** - Add `$invoice->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense('invoice')

### Stock Collect Creation Files
1. **orderadmin/pages/daily_order.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense("stock_collect")
2. **orderadmin/pages/dcollect.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense("stock_collect") (2 locations)
3. **orderadmin/pages/delivery_status.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense("stock_collect") (2 locations)
4. **orderadmin/pages/pending_order.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense("stock_collect") (2 locations)
5. **app/pages/reports/stock.php** - Add `$obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;` after R::dispense("stock_collect")

### Stock Collect Item Creation
For all stock_collect_item dispense calls, add branch_id from parent stock_collect record after storing the parent.

### Stock Report Filtering
**app/pages/reports/stock.php** - Add branch_id filtering to all SQL queries:
- Get branch_id from session: `$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;`
- Add `AND o.branch_id = $branch_id` to invoice queries
- Add `AND o.branch_id = $branch_id` to stock_collect queries  
- Add `AND o.branch_id = $branch_id` to order queries
- Add `AND oi.branch_id = $branch_id` to damaged_item queries
