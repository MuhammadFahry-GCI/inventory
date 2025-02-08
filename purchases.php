<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $products = $_POST['products']; // Array of product IDs
    $quantities = $_POST['quantities']; // Array of quantities
    $prices = $_POST['prices']; // Array of prices

    $total_amount = 0;
    for ($i = 0; $i < count($products); $i++) {
        $total_amount += $quantities[$i] * $prices[$i];
    }

    // Insert purchase header
    $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, user_id, purchase_date, total_amount) VALUES (?, ?, CURDATE(), ?)");
    $stmt->execute([$supplier_id, $_SESSION['user_id'], $total_amount]);
    $purchase_id = $pdo->lastInsertId();

    // Insert purchase items
    $stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < count($products); $i++) {
        $stmt->execute([$purchase_id, $products[$i], $quantities[$i], $prices[$i]]);

        // Update product stock
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$quantities[$i], $products[$i]]);
    }

    header("Location: purchases.php");
    exit();
}

// Get suppliers
$stmt = $pdo->query("SELECT * FROM suppliers");
$suppliers = $stmt->fetchAll();

// Get purchases with supplier info
$stmt = $pdo->query("SELECT DISTINCT p.*, s.name as supplier_name, u.username as user_name 
                     FROM purchases p 
                     JOIN suppliers s ON p.supplier_id = s.id 
                     JOIN users u ON p.user_id = u.id 
                     ORDER BY p.created_at DESC");
$purchases = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Purchase Management - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex">
        <?php include 'templates/sidebar.php'; ?>

        <div class="content p-4" style="flex: 1;">
            <h2>Purchase Management</h2>

            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                New Purchase
            </button>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Created By</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo $purchase['purchase_date']; ?></td>
                            <td><?php echo $purchase['supplier_name']; ?></td>
                            <td><?php echo $purchase['user_name']; ?></td>
                            <!-- Change this line in the table -->
                            <td>Rp <?php echo number_format($purchase['total_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info">View Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Purchase Modal -->
    <div class="modal fade" id="addPurchaseModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="purchaseForm">
                        <div class="mb-3">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control" required onchange="loadSupplierProducts(this.value)">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="productList">
                            <!-- Product rows will be added here -->
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="addProductRow()">Add Product</button>
                        <button type="submit" class="btn btn-primary">Save Purchase</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let supplierProducts = {};

        function loadSupplierProducts(supplierId) {
            fetch(`get_supplier_products.php?supplier_id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    supplierProducts = data;
                    document.getElementById('productList').innerHTML = '';
                    addProductRow();
                });
        }

        function addProductRow() {
            const div = document.createElement('div');
            div.className = 'row mb-2';
            div.innerHTML = `
            <div class="col-md-5">
                <select name="products[]" class="form-control" required>
                    ${Object.entries(supplierProducts).map(([id, product]) => 
                        `<option value="${id}">${product.name} - $${product.price}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="quantities[]" class="form-control" placeholder="Quantity" required>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="prices[]" class="form-control" placeholder="Price" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
            document.getElementById('productList').appendChild(div);
        }
    </script>
</body>

</html>