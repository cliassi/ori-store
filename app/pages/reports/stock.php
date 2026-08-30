<?php
// vd($_SESSION);
// die(0);
// Ensure required columns exist to prevent query failures
// ensureMysqlColumn('order', 'lorry_id', 'INT NULL');
// ensureMysqlColumn('order', 'confirm_date', 'DATE NULL');
// ensureMysqlColumn('invoice', 'confirm_date', 'DATE NULL');
// ensureMysqlColumn('damaged_item', 'confirm_date', 'DATE NULL');
// ensureMysqlColumn('stock_collect', 'confirm_date', 'DATE NULL');
// --- ID Detection: Accept from $get->id only ---
if (!defined('ID') && isset($get->id) && is_numeric($get->id)) {
    define('ID', (int) $get->id);
}

// --- Placeholder/Fallback for userList() ---
// IMPORTANT: The actual userList() function should be included from your framework files.
// This is a safety net to prevent fatal errors, but staff names will be incorrect/missing 
// if the true function is not defined elsewhere.
if (!function_exists('userList')) {
    function userList()
    {
        // Return a mock array or empty array if the real function isn't loaded
        return [];
    }
}

function sanitizeDate($date_str)
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        $d = DateTime::createFromFormat('Y-m-d', $date_str);
        if ($d && $d->format('Y-m-d') === $date_str) {
            return $date_str; // Returns valid date string
        }
    }
    return false; // Returns false if invalid
}

// --- Get & sanitize dates from $get (Reads raw input) ---
$from_date_input = isset($get->from_date) ? $get->from_date : '';
$to_date_input = isset($get->to_date) ? $get->to_date : '';

if (defined('ID')) {

    // Attempt to sanitize the user's inputs
    $temp_from = sanitizeDate($from_date_input);
    $temp_to = sanitizeDate($to_date_input);

    // Set the query variables ($from_date/$to_date) to the valid input, or the default range
    // $from_date and $to_date will be used for the SQL query.
    $from_date = $temp_from ?: date('Y-m-d', strtotime('-14 days'));
    $to_date = $temp_to ?: date('Y-m-d');

    // Ensure 'To' is not before 'From'
    if ($to_date < $from_date) {
        $to_date = $from_date;
    }

    // --- Set variables for HTML form value ---
    // If the user provided valid input, we want the form field to DISPLAY that exact input.
    // If it was initial load or invalid, $from_date/$to_date already holds the 14-day default.
    $form_from_date = $from_date_input ?: $from_date;
    $form_to_date = $to_date_input ?: $to_date;

} else {
    // Logic for the main (non-ID) view
    $from_date = date('Y-m-d', strtotime('-14 days'));
    $to_date = date('Y-m-d');

    // For the main view, form values are the defaults
    $form_from_date = $from_date;
    $form_to_date = $to_date;
}

// Get branch_id from session for filtering
$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;

$canDeleteMovement = (
    uid() == 1
    || (function_exists('username') && username() == 'Adminn')
    || (function_exists('isUserIn') && isUserIn(['Adminn']))
);

// --- Handle form submissions (delete, approve, save) ---
if (isset($post->movement_row_id) && isset($post->movement_row_table) && isset($post->movement_src)) {
    $movementId = (int) $post->movement_row_id;
    $movementTable = trim((string) $post->movement_row_table);
    $movementSrc = trim((string) $post->movement_src);
    $movementPin = isset($post->movement_pin) ? trim((string) $post->movement_pin) : '';
    $isValidPin = false;

    if ($canDeleteMovement && $movementPin !== '') {
        $currentUser = R::load('sys_user', uid());
        if ($currentUser && (int) $currentUser->id > 0 && (string) $currentUser->u_pin === $movementPin) {
            $isValidPin = true;
        } else {
            $adminUser = R::findOne('sys_user', 'u_username = ? AND u_pin = ?', ['Adminn', $movementPin]);
            if ($adminUser) {
                $isValidPin = true;
            }
        }
    }

    if ($canDeleteMovement && $isValidPin && $movementId > 0) {
        if ($movementSrc === 'collection' && $movementTable === 'invoice_item') {
            $movement = R::load('invoice_item', $movementId);
            if ($movement && (int) $movement->id > 0) {
                R::trash($movement);
            }
        } elseif ($movementSrc === 'ret' && $movementTable === 'stock_collect_item') {
            $movement = R::load('stock_collect_item', $movementId);
            if ($movement && (int) $movement->id > 0) {
                $movement->returned_quantity = 0;
                R::store($movement);
            }
        } elseif ($movementSrc === 'damage' && $movementTable === 'stock_collect_item') {
            $movement = R::load('stock_collect_item', $movementId);
            if ($movement && (int) $movement->id > 0) {
                $movement->damaged_quantity = 0;
                $movement->damaged_cause = '';
                R::store($movement);
            }
        } elseif ($movementSrc === 'damage' && $movementTable === 'damaged_item') {
            $movement = R::load('damaged_item', $movementId);
            if ($movement && (int) $movement->id > 0) {
                R::trash($movement);
            }
        }
    }

    redir($_SERVER['REQUEST_URI']);
}

