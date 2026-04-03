<?php
/**
 * details/supplierProduct.php
 * Displays products associated with a specific supplier and handles linking new products.
 * Assumes RedBean (R::) is already loaded and configured by the main script.
 */

// --- Configuration & Validation ---
// Ensure the Supplier ID is passed via the URL (e.g., /product/supplierProduct/5)
if (!defined('ID') || !is_numeric(ID)) {
    die("<div class='alert alert-danger'>Error: Supplier ID is required. Please use the format: /product/supplierProduct/[SupplierID]</div>");
}

$supplierId = ID;
$message = ''; // Message container for success/error alerts

// --- 1. Handle Form Submission (Linking Product to Supplier) ---
if (isset($post->link_product) && isset($post->supplier_id) && isset($post->product_id)) {
    try {
        $link_supplier_id = (int)$post->supplier_id;
        $link_product_id = (int)$post->product_id;

        // Check if the link already exists in the product_supplier table
        $existing_link = R::findOne('product_supplier', 'supplier_id = ? AND product_id = ?', [$link_supplier_id, $link_product_id]);

        if (!$existing_link) {
            // Load the beans for the association
            $supplierBean = R::load('supplier', $link_supplier_id);
            $productBean = R::load('product', $link_product_id);

            // Check if both exist (R::load returns a bean with ID 0 if not found)
            if ($supplierBean->id && $productBean->id) {
                // RedBean many-to-many association using shared lists (creates product_supplier link)
                // We load the existing sharedProductList and add the new bean
                $supplierBean->sharedProductList[] = $productBean;
                R::store($supplierBean);
                
                $message = "<div class='alert alert-success'>Successfully linked Product ID $link_product_id to " . htmlspecialchars($supplierBean->company) . ".</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error: Supplier ID or Product ID not found.</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>Product ID $link_product_id is already linked to this supplier.</div>";
        }
    } catch (\Throwable $th) {
        // dump($th); // Uncomment for debugging
        $message = "<div class='alert alert-danger'>Database Error: Could not link product.</div>";
    }
}

// --- 2. Fetch Data for Display ---
// Execute the Custom SQL Query using R::getAll()
$sql = "
    SELECT 
        p.id AS product_id, 
        p.name AS product_name, 
        s.company AS supplier_company,
        ps.supplier_id 
    FROM 
        product_supplier ps
    JOIN 
        product p ON ps.product_id = p.id
    JOIN 
        supplier s ON ps.supplier_id = s.id
    WHERE 
        ps.supplier_id = ?
    ORDER BY p.name
";

// R::getAll returns a standard PHP array of arrays, safely bound to $supplierId
$productData = R::getAll($sql, [$supplierId]);

// Determine Supplier Name for the Header
if (!empty($productData)) {
    $supplierCompany = $productData[0]['supplier_company'];
} else {
    // Fallback: Check if the supplier even exists
    $supplierCompany = R::getCell('SELECT company FROM supplier WHERE id = ?', [$supplierId]);
}

// Check if the supplier exists
if (!$supplierCompany) {
    die("<div class='alert alert-warning'>Supplier with ID **$supplierId** not found.</div>");
}

// --- HTML & Presentation ---
?>

<div class="row">
    <div class="col-sm-12">
        <?php echo $message; // Display any messages ?>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5>
                            <span class="text-primary"></span> Products Supplied by: 
                            <?php echo htmlspecialchars($supplierCompany); ?> 
                        </h5>
                        <p class="text-muted mb-0">Total <?php echo count($productData); ?> Products</p>
                    </div>
                    
                    <div class="col-4 text-end">
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            onclick="setSupplierId(<?php echo $supplierId; ?>)" 
                            data-bs-toggle="modal" 
                            data-bs-target="#productFormModal">
                            <i class='fa fa-plus'></i> Add Product
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <?php if (count($productData) > 0): ?>
                        <table id="supplierProductsTable" class="table table-striped table-bordered nowrap">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Supplier Name (Company)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['supplier_company']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            No products are currently linked to this supplier.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productFormModal" tabindex="-1" aria-labelledby="productFormModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productFormModalLabel">Link New Product to Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post">
            <input type="hidden" id="modalSupplierId" name="supplier_id">
            
            <div class="mb-3">
                <label for="productId" class="form-label">Enter **Product ID** to Link</label>
                <input type="number" class="form-control" id="productId" name="product_id" required min="1">
                <div class="form-text text-muted">This should be the ID of an existing product.</div>
            </div>
            
            <button type="submit" name="link_product" class="btn btn-success">Link Product</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
    /**
     * Stores the current supplier ID in the hidden field of the modal form 
     * when the 'Add Product' button is clicked.
     */
    function setSupplierId(id) {
        // Set the value of the hidden input field inside the modal
        document.getElementById('modalSupplierId').value = id;
    }
</script>