if (isset($get->token)) {
    $token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
    if ($token) {
        R::trash($token);
        $stock_damage = R::load("damaged_item", $get->id);
        $stock_damage->status = 'Approved';
        R::store($stock_damage);
        redir("?");
    }
}

if (isset($post->save)) {
    $obj = R::dispense("stock_collect");
    $obj->salesman_id = isset($post->salesman) ? $post->salesman : 0;
    $obj->branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
    $obj->delivery_staff = $post->delivery_staff;
    $obj->date = today();
    $obj->created_by = uid();

    $stored = false;
    foreach ($post->variance as $id => $qty) {
        if ($qty == 0)
            continue;
        if (!$stored) {
            R::store($obj);
            $stored = true;
        }
        $variance = R::load("product_variance", $id);
        $product = R::load("product", $variance->product_id);
        $ii = R::dispense("stock_collect_item");

        $ii->stock_collect_id = $obj->id;
        $ii->product_id = $product->id;
        $ii->product_variance_id = $variance->id;
        $ii->quantity = $qty;
        $ii->price = $variance->price;
        $ii->cost = $variance->cost;
        $ii->name = $product->name;
        $ii->description = "$variance->particulars $variance->size x $variance->unit";
        $ii->created_by = uid();
        $ii->branch_id = $obj->branch_id;

        // FIX: Changed R->store to R::store (Static call fix)
        R::store($ii);
    }
    redir(ROOT . "/delivery?s=$obj->delivery_staff");
}
?>
<style>
    /* Prevent page overflow */
    body {
        overflow-x: hidden !important;
    }

    .card {
        overflow: hidden;
    }

    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .col-sm-12 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Make sure form doesn't overflow */
    form.mb-3 {
        overflow-x: auto;
        max-width: 100%;
    }

    .row.g-2 {
        flex-wrap: wrap;
    }

    .sticky-table-container {
        /* max-height: 350px; */
        height: calc(100vh - 300px);
        overflow-x: auto;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .sticky-table-container table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.825rem;
        min-width: 800px;
        /* Prevents table from being too compressed */
    }

    .sticky-table-container thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
    }

    .sticky-table-container thead th {
        padding: 8px 10px;
        font-weight: 600;
        color: #495057;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        font-size: 0.8rem;
        white-space: nowrap;
    }

    .sticky-table-container tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
        font-size: 0.8rem;
    }

    .sticky-table-container tbody tr:hover {
        background-color: #f9f9f9;
    }

    .sticky-table-container tbody tr.table-footer {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background-color: #f8f9fa;
        font-weight: bold;
        border-top: 2px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
    }

    .sticky-table-container img {
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        max-width: 60px;
        height: auto;
    }

    .btn-sm {
        padding: 3px 8px;
        font-size: 0.75rem;
    }

    .form-control.w100 {
        width: 80px;
        text-align: center;
        padding: 4px 8px;
        font-size: 0.875rem;
        border-radius: 6px;
    }

    .supplier-select {
        width: auto;
        display: inline-block;
        margin-left: 10px;
        font-size: 0.875rem;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.25rem;
    }

    .card-header h5 {
        margin: 0;
        font-weight: 600;
        color: #343a40;
    }

    .text-center {
        text-align: center;
    }

    .bg-purchase {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .bg-collection {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .bg-return {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .bg-damage {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }

    .qty-cell-inline {
        display: inline-flex;
        align-items: center;
    }

    .qty-delete-btn {
        margin-left: 2px;
        border: none;
        background: transparent;
        color: #dc3545;
        font-weight: 600;
        line-height: 1;
        padding: 0;
        cursor: pointer;
    }

    .qty-delete-btn:hover {
        text-decoration: underline;
    }

    .stock-balance {
        font-weight: 600;
        color: #212529;
        font-size: 0.85rem;
    }

    /* Responsive adjustments */
    @media (max-width: 1400px) {
        .sticky-table-container table {
            font-size: 0.75rem;
        }

        .sticky-table-container thead th,
        .sticky-table-container tbody td {
            padding: 6px 8px;
        }

        .sticky-table-container img {
            max-width: 50px;
        }
    }

    /* Make specific columns narrower */
    .sticky-table-container td:first-child,
    .sticky-table-container th:first-child {
        width: 40px;
    }

    .sticky-table-container img {
        width: 60px;
        height: 60px;
        object-fit: cover;
    }
</style>

<?php if (defined('ID')): ?>
    <form class="mb-3" method="get" action="" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <!-- Keep the id as hidden input -->
        <input type="hidden" name="id" value="<?php echo ID; ?>">

        <label for="from_date" style="margin: 0; font-size: 0.85rem; white-space: nowrap;">From:</label>
        <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($form_from_date); ?>"
            style="font-size: 0.85rem; padding: 5px 8px; width: auto;" required>

        <label for="to_date" style="margin: 0; font-size: 0.85rem; white-space: nowrap;">To:</label>
        <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($form_to_date); ?>"
            style="font-size: 0.85rem; padding: 5px 8px; width: auto;" required>

        <label for="product_variance" style="margin: 0; font-size: 0.85rem; white-space: nowrap;">Product Variance:</label>
        <select class="form-control" name="product_variance" style="font-size: 0.85rem; padding: 5px 8px; width: auto;">
            <option value="">All Variants</option>
            <?php
            if (defined('ID')) {
                $variants = R::find('product_variance', '1=1 ORDER BY particulars');
            } else {
                $variants = R::find('product_variance', 'ORDER BY particulars');
            }
            foreach ($variants as $variant) {
                $selected = (isset($get->product_variance) && $get->product_variance == $variant->id) ? 'selected' : '';
                echo "<option value='" . $variant->id . "' $selected>" . htmlspecialchars($variant->particulars) . "</option>";
            }
            ?>
        </select>

        <div
            style="display: flex; gap: 15px; flex-wrap: wrap; margin-left: 20px; border-left: 1px solid #ddd; padding-left: 20px;">
            <?php
            // Determine if form was submitted by checking if from_date is in URL
            $form_submitted = isset($get->from_date);
            ?>
            <label style="margin: 0; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="show_purchase" value="1" <?php echo (!$form_submitted || isset($get->show_purchase)) ? 'checked' : ''; ?> style="cursor: pointer;">
                <span
                    style="background-color: #d4edda; padding: 2px 8px; border-radius: 3px; font-weight: 500;">Purchase</span>
            </label>
            <label style="margin: 0; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="show_collection" value="1" <?php echo (!$form_submitted || isset($get->show_collection)) ? 'checked' : ''; ?> style="cursor: pointer;">
                <span
                    style="background-color: #cce5ff; padding: 2px 8px; border-radius: 3px; font-weight: 500;">Collection</span>
            </label>
            <label style="margin: 0; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="show_return" value="1" <?php echo (!$form_submitted || isset($get->show_return)) ? 'checked' : ''; ?> style="cursor: pointer;">
                <span
                    style="background-color: #fff3cd; padding: 2px 8px; border-radius: 3px; font-weight: 500;">Return</span>
            </label>
            <label style="margin: 0; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="show_damage" value="1" <?php echo (!$form_submitted || isset($get->show_damage)) ? 'checked' : ''; ?> style="cursor: pointer;">
                <span
                    style="background-color: #f8d7da; padding: 2px 8px; border-radius: 3px; font-weight: 500;">Damage</span>
            </label>
        </div>
        <button type="submit" class="btn btn-primary" style="font-size: 0.85rem; padding: 5px 15px;">Filter</button>
    </form>
<?php endif; ?>


<div class="dt-responsive table-responsive">
    <div class="sticky-table-container">
        <table id="simpletable" class="table">
            <tbody>
                <?php
                $users = userList(); // Fixed via stub function / assumes proper include
                $i = 1;

                if (defined('ID')) {
                    echo "<thead><tr><th>No.</th><th>Date</th><th>Particulars</th><th>Image</th><th>Size</th>
                                            <th>Staff</th><th>Code</th><th>Purchase</th><th>Collection</th><th>Return</th><th>Damage</th><th>Balance</th></tr></thead>";

                    $sql = "
                                        SELECT * FROM (
                                            -- Sales Collection (OUT)
                                            SELECT 'collection' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                                            oi.quantity quantity, o.invoice_date `date`, oi.created_at, COALESCE(oi.created_by, o.created_by) created_by,
                                            COALESCE(NULLIF(sc_latest.delivery_staff, ''), NULLIF(oi.delivery_staff, ''), NULLIF(o.salesman, '')) staff,
                                            '' `remarks`, '' status, o.customer_id AS customer_id, c.code AS customer_code, NULL AS supplier_id,
                                            oi.id AS row_ref_id, 'invoice_item' AS row_ref_table
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            JOIN `invoice_item` oi ON v.id = oi.product_variance_id
                                            JOIN `invoice` o ON oi.invoice_id = o.id
                                            LEFT JOIN `customer` c ON o.customer_id = c.id
                                            LEFT JOIN (
                                                SELECT sci.invoice_item_id, sc.delivery_staff
                                                FROM stock_collect_item sci
                                                INNER JOIN stock_collect sc ON sc.id = sci.stock_collect_id
                                                INNER JOIN (
                                                    SELECT invoice_item_id, MAX(id) latest_id
                                                    FROM stock_collect_item
                                                    WHERE invoice_item_id IS NOT NULL AND invoice_item_id > 0
                                                    GROUP BY invoice_item_id
                                                ) latest ON latest.latest_id = sci.id
                                            ) sc_latest ON sc_latest.invoice_item_id = oi.id
                                            WHERE v.id = " . (int) ID . "
                                                AND o.invoice_date BETWEEN '$from_date' AND '$to_date'
                                                AND o.branch_id = $branch_id

                                            UNION ALL

                                            -- Stock Collect Return (IN)
                                            SELECT 'ret' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                                            oi.returned_quantity quantity, o.date `date`, oi.created_at, oi.created_by, o.delivery_staff staff, '' `remarks`, '' status, NULL AS customer_id, NULL AS customer_code, NULL AS supplier_id,
                                            oi.id AS row_ref_id, 'stock_collect_item' AS row_ref_table
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            JOIN `stock_collect_item` oi ON v.id = oi.product_variance_id
                                            JOIN `stock_collect` o ON oi.stock_collect_id = o.id
                                            WHERE v.id = " . (int) ID . "
                                                AND oi.returned_quantity > 0
                                                AND o.date BETWEEN '$from_date' AND '$to_date'
                                                AND o.branch_id = $branch_id

                                            UNION ALL

                                            -- Stock Collect Damage (OUT)
                                            SELECT 'damage' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                                            oi.damaged_quantity quantity, o.date `date`, oi.created_at, oi.created_by, o.delivery_staff staff, '' `remarks`, '' status, NULL AS customer_id, NULL AS customer_code, NULL AS supplier_id,
                                            oi.id AS row_ref_id, 'stock_collect_item' AS row_ref_table
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            JOIN `stock_collect_item` oi ON v.id = oi.product_variance_id
                                            JOIN `stock_collect` o ON oi.stock_collect_id = o.id
                                            WHERE v.id = " . (int) ID . "
                                                AND oi.damaged_quantity > 0
                                                AND o.date BETWEEN '$from_date' AND '$to_date'
                                                AND o.branch_id = $branch_id

                                            UNION ALL

                                            -- Purchase/Inbound Order (IN)
                                            SELECT 'order' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, 
                                            oi.quantity quantity, COALESCE(o.confirm_date, o.order_date) `date`, oi.created_at, oi.created_by, '' staff, 'Warehouse' `remarks`, '' status, NULL AS customer_id, NULL AS customer_code, o.supplier_id AS supplier_id,
                                            oi.id AS row_ref_id, 'order_item' AS row_ref_table
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            JOIN `order_item` oi ON v.id = oi.product_variance_id
                                            JOIN `order` o ON oi.order_id = o.id
                                            WHERE v.id = " . (int) ID . "
                                                AND COALESCE(o.confirm_date, o.order_date) BETWEEN '$from_date' AND '$to_date'
                                                AND o.branch_id = $branch_id

                                            UNION ALL

                                            -- Warehouse Damaged Item (OUT)
                                            SELECT 'damage' src, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image_single vimage, size, unit, 
                                            oi.quantity quantity, DATE(oi.created_at) `date`, oi.created_at, oi.created_by, '' staff, 'Damage' `remarks`, oi.status, NULL AS customer_id, NULL AS customer_code, NULL AS supplier_id,
                                            oi.id AS row_ref_id, 'damaged_item' AS row_ref_table
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            JOIN `damaged_item` oi ON v.id = oi.product_variance_id
                                            WHERE v.id = " . (int) ID . "
                                                AND DATE(oi.created_at) BETWEEN '$from_date' AND '$to_date'
                                                AND oi.branch_id = $branch_id
                                        ) a 
                                        ORDER BY `date`, created_at
                                    ";

                    $items = select($sql);
                    $bal = $balp = 0;

                    // Check if query failed (returns boolean false, preventing mysqli_fetch_object warning)
                    if ($items === false) {
                        echo "<tr><td colspan='12'><div class='alert alert-danger'>SQL Query Failed. Please check the database and table names.</div></td></tr>";
                    } else {
                        $total_order = 0;
                        $total_return = 0;
                        $total_damage = 0;
                        $total_collection = 0;

                        $openingSql = "
                                            SELECT v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit,
                                                COALESCE(SUM(a.stock_quantity), 0) stock_quantity,
                                                COALESCE(SUM(a.damage_quantity), 0) damage_quantity
                                            FROM `product` p
                                            JOIN `product_variance` v ON p.id = v.product_id
                                            LEFT JOIN (
                                                SELECT v.id, -oi.quantity stock_quantity, 0 damage_quantity
                                                FROM `product_variance` v
                                                JOIN `invoice_item` oi ON v.id = oi.product_variance_id
                                                JOIN `invoice` o ON oi.invoice_id = o.id
                                                WHERE v.id = " . (int) ID . "
                                                    AND o.invoice_date < '$from_date'
                                                    AND o.branch_id = $branch_id

                                                UNION ALL

                                                SELECT v.id, oi.returned_quantity stock_quantity, 0 damage_quantity
                                                FROM `product_variance` v
                                                JOIN `stock_collect_item` oi ON v.id = oi.product_variance_id
                                                JOIN `stock_collect` o ON oi.stock_collect_id = o.id
                                                WHERE v.id = " . (int) ID . "
                                                    AND oi.returned_quantity > 0
                                                    AND o.date < '$from_date'
                                                    AND o.branch_id = $branch_id

                                                UNION ALL

                                                SELECT v.id, 0 stock_quantity, oi.damaged_quantity damage_quantity
                                                FROM `product_variance` v
                                                JOIN `stock_collect_item` oi ON v.id = oi.product_variance_id
                                                JOIN `stock_collect` o ON oi.stock_collect_id = o.id
                                                WHERE v.id = " . (int) ID . "
                                                    AND oi.damaged_quantity > 0
                                                    AND o.date < '$from_date'
                                                    AND o.branch_id = $branch_id

                                                UNION ALL

                                                SELECT v.id, oi.quantity stock_quantity, 0 damage_quantity
                                                FROM `product_variance` v
                                                JOIN `order_item` oi ON v.id = oi.product_variance_id
                                                JOIN `order` o ON oi.order_id = o.id
                                                WHERE v.id = " . (int) ID . "
                                                    AND COALESCE(o.confirm_date, o.order_date) < '$from_date'
                                                    AND o.branch_id = $branch_id

                                                UNION ALL

                                                SELECT v.id, 0 stock_quantity, oi.quantity damage_quantity
                                                FROM `product_variance` v
                                                JOIN `damaged_item` oi ON v.id = oi.product_variance_id
                                                WHERE v.id = " . (int) ID . "
                                                    AND DATE(oi.created_at) < '$from_date'
                                                    AND oi.branch_id = $branch_id
                                            ) a ON a.id = v.id
                                            WHERE v.id = " . (int) ID . "
                                            GROUP BY v.id, p.id, p.name, p.image, v.particulars, v.cost, v.price, v.image, size, unit
                                        ";
                        $openingItems = select($openingSql);
                        if ($openingItems !== false && ($opening = mysqli_fetch_object($openingItems))) {
                            $bal = $opening->stock_quantity + 0;
                            $balp = $opening->damage_quantity + 0;
                            echo "<tr id='var-opening' title='opening'>";
                            echo "<td>$i</td>";
                            echo "<td>" . date("d M y", strtotime($from_date)) . "</td>";
                            echo "<td>Opening Stock - $opening->particulars</td>";
                            echo "<td class='text-center'><img src='" . ROOT . "/{$opening->vimage}' alt='Product Image'></td>";
                            echo "<td>$opening->size</td>";
                            echo "<td></td>";
                            echo "<td></td>";
                            echo "<td></td><td></td><td></td><td></td>";
                            echo "<td class='text-center stock-balance'>" . ($balp > 0 ? "<span style='color:grey'>(- $balp pcs)</span> " : "") . "$bal</td>";
                            echo "</tr>";
                            $i++;
                        }

                        while ($var = mysqli_fetch_object($items)) {
                            // Check product_variance filter (ID from URL or dropdown)
                            $product_variance_filter = isset($get->product_variance) && $get->product_variance != '' ? $get->product_variance : (defined('ID') && ID > 0 ? ID : null);
                            if ($product_variance_filter !== null) {
                                if ($var->id != $product_variance_filter)
                                    continue;
                            }

                            // Check if this row should be displayed based on checkboxes
                            // If form was submitted, only show if checkbox is checked (isset means it was checked)
                            // If form wasn't submitted, show all
                            $show_purchase = !$form_submitted || isset($get->show_purchase);
                            $show_collection = !$form_submitted || isset($get->show_collection);
                            $show_return = !$form_submitted || isset($get->show_return);
                            $show_damage = !$form_submitted || isset($get->show_damage);

                            // Skip row if it doesn't match filter
                            if ($var->src == 'order' && !$show_purchase)
                                continue;
                            if ($var->src == 'collection' && !$show_collection)
                                continue;
                            if ($var->src == 'ret' && !$show_return)
                                continue;
                            if ($var->src == 'damage' && !$show_damage)
                                continue;

                            echo "<tr id='var-' title='$var->src'>";
                            echo "<td>$i</td>";
                             echo "<td>";
                             $confirmDateRaw = $var->date;
                             $confirmDisplay = date("d M y", strtotime($confirmDateRaw));
                             if (strtotime($confirmDateRaw) === false) {
                                 $confirmDisplay = "Invalid Date";
                             }
                             echo "<span class='editable-confirm-date' style='cursor: pointer;' data-order-id='{$var->id}' data-date='{$confirmDateRaw}'>{$confirmDisplay}</span>";
                             echo "</td>";
                            echo "<td>$var->particulars</td>";
                            //echo "<td class='text-center'><img src='" . ROOT . "/{$var->vimage}' height='80px' alt='Product Image'></td>";
                            echo "<td class='text-center'><img src='" . ROOT . "/{$var->vimage}' alt='Product Image'></td>";
                            echo "<td>$var->size</td>";
                            echo "<td>" . (nn($var->staff) ? $var->staff : (isset($users[$var->created_by]) ? $users[$var->created_by] : 'Unknown')) . "</td>";
                            echo "<td>";
                            if ((int) $var->customer_id > 0) {
                                $customerCode = nn($var->customer_code) ? htmlspecialchars($var->customer_code) : ("CUST-" . (int) $var->customer_id);
                                echo "<a href='" . ROOT . "/customer/details/$var->customer_id'>$customerCode</a>";
                            }
                            echo "</td>";

                            $rowRefId = (int) $var->row_ref_id;
                            $rowRefTable = preg_replace('/[^a-z_]/i', '', (string) $var->row_ref_table);
                            $rowSource = preg_replace('/[^a-z_]/i', '', (string) $var->src);
                            $movementDeleteBtn = '';
                            if ($canDeleteMovement && $rowRefId > 0 && in_array($rowSource, ['collection', 'ret', 'damage'], true)) {
                                $movementDeleteBtn = "<button type='button' class='qty-delete-btn' onclick=\"deleteMovementRow('{$rowRefTable}', {$rowRefId}, '{$rowSource}')\">X</button>";
                            }

                            if ($var->src == 'order') {
                                $bal += $var->quantity;
                                $total_order += $var->quantity;
                                echo "<td class='text-center bg-purchase'>";
                                if ((int) $var->supplier_id > 0) {
                                    echo "<a href='" . ROOT . "/supplier/details/$var->supplier_id'>$var->quantity</a>";
                                } else {
                                    echo $var->quantity;
                                }
                                echo "</td><td></td><td></td><td></td>";
                            } elseif ($var->src == 'ret') {
                                $bal += $var->quantity;
                                $total_return += $var->quantity;
                                echo "<td></td><td></td><td class='text-center bg-return'><span class='qty-cell-inline'>{$var->quantity}{$movementDeleteBtn}</span></td><td></td>";
                            } elseif ($var->src == 'damage') {
                                $balp += $var->quantity;
                                $total_damage += $var->quantity;
                                echo "<td></td><td></td><td></td><td class='text-center bg-damage'><span class='qty-cell-inline'>{$var->quantity}{$movementDeleteBtn}</span></td>";
                            } elseif ($var->src == 'collection') {
                                $bal -= $var->quantity;
                                $total_collection += $var->quantity;
                                echo "<td></td><td class='text-center bg-collection'><span class='qty-cell-inline'>" . abs($var->quantity) . "{$movementDeleteBtn}</span></td><td></td><td></td>";
                            }
                            echo "<td class='text-center stock-balance'>" . ($balp > 0 ? "<span style='color:grey'>(- $balp pcs)</span> " : "") . "$bal</td>";
                            echo "</tr>";
                            $i++;
                        }
                        // Add footer with totals
                        echo "<tr class='table-footer'>";
                        echo "<td colspan='7'>TOTAL</td>";
                        echo "<td class='text-center bg-purchase'>" . $total_order . "</td>";
                        echo "<td class='text-center bg-collection'>" . $total_collection . "</td>";
                        echo "<td class='text-center bg-return'>" . $total_return . "</td>";
                        echo "<td class='text-center bg-damage'>" . $total_damage . "</td>";
                        echo "<td class='text-center stock-balance'>" . ($balp > 0 ? "<span style='color:grey'>(- $balp pcs)</span> " : "") . "$bal</td>";
                        echo "</tr>";
                    }
                } else {
                    // Main product list (no date filter needed)
                    echo "<thead><tr><th>No.</th><th>Product</th><th>Particulars</th><th>Image</th><th>Size</th><th>Unit</th><th>Stock Quantity</th></tr></thead>";
                    $items = select("SELECT id, pid, pname, pimage, particulars, cost, price, vimage, size, unit, SUM(quantity) quantity , `date` FROM (
                                         SELECT oi.id iid, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, -oi.quantity quantity, invoice_date `date`
                                         FROM `product` p, `product_variance` v, `invoice_item` oi, `invoice` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.invoice_id
                                         UNION
                                         SELECT oi.id iid, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity, o.confirm_date `date`
                                         FROM `product` p, `product_variance` v, `order_item` oi, `order` o WHERE p.id=v.product_id AND v.id=oi.product_variance_id AND o.id=oi.order_id
                                         UNION
                                          SELECT oi.id iid, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.quantity / unit, oi.created_at `date`
                                         FROM `product` p, `product_variance` v, `damaged_item` oi WHERE p.id=v.product_id AND v.id=oi.product_variance_id
                                         UNION
                                         SELECT oi.id iid, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, oi.returned_quantity quantity, o.confirm_date `date`
                                         FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id 
                                         AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND oi.returned_quantity > 0 
                                         UNION
                                         SELECT oi.id iid, v.id, p.id pid, p.name pname, p.image pimage, v.particulars, v.cost, v.price, v.image vimage, size, unit, -oi.damaged_quantity quantity, o.confirm_date `date`
                                         FROM `product` p, `product_variance` v, `stock_collect_item` oi, `stock_collect` o WHERE p.id=v.product_id 
                                         AND v.id=oi.product_variance_id AND o.id=oi.stock_collect_id AND oi.damaged_quantity > 0
                                        ) a GROUP BY id ORDER BY pid, particulars");

                    while ($var = mysqli_fetch_object($items)) {
                        if (strpos($var->quantity, ".") !== FALSE) {
                            $q = explode(".", $var->quantity);
                            $whole = $q[0];
                            $pcs = nf(".{$q[1]}" * $var->unit, 0) + 0;
                            if ($pcs) {
                                $var->quantity = "<span style='color:grey'>(- $pcs Pcs)</span> $whole";
                            } else {
                                $var->quantity = "$whole";
                            }
                        }
                        echo "<tr>";
                        echo "<td>$i</td>";
                        echo "<td>$var->pname</td>";
                        echo "<td><a href='" . METHOD . "/$var->id'>$var->particulars</a></td>";
                        //echo "<td class='text-center'><img src='" . ROOT . "/{$var->vimage}' height='80px' alt='Product'></td>";
                        echo "<td class='text-center'><img src='" . ROOT . "/{$var->vimage}' alt='Product'></td>";
                        echo "<td>$var->size</td>";
                        echo "<td>$var->unit</td>";
                        echo "<td>$var->quantity</td>";
                        echo "</tr>";
                        $i++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
</div>
</div>
</form>

<form method="post" id="movement-del-form">
    <input type="hidden" name="movement_row_id" id="movement_row_id">
    <input type="hidden" name="movement_row_table" id="movement_row_table">
    <input type="hidden" name="movement_src" id="movement_src">
    <input type="hidden" name="movement_pin" id="movement_pin">
</form>

<script type="text/javascript">
    function setProduct(i) {
        $("#product_id").val(i);
    }
    function setProductEdit(pid, vid) {
        $("#product_id").val(pid);
        $("#var_id").val(vid);
        $("#particulars").val($("#particulars-" + vid).text());
        $("#size").val($("#size-" + vid).text());
        $("#unit").val($("#unit-" + vid).text());
        $("#cost").val($("#cost-" + vid).text());
        $("#price").val($("#price-" + vid).text());
        $("#image").removeAttr('required');
    }

    function deleteMovementRow(tableName, rowId, src) {
        var label = src === "ret" ? "return" : src;
        var submitDelete = function (pin) {
            if (!pin) return;
            $("#movement_row_id").val(rowId);
            $("#movement_row_table").val(tableName);
            $("#movement_src").val(src);
            $("#movement_pin").val(pin);
            $("#movement-del-form").submit();
        };

        if (typeof Swal !== "undefined" && Swal.fire) {
            Swal.fire({
                title: "Delete " + label + "?",
                text: "Please enter PIN to continue.",
                input: "password",
                inputPlaceholder: "Enter PIN",
                inputAttributes: {
                    autocomplete: "off"
                },
                showCancelButton: true,
                confirmButtonText: "Delete",
                confirmButtonColor: "#d33",
                preConfirm: function (pin) {
                    if (!pin) {
                        Swal.showValidationMessage("PIN is required");
                        return false;
                    }
                    return pin;
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitDelete(result.value);
                }
            });
            return;
        }

        var pin = prompt("Please key in your PIN to delete this " + label + "?");
        submitDelete(pin);
    }

     document.addEventListener("DOMContentLoaded", function () {
         var stockTable = document.getElementById("simpletable");
         if (!stockTable) return;

         if (typeof $ !== "undefined" && $.fn && $.fn.select2) {
             $(stockTable).find("select").each(function () {
                 if (!$(this).hasClass("select2-hidden-accessible")) {
                     $(this).select2({ width: "style" });
                 }
             });
         }
         
         // Add click handler for editable confirm dates
         if (typeof $ !== "undefined" && typeof Swal !== "undefined" && Swal.fire) {
             $(document).on('click', '.editable-confirm-date', function () {
                 var orderId = $(this).data('order-id');
                 var currentDate = $(this).data('date');
                 
                 Swal.fire({
                     title: 'Update Confirm Date',
                     html: '<input id="swal-confirm-date" type="date" class="swal2-input" value="' + currentDate + '">',
                     showCancelButton: true,
                     confirmButtonText: 'Update',
                     preConfirm: () => {
                         var newDate = document.getElementById('swal-confirm-date').value;
                         if (!newDate) {
                             Swal.showValidationMessage('Date is required');
                             return false;
                         }
                         return { date: newDate };
                     }
                 }).then((result) => {
                     if (result.isConfirmed) {
                         var form = $('<form method="post"></form>');
                         form.append('<input type="hidden" name="update_order_date" value="1">');
                         form.append('<input type="hidden" name="order_id" value="' + orderId + '">');
                         form.append('<input type="hidden" name="date_type" value="confirm">');
                         form.append('<input type="hidden" name="new_date" value="' + result.value.date + '">');
                         $('body').append(form);
                         form.submit();
                     }
                 });
             });
         }
     });

    // You may need to ensure 'bootstrap.Modal' is defined if using the lightbox logic
    // if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    //     var lightboxModal = new bootstrap.Modal(document.getElementById('lightboxModal'));
    //     document.querySelectorAll('[data-lightbox]').forEach(function(elem) {
    //         elem.addEventListener('click', function(event) {
    //             var images_path = event.target.tagName == 'IMG' ? event.target.parentNode : event.target;
    //             var recipient = images_path.getAttribute('data-lightbox');
    //             document.querySelector('.modal-image').src = recipient;
    //             lightboxModal.show();
    //         });
    //     });
    // }

    function removeClassByPrefix(node, prefix) {
        for (let i = 0; i < node.classList.length; i++) {
            if (node.classList[i].startsWith(prefix)) {
                node.classList.remove(node.classList[i]);
                i--;
            }
        }
    }
</script